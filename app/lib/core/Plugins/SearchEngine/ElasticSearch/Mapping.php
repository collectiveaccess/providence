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
	protected $opo_search_conf;
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
	 * Element info array
	 * @var array
	 */
	protected $opa_element_info;

	/**
	 * Mapping constructor.
	 */
	public function __construct() {
		// set up basic properties
		$this->opo_datamodel = \Datamodel::load();
		$this->opo_search_conf = \Configuration::load(\Configuration::load()->get('search_config'));
		$this->opo_indexing_conf = \Configuration::load($this->opo_search_conf->get('search_indexing_config'));
		$this->opo_db = new \Db();
		$this->opo_search_base = new \SearchBase($this->opo_db, null, false);

		$this->opa_element_info = array();
		foreach($this->getTables() as $vs_table) {
			$this->prefetchElementInfo($vs_table);
		}
	}

	/**
	 * Check if the ElasticSearch mapping needs refreshing
	 * @return bool
	 */
	public function needsRefresh() {
		return !\ExternalCache::contains('LastPing', 'ElasticSearchMapping');
	}

	/**
	 * Ping the ElasticSearch mapping, effectively resetting the refresh time
	 */
	public function ping() {
		\ExternalCache::save('LastPing', 'meow', 'ElasticSearchMapping');
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
	 * Get indexing fields and options for a given table (and its related tables)
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
				$va_rewritten_fields[$ps_table.'.A'.$va_matches[1]] = $va_field_options;
			} else {
				$vn_i = $this->getDatamodel()->getFieldNum($ps_table, $vs_field_name);

				$va_rewritten_fields[$ps_table.'.I' . $vn_i] = $va_field_options;
			}
		}

		$va_related_tables = $this->getSearchBase()->getRelatedIndexingTables($ps_table);
		foreach($va_related_tables as $vs_related_table) {
			$va_related_table_fields = $this->getSearchBase()->getFieldsToIndex($ps_table, $vs_related_table);
			foreach($va_related_table_fields as $vs_related_table_field => $va_related_table_field_options){
				if (preg_match('!^_ca_attribute_([\d]*)$!', $vs_related_table_field, $va_matches)) {
					$va_rewritten_fields[$vs_related_table.'.A'.$va_matches[1]] = $va_related_table_field_options;
				} else {
					$vn_i = $this->getDatamodel()->getFieldNum($vs_related_table, $vs_related_table_field);

					$va_rewritten_fields[$vs_related_table.'.I' . $vn_i] = $va_related_table_field_options;
				}
			}
		}

		return $va_rewritten_fields;
	}

	/**
	 * Get all applicable element ids for a given table
	 * @param string $ps_table
	 * @return array
	 */
	public function getElementIDsForTable($ps_table) {
		if(!$this->getDatamodel()->tableExists($ps_table)) { return array(); }
		$va_table_fields = $this->getSearchBase()->getFieldsToIndex($ps_table);
		if(!is_array($va_table_fields)) { return array(); }

		$va_return = array();
		foreach($va_table_fields as $vs_fld => $va_info) {
			if (preg_match('!^_ca_attribute_([\d]*)$!', $vs_fld, $va_matches)) {
				$va_return[] = intval($va_matches[1]);
			}
		}

		return array_unique($va_return);
	}

	/**
	 * Prefetch element info for given table. This is more efficient than running a db query every
	 * time @see Mapping::getElementInfo() is called. Also @see $opa_element_info.
	 * @param string $ps_table
	 */
	protected function prefetchElementInfo($ps_table) {
		if(isset($this->opa_element_info[$ps_table]) && is_array($this->opa_element_info[$ps_table])) { return; }
		if(!$this->getDatamodel()->tableExists($ps_table)) { return; }
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

		$this->opa_element_info = array_merge($this->opa_element_info, $va_attributes);
	}

	/**
	 * Get info for given element id. Keys in the result array are:
	 * 		element_id
	 * 		element_code
	 * 		datatype
	 * @param int $pn_element_id
	 * @return array|bool
	 */
	public function getElementInfo($pn_element_id) {
		if(isset($this->opa_element_info[$pn_element_id])) {
			return $this->opa_element_info[$pn_element_id];
		}

		return false;
	}

	/**
	 * Get ElasticSearch property config fragment for a given element_id
	 *
	 * @todo: We should respect settings in the indexing config here. Right now they're ignored.
	 * @todo: The default cfg doesn't have any element-level indexing settings but sometimes they can come in handy
	 *
	 * @param string $ps_table
	 * @param int $pn_element_id
	 * @param array $pa_element_info @see Mapping::getElementInfo()
	 * @return array
	 */
	public function getConfigForElement($ps_table, $pn_element_id, $pa_element_info) {
		if(!is_numeric($pn_element_id) && (intval($pn_element_id) > 0)) { return array(); }
		$va_element_info = $this->getElementInfo($pn_element_id);
		$vs_element_code = $va_element_info['element_code'];
		if(!$vs_element_code) { return array(); }

		// init: we never store -- all SearchResult::get() operations are now done on our database tables
		$va_element_config = array(
			$ps_table.'.'.$vs_element_code => array(
				'store' => false
			)
		);

		// @todo break this out into separate classes in the ElasticSearch\FieldTypes namespace!?

		switch($pa_element_info['datatype']) {
			case 2:	// daterange
				$va_element_config[$ps_table.'.'.$vs_element_code]['type'] = 'date';
				$va_element_config[$ps_table.'.'.$vs_element_code]['format'] = 'dateOptionalTime';
				$va_element_config[$ps_table.'.'.$vs_element_code]['ignore_malformed'] = false;
				$va_element_config[$ps_table.'.'.$vs_element_code.'_text'] = array('type' => 'string', 'store' => false);
				break;
			case 4:	// geocode
				$va_element_config[$ps_table.'.'.$vs_element_code]['type'] = 'geo_point';
				$va_element_config[$ps_table.'.'.$vs_element_code.'_text'] = array('type' => 'string', 'store' => false);
				break;
			case 6: // currency
			case 8: // length
				// we may want to do range searches on those!? -> index as double and store unit separately
				$va_element_config[$ps_table.'.'.$vs_element_code]['type'] = 'double';
				$va_element_config[$ps_table.'.'.$vs_element_code.'_text'] = array('type' => 'string', 'store' => false);
				break;
			case 10:	// timecode
			case 12:	// numeric/float
				$va_element_config[$ps_table.'.'.$vs_element_code]['type'] = 'double';
				break;
			case 11:	// integer
				$va_element_config[$ps_table.'.'.$vs_element_code]['type'] = 'long';
				break;
			case 1: // text
			case 3:	// list
			case 5:	// url
			case 9: // weight
			case 13: // LCSH
			case 14: // geonames
			case 15: // file
			case 16: // media
			case 19: // taxonomy
			case 20: // information service
			case 21: // object representations
			case 22: // entities
			case 23: // places
			case 24: // occurrences
			case 25: // collections
			case 26: // storage locations
			case 27: // loans
			case 28: // movements
			case 29: // objects
			case 30: // object lots
			default:
				$va_element_config[$ps_table.'.'.$vs_element_code]['type'] = 'string';
				break;
		}
		return $va_element_config;
	}

	/**
	 * Get ElasticSearch property config fragment for a given intrinsic field
	 * @param string $ps_table
	 * @param int $pn_field_num
	 * @param array $pa_indexing_config
	 * @return array
	 */
	public function getConfigForIntrinsic($ps_table, $pn_field_num, $pa_indexing_config) {
		$vs_field_name = $this->getDatamodel()->getFieldName($ps_table, $pn_field_num);
		if(!$vs_field_name) { return array(); }
		$t_instance = $this->getDatamodel()->getInstance($ps_table);

		$va_field_options = array(
			$ps_table.'.'.$vs_field_name => array(
				'store' => false
			)
		);

		if($pa_indexing_config['BOOST']){
			$va_field_options[$ps_table.'.'.$vs_field_name]['boost'] = floatval($pa_indexing_config['BOOST']);
		}

		if(in_array('DONT_TOKENIZE',$pa_indexing_config)){
			$va_field_options[$ps_table.'.'.$vs_field_name]['analyzer'] = 'analyzer_keyword';
		}

		switch($t_instance->getFieldInfo($vs_field_name, 'FIELD_TYPE')){
			case (FT_TEXT):
			case (FT_MEDIA):
			case (FT_FILE):
			case (FT_PASSWORD):
			case (FT_VARS):
				$va_field_options[$ps_table.'.'.$vs_field_name]['type'] = 'string';
				break;
			case (FT_NUMBER):
			case (FT_TIME):
			case (FT_TIMERANGE):
			case (FT_TIMECODE):
				if ($t_instance->getFieldInfo($vs_field_name, 'LIST_CODE')) {	// list-based intrinsics get indexed with both item_id and label text
					$va_field_options[$ps_table.'.'.$vs_field_name]['type'] = 'string';
				} else {
					$va_field_options[$ps_table.'.'.$vs_field_name]['type'] = 'double';
				}
				break;
			case (FT_TIMESTAMP):
			case (FT_DATETIME):
			case (FT_HISTORIC_DATETIME):
			case (FT_DATE):
			case (FT_HISTORIC_DATE):
			case (FT_DATERANGE):
			case (FT_HISTORIC_DATERANGE):
				$va_field_options[$ps_table.'.'.$vs_field_name]['type'] = 'date';
				$va_field_options[$ps_table.'.'.$vs_field_name]['format'] = 'dateOptionalTime';
				$va_field_options[$ps_table.'.'.$vs_field_name]['ignore_malformed'] = false;
				break;
			case (FT_BIT):
				$va_field_options[$ps_table.'.'.$vs_field_name]['type'] = 'boolean';
				break;
			default:
				$va_field_options[$ps_table.'.'.$vs_field_name]['type'] = 'string';
				break;
		}

		return $va_field_options;
	}

	/**
	 * Get the mapping in the array format the Elasticsearch PHP API expects
	 * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_index_management_operations.html#_put_mappings_api
	 * @return array
	 */
	public function get() {
		$va_mapping_config = array();

		foreach($this->getTables() as $vs_table) {
			$va_mapping_config[$vs_table]['_source']['enabled'] = false;
			$va_mapping_config[$vs_table]['properties'] = array();

			foreach($this->getFieldsToIndex($vs_table) as $vs_field => $va_indexing_info) {
				if(preg_match("/^(ca[\_a-z]+)\.A([0-9]+)$/", $vs_field, $va_matches)) { // attribute
					$va_mapping_config[$vs_table]['properties'] =
						array_merge(
							$va_mapping_config[$vs_table]['properties'],
							$this->getConfigForElement(
								$va_matches[1],
								(int)$va_matches[2],
								$this->getElementInfo((int)$va_matches[2])
							)
						);
				} elseif(preg_match("/^(ca[\_a-z]+)\.I([0-9]+)$/", $vs_field, $va_matches)) { // intrinsic
					$va_mapping_config[$vs_table]['properties'] =
						array_merge(
							$va_mapping_config[$vs_table]['properties'],
							$this->getConfigForIntrinsic(
								$va_matches[1],
								(int) $va_matches[2],
								$va_indexing_info
							)
						);
				}
			}

			// add config for modified and created, which are always indexed
			$va_mapping_config[$vs_table]['properties']["{$vs_table}.modified"] = array(
				'type' => 'date'
			);
			$va_mapping_config[$vs_table]['properties']["{$vs_table}.created"] = array(
				'type' => 'date'
			);
		}

		return $va_mapping_config;
	}
}
