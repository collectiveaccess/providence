<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/library/CheckInController.php :
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

	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectCheckoutSearch.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_checkouts.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

 	class CheckInController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		private $opo_client_services_config;
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkin')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			AssetLoadManager::register('objectcheckin');
 			
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Begin checkout process with user select
 		 */
 		public function Index() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkin')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$this->render('checkin/items_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Return info via ajax on selected object
 		 */
 		public function GetObjectInfo() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkin')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$pn_checkout_id = $this->request->getParameter('checkout_id', pInteger);
 			
 			$t_checkout = new ca_object_checkouts($pn_checkout_id);
 			$t_object = new ca_objects($t_checkout->get('object_id'));
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
 			$this->render('checkin/ajax_data_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function SaveTransaction() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkin')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$ps_item_list = $this->request->getParameter('item_list', pString);
 			$pa_item_list = json_decode($ps_item_list, true);
 			
 			$t_checkout = new ca_object_checkouts();
 			
 			$va_ret = array('status' => 'OK', 'errors' => array(), 'checkins' => array());
 			foreach($pa_item_list as $vn_i => $va_item) {
 				if ($t_checkout->load($va_item['checkout_id'])) {
					$vn_object_id = $t_checkout->get('object_id');
					$t_object = new ca_objects($vn_object_id);
					if ($t_checkout->isOut()) { 
						try {
							$t_checkout->checkin($vn_object_id, $va_item['note']);
						
							if ($t_checkout->numErrors() == 0) {
								$va_ret['checkins'][] = _t('Returned %1', $t_object->get('ca_objects.preferred_labels.name'));
							} else {
								$va_ret['errors'][] = _t('Could not check in %1: %2', $t_object->get('ca_objects.preferred_labels.name'), join("; ", $t_checkout->getErrors()));
							}
						} catch (Exception $e) {
							$va_ret['errors'][] = _t('%1 is not out', $t_object->get('ca_objects.preferred_labels.name'));
						}
					} else {
						$va_ret['errors'][] = _t('%1 is not out', $t_object->get('ca_objects.preferred_labels.name'));
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
 			return $this->render('checkin/widget_checkin_html.php', true);
 		}
 		# -------------------------------------------------------
 	}