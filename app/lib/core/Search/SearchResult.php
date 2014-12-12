<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchResult.php : implements interface to results from a search
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
	
	private $opa_prefetch_cache;
	private $opa_rel_prefetch_cache;
	private $opa_timestamp_cache;
	private $opa_row_ids_to_prefetch_cache;
	
	private $opo_tep; // time expression parser
	
	private $opa_cached_result_counts;
	
	# ------------------------------------------------------------------
	public function __construct($po_engine_result=null, $pa_tables=null) {
		$this->opo_db = new Db();
		$this->opo_datamodel = Datamodel::load();
		$this->opo_subject_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
		
		$this->ops_subject_pk = $this->opo_subject_instance->primaryKey();
		$this->ops_subject_idno = $this->opo_subject_instance->getProperty('ID_NUMBERING_ID_FIELD');
		$this->opb_use_identifiers_in_urls = (bool)$this->opo_subject_instance->getAppConfig()->get('use_identifiers_in_urls');
		
		$this->opa_prefetch_cache = array();
		$this->opa_rel_prefetch_cache = array();
		$this->opa_timestamp_cache = array();
		$this->opa_row_ids_to_prefetch_cache = array();
		
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
	public function init($po_engine_result, $pa_tables, $pa_options=null) {
		
		$this->opn_table_num = $this->opo_subject_instance->tableNum();
		$this->ops_table_name =  $this->opo_subject_instance->tableName();
		$this->ops_table_pk = $this->opo_subject_instance->primaryKey();
		$this->opa_cached_result_counts = array();
		
		$this->opo_engine_result = $po_engine_result;
		$this->opa_tables = $pa_tables;
		
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
	protected function getRowIDsToPrefetch($ps_tablename, $pn_start, $pn_num_rows) {
		if ($this->opa_row_ids_to_prefetch_cache[$ps_tablename.'/'.$pn_start.'/'.$pn_num_rows]) { return $this->opa_row_ids_to_prefetch_cache[$ps_tablename.'/'.$pn_start.'/'.$pn_num_rows]; }
		$va_row_ids = array();
		
		$vn_cur_row_index = $this->opo_engine_result->currentRow();
		self::seek($pn_start);
		
		$vn_i=0;
		while(self::nextHit() && ($vn_i < $pn_num_rows)) {
			$va_row_ids[] = $this->opo_engine_result->get($this->ops_table_pk);
			$vn_i++;
		}
		self::seek($vn_cur_row_index + 1);
		
		return $this->opa_row_ids_to_prefetch_cache[$ps_tablename.'/'.$pn_start.'/'.$pn_num_rows] = $va_row_ids;
	}
	# ------------------------------------------------------------------
	/**
	 * TODO: implement prefetch of related and non-indexed-stored fields. Basically, instead of doing a query for every row via get() [which will still be an option if you're lazy]
	 * prefetch() will allow you to tell SearchResult to preload values for a set of hits starting at $pn_start 
	 * Because this can be done in a single query it'll presumably be faster than lazy loading lots of rows
	 */
	public function prefetch($ps_tablename, $pn_start, $pn_num_rows, $pa_options=null) {
		if (!$ps_tablename ) { return; }
		//print "PREFETCH: ".$ps_tablename.' - '. $pn_start.' - '. $pn_num_rows."<br>";
		
		// get row_ids to fetch
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($ps_tablename, $pn_start, $pn_num_rows)) == 0) { return false; }
		
		// do join
		$va_joins = array();
		
		$t_rel_instance = $this->opo_datamodel->getInstanceByTableName($ps_tablename, true);
		if (!$t_rel_instance) { return; }
		
		if ($ps_tablename != $this->ops_table_name) {
			$va_fields = $this->opa_tables[$ps_tablename]['fieldList'];
			$va_fields[] = $this->ops_table_name.'.'.$this->ops_table_pk;
			
			// Include type_id field for item table (eg. ca_entities.type_id)
			if (method_exists($t_rel_instance, "getTypeFieldName") && ($vs_type_fld_name = $t_rel_instance->getTypeFieldName())) {
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
			
			// Include source_id field for item table (eg. ca_entities.source_id)
			if (method_exists($t_rel_instance, "getSourceFieldName") && ($vs_source_id_fld_name = $t_rel_instance->getSourceFieldName())) {
				$va_fields[] = $t_rel_instance->tableName().'.'.$vs_source_id_fld_name.' item_source_id';
			} else {
				// Include source_id field for item table (eg. ca_entities.source_id) when fetching labels
				if (method_exists($t_rel_instance, "getSubjectTableInstance")) {
					$t_label_subj_instance = $t_rel_instance->getSubjectTableInstance();
					if (method_exists($t_label_subj_instance, "getSourceFieldName") && ($vs_source_id_fld_name = $t_label_subj_instance->getSourceFieldName())) {
						$va_fields[] = $t_label_subj_instance->tableName().'.'.$vs_source_id_fld_name.' item_source_id';
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
					
					$t_link = $this->opo_datamodel->getInstanceByTableName($va_rel['many_table'], true);
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
		if(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $this->opo_subject_instance->hasField('access')) {
			$vs_criteria_sql .= " AND ({$this->ops_table_name}.access IN (".join(",", $pa_options['checkAccess']) ."))";	
		}
	
		$vb_has_locale_id = true;
		if ($this->opo_subject_instance->hasField('locale_id') && (!$t_rel_instance->hasField('locale_id'))) {
			$va_fields[] = $this->ops_table_name.'.locale_id';
			$vb_has_locale_id = true;
		}
		
		if ($t_rel_instance->hasField('idno_sort')) {
			$va_order_bys[] = $t_rel_instance->tableName().".idno_sort";
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
		//print "<pre>$vs_sql</pre>";
		$qr_rel = $this->opo_db->query($vs_sql);
		
		if (is_array($this->opa_prefetch_cache[$ps_tablename]) && (sizeof($this->opa_prefetch_cache[$ps_tablename]) > 32768)) {
			$this->opa_prefetch_cache[$ps_tablename] = array_slice($this->opa_prefetch_cache[$ps_tablename], 0, 4096);
		}
		
		$va_rel_row_ids = array();
		while($qr_rel->nextRow()) {
			$va_row = $qr_rel->getRow();
			$vn_row_id = $va_row[$this->ops_table_pk];
			if ($vb_has_locale_id) {
				$vn_locale_id = $va_row['locale_id'];
				$this->opa_prefetch_cache[$ps_tablename][$vn_row_id][$vn_locale_id][] = $va_row;
			} else {
				$this->opa_prefetch_cache[$ps_tablename][$vn_row_id][1][] = $va_row;
			}
		}
		
		// Fill row_id values for which there is nothing to prefetch with an empty lists
		// otherwise we'll try and prefetch these again later wasting time.
		foreach($va_row_ids as $vn_row_id) {
			if (!isset($this->opa_prefetch_cache[$ps_tablename][$vn_row_id])) {
				$this->opa_prefetch_cache[$ps_tablename][$vn_row_id] = array();
			}
		}
		
		//print "<pre>".print_r($this->opa_prefetch_cache[$ps_tablename], true)."</pre>";
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function prefetchRelated($ps_tablename, $pn_start, $pn_num_rows, $pa_options) {
		unset($pa_options['request']);
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($ps_tablename, $pn_start, $pn_num_rows)) == 0) { return false; }
		//print "PREFETCH RELATED (".join("; ", $va_row_ids)."): ".$ps_tablename.' - '. $pn_start.' - '. $pn_num_rows."<br>";
		$vs_md5 = caMakeCacheKeyFromOptions($pa_options);
		$va_rel_items = $this->opo_subject_instance->getRelatedItems($ps_tablename, array_merge($pa_options, array('row_ids' => $va_row_ids, 'limit' => 100000)));		// if there are more than 100,000 then we have a problem
		
		
		if (is_array($this->opa_rel_prefetch_cache[$ps_tablename]) && (sizeof($this->opa_rel_prefetch_cache[$ps_tablename]) > 32768)) {
			$this->opa_rel_prefetch_cache[$ps_tablename] = array_slice($this->opa_rel_prefetch_cache[$ps_tablename], 0, 4096);
		}
		
		foreach($va_rel_items as $vn_relation_id => $va_rel_item) {
			$this->opa_rel_prefetch_cache[$ps_tablename][$va_rel_item['row_id']][$vs_md5][] = $va_rel_item;
		}
		// Fill row_id values for which there is nothing to prefetch with an empty lists
		// otherwise we'll try and prefetch these again later wasting time.
		foreach($va_row_ids as $vn_row_id) {
			if (!isset($this->opa_rel_prefetch_cache[$ps_tablename][$vn_row_id][$vs_md5])) {
				$this->opa_rel_prefetch_cache[$ps_tablename][$vn_row_id][$vs_md5] = array();
			}
		}
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function prefetchChangeLogData($ps_tablename, $pn_start, $pn_num_rows) {
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($ps_tablename, $pn_start, $pn_num_rows)) == 0) { return false; }
		$vs_key = md5($ps_tablename.'/'.print_r($va_row_ids, true));
		if ($this->opa_timestamp_cache['fetched'][$vs_key]) { return true; }
		
		$o_log = new ApplicationChangeLog();
	
		if (is_array($this->opa_timestamp_cache[$ps_tablename]) && (sizeof($this->opa_timestamp_cache[$ps_tablename]) > 32768)) {
			$this->opa_timestamp_cache[$ps_tablename] = array_slice($this->opa_timestamp_cache[$ps_tablename], 0, 4096);
		}
	
		if (!is_array($this->opa_timestamp_cache['created_on'][$ps_tablename])) { $this->opa_timestamp_cache['created_on'][$ps_tablename] = array(); }
		$this->opa_timestamp_cache['created_on'][$ps_tablename] += $o_log->getCreatedOnTimestampsForIDs($ps_tablename, $va_row_ids);
		if (!is_array($this->opa_timestamp_cache['last_changed'][$ps_tablename])) { $this->opa_timestamp_cache['last_changed'][$ps_tablename] = array(); }
		$this->opa_timestamp_cache['last_changed'][$ps_tablename] += $o_log->getLastChangeTimestampsForIDs($ps_tablename, $va_row_ids);

		$this->opa_timestamp_cache['fetched'][$vs_key] = true;
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
	 *		delimiter = 
	 *		returnAllLocales = 
	 *		convertCodesToDisplayText = if true then item_ids are automatically converted to display text in the current locale; default is false (return item_ids raw)
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
 	 *		sort = optional array of bundles to sort returned values on. Currently only supported when getting related values via simple related <table_name> and <table_name>.related invokations. Eg. from a ca_objects results you can use the 'sort' option got get('ca_entities'), get('ca_entities.related') or get('ca_objects.related'). The bundle specifiers are fields with or without tablename. Only those fields returned for the related tables (intrinsics and label fields) are sortable. You cannot sort on attributes.
	 *		filter = optional array of elements to filter returned values on. The element must be part off the container being fetched from. For example, if you're get()'ing a value from a container element (Eg. ca_objects.dates.date_value) you can filter on any other subelement in that container by passing the name of the subelement and a value (Eg. "date_type" => "copyright"). Pass only the name of the subelement, not the full path that includes the table and container element. You can filter on multiple subelements by passing each subelement as a key in the array. Only values that match all filters are returned. You can filter on multiple values for a subelement by passing an array of values rather than a scalar (Eg. "date_type" => array("copyright", "patent")). Values that match *any* of the values will be returned. Only simple equivalance is supported. NOTE: Filters are only available when returnAsArray is set. They will be ignored if returnAsArray is not set.
	 *
	 *		maxLevelsFromTop = for hierarchical gets, restricts the number of levels returned to the top-most starting with the root.
	 *		maxLevelsFromBottom = for hierarchical gets, restricts the number of levels returned to the bottom-most starting with the lowest leaf node.
	 *		maxLevels = synonym for maxLevelsFromBottom
	 */
	public function get($ps_field, $pa_options=null) {	
		$vb_return_as_array = caGetOption('returnAsArray', $pa_options, false, array('castTo' => 'bool'));
		$va_filters = caGetOption('filters', $pa_options, array(), array('castTo' => 'array'));
		$vm_val = self::_get($ps_field, $pa_options);
		
		if ($vb_return_as_array && sizeof($va_filters)) {
			$va_tmp = explode(".", $ps_field);
			if (sizeof($va_tmp) > 1) { array_pop($va_tmp); }
			
			if (($t_instance = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true))) {
				$va_keepers = array();
				foreach($va_filters as $vs_filter => $va_filter_vals) {
					if (!is_array($va_filter_vals)) { $va_filter_vals = array($va_filter_vals); }
					
					foreach($va_filter_vals as $vn_index => $vs_filter_val) {
						// is value a list attribute idno?
						if (!is_numeric($vs_filter_val) && (($t_element = $t_instance->_getElementInstance($vs_filter)) && ($t_element->get('datatype') == 3))) {
							$va_filter_vals[$vn_index] = caGetListItemID($t_element->get('list_id'), $vs_filter_val);
						}
					}
				
					$va_filter_values = $this->get(join(".", $va_tmp).".{$vs_filter}", array('returnAsArray' => true, 'alwaysReturnItemID' => true));
			
					foreach($va_filter_values as $vn_id => $vm_filtered_val) {
						if ((!isset($va_keepers[$vn_id]) || $va_keepers[$vn_id]) && in_array($vm_filtered_val, $va_filter_vals)) {	// any match for the element counts
							$va_keepers[$vn_id] = true;
						} else {	// if no match on any criteria kill it
							$va_keepers[$vn_id] = false;
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
	 * Implementation of primary get() functionality
	 */ 
	private function _get($ps_field, $pa_options=null) {	
		if (!is_array($pa_options)) { $pa_options = array(); }
		if(isset($pa_options['restrictToType']) && (!isset($pa_options['restrict_to_type']) || !$pa_options['restrict_to_type'])) { $pa_options['restrict_to_type'] = $pa_options['restrictToType']; }
	 	if(isset($pa_options['restrictToTypes']) && (!isset($pa_options['restrict_to_types']) || !$pa_options['restrict_to_types'])) { $pa_options['restrict_to_types'] = $pa_options['restrictToTypes']; }
	 	if(isset($pa_options['restrictToRelationshipTypes']) && (!isset($pa_options['restrict_to_relationship_types']) || !$pa_options['restrict_to_relationship_types'])) { $pa_options['restrict_to_relationship_types'] = $pa_options['restrictToRelationshipTypes']; }
	 	if(isset($pa_options['excludeType']) && (!isset($pa_options['exclude_type']) || !$pa_options['exclude_type'])) { $pa_options['exclude_type'] = $pa_options['excludeType']; }
	 	if(isset($pa_options['excludeTypes']) && (!isset($pa_options['exclude_types']) || !$pa_options['exclude_types'])) { $pa_options['exclude_types'] = $pa_options['excludeTypes']; }
	 	if(isset($pa_options['excludeRelationshipTypes']) && (!isset($pa_options['exclude_relationship_types']) || !$pa_options['exclude_relationship_types'])) { $pa_options['exclude_relationship_types'] = $pa_options['excludeRelationshipTypes']; }

		$vb_return_as_array = 			caGetOption('returnAsArray', $pa_options, false, array('castTo' => 'bool'));
		$vb_return_all_locales = 		caGetOption('returnAllLocales', $pa_options, false, array('castTo' => 'bool'));
		
		$vb_return_as_link = 			caGetOption('returnAsLink', $pa_options, false, array('castTo' => 'bool'));
		$vs_return_as_link_text = 		caGetOption('returnAsLinkText', $pa_options, '');
		$vs_return_as_link_target = 	caGetOption('returnAsLinkTarget', $pa_options, '');
		$vs_return_as_link_attributes = caGetOption('returnAsLinkAttributes', $pa_options, array(), array('castTo' => 'array'));
		
		$va_original_path_components = $va_path_components = $this->getFieldPathComponents($ps_field);
		
		if ($va_path_components['table_name'] != $this->ops_table_name) {
			$vs_access_chk_key  = $va_path_components['table_name'].($va_path_components['field_name'] ? '.'.$va_path_components['field_name'] : '');
		} else {
			$vs_access_chk_key  = $va_path_components['field_name'];
		}
		
		if (caGetBundleAccessLevel($this->ops_table_name, $vs_access_chk_key) == __CA_BUNDLE_ACCESS_NONE__) {
			return null;
		}
		
		$vo_request = caGetOption('request', $pa_options, null);
		unset($pa_options['request']);
		
		// first see if the search engine can provide the field value directly (fastest)
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
		
		$vs_template = 					caGetOption('template', $pa_options, null);
		$vs_delimiter = 				caGetOption('delimiter', $pa_options, ' ');
		$vs_hierarchical_delimiter = 	caGetOption('hierarchicalDelimiter', $pa_options, ' ');
		
		if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
		if(isset($pa_options['sort']) && !is_array($pa_options['sort'])) { $pa_options['sort'] = array($pa_options['sort']); }
		if (is_array($va_sort_fields = (isset($pa_options['sort']) && is_array($pa_options['sort'])) ? $pa_options['sort'] : null)) {
			foreach($va_sort_fields as $vn_i => $vs_sort_fld) {
				if(!trim($vs_sort_fld)) { unset($va_sort_fields[$vn_i]); }
			}
		}
		$vn_row_id = $this->opo_engine_result->get($this->ops_table_pk);	
		
		
		// try to lazy load (slower)...
		
//
// Are we getting timestamp (created on or last modified) info?
//
		if (($va_path_components['table_name'] == $this->ops_table_name) && ($va_path_components['field_name'] == 'created')) {
			if (!isset($this->opa_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id])) {
				$this->prefetchChangeLogData($this->ops_table_name, $this->opo_engine_result->currentRow(), $this->getOption('prefetch'));
			}
			
			if ($vb_return_as_array) {
				return $this->opa_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id];
			} else {
				$vs_subfield = $va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : 'timestamp';
				$vm_val = $this->opa_timestamp_cache['created_on'][$this->ops_table_name][$vn_row_id][$vs_subfield];
				
				if ($vs_subfield == 'timestamp') {
					$o_tep = new TimeExpressionParser();
					$o_tep->setUnixTimestamps($vm_val, $vm_val);
					$vm_val = $o_tep->getText($pa_options);
				}
				return $vm_val;
			}
		}
		
		if (($va_path_components['table_name'] == $this->ops_table_name) && ($va_path_components['field_name'] == 'lastModified')) {
			if (!isset($this->opa_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id])) {
				$this->prefetchChangeLogData($this->ops_table_name, $this->opo_engine_result->currentRow(), $this->getOption('prefetch'));
			}
			
			if ($vb_return_as_array) {
				return $this->opa_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id];
			} else {
				$vs_subfield = $va_path_components['subfield_name'] ? $va_path_components['subfield_name'] : 'timestamp';
				$vm_val = $this->opa_timestamp_cache['last_changed'][$this->ops_table_name][$vn_row_id][$vs_subfield];
				
				if ($vs_subfield == 'timestamp') {
					$o_tep = new TimeExpressionParser();
					$o_tep->setUnixTimestamps($vm_val, $vm_val);
					$vm_val = $o_tep->getText($pa_options);
				}
				return $vm_val;
			}
		}
		
		if (!($t_instance = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true))) { return null; }	// Bad table
		$t_original_instance = $t_instance;	// $t_original_instance will always be the as-called subject; optimizations may results in $t_instance being transformed into a different model
//
// Simple related table get: 
//			<table>
//			<table>.related
//			<table>.hierarchy
//			<table>.related.hierarchy
//
		if (
			(($va_path_components['num_components'] == 1) && ($va_path_components['table_name'] !== $this->ops_table_name))
			||
			(($va_path_components['num_components'] == 2) && ($va_path_components['field_name'] == 'related'))
			||
			(($va_path_components['num_components'] == 2) && ($va_path_components['field_name'] == 'hierarchy'))
			||
			(($va_path_components['num_components'] == 3) && ($va_path_components['field_name'] == 'related') && ($va_path_components['subfield_name'] == 'hierarchy'))
		) {
			if (!($t_table = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true))) { return null; }
			
			$vb_show_hierarachy = (bool)(($va_path_components['field_name'] == 'hierarchy') && $t_instance->isHierarchical());
			
			if ($va_path_components['num_components'] == 2) {
				$va_path_components['num_components'] = 1;
				$va_path_components['field_name'] = null;
			}
			
			$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
			if (!isset($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
				$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
			}
			
			$va_related_items = $this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5];
		
			if (!is_array($va_related_items)) { return null; }
			
			if (is_array($va_sort_fields) && sizeof($va_sort_fields)) {
				$va_related_items = caSortArrayByKeyInValue($va_related_items, $va_sort_fields);
			}
	
// Return as array	
			if($vs_template) {
				return caProcessTemplateForIDs($vs_template, $this->opo_subject_instance->tableName(), array($vn_row_id), array_merge($pa_options, array('placeholderPrefix' => $va_path_components['field_name'])));
			}	
			if($vb_return_as_array || $vb_return_all_locales) {
				 if ($vb_return_all_locales) {
					$va_related_tmp = array();
					foreach($va_related_items as $vn_i => $va_related_item) {
						$va_related_tmp[$vn_i][$va_related_item['locale_id']] = $va_related_item;
					}
					return $va_related_tmp;
				 } else {
				 	if (!$vs_template && !$va_path_components['field_name']) { return $va_related_items; }
				 	$vs_pk = $t_instance->primaryKey();
				 	$va_links = array();
					foreach($va_related_items as $vn_relation_id => $va_relation_info) {
						$va_relation_info['labels'] = caExtractValuesByUserLocale(array(0 => $va_relation_info['labels']));	
						
						if ($vb_return_as_link) {
							$va_template_opts = array();
							$va_template_opts['relationshipValues'][$va_relation_info[$vs_pk]][$va_relation_info['relation_id']]['relationship_typename'] = $va_relation_info['relationship_typename'];
							$vs_text = $vs_template ? caProcessTemplateForIDs($vs_template, $t_instance->tableName(), array($va_relation_info[$vs_pk]), $va_template_opts) : join("; ", $va_relation_info['labels']);
							$va_link = caCreateLinksFromText(array($vs_text), $va_original_path_components['table_name'], array($va_relation_info[$vs_pk]), $vs_return_as_link_class, $vs_return_as_link_target);
							$va_links[$vn_relation_id] = array_pop($va_link);
						} else {
							$va_related_items[$vn_relation_id]['labels'] = $va_relation_info['labels'];
						}
					}
					
					if ($vb_return_as_link) {
						return $va_links;
					}
					return $va_related_items;
				 }
			} else {
// Return scalar
				$va_proc_labels = array();
				
				$va_row_ids = array();
				$vs_rel_pk = $t_instance->primaryKey();
				
				$va_relationship_values = array();
				foreach($va_related_items as $vn_relation_id => $va_relation_info) {
					$va_row_ids[] = $va_relation_info[$vs_rel_pk];
					$va_relationship_values[$va_relation_info[$vs_rel_pk]][$vn_relation_id] = array(
						'relationship_typename' => $va_relation_info['relationship_typename'],
						'relationship_type_id' => $va_relation_info['relationship_type_id'],
						'relationship_type_code' => $va_relation_info['relationship_type_code'],
						'relationship_typecode' => $va_relation_info['relationship_type_code'],
						'label' => $va_relation_info['label']
					);
				}
				if (!sizeof($va_row_ids)) { return ''; }
				if (!$vs_template) { $vs_template = "^label"; }
				
				$va_template_opts = $pa_options;
				unset($va_template_opts['request']);
				unset($va_template_opts['template']);
				$va_template_opts['returnAsLink'] = false;
				$va_template_opts['returnAsArray'] = true;
				
				$va_text = caProcessTemplateForIDs($vs_template, $t_instance->tableNum(), $va_row_ids, array_merge($va_template_opts, array('relationshipValues' => $va_relationship_values, 'showHierarchicalLabels' => $vb_show_hierarachy)));
							
				if ($vb_return_as_link) {
					$va_links = caCreateLinksFromText($va_text, $va_original_path_components['table_name'], $va_row_ids, $vs_return_as_link_class, $vs_return_as_link_target);
					
					return join($vs_delimiter, $va_links);
				} 
				return join($vs_delimiter, $va_text);
			}
		}
		
		$vb_need_parent = false;
		$vb_need_children = false;
		
		
//
// Transform "preferred_labels" into tables for pre-fetching
//
		$vb_is_get_for_labels = $vb_return_all_label_values = $vb_get_preferred_labels_only = $vb_get_nonpreferred_labels_only = false;
		if(in_array($va_path_components['field_name'], array('preferred_labels', 'nonpreferred_labels'))) {
			if ($t_instance->getProperty('LABEL_TABLE_NAME')) {
				
				$vb_get_preferred_labels_only = ($va_path_components['field_name'] == 'preferred_labels') ? true : false;
				$vb_get_nonpreferred_labels_only = ($va_path_components['field_name'] == 'nonpreferred_labels') ? true : false;
				
				if ($va_path_components['num_components'] == 2) {	// if it's just <table_name>.preferred_labels then return an array of fields from the label table
					$vb_return_all_label_values = true;
				}
				
				$va_path_components['table_name'] = $t_instance->getLabelTableName();
				$t_label_instance = $t_instance->getLabelTableInstance();
				if (!$va_path_components['subfield_name'] || !$t_label_instance->hasField($va_path_components['subfield_name'])) {
					$va_path_components['field_name'] = $t_instance->getLabelDisplayField();
				} else {
					$va_path_components['field_name'] = $va_path_components['subfield_name'];
				}
				$va_path_components['subfield_name'] = null;
				
				$va_path_components = $this->getFieldPathComponents($va_path_components['table_name'].'.'.$va_path_components['field_name']);
				// Ok, convert the table instance to the label table since that's the table we'll be grabbing data from
				$t_instance = $t_label_instance;
				
				$vb_is_get_for_labels = true;
			}
		}
		
//
// Handle modifiers (parent, children, related, hierarchy) with and without fields
//
		if ($va_path_components['num_components'] >= 2) {
			switch($va_path_components['field_name']) {
				case 'parent':
					if (($t_instance->isHierarchical()) && ($vn_parent_id = $this->get($va_path_components['table_name'].'.'.$t_instance->getProperty('HIERARCHY_PARENT_ID_FLD')))) {
						//
						// TODO: support some kind of prefetching of parents?
						//
						unset($va_path_components['components'][1]);
						if ($t_instance->load($vn_parent_id)) {
							return $t_instance->get(join('.', array_values($va_path_components['components'])), $pa_options);
						}
						return null;
					}
					break;
				case 'children':
					if ($t_instance->isHierarchical()) {
						//unset($va_path_components['components'][1]);	// remove 'children' from field path
						$vs_field_spec = join('.', array_values($va_path_components['components']));
						if ($vn_id = $this->get($va_path_components['table_name'].'.'.$t_instance->primaryKey(), array('returnAsArray' => false))) {
							if($t_instance->load($vn_id)) {
								return $t_instance->get($vs_field_spec, $pa_options);
							}
						}
						return null;
					} 
					break;
				case 'related':
					// Regular related table call
					if ($va_path_components['table_name'] != $this->ops_table_name) {
						// just remove "related" from name and be on our way
						$va_tmp = $va_path_components['components'];
						array_splice($va_tmp, 1, 1);
						return $this->get(join('.', $va_tmp), $pa_options);
					}
					
					// Self-relations need special handling
					$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
					if (!isset($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
						$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
					}
					
					$va_related_items = $this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5];
					if (!($t_table = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true))) { return null; }
					
					$va_ids = array();
					foreach($va_related_items as $vn_relation_id => $va_item) {
						$va_ids[] = $va_item[$t_table->primaryKey()];
					}
					$va_vals = array();
					
					if ($qr_res = $t_table->makeSearchResult($va_path_components['table_name'], $va_ids)) {
						$va_tmp = $va_path_components['components'];
						unset($va_tmp[1]);
						$vs_rel_field = join('.', $va_tmp);
						
						while($qr_res->nextHit()) {
							if ($vb_return_as_array) {
								$va_vals = array_merge($va_vals, $qr_res->get($vs_rel_field, $pa_options));
							} else {
								$va_vals[] = $qr_res->get($vs_rel_field, $pa_options);
							}
						}
					}
					
					//if (is_array($va_sort_fields) && sizeof($va_sort_fields)) {
					//	$va_vals = caSortArrayByKeyInValue($va_vals, $va_sort_fields);
					//}
					
					if ($vb_return_as_link) {
						if (!$vb_return_all_locales) {
							$va_vals = caCreateLinksFromText($va_vals, $va_original_path_components['table_name'], $va_ids, $vs_return_as_link_class, $vs_return_as_link_target);
						}
					}
	
					if ($vb_return_as_array) {
						return $va_vals;
					} else {
						return join($vs_delimiter, $va_vals);
					}
					break;
				case 'hierarchy':
					$vn_max_levels_from_bottom = caGetOption('maxLevelsFromBottom', $pa_options, caGetOption('maxLevels', $pa_options, null));
					$vn_max_levels_from_top = caGetOption('maxLevelsFromTop', $pa_options, null);
					
					if ($t_instance->isHierarchical()) {
						$vs_field_spec = join('.', array_values($va_path_components['components']));
						$vs_hier_pk_fld = $t_instance->primaryKey();
						if ($va_ids = $this->get($va_path_components['table_name'].'.'.$vs_hier_pk_fld, array_merge($pa_options, array('returnAsArray' => true, 'returnAsLink'=> false, 'returnAllLocales' => false)))) {
							$va_vals = array();
							if ($va_path_components['subfield_name'] == $vs_hier_pk_fld) {
								foreach($va_ids as $vn_id) {
									// TODO: This is too slow
									if($t_instance->load($vn_id)) {
										$va_vals = array_merge($va_vals, $t_instance->get($va_path_components['table_name'].".hierarchy.".$vs_hier_pk_fld, array_merge($pa_options, array('returnAsArray' => true))));
									}
								}
							} else {
								foreach($va_ids as $vn_id) {
									// TODO: This is too slow
									if($t_instance->load($vn_id)) {
										$va_vals = $t_instance->get($vs_field_spec, array_merge($pa_options, array('returnAsArray' => true)));
										
										// Add/replace hierarchy name
										if (($t_instance->getProperty('HIERARCHY_TYPE') == __CA_HIER_TYPE_MULTI_MONO__) &&  $t_instance->getHierarchyName()) {
											$vn_first_key = array_shift(array_keys($va_vals));
											if ($vb_return_all_locales) {
												$va_vals[$vn_first_key] = array(0 => array($t_instance->getHierarchyName()));
											} else {
												$va_vals[$vn_first_key] = $t_instance->getHierarchyName();
											}
										}
										
										if ($vn_max_levels_from_bottom > 0) {
											if (($vn_start = sizeof($va_vals) - $vn_max_levels_from_bottom) < 0) { $vn_start = 0; }
											$va_vals = array_slice($va_vals, $vn_start, $vn_max_levels_from_bottom, true);
										} elseif($vn_max_levels_from_top > 0) {
											$va_vals = array_slice($va_vals, 0, $vn_max_levels_from_top, true);
										}
									}
								}
							}
							
							if ($vb_return_as_array) {
								return $va_vals;
							} else {
								return join($vs_hierarchical_delimiter, $va_vals);
							}
						}
						return null;
					} 
					break;
			}
		}

		// If the requested table was not added to the query via SearchEngine::addTable()
		// then auto-add it here. It's better to explicitly add it with addTables() as that call
		// gives you precise control over which fields are autoloaded and also lets you specify limiting criteria 
		// for selection of related field data; and it also lets you explicitly define the tables used to join the
		// related table. Autoloading guesses and usually does what you want, but not always.
		if (!isset($this->opa_tables[$va_path_components['table_name']]) || !$this->opa_tables[$va_path_components['table_name']]) {
			$va_join_tables = $this->opo_datamodel->getPath($this->ops_table_name, $va_path_components['table_name']);
			array_shift($va_join_tables); 	// remove subject table
			array_pop($va_join_tables);		// remove content table (we only need linking tables here)
			
			$va_join_criteria = array();
			if(is_array($va_primary_ids)) {
				foreach($va_primary_ids as $vs_t => $va_t_ids) {
					if (isset($va_join_tables[$vs_t]) && (sizeof($va_t_ids) > 0)) {
						$vs_t_pk = $this->opo_datamodel->getTablePrimaryKeyName($vs_t);
						$va_join_criteria[] = "{$vs_t}.{$vs_t_pk} NOT IN (".join(",",$va_t_ids).")";
					}
				}
			}
			
			$this->opa_tables[$va_path_components['table_name']] = array(
				'fieldList' => array($va_path_components['table_name'].'.*'),
				'joinTables' => array_keys($va_join_tables),
				'criteria' => $va_join_criteria
			);
			
		}
		
		
		if (($va_path_components['table_name'] === $this->ops_table_name) && !$t_instance->hasField($va_path_components['field_name']) && method_exists($t_instance, 'getAttributes')) {
			
//
// Return attribute values for primary table 
//
			
			if ($va_path_components['field_name'] && ($t_element = $t_instance->_getElementInstance($va_path_components['field_name']))) {
				$vn_element_id = $t_element->getPrimaryKey();
			} else {
				$vn_element_id = null;
			}
			if (!isset(ca_attributes::$s_get_attributes_cache[$this->opn_table_num.'/'.$vn_row_id][$vn_element_id])) {
				ca_attributes::prefetchAttributes($this->opo_db, $this->opn_table_num, $this->getRowIDsToPrefetch($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch')), ($vn_element_id ? array($vn_element_id) : null), array('dontFetchAlreadyCachedValues' => true));
			}
			
			if (!$vb_return_as_array && !$vb_return_all_locales) {
// return scalar

				//
				// Handle "hierarchy" modifier on list elements
				//
				if ($va_hier = $this->_getElementHierarchy($t_instance, $va_path_components)) {
					return join($vs_hierarchical_delimiter, $va_hier);
				}

				if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($va_path_components['field_name'])) {
					$vs_template = null;
					if ($va_path_components['subfield_name']) { 
						$va_values = $t_instance->getAttributeDisplayValues($va_path_components['field_name'], $vn_row_id, $pa_options);
						$va_value_list = array();
						foreach($va_values as $vn_id => $va_attr_val_list) {
							foreach($va_attr_val_list as $vn_value_id => $va_value_array) {
								$va_value_list[] = $va_value_array[$va_path_components['subfield_name']];
							}
						}
						return join(" ", $va_value_list);
					} else {
						if (isset($pa_options['template'])) { $vs_template = $pa_options['template']; }
					}
					unset($pa_options['template']);
					if (!$vs_template)  { $vs_template = "^".($va_path_components['subfield_name']) ? $va_path_components['subfield_name'] : $va_path_components['field_name']; }

					return $t_instance->getAttributesForDisplay($va_path_components['field_name'], $vs_template, array_merge(array('row_id' => $vn_row_id), $pa_options));
				}
		
				if ($t_element && !$va_path_components['subfield_name'] && ($t_element->get('datatype') == 0)) {
					return $t_instance->getAttributesForDisplay($va_path_components['field_name'], $vs_template, array_merge($pa_options, array('row_id' => $vn_row_id)));
				} else {
					if(!$vs_template) {
						return $t_instance->getRawValue($vn_row_id, $va_path_components['field_name'], $va_path_components['subfield_name'], ',', $pa_options);
					} else {
						return caProcessTemplateForIDs($vs_template, $va_path_components['table_name'], array($vn_row_id), array());
					}
				}
			} else {
// return array
				
				//
				// Handle "hierarchy" modifier on list elements
				//
				if ($va_hier = $this->_getElementHierarchy($t_instance, $va_path_components)) {
					return $va_hier;
				}
				
				$va_values = $t_instance->getAttributeDisplayValues($va_path_components['field_name'], $vn_row_id, $pa_options);
				
				if ($vs_template && !$vb_return_all_locales) {
					$va_values_tmp = array();
					foreach($va_values as $vn_i => $va_value_list) {
						foreach($va_value_list as $vn_attr_id => $va_attr_data) {
							$va_values_tmp[] = caProcessTemplateForIDs($vs_template, $va_path_components['table_name'], array($vn_row_id), array_merge($pa_options, array('placeholderPrefix' => $va_path_components['field_name'])));
						}
					}
					
					$va_values = $va_values_tmp;
				} else {
					if ($va_path_components['subfield_name']) {
						if ($vb_return_all_locales) {
							foreach($va_values as $vn_row_id => $va_values_by_locale) {
								foreach($va_values_by_locale as $vn_locale_id => $va_value_list) {
									foreach($va_value_list as $vn_attr_id => $va_attr_data) {
										$va_values[$vn_row_id][$vn_locale_id][$vn_attr_id] = $va_attr_data[$va_path_components['subfield_name']];
									}
								}
							}
						} else {
							$va_processed_value_list = array();
							foreach($va_values as $vn_row_id => $va_value_list) {
								foreach($va_value_list as $vn_attr_id => $va_attr_data) {
									$va_processed_value_list[$vn_attr_id] = $va_attr_data[$va_path_components['subfield_name']];
								}
							}
							$va_values = $va_processed_value_list;
						}
					} else {
						if (!$vb_return_all_locales) {
							$va_values = array_shift($va_values);
						}
					}
				}
				return $va_values;
			}
		} else {
			// Prefetch intrinsic fields in primary and related tables
			if (!isset($this->opa_prefetch_cache[$va_path_components['table_name']][$vn_row_id])) {
				$this->prefetch($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);	// try to prefetch ahead (usually doesn't hurt and very often helps performance)
			}
		}
		
		
		$va_return_values = array();
		if (($va_path_components['table_name'] !== $this->ops_table_name) && ($va_path_components['field_name'] !== 'relationship_typename') && !$t_instance->hasField($va_path_components['field_name']) && method_exists($t_instance, 'getAttributes')) {
//
// Return metadata attributes in a related table
//
			
			$vs_pk = $t_instance->primaryKey();
			$vb_is_related = ($this->ops_table_name !== $va_path_components['table_name']);
			$va_ids = array();
			
			$vs_opt_md5 = caMakeCacheKeyFromOptions($pa_options);
			if (!isset($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
				$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
			}
			
			if (is_array($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
				foreach($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5] as $vn_i => $va_values) { //$vn_locale_id => $va_values_by_locale) {
					$va_ids[] = $va_values[$vs_pk];
					
					if (!$vb_return_as_array) {
						$vs_val = $t_instance->getAttributesForDisplay($va_path_components['field_name'], $vs_template, array_merge(array('row_id' => $va_values[$vs_pk]), $pa_options));
					} else {
						$vs_val = $t_instance->getAttributeDisplayValues($va_path_components['field_name'], $va_values[$vs_pk], $pa_options);
					}
					if ($vs_val) {
						if ($vb_return_as_array) {
							if (!$vb_return_all_locales) {
								foreach($vs_val as $vn_i => $va_values_list) {
									foreach($va_values_list as $vn_j => $va_values) {
										$va_return_values[] = $va_values;
									}
								}
							} else {
								foreach($vs_val as $vn_i => $va_values_list) {
									$va_return_values[] = $va_values_list;
								}
							}
						} else {
							$va_return_values[] = $vs_val;
						}
					}
				}
			}
			 
			if ($vb_return_as_array || $vb_return_all_locales) {
// return array
				if ($vb_return_as_link && $vb_is_related) {
					$vs_table_name = $t_instance->tableName();
					$vs_fld_key = ($va_path_components['subfield_name']) ? $va_path_components['subfield_name'] : $va_path_components['field_name'];
					if (!$vb_return_all_locales) {
						$va_return_values_tmp = array();
						foreach($va_return_values as $vn_i => $va_value) {
							if ($vs_template) {
								$vs_value = caProcessTemplateForIDs($vs_template, $va_path_components['table_name'], array($va_ids[$vn_i][$vs_pk]), array('returnAsArray' => false));
							} else {
								$vs_value = $va_value[$vs_fld_key];
							}
							
							if ($vb_return_as_link) {
								$va_return_values_tmp[$vn_i] = array_pop(caCreateLinksFromText(array($vs_value), $va_original_path_components['table_name'], array($va_ids[$vn_i]), $vs_return_as_link_class, $vs_return_as_link_target));
							} else {
								$va_return_values_tmp[$vn_i] = $vs_value;
							}
						}
						$va_return_values = $va_return_values_tmp;
					}
				}
				return $va_return_values;
			} else {
// return scalar
				if ($vb_return_as_link && $vb_is_related) {
					$va_return_values = caCreateLinksFromText($va_return_values, $va_original_path_components['table_name'], $va_ids, $vs_return_as_link_class, $vs_return_as_link_target);
				}
				if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
					return caConvertLineBreaks(join($vs_delimiter, $va_return_values));
				} else {
					return join($vs_delimiter, $va_return_values);
				}
			}
		} else {

			if ($vs_template) {
				return caProcessTemplateForIDs($vs_template, $this->opo_subject_instance->tableName(), array($vn_row_id), array_merge($pa_options, array('placeholderPrefix' => $va_path_components['field_name'])));
			}	
//
// Return fields (intrinsics, labels) in primary or related table
//
			$t_list = $this->opo_datamodel->getInstanceByTableName('ca_lists', true);
			$va_value_list = array($vn_row_id => $this->opa_prefetch_cache[$va_path_components['table_name']][$vn_row_id]);

			// Restrict to relationship types (related)
			if (isset($pa_options['restrict_to_relationship_types']) && $pa_options['restrict_to_relationship_types']) {
				if (!is_array($pa_options['restrict_to_relationship_types'])) {
					$pa_options['restrict_to_relationship_types'] = array($pa_options['restrict_to_relationship_types']);
				}
				if (sizeof($pa_options['restrict_to_relationship_types'])) {
					$t_rel_type = $this->opo_datamodel->getInstanceByTableName('ca_relationship_types', true);
					$va_rel_types = array();
					$va_rel_path = array_keys($this->opo_datamodel->getPath($this->ops_table_name,  $va_path_components['table_name']));
					foreach($pa_options['restrict_to_relationship_types'] as $vm_type) {
						if (!$vm_type) { continue; }
						if ($vn_type_id = $t_rel_type->getRelationshipTypeID($va_rel_path[1], $vm_type)) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel_type->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}
					if (sizeof($va_rel_types)) {
						$va_tmp = array();
						foreach($va_value_list as $vn_id => $va_by_locale) {
							foreach($va_by_locale as $vn_locale_id => $va_values) {
								foreach($va_values as $vn_i => $va_value) {
									if (!$va_value['rel_type_id'] || in_array($va_value['rel_type_id'], $va_rel_types)) {
										$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
									}
								}
							}
						}
						$va_value_list = $va_tmp;
					}
				}
			}
			
			// Exclude relationship types (related)
			if (isset($pa_options['exclude_relationship_types']) && $pa_options['exclude_relationship_types']) {
				if (!is_array($pa_options['exclude_relationship_types'])) {
					$pa_options['exclude_relationship_types'] = array($pa_options['exclude_relationship_types']);
				}
				
				if (sizeof($pa_options['exclude_relationship_types'])) {
					$t_rel_type = $this->opo_datamodel->getInstanceByTableName('ca_relationship_types', true);
					$va_rel_types = array();
					$va_rel_path = array_keys($this->opo_datamodel->getPath($this->ops_table_name,  $va_path_components['table_name']));
					foreach($pa_options['exclude_relationship_types'] as $vm_type) {
						if ($vn_type_id = $t_rel_type->getRelationshipTypeID($va_rel_path[1], $vm_type)) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel_type->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}
					if (sizeof($va_rel_types)) {
						$va_tmp = array();
						foreach($va_value_list as $vn_id => $va_by_locale) {
							foreach($va_by_locale as $vn_locale_id => $va_values) {
								foreach($va_values as $vn_i => $va_value) {
									if (!in_array($va_value['rel_type_id'], $va_rel_types)) {
										$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
									}
								}
							}
						}
						$va_value_list = $va_tmp;
					}
				}
			}
			
			// Restrict to types (related)
			$va_type_ids = $vs_type_fld = null;
			if (method_exists($t_instance, "getTypeFieldName")) {
				$va_type_ids = caMergeTypeRestrictionLists($t_instance, $pa_options);
				$vs_type_fld = $t_instance->getTypeFieldName();
			} else {
				if (method_exists($t_instance, "getSubjectTableInstance")) {
					$t_label_subj_instance = $t_instance->getSubjectTableInstance();
					if (method_exists($t_label_subj_instance, "getTypeFieldName")) {
						$va_type_ids = caMergeTypeRestrictionLists($t_label_subj_instance, $pa_options);
						$vs_type_fld = 'item_type_id';
					}
				}
			}
			
			if (is_array($va_type_ids) && sizeof($va_type_ids)) {
				$va_tmp = array(); 
				foreach($va_value_list as $vn_id => $va_by_locale) {
					foreach($va_by_locale as $vn_locale_id => $va_values) {
						foreach($va_values as $vn_i => $va_value) {
							if (!$va_value[$vs_type_fld ? $vs_type_fld : 'item_type_id'] || in_array($va_value[$vs_type_fld ? $vs_type_fld : 'item_type_id'], $va_type_ids)) {
								$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
							}
						}
					}
				}
				$va_value_list = $va_tmp;
			}
			
			
			// Restrict to sources (related)
			$va_source_ids = $vs_source_id_fld = null;
			if (method_exists($t_instance, "getSourceFieldName")) {
				$va_source_ids = caMergeSourceRestrictionLists($t_instance, $pa_options);
				$vs_source_id_fld = $t_instance->getSourceFieldName();
			} else {
				if (method_exists($t_instance, "getSubjectTableInstance")) {
					$t_label_subj_instance = $t_instance->getSubjectTableInstance();
					if (method_exists($t_label_subj_instance, "getSourceFieldName")) {
						$va_source_ids = caMergeSourceRestrictionLists($t_label_subj_instance, $pa_options);
						$vs_source_id_fld = 'item_source_id';
					}
				}
			}
			
			if (is_array($va_source_ids) && sizeof($va_source_ids)) {
				$va_tmp = array(); 
				foreach($va_value_list as $vn_id => $va_by_locale) {
					foreach($va_by_locale as $vn_locale_id => $va_values) {
						foreach($va_values as $vn_i => $va_value) {
							if (!$va_value[$vs_source_id_fld ? $vs_source_id_fld : 'item_source_id'] || in_array($va_value[$vs_source_id_fld ? $vs_source_id_fld : 'item_source_id'], $va_source_ids)) {
								$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
							}
						}
					}
				}
				$va_value_list = $va_tmp;
			}
			
			// Exclude types (related)
			if (isset($pa_options['exclude_type']) && $pa_options['exclude_type']) {
				if (!isset($pa_options['exclude_types']) || !is_array($pa_options['exclude_types'])) {
					$pa_options['exclude_types'] = array();
				}
				$pa_options['exclude_types'][] = $pa_options['exclude_type'];
			}
			if (isset($pa_options['exclude_types']) && is_array($pa_options['exclude_types'])) {
				$va_ids = caMakeTypeIDList($va_path_components['table_name'], $pa_options['exclude_types']);
				
				if (is_array($va_ids) && (sizeof($va_ids) > 0)) {					
					$va_tmp = array(); 
					foreach($va_value_list as $vn_id => $va_by_locale) {
						foreach($va_by_locale as $vn_locale_id => $va_values) {
							foreach($va_values as $vn_i => $va_value) {
								if (!in_array($va_value[$vs_type_fld ? $vs_type_fld : 'item_type_id'], $va_type_ids)) {
									$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
								}
							}
						}
					}
					$va_value_list = $va_tmp;
				}
			}
			
			// Handle 'relationship_typename' (related)
			$vb_get_relationship_typename = false;
			if ($va_path_components['field_name'] == 'relationship_typename') {
				$va_path_components['field_name'] = 'rel_type_id';
				$vb_get_relationship_typename = true;
			}
	
			if ($vb_return_as_array) {
// return array (intrinsics or labels in primary or related table)
				if ($t_instance->hasField($va_path_components['field_name']) && ($va_path_components['table_name'] === $t_instance->tableName())) {
					// Intrinsic
					$va_field_info = $t_instance->getFieldInfo($va_path_components['field_name']);
					$vs_pk = $t_original_instance->primaryKey();
					// Handle specific intrinsic types
					switch($va_field_info['FIELD_TYPE']) {
						case FT_DATERANGE:
						case FT_HISTORIC_DATERANGE:
							foreach($va_value_list as $vn_id => $va_values_by_locale) {
								foreach($va_values_by_locale as $vn_locale_id => $va_values) {
									foreach($va_values as $vn_i => $va_value) {
										$va_ids[] = $va_value[$vs_pk];
					
										if(caGetOption('getDirectDate', $pa_options, false)) {
											if(caGetOption('sortable', $pa_options, false)) {
												$vs_prop = $va_value[$va_field_info['START']].'/'.$va_value[$va_field_info['END']];
											} else {
												$vs_prop = $va_value[$va_field_info['START']];
											}
										} else {
											$this->opo_tep->init();
											if ($va_field_info['FIELD_TYPE'] == FT_DATERANGE) {
												$this->opo_tep->setUnixTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
											} else {
												$this->opo_tep->setHistoricTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
											}
											$vs_prop = $this->opo_tep->getText($pa_options);
										}
										if ($vb_return_all_locales) {
											$va_return_values[$vn_row_id][$vn_locale_id][] = $vs_prop;
										} else {
											$va_return_values[] = $vs_prop;
										}
									}
								}
							}
							break;
						case FT_MEDIA:
							if(!$vs_version = $va_path_components['subfield_name']) {
								$vs_version = "largeicon";
							}
							foreach($va_value_list as $vn_id => $va_values_by_locale) {
								foreach($va_values_by_locale as $vn_locale_id => $va_values) {
									foreach($va_values as $vn_i => $va_value) {
										$va_ids[] = $va_value[$vs_pk];
					
										if (isset($pa_options['unserialize']) && $pa_options['unserialize']) {
											$vs_prop = caUnserializeForDatabase($va_value[$va_path_components['field_name']]);
											if ($vb_return_all_locales) {
												$va_return_values[$vn_row_id][$vn_locale_id][] = $vs_prop;
											} else {
												$va_return_values[] = $vs_prop;
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
								
											if ($vb_return_all_locales) {
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
							}
							break;
						default:
							// is intrinsic field in primary table
							$vb_supports_preferred = (bool)$t_instance->hasField('is_preferred');
							foreach($va_value_list as $vn_id => $va_values_by_locale) {
								foreach($va_values_by_locale as $vn_locale_id => $va_values) {
									foreach($va_values as $vn_i => $va_value) {
										$va_ids[] = $vn_id = $va_value[$vs_pk];
					
										if (($vb_get_preferred_labels_only) && ($vb_supports_preferred) && (!$va_value['is_preferred'])) { continue; }
										if (($vb_get_nonpreferred_labels_only) && ($vb_supports_preferred) && ($va_value['is_preferred'])) { continue; }
										
										$vs_prop = $va_value[$va_path_components['field_name']];
										if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST_CODE"))) {
											$vs_prop = $t_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
										} else {
											if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST"))) {
												$vs_prop = $t_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop);
											} else {
												if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($va_path_components['field_name'] === 'locale_id') && ((int)$vs_prop > 0)) {
													$t_locale = new ca_locales($vs_prop);
													$vs_prop = $t_locale->getName();
												} else {
													if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && (is_array($va_list = $t_instance->getFieldInfo($va_path_components['field_name'],"BOUNDS_CHOICE_LIST")))) {
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
										
										if ($vb_return_all_locales) {
											$va_return_values[$vn_id][$vn_locale_id][] = $vs_prop;
										} else {
											$va_return_values[$vn_id][$vn_locale_id] = $vs_prop;
										}
									}
								}
							}
							
							if (!$vb_return_all_locales) {
								$va_return_values = array_values(caExtractValuesByUserLocale($va_return_values));
							}
							break;
					}
				} else {
					// Attributes
					$vs_pk = $t_original_instance->primaryKey();
					$vb_is_related = ($this->ops_table_name !== $va_path_components['table_name']);
					$va_ids = array();
					
					$t_instance = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
								
					foreach($va_value_list as $vn_i => $va_values_by_locale) {
						foreach($va_values_by_locale as $vn_locale_id => $va_values) {
							foreach($va_values as $vn_i => $va_value) {
								if ($vb_is_related) {
									$va_ids[] = $va_value[$vs_pk];
								}
								if (($vb_get_preferred_labels_only) && (!$va_value['is_preferred'])) { continue; }
								if (($vb_get_nonpreferred_labels_only) && ($va_value['is_preferred'])) { continue; }
								
								// do we need to translate foreign key and choice list codes to display text?
								$vs_prop = ($vb_return_all_label_values && !$vb_return_as_link) ? $va_value : $va_value[$va_path_components['field_name']];
								
								if ($vb_get_relationship_typename) {
									if (!$t_rel_type) { $t_rel_type = $this->opo_datamodel->getInstanceByTableName('ca_relationship_types', true); }
									if (is_array($va_labels = $t_rel_type->getDisplayLabels(false, array('row_id' => (int)$vs_prop)))) {
										$va_label = array_shift($va_labels);
										$vs_prop = $va_label[0]['typename'];
									} else {
										$vs_prop = "?";
									}
								} else {
									// Decode list items to text
									if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST_CODE"))) {
										$vs_prop = $t_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
									} else {
										if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST"))) {
											$vs_prop = $t_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop);
										} else {
											if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($va_path_components['field_name'] === 'locale_id') && ((int)$vs_prop > 0)) {
												$t_locale = new ca_locales($vs_prop);
												$vs_prop = $t_locale->getName();
											} else {
												if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && (is_array($va_list = $t_instance->getFieldInfo($va_path_components['field_name'],"BOUNDS_CHOICE_LIST")))) {
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
								}
		
								if ($vb_return_all_locales) {
									$va_return_values[$vn_row_id][$vn_locale_id][] = $vs_prop;
								} else {
									if ($vb_get_nonpreferred_labels_only && is_array($vs_prop)) {	// non-preferred labels are lists of lists because they can repeat
										$va_return_values[][] = $vs_prop;
									} else {
										$va_return_values[] = $vs_prop;
									}
								}
							}
						}
					}
				}
				if ($vb_return_as_link) {
					if (!$vb_return_all_locales) {
						$va_return_values = caCreateLinksFromText($va_return_values, $va_original_path_components['table_name'], $va_ids, $vs_return_as_link_class, $vs_return_as_link_target);
					}
				}
				return $va_return_values;
			} else {
// Return scalar (intrinsics or labels in primary or related table)
				if ($vb_get_preferred_labels_only || $vb_get_nonpreferred_labels_only) {
					// We have to distinguish between preferred and non-preferred labels here
					// so that only appropriate labels are passed for output.
					$va_filtered_values = array();
					foreach($va_value_list as $vn_label_id => $va_labels_by_locale) {
						foreach($va_labels_by_locale as $vn_locale_id => $va_labels) {
							foreach($va_labels as $vn_i => $va_label) {
								if (	
									($vb_get_preferred_labels_only && ((!isset($va_label['is_preferred']) || $va_label['is_preferred'])))
									||
									($vb_get_nonpreferred_labels_only && !$va_label['is_preferred'])
								) {
									$va_filtered_values[$vn_label_id][$vn_locale_id][] = $va_label;
								}
							}
						}
					}
					$va_value_list = $va_filtered_values;
				}
				$va_value_list = caExtractValuesByUserLocale($va_value_list);
				
				// do we need to translate foreign key and choice list codes to display text?
				$t_instance = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
				$va_field_info = $t_instance->getFieldInfo($va_path_components['field_name']);
				
				$vs_pk = $t_instance->primaryKey();
				$vb_is_related = ($this->ops_table_name !== $va_path_components['table_name']);
				$va_ids = array();
				foreach($va_value_list as $vn_i => $va_values) {
					if (!is_array($va_values)) { continue; }
					
					// Handle specific intrinsic types
					$vs_template_value = $vs_template;
					foreach($va_values as $vn_j => $va_value) {
						switch($va_field_info['FIELD_TYPE']) {
							case FT_BIT:
								if ($pa_options['convertCodesToDisplayText']) {
									$va_value[$va_path_components['field_name']] = (bool)$vs_prop ? _t('yes') : _t('no'); 
								}
								break;
							case FT_DATERANGE:
								if(caGetOption('getDirectDate', $pa_options, false)) {
									if(caGetOption('sortable', $pa_options, false)) {
										$va_value[$va_path_components['field_name']] = $va_value[$va_field_info['START']].'/'.$va_value[$va_field_info['END']];
									} else {
										$va_value[$va_path_components['field_name']] = $va_value[$va_field_info['START']];
									}
								} else {
									$this->opo_tep->init();
									$this->opo_tep->setUnixTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
									$va_value[$va_path_components['field_name']] = $this->opo_tep->getText($pa_options);
								}
								break;
							case FT_HISTORIC_DATERANGE:
								if(caGetOption('getDirectDate', $pa_options, false)) {
									if(caGetOption('sortable', $pa_options, false)) {
										$va_value[$va_path_components['field_name']] = $va_value[$va_field_info['START']].'/'.$va_value[$va_field_info['END']];
									} else {
										$va_value[$va_path_components['field_name']] = $va_value[$va_field_info['START']];
									}
								} else {
									$this->opo_tep->init();
									$this->opo_tep->setHistoricTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
									$va_value[$va_path_components['field_name']] = $this->opo_tep->getText($pa_options);
								}
								break;
							case FT_MEDIA:
							
								if(!$vs_version = $va_path_components['subfield_name']) {
									$vs_version = "largeicon";
								}
								
								// See if an info element was passed, eg. ca_object_representations.media.icon.width should return the width of the media rather than a tag or url to the media
								$vs_info_element = ($va_path_components['num_components'] == 4) ? $va_path_components['components'][3] : null;
								
								if (isset($pa_options['unserialize']) && $pa_options['unserialize']) {
									return caUnserializeForDatabase($va_value[$va_path_components['field_name']]);
								} else {
									$o_media_settings = new MediaProcessingSettings($va_path_components['table_name'], $va_path_components['field_name']);
									$va_versions = $o_media_settings->getMediaTypeVersions('*');
								
									if (!isset($va_versions[$vs_version])) {
										$va_tmp = array_keys($va_versions);
										$vs_version = array_shift($va_tmp);
									}
									
									if ($vs_info_element) {
										// Return media info
										$va_value[$va_path_components['field_name']] = $this->getMediaInfo($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $vs_info_element, $pa_options);
									} elseif (isset($pa_options['returnURL']) && ($pa_options['returnURL'])) {
										$va_value[$va_path_components['field_name']] = $this->getMediaUrl($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
									} else {
										$va_value[$va_path_components['field_name']] = $this->getMediaTag($va_path_components['table_name'].'.'.$va_path_components['field_name'], $vs_version, $pa_options);
									}
								}
								break;
							default:
								// noop
								break;
						}
						
						$vs_prop = $va_value[$va_path_components['field_name']];
		
						// Decode list items to text
						if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST_CODE"))) {
							$va_value[$va_path_components['field_name']] = $t_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
						} else {
							if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST"))) {
								$va_value[$va_path_components['field_name']] = $t_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop);
							} else {
								if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($va_path_components['field_name'] === 'locale_id') && ((int)$vs_prop > 0)) {
									$t_locale = new ca_locales($vs_prop);
									$va_value[$va_path_components['field_name']] = $t_locale->getName();
								} else {
									if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && (is_array($va_list = $t_instance->getFieldInfo($va_path_components['field_name'],"BOUNDS_CHOICE_LIST")))) {
										foreach($va_list as $vs_option => $vs_value) {
											if ($vs_value == $vs_prop) {
												$va_value[$va_path_components['field_name']] = $vs_option;
												break;
											}
										}
									}
								}
							}
						}
						
						$vs_pk = $this->opo_datamodel->getTablePrimaryKeyName($va_original_path_components['table_name']);
						if ($vs_template) {
							foreach($va_value_list as $vn_id => $va_values) {
								foreach($va_values as $vn_i => $va_value) {
									
									$vs_prop = caProcessTemplateForIDs($vs_template, $va_original_path_components['table_name'], array($va_value[$vs_pk]), array('returnAsArray' => false));
									$va_return_values[] = $vs_prop;
									$va_ids[] = $va_value[$vs_pk];
								}
							}
						} else {
							$vs_prop = $va_value[$va_path_components['field_name']];
							$va_return_values[] = $vs_prop;
							if ($vb_is_related) {
								$va_ids[] = $va_value[$vs_pk];
							}
						}
					}
				}
				
				if ($vb_return_as_link && $vb_is_related) {
					$va_return_values = caCreateLinksFromText($va_return_values, $va_original_path_components['table_name'], $va_ids, $vs_return_as_link_class, $vs_return_as_link_target);
				}
				
				if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
					return caConvertLineBreaks(join($vs_delimiter, $va_return_values));
				} else {
					return join($vs_delimiter, $va_return_values);
				}
			}
		}

		return null;
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
		$pn_end = caGetOption('end', $pa_options, $this->numHits());
		
		$this->seek($pn_start);
		$vn_c = 0;
		
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
	  *
	  */
	private function getFieldPathComponents($ps_path) {
		$va_tmp = explode('.', $ps_path);
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
		
		return array(
			'table_name' 		=> $vs_table_name,
			'field_name' 		=> $vs_field_name,
			'subfield_name' 	=> $vs_subfield_name,
			'num_components'	=> sizeof($va_tmp),
			'components'		=> $va_tmp
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
