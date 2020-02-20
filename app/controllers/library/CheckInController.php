<?php
/* ----------------------------------------------------------------------
 * app/controllers/library/CheckInController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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

 	require_once(__CA_APP_DIR__.'/helpers/libraryServicesHelpers.php');
	require_once(__CA_LIB_DIR__.'/Search/ObjectCheckoutSearch.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_checkouts.php');
	require_once(__CA_LIB_DIR__.'/ResultContext.php');

 	class CheckInController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkin') || !$this->request->config->get('enable_library_services')  || !$this->request->config->get('enable_object_checkout')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			AssetLoadManager::register('objectcheckin');
 			
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Index() {
 			$this->render('checkin/items_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Return info via ajax on selected object
 		 */
 		public function GetObjectInfo() {
 			$pn_checkout_id = $this->request->getParameter('checkout_id', pInteger);
 			
 			$t_checkout = new ca_object_checkouts($pn_checkout_id);
 			$t_user = new ca_users($t_checkout->get('user_id'));
 			$t_object = new ca_objects($t_checkout->get('object_id'));
 			$va_status = $t_object->getCheckoutStatus();
			$va_checkout_config = ca_object_checkouts::getObjectCheckoutConfigForType($t_object->getTypeCode());
 			
 			$va_info = array(
 				'object_id' => $t_object->getPrimaryKey(),
 				'idno' => $t_object->get('idno'),
 				'name' => $t_object->get('ca_objects.preferred_labels.name'),
 				'media' => $t_object->getWithTemplate('^ca_object_representations.media.icon'),
 				'status' => $t_object->getCheckoutStatus(),
 				'status_display' => $t_object->getCheckoutStatus(array('returnAsText' => true)),
 				'checkout_date' => $t_checkout->get('ca_object_checkouts.checkout_date', array('timeOmit' => true)),
 				'user_name' => $t_user->get('ca_users.fname').' '.$t_user->get('ca_users.lname'),
 				'config' => $va_checkout_config
 			);
 			$va_info['title'] = $va_info['name'].' ('.$va_info['idno'].')';
 			$va_info['borrower'] = _t('Borrowed by %1 on %2', $va_info['user_name'], $va_info['checkout_date']);
 			$this->view->setVar('data', $va_info);
 			$this->render('checkin/ajax_data_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function SaveTransaction() {
 			$ps_item_list = $this->request->getParameter('item_list', pString);
 			$pa_item_list = json_decode(stripslashes($ps_item_list), true);
 			
 			if (is_array($pa_item_list)) {
				$t_checkout = new ca_object_checkouts();
			
				$va_ret = array('status' => 'OK', 'errors' => array(), 'checkins' => array());
				foreach($pa_item_list as $vn_i => $va_item) {
					if ($t_checkout->load($va_item['checkout_id'])) {
						$vn_object_id = $t_checkout->get('object_id');
						$t_object = new ca_objects($vn_object_id);
						if ($t_checkout->isOut()) { 
							try {
								$t_checkout->checkin($vn_object_id, $va_item['note'], array('request' => $this->request));
							
								$t_user = new ca_users($t_checkout->get('user_id'));
								$vs_user_name = $t_user->get('ca_users.fname').' '.$t_user->get('ca_users.lname');
								$vs_borrow_date = $t_checkout->get('ca_object_checkouts.checkout_date', array('timeOmit' => true));
						
								if ($t_checkout->numErrors() == 0) {
									$va_ret['checkins'][] = _t('Returned <em>%1</em> (%2) borrowed by %3 on %4', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno'), $vs_user_name, $vs_borrow_date);
								} else {
									$va_ret['errors'][] = _t('Could not check in <em>%1</em> (%2): %3', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno'), join("; ", $t_checkout->getErrors()));
								}
							} catch (Exception $e) {
								$va_ret['errors'][] = _t('<em>%1</em> (%2) is not out', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno'));
							}
						} else {
							$va_ret['errors'][] = _t('<em>%1</em> (%2) is not out', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('ca_objects.idno'));
						}
					}
				}
			}
 			
 			$this->view->setVar('data', $va_ret);
 			$this->render('checkin/ajax_data_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			return $this->render('checkin/widget_checkin_html.php', !$this->request->isAjax());
 		}
 		# -------------------------------------------------------
 	}
