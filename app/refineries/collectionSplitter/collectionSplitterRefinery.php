<?php
/* ----------------------------------------------------------------------
 * collectionSplitterRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_LIB_DIR__.'/ca/Import/BaseRefinery.php');
 	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 
	class collectionSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		private $opb_returns_multiple_values = true;
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'collectionSplitter';
			$this->ops_title = _t('Collection splitter');
			$this->ops_description = _t('Provides several collection-related import functions: splitting of multiple collections in a string into individual values, mapping of type and relationship type for related collections, and merging collection data with names.');
			
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => true,
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options=null) {
			$this->opb_returns_multiple_values = true;
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			if (is_array($pm_value)) {
				$va_collections = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['collectionSplitter_delimiter']) {
					$va_collections = explode($vs_delimiter, $pm_value);
				} else {
					$va_collections = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			foreach($va_collections as $vn_i => $vs_collection) {
				if (!$vs_collection = trim($vs_collection)) { continue; }
				
				if($vs_terminal == 'name') {
					$this->opb_returns_multiple_values = false;
					return $vs_collection;
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return array(0 => array('name' => $vs_collection));	
				}
			
				// Set label
				$va_val = array('preferred_labels' => array('name' => $vs_collection));
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['collectionSplitter_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if (
					(!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) 
					&& 
					($vs_rel_type_opt = $pa_item['settings']['collectionSplitter_relationshipTypeDefault'])	
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarning(_t('[collectionSplitterRefinery] No relationship type is set for collection %1', $vs_collection));
				}
				
				// Set collection_type
				if (
					($vs_type_opt = $pa_item['settings']['collectionSplitter_collectionType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['collectionSplitter_collectionTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarning(_t('[collectionSplitterRefinery] No collection type is set for collection %1', $vs_collection));
				}
				
				// Set collection parents
				global $g_ui_locale_id;
				if ($va_parents = $pa_item['settings']['collectionSplitter_parents']) {
					if (!is_array($va_parents)) { $va_parents = array($va_parents); }
					$vn_collection_id = null;
						
					foreach($va_parents as $vn_i => $vs_parent) {
						$vn_collection_id = DataMigrationUtils::getCollectionID($vs_parent, $va_val['_type'], $g_ui_locale_id, array('idno' => $vs_parent, 'parent_id' => $vn_collection_id), $pa_options);
						
						if ($o_log) { $o_log->logDebug(_t('[collectionSplitterRefinery] Got parent %1 with collection_id %2 for %3', $vs_parent, $vn_collection_id, $vs_collection)); }
					}
					$va_val['parent_id'] = $vn_collection_id;
				}
			
				// Set attributes
				if (is_array($pa_item['settings']['collectionSplitter_attributes'])) {
					$va_attr_vals = array();
					foreach($pa_item['settings']['collectionSplitter_attributes'] as $vs_element_code => $va_attrs) {
						if(is_array($va_attrs)) {
							foreach($va_attrs as $vs_k => $vs_v) {
								// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
								// DataMigrationUtils::getCollectionID(), which ca_data_importers::importDataFromSource() uses to create related collections
								// only supports non-repeating attribute values, so we join any values here and call it a day.
								$va_attr_vals[$vs_element_code][$vs_k] = (is_array($vm_v = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $vs_delimiter, $vn_c))) ? join(" ", $vm_v) : $vm_v;
							}
						} else {
							$va_attr_vals[$vs_element_code][$vs_element_code] = (is_array($vm_v = BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $vs_delimiter, $vn_c))) ? join(" ", $vm_v) : $vm_v;
						}
					}
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * collectionSplitter returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['collectionSplitter'] = array(		
			'collectionSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values')
			),
			'collectionSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.  Note for object data: if the relationship type matches that set as the hierarchy control, the object will be pulled in as a "child" element in the collection hierarchy.')
			),
			'collectionSplitter_collectionType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Collection type'),
				'description' => _t('Accepts a constant list item idno from the list collection_types or a reference to the location in the data source where the type can be found.')
			),
			'collectionSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the collection record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'collectionSplitter_parents' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Parents'),
				'description' => _t('Collection parents to create, if required')
			),
			'collectionSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'collectionSplitter_collectionTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Collection type default'),
				'description' => _t('Sets the default collection type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list collection_types.')
			)
		);
?>