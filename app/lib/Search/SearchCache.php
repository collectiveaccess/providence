<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/SearchCache.php : Caching for SearchEngine
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2023 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/ResultDescTrait.php");

class SearchCache {
	# ------------------------------------------------------
	use ResultDescTrait;
	
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
	public function __construct(?string $search=null, ?int $table_num=null, ?array $options=null) {
		$this->opa_search = [];

		if ($search && $table_num) {
			$this->load($search, $table_num, $options);
		}
	}
	# ------------------------------------------------------
	/**
	 * Load search from cache
	 * @param string $search
	 * @param int $table_num
	 * @param array $options
	 * @return bool true if successful, false if no result could be found in cache
	 */
	public function load(string $search, int $table_num, ?array $options=null) {
		$cache_key = $this->generateCacheKey($search, $table_num, $options);
		if(CompositeCache::contains($cache_key, 'SearchCache')) {
			if(is_array($va_cached_data = CompositeCache::fetch($cache_key, 'SearchCache'))) {
				$this->opa_search = $va_cached_data;
				$this->ops_cache_key = $cache_key;
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
	public function clear() : bool {
		$this->opa_search = [];
		$this->ops_cache_key = null;
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Save result to cache
	 * @param string $search the search expression
	 * @param int $table_num
	 * @param array $results
	 * @param array $result_desc
	 * @param null|array $pa_params
	 * @param null|array $type_restrictions
	 * @param null|array $options
	 * @return bool
	 */
	public function save(string $search, int $table_num, array $results, ?array $result_desc=null, ?array $params=null, ?array $type_restrictions=null, ?array $options=null) {
		if (!is_array($params)) { $params = []; }
		if (!is_array($type_restrictions)) { $type_restrictions = []; }

		$this->ops_cache_key = $this->generateCacheKey($search, $table_num, $options);
		$this->opa_search = array(
			'results' => $results,
			'search' => $search,
			'table_num' => $table_num,
			'params' => $params,
			'type_restrictions' => $type_restrictions
		);
		$this->setRawResultDesc($result_desc);	// optional data on how search temrs matched specified rows
		
		return CompositeCache::save($this->ops_cache_key, $this->opa_search, 'SearchCache');
	}
	# ------------------------------------------------------
	/**
	 * Remove this result from cache
	 * @return bool
	 */
	public function remove() {
		$this->opa_search = [];
		$ret = CompositeCache::delete($this->ops_cache_key, 'SearchCache');
		$this->ops_cache_key = null;
		return $ret;
	}
	# ------------------------------------------------------
	/**
	 * Get cache key for existing cached result
	 * @return string
	 */
	public function getCacheKey() : string {
		return $this->ops_cache_key;
	}
	# ------------------------------------------------------
	/**
	 * Set parameter
	 * @param string $param
	 * @param mixed $pm_value
	 */
	public function setParameter(string $param, $value) : void {
		$this->opa_search['params'][$param] = $value;
	}
	# ------------------------------------------------------
	/**
	 * Get list of parameters
	 * @return array
	 */
	public function getParameters() : array {
		return $this->opa_search['params'] ?? [];
	}
	# ------------------------------------------------------
	/**
	 * Get specific parameter
	 * @param string $param
	 * @return mixed|null
	 */
	public function getParameter(string $param) {
		return (isset($this->opa_search['params'][$param]) ? $this->opa_search['params'][$param] : null);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCounts() : array {
		return $this->opa_search['counts'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setResults(?array $results) : bool {
		$this->opa_search['results'] = is_array($results) ? $results : [];
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getResults() : array {
		return $this->opa_search['results'] ?? [];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function numResults() : int {
		return is_array($this->opa_search['results']) ? sizeof($this->opa_search['results']) : 0;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setTypeRestrictions(?array $type_restrictions) : bool {
		$this->opa_search['type_restrictions'] = is_array($type_restrictions) ? $type_restrictions : [];
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getTypeRestrictions() : array {
		return is_array($this->opa_search['type_restrictions']) ? $this->opa_search['type_restrictions'] : [];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function generateCacheKey(string $search, int $table_num, ?array $options = null) : string {
		return md5($search.'/'.$table_num.'/'.print_r($options, true));
	}
	# ------------------------------------------------------
	# Global parameters - available to all searches
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getGlobalParameter(string $param) : bool {
		if(CompositeCache::contains("search_global_{$param}", 'SearchCache')) {
			return CompositeCache::fetch("search_global_{$param}", 'SearchCache');
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setGlobalParameter(string $param, $value) {
		CompositeCache::save("search_global_{$param}", $value, 'SearchCache');
	}
	# ------------------------------------------------------
}
