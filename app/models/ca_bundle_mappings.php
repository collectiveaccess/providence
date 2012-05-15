<?php
/** ---------------------------------------------------------------------
 * app/models/ca_bundle_mappings.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */

require_once(__CA_LIB_DIR__.'/core/ModelSettings.php'); 
require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_groups.php');
require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_rules.php'); 
	
#
# Populate "target" drop-down with installed formats
#
$r_formats = opendir(__CA_LIB_DIR__.'/ca/ImportExport/Formats');

$va_mapping_formats = array();
while(($vs_format = readdir($r_formats)) !== false) {
	if ($vs_format{0} === '.') { continue; }
	if (preg_match('!^Base!', $vs_format)) { continue; }
	//BaseModel::$s_ca_models_definitions['ca_bundle_mappings']['FIELDS']['target']['BOUNDS_CHOICE_LIST'][$vs_format] = $vs_format;
	$va_mapping_formats[$vs_format] = $vs_format;
}

BaseModel::$s_ca_models_definitions['ca_bundle_mappings'] = array(
 	'NAME_SINGULAR' 	=> _t('bundle mapping'),
 	'NAME_PLURAL' 		=> _t('bundle mappings'),
 	'FIELDS' 			=> array(
		'mapping_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Mapping id', 'DESCRIPTION' => 'Identifier for Mapping'
		),
		'direction' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DONT_USE_AS_BUNDLE' => true,
				'IS_NULL' => false, 
				'DEFAULT' => 'E',
				'LABEL' => _t('Direction'), 'DESCRIPTION' => _t('Direction of mapping. "Import" indicates the mapping is <i>from</i> an external source into CollectiveAccess; "export" means the mapping is from CollectiveAccess to an external target.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('import') => 'I',
					_t('export') => 'E',
					//_t('import/export') => 'X',
					_t('fragment') => 'F'
				),
				'DONT_ALLOW_IN_UI' => true,
		),
		'target' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 1,
				'DONT_USE_AS_BUNDLE' => true,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Format'), 'DESCRIPTION' => _t('Code indicating import/export data format (Ex. EAD, METS)'),
				'DONT_ALLOW_IN_UI' => true,
				'BOUNDS_CHOICE_LIST' => $va_mapping_formats
		),
		'mapping_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Mapping code'), 'DESCRIPTION' => _t('Unique identifer for this mapping.'),
				'UNIQUE_WITHIN' => array()
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DONT_USE_AS_BUNDLE' => true,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Item type'), 'DESCRIPTION' => _t('Type of item for which data is being imported or exported.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('objects') => 57,
					_t('object lots') => 51,
					_t('entities') => 20,
					_t('places') => 72,
					_t('occurrences') => 67,
					_t('collections') => 13,
					_t('storage locations') => 89,
					_t('loans') => 133,
					_t('movements') => 137,
					_t('tours') => 153,
					_t('tour stops') => 155,
					_t('object events') => 45,
					_t('object representations') => 56,
					_t('list items') => 33
				),
				'DONT_ALLOW_IN_UI' => true
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if mapping is accessible to the public or not. ')
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Mapping settings')
		)
	)
);
unset($va_mapping_formats);

class ca_bundle_mappings extends BundlableLabelableBaseModelWithAttributes {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_bundle_mappings';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'mapping_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('mapping_id');

	# When the list of "list fields" above contains more than one field,
	# the LIST_DELIMITER text is displayed between fields as a delimiter.
	# This is typically a comma or space, but can be any string you like
	protected $LIST_DELIMITER = ' ';


	# What you'd call a single record from this table (eg. a "person")
	protected $NAME_SINGULAR;

	# What you'd call more than one record from this table (eg. "people")
	protected $NAME_PLURAL;

	# List of fields to sort listing of records by; you can use 
	# SQL 'ASC' and 'DESC' here if you like.
	protected $ORDER_BY = array('mapping_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	null;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_PARENT_ID_FLD		=	null;
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);	
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_bundle_mapping_labels';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
		global $_ca_bundle_mappings_settings;
		parent::__construct($pn_id);
		
		//
		$this->initSettings();
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
		
		$this->BUNDLES['ca_bundle_mapping_groups'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Mapping units'));
		$this->BUNDLES['settings'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Mapping settings'));
	}
	# ------------------------------------------------------
	protected function initSettings() {
		$va_settings = array();
		
		if (!($vn_table_num = $this->get('table_num'))) { 
			$this->SETTINGS = new ModelSettings($this, 'settings', array());	
		}
		if (($t_instance = $this->_DATAMODEL->getInstanceByTableNum($vn_table_num, true)) && method_exists($t_instance, "getTypeListCode")) {
			$va_settings['restrict_to_types'] = array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 100, 'height' => 8,
				'takesLocale' => false,
				'default' => '',
				'useList' => $t_instance->getTypeListCode(),
				'label' => _t('Restrict to types'),
				'description' => _t('Restrict use of mapping to specific types of item.')
			);
		}
		
		if (in_array($this->get('direction'), array('I', 'X'))) {
			$va_settings['defaultLocale'] = array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_SELECT,
				'width' => 40, 'height' => 1,
				'useLocaleList' => true,
				'label' => _t('Default locale'),
				'description' => _t('Sets locale used for all newly imported data.')
			);
			$va_settings['key'] = array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 80, 'height' => 2,
				'label' => _t('Path to identifier in import data'),
				'description' => _t('Specifies value in import data set to use as identifier when attempting to match with existing data. For XML data sets, specify the key as a tag path (ex. /record/head/identifier). For delimited data specify a column number.')
			);
			$va_settings['removeExistingRelationshipsOnUpdate'] = array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_CHECKBOXES,
				'width' => 4, 'height' => 1,
				'takesLocale' => false,
				'default' => '0',
				'label' => _t('Remove existing relationships on update?'),
				'description' => _t('If checked all existing relationships will be removed before record is updated. Otherwise any newly imported relationships will be added to existing ones.')
			);
			$va_settings['removeExistingAttributesOnUpdate'] = array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_CHECKBOXES,
				'width' => 4, 'height' => 1,
				'takesLocale' => false,
				'default' => '0',
				'label' => _t('Remove existing data on update?'),
				'description' => _t('If checked field values will be replaced with newly imported data when the record is updated.')
			);
		}
		
		$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);		
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
	# ------------------------------------------------------
	public function load ($pm_id=null) { 
		$vn_rc = parent::load($pm_id);
		$this->initSettings();
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override BaseModel::set() to prevent setting of target, table_num and direction fields for existing records
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if ($this->getPrimaryKey()) {
			if(!is_array($pa_fields))  { $pa_fields = array($pa_fields => $pm_value); }
			$va_fields_proc = array();
			foreach($pa_fields as $vs_field => $vs_value) {
				if (!in_array($vs_field, array('table_num', 'format', 'direction'))) {
					$va_fields_proc[$vs_field] = $vs_value;
				}
			}
			if (!sizeof($va_fields_proc)) { $va_fields_proc = null; }
			$vn_rc = parent::set($va_fields_proc, null, $pa_options);	
			
			$this->initSettings();
			return $vn_rc;
		}
		
		$vn_rc = parent::set($pa_fields, $pm_value, $pa_options);
		
		$this->initSettings();
		return $vn_rc;
	}
	# ------------------------------------------------------
	# Mapping settings
	# ------------------------------------------------------
	/**
	 *
	 */
	 # ------------------------------------------------------
	/** 
	 *
	 */
	public function addGroup($ps_name, $ps_group_code, $ps_notes, $pn_locale_id) {
		if (!$this->getPrimaryKey()) { return false; }
		
		$t_group = new ca_bundle_mapping_groups();
		$t_group->setMode(ACCESS_WRITE);
		$t_group->set('group_code', substr(preg_replace("![^A-Za-z0-9_]+!", "_", $ps_name), 0, 100));
		$t_group->set('mapping_id', $this->getPrimaryKey());
		$t_group->set('notes', $ps_notes);
		$t_group->insert();
		
		if ($t_group->numErrors()) {
			$this->errors = $t_group->errors;
			return false;
		}
		
		$t_group->addLabel(
			array('name' => $ps_name), $pn_locale_id, null, true
		);
		
		if ($t_group->numErrors()) {
			$this->errors = $t_group->errors;
			$t_group->delete(true);
			return false;
		}
		
		return $t_group;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function removeGroup($pn_group_id) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) { return false; }
		$t_group = new ca_bundle_mapping_groups();
		
		if (!$t_group->load(array('mapping_id' => $vn_mapping_id, 'group_id' => $pn_group_id))) { return false; }
		$t_group->setMode(ACCESS_WRITE);
		return $t_group->delete(true);
	}
	# ------------------------------------------------------
	/**
 	 * Returns a list of groups for the current mapping with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			NONE yet
	 * @return array Array keyed on group_id with values set to ranks for each group. 
	 */
	public function getGroupIDRanks($pa_options=null) {
		if(!($vn_mapping_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT g.group_id, g.rank
			FROM ca_bundle_mapping_groups g
			WHERE
				g.mapping_id = ?
			ORDER BY 
				g.rank ASC
		", (int)$vn_mapping_id);
		$va_groups = array();
		
		while($qr_res->nextRow()) {
			$va_groups[$qr_res->get('group_id')] = $qr_res->get('rank');
		}
		return $va_groups;
	}
	# ------------------------------------------------------
	/**
	 * Sets order of groups in the currently loaded mapping to the order of group_ids as set in $pa_group_ids
	 *
	 * @param array $pa_group_ids A list of group_ids in the mapping, in the order in which they should be displayed in the ui
	 * @param array $pa_options An optional array of options. Supported options include:
	 *			NONE
	 * @return array An array of errors. If the array is empty then no errors occurred
	 */
	public function reorderGroups($pa_group_ids, $pa_options=null) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) {	
			return null;
		}
		
		$va_group_ranks = $this->getGroupIDRanks($pa_options);	// get current ranks
		
		$vn_i = 0;
		$o_trans = new Transaction();
		$t_group = new ca_bundle_mapping_groups();
		$t_group->setTransaction($o_trans);
		$t_group->setMode(ACCESS_WRITE);
		$va_errors = array();
		
		
		// delete rows not present in $pa_group_ids
		$va_to_delete = array();
		foreach($va_group_ranks as $vn_group_id => $va_rank) {
			if (!in_array($vn_group_id, $pa_group_ids)) {
				if ($t_group->load(array('mapping_id' => $vn_mapping_id, 'group_id' => $vn_group_id))) {
					$t_group->delete(true);
				}
			}
		}
		
		
		// rewrite ranks
		foreach($pa_group_ids as $vn_rank => $vn_group_id) {
			if (isset($va_group_ranks[$vn_group_id]) && $t_group->load(array('mapping_id' => $vn_mapping_id, 'group_id' => $vn_group_id))) {
				if ($va_group_ranks[$vn_group_id] != $vn_rank) {
					$t_group->set('rank', $vn_rank);
					$t_group->update();
				
					if ($t_group->numErrors()) {
						$va_errors[$vn_group_id] = _t('Could not reorder group %1: %2', $vn_group_id, join('; ', $t_group->getErrors()));
					}
				}
			} 
		}
		
		if(sizeof($va_errors)) {
			$o_trans->rollback();
		} else {
			$o_trans->commit();
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function addRule($pn_group_id, $ps_ca_path_suffix, $ps_external_path_suffix, $ps_notes, $pa_settings) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) { return null; }
		
		$t_group = new ca_bundle_mapping_groups($pn_group_id);
		if ($t_group->getPrimaryKey() && ($t_group->get('mapping_id') == $vn_mapping_id)) {
			return $t_group->addRule($ps_ca_path_suffix, $ps_external_path_suffix, $ps_notes, $pa_settings);
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function removeRule($pn_relation_id) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) { return null; }
		
		$t_relationship = new ca_bundle_mapping_relationships($pn_relation_id);
		if ($t_relationship->getPrimaryKey() && ($t_relationship->get('mapping_id') == $vn_mapping_id)) {
			$t_relationship->setMode(ACCESS_WRITE);
			$t_relationship->delete(true);
			
			if ($t_relationship->numErrors()) {
				$this->errors = array_merge($this->errors, $t_relationship->errors);
				return false;
			}
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getRules($pn_type_id=null) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$vs_type_sql = "";
		if ($pn_type_id = (int)$pn_type_id) {
			$vs_type_sql = " AND (type_id IS NULL OR type_id = {$pn_type_id})";
		}
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_bundle_mapping_relationships
			WHERE
				mapping_id = ? {$vs_type_sql}
		", (int)$vn_mapping_id);
		
		$va_rels = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_row['settings'] = caUnserializeForDatabase($qr_res->get('settings'));
			$va_rels[] = $va_row;
		}
		
		return $va_rels;
	}
	# ------------------------------------------------------
	# Settings
	# ------------------------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 */
	public function __call($ps_name, $pa_arguments) {
		if (method_exists($this->SETTINGS, $ps_name)) {
			return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
		}
		die($this->tableName()." does not implement method {$ps_name}");
	}
	# ------------------------------------------------------	
	/**
	 *
	 */
	public function getAvailableMappings($pm_table_name_or_num=null, $pa_directions=null) {
		$o_dm = Datamodel::load();
		
		if ($pm_table_name_or_num) {
			if (!($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) {
				return null;
			}
		} else {
			$vn_table_num = null;
		}

		if (!is_array($pa_directions)) { $pa_directions = array('I', 'E', 'X'); }
		
		if (!is_array($pa_directions)) {
			$pa_directions = array($pa_directions);
		}
		
		$va_directions = array();
		
		foreach($pa_directions as $ps_direction) {
			$ps_direction = strtoupper($ps_direction);
			if (!in_array($ps_direction, array('I', 'E', 'X'))) {
				$ps_direction = 'X';
			}
			$va_directions["'".$ps_direction."'"] = true;
		}
		
		$vs_direction_sql = '';
		if (sizeof($va_directions)) {
			$vs_direction_sql = ' (direction IN ('.join(', ', array_keys($va_directions)).'))';
		}
		
		$o_db = new Db();
		
		if ($vn_table_num) {
			$qr_res = $o_db->query($x="
				SELECT cbm.*, cbml.name, cbml.locale_id
				FROM ca_bundle_mappings cbm
				LEFT JOIN ca_bundle_mapping_labels AS cbml ON cbm.mapping_id = cbml.mapping_id 
				WHERE
					cbml.is_preferred = 1 AND table_num = ? ".($vs_direction_sql ? " AND {$vs_direction_sql}" : "")."
			", (int)$vn_table_num);
		} else {
			$qr_res = $o_db->query("
				SELECT cbm.*, cbml.name, cbml.locale_id
				FROM ca_bundle_mappings cbm
				LEFT JOIN ca_bundle_mapping_labels AS cbml ON cbm.mapping_id = cbml.mapping_id 
				WHERE
					cbml.is_preferred = 1 ".($vs_direction_sql ? " AND {$vs_direction_sql}" : ""));
		}
		$va_mappings = array();
		while($qr_res->nextRow()) {
			$va_mappings[$qr_res->get('mapping_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
		}
		return caExtractValuesByUserLocale($va_mappings);
	}
	# ------------------------------------------------------	
	/**
	 *
	 */
	public static function getMappingList($pm_table_name_or_num=null, $pa_directions=null) {
		$t_mapping = new ca_bundle_mappings();
		return $t_mapping->getAvailableMappings($pm_table_name_or_num, $pa_directions);
	}
	# ------------------------------------------------------	
	/**
	 * Returns list of mapping groups (aka. "units") associated with this mapping 
	 *
	 * @param int $pn_mapping_id
	 * @param array $pa_options Options include:
	 *			includeRules = if true rules for each group are included in returned array; default is false
	 * @return array List of groups
	 */
	public function getGroups($pn_mapping_id=null, $pa_options=null) {
		if (!($vn_mapping_id = (int)$pn_mapping_id)) {
			$vn_mapping_id = $this->getPrimaryKey();
			if (!$vn_mapping_id)  { return null; }
		}
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_bundle_mapping_groups g
			INNER JOIN ca_bundle_mapping_group_labels as l ON l.group_id = g.group_id
			WHERE
				g.mapping_id = ? AND l.is_preferred = 1
			ORDER BY 
				g.rank
		", (int)$vn_mapping_id);
		$va_groups = array();
		
		$t_group = new ca_bundle_mapping_groups();
		while($qr_res->nextRow()) {
			$va_groups[$vn_group_id = $qr_res->get('group_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
			
			if (isset($pa_options['includeRules']) && $pa_options['includeRules'] && !isset($va_groups[$vn_group_id][$qr_res->get('locale_id')]['rules'])) {
				$va_groups[$vn_group_id][$qr_res->get('locale_id')]['rules'] = $t_group->getRules($vn_group_id);
			}
		}
		return caExtractValuesByUserLocale($va_groups);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function mappingIsAvailable($pm_mapping_code_or_id) {
		$t_chk = new ca_bundle_mappings();
		
		if (is_numeric($pm_mapping_code_or_id)) {
			$vb_exists = $t_chk->load($pm_mapping_code_or_id);
		} else {
			$vb_exists = $t_chk->load(array('mapping_code' => $pm_mapping_code_or_id));
		}
		
		return ($vb_exists ? $t_chk : false);
	}
	# ------------------------------------------------------
	/**
	 * Get mapping count
	 */
	public static function getMappingCount($pm_table_name_or_num=null, $pa_directions=null){
		return sizeof(ca_bundle_mappings::getMappingList($pm_table_name_or_num, $pa_directions));
	}
	# ------------------------------------------------------
	/**
	 * Get mapping count
	 */
	public function getMappingStatistics($pn_mapping_id=null) {
		if (!($vn_mapping_id = (int)$pn_mapping_id)) {
			$vn_mapping_id = $this->getPrimaryKey();
			if (!$vn_mapping_id)  { return null; }
		}
		
		$o_db = $this->getDb();
		
		$va_stats = array(
			'groupCount' => 0,
			'ruleCount' => 0
		);
		
		$qr_res = $o_db->query("
			SELECT count(*) c 
			FROM ca_bundle_mapping_groups
			WHERE
				mapping_id = ?
		", (int)$vn_mapping_id);
		if (!$qr_res->nextRow()) {
			return null;
		}
		
		$va_stats['groupCount'] = (int)$qr_res->get('c');
		
		$qr_res = $o_db->query("
			SELECT count(*) c 
			FROM ca_bundle_mapping_groups g
			INNER JOIN ca_bundle_mapping_rules AS r ON r.group_id = g.group_id
			WHERE
				g.mapping_id = ?
		", (int)$vn_mapping_id);
		if (!$qr_res->nextRow()) {
			return null;
		}
		
		$va_stats['ruleCount'] = (int)$qr_res->get('c');
		
		return $va_stats;
		
	}
	# ------------------------------------------------------
	# Bundles
	# ------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of groups in the currently loaded mapping
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getGroupHTMLFormBundle($po_request, $ps_form_name) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_mapping', $this);		
		$o_view->setVar('t_group', new ca_bundle_mapping_groups());		
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('request', $po_request);
		
		if ($this->getPrimaryKey()) {
			$o_view->setVar('groups', $this->getGroups(null, array('includeRules' => true)));
		} else {
			$o_view->setVar('groups', array());
		}
		
		return $o_view->render('ca_bundle_mapping_groups.php');
	}
	# -------------------------------------------------------
}
?>