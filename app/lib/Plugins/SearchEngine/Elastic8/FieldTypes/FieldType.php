<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/FieldTypes/FieldType.php :
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

use ca_metadata_elements;
use Datamodel;
use Zend_Search_Lucene_Index_Term;

abstract class FieldType {

	abstract public function getIndexingFragment( $content, $options );

	abstract public function getRewrittenTerm( $term );

	/**
	 * Allows implementations to add additional terms to the query
	 *
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return bool
	 */
	public function getAdditionalTerms( $term ) {
		return false;
	}

	/**
	 * Allows implementations to add ElasticSearch query filters
	 *
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return bool
	 */
	public function getQueryFilters( $term ) {
		return false;
	}

	/**
	 * @param string $table
	 * @param string $content_fieldname
	 *
	 * @return FieldType
	 */
	public static function getInstance( $table, $content_fieldname ) {
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/DateRange.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Geocode.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Currency.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Length.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Weight.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Timecode.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Integer.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Numeric.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/GenericElement.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Intrinsic.php' );
		require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/Timestamp.php' );

		if ( $table == 'created' || $table == 'modified' ) {
			return new Timestamp( $table );
		}

		// if this is an indexing field name, rewrite it
		$could_be_attribute = true;
		if ( preg_match( "/^(I|A)[0-9]+$/", $content_fieldname ) ) {

			if ( $content_fieldname[0] === 'A' ) { // Metadata attribute
				$field_num_proc = (int) substr( $content_fieldname, 1 );
				$content_fieldname = ca_metadata_elements::getElementCodeForId( $field_num_proc );
				if ( ! $content_fieldname ) {
					return null;
				}
			} else {
				// Plain intrinsic
				$could_be_attribute = false;
				$field_num_proc = (int) substr( $content_fieldname, 1 );
				$content_fieldname = Datamodel::getFieldName( $table, $field_num_proc );
			}

		}

		if ( $content_fieldname && $could_be_attribute ) {
			$tmp = explode( '/', $content_fieldname );
			$content_fieldname = array_pop( $tmp );
			if ( $datatype = ca_metadata_elements::getElementDatatype( $content_fieldname ) ) {
				switch ( $datatype ) {
					case __CA_ATTRIBUTE_VALUE_DATERANGE__:
						return new DateRange( $table, $content_fieldname );
					case __CA_ATTRIBUTE_VALUE_GEOCODE__:
						return new Geocode( $table, $content_fieldname );
					case __CA_ATTRIBUTE_VALUE_CURRENCY__:
						return new Currency( $table, $content_fieldname );
					case __CA_ATTRIBUTE_VALUE_LENGTH__:
						return new Length( $table, $content_fieldname );
					case __CA_ATTRIBUTE_VALUE_WEIGHT__:
						return new Weight( $table, $content_fieldname );
					case __CA_ATTRIBUTE_VALUE_TIMECODE__:
						return new Timecode( $table, $content_fieldname );
					case __CA_ATTRIBUTE_VALUE_INTEGER__:
						return new Integer( $table, $content_fieldname );
					case __CA_ATTRIBUTE_VALUE_NUMERIC__:
						return new Numeric( $table, $content_fieldname );
					default:
						return new GenericElement( $table, $content_fieldname );
				}
			} else {
				return new Intrinsic( $table, $content_fieldname );
			}
		} else {
			return new Intrinsic( $table, $content_fieldname );
		}
	}

}
