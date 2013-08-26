<?php
/* ----------------------------------------------------------------------
 * storageLocationSplitterRefinery.php : 
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
 	require_once(__CA_MODELS_DIR__.'/ca_storage_locations.php');
 
	class storageLocationSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		private $opb_returns_multiple_values = true;
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'storageLocationSplitter';
			$this->ops_title = _t('Storage location splitter');
			$this->ops_description = _t('Provides several storage location-related import functions: splitting of multiple locations in a string into individual values, mapping of type and relationship type for related locations, building location hierarchies and merging location data with names.');
			
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
				$va_locations = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['storageLocationSplitter_delimiter']) {
					$va_locations = explode($vs_delimiter, $pm_value);
				} else {
					$va_locations = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			foreach($va_locations as $vn_i => $vs_location) {
				if (!($vs_location = trim($vs_location))) { continue; }
				
				if ($vs_hier_delimiter = $pa_item['settings']['storageLocationSplitter_hierarchicalDelimiter']) {
					$va_location_hier = explode($vs_hier_delimiter, $vs_location);
					if (sizeof($va_location_hier) > 1) {
						$vs_location = array_pop($va_location_hier);
						
						global $g_ui_locale_id;
						$vn_location_id = null;
					
						if (!is_array($va_types = $pa_item['settings']['storageLocationSplitter_hierarchicalStorageLocationTypes'])) {
							$va_types = array();
						}
						
						foreach($va_location_hier as $vn_i => $vs_parent) {
							if (sizeof($va_types) > 0)  { 
								$vs_type = array_shift($va_types); 
							} else { 
								if (!($vs_type = $pa_item['settings']['storageLocationSplitter_storageLocationType'])) {
									$vs_type = $pa_item['settings']['storageLocationSplitter_storageLocationTypeDefault'];
								}
							}
							if (!$vs_type) { break; }
							$vn_location_id = DataMigrationUtils::getStorageLocationID($vs_parent, $vn_location_id, $vs_type, $g_ui_locale_id, array('idno' => $vs_parent, 'parent_id' => $vn_location_id), $pa_options);
						}
						$va_val['parent_id'] = $vn_location_id;
					}
				}
				
				if($vs_terminal == 'name') {
					$this->opb_returns_multiple_values = false;
					return $vs_location;
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return array(0 => array('name' => $vs_location));	
				}
			
				// Set label
				$va_val = array('preferred_labels' => array('name' => $vs_location));
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['storageLocationSplitter_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['storageLocationSplitter_relationshipTypeDefault'])) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarn(_t('[storageLocationSplitterRefinery] No relationship type is set for location %1', $vs_location));
				}
			
				// Set storage_location_type
				if (
					($vs_type_opt = $pa_item['settings']['storageLocationSplitter_storageLocationType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['storageLocationSplitter_storageLocationTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarn(_t('[storageLocationSplitterRefinery] No storage location type is set for location %1', $vs_location));
				}
				
				// Set location parents
				if ($va_parents = $pa_item['settings']['storageLocationSplitter_parents']) {
					$va_val['parent_id'] = $va_val['_parent_id'] = caProcessRefineryParents('storageLocationSplitterRefinery', 'ca_storage_locations', $va_parents, $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log);
				} 
			
				// Set attributes
				if (is_array($va_attr_vals = caProcessRefineryAttributes($pa_item['settings']['storageLocationSplitter_attributes'], $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				if (!$va_val['_parent_id']) {
					$t_location = new ca_storage_locations();
					$va_val['_parent_id'] = $va_val['parent_id'] = $t_location->getHierarchyRootID();
				}
				if (!$va_val['_parent_id']) { 
					if ($o_log) { $o_log->logError(_t('[storageLocationSplitterRefinery] No parent found or location %1 in hierarchy %2', $vs_location, $vs_hierarchy)); }
					return array(); 
				}
							
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * storageLocationSplitter returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['storageLocationSplitter'] = array(		
			'storageLocationSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values.')
			),
			'storageLocationSplitter_hierarchicalDelimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Hierarchical delimiter'),
				'description' => _t('Sets the value of the delimiter to break hierarchical values on.')
			),
			'storageLocationSplitter_hierarchicalStorageLocationTypes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Hierarchical storage location types'),
				'description' => _t('A semicolon-delimited list of storage location types to apply to hierarchical storage locations extracted using the hierarchical delimiter.')
			),
			'storageLocationSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'storageLocationSplitter_storageLocationType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Storage location type'),
				'description' => _t('Accepts a constant list item idno from the list storage_location_types or a reference to the location in the data source where the type can be found.')
			),
			'storageLocationSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the storage location record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'storageLocationSplitter_parents' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Parents'),
				'description' => _t('Storage location parents to create, if required')
			),
			'storageLocationSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system')
			),
			'storageLocationSplitter_storageLocationTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Storage location type default'),
				'description' => _t('Sets the default storage location type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list storage_location_types')
			)
		);
?>