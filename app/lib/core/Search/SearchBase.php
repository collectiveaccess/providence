<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchBase.php : Base class for searches
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/core/BaseFindEngine.php");
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
	
	class SearchBase extends BaseFindEngine {
		# ------------------------------------------------
		protected $opo_datamodel;
		protected $opo_db;
		protected $opo_app_config;
		protected $opo_search_config;
		protected $opo_search_indexing_config;
		protected $opo_engine;
		
		static $s_fields_to_index_cache = array();
		# ------------------------------------------------
		public function __construct($opo_db=null, $ps_engine=null) {			
			$this->opo_datamodel = Datamodel::load();
			$this->opo_app_config = Configuration::load();
			$this->opo_search_config = Configuration::load($this->opo_app_config->get("search_config"));
			$this->opo_search_indexing_config = Configuration::load($this->opo_search_config->get("search_indexing_config"));			

			// load search engine plugin as configured by the 'search_engine_plugin' directive in the main app config file
			if (!($this->opo_engine = SearchBase::newSearchEngine($ps_engine, 57))) {
				die("Couldn't load configured search engine plugin. Check your application configuration and make sure 'search_engine_plugin' directive is set properly.");
			}
	
			$this->opo_db = $opo_db ? $opo_db : new Db();
		}
		# ------------------------------------------------
		/** 
		 * You pass this the ** plugin file name ** (eg. 'Lucene'), not the actual class name (eg. WLPlugSearchEngineLucene)
		 * and you get back an instance of the plugin
		 */
		static public function newSearchEngine($ps_plugin_name=null, $pn_table_num=null) {		
			if (!$ps_plugin_name) {
				$o_config = Configuration::load();
				$ps_plugin_name = $o_config->get('search_engine_plugin');
			}
			if (!file_exists(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/'.$ps_plugin_name.'.php')) { return null; }
			
			require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/'.$ps_plugin_name.'.php');
			
			$ps_classname = 'WLPlugSearchEngine'.$ps_plugin_name;
			return new $ps_classname;
		}
		# ------------------------------------------------
		# Utils
		# ------------------------------------------------
		/**
		 *
		 */
		public function getFieldsToIndex($pm_subject_table, $pm_content_table=null) {
			if (isset(SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table])) {
				return SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table];
			}
			if (is_numeric($pm_subject_table)) {
				$vs_subject_table = $this->opo_datamodel->getTableName($pm_subject_table);
			} else {
				$vs_subject_table = $pm_subject_table;
			}
	
			if ($pm_content_table == null) {
				$vs_content_table = $vs_subject_table;
			} else {
				if (is_numeric($pm_content_table)) {
					$vs_content_table = $this->opo_datamodel->getTableName($pm_content_table);
				} else {
					$vs_content_table = $pm_content_table;
				}
			}
			if(!($va_info = $this->opo_search_indexing_config->getAssoc($vs_subject_table))) {
				return SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table] = SearchBase::$s_fields_to_index_cache[$vs_subject_table.'/'.$vs_content_table] = null;
			}
	
			$va_fields_to_index = $va_info[$vs_content_table]['fields'];
			if (isset($va_fields_to_index['_metadata'])) {
				$va_data = $va_fields_to_index['_metadata'];
				unset($va_fields_to_index['_metadata']);
				
				$t_subject = $this->opo_datamodel->getInstanceByTableName($vs_content_table, false);
				$va_field_data = $t_subject->getApplicableElementCodes(null, false, false);
				foreach($va_field_data as $vn_element_id => $vs_element_code) {
					$va_fields_to_index['_ca_attribute_'.$vn_element_id] = $va_data;
				}
			}
			return SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table] = SearchBase::$s_fields_to_index_cache[$vs_subject_table.'/'.$vs_content_table] = $va_fields_to_index;
	
		}
		# ------------------------------------------------
		/**
		 * Returns list of tables which provide indexing for the specified subject table
		 */
		public function getRelatedIndexingTables($pm_subject_table) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = $this->opo_datamodel->getTableName($pm_subject_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->get($pm_subject_table)) {
				return null;
			}
	
			unset($va_info['_access_points']);
			unset($va_info[$pm_subject_table]);
			$va_tables = array_keys($va_info);
			return $va_tables;
		}
		# ------------------------------------------------
		public function getTableIndexingInfo($pm_subject_table, $pm_content_table) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = $this->opo_datamodel->getTableName($pm_subject_table);
			}
			if (is_numeric($pm_content_table)) {
				$pm_content_table = $this->opo_datamodel->getTableName($pm_content_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->get($pm_subject_table)) {
				return null;
			}
			// 'tables' is optional for one-many relations but its absence would be felt upstream
			// so we add it here as an empty array when it's not already present
			if (!isset($va_info[$pm_content_table]['tables']) || !$va_info[$pm_content_table]['tables']) { $va_info[$pm_content_table]['tables'] = array(); }
			return $va_info[$pm_content_table];
		}
		# ------------------------------------------------
		public function getIndexedTables() {
			return $this->opo_search_indexing_config->getAssocKeys();
		}
		# ------------------------------------------------
		public function getFieldOptions($pm_subject_table, $pm_content_table, $ps_fieldname) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = $this->opo_datamodel->getTableName($pm_subject_table);
			}
			if (is_numeric($pm_content_table)) {
				$pm_content_table = $this->opo_datamodel->getTableName($pm_content_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->get($pm_subject_table)) {
				return null;
			}
	
			return $va_info[$pm_content_table]['fields'][$ps_fieldname];
	
		}
		# -------------------------------------------------
		public function getAccessPoints($pm_subject_table) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = $this->opo_datamodel->getTableName($pm_subject_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->get($pm_subject_table)) {
				return null;
			}
			$va_access_points =  $va_info['_access_points'];
			foreach($va_access_points as $vs_k => $va_v) {
				$va_access_points[mb_strtolower($vs_k)] = $va_v;
			}
			return is_array($va_access_points) ? $va_access_points : array();
		}
		# -------------------------------------------------
		public function getAccessPointInfo($pm_subject_table, $ps_access_point) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = $this->opo_datamodel->getTableName($pm_subject_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->get($pm_subject_table)) {
				return null;
			}
	
			return $va_info['_access_points'][$ps_access_point];
		}
		# -------------------------------------------------
		/**
		 * Provides a table instance for the specified table (table name or number are both accepted)
		 * This caches instances using the datamodel instance caching
		 */
		public function getTableInstance($pm_table) {
			if (is_numeric($pm_table)) {
				return $this->opo_datamodel->getInstanceByTableNum($pm_table, true);
			} else {
				return $this->opo_datamodel->getInstanceByTableName($pm_table, true);
			}
		}
		# ------------------------------------------------------------------
	}
?>