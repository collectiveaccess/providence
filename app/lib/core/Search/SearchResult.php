<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchResult.php : implements interface to results from a search
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2015 Whirl-i-Gig
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


# ----------------------------------------------------------------------
class SearchResult extends BaseObject {
	
	private $opo_datamodel;
	private $opo_search_config;
	private $opo_db;
	private $opn_table_num;
	protected $ops_table_name;
	private $ops_table_pk;
	// ----
	
	private $opa_options;
	
	private $opo_engine_result;
	protected $opa_tables;
	
	protected $opo_subject_instance;

	private $opa_row_ids_to_prefetch_cache;
	
	private $opo_tep; // time expression parser
	
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
	
	# ------------------------------------------------------------------
	public function __construct($po_engine_result=null, $pa_tables=null) {
		$this->opo_db = new Db();
		$this->opo_datamodel = Datamodel::load();
		$this->opo_subject_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
		
		$this->ops_subject_pk = $this->opo_subject_instance->primaryKey();
		$this->ops_subject_idno = $this->opo_subject_instance->getProperty('ID_NUMBERING_ID_FIELD');
		$this->opb_use_identifiers_in_urls = (bool)$this->opo_subject_instance->getAppConfig()->get('use_identifiers_in_urls');
		$this->opa_row_ids_to_prefetch_cache = array();
		
		if ($po_engine_result) {
			$this->init($po_engine_result, $pa_tables);
		}
		
		if (!$GLOBALS["_DbResult_time_expression_parser"]) { $GLOBALS["_DbResult_time_expression_parser"] = new TimeExpressionParser(); }
		if (!$GLOBALS["_DbResult_timecodeparser"]) { $GLOBALS["_DbResult_timecodeparser"] = new TimecodeParser(); }
		
		if (!$GLOBALS["_DbResult_mediainfocoder"]) { $GLOBALS["_DbResult_mediainfocoder"] = MediaInfoCoder::load(); }
		if (!$GLOBALS["_DbResult_fileinfocoder"]) { $GLOBALS["_DbResult_fileinfocoder"] = FileInfoCoder::load(); }
		
		
		$this->opt_list = $this->opo_datamodel->getInstanceByTableName('ca_lists', true);
		
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
				'prefetch' => 50
		);
		
		
		$this->opo_tep = $GLOBALS["_DbResult_time_expression_parser"];
	}
	# ------------------------------------------------------------------
	public function cloneInit() {
		$this->opo_db = new Db();
		$this->opo_datamodel = Datamodel::load();
		$this->opo_subject_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
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
		return $this->opo_datamodel->getTablePrimaryKeyName($this->opn_table_num);
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
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = $this->opo_datamodel->getInstanceByTableName($ps_tablename, true);
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
		// get row_ids to fetch
		if (isset($pa_options['row_ids']) && is_array($pa_options['row_ids'])) {
			$va_row_ids = $pa_options['row_ids'];
		} else {
			$va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows);
		}
		if (sizeof($va_row_ids) == 0) { return false; }

		if (!($t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = $this->opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}
		if (!$t_rel_instance->isHierarchical()) { return false; }

		if ($ps_tablename !== $this->ops_table_name) {
			$va_row_ids = $this->_getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, SearchResult::$opa_hierarchy_parent_prefetch_cache_index, $t_rel_instance, $va_row_ids, $pa_options);
		}
		$vs_pk = $t_rel_instance->primaryKey();
		$vs_parent_id_fld = $t_rel_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_hier_id_fld = $t_rel_instance->getProperty('HIERARCHY_ID_FLD');
		
		$vs_sql = "
			SELECT t.{$vs_pk}, t.{$vs_parent_id_fld} ".($vs_hier_id_fld ? ", t.{$vs_hier_id_fld}" : '')."
			FROM {$ps_tablename} t
			WHERE
				t.{$vs_pk} IN (?)
		";
		
		$va_row_ids_in_current_level = $va_row_ids;
		
		$va_row_id_map = null;
		$vn_level = 0;
		
		while(true) {
			if (!sizeof($va_row_ids_in_current_level)) { break; }
			$qr_rel = $this->opo_subject_instance->getDb()->query($vs_sql, array($va_row_ids_in_current_level));
			if (!$qr_rel || ($qr_rel->numRows() == 0)) { break;}
			
			while($qr_rel->nextRow()) {
				$va_row = $qr_rel->getRow();
				if (!$va_row[$vs_parent_id_fld]) { continue; }
				
				if ($vn_level == 0) {
					$va_row_id_map[$va_row[$vs_parent_id_fld]] = $va_row[$vs_pk];
					SearchResult::$opa_hierarchy_parent_prefetch_cache[$ps_tablename][$va_row[$vs_pk]] = array();
				} else {
					$va_row_id_map[$va_row[$vs_parent_id_fld]] = $va_row_id_map[$va_row[$vs_pk]];
				}
				if (!$va_row_id_map[$va_row[$vs_parent_id_fld]]) { continue; }
				
				SearchResult::$opa_hierarchy_parent_prefetch_cache[$ps_tablename][$va_row_id_map[$va_row[$vs_parent_id_fld]]][] = $va_row[$vs_parent_id_fld];
			}
			
			$va_row_ids_in_current_level = $qr_rel->getAllFieldValues($vs_parent_id_fld);
			
			$vn_level++;
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
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = $this->opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}
		if (!$t_rel_instance->isHierarchical()) { return false; }

		if ($ps_tablename != $this->ops_table_name) {
			$va_row_ids = $this->_getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, SearchResult::$opa_hierarchy_children_prefetch_cache_index, $t_rel_instance, $va_row_ids, $pa_options);
		}
		
		$vs_pk = $t_rel_instance->primaryKey();
		$vs_parent_id_fld = $t_rel_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_sql = "
			SELECT {$vs_pk}, {$vs_parent_id_fld}
			FROM {$ps_tablename}
			WHERE
				 {$vs_parent_id_fld} IN (?)
		";
		
		$va_row_ids_in_current_level = $va_row_ids;
		
		$va_row_id_map = null;
		$vn_level = 0;
		
		while(true) {
			$qr_rel = $this->opo_subject_instance->getDb()->query($vs_sql, array($va_row_ids_in_current_level));
			
			if (!$qr_rel || ($qr_rel->numRows() == 0)) { break;}
			
			$va_row_ids_in_current_level = array(); 
			while($qr_rel->nextRow()) {
				$va_row = $qr_rel->getRow();
				
				if ($vn_level == 0) {
					$va_row_id_map[$va_row[$vs_pk]] = $va_row[$vs_parent_id_fld];
					//SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$va_row[$vs_pk]] = array();
				} else {
					$va_row_id_map[$va_row[$vs_pk]] = $va_row_id_map[$va_row[$vs_parent_id_fld]];
				}
				if (!$va_row_id_map[$va_row[$vs_pk]]) { continue; }
				
				SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$va_row[$vs_parent_id_fld]][] = 
				SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$va_row_id_map[$va_row[$vs_parent_id_fld]]][] =
					$va_row_ids_in_current_level[] = $va_row[$vs_pk];
			}
			$va_row_ids += $va_row_ids_in_current_level;
			$vn_level++;
			
			if ((!isset($pa_options['allDescendants']) || !$pa_options['allDescendants']) && ($vn_level > 0)) {
				break;
			}
		}
		
		foreach($va_row_ids as $vn_row_id) {
			if (!isset(SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$vn_row_id])) { 
				SearchResult::$opa_hierarchy_children_prefetch_cache[$ps_tablename][$vn_row_id] = array();
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
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = $this->opo_datamodel->getInstanceByTableName($ps_tablename, true);
		}
		if (!$t_rel_instance->isHierarchical()) { return false; }

		if ($ps_tablename != $this->ops_table_name) {
			$va_row_ids = $this->_getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, SearchResult::$opa_hierarchy_siblings_prefetch_cache_index, $t_rel_instance, $va_row_ids, $pa_options);
		}
		
		$vs_pk = $t_rel_instance->primaryKey();
		$vs_parent_id_fld = $t_rel_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_sql = "
			SELECT t.{$vs_pk}, t.{$vs_parent_id_fld}, p.{$vs_pk} sibling_id
			FROM {$ps_tablename} t
			INNER JOIN {$ps_tablename} AS p ON t.{$vs_parent_id_fld} = p.{$vs_parent_id_fld}
			WHERE
				 t.{$vs_pk} IN (?)
		";
		
		
		$qr_rel = $this->opo_subject_instance->getDb()->query($vs_sql, array($va_row_ids));
		while($qr_rel->nextRow()) {
			$va_row = $qr_rel->getRow();
			
			SearchResult::$opa_hierarchy_siblings_prefetch_cache[$ps_tablename][$va_row[$vs_pk]][] = $va_row['sibling_id'];
		}
		
		foreach($va_row_ids as $vn_row_id) {
			if (!isset(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$ps_tablename][$vn_row_id])) { 
				SearchResult::$opa_hierarchy_siblings_prefetch_cache[$ps_tablename][$vn_row_id] = array();
			}
		}
		
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedIDsForPrefetch($ps_tablename, $pn_start, $pn_num_rows, &$pa_cache, $t_rel_instance, $va_row_ids, $pa_options) {
		$this->prefetchRelated($ps_tablename, $pn_start, $pn_num_rows, $pa_options);
						
		$va_base_row_ids = array();
		$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
		$va_related_ids = array();
		foreach($va_row_ids as $vn_row_id) {
			if(is_array($va_related_items = self::$s_rel_prefetch_cache[$this->ops_table_name][$vn_row_id][$ps_tablename][$vs_opt_md5])) {
				$va_base_row_ids[$vn_row_id] = caExtractValuesFromArrayList($va_related_items, $t_rel_instance->primaryKey());
				$va_related_ids += $va_base_row_ids[$vn_row_id];
				$pa_cache[$this->ops_table_name][$vn_row_id][$ps_tablename] = $va_base_row_ids[$vn_row_id];
			} else {
				$pa_cache[$this->ops_table_name][$vn_row_id][$ps_tablename] = array();
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
			$t_instance = SearchResult::$s_instance_cache[$this->ops_table_name] = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
		}
		if (!($t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$ps_tablename] = $this->opo_datamodel->getInstanceByTableName($ps_tablename, true);
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
				if (($va_rels = $this->opo_datamodel->getOneToManyRelations($vs_left_table)) && is_array($va_rels[$vs_right_table])) {
					$va_acc = array();
					foreach($va_rels[$vs_right_table] as $va_rel) {
						$va_acc[] =	$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
					}
					$vs_join_eq = join(" OR ", $va_acc);
					$va_joins[] = 'INNER JOIN '.$vs_right_table.' ON '.$vs_join_eq; 
					
					if (!($t_link = SearchResult::$s_instance_cache[$va_rel['many_table']])) {
						$t_link = SearchResult::$s_instance_cache[$va_rel['many_table']] = $this->opo_datamodel->getInstanceByTableName($va_rel['many_table'], true);
					}
					if (is_a($t_link, 'BaseRelationshipModel') && $t_link->hasField('type_id')) {
						$va_fields[] = $va_rel['many_table'].'.type_id rel_type_id';
					}
					if ($t_link->hasField('rank')) { 
						$va_order_bys[] = $t_link->tableName().'.rank';
					}
				} else {
					if (($va_rels = $this->opo_datamodel->getOneToManyRelations($vs_right_table)) && is_array($va_rels[$vs_left_table])) {
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
		
		$va_rel_row_ids = array();
		while($qr_rel->nextRow()) {
			$va_row = $qr_rel->getRow();
			$vn_row_id = $va_row[$this->ops_table_pk];
			
			$vn_locale_id = $vb_has_locale_id ? $va_row['locale_id'] : null;
			self::$s_prefetch_cache[$ps_tablename][$vn_row_id][$vn_locale_id][] = $va_row;
		}
		
		// Fill row_id values for which there is nothing to prefetch with an empty lists
		// otherwise we'll try and prefetch these again later wasting time.
		foreach($va_row_ids as $vn_row_id) {
			if (!isset(self::$s_prefetch_cache[$ps_tablename][$vn_row_id])) {
				self::$s_prefetch_cache[$ps_tablename][$vn_row_id] = array();
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function prefetchRelated($ps_tablename, $pn_start, $pn_num_rows, $pa_options) {
		unset($pa_options['request']);
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($pn_start, $pn_num_rows)) == 0) { return false; }
		
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
		
		$vs_md5 = caMakeCacheKeyFromOptions($pa_options);
	
		$va_criteria = is_array($this->opa_tables[$ps_tablename]) ? $this->opa_tables[$ps_tablename]['criteria'] : null;
		$va_rel_items = $this->opo_subject_instance->getRelatedItems($ps_tablename, array_merge($pa_options, array('row_ids' => $va_row_ids, 'limit' => 100000, 'criteria' => $va_criteria)));		// if there are more than 100,000 then we have a problem
		
		if (!is_array($va_rel_items) || !sizeof($va_rel_items)) { return; }
		
		if (!isset($this->opa_tables[$ps_tablename])) {
			$va_join_tables = $this->opo_datamodel->getPath($this->ops_table_name, $ps_tablename);
			array_shift($va_join_tables); 	// remove subject table
			array_pop($va_join_tables);		// remove content table (we only need linking tables here)
			
			$this->opa_tables[$ps_tablename] = array(
				'fieldList' => array($ps_tablename.'.*'),
				'joinTables' => array_keys($va_join_tables),
				'criteria' => array()
			);
		}
		
		// TODO: why is the repeatedly called?
		
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
	 * Returns a value from the query result. This can be a single value if it is a field in the subject table (eg. objects table in an objects search), or
	 * perhaps multiple related values (eg. related entities in an objects search). By default get() always returns a single value; for fields with multiple values
	 * the value will be the first value encountered when loading the field data. 
	 *
	 * You can fetch the values of attributes attached to the subject row (ie. if you're searching for ca_objects rows, the subject row is the ca_objects row)
	 * by use the "virtual" field name <subject_table_name>.<element_code> (ex. ca_objects.date_created)
	 * If the attribute is a multi-value container then you can fetch a specific value using the format <subject_table_name>.<attribute_element_code>/<value_element_code>
	 * For example, if you want to get the "date_value" value out of a "date" attribute attached to a ca_objects row, then you'd call get()
	 * with this fieldname: ca_objects.date/date_value
	 *
	 * If you want to get the other values for a multiple-value fields use the following options:
	 *
	 *		returnAsArray = if true, return an array, otherwise return a string (default is false)
	 *		template = formats attribute values; precede element codes with a caret ("^"). Eg. "^address1<br/>^city, ^state ^postalcode ^country"; only used when returnAsArray is false and a scalar is therefore to be returned.
	 *		delimiter = Characters to place in between repeating values when returning a string
	 *		returnAllLocales = Return array of all available values in all locales. Array is indexed by id and then by locale. Implies returnAsArray. [Default is false]
	 *		convertCodesToDisplayText = if true then item_ids are automatically converted to display text in the current locale [Default is false (return item_ids raw)]
	 *		convertCodesToIdno = if true then item_ids are automatically converted to list item idno's (ca_list_items.idno); if convertCodesToDisplayText is also set then it takes precedence  [Default is false (return item_ids raw)]
	 *
	 * 		restrict_to_type = restricts returned items to those of the specified type; only supports a single type which can be specified as a list item_code or item_id
 	 *		restrictToType = synonym for restrict_to_type
 	 *		restrict_to_types = restricts returned items to those of the specified types; pass an array of list item_codes or item_ids
 	 *		restrictToTypes = synonym for restrict_to_types
 	 *		restrict_to_relationship_types = restricts returned items to those related to the current row by the specified relationship type(s). You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		restrictToRelationshipTypes = synonym for restrict_to_relationship_types
 	 *
 	 *		exclude_relationship_types = omits any items related to the current row with any of the specified types from the returned set of its. You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		excludeRelationshipTypes = synonym for exclude_relationship_types
 	 *
 	 *		returnAsLink = if true and $ps_field is set to a specific field in a related table, or $ps_field is set to a related table 
 	 *				(eg. ca_entities or ca_entities.related) AND the template option is set and returnAllLocales is not set, then returned values will be links. The destination of the link will be the appropriate editor when executed within Providence or the appropriate detail page when executed within Pawtucket or another front-end. Default is false.
 	 *				If $ps_field is set to refer to a URL metadata element and returnAsLink is set then the returned values will be HTML links using the URL value.
 	 *		returnAsLinkText = text to use a content of HTML link. If omitted the url itself is used as the link content.
 	 *		returnAsLinkAttributes = array of attributes to include in link <a> tag. Use this to set class, alt and any other link attributes.
 	 * 		returnAsLinkTarget = Optional link target. If any plugin implementing hookGetAsLink() responds to the specified target then the plugin will be used to generate the links rather than CA's default link generator.
 	 *
 	 *		hierarchyDirection = asc|desc Order in which to return levels when get()'ing a hierarchical path. "Asc"ending  begins with the root; "desc"ending begins with the child furthest from the root [Default is asc]
 	 *		allDescendants = Return all items from the full depth of the hierarchy when get()'ing children rather than only immediate children. [Default is false]
 	 *
 	 *		sort = optional array of bundles to sort returned values on. Currently only supported when getting related values via simple related <table_name> and <table_name>.related invokations. Eg. from a ca_objects results you can use the 'sort' option got get('ca_entities'), get('ca_entities.related') or get('ca_objects.related'). The bundle specifiers are fields with or without tablename. Only those fields returned for the related tables (intrinsics and label fields) are sortable. You cannot sort on attributes.
	 *		filters = optional array of elements to filter returned values on. The element must be part of the container being fetched from. For example, if you're get()'ing a value from a container element (Eg. ca_objects.dates.date_value) you can filter on any other subelement in that container by passing the name of the subelement and a value (Eg. "date_type" => "copyright"). Pass only the name of the subelement, not the full path that includes the table and container element. You can filter on multiple subelements by passing each subelement as a key in the array. Only values that match all filters are returned. You can filter on multiple values for a subelement by passing an array of values rather than a scalar (Eg. "date_type" => array("copyright", "patent")). Values that match *any* of the values will be returned. Only simple equivalance is supported. NOTE: Filters are only available when returnAsArray is set. They will be ignored if returnAsArray is not set.
	 *
	 *		maxLevelsFromTop = for hierarchical gets, restricts the number of levels returned to the top-most starting with the root.
	 *		maxLevelsFromBottom = for hierarchical gets, restricts the number of levels returned to the bottom-most starting with the lowest leaf node.
	 *		maxLevels = synonym for maxLevelsFromBottom
	 *
	 *		assumeDisplayField = Return display field for ambiguous preferred label specifiers (Ex. ca_entities.preferred_labels => ca_entities.preferred_labels.displayname), otherwise  an array with all label fields is returned [Default is true]
	 *		
	 *		checkAccess = Array of access values to filter returned values on. Available for any table with an "access" field (ca_objects, ca_entities, etc.). If omitted no filtering is performed. [Default is null]
	 *
	 * 	@return mixed String or array
	 */
	public function get($ps_field, $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		$vb_return_as_array = isset($pa_options['returnAsArray']) ? (bool)$pa_options['returnAsArray'] : false;
		$va_filters = is_array($pa_options['filters']) ? $pa_options['filters'] : array();
		
		// Add table name to field specs that lack it
		if ((strpos($ps_field, '.') === false) && (!$this->opo_datamodel->tableExists($ps_field))) {
			$va_tmp = array($this->ops_table_name, $ps_field);
			$ps_field = $this->ops_table_name.".{$ps_field}";
		}
		
		$vm_val = self::_get($ps_field, $pa_options);
		
		if ($vb_return_as_array && sizeof($va_filters)) {
			$va_tmp = explode(".", $ps_field);
			if (sizeof($va_tmp) > 1) { array_pop($va_tmp); }
			
			
			if (!($t_instance = SearchResult::$s_instance_cache[$va_tmp[0]])) {
				$t_instance = SearchResult::$s_instance_cache[$va_tmp[0]] = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true);
			}
			
			if ($t_instance) {
				$va_keepers = array();
				foreach($va_filters as $vs_filter => $va_filter_vals) {
					if(!$vs_filter) { continue; }
					if (!is_array($va_filter_vals)) { $va_filter_vals = array($va_filter_vals); }
					
					foreach($va_filter_vals as $vn_index => $vs_filter_val) {
						// is value a list attribute idno?
						if (!is_numeric($vs_filter_val) && (($t_element = $t_instance->_getElementInstance($vs_filter)) && ($t_element->get('datatype') == 3))) {
							$va_filter_vals[$vn_index] = caGetListItemID($t_element->get('list_id'), $vs_filter_val);
						}
					}
				
					$va_filter_values = $this->get(join(".", $va_tmp).".{$vs_filter}", array('returnAsArray' => true, 'alwaysReturnItemID' => true));
			
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
				return $va_filtered_vals;
			}
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
	 * Actual implementation of get()
	 *
	 * @param string $ps_field bundle specifier
	 * @param null|array $pa_options options array
	 * @return array|null|string
	 */
	private function _get($ps_field, $pa_options=null) {
		if (!is_array($pa_options)) $pa_options = array();
		
		$vb_return_as_array 				= caGetOption('returnAsArray', $pa_options, false); 
		$vb_return_all_locales 				= caGetOption('returnAllLocales', $pa_options, false);
		if ($vb_return_all_locales) { $vb_return_as_array = true; } // returnAllLocales implies returnAsArray

		$vs_delimiter 						= caGetOption('delimiter', $pa_options, ';'); 
		$vb_unserialize 					= caGetOption('unserialize', $pa_options, false); 
		
		$vb_return_url 						= caGetOption('returnURL', $pa_options, false); 
		$vb_convert_codes_to_display_text 	= caGetOption('convertCodesToDisplayText', $pa_options, false); 
		$vb_convert_codes_to_idno 			= caGetOption('convertCodesToIdno', $pa_options, false); 
		
		$vn_max_levels_from_top 			= caGetOption('maxLevelsFromTop', $pa_options, null);
		$vn_max_levels_from_bottom 			= caGetOption('maxLevelsFromBottom', $pa_options, caGetOption('maxLevels', $pa_options, null));
		$vn_remove_first_items 				= caGetOption('removeFirstItems', $pa_options, 0, array('castTo' => 'int'));

		$va_check_access 					= caGetOption('checkAccess', $pa_options, null); 
		$vs_template 						= caGetOption('template', $pa_options, null);
		
		
		$va_path_components = isset(SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_field]) ? SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_field] : $this->parseFieldPathComponents($ps_field);
		
		$va_val_opts = array_merge($pa_options, array(
			'returnAsArray' => $vb_return_as_array,
			'returnAllLocales' => $vb_return_all_locales,
			'pathComponents' => $va_path_components,
			'delimiter' => $vs_delimiter,
			'returnURL' => $vb_return_url,
			'convertCodesToDisplayText' => $vb_convert_codes_to_display_text,
			'convertCodesToIdno' => $vb_convert_codes_to_idno,
			'checkAccess' => $va_check_access,
			'template' => $vs_template
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
			if ($vb_return_as_array) {
				if ($vb_return_all_locales) {
					return array(1 => $vs_value);
				} else {
					return array($vs_value);
				}
			} else {
				return $vs_value;
			}
		}
		
		if (!($t_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']])) {
			$t_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']] = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
		}
		if (!$t_instance) { return null; }	// Bad table
		
		$vn_row_id = $this->opo_engine_result->get($this->ops_table_pk);
		$va_val_opts['primaryKey'] = $t_instance->primaryKey();
		
		if ($va_path_components['hierarchical_modifier']) {
			switch($va_path_components['hierarchical_modifier']) {
				case 'parent':
					if ($va_path_components['related']) {
						// [RELATED TABLE PARENT]
						
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']])) {
							$this->prefetchHierarchyParents($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']];
					} else {
						// [PRIMARY TABLE PARENT]
						
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id])) {
							$this->prefetchHierarchyParents($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						$va_ids = array($vn_row_id);
					}
					if (!sizeof($va_ids)) { return $pa_options['returnAsArray'] ? array() : null; }
					
					$va_hiers = array();
					
					foreach($va_ids as $vn_id) {
						$va_parent_ids = array();
						if (
							isset(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id])
							&&
							is_array(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id])	
						) {
							if (!is_array($va_parent_ids = SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id])) {
								return $pa_options['returnAsArray'] ? array() : null;
							}
						}
						
						$va_parent_ids = array_slice($va_parent_ids, 0, 1);
					
						if (!($qr_hier = $t_instance->makeSearchResult($va_path_components['table_name'], $va_parent_ids))) {
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
					
					return $vb_return_as_array ? $va_hiers : join($vs_delimiter, $va_hiers);
					
					break;
				case 'hierarchy':
					// generate the hierarchy
					if ($va_path_components['related']) {
						// [RELATED TABLE HIERARCHY]
						
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']])) {
							$this->prefetchHierarchyParents($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						// ids of related items
						$va_ids = array_values(SearchResult::$opa_hierarchy_parent_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']]);
					
					} else {
						// [PRIMARY TABLE HIERARCHY]
						if (!isset(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id])) {
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
									if(is_array(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id])) {
										$va_hier_id_list = array_merge(array($vn_id), SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_id]);
										if ($vs_hierarchy_direction === 'asc') { $va_hier_id_list = array_reverse($va_hier_id_list); }
										$va_hier_ids[] = $va_hier_id_list;
									}
								}
							} else {
								// Return ids from hierarchy in order
								if(is_array(SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id])) {
									$va_hier_ids = array_merge(array($vn_row_id), SearchResult::$opa_hierarchy_parent_prefetch_cache[$va_path_components['table_name']][$vn_row_id]);
								} else {
									$va_hier_ids = array($vn_row_id);
								}
								//print caPrintStackTrace();
								if ($vs_hierarchy_direction === 'asc') { $va_hier_ids = array_reverse($va_hier_ids); }
							}
							return $vb_return_as_array ?  $va_hier_ids : join($vs_delimiter, $va_hier_ids);
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
						
								$va_hiers = array();
								if ($qr_hier = caMakeSearchResult($va_path_components['table_name'], $va_ancestor_ids)) {
							
									while($qr_hier->nextHit()) {
										$vm_val = $qr_hier->get($vs_field_spec, $pa_options);
										$va_hiers[] = $vb_return_as_array ? array_shift($vm_val) : $vm_val;
									}
									if (!is_null($vn_max_levels_from_top)) {
										$va_hiers = array_slice($va_hiers, 0, $vn_max_levels_from_top, true);
									} elseif (!is_null($vn_max_levels_from_bottom)) {
										if (($vn_start = sizeof($va_hiers) - $vn_max_levels_from_bottom) < 0) { $vn_start = 0; }
										$va_hiers = array_slice($va_hiers, $vn_start, $vn_max_levels_from_bottom, true);
									}
								}
								
								if ($va_path_components['related']) {
									$va_acc = array();
									if ($vb_return_all_locales && $vb_return_as_array) {
										foreach($va_hiers as $vn_i => $va_by_locale) {
											foreach($va_by_locale as $vn_locale_id => $va_val) {
												$va_acc[$vn_locale_id][] = join($vs_delimiter, array_filter($va_val, 'strlen'));
											}
										}
									} elseif($vb_return_as_array) {
										foreach($va_hiers as $vn_i => $va_val) {
											$va_acc[] = join($vs_delimiter, array_filter($va_val, 'strlen'));
										}
									} else {
										$va_acc = join($vs_delimiter, array_filter($va_hiers, 'strlen'));
									}
									$va_hier_list[] = $va_acc;
								} else { 
									return $vb_return_as_array ? $va_hiers : join($vs_delimiter, array_filter($va_hiers, 'strlen'));
								}
							}
						}
					}
					
					return $vb_return_as_array ? $va_hier_list : join($vs_delimiter, $va_hier_list);
					
					break;
				case 'children':
					// grab children 
					if ($va_path_components['related']) {
						// [RELATED TABLE CHILDREN]
						
						if (!isset(SearchResult::$opa_hierarchy_children_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']])) {
							$this->prefetchHierarchyChildren($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = SearchResult::$opa_hierarchy_children_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']];
					} else {
						// [PRIMARY TABLE CHILDREN]
						
						if (!isset(SearchResult::$opa_hierarchy_children_prefetch_cache[$this->ops_table_name][$vn_row_id])) {
							$this->prefetchHierarchyChildren($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = array($vn_row_id);
					}
					
					$va_hier_list = array();
					foreach($va_ids as $vn_id) {
						if (
							!is_array(SearchResult::$opa_hierarchy_children_prefetch_cache[$va_path_components['table_name']][$vn_id])
							||
							!sizeof(SearchResult::$opa_hierarchy_children_prefetch_cache[$va_path_components['table_name']][$vn_id])
						){ 
							continue;
						}
						$qr_hier = $t_instance->makeSearchResult($va_path_components['table_name'], SearchResult::$opa_hierarchy_children_prefetch_cache[$va_path_components['table_name']][$vn_id]);
						
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
						return join($vs_delimiter, $va_hier_list);
					}
					return $va_hier_list;
					break;
				case 'siblings':
					// grab siblings 
					if ($va_path_components['related']) {
						// [RELATED TABLE SIBLINGS]
						
						if (!isset(SearchResult::$opa_hierarchy_siblings_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']])) {
							$this->prefetchHierarchySiblings($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = SearchResult::$opa_hierarchy_siblings_prefetch_cache_index[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']];
						
					} else {
						// [PRIMARY TABLE SIBLINGS]
						
						if (!isset(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$this->ops_table_name][$vn_row_id])) {
							$this->prefetchHierarchySiblings($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
						}
						
						$va_ids = array($vn_row_id);
					}
					
					$va_hier_list = array();
					foreach($va_ids as $vn_id) {
						if (
							!is_array(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$va_path_components['table_name']][$vn_id])
							||
							!sizeof(SearchResult::$opa_hierarchy_siblings_prefetch_cache[$va_path_components['table_name']][$vn_id])
						){ 
							continue;
						}
						$qr_hier = $t_instance->makeSearchResult($va_path_components['table_name'], SearchResult::$opa_hierarchy_siblings_prefetch_cache[$va_path_components['table_name']][$vn_id]);
						
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
						return join($vs_delimiter, $va_hier_list);
					}
					return $va_hier_list;
					break;
			}
			return;
		}

		if ($va_path_components['related']) {
//
// [RELATED TABLE] 
//
			$vs_opt_md5 = caMakeCacheKeyFromOptions(array_merge($pa_options, array('dontReturnLabels' => false)));
			
			if (!isset(self::$s_rel_prefetch_cache[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5])) {
				$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), array_merge($pa_options, array('dontReturnLabels' => false)));
			}
			
			$va_related_items = self::$s_rel_prefetch_cache[$this->ops_table_name][$vn_row_id][$va_path_components['table_name']][$vs_opt_md5];

			if (!is_array($va_related_items)) { return $vb_return_as_array ? array() : null; }
		
			
			return $this->_getRelatedValue($va_related_items, $va_val_opts);
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
						return self::$s_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id];
					} else {
						$vs_subfield = $va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : 'timestamp';
						$vm_val = self::$s_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id][$vs_subfield];
				
						if ($vs_subfield == 'timestamp') {
							$this->opo_tep->init();
							$this->opo_tep->setUnixTimestamps($vm_val, $vm_val);
							$vm_val = $this->opo_tep->getText($pa_options);
						}
						return $vm_val;
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
						return self::$s_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id];
					} else {
						$vs_subfield = $va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : 'timestamp';
						$vm_val = self::$s_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id][$vs_subfield];
				
						if ($vs_subfield == 'timestamp') {
							$this->opo_tep->init();
							$this->opo_tep->setUnixTimestamps($vm_val, $vm_val);
							$vm_val = $this->opo_tep->getText($pa_options);
						}
						return $vm_val;
					}
				}
	
//
// [PRIMARY TABLE] Preferred/nonpreferred labels
//
				if (in_array($va_path_components['field_name'], array('preferred_labels', 'nonpreferred_labels')) && ($t_instance instanceof LabelableBaseModelWithAttributes)) {
					$vs_label_table_name = $t_instance->getLabelTableName();
					if (!isset(self::$s_prefetch_cache[$vs_label_table_name][$vn_row_id])) {
						$this->prefetchLabels($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
					}
					return $this->_getLabelValue(self::$s_prefetch_cache[$vs_label_table_name][$vn_row_id], $t_instance, $va_val_opts);
				}
					
				if ($t_instance->hasField($va_path_components['field_name'])) {
					$va_val_opts['fieldInfo'] = $t_instance->getFieldInfo($va_path_components['field_name']);
//
// [PRIMARY TABLE] Plain old intrinsic
//
					if (!isset(self::$s_prefetch_cache[$va_path_components['table_name']][$vn_row_id])) {
						$this->prefetch($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);	
					}
					return $this->_getIntrinsicValue(self::$s_prefetch_cache[$va_path_components['table_name']][$vn_row_id], $t_instance, $va_val_opts);

				} elseif(method_exists($t_instance, 'isValidBundle') && !$t_instance->hasElement($va_path_components['field_name']) && $t_instance->isValidBundle($va_path_components['field_name'])) {
//
// [PRIMARY TABLE] Special bundle
//				
					return $t_instance->renderBundleForDisplay($va_path_components['field_name'], $vn_row_id, self::$s_prefetch_cache[$va_path_components['table_name']][$vn_row_id], $va_val_opts);
				} else {
//
// [PRIMARY TABLE] Metadata attribute
//				

					if (($t_instance instanceof BaseModelWithAttributes) && isset($va_path_components['field_name']) && $va_path_components['field_name'] && $t_element = $t_instance->_getElementInstance($va_path_components['field_name'])) {
						$vn_element_id = $t_element->getPrimaryKey();
					} else {
						return $pa_options['returnAsArray'] ? array() : null;
					}
					if (!isset(ca_attributes::$s_get_attributes_cache[(int)$this->opn_table_num.'/'.(int)$vn_row_id][(int)$vn_element_id])) {
						ca_attributes::prefetchAttributes($this->opo_subject_instance->getDb(), $this->opn_table_num, $this->getRowIDsToPrefetch($this->opo_engine_result->currentRow(), $this->getOption('prefetch')), ($vn_element_id ? array($vn_element_id) : null), array('dontFetchAlreadyCachedValues' => true));
					}
					$va_attributes = ca_attributes::getAttributes($this->opo_subject_instance->getDb(), $this->opn_table_num, $vn_row_id, array($vn_element_id), array());
			
					return $this->_getAttributeValue($va_attributes[$vn_element_id], $t_instance, $va_val_opts);
				}
			}
		}
		
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * get() value for related table
	 *
	 * @param array $pa_value_list
	 * @param array Options include:
	 *		pathComponents = 
	 *		returnAsArray =
	 *		returnAllLocales =
	 *		returnAsLink = 
	 *		delimiter =
	 *		template =
	 *
	 * @return array|string
	 */
	private function _getRelatedValue($pa_value_list, $pa_options=null) {
		$vb_return_as_link 		= caGetOption('returnAsLink', $pa_options, false, array('castTo' => 'bool'));
		$va_path_components		=& $pa_options['pathComponents'];
		$vs_template 			= caGetOption('template', $pa_options);
		
		$pa_check_access		= caGetOption('checkAccess', $pa_options, null);

		// Handle table-only case...
		if (!$va_path_components['field_name']) {
			// ... by returning array of values from related items
			if ($pa_options['returnAsArray']) {  return is_array($pa_value_list) ? $pa_value_list : array(); }

			// ... by processing a display template for these records
			if(!($vs_template)) {
				// ... or by returning a list of preferred label values if no template is set
				$va_path_components['field_name'] = 'preferred_labels';
			}
		}
		
		if (!($t_rel_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']])) {
			$t_rel_instance = SearchResult::$s_instance_cache[$va_path_components['table_name']] = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
		}

		if (!($t_rel_instance instanceof BundlableLabelableBaseModelWithAttributes)) { return null; }
		
		$vs_pk = $t_rel_instance->primaryKey();
		
		$va_ids = array();
		foreach($pa_value_list as $vn_i => $va_rel_item) {
			$va_ids[] = $va_rel_item[$vs_pk];
		}
		if (!sizeof($va_ids)) { return ($pa_options['returnAllLocales'] || $pa_options['returnAsArray']) ? array() : null; }

		if(!$va_path_components['field_name']) {  // get spec is a plain table without field_name -> there is a template
			if($vb_return_as_link) {
				$va_links = array();
				foreach($pa_value_list as $vs_key => $va_relation_info) {
					$va_template_opts = array();
					$va_template_opts['relationshipValues'][$va_relation_info[$vs_pk]][$va_relation_info['relation_id']]['relationship_typename'] = $va_relation_info['relationship_typename'];
					$vs_text = caProcessTemplateForIDs($vs_template, $t_rel_instance->tableName(), array($va_relation_info[$vs_pk]), $va_template_opts);
					$va_link = caCreateLinksFromText(array($vs_text), $t_rel_instance->tableName(), array($va_relation_info[$vs_pk]));
					$va_links[$vs_key] = array_pop($va_link);
				}
				return (sizeof($va_links) > 0) ? join($pa_options['delimiter'], $va_links) : null;
			} else {
				return caProcessTemplateForIDs($vs_template, $t_rel_instance->tableName(), $va_ids, $pa_options);
			}
		}

		$qr_rel = $t_rel_instance->makeSearchResult($va_path_components['table_name'], $va_ids);
		$va_return_values = array();
		$va_spec = array();
		foreach(array('table_name', 'field_name', 'subfield_name') as $vs_f) {
			if ($va_path_components[$vs_f]) { $va_spec[] = $va_path_components[$vs_f]; }
		}

		while($qr_rel->nextHit()) {
			$vm_val = $qr_rel->get(join(".", $va_spec), $pa_options);
			if (is_array($pa_check_access) && sizeof($pa_check_access) && !in_array($qr_rel->get($va_path_components['table_name'].".access"), $pa_check_access)) {
				continue;
			}
			
			if (is_null($vm_val)) { continue; } // Skip null values; indicates that there was no related value
			if (caGetOption('returnAsArray', $pa_options, false)) {
				foreach($vm_val as $vn_i => $vs_val) {
					// We include blanks in arrays so various get() calls on different fields in the same record set align
					$va_return_values[] = $vs_val;
				}
			} else {
				$va_return_values[$qr_rel->get($t_rel_instance->primaryKey())] = $vm_val;
			}
		}
	
		if ($pa_options['unserialize'] && !$pa_options['returnAsArray']) { return array_shift($va_return_values); }	
		if ($pa_options['returnAllLocales'] || $pa_options['returnAsArray']) { return is_array($va_return_values) ? $va_return_values : array(); } 
		
		if ($vb_return_as_link) {
			$va_return_values = caCreateLinksFromText($va_return_values, $t_rel_instance->tableName(), array($va_relation_info[$vs_pk]));
		}
	
		return (sizeof($va_return_values) > 0) ? join($pa_options['delimiter'], $va_return_values) : null;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_value_list
	 * @param BaseModel $pt_instance
	 * @param array Options include:
	 *		pathComponents = 
	 *		returnAsArray =
	 *		returnAllLocales =
	 *		returnAsLink = 
	 *		delimiter =
	 *		convertCodesToDisplayText =
	 *		convertCodesToIdno =
	 *		assumeDisplayField = Return display field for ambiguous preferred label specifiers (Ex. ca_entities.preferred_labels => ca_entities.preferred_labels.displayname), otherwise  an array with all label fields is returned [Default is true]
	 *
	 * @return array|string
	 */
	private function _getLabelValue($pa_value_list, $pt_instance, $pa_options) {
		$vb_return_as_array 		= caGetOption('returnAsArray', $pa_options, false, array('castTo' => 'bool'));
		$vb_return_all_locales 		= caGetOption('returnAllLocales', $pa_options, false, array('castTo' => 'bool'));
		$vb_return_as_link 			= caGetOption('returnAsLink', $pa_options, false, array('castTo' => 'bool'));
		$vb_assume_display_field 	= caGetOption('assumeDisplayField', $pa_options, true, array('castTo' => 'bool'));
		$vs_template 				= caGetOption('template', $pa_options, null, array('castTo' => 'string'));
		
		$va_path_components			=& $pa_options['pathComponents'];
		
		// Set subfield to display field if not specified and *NOT* returning as array
		// (when returning as array without a specified subfield we return an array with entire label record)
		if ((!$vb_return_as_array || $vb_assume_display_field) && !$va_path_components['subfield_name']) { $va_path_components['subfield_name'] = $pt_instance->getLabelDisplayField(); }
		
		$vs_table_name = $pt_instance->tableName();
		$vs_pk = $pt_instance->primaryKey();
		
		$va_return_values = array();
		if (is_array($pa_value_list)) {
			foreach($pa_value_list as $vn_locale_id => $va_labels_by_locale) {
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

					if ($vs_template) {
						$vs_val_proc = caProcessTemplateForIDs($vs_template, $vs_table_name, array($vn_id), $pa_options);
					} else {
						$vs_val_proc = $va_label[$va_path_components['subfield_name']];
					}
					
					if (caGetOption('convertCodesToDisplayText', $pa_options, false)) {
						$vs_val_proc = $this->_convertCodeToDisplayText($vs_val_proc, $va_path_components, $pt_instance->getLabelTableInstance(), $pa_options);
					} elseif(caGetOption('convertCodesToIdno', $pa_options, false)) {
						$vs_val_proc = $this->_convertCodeToIdno($vs_val_proc, $va_path_components, $pt_instance->getLabelTableInstance(), $pa_options);
					}
					
					if ($vb_return_as_link) {
						$vs_val_proc = caCreateLinksFromText($vs_val_proc, $vs_table_name, $vn_id);
					}
					
					if ($vb_return_all_locales) {
						$va_return_values[0][$vn_locale_id][] = !$va_path_components['subfield_name'] ? $va_label : $vs_val_proc;
					} else {
						$va_return_values[0][$vn_locale_id] = !$va_path_components['subfield_name'] ? $va_label : $vs_val_proc;
					}
				}
			}
		}
		
		if ($vb_return_all_locales) { return $va_return_values; } 
		$va_return_values = array_values(caExtractValuesByUserLocale($va_return_values));
		if ($vb_return_as_array) { return $va_return_values; }
		
		return (sizeof($va_return_values) > 0) ? join($pa_options['delimiter'], $va_return_values) : null;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_value_list
	 * @param BaseModel $pt_instance
	 * @param array Options include:
	 *		pathComponents = 
	 *		returnAsArray =
	 *		returnAllLocales =
	 *		returnAsLink = 
	 *		delimiter =
	 *
	 * @return array|string
	 */
	private function _getAttributeValue($pa_value_list, $pt_instance, $pa_options) {
		$vb_return_as_array 	= caGetOption('returnAsArray', $pa_options, false, array('castTo' => 'bool'));
		$vb_return_all_locales 	= caGetOption('returnAllLocales', $pa_options, false, array('castTo' => 'bool'));
		$vb_return_as_link 		= caGetOption('returnAsLink', $pa_options, false, array('castTo' => 'bool'));
		$va_path_components		=& $pa_options['pathComponents'];
		$va_return_values = array();
		
		$vn_id = $this->get($pt_instance->primaryKey(true));
		$vs_table_name = $pt_instance->tableName();
		
		if (is_array($pa_value_list)) {
			$vn_c = 0;
			foreach($pa_value_list as $o_attribute) {
				$va_values = $o_attribute->getValues();
				if (!($vn_locale_id = $o_attribute->getLocaleID())) { $vn_locale_id = 1; };
			
				foreach($va_values as $o_value) {
					$vs_element_code = $o_value->getElementCode();
					if ($va_path_components['subfield_name']) {
						if ($va_path_components['subfield_name'] !== $vs_element_code) { continue; }
						$vs_element_code = is_array($va_return_values[$vn_c][$vn_locale_id]) ? sizeof($va_return_values[$vn_c][$vn_locale_id]) : 0;
					}
					
					switch($o_value->getType()) {
						case __CA_ATTRIBUTE_VALUE_LIST__:
							$t_element = $pt_instance->_getElementInstance($o_value->getElementID());
							$vn_list_id = $t_element->get('list_id');
							
							$vs_val_proc = $o_value->getDisplayValue(array_merge($pa_options, array('alwaysReturnItemID' => !caGetOption('convertCodesToDisplayText', $pa_options, false), 'list_id' => $vn_list_id)));
							break;
						default:
							$vs_val_proc = $o_value->getDisplayValue($pa_options);
							break;
					}
					
					if($vb_return_as_link) { $vs_val_proc = caCreateLinksFromText($vs_val_proc, $vs_table_name, $vn_id); }
					if(isset($va_return_values[$vn_c][$vn_locale_id])) { $vn_c++; }
					
					if(!$vb_return_all_locales) {
						$va_return_values[$vn_c][$vn_locale_id] = $vs_val_proc;
					} else {
						$va_return_values[$vn_c][$vn_locale_id][$vs_element_code] = $vs_val_proc;
					}
				}
			}
		}
		
		if ($pa_options['returnAllLocales']) { return $va_return_values; } 	
		if ($pa_options['returnAsArray']) { return is_array($va_return_values) ? array_values(caExtractValuesByUserLocale($va_return_values)) : array(); }
		$va_return_values = caExtractValuesByUserLocale($va_return_values);
		
		return (sizeof($va_return_values) > 0) ? join($pa_options['delimiter'], $va_return_values) : null;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_value_list
	 * @param BaseModel $pt_instance
	 * @param array Options include:
	 *		pathComponents = 
	 *		returnAsArray =
	 *		returnAllLocales =
	 *		returnAsLink = 
	 *		delimiter =
	 *		unserialize =
	 *		convertCodesToDisplayText = 
	 *		convertCodesToIdno = 
	 *		fieldInfo =
	 *		primaryKey = 
	 *
	 * @return array|string
	 */
	private function _getIntrinsicValue($pa_value_list, $pt_instance, $pa_options) {
		$vb_return_as_link 		= caGetOption('returnAsLink', $pa_options, false, array('castTo' => 'bool'));
		$va_path_components		= $pa_options['pathComponents'];
		$va_field_info 			= $pa_options['fieldInfo'];
		$vs_pk 					= $pa_options['primaryKey'];
	
		$vs_table_name = $pt_instance->tableName();
		
		// Handle specific intrinsic types
		switch($va_field_info['FIELD_TYPE']) {
			case FT_DATERANGE:
			case FT_HISTORIC_DATERANGE:
				foreach($pa_value_list as $vn_locale_id => $va_values) {
					foreach($va_values as $vn_i => $va_value) {
						$va_ids[] = $vn_id = $va_value[$vs_pk];
	
						if (caGetOption('getDirectDate', $pa_options, false) || caGetOption('GET_DIRECT_DATE', $pa_options, false)) {
							$vs_prop = $va_value[$va_field_info['START']];
						} elseif(caGetOption('sortable', $pa_options, false)) {
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
						
						if ($vb_return_as_link) { $vs_prop = caCreateLinksFromText($vs_prop, $vs_table_name, $vn_id); }
						
						if ($pa_options['returnAllLocales']) {
							$va_return_values[$vn_locale_id][] = $vs_prop;
						} else {
							$va_return_values[] = $vs_prop;
						}
					}
				}
				break;
			case FT_MEDIA:
				if(!($vs_version = $va_path_components['subfield_name'])) {
					$vs_version = "largeicon";
				}
				
				foreach($pa_value_list as $vn_locale_id => $va_values) {
					foreach($va_values as $vn_i => $va_value) {
						$va_ids[] = $vn_row_id = $va_value[$vs_pk];
						
						if ($pa_options['unserialize']) {
							$va_props = caUnserializeForDatabase($va_value[$va_path_components['field_name']]);
			
							if ($pa_options['returnAllLocales']) {
								$va_return_values[$vn_row_id][$vn_locale_id][] = $va_props;
							} else {
								$va_return_values[] = $va_props;
							}
						} else {
							$o_media_settings = new MediaProcessingSettings($va_path_components['table_name'], $va_path_components['field_name']);
							$va_versions = $o_media_settings->getMediaTypeVersions('*');
			
		
							if (!isset($va_versions[$vs_version])) {
								$va_tmp = array_keys($va_versions);
								$vs_version = array_shift($va_tmp);
							}
							
							// See if an info element was passed, eg. ca_object_representations.media.icon.width should return the width of the media rather than a tag or url to the media
							$vs_info_element = ($va_path_components['num_components'] == 4) ? $va_path_components['components'][3] : null;
				
							if ($pa_options['returnAllLocales']) {
								if ($vs_info_element) {
									$va_return_values[$vn_row_id][$vn_locale_id][] = $this->getMediaInfo($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $vs_info_element, $pa_options);
								} elseif (isset($pa_options['returnURL']) && ($pa_options['returnURL'])) {
									$va_return_values[$vn_row_id][$vn_locale_id][] = $this->getMediaUrl($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
								} else {
									$va_return_values[$vn_row_id][$vn_locale_id][] = $this->getMediaTag($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
								}
							} else {
								if ($vs_info_element) {
									$va_return_values[] = $this->getMediaInfo($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $vs_info_element, $pa_options);
								} elseif (isset($pa_options['returnURL']) && ($pa_options['returnURL'])) {
									$va_return_values[] = $this->getMediaUrl($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
								} else {
									$va_return_values[] = $this->getMediaTag($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
								}
							}
						}
					}
				}
				break;
			default:
				// is intrinsic field in primary table
				foreach($pa_value_list as $vn_locale_id => $va_values) {
					foreach($va_values as $vn_i => $va_value) {
						$va_ids[] = $vn_id = $va_value[$vs_pk];
	
						$vs_prop = $va_value[$va_path_components['field_name']];
						if ($pa_options['unserialize']) {
							$vs_prop = caUnserializeForDatabase($vs_prop);
						}
						
						if (caGetOption('convertCodesToDisplayText', $pa_options, false)) {
							$vs_prop = $this->_convertCodeToDisplayText($vs_prop, $va_path_components, $pt_instance, $pa_options);
						} elseif(caGetOption('convertCodesToIdno', $pa_options, false)) {
							$vs_prop = $this->_convertCodeToIdno($vs_prop, $va_path_components, $pt_instance, $pa_options);
						}	
						
						if ($pa_options['returnAllLocales']) {
							$va_return_values[$vn_id][$vn_locale_id][] = $vs_prop;
						} else {
							$va_return_values[] = $vs_prop;
						}
					}
				}
				break;
		}	
		
		if ($pa_options['unserialize'] && !$pa_options['returnAsArray']) {
			return array_shift($va_return_values);
		}
						
		if ($pa_options['returnAllLocales'] || $pa_options['returnAsArray']) { return $va_return_values; } 
		return (sizeof($va_return_values) > 0) ? join($pa_options['delimiter'], $va_return_values) : null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getWithTemplate($ps_template, $pa_options=null) {	
		return caProcessTemplateForIDs($ps_template, $this->ops_table_name, array($this->get($this->ops_table_name.".".$this->ops_subject_pk)), $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getWithTemplateForResults($ps_template, $pa_options=null) {	
		$pn_start = caGetOption('start', $pa_options, 0);
		$this->seek($pn_start);
		
		return caProcessTemplateForIDs($ps_template, $this->ops_table_name, array($this->get($this->ops_table_name.".".$this->ops_subject_pk)), $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getAttributeAsHTMLLink($ps_val, $ps_field, $pa_attributes=array(), $pa_options=null) {
		if (!is_array($pa_attributes)) { $pa_attributes = array(); }
		$vs_return_as_link_class = 	(isset($pa_options['returnAsLinkClass'])) ? (string)$pa_options['returnAsLinkClass'] : '';
		$vs_return_as_link_get_text_from = 	(isset($pa_options['returnAsLinkGetTextFrom'])) ? (string)$pa_options['returnAsLinkGetTextFrom'] : '';
		
		$vs_val = $va_subvalues[$vn_attribute_id];
		$va_tmp = explode(".", $ps_field); array_pop($va_tmp);
		$vs_link_text = ($vs_return_as_link_get_text_from) ? $this->get(join(".", $va_tmp).".{$vs_return_as_link_get_text_from}") : $ps_val;

		$va_link_attr = $pa_attributes;
		$va_link_attr['href'] = $ps_val;
		if ($vs_return_as_link_class) { $va_link_attr['class'] = $vs_return_as_link_class; }
		
		return caHTMLLink($vs_link_text, $va_link_attr);
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
	private function _getElementHierarchy($pt_instance, $pa_path_components) {
		$vb_is_in_container = false;
		if (
			(
				($pa_path_components['subfield_name'] === 'hierarchy') 
				&& 
				in_array($pt_instance->_getElementDatatype($pa_path_components['field_name']), array(__CA_ATTRIBUTE_VALUE_LIST__))
			)
			||
			(
				isset($pa_path_components['components'][3]) 
				&& 
				($pa_path_components['components'][3] === 'hierarchy') 
				&& 
				($pt_instance->_getElementDatatype($pa_path_components['field_name']) == __CA_ATTRIBUTE_VALUE_CONTAINER__)
				&&
				($vb_is_in_container = in_array($pt_instance->_getElementDatatype($pa_path_components['subfield_name']), array(__CA_ATTRIBUTE_VALUE_LIST__)))
			)
		) {
			if ($vb_is_in_container) {
				$va_items = $this->get($pa_path_components['table_name'].'.'.$pa_path_components['field_name'].'.'.$pa_path_components['subfield_name'], array('returnAsArray' => true));
			} else {
				$va_items = $this->get($pa_path_components['table_name'].'.'.$pa_path_components['field_name'], array('returnAsArray' => true));
			}
			if (!is_array($va_items)) { return null; }
			$va_item_ids = caExtractValuesFromArrayList($va_items, $pa_path_components['field_name'], array('preserveKeys' => false));
			$qr_items = caMakeSearchResult('ca_list_items', $va_item_ids);
			
			if (!$va_item_ids || !is_array($va_item_ids) || !sizeof($va_item_ids)) {  return array(); } 
			$va_vals = array();
			
			$va_get_spec = $pa_path_components['components'];
			array_shift($va_get_spec); array_shift($va_get_spec);
			if ($vb_is_in_container) { array_shift($va_get_spec); }
			array_unshift($va_get_spec, 'ca_list_items');
			$vs_get_spec = join('.', $va_get_spec);
			while($qr_items->nextHit()) {
				$va_hier = $qr_items->get($vs_get_spec, array('returnAsArray' => true));
				array_shift($va_hier);	// get rid of root
				$va_vals[] = $va_hier;
			}
			return $va_vals;
		} 
		return null;
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
				$vs_prop = $this->opt_list->caGetListItemIDForValue($vs_list_code, $vs_prop);
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
		
		$vs_table_name = $pa_path_components['table_name'];
		if (method_exists($pt_instance, 'setLabelTypeList')) {
			$pt_instance->setLabelTypeList($this->opo_subject_instance->getAppConfig()->get(($pa_path_components['field_name'] == 'nonpreferred_labels') ? "{$vs_table_name}_nonpreferred_label_type_list" : "{$vs_table_name}_preferred_label_type_list"));
		}
		if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $pt_instance->getFieldInfo($vs_field_name,"LIST_CODE"))) {
			$vs_prop = $this->opt_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
		} else {
			if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $pt_instance->getFieldInfo($vs_field_name,"LIST"))) {
				$vs_prop = $this->opt_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop);
			} else {
				if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_field_name === 'locale_id') && ((int)$vs_prop > 0)) {
					$t_locale = new ca_locales($vs_prop);
					$vs_prop = $t_locale->getName();
				} else {
					if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && (is_array($va_list = $pt_instance->getFieldInfo($vs_field_name,"BOUNDS_CHOICE_LIST")))) {
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
		$va_media_info = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaInfo($va_media_info[$vn_index], $ps_version, $ps_key, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaPath($ps_field, $ps_version, $pa_options=null) {
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaPath($this->get($ps_field, array("unserialize" => true)), $ps_version, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media paths attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaPaths($ps_field, $ps_version, $pa_options=null) {
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		
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
		//$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaUrl($this->get($ps_field, array("unserialize" => true)), $ps_version, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media urls attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaUrls($ps_field, $ps_version, $pa_options=null) {
		
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		
		$va_media_urls = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
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
	
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaTag($this->get($ps_field, array("unserialize" => true)), $ps_version, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media tags attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaTags($ps_field, $ps_version, $pa_options=null) {
		
		$va_media = self::get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		$va_media_tags = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
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
		
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		
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
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaVersions($this->get($ps_field, array("unserialize" => true)));
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
		return $GLOBALS["_DbResult_mediainfocoder"]->hasMedia($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function mediaIsMirrored($ps_field, $ps_version) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->mediaIsMirrored($this->get($va_field["field"], array("unserialize" => true)), $ps_version);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getMediaMirrorStatus($ps_field, $ps_version, $ps_mirror=null) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaMirrorStatus($this->get($va_field["field"], array("unserialize" => true)), $ps_version, $ps_mirror);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileInfo($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileInfo($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFilePath($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFilePath($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileUrl($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileUrl($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function hasFile($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->hasFile($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileConversions($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversions($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileConversionPath($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionPath($this->get($va_field["field"], array("unserialize" => true)), $ps_mimetype);
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	function getFileConversionUrl($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionUrl($this->get($va_field["field"], array("unserialize" => true)), $ps_mimetype);
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
			$this->opa_options[$ps_option] = $pm_value;

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
		return $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
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
				if ($t_instance = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true)) {	// table name
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
		if (($t_instance = $this->opo_datamodel->getInstanceByTableName($vs_table_name, true)) && (is_a($t_instance, "BaseLabel"))) {
			$vs_table_name = $t_instance->getSubjectTableName();
			$vs_subfield_name = $vs_field_name;
			$vs_field_name = "preferred_labels";
		}
		
		return SearchResult::$s_parsed_field_component_cache[$this->ops_table_name.'/'.$ps_path] = array(
			'table_name' 		=> $vs_table_name,
			'field_name' 		=> $vs_field_name,
			'subfield_name' 	=> $vs_subfield_name,
			'num_components'	=> sizeof($va_tmp),
			'components'		=> $va_tmp,
			'related'			=> $vb_is_related,
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
}
