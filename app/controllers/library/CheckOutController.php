<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/library/CheckOutController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 	require_once(__CA_MODELS_DIR__.'/ca_object_checkouts.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

 	class CheckOutController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		private $opo_client_services_config;
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout')) { 
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
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->render('checkout/find_user_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Checkout items screen
 		 */
 		public function Items() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
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
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$pn_user_id = $this->request->getParameter('user_id', pInteger);
 			$pn_object_id = $this->request->getParameter('object_id', pInteger);
 			
 			$t_object = new ca_objects($pn_object_id);
 			
 			$va_status = $t_object->getCheckoutStatus();
			$va_checkout_config = ca_object_checkouts::getObjectCheckoutConfigForType($t_object->getTypeCode());
 			
 			$va_info = array(
 				'object_id' => $t_object->getPrimaryKey(),
 				'idno' => $t_object->get('idno'),
 				'name' => $t_object->get('ca_objects.preferred_labels.name'),
 				'media' => $t_object->get('ca_object_representations.media.icon'),
 				'status' => $t_object->getCheckoutStatus(),
 				'status_display' => $t_object->getCheckoutStatus(array('returnAsText' => true)),
 				'config' => $va_checkout_config
 			);
 			
 			$this->view->setVar('data', $va_info);
 			$this->render('checkout/ajax_data_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Return info via ajax on selected object
 		 */
 		public function SaveTransaction() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$pn_user_id = $this->request->getParameter('user_id', pInteger);
 			$ps_item_list = $this->request->getParameter('item_list', pString);
 			
 			$pa_item_list = json_decode($ps_item_list, true);
 			
 			$t_checkout = ca_object_checkouts::newCheckoutTransaction();
 			$va_ret = array('status' => 'OK', 'total' => sizeof($pa_item_list), 'errors' => array(), 'checkouts' => array());
 			foreach($pa_item_list as $vn_i => $va_item) {
 				try {
 					$vn_checkout_id = $t_checkout->checkout($va_item['object_id'], $pn_user_id, $va_item['note'], $va_item['due_date']);
 				
					if ($vn_checkout_id > 0) {
						$va_ret['checkouts'][$va_item['object_id']] = _t('Checked out %1; due date is %2', $va_item['object_id'], $va_item['due_date']);
					} else {
						$va_ret['errors'][$va_item['object_id']] = _t('Could not check out %1: %2', $va_item['object_id'], join('; ', $t_checkout->getErrors()));
					}
				} catch (Exception $e) {
					$va_ret['errors'][$va_item['object_id']] = $e->getMessage();
				}
 			}
 			
 			$this->view->setVar('data', $va_ret);
 			$this->render('checkout/ajax_data_json.php');
 		}
 		# -------------------------------------------------------
 	}