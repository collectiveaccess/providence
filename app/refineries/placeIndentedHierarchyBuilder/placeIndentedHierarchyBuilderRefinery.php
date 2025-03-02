<?php
/* ----------------------------------------------------------------------
 * placeIndentedHierarchyBuilderRefinery.php.php : 
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
require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');

class placeIndentedHierarchyBuilderRefinery extends BaseRefinery {
	# -------------------------------------------------------
	static $level_values = null;
	static $level_value_ids = null;
	static $level_parent_ids = null;
	
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'placeIndentedHierarchyBuilder';
		$this->ops_title = _t('Indented placed hierarchy builder');
		$this->ops_description = _t('Imports spreadsheets with place hierarchies expressed as indented values spread across several columns as hierarchical lists.');
		
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
			if ($t_mapping->get('table_num') != Datamodel::getTableNum('ca_places')) { 
				if ($o_log) {
					$o_log->logError(_t("placeIndentedHierarchyBuilder refinery may only be used in imports to ca_places"));
				}
				return null; 
			}
		}
		
		$group_dest = explode(".", $group['destination']);
		$terminal = array_pop($group_dest);
		$pm_value = $source_data[$item['source']];
		
		// Get list of fields to insert
		if (!is_array($levels = $item['settings']['placeIndentedHierarchyBuilder_levels'])) {
			if ($o_log) {
				$o_log->logError(_t("placeIndentedHierarchyBuilder requires levels option be set to a list of data source placeholders"));
			}
			return null; 
		} else {
			$level_types = $item['settings']['placeIndentedHierarchyBuilder_levelTypes'];
		}
		
		// Get hierarchy, or create if it doesn't already exist
		if (!($hier_code = $item['settings']['placeIndentedHierarchyBuilder_hierarchy'])) {
			if ($o_log) {
				$o_log->logError(_t("placeIndentedHierarchyBuilder requires hierarchy option be set"));
			}
			return null; 
		}
		$hier_id = caGetListItemID('place_hierarchies', $hier_code);
		if (!$hier_id) {
			// create list
			$t_list = new ca_lists(['list_code' => 'place_hierarchies']);
			if(!$t_list->isLoaded()) {
				if ($o_log) {
					$o_log->logError(_t("placeIndentedHierarchyBuilder requires place_hierarchies list to be defined"));
				}
				return null; 
			}
			if(!($t_hier = $t_list->addItem($hier_code, true, false, null, null, $hier_code))) {
				if ($o_log) {
					$o_log->logError(_t("placeIndentedHierarchyBuilder could not create place hierarchy %1: %2", $hier_code, join("; ", $t_list->getErrors())));
				}	
				return null;
			}
			$hier_id = $t_hier->getPrimaryKey();
		}
		
		// Handle each level
		if (!is_array($level_values = placeIndentedHierarchyBuilderRefinery::$level_values)) { 
			$level_values = $level_value_ids = [];
		}
		$level_value_ids = placeIndentedHierarchyBuilderRefinery::$level_value_ids;
		$level_parent_ids = placeIndentedHierarchyBuilderRefinery::$level_parent_ids;
		
		$max_level = 0;
		$parent_id = null;
		
		foreach($levels as $i => $level_placeholder) {
			$level_value = null;
			if (strlen($level_placeholder)) {
				if ($level_value = BaseRefinery::parsePlaceholder($level_placeholder, $source_data, $item, 0, array('reader' => caGetOption('reader', $options, null), 'returnAsString' => true))) {
					if (!$parent_id) { 
						$x = $i;
						do {
							$parent_id = $level_parent_ids[$x];
							$x--;
						} while(!$parent_id && ($x >=0));
					}
					
					$type = isset($level_types[$i]) ? $level_types[$i] : null;
					if ($place_id = DataMigrationUtils::getPlaceID($level_value, $parent_id,  $type, $locale_id, $hier_id, ['idno' => preg_replace("![^A-Za-z0-9_]+!", "_", $level_value)], 
						[
							'matchOnIdno' => true, 'log' => $o_log, 'transaction' => caGetOption('transaction', $options, null), 
							'importEvent' => caGetOption('event', $options, null), 
							'importEventSource' => 'placeIndentedHierarchyBuilder'
						]
						)
					) {
						$level_parent_ids[$i] = $parent_id;
						$level_parent_ids[$i+1] = $place_id;
						
						$parent_id = $place_id;
						$level_values[$i] = $level_value;
						$level_value_ids[$i] = $place_id;
						$max_level = $i;
					
					}	
				}
			} 
		}
		placeIndentedHierarchyBuilderRefinery::$level_values = array_slice($level_values ?? [], 0, $max_level + 1);
		placeIndentedHierarchyBuilderRefinery::$level_value_ids = array_slice($level_value_ids ?? [], 0, $max_level + 1);
		placeIndentedHierarchyBuilderRefinery::$level_parent_ids = array_slice($level_parent_ids ?? [], 0, $max_level + 2);
		
		if ($item['settings']['placeIndentedHierarchyBuilder_list'] == 'returnData') {
			return $parent_id;
		}
		
		return null;
	}
	# -------------------------------------------------------	
	/**
	 * placeIndentedHierarchyBuilderRefinery returns multiple values
	 *
	 * @return bool
	 */
	public function returnsMultipleValues() {
		return false;
	}
	# -------------------------------------------------------	
	/**
	 * placeIndentedHierarchyBuilder returns actual row_ids, not idnos
	 *
	 * @return bool
	 */
	public function returnsRowIDs() {
		return true;
	}
	# -------------------------------------------------------
}

 BaseRefinery::$s_refinery_settings['placeIndentedHierarchyBuilder'] = array(	
	'placeIndentedHierarchyBuilder_levels' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Levels'),
		'description' => _t('List of sources for hierarchy levels')
	),
	'placeIndentedHierarchyBuilder_levelTypes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Level types'),
		'description' => _t('List of types for hierarchy levels')
	),
	'placeIndentedHierarchyBuilder_hierarchy' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('List'),
		'description' => _t('Code of place hierarchy to import places into')
	),
	'placeIndentedHierarchyBuilder_mode' => array(
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
		'description' => _t('Set to "returnData" to return the id of lowest place in the hierarchy to the importer; set to "processOnly" to create the places in the hierarchy but not return values to the importer. Default is to process only.')
	),			
	'placeIndentedHierarchyBuilder_ignoreParent' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore parent when trying to match row'),
		'description' => _t('Ignore parent when trying to match row.')
	),
	'placeIndentedHierarchyBuilder_ignoreType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore type when trying to match row'),
		'description' => _t('Ignore type when trying to match row.')
	)
);
