<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/InformationServiceController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 	require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");
 	require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
 	require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
 	require_once(__CA_LIB_DIR__.'/core/InformationServiceManager.php');
 	
 
 	class InformationServiceController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
 		/**
 		 * Perform lookup on a remote data service and return matched values
 		 *
 		 * @param array $pa_additional_query_params
 		 * @param array $pa_options
 		 */
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			$o_config = Configuration::load();
			
			if (!($ps_query = $this->request->getParameter('q', pString))) {
				$ps_query =		$this->request->getParameter('term', pString);
			}
			$ps_type = 			$this->request->getParameter('type', pString);
			$pn_element_id = 	$this->request->getParameter('element_id', pInteger);
			$t_element = 		new ca_metadata_elements($pn_element_id);
			
			if (!$t_element->getPrimaryKey()) { 
				// error
				$va_items['error'] = array('label' => _t('ERROR: Invalid element_id'), 'idno' => '');
			} else {
			
				$vs_service = $t_element->getSetting('service');
			
				$va_items = array();
				if (unicode_strlen($ps_query) >= 3) {
					try {
						// Load plugin and connect to information service
						if (!($o_plugin = InformationServiceManager::getInformationServiceInstance($vs_service))) {
							$va_items['error'] = array('label' => _t('ERROR: Invalid service'), 'idno' => '');
						} else {
							$va_data = $o_plugin->lookup($t_element->getSettings(), $ps_query, array('element_id' => $pn_element_id));
					
							if ($va_data && isset($va_data['results']) && is_array($va_data['results'])) {
								foreach($va_data['results'] as $va_result) {
									$va_items[] = array('label' => (string)$va_result['label'], 'idno' => (string)$va_result['idno'], 'url' => (string)$va_result['url']);
								}
							}
						}
					} catch (Exception $e) {
						$va_items['error'] = array('label' => _t('ERROR').': '.$e->getMessage(), 'idno' => '');
					}
				}
			}
			
			$this->view->setVar('information_service_list', $va_items);
 			return $this->render('ajax_information_service_list_html.php');
		}
 		# -------------------------------------------------------
 		/**
 		 * Fetch details on an item from a remote data source and output results of the 'display' key in the response.
 		 *
 		 */
		public function GetDetail() {
			$pn_element_id = $this->request->getParameter('element_id', pInteger);
			$t_element = new ca_metadata_elements($pn_element_id);
			$va_data = array();
			if (!$t_element->getPrimaryKey()) { 
				// error
				$va_items['error'] = array('label' => _t('ERROR: Invalid element_id'), 'idno' => '');
			} else {
				$vs_service = $t_element->getSetting('service');
				$va_settings = $t_element->getSettings();
				
				$pn_attribute_id = $this->request->getParameter('id', pInteger);
				
				$t_attr_val = new ca_attribute_values();
				if ($t_attr_val->load(array('attribute_id' => $pn_attribute_id, 'element_id' => $pn_element_id))) {
					$t_attr = new ca_attributes();
					if ($t_attr->load($pn_attribute_id)) {
						if (!caCanRead($this->request->getUserID(), $t_attr->get('table_num'), $t_attr->get('row_id'), $t_element->get('element_code'))) {
							$va_items['error'] = array('label' => _t('ERROR: You do not have access to this item'), 'idno' => '');
						} else {			
							$vs_url = $t_attr_val->get('value_longtext2');
							if (!($o_plugin = InformationServiceManager::getInformationServiceInstance($vs_service))) {
								$va_items['error'] = array('label' => _t('ERROR: Invalid service'), 'idno' => '');
							} else {
								$va_data = $o_plugin->getExtendedInformation($va_settings, $vs_url);
							}
						}
					}
				}
			}
			
			$this->view->setVar('detail', $va_data);
			return $this->render('ajax_information_service_detail_html.php');
		}
		# -------------------------------------------------------
 	}
 ?>