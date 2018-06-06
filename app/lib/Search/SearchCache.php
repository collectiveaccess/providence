<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/SearchCache.php : Caching for SearchEngine
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2014 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__.'/Zend/Cache.php');

class SearchCache {
	# ------------------------------------------------------
	/**
	 * @var string Cache key for currently loaded browse
	 */
	private $ops_cache_key;

	/**
	 * @var array Working copy of search data
	 */
	private $opa_search;

	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_search=null, $pn_table_num=null, $pa_options=null) {
		$this->opa_search = array();

		if ($ps_search && $pn_table_num) {
			$this->load($ps_search, $pn_table_num, $pa_options);
		}
	}
	# ------------------------------------------------------
	/**
	 * Load search from cache
	 * @param string $ps_search
	 * @param int $pn_table_num
	 * @param array $pa_options
	 * @return bool true if successful, false if no result could be found in cache
	 */
	public function load($ps_search, $pn_table_num, $pa_options) {
		$ps_cache_key = $this->generateCacheKey($ps_search, $pn_table_num, $pa_options);
		if(CompositeCache::contains($ps_cache_key, 'SearchCache')) {
			if(is_array($va_cached_data = CompositeCache::fetch($ps_cache_key, 'SearchCache'))) {
				$this->opa_search = $va_cached_data;
				$this->ops_cache_key = $ps_cache_key;
				return true;
			}
		}

		return false;
	}
	# ------------------------------------------------------
	/**
	 * Clear this SearchCache instance
	 * @return bool
	 */
	public function clear() {
		$this->opa_search = array();
		$this->ops_cache_key = null;
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Save result to cache
	 * @param string $ps_search the search expression
	 * @param int $pn_table_num
	 * @param array $pa_results
	 * @param null|array $pa_params
	 * @param null|array $pa_type_restrictions
	 * @param null|array $pa_options
	 * @return bool
	 */
	public function save($ps_search, $pn_table_num, $pa_results, $pa_params=null, $pa_type_restrictions=null, $pa_options=null) {
		if (!is_array($pa_params)) { $pa_params = array(); }
		if (!is_array($pa_type_restrictions)) { $pa_type_restrictions = array(); }

		$this->ops_cache_key = $this->generateCacheKey($ps_search, $pn_table_num, $pa_options);
		$this->opa_search = array(
			'results' => $pa_results,
			'search' => $ps_search,
			'table_num' => $pn_table_num,
			'params' => $pa_params,
			'type_restrictions' => $pa_type_restrictions
		);
		return CompositeCache::save($this->ops_cache_key, $this->opa_search, 'SearchCache');
	}
	# ------------------------------------------------------
	/**
	 * Remove this result from cache
	 * @return bool
	 */
	public function remove() {
		$this->opa_search = array();
		$vm_ret = CompositeCache::delete($this->ops_cache_key, 'SearchCache');
		$this->ops_cache_key = null;
		return $vm_ret;
	}
	# ------------------------------------------------------
	/**
	 * Get cache key for existing cached result
	 * @return string
	 */
	public function getCacheKey() {
		return $this->ops_cache_key;
	}
	# ------------------------------------------------------
	/**
	 * Set parameter
	 * @param string $ps_param
	 * @param mixed $pm_value
	 */
	public function setParameter($ps_param, $pm_value) {
		$this->opa_search['params'][$ps_param] = $pm_value;
	}
	# ------------------------------------------------------
	/**
	 * Get list of parameters
	 * @return array
	 */
	public function getParameters() {
		return $this->opa_search['params'];
	}
	# ------------------------------------------------------
	/**
	 * Get specific parameter
	 * @param string $ps_param
	 * @return mixed|null
	 */
	public function getParameter($ps_param) {
		return (isset($this->opa_search['params'][$ps_param]) ? $this->opa_search['params'][$ps_param] : null);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCounts() {
		return $this->opa_search['counts'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setResults($pa_results) {
		$this->opa_search['results'] = is_array($pa_results) ? $pa_results : array();
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getResults() {
		return $this->opa_search['results'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function numResults() {
		return is_array($this->opa_search['results']) ? sizeof($this->opa_search['results']) : 0;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setTypeRestrictions($pa_type_restrictions) {
		$this->opa_search['type_restrictions'] = is_array($pa_type_restrictions) ? $pa_type_restrictions : array();
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getTypeRestrictions() {
		return is_array($this->opa_search['type_restrictions']) ? $this->opa_search['type_restrictions'] : array();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function generateCacheKey($ps_search, $pn_table_num, $pa_options) {
		return md5($ps_search.'/'.$pn_table_num.'/'.serialize($pa_options));
	}
	# ------------------------------------------------------
	# Global parameters - available to all searches
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getGlobalParameter($ps_param) {
		if(CompositeCache::contains("search_global_{$ps_param}", 'SearchCache')) {
			return CompositeCache::fetch("search_global_{$ps_param}", 'SearchCache');
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setGlobalParameter($ps_param, $pm_value) {
		CompositeCache::save("search_global_{$ps_param}", $pm_value, 'SearchCache');
	}
	# ------------------------------------------------------
}
