<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/library/OrderEditorController.php :
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

 	require_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
 	require_once(__CA_MODELS_DIR__.'/ca_commerce_orders.php');
 	require_once(__CA_MODELS_DIR__.'/ca_commerce_order_items.php');
 	require_once(__CA_APP_DIR__.'/helpers/gisHelpers.php');

 	class OrderEditorController extends ActionController {
 		# -------------------------------------------------------
 		private $opt_order;
 		private $opo_result_context;
 		private $opo_app_plugin_manager;
 		private $opo_client_services_config;
 		# -------------------------------------------------------
 		#
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
 			$this->opt_order->set('order_type', 'L');
 			
 			$this->view->setVar('t_order', $this->opt_order);
 			$this->view->setVar('order_id', $this->opt_order->getPrimaryKey());
 			$this->view->setVar('t_item', $this->opt_order);
 			
 			$this->view->setVar('client_services_config', $this->opo_client_services_config = Configuration::load($this->request->config->get('client_services_config')));
 			$this->view->setVar('currency', $this->opo_client_services_config->get('currency'));
 			$this->view->setVar('currency_symbol', $this->opo_client_services_config->get('currency_symbol'));
 			
 			$this->opo_result_context = new ResultContext($this->request, 'ca_commerce_orders', 'basic_search_library');
 		}
 		# -------------------------------------------------------
 		public function Edit() {
 			if (!$this->opt_order->getPrimaryKey()) { 
 				$this->CustomerInfo();
 			} else {
 				$this->OrderOverview();
 			}
 		}
 		# -------------------------------------------------------
 		public function OrderOverview() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			$this->view->setVar('transaction_id', ($vn_transaction_id = $this->opt_order->get('transaction_id')) ? $vn_transaction_id : $this->request->getParameter('transaction_id', pInteger));
 			$this->view->setVar('t_transaction', new ca_commerce_transactions($vn_transaction_id));
 			$this->view->setVar('currency_symbol', $this->opo_client_services_config->get('currency_symbol'));
 			$this->view->setVar('additional_fees', $this->opt_order->getAdditionalFeesHTMLFormBundle($this->request, array('config' => $this->opo_client_services_config, 'currency_symbol' => $this->opo_client_services_config->get('currency_symbol'), 'type' => 'L')));
 		
 			$this->render('order_overview_html.php');
 		}
 		# -------------------------------------------------------
 		public function CustomerInfo() {
 			$this->view->setVar('transaction_id', ($vn_transaction_id = $this->opt_order->get('transaction_id')) ? $vn_transaction_id : $this->request->getParameter('transaction_id', pInteger));
 			
 			$this->view->setVar('additional_fees', $this->opt_order->getAdditionalFeesHTMLFormBundle($this->request, array('config' => $this->opo_client_services_config, 'currency_symbol' => $this->opo_client_services_config->get('currency_symbol'), 'type' => 'L')));
 			
 			$this->render('order_customer_info_html.php');
 		}
 		# -------------------------------------------------------
 		public function AdditionalFees() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			$this->view->setVar('transaction_id', ($vn_transaction_id = $this->opt_order->get('transaction_id')) ? $vn_transaction_id : $this->request->getParameter('transaction_id', pInteger));
 			
 			$this->view->setVar('additional_fees', $this->opt_order->getAdditionalFeesHTMLFormBundle($this->request, array('config' => $this->opo_client_services_config, 'currency_symbol' => $this->opo_client_services_config->get('currency_symbol'), 'type' => 'L')));
 		
 			$this->render('order_additional_fees_html.php');
 		}
 		# -------------------------------------------------------
 		public function Shipping() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$this->render('order_shipping_html.php');
 		}
 		# -------------------------------------------------------
 		public function Payment() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			$va_months = array();
 			$o_tep = new TimeExpressionParser();
 			foreach($o_tep->getMonthList() as $vn_m => $vs_month) {
 				$va_months[$vs_month] = $vn_m + 1;
 			}
 			$this->view->setVar('credit_card_exp_month_list', $va_months);
 			
 			$va_tmp = getDate(); $vn_current_year = $va_tmp['year'];
 			$vn_i = 0;
 			$va_credit_card_exp_year_list = array();
 			while($vn_i < 8) {
 				$va_credit_card_exp_year_list[$vn_current_year + $vn_i] = $vn_current_year + $vn_i;
 				$vn_i++;
 			}
 			$this->view->setVar('credit_card_exp_year_list', $va_credit_card_exp_year_list);
 			
 			
 			$this->render('order_payment_html.php');
 		}
 		# -------------------------------------------------------
 		public function ItemList() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$va_items = $this->opt_order->getItems();
 			foreach($va_items as $vn_item_id => $va_item) {
 				$va_items[$vn_item_id]['loan_checkout_date'] = $va_items[$vn_item_id]['loan_checkout_date'] ? caGetLocalizedDate($va_items[$vn_item_id]['loan_checkout_date'], array('dateFormat' => 'delimited')) : '';
 				$va_items[$vn_item_id]['loan_due_date'] = $va_items[$vn_item_id]['loan_due_date'] ? caGetLocalizedDate($va_items[$vn_item_id]['loan_due_date'], array('dateFormat' => 'delimited')) : '';
 				$va_items[$vn_item_id]['loan_return_date'] = $va_items[$vn_item_id]['loan_return_date'] ? caGetLocalizedDate($va_items[$vn_item_id]['loan_return_date'], array('dateFormat' => 'delimited')) : '';
 			}
 	
 			$this->view->setVar('order_items', $va_items);
 			
 			$va_service_groups = $this->opo_client_services_config->getAssoc('service_groups');
 			$va_default_prices = array();
 			foreach($va_service_groups as $vs_group => $va_group_info) {
 				foreach($va_group_info['services'] as $vs_service => $va_service_info) {
 					$va_default_prices[$vs_service] = $va_service_info;
 				}
 			}
 			
 			$this->view->setVar('default_item_prices', $va_default_prices);
 			
 			if (($vn_loan_period_in_days = $this->opo_client_services_config->get('default_library_loan_period')) <= 0) {
				$vn_loan_period_in_days = 7;
			}
 			$t_order_item = new ca_commerce_order_items();
			$t_order_item->set('loan_checkout_date', time(), array('SET_DIRECT_DATE' => true));
			$t_order_item->set('loan_due_date', time() + ($vn_loan_period_in_days * 24 * 60 * 60), array('SET_DIRECT_DATE' => true));
			
			$va_default_values = array('loan_checkout_date' => $t_order_item->get('loan_checkout_date', array('dateFormat' => 'delimited', 'timeOmit' => true)), 'loan_due_date' => $t_order_item->get('loan_due_date', array('dateFormat' => 'delimited', 'timeOmit' => true)));
 			
 			$this->view->setVar('t_order_item', $t_order_item);
 			$this->view->setVar('additional_fees', $t_order_item->getAdditionalFeesHTMLFormBundle($this->request, array('config' => $this->opo_client_services_config, 'currency_symbol' => $this->opo_client_services_config->get('currency_symbol'), 'type' => 'L')));
 			$this->view->setVar('additional_fees_for_new_items', $t_order_item->getAdditionalFeesHTMLFormBundle($this->request, array('config' => $this->opo_client_services_config, 'currency_symbol' => $this->opo_client_services_config->get('currency_symbol'), 'type' => 'L')));	
 			
 			$this->view->setVar('additional_fee_codes', $va_additional_fee_codes = $this->opo_client_services_config->getAssoc('additional_loan_fees'));
 			
 			foreach($va_additional_fee_codes as $vs_code => $va_info) {
 				$va_default_values['ADDITIONAL_FEE_'.$vs_code] = $va_info['default_cost'];
 			}
 			
 			$this->view->setVar('default_values', $va_default_values);
			
 			$this->render('order_item_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Communications() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$this->view->setVar('transaction_id', ($vn_transaction_id = $this->opt_order->get('transaction_id')) ? $vn_transaction_id : $this->request->getParameter('transaction_id', pInteger));
 			$this->view->setVar('t_transaction', $t_transaction = new ca_commerce_transactions($vn_transaction_id));
 			
 			$this->view->setVar('messages', $t_transaction->getMessages());
 			$this->render('order_communications_html.php');
 		}
 		# -------------------------------------------------------
 		public function FulfillmentEvents() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$this->view->setVar('log', $this->opt_order->getFulfillmentLog());
 			$this->render('order_fulfillment_events_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function ReturnQuoteToUser() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$this->opt_order->setMode(ACCESS_WRITE);
 			$this->opt_order->set('order_status', 'AWAITING_PAYMENT');
 			$this->opt_order->update();
 			
 			$this->OrderOverview();
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
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
 					case 'order_type':
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
 			$va_fees = $this->opo_client_services_config->getAssoc('additional_loan_fees');
 	
 			if (is_array($va_fees)) {
 				if (!is_array($va_fee_values = $this->opt_order->get('additional_fees'))) { $va_fee_values = array(); }
 				foreach($va_fees as $vs_code => $va_info) {
 					$va_fee_values[$vs_code] = ((float)$this->request->getParameter("additional_fee_{$vs_code}", pString));
 				}
 				$this->opt_order->set('additional_fees', $va_fee_values);
 			}
 			
 			$this->opt_order->setMode(ACCESS_WRITE);
 			if ($this->opt_order->getPrimaryKey()) {
 				$this->opt_order->set('order_type', 'L');	// L=loan
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
					}
 				}
 				$this->opt_order->set('transaction_id', $vn_transaction_id);
 				$this->opt_order->set('order_type', 'L');	// L=loan
 					
 				$this->opt_order->insert();
 				$this->request->setParameter('order_id', $this->opt_order->getPrimaryKey());
 			}
 			
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
 			
 			if (!$this->opt_order->numErrors()) {
 				$this->notification->addNotification(_t('Saved changes'), __NOTIFICATION_TYPE_INFO__);	
 			} else {
 				$va_errors['general'] = $this->opt_order->errors();
 				$this->notification->addNotification(_t('Errors occurred: %1', join('; ', $this->opt_order->getErrors())), __NOTIFICATION_TYPE_ERROR__);
 			}
 			$this->view->setVar('errors', $va_errors);
 		}
 		# -------------------------------------------------------
 		public function SaveOrderOverview() {
 			$this->Save();
 			$this->OrderOverview();
 		}
 		# -------------------------------------------------------
 		public function SaveCustomerInfo() {
 			$this->Save();
 			$this->CustomerInfo();
 		}
 		# -------------------------------------------------------
 		public function SaveAdditionalFees() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			$this->Save();
 			$this->AdditionalFees();
 		}
 		# -------------------------------------------------------
 		public function SaveShipping() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			$this->Save();
 			$this->Shipping();
 		}
 		# -------------------------------------------------------
 		public function SaveCommunications() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$pn_transaction_id = $this->request->getParameter('transaction_id', pInteger);
 			$t_trans = new ca_commerce_transactions($pn_transaction_id);
 			
 			$this->view->setVar('communication_id', $pn_communication_id);
 			$this->view->setVar('transaction_id', $pn_transaction_id);
 			
 			if ($t_trans->haveAccessToTransaction($this->request->getUserID())) {
 				if($this->request->getParameter('message', pString)){
					if ($t_trans->sendInstitutionMessage('L', $this->request->getParameter('subject', pString), $this->request->getParameter('message', pString), $this->request->getUserID())) {	
						$this->notification->addNotification(_t('Message has been sent'), __NOTIFICATION_TYPE_INFO__);
					} else {
						$this->notification->addNotification(_t('Errors occurred when sending message: %1', join('; ', $t_trans->getErrors())), __NOTIFICATION_TYPE_ERROR__);
					}
				}else{
					$this->notification->addNotification(_t('There were errors sending your message: You must enter message text.'), __NOTIFICATION_TYPE_ERROR__);
				}
 			} else {
 				$this->notification->addNotification(_t('You do not have access to this transaction'), __NOTIFICATION_TYPE_ERROR__);
 			}
 			$this->Communications();
 		}
 		# -------------------------------------------------------
 		public function SavePayment() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$va_errors = array();
 			
 			if ($this->opt_order->get('payment_received_on')) {
 				$this->notification->addNotification(_t('Order is already paid for'), __NOTIFICATION_TYPE_ERROR__);
 			} else {
				// Set payment intrinsics
				foreach(array('payment_method', 'payment_status', 'payment_received_on') as $vs_f) { 				
					if (isset($_REQUEST[$vs_f])) {
						if (!$this->opt_order->set($vs_f, $this->request->getParameter($vs_f, pString))) {
							$va_errors[$vs_f] = $this->opt_order->errors();
						}
					}
				}
				$this->opt_order->setMode(ACCESS_WRITE);
				
				// Set payment-type specific info
				$this->opt_order->setPaymentInfo($_REQUEST);
				if ($this->opt_order->numErrors()) {
					$va_errors['payment_info'] = $this->opt_order->getErrors();
					$this->notification->addNotification(_t('Errors occurred: %1', join('; ', $this->opt_order->getErrors())), __NOTIFICATION_TYPE_ERROR__);
				} else {
					$this->opt_order->update();
					if ($this->opt_order->numErrors()) {
						$va_errors['general'] = $this->opt_order->errors();
						$this->notification->addNotification(_t('Errors occurred: %1', join('; ', $this->opt_order->getErrors())), __NOTIFICATION_TYPE_ERROR__);
					} else {
						if ($this->opt_order->get('payment_received_on')) {
							$this->notification->addNotification(_t('Payment was recorded'), __NOTIFICATION_TYPE_INFO__);	
						} else {
							$this->notification->addNotification(_t('Saved changes'), __NOTIFICATION_TYPE_INFO__);	
						}
					}
				}
 				$this->view->setVar('errors', $va_errors);
			}
 			
 			$this->Payment();
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function SaveItemList() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$va_additional_fee_codes = $this->opo_client_services_config->getAssoc('additional_loan_fees');
 			
 			$va_errors = array();
 			$va_failed_insert_list = array();
 			
 			// Look for newly added items
 			$va_seen_items = array();
 			foreach($_REQUEST as $vs_k => $vs_v) {
 				if(preg_match("!^item_list_idnew_([\d]+)$!", $vs_k, $va_matches)) {
 					$va_seen_items[$vs_k] = true;
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
 						if ($this->opt_order->numErrors() > 0) {
							foreach($this->opt_order->errors() as $o_error) {
								$va_errors['new_0'][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
							}
							
							$va_fee_values_proc = array();
							foreach($va_fee_values as $vs_k => $vs_v) {
								$va_fee_values_proc['ADDITIONAL_FEE_'.$vs_k] = $vs_v;
							}
							
							$va_failed_insert_list[] = array_merge(
								$va_values,
								$va_fee_values_proc,
								array(
									'autocomplete' => $_REQUEST['item_list_autocompletenew_'.$va_matches[1]],
									'id' => $vn_object_id
								)
							);
							
							$this->opt_order->clearErrors();
						}
 					}
 				}
 			}
 			
 			// Look for edited items
 			foreach($_REQUEST as $vs_k => $vs_v) {
 				if(preg_match("!^item_list_id([\d]+)$!", $vs_k, $va_matches)) {
 					if (isset($va_seen_items[$vs_k]) && $va_seen_items[$vs_k]) { continue; }
 					if ($vn_item_id = (int)$va_matches[1]) {
 						$va_values = array();
 						foreach($_REQUEST as $vs_f => $vs_value) {
 							if (preg_match("!new_[\d]+$!", $vs_f)) { continue; }
 							if(preg_match("!^item_list_([A-Za-z0-9_]+)_".$vn_item_id."$!", $vs_f, $va_matches2)) {
 								$va_values[$va_matches2[1]] = $vs_value;
 							}
 						}
 						
 						// Set additional fees
 						//
 						$va_fee_values = array();
 						foreach($va_additional_fee_codes as $vs_code => $va_info) {
 							$va_fee_values[$vs_code] = $_REQUEST['additional_order_item_fee_'.$vs_code.'_'.$vn_item_id];
 						}
 						$t_item = $this->opt_order->editItem($vn_item_id, $va_values, array('additional_fees' => $va_fee_values));
 						
						if ($this->opt_order->numErrors()) {
							foreach($this->opt_order->errors() as $o_error) {
								$va_errors[$vn_item_id][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
							}
							$this->opt_order->clearErrors();
						}
 					}
 				}
 			}
 			
 			// Look for deleted items
 			foreach($_REQUEST as $vs_k => $vs_v) {
 				if(preg_match("!^item_list_([\d]+)_delete$!", $vs_k, $va_matches)) {
 					if ($vn_item_id = (int)$va_matches[1]) {
 						// delete item from order
 						$t_item = $this->opt_order->removeItem($vn_item_id, array());
 					}
 				}
 			}
 			
 			if (sizeof($va_errors)) {
 				$this->notification->addNotification(_t("There are errors preventing information in specific fields from being saved as noted below."), __NOTIFICATION_TYPE_ERROR__);
 			}
 			$this->view->setVar('errors', $va_errors);
 			$this->view->setVar('failed_insert_list', $va_failed_insert_list);
 			
 			// reorder items?
 			$this->opt_order->reorderItems(explode(';', $this->request->getParameter('item_listBundleList', pString)));
 			
 			$this->ItemList();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Performs two-step delete of an existing record. The first step is a confirmation dialog, followed by actual deletion upon user confirmation
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function Delete($pa_options=null) {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			
 			$vn_order_id = $this->opt_order->getPrimaryKey();
 			if (!$vn_order_id) { return; }
 			
 			if ($vn_order_id && !$this->opt_order->getPrimaryKey()) {
 				$this->notification->addNotification(_t("Order does not exist"), __NOTIFICATION_TYPE_ERROR__);	
 				return;
 			}
 			
 			if ($vb_confirm = ($this->request->getParameter('confirm', pInteger) == 1) ? true : false) {
 				$vb_we_set_transation = false;
 				if (!$this->opt_order->inTransaction()) { 
 					$this->opt_order->setTransaction($o_t = new Transaction());
 					$vb_we_set_transation = true;
 				}
 				
 				$this->opt_order->setMode(ACCESS_WRITE);
 				$this->opt_order->delete(true);
 				if ($vb_we_set_transation) {
 					if ($this->numErrors() > 0) {
 						$o_t->rollbackTransaction();	
 					} else {
 						$o_t->commitTransaction();
 					}
 				}
 			}
 			$this->view->setVar('confirmed', $vb_confirm);
 			if ($this->opt_order->numErrors()) {
 				foreach($this->opt_order->errors() as $o_e) {
 					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);	
 				}
 			} else {
 				if ($vb_confirm) {
 					$this->notification->addNotification(_t("Order was deleted"), __NOTIFICATION_TYPE_INFO__);
 					
 					// update result list since it has changed
 					$this->opo_result_context->removeIDFromResults($vn_order_id);
 					$this->opo_result_context->invalidateCache();
  					$this->opo_result_context->saveContext();
  				
  				
 					// clear order_id - it's no longer valid
 					$this->opt_order->clear();
 					$this->view->setVar($this->opt_order->primaryKey(), null);
 					$this->request->setParameter($this->opt_order->primaryKey(), null, 'PATH');
 					
					// Clear out row_id so sidenav is disabled
					$this->request->setParameter($this->opt_order->primaryKey(), null, 'POST');

					# trigger "DeleteItem" hook 
					$this->opo_app_plugin_manager->hookDeleteItem(array('id' => $vn_order_id, 'table_num' => $this->opt_order->tableNum(), 'table_name' => $this->opt_order->tableName(), 'instance' => $this->opt_order));
 				}
 			}
 			
 			$this->render('order_delete_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns change log display for currently edited record in current view inherited from ActionController
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function Log($pa_options=null) {
 			JavascriptLoadManager::register('tableList');
 			
 			$this->render('order_log_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function CreateNewOrderFromCommunication() {
 			if ($pn_communication_id = $this->request->getParameter('communication_id', pInteger)) {
 				$t_comm = new ca_commerce_communications($pn_communication_id);
 				if (!$t_comm->getPrimaryKey()) {
 					$this->notification->addNotification(_t('Invalid message'), __NOTIFICATION_TYPE_ERROR__);
 					$this->CustomerInfo();
 					return;
 				}
 				$t_trans = new ca_commerce_transactions($t_comm->get('transaction_id'));
 				if (!$t_trans->getPrimaryKey()) {
 					$this->notification->addNotification(_t('Message is not associated with a transaction'), __NOTIFICATION_TYPE_ERROR__);
 					$this->CustomerInfo();
 					return;
 				}
 				$t_user = new ca_users($t_trans->get('user_id'));
 				
 				$this->opt_order->setMode(ACCESS_WRITE);
 				$this->opt_order->set('transaction_id', $t_trans->getPrimaryKey());
 				if ($t_user->getPrimaryKey()) {
					$this->opt_order->set('billing_fname', $t_user->get('fname'));
					$this->opt_order->set('billing_lname', $t_user->get('lname'));
					$this->opt_order->set('billing_email', $t_user->get('email'));
					$this->opt_order->set('shipping_fname', $t_user->get('fname'));
					$this->opt_order->set('shipping_lname', $t_user->get('lname'));
					$this->opt_order->set('shipping_email', $t_user->get('email'));
					
					// Pre-populate order with user's profile address
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
					foreach($va_mapping as $vs_field => $vs_pref) {
						$this->opt_order->set($vs_field, $t_user->getPreference($vs_pref));
					}
					
				}
				
				$this->opt_order->set('order_type', 'L');	// L=loan
 				$this->opt_order->insert();
 				
 				$this->request->setParameter('order_id', $this->opt_order->getPrimaryKey());
 				
 				if (!$this->opt_order->numErrors()) {
					$this->notification->addNotification(_t('Saved changes'), __NOTIFICATION_TYPE_INFO__);	
					
					// Add items
 					$t_set = new ca_sets($t_trans->get('set_id'));
 					if ($t_set->getPrimaryKey()) {
						$va_items = $t_set->getItems();
						foreach($va_items as $va_item_list) {
							foreach($va_item_list as $vn_i => $va_item) {
								if (!is_array($va_item['selected_services'])) {
									//$va_item['selected_services'] = array('DIGITAL_COPY');	// TODO: make default configurable
								}
								foreach($va_item['selected_services'] as $vs_service) {
									if ($t_item = $this->opt_order->addItem($va_item['row_id'], array('service' => $vs_service), array('representations_ids' => (is_array($va_item['selected_representations']) && sizeof($va_item['selected_representations'])) ? $va_item['selected_representations'] : null))) {
										
										$t_item->updateFee();
									}
								}
							}
						}
						
						// Delete originating set if configured to do so
						if($this->opo_client_services_config->get('set_disposal_policy') == 'DELETE_WHEN_ORDER_CREATED') {
							$t_set->setMode(ACCESS_WRITE);
							$t_set->delete(true);
						}
					}
				} else {
					$va_errors['general'] = $this->opt_order->errors();
					$this->notification->addNotification(_t('Errors occurred: %1', join('; ', $this->opt_order->getErrors())), __NOTIFICATION_TYPE_ERROR__);
				}
				$this->view->setVar('errors', $va_errors);
				
 			}
			$this->OrderOverview();
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$this->view->setVar('result_context', $this->opo_result_context);
 			return $this->render('widget_loan_info_html.php', true);
 		}
 		# -------------------------------------------------------
 		/**
 		 * Return user profile values for specified user
 		 */
 		public function GetUserProfileInfo() {
 			if (!$this->request->user->canDoAction('can_manage_clients')) { return null; }
 			$pn_user_id = $this->request->getParameter('user_id', pInteger);
 			
 			$t_user = new ca_users($pn_user_id);
 			$va_profile_prefs = $t_user->getValidPreferences('profile');
 			if (is_array($va_profile_prefs) && sizeof($va_profile_prefs)) {
 				$va_elements = array();
				foreach($va_profile_prefs as $vs_pref) {
					$va_pref_info = $t_user->getPreferenceInfo($vs_pref);
					$va_elements[str_replace('user_profile_', '', $vs_pref)] = array($t_user->getPreference($vs_pref));
				}
				
				$this->view->setVar("profile_values", $va_elements);
			}
 			
 			$this->view->setVar("user_id", $pn_user_id);
 			$this->render('ajax_user_profile_info_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function GetChecklist() {
 			if (!$this->opt_order->getPrimaryKey()) { $this->Edit(); return; }
 			require_once(__CA_LIB_DIR__."/core/Print/html2pdf/html2pdf.class.php");
			
			$this->view->setVar('items', $this->opt_order->getItems());
				
			try {
				$vs_content = $this->render('loan_pdf_checklist_html.php');
				$vo_html2pdf = new HTML2PDF('L','letter','en');
				$vo_html2pdf->setDefaultFont("dejavusans");
				$vo_html2pdf->setTestIsImage(false);
				$vo_html2pdf->WriteHTML($vs_content);
				
	header("Content-Disposition: attachment; filename=loan_checklist.pdf");
	header("Content-type: application/pdf");
	
				$vo_html2pdf->Output('loan_checklist.pdf');
				$vb_printed_properly = true;
			} catch (Exception $e) {
				$vb_printed_properly = false;
				$this->postError(3100, _t("Could not generate PDF"),"OrderEditorController->GetCheckList()");
			}
		}
 		# -------------------------------------------------------
 	}
 ?>