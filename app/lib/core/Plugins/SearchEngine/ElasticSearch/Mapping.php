<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/Mapping.php :
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

class Mapping {
	/**
	 * @var \Configuration
	 */
	protected $opo_indexing_conf;
	/**
	 * @var \SearchBase
	 */
	protected $opo_search_base;
	/**
	 * @var \Datamodel
	 */
	protected $opo_datamodel;

	/**
	 * @var \Db
	 */
	protected $opo_db;

	/**
	 * Mapping constructor.
	 */
	public function __construct() {
		$this->opo_datamodel = \Datamodel::load();
		$this->opo_indexing_conf = \Configuration::load(\Configuration::load()->get('search_indexing_config'));
		$this->opo_search_base = new \SearchBase();
		$this->opo_db = new \Db();
	}

	/**
	 * @return \Configuration
	 */
	protected function getIndexingConf() {
		return $this->opo_indexing_conf;
	}

	/**
	 * @return \SearchBase
	 */
	protected function getSearchBase() {
		return $this->opo_search_base;
	}

	/**
	 * @return \Datamodel
	 */
	protected function getDatamodel() {
		return $this->opo_datamodel;
	}

	/**
	 * @return \Db
	 */
	public function getDb() {
		return $this->opo_db;
	}

	/**
	 * Returns all tables that are supposed to be indexed
	 * @return array
	 */
	public function getTables() {
		return $this->getIndexingConf()->getAssocKeys();
	}

	/**
	 * Get indexing fields for a given table, keys/field_names rewritten as A[0-9]+ or I[0-9]+ format
	 * @param $ps_table
	 * @return array
	 */
	public function getFieldsToIndex($ps_table) {
		if(!$this->getDatamodel()->tableExists($ps_table)) { return array(); }
		$va_table_fields = $this->getSearchBase()->getFieldsToIndex($ps_table);
		if(!is_array($va_table_fields)) { return array(); }

		$va_rewritten_fields = array();
		foreach($va_table_fields as $vs_field_name => $va_field_options){
			if (preg_match('!^_ca_attribute_([\d]*)$!', $vs_field_name, $va_matches)) {
				$va_rewritten_fields['A'.$va_matches[1]] = $va_field_options;
			} else {
				$vn_i = $this->getDatamodel()->getFieldNum($ps_table, $vs_field_name);

				$va_rewritten_fields['I' . $vn_i] = $va_field_options;
			}
		}

		return $va_rewritten_fields;
	}

	public function getElementIDsForTable($ps_table) {
		$va_return = array();
		foreach($this->getFieldsToIndex($ps_table) as $vs_fld => $va_info) {
			if(preg_match("/^A([0-9]+)$/", $vs_fld, $va_matches)) {
				$va_return[] = intval($va_matches[1]);
			}
		}

		return array_unique($va_return);
	}

	public function getElementInfo($ps_table) {
		if(!$this->getDatamodel()->tableExists($ps_table)) { return array(); }
		$pn_table_num = $this->getDatamodel()->getTableNum($ps_table);

		$va_attributes = array();
		foreach($this->getElementIDsForTable($ps_table) as $vn_id) {
			$qr_type_restrictions = $this->getDb()->query('
				SELECT DISTINCT came.*
				FROM ca_metadata_type_restrictions camtr
				INNER JOIN ca_metadata_elements as came ON came.element_id = camtr.element_id
				WHERE camtr.table_num = ? AND came.element_id = ?
			',(int)$pn_table_num, $vn_id);

			while($qr_type_restrictions->nextRow()) {
				$vn_element_id = $qr_type_restrictions->get('element_id');

				$va_attributes[$vn_element_id] = array(
					'element_id' => $vn_element_id,
					'element_code' => $qr_type_restrictions->get('element_code'),
					'datatype' => $qr_type_restrictions->get('datatype')
				);
			}
		}

		return $va_attributes;
	}

	public function getConfigForElement($pn_element_id, $pa_element_info) {
		$va_element_opts = array(
			'properties' => array(
				'store' => false
			)
		);
		switch($pa_element_info['datatype']) {
			case 1: // text
			case 3:	// list
			case 5:	// url
			case 6: // currency
			case 8: // length
			case 9: // weight
			case 13: // LCSH
			case 14: // geonames
			case 15: // file
			case 16: // media
			case 19: // taxonomy
			case 20: // information service
				$va_element_opts['properties']['type'] = 'string';
				break;
			case 2:	// daterange
				$va_element_opts['properties']['type'] = 'date';
				$va_element_opts['properties']["format"] = 'dateOptionalTime';
				$va_element_opts['properties']["ignore_malformed"] = false;
				$va_table_fields['A'.$pn_element_id.'_text'] = array('properties' => array('type' => 'string'));
				break;
			case 4:	// geocode
				$va_element_opts['properties']['type'] = 'geo_point';
				$va_table_fields['A'.$pn_element_id.'_text'] = array('properties' => array('type' => 'string'));
				break;
			case 10:	// timecode
			case 12:	// numeric/float
				$va_element_opts['properties']['type'] = 'double';
				break;
			case 11:	// integer
				$va_element_opts['properties']['type'] = 'long';
				break;
			default:
				$va_element_opts['properties']['type'] = 'string';
				break;
		}
		return $va_element_opts;
	}

	public function getConfigForIntrinsic($ps_table, $pn_field_num, $pa_indexing_config) {
		$vs_field_name = $this->getDatamodel()->getFieldName($ps_table, $pn_field_num);
		if(!$vs_field_name) { return array(); }
		$t_instance = $this->getDatamodel()->getInstance($ps_table);

		$va_field_options = array(
			'properties' => array(
				'store' => false
			)
		);

		if($pa_indexing_config["BOOST"]){
			$va_field_options['properties']["boost"] = floatval($va_field_options["BOOST"]);
		}

		if(in_array("DONT_TOKENIZE",$va_field_options)){
			$va_field_options['analyzer'] = 'analyzer_keyword';
		}

		if (!isset($va_field_options['properties']['type']) && $t_instance->hasField($vs_field_name)) {
			switch($t_instance->getFieldInfo($vs_field_name, "FIELD_TYPE")){
				case (FT_TEXT):
				case (FT_MEDIA):
				case (FT_FILE):
				case (FT_PASSWORD):
				case (FT_VARS):
					$va_field_options['properties']['type'] = 'string';
					break;
				case (FT_NUMBER):
				case (FT_TIME):
				case (FT_TIMERANGE):
				case (FT_TIMECODE):
					if ($t_instance->getFieldInfo($vs_field_name, "LIST_CODE")) {	// list-based intrinsics get indexed with both item_id and label text
						$va_field_options['properties']['type'] = 'string';
					} else {
						$va_field_options['properties']['type'] = 'double';
					}
					break;
				case (FT_TIMESTAMP):
				case (FT_DATETIME):
				case (FT_HISTORIC_DATETIME):
				case (FT_DATE):
				case (FT_HISTORIC_DATE):
				case (FT_DATERANGE):
				case (FT_HISTORIC_DATERANGE):
					$va_field_options['properties']['type'] = 'date';
					break;
				case (FT_BIT):
					$va_field_options['properties']['type'] = 'boolean';
					break;
				default:
					$va_field_options['properties']['type'] = "string";
					break;
			}
		}
	}
}
