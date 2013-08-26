<?php
/* ----------------------------------------------------------------------
 * movementSplitterRefinery.php : 
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
 
	class movementSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		private $opb_returns_multiple_values = true;
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'movementSplitter';
			$this->ops_title = _t('Movement splitter');
			$this->ops_description = _t('Provides several movement-related import functions: splitting of multiple movements in a string into individual values, mapping of type and relationship type for related movements, and merging movement data with movement names.');
			
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
				$va_movements = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['movementSplitter_delimiter']) {
					$va_movements = explode($vs_delimiter, $pm_value);
				} else {
					$va_movements = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			foreach($va_movements as $vn_i => $vs_movement) {
				if (!$vs_movement = trim($vs_movement)) { continue; }
				
				if($vs_terminal == 'name') {
					$this->opb_returns_multiple_values = false;
					return $vs_movement;
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return array(0 => array('name' => $vs_movement));	
				}
			
				// Set label
				$va_val = array('preferred_labels' => array('name' => $vs_movement));
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['movementSplitter_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || $va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['movementSplitter_relationshipTypeDefault'])) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarn(_t('[movementSplitterRefinery] No relationship type is set for movement %1', $vs_movement));
				}
	
				// Set movement_type
				if (
					($vs_type_opt = $pa_item['settings']['movementSplitter_movementType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['movementSplitter_movementTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarn(_t('[movementSplitterRefinery] No movement type is set for movement %1', $vs_movement));
				}
			
				// Set movement parents
				if ($va_parents = $pa_item['settings']['movementSplitter_parents']) {
					$va_val['parent_id'] = $va_val['_parent_id'] = caProcessRefineryParents('movementSplitterRefinery', 'ca_movements', $va_parents, $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log);
				}
			
				// Set attributes
				if (is_array($va_attr_vals = caProcessRefineryAttributes($pa_item['settings']['movementSplitter_attributes'], $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * movementSplitter returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['movementSplitter'] = array(		
			'movementSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values')
			),
			'movementSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.  Note for object data: if the relationship type matches that set as the hierarchy control, the object will be pulled in as a "child" element in the movement hierarchy.')
			),
			'movementSplitter_movementType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('movement type'),
				'description' => _t('Accepts a constant list item idno from the list movement_types or a reference to the location in the data source where the type can be found.')
			),
			'movementSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the movement record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'movementSplitter_parents' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Parents'),
				'description' => _t('movement parents to create, if required')
			),
			'movementSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'movementSplitter_movementTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('movement type default'),
				'description' => _t('Sets the default movement type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list movement_types.')
			)
		);
?>