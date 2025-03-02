<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/ElasticSearch/FieldTypes/Geocode.php :
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

namespace ElasticSearch\FieldTypes;

require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch/FieldTypes/GenericElement.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/GeocodeAttributeValue.php');

class Geocode extends GenericElement {
	public function __construct($ps_table_name, $ps_element_code) {
		parent::__construct($ps_table_name, $ps_element_code);
	}

	public function getIndexingFragment($pm_content, $pa_options) {
		if (is_array($pm_content)) { $pm_content = serialize($pm_content); }
		if ($pm_content == '') { return parent::getIndexingFragment($pm_content, $pa_options); }
		$va_return = array();

		$o_geocode_parser = new \GeocodeAttributeValue();

		$va_return[$this->getTableName().'/'.$this->getElementCode().'_text'] = $pm_content;

		//@see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-geo-shape-type.html
		if ($va_coords = $o_geocode_parser->parseValue($pm_content, array())) {
			// Features and points within features are delimited by : and ; respectively. We have to break those apart first.
			if (isset($va_coords['value_longtext2']) && $va_coords['value_longtext2']) {
				$va_points = preg_split("[\:\;]", $va_coords['value_longtext2']);
				// fun fact: ElasticSearch expects GeoJSON -- which has pairs of longitude, latitude.
				// google maps and others usually return latitude, longitude, which is also what we store
				if(sizeof($va_points) == 1) {
					$va_tmp = explode(',', $va_points[0]);
					$va_return[$this->getTableName().'/'.$this->getElementCode()] = array(
						'type' => 'point',
						'coordinates' => array((float)$va_tmp[1],(float)$va_tmp[0])
					);
				} elseif(sizeof($va_points) > 1) {
					// @todo might want to index as multipolygon to break apart features?
					$va_coordinates_for_es = array();
					foreach($va_points as $vs_point) {
						$va_tmp = explode(',', $vs_point);
						$va_coordinates_for_es[] = array((float)$va_tmp[1],(float)$va_tmp[0]);
					}

					$va_return[$this->getTableName().'/'.$this->getElementCode()] = array(
						'type' => 'polygon',
						'coordinates' => $va_coordinates_for_es
					);
				}
			}
		}

		return $va_return;
	}

	/**
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return \Zend_Search_Lucene_Index_Term
	 */
	public function getRewrittenTerm($po_term) {
		$va_tmp = explode('\\/', $po_term->field);
		if(sizeof($va_tmp) == 3) {
			unset($va_tmp[1]);
			$po_term = new \Zend_Search_Lucene_Index_Term(
				$po_term->text, join('\\/', $va_tmp)
			);
		}

		if(strtolower($po_term->text) === '[blank]') {
			return new \Zend_Search_Lucene_Index_Term(
				$po_term->field, '_missing_'
			);
		} elseif(strtolower($po_term->text) === '[set]') {
			return new \Zend_Search_Lucene_Index_Term(
				$po_term->field, '_exists_'
			);
		}

		// so yeah, it's impossible to query geo_shape fields in a query string in ElasticSearch. You *have to* use filters
		return null;
	}

	public function getFilterForRangeQuery($o_lower_term, $o_upper_term) {
		$va_return = array();

		$va_lower_coords = explode(',', $o_lower_term->text);
		$va_upper_coords = explode(',', $o_upper_term->text);

		$va_return[str_replace('\\', '', $o_lower_term->field)] = array(
			'shape' => array(
				'type' => 'envelope',
				'coordinates' => array(
					array((float) $va_lower_coords[1], (float) $va_lower_coords[0]),
					array((float) $va_upper_coords[1], (float) $va_upper_coords[0]),
				)
			)
		);
		return $va_return;
	}

	/**
	 * @param \Zend_Search_Lucene_Search_Query_Phrase $o_subquery
	 * @return mixed
	 */
	public function getFilterForPhraseQuery($o_subquery) {
		$va_terms = array();
		foreach($o_subquery->getQueryTerms() as $o_term) {
			$o_term = caRewriteElasticSearchTermFieldSpec($o_term);
			$va_terms[] = $o_term->text;
		}

		$va_parsed_search = caParseGISSearch(join(' ', $va_terms));

		$va_return[str_replace('\\', '', $o_term->field)] = array(
			'shape' => array(
				'type' => 'envelope',
				'coordinates' => array(
					array((float) $va_parsed_search['min_longitude'], (float) $va_parsed_search['min_latitude']),
					array((float) $va_parsed_search['max_longitude'], (float) $va_parsed_search['max_latitude']),
				)
			)
		);
		return $va_return;
	}
}
