<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/uBio.php :
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

  /**
    *
    */ 
    
    
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/BaseInformationServicePlugin.php");

class BaseGettyLODServicePlugin extends BaseInformationServicePlugin {
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on Getty linked open data service
	 *
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options
	 * 		skosScheme - skos:inSchema query filter for SPARQL query. This essentially defines the vocabulary you're looking up.
	 * 				Can be empty if you want to search the whole linked data service (TGN + AAT as of April 2015, ULAN coming soon)
	 * 		beta - query getty "beta" service instead. It sometimes has a preview into upcoming features. true or false, defaults to false.
	 * @return array
	 */
	public function lookup($ps_search, $pa_options=null) {
		$vs_skos_scheme = caGetOption('skosScheme', $pa_options, '', array('validValues' => array('', 'tgn', 'aat', 'ulan')));
		$vb_beta = caGetOption('beta', $pa_options, false);

		$vs_query = urlencode('
SELECT ?ID ?TermPrefLabel ?Parents {
  ?ID a skos:Concept; luc:text "'.$ps_search.'"; skos:inScheme '.$vs_skos_scheme.': ;
    gvp:prefLabelGVP [xl:literalForm ?TermPrefLabel].
    {?ID gvp:parentStringAbbrev ?Parents}
    {?ID gvp:displayOrder ?Order}
} ORDER BY ASC(?Order)
LIMIT 25
		');

		if(!$vb_beta) {
			$vs_getty_query_url = 'http://vocab.getty.edu/sparql.json';
		} else {
			$vs_getty_query_url = 'http://vocab-beta.getty.edu/sparql.json';
		}

		$o_curl=curl_init();
		curl_setopt($o_curl, CURLOPT_URL, "{$vs_getty_query_url}?query={$vs_query}");
		curl_setopt($o_curl, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($o_curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($o_curl, CURLOPT_USERAGENT, 'CollectiveAccess web service lookup');
		$vs_result = curl_exec($o_curl);
		curl_close($o_curl);

		if(!$vs_result) {
			return false;
		}

		$va_return = array();
		$va_result = json_decode($vs_result, true);
		if(!isset($va_result['results']['bindings']) || !is_array($va_result['results']['bindings'])) {
			return false;
		}

		foreach($va_result['results']['bindings'] as $va_values) {
			$vs_id = '';
			if(preg_match("/[a-z]{3,4}\/[0-9]+$/", $va_values['ID']['value'], $va_matches)) {
				$vs_id = str_replace('/', ':', $va_matches[0]);
			}

			$va_return['results'][] = array(
				'label' => $va_values['TermPrefLabel']['value'] . " (".$va_values['Parents']['value'].")",
				'url' => $va_values['ID']['value'],
				'id' => $vs_id,
			);
		}

		return $va_return;
	}
	# ------------------------------------------------
	/** 
	 * Fetch details about a specific item from getty data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		$o_graph = new EasyRdf_Graph("http://vocab.getty.edu/download/rdf?uri={$ps_url}.rdf");
		$o_graph->load();
		//var_dump($o_graph->get('http://vocab.getty.edu/ontology#parentString'));

		return array('display' => '');
	}
	# ------------------------------------------------
}
