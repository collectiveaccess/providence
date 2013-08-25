<?php
/* ----------------------------------------------------------------------
 * listItemSplitterRefinery.php : 
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
 
	class listItemSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		private $opb_returns_multiple_values = true;
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'listItemSplitter';
			$this->ops_title = _t('List item splitter');
			$this->ops_description = _t('Provides several list item-related import functions: splitting of many items in a string into separate names, and merging entity data with item names.');
			
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
			global $g_ui_locale_id;
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$va_group_dest[] = $vs_terminal; // put terminal back on end
			
			$pm_value = $pa_source_data[$pa_item['source']];
			
			if (is_array($pm_value)) {
				$va_list_items = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['listItemSplitter_delimiter']) {
					$va_list_items = explode($vs_delimiter, $pm_value);
				} else {
					$va_list_items = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			
			foreach($va_list_items as $vn_i => $vs_list_item) {
				if (!$vs_list_item = trim($vs_list_item)) { continue; }
				
				if(in_array($vs_terminal, array('name_singular', 'name_plural'))) {
					$this->opb_returns_multiple_values = false;
					return $vs_list_item;
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return array(0 => array('name_singular' => $vs_list_item, 'name_plural' => $vs_list_item));	
				}
			
				// Set label
				$va_val = array('preferred_labels' => array('name_singular' => $vs_list_item, 'name_plural' => $vs_list_item));
					
				// Set list_item_type
				if (
					($vs_type_opt = $pa_item['settings']['listItemSplitter_listItemType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['listItemSplitter_listItemTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				// Set list 
				$vn_list_id = null;
				if ($vs_list = $pa_item['settings']['listItemSplitter_list']) {
					$vn_list_id = caGetListID($vs_list);
				}
				if (!$vn_list_id) {
					// No list = bail!
					if ($o_log) { $o_log->logError(_t('[listItemSplitterRefinery] Could not find list %1 for item %2; item was skipped', $vs_list, $vs_list_item)); }
					return array();
				} 
				
				$va_val['list_id'] = $vn_list_id;
				
				$t_item = new ca_list_items();
				if ($pa_item['settings']['listItemSplitter_parent'] && $t_item->load(array('idno' => $pa_item['settings']['listItemSplitter_parent'], 'list_id' => $vn_list_id))) {
					$va_val['parent_id'] = $t_item->getPrimaryKey();
				} else {
					$va_val['parent_id'] = null;
				}
				
				// Set list item parents
				if ($va_parents = $pa_item['settings']['listItemSplitter_parents']) {
					$va_val['parent_id'] = caProcessRefineryParents('listItemSplitterRefinery', 'ca_list_items', $va_parents, $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log);
				}
			
				// Set attributes
				if (is_array($va_attr_vals = caProcessRefineryAttributes($pa_item['settings']['listItemSplitter_attributes'], $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				
				
				if ($vs_terminal == 'ca_list_items') {	
	// related list item
					// Set relationship type
					if (
						($vs_rel_type_opt = $pa_item['settings']['listItemSplitter_relationshipType'])
					) {
						$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
					}
					
					if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['listItemSplitter_relationshipTypeDefault'])) {
						$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
					}
					
					if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
						$o_log->logWarn(_t('[listItemSplitterRefinery] No relationship type is set for item %1', $vs_list_item));
					}
					
					$va_vals[] = $va_val;
				} else {							
	// list item in an attribute
					if ($vn_item_id = DataMigrationUtils::getListItemID($va_val['list_id'], $vs_list_item, $va_element_data['_type'], $g_ui_locale_id, array_merge($va_attr_vals, array('parent_id' => $va_val['_parent_id'], 'is_enabled' => true), $pa_options))) {
						$va_vals[] = array($vs_terminal => array($vs_terminal => $vn_item_id));
					} else {
						if ($o_log) { $o_log->logError(_t('[listItemSplitterRefinery] Could not add list item %1 to list %2', $vs_list_item, $va_val['list_id'])); }
					}
				}
				
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * listItemSplitter returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['listItemSplitter'] = array(		
			'listItemSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values.')
			),
			'listItemSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'listItemSplitter_listItemType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('List item type'),
				'description' => _t('Accepts a constant list item idno from the list list_item_types or a reference to the location in the data source where the type can be found.')
			),
			'listItemSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the list item record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'listItemSplitter_list' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('List'),
				'description' => _t('Identifies the root node of the list item list to add items to.')
			),
			'listItemSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system')
			),
			'listItemSplitter_listItemTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('List item type default'),
				'description' => _t('Sets the default list item type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list list_item_types')
			),
			'listItemSplitter_parents' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Parents'),
				'description' => _t('List item parents to create, if required')
			),
			'listItemSplitter_parent' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Parent'),
				'description' => _t('Parent list item')
			),
		);
?>