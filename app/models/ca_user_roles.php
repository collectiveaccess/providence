<?php
/** ---------------------------------------------------------------------
 * app/models/ca_user_roles.php : table access class for table ca_user_roles
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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

 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
	require_once(__CA_LIB_DIR__."/ca/WidgetManager.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	

BaseModel::$s_ca_models_definitions['ca_user_roles'] = array(
 	'NAME_SINGULAR' 	=> _t('user role'),
 	'NAME_PLURAL' 		=> _t('user roles'),
 	'FIELDS' 			=> array(
 		'role_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Role id'), 'DESCRIPTION' => _t('Unique identifier for role')
		),
		'name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Name'), 'DESCRIPTION' => _t('Name of role (must be unique)'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Code'), 'DESCRIPTION' => _t('Short code (up to 20 characters) for role (must be unique)'),
				'BOUNDS_LENGTH' => array(1,20)
		),
		'description' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 6,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Description'), 'DESCRIPTION' => _t('Description of role. This text will be displayed to system administrators only and should clearly document the purpose of the role.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the role when displayed in a list with other roles. Lower numbers indicate higher priority.'),
				'BOUNDS_VALUE' => array(0,65535)
		),
		'vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Role settings'), 'DESCRIPTION' => _t('Storage for role-level variables')
		),
		'field_access' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Field access'), 'DESCRIPTION' => _t('Contains definitions of what table/field combinations users with this role can access.')
		)
 	)
);

class ca_user_roles extends BaseModel {
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
	protected $TABLE = 'ca_user_roles';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'role_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('name');

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
	protected $ORDER_BY = array('name');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

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
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	protected $opo_app_plugin_manager;
	
	static $s_action_list;
	
	# ------------------------------------------------------
	# --- Constructor
	#
	# This is a function called when a new instance of this object is created. This
	# standard constructor supports three calling modes:
	#
	# 1. If called without parameters, simply creates a new, empty objects object
	# 2. If called with a single, valid primary key value, creates a new objects object and loads
	#    the record identified by the primary key value
	#
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
		
 		$this->opo_app_plugin_manager = new ApplicationPluginManager();
		$this->opo_widget_manager = new WidgetManager();
	}
	# ------------------------------------------------------
	public function getRoleList($ps_sort_field='', $ps_sort_direction='asc') {
		$o_db = $this->getDb();
		
		$va_valid_sorts = array('name', 'code');
		if (!in_array($ps_sort_field, $va_valid_sorts)) {
			$ps_sort_field = 'name';
		}
	
		$vs_sort = 'ORDER BY '.$ps_sort_field;

		$qr_roles = $o_db->query("
			SELECT *
			FROM ca_user_roles
			$vs_sort
		");
		
		$va_roles = array();
		while($qr_roles->nextRow()) {
 			$va_roles[$qr_roles->get('role_id')] = $qr_roles->getRow();
 		}
		
		return $va_roles;
	}
	# ------------------------------------------------------
	public function getName() {
		return $this->get('name');
	}
	# ------------------------------------------------------
	# Actions
	# ------------------------------------------------------
	/**
	 * Returns list of allowable actions for currently loaded role
	 */
	public function getRoleActions() {
		$va_role_settings = $this->get('vars');
		$va_actions = (isset($va_role_settings['actions']) && is_array($va_role_settings['actions'])) ? $va_role_settings['actions'] : array();
		return $va_actions;
	}
	# ------------------------------------------------------
	/**
	 * Sets list of allowable actions for currently loaded role
	 * Note: you must insert() or update() before changes are saved
	 * $pa_actions should be an indexed array of action names (as defined in app/conf/user_actions.conf)
	 */
	public function setRoleActions($pa_actions) {
		$va_role_settings = $this->get('vars');
		$va_role_settings['actions'] = $pa_actions;
		$this->set('vars', $va_role_settings);
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of all possible actions 
	 */
	public function getRoleActionList($pb_flatten=false) {
		$va_actions = ca_user_roles::loadRoleActionList($this);
		if ($pb_flatten) {
			return $va_actions['flattened'];
		} else {
			return $va_actions['raw'];
		}
	}
	# ------------------------------------------------------
	/**
	 * Caches and returns list of all possible actions 
	 */
	public static function loadRoleActionList() {
		if (!ca_user_roles::$s_action_list) {
			$o_config = Configuration::load();
			$o_actions_config = Configuration::load($o_config->get('user_actions'));
			$vo_datamodel = Datamodel::load();
			
			$va_raw_actions = $o_actions_config->getAssoc('user_actions');
	
			// expand actions that need expanding
			foreach($va_raw_actions as $vs_group => &$va_group_info) {
				$va_new_actions = array();
				foreach($va_group_info["actions"] as $vs_action_key => $va_action){
					if(is_array($va_action["expand_types"]) && strlen($va_action["expand_types"]["table"])>0){
						$t_instance = $vo_datamodel->getInstanceByTableName($va_action["expand_types"]["table"], true);
						if(method_exists($t_instance, "getTypeList")){
							$va_type_list = $t_instance->getTypeList();
							foreach($va_type_list as $vn_type_id => $va_type){
								$vs_descr_app = str_replace("%t", "&quot;".$va_type["name_singular"]."&quot;", $va_action["expand_types"]["description_appendix"]);
								$vs_label_app = str_replace("%t", "&quot;".$va_type["name_singular"]."&quot;", $va_action["expand_types"]["label_appendix"]);
								$va_new_actions[$vs_action_key."_type:{$t_instance->tableName()}:{$va_type["idno"]}"] = array(
									"description" => $va_action["description"]." ".$vs_descr_app,
									"label" => $va_action["label"]." ".$vs_label_app
								);
							}
						}
					}
				}
				$va_group_info["actions"] = array_merge($va_group_info["actions"],$va_new_actions);
			}
			
			if (is_array($va_raw_plugin_actions = ApplicationPluginManager::getPluginRoleActions())) {
				$va_raw_actions['plugins'] = array(
					'label' => 'Plugin actions',
					'description' => '',
					'actions' => $va_raw_plugin_actions
				);
			}
			if (is_array($va_raw_widget_actions = WidgetManager::getWidgetRoleActions())) {
				$va_raw_actions['widgets'] = array(
					'label' => 'Widget actions',
					'description' => '',
					'actions' => $va_raw_widget_actions
				);
			}
			
			$va_flattened_actions = array();
			foreach($va_raw_actions as $vs_group => $va_group_actions_info) {
				$va_flattened_actions = array_merge($va_flattened_actions, $va_group_actions_info['actions']);
			}
			
			ca_user_roles::$s_action_list = array('raw' => $va_raw_actions, 'flattened' => $va_flattened_actions);
		}
		return ca_user_roles::$s_action_list;
	}
	# ------------------------------------------------------
	# Static
	# ------------------------------------------------------
	/** 
	 *
	 */
	public static function getActionsForRoleIDs($pa_role_ids) {
		if (!is_array($pa_role_ids) || (sizeof($pa_role_ids) === 0)) { return array(); }
		
		$o_db = new Db();
		
		$qr_res = $o_db->query("
			SELECT role_id, vars
			FROM ca_user_roles
			WHERE
				role_id IN (".join(',', $pa_role_ids).")
		");
		
		$va_actions = array();
		while($qr_res->nextRow()) {
			$va_role_data = $qr_res->getVars('vars');
			if (isset($va_role_data['actions']) && is_array($va_role_data['actions'])) {
				$va_actions = array_merge($va_actions, $va_role_data['actions']);
			}
		}
		$va_actions = array_flip($va_actions);
		return array_keys($va_actions);
	}
	# ------------------------------------------------------
	/** 
	 * Determines if $ps_action is a valid user action
	 *
	 * @param string $ps_action A user action code to test
	 * @return bool True if code is valid, false if not
	 */
	public static function isValidAction($ps_action) {
		$va_actions = ca_user_roles::loadRoleActionList();
		if(isset($va_actions['flattened'][$ps_action])) { return true; }
		
		return false;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public static function getBundlesForRoleIDs($pa_role_ids) {
		if (!is_array($pa_role_ids) || (sizeof($pa_role_ids) === 0)) { return array(); }
		
		$o_db = new Db();
		
		$qr_res = $o_db->query("
			SELECT role_id, vars
			FROM ca_user_roles
			WHERE
				role_id IN (".join(',', $pa_role_ids).")
		");
		
		$va_bundles = array();
		while($qr_res->nextRow()) {
			$va_role_data = $qr_res->getVars('vars');
			if (isset($va_role_data['bundle_access_settings']) && is_array($va_role_data['bundle_access_settings'])) {
				
				$va_bundles;
				
			}
		}
		$va_actions = array_flip($va_actions);
		return array_keys($va_actions);
	}
	# ------------------------------------------------------
}
?>