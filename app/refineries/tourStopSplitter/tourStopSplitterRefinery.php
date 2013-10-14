<?php
/* ----------------------------------------------------------------------
 * tourStopSplitterRefinery.php : 
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
 
	class tourStopSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'tourStopSplitter';
			$this->ops_title = _t('Tour stop splitter');
			$this->ops_description = _t('Provides several tourstop location-related import functions: splitting of multiple locations in a string into individual values, mapping of type and relationship type for related locations, building location hierarchies and merging location data with names.');
			
			$this->opb_returns_multiple_values = true;
			
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
			$vs_dest_table = $va_group_dest[0];
			
			$pm_value = $pa_source_data[$pa_item['source']];
			
			if (is_array($pm_value)) {
				$va_tour_stops = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['tourStopSplitter_delimiter']) {
					$va_tour_stops = explode($vs_delimiter, $pm_value);
				} else {
					$va_tour_stops = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			foreach($va_tour_stops as $vn_i => $vs_tour_stop) {
				if (!$vs_tour_stop = trim($vs_tour_stop)) { continue; }
				
				if(in_array($vs_terminal, array('name_singular', 'name_plural'))) {
					$this->opb_returns_multiple_values = false;
					return $vs_tour_stop;
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return array(0 => array('name_singular' => $vs_tour_stop, 'name_plural' => $vs_tour_stop));	
				}
			
				// Set label
				$va_val = array('preferred_labels' => array('name_singular' => $vs_tour_stop, 'name_plural' => $vs_tour_stop));
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['tourStopSplitter_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['tourStopSplitter_relationshipTypeDefault'])) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarn(_t('[tourStopSplitterRefinery] No relationship type is set for tour stop %1', $vs_tour_stop));
				}
				
				// Set tour_stop_type
				if (
					($vs_type_opt = $pa_item['settings']['tourStopSplitter_tourStopType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['tourStopSplitter_tourStopTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarn(_t('[tourStopSplitterRefinery] No collection type is set for tour stop %1', $vs_tour_stop));
				}
				
				// Set tour 
				$vn_tour_id = null;
				if ($vs_tour = $pa_item['settings']['tourStopSplitter_tour']) {
					$vn_tour_id = caGetTourID($vs_tour);
				}
				if (!$vn_tour_id) {
					// No tour = bail!
					if($o_log) { $o_log->logWarn(_t('[tourStopSplitterRefinery] Could not find tour %1 to relate stop stops to.', $vs_tour_stop)); }
					return array();
				} 
				
				$va_val['tour_id'] = $vn_tour_id;
							
				// Set stop parents
				if ($va_parents = $pa_item['settings']['tourStopSplitter_parents']) {
					$va_val['parent_id'] = $va_val['_parent_id'] = caProcessRefineryParents('tourStopSplitterRefinery', 'ca_tour_stops', $va_parents, $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log);
				}
			
				// Set attributes
				if (is_array($va_attr_vals = caProcessRefineryAttributes($pa_item['settings']['tourStopSplitter_attributes'], $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				if (!$va_val['_parent_id']) { 
					if ($o_log) { $o_log->logError(_t('[tourStopSplitterRefinery] No parent found for %1', $vs_tour_stop)); }
					return array(); 
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * tourStopSplitter returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['tourStopSplitter'] = array(		
			'tourStopSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values.')
			),
			'tourStopSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'tourStopSplitter_tourStopType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Tour stop type'),
				'description' => _t('Accepts a constant list item idno from the list tour_stop_types or a reference to the location in the data source where the type can be found.')
			),
			'tourStopSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the tour stop record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'tourStopSplitter_tour' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Tour'),
				'description' => _t('Identifies the tour to add the stop to.')
			),
			'tourStopSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system')
			),
			'tourStopSplitter_tourStopTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Tour stop type default'),
				'description' => _t('Sets the default tour stop type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list tour_stop_types')
			),
			'tourStopSplitter_interstitial' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Interstitial attributes'),
				'description' => _t('Sets or maps metadata for the interstitial tour stop <em>relationship</em> record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'tourStopSplitter_nonPreferredLabels' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Non-preferred labels'),
				'description' => _t('List of non-preferred labels to apply to tour stops.')
			)
		);
?>