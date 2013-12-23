<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/SqlSearch.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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

	private $opa_doc_content_buffer;
	
	private $ops_insert_sql; 	// sql INSERT statement (for indexing)
	
	private $ops_delete_sql;	// sql DELETE statement (for unindexing)
	private $opqr_delete;		// prepared statement for delete (subject_tablenum and subject_row_id only specified)
	private $ops_delete_with_field_specification_sql;		// sql DELETE statement (for unindexing)
	private $opqr_delete_with_field_specification;			// prepared statement for delete with field_tablenum and field_num specified
	
	private $opqr_update_index_in_place;
	
	private $opo_stemmer;		// snoball stemmer
	private $opb_do_stemming = true;
	
	private $opo_tep;			// date/time expression parse
	
	static $s_word_cache = array();						// cached word-to-word_id values used when indexing
	static $s_metadata_elements; 						// cached metadata element info
	static $s_fieldnum_cache = array();				// cached field name-to-number values used when indexing
	static $s_doc_content_buffer = array();			// content buffer used when indexing
	
	//
	// TODO: Obviously these are specific to English. We need to add stop words for other languages.
	//
	static $s_stop_words = array("a", "an", "the", "of", "to");
	
	# -------------------------------------------------------
	public function __construct() {
		parent::__construct();
		
		$this->opo_tep = new TimeExpressionParser();
		
		$this->ops_lookup_word_sql = "
			SELECT word_id 
			FROM ca_sql_search_words
			WHERE
				word = ?
		";
		
		$this->opqr_lookup_word = $this->opo_db->prepare($this->ops_lookup_word_sql);
		
		$this->ops_insert_word_index_sql = "
			INSERT  INTO ca_sql_search_word_index
			(table_num, row_id, field_table_num, field_num, field_row_id, word_id, boost, access)
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
		
		$this->ops_delete_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?)";
		$this->opqr_delete = $this->opo_db->prepare($this->ops_delete_sql);
		
		$this->ops_delete_with_field_num_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?)";
		$this->opqr_delete_with_field_num = $this->opo_db->prepare($this->ops_delete_with_field_num_sql);
		
		$this->ops_delete_with_field_row_id_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_row_id = ?)";
		$this->opqr_delete_with_field_row_id = $this->opo_db->prepare($this->ops_delete_with_field_row_id_sql);
		
		$this->ops_delete_with_field_row_id_and_num = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?) AND (field_row_id = ?)";
		$this->opqr_delete_with_field_row_id_and_num = $this->opo_db->prepare($this->ops_delete_with_field_row_id_and_num);
		
		$this->ops_delete_dependent_sql = "DELETE FROM ca_sql_search_word_index WHERE (field_table_num = ?) AND (field_row_id = ?)";
		$this->opqr_delete_dependent_sql = $this->opo_db->prepare($this->ops_delete_dependent_sql);
		
		$this->opo_stemmer = new SnoballStemmer();
		$this->opb_do_stemming = (int)trim($this->opo_search_config->get('search_sql_search_do_stemming')) ? true : false;
		
		
		if (!($this->ops_indexing_tokenizer_regex = trim($this->opo_search_config->get('indexing_tokenizer_regex')))) {
			$this-> ops_indexing_tokenizer_regex = "^\pL\pN\pNd/_#\@\&\.";
		}
		if (!($this->ops_search_tokenizer_regex = trim($this->opo_search_config->get('search_tokenizer_regex')))) {
			$this->ops_search_tokenizer_regex = "^\pL\pN\pNd/_#\@\&";
		}
		
		if (!is_array($this->opa_asis_regexes = $this->opo_search_config->getList('asis_regexes'))) {
			$this->opa_asis_regexes = array();
		}
		
		
		//$this->opqr_insert_ngram = $this->opo_db->prepare($this->ops_insert_ngram_sql);
		
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
		$this->opa_options = array(
				'limit' => 2000,													// maximum number of hits to return [default=2000]  ** NOT CURRENTLY ENFORCED -- MAY BE DROPPED **
				'maxContentBufferSize' => 5000,							// maximum number of indexed content items to accumulate before writing to the database
				'maxWordIndexInsertSegmentSize' => 2500,		// maximum number of word index rows to put into a single insert
				'maxWordCacheSize' => 3000,								// maximum number of words to cache while indexing before purging
				'cacheCleanFactor' => 0.50,									// percentage of words retained when cleaning the cache
				
				'omitPrivateIndexing' => false								//
		);
		
		// Defines specific capabilities of this engine and plug-in
		// The indexer and engine can use this information to optimize how they call the plug-in
		$this->opa_capabilities = array(
			'incremental_reindexing' => true		// can update indexing using only changed fields, rather than having to reindex the entire row (and related stuff) every time
		);
		
		if (defined('__CA_SEARCH_IS_FOR_PUBLIC_DISPLAY__')) {
			$this->setOption('omitPrivateIndexing', true); 
		}
	}
	# -------------------------------------------------------
	/**
	 * Completely clear index (usually in preparation for a full reindex)
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
	public function __destruct() {	
		if (is_array(WLPlugSearchEngineSqlSearch::$s_doc_content_buffer) && sizeof(WLPlugSearchEngineSqlSearch::$s_doc_content_buffer)) {
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
	public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null) {
		$this->_setMode('search');
		$this->opa_filters = $pa_filters;
		
		if (!($t_instance = $this->opo_datamodel->getInstanceByTableNum($pn_subject_tablenum, true))) {
			// TODO: Better error message
			die("Invalid subject table");
		}
		
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
						$va_wheres[] = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					} else {
						// join in primary table
						$va_wheres[] = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					}
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
			";
			$qr_res = $this->opo_db->query($vs_sql);
		} else {
			$this->_createTempTable('ca_sql_search_search_final');
			$this->_doQueriesForSqlSearch($po_rewritten_query, $pn_subject_tablenum, 'ca_sql_search_search_final', 0);
				
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
						$va_wheres[] = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					} else {
						$t_table = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true);
						// join in primary table
						if (!isset($va_joins[$va_tmp[0]])) {
							$va_joins[$va_tmp[0]] = "INNER JOIN ".$va_tmp[0]." ON ".$va_tmp[0].".".$t_table->primaryKey()." = ca_sql_search_search_final.row_id";
						}
						$va_wheres[] = "(".$va_filter['field']." ".$va_filter['operator']." ".$this->_filterValueToQueryValue($va_filter).")";
					}
				}
			}
			
			$vs_join_sql = join("\n", $va_joins);
			$vs_where_sql = '';
			if (sizeof($va_wheres)) {
				$vs_where_sql = " WHERE ".join(" AND ", $va_wheres);
			}
			$vs_sql = "
				SELECT DISTINCT row_id
				FROM ca_sql_search_search_final
				{$vs_join_sql}
				{$vs_where_sql}
				ORDER BY
					boost DESC
			";
			$qr_res = $this->opo_db->query($vs_sql);
		
			$this->_dropTempTable('ca_sql_search_search_final');
		}
		
		$va_hits = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_hits[] = array(
				'subject_id' => $pn_subject_tablenum,
				'subject_row_id' => $va_row['row_id']
			);
		}
		return new WLPlugSearchEngineSqlSearchResult($va_hits, $pn_subject_tablenum);
	}
	# -------------------------------------------------------
	private function _createTempTable($ps_name) {
		$this->opo_db->query("DROP TABLE IF EXISTS {$ps_name}");
		$this->opo_db->query("
			CREATE TEMPORARY TABLE {$ps_name} (
				row_id int unsigned not null,
				boost int not null default 1,
				
				unique key i_row_id (row_id),
				key i_boost (boost)
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
	private function _getElementIDForAccessPoint($ps_access_point) {
		list($vs_table, $vs_field) = explode('.', $ps_access_point);
		if (!($t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true))) { return null; }
		$vs_table_num = $t_table->tableNum();
		
		if (is_numeric($vs_field)) {
			$vs_fld_num = $vs_field;
		} else {
			$vs_fld_num = $this->getFieldNum($vs_table, $vs_field);
		}
		
		if (!strlen($vs_fld_num)) {
			$t_element = new ca_metadata_elements();
			if ($t_element->load(array('element_code' => $vs_field))) {
				switch ($t_element->get('datatype')) {
					default:
						return array('table_num' => $vs_table_num, 'element_id' => $t_element->getPrimaryKey(), 'field_num' => 'A'.$t_element->getPrimaryKey(), 'datatype' => $t_element->get('datatype'), 'element_info' => $t_element->getFieldValuesArray());
						break;
				}
			}
		} else {
			return array('table_num' => $vs_table_num, 'field_num' => 'I'.$vs_fld_num, 'field_num_raw' => $vs_fld_num, 'datatype' => null);
		}

		return null;
	}
	# -------------------------------------------------------
	private function _doQueriesForSqlSearch($po_rewritten_query, $pn_subject_tablenum, $ps_dest_table, $pn_level=0) {		// query is always of type Zend_Search_Lucene_Search_Query_Boolean
		$vn_i = 0;
		$va_old_signs = $po_rewritten_query->getSigns();
		foreach($po_rewritten_query->getSubqueries() as $o_lucene_query_element) {
			$vb_is_blank_search = false;
			
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
			
			
			switch(get_class($o_lucene_query_element)) {
				case 'Zend_Search_Lucene_Search_Query_Boolean':
					$this->_createTempTable('ca_sql_search_temp_'.$pn_level);
					
					$this->_doQueriesForSqlSearch($o_lucene_query_element, $pn_subject_tablenum, 'ca_sql_search_temp_'.$pn_level, ($pn_level+1));
					
					
					// merge with current destination
					switch($vs_op) {
						case 'AND':
							// and
							$this->_createTempTable($ps_dest_table.'_acc');
							
							if ($vn_i == 0) {
								$vs_sql = "
									INSERT IGNORE INTO {$ps_dest_table}
									SELECT DISTINCT row_id, boost
									FROM ca_sql_search_temp_{$pn_level}
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
							} else {
								$vs_sql = "
									INSERT IGNORE INTO {$ps_dest_table}_acc
									SELECT mfs.row_id, SUM(mfs.boost)
									FROM {$ps_dest_table} mfs
									INNER JOIN ca_sql_search_temp_{$pn_level} AS ftmp1 ON ftmp1.row_id = mfs.row_id
									GROUP BY mfs.row_id
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								
								$qr_res = $this->opo_db->query("TRUNCATE TABLE {$ps_dest_table}");
								
								$qr_res = $this->opo_db->query("INSERT INTO {$ps_dest_table} SELECT row_id, boost FROM {$ps_dest_table}_acc");
							} 
							$this->_dropTempTable($ps_dest_table.'_acc');
							break;
						case 'NOT':
							
							$vs_sql = "
								DELETE FROM {$ps_dest_table} WHERE row_id IN
								(SELECT row_id FROM ca_sql_search_temp_{$pn_level})
							";
						
							//print "$vs_sql<hr>";
							$qr_res = $this->opo_db->query($vs_sql);
							break;
						default:
						case 'OR':
							// or
							$vs_sql = "
								INSERT IGNORE INTO {$ps_dest_table}
								SELECT row_id, SUM(boost)
								FROM ca_sql_search_temp_{$pn_level}
								GROUP BY row_id
							";
							//print "$vs_sql<hr>";
							$qr_res = $this->opo_db->query($vs_sql);
							break;
					}
					
					$this->_dropTempTable('ca_sql_search_temp_'.$pn_level);
					break;
				case 'Zend_Search_Lucene_Search_Query_Term':
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				case 'Zend_Search_Lucene_Search_Query_Phrase':
				case 'Zend_Search_Lucene_Search_Query_Range':
					$va_ft_terms = array();
					$va_ft_like_terms = array();
					$va_ft_stem_terms = array();
					
					$vs_direct_sql_query = null;
					$pa_direct_sql_query_params = null; // set to array with values to use with direct SQL query placeholders or null to pass single standard table_num value as param (most queries just need this single value)
					
					$va_tmp = array();
					$vs_access_point = '';
					$va_raw_terms = array();
					switch(get_class($o_lucene_query_element)) {
						case 'Zend_Search_Lucene_Search_Query_Range':
							$va_lower_term = $o_lucene_query_element->getLowerTerm();
							$va_upper_term = $o_lucene_query_element->getUpperTerm();
							$va_element = $this->_getElementIDForAccessPoint($va_lower_term->field);
							
							switch($va_element['datatype']) {
								case 4:		// geocode
									$t_geocode = new GeocodeAttributeValue();
									$va_parsed_value = $t_geocode->parseValue($va_lower_term->text, $va_element['element_info']);
									$vs_lower_lat = $va_parsed_value['value_decimal1'];
									$vs_lower_long = $va_parsed_value['value_decimal2'];
									
									$va_parsed_value = $t_geocode->parseValue($va_upper_term->text, $va_element['element_info']);
									$vs_upper_lat = $va_parsed_value['value_decimal1'];
									$vs_upper_long = $va_parsed_value['value_decimal2'];
									
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
								case 6:		// currency
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
								case 10:	// timecode
									$t_timecode = new TimecodeAttributeValue();
									$va_parsed_value = $t_timecode->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_timecode->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case 8: 	// length
									$t_len = new LengthAttributeValue();
									$va_parsed_value = $t_len->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_len->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case 9: 	// weight
									$t_weight = new WeightAttributeValue();
									$va_parsed_value = $t_weight->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_weight->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case 11: 	// integer
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
								case 12:	// decimal
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
						 	$va_words = array();
						 	foreach($o_lucene_query_element->getQueryTerms() as $o_term) {
								if (!$vs_access_point && ($vs_field = $o_term->field)) { $vs_access_point = $vs_field; }
								
								$va_raw_terms[] = $vs_text = (string)$o_term->text;
								if (strlen($vs_escaped_text = $this->opo_db->escape($vs_text))) {
									$va_words[] = $vs_escaped_text;
								}
							}
							if (!sizeof($va_words)) { continue(3); }
							
							$va_ap_tmp = explode(".", $vs_access_point);
							$vn_fld_table = $vn_fld_num = null;
							if(sizeof($va_ap_tmp) == 2) {
								$va_element = $this->_getElementIDForAccessPoint($vs_access_point);
								if ($va_element) {
									$vs_fld_num = $va_element['field_num'];
									$vs_fld_table_num = $va_element['table_num'];
									$vs_fld_limit_sql = " AND (swi.field_table_num = {$vs_fld_table_num} AND swi.field_num = '{$vs_fld_num}')";
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
								if (!$qr_count->nextRow() || !(int)$qr_count->get('c')) { 
									foreach($va_temp_tables as $vs_temp_table) {
										$this->_dropTempTable($vs_temp_table);
									}
									break(2); 
								}
								
								$va_temp_tables[] = $vs_temp_table;	
							}
							
							$vs_results_temp_table = array_pop($va_temp_tables);
							
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
						case 'Zend_Search_Lucene_Search_Query_MultiTerm':
							$va_ft_like_term_list = array();
							
							foreach($o_lucene_query_element->getTerms() as $o_term) {
								$va_raw_terms[] = $vs_term = (string)(method_exists($o_term, "getTerm") ? $o_term->getTerm()->text : $o_term->text);
								if (!$vs_access_point && ($vs_field = method_exists($o_term, "getTerm") ? $o_term->getTerm()->field : $o_term->field)) { $vs_access_point = $vs_field; }
								
								$vs_stripped_term = preg_replace('!\*+$!u', '', $vs_term);
								$va_ft_like_terms[] = $vs_stripped_term.($vb_had_wildcard ? '%' : '');
							}
							break;
						default:
							$vs_access_point = $o_lucene_query_element->getTerm()->field;
							$vs_term = $o_lucene_query_element->getTerm()->text;
						
							if ($vs_access_point && (mb_strtoupper($vs_term) == _t('[BLANK]'))) {
								$vb_is_blank_search = true; 
								break;
							}
							$va_terms = $this->_tokenize($vs_term, true, $vn_i);
							$vb_output_term = false;
							foreach($va_terms as $vs_term) {
								if (in_array(trim(mb_strtolower($vs_term, 'UTF-8')), WLPlugSearchEngineSqlSearch::$s_stop_words)) { continue; }
								if (get_class($o_lucene_query_element) != 'Zend_Search_Lucene_Search_Query_MultiTerm') {
									$vs_stripped_term = preg_replace('!\*+$!u', '', $vs_term);
										
										// do stemming
										if ($this->opb_do_stemming) {
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
										$vb_output_term = true;	
								}
							}
							if ($vb_output_term) {
								$va_raw_terms[] = $vs_term;
							} else {
								$vn_i--;
							}
							break;
					}
					
					$vs_fld_num = $vs_table_num = $t_table = null;
					$vb_ft_bit_optimization = false;
					if ($vs_access_point) {
						list($vs_table, $vs_field, $vs_sub_field) = explode('.', $vs_access_point);
						if (in_array($vs_table, array('created', 'modified'))) {
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
											SELECT ccl.logged_row_id, 1
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
											SELECT ccl.logged_row_id, 1
											FROM ca_change_log ccl
											WHERE
												(ccl.log_datetime BETWEEN ".(int)$va_range['start']." AND ".(int)$va_range['end'].")
												AND
												(ccl.logged_table_num = ?)
												AND
												(ccl.changetype = 'U')
												{$vs_user_sql}
										UNION
											SELECT ccls.subject_row_id, 1
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
							if ($vs_table && $vs_field) {
								$t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
								if ($t_table) {
									$vs_table_num = $t_table->tableNum();
									if (is_numeric($vs_field)) {
										$vs_fld_num = 'I'.$vs_field;
										$vn_fld_num = (int)$vs_field;
									} else {
										$vn_fld_num = $this->getFieldNum($vs_table, $vs_field);
										$vs_fld_num = 'I'.$vn_fld_num;
										
										if (!strlen($vn_fld_num)) {
											$t_element = new ca_metadata_elements();
											if ($t_element->load(array('element_code' => ($vs_sub_field ? $vs_sub_field : $vs_field)))) {
												$vn_fld_num = $t_element->getPrimaryKey();
												$vs_fld_num = 'A'.$vn_fld_num;
												
												if (!$vb_is_blank_search) {
													//
													// For certain types of attributes we can directly query the
													// attributes in the database rather than using the full text index
													// This allows us to do "intelligent" querying... for example on date ranges
													// parsed from natural language input and for length dimensions using unit conversion
													//
													switch($t_element->get('datatype')) {
														case 2:		// dates		
															$vb_all_numbers = true;
															foreach($va_raw_terms as $vs_term) {
																if (!is_numeric($vs_term)) {
																	$vb_all_numbers = false;
																	break;
																}
															}
															$vs_raw_term = join(' ', $va_raw_terms);
															$vb_exact = ($vs_raw_term{0} == "#") ? true : false;	// dates prepended by "#" are considered "exact" or "contained - the matched dates must be wholly contained by the search term
															if ($vb_exact) {
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
																}
															} else {
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
																}
															}
															break;
														case 4:		// geocode
															$t_geocode = new GeocodeAttributeValue();
															// If it looks like a lat/long pair that has been tokenized by Lucene
															// into oblivion rehydrate it here.
															if ($va_coords = caParseGISSearch(join(' ', $va_raw_terms))) {
																
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
															}
															break;
														case 6:		// currency
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
															break;
														case 8:		// length
															$t_len = new LengthAttributeValue();
															$va_parsed_value = $t_len->parseValue(array_shift($va_raw_terms), $t_element->getFieldValuesArray());
															$vn_len = $va_parsed_value['value_decimal1'];	// this is always in meters so we can compare this value to the one in the database
															
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
															break;
														case 9:		// weight
															$t_weight = new WeightAttributeValue();
															$va_parsed_value = $t_weight->parseValue(array_shift($va_raw_terms), $t_element->getFieldValuesArray());
															$vn_weight = $va_parsed_value['value_decimal1'];	// this is always in kilograms so we can compare this value to the one in the database
															
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
															break;
														case 10:	// timecode
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
															break;
														case 11: 	// integer
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
															break;
														case 12:	// decimal
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
															break;
													}
												}	
											} else { // neither table fields nor elements, i.e. 'virtual' fields like _count should 
												$vn_fld_num = false;
												$vs_fld_num = $vs_field;
											}
										}
									}
									if ($t_table->getFieldInfo($t_table->fieldName($vn_fld_num), 'FIELD_TYPE') == FT_BIT) {
										$vb_ft_bit_optimization = true;
									}
								}
							}
						}
					}
					
					//
					// If we're querying on the fulltext index then we need to construct
					// the query here... if we already have a direct SQL query to run then we can skip this
					//
					if ($vb_is_blank_search) {
						$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = '{$vs_fld_num}') AND (swi.word_id = 0))";
						
						if (!sizeof($va_sql_where)) { continue; }
						$vs_sql_where = join(' OR ', $va_sql_where);
					} elseif (!$vs_direct_sql_query) {
						$va_sql_where = array();
						if (sizeof($va_ft_terms)) {
							if (($t_table) && (strlen($vs_fld_num) > 1)) {
								$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = '{$vs_fld_num}') AND (sw.word IN (".join(',', $va_ft_terms).")))";
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
					
					
					//print "OP=$vs_op<br>";
					if ($vn_i == 0) {
						if ($vs_direct_sql_query) {
							$vs_direct_sql_query = str_replace('^JOIN', "", $vs_direct_sql_query);
						}
						$vs_sql = ($vs_direct_sql_query) ? "INSERT IGNORE INTO {$ps_dest_table} {$vs_direct_sql_query}" : "
							INSERT IGNORE INTO {$ps_dest_table}
							SELECT swi.row_id, SUM(swi.boost)
							FROM ca_sql_search_word_index swi
							".((!$vb_is_blank_search) ? "INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id" : '')."
							WHERE
								{$vs_sql_where}
								AND
								swi.table_num = ?
								".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
							GROUP BY swi.row_id 
						";
						
						if ((($vn_num_terms = (sizeof($va_ft_terms) + sizeof($va_ft_like_terms) + sizeof($va_ft_stem_terms))) > 1) && (!$vs_direct_sql_query)){
							$vs_sql .= " HAVING count(distinct sw.word_id) = {$vn_num_terms}";
						}
						
						if ($this->debug) { print 'FIRST: '.$vs_sql." [$pn_subject_tablenum]<hr>\n"; }
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql, is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array((int)$pn_subject_tablenum));
					} else {
						switch($vs_op) {
							case 'AND':
								if ($vs_direct_sql_query) {
									$vs_direct_sql_query = str_replace('^JOIN', "INNER JOIN {$ps_dest_table} AS ftmp1 ON ftmp1.row_id = ca.row_id", $vs_direct_sql_query);
								}
								$this->_createTempTable($ps_dest_table.'_acc');
								$vs_sql = ($vs_direct_sql_query) ? "INSERT IGNORE INTO {$ps_dest_table}_acc {$vs_direct_sql_query}" : "
									INSERT IGNORE INTO {$ps_dest_table}_acc
									SELECT swi.row_id, SUM(swi.boost)
									FROM ca_sql_search_word_index swi
									INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id
									INNER JOIN {$ps_dest_table} AS ftmp1 ON ftmp1.row_id = swi.row_id
									WHERE
										{$vs_sql_where}
										AND
										swi.table_num = ?
										".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
									GROUP BY
										swi.row_id
								";
								
								if (($vn_num_terms = (sizeof($va_ft_terms) + sizeof($va_ft_like_terms) + sizeof($va_ft_stem_terms))) > 1) {
									$vs_sql .= " HAVING count(distinct sw.word_id) = {$vn_num_terms}";
								}
	
								if ($this->debug) { print 'AND:'.$vs_sql."<hr>\n"; }
								$qr_res = $this->opo_db->query($vs_sql, is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array((int)$pn_subject_tablenum));
								$qr_res = $this->opo_db->query("TRUNCATE TABLE {$ps_dest_table}");
								$qr_res = $this->opo_db->query("INSERT INTO {$ps_dest_table} SELECT row_id, boost FROM {$ps_dest_table}_acc");
								
								//$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_sql_search_temp_2");
								
								$this->_dropTempTable($ps_dest_table.'_acc');
								break;
							case 'NOT':
								if ($vs_direct_sql_query) {
									$vs_direct_sql_query = str_replace('^JOIN', "", $vs_direct_sql_query);
								}
								
								$vs_sql = "
									SELECT row_id
									FROM ca_sql_search_words sw
									INNER JOIN ca_sql_search_word_index AS swi ON sw.word_id = swi.word_id
									WHERE 
										".($vs_sql_where ? "{$vs_sql_where} AND " : "")." swi.table_num = ? 
										".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '');
								
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql, is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array((int)$pn_subject_tablenum));
								$va_ids = $qr_res->getAllFieldValues("row_id");
								
								$vs_sql = "
									DELETE FROM {$ps_dest_table} 
									WHERE 
										row_id IN (?)
								";
							
								$qr_res = $this->opo_db->query($vs_sql, array($va_ids));
								//print "$vs_sql<hr>";
								break;
							default:
							case 'OR':
								if ($vs_direct_sql_query) {
									$vs_direct_sql_query = str_replace('^JOIN', "", $vs_direct_sql_query);
								}
								$vs_sql = ($vs_direct_sql_query) ? "INSERT IGNORE INTO {$ps_dest_table} {$vs_direct_sql_query}" : "
									INSERT IGNORE INTO {$ps_dest_table}
									SELECT swi.row_id, SUM(swi.boost)
									FROM ca_sql_search_word_index swi
									INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id
									WHERE
										{$vs_sql_where}
										AND
										swi.table_num = ?
										".($this->getOption('omitPrivateIndexing') ? " AND swi.access = 0" : '')."
									GROUP BY
										swi.row_id
								";
	
								if ($this->debug) { print 'OR'.$vs_sql."<hr>\n"; }
								$qr_res = $this->opo_db->query($vs_sql, is_array($pa_direct_sql_query_params) ? $pa_direct_sql_query_params : array((int)$pn_subject_tablenum));
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
			$vn_i++;
		}	
	}
	# -------------------------------------------------------
	# Indexing
	# -------------------------------------------------------
	public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$this->_setMode('indexing');
		
		if ($this->debug) { print "[SqlSearchDebug] startRowIndexing: $pn_subject_tablenum/$pn_subject_row_id<br>\n"; }

		$this->opn_indexing_subject_tablenum = $pn_subject_tablenum;
		$this->opn_indexing_subject_row_id = $pn_subject_row_id;
	}
	# -------------------------------------------------------
	public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options) {
		if (is_array($pm_content)) {
			$pm_content = serialize($pm_content);
		}
		
		$vn_boost = 1;
		if (isset($pa_options['BOOST'])) {
			$vn_boost = intval($pa_options['BOOST']);
		}
		
		if ($this->debug) { print "[SqlSearchDebug] indexField: $pn_content_tablenum/$ps_content_fieldname [$pn_content_row_id] =&gt; $pm_content<br>\n"; }
	
		if (in_array((string)'DONT_TOKENIZE', array_values($pa_options), true)) { 
			$pa_options['DONT_TOKENIZE'] = true;  
		} else {
			if (!isset($pa_options['DONT_TOKENIZE'])) { 
				$pa_options['DONT_TOKENIZE'] = false; 
			}
		}
		$vb_tokenize = $pa_options['DONT_TOKENIZE'] ? false : true;
		
		if (!isset($pa_options['PRIVATE'])) { $pa_options['PRIVATE'] = 0; }
		if (in_array('PRIVATE', $pa_options, true)) { $pa_options['PRIVATE'] = 1; }
		$vn_private = $pa_options['PRIVATE'] ? 1 : 0;
		
		if (!isset($pa_options['datatype'])) { $pa_options['datatype'] = null; }
		
		if ($ps_content_fieldname[0] == 'A') {
			$vn_field_num_proc = (int)substr($ps_content_fieldname, 1);
			
			// do we need to index this (don't index attribute types that we'll search directly)
			if (WLPlugSearchEngineSqlSearch::$s_metadata_elements[$vn_field_num_proc]) {
				switch(WLPlugSearchEngineSqlSearch::$s_metadata_elements[$vn_field_num_proc]['datatype']) {
					case 0:		//container
					case 2:		//daterange
					case 4:		//geocode
					case 6:		//currency
					case 8:		//length
					case 9:		//weight
					case 10:	//timecode
					case 15:	//media
					case 16:	//file
					case 17:	//place
					case 18:	//occurrence
						return;
				}
			}
		} 
		
		if (strlen((string)$pm_content) == 0) { 
			$va_words = null;
		} else {
			// Tokenize string
			if ($vb_tokenize) {
				$va_words = $this->_tokenize((string)$pm_content);
			} else {
				// always break things up on spaces, even if we're not actually tokenizing
				$va_words = preg_split("![ ]+!", (string)$pm_content);
			}
		}
		WLPlugSearchEngineSqlSearch::$s_doc_content_buffer[$this->opn_indexing_subject_tablenum.'/'.$this->opn_indexing_subject_row_id.'/'.$pn_content_tablenum.'/'.$ps_content_fieldname.'/'.$pn_content_row_id.'/'.$vn_boost.'/'.$vn_private][] = $va_words;
	}
	# ------------------------------------------------
	public function commitRowIndexing() {
		if (sizeof(WLPlugSearchEngineSqlSearch::$s_doc_content_buffer) > $this->getOption('maxContentBufferSize')) {
			$this->flushContentBuffer();
		}
	}
	# ------------------------------------------------
	public function flushContentBuffer() {
		// add fields to doc
		$va_row_sql = array();
		$vn_segment = 0;
		
		foreach(WLPlugSearchEngineSqlSearch::$s_doc_content_buffer as $vs_key => $va_content_list) {
			foreach($va_content_list as $vn_i => $va_content) {
				$vn_seq = 0;
				//$va_word_list = is_array($va_content) ? array_flip($va_content) : null;
				$va_word_list = is_array($va_content) ? $va_content : null;
			
				$va_tmp = explode('/', $vs_key);
				$vn_table_num= (int)$va_tmp[0];
				$vn_row_id= (int)$va_tmp[1];
				$vn_content_table_num = (int)$va_tmp[2];
				$vn_content_field_num = $va_tmp[3];
				$vn_content_row_id = (int)$va_tmp[4];
				$vn_boost= (int)$va_tmp[5];
				$vn_access= (int)$va_tmp[6];
			
				if (!defined("__CollectiveAccess_IS_REINDEXING__") && $this->can('incremental_reindexing')) {
					$this->removeRowIndexing($vn_table_num, $vn_row_id, $vn_content_table_num, $vn_content_field_num);
				}
			
				if (is_array($va_word_list)) {
					//foreach($va_word_list as $vs_word => $vn_x) {
					foreach($va_word_list as $vs_word) {
						if(!strlen((string)$vs_word)) { continue; }
						if (!($vn_word_id = (int)$this->getWordID((string)$vs_word))) { continue; }
				
						$va_row_sql[$vn_segment][] = '('.$vn_table_num.','.$vn_row_id.','.$vn_content_table_num.',\''.$vn_content_field_num.'\','.$vn_content_row_id.','.$vn_word_id.','.$vn_boost.','.$vn_access.')';	
						$vn_seq++;
				
						if (sizeof($va_row_sql[$vn_segment]) > $this->getOption('maxWordIndexInsertSegmentSize')) { $vn_segment++; }
					}
				} else {
					// index blank value
					$va_row_sql[$vn_segment][] = '('.$vn_table_num.','.$vn_row_id.','.$vn_content_table_num.',\''.$vn_content_field_num.'\','.$vn_content_row_id.',0,0,'.$vn_access.')';	
					$vn_seq++;
				
					if (sizeof($va_row_sql[$vn_segment]) > $this->getOption('maxWordIndexInsertSegmentSize')) { $vn_segment++; }
				}
			}
		}
		
		// add new indexing
		
		if (sizeof($va_row_sql)) {
			foreach($va_row_sql as $vn_segment => $va_row_sql_list) {
				if (sizeof($va_row_sql_list)) {
					$vs_sql = $this->ops_insert_word_index_sql."\n".join(",", $va_row_sql_list);
					$this->opo_db->query($vs_sql);
				}
			}
			if ($this->debug) { print "[SqlSearchDebug] Commit row indexing<br>\n"; }
		}
	
		// clean up
		//$this->opn_indexing_subject_tablenum = null;
		//$this->opn_indexing_subject_row_id = null;
		WLPlugSearchEngineSqlSearch::$s_doc_content_buffer = array();
		
		$this->_checkWordCacheSize();
	}
	# ------------------------------------------------
	public function getWordID($ps_word) {
		if (!strlen($ps_word = trim(mb_strtolower($ps_word, "UTF-8")))) { return null; }
		if ((int)WLPlugSearchEngineSqlSearch::$s_word_cache[(string)$ps_word]) { return (int)WLPlugSearchEngineSqlSearch::$s_word_cache[(string)$ps_word]; } 
		
		if ($qr_res = $this->opqr_lookup_word->execute((string)$ps_word)) {
			if ($qr_res->nextRow()) {
				return WLPlugSearchEngineSqlSearch::$s_word_cache[(string)$ps_word] = (int)$qr_res->get('word_id', array('binary' => true));
			}
		}
		
		// insert word
		if (!($vs_stem = trim($this->opo_stemmer->stem((string)$ps_word)))) { $vs_stem = (string)$ps_word; }
		$this->opqr_insert_word->execute((string)$ps_word, $vs_stem);
		if ($this->opqr_insert_word->numErrors()) { return null; }
		if (!($vn_word_id = (int)$this->opqr_insert_word->getLastInsertID())) { return null; }
		
		// create ngrams
		$va_ngrams = caNgrams((string)$ps_word, 4);
		$vn_seq = 0;
		
		$va_ngram_buf = array();
		foreach($va_ngrams as $vs_ngram) {
			//$this->opqr_insert_ngram->execute($vn_word_id, $vs_ngram, $vn_seq);
			$va_ngram_buf[] = "({$vn_word_id},'{$vs_ngram}',{$vn_seq})";
			$vn_seq++;
		}
		
		if (sizeof($va_ngram_buf)) {
			$vs_sql = $this->ops_insert_ngram_sql."\n".join(",", $va_ngram_buf);
			$this->opo_db->query($vs_sql);
		}
		
		return WLPlugSearchEngineSqlSearch::$s_word_cache[(string)$ps_word] = (int)$vn_word_id;
	}
	# ------------------------------------------------
	private function _checkWordCacheSize() {
		if ((sizeof(WLPlugSearchEngineSqlSearch::$s_word_cache)) > ($vn_max_size = $this->getOption('maxWordCacheSize'))) {
			WLPlugSearchEngineSqlSearch::$s_word_cache = array_slice(WLPlugSearchEngineSqlSearch::$s_word_cache, 0, $vn_max_size * $this->getOption('cacheCleanFactor'), true);
		}
	}
	# ------------------------------------------------
	public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id, $ps_field_table_num=null, $pn_field_num=null, $pn_field_row_id=null) {
	
		//print "[SqlSearchDebug] removeRowIndexing: $pn_subject_tablenum/$pn_subject_row_id<br>\n"; 
		
		// remove dependent row indexing
		if ($pn_subject_tablenum && $pn_subject_row_id && $ps_field_table_num && $pn_field_row_id && strlen($pn_field_num)) {
			//print "DELETE ROW WITH FIELD NUM $pn_subject_tablenum/$pn_subject_row_id/$ps_field_table_num/$pn_field_num/$pn_field_row_id<br>";
			return $this->opqr_delete_with_field_row_id_and_num->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id, (int)$ps_field_table_num, (string)$pn_field_num, (int)$pn_field_row_id);
		} else {
			if ($pn_subject_tablenum && $pn_subject_row_id && $ps_field_table_num && $pn_field_row_id) {
				//print "DELETE ROW $pn_subject_tablenum/$pn_subject_row_id/$ps_field_table_num/$pn_field_row_id<br>";
				return $this->opqr_delete_with_field_row_id->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id, (int)$ps_field_table_num, (int)$pn_field_row_id);
			} else {
				if ($ps_field_table_num && !is_null($pn_field_num)) {
					//print "DELETE FIELD $pn_subject_tablenum/$pn_subject_row_id/$ps_field_table_num/$pn_field_num<br>";
					return $this->opqr_delete_with_field_num->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id, (int)$ps_field_table_num, (string)$pn_field_num);
				} else {
					if (!$pn_subject_tablenum && !$pn_subject_row_id && $ps_field_table_num && $pn_field_row_id) {
						//print "DELETE DEP $ps_field_table_num/$pn_field_row_id<br>";
						$this->opqr_delete_dependent_sql->execute((int)$ps_field_table_num, (int)$pn_field_row_id);
					} else {
						//print "DELETE ALL $pn_subject_tablenum/$pn_subject_row_id<br>";
						return $this->opqr_delete->execute((int)$pn_subject_tablenum, (int)$pn_subject_row_id);
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
			$this->removeRowIndexing($pn_subject_tablenum, $vn_subject_row_id, $pn_content_tablenum, $ps_content_fieldnum, $pn_content_row_id);
		}
		
		$va_words = $this->_tokenize($ps_content);
		$va_literal_content = caGetOption("literalContent", $pa_options, null);
		if ($va_literal_content && !is_array($va_literal_content)) { $va_literal_content = array($va_literal_content); }
		
		$vn_boost = 1;
		if (isset($pa_options['BOOST'])) {
			$vn_boost = intval($pa_options['BOOST']);
		}
		
		if (!isset($pa_options['PRIVATE'])) { $pa_options['PRIVATE'] = 0; }
		if (in_array('PRIVATE', $pa_options, true)) { $pa_options['PRIVATE'] = 1; }
		$vn_private = $pa_options['PRIVATE'] ? 1 : 0;
		
		
		$va_row_insert_sql = array();
		
		$pn_subject_tablenum = (int)$pn_subject_tablenum;
		$vn_row_id = (int)$vn_row_id;
		$pn_content_tablenum = (int)$pn_content_tablenum;
		$pn_content_row_id = (int)$pn_content_row_id;
		$vn_boost = (int)$vn_boost;
		$vn_access = (int)$vn_access;
		
		
		foreach($pa_subject_row_ids as $vn_row_id) {
			if (!$vn_row_id) { 
				if ($this->debug) { print "[SqlSearchDebug] Cannot index row because row id is missing!<br>\n"; }
				continue; 
			}
			$vn_seq = 0;
			foreach($va_words as $vs_word) {
				if (!($vn_word_id = $this->getWordID($vs_word))) { continue; }
				$va_row_insert_sql[] = "({$pn_subject_tablenum}, {$vn_row_id}, {$pn_content_tablenum}, '{$ps_content_fieldnum}', {$pn_content_row_id}, {$vn_word_id}, {$vn_boost}, {$vn_private})";
				$vn_seq++;
			}
			
			if (is_array($va_literal_content)) {
				foreach($va_literal_content as $vs_literal) {
					if (!($vn_word_id = $this->getWordID($vs_literal))) { continue; }
					$va_row_insert_sql[] = "({$pn_subject_tablenum}, {$vn_row_id}, {$pn_content_tablenum}, '{$ps_content_fieldnum}', {$pn_content_row_id}, {$vn_word_id}, {$vn_boost}, {$vn_private})";
					$vn_seq++;
				}
			}
		}
		
		// do insert
		if (sizeof($va_row_insert_sql)) {
			$vs_sql = $this->ops_insert_word_index_sql."\n".join(",", $va_row_insert_sql);
			$this->opo_db->query($vs_sql);
			if ($this->debug) { print "[SqlSearchDebug] Commit row indexing<br>\n"; }
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
		
			return preg_split('![ ]+!', trim(preg_replace('!['.$this->ops_search_tokenizer_regex.']+!u', ' ', strip_tags($ps_content))));
		} else {
			return preg_split('![ ]+!', trim(preg_replace('!['.$this->ops_indexing_tokenizer_regex.']+!u', ' ', strip_tags($ps_content))));
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
			
			while($qr_res->nextRow()) {
				$va_hits[$qr_res->get('row_id', array('binary' => true))] = true;
			}
		}
		return $va_hits;
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
?>