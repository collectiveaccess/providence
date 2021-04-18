<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/SearchBase.php : Base class for search
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2019 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/BaseFindEngine.php");
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__."/Db.php");
	
	class SearchBase extends BaseFindEngine {
		# ------------------------------------------------
		protected $opo_app_config;
		protected $opo_search_config;
		protected $opo_search_indexing_config;
		protected $opo_engine;
		
		static $s_fields_to_index_cache = array();
		# ------------------------------------------------
		/**
		 * @param Db $po_db A database client object to use rather than creating a new connection. [Default is to create a new database connection]
		 * @param string $ps_engine Name of the search engine to use. [Default is the engine configured using "search_engine_plugin" in app.conf]
		 * @param bool $pb_load_engine if set to true (default is false) we don't attempt to load an engine instance. this is useful if you just want to use SearchBase for the utility methods
		 */
		public function __construct($po_db=null, $ps_engine=null, $pb_load_engine=true) {
			parent::__construct($po_db);
			
			$this->opo_app_config = Configuration::load();
			$this->opo_search_config = Configuration::load(__CA_CONF_DIR__.'/search.conf');
			$this->opo_search_indexing_config = Configuration::load(__CA_CONF_DIR__.'/search_indexing.conf');			

			// load search engine plugin as configured by the 'search_engine_plugin' directive in the main app config file
			if($pb_load_engine) {
				if (!($this->opo_engine = SearchBase::newSearchEngine($ps_engine, $po_db))) {
					die("Couldn't load configured search engine plugin. Check your application configuration and make sure 'search_engine_plugin' directive is set properly.");
				}
			}
		}
		# ------------------------------------------------
		/** 
		 * Get search engine instance
		 *
		 * @param string $ps_plugin_name A valid plugin file name (eg. 'ElasticSearch'), not the actual class name (eg. WLPlugSearchEngineElasticSearch)
		 * @return WLPlugSearchEngine instance or null if engine is invalid
		 */
		static public function newSearchEngine($ps_plugin_name=null, $po_db=null) {		
			if (!$ps_plugin_name) {
				$o_config = Configuration::load();
				$ps_plugin_name = $o_config->get('search_engine_plugin');
			}
			if (!file_exists(__CA_LIB_DIR__.'/Plugins/SearchEngine/'.$ps_plugin_name.'.php')) { return null; }
			
			require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/'.$ps_plugin_name.'.php');
			
			$ps_classname = 'WLPlugSearchEngine'.$ps_plugin_name;
			return new $ps_classname($po_db);
		}
		# ------------------------------------------------
		/**
		 * Set the database client 
		 *
		 * @param Db $po_db
		 * @return void
		 */
		public function setDb($po_db) {
			$this->db = $po_db;
		}
		# ------------------------------------------------
		/**
		 * Get the current database client
		 *
		 * @return Db
		 */
		public function getDb() {
			return $this->db;
		}
		# ------------------------------------------------
		# Utilities
		# ------------------------------------------------
		/**
		 * Fetch list of fields to index for the subject table
		 *
		 * @param mixed $pm_subject_table Name or number of table indexing is to be applied to
		 * @param mixed $pm_content_table Name or number of table containing content being indexed. [Default is $pm_subject_table]
		 * @param array $pa_options Options include:
		 *      currentValueFields = Return fields for current value indexing instead of standard indexing field set. [Default is false]
		 *      clearCache = Clear field cache. [Default is false]
		 *      intrinsicOnly = Return only intrinsic fields. [Default is false]
		 *
		 * @return array
		 */
		public function getFieldsToIndex($pm_subject_table, $pm_content_table=null, $pa_options=null) {
			if(caGetOption('clearCache', $pa_options, false)) {
				self::clearCache();
			}

			$vs_key = caMakeCacheKeyFromOptions($pa_options);
			if (isset(SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table.'/'.$vs_key])) {
				return SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table.'/'.$vs_key];
			}
			if (is_numeric($pm_subject_table)) {
				$vs_subject_table = Datamodel::getTableName($pm_subject_table);
			} else {
				$vs_subject_table = $pm_subject_table;
			}
	
			if ($pm_content_table == null) {
				$vs_content_table = $vs_subject_table;
			} else {
				if (is_numeric($pm_content_table)) {
					$vs_content_table = Datamodel::getTableName($pm_content_table);
				} else {
					$vs_content_table = $pm_content_table;
				}
			}
			if(!($va_info = $this->opo_search_indexing_config->getAssoc($vs_subject_table))) {
				return SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table.'/'.$vs_key] = SearchBase::$s_fields_to_index_cache[$vs_subject_table.'/'.$vs_content_table.'/'.$vs_key] = null;
			}
	
	        if ($current_value_fields_only = caGetOption('currentValueFields', $pa_options, false)) {
	            $va_fields_to_index = is_array($va_info[$vs_content_table]['current_values']) ? $va_info[$vs_content_table]['current_values'] : [];
	        } else {
			    $va_fields_to_index = [0 => $va_info[$vs_content_table]['fields']];
	        }
			$t_subject = Datamodel::getInstanceByTableName($vs_content_table, false);
			
			foreach($va_fields_to_index as $p => $field_list) {
                if (caGetOption('intrinsicOnly', $pa_options, false)) {
                    unset($field_list['_metadata']);
                    foreach($field_list as $vs_f => $va_data) {
                        if (substr($vs_f, 0, 14) === '_ca_attribute_') { unset($field_list[$vs_f]); continue; }
                        if (!$t_subject->hasField($vs_f)) { unset($field_list[$vs_f]); continue; }
                        if (($vs_start = $t_subject->getFieldInfo($vs_f, 'START') && ($vs_end = $t_subject->getFieldInfo($vs_f, 'END')))) {
                            $field_list[$vs_start] = $va_data;
                            $field_list[$vs_end] = $va_data;
                            unset($field_list[$vs_f]);
                        
                        }
                    }
                
                    return $field_list;
                }
            
                // Expand "_metadata" to all available metadata elements
                if (isset($field_list['_metadata'])) {
                    $va_data = $field_list['_metadata'];
                    unset($field_list['_metadata']);

                    $vb_include_non_root_elements = caGetOption('includeNonRootElements', $pa_options, false);
                    $va_field_data = $t_subject->getApplicableElementCodes(null, $vb_include_non_root_elements, false);
                    foreach($va_field_data as $vn_element_id => $vs_element_code) {
                        $field_list['_ca_attribute_'.$vn_element_id] = $va_data;
                    }
                }
            
                // Convert specific attribute codes to element_ids
                if (is_array($field_list)) {
                    foreach($field_list as $vs_f => $va_info) {
                        if (
                        	((substr($vs_f, 0, 14) === '_ca_attribute_') && preg_match('!^_ca_attribute_([A-Za-z]+[A-Za-z0-9_]*)$!', $vs_f, $va_matches) && ($element_id = ca_metadata_elements::getElementID($va_matches[1])))
                        	||
                        	($element_id = ca_metadata_elements::getElementID($vs_f))
                        ) {
                            unset($field_list[$vs_f]);
                            
                            if (!caGetOption('DONT_INDEX', $va_info, false)) {
                            	$field_list["_ca_attribute_{$element_id}"] = $va_info;
                            }
                        }
                    }
                }

                // always index type id if applicable and not already indexed
                if(!$current_value_fields_only && method_exists($t_subject, 'getTypeFieldName') && ($vs_type_field = $t_subject->getTypeFieldName()) && !isset($field_list[$vs_type_field])) {
                    $field_list[$vs_type_field] = array('STORE', 'DONT_TOKENIZE');
                }
                
                $va_fields_to_index[$p] = $field_list;
		    }
		    if (!$current_value_fields_only) { $va_fields_to_index = $va_fields_to_index[0]; }
			return SearchBase::$s_fields_to_index_cache[$pm_subject_table.'/'.$pm_content_table.'/'.$vs_key] = SearchBase::$s_fields_to_index_cache[$vs_subject_table.'/'.$vs_content_table.'/'.$vs_key] = $va_fields_to_index;
		}
		# ------------------------------------------------
		/**
		 * 
		 *
		 * @param mixed $pm_subject_table Name or number of table indexing is to be applied to
		 *
		 * @return array
		 */
		public function getHistoryTrackingPoliciesToIndex($pm_subject_table, $pa_options=null) {
		    if (is_numeric($pm_subject_table)) {
				$vs_subject_table = Datamodel::getTableName($pm_subject_table);
			} else {
				$vs_subject_table = $pm_subject_table;
			}
			
			if(!($va_info = $this->opo_search_indexing_config->getAssoc($vs_subject_table))) {
			    return [];
			}
			
			return is_array($va_info[$vs_subject_table]['current_values']) ? $va_info[$vs_subject_table]['current_values'] : [];
		}
		# ------------------------------------------------
		public static function clearCache() {
			self::$s_fields_to_index_cache = array();
		}
		# ------------------------------------------------
		/**
		 * Returns list of tables which provide indexing for the specified subject table
		 *
		 * @param mixed $pm_subject_table Name or number of table indexing is to be applied to
		 *
		 * @return array
		 */
		public function getRelatedIndexingTables($pm_subject_table) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = Datamodel::getTableName($pm_subject_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->getAssoc($pm_subject_table)) {
				return null;
			}
	
			unset($va_info['_access_points']);
			
			$vs_label_table = null;
			if (($t_instance = Datamodel::getInstanceByTableName($pm_subject_table, true)) && (method_exists($t_instance, 'getLabelTableName'))) {
				$vs_label_table = $t_instance->getLabelTableName();
			}
			
			if (!isset($va_info[$pm_subject_table]['related']) && (!$vs_label_table || !isset($va_info[$vs_label_table]['related']))) { unset($va_info[$pm_subject_table]); }	// remove subject table _unless_ 'related' indexing is enabled in subject or subject's label
			$va_tables = array_keys($va_info);
			return $va_tables;
		}
		# ------------------------------------------------
		/**
		 * Fetch list of tables to traverse when indexing content in the content table against the subject table
		 *
		 * @param mixed $pm_subject_table Name or number of table indexing is to be applied to
		 * @param mixed $pm_content_table Name or number of table containing content being indexed. [Default is $pm_subject_table]
		 *
		 * @return array
		 */
		public function getTableIndexingInfo($pm_subject_table, $pm_content_table) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = Datamodel::getTableName($pm_subject_table);
			}
			if (is_numeric($pm_content_table)) {
				$pm_content_table = Datamodel::getTableName($pm_content_table);
			}
			if(!is_array($va_info = $this->opo_search_indexing_config->getAssoc($pm_subject_table))) {
				return null;
			}
			// 'tables' is optional for one-many relations but its absence would be felt upstream
			// so we add it here as an empty array when it's not already present
			if (!isset($va_info[$pm_content_table]['tables']) || !$va_info[$pm_content_table]['tables']) { $va_info[$pm_content_table]['tables'] = array(); }
			return $va_info[$pm_content_table];
		}
		# ------------------------------------------------
		/**
		 * Fetch list of all tables to be indexed
		 *
		 * @return array
		 */
		public function getIndexedTables() {
			return $this->opo_search_indexing_config->getAssocKeys();
		}
		# ------------------------------------------------
		/**
		 * Fetch options to field being indexed for content against subject
		 *
		 * @param mixed $pm_subject_table Name or number of table indexing is to be applied to
		 * @param mixed $pm_content_table Name or number of table containing content being indexed.
		 * @param string $ps_field_name The name of the field to be indexed
		 *
		 * @return array
		 */
		public function getFieldOptions($pm_subject_table, $pm_content_table, $ps_fieldname) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = Datamodel::getTableName($pm_subject_table);
			}
			if (is_numeric($pm_content_table)) {
				$pm_content_table = Datamodel::getTableName($pm_content_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->getAssoc($pm_subject_table)) {
				return null;
			}
	
			return $va_info[$pm_content_table]['fields'][$ps_fieldname];
	
		}
		# -------------------------------------------------
		/**
		 * Fetch list of access points for subject table
		 *
		 * @param 
		 */
		public function getAccessPoints($pm_subject_table) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = Datamodel::getTableName($pm_subject_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->getAssoc($pm_subject_table)) {
				return null;
			}
			$va_access_points =  $va_info['_access_points'];
			foreach($va_access_points as $vs_k => $va_v) {
				$va_access_points[mb_strtolower($vs_k)] = $va_v;
			}
			return is_array($va_access_points) ? $va_access_points : array();
		}
		# -------------------------------------------------
		/**
		 * Fetch info about an access point
		 *
		 * @param mixed $pm_subject_table Name or number of table indexing is to be applied to
		 * @param string $ps_access_point The name of the access point
		 *
		 * @return array
		 */
		public function getAccessPointInfo($pm_subject_table, $ps_access_point) {
			if (is_numeric($pm_subject_table)) {
				$pm_subject_table = Datamodel::getTableName($pm_subject_table);
			}
			if(!$va_info = $this->opo_search_indexing_config->getAssoc($pm_subject_table)) {
				return null;
			}
	
			return $va_info['_access_points'][$ps_access_point];
		}
		# -------------------------------------------------
		/**
		 * Provides a model instance for the specified table (table name or number are 
		 * both accepted) using datamodel instance caching
		 *
		 * @param mixed $pm_table_name_or_num A valid table name or number
		 * @return BaseModel A model instance or null if the table is invalid
		 */
		public function getTableInstance($pm_table_name_or_num) {
			return Datamodel::getInstance($pm_table_name_or_num, true);
		}
		# ------------------------------------------------------------------
	}
