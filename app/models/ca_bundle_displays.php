<?php
/** ---------------------------------------------------------------------
 * app/models/ca_bundle_displays.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ca/BundlableLabelableBaseModelWithAttributes.php'); 
require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php'); 
require_once(__CA_MODELS_DIR__.'/ca_bundle_display_placements.php'); 
require_once(__CA_MODELS_DIR__.'/ca_bundle_displays_x_user_groups.php'); 
require_once(__CA_MODELS_DIR__.'/ca_bundle_display_type_restrictions.php'); 
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php'); 
require_once(__CA_MODELS_DIR__.'/ca_lists.php'); 
require_once(__CA_LIB_DIR__."/core/Parsers/htmlpurifier/HTMLPurifier.standalone.php");

define('__CA_BUNDLE_DISPLAY_NO_ACCESS__', 0);
define('__CA_BUNDLE_DISPLAY_READ_ACCESS__', 1);
define('__CA_BUNDLE_DISPLAY_EDIT_ACCESS__', 2);

BaseModel::$s_ca_models_definitions['ca_bundle_displays'] = array(
 	'NAME_SINGULAR' 	=> _t('display list'),
 	'NAME_PLURAL' 		=> _t('display lists'),
	'FIELDS' 			=> array(
		'display_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this display')
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for User'
		),
		'is_system' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is system display?'), 'DESCRIPTION' => _t('If set, display will be available to all users as part of the system-wide display list.'),
				'REQUIRES' => array('is_administrator')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
				'DONT_USE_AS_BUNDLE' => true,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Display type'), 'DESCRIPTION' => _t('Indicates type of item display is used for.'),
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
					//_t('lists') => 36,
					_t('list items') => 33
				)
		),
		'display_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Display code'), 'DESCRIPTION' => _t('Unique alphanumeric identifier for this display.'),
				'UNIQUE_WITHIN' => array()
				//'REQUIRES' => array('is_administrator')
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Display settings')
		)
	)
);

global $_ca_bundle_displays_settings;
$_ca_bundle_displays_settings = array(		// global
	'show_empty_values' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 4, 'height' => 1,
		'takesLocale' => false,
		'default' => '1',
		'label' => _t('Display empty values?'),
		'description' => _t('If checked all values will be displayed, whether there is content for them or not.')
	)
);
	
class ca_bundle_displays extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_bundle_displays';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'display_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('display_id');

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
	protected $ORDER_BY = array('display_id');

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
	protected $USERS_RELATIONSHIP_TABLE = 'ca_bundle_displays_x_users';
	protected $USER_GROUPS_RELATIONSHIP_TABLE = 'ca_bundle_displays_x_user_groups';
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_bundle_display_labels';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'display_code';	// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;			// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# cache for haveAccessToDisplay()
	static $s_have_access_to_display_cache = array();
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	
	static $s_placement_list_cache;		// cache for getPlacements()
	
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		// Filter list of tables display can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_bundle_displays']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caFilterTableList(BaseModel::$s_ca_models_definitions['ca_bundle_displays']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);
		
		global $_ca_bundle_displays_settings;
		parent::__construct($pn_id);
		
		//
		$this->SETTINGS = new ModelSettings($this, 'settings', $_ca_bundle_displays_settings);
		
	}
	# ------------------------------------------------------
	/**
	 * @param array $pa_options
	 *		duplicate_subitems
	 */
	public function duplicate($pa_options=null) {
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction($o_t = new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			$o_t = $this->getTransaction();
		}
		
		if ($t_dupe = parent::duplicate($pa_options)) {
			$vb_duplicate_subitems = isset($pa_options['duplicate_subitems']) && $pa_options['duplicate_subitems'];
		
			if ($vb_duplicate_subitems) { 
				// Try to dupe related ca_bundle_display_placements rows
				$o_db = $this->getDb();
				
				$qr_res = $o_db->query("
					SELECT *
					FROM ca_bundle_display_placements
					WHERE display_id = ?
				", (int)$this->getPrimaryKey());
				
				$va_items = array();
				while($qr_res->nextRow()) {
					$va_items[$qr_res->get('placement_id')] = $qr_res->getRow();
				}
				
				foreach($va_items as $vn_item_id => $va_item) {
					$t_item = new ca_bundle_display_placements();
					$t_item->setMode(ACCESS_WRITE);
					$va_item['display_id'] = $t_dupe->getPrimaryKey();
					$t_item->set($va_item);
					$t_item->insert();
					
					if ($t_item->numErrors()) {
						$this->errors = $t_item->errors;
						if ($vb_we_set_transaction) { $this->removeTransaction(false);}
						return false;
					}
				}
			}
		}
		
		
		if ($vb_we_set_transaction) { $this->removeTransaction(true);}
		return $t_dupe;
	}
	# ------------------------------------------------------
	/** 
	 * Override set() to reject changes to user_id for existing rows
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if ($this->getPrimaryKey()) {
			if (is_array($pa_fields)) {
				if (isset($pa_fields['user_id'])) { unset($pa_fields['user_id']); }
				if (isset($pa_fields['table_num'])) { unset($pa_fields['table_num']); }
			} else {
				if ($pa_fields === 'user_id') { return false; }
				if ($pa_fields === 'table_num') { return false; }
			}
		}
		return parent::set($pa_fields, $pm_value, $pa_options);
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_users'] = array('type' => 'special', 'repeating' => true, 'label' => _t('User access'));
		$this->BUNDLES['ca_user_groups'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Group access'));
		$this->BUNDLES['ca_bundle_display_placements'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Display list contents'));
		$this->BUNDLES['settings'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Display settings'));
		$this->BUNDLES['ca_bundle_display_type_restrictions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Type restrictions'));
	}
	# ------------------------------------------------------
	# Display settings
	# ------------------------------------------------------
	/**
	 * Add bundle placement to currently loaded display
	 *
	 * @param string $ps_bundle_name Name of bundle to add (eg. ca_objects.idno, ca_objects.preferred_labels.name)
	 * @param array $pa_settings Placement settings array; keys should be valid setting names
	 * @param int $pn_rank Optional value that determines sort order of bundles in the display. If omitted, placement is added to the end of the display.
	 * @param array $pa_options Optional array of options. Supports the following options:
	 * 		user_id = if specified then add will fail if specified user does not have edit access for the display
	 * @return int Returns placement_id of newly created placement on success, false on error
	 */
	public function addPlacement($ps_bundle_name, $pa_settings, $pn_rank=null, $pa_options=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }
		unset(ca_bundle_displays::$s_placement_list_cache[$vn_display_id]);
		
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)) {
			return null;
		}
		
		$t_placement = new ca_bundle_display_placements(null, is_array($pa_options['additional_settings']) ? $pa_options['additional_settings'] : null);
		$t_placement->setMode(ACCESS_WRITE);
		$t_placement->set('display_id', $vn_display_id);
		$t_placement->set('bundle_name', $ps_bundle_name);
		$t_placement->set('rank', $pn_rank);
		
		if (is_array($pa_settings)) {
			foreach($pa_settings as $vs_key => $vs_value) {
				$t_placement->setSetting($vs_key, $vs_value);
			}
		}
		
		$t_placement->insert();
		
		if ($t_placement->numErrors()) {
			$this->errors = array_merge($this->errors, $t_placement->errors);
			return false;
		}
		return $t_placement->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Removes bundle placement from display
	 *
	 * @param int $pn_placement_id Placement_id of placement to remove
	 * @param array $pa_options Optional array of options. Supports the following options:
	 * 		user_id = if specified then remove will fail if specified user does not have edit access for the display
	 * @return bool Returns true on success, false on error
	 */
	public function removePlacement($pn_placement_id, $pa_options=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)) {
			return null;
		}
		
		$t_placement = new ca_bundle_display_placements($pn_placement_id);
		if ($t_placement->getPrimaryKey() && ($t_placement->get('display_id') == $vn_display_id)) {
			$t_placement->setMode(ACCESS_WRITE);
			$t_placement->delete(true);
			
			if ($t_placement->numErrors()) {
				$this->errors = array_merge($this->errors, $t_placement->errors);
				return false;
			}
			
			unset(ca_bundle_displays::$s_placement_list_cache[$vn_display_id]);
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of placements for the currently loaded display.
	 *
	 * @param array $pa_options Optional array of options. Supports the following options:
	 * 		noCache = if set to true then the returned list if always generated directly from the database, otherwise it is returned from the cache if possible. Set this to true if you expect the cache may be stale. Default is false.
	 *		returnAllAvailableIfEmpty = if set to true then the list of all available bundles will be returned if the currently loaded display has no placements, or if there is no display loaded
	 *		table = if using the returnAllAvailableIfEmpty option and you expect a list of available bundles to be returned if no display is loaded, you must specify the table the bundles are intended for use with with this option. Either the table name or number may be used.
	 *		user_id = if specified then placements are only returned if the user has at least read access to the display
	 *		settingsOnly = if true the settings forms are omitted and only setting values are returned; default is false
	 * @return array List of placements in display order. Array is keyed on bundle name. Values are arrays with the following keys:
	 *		placement_id = primary key of ca_bundle_display_placements row - a unique id for the placement
	 *		bundle_name = bundle name (a code - not for display)
	 *		settings = array of placement settings. Keys are setting names.
	 *		display = display string for bundle
	 */
	public function getPlacements($pa_options=null) {
		$pb_no_cache = (isset($pa_options['noCache'])) ? (bool)$pa_options['noCache'] : false;
		$pb_settings_only = (isset($pa_options['settingsOnly'])) ? (bool)$pa_options['settingsOnly'] : false;
		$pb_return_all_available_if_empty = (isset($pa_options['returnAllAvailableIfEmpty']) && !$pb_settings_only) ? (bool)$pa_options['returnAllAvailableIfEmpty'] : false;
		$ps_table = (isset($pa_options['table'])) ? $pa_options['table'] : null;
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_READ_ACCESS__)) {
			return array();
		}
		
		if (!($vn_display_id = $this->getPrimaryKey())) {
			if ($pb_return_all_available_if_empty && $ps_table) {
				return ca_bundle_displays::$s_placement_list_cache[$vn_display_id] = $this->getAvailableBundles($ps_table);
			}
			return array(); 
		}
		
		$vs_cache_key = $vn_display_id.'/'.($pb_settings_only ? 1 : 0);
		if (!$pb_no_cache && isset(ca_bundle_displays::$s_placement_list_cache[$vs_cache_key]) && ca_bundle_displays::$s_placement_list_cache[$vs_cache_key]) {
			return ca_bundle_displays::$s_placement_list_cache[$vs_cache_key];
		}
		
		$o_dm = $this->getAppDatamodel();
		$o_db = $this->getDb();
		
		$t_list = new ca_lists();
		
		$qr_res = $o_db->query("
			SELECT placement_id, bundle_name, settings
			FROM ca_bundle_display_placements
			WHERE
				display_id = ?
			ORDER BY rank
		", (int)$vn_display_id);
		
		$va_available_bundles = ($pb_settings_only) ? array() : $this->getAvailableBundles(null, $pa_options);
		$va_placements = array();
	
		if ($qr_res->numRows() > 0) {
			$vs_subject_table = $o_dm->getTableName($this->get('table_num'));
			$t_subject = $o_dm->getInstanceByTableNum($this->get('table_num'), true);
			$t_placement = new ca_bundle_display_placements();
			while($qr_res->nextRow()) {
				$vs_bundle_name = $qr_res->get('bundle_name');
				$va_bundle_name = explode(".", $vs_bundle_name);
				
				$va_placements[$vn_placement_id = (int)$qr_res->get('placement_id')] = $qr_res->getRow();
				$va_placements[$vn_placement_id]['settings'] = $va_settings = caUnserializeForDatabase($qr_res->get('settings'));
				if (!$pb_settings_only) {
					$t_placement->setSettingDefinitionsForPlacement($va_available_bundles[$vs_bundle_name]['settings']);
					$va_placements[$vn_placement_id]['display'] = $va_available_bundles[$vs_bundle_name]['display'];
					$va_placements[$vn_placement_id]['settingsForm'] = $t_placement->getHTMLSettingForm(array('id' => $vs_bundle_name.'_'.$vn_placement_id, 'settings' => $va_settings));
				} else {
					$va_tmp = explode('.', $vs_bundle_name);
					$t_instance = $o_dm->getInstanceByTableName($va_tmp[0], true);
					$va_placements[$vn_placement_id]['display'] = ($t_instance ? $t_instance->getDisplayLabel($vs_bundle_name) : "???");
				}
				
				if ($va_bundle_name[0] == $vs_subject_table) {
					// Only primary fields are inline-editable
					
					// Check if it is one of the types of fields that is inline editable
					if ($va_bundle_name[1] == 'preferred_labels') {
						// Preferred labels are inline editable
						$va_placements[$vn_placement_id]['allowInlineEditing'] = true;
						$va_placements[$vn_placement_id]['inlineEditingType'] = DT_FIELD;
					} elseif ($t_subject->hasField($va_bundle_name[1])) {
						//
						// Intrinsics are editable, except for type_id
						//
						if ($va_bundle_name[1] == $t_subject->getTypeFieldName()) {
							$va_placements[$vn_placement_id]['allowInlineEditing'] = false;
							$va_placements[$vn_placement_id]['inlineEditingType'] = null;
						} else {
							$va_placements[$vn_placement_id]['allowInlineEditing'] = true;
							switch($t_subject->getFieldInfo($va_bundle_name[1], 'DISPLAY_TYPE')) {
								case 'DT_SELECT':
									if (($vs_list_code = $t_subject->getFieldInfo($va_bundle_name[1], 'LIST')) || ($vs_list_code = $t_subject->getFieldInfo($va_bundle_name[1], 'LIST_CODE'))) {
										$va_placements[$vn_placement_id]['inlineEditingType'] = DT_SELECT;
										if (!is_array($va_list_labels = $t_list->getItemsForList($vs_list_code, array('labelsOnly' => true)))) {
											$va_list_labels = array();
										}
										$va_placements[$vn_placement_id]['inlineEditingListValues'] = array_values($va_list_labels);
									} else {
										$va_placements[$vn_placement_id]['inlineEditingType'] = DT_FIELD;
									}
									break;
								default:
									$va_placements[$vn_placement_id]['inlineEditingType'] = DT_FIELD;
									break;
							}
						}
					} elseif ($t_subject->hasElement($va_bundle_name[1])) {
						$vn_data_type = ca_metadata_elements::getElementDatatype($va_bundle_name[1]);
						switch($vn_data_type) {
							case 1:
							case 2:
							case 5:
							case 6:
							case 8:
							case 9:
							case 10:
							case 11:
							case 12:
								$va_placements[$vn_placement_id]['allowInlineEditing'] = true;
								$va_placements[$vn_placement_id]['inlineEditingType'] = DT_FIELD;
								break;
							case 3:	// lists
								if ($t_element = ca_metadata_elements::getInstance($va_bundle_name[1])) {
									switch($t_element->getSetting('render')) {
										case 'select':
										case 'yes_no_checkboxes':
										case 'radio_buttons':
										case 'checklist':
											$va_placements[$vn_placement_id]['allowInlineEditing'] = true;
											$va_placements[$vn_placement_id]['inlineEditingType'] = DT_SELECT;
											$va_placements[$vn_placement_id]['inlineEditingListValues'] = array_values($t_list->getItemsForList($t_element->get("list_id"), array('labelsOnly' => true)));
											break;
										case 'lookup':
										case 'horiz_hierbrowser':
										case 'horiz_hierbrowser_with_search':
										case 'vert_hierbrowser':
											$va_placements[$vn_placement_id]['allowInlineEditing'] = true;
											$va_placements[$vn_placement_id]['inlineEditingType'] = DT_LOOKUP;	
											$va_placements[$vn_placement_id]['inlineEditingList'] = $t_element->get('list_id');
											break;
										default: // if it's a render setting we don't know about it's not editable
											$va_placements[$vn_placement_id]['allowInlineEditing'] = false;
											$va_placements[$vn_placement_id]['inlineEditingType'] = null;
											break;
									}
								}
								break;
							default:
								$va_placements[$vn_placement_id]['allowInlineEditing'] = false;
								$va_placements[$vn_placement_id]['inlineEditingType'] = null;
								break;
						}
					} else {
						$va_placements[$vn_placement_id]['allowInlineEditing'] = false;
						$va_placements[$vn_placement_id]['inlineEditingType'] = null;
					}
				} else {
					// Related bundles are never inline-editable (for now)
					$va_placements[$vn_placement_id]['allowInlineEditing'] = false;
					$va_placements[$vn_placement_id]['inlineEditingType'] = null;
				}
			}
		} else {
			if ($pb_return_all_available_if_empty) {
				$va_placements = $this->getAvailableBundles($this->get('table_num'));
			}
		}
		return ca_bundle_displays::$s_placement_list_cache[$vs_cache_key] = $va_placements;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of bundle displays subject to options
	 * 
	 * @param array $pa_options Optional array of options. Supported options are:
	 *			table = If set, list is restricted to displays that pertain to the specified table. You can pass a table name or number. If omitted displays for all tables will be returned.
	 *			user_id = Restricts returned displays to those accessible by the current user. If omitted then all displays, regardless of access are returned.
	 *			restrictToTypes = Restricts returned displays to those bound to the specified type. Default is to not restrict by type.
	 *			dontIncludeSubtypesInTypeRestriction = If restrictToTypes is set, controls whether or not subtypes are automatically included in the restriction. Default is false – subtypes are included.
	 *			access = Restricts returned displays to those with at least the specified access level for the specified user. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then displays where the user has at least read access will be returned. 
	 * @return array Array of displays keyed on display_id and then locale_id. Keys for the per-locale value array include: display_id,  display_code, user_id, table_num,  label_id, name (display name of display), locale_id (locale of display name), bundle_display_content_type (display name of content this display pertains to)
	 */
	 public function getBundleDisplays($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$pm_table_name_or_num = 							caGetOption('table', $pa_options, null);
		$pn_user_id = 										caGetOption('user_id', $pa_options, null);
		$pn_access = 										caGetOption('access', $pa_options, null); 
		$pa_restrict_to_types = 							caGetOption('restrictToTypes', $pa_options, null);
		$pb_dont_include_subtypes_in_type_restriction = 	caGetOption('dontIncludeSubtypesInTypeRestriction', $pa_options, false);
		
	 	$o_dm = $this->getAppDatamodel();
	 	if ($pm_table_name_or_num && !($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) { return array(); }
		
		$o_db = $this->getDb();
		
		$va_sql_wheres = array(
			'((bdl.is_preferred = 1) OR (bdl.is_preferred is null))'
		);
		if ($vn_table_num > 0) {
			$va_sql_wheres[] = "(bd.table_num = ".intval($vn_table_num).")";
		}
		
		if(is_array($pa_restrict_to_types) && sizeof($pa_restrict_to_types)) {
			$va_type_list = caMakeTypeIDList($pm_table_name_or_num, $pa_restrict_to_types, array('dontIncludeSubtypesInTypeRestriction' => $pb_dont_include_subtypes_in_type_restriction));
			if (sizeof($va_type_list) > 0) {
				$va_sql_wheres[] = "(cbdtr.type_id IS NULL OR cbdtr.type_id IN (".join(",", $va_type_list)."))";
			}
		}
		$va_sql_access_wheres = array();
		if ($pn_user_id) {
			$t_user = $o_dm->getInstanceByTableName('ca_users', true);
			$t_user->load($pn_user_id);
			
			if ($t_user->getPrimaryKey()) {
				$vs_access_sql = ($pn_access > 0) ? " AND (access >= ".intval($pn_access).")" : "";
				if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
					$vs_sql = "(
						(bd.user_id = ".intval($pn_user_id).") OR 
						(bd.display_id IN (
								SELECT display_id 
								FROM ca_bundle_displays_x_user_groups 
								WHERE 
									group_id IN (".join(',', array_keys($va_groups)).") {$vs_access_sql}
							)
						)
					)";
				} else {
					$vs_sql = "(bd.user_id = {$pn_user_id})";
				}
				
				$vs_sql .= " OR (bd.display_id IN (
										SELECT display_id 
										FROM ca_bundle_displays_x_users 
										WHERE 
											user_id = {$pn_user_id} {$vs_access_sql}
									)
								)";
				
				
				$va_sql_access_wheres[] = "({$vs_sql})";
			}
		}
		
		if ($pn_access == __CA_BUNDLE_DISPLAY_READ_ACCESS__) {
			$va_sql_access_wheres[] = "(bd.is_system = 1)";
		}
		
		if (sizeof($va_sql_access_wheres)) {
			$va_sql_wheres[] = "(".join(" OR ", $va_sql_access_wheres).")";
		}
		
		// get displays
		$qr_res = $o_db->query($vs_sql = "
			SELECT
				bd.display_id, bd.display_code, bd.user_id, bd.table_num, 
				bdl.label_id, bdl.name, bdl.locale_id, u.fname, u.lname, u.email,
				l.language, l.country
			FROM ca_bundle_displays bd
			LEFT JOIN ca_bundle_display_labels AS bdl ON bd.display_id = bdl.display_id
			LEFT JOIN ca_locales AS l ON bdl.locale_id = l.locale_id
			LEFT JOIN ca_bundle_display_type_restrictions AS cbdtr ON bd.display_id = cbdtr.display_id
			INNER JOIN ca_users AS u ON bd.user_id = u.user_id
			".(sizeof($va_sql_wheres) ? 'WHERE ' : '')."
			".join(' AND ', $va_sql_wheres)."
			ORDER BY -cbdtr.display_id DESC, bdl.name ASC
		");
		//print "got $vs_sql";
		$va_displays = array();
		
		$t_list = new ca_lists();
		$va_type_name_cache = array();
		while($qr_res->nextRow()) {
			$vn_table_num = $qr_res->get('table_num');
			if (!isset($va_type_name_cache[$vn_table_num]) || !($vs_display_type = $va_type_name_cache[$vn_table_num])) {
				$vs_display_type = $va_type_name_cache[$vn_table_num] = $this->getBundleDisplayTypeName($vn_table_num, array('number' => 'plural'));
			}
			$va_displays[$qr_res->get('display_id')][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array('bundle_display_content_type' => $vs_display_type));
		}
		return $va_displays;
	}
	# ------------------------------------------------------
	/**
	 * Return available displays as HTML <select> drop-down menu
	 *
	 * @param string $ps_select_name Name attribute for <select> form element 
	 * @param array $pa_attributes Optional array of attributes to embed in HTML <select> tag. Keys are attribute names and values are attribute values.
	 * @param array $pa_options Optional array of options. Supported options include:
	 * 		Supports all options supported by caHTMLSelect() and ca_bundle_displays::getBundleDisplays() + the following:
	 *			addDefaultDisplay - if true, the "default" display is included at the head of the list; this is simply a display called "default" that is assumed to be handled by your code; the default is not to add the default value (false)
	 *			addDefaultDisplayIfEmpty - same as 'addDefaultDisplay' except that the default value is only added if the display list is empty
	 * @return string HTML code defining <select> drop-down
	 */
	public function getBundleDisplaysAsHTMLSelect($ps_select_name, $pa_attributes=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$va_available_displays = caExtractValuesByUserLocale($this->getBundleDisplays($pa_options));
	
		$va_content = array();
		
		if (
			(isset($pa_options['addDefaultDisplay']) && $pa_options['addDefaultDisplay'])
			|| 
			(isset($pa_options['addDefaultDisplayIfEmpty']) &&  ($pa_options['addDefaultDisplayIfEmpty']) && (!sizeof($va_available_displays)))
		) {
			$va_content[_t('Default')] = 0;
		}
		
		foreach($va_available_displays as $vn_display_id => $va_info) {
			$va_content[$va_info['name']] = $vn_display_id;
		}
		
		if (sizeof($va_content) == 0) { return ''; }
		return caHTMLSelect($ps_select_name, $va_content, $pa_attributes, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Returns name of type of content (synonymous with the table name for the content) currently loaded bundle display contains for display. Will return name in singular number unless the 'number' option is set to 'plural'
	 *
	 * @param int $pn_table_num Table number to return name for. If omitted then the name for the content type contained by the current bundle display will be returned. Use this parameter if you want to force a content type without having to load a bundle display.
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		number = Set to 'plural' to return plural version of name; set to 'singular' [default] to return the singular version
	 * @return string The name of the type of content or null if $pn_table_num is not set to a valid table and no form is loaded.
	 */
	public function getBundleDisplayTypeName($pm_table_name_or_num=null, $pa_options=null) {
		$o_dm = $this->getAppDatamodel();
		if (!$pm_table_name_or_num && !($pm_table_name_or_num = $this->get('table_num'))) { return null; }
	 	if (!($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) { return null; }
		
		$t_instance = $o_dm->getInstanceByTableNum($vn_table_num, true);
		if (!$t_instance) { return null; }
		return (isset($pa_options['number']) && ($pa_options['number'] == 'plural')) ? $t_instance->getProperty('NAME_PLURAL') : $t_instance->getProperty('NAME_SINGULAR');

	}
	# ------------------------------------------------------
	/**
	 * Determines if user has access to a display at a specified access level.
	 *
	 * @param int $pn_user_id user_id of user to check display access for
	 * @param int $pn_access type of access required. Use __CA_BUNDLE_DISPLAY_READ_ACCESS__ for read-only access or __CA_BUNDLE_DISPLAY_EDIT_ACCESS__ for editing (full) access
	 * @param int $pn_display_id The id of the display to check. If omitted then currently loaded display will be checked.
	 * @return bool True if user has access, false if not
	 */
	public function haveAccessToDisplay($pn_user_id, $pn_access, $pn_display_id=null) {
		if ($pn_display_id) {
			$vn_display_id = $pn_display_id;
			$t_disp = new ca_bundle_displays($vn_display_id);
			$vn_display_user_id = $t_disp->get('user_id');
		} else {
			$vn_display_user_id = $this->get('user_id');
			$t_disp = $this;
		}
		if(!$vn_display_id && !($vn_display_id = $t_disp->getPrimaryKey())) { 
			return true; // new display
		}
		if (isset(ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_access])) {
			return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_access];
		}
		
		if (($vn_display_user_id == $pn_user_id)) {	// owners have all access
			return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}
		
		
		if ((bool)$t_disp->get('is_system') && ($pn_access == __CA_BUNDLE_DISPLAY_READ_ACCESS__)) {	// system displays are readable by all
			return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}
		
		$o_db =  $this->getDb();
		$qr_res = $o_db->query("
			SELECT dxg.display_id 
			FROM ca_bundle_displays_x_user_groups dxg 
			INNER JOIN ca_user_groups AS ug ON dxg.group_id = ug.group_id
			INNER JOIN ca_users_x_groups AS uxg ON uxg.group_id = ug.group_id
			WHERE 
				(dxg.access >= ?) AND (uxg.user_id = ?) AND (dxg.display_id = ?)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_display_id);
	
		if ($qr_res->numRows() > 0) { return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_access] = true; }
		
		$qr_res = $o_db->query("
			SELECT dxu.display_id 
			FROM ca_bundle_displays_x_users dxu
			INNER JOIN ca_users AS u ON dxu.user_id = u.user_id
			WHERE 
				(dxu.access >= ?) AND (u.user_id = ?) AND (dxu.display_id = ?)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_display_id);
	
		if ($qr_res->numRows() > 0) { return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_access] = true; }
		
		return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_access] = false;
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
	# Bundles
	# ------------------------------------------------------
	/**
	 * Returns HTML bundle for adding/editing/deleting placements from a display
	 *
	 * @param object $po_request The current request
	 * @param $ps_form_name The name of the HTML form this bundle will be part of
	 * @return string HTML for bundle
	 */
	public function getBundleDisplayHTMLFormBundle($po_request, $ps_form_name) {
		if (!$this->haveAccessToDisplay($po_request->getUserID(), __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)) {
			return null;
		}
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');	
		
		$o_view->setVar('lookup_urls', caJSONLookupServiceUrl($po_request, $this->getAppDatamodel()->getTableName($this->get('table_num'))));
		$o_view->setVar('t_display', $this);
		$o_view->setVar('id_prefix', $ps_form_name);		
		
		return $o_view->render('ca_bundle_display_placements.php');
	}
	# ------------------------------------------------------
	# Support methods for display setup UI
	# ------------------------------------------------------
	/**
	 * Returns all available bundle display placements - those data bundles that can be displayed for the given content type, in other words.
	 * The returned value is a list of arrays; each array contains a 'bundle' specifier than can be passed got Model::get() or SearchResult::get() and a display name
	 *
	 * @param mixed $pm_table_name_or_num The table name or number specifying the content type to fetch bundles for. If omitted the content table of the currently loaded display will be used.
	 * @param array $pa_options Support options are
	 *		no_cache = if set caching of underlying data required to generate list is disabled. This is required in certain situations such as during installation. Only set this if you suspect stale data is being used to generate the list. Eg. if you've been changing metadata attributes in the same request in which you call this method. Default is false.
	 *		no_tooltips = if set no tooltips for available bundles will be emitted. Default is false - tooltips will be emitted.
	 *		format = specifies label format for bundles. Valid values are "simple" (just the name of the element) or "full" (name of element, name of type of item element pertains to and alternate label, if defined). Default is "full"
	 * @return array And array of bundles keyed on display label. Each value is an array with these keys:
	 *		bundle = The bundle name (eg. ca_objects.idno)
	 *		display = Display label for each available bundle
	 *		description = Description of bundle
	 * 
	 * Will return null if table name or number is invalid.
	 */
	public function getAvailableBundles($pm_table_name_or_num=null, $pa_options=null) {
		if (!$pm_table_name_or_num) { $pm_table_name_or_num = $this->get('table_num'); }
		$pm_table_name_or_num = $this->getAppDatamodel()->getTableNum($pm_table_name_or_num);
		if (!$pm_table_name_or_num) { return null; }
		
		$vb_show_tooltips = (isset($pa_options['no_tooltips']) && (bool)$pa_options['no_tooltips']) ? false : true;
		$vs_format = (isset($pa_options['format']) && in_array($pa_options['format'], array('simple', 'full'))) ? $pa_options['format'] : 'full';
		
		$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($pm_table_name_or_num, false);
		$vs_table = $t_instance->tableName();
		$vs_table_display_name = $t_instance->getProperty('NAME_PLURAL');
		
		$va_available_bundles = array();
		
		$t_placement = new ca_bundle_display_placements(null, array());
		
		// get intrinsic fields
		$va_additional_settings = array(
			'maximum_length' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 100,
				'label' => _t('Maximum length'),
				'description' => _t('Maximum length, in characters, of displayed information.')
			)
		);
		foreach($t_instance->getFormFields() as $vs_f => $va_info) {
			if (isset($va_info['DONT_USE_AS_BUNDLE']) && $va_info['DONT_USE_AS_BUNDLE']) { continue; }
			if ($t_instance->getFieldInfo($vs_f, 'ALLOW_BUNDLE_ACCESS_CHECK')) {
				if (caGetBundleAccessLevel($vs_table, $vs_f) == __CA_BUNDLE_ACCESS_NONE__) {
					continue;
				}
			}
			
			$vs_bundle = $vs_table.'.'.$vs_f;
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_{$vs_f}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_{$vs_table}_{$vs_f}",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		}
		
		// get attributes
		$va_element_codes = $t_instance->getApplicableElementCodes(null, false, $pa_options['no_cache']);
		
		$t_md = new ca_metadata_elements();
		$va_all_elements = $t_md->getElementsAsList();
		
		$va_additional_settings = array(
			'format' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 35, 'height' => 5,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Display format'),
				'description' => _t('Template used to format output.'),
				'helpText' => ''
			),
			'delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 35, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Text to place in-between repeating values.')
			),
			'maximum_length' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 2048,
				'label' => _t('Maximum length'),
				'description' => _t('Maximum length, in characters, of displayed information.')
			),
			'filter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 35, 'height' => 5,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Filter using expression'),
				'description' => _t('Expression to filter values with. Leave blank if you do not wish to filter values.')
			)
		);
		foreach($va_element_codes as $vn_element_id => $vs_element_code) {
			if (!is_null($va_all_elements[$vn_element_id]['settings']['canBeUsedInDisplay'] ) && !$va_all_elements[$vn_element_id]['settings']['canBeUsedInDisplay']) { continue; }
			$t_placement = new ca_bundle_display_placements(null, $va_additional_settings);
			if (caGetBundleAccessLevel($vs_table, $vs_element_code) == __CA_BUNDLE_ACCESS_NONE__) {
				continue;	
			}
			
			switch($va_all_elements[$vn_element_id]['datatype']) {
				case 3:	// list
					$va_even_more_settings = array(
						'sense' => array(
							'formatType' => FT_TEXT,
							'displayType' => DT_SELECT,
							'width' => 20, 'height' => 1,
							'takesLocale' => false,
							'default' => 'singular',
							'options' => array(
								_t('Singular') => 'singular',
								_t('Plural') => 'plural'
							),
							'label' => _t('Sense'),
							'description' => _t('Determines if value used is singular or plural version.')
						)		
					);
					break;
				case 0:	// container (allows sub-elements to be summarized)
				case 6: // Currency
				case 8: // Length
				case 9:	// Weight
				case 10: // Timecode
				case 11: // Integer
				case 12: // Numeric (decimal)
					$va_even_more_settings = array(
						'bottom_line' => array(
							'formatType' => FT_TEXT,
							'displayType' => DT_FIELD,
							'width' => 35, 'height' => 5,
							'takesLocale' => false,
							'default' => '',
							'label' => _t('Bottom line format'),
							'description' => _t('Template to format aggregate data for display under this column. The template can include these aggregate data tags: ^PAGEAVG, ^AVG, ^PAGESUM, ^SUM, ^PAGEMin, ^MIN, ^PAGEMAX, ^MAX. For containers follow the tag with the element code of the subelement to aggregate. Ex. ^SUM:dimensions_width')
						)		
					);
					
					if ($va_all_elements[$vn_element_id]['datatype'] == 6) {
						$va_even_more_settings['display_currency_conversion'] = array(
							'formatType' => FT_NUMBER,
							'displayType' => DT_CHECKBOXES,
							'width' => 10, 'height' => 1,
							'takesLocale' => false,
							'default' => '0',
							'label' => _t('Display currency conversion?'),
							'description' => _t('Check this option if you want your currency values to be displayed in both the specified and local currency.')
						);
					}
					break;
				default:
					$va_even_more_settings = array();
					break;
			}
			
			$vs_bundle = $vs_table.'.'.$vs_element_code;
			
			$va_even_more_settings['format'] = $va_additional_settings['format'];
			//$va_even_more_settings['format']['helpText'] = $this->getTemplatePlaceholderDisplayListForBundle($vs_bundle);
			
			$t_placement = new ca_bundle_display_placements(null, array_merge($va_additional_settings, $va_even_more_settings));
			
			$vs_display =  "<div id='bundleDisplayEditorBundle_{$vs_table}_{$vs_element_code}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => array_merge($va_additional_settings, $va_even_more_settings)
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_{$vs_table}_{$vs_element_code}",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		}
		
		
		if (caGetBundleAccessLevel($vs_table, "preferred_labels") != __CA_BUNDLE_ACCESS_NONE__) {
			// get preferred labels for this table
			$va_additional_settings = array(
				'format' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => 35, 'height' => 5,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				),
				'delimiter' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => 35, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Delimiter'),
					'description' => _t('Text to place in-between repeating values.')
				),
				'maximum_length' => array(
					'formatType' => FT_NUMBER,
					'displayType' => DT_FIELD,
					'width' => 6, 'height' => 1,
					'takesLocale' => false,
					'default' => 100,
					'label' => _t('Maximum length'),
					'description' => _t('Maximum length, in characters, of displayed information.')
				)
			);
			$t_placement = new ca_bundle_display_placements(null, $va_additional_settings);
			
			$vs_bundle = $vs_table.'.preferred_labels';
			//$va_additional_settings['format']['helpText'] = $this->getTemplatePlaceholderDisplayListForBundle($vs_bundle);
			
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_preferred_labels'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_{$vs_table}_preferred_labels",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		}
		
		if (caGetBundleAccessLevel($vs_table, "nonpreferred_labels") != __CA_BUNDLE_ACCESS_NONE__) {
			// get non-preferred labels for this table
			$t_placement = new ca_bundle_display_placements(null, $va_additional_settings);
			
			$vs_bundle = $vs_table.'.nonpreferred_labels';
			//$va_additional_settings['format']['helpText'] = $this->getTemplatePlaceholderDisplayListForBundle($vs_bundle);
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_nonpreferred_labels'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
				
			if ($vb_show_tooltips) {	
				TooltipManager::add(
					"#bundleDisplayEditorBundle_{$vs_table}_nonpreferred_labels",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		}
		
		// get commerce order history bundle (objects only, of course)
		if ($vs_table == 'ca_objects') {
			$va_additional_settings = array(
				'order_type' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'width' => 35, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'options' => array(
						_t('Sales order') => 'O',
						_t('Loan') => 'L'
					),
					'label' => _t('Type of order'),
					'description' => _t('Determines which type of order is displayed.')
				)		
			);
			
			$vs_bundle = 'ca_commerce_order_history';
			$vs_label = _t('Order history');
			$vs_display = _t('Order history');
			$vs_description = _t('List of orders (loans or sales) that include this object');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_ca_commerce_order_history",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		}
		
		if (caGetBundleAccessLevel($vs_table, "ca_object_representations") != __CA_BUNDLE_ACCESS_NONE__) {
			// get object representations (objects only, of course)
			if ($vs_table == 'ca_objects') {
				$va_additional_settings = array(
					'display_mode' => array(
						'formatType' => FT_TEXT,
						'displayType' => DT_SELECT,
						'width' => 35, 'height' => 1,
						'takesLocale' => false,
						'default' => '',
						'options' => array(
							_t('Media') => 'media',
							_t('URL') => 'url'
						),
						'label' => _t('Output mode'),
						'description' => _t('Determines if value used is URL of media or the media itself.')
					)		
				);
			
				$o_media_settings = new MediaProcessingSettings('ca_object_representations', 'media');
				$va_versions = $o_media_settings->getMediaTypeVersions('*');
				
				foreach($va_versions as $vs_version => $va_version_info) {
					$t_placement = new ca_bundle_display_placements(null, $va_additional_settings);
					
					$vs_bundle = 'ca_object_representations.media.'.$vs_version;
					$vs_display = "<div id='bundleDisplayEditorBundle_ca_object_representations_media_{$vs_version}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
					$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
						'bundle' => $vs_bundle,
						'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
						'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
						'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
						'settings' => $va_additional_settings
					);
					
					if ($vb_show_tooltips) {
						TooltipManager::add(
							"#bundleDisplayEditorBundle_ca_object_representations_media_{$vs_version}",
							$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
						);
					}
				}
				
				$t_rep = new ca_object_representations();
				foreach(array('mimetype', 'md5', 'original_filename') as $vs_rep_field) {
					$vs_bundle = 'ca_object_representations.'.$vs_rep_field;
					$vs_display = "<div id='bundleDisplayEditorBundle_ca_object_representations_{$vs_rep_field}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_rep->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_rep->getDisplayLabel($vs_bundle))."</div>";
					
					$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
						'bundle' => $vs_bundle,
						'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
						'description' => $vs_description = $t_rep->getDisplayDescription($vs_bundle),
						'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
						'settings' => array()
					);
				}
				
			}
		}
		
		// get related items
		
		$o_dm = $this->getAppDatamodel();
		foreach(array(
			'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations', 'ca_loans', 'ca_movements', 'ca_list_items'
		) as $vs_related_table) {
			if ($this->getAppConfig()->get($vs_related_table.'_disable')) { continue; }			
			if (caGetBundleAccessLevel($vs_table, $vs_related_table) == __CA_BUNDLE_ACCESS_NONE__) { continue; }
			
			if ($vs_related_table === $vs_table) { 
				$vs_bundle = $vs_related_table.'.related';
			} else {
				$vs_bundle = $vs_related_table;
			}
			
			$t_rel_instance = $o_dm->getInstanceByTableName($vs_related_table, true);
			$vs_table_name = $o_dm->getTableName($this->get('table_num'));
			$va_path = array_keys($o_dm->getPath($vs_table_name, $vs_related_table));
			if ((sizeof($va_path) < 2) || (sizeof($va_path) > 3)) { continue; }		// only use direct relationships (one-many or many-many)
			
			$va_additional_settings = array(
				'format' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => 35, 'height' => 5,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				),
				'restrict_to_relationship_types' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'useRelationshipTypeList' => $va_path[1],
					'width' => 35, 'height' => 5,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Restrict to relationship types'),
					'description' => _t('Restricts display to items related using the specified relationship type(s). Leave all unchecked for no restriction.')
				),
				'restrict_to_types' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'useList' => $t_rel_instance->getTypeListCode(),
					'width' => 35, 'height' => 5,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Restrict to types'),
					'description' => _t('Restricts display to items of the specified type(s). Leave all unchecked for no restriction.')
				),
				'delimiter' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => 35, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Delimiter'),
					'description' => _t('Text to place in-between repeating values.')
				),
				'show_hierarchy' => array(
					'formatType' => FT_NUMBER,
					'displayType' => DT_CHECKBOXES,
					'width' => 10, 'height' => 1,
					'hideOnSelect' => array('format'),
					'takesLocale' => false,
					'default' => '0',
					'label' => _t('Show hierarchy?'),
					'description' => _t('If checked the full hierarchical path will be shown.')
				),
				'remove_first_items' => array(
					'formatType' => FT_NUMBER,
					'displayType' => DT_FIELD,
					'width' => 10, 'height' => 1,
					'takesLocale' => false,
					'default' => '0',
					'label' => _t('Remove first items from hierarchy?'),
					'description' => _t('If set to a non-zero value, the specified number of items at the top of the hierarchy will be omitted. For example, if set to 2, the root and first child of the hierarchy will be omitted.')
				),
				'hierarchy_order' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'options' =>array(
						_t('top first') => 'ASC',
						_t('bottom first') => 'DESC'
					),
					'width' => 35, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Order hierarchy'),
					'description' => _t('Determines order in which hierarchy is displayed.')
				),
				'hierarchy_limit' => array(
					'formatType' => FT_NUMBER,
					'displayType' => DT_FIELD,
					'width' => 10, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Maximum length of hierarchy'),
					'description' => _t('Maximum number of items to show in the hierarchy. Leave blank to show the unabridged hierarchy.')
				),
				'hierarchical_delimiter' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => 35, 'height' => 1,
					'takesLocale' => false,
					'default' => ' ➔ ',
					'label' => _t('Hierarchical delimiter'),
					'description' => _t('Text to place in-between elements of a hierarchical value.')
				)
			);
			
			//$va_additional_settings['format']['helpText'] = $this->getTemplatePlaceholderDisplayListForBundle($vs_bundle);
		
			$t_placement = new ca_bundle_display_placements(null, $va_additional_settings);
			
			$vs_id_suffix = "bundleDisplayEditorBundle_".str_replace(".", "_", $vs_bundle);
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_id_suffix}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_rel_instance->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_rel_instance->getDisplayLabel($vs_bundle))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description = $t_rel_instance->getDisplayDescription($vs_bundle),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_{$vs_id_suffix}",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		}
		
		// created and modified
		$va_additional_settings = array(
			'dateFormat' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 40, 'height' => 1,
				'takesLocale' => false,
				'default' => 'iso8601',
				'options' => array(
					'ISO-8601' => 'iso8601',
					'Text' => 'text'
				),
				'label' => _t('Date format'),
				'description' => _t('Sets format for output of date when exporting.')
			)
		);
		$t_placement = new ca_bundle_display_placements(null, $va_additional_settings);
		
		$vs_bundle = "{$vs_table}.created";
		$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_created'><span class='bundleDisplayEditorPlacementListItemTitle'>"._t('General')."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
		$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
			'bundle' => $vs_bundle,
			'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
			'description' => $vs_description = _t('Date and time item was created'),
			'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
			'settings' => $va_additional_settings
		);
		
		if ($vb_show_tooltips) {
			TooltipManager::add(
				"#bundleDisplayEditorBundle_{$vs_table}_created",
				$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
			);
		}
		
		$vs_bundle = "{$vs_table}.lastModified";
		$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_lastModified'><span class='bundleDisplayEditorPlacementListItemTitle'>"._t('General')."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
		$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
			'bundle' => $vs_bundle,
			'display' => $vs_display,
			'description' => $vs_description = _t('Date and time item was last modified'),
			'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
			'settings' => $va_additional_settings
		);
		
		if ($vb_show_tooltips) {
			TooltipManager::add(
				"#bundleDisplayEditorBundle_{$vs_table}_lastModified",
				$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
			);
		}
		
		ksort($va_available_bundles);
		$va_sorted_bundles = array();
		foreach($va_available_bundles as $vs_k => $va_val) {
			foreach($va_val as $vs_real_key => $va_info) {
				$va_sorted_bundles[$vs_real_key] = $va_info;
			}
		}
		return $va_sorted_bundles;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of placements in the currently loaded display
	 *
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		noCache = if set to true, no caching of placement values is performed. 
	 *		no_tooltips = if set no tooltips for available bundles will be emitted. Default is false - tooltips will be emitted.
	 *		format = specifies label format for bundles. Valid values are "simple" (just the name of the element) or "full" (name of element, name of type of item element pertains to and alternate label, if defined). Default is "full"
	 *		user_id = if specified then placements are only returned if the user has at least read access to the display
	 * @return array List of placements. Each element in the list is an array with the following keys:
	 *		display = A display label for the bundle
	 *		bundle = The bundle name
	 */
	public function getPlacementsInDisplay($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$pb_no_cache = isset($pa_options['noCache']) ? (bool)$pa_options['noCache'] : false;
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_READ_ACCESS__)) {
			return array();
		}
		
		$vb_show_tooltips = (isset($pa_options['no_tooltips']) && (bool)$pa_options['no_tooltips']) ? false : true;
		$vs_format = (isset($pa_options['format']) && in_array($pa_options['format'], array('simple', 'full'))) ? $pa_options['format'] : 'full';
		
		if (!($pn_table_num = $this->getAppDatamodel()->getTableNum($this->get('table_num')))) { return null; }
		
		if (!($t_instance = $this->getAppDatamodel()->getInstanceByTableNum($pn_table_num, true))) { return null; }
		
		if(!is_array($va_placements = $this->getPlacements($pa_options))) { $va_placements = array(); }
		
		$va_placements_in_display = array();
		foreach($va_placements as $vn_placement_id => $va_placement) {
			$vs_label = ($vs_label = $t_instance->getDisplayLabel($va_placement['bundle_name'])) ? $vs_label : $va_placement['bundle_name'];
			if(is_array($va_placement['settings']['label'])){
				$va_tmp = caExtractValuesByUserLocale(array($va_placement['settings']['label']));
				if ($vs_user_set_label = array_shift($va_tmp)) {
					$vs_label = "{$vs_label} (<em>{$vs_user_set_label}</em>)";
				}
			}
			$vs_display = "<div id='bundleDisplayEditor_{$vn_placement_id}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> {$vs_label}</div>";
			$va_placement['display'] = ($vs_format == 'simple') ? $vs_label : $vs_display;
			$va_placement['bundle'] = $va_placement['bundle_name']; // we used 'bundle' in the arrays, but the database field is called 'bundle_name' and getPlacements() returns data directly from the database
			unset($va_placement['bundle_name']);
			
			$va_placements_in_display[$vn_placement_id] = $va_placement;
			
			$vs_description = $t_instance->getDisplayDescription($va_placement['bundle']);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditor_{$vn_placement_id}",
					$this->_formatBundleTooltip($vs_label, $va_placement['bundle'], $vs_description)
				);
			}
		}
		
		return $va_placements_in_display;
	}
	# ------------------------------------------------------
	private function _formatBundleTooltip($ps_label, $ps_bundle, $ps_description) {
		return "<div><strong>{$ps_label}</strong></div><div class='bundleDisplayElementBundleName'>{$ps_bundle}</div><br/><div>{$ps_description}</div>";
	}
	# ------------------------------------------------------
	/**
	 * Returns number of placements in the currently loaded display
	 *
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		noCache = if set to true, no caching of placement values is performed.
	 *		user_id = if specified then placements are only returned if the user has at least read access to the display
	 * @return int Number of placements. 
	 */
	public function getPlacementCount($pa_options=null) {
		return sizeof($this->getPlacementsInDisplay($pa_options));
	}
	# ------------------------------------------------------
	#
	# ------------------------------------------------------
	/**
	 * Returns list of valid template placeholders for the specified bundle. These placeholders always begin
	 * with a caret ("^") and will be replaced with content values when a bundle display is rendered by xxx()
	 * 
	 * @param $ps_bundle_name - name of bundle
	 * @return array - list of placeholders as keys; values are text description of value; will return null if bundle name is invalid
	 */
	public function getTemplatePlaceholderListForBundle($ps_bundle_name) {
	 	$o_dm = $this->getAppDatamodel();
	 	$t_instance = null;
	 	
		$va_tmp = explode('.', $ps_bundle_name);
		switch(sizeof($va_tmp)) {
			case 2:
				$vs_table = $va_tmp[0];
				$vs_bundle = $va_tmp[1];
				
				if ($vs_bundle == 'rel') { $vs_bundle = 'preferred_labels'; }
				break;
			case 1:
				if (!($t_instance = $o_dm->getInstanceByTableName($va_tmp[0], true))) {
					$vs_table = $o_dm->getTableName($this->get('table_num'));
					$vs_bundle = $va_tmp[0];
				} else {
					$vs_table = $va_tmp[0];
					$vs_bundle = 'preferred_labels';
				}
				break;
			default:
				return null;
				break;
		}
		
		if (!$t_instance) {
			if(!($t_instance = $o_dm->getInstanceByTableName($vs_table, true))) { return null; }
		}
		
		$va_key = array('^label' => array(
			'label' => _t('Placement label'),
			'description' => _t('The label for this placement as defined in the placements settings. The value used will be adjusted to reflect the current user&apos;s locale.')
		));
		if ($t_instance->hasField($vs_bundle)) {
			// is intrinsic field
			$va_key["^{$vs_bundle}"] = array(
				'label' => $t_instance->getFieldInfo($vs_bundle, 'LABEL'),
				'description' => $t_instance->getFieldInfo($vs_bundle, 'DESCRIPTION')
			);
			return $va_key;
		}
		
		$va_element_codes = array_flip($t_instance->getApplicableElementCodes(null, false, false));
		
		if ($va_element_codes[$vs_bundle]) {
			$t_element = new ca_metadata_elements();
			if ($t_element->load(array('element_code' => $vs_bundle))) {
				// is attribute
				
				$va_hier = $t_element->getElementsInSet();
				if (is_array($va_hier) && (sizeof($va_hier) > 0)) {
					// is container with children
					foreach($va_hier as $va_node) {
						if ($va_node['datatype'] == 0) { continue; }	// skip containers
						$va_key['^'.$va_node['element_code']] = array(
							'label' => $t_instance->getAttributeLabel($va_node['element_code']),
							'description' => $t_instance->getAttributeDescription($va_node['element_code'])
						);
					}
					return $va_key;
				}
				
				// is simple single-element attribute
				$va_key["^{$vs_bundle}"] = array (
					'label' => $t_instance->getAttributeLabel($vs_bundle),
					'description' => $t_instance->getAttributeDescription($vs_bundle)
				);
				return $va_key;
			}
		}
		
		if ($vs_bundle == 'preferred_labels') {
			if ($t_label = $t_instance->getLabelTableInstance()) {
				foreach($t_label->getFormFields() as $vs_field => $va_field_info) {
					$va_key['^'.$vs_field] = array(
						'label' => $va_field_info['LABEL'],
						'description' => $va_field_info['DESCRIPTION']
					);
				}
				return $va_key;
			}
		}
		
		if ($vs_bundle == 'nonpreferred_labels') {
			if ($t_label = $t_instance->getLabelTableInstance()) {
				foreach($t_label->getFormFields() as $vs_field => $va_field_info) {
					$va_key['^'.$vs_field] = array(
						'label' => $va_field_info['LABEL'],
						'description' => $va_field_info['DESCRIPTION']
					);
				}
				return $va_key;
			}
		}
		
		if ($vs_bundle == 'ca_object_representations') {
			if ($vs_table == 'ca_objects') {
				$t_rep = new ca_object_representations();
				foreach($t_rep->getFormFields() as $vs_field => $va_field_info) {
					$va_key['^'.$vs_field] = array(
						'label' => $va_field_info['LABEL'],
						'description' => $va_field_info['DESCRIPTION']
					);
				}
				$va_key['^media:{version name}'] = array(
					'label' => _t('Specified version of media'),
					'description' => _t('The version of the media representation specified by {version name} for display.')
				);
				
				return $va_key;
			}
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function getTemplatePlaceholderDisplayListForBundle($ps_bundle_name) {
		$va_list = $this->getTemplatePlaceholderListForBundle($ps_bundle_name);
		
		$va_buf = array();
		
		if (is_array($va_list) && sizeof($va_list)) {
			foreach($va_list as $vs_tag => $va_info) {
				$va_buf[] = "<tr><td class='settingsKeyRow'>{$va_info['label']}</td><td class='settingsKeyRow'>{$vs_tag}</td></tr>\n";	
			}
		} else {
			return '';
		}
		
		return '<div style="overflow: auto;"><table><tr><th>'._t('Value').'</th><th>'._t('Tag').'</th></tr>'.join("", $va_buf).'</table></div>';
	}
	# ------------------------------------------------------
	# Display of values
	# ------------------------------------------------------
	/** 
	 * Get display value(s) out of result $po_result for specified bundle and format it using the configured value template
	 *
	 * @param object $po_result A sub-class of SearchResult or BaseModel to extract data out of
	 * @param int $pn_placement_id 
	 * @param array Optional array of options. Supported options include:
	 *		request = The current RequestHTTP object
	 *		convertCodesToDisplayText = If true numeric list id's and value lists are converted to display text. Default is true. If false then all such values are returned as the original integer codes.
	 *		forReport = If true then certain values are transformed for display in a report. Namely, all media output is forced to use the version specified by the app.conf 'representation_version_for_report' directive, no matter the setting in the display.
	 *		purify = if true then value is run through HTMLPurifier (http://htmlpurifier.org) before being returned; this is useful when you want to make sure any HTML in the value is valid, particularly when converting HTML to a PDF as invalid markup will cause an exception. Default is false as HTMLPurify can significantly slow down things if used everywhere.
	 *		delimiter = character(s) to place between repeating values
	 *
	 * @return string The processed value ready for display
	 */
	public function getDisplayValue($po_result, $pn_placement_id, $pa_options=null) {
		
		if (!is_numeric($pn_placement_id)) {
			$vs_bundle_name = $pn_placement_id;
			$va_placement = array();
		} else {
			$va_placements = $this->getPlacements();
			$va_placement = $va_placements[$pn_placement_id];
			$vs_bundle_name = $va_placement['bundle_name'];
		}
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$o_request = caGetOption('request', $pa_options, null);
		
		if (!isset($pa_options['convertCodesToDisplayText'])) { $pa_options['convertCodesToDisplayText'] = true; }
		if (!isset($pa_options['delimiter'])) { $pa_options['delimiter'] = ";\n\n"; }
		if (!isset($pa_options['forReport'])) { $pa_options['forReport'] = false; }
		if (!isset($pa_options['purify'])) { $pa_options['purify'] = false; }
		if (!isset($pa_options['asHTML'])) { $pa_options['asHTML'] = true; }
		
		if (!isset($pa_options['maximumLength'])) { $pa_options['maximumLength'] =  ($va_placement['settings']['maximum_length']) ? $va_placement['settings']['maximum_length'] : null; }
		if (!isset($pa_options['filter'])) { $pa_options['filter'] = caGetOption('filter', $va_placement['settings'], null); }
		
		$pa_options['delimiter'] = ($va_placement['settings']['delimiter']) ? $va_placement['settings']['delimiter'] : $pa_options['delimiter'];
		$pa_options['useSingular'] = (isset($va_placement['settings']['sense']) && ($va_placement['settings']['sense'] == 'singular')) ? true : false;
		$pa_options['returnURL'] = (isset($va_placement['settings']['display_mode']) && ($va_placement['settings']['display_mode'] == 'url'))  ? true : false;
		$pa_options['dateFormat'] = (isset($va_placement['settings']['dateFormat']) && ($va_placement['settings']['dateFormat'])) ? $va_placement['settings']['dateFormat'] : $pa_options['dateFormat'];
		
		if(caGetOption('display_currency_conversion', $va_placement['settings'], false) && $o_request && $o_request->isLoggedIn()) {
			$pa_options['displayCurrencyConversion'] = $o_request->user->getPreference('currency');
		}
		
		$pa_options['hierarchicalDelimiter'] = '';
		
		
		$va_tmp = explode('.', $vs_bundle_name);
		
		if ($va_placement['settings']['show_hierarchy'] || $pa_options['show_hierarchy']) {
			if ($va_tmp[1] == 'related') {
				array_splice($va_tmp, 2, 0, 'hierarchy');
			} else {
				array_splice($va_tmp, 1, 0, 'hierarchy');
			}
			$vs_bundle_name = join(".", $va_tmp);
			$pa_options['hierarchicalDelimiter'] = ($va_placement['settings']['hierarchical_delimiter']) ? $va_placement['settings']['hierarchical_delimiter'] : null;	
			$pa_options['direction'] = ($va_placement['settings']['hierarchy_order']) ? $va_placement['settings']['hierarchy_order'] : null;	
			$pa_options['bottom'] = ($va_placement['settings']['hierarchy_limit']) ? $va_placement['settings']['hierarchy_limit'] : null;	
			$pa_options['removeFirstItems'] = ($va_placement['settings']['remove_first_items']) ? $va_placement['settings']['remove_first_items'] : null;	
		}
		
		$pa_options['restrict_to_relationship_types'] = $va_placement['settings']['restrict_to_relationship_types'];
		$pa_options['restrict_to_types'] = $va_placement['settings']['restrict_to_types'];
		if ((sizeof($va_tmp) == 1) || ((sizeof($va_tmp) == 2) && ($va_tmp[1] == 'related'))) {
			$pa_options['template'] = ($va_placement['settings']['format']) ? $va_placement['settings']['format'] : $this->getAppConfig()->get($va_tmp[0].'_relationship_display_format');
		} else {
			$pa_options['template'] = ($va_placement['settings']['format']) ? $va_placement['settings']['format'] : null;
		}
		
		
		$vs_val = '';
		if($pa_options['template']) { 
			if ($t_instance = $this->getAppDatamodel()->getInstanceByTableName($va_tmp[0], true)) {
				$va_tmp2 = $va_tmp;
				if ((sizeof($va_tmp2) > 1) && (in_array($vs_tmp = array_pop($va_tmp2), array('related')))) {
					$va_tmp2[] = $vs_tmp;
				}
				$va_tmp2[] = $t_instance->primaryKey();
		
				$va_ids = $po_result->get(join('.', $va_tmp2), array('returnAsArray' => true));
				$va_links = array();
				if (is_array($va_ids)) {
					$vs_val = caProcessTemplateForIDs($pa_options['template'], $va_tmp2[0], $va_ids, array_merge($pa_options, array('returnAsArray' => false)));
				}
			}
		} else {
			$vs_val = $po_result->get(join(".", $va_tmp), $pa_options);
		}
		
		if (isset($pa_options['purify']) && $pa_options['purify']) {
			$o_purifier = new HTMLPurifier();
    		$vs_val = $o_purifier->purify($vs_val);
		}
		
		return $vs_val;
	}
	# ------------------------------------------------------
	public function savePlacementsFromHTMLForm($po_request, $ps_form_prefix) {;
		if ($vs_bundles = $po_request->getParameter($ps_form_prefix.'_ca_bundle_display_placementsdisplayBundleList', pString)) {
			$va_bundles = explode(';', $vs_bundles);
			
			$t_display = new ca_bundle_displays($this->getPrimaryKey());
			$va_placements = $t_display->getPlacements(array('user_id' => $po_request->getUserID()));
			
			// remove deleted bundles
			
			foreach($va_placements as $vn_placement_id => $va_bundle_info) {
				if (!in_array($va_bundle_info['bundle_name'].'_'.$va_bundle_info['placement_id'], $va_bundles)) {
					$t_display->removePlacement($va_bundle_info['placement_id'], array('user_id' => $po_request->getUserID()));
					if ($t_display->numErrors()) {
						$this->errors = $t_display->errors;
						return false;
					}
				}
			}
			
			$va_locale_list = ca_locales::getLocaleList(array('index_by_code' => true));
			
			$va_available_bundles = $t_display->getAvailableBundles();
			foreach($va_bundles as $vn_i => $vs_bundle) {
				// get settings
				
				if (preg_match('!^(.*)_([\d]+)$!', $vs_bundle, $va_matches)) {
					$vn_placement_id = (int)$va_matches[2];
					$vs_bundle = $va_matches[1];
				} else {
					$vn_placement_id = null;
				}
				$vs_bundle_proc = str_replace(".", "_", $vs_bundle);
				
				$va_settings = array();
				
				foreach($_REQUEST as $vs_key => $vs_val) {
					if (preg_match("!^{$vs_bundle_proc}_([\d]+)_(.*)$!", $vs_key, $va_matches)) {
						
						// is this locale-specific?
						if (preg_match('!(.*)_([a-z]{2}_[A-Z]{2})$!', $va_matches[2], $va_locale_matches)) {
							$vn_locale_id = isset($va_locale_list[$va_locale_matches[2]]) ? (int)$va_locale_list[$va_locale_matches[2]]['locale_id'] : 0;
							$va_settings[(int)$va_matches[1]][$va_locale_matches[1]][$vn_locale_id] = $vs_val;
						} else {
							$va_settings[(int)$va_matches[1]][$va_matches[2]] = $vs_val;
						}
					}
				}
				
				if($vn_placement_id === 0) {
					$t_display->addPlacement($vs_bundle, $va_settings[$vn_placement_id], $vn_i + 1, array('user_id' => $po_request->getUserID(), 'additional_settings' => $va_available_bundles[$vs_bundle]['settings']));
					if ($t_display->numErrors()) {
						$this->errors = $t_display->errors;
						return false;
					}
				} else {
					$t_placement = new ca_bundle_display_placements($vn_placement_id, $va_available_bundles[$vs_bundle]['settings']);
					$t_placement->setMode(ACCESS_WRITE);
					$t_placement->set('rank', $vn_i + 1);
					
					if (is_array($va_settings[$vn_placement_id])) {
						//foreach($va_settings[$vn_placement_id] as $vs_setting => $vs_val) {
						foreach($t_placement->getAvailableSettings() as $vs_setting => $va_setting_info) {
							$vs_val = isset($va_settings[$vn_placement_id][$vs_setting]) ? $va_settings[$vn_placement_id][$vs_setting] : null;
						
							$t_placement->setSetting($vs_setting, $vs_val);
						}
					}
					$t_placement->update();
					
					if ($t_placement->numErrors()) {
						$this->errors = $t_placement->errors;
						return false;
					}
				}
			}
		} 
	}
	# ----------------------------------------
	# Type restrictions
	# ----------------------------------------
	/**
	 * Adds restriction (a binding between the display and item type)
	 *
	 * @param int $pn_type_id the type
	 * @param array $pa_settings Array of options for the restriction. (No options are currently implemented).
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function addTypeRestriction($pn_type_id, $va_settings=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		if (!is_array($va_settings)) { $va_settings = array(); }
		
		if (!($t_instance = $this->_DATAMODEL->getInstanceByTableNum($this->get('table_num')))) { return false; }
		
		$va_type_list = $t_instance->getTypeList();
		if (!isset($va_type_list[$pn_type_id])) { return false; }
		
		$t_restriction = new ca_bundle_display_type_restrictions();
		$t_restriction->setMode(ACCESS_WRITE);
		$t_restriction->set('table_num', $this->get('table_num'));
		$t_restriction->set('type_id', $pn_type_id);
		$t_restriction->set('display_id', $this->getPrimaryKey());
		foreach($va_settings as $vs_setting => $vs_setting_value) {
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
	 * Sets restrictions for currently loaded display
	 *
	 * @param array $pa_type_ids list of types to restrict to
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function setTypeRestrictions($pa_type_ids) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		if (!is_array($pa_type_ids)) {
			if (is_numeric($pa_type_ids)) { 
				$pa_type_ids = array($pa_type_ids); 
			} else {
				$pa_type_ids = array();
			}
		}
		
		if (!($t_instance = $this->_DATAMODEL->getInstanceByTableNum($this->get('table_num')))) { return false; }
		
		$va_type_list = $t_instance->getTypeList();
		$va_current_restrictions = $this->getTypeRestrictions();
		$va_current_type_ids = array();
		foreach($va_current_restrictions as $vn_i => $va_restriction) {
			$va_current_type_ids[$va_restriction['type_id']] = true;
		}
		
		foreach($va_type_list as $vn_type_id => $va_type_info) {
			if(in_array($vn_type_id, $pa_type_ids)) {
				// need to set
				if(!isset($va_current_type_ids[$vn_type_id])) {
					$this->addTypeRestriction($vn_type_id);
				}
			} else {
				// need to unset
				if(isset($va_current_type_ids[$vn_type_id])) {
					$this->removeTypeRestriction($vn_type_id);
				}
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Remove restriction from currently loaded display for specified type
	 *
	 * @param int $pn_type_id The type of the restriction
	 * @return bool True on success, false on error, null if no screen is loaded
	 */
	public function removeTypeRestriction($pn_type_id) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			DELETE FROM ca_bundle_display_type_restrictions
			WHERE
				display_id = ? AND type_id = ?
		", (int)$this->getPrimaryKey(), (int)$pn_type_id);
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Remove all type restrictions from loaded display
	 *
	 * @return bool True on success, false on error, null if no screen is loaded 
	 */
	public function removeAllTypeRestrictions() {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			DELETE FROM ca_bundle_display_type_restrictions
			WHERE
				display_id = ?
		", (int)$this->getPrimaryKey());
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Return restrictions for currently loaded display
	 *
	 * @param int $pn_type_id Type to limit returned restrictions to; if omitted or null then all restrictions are returned
	 * @return array A list of restrictions, false on error or null if no ui is loaded
	 */
	public function getTypeRestrictions($pn_type_id=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		
		$o_db = $this->getDb();
		
		$vs_table_type_sql = '';
		if ($pn_type_id > 0) {
			$vs_table_type_sql .= ' AND type_id = '.intval($pn_type_id);
		}
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_bundle_display_type_restrictions
			WHERE
				display_id = ? {$vs_table_type_sql}
		", (int)$this->getPrimaryKey());
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}
		
		$va_restrictions = array();
		while($qr_res->nextRow()) {
			$va_restrictions[] = $qr_res->getRow();
		}
		return $va_restrictions;
	}
	# ----------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of type restriction in the currently loaded display
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getTypeRestrictionsHTMLFormBundle($po_request, $ps_form_name) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_display', $this);			
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('request', $po_request);
		
		$va_type_restrictions = $this->getTypeRestrictions();
		$va_restriction_type_ids = array();
		if (is_array($va_type_restrictions)) {
			foreach($va_type_restrictions as $vn_i => $va_restriction) {
				$va_restriction_type_ids[] = $va_restriction['type_id'];
			}
		}
		
		if (!($t_instance = $this->_DATAMODEL->getInstanceByTableNum($vn_table_num = $this->get('table_num')))) { return null; }
		
		$o_view->setVar('type_restrictions', $t_instance->getTypeListAsHTMLFormElement('type_restrictions[]', array('multiple' => 1, 'height' => 5), array('value' => 0, 'values' => $va_restriction_type_ids)));
	
		return $o_view->render('ca_bundle_display_type_restrictions.php');
	}
	# ----------------------------------------
	public function saveTypeRestrictionsFromHTMLForm($po_request, $ps_form_prefix) {
		if (!$this->getPrimaryKey()) { return null; }
		
		return $this->setTypeRestrictions($po_request->getParameter('type_restrictions', pArray));
	}
	# ------------------------------------------------------
}
?>
