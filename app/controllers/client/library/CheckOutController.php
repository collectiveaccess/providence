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
 			
 			$this->opo_result_context = new ResultContext($this->request, 'ca_commerce_orders', 'basic_search');
 		}
 		# -------------------------------------------------------
 		public function Index() {
 			
 			$this->view->setVar('order_items', is_array($va_items = $this->opt_order->getItems()) ? $va_items : array());
 			
 			// Set default dates for order items
 			$t_order_item = new ca_commerce_order_items();
			$t_order_item->set('loan_checkout_date', time(), array('SET_DIRECT_DATE' => true));
			if (($vn_loan_period_in_days = $this->opo_client_services_config->get('default_library_loan_period')) <= 0) {
				$vn_loan_period_in_days = 7;
			}
			$t_order_item->set('loan_due_date', time() + ($vn_loan_period_in_days * 24 * 60 * 60), array('SET_DIRECT_DATE' => true));
 			
 			$this->view->setVar('t_order_item', $t_order_item);
 			
 			//$this->view->setVar('default_item_prices', $va_default_prices);
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
 					if (($vn_user_id = $this->request->getParameter('transaction_user_id', pInteger))) {
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
				
				$va_additional_fee_codes = $this->opo_client_services_config->getAssoc('additional_order_item_fees');
				
				// Look for newly added items
				$vn_items_added = 0;
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
							}
						}
					}
				}
				
				if (!$this->opt_order->numErrors() && $vn_items_added) {
					$this->notification->addNotification(_t('Checked out %1 %2 for %3 (order %4)', $vn_items_added, ($vn_items_added == 1) ? _t('item') : _t('items'), $t_user->get('fname').' '.$t_user->get('lname'), $this->opt_order->getOrderNumber()), __NOTIFICATION_TYPE_INFO__);	
					$this->opt_order = new ca_commerce_orders();
					
					$this->request->setParameter('order_id', null);
					$this->view->setVar('t_order', $this->opt_order);
					$this->view->setVar('order_id', $this->opt_order->getPrimaryKey());
					$this->view->setVar('t_item', $this->opt_order);
				} else {
					if ($vn_items_added == 0) {
						$vs_errors = _t('No items were specified');
					} else {
						$vs_errors = join('; ', $this->opt_order->getErrors());
					}
					$va_errors['general'] = $this->opt_order->errors();
					$this->notification->addNotification(_t('Errors occurred: %1', $vs_errors), __NOTIFICATION_TYPE_ERROR__);
				}
			}
 			$this->view->setVar('errors', $va_errors);
 			
 		
 			$this->Index();
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