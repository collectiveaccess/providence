<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/SparqlEndpoint.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage InformationService
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 define('QUERY_SPARQL_REPLACE_PLACEHOLDER', '***PLACEHOLDER***');
 define('QUERY_SPARQL_HIERARCHY_DELIMITER', '>');

 require_once(__CA_LIB_DIR__."/Plugins/IWLPlugInformationService.php");
 require_once(__CA_LIB_DIR__."/Plugins/InformationService/BaseInformationServicePlugin.php");

 global $g_information_service_settings_SparqlEndpoint;
 $g_information_service_settings_SparqlEndpoint = array(
   'querySparql' => array(
 		'formatType' => FT_TEXT,
 		'displayType' => DT_FIELD,
 		'default' => 'SELECT ?item ?itemLabel ?title WHERE {
  SERVICE wikibase:mwapi {
      bd:serviceParam wikibase:api "EntitySearch" .
      bd:serviceParam wikibase:endpoint "www.wikidata.org" .
      bd:serviceParam mwapi:search "***PLACEHOLDER***" .
      bd:serviceParam mwapi:language "en" .
      ?item wikibase:apiOutputItem mwapi:item .
      ?num wikibase:apiOrdinal true .
  }
      SERVICE wikibase:label {
                    bd:serviceParam wikibase:language "en" .
                  }
   BIND (STRAFTER(STR(?item), "http://www.wikidata.org/entity/") AS ?title)
  }',
 		'width' => 90, 'height' => 4,
 		'label' => _t('SPARQL query'),
 		'validForRootOnly' => 1,
 		'description' => _t('Insert SPARQL query here. Replace the query argument with ***PLACEHOLDER*** text.')
 	),
 	'querySparqlUrl' => array(
 		'formatType' => FT_TEXT,
 		'displayType' => DT_FIELD,
 		'default' => 'https://query.wikidata.org/sparql',
 		'width' => 90, 'height' => 2,
 		'label' => _t('SPARQL endpoint'),
 		'validForRootOnly' => 1,
 		'description' => _t('SPARQL endpoint, default Wikidata.')
 	),
 	'querySparqlResultsKeys' => array(
 		'formatType' => FT_TEXT,
 		'displayType' => DT_FIELD,
 		'default' => 'results > bindings',
 		'width' => 90, 'height' => 1,
 		'label' => _t('Result list keys'),
 		'validForRootOnly' => 1,
 		'description' => _t('Where is the results list? Keys hierarchy, separated by >')
 	),
 	'querySparqlElementUrl' => array(
 		'formatType' => FT_TEXT,
 		'displayType' => DT_FIELD,
 		'default' => '{{ item > value }}',
 		'width' => 90, 'height' => 2,
 		'label' => _t('Keys hierarchy to get element URL'),
 		'validForRootOnly' => 1,
 		'description' => _t('Keys hierarchy, separated by >')
 	),
 	'querySparqlElementLabel' => array(
 		'formatType' => FT_TEXT,
 		'displayType' => DT_FIELD,
 		'default' => '{{ itemLabel > value }} [{{ title > value}}]',
 		'width' => 90, 'height' => 2,
 		'label' => _t('Keys hierarchy to get element Label, used for autocomplete search and dropdown label'),
 		'validForRootOnly' => 1,
 		'description' => _t('Keys hierarchy, separated by >')
 	),
 );

 function synUrl2id($url) {
 		$els = explode('/', $url);
 		return array_pop($els);
 }

 function synHierarchy2array($querySparqlResultsKeys) {
 		/***
 		 From 'results > bindings' to array('results', 'bindings')
 		 ***/
 		$els = explode(QUERY_SPARQL_HIERARCHY_DELIMITER, $querySparqlResultsKeys);
 		$ret = array();
 		foreach ($els as $el) {
 				// trimmed value
 				$ret[] = trim($el);
 		}
 		return $ret;
 }

 function synFormatField($string, $data_row) {
 		/***
 			Format a string like:
 			{{ itemLabel > value }} [{{ item > value }}]
 			to:
       aerophone [Q659216]
 		***/
 		// XXX
 		$GLOBALS['synDirtyDataRow'] = $data_row;

 		return preg_replace_callback('/{{((?:[^}]|}[^}])+)}}/', function($match) {
 				$keys = synHierarchy2array($match[1]);
 				return drillDown($GLOBALS['synDirtyDataRow'], $keys);
 		}, $string);
 		unset($GLOBALS['synDirtyDataRow']);
 }

 function drillDown($arr, &$keys) {
 		try {
 				$keys = array_reverse($keys);
 				$k = array_pop($keys);
 				$keys = array_reverse($keys);
 				// search for element by key
 				if (count($keys)) {
 						return drillDown($arr[$k], $keys);
 				}
 				else {
 						return $arr[$k];
 				}
 		} catch (Exception $e) {
				// TODO: improve? e.g. if(!is_array($va_content) || !isset($va_content['results']['bindings']) || !is_array($va_content['results']['bindings']) || !sizeof($va_content['results']['bindings'])) { return array(); }
 			  error_log('InformationService drillDown: wrong keys, cannot find elementsarray');
 				return array();
 		}
 }

 function caQueryExternalWebserviceHeaders($ps_url, $ps_headers = array()) {
   /***
   Add header request to caQueryExternalWebservice.

   $headers = array(
    'Accept: application/json',
    'Authorization: This-is-a-test',
);
   ***/
   if(!isURL($ps_url)) { return false; }
   $o_conf = Configuration::load();

   $vo_curl = curl_init();
   curl_setopt($vo_curl, CURLOPT_URL, $ps_url);

   if($vs_proxy = $o_conf->get('web_services_proxy_url')){
     curl_setopt($vo_curl, CURLOPT_PROXY, $vs_proxy);
   }

   curl_setopt($vo_curl, CURLOPT_SSL_VERIFYHOST, 0);
   curl_setopt($vo_curl, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($vo_curl, CURLOPT_FOLLOWLOCATION, true);
   curl_setopt($vo_curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($vo_curl, CURLOPT_AUTOREFERER, true);
   curl_setopt($vo_curl, CURLOPT_CONNECTTIMEOUT, 120);
   curl_setopt($vo_curl, CURLOPT_TIMEOUT, 120);
   curl_setopt($vo_curl, CURLOPT_MAXREDIRS, 10);
   curl_setopt($vo_curl, CURLOPT_USERAGENT, 'CollectiveAccess web service lookup');
   if (!empty($ps_headers)) {
     curl_setopt($vo_curl, CURLOPT_HTTPHEADER, $ps_headers);
   }

   $vs_content = curl_exec($vo_curl);

   if(curl_getinfo($vo_curl, CURLINFO_HTTP_CODE) !== 200) {
     throw new \Exception(_t('An error occurred while querying an external webservice'));
   }
   curl_close($vo_curl);
   return $vs_content;
 }

 class WLPlugInformationServiceSparqlEndpoint Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
 	# ------------------------------------------------
 	static $s_settings;
 	# ------------------------------------------------
 	/**
 	 *
 	 */
 	public function __construct() {
 		global $g_information_service_settings_SparqlEndpoint;

 		WLPlugInformationServiceSparqlEndpoint::$s_settings = $g_information_service_settings_SparqlEndpoint;
 		parent::__construct();
 		$this->info['NAME'] = 'SparqlEndpoint';

 		$this->description = _t('Provides access to Wikidata service and other SPARQL endpoints.');
 	}
 	# ------------------------------------------------
 	/**
 	 * Get all settings settings defined by this plugin as an array
 	 *
 	 * @return array
 	 */
 	public function getAvailableSettings() {
 		return WLPlugInformationServiceSparqlEndpoint::$s_settings;
 	}
 	# ------------------------------------------------
 	# Data
 	# ------------------------------------------------
 	/**
 	 * Perform lookup on Wikipedia-based data service
 	 *
 	 * @param array $pa_settings Plugin settings values
 	 * @param string $ps_search The expression with which to query the remote data service
 	 * @param array $pa_options Lookup options (none defined yet)
 	 * @return array
 	 */
 	public function lookup($pa_settings, $ps_search, $pa_options=null) {
 		// // error_log(serialize($pa_settings));
 		// /core/Plugins/InformationService/InformationServiceAttributeValue.php
 		$query_sparql = $pa_settings['querySparql'];
 		// support passing full wikipedia URLs
 		if(isURL($ps_search)) { $ps_search = self::getPageTitleFromURI($ps_search); }
 		$vs_lang = caGetOption('lang', $pa_settings, 'en');

 		// readable version of get parameters
 		// ?query=...&format=...
 		$va_get_params = array(
 			'query' => urlencode(str_replace(QUERY_SPARQL_REPLACE_PLACEHOLDER, $ps_search, $query_sparql)),
 			'format' => 'json'  // XXX  al posto di usare l'header, usa esplicito GET param
 		);
 		$vs_content = caQueryExternalWebserviceHeaders(
       $vs_url = $pa_settings['querySparqlUrl'] . '?' . caConcatGetParams($va_get_params),
       $vs_headers = array('Accept: application/json')
 		);

 		$va_content = @json_decode($vs_content, true);
 		// top level drill down
 		// $looking_for_keys = array('results', 'bindings');
 		$looking_for_keys = synHierarchy2array($pa_settings['querySparqlResultsKeys']);
 		// equivalent to $va_content['results']['bindings']
 		$va_results = drillDown($va_content, $looking_for_keys);
 		$va_return = array();

 		// Get valuable data from each element
 		foreach($va_results as $va_result) {
 			$va_return['results'][] = array(
 				'label' => synFormatField($pa_settings['querySparqlElementLabel'], $va_result),
 				'url' => synFormatField($pa_settings['querySparqlElementUrl'], $va_result),
 			);
 		}

 		return $va_return;
 	}
 	# ------------------------------------------------
 	/**
 	 * Get display value. From lookup field to saved field.
 	 * @param string $ps_text
 	 * @return string
 	 */
 	public function getDisplayValueFromLookupText($ps_text) {
 		if(!$ps_text) { return ''; }
 		$va_matches = array();
 		if(preg_match("/^(.+)\s+\[.*?\]$/", $ps_text, $va_matches)) {
 			return $va_matches[1];
 		}
 		return $ps_text;
 	}
 	# ------------------------------------------------
 	/**
 	 * Fetch details about a specific item from a Wikipedia-based data service for "more info" panel
 	 *
 	 * @param array $pa_settings Plugin settings values
 	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
 	 * @return array An array of data from the data server defining the item.
 	 */
 	public function getExtendedInformation($pa_settings, $ps_url) {
 		$vs_display = "<p><a href='$ps_url' target='_blank'>$ps_url</a></p>";

 		$va_info = $this->getExtraInfo($pa_settings, $ps_url);

 		$vs_display .= "<div style='float:right; margin: 10px 0px 10px 10px;'><img src='".$va_info['image_thumbnail']."' /></div>";
 		$vs_display .= $va_info['abstract'];

 		return array('display' => $vs_display);
 	}
 	# ------------------------------------------------
 	private static function getPageTitleFromURI($ps_uri) {
 		if(preg_match("/\/([^\/]+)$/", $ps_uri, $va_matches)) {
 			return $va_matches[1];
 		}

 		return false;
 	}
 	# ------------------------------------------------
 }
