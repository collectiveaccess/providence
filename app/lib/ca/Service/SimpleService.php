<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/SimpleService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2016 Whirl-i-Gig
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

class SimpleService {
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

		switch($va_endpoint_config['type']) {
			case 'search':
				$vm_return = self::runSearchEndpoint($va_endpoint_config, $po_request);
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
		$o_dm = Datamodel::load();

		// load instance
		$t_instance = $o_dm->getInstance($pa_config['table']);
		if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {
			throw new Exception('invalid table');
		}

		$va_get_options = array();

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

		$va_return = array();
		foreach($pa_config['content'] as $vs_key => $vs_template) {
			$va_return[self::sanitizeKey($vs_key)] = $t_instance->getWithTemplate($vs_template, $va_get_options);
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
		$o_dm = Datamodel::load();

		// load blank instance
		$t_instance = $o_dm->getInstance($pa_config['table']);
		if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {
			throw new Exception('invalid table');
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
			$va_type_filter = array();
			foreach($pa_config['restrictToTypes'] as $vs_type_code) {
				$va_type_filter[] = caGetListItemID($t_instance->getTypeListCode(), $vs_type_code);
			}
			$o_search->addResultFilter($t_instance->tableName().'.type_id', 'IN', join(",",$va_type_filter));
		}

		/** @var SearchResult $o_res */
		$o_res = $o_search->search($ps_q, array(
			'sort' => $po_request->getParameter('sort', pString),
			'sortDirection' => $po_request->getParameter('sortDirection', pString),
			'checkAccess' => $pa_config['checkAccess'],
		));

		if($vn_start = $po_request->getParameter('start', pInteger)) {
			if(!$o_res->seek($vn_start)) {
				return array();
			}
		}

		$vn_limit = $po_request->getParameter('limit', pInteger);
		if(!$vn_limit) { $vn_limit = 0; }

		$va_return = array();
		$va_get_options = array();
		if(isset($pa_config['checkAccess']) && is_array($pa_config['checkAccess'])) {
			$va_get_options['checkAccess'] = $pa_config['checkAccess'];
		}

		while($o_res->nextHit()) {
			$va_hit = array();

			foreach($pa_config['content'] as $vs_key => $vs_template) {
				$va_hit[self::sanitizeKey($vs_key)] = $o_res->getWithTemplate($vs_template, $va_get_options);
			}

			$va_return[$o_res->get($t_instance->primaryKey(true))] = $va_hit;

			if($vn_limit && (sizeof($va_return) >= $vn_limit)) { break; }
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

		if(!isset($va_endpoints[$ps_endpoint]['type']) || !in_array($va_endpoints[$ps_endpoint]['type'], array('search', 'detail'))) {
			throw new Exception('Service endpoint config is invalid: type must be search or detail');
		}

		if(!isset($va_endpoints[$ps_endpoint]['content']) || !is_array($va_endpoints[$ps_endpoint]['content'])) {
			throw new Exception('Service endpoint config is invalid: No display content defined');
		}

		return $va_endpoints[$ps_endpoint];
	}
	# -------------------------------------------------------
	private static function sanitizeKey($ps_key) {
		return preg_replace('[^A-Za-z0-9\-\_\.\:]', '', $ps_key);
	}
	# -------------------------------------------------------
}
