<?php
/* ----------------------------------------------------------------------
 * storageLocationIndentedHierarchyBuilderRefinery.php.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');
require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');

class storageLocationIndentedHierarchyBuilderRefinery extends BaseRefinery {
	# -------------------------------------------------------
	static $level_values = null;
	static $level_value_ids = null;
	static $level_parent_ids = null;
	
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'storageLocationIndentedHierarchyBuilder';
		$this->ops_title = _t('Indexed storage location hierarchy builder');
		$this->ops_description = _t('Imports spreadsheets with hierarchies expressed as indented values spread across several columns as hierarchical storage location.');
		
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
			'errors' => [],
			'warnings' => [],
			'available' => true,
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function refine(&$destination_data, $group, $item, $source_data, $options=null) {
		global $g_ui_locale_id;
		$delimiter = caGetOption('delimiter', $options, null);
		
		if(!($locale_id = ca_locales::getDefaultCataloguingLocaleID())) {
			$locale_id = $g_ui_locale_id;
		}
		$o_log = (isset($options['log']) && is_object($options['log'])) ? $options['log'] : null;
		
		$t_mapping = caGetOption('mapping', $options, null);
		if ($t_mapping) {
			if ($t_mapping->get('table_num') != Datamodel::getTableNum('ca_storage_locations')) { 
				if ($o_log) {
					$o_log->logError(_t("storageLocationIndentedHierarchyBuilder refinery may only be used in imports to ca_storage_locations"));
				}
				return null; 
			}
		}
		
		$group_dest = explode(".", $group['destination']);
		$terminal = array_pop($group_dest);
		$pm_value = $source_data[$item['source']];
		
		// Get list of fields to insert
		if (!is_array($levels = $item['settings']['storageLocationIndentedHierarchyBuilder_levels'])) {
			if ($o_log) {
				$o_log->logError(_t("storageLocationIndentedHierarchyBuilder requires levels option be set to a list of data source placeholders"));
			}
			return null; 
		} else {
			$level_types = $item['settings']['storageLocationIndentedHierarchyBuilder_levelTypes'];
		}
		
		
		$t_loc = new ca_storage_locations();
		
		// Handle each level
		if (!is_array($level_values = storageLocationIndentedHierarchyBuilderRefinery::$level_values)) { 
			$level_values = $level_value_ids = [];
		}
		$level_value_ids = storageLocationIndentedHierarchyBuilderRefinery::$level_value_ids;
		$level_parent_ids = storageLocationIndentedHierarchyBuilderRefinery::$level_parent_ids;
		$max_level = 0;
		$parent_id = null;
		$attr_spec = null;
		
		if(is_array($attributes = caGetOption('attributes', $options, null))) {
			$attr_spec = caProcessRefineryAttributes($attributes, $source_data, $item, 0, $options);
		}
		foreach($levels as $i => $level_placeholder) {
			$level_value = null;
			if (strlen($level_placeholder)) {
				if ($level_value = BaseRefinery::parsePlaceholder($level_placeholder, $source_data, $item, 0, ['reader' => caGetOption('reader', $options, null), 'returnAsString' => true])) {
					if (!$parent_id) { 
						$x = $i;
						do {
							$parent_id = $level_parent_ids[$x];
							$x--;
						} while(!$parent_id && ($x >=0));
					}
					
					$type = isset($level_types[$i]) ? $level_types[$i] : null;
					if (
						$item_id = DataMigrationUtils::getStorageLocationID($level_value, $parent_id, $type, $locale_id, 
							array_merge(['is_enabled' => 1, 'idno' => preg_replace("![^A-Za-z0-9_]+!", "_", $level_value)], $attr_spec ?? []), 
							['matchOnIdno' => true, 'log' => $o_log, 'transaction' => caGetOption('transaction', $options, null), 'importEvent' => caGetOption('event', $options, null), 'importEventSource' => 'storageLocationIndentedHierarchyBuilder']
						)
					) {
						$level_parent_ids[$i] = $parent_id;
						$level_parent_ids[$i+1] = $item_id;
						
						$parent_id = $item_id;
						$level_values[$i] = $level_value;
						$level_value_ids[$i] = $item_id;
						$max_level = $i;
						
					
					}	
				}
			} 
		}
		storageLocationIndentedHierarchyBuilderRefinery::$level_values = array_slice($level_values, 0, $max_level + 1);
		storageLocationIndentedHierarchyBuilderRefinery::$level_value_ids = array_slice($level_value_ids, 0, $max_level + 1);
		storageLocationIndentedHierarchyBuilderRefinery::$level_parent_ids = array_slice($level_parent_ids, 0, $max_level + 2);
		
		if ($item['settings']['storageLocationIndentedHierarchyBuilder_list'] == 'returnData') {
			return $parent_id;
		}
		
		return null;
	}
	# -------------------------------------------------------	
	/**
	 * storageLocationIndentedHierarchyBuilderRefinery returns multiple values
	 *
	 * @return bool
	 */
	public function returnsMultipleValues() {
		return false;
	}
	# -------------------------------------------------------	
	/**
	 * storageLocationIndentedHierarchyBuilder returns actual row_ids, not idnos
	 *
	 * @return bool
	 */
	public function returnsRowIDs() {
		return true;
	}
	# -------------------------------------------------------
}

 BaseRefinery::$s_refinery_settings['storageLocationIndentedHierarchyBuilder'] = [
	'storageLocationIndentedHierarchyBuilder_levels' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Levels'),
		'description' => _t('List of sources for hierarchy levels')
	),
	'storageLocationIndentedHierarchyBuilder_levelTypes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Level types'),
		'description' => _t('List of types for hierarchy levels')
	),
	'storageLocationIndentedHierarchyBuilder_mode' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => 'processOnly',
		'options' => array(
			_t('Return data') => 'returnData',
			_t('Process only') => 'processOnly'
		),
		'label' => _t('Operating mode'),
		'description' => _t('Set to "returnData" to return the id of lowest item in the hierarchy to the importer; set to "processOnly" to create the list items in the hierarchy but not return values to the importer. Default is to process only.')
	),	
	'storageLocationIndentedHierarchyBuilder_attributes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attributes'),
		'description' => _t('Sets or maps metadata for the lot record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	),		
	'storageLocationIndentedHierarchyBuilder_ignoreParent' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore parent when trying to match row'),
		'description' => _t('Ignore parent when trying to match row.')
	),
	'storageLocationIndentedHierarchyBuilder_ignoreType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore type when trying to match row'),
		'description' => _t('Ignore type when trying to match row.')
	)
];
