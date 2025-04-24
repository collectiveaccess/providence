<?php
/* ----------------------------------------------------------------------
 * objectHierarchyBuilderRefinery.php : 
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

class objectHierarchyBuilderRefinery extends BaseRefinery {
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'objectHierarchyBuilder';
		$this->ops_title = _t('object hierarchy builder');
		$this->ops_description = _t('Builds an object hierarchy.');
		
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
			if ($t_mapping->get('table_num') != Datamodel::getTableNum('ca_objects')) { 
				if ($o_log) {
					$o_log->logError(_t("objectHierarchyBuilder refinery may only be used in imports to ca_objects"));
				}
				return null; 
			}
		}
		
		$va_group_dest = explode(".", $pa_group['destination']);
		$vs_terminal = array_pop($va_group_dest);
		$pm_value = $pa_source_data[$pa_item['source']];
		
		
		$vn_parent_id = null;
		
		// Set object parents
		if ($va_parents = $pa_item['settings']['objectHierarchyBuilder_parents']) {
			$pa_options['refinery'] = $this;
			$vn_parent_id = caProcessRefineryParents('objectHierarchyBuilder', 'ca_objects', $va_parents, $pa_source_data, $pa_item, null, $pa_options);
		}
		
		return $vn_parent_id;
	}
	# -------------------------------------------------------	
	/**
	 * objectHierarchyBuilder returns multiple values
	 *
	 * @return bool
	 */
	public function returnsMultipleValues() {
		return false;
	}
	# -------------------------------------------------------	
	/**
	 * objectHierarchyBuilder returns actual row_ids, not idnos
	 *
	 * @return bool
	 */
	public function returnsRowIDs() {
		return true;
	}
	# -------------------------------------------------------
}

BaseRefinery::$s_refinery_settings['objectHierarchyBuilder'] = array(	
	'objectHierarchyBuilder_hierarchicalDelimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchical delimiter'),
		'description' => _t('Hierarchical delimiter')
	),
	'objectHierarchyBuilder_hierarchicalTypes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchical types'),
		'description' => _t('Hierarchical type list (indexed against values split with hierarchical delimiter)')
	),
	'objectHierarchyBuilder_matchOn' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Match on'),
		'description' => _t('List indicating sequence of checks for an existing record; values of array can be "preferred_labels" (or "label"), "nonpreferred_labels", "idno" or a metadata element code. Ex. array("idno", "label") will first try to match on idno and then label if the first match fails')
	),
	'objectHierarchyBuilder_parents' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Parents'),
		'description' => _t('object parents to create')
	),
	'objectHierarchyBuilder_dontMatchOnLabel' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Do not match on label'),
		'description' => _t('set to prohibit matching using label')
	),
	'objectHierarchyBuilder_ignoreParent' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore parent when trying to match row'),
		'description' => _t('Ignore parent when trying to match row.')
	),
	'objectHierarchyBuilder_ignoreType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore type when trying to match row'),
		'description' => _t('Ignore type when trying to match row.')
	)
);
