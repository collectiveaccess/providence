<?php
/* ----------------------------------------------------------------------
 * notificationsPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/Logging/Eventlog.php');
 	require_once(__CA_LIB_DIR__.'/Db.php');
 	require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_triggers.php');
	
	class notificationsPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Handles periodic email alert tasks for systen notification features.');
			parent::__construct();
			
			$this->opo_config = Configuration::load();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true 
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => defined('__CA_QUEUE_ENABLED__') && __CA_QUEUE_ENABLED__
			);
		}
		# -------------------------------------------------------
		/**
		 * Generate notifications
		 */
		public function hookPeriodicTask(&$pa_params) {
			$t_log = new Eventlog();
			$o_db = new Db();
			
			ca_metadata_alert_triggers::firePeriodicTriggers();
			
			if (!defined('__CA_QUEUE_ENABLED__') || !__CA_QUEUE_ENABLED__) { return true; }
			
			if (is_array($va_notifications = ca_users::getQueuedEmailNotifications())) {
				
				$vs_app_name = $this->opo_config->get('app_display_name');
				$vs_sender_email = $this->opo_config->get('notification_email_sender');
		
				// digest by user
				$va_notifications_by_user = array_reduce($va_notifications, function($c, $i) { $c[$i['user_id']][] = $i; return $c; }, []);
			
				foreach($va_notifications_by_user as $vn_user_id => $va_notifications_for_user) {
					if(!sizeof($va_notifications_for_user)) { continue; }
					$vs_to_email = $va_notifications_for_user[0]['email'];
					if (caSendMessageUsingView(null, $vs_to_email, $vs_sender_email, $this->opo_config->get('notification_email_subject'), "notification_digest.tpl", ['notifications' => $va_notifications_for_user, 'sent_on' => time()], null, null, ['source' => 'Notification'])) {
						$va_notification_subject_ids = array_map(function($v) { return $v['subject_id']; }, $va_notifications_for_user);
						$t_subject = new ca_notification_subjects();
						foreach($va_notification_subject_ids as $vn_subject_id) {
							if ($t_subject->load($vn_subject_id)) {
								$t_subject->setMode(ACCESS_WRITE);
								$t_subject->set('delivery_email_sent_on', _t('now'));
								if (!$t_subject->update()) {
									$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not set notification subject %1 as read: %2', $vn_subject_id, join('; ', $t_subject->getErrors())), 'SOURCE' => 'notificationsPlugin->hookPeriodicTask'));
									continue;
								}
							}
						}
					} // caSendMessageUsingView logs failures
				
				}
			}
					
			return true;
		}
		# -------------------------------------------------------
	}
