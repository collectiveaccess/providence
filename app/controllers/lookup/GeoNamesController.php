<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/GeoNamesController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__."/ca_locales.php");


class GeoNamesController extends ActionController {
 	# -------------------------------------------------------
 	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 		parent::__construct($po_request, $po_response, $pa_view_paths);
 	}
 	# -------------------------------------------------------
 	# AJAX handlers
 	# -------------------------------------------------------
	public function Get($pa_additional_query_params=null, $pa_options=null) {
		global $g_ui_locale_id;

		$vn_max = ($this->request->getParameter('maxRows', pInteger) ? $this->request->getParameter('maxRows', pInteger) : 20);
		$ps_query = $this->request->getParameter('term', pString);

		$ps_gn_elements = urldecode($this->request->getParameter('gnElements', pString));
		$ps_gn_delimiter = urldecode($this->request->getParameter('gnDelimiter', pString));

		$pa_elements = explode(',',$ps_gn_elements);

		$vo_conf = Configuration::load();
		$vs_user = trim($vo_conf->get("geonames_user"));

		
		$va_items = array();
		if (unicode_strlen($ps_query) >= 3) {
			$vs_base = "http://api.geonames.org/search";
			$t_locale = new ca_locales($g_ui_locale_id);
			$vs_lang = $t_locale->get("language");
			$va_params = array(
				"q" => $ps_query,
				"lang" => $vs_lang,
				'style' => 'full',
				'username' => $vs_user,
				'maxRows' => $vn_max,
			);

			foreach ($va_params as $vs_key => $vs_value) {
				$vs_query_string .= "$vs_key=" . urlencode($vs_value) . "&";
			}

			try {

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
					$vs_xml = @file_get_contents("{$vs_base}?$vs_query_string", false, $vo_context);

				} else {
					$vs_xml = @file_get_contents("{$vs_base}?$vs_query_string");
				}

				$vo_xml = new SimpleXMLElement($vs_xml);
				
				$va_attr = $vo_xml->status ? $vo_xml->status->attributes() : null;
				if ($va_attr && isset($va_attr['value']) && ((int)$va_attr['value'] > 0)) { 
					$va_items[0] = array(
						'displayname' => _t('Connection to GeoNames with username "%1" was rejected with the message "%2". Check your configuration and make sure your GeoNames.org account is enabled for web services.', $vs_user, $va_attr['message']),
						'lat' => '',
						'lng' => '',
					);
					$va_items[0]['label'] = $va_items[0]['displayname'];
				} else {
					foreach($vo_xml->children() as $vo_child){
						if($vo_child->getName()=="geoname"){
							$va_elements = array();

							foreach($pa_elements as $ps_element){
								$vs_val = $vo_child->{trim($ps_element)};
								if(strlen(trim($vs_val))>0){
									$va_elements[] = trim($vs_val);
								}
							}

							$va_items[(string)$vo_child->geonameId] = array(
								'displayname' => $vo_child->name,
								'label' => join($ps_gn_delimiter,$va_elements).
											($vo_child->lat ? " [".$vo_child->lat."," : '').
											($vo_child->lng ? $vo_child->lng."]" : ''),
								'lat' => $vo_child->lat ? $vo_child->lat : null,
								'lng' => $vo_child->lng ? $vo_child->lng : null,
								'id' => (string)$vo_child->geonameId
							);
						}
					}
				}
			} catch (Exception $e) {
				$va_items[0] = array(
					'displayname' => _t('Could not connect to GeoNames'),
					'lat' => '',
					'lng' => '',
					'id' => 0
				);
				$va_items[0]['label'] = $va_items[0]['displayname'];
			}
		}

		$this->view->setVar('geonames_list', $va_items);
		return $this->render('ajax_geonames_list_html.php');
	}
	# -------------------------------------------------------
}
