<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/NomismaMints.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/Plugins/InformationService/BaseNomismaLODServicePlugin.php");

global $g_information_service_settings_nomisma_mints;
$g_information_service_settings_nomisma_mints = [];

class WLPlugInformationServiceNomismaMints extends BaseNomismaLODServicePlugin implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_nomisma_mints;

		WLPlugInformationServiceNomismaMints::$s_settings = $g_information_service_settings_nomisma_mints;
		parent::__construct();
		$this->info['NAME'] = 'Nomisma Mints';
		
		$this->description = _t('Provides access to mint data in Nomisma.org data service');
	}
	# ------------------------------------------------
	protected function getConfigName() {
		return 'nomisma_mints';
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceNomismaMints::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on Nomisma-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options
	 * 			phrase => send a lucene phrase search instead of keywords
	 * 			raw => return raw, unprocessed results from getty service
	 *			short = return short label (term only) [Default is false]
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }

		$va_service_conf = $this->opo_linked_data_conf->get('nomisma_mints');
		$vs_search_field = (isset($va_service_conf['search_text']) && $va_service_conf['search_text']) ? 'luc:text' : 'luc:term';

		$pb_phrase = (bool) caGetOption('phrase', $pa_options, false);
		$pb_raw = (bool) caGetOption('raw', $pa_options, false);
		$pn_limit = (int) caGetOption('limit', $pa_options, ($va_service_conf['result_limit']) ? $va_service_conf['result_limit'] : 50);

		/**
		 * Contrary to what the Getty documentation says the terms seem to get combined by OR, not AND, so if you pass
		 * "Coney Island" you get all kinds of Islands, just not the one you're looking for. It's in there somewhere but
		 * the order field might prevent it from showing up within the limit. So we do our own little piece of "query rewriting" here.
		 */
		if(is_numeric($ps_search)) {
			$vs_search = $ps_search;
		} elseif(isURL($ps_search)) {
			$vs_search = str_replace('http://nomisma.org/id/', '', $ps_search);
		} elseif($pb_phrase) {
			$vs_search = '\"'.$ps_search.'\"';
		} else {
			$va_search = preg_split('/[\s]+/', $ps_search);
			$vs_search = join(' AND ', $va_search);
		}

		$vs_query = urlencode('PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX dcterms:	<http://purl.org/dc/terms/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX nm: <http://nomisma.org/id/>
PREFIX nmo: <http://nomisma.org/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX spatial: <http://jena.apache.org/spatial#>
PREFIX xsd:	<http://www.w3.org/2001/XMLSchema#>

SELECT * WHERE {
   ?loc 
        geo:lat ?lat ;
        geo:long ?long .
   ?mint geo:location ?loc ;
         skos:prefLabel ?label ;
         skos:broader ?parent;
         a nmo:Mint
  FILTER (regex(?label, "'.$vs_search.'", "i"))
}
LIMIT '.$pn_limit);

		$va_results = parent::queryNomisma($vs_query);
		if(!is_array($va_results)) { return false; }

		if($pb_raw) { return $va_results; }

		$va_return = array();
		
		$res = [];
		foreach($va_results as $va_values) {
			$vs_id = '';
			if(preg_match("/([a-z0-9_\- ]+)$/", $va_values['mint']['value'], $va_matches)) {
				$vs_id = str_replace('/', ':', $va_matches[0]);
			}
			if(isset($res[$vs_id])) { continue; }

			$parent = '';
			if(preg_match("/([a-z0-9_\- ]+)$/", $va_values['parent']['value'], $va_matches)) {
				$parent = caUcFirstUTF8Safe(str_replace('/', ':', $va_matches[0]));
			}
			
			$vs_label = (caGetOption('format', $pa_options, null, ['forceToLowercase' => true]) === 'short') ? $va_values['label']['value'] : ($parent ? $parent . " ➜ " : "") . $va_values['label']['value'] . " [" . $vs_id . "]";
			$vs_label = preg_replace('/\,\s\.\.\.\s[A-Za-z\s]+Facet\s*/', '', $vs_label);
			$vs_label = preg_replace('/[\<\>]/', '', $vs_label);

			$res[$vs_id] = array(
				'label' => htmlentities($vs_label),
				'url' => $va_values['mint']['value'],
				'idno' => $vs_id,
			);
		}

		$va_return['results'] = array_values($res);
		return $va_return;
	}
	# ------------------------------------------------
	/**
	 * Get display value
	 * @param string $ps_text
	 * @return string
	 */
	public function getDisplayValueFromLookupText($ps_text) {
		if(!$ps_text) { return ''; }
		$va_matches = array();

		if(preg_match("/^\[[0-9]+\]\s+([\p{L}\p{P}\p{Z}]+)\s+\[/", $ps_text, $va_matches)) {
			return $va_matches[1];
		}
		return $ps_text;
	}
	# ------------------------------------------------
}
