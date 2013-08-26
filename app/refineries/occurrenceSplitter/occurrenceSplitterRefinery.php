<?php
/* ----------------------------------------------------------------------
 * occurrenceSplitterRefinery.php : 
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
 
	class occurrenceSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		private $opb_returns_multiple_values = true;
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'occurrenceSplitter';
			$this->ops_title = _t('Occurrence splitter');
			$this->ops_description = _t('Provides several occurrence-related import functions: splitting of multiple occurrences in a string into individual values, mapping of type and relationship type for related occurrences, building occurrence hierarchies and merging occurrence data with names.');
			
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
				$va_occurrences = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['occurrenceSplitter_delimiter']) {
					$va_occurrences = explode($vs_delimiter, $pm_value);
				} else {
					$va_occurrences = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			foreach($va_occurrences as $vn_i => $vs_occurrence) {
				if (!$vs_occurrence = trim($vs_occurrence)) { continue; }
				
				if($vs_terminal == 'name') {
					$this->opb_returns_multiple_values = false;
					return $vs_occurrence;
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return array(0 => array('name' => $vs_occurrence));	
				}
			
				// Set label
				$va_val = array('preferred_labels' => array('name' => $vs_occurrence));
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['occurrenceSplitter_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['occurrenceSplitter_relationshipTypeDefault'])) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarn(_t('[occurrenceSplitterRefinery] No relationship type is set for occurrence %1', $vs_occurrence));
				}
			
				// Set occurrence_type
				if (
					($vs_type_opt = $pa_item['settings']['occurrenceSplitter_occurrenceType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) && !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['occurrenceSplitter_occurrenceTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarn(_t('[occurrenceSplitterRefinery] No occurrence type is set for occurrence %1', $vs_occurrence));
				}
				
				// Set occurrence parents
				if ($va_parents = $pa_item['settings']['occurrenceSplitter_parents']) {
					$va_val['parent_id'] = $va_val['_parent_id'] = caProcessRefineryParents('occurrenceSplitterRefinery', 'ca_occurrences', $va_parents, $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log);
				}
			
				// Set attributes
				if (is_array($va_attr_vals = caProcessRefineryAttributes($pa_item['settings']['occurrenceSplitter_attributes'], $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
								
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * occurrenceSplitter returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['occurrenceSplitter'] = array(		
			'occurrenceSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values')
			),
			'occurrenceSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.  Note for object data: if the relationship type matches that set as the hierarchy control, the object will be pulled in as a "child" element in the occurrence hierarchy.')
			),
			'occurrenceSplitter_occurrenceType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Occurrence type'),
				'description' => _t('Accepts a constant list item idno from the list occurrence_types or a reference to the location in the data source where the type can be found.')
			),
			'occurrenceSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the occurrence record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'occurrenceSplitter_parents' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Parents'),
				'description' => _t('Occurrence parents to create, if required')
			),
			'occurrenceSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'occurrenceSplitter_occurrenceTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Occurrence type default'),
				'description' => _t('Sets the default occurrence type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list occurrence_types.')
			)
		);
?>