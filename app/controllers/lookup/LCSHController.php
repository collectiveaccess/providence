<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/LCSHController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 	
 
 	class LCSHController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			if (!($ps_query = $this->request->getParameter('q', pString))) {
				$ps_query = $this->request->getParameter('term', pString);
			}
			$ps_type = $this->request->getParameter('type', pString);
			$va_vocs = array();
			$vs_voc_query = '';
			if ($vn_element_id = $this->request->getParameter('element_id', pInteger)) {
				$t_element = new ca_metadata_elements($vn_element_id);
				if ($vs_voc = $t_element->getSetting('vocabulary')) {
					$vs_voc_query .= '&q='.urlencode($vs_voc);
				}
			}
			$vo_conf = Configuration::load();
			$va_items = array();
			if (unicode_strlen($ps_query) >= 3) {
				try {
					$vs_url = "http://id.loc.gov/search/?q=".urlencode($ps_query).$vs_voc_query.'&format=atom&count=150';

					if($vs_proxy = $vo_conf->get('web_services_proxy_url')){ /* proxy server is configured */

						if(($vs_proxy_user = $vo_conf->get('web_services_proxy_auth_user')) && ($vs_proxy_pass = $vo_conf->get('web_services_proxy_auth_pw'))){
							$vs_proxy_auth = base64_encode("{$vs_proxy_user}:{$vs_proxy_pass}");
						}

						$va_context_options = array( 'http' => array(
							'proxy' => $vs_proxy,
							'request_fulluri' => true,
							'header' => 'User-agent: CollectiveAccess web service lookup',
						));

						if($vs_proxy_auth){
							$va_context_options['http']['header'] = "Proxy-Authorization: Basic {$vs_proxy_auth}";
						}

						$vo_context = stream_context_create($va_context_options);
						$vs_data = @file_get_contents($vs_url, false, $vo_context);

					} else {
						$vs_data = @file_get_contents($vs_url);
					}

					if ($vs_data) {
						$o_xml = @simplexml_load_string($vs_data);
	
						if ($o_xml) {
							$o_entries = $o_xml->{'entry'};
							if ($o_entries && sizeof($o_entries)) {
								foreach($o_entries as $o_entry) {
									$o_links = $o_entry->{'link'};
									$va_attr = $o_links[0]->attributes();
									$vs_url = (string)$va_attr->{'href'};
									$va_items[] = array('label' => (string)$o_entry->{'title'}, 'idno' => (string)$o_entry->{'id'}, 'url' => $vs_url);
								}
							}
						}
					}
				} catch (Exception $e) {
					$va_items['error'] = array('displayname' => _t('ERROR').':'.$e->getMessage(), 'idno' => '');
				}
			}
			
			$this->view->setVar('lcsh_list', $va_items);
 			return $this->render('ajax_lcsh_list_html.php');
		}
		# -------------------------------------------------------
 	}
 ?>