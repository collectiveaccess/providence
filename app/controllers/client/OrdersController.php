<?php
/* ----------------------------------------------------------------------
 * app/controllers/logs/OrdersController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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

 	class OrdersController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		private $opo_client_services_config;
 		# -------------------------------------------------------
 		public function Index() {
 			JavascriptLoadManager::register('tableList');
			
			$o_result_context = new ResultContext($this->request, 'ca_commerce_orders', 'basic_search');
			
 			$va_options = array();
 			$t_order = new ca_commerce_orders();
 			
 			// Set drop-down fields to be null-able here so we don't get them auto-setting to their defaults
 			$t_order->setFieldAttribute('order_status', 'IS_NULL', true);
 			$t_order->setFieldAttribute('shipping_method', 'IS_NULL', true);
 			
 			// filtering options
 			foreach(array('order_status' => 'string', 'created_on' => 'string', 'shipping_date' => 'string', 'shipping_method' => 'string', 'shipped_on_date' => 'string', 'search' => 'string', 'shipping_method' => 'string', 'transaction_id' => 'int') as $vs_f => $vs_type) {
				if (array_key_exists($vs_f, $_REQUEST)) {
					$vm_v = $this->request->getParameter($vs_f, pString);
					$o_result_context->setParameter('caClientOrderList_'.$vs_f, $vm_v);
				} else {
					$vm_v = $o_result_context->getParameter('caClientOrderList_'.$vs_f);
				}
				
				// transaction_id may appear in GET requests since the communication editor links to this page with transaction_id as a param
				if ($vs_f == 'transaction_id') { $vm_v = $this->request->getParameter($vs_f, pString);}
				
				switch($vs_type) {
					case 'int':
						if (strlen($vm_v)) {
							$vm_v = (int)$vm_v;
						}
						break;
				}
				if ($vs_f != 'search') { $t_order->set($vs_f, $vm_v); }
				$va_options[$vs_f] = $vm_v;
			}
 			$this->view->setVar('t_order', $t_order);
 			$this->view->setVar('filter_options', $va_options);
 			$this->view->setVar('order_list', $va_order_list = $t_order->getOrders($va_options));
 	
 			$va_order_ids = array();
 			foreach($va_order_list as $vn_i => $va_order) {
 				$va_order_ids[] = $va_order['order_id'];
 			}
 			
			$o_result_context->setResultList($va_order_ids);
			$o_result_context->setAsLastFind();
			$o_result_context->saveContext();
 			
 			
 			$this->view->setVar('client_services_config', $this->opo_client_services_config = Configuration::load($this->request->config->get('client_services_config')));
 			$this->view->setVar('currency', $this->opo_client_services_config->get('currency'));
 			$this->view->setVar('currency_symbol', $this->opo_client_services_config->get('currency_symbol'));
 			
 			$this->render('list_orders_html.php');
 		}
 		# -------------------------------------------------------
 		public function ViewOrder() {
 			JavascriptLoadManager::register('tableList');
 			
 			$this->render('view_orders_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_order = new ca_commerce_orders();
 			$this->view->setVar('order_list', $va_order_list = $t_order->getOrders($va_options));
 			return $this->render('widget_orders_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>