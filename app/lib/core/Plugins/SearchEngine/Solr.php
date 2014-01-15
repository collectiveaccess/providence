<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/Solr.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/WLPlug.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/IWLPlugSearchEngine.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/SolrResult.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Client.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Response.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');
require_once(__CA_LIB_DIR__.'/core/Search/Solr/SolrConfiguration.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/BaseSearchPlugin.php');
require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/GeocodeAttributeValue.php');

class WLPlugSearchEngineSolr extends BaseSearchPlugin implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	private $opn_indexing_subject_tablenum;
	private $ops_indexing_subject_tablename;
	private $opn_indexing_subject_row_id;
	
	static $s_doc_content_buffer = array();			// content buffer used when indexing
	static $s_element_code_cache = array();
	static $s_fieldnum_cache = array();				// cached field name-to-number values used when indexing
	# -------------------------------------------------------
	public function __construct(){
		parent::__construct();
		
		//if($this->_SolrConfigIsOutdated()){
		//	$this->_refreshSolrConfiguration();
		//}
		
		$this->opo_db = new Db();

		$this->opo_tep = new TimeExpressionParser();	
		
		$this->opo_geocode_parser = new GeocodeAttributeValue();
	}
	# -------------------------------------------------------
	public function init(){
		$this->opa_options = array(
				'start' => 0,
				'limit' => 150000,							// maximum number of hits to return [default=10000],
				'maxContentBufferSize' => 100				// maximum number of indexed content items to accumulate before writing to the database
		);

		$this->opa_capabilities = array(
			'incremental_reindexing' => false
		);
	}
	# -------------------------------------------------------
	/**
	 * Completely clear index (usually in preparation for a full reindex
	 */
	public function truncateIndex() {
		return true;
	}
	
	# -------------------------------------------------------
	public function setTableNum($pn_table_num) {
		$this->opn_subject_tablenum = $pn_table_num;
	}
	# -------------------------------------------------------
	public function __destruct() {	
		if (is_array(WLPlugSearchEngineSolr::$s_doc_content_buffer) && sizeof(WLPlugSearchEngineSolr::$s_doc_content_buffer)) {
			if(!$this->opo_db->connected(false, 'Solr->__destruct()')) { $this->opo_db->connect(); }		// Need to reconnect database if connection has already been torn down
			$this->flushContentBuffer();
		}
	}
	# -------------------------------------------------------
	public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null){
		$va_solr_search_filters = array();
		
		$vn_i = 0;
		$va_old_signs = is_object($po_rewritten_query) ? $po_rewritten_query->getSigns() : array();
		
		$va_terms = $va_signs = array();
		
		if ($po_rewritten_query) {
			foreach($po_rewritten_query->getSubqueries() as $o_lucene_query_element) {
			if (!$va_old_signs || !is_array($va_old_signs)) {	// if array is null then according to Zend Lucene all subqueries should be "are required"... so we AND them
				$vs_op = "AND";
			} else {
				if (is_null($va_old_signs[$vn_i])) {	// is the sign for a particular query is null then OR is (it is "neither required nor prohibited")
					$vs_op = 'OR';
				} else {
					$vs_op = ($va_old_signs[$vn_i] === false) ? 'NOT' : 'AND';	// true sign indicated "required" (AND) operation, false indicated "prohibited" (NOT) operation
				}
			}
			if ($vn_i == 0) { $vs_op = 'OR'; }
			
			switch($vs_class = get_class($o_lucene_query_element)) {
				case 'Zend_Search_Lucene_Search_Query_Term':
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				case 'Zend_Search_Lucene_Search_Query_Phrase':
					
					if ($vs_class != 'Zend_Search_Lucene_Search_Query_Term') {
						$va_raw_terms = array();
						foreach($o_lucene_query_element->getQueryTerms() as $o_term) {
							if (!$vs_access_point && ($vs_field = $o_term->field)) { $vs_access_point = $vs_field; }
							
							$va_raw_terms[] = $vs_text = (string)$o_term->text;
						}
						$vs_term = join(" ", $va_raw_terms);
					} else {
						$vs_access_point = $o_lucene_query_element->getTerm()->field;
						$vs_term = $o_lucene_query_element->getTerm()->text;
					}
					
					if ($vs_access_point) {
						list($vs_table, $vs_field, $vs_sub_field) = explode('.', $vs_access_point);
						
						if (in_array($vs_table, array('created', 'modified'))) {
							
							if (!$this->opo_tep->parse($vs_term)) { break; }
							$va_range = $this->opo_tep->getUnixTimestamps();
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
									if ($vn_user_id) {
										$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Boolean(
											array(
												new Zend_Search_Lucene_Index_Term('['.$this->opo_tep->getText(array('start_as_iso8601' => true))." TO ".$this->opo_tep->getText(array('end_as_iso8601' => true)).']', 'created'),
												new Zend_Search_Lucene_Index_Term($vn_user_id, 'created_user_id')
											),
											array(
												true, true
											)
										);
									} else {
										$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('['.$this->opo_tep->getText(array('start_as_iso8601' => true))." TO ".$this->opo_tep->getText(array('end_as_iso8601' => true)).']', 'created'));
									}
									break;
								case 'modified':
									if ($vn_user_id) {
										$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Boolean(
											array(
												new Zend_Search_Lucene_Index_Term('['.$this->opo_tep->getText(array('start_as_iso8601' => true))." TO ".$this->opo_tep->getText(array('end_as_iso8601' => true)).']', 'modified'),
												new Zend_Search_Lucene_Index_Term($vn_user_id, 'modified_user_id')
											),
											array(
												true, true
											)
										);
									} else {
										$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('['.$this->opo_tep->getText(array('start_as_iso8601' => true))." TO ".$this->opo_tep->getText(array('end_as_iso8601' => true)).']', 'modified'));
									}
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
												
												//
												// For certain types of attributes we can directly query the
												// attributes in the database rather than using the full text index
												// This allows us to do "intelligent" querying... for example on date ranges
												// parsed from natural language input and for length dimensions using unit conversion
												//
												switch($t_element->get('datatype')) {
													case 2:		// dates	
														$vb_exact = ($vs_term{0} == "#") ? true : false;	// dates prepended by "#" are considered "exact" or "contained - the matched dates must be wholly contained by the search term
														if ($vb_exact) {
															$vs_raw_term = substr($vs_term, 1);
															if ($this->opo_tep->parse($vs_term)) {
																$va_dates = $this->opo_tep->getHistoricTimestamps();
																// TODO: fix date handling to reflect distinctions in ranges
																$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('['.$this->opo_tep->getText(array('start_as_iso8601' => true))." TO ".$this->opo_tep->getText(array('end_as_iso8601' => true)).']', $vs_table.'.'.$vs_fld_num));
															}
														} else {
															if ($this->opo_tep->parse($vs_term)) {
																$va_dates = $this->opo_tep->getHistoricTimestamps();
																// TODO: fix date handling to reflect distinctions in ranges
																$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('['.$this->opo_tep->getText(array('start_as_iso8601' => true))." TO ".$this->opo_tep->getText(array('end_as_iso8601' => true)).']', $vs_table.'.'.$vs_fld_num));
															}
														}
														break;
													case 4:		// geocode
														$t_geocode = new GeocodeAttributeValue();
														if ($va_coords = caParseGISSearch($vs_term)) {
															$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('['.$va_coords['min_latitude'].','.$va_coords['min_longitude']." TO ".$va_coords['max_latitude'].','.$va_coords['max_longitude'].']', $vs_table.'.'.$vs_fld_num));
														}
														break;
													case 6:		// currency
														$t_cur = new CurrencyAttributeValue();
														$va_parsed_value = $t_cur->parseValue($vs_term, $t_element->getFieldValuesArray());
														$vn_amount = (float)$va_parsed_value['value_decimal1'];
														$vs_currency = preg_replace('![^A-Z0-9]+!', '', $va_parsed_value['value_longtext1']);
														
														$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($vn_amount, $vs_table.'.'.$vs_fld_num));
														break;
													case 8:		// length
														$t_len = new LengthAttributeValue();
														$va_parsed_value = $t_len->parseValue($vs_term, $t_element->getFieldValuesArray());
														$vn_len = (float)$va_parsed_value['value_decimal1'];	// this is always in meters so we can compare this value to the one in the database
														
														$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($vn_len, $vs_table.'.'.$vs_fld_num));
														break;
													case 9:		// weight
														$t_weight = new WeightAttributeValue();
														$va_parsed_value = $t_weight->parseValue($vs_term, $t_element->getFieldValuesArray());
														$vn_weight = (float)$va_parsed_value['value_decimal1'];	// this is always in kilograms so we can compare this value to the one in the database
														
														$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($vn_weight, $vs_table.'.'.$vs_fld_num));
														break;
													case 10:	// timecode
														$t_timecode = new TimecodeAttributeValue();
														$va_parsed_value = $t_timecode->parseValue($vs_term, $t_element->getFieldValuesArray());
														$vn_timecode = (float)$va_parsed_value['value_decimal1'];
														
														$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($vn_timecode, $vs_table.'.'.$vs_fld_num));	
														break;
													case 11: 	// integer
														$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term((float)$vs_term, $vs_table.'.'.$vs_fld_num));
														break;
													case 12:	// decimal
														$o_lucene_query_element = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term((float)$vs_term, $vs_table.'.'.$vs_fld_num));
														break;
													default:	// everything else
														$o_lucene_query_element->getTerm()->field = $vs_table.'.'.$vs_fld_num;
														break;
												}
											} else {
												$vn_fld_num = false;
												$vs_fld_num = $vs_field;
											}
										}
									}
								}
							}
						}
					}					
					break;
			}
				
			$va_terms[] = $o_lucene_query_element;
			$va_signs[] = is_array($va_old_signs) ? (array_key_exists($vn_i, $va_old_signs) ? $va_old_signs[$vn_i] : true) : true;

			
			$vn_i++;
		}
		
			$o_rewritten_query = new Zend_Search_Lucene_Search_Query_Boolean($va_terms, $va_signs);	
			$ps_search_expression = $this->_queryToString($o_rewritten_query);
		}
		if (is_array($pa_filters) && sizeof($pa_filters) && ($vs_filter_query = $this->_filterValueToQueryValue($pa_filters))) {
			$ps_search_expression .= ' AND ('.$vs_filter_query.')';
		}
		
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_solr_url')."/". /* general url */
			$this->opo_datamodel->getTableName($pn_subject_tablenum). /* core name (i.e. table name) */
			"/select"//. /* standard request handler */
		);

		$vo_http_client->setParameterGet($va_get = array(
			'q'		=> utf8_decode($ps_search_expression),
			'wt'	=> 'json',						// php fetching mode
			'fl'	=> $this->opo_datamodel->getTablePrimaryKeyName($pn_subject_tablenum),
			'start'	=> $this->getOption('start'),	// where to start the result fetching
			'rows'	=> $this->getOption('limit')	// how many results to fetch
		));
		
		//caDebug($va_get,'Solr GET parameters');

		$vo_http_response = $vo_http_client->request();
		$va_result = json_decode($vo_http_response->getBody(), true);
		
		//caDebug($va_result,'Result body');
		
		return new WLPlugSearchEngineSolrResult($va_result["response"]["docs"], $pn_subject_tablenum);
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
					$vs_query = $subquery;
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
	# -------------------------------------------------------
	private function _filterValueToQueryValue($pa_filters) {
		$va_terms = array();
		foreach($pa_filters as $va_filter) {
			switch($va_filter['operator']) {
				case '=':
					$va_terms[] = $va_filter['field'].':'.$va_filter['value'];
					break;
				case '<':
					$va_terms[] = $va_filter['field'].':{-'.pow(2,32).' TO '.$va_filter['value'].'}';
					break;
				case '<=':
					$va_terms[] = $va_filter['field'].':['.pow(2,32).' TO '.$va_filter['value'].']';
					break;
				case '>':
					$va_terms[] = $va_filter['field'].':{'.$va_filter['value'].' TO '.pow(2,32).'}';
					break;
				case '>=':
					$va_terms[] = $va_filter['field'].':['.$va_filter['value'].' TO '.pow(2,32).']';
					break;
				case '<>':
					$va_terms[] = 'NOT '.$va_filter['field'].':'.$va_filter['value'];
					break;
				case '-':
					$va_tmp = explode(',', $va_filter['value']);
					$va_terms[] = $va_filter['field'].':['.$va_tmp[0].' TO '.$va_tmp[1].']';
					break;
				case 'in':
					$va_tmp = explode(',', $va_filter['value']);
					$va_list = array();
					foreach($va_tmp as $vs_item) {
						$va_list[] = $va_filter['field'].':'.$vs_item;
					}

					$va_terms[] = '('.join(' OR ', $va_list).')';
					break;
				default:
				case 'is':
				case 'is not':
					// noop
					break;
			}
		}
		return join(' AND ', $va_terms);
	}
	# -------------------------------------------------------
	public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id){
		$this->opa_doc_content_buffer = array();
		$this->opn_indexing_subject_tablenum = $pn_subject_tablenum;
		$this->opn_indexing_subject_row_id = $pn_subject_row_id;
		$this->ops_indexing_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_tablenum);
		$this->ops_indexing_subject_tablename_pk = $this->opo_datamodel->getTablePrimaryKeyName($pn_subject_tablenum);
	}
	# -------------------------------------------------------
	private function _getMetadataElement($ps_element_code) {
		if (isset(WLPlugSearchEngineSolr::$s_element_code_cache[$ps_element_code])) { return WLPlugSearchEngineSolr::$s_element_code_cache[$ps_element_code]; }
		
		$t_element = new ca_metadata_elements($ps_element_code);
		if (!($vn_element_id = $t_element->getPrimaryKey())) { 
			return WLPlugSearchEngineSolr::$s_element_code_cache[$ps_element_code] = null;
		}
		
		return WLPlugSearchEngineSolr::$s_element_code_cache[$ps_element_code] = array(
			'element_id' => $vn_element_id,
			'element_code' => $t_element->get('element_code'),
			'datatype' => $t_element->get('datatype')
		);
	}
	# -------------------------------------------------------
	public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options){
		if (is_array($pm_content)) {
			$pm_content = serialize($pm_content);
		}

		$ps_content_tablename = $this->ops_indexing_subject_tablename;
		if ($ps_content_fieldname[0] === 'A') {
			// attribute
			$vn_field_num_proc = (int)substr($ps_content_fieldname, 1);

			if (!$va_element_info = $this->_getMetadataElement($vn_field_num_proc)) { return null; }
			switch($va_element_info['datatype']) {
				case 1: // text
				case 3:	// list
				case 5:	// url
				case 6: // currency
				case 8: // length
				case 9: // weight
				case 13: // LCSH
				case 14: // geonames
				case 15: // file
				case 16: // media
				case 19: // taxonomy
				case 20: // information service
					// noop
					break;
				case 2:	// daterange
					if (!is_array($pa_parsed_content = caGetISODates($pm_content))) { return null; }
					$this->opa_doc_content_buffer[$ps_content_tablename.'.'.$ps_content_fieldname.'_text'][] = $pm_content;
					$pm_content = $pa_parsed_content;
					break;
				case 4:	// geocode
					if ($va_coords = $this->opo_geocode_parser->parseValue($pm_content, $va_element_info)) {
						if (isset($va_coords['value_longtext2']) && $va_coords['value_longtext2']) {
							$this->opa_doc_content_buffer[$ps_content_tablename.'.'.$ps_content_fieldname.'_text'][] = $pm_content;
							$pm_content = explode(':', $va_coords['value_longtext2']);
						} else {
							return;
						}
					} else {
						return;
					}
					break;
				case 10:	// timecode
				case 12:	// numeric/float
					$pm_content = (float)$pm_content;
					break;
				case 11:	// integer
					$pm_content = (int)$pm_content;
					break;
				default:
					// noop
					break;
			}
		} else {
			// Intrinsic field
			$ps_content_tablename = $this->opo_datamodel->getTableName($pn_content_tablenum);
			$vn_field_num_proc = (int)substr($ps_content_fieldname, 1);
			$ps_content_fieldname = $this->opo_datamodel->getFieldName($ps_content_tablename, $vn_field_num_proc);
		}
		
		$this->opa_doc_content_buffer[$ps_content_tablename.'.'.$ps_content_fieldname][] = $pm_content;
	}
	# -------------------------------------------------------
	public function commitRowIndexing(){
		if(sizeof($this->opa_doc_content_buffer) > 0){
			WLPlugSearchEngineSolr::$s_doc_content_buffer[$this->ops_indexing_subject_tablename.'/'.$this->ops_indexing_subject_tablename_pk.'/'.$this->opn_indexing_subject_row_id] = $this->opa_doc_content_buffer;
		}
		unset($this->opn_indexing_subject_tablenum);
		unset($this->opn_indexing_subject_row_id);
		unset($this->ops_indexing_subject_tablename);

		if (sizeof(WLPlugSearchEngineSolr::$s_doc_content_buffer) > $this->getOption('maxContentBufferSize')) {
			$this->flushContentBuffer();
		}
	}
	# -------------------------------------------------------
	public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id){
		/* that's easy, huh? */
		$vs_post_xml = '<delete><id>'.$pn_subject_row_id.'</id></delete>';
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_solr_url')."/". /* general url */
			$this->opo_datamodel->getTableName($pn_subject_tablenum). /* core name (i.e. table name */
			"/update" /* updater */
		);
		$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		$vo_http_client->request();

		/* commit */
		$vs_post_xml = '<commit />';
		$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		
		try {
			$vo_http_client->request();
		} catch (Exception $e) {
			// Commit error
			caLogEvent('ERR', _t('Commit of index delete failed: %1', $e->getMessage()), 'Solr->removeRowIndexing()');
		}
	}
	# ------------------------------------------------
	public function flushContentBuffer() {
		/* build Solr xml indexing format for each core */
		$va_post_xml = array();
		foreach(WLPlugSearchEngineSolr::$s_doc_content_buffer as $vs_key => $va_doc_content_buffer) {
			$va_key = explode('/', $vs_key);
			$va_post_xml[$va_key[0]] .= "\t<doc>\n";
			
				foreach($va_doc_content_buffer as $vs_field_name => $va_field_content){
					$va_acc = array();
					foreach($va_field_content as $vs_field_content) {
						if (is_array($vs_field_content)) {
							foreach($vs_field_content as $vs_field_content_element) {
								$va_post_xml[$va_key[0]].="\t\t".'<field name="';
								$va_post_xml[$va_key[0]].= $vs_field_name;
								$va_post_xml[$va_key[0]].='"><![CDATA[';
								$va_post_xml[$va_key[0]].= caEscapeForXML($vs_field_content_element);
								$va_post_xml[$va_key[0]].=']]></field>'."\n";
							}
						} else {
							$va_acc[] = $vs_field_content;
						}
					}
					
					if (sizeof($va_acc)) {
						$va_post_xml[$va_key[0]].="\t\t".'<field name="';
						$va_post_xml[$va_key[0]].= $vs_field_name;
						$va_post_xml[$va_key[0]].='"><![CDATA[';
						$va_post_xml[$va_key[0]].= caEscapeForXML(join("\n", $va_acc));
						$va_post_xml[$va_key[0]].=']]></field>'."\n";
					}
				}
				
				if (!isset($va_doc_content_buffer[$va_key[0].".".$va_key[1]])) { 
					/* add pk */
					$va_post_xml[$va_key[0]].= "\t\t".'<field name="'.$va_key[0].".".$va_key[1].'">'.$va_key[2].'</field>'."\n";
				}
				
			$va_post_xml[$va_key[0]] .= "\t</doc>\n";
			
			// Output created on and modified on timestamps
				$qr_res = $this->opo_db->query("	
								SELECT ccl.log_id, ccl.log_datetime, ccl.changetype, ccl.user_id
								FROM ca_change_log ccl
								WHERE
									(ccl.logged_table_num = ?) AND (ccl.logged_row_id = ?)
									AND
									(ccl.changetype <> 'D')
				", $this->opo_datamodel->getTableNum($va_key[0]), (int)$va_key[2]);
				while ($qr_res->nextRow()) {
					$va_post_xml[$va_key[0]] .= "\t<doc>\n";
					
					// We "fake" the <table>.<primary key> value here to be the log_id of the change log entry to ensure that the log entry
					// document has a different unique key than the entry for the actual record. If we didn't do this then we'd overwrite
					// the indexing for the record itself with indexing for successful log entries. Since the SearchEngine is looking for
					// just the primary key, sans table name, it's ok to do this hack.
					
					// make (relatively) sure we don't overwrite actual index entries with the log entries (the above statement is not quite
					// true as <table>.<primary_key> is defined as uniqueKey and used by Solr to determine if something is a new document or
					// something old that has to be updated)
					$vs_key = intval($va_key[2]."00000".$qr_res->get('log_id'));
					
					$va_post_xml[$va_key[0]].= "\t\t".'<field name="'.$va_key[0].".".$va_key[1].'">'.$vs_key.'</field>'."\n";
					$va_post_xml[$va_key[0]].= "\t\t".'<field name="'.$va_key[1].'">'.$va_key[2].'</field>'."\n";
					if ($qr_res->get('changetype') == 'I') {
						$va_post_xml[$va_key[0]].="\t\t".'<field name="created"';
						$va_post_xml[$va_key[0]].='><![CDATA[';
						$va_post_xml[$va_key[0]].= date("c", $qr_res->get('log_datetime')).'Z';
						$va_post_xml[$va_key[0]].=']]></field>'."\n";
						$va_post_xml[$va_key[0]].="\t\t".'<field name="created_user_id"><![CDATA['.$qr_res->get('user_id').']]></field>'."\n";
					} else {
						$va_post_xml[$va_key[0]].="\t\t".'<field name="modified"';
						$va_post_xml[$va_key[0]].='><![CDATA[';
						$va_post_xml[$va_key[0]].= date("c", $qr_res->get('log_datetime')).'Z';
						$va_post_xml[$va_key[0]].=']]></field>'."\n";
						$va_post_xml[$va_key[0]].="\t\t".'<field name="modified_user_id"><![CDATA['.$qr_res->get('user_id').']]></field>'."\n";
					}
					$va_post_xml[$va_key[0]] .= "\t</doc>\n";
				}
		}
		
		/* No delete of the old stuff needed. If the pk (defined as uniqueKey) already exists, it is automatically updated */

		/* send data */
		$vo_http_client = new Zend_Http_Client();
		foreach($va_post_xml as $vs_core => $vs_post_xml) {

			//caDebug($vs_post_xml,$vs_core);

			$vo_http_client->setUri(
				$this->opo_search_config->get('search_solr_url')."/". /* general url */
				$vs_core. /* core name (i.e. table name) */
				"/update" /* updater */
			);
			$vo_http_client->setRawData("<add>{$vs_post_xml}</add>")->setEncType('text/xml')->request('POST');
			
			try {
				$vo_http_response = $vo_http_client->request();
				if ($o_resp = @new SimpleXMLElement($vo_http_response->getBody())) {
					$va_status = $o_resp->xpath("/response/lst/int[@name='status']");
					$vn_status = (int)$va_status[0];
					if ($vn_status > 0) { 
						caLogEvent('ERR', _t('Indexing failed for %1: status %2', $vs_core, $vn_status), 'Solr->flushContentBuffer()');
					} else {						
						/* commit */
						try {
							$vs_post_xml = '<commit />';
							$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
							$vo_http_response = $vo_http_client->request();
							if ($o_resp = @new SimpleXMLElement($vo_http_response->getBody())) {
								$va_status = $o_resp->xpath("/response/lst/int[@name='status']");
								$vn_status = (int)$va_status[0];
								if ($vn_status > 0) { 
									caLogEvent('ERR', _t('Indexing commit failed for %1: status %2', $vs_core, $vn_status), 'Solr->flushContentBuffer()');
								}
							} else {
								caLogEvent('ERR', _t('Indexing commit failed for %1: invalid response from SOLR %2', $vs_core, $vo_http_response->getBody()), 'Solr->flushContentBuffer()');
							}
						} catch (Exception $e) {
							// Commit error
							caLogEvent('ERR', _t('Indexing commit failed: %1; response was %2', $e->getMessage(), $vo_http_response->getBody()), 'Solr->flushContentBuffer()');
						}
					}
				} else {
					caLogEvent('ERR', _t('Indexing failed for %1: invalid response from SOLR %2', $vs_core, $vo_http_response->getBody()), 'Solr->flushContentBuffer()');
				}
			} catch (Exception $e) {
				// Indexing error
				caLogEvent('ERR', _t('Indexing failed for %1: %2; response was %3', $vs_core, $e->getMessage(), $vo_http_response->getBody()), 'Solr->flushContentBuffer()');
			}
		}

		/* clean up */
		unset($vo_http_client);
		unset($vs_post_xml);
		$this->opa_doc_content_buffer = array();
		WLPlugSearchEngineSolr::$s_doc_content_buffer = array();
	}
	# -------------------------------------------------------
	public function optimizeIndex($pn_tablenum){
		/* optimize */
		$vs_post_xml = '<optimize />';
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_solr_url')."/". /* general url */
			$this->opo_datamodel->getTableName($pn_tablenum). /* core name (i.e. table name */
			"/update" /* updater */
		);
		
		try {
			$vo_http_client->setRawData($vs_post_xml)->setEncType('text/xml')->request('POST');
		
			$vo_http_response = $vo_http_client->request();
		} catch (Exception $e) {
			// Optimize error
			caLogEvent('ERR', _t('Index optimize failed: %1', $e->getMessage()), 'Solr->optimizeIndex()');
		}
	}
	# --------------------------------------------------
	private function _refreshSolrConfiguration(){
			SolrConfiguration::updateSolrConfiguration();
			/* reload all cores */
			$vo_http_client = new Zend_Http_Client();
			$vo_http_client->setUri(
				$this->opo_search_config->get('search_solr_url')."/". /* general url */
				"/admin/cores" /* CoreAdminHandler */
			);

			$vo_search_indexing_config = Configuration::load($this->opo_search_config->get('search_indexing_config'));
			$va_tables = $vo_search_indexing_config->getAssocKeys();
			/* reload all tables */
			foreach($va_tables as $vs_table){
				$vo_http_client->setParameterGet(array(
					'action'		=> 'RELOAD',
					'core'			=> $vs_table
				));
				$vo_http_client->request();
			}
	}
	# --------------------------------------------------
	public function _SolrConfigIsOutdated(){
		global $o_db;
		if(!is_object($o_db)){
			$o_db = new Db();
		}

		$va_searchconfig_stat = stat($this->opo_search_config->get('search_indexing_config'));
		if(file_exists($this->opo_search_config->get('search_solr_home_dir')."/".$this->ops_indexing_subject_tablename.'/conf/schema.xml')){
			$va_solrconfig_stat = stat(
				$this->opo_search_config->get('search_solr_home_dir')."/".
				$this->ops_indexing_subject_tablename.
				'/conf/schema.xml'
			);
		} else {
			return true;
		}

		if($va_searchconfig_stat['mtime']>$va_solrconfig_stat['mtime']){
			return true;
		}

		$va_frontend_options = array(
			'lifetime' => null, 				/* cache lives forever (until manual destruction) */
			'logging' => false,					/* do not use Zend_Log to log what happens */
			'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
			'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
			'automatic_serialization' => true	/* we store arrays, so we have to enable that */
		);
		$vs_cache_dir = __CA_APP_DIR__.'/tmp';

		$va_backend_options = array(
			'cache_dir' => $vs_cache_dir,		/* where to store cache data? */
			'file_locking' => true,				/* cache corruption avoidance */
			'read_control' => false,			/* no read control */
			'file_name_prefix' => 'ca_cache',	/* prefix of cache files */
			'cache_file_perm' => 0777			/* permissions of cache files */
		);
		$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);

		if (!($va_cache_data = $vo_cache->load('ca_search_indexing_info_'.$this->ops_indexing_subject_tablename))) {
			return true;
		}

		$va_cache_data = array_keys($va_cache_data);

		$qr_type_restrictions = $o_db->query('
			SELECT DISTINCT element_id
			FROM ca_metadata_type_restrictions
			WHERE table_num = ?
		',(int)$this->opn_indexing_subject_tablenum);
		$va_type_restrictions = array();
		while($qr_type_restrictions->nextRow()){
			$va_type_restrictions[] = $qr_type_restrictions->get('element_id');
		}
		$va_table_fields = array();
		foreach($va_type_restrictions as $vn_element_id){
			$va_table_fields[] = '_ca_attribute_'.$vn_element_id;
		}

		/* this is a very stupid way to find out if $va_table_fields is a subset of $va_cache_data */
		/* it should be sufficient due to the invariants in the code above though */
		if(count(array_unique(array_merge($va_table_fields,$va_cache_data))) > count($va_cache_data)){
			return true;
		}

		return false;
	}
	# --------------------------------------------------
	public function engineName() {
		return 'Solr';
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
		
		$t_instance = $this->opo_datamodel->getInstanceByTableNum($pn_table_num, true);
		$vs_pk = $t_instance->primaryKey();
		
		$vn_limit = 0;
		if (isset($pa_options['limit']) && ($pa_options['limit'] > 0)) { 
			$vn_limit = intval($pa_options['limit']);
		}
		
		// TODO: just do a standard search for now... we'll have to think harder about
		// how to optimize this for SOLR later
		$o_results = $this->search($pn_table_num, $ps_search);
		
		$va_hits = array();
		$vn_i = 0;
		while($o_results->nextHit()) {
			if (($vn_limit > 0) && ($vn_limit <= $vn_i)) { break; }
			$va_hits[$o_results->get($vs_pk)] = true;
			$vn_i++;
		}
		
		return $va_hits;
	}
	# --------------------------------------------------
	# Utils
	# --------------------------------------------------
	private function getFieldNum($pn_table_name_or_num, $ps_fieldname) {
		if (isset(WLPlugSearchEngineSolr::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname])) { return WLPlugSearchEngineSolr::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname]; }
		
		$vs_table_name = is_numeric($pn_table_name_or_num) ? $this->opo_datamodel->getTableName((int)$pn_table_name_or_num) : (string)$pn_table_name_or_num;
		return WLPlugSearchEngineSolr::$s_fieldnum_cache[$pn_table_name_or_num.'/'.$ps_fieldname] = $this->opo_datamodel->getFieldNum($vs_table_name, $ps_fieldname);
	}
	# -------------------------------------------------------
}
?>