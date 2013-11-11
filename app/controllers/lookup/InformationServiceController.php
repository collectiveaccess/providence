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
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			$o_config = Configuration::load();
			
			if (!($ps_query = $this->request->getParameter('q', pString))) {
				$ps_query = $this->request->getParameter('term', pString);
			}
			$ps_type = $this->request->getParameter('type', pString);
			$pn_element_id = $this->request->getParameter('element_id', pInteger);
			$t_element = new ca_metadata_elements($pn_element_id);
			
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
 		 *
 		 */
		public function GetDetail() {
			$pn_attribute_value_id = $this->request->getParameter('id', pInteger);
			$t_value = new ca_attribute_values($pn_attribute_value_id);
			$t_value->dump();
			//$vs_url = 
			return $this->render('ajax_information_service_detail_html.php');
		}
		# -------------------------------------------------------
 	}
 ?>