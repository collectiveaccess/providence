<?php
/* ----------------------------------------------------------------------
 * placeHierarchyBuilderRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Parsers/ExpressionParser.php');
require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');

class placeHierarchyBuilderRefinery extends BaseRefinery {
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'placeHierarchyBuilder';
		$this->ops_title = _t('Place hierarchy builder');
		$this->ops_description = _t('Builds a place hierarchy.');
		
		$this->opb_returns_multiple_values = true;
		$this->opb_supports_relationships = true;
		
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
		$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
		
		$t_mapping = caGetOption('mapping', $pa_options, null);
		if ($t_mapping) {
			if ($t_mapping->get('table_num') != Datamodel::getTableNum('ca_places')) { 
				if ($o_log) {
					$o_log->logError(_t("placeHierarchyBuilder refinery may only be used in imports to ca_places"));
				}
				return null; 
			}
		}
		
		$va_group_dest = explode(".", $pa_group['destination']);
		$vs_terminal = array_pop($va_group_dest);
		$pm_value = $pa_source_data[$pa_item['source']];
		
		// Set place hierarchy
		if (
			($vs_hierarchy = $pa_item['settings']['placeHierarchyBuilder_hierarchy'])		// for compatibility with older mappings
			||
			($vs_hierarchy = $pa_item['settings']['placeHierarchyBuilder_placeHierarchy'])
		) {
			$vn_hierarchy_id = caGetListItemID('place_hierarchies', $vs_hierarchy);
		} else {
			// Default to first place hierarchy
			$t_list = new ca_lists();
			$va_hierarchy_ids = $t_list->getItemsForList('place_hierarchies', array('idsOnly' => true, 'omitRoot' => true));
			$vn_hierarchy_id = array_shift($va_hierarchy_ids);
		}
		if (!$vn_hierarchy_id) {
			if ($o_log) { $o_log->logError(_t('[placeHierarchyBuilderRefinery] No place hierarchies are defined')); }
			return array();
		}
		$pa_options['hierarchyID'] = $vn_hierarchy_id;
		
		
		$vn_parent_id = null;
		
		// Set place parents
		if ($va_parents = $pa_item['settings']['placeHierarchyBuilder_parents']) {
			$pa_options['refinery'] = $this;
			$vn_parent_id = caProcessRefineryParents('placeHierarchyBuilder', 'ca_places', $va_parents, $pa_source_data, $pa_item, null, $pa_options);
		}
		
		return $vn_parent_id;
	}
	# -------------------------------------------------------	
	/**
	 * placeHierarchyBuilder returns multiple values
	 *
	 * @return bool
	 */
	public function returnsMultipleValues() {
		return false;
	}
	# -------------------------------------------------------	
	/**
	 * placeHierarchyBuilder returns actual row_ids, not idnos
	 *
	 * @return bool
	 */
	public function returnsRowIDs() {
		return true;
	}
	# -------------------------------------------------------
}

BaseRefinery::$s_refinery_settings['placeHierarchyBuilder'] = array(	
	'placeHierarchyBuilder_hierarchicalDelimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchical delimiter'),
		'description' => _t('Hierarchical delimiter')
	),
	'placeHierarchyBuilder_hierarchicalTypes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchical types'),
		'description' => _t('Hierarchical type list (indexed against values split with hierarchical delimiter)')
	),
	'placeHierarchyBuilder_matchOn' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Match on'),
		'description' => _t('List indicating sequence of checks for an existing record; values of array can be "preferred_labels" (or "label"), "nonpreferred_labels", "idno" or a metadata element code. Ex. array("idno", "label") will first try to match on idno and then label if the first match fails')
	),
	'placeHierarchyBuilder_placeHierarchy' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchy'),
		'description' => _t('Identifies the hierarchy (list_item id or idno) to add items to.')
	),
	'placeHierarchyBuilder_hierarchy' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchy'),
		'description' => _t('Deprecated synonym for placeHierarchy setting.')
	),
	'placeHierarchyBuilder_parents' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Parents'),
		'description' => _t('Place parents to create')
	),
	'placeHierarchyBuilder_ignoreParent' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore parent when trying to match row'),
		'description' => _t('Ignore parent when trying to match row.')
	),
	'placeHierarchyBuilder_ignoreType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore type when trying to match row'),
		'description' => _t('Ignore type when trying to match row.')
	)
);
