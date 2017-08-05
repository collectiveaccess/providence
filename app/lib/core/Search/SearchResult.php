<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchResult.php : implements interface to results from a search
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2017 Whirl-i-Gig
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

# ----------------------------------------------------------------------
# --- Import classes
# ----------------------------------------------------------------------
include_once(__CA_LIB_DIR__."/core/BaseObject.php");
include_once(__CA_LIB_DIR__."/core/Datamodel.php");
include_once(__CA_LIB_DIR__."/core/Media/MediaInfoCoder.php");
include_once(__CA_LIB_DIR__."/core/File/FileInfoCoder.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimecodeParser.php");
include_once(__CA_LIB_DIR__."/core/ApplicationChangeLog.php");
include_once(__CA_MODELS_DIR__."/ca_locales.php");


# ----------------------------------------------------------------------
class SearchResult extends BaseObject {
	
	private static $opo_datamodel;
	private $opo_search_config;
	private $opo_db;
	private $opn_table_num;
	protected $ops_table_name;
	private $ops_table_pk;
	// ----
	
	private $opa_options;

	/**
	 * @var IWLPlugSearchEngineResult
	 */
	private $opo_engine_result;
	protected $opa_tables;
	
	protected $opo_subject_instance;

	private $opa_row_ids_to_prefetch_cache;
	
	private $opo_tep; // time expression parser
	
	private static $o_db;
	private static $opa_locales = null;
	private static $opo_locales; // ca_locales instance
	private static $opt_list = null; // ca_lists instance
	
	private $opa_cached_result_counts;

	static $s_prefetch_cache = array();
	static $s_instance_cache = array();
	static $s_timestamp_cache = array();
	static $s_rel_prefetch_cache = array();
	static $s_parsed_field_component_cache = array();
	static $opa_hierarchy_parent_prefetch_cache = array();
	static $opa_hierarchy_children_prefetch_cache = array();
	static $opa_hierarchy_parent_prefetch_cache_index = array();
	static $opa_hierarchy_children_prefetch_cache_index = array();
	static $opa_hierarchy_siblings_prefetch_cache = array();
	static $opa_hierarchy_siblings_prefetch_cache_index = array();
	
	private $opb_use_identifiers_in_urls = false;
	private $ops_subject_idno = false;
	
	/**
	 * Maximum number of entries for each table in these caches:
	 *		rel_prefetch_cache
	 *		prefetch_cache
	 *		hierarchy_parent_prefetch_cache
	 *		hierarchy_parent_prefetch_cache_index
	 *		hierarchy_siblings_prefetch_cache
	 *		hierarchy_siblings_prefetch_cache_index
	 *		hierarchy_children_prefetch_cache_index
	 */
	static $s_cache_size_limit = 256;

	# ------------------------------------------------------------------
	private $opb_disable_get_with_template_prefetch = false;
	private $opa_template_prefetch_cache = array();
	# ------------------------------------------------------------------

	/**
	 * Clear all internal caches
	 *
	 * @return void
	 */ 
	public static function clearCaches() {
		self::$s_prefetch_cache = array();
		self::$s_instance_cache = array();
		self::$s_timestamp_cache = array();
		self::$s_rel_prefetch_cache = array();
		self::$s_parsed_field_component_cache = array();
		self::$opa_hierarchy_parent_prefetch_cache = array();
		self::$opa_hierarchy_children_prefetch_cache = array();
		self::$opa_hierarchy_parent_prefetch_cache_index = array();
		self::$opa_hierarchy_children_prefetch_cache_index = array();
		self::$opa_hierarchy_siblings_prefetch_cache = array();
		self::$opa_hierarchy_siblings_prefetch_cache_index = array();
	}
	
	/**
	 * Get relative sizes of internal caches. The size is the strlen of the cache when serialized and
	 * is only useful for relative size comparisons.
	 *
	 * @return array Array with keys set to cache name, values set to relative sizes
	 */
	public static function getCacheSizes() {
		return [
			'prefetch_cache' => strlen(serialize(self::$s_prefetch_cache)),
			'instance_cache' => strlen(serialize(self::$s_instance_cache)),
			'timestamp_cache' => strlen(serialize(self::$s_timestamp_cache)),
			'rel_prefetch_cache' => strlen(serialize(self::$s_rel_prefetch_cache)),
			'parsed_field_component_cache' => strlen(serialize(self::$s_parsed_field_component_cache)),
			'hierarchy_parent_prefetch_cache' => strlen(serialize(self::$opa_hierarchy_parent_prefetch_cache)),
			'hierarchy_children_prefetch_cache' => strlen(serialize(self::$opa_hierarchy_children_prefetch_cache)),
			'hierarchy_parent_prefetch_cache_index' => strlen(serialize(self::$opa_hierarchy_parent_prefetch_cache_index)),
			'hierarchy_children_prefetch_cache_index' => strlen(serialize(self::$opa_hierarchy_children_prefetch_cache_index)),
			'hierarchy_siblings_prefetch_cache' => strlen(serialize(self::$opa_hierarchy_siblings_prefetch_cache)),
			'hierarchy_siblings_prefetch_cache_index' => strlen(serialize(self::$opa_hierarchy_siblings_prefetch_cache_index))
		];
	}

	/**
	 * Checks size of volatile per-table caches and resets them if their size exceeds the threshold for the given table
	 * as set in SearchResult::$s_cache_size_limit. Caches managed include:
	 *
	 *		rel_prefetch_cache
	 *		prefetch_cache
	 *		hierarchy_parent_prefetch_cache
	 *		hierarchy_parent_prefetch_cache_index
	 *		hierarchy_siblings_prefetch_cache
	 *		hierarchy_siblings_prefetch_cache_index
	 *		hierarchy_children_prefetch_cache_index
	 * 
	 * @param string Table name
	 * @return void
	 */ 
	public static function checkCacheSizeLimit($ps_tablename) {
		foreach ([
			'prefetch_cache' => &self::$s_prefetch_cache,
			'instance_cache' => &self::$s_instance_cache,
			'timestamp_cache' => &self::$s_timestamp_cache,
			'rel_prefetch_cache' => &self::$s_rel_prefetch_cache,
			'parsed_field_component_cache' => &self::$s_parsed_field_component_cache,
			'hierarchy_parent_prefetch_cache' => &self::$opa_hierarchy_parent_prefetch_cache,
			'hierarchy_children_prefetch_cache' => &self::$opa_hierarchy_children_prefetch_cache,
			'hierarchy_parent_prefetch_cache_index' => &self::$opa_hierarchy_parent_prefetch_cache_index,
			'hierarchy_children_prefetch_cache_index' => &self::$opa_hierarchy_children_prefetch_cache_index,
			'hierarchy_siblings_prefetch_cache' => &self::$opa_hierarchy_siblings_prefetch_cache,
			'hierarchy_siblings_prefetch_cache_index' => &self::$opa_hierarchy_siblings_prefetch_cache_index
		] as $vs_cache => &$va_cache) {
			switch($vs_cache) {
				case 'rel_prefetch_cache':
				case 'prefetch_cache':
				case 'hierarchy_parent_prefetch_cache':
				case 'hierarchy_parent_prefetch_cache_index':
				case 'hierarchy_siblings_prefetch_cache':
				case 'hierarchy_siblings_prefetch_cache_index':
				case 'hierarchy_children_prefetch_cache_index':
					if (is_array($va_cache) && is_array($va_cache[$ps_tablename]) && (sizeof($va_cache[$ps_tablename]) > SearchResult::$s_cache_size_limit)) {
						$va_cache[$ps_tablename] = [];
					}
					break;
			}
			
		}
	}


	public function __construct($po_engine_result=null, $pa_tables=null) {
		if (!SearchResult::$opo_datamodel) { SearchResult::$opo_datamodel = Datamodel::load(); }
		
		$this->opo_db = (SearchResult::$o_db) ? SearchResult::$o_db : SearchResult::$o_db = new Db();
		$this->opo_subject_instance = SearchResult::$opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
		
		$this->ops_subject_pk = $this->opo_subject_instance->primaryKey();
		$this->ops_subject_idno = $this->opo_subject_instance->getProperty('ID_NUMBERING_ID_FIELD');
		$this->opb_use_identifiers_in_urls = (bool)$this->opo_subject_instance->getAppConfig()->get('use_identifiers_in_urls');
		$this->opa_row_ids_to_prefetch_cache = array();
		
		
		if (!SearchResult::$opo_locales) { SearchResult::$opo_locales = new ca_locales();; }
		if (!SearchResult::$opa_locales) { SearchResult::$opa_locales = ca_locales::getLocaleList(); }
		if (!SearchResult::$opt_list) { SearchResult::$opt_list = SearchResult::$opo_datamodel->getInstanceByTableName('ca_lists', true); }
		
		if ($po_engine_result) {
			$this->init($po_engine_result, $pa_tables);
		}
		
		if (!$GLOBALS["_DbResult_time_expression_parser"]) { $GLOBALS["_DbResult_time_expression_parser"] = new TimeExpressionParser(); }
		if (!$GLOBALS["_DbResult_timecodeparser"]) { $GLOBALS["_DbResult_timecodeparser"] = new TimecodeParser(); }
		
		if (!$GLOBALS["_DbResult_mediainfocoder"]) { $GLOBALS["_DbResult_mediainfocoder"] = MediaInfoCoder::load(); }
		if (!$GLOBALS["_DbResult_fileinfocoder"]) { $GLOBALS["_DbResult_fileinfocoder"] = FileInfoCoder::load(); }
		
		
		
		// valid options and defaults
		$this->opa_options = array(
				// SearchResult::get() can load field data from database when it is not available directly from the search index (most fields are *not* available from the index)
				// It is almost always more efficient to grab multiple field values from a table in one query, and to do so for multiple rows, than to generate and execute queries 
				// each time get() is called. Thus get() automatically "prefetches" field values for a given table when it is called; the "prefetch" option defined how many rows
				// beyond the current row are pre-loaded. You ideally want this value to match the number of rows you actually plan to use. If you're generating lists of search
				// results and page the results with 50 results per page then you'd want to the prefetch to be 50. If the number of rows you need is very large (> 200?) then it might
				// make sense to use a value less than the total number of rows since queries with many enumerated row_ids (which is what the prefetch mechanism uses) may run slowly
				// when a large number of ids are specified. The default for this is 50.
				// 
				'prefetch' => 50,
				'prefetchAttributes' => null
		);
		
		
		$this->opo_tep = $GLOBALS["_DbResult_time_expression_parser"];
		
		$this->opa_template_prefetch_cache = array();
	}
	# ------------------------------------------------------------------
	public function cloneInit() {
		$this->opo_db = new Db();
		SearchResult::$opo_datamodel = Datamodel::load();
		$this->opo_subject_instance = SearchResult::$opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param IWLPlugSearchEngineResult $po_engine_result
	 * @param array $pa_tables
	 * @param array $pa_options Options include:
	 *		db = optional Db instance to use for database connectivity. If omitted a new database connection is used. If you need to have you result set access the database within a specific transaction you should pass the Db object used by the transaction here.
	 */
	public function init($po_engine_result, $pa_tables, $pa_options=null) {
		
		$this->opn_table_num = $this->opo_subject_instance->tableNum();
		$this->ops_table_name =  $this->opo_subject_instance->tableName();
		$this->ops_table_pk = $this->opo_subject_instance->primaryKey();
		$this->opa_cached_result_counts = array();
		
		$this->opo_engine_result = $po_engine_result;
		$this->opa_tables = $pa_tables;
		
		if ($o_db = caGetOption('db', $pa_options, null)) { 
			$this->opo_db = $o_db;
		}
		
		$this->errors = array();
	}
	# ------------------------------------------------------------------
	/**
	 * Controls prefetching for @see SearchResult::getWithTemplate()
	 * @param bool $pb_disable do prefetching or not?
	 */
	public function disableGetWithTemplatePrefetch($pb_disable=true) {
		$this->opb_disable_get_with_template_prefetch = $pb_disable;
	}
	# ------------------------------------------------------------------
	public function getDb() {
		return $this->opo_db;
	}
	# ------------------------------------------------------------------
	public function tableNum() {
		return $this->opn_table_num;
	}
	# ------------------------------------------------------------------
	public function tableName() {
		return $this->ops_table_name;
	}
	# ------------------------------------------------------------------
	public function primaryKey() {
		return SearchResult::$opo_datamodel->getTablePrimaryKeyName($this->opn_table_num);
	}
	# ------------------------------------------------------------------
	public function numHits() {
		return $this->opo_engine_result->numHits();
	}
	# ------------------------------------------------------------------
	public function nextHit() {
		return $this->opo_engine_result->nextHit();
	}
	# ------------------------------------------------------------------
	public function currentIndex() {
		return $this->opo_engine_result->currentRow();
	}
	# ------------------------------------------------------------------
	public function previousHit() {
		$vn_index = $this->opo_engine_result->currentRow();
		if ($vn_index >= 0) {
			$this->opo_engine_result->seek($vn_index);
		}
	}
	# ------------------------------------------------------------------
	/**
  	 * Returns true if this current hit is the last in the set
  	 *
  	 * @return boolean True if current hit is the last in the results set, false otherwise
	 */
	public function isLastHit() {
		$vn_index = $this->opo_engine_result->currentRow();
		$vn_num_hits = $this->opo_engine_result->numHits();
		
		if ($vn_index == ($vn_num_hits - 1)) { return true; }
		
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	protected function getRowIDsToPrefetch($pn_start, $pn_num_rows) {
		if ($this->opa_row_ids_to_prefetch_cache[$pn_start.'/'.$pn_num_rows]) { return $this->opa_row_ids_to_prefetch_cache[$pn_start.'/'.$pn_num_rows]; }
		$va_row_ids = array();
		
		$vn_cur_row_index = $this->opo_engine_result->currentRow();
		self::seek($pn_start);
		
		$vn_i=0;
		while(self::nextHit() && ($vn_i < $pn_num_rows)) {
			if ($vn_row_id = (int)$this->opo_engine_result->get($this->ops_table_pk)) {
				$va_row_ids[] = $vn_row_id;
			}
			$vn_i++;
		}
		self::seek($vn_cur_row_index + 1);
		
		return $this->opa_row_ids_to_prefetch_cache[$pn_start.'/'.$pn_num_rows] = $va_row_ids;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function prefetchLabels($ps_tablename, $pn_start, $pn_num_rows, $pa_options=null) {
		if (!$ps_tablename ) { return; }
		if (!($t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = SearchResult::$opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}

		$vs_label_table = $t_rel_instance->getLabelTableName();
		
		if (!isset($this->opa_tables[$vs_label_table])) {
			$this->opa_tables[$vs_label_table] = array(
				'fieldList' => array($vs_label_table.'.*'),
				'joinTables' => array(),
				'criteria' => array()
			);
		}
		
		$this->prefetch($vs_label_table, $pn_start, $pn_num_rows, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function prefetchHierarchyParents($ps_tablename, $pn_start, $pn_num_rows, $pa_options=null) {
		if (!$ps_tablename ) { return; }
		
		SearchResult::checkCacheSizeLimit($ps_tablename);
		
		// get row_ids to fetch
		if (isset($pa_options['row_ids']) && is_array($pa_options['row_ids'])) {
			$va_row_ids = $pa_options['row_ids'];
		} else {
			$va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows);
		}
		if (sizeof($va_row_ids) == 0) { return false; }

		if (!($t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = SearchResult::$opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}
		if (!$t_rel_instance->isHierarchical()) { return false; }
		
		$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);

		if ($ps_tablename !== $this->ops_table_name) {
			$va_row_ids = $this->_getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, SearchResult::$opa_hierarchy_parent_prefetch_cache_index, $t_rel_instance, $va_row_ids, $pa_options);
		}
		$vs_pk = $t_rel_instance->primaryKey();
		$vs_parent_id_fld = $t_rel_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_hier_id_fld = $t_rel_instance->getProperty('HIERARCHY_ID_FLD');
		
		$va_row_ids_in_current_level = $va_row_ids;
		$va_params = array($va_row_ids_in_current_level);
		
		$vs_type_sql = '';
		if (is_array($va_type_ids = caMakeTypeIDList($ps_tablename, caGetOption('restrictToTypes', $pa_options, null))) && sizeof($va_type_ids)) {
			$vs_type_sql = " AND (p.type_id IN (?)".($t_rel_instance->getFieldInfo('type_id', 'IS_NULL') ? " OR (p.type_id IS NULL)" : '').')';
			$va_params[] = $va_type_ids;
		}
		
		
		$vs_sql = "
			SELECT t.{$vs_pk}, t.{$vs_parent_id_fld} ".($vs_hier_id_fld ? ", t.{$vs_hier_id_fld}" : '')."
			FROM {$ps_tablename} t
			INNER JOIN {$ps_tablename} AS p ON p.{$vs_pk} = t.{$vs_parent_id_fld}
			WHERE
				t.{$vs_pk} IN (?)".($t_rel_instance->hasField('deleted') ? " AND (t.deleted = 0)" : "")."
				{$vs_type_sql}
		";

		$va_row_id_map = null;
		$vn_level = 0;
		
		while(true) {
			if (!sizeof($va_row_ids_in_current_level) || !is_array($va_params) || !is_array($va_params[0]) || !sizeof($va_params[0])) { break; }
			$qr_rel = $this->opo_subject_instance->getDb()->query($vs_sql, $va_params);
			if (!$qr_rel || ($qr_rel->numRows() == 0)) { break;}
			
			while($qr_rel->nextRow()) {
				$va_row = $qr_rel->getRow();
				if (!$va_row[$vs_parent_id_fld]) { continue; }
				
				$va_row_id_map[$va_row[$vs_pk]] = $va_row[$vs_parent_id_fld];		// list of ids indexed by parent_id
			}
			
			$va_row_ids_in_current_level = $va_params[0] = $qr_rel->getAllFieldValues($vs_parent_id_fld);
			
			$vn_level++;
		}
		
		foreach($va_row_ids as $vn_id) {
			SearchResult::$opa_hierarchy_parent_prefetch_cache[$ps_tablename][$vn_id][$vs_opt_md5] = [];
			
			$vn_key = $vn_id;
			while(true) {
				if (!isset($va_row_id_map[$vn_key]) || !$va_row_id_map[$vn_key]) { 
					break; 
				}
				SearchResult::$opa_hierarchy_parent_prefetch_cache[$ps_tablename][$vn_id][$vs_opt_md5][] = $va_row_id_map[$vn_key];
				
				$vn_key = $va_row_id_map[$vn_key];
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function prefetchHierarchyChildren($ps_tablename, $pn_start, $pn_num_rows, $pa_options=null) {
		if (!$ps_tablename ) { return; }
		
		// get row_ids to fetch
		if (isset($pa_options['row_ids']) && is_array($pa_options['row_ids'])) {
			$va_row_ids = $pa_options['row_ids'];
		} else {
			$va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows);
		}
		if (sizeof($va_row_ids) == 0) { return false; }

		if (!($t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = SearchResult::$opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}
		if (!$t_rel_instance->isHierarchical()) { return false; }

		$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);

		if ($ps_tablename != $this->ops_table_name) {
			$va_row_ids = $this->_getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, SearchResult::$opa_hierarchy_children_prefetch_cache_index, $t_rel_instance, $va_row_ids, $pa_options);
		}
		
		
		$va_row_ids_in_current_level = $va_row_ids;
		$va_params = array($va_row_ids_in_current_level);
		
		$vs_type_sql = '';
		if (is_array($va_type_ids = caMakeTypeIDList($ps_tablename, caGetOption('restrictToTypes', $pa_options, null))) && sizeof($va_type_ids)) {
			$vs_type_sql = " AND (type_id IN (?)".($t_rel_instance->getFieldInfo('type_id', 'IS_NULL') ? " OR ({$vs_related_table}.type_id IS NULL)" : '').')';;
			$va_params[] = $va_type_ids;
		}
		if(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_instance->hasField('access')) {
			$vs_access_sql = " AND ({$ps_tablename}.access IN (".join(",", $pa_options['checkAccess']) ."))";	
		}
	
		$vs_pk = $t_rel_instance->primaryKey();
		$vs_parent_id_fld = $t_rel_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_sql = "
			SELECT {$vs_pk}, {$vs_parent_id_fld}
			FROM {$ps_tablename}
			WHERE
				 {$vs_parent_id_fld} IN (?)".($t_rel_instance->hasField('deleted') ? " AND (deleted = 0)" : "")."
				 {$vs_type_sql}
				 {$vs_access_sql}
		";
		$va_row_id_map = null;
		$vn_level = 0;
		
		while(true) {
			if (!is_array($va_params) || !sizeof($va_params) || !is_array($va_params[0]) || !sizeof($va_params[0])) { break; }
			$qr_rel = $this->opo_subject_instance->getDb()->query($vs_sql, $va_params);
			
			$va_row_ids += $va_row_ids_in_current_level;
			if (!$qr_rel || ($qr_rel->numRows() == 0)) { break;}
			
			$va_row_ids_in_current_level = array(); 
			while($qr_rel->nextRow()) {
				$va_row = $qr_rel->getRow();
				
				if ($vn_level == 0) {
					$va_row_id_map[$va_row[$vs_pk]] = $va_row[$vs_parent_id_fld];
				} else {
					$va_row_id_map[$va_row[$vs_pk]] = $va_row_id_map[$va_row[$vs_parent_id_fld]];
				}
				if (!$va_row_id_map[$va_row[$vs_pk]]) { continue; }
				
				SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$va_row[$vs_parent_id_fld]][$vs_opt_md5][] = 
				SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$va_row_id_map[$va_row[$vs_parent_id_fld]]][$vs_opt_md5][] =
					$va_row_ids_in_current_level[] = $va_row[$vs_pk];
			}
			$vn_level++;
			
			if ((!isset($pa_options['allDescendants']) || !$pa_options['allDescendants']) && ($vn_level > 0)) {
				break;
			}
		}
		
		foreach($va_row_ids as $vn_row_id) {
			if (!isset(SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$vn_row_id][$vs_opt_md5])) { 
				SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$vn_row_id][$vs_opt_md5] = array();
			}
		}
		
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function prefetchHierarchySiblings($ps_tablename, $pn_start, $pn_num_rows, $pa_options=null) {
		if (!$ps_tablename ) { return; }
		
		// get row_ids to fetch
		if (isset($pa_options['row_ids']) && is_array($pa_options['row_ids'])) {
			$va_row_ids = $pa_options['row_ids'];
		} else {
			$va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows);
		}
		if (sizeof($va_row_ids) == 0) { return false; }

		if (!($t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = SearchResult::$opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}
		if (!$t_rel_instance->isHierarchical()) { return false; }

		$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
		
		if ($ps_tablename != $this->ops_table_name) {
			$va_row_ids = $this->_getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, SearchResult::$opa_hierarchy_siblings_prefetch_cache_index, $t_rel_instance, $va_row_ids, $pa_options);
		}
		
		$va_params = array($va_row_ids);
		
		$vs_type_sql = '';
		if (is_array($va_type_ids = caMakeTypeIDList($ps_tablename, caGetOption('restrictToTypes', $pa_options, null))) && sizeof($va_type_ids)) {
			$vs_type_sql = " AND (p.type_id IN (?)".($t_rel_instance->getFieldInfo('type_id', 'IS_NULL') ? " OR (p.type_id IS NULL)" : '').')';;
			$va_params[] = $va_type_ids;
		}
		
		if(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_instance->hasField('access')) {
			$vs_access_sql = " AND ({$ps_tablename}.access IN (".join(",", $pa_options['checkAccess']) ."))";	
		}
		
		$vs_pk = $t_rel_instance->primaryKey();
		$vs_parent_id_fld = $t_rel_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_sql = "
			SELECT t.{$vs_pk}, t.{$vs_parent_id_fld}, p.{$vs_pk} sibling_id
			FROM {$ps_tablename} t
			INNER JOIN {$ps_tablename} AS p ON t.{$vs_parent_id_fld} = p.{$vs_parent_id_fld}
			WHERE
				 t.{$vs_pk} IN (?)".($t_rel_instance->hasField('deleted') ? " AND (t.deleted = 0) AND (p.deleted = 0)" : "")."
				 {$vs_type_sql} {$vs_access_sql}
		";
		
		$qr_rel = $this->opo_subject_instance->getDb()->query($vs_sql, $va_params);
		while($qr_rel->nextRow()) {
			$va_row = $qr_rel->getRow();
			
			SearchResult::$opa_hierarchy_siblings_prefetch_cache[$ps_tablename][$va_row[$vs_pk]][$vs_opt_md5][] = $va_row['sibling_id'];
		}
		
		foreach($va_row_ids as $vn_row_id) {
			if (!isset(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$ps_tablename][$vn_row_id][$vs_opt_md5])) { 
				SearchResult::$opa_hierarchy_siblings_prefetch_cache[$ps_tablename][$vn_row_id][$vs_opt_md5] = array();
			}
		}
		
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, &$pa_cache, $t_rel_instance, $va_row_ids, $pa_options) {
		$this->prefetchRelated($ps_tablename, $pn_start, $pn_num_rows, $pa_options);
		
		SearchResult::checkCacheSizeLimit($ps_tablename);
						
		$va_base_row_ids = array();
		$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
		$va_related_ids = array();
		
		
		foreach($va_row_ids as $vn_row_id) {
			if(is_array($va_related_items = self::$s_rel_prefetch_cache[$this->ops_table_name][$vn_row_id][$ps_tablename][$vs_opt_md5])) {
				$va_base_row_ids[$vn_row_id] = caExtractValuesFromArrayList($va_related_items, $t_rel_instance->primaryKey());
				$va_related_ids += $va_base_row_ids[$vn_row_id];
				$pa_cache[$this->ops_table_name][$vn_row_id][$ps_tablename][$vs_opt_md5] = $va_base_row_ids[$vn_row_id];
			} else {
				$pa_cache[$this->ops_table_name][$vn_row_id][$ps_tablename][$vs_opt_md5] = array();
			}
		}
		
		return array_unique($va_related_ids);
	}
	# ------------------------------------------------------------------
	/**
	 * TODO: implement prefetch of related and non-indexed-stored fields. Basically, instead of doing a query for every row via get() [which will still be an option if you're lazy]
	 * prefetch() will allow you to tell SearchResult to preload values for a set of hits starting at $pn_start 
	 * Because this can be done in a single query it'll presumably be faster than lazy loading lots of rows
	 */
	public function prefetch($ps_tablename, $pn_start, $pn_num_rows, $pa_options=null) {
		if (!$ps_tablename ) { return; }
		
		$vs_md5 = caMakeCacheKeyFromOptions($pa_options);
		
		// get row_ids to fetch
		if (isset($pa_options['row_ids']) && is_array($pa_options['row_ids'])) {
			$va_row_ids = $pa_options['row_ids'];
		} else {
			$va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows);
		}
		if (sizeof($va_row_ids) == 0) { return false; }
		
		// do join
		$va_joins = array();
		
		if (!($t_instance = SearchResult::$s_instance_cache[$this->ops_table_name])) {
			$t_instance = SearchResult::$s_instance_cache[$this->ops_table_name] = SearchResult::$opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
		}
		if (!($t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = SearchResult::$opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}
		if (!$t_instance || !$t_rel_instance) { return; }
		
		if ($ps_tablename != $this->ops_table_name) {
			$va_fields = $this->opa_tables[$ps_tablename]['fieldList'];
			$va_fields[] = $this->ops_table_name.'.'.$this->ops_table_pk;
			
			// Include type_id field for item table (eg. ca_entities.type_id)
			if (method_exists($t_rel_instance, "getTypeFieldName") && ($t_rel_instance->getTypeFieldName()) && ($vs_type_fld_name = $t_rel_instance->getTypeFieldName())) {
				$va_fields[] = $t_rel_instance->tableName().'.'.$vs_type_fld_name.' item_type_id';
			} else {
				// Include type_id field for item table (eg. ca_entities.type_id) when fetching labels
				if (method_exists($t_rel_instance, "getSubjectTableInstance")) {
					$t_label_subj_instance = $t_rel_instance->getSubjectTableInstance();
					if (method_exists($t_label_subj_instance, "getTypeFieldName") && ($vs_type_fld_name = $t_label_subj_instance->getTypeFieldName())) {
						$va_fields[] = $t_label_subj_instance->tableName().'.'.$vs_type_fld_name.' item_type_id';
					}
				}
			}
			
			$va_joined_table_info = $this->opa_tables[$ps_tablename];
			$va_linking_tables = $va_joined_table_info['joinTables'];
			if (!is_array($va_linking_tables)) { $va_linking_tables = array(); }
			array_push($va_linking_tables, $ps_tablename);
			
			$vs_left_table = $this->ops_table_name;

			$va_order_bys = array();
			foreach($va_linking_tables as $vs_right_table) {
				$vs_join_eq = '';
				if (($va_rels = SearchResult::$opo_datamodel->getOneToManyRelations($vs_left_table)) && is_array($va_rels[$vs_right_table])) {
					$va_acc = array();
					foreach($va_rels[$vs_right_table] as $va_rel) {
						$va_acc[] =	$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
					}
					$vs_join_eq = join(" OR ", $va_acc);
					$va_joins[] = 'INNER JOIN '.$vs_right_table.' ON '.$vs_join_eq; 
					
					if (!($t_link = SearchResult::$s_instance_cache[$va_rel['many_table']])) {
						$t_link = SearchResult::$s_instance_cache[$va_rel['many_table']] = SearchResult::$opo_datamodel->getInstanceByTableName($va_rel['many_table'], true);
					}
					if (is_a($t_link, 'BaseRelationshipModel') && $t_link->hasField('type_id')) {
						$va_fields[] = $va_rel['many_table'].'.type_id rel_type_id';
					}
					if ($t_link->hasField('rank')) { 
						$va_order_bys[] = $t_link->tableName().'.rank';
					}
				} else {
					if (($va_rels = SearchResult::$opo_datamodel->getOneToManyRelations($vs_right_table)) && is_array($va_rels[$vs_left_table])) {
						$va_acc = array();
						foreach($va_rels[$vs_left_table] as $va_rel) {
							$va_acc[] = $va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
						}
						$vs_join_eq = join(" OR ", $va_acc);
						$va_joins[] = 'INNER JOIN '.$vs_right_table.' ON '.$vs_join_eq; 
					}
				}
				
				$vs_left_table = $vs_right_table;
			}
		} else {
			$va_fields = array('*');
		}
		
		$vs_criteria_sql = '';
		if (is_array($this->opa_tables[$ps_tablename]['criteria']) && (sizeof($this->opa_tables[$ps_tablename]['criteria']) > 0)) {
			$vs_criteria_sql = ' AND ('.join(' AND ', $this->opa_tables[$ps_tablename]['criteria']).')';
		}
		
		if(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_instance->hasField('access')) {
			$vs_criteria_sql .= " AND ({$ps_tablename}.access IN (".join(",", $pa_options['checkAccess']) ."))";	
		}
		if(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_instance->hasField('access')) {
			$vs_criteria_sql .= " AND ({$this->ops_table_name}.access IN (".join(",", $pa_options['checkAccess']) ."))";	
		}
	
		$vb_has_locale_id = true;
		if ($this->opo_subject_instance->hasField('locale_id') && (!$t_rel_instance->hasField('locale_id'))) {
			$va_fields[] = $this->ops_table_name.'.locale_id';
			$vb_has_locale_id = true;
		}
		
		if ($t_rel_instance->hasField('idno_sort')) {
			$va_order_bys [] = $t_rel_instance->tableName().".idno_sort";
		}
	
		$vs_deleted_sql = '';
		$vs_rel_pk = $t_rel_instance->primaryKey();
		if ($t_rel_instance->hasField('deleted')) {
			$vs_deleted_sql = " AND (".$t_rel_instance->tableName().".deleted = 0)";
		}
		
		$vs_order_by = sizeof($va_order_bys) ? " ORDER BY ".join(", ", $va_order_bys) : "";
		$vs_sql = "
			SELECT ".join(',', $va_fields)."
			FROM ".$this->ops_table_name."
			".join("\n", $va_joins)."
			WHERE
				".$this->ops_table_name.'.'.$this->ops_table_pk." IN (".join(',', $va_row_ids).") {$vs_criteria_sql} {$vs_deleted_sql}
			{$vs_order_by}
		";
		
		$qr_rel = $this->opo_subject_instance->getDb()->query($vs_sql);
		
		$vs_rel_pk = $t_rel_instance->primaryKey();
		while($qr_rel->nextRow()) {
			$va_row = $qr_rel->getRow();
			$vn_row_id = $va_row[$this->ops_table_pk];
			$vn_rel_row_id = $va_row[$vs_rel_pk];
			
			$vn_locale_id = $vb_has_locale_id ? $va_row['locale_id'] : null;
			self::$s_prefetch_cache[$ps_tablename][$vn_row_id][$vs_md5][$vn_locale_id][$vn_rel_row_id] = $va_row;
		}
		
		// Fill row_id values for which there is nothing to prefetch with an empty lists
		// otherwise we'll try and prefetch these again later wasting time.
		foreach($va_row_ids as $vn_row_id) {
			if (!isset(self::$s_prefetch_cache[$ps_tablename][$vn_row_id][$vs_md5])) {
				self::$s_prefetch_cache[$ps_tablename][$vn_row_id][$vs_md5] = array();
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function prefetchRelated($ps_tablename, $pn_start, $pn_num_rows, $pa_options) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!method_exists($this->opo_subject_instance, "getRelatedItems")) { return false; }
		unset($pa_options['request']);
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows)) == 0) { return false; }
		
		SearchResult::checkCacheSizeLimit($this->ops_table_name);
		
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
		
		$vs_md5 = caMakeCacheKeyFromOptions($pa_options);
		
		$va_criteria = is_array($this->opa_tables[$ps_tablename]) ? $this->opa_tables[$ps_tablename]['criteria'] : null;
		
		$va_opts = array_merge($pa_options, array('row_ids' => $va_row_ids, 'criteria' => $va_criteria));
		if (!isset($va_opts['limit'])) { $va_opts['limit'] = 100000; }
	
		$va_rel_items = $this->opo_subject_instance->getRelatedItems($ps_tablename, $va_opts);		// if there are more than 100,000 then we have a problem
		
		if (!is_array($va_rel_items) || !sizeof($va_rel_items)) { return; }
		
		if (!isset($this->opa_tables[$ps_tablename])) {
			$va_join_tables = SearchResult::$opo_datamodel->getPath($this->ops_table_name, $ps_tablename);
			array_shift($va_join_tables); 	// remove subject table
			array_pop($va_join_tables);		// remove content table (we only need linking tables here)
			
			$this->opa_tables[$ps_tablename] = array(
				'fieldList' => array($ps_tablename.'.*'),
				'joinTables' => array_keys($va_join_tables),
				'criteria' => array()
			);
		}
		
		
		foreach($va_rel_items as $vs_key => $va_rel_item) {
			self::$s_rel_prefetch_cache[$this->ops_table_name][(int)$va_rel_item['row_id']][$ps_tablename][$vs_md5][$va_rel_item[$va_rel_item['_key']]] = $va_rel_item;
		}
		
		//$this->prefetch($ps_tablename, $pn_start, $pn_num_rows);
		
		// Fill row_id values for which there is nothing to prefetch with an empty lists
		// otherwise we'll try and prefetch these again later wasting time.
		foreach($va_row_ids as $vn_row_id) {
			if (!isset(self::$s_rel_prefetch_cache[$this->ops_table_name][(int)$vn_row_id][$ps_tablename][$vs_md5])) {
				self::$s_rel_prefetch_cache[$this->ops_table_name][(int)$vn_row_id][$ps_tablename][$vs_md5] = array();
			}
		}
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function prefetchChangeLogData($ps_tablename, $pn_start, $pn_num_rows) {
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows)) == 0) { return false; }
		$vs_key = caMakeCacheKeyFromOptions(array_merge($va_row_ids, array('_table' => $ps_tablename)));
		if (self::$s_timestamp_cache['fetched'][$vs_key]) { return true; }
		
		$o_log = new ApplicationChangeLog();
	
		if (!is_array(self::$s_timestamp_cache['created_on'][$ps_tablename])) { self::$s_timestamp_cache['created_on'][$ps_tablename] = array(); }
		self::$s_timestamp_cache['created_on'][$ps_tablename] += $o_log->getCreatedOnTimestampsForIDs($ps_tablename, $va_row_ids);
		if (!is_array(self::$s_timestamp_cache['last_changed'][$ps_tablename])) { self::$s_timestamp_cache['last_changed'][$ps_tablename] = array(); }
		self::$s_timestamp_cache['last_changed'][$ps_tablename] += $o_log->getLastChangeTimestampsForIDs($ps_tablename, $va_row_ids);

		self::$s_timestamp_cache['fetched'][$vs_key] = true;
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getPrimaryKey() {
		return $this->opo_engine_result->get($this->opo_subject_instance->primaryKey());
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getPrimaryKeyValues($pn_limit=null) {
		return $this->opo_engine_result->getHits($pn_limit);
	}
	# ------------------------------------------------------------------
	/**
	  * Returns a list of values for the specified field from all rows in the result set. 
	  * If you need to extract all values from single field in a result set this method provides a convenient means to do so.
	  *
	  * @param mixed $ps_field Array of field names or single name of field to fetch
	  * @return array List of values for the specified fields
	  */
	public function getAllFieldValues($pm_field, $pa_options=null) {
		$vn_current_row = $this->currentIndex();
		$this->seek(0);
		
		$va_values = array();
		if(!is_array($pm_field)) {
			while($this->nextHit()) {
				$va_values[] = $this->get($pm_field, $pa_options);
			}
		} else {
			while($this->nextHit()) {
				foreach($pm_field as $vs_field) {
					$va_values[$vs_field][] = $this->get($vs_field, $pa_options);
				}
			}
		}
		
		$this->seek($vn_current_row - 1);
		
		return $va_values;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns a value from the query result. This can be a single value if it is a field in the subject table (eg. objects table in an objects search), or
	 * perhaps multiple related values (eg. related entities in an objects search). 
	 *
	 * You can fetch the values attached to a subject using the bundle specification, generally in the format <subject_table_name>.<element_code> (ex. ca_objects.date_created)
	 * If the bundle is a container then you can fetch a specific value using the format <subject_table_name>.<attribute_element_code>.<value_element_code>
	 * For example, to get the "date_value" value out of a "date" container attached to a ca_objects row, get() would be called with the field parameter set to ca_objects.date.date_value
	 *
	 * By default get() returns a string for display in the current locale. You can control the formatting of the output using various options described below including "template" (format output using a displayt template),
	 * "makeLink" (convert references to records into clickable links) and "delimiter" (specify text to place between multiple values)
	 *
	 * When the "returnAsArray" option is set get() will return a numerically indexed array list of values. This array will always be one-dimensional with a sequence of display values.
	 *
	 * You can force values for all available locales to be included in the returned string or array list using the "returnAllLocales" option.
	 * 
	 * CollectiveAccess stores related, repeating and multilingual data in a fairly complex series of nested structures. get() is intended to faciliate output of data so most of its options are geared towards
	 * flattening of data for easy of formatting and display, with commensurate loss of internal structre. Set the "returnWithStructure" option to obtain the "raw" data with all of its internal structure intact. The
	 * returned value will be a multidimensional array tailored to the type of data being returned. Typically this array will be indexed first by the id of the record to which the returned data is attached, then
	 * by locale_id or code (if "returnAllLocales" is set), then the id specific to the data item (Eg. internal attribute_id for metadata, label_id for labels, Etc.), and finally an array with keys set to data element names
	 * and associated values.
	 *
	 * Return values can be modified using the following options:
	 *
	 *		[Options controlling type of return value]
	 *			returnAsArray = return values in a one-dimensional, numerically indexed array. If not not a string is always returned. [Default is false]
	 *			returnWithStructure = return values in a multi-dimensional array mirroring the internal storage structure of CollectiveAccess. [Default is false]
	 *			returnAsSearchResult = return values as search result instance. [Default is false]
	 *			returnAsCount = return the number of values that would be returned. If returnAsArray or returnWithStructure is set then the count will be returned as a one-element array, otherwise an integer value will be returned. [Default is false]
	 *
	 *		[Options controlling scope of data in return value]
	 *			returnAllLocales = Return values from all available locales, rather than just the most appropriate locale for the current user. For string and array return values, returnAllLocales will result in inclusion of additional values. For returnWithStructure, additional entries keys on locale_id or code will be added.  [Default is false]
	 *			useLocaleCodes =  For returnWithStructure locale codes (ex. en_US) will be used rather than numeric locale_ids. [Default is false]
	 * 			restrictToTypes = For bundles referencing data in related tables (ex. calling ca_entities.idno from a ca_objects result) will restrict returned items to those of the specified types. An array of list item idnos and/or item_ids may be specified. [Default is null]
 	 *			restrictToRelationshipTypes =  For bundles referencing data in related tables (ex. calling ca_entities.idno from a ca_objects result) will restrict returned items to those related using the specified relationship types. An array of relationship type idnos and/or type_ids may be specified. [Default is null]
 	 *			excludeTypes = For bundles referencing data in related tables (ex. calling ca_entities.idno from a ca_objects result) will restrict returned items to those *not* of the specified types. An array of list item idnos and/or item_ids may be specified. [Default is null]
 	 *			excludeRelationshipTypes = For bundles referencing data in related tables (ex. calling ca_entities.idno from a ca_objects result) will restrict returned items to those *not* related using the specified relationship types. An array of relationship type idnos and/or type_ids may be specified. [Default is null]
 	 *			restrictToType = Synonym for restrictToTypes. [Default is null]
 	 *			restrictToRelationshipType = Synonym for restrictToRelationshipTypes. [Default is null]
 	 *			excludeType = Synonym for excludeTypes. [Default is null]
 	 *			excludeRelationshipType = Synonym for excludeRelationshipTypes. [Default is null]
 	 *			excludeValues = An array of values to exclude if found. [Default is null; return all values]
 	 *			excludeIdnos = An array of idnos to exclude if found. Only relevant when pulling related records, either via a related get spec (Eg. ca_entities.preferred_labels from a ca_object instance) or an authority metadata element. [Default is null; return all values]
 	 *			filters = Array list of elements to filter returned values on. The element must be part of the container being fetched from. For example, when fetching a value from a container element (ex. ca_objects.dates.date_value) you can filter on any other subelement in that container by passing the name of the subelement and a value (ex. "date_type" => "copyright"). Pass only the name of the subelement, not the full path that includes the table and container element. You may filter on multiple subelements by passing each subelement as a key in the array. Only values that match all filters are returned. You can filter on multiple values for a subelement by passing an array of values rather than a scalar (Eg. "date_type" => array("copyright", "patent")). Values that match *any* of the values will be returned. Only simple equivalance is supported. NOTE: Filters are only available when returnAsArray or returnWithStructure are set. [Default is null]
 	 *			assumeDisplayField = For returnWithStructure, return display field for ambiguous preferred label specifiers (ex. ca_entities.preferred_labels => ca_entities.preferred_labels.displayname). If set to false an array with all label fields is returned. [Default is true]
	 *			returnURL = When fetching intrinsic value of type FT_MEDIA return URL to media rather than HTML tag. [Default is false]
	 *			returnPath = When fetching intrinsic value of type FT_MEDIA return path to media rather than HTML tag. [Default is false] 
	 *			unserialize = When fetching intrinsic value of type FT_VARS (serialized variables) return unserialized value. [Default is false]
	 *			list = A list code or array or list codes to restrict returned values to when referencing ca_list_items values. [Default is null]
	 *			
	 *		[Formatting options for strings and arrays]
	 *			template = Display template use when formatting return values. @see http://docs.collectiveaccess.org/wiki/Display_Templates. [Default is null]
	 *			delimiter = Characters to place in between repeating values when returning a string
	 *			makeLink = Return value as a link to the relevant editor (Providence) or detail (Pawtucket) when bundle references data in a related table; return value as HTML link when value is URL type. [Default is false]
	 *			returnAsLink = Synonym for makeLink. [Default is false]
	 *			convertCodesToDisplayText = Convert list item_ids text in the user's preferred locale for display. [Default is false]
	 *			convertCodesToIdno = Convert list item_ids to idno's (ca_list_items.idno). If convertCodesToDisplayText is also set then it will take precedence. [Default is false]
	 *			output = Convert list item_ids to display text in user's preferred locale ("text") or idno ("idno"). This is an easier to type alternative to the convertCodesToDisplayText and convertCodesToIdno options. [Default is null]
	 *			sort = Array list of bundles to sort returned values on. Currently sort is only supported when getting related values via simple related <table_name> and <table_name>.related bundle specifiers. Eg. from a ca_objects results you can sort when fetching 'ca_entities', 'ca_entities.related', 'ca_objects.related', etc.. The sortable bundle specifiers are fields with or without tablename. Only those fields returned for the related tables (intrinsics and label fields) are sortable. You can also sort on attributes if returnWithStructure is set. [Default is null]
	*
	 *		[Formatting for strings only]
 	 *			toUpper = Force all values to upper case. [Default is false]
	 *			toLower = Force all values to lower case. [Default is false]
	 *			makeFirstUpper = Force first character of all values to upper case. [Default is false]
	 *			trim = Trim white space from beginning and end of string. [Default is false]
	 *			start = Return all values trimmed to start at the specified character. [Default is null]
	 *			length = Return all values truncated to a maximum length. [Default is null]
	 *			truncate = Return all values from the beginning truncated to a maximum length; equivalent of passing start=0 and length. [Default is null]
	 *			ellipsis = Add ellipsis ("...") to truncated values. Values will be set to the truncated length including the ellipsis. Eg. a value truncated to 12 characters will include 9 characters of text and 3 characters of ellipsis. [Default is false]
	 *
	 *		[Formatting options for hierarchies]
	 *			maxLevelsFromTop = Restrict the number of levels returned to the top-most beginning with the root. [Default is null]
	 *			maxLevelsFromBottom = Restrict the number of levels returned to the bottom-most starting with the lowest leaf node. [Default is null]
	 *			maxLevels = synonym for maxLevelsFromBottom. [Default is null]
	 *			removeFirstItems = Number of levels from top of hierarchy before returning. [Default is null]
	 *			hierarchyDirection = Order in which to return hierarchical levels. Set to either "asc" or "desc". "Asc"ending returns hierarchy beginning with the root; "desc"ending begins with the child furthest from the root. [Default is asc]
 	 *			allDescendants = Return all items from the full depth of the hierarchy when fetching children. By default only immediate children are returned. [Default is false]
	 * 			hierarchyDelimiter = Characters to place in between separate hiearchy levels. Defaults to the 'delimiter' option.
 	 *
	 *		[Front-end access control]		
	 *			checkAccess = Array of access values to filter returned values on. Available for any table with an "access" field (ca_objects, ca_entities, etc.). If omitted no filtering is performed. [Default is null]
 	 *
	 *	@param string $ps_field 
	 *	@param array $pa_options Options as described above
	 * 	@return mixed String or array
	 */
	public function get($ps_field, $pa_options=null) {
		$vb_return_as_count = isset($pa_options['returnAsCount']) ? (bool)$pa_options['returnAsCount'] : false;
		$vb_return_as_array = isset($pa_options['returnAsArray']) ? (bool)$pa_options['returnAsArray'] : false;
		$vb_return_with_structure = isset($pa_options['returnWithStructure']) ? (bool)$pa_options['returnWithStructure'] : false;
		if ($vb_return_as_search_result = isset($pa_options['returnAsSearchResult']) ? (bool)$pa_options['returnAsSearchResult'] : false) {
			$vb_return_as_array = true; 
			$vb_return_with_structure = $vb_return_all_locales = false;
			$pa_options['template'] = null;
			$pa_options['returnAsSearchResult'] = false;
		}
		
		if ($vb_return_with_structure) { $pa_options['returnAsArray'] = $vb_return_as_array = true; } // returnWithStructure implies returnAsArray
		
		// Return primary key of primary table as quickly as possible
		if (($ps_field == $this->ops_table_pk) || ($ps_field == $this->ops_table_name.'.'.$this->ops_table_pk)) {
			$vn_id = $this->opo_engine_result->get($this->ops_table_pk);
			
			if ($vb_return_as_count) {
				return $vb_return_as_array ? [1] : 1;
			} elseif($vb_return_as_search_result) {
				return caMakeSearchResult($this->ops_table_name, [$vn_id], $pa_options);
			} elseif($vb_return_as_array || $vb_return_with_structure) {
				return [$vn_id];
			} else {
				return $vn_id;
			}
		}
		
		if (isset($pa_options['template']) && $pa_options['template']) {
			return $this->getWithTemplate($pa_options['template'], $pa_options);
		}
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		$va_filters = is_array($pa_options['filters']) ? $pa_options['filters'] : array();
		
		// Add table name to field specs that lack it
		if ((strpos($ps_field, '.') === false) && (!SearchResult::$opo_datamodel->tableExists($ps_field))) {
			$va_tmp = array($this->ops_table_name, $ps_field);
			$ps_field = $this->ops_table_name.".{$ps_field}";
		}
		
		$vb_return_all_locales 				= isset($pa_options['returnAllLocales']) ? (bool)$pa_options['returnAllLocales'] : false;

		$vs_delimiter 						= isset($pa_options['delimiter']) ? $pa_options['delimiter'] : ';';
		$vs_hierarchical_delimiter 			= isset($pa_options['hierarchyDelimiter']) ? $pa_options['hierarchyDelimiter'] : $vs_delimiter;
		$vb_unserialize 					= isset($pa_options['unserialize']) ? (bool)$pa_options['unserialize'] : false;
		
		$vb_return_url 						= isset($pa_options['returnURL']) ? (bool)$pa_options['returnURL'] : false;
		$vb_return_path 					= isset($pa_options['returnPath']) ? (bool)$pa_options['returnPAth'] : false;
		$vb_convert_codes_to_display_text 	= isset($pa_options['convertCodesToDisplayText']) ? (bool)$pa_options['convertCodesToDisplayText'] : false;
		$vb_convert_codes_to_idno 			= isset($pa_options['convertCodesToIdno']) ? (bool)$pa_options['convertCodesToIdno'] : false;
		
		$va_exclude_values 					= (isset($pa_options['excludeValues']) && $pa_options['excludeValues']) ? is_array($pa_options['excludeValues']) ? $pa_options['excludeValues'] : [$pa_options['excludeValues']] : [];
		$va_exclude_idnos					= (isset($pa_options['excludeIdnos']) && $pa_options['excludeIdnos']) ? is_array($pa_options['excludeIdnos']) ? $pa_options['excludeIdnos'] : [$pa_options['excludeIdnos']] : [];
		
		
		$vb_use_locale_codes 				= isset($pa_options['useLocaleCodes']) ? (bool)$pa_options['useLocaleCodes'] : false;
		$vb_assume_display_field 			= isset($pa_options['assumeDisplayField']) ? (bool)$pa_options['assumeDisplayField'] : true;
		
		if (!($vs_output = (isset($pa_options['output']) ? (string)$pa_options['output'] : null))) {
			if ($vb_convert_codes_to_display_text) { $vs_output = "text"; }
			if (!$vs_output && $vb_convert_codes_to_idno) { $vs_output = "idno"; }
		}
		if (!in_array($vs_output, array('text', 'idno', 'value'))) { $vs_output = 'value'; }
		$pa_options['output'] = $vs_output;
		
		if (!($vb_return_as_link = (isset($pa_options['makeLink']) ? (bool)$pa_options['makeLink'] : false))) {
			$vb_return_as_link 				= (isset($pa_options['returnAsLink']) ? (bool)$pa_options['returnAsLink'] : false); 
		}
		$pa_options['makeLink'] = $vb_return_as_link;
		
		$vn_max_levels_from_top 			= isset($pa_options['maxLevelsFromTop']) ? (int)$pa_options['maxLevelsFromTop'] : null;
		$vn_max_levels_from_bottom 			= caGetOption(array('maxLevelsFromBottom', 'maxLevels', 'level_limit', 'hierarchy_limit'), $pa_options, null);
		$vn_remove_first_items 				= isset($pa_options['removeFirstItems']) ? (int)$pa_options['removeFirstItems'] : 0;

		$va_check_access 					= isset($pa_options['checkAccess']) ? (is_array($pa_options['checkAccess']) ? $pa_options['checkAccess'] : array($pa_options['checkAccess'])) : null;
		$vs_template 						= isset($pa_options['template']) ? (string)$pa_options['template'] : null;
		
		
		$va_path_components = isset(SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_field]) ? SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_field] : $this->parseFieldPathComponents($ps_field);
		if ($va_path_components['is_count']) { 
			$vb_return_as_count = true; 
		} elseif($vb_return_as_count) {
			$va_path_components['is_count'] = true;
		}
		
		$va_val_opts = array_merge($pa_options, array(
			'returnAsArray' => $vb_return_as_array,
			'returnAllLocales' => $vb_return_all_locales,
			'returnWithStructure' => $vb_return_with_structure,
			'pathComponents' => $va_path_components,
			'delimiter' => $vs_delimiter,
			'makeLink' => $vb_return_as_link,
			'returnURL' => $vb_return_url,
			'returnPath' => $vb_return_path,
			'unserialize' => $vb_unserialize,
			'convertCodesToDisplayText' => $vb_convert_codes_to_display_text,
			'convertCodesToIdno' => $vb_convert_codes_to_idno,
			'checkAccess' => $va_check_access,
			'template' => $vs_template,
			'useLocaleCodes' => $vb_use_locale_codes,
			'excludeValues' => $va_exclude_values,
			'excludeIdnos' => $va_exclude_idnos
		));
		
		
		if ($va_path_components['table_name'] != $this->ops_table_name) {
			$vs_access_chk_key  = $va_path_components['table_name'].($va_path_components['field_name'] ? '.'.$va_path_components['field_name'] : '');
		} else {
			$vs_access_chk_key  = $va_path_components['field_name'];
		}

		if (($va_path_components['field_name'] !== 'access') && (caGetBundleAccessLevel($va_path_components['table_name'], $vs_access_chk_key) == __CA_BUNDLE_ACCESS_NONE__)) {
			return null;
		}
		
		if(!(($vs_value = $this->opo_engine_result->get($ps_field, $pa_options)) === false)) {
			if (in_array($vs_value, $va_exclude_values)) { return $vb_return_as_array ? [] : null; }
			if ($vb_return_as_array) {
				if ($vb_return_all_locales) {
					return array(1 => $vs_value);
				} else {
					return array($vs_value);
				}
			} elseif($vb_return_as_search_result) {
				return caMakeSearchResult($va_path_components['table_name'], [$vs_value], $pa_options);
			} elseif($vb_return_as_count) {
				return $vb_return_as_array ? [1] : 1;
			} else {
				return $vs_value;
			}
		}
		
		if (!($t_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']])) {
			$t_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']] = SearchResult::$opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
		}
		if (!$t_instance) { return null; }	// Bad table
		
		$vn_row_id = $this->opo_engine_result->get($this->ops_table_pk);
		$va_val_opts['primaryKey'] = $t_instance->primaryKey();
		
		if ($va_path_components['hierarchical_modifier']) {
			
			if ($vb_assume_display_field && in_array($va_path_components['field_name'], array('preferred_labels', 'nonpreferred_labels')) && !$va_path_components['subfield_name']) {
				$va_path_components['subfield_name'] = $va_path_components['components'][2] = $t_instance->getLabelDisplayField();
				$va_path_components['num_components'] = sizeof($va_path_components['components']);
			}
		
			switch($va_path_components['hierarchical_modifier']) {
				case 'parent':
					$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
					if ($va_path_components['related']) {
						// [RELATED TABLE PARENT]
						
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5])) {
							$this->prefetchHierarchyParents($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5];
					} else {
						// [PRIMARY TABLE PARENT]
						
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
							$this->prefetchHierarchyParents($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						$va_ids = array($vn_row_id);
					}
					if (!sizeof($va_ids)) { return $pa_options['returnAsArray'] ? array() : null; }
					
					$va_hiers = array();
					
					foreach($va_ids as $vn_id) {
						$va_parent_ids = array();
						if (
							isset(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])
							&&
							is_array(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])	
						) {
							if (!is_array($va_parent_ids = SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])) {
								return $pa_options['returnAsArray'] ? array() : null;
							}
						}
						
						$va_parent_ids = array_slice($va_parent_ids, 0, 1);
					
						if (!($qr_hier = $t_instance->makeSearchResult($va_path_components['table_name'], $va_parent_ids, $pa_options))) {
							return $pa_options['returnAsArray'] ? array() : null;
						}
			
						$va_tmp = array($va_path_components['table_name']);
						if ($va_path_components['field_name']) { $va_tmp[] = $va_path_components['field_name']; }
						if ($va_path_components['subfield_name']) { $va_tmp[] = $va_path_components['subfield_name']; }
						$vs_hier_fld_name = join(".", $va_tmp);
						
						$vs_pk = $t_instance->primaryKey();
						
						$vm_val = null;
						if($qr_hier->nextHit()) {
							$vm_val = $qr_hier->get($vs_hier_fld_name, $pa_options);
						}
						if ($vm_val) { $va_hiers[] = $vb_return_as_array ? array_shift($vm_val) : $vm_val; }
					}
					
					$vm_val = $vb_return_as_array ? $va_hiers : join($vs_hierarchical_delimiter, $va_hiers);
					goto filter;
					break;
				case 'hierarchy':
					$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
					
					// generate the hierarchy
					if ($va_path_components['related']) {
						// [RELATED TABLE HIERARCHY]
						
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5])) {
							$this->prefetchHierarchyParents($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						// ids of related items
						$va_ids = array_values(SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5]);
					
					} else {
						// [PRIMARY TABLE HIERARCHY]
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
							$this->prefetchHierarchyParents($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						$va_ids = array($vn_row_id);
					}
					if (!sizeof($va_ids)) { return $pa_options['returnAsArray'] ? array() : null; }
					
					$vs_hier_pk_fld = $t_instance->primaryKey();
					$va_hiers = $va_hier_ids = array();
					
					$vs_hierarchy_direction = isset($pa_options['hierarchyDirection']) ? strtolower($pa_options['hierarchyDirection']) : 'asc';

					if ($t_instance->isHierarchical()) {
						if ($va_path_components['field_name'] === $vs_hier_pk_fld) {
							if ($va_path_components['related']) {
								foreach($va_ids as $vn_id) {
									if(is_array(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])) {
										$va_hier_id_list = array_merge(array($vn_id), SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5]);
										$va_hier_id_list = array_filter($va_hier_id_list, function($v) { return $v > 0 ;});
										
										$va_hier_id_list = array_reverse($va_hier_id_list);
										
										if (!is_null($vn_max_levels_from_top) && ($vn_max_levels_from_top > 0)) {
											$va_hier_id_list = array_slice($va_hier_id_list, 0, $vn_max_levels_from_top, true);
										} elseif (!is_null($vn_max_levels_from_bottom) && ($vn_max_levels_from_bottom > 0)) {
											if (($vn_start = sizeof($va_hier_id_list) - $vn_max_levels_from_bottom) < 0) { $vn_start = 0; }
											$va_hier_id_list = array_slice($va_hier_id_list, $vn_start, $vn_max_levels_from_bottom, true);
										}
										
							
										if ($t_instance->getHierarchyType() == __CA_HIER_TYPE_MULTI_MONO__) {
											array_shift($va_hier_id_list);
										}
										if ($vs_hierarchy_direction === 'desc') { $va_hier_id_list = array_reverse($va_hier_id_list); }
										$va_hier_ids[] = $va_hier_id_list;
									}
								}
							} else {
								// Return ids from hierarchy in order
								if(is_array(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
									$va_hier_ids = array_merge(array($vn_row_id), SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5]);
								} else {
									$va_hier_ids = array($vn_row_id);
								}
								
								if(($vn_type_id = $this->get($va_path_components['table_name'].".type_id")) && ($va_restrict_to_types = caGetOption('restrictToTypes', $pa_options, null))) {
									$va_types = caMakeTypeIDList($va_path_components['table_name'], $va_restrict_to_types);
									if (!in_array($vn_type_id, $va_types)) { 
										array_shift($va_hier_ids);
									}
								}
								
								if (in_array($t_instance->getHierarchyType(), [__CA_HIER_TYPE_SIMPLE_MONO__, __CA_HIER_TYPE_MULTI_MONO__])) { array_pop($va_hier_ids); }
								if (!is_null($vn_max_levels_from_top) && ($vn_max_levels_from_top > 0)) {
									$va_hier_ids = array_slice($va_hier_ids, 0, $vn_max_levels_from_top, true);
								} elseif (!is_null($vn_max_levels_from_bottom) && ($vn_max_levels_from_bottom > 0)) {
									if (($vn_start = sizeof($va_hier_ids) - $vn_max_levels_from_bottom) < 0) { $vn_start = 0; }
									$va_hier_ids = array_slice($va_hier_ids, $vn_start, $vn_max_levels_from_bottom, true);
								}
								
								if ($vs_hierarchy_direction === 'asc') { $va_hier_ids = array_reverse($va_hier_ids); }
							}
							
							$vm_val = $vb_return_as_array ?  $va_hier_ids : join($vs_hierarchical_delimiter, $va_hier_ids);
							goto filter;
						} else {
							$vs_field_spec = join('.', array_values($va_path_components['components']));
						
							$va_ancestor_id_list = $this->get($va_path_components['table_name'].'.hierarchy.'.$vs_hier_pk_fld, array_merge($pa_options, array('returnAsArray' => true, 'returnAsLink'=> false, 'returnAllLocales' => false)));
						
							if (!is_array($va_ancestor_id_list)) { return $vb_return_as_array ? array() : null; }
							if (!$va_path_components['related']) {
								$va_ancestor_id_list = array($va_ancestor_id_list);
							}
							$va_hier_list = array();
							foreach($va_ancestor_id_list as $va_ancestor_ids) {
								if($vn_remove_first_items > 0) {
									$va_ancestor_ids = array_slice($va_ancestor_ids, $vn_remove_first_items);
								}
						
								$va_hier_item = array();
								if ($qr_hier = caMakeSearchResult($va_path_components['table_name'], $va_ancestor_ids, $pa_options)) {
							
									while($qr_hier->nextHit()) {
										$va_hier_item += $qr_hier->get($vs_field_spec, array('returnWithStructure' => true, 'returnAllLocales' => true, 'useLocaleCodes' => $pa_options['useLocaleCodes']));
									}
									if (!is_null($vn_max_levels_from_top) && ($vn_max_levels_from_top > 0)) {
										$va_hier_item = array_slice($va_hier_item, 0, $vn_max_levels_from_top, true);
									} elseif (!is_null($vn_max_levels_from_bottom) && ($vn_max_levels_from_bottom > 0)) {
										if (($vn_start = sizeof($va_hier_item) - $vn_max_levels_from_bottom) < 0) { $vn_start = 0; }
										$va_hier_item = array_slice($va_hier_item, $vn_start, $vn_max_levels_from_bottom, true);
									}
									$va_hier_list[] = $va_hier_item;
								}
							}
						}
					}
				
					$va_acc = [];
					foreach($va_hier_list as $vn_h => $va_hier_item) {
						if (!$vb_return_all_locales) { $va_hier_item = caExtractValuesByUserLocale($va_hier_item); }
					
						if ($vb_return_with_structure) {
							$va_acc[] = $va_hier_item;
						} elseif($this->ops_table_name == $va_path_components['table_name']) {
							// For primary table: return hier path as list (there can be only one hierarchical path)
							$va_acc = $this->_flattenArray($va_hier_item, $pa_options);
						} else {
							// For related tables: return each hier path as concatenated string as there can be repeats and returnAsArray must be a flat array
							$va_acc[] = join($vs_hierarchical_delimiter, $this->_flattenArray($va_hier_item, $pa_options));
						}
					}
					if (!$vb_return_as_array) { 
						return $vb_return_as_count ? sizeof($va_acc) : join($vs_delimiter, $va_acc);
					}
					$vm_val = $va_acc;
					goto filter;
					break;
				case 'children':
					// grab children 
					$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
					if ($va_path_components['related']) {
						// [RELATED TABLE CHILDREN]
						
						if (!isset(SearchResult::$opa_hierarchy_children_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5])) {
							$this->prefetchHierarchyChildren($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = SearchResult::$opa_hierarchy_children_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5];
					} else {
						// [PRIMARY TABLE CHILDREN]
						
						if (!isset(SearchResult::$opa_hierarchy_children_prefetch_cache[$this->ops_table_name][$vn_row_id][$vs_opt_md5])) {
							$this->prefetchHierarchyChildren($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = array($vn_row_id);
					}
					
					$va_hier_list = array();
					foreach($va_ids as $vn_id) {
						if (
							!is_array(SearchResult::$opa_hierarchy_children_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])
							||
							!sizeof(SearchResult::$opa_hierarchy_children_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])
						){ 
							continue;
						}
						$qr_hier = $t_instance->makeSearchResult($va_path_components['table_name'], SearchResult::$opa_hierarchy_children_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5], $pa_options);
						
						$va_tmp = array($va_path_components['table_name']);
						if ($va_path_components['field_name']) { $va_tmp[] = $va_path_components['field_name']; }
						if ($va_path_components['subfield_name']) { $va_tmp[] = $va_path_components['subfield_name']; }
						$vs_hier_fld_name = join(".", $va_tmp);
							
						$vs_pk = $t_instance->primaryKey();
						while($qr_hier->nextHit()) {
							$vm_val = $qr_hier->get($vs_hier_fld_name, $pa_options);
							$va_hier_list[$qr_hier->get($va_path_components['table_name'].'.'.$vs_pk)] = $vb_return_as_array ? array_shift($vm_val) : $vm_val;;
						}
					}
					
					if (!$vb_return_as_array) { 
						return $vb_return_as_count ? sizeof($va_hier_list) : join($vs_hierarchical_delimiter, $va_hier_list);
					}
					$vm_val = $va_hier_list;
					goto filter;
					break;
				case 'siblings':
					// grab siblings 
					$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
					if ($va_path_components['related']) {
						// [RELATED TABLE SIBLINGS]
						
						if (!isset(SearchResult::$opa_hierarchy_siblings_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5])) {
							$this->prefetchHierarchySiblings($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = SearchResult::$opa_hierarchy_siblings_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5];
						
					} else {
						// [PRIMARY TABLE SIBLINGS]
						
						if (!isset(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$this->ops_table_name][$vn_row_id][$vs_opt_md5])) {
							$this->prefetchHierarchySiblings($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = array($vn_row_id);
					}
					
					$va_hier_list = array();
					foreach($va_ids as $vn_id) {
						if (
							!is_array(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])
							||
							!sizeof(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5])
						){ 
							continue;
						}
						$qr_hier = $t_instance->makeSearchResult($va_path_components['table_name'], SearchResult::$opa_hierarchy_siblings_prefetch_cache[$va_path_components['table_name']][$vn_id][$vs_opt_md5], $pa_options);
						
						$va_tmp = array($va_path_components['table_name']);
						if ($va_path_components['field_name']) { $va_tmp[] = $va_path_components['field_name']; }
						if ($va_path_components['subfield_name']) { $va_tmp[] = $va_path_components['subfield_name']; }
						$vs_hier_fld_name = join(".", $va_tmp);
							
						$vs_pk = $t_instance->primaryKey();
						while($qr_hier->nextHit()) {
							$vm_val = $qr_hier->get($vs_hier_fld_name, $pa_options);
							$va_hier_list[$qr_hier->get($va_path_components['table_name'].'.'.$vs_pk)] = $vb_return_as_array ? array_shift($vm_val) : $vm_val;;
						}
					}
					
					if (!$vb_return_as_array) { 
						return $vb_return_as_count ? sizeof($va_hier_list) : join($vs_hierarchical_delimiter, $va_hier_list);
					}
					$vm_val = $va_hier_list;
					goto filter;
					break;
			}
			goto filter;
		}

		if ($va_path_components['related']) {
//
// [RELATED TABLE] 
//
			$vb_return_cache_options = false;
			if (caGetOption('returnCacheOptions', $pa_options, false)) {
				$vb_return_cache_options = true; unset($pa_options['returnCacheOptions']);
			}
			$vs_opt_md5 = caMakeCacheKeyFromOptions($va_get_opts = array_merge($pa_options, array('dontReturnLabels' => false)));

			if ($vb_return_cache_options) { return $va_get_opts; }
			
			if (!isset(self::$s_rel_prefetch_cache[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5])) {
				$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $va_get_opts);
			} 
			
			$va_related_items = self::$s_rel_prefetch_cache[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5];

			if (!is_array($va_related_items)) { return ($vb_return_with_structure || $vb_return_as_array) ? array() : null; }
		
			$vm_val = $this->_getRelatedValue($va_related_items, $va_val_opts);
			if ($vb_return_as_count) { return $vm_val; }
			goto filter;
		} else {
			if (!$va_path_components['hierarchical_modifier']) {
//
// [PRIMARY TABLE] Created on
//
				if ($va_path_components['field_name'] == 'created') {
					if (!isset(self::$s_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id])) {
						$this->prefetchChangeLogData($this->ops_table_name, $this->opo_engine_result->currentRow(), $this->getOption('prefetch'));
					}
			
					if ($vb_return_as_array) {
						if($va_path_components['subfield_name']) {
							$vm_val = [self::$s_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id][$va_path_components['subfield_name']]];
						} else {
							$vm_val = self::$s_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id];
						}
						goto filter;
					} else {
						$vs_subfield = $va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : 'timestamp';
						$vm_val = self::$s_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id]['timestamp'];
				
						if ($vs_subfield == 'timestamp') {
							$this->opo_tep->init();
							$this->opo_tep->setUnixTimestamps($vm_val, $vm_val);
							$vm_val = $this->opo_tep->getText($pa_options);
						}
						goto filter;
					}
				}
				
//
// [PRIMARY TABLE] Last modified on
//		
				if ($va_path_components['field_name'] == 'lastModified') {
					if (!isset(self::$s_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id])) {
						$this->prefetchChangeLogData($this->ops_table_name, $this->opo_engine_result->currentRow(), $this->getOption('prefetch'));
					}
			
					if ($vb_return_as_array) {
						if($va_path_components['subfield_name']) {
							$vm_val = [self::$s_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id][$va_path_components['subfield_name']]];
						} else {
							$vm_val = self::$s_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id];
						}
						goto filter;
					} else {
						$vs_subfield = $va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : 'timestamp';
						$vm_val = self::$s_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id]['timestamp'];
				
						if ($vs_subfield == 'timestamp') {
							$this->opo_tep->init();
							$this->opo_tep->setUnixTimestamps($vm_val, $vm_val);
							$vm_val = $this->opo_tep->getText($pa_options);
						}
						goto filter;
					}
				}
				$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
//
// [PRIMARY TABLE] Preferred/nonpreferred labels
//
				if (in_array($va_path_components['field_name'], array('preferred_labels', 'nonpreferred_labels')) && ($t_instance instanceof LabelableBaseModelWithAttributes)) {
					$vs_label_table_name = $t_instance->getLabelTableName();
					if (!isset(self::$s_prefetch_cache[$vs_label_table_name][$vn_row_id][$vs_opt_md5])) {
						$this->prefetchLabels($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
					}
					
					$vm_val = $this->_getLabelValue(self::$s_prefetch_cache[$vs_label_table_name][$vn_row_id][$vs_opt_md5], $t_instance, $va_val_opts);
					if ($vb_return_as_count) { return $vm_val; }
					goto filter;
				}
					
				if ($t_instance->hasField($va_path_components['field_name'])) {
					$va_val_opts['fieldInfo'] = $t_instance->getFieldInfo($va_path_components['field_name']);
//
// [PRIMARY TABLE] Plain old intrinsic
//
					if (!isset(self::$s_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
						$this->prefetch($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);	
					}
					$vm_val = $this->_getIntrinsicValue(self::$s_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5], $t_instance, $va_val_opts);
					goto filter;
				} elseif(method_exists($t_instance, 'isValidBundle') && !$t_instance->hasElement($va_path_components['field_name'], null, false, array('dontCache' => false)) && $t_instance->isValidBundle($va_path_components['field_name'])) {
//
// [PRIMARY TABLE] Special bundle
//				
					if (!isset(self::$s_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
						$this->prefetch($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);	
					}
					
					$vm_val = $t_instance->renderBundleForDisplay($va_path_components['field_name'], $vn_row_id, self::$s_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5], $va_val_opts);
					
					if ($pa_options['returnWithStructure']) { 
						if ($pa_options['returnAllLocales']) { 
							$vm_val = [$vn_row_id => [$t_instance->get('locale_id') => [$va_path_components['field_name'] => $vm_val]]];
						} else {
							$vm_val = [$vn_row_id => [$va_path_components['field_name'] => $vm_val]];
						}
					} elseif($pa_options['returnAsArray']) {
						$vm_val = [$vm_val];
					} 
					
					goto filter;
				} else {
//
// [PRIMARY TABLE] Metadata attribute
//				

					if (($t_instance instanceof BaseModelWithAttributes) && isset($va_path_components['field_name']) && $va_path_components['field_name'] && $t_element = ca_metadata_elements::getInstance($va_path_components['field_name'])) {
						$vn_element_id = $t_element->getPrimaryKey();
					} else {
						return $pa_options['returnAsArray'] ? array() : null;
					}
					if (!isset(ca_attributes::$s_get_attributes_cache[(int)$this->opn_table_num.'/'.(int)$vn_row_id][(int)$vn_element_id])) {
						$va_element_ids = ($vn_element_id ? array($vn_element_id) : null);
						if(is_array($va_prefetch_attributes = $this->getOption('prefetchAttributes')) && sizeof($va_prefetch_attributes)) {
							$va_element_ids = array_unique($va_element_ids + $va_prefetch_attributes);
						}
						ca_attributes::prefetchAttributes($this->opo_subject_instance->getDb(), $this->opn_table_num, $this->getRowIDsToPrefetch($this->opo_engine_result->currentRow(), $this->getOption('prefetch')), $va_element_ids, array('dontFetchAlreadyCachedValues' => true));
					}
					
					$va_attributes = ca_attributes::getAttributes($this->opo_subject_instance->getDb(), $this->opn_table_num, $vn_row_id, array($vn_element_id), array());

					$vm_val = $this->_getAttributeValue($va_attributes[$vn_element_id], $t_instance, $va_val_opts);
					if ($vb_return_as_count) { return $vm_val; }
					goto filter;
				}
			}
		}
		
		filter:
		
		
		// Sort structures by key
		$va_sort_fields = caGetOption('sort', $pa_options, null);
		if($va_sort_fields && !is_array($va_sort_fields)) { $va_sort_fields = [$va_sort_fields]; }
		if ($vb_return_as_array && is_array($vm_val) && is_array($va_sort_fields) && sizeof($va_sort_fields)) {
			$va_keys = array_map(function($v) { return array_pop(explode('.', $v)); }, $va_sort_fields);
			
			if (is_array($va_keys) && sizeof($va_keys)) {
				if ($vb_return_with_structure) {
					foreach($vm_val as $vn_top_level_id => $va_data) {
						if(!$va_data || !is_array($va_data)) { continue; }
						$vm_val[$vn_top_level_id] = caSortArrayByKeyInValue($va_data, $va_keys, caGetOption('sortDirection', $pa_options, 'ASC'));
					}
				}
			}
		}
		
		// process excludes 
		if (sizeof($va_exclude_values) > 0) {
			if ($vb_return_as_array && is_array($vm_val)) {
				if ($vb_return_with_structure) {
					if ($vb_return_all_locales) {
						foreach($vm_val as $vn_locale_id => $va_by_locale) {
							foreach($va_by_locale as $vn_i => $va_by_value) {
								foreach($va_by_locale as $vs_subfield => $vs_val) {
									if (in_array($vs_val, $va_exclude_values)) { unset($vm_val[$vn_locale_id][$vn_i]); }
								}
							}
						}
					} else {
						foreach($vm_val as $vn_i => $va_by_value) {
							foreach($va_by_value as $vs_subfield => $vs_val) {
								if (in_array($vs_val, $va_exclude_values)) { unset($vm_val[$vn_i]); break; }
							}
						}
					}
				} else {
					foreach($vm_val as $vn_i => $vs_val) {
						if (in_array($vs_val, $va_exclude_values)) { unset($vm_val[$vn_i]); }
					}
				}
			} elseif (in_array($vm_val, $va_exclude_values)) { $vm_val = null; } 
		}
		
		if ($vb_return_as_array && sizeof($va_filters)) {
			$va_tmp = explode(".", $ps_field);
			if (sizeof($va_tmp) > 1) { array_pop($va_tmp); }
			
			
			if (!($t_instance = SearchResult::$s_instance_cache[$va_tmp[0]])) {
				$t_instance = SearchResult::$s_instance_cache[$va_tmp[0]] = SearchResult::$opo_datamodel->getInstanceByTableName($va_tmp[0], true);
			}
			
			if ($t_instance) {
				$va_keepers = array();
				foreach($va_filters as $vs_filter => $va_filter_vals) {
					if(!$vs_filter) { continue; }
					if (!is_array($va_filter_vals)) { $va_filter_vals = array($va_filter_vals); }
					
					foreach($va_filter_vals as $vn_index => $vs_filter_val) {
						// is value a list attribute idno?
						if (!is_numeric($vs_filter_val) && (($t_element = ca_metadata_elements::getInstance($vs_filter)) && ($t_element->get('datatype') == 3))) {
							$va_filter_vals[$vn_index] = caGetListItemID($t_element->get('list_id'), $vs_filter_val);
						}
					}
					
					$va_filter_values = $this->get(join(".", $va_tmp).".{$vs_filter}", array('returnAsArray' => true, 'alwaysReturnItemID' => true, 'sort' => caGetOption('sort', $pa_options, null), 'sortDirection' => caGetOption('sortDirection', $pa_options, null)));
			
					if (is_array($va_filter_values)) {
						foreach($va_filter_values as $vn_id => $vm_filtered_val) {
							if ((!isset($va_keepers[$vn_id]) || $va_keepers[$vn_id]) && in_array($vm_filtered_val, $va_filter_vals)) {	// any match for the element counts
								$va_keepers[$vn_id] = true;
							} else {	// if no match on any criteria kill it
								$va_keepers[$vn_id] = false;
							}
						}
					}
				}
			
				$va_filtered_vals = array();
				foreach($va_keepers as $vn_id => $vb_include) {
					if (!$vb_include) { continue; }
					$va_filtered_vals[$vn_id] = $vm_val[$vn_id];
				}
				if ($vb_return_as_count) {
					return [sizeof($va_filtered_vals)];
				} else {
					return array_values($va_filtered_vals);
				}
			}
		}
		
		if ($vb_return_as_search_result) {
			if (!is_array($vm_val) || !sizeof($vm_val)) { return null; }
			return caMakeSearchResult($va_path_components['table_name'], $vm_val, $pa_options);
		}
		
		if ($vb_return_as_count) {
			$vn_count = is_array($vm_val) ? sizeof($vm_val) : 1;
			return $vb_return_as_array ? [$vn_count] : $vn_count;
		}
		return $vm_val;
	}
	# ------------------------------------------------------------------
	/**
	 * Results are speculatively pre-fetched in blocks to improve performance. This can cause issues
	 * if you're inserting rows and expect a previously create SearchResult to "see" those new rows. 
	 * SearchResult::clearResultCacheForTable() will clear the result cache for a table and, if applicable, the table
	 * storing related labels, causing get() to re-fetch fresh data for the table on next invocation.
	 *
	 * @param string $ps_table Name of table to purge cache for
	 * @return void
	 */
	public static function clearResultCacheForTable($ps_table) {
		unset(self::$s_prefetch_cache[$ps_table]);
		unset(self::$s_rel_prefetch_cache[$ps_table]);

		$ps_label_table = LabelableBaseModelWithAttributes::getLabelTable($ps_table);
		if($ps_label_table) {
			unset(self::$s_prefetch_cache[$ps_label_table]);
			unset(self::$s_rel_prefetch_cache[$ps_label_table]);
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Results are speculatively pre-fetched in blocks to improve performance. This can cause issues
	 * if you're inserting rows and expect a previously create SearchResult to "see" those new rows. 
	 * SearchResult::clearResultCacheForRow() will clear the result cache for a single row, specified by its primary key,
	 * causing get() to re-fetch fresh data for the row. If applicable, the table storing related labels will also be purged. 
	 *
	 * @param string $ps_table Name of table to purge cache for
	 * @pram int $pn_row_id The primary key of the row to purge cache for
	 * @return void
	 */
	public static function clearResultCacheForRow($ps_table, $pn_row_id) {
		unset(self::$s_prefetch_cache[$ps_table][$pn_row_id]);
		unset(self::$s_rel_prefetch_cache[$ps_table][$pn_row_id]);

		$ps_label_table = LabelableBaseModelWithAttributes::getLabelTable($ps_table);
		if($ps_label_table) {
			unset(self::$s_prefetch_cache[$ps_label_table][$pn_row_id]);
			unset(self::$s_rel_prefetch_cache[$ps_label_table][$pn_row_id]);
		}
	}
	# ------------------------------------------------------------------
	/**
	 * get() value for related table
	 *
	 * @param array $pa_value_list
	 * @param array Options 
	 *
	 * @return array|string
	 */
	private function _getRelatedValue($pa_value_list, $pa_options=null) {
		$vb_return_as_link 		= $pa_options['returnAsLink'];
		$va_path_components		=& $pa_options['pathComponents'];
		
		$vb_assume_display_field 	= isset($pa_options['assumeDisplayField']) ? (bool)$pa_options['assumeDisplayField'] : true;
		
		$pa_check_access		= $pa_options['checkAccess'];
		if (!is_array($pa_exclude_idnos	= $pa_options['excludeIdnos'])) { $pa_exclude_idnos = []; }
		
		if (!($t_rel_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']] = SearchResult::$opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
		}
		
		if (!($t_rel_instance instanceof BaseModel)) { return null; }
		
		// Handle table-only case...
		if (!$va_path_components['field_name']) {
			if ($pa_options['returnWithStructure']) {
				return $pa_value_list;
			} else {
				// ... by returning a list of preferred label values 
				$va_path_components['field_name'] = $va_path_components['components'][1] = 'preferred_labels';
				$va_path_components['subfield_name'] = $va_path_components['components'][2] = $t_rel_instance->getLabelDisplayField();
				$va_path_components['num_components'] = sizeof($va_path_components['components']);
			}	
		}
		
		if ($vb_assume_display_field && in_array($va_path_components['field_name'], array('preferred_labels', 'nonpreferred_labels')) && !$va_path_components['subfield_name']) {
			$va_path_components['subfield_name'] = $va_path_components['components'][2] = $t_rel_instance->getLabelDisplayField();
			$va_path_components['num_components'] = sizeof($va_path_components['components']);
		}
		$vs_pk = $t_rel_instance->primaryKey();
		
		
		$va_ids = array();
		foreach($pa_value_list as $vn_i => $va_rel_item) {
			$va_ids[] = $va_rel_item[$vs_pk];
		}
		if (!sizeof($va_ids)) { return $pa_options['returnAsArray'] ? array() : null; }

		
		if (!($qr_rel = caMakeSearchResult($va_path_components['table_name'], $va_ids, array('instance' => $t_rel_instance)))) { return null; }

		$va_return_values = array();
		$va_spec = array();
		foreach($va_path_components['components'] as $vs_v) {
			if ($vs_v) { $va_spec[] = $vs_v; }
		}

		$vs_idno_fld = $t_rel_instance->getProperty('ID_NUMBERING_ID_FIELD');
		$vs_rel_table_name = $t_rel_instance->tableName();
		
		$pa_restrict_to_lists = caGetOption('list', $pa_options, null, ['castTo' => 'array']);
		if (is_array($pa_restrict_to_lists)) { $pa_restrict_to_lists = caMakeListIDList($pa_restrict_to_lists); }
		
		while($qr_rel->nextHit()) {
			$vm_val = $qr_rel->get(join(".", $va_spec), $pa_options);
			if (is_array($pa_check_access) && sizeof($pa_check_access) && $t_rel_instance->hasField('access') && !in_array($qr_rel->get($va_path_components['table_name'].".access"), $pa_check_access)) {
				continue;
			}
			
			if (in_array($qr_rel->get("{$vs_rel_table_name}.{$vs_idno_fld}"), $pa_exclude_idnos)) {
				continue;
			}
			
			if (($vs_rel_table_name == 'ca_list_items') && is_array($pa_restrict_to_lists) && sizeof($pa_restrict_to_lists) && !in_array($qr_rel->get("ca_list_items.list_id"), $pa_restrict_to_lists)) {
				continue;
			}
			
			if (is_null($vm_val)) { continue; } // Skip null values; indicates that there was no related value
			
			if ($pa_options['returnWithStructure']) {
				if (!is_array($vm_val)) { $vm_val = array($vm_val); }
				$va_return_values = array_merge($va_return_values, $vm_val);
			} elseif ($pa_options['returnAsArray']) {
				if (!is_array($vm_val)) { $vm_val = array($vm_val); }
				foreach($vm_val as $vn_i => $vs_val) {
					// We include blanks in arrays so various get() calls on different fields in the same record set align
					$va_return_values[] = $vs_val;
				}
			} else {
				$va_return_values[] = $vm_val;
			}
		}
		if ($va_path_components['is_count']) {
			return $pa_options['returnAsArray'] ? [sizeof($va_return_values)] : sizeof($va_return_values); 
		}
		
		if ($pa_options['unserialize'] && !$pa_options['returnAsArray']) { return array_shift($va_return_values); }	
		if ($pa_options['returnAsArray']) { return is_array($va_return_values) ? $va_return_values : array(); } 
		
		if ($vb_return_as_link) {
			$va_return_values = caCreateLinksFromText($va_return_values, $t_rel_instance->tableName(), array($va_relation_info[$vs_pk]));
		}
		
		return (sizeof($va_return_values) > 0) ? join($pa_options['delimiter'], $va_return_values) : null;
	}
	# ------------------------------------------------------------------
	/**
	 * get() value for label
	 *
	 * @param array $pa_value_list
	 * @param BaseModel $pt_instance
	 * @param array Options
	 *
	 * @return array|string
	 */
	private function _getLabelValue($pa_value_list, $pt_instance, $pa_options) {
		$vb_assume_display_field 			= isset($pa_options['assumeDisplayField']) ? (bool)$pa_options['assumeDisplayField'] : true;
		$vb_convert_codes_to_display_text 	= isset($pa_options['convertCodesToDisplayText']) ? (bool)$pa_options['convertCodesToDisplayText'] : false;
		$vb_convert_codes_to_idno 			= isset($pa_options['convertCodesToIdno']) ? (bool)$pa_options['convertCodesToIdno'] : false;
		
		if ($vb_convert_codes_to_display_text) { $pa_options['output'] = 'text'; }
		if ($vb_convert_codes_to_idno) { $pa_options['output'] = 'idno'; }
		
		$va_path_components			=& $pa_options['pathComponents'];
		
		// Set subfield to display field if not specified and *NOT* returning as array
		if ($vb_assume_display_field && !$va_path_components['subfield_name']) { 
			$va_path_components['components'][2] = $va_path_components['subfield_name'] = $pt_instance->getLabelDisplayField(); 
			$va_path_components['num_components'] = sizeof($va_path_components['components']);
		}
		
		$vs_table_name = $pt_instance->tableName();
		$vs_pk = $pt_instance->primaryKey();
		
		$va_return_values = array();
		if (is_array($pa_value_list)) {
			foreach($pa_value_list as $vn_locale_id => $va_labels_by_locale) {
									
				if ($pa_options['useLocaleCodes']) {
					if (!$vn_locale_id || !($vm_locale_id = SearchResult::$opo_locales->localeIDToCode($vn_locale_id))) { $vm_locale_id = __CA_DEFAULT_LOCALE__; }; 
				} else {
					if (!($vm_locale_id = $vn_locale_id)) { $vm_locale_id = SearchResult::$opo_locales->localeCodeToID(__CA_DEFAULT_LOCALE__); }; 
				}
				
				foreach($va_labels_by_locale as $vn_id => $va_label) {
					$vn_id = $va_label[$vs_pk];
				
					
					if (isset($va_label['is_preferred'])) {
						if ((((bool)$va_label['is_preferred']) && ($va_path_components['field_name'] == 'preferred_labels'))) {
							// noop
						} elseif (((!(bool)$va_label['is_preferred']) && ($va_path_components['field_name'] == 'nonpreferred_labels'))) {
							// noop
						} else {
							continue;
						}
					}
					$vs_val_proc = $va_label[$va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : $pt_instance->getLabelDisplayField()];
					
					switch($pa_options['output']) {
						case 'text':
							$vs_val_proc = $this->_convertCodeToDisplayText($vs_val_proc, $va_path_components, $pt_instance->getLabelTableInstance(), $pa_options);
							break;
						case 'idno':
							$vs_val_proc = $this->_convertCodeToIdno($vs_val_proc, $va_path_components, $pt_instance->getLabelTableInstance(), $pa_options);
							break;
					}
					
					if ($pa_options['makeLink']) {
						$vs_val_proc = array_shift(caCreateLinksFromText(array($vs_val_proc), $vs_table_name, array($vn_id)));
					}
					
					if ($pa_options['returnWithStructure']) {
						if (!$va_path_components['subfield_name'] && isset($va_label['type_id'])) {
							$va_mod_path_components = array_merge($va_path_components, ['subfield_name' => 'type_id', 'num_components' => 3]);
							$va_mod_path_components['components'][2] = 'type_id';
							switch($pa_options['output']) {
								case 'text':
									$va_label['type_id'] = $this->_convertCodeToDisplayText($va_label['type_id'], $va_mod_path_components, $pt_instance->getLabelTableInstance(), $pa_options);
									break;
								case 'idno':
									$va_label['type_id'] = $this->_convertCodeToIdno($va_label['type_id'], $va_mod_path_components, $pt_instance->getLabelTableInstance(), $pa_options);
									break;
							}
						}
						$va_return_values[$vn_id][$vm_locale_id][$va_label['label_id']] = $va_path_components['subfield_name'] ? array($va_path_components['subfield_name'] => $vs_val_proc) : $va_label;
					} else {
						$va_return_values[$vn_id][$vm_locale_id][$va_label['label_id']] = $va_path_components['subfield_name'] ? $vs_val_proc : $va_label;
					}
				}
			}
		}
		
		if (!$pa_options['returnAllLocales']) { $va_return_values = caExtractValuesByUserLocale($va_return_values); } 	
		if ($pa_options['returnWithStructure']) { 
			return is_array($va_return_values) ? $va_return_values : array(); 
		}
		
		//
		// Flatten array for return as string or simple array value
		// 
		$va_flattened_values = $this->_flattenArray($va_return_values, $pa_options);
		
		if ($va_path_components['is_count']) {
			return $pa_options['returnAsArray'] ? [sizeof($va_flattened_values)] : sizeof($va_flattened_values); 
		}
		
		if ($pa_options['returnAsArray']) {
			return $va_flattened_values;
		} else {
			return (sizeof($va_flattened_values) > 0) ? join($pa_options['delimiter'], $va_flattened_values) : null;
		}
		
		return (sizeof($va_return_values) > 0) ? join($pa_options['delimiter'], $va_return_values) : null;
	}
	# ------------------------------------------------------------------
	/**
	 * get() value for attribute
	 *
	 * @param array $pa_value_list
	 * @param BaseModel $pt_instance
	 * @param array Options
	 *
	 * @return array|string
	 */
	private function _getAttributeValue($pa_value_list, $pt_instance, $pa_options) {
		$va_path_components					=& $pa_options['pathComponents'];
		$vs_delimiter						= isset($pa_options['delimiter']) ? $pa_options['delimiter'] : ';';
		$vb_convert_codes_to_display_text 	= isset($pa_options['convertCodesToDisplayText']) ? (bool)$pa_options['convertCodesToDisplayText'] : false;
		
		$va_return_values = [];
		
		$pa_exclude_idnos = caGetOption('excludeIdnos', $pa_options, []);
		if (!is_array($pa_exclude_idnos) && $pa_exclude_idnos) { $pa_exclude_idnos = [$pa_exclude_idnos]; } 
		
		$vn_id = $this->get($pt_instance->primaryKey(true));
		$vs_table_name = $pt_instance->tableName();
		
		if (is_array($pa_value_list) && sizeof($pa_value_list)) {
			$va_val_proc = array();
			foreach($pa_value_list as $o_attribute) {
				$t_attr_element = ca_metadata_elements::getInstance($o_attribute->getElementID());
				$vn_attr_type = $t_attr_element->get('datatype');
				
				$va_acc = array();
				$va_values = $o_attribute->getValues();
				
				if ($pa_options['useLocaleCodes']) {
					if (!$o_attribute->getLocaleID() || !($vm_locale_id = SearchResult::$opo_locales->localeIDToCode($o_attribute->getLocaleID()))) { $vm_locale_id = __CA_DEFAULT_LOCALE__; }; 
				} else {
					if (!($vm_locale_id = $o_attribute->getLocaleID())) { $vm_locale_id = SearchResult::$opo_locales->localeCodeToID(__CA_DEFAULT_LOCALE__); }; 
				}
				
				$vb_did_return_value = false;

				foreach($va_values as $o_value) {
					$vs_val_proc = null;
					$vb_dont_return_value = false;
					$vs_element_code = $o_value->getElementCode();
					
					$va_auth_spec = $vb_has_hierarchy_modifier = null; 
					if (is_a($o_value, "AuthorityAttributeValue")) {
						$va_auth_spec = $va_path_components['components'];
						
						$vb_has_hierarchy_modifier = SearchResult::_isHierarchyModifier($va_auth_spec);
						
						if (SearchResult::_isHierarchyModifier($va_path_components['field_name']) && $pt_instance->hasElement($va_path_components['subfield_name'], null, true, array('dontCache' => false))) {
							// ca_objects.hierarchy.authority_attr_code
							array_shift($va_auth_spec); // remove table spec
							array_shift($va_auth_spec); // remove hier modifier
							array_shift($va_auth_spec); // remove auth_attr_code
							
						} elseif (($vb_has_field_name = $pt_instance->hasElement($va_path_components['field_name'], null, true, array('dontCache' => false))) && $vb_has_hierarchy_modifier) {
							// ca_objects.authority_attr_code.hierarchy
							// ca_objects.authority_attr_code.authority_attr_subcode.hierarchy
							while(sizeof($va_auth_spec) && !SearchResult::_isHierarchyModifier($va_auth_spec[0])) {
								array_shift($va_auth_spec); // remove auth_attr_code
							}
						} elseif ($pt_instance->hasElement($va_path_components['field_name'], null, true, array('dontCache' => false))) {
							// ca_objects.authority_attr_code
							$va_auth_spec = [];
						}
					}
					
					if ($va_path_components['subfield_name'] && ($va_path_components['subfield_name'] !== $vs_element_code) && !SearchResult::_isHierarchyModifier($va_path_components['subfield_name']) && !($o_value instanceof InformationServiceAttributeValue) && !($o_value instanceof LCSHAttributeValue) && !($o_value instanceof MediaAttributeValue) && !($o_value instanceof FileAttributeValue)) {
						$vb_dont_return_value = true;
						if (!$pa_options['filter']) { continue; }
					}
									
					if (is_a($o_value, "AuthorityAttributeValue")) {
						$vs_auth_table_name = $o_value->tableName();
						if (!is_array($va_auth_spec) || !sizeof($va_auth_spec)) { $va_auth_spec = [SearchResult::$opo_datamodel->primaryKey($vs_auth_table_name)]; }
						array_unshift($va_auth_spec, $vs_auth_table_name);
						
						if ($qr_res = caMakeSearchResult($vs_auth_table_name, array($o_value->getID()))) {
							$va_options = $pa_options;
							unset($va_options['returnWithStructure']);
							$va_options['returnAsArray'] = true;
							
							if ($qr_res->nextHit()) {
								if (($t_instance = $o_value->elementTypeToInstance($o_value->getType())) && ($vs_idno_fld = $t_instance->getProperty('ID_NUMBERING_ID_FIELD'))) {
									if (in_array($qr_res->get($vs_auth_table_name.'.'.$vs_idno_fld), $pa_exclude_idnos)) {
										continue;
									}
								}
							
								$va_val_proc = $qr_res->get(join(".", $va_auth_spec), $va_options);
								if(is_array($va_val_proc)) {
									foreach($va_val_proc as $vn_i => $vs_v) {
										$vn_list_id = null;
										if ($o_value->getType() == __CA_ATTRIBUTE_VALUE_LIST__) {
											$t_element = ca_metadata_elements::getInstance($o_value->getElementID());
											$vn_list_id = $t_element->get('list_id');
										}

										$vb_did_return_value = true;
										if ($pa_options['returnWithStructure']) {
											$va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID().(($vn_i > 0) ? "_{$vn_i}" : '')][$vs_element_code] = $vb_has_hierarchy_modifier ? $vs_v : $o_value->getDisplayValue(array_merge($pa_options, array('output' => $pa_options['output'], 'list_id' => $vn_list_id)));
										} else {
											$va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID().(($vn_i > 0) ? "_{$vn_i}" : '')][] = $vb_has_hierarchy_modifier ? $vs_v : $o_value->getDisplayValue(array_merge($pa_options, array('output' => $pa_options['output'], 'list_id' => $vn_list_id)));
										}
									}
								}
							}
						}
						continue;
					}
					
					if (is_null($vs_val_proc)) {
						switch($o_value->getType()) {
						    case __CA_ATTRIBUTE_VALUE_MEDIA__:
						    case __CA_ATTRIBUTE_VALUE_FILE__:
						        $vs_return_type = 'tag';
                                $va_versions = $o_value->getVersions();
                                $vs_version = $va_versions[0];
                                
                                $va_e = array_slice($va_path_components['components'], 2);
                                foreach($va_e as $vs_e) {
                                    switch($vs_e) {
                                        case 'tag':
                                        case 'url':
                                        case 'path':
                                        case 'width':
                                        case 'height':
                                        case 'mimetype':
                                            $vs_return_type = $vs_e;
                                            break;
                                        default:
                                            if(in_array($vs_e, $va_versions)) {
                                                $vs_version = $vs_e;
                                            }
                                            break;
                                    }
                                }
                                $vs_val_proc = $o_value->getDisplayValue(['return' => $vs_return_type, 'version' => $vs_version]);
						        break;
							case __CA_ATTRIBUTE_VALUE_LIST__:
								$t_element = ca_metadata_elements::getInstance($o_value->getElementID());
								$vn_list_id = $t_element->get('list_id');
								if (in_array($o_value->getDisplayValue(array('output' => 'idno', 'list_id' => $vn_list_id)), $pa_exclude_idnos)) {
									continue(2);
								}
						
								$vs_val_proc = $o_value->getDisplayValue(array_merge($pa_options, array('output' => $pa_options['output'], 'list_id' => $vn_list_id)));
								break;
							case __CA_ATTRIBUTE_VALUE_LCSH__:
								switch($va_path_components['subfield_name']) {
									case 'text':
									case 'id':
									case 'url':
										$vs_val_proc = $o_value->getDisplayValue(array_merge($pa_options, array($va_path_components['subfield_name'] => true)));
										break;
									default:
										$vs_val_proc = $o_value->getDisplayValue(array_merge($pa_options, array('output' => $pa_options['output'])));
										break;
								}
								break;
							case __CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__:
								//ca_objects.informationservice.ulan_container
							
								// support subfield notations like ca_objects.wikipedia.abstract, but only if we're not already at subfield-level, e.g. ca_objects.container.wikipedia
								if($va_path_components['subfield_name'] && ($vs_element_code != $va_path_components['subfield_name']) && ($vs_element_code == $va_path_components['field_name'])) {
									switch($va_path_components['subfield_name']) {
										case 'uri':
										case 'url':
											$vs_val_proc = $o_value->getUri();
											break;
										default:
											$vs_val_proc = $o_value->getExtraInfo($va_path_components['subfield_name']);
											break;
									}
									$vb_dont_return_value = false;
									break;
								}

								// support ca_objects.container.wikipedia.abstract
								if(($vs_element_code == $va_path_components['subfield_name']) && ($va_path_components['num_components'] == 4)) {
									$vs_val_proc = $o_value->getExtraInfo($va_path_components['components'][3]);
									$vb_dont_return_value = false;
									break;
								}
							
								// support ca_objects.wikipedia or ca_objects.container.wikipedia (Eg. no "extra" value specified)
								if (($vs_element_code == $va_path_components['field_name']) || ($vs_element_code == $va_path_components['subfield_name'])) {
									$vs_val_proc = $o_value->getDisplayValue(array_merge($pa_options, array('output' => $pa_options['output'])));
									$vb_dont_return_value = false;
									break;
								}
								continue(2);
							default:
								if (in_array($o_value->getDisplayValue(array('output' => 'idno')), $pa_exclude_idnos)) {
									continue(2);
								}
								$vs_val_proc = $o_value->getDisplayValue(array_merge($pa_options, array('output' => $pa_options['output'])));
								break;
						}
					}
					
					if (($vn_attr_type == __CA_ATTRIBUTE_VALUE_CONTAINER__) && !$va_path_components['subfield_name'] && !$pa_options['returnWithStructure']) {
						if (strlen($vs_val_proc) > 0)  {$va_val_proc[] = $vs_val_proc; }
						$vs_val_proc = join($vs_delimiter, $va_val_proc);
					} 
					
					$va_spec = $va_path_components['components'];
					
					array_pop($va_spec);
					$va_acc[join('.', $va_spec).'.'.$vs_element_code] = $o_value->getDisplayValue(array_merge($pa_options, array('output' => 'idno')));
					
					if (!$vb_dont_return_value) {
						$vb_did_return_value = true;
						if($pa_options['makeLink']) { $vs_val_proc = array_shift(caCreateLinksFromText(array($vs_val_proc), $vs_table_name, array($vn_id))); }
					
						if ($pa_options['returnWithStructure']) {
							$va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID()][$vs_element_code] = $vs_val_proc;
							if ($o_value->getType() == __CA_ATTRIBUTE_VALUE_DATERANGE__) {  // add sortable alternate value for dates
							    $va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID()][$vs_element_code.'_sort_'] = $o_value->getDisplayValue(['sortable' => true]); // add sortable alternate representation for dates; this will be used by the SearchResult::get() "sort" option to properly sort date values
							}
						} else { 
							$va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID()][] = $vs_val_proc;	
						}
					}
				}
				
				if ($va_path_components['subfield_name'] && $pa_options['returnBlankValues'] && !$vb_did_return_value) {
					// value is missing so insert blank
					if ($pa_options['returnWithStructure']) {
						$va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID()][$va_path_components['subfield_name']] = '';
					} else { 
						$va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID()][] = '';	
					}
				}
				
				if ($pa_options['filter']) {
					$va_tags = caGetTemplateTags($pa_options['filter']);
			
					$va_vars = array();
					foreach($va_tags as $vs_tag) {
						if (isset($va_acc[$vs_tag])) { 
							$va_vars[$vs_tag] = $va_acc[$vs_tag];
						}  else {
							$va_vars[$vs_tag] = $this->get($vs_tag, array('convertCodesToIdno' => true));
						}
					}
					
					if (ExpressionParser::evaluate($pa_options['filter'], $va_vars)) {
						unset($va_return_values[(int)$vn_id][$vm_locale_id][(int)$o_attribute->getAttributeID()]);
						continue;
					}
				}
			}
		} else {
			// is blank
			if ($pa_options['returnWithStructure'] && $pa_options['returnBlankValues']) {
				$va_return_values[(int)$vn_id][null][null][$va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : $va_path_components['field_name']] = '';
			}	
		}
		
		if (!$pa_options['returnAllLocales']) { $va_return_values = caExtractValuesByUserLocale($va_return_values); } 	
		if ($pa_options['returnWithStructure']) { 
			return is_array($va_return_values) ? $va_return_values : array(); 
		}
		
		//
		// Flatten array for return as string or simple array value
		// 
		$va_flattened_values = array_map(function($v) { return is_array($v) ? join("; ", $v) : $v; }, $this->_flattenArray($va_return_values, $pa_options));
		
		if ($va_path_components['is_count']) {
			return $pa_options['returnAsArray'] ? [sizeof($va_flattened_values)] : sizeof($va_flattened_values); 
		}
		if ($pa_options['returnAsArray']) {
			return $va_flattened_values;
		} else {
			return (sizeof($va_flattened_values) > 0) ? join($pa_options['delimiter'], $va_flattened_values) : null;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * get() value for intrinsic
	 *
	 * @param array $pa_value_list
	 * @param BaseModel $pt_instance
	 * @param array Options
	 *
	 * @return array|string
	 */
	private function _getIntrinsicValue($pa_value_list, $pt_instance, $pa_options) {
		$vb_return_as_link 		= isset($pa_options['returnAsLink']) ? $pa_options['returnAsLink'] : false;
		$vb_get_direct_date 	= (bool) caGetOption(array('getDirectDate', 'GET_DIRECT_DATE'), $pa_options, false);
		$vb_sortable			= isset($pa_options['sortable']) ? $pa_options['sortable'] : false;
		
		$va_path_components		= $pa_options['pathComponents'];
		$va_field_info 			= $pa_options['fieldInfo'];
		$vs_pk 					= $pa_options['primaryKey'];
	
		$vs_table_name = $pt_instance->tableName();
		
		// Handle specific intrinsic types
		switch($va_field_info['FIELD_TYPE']) {
			case FT_DATERANGE:
			case FT_HISTORIC_DATERANGE:
            case FT_TIMESTAMP:
            case FT_DATETIME:
            case FT_HISTORIC_DATETIME:
				foreach($pa_value_list as $vn_locale_id => $va_values) {
					
					if ($pa_options['useLocaleCodes']) {
						if (!$vn_locale_id || !($vm_locale_id = SearchResult::$opo_locales->localeIDToCode($vn_locale_id))) { $vm_locale_id = __CA_DEFAULT_LOCALE__; }; 
					} else {
						if (!($vm_locale_id = $vn_locale_id)) { $vm_locale_id = SearchResult::$opo_locales->localeCodeToID(__CA_DEFAULT_LOCALE__); }; 
					}
					
					foreach($va_values as $vn_i => $va_value) {
						$va_ids[] = $vn_id = $va_value[$vs_pk];

                        if (in_array($va_field_info['FIELD_TYPE'], array(FT_TIMESTAMP, FT_DATETIME, FT_HISTORIC_DATETIME))) {
                            $vs_prop = $va_value[$va_path_components['field_name']];

                            if (!$vb_get_direct_date && !$vb_sortable) {
                                $this->opo_tep->init();
                                if ($va_field_info['FIELD_TYPE'] !== FT_HISTORIC_DATETIME) {
                                    $this->opo_tep->setUnixTimestamps($vs_prop, $vs_prop);
                                } else {
                                    $this->opo_tep->setHistoricTimestamps($vs_prop, $vs_prop);
                                }
                                $vs_prop = $this->opo_tep->getText($pa_options);
                            }
                        } elseif ($vb_get_direct_date) {
							$vs_prop = $va_value[$va_field_info['START']];
						} elseif($vb_sortable) {
							$vs_prop = $va_value[$va_field_info['START']];
						} else {
							$this->opo_tep->init();
							if ($va_field_info['FIELD_TYPE'] == FT_DATERANGE) {
								$this->opo_tep->setUnixTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
							} else {
								$this->opo_tep->setHistoricTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
							}
							$vs_prop = $this->opo_tep->getText($pa_options);
						}
						
						if ($vb_return_as_link) { $vs_prop = array_shift(caCreateLinksFromText(array($vs_prop), $vs_table_name, array($vn_id))); }
						
						$va_return_values[$vn_id][$vm_locale_id] = $vs_prop;
					}
				}
				break;
			case FT_MEDIA:
				if(!($vs_version = $va_path_components['subfield_name'])) {
					$vs_version = "largeicon"; // TODO: fix
				}
				
				foreach($pa_value_list as $vn_locale_id => $va_values) {
				
					if ($pa_options['useLocaleCodes']) {
						if (!$vn_locale_id || !($vm_locale_id = SearchResult::$opo_locales->localeIDToCode($vn_locale_id))) { $vm_locale_id = __CA_DEFAULT_LOCALE__; }; 
					} else {
						if (!($vm_locale_id = $vn_locale_id)) { $vm_locale_id = SearchResult::$opo_locales->localeCodeToID(__CA_DEFAULT_LOCALE__); }; 
					}
					
					foreach($va_values as $vn_i => $va_value) {
						$va_ids[] = $vn_id = $va_value[$vs_pk];
						
						
						$o_media_settings = new MediaProcessingSettings($va_path_components['table_name'], $va_path_components['field_name']);
						$va_versions = $o_media_settings->getMediaTypeVersions('*');
		
	
						if (!isset($va_versions[$vs_version])) {
							$va_tmp = array_keys($va_versions);
							$vs_version = array_shift($va_tmp);
						}
						
						// See if an info element was passed, eg. ca_object_representations.media.icon.width should return the width of the media rather than a tag or url to the media
						$vs_info_element = ($va_path_components['num_components'] == 4) ? $va_path_components['components'][3] : null;
			
						if($pa_options['unserialize']) {
							$va_return_values[$vn_id][$vm_locale_id] = caUnserializeForDatabase($va_value[$va_path_components['field_name']]);
						} elseif ($vs_info_element && (!in_array($vs_info_element, ['url', 'path', 'tag']))) {
							$va_return_values[$vn_id][$vm_locale_id] = $this->getMediaInfo($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $vs_info_element, $pa_options);
						} elseif ((isset($pa_options['returnURL']) && ($pa_options['returnURL'])) || ($vs_info_element == 'url')) {
							$va_return_values[$vn_id][$vm_locale_id] = $this->getMediaUrl($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
						} elseif ((isset($pa_options['returnPath']) && ($pa_options['returnPath'])) || ($vs_info_element == 'path')) {
							$va_return_values[$vn_id][$vm_locale_id] = $this->getMediaPath($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
						} else {
							$va_return_values[$vn_id][$vm_locale_id] = $this->getMediaTag($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
						}
					}
				}
				break;
			default:
				// is intrinsic field in primary table
				foreach($pa_value_list as $vn_locale_id => $va_values) {
				
					if ($pa_options['useLocaleCodes']) {
						if (!$vn_locale_id || !($vm_locale_id = SearchResult::$opo_locales->localeIDToCode($vn_locale_id))) { $vm_locale_id = __CA_DEFAULT_LOCALE__; }; 
					} else {
						if (!($vm_locale_id = $vn_locale_id)) { $vm_locale_id = SearchResult::$opo_locales->localeCodeToID(__CA_DEFAULT_LOCALE__); }; 
					}
					
					foreach($va_values as $vn_i => $va_value) {
						$va_ids[] = $vn_id = $va_value[$vs_pk];
							
						$vs_prop = $va_value[$va_path_components['field_name']];
					
						if ($pa_options['unserialize']) {
							$vs_prop = caUnserializeForDatabase($vs_prop);
							
							if(is_array($vs_prop) && $va_path_components['subfield_name']) {
								$vs_prop = isset($vs_prop[$va_path_components['subfield_name']]) ? $vs_prop[$va_path_components['subfield_name']] : null;
							}
						}
					
						if ($pa_options['convertCodesToDisplayText']) {
							$vs_prop = $this->_convertCodeToDisplayText($vs_prop, $va_path_components, $pt_instance, $pa_options);
						} elseif($pa_options['convertCodesToIdno']) {
							$vs_prop = $this->_convertCodeToIdno($vs_prop, $va_path_components, $pt_instance, $pa_options);
						}
						
						$va_return_values[$vn_id][$vm_locale_id] = $vs_prop;
					}
				}
				break;
		}	
		
		if (!$pa_options['returnAllLocales']) { $va_return_values = caExtractValuesByUserLocale($va_return_values); } 	
		if ($pa_options['returnWithStructure']) { 
			return is_array($va_return_values) ? $va_return_values : array(); 
		}
		
		//
		// Flatten array for return as string or simple array value
		// 
		$va_flattened_values = $this->_flattenArray($va_return_values, $pa_options);
		
		if ($pa_options['returnAsArray']) {
			return $va_flattened_values;
		} else {
			return (sizeof($va_flattened_values) > 0) ? join($pa_options['delimiter'], $va_flattened_values) : null;
		}
	}
	# ------------------------------------------------------------------
	/** 
	 * Flatten value of returned values subject to get() options.
	 *
	 * @param array $pa_array
	 * @param array $pa_options Options include:
	 *		toUpper = Force all values to upper case. [Default is false]
	 *		toLower = Force all values to lower case. [Default is false]
	 *		makeFirstUpper = Force first character of all values to upper case. [Default is false]
	 *		trim = Trim white space from beginning and end of string. [Default is false]
	 *		start = Return all values trimmed to start at the specified character. [Default is null]
	 *		length = Return all values truncated to a maximum length. [Default is null]
	 *		truncate = Return all values from the beginning truncated to a maximum length; equivalent of passing start=0 and length. [Default is null]
	 *		ellipsis = Add ellipsis ("...") to truncated values. Values will be set to the truncated length including the ellipsis. Eg. a value truncated to 12 characters will include 9 characters of text and 3 characters of ellipsis. [Default is false]
	 *
	 * @return array
	 */
	private function _flattenArray($pa_array, $pa_options=null) {
		$va_flattened_values = array();
		if ($pa_options['returnAllLocales']) {
			$pa_array = caExtractValuesByUserLocale($pa_array);
			foreach($pa_array as $va_by_attr) {
				if (!is_array($va_by_attr)) { $va_flattened_values[] = $va_by_attr; continue;  }
				foreach($va_by_attr as $vs_val) {
					if (is_array($vs_val) && sizeof($vs_val) == 1) { 
						$vs_val = array_shift($vs_val); 
					} elseif(is_array($vs_val)) {
						$va_flattened_values[] = $vs_val;
						continue;
					}
				
					if($pa_options['toUpper'] || $pa_options['toUpper']) {
						$vs_val = mb_strtoupper($vs_val);
					}
					if($pa_options['toLower'] || $pa_options['tolower']) {
						$vs_val = mb_strtolower($vs_val);
					}
					if($pa_options['makeFirstUpper'] || $pa_options['makefirstupper']) {
						$vs_val = ucfirst($vs_val);
					}
					if ($pa_options['truncate'] && ($pa_options['truncate'] > 0)) { 
						$pa_options['start'] = 0;
						$pa_options['length'] = (int)$pa_options['truncate'];
					}
					$vn_start = (strlen($pa_options['start']) && is_numeric($pa_options['start'])) ? (int)$pa_options['start'] : 0;
					$vn_length = (strlen($pa_options['length']) && ($pa_options['length'] > 0)) ? (int)$pa_options['length'] : null;
					
					$vb_needs_ellipsis = false;
					if(($vn_start > 0) || (!is_null($vn_length))) {
						if ($pa_options['ellipsis'] && (strlen($vs_val) > ($vn_start + $vn_length))) {
							$vb_needs_ellipsis = true; $vn_length -= 3;
						}
						$vs_val = mb_substr($vs_val, $vn_start, $vn_length);
					}
					if($pa_options['trim']) {
						$vs_val = trim($vs_val);
					}
					
					$vs_val .= ($vb_needs_ellipsis ? '...' : '');
					
					
					$va_flattened_values[] = $vs_val;
				}
			}	
		} else {
			foreach($pa_array as $va_vals) {
				if(!is_array($va_vals)) { $va_flattened_values[] = $va_vals; continue; }
				foreach($va_vals as $vs_val) {
					if (is_array($vs_val) && sizeof($vs_val) == 1) { 
						$vs_val = array_shift($vs_val); 
					} elseif(is_array($vs_val)) {
						$va_flattened_values[] = $vs_val;
						continue;
					}
					
					if($pa_options['toUpper'] || $pa_options['toupper']) {
						$vs_val = mb_strtoupper($vs_val);
					}
					if($pa_options['toLower'] || $pa_options['tolower']) {
						$vs_val = mb_strtolower($vs_val);
					}
					if($pa_options['makeFirstUpper'] || $pa_options['makefirstupper']) {
						$vs_val = ucfirst($vs_val);
					}
					if ($pa_options['truncate'] && ($pa_options['truncate'] > 0)) { 
						$pa_options['start'] = 0;
						$pa_options['length'] = (int)$pa_options['truncate'];
					}
					$vn_start = (strlen($pa_options['start']) && is_numeric($pa_options['start'])) ? (int)$pa_options['start'] : 0;
					$vn_length = (strlen($pa_options['length']) && ($pa_options['length'] > 0)) ? (int)$pa_options['length'] : null;
					
					$vb_needs_ellipsis = false;
					if(($vn_start > 0) || (!is_null($vn_length))) {
						if ($pa_options['ellipsis'] && (strlen($vs_val) > ($vn_start + $vn_length))) {
							$vb_needs_ellipsis = true; $vn_length -= 3;
						}
						$vs_val = mb_substr($vs_val, $vn_start, $vn_length);
					} 
					if($pa_options['trim']) {
						$vs_val = trim($vs_val);
					}
					
					$vs_val .= ($vb_needs_ellipsis ? '...' : '');
					
					$va_flattened_values[] = $vs_val;
				}
			}	
		}
		return $va_flattened_values;
	}
	# ------------------------------------------------------------------
	/**
	 * Run the given display template for the current row in the result set
	 * @param string $ps_template The display template, e.g. "^ca_objects.preferred_labels"
	 * @param null|array $pa_options Array of options, @see caProcessTemplateForIDs
	 * @return mixed
	 */
	public function getWithTemplate($ps_template, $pa_options=null) {
		unset($pa_options['request']);
		//if($this->opb_disable_get_with_template_prefetch) {
			if(!is_array($pa_options)) { $pa_options = array(); }
			return caProcessTemplateForIDs($ps_template, $this->ops_table_name, array($this->get($this->ops_table_name.".".$this->ops_subject_pk)), array_merge($pa_options, ['dontPrefetchRelated' => true]));
		//}

		// the assumption is that if you run getWithTemplate for the current row, you'll probably run it for the next bunch of rows too
		// since running caProcessTemplateForIDs for every single row is slow, we prefetch a set number of rows here
		$vs_cache_base_key = $this->getCacheKeyForGetWithTemplate($ps_template, $pa_options);
		if(!isset($this->opa_template_prefetch_cache[$vs_cache_base_key][$vn_cur_row = $this->opo_engine_result->currentRow()])) {
			$this->prefetchForGetWithTemplate($ps_template, $pa_options);
		}

		return $this->opa_template_prefetch_cache[$vs_cache_base_key][$vn_cur_row];
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function prefetchForGetWithTemplate($ps_template, $pa_options) {
		$va_ids = $this->getRowIDsToPrefetch($this->opo_engine_result->currentRow(), 500);
		$vs_cache_base_key = $this->getCacheKeyForGetWithTemplate($ps_template, $pa_options);
		$pa_options['returnAsArray'] = true; // careful, this would change the cache key ... which is why we generate it before
		$pa_options['includeBlankValuesInTopLevelForPrefetch'] = true; // if we don't set this blank values are omitted and array offsets following a blank value will be incorrect. A recipe for a bad day.
		$va_vals = caProcessTemplateForIDs($ps_template, $this->ops_table_name, $va_ids, $pa_options);

		// if we're at the first hit, we don't need to offset the cache keys, so we can use $va_vals as-is
		if(($vn_cur_row = $this->opo_engine_result->currentRow()) == 0) {
			$this->opa_template_prefetch_cache[$vs_cache_base_key] = array_values($va_vals);
		} else {
			// this is kind of slow but we hope that users usually pull when the ptr is still at the first result
			// I tried messing around with array_walk instead of this loop but that doesn't gain us much, and this is way easier to read
			$vn_i = 0;
			foreach($va_vals as $vs_val) {
				$this->opa_template_prefetch_cache[$vs_cache_base_key][$vn_cur_row + $vn_i] = $vs_val;
				$vn_i++;
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function clearGetWithTemplatePrefetch() {
		$this->opa_template_prefetch_cache = array();
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function getCacheKeyForGetWithTemplate($ps_template, $pa_options) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		foreach($pa_options as $vs_k => $vs_v) {
			if (in_array($vs_k, array('useSingular', 'maximumLength', 'delimiter', 'purify', 'restrict_to_types', 'restrict_to_relationship_types',  'restrictToTypes', 'restrictToRelationshipTypes', 'returnAsArray',  'excludeTypes', 'excludeRelationshipTypes'))) { continue; }
			unset($pa_options[$vs_k]);
		}
		return md5($this->ops_table_name.'/'.$ps_template.'/'.serialize($pa_options));
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getWithTemplateForResults($ps_template, $pa_options=null) {	
		$pn_start = caGetOption('start', $pa_options, 0);
		$this->seek($pn_start);

		return caProcessTemplateForIDs($ps_template, $this->ops_table_name, $this->getRowIDsToPrefetch($pn_start, $this->numHits()), array_merge($pa_options, array('returnAsArray' => true)));
	}
	# ------------------------------------------------------------------
	/**
	 * Move current row in result set 
	 *
	 * @param int $pn_index The row to move to. Rows are numbers from zero.
	 * @return bool True on success, false on failure
	 */
	public function seek($pn_index) {
		return $this->opo_engine_result->seek($pn_index);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _convertCodeToIdno($ps_prop, $pa_path_components, $pt_instance, $pa_options=null) {
		$vs_prop = $ps_prop;
		
		$vs_field_name = $pa_path_components['subfield_name'] ? $pa_path_components['subfield_name'] : $pa_path_components['field_name'];
		
		$vs_table_name = $pa_path_components['table_name'];
		if (method_exists($pt_instance, 'setLabelTypeList')) {
			$pt_instance->setLabelTypeList($this->opo_subject_instance->getAppConfig()->get(($pa_path_components['field_name'] == 'nonpreferred_labels') ? "{$vs_table_name}_nonpreferred_label_type_list" : "{$vs_table_name}_preferred_label_type_list"));
		}
		if (isset($pa_options['convertCodesToIdno']) && $pa_options['convertCodesToIdno'] && ($vs_list_code = $pt_instance->getFieldInfo($vs_field_name,"LIST_CODE"))) {
			$vs_prop = caGetListItemIdno($vs_prop);
		} else {
			if (isset($pa_options['convertCodesToIdno']) && $pa_options['convertCodesToIdno'] && ($vs_list_code = $pt_instance->getFieldInfo($vs_field_name,"LIST"))) {
				$vs_prop = caGetListItemIdno(caGetListItemIDForValue($vs_list_code, $vs_prop));
			}
		}
		return $vs_prop;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _convertCodeToDisplayText($ps_prop, $pa_path_components, $pt_instance, $pa_options=null) {
		$vs_prop = $ps_prop;
		
		$vs_field_name = $pa_path_components['subfield_name'] ? $pa_path_components['subfield_name'] : $pa_path_components['field_name'];
		$vb_convert_codes_to_display_text = ((isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText']) || ($pa_options['output'] == 'text'));
		
		$vs_table_name = $pa_path_components['table_name'];
		if (method_exists($pt_instance, 'setLabelTypeList') && ($vs_label_list_name = $this->opo_subject_instance->getAppConfig()->get(($pa_path_components['field_name'] == 'nonpreferred_labels') ? "{$vs_table_name}_nonpreferred_label_type_list" : "{$vs_table_name}_preferred_label_type_list"))) {
			$pt_instance->setLabelTypeList($vs_label_list_name);
		}
		
		if ($vb_convert_codes_to_display_text && ($vs_list_code = $pt_instance->getFieldInfo($vs_field_name,"LIST_CODE"))) {
			$vs_prop = SearchResult::$opt_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
		} else {
			if ($vb_convert_codes_to_display_text && ($vs_list_code = $pt_instance->getFieldInfo($vs_field_name,"LIST"))) {
				$vs_prop = SearchResult::$opt_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop);
			} else {
				if ($vb_convert_codes_to_display_text && ($vs_field_name === 'locale_id') && ((int)$vs_prop > 0)) {
					$t_locale = new ca_locales($vs_prop);
					$vs_prop = $t_locale->getName();
				} else {
					if ($vb_convert_codes_to_display_text && (is_array($va_list = $pt_instance->getFieldInfo($vs_field_name,"BOUNDS_CHOICE_LIST")))) {
						foreach($va_list as $vs_option => $vs_value) {
							if ($vs_value == $vs_prop) {
								$vs_prop = $vs_option;
								break;
							}
						}
					}
				}
			}
		}
		return $vs_prop;
	}
	# ------------------------------------------------
	/**
	 * Determines if there is any data in the result for the specified data field(s)
	 * Typically used to determine if a result set can be used for visualization (Eg. does a result set have mappable data?)
	 *
	 * @param mixed $pa_fields Field or list of fields to check
	 * @param array $pa_options Options include:
	 *		limit = number of rows to check before giving up; should be capped at a reasonable value for large empty result sets to avoid timeouts [Default=10000]
	 *
	 * @return bool True result set includes data for any of the fields in $pa_fields
	 */
	public function hasData($pa_fields, $pa_options=null) {
		if(!$pa_fields) { return null; }
		
		$vn_cur_pos = $this->currentIndex();
		if ($vn_cur_pos < 0) { $vn_cur_pos = 0; }
		$this->seek(0);
		
		$o_dm = Datamodel::load();
		
		if(!is_array($pa_fields) && ($pa_fields)) { $pa_fields = array($pa_fields); }
		
		//
		// Make sure fields actually exist
		//
		foreach($pa_fields as $vn_i => $vs_field) {
			$va_tmp = explode('.', $vs_field);
			if (!($t_instance = $o_dm->getInstanceByTableName($va_tmp[0], true))) { unset($pa_fields[$vn_i]); continue; } 
			if (!$t_instance->hasField($va_tmp[1]) && (!$t_instance->hasElement($va_tmp[1], null, false, array('dontCache' => false)))) { unset($pa_fields[$vn_i]); }
		}
		
		$vn_c = 0;
		if (($vn_limit = caGetOption('limit', $pa_options, 10000)) < 1) { $vn_limit = 10000; }
		while($this->nextHit() && ($vn_c < $vn_limit)) {
			foreach($pa_fields as $vn_i => $vs_field) {
				if (trim($this->get($vs_field))) {
					$this->seek($vn_cur_pos);
					return true;
				}
			}
			$vn_c++;
		}
		$this->seek($vn_cur_pos);
		return false;
	}
	# ------------------------------------------------------------------
	#  Field value accessors (allow you to get specialized values out of encoded fields such as uploaded media and files, dates/date ranges, timecode, etc.) 
	# ------------------------------------------------------------------
	/**
	 * Fetches an array of information about the specified bundle. Information includes the table name, fields name and, for intrinsics a model instance.
	 *
	 * @param string $ps_field The bundle to get fetch information for
	 * @return mixed An array of bundle information. False if information could not be fetched.
	 */
	function getFieldInfo($ps_field) {
		$va_tmp = explode(".", $ps_field);
		switch(sizeof($va_tmp)) {
			case 1:		// query field name (no table specified, in other words)
				return array("table" => null, "field" => $ps_field, "instance" => null);
				break;
			case 2:		// table.field format fieldname
				$o_dm = Datamodel::load();
				$o_instance = $o_dm->getInstanceByTableName($va_tmp[0], true);
				if ($o_instance) {
					return array("table" => $va_tmp[0], "field" => $va_tmp[1], "instance" => $o_instance);
				}
				return array("table" => null, "field" => $ps_field, "instance" => null);
				break;
			default:	// invalid field name
				return false;
				break;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaInfo($ps_field, $ps_version=null, $ps_key=null, $pa_options=null) {
		$vn_index = (isset($pa_options['index']) && ((int)$pa_options['index'] > 0)) ? (int)$pa_options['index'] : 0;
		$va_media_info = $this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaInfo(array_shift($va_media_info), $ps_version, $ps_key, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaPath($ps_field, $ps_version, $pa_options=null) {
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaPath(array_shift($this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true))), $ps_version, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media paths attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaPaths($ps_field, $ps_version, $pa_options=null) {
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));
		
		$va_media_paths = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
				$va_media_paths[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaPath($vm_media, $ps_version, $pa_options);
			}
		}
		return $va_media_paths;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaUrl($ps_field, $ps_version, $pa_options=null) {
		$va_media_infos = $this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaUrl(array_shift($va_media_infos), $ps_version, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media urls attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaUrls($ps_field, $ps_version, $pa_options=null) {
		$va_media_infos = $this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));
		
		$va_media_urls = array();
		if (is_array($va_media_infos) && sizeof($va_media_infos)) {
			foreach($va_media_infos as $vm_media) {
				$va_media_urls[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaUrl($vm_media, $ps_version, $pa_options);
			}
		}
		return $va_media_urls;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaTag($ps_field, $ps_version, $pa_options=null) {
		$va_media_infos = self::get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaTag(array_shift($va_media_infos), $ps_version, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media tags attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaTags($ps_field, $ps_version, $pa_options=null) {
		
		$va_media_infos = self::get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));
		$va_media_tags = array();
		if (is_array($va_media_infos) && sizeof($va_media_infos)) {
			foreach($va_media_infos as $vm_media) {
				$va_media_tags[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaTag($vm_media, $ps_version, $pa_options);
			}
		}
		return $va_media_tags;
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media info arrays attached to this search result. An object can have more than more representation.
	 *
	 */
	function getMediaInfos($ps_field) {
		
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));
		
		$va_media_infos = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
				$va_media_infos[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaInfo($vm_media);
			}
		}
		return $va_media_infos;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaVersions($ps_field) {
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaVersions(array_shift($this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function hasMediaVersion($ps_field, $ps_version) {
		if (!is_array($va_tmp = $this->getMediaVersions($ps_field))) {
			return false;
		}
		return in_array($ps_version, $va_tmp);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function hasMedia($ps_field) {  
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->hasMedia(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaScale($ps_field) {  
		$va_media_infos = $this->get($ps_field, array("unserialize" => true, 'returnWithStructure' => true));

		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaScale(array_shift($va_media_infos), $pa_options);	
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function mediaIsMirrored($ps_field, $ps_version) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->mediaIsMirrored(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))), $ps_version);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaMirrorStatus($ps_field, $ps_version, $ps_mirror=null) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaMirrorStatus(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))), $ps_version, $ps_mirror);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileInfo($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileInfo(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFilePath($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFilePath(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileUrl($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileUrl(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function hasFile($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->hasFile(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileConversions($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversions(array($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileConversionPath($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionPath(array($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))), $ps_mimetype);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileConversionUrl($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionUrl(array_shift($this->get($va_field["field"], array("unserialize" => true, 'returnWithStructure' => true))), $ps_mimetype);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getDate($ps_field, $pa_options=null) {
		
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if (!in_array($vn_field_type = $va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE"), array(FT_DATE, FT_TIME, FT_DATETIME, FT_TIMESTAMP, FT_HISTORIC_DATETIME, FT_HISTORIC_DATERANGE, FT_DATERANGE))) {
				return false;
			}
			
			$vn_val = $this->get($va_field["field"], array("binary" => true));
			$GLOBALS["_DbResult_time_expression_parser"]->init();	// get rid of any linger date-i-ness
			switch($vn_field_type) {
				case (FT_DATE):
				case (FT_TIME):
				case (FT_DATETIME):
				case (FT_TIMESTAMP):
				case (FT_HISTORIC_DATETIME):	
					if ($pa_options["getRawDate"]) {
						return $vn_val;
					} else {
						$GLOBALS["_DbResult_time_expression_parser"]->init();
						if ($vn_field_type == FT_HISTORIC_DATETIME) {
							$GLOBALS["_DbResult_time_expression_parser"]->setHistoricTimestamps($vn_val, $vn_val);
						} else {
							$GLOBALS["_DbResult_time_expression_parser"]->setUnixTimestamps($vn_val, $vn_val);
						}
						return $GLOBALS["_DbResult_time_expression_parser"]->getText();
					}
					break;
				case (FT_DATERANGE):
				case (FT_HISTORIC_DATERANGE):	
					$vs_start_field_name = 	$va_field["instance"]->getFieldInfo($va_field["field"],"START");
					$vs_end_field_name = 	$va_field["instance"]->getFieldInfo($va_field["field"],"END");
					
					if (!$pa_options["getRawDate"]) {
						$GLOBALS["_DbResult_time_expression_parser"]->init();
						if ($vn_field_type == FT_HISTORIC_DATERANGE) {
							$GLOBALS["_DbResult_time_expression_parser"]->setHistoricTimestamps($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
						} else {
							$GLOBALS["_DbResult_time_expression_parser"]->setUnixTimestamps($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
						}
						return $GLOBALS["_DbResult_time_expression_parser"]->getText();
					} else {
						return array($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
					}
					break;
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getTimecode($ps_field, $ps_format=null) {
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if ($va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE") != FT_TIMECODE) {
				return false;
			}
		}
		
		if (is_numeric($vn_tc = $this->get($va_field["field"]))) {
			$GLOBALS["_DbResult_timecodeparser"]->setParsedValueInSeconds($vn_tc);
			return $GLOBALS["_DbResult_timecodeparser"]->getText($ps_format);
		} else {
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getChoiceListValue($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		if(is_object($va_field["instance"])) {
			if (is_array($va_field["instance"]->getFieldInfo($va_field["field"], "BOUNDS_CHOICE_LIST"))) {
				return $va_field["instance"]->getChoiceListValue($va_field["field"], $this->get($va_field["field"]));
			} else {
				// no choice list; return actual field value
				return $this->get($va_field["field"]);
			}
		} else {
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function getVars($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if ($va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE") != FT_VARS) {
				return false;
			}
		}
		return $this->get($va_field["field"], array("unserialize" => true));
	}
	# ------------------------------------------------------------------
	# Options
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function setOption($ps_option, $pm_value) {
		if ($this->isValidOption($ps_option)) {
			switch($ps_option) {
				case 'prefetchAttributes':
					$this->opa_options[$ps_option] = array_values(ca_metadata_elements::elementCodesToIDs($pm_value));
					break;
				default:
					$this->opa_options[$ps_option] = $pm_value;
					break;
			}

			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function getOption($ps_option) {
		return $this->opa_options[$ps_option];
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function getAvailableOptions() {
		return array_keys($this->opa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function isValidOption($ps_option) {
		return in_array($ps_option, $this->getAvailableOptions());
	}
	# ------------------------------------------------------------------
	# Utilities
	
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getResultTableName() {
		return $this->ops_table_name;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getResultTableInstance() {
		return SearchResult::$opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
	}
	# ------------------------------------------------------------------
	/**
	  * TODO: NEW!
	  */
	private function parseFieldPathComponents($ps_path) {
		if (isset(SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_path])) { return SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_path]; }
		$va_tmp = explode('.', $ps_path);
		
		$vb_is_related = false;
		if ($va_tmp[1] == 'related') {
			array_splice($va_tmp, 1, 1);
			$vb_is_related = true;
		} else {
			if ($va_tmp[0] !== $this->ops_table_name) {
				$vb_is_related = true;
			}
		}
		
		$vb_is_count = false;
		if ($va_tmp[sizeof($va_tmp)-1] == '_count') {
			array_pop($va_tmp);
			$vb_is_count = true;
		}
		
		$vs_hierarchical_modifier = null;
		if ($va_tmp[1] == 'hierarchy') {
			array_splice($va_tmp, 1, 1);
			$vs_hierarchical_modifier = 'hierarchy';
		} elseif ($va_tmp[1] == 'parent') {
			array_splice($va_tmp, 1, 1);
			$vs_hierarchical_modifier = 'parent';
		} elseif ($va_tmp[1] == 'children') {
			array_splice($va_tmp, 1, 1);
			$vs_hierarchical_modifier = 'children';
		} elseif ($va_tmp[1] == 'siblings') {
			array_splice($va_tmp, 1, 1);
			$vs_hierarchical_modifier = 'siblings';
		}
		
		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		
				if ($t_instance = SearchResult::$opo_datamodel->getInstanceByTableName($va_tmp[0], true)) {	// table name
					$vs_table_name = $va_tmp[0];
					$vs_field_name = null;
					$vs_subfield_name = null;
				} else {																			// field name in searched table
					$vs_table_name = $this->ops_table_name;
					$vs_field_name = $va_tmp[0];
					$vs_subfield_name = null;
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
				$vs_table_name = $va_tmp[0];
				$vs_field_name = $va_tmp[1];
				$vs_subfield_name = null;
				break;
			# -------------------------------------
			default:
			case 3:		// table_name.field_name.sub_element
				$vs_table_name = $va_tmp[0];
				$vs_field_name = $va_tmp[1];
				$vs_subfield_name = $va_tmp[2];
				break;
			# -------------------------------------
		}
		
		// rewrite label tables to use preferred_labels syntax
		if (($t_instance = SearchResult::$opo_datamodel->getInstanceByTableName($vs_table_name, true)) && (is_a($t_instance, "BaseLabel"))) {
			$vs_table_name = $t_instance->getSubjectTableName();
			$vs_subfield_name = $vs_field_name;
			$vs_field_name = "preferred_labels";
			$va_tmp = array($vs_table_name, $vs_field_name, $vs_subfield_name);
			$vb_is_related = ($vs_table_name !== $this->ops_table_name);
		}
	
		return SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_path] = array(
			'table_name' 		=> $vs_table_name,
			'field_name' 		=> $vs_field_name,
			'subfield_name' 	=> $vs_subfield_name,
			'num_components'	=> sizeof($va_tmp),
			'components'		=> $va_tmp,
			'related'			=> $vb_is_related,
			'is_count'			=> $vb_is_count,
			'hierarchical_modifier' => $vs_hierarchical_modifier
		);
	}
	# ------------------------------------------------------------------
	/**
	 * Scans the result set and gets all field values of the field list given, including their count.
	 * This can be useful for presentation of results partitioned by type
	 * 
	 * The returned array looks like this:
	 * array(
	 * 	field1 => array(
	 * 				"field_value1" => count_of_field_value1,
	 * 				"field_value2" => count_of_field_value2,
	 * 				...)
	 *  field2 => ...
	 *  ...
	 * )
	 * 
	 * If it is not possible to fetch values for one of the given fields, it is simply ignored.
	 *
	 * @param array $pa_field_list List of fields to fetch counts for. Fields should be fully qualified <table>.<field> specifications (eg. ca_objects.type_id)
	 * @param bool $vb_sort If true, counts for each field value will be sorted by value; default is false
	 */
	public function getResultCountForFieldValues($pa_field_list, $vb_sort=false){
		$vs_key = md5(print_r($pa_field_list, true).($vb_sort ? 'sort' : 'nosort'));
		if (isset( $this->opa_cached_result_counts[$vs_key])) { return  $this->opa_cached_result_counts[$vs_key]; }
		if (($vn_cur_row_index = $this->opo_engine_result->currentRow()) < 0) {
			$vn_cur_row_index = 0;
		}
		self::seek(0);
		$va_result = array();
		
		// loop through result and try to fetch values of the given field list
		while(self::nextHit()) {
			foreach($pa_field_list as $vs_field){
				// try to fetch fields as defined, don't care about non-existing fields
				if($vm_field_values=$this->get($vs_field,array('returnAsArray' => true))) {
					if(is_array($vm_field_values) && sizeof($vm_field_values)>0) {
						// rewrite $vs_field to represent the SearchEngine::addFilter() format;
						// this makes life a lot easier
						$va_matches = array();
						if(preg_match("/([\w_\-]+)\.(md_[0-9]+)\.([\w_\-]+)$/",$vs_field,$va_matches)) {
							$vs_field = $va_matches[1].'.'.$va_matches[2];
						}
						foreach($vm_field_values as $vs_field_value) {
							$va_result[$vs_field][$vs_field_value]++;
						}						
					} // do nothing on other cases (e.g. error or empty fields)
				}
			}
		}
		
		// restore current position
		self::seek($vn_cur_row_index);
		
		// user wants the arrays to be sorted
		if($vb_sort) {
			foreach ($va_result as &$va_field_contents){
				ksort($va_field_contents);
			}
		}
		return $this->opa_cached_result_counts[$vs_key] = $va_result;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getIdentifierForUrl() {
		if ($this->opb_use_identifiers_in_urls && $this->ops_subject_idno) {
			return $this->get($this->ops_subject_idno);
		} else {
			return $this->get($this->ops_subject_pk);
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	static public function _isHierarchyModifier($pm_modifier) {
		$va_hierarchy_modifiers = ['hierarchy', 'parent', 'children', 'siblings'];
	
		if (is_array($pm_modifier)) {
			return (sizeof(array_intersect($va_hierarchy_modifiers, $pm_modifier)) > 0);
		} else {
			return in_array($pm_modifier, $va_hierarchy_modifiers);
		}
	}
	# ------------------------------------------------------------------
}
