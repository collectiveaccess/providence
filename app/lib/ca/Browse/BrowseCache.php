<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Browse/BrowseCache.php : Caching for BrowseEngine
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');
 
	class BrowseCache {
		# ------------------------------------------------------
		/**
		 * @var Cache key for currently loaded browse
		 */
		private $ops_cache_key;
		
		/**
		 * @var Zend_Cache object used to store cached browse data
		 */
		private $opo_cache;
		
		/**
		 * @var Working copy of browse data
		 */
		private $opa_browse;
		
		/**
		 * @var In memory data cache
		 */
		 static $s_data;
		
		# ------------------------------------------------------
		/**
		 *
		 */
		public function __construct($ps_cache_key=null) {
			
			$this->opo_cache = BrowseCache::_getCacheObject();
			
			$this->opa_browse = array();
		
			if ($ps_cache_key) {
				$this->load($ps_cache_key);
			}
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		 public function load($ps_cache_key) {
		 //	if ($this->ops_cache_key == $ps_cache_key) { return true; } // already loaded
		 	if (is_array(BrowseCache::$s_data[$ps_cache_key])) { 
		 		$this->opa_browse = BrowseCache::$s_data[$ps_cache_key];
				$this->ops_cache_key = $ps_cache_key; 
		 		return true; 
		 	}
		 		
		 	if (!$this->opo_cache) { return false; }
			if (is_array($va_cached_data = $this->opo_cache->load((string)$ps_cache_key)) && sizeof($va_cached_data)) { 
				$this->opa_browse = $va_cached_data;
				$this->ops_cache_key = $ps_cache_key; 
				BrowseCache::$s_data[$ps_cache_key] = $va_cached_data;
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
		 	if (!$this->opo_cache) { return false; }
		 	$this->ops_cache_key = $this->getCurrentCacheKey();
			BrowseCache::$s_data[$this->ops_cache_key] = $this->opa_browse;
			return true;
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function remove() {
		 	if (!$this->opo_cache) { return false; }
		 	$this->opa_browse = array();
		 	BrowseCache::$s_data[$this->ops_cache_key]  = null;
		 	return $this->opo_cache->remove($this->ops_cache_key);
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function clearAll() {
		 	if (!$this->opo_cache) { return false; }
		 	BrowseCache::$s_data = array();
		 	return $this->opo_cache->clean(
		 		 Zend_Cache::CLEANING_MODE_MATCHING_TAG,
		 		 array('ca_browse_cache')
		 		);
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
		 public function getResults() {
		 	return $this->opa_browse['results'];
		 }
		 # ------------------------------------------------------
		 public function getCurrentCacheKey() {
			if(!is_array($va_params = $this->getParameters())) { $va_params = array(); }
			if (!is_array($va_params['criteria'])) { $va_params['criteria'] = array(); }
			if(!is_array($va_type_restrictions = $this->getTypeRestrictions())) { $va_type_restrictions = array(); }
			
			return BrowseCache::makeCacheKey($va_params, $va_type_restrictions);
		}
		# ------------------------------------------------------
		 public static function makeCacheKey($pa_params, $pa_type_restrictions) {
			if (!is_array($pa_params['criteria'])) { $pa_params['criteria'] = array(); }
			
			return md5($pa_params['context'].'/'.$pa_params['table_num'].'/'.print_r($pa_params['criteria'], true).'/'.print_r($pa_type_restrictions, true));
		}
		# ------------------------------------------------------
		# Global parameters - available to all browses
		# ------------------------------------------------------
		/**
		 *
		 */
		 public function getGlobalParameter($ps_param) {
		 	return $this->opo_cache->load('browse_global_'.$ps_param);
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function setGlobalParameter($ps_param, $pm_value) {
		 	if (!$this->opo_cache) { return false; }
		 	$this->opo_cache->save($pm_value, 'browse_global_'.$ps_param);
		 }
		# ------------------------------------------------------
		/**
		  *
		  */
		static public function _getCacheObject() {
			$o_config = Configuration::load();
			return caGetCacheObject('ca_browse_'.$o_config->get('app_name'));
		}
		# ------------------------------------------------------
		/**
		  *
		  */
		static public function _writeBrowseCacheToDisk() {
			if (is_array(BrowseCache::$s_data )) {
				$o_cache =  BrowseCache::_getCacheObject();
				foreach(BrowseCache::$s_data as $vs_cache_key => $va_cached_data) {
					$o_cache->save($va_cached_data, $vs_cache_key, array('ca_browse_cache'));
				}
			}
		}
		# ------------------------------------------------------
	}
	
	register_shutdown_function("BrowseCache::_writeBrowseCacheToDisk");
?>