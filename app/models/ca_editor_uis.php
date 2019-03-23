<?php
/** ---------------------------------------------------------------------
 * app/models/ca_editor_uis.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2018 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BundlableLabelableBaseModelWithAttributes.php');
require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
require_once(__CA_MODELS_DIR__.'/ca_editor_ui_type_restrictions.php');


BaseModel::$s_ca_models_definitions['ca_editor_uis'] = array(
 	'NAME_SINGULAR' 	=> _t('editor UI'),
 	'NAME_PLURAL' 		=> _t('editor UIs'),
 	'FIELDS' 			=> array(
 		'ui_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this user interface')
		),
		'editor_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 22, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Editor code'), 'DESCRIPTION' => _t('Unique code for editor; used to identify the editor for configuration purposes.'),
				'BOUNDS_LENGTH' => array(0,100),
				'UNIQUE_WITHIN' => []
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for User'
		),
		'is_system_ui' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is system UI?'), 'DESCRIPTION' => _t('If set, user interface will be available to all users.'),
				'REQUIRES' => array('is_administrator')
		),
		'editor_type' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
				'DONT_USE_AS_BUNDLE' => true,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Editor type'), 'DESCRIPTION' => _t('Type of item this editor UI operates on.'),
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
					_t('object representations') => 56,
					_t('representation annotations') => 82,
					_t('sets') => 103,
					_t('set items') => 105,
					_t('lists') => 36,
					_t('list items') => 33,
					_t('search forms') => 121,
					_t('displays') => 124,
					_t('relationship types') => 79,
					_t('site pages') => 235,
					_t('user interfaces') => 101,
					_t('user interface screens') => 100,
					_t('metadata alert rules') => 238
				)
		),
		'color' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COLORPICKER, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Color'), 'DESCRIPTION' => _t('Color to identify the editor UI with')
		),
		'icon' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_icons',
				'LABEL' => _t('Icon'), 'DESCRIPTION' => _t('Optional icon to identify the editor UI with')
		)
 	)
);

class ca_editor_uis extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_editor_uis';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'ui_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('ui_id');

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
	protected $ORDER_BY = array('ui_id');

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
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
			
		)
	);	
	
	# ------------------------------------------------------
	# Group-based access control
	# ------------------------------------------------------
	protected $USERS_RELATIONSHIP_TABLE = 'ca_editor_uis_x_users';
	protected $USER_GROUPS_RELATIONSHIP_TABLE = 'ca_editor_uis_x_user_groups';
	protected $USER_ROLES_RELATIONSHIP_TABLE = 'ca_editor_uis_x_roles';
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_editor_ui_labels';
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'EditorUISearch';
	protected $SEARCH_RESULT_CLASSNAME = 'EditorUISearchResult';
	
	
	static $s_available_ui_cache = [];
	static $s_default_ui_cache = [];
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	
	static $s_loaded_relationship_tables = false;
	static $s_screen_cache = [];
	static $s_screen_bundle_cache = [];
	static $s_placements_for_bundle_cache = [];
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);
		
		if (!ca_editor_uis::$s_loaded_relationship_tables) {
			require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
			$t_rel = new ca_relationship_types();
			$va_rels = $t_rel->getRelationshipsUsingTypes();
			
			foreach($va_rels as $vn_table_num => $va_rel_table_info) {
				BaseModel::$s_ca_models_definitions['ca_editor_uis']['FIELDS']['editor_type']['BOUNDS_CHOICE_LIST'][$va_rel_table_info['name']] = $vn_table_num;
			}
			
			ca_editor_uis::$s_loaded_relationship_tables = true;
		}
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		
		$this->BUNDLES['ca_users'] = array('type' => 'special', 'repeating' => true, 'label' => _t('User access'));
		$this->BUNDLES['ca_user_groups'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Group access'));
		$this->BUNDLES['ca_user_roles'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Role access'));
		
		$this->BUNDLES['ca_editor_ui_screens'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Screens'));
		$this->BUNDLES['ca_editor_ui_type_restrictions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Type restrictions'));
	}
	# ------------------------------------------------------
	/** 
	 * Override set() to reject changes to user_id for existing rows
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if ($this->getPrimaryKey()) {
			if (is_array($pa_fields)) {
				if (isset($pa_fields['user_id'])) { unset($pa_fields['user_id']); }
				if (isset($pa_fields['editor_type'])) { unset($pa_fields['editor_type']); }
			} else {
				if ($pa_fields === 'user_id') { return false; }
				if ($pa_fields === 'editor_type') { return false; }
			}
		}
		return parent::set($pa_fields, $pm_value, $pa_options);
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		$vn_rc = parent::insert($pa_options);
		
		if ($this->getPrimaryKey()) {
			// create root in ca_list_items
			$t_item_root = new ca_editor_ui_screens();
			$t_item_root->setMode(ACCESS_WRITE);
			$t_item_root->set('ui_id', $this->getPrimaryKey());
			$t_item_root->set('is_default', 0);
			$t_item_root->insert();
			
			if ($t_item_root->numErrors()) {
				$this->delete();
				$this->errors = array_merge($this->errors, $t_item_root->errors);
			}
		}
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 * Load standard UI (either system default or user-defined) for specified record and, optionally, record type
	 *
	 * @param mixed $pm_table_name_or_num Table name or number to load editor for. Values for $pm_table_name_or_num may be, for example:
	 *		'ca_objects' = objects editor
	 *		'ca_entities' = entities editor
	 *		.. etc ..
	 * @param RequestHTTP $po_request The current request
	 * @param int $pn_type_id Optional type_id
	 * @param array $pa_options Options are:
	 *		editorPref = "cataloguing" to select UI using user's "cataloguing_<table_name>_editor_ui" preference, "quickadd" to use "quickadd_<table_name>_editor_ui" pref; default is "cataloguing"
	 * @return ca_editor_uis instance loaded with default UI on success, false on failure
	 */
	static public function loadDefaultUI($pm_table_name_or_num, $po_request, $pn_type_id=null, $pa_options=null) {
		if (isset(ca_editor_uis::$s_default_ui_cache[$pm_table_name_or_num.'/'.$pn_type_id])) { return ca_editor_uis::$s_default_ui_cache[$pm_table_name_or_num.'/'.$pn_type_id]; }
		
		if (is_numeric($pm_table_name_or_num)) {
			$t_instance = Datamodel::getInstanceByTableNum($pm_table_name_or_num, true);
		} else {
			$t_instance = Datamodel::getInstanceByTableName($pm_table_name_or_num, true);
		}
		if (!$t_instance) { return ca_editor_uis::$s_default_ui_cache[$pm_table_name_or_num.'/'.$pn_type_id] = false; }
			
		$vs_table_name = $t_instance->tableName();
		$vn_table_num = $t_instance->tableNum();
		
		if (!isset($pa_options['editorPref'])) { $pa_options['editorPref'] = 'cataloguing'; }
		
		if ($po_request->user) {
			switch($pa_options['editorPref']) {
				case 'quickadd':
					$va_uis_by_type = $po_request->user->getPreference("quickadd_{$vs_table_name}_editor_ui");
					break;
				default:
					$va_uis_by_type = $po_request->user->getPreference("cataloguing_{$vs_table_name}_editor_ui");
					break;
			}
			$va_available_uis_by_type = $po_request->user->_getUIListByType($vn_table_num);
		} else {
			$va_uis_by_type = $va_available_uis_by_type = [];
		}

		$vn_type_id = $pn_type_id;
		if ($vn_type_id && $va_uis_by_type) {
			if (!is_array($va_uis_by_type)) { 
				if (!isset($va_available_uis_by_type[$vn_type_id][$va_uis_by_type]) && !isset($va_available_uis_by_type['__all__'][$va_uis_by_type])) {
					$vn_type_id = null;
				}
				$va_uis_by_type = []; 
			} else {
				if (!isset($va_available_uis_by_type[$vn_type_id][$va_uis_by_type[$vn_type_id]]) && !isset($va_available_uis_by_type['__all__'][$va_uis_by_type[$vn_type_id]])) {
					$vn_type_id = null;
				}
			}
		}
	
		$t_ui = new ca_editor_uis();
		
		// If table supports null types take type_id=null to be  "none" rather than a signal to allow any type of editor
		if (!$vn_type_id && method_exists($t_instance, "getTypeFieldName") && (bool)$t_instance->getFieldInfo($t_instance->getTypeFieldName(), 'IS_NULL')) {
			$vn_type_id = '_NONE_';
		}
		if (!$vn_type_id || !($vn_rc = $t_ui->load($va_uis_by_type[$vn_type_id]))) {
			$va_ui_ids = ca_editor_uis::getAvailableUIs($vn_table_num, $po_request, $vn_type_id, true);

			if (sizeof($va_ui_ids) == 0) { return ca_editor_uis::$s_default_ui_cache[$pm_table_name_or_num.'/'.$pn_type_id] = false; }
			$va_tmp = array_keys($va_ui_ids);
			if ($t_ui->load($va_tmp[0])) {
				return ca_editor_uis::$s_default_ui_cache[$pm_table_name_or_num.'/'.$pn_type_id] = $t_ui;
			}
			return ca_editor_uis::$s_default_ui_cache[$pm_table_name_or_num.'/'.$pn_type_id] = false;
		}
		return ca_editor_uis::$s_default_ui_cache[$pm_table_name_or_num.'/'.$pn_type_id] = $t_ui;
	}
	# ----------------------------------------
	#
	# ----------------------------------------
	/**
	 * Returns list of screens for a given UI. 
	 *
	 * @param int $pn_type_id Optional type to restrict screens to
	 * @param array $pa_options Options include:
	 *		showAll = Include screens that do not have placements. Default is false.
	 *		user_id = User_id to apply access control for
	 *      idsOnly = Return screen_ids only [Default is false]
	 *
	 * @return array List of screens for this user interface
	 */
	public function getScreens($pn_type_id=null, $pa_options=null) {
		if (!($vn_id = $this->getPrimaryKey())) { return false; }
		
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$vn_id}/{$pn_type_id}");
		if (isset(self::$s_screen_cache[$vs_cache_key])) { return self::$s_screen_cache[$vs_cache_key]; }
		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('editor_type')))) { return null; }
		
		if($t_instance instanceof BaseRelationshipModel) {
			$va_types = $t_instance->getRelationshipTypes();
		} else {
			$va_types = $t_instance->getTypeList();	
		}
		$o_db = $this->getDb();
		
		$va_wheres = ["(ceus.ui_id = ?)"];
		$va_params = array((int)$this->getPrimaryKey());
		
	
		if ($pn_type_id > 0) {
			$va_wheres[] = "(ceustr.type_id IS NULL OR ceustr.type_id = ? OR (ceustr.include_subtypes = 1 AND ceustr.type_id IN (?)))";
			$va_params[] = $pn_type_id;
			$va_params[] = caGetAncestorsForItemID($pn_type_id, ['includeSelf' => true]);
		}
	
		
		$t_user = new ca_users();
		if (($vn_user_id = caGetOption('user_id', $pa_options, null)) && ($t_user->load($vn_user_id))) {
			$vs_access_sql = "((ceus.screen_id IN 
					(
						SELECT screen_id 
						FROM ca_editor_ui_screens_x_users
						WHERE
							user_id = ?
					)
				)";
				$va_params[] = $vn_user_id;
				
			$va_groups = $t_user->getUserGroups();
			if (is_array($va_groups) && sizeof($va_groups)) {
				$vs_access_sql .= " OR (ceus.screen_id IN 
					(
						SELECT screen_id 
						FROM ca_editor_ui_screens_x_user_groups
						WHERE
							group_id IN (?)
					)
				)";
				$va_params[] = array_keys($va_groups);
			}
			
			$va_roles = $t_user->getUserRoles();
			if (is_array($va_roles) && sizeof($va_roles)) {
				$vs_access_sql .= " OR (ceus.screen_id IN 
					(
						SELECT screen_id 
						FROM ca_editor_ui_screens_x_roles
						WHERE
							role_id IN (?)
					)
				)";
				$va_params[] = array_keys($va_roles);
			}
			$vs_access_sql .= "
				OR (
					ceus.screen_id NOT IN (
						SELECT screen_id FROM ca_editor_ui_screens_x_users
					)
					AND
					ceus.screen_id NOT IN (
						SELECT screen_id FROM ca_editor_ui_screens_x_user_groups
					)
					AND
					ceus.screen_id NOT IN (
						SELECT screen_id FROM ca_editor_ui_screens_x_roles
					)
				)
			)";
			$va_wheres[] = $vs_access_sql;
		}
		
		$qr_res = $o_db->query("
			SELECT ceus.*, ceusl.*, ceustr.type_id restriction_type_id
			FROM ca_editor_ui_screens ceus
			INNER JOIN ca_editor_ui_screen_labels AS ceusl ON ceus.screen_id = ceusl.screen_id
			LEFT JOIN ca_editor_ui_screen_type_restrictions AS ceustr ON ceus.screen_id = ceustr.screen_id
			WHERE
				 ".join(" AND ", $va_wheres)."
			ORDER BY 
				ceus.rank, ceus.screen_id
		", $va_params);
		
		$va_screens = [];
		
		$vb_ids_only = caGetOption('idsOnly', $pa_options, false);
		
		$va_ids = [];
		while($qr_res->nextRow()) {
		    if ($vb_ids_only) { $va_ids[] = $qr_res->get('screen_id'); continue; }
		    
			if (!$va_screens[$vn_screen_id = $qr_res->get('screen_id')][$vn_screen_locale_id = $qr_res->get('locale_id')]) {
				$va_screens[$vn_screen_id][$vn_screen_locale_id] = $qr_res->getRow();
				if ((bool)$va_screens[$vn_screen_id][$vn_screen_locale_id]['is_default']) {
					$va_screens[$vn_screen_id][$vn_screen_locale_id]['isDefault'] = "◉";
				}
				$va_screens[$vn_screen_id][$vn_screen_locale_id]['numPlacements'] = sizeof($this->getScreenBundlePlacements($vn_screen_id));
			}
			
			if($qr_res->get('restriction_type_id')) {
				$vs_key_to_add = ($t_instance instanceof BaseRelationshipModel) ? 'type_code' : 'name_plural';
				$va_screens[$vn_screen_id][$vn_screen_locale_id]['typeRestrictions'][$qr_res->get('restriction_type_id')] = $va_types[$qr_res->get('restriction_type_id')][$vs_key_to_add];
			}
		}
		if ($vb_ids_only) { return self::$s_screen_cache[$vs_cache_key] = $va_ids; }
		
		$va_screens_with_bundles = null;
		if ((!isset($pa_options['showAll']) || !$pa_options['showAll']) && (sizeof($va_screens))) {
			// Get placements for all screens, so we can filter screens without placements
			$qr_res = $o_db->query("
				SELECT screen_id, placement_id, bundle_name
				FROM ca_editor_ui_bundle_placements
				WHERE
					screen_id IN (?)
			", array(array_keys($va_screens)));
			
			$vs_table = $t_instance->tableName();
			
			$va_screens_with_bundles = [];
			while($qr_res->nextRow()) {
				$vn_screen_id = $qr_res->get('screen_id');
				if (isset($va_screens_with_bundles[$vn_screen_id])) { continue; }
				if (caGetBundleAccessLevel($vs_table, $qr_res->get('bundle_name')) != __CA_BUNDLE_ACCESS_NONE__) {
					$va_screens_with_bundles[$vn_screen_id] = true;
				}
			}
		}
		
		foreach($va_screens as $vn_screen_id => $va_screen_labels_by_locale) {
			if (is_array($va_screens_with_bundles) && !isset($va_screens_with_bundles[$vn_screen_id])) { unset($va_screens[$vn_screen_id]); continue; }
			foreach($va_screen_labels_by_locale as $vn_locale_id => $va_restriction_info) {
				if (!is_array($va_screens[$vn_screen_id][$vn_locale_id]['typeRestrictions'])) { continue; }
				$va_screens[$vn_screen_id][$vn_locale_id]['typeRestrictionsForDisplay'] = join(', ', $va_screens[$vn_screen_id][$vn_locale_id]['typeRestrictions']);
			}
		}
		return self::$s_screen_cache[$vs_cache_key] = caExtractValuesByUserLocale($va_screens);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public static function getAccessForUI($po_request, $pm_ui_id) {
		
		if (!is_numeric($pm_ui_id) || !($t_ui = ca_editor_uis::find(array('ui_id' => $pm_ui_id), array('returnAs' => 'firstModelInstance')))) {
			$t_ui = ca_editor_uis::find(array('editor_code' => $pm_ui_id), array('returnAs' => 'firstModelInstance'));
		}
		if (!$t_ui) { return null; }
		if ($t_ui->get('is_system_ui')) { return __CA_BUNDLE_ACCESS_EDIT__; }
		$vn_ui_id = $t_ui->getPrimaryKey();
		
		if ($vn_user_id = $po_request->getUserID()) {
			$t_user = $po_request->getUser();
			
			// Check for user access
			$qr_users = $t_user->getDb()->query("
				SELECT ui_id, user_id, access 
				FROM ca_editor_uis_x_users
				WHERE
					user_id = ? AND ui_id = ?", array($vn_user_id, $vn_ui_id));
					
			if ($qr_users->nextRow()) {
				return (int)$qr_users->get('access');
			}
			
			// Check for group access
			
			if ((is_array($va_groups = $t_user->getUserGroups())) && sizeof($va_groups)) {
				$qr_groups = $t_user->getDb()->query("
					SELECT ui_id, group_id, access 
					FROM ca_editor_uis_x_user_groups
					WHERE
						group_id IN (?) AND ui_id = ?", array(array_keys($va_groups), $vn_ui_id));
						
				if ($qr_groups->nextRow()) {
					return (int)$qr_groups->get('access');
				}
			}		
			
			// Check for role access
			
			if ((is_array($va_roles = $t_user->getUserRoles())) && sizeof($va_roles)) {
				$qr_roles = $t_user->getDb()->query("
					SELECT ui_id, role_id, access 
					FROM ca_editor_uis_x_roles
					WHERE
						role_id IN (?) AND ui_id = ?", array(array_keys($va_roles), $vn_ui_id));
						
				if ($qr_roles->nextRow()) {
					return (int)$qr_roles->get('access');
				}
			}			
		}
		
		$qr_all = $t_user->getDb()->query("
			SELECT ui_id FROM ca_editor_uis_x_users WHERE ui_id = ?
		", array($vn_ui_id));
		if (!$qr_all->nextRow()) {
			$qr_all = $t_user->getDb()->query("
				SELECT ui_id FROM ca_editor_uis_x_user_groups WHERE ui_id = ?
			", array($vn_ui_id));
			if (!$qr_all->nextRow()) {
				$qr_all = $t_user->getDb()->query("
					SELECT ui_id FROM ca_editor_uis_x_roles WHERE ui_id = ?
				", array($vn_ui_id));
				if (!$qr_all->nextRow()) {
					return __CA_BUNDLE_ACCESS_EDIT__; // no user, group or role access control applied to this screen...  allow editing
				}
			}
		}
		
		return false; // no access
	}
	# ----------------------------------------
	/**
	 *
	 */
	public static function getAccessForScreen($po_request, $pm_screen) {
		$vn_screen_id = intval(str_replace('Screen', '', $pm_screen));
		
		if ($vn_user_id = $po_request->getUserID()) {
			$t_user = $po_request->getUser();
			
			// Check for user access
			$qr_users = $t_user->getDb()->query("
				SELECT screen_id, user_id, access 
				FROM ca_editor_ui_screens_x_users
				WHERE
					user_id = ? AND screen_id = ?", array($vn_user_id, $vn_screen_id));
					
			if ($qr_users->nextRow()) {
				return (int)$qr_users->get('access');
			}
			
			// Check for group access
			
			if ((is_array($va_groups = $t_user->getUserGroups())) && sizeof($va_groups)) {
				$qr_groups = $t_user->getDb()->query("
					SELECT screen_id, group_id, access 
					FROM ca_editor_ui_screens_x_user_groups
					WHERE
						group_id IN (?) AND screen_id = ?", array(array_keys($va_groups), $vn_screen_id));
						
				if ($qr_groups->nextRow()) {
					return (int)$qr_groups->get('access');
				}
			}		
			
			// Check for role access
			
			if ((is_array($va_roles = $t_user->getUserRoles())) && sizeof($va_roles)) {
				$qr_roles = $t_user->getDb()->query("
					SELECT screen_id, role_id, access 
					FROM ca_editor_ui_screens_x_roles
					WHERE
						role_id IN (?) AND screen_id = ?", array(array_keys($va_roles), $vn_screen_id));
						
				if ($qr_roles->nextRow()) {
					return (int)$qr_roles->get('access');
				}
			}			
		}
		
		$qr_all = $t_user->getDb()->query("
			SELECT screen_id FROM ca_editor_ui_screens_x_users WHERE screen_id = ?
		", array($vn_screen_id));
		if (!$qr_all->nextRow()) {
			$qr_all = $t_user->getDb()->query("
				SELECT screen_id FROM ca_editor_ui_screens_x_user_groups WHERE screen_id = ?
			", array($vn_screen_id));
			if (!$qr_all->nextRow()) {
				$qr_all = $t_user->getDb()->query("
					SELECT screen_id FROM ca_editor_ui_screens_x_roles WHERE screen_id = ?
				", array($vn_screen_id));
				if (!$qr_all->nextRow()) {
					$qr_all = $t_user->getDb()->query("
						SELECT ui_id FROM ca_editor_ui_screens WHERE screen_id = ?
					", array($vn_screen_id));
					
					if ($qr_all->nextRow()) {
						// Inherit access from UI screen is part of
						return ca_editor_uis::getAccessForUI($po_request, $qr_all->get('ui_id'));
					}
				}
			}
		}
		
		return false; // no access
	}
	# ----------------------------------------
	/**
	  * Return information about default screen
	  *
	  * @param RequestHTTP $po_request The current request
	  * @param int $pn_type_id Optional type to restrict screens to
	  * @param array $pa_options Options are those available for ca_editor_uis::getScreens()
	  *
	  * @return array Default screen information as an array
	  */
	public function getDefaultScreen($po_request=null, $pn_type_id=null, $pa_options=null) {
		if(!caGetOption('user_id', $pa_options, null) && $po_request) { $pa_options['user_id'] = $po_request->getUserID(); }
		$va_screens = $this->getScreens($pn_type_id, $pa_options);
		
		foreach($va_screens as $vn_screen_id => $va_screen) {
			if (isset($va_screen['isDefault']) && $va_screen['isDefault']) {
				return $va_screen;
			}
		}
		return array_shift($va_screens);
	}
	# ----------------------------------------
	/**
	  * Return number of screens configured for currently loaded UI 
	  *
	  * @param int $pn_type_id Optional type_id used when and per-screen type restrictions are enforced; if not set (the default) then all screens are returned - no type restrictions are enforced.
	  * @param array $pa_options Options include:
	  *		user_id = User_id to apply access control for
	  * @return int Number of screens configured for the current UI
	  */
	public function getScreenCount($pn_type_id=null, $pa_options=null) {
		if (!$this->getPrimaryKey()) { return 0; }
		if(!caGetOption('user_id', $pa_options, null) && $po_request) { $pa_options['user_id'] = $po_request->getUserID(); }
		$pa_options['showAll'] = true;
		
		$vs_opts_md5 = md5(print_r(array('showAll' => true), true));
		
		return sizeof($this->getScreens($pn_type_id, $pa_options));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getScreenBundlePlacements($pm_screen, $pn_type_id=null, $pa_options=null) {
		if (!($vn_id = $this->getPrimaryKey())) { return false; }
		
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$vn_id}/{$pm_screen}/{$pn_type_id}");
		
		if (isset(self::$s_screen_bundle_cache[$vs_cache_key])) { return self::$s_screen_bundle_cache[$vs_cache_key]; }
		
		$o_db = $this->getDb();
		
		$vn_screen_id = intval(str_replace('Screen', '', $pm_screen));
		$va_params = [(int)$this->getPrimaryKey(), $vn_screen_id];
		
		$vs_bundle_list_sql = '';
		if ($va_bundle_list = caGetOption('bundleList', $pa_options, null)) {
		    if (!is_array($va_bundle_list)) { $va_bundle_list = [$va_bundle_list]; }
		    if (sizeof($va_bundle_list = array_filter($va_bundle_list, "strlen"))) {
		        $vs_bundle_list_sql = " AND (ceuibp.bundle_name IN (?))";
		        $va_params[] = $va_bundle_list;
		    }
		}
		
		
		$va_bundles = [];
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_editor_ui_bundle_placements ceuibp
			INNER JOIN ca_editor_ui_screens AS ceus ON ceus.screen_id = ceuibp.screen_id
			WHERE
				(ceus.ui_id = ?) AND (ceuibp.screen_id = ?) {$vs_bundle_list_sql}
			ORDER BY 
				ceuibp.rank
		", $va_params);
		
		$va_placements = [];
		while ($qr_res->nextRow()) {
			$va_tmp = $qr_res->getRow();
			$va_tmp['settings'] = $qr_res->getVars('settings');
			
			$va_types = [];
			if (isset($va_tmp['settings']['bundleTypeRestrictions'])) {
				$va_types = $va_tmp['settings']['bundleTypeRestrictions'];
				if ($va_types && !is_array($va_types)) { $va_types = [$va_types]; }
				
				$va_types = caMakeTypeIDList($this->get('editor_type'), $va_types, ['dontIncludeSubtypesInTypeRestriction' => !(isset($va_tmp['settings']['bundleTypeRestrictionsIncludeSubtypes']) && (bool)$va_tmp['settings']['bundleTypeRestrictionsIncludeSubtypes'])]);
			}

			// check bundle-placement type restrictions if set
			if (
				$pn_type_id && is_array($va_types) && sizeof($va_types) &&
				!in_array($pn_type_id, $va_types)
			) { continue; }
				
			$va_placements[] = $va_tmp;
		}
		
		return self::$s_screen_bundle_cache[$vs_cache_key] = $va_placements;
	}
	# ----------------------------------------
	/**
	 * Returns screen name for the first screen in the currently loaded UI 
	 * that contains the bundle named by $ps_bundle_name
	 *
	 * @param string $ps_bundle_name
	 * @param RequestHTTP $po_request
	 * @param array $pa_options Options include:
	 *		user_id = User_id to apply access control for
	 */
	public function getScreenWithBundle($ps_bundle_name, $po_request=null, $pa_options=null) {
		if (!$this->getPrimaryKey()) { return null; }
		if(!caGetOption('user_id', $pa_options, null) && $po_request) { $pa_options['user_id'] = $po_request->getUserID(); }
		
		foreach($this->getScreens(null, $pa_options) as $va_screen) {
			$vn_screen_id = $va_screen['screen_id'];
			$va_placements = $this->getScreenBundlePlacements('Screen'.$vn_screen_id);
			
			foreach($va_placements as $va_placement) {
				if ($va_placement['bundle_name'] === $ps_bundle_name) {
					return 'Screen'.$vn_screen_id;
				}
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns data for each placement in the currently loaded UI 
	 * for the bundle named by $ps_bundle_name. Searches all screens of
	 * the UI.
	 *
	 * @param string $ps_bundle_name The bundle name
	 * @param RequestHTTP $po_request The current request
	 * @param array $pa_options Options include:
	 *		user_id = User_id to apply access control for
	 *
	 * @return array A list of placement info, one for each placement in the editor
	 */
	public function getPlacementsForBundle($ps_bundle_name, $po_request=null, $pa_options=null) {
		if (!($vn_id = $this->getPrimaryKey())) { return null; }
	    if (!is_array($pa_options)) { $pa_options = []; }
	    
	    $vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$vn_id}/{$ps_bundle_name}");
		
		if (isset(self::$s_placements_for_bundle_cache[$vs_cache_key])) { return self::$s_placements_for_bundle_cache[$vs_cache_key]; }


		if(!caGetOption('user_id', $pa_options, null) && $po_request) { $pa_options['user_id'] = $po_request->getUserID(); }
	
		$va_found = [];
		foreach($this->getScreens(null, array_merge($pa_options, ['idsOnly' => true])) as $vn_screen_id) {
			$va_placements = $this->getScreenBundlePlacements('Screen'.$vn_screen_id, null, ['bundleList' => [$ps_bundle_name]]);
			
			foreach($va_placements as $va_placement) {
				if ($va_placement['bundle_name'] === $ps_bundle_name) {
					$va_found[] = $va_placement;
				}
			}
		}
		return self::$s_placements_for_bundle_cache[$vs_cache_key] = $va_found;
	}
	# ----------------------------------------
	/**
	 *	Return navigation configuration fragment suitable for insertion into the navigation.conf structure.
	 *	Can be used by lib/AppNavigation to dynamically insert navigation for screens into navigation tree
	 *
	 * @param RequestHTTP $po_request
	 * @param int $pn_type_id
	 * @param string $ps_module_path
	 * @param string $ps_controller
	 * @param string $ps_action
	 * @param array $pa_parameters
	 * @param array $pa_requirements
	 * @param bool $pb_disable_options
	 * @param array $pa_options	Values to include in returned array for each screen. Values are returned as-is. Specific options also have the following effects:
	 *		returnTypeRestrictions = return list of type restrictions for screen. Default is false. 
	 *		restrictToTypes = 
	 *		user_id = User_id to apply access control for
	 * @return array
	 */
	public function getScreensAsNavConfigFragment($po_request, $pn_type_id, $ps_module_path, $ps_controller, $ps_action, $pa_parameters, $pa_requirements, $pb_disable_options=false, $pa_options=null) {
		if(!caGetOption('user_id', $pa_options, null) && $po_request) { $pa_options['user_id'] = $po_request->getUserID(); }
		if (!($va_screens = $this->getScreens($pn_type_id, $pa_options))) { return false; }
		
		if (is_array($restrict_to_types = caGetOption('restrictToTypes', $pa_options, null)) && sizeof($restrict_to_types)) {
		    $restrict_to_types = caMakeTypeIDList($this->get('editor_type'), $restrict_to_types);
		}
		$va_nav = [];
		$vn_default_screen_id = null;
		foreach($va_screens as $va_screen) {
			$va_screen_restrictions = $va_screen['typeRestrictions'];
		    if(is_array($va_screen_restrictions)) { $va_screen_restrictions = caMakeTypeIDList($this->get('editor_type'), array_keys($va_screen_restrictions)); }
			
			if(is_array($restrict_to_types) && is_array($va_screen_restrictions) && (sizeof($va_screen_restrictions) > 0)) {
				$vb_skip = true;
				foreach($restrict_to_types as $vn_res_type_id) {
					if (in_array($vn_res_type_id, $va_screen_restrictions)) {
						$vb_skip = false;
						break;
					}
				}
				if ($vb_skip) { continue; }
			}
			
			if (!$vn_default_screen_id) { $vn_default_screen_id = $va_screen['screen_id']; }
			
			$va_nav['screen_'.$va_screen['screen_id']] = array(
				'displayName' => $va_screen['name'],
				"default" => array( 
					'module' => $ps_module_path, 
					'controller' => $ps_controller,
					'action' => $ps_action.'/Screen'.$va_screen['screen_id']
				),
				"useActionInPath" => 0,
				"useActionExtraInPath" => 1,
				"disabled" => $pb_disable_options,
				"requires" => $pa_requirements,
				"parameters" => $pa_parameters
			);
			
			if(isset($pa_options['returnTypeRestrictions']) && $pa_options['returnTypeRestrictions']) {
				$va_nav['screen_'.$va_screen['screen_id']]['typeRestrictions'] = $va_screen['typeRestrictions'];
			}
			
			if (is_array($pa_options)) {
				$va_nav['screen_'.$va_screen['screen_id']] = array_merge($va_nav['screen_'.$va_screen['screen_id']], $pa_options);
			}
			if ($va_screen['is_default']) { $vn_default_screen_id = $va_screen['screen_id']; }
		}
		return array('fragment' => $va_nav, 'defaultScreen' => 'Screen'.$vn_default_screen_id);
	}
	# ----------------------------------------
	# Static
	# ----------------------------------------
	/**
	 * Get simple UI list (restricted by user)
	 */
	public static function getUIList($pm_table=null, $pn_user_id=null, $pn_type_id=null){
		$pn_table_num = Datamodel::getTableNum($pm_table);
		if ($pn_user_id) { $vs_key = $pn_user_id; } else { $vs_key = "_all_"; }
		if (ca_editor_uis::$s_available_ui_cache[$pm_table.'/'.$pn_user_id]) { return ca_editor_uis::$s_available_ui_cache[$pm_table.'/'.$pn_user_id]; }
		$o_db = new Db();
		
		$va_wheres = $va_params = [];
		
		if (!is_array($va_type_list = caMakeTypeIDList($pn_table_num, array($pn_type_id)))) { $va_type_list = []; }
		if (!sizeof($va_type_list)) { $va_type_list = array($pn_type_id); }
		
		if ($pn_table_num) {
			$va_wheres[] = '(ceui.editor_type = ?)';
			$va_params[] = (int)$pn_table_num;
		}
		if ($pn_type_id) { 
			$va_wheres[] = "(ceui.type_id IS NULL OR ceutr.type_id = ? OR (ceutr.include_subtypes = 1 AND ceutr.type_id IN (?)))"; 
			$va_params[] = (int)$pn_type_id; $va_params[] = $va_type_list;
		}
		
		if ($pn_user_id) {
			$va_wheres[] = "(
				(ceui.user_id = ?) OR
				(ceui.is_system_ui = 1)
			)";
			$va_params[] = (int)$pn_user_id;
			
			$qr_res = $o_db->query("
				SELECT ceui.ui_id, ceuil.name, ceuil.description, ceuil.locale_id, ceui.editor_type, ceui.is_system_ui, ceui.editor_code, ceutr.type_id restriction_type_id
				FROM ca_editor_uis ceui
				LEFT JOIN ca_editor_ui_type_restrictions AS ceutr ON ceui.ui_id = ceutr.ui_id
				INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
				WHERE
					".join(" AND ", $va_wheres)."
				ORDER BY ceuil.name
			", $va_params);
		} else {
			$qr_res = $o_db->query("
				SELECT ceui.ui_id, ceuil.name, ceuil.description, ceuil.locale_id, ceui.editor_type, ceui.is_system_ui, ceui.editor_code, ceutr.type_id restriction_type_id
				FROM ca_editor_uis ceui
				LEFT JOIN ca_editor_ui_type_restrictions AS ceutr ON ceui.ui_id = ceutr.ui_id
				INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
				".((sizeof($va_wheres) ? "WHERE " : "").join(" AND ", $va_wheres))."
				ORDER BY ceuil.name
			", $va_params);
		}
		
		$va_uis = [];
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_uis[$va_row['ui_id']][$va_row['locale_id']] = $va_row;
		}
		
		$va_uis = caExtractValuesByUserLocale($va_uis);
		return ca_editor_uis::$s_available_ui_cache[$pm_table.'/'.$pn_user_id] = ca_editor_uis::$s_available_ui_cache[$pn_table_num.'/'.$pn_user_id] = $va_uis;
	}
	# ----------------------------------------
	/**
	 * Get UI count
	 */
	public static function getUICount($pn_table_num=null, $pn_user_id=null){
		return sizeof(ca_editor_uis::getUIList($pn_table_num, $pn_user_id));
	}
	# ----------------------------------------
	/**
	 * Returns a list of ca_editor_uis ui_ids for all
	 * user interfaces that the user can access for the specified type
	 */
	public static function getAvailableUIs($pn_table_num, $po_request, $pn_type_id=null) {
		$pn_user_id = $po_request->getUserID();
		if (ca_editor_uis::$s_available_ui_cache[$pn_table_num.'/'.$pn_type_id.'/'.$pn_user_id]) { return ca_editor_uis::$s_available_ui_cache[$pn_table_num.'/'.$pn_type_id.'/'.$pn_user_id]; }
		
		if ($pn_type_id) {
			$va_ui_list = $po_request->user->_getUIListByType($pn_table_num);
			if (!is_array($va_uis = $va_ui_list[$pn_type_id])) { $va_uis = []; }
			if (is_array($va_ui_list['__all__'])) {
				$va_uis = $va_uis + $va_ui_list['__all__'];
			}
		} else {
			$va_uis = $po_request->user->_getUIList($pn_table_num);
		}
		
		return ca_editor_uis::$s_available_ui_cache[$pn_table_num.'/'.$pn_type_id.'/'.$pn_user_id] = $va_uis;
	}
	# ------------------------------------------------------
	/**
 	 * Returns a list of row_ids for the current set with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			user_id = the user_id of the current user; used to determine which sets the user has access to
	 * @return array Array keyed on row_id with values set to ranks for each item. If the set contains duplicate row_ids then the list will only have the largest rank. If you have sets with duplicate rows use getItemRanks() instead
	 */
	public function getScreenIDRanks($pa_options=null) {
		if(!($vn_ui_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT cauis.screen_id, cauis.rank
			FROM ca_editor_ui_screens cauis
			WHERE
				cauis.ui_id = ? AND cauis.parent_id IS NOT NULL
			ORDER BY 
				cauis.rank ASC
		", (int)$vn_ui_id);
		$va_screens = [];
		
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_screens[$qr_res->get('screen_id')] = $qr_res->get('rank');
		}
		return $va_screens;
	}
	# ------------------------------------------------------
	/**
	 * Sets order of screens in the currently loaded ui to the order of screen_ids as set in $pa_screen_ids
	 *
	 * @param array $pa_screen_ids A list of screen_ids in the ui, in the order in which they should be displayed in the ui
	 * @param array $pa_options An optional array of options. Supported options include:
	 *			NONE
	 * @return array An array of errors. If the array is empty then no errors occurred
	 */
	public function reorderScreens($pa_screen_ids, $pa_options=null) {
		if (!($vn_ui_id = $this->getPrimaryKey())) {	
			return null;
		}
		
		$va_screen_ranks = $this->getScreenIDRanks($pa_options);	// get current ranks
		
		$vn_i = 0;
		
		$vb_web_set_transaction = false;
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$vb_web_set_transaction = true;
		} else {
			$o_trans = $this->getTransaction();
		}
		
		$t_screen = new ca_editor_ui_screens();
		$t_screen->setTransaction($o_trans);
		$t_screen->setMode(ACCESS_WRITE);
		$va_errors = [];
		
		
		// delete rows not present in $pa_screen_ids
		$va_to_delete = [];
		foreach($va_screen_ranks as $vn_screen_id => $va_rank) {
			if (!in_array($vn_screen_id, $pa_screen_ids)) {
				if ($t_screen->load(array('ui_id' => $vn_ui_id, 'screen_id' => $vn_screen_id))) {
					$t_screen->delete(true);
				}
			}
		}
		
		
		// rewrite ranks
		foreach($pa_screen_ids as $vn_rank => $vn_screen_id) {
			if (isset($va_screen_ranks[$vn_screen_id]) && $t_screen->load(array('ui_id' => $vn_ui_id, 'screen_id' => $vn_screen_id))) {
				if ($va_screen_ranks[$vn_screen_id] != $vn_rank) {
					$t_screen->set('rank', $vn_rank);
					$t_screen->update();
				
					if ($t_screen->numErrors()) {
						$va_errors[$vn_screen_id] = _t('Could not reorder screen %1: %2', $vn_screen_id, join('; ', $t_screen->getErrors()));
					}
				}
			} else {
				// add screen to UI
			//	$this->addItem($vn_screen_id, null, $vn_user_id, $vn_rank);
			}
		}
		
		if(sizeof($va_errors)) {
			if ($vb_web_set_transaction) { $o_trans->rollback(); }
		} else {
			if ($vb_web_set_transaction) { $o_trans->commit(); }
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function addScreen($ps_name, $pn_locale_id, $ps_idno, $ps_color='000000', $is_default=false) {
		if (!$this->getPrimaryKey()) { return false; }
		
		$t_screen = new ca_editor_ui_screens();
		$t_screen->setMode(ACCESS_WRITE);
		$t_screen->set('idno', $ps_idno);
		$t_screen->set('ui_id', $this->getPrimaryKey());
		$t_screen->set('color', $ps_color);
		$t_screen->set('is_default', (bool)$is_default ? 1 : 0);
		$t_screen->insert();
		
		if ($t_screen->numErrors()) {
			$this->errors = $t_screen->errors;
			return false;
		}
		
		$t_screen->addLabel(
			array('name' => $ps_name), $pn_locale_id, null, true
		);
		
		if ($t_screen->numErrors()) {
			$this->errors = $t_screen->errors;
			$t_screen->delete(true);
			return false;
		}
		
		return $t_screen;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function removeScreen($pn_screen_id) {
		if (!($vn_ui_id = $this->getPrimaryKey())) { return false; }
		$t_screen = new ca_editor_ui_screens();
		
		if (!$t_screen->load(array('ui_id' => $vn_ui_id, 'screen_id' => $pn_screen_id))) { return false; }
		$t_screen->setMode(ACCESS_WRITE);
		return $t_screen->delete(true);
	}
	# ----------------------------------------
	# Type restrictions
	# ----------------------------------------
	/**
	 * Adds restriction (a binding between the ui and item type)
	 *
	 * @param int $pn_type_id the type
	 * @param array $pa_settings Options include:
	 *		includeSubtypes = automatically expand type restriction to include sub-types. [Default is false]
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function addTypeRestriction($pn_type_id, $pa_settings=null) {
		if (!($vn_ui_id = $this->getPrimaryKey())) { return null; }		// UI must be loaded
		if (!is_array($pa_settings)) { $pa_settings = []; }
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('editor_type')))) { return false; }

		if ($t_instance instanceof BaseRelationshipModel) { // interstitial type restriction incoming
			$va_rel_type_list = $t_instance->getRelationshipTypes();
			if(!isset($va_rel_type_list[$pn_type_id])) { return false; }
		} elseif($t_instance instanceof ca_representation_annotations) { // annotation type restriction
			$o_annotation_type_conf = Configuration::load(Configuration::load()->get('annotation_type_config'));
			$vb_ok = false;
			foreach($o_annotation_type_conf->get('types') as $vs_type_code => $va_type_info) {
				if(isset($va_type_info['typeID']) && ($va_type_info['typeID'] == $pn_type_id)) {
					$vb_ok = true;
					break;
				}
			}

			if(!$vb_ok) { return false; } // couldn't find type id
		} else { // "normal" (list-based) type restriction
			$va_type_list = $t_instance->getTypeList();
			if (!isset($va_type_list[$pn_type_id])) { return false; }
		}
		
		$t_restriction = new ca_editor_ui_type_restrictions();
		$t_restriction->setMode(ACCESS_WRITE);
		$t_restriction->set('table_num', $this->get('editor_type'));
		$t_restriction->set('type_id', $pn_type_id);
		$t_restriction->set('include_subtypes', caGetOption('includeSubtypes', $pa_settings, 0));
		$t_restriction->set('ui_id', $this->getPrimaryKey());
		
		unset($pa_settings['includeSubtypes']);
		foreach($pa_settings as $vs_setting => $vs_setting_value) {
			$t_restriction->setSetting($vs_setting, $vs_setting_value);
		}
		$t_restriction->insert();
		
		if ($t_restriction->numErrors()) {
			$this->errors = $t_restriction->errors();
			return false;
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Edit settings for an existing type restriction on the currently loaded row
	 *
	 * @param int $pn_restriction_id
	 * @param int $pn_type_id New type for relationship
	 */
	public function editTypeRestriction($pn_restriction_id, $pa_settings=null) {
		if (!($vn_ui_id = $this->getPrimaryKey())) { return null; }		// UI must be loaded
		$t_restriction = new ca_editor_ui_type_restrictions($pn_restriction_id);
		if ($t_restriction->isLoaded()) {
			$t_restriction->setMode(ACCESS_WRITE);
			$t_restriction->set('include_subtypes', caGetOption('includeSubtypes', $pa_settings, 0));
			$t_restriction->update();
			if ($t_restriction->numErrors()) {
				$this->errors = $t_restriction->errors();
				return false;
			}
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Sets restrictions for currently loaded ui
	 *
	 * @param array $pa_type_ids list of types to restrict to
	 * @param array $pa_options Options include:
	 *		includeSubtypes = Automatically include subtypes for all set type restrictions. [Default is false]
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function setTypeRestrictions($pa_type_ids, $pa_options=null) {
		if (!($vn_ui_id = $this->getPrimaryKey())) { return null; }		// UI must be loaded
		if (!is_array($pa_type_ids)) {
			if (is_numeric($pa_type_ids)) { 
				$pa_type_ids = array($pa_type_ids); 
			} else {
				$pa_type_ids = [];
			}
		}
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('editor_type')))) { return false; }

		if ($t_instance instanceof BaseRelationshipModel) { // interstitial type restrictions
			$va_type_list = $t_instance->getRelationshipTypes();
		} else { // "normal" (list-based) type restrictions
			$va_type_list = $t_instance->getTypeList();
		}
		
		$va_current_restrictions = $this->getTypeRestrictions();
		$va_current_type_ids = [];
		foreach($va_current_restrictions as $vn_i => $va_restriction) {
			$va_current_type_ids[$va_restriction['type_id']] = $va_restriction['restriction_id'];
		}
		
		foreach($va_type_list as $vn_type_id => $va_type_info) {
			if(in_array($vn_type_id, $pa_type_ids)) {
				// need to set
				if(!isset($va_current_type_ids[$vn_type_id])) {
					$this->addTypeRestriction($vn_type_id, $pa_options);
				} else {
					$this->editTypeRestriction($va_current_type_ids[$vn_type_id], $pa_options);
				}
			} elseif(isset($va_current_type_ids[$vn_type_id])) {	
				// need to unset
				$this->removeTypeRestriction($vn_type_id);
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Remove restriction from currently loaded ui for specified type
	 *
	 * @param int $pn_type_id The type of the restriction
	 * @return bool True on success, false on error, null if no screen is loaded
	 */
	public function removeTypeRestriction($pn_type_id=null) {
		if (!($vn_ui_id = (int)$this->getPrimaryKey())) { return null; }		// ui must be loaded

		$va_params = ['ui_id' => $vn_ui_id];
		if ((int)$pn_type_id > 0) { $va_params['type_id'] = (int)$pn_type_id; }

		if (is_array($va_uis = ca_editor_ui_type_restrictions::find($va_params, ['returnAs' => 'modelInstances']))) {
			foreach($va_uis as $t_ui) {
				$t_ui->setMode(ACCESS_WRITE);
				$t_ui->delete(true);
				if ($t_ui->numErrors()) {
					$this->errors = $t_ui->errors();
					return false;
				}
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Remove all type restrictions from loaded ui
	 *
	 * @return bool True on success, false on error, null if no screen is loaded 
	 */
	public function removeAllTypeRestrictions() {
		return $this->removeTypeRestriction();
	}
	# ----------------------------------------
	/**
	 * Return restrictions for currently loaded ui
	 *
	 * @param int $pn_type_id Type to limit returned restrictions to; if omitted or null then all restrictions are returned
	 * @return array A list of restrictions, false on error or null if no ui is loaded
	 */
	public function getTypeRestrictions($pn_type_id=null) {
		if (!($vn_ui_id = (int)$this->getPrimaryKey())) { return null; }
		
		$va_params = ['ui_id' => $vn_ui_id];
		if ((int)$pn_type_id > 0) { $va_params['type_id'] = (int)$pn_type_id; }

		return ca_editor_ui_type_restrictions::find($va_params, ['returnAs' => 'arrays']);
	}
	# ------------------------------------------------------
	# Bundles
	# ------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of screens in the currently loaded UI
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getScreenHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code) {
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_ui', $this);		
		$o_view->setVar('t_screen', new ca_editor_ui_screens());		
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('placement_code', $ps_placement_code);
		$o_view->setVar('request', $po_request);
		
		if ($this->getPrimaryKey()) {
			// We don't filter screens based upon user access in the configuration interface
			$o_view->setVar('screens', $this->getScreens(null, array('showAll' => true)));
		} else {
			$o_view->setVar('screens', []);
		}
		
		return $o_view->render('ca_editor_ui_screens.php');
	}
	# ----------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of type restriction in the currently loaded ui
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getTypeRestrictionsHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_ui', $this);
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);
		$o_view->setVar('request', $po_request);
		
		$va_type_restrictions = $this->getTypeRestrictions();
		$va_restriction_type_ids = [];
		$vb_include_subtypes = false;
		if (is_array($va_type_restrictions)) {
			foreach($va_type_restrictions as $vn_i => $va_restriction) {
				$va_restriction_type_ids[] = $va_restriction['type_id'];
				if ($va_restriction['include_subtypes'] && !$vb_include_subtypes) { $vb_include_subtypes = true; }
			}
		}
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($vn_table_num = $this->get('editor_type')))) { return null; }

		$vs_subtype_element = caProcessTemplate($this->getAppConfig()->get('form_element_display_format_without_label'), [
			'ELEMENT' => _t('Include subtypes?').' '.caHTMLCheckboxInput('type_restriction_include_subtypes', ['value' => '1', 'checked' => $vb_include_subtypes])
		]);
		
		if($t_instance instanceof BaseRelationshipModel) { // interstitial
			$o_view->setVar('type_restrictions', $t_instance->getRelationshipTypesAsHTMLSelect($t_instance->getLeftTableName(),null,null,array('name' => 'type_restrictions[]', 'multiple' => 1, 'size' => 5), array('values' => $va_restriction_type_ids)).$vs_subtype_element);
		} elseif($t_instance instanceof ca_representation_annotations) { // config based
			$o_annotation_type_conf = Configuration::load(Configuration::load()->get('annotation_type_config'));
			$va_annotation_type_select_list = [];
			foreach($o_annotation_type_conf->get('types') as $vs_type_code => $va_type_info) {
				if(!isset($va_type_info['typeID'])) { continue; }
				$va_annotation_type_select_list[$vs_type_code] = $va_type_info['typeID'];
			}

			$o_view->setVar('type_restrictions', caHTMLSelect('type_restrictions[]', $va_annotation_type_select_list, array('multiple' => 1, 'size' => 5), array('value' => 0, 'values' => $va_restriction_type_ids)).$vs_subtype_element);
		} else { // list-based
			$o_view->setVar('type_restrictions', $t_instance->getTypeListAsHTMLFormElement('type_restrictions[]', array('multiple' => 1, 'height' => 5), array('value' => 0, 'values' => $va_restriction_type_ids)).$vs_subtype_element);
		}
	
		return $o_view->render('ca_editor_ui_type_restrictions.php');
	}
	# ----------------------------------------
	public function saveTypeRestrictionsFromHTMLForm($po_request, $ps_form_prefix, $ps_placement_code) {
		if (!$this->getPrimaryKey()) { return null; }
		
		return $this->setTypeRestrictions($po_request->getParameter('type_restrictions', pArray), ['includeSubtypes' => $po_request->getParameter('type_restriction_include_subtypes', pInteger)]);
	}
	# ----------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_path_to_template
	 * @param string $ps_screen_name
	 * @param array $pa_options Options include"
	 *		replace = replace any existing placements. [Default is true]
	 *		restrictions = an array of type restrictions to apply to the sdreen. [Default is null]
	 *
	 * @return bool
	 */
	public function createScreenFromPawtucketDetailTemplate($ps_path_to_template, $ps_screen_name, array $pa_options=null) {
		global $g_ui_locale;
		if (!($vn_ui_id = $this->getPrimaryKey())) { return null; }
		if(!($vs_template_content = file_get_contents($ps_path_to_template))) { return null; }
		$va_tags = caGetTemplateTags($vs_template_content, ['ignoreQuotes' => true]);
		
		$pb_replace = caGetOption('replace', $pa_options, true);
		
		$vs_screen_code = trim(preg_replace("![^A-Za-z0-9_]+!", "_", mb_strtolower($ps_screen_name)));
		
		// load screen?
		if (
			!($t_screen = ca_editor_ui_screens::find(['idno' => $ps_screen_name], ['returnAs' => 'firstModelInstance']))
			&&
			!($t_screen = ca_editor_ui_screens::find(['idno' => $vs_screen_code], ['returnAs' => 'firstModelInstance']))
		) {
			// create screen
			if (!($t_screen = $this->addScreen($ps_screen_name, $g_ui_locale, $vs_screen_code))) {
				return false;
			}
		}
		
		$va_bundles = [];
		
		$t_instance = Datamodel::getInstanceByTableNum($this->get('editor_type'), true);
		$vs_table = $t_instance->tableName();
		
		$pa_type_restriction_ids = null;
		if(is_array($pa_type_restrictions = caGetOption('restrictions', $pa_options, null)) && sizeof($pa_type_restrictions)) {
			$pa_type_restriction_ids = caMakeTypeIDList($vs_table, $pa_type_restrictions);
			$pa_type_restrictions = caMakeTypeList($vs_table, $pa_type_restriction_ids);
		}
		
		if ($pb_replace) { $t_screen->removeAllPlacements(); }
		$t_screen->removeAllTypeRestrictions();
		
		$vn_type_restriction_id = $vs_type_restriction = null;
		
		if (is_array($pa_type_restriction_ids) && sizeof($pa_type_restriction_ids)) {
			foreach($pa_type_restriction_ids as $vn_type_restriction_id) {
				$t_screen->addTypeRestriction($vn_type_restriction_id);
			}
		} elseif(preg_match("!^{$vs_table}_(.*)_html$!", pathinfo($ps_path_to_template, PATHINFO_FILENAME), $m) && ($vn_type_restriction_id = $t_instance->getTypeIDForCode($vs_type_restriction = $m[1]))) {
			$t_screen->addTypeRestriction($vn_type_restriction_id);
			$pa_type_restrictions = [$vs_type_restriction];
		}
		
		foreach($va_tags as $vs_tag) {
			$vs_tag = array_shift(explode("%", $vs_tag));
			$va_tag = explode('.', $vs_tag);
		
			if ($va_tag[0] == $vs_table) {
				if ($t_instance->isValidBundle($vs_tag)) {	//related
					$va_bundles[$vs_tag] = preg_replace("![^A-Za-z0-9_]+!", "_", $vs_tag);
				} elseif($t_instance->isValidBundle($va_tag[1])) {	// label?
					$va_bundles[$va_tag[1]] = preg_replace("![^A-Za-z0-9_]+!", "_", $va_tag[1]);
				} elseif($t_instance->hasElement($va_tag[1])) {	// metadata element
					$va_bundles["ca_attribute_".$va_tag[1]] = preg_replace("![^A-Za-z0-9_]+!", "_", $va_tag[0].'_'.$va_tag[1]);
				}
			} elseif (Datamodel::getTableNum($va_tag[0])) {
				// related?
				if ($t_instance->isValidBundle($va_tag[0])) {	//related
					$va_bundles[$va_tag[0]] = $va_tag[0];
				}
			}
			
		}
		
		foreach($va_bundles as $vs_bundle_name => $vs_placement_code) {
			$t_screen->addPlacement($vs_bundle_name, "screen_{$vn_screen_id}_{$vs_bundle_name}", [], $pn_rank=null, $pa_options=null);
		}
		
		$va_bundles_for_return = array_map(function($v) {
			return str_replace("ca_attribute_", "", $v);
		}, array_keys($va_bundles));
		
		return [	
			'screen' => $ps_screen_name, 'screen_idno' => $vs_screen_code, 
			'type_restrictions' => $pa_type_restrictions,
			'replace' => $pb_replace, 'bundles' => $va_bundles_for_return
		];
	}
	# ----------------------------------------
}
