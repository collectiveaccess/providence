<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/FieldTypes/Currency.php :
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

use CurrencyAttributeValue;
use Zend_Search_Lucene_Index_Term;

require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/GenericElement.php' );

class Currency extends GenericElement {

	public function __construct( $table_name, $element_code ) {
		parent::__construct( $table_name, $element_code );
	}

	public function getIndexingFragment( $content, $options ) {
		if ( is_array( $content ) ) {
			$content = serialize( $content );
		}
		if ( $content == '' ) {
			return parent::getIndexingFragment( $content, $options );
		}

		// we index currencys as float number and the 3-char currency code in a separate text field
		$curr = new CurrencyAttributeValue();
		$parsed_currency = $curr->parseValue( $content, [] );

		if ( is_array( $parsed_currency ) && isset( $parsed_currency['value_decimal1'] ) ) {
			return [
				$this->getTableName() . '/' . $this->getElementCode() => $parsed_currency['value_decimal1'],
				$this->getTableName() . '/' . $this->getElementCode()
				. '_currency' => $parsed_currency['value_longtext1'],
			];
		} else {
			return parent::getIndexingFragment( $content, $options );
		}
	}

	/**
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return Zend_Search_Lucene_Index_Term
	 */
	public function getRewrittenTerm( $term ) {
		$tmp = explode( '\\/', $term->field );
		if ( sizeof( $tmp ) == 3 ) {
			unset( $tmp[1] );
			$term = new Zend_Search_Lucene_Index_Term(
				$term->text, join( '\\/', $tmp )
			);
		}

		if ( strtolower( $term->text ) === '[blank]' ) {
			return new Zend_Search_Lucene_Index_Term(
				$term->field, '_missing_'
			);
		} elseif ( strtolower( $term->text ) === '[set]' ) {
			return new Zend_Search_Lucene_Index_Term(
				$term->field, '_exists_'
			);
		}

		$curr = new CurrencyAttributeValue();
		$parsed_currency = $curr->parseValue( $term->text, [] );

		if ( is_array( $parsed_currency ) && isset( $parsed_currency['value_decimal1'] ) ) {
			return new Zend_Search_Lucene_Index_Term(
				$parsed_currency['value_decimal1'],
				$term->field
			);
		} else {
			return $term;
		}
	}

	public function getAdditionalTerms( $term ) {
		$curr = new CurrencyAttributeValue();
		$parsed_currency = $curr->parseValue( $term->text, [] );

		if ( is_array( $parsed_currency ) && isset( $parsed_currency['value_longtext1'] ) ) {
			return [
				new Zend_Search_Lucene_Index_Term(
					$parsed_currency['value_longtext1'],
					$this->getTableName() . '\\/' . $this->getElementCode() . '_currency'
				)
			];
		} else {
			return false;
		}
	}
}
