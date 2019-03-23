<?php
/* ----------------------------------------------------------------------
 * libraryServicesPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2018 Whirl-i-Gig
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
 	require_once(__CA_APP_DIR__.'/helpers/libraryServicesHelpers.php');
 	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_checkouts.php');
 	require_once(__CA_LIB_DIR__.'/Logging/Eventlog.php');
 	require_once(__CA_LIB_DIR__.'/Db.php');
	
	class libraryServicesPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_library_services_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Handles periodic cleanup and email alert tasks for library services features.');
			parent::__construct();
			
			$this->opo_config = Configuration::load();
			$this->opo_library_services_config = caGetlibraryServicesConfiguration();
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
				'available' => ((bool)$this->opo_config->get('enable_library_services'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Perform library services-related periodic tasks
		 */
		public function hookPeriodicTask(&$pa_params) {
			$t_log = new Eventlog();
			$o_db = new Db();
			
			if (!((bool)$this->opo_config->get('enable_library_services'))) { return true; }
			
			if ((bool)$this->opo_config->get('enable_object_checkout')) {
				$t_user = new ca_users();
				$t_checkout = new ca_object_checkouts();
				
				$vs_app_name = $this->opo_config->get('app_display_name');
				
				$vs_sender_name = $this->opo_library_services_config->get('notification_sender_name');
				$vs_sender_email = $this->opo_library_services_config->get('notification_sender_email');
				if (!is_array($va_administrative_email_addresses = $this->opo_library_services_config->getList('administrative_email_addresses'))) {
					$va_administrative_email_addresses = array();
				}
				
				// Periodic "coming due" notices	
				if ($this->opo_library_services_config->get('send_coming_due_notices') && ($vs_interval = $this->opo_library_services_config->get('coming_due_interval'))) {
					try {
						$va_items_by_user = ca_object_checkouts::getItemsDueWithin($vs_interval, array('groupBy' => 'user_id', 'template' => $this->opo_library_services_config->get('coming_due_item_display_template'), 'notificationInterval' => $this->opo_library_services_config->get('coming_due_notification_interval')));
						
						foreach($va_items_by_user as $vn_user_id => $va_items_for_user) {
							if ($t_user->load($vn_user_id)) {
								if ($vs_user_email = $t_user->get('email')) {
									$vs_subject = _t('Notice of items coming due for return');
									if (caSendMessageUsingView(null, $vs_user_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "library_coming_due.tpl", ['subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'items' => $va_items_for_user], null, $va_administrative_email_addresses, ['source' => 'Libary item due'])) {
										// mark record
										foreach($va_items_for_user as $va_item) {
											if ($t_checkout->load($va_item['checkout_id'])) {
												$t_checkout->setMode(ACCESS_WRITE);
												$t_checkout->set('last_sent_coming_due_email', _t('now'));	
												$t_checkout->update();
												if ($t_checkout->numErrors()) {
													$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not mark checkout coming due message sent time because update failed: %1', join("; ", $t_checkout->getErrors())), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
												}
											} else {
												$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not mark checkout coming due message sent time because checkout id %1 was not found', $va_item['checkout_id']), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
											}
										}
									} 
								} else {
									// no email
									$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('No email address set for user %1 (%2)', $t_user->get('user_name'), trim($t_user->get('fname').' '.$t_user->get('lname'))), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
								}
							} else {
								// invalid user
								$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('User id %1 does not exist', $vn_user_id), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
							}
						}
					} catch(Exception $e) {
						$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Invalid interval (%1) specified for coming due notices', $vs_interval), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));
					}
				}
				
				// Periodic overdue notices
				if ($this->opo_library_services_config->get('send_overdue_notices')) {
					try {
						$va_items_by_user = ca_object_checkouts::getOverdueItems(array('groupBy' => 'user_id', 'template' => $this->opo_library_services_config->get('overdue_item_display_template'), 'notificationInterval' => $this->opo_library_services_config->get('overdue_notification_interval')));
						
						foreach($va_items_by_user as $vn_user_id => $va_items_for_user) {
							if ($t_user->load($vn_user_id)) {
								if ($vs_user_email = $t_user->get('email')) {
									$vs_subject = _t('Notice of overdue items');
									if (caSendMessageUsingView(null, $vs_user_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "library_overdue.tpl", ['subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'items' => $va_items_for_user], null, $va_administrative_email_addresses, ['source' => 'Library item overdue'])) {
										// mark record
										foreach($va_items_for_user as $va_item) {
											if ($t_checkout->load($va_item['checkout_id'])) {
												$t_checkout->setMode(ACCESS_WRITE);
												$t_checkout->set('last_sent_overdue_email', _t('now'));	
												$t_checkout->update();
												if ($t_checkout->numErrors()) {
													$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not mark checkout overdue message sent time because update failed: %1', join("; ", $t_checkout->getErrors())), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
												}
											} else {
												$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not mark checkout overdue message sent time because checkout id %1 was not found', $va_item['checkout_id']), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
											}
										}
									} 
								} else {
									// no email
									$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('No email address set for user %1 (%2)', $t_user->get('user_name'), trim($t_user->get('fname').' '.$t_user->get('lname'))), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
								}
							} else {
								// invalid user
								$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('User id %1 does not exist', $vn_user_id), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
							}
						}
					} catch(Exception $e) {
						$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Failed to get overdue list'), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));
					}
				}
				
				// Notice when reservation becomes available
				if ($this->opo_library_services_config->get('send_reservation_available_notices')) {
					try {
						$va_items_by_user = ca_object_checkouts::getReservedAvailableItems(array('groupBy' => 'user_id', 'template' => $this->opo_library_services_config->get('overdue_item_display_template'), 'notificationInterval' => $this->opo_library_services_config->get('reservation_available_notification_interval')));
						
						foreach($va_items_by_user as $vn_user_id => $va_items_for_user) {
							if ($t_user->load($vn_user_id)) {
								if ($vs_user_email = $t_user->get('email')) {
									$vs_subject = _t('Notice of reserved available items');
									if (caSendMessageUsingView(null, $vs_user_email, $vs_sender_email, "[{$vs_app_name}] {$vs_subject}", "library_reservation_available.tpl", ['subject' => $vs_subject, 'from_user_id' => $vn_user_id, 'sender_name' => $vs_sender_name, 'sender_email' => $vs_sender_email, 'sent_on' => time(), 'items' => $va_items_for_user], null, $va_administrative_email_addresses, ['source' => 'Library reserved item available'])) {
										// mark record
										foreach($va_items_for_user as $va_item) {
											if ($t_checkout->load($va_item['checkout_id'])) {
												$t_checkout->setMode(ACCESS_WRITE);
												$t_checkout->set('last_reservation_available_email', _t('now'));	
												$t_checkout->update();
												if ($t_checkout->numErrors()) {
													$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not mark reserved available message sent time because update failed: %1', join("; ", $t_checkout->getErrors())), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
												}
											} else {
												$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Could not mark reserved available message sent time because checkout id %1 was not found', $va_item['checkout_id']), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
											}
										}
									} 
								} else {
									// no email
									$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('No email address set for user %1 (%2)', $t_user->get('user_name'), trim($t_user->get('fname').' '.$t_user->get('lname'))), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
								}
							} else {
								// invalid user
								$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('User id %1 does not exist', $vn_user_id), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));	
							}
						}
					} catch(Exception $e) {
						$t_log->log(array('CODE' => 'ERR', 'MESSAGE' => _t('Failed to get reserved available list'), 'SOURCE' => 'libraryServicesPlugin->hookPeriodicTask'));
					}
				}
			}
					
			return true;
		}
		# -------------------------------------------------------
	}
