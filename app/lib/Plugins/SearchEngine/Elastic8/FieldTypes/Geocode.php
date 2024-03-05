<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/FieldTypes/Geocode.php :
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

use GeocodeAttributeValue;
use Zend_Search_Lucene_Index_Term;
use Zend_Search_Lucene_Search_Query_Phrase;

require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/GenericElement.php' );
require_once( __CA_LIB_DIR__ . '/Attributes/Values/GeocodeAttributeValue.php' );

class Geocode extends GenericElement {
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
		$return = array();

		$geocode_parser = new GeocodeAttributeValue();

		$return[ $this->getTableName() . '/' . $this->getElementCode() . '_text' ] = $content;

		//@see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-geo-shape-type.html
		if ( $coords = $geocode_parser->parseValue( $content, array() ) ) {
			// Features and points within features are delimited by : and ; respectively. We have to break those apart first.
			if ( isset( $coords['value_longtext2'] ) && $coords['value_longtext2'] ) {
				$points = preg_split( "[\:\;]", $coords['value_longtext2'] );
				// fun fact: ElasticSearch expects GeoJSON -- which has pairs of longitude, latitude.
				// google maps and others usually return latitude, longitude, which is also what we store
				if ( sizeof( $points ) == 1 ) {
					$tmp = explode( ',', $points[0] );
					$return[ $this->getTableName() . '/' . $this->getElementCode() ] = array(
						'type' => 'point',
						'coordinates' => array( (float) $tmp[1], (float) $tmp[0] )
					);
				} elseif ( sizeof( $points ) > 1 ) {
					// @todo might want to index as multipolygon to break apart features?
					$coordinates_for_es = array();
					foreach ( $points as $point ) {
						$tmp = explode( ',', $point );
						$coordinates_for_es[] = array( (float) $tmp[1], (float) $tmp[0] );
					}

					$return[ $this->getTableName() . '/' . $this->getElementCode() ] = array(
						'type' => 'polygon',
						'coordinates' => $coordinates_for_es
					);
				}
			}
		}

		return $return;
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

		// so yeah, it's impossible to query geo_shape fields in a query string in ElasticSearch. You *have to* use filters
		return null;
	}

	public function getFilterForRangeQuery( $lower_term, $upper_term ) {
		$return = array();

		$lower_coords = explode( ',', $lower_term->text );
		$upper_coords = explode( ',', $upper_term->text );

		$return[ str_replace( '\\', '', $lower_term->field ) ] = array(
			'shape' => array(
				'type' => 'envelope',
				'coordinates' => array(
					array( (float) $lower_coords[1], (float) $lower_coords[0] ),
					array( (float) $upper_coords[1], (float) $upper_coords[0] ),
				)
			)
		);

		return $return;
	}

	/**
	 * @param Zend_Search_Lucene_Search_Query_Phrase $subquery
	 *
	 * @return mixed
	 */
	public function getFilterForPhraseQuery( $subquery ) {
		$terms = array();
		foreach ( $subquery->getQueryTerms() as $term ) {
			$term = caRewriteElasticSearchTermFieldSpec( $term );
			$terms[] = $term->text;
		}

		$parsed_search = caParseGISSearch( join( ' ', $terms ) );

		$return[ str_replace( '\\', '', $term->field ) ] = array(
			'shape' => array(
				'type' => 'envelope',
				'coordinates' => array(
					array( (float) $parsed_search['min_longitude'], (float) $parsed_search['min_latitude'] ),
					array( (float) $parsed_search['max_longitude'], (float) $parsed_search['max_latitude'] ),
				)
			)
		);

		return $return;
	}
}
