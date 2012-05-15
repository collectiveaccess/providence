<?php
/** ---------------------------------------------------------------------
 * app/models/ca_bundle_mapping_groups.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/ca/BaseLabel.php');
require_once(__CA_LIB_DIR__.'/ca/BundlableLabelableBaseModelWithAttributes.php'); 

BaseModel::$s_ca_models_definitions['ca_bundle_mapping_groups'] = array(
 	'NAME_SINGULAR' 	=> _t('bundle mapping group'),
 	'NAME_PLURAL' 		=> _t('bundle mapping groups'),
 	'FIELDS'			=> array(
		'group_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Group id', 'DESCRIPTION' => 'Identifier for Relation'
		),
		'mapping_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => 'Mapping id', 'DESCRIPTION' => 'Identifier for mapping'
		),
		'group_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Unit code'), 'DESCRIPTION' => _t('Unique alphanumeric identifier for this unit.'),
				'BOUNDS_VALUE' => array(0,100)
		),
		'ca_base_path' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
			//	'DONT_ALLOW_IN_UI' => true,
				'LABEL' => _t('Base path for CollectiveAccess target'), 'DESCRIPTION' => _t('Path to CollectiveAccess bundle being mapped by this unit. The CollectiveAccess portion of all rules in this unit will be applied relative to this base path.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'external_base_path' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
			//	'DONT_ALLOW_IN_UI' => true,
				'LABEL' => _t('Base path for external target'), 'DESCRIPTION' => _t('Path to element in external format being mapped by this unit. All elements in the external file format being mapped to by this unit will be created relative to this base path. The format of the base path is determined by the format. For XML-based formats this will typically be an XPath specification; for delimited targets this will be a column number.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Unit settings')
		),
		'notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Notes'), 'DESCRIPTION' => _t('Notes and remarks relating to the mapping unit'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		)
	)
);

class ca_bundle_mapping_groups extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_bundle_mapping_groups';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'group_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('bundle_name');

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
	protected $ORDER_BY = array('group_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = 'rank';
	
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
	protected $LABEL_TABLE_NAME = "ca_bundle_mapping_group_labels";
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	# ----------------------------------------
	function __construct($pn_id=null) {
		parent::__construct($pn_id);
		
		$this->initSettings();
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
		
		$this->BUNDLES['ca_bundle_mapping_rules'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Unit rules'));
		$this->BUNDLES['settings'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Unit settings'));
	}
	# ------------------------------------------------------
	protected function initSettings() {
		$va_settings = array();
		
		$va_tmp = explode('.', $this->get('ca_base_path'));
		if ((!isset($va_tmp[0])) || !$va_tmp[0]) { 
			$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);	
			return false; 
		}
		$t_group_instance = $this->_DATAMODEL->getInstanceByTableNum($va_tmp[0], true);
		if (!$t_group_instance) { 
			$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);	
			return false; 
		}
		
		$t_mapping = new ca_bundle_mappings($this->get('mapping_id'));
		$t_mapping_instance = $this->_DATAMODEL->getInstanceByTableNum($t_mapping->get('table_num'), true);
		if (!$t_mapping_instance) { 
			$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);	
			return false; 
		}
		
		if(in_array($t_mapping->get('direction'), array('I', 'X'))) {
			// Import mapping
			$t_list = new ca_lists();
			if (($vs_list_code = $t_group_instance->getTypeListCode()) && ($t_list->load(array('list_code' => $vs_list_code)))) {
				$va_settings['type'] = array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'width' => 100, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'useList' => $vs_list_code,
					'label' => _t('Type'),
					'description' => _t('Type to use when creating new records via import.')
				);	
			}
			
			if ($t_rel = $t_mapping_instance->getRelationshipInstance($t_group_instance->tableName())) {
				$va_settings['relationship_type'] = array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'useRelationshipTypeList' => $t_rel->tableName(),
					'width' => 100, 'height' => 6,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Restrict to relationship types'),
					'description' => _t('Restrict use of mapping to specific types of relationships.')
				);	
			}
			
			if (($t_group_instance->tableName() == 'ca_list_items') || ($t_mapping_instance->tableName() == 'ca_list_items')) {
				$va_settings['list'] = array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'showLists' => true,
					'width' => 100, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('List'),
					'description' => _t('List to add items to.')
				);	
			}
			
			$va_settings['priority'] = array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 40, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'options' => array(
					'mandatory' => 'mandatory',
					'optional' => 'optional'
				),
				'label' => _t('Priority'),
				'description' => _t('If mandatory then a record will fail to import if any of the values mapped in this unit are invalid.')
			);
			
		}
		
		if(in_array($t_mapping->get('direction'), array('E', 'X'))) {
			// Export mapping
			if (method_exists($t_group_instance, "getTypeListCode")) {
				$va_settings['restrict_to_types'] = array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'width' => 100, 'height' => 8,
					'takesLocale' => false,
					'default' => '',
					'useList' => $t_group_instance->getTypeListCode(),
					'label' => _t('Restrict to types'),
					'description' => _t('Restrict use of mapping to specific types of item.')
				);	
			}
			
			if ($t_rel = $t_mapping_instance->getRelationshipInstance($t_group_instance->tableName())) {
				$va_settings['restrict_to_relationship_types'] = array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'useRelationshipTypeList' =>  $t_rel->tableName(),
					'width' => 100, 'height' => 8,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Restrict to relationship types'),
					'description' => _t('Restrict use of mapping to specific types of relationships.')
				);	
			}
			
			$va_settings['forceOutput'] = array(
				'formatType' => FT_TEXT,
				'displayType' => DT_CHECKBOXES,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '0',
				'label' => _t('Force output?'),
				'description' => _t('Normally rules in a group are only applied if there is at least one mapping to a non-static value and that value is set. If "forceOutput" is checked then these rules will be applied even if no non-static values are set.')
			);
		}
		
		$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);		
		
		$this->FIELDS['external_base_path']['LABEL'] = _t('Base path for %1 target', $t_mapping->get('target'));
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
	public function insert ($pa_options=null) { 
		$vn_rc = parent::insert($pa_options);
		$this->initSettings();
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function update ($pa_options=null) { 
		$vn_rc = parent::update($pa_options);
		$this->initSettings();
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 */
	public function __call($ps_name, $pa_arguments) {
		if (method_exists($this->SETTINGS, $ps_name)) {
			return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
		}
		die($this->tableName()." does not implement method {$ps_name}");
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function getRules($pn_group_id=null, $pa_options=null) {
		if (!($vn_group_id = (int)$pn_group_id)) {
			$vn_group_id = $this->getPrimaryKey();
			if (!$vn_group_id)  { return null; }
		}
		
		$vs_id_prefix = isset($pa_options['id_prefix']) ? $pa_options['id_prefix'] : '';
		$vb_include_settings_form = isset($pa_options['includeSettingsForm']) ? (bool)$pa_options['includeSettingsForm'] : false;
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_bundle_mapping_rules r
			WHERE
				r.group_id = ?
			ORDER BY 
				r.rank
		", (int)$vn_group_id);
		
		$va_rules = array();
		
		while($qr_res->nextRow()) {
			$va_rules[$vn_rule_id = $qr_res->get('rule_id')] = $qr_res->getRow();
			
			if ($vb_include_settings_form) {
				$t_rule = new ca_bundle_mapping_rules($vn_rule_id);
				$va_rules[$vn_rule_id]['settings'] = $t_rule->getHTMLSettingForm(array('settings' => $t_rule->getSettings(), 'id' => "{$vs_id_prefix}_setting_{$vn_rule_id}"));
			}
		}
		return $va_rules;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function getRuleCount($pn_group_id=null) {
		if(is_array($va_rules = $this->getRules($pn_group_id))) {
			return sizeof($va_rules);
		}
		return 0;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function addRule($ps_ca_path_suffix, $ps_external_path_suffix, $ps_notes, $pa_settings) {
		if (!($vn_group_id = $this->getPrimaryKey())) { return null; }
		
		$t_rule = new ca_bundle_mapping_rules();
		$t_rule->setMode(ACCESS_WRITE);
		$t_rule->set('group_id', $vn_group_id);
		$t_rule->set('ca_path_suffix', $ps_ca_path_suffix);
		$t_rule->set('external_path_suffix', $ps_external_path_suffix);
		$t_rule->set('notes', $ps_notes);
		
		foreach($pa_settings as $vs_key => $vs_value) {
			$t_rule->setSetting($vs_key, $vs_value);
		}
		
		$t_rule->insert();
		
		if ($t_rule->numErrors()) {
			$this->errors = array_merge($this->errors, $t_rule->errors);
			return false;
		}
		return $t_rule;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function removeRule($pn_rule_id) {
		if (!($vn_group_id = $this->getPrimaryKey())) { return null; }
		
		$t_rule = new ca_bundle_mapping_rules($pn_rule_id);
		if ($t_rule->getPrimaryKey() && ($t_rule->get('group_id') == $vn_group_id)) {
			$t_rule->setMode(ACCESS_WRITE);
			$t_rule->delete(true);
			
			if ($t_rule->numErrors()) {
				$this->errors = array_merge($this->errors, $t_rule->errors);
				return false;
			}
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
 	 * Returns a list of rules for the current mapping group with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			NONE yet
	 * @return array Array keyed on riule_id with values set to ranks for each rule. 
	 */
	public function getRuleIDRanks($pa_options=null) {
		if(!($vn_group_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT r.rule_id, r.rank
			FROM ca_bundle_mapping_rules r
			WHERE
				r.group_id = ?
			ORDER BY 
				r.rank ASC
		", (int)$vn_group_id);
		$va_rules = array();
		
		while($qr_res->nextRow()) {
			$va_rules[$qr_res->get('rule_id')] = $qr_res->get('rank');
		}
		return $va_rules;
	}
	# ------------------------------------------------------
	/**
	 * Sets order of rules in the currently loaded mapping group to the order of rule_ids as set in $pa_rule_ids
	 *
	 * @param array $pa_rule_ids A list of rule_ids in the mapping, in the order in which they should be displayed in the ui
	 * @param array $pa_options An optional array of options. Supported options include:
	 *			NONE
	 * @return array An array of errors. If the array is empty then no errors occurred
	 */
	public function reorderRules($pa_rule_ids, $pa_options=null) {
		if (!($vn_group_id = $this->getPrimaryKey())) {	
			return null;
		}
		
		$va_rule_ranks = $this->getRuleIDRanks($pa_options);	// get current ranks
		
		$vn_i = 0;
		$o_trans = new Transaction();
		$t_rule = new ca_bundle_mapping_rules();
		$t_rule->setTransaction($o_trans);
		$t_rule->setMode(ACCESS_WRITE);
		$va_errors = array();
		
		
		// delete rows not present in $pa_rule_ids
		$va_to_delete = array();
		foreach($va_rule_ranks as $vn_rule_id => $va_rank) {
			if (!in_array($vn_rule_id, $pa_rule_ids)) {
				if ($t_rule->load(array('group_id' => $vn_group_id, 'rule_id' => $vn_rule_id))) {
					$t_rule->delete(true);
				}
			}
		}
		
		
		// rewrite ranks
		foreach($pa_rule_ids as $vn_rank => $vn_rule_id) {
			if (isset($va_rule_ranks[$vn_rule_id]) && $t_rule->load(array('group_id' => $vn_group_id, 'rule_id' => $vn_rule_id))) {
				if ($va_rule_ranks[$vn_rule_id] != $vn_rank) {
					$t_rule->set('rank', $vn_rank);
					$t_rule->update();
				
					if ($t_rule->numErrors()) {
						$va_errors[$vn_rule_id] = _t('Could not reorder rule %1: %2', $vn_rule_id, join('; ', $t_rule->getErrors()));
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
	public function getRuleHTMLFormBundle($po_request, $ps_form_name) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_group', $this);	
		$t_mapping = new ca_bundle_mappings($this->get('mapping_id'));
		$o_view->setVar('t_mapping', $t_mapping);		
		
		$t_rule = new ca_bundle_mapping_rules();
		$o_view->setVar('t_rule', $t_rule);		
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('request', $po_request);
		
		if ($this->getPrimaryKey()) {
			$o_view->setVar('rules', $this->getRules(null, array('id_prefix' => $ps_form_name, 'includeSettingsForm' => true)));
		} else {
			$o_view->setVar('rules', array());
		}
		
		$o_view->setVar('new_settings_form', $t_rule->getHTMLSettingForm(array('settings' => array(), 'id' => $vs_id_prefix.'_setting_new_{n}')));
		
		return $o_view->render('ca_bundle_mapping_rules.php');
	}
	# ----------------------------------------
}
?>