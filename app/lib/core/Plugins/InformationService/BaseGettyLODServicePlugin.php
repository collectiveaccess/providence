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
	private $opo_linked_data_conf = null;
	# ------------------------------------------------
	public function __construct() {
		parent::__construct(); // sets app.conf

		$this->opo_linked_data_conf = Configuration::load($this->opo_config->get('linked_data_config'));
	}
	# ------------------------------------------------
	/** 
	 * Perform lookup on Getty linked open data service
	 *
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options
	 * 		skosScheme - skos:inSchema query filter for SPARQL query. This essentially defines the vocabulary you're looking up.
	 * 				Can be empty if you want to search the whole linked data service (TGN + AAT as of April 2015, ULAN coming soon)
	 * @return array
	 */
	public function lookup($ps_search, $pa_options=null) {
		$vs_skos_scheme = caGetOption('skosScheme', $pa_options, '', array('validValues' => array('', 'tgn', 'aat', 'ulan')));

		/**
		 * Contrary to what the Getty documentation says the terms seem to get combined by OR, not AND, so if you pass
		 * "Coney Island" you get all kinds of Islands, just not the one you're looking for. It's in there somewhere but
		 * the order field might prevent it from showing up within the limit. So we do our own little piece of "query rewriting" here.
		 */
		$va_search = preg_split('/[\s]+/', $ps_search);
		$vs_search = join(' AND ', $va_search);

		$vs_query = urlencode('SELECT ?ID ?TermPrefLabel ?Parents {
				?ID a skos:Concept; luc:term "'.$vs_search.'"; skos:inScheme '.$vs_skos_scheme.': ;
				gvp:prefLabelGVP [xl:literalForm ?TermPrefLabel].
				{?ID gvp:parentStringAbbrev ?Parents}
				{?ID gvp:displayOrder ?Order}
			} ORDER BY ASC(?Order)
			LIMIT 25');

		$o_curl=curl_init();
		curl_setopt($o_curl, CURLOPT_URL, "http://vocab.getty.edu/sparql.json?query={$vs_query}");
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
		$va_service_conf = $this->opo_linked_data_conf->get('tgn'); // @todo make configurable
		if(!$va_service_conf || !is_array($va_service_conf)) { return array('display' => ''); }
		if(!isset($va_service_conf['detail_view_info']) || !is_array($va_service_conf['detail_view_info'])) { return array('display' => ''); }

		$vs_display = '<div style="margin-top:10px; margin-bottom: 10px;"><a target="_blank" href="'.$ps_url.'">'.$ps_url.'</a></div>';
		foreach($va_service_conf['detail_view_info'] as $va_node) {
			if(!isset($va_node['literal'])) { continue; }

			$vs_uri_for_pull = isset($va_node['uri']) ? $va_node['uri'] : null;

			$vs_display .= "<div class='formLabel'>";
			$vs_display .= isset($va_node['label']) ? $va_node['label'].": " : "";
			$vs_display .= "<span class='formLabelPlain'>".self::getLiteralFromRDFNode($ps_url, $va_node['literal'], $vs_uri_for_pull)."</span>";
			$vs_display .= "</div>\n";
		}

		return array('display' => $vs_display);
	}
	# ------------------------------------------------
	// HELPERS
	# ------------------------------------------------
	/**
	 * Fetches a literal property string value from given node
	 * @param string $ps_base_node
	 * @param string $ps_literal_propery EasyRdf property definition
	 * @param string|null $ps_node_uri Optional related node URI to pull from
	 * @return string|bool
	 */
	static function getLiteralFromRDFNode($ps_base_node, $ps_literal_propery, $ps_node_uri=null) {
		if(!isURL($ps_base_node)) { return false; }

		if(!($o_graph = self::getURIAsRDFGraph($ps_base_node))) { return false; }

		if(strlen($ps_node_uri) > 0) {
			$o_related_node = $o_graph->get($ps_base_node, $ps_node_uri);
			$vs_pull_uri = (string) $o_related_node;
			if(!($o_graph = self::getURIAsRDFGraph($vs_pull_uri))) { return false; }
		} else {
			$vs_pull_uri = $ps_base_node;
		}

		$o_literal = $o_graph->get($vs_pull_uri, $ps_literal_propery);

		if(!($o_literal instanceof EasyRdf_Literal)) { return false; }
		return $o_literal->getValue();
	}
	# ------------------------------------------------
	/**
	 * Try to load a given URI as RDF Graph
	 * @param string $ps_uri
	 * @return bool|EasyRdf_Graph
	 */
	static function getURIAsRDFGraph($ps_uri) {
		if(MemoryCache::contains($ps_uri, 'GettyLinkedDataRDFGraphs')) {
			return MemoryCache::fetch($ps_uri, 'GettyLinkedDataRDFGraphs');
		}

		try {
			$o_graph = new EasyRdf_Graph("http://vocab.getty.edu/download/rdf?uri={$ps_uri}.rdf");
			$o_graph->load();
		} catch(Exception $e) {
			return false;
		}

		MemoryCache::save($ps_uri, $o_graph, 'GettyLinkedDataRDFGraphs');
		return $o_graph;
	}
	# ------------------------------------------------
}
