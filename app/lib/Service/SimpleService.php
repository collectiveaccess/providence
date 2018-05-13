<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/SimpleService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2017 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__."/Browse/BrowseEngine.php");
require_once(__CA_APP_DIR__."/helpers/browseHelpers.php");

class SimpleService {
	# -------------------------------------------------------
	static $s_key_cache = [];
	static $s_simple_template_cache = [];
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $ps_endpoint
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch($ps_endpoint, $po_request) {

		$vs_cache_key = $po_request->getHash();

		if(!$po_request->getParameter('noCache', pInteger)) {
			if(ExternalCache::contains($vs_cache_key, "SimpleAPI_{$ps_endpoint}")) {
				return ExternalCache::fetch($vs_cache_key, "SimpleAPI_{$ps_endpoint}");
			}
		}

		$va_endpoint_config = self::getEndpointConfig($ps_endpoint); // throws exception if it can't be found

		switch(strtolower($va_endpoint_config['type'])) {
			case 'stats':
				$vm_return = self::runStatsEndpoint($va_endpoint_config, $po_request);
				break;
			case 'refineablesearch':
				$vm_return = self::runRefineableSearchEndpoint($va_endpoint_config, $po_request);
				break;
			case 'search':
				$vm_return = self::runSearchEndpoint($va_endpoint_config, $po_request);
				break;
			case 'site_page':
				$vm_return = self::runSitePageEndpoint($va_endpoint_config, $po_request);
				break;
			case 'detail':
			default:
				$vm_return = self::runDetailEndpoint($va_endpoint_config, $po_request);
				break;
		}

		$vn_ttl = defined('__CA_SERVICE_API_CACHE_TTL__') ? __CA_SERVICE_API_CACHE_TTL__ : 60*60; // save for an hour by default
		ExternalCache::save($vs_cache_key, $vm_return, "SimpleAPI_{$ps_endpoint}", $vn_ttl);
		return $vm_return;
	}
	# -------------------------------------------------------
	/**
	 * @param array $pa_config
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	private static function runDetailEndpoint($pa_config, $po_request) {
		// load instance
		$t_instance = Datamodel::getInstance($pa_config['table']);
		if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {
			throw new Exception('Invalid table');
		}

		$va_get_options = [];

		$pm_id = $po_request->getParameter('id', pString);
		if(!$t_instance->load($pm_id)) {
			$t_instance->load(array($t_instance->getProperty('ID_NUMBERING_ID_FIELD') => $pm_id));
		}

		if(!$t_instance->getPrimaryKey()) {
			throw new Exception('Could not load record');
		}

		// checkAccess
		if(isset($pa_config['checkAccess']) && is_array($pa_config['checkAccess'])) {
			$va_get_options['checkAccess'] = $pa_config['checkAccess'];
			if(!in_array($t_instance->get('access'), $pa_config['checkAccess'])) {
				throw new Exception('Invalid parameters');
			}
		}

		// restrictToTypes
		if($pa_config['restrictToTypes'] && is_array($pa_config['restrictToTypes']) && (sizeof($pa_config['restrictToTypes']) > 0)) {
			if(!in_array($t_instance->getTypeCode(), $pa_config['restrictToTypes'])) {
				throw new Exception('Invalid parameters');
			}
		}

		$va_return = [];
		
		foreach($pa_config['content'] as $vs_key => $vm_template) {
			$va_return[self::sanitizeKey($vs_key)] = SimpleService::processContentKey($t_instance, $vs_key, $vm_template, $va_get_options);
		}

		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * @param array $pa_config
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	private static function runSitePageEndpoint($pa_config, $po_request) {
		// load instance
		$t_instance = Datamodel::getInstance('ca_site_pages');
		if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {
			throw new Exception('invalid table');
		}

		$va_get_options = array();

		$ps_path = $po_request->getParameter('path', pString);
	
		if(!$t_instance->load(array("path" => $ps_path))) {
			throw new Exception('invalid path');
		}

		if(!$t_instance->getPrimaryKey()) {
			throw new Exception('Could not load record');
		}

		// checkAccess
		if(isset($pa_config['checkAccess']) && is_array($pa_config['checkAccess'])) {
			$va_get_options['checkAccess'] = $pa_config['checkAccess'];
			if(!in_array($t_instance->get('access'), $pa_config['checkAccess'])) {
				throw new Exception('Invalid parameters');
			}
		}

		$va_return = array();
		
		# --- return all the configured tag/content for the page - nothing needs to be configured in services.conf
		if($va_content = $t_instance->get("content")){
			$va_return["data"] = $va_content;
		}

		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * @param array $pa_config
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	private static function runSearchEndpoint($pa_config, $po_request) {
		
		$vb_return_data_as_list = caGetOption('returnDataAsList', $pa_config, false, ['castTo' => 'bool']);

		// load blank instance
		$t_instance = Datamodel::getInstance($pa_config['table']);
		if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {
			throw new Exception('Invalid table');
		}

		if(!($ps_q = $po_request->getParameter('q', pString))) {
			throw new Exception('No query specified');
		}

		$o_search = caGetSearchInstance($pa_config['table']);
		if(!$o_search instanceof SearchEngine) {
			throw new Exception('Invalid table in config');
		}

		// restrictToTypes
		if($pa_config['restrictToTypes'] && is_array($pa_config['restrictToTypes']) && (sizeof($pa_config['restrictToTypes']) > 0)) {
			$o_search->setTypeRestrictions($pa_config['restrictToTypes']);
		}

		/** @var SearchResult $o_res */
		$o_res = $o_search->search($ps_q, array(
			'sort' => ($po_request->getParameter('sort', pString)) ? $po_request->getParameter('sort', pString) : $pa_config['sort'],
			'sortDirection' => ($po_request->getParameter('sortDirection', pString)) ? $po_request->getParameter('sortDirection', pString) : $pa_config['sortDirection'],
			'checkAccess' => $pa_config['checkAccess'],
		));

		if($vn_start = $po_request->getParameter('start', pInteger)) {
			if(!$o_res->seek($vn_start)) {
				return [];
			}
		}

		$vn_limit = $po_request->getParameter('limit', pInteger);
		if(!$vn_limit) { $vn_limit = 0; }

		$va_return = [];
		if (isset($pa_config['includeCount']) && $pa_config['includeCount']) {
		    $va_return['resultCount'] = $o_res->numHits();
		}
		$va_get_options = [];
		if(isset($pa_config['checkAccess']) && is_array($pa_config['checkAccess'])) {
			$va_get_options['checkAccess'] = $pa_config['checkAccess'];
		}

		while($o_res->nextHit()) {
			$va_hit = [];

			foreach($pa_config['content'] as $vs_key => $vm_template) {
				$va_hit[self::sanitizeKey($vs_key)] = SimpleService::processContentKey($o_res, $vs_key, $vm_template, $va_get_options);
			}
			
			if ($vb_return_data_as_list) {
				$va_return['data'][] = $va_hit;
				if($vn_limit && (sizeof($va_return['data']) >= $vn_limit)) { break; }
			} else {
				$va_return[$o_res->get($t_instance->primaryKey(true))] = $va_hit;
				if($vn_limit && (sizeof($va_return) >= $vn_limit)) { break; }
			}
			
		}

		return $va_return;

	}
	# -------------------------------------------------------
	/**
	 * @param array $pa_config
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	private static function runRefineableSearchEndpoint($pa_config, $po_request) {
		
		$vb_return_data_as_list = caGetOption('returnDataAsList', $pa_config, false, ['castTo' => 'bool']);

		// load blank instance
		$t_instance = Datamodel::getInstance($pa_config['table']);
		if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {
			throw new Exception('Invalid table');
		}

		if(!($ps_q = $po_request->getParameter('q', pString))) {
			throw new Exception('No query specified');
		}

		$o_browse = caGetBrowseInstance($pa_config['table']);
		if(!$o_browse instanceof BrowseEngine) {
			throw new Exception('Invalid table in config');
		}
		
		if (isset($pa_config['facet_group']) && $pa_config['facet_group']) {
			$o_browse->setFacetGroup($pa_config['facet_group']);
		}

		// restrictToTypes
		if($pa_config['restrictToTypes'] && is_array($pa_config['restrictToTypes']) && (sizeof($pa_config['restrictToTypes']) > 0)) {
			$o_browse->setTypeRestrictions($pa_config['restrictToTypes']);
		}
		$o_browse->addCriteria("_search", $ps_q);
		
		if(is_array($pa_criteria_to_add = $po_request->getParameter('criteria', pArray))) {
			foreach($pa_criteria_to_add as $vs_facet_value) {
				list($vs_facet, $vs_value) = explode(":", $vs_facet_value);
				$o_browse->addCriteria($vs_facet, $vs_value);
			}
		}

		
		$va_search_opts = array(
					'sort' => ($po_request->getParameter('sort', pString)) ? $po_request->getParameter('sort', pString) : $pa_config['sort'], 
					'sort_direction' => ($po_request->getParameter('sortDirection', pString)) ? $po_request->getParameter('sortDirection', pString) : $pa_config['sortDirection'], 
					'appendToSearch' => $vs_append_to_search,
					'checkAccess' => $pa_config['checkAccess'],
					'no_cache' => true,
					'dontCheckFacetAvailability' => true,
					'filterNonPrimaryRepresentations' => true
				);
		$o_browse->execute();
		
		/** @var SearchResult $o_res */
		$o_res = $o_browse->getResults();
		
		if($vn_start = $po_request->getParameter('start', pInteger)) {
			if(!$o_res->seek($vn_start)) {
				return [];
			}
		}

		$vn_limit = $po_request->getParameter('limit', pInteger);
		if(!$vn_limit) { $vn_limit = 0; }

		$va_return = ['resultCount' => $o_res->numHits()];
		$va_get_options = [];
		if(isset($pa_config['checkAccess']) && is_array($pa_config['checkAccess'])) {
			$va_get_options['checkAccess'] = $pa_config['checkAccess'];
		}
		
		if (!is_array($va_include_facets = $pa_config['facets'])) { $va_include_facets = []; }
		
		if (is_array($va_facets = $o_browse->getInfoForAvailableFacets())) {
			foreach($va_facets as $vs_facet => $va_facet_info) {
				if (sizeof($va_include_facets) && !in_array($vs_facet, $va_include_facets)) { continue; }
			
				if(!is_array($va_content = $o_browse->getFacetContent($vs_facet))) { continue; }
				
				$va_ret_content = [];
				foreach($va_content as $vn_id => $va_content_item) {
					$va_ret_content[] = [
						'id' => $va_content_item['id'],
						'label' => $va_content_item['label']
					];
				}
				$va_return['facets'][$vs_facet] = [
					'type' => $va_facet_info['type'],
					'table' => isset($va_facet_info['table']) ? $va_facet_info['table'] : null,
					'label_singular' => $va_facet_info['label_singular'],
					'label_plural' => $va_facet_info['label_plural'],
					'content' => $va_ret_content
				];
			}
		}

		while($o_res->nextHit()) {
			$va_hit = [];

			foreach($pa_config['content'] as $vs_key => $vm_template) {
				$va_hit[self::sanitizeKey($vs_key)] = SimpleService::processContentKey($o_res, $vs_key, $vm_template, $va_get_options);
			}
			
			if ($vb_return_data_as_list) {
				$va_return['results']['data'][] = $va_hit;
				if($vn_limit && (sizeof($va_return['results']['data']) >= $vn_limit)) { break; }
			} else {
				$va_return['results'][$o_res->get($t_instance->primaryKey(true))] = $va_hit;
				if($vn_limit && (sizeof($va_return['results']) >= $vn_limit)) { break; }
			}
			
		}

		$va_return['criteria'] = $o_browse->getCriteriaWithLabels();
		
		$va_return['criteria_facet_names'] = [];
		foreach($va_return['criteria'] as $vs_facet => $va_facet_values) {
			if ($vs_facet == '_search') { continue; }
			$va_facet_info = $o_browse->getInfoForFacet($vs_facet);
			$va_return['criteria_facet_names'][$vs_facet] = $va_facet_info['label_plural'];
		}
		
		return $va_return;

	}
	# -------------------------------------------------------
	/**
	 * Get configuration for endpoint. Also does config validation.
	 * @param string $ps_endpoint
	 * @return array
	 * @throws Exception
	 */
	private static function getEndpointConfig($ps_endpoint) {
		$o_app_conf = Configuration::load();
		$o_service_conf = Configuration::load($o_app_conf->get('services_config'));

		$va_endpoints = $o_service_conf->get('simple_api_endpoints');

		if(!is_array($va_endpoints) || !isset($va_endpoints[$ps_endpoint]) || !is_array($va_endpoints[$ps_endpoint])) {
			throw new Exception('Invalid service endpoint');
		}

		if(!isset($va_endpoints[$ps_endpoint]['type']) || !in_array($va_endpoints[$ps_endpoint]['type'], array('search', 'detail', 'refineablesearch', 'stats', 'site_page'))) {
			throw new Exception('Service endpoint config is invalid: type must be search or detail');
		}

		if(!isset($va_endpoints[$ps_endpoint]['content']) || !is_array($va_endpoints[$ps_endpoint]['content'])) {
			throw new Exception('Service endpoint config is invalid: No display content defined');
		}

		return $va_endpoints[$ps_endpoint];
	}
	# -------------------------------------------------------
	private static function sanitizeKey($ps_key) {
	    if(isset(SimpleService::$s_key_cache[$ps_key])) { return SimpleService::$s_key_cache[$ps_key]; }
		return SimpleService::$s_key_cache[$ps_key] = preg_replace('[^A-Za-z0-9\-\_\.\:]', '', $ps_key);
	}
	# -------------------------------------------------------
	private static function isSimpleTemplate($ps_template) {
	    if(isset(SimpleService::$s_simple_template_cache[$ps_template])) { return SimpleService::$s_simple_template_cache[$ps_template]; }
	    
	    return SimpleService::$s_simple_template_cache[$ps_template] = preg_match("!^\^ca_[A-Za-z0-9_\-\.]+$!", $ps_template);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function processContentKey($pt_instance, $ps_key, $pm_template, $pa_options=null) {
		if(is_array($pm_template)) {
			$vs_return_as = caGetOption('returnAs', $pm_template, 'text', ['forceLowercase' => true]);
			$vs_delimiter = caGetOption('delimiter', $pm_template, ";");
			$vs_template = caGetOption('valueTemplate', $pm_template, 'No template');
			
			// Get values and break on delimiter
			if (SimpleService::isSimpleTemplate($vs_template)) {
		        $vs_v = $pt_instance->get(str_replace("^", "", $vs_template), $pa_options);
			} else {
			    $vs_v = $pt_instance->getWithTemplate($vs_template, array_merge($pa_options, ['includeBlankValuesInArray' => true]));
			}
			$va_v = explode($vs_delimiter, $vs_v);
			
			$va_key = null;
			if ($vs_key_template = caGetOption('keyTemplate', $pm_template, null)) {
				// Get keys and break on delimiter
				$va_keys = explode($vs_delimiter, $vs_keys = $pt_instance->getWithTemplate($vs_key_template, $pa_options));
			}
		
			$va_v_decode = [];
			foreach($va_v as $vn_i => $vs_part) {
				switch($vs_return_as) {
					case 'json':
						if ($va_json = json_decode($vs_part)) { 
							if ($va_keys) {
								$va_v_decode[$va_keys[$vn_i]] = $va_json; 
							} else {
								$va_v_decode[] = $va_json; 
							}
						}
						break;
					default:
						$va_v_decode[] = $vs_part;
						break;
				}
			}
			return $va_v_decode;
		} else {
		    if (SimpleService::isSimpleTemplate($pm_template)) {
		        return $pt_instance->get(str_replace("^", "", $pm_template), $pa_options);
		    } 
			return $pt_instance->getWithTemplate($pm_template, $pa_options);
		}
	}
	# -------------------------------------------------------
}
