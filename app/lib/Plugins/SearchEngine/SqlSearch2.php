<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/SqlSearch2.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2021 Whirl-i-Gig
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

 require_once(__CA_LIB_DIR__.'/Plugins/WLPlug.php');
 require_once(__CA_LIB_DIR__.'/Plugins/IWLPlugSearchEngine.php');
 require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/SqlSearchResult.php'); 
 require_once(__CA_LIB_DIR__.'/Search/Common/Stemmer/SnoballStemmer.php');
 require_once(__CA_APP_DIR__.'/helpers/gisHelpers.php');
 require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/BaseSearchPlugin.php');

class WLPlugSearchEngineSqlSearch2 extends BaseSearchPlugin implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	private $indexing_subject_tablenum=null;
	private $indexing_subject_row_id=null;
	
	private $delete_sql;	// sql DELETE statement (for unindexing)
	private $q_delete;		// prepared statement for delete (subject_tablenum and subject_row_id only specified)
	
	private $stemmer;		// snoball stemmer
	private $do_stemming = true;
	
	private $tep;			// date/time expression parse
	
	static $word_cache = array();						// cached word-to-word_id values used when indexing
	static $s_metadata_elements; 						// cached metadata element info
	static $s_fieldnum_cache = array();				// cached field name-to-number values used when indexing
	
	private $ops_insert_word_index_sql = '';
	private $q_lookup_word = null;
	private $ops_insert_word_sql = '';
	private $ops_insert_ngram_sql = '';


	private $ops_delete_with_field_num_sql = "";
	private $q_delete_with_field_num = null;

	private $ops_delete_with_field_row_id_sql = '';
	private $q_delete_with_field_row_id = null;

	private $ops_delete_with_field_row_id_and_num = "";
	private $q_delete_with_field_row_id_and_num = null;

	private $ops_delete_dependent_sql = "";
	private $q_delete_dependent_sql = null;
	
	static private $s_doc_content_buffer = [];			// content buffer used when indexing
	
	# -------------------------------------------------------
	public function __construct($po_db=null) {
		global $g_ui_locale;
		
		parent::__construct($po_db);
		
		$this->tep = new TimeExpressionParser();
		$this->tep->setLanguage($g_ui_locale);
		
		$this->stemmer = new SnoballStemmer();
		$this->do_stemming = (int)trim($this->search_config->get('search_sql_search_do_stemming')) ? true : false;
		
		$this->initDbStatements();
		
		if (!($this->ops_indexing_tokenizer_regex = trim($this->search_config->get('indexing_tokenizer_regex')))) {
			$this-> ops_indexing_tokenizer_regex = "^\pL\pN\pNd/_#\@\&\.";
		}
		if (!($this->ops_search_tokenizer_regex = trim($this->search_config->get('search_tokenizer_regex')))) {
			$this->ops_search_tokenizer_regex = "^\pL\pN\pNd/_#\@\&";
		}
		
		if (!is_array($this->opa_asis_regexes = $this->search_config->getList('asis_regexes'))) {
			$this->opa_asis_regexes = array();
		}
		
		//
		// Load info about metadata elements into static var cache if it hasn't already be fetched
		//
		if (!is_array(WLPlugSearchEngineSqlSearch2::$s_metadata_elements)) {
			WLPlugSearchEngineSqlSearch2::$s_metadata_elements = ca_metadata_elements::getRootElementsAsList();
		}
		$this->debug = false;
	}
	# -------------------------------------------------------
	# Initialization and capabilities
	# -------------------------------------------------------
	public function init() {
		if(($max_indexing_buffer_size = (int)$this->search_config->get('max_indexing_buffer_size')) < 1) {
			$max_indexing_buffer_size = 5000;
		}
		
		$this->options = array(
				'limit' => 2000,											// maximum number of hits to return [default=2000]  ** NOT CURRENTLY ENFORCED -- MAY BE DROPPED **
				'maxIndexingBufferSize' => $max_indexing_buffer_size,	// maximum number of indexed content items to accumulate before writing to the database
				'maxWordIndexInsertSegmentSize' => ceil($max_indexing_buffer_size / 2), // maximum number of word index rows to put into a single insert
				'maxWordCacheSize' => 131072,								// maximum number of words to cache while indexing before purging
				'cacheCleanFactor' => 0.50,									// percentage of words retained when cleaning the cache
				
				'omitPrivateIndexing' => false,								//
				'excludeFieldsFromSearch' => null,
				'restrictSearchToFields' => null,
				'strictPhraseSearching' => true							// strict phrase searching finds only records with the precise phrase; non-strict will find fields with all of the words, in any order
		);
		
		// Defines specific capabilities of this engine and plug-in
		// The indexer and engine can use this information to optimize how they call the plug-in
		$this->capabilities = array(
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
	public function setDb($db) {
		parent::setDb($db);
		$this->initDbStatements();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __destruct() {	
		if (is_array(self::$s_doc_content_buffer) && sizeof(self::$s_doc_content_buffer)) {
			if($this->db && !$this->db->connected()) {
				$this->db->connect();
			}
			$this->flushContentBuffer();
		}
		unset($this->config);
		unset($this->search_config);
		unset($this->db);
		unset($this->tep);
	}
	# -------------------------------------------------------
	# Query
	# -------------------------------------------------------
	/**
	 *
	 */
	public function search(int $subject_tablenum, string $search_expression, array $filters=[], $rewritten_query) {
		$hits = $this->_filterQueryResult(
			$subject_tablenum, 
			$this->_processQuery($subject_tablenum, $rewritten_query), 
			$filters
		);
		
		arsort($hits, SORT_NUMERIC);	// sort by boost

		return new WLPlugSearchEngineSqlSearchResult(array_keys($hits), $subject_tablenum);
	}
	# -------------------------------------------------------
	/**
	 * Dispatch query for processing
	 */
	private function _processQuery(int $subject_tablenum, $query) {
		$qclass = get_class($query);
		
		$row_ids = [];
		switch($qclass) {
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				$row_ids = $this->_processQueryBoolean($subject_tablenum, $query);
				break;
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				$row_ids = $this->_processQueryMultiterm($subject_tablenum, $query);
				break;
			case 'Zend_Search_Lucene_Search_Query_Term':
				$row_ids = $this->_processQueryTerm($subject_tablenum, $query);
				break;
			case 'Zend_Search_Lucene_Index_Term':
				$row_ids = $this->_processQueryTerm($subject_tablenum, $query);
				break;
			case 'Zend_Search_Lucene_Search_Query_Phrase':
				$row_ids = $this->_processQueryPhrase($subject_tablenum, $query);
				break;
			case 'Zend_Search_Lucene_Search_Query_Range':
				$row_ids = $this->_processQueryRange($subject_tablenum, $query);
				break;
			default:
				throw new ApplicationException(_t('Invalid query type: %1', $qclass));
				break;
		}
		
		return $row_ids;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processQueryBoolean(int $subject_tablenum, $query) {
		$signs = $query->getSigns();
	 	$subqueries = $query->getSubqueries();
	 	
	 	$subject_table = Datamodel::getTableName($subject_tablenum);
	 	$pk = Datamodel::primaryKey($subject_tablenum);
	 	
	 	$acc = [];
	 	foreach($subqueries as $i => $subquery) {
	 		$hits = $this->_processQuery($subject_tablenum, $subquery);
	 		$op = $this->_getBooleanOperator($signs, $i);
	 		
	 		switch($op) {
	 			case 'AND':
	 				if ($i == 0) { $acc = $hits; break; }
	 				
	 				$acc = array_intersect_key($acc, $hits);
	 				foreach($acc as $row_id => $boost) {
	 					$acc[$row_id] += $hits[$row_id];	// add boost
	 				}
	 				break;
	 			case 'OR':
	 				if ($i == 0) { $acc = $hits; break; }
	 				$acc = array_replace($hits, $acc);
	 				foreach($acc as $row_id => $boost) {
	 					$acc[$row_id] += $hits[$row_id];	// add boost
	 				}
	 				break;
	 			case 'NOT':
	 				if ($i == 0) {
	 					// TODO: Try to optimize this case by moving it from first position when possible?
	 					// 		 Without anything to diff this with we have to invert the result set, which can potentially 
	 					//		 return a very large result set
	 					$deleted_sql = Datamodel::getFieldNum($subject_tablenum, 'deleted') ? 'deleted = 0 AND ' : '';
	 					if (!sizeof($hits)) { $acc = []; break; }
	 					$qr_res = $this->db->query("
	 						SELECT {$pk} 
	 						FROM {$subject_table} 
	 						WHERE {$deleted_sql} {$pk} NOT IN (?)
	 					", [array_keys($hits)]);
	 					$vals = $qr_res->getAllFieldValues($pk);
	 					
	 					$acc = [];
	 					foreach($vals as $row_id) {
	 						// assume constant boost = 1 here
	 						$acc[$row_id] = 1;
	 					}
	 				} else {
	 					$acc = array_diff_key($acc, $hits);	
	 					foreach($acc as $row_id => $boost) {
							$acc[$row_id] += $hits[$row_id];	// add boost
						}
	 				}
	 				break;
	 			default:
	 				throw new ApplicationException(_t('Invalid boolean operator: %1', $op));
	 				break;	
	 		}
	 		
	 	}
	 	return $acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processQueryMultiterm(int $subject_tablenum, $query) {
		$terms = $query->getTerms();
		
		$acc = [];
	 	foreach($terms as $i => $term) {
	 		$hits = $this->_processQueryTerm($subject_tablenum, $term);
	 		$op = $this->_getBooleanOperator($signs, $i);

	 		switch($op) {
	 			case 'AND':
	 				if ($i == 0) { $acc = $hits; break; }
	 				
	 				$acc = array_intersect_key($acc, $hits);
	 				foreach($acc as $row_id => $boost) {
						$acc[$row_id] += $hits[$row_id];	// add boost
					}
	 				break;
	 			case 'OR':
	 				if ($i == 0) { $acc = $hits; break; }
	 				$acc = array_replace($hits, $acc);
	 				foreach($acc as $row_id => $boost) {
	 					$acc[$row_id] += $hits[$row_id];	// add boost
	 				}
	 				break;
	 			case 'NOT':
	 				if ($i == 0) {
	 					// invert set
	 				} else {
	 					$acc = array_diff_key($acc, $hits);	
	 					foreach($acc as $row_id => $boost) {
							$acc[$row_id] += $hits[$row_id];	// add boost
						}
	 				}
	 				break;
	 			default:
	 				throw new ApplicationException(_t('Invalid boolean operator: %1', $op));
	 				break;	
	 		}
	 		
	 	}
	 	return $acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processQueryTerm(int $subject_tablenum, $query) {
		$qclass = get_class($query);
		$term = ($qclass === 'Zend_Search_Lucene_Search_Query_Term') ? $query->getTerm() : $query;
		
	 	$field = $term->field;
	 	$field_lc = mb_strtolower($field);
	 	$field_elements = explode('.', $field_lc);
	 	if (in_array($field_elements[0], [_t('created'), _t('modified')])) {
	 		return $this->_processQueryChangeLog($subject_tablenum, $term);
	 	}
	 	$text = $term->text;
	 	
	 	$blank_val = caGetBlankLabelText($subject_tablenum);
	 	$is_blank = ((mb_strtolower("[{$blank_val}]") === mb_strtolower($text)) || (mb_strtolower("[BLANK]") === mb_strtolower($text)));
	 	$is_not_blank = (mb_strtolower("["._t('SET')."]") === mb_strtolower($text));
	 	
	 	
	 	if ($this->do_stemming && !$is_blank && !$is_not_blank) {
	 		$text_stem = $this->stemmer->stem($text);
	 		if (($text !== $text_stem) && ($text_stem[strlen($text_stem)-1] !== '*')) { 
	 			$text = $text_stem.'*';
	 		}
	 	}
	 	
	 	$ap = $field ? $this->_getElementIDForAccessPoint($subject_tablenum, $field) : null;
	 	
	 	if (is_array($ap)) {
	 		// Handle datatype-specific queries
	 		$ret = $this->_processMetadataDataType($subject_tablenum, $ap, $query);
	 		if(is_array($ret)) { return $ret; }
	 	}
	 	
	 
	 	$params = [$subject_tablenum];
	 	$word_op = '=';
	 	$word_field = 'sw.word';
	 	if (is_array($ap) && $is_blank) {
	 		$params[] = 0;
	 		$word_field = 'swi.word_id';
	 	} elseif(is_array($ap) && $is_not_blank) {
	 		$word_op = '>';
	 		$params[] = 0;
	 		$word_field = 'swi.word_id';
	 	} elseif ($has_wildcard = ((strpos($text, '*') !== false) || (strpos($text, '?') !== false))) {
	 		$word_op = 'LIKE';
	 		$text = str_replace('*', '%', $text);
	 		$text = str_replace('?', '_', $text);
	 		$params[] = $text;
	 	} else{
	 		$params[] = $text;
	 	}
	 
	 	$field_sql = null;
	 	if (is_array($ap)) {
	 		$field_sql = " AND swi.field_table_num = ? AND swi.field_num = ?";
	 		$params[] = $ap['table_num'];
	 		$params[] = $ap['field_num'];
	 		
	 		if (is_array($ap['relationship_type_ids']) && sizeof($ap['relationship_type_ids'])) {
	 			$field_sql .= " AND swi.rel_type_id IN (?)";
	 			$params[] = $ap['relationship_type_ids'];
	 		}
	 	}
	 	
	 	$private_sql = ($this->getOption('omitPrivateIndexing') ? ' AND swi.access = 0' : '');
	 	
		$qr_res = $this->db->query("
			SELECT swi.row_id, swi.boost
			FROM ca_sql_search_word_index swi
			".(!$is_blank ? 'INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id' : '')."
			WHERE
				swi.table_num = ? AND {$word_field} {$word_op} ?
				{$field_sql}
				{$private_sql}
		", $params);
	 	return $this->_arrayFromDbResult($qr_res);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processQueryPhrase(int $subject_tablenum, $query) {
	 	$terms = $query->getTerms();
	 	$private_sql = ($this->getOption('omitPrivateIndexing') ? ' AND swi.access = 0' : '');
	 	
	 	if ($this->getOption('strictPhraseSearching')) {
	 		$words = [];
	 		$temp_tables = [];
	 		$ap_spec = null;
			foreach($terms as $term) {
				if (!$ap_spec && ($field = $term->field)) { $ap_spec = $field; }
				
				if (strlen($escaped_text = $this->db->escape($term->text))) {
					$words[] = $escaped_text;
				}
			}
		
			if (!sizeof($words)) { return []; }
		
			$ap_tmp = explode(".", $ap_spec);
			$fld_table = $fld_num = null;
			
			$fld_limit_sql = null;
			if(is_array($ap_tmp) && (sizeof($ap_tmp) >= 2)) {
				$ap = $this->_getElementIDForAccessPoint($subject_tablenum, $ap_spec);
				if (is_array($ap)) {
					// Handle datatype-specific queries
					$ret = $this->_processMetadataDataType($subject_tablenum, $ap, $query);
					if(is_array($ret)) { return $ret; }
				}
				if (isset($ap['field_num'], $ap['table_num'])) {
					$fld_num = $ap['field_num'];
					$fld_table_num = $ap['table_num'];
					$fld_limit_sql = " AND (swi.field_table_num = {$fld_table_num} AND swi.field_num = '{$fld_num}')";
					
					if (is_array($ap['relationship_type_ids']) && sizeof($ap['relationship_type_ids'])) {
						$fld_limit_sql .= " AND (swi.rel_type_id IN (".join(",", $ap['relationship_type_ids'])."))";
					}
				}
			}
			
			$w = 0;
	 		foreach($words as $w => $word) {
				$temp_table = 'ca_sql_search_phrase_'.md5("{$subject_tablenum}/{$word}/{$w}");
				$this->_createTempTable($temp_table);
			
				$tc = sizeof($temp_tables);
				$qr_res = $this->db->query("
					INSERT INTO {$temp_table}
					SELECT swi.index_id + 1, 1, null
					FROM ca_sql_search_words sw 
					INNER JOIN ca_sql_search_word_index AS swi ON sw.word_id = swi.word_id 
					".(($tc > 0) ? " INNER JOIN ".$temp_tables[$tc - 1]." AS tt ON swi.index_id = tt.row_id" : "")."
					WHERE 
						sw.word = ? AND swi.table_num = ? {$fld_limit_sql}
						{$private_sql}
				", $word, (int)$subject_tablenum);
				$qr_count = $this->db->query("SELECT count(*) c FROM {$temp_table}");
			
				$temp_tables[] = $temp_table;	
			}
			$results_temp_table = array_pop($temp_tables);
							
			$this->db->query("UPDATE {$results_temp_table} SET row_id = row_id - 1");
			
			$qr_res = $this->db->query("
				SELECT swi.row_id, ca.boost, ca.field_container_id
				FROM {$results_temp_table} ca
				INNER JOIN ca_sql_search_word_index AS swi ON swi.index_id = ca.row_id
			");
			
	 		$hits = $this->_arrayFromDbResult($qr_res);
	 		
			// Clean up temp tables
			foreach($temp_tables as $temp_table) {
				$this->_dropTempTable($temp_table);
			}
			return $hits;
	 	} else {
	 		$acc = [];
			foreach($terms as $i => $term) {
				$hits = $this->_processQueryTerm($subject_tablenum, $term);
				if ($i == 0) { $acc = $hits; continue; }
				$acc = array_intersect_key($acc, $hits);
			}
			return $acc;
	 	}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processQueryChangeLog(int $subject_tablenum, Zend_Search_Lucene_Index_Term $term) {
		$field = $term->field;
	 	$text = $term->text;
	 	
	 	$field_lc = mb_strtolower($field);
	 	$field_elements = explode('.', $field_lc);
	 	if (in_array($field_elements[0], [_t('created'), _t('modified')])) {
	 		if (!$this->tep->parse($text)) { return []; }
	 		$range = $this->tep->getUnixTimestamps();
			$user_id = null;
			$user_sql = '';
			if (sizeof($field_elements) > 1) {
				if (!is_int($field_elements[1])) {
					$t_user = new ca_users();
					if (
						$t_user->load(["user_name" => $field_elements[1]])
						||
						((strpos($field_elements[1], "_") !== false) && $t_user->load(["user_name" => str_replace("_", " ", $field_elements[1])]))
					) {
						$user_id = (int)$t_user->getPrimaryKey();
					}
				} else {
					$user_id = (int)$field_elements[1];
				}
				$user_sql = ($user_id)  ? " AND (ccl.user_id = {$user_id})" : "";
			}

			switch($field_elements[0]) { 
				case _t('created'):
					$qr_res = $this->db->query("
							SELECT ccl.logged_row_id row_id
							FROM ca_change_log ccl
							WHERE
								(ccl.log_datetime BETWEEN ? AND ?)
								AND
								(ccl.logged_table_num = ?)
								AND
								(ccl.changetype = 'I')
								{$user_sql}
						", [(int)$range['start'], (int)$range['end'], $subject_tablenum]);
					break;
				case _t('modified'):
					$qr_res = $this->db->query("
							SELECT ccl.logged_row_id row_id
							FROM ca_change_log ccl
							WHERE
								(ccl.log_datetime BETWEEN ? AND ?)
								AND
								(ccl.logged_table_num = ?)
								AND
								(ccl.changetype = 'U')
								{$user_sql}
						UNION
							SELECT ccls.subject_row_id row_id
							FROM ca_change_log ccl
							INNER JOIN ca_change_log_subjects AS ccls ON ccls.log_id = ccl.log_id
							WHERE
								(ccl.log_datetime BETWEEN ? AND ?)
								AND
								(ccls.subject_table_num = ?)
								{$user_sql}
						", [(int)$range['start'], (int)$range['end'], $subject_tablenum, (int)$range['start'], (int)$range['end'], $subject_tablenum]);
					break;
				default:
					throw new ApplicationException(_t('Invalid change log search mode: %1', $field));
					break;
			}
			
	 		return $this->_arrayFromDbResult($qr_res);
	 	}
	 	return [];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processQueryRange(int $subject_tablenum, Zend_Search_Lucene_Search_Query_Range $query) {
	 	$lower_term = $query->getLowerTerm();
		$upper_term = $query->getUpperTerm();
		$lower_text = $lower_term->text;
		$upper_text = $upper_term->text;
		
		
		$ap = $this->_getElementIDForAccessPoint($subject_tablenum, $lower_term->field);
		if (!is_array($ap)) { return []; }
		
		return $this->_processMetadataDataType($subject_tablenum, $ap, $query);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processMetadataDataType(int $subject_tablenum, array $ap, $query) { 
		$qclass = get_class($query);
		
		$text = $text_upper = null;
		switch($qclass) {
			case 'Zend_Search_Lucene_Search_Query_Term':
				$term = $query->getTerm();
				$text = $term->text;
				break;
			case 'Zend_Search_Lucene_Index_Term':
				$text = $query->text;
				break;
			case 'Zend_Search_Lucene_Search_Query_Phrase':
				$terms = $query->getTerms();
				$text = join(' ', array_map(function($t) { return $t->text;}, $terms));
				break;
			case 'Zend_Search_Lucene_Search_Query_Range':
				$lower_term = $query->getLowerTerm();
				$upper_term = $query->getUpperTerm();
				$text = $lower_term->text;
				$text_upper = $upper_term->text;
				break;
			default:
				throw new ApplicationException(_t('Invalid query passed to _processMetadataDataType: %1', $qclass));
				break;
		}
		
		// is field intrinsic? (dates, integer, numerics can be intrinsic)
		if($ap['type'] === 'INTRINSIC') {
			$field = explode('.', $ap['access_point']);
			$field_name = $field[1];
			$table = $field[0];
			
			if (!($t_instance = Datamodel::getInstance($table, true))) { return []; }
			if(!$t_instance->hasField($field_name)) { return []; }
			$fi = $t_instance->getFieldInfo($field_name);
			
			switch($fi['FIELD_TYPE']) {
				case FT_NUMBER:
					$ap['element_info']['datatype'] = __CA_ATTRIBUTE_VALUE_NUMERIC__;
					break;
				case FT_HISTORIC_DATERANGE:
				case FT_DATERANGE:
					$ap['element_info']['datatype'] = __CA_ATTRIBUTE_VALUE_DATERANGE__;
					break;
				default:
					return null;	// Don't process here - use search index
			}
		}
		
		$qinfo = null;
		switch($ap['element_info']['datatype']) {
	 		case __CA_ATTRIBUTE_VALUE_DATERANGE__:
				$qinfo = $this->_queryForDateRangeAttribute(new DateRangeAttributeValue(), $ap, $text, $text_upper);
				break;
			case __CA_ATTRIBUTE_VALUE_TIMECODE__:
				$qinfo = $this->_queryForNumericAttribute(new TimecodeAttributeValue(), $ap, $text, $text_upper, 'value_decimal1');
				break;
			case __CA_ATTRIBUTE_VALUE_LENGTH__:
				$qinfo = $this->_queryForNumericAttribute(new LengthAttributeValue(), $ap, $text, $text_upper, 'value_decimal1');
				break;
			case __CA_ATTRIBUTE_VALUE_WEIGHT__:
				$qinfo = $this->_queryForNumericAttribute(new WeightAttributeValue(), $ap, $text, $text_upper, 'value_decimal1');
				break;
			case __CA_ATTRIBUTE_VALUE_INTEGER__:
				$qinfo = $this->_queryForNumericAttribute(new NumericAttributeValue(), $ap, $text, $text_upper, 'value_integer1');
				break;
			case __CA_ATTRIBUTE_VALUE_NUMERIC__:
				$qinfo = $this->_queryForNumericAttribute(new NumericAttributeValue(), $ap, $text, $text_upper, 'value_decimal1');
				break;
			case __CA_ATTRIBUTE_VALUE_CURRENCY__:
				$qinfo = $this->_queryForCurrencyAttribute(new CurrencyAttributeValue(), $ap, $text, $text_upper);
				break;
			case __CA_ATTRIBUTE_VALUE_GEOCODE__:
				$qinfo = $this->_queryForGeocodeAttribute(new GeocodeAttributeValue(), $ap, $text, $text_upper);
				break;
		}
		if(is_array($qinfo)) {
			$params = $qinfo['params'];
			if($ap['type'] !== 'INTRINSIC') { array_unshift($params, $subject_tablenum); }
			$qr_res = $this->db->query($qinfo['sql'], $params);
			
			return $this->_arrayFromDbResult($qr_res);
		}
		return null;	// can't process here - try using search index
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _filterQueryResult(int $subject_tablenum, ?array $hits, array $filters) {
		if (is_array($filters) && sizeof($filters) && sizeof($hits)) {
			if (!($t_instance = Datamodel::getInstance($subject_tablenum, true))) {
				throw new ApplicationException(_t('Invalid subject table: %1', $subject_tablenum));
			}
			foreach($filters as $filter) {
				$tmp = explode('.', $filter['field']);
				$path = [];
				$joins = [];
				
				if(!($fi = Datamodel::getInstance($tmp[0], true))) { continue; }
				if(!$fi->hasField($tmp[1])) { continue; }
			
				if ($tmp[0] !== $table_name) {
					$path = Datamodel::getPath($table_name, $tmp[0]);
				} 
				if (is_array($path) && sizeof($path)) {
					$last_table = null;
					// generate related joins
					foreach($path as $table => $va_info) {
						if (!($t_table = Datamodel::getInstance($table, true))) {
							throw new ApplicationException(_t('Invalid path table: %1', $table));
						}
						
						$rels = Datamodel::getOneToManyRelations($last_table, $table);
						if (!sizeof($rels)) {
							$rels = Datamodel::getOneToManyRelations($table, $last_table);
						}
						if ($table == $rels['one_table']) {
							$joins[$table] = "INNER JOIN ".$rels['one_table']." ON ".$rels['one_table'].".".$rels['one_table_field']." = ".$rels['many_table'].".".$rels['many_table_field'];
						} else {
							$joins[$table] = "INNER JOIN ".$rels['many_table']." ON ".$rels['many_table'].".".$rels['many_table_field']." = ".$rels['one_table'].".".$rels['one_table_field'];
						}
						
						$last_table = $table;
					}
					$sql_where = "(".$filter['field']." ".$filter['operator']." ".$this->_filterValueToQueryValue($filter).")";
				} else {
					if(!($t_table = Datamodel::getInstanceByTableName($tmp[0], true))) {
						throw new ApplicationException(_t('Invalid path table: %1', $table));
					}
					$sql_where = "(".$filter['field']." ".$filter['operator']." ".$this->_filterValueToQueryValue($filter).")";
				}
			
				switch($filter['operator']) {
					case 'in':
						if (strpos(strtolower($filter['value']), 'null') !== false) {
							$sql_where = "({$sql_where} OR (".$filter['field']." IS NULL))";
						}
						break;
					case 'not in':
						if (strpos(strtolower($filter['value']), 'null') !== false) {
							$sql_where = "({$sql_where} OR (".$filter['field']." IS NOT NULL))";
						}
						break;
				}
				$wheres[] = $sql_where;
			}
			
			$pk = $t_instance->primaryKey(true);
			$table = $t_instance->tableName();
			$sql_joins = join("\n", $joins);
			
			$qr_res = $this->db->query("
				SELECT {$pk} 
				FROM {$table} 
				{$sql_joins} 
				WHERE {$pk} IN (?) AND ".join(' AND ', $wheres), [array_keys($hits)]);
				
			$filtered_hits = array_flip($qr_res->getAllFieldValues($pk));
			return array_intersect_key($hits, $filtered_hits);
		}
		
		return $hits;
	}
	# -------------------------------------------------------
	# Indexing
	# -------------------------------------------------------
	public function startRowIndexing($subject_tablenum, $pn_subject_row_id) {
		if ($this->debug) { Debug::msg("[SqlSearchDebug] startRowIndexing: $subject_tablenum/$pn_subject_row_id"); }

		$this->indexing_subject_tablenum = $subject_tablenum;
		$this->indexing_subject_row_id = $pn_subject_row_id;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
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
		$vn_container_id = (isset($pa_options['container_id']) && ($pa_options['container_id'] > 0)) ? (int)$pa_options['container_id'] : 'NULL';
		
		if (!isset($pa_options['PRIVATE'])) { $pa_options['PRIVATE'] = 0; }
		if (in_array('PRIVATE', $pa_options, true)) { $pa_options['PRIVATE'] = 1; }
		$vn_private = $pa_options['PRIVATE'] ? 1 : 0;
		
		if (!isset($pa_options['datatype'])) { $pa_options['datatype'] = null; }
		
		if ($ps_content_fieldname[0] == 'A') {
			$vn_field_num_proc = (int)substr($ps_content_fieldname, 1);
			
			// do we need to index this (don't index attribute types that we'll search directly)
			if (WLPlugSearchEngineSqlSearch2::$s_metadata_elements[$vn_field_num_proc]) {
				switch(WLPlugSearchEngineSqlSearch2::$s_metadata_elements[$vn_field_num_proc]['datatype']) {
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
			$this->removeRowIndexing($this->indexing_subject_tablenum, $this->indexing_subject_row_id, $pn_content_tablenum, array($ps_content_fieldname), $pn_content_row_id, $vn_rel_type_id);
		}
		if (!$va_words) {
			self::$s_doc_content_buffer[] = '('.$this->indexing_subject_tablenum.','.$this->indexing_subject_row_id.','.$pn_content_tablenum.',\''.$ps_content_fieldname.'\','.$vn_container_id.','.$pn_content_row_id.',0,0,'.$vn_private.','.$vn_rel_type_id.')';
		} else {
			foreach($va_words as $vs_word) {
				if(!strlen($vs_word)) { continue; }
				if (!($vn_word_id = (int)$this->getWordID($vs_word))) { continue; }
			
				self::$s_doc_content_buffer[] = '('.$this->indexing_subject_tablenum.','.$this->indexing_subject_row_id.','.$pn_content_tablenum.',\''.$ps_content_fieldname.'\','.$vn_container_id.','.$pn_content_row_id.','.$vn_word_id.','.$vn_boost.','.$vn_private.','.$vn_rel_type_id.')';
			}
		}
	}
	# ------------------------------------------------
	public function commitRowIndexing() {
		if (sizeof(self::$s_doc_content_buffer) > $this->getOption('maxIndexingBufferSize')) {
			$this->flushContentBuffer();
		}
	}
	# ------------------------------------------------
	public function flushContentBuffer() {
		// add fields to doc
		$vn_max_word_segment_size = (int)$this->getOption('maxWordIndexInsertSegmentSize');
		
		// add new indexing
		if (is_array(self::$s_doc_content_buffer) && sizeof(self::$s_doc_content_buffer)) {
			while(sizeof(self::$s_doc_content_buffer) > 0) {
				if (defined("__CollectiveAccess_IS_REINDEXING__")) {
					$this->db->query("SET unique_checks=0");
					$this->db->query("SET foreign_key_checks=0");
				}
				$this->db->query($this->ops_insert_word_index_sql."\n".join(",", array_splice(self::$s_doc_content_buffer, 0, $vn_max_word_segment_size)));
				if (defined("__CollectiveAccess_IS_REINDEXING__")) {
					$this->db->query("SET unique_checks=1");
					$this->db->query("SET foreign_key_checks=1");
				}
			}
			if ($this->debug) { Debug::msg("[SqlSearchDebug] Commit row indexing"); }
		}
	
		// clean up
		self::$s_doc_content_buffer = [];
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
		if (isset(WLPlugSearchEngineSqlSearch2::$word_cache[$ps_word])) { return (int)WLPlugSearchEngineSqlSearch2::$word_cache[$ps_word]; } 
		
		if ($qr_res = $this->q_lookup_word->execute($ps_word)) {
			if ($qr_res->nextRow()) {
				return WLPlugSearchEngineSqlSearch2::$word_cache[$ps_word] = (int)$qr_res->get('word_id', array('binary' => true));
			}
		}
		
		try {
            // insert word
            if (!($vs_stem = trim($this->stemmer->stem($ps_word)))) { $vs_stem = $ps_word; }
            if (mb_strlen($vs_stem) > 255) { $vs_stem = mb_substr($vs_stem, 0, 255); }
        
            $this->opqr_insert_word->execute($ps_word, $vs_stem);
            if ($this->opqr_insert_word->numErrors()) { return null; }
            if (!($vn_word_id = (int)$this->opqr_insert_word->getLastInsertID())) { return null; }
        } catch (Exception $e) {
            if ($qr_res = $this->q_lookup_word->execute($ps_word)) {
                if ($qr_res->nextRow()) {
                    return WLPlugSearchEngineSqlSearch2::$word_cache[$ps_word] = (int)$qr_res->get('word_id', array('binary' => true));
                }
            }
            return null;
        }
		
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
		// 			$this->db->query($vs_sql);
		// 		}
		
		return WLPlugSearchEngineSqlSearch2::$word_cache[$ps_word] = $vn_word_id;
	}
	# ------------------------------------------------
	private function _checkWordCacheSize() {
		if ((sizeof(WLPlugSearchEngineSqlSearch2::$word_cache)) > ($vn_max_size = $this->getOption('maxWordCacheSize'))) {
			WLPlugSearchEngineSqlSearch2::$word_cache = array_slice(WLPlugSearchEngineSqlSearch2::$word_cache, 0, $vn_max_size * $this->getOption('cacheCleanFactor'), true);
		}
	}
	# ------------------------------------------------
	public function removeRowIndexing($subject_tablenum, $pn_subject_row_id, $pn_field_tablenum=null, $pa_field_nums=null, $pn_field_row_id=null, $pn_rel_type_id=null) {

		//print "[SqlSearchDebug] removeRowIndexing: $subject_tablenum/$pn_subject_row_id<br>\n";
		$vn_rel_type_id = $pn_rel_type_id ? $pn_rel_type_id : 0;
		
		
		// remove dependent row indexing
		if ($subject_tablenum && $pn_subject_row_id &&  !is_null($pn_field_tablenum) && !is_null($pn_field_row_id) && is_array($pa_field_nums) && sizeof($pa_field_nums)) {
			foreach($pa_field_nums as $pn_field_num) {
				if(!$pn_field_num) { continue; }
				//print "DELETE ROW WITH FIELD NUM $subject_tablenum/$pn_subject_row_id/$pn_field_tablenum/$pn_field_num/$pn_field_row_id<br>";
				$this->q_delete_with_field_row_id_and_num->execute((int)$subject_tablenum, (int)$pn_subject_row_id, (int)$pn_field_tablenum, (string)$pn_field_num, (int)$pn_field_row_id, $vn_rel_type_id);
			}
			return true;
		} else {
			if ($subject_tablenum && $pn_subject_row_id && !is_null($pn_field_tablenum) && !is_null($pn_field_row_id)) {
				//print "DELETE ROW $subject_tablenum/$pn_subject_row_id/$pn_field_tablenum/$pn_field_row_id<br>";
				return $this->q_delete_with_field_row_id->execute((int)$subject_tablenum, (int)$pn_subject_row_id, (int)$pn_field_tablenum, (int)$pn_field_row_id, $vn_rel_type_id);
			} else {
				if (!is_null($pn_field_tablenum) && is_array($pa_field_nums) && sizeof($pa_field_nums)) {
					foreach($pa_field_nums as $pn_field_num) {
						if(!$pn_field_num) { continue; }
						//print "DELETE FIELD $subject_tablenum/$pn_subject_row_id/$pn_field_tablenum/$pn_field_num<br>";
						
						if (!is_null($pn_rel_type_id)) {
						    $this->q_delete_with_field_num->execute((int)$subject_tablenum, (int)$pn_subject_row_id, (int)$pn_field_tablenum, (string)$pn_field_num, $vn_rel_type_id);
					    } else {
					        $this->q_delete_with_field_num_without_rel_type_id->execute((int)$subject_tablenum, (int)$pn_subject_row_id, (int)$pn_field_tablenum, (string)$pn_field_num);
					    }
					}
					return true;
				} else {
					if (!$subject_tablenum && !$pn_subject_row_id && !is_null($pn_field_tablenum) && !is_null($pn_field_row_id)) {
						//print "DELETE DEP $pn_field_tablenum/$pn_field_row_id<br>";
						$this->q_delete_dependent_sql->execute((int)$pn_field_tablenum, (int)$pn_field_row_id, $vn_rel_type_id);
					} else {
						//print "DELETE ALL $subject_tablenum/$pn_subject_row_id<br>";
						return $this->q_delete->execute((int)$subject_tablenum, (int)$pn_subject_row_id, $vn_rel_type_id);
					}
				}
			}
		}
	}
	# ------------------------------------------------
	/**
	 *
	 *
	 * @param int $subject_tablenum
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
	public function updateIndexingInPlace($subject_tablenum, $pa_subject_row_ids, $pn_content_tablenum, $ps_content_fieldnum, $pn_content_container_id, $pn_content_row_id, $ps_content, $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = []; }
		// Find existing indexing for this subject and content 	
		foreach($pa_subject_row_ids as $vn_subject_row_id) {
			$this->removeRowIndexing($subject_tablenum, $vn_subject_row_id, $pn_content_tablenum, array($ps_content_fieldnum), $pn_content_row_id, caGetOption('relationship_type_id', $pa_options, null));
		}
		
		if (caGetOption("DONT_TOKENIZE", $pa_options, false) || in_array('DONT_TOKENIZE', $pa_options, true)) {
			$va_words = array($ps_content);
		} else {
			$va_words = $this->_tokenize($ps_content);
		}
		
		if (caGetOption("INDEX_AS_IDNO", $pa_options, false) || in_array('INDEX_AS_IDNO', $pa_options, true)) {
			$t_content = Datamodel::getInstanceByTableNum($pn_content_tablenum, true);
			
			$va_values = [];
			if ($delimiters = caGetOption("IDNO_DELIMITERS", $pa_options, false)) {
				if ($delimiters && !is_array($delimiters)) { $delimiters = [$delimiters]; }
				if ($delimiters) {
					$va_values = array_map(function($v) { return trim($v); }, preg_split('!('.join('|', $delimiters).')!', $ps_content));
				} 
			}
			if (!sizeof($va_values) && method_exists($t_content, "getIDNoPlugInInstance") && ($o_idno = $t_content->getIDNoPlugInInstance())) {
				$va_values = $o_idno->getIndexValues($ps_content);
			}
			$va_words += $va_values;
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
		
		$subject_tablenum = (int)$subject_tablenum;
		$pn_content_tablenum = (int)$pn_content_tablenum;
		$pn_content_row_id = (int)$pn_content_row_id;
		$vn_boost = (int)$vn_boost;

		
		foreach($pa_subject_row_ids as $vn_row_id) {
			if (!$vn_row_id) { 
				if ($this->debug) { Debug::msg("[SqlSearchDebug] Cannot index row because row id is missing!"); }
				continue; 
			}
			$vn_seq = 0;
			foreach($va_words as $vs_word) {
				if (!($vn_word_id = $this->getWordID($vs_word))) { continue; }
				$va_row_insert_sql[] = "({$subject_tablenum}, {$vn_row_id}, {$pn_content_tablenum}, '{$ps_content_fieldnum}', ".($pn_content_container_id ? $pn_content_container_id : 'NULL').", {$pn_content_row_id}, {$vn_word_id}, {$vn_boost}, {$vn_private}, {$vn_rel_type_id})";
				$vn_seq++;
			}
			
			if (is_array($va_literal_content)) {
				foreach($va_literal_content as $vs_literal) {
					if (!($vn_word_id = $this->getWordID($vs_literal))) { continue; }
					$va_row_insert_sql[] = "({$subject_tablenum}, {$vn_row_id}, {$pn_content_tablenum}, '{$ps_content_fieldnum}', ".($pn_content_container_id ? $pn_content_container_id : 'NULL').", {$pn_content_row_id}, {$vn_word_id}, {$vn_boost}, {$vn_private}, {$vn_rel_type_id})";
					$vn_seq++;
				}
			}
		}
		
		// do insert
		if (sizeof($va_row_insert_sql)) {
			$vs_sql = $this->ops_insert_word_index_sql."\n".join(",", $va_row_insert_sql);
			$this->db->query($vs_sql);
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
		
			return preg_split('![ ]+!', trim(preg_replace("%((?<!\d)[".$this->ops_search_tokenizer_regex."]+(?!\d))%u", ' ', strip_tags($ps_content))));
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
				$va_quoted_words[] = $this->db->escape($vs_word);
			}
			$qr_res = $this->db->query("
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
	/**
	 *
	 */
	public function wordIDsToWords($pa_word_ids) {
		if(!is_array($pa_word_ids) || !sizeof($pa_word_ids)) { return array(); }
	
		$qr_words = $this->db->query("
			SELECT word, word_id FROM ca_sql_search_words WHERE word_id IN (?)
		", array($pa_word_ids));
		
		$va_words = array();
		while($qr_words->nextRow()) {
			$va_words[(int)$qr_words->get('word_id')] = $qr_words->get('word');
		}
		return $va_words;
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
			$this->db->query("DELETE FROM ca_sql_search_word_index WHERE table_num = ?", array((int)$pn_table_num));
		} else {
			$this->db->query("TRUNCATE TABLE ca_sql_search_word_index");
			$this->db->query("TRUNCATE TABLE ca_sql_search_words");
			$this->db->query("TRUNCATE TABLE ca_sql_search_ngrams");
		}
		return true;
	}	
	# -------------------------------------------------------
	/**
	 * Initialize database SQL and prepared statements and indexing
	 */
	private function initDbStatements() {
		$this->lookup_word_sql = "
			SELECT word_id 
			FROM ca_sql_search_words
			WHERE
				word = ?
		";
		
		$this->q_lookup_word = $this->db->prepare($this->lookup_word_sql);
		
		$this->ops_insert_word_index_sql = "
			INSERT  INTO ca_sql_search_word_index
			(table_num, row_id, field_table_num, field_num, field_container_id, field_row_id, word_id, boost, access, rel_type_id)
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
		
		$this->opqr_insert_word = $this->db->prepare($this->ops_insert_word_sql);
		
		$this->delete_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete = $this->db->prepare($this->delete_sql);
		
		$this->ops_delete_with_field_num_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?) AND (rel_type_id = ?)";
		$this->q_delete_with_field_num = $this->db->prepare($this->ops_delete_with_field_num_sql);
		
		$this->ops_delete_with_field_num_sql_without_rel_type_id = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?)";
		$this->q_delete_with_field_num_without_rel_type_id = $this->db->prepare($this->ops_delete_with_field_num_sql_without_rel_type_id);
		
		$this->ops_delete_with_field_row_id_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete_with_field_row_id = $this->db->prepare($this->ops_delete_with_field_row_id_sql);
		
		$this->ops_delete_with_field_row_id_and_num = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete_with_field_row_id_and_num = $this->db->prepare($this->ops_delete_with_field_row_id_and_num);
		
		$this->ops_delete_dependent_sql = "DELETE FROM ca_sql_search_word_index WHERE (field_table_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete_dependent_sql = $this->db->prepare($this->ops_delete_dependent_sql);	
	}
	# --------------------------------------------------
	# Utils
	# --------------------------------------------------
	private function getFieldNum($pn_table_name_or_num, $ps_fieldname) {
		if (isset(WLPlugSearchEngineSqlSearch2::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname])) { return WLPlugSearchEngineSqlSearch2::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname]; }
		
		$vs_table_name = is_numeric($pn_table_name_or_num) ? Datamodel::getTableName((int)$pn_table_name_or_num) : (string)$pn_table_name_or_num;
		return WLPlugSearchEngineSqlSearch2::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname] = Datamodel::getFieldNum($vs_table_name, $ps_fieldname);
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
					$va_values[] = (int)preg_replace("![^\d]+!", "", $vs_tmp);
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
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getElementIDForAccessPoint($subject_tablenum, $ps_access_point) {
		$va_tmp = preg_split('![/\|]+!', $ps_access_point);
		list($vs_table, $vs_field, $vs_subfield, $vs_subsubfield, $vs_subsubsubfield) = explode('.', $va_tmp[0]);
		if ($vs_table === '_fulltext') { return null; }	// ignore "_fulltext" specifier – just treat as text search
		
		$vs_rel_table = caGetRelationshipTableName($subject_tablenum, $vs_table);
		$va_rel_type_ids = ($va_tmp[1] && $vs_rel_table) ? caMakeRelationshipTypeIDList($vs_rel_table, preg_split("![,;]+!", $va_tmp[1])) : [];
		
		if (!($t_table = Datamodel::getInstanceByTableName($vs_table, true))) { 
			return array('access_point' => $va_tmp[0]);
		}
		$vs_table_num = $t_table->tableNum();
		
		// counts for relationship
		$vn_rel_type = null;
		
		if (is_array($va_rel_type_ids) && (sizeof($va_rel_type_ids) > 0)) {
			$vn_rel_type = (int)$va_rel_type_ids[0];
		}
		
		if (strtolower($vs_field) == 'count') {
			if (!is_array($va_rel_type_ids) || !sizeof($va_rel_type_ids)) { $va_rel_type_ids = [0]; }	// for counts must pass "0" as relationship type to pull count for all reltypes in aggregate
			return array(
				'access_point' => "{$vs_table}.{$vs_field}",
				'relationship_type' => $vn_rel_type,
				'table_num' => $vs_table_num,
				'element_id' => null,
				'field_num' => 'COUNT',
				'datatype' => 'COUNT',
				'element_info' => null,
				'relationship_type_ids' => $va_rel_type_ids,
				'type' => 'COUNT'
			);
		} elseif (strtolower($vs_field) == 'current_value') {
		    if(!$vs_subfield) { $vs_subfield = '__default__'; }
		    
		    $vs_fld_num = null;
		    if ($vn_fld_num = $this->getFieldNum($vs_table, $vs_subsubsubfield ? $vs_subsubsubfield : $vs_subsubfield)) {
		        $vs_fld_num = "I{$vn_fld_num}";
		    } elseif($t_element = ca_metadata_elements::getInstance($vs_subsubsubfield ? $vs_subsubsubfield : $vs_subsubfield)) {
		        $vs_fld_num = "A".$t_element->getPrimaryKey();
		    }
		    return array(
				'access_point' => $va_tmp[0],
				'relationship_type' => $vn_rel_type,
				'table_num' => $vs_table_num,
				'element_id' => null,
				'field_num' => "CV{$vs_subfield}_{$vs_fld_num}",
				'datatype' => 'CV',
				'element_info' => null,
				'relationship_type_ids' => $va_rel_type_ids,
				'type' => 'CV'
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
						'relationship_type_ids' => $va_rel_type_ids,
						'type' => 'COUNT'
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
						'relationship_type_ids' => $va_rel_type_ids,
						'type' => 'METADATA'
					);
				}
			}
		} else {

			return array('access_point' => $va_tmp[0], 'relationship_type' => $va_tmp[1], 'table_num' => $vs_table_num, 'field_num' => 'I'.$vs_fld_num, 'field_num_raw' => $vs_fld_num, 'datatype' => null, 'relationship_type_ids' => $va_rel_type_ids, 'type' => 'INTRINSIC');
		}

		return null;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getBooleanOperator(?array $signs, int $index) {
		if (is_null($signs) || ($signs[$i] === true)) {	
			// if array is null then according to Zend Lucene all subqueries should be "are required"... so we AND them
			return "AND";
		} elseif (is_null($signs[$index])) {	
			// is the sign for a particular query is null then OR it is (it is "neither required nor prohibited")
			return 'OR';
		} else {
			// true sign indicates "required" (AND) operation, false indicates "prohibited" (NOT) operation
			return ($signs[$index] === false) ? 'NOT' : 'AND';	
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _createTempTable(string $name) {
		$this->db->query("DROP TABLE IF EXISTS {$name}");
		$this->db->query("
			CREATE TEMPORARY TABLE {$name} (
				row_id int unsigned not null primary key,
				boost int not null default 1,
				field_container_id int unsigned null
			) engine=memory;
		");
		if ($this->db->numErrors()) {
			return false;
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _dropTempTable(string $name) {
		$this->db->query("
			DROP TABLE IF EXISTS {$name};
		");
		if ($this->db->numErrors()) {
			return false;
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _arrayFromDbResult(DbResult $qr_res) {
		$vals = $qr_res->getAllFieldValues(['row_id', 'boost']);
	 	if(!isset($vals['row_id'])) { return []; }
	 	$hits = [];
	 	foreach($vals['row_id'] as $i => $row_id) {
	 		$hits[$row_id] = $vals['boost'][$i];
	 	}
	 	return $hits;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _queryForNumericAttribute($attrval, $ap, $text, $text_upper, $attr_field) {
		if (!is_array($parsed_value = $attrval->parseValue($text, $ap['element_info']))) {
			return null;
		}
		
		if (!in_array($attr_field, ['value_integer1', 'value_decimal1'])) { 
			throw new ApplicationException(_t('Invalid attribute field'));
		}
		
		if($ap['type'] === 'INTRINSIC') {
			$tmp = explode('.', $ap['access_point']);
			if (!($t_table = Datamodel::getInstance($tmp[0], true))) {
				throw new ApplicationException(_t('Invalid table %1 in bundle %2', $tmp[0], $access_point));
			}
			
			$pk = $t_table->primaryKey(true);
			$table = $t_table->tableName();
			$field_name = $tmp[1];
			
			if(!$t_table->hasField($field_name)) { 
				throw new ApplicationException(_t('Invalid field %1 in bundle %2', $tmp[1], $access_point));
			}
			
			if ($text_upper && (is_array($parsed_value_end = $attrval->parseValue($text_upper, $ap['element_info'])))) {
				$where_sql = "({$table}.{$field_name} >= ? AND {$table}.{$field_name} <= ?)";
				$params = [$parsed_value['value_decimal1'], $parsed_value_end['value_decimal1']];
			} else {
				$where_sql = "({$table}.{$field_name} = ?)";
				$params = [$parsed_value['value_decimal1']];
			}
			
			$sql = "
				SELECT {$pk} row_id, 1 boost
				FROM {$table}
				WHERE
					{$where_sql}
			";
		} else {
			$params = [$parsed_value[$attr_field]];
			$where_sql = '';
		
			if ($text_upper && (is_array($parsed_value_end = $attrval->parseValue($text_upper, $ap['element_info'])))) {
				$where_sql = "(cav.{$attr_field} >= ? AND cav.{$attr_field} <= ?)";
				$params[] = $parsed_value_end['value_decimal1'];
			} else {
				$where_sql = "(cav.{$attr_field} = ?)";
			}
		
			$sql = "
				SELECT ca.row_id, 1 boost
				FROM ca_attribute_values cav
				INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
				WHERE
					(cav.element_id = {$ap['element_info']['element_id']}) AND (ca.table_num = ?)
					AND
					({$where_sql})
			";
		}
		
		return ['sql' => $sql, 'params' => $params];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _queryForCurrencyAttribute($attrval, $ap, $text, $text_upper) {
		if (!is_array($parsed_value = $attrval->parseValue($text, $ap['element_info']))) {
			return null;
		}
		
		$currency = preg_replace('![^A-Z0-9]+!', '', $parsed_value['value_longtext1']);
		if (!$currency) { 
			return null;	// no currency
		}
		$params = [$parsed_value['value_decimal1']];
		$where_sql = '';
		
		if ($text_upper && (is_array($parsed_value_end = $attrval->parseValue($text_upper, $ap['element_info'])))) {
			$where_sql = "((cav.value_decimal1 >= ? AND cav.value_decimal1 <= ?) AND (cav.value_longtext1 = ?))";
			$params[] = $parsed_value_end['value_decimal1'];
		} else {
			$where_sql = "((cav.value_decimal1 = ?) AND (cav.value_longtext1 = ?))";
		}
		$params[] = $currency;

		$sql = "
			SELECT ca.row_id, 1 boost
			FROM ca_attribute_values cav
			INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
			WHERE
				(cav.element_id = {$ap['element_info']['element_id']}) AND (ca.table_num = ?)
				AND
				({$where_sql})
		";
		
		return ['sql' => $sql, 'params' => $params];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _queryForGeocodeAttribute($attrval, $ap, $text, $text_upper) {
		$upper_lat = $upper_long = $lower_lat = $lower_long = null;
		if ($text_upper) {
			if (!is_array($parsed_value = $attrval->parseValue("[{$text}]", $ap['element_info']))) {
				return null;
			}
			$lower_lat = (float)$parsed_value['value_decimal1'];
			$lower_long = (float)$parsed_value['value_decimal2'];
		
		
			$parsed_value = $attrval->parseValue("[{$text_upper}]", $ap['element_info']);
			$upper_lat = (float)$parsed_value['value_decimal1'];
			$upper_long = (float)$parsed_value['value_decimal2'];
		
			// MySQL BETWEEN always wants the lower value first ... BETWEEN 5 AND 3 wouldn't match 4 ... So we swap the values if necessary
			if($upper_lat < $lower_lat) {
				$tmp = $upper_lat;
				$upper_lat = $lower_lat;
				$lower_lat = $tmp;
			}
			if($upper_long < $lower_long) {
				$tmp = $upper_long;
				$upper_long = $lower_long;
				$lower_long = $tmp;
			}
		} elseif(is_array($parsed_values = caParseGISSearch($text))) {
			$lower_lat = $parsed_values['min_latitude'];
			$upper_lat = $parsed_values['max_latitude'];
			$lower_long = $parsed_values['min_longitude'];
			$upper_long = $parsed_values['max_longitude'];
		} else {
			return [];
		}
		
		$params = [];
		$where_sql = '';
		
		if (!is_null($upper_lat) && !is_null($upper_long)) {
			$where_sql = "((cav.value_decimal1 >= ? AND cav.value_decimal1 <= ?) AND (cav.value_decimal2 >= ? AND cav.value_decimal2 <= ?))";
			$params = [$lower_lat, $upper_lat, $lower_long, $upper_long];
		} else {
			throw new ApplicationException(_t('Upper lat/long coordinates must not be empty'));
		}
		
		$sql = "
			SELECT ca.row_id, 1 boost
			FROM ca_attribute_values cav
			INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
			WHERE
				(cav.element_id = {$ap['element_info']['element_id']}) AND (ca.table_num = ?)
				AND
				({$where_sql})
		";
		
		return ['sql' => $sql, 'params' => $params];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _queryForDateRangeAttribute($attrval, $ap, $text, $text_upper) {
		if ($text_upper) { $text = "{$text} - {$text_upper}"; }
		if (!is_array($parsed_value = $attrval->parseValue($text, $ap['element_info']))) {
			return null;
		}
		
		$dates = [
			'start' => $parsed_value['value_decimal1'],
			'end' => $parsed_value['value_decimal2']
		];
		if (((int)$dates['start'] === -2000000000) && $this->search_config->get('treat_before_dates_as_circa')) {
			$dates['start'] = (int)$dates['end'] + 0.1231235959;
		}
		if (((int)$dates['end'] === 2000000000) && $this->search_config->get('treat_after_dates_as_circa')) {
			$dates['end'] = (int)$dates['start'];
		}
		
		$dates['start'] = (float)$dates['start'];
		$dates['end'] = (float)$dates['end'];
		
		if($ap['type'] === 'INTRINSIC') {
			$tmp = explode('.', $ap['access_point']);
			if (!($t_table = Datamodel::getInstance($tmp[0], true))) {
				throw new ApplicationException(_t('Invalid table %1 in bundle %2', $tmp[0], $access_point));
			}
			
			$pk = $t_table->primaryKey(true);
			$table = $t_table->tableName();
			
			$fi = $t_table->getFieldInfo($tmp[1]);
			
			$sql = "
				SELECT {$pk} row_id, 1 boost
				FROM {$table}
				WHERE
					(
						({$table}.{$fi['START']} BETWEEN ? AND ?)
						OR
						({$table}.{$fi['END']} BETWEEN ? AND ?)
						OR
						({$table}.{$fi['START']} <= ? AND {$table}.{$fi['END']} >= ?)	
					)
	
			";
			$params = [$dates['start'], $dates['end'], $dates['start'], $dates['end'], $dates['start'], $dates['end']];
		} else {
			$sql = "
				SELECT ca.row_id, 1 boost
				FROM ca_attribute_values cav
				INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
				WHERE
					(cav.element_id = {$ap['element_info']['element_id']}) AND (ca.table_num = ?)
					AND
					(
						(cav.value_decimal1 BETWEEN ? AND ?)
						OR
						(cav.value_decimal2 BETWEEN ? AND ?)
						OR
						(cav.value_decimal1 <= ? AND cav.value_decimal2 >= ?)	
					)
	
			";
			$params = [$dates['start'], $dates['end'], $dates['start'], $dates['end'], $dates['start'], $dates['end']];
		}	
		return ['sql' => $sql, 'params' => $params];
	}
	# -------------------------------------------------------
}
