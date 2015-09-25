<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/Query.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

namespace ElasticSearch;

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/Field.php');

class Query {

	/**
	 * Subject table
	 * @var int
	 */
	protected $opn_subject_table_num;
	/**
	 * Search expression
	 * @var string
	 */
	protected $ops_search_expression;
	/**
	 * Rewritten query
	 * @var \Zend_Search_Lucene_Search_Query_Boolean
	 */
	protected $opo_rewritten_query;
	/**
	 * Filters
	 * @var array
	 */
	protected $opa_filters;

	/**
	 * Query constructor.
	 * @param int $opn_subject_table_num
	 * @param string $ops_search_expression
	 * @param \Zend_Search_Lucene_Search_Query_Boolean $opo_rewritten_query
	 * @param array $opa_filters
	 */
	public function __construct($opn_subject_table_num, $ops_search_expression, \Zend_Search_Lucene_Search_Query_Boolean $opo_rewritten_query, array $opa_filters) {
		$this->opn_subject_table_num = $opn_subject_table_num;
		$this->ops_search_expression = $ops_search_expression;
		$this->opo_rewritten_query = $opo_rewritten_query;
		$this->opa_filters = $opa_filters;
	}

	/**
	 * @return int
	 */
	protected function getSubjectTableNum() {
		return $this->opn_subject_table_num;
	}

	/**
	 * @return string
	 */
	protected function getSearchExpression() {
		return $this->ops_search_expression;
	}

	/**
	 * @return \Zend_Search_Lucene_Search_Query_Boolean
	 */
	protected function getRewrittenQuery() {
		return $this->opo_rewritten_query;
	}

	/**
	 * @return array
	 */
	protected function getFilters() {
		return $this->opa_filters;
	}

	public function get() {

		foreach($this->getRewrittenQuery()->getSubqueries() as $o_subquery) {
			//var_dump($o_subquery);
		}

		$vs_search_expression = $this->getSearchExpression();

		if ($vs_filter_query = $this->getFilterQuery()) {
			$vs_search_expression = "({$vs_search_expression}) AND ({$vs_filter_query})";
		}

		return $vs_search_expression;
	}

	protected function getFilterQuery() {
		$va_terms = array();
		foreach($this->getFilters() as $va_filter) {
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
						// this case specifically happens when filtering list item search results by type id
						// (if type based access control is enabled, that is). The filter is something like
						// type_id IN 2,3,4,NULL. So we have to allow for empty values, which is a little bit
						// different in ElasticSearch.
						if(strtolower($vs_item) == 'null') {
							$va_list[] = '_missing_:' . $va_filter['field'];
						} else {
							$va_list[] = $va_filter['field'].':'.$vs_item;
						}

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
}
