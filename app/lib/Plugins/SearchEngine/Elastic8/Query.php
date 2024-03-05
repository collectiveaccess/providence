<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/Query.php :
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

namespace Elastic8;

use Elastic8\FieldTypes\FieldType;
use Exception;
use Zend_Search_Lucene_Exception;
use Zend_Search_Lucene_Index_Term;
use Zend_Search_Lucene_Search_Query;
use Zend_Search_Lucene_Search_Query_Boolean;
use Zend_Search_Lucene_Search_Query_MultiTerm;
use Zend_Search_Lucene_Search_Query_Phrase;
use Zend_Search_Lucene_Search_Query_Range;
use Zend_Search_Lucene_Search_Query_Term;

require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/Field.php' );
require_once( __CA_MODELS_DIR__ . '/ca_metadata_elements.php' );

require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Intrinsic.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/DateRange.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Numeric.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Geocode.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Integer.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Timecode.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Timestamp.php' );

class Query {

	/**
	 * Subject table
	 *
	 * @var int
	 */
	protected $subject_table_num;
	/**
	 * Search expression
	 *
	 * @var string
	 */
	protected $search_expression;
	/**
	 * Rewritten query
	 *
	 * @var Zend_Search_Lucene_Search_Query_Boolean
	 */
	protected $rewritten_query;
	/**
	 * Filters set by search engine
	 *
	 * @var array
	 */
	protected $filters;

	/**
	 * Filters ready for ElasticSearch
	 *
	 * @var array
	 */
	protected $additional_filters;

	/**
	 * Query constructor.
	 *
	 * @param int $subject_table_num
	 * @param string $search_expression
	 * @param Zend_Search_Lucene_Search_Query_Boolean $rewritten_query
	 * @param array $filters
	 */
	public function __construct(
		$subject_table_num, $search_expression, Zend_Search_Lucene_Search_Query_Boolean $rewritten_query,
		array $filters
	) {
		$this->subject_table_num = $subject_table_num;
		$this->search_expression = $search_expression;
		$this->rewritten_query = $rewritten_query;
		$this->filters = $filters;
		$this->additional_filters = array();

		$this->rewrite();
	}

	/**
	 * @return int
	 */
	protected function getSubjectTableNum() {
		return $this->subject_table_num;
	}

	/**
	 * @return string
	 */
	public function getSearchExpression() {
		return $this->search_expression;
	}

	/**
	 * @return Zend_Search_Lucene_Search_Query_Boolean
	 */
	protected function getRewrittenQuery() {
		return $this->rewritten_query;
	}

	/**
	 * @return array
	 */
	protected function getFilters() {
		return $this->filters;
	}

	/**
	 * @return array
	 */
	public function getAdditionalFilters() {
		return $this->additional_filters;
	}

	/**
	 * Rewrite query
	 *
	 * @throws Exception
	 * @throws Zend_Search_Lucene_Exception
	 */
	protected function rewrite() {
		$search_expression = $this->getSearchExpression();

		// find terms in subqueries and run them through FieldType rewriting and then re-construct the same
		// subqueries to replace them in the query string, taking advantage of their __toString() method
		$new_search_expression_parts = [];
		foreach ( $this->getRewrittenQuery()->getSubqueries() as $subquery ) {
			switch ( get_class( $subquery ) ) {
				case 'Zend_Search_Lucene_Search_Query_Range':
				case 'Zend_Search_Lucene_Search_Query_Term':
				case 'Zend_Search_Lucene_Search_Query_Phrase':
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
					$new_subquery = $this->rewriteSubquery( $subquery );
					$new_search_expression_parts[] = preg_replace( '/^\+/u', '', (string) $new_subquery );
					break;
				case 'Zend_Search_Lucene_Search_Query_Boolean':
					/** @var $subquery Zend_Search_Lucene_Search_Query_Boolean. */
					$new_subqueries = array();
					foreach ( $subquery->getSubqueries() as $subsubquery ) {
						$new_subqueries[] = $this->rewriteSubquery( $subsubquery );
					}
					$new_subquery = new Zend_Search_Lucene_Search_Query_Boolean( $new_subqueries,
						$subquery->getSigns() );
					$new_search_expression_parts[] = preg_replace( '/^\+/u', '', (string) $new_subquery );
					break;
				default:
					throw new Exception( 'Encountered unknown Zend query type in Elastic8\Query: '
						. get_class( $subquery ) . '. Query was: ' . $search_expression );
					break;
			}
		}
		$signs = $this->getRewrittenQuery()->getSigns() ?: [];
		if ( sizeof( $new_search_expression_parts ) == sizeof( $signs ) ) {
			$search_expression = '';

			foreach ( $new_search_expression_parts as $i => $part ) {
				$sign = array_shift( $signs );
				if ( $part ) {
					if ( $sign ) {
						$search_expression .= "+($part) ";
					} else {
						$search_expression .= "($part) ";
					}
				}
			}

			$search_expression = trim( $search_expression );
		} else {
			$search_expression = join( ' AND ', array_filter( $new_search_expression_parts ) );
		}

		// get rid of empty "AND|OR ()" or "() AND|OR" blocks that prevent ElasticSearch query parsing
		// (can happen in advanced search forms)
		$search_expression = preg_replace( "/\s*(AND|OR)\s+\(\s*\)/u", '', $search_expression );
		$search_expression = preg_replace( "/\(\s*\)\s+(AND|OR)\s*/u", '', $search_expression );

		// add filters
		if ( $filter_query = $this->getFilterQuery() ) {
			if ( ( $search_expression == '()' ) || ( $search_expression == '' ) ) {
				$search_expression = $filter_query;
			} else {
				$search_expression = "({$search_expression}) AND ({$filter_query})";
			}
		}

		$this->search_expression = $search_expression;
	}

	/**
	 * @param $subquery
	 *
	 * @return string|Zend_Search_Lucene_Search_Query
	 * @throws Exception
	 * @throws Zend_Search_Lucene_Exception
	 */
	public function rewriteSubquery( $subquery ) {
		switch ( get_class( $subquery ) ) {
			case 'Zend_Search_Lucene_Search_Query_Range':
				/** @var $subquery Zend_Search_Lucene_Search_Query_Range */
				$lower_term = caRewriteElasticSearchTermFieldSpec( $subquery->getLowerTerm() );
				$lower_fld = $this->getFieldTypeForTerm( $lower_term );
				$upper_term = caRewriteElasticSearchTermFieldSpec( $subquery->getUpperTerm() );
				$upper_fld = $this->getFieldTypeForTerm( $upper_term );

				$new_subquery = null;

				if ( $lower_fld instanceof FieldTypes\Geocode ) {
					$this->additional_filters[]
						= [ 'geo_shape' => $lower_fld->getFilterForRangeQuery( $lower_term, $upper_term ) ];
				} else {
					$lower_rewritten_term = $lower_fld->getRewrittenTerm( $lower_term );
					$upper_rewritten_term = $upper_fld->getRewrittenTerm( $upper_term );

					if ( $lower_rewritten_term && $upper_rewritten_term ) {
						$new_subquery = new Zend_Search_Lucene_Search_Query_Range(
							$lower_fld->getRewrittenTerm( $lower_term ),
							$upper_fld->getRewrittenTerm( $upper_term ),
							$subquery->isInclusive()
						);
					}
				}

				return $this->getSubqueryWithAdditionalTerms( $new_subquery, $lower_fld, $lower_term );
			case 'Zend_Search_Lucene_Search_Query_Term':
				/** @var $subquery Zend_Search_Lucene_Search_Query_Term */
				$term = caRewriteElasticSearchTermFieldSpec( $subquery->getTerm() );
				$fld = $this->getFieldTypeForTerm( $term );

				$new_subquery = null;
				if ( ( $fld instanceof FieldTypes\DateRange ) || ( $fld instanceof FieldTypes\Timestamp ) ) {
					$new_subquery = null;
					foreach ( $fld->getFiltersForTerm( $term ) as $filter ) {
						$this->additional_filters[] = $filter;
					}
					break;
				} else {
					if ( $rewritten_term = $fld->getRewrittenTerm( $term ) ) {
						$new_subquery
							= new Zend_Search_Lucene_Search_Query_Term( $fld->getRewrittenTerm( $term ) );
					}
				}

				return $this->getSubqueryWithAdditionalTerms( $new_subquery, $fld, $term );
			case 'Zend_Search_Lucene_Search_Query_Phrase':
				/** @var $subquery Zend_Search_Lucene_Search_Query_Phrase */
				$new_subquery = new Zend_Search_Lucene_Search_Query_Phrase();

				$fields_in_subquery = array();
				$terms = $subquery->getTerms();
				foreach ( $terms as $term ) {
					$fields_in_subquery[] = $term->field;
				}

				$multiterm_all_terms_same_field = ( sizeof( array_unique( $fields_in_subquery ) ) < 2 )
					&& ( sizeof( $terms ) > 1 );

				// below we convert stuff multi term phrase query stuff like
				// 		ca_objects.dimensions_width:"30 cm",
				// which is parsed as two terms ... "30", and "cm" to one relatively simple term query
				if ( $multiterm_all_terms_same_field && ( $first_term = array_shift( $terms ) ) ) {
					$first_term = caRewriteElasticSearchTermFieldSpec( $first_term );
					$fld = $this->getFieldTypeForTerm( $first_term );
					if ( ( $fld instanceof FieldTypes\Length ) || ( $fld instanceof FieldTypes\Weight )
						|| ( $fld instanceof FieldTypes\Currency )
					) {
						$acc = '';
						foreach ( $terms as $t ) {
							$acc .= $t->text;
						}
						$term = new Zend_Search_Lucene_Index_Term( $acc, $first_term->field );
						$rewritten_term = $fld->getRewrittenTerm( $term );

						// sometimes, through the magic of advanced search forms, range queries like
						//		ca_objects.dimensions_length:"25cm - 30 cm"
						// end up here. so we make them "real" range queries below
						if ( $this->isDisguisedRangeQuery( $rewritten_term ) ) {
							return $this->getSubqueryWithAdditionalTerms(
								$this->rewriteIndexTermAsRangeQuery( $rewritten_term, $fld ),
								$fld, $term
							);
						}

						$new_subquery->addTerm( $rewritten_term );

						return $this->getSubqueryWithAdditionalTerms( $new_subquery, $fld, $term );
					}
				}

				// "normal" phrase rewriting below
				foreach ( $terms as $term ) {
					$term = caRewriteElasticSearchTermFieldSpec( $term );
					$fld = $this->getFieldTypeForTerm( $term );

					if ( $fld instanceof FieldTypes\Geocode ) {
						$new_subquery = null;
						$this->additional_filters[]
							= [ 'geo_shape' => $fld->getFilterForPhraseQuery( $subquery ) ];
						break;
					} elseif ( ( $fld instanceof FieldTypes\DateRange )
						|| ( $fld instanceof FieldTypes\Timestamp )
					) {
						$new_subquery = null;

						foreach ( $fld->getFiltersForPhraseQuery( $subquery ) as $filter ) {
							$this->additional_filters[] = $filter;
						}
						break;
					} else {
						if ( $rewritten_term = $fld->getRewrittenTerm( $term ) ) {
							if ( $multiterm_all_terms_same_field ) {
								$rewritten_term->text = preg_replace( "/\"(.+)\"/u", "$1", $rewritten_term->text );
							}
							$new_subquery->addTerm( $rewritten_term );
						}
					}
				}

				return $this->getSubqueryWithAdditionalTerms( $new_subquery, $fld, $term );
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				/** @var @o_subquery \Zend_Search_Lucene_Search_Query_MultiTerm */
				$terms = $subquery->getTerms();

				$new_terms = array();
				foreach ( $terms as $term ) {
					$term = caRewriteElasticSearchTermFieldSpec( $term );
					$fld = $this->getFieldTypeForTerm( $term );
					$new_terms[] = $fld->getRewrittenTerm( $term );
				}

				$new_subquery = new Zend_Search_Lucene_Search_Query_MultiTerm( $new_terms,
					$subquery->getSigns() );

				return $this->getSubqueryWithAdditionalTerms( $new_subquery, $fld, $term );
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				/** @var $subquery Zend_Search_Lucene_Search_Query_Boolean */
				$new_subqueries = array();
				foreach ( $subquery->getSubqueries() as $subsubquery ) {
					$new_subqueries[] = $this->rewriteSubquery( $subsubquery );
				}
				$new_subquery = new Zend_Search_Lucene_Search_Query_Boolean( $new_subqueries,
					$subquery->getSigns() );

				return $new_subquery;
			default:
				throw new Exception( 'Encountered unknown Zend subquery type in Elastic8\Query: '
					. get_class( $subquery ) );
				break;
		}
	}

	/**
	 * @param Zend_Search_Lucene_Search_Query $original_subquery
	 * @param FieldTypes\FieldType $fld
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return Zend_Search_Lucene_Search_Query
	 */
	protected function getSubqueryWithAdditionalTerms( $original_subquery, $fld, $term ) {
		if ( ( $additional_terms = $fld->getAdditionalTerms( $term ) ) && is_array( $additional_terms ) ) {

			// we cant use the index terms as is; have to construct term queries
			$additional_term_queries = array();
			if ( $original_subquery ) {
				$additional_term_queries[] = $original_subquery;
			}
			foreach ( $additional_terms as $additional_term ) {
				$additional_term_queries[] = new Zend_Search_Lucene_Search_Query_Term( $additional_term );
			}

			return join( ' AND ', $additional_term_queries );
		} else {
			return $original_subquery;
		}
	}

	/**
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return FieldType
	 */
	protected function getFieldTypeForTerm( $term ) {
		$parts = preg_split( "!(\\\)?/!", $term->field );
		$table = $parts[0];
		$fld = array_pop( $parts );

		return FieldTypes\FieldType::getInstance( $table, $fld );
	}

	protected function getFilterQuery() {
		$terms = array();
		foreach ( $this->getFilters() as $filter ) {
			$filter['field'] = str_replace( '.', '\/', $filter['field'] );
			switch ( $filter['operator'] ) {
				case '=':
					$terms[] = $filter['field'] . ':' . $filter['value'];
					break;
				case '<':
					$terms[] = $filter['field'] . ':{-' . pow( 2, 32 ) . ' TO ' . $filter['value'] . '}';
					break;
				case '<=':
					$terms[] = $filter['field'] . ':[' . pow( 2, 32 ) . ' TO ' . $filter['value'] . ']';
					break;
				case '>':
					$terms[] = $filter['field'] . ':{' . $filter['value'] . ' TO ' . pow( 2, 32 ) . '}';
					break;
				case '>=':
					$terms[] = $filter['field'] . ':[' . $filter['value'] . ' TO ' . pow( 2, 32 ) . ']';
					break;
				case '<>':
					$terms[] = 'NOT ' . $filter['field'] . ':' . $filter['value'];
					break;
				case '-':
					$tmp = explode( ',', $filter['value'] );
					$terms[] = $filter['field'] . ':[' . $tmp[0] . ' TO ' . $tmp[1] . ']';
					break;
				case 'in':
					$tmp = explode( ',', $filter['value'] );
					$list = array();
					foreach ( $tmp as $item ) {
						// this case specifically happens when filtering list item search results by type id
						// (if type based access control is enabled, that is). The filter is something like
						// type_id IN 2,3,4,NULL. So we have to allow for empty values, which is a little bit
						// different in ElasticSearch.
						if ( strtolower( $item ) == 'null' ) {
							$list[] = '_missing_:' . $filter['field'];
						} else {
							$list[] = $filter['field'] . ':' . $item;
						}

					}

					$terms[] = '(' . join( ' OR ', $list ) . ')';
					break;
				default:
				case 'is':
				case 'is not':
					// noop
					break;
			}
		}

		return join( ' AND ', $terms );
	}

	/**
	 * Is this index term a disguised range search? If so,
	 * we can rewrite it as actual ranged search
	 *
	 * Note: this is only for Length, Weight, Currency ...
	 *
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return bool
	 */
	protected function isDisguisedRangeQuery( $term ) {
		return (bool) preg_match( "/[0-9]+.*to[\s]*[0-9]+/u", $term->text );
	}

	/**
	 * Rewrite index term as range query
	 *
	 * @param Zend_Search_Lucene_Index_Term $term
	 * @param FieldType $fld
	 *
	 * @return Zend_Search_Lucene_Search_Query_Range
	 * @throws Exception
	 */
	protected function rewriteIndexTermAsRangeQuery( $term, $fld ) {
		$lower_term = $upper_term = null;

		if ( preg_match( "/^(.+)to/u", $term->text, $matches ) ) {
			$lower_term = trim( $matches[1] );
		}

		if ( preg_match( "/to(.+)$/u", $term->text, $matches ) ) {
			$upper_term = trim( $matches[1] );
		}

		if ( ! $lower_term || ! $upper_term ) {
			throw new Exception( 'Could not parse index term as range query' );
		}

		$int_lower_term = new Zend_Search_Lucene_Index_Term( $lower_term, $term->field );
		$int_upper_term = new Zend_Search_Lucene_Index_Term( $upper_term, $term->field );

		$lower_term = $fld->getRewrittenTerm( $int_lower_term );
		$upper_term = $fld->getRewrittenTerm( $int_upper_term );

		return new Zend_Search_Lucene_Search_Query_Range( $lower_term, $upper_term, true );
	}
}
