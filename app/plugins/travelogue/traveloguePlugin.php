<?php
/* ----------------------------------------------------------------------
 * traveloguePlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_APP_DIR__.'/helpers/mailHelpers.php');
 	require_once(__CA_APP_DIR__.'/helpers/clientServicesHelpers.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
 	require_once(__CA_LIB_DIR__.'/core/Db.php');
 	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 	
 	require_once(__CA_LIB_DIR__.'/core/Zend/Mail.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Mail/Storage/Imap.php');
	
	class traveloguePlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_client_services_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Accepts submissions of media via email.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/travelogue.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the twitterPlugin plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Perform client services-related periodic tasks
		 */
		public function hookPeriodicTask(&$pa_params) {
			global $AUTH_CURRENT_USER_ID;
						
			$t_log = new Eventlog();
			$o_db = new Db();
			//$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not authenticate to remote system %1', $vs_base_url), 'SOURCE' => 'traveloguePlugin->hookPeriodicTask'));

			// Get new email
				$pn_locale_id = 1; // US
			
				$vs_server = $this->opo_config->get('imap_server');
				$vs_username = $this->opo_config->get('username');
				$vs_password = $this->opo_config->get('password');
				$vs_ssl = $this->opo_config->get('ssl');
				
				if (!$vs_server) { return; }
				if (!$vs_username) { return; }
				
				try {
					$o_mail = new Zend_Mail_Storage_Imap(array(
						'host'     => $vs_server,
						'user'     => $vs_username,
						'password' => $vs_password, 
						'ssl'      => $vs_ssl)
					);
				} catch (Exception $e) {
					return null;
				}
				
				$va_mimetypes = $this->opo_config->getList('mimetypes');
				
				$va_mail_to_delete = array();
				foreach ($o_mail as $vn_message_num => $o_message) {
					$va_mail_to_delete[$vn_message_num] = true;
					
					// Extract title from subject line of email
					$vs_subject = $o_message->subject;
					$vs_from = $o_message->headerExists('from') ? $o_message->from : "";
					print "PROCESSING {$vs_subject} FROM {$vs_from}\n";
					
					// Extract media from email attachments
					// Extract caption from email body
					$va_images = array();
					$va_texts = array();
					foreach (new RecursiveIteratorIterator($o_message) as $o_part) {
						try {
							if (in_array(strtok($o_part->contentType, ';'), $va_mimetypes)) {
								$va_images[] = $o_part;
							} else {
								if (in_array(strtok($o_part->contentType, ';'), array("text/plain", "text/html"))) {
									$va_texts[] = (string)$o_part;
								}
							}
						} catch (Zend_Mail_Exception $e) {
							// ignore
						}
					}
					
					if (!sizeof($va_images)) { continue; }
					
					// Get user by email address
					if (preg_match('!<([^>]+)>!', $vs_from, $va_matches)) {	// extract raw address from "from" header
						$vs_from = $va_matches[1];
					}
					$t_user = new ca_users();
					if ($t_user->load(array('email' => $vs_from))) {
						$AUTH_CURRENT_USER_ID = $vn_user_id = $t_user->getPrimaryKey();	// force libs to consider matched user as logged in; change log will reflect this name
					} else {
						$vn_user_id = null;
					}
					
					// Create object
					$t_object = new ca_objects();
					$t_object->setMode(ACCESS_WRITE);
					$t_object->set('type_id', $this->opo_config->get('object_type'));
					
					// TODO: set idno to autogenerated # and/or allow for configurable policy
					$t_object->set('idno', '');
					$t_object->set('access', $this->opo_config->get('default_access'));
					$t_object->set('status', $this->opo_config->get('default_status'));
					
					// TODO: make this a configurable mapping ala how media metadata is done
					$t_object->addAttribute(
						array('locale_id' => $pn_locale_id, 'generalNotes' => join("\n\n", $va_texts)),
						'generalNotes'
					);
					
					$t_object->insert();
					DataMigrationUtils::postError($t_object, "While adding object", "traveloguePlugin"); // TODO: log this
					
					$t_object->addLabel(
						array('name' => $vs_subject), $pn_locale_id, null, true
					);
					DataMigrationUtils::postError($t_object, "While adding label", "traveloguePlugin");  // TODO: log this
					
					// Add representation
					$vs_tmp_file_path = tempnam(caGetTempDirPath(), 'travelogue_');
					foreach($va_images as $vn_i => $vs_file_content) {
						if (file_put_contents($vs_tmp_file_path, base64_decode((string)$vs_file_content))) {
							$t_object->addRepresentation($vs_tmp_file_path, $this->opo_config->get('representation_type'), 1, $this->opo_config->get('default_status'), $this->opo_config->get('default_access'), true);
							DataMigrationUtils::postError($t_object, "While adding media", "traveloguePlugin");  // TODO: log this
						}
					}
					
					// TODO: add option to link user-as-entity to image (probably as creator)
					
				}
				foreach(array_reverse(array_keys($va_mail_to_delete)) as $vn_message_num) {
					$o_mail->removeMessage($vn_message_num);
				}
				
		}
		# -------------------------------------------------------
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}
?>