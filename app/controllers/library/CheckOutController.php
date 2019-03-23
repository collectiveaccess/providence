<?php
/* ----------------------------------------------------------------------
 * app/controllers/library/CheckOutController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2019 Whirl-i-Gig
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
	require_once(__CA_LIB_DIR__.'/Search/ObjectSearch.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_checkouts.php');
	require_once(__CA_LIB_DIR__.'/ResultContext.php');

 	class CheckOutController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout') || !$this->request->config->get('enable_library_services')  || !$this->request->config->get('enable_object_checkout')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			AssetLoadManager::register('objectcheckout');
 			
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Begin checkout process with user select
 		 */
 		public function Index() {
 			$this->render('checkout/find_user_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Checkout items screen
 		 */
 		public function Items() {
 			$pn_user_id = $this->request->getParameter('user_id', pInteger);
 			
 			$this->view->setVar('user_id', $pn_user_id);
 			$this->view->setVar('checkout_types', ca_object_checkouts::getObjectCheckoutTypes());
 			
 			$this->render('checkout/items_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Return info via ajax on selected object
 		 */
 		public function GetObjectInfo() {
 			$pn_user_id = $this->request->getParameter('user_id', pInteger);
 			$pn_object_id = $this->request->getParameter('object_id', pInteger);
 			
 			$t_object = new ca_objects($pn_object_id);
 			
 			$vn_current_user_id = $vs_current_user = $vs_current_user_checkout_date = $vs_reservation_list = null;		// user_id of current holder of item
 			
 			$vb_is_reserved_by_current_user = false;
 			switch($vn_status = $t_object->getCheckoutStatus()) {
 				case __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__:
 					$vs_status_display = _t('Available');
 					break;
 				case __CA_OBJECTS_CHECKOUT_STATUS_OUT__:
 					$t_checkout = ca_object_checkouts::getCurrentCheckoutInstance($pn_object_id);
 					$vn_current_user_id = $t_checkout->get('user_id');
 					
 					$vs_status_display = ($vn_current_user_id == $pn_user_id) ? _t('Out with this user') : _t('Out');
 					$vs_current_user_checkout_date = $t_checkout->get('checkout_date', array('timeOmit' => true));
 					
 					break;
 				case __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__:
 					$t_checkout = ca_object_checkouts::getCurrentCheckoutInstance($pn_object_id);
 					$vn_current_user_id = $t_checkout->get('user_id');
 					$va_reservations = $t_object->getCheckoutReservations();
 					$vn_num_reservations = is_array($va_reservations) ? sizeof($va_reservations) : 0;
 					
 					$vs_current_user_checkout_date = $t_checkout->get('checkout_date', array('timeOmit' => true));
 					
 					$vs_status_display = ($vn_num_reservations == 1) ? _t('Out with %1 reservation', $vn_num_reservations) : _t('Out with %1 reservations', $vn_num_reservations);
 					break;
 				case __CA_OBJECTS_CHECKOUT_STATUS_RESERVED__:
 					// get reservations list
 					$va_reservations = $t_object->getCheckoutReservations();
 					$vn_num_reservations = is_array($va_reservations) ? sizeof($va_reservations) : 0;
 					$t_checkout = ca_object_checkouts::getCurrentCheckoutInstance($pn_object_id);
 					$vs_current_user_checkout_date = $t_checkout->get('created_on', array('timeOmit' => true));
 					
 					$vs_status_display = ($vn_num_reservations == 1) ? _t('Reserved') : _t('Available with %1 reservations', $vn_num_reservations);
 					
 					break;
 			}
 			
 			$vb_is_held_by_current_user = ($pn_user_id == $vn_current_user_id);
 			
 			if (is_array($va_reservations)) {
				$va_tmp = array();
				foreach($va_reservations as $va_reservation) {
					$vb_is_reserved_by_current_user = ($va_reservation['user_id'] == $pn_user_id);
					$t_user = new ca_users($va_reservation['user_id']);
					$va_tmp[] = $t_user->get('fname').' '.$t_user->get('lname').(($vs_email = $t_user->get('email')) ? " ({$vs_email})" : "");
				}
				$vs_reservation_list = join(", ", $va_tmp);
			}
 			
 			if ($vn_current_user_id) {
 				$t_user = new ca_users($vn_current_user_id);
 				$vs_current_user = $t_user->get('fname').' '.$t_user->get('lname');
 			}
 		
 			
			$va_checkout_config = ca_object_checkouts::getObjectCheckoutConfigForType($t_object->getTypeCode());
 			
 			$vs_holder_display_label = '';
 			if ($vb_is_held_by_current_user) {
 				$vs_status_display = _t('The user currently has this item');
 			} elseif($vb_is_reserved_by_current_user) {
 				$vs_status_display = _t('The user has reserved this item');
 			} else {
 				$vs_reserve_display_label = ($vn_status == 3) ? _t('Currently reserved by %1', $vs_reservation_list) : _t('Will reserve');
 				
 				if (in_array($vn_status, array(1, 2))) {
 					$vs_holder_display_label = _t('held by %1 since %2', $vs_current_user, $vs_current_user_checkout_date);
 				}
 			}
 			$va_info = array(
 				'object_id' => $t_object->getPrimaryKey(),
 				'idno' => $t_object->get('idno'),
 				'name' => $t_object->get('ca_objects.preferred_labels.name'),
 				'media' => $t_object->getWithTemplate('^ca_object_representations.media.icon'),
 				'status' => $vn_status,
 				'status_display' => $vs_status_display,
 				'numReservations' => is_array($va_reservations) ? sizeof($va_reservations) : 0,
 				'reservations' => $va_reservations,
 				'config' => $va_checkout_config,
 				'current_user_id' => $vn_current_user_id,
 				'current_user' => $vs_current_user,
 				'current_user_checkout_date' => $vs_current_user_checkout_date,
 				'isOutWithCurrentUser' => ($pn_user_id == $vn_current_user_id),
 				'isReservedByCurrentUser' => $vb_is_reserved_by_current_user,
 				
 				'reserve_display_label' => $vs_reserve_display_label,
 				'due_on_display_label' => _t('Due on'),
 				'notes_display_label' => _t('Notes'),
 				'holder_display_label' => $vs_holder_display_label
 			);
 			$va_info['title'] = $va_info['name']." (".$va_info['idno'].")";
 			
 			$va_info['storage_location'] = $t_object->getWithTemplate($va_checkout_config['show_storage_location_template']);
 			
 			$this->view->setVar('data', $va_info);
 			$this->render('checkout/ajax_data_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Return info via ajax on selected object
 		 */
 		public function SaveTransaction() {
 			$pn_user_id = $this->request->getParameter('user_id', pInteger);
 			$ps_item_list = $this->request->getParameter('item_list', pString);
 			$pa_item_list = json_decode(stripslashes($ps_item_list), true);
 			if (!is_array($pa_item_list)) { $pa_item_list = []; }
 			
 			$t_checkout = ca_object_checkouts::newCheckoutTransaction();
 			$va_ret = array('status' => 'OK', 'total' => sizeof($pa_item_list), 'errors' => array(), 'checkouts' => array());
 				
 			if(is_array($pa_item_list)) {
				$t_object = new ca_objects();
				foreach($pa_item_list as $vn_i => $va_item) {
					if (!$t_object->load(array('object_id' => $va_item['object_id'], 'deleted' => 0))) { continue; }
				
					$vs_name = $t_object->getWithTemplate("^ca_objects.preferred_labels.name (^ca_objects.idno)");
					if ($va_checkout_info = $t_checkout->objectIsOut($va_item['object_id'])) {
						if ($va_checkout_info['user_id'] == $pn_user_id) {
							// user already has item so skip it
							$va_ret['errors'][$va_item['object_id']] = _t('User already has <em>%1</em>', $vs_name);
							continue;
						}
						try {
							$vb_res = $t_checkout->reserve($va_item['object_id'], $pn_user_id, $va_item['note'], array('request' => $this->request));
							if ($vb_res) {
								$va_ret['checkouts'][$va_item['object_id']] = _t('Reserved <em>%1</em>', $vs_name);
							} else {
								$va_ret['errors'][$va_item['object_id']] = _t('Could not reserve <em>%1</em>: %2', $vs_name, join('; ', $t_checkout->getErrors()));
							}
						} catch (Exception $e) {
							$va_ret['errors'][$va_item['object_id']] = _t('Could not reserve <em>%1</em>: %2', $vs_name, $e->getMessage());
						}
					} else {
						try {
							$vb_res = $t_checkout->checkout($va_item['object_id'], $pn_user_id, $va_item['note'], $va_item['due_date'], array('request' => $this->request));
				
							if ($vb_res) {
								$va_ret['checkouts'][$va_item['object_id']] = _t('Checked out <em>%1</em>; due date is %2', $vs_name, $va_item['due_date']);
							} else {
								$va_ret['errors'][$va_item['object_id']] = _t('Could not check out <em>%1</em>: %2', $vs_name, join('; ', $t_checkout->getErrors()));
							}
						} catch (Exception $e) {
							$va_ret['errors'][$va_item['object_id']] = _t('Could not check out <em>%1</em>: %2', $vs_name, $e->getMessage());
						}
					}
				}
			}
 			
 			$this->view->setVar('data', $va_ret);
 			$this->render('checkout/ajax_data_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_user = new ca_users($pn_user_id = $this->request->getParameter('user_id', pInteger));
 			
 			$this->view->setVar('user_id', $pn_user_id);
 			$this->view->setVar('t_user', $t_user);
 			
 			return $this->render('checkout/widget_checkout_html.php', !$this->request->isAjax());
 		}
 		# -------------------------------------------------------
 	}
