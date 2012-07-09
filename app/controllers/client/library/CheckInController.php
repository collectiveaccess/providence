<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/library/CheckInController.php :
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
	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectSearch.php');

 	class CheckInController extends ActionController {
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
 			
 			$this->view->setVar('client_services_config', $this->opo_client_services_config = Configuration::load($this->request->config->get('client_services_config')));
 			$this->view->setVar('currency', $this->opo_client_services_config->get('currency'));
 			$this->view->setVar('currency_symbol', $this->opo_client_services_config->get('currency_symbol'));
 			
 			$this->opo_result_context = new ResultContext($this->request, 'ca_commerce_orders', 'basic_search_library');
 		}
 		# -------------------------------------------------------
 		public function Index() {
 			
 			
 			$this->render('checkin_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function getItemInfo() {
 			$ps_search = str_replace('"', '', $this->request->getParameter('search', pString));
 			$va_values = array();
				
 			$t_item = new ca_commerce_order_items();
 			$o_search = new ObjectSearch();
 			$qr_res = $o_search->search("ca_objects.idno:\"{$ps_search}\"");
			if (!$qr_res->numHits()) {
				$qr_res = $o_search->search($ps_search);
			}
			
			$va_object_ids = array();
			while($qr_res->nextHit()) {
				$va_object_ids[] = (int)$qr_res->get('ca_objects.object_id'); 
			}
			
			$va_items = array('search' => $ps_search, 'matches' => array());
			if (sizeof($va_object_ids)) {
				$o_db = new Db();
				$qr_items = $o_db->query("
					SELECT i.item_id, o.order_id
					FROM ca_commerce_order_items i
					INNER JOIN ca_commerce_orders AS o ON o.order_id = i.order_id
					WHERE
						object_id IN (?) AND o.order_type = 'L' AND i.loan_return_date IS NULL
				", array($va_object_ids));
				
				while($qr_items->nextRow()) {
					$t_item = new ca_commerce_order_items($qr_items->get('item_id'));
					$t_order = $t_item->getOrder();
					$va_values = $t_item->getFieldValuesArray();
					$va_values['user'] = $t_order->getOrderTransactionUserName();
					
					// get object label
					$t_object = $t_item->getItemObject();
					$va_values['object'] = $t_object->get('ca_objects.preferred_labels.name');
					$va_values['idno'] = $t_object->get('ca_objects.idno');
					
					// generate display dates
					$va_values['loan_checkout_date_raw'] = $va_values['loan_checkout_date'];
					$va_values['loan_checkout_date'] = caGetLocalizedDate($va_values['loan_checkout_date'], array('dateFormat' => 'delimited', 'timeOmit' => true));
					$va_values['loan_due_date_raw'] = $va_values['loan_due_date'];
					$va_values['loan_due_date'] = caGetLocalizedDate($va_values['loan_due_date'], array('dateFormat' => 'delimited', 'timeOmit' => true));
					if ($va_values['loan_due_date_raw'] < time()) {
						$va_values['loan_due_date'] .= " (<em>"._t("Overdue by %1", caFormatInterval(time() - $va_values['loan_due_date_raw'], 2))."</em>)";
					}
					
					$va_values['order_number'] = $t_order->getOrderNumber();
					
					$va_rep = $t_object->getPrimaryRepresentation(array('thumbnail'));
					$va_values['thumbnail_tag'] = $va_rep['tags']['thumbnail'];
					
					
					$va_items['matches'][] = $va_values;
				}
			}
 			$this->view->setVar('items', $va_items);
 			return $this->render('ajax_order_item_info_json.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {	
 			$vn_checkin_count = 0;
 			foreach($_REQUEST as $vs_k => $vs_v) {
 				if (preg_match('!^caClientLibraryCheckin_item_id_([\d]+)$!', $vs_k, $va_matches)) {
 					if (isset($_REQUEST['caClientLibraryCheckin_'.$va_matches[1].'_delete'])) { continue; }
 					$vn_item_id = $va_matches[1];
 					
 					$t_order_item = new ca_commerce_order_items($vn_item_id);
 					if ($t_order_item->getPrimaryKey()) {
 						$t_order_item->setMode(ACCESS_WRITE);
 						$t_order_item->set('loan_return_date', time(), array('SET_DIRECT_DATE' => true));
 						$t_order_item->set('notes', $this->request->getParameter('caClientLibraryCheckin_notes_'.$vn_item_id, pString));
 						$t_order_item->update();
 						
 						if ($t_order_item->numErrors()) {
 							$this->notification->addNotification(_t('Could not check in item %1: %2', $vn_item_id, join("; ", $t_order_item->getErrors())), __NOTIFICATION_TYPE_ERROR__);	
 							continue;
 						}
 						$vn_checkin_count++;
 					}
 				}
 			}
 			$this->notification->addNotification(($vn_checkin_count == 1) ? _t('Checked in %1 item', $vn_checkin_count) : _t('Checked in %1 items', $vn_checkin_count), __NOTIFICATION_TYPE_INFO__);	
			
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_order = new ca_commerce_orders();
 			$this->view->setVar('order_list', $va_order_list = $t_order->getOrders($va_options));
 			return $this->render('widget_checkin_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>