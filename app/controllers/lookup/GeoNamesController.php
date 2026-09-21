<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/GeoNamesController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2026 Whirl-i-Gig
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
 	public function __construct(&$request, &$response, $view_paths=null) {
 		parent::__construct($request, $response, $view_paths);
 	}
 	# -------------------------------------------------------
 	# AJAX handlers
 	# -------------------------------------------------------
	public function Get($additional_query_params=null, $options=null) {
		global $g_ui_locale_id;

		$vn_max = ($this->request->getParameter('maxRows', pInteger) ? $this->request->getParameter('maxRows', pInteger) : 20);
		$query = $this->request->getParameter('term', pString);

		$gn_elements = urldecode($this->request->getParameter('gnElements', pString));
		$gn_delimiter = urldecode($this->request->getParameter('gnDelimiter', pString));
		
		$country = explode(';', preg_replace("![^A-Z;]+!i", "", urldecode($this->request->getParameter('country', pString))));
		$feature_class = explode(';', preg_replace("![^A-Z;]+!i", "", urldecode($z=$this->request->getParameter('featureClass', pString))));
		$mode = preg_replace("![^A-Z]+!i", "", urldecode($this->request->getParameter('mode', pString)));

		$elements = explode(',',$gn_elements);

		$conf = Configuration::load();
		$user = trim($conf->get("geonames_user"));

		
		$items = array();
		if (mb_strlen($query) >= 3) {
			$base = $conf->get('geonames_api_base_url') . '/search';
			$t_locale = new ca_locales($g_ui_locale_id);
			$lang = $t_locale->get("language");
			$params = [
				"lang" => $lang,
				'style' => 'full',
				'username' => $user,
				'maxRows' => $vn_max,
				'fuzzy' => 1
			];
			if($mode === 'name' ) { $params["name"] = $query; } else { $params['q'] = $query; }
			if(is_array($country) && sizeof($country)) {
				$params['country'] = $country; 
			}
			if(is_array($feature_class) && sizeof($feature_class)) { 
				$params['featureClass'] = $feature_class; 
			}

			$query_string = '';
			foreach ($params as $key => $val) {
				if(is_array($val)) {
					foreach($val as $v) {
						$query_string .= "{$key}=" . urlencode($v) . "&";
					}
				} else {
					$query_string .= "{$key}=" . urlencode($val) . "&";
				}
			}

			try {
				$xml = caQueryExternalWebservice("{$base}?{$query_string}");
				$xml = new SimpleXMLElement($xml);
				
				$attr = $xml->status ? $xml->status->attributes() : null;
				if ($attr && isset($attr['value']) && ((int)$attr['value'] > 0)) { 
					$items[0] = array(
						'displayname' => _t('Connection to GeoNames with username "%1" was rejected with the message "%2". Check your configuration and make sure your GeoNames.org account is enabled for web services.', $user, $attr['message']),
						'lat' => '',
						'lng' => '',
					);
					$items[0]['label'] = $items[0]['displayname'];
				} else {
					foreach($xml->children() as $child){
						if($child->getName()=="geoname"){
							$md_elements = array();

							foreach($elements as $element){
								$val = $child->{trim($element)};
								if(strlen(trim($val))>0){
									$md_elements[] = trim($val);
								}
							}

							$items[(string)$child->geonameId] = array(
								'displayname' => $child->name,
								'label' => join($gn_delimiter,$md_elements).
											($child->lat ? " [".$child->lat."," : '').
											($child->lng ? $child->lng."]" : ''),
								'lat' => $child->lat ? $child->lat : null,
								'lng' => $child->lng ? $child->lng : null,
								'id' => (string)$child->geonameId
							);
						}
					}
				}
			} catch (Exception $e) {
				$items[0] = array(
					'displayname' => _t('Could not connect to GeoNames'),
					'lat' => '',
					'lng' => '',
					'id' => 0
				);
				$items[0]['label'] = $items[0]['displayname'];
			}
		}

		$this->view->setVar('geonames_list', $items);
		return $this->render('ajax_geonames_list_html.php');
	}
	# -------------------------------------------------------
}
