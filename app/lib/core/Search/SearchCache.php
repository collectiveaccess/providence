<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchCache.php : Caching for SearchEngine
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');
 
	class SearchCache {
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
		 * @var Working copy of search data
		 */
		private $opa_search;
		
		# ------------------------------------------------------
		/**
		 *
		 */
		public function __construct($ps_search=null, $pn_table_num=null, $pa_options=null) {
			$va_frontend_options = array(
				'lifetime' => 3600, 				/* cache lives 1 hour */
				'logging' => false,					/* do not use Zend_Log to log what happens */
				'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
				'automatic_cleaning_factor' => 100, 	/* no automatic cache cleaning */
				'automatic_serialization' => true	/* we store arrays, so we have to enable that */
			);
			
			$o_config = Configuration::load();
			$va_backend_options = array(
				'cache_dir' =>  __CA_APP_DIR__.'/tmp',		/* where to store cache data? */
				'file_locking' => true,				/* cache corruption avoidance */
				'read_control' => false,			/* no read control */
				'file_name_prefix' => 'ca_search_'.$o_config->get('app_name'),	/* prefix of cache files */
				'cache_file_perm' => 0700			/* permissions of cache files */
			);


			try {
				$this->opo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);
			} catch (exception $e) {
				// noop
			}
			
			$this->opa_search = array();
		
			if ($ps_search && $pn_table_num) {
				$this->load($ps_search, $pn_table_num, $pa_options);
			}
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		 public function load($ps_search, $pn_table_num, $pa_options) {
		 	if (!$this->opo_cache) { return false; }
		 	$ps_cache_key = $this->generateCacheKey($ps_search, $pn_table_num, $pa_options);
			if (is_array($va_cached_data = $this->opo_cache->load($ps_cache_key)) && sizeof($va_cached_data)) { 
				$this->opa_search = $va_cached_data;
				$this->ops_cache_key = $ps_cache_key;
				return true; 
		 	}
		 	
		 	return false;
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function clear() {
		 	$this->opa_search = array();
		 	return true;
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function save($ps_search, $pn_table_num, $pa_results, $pa_params=null, $pa_type_restrictions=null, $pa_options=null) {
		 	if (!$this->opo_cache) { return false; }
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
		 	
		 	return $this->opo_cache->save($this->opa_search, $this->ops_cache_key, array('ca_search_cache'));
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function remove() {
		 	if (!$this->opo_cache) { return false; }
		 	$this->opa_search = array();
		 	return $this->opo_cache->remove($this->ops_cache_key);
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function clearAll() {
		 	if (!$this->opo_cache) { return false; }
		 	return $this->opo_cache->clean(
		 		 Zend_Cache::CLEANING_MODE_MATCHING_TAG,
		 		 array('ca_search_cache')
		 		);
		 }
		# ------------------------------------------------------
		/**
		 *
		 */
		 public function getCacheKey() {
		 	return $this->ops_cache_key;
		 }
		# ------------------------------------------------------
		/**
		 *
		 */
		 public function setParameter($ps_param, $pm_value) {
		 	$this->opa_search['params'][$ps_param] = $pm_value;
		 }
		# ------------------------------------------------------
		/**
		 *
		 */
		 public function getParameters() {
		 	return $this->opa_search['params'];
		 }
		 # ------------------------------------------------------
		/**
		 *
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
			return md5($ps_search.'/'.$pn_table_num.'/'.print_R($pa_options, true));
		}
		# ------------------------------------------------------
		# Global parameters - available to all searches
		# ------------------------------------------------------
		/**
		 *
		 */
		 public function getGlobalParameter($ps_param) {
		 	return $this->opo_cache->load('search_global_'.$ps_param);
		 }
		 # ------------------------------------------------------
		/**
		 *
		 */
		 public function setGlobalParameter($ps_param, $pm_value) {
		 	if (!$this->opo_cache) { return false; }
		 	$this->opo_cache->save($pm_value, 'search_global_'.$ps_param);
		 }
		# ------------------------------------------------------
	}
?>