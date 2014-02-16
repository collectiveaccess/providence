<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchIndexer.php : indexing of content for search
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2014 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/core/Search/SearchBase.php");
require_once(__CA_LIB_DIR__.'/core/Utils/Graph.php');
require_once(__CA_LIB_DIR__.'/core/Utils/Timer.php');
require_once(__CA_LIB_DIR__.'/core/Utils/CLIProgressBar.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');
require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');

class SearchIndexer extends SearchBase {
	# ------------------------------------------------

	private $opa_dependencies_to_update;
	
	/**
	 * Cache array for generated hierarchical paths
	 */
	static public $s_related_rows_joins_cache = array();
	
	/**
	 * Cache array for SQL joins used to fetch related records indexing
	 */
	static public $s_related_fields_joins_cache = array();	
	
	/**
	 * Cache array for generated hierarchical paths
	 */
	static public $s_hier_path_cache = array();
	
	/** 
	 * Cache array for ca_metadata_element element_code => element_id conversions
	 */
	static public $s_SearchIndexer_element_id_cache = array();
	
	/** 
	 * Cache array for ca_metadata_element element_code => data type conversions
	 */
	static public $s_SearchIndexer_element_data_type_cache = array();
		
	/** 
	 * Cache array for ca_metadata_element element_code => list_id conversions
	 */
	static public $s_SearchIndexer_element_list_id_cache = array();
	
	/** 
	 * Cache array for field numbers
	 */
	static public $s_SearchIndexer_field_num_cache = array();
	
	private $opo_metadata_element = null;

	# ------------------------------------------------
	/**
	 * Constructor takes Db() instance which it uses for all database access. You should pass an instance in
	 * because all the database accesses need to be in the same transactional context as whatever else you're doing. In
	 * the case of Table::insert(), Table::update() and Table::delete() [the main users of , they're always in a transactional context
	 * so this is critical. If you don't pass an Db() instance then the constructor creates a new one, which is useful for
	 * cases where you're reindexing and not in a transaction.
	 */
	public function __construct($opo_db=null, $ps_engine=null) {
		require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
		parent::__construct($opo_db, $ps_engine);
		
		$this->opo_metadata_element = new ca_metadata_elements();
	}
	# -------------------------------------------------------
	/**
	 * Returns a list of tables the require indexing
	 */
	public function getIndexedTables() {
		$va_table_names = $this->opo_datamodel->getTableNames();
		
		$o_db = $this->opo_db;
		$va_tables_to_index = $va_tables_by_size = array();
		foreach($va_table_names as $vs_table) {
			$vn_table_num = $this->opo_datamodel->getTableNum($vs_table);
			$t_instance = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
			$va_fields_to_index = $this->getFieldsToIndex($vn_table_num);
			if (!is_array($va_fields_to_index) || (sizeof($va_fields_to_index) == 0)) {
				continue;
			}
			
			$qr_all = $o_db->query("SELECT count(*) c FROM $vs_table");
			$qr_all->nextRow();
			$vn_num_rows = (int)$qr_all->get('c');

			$va_tables_to_index[$vs_table] = array('name' => $vs_table, 'num' => $vn_table_num, 'count' => $vn_num_rows, 'displayName' => $t_instance->getProperty('NAME_PLURAL'));
			$va_tables_by_size[$vs_table] = $vn_num_rows;
		}
		
		asort($va_tables_by_size);
		$va_tables_by_size = array_reverse($va_tables_by_size);
		
		$va_sorted_tables = array();
		foreach($va_tables_by_size as $vs_table => $vn_count) {
			$va_sorted_tables[$va_tables_to_index[$vs_table]['num']] = $va_tables_to_index[$vs_table];
		}
		
		return $va_sorted_tables;
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function truncateIndex() {
		return $this->opo_engine->truncateIndex();
	}
	# -------------------------------------------------------
	/**
	 * Forces a full reindex of all rows in the database or, optionally, a single table
	 *
	 * @param array $pa_table_name
	 * @param array $pa_options Reindexing options:
	 *			showProgress
	 *			interactiveProgressDisplay
	 *			log
	 *			callback
	 */
	public function reindex($pa_table_names=null, $pa_options=null) {
		define('__CollectiveAccess_IS_REINDEXING__', 1);
		$t_timer = new Timer();
		
		
		$pb_display_progress = isset($pa_options['showProgress']) ? (bool)$pa_options['showProgress'] : true;
		$pb_interactive_display = isset($pa_options['interactiveProgressDisplay']) ? (bool)$pa_options['interactiveProgressDisplay'] : false;
		$ps_callback = isset($pa_options['callback']) ? (string)$pa_options['callback'] : false;
		
		if ($pa_table_names) {
			if (!is_array($pa_table_names)) { $pa_table_names = array($pa_table_names); }
			
			$va_table_names = array();
			foreach($pa_table_names as $vs_table) {
				if ($this->opo_datamodel->tableExists($vs_table)) {
					$vn_num = $this->opo_datamodel->getTableNum($vs_table);
					print "\nTRUNCATING {$vs_table}\n\n";
					$this->opo_engine->truncateIndex($vn_num);
					$t_instance = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
					$va_table_names[$vn_num] = array('name' => $vs_table, 'num' => $vn_num, 'displayName' => $t_instance->getProperty('NAME_PLURAL'));
				}
			}
			if (!sizeof($va_table_names)) { return false; }
		} else {
			// full reindex
			$this->opo_engine->truncateIndex();
			$va_table_names = $this->getIndexedTables();
		}
		
		$o_db = $this->opo_db;
		
		if ($pb_display_progress || $ps_callback) {
			$va_names = array();
			foreach($va_table_names as $vn_table_num => $va_table_info) {
				$va_names[] = $va_table_info['displayName'];
			}
			if ($pb_display_progress) {
				print "\nWILL INDEX [".join(", ", $va_names)."]\n\n";
			}
		}
		
		$vn_tc = 0;
		foreach($va_table_names as $vn_table_num => $va_table_info) {
			$vs_table = $va_table_info['name'];
			$t_table_timer = new Timer();
			$t_instance = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
			$vs_table_pk = $t_instance->primaryKey();

			$va_fields_to_index = $this->getFieldsToIndex($vn_table_num);
			if (!is_array($va_fields_to_index) || (sizeof($va_fields_to_index) == 0)) {
				continue;
			}


			$qr_all = $o_db->query("SELECT ".$t_instance->primaryKey()." FROM $vs_table");

			$vn_num_rows = $qr_all->numRows();
			if ($pb_display_progress) {
				print CLIProgressBar::start($vn_num_rows, _t('Indexing %1', $t_instance->getProperty('NAME_PLURAL')));
			}

			$vn_c = 0;
			while($qr_all->nextRow()) {
				$t_instance->load($qr_all->get($t_instance->primaryKey()));
				$t_instance->doSearchIndexing(array(), true, $this->opo_engine->engineName());
				if ($pb_display_progress && $pb_interactive_display) {
					print CLIProgressBar::next();
				}
				
				if (($ps_callback) && (!($vn_c % 100))) { 
					$ps_callback(
						$vn_c,
						$vn_num_rows,
						null, 
						null,
						(float)$t_timer->getTime(2),
						memory_get_usage(true),
						$va_table_names,
						$vn_table_num,
						$t_instance->getProperty('NAME_PLURAL'),
						$vn_tc+1
					); 
				}
				$vn_c++;
			}
			$qr_all->free();
			unset($t_instance);
			if ($pb_display_progress && $pb_interactive_display) {
				print CLIProgressBar::finish();
			}
			$this->opo_engine->optimizeIndex($vn_table_num);
			
			$vn_tc++;
		}
		
		if ($pb_display_progress) {
			print "\n\n\nDone! [Indexing for ".join(", ", $va_names)." took ".caFormatInterval((float)$t_timer->getTime(4))."]\n";
		}
		if ($ps_callback) { 
			$ps_callback(
				1,
				1, 
				_t('Elapsed time: %1', caFormatInterval((float)$t_timer->getTime(2))),
				_t('Index rebuild complete!'),
				(float)$t_timer->getTime(2),
				memory_get_usage(true),
				$va_table_names,
				null,
				null,
				sizeof($va_table_names)
			); 
		}
	}
	# ------------------------------------------------
	/**
	 * Fetches list of dependencies for a given table
	 */
	public function getDependencies($ps_subject_table){
		/* set up cache */
		$va_frontend_options = array(
			'lifetime' => null, 				/* cache lives forever (until manual destruction) */
			'logging' => false,					/* do not use Zend_Log to log what happens */
			'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
			'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
			'automatic_serialization' => true	/* we store arrays, so we have to enable that */
		);
		$vs_cache_dir = __CA_APP_DIR__.'/tmp';//$this->opo_app_config->get('site_home_dir').'/tmp';
		$va_backend_options = array(
			'cache_dir' => $vs_cache_dir,		/* where to store cache data? */
			'file_locking' => true,				/* cache corruption avoidance */
			'read_control' => false,			/* no read control */
			'file_name_prefix' => 'ca_cache',	/* prefix of cache files */
			'cache_file_perm' => 0700			/* permissions of cache files */
		);
		
		try {
			$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);
		} catch (Exception $e) {
			// return dependencies without caching
			return $this->__getDependencies($ps_subject_table);
		}
		/* handle total cache miss (completely new cache has been generated) */
		if (!(is_array($va_cache_data = $vo_cache->load('ca_table_dependency_array')))) {
    		$va_cache_data = array();
		}

		/* cache outdated? (i.e. changes to search_indexing.conf) */
		$va_configfile_stat = stat($this->opo_search_config->get('search_indexing_config'));
		if($va_configfile_stat['mtime'] != $vo_cache->load('ca_table_dependency_array_mtime')) {
			$vo_cache->save($va_configfile_stat['mtime'],'ca_table_dependency_array_mtime');
			$va_cache_data = array();
		}

		if(isset($va_cache_data[$ps_subject_table]) && is_array($va_cache_data[$ps_subject_table])) { /* cache hit */
			/* return data from cache */
			/* TODO: probably we should implement some checks for data consistency */
			return $va_cache_data[$ps_subject_table];
		} else { /* cache miss */
			/* build dependency graph, store it in cache and return it */
			$va_deps = $this->__getDependencies($ps_subject_table);
			$va_cache_data[$ps_subject_table] = $va_deps;
			$vo_cache->save($va_cache_data,'ca_table_dependency_array');
			return $va_deps;
		}
	}
	# ------------------------------------------------
	/**
	 * Generate hierarchical values for using in indexing of hierarchical values with INDEX_ANCESTORS enabled
	 */
	private function _genHierarchicalPath($pn_subject_row_id, $ps_field, $t_subject, $pa_options=null) {
		if (isset(SearchIndexer::$s_hier_path_cache[$vs_key = md5("{$pn_subject_row_id}/{$ps_field}/".print_R($pa_options, true))])) { 
			return SearchIndexer::$s_hier_path_cache[$vs_key];
		}
		$pn_start = caGetOption('INDEX_ANCESTORS_START_AT_LEVEL', $pa_options, 0);
		$pn_max_levels = caGetOption('INDEX_ANCESTORS_MAX_NUMBER_OF_LEVELS', $pa_options, null);
		$ps_delimiter = caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $pa_options, '; ');
		
		// Automagically generate hierarchical paths for preferred labels passed as label table + label field
		if (is_subclass_of($t_subject, "BaseLabel")) {
			$pn_subject_row_id = $t_subject->get($t_subject->getSubjectKey());
			$t_subject = $t_subject->getSubjectTableInstance();
			$ps_field = "preferred_labels.{$ps_field}";
		}
		$va_ids = $t_subject->getHierarchyAncestors($pn_subject_row_id, array('idsOnly' => true, 'includeSelf' => true));
		$vs_subject_tablename = $t_subject->tableName();
		
		if (is_array($va_ids) && sizeof($va_ids) > 0) {
			$qr_hier_res = $t_subject->makeSearchResult($vs_subject_tablename, $va_ids);
		
			$va_hier_values = array();
			while($qr_hier_res->nextHit()) {
				if ($vs_v = $qr_hier_res->get($vs_subject_tablename.".".$ps_field)) {
					$va_hier_values[] = $vs_v;
				}
			}
			$va_hier_values = array_reverse($va_hier_values);
			
			
			if ($pn_start > 0) {
				$va_hier_values = array_slice($va_hier_values, $pn_start);
			}
			if ($pn_max_levels > 0) {
				$va_hier_values = array_slice($va_hier_values, 0, $pn_max_levels);
			}
			
			return SearchIndexer::$s_hier_path_cache[$vs_key] = array('values' => $va_hier_values, 'path' => join($ps_delimiter, $va_hier_values));
		}
		return SearchIndexer::$s_hier_path_cache[$vs_key] = null;
	}
	# ------------------------------------------------
	/**
	 * Indexes single row in a table; this is the public call when one needs to index content.
	 * indexRow() will analyze the dependencies of the row being indexed and automatically
	 * apply the indexing of the row to all dependent rows in other tables.  (Note that while I call this
	 * a "public" call in fact you shouldn't need to call this directly. BaseModel.php does this for you
	 * during insert() and update().)
	 *
	 * For example, if you are indexing a row in table 'entities', then indexRow()
	 * will automatically apply the indexing not just to the entities record, but also
	 * to all objects, place_names, occurrences, lots, etc. that reference the entity.
	 * The dependencies are configured in the search_indices.conf configuration file.
	 *
	 * "subject" tablenum/row_id refer to the row **to which the indexing is being applied**. This may be the row being indexed
	 * or it may be a dependent row. The "content" tablenum/fieldnum/row_id parameters define the specific row and field being indexed.
	 * This is always the actual row being indexed. $pm_content is the content to be indexed and $pa_options is an optional associative
	 * array of indexing options passed through from the search_indices.conf (no options are defined yet - but will be soon)

	 */
	public function indexRow($pn_subject_tablenum, $pn_subject_row_id, $pa_field_data, $pb_reindex_mode=false, $pa_exclusion_list=null, $pa_changed_fields=null, $pa_old_values=null, $pa_options=null) {
		if (!$pb_reindex_mode && is_array($pa_changed_fields) && !sizeof($pa_changed_fields)) { return; }	// don't bother indexing if there are no changed fields
		
		$vs_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_tablenum);
		$t_subject = $this->getTableInstance($vs_subject_tablename, true);
		
		// Prevent endless recursive reindexing
		if (is_array($pa_exclusion_list[$pn_subject_tablenum]) && (isset($pa_exclusion_list[$pn_subject_tablenum][$pn_subject_row_id]))) { return; }
		
		
		$vb_reindex_children = false;
		
		$vs_subject_pk = $t_subject->primaryKey();
		if (!is_array($pa_changed_fields)) { $pa_changed_fields = array(); }
		
		foreach($pa_changed_fields as $vs_k => $vb_bool) {
			if (!isset($pa_field_data[$vs_k])) { $pa_field_data[$vs_k] = null; }
		}
		
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?
		
		foreach($this->opo_search_config->get('search_indexing_replacements') as $vs_to_replace => $vs_replacement){
			foreach($pa_field_data as $vs_k => &$vs_value) {
				if($vs_replacement=="nothing") {
					$vs_replacement="";
				}
				$vs_value = str_replace($vs_to_replace,$vs_replacement,$vs_value);
			}
		}
		
		if (!$pa_exclusion_list) { $pa_exclusion_list = array(); }
		$pa_exclusion_list[$pn_subject_tablenum][$pn_subject_row_id] = true;
			
		//
		// index fields in subject table itself
		//
		$va_fields_to_index = $this->getFieldsToIndex($pn_subject_tablenum);
		
		if(is_array($va_fields_to_index)) {
			
			foreach($va_fields_to_index as $vs_k => $va_data) {
				if (preg_match('!^ca_attribute_(.*)$!', $vs_k, $va_matches)) {
					if (!is_numeric($va_matches[1])) {
						if ($vn_x = $this->_getElementID($va_matches[1])) {
							$va_matches[1] = $vn_x;
						} else {
							unset($va_fields_to_index[$vs_k]);
							continue;
						}
					}
					unset($va_fields_to_index[$vs_k]);
					if ($va_data['DONT_INDEX']) {	// remove attribute from indexing list
						unset($va_fields_to_index['_ca_attribute_'.$va_matches[1]]);
					} else {
						$va_fields_to_index['_ca_attribute_'.$va_matches[1]] = $va_data;
					}
				}
			}
		}
		
		// 
		// If location in hierarchy has changed we need to reindex this record and all of its children
		//
		if ($t_subject->isHierarchical() && isset($pa_changed_fields['parent_id']) && $pa_changed_fields['parent_id'] && method_exists($t_subject, "makeSearchResult")) {
			$pb_reindex_mode = true;
			$vb_reindex_children = true;
		}
		
		$vb_started_indexing = false;
		if (is_array($va_fields_to_index)) {
			$this->opo_engine->startRowIndexing($pn_subject_tablenum, $pn_subject_row_id);
			$vb_started_indexing = true;
			foreach($va_fields_to_index as $vs_field => $va_data) {
				if (substr($vs_field, 0, 14) === '_ca_attribute_') {	
					//
					// Is attribute
					//
					$vs_v = $pa_field_data[$vs_field];
					if (!preg_match('!^_ca_attribute_(.*)$!', $vs_field, $va_matches)) { continue; }
					if ($vb_can_do_incremental_indexing && (!$pb_reindex_mode) && (!isset($pa_changed_fields[$vs_field]) || !$pa_changed_fields[$vs_field])) {
						continue;	// skip unchanged attribute value
					}

					if($va_data['DONT_INDEX'] && is_array($va_data['DONT_INDEX'])){
						$vb_cont = false;
						foreach($va_data["DONT_INDEX"] as $vs_exclude_type){
							if($this->_getElementID($vs_exclude_type) == intval($va_matches[1])){
								$vb_cont = true;
								break;
							}
						}
						if($vb_cont) continue; // skip excluded attribute type
					}
					
					$va_data['datatype'] = (int)$this->_getElementDataType($va_matches[1]);
					
					switch($va_data['datatype']) {
						case 0: 		// container
							// index components of complex multi-value attributes
							$va_attributes = $t_subject->getAttributesByElement($va_matches[1], array('row_id' => $pn_subject_row_id));
							
							if (sizeof($va_attributes)) { 
								foreach($va_attributes as $vo_attribute) {
									/* index each element of the container */
									foreach($vo_attribute->getValues() as $vo_value) {
										$vn_list_id = $this->_getElementListID($vo_value->getElementID());											
										$this->opo_engine->indexField($pn_subject_tablenum, 'A'.$vo_value->getElementID(), $vo_attribute->getAttributeID(), $vo_value->getDisplayValue($vn_list_id), $va_data);																																															
									}
								}
							} else {
								// we are deleting a container so cleanup existing sub-values
								$va_sub_elements = $this->opo_metadata_element->getElementsInSet($va_matches[1]);
								
								foreach($va_sub_elements as $vn_i => $va_element_info) {
									$this->opo_engine->indexField($pn_subject_tablenum, 'A'.$va_element_info['element_id'], $va_element_info['element_id'], '', $va_data);
								}
							}
							break;
						case 3:		// list
							// We pull the preferred labels of list items for indexing here. We do so for all languages. Note that
							// this only done for list attributes that are standalone and not a sub-element in a container. Perhaps
							// we should also index the text of sub-element lists, but it's not clear that it is a good idea yet. The list_id's of
							// sub-elements *are* indexed however, so advanced search forms passing ids instead of text will work.
							$va_tmp = array();
							if (is_array($va_attributes = $t_subject->getAttributesByElement($va_matches[1], array('row_id' => $pn_subject_row_id)))) {
								foreach($va_attributes as $vo_attribute) {
									foreach($vo_attribute->getValues() as $vo_value) {
										$va_tmp[$vo_attribute->getAttributeID()] = $vo_value->getDisplayValue();
									}
								}
							}
							
							$va_new_values = array();
							$t_item = new ca_list_items();
							$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_tmp, array('returnAllLocales' => true));
							
							foreach($va_labels as $vn_row_id => $va_labels_per_row) {
								foreach($va_labels_per_row as $vn_locale_id => $va_label_list) {
									foreach($va_label_list as $vs_label) {
										$va_new_values[$vn_row_id][$vs_label] = true;
									}
								}
							}
							
							foreach($va_tmp as $vn_attribute_id => $vn_item_id) {
								if(!$vn_item_id) { continue; }
								if(!isset($va_new_values[$vn_item_id]) || !is_array($va_new_values[$vn_item_id])) { continue; }
								$vs_v = join(' ;  ', array_merge(array($vn_item_id), array_keys($va_new_values[$vn_item_id])));	
								$this->opo_engine->indexField($pn_subject_tablenum, 'A'.$va_matches[1], $vn_attribute_id, $vs_v, $va_data);
							}
							
							break;
						default:
							$va_attributes = $t_subject->getAttributesByElement($va_matches[1], array('row_id' => $pn_subject_row_id));
							if (!is_array($va_attributes)) { break; }
							foreach($va_attributes as $vo_attribute) {
								foreach($vo_attribute->getValues() as $vo_value) {
									//if the field is a daterange type get content from start and end fields
									$va_field_list = $t_subject->getFieldsArray();
									if(in_array($va_field_list[$vs_field]['FIELD_TYPE'],array(FT_DATERANGE,FT_HISTORIC_DATERANGE))) {
										$start_field = $va_field_list[$vs_field]['START'];
										$end_field = $va_field_list[$vs_field]['END'];
										$pn_content = $pa_field_data[$start_field] . " - " .$pa_field_data[$end_field];
									} else {
										$pn_content = $vo_value->getDisplayValue();
									}
									$this->opo_engine->indexField($pn_subject_tablenum, 'A'.$va_matches[1], $vo_attribute->getAttributeID(), $pn_content, $va_data);
								}
							}
							break;
					}
				} else {
					//
					// Plain old field
					//
					if ($vb_can_do_incremental_indexing && (!$pb_reindex_mode) && (!isset($pa_changed_fields[$vs_field])) && ($vs_field != $vs_subject_pk) ) {	// skip unchanged
						continue;
					}
					
					if (!($vn_fld_num = $t_subject->fieldNum($vs_field))) { continue; }
					
					//
					// Hierarchical indexing in primary table
					//
					if (((isset($va_data['INDEX_ANCESTORS']) && $va_data['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_data))) {
						if ($t_subject && $t_subject->isHierarchical()) {
							$vn_fld_num = $t_subject->fieldNum($vs_field);
							if ($va_hier_values = $this->_genHierarchicalPath($pn_subject_row_id, $vs_field, $t_subject, $va_data)) {
								$this->opo_engine->indexField($pn_subject_tablenum, 'I'.$vn_fld_num, $pn_subject_row_id, join(" ", $va_hier_values['values']), $va_data);
								if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $va_data, false) !== false) {
									$this->opo_engine->indexField($pn_subject_tablenum, 'I'.$vn_fld_num, $pn_subject_row_id, $va_hier_values['path'], array_merge($va_data, array('DONT_TOKENIZE' => 1)));
								}
							}
							
							$va_children_ids = $t_subject->getHierarchyAsList($pn_subject_row_id, array('idsOnly' => true));
							
							if (!$pb_reindex_mode && is_array($va_children_ids) && sizeof($va_children_ids) > 0) {
								// trigger reindexing of children
								$o_indexer = new SearchIndexer($this->opo_db);
								$qr_children_res = $t_subject->makeSearchResult($vs_subject_tablename, $va_children_ids);
								while($qr_children_res->nextHit()) {
									$o_indexer->indexRow($pn_subject_tablenum, $vn_id=$qr_children_res->get($vs_subject_pk), array($vs_subject_pk => $vn_id, 'parent_id' => $qr_children_res->get('parent_id'), $vs_field => $qr_children_res->get($vs_field)), false, $pa_exclusion_list, array($vs_field => true), null);
								}
							}
							continue;
						}
					}
				
					// specialized identifier (idno) processing; used IDNumbering plugin to generate searchable permutations of identifier
					if (((isset($va_data['INDEX_AS_IDNO']) && $va_data['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $va_data)) && method_exists($t_subject, "getIDNoPlugInInstance") && ($o_idno = $t_subject->getIDNoPlugInInstance())) {
						$va_values = $o_idno->getIndexValues($pa_field_data[$vs_field]);
						$vn_fld_num = $t_subject->fieldNum($vs_field);
						$this->opo_engine->indexField($pn_subject_tablenum, 'I'.$vn_fld_num, $pn_subject_row_id, join(" ", $va_values), $va_data);
						continue;
					}
					
					$va_field_list = $t_subject->getFieldsArray();
					if(in_array($va_field_list[$vs_field]['FIELD_TYPE'],array(FT_DATERANGE,FT_HISTORIC_DATERANGE))) {
						// if the field is a daterange type get content from start and end fields
						$start_field = $va_field_list[$vs_field]['START'];
						$end_field = $va_field_list[$vs_field]['END'];
						$pn_content = $pa_field_data[$start_field] . " - " .$pa_field_data[$end_field];
					} else {
						$va_content = array();
						
						if (isset($va_field_list[$vs_field]['LIST_CODE']) && $va_field_list[$vs_field]['LIST_CODE']) {
							// Is reference to list item so index preferred label values
							$t_item = new ca_list_items((int)$pa_field_data[$vs_field]);
							$va_labels = $t_item->getPreferredDisplayLabelsForIDs(array((int)$pa_field_data[$vs_field]), array('returnAllLocales' => true));
							
							foreach($va_labels as $vn_label_row_id => $va_labels_per_row) {
								foreach($va_labels_per_row as $vn_locale_id => $va_label_list) {
									foreach($va_label_list as $vs_label) {
										$va_content[$vs_label] = true;
									}
								}
							}
							$va_content[$t_item->get('idno')] = true;
						}  else {
							// is this field related to something?
							if (is_array($va_rels = $this->opo_datamodel->getManyToOneRelations($vs_subject_tablename)) && ($va_rels[$vs_field])) {
								if (isset($va_rels[$vs_field])) {
									if ($pa_changed_fields[$vs_field]) {
										$pb_reindex_mode = true;	// trigger full reindex of record so it reflects text of related item (if so indexed)
									}
								}
								
								$this->opo_engine->indexField($pn_subject_tablenum, 'I'.$vn_fld_num, $pn_subject_row_id, $pn_content, $va_data);
							}
						}
						$va_content[$pa_field_data[$vs_field]] = true;
						
						$this->opo_engine->indexField($pn_subject_tablenum, 'I'.$vn_fld_num, $pn_subject_row_id, join(" ", array_keys($va_content)), $va_data);
						continue;
					}
					
					$this->opo_engine->indexField($pn_subject_tablenum, 'I'.$vn_fld_num, $pn_subject_row_id, $pn_content, $va_data);
				}
			}
		}
		
		// -------------------------------------
		//
		// index related fields
		//
		// Here's where we generate indexing on the subject from content in related rows (data stored externally to the subject row)
		// If the underlying engine doesn't support incremental indexing (if it can't change existing indexing for a row in-place, in other words)
		// then we need to do this every time we update the indexing for a row; if the engine *does* support incremental indexing then
		// we can just update the existing indexing with content from the changed fields.
		//
		// We also do this indexing if we're in "reindexing" mode. When reindexing is indicated it means that we need to act as if
		// we're indexing this row for the first time, and all indexing should be performed.
if (!$vb_can_do_incremental_indexing || $pb_reindex_mode) {
		if (is_array($va_related_tables = $this->getRelatedIndexingTables($pn_subject_tablenum))) {
			if (!$vb_started_indexing) {
				$this->opo_engine->startRowIndexing($pn_subject_tablenum, $pn_subject_row_id);
				$vb_started_indexing = true;
			}
			
			foreach($va_related_tables as $vs_related_table) {
				$vn_related_tablenum = $this->opo_datamodel->getTableNum($vs_related_table);
				$vs_related_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_related_tablenum);
				
				$t_rel = $this->opo_datamodel->getInstanceByTableNum($vn_related_tablenum, true);
				
				$va_fields_to_index = $this->getFieldsToIndex($pn_subject_tablenum, $vs_related_table);
				$va_table_info = $this->getTableIndexingInfo($pn_subject_tablenum, $vs_related_table);

				$va_field_list = array_keys($va_fields_to_index);
				
				$va_table_list_list = array(); //$va_table_info['tables'];
				$va_table_key_list = array(); //$va_table_info['keys'];
				
				if (isset($va_table_info['key']) && $va_table_info['key']) {
					$va_table_list_list = array('key' => array($vs_related_table));
					$va_table_key_list = array();
				} else {
					if ($pb_reindex_mode || (!$vb_can_do_incremental_indexing)) {
						$va_table_list_list = isset($va_table_info['tables']) ? $va_table_info['tables'] : null;
						$va_table_key_list = isset($va_table_info['keys']) ? $va_table_info['keys'] : null;
					}
				}
				
				if (!is_array($va_table_list_list) || !sizeof($va_table_list_list)) { continue; } //$va_table_list_list = array($vs_related_table => array()); }
			
				foreach($va_table_list_list as $vs_list_name => $va_linking_tables) {
					array_push($va_linking_tables, $vs_related_table);
					$vs_left_table = $vs_subject_tablename;
	
					$va_joins = array();
					foreach($va_linking_tables as $vs_right_table) {
						if (is_array($va_table_key_list) && (isset($va_table_key_list[$vs_list_name][$vs_right_table][$vs_left_table]) || isset($va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table]))) {		// are the keys for this join specified in the indexing config?
							if (isset($va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table])) {
								$va_key_spec = $va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table];	
								$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['right_key'].' = '.$vs_left_table.'.'.$va_key_spec['left_key'];
								if ($va_key_spec['left_table_num'] || $va_key_spec['right_table_num']) {
									if ($va_key_spec['right_table_num']) {
										$vs_join .= ' AND '.$vs_right_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table);
									} else {
										$vs_join .= ' AND '.$vs_left_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table);
									}
								}
								$vs_join .= ")";
							} else {
								$va_key_spec = $va_table_key_list[$vs_list_name][$vs_right_table][$vs_left_table];
								$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['left_key'].' = '.$vs_left_table.'.'.$va_key_spec['right_key'];
								if ($va_key_spec['left_table_num'] || $va_key_spec['right_table_num']) {
									if ($va_key_spec['right_table_num']) {
										$vs_join .= ' AND '.$vs_left_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table);
									} else {
										$vs_join .= ' AND '.$vs_right_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table);
									}
								}
								$vs_join .= ")";
							}
							
							$va_joins[] = $vs_join;
						} else {
							if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_left_table, $vs_right_table)) {
								$va_joins[] = 'INNER JOIN '.$va_rel['many_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
							} else {
								if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_right_table, $vs_left_table)) {
									$va_joins[] = 'INNER JOIN '.$va_rel['one_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
								}
							}
						}
						$vs_left_table = $vs_right_table;
					}
	
					$va_proc_field_list = array();
					$vn_field_list_count = sizeof($va_field_list);
					for($vn_i=0; $vn_i < $vn_field_list_count; $vn_i++) {
						if ($va_field_list[$vn_i] == '_count') {
							continue; 
						}
						if (substr($va_field_list[$vn_i], 0, 14) === '_ca_attribute_') { continue; }
						if (!trim($va_field_list[$vn_i])) { continue; }
						$va_proc_field_list[$vn_i] = $vs_related_table.'.'.$va_field_list[$vn_i];
					}
					$va_proc_field_list[] = $vs_related_table.'.'.$vs_related_pk;
					if (isset($va_rel['many_table']) && $va_rel['many_table']) { 
						$va_proc_field_list[] = $va_rel['many_table'].'.'.$va_rel['many_table_field'];
					}
					$vs_sql = "
						SELECT ".join(",", $va_proc_field_list)."
						FROM ".$vs_subject_tablename."
						".join("\n", $va_joins)."
						WHERE
							(".$vs_subject_tablename.'.'.$vs_subject_pk.' = ?)
					';
					$qr_res = $this->opo_db->query($vs_sql, $pn_subject_row_id);
					
					if ($this->opo_db->numErrors()) {
						// Shouldn't ever happen
						throw new Exception(_t("SQL error while getting content for index of related fields: %1; SQL was %2", $this->opo_db->getErrors(), $vs_sql));
					}
					while($qr_res->nextRow()) {
						$va_field_data = $qr_res->getRow();
						$vn_row_id = $qr_res->get($vs_related_pk);
						
						foreach($va_fields_to_index as $vs_rel_field => $va_rel_field_info) {
//
// BEGIN: Index attributes in related tables
//						
							$vb_is_attr = false;
							if (substr($vs_rel_field, 0, 14) === '_ca_attribute_') {
								if (!preg_match('!^_ca_attribute_(.*)$!', $vs_rel_field, $va_matches)) { continue; }
								
								if($va_rel_field_info['DONT_INDEX'] && is_array($va_rel_field_info['DONT_INDEX'])){
									$vb_cont = false;
									foreach($va_rel_field_info["DONT_INDEX"] as $vs_exclude_type){
										if($this->_getElementID($vs_exclude_type) == intval($va_matches[1])){
											$vb_cont = true;
											break;
										}
									}
									if($vb_cont) continue; // skip excluded attribute type
								}
			
								$vb_is_attr = true;
								
								$va_rel_field_info['datatype'] = (int)$this->_getElementDataType($va_matches[1]);
			
								switch($va_rel_field_info['datatype']) {
									case 0: 		// container
										// index components of complex multi-value attributes
										$va_attributes = $t_rel->getAttributesByElement($va_matches[1], array('row_id' => $vn_row_id));
					
										if (sizeof($va_attributes)) { 
											foreach($va_attributes as $vo_attribute) {
												foreach($vo_attribute->getValues() as $vo_value) {
													$vn_list_id = $this->_getElementListID($vo_value->getElementID());
													$this->opo_engine->indexField($vn_related_tablenum, 'A'.$vo_value->getElementID(), $vo_attribute->getAttributeID(), $vo_value->getDisplayValue($vn_list_id), $va_rel_field_info);	// 4 = ca_attributes
												}
											}
										} else {
											// we are deleting a container so cleanup existing sub-values
											$va_sub_elements = $this->opo_metadata_element->getElementsInSet($va_matches[1]);
						
											foreach($va_sub_elements as $vn_i => $va_element_info) {
												$this->opo_engine->indexField($vn_related_tablenum, 'A'.$va_element_info['element_id'], $va_element_info['element_id'], '', $va_rel_field_info);
											}
										}
										break;
									case 3:		// list
										// We pull the preferred labels of list items for indexing here. We do so for all languages. Note that
										// this only done for list attributes that are standalone and not a sub-element in a container. Perhaps
										// we should also index the text of sub-element lists, but it's not clear that it is a good idea yet. The list_id's of
										// sub-elements *are* indexed however, so advanced search forms passing ids instead of text will work.
										$va_tmp = array();
										if (is_array($va_attributes = $t_rel->getAttributesByElement($va_matches[1], array('row_id' => $vn_row_id)))) {
											foreach($va_attributes as $vo_attribute) {
												foreach($vo_attribute->getValues() as $vo_value) {
													$va_tmp[$vo_attribute->getAttributeID()] = $vo_value->getDisplayValue();
												}
											}
										}
					
										$va_new_values = array();
										$t_item = new ca_list_items();
										$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_tmp, array('returnAllLocales' => true));
					
										foreach($va_labels as $vn_label_row_id => $va_labels_per_row) {
											foreach($va_labels_per_row as $vn_locale_id => $va_label_list) {
												foreach($va_label_list as $vs_label) {
													$va_new_values[$vn_label_row_id][$vs_label] = true;
												}
											}
										}
					
										foreach($va_tmp as $vn_attribute_id => $vn_item_id) {
											if(!$vn_item_id) { continue; }
											if(!isset($va_new_values[$vn_item_id]) || !is_array($va_new_values[$vn_item_id])) { continue; }
											$vs_v = join(' ;  ', array_merge(array($vn_item_id), array_keys($va_new_values[$vn_item_id])));	
											$this->opo_engine->indexField($vn_related_tablenum, 'A'.$va_matches[1], $vn_attribute_id, $vs_v, $va_rel_field_info);
										}
					
										break;
									default:
										$va_attributes = $t_rel->getAttributesByElement($va_matches[1], array('row_id' => $vn_row_id));
					
										if (!is_array($va_attributes)) { break; }
										foreach($va_attributes as $vo_attribute) {
											foreach($vo_attribute->getValues() as $vo_value) {
												$pn_content = $vo_value->getDisplayValue();
												$this->opo_engine->indexField($vn_related_tablenum, 'A'.$va_matches[1], $vo_attribute->getAttributeID(), $pn_content, $va_rel_field_info);
											}
										}
										break;
								}
							}
							
							$vs_fld_data = trim($va_field_data[$vs_rel_field]);
						
							//
							// Hierarchical indexing in related tables
							//
							if (((isset($va_rel_field_info['INDEX_ANCESTORS']) && $va_rel_field_info['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_rel_field_info))) {
								// is this current field a label?
								$t_hier_rel = $t_rel;
								$vn_fld_num = $t_rel->fieldNum($vs_rel_field);
								$vn_id = $vn_row_id;
								
								$vb_is_label = false;
								
								if (is_subclass_of($t_hier_rel, "BaseLabel")) {
									$t_hier_rel->load($vn_row_id);
									$t_hier_rel = $t_hier_rel->getSubjectTableInstance();
									
									$vn_id = $t_hier_rel->getPrimaryKey();
									$vb_is_label = true;
								}
								
								if ($t_hier_rel && $t_hier_rel->isHierarchical()) {
									// get hierarchy
									if ($va_hier_values = $this->_genHierarchicalPath($vn_id, ($vb_is_label ? "preferred_labels.".$vs_rel_field : $vs_rel_field), $t_hier_rel, $va_rel_field_info)) {
										$this->opo_engine->indexField($vn_related_tablenum, 'I'.$vn_fld_num, $vn_id, $vs_fld_data.' '.join(" ", $va_hier_values['values']), $va_rel_field_info);
										if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $va_rel_field_info, false) !== false) {
											$this->opo_engine->indexField($vn_related_tablenum, 'I'.$vn_fld_num, $vn_id, $va_hier_values['path'], array_merge($va_rel_field_info, array('DONT_TOKENIZE' => 1)));
										}
									}
									
									$va_children_ids = $t_hier_rel->getHierarchyAsList($vn_row_id, array('idsOnly' => true));
								
									if (!$pb_reindex_mode && is_array($va_children_ids) && sizeof($va_children_ids) > 0) {
										// trigger reindexing of children
										$o_indexer = new SearchIndexer($this->opo_db);
										$qr_children_res = $t_hier_rel->makeSearchResult($t_hier_rel->tableName(), $va_children_ids);
										$vs_pk = $t_hier_rel->primaryKey();
										$vn_table_num = $t_hier_rel->tableNum();
										while($qr_children_res->nextHit()) {
											$vn_id=$qr_children_res->get($vs_pk);
											if ($vn_id == $vn_row_id) { continue; }
											$o_indexer->indexRow($vn_table_num, $vn_id, array($vs_pk => $vn_id, 'parent_id' => $qr_children_res->get('parent_id'), $vs_rel_field => $qr_children_res->get($vs_rel_field)), false, $pa_exclusion_list, array($vs_rel_field => true), null);
										}
									}
									continue;
								}
							}
						
							switch($vs_rel_field){
								case '_count':
									// noop
									break;
								default:
									if ($vb_is_attr) {
										$this->opo_engine->indexField($vn_related_tablenum, 'A'.$va_matches[1], $qr_res->get($vs_related_pk), $vs_fld_data, $va_rel_field_info);
									} else {
										$this->opo_engine->indexField($vn_related_tablenum, 'I'.$this->opo_datamodel->getFieldNum($vs_related_table, $vs_rel_field), $qr_res->get($vs_related_pk), $vs_fld_data, $va_rel_field_info);
									}
									break;	
							}
//
// END: Index attributes in related tables
//
						}
					}
					//if (isset($va_fields_to_index['_count'])) {
						//$this->opo_engine->indexField($pn_subject_tablenum, '_count', $pn_subject_row_id, $qr_res->numRows(), array());
					//}
				}
			}
		}
}		
		// save indexing on subject
		if ($vb_started_indexing) {
			$this->opo_engine->commitRowIndexing();
		}
		
		if ((!$pb_reindex_mode) && (sizeof($pa_changed_fields) > 0)) {
			//
			// When not reindexing then we consider the effect of the change on this row upon related rows that use it
			// in their indexing. This means figuring out which related tables have indexing that depend upon the subject row.
			//
			// We deal with this by pulling up a dependency map generated from the search_indexing.conf file and then reindexing
			// those rows
			//
			$va_deps = $this->getDependencies($vs_subject_tablename);
			
			$va_changed_field_nums = array();
			foreach(array_keys($pa_changed_fields) as $vs_f) {
				if ($t_subject->hasField($vs_f)) {
					$va_changed_field_nums[$vs_f] = 'I'.$t_subject->fieldNum($vs_f);
				} else {
					if (preg_match('!^_ca_attribute_([\d]+)$!', $vs_f, $va_matches)) {
						$va_changed_field_nums[$vs_f] = 'A'.$this->_getElementListCode($va_matches[1]);
					}
				}	
			}
			
			//
			// reindex rows in dependent tables that use the subject_row_id
			//
			$va_rows_to_reindex = $this->_getDependentRowsForSubject($pn_subject_tablenum, $pn_subject_row_id, $va_deps, $va_changed_field_nums);
			
			if ($vb_can_do_incremental_indexing) { 
				$va_rows_to_reindex_by_row_id = array();
				
				foreach($va_rows_to_reindex as $vs_key => $va_row_to_reindex) {
					foreach($va_row_to_reindex['field_nums'] as $vs_fld_name => $vn_fld_num) {
						$vs_new_key = $va_row_to_reindex['table_num'].'/'.$va_row_to_reindex['field_table_num'].'/'.$vn_fld_num.'/'.$va_row_to_reindex['field_row_id'];
					
						if(!isset($va_rows_to_reindex_by_row_id[$vs_new_key])) {
							$va_rows_to_reindex_by_row_id[$vs_new_key] = array(
								'table_num' => $va_row_to_reindex['table_num'],
								'row_ids' => array(),
								'field_table_num' => $va_row_to_reindex['field_table_num'],
								'field_num' => $vn_fld_num,
								'field_name' => $vs_fld_name,
								'field_row_id' => $va_row_to_reindex['field_row_id'],
								'field_values' => $va_row_to_reindex['field_values'],
								'indexing_info' => $va_row_to_reindex['indexing_info'][$vs_fld_name]
							);
						}
						$va_rows_to_reindex_by_row_id[$vs_new_key]['row_ids'][] = $va_row_to_reindex['row_id'];
					}
				}
				$o_indexer = new SearchIndexer($this->opo_db);
				foreach($va_rows_to_reindex_by_row_id as $va_row_to_reindex) {
					if ($va_row_to_reindex['field_table_num'] === 4) {		// is attribute
						$va_row_to_reindex['indexing_info']['datatype'] = $this->_getElementDataType($va_row_to_reindex['field_num']);
					}
					
					if (((isset($va_row_to_reindex['indexing_info']['INDEX_ANCESTORS']) && $va_row_to_reindex['indexing_info']['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_row_to_reindex['indexing_info']))) {
						if (!is_array($va_row_to_reindex['row_ids'])) { continue; }
						
						$va_content = $this->_genHierarchicalPath($va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_name'], $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['field_table_num'], true), array());
						
						$vs_content = is_array($va_content['values']) ? join(" ", $va_content['values']) : "";
						
						$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], $va_row_to_reindex['field_row_id'], $vs_content, array_merge($va_row_to_reindex['indexing_info'], array('literalContent' => $va_content['path'])));
			
						//
						// THE FOLLOWING CODE IS TOO SLOW - 	updateIndexingInPlace() call following the commented-out block of code 
						//										replaces this and is much faster... but we need to validate that it works reliably
						//
// 						foreach($va_row_to_reindex['row_ids'] as $vn_row_to_reindex_id) {
// 							if ($t_dep = $this->getTableInstance($va_row_to_reindex['table_num'], true)) {
// 								$va_dep_ids = $t_dep->getHierarchyAsList($vn_row_to_reindex_id, array('idsOnly' => true, 'includeSelf' => true));
// 								
// 								$va_dep_values = BaseModel::getFieldValueArraysForIDs($va_dep_ids, $t_dep->tableName());
// 								
// 								foreach($va_dep_values as $vn_dep_id => $va_dep_value) {
// 									$o_indexer->indexRow($va_row_to_reindex['table_num'], $vn_dep_id, $va_dep_value, true, $pa_exclusion_list, null, null);
// 								}
// 							}
// 						}
					} else {
						$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']], $va_row_to_reindex['indexing_info']);
					}
				}
			} else {
				//
				// If the underlying engine doesn't support incremental indexing then
				// we fall back to reindexing each dependenting row completely and independently.
				// This can be *really* slow for subjects with many dependent rows (for example, a ca_list_item value used as a type for many ca_objects rows)
				// and we need to think about how to optimize this for such engines; ultimately since no matter how you slice it in such
				// engines you're going to have a lot of reindexing going on, we may just have to construct a facility to handle large
				// indexing tasks in a separate process when the number of dependent rows exceeds a certain threshold
				//
				$o_indexer = new SearchIndexer($this->opo_db);
				foreach($va_rows_to_reindex as $va_row_to_reindex) {
					if ((!$t_dep) || ($t_dep->tableNum() != $va_row_to_reindex['table_num'])) {
						$t_dep = $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['table_num']);
					}
					
					$vb_support_attributes = is_subclass_of($t_dep, 'BaseModelWithAttributes') ? true : false;
					if (is_array($pa_exclusion_list[$va_row_to_reindex['table_num']]) && (isset($pa_exclusion_list[$va_row_to_reindex['table_num']][$va_row_to_reindex['row_id']]))) { continue; }
					// trigger reindexing
					if ($vb_support_attributes) {
						if ($t_dep->load($va_row_to_reindex['row_id'])) {
							// 
							$o_indexer->indexRow($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], $t_dep->getFieldValuesArray(), true, $pa_exclusion_list);
						}
					} else {
						$o_indexer->indexRow($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], $va_row_to_reindex['field_values'], true, $pa_exclusion_list);
					}
				}
				$o_indexer = null;
			}
		} 
		
		if ($vb_reindex_children && method_exists($t_subject, "makeSearchResult")) {
			//
			// Force reindexing of children of this record, typically because the record has shifted location in the hierarchy and is hierarchically indexed
			//
			$va_children_ids = $t_subject->getHierarchyAsList($pn_subject_row_id, array('idsOnly' => true));
			if (is_array($va_children_ids) && sizeof($va_children_ids) > 0) {
				// trigger reindexing of children
				$o_indexer = new SearchIndexer($this->opo_db);
				$qr_children_res = $t_subject->makeSearchResult($vs_subject_tablename, $va_children_ids);
				while($qr_children_res->nextHit()) {
					$o_indexer->indexRow($pn_subject_tablenum, $vn_id=$qr_children_res->get($vs_subject_pk), array($vs_subject_pk => $vn_id, 'parent_id' => $qr_children_res->get('parent_id')), true, $pa_exclusion_list, array(), null);
				}
			}
		}
	}
	# ------------------------------------------------
	/**
	 * Removes indexing for specified row in table; this is the public call when one is deleting a record
	 * and needs to remove the associated indexing. unindexRow() will also remove indexing for the specified
	 * row from all dependent rows in other tables. It essentially undoes the results of indexRow().
	 * (Note that while this is called this a "public" call in fact you shouldn't need to call this directly. BaseModel.php does
	 * this for you during delete().)
	 */
	public function startRowUnIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?
		
		$vs_subject_tablename 		= $this->opo_datamodel->getTableName($pn_subject_tablenum);
		$t_subject 					= $this->getTableInstance($vs_subject_tablename, true);
		$vs_subject_pk 				= $t_subject->primaryKey();

		$va_deps = $this->getDependencies($vs_subject_tablename);
		
		$va_indexed_tables = $this->getIndexedTables();
		
		// Trigger reindexing if:
		//		* This row has dependencies
		//		* The row's table is indexed
		//		* We're changing an attribute or attribute value
		if (in_array($pn_subject_tablenum, array(3,4)) || isset($va_indexed_tables[$pn_subject_tablenum]) || sizeof($va_deps)) {
			$this->opa_dependencies_to_update = $this->_getDependentRowsForSubject($pn_subject_tablenum, $pn_subject_row_id, $va_deps);
		}
		return true;
	}
	# ------------------------------------------------
	public function commitRowUnIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?
		
		// delete index from subject
		$this->opo_engine->removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id); 
		
		if (is_array($this->opa_dependencies_to_update)) {
			if (!$vb_can_do_incremental_indexing) {
				$o_indexer = new SearchIndexer($this->opo_db);
				foreach($this->opa_dependencies_to_update as $va_item) {
					// trigger reindexing of related rows in dependent tables
					$o_indexer->indexRow($va_item['field_table_num'], $va_item['field_row_id'], $va_item['field_values'], true);
				}
				$o_indexer = null;
			} else {
				// incremental indexing engines delete dependent rows here
				// delete from index where other subjects reference it 
				$this->opo_engine->removeRowIndexing(null, null, $pn_subject_tablenum, null, $pn_subject_row_id);
				
				foreach($this->opa_dependencies_to_update as $va_item) {
					$this->opo_engine->removeRowIndexing($va_item['table_num'], $va_item['row_id'], $va_item['field_table_num'], null, $va_item['field_row_id']); 
				}
			}	
		}
		$this->opa_dependencies_to_update = null;
	}
	# ------------------------------------------------
	/**
	 * Determine if any of the fields to index are in the list of changed field nums
	 *
	 * @param array $pa_fields_to_index Array of fields to index as returned by SearchBase::getFieldsToIndex()
	 * @param array $pa_changed_field_nums Array of fields that have changed, where array keys are field names and array values are field number codes (Eg. I15 or A4)
	 * @return bool
	 */
	private function _indexedFieldsHaveChanged($pa_fields_to_index, $pa_changed_field_nums) {
		foreach($pa_fields_to_index as $vs_field => $va_indexing_info) {
			switch($vs_field) {
				case '_count':
					// noop
					break;
				default:
					$vn_fld_num = null;
					if (is_array($pa_changed_field_nums)) {
						if (isset($pa_changed_field_nums[$vs_field]) && $pa_changed_field_nums[$vs_field]) {
							return true;
						}
					}
					break;
			}
		}
		return false;
	}
	# ------------------------------------------------
	/**
	 * Returns an array with info about rows that need to be reindexed due to change in content for the given subject
	 */
	private function _getDependentRowsForSubject($pn_subject_tablenum, $pn_subject_row_id, $va_deps, $pa_changed_field_nums=null) {
		$va_dependent_rows = array();
		$vs_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_tablenum);
		
		$t_subject = $this->getTableInstance($vs_subject_tablename);
		$vs_subject_pk = $t_subject->primaryKey();
		
// Loop through dependent tables
		foreach($va_deps as $vs_dep_table) {
		
			$t_dep 				= $this->getTableInstance($vs_dep_table);
			$vs_dep_pk 			= $t_dep->primaryKey();
			$vn_dep_tablenum 	= $t_dep->tableNum();
			
			$va_dep_rel_indexing_tables = $this->getRelatedIndexingTables($vs_dep_table);
// Loop through tables indexed against dependency
			foreach($va_dep_rel_indexing_tables as $vs_dep_rel_table) {
			
			
				$va_table_info = $this->getTableIndexingInfo($vs_dep_table, $vs_dep_rel_table);
				
				if (isset($va_table_info['key']) && $va_table_info['key']) {
					$va_table_list_list = array('key' => array($vs_dep_table));
					$va_table_key_list = array();
				} else {
					$va_table_list_list = isset($va_table_info['tables']) ? $va_table_info['tables'] : null;
					$va_table_key_list = isset($va_table_info['keys']) ? $va_table_info['keys'] : null;
				}
// loop through the tables for each relationship between the subject and the dep

				$va_rel_tables_to_index_list = array();
				
				foreach($va_table_list_list as $vs_list_name => $va_linking_tables) {
					$va_linking_tables = is_array($va_linking_tables) ? array_reverse($va_linking_tables) : array();		// they come out of the conf file reversed from how we want them
					array_unshift($va_linking_tables, $vs_dep_rel_table);
					array_push($va_linking_tables, $vs_dep_table);															// the dep table name is not listed in the config file (it's redundant)
					
					if(in_array($vs_subject_tablename, $va_linking_tables)) {
						$va_rel_tables_to_index_list[] = $vs_dep_rel_table;
					}
				}
				
// update indexing for each relationship
				foreach($va_rel_tables_to_index_list as $vs_rel_table) {
					$va_indexing_info = $this->getTableIndexingInfo($vn_dep_tablenum, $vs_rel_table);
					$vn_rel_tablenum = $this->opo_datamodel->getTableNum($vs_rel_table);
					$vn_rel_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_rel_tablenum);
					$t_rel = $this->opo_datamodel->getInstanceByTableNum($vn_rel_tablenum, true);
					
					if (is_array($va_indexing_info['tables']) && (sizeof($va_indexing_info['tables']))) {
						$va_table_path = $va_indexing_info['tables'];
					} else {
						if ($va_indexing_info['key']) {
							$va_table_path = array(0 => array($vs_rel_table, $vs_dep_table));
						} else {
							continue;
						}
					}
					
					foreach($va_table_path as $vs_n => $va_table_list) {
						if (!in_array($vs_dep_table, $va_table_list)) { array_unshift($va_table_list, $vs_dep_table); }
						if (!in_array($vs_rel_table, $va_table_list)) { $va_table_list[] = $vs_rel_table; }
						if (!in_array($vs_subject_tablename, $va_table_list)) { continue; }
						
						$va_fields_to_index = $this->getFieldsToIndex($vn_dep_tablenum, $vs_rel_table);
			if ($vs_rel_table == $vs_subject_tablename) {
						if (is_array($pa_changed_field_nums) && !$this->_indexedFieldsHaveChanged($va_fields_to_index, $pa_changed_field_nums)) { continue; } // check if the current field actually needs indexing; only do this check if we've been passed a list of changed fields, otherwise we have to assume that everything has changed
			}			
						$va_full_path = $va_table_list;
						array_unshift($va_full_path, $vs_dep_table);
						$qr_rel_rows = $this->_getRelatedRows(array_reverse($va_full_path), isset($va_table_key_list[$vs_list_name]) ? $va_table_key_list[$vs_list_name] : null, $vs_subject_tablename, $pn_subject_row_id, $vs_rel_table ? $vs_rel_table : $vs_dep_table, $va_fields_to_index);
							
						if ($qr_rel_rows) {
							while($qr_rel_rows->nextRow()) {
								foreach($va_fields_to_index as $vs_field => $va_indexing_info) {
									switch($vs_field) {
										case '_count':
											$vn_fld_num = '_count';
											break;
										default:
											$vn_fld_num = null;
											if (isset(SearchIndexer::$s_SearchIndexer_field_num_cache[$vn_rel_tablenum][$vs_field])) { 
												$vn_fld_num = SearchIndexer::$s_SearchIndexer_field_num_cache[$vn_rel_tablenum][$vs_field]; 
												break;
											}
											
											if (preg_match('!^_ca_attribute_([\d]+)$!', $vs_field, $va_matches)) {
												SearchIndexer::$s_SearchIndexer_field_num_cache[$vn_rel_tablenum][$vs_field] = $vn_fld_num = 'A'.$va_matches[1];
											} else {
												SearchIndexer::$s_SearchIndexer_field_num_cache[$vn_rel_tablenum][$vs_field] = $vn_fld_num = 'I'.$t_rel->fieldNum($vs_field);
											}
							
											break;
									}
									
									if (!$vn_fld_num) { continue; }
									
									$vn_fld_row_id = $qr_rel_rows->get($vn_rel_pk);
									$vn_row_id = $qr_rel_rows->get($vs_dep_pk);
									$vs_key = $vn_dep_tablenum.'/'.$vn_row_id.'/'.$vn_rel_tablenum.'/'.$vn_fld_row_id;
									
									if (!isset($va_dependent_rows[$vs_key])) {
										$va_dependent_rows[$vs_key] = array(
											'table_num' => $vn_dep_tablenum,
											'row_id' => $vn_row_id,
											'field_table_num' => $vn_rel_tablenum,
											'field_row_id' => $vn_fld_row_id,
											'field_values' => $qr_rel_rows->getRow(),
											'field_nums' => array(),
											'field_names' => array()
										);
									}
									$va_dependent_rows[$vs_key]['field_nums'][$vs_field] = $vn_fld_num;
									$va_dependent_rows[$vs_key]['field_names'][$vn_fld_num] = $vs_field;
									$va_dependent_rows[$vs_key]['indexing_info'][$vs_field] = $va_indexing_info;
								}
							}
						}
					}
				}
			}
		}
		
		return $va_dependent_rows;
	}
	# ------------------------------------------------
	/**
	 * Returns query result with rows related via tables specified in $pa_tables to the specified subject; used by
	 * _getDependentRowsForSubject() to generate dependent row set
	 */
	private function _getRelatedRows($pa_tables, $pa_table_keys, $ps_subject_tablename, $pn_row_id, $ps_table_to_index, $pa_fields_to_index) {
		if (!in_array($ps_subject_tablename, $pa_tables)) { $pa_tables[] = $ps_subject_tablename; }
		$vs_key = md5(print_r($pa_tables, true)."/".print_r($pa_table_keys, true)."/".$ps_subject_tablename);
		
		$va_flds = array();
		$va_fld_names = array();
		
		// Add fields being indexed 
		if ($t_indexed_table = $this->opo_datamodel->getInstanceByTableName($ps_table_to_index, true)) {
			foreach($pa_fields_to_index as $vs_f => $va_field_info) {
				if (!$t_indexed_table->hasField($vs_f)) { continue; }
				if (in_array($t_indexed_table->getFieldInfo($vs_f, 'FIELD_TYPE'), array(FT_MEDIA, FT_FILE, FT_VARS, FT_DATERANGE, FT_HISTORIC_DATERANGE, FT_TIMERANGE))) { continue; }
				if (isset($va_fld_names[$vs_f]) && $va_fld_names[$vs_f]) { continue; }
				
				$va_flds[$ps_table_to_index.".".$vs_f] = true;
				$va_fld_names[$vs_f] = true;
			}
		}
		
		if (!isset(SearchIndexer::$s_related_rows_joins_cache[$vs_key]) || !(SearchIndexer::$s_related_rows_joins_cache[$vs_key])) {
			$vs_left_table = $vs_select_tablename = array_shift($pa_tables);
	
			$t_subject = $this->opo_datamodel->getInstanceByTableName($ps_subject_tablename, true);
			$vs_subject_pk = $t_subject->primaryKey();
			
			$va_tmp = array_keys($t_subject->getFormFields(true));
			foreach($va_tmp as $vn_i => $vs_v) {
				if (in_array($t_subject->getFieldInfo($vs_v, 'FIELD_TYPE'), array(FT_MEDIA, FT_FILE, FT_VARS, FT_DATERANGE, FT_HISTORIC_DATERANGE, FT_TIMERANGE))) { continue; }
				if(isset($va_fld_names[$vs_v]) && $va_fld_names[$vs_v]) { continue; }
				
				$va_flds[$ps_subject_tablename.".".$vs_v] = true;
				$va_fld_names[$vs_v] = true;
			}
			
			$t_select = $this->opo_datamodel->getInstanceByTableName($vs_select_tablename, true);
			$vs_select_pk = $t_subject->primaryKey();
			
			$va_tmp = array_keys($t_select->getFormFields(true));
			foreach($va_tmp as $vn_i => $vs_v) {
				if (in_array($t_select->getFieldInfo($vs_v, 'FIELD_TYPE'), array(FT_MEDIA, FT_FILE, FT_VARS, FT_DATERANGE, FT_HISTORIC_DATERANGE, FT_TIMERANGE))) { continue; }
				if(isset($va_fld_names[$vs_v]) && $va_fld_names[$vs_v]) { continue; }
				
				$va_flds[$vs_select_tablename.".".$vs_v] = true;
				$va_fld_names[$vs_v] = true;
			}
			
			$va_joins = array();
			
			foreach($pa_tables as $vs_right_table) {
				if ($vs_right_table == $vs_select_tablename) { continue; }
				if (is_array($pa_table_keys) && (isset($pa_table_keys[$vs_right_table][$vs_left_table]) || isset($pa_table_keys[$vs_left_table][$vs_right_table]))) {		// are the keys for this join specified in the indexing config?
					if (isset($pa_table_keys[$vs_left_table][$vs_right_table])) {
						$va_key_spec = $pa_table_keys[$vs_left_table][$vs_right_table];	
						$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['right_key'].' = '.$vs_left_table.'.'.$va_key_spec['left_key'];
					
						if ($va_key_spec['left_table_num'] || $va_key_spec['right_table_num']) {
							if ($va_key_spec['right_table_num']) {
								$vs_join .= ' AND '.$vs_right_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table);
								$vs_t = $vs_right_table;
							} else {
								$vs_join .= ' AND '.$vs_left_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table);
								$vs_t = $vs_left_table;
							}
						}
						$vs_join .= ')';						
					} else {
						$va_key_spec = $pa_table_keys[$vs_right_table][$vs_left_table];
						$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['left_key'].' = '.$vs_left_table.'.'.$va_key_spec['right_key'];
					
						if ($va_key_spec['left_table_num'] || $va_key_spec['right_table_num']) {
							if ($va_key_spec['right_table_num']) {
								$vs_join .= ' AND '.$vs_left_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table);
								$vs_t = $vs_left_table;
							} else {
								$vs_join .= ' AND '.$vs_right_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table);
								$vs_t = $vs_right_table;
							}
						}
						$vs_join .= ')';
					}
					
					$va_flds["{$vs_left_table}.".$this->opo_datamodel->getTablePrimaryKeyName($vs_left_table)] = true;
					$va_flds["{$vs_right_table}.".$this->opo_datamodel->getTablePrimaryKeyName($vs_right_table)] = true;
					
					$va_joins[] = $vs_join;
	
				} else {
					if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_left_table, $vs_right_table)) {
						$va_joins[] = 'INNER JOIN '.$va_rel['many_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
						$vs_t = $va_rel['many_table'];
						$va_flds[$va_rel['many_table'].".".$this->opo_datamodel->getTablePrimaryKeyName($va_rel['many_table'])] = true;
					} else {
						if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_right_table, $vs_left_table)) {
							$va_joins[] = 'INNER JOIN '.$va_rel['one_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
							$vs_t = $va_rel['one_table'];
							
							$va_flds[$va_rel['one_table'].".".$this->opo_datamodel->getTablePrimaryKeyName($va_rel['one_table'])] = true;
						}
					}
				}
				$vs_left_table = $vs_right_table;
				
				$t_instance = $this->opo_datamodel->getInstanceByTableName($vs_t, true);
				$va_tmp = array_keys($t_instance->getFormFields(true));
				foreach($va_tmp as $vn_i => $vs_v) {
					if (in_array($t_instance->getFieldInfo($vs_v, 'FIELD_TYPE'), array(FT_MEDIA, FT_FILE, FT_VARS, FT_DATERANGE, FT_HISTORIC_DATERANGE, FT_TIMERANGE))) { continue; }
					if(isset($va_fld_names[$vs_v]) && $va_fld_names[$vs_v]) { continue; }
				
					$va_flds[$vs_t.".".$vs_v] = true;
					$va_fld_names[$vs_v] = true;
				}
			}
		
			SearchIndexer::$s_related_rows_joins_cache[$vs_key] = $va_joins;
			SearchIndexer::$s_related_fields_joins_cache[$vs_key] = $va_flds;
		} else {
			$va_joins = SearchIndexer::$s_related_rows_joins_cache[$vs_key];
			$va_flds = SearchIndexer::$s_related_fields_joins_cache[$vs_key];
			
			$vs_select_tablename = array_shift($pa_tables);
			$t_subject = $this->opo_datamodel->getInstanceByTableName($ps_subject_tablename, true);
			$vs_subject_pk = $t_subject->primaryKey();
			
		}
		$vs_sql = "
			SELECT ".join(", ", array_keys($va_flds))."
			FROM ".$vs_select_tablename."
			".join("\n", $va_joins)."
			WHERE
			{$ps_subject_tablename}.{$vs_subject_pk} = ?
		";
		//print $vs_sql;
		
		$qr_res = $this->opo_db->query($vs_sql, $pn_row_id);
		if (!$qr_res) { 
			throw new Exception(_t("Invalid _getRelatedRows query: %1", join("; ", $this->opo_db->getErrors())));
		}
		
		return $qr_res;
	}
	# ------------------------------------------------
	/**
	 * Generates directed graph that represents indexing dependencies between tables in the database
	 * and then derives a list of indexed tables that might contain rows needing to be reindexed because
	 * they use the subject table as part of their indexing.
	 */
	private function __getDependencies($ps_subject_table) {
		$o_graph = new Graph();
		$va_indexed_tables = $this->getIndexedTables();
		
		$va_indexed_table_name_list = array();
		foreach($va_indexed_tables as $vn_table_num => $va_table_info) {
			$va_indexed_table_name_list[] = $vs_indexed_table = $va_table_info['name'];
			if ($vs_indexed_table == $ps_subject_table) { continue; }		// the subject can't be dependent upon itself

			// get list related tables used to index the subject table
			$va_related_tables = $this->getRelatedIndexingTables($vs_indexed_table);
			foreach($va_related_tables as $vs_related_table) {
				// get list of tables in indexing relationship
				// eg. if the subject is 'objects', and the related table is 'entities' then
				// the table list would be ['objects', 'objects_x_entities', 'entities']
				$va_info = $this->getTableIndexingInfo($vs_indexed_table, $vs_related_table);
				$va_table_list_list = $va_info['tables'];
				
				if (!is_array($va_table_list_list) || !sizeof($va_table_list_list)) { 
					if ($vs_table_key = $va_info['key']) {
						// Push direct relationship through one-to-many key onto table list
						$va_table_list_list = array($vs_related_table => array());
					} else {
						$va_table_list_list = array();
					}
				}

				foreach($va_table_list_list as $vs_list_name => $va_table_list) {
					array_unshift($va_table_list,$vs_indexed_table);
					array_push($va_table_list, $vs_related_table);
	
					if (in_array($ps_subject_table, $va_table_list)) {			// we only care about indexing relationships that include the subject table
						// for each each related table record the intervening tables in the graph
						$vs_last_table = '';
						foreach($va_table_list as $vs_tablename) {
							$o_graph->addNode($vs_tablename);
							if ($vs_last_table) {
								if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_tablename, $vs_last_table)) {		// determining direction of relationship (directionality is from the "many" table to the "one" table
									$o_graph->addRelationship($vs_tablename, $vs_last_table, 10, true);
								} else {
									$o_graph->addRelationship($vs_last_table, $vs_tablename, 10, true);
								}
							}
							$vs_last_table = $vs_tablename;
						}
					}
				}
			}
		}
		
		$va_topo_list = $o_graph->getTopologicalSort();

		$va_deps = array();
		foreach($va_topo_list as $vs_tablename) {
			if ($vs_tablename == $ps_subject_table) { continue; }
			if (!in_array($vs_tablename, $va_indexed_table_name_list)) { continue; }

			$va_deps[] = $vs_tablename;
		}

		return $va_deps;
	}
	# ------------------------------------------------
	/**
	 * Returns element_id of ca_metadata_element with specified element_code or NULL if the element doesn't exist
	 * Because of dependency and performance issues we do a straight query here rather than go through the ca_metadata_elements model
	 */
	private function _getElementID($ps_element_code) {
		if (isset(SearchIndexer::$s_SearchIndexer_element_id_cache[$ps_element_code])) { return SearchIndexer::$s_SearchIndexer_element_id_cache[$ps_element_code]; }
		
		if (is_numeric($ps_element_code)) {
			$qr_res = $this->opo_db->query("
				SELECT element_id, datatype, list_id FROM ca_metadata_elements WHERE element_id = ?
			", intval($ps_element_code));
		} else {
			$qr_res = $this->opo_db->query("
				SELECT element_id, datatype, list_id FROM ca_metadata_elements WHERE element_code = ?
			", $ps_element_code);
		}
		if (!$qr_res->nextRow()) { return null; }
		$vn_element_id =  $qr_res->get('element_id');
		SearchIndexer::$s_SearchIndexer_element_data_type_cache[$ps_element_code] = SearchIndexer::$s_SearchIndexer_element_data_type_cache[$vn_element_id] = $qr_res->get('datatype');
		SearchIndexer::$s_SearchIndexer_element_list_id_cache[$ps_element_code] = SearchIndexer::$s_SearchIndexer_element_list_id_cache[$vn_element_id] = $qr_res->get('list_id');
		SearchIndexer::$s_SearchIndexer_element_id_cache[$vn_element_id] = $ps_element_code;
		return SearchIndexer::$s_SearchIndexer_element_id_cache[$ps_element_code] = $vn_element_id;
	}
	# ------------------------------------------------
	/**
	 * Returns datatype code of ca_metadata_element with specified element_code or NULL if the element doesn't exist
	 */
	private function _getElementDataType($ps_element_code) {
		$vn_element_id = $this->_getElementID($ps_element_code);	// ensures $s_SearchIndexer_element_data_type_cache[$ps_element_code] is populated
		return SearchIndexer::$s_SearchIndexer_element_data_type_cache[$vn_element_id];
	}
	# ------------------------------------------------
	/**
	 * Returns list_id of ca_metadata_element with specified element_code or NULL if the element doesn't exist
	 */
	private function _getElementListID($ps_element_code) {
		$vn_element_id = $this->_getElementID($ps_element_code);	// ensures $s_SearchIndexer_element_data_type_cache[$ps_element_code] is populated
		return SearchIndexer::$s_SearchIndexer_element_list_id_cache[$vn_element_id];
	}
	# ------------------------------------------------
	/**
	 * Returns element_code of ca_metadata_element with specified element_id or NULL if the element doesn't exist
	 */
	private function _getElementListCode($pn_element_id) {
		$vn_element_id = $this->_getElementID($pn_element_id);	// ensures $s_SearchIndexer_element_id_cache[$vn_element_id] is populated
		return SearchIndexer::$s_SearchIndexer_element_id_cache[$vn_element_id];
	}
	# ------------------------------------------------
}
?>
