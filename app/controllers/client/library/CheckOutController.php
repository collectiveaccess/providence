<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/library/CheckOutController.php :
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

	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectSearch.php');
 	require_once(__CA_MODELS_DIR__.'/ca_commerce_orders.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

 	class CheckOutController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		private $opo_client_services_config;
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			JavascriptLoadManager::register('tableList');
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register("panel");
 			
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 			
 			$this->opt_order = new ca_commerce_orders($this->request->getParameter('order_id', pInteger));
 			if (!$this->opt_order->getPrimaryKey()) { 
 				$this->request->setParameter('order_id', 0); 
 			}
 			$this->view->setVar('t_order', $this->opt_order);
 			$this->view->setVar('order_id', $this->opt_order->getPrimaryKey());
 			$this->view->setVar('t_item', $this->opt_order);
 			
 			$this->view->setVar('client_services_config', $this->opo_client_services_config = Configuration::load($this->request->config->get('client_services_config')));
 			$this->view->setVar('currency', $this->opo_client_services_config->get('currency'));
 			$this->view->setVar('currency_symbol', $this->opo_client_services_config->get('currency_symbol'));
 			
 			$this->opo_result_context = new ResultContext($this->request, 'ca_commerce_orders', 'basic_search_library');
 		}
 		# -------------------------------------------------------
 		public function Index() {
 			
 			$this->view->setVar('order_items', is_array($va_items = $this->opt_order->getItems()) ? $va_items : array());
 			
 			//
 			// Set default dates for order items
 			//
 			$t_order_item = new ca_commerce_order_items();
			$t_order_item->set('loan_checkout_date', time(), array('SET_DIRECT_DATE' => true));
			if (($vn_loan_period_in_days = $this->opo_client_services_config->get('default_library_loan_period')) <= 0) {
				$vn_loan_period_in_days = 7;
			}
			$t_order_item->set('loan_due_date', time() + ($vn_loan_period_in_days * 24 * 60 * 60), array('SET_DIRECT_DATE' => true));
 			$va_default_values = array('loan_checkout_date' => $t_order_item->get('loan_checkout_date', array('dateFormat' => 'delimited', 'timeOmit' => true)), 'loan_due_date' => $t_order_item->get('loan_due_date', array('dateFormat' => 'delimited', 'timeOmit' => true)));
 			
 			$this->view->setVar('t_order_item', $t_order_item);
 			
 			//$this->view->setVar('default_item_prices', $va_default_prices);
 			
 			//
 			// Additional fees
 			//
 			$this->view->setVar('additional_fees', $t_order_item->getAdditionalFeesHTMLFormBundle($this->request, array('config' => $this->opo_client_services_config, 'currency_symbol' => $this->opo_client_services_config->get('currency_symbol'), 'type' => 'L')));
 			$this->view->setVar('additional_fees_for_new_items', $t_order_item->getAdditionalFeesHTMLFormBundle($this->request, array('config' => $this->opo_client_services_config, 'currency_symbol' => $this->opo_client_services_config->get('currency_symbol'), 'use_defaults' => true, 'type' => 'L')));	
 			
 			$this->view->setVar('additional_fee_codes', $va_additional_fee_codes = $this->opo_client_services_config->getAssoc('additional_loan_fees'));
 			
 			foreach($va_additional_fee_codes as $vs_code => $va_info) {
 				$va_default_values['ADDITIONAL_FEE_'.$vs_code] = $va_info['default_cost'];
 			}
 			$this->view->setVar('default_values', $va_default_values);
 			
 			//
 			// Functional options
 			//
 			$this->view->setVar('loan_use_item_fee_and_tax', (bool)$this->opo_client_services_config->get('loan_use_item_fee_and_tax'));
 			$this->view->setVar('loan_use_notes_and_restrictions', (bool)$this->opo_client_services_config->get('loan_use_notes_and_restrictions'));
 			$this->view->setVar('loan_use_additional_fees', (bool)$this->opo_client_services_config->get('loan_use_additional_fees'));
 			
 			$this->render('checkout_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			// Field to user profile preference mapping
 			$va_mapping = array(
				'billing_organization' => 'user_profile_organization',
				'billing_address1' => 'user_profile_address1',
				'billing_address2' => 'user_profile_address2',
				'billing_city' => 'user_profile_city',
				'billing_zone' => 'user_profile_state',
				'billing_postal_code' => 'user_profile_postalcode',
				'billing_country' => 'user_profile_country',
				'billing_phone' => 'user_profile_phone',
				'billing_fax' => 'user_profile_fax',
				'shipping_organization' => 'user_profile_organization',
				'shipping_address1' => 'user_profile_address1',
				'shipping_address2' => 'user_profile_address2',
				'shipping_city' => 'user_profile_city',
				'shipping_zone' => 'user_profile_state',
				'shipping_postal_code' => 'user_profile_postalcode',
				'shipping_country' => 'user_profile_country',
				'shipping_phone' => 'user_profile_phone',
				'shipping_fax' => 'user_profile_fax'
			);
		
 			$va_errors = array();
 			$va_failed_insert_list = array();
 			
 			$va_fields = $this->opt_order->getFormFields();
 			foreach($va_fields as $vs_f => $va_field_info) {
 				switch($vs_f) {
 					case 'transaction_id':
 						// noop
 						break;
 					default:
 						if (isset($_REQUEST[$vs_f])) {
							if (!$this->opt_order->set($vs_f, $this->request->getParameter($vs_f, pString))) {
								$va_errors[$vs_f] = $this->opt_order->errors();
							}
						}
 						break;
 				}
 			}
 			
 			// Set additional fees for order
 			$va_fees = $this->opo_client_services_config->getAssoc('additional_order_fees');
 	
 			if (is_array($va_fees)) {
 				if (!is_array($va_fee_values = $this->opt_order->get('additional_fees'))) { $va_fee_values = array(); }
 				foreach($va_fees as $vs_code => $va_info) {
 					$va_fee_values[$vs_code] = ((float)$this->request->getParameter("additional_fee_{$vs_code}", pString));
 				}
 				$this->opt_order->set('additional_fees', $va_fee_values);
 			}
 			
 			$this->opt_order->setMode(ACCESS_WRITE);
 			if ($this->opt_order->getPrimaryKey()) {
 				$this->opt_order->update();
 				$vn_transaction_id = $this->opt_order->get('transaction_id');
 			} else {
 				// Set transaction
 				if (!($vn_transaction_id = $this->request->getParameter('transaction_id', pInteger))) {
 					if (!($vn_user_id = $this->request->getParameter('transaction_user_id', pInteger))) {
 						if ($vs_user_name = $this->request->getParameter('billing_email', pString)) {
							// Try to create user in-line
							$t_user = new ca_users();
							
							if ($t_user->load(array('user_name' => $vs_user_name))) {
								if ($t_user->get('active') == 1) { 					// user is active - if not active don't use
									if ($t_user->get('userclass') == 255) { 		// user is deleted
										$t_user->setMode(ACCESS_WRITE);
										$t_user->set('userclass', 1);				// 1=public user (no back-end login)
										$t_user->update();
										if ($t_user->numErrors()) {
											$this->notification->addNotification(_t('Errors occurred when undeleting user: %1', join('; ', $t_user->getErrors())), __NOTIFICATION_TYPE_ERROR__);
										} else {
											$vn_user_id = $t_user->getPrimaryKey();
										}
									} else {
										$vn_user_id = $t_user->getPrimaryKey();
									}
								} else {
									$t_user->setMode(ACCESS_WRITE);
									$t_user->set('active', 1);
									$t_user->set('userclass', 1);				// 1=public user (no back-end login)
									$t_user->update();
									if ($t_user->numErrors()) {
										$this->notification->addNotification(_t('Errors occurred when reactivating user: %1', join('; ', $t_user->getErrors())), __NOTIFICATION_TYPE_ERROR__);
									} else {
										$vn_user_id = $t_user->getPrimaryKey();
									}
								}
							} else {
								$t_user->setMode(ACCESS_WRITE);
								$t_user->set('user_name', $vs_user_name);
								$t_user->set('password', $vs_password = substr(md5(uniqid(microtime())), 0, 6));
								$t_user->set('userclass', 1);		// 1=public user (no back-end login)
								$t_user->set('fname', $vs_fname = $this->request->getParameter('billing_fname', pString));
								$t_user->set('lname', $vs_lname = $this->request->getParameter('billing_lname', pString));
								$t_user->set('email', $vs_user_name);
								
								$t_user->insert();
								if ($t_user->numErrors()) {
									$this->notification->addNotification(_t('Errors occurred when creating new user: %1', join('; ', $t_user->getErrors())), __NOTIFICATION_TYPE_ERROR__);
								} else {
									$vn_user_id = $t_user->getPrimaryKey();
									
									$this->notification->addNotification(_t('Created new client login for <em>%1</em>. Login name is <em>%2</em> and password is <em>%3</em>', $vs_fname.' '.$vs_lname, $vs_user_name, $vs_password), __NOTIFICATION_TYPE_INFO__);
									
									// Create related entity?
								}
							}
						}
						
 					}
 					
 					if ($vn_user_id) {
						// try to create transaction
						$t_trans = new ca_commerce_transactions();
						$t_trans->setMode(ACCESS_WRITE);
						$t_trans->set('user_id', $vn_user_id);
						$t_trans->set('short_description', "Created on ".date("c"));
						$t_trans->set('set_id', null);
						$t_trans->insert();
						if ($t_trans->numErrors()) {
							$this->notification->addNotification(_t('Errors occurred when creating commerce transaction: %1', join('; ', $t_trans->getErrors())), __NOTIFICATION_TYPE_ERROR__);
						} else {
							$vn_transaction_id = $t_trans->getPrimaryKey();
						}
					} else {
						$this->notification->addNotification(_t('You must specify a client'), __NOTIFICATION_TYPE_ERROR__);
						$va_errors['general'][] = new Error(1100, _t('You must specify a client'), 'CheckOutController->Save()', false, false, false);
					}
 				}
 				$this->opt_order->set('transaction_id', $vn_transaction_id);
 				
 				if ($vn_transaction_id) {
					$this->opt_order->set('order_type', 'L'); 	// L = loan (as opposed to 'O' for sales orders)	
					$this->opt_order->set('order_status', 'OPEN');
					$this->opt_order->insert();
					$this->request->setParameter('order_id', $x=$this->opt_order->getPrimaryKey());
				}
			}
 			
 			if ($vn_transaction_id) {
				// set user profile if not already set
				$t_trans = new ca_commerce_transactions($vn_transaction_id);
				$t_user = new ca_users($t_trans->get('user_id'));
				$t_user->setMode(ACCESS_WRITE);
				foreach($va_mapping as $vs_field => $vs_pref) {
					if (!strlen($t_user->getPreference($vs_pref))) {
						$t_user->setPreference($vs_pref, $this->opt_order->get($vs_field));
					}
				}
				$t_user->update();
				
				$va_additional_fee_codes = $this->opo_client_services_config->getAssoc('additional_loan_fees');
				
				// Look for newly added items
				$vn_items_added = 0;
				$vn_item_errors = 0;
				$vs_errors = '';
				foreach($_REQUEST as $vs_k => $vs_v) {
					if(preg_match("!^item_list_idnew_([\d]+)$!", $vs_k, $va_matches)) {
						if ($vn_object_id = (int)$vs_v) {
							// add item to order
							$va_values = array();
							foreach($_REQUEST as $vs_f => $vs_value) {
								if(preg_match("!^item_list_([A-Za-z0-9_]+)_new_".$va_matches[1]."$!", $vs_f, $va_matches2)) {
									$va_values[$va_matches2[1]] = $vs_value;
								}
							}
							
							// Set additional fees
							//
							$va_fee_values = array();
							foreach($va_additional_fee_codes as $vs_code => $va_info) {
								$va_fee_values[$vs_code] = $_REQUEST['additional_order_item_fee_'.$vs_code.'_new_'.$va_matches[1]];
							}
							
							$t_item = $this->opt_order->addItem($vn_object_id, $va_values, array('additional_fees' => $va_fee_values));
							
							if ($t_item && $t_item->getPrimaryKey()) {
								$vn_items_added++;
							} else {
								if ($this->opt_order->numErrors()) {
									$t_object = new ca_objects($vn_object_id);
									$this->notification->addNotification(_t('Could not check-out item <em>%1</em> (%2) due to errors: %3', $t_object->get('ca_objects.preferred_labels.name'), $t_object->get('idno'), join("; ", $this->opt_order->getErrors())), __NOTIFICATION_TYPE_ERROR__);
									$vn_item_errors++;
									
									$va_fee_values_proc = array();
									foreach($va_fee_values as $vs_k => $vs_v) {
										$va_fee_values_proc['ADDITIONAL_FEE_'.$vs_k] = $vs_v;
									}
									$va_failed_insert_list[] = array_merge(
										$va_values, $va_fee_values_proc, array(
											'autocomplete' => $_REQUEST['item_list_autocompletenew_'.$va_matches[1]],
											'id' => $vn_object_id
										)
									);
								}
							}
						}
					}
				}
				
				if (!$this->opt_order->numErrors() && $vn_items_added) {
					$this->notification->addNotification(_t('Checked out %1 %2 for %3 (order %4)', $vn_items_added, ($vn_items_added == 1) ? _t('item') : _t('items'), $t_user->get('fname').' '.$t_user->get('lname'), $this->opt_order->getOrderNumber()), __NOTIFICATION_TYPE_INFO__);	
					$this->opt_order->set('order_status', 'PROCESSED');
					$this->opt_order->update();
					$this->opt_order = new ca_commerce_orders();
					
					$this->request->setParameter('order_id', null);
					$this->view->setVar('t_order', $this->opt_order);
					$this->view->setVar('order_id', $this->opt_order->getPrimaryKey());
					$this->view->setVar('t_item', $this->opt_order);
				} else {
					if (($vn_items_added == 0) && ($this->opt_order->numErrors() == 0)) {
						$vs_errors = _t('No items were specified');
					} else {
						if ($vn_item_errors == 0) {
							$vs_errors = join('; ', $this->opt_order->getErrors());
						}
					}
					if ($vs_errors) {
						$va_errors['general'] = $this->opt_order->errors();
						$this->notification->addNotification(_t('Errors occurred: %1', $vs_errors), __NOTIFICATION_TYPE_ERROR__);
					}
				}
			}
 			$this->view->setVar('errors', $va_errors);
 			$this->view->setVar('failed_insert_list', $va_failed_insert_list);
 			
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Get() {
 			$ps_query = $this->request->getParameter('term', pString);
 			$o_search = new ObjectSearch();
 			
 			$qr_res = $o_search->search($ps_query);
 			
 			$va_object_ids = array();
 			while($qr_res->nextHit()) {
 				$va_object_ids[$qr_res->get('object_id')] = false;
 			}
 			
 			if (sizeof($va_object_ids)) {
				// get checked out items
				$o_db = new Db();
				
				$qr_checked_out_items = $o_db->query("
					SELECT DISTINCT i.object_id, i.loan_due_date
					FROM ca_commerce_order_items i
					WHERE
						i.loan_return_date IS NULL and i.loan_checkout_date > 0 AND i.object_id IN (?)
				", array(array_keys($va_object_ids)));
				
				
				while($qr_checked_out_items->nextRow()) {
					$va_object_ids[$qr_checked_out_items->get('object_id')] = $qr_checked_out_items->get('loan_due_date');
				}
				
				$t_object = new ca_objects();
				$qr_res = $t_object->makeSearchResult('ca_objects', array_keys($va_object_ids));
				
				$va_items = caProcessRelationshipLookupLabel($qr_res, $t_object, array());
		
				foreach($va_items as $vn_object_id => $va_object) {
					if ((int)$va_object_ids[$vn_object_id] > 0) {
						$vs_due_date_for_display = caGetLocalizedDate($va_object_ids[$vn_object_id], array('format' => 'delimited', 'timeOmit' => true));
						$va_items[$vn_object_id]['label'] .= ' [<em>'._t('on loan through %1', $vs_due_date_for_display).'</em>]';
						$va_items[$vn_object_id]['due_date'] = $va_object_ids[$vn_object_id];
						$va_items[$vn_object_id]['due_date_display'] = $vs_due_date_for_display;
					}
				}
			}
			if (!is_array($va_items)) { $va_items = array(); }
			
			if (!sizeof($va_items)) {		// nothing found
				$va_items[0] = array(
					'label' => _t('No matches found'),
					'type_id' => null,
					'object_id' => 0
				);
			}
			
			$this->view->setVar('object_list', $va_items);
			$this->view->setVar('object_id_list', $va_object_ids);
 			return $this->render('ajax_object_list_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_order = new ca_commerce_orders();
 			$this->view->setVar('order_list', $va_order_list = $t_order->getOrders($va_options));
 			return $this->render('widget_checkout_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>