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
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Intrinsic.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/DateRange.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Numeric.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Geocode.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Integer.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Timecode.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Timestamp.php');

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
	 * Filters set by search engine
	 * @var array
	 */
	protected $opa_filters;

	/**
	 * Filters ready for ElasticSearch
	 * @var array
	 */
	protected $opa_additional_filters;

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
		$this->opa_additional_filters = array();

		$this->rewrite();
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
	public function getSearchExpression() {
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

	/**
	 * @return array
	 */
	public function getAdditionalFilters() {
		return $this->opa_additional_filters;
	}

	/**
	 * Rewrite query
	 * @throws \Exception
	 * @throws \Zend_Search_Lucene_Exception
	 */
	protected function rewrite() {
		$vs_search_expression = $this->getSearchExpression();

		// find terms in subqueries and run them through FieldType rewriting and then re-construct the same
		// subqueries to replace them in the query string, taking advantage of their __toString() method
		$va_new_search_expression_parts = [];
		foreach($this->getRewrittenQuery()->getSubqueries() as $o_subquery) {
			switch(get_class($o_subquery)) {
				case 'Zend_Search_Lucene_Search_Query_Range':
				case 'Zend_Search_Lucene_Search_Query_Term':
				case 'Zend_Search_Lucene_Search_Query_Phrase':
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
					$o_new_subquery = $this->rewriteSubquery($o_subquery);
					$va_new_search_expression_parts[] = preg_replace('/^\+/u', '', (string) $o_new_subquery);
					break;
				case 'Zend_Search_Lucene_Search_Query_Boolean':
					/** @var $o_subquery \Zend_Search_Lucene_Search_Query_Boolean. */
					$va_new_subqueries = array();
					foreach($o_subquery->getSubqueries() as $o_subsubquery) {
						$va_new_subqueries[] = $this->rewriteSubquery($o_subsubquery);
					}
					$o_new_subquery = new \Zend_Search_Lucene_Search_Query_Boolean($va_new_subqueries, $o_subquery->getSigns());
					$va_new_search_expression_parts[] = preg_replace('/^\+/u', '', (string) $o_new_subquery);
					break;
				default:
					throw new \Exception('Encountered unknown Zend query type in ElasticSearch\Query: ' . get_class($o_subquery). '. Query was: ' . $vs_search_expression);
					break;
			}
		}

		if(sizeof($va_new_search_expression_parts) == sizeof($this->getRewrittenQuery()->getSigns())) {
			$vs_search_expression = '';
			$va_signs = $this->getRewrittenQuery()->getSigns();
			foreach($va_new_search_expression_parts as $i=> $vs_part) {
				$vb_sign = array_shift($va_signs);
				if($vs_part) {
					if($vb_sign) {
						$vs_search_expression .= "+($vs_part) ";
					} else {
						$vs_search_expression .= "($vs_part) ";
					}
				}
			}

			$vs_search_expression = trim($vs_search_expression);
		} else {
			$vs_search_expression = join(' AND ', array_filter($va_new_search_expression_parts));
		}

		// get rid of empty "AND|OR ()" or "() AND|OR" blocks that prevent ElasticSearch query parsing
		// (can happen in advanced search forms)
		$vs_search_expression = preg_replace("/\s*(AND|OR)\s+\(\s*\)/u", '', $vs_search_expression);
		$vs_search_expression = preg_replace("/\(\s*\)\s+(AND|OR)\s*/u", '', $vs_search_expression);

		// add filters
		if ($vs_filter_query = $this->getFilterQuery()) {
			if(($vs_search_expression == '()') || ($vs_search_expression == '')) {
				$vs_search_expression = $vs_filter_query;
			} else {
				$vs_search_expression = "({$vs_search_expression}) AND ({$vs_filter_query})";
			}
		}

		$this->ops_search_expression = $vs_search_expression;
	}

	/**
	 * @param $o_subquery
	 * @return string|\Zend_Search_Lucene_Search_Query
	 * @throws \Exception
	 * @throws \Zend_Search_Lucene_Exception
	 */
	public function rewriteSubquery($o_subquery) {
		switch(get_class($o_subquery)) {
			case 'Zend_Search_Lucene_Search_Query_Range':
				/** @var $o_subquery \Zend_Search_Lucene_Search_Query_Range */
				$o_lower_term = caRewriteElasticSearchTermFieldSpec($o_subquery->getLowerTerm());
				$o_lower_fld = $this->getFieldTypeForTerm($o_lower_term);
				$o_upper_term = caRewriteElasticSearchTermFieldSpec($o_subquery->getUpperTerm());
				$o_upper_fld = $this->getFieldTypeForTerm($o_upper_term);

				$o_new_subquery = null;

				if($o_lower_fld instanceof FieldTypes\Geocode) {
					$this->opa_additional_filters[] = ['geo_shape' => $o_lower_fld->getFilterForRangeQuery($o_lower_term, $o_upper_term)];
				} else {
					$o_lower_rewritten_term = $o_lower_fld->getRewrittenTerm($o_lower_term);
					$o_upper_rewritten_term = $o_upper_fld->getRewrittenTerm($o_upper_term);

					if($o_lower_rewritten_term && $o_upper_rewritten_term) {
						$o_new_subquery = new \Zend_Search_Lucene_Search_Query_Range(
							$o_lower_fld->getRewrittenTerm($o_lower_term),
							$o_upper_fld->getRewrittenTerm($o_upper_term),
							$o_subquery->isInclusive()
						);
					}
				}

				return $this->getSubqueryWithAdditionalTerms($o_new_subquery, $o_lower_fld, $o_lower_term);
			case 'Zend_Search_Lucene_Search_Query_Term':
				/** @var $o_subquery \Zend_Search_Lucene_Search_Query_Term */
				$o_term = caRewriteElasticSearchTermFieldSpec($o_subquery->getTerm());
				$o_fld = $this->getFieldTypeForTerm($o_term);

				$o_new_subquery = null;
				if(($o_fld instanceof FieldTypes\DateRange) || ($o_fld instanceof FieldTypes\Timestamp)) {
					$o_new_subquery = null;
					foreach($o_fld->getFiltersForTerm($o_term) as $va_filter) {
						$this->opa_additional_filters[] = $va_filter;
					}
					break;
				}  else {
					if($o_rewritten_term = $o_fld->getRewrittenTerm($o_term)) {
						$o_new_subquery = new \Zend_Search_Lucene_Search_Query_Term($o_fld->getRewrittenTerm($o_term));
					}
				}

				return $this->getSubqueryWithAdditionalTerms($o_new_subquery, $o_fld, $o_term);
			case 'Zend_Search_Lucene_Search_Query_Phrase':
				/** @var $o_subquery \Zend_Search_Lucene_Search_Query_Phrase */
				$o_new_subquery = new \Zend_Search_Lucene_Search_Query_Phrase();

				$va_fields_in_subquery = array();
				foreach($o_subquery->getTerms() as $o_term) {
					$va_fields_in_subquery[] = $o_term->field;
				}

				$vb_multiterm_all_terms_same_field = (sizeof(array_unique($va_fields_in_subquery)) < 2) && (sizeof($o_subquery->getTerms()) > 1);

				// below we convert stuff multi term phrase query stuff like
				// 		ca_objects.dimensions_width:"30 cm",
				// which is parsed as two terms ... "30", and "cm" to one relatively simple term query
				if($vb_multiterm_all_terms_same_field && ($o_first_term = array_shift($o_subquery->getTerms()))) {
					$o_first_term = caRewriteElasticSearchTermFieldSpec($o_first_term);
					$o_fld = $this->getFieldTypeForTerm($o_first_term);
					if(($o_fld instanceof FieldTypes\Length) || ($o_fld instanceof FieldTypes\Weight) || ($o_fld instanceof FieldTypes\Currency)) {
						$vs_acc = '';
						foreach($o_subquery->getTerms() as $o_t) {
							$vs_acc .= $o_t->text;
						}
						$o_term = new \Zend_Search_Lucene_Index_Term($vs_acc, $o_first_term->field);
						$o_rewritten_term = $o_fld->getRewrittenTerm($o_term);

						// sometimes, through the magic of advanced search forms, range queries like
						//		ca_objects.dimensions_length:"25cm - 30 cm"
						// end up here. so we make them "real" range queries below
						if($this->isDisguisedRangeQuery($o_rewritten_term)) {
							return $this->getSubqueryWithAdditionalTerms(
								$this->rewriteIndexTermAsRangeQuery($o_rewritten_term, $o_fld),
								$o_fld, $o_term
							);
						}

						$o_new_subquery->addTerm($o_rewritten_term);
						return $this->getSubqueryWithAdditionalTerms($o_new_subquery, $o_fld, $o_term);
					}
				}

				// "normal" phrase rewriting below
				foreach($o_subquery->getTerms() as $o_term) {
					$o_term = caRewriteElasticSearchTermFieldSpec($o_term);
					$o_fld = $this->getFieldTypeForTerm($o_term);

					if($o_fld instanceof FieldTypes\Geocode) {
						$o_new_subquery = null;
						$this->opa_additional_filters[] = ['geo_shape' => $o_fld->getFilterForPhraseQuery($o_subquery)];
						break;
					} elseif(($o_fld instanceof FieldTypes\DateRange) || ($o_fld instanceof FieldTypes\Timestamp)) {
						$o_new_subquery = null;

						foreach($o_fld->getFiltersForPhraseQuery($o_subquery) as $va_filter) {
							$this->opa_additional_filters[] = $va_filter;
						}
						break;
					} else {
						if($o_rewritten_term = $o_fld->getRewrittenTerm($o_term)) {
							if($vb_multiterm_all_terms_same_field) {
								$o_rewritten_term->text = preg_replace("/\"(.+)\"/u", "$1", $o_rewritten_term->text);
							}
							$o_new_subquery->addTerm($o_rewritten_term);
						}
					}
				}

				return $this->getSubqueryWithAdditionalTerms($o_new_subquery, $o_fld, $o_term);
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				/** @var @o_subquery \Zend_Search_Lucene_Search_Query_MultiTerm */
				$va_terms = $o_subquery->getTerms();

				$va_new_terms = array();
				foreach($va_terms as $o_term) {
					$o_term = caRewriteElasticSearchTermFieldSpec($o_term);
					$o_fld = $this->getFieldTypeForTerm($o_term);
					$va_new_terms[] = $o_fld->getRewrittenTerm($o_term);
				}

				$o_new_subquery = new \Zend_Search_Lucene_Search_Query_MultiTerm($va_new_terms, $o_subquery->getSigns());
				return $this->getSubqueryWithAdditionalTerms($o_new_subquery, $o_fld, $o_term);
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				/** @var $o_subquery \Zend_Search_Lucene_Search_Query_Boolean */
				$va_new_subqueries = array();
				foreach($o_subquery->getSubqueries() as $o_subsubquery) {
					$va_new_subqueries[] = $this->rewriteSubquery($o_subsubquery);
				}
				$o_new_subquery = new \Zend_Search_Lucene_Search_Query_Boolean($va_new_subqueries, $o_subquery->getSigns());
				return $o_new_subquery;
			default:
				throw new \Exception('Encountered unknown Zend subquery type in ElasticSearch\Query: ' . get_class($o_subquery));
				break;
		}
	}

	/**
	 * @param \Zend_Search_Lucene_Search_Query $po_original_subquery
	 * @param FieldTypes\FieldType $po_fld
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return \Zend_Search_Lucene_Search_Query
	 */
	protected function getSubqueryWithAdditionalTerms($po_original_subquery, $po_fld, $po_term) {
		if(($va_additional_terms = $po_fld->getAdditionalTerms($po_term)) && is_array($va_additional_terms)) {

			// we cant use the index terms as is; have to construct term queries
			$va_additional_term_queries = array();
			if($po_original_subquery) { $va_additional_term_queries[] = $po_original_subquery; }
			foreach($va_additional_terms as $o_additional_term) {
				$va_additional_term_queries[] = new \Zend_Search_Lucene_Search_Query_Term($o_additional_term);
			}

			return join(' AND ', $va_additional_term_queries);
		} else {
			return $po_original_subquery;
		}
	}

	/**
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return \ElasticSearch\FieldTypes\FieldType
	 */
	protected function getFieldTypeForTerm($po_term) {
		$va_parts = preg_split("!(\\\)?/!", $po_term->field);
		$vs_table = $va_parts[0];
		$vs_fld = array_pop($va_parts);
		return FieldTypes\FieldType::getInstance($vs_table, $vs_fld);
	}

	protected function getFilterQuery() {
		$va_terms = array();
		foreach($this->getFilters() as $va_filter) {
			$va_filter['field'] = str_replace('.', '\/', $va_filter['field']);
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

	/**
	 * Is this index term a disguised range search? If so,
	 * we can rewrite it as actual ranged search
	 *
	 * Note: this is only for Length, Weight, Currency ...
	 *
	 * @param \Zend_Search_Lucene_Index_Term $o_term
	 * @return bool
	 */
	protected function isDisguisedRangeQuery($o_term) {
		return (bool) preg_match("/[0-9]+.*to[\s]*[0-9]+/u", $o_term->text);
	}

	/**
	 * Rewrite index term as range query
	 *
	 * @param \Zend_Search_Lucene_Index_Term $o_term
	 * @param \ElasticSearch\FieldTypes\FieldType $o_fld
	 * @return \Zend_Search_Lucene_Search_Query_Range
	 * @throws \Exception
	 */
	protected function rewriteIndexTermAsRangeQuery($o_term, $o_fld) {
		$vs_lower_term = $vs_upper_term = null;

		if(preg_match("/^(.+)to/u", $o_term->text, $va_matches)) {
			$vs_lower_term = trim($va_matches[1]);
		}

		if(preg_match("/to(.+)$/u", $o_term->text, $va_matches)) {
			$vs_upper_term = trim($va_matches[1]);
		}

		if(!$vs_lower_term || !$vs_upper_term) {
			throw new \Exception('Could not parse index term as range query');
		}

		$o_int_lower_term = new \Zend_Search_Lucene_Index_Term($vs_lower_term, $o_term->field);
		$o_int_upper_term = new \Zend_Search_Lucene_Index_Term($vs_upper_term, $o_term->field);

		$o_lower_term = $o_fld->getRewrittenTerm($o_int_lower_term);
		$o_upper_term = $o_fld->getRewrittenTerm($o_int_upper_term);

		return new \Zend_Search_Lucene_Search_Query_Range($o_lower_term, $o_upper_term, true);
	}
}
