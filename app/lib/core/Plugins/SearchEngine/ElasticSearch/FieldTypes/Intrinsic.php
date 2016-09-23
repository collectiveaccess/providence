<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Intrinsic.php :
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

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/FieldType.php');

class Intrinsic extends FieldType {

	/**
	 * Table name
	 * @var string
	 */
	protected $ops_table_name;
	/**
	 * Field name
	 * @var string
	 */
	protected $ops_field_name;

	/**
	 * Intrinsic constructor.
	 * @param string $ops_table_name
	 * @param string $ops_field_name
	 */
	public function __construct($ops_table_name, $ops_field_name) {
		$this->ops_table_name = $ops_table_name;
		$this->ops_field_name = $ops_field_name;
	}

	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->ops_table_name;
	}

	/**
	 * @param string $ops_table_name
	 */
	public function setTableName($ops_table_name) {
		$this->ops_table_name = $ops_table_name;
	}

	/**
	 * @return string
	 */
	public function getFieldName() {
		return $this->ops_field_name;
	}

	/**
	 * @param string $ops_field_name
	 */
	public function setFieldName($ops_field_name) {
		$this->ops_field_name = $ops_field_name;
	}

	/**
	 * @param mixed $pm_content
	 * @param array $pa_options
	 * @return array
	 */
	public function getIndexingFragment($pm_content, $pa_options) {
		if(is_array($pm_content)) { $pm_content = serialize($pm_content); }
		if($pm_content == '') { $pm_content = null; }

		$t_instance = \Datamodel::load()->getInstance($this->getTableName(), true);
		$va_field_info = \Datamodel::load()->getFieldInfo($this->getTableName(), $this->getFieldName());

		switch($va_field_info['FIELD_TYPE']) {
			case (FT_BIT):
				$pm_content = (bool) $pm_content;
				break;
			case (FT_NUMBER):
			case (FT_TIME):
			case (FT_TIMERANGE):
			case (FT_TIMECODE):
				if (!isset($va_field_info['LIST_CODE'])) {
					$pm_content = (float) $pm_content;
				}
				break;
			default:
				// noop (pm_content is just pm_content)
				break;
		}

		$va_return = array(
			$this->getTableName() . '/' . $this->getFieldName() => $pm_content
		);

		if($t_instance->getProperty('ID_NUMBERING_ID_FIELD') == $this->getFieldName() || (is_array($pa_options) && in_array('INDEX_AS_IDNO', $pa_options))) {
			if (method_exists($t_instance, "getIDNoPlugInInstance") && ($o_idno = $t_instance->getIDNoPlugInInstance())) {
				$va_values = array_values($o_idno->getIndexValues($pm_content));
			} else {
				$va_values = explode(' ', $pm_content);
			}

			$va_return = array(
				$this->getTableName() . '/' . $this->getFieldName() => $va_values
			);
		}

		if($vn_rel_type_id = caGetOption('relationship_type_id', $pa_options)) {
			// we use slashes as table_name/field_name delimiter, so let's use something else for the relationship type code
			$va_return[
				$this->getTableName() . '/' . $this->getFieldName() . '|' . caGetRelationshipTypeCode($vn_rel_type_id)
			] = $pm_content;
		}

		return $va_return;
	}

	/**
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return \Zend_Search_Lucene_Index_Term
	 */
	public function getRewrittenTerm($po_term) {
		$t_instance = \Datamodel::load()->getInstance($this->getTableName(), true);

		$vs_raw_term = $po_term->text;
		if(mb_substr($vs_raw_term, -1) == '|') {
			$vs_raw_term = mb_substr($vs_raw_term, 0, mb_strlen($vs_raw_term) - 1);
		}

		$va_field_components = explode('/', $po_term->field);

		if((strtolower($vs_raw_term) === '[blank]')) {
			if($t_instance instanceof \BaseLabel) { // labels usually have actual [BLANK] values
				return new \Zend_Search_Lucene_Index_Term(
					'"'.$vs_raw_term.'"', $po_term->field
				);
			} else {
				return new \Zend_Search_Lucene_Index_Term(
					$po_term->field, '_missing_'
				);
			}
		} elseif(strtolower($vs_raw_term) === '[set]') {
			return new \Zend_Search_Lucene_Index_Term(
				$po_term->field, '_exists_'
			);
		} elseif(
			($t_instance instanceof \BaseModel) &&
			isset($va_field_components[1]) &&
			($t_instance->getProperty('ID_NUMBERING_ID_FIELD') == $va_field_components[1])
		) {
			if(stripos($vs_raw_term, '*') !== false) {
				return new \Zend_Search_Lucene_Index_Term(
					$vs_raw_term, $po_term->field
				);
			} else {
				return new \Zend_Search_Lucene_Index_Term(
					'"'.$vs_raw_term.'"', $po_term->field
				);
			}
		} else {
			return new \Zend_Search_Lucene_Index_Term(
				str_replace('/', '\\/', $vs_raw_term),
				$po_term->field
			);
		}
	}
}
