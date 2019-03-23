<?php
/** ---------------------------------------------------------------------
 * app/lib/Browse/BrowseCache.php : Caching for BrowseEngine
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
 * @subpackage Browse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__.'/Zend/Cache.php');

class BrowseCache {
	# ------------------------------------------------------
	/**
	 * @var Cache key for currently loaded browse
	 */
	private $ops_cache_key;


	/**
	 * @var Working copy of browse data
	 */
	private $opa_browse;

	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_cache_key=null) {

		$this->opa_browse = array();

		if ($ps_cache_key) {
			$this->load($ps_cache_key);
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 *
	 * @param string $ps_cache_key
	 * @param array $pa_options Options include:
	 *		removeDeletedItems = remove any items in the cache that are currently marked as deleted [Default=true]
	 *
	 * @return bool
	 */
	public function load($ps_cache_key, $pa_options=null) {
		if (ExternalCache::contains($ps_cache_key, 'Browse')) {
			$this->opa_browse = ExternalCache::fetch($ps_cache_key, 'Browse');
			$this->ops_cache_key = $ps_cache_key;
			
			if (caGetOption('removeDeletedItems', $pa_options, true)) {
				if (($t_instance = Datamodel::getInstanceByTableNum($this->opa_browse['params']['table_num'], true)) && ($t_instance->hasField('deleted'))) {
					// check if there are any deleted items in the cache
					if (is_array($va_ids = $this->opa_browse['results']) && sizeof($va_ids)) {
						$vs_pk = $t_instance->primaryKey();
						$qr_deleted = $t_instance->getDb()->query($x="
							SELECT {$vs_pk} FROM ".$t_instance->tableName()." WHERE {$vs_pk} IN (?) AND deleted = 1
						", array($va_ids));	
						if ($qr_deleted->numRows() > 0) {
							$va_deleted_ids = $qr_deleted->getAllFieldValues($vs_pk);
							foreach($va_deleted_ids as $vn_deleted_id) {
								if (($vn_i = array_search($vn_deleted_id, $va_ids)) !== false) {
									unset($va_ids[$vn_i]);
								}
							}
							$this->opa_browse['results'] = array_values($va_ids);
						}
						
					}
				}
			}
			
			return true;
		}

		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function clear() {
		$this->opa_browse = array();
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function save() {
		$this->ops_cache_key = $this->getCurrentCacheKey();
		ExternalCache::save($this->ops_cache_key, $this->opa_browse, 'Browse');
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function refresh() {
		$this->ops_cache_key = $this->getCurrentCacheKey();
		return $this->load($this->ops_cache_key);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function remove() {
		$this->opa_browse = array();
		return ExternalCache::delete($this->ops_cache_key, 'Browse');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCacheKey() {
		$this->ops_cache_key = $this->getCurrentCacheKey();
		return $this->ops_cache_key;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setFacet($ps_facet_name, &$pa_facet_data) {
		$this->opa_browse['facets'][$ps_facet_name] = $pa_facet_data;
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setFacets(&$pa_facet_data) {
		$this->opa_browse['facets'] = $pa_facet_data;
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getFacets() {
		return $this->opa_browse['facets'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getFacet($ps_facet_name) {
		return (isset($this->opa_browse['facets'][$ps_facet_name]) ? $this->opa_browse['facets'][$ps_facet_name] : null);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setParameter($ps_param, $pm_value) {
		$this->opa_browse['params'][$ps_param] = $pm_value;
		$this->ops_cache_key = $this->getCurrentCacheKey();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getParameters() {
		return $this->opa_browse['params'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getParameter($ps_param) {
		return (isset($this->opa_browse['params'][$ps_param]) ? $this->opa_browse['params'][$ps_param] : null);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setResults($pa_results) {
		$this->opa_browse['results'] = is_array($pa_results) ? $pa_results : array();
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function numResults() {
		return is_array($this->opa_browse['results']) ? sizeof($this->opa_browse['results']) : 0;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setTypeRestrictions($pa_type_restrictions) {
		$this->opa_browse['type_restrictions'] = is_array($pa_type_restrictions) ? $pa_type_restrictions : array();
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getTypeRestrictions() {
		return is_array($this->opa_browse['type_restrictions']) ? $this->opa_browse['type_restrictions'] : array();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setSourceRestrictions($pa_source_restrictions) {
		$this->opa_browse['source_restrictions'] = is_array($pa_source_restrictions) ? $pa_source_restrictions : array();
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getSourceRestrictions() {
		return is_array($this->opa_browse['source_restrictions']) ? $this->opa_browse['source_restrictions'] : array();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getResults() {
		return $this->opa_browse['results'];
	}
	# ------------------------------------------------------
	public function getCurrentCacheKey() {
		if(!is_array($va_params = $this->getParameters())) { $va_params = array(); }
		if (!is_array($va_params['criteria'])) { $va_params['criteria'] = array(); }
		if(!is_array($va_type_restrictions = $this->getTypeRestrictions())) { $va_type_restrictions = array(); }
		if(!is_array($va_source_restrictions = $this->getSourceRestrictions())) { $va_source_restrictions = array(); }

		return BrowseCache::makeCacheKey($va_params, $va_type_restrictions,$va_source_restrictions);
	}
	# ------------------------------------------------------
	public static function makeCacheKey($pa_params, $pa_type_restrictions, $pa_source_restrictions) {
		if (!is_array($pa_params['criteria'])) { $pa_params['criteria'] = array(); }

		return md5($pa_params['context'].'/'.$pa_params['table_num'].'/'.print_r($pa_params['criteria'], true).'/'.print_r($pa_type_restrictions, true).'/'.print_r($pa_source_restrictions, true));
	}
	# ------------------------------------------------------
	# Global parameters - available to all browses
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getGlobalParameter($ps_param) {
		if(ExternalCache::contains("browse_global_{$ps_param}", 'Browse')) {
			return ExternalCache::fetch("browse_global_{$ps_param}", 'Browse');
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setGlobalParameter($ps_param, $pm_value) {
		ExternalCache::save("browse_global_{$ps_param}", $pm_value, 'Browse');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	/*static public function _writeBrowseCacheToDisk() {
		if (is_array(BrowseCache::$s_data )) {
			$o_cache =  BrowseCache::_getCacheObject();
			foreach(BrowseCache::$s_data as $vs_cache_key => $va_cached_data) {
				$o_cache->save($va_cached_data, $vs_cache_key, array('ca_browse_cache'));
			}
		}
	}*/
	# ------------------------------------------------------
}

//register_shutdown_function("BrowseCache::_writeBrowseCacheToDisk");
