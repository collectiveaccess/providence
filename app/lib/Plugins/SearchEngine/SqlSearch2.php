<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/SqlSearch2.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2022 Whirl-i-Gig
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
	
	
	private $q_lookup_word = null;
	private $insert_word_sql = '';
	private $insert_ngram_sql = '';


	private $insert_word_index_sql = '';
	private $delete_with_field_num_sql = "";
	private $q_delete_with_field_num = null;

	private $delete_with_field_row_id_sql = '';
	private $q_delete_with_field_row_id = null;

	private $delete_with_field_row_id_and_num = "";
	private $q_delete_with_field_row_id_and_num = null;

	private $delete_dependent_sql = "";
	private $q_delete_dependent_sql = null;
	
	
	static public $whitespace_tokenizer_regex;
	static public $punctuation_tokenizer_regex;
	
	static private $word_cache = [];					// cached word-to-word_id values used when indexing
	static private $metadata_elements; 					// cached metadata element info
	static private $fieldnum_cache = [];				// cached field name-to-number values used when indexing
	static private $stop_words = null;
	static private $doc_content_buffer = [];			// content buffer used when indexing
	
	static protected $filter_stop_words = null;
	
	# -------------------------------------------------------
	public function __construct($db=null) {
		global $g_ui_locale;
		
		parent::__construct($db);
		
		if(is_null(self::$filter_stop_words)) { self::$filter_stop_words = $this->search_config->get('use_stop_words'); }
		
		$this->tep = new TimeExpressionParser();
		$this->tep->setLanguage($g_ui_locale);
		
		$this->stemmer = new SnoballStemmer();
		$this->do_stemming = (int)trim($this->search_config->get('search_sql_search_do_stemming')) ? true : false;
		
		$this->initDbStatements();

		if(!(self::$whitespace_tokenizer_regex = $this->search_config->get('whitespace_tokenizer_regex'))) {
			self::$whitespace_tokenizer_regex = '[\s\"\—\-]+';
		}
		if(!(self::$punctuation_tokenizer_regex = $this->search_config->get('punctuation_tokenizer_regex'))) {
			self::$whitespace_tokenizer_regex = '[\.,;:\(\)\{\}\[\]\|\\\+_\!\&«»\']+';
		}
		
		if(self::$filter_stop_words) {
			if(!is_array(self::$stop_words)) { 
				if(CompositeCache::contains('stop_words', 'SqlSearch2')) { 
					self::$stop_words = CompositeCache::fetch('stop_words', 'SqlSearch2');
				} else {
					$sw = new \voku\helper\StopWords();
					$langs = array_map(function($v) { return array_shift(explode('_', $v)); }, ca_locales::getCataloguingLocaleCodes());
			
					self::$stop_words = [];
					foreach($langs as $lang) {
						try {
							self::$stop_words = array_merge(self::$stop_words, array_flip($sw->getStopWordsFromLanguage($lang)));
						} catch(Exception $e) {
							// noop
						}
					}
			
					// Add application-specific stop words
					self::$stop_words[mb_strtolower('['.caGetBlankLabelText(57).']')] = 1;
				
					CompositeCache::save('stop_words', self::$stop_words, 'SqlSearch2');
				}
			}
		} else {
			self::$stop_words = [];
		}
		
		//
		// Load info about metadata elements into static var cache if it hasn't already be fetched
		//
		if (!is_array(WLPlugSearchEngineSqlSearch2::$metadata_elements)) {
			WLPlugSearchEngineSqlSearch2::$metadata_elements = ca_metadata_elements::getRootElementsAsList();
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
				'maxWordCacheSize' => 1048576,								// maximum number of words to cache while indexing before purging
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
	 * @param Db $db A database connection to use in place of current one
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
		if (is_array(self::$doc_content_buffer) && sizeof(self::$doc_content_buffer)) {
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
		if(!is_array($hits)) { $hits = []; }
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
	 	$i = -1;
	 	foreach($subqueries as $subquery) {
	 		$hits = $this->_processQuery($subject_tablenum, $subquery);
	 		if(is_null($hits)) { continue; } // skip stop words
	 		$i++;
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
	 	$ap = $field ? $this->_getElementIDForAccessPoint($subject_tablenum, $field) : null;
	 	$words = [$term->text];
	 	if($field && !is_array($ap)) {
	 		array_unshift($words, $field);
	 		$field = null;
	 	}
	 	$indexing_options = caGetOption('indexing_options', $ap, null);
	 	
	 	$blank_val = caGetBlankLabelText($subject_tablenum);
	 	$is_blank = ((mb_strtolower("[{$blank_val}]") === mb_strtolower($term->text)) || (mb_strtolower("[BLANK]") === mb_strtolower($term->text)));
	 	$is_not_blank = (mb_strtolower("["._t('SET')."]") === mb_strtolower($term->text));
	 	
	 	if(!$is_blank && !$is_not_blank && (!is_array($indexing_options) || !in_array('DONT_TOKENIZE', $indexing_options))) {
	 		$words = self::filterStopWords(self::tokenize(join(' ', $words), true));
	 	}
	 	if(!$words || !sizeof($words)) { return null; }
	 	
	 	
	 	$word_field = 'sw.word';
	 	
	 	if (is_array($ap) && !$this->useSearchIndexForAP($ap)) {
	 		// Handle datatype-specific queries
	 		$ret = $this->_processMetadataDataType($subject_tablenum, $ap, $query);
	 		if(is_array($ret)) { return $ret; }
	 	}
	 	
	 	foreach($words as $i => $text) {
			// Don't stem if:
			//	1. Stemming is disabled
			//	2. Search for is blank values
			//	3. Search is not non-blank values
			//	4. Search includes non-letter characters
			//  5. Search is flagged with trailing "|" as "do-not-stem"
			$do_not_stem = preg_match("!\|$!", $text);
			$text = preg_replace("!\|$!", '', $text);
			if ($this->do_stemming && !$do_not_stem && !$is_blank && !$is_not_blank && !preg_match("![^A-Za-z]+!u", $text)) {
				$text_stem = $this->stemmer->stem($text);
				if (($text !== $text_stem) && ($text_stem[strlen($text_stem)-1] !== '*')) { 
					$text = $text_stem.'*';
					$word_field = 'sw.stem';
				}
			}
		
			$params = [$subject_tablenum];
			$word_op = '=';
		
			$use_boost = true;
			$is_bare_wildcard = false;
			if (is_array($ap) && $is_blank) {
				$params[] = 0;
				$word_field = 'swi.word_id';
			} elseif (!is_array($ap) && $is_blank) {
				return [];
			} elseif(is_array($ap) && $is_not_blank) {
				$word_op = '>';
				$params[] = 0;
				$word_field = 'swi.word_id';
			} elseif ($text === '*') {
				$is_bare_wildcard = true;
			} elseif ($has_wildcard = ((strpos($text, '*') !== false) || (strpos($text, '?') !== false))) {
				$word_op = 'LIKE';
				$text = str_replace('*', '%', $text);
				$text = str_replace('?', '_', $text);
				$params[] = $text;
				$use_boost = false;
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
		
			if($restrictions = $this->_getFieldRestrictions($subject_tablenum)) {
				$res = [];
			
				$res_by_table = [];
				foreach($restrictions['restrict'] as $r) {
					$res_by_table[$r['table_num']][] = $r['field_num'];
				}
				foreach($res_by_table as $rtable_num => $rfield_nums) {
					$res[] = "(swi.field_table_num = ? AND swi.field_num IN (?))";
					$params[] = $rtable_num;
					$params[] = $rfield_nums;
				}
			
				$flds = [];
				foreach($restrictions['exclude'] as $r) {
					$flds[] = "'".$r['table_num'].'/'.$r['field_num']."'";
				}
				if(sizeof($flds)) {
					$res[] = "(CONCAT(swi.field_table_num, '/', swi.field_num) NOT IN (?))";
					$params[] = join(',', $flds);
				}
				if(sizeof($res)) {
					$field_sql .= " AND (".join(' OR ', $res).")";
				}
			}
		
			$private_sql = ($this->getOption('omitPrivateIndexing') ? ' AND swi.access = 0' : '');
		
			if ($is_bare_wildcard) {
				$t = Datamodel::getInstance($subject_tablenum, true);
				$pk = $t->primaryKey();
				$table = $t->tableName();
			
				$qr_res = $this->db->query("
					SELECT {$pk} row_id, 100 boost
					FROM {$table}".($t->hasField('deleted') ? " WHERE deleted = 0" : "")."
				", []);
			} elseif($use_boost) {
				$qr_res = $this->db->query("
					SELECT swi.row_id, SUM(swi.boost) boost
					FROM ca_sql_search_word_index swi
					".(!$is_blank ? 'INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id' : '')."
					WHERE
						swi.table_num = ? AND {$word_field} {$word_op} ?
						{$field_sql}
						{$private_sql}
					GROUP BY swi.row_id
				", $params);
			} else {
				$qr_res = $this->db->query("
					SELECT DISTINCT swi.row_id, 100 boost
					FROM ca_sql_search_word_index swi
					".(!$is_blank ? 'INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id' : '')."
					WHERE
						swi.table_num = ? AND {$word_field} {$word_op} ?
						{$field_sql}
						{$private_sql}
				", $params);
			}
			$res[$i] = $this->_arrayFromDbResult($qr_res);
		}
		$ret = array_shift($res);
		foreach($res as $r) {
			$ret = array_intersect($ret, $res[$r]);
		}
		return $ret;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _processQueryPhrase(int $subject_tablenum, $query) {
	 	$terms = $query->getTerms();
	 	$private_sql = ($this->getOption('omitPrivateIndexing') ? ' AND swi.access = 0' : '');
	 	
	 	$term = $terms[0];
	 	$field = $term->field;
	 	$field_lc = mb_strtolower($field);
	 	$field_elements = explode('.', $field_lc);
	 	if (in_array($field_elements[0], [_t('created'), _t('modified')])) {
	 		return $this->_processQueryChangeLog($subject_tablenum, $query);
	 	}
	 	if ($this->getOption('strictPhraseSearching')) {
	 		$words = [];
	 		$temp_tables = [];
	 		$ap_spec = null;
			foreach($terms as $term) {
				if (!$ap_spec && ($field = $term->field)) { $ap_spec = $field; }
				
				if (strlen($escaped_text = $this->db->escape(join(' ', self::tokenize($term->text, true))))) {
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
	 			$word_op = '=';
	 			if($has_wildcard = ((strpos($word, '*') !== false) || (strpos($word, '?') !== false))) {
	 				$word_op = 'LIKE';
					$word = str_replace('*', '%', $word);
					$word = str_replace('?', '_', $word);
	 			}
	 		
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
						sw.word {$word_op} ? AND swi.table_num = ? {$fld_limit_sql}
						{$private_sql}
				", $word, (int)$subject_tablenum);
				$qr_count = $this->db->query("SELECT count(*) c FROM {$temp_table}");
			
				$temp_tables[] = $temp_table;	
			}
			$results_temp_table = array_pop($temp_tables);
							
			$this->db->query("UPDATE {$results_temp_table} SET row_id = row_id - 1");
			
			$params = [];
			if($restrictions = $this->_getFieldRestrictions($subject_tablenum)) {
				$res = [];
				foreach($restrictions['restrict'] as $r) {
					$res[] = "(swi.field_table_num = ? AND swi.field_num = ?)";
					$params[] = $r['table_num'];
					$params[] = $r['field_num'];
				}
			
				$flds = [];
				foreach($restrictions['exclude'] as $r) {
					$flds[] = "'".$r['table_num'].'/'.$r['field_num']."'";
				}
				if(sizeof($flds)) {
					$res[] = "(CONCAT(swi.field_table_num, '/', swi.field_num) NOT IN (?))";
					$params[] = join(',', $flds);
				}
				if(sizeof($res)) {
					$field_sql .= " AND (".join(' AND ', $res).")";
				}
			}
			
			$qr_res = $this->db->query("
				SELECT swi.row_id, ca.boost, ca.field_container_id
				FROM {$results_temp_table} ca
				INNER JOIN ca_sql_search_word_index AS swi ON swi.index_id = ca.row_id {$field_sql}
			", $params);
			
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
	private function _processQueryChangeLog(int $subject_tablenum, Object $term) {
		switch(get_class($term)) {
			case 'Zend_Search_Lucene_Search_Query_Term':
			case 'Zend_Search_Lucene_Index_Term':
	 			$text = $term->text;
				$field = $term->field;
	 			break;
	 		case 'Zend_Search_Lucene_Search_Query_Phrase':
	 			$text = join(' ', array_map(function($t) { return $t->text; }, $terms = $term->getTerms()));
				$field = $terms[0]->field;
	 			break;
	 	}
	 	
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
							SELECT ccl.logged_row_id row_id, 1 boost
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
							SELECT ccl.logged_row_id row_id, 1 boost
							FROM ca_change_log ccl
							WHERE
								(ccl.log_datetime BETWEEN ? AND ?)
								AND
								(ccl.logged_table_num = ?)
								AND
								(ccl.changetype = 'U')
								{$user_sql}
						UNION
							SELECT ccls.subject_row_id row_id, 1 boost
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
		
		if ($ap['type'] === 'COUNT') {
			$params = [
				$subject_tablenum, (int)$lower_text, (int)$upper_text
			];
			$qr_res = $this->db->query("
				SELECT swi.row_id, SUM(swi.boost) boost
				FROM ca_sql_search_word_index swi
				INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id
				WHERE
					swi.table_num = ? AND swi.field_num = 'COUNT' AND sw.word BETWEEN ? AND ?
				GROUP BY swi.row_id
			", $params);
			return $this->_arrayFromDbResult($qr_res);
		}
		$table = Datamodel::getTableName($subject_tablenum);
		$idno_fld = Datamodel::getTableProperty($subject_tablenum, 'ID_NUMBERING_ID_FIELD');
		if($lower_term->field === "{$table}.{$idno_fld}") {
			if($o_idno = IDNumbering::newIDNumberer($table)) {
				$idno_sort_fld = Datamodel::getTableProperty($subject_tablenum, 'ID_NUMBERING_SORT_FIELD');
				if(($t_subject = Datamodel::getInstance($table, true)) && ($t_subject->hasField("{$idno_sort_fld}_num"))) {
					$lower_index = $o_idno->getSortableNumericValue($lower_text);
					$upper_index = $o_idno->getSortableNumericValue($upper_text);
			
					$pk = Datamodel::primaryKey($table);
				
					$params = [
						(int)$lower_index, (int)$upper_index
					];
					$qr_res = $this->db->query("
						SELECT t.{$pk} row_id, 100 boost
						FROM {$table} t
						WHERE
							t.{$idno_sort_fld}_num BETWEEN ? AND ?
					", $params);
					return $this->_arrayFromDbResult($qr_res);
				}
			}
		}
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
					$ap['element_info']['datatype'] = isset($fi['LIST_CODE']) ? null : __CA_ATTRIBUTE_VALUE_NUMERIC__;
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
		if(is_array($qinfo) && sizeof($qinfo)) {
			$params = $qinfo['params'];
			if($ap['type'] !== 'INTRINSIC') { array_unshift($params, $ap['table_num']); }
			$qr_res = $this->db->query($qinfo['sql'], $params);
			
			$row_ids = $this->_arrayFromDbResult($qr_res);
			if ((int)$ap['table_num'] === (int)$subject_tablenum) {
				return $row_ids;
			}
			
			$s = Datamodel::getInstance($subject_tablenum, true);
			$spk = $s->primaryKey(true);
			
			// convert related ids to subject
			$ap_instance = Datamodel::getInstance($ap['table_num'], true);
			
			// it's labels, so first convert labels to their subject-table ids... and then we convert that to the search subject
			if(is_a($ap_instance, 'BaseLabel')) {
				$ap_subject = $ap_instance->getSubjectTableInstance();
				$aspk = $ap_subject->primaryKey();
				
				$qr = $this->db->query("
					SELECT {$aspk} FROM {$ap_instance->tableName()} WHERE {$ap_instance->primaryKey()} IN (?)
				", [array_keys($row_ids)]);
				
				$row_ids = [];
				while($qr->nextRow()) {
					$row_ids[(int)$qr->get($aspk)] = 1;
				}
				$ap['table_num'] = $ap_subject->tableNum();
			}
			
			$subject_ids = [];
			
			if(!($qr = caMakeSearchResult($ap['table_num'], array_keys($row_ids)))) { return []; }
		
			while($qr->nextHit()) {
				switch((int)$ap['table_num']) {
					case 103:
						$s = $qr->getInstance();
						$a = $s->getItems(['idsOnly' => true]);
						break;
					default:
						$a = $qr->get($spk, ['returnAsArray' => true]);
						break;
				}
				
				foreach($a as $i) {
					$subject_ids[$i] = 1;
				}
			}	
			
			return $subject_ids;
		}
		return null;	// can't process here - try using search index
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _filterQueryResult(int $subject_tablenum, ?array $hits, array $filters) {
		if (is_array($filters) && sizeof($filters) && is_array($hits) && sizeof($hits)) {
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
	/**
	 *
	 */
	private function _getFieldRestrictions(int $table_num) {
		$restrict_to_fields = $exclude_fields_from_search = [];
		if(is_array($this->getOption('restrictSearchToFields'))) {
			foreach($this->getOption('restrictSearchToFields') as $f) {
				$restrict_to_fields[] = $this->_getElementIDForAccessPoint($table_num, $f);
			}
		}
		if(is_array($this->getOption('excludeFieldsFromSearch'))) {
			foreach($this->getOption('excludeFieldsFromSearch') as $f) {
				$exclude_fields_from_search[] = $this->_getElementIDForAccessPoint($table_num, $f);
			}
		}
		return ['restrict' => $restrict_to_fields, 'exclude' => $exclude_fields_from_search];
	}
	# -------------------------------------------------------
	# Indexing
	# -------------------------------------------------------
	/**
	 *
	 */
	public function startRowIndexing(int $subject_tablenum, int $subject_row_id) : void {
		if ($this->debug) { Debug::msg("[SqlSearchDebug] startRowIndexing: {$subject_tablenum}/{$subject_row_id}"); }

		$this->indexing_subject_tablenum = $subject_tablenum;
		$this->indexing_subject_row_id = $subject_row_id;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function indexField(int $content_tablenum, string $content_fieldname, int $content_row_id, $content, ?array $options=null) {
		if (!is_array($options)) { $options = []; }
		
		if (!is_array($content)) {
			$content = [$content];
		}
		
		$boost = 1;
		if (isset($options['BOOST'])) {
			$boost = intval($options['BOOST']);
		}
		
		if ($this->debug) { Debug::msg("[SqlSearchDebug] indexField: $content_tablenum/$content_fieldname [$content_row_id] =&gt; $content"); }
	
		if (in_array('DONT_TOKENIZE', array_values($options), true)) { 
			$options['DONT_TOKENIZE'] = true;  
		} elseif (!isset($options['DONT_TOKENIZE'])) { 
			$options['DONT_TOKENIZE'] = false; 
		}
		
		$force_tokenize = (in_array('TOKENIZE', array_values($options), true) || isset($options['TOKENIZE']));
		$tokenize = $options['DONT_TOKENIZE'] ? false : true;
		
		$rel_type_id = (isset($options['relationship_type_id']) && ($options['relationship_type_id'] > 0)) ? (int)$options['relationship_type_id'] : 0;
		$container_id = (isset($options['container_id']) && ($options['container_id'] > 0)) ? (int)$options['container_id'] : 'NULL';
		
		if (!isset($options['PRIVATE'])) { $options['PRIVATE'] = 0; }
		if (in_array('PRIVATE', $options, true)) { $options['PRIVATE'] = 1; }
		$private = $options['PRIVATE'] ? 1 : 0;
		
		if (!isset($options['datatype'])) { $options['datatype'] = null; }
		
		if ($content_fieldname[0] == 'A') {
			$field_num_proc = (int)substr($content_fieldname, 1);
			
			// do we need to index this (don't index attribute types that we'll search directly)
			if (WLPlugSearchEngineSqlSearch2::$metadata_elements[$field_num_proc]) {
				switch(WLPlugSearchEngineSqlSearch2::$metadata_elements[$field_num_proc]['datatype']) {
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
		if ((!is_array($content) && !strlen($content)) || !sizeof($content) || (((sizeof($content) == 1) && strlen((string)$content[0]) == 0)) || ((sizeof($content) === 1) && ((string)mb_strtolower($content[0]) === mb_strtolower(caGetBlankLabelText(Datamodel::getTableName($content_tablenum)))))){ 
			$words = null;
		} else {
			// Tokenize string
			$words = [];
			if ($tokenize || $force_tokenize) {
				foreach($content as $content) {
					$words = array_merge($words, self::tokenize((string)$content));
				}
			}
			if (!$tokenize) { $words = array_merge($words, $content); }
		}
		
		$incremental_reindexing = (bool)$this->can('incremental_reindexing');
		
		if (!defined("__CollectiveAccess_IS_REINDEXING__") && $incremental_reindexing) {
			$this->removeRowIndexing($this->indexing_subject_tablenum, $this->indexing_subject_row_id, $content_tablenum, array($content_fieldname), $content_row_id, $rel_type_id);
		}
		if (!$words) {
			self::$doc_content_buffer[] = '('.$this->indexing_subject_tablenum.','.$this->indexing_subject_row_id.','.$content_tablenum.',\''.$content_fieldname.'\','.$container_id.','.$content_row_id.',0,0,'.$private.','.$rel_type_id.')';
		} else {
			if((bool)$this->search_config->get('group_index_for_repeating_terms_in_field')) {
				$u = array_unique($words);
				if (($c1 = sizeof($u)) < ($c2 = sizeof($words))) { 
					$words = $u;
				}
			}
			foreach($words as $vs_word) {
				if(!strlen($vs_word)) { continue; }
				if (!($word_id = (int)$this->getWordID($vs_word))) { continue; }
			
				self::$doc_content_buffer[] = '('.$this->indexing_subject_tablenum.','.$this->indexing_subject_row_id.','.$content_tablenum.',\''.$content_fieldname.'\','.$container_id.','.$content_row_id.','.$word_id.','.$boost.','.$private.','.$rel_type_id.')';
			}
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function commitRowIndexing() : void {
		if (sizeof(self::$doc_content_buffer) > $this->getOption('maxIndexingBufferSize')) {
			$this->flushContentBuffer();
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function flushContentBuffer() : void {
		// add fields to doc
		$vn_max_word_segment_size = (int)$this->getOption('maxWordIndexInsertSegmentSize');
		
		// add new indexing
		if (is_array(self::$doc_content_buffer) && sizeof(self::$doc_content_buffer)) {
			while(sizeof(self::$doc_content_buffer) > 0) {
				if (defined("__CollectiveAccess_IS_REINDEXING__")) {
					$this->db->query("SET unique_checks=0");
					$this->db->query("SET foreign_key_checks=0");
				}
				$this->db->query($this->insert_word_index_sql."\n".join(",", array_splice(self::$doc_content_buffer, 0, $vn_max_word_segment_size)));
				if (defined("__CollectiveAccess_IS_REINDEXING__")) {
					$this->db->query("SET unique_checks=1");
					$this->db->query("SET foreign_key_checks=1");
				}
			}
			if ($this->debug) { Debug::msg("[SqlSearchDebug] Commit row indexing"); }
		}
	
		// clean up
		self::$doc_content_buffer = [];
		$this->_checkWordCacheSize();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getWordID(string $word) : ?int {
		$word = (string)$word;
		
		//reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
		$word = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
		 '|[\x00-\x7F][\x80-\xBF]+'.
		 '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
		 '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
		 '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
		 '?', $word);

		//reject overly long 3 byte sequences and UTF-16 surrogates and replace with ?
		$word = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.
		 '|\xED[\xA0-\xBF][\x80-\xBF]/S','?', $word);
		 
		if (!strlen($word = trim(mb_strtolower($word, "UTF-8")))) { return null; }
		if (mb_strlen($word) > 255) { $word = mb_substr($word, 0, 255); }
		if (isset(WLPlugSearchEngineSqlSearch2::$word_cache[$word])) { return (int)WLPlugSearchEngineSqlSearch2::$word_cache[$word]; } 
		
		if ($qr_res = $this->q_lookup_word->execute($word)) {
			if ($qr_res->nextRow()) {
				return WLPlugSearchEngineSqlSearch2::$word_cache[$word] = (int)$qr_res->get('word_id', ['binary' => true]);
			}
		}
		
		try {
            // insert word
            if (!($stem = trim($this->stemmer->stem($word)))) { $stem = $word; }
            if (mb_strlen($stem) > 255) { $stem = mb_substr($stem, 0, 255); }
        
            $this->opqr_insert_word->execute($word, $stem);
            if ($this->opqr_insert_word->numErrors()) { return null; }
            if (!($word_id = (int)$this->opqr_insert_word->getLastInsertID())) { return null; }
        } catch (Exception $e) {
            if ($qr_res = $this->q_lookup_word->execute($word)) {
                if ($qr_res->nextRow()) {
                    return WLPlugSearchEngineSqlSearch2::$word_cache[$word] = (int)$qr_res->get('word_id', ['binary' => true]);
                }
            }
            return null;
        }
		
		// create ngrams
		// 		$va_ngrams = caNgrams($word, 4);
		// 		$seq = 0;
		// 		
		// 		$va_ngram_buf = array();
		// 		foreach($va_ngrams as $ngram) {
		// 			$va_ngram_buf[] = "({$word_id},'{$ngram}',{$seq})";
		// 			$seq++;
		// 		}
		// 		
		// 		if (sizeof($va_ngram_buf)) {
		// 			$sql = $this->ops_insert_ngram_sql."\n".join(",", $va_ngram_buf);
		// 			$this->db->query($sql);
		// 		}
		
		return WLPlugSearchEngineSqlSearch2::$word_cache[$word] = $word_id;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _checkWordCacheSize() {
		if ((sizeof(WLPlugSearchEngineSqlSearch2::$word_cache)) > ($vn_max_size = $this->getOption('maxWordCacheSize'))) {
			WLPlugSearchEngineSqlSearch2::$word_cache = array_slice(WLPlugSearchEngineSqlSearch2::$word_cache, 0, $vn_max_size * $this->getOption('cacheCleanFactor'), true);
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function removeRowIndexing(int $subject_tablenum, int $pn_subject_row_id, ?int $pn_field_tablenum=null, $pa_field_nums=null, ?int $pn_field_row_id=null, ?int $pn_rel_type_id=null) {

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
			$va_words = self::tokenize($ps_content);
		}
		
		if((sizeof($va_words) === 1) && (mb_strtolower((string)$va_words[0]) === mb_strtolower(caGetBlankLabelText(Datamodel::getTableName($pn_content_tablenum))))) {
			$va_words = null;
		} elseif (caGetOption("INDEX_AS_IDNO", $pa_options, false) || in_array('INDEX_AS_IDNO', $pa_options, true)) {
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
			
			if($va_words) {
				foreach($va_words as $vs_word) {
					if(is_null($vs_word))  { continue; }
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
			} else {
				$va_row_insert_sql[] = "({$subject_tablenum}, {$vn_row_id}, {$pn_content_tablenum}, '{$ps_content_fieldnum}', ".($pn_content_container_id ? $pn_content_container_id : 'NULL').", {$pn_content_row_id}, 0, 0, {$vn_private}, {$vn_rel_type_id})";
				$vn_seq++;
			}
		}
		
		// do insert
		if (sizeof($va_row_insert_sql)) {
			$vs_sql = $this->insert_word_index_sql."\n".join(",", $va_row_insert_sql);
			$this->db->query($vs_sql);
			if ($this->debug) { Debug::msg("[SqlSearchDebug] Commit row indexing"); }
		}				
	}
	# -------------------------------------------------
	/**
	 * Not supported in this engine - does nothing
	 */
	public function optimizeIndex(int $tablenum) {
		// noop	
	}
	# --------------------------------------------------
	/**
	 * 
	 */
	public function engineName() {
		return 'SqlSearch2';
	}
	# --------------------------------------------------
	/**
	 * Tokenize string for indexing or search
	 *
	 * @param string $content
	 * @param bool $for_search
	 * @param int $index
	 *
	 * @return array Tokenized terms
	 */
	static public function tokenize(?string $content, ?bool $for_search=false, ?int $index=0) : array {
		$content = preg_replace('![\']+!u', '', $content);		// strip apostrophes for compatibility with SearchEngine class, which does the same to all search expressions

		$words = preg_split('!'.self::$whitespace_tokenizer_regex.'!u', strip_tags($content));
		$words = array_map(function($v) {
			$w = preg_replace('!^'.self::$punctuation_tokenizer_regex.'!u', '', html_entity_decode($v, null, 'UTF-8'));
			return mb_strtolower(preg_replace('!'.self::$punctuation_tokenizer_regex.'$!u', '', $w));
		}, $words);
		
		return self::filterStopWords($words);
	}
	# --------------------------------------------------
	/**
	 *
	 */
	static public function filterStopWords(array $words) : array {
		if(!self::$filter_stop_words) { return $words; }
		return array_filter($words, function($v) {
			return (strlen($v) && !array_key_exists($v, self::$stop_words));
		});
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
		$va_words = self::tokenize($ps_search, true);
		
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
		
		$this->insert_word_index_sql = "
			INSERT INTO ca_sql_search_word_index
			(table_num, row_id, field_table_num, field_num, field_container_id, field_row_id, word_id, boost, access, rel_type_id)
			VALUES
		";
		
		$this->insert_word_sql = "
			INSERT INTO ca_sql_search_words
			(word, stem)
			VALUES
			(?, ?)
		";
		
		$this->insert_ngram_sql = "
			INSERT INTO ca_sql_search_ngrams
			(word_id, ngram, seq)
			VALUES
		";
		
		$this->opqr_insert_word = $this->db->prepare($this->insert_word_sql);
		
		$this->delete_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete = $this->db->prepare($this->delete_sql);
		
		$this->delete_with_field_num_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?) AND (rel_type_id = ?)";
		$this->q_delete_with_field_num = $this->db->prepare($this->delete_with_field_num_sql);
		
		$this->delete_with_field_num_sql_without_rel_type_id = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?)";
		$this->q_delete_with_field_num_without_rel_type_id = $this->db->prepare($this->delete_with_field_num_sql_without_rel_type_id);
		
		$this->delete_with_field_row_id_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete_with_field_row_id = $this->db->prepare($this->delete_with_field_row_id_sql);
		
		$this->delete_with_field_row_id_and_num = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete_with_field_row_id_and_num = $this->db->prepare($this->delete_with_field_row_id_and_num);
		
		$this->delete_dependent_sql = "DELETE FROM ca_sql_search_word_index WHERE (field_table_num = ?) AND (field_row_id = ?) AND (rel_type_id = ?)";
		$this->q_delete_dependent_sql = $this->db->prepare($this->delete_dependent_sql);	
	}
	# --------------------------------------------------
	# Utils
	# --------------------------------------------------
	private function getFieldNum($pn_table_name_or_num, $ps_fieldname) {
		if (isset(WLPlugSearchEngineSqlSearch2::$fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname])) { return WLPlugSearchEngineSqlSearch2::$fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname]; }
		
		$vs_table_name = is_numeric($pn_table_name_or_num) ? Datamodel::getTableName((int)$pn_table_name_or_num) : (string)$pn_table_name_or_num;
		return WLPlugSearchEngineSqlSearch2::$fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname] = Datamodel::getFieldNum($vs_table_name, $ps_fieldname);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _filterValueToQueryValue(array $filter) : string {
		switch(strtolower($filter['operator'])) {
			case '>':
			case '<':
			case '=':
			case '>=':
			case '<=':
			case '<>':
				return (int)$filter['value'];
				break;
			case 'in':
			case 'not in':
				$tmp = explode(',', $filter['value']);
				$values = array();
				foreach($tmp as $t) {
					if ($t == 'NULL') { continue; }
					$values[] = (int)preg_replace("![^\d]+!", "", $t);
				}
				return "(".join(",", $values).")";
				break;
			case 'is':
			case 'is not':
			default:
				return is_null($filter['value']) ? 'NULL' : (string)$filter['value'];
				break;
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getElementIDForAccessPoint($subject_tablenum, $access_point) {
		$tmp = preg_split('![/\|]+!', $access_point);
		list($table, $field, $subfield, $subsubfield, $subsubsubfield) = explode('.', $tmp[0]);
		if ($table === '_fulltext') { return null; }	// ignore "_fulltext" specifier – just treat as text search
		
		$rel_table = caGetRelationshipTableName($subject_tablenum, $table);
		$rel_type_ids = ($tmp[1] && $rel_table) ? caMakeRelationshipTypeIDList($rel_table, preg_split("![,;]+!", $tmp[1])) : [];
		
		if (!($t_table = Datamodel::getInstanceByTableName($table, true))) { 
			if(in_array($table, caSearchGetTablesForAccessPoints([$tmp[0]]))) {
				return ['access_point' => $tmp[0]];
			}
			return null;
		}
		$table_num = $t_table->tableNum();
		
		// counts for relationship
		$vn_rel_type = null;
		
		if (is_array($rel_type_ids) && (sizeof($rel_type_ids) > 0)) {
			$vn_rel_type = (int)$rel_type_ids[0];
		}
		
		if(is_array($indexing_info = $this->search_indexing_config->get(Datamodel::getTableName($subject_tablenum)))) {
			$indexing_info = $indexing_info[$table]['fields'][$field] ?? null;
		}
		if (strtolower($field) == 'count') {
			if (!is_array($rel_type_ids) || !sizeof($rel_type_ids)) { $rel_type_ids = [0]; }	// for counts must pass "0" as relationship type to pull count for all reltypes in aggregate
			return array(
				'access_point' => "{$table}.{$field}",
				'relationship_type' => $vn_rel_type,
				'table_num' => $table_num,
				'element_id' => null,
				'field_num' => 'COUNT',
				'datatype' => 'COUNT',
				'element_info' => null,
				'relationship_type_ids' => $rel_type_ids,
				'type' => 'COUNT',
				'indexing_options' => $indexing_info
			);
		} elseif (strtolower($field) == 'current_value') {
		    if(!$subfield) { $subfield = '__default__'; }
		    
		    $fld_num = null;
		    if ($vn_fld_num = $this->getFieldNum($table, $subsubsubfield ? $subsubsubfield : $subsubfield)) {
		        $fld_num = "I{$vn_fld_num}";
		    } elseif($t_element = ca_metadata_elements::getInstance($subsubsubfield ? $subsubsubfield : $subsubfield)) {
		        $fld_num = "A".$t_element->getPrimaryKey();
		    }
		    return array(
				'access_point' => $tmp[0],
				'relationship_type' => $vn_rel_type,
				'table_num' => $table_num,
				'element_id' => null,
				'field_num' => $fld_num ? "CV{$subfield}_{$fld_num}" : "CV{$subfield}",
				'datatype' => 'CV',
				'element_info' => null,
				'relationship_type_ids' => $rel_type_ids,
				'policy' => $subfield,
				'type' => 'CV',
				'indexing_options' => $indexing_info
			);
		
		} elseif (is_numeric($field)) {
			$fld_num = $field;
		} else {
			$fld_num = $this->getFieldNum($table, $field);
		}
		
		if (!strlen($fld_num)) {
			$t_element = new ca_metadata_elements();
			
			$vb_is_count = false;
			if(strtolower($subfield) == 'count') {
				$subfield = null;
				$vb_is_count = true;
				if (!is_array($rel_type_ids) || !sizeof($rel_type_ids)) { $rel_type_ids = [0]; }
			}
			if ($t_element->load(array('element_code' => ($subfield ? $subfield : $field)))) {
				if ($vb_is_count) {
					return array(
						'access_point' => "{$table}.{$field}",
						'relationship_type' => $tmp[1],
						'table_num' => $table_num,
						'element_id' => $t_element->getPrimaryKey(),
						'field_num' => 'COUNT'.$t_element->getPrimaryKey(),
						'datatype' => 'COUNT',
						'element_info' => $t_element->getFieldValuesArray(),
						'relationship_type_ids' => $rel_type_ids,
						'type' => 'COUNT',
						'indexing_options' => $indexing_info
					);
				} else {
					return array(
						'access_point' => $tmp[0],
						'relationship_type' => $tmp[1],
						'table_num' => $table_num,
						'element_id' => $t_element->getPrimaryKey(),
						'field_num' => 'A'.$t_element->getPrimaryKey(),
						'datatype' => $t_element->get('datatype'),
						'element_info' => $t_element->getFieldValuesArray(),
						'relationship_type_ids' => $rel_type_ids,
						'type' => 'METADATA',
						'indexing_options' => $indexing_info
					);
				}
			}
		} else {

			return array('access_point' => $tmp[0], 'relationship_type' => $tmp[1], 'table_num' => $table_num, 'field_num' => 'I'.$fld_num, 'field_num_raw' => $fld_num, 'datatype' => null, 'relationship_type_ids' => $rel_type_ids, 'type' => 'INTRINSIC', 'indexing_options' => $indexing_info);
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
		list($text, $modifier) = $this->parseModifier($text);
		if (!is_array($parsed_value = $attrval->parseValue($text, $ap['element_info']))) {
			return null;
		}
		
		if (!in_array($attr_field, ['value_integer1', 'value_decimal1'])) { 
			throw new ApplicationException(_t('Invalid attribute field'));
		}
		$parsed_value_end = $text_upper ? $attrval->parseValue($text_upper, $ap['element_info']) : null;
				
		if($ap['type'] === 'INTRINSIC') {
			$tmp = explode('.', $ap['access_point']);
			if (!($t_table = Datamodel::getInstance($tmp[0], true))) {
				throw new ApplicationException(_t('Invalid table %1 in bundle %2', $tmp[0], $access_point));
			}
			
			$pk = $t_table->primaryKey(true);
			$table = $t_table->tableName();
			$field = $tmp[1];
			
			if(!$t_table->hasField($field)) { 
				throw new ApplicationException(_t('Invalid field %1 in bundle %2', $field, $access_point));
			}
		} else {
			$field = 'cav.'.$attr_field;
		}
		
		$sql_where = null;
		switch($modifier) {
			case '#gt#':
				$sql_where = "({$field} > ?)"; 
				$params = [$parsed_value['value_decimal1']];
				break;
			case '#gt=':
				$sql_where = "({$field} >= ?)"; 
				$params = [$parsed_value['value_decimal1']];
				break;
			case '#lt#':
				$sql_where = "({$field} < ?)"; 
				$params = [$parsed_value['value_decimal1']];
				break;
			case '#lt=':
				$sql_where = "({$field} <= ?)"; 
				$params = [$parsed_value['value_decimal1']];
				break;
			case '#eq#':
			default:
				if($parsed_value_end) {
					$sql_where = "({$field} >= ? AND {$field} <= ?)";
					$params = [$parsed_value['value_decimal1'], $parsed_value_end['value_decimal1']];
				} else {
					$sql_where = "({$field} = ?)";
					$params = [$parsed_value['value_decimal1']];
				}
				break;
		}
		
		if($ap['type'] === 'INTRINSIC') {
			$sql = "
				SELECT {$pk} row_id, 1 boost
				FROM {$table}
				WHERE
					{$sql_where}
			";
		} else {
			$sql = "
				SELECT ca.row_id, 1 boost
				FROM ca_attribute_values cav
				INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
				WHERE
					(cav.element_id = {$ap['element_info']['element_id']}) AND (ca.table_num = ?)
					AND
					{$sql_where}
			";
		}
		
		return ['sql' => $sql, 'params' => $params];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _queryForCurrencyAttribute($attrval, $ap, $text, $text_upper) {
		list($text, $modifier) = $this->parseModifier($text);
		if (!is_array($parsed_value = $attrval->parseValue($text, $ap['element_info']))) {
			return null;
		}
		
		$currency = preg_replace('![^A-Z0-9]+!', '', $parsed_value['value_longtext1']);
		if (!$currency) { 
			return null;	// no currency
		}
		
		$parsed_value_end = $text_upper ? $attrval->parseValue($text_upper, $ap['element_info']) : null;
		
		$sql_where = null;
		switch($modifier) {
			case '#gt#':
				$sql_where = "(cav.value_decimal1 > ? AND cav.value_longtext1 = ?)";
				$params = [$parsed_value['value_decimal1'], $currency];
				break;
			case '#gt=':
				$sql_where = "(cav.value_decimal1 >= ? AND cav.value_longtext1 = ?)";
				$params = [$parsed_value['value_decimal1'], $currency];
				break;
			case '#lt#':
				$sql_where = "(cav.value_decimal1 < ? AND cav.value_longtext1 = ?)";
				$params = [$parsed_value['value_decimal1'], $currency];
				break;
			case '#lt=':
				$sql_where = "(cav.value_decimal1 <= ? AND cav.value_longtext1 = ?)";
				$params = [$parsed_value['value_decimal1'], $currency];
				break;
			case '#eq#':
			default:
				if($parsed_value_end) {
					$sql_where = "((cav.value_decimal1 >= ? AND cav.value_decimal1 <= ?) AND (cav.value_longtext1 = ?))";
					$params = [$parsed_value['value_decimal1'], $parsed_value_end['value_decimal1'], $currency];
				} else {
					$sql_where = "(cav.value_decimal1 = ? AND cav.value_longtext1 = ?)";
					$params = [$parsed_value['value_decimal1'], $currency];
				}
				break;
		}

		$sql = "
			SELECT ca.row_id, 1 boost
			FROM ca_attribute_values cav
			INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
			WHERE
				(cav.element_id = {$ap['element_info']['element_id']}) AND (ca.table_num = ?)
				AND
				{$sql_where}
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
		list($text, $modifier) = $this->parseModifier($text);
		
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

		$sfield = $efield = $params = null;
		
		if($ap['type'] === 'INTRINSIC') {
			$tmp = explode('.', $ap['access_point']);
			if (!($t_table = Datamodel::getInstance($tmp[0], true))) {
				throw new ApplicationException(_t('Invalid table %1 in bundle %2', $tmp[0], $access_point));
			}
			
			$pk = $t_table->primaryKey(true);
			$table = $t_table->tableName();
			
			$fi = $t_table->getFieldInfo($tmp[1]);
			
			$sfield = $table.'.'.$fi['START'];
			$efield = $table.'.'.$fi['END'];
		} else {
			$sfield = 'cav.value_decimal1';
			$efield = 'cav.value_decimal2';
			
			$params = [$dates['start'], $dates['end'], $dates['start'], $dates['end'], $dates['start'], $dates['end']];
		}	
		
		switch($modifier) {
			case '#gt#':
				$sql_where = "({$sfield} > ?)"; 
				$params = [$dates['end']];
				break;
			case '#gt=':
				$sql_where = "({$sfield} >= ?)"; 
				$params = [$dates['start']];
				break;
			case '#lt#':
				$sql_where = "({$efield} < ?)"; 
				$params = [$dates['start']];
				break;
			case '#lt=':
				$sql_where = "({$efield} <= ?)"; 
				$params = [$dates['end']];
				break;
			case '#eq#':
				$sql_where = "(({$sfield} BETWEEN ? AND ?) AND ({$efield} BETWEEN ? AND ?))"; 
				$params = [$dates['start'], $dates['end'], $dates['start'], $dates['end']];
				break;
			default:
				$sql_where = "(
						({$sfield} BETWEEN ? AND ?)
						OR
						({$efield} BETWEEN ? AND ?)
						OR
						({$sfield} <= ? AND {$efield} >= ?)	
					)";
				$params = [$dates['start'], $dates['end'], $dates['start'], $dates['end'], $dates['start'], $dates['end']];
				break;
		}
		
		if($ap['type'] === 'INTRINSIC') {
			$sql = "
				SELECT {$pk} row_id, 1 boost
				FROM {$table}
				WHERE
					{$sql_where}
			";
		} else {
			$sql = "
				SELECT ca.row_id, 1 boost
				FROM ca_attribute_values cav
				INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
				WHERE
					(cav.element_id = {$ap['element_info']['element_id']}) AND (ca.table_num = ?)
					AND
					{$sql_where}
			";
		}
		
		return ['sql' => $sql, 'params' => $params];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function parseModifier($text) {
		$modifier = null;
		if(preg_match("!^(#[gtleq]+[#=]{1})!i", $text, $m)) {
			$modifier = strtolower($m[1]);
			$text = preg_replace("!^(#[gtleq]+[#=]{1})!i", '', $text);
		}
		return [$text, $modifier];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function useSearchIndexForAP(array $ap) : bool {
		if(is_array($ap['indexing_options'])) {
			foreach(['INDEX_AS_IDNO', 'INDEX_ANCESTORS', 'CHILDREN_INHERIT', 'ANCESTORS_INHERIT', 'INDEX_AS_MIMETYPE'] as $k) {
				if(in_array($k, $ap['indexing_options'], true) || isset($ap['indexing_options'][$k])) { return true; }
			}
		}
		return false;
	}
	# -------------------------------------------------------
}
