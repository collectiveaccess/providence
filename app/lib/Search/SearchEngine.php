<?php
/* ----------------------------------------------------------------------
 * app/lib/Search/SearchEngine.php : Base class for searches
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2018 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

# ----------------------------------------------------------------------
# --- Import classes
# ----------------------------------------------------------------------
require_once(__CA_LIB_DIR__."/Search/SearchBase.php");
require_once(__CA_LIB_DIR__."/Zend/Search/Lucene.php");
require_once(__CA_LIB_DIR__."/Plugins/SearchEngine/CachedResult.php");
require_once(__CA_LIB_DIR__."/Search/SearchIndexer.php");
require_once(__CA_LIB_DIR__."/Search/SearchResult.php");
require_once(__CA_LIB_DIR__."/Search/SearchCache.php");
require_once(__CA_LIB_DIR__."/Logging/Searchlog.php");
require_once(__CA_LIB_DIR__."/Utils/Timer.php");
require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');

require_once(__CA_LIB_DIR__."/Search/Common/Parsers/LuceneSyntaxParser.php");
require_once(__CA_LIB_DIR__."/Zend/Search/Lucene/Search/Query.php");
require_once(__CA_LIB_DIR__."/Zend/Search/Lucene/Search/Query/Boolean.php");
require_once(__CA_LIB_DIR__."/Zend/Search/Lucene/Search/Query/Term.php");

require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_acl.php');

# ----------------------------------------------------------------------
class SearchEngine extends SearchBase {

	private $opn_tablenum;
	private $opa_tables;
	// ----
	
	private $opa_options;
	private $opa_result_filters;
	
	/**
	 * @var subject type_id to limit search to (eg. only search ca_objects with type_id = 10)
	 */
	private $opa_search_type_ids = null;
	
	/**
	 * @var subject source_id to limit search to (eg. only search ca_objects with source_id = 10)
	 */
	private $opa_search_source_ids = null;	
	
	# ------------------------------------------------------------------
	public function __construct($opo_db=null, $ps_tablename=null) {
		parent::__construct($opo_db);
		if ($ps_tablename != null) { $this->ops_tablename = $ps_tablename; }
		
		$this->opa_options = array();
		$this->opa_result_filters = array();
		
		$this->opn_tablenum = Datamodel::getTableNum($this->ops_tablename);
		
		$this->opa_tables = array();	
	}
	# ------------------------------------------------------------------
	public function setOption($ps_option, $pm_value) {
		return $this->opo_engine->setOption($ps_option, $pm_value);
	}
	# ------------------------------------------------------------------
	public function getOption($ps_option) {
		return $this->opo_engine->getOption($ps_option);
	}
	# ------------------------------------------------------------------
	public function getAvailableOptions() {
		return $this->opo_engine->getAvailableOptions();
	}
	# ------------------------------------------------------------------
	public function isValidOption($ps_option) {
		return $this->opo_engine->isValidOption($ps_option);
	}
	# ------------------------------------------------------------------
	# Search
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function search($ps_search, $pa_options=null) {
		$vs_append_to_search = (isset($pa_options['appendToSearch'])) ? ' '.$pa_options['appendToSearch'] : '';
		return $this->search($ps_search.$vs_append_to_search, null, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Performs a search by calling the search() method on the underlying search engine plugin
	 * Information about all searches is logged to ca_search_log
	 *
	 * @param string $ps_search The search to perform; engine takes Lucene syntax query
	 * @param SearchResult $po_result  A newly instantiated sub-class of SearchResult to place search results into and return. If this is not set, then a generic SearchResults object will be returned.
	 * @param array $pa_options Optional array of options for the search. Options include:
	 *
	 *		sort = field or attribute to sort on in <table name>.<field or attribute name> format (eg. ca_objects.idno); default is to sort on relevance (aka. sort='_natural')
	 *		sortDirection = direction to sort results by, either 'asc' for ascending order or 'desc' for descending order; default is 'asc'
	 *		no_cache = if true, search is performed regardless of whether results for the search are already cached; default is false
	 *		limit = if set then search results will be limited to the quantity specified. If not set then all results are returned.
	 *		form_id = optional form identifier string to record in log for search
	 *		log_details = optional form description to record in log for search
	 *		search_source = optional source indicator text to record in log for search
	 *		checkAccess = optional array of access values to filter results on
	 *		showDeleted = if set to true, related items that have been deleted are returned. Default is false.
	 *		deletedOnly = if set to true, only deleted items are returned. Default is false.
	 *		limitToModifiedOn = if set returned results will be limited to rows modified within the specified date range. The value should be a date/time expression parse-able by TimeExpressionParser
	 *		sets = if value is a list of set_ids, only rows that are members of those sets will be returned
	 *		user_id = If set item level access control is performed relative to specified user_id, otherwise defaults to logged in user
	 *		dontFilterByACL = if true ACL checking is not performed on results
	 *		appendToSearch = 
	 *		restrictSearchToFields = 
	 *      rootRecordsOnly = Only return records that are the root of whatever hierarchy they are in. [Default is false]
	 *
	 * @return SearchResult Results packages in a SearchResult object, or sub-class of SearchResult if an instance was passed in $po_result
	 * @uses TimeExpressionParser::parse
	 */
	public function doSearch($ps_search, $po_result=null, $pa_options=null) {
		$t = new Timer();
		global $AUTH_CURRENT_USER_ID;
		
		if ($vs_append_to_search = (isset($pa_options['appendToSearch'])) ? ' '.$pa_options['appendToSearch'] : '') {
			$ps_search .= $vs_append_to_search;
		}
		
		$ps_search = preg_replace('![\|]([A-Za-z0-9_,;]+[:]{1})!', "/$1", $ps_search);	// allow | to be used in lieu of / as the relationship type separator, as "/" is problematic to encode in GET requests
		$ps_search = preg_replace('/(?!")\['.caGetBlankLabelText().'\](?!")/i', '"['.caGetBlankLabelText().']"', $ps_search); // the special [BLANK] search term, which returns records that have *no* content in a specific fields, has to be quoted in order to protect the square brackets from the parser.
		$ps_search = preg_replace('/(?!")\['._t('SET').'\](?!")/i', '"['._t('SET').']"', $ps_search); // the special [SET] search term, which returns records that have *any* content in a specific fields, has to be quoted in order to protect the square brackets from the parser.
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		if(($vn_limit = caGetOption('limit', $pa_options, null, array('castTo' => 'int'))) < 0) { $vn_limit = null; }
		$vs_sort = caGetOption('sort', $pa_options, null);
		$vs_sort_direction = strtolower(caGetOption('sortDirection', $pa_options, caGetOption('sort_direction', $pa_options, null)));
		
		//print "QUERY=$ps_search<br>";
		//
		// Note that this is *not* misplaced code that should be in the Lucene plugin!
		//
		// We are using the Lucene syntax as our query syntax regardless the of back-end search engine.
		// The Lucene calls below just parse the query and then rewrite access points as-needed; the result
		// is a Lucene-compliant query ready-to-roll that is passed to the engine plugin. Of course, the Lucene
		// plugin just uses the string as-is... other plugins my choose to parse it however they wish to.
		//
		
		//
		// Process suffixes list... if search conforms to regex then we append a suffix.
		// This is useful, for example, to allow auto-wildcarding of accession numbers: if the search looks like an accession regex-wise we can append a "*"
		//
		$va_suffixes = $this->opo_search_config->getAssoc('search_suffixes');
		if (is_array($va_suffixes) && sizeof($va_suffixes) && (!preg_match('!"!', $ps_search))) {		// don't add suffix wildcards when quoting
			foreach($va_suffixes as $vs_preg => $vs_suffix) {
				if (preg_match("!{$vs_preg}!", $ps_search)) {
					$ps_search = preg_replace("!({$vs_preg})[\*]*!", "$1{$vs_suffix}", $ps_search);
				}
			}
		}
		
		// apply query rewrites
		if (is_array($va_rewrite_regexs = $this->opo_search_config->get('rewrite_regexes'))) {
			if (isset($va_rewrite_regexs[$this->ops_tablename]) && is_array($va_rewrite_regexs[$this->ops_tablename])) { $va_rewrite_regexs = $va_rewrite_regexs[$this->ops_tablename]; }
			foreach($va_rewrite_regexs as $vs_regex_name => $va_rewrite_regex) {
				$ps_search = preg_replace("!".trim($va_rewrite_regex[0])."!", trim($va_rewrite_regex[1]), $ps_search);
			}
		}
		
        if ((is_array($va_idno_regexs = $this->opo_search_config->get('idno_regexes'))) && (!preg_match("!".$this->ops_tablename.".{$vs_idno_fld}!", $ps_search))) {
			if (isset($va_idno_regexs[$this->ops_tablename]) && is_array($va_idno_regexs[$this->ops_tablename])) { $va_idno_regexs = $va_idno_regexs[$this->ops_tablename]; }
			foreach($va_idno_regexs as $vs_idno_regex) {
				if ((preg_match("!{$vs_idno_regex}!", $ps_search, $va_matches)) && ($t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true)) && ($vs_idno_fld = $t_instance->getProperty('ID_NUMBERING_ID_FIELD'))) {
					$ps_search = str_replace($va_matches[0], $this->ops_tablename.".{$vs_idno_fld}:\"".$va_matches[0]."\"", $ps_search);
				}
			}
		}
		
		$vb_no_cache = isset($pa_options['no_cache']) ? $pa_options['no_cache'] : false;
		unset($pa_options['no_cache']);

		$vn_cache_timeout = (int) $this->opo_search_config->get('cache_timeout');
		if($vn_cache_timeout == 0) { $vb_no_cache = true; } // don't try to cache if cache timeout is 0 (0 means disabled)
		
		$t_table = Datamodel::getInstanceByTableName($this->ops_tablename, true);
		$vs_cache_key = md5($ps_search."/".serialize($this->getTypeRestrictionList($pa_options))."/".serialize($this->opa_result_filters));

		$o_cache = new SearchCache();
		$vb_from_cache = false;

		if (!$vb_no_cache && ($o_cache->load($vs_cache_key, $this->opn_tablenum, $pa_options))) {
			$vn_created_on = $o_cache->getParameter('created_on');
			if((time() - $vn_created_on) < $vn_cache_timeout) {
				Debug::msg('SEARCH cache hit for '.$vs_cache_key);
				$va_hits = $o_cache->getResults();
				
				
				if ($vs_sort != '_natural') {
					$va_hits = $this->sortHits($va_hits, $this->ops_tablename, $vs_sort, $vs_sort_direction);
				} elseif (($vs_sort == '_natural') && ($vs_sort_direction == 'desc')) {
					$va_hits = array_reverse($va_hits);
				}
				$o_res = new WLPlugSearchEngineCachedResult($va_hits, $this->opn_tablenum);
				$vb_from_cache = true;
			} else {
				Debug::msg('SEARCH cache expire for '.$vs_cache_key);
				$o_cache->remove();
			}
		}

		if(!$vb_from_cache) {
			Debug::msg('SEARCH cache miss for '.$vs_cache_key);
			$vs_char_set = $this->opo_app_config->get('character_set');
			
			$o_query_parser = new LuceneSyntaxParser();
			$o_query_parser->setEncoding($vs_char_set);
			$o_query_parser->setDefaultOperator(LuceneSyntaxParser::B_AND);
			
			$ps_search = preg_replace('![\']+!', '', $ps_search);	
			$ps_search = preg_replace("/\[((?!SET|BLANK)[A-Za-z0-9\-]+[ ]+(?!to)[^\]]*)\]/", "$1", $ps_search);		// remove search strings (but not range expressions) from square brackets so they may be searched
		
			try {
				$o_parsed_query = $o_query_parser->parse($ps_search, $vs_char_set);
			} catch (Exception $e) {
				// Retry search with all non-alphanumeric characters removed
				try {
					$vs_search_proc = preg_replace("!^(AND|OR)!i", "", $ps_search);
					$vs_search_proc = preg_replace("![^A-Za-z0-9 ]+!", " ", $vs_search_proc);
					$o_parsed_query = $o_query_parser->parse($vs_search_proc, $vs_char_set);
				} catch (Exception $e) {
					$o_parsed_query = $o_query_parser->parse("", $vs_char_set);
				}
			}
			$va_rewrite_results = $this->_rewriteQuery($o_parsed_query);
			$o_rewritten_query = new Zend_Search_Lucene_Search_Query_Boolean($va_rewrite_results['terms'], $va_rewrite_results['signs']);

			$vs_search = $this->_queryToString($o_rewritten_query);
			//print "<div style='background:#FFFFFF; padding: 5px; border: 1px dotted #666666;'><strong>DEBUG: </strong>".$ps_search.'/'.$vs_search."</div>";

			// Filter deleted records out of final result
			if ((isset($pa_options['deletedOnly']) && $pa_options['deletedOnly']) && $t_table->hasField('deleted')) {
				$this->addResultFilter($this->ops_tablename.'.deleted', '=', '1');
			} else {
				if ((!isset($pa_options['showDeleted']) || !$pa_options['showDeleted']) && $t_table->hasField('deleted')) {
					$this->addResultFilter($this->ops_tablename.'.deleted', '=', '0');
				}
			}
			
			if (isset($pa_options['checkAccess']) && (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) && $t_table->hasField('access')) {
				$va_access_values = $pa_options['checkAccess'];
				$this->addResultFilter($this->ops_tablename.'.access', 'IN', join(",",$va_access_values));
			} 
			
			$vb_no_types = false;	
			if (!$pa_options['expandToIncludeParents'] && is_array($va_type_ids = $this->getTypeRestrictionList()) && (sizeof($va_type_ids) > 0) && $t_table->hasField('type_id')) {
				if ($t_table->getFieldInfo('type_id', 'IS_NULL')) {
					$va_type_ids[] = 'NULL';
				}
				$this->addResultFilter($this->ops_tablename.'.type_id', 'IN', join(",",$va_type_ids));
			} elseif (is_array($va_type_ids) && (sizeof($va_type_ids) == 0)) { 
				$vb_no_types = true; 
			}
			
			if (!$vb_no_types) {
				// Filter on source
				if (is_array($va_source_ids = $this->getSourceRestrictionList())) {
					$this->addResultFilter($this->ops_tablename.'.source_id', 'IN', join(",",$va_source_ids));
				}
			
				if (in_array($t_table->getHierarchyType(), array(__CA_HIER_TYPE_SIMPLE_MONO__, __CA_HIER_TYPE_MULTI_MONO__))) {
					$this->addResultFilter($this->ops_tablename.'.parent_id', 'IS NOT', NULL);
				}
				
				if (caGetOption('rootRecordsOnly', $pa_options, false)) {
					$this->addResultFilter($this->ops_tablename.'.parent_id', 'IS', NULL);
				}
			
				if (is_array($va_restrict_to_fields = caGetOption('restrictSearchToFields', $pa_options, null)) && $this->opo_engine->can('restrict_to_fields')) {
					$this->opo_engine->setOption('restrictSearchToFields', $va_restrict_to_fields);
				}
				if (is_array($va_exclude_fields_from_search = caGetOption('excludeFieldsFromSearch', $pa_options, null)) && $this->opo_engine->can('restrict_to_fields')) {
					$this->opo_engine->setOption('excludeFieldsFromSearch', $va_exclude_fields_from_search);
				}
				
				$vb_do_acl = $this->opo_app_config->get('perform_item_level_access_checking') && method_exists($t_table, "supportsACL") && $t_table->supportsACL();

				$o_res =  $this->opo_engine->search($this->opn_tablenum, $vs_search, $this->opa_result_filters, $o_rewritten_query);
			
				// cache the results
				$va_hits = $o_res->getPrimaryKeyValues($vb_do_acl ? null : $vn_limit);
				
										
				if ($pa_options['expandToIncludeParents'] && sizeof($va_hits)) {
					$qr_exp = caMakeSearchResult($this->opn_tablenum, $va_hits);
					if (!is_array($va_type_ids) || !sizeof($va_type_ids)) { $va_type_ids = null; }
					
					$va_results = [];
					$va_parents = [];
					while($qr_exp->nextHit()) {
						if ($vn_parent_id = $qr_exp->get('parent_id')) {
							if (
								((!$va_type_ids) || (in_array($qr_exp->get($this->opn_tablenum.'.parent.type_id'), $va_type_ids)))
							) { 
								$va_parents[$vn_parent_id] = 1;
							}
						}
						if (($va_type_ids) && (!in_array($qr_exp->get('type_id'), $va_type_ids))) { continue; }
						$va_results[] = $qr_exp->getPrimaryKey();
					}
					$va_hits = array_merge($va_hits, array_keys($va_parents));
				}
				$o_res->seek(0);
			} else {
				$va_hits = array();
			}

			if (isset($pa_options['sets']) && $pa_options['sets']) {
				$va_hits = $this->filterHitsBySets($va_hits, $pa_options['sets'], array('search' => $vs_search));
			}
						
			$vn_user_id = (isset($pa_options['user_id']) && (int)$pa_options['user_id']) ?  (int)$pa_options['user_id'] : (int)$AUTH_CURRENT_USER_ID;
			if ((!isset($pa_options['dontFilterByACL']) || !$pa_options['dontFilterByACL']) && $vb_do_acl) {
				$va_hits = $this->filterHitsByACL($va_hits, $this->opn_tablenum, $vn_user_id, __CA_ACL_READONLY_ACCESS__);
				if ($vn_limit > 0) { $va_hits = array_slice($va_hits, 0, $vn_limit); }
			}
			
			if ($vs_sort && ($vs_sort !== '_natural')) {
				$va_hits = $this->sortHits($va_hits, $t_table->tableName(), $vs_sort, $vs_sort_direction);
			} elseif ((($vs_sort == '_natural') || !$vs_sort) && ($vs_sort_direction == 'desc')) {
				$va_hits = array_reverse($va_hits);
			}
			
			$o_res = new WLPlugSearchEngineCachedResult($va_hits, $this->opn_tablenum);
			
			// cache for later use
			if(!$vb_no_cache) {
				$o_cache->save($vs_cache_key, $this->opn_tablenum, $va_hits, array('created_on' => time()), null, $pa_options);
			}

			// log search
			$o_log = new Searchlog();
			
			$vn_search_form_id = isset($pa_options['form_id']) ? $pa_options['form_id'] : null;
			$vs_log_details = isset($pa_options['log_details']) ? $pa_options['log_details'] : '';
			$vs_search_source = isset($pa_options['search_source']) ? $pa_options['search_source'] : '';
				
			$vn_execution_time = $t->getTime(4);
			$o_log->log(array(
				'user_id' => $vn_user_id, 
				'table_num' => $this->opn_tablenum, 
				'search_expression' => $ps_search, 
				'num_hits' => sizeof($va_hits),
				'form_id' => $vn_search_form_id, 
				'ip_addr' => $_SERVER['REMOTE_ADDR'] ?  $_SERVER['REMOTE_ADDR'] : null,
				'details' => $vs_log_details,
				'search_source' => __CA_APP_TYPE__.($vs_search_source ? ":{$vs_search_source}" : ""),
				'execution_time' => $vn_execution_time
			));
		}

		if ($po_result) {
			$po_result->init($o_res, $this->opa_tables, $pa_options);
			return $po_result;
		} else {
			return new SearchResult($o_res, $this->opa_tables);
		}
	}
	# ------------------------------------------------------------------
	/**
	 * @param $pa_hits Array of row_ids to filter. 
	 */
	public function filterHitsBySets($pa_hits, $pa_set_ids, $pa_options=null) {
		if (!sizeof($pa_hits)) { return $pa_hits; }
		if (!sizeof($pa_set_ids)) { return $pa_hits; }
		if (!($t_table = Datamodel::getInstanceByTableNum($this->opn_tablenum, true))) { return $pa_hits; }
		
		$vs_search_tmp_table = $this->loadListIntoTemporaryResultTable($pa_hits, md5(isset($pa_options['search']) ? $pa_options['search'] : rand(0, 1000000)));
			
		$vs_table_name = $t_table->tableName();
		$vs_table_pk = $t_table->primaryKey();
		
		$qr_sort = $this->opo_db->query("
			SELECT ca_set_items.row_id
			FROM ca_set_items
			INNER JOIN {$vs_search_tmp_table} ON {$vs_search_tmp_table}.row_id = ca_set_items.row_id 
			WHERE
				ca_set_items.table_num = ? AND
				ca_set_items.set_id IN (?)
		", (int)$this->opn_tablenum, $pa_set_ids);
		
		$this->cleanupTemporaryResultTable();
		return $qr_sort->getAllFieldValues('row_id');
	}

	/**
	 *
	 */
	public function getRandomResult($pn_num_hits=10, $po_result=null) {
		if (!($t_table = Datamodel::getInstanceByTableNum($this->opn_tablenum, true))) { return null; }
		$vs_table_pk = $t_table->primaryKey();
		$vs_table_name = $this->ops_tablename;
		
		$qr_res = $this->opo_db->query("
			SELECT {$vs_table_name}.{$vs_table_pk}
			FROM {$vs_table_name}
			WHERE {$vs_table_name}.{$vs_table_pk} >= 
				(SELECT FLOOR( MAX({$vs_table_name}.{$vs_table_pk}) * RAND()) FROM {$vs_table_name}) 
			LIMIT {$pn_num_hits}
		");
		
		$va_hits = $qr_res->getAllFieldValues($vs_table_pk);
		
		$o_res = new WLPlugSearchEngineCachedResult($va_hits, $this->opn_tablenum);
		
		if ($po_result) {
			$po_result->init($o_res, array());
			return $po_result;
		} else {
			return new SearchResult($o_res, array());
		}
	}
	# ------------------------------------------------------------------
	private function _rewriteQuery($po_query) {
		$va_terms = array();
		$va_signs = array();
		switch(get_class($po_query)) {
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				$va_items = $po_query->getSubqueries();
				break;
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				$va_items = $po_query->getTerms();
				break;
			default:
				$va_items = array();
				break;
		}
		
		if (method_exists($po_query, 'getSigns')) {
			$va_old_signs = $po_query->getSigns();
		} else {
			$va_old_signs = array();
		}

		$vn_i = 0;
		foreach($va_items as $o_term) {
			switch(get_class($o_term)) {
				case 'Zend_Search_Lucene_Search_Query_Preprocessing_Term':
					$va_terms[] = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($o_term->__toString()));
					$va_signs[] = array_key_exists($va_old_signs, $vn_i) ? $va_old_signs[$vn_i] : true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Term':
					$va_rewritten_terms = $this->_rewriteTerm($o_term, $va_old_signs[$vn_i]);
					if (sizeof($va_rewritten_terms['terms']) == 1) {
						$va_terms[] = new Zend_Search_Lucene_Search_Query_Term(array_shift($va_rewritten_terms['terms']));
						$va_signs[] = array_shift($va_rewritten_terms['signs']);
					} else { 
						for($vn_j = 0; $vn_j < sizeof($va_rewritten_terms['terms']); $vn_j++) {
							$va_terms[] = new Zend_Search_Lucene_Search_Query_MultiTerm(array($va_rewritten_terms['terms'][$vn_j]), array($va_rewritten_terms['signs'][$vn_j]));
							$va_signs[] = $va_rewritten_terms['signs'][$vn_j] ? true : is_null($va_rewritten_terms['signs'][$vn_j]) ? null : false;
						}
					}
					break;
				case 'Zend_Search_Lucene_Index_Term':
					$va_rewritten_terms = $this->_rewriteTerm(new Zend_Search_Lucene_Search_Query_Term($o_term), $va_old_signs[$vn_i]);
					if (sizeof($va_rewritten_terms['terms']) == 1) {
						$o_mt = new Zend_Search_Lucene_Search_Query_Term($va_rewritten_terms['terms'][0]);
					} else {
						$o_mt = new Zend_Search_Lucene_Search_Query_MultiTerm($va_rewritten_terms['terms'], $va_rewritten_terms['signs']);
					}
					$va_terms[] = $o_mt;
					$va_signs[] = sizeof($va_rewritten_terms['signs']) ? array_shift($va_rewritten_terms['signs']): true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Wildcard':
					$va_rewritten_terms = $this->_rewriteTerm(new Zend_Search_Lucene_Search_Query_Term($o_term->getPattern()), $va_old_signs[$vn_i]);
					$o_mt = new Zend_Search_Lucene_Search_Query_MultiTerm($va_rewritten_terms['terms'], $va_rewritten_terms['signs']);
					$va_terms[] = $o_mt;
					$va_signs[] = sizeof($va_rewritten_terms['signs']) ? array_shift($va_rewritten_terms['signs']): true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Phrase':
					$va_phrase_items = $o_term->getTerms();
					$va_rewritten_phrase = $this->_rewritePhrase($o_term, $va_old_signs[$vn_i]);
					
					foreach($va_rewritten_phrase['terms'] as $o_term) {
						$va_terms[] = $o_term;
					}
					foreach($va_rewritten_phrase['signs'] as $vb_sign) {
						$va_signs[] = $vb_sign;
					}
					break;
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
					$va_tmp = $this->_rewriteQuery($o_term);
					$va_terms[] = new Zend_Search_Lucene_Search_Query_MultiTerm($va_tmp['terms'], $va_tmp['signs']);
					$va_signs[] = $va_old_signs[$vn_i];
					break;
				case 'Zend_Search_Lucene_Search_Query_Boolean':
					$va_tmp = $this->_rewriteQuery($o_term);
					// don't wrap 1-term query in unnecessary extra boolean subquery. apparently the engines can't handle the extra parentheses
					if(sizeof($va_tmp['terms']) == 1) {
						$va_terms[] = array_shift($va_tmp['terms']);
					} else {
						$va_terms[] = new Zend_Search_Lucene_Search_Query_Boolean($va_tmp['terms'], $va_tmp['signs']);
					}

					$va_signs[] = $va_old_signs[$vn_i];
					break;
				case 'Zend_Search_Lucene_Search_Query_Range':
					$va_signs[] = $va_old_signs[$vn_i] ;
					$va_terms = array_merge($va_terms, $this->_rewriteRange($o_term));
					break;
				default:
					// NOOP (TODO: do *something*)
					break;
			}	
			
			$vn_i++;
		}
		return array(
			'terms' => $va_terms,
			'signs' => $va_signs
		);
	}
	# ------------------------------------------------------------------
	/**
	 * @param $po_term - term to rewrite; must be Zend_Search_Lucene_Search_Query_Term object
	 * @param $pb_sign - Zend boolean flag (true=and, null=or, false=not)
	 * @return array - rewritten terms are *** Zend_Search_Lucene_Index_Term *** objects
	 */
	private function _rewriteTerm($po_term, $pb_sign) {
		$vs_fld = $po_term->getTerm()->field;
		if (sizeof($va_access_points = $this->getAccessPoints($this->opn_tablenum))) {
			// if field is access point then do rewrite
			$va_fld_tmp = explode("/", mb_strtolower($vs_fld));
			$vs_fld_lc = $va_fld_tmp[0];
			$vs_rel_types = isset($va_fld_tmp[1]) ? $va_fld_tmp[1] : null;
			
			if (
				isset($va_access_points[$vs_fld_lc]) 
				&&
				($va_ap_info = $va_access_points[$vs_fld_lc])
			) {
				$va_fields = isset($va_ap_info['fields']) ? $va_ap_info['fields'] : null;
				if (!in_array($vs_bool = strtoupper($va_ap_info['boolean']), array('AND', 'OR'))) {
					$vs_bool = 'OR';
				}
				
				$va_terms = array();
				$vs_term = (string)$po_term->getTerm()->text;
				foreach($va_fields as $vs_field) {
					$va_tmp = explode(".", $vs_field);
					
					// Rewrite FT_BIT fields to accept yes/no values
					if (Datamodel::getFieldInfo($va_tmp[0], $va_tmp[1], 'FIELD_TYPE') == FT_BIT) {
						switch(mb_strtolower($vs_term)) {
							case 'yes':
							case _t('yes'):
								$vs_term = 1;
								break;
							case 'no':
							case _t('no'):
								$vs_term = 0;
								break;
						}
					} 
					
					if(isset($va_ap_info['options']) && ($va_ap_info['options']['DONT_STEM'] || in_array('DONT_STEM', $va_ap_info['options']))) {
						$vs_term .= '|';
					}
					$va_terms['terms'][] = new Zend_Search_Lucene_Index_Term($vs_term, $vs_field.($vs_rel_types ? "/{$vs_rel_types}" : ''));
					$va_terms['signs'][] = ($vs_bool == 'AND') ? true : null;
					$va_terms['options'][] = is_array($va_ap_info['options']) ? $va_ap_info['options'] : array();
				}
				
				if (is_array($va_additional_criteria = $va_ap_info['additional_criteria'])) {
					foreach($va_additional_criteria as $vs_criterion) {
						$va_terms['terms'][] = new Zend_Search_Lucene_Index_Term($vs_criterion);
						$va_terms['signs'][] = $vs_bool;
						$va_terms['options'][] = is_array($va_ap_info['options']) ? $va_ap_info['options'] : array();
					}
				}
				
				if (sizeof($va_terms['signs']) > 0) { array_pop($va_terms['signs']); }
				return $va_terms;
			}
		}
		
		// is it an idno?
		if (is_array($va_idno_regexs = $this->opo_search_config->get('idno_regexes'))) {
			if (isset($va_idno_regexs[$this->ops_tablename]) && is_array($va_idno_regexs[$this->ops_tablename])) { $va_idno_regexs = $va_idno_regexs[$this->ops_tablename]; }
			foreach($va_idno_regexs as $vs_idno_regex) {
				if ((preg_match("!{$vs_idno_regex}!", (string)$po_term->getTerm()->text, $va_matches)) && ($t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true)) && ($vs_idno_fld = $t_instance->getProperty('ID_NUMBERING_ID_FIELD'))) {
					$vs_table_name = $t_instance->tableName();
					return array(
						'terms' => array(new Zend_Search_Lucene_Index_Term((string)((sizeof($va_matches) > 1) ? $va_matches[1] : $va_matches[0]), "{$vs_table_name}.{$vs_idno_fld}")),
						'signs' => array($pb_sign),
						'options' => array()
					);
				}
			}
		}
		
		// is it a label? Rewrite the field for that.
		$va_tmp = explode('/', $vs_fld);
		$va_tmp2 = explode('.', $va_tmp[0]);
		if (in_array($va_tmp2[1], array('preferred_labels', 'nonpreferred_labels'))) {
			if ($t_instance = Datamodel::getInstanceByTableName($va_tmp2[0], true)) {
				if (method_exists($t_instance, "getLabelTableName")) {
					return array(
						'terms' => array(new Zend_Search_Lucene_Index_Term($po_term->getTerm()->text, $t_instance->getLabelTableName().'.'.((isset($va_tmp2[2]) && $va_tmp2[2]) ? $va_tmp2[2] : $t_instance->getLabelDisplayField()).($va_tmp[1] ? '/'.$va_tmp[1] : ''))),
						'signs' => array($pb_sign),
						'options' => array()
					);
				}
			}
		}
		
		return array('terms' => array($po_term->getTerm()), 'signs' => array($pb_sign), 'options' => array());
	}
	# ------------------------------------------------------------------
	/**
	 * @param $po_term - phrase expression to rewrite; must be Zend_Search_Lucene_Search_Query_Phrase object
	 * @param $pb_sign - Zend boolean flag (true=and, null=or, false=not)
	 * @return array - rewritten phrases are *** Zend_Search_Lucene_Search_Query_Phrase *** objects
	 */
	private function _rewritePhrase($po_term, $pb_sign) {		
		$va_index_term_strings = array();
		$va_phrase_terms = $po_term->getTerms();
		foreach($va_phrase_terms as $o_phrase_term) {
			$va_index_term_strings[] = $o_phrase_term->text; 
		}
		
		$vs_fld = $va_phrase_terms[0]->field;
		
		if (sizeof($va_access_points = $this->getAccessPoints($this->opn_tablenum))) {
			// if field is access point then do rewrite
			if (
				isset($va_access_points[$vs_fld]) 
				&&
				($va_ap_info = $va_access_points[$vs_fld])
			) {
				$va_fields = isset($va_ap_info['fields']) ? $va_ap_info['fields'] : null;
				if (!in_array($vs_bool = strtoupper($va_ap_info['boolean']), array('AND', 'OR'))) {
					$vs_bool = 'OR';
				}
				
				foreach($va_fields as $vs_field) {
					$va_terms['terms'][] = new Zend_Search_Lucene_Search_Query_Phrase($va_index_term_strings, null, $vs_field);
					$va_terms['signs'][] = ($vs_bool == 'AND') ? true : null;
					$va_terms['options'][] = is_array($va_ap_info['options']) ? $va_ap_info['options'] : array();
				}
				
				if (is_array($va_additional_criteria = $va_ap_info['additional_criteria'])) {
					foreach($va_additional_criteria as $vs_criterion) {
						$va_terms['terms'][] = new Zend_Search_Lucene_Index_Term($vs_criterion);
						$va_terms['signs'][] = $vs_bool;
						$va_terms['options'][] = is_array($va_ap_info['options']) ? $va_ap_info['options'] : array();
					}
				}
				
				return $va_terms;
			}
		}
		
		// is it a labels? Rewrite the field for that.
		$va_tmp = explode('/', $vs_fld);
		$va_tmp2 = explode('.', $va_tmp[0]);
		if (in_array($va_tmp2[1], array('preferred_labels', 'nonpreferred_labels'))) {
			if ($t_instance = Datamodel::getInstanceByTableName($va_tmp2[0], true)) {
				if (method_exists($t_instance, "getLabelTableName")) {
					return array(
						'terms' => array(new Zend_Search_Lucene_Search_Query_Phrase($va_index_term_strings, null, $t_instance->getLabelTableName().'.'.$t_instance->getLabelDisplayField().($va_tmp[1] ? '/'.$va_tmp[1] : ''))),
						'signs' => array($pb_sign),
						'options' => array()
					);
				}
			}
		}
		
		return array('terms' => array($po_term), 'signs' => array($pb_sign), 'options' => array());
	}
	# ------------------------------------------------------------------
	/**
	 * @param $po_range - range expression to rewrite; must be Zend_Search_Lucene_Search_Query_Range object
	 * @return array - rewritten search terms 
	 */
	private function _rewriteRange($po_range) {
		if (sizeof($va_access_points = $this->getAccessPoints($this->opn_tablenum))) {
			// if field is access point then do rewrite
			if ($va_ap_info = $va_access_points[$po_range->getField()]) {
				$va_fields = $va_ap_info['fields'];
				if (!in_array($vs_bool = strtoupper($va_ap_info['boolean']), array('AND', 'OR'))) {
					$vs_bool = 'OR';
				}
				$va_tmp = array();
				foreach($va_fields as $vs_field) {
					$po_range->getLowerTerm()->field = $vs_field;
					$po_range->getUpperTerm()->field = $vs_field;
					$o_range = new Zend_Search_Lucene_Search_Query_Range($po_range->getLowerTerm(), $po_range->getUpperTerm(), (($vs_bool == 'OR') ? null : true));
					$o_range->field = $vs_field;
					$va_terms[] = $o_range;
					
				}
				
				if (is_array($va_additional_criteria = $va_ap_info['additional_criteria'])) {
					foreach($va_additional_criteria as $vs_criterion) {
						$va_terms[] = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($vs_criterion));
					}
				}
				return $va_terms;
			}
		}
		
		return array($po_range);
	}
	# ------------------------------------------------------------------
	private function _queryToString($po_parsed_query) {
		switch(get_class($po_parsed_query)) {
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				$va_items = $po_parsed_query->getSubqueries();
				$va_signs = $po_parsed_query->getSigns();
				break;
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				$va_items = $po_parsed_query->getTerms();
				$va_signs = $po_parsed_query->getSigns();
				break;
			case 'Zend_Search_Lucene_Search_Query_Phrase':
				//$va_items = $po_parsed_query->getTerms();
				$va_items = $po_parsed_query;
				$va_signs = null;
				break;
			case 'Zend_Search_Lucene_Search_Query_Range':
				$va_items = $po_parsed_query;
				$va_signs = null;
				break;
			default:
				$va_items = array();
				$va_signs = null;
				break;
		}
		
		$vs_query = '';
		foreach ($va_items as $id => $subquery) {
			if ($id != 0) {
				$vs_query .= ' ';
			}
		
			if (($va_signs === null || $va_signs[$id] === true) && ($id)) {
				$vs_query .= ' AND ';
			} else if (($va_signs[$id] === false) && $id) {
				$vs_query .= ' NOT ';
			} else {
				if ($id) { $vs_query .= ' OR '; }
			}
			switch(get_class($subquery)) {
				case 'Zend_Search_Lucene_Search_Query_Phrase':
					$vs_query .= '(' . $subquery->__toString(). ')';
					break;
				case 'Zend_Search_Lucene_Index_Term':
					$subquery = new Zend_Search_Lucene_Search_Query_Term($subquery);
					// intentional fallthrough to next case here
				case 'Zend_Search_Lucene_Search_Query_Term':
					$vs_query .= '(' . $subquery->__toString() . ')';
					break;	
				case 'Zend_Search_Lucene_Search_Query_Range':
					$vs_query .= '(' . $subquery->__toString() . ')';
					break;
				default:
					$vs_query .= '(' . $this->_queryToString($subquery) . ')';
					break;
			}
			
		
			if ((method_exists($subquery, "getBoost")) && ($subquery->getBoost() != 1)) {
				$vs_query .= '^' . round($subquery->getBoost(), 4);
			}
		}
		
		return $vs_query;
    }
	# ------------------------------------------------------------------
	# Search parameter accessors
	# ------------------------------------------------------------------
	public function addTable($ps_tablename, $pa_fieldlist, $pa_join_tables=array(), $pa_criteria=array()) {
		$this->opa_tables[$ps_tablename] = array(
			'fieldList' => $pa_fieldlist,
			'joinTables' => $pa_join_tables,
			'criteria' => $pa_criteria
		);
	}
	# ------------------------------------------------------------------
	public function removeTable($ps_tablename) {
		unset($this->opa_tables[$ps_tablename]);
	}
	# ------------------------------------------------------------------
	public function getTables() {
		return $this->opa_tables;
	}
	# ------------------------------------------------------------------
	/**
	 * Result filters are criteria through which the results of a search are passed before being
	 * returned to the caller. They are often used to restrict the domain over which searches operate
	 * (for example, ensuring that a search only returns rows with a certain "status" field value)
	 *
	 * $ps_field is the name of an instrinsic field 
	 * $ps_operator is one of the following: =, <, >, <=, >=, - ("between"), in
	 * $pm_value is the value to apply; this is usually text or a number; for the "in" operator this is a comma-separated list of string or numeric values
	 *			
	 *
	 */
	public function addResultFilter($ps_field, $ps_operator, $pm_value) {
		$ps_operator = strtolower($ps_operator);
		if (!in_array($ps_operator, array('=', '<', '>', '<=', '>=', '-', 'in', 'not in', '<>', 'is', 'is not'))) { return false; }
		
		$this->opa_result_filters[] = array(
			'field' => $ps_field,
			'operator' => $ps_operator,
			'value' => $pm_value
		);
		
		return true;
	}
	# ------------------------------------------------------------------
	public function clearResultFilters() {
		$this->opa_result_filters = array();
	}
	# ------------------------------------------------------------------
	public function getResultFilters() {
		return $this->opa_result_filters;
	}
	# ------------------------------------------------------
	# Type filtering
	# ------------------------------------------------------
	/**
	 * When type restrictions are specified, the search will only consider items of the given types. 
	 * If you specify a type that has hierarchical children then the children will automatically be included
	 * in the restriction. You may pass numeric type_id and alphanumeric type codes interchangeably.
	 *
	 * @param array $pa_type_codes_or_ids List of type_id or code values to filter search by. When set, the search will only consider items of the specified types. Using a hierarchical parent type will automatically include its children in the restriction. 
	 * @param array $pa_options Options include
	 *		includeSubtypes = include any child types in the restriction. Default is true.
	 * @return boolean True on success, false on failure
	 */
	public function setTypeRestrictions($pa_type_codes_or_ids, $pa_options=null) {
		$t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true);
		
		if (!$pa_type_codes_or_ids) { return false; }
		if (is_array($pa_type_codes_or_ids) && !sizeof($pa_type_codes_or_ids)) { return false; }
		if (!is_array($pa_type_codes_or_ids)) { $pa_type_codes_or_ids = array($pa_type_codes_or_ids); }
		
		$t_list = new ca_lists();
		if (!method_exists($t_instance, 'getTypeListCode')) { return false; }
		if (!($vs_list_name = $t_instance->getTypeListCode())) { return false; }
		$va_type_list = $t_instance->getTypeList();
		
		$this->opa_search_type_ids = array();
		foreach($pa_type_codes_or_ids as $vs_code_or_id) {
			if (!strlen($vs_code_or_id)) { continue; }
			if (!is_numeric($vs_code_or_id)) {
				$vn_type_id = $t_list->getItemIDFromList($vs_list_name, $vs_code_or_id);
			} else {
				$vn_type_id = (int)$vs_code_or_id;
			}
			
			if (!$vn_type_id) { return false; }
			
			if (isset($va_type_list[$vn_type_id]) && $va_type_list[$vn_type_id]) {	// is valid type for this subject
				if (caGetOption('includeSubtypes', $pa_options, true)) {
					// See if there are any child types
					$t_item = new ca_list_items($vn_type_id);
					$va_ids = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
					$va_ids[] = $vn_type_id;
					$this->opa_search_type_ids = array_merge($this->opa_search_type_ids, $va_ids);
				} else {
					$this->opa_search_type_ids[] = $vn_type_id;
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of type_id values to restrict search to. Return values are always numeric types, 
	 * never codes, and will include all type_ids to filter on, including children of hierarchical types.
	 *
	 * @return array List of type_id values to restrict search to.
	 */
	public function getTypeRestrictionList($pa_options=null) {
		if (function_exists("caGetTypeRestrictionsForUser")) {
			$va_pervasive_types = caGetTypeRestrictionsForUser($this->ops_tablename, $pa_options);	// restrictions set in app.conf or by associated user role
			if (!is_array($va_pervasive_types) || !sizeof($va_pervasive_types)) { return $this->opa_search_type_ids; }
				
			if (is_array($this->opa_search_type_ids) && sizeof($this->opa_search_type_ids)) {
				$va_filtered_types = array();
				foreach($this->opa_search_type_ids as $vn_id) {
					if (in_array($vn_id, $va_pervasive_types)) {
						$va_filtered_types[] = $vn_id;
					}
				}
				return $va_filtered_types;
			} else {
				return $va_pervasive_types;
			}
		}
		return $this->opa_search_type_ids;
	}
	# ------------------------------------------------------
	/**
	 * Removes any specified type restrictions on the search
	 *
	 * @return boolean Always returns true
	 */
	public function clearTypeRestrictionList() {
		$this->opa_search_type_ids = null;
		return true;
	}
	# ------------------------------------------------------
	# Source filtering
	# ------------------------------------------------------
	/**
	 * When source restrictions are specified, the search will only consider items of the given sources. 
	 * If you specify a source that has hierarchical children then the children will automatically be included
	 * in the restriction. You may pass numeric source_id and alphanumeric source codes interchangeably.
	 *
	 * @param array $pa_source_codes_or_ids List of source_id or code values to filter search by. When set, the search will only consider items of the specified sources. Using a hierarchical parent source will automatically include its children in the restriction. 
	 * @param array $pa_options Options include
	 *		includeSubsources = include any child sources in the restriction. Default is true.
	 * @return boolean True on success, false on failure
	 */
	public function setSourceRestrictions($pa_source_codes_or_ids, $pa_options=null) {
		$t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true);
		
		if (!$pa_source_codes_or_ids) { return false; }
		if (is_array($pa_source_codes_or_ids) && !sizeof($pa_source_codes_or_ids)) { return false; }
		if (!is_array($pa_source_codes_or_ids)) { $pa_source_codes_or_ids = array($pa_source_codes_or_ids); }
		
		$t_list = new ca_lists();
		if (!method_exists($t_instance, 'getSourceListCode')) { return false; }
		if (!($vs_list_name = $t_instance->getSourceListCode())) { return false; }
		$va_source_list = $t_instance->getSourceList();
		
		$this->opa_search_source_ids = array();
		foreach($pa_source_codes_or_ids as $vs_code_or_id) {
			if (!strlen($vs_code_or_id)) { continue; }
			if (!is_numeric($vs_code_or_id)) {
				$vn_source_id = $t_list->getItemIDFromList($vs_list_name, $vs_code_or_id);
			} else {
				$vn_source_id = (int)$vs_code_or_id;
			}
			
			if (!$vn_source_id) { return false; }
			
			if (isset($va_source_list[$vn_source_id]) && $va_source_list[$vn_source_id]) {	// is valid source for this subject
				if (caGetOption('includeSubsources', $pa_options, true)) {
					// See if there are any child sources
					$t_item = new ca_list_items($vn_source_id);
					$va_ids = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
					$va_ids[] = $vn_source_id;
					$this->opa_search_source_ids = array_merge($this->opa_search_source_ids, $va_ids);
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of source_id values to restrict search to. Return values are always numeric sources, 
	 * never codes, and will include all source_ids to filter on, including children of hierarchical sources.
	 *
	 * @return array List of source_id values to restrict search to.
	 */
	public function getSourceRestrictionList() {
		if (function_exists("caGetSourceRestrictionsForUser")) {
			$va_pervasive_sources = caGetSourceRestrictionsForUser($this->ops_tablename);	// restrictions set in app.conf or by associated user role
			if (!is_array($va_pervasive_sources)) { return $this->opa_search_source_ids; }
				
			if (is_array($this->opa_search_source_ids) && sizeof($this->opa_search_source_ids)) {
				$va_filtered_sources = array();
				foreach($this->opa_search_source_ids as $vn_id) {
					if (in_array($vn_id, $va_pervasive_sources)) {
						$va_filtered_sources[] = $vn_id;
					}
				}
				return $va_filtered_sources;
			} else {
				return $va_pervasive_sources;
			}
		}
		return $this->opa_search_source_ids;
	}
	# ------------------------------------------------------
	/**
	 * Removes any specified source restrictions on the search
	 *
	 * @return boolean Always returns true
	 */
	public function clearSourceRestrictionList() {
		$this->opa_search_source_ids = null;
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Ask the search engine plugin if everything is configured properly.
	 *
	 * @return ASearchConfigurationSettings
	 */
	public static function checkPluginConfiguration() {
		$o_config = Configuration::load();
		$ps_plugin_name = $o_config->get('search_engine_plugin');
		if (!file_exists(__CA_LIB_DIR__.'/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php')) {
			return null;
		}
		require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php');
		$ps_classname = $ps_plugin_name."ConfigurationSettings";

		return new $ps_classname;
	}
	# ------------------------------------------------------------------
	/**
	 * Ask the search engine plugin for its display name
	 *
	 * @return String
	 */
	public static function getPluginEngineName() {
		$o_config = Configuration::load();
		$ps_plugin_name = $o_config->get('search_engine_plugin');
		if (!file_exists(__CA_LIB_DIR__.'/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php')) {
			return null;
		}
		require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php');
		$ps_classname = $ps_plugin_name."ConfigurationSettings";
		$o_instance = new $ps_classname;
		return $o_instance->getEngineName();
	}
	# ------------------------------------------------------------------
	#
	# ------------------------------------------------------------------
	/**
	 * Performs the quickest possible search on the index for the table specified by the superclass
	 * using the text in $ps_search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back quickly. 
	 * quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is critical
	 * and the ability to control search parameters is not required.
	 *
	 * Quick searches are NOT logged to ca_search_log
	 *
	 * @param $ps_search - The text to search on
	 * @param $ps_tablename - name of table to search on
	 * @param $pn_table_num - number of table to search on (same table as $ps_tablename)
	 * @param $pa_options - an optional associative array specifying search options. Supported options are: 'limit' (the maximum number of results to return), 'checkAccess' (only return results that have an access value = to the specified value)
	 * 
	 * @return Array - an array of results is returned keys first by primary key id, then by locale_id. The array values are associative arrays with two keys: type_id (the type_id of the result; this points to a ca_list_items row defining the type of the result item) and label (the row item's label display field). You can push the returned results array from caExtractValuesByUserLocale() to get an array keyed by primary key id and returning for each id a displayable text label + the type of the found result item.
	 * 
	 */
	static function quickSearch($ps_search, $ps_tablename, $pn_tablenum, $pa_options=null) {
		$o_config = Configuration::load();
		
		if (!($ps_plugin_name = $o_config->get('search_engine_plugin'))) { return null; }
		if (!@require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/'.$ps_plugin_name.'.php')) { return null; }
		$ps_classname = 'WLPlugSearchEngine'.$ps_plugin_name;
		if (!($o_engine =  new $ps_classname)) { return null; }
	
		$va_ids = $o_engine->quickSearch($pn_tablenum, $ps_search, $pa_options);
		
		if (!is_array($va_ids) || !sizeof($va_ids)) { return array(); }
		$t_instance = Datamodel::getInstanceByTableNum($pn_tablenum, true);
		
		$t_label_instance = 		$t_instance->getLabelTableInstance();
		$vs_label_table_name = 		$t_instance->getLabelTableName();
		$vs_label_display_field = 	$t_instance->getLabelDisplayField();
		$vs_pk = 					$t_instance->primaryKey();
		$vs_is_preferred_sql = '';
		if ($t_label_instance->hasField('is_preferred')) {
			$vs_is_preferred_sql = ' AND (l.is_preferred = 1)';
		}
		
		$vs_check_access_sql = '';
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_instance->hasField('access')) {
			$vs_check_access_sql = ' AND (n.access IN ('.join(", ", $pa_options['checkAccess']).'))';
		}
		
		$vs_limit_sql = '';
		if (isset($pa_options['limit']) && !is_null($pa_options['limit']) && ($pa_options['limit'] > 0)) {
			$vs_limit_sql = ' LIMIT '.intval($pa_options['limit']);
		}
		
		$vs_type_restriction_sql = '';
		$va_types = caGetTypeRestrictionsForUser($ps_tablename);
		if (is_array($va_types) && sizeof($va_types)) {
			$vs_type_restriction_sql = ' AND n.type_id IN ('.join(',', $va_types).')';
		}
		
		$vs_delete_sql = '';
		if ($t_instance->hasField('deleted')) {
			$vs_delete_sql = ' AND (deleted = 0)';
		}

		$o_db = new Db();
		$qr_res = $o_db->query("
			SELECT n.{$vs_pk}, l.{$vs_label_display_field}, l.locale_id, n.type_id
			FROM {$vs_label_table_name} l
			INNER JOIN ".$ps_tablename." AS n ON n.{$vs_pk} = l.{$vs_pk}
			WHERE
				l.".$vs_pk." IN (".join(',', $va_ids).")
				{$vs_is_preferred_sql}
				{$vs_check_access_sql}
				{$vs_type_restriction_sql}
				{$vs_delete_sql}
			{$vs_limit_sql}
		");
		$va_hits = array();
		while($qr_res->nextRow()) {
			$va_hits[$qr_res->get($vs_pk)][$qr_res->get('locale_id')] = array(
				'type_id' => $qr_res->get('type_id'),
				'label' => $qr_res->get($vs_label_display_field)
			);
		}
		return $va_hits;
	}
	# ------------------------------------------------------------------
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
		if ($this->opo_engine && method_exists($this->opo_engine, "suggest")) {
			$pa_options['table'] = $this->opn_tablenum;
			return  $this->opo_engine->suggest($ps_text, $pa_options);
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns search expression as string for display with field qualifiers translated into display labels
	 * 
	 * @param string $ps_search
	 * @param mixed $ps_table
	 * @return string
	 */
	static public function getSearchExpressionForDisplay($ps_search, $ps_table) {
		$o_config = Configuration::load();
		
		if ($t_instance = Datamodel::getInstanceByTableName($ps_table, true)) {
			$vs_char_set = $o_config->get('character_set');
			
			$o_query_parser = new LuceneSyntaxParser();
			$o_query_parser->setEncoding($vs_char_set);
			$o_query_parser->setDefaultOperator(LuceneSyntaxParser::B_AND);
	
			$ps_search = preg_replace('![\']+!', '', $ps_search);
			try {
				$o_parsed_query = $o_query_parser->parse($ps_search, $vs_char_set);
			} catch (Exception $e) {
				// Retry search with all non-alphanumeric characters removed
				try {
					$o_parsed_query = $o_query_parser->parse(preg_replace("![^A-Za-z0-9 ]+!", " ", $ps_search), $vs_char_set);
				} catch (Exception $e) {
					$o_parsed_query = $o_query_parser->parse("", $vs_char_set);
				}
			}
			
			$va_field_list = SearchEngine::_getFieldList($o_parsed_query);
			
			foreach($va_field_list as $vs_field) {
				$va_tmp = explode('/', $vs_field);
				
				if (sizeof($va_tmp) > 1) {
					$vs_rel_type = $va_tmp[1];
					$vs_field_proc = $va_tmp[0];
				} else {
					$vs_rel_type = null;
					$vs_field_proc = $vs_field;
				}
				if ($vs_label = $t_instance->getDisplayLabel($vs_field_proc)) {
					$ps_search = str_replace($vs_field, $vs_rel_type ? _t("%1 [as %2]", $vs_label, $vs_rel_type) : $vs_label, $ps_search);
				}
			}
		}
		return $ps_search;	
	}
	# ------------------------------------------------------------------
	/**
	 * Returns all field qualifiers in parsed queryString
	 *
	 * @param LuceneSyntaxParser $po_query
	 * @return array 
	 */
	static private function _getFieldList($po_query) {
		$va_fields = array();
		
		switch(get_class($po_query)) {
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				$va_items = $po_query->getSubqueries();
				break;
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				$va_items = $po_query->getTerms();
				break;
			default:
				$va_items = array();
				break;
		}
		
		$vn_i = 0;
		foreach($va_items as $o_term) {
			switch(get_class($o_term)) {
				case 'Zend_Search_Lucene_Search_Query_Preprocessing_Term':
					$va_fields[] = $o_term->getTerm()->field;
					break;
				case 'Zend_Search_Lucene_Search_Query_Term':
					$va_fields[] = $o_term->getTerm()->field;
					break;
				case 'Zend_Search_Lucene_Index_Term':
					$va_fields[] = $o_term->getTerm()->field;
					break;
				//case 'Zend_Search_Lucene_Search_Query_Wildcard':
					//$va_fields = $o_term->getTerm()->field;
				//	break;
				case 'Zend_Search_Lucene_Search_Query_Phrase':
					$va_phrase_items = $o_term->getTerms();
					foreach($va_phrase_items as $o_term) {
						$va_fields[] = $o_term->field;
					}
					break;
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
					$va_fields = array_merge($va_fields, SearchEngine::_getFieldList($o_term));
					break;
				case 'Zend_Search_Lucene_Search_Query_Boolean':
					$va_fields = array_merge($va_fields, SearchEngine::_getFieldList($o_term));
					break;
				case 'Zend_Search_Lucene_Search_Query_Range':
					$va_fields[] = $o_term->getTerm()->field;
					break;
				default:
					// NOOP (TODO: do *something*)
					break;
			}	
			
			$vn_i++;
		}
		
		return $va_fields;
	}
	# ------------------------------------------------------------------
}
