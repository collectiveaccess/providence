<?php
/* ----------------------------------------------------------------------
 * plugins/TaskQueueHandlers/mediaImport.php : task queue plugin handler for mediaImport application plugin
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/core/Db/Transaction.php");
require_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugTaskQueueHandler.php");
require_once(__CA_LIB_DIR__.'/core/Db.php');
require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
require_once(__CA_LIB_DIR__.'/core/Zend/Mail.php');
require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');
	
	class WLPlugTaskQueueHandlermediaImport Extends WLPlug Implements IWLPlugTaskQueueHandler {
		# --------------------------------------------------------------------------------
		public $error;
		public $debug = 0;
	
		# --------------------------------------------------------------------------------
		# Constructor - all task queue handlers must implement this
		#
		public function __construct() {
			$this->error = new Error();
			$this->error->setErrorOutput(0);
		}
		# --------------------------------------------------------------------------------
		public function getHandlerName() {
			return _t("Batch media importer");
		}
		# --------------------------------------------------------------------------------
		public function getParametersForDisplay($pa_rec) {
			$va_parameters = caUnserializeForDatabase($pa_rec["parameters"]);
			
			$va_params = array();
			
			$va_params['importing_from'] = array(
				'label' => _t("Importing media from"),
				'value' => $va_parameters["directory"]
			);
			$va_params['number_of_files'] = array(
				'label' => _t("Files to import"),
				'value' => (int)$va_parameters["number_of_files"]
			);
			
			return $va_params;
		}
		# --------------------------------------------------------------------------------
		# Task processor function - all task queue handlers must implement this
		# 
		# Returns 1 on success, 0 on error
		public function process($pa_parameters) {
			$vs_import_mode = 						(string)$pa_parameters["import_mode"];
			$vs_matching_mode = 					(string)$pa_parameters["matching_mode"];
			$vs_directory = 						(string)$pa_parameters["directory"];
			$va_regexes = 							is_array($pa_parameters["regexes"]) ? $pa_parameters["regexes"] : array();
			
			$vn_object_type_id =				 	(int)$pa_parameters["object_type_id"];
			$vn_object_locale_id =				 	(int)$pa_parameters["object_locale_id"];
			$vs_object_status = 					(int)$pa_parameters["object_status"];
			$vs_object_access = 					(int)$pa_parameters["object_access"];
			$vn_object_representation_type_id =	 	(int)$pa_parameters["object_representation_type_id"];
			$vn_object_representation_locale_id =	(int)$pa_parameters["object_representation_locale_id"];
			$vs_object_representation_status = 		(int)$pa_parameters["object_representation_status"];
			$vs_object_representation_access = 		(int)$pa_parameters["object_representation_access"];
			
			$vb_delete_media_after_import =			(boolean)$pa_parameters["delete_media_after_import"];
			
			$vs_object_idno_option =				(string)$pa_parameters["object_idno_option"];
			$vs_object_idno =						(string)$pa_parameters["object_idno"];
			
			$vn_user_id =							(int)$pa_parameters['user_id'];
			
			$vb_dont_send_email =					(bool)$pa_parameters['dont_send_email'];
			
			$o_eventlog = new Eventlog();
			
			if (!is_dir($vs_directory)) { 
				// bad directory
				return false; 	
			}
			
			// get file names
			$r_dir = opendir($vs_directory);
			
			$va_files = array();
			while(($f = readdir($r_dir)) !== false) {
				if ($f{0} === '.') { continue; }
				if (is_dir("{$vs_directory}/{$f}")) { continue; } // don't allow directories!
				$va_files[] = $f;
			}
			
			sort($va_files);
			
			$o_eventlog->log(array(
				"CODE" => 'DEBG',
				"SOURCE" => "mediaImport",
				"MESSAGE" => "Starting import of media from {$vs_directory}"
			));
			
			$va_report = array();
			
			foreach($va_files as $f) {
				$va_report[$f] = array('errors' => array(), 'notes' => array());
				
				$vs_idno = null;
				
				$o_trans = new Transaction();
				$t_object = new ca_objects();
				$t_object->setTransaction($o_trans);
				if ($vs_matching_mode === '*') {
					foreach($va_regexes as $vs_regex_name => $va_regex_patterns) {
						foreach($va_regex_patterns as $vs_regex) {
							if (preg_match('!'.$vs_regex.'!', $f, $va_matches)) {
								if (!$vs_idno || (strlen($va_matches[1]) < strlen($vs_idno))) {
									$vs_idno = $va_matches[1];
								}
								if ($t_object->load(array('idno' => $va_matches[1], 'deleted' => 0))) {
									$va_report[$f]['notes'][] = _t('Matched media %1 to object using %2', $f, $vs_regex_name);
									break(2);
								}
							}
						}
					}
				} else {
					foreach($va_regexes[$vs_matching_mode] as $vs_regex) {
						if (preg_match('!'.$vs_regex.'!', $f, $va_matches)) {
							if (!$vs_idno || (strlen($va_matches[1]) < strlen($vs_idno))) {
								$vs_idno = $va_matches[1];
							}
							if($t_object->load(array('idno' => $va_matches[1], 'deleted' => 0))) {
								$va_report[$f]['notes'][] = _t('Matched media %1 to object using %2', $f, $vs_matching_mode);
								break;
							}
						}
					}
				}
				
				if (!$t_object->getPrimaryKey()) {
					// Use filename as idno if all else fails
					if ($t_object->load(array('idno' => $f, 'deleted' => 0))) {
						$va_report[$f]['notes'][] = _t('Matched media %1 to object using filename', $f);
					}
				}
				
				if ($t_object->getPrimaryKey()) {
					// found existing object
					$t_object->setMode(ACCESS_WRITE);
					
					$t_object->addRepresentation($vs_directory.'/'.$f, $vn_object_representation_type_id, $vn_object_representation_locale_id, $vs_object_representation_status, $vs_object_representation_access, false, array(), array('original_filename' => $f));
				
					if ($t_object->numErrors()) {	
						$o_eventlog->log(array(
							"CODE" => 'ERR',
							"SOURCE" => "mediaImport",
							"MESSAGE" => "Error importing {$f} from {$vs_directory}: ".join('; ', $t_object->getErrors())
						));
						$va_report[$f]['errors'][] = _t("Error importing %1 from %2: %3", $f, $vs_directory, join('; ', $t_object->getErrors()));
						$o_trans->rollback();
						continue;
					}	
				} else {
					// should we create new object?
					if ($vs_import_mode === 'new_objects_as_needed') {
						//print "LOADING ".$vs_directory.'/'.$f." INTO NEW OBJECT\n";
						$t_object->setMode(ACCESS_WRITE);
						$t_object->set('type_id', $vn_object_type_id);
						$t_object->set('locale_id', $vn_object_locale_id);
						$t_object->set('status', $vs_object_status);
						$t_object->set('access', $vs_object_access);
						
						if ($vs_object_idno_option === 'use_filename_as_identifier') {
							// use the filename as identifier
							$t_object->set('idno', $vs_idno ? $vs_idno : $f);
						} else {
							// Calculate identifier using numbering plugin
							$o_numbering_plugin = $t_object->getIDNoPlugInInstance();
							if (!($vs_sep = $o_numbering_plugin->getSeparator())) { $vs_sep = ''; }
							if (!is_array($va_idno_values = $o_numbering_plugin->htmlFormValuesAsArray('idno', $vs_object_idno, false, false, true))) { $va_idno_values = array(); }
							$t_object->set('idno', join($vs_sep, $va_idno_values));	// true=always set serial values, even if they already have a value; this let's us use the original pattern while replacing the serial value every time through
						}
						
						$t_object->insert();
						
						if ($t_object->numErrors()) {	
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => "Error creating new object while importing {$f} from {$vs_directory}: ".join('; ', $t_object->getErrors())
							));
							$va_report[$f]['errors'][] = _t("Error creating new object while importing %1 from %2: %3", $f, $vs_directory, join('; ', $t_object->getErrors()));
							$o_trans->rollback();
							continue;
						}
						
						$t_object->addLabel(
							array('name' => $f), $vn_object_locale_id, null, true
						);
						
						if ($t_object->numErrors()) {	
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => "Error creating object label while importing {$f} from {$vs_directory}: ".join('; ', $t_object->getErrors())
							));
							$va_report[$f]['errors'][] = _t("Error creating object label while importing %1 from %2: %3", $f, $vs_directory, join('; ', $t_object->getErrors()));
							$o_trans->rollback();
							continue;
						}
						$t_object->addRepresentation($vs_directory.'/'.$f, $vn_object_representation_type_id, $vn_object_representation_locale_id, $vs_object_representation_status, $vs_object_representation_access, true, array(), array('original_filename' => $f));
				
						if ($t_object->numErrors()) {	
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => "Error importing {$f} from {$vs_directory}: ".join('; ', $t_object->getErrors())
							));
							$va_report[$f]['errors'][] = _t("Error importing %1 from %2: %3", $f, $vs_directory, join('; ', $t_object->getErrors()));
							$o_trans->rollback();
							continue;
						}
					}
				}
				
				$va_report[$f]['notes'][] = _t('Imported file %1 from %2', $f, $vs_directory);
				if ($vb_delete_media_after_import) {
					@unlink($vs_directory.'/'.$f);
					$va_report[$f]['notes'][] = _t('Deleted file %1 from %2', $f, $vs_directory);
				}
				
				$o_trans->commit();
				$o_eventlog->log(array(
					"CODE" => 'DEBG',
					"SOURCE" => "mediaImport",
					"MESSAGE" => "Processing file {$f} from {$vs_directory}"
				));
			}
			
			if (!$vb_dont_send_email) {
				$o_config = Configuration::load(__CA_APP_DIR__.'/plugins/mediaImport/conf/mediaImport.conf');
				
				$t_user = new ca_users($vn_user_id);

				if (((bool)$o_config->get('send_email_reports')) && ($vs_email = $t_user->get('email'))) {
					$va_smtp_options = array();
					if (__CA_SMTP_AUTH__) {
						$va_smtp_options['auth'] = __CA_SMTP_AUTH__;
						$va_smtp_options['username'] = __CA_SMTP_USER__;
						$va_smtp_options['password'] = __CA_SMTP_PASSWORD__;
					}
					if (__CA_SMTP_PORT__) {
						$va_smtp_options['port'] = __CA_SMTP_PORT__;
					} else {
						$va_smtp_options['port'] = 25;
					}
					
					$o_view = new View(null, __CA_APP_DIR__.'/plugins/mediaImport/views');
					$o_view->setVar('report', $va_report);
					
					try {
						$o_transport = new Zend_Mail_Transport_Smtp(__CA_SMTP_SERVER__, $va_smtp_options);
						Zend_Mail::setDefaultTransport($o_transport);
						 
						$o_mail = new Zend_Mail();
						$o_mail->setBodyHtml($o_view->render('email_report_html.php'));
						$o_mail->setFrom(__CA_ADMIN_EMAIL__, 'CollectiveAccess MediaImporter');
						$o_mail->addTo($vs_email, trim($t_user->get('fname').' '.$t_user->get('lname')));
						$o_mail->setSubject(_t('CollectiveAccess batch media import processing report'));
						$o_mail->send();
					} catch (Exception $e) {
						$o_eventlog->log(array(
							"CODE" => 'ERR',
							"SOURCE" => "mediaImport",
							"MESSAGE" => "Could not send report email to '{$vs_email}': ".$e->getMessage()
						));
					}
				}
			}
			
			return $va_report;
		}
		# --------------------------------------------------------------------------------
		# Cancel function - cancels queued task, doing cleanup and deleting task queue record
		# all task queue handlers must implement this
		#
		# Returns 1 on success, 0 on error
		public function cancel($pn_task_id, $pa_parameters) {
			return true;
		}
		# --------------------------------------------------------------------------------
	}
?>