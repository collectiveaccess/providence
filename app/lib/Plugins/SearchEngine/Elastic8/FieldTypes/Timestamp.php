<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/FieldTypes/Timestamp.php :
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

namespace Elastic8\FieldTypes;

use Zend_Search_Lucene_Index_Term;
use Zend_Search_Lucene_Search_Query_Phrase;

require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/FieldType.php' );

class Timestamp extends FieldType {

	/**
	 * Field name
	 *
	 * @var string
	 */
	protected $field_name;

	/**
	 * Timestamp constructor.
	 *
	 * @param string $field_name
	 */
	public function __construct( $field_name ) {
		$this->field_name = $field_name;
	}

	/**
	 * @return string
	 */
	public function getFieldName() {
		return $this->field_name;
	}

	/**
	 * @param string $field_name
	 */
	public function setFieldName( $field_name ) {
		$this->field_name = $field_name;
	}

	/**
	 * @param mixed $content
	 * @param array $options
	 *
	 * @return array
	 */
	public function getIndexingFragment( $content, $options ) {
		if ( is_array( $content ) ) {
			$content = serialize( $content );
		}

		return [
			str_replace( '.', '/', $this->getFieldName() ) => $content
		];
	}

	/**
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return Zend_Search_Lucene_Index_Term
	 */
	public function getRewrittenTerm( $term ) {
		return $term;
	}

	/**
	 * @param Zend_Search_Lucene_Search_Query_Phrase $query
	 *
	 * @return array
	 */
	public function getFiltersForPhraseQuery( $query ) {
		$terms = $return = [];
		$fld = null;
		foreach ( $query->getQueryTerms() as $term ) {
			$term = caRewriteElasticSearchTermFieldSpec( $term );
			$fld = str_replace( '\\', '', $term->field );
			$terms[] = $term->text;
		}

		$parsed_values = caGetISODates( join( ' ', $terms ) );

		$return[] = [
			'range' => [
				$fld => [
					'lte' => $parsed_values['end'],
				]
			]
		];

		$return[] = [
			'range' => [
				$fld => [
					'gte' => $parsed_values['start'],
				]
			]
		];

		return $return;
	}

	/**
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return array
	 */
	function getFiltersForTerm( $term ) {
		$return = [];
		$parsed_values = caGetISODates( $term->text );
		$fld = str_replace( '\\', '', $term->field );

		$return[] = [
			'range' => [
				$fld => [
					'lte' => $parsed_values['end'],
				]
			]
		];

		$return[] = [
			'range' => [
				$fld => [
					'gte' => $parsed_values['start'],
				]
			]
		];

		return $return;
	}
}
