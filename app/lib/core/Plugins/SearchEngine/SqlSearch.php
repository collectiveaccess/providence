<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/SqlSearch.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2016 Whirl-i-Gig
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

 require_once(__CA_LIB_DIR__.'/core/Db.php');
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/WLPlug.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/IWLPlugSearchEngine.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/SqlSearchResult.php'); 
 require_once(__CA_LIB_DIR__.'/core/Search/Common/Stemmer/SnoballStemmer.php');
 require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
 require_once(__CA_APP_DIR__.'/helpers/gisHelpers.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/BaseSearchPlugin.php');

class WLPlugSearchEngineSqlSearch extends BaseSearchPlugin implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	private $opn_indexing_subject_tablenum=null;
	private $opn_indexing_subject_row_id=null;
	
	private $opa_doc_content_buffer = array();			// content buffer used when indexing
	
	private $ops_delete_sql;	// sql DELETE statement (for unindexing)
	private $opqr_delete;		// prepared statement for delete (subject_tablenum and subject_row_id only specified)
	
	private $opo_stemmer;		// snoball stemmer
	private $opb_do_stemming = true;
	
	private $opo_tep;			// date/time expression parse
	
	static $s_word_cache = array();						// cached word-to-word_id values used when indexing
	static $s_metadata_elements; 						// cached metadata element info
	static $s_fieldnum_cache = array();				// cached field name-to-number values used when indexing
	
	private $ops_insert_word_index_sql = '';
	private $opqr_lookup_word = null;
	private $ops_insert_word_sql = '';
	private $ops_insert_ngram_sql = '';


	private $ops_delete_with_field_num_sql = "";
	private $opqr_delete_with_field_num = null;

	private $ops_delete_with_field_row_id_sql = '';
	private $opqr_delete_with_field_row_id = null;

	private $ops_delete_with_field_row_id_and_num = "";
	private $opqr_delete_with_field_row_id_and_num = null;

	private $ops_delete_dependent_sql = "";
	private $opqr_delete_dependent_sql = null;
	
	# -------------------------------------------------------
	public function __construct($po_db=null) {
		parent::__construct($po_db);
		
		$this->opo_tep = new TimeExpressionParser();
		
		
		$this->opo_stemmer = new SnoballStemmer();
		$this->opb_do_stemming = (int)trim($this->opo_search_config->get('search_sql_search_do_stemming')) ? true : false;
		
		$this->initDbStatements();
		
		if (!($this->ops_indexing_tokenizer_regex = trim($this->opo_search_config->get('indexing_tokenizer_regex')))) {
			$this-> ops_indexing_tokenizer_regex = "^\pL\pN\pNd/_#\@\&\.";
		}
		if (!($this->ops_search_tokenizer_regex = trim($this->opo_search_config->get('search_tokenizer_regex')))) {
			$this->ops_search_tokenizer_regex = "^\pL\pN\pNd/_#\@\&";
		}
		
		if (!is_array($this->opa_asis_regexes = $this->opo_search_config->getList('asis_regexes'))) {
			$this->opa_asis_regexes = array();
		}
		
		//
		// Load info about metadata elements into static var cache if it hasn't already be fetched
		//
		if (!is_array(WLPlugSearchEngineSqlSearch::$s_metadata_elements)) {
			WLPlugSearchEngineSqlSearch::$s_metadata_elements = ca_metadata_elements::getRootElementsAsList();
		}
		$this->debug = false;
	}
	# -------------------------------------------------------
	# Initialization and capabilities
	# -------------------------------------------------------
	public function init() {
		if(($vn_max_indexing_buffer_size = (int)$this->opo_search_config->get('max_indexing_buffer_size')) < 1) {
			$vn_max_indexing_buffer_size = 5000;
		}
		
		$this->opa_options = array(
				'limit' => 2000,											// maximum number of hits to return [default=2000]  ** NOT CURRENTLY ENFORCED -- MAY BE DROPPED **
				'maxIndexingBufferSize' => $vn_max_indexing_buffer_size,	// maximum number of indexed content items to accumulate before writing to the database
				'maxWordIndexInsertSegmentSize' => ceil($vn_max_indexing_buffer_size / 2), // maximum number of word index rows to put into a single insert
				'maxWordCacheSize' => 131072,								// maximum number of words to cache while indexing before purging
				'cacheCleanFactor' => 0.50,									// percentage of words retained when cleaning the cache
				
				'omitPrivateIndexing' => false,								//
				'excludeFieldsFromSearch' => null,
				'restrictSearchToFields' => null,
				'strictPhraseSearching' => true							// strict phrase searching finds only records with the precise phrase; non-strict will find fields with all of the words, in any order
		);
		
		// Defines specific capabilities of this engine and plug-in
		// The indexer and engine can use this information to optimize how they call the plug-in
		$this->opa_capabilities = array(
			'incremental_reindexing' => true,		// can update indexing using only changed fields, rather than having to reindex the entire row (and related stuff) every time
			'restrict_to_fields' => true
		);
		
		if (defined('__CA_SEARCH_IS_FOR_PUBLIC_DISPLAY__')) {
			$this->setOption('omitPrivateIndexing', true); 
		}
	}
	# -------------------------------------------------------
	/**
	 * Set database connection
	 *
	 * @param Db $po_db A database connection to use in place of current one
	 */
	public function setDb($po_db) {
		parent::setDb($po_db);
		$this->initDbStatements();
	}
	# -------------------------------------------------------
	/**
	 * Initialize database SQL and prepared statements
	 */
	private function initDbStatements() {
		$this->ops_lookup_word_sql = "
			SELECT word_id 
			FROM ca_sql_search_words
			WHERE
				word = ?
		";
		
		$this->opqr_lookup_word = $this->opo_db->prepare($this->ops_lookup_word_sql);
		
		$this->ops_insert_word_index_sql = "
			INSERT  INTO ca_sql_search_word_index
			(table_num, row_id, field_table_num, field_num, field_row_id, word_id, boost, access, rel_type_id)
			VALUES
		";
		
		$this->ops_insert_word_sql = "
			INSERT  INTO ca_sql_search_words
			(word, stem)
			VALUES
			(?, ?)
		";
		
		$this->ops_insert_ngram_sql = "
			INSERT  INTO ca_sql_search_ngrams
			(word_id, ngram, seq)
			VALUES
		";
		
		$this->opqr_insert_word = $this->opo_db->prepare($this->ops_insert_word_sql);
		
		$this->ops_delete_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (rel_type_id = ?)";
		$this->opqr_delete = $this->opo_db->prepare($this->ops_delete_sql);
		
		$this->ops_delete_with_field_num_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?) AND (rel_type_id = ?)";
		$this->opqr_delete_with_field_num = $this->opo_db->prepare($this->ops_delete_with_field_num_sql);
		
		$this->ops_delete_with_field_row_id_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->opqr_delete_with_field_row_id = $this->opo_db->prepare($this->ops_delete_with_field_row_id_sql);
		
		$this->ops_delete_with_field_row_id_and_num = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->opqr_delete_with_field_row_id_and_num = $this->opo_db->prepare($this->ops_delete_with_field_row_id_and_num);
		
		$this->ops_delete_dependent_sql = "DELETE FROM ca_sql_search_word_index WHERE (field_table_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->opqr_delete_dependent_sql = $this->opo_db->prepare($this->ops_delete_dependent_sql);
		
	}
	# -------------------------------------------------------
	/**
	 * Completely clear index (usually in preparation for a full reindex)
	 *
	 * @param int $pn_table_num Table_num of table to truncate from index; if omitted index for all tables is truncated.
	 * @return bool Returns true
	 */
	public function truncateIndex($pn_table_num=null) {
		if ($pn_table_num > 0) {
			$this->opo_db->query("DELETE FROM ca_sql_search_word_index WHERE table_num = ?", array((int)$pn_table_num));
		} else {
			$this->opo_db->query("TRUNCATE TABLE ca_sql_search_word_index");
			$this->opo_db->query("TRUNCATE TABLE ca_sql_search_words");
			$this->opo_db->query("TRUNCATE TABLE ca_sql_search_ngrams");
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _setMode($ps_mode) {
		switch ($ps_mode) {
			case 'search':
				// noop
				break;
			case 'indexing':
				// noop
				break;
			default:
				break;
		}
		
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __destruct() {	
		if (is_array($this->opa_doc_content_buffer) && sizeof($this->opa_doc_content_buffer)) {
			if($this->opo_db && !$this->opo_db->connected()) {
				$this->opo_db->connect();
			}
			$this->flushContentBuffer();
		}
		unset($this->opo_config);
		unset($this->opo_search_config);
		unset($this->opo_datamodel);
		unset($this->opo_db);
		unset($this->opo_tep);
	}
	# -------------------------------------------------------
	# Search
	# -------------------------------------------------------
	/**
	 *
	 */
	public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null) {
		$t = new Timer();
		$this->_setMode('search');
		$this->opa_filters = $pa_filters;
		if (!($t_instance = $this->opo_datamodel->getInstanceByTableNum($pn_subject_tablenum, true))) {
			// TODO: Better error message
			die("Invalid subject table");
		}
		
		$va_restrict_to_fields = $va_exclude_fields_from_search = array();
		if(is_array($this->getOption('restrictSearchToFields'))) {
			foreach($this->getOption('restrictSearchToFields') as $vs_f) {
				$va_restrict_to_fields[] = $this->_getElementIDForAccessPoint($pn_subject_tablenum, $vs_f);
			}
		}
		if(is_array($this->getOption('excludeFieldsFromSearch'))) {
			foreach($this->getOption('excludeFieldsFromSearch') as $vs_f) {
				$va_exclude_fields_from_search[] = $this->_getElementIDForAccessPoint($pn_subject_tablenum, $vs_f);
			}
		}
		
		$this->opo_db->query('SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
		if (trim($ps_search_expression) === '((*))') {	
			$vs_table_name = $t_instance->tableName();
			$vs_pk = $t_instance->primaryKey();
			
			// do we need to filter?
			$va_filters = $this->getFilters();
			$va_joins = array();
			$va_wheres = array();
			if (is_array($va_filters) && sizeof($va_filters)) {
				foreach($va_filters as $va_filter) {
					$va_tmp = explode('.', $va_filter['field']);
					
					$va_path = array();
					if ($va_tmp[0] != $vs_table_name) {
						$va_path = $this->opo_datamodel->getPath($vs_table_name, $va_tmp[0]);
					} 
					if (sizeof($va_path)) {
						$vs_last_table = null;
						// generate related joins
						foreach($va_path as $vs_table => $va_info) {
							$t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
							if ($vs_last_table) {
								$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_last_table, $vs_table);
								if (!sizeof($va_rels)) {
									$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_table, $vs_last_table);
								}
    							if ($vs_table == $va_rels['one_table']) {
									$va_joins[$vs_table] = "INNER JOIN ".$va_rels['one_table']." ON ".$va_rels['one_table'].".".$va_rels['one_table_field']." = ".$va_rels['many_table'].".".$va_rels['many_table_field'];
								} else {
									$va_joins[$vs_table] = "INNER JOIN ".$va_rels['many_table']." ON ".$va_rels['many_table'].".".$va_rels['many_table_field']." = ".$va_rels['one_table'].".".$va_rels['one_table_field'];
								}
							}
							$t_last_table = $t_table;
							$vs_last_table = $vs_table;
						}
						$vs_where = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					} else {
						// join in primary table
						$vs_where = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					}
					
					if (in_array('NULL', $va_filter, true)) {
						switch($va_filter['operator']) {
							case 'in':
								if (strpos(strtolower($va_filter['value']), 'null') !== false) {
									$vs_where = "({$vs_where} OR (".$va_filter['field']." IS NULL))";
								}
								break;
							case 'not in':
								if (strpos(strtolower($va_filter['value']), 'null') !== false) {
									$vs_where = "({$vs_where} OR (".$va_filter['field']." IS NOT NULL))";
								}
								break;
						}
					}
					$va_wheres[] = $vs_where;
				}
			}
			
			$vs_join_sql = join("\n", $va_joins);
			$vs_where_sql = '';
			if (sizeof($va_wheres)) {
				$vs_where_sql = " WHERE ".join(" AND ", $va_wheres);
			}
			
			$vs_sql = "
				SELECT {$vs_table_name}.{$vs_pk} row_id 
				FROM {$vs_table_name}
				{$vs_join_sql}
				{$vs_where_sql}
				ORDER BY
					row_id
			";
			$qr_res = $this->opo_db->query($vs_sql);
		} else {
			$this->_createTempTable('ca_sql_search_search_final');
			$this->_doQueriesForSqlSearch($po_rewritten_query, $pn_subject_tablenum, 'ca_sql_search_search_final', 0, array('restrictSearchToFields' => $va_restrict_to_fields, 'excludeFieldsFromSearch' => $va_exclude_fields_from_search));
			Debug::msg("doqueries for {$ps_search_expression} took ".$t->getTime(4));
				
			// do we need to filter?
			$va_filters = $this->getFilters();
			$va_joins = array();
			$va_wheres = array();
			if (is_array($va_filters) && sizeof($va_filters)) {
				foreach($va_filters as $va_filter) {
					$va_tmp = explode('.', $va_filter['field']);
					$va_path = array();
					if ($va_tmp[0] != $vs_table_name) {
						$va_path = $this->opo_datamodel->getPath($vs_table_name, $va_tmp[0]);
					} 
					if (sizeof($va_path)) {
						$vs_last_table = null;
						// generate related joins
						foreach($va_path as $vs_table => $va_info) {
							$t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
							if (!$vs_last_table) {
								$va_joins[$vs_table] = "INNER JOIN ".$vs_table." ON ".$vs_table.".".$t_table->primaryKey()." = ca_sql_search_search_final.row_id";
							} else {
								$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_last_table, $vs_table);
								if (!sizeof($va_rels)) {
									$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_table, $vs_last_table);
								}
    							if ($vs_table == $va_rels['one_table']) {
									$va_joins[$vs_table] = "INNER JOIN ".$va_rels['one_table']." ON ".$va_rels['one_table'].".".$va_rels['one_table_field']." = ".$va_rels['many_table'].".".$va_rels['many_table_field'];
								} else {
									$va_joins[$vs_table] = "INNER JOIN ".$va_rels['many_table']." ON ".$va_rels['many_table'].".".$va_rels['many_table_field']." = ".$va_rels['one_table'].".".$va_rels['one_table_field'];
								}
							}
							$t_last_table = $t_table;
							$vs_last_table = $vs_table;
						}
						$vs_where = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					} else {
						$t_table = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true);
						// join in primary table
						if (!isset($va_joins[$va_tmp[0]])) {
							$va_joins[$va_tmp[0]] = "INNER JOIN ".$va_tmp[0]." ON ".$va_tmp[0].".".$t_table->primaryKey()." = ca_sql_search_search_final.row_id";
						}
						$vs_where = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					}
					
					switch($va_filter['operator']) {
						case 'in':
							if (strpos(strtolower($va_filter['value']), 'null') !== false) {
								$vs_where = "({$vs_where} OR (".$va_filter['field']." IS NULL))";
							}
							break;
						case 'not in':
							if (strpos(strtolower($va_filter['value']), 'null') !== false) {
								$vs_where = "({$vs_where} OR (".$va_filter['field']." IS NOT NULL))";
							}
							break;
					}
					$va_wheres[] = $vs_where;
				}
				
				Debug::msg("set up filters for {$ps_search_expression} took ".$t->getTime(4));
			}
			
			$vs_join_sql = join("\n", $va_joins);
			$vs_where_sql = '';
			if (sizeof($va_wheres)) {
				$vs_where_sql = " WHERE ".join(" AND ", $va_wheres);
			}
			$vs_sql = "
				SELECT DISTINCT boost, row_id
				FROM ca_sql_search_search_final
				{$vs_join_sql}
				{$vs_where_sql}
				ORDER BY
					boost DESC, row_id
			";
			$qr_res = $this->opo_db->query($vs_sql);
			
			Debug::msg("search for {$ps_search_expression} took ".$t->getTime(4));
		
			$this->_dropTempTable('ca_sql_search_search_final');
		}
		$this->opo_db->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
		$va_hits = $qr_res->getAllFieldValues('row_id');
		
		return new WLPlugSearchEngineSqlSearchResult($va_hits, $pn_subject_tablenum);
	}
	# -------------------------------------------------------
	private function _createTempTable($ps_name) {
		$this->opo_db->query("DROP TABLE IF EXISTS {$ps_name}");
		$this->opo_db->query("
			CREATE TEMPORARY TABLE {$ps_name} (
				row_id int unsigned not null primary key,
				boost int not null default 1
			) engine=memory;
		");
		if ($this->opo_db->numErrors()) {
			return false;
		}
		return true;
	}
	# -------------------------------------------------------
	private function _dropTempTable($ps_name) {
		$this->opo_db->query("
			DROP TABLE IF EXISTS {$ps_name};
		");
		if ($this->opo_db->numErrors()) {
			return false;
		}
		return true;
	}
	# -------------------------------------------------------
	private function _getElementIDForAccessPoint($pn_subject_tablenum, $ps_access_point) {
		$va_tmp = explode('/', $ps_access_point);
		list($vs_table, $vs_field, $vs_subfield) = explode('.', $va_tmp[0]);
		
		$vs_rel_table = caGetRelationshipTableName($pn_subject_tablenum, $vs_table);
		$va_rel_type_ids = ($va_tmp[1] && $vs_rel_table) ? caMakeRelationshipTypeIDList($vs_rel_table, preg_split("![,;]+!", $va_tmp[1])) : null;
		
		if (!($t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true))) { 
			return array('access_point' => $va_tmp[0]);
		}
		$vs_table_num = $t_table->tableNum();
		
		// counts for relationship
		if (strtolower($vs_field) == 'count') {
			$vs_rel_type = null;
			
			if (sizeof($va_rel_type_ids) > 0) {
				$vn_rel_type = $va_rel_type_ids[0];
			} else {
				$va_rel_type_ids = [0];
			}
			
			return array(
				'access_point' => "{$vs_table}.{$vs_field}",
				'relationship_type' => (int)$vn_rel_type,
				'table_num' => $vs_table_num,
				'element_id' => null,
				'field_num' => 'COUNT',
				'datatype' => 'COUNT',
				'element_info' => null,
				'relationship_type_ids' => $va_rel_type_ids
			);
		} elseif (is_numeric($vs_field)) {
			$vs_fld_num = $vs_field;
		} else {
			$vs_fld_num = $this->getFieldNum($vs_table, $vs_field);
		}
		
		if (!strlen($vs_fld_num)) {
			$t_element = new ca_metadata_elements();
			
			$vb_is_count = false;
			if(strtolower($vs_subfield) == 'count') {
				$vs_subfield = null;
				$vb_is_count = true;
				if (!is_array($va_rel_type_ids) || !sizeof($va_rel_type_ids)) { $va_rel_type_ids = [0]; }
			}
			if ($t_element->load(array('element_code' => ($vs_subfield ? $vs_subfield : $vs_field)))) {
				if ($vb_is_count) {
					return array(
						'access_point' => "{$vs_table}.{$vs_field}",
						'relationship_type' => $va_tmp[1],
						'table_num' => $vs_table_num,
						'element_id' => $t_element->getPrimaryKey(),
						'field_num' => 'COUNT'.$t_element->getPrimaryKey(),
						'datatype' => 'COUNT',
						'element_info' => $t_element->getFieldValuesArray(),
						'relationship_type_ids' => $va_rel_type_ids
					);
				} else {
					return array(
						'access_point' => $va_tmp[0],
						'relationship_type' => $va_tmp[1],
						'table_num' => $vs_table_num,
						'element_id' => $t_element->getPrimaryKey(),
						'field_num' => 'A'.$t_element->getPrimaryKey(),
						'datatype' => $t_element->get('datatype'),
						'element_info' => $t_element->getFieldValuesArray(),
						'relationship_type_ids' => $va_rel_type_ids
					);
				}
			}
		} else {

			return array('access_point' => $va_tmp[0], 'relationship_type' => $va_tmp[1], 'table_num' => $vs_table_num, 'field_num' => 'I'.$vs_fld_num, 'field_num_raw' => $vs_fld_num, 'datatype' => null, 'relationship_type_ids' => $va_rel_type_ids);
		}

		return null;
	}
	# -------------------------------------------------------
	private function _doQueriesForSqlSearch($po_rewritten_query, $pn_subject_tablenum, $ps_dest_table, $pn_level=0, $pa_options=null) {		// query is always of type Zend_Search_Lucene_Search_Query_Boolean
		$vn_i = 0;
		switch(get_class($po_rewritten_query)){
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				$va_elements = $po_rewritten_query->getTerms();
				break;
			default:
				$va_elements = $po_rewritten_query->getSubqueries();
				break;
		}
		
		$o_base = new SearchBase();
		
		$va_old_signs = $po_rewritten_query->getSigns();
		
		$vb_is_only_blank_searches = true;
		foreach($va_elements as $o_lucene_query_element) {
			$vb_is_blank_search = $vb_is_not_blank_search = false;
			
			if (is_null($va_old_signs)) {	// if array is null then according to Zend Lucene all subqueries should be "are required"... so we AND them
				$vs_op = "AND";
			} else {
				if (is_null($va_old_signs[$vn_i])) {	// is the sign for a particular query is null then OR is (it is "neither required nor prohibited")
					$vs_op = 'OR';
				} else {
					$vs_op = ($va_old_signs[$vn_i] === false) ? 'NOT' : 'AND';	// true sign indicated "required" (AND) operation, false indicated "prohibited" (NOT) operation
				}
			}
			if ($vn_i == 0) { $vs_op = 'OR'; }
			
			
			$va_direct_query_temp_tables = array();	// List of temporary tables created by direct search queries; tables listed here are dropped at the end of processing for the query element		
			$pa_direct_sql_query_params = null; // set to array with values to use with direct SQL query placeholders or null to pass single standard table_num value as param (most queries just need this single value)
			$vs_direct_sql_query = null;
			$vn_direct_sql_target_table_num = $pn_subject_tablenum;
			
			$vb_dont_rewrite_direct_sql_query = false;
			switch($vs_class = get_class($o_lucene_query_element)) {
				case 'Zend_Search_Lucene_Search_Query_Boolean':
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
					$this->_createTempTable('ca_sql_search_temp_'.$pn_level);
					
					if (($vs_op == 'AND') && ($vn_i == 0)) {
						$this->_doQueriesForSqlSearch($o_lucene_query_element, $pn_subject_tablenum, $ps_dest_table, ($pn_level+1));
					} else {
						$this->_doQueriesForSqlSearch($o_lucene_query_element, $pn_subject_tablenum, 'ca_sql_search_temp_'.$pn_level, ($pn_level+1));
					}
					
					
					// merge with current destination
					switch($vs_op) {
						case 'AND':
							if ($vn_i > 0) {
								$this->_createTempTable("{$ps_dest_table}_acc");
							
								$vs_sql = "
									INSERT IGNORE INTO {$ps_dest_table}_acc
									SELECT mfs.row_id, SUM(mfs.boost)
									FROM {$ps_dest_table} mfs
									INNER JOIN ca_sql_search_temp_{$pn_level} AS ftmp1 ON ftmp1.row_id = mfs.row_id
									GROUP BY mfs.row_id
								";
								$qr_res = $this->opo_db->query($vs_sql);
								
								$qr_res = $this->opo_db->query("TRUNCATE TABLE {$ps_dest_table}");
								
								$qr_res = $this->opo_db->query("INSERT INTO {$ps_dest_table} SELECT row_id, boost FROM {$ps_dest_table}_acc");
								$this->_dropTempTable("{$ps_dest_table}_acc");
							} 
							break;
						case 'NOT':
							$qr_res = $this->opo_db->query("SELECT row_id FROM ca_sql_search_temp_{$pn_level}");
							
							if (is_array($va_ids = $qr_res->getAllFieldValues('row_id')) && sizeof($va_ids)) {
								$vs_sql = "
									DELETE FROM {$ps_dest_table} WHERE row_id IN (?)
								";
								$qr_res = $this->opo_db->query($vs_sql, array($va_ids));
							}
							break;
						default:
						case 'OR':
							$vs_sql = "
								INSERT IGNORE INTO {$ps_dest_table}
								SELECT row_id, SUM(boost)
								FROM ca_sql_search_temp_{$pn_level}
								GROUP BY row_id
							";
							$qr_res = $this->opo_db->query($vs_sql);
							break;
					}
					$vn_i++;
					$this->_dropTempTable('ca_sql_search_temp_'.$pn_level);
					break;
				case 'Zend_Search_Lucene_Search_Query_Term':
				case 'Zend_Search_Lucene_Index_Term':
				case 'Zend_Search_Lucene_Search_Query_Phrase':
				case 'Zend_Search_Lucene_Search_Query_Range':
					$va_ft_terms = array();
					$va_ft_like_terms = array();
					$va_ft_stem_terms = array();

					$vs_access_point = '';
					$va_raw_terms = $va_raw_terms_escaped = array();
					$vs_fld_num = $vs_table_num = $t_table = null;
					switch(get_class($o_lucene_query_element)) {
						case 'Zend_Search_Lucene_Search_Query_Range':
							$va_lower_term = $o_lucene_query_element->getLowerTerm();
							$va_upper_term = $o_lucene_query_element->getUpperTerm();

							$va_tmp = explode('.', $va_lower_term->field);							
							$va_indexed_fields = $o_base->getFieldsToIndex($pn_subject_tablenum, $va_tmp[0]);
							if(is_array($va_tmp) && (sizeof($va_tmp) > 1) && is_array($va_indexed_fields) && isset($va_indexed_fields[$va_tmp[1]])) {
							    // is intrinsic
							    $vn_lower_val = intval($va_lower_term->text);
                                $vn_upper_val = intval($va_upper_term->text);
                                
                                if ($t_instance = $this->opo_datamodel->getInstanceByTableNum($pn_subject_tablenum, true)) {
                                
                                    $vs_direct_sql_query = "
                                        SELECT ".$t_instance->primaryKey()." AS row_id, 1
                                        FROM ".$t_instance->tableName()."
                                        WHERE
                                            (".$va_lower_term->field." BETWEEN ".floatval($vn_lower_val)." AND ".floatval($vn_upper_val).")
                                        
                                    ";
                                }
                                break;
							}
							
							$va_element = $this->_getElementIDForAccessPoint($pn_subject_tablenum, $va_lower_term->field);
							
							$vn_direct_sql_target_table_num = $va_element['table_num'];							
							$va_indexed_fields = $o_base->getFieldsToIndex($pn_subject_tablenum, $vn_direct_sql_target_table_num);
							
							$vn_root_element_id = $va_element['element_info']['hier_element_id'];
							if (($va_element['datatype'] !== 'COUNT') && !isset($va_indexed_fields['_ca_attribute_'.$va_element['element_id']]) && (!$vn_root_element_id || ($vn_root_element_id && !isset($va_indexed_fields['_ca_attribute_'.$vn_root_element_id])))) { break(2); } // skip if not indexed
										
							switch($va_element['datatype']) {
								case 'COUNT':
									$vb_dont_rewrite_direct_sql_query = true;
									$vs_direct_sql_query = "
										SELECT ca.row_id, 1
										FROM ca_sql_search_word_index ca
										INNER JOIN ca_sql_search_words AS sw ON ca.word_id = sw.word_id
										^JOIN
										WHERE
											(ca.table_num = {$pn_subject_tablenum}) 
											AND 
											(ca.field_table_num = ?)
											AND
											(ca.rel_type_id IN (".join(',', (is_array($va_element['relationship_type_ids']) && sizeof($va_element['relationship_type_ids'])) ? $va_element['relationship_type_ids'] : [0])."))
											AND
											(ca.field_num = '".$va_element['field_num']."')
											AND
											(sw.word BETWEEN ".(int)$va_lower_term->text." and ".(int)$va_upper_term->text.")
											
									".($this->getOption('omitPrivateIndexing') ? " AND ca.access = 0" : '');
									break;
								case __CA_ATTRIBUTE_VALUE_GEOCODE__:
									$t_geocode = new GeocodeAttributeValue();
									$va_parsed_value = $t_geocode->parseValue('['.$va_lower_term->text.']', $va_element['element_info']);
									$vs_lower_lat = $va_parsed_value['value_decimal1'];
									$vs_lower_long = $va_parsed_value['value_decimal2'];
									
									$va_parsed_value = $t_geocode->parseValue('['.$va_upper_term->text.']', $va_element['element_info']);
									$vs_upper_lat = $va_parsed_value['value_decimal1'];
									$vs_upper_long = $va_parsed_value['value_decimal2'];

									// mysql BETWEEN always wants the lower value first ... BETWEEN 5 AND 3 wouldn't match 4 ... So we swap the values if necessary
									if($vs_upper_lat < $vs_lower_lat) {
										$tmp=$vs_upper_lat;
										$vs_upper_lat=$vs_lower_lat;
										$vs_lower_lat=$tmp;
									}

									if($vs_upper_long < $vs_lower_long) {
										$tmp=$vs_upper_long;
										$vs_upper_long=$vs_lower_long;
										$vs_lower_long=$tmp;
									}

									$vs_direct_sql_query = "
										SELECT ca.row_id, 1
										FROM ca_attribute_values cav
										INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
										^JOIN
										WHERE
											(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
											AND
											(cav.value_decimal1 BETWEEN ".floatval($vs_lower_lat)." AND ".floatval($vs_upper_lat).")
											AND
											(cav.value_decimal2 BETWEEN ".floatval($vs_lower_long)." AND ".floatval($vs_upper_long).")	
									";
									break;
								case __CA_ATTRIBUTE_VALUE_CURRENCY__:
									$t_cur = new CurrencyAttributeValue();
									$va_parsed_value = $t_cur->parseValue($va_lower_term->text, $va_element['element_info']);
									$vs_currency = preg_replace('![^A-Z0-9]+!', '', $va_parsed_value['value_longtext1']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_cur->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									
									$vs_direct_sql_query = "
										SELECT ca.row_id, 1
										FROM ca_attribute_values cav
										INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
										^JOIN
										WHERE
											(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
											AND
											(cav.value_decimal1 BETWEEN ".floatval($vn_lower_val)." AND ".floatval($vn_upper_val).")
											AND
											(cav.value_longtext1 = '".$this->opo_db->escape($vs_currency)."')
											
									";
									break;
								case __CA_ATTRIBUTE_VALUE_TIMECODE__:
									$t_timecode = new TimecodeAttributeValue();
									$va_parsed_value = $t_timecode->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_timecode->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case __CA_ATTRIBUTE_VALUE_LENGTH__:
									$t_len = new LengthAttributeValue();
									$va_parsed_value = $t_len->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_len->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case __CA_ATTRIBUTE_VALUE_WEIGHT__:
									$t_weight = new WeightAttributeValue();
									$va_parsed_value = $t_weight->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_weight->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case __CA_ATTRIBUTE_VALUE_INTEGER__:
									$vn_lower_val = intval($va_lower_term->text);
									$vn_upper_val = intval($va_upper_term->text);
									
									$vs_direct_sql_query = "
										SELECT ca.row_id, 1
										FROM ca_attribute_values cav
										INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
										^JOIN
										WHERE
											(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
											AND
											(cav.value_integer1 BETWEEN ".floatval($vn_lower_val)." AND ".floatval($vn_upper_val).")
											
									";
									break;
								case __CA_ATTRIBUTE_VALUE_NUMERIC__:
									$vn_lower_val = floatval($va_lower_term->text);
									$vn_upper_val = floatval($va_upper_term->text);
									break;
							}
							
							if (!$vs_direct_sql_query) {
								$vs_direct_sql_query = "
									SELECT ca.row_id, 1
									FROM ca_attribute_values cav
									INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
									^JOIN
									WHERE
										(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
										AND
										(cav.value_decimal1 BETWEEN ".floatval($vn_lower_val)." AND ".floatval($vn_upper_val).")
										
								";
							}
							break;
						case 'Zend_Search_Lucene_Search_Query_Phrase':
							// dont do strict phrase searching for modified and created
							$o_first_term = array_shift($o_lucene_query_element->getQueryTerms());
							list($vs_first_term_table, $_, $_) = explode('.', $o_first_term->field);

	if ($this->getOption('strictPhraseSearching') && !in_array($vs_first_term_table, array('modified', 'created'))) {
						 	$va_words = array();
						 	foreach($o_lucene_query_element->getQueryTerms() as $o_term) {
								if (!$vs_access_point && ($vs_field = $o_term->field)) { $vs_access_point = $vs_field; }
								
								$va_terms = $this->_tokenize((string)$o_term->text, true); //preg_split("![".$this->ops_search_tokenizer_regex."]+!u", (string)$o_term->text);
								$va_raw_terms[] = (string)$o_term->text;
								$va_raw_terms_escaped[] = '"'.$this->opo_db->escape((string)$o_term->text).'"';
								foreach($va_terms as $vs_term) {
									if (strlen($vs_escaped_text = $this->opo_db->escape($vs_term))) {
										$va_words[] = $vs_escaped_text;
									}
								}
							}
							if (!sizeof($va_words)) { continue(3); }
						
							$va_ap_tmp = explode(".", $vs_access_point);
							$vn_fld_table = $vn_fld_num = null;
							if(sizeof($va_ap_tmp) >= 2) {
								$va_element = $this->_getElementIDForAccessPoint($pn_subject_tablenum, $vs_access_point);
								
								if (isset($va_element['field_num'],$va_element['table_num'])) {
									$vs_fld_num = $va_element['field_num'];
									$vs_fld_table_num = $va_element['table_num'];
									$vs_fld_limit_sql = " AND (swi.field_table_num = {$vs_fld_table_num} AND swi.field_num = '{$vs_fld_num}')";
									
									if (is_array($va_element['relationship_type_ids']) && sizeof($va_element['relationship_type_ids'])) {
										$vs_fld_limit_sql .= " AND (swi.rel_type_id IN (".join(",", $va_element['relationship_type_ids'])."))";
									}
								}
							}
							
							$va_temp_tables = array();
							$vn_w = 0;
							foreach($va_words as $vs_word) {
								$vn_w++;
								$vs_temp_table = 'ca_sql_search_phrase_'.md5($pn_subject_tablenum."/".$vs_word."/".$vn_w);
								$this->_createTempTable($vs_temp_table);
								$vs_sql = "
									INSERT INTO {$vs_temp_table}
									SELECT swi.index_id + 1, 1
									FROM ca_sql_search_words sw 
									INNER JOIN ca_sql_search_word_index AS swi ON sw.word_id = swi.word_id 
									".(sizeof($va_temp_tables) ? " INNER JOIN ".$va_temp_tables[sizeof($va_temp_tables) - 1]." AS tt ON swi.index_id = tt.row_id" : "")."
									WHERE 
										sw.word = ? AND swi.table_num = ? {$vs_fld_limit_sql}
 										".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
								";
								$qr_res = $this->opo_db->query($vs_sql, $vs_word, (int)$pn_subject_tablenum);
								
								$qr_count = $this->opo_db->query("SELECT count(*) c FROM {$vs_temp_table}");
								
								$va_temp_tables[] = $vs_temp_table;	
							}
							
							$vs_results_temp_table = array_pop($va_temp_tables);
							
							$this->opo_db->query("UPDATE {$vs_results_temp_table} SET row_id = row_id - 1");
							$va_direct_query_temp_tables[$vs_results_temp_table] = true;
							$vs_direct_sql_query = "SELECT swi.row_id, ca.boost 
													FROM {$vs_results_temp_table} ca
													INNER JOIN ca_sql_search_word_index AS swi ON swi.index_id = ca.row_id 
							";
							$pa_direct_sql_query_params = array(); // don't pass any params
							
							foreach($va_temp_tables as $vs_temp_table) {
								$this->_dropTempTable($vs_temp_table);
							}
							
							break;
		}
						default:
							switch($vs_class) {
								case 'Zend_Search_Lucene_Search_Query_Phrase':
									$va_term_objs = $o_lucene_query_element->getQueryTerms();
									break;
								case 'Zend_Search_Lucene_Index_Term':
									$va_term_objs = array($o_lucene_query_element);
									break;
								default:
									$va_term_objs = array($o_lucene_query_element->getTerm());
									break;
							}
							
							foreach($va_term_objs as $o_term) {
								$va_access_point_info = $this->_getElementIDForAccessPoint($pn_subject_tablenum, $o_term->field);
								$vs_access_point = $va_access_point_info['access_point'];
								$vn_direct_sql_target_table_num = $va_access_point_info['table_num'];
							
								$vs_raw_term = (string)$o_term->text;
								//$vs_term = preg_replace("%((?<!\d)[".$this->ops_search_tokenizer_regex."]+|[".$this->ops_search_tokenizer_regex."]+(?!\d))%u", '', $vs_raw_term);
								$vs_term = join(' ', $this->_tokenize($vs_raw_term, true));
								
								if ($vs_access_point && (mb_strtoupper($vs_raw_term) == '['._t('BLANK').']')) {
									$t_ap = $this->opo_datamodel->getInstanceByTableNum($va_access_point_info['table_num'], true);
									if (is_a($t_ap, 'BaseLabel')) {	// labels have the literal text "[blank]" indexed to "blank" to indicate blank-ness 
										$vb_is_blank_search = false;
										$vs_term = _t('blank');
									} else {
										$vb_is_blank_search = true;
										$vs_table_num = $va_access_point_info['table_num'];
										$vs_fld_num = $va_access_point_info['field_num'];
										break;
									} 
								} elseif ($vs_access_point && (mb_strtoupper($vs_raw_term) == '['._t('SET').']')) {
									$vb_is_not_blank_search = true;
									$vs_table_num = $va_access_point_info['table_num'];
									$vs_fld_num = $va_access_point_info['field_num'];
									break;
								} elseif ($vs_access_point) {
									$vs_table_num = $va_access_point_info['table_num'];
									$vs_fld_num = $va_access_point_info['field_num'];
								}
							
								$va_terms = array($vs_term); //$this->_tokenize($vs_term, true, $vn_i);
								$vb_has_wildcard = (bool)(preg_match('!\*$!', $vs_raw_term));
								$vb_output_term = false;
								foreach($va_terms as $vs_term) {
									if ($vb_has_wildcard) { $vs_term .= '*'; }
									
									$vs_stripped_term = preg_replace('!\*+$!u', '', $vs_term);
									
									if ($vb_has_wildcard) {
										$va_ft_like_terms[] = $vs_stripped_term;
									} else {
										// do stemming
										$vb_do_stemming = $this->opb_do_stemming;
										if (mb_substr($vs_term, -1) == '|') {
											$vs_term = mb_substr($vs_term, 0, mb_strlen($vs_term) - 1);
											$vs_raw_term = mb_substr($vs_raw_term, 0, mb_strlen($vs_raw_term) - 1);
											$vb_do_stemming = false;
										} elseif (mb_substr($vs_raw_term, -1) == '|') {
											$vs_raw_term = mb_substr($vs_raw_term, 0, mb_strlen($vs_raw_term) - 1);
											$vb_do_stemming = false;
										}
										if ($vb_do_stemming) {
											$vs_to_stem = preg_replace('!\*$!u', '', $vs_term);
											if (!preg_match('!y$!u', $vs_to_stem) && !preg_match('![0-9]+!', $vs_to_stem)) {	// don't stem things ending in 'y' as that can cause problems (eg "Bowery" becomes "Boweri")
												if (!($vs_stem = trim($this->opo_stemmer->stem($vs_to_stem)))) {
													$vs_stem = (string)$vs_term;
												}
												$va_ft_stem_terms[] = "'".$this->opo_db->escape($vs_stem)."'";
											} else {
												$va_ft_terms[] = '"'.$this->opo_db->escape($vs_term).'"';
											}
										} else {
											$va_ft_terms[] = '"'.$this->opo_db->escape($vs_term).'"';
										}
									}
									$vb_output_term = true;	
								
								}
								if ($vb_output_term) { $va_raw_terms[] = $vs_raw_term; $va_raw_terms_escaped[] = '"'.$this->opo_db->escape($vs_raw_term).'"'; }
							}
							$va_raw_terms = array_unique($va_raw_terms);
							$va_raw_terms_escaped = array_unique($va_raw_terms_escaped);
							$va_ft_terms = array_unique($va_ft_terms);
							$va_ft_stem_terms = array_unique($va_ft_stem_terms);
							
							break;
					}
					
					$vb_ft_bit_optimization = false;
					if ($vs_access_point) {
						list($vs_table, $vs_field, $vs_sub_field) = explode('.', $vs_access_point);
						if (in_array($vs_table, array('created', 'modified'))) {
							$vn_direct_sql_target_table_num = $pn_subject_tablenum;
							$o_tep = new TimeExpressionParser();
							$vs_date = join(' ', $va_raw_terms);
							
							if (!$o_tep->parse($vs_date)) { break; }
							$va_range = $o_tep->getUnixTimestamps();
							$vn_user_id = null;
							if ($vs_field = trim($vs_field)) {
								if (!is_int($vs_field)) {
									$t_user = new ca_users();
									if ($t_user->load(array("user_name" => $vs_field))) {
										$vn_user_id = (int)$t_user->getPrimaryKey();
									}
								} else {
									$vn_user_id = (int)$vs_field;
								}
							}
							$vs_user_sql = ($vn_user_id)  ? " AND (ccl.user_id = ".(int)$vn_user_id.")" : "";
							
							switch($vs_table) {
								case 'created':
									$vs_direct_sql_query = "
											SELECT ccl.logged_row_id row_id, 1
											FROM ca_change_log ccl
											WHERE
												(ccl.log_datetime BETWEEN ".(int)$va_range['start']." AND ".(int)$va_range['end'].")
												AND
												(ccl.logged_table_num = ?)
												AND
												(ccl.changetype = 'I')
												{$vs_user_sql}
										";
									break;
								case 'modified':
									$vs_direct_sql_query = "
											SELECT ccl.logged_row_id row_id, 1
											FROM ca_change_log ccl
											WHERE
												(ccl.log_datetime BETWEEN ".(int)$va_range['start']." AND ".(int)$va_range['end'].")
												AND
												(ccl.logged_table_num = ?)
												AND
												(ccl.changetype = 'U')
												{$vs_user_sql}
										UNION
											SELECT ccls.subject_row_id row_id, 1
											FROM ca_change_log ccl
											INNER JOIN ca_change_log_subjects AS ccls ON ccls.log_id = ccl.log_id
											WHERE
												(ccl.log_datetime BETWEEN ".(int)$va_range['start']." AND ".(int)$va_range['end'].")
												AND
												(ccls.subject_table_num = {$pn_subject_tablenum})
												{$vs_user_sql}
										";
									break;
							}
						} else {
							if ((!$vb_is_blank_search && !$vb_is_not_blank_search) && $vs_table && $vs_field && ($t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true)) ) {
								$vs_table_num = $t_table->tableNum();
								
								if (is_numeric($vs_field)) {
									$vs_fld_num = 'I'.$vs_field;
									$vn_fld_num = (int)$vs_field;
								} else {
									if($vn_fld_num = $this->getFieldNum($vs_table, $vs_field)) {
										$vs_fld_num = 'I'.$vn_fld_num;
									}
									
									if (!strlen($vn_fld_num)) {
										$t_element = new ca_metadata_elements();
										if ($t_element->load(array('element_code' => ($vs_sub_field ? $vs_sub_field : $vs_field)))) {
											$vn_direct_sql_target_table_num = $vs_table_num;
											$va_indexed_fields = $o_base->getFieldsToIndex($pn_subject_tablenum, $vn_direct_sql_target_table_num);
											$vn_fld_num = $t_element->getPrimaryKey();
											$vn_root_element_id = $t_element->get('hier_element_id');
											
											if (!isset($va_indexed_fields['_ca_attribute_'.$vn_fld_num]) && (!$vn_root_element_id || ($vn_root_element_id && !isset($va_indexed_fields['_ca_attribute_'.$vn_root_element_id])))) { break(2); } // skip if not indexed
											//$vs_fld_num = 'A'.$vn_fld_num;
										
											if (!$vb_is_blank_search && !$vb_is_not_blank_search) {
												//
												// For certain types of attributes we can directly query the
												// attributes in the database rather than using the full text index
												// This allows us to do "intelligent" querying... for example on date ranges
												// parsed from natural language input and for length dimensions using unit conversion
												//
												switch($t_element->get('datatype')) {
													case __CA_ATTRIBUTE_VALUE_DATERANGE__:	
														$vb_all_numbers = true;
														foreach($va_raw_terms as $vs_term) {
															if (!is_numeric($vs_term)) {
																$vb_all_numbers = false;
																break;
															}
														}
														$vs_raw_term = join(' ', $va_raw_terms);
														
														$vs_eq = '';
														switch($vs_raw_term{0}) {
															case '#':
																$vs_raw_term = substr($vs_raw_term, 1);
																if ($this->opo_tep->parse($vs_raw_term)) {
																	$va_dates = $this->opo_tep->getHistoricTimestamps();
																	$vs_direct_sql_query = "
																		SELECT ca.row_id, 1
																		FROM ca_attribute_values cav
																		INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
																		^JOIN
																		WHERE
																			(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																			AND
																			(
																				(cav.value_decimal1 BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
																				AND
																				(cav.value_decimal2 BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
																			)
																	
																	";
																	$vn_direct_sql_target_table_num = $vs_table_num; 
																}
																break;
															case '<':
																$vs_raw_term = substr($vs_raw_term, 1);
																if ($vs_raw_term{0} == '=') {
																	$vs_raw_term = substr($vs_raw_term, 1);
																	$vs_eq = '=';
																}
																if ($this->opo_tep->parse($vs_raw_term)) {
																	$va_dates = $this->opo_tep->getHistoricTimestamps();
																	$vs_direct_sql_query = "
																		SELECT ca.row_id, 1
																		FROM ca_attribute_values cav
																		INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
																		^JOIN
																		WHERE
																			(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																			AND
																			(
																				(cav.value_decimal2 <{$vs_eq} ".(($vs_eq === '=') ? floatval($va_dates['end']) : floatval($va_dates['start'])).")
																			)
																	";
																	$vn_direct_sql_target_table_num = $vs_table_num; 
																}
																break;
															case '>':
																$vs_raw_term = substr($vs_raw_term, 1);
																if ($vs_raw_term{0} == '=') {
																	$vs_raw_term = substr($vs_raw_term, 1);
																	$vs_eq = '=';
																}
																if ($this->opo_tep->parse($vs_raw_term)) {
																	$va_dates = $this->opo_tep->getHistoricTimestamps();
																	$vs_direct_sql_query = "
																		SELECT ca.row_id, 1
																		FROM ca_attribute_values cav
																		INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
																		^JOIN
																		WHERE
																			(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																			AND
																			(
																				(cav.value_decimal1 >{$vs_eq} ".(($vs_eq === '=') ? floatval($va_dates['start']) : floatval($va_dates['end'])).")
																			)
																	";
																	$vn_direct_sql_target_table_num = $vs_table_num; 
																}
																break;
															default:
																if ($this->opo_tep->parse($vs_raw_term)) {
																	$va_dates = $this->opo_tep->getHistoricTimestamps();
																	$vs_direct_sql_query = "
																		SELECT ca.row_id, 1
																		FROM ca_attribute_values cav
																		INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
																		^JOIN
																		WHERE
																			(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																			AND
																			(
																				(cav.value_decimal1 BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
																				OR
																				(cav.value_decimal2 BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
																				OR
																				(cav.value_decimal1 <= ".floatval($va_dates['start'])." AND cav.value_decimal2 >= ".floatval($va_dates['end']).")	
																			)
																	
																	";
																
																	$vn_direct_sql_target_table_num = $vs_table_num; 
																}
																break;
														}
														break;
													case __CA_ATTRIBUTE_VALUE_GEOCODE__:
														// At this point $va_raw_terms has been tokenized by Lucene into oblivion
														// and is also dependent on the search_tokenizer_regex so we can't really do anything with it.
														// We now build our own un-tokenized term array instead. caParseGISSearch() can handle it.
														$va_gis_terms = array();
														foreach($o_lucene_query_element->getQueryTerms() as $o_term) {
															$va_gis_terms[] = trim((string)$o_term->text);
														}
														if ($va_coords = caParseGISSearch(join(' ', $va_gis_terms))) {
															$vs_direct_sql_query = "
																SELECT ca.row_id, 1
																FROM ca_attribute_values cav
																INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
																^JOIN
																WHERE
																	(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																	AND
																	(cav.value_decimal1 BETWEEN {$va_coords['min_latitude']} AND {$va_coords['max_latitude']})
																	AND
																	(cav.value_decimal2 BETWEEN {$va_coords['min_longitude']} AND {$va_coords['max_longitude']})
																
															";
															$vn_direct_sql_target_table_num = $vs_table_num; 
														}
														break;
													case __CA_ATTRIBUTE_VALUE_CURRENCY__:
														$t_cur = new CurrencyAttributeValue();
														$va_parsed_value = $t_cur->parseValue(join(' ', $va_raw_terms), $t_element->getFieldValuesArray());
														$vn_amount = $va_parsed_value['value_decimal1'];
														$vs_currency = preg_replace('![^A-Z0-9]+!', '', $va_parsed_value['value_longtext1']);
													
														$vs_direct_sql_query = "
															SELECT ca.row_id, 1
															FROM ca_attribute_values cav
															INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
															^JOIN
															WHERE
																(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																AND
																(cav.value_decimal1 = ".floatval($vn_amount).")
																AND
																(cav.value_longtext1 = '".$this->opo_db->escape($vs_currency)."')
															
														";
														$vn_direct_sql_target_table_num = $vs_table_num; 
														break;
													case __CA_ATTRIBUTE_VALUE_LENGTH__:
														// If it looks like a dimension that has been tokenized by Lucene
														// into oblivion rehydrate it here.
														try {
															switch(sizeof($va_raw_terms)) {
																case 2:
																	$vs_dimension = $va_raw_terms[0] . caGetDecimalSeparator() . $va_raw_terms[1];
																	break;
																case 3:
																	$vs_dimension = $va_raw_terms[0] . caGetDecimalSeparator() . $va_raw_terms[1] . " " . $va_raw_terms[2];
																	break;
																default:
																	$vs_dimension = join(' ', $va_raw_terms);
															}
															$vo_parsed_measurement = caParseLengthDimension($vs_dimension);
															$vn_len = $vo_parsed_measurement->convertTo('METER',6, 'en_US');
														} catch(Exception $e) {
															$vs_direct_sql_query = null;
															break;
														}

														$vs_direct_sql_query = "
															SELECT ca.row_id, 1
															FROM ca_attribute_values cav
															INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
															^JOIN
															WHERE
																(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																AND
																(cav.value_decimal1 = ".floatval($vn_len).")
															
														";
														$vn_direct_sql_target_table_num = $vs_table_num; 
														break;
													case __CA_ATTRIBUTE_VALUE_WEIGHT__:
														// If it looks like a weight that has been tokenized by Lucene
														// into oblivion rehydrate it here.
														try {
															switch(sizeof($va_raw_terms)) {
																case 2:
																	$vs_dimension = $va_raw_terms[0] . caGetDecimalSeparator() . $va_raw_terms[1];
																	break;
																case 3:
																	$vs_dimension = $va_raw_terms[0] . caGetDecimalSeparator() . $va_raw_terms[1] . " " . $va_raw_terms[2];
																	break;
																default:
																	$vs_dimension = join(' ', $va_raw_terms);
															}

															$vo_parsed_measurement = caParseWeightDimension($vs_dimension);
															$vn_weight = $vo_parsed_measurement->convertTo('KILOGRAM',6, 'en_US');
														} catch(Exception $e) {
															$vs_direct_sql_query = null;
															break;
														}

														$vs_direct_sql_query = "
															SELECT ca.row_id, 1
															FROM ca_attribute_values cav
															INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
															^JOIN
															WHERE
																(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																AND
																(cav.value_decimal1 = ".floatval($vn_weight).")
															
														";
														$vn_direct_sql_target_table_num = $vs_table_num; 
														break;
													case __CA_ATTRIBUTE_VALUE_TIMECODE__:
														$t_timecode = new TimecodeAttributeValue();
														$va_parsed_value = $t_timecode->parseValue(join(' ', $va_raw_terms), $t_element->getFieldValuesArray());
														$vn_timecode = $va_parsed_value['value_decimal1'];
													
														$vs_direct_sql_query = "
															SELECT ca.row_id, 1
															FROM ca_attribute_values cav
															INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
															^JOIN
															WHERE
																(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																AND
																(cav.value_decimal1 = ".floatval($vn_timecode).")
															
														";
														$vn_direct_sql_target_table_num = $vs_table_num; 
														break;
													case __CA_ATTRIBUTE_VALUE_INTEGER__:
														$vs_direct_sql_query = "
															SELECT ca.row_id, 1
															FROM ca_attribute_values cav
															INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
															^JOIN
															WHERE
																(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																AND
																(cav.value_integer1 = ".intval(array_shift($va_raw_terms)).")
															
														";
														$vn_direct_sql_target_table_num = $vs_table_num; 
														break;
													case __CA_ATTRIBUTE_VALUE_NUMERIC__:
														$vs_direct_sql_query = "
															SELECT ca.row_id, 1
															FROM ca_attribute_values cav
															INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
															^JOIN
															WHERE
																(cav.element_id = {$vn_fld_num}) AND (ca.table_num = ?)
																AND
																(cav.value_decimal1 = ".floatval(array_shift($va_raw_terms)).")
															
														";
														$vn_direct_sql_target_table_num = $vs_table_num; 
														break;
												}
											}	
										} else { // neither table fields nor elements, i.e. 'virtual' fields like _count should 
											$vn_fld_num = false;
											$vs_fld_num = $vs_field;
										}
									}
								}
								if (($vs_intrinsic_field_name = $t_table->fieldName($vn_fld_num)) && (($vn_intrinsic_type = $t_table->getFieldInfo($vs_intrinsic_field_name, 'FIELD_TYPE')) == FT_BIT)) {
									$vb_ft_bit_optimization = true;
								} elseif($vn_intrinsic_type == FT_HISTORIC_DATERANGE) {
									$vb_all_numbers = true;
									foreach($va_raw_terms as $vs_term) {
										if (!is_numeric($vs_term)) {
											$vb_all_numbers = false;
											break;
										}
									}
								
									$vs_date_start_fld = $t_table->getFieldInfo($vs_intrinsic_field_name, 'START');
									$vs_date_end_fld = $t_table->getFieldInfo($vs_intrinsic_field_name, 'END');
								
									$vs_raw_term = join(' ', $va_raw_terms);
									
									switch($vs_raw_term{0}) {
										case '#':
											$vs_raw_term = substr($vs_raw_term, 1);
											if ($this->opo_tep->parse($vs_raw_term)) {
												$va_dates = $this->opo_tep->getHistoricTimestamps();
												$vs_direct_sql_query = "
													SELECT ".$t_table->primaryKey().", 1
													FROM ".$t_table->tableName()."
													^JOIN
													WHERE
														(
															({$vs_date_start_fld} BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
															AND
															({$vs_date_end_fld} BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
														)
												
												";
											}
											break;
										case '<':
											$vs_raw_term = substr($vs_raw_term, 1);
											if ($vs_raw_term{0} == '=') {
												$vs_raw_term = substr($vs_raw_term, 1);
												$vs_eq = '=';
											}
											if ($this->opo_tep->parse($vs_raw_term)) {
												$va_dates = $this->opo_tep->getHistoricTimestamps();
												
												$vs_direct_sql_query = "
													SELECT ".$t_table->primaryKey().", 1
													FROM ".$t_table->tableName()."
													^JOIN
													WHERE
														(
															({$vs_date_end_fld} <{$vs_eq} ".(($vs_eq === '=') ? floatval($va_dates['end']) : floatval($va_dates['start'])).")
														)
												
												";
											}
											break;
										case '>':
											$vs_raw_term = substr($vs_raw_term, 1);
											if ($vs_raw_term{0} == '=') {
												$vs_raw_term = substr($vs_raw_term, 1);
												$vs_eq = '=';
											}
											if ($this->opo_tep->parse($vs_raw_term)) {
												$va_dates = $this->opo_tep->getHistoricTimestamps();
												
												$vs_direct_sql_query = "
													SELECT ".$t_table->primaryKey().", 1
													FROM ".$t_table->tableName()."
													^JOIN
													WHERE
														(
															({$vs_date_start_fld} >{$vs_eq} ".(($vs_eq === '=') ? floatval($va_dates['start']) : floatval($va_dates['end'])).")
														)
												
												";
											}
											break;
										default:
											if ($this->opo_tep->parse($vs_raw_term)) {
												$va_dates = $this->opo_tep->getHistoricTimestamps();
												$vs_direct_sql_query = "
													SELECT ".$t_table->primaryKey().", 1
													FROM ".$t_table->tableName()."
													^JOIN
													WHERE
														(
															({$vs_date_start_fld} BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
															OR
															({$vs_date_end_fld} BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
															OR
															({$vs_date_start_fld} <= ".floatval($va_dates['start'])." AND {$vs_date_end_fld} >= ".floatval($va_dates['end']).")	
														)
												
												";
											}
											break;
									}

									$pa_direct_sql_query_params = array();
								}
							}
						}
					}
					
					//
					// If we're querying on the fulltext index then we need to construct
					// the query here... if we already have a direct SQL query to run then we can skip this
					//
					$va_sql_where = array();
					if ($vb_is_blank_search) {
						$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = '{$vs_fld_num}') AND (swi.word_id = 0))";
						
						if (!sizeof($va_sql_where)) { continue; }
						$vs_sql_where = join(' OR ', $va_sql_where);
					} elseif ($vb_is_not_blank_search) {
						$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = '{$vs_fld_num}') AND (swi.word_id > 0))";
						if (!sizeof($va_sql_where)) { continue; }
						$vs_sql_where = join(' OR ', $va_sql_where);
					} elseif (!$vs_direct_sql_query) {
						if (sizeof($va_ft_terms)) {
							if (($t_table) && (strlen($vs_fld_num) > 1)) {
								$o_search = new SearchEngine();
								if (!is_array($va_field_info = $o_search->getFieldOptions($pn_subject_tablenum, $vs_table_num, $vs_field))) { $va_field_info = array(); }
								$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = '{$vs_fld_num}') AND (sw.word IN (".((in_array('DONT_TOKENIZE', $va_field_info, true) || in_array('INDEX_AS_IDNO', $va_field_info, true)) ? join(',', $va_raw_terms_escaped) : join(',', $va_ft_terms)).")))";
							} else {
								if (sizeof($va_ft_terms) == 1) {
									$va_sql_where[] =  "(sw.word = ".$va_ft_terms[0].")";
								} else {
									$va_sql_where[] =  "(sw.word IN (".join(',', $va_ft_terms)."))";
								}
							}
						}
						
						if (sizeof($va_ft_like_terms)) {
							$va_tmp = array();
							foreach($va_ft_like_terms as $vs_term) {
								if ($vb_ft_bit_optimization) {
									$va_tmp[] = '(sw.word = \' '.$this->opo_db->escape(trim($vs_term)).' \')';
								} else {
									$va_tmp[] = '(sw.word LIKE \''.$this->opo_db->escape(trim($vs_term)).'%\')';
								}
							}
							if (($t_table) && (strlen($vs_fld_num) > 1)) {
								$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = '{$vs_fld_num}') AND (".join(' AND ', $va_tmp)."))";
							} else {
								$va_sql_where[] =  "(".join(' AND ', $va_tmp).")";
							}
						}
						
						if (sizeof($va_ft_stem_terms)) {
							if (($t_table) && (strlen($vs_fld_num) > 1)) {
								$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = '{$vs_fld_num}') AND (sw.stem IN (".join(',', $va_ft_stem_terms).")))";
							} else {
								$va_sql_where[] =  "(sw.stem IN (".join(',', $va_ft_stem_terms)."))";
							}
						}
						
						if (!sizeof($va_sql_where)) { continue; }
						$vs_sql_where = join(' OR ', $va_sql_where);
					} else {
						$va_ft_terms = $va_ft_like_terms = $va_ft_like_terms = array();
					}
					
					$vs_rel_type_id_sql = null;
					if((is_array($va_access_point_info['relationship_type_ids']) && sizeof($va_access_point_info['relationship_type_ids']))) {
						$vs_rel_type_id_sql = " AND (swi.rel_type_id IN (".join(",", $va_access_point_info['relationship_type_ids'])."))";
					}
					if (!$vs_fld_num && is_array($va_restrict_to_fields = caGetOption('restrictSearchToFields', $pa_options, null)) && sizeof($va_restrict_to_fields)) {
						$va_field_restrict_sql = array();
						foreach($va_restrict_to_fields as $va_restrict) {
							$va_field_restrict_sql[] = "((swi.field_table_num = ".intval($va_restrict['table_num']).") AND (swi.field_num = '".$va_restrict['field_num']."'))";
						}
						$vs_sql_where .= " AND (".join(" OR ", $va_field_restrict_sql).")";
					}
					if (!$vs_fld_num && is_array($va_exclude_fields_from_search = caGetOption('excludeFieldsFromSearch', $pa_options, null)) && sizeof($va_exclude_fields_from_search)) {
						$va_field_restrict_sql = array();
						foreach($va_exclude_fields_from_search as $va_restrict) {
							$va_field_restrict_sql[] = "((swi.field_table_num <> ".intval($va_restrict['table_num']).") AND (swi.field_num <> '".$va_restrict['field_num']."'))";
						}
						$vs_sql_where .= " AND (".join(" OR ", $va_field_restrict_sql).")";
					}
					
					
					$va_join = array();
					if (($vn_direct_sql_target_table_num != $pn_subject_tablenum) && !$vb_dont_rewrite_direct_sql_query) {
						// We're doing direct queries on metadata in a related table, fun!
						// Now let's rewrite the direct query to work...
						
						if ($t_target = $this->opo_datamodel->getInstanceByTableNum($vn_direct_sql_target_table_num, true)) {
							// First we create the join from the related table to our subject
							$vs_target_table_name = $t_target->tableName();
							
							$va_path = array_keys($this->opo_datamodel->getPath($vn_direct_sql_target_table_num, $pn_subject_tablenum));
							
							$vs_left_table = array_shift($va_path);
							$va_join[] = "INNER JOIN {$vs_left_table} ON {$vs_left_table}.".$this->opo_datamodel->primaryKey($vs_left_table)." = ca.row_id";
							
							$vn_cj = 0;
							foreach($va_path as $vs_right_table) {
								if (sizeof($va_rels = $this->opo_datamodel->getRelationships($vs_left_table, $vs_right_table)) > 0) {
									$va_join[] = "INNER JOIN {$vs_right_table} ON {$vs_right_table}.".$va_rels[$vs_left_table][$vs_right_table][0][1]." = ".("{$vs_left_table}.".$va_rels[$vs_left_table][$vs_right_table][0][0]);
								}
								$vs_left_table = $vs_right_table;
								$vn_cj++;
							}
						
							// Next we rewrite the key we're pulling to be from our subject
							$vs_direct_sql_query = str_replace("SELECT ca.row_id", "SELECT ".$this->opo_datamodel->primaryKey($pn_subject_tablenum, true), $vs_direct_sql_query);
							// Finally we pray
						}
					}
					
					
					if (!$vb_is_blank_search && !$vb_is_not_blank_search) { $vb_is_only_blank_searches = false; }
					
					if ($vn_i == 0) {
						if($vs_direct_sql_query) {
							$vs_direct_sql_query = str_replace('^JOIN', join("\n", $va_join), $vs_direct_sql_query);
							$vs_sql = "INSERT IGNORE INTO {$ps_dest_table} {$vs_direct_sql_query}";
							
							if((strpos($vs_sql, '?') !== false) && (!is_array($pa_direct_sql_query_params) || sizeof($pa_direct_sql_query_params) == 0)) {
								$pa_direct_sql_query_params = array(($vn_direct_sql_target_table_num != $pn_subject_tablenum) ? $vn_direct_sql_target_table_num : (int)$pn_subject_tablenum);
							}
						} else {
							$vs_sql = "
								INSERT IGNORE INTO {$ps_dest_table}
								SELECT swi.row_id, SUM(swi.boost)
								FROM ca_sql_search_word_index swi
								".((!$vb_is_only_blank_searches) ? "INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id" : '')."
								WHERE
									{$vs_sql_where}
									AND
									swi.table_num = ?
									{$vs_rel_type_id_sql}
									".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
								GROUP BY swi.row_id
							";
							$pa_direct_sql_query_params = array((int)$pn_subject_tablenum);
						}

						
						if ((($vn_num_terms = (sizeof($va_ft_terms) + sizeof($va_ft_like_terms) + sizeof($va_ft_stem_terms))) > 1) && (!$vs_direct_sql_query)){
							$vs_sql .= " HAVING count(distinct sw.stem) >= {$vn_num_terms}";
						}
						
						$t = new Timer();
						$pa_direct_sql_query_params = is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array();
						if(strpos($vs_sql, '?') === false) { $pa_direct_sql_query_params = array(); }
						$this->opo_db->query($vs_sql, $pa_direct_sql_query_params);
						
						$vn_i++;
						if ($this->debug) { Debug::msg('FIRST: '.$vs_sql." [$pn_subject_tablenum] ".$t->GetTime(4)); }
					} else {
						switch($vs_op) {
							case 'AND':
								if ($vs_direct_sql_query) {
									if ($vn_direct_sql_target_table_num != $pn_subject_tablenum) {
										array_push($va_join, "INNER JOIN {$ps_dest_table} AS ftmp1 ON ftmp1.row_id = ".$this->opo_datamodel->primaryKey($pn_subject_tablenum, true));
									} else {
										array_unshift($va_join, "INNER JOIN {$ps_dest_table} AS ftmp1 ON ftmp1.row_id = ca.row_id");
									}
									$vs_direct_sql_query = str_replace('^JOIN', join("\n", $va_join), $vs_direct_sql_query);
									
									if((strpos($vs_direct_sql_query, '?') !== false)) {
										$pa_direct_sql_query_params = array(($vn_direct_sql_target_table_num != $pn_subject_tablenum) ? $vn_direct_sql_target_table_num : (int)$pn_subject_tablenum);
									}
								}
								$vs_sql = ($vs_direct_sql_query) ? "{$vs_direct_sql_query}" : "
									SELECT swi.row_id
									FROM ca_sql_search_word_index swi
									".((!$vb_is_only_blank_searches) ? "INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id" : '')."
									WHERE
										{$vs_sql_where}
										AND
										swi.table_num = ?
										{$vs_rel_type_id_sql}
										".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
									GROUP BY swi.row_id
								";
								
								if (($vn_num_terms = (sizeof($va_ft_terms) + sizeof($va_ft_like_terms) + sizeof($va_ft_stem_terms))) > 1) {
									$vs_sql .= " HAVING count(distinct sw.stem) >= {$vn_num_terms}";
								}
								
								$t = new Timer();
								
								$pa_direct_sql_query_params = is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array((int)$pn_subject_tablenum);
								if(strpos($vs_sql, '?') === false) { $pa_direct_sql_query_params = array(); }
								$qr_res = $this->opo_db->query($vs_sql, $pa_direct_sql_query_params);
								
								if ($this->debug) { Debug::msg('AND: '.$vs_sql. ' '.$t->GetTime(4). ' '.$qr_res->numRows()); }
						
								if (is_array($va_ids = $qr_res->getAllFieldValues(($vs_direct_sql_query && ($vn_direct_sql_target_table_num != $pn_subject_tablenum)) ? $this->opo_datamodel->primaryKey($pn_subject_tablenum) : 'row_id')) && sizeof($va_ids)) {
									
									$vs_sql = "DELETE FROM {$ps_dest_table} WHERE row_id NOT IN (?)";
									$qr_res = $this->opo_db->query($vs_sql, array($va_ids));
									if ($this->debug) { Debug::msg('AND DELETE: '.$vs_sql. ' '.$t->GetTime(4)); }
								} else { // we don't have any results left, ie. our AND query should yield an empty result
									$this->opo_db->query("DELETE FROM {$ps_dest_table}");
								}
								
								$vn_i++;
								break;
							case 'NOT':
								if ($vs_direct_sql_query) {
									$vs_direct_sql_query = str_replace('^JOIN', join("\n", $va_join), $vs_direct_sql_query);
									$pa_direct_sql_query_params = array(($vn_direct_sql_target_table_num != $pn_subject_tablenum) ? $vn_direct_sql_target_table_num : (int)$pn_subject_tablenum);
								}
								
								$vs_sql = "
									SELECT swi.row_id
									FROM ca_sql_search_word_index swi
									".((!$vb_is_only_blank_searches) ? "INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id" : '')."
									WHERE 
										".($vs_sql_where ? "{$vs_sql_where} AND " : "")." swi.table_num = ? 
										{$vs_rel_type_id_sql}
										".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '');
								
								$pa_direct_sql_query_params = is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array((int)$pn_subject_tablenum);
								if(strpos($vs_sql, '?') === false) { $pa_direct_sql_query_params = array(); }
								$qr_res = $this->opo_db->query($vs_sql, $pa_direct_sql_query_params);
								$va_ids = $qr_res->getAllFieldValues(($vs_direct_sql_query && ($vn_direct_sql_target_table_num != $pn_subject_tablenum)) ? $this->opo_datamodel->primaryKey($pn_subject_tablenum) : 'row_id');
								
								if (sizeof($va_ids) > 0) {
									$vs_sql = "
										DELETE FROM {$ps_dest_table} 
										WHERE 
											row_id IN (?)
									";
									if ($this->debug) { Debug::msg('NOT '.$vs_sql); }
									$qr_res = $this->opo_db->query($vs_sql, array($va_ids));
								}
								
								$vn_i++;
								break;
							default:
							case 'OR':
								if ($vs_direct_sql_query) {
									$vs_direct_sql_query = str_replace('^JOIN', join("\n", $va_join), $vs_direct_sql_query);
									$pa_direct_sql_query_params = array(($vn_direct_sql_target_table_num != $pn_subject_tablenum) ? $vn_direct_sql_target_table_num : (int)$pn_subject_tablenum);
								}
								$vs_sql = ($vs_direct_sql_query) ? "INSERT IGNORE INTO {$ps_dest_table} {$vs_direct_sql_query}" : "
									INSERT IGNORE INTO {$ps_dest_table}
									SELECT swi.row_id, SUM(swi.boost)
									FROM ca_sql_search_word_index swi
									".((!$vb_is_only_blank_searches) ? "INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id" : '')."
									WHERE
										{$vs_sql_where}
										AND
										swi.table_num = ?
										{$vs_rel_type_id_sql}
										".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
									GROUP BY
										swi.row_id
								";
	
								if ($this->debug) { Debug::msg('OR '.$vs_sql); }
								
								$vn_i++;
								
								$pa_direct_sql_query_params = is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array((int)$pn_subject_tablenum);
								if(strpos($vs_sql, '?') === false) { $pa_direct_sql_query_params = array(); }
								$qr_res = $this->opo_db->query($vs_sql, $pa_direct_sql_query_params);
								break;
						}
					}

					// Drop any temporary tables created by direct search queries					
					foreach(array_keys($va_direct_query_temp_tables) as $vs_temp_table_to_drop) {
						$this->_dropTempTable($vs_temp_table_to_drop);
					}
							
					break;
				default:
					//print get_class($o_lucene_query_element);
					break;
			}
		}	
	}
	# -------------------------------------------------------
	# Indexing
	# -------------------------------------------------------
	public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$this->_setMode('indexing');
		
		if ($this->debug) { Debug::msg("[SqlSearchDebug] startRowIndexing: $pn_subject_tablenum/$pn_subject_row_id"); }

		$this->opn_indexing_subject_tablenum = $pn_subject_tablenum;
		$this->opn_indexing_subject_row_id = $pn_subject_row_id;
	}
	# -------------------------------------------------------
	public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options) {
		if (!is_array($pa_options)) { $pa_options = []; }
		
		if (!is_array($pm_content)) {
			$pm_content = [$pm_content];
		}
		
		$vn_boost = 1;
		if (isset($pa_options['BOOST'])) {
			$vn_boost = intval($pa_options['BOOST']);
		}
		
		if ($this->debug) { Debug::msg("[SqlSearchDebug] indexField: $pn_content_tablenum/$ps_content_fieldname [$pn_content_row_id] =&gt; $pm_content"); }
	
		if (in_array('DONT_TOKENIZE', array_values($pa_options), true)) { 
			$pa_options['DONT_TOKENIZE'] = true;  
		} elseif (!isset($pa_options['DONT_TOKENIZE'])) { 
			$pa_options['DONT_TOKENIZE'] = false; 
		}
		
		$vb_force_tokenize = (in_array('TOKENIZE', array_values($pa_options), true) || isset($pa_options['TOKENIZE']));
		$vb_tokenize = $pa_options['DONT_TOKENIZE'] ? false : true;
		
		$vn_rel_type_id = (isset($pa_options['relationship_type_id']) && ($pa_options['relationship_type_id'] > 0)) ? (int)$pa_options['relationship_type_id'] : 0;
		
		if (!isset($pa_options['PRIVATE'])) { $pa_options['PRIVATE'] = 0; }
		if (in_array('PRIVATE', $pa_options, true)) { $pa_options['PRIVATE'] = 1; }
		$vn_private = $pa_options['PRIVATE'] ? 1 : 0;
		
		if (!isset($pa_options['datatype'])) { $pa_options['datatype'] = null; }
		
		if ($ps_content_fieldname[0] == 'A') {
			$vn_field_num_proc = (int)substr($ps_content_fieldname, 1);
			
			// do we need to index this (don't index attribute types that we'll search directly)
			if (WLPlugSearchEngineSqlSearch::$s_metadata_elements[$vn_field_num_proc]) {
				switch(WLPlugSearchEngineSqlSearch::$s_metadata_elements[$vn_field_num_proc]['datatype']) {
					case __CA_ATTRIBUTE_VALUE_CONTAINER__:	
					case __CA_ATTRIBUTE_VALUE_GEOCODE__:	
					case __CA_ATTRIBUTE_VALUE_CURRENCY__:
					case __CA_ATTRIBUTE_VALUE_LENGTH__:
					case __CA_ATTRIBUTE_VALUE_WEIGHT__:
					case __CA_ATTRIBUTE_VALUE_TIMECODE__:
					case __CA_ATTRIBUTE_VALUE_MEDIA__:
					case __CA_ATTRIBUTE_VALUE_FILE__:
						return;
				}
			}
		} 
		
		if ((!is_array($pm_content) && !strlen($pm_content)) || !sizeof($pm_content) || (((sizeof($pm_content) == 1) && strlen((string)$pm_content[0]) == 0))) { 
			$va_words = null;
		} else {
			// Tokenize string
			$va_words = [];
			if ($vb_tokenize || $vb_force_tokenize) {
				foreach($pm_content as $ps_content) {
					$va_words = array_merge($va_words, $this->_tokenize((string)$ps_content));
				}
			}
			if (!$vb_tokenize) { $va_words = array_merge($va_words, $pm_content); }
		}
		
		$vb_incremental_reindexing = (bool)$this->can('incremental_reindexing');
		
		if (!defined("__CollectiveAccess_IS_REINDEXING__") && $vb_incremental_reindexing) {
			$this->removeRowIndexing($this->opn_indexing_subject_tablenum, $this->opn_indexing_subject_row_id, $pn_content_tablenum, array($ps_content_fieldname), $pn_content_row_id, $vn_rel_type_id);
		}
		if (!$va_words) {
			$this->opa_doc_content_buffer[] = '('.$this->opn_indexing_subject_tablenum.','.$this->opn_indexing_subject_row_id.','.$pn_content_tablenum.',\''.$ps_content_fieldname.'\','.$pn_content_row_id.',0,0,'.$vn_private.','.$vn_rel_type_id.')';
		} else {
			foreach($va_words as $vs_word) {
				if(!strlen($vs_word)) { continue; }
				if (!($vn_word_id = (int)$this->getWordID($vs_word))) { continue; }
			
				$this->opa_doc_content_buffer[] = '('.$this->opn_indexing_subject_tablenum.','.$this->opn_indexing_subject_row_id.','.$pn_content_tablenum.',\''.$ps_content_fieldname.'\','.$pn_content_row_id.','.$vn_word_id.','.$vn_boost.','.$vn_private.','.$vn_rel_type_id.')';
			}
		}
	}
	# ------------------------------------------------
	public function commitRowIndexing() {
		if (sizeof($this->opa_doc_content_buffer) > $this->getOption('maxIndexingBufferSize')) {
			$this->flushContentBuffer();
		}
	}
	# ------------------------------------------------
	public function flushContentBuffer() {
		// add fields to doc
		$vn_max_word_segment_size = (int)$this->getOption('maxWordIndexInsertSegmentSize');
		
		// add new indexing
		if (is_array($this->opa_doc_content_buffer) && sizeof($this->opa_doc_content_buffer)) {
			while(sizeof($this->opa_doc_content_buffer) > 0) {
				if (defined("__CollectiveAccess_IS_REINDEXING__")) {
					$this->opo_db->query("SET unique_checks=0");
					$this->opo_db->query("SET foreign_key_checks=0");
				}
				$this->opo_db->query($this->ops_insert_word_index_sql."\n".join(",", array_splice($this->opa_doc_content_buffer, 0, $vn_max_word_segment_size)));
				if (defined("__CollectiveAccess_IS_REINDEXING__")) {
					$this->opo_db->query("SET unique_checks=1");
					$this->opo_db->query("SET foreign_key_checks=1");
				}
			}
			if ($this->debug) { Debug::msg("[SqlSearchDebug] Commit row indexing"); }
		}
	
		// clean up
		$this->opa_doc_content_buffer = null;
		$this->opa_doc_content_buffer = array();
		$this->_checkWordCacheSize();
	}
	# ------------------------------------------------
	public function getWordID($ps_word) {
		$ps_word = (string)$ps_word;
		//$ps_word =  preg_replace('/[[:^print:]]/', '', $ps_word);
		
		//reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
		$ps_word = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
		 '|[\x00-\x7F][\x80-\xBF]+'.
		 '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
		 '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
		 '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
		 '?', $ps_word);

		//reject overly long 3 byte sequences and UTF-16 surrogates and replace with ?
		$ps_word = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.
		 '|\xED[\xA0-\xBF][\x80-\xBF]/S','?', $ps_word);
		 
		if (!strlen($ps_word = trim(mb_strtolower($ps_word, "UTF-8")))) { return null; }
		if (mb_strlen($ps_word) > 255) { $ps_word = mb_substr($ps_word, 0, 255); }
		if (isset(WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word])) { return (int)WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word]; } 
		
		if ($qr_res = $this->opqr_lookup_word->execute($ps_word)) {
			if ($qr_res->nextRow()) {
				return WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word] = (int)$qr_res->get('word_id', array('binary' => true));
			}
		}
		
		// insert word
		if (!($vs_stem = trim($this->opo_stemmer->stem($ps_word)))) { $vs_stem = $ps_word; }
		if (mb_strlen($vs_stem) > 255) { $vs_stem = mb_substr($vs_stem, 0, 255); }
		
		$this->opqr_insert_word->execute($ps_word, $vs_stem);
		if ($this->opqr_insert_word->numErrors()) { return null; }
		if (!($vn_word_id = (int)$this->opqr_insert_word->getLastInsertID())) { return null; }
		
		// create ngrams
		// 		$va_ngrams = caNgrams($ps_word, 4);
		// 		$vn_seq = 0;
		// 		
		// 		$va_ngram_buf = array();
		// 		foreach($va_ngrams as $vs_ngram) {
		// 			$va_ngram_buf[] = "({$vn_word_id},'{$vs_ngram}',{$vn_seq})";
		// 			$vn_seq++;
		// 		}
		// 		
		// 		if (sizeof($va_ngram_buf)) {
		// 			$vs_sql = $this->ops_insert_ngram_sql."\n".join(",", $va_ngram_buf);
		// 			$this->opo_db->query($vs_sql);
		// 		}
		
		return WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word] = $vn_word_id;
	}
	# ------------------------------------------------
	private function _checkWordCacheSize() {
		if ((sizeof(WLPlugSearchEngineSqlSearch::$s_word_cache)) > ($vn_max_size = $this->getOption('maxWordCacheSize'))) {
			WLPlugSearchEngineSqlSearch::$s_word_cache = array_slice(WLPlugSearchEngineSqlSearch::$s_word_cache, 0, $vn_max_size * $this->getOption('cacheCleanFactor'), true);
		}
	}
	# ------------------------------------------------
	public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id, $pn_field_tablenum=null, $pa_field_nums=null, $pn_field_row_id=null, $pn_rel_type_id=null) {

		//print "[SqlSearchDebug] removeRowIndexing: $pn_subject_tablenum/$pn_subject_row_id<br>\n";
		if (!$pn_rel_type_id) { $pn_rel_type_id = 0; }
		
		// remove dependent row indexing
		if ($pn_subject_tablenum && $pn_subject_row_id &&  !is_null($pn_field_tablenum) && !is_null($pn_field_row_id) && is_array($pa_field_nums) && sizeof($pa_field_nums)) {
			foreach($pa_field_nums as $pn_field_num) {
				if(!$pn_field_num) { continue; }
				//print "DELETE ROW WITH FIELD NUM $pn_subject_tablenum/$pn_subject_row_id/$pn_field_tablenum/$pn_field_num/$pn_field_row_id<br>";
				$this->opqr_delete_with_field_row_id_and_num->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id, (int)$pn_field_tablenum, (string)$pn_field_num, (int)$pn_field_row_id, $pn_rel_type_id);
			}
			return true;
		} else {
			if ($pn_subject_tablenum && $pn_subject_row_id && !is_null($pn_field_tablenum) && !is_null($pn_field_row_id)) {
				//print "DELETE ROW $pn_subject_tablenum/$pn_subject_row_id/$pn_field_tablenum/$pn_field_row_id<br>";
				return $this->opqr_delete_with_field_row_id->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id, (int)$pn_field_tablenum, (int)$pn_field_row_id, $pn_rel_type_id);
			} else {
				if (!is_null($pn_field_tablenum) && is_array($pa_field_nums) && sizeof($pa_field_nums)) {
					foreach($pa_field_nums as $pn_field_num) {
						if(!$pn_field_num) { continue; }
						//print "DELETE FIELD $pn_subject_tablenum/$pn_subject_row_id/$pn_field_tablenum/$pn_field_num<br>";
						$this->opqr_delete_with_field_num->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id, (int)$pn_field_tablenum, (string)$pn_field_num, $pn_rel_type_id);
					}
					return true;
				} else {
					if (!$pn_subject_tablenum && !$pn_subject_row_id && !is_null($pn_field_tablenum) && !is_null($pn_field_row_id)) {
						//print "DELETE DEP $pn_field_tablenum/$pn_field_row_id<br>";
						$this->opqr_delete_dependent_sql->execute((int)$pn_field_tablenum, (int)$pn_field_row_id, $pn_rel_type_id);
					} else {
						//print "DELETE ALL $pn_subject_tablenum/$pn_subject_row_id<br>";
						return $this->opqr_delete->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id, $pn_rel_type_id);
					}
				}
			}
		}
	}
	# ------------------------------------------------
	/**
	 *
	 *
	 * @param int $pn_subject_tablenum
	 * @param array $pa_subject_row_ids
	 * @param int $pn_content_tablenum
	 * @param string $ps_content_fieldnum
	 * @param int $pn_content_row_id
	 * @param string $ps_content
	 * @param array $pa_options
	 *		literalContent = array of text content to be applied without tokenization
	 *		BOOST = Indexing boost to apply
	 *		PRIVATE = Set indexing to private
	 */
	public function updateIndexingInPlace($pn_subject_tablenum, $pa_subject_row_ids, $pn_content_tablenum, $ps_content_fieldnum, $pn_content_row_id, $ps_content, $pa_options=null) {
		
		// Find existing indexing for this subject and content 	
		foreach($pa_subject_row_ids as $vn_subject_row_id) {
			$this->removeRowIndexing($pn_subject_tablenum, $vn_subject_row_id, $pn_content_tablenum, array($ps_content_fieldnum), $pn_content_row_id, caGetOption('relationship_type_id', $pa_options, null));
		}
		
		if (caGetOption("DONT_TOKENIZE", $pa_options, false) || in_array('DONT_TOKENIZE', $pa_options, true)) {
			$va_words = array($ps_content);
		} else {
			$va_words = $this->_tokenize($ps_content);
		}
		
		if (caGetOption("INDEX_AS_IDNO", $pa_options, false) || in_array('INDEX_AS_IDNO', $pa_options, true)) {
			$t_content = $this->opo_datamodel->getInstanceByTableNum($pn_content_tablenum, true);
			if (method_exists($t_content, "getIDNoPlugInInstance") && ($o_idno = $t_content->getIDNoPlugInInstance())) {
				$va_values = $o_idno->getIndexValues($ps_content);
				$va_words += $va_values;
			}
		}
		
		$va_literal_content = caGetOption("literalContent", $pa_options, null);
		if ($va_literal_content && !is_array($va_literal_content)) { $va_literal_content = array($va_literal_content); }
		
		$vn_boost = 1;
		if (isset($pa_options['BOOST'])) {
			$vn_boost = intval($pa_options['BOOST']);
		}
		
		if (!isset($pa_options['PRIVATE'])) { $pa_options['PRIVATE'] = 0; }
		if (in_array('PRIVATE', $pa_options, true)) { $pa_options['PRIVATE'] = 1; }
		$vn_private = $pa_options['PRIVATE'] ? 1 : 0;
		
		$vn_rel_type_id = (int)caGetOption('relationship_type_id', $pa_options, 0);
		
		$va_row_insert_sql = array();
		
		$pn_subject_tablenum = (int)$pn_subject_tablenum;
		$vn_row_id = (int)$vn_row_id;
		$pn_content_tablenum = (int)$pn_content_tablenum;
		$pn_content_row_id = (int)$pn_content_row_id;
		$vn_boost = (int)$vn_boost;
		$vn_access = (int)$vn_access;
		
		
		foreach($pa_subject_row_ids as $vn_row_id) {
			if (!$vn_row_id) { 
				if ($this->debug) { Debug::msg("[SqlSearchDebug] Cannot index row because row id is missing!"); }
				continue; 
			}
			$vn_seq = 0;
			foreach($va_words as $vs_word) {
				if (!($vn_word_id = $this->getWordID($vs_word))) { continue; }
				$va_row_insert_sql[] = "({$pn_subject_tablenum}, {$vn_row_id}, {$pn_content_tablenum}, '{$ps_content_fieldnum}', {$pn_content_row_id}, {$vn_word_id}, {$vn_boost}, {$vn_private}, {$vn_rel_type_id})";
				$vn_seq++;
			}
			
			if (is_array($va_literal_content)) {
				foreach($va_literal_content as $vs_literal) {
					if (!($vn_word_id = $this->getWordID($vs_literal))) { continue; }
					$va_row_insert_sql[] = "({$pn_subject_tablenum}, {$vn_row_id}, {$pn_content_tablenum}, '{$ps_content_fieldnum}', {$pn_content_row_id}, {$vn_word_id}, {$vn_boost}, {$vn_private}, {$vn_rel_type_id})";
					$vn_seq++;
				}
			}
		}
		
		// do insert
		if (sizeof($va_row_insert_sql)) {
			$vs_sql = $this->ops_insert_word_index_sql."\n".join(",", $va_row_insert_sql);
			$this->opo_db->query($vs_sql);
			if ($this->debug) { Debug::msg("[SqlSearchDebug] Commit row indexing"); }
		}				
	}
	# -------------------------------------------------
	/**
	 * Not supported in this engine - does nothing
	 */
	public function optimizeIndex($pn_tablenum) {
		// noop	
	}
	# --------------------------------------------------
	public function engineName() {
		return 'SqlSearch';
	}
	# --------------------------------------------------
	private function _tokenize($ps_content, $pb_for_search=false, $pn_index=0) {
		$ps_content = preg_replace('![\']+!', '', $ps_content);		// strip apostrophes for compatibility with SearchEngine class, which does the same to all search expressions
		
		if ($pb_for_search) {
			if ($pn_index == 0) {
				if (is_array($this->opa_asis_regexes)) {
					foreach($this->opa_asis_regexes as $vs_asis_regex) {
						if (preg_match('!'.$vs_asis_regex.'!', $ps_content)) {
							return array($ps_content);
						}
					}
				}
			}
		
			return preg_split('![ ]+!', trim(preg_replace("%((?<!\d)[".$this->ops_search_tokenizer_regex."]+|[".$this->ops_search_tokenizer_regex."]+(?!\d))%u", ' ', strip_tags($ps_content))));
		} else {
			return preg_split('![ ]+!', trim(preg_replace("%((?<!\d)[".$this->ops_search_tokenizer_regex."]+|[".$this->ops_search_tokenizer_regex."]+(?!\d))%u", ' ', strip_tags($ps_content))));
		}
	}
	# --------------------------------------------------
	/**
	 * Performs the quickest possible search on the index for the specfied table_num in $pn_table_num
	 * using the text in $ps_search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back quickly. 
	 * quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is critical
	 * and the ability to control search parameters is not required.
	 *
	 * @param $pn_table_num - The table index to search on
	 * @param $ps_search - The text to search on
	 * @param $pa_options - an optional associative array specifying search options. Supported options are: 'limit' (the maximum number of results to return)
	 *
	 * @return Array - an array of results is returned keyed by primary key id. The array values boolean true. This is done to ensure no duplicate row_ids
	 * 
	 */
	public function quickSearch($pn_table_num, $ps_search, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vs_limit_sql = '';
		if (isset($pa_options['limit']) && ($pa_options['limit'] > 0)) { 
			$vs_limit_sql = 'LIMIT '.$pa_options['limit'];
		}
		
		$va_hits = array();
		$va_words = $this->_tokenize($ps_search, true);
		
		if (sizeof($va_words)) {
			$va_quoted_words = array();
			foreach($va_words as $vs_word) {
				$va_quoted_words[] = $this->opo_db->escape($vs_word);
			}
			$qr_res = $this->opo_db->query("
				SELECT swi.row_id
				FROM ca_sql_search_words sw
				INNER JOIN ca_sql_search_word_index AS swi ON swi.word_id = sw.word_id
				WHERE
					sw.word IN (?)
					AND
					swi.table_num = ?
					".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
				GROUP BY swi.row_id
				ORDER BY sum(swi.boost) DESC
				{$vs_limit_sql}
			", array($va_quoted_words, $pn_table_num));
			
			$va_hits = $qr_res->getAllFieldValues('row_id');
		}
		return $va_hits;
	}
	# --------------------------------------------------
	# Spell correction/"Did you mean?"
	# --------------------------------------------------
	/**
	 * Return list of suggested searches that will find something, based upon the specified search expression
	 *
	 * @param string $ps_text The search expression
	 * @param array $pa_options Options are:
	 *		returnAsLink = return suggestions as links to full-text searces. [Default is no]
	 *		request = the current request; required if links are to be generated using returnAsLink. [Default is null]
	 *		table = the name or number of the table to restrict searches to. If you pass, for example, "ca_objects" search expressions specifically for object searches will be returned. [Default is null]
	 * @return array List of suggested searches
	 */
	public function suggest($ps_text, $pa_options=null) {
		$o_dm = Datamodel::load();
		$va_tokens = $this->_tokenize($ps_text);
		
		$pm_table = caGetOption('table', $pa_options, null);
		$vn_table_num = $pm_table ? $o_dm->getTableNum($pm_table) : null;
		
		$va_word_ids = array();
		foreach($va_tokens as $vn_i => $vs_token) {
			if(preg_match("![\d]+!", $vs_token)) { continue; } // don't try to match if there are numbers
			
			// set ngram length based upon length of word
			// shorter words require shorter ngrams to detect similarity
			$vn_token_len = strlen($vs_token);
			if ($vn_token_len <= 8) {
				$vn_ngram_len = 2;
			} elseif($vn_token_len <= 11) {
				$vn_ngram_len = 3;	
			} else {
				$vn_ngram_len = 4;
			}
			
			$va_ngrams = caNgrams($vs_token, $vn_ngram_len);
			
			
			$vs_table_sql = $vn_table_num ? 'AND swi.table_num = ?' : '';
		
			if (!is_array($va_ngrams) || !sizeof($va_ngrams)) { continue; }
			$vn_num_ngrams = sizeof($va_ngrams);
			// Look for items with the most shared ngrams
			
			$va_params = array($va_ngrams);
			//if ($vn_table_num) { $va_params[] = $vn_table_num; }
			$qr_res = $this->opo_db->query("
				SELECT ng.word_id, sw.word, count(*) sc
				FROM ca_sql_search_ngrams ng
				INNER JOIN ca_sql_search_words AS sw ON sw.word_id = ng.word_id
				WHERE
					ng.ngram IN (?)
				GROUP BY ng.word_id, sw.word
				ORDER BY (length(sw.word) - (count(*) * {$vn_ngram_len})), (".($vn_ngram_len * $vn_num_ngrams).") - ((count(*) * {$vn_ngram_len}))
				LIMIT 250
			", $va_params);
			$va_word_ids[$vn_i] = array();
			$vn_c = 0;
			
			// Check ngram results using various techniques to find most relevant hits
			$vs_token_metaphone = metaphone($vs_token);
			while($qr_res->nextRow()) {
				$vs_word = $qr_res->get('word');
				if(preg_match("![^A-Za-z ]+!", $vs_word)) { continue; } 	// skip anything that is not entirely letters and space
				$vn_word_id = $qr_res->get('word_id');
				
				// Is it an exact match?
				if ($vs_word == $vs_token) {
					$va_word_ids[$vn_i][$vn_word_id] = -250;
					$vn_c++;
					continue;
				}
				
				// Does it sound like the word we're looking for (in English at least)
				if (metaphone($vs_word) == $vs_token_metaphone) {
					$va_word_ids[$vn_i][$vn_word_id] = -150;
					$vn_c++;
					continue;
				}
				
				// Is it close to what we're looking for distance-wise?
				if (strpos($vs_word, $vs_token) === false) { 
					if (($vn_score = levenshtein($vs_word, $vs_token)) > 3) { continue; }
				} else {
					$vn_score -= 150;
				}
				
				// does it begin with the same character?
				for($i=1; $i <= mb_strlen($vs_word); $i++) {
					if (mb_substr($vs_word, 0, $i) === mb_substr($vs_token, 0, $i)) {
						$vn_score -= 25;
					} else {
						break;
					}
				}
				$va_word_ids[$vn_i][$vn_word_id] = $vn_score;
				$vn_c++;
			
				//if ($vn_c > 25) { break; }	// give up when we're found 500 possible hits
			}
		}
		
		$va_temp_tables = array();
		$vn_w = 0;
		if (!is_array($va_word_ids) || !sizeof($va_word_ids)) {
			return array();
		}
		
		// Look for phrases that use any sequence of matched words in proper order
		//
		if (sizeof($va_word_ids) > 1) {
			foreach($va_word_ids as $vn_i => $va_word_list) {
				if (!sizeof($va_word_list)) { continue; }
				asort($va_word_list, SORT_NUMERIC);
				$va_word_list = array_keys(array_slice($va_word_list, 0, 30, true));
				$vn_w++;
				$vs_temp_table = 'ca_sql_search_suggest_'.md5("/".$vn_i."/".print_R($va_word_list, true));
				$this->_createTempTable($vs_temp_table);
			
				$vs_sql = "
					INSERT INTO {$vs_temp_table}
					SELECT swi.index_id + 1, 1
					FROM ca_sql_search_word_index swi
					".(sizeof($va_temp_tables) ? " INNER JOIN ".$va_temp_tables[sizeof($va_temp_tables) - 1]." AS tt ON swi.index_id = tt.row_id" : "")."
					WHERE 
						swi.word_id IN (?) {$vs_table_sql}
						".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
				";
			
				$va_params = array($va_word_list);
				if ($vn_table_num) { $va_params[] = $vn_table_num; }
			
				$qr_res = $this->opo_db->query($vs_sql, $va_params);
			
			
				$va_temp_tables[] = $vs_temp_table;	
			}
		
			if (!sizeof($va_temp_tables)) { return array(); }
		
			// Get most relevant phrases from index
			//
			$vs_results_table = array_pop($va_temp_tables);
			$qr_result = $this->opo_db->query("SELECT * FROM {$vs_results_table} LIMIT 50");
		
			$va_phrases = array();
			while($qr_result->nextRow()) {
				$va_indices = array();
				$vn_index_id = $qr_result->get('row_id') - 1;
			
				for($i=0; $i < sizeof($va_tokens); $i++) {
					$va_indices[] = $vn_index_id;
					$vn_index_id--;
				}
			
				$qr_phrases = $this->opo_db->query("
					SELECT sw.word, swi.index_id 
					FROM ca_sql_search_words sw
					INNER JOIN ca_sql_search_word_index AS swi ON sw.word_id = swi.word_id
					WHERE
						(swi.index_id IN (?))
				", array($va_indices));
			
				$va_acc = array();
				while($qr_phrases->nextRow()) {
					$va_acc[] = $qr_phrases->get('word');
				}
				$va_phrases[] = join(" ", $va_acc);
			}
		
			foreach($va_temp_tables as $vs_temp_table) {
				$this->_dropTempTable($vs_temp_table);
			}
			$this->_dropTempTable($vs_results_table);
		
			$va_phrases = array_unique($va_phrases);
		} else {
			// handle single word
			if (!sizeof($va_word_ids[0])) { return array(); }
			asort($va_word_ids[0], SORT_NUMERIC);
			$va_word_ids[0] = array_slice($va_word_ids[0], 0, 3, true);
			$qr_phrases = $this->opo_db->query("
				SELECT sw.word
				FROM ca_sql_search_words sw
				WHERE
					(sw.word_id IN (?))
			", array(array_keys($va_word_ids[0])));
		
			$va_phrases = array();
			while($qr_phrases->nextRow()) {
				$va_phrases[] = $qr_phrases->get('word');
			}
		}
		
		if (caGetOption('returnAsLink', $pa_options, false) && ($po_request = caGetOption('request', $pa_options, null))) {
			foreach($va_phrases as $vn_i => $vs_phrase) {
				$va_phrases[$vn_i] = caNavLink($po_request, $vs_phrase, '', '*', '*', 'Index', array('search' => $vs_phrase));
			}
		}
		
		return $va_phrases;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function wordIDsToWords($pa_word_ids) {
		if(!is_array($pa_word_ids) || !sizeof($pa_word_ids)) { return array(); }
	
		$qr_words = $this->opo_db->query("
			SELECT word, word_id FROM ca_sql_search_words WHERE word_id IN (?)
		", array($va_word_ids));
		
		$va_words = array();
		while($qr_words->nextRow()) {
			$va_words[(int)$qr_words->get('word_id')] = $qr_words->get('word');
		}
		return $va_words;
	}
	# --------------------------------------------------
	# Utils
	# --------------------------------------------------
	private function getFieldNum($pn_table_name_or_num, $ps_fieldname) {
		if (isset(WLPlugSearchEngineSqlSearch::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname])) { return WLPlugSearchEngineSqlSearch::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname]; }
		
		$vs_table_name = is_numeric($pn_table_name_or_num) ? $this->opo_datamodel->getTableName((int)$pn_table_name_or_num) : (string)$pn_table_name_or_num;
		return WLPlugSearchEngineSqlSearch::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname] = $this->opo_datamodel->getFieldNum($vs_table_name, $ps_fieldname);
	}
	# -------------------------------------------------------
	private function _filterValueToQueryValue($pa_filter) {
		switch(strtolower($pa_filter['operator'])) {
			case '>':
			case '<':
			case '=':
			case '>=':
			case '<=':
			case '<>':
				return (int)$pa_filter['value'];
				break;
			case 'in':
			case 'not in':
				$va_tmp = explode(',', $pa_filter['value']);
				$va_values = array();
				foreach($va_tmp as $vs_tmp) {
					if ($vs_tmp == 'NULL') { continue; }
					$va_values[] = (int)$vs_tmp;
				}
				return "(".join(",", $va_values).")";
				break;
			case 'is':
			case 'is not':
			default:
				return is_null($pa_filter['value']) ? 'NULL' : (string)$pa_filter['value'];
				break;
		}
	}
	# --------------------------------------------------
}