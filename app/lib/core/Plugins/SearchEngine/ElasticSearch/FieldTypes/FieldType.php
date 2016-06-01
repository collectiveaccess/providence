<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/FieldType.php :
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

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/DateRange.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Geocode.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Currency.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Length.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Weight.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Timecode.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Integer.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Numeric.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/GenericElement.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Intrinsic.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Timestamp.php');

abstract class FieldType {

	abstract public function getIndexingFragment($pm_content, $pa_options);
	abstract public function getRewrittenTerm($po_term);

	/**
	 * Allows implementations to add additional terms to the query
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return bool
	 */
	public function getAdditionalTerms($po_term) {
		return false;
	}

	/**
	 * Allows implementations to add ElasticSearch query filters
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return bool
	 */
	public function getQueryFilters($po_term) {
		return false;
	}

	/**
	 * @param string $ps_table
	 * @param string $ps_content_fieldname
	 * @return \ElasticSearch\FieldTypes\FieldType
	 */
	public static function getInstance($ps_table, $ps_content_fieldname) {
		if($ps_table == 'created' || $ps_table == 'modified') {
			return new Timestamp($ps_table);
		}

		// if this is an indexing field name, rewrite it
		$vb_could_be_attribute = true;
		if(preg_match("/^(I|A)[0-9]+$/", $ps_content_fieldname)) {

			if ($ps_content_fieldname[0] === 'A') { // Metadata attribute
				$vn_field_num_proc = (int)substr($ps_content_fieldname, 1);
				$ps_content_fieldname = \ca_metadata_elements::getElementCodeForId($vn_field_num_proc);
				if(!$ps_content_fieldname) { return null; }
			} else {
				// Plain intrinsic
				$vb_could_be_attribute = false;
				$vn_field_num_proc = (int)substr($ps_content_fieldname, 1);
				$ps_content_fieldname = \Datamodel::load()->getFieldName($ps_table, $vn_field_num_proc);
			}

		}

		if($ps_content_fieldname && $vb_could_be_attribute) {
			$va_tmp = explode('/', $ps_content_fieldname);
			$ps_content_fieldname = array_pop($va_tmp);
			if($vn_datatype = \ca_metadata_elements::getElementDatatype($ps_content_fieldname)) {
				switch ($vn_datatype) {
					case 2:
						return new DateRange($ps_table, $ps_content_fieldname);
					case 4:
						return new Geocode($ps_table, $ps_content_fieldname);
					case 6:
						return new Currency($ps_table, $ps_content_fieldname);
					case 8:
						return new Length($ps_table, $ps_content_fieldname);
					case 9:
						return new Weight($ps_table, $ps_content_fieldname);
					case 10:
						return new Timecode($ps_table, $ps_content_fieldname);
					case 11:
						return new Integer($ps_table, $ps_content_fieldname);
					case 12:
						return new Numeric($ps_table, $ps_content_fieldname);
					default:
						return new GenericElement($ps_table, $ps_content_fieldname);
				}
			} else {
				return new Intrinsic($ps_table, $ps_content_fieldname);
			}
		} else {
			return new Intrinsic($ps_table, $ps_content_fieldname);
		}
	}

}
