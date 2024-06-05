<?php
/** ---------------------------------------------------------------------
 * app/models/ca_bundle_displays.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ModelSettings.php');

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
					_t('list items') => 33
				)
		),
		'display_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Display code'), 'DESCRIPTION' => _t('Unique alphanumeric identifier for this display.'),
				'UNIQUE_WITHIN' => []
				//'REQUIRES' => array('is_administrator')
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Display settings')
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if display is accessible to the public or not.')
		)
	)
);

class ca_bundle_displays extends BundlableLabelableBaseModelWithAttributes {
	use ModelSettings;
	use SetUniqueIdnoTrait;
	
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
	static $s_have_access_to_display_cache = [];
	
	static $s_placement_list_cache;		// cache for getPlacements()
	
	# ------------------------------------------------------
	public function __construct($id=null, ?array $options=null) {
		// Filter list of tables display can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_bundle_displays']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caFilterTableList(BaseModel::$s_ca_models_definitions['ca_bundle_displays']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);
		
		parent::__construct($id, $options);
		
		//
		$this->setAvailableSettings([
			'show_empty_values' => [
				'formatType' => FT_NUMBER,
				'displayType' => DT_CHECKBOXES,
				'width' => 4, 'height' => 1,
				'takesLocale' => false,
				'default' => '1',
				'label' => _t('Display empty values?'),
				'description' => _t('If checked all values will be displayed, whether there is content for them or not.')
			],
			'bottom_line' => [
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 100, 'height' => 4,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Bottom line format'),
				'description' => _t('Format per-page and per-report summary information.')
			],
			'show_only_in' => [
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'multiple' => 1,
				'width' => 100, 'height' => 4,
				'takesLocale' => false,
				'options' => [
					_t('Search/browse (thumbnail view)') => 'search_browse_thumbnail',
					_t('Search/browse (full view)') => 'search_browse_full',
					_t('Search/browse (list view)') => 'search_browse_list',
					_t('Editor summaries') => 'editor_summary',
					_t('Editor relationship bundles') => 'editor_relationship_bundle',
					_t('Set items bundles') => 'set_item_bundle'
				],
				'default' => '',
				'label' => _t('Show display in'),
				'description' => _t('Restrict display to use in specific contexts. If no contexts are selected the display will be shown in all contexts.')
			]
		]);
	}
	# ------------------------------------------------------
	/**
	 * Implement display-specific behavior to support duplication of sub-items.
	 *
	 * @param array $options Options include:
	 *		duplicateSubitems = Duplicate placements within display. [Default is false]
	 *
	 * @return ca_bundle_displays An instance containing the newly created display
	 */
	public function duplicate($options=null) {
		$we_set_transaction = false;
		$o_t = null;
		if (!$this->inTransaction()) {
			$o_t = new Transaction($this->getDb());
			$this->setTransaction($o_t);
			$we_set_transaction = true;
		} else {
			$o_t = $this->getTransaction();
		}
		
		if ($t_dupe = parent::duplicate($options)) {
			$duplicate_subitems = caGetOption(['duplicateSubitems', 'duplicate_subitems'], $options, false);
		
			if ($duplicate_subitems) { 
				// Try to dupe related ca_bundle_display_placements rows
				$o_db = $this->getDb();
				
				$qr_res = $o_db->query("
					SELECT *
					FROM ca_bundle_display_placements
					WHERE display_id = ?
				", (int)$this->getPrimaryKey());
				
				$items = [];
				while($qr_res->nextRow()) {
					$row = $qr_res->getRow();
					$row['settings'] = caUnserializeForDatabase($row['settings']);
					$items[$qr_res->get('placement_id')] = $row;
				}
				
				foreach($items as $item_id => $item) {
					$t_item = new ca_bundle_display_placements();
					if ($this->inTransaction()) { $t_item->setTransaction($this->getTransaction()); }
					$t_item->setMode(ACCESS_WRITE);
					$item['display_id'] = $t_dupe->getPrimaryKey();
					$t_item->set($item);
					$t_item->insert();
					
					if ($t_item->numErrors()) {
						$this->errors = $t_item->errors;
						if ($we_set_transaction) { $this->removeTransaction(false);}
						return false;
					}
				}
			}
		}
		
		
		if ($we_set_transaction) { $this->removeTransaction(true);}
		return $t_dupe;
	}
	# ------------------------------------------------------
	/** 
	 * Override set() to reject changes to user_id for existing rows
	 */
	public function set($pa_fields, $pm_value="", $options=null) {
		if ($this->getPrimaryKey()) {
			if (is_array($pa_fields)) {
				if (isset($pa_fields['user_id'])) { unset($pa_fields['user_id']); }
				if (isset($pa_fields['table_num'])) { unset($pa_fields['table_num']); }
			} else {
				if ($pa_fields === 'user_id') { return false; }
				if ($pa_fields === 'table_num') { return false; }
			}
		}
		return parent::set($pa_fields, $pm_value, $options);
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($options=null) {
		parent::initLabelDefinitions($options);
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
	 * @param array $options Optional array of options. Supports the following options:
	 * 		user_id = if specified then add will fail if specified user does not have edit access for the display
	 * @return int Returns placement_id of newly created placement on success, false on error
	 */
	public function addPlacement($ps_bundle_name, $pa_settings, $pn_rank=null, $options=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }
		unset(ca_bundle_displays::$s_placement_list_cache[$vn_display_id]);
		
		$pn_user_id = isset($options['user_id']) ? $options['user_id'] : null;
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)) {
			return null;
		}
		
		$t_placement = new ca_bundle_display_placements(null, null, is_array($options['additional_settings']) ? $options['additional_settings'] : null);
		$t_placement->setMode(ACCESS_WRITE);
		if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
		$t_placement->set('display_id', $vn_display_id);
		$t_placement->set('bundle_name', trim($ps_bundle_name));
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
		
		// flush sort cache as modifying display will change values
		CompositeCache::flush('sorts');
		return $t_placement->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Removes bundle placement from display
	 *
	 * @param int $pn_placement_id Placement_id of placement to remove
	 * @param array $options Optional array of options. Supports the following options:
	 * 		user_id = if specified then remove will fail if specified user does not have edit access for the display
	 * @return bool Returns true on success, false on error
	 */
	public function removePlacement($pn_placement_id, $options=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }
		$pn_user_id = isset($options['user_id']) ? $options['user_id'] : null;
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)) {
			return null;
		}
		
		$t_placement = new ca_bundle_display_placements($pn_placement_id);
		if ($t_placement->getPrimaryKey() && ($t_placement->get('display_id') == $vn_display_id)) {
			$t_placement->setMode(ACCESS_WRITE);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			$t_placement->delete(true);
			
			if ($t_placement->numErrors()) {
				$this->errors = array_merge($this->errors, $t_placement->errors);
				return false;
			}
			
			unset(ca_bundle_displays::$s_placement_list_cache[$vn_display_id]);
			
			// flush sort cache as modifying display will change values
		    CompositeCache::flush('sorts');
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of placements for the currently loaded display.
	 *
	 * @param array $options Optional array of options. Supports the following options:
	 * 		noCache = if set to true then the returned list if always generated directly from the database, otherwise it is returned from the cache if possible. Set this to true if you expect the cache may be stale. Default is false.
	 *		returnAllAvailableIfEmpty = if set to true then the list of all available bundles will be returned if the currently loaded display has no placements, or if there is no display loaded
	 *		table = if using the returnAllAvailableIfEmpty option and you expect a list of available bundles to be returned if no display is loaded, you must specify the table the bundles are intended for use with with this option. Either the table name or number may be used.
	 *		user_id = if specified then placements are only returned if the user has at least read access to the display
	 *		settingsOnly = if true the settings forms are omitted and only setting values are returned. [Default is false]
	 *		omitEditingInfo = don't include data required for inline in-spreadsheet editing. [Default is false]
	 * @return array List of placements in display order. Array is keyed on bundle name. Values are arrays with the following keys:
	 *		placement_id = primary key of ca_bundle_display_placements row - a unique id for the placement
	 *		bundle_name = bundle name (a code - not for display)
	 *		settings = array of placement settings. Keys are setting names.
	 *		display = display string for bundle
	 */
	public function getPlacements($options=null) {
		$pb_no_cache = (isset($options['noCache'])) ? (bool)$options['noCache'] : false;
		$pb_settings_only = (isset($options['settingsOnly'])) ? (bool)$options['settingsOnly'] : false;
		$pb_return_all_available_if_empty = (isset($options['returnAllAvailableIfEmpty']) && !$pb_settings_only) ? (bool)$options['returnAllAvailableIfEmpty'] : false;
		$ps_table = (isset($options['table'])) ? $options['table'] : null;
		$pn_user_id = isset($options['user_id']) ? $options['user_id'] : null;
		$pb_omit_editing_info = caGetOption('omitEditingInfo', $options, false);
		
		$ps_hierarchical_delimiter = caGetOption('hierarchicalDelimiter', $options, null);
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_READ_ACCESS__)) {
			return [];
		}
		
		if (!($vn_display_id = $this->getPrimaryKey())) {
			if ($pb_return_all_available_if_empty && $ps_table) {
				return ca_bundle_displays::$s_placement_list_cache[$vn_display_id] = $this->getAvailableBundles($ps_table);
			}
			return []; 
		}
		
		$cache_key = $vn_display_id.'/'.($pb_settings_only ? 1 : 0);
		if (!$pb_no_cache && isset(ca_bundle_displays::$s_placement_list_cache[$cache_key]) && ca_bundle_displays::$s_placement_list_cache[$cache_key]) {
			return ca_bundle_displays::$s_placement_list_cache[$cache_key];
		}
		
		$o_db = $this->getDb();
		
		$t_list = new ca_lists();
		if ($this->inTransaction()) { $t_list->setTransaction($this->getTransaction()); }
		
		$qr_res = $o_db->query("
			SELECT placement_id, bundle_name, settings
			FROM ca_bundle_display_placements
			WHERE
				display_id = ?
			ORDER BY `rank`
		", (int)$vn_display_id);
		
		$va_available_bundles = ($pb_settings_only) ? [] : $this->getAvailableBundles(null, $options);
		$placements = [];
		
		if ($qr_res->numRows() > 0) {
			$vs_subject_table = Datamodel::getTableName($this->get('table_num'));
			$t_subject = Datamodel::getInstanceByTableNum($this->get('table_num'), true);
			$t_placement = new ca_bundle_display_placements();
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$t_user = new ca_users($pn_user_id);
			while($qr_res->nextRow()) {
				$vb_use_item_values = false;
				$vs_bundle_name = $vs_bundle_name_proc = $qr_res->get('bundle_name');
				$va_bundle_name = explode(".", $vs_bundle_name);
				if (!isset($va_available_bundles[$vs_bundle_name]) && (sizeof($va_bundle_name) > 2)) {
					array_pop($va_bundle_name);
					$vs_bundle_name_proc = join('.', $va_bundle_name);
				} elseif (!isset($va_available_bundles[$vs_bundle_name]) && (sizeof($va_bundle_name) === 1)) {
					$va_bundle_name[] = 'related';
					$vs_bundle_name_proc = $vs_bundle_name = join('.', $va_bundle_name);
				}
				$vb_user_can_edit = $t_subject->isSaveable(caGetOption('request', $options, null), $vs_bundle_name);
				
				$placements[$placement_id = (int)$qr_res->get('placement_id')] = $qr_res->getRow();
				$placements[$placement_id]['settings'] = $va_settings = caUnserializeForDatabase($qr_res->get('settings'));
				$placements[$placement_id]['allowEditing'] = $vb_user_can_edit;
							
				if (!$pb_settings_only) {
					$t_placement->setSettingDefinitionsForPlacement($va_available_bundles[$vs_bundle_name_proc]['settings'] ?? null);
					$placements[$placement_id]['display'] = $va_available_bundles[$vs_bundle_name]['display'] ?? null;
					$placements[$placement_id]['settingsForm'] = $t_placement->getHTMLSettingForm(array('id' => $vs_bundle_name.'_'.$placement_id, 'settings' => $va_settings, 'table' => $vs_subject_table));
				} else {
					$t_instance = Datamodel::getInstanceByTableName($va_bundle_name[0], true);
					$placements[$placement_id]['display'] = ($t_instance ? $t_instance->getDisplayLabel($vs_bundle_name) : "???");
				}
if (!$pb_omit_editing_info) {
				if ($va_bundle_name[0] == $vs_subject_table) {
					// Only primary fields are inline-editable
				
					// Check if it is one of the types of fields that is inline editable
					if ($va_bundle_name[1] === 'preferred_labels') {
						//
						// Preferred labels are always inline editable
						//
						$placements[$placement_id]['allowInlineEditing'] = $vb_user_can_edit;
						$placements[$placement_id]['inlineEditingType'] = DT_FIELD;
					} elseif(in_array($va_bundle_name[1], ['created', 'modified'])) {
						//
						// created and modified dates are not editable
						//
						$placements[$placement_id]['allowInlineEditing'] = false;
						$placements[$placement_id]['allowEditing'] = false;
						$placements[$placement_id]['inlineEditingType'] = null;
					} elseif ($t_subject->hasField($va_bundle_name[1])) {
						//
						// Intrinsics are always editable, except for primary key and type_id
						//
						if (in_array($va_bundle_name[1], [$t_subject->getTypeFieldName(), $t_subject->primaryKey()])) {
							$placements[$placement_id]['allowInlineEditing'] = false;
							$placements[$placement_id]['allowEditing'] = false;
							$placements[$placement_id]['inlineEditingType'] = null;
						} elseif ($vs_edit_bundle = $t_subject->getFieldInfo($va_bundle_name[1], 'RESULTS_EDITOR_BUNDLE')) {
							$placements[$placement_id]['allowEditing'] = $vb_user_can_edit;
							$placements[$placement_id]['allowInlineEditing'] = false;
							$placements[$placement_id]['inlineEditingType'] = null;
						} else {
							if(isset($va_bundle_name[1])){
								// Do not allow in-line editing if the intrinsic element is identifier and
								// a). is not editable (editable = 0 in multipart_id_numbering.conf)
								// b). consists of multiple elements
								if($va_bundle_name[1] == $t_subject->getProperty('ID_NUMBERING_ID_FIELD')) {
									// check if identifier is editable
									$vb_id_editable = $t_subject->opo_idno_plugin_instance->isFormatEditable($vs_subject_table);
									
									$placements[$placement_id]['allowInlineEditing'] = false;
									$placements[$placement_id]['allowEditing'] = $vb_id_editable && $vb_user_can_edit;
								} else {
									$placements[$placement_id]['allowInlineEditing'] = $vb_user_can_edit;
								}
							}

							switch($t_subject->getFieldInfo($va_bundle_name[1], 'DISPLAY_TYPE')) {
								case 'DT_SELECT':
									if ($vs_list_code = $t_subject->getFieldInfo($va_bundle_name[1], 'LIST')) {
										$vb_use_item_values = true;
									} else {
										$vs_list_code = $t_subject->getFieldInfo($va_bundle_name[1], 'LIST_CODE');
									}
									if ($vs_list_code && ($t_list->numItemsInList($vs_list_code) <= 500)) {
										$placements[$placement_id]['inlineEditingType'] = DT_SELECT;
										if (!is_array($va_list_items = $t_list->getItemsForList($vs_list_code))) {
											break;
										}
										$va_list_items = caExtractValuesByUserLocale($va_list_items);
										
										$va_list_item_labels = [];
										foreach($va_list_items as $item_id => $va_list_item) {
											$va_list_item_labels[$vb_use_item_values ? $va_list_item['item_value'] : $item_id] = $va_list_item['name_plural'];
										}
										
										$placements[$placement_id]['inlineEditingListValues'] = array_values($va_list_item_labels);
										$placements[$placement_id]['inlineEditingListValueMap'] = array_flip($va_list_item_labels);
									} else {
										$placements[$placement_id]['inlineEditingType'] = DT_FIELD;
									}
									break;
								default:
									$placements[$placement_id]['inlineEditingType'] = DT_FIELD;
									break;
							}
						}
					} elseif ($t_subject->hasElement($va_bundle_name[1])) {
						// Attributes are editable for certain types
						$vn_data_type = ca_metadata_elements::getElementDatatype($va_bundle_name[1]);
						if (ca_bundle_displays::attributeTypeSupportsInlineEditing($vn_data_type)) {
							switch($vn_data_type) {
								default:
									$placements[$placement_id]['allowInlineEditing'] = $vb_user_can_edit;
									$placements[$placement_id]['inlineEditingType'] = DT_FIELD;
									break;
								case __CA_ATTRIBUTE_VALUE_LIST__:	
									if ($t_element = ca_metadata_elements::getInstance($va_bundle_name[1])) {
										switch($t_element->getSetting('render')) {
											case 'select':
											case 'yes_no_checkboxes':
											case 'radio_buttons':
											case 'checklist':
											case 'lookup':
											case 'horiz_hierbrowser':
											case 'horiz_hierbrowser_with_search':
											case 'vert_hierbrowser':
											case 'vert_hierbrowser_down':
												if($t_element->get("list_id") > 0) {
													if ($t_list->numItemsInList($t_element->get("list_id")) > 500) {
														// don't send very large lists
														$placements[$placement_id]['allowInlineEditing'] = false;
														$placements[$placement_id]['inlineEditingType'] = null;
													} else {
														$placements[$placement_id]['allowInlineEditing'] = $vb_user_can_edit;
														$placements[$placement_id]['inlineEditingType'] = DT_SELECT;
												
														$va_list_values = $t_list->getItemsForList($t_element->get("list_id"), array('labelsOnly' => true));
												
														$qr_list_items = caMakeSearchResult('ca_list_items', array_keys($va_list_values));
														$va_list_item_labels = [];
										
														while($qr_list_items->nextHit()) {
															if(!($v = trim($qr_list_items->get('ca_list_items.hierarchy.preferred_labels.name_plural', ['delimiter' => $ps_hierarchical_delimiter])))) { continue; }
															$va_list_item_labels[$vb_use_item_values ? $qr_list_items->get('ca_list_items.item_value') : $qr_list_items->get('ca_list_items.item_id')] = $v;
														}
														asort($va_list_item_labels);
														$placements[$placement_id]['inlineEditingListValues'] = array_values($va_list_item_labels);
														$placements[$placement_id]['inlineEditingListValueMap'] = array_flip($va_list_item_labels);
													}
												}
												break;
											default: // if it's a render setting we don't know about it's not editable
												$placements[$placement_id]['allowInlineEditing'] = false;
												$placements[$placement_id]['inlineEditingType'] = null;
												break;
										}
									}
									break;
							}
						} else {
							$placements[$placement_id]['allowInlineEditing'] = false;
							$placements[$placement_id]['inlineEditingType'] = null;
						}
					} else {
						$placements[$placement_id]['allowInlineEditing'] = false;
						$placements[$placement_id]['inlineEditingType'] = null;
					}
				} else {
					// Related bundles are never inline-editable (for now)
					$placements[$placement_id]['allowInlineEditing'] = false;
					$placements[$placement_id]['inlineEditingType'] = null;
					
					// representation media bundles aren't editable at all
					if ((($va_bundle_name[0] ?? null) == 'ca_object_representations') && (($va_bundle_name[1] ?? null) == 'media')) {
						$placements[$placement_id]['allowEditing'] = false;
					}
				}
}
			}
		} else {
			if ($pb_return_all_available_if_empty) {
				$placements = $this->getAvailableBundles($this->get('table_num'));
			}
		}
		return ca_bundle_displays::$s_placement_list_cache[$cache_key] = $placements;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of bundle displays subject to options
	 * 
	 * @param array $options Optional array of options. Supported options are:
	 *			table = If set, list is restricted to displays that pertain to the specified table. You can pass a table name or number. If omitted displays for all tables will be returned.
	 *			user_id = Restricts returned displays to those accessible by the current user. If omitted then all displays, regardless of access are returned.
	 *			restrictToTypes = Restricts returned displays to those bound to the specified type. Default is to not restrict by type.
	 *			access = Restricts returned displays to those with at least the specified access level for the specified user. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then displays where the user has at least read access will be returned. 
	 *          context = context to filter display list for. [Default is null – no filtering performed]
	 * @return array Array of displays keyed on display_id and then locale_id. Keys for the per-locale value array include: display_id,  display_code, user_id, table_num,  label_id, name (display name of display), locale_id (locale of display name), bundle_display_content_type (display name of content this display pertains to)
	 */
	 public function getBundleDisplays($options=null) {
		if (!is_array($options)) { $options = []; }
		$pm_table_name_or_num = 							caGetOption('table', $options, null);
		$pn_user_id = 										caGetOption('user_id', $options, null);
		$pn_user_access = 									caGetOption('access', $options, null); 
		$pa_access = 										caGetOption('checkAccess', $options, null); 
		$pa_restrict_to_types = 							caGetOption('restrictToTypes', $options, null, ['castTo' => 'array']);
		$pa_restrict_to_types = array_filter($pa_restrict_to_types, function($v) { return ($v == '*') ? false : (bool)$v; });
		
		$pb_system_only = 									caGetOption('systemOnly', $options, false);
		
		$vn_table_num = null;
	 	if ($pm_table_name_or_num && !($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return []; }
		
		$o_db = $this->getDb();
		
		$va_params = [];
		$va_wheres = ['((bdl.is_preferred = 1) OR (bdl.is_preferred is null))'];
		
		if ($vn_table_num > 0) {
			$va_wheres[] = "(bd.table_num = ".intval($vn_table_num).")";
		}
		
		if ($pm_table_name_or_num && is_array($pa_restrict_to_types) && sizeof($pa_restrict_to_types) && is_array($va_ancestors = caGetAncestorsForItemID($pa_restrict_to_types, ['includeSelf' => true])) && sizeof($va_ancestors)) {
			$va_wheres[] = "(cbdtr.type_id IS NULL OR cbdtr.type_id IN (?) OR (cbdtr.include_subtypes = 1 AND cbdtr.type_id IN (?)))";
			$va_params[] = $pa_restrict_to_types;
			$va_params[] = $va_ancestors;
		}
		
		if (is_array($pa_access) && (sizeof($pa_access))) {
			$pa_access = array_map("intval", $pa_access);
			$va_wheres[] = "(bd.access IN (?))";
			$va_params[] = $pa_access;
		}
		
		$va_access_wheres = [];
		if ($pn_user_id) {
			$t_user = Datamodel::getInstanceByTableName('ca_users', true);
			$t_user->load($pn_user_id);
			
			if ($t_user->getPrimaryKey()) {
				$vs_access_sql = ($pn_user_access > 0) ? " AND (access >= ".intval($pn_user_access).")" : "";
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
				
				
				$va_access_wheres[] = "({$vs_sql})";
			}
		}
		
		if (($pn_user_access == __CA_BUNDLE_DISPLAY_READ_ACCESS__) || $pb_system_only) {
			$va_access_wheres[] = "(bd.is_system = 1)";
		}
		
		if (sizeof($va_access_wheres)) {
			$va_wheres[] = "(".join(" OR ", $va_access_wheres).")";
		}
		
		// get displays
		$qr_res = $o_db->query($vs_sql = "
			SELECT
				bd.display_id, bd.display_code, bd.user_id, bd.table_num, bd.settings,
				bdl.label_id, bdl.name, bdl.locale_id, u.fname, u.lname, u.email,
				l.language, l.country
			FROM ca_bundle_displays bd
			LEFT JOIN ca_bundle_display_labels AS bdl ON bd.display_id = bdl.display_id
			LEFT JOIN ca_locales AS l ON bdl.locale_id = l.locale_id
			LEFT JOIN ca_bundle_display_type_restrictions AS cbdtr ON bd.display_id = cbdtr.display_id
			INNER JOIN ca_users AS u ON bd.user_id = u.user_id
			".(sizeof($va_wheres) ? 'WHERE ' : '')."
			".join(' AND ', $va_wheres)."
			ORDER BY -cbdtr.display_id DESC, bdl.name ASC
		", $va_params);
		
		$va_displays = [];

		$va_type_name_cache = [];
		while($qr_res->nextRow()) {
			$vn_table_num = $qr_res->get('table_num');
			if (!isset($va_type_name_cache[$vn_table_num]) || !($vs_display_type = $va_type_name_cache[$vn_table_num])) {
				$vs_display_type = $va_type_name_cache[$vn_table_num] = $this->getBundleDisplayTypeName($vn_table_num, array('number' => 'plural'));
			}
			$va_displays[$qr_res->get('display_id')][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array('bundle_display_content_type' => $vs_display_type));
			
			$va_displays[$qr_res->get('display_id')][$qr_res->get('locale_id')]['settings'] = caUnserializeForDatabase($va_displays[$qr_res->get('display_id')][$qr_res->get('locale_id')]['settings']);
		}
		if($context = caGetOption('context', $options, null)) {
			foreach($va_displays as $display_id => $info) {
				if(!is_array($info)) { continue; }
				$info = array_shift($info);
				if (is_array($info['settings']['show_only_in']) && sizeof($info['settings']['show_only_in']) && !in_array($context, $info['settings']['show_only_in'])) { 
					unset($va_displays[$display_id]);
					continue; 
				}
			}
		}
		return $va_displays;
	}
	# ------------------------------------------------------
	/**
	 * Return available displays as HTML <select> drop-down menu
	 *
	 * @param string $ps_select_name Name attribute for <select> form element 
	 * @param array $pa_attributes Optional array of attributes to embed in HTML <select> tag. Keys are attribute names and values are attribute values.
	 * @param array $options Optional array of options. Supported options include:
	 * 		Supports all options supported by caHTMLSelect() and ca_bundle_displays::getBundleDisplays() + the following:
	 *			addDefaultDisplay = if true, the "default" display is included at the head of the list; this is simply a display called "default" that is assumed to be handled by your code; the default is not to add the default value (false)
	 *			addDefaultDisplayIfEmpty = same as 'addDefaultDisplay' except that the default value is only added if the display list is empty
	 *			dontIncludeSubtypesInTypeRestriction = don't automatically include subtypes of a type when calculating type restrictions. [Default is true]
	 *          context = context to filter display list for. [Default is null – no filtering performed]
	 * @return string HTML code defining <select> drop-down
	 */
	public function getBundleDisplaysAsHTMLSelect($ps_select_name, $pa_attributes=null, $options=null) {
		if (!is_array($options)) { $options = []; }
		
		if (!isset($options['dontIncludeSubtypesInTypeRestriction'])) { $options['dontIncludeSubtypesInTypeRestriction'] = true; }
		$va_available_displays = caExtractValuesByUserLocale($this->getBundleDisplays($options));
	
		$va_content = [];
		
		if (
			(isset($options['addDefaultDisplay']) && $options['addDefaultDisplay'])
			|| 
			(isset($options['addDefaultDisplayIfEmpty']) &&  ($options['addDefaultDisplayIfEmpty']) && (!sizeof($va_available_displays)))
		) {
			$va_content[_t('Default')] = 0;
		}
		
		if (sizeof($va_content) == 0) { return ''; }
		return caHTMLSelect($ps_select_name, $va_content, $pa_attributes, $options);
	}
	# ------------------------------------------------------
	/**
	 * Returns name of type of content (synonymous with the table name for the content) currently loaded bundle display contains for display. Will return name in singular number unless the 'number' option is set to 'plural'
	 *
	 * @param int $pn_table_num Table number to return name for. If omitted then the name for the content type contained by the current bundle display will be returned. Use this parameter if you want to force a content type without having to load a bundle display.
	 * @param array $options Optional array of options. Supported options are:
	 *		number = Set to 'plural' to return plural version of name; set to 'singular' [default] to return the singular version
	 * @return string The name of the type of content or null if $pn_table_num is not set to a valid table and no form is loaded.
	 */
	public function getBundleDisplayTypeName($pm_table_name_or_num=null, $options=null) {
		if (!$pm_table_name_or_num && !($pm_table_name_or_num = $this->get('table_num'))) { return null; }
	 	if (!($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return null; }
		
		$t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
		if (!$t_instance) { return null; }
		return (isset($options['number']) && ($options['number'] == 'plural')) ? $t_instance->getProperty('NAME_PLURAL') : $t_instance->getProperty('NAME_SINGULAR');

	}
	# ------------------------------------------------------
	/**
	 * Determines if user has access to a display at a specified access level.
	 *
	 * @param int $pn_user_id user_id of user to check display access for
	 * @param int $pn_user_access type of access required. Use __CA_BUNDLE_DISPLAY_READ_ACCESS__ for read-only access or __CA_BUNDLE_DISPLAY_EDIT_ACCESS__ for editing (full) access
	 * @param int $pn_display_id The id of the display to check. If omitted then currently loaded display will be checked.
	 * @return bool True if user has access, false if not
	 */
	public function haveAccessToDisplay($pn_user_id, $pn_user_access, $pn_display_id=null) {
		$vn_display_id = null;
		if ($pn_display_id) {
			$vn_display_id = $pn_display_id;
			$t_disp = new ca_bundle_displays($vn_display_id);
			if ($this->inTransaction()) { $t_disp->setTransaction($this->getTransaction()); }
			$vn_display_user_id = $t_disp->get('user_id');
		} else {
			$vn_display_user_id = $this->get('user_id');
			$t_disp = $this;
		}
		if(!$vn_display_id && !($vn_display_id = $t_disp->getPrimaryKey())) { 
			return true; // new display
		}
		if (isset(ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_user_access])) {
			return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_user_access];
		}
		
		if (($vn_display_user_id == $pn_user_id)) {	// owners have all access
			return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_user_access] = true;
		}
		
		
		if ((bool)$t_disp->get('is_system') && ($pn_user_access == __CA_BUNDLE_DISPLAY_READ_ACCESS__)) {	// system displays are readable by all
			return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_user_access] = true;
		}
		
		$o_db =  $this->getDb();
		$qr_res = $o_db->query("
			SELECT dxg.display_id 
			FROM ca_bundle_displays_x_user_groups dxg 
			INNER JOIN ca_user_groups AS ug ON dxg.group_id = ug.group_id
			INNER JOIN ca_users_x_groups AS uxg ON uxg.group_id = ug.group_id
			WHERE 
				(dxg.access >= ?) AND (uxg.user_id = ?) AND (dxg.display_id = ?)
		", (int)$pn_user_access, (int)$pn_user_id, (int)$vn_display_id);
	
		if ($qr_res->numRows() > 0) { return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_user_access] = true; }
		
		$qr_res = $o_db->query("
			SELECT dxu.display_id 
			FROM ca_bundle_displays_x_users dxu
			INNER JOIN ca_users AS u ON dxu.user_id = u.user_id
			WHERE 
				(dxu.access >= ?) AND (u.user_id = ?) AND (dxu.display_id = ?)
		", (int)$pn_user_access, (int)$pn_user_id, (int)$vn_display_id);
	
		if ($qr_res->numRows() > 0) { return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_user_access] = true; }
		
		return ca_bundle_displays::$s_have_access_to_display_cache[$vn_display_id.'/'.$pn_user_id.'/'.$pn_user_access] = false;
	}
	# ------------------------------------------------------
	# Bundles
	# ------------------------------------------------------
	/**
	 * Returns HTML bundle for adding/editing/deleting placements from a display
	 *
	 * @param object $request The current request
	 * @param $ps_form_name The name of the HTML form this bundle will be part of
	 * @return string HTML for bundle
	 */
	public function getBundleDisplayHTMLFormBundle($request, $ps_form_name, $ps_placement_code, $options=null) {
		if (!$this->haveAccessToDisplay($request->getUserID(), __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)) {
			return null;
		}
		
		$o_view = new View($request, $request->getViewsDirectoryPath().'/bundles/');	
		
		$o_view->setVar('lookup_urls', caJSONLookupServiceUrl($request, Datamodel::getTableName($this->get('table_num'))));
		$o_view->setVar('t_display', $this);
		$o_view->setVar('placement_code', $ps_placement_code);	
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
	 * @param array $options Support options are
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
	public function getAvailableBundles($pm_table_name_or_num=null, $options=null) {
		global $g_request;
		if (!$pm_table_name_or_num) { $pm_table_name_or_num = $this->get('table_num'); }
		$pm_table_name_or_num = Datamodel::getTableNum($pm_table_name_or_num);
		if (!$pm_table_name_or_num) { return null; }
		$cache_key = caMakeCacheKeyFromOptions($options ?? [], $pm_table_name_or_num.'|'.(($g_request && $g_request->user) ? 'USER:'.$g_request->user->getPrimaryKey() : ''));
		if(CompositeCache::contains($cache_key)) {
			return CompositeCache::fetch($cache_key);
		}
		
		$t_subject = Datamodel::getInstance($pm_table_name_or_num, true);
		
		$vb_show_tooltips = (isset($options['no_tooltips']) && (bool)$options['no_tooltips']) ? false : true;
		$vs_format = (isset($options['format']) && in_array($options['format'], array('simple', 'full'))) ? $options['format'] : 'full';
		
		$t_instance = Datamodel::getInstanceByTableNum($pm_table_name_or_num, false);
		$vs_table = $t_instance->tableName();
		$vs_table_display_name = $t_instance->getProperty('NAME_PLURAL');
		
		$va_available_bundles = [];
		
		$t_placement = new ca_bundle_display_placements(null, null, []);
		
		
		// add generic bundle
		$vs_label = _t('Generic bundle');
		$vs_bundle = "{$vs_table}._generic_bundle_";
		$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}__generic_bundle_'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> {$vs_label}</div>";
		
		$va_additional_settings = [
			'format' => [
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => '490px', 'height' => 20,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Display format'),
				'description' => _t('Template used to format output.'),
				'helpText' => ''
			]
		];
		$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
		if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
		
		$va_available_bundles[$vs_display][$vs_bundle] = [
			'bundle' => $vs_bundle,
			'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
			'description' => _t('Generic template bundle for %1', caUcFirstUTF8Safe($t_instance->getProperty('NAME_PLURAL'))),
			'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
			'settings' => $va_additional_settings
		];
		
		if ($vb_show_tooltips) {
			TooltipManager::add(
				"#bundleDisplayEditorBundle_".str_replace('.', '_', $vs_bundle),
				$this->_formatBundleTooltip($vs_label, $vs_bundle, _t('Use this generic %1 bundle to display %1 templates not specific to a single metadata element.', $t_instance->getProperty('NAME_SINGULAR')))
			);
		}
		
		// get intrinsic fields
		$t_placement = new ca_bundle_display_placements(null, null, []);
		if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
		$va_additional_settings = array(
			'maximum_length' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 100,
				'label' => _t('Maximum length'),
				'description' => _t('Maximum length, in characters, of displayed information.')
			),
			'format' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => '490px', 'height' => 20,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Display format'),
				'description' => _t('Template used to format output.')
			)
		);
		foreach($t_instance->getFormFields() as $vs_f => $info) {
			if (isset($info['DONT_USE_AS_BUNDLE']) && $info['DONT_USE_AS_BUNDLE']) { continue; }
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
		$va_element_codes = $t_instance->getApplicableElementCodes(null, false, $options['no_cache'] ?? false);
		
		$t_md = new ca_metadata_elements();
		if ($this->inTransaction()) { $t_md->setTransaction($this->getTransaction()); }
		$va_all_elements = $t_md->getElementsAsList();
		
		$va_additional_settings = array(
			'format' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => '490px', 'height' => 20,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Display format'),
				'description' => _t('Template used to format output.'),
				'helpText' => ''
			),
			'locale' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => "200px", 'height' => 1,
				'default' => '',
				'useLocaleList' => true,
				'allowNull' => true,
				'label' => _t('Locale'),
				'description' => _t('Locale to use for output.')
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
			if (!is_null($va_all_elements[$vn_element_id]['settings']['canBeUsedInDisplay'] ?? null) && !$va_all_elements[$vn_element_id]['settings']['canBeUsedInDisplay']) { continue; }
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			if (caGetBundleAccessLevel($vs_table, $vs_element_code) == __CA_BUNDLE_ACCESS_NONE__) {
				continue;	
			}
			
			switch($va_all_elements[$vn_element_id]['datatype'] ?? null) {
				case __CA_ATTRIBUTE_VALUE_TEXT__:
					$va_even_more_settings = array(
						'newlines' => array(
							'formatType' => FT_TEXT,
							'displayType' => DT_SELECT,
							'width' => 30, 'height' => 1,
							'takesLocale' => false,
							'default' => 'HTML',
							'options' => array(
								_t('Preserve newlines') => 'NL2BR',
								_t('Display as HTML') => 'HTML'
							),
							'label' => _t('Newlines'),
							'description' => _t('Determines how newlines in text are processed.')
						)		
					);
					break;
				case __CA_ATTRIBUTE_VALUE_LIST__:
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
				case __CA_ATTRIBUTE_VALUE_MEDIA__:
					$va_even_more_settings = [
					    'appendMultiPagePDFToPDFOutput' => [
							'formatType' => FT_NUMBER,
							'displayType' => DT_CHECKBOXES,
							'width' => 10, 'height' => 1,
							'takesLocale' => false,
							'default' => '0',
							'label' => _t('Append multipage PDF to output?'),
							'description' => _t('Check this option if you want PDF media in display appended to the end of PDF display output.')
						    ]
						];
					break;
				case __CA_ATTRIBUTE_VALUE_CONTAINER__:	// (allows sub-elements to be summarized)
				case __CA_ATTRIBUTE_VALUE_CURRENCY__: 
				case __CA_ATTRIBUTE_VALUE_LENGTH__: 
				case __CA_ATTRIBUTE_VALUE_WEIGHT__:
				case __CA_ATTRIBUTE_VALUE_TIMECODE__: 
				case __CA_ATTRIBUTE_VALUE_INTEGER__: 
				case __CA_ATTRIBUTE_VALUE_NUMERIC__: 
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
					
					if (($va_all_elements[$vn_element_id]['datatype'] ?? null) == 6) {
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
					$va_even_more_settings = [];
					break;
			}
			
			$vs_bundle = $vs_table.'.'.$vs_element_code;
			
			$va_even_more_settings['format'] = $va_additional_settings['format'];
			//$va_even_more_settings['format']['helpText'] = $this->getTemplatePlaceholderDisplayListForBundle($vs_bundle);
			
			$t_placement = new ca_bundle_display_placements(null, null, array_merge($va_additional_settings, $va_even_more_settings));
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
		
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
					'width' => '490px', 'height' => 20,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				),
				'locale' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'width' => "200px", 'height' => 1,
					'default' => '',
					'useLocaleList' => true,
					'allowNull' => true,
					'label' => _t('Locale'),
					'description' => _t('Locale to use for output.')
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
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
		
			$vs_bundle = $vs_table.'.preferred_labels';
			
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
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$vs_bundle = $vs_table.'.nonpreferred_labels';
			
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
		
		if ($vs_table == 'ca_objects') {
			
			$va_additional_settings = array(
				'format' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => '490px', 'height' => 20,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				)	
			);
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$vs_bundle = 'ca_object_checkouts';
			$vs_label = _t('Library checkouts');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_preferred_labels'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('Library checkouts')."</div>";
			$vs_description = _t('List of library checkouts that include this object');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_ca_object_checkouts",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
			
			$va_additional_settings = array(
				'format' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => '490px', 'height' => 20,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				)
			);
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$vs_bundle = $vs_table.'.ca_objects_location';
			$vs_label = _t('Current location');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_ca_objects_location'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('Current location')."</div>";
			$vs_description = _t('Current location of object');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_ca_objects_location",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
			
			$vs_bundle = $vs_table.'.home_location_value';
			$vs_label = _t('Home location didplay value');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_home_location_value'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('Home location display value')."</div>";
			$vs_description = _t('Home location of object');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_home_location_value",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}

		}
		
		if (method_exists($t_instance, 'tablesTakeHistoryTracking') && in_array($vs_table, $vs_table::tablesTakeHistoryTracking())) {
			$va_additional_settings = array(
				'format' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => '490px', 'height' => 20,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				),
				'policy' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'default' => '__default__',
					'width' => "275px", 'height' => 1,
					'useHistoryTrackingPolicyList' => true,
					'label' => _t('Use history tracking policy'),
					'description' => ''
				)
			);
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$vs_bundle = $vs_table.'.history_tracking_current_value';
			$vs_label = _t('History tracking current value');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_history_tracking_current_value'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('History tracking current value')."</div>";
			$vs_description = _t('Current value for history tracking policy');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0', 'table' => $vs_table)),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_history_tracking_current_value",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
			
			$vs_bundle = $vs_table.'.history_tracking_current_date';
			$vs_label = _t('History tracking current value date');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_history_tracking_current_date'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('History tracking current value date')."</div>";
			$vs_description = _t('Current value date for history tracking policy');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0', 'table' => $vs_table)),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_history_tracking_current_date",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
			
			$va_additional_settings = array(
				'format' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => '490px', 'height' => 20,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				),
				'policy' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'default' => '__default__',
					'width' => "275px", 'height' => 1,
					'useHistoryTrackingReferringPolicyList' => true,
					'label' => _t('Use history tracking policy'),
					'description' => ''
				)
			);
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$vs_bundle = $vs_table.'.history_tracking_current_contents';
			$vs_label = _t('History tracking current contents');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_history_tracking_current_contents'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('History tracking contents')."</div>";
			$vs_description = _t('Current value date for history tracking policy');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0', 'table' => $vs_table)),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_history_tracking_current_contents",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		
		
		    $va_additional_settings = array(
				'display_template' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => '490px', 'height' => 20,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				)
			);
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$vs_bundle = $vs_table.'.submitted_by_user';
			$vs_label = _t('Submitted by user');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_submitted_by_user'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('Submitted by user')."</div>";
			$vs_description = _t('Name and email address user that submitted item');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0', 'table' => $vs_table)),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_submitted_by_user",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
			
			$va_additional_settings = array(
				'display_template' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => '490px', 'height' => 20,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Display format'),
					'description' => _t('Template used to format output.')
				)
			);
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
			$vs_bundle = $vs_table.'.submission_group';
			$vs_label = _t('Submitted by group');
			$vs_display = "<div id='bundleDisplayEditorBundle_{$vs_table}_submission_group'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('Submission group')."</div>";
			$vs_description = _t('Group item was submitted under');
			
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
				'description' => $vs_description,
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0', 'table' => $vs_table)),
				'settings' => $va_additional_settings
			);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditorBundle_submission_group",
					$this->_formatBundleTooltip($vs_label, $vs_bundle, $vs_description)
				);
			}
		}
		
		if (caGetBundleAccessLevel($vs_table, "ca_object_representations") != __CA_BUNDLE_ACCESS_NONE__) {
			// get object representations (objects only, of course)
			if (is_a($t_subject, 'RepresentableBaseModel')) {
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
					),
					'show_nonprimary' => array(
						'formatType' => FT_TEXT,
						'displayType' => DT_SELECT,
						'width' => 35, 'height' => 1,
						'takesLocale' => false,
						'default' => 0,
						'options' => array(
							_t('Yes') => 1,
							_t('No') => 0
						),
						'label' => _t('Include non-primary media'),
						'description' => _t('Includes non-primary media in display.')
					),					
                    'delimiter' => array(
                        'formatType' => FT_TEXT,
                        'displayType' => DT_FIELD,
                        'width' => 35, 'height' => 1,
                        'takesLocale' => false,
                        'default' => '',
                        'label' => _t('Delimiter'),
                        'description' => _t('Text to place in-between repeating values.')
                    )
				);
			
				$o_media_settings = new MediaProcessingSettings('ca_object_representations', 'media');
				$va_versions = $o_media_settings->getMediaTypeVersions('*');
				
				foreach($va_versions as $vs_version => $va_version_info) {
					$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
					if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
		
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
				if ($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction()); }
				
				foreach(array('mimetype', 'md5', 'original_filename') as $vs_rep_field) {
					$vs_bundle = 'ca_object_representations.'.$vs_rep_field;
					$vs_display = "<div id='bundleDisplayEditorBundle_ca_object_representations_{$vs_rep_field}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_rep->getProperty('NAME_SINGULAR'))."</span> ".($vs_label = $t_rep->getDisplayLabel($vs_bundle))."</div>";
					
					$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
						'bundle' => $vs_bundle,
						'display' => ($vs_format == 'simple') ? $vs_label : $vs_display,
						'description' => $vs_description = $t_rep->getDisplayDescription($vs_bundle),
						'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
						'settings' => []
					);
				}
				
			}
		}
		
		// get related items
		
		foreach(array(
			'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations', 'ca_loans', 'ca_movements', 'ca_list_items', 'ca_object_representations'
		) as $vs_related_table) {
			if ($this->getAppConfig()->get($vs_related_table.'_disable') && !(($vs_related_table == 'ca_object_representations') && (!$this->getAppConfig()->get('ca_objects_disable')))) { continue; }			
			if (caGetBundleAccessLevel($vs_table, $vs_related_table) == __CA_BUNDLE_ACCESS_NONE__) { continue; }
			
			if ($vs_related_table === $vs_table) { 
				$vs_bundle = $vs_related_table.'.related';
			} else {
				$vs_bundle = $vs_related_table;
			}
			
			$t_rel_instance = Datamodel::getInstanceByTableName($vs_related_table, true);
			$vs_table_name = Datamodel::getTableName($this->get('table_num'));
			$va_path = array_keys(Datamodel::getPath($vs_table_name, $vs_related_table));
			if ((sizeof($va_path) < 2) || (sizeof($va_path) > 3)) { continue; }		// only use direct relationships (one-many or many-many)
			
			$va_additional_settings = array(
				'format' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => '490px', 'height' => 20,
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
					'multiple' => 1,
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
					'multiple' => 1,
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
				'sort' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FIELD,
					'width' => 35, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Sort using'),
					'description' => _t('Override sort option for this field. Use this if you want result lists to sort on a different field when clicking on this bundle.')
				),
				'numPerPage' => array(
					'formatType' => FT_NUMBER,
					'displayType' => DT_FIELD,
					'default' => 100,
					'width' => "5", 'height' => 1,
					'label' => _t('Number of items to load per page'),
					'description' => _t('Maximum number of items to render on initial load.')
				)
			);
			
			if($vs_related_table == 'ca_object_representations') {
				$va_additional_settings['show_nonprimary'] = [
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'width' => 35, 'height' => 1,
					'takesLocale' => false,
					'default' => 0,
					'options' => array(
						_t('Yes') => 1,
						_t('No') => 0
					),
					'label' => _t('Include non-primary media'),
					'description' => _t('Includes non-primary media in display.')
				];
			}
			
			if ($vs_related_table == 'ca_list_items') {
				$va_additional_settings['restrictToLists'] = array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'showLists' => true,
					'width' => 60, 'height' => 5,
					'multiple' => 1,
					'takesLocale' => false,
					'label' => _t('Restrict to list'),
					'description' => _t('Display related items from selected lists only. If no lists are selected then all related items are displayed.')
				);
			}
			
			if ($t_rel_instance->isHierarchical()) {
				$va_additional_settings += array(
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
			}
			
			if (
				(($vs_table === 'ca_objects') && ($vs_related_table === 'ca_storage_locations'))
				||
				(($vs_table === 'ca_storage_locations') && ($vs_related_table === 'ca_objects'))
				||
				(($vs_table === 'ca_objects') && ($vs_related_table === 'ca_movements'))
				||
				(($vs_table === 'ca_movements') && ($vs_related_table === 'ca_objects'))
				||
				(($vs_table === 'ca_storage_locations') && ($vs_related_table === 'ca_movements'))
				||
				(($vs_table === 'ca_movements') && ($vs_related_table === 'ca_storage_locations'))
			) {
				$va_additional_settings['showCurrentOnly'] = array(
					'formatType' => FT_TEXT,
					'displayType' => DT_CHECKBOXES,
					'width' => "10", 'height' => "1",
					'takesLocale' => false,
					'default' => '0',
					'label' => _t('Show current only?'),
					'description' => _t('If checked only current objects are displayed.')
				);
			}
			
			//$va_additional_settings['format']['helpText'] = $this->getTemplatePlaceholderDisplayListForBundle($vs_bundle);
		
			$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
			if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
			
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
		$t_placement = new ca_bundle_display_placements(null, null, $va_additional_settings);
		if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
		
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
		
		uksort($va_available_bundles, function($a, $b) {
			return strcasecmp(strip_tags($a), strip_tags($b));
		});
		$va_sorted_bundles = [];
		foreach($va_available_bundles as $vs_k => $va_val) {
			foreach($va_val as $vs_real_key => $info) {
				$va_sorted_bundles[$vs_real_key] = $info;
			}
		}
		CompositeCache::save($cache_key, $va_sorted_bundles);
		return $va_sorted_bundles;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of placements in the currently loaded display
	 *
	 * @param array $options Optional array of options. Supported options are:
	 *		noCache = if set to true, no caching of placement values is performed. 
	 *		no_tooltips = if set no tooltips for available bundles will be emitted. Default is false - tooltips will be emitted.
	 *		format = specifies label format for bundles. Valid values are "simple" (just the name of the element) or "full" (name of element, name of type of item element pertains to and alternate label, if defined). Default is "full"
	 *		user_id = if specified then placements are only returned if the user has at least read access to the display
	 * @return array List of placements. Each element in the list is an array with the following keys:
	 *		display = A display label for the bundle
	 *		bundle = The bundle name
	 */
	public function getPlacementsInDisplay($options=null) {
		if (!is_array($options)) { $options = []; }
		$pb_no_cache = caGetOption('noCache', $options, false);
		$pn_user_id = caGetOption('user_id', $options, null);
		
		if ($pn_user_id && !$this->haveAccessToDisplay($pn_user_id, __CA_BUNDLE_DISPLAY_READ_ACCESS__)) {
			return [];
		}
		
		$vb_show_tooltips = !caGetOption(['no_tooltips', 'noToolTips'], $options, false);
		$vs_format = caGetOption('format', $options, 'full', array('validValues' => array('simple', 'full')));
		
		if (!($pn_table_num = Datamodel::getTableNum($this->get('table_num')))) { return null; }
		if (!($t_instance = Datamodel::getInstance($pn_table_num, true))) { return null; }
		
		if(!is_array($placements = $this->getPlacements($options))) { $placements = []; }
		
		$placements_in_display = [];
		foreach($placements as $placement_id => $va_placement) {
			$vs_label = ($vs_label = $t_instance->getDisplayLabel($va_placement['bundle_name'])) ? $vs_label : $va_placement['bundle_name'];
			if(is_array($va_placement['settings'] ?? null) && is_array($va_placement['settings']['label'] ?? null)){
				$tmp = caExtractValuesByUserLocale(array($va_placement['settings']['label'] ?? null));
				if ($vs_user_set_label = array_shift($tmp)) {
					$vs_label = "{$vs_label} (<em>{$vs_user_set_label}</em>)";
				}
			}
			$vs_display = "<div id='bundleDisplayEditor_{$placement_id}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> {$vs_label}</div>";
			$va_placement['display'] = ($vs_format == 'simple') ? $vs_label : $vs_display;
			$va_placement['bundle'] = $va_placement['bundle_name']; // we used 'bundle' in the arrays, but the database field is called 'bundle_name' and getPlacements() returns data directly from the database
			unset($va_placement['bundle_name']);
			
			$placements_in_display[$placement_id] = $va_placement;
			
			$vs_description = $t_instance->getDisplayDescription($va_placement['bundle']);
			
			if ($vb_show_tooltips) {
				TooltipManager::add(
					"#bundleDisplayEditor_{$placement_id}",
					$this->_formatBundleTooltip($vs_label, $va_placement['bundle'], $vs_description)
				);
			}
		}
		
		return $placements_in_display;
	}
	# ------------------------------------------------------
	private function _formatBundleTooltip($ps_label, $ps_bundle, $ps_description) {
		return "<div><strong>{$ps_label}</strong></div><div class='bundleDisplayElementBundleName'>{$ps_bundle}</div><br/><div>{$ps_description}</div>";
	}
	# ------------------------------------------------------
	/**
	 * Returns number of placements in the currently loaded display
	 *
	 * @param array $options Optional array of options. Supported options are:
	 *		noCache = if set to true, no caching of placement values is performed.
	 *		user_id = if specified then placements are only returned if the user has at least read access to the display
	 * @return int Number of placements. 
	 */
	public function getPlacementCount($options=null) {
		return is_array($p = $this->getPlacementsInDisplay($options)) ? sizeof($p) : 0;
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
	 	$t_instance = null;
	 	
		$tmp = explode('.', $ps_bundle_name);
		switch(sizeof($tmp)) {
			case 2:
				$vs_table = $tmp[0];
				$vs_bundle = $tmp[1];
				
				if ($vs_bundle == 'rel') { $vs_bundle = 'preferred_labels'; }
				break;
			case 1:
				if (!($t_instance = Datamodel::getInstanceByTableName($tmp[0], true))) {
					$vs_table = Datamodel::getTableName($this->get('table_num'));
					$vs_bundle = $tmp[0];
				} else {
					$vs_table = $tmp[0];
					$vs_bundle = 'preferred_labels';
				}
				break;
			default:
				return null;
				break;
		}
		
		if (!$t_instance) {
			if(!($t_instance = Datamodel::getInstanceByTableName($vs_table, true))) { return null; }
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
			if ($this->inTransaction()) { $t_element->setTransaction($this->getTransaction()); }
		
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
		
		$va_buf = [];
		
		if (is_array($va_list) && sizeof($va_list)) {
			foreach($va_list as $vs_tag => $info) {
				$va_buf[] = "<tr><td class='settingsKeyRow'>{$info['label']}</td><td class='settingsKeyRow'>{$vs_tag}</td></tr>\n";	
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
	 *		showHierarchy = 
	 *		returnInfo = Include information about the underlying display bundle in an array. Information includes the display value, the number of discrete values in the bundle for the current row, type of bundle, minumum and maximum number of repeating values allowed and where applicable a list of related ids (attribute ids for attributes, label_ids for labels, row_ids for related) [Default is false]
	 * @return mixed The processed value ready for display, unless returnInfo option is set in which case an array is returned
	 */
	public function getDisplayValue($po_result, $pn_placement_id, $options=null) {
		if (!is_array($options)) { $options = []; }
		
		$vb_return_info =	caGetOption('returnInfo', $options, false);
		
		if (!is_numeric($pn_placement_id)) {
			$vs_bundle_name = $pn_placement_id;
			$va_placement = [];
		} elseif($pn_placement_id < 0) {	// default display
			$val = $bundle = null;
			switch((int)$pn_placement_id) {
				case -1:	// idno
					
					if($instance = is_a($po_result, 'BaseModel') ? $po_result : $po_result->getInstance()) {
						$val = $po_result->get($bundle = $instance->tableName().'.'.$instance->getProperty('ID_NUMBERING_ID_FIELD'));
					}
					$bundle_type =  'intrinsic';
					break;
				case -2:	// display name	
					if($instance = is_a($po_result, 'BaseModel') ? $po_result : $po_result->getInstance()) {
						$val = $po_result->get($bundle = $instance->tableName().'.preferred_labels');
					}
					$bundle_type =  'preferred_labels';
					break;
				default:
					return null;
					break;
			}
			if($vb_return_info) {
				return [
					'value' => $val,
					'bundle' => $bundle,
					'type' => $bundle_type,
					'minCount' => 1,
					'maxCount' => 1,
					'count' => 1,
					'ids' => $po_result->getPrimaryKey(),
					'inlineEditable' => true
				];
			} else {
				return $val;
			}
		} else {
			$placements = $this->getPlacements(['settingsOnly' => true, 'omitEditingInfo' => true]);
			$va_placement = $placements[$pn_placement_id];
			$vs_bundle_name = $va_placement['bundle_name'];
		}
		$va_settings = 		caGetOption('settings', $va_placement, [], array('castTo' => 'array'));
		$o_request = 		caGetOption('request', $options, null);
		
		$vb_include_nonprimary_media = caGetOption('show_nonprimary', $options, false);
		
		if(method_exists($po_result, 'filterNonPrimaryRepresentations')) { $po_result->filterNonPrimaryRepresentations(!$vb_include_nonprimary_media); }
		
		if (!isset($options['convertCodesToDisplayText'])) { $options['convertCodesToDisplayText'] = true; }
		if (!isset($options['forReport'])) { $options['forReport'] = false; }
		if (!isset($options['purify'])) { $options['purify'] = false; }
		if (!isset($options['asHTML'])) { $options['asHTML'] = true; }
		
		if (!isset($options['maximumLength'])) { $options['maximumLength'] =  ($va_settings['maximum_length'] ?? null) ? $va_settings['maximum_length'] : null; }
		if (!isset($options['filter'])) { $options['filter'] = caGetOption('filter', $va_settings, null); }
		
		$options['locale'] = ca_locales::IDToCode(caGetOption('locale', $va_settings, caGetOption('locale', $options, null)));
		$options['delimiter'] = caGetOption('delimiter', $options, caGetOption('delimiter', $va_settings, '; '));
		$options['dateFormat'] = caGetOption('dateFormat', $options, caGetOption('dateFormat', $va_settings, ''));
		$options['useSingular'] = (isset($va_settings['sense']) && ($va_settings['sense'] == 'singular')) ? true : false;
		$options['returnURL'] = (isset($va_settings['display_mode']) && ($va_settings['display_mode'] == 'url'))  ? true : false;
		
		if(caGetOption('display_currency_conversion', $va_settings, false) && $o_request && $o_request->isLoggedIn()) {
			$options['displayCurrencyConversion'] = $o_request->user->getPreference('currency');
		}
		
		$va_bundle_bits = explode('.', $vs_bundle_name);
		$options['bundle'] = $vs_bundle_name;
		
		$options['restrictToRelationshipTypes'] = 	caGetOption('restrict_to_relationship_types', $va_settings, null);
		$options['restrictToTypes'] =				caGetOption('restrict_to_types', $va_settings, null);
		$options['removeFirstItems'] =				caGetOption('remove_first_items', $va_settings, null);
		$options['hierarchyDirection'] =				caGetOption('hierarchy_order', $va_settings, null);
		$options['hierarchyDelimiter'] =				caGetOption('hierarchical_delimiter', $va_settings, null);
		
		$pb_show_hierarchy = caGetOption(array('showHierarchy', 'show_hierarchy'), $options, false);
		
		unset($options['format']);	// don't pass format strings to get() here
		if ((sizeof($va_bundle_bits) == 1) || ((sizeof($va_bundle_bits) == 2) && ($va_bundle_bits[1] == 'related'))) {
			$options['template'] = caGetOption('format', $va_settings, $this->getAppConfig()->get($va_bundle_bits[0].'_relationship_display_format'));;
		} else {
			$options['template'] = caGetOption('format', $va_settings, null);
		}
		
		$t_instance = null;
		$vs_val = '';
		
		// Use configured default template when available
		if(!($vs_template = trim($options['template'])) && (sizeof($va_bundle_bits) == 1) && ($t_instance = Datamodel::getInstanceByTableName($va_bundle_bits[0], true))) {
			$vs_template = $this->getAppConfig()->get($va_bundle_bits[0]."_default_bundle_display_template");
		}
		
		if ((!$vs_template) && ($t_element = ca_metadata_elements::getInstance($va_bundle_bits[sizeof($va_bundle_bits)-1]))) { 
			$vs_template = $t_element->getSetting('displayTemplate'); 
		}
		
		if(!$pb_show_hierarchy && $vs_template) {
			unset($options['template']);
			
			// Hack to rewrite object-object lot relationship in standard relationship syntax for display template
			if(($va_bundle_bits[0] === 'ca_objects') && ($va_bundle_bits[1] === 'lot_id')) {
				$va_bundle_bits = ['ca_object_lots'];
			}
			
			if ($t_instance = Datamodel::getInstanceByTableName($va_bundle_bits[0], true)) {
				$va_bundle_bits_proc = $va_bundle_bits;
				$vb_is_related = false;
				if ((sizeof($va_bundle_bits) == 1) || ((sizeof($va_bundle_bits) == 2) && $va_bundle_bits[1] == 'related')) {
					// pulling related
					$vb_is_related = true;
				} elseif ((sizeof($va_bundle_bits_proc) > 1) && (in_array($vs_tmp = array_pop($va_bundle_bits_proc), array('related')))) {
					// pulling related
					$va_bundle_bits_proc[] = $vs_tmp;
					$vb_is_related = true;
				} else {
					// pulling current record
					$va_bundle_bits_proc[] = $t_instance->primaryKey();
				}
				
				if ($vb_is_related) {
					$vs_restrict_to_types = (is_array($options['restrictToTypes']) && sizeof($options['restrictToTypes'])) ? "restrictToTypes=\"".join("|", $options['restrictToTypes'])."\"" : "";
					$vs_restrict_to_relationship_types = (is_array($options['restrictToRelationshipTypes']) && sizeof($options['restrictToRelationshipTypes'])) ? "restrictToRelationshipTypes=\"".join("|", $options['restrictToRelationshipTypes'])."\"" : "";
					
					// resolve template relative to relationship
					if (is_array($va_path = Datamodel::getPath($po_result->tableName(), $rel_table = $t_instance->tableName()))) {
						$va_path = array_keys($va_path);
						
						$vs_sort_dir_attr = '';
						if ($vs_sort = trim(caGetOption('sort', $options, null, ['castTo' => 'string']))) {
						    $vs_sort_dir = caGetOption('sortDirection', $options, null, ['castTo' => 'string']);
						    unset($options['sort']);
						    unset($options['sortDirection']);
						} else { 
						    $vs_sort = caGetOption('sort', $va_settings, null, ['castTo' => 'string']); 
						    $vs_sort_dir = caGetOption('sortDirection', $va_settings, null, ['castTo' => 'string']);
						}
						$tmp = explode('.', $vs_sort);
						if(Datamodel::tableExists($tmp[0])) {
							$vs_sort_attr = "sort=\"{$vs_sort}\"";	
						} elseif (($vs_sort_attr = ($vs_sort) ? "sort=\"{$rel_table}.{$vs_sort}\"" : "")) {
						    $vs_sort_dir_attr = ($vs_sort_dir) ? "sortDirection=\"{$vs_sort_dir}\"" : "";
						}
						$max_items = (int)caGetOption('numPerPage', $va_settings, 0);
						
						$filter_primary_attr = 'filterNonPrimaryRepresentations="'.($vb_include_nonprimary_media ? "0" : "1").'"';
						
						$vs_unit_tag = "<unit ".(($max_items > 0) ? "length=\"{$max_items}\"" : '')." relativeTo=\"".$va_path[1]."\" delimiter=\"".$options['delimiter']."\" {$vs_restrict_to_types} {$vs_restrict_to_relationship_types} {$vs_sort_attr} {$vs_sort_dir_attr} {$filter_primary_attr}>";

						switch(sizeof($va_path)) {
							case 3:
								// For regular relationships just evaluate the template relative to the relationship record
								// this way the template can reference interstitial data
								$t = (caGetOption('showCurrentOnly', $options, true) && !$vs_restrict_to_types  && !$vs_restrict_to_relationship_types) ? $vs_template : $vs_unit_tag.$vs_template."</unit>";
								$vs_val = $po_result->getWithTemplate($t, $options);
								break;
							case 2:
								$t_rel = Datamodel::getInstanceByTableName($va_path[1], true);
								if (method_exists($t_rel, 'isSelfRelationship') && $t_rel->isSelfRelationship()) {
									// is a self-relationship
									$vs_val = $po_result->getWithTemplate($vs_unit_tag.$vs_template."</unit>", array_merge($options, array('primaryIDs' => array($po_result->tableName() => array($po_result->getPrimaryKey())))));
								} else {
									// is a many-one relationship; evaluate the template for these relative
									// to the related record
									$vs_val = $po_result->getWithTemplate($vs_unit_tag.$vs_template."</unit>", $options);
								}
								break;
							default:
								$vs_val = _t("???");
								break;
						}
					}
				} else {
					// resolve template relative to current record
					$rtc = null;
					$element_code = $va_bundle_bits[sizeof($va_bundle_bits)-1];
					if(!in_array($element_code, ['_generic_bundle_', 'history_tracking_current_value'], true)) {
						// Set container context for all bundles except generic and current value bundles
						// We skip current value bundles because some extant templates pull in data outside of the current value
						// container for display, and as current value never repeats the utility of setting context here is limited
						$dt = ca_metadata_elements::getElementDatatype($element_code);
						$rtc = ($dt === 0) ? $vs_bundle_name : null;
					}
					$vs_val = $po_result->getWithTemplate($vs_template, [
							'relativeToContainer' => $rtc, 
							'filters'=> $options['filters'] ?? null, 
							'delimiter' => $options['delimiter'] ?? null, 
							'policy' => $va_settings['policy'] ?? null
						]		// passed for history tracking current value
					);
				}
			}
		} else {
			// Straight get
			if($pb_show_hierarchy && (sizeof($va_bundle_bits) == 1)) {
				$va_bundle_bits[] = 'hierarchy.preferred_labels.name';
			}
			
			// policy passed for history tracking current value
			// returnTagWithPath passed to force absolute file path to be used when running reports – some systems cannot handle urls in PDFs due to DNS configuration
			$vs_val = $po_result->get(join(".", $va_bundle_bits), array_merge(['doRefSubstitution' => true], $options, ['policy' => $va_settings['policy'] ?? null, 'returnTagWithPath' => $options['forReport']]));	
		}
		
		if($options['forReport']) {
			$vs_val = strip_tags($vs_val, $this->getAppConfig()->get('report_allowed_text_tags') ?? []);
		}
		
		if (isset($options['purify']) && $options['purify']) {
    		$vs_val = ca_bundle_displays::getPurifier()->purify($vs_val);
		}
		
		if ($vb_return_info) {
			if (!$t_instance) { $t_instance = Datamodel::getInstanceByTableName($va_bundle_bits[0], true); }
			
			if(is_array($tmp = $po_result->get(join(".", $va_bundle_bits), array_merge($options, ['returnWithStructure' => true])))) {
				$info_data = array_shift($tmp);
			}
			if(!is_array($info_data)) { $info_data = []; }
			
			$vs_inline_editing_type = $va_inline_editing_list_values = $inline_editing_list_id = null;
				
			if($va_bundle_bits[0] !== $po_result->tableName()) {
				// related
				return array(
					'value' => $vs_val,
					'bundle' => join('.', $va_bundle_bits),
					'type' => 'related',
					'minCount' => 0,
					'maxCount' => null,
					'count' => sizeof($info_data),
					'ids' => array_keys($info_data),
					'inlineEditable' => false
				);
			} elseif($va_bundle_bits[1] == 'preferred_labels') {
				// preferred label
				return array(
					'value' => $vs_val,
					'bundle' => join('.', $va_bundle_bits),
					'type' => 'preferred_labels',
					'minCount' => 1,
					'maxCount' => 1,
					'count' => sizeof($info_data),
					'ids' => array_keys($info_data),
					'inlineEditable' => true
				);
			} elseif($va_bundle_bits[1] == 'nonpreferred_labels') {
				// nonpreferred label
				return array(
					'value' => $vs_val,
					'bundle' => join('.', $va_bundle_bits),
					'type' => 'nonpreferred_labels',
					'minCount' => 0,
					'maxCount' => null,
					'count' => sizeof($info_data),
					'ids' => array_keys($info_data),
					'inlineEditable' => false
				);
			} elseif ($t_instance->hasField($va_bundle_bits[1])) {
				$vb_editable = true;
				$vs_bundle_name = join('.', $va_bundle_bits);
				if ($t_instance->getProperty('ID_NUMBERING_ID_FIELD') === $va_bundle_bits[1]) {
					// ... except for idno's 
					$vb_editable = false;
				} elseif(in_array($va_bundle_bits[1], [$t_instance->getTypeFieldName(), $t_instance->primaryKey(), 'is_deaccessioned'])) {
					// ... and type_id fields, which are never inline editable
					$vb_editable = false;
				}
				
				switch($va_bundle_bits[1]) {
					case 'is_deaccessioned':
						$vs_bundle_name = 'ca_objects_deaccession';
						break;
				}
				
				return array(
					'value' => $vs_val,
					'bundle' => $vs_bundle_name,
					'type' => 'intrinsic',
					'minCount' => 1,
					'maxCount' => 1,
					'count' => 1,
					'ids' => null,
					'inlineEditable' => $vb_editable
				);
			} elseif ($t_instance->hasElement($va_bundle_bits[1])) {
				// attributes
				$vn_min = $vn_max = null;
				if ($t_element = ca_metadata_elements::getInstance($va_bundle_bits[1])) {
					if ($t_restriction = $t_element->getTypeRestrictionInstanceForElement($t_instance->tableNum(), $po_result->get($t_instance->tableName().'.'.$t_instance->getTypeFieldName()))) { 
						$vn_min = $t_restriction->getSetting('minAttributesPerRow');
						$vn_max = $t_restriction->getSetting('maxAttributesPerRow');
					} 
				}
				
				$vn_data_type = $t_element ? $t_element->get('datatype') : null;

				return array(
					'value' => $vs_val,
					'bundle' => join('.', $va_bundle_bits),
					'type' => 'attribute',
					'elementType' => $vn_data_type,
					'minCount' => $vn_min,
					'maxCount' => $vn_max,
					'count' => $vn_count = sizeof($info_data),
					'ids' => array_keys($info_data),
					'inlineEditable' => ca_bundle_displays::attributeTypeSupportsInlineEditing($vn_data_type) && ($vn_count >= 0) && ($vn_count <= 1)
				);
			} else {
				// special
				return array(
					'value' => $vs_val,
					'bundle' => join('.', $va_bundle_bits),
					'type' => 'special',
					'minCount' => 1,
					'maxCount' => 1,
					'count' => sizeof($info_data),
					'ids' => null,
					'inlineEditable' => false
				);
			}
			
		}
		if(($options['maximumLength'] > 0) && (mb_strlen($vs_val) > $options['maximumLength'])) {
			$doc = new DOMDocument();
			@$doc->loadHTML('<?xml encoding="utf-8" ?>'.mb_substr(caEscapeForXML($vs_val), 0, $options['maximumLength']));
			return $doc->saveHTML();
		}
		
		if(caGetOption('newlines', $va_settings, null) === 'NL2BR') {
			$vs_val = nl2br($vs_val);
		}
		return $vs_val;
	}
	# ----------------------------------------
	# Screen editor
	# ----------------------------------------
	/**
	 * Used when saving content from a ca_bundle_display_placements bundle in the ca_editor_ui_screens editor
	 *
	 * @param RequestHTTP $request
	 * @param string $ps_form_prefix
	 * @param string $ps_placement_code
	 *
	 * return bool
	 */
	public function savePlacementsFromHTMLForm($request, $ps_form_prefix, $ps_placement_code) {;
		if ($vs_bundles = $request->getParameter("{$ps_placement_code}{$ps_form_prefix}displayBundleList", pString)) {
			$va_bundles = explode(';', $vs_bundles);
			$t_display = new ca_bundle_displays($this->getPrimaryKey());
			if ($this->inTransaction()) { $t_display->setTransaction($this->getTransaction()); }
			$placements = $t_display->getPlacements(array('user_id' => $request->getUserID()));
			
			// remove deleted bundles
			
			foreach($placements as $placement_id => $va_bundle_info) {
				if (!in_array($va_bundle_info['bundle_name'].'_'.$va_bundle_info['placement_id'], $va_bundles)) {
					$t_display->removePlacement($va_bundle_info['placement_id'], array('user_id' => $request->getUserID()));
					if ($t_display->numErrors()) {
						$this->errors = $t_display->errors;
						return false;
					}
				}
			}
			
			$va_locale_list = ca_locales::getLocaleList(array('index_by_code' => true));

			$va_available_bundles = $t_display->getAvailableBundles();
			foreach($va_bundles as $i => $vs_bundle) {
				// get settings
				
				if (preg_match('!^(.*)_([\d]+)$!', $vs_bundle, $va_matches)) {
					$placement_id = (int)$va_matches[2];
					$vs_bundle = $va_matches[1];
				} else {
					$placement_id = null;
				}
				$vs_bundle_proc = str_replace(".", "_", $vs_bundle);
				
				$va_settings = [];
			
				foreach($_REQUEST as $vs_key => $vs_val) {
					if (preg_match("!^{$vs_bundle_proc}_([\d]+)_([^\d]+.*)$!", $vs_key, $va_matches)) {
						
						// is this locale-specific?
						if (preg_match('!(.*)_([a-z]{2}_[A-Z]{2})$!', $va_matches[2], $va_locale_matches)) {
							$vn_locale_id = isset($va_locale_list[$va_locale_matches[2]]) ? (int)$va_locale_list[$va_locale_matches[2]]['locale_id'] : 0;
							$va_settings[(int)$va_matches[1]][$va_locale_matches[1]][$vn_locale_id] = $vs_val;
						} else {
							$va_settings[(int)$va_matches[1]][$va_matches[2]] = $vs_val;
						}
					}
				}
				
				if(((int)$placement_id === 0)) {
					$t_display->addPlacement($vs_bundle, $va_settings[$placement_id] ?? null, $i + 1, array('user_id' => $request->getUserID(), 'additional_settings' => $va_available_bundles[$vs_bundle]['settings'] ?? []));
					if ($t_display->numErrors()) {
						$this->errors = $t_display->errors;
						return false;
					}
				} else {
					$t_placement = new ca_bundle_display_placements($placement_id, null, $va_available_bundles[$vs_bundle]['settings']);
					if ($this->inTransaction()) { $t_placement->setTransaction($this->getTransaction()); }
					$t_placement->set('rank', $i + 1);
					
					if (is_array($va_settings[$placement_id] ?? null)) {
						//foreach($va_settings[$placement_id] as $vs_setting => $vs_val) {
						foreach($t_placement->getAvailableSettings() as $vs_setting => $va_setting_info) {
							$vs_val = isset($va_settings[$placement_id][$vs_setting]) ? $va_settings[$placement_id][$vs_setting] : null;
						
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
		return true;
	}
	# ----------------------------------------
	# Type restrictions
	# ----------------------------------------
	/**
	 * Adds restriction (a binding between the display and item type)
	 *
	 * @param int $pn_type_id the type
	 * @param array $pa_settings Options include:
	 *		includeSubtypes = automatically expand type restriction to include sub-types. [Default is false]
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function addTypeRestriction($pn_type_id, $pa_settings=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		if (!is_array($pa_settings)) { $pa_settings = []; }
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('table_num')))) { return false; }
		
		$va_type_list = $t_instance->getTypeList();
		if (!isset($va_type_list[$pn_type_id])) { return false; }
		
		$t_restriction = new ca_bundle_display_type_restrictions();
		if ($this->inTransaction()) { $t_restriction->setTransaction($this->getTransaction()); }
		$t_restriction->setMode(ACCESS_WRITE);
		$t_restriction->set('table_num', $this->get('table_num'));
		$t_restriction->set('type_id', $pn_type_id);
		$t_restriction->set('display_id', $this->getPrimaryKey());
		$t_restriction->set('include_subtypes', caGetOption('includeSubtypes', $pa_settings, 0));
		
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
		$t_restriction = new ca_bundle_display_type_restrictions($pn_restriction_id);
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
	 * Sets restrictions for currently loaded display
	 *
	 * @param array $pa_type_ids list of types to restrict to
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function setTypeRestrictions($pa_type_ids, $options=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		if (!is_array($pa_type_ids)) {
			if (is_numeric($pa_type_ids)) { 
				$pa_type_ids = array($pa_type_ids); 
			} else {
				$pa_type_ids = [];
			}
		}
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('table_num')))) { return false; }
		
		$va_type_list = $t_instance->getTypeList();
		$va_current_restrictions = $this->getTypeRestrictions();
		$va_current_type_ids = [];
		foreach($va_current_restrictions as $i => $va_restriction) {
			$va_current_type_ids[$va_restriction['type_id']] = $va_restriction['restriction_id'];
		}
		
		foreach($va_type_list as $vn_type_id => $va_type_info) {
			if(in_array($vn_type_id, $pa_type_ids)) {
				// need to set
				if(!isset($va_current_type_ids[$vn_type_id])) {
					$this->addTypeRestriction($vn_type_id, $options);
				} else {
					$this->editTypeRestriction($va_current_type_ids[$vn_type_id], $options);
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
	 * Remove restriction from currently loaded display for specified type
	 *
	 * @param int $pn_type_id The type of the restriction
	 * @return bool True on success, false on error, null if no screen is loaded
	 */
	public function removeTypeRestriction($pn_type_id=null) {
		if (!($vn_display_id = (int)$this->getPrimaryKey())) { return null; }
		
		$va_params = ['display_id' => $vn_display_id];
		if ((int)$pn_type_id > 0) { $va_params['type_id'] = (int)$pn_type_id; }

		if (is_array($va_displays = ca_bundle_display_type_restrictions::find($va_params, ['returnAs' => 'modelInstances']))) {
			foreach($va_displays as $t_display) {
				$t_display->setMode(ACCESS_WRITE);
				$t_display->delete(true);
				if ($t_display->numErrors()) {
					$this->errors = $t_display->errors();
					return false;
				}
			}
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
		return $this->removeTypeRestriction();
	}
	# ----------------------------------------
	/**
	 * Return restrictions for currently loaded display
	 *
	 * @param int $pn_type_id Type to limit returned restrictions to; if omitted or null then all restrictions are returned
	 * @return array A list of restrictions, false on error or null if no ui is loaded
	 */
	public function getTypeRestrictions($pn_type_id=null) {
		if (!($vn_display_id = $this->getPrimaryKey())) { return null; }
		
		$va_params = ['display_id' => $vn_display_id];
		if ((int)$pn_type_id > 0) { $va_params['type_id'] = (int)$pn_type_id; }

		return ca_bundle_display_type_restrictions::find($va_params, ['returnAs' => 'arrays']);
	}
	# ----------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of type restriction in the currently loaded display
	 * 
	 * @param object $request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getTypeRestrictionsHTMLFormBundle($request, $ps_form_name, $ps_placement_code, $options=null) {
		$o_view = new View($request, $request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_display', $this);			
		$o_view->setVar('id_prefix', $ps_form_name);	
		$o_view->setVar('placement_code', $ps_placement_code);		
		$o_view->setVar('request', $request);
		
		$va_type_restrictions = $this->getTypeRestrictions();
		$va_restriction_type_ids = [];
		
		$vb_include_subtypes = false;
		if (is_array($va_type_restrictions)) {
			foreach($va_type_restrictions as $i => $va_restriction) {
				$va_restriction_type_ids[] = $va_restriction['type_id'];
				if ($va_restriction['include_subtypes'] && !$vb_include_subtypes) { $vb_include_subtypes = true; }
			}
		}
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($vn_table_num = $this->get('table_num')))) { return null; }
		
		$vs_subtype_element = caProcessTemplate($this->getAppConfig()->get('form_element_display_format_without_label'), [
			'ELEMENT' => _t('Include subtypes?').' '.caHTMLCheckboxInput('type_restriction_include_subtypes', ['value' => '1', 'checked' => $vb_include_subtypes])
		]);
		$o_view->setVar('type_restrictions', $t_instance->getTypeListAsHTMLFormElement('type_restrictions[]', array('multiple' => 1, 'height' => 5), array('forceEnabled' => true, 'value' => 0, 'values' => $va_restriction_type_ids)).$vs_subtype_element);
	
		return $o_view->render('ca_bundle_display_type_restrictions.php');
	}
	# ----------------------------------------
	public function saveTypeRestrictionsFromHTMLForm($request, $ps_form_prefix, $ps_placement_code) {
		if (!$this->getPrimaryKey()) { return null; }
		
		return $this->setTypeRestrictions($request->getParameter('type_restrictions', pArray), ['includeSubtypes' => $request->getParameter('type_restriction_include_subtypes', pInteger)]);
	}
	# ------------------------------------------------------
	/**
	 * Check if specific attribute type can support inline (aka. spreadsheet) editing. 
	 * Being an element of a supported type is a necessary, but not sufficient, condition for inline 
	 * editing. Other factors, such as repeatibility, may prevent inline editing for some values.
	 *
	 * @param int $type The attribute type
	 * @return bool 
	 */
	public static function attributeTypeSupportsInlineEditing(int $type) {
		return in_array($type, [
			__CA_ATTRIBUTE_VALUE_TEXT__,
			__CA_ATTRIBUTE_VALUE_DATERANGE__,
		  	__CA_ATTRIBUTE_VALUE_URL__,
		 	__CA_ATTRIBUTE_VALUE_CURRENCY__,
		 	__CA_ATTRIBUTE_VALUE_LENGTH__,
			__CA_ATTRIBUTE_VALUE_WEIGHT__,
			__CA_ATTRIBUTE_VALUE_TIMECODE__,
			__CA_ATTRIBUTE_VALUE_INTEGER__,
			__CA_ATTRIBUTE_VALUE_NUMERIC__,
			__CA_ATTRIBUTE_VALUE_LIST__
		]);
	}
	# ------------------------------------------------------------------
	# Support for display-based editing of search/browse results
	# aka. the "results editor"
	# ------------------------------------------------------------------
	/**
	 * Return list of bundles in display with inline editing settings for each.
	 *
	 * @param string $ps_tablename Name of edited table name
	 * @param array $options Options include:
	 *		user_id = id of current user; used to calculate access restrictions. [Default is null] 
	 *		type_id = id of type to restrict display to. [Default is null]
	 * @return array Array with two keys: "displayList" contains the list of bundles; "headers" contains column headers for the editor
	 */
	public function getDisplayListForResultsEditor(string $table, $options=null) {
		$display_list = [];
		
		$t_model = Datamodel::getInstanceByTableName($table, true);
		
		$user_id = caGetOption('user_id', $options, null);
		$type_id = caGetOption('type_id', $options, null);
		
		if(is_null($user_id) || $this->haveAccessToDisplay($user_id, __CA_BUNDLE_DISPLAY_READ_ACCESS__)) {
			$placements = $this->getPlacements(['settingsOnly' => true, 
				'hierarchicalDelimiter' => ' ➜ ', 'user_id' => $user_id, 
				'request' => caGetOption('request', $options, null)
			]);
		
			foreach($placements as $placement_id => $display_item) {
				$va_settings = caUnserializeForDatabase($display_item['settings']);
				
				// get column header text
				$vs_header = $display_item['display'];
				if (isset($va_settings['label']) && is_array($va_settings['label'])) {
					$tmp = caExtractValuesByUserLocale(array($va_settings['label']));
					if ($vs_tmp = array_shift($tmp)) { $vs_header = $vs_tmp; }
				}
				
				$display_list[$placement_id] = array(
					'placement_id' => 				$placement_id,
					'bundle_name' => 				$display_item['bundle_name'] ?? null,
					'display' => 					$vs_header,
					'settings' => 					$va_settings,
					'allowEditing' =>				$display_item['allowEditing'] ?? null,
					'allowInlineEditing' => 		$display_item['allowInlineEditing'] ?? null,
					'inlineEditingType' => 			$display_item['inlineEditingType'] ?? null,
					'inlineEditingList' => 			$display_item['inlineEditingList'] ?? null,
					'inlineEditingListValues' => 	$display_item['inlineEditingListValues'] ?? null,
					'inlineEditingListValueMap' => 	$display_item['inlineEditingListValueMap'] ?? null
				);
			}
		}
		
		//
		// Default display list (if none are specifically defined)
		//
		if (!sizeof($display_list)) {
			if ($idno_fld = $t_model->getProperty('ID_NUMBERING_ID_FIELD')) {
				$va_multipart_id = new MultipartIDNumber($table, '__default__', null, $t_model->getDb());
				$display_list['-1'] = array(
					'placement_id' => 				'-1',
					'bundle_name' => 				"{$table}.{$idno_fld}",
					'display' => 					$t_model->getDisplayLabel($table.'.'.$idno_fld),
					'settings' => 					[],
					'allowEditing' =>				true,
					'allowInlineEditing' => 		$va_multipart_id->isFormatEditable($table),
					'inlineEditingType' => 			DT_FIELD,
					'inlineEditingListValues' => 	[],
					'inlineEditingListValueMap' => 	[]
				);
			}
			
			if (method_exists($t_model, 'getLabelTableInstance') && ($t_label = $t_model->getLabelTableInstance()) && !(($table === 'ca_objects') && ($this->getAppConfig()->get('ca_objects_dont_use_labels')))) {
				$display_list['-2'] = array(
					'placement_id' => 				'-2',
					'bundle_name' => 				$l = $t_label->tableName().'.'.$t_label->getDisplayField(),
					'display' => 					$t_label->getDisplayLabel($l),
					'settings' => 					[],
					'allowEditing' =>				true,
					'allowInlineEditing' => 		true,
					'inlineEditingType' => 			DT_FIELD,
					'inlineEditingListValues' => 	[],
					'inlineEditingListValueMap' => 	[]
				);
			}
		}
		
		// figure out which items in the display are sortable
		if (method_exists($t_model, 'getApplicableElementCodes')) {
			$va_sortable_elements = ca_metadata_elements::getSortableElements($t_model->tableName());
			$va_attribute_list = array_flip($t_model->getApplicableElementCodes($type_id, false, false));
			$t_label = $t_model->getLabelTableInstance();
			$label_table_name = $t_label ? $t_label->tableName() : null;
			$label_display_field = $t_label ? $t_label->getDisplayField() : null;
			foreach($display_list as $i => $display_item) {
				$tmp = explode('.', $display_item['bundle_name']);

				if(!isset($tmp[1])){
					$tmp[1] = null;
				}

				if (
					(($tmp[0] === $label_table_name) && ($tmp[1] === $label_display_field))
					||
					(($tmp[0] == $table) && ($tmp[1] === 'preferred_labels'))
				) {
					$display_list[$i]['is_sortable'] = true;
					$display_list[$i]['bundle_sort'] = $label_table_name.'.'.$t_model->getLabelSortField();
					continue;
				}
				
				if ($tmp[0] !== $table) { continue; }
				
				if ($t_model->hasField($tmp[1])) {
					if($t_model->getFieldInfo($tmp[1], 'FIELD_TYPE') == FT_MEDIA) { // sorting media fields doesn't really make sense and can lead to sql errors
						continue;
					}
					$display_list[$i]['is_sortable'] = true;
					
					if ($t_model->hasField($tmp[1].'_sort')) {
						$display_list[$i]['bundle_sort'] = $display_item['bundle_name'].'_sort';
					} else {
						$display_list[$i]['bundle_sort'] = $display_item['bundle_name'];
					}
					continue;
				}
				
				if (isset($va_attribute_list[$tmp[1]]) && ($va_sortable_elements[$va_attribute_list[$tmp[1]]] ?? null)) {
					$display_list[$i]['is_sortable'] = true;
					$display_list[$i]['bundle_sort'] = $display_item['bundle_name'];
					continue;
				}
			}
		}
		
		$va_headers = [];
		foreach($display_list as $display_item) {
			$va_headers[] = $display_item['display'];
		}
		
		return array('displayList' => $display_list, 'headers' => $va_headers);
	}
	# ------------------------------------------------------
	/**
	 * Convert list of bundle names into placement list for use with results editor
	 *
	 * @param array $bundles Array of bundle names
	 * @param array $settings Array of placement settings to control how bundle is rendered
	 * @return array Array of placements. Each value is an array with information about a column in the inline editor.
	 */
	static public function makeBundlesForResultsEditor($bundles, $settings=null) {		
		$placements = [];

		$i = 1;
		foreach($bundles as $i => $field) {
			$bundle = str_replace(",", ".", $field);
			$placement = str_replace(",", "_", $field);
			
			$tmp = explode(".", $bundle);
			if ($t_instance = Datamodel::getInstanceByTableName($tmp[0], true)) {
				if ($edit_bundle = $t_instance->getFieldInfo($tmp[1], 'RESULTS_EDITOR_BUNDLE')) {	
					// substitute bundle name for intrinsic (used to allow "special" bundles to be used for editing of intrinsics)
					$bundle = $edit_bundle;
				}
			}
			
			$bundle = preg_replace("!\.related$!", "", $bundle);  // Remove .related specifier as editor form generator doesn't need or recognize it
			$placements[] = [
				'placement_code' => "{$placement}_{$i}",
				'bundle_name' => $bundle,
				'settings' => isset($settings[$i]) ? $settings[$i] : null
			];
			$i++;
		}
		
		return $placements;
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of columns suitable for use with ca.tableview.js
	 * (implements "spreadsheet" editing UI)
	 *
	 * @param array $display_list
	 * @param array $options Options include:
	 *		request = The current request, used to calculate service urls. [Default is null]
	 * @return array
	 */ 
	static public function getColumnsForResultsEditor($display_list, $options=null) {
		$request = caGetOption('request', $options, null); 
		$bundle_names = caExtractValuesFromArrayList($display_list, 'bundle_name', array('preserveKeys' => true));
		$column_spec = [];

		foreach($bundle_names as $placement_id => $bundle_name) {
			if (!(bool)$display_list[$placement_id]['allowInlineEditing']) {
				// Read only
				$column_spec[] = array(
					'data' => $placement_id,  
					'readOnly' => !(bool)$display_list[$placement_id]['allowInlineEditing'],
					'allowEditing' => $display_list[$placement_id]['allowEditing']
				);
				continue;
			}
			switch($display_list[$placement_id]['inlineEditingType']) {
				case DT_SELECT:
					$column_spec[] = array(
						'data' => $placement_id,
						'readOnly' => false,
						'type' => 'DT_SELECT',
						'source' => $display_list[$placement_id]['inlineEditingListValues'],
						'sourceMap' => $display_list[$placement_id]['inlineEditingListValueMap'],
						'strict' => true,
						'allowEditing' => $display_list[$placement_id]['allowEditing']
					);
					break;
				case DT_LOOKUP:
					if ($request) {
						$va_urls = caJSONLookupServiceUrl($request, 'ca_list_items');
						$column_spec[] = array(
							'data' => $placement_id, 
							'readOnly' => false,
							'type' => 'DT_LOOKUP',
							'list' => caGetListCode($display_list[$placement_id]['inlineEditingList']),
							'sourceMap' => $display_list[$placement_id]['inlineEditingListValueMap'],
							'lookupURL' => $va_urls['search'],
							'strict' => false,
							'allowEditing' => $display_list[$placement_id]['allowEditing']
						);
					}
					break;
				default:
					$column_spec[] = array(
						'data' => $placement_id,
						'readOnly' => false,
						'type' => 'DT_FIELD',
						'allowEditing' => $display_list[$placement_id]['allowEditing']
					);
					break;
			}
		}
		
		return $column_spec;
	}
	# ------------------------------------------------------------------
	/** 
	 * Save data from results editor. Data may be saved in two ways
	 *	(1) "inline" from the spreadsheet view. Data in a changed cell will be submitted here in a "changes" array.
	 *  (2) "complex" editing from a popup editing window. Data is submitted from a form as standard editor UI form data from a psuedo editor UI screen.
	 *
	 * @param string $table Name of edited tables
	 * @param array $options Options include:
	 *		request = The current request, used to fetch data to save. This option is mandatory. If not set no data will be saved. [Default is null]
	 * @return bool False on error, true on success
	 */
	public function saveResultsEditorData($table, $options=null) {
		if (!$t_subject = Datamodel::getInstanceByTableName($table, true)) { return null; }
		$request = caGetOption('request', $options, null);
		
		$va_response = [];
		$va_error_list = [];
		$va_ids = [];
		if ($request && is_array($changes = $request->getParameter('changes', pArray))) {
			// If "changes" is set this is a simple inline edit
			foreach($changes as $va_change) {
				$va_ids[] = $id = $va_change['id'];
				
				if ($t_subject->load($id)) {
					$t_subject->setMode(ACCESS_WRITE);
					
					$placement_id = $va_change['change'][1];
					
					if ($placement_id > 0) {
						$placement = new ca_bundle_display_placements($placement_id);
						if(!$placement->isLoaded()) { continue; }
						
						$bundle = [
							'placement_id' => $placement_id,
							'bundle_name' => $placement->get('bundle_name')
						];
					} else {
						// Handle default display
						$bundles = $this->getDisplayListForResultsEditor($table);
						if(!isset($bundles['displayList'][(int)$placement_id])) { continue; }
						$bundle = [
							'placement_id' => $placement_id,
							'bundle_name' => $bundles['displayList'][(int)$placement_id]['bundle_name']
						];
					}
					
					$vb_set_value = false;
					
					
					if (!$t_subject->isSaveable($request, $bundle['bundle_name'])) { 
						$va_error_list[$bundle['bundle_name']] = _t('Could not save change');
						continue; 
					}
					
					$bundle_info = $t_subject->getBundleInfo($bundle['bundle_name']);
					switch($bundle_info['type']) {
						case 'intrinsic':
							$tmp = explode('.', $bundle['bundle_name']);
							$vs_key = 'P'.$bundle['placement_id'].'_resultsEditor'.$tmp[1]; // bare field name for intrinsics
							
							break;
						case 'preferred_label':
						case 'nonpreferred_label':
							$vs_label_id = null;
							if (
								is_array($tmp = $t_subject->get($bundle['bundle_name'], ['returnWithStructure' => true]))
								&&
								is_array($va_vals = array_shift($tmp))
								&&
								is_array($va_label_ids = array_keys($va_vals))
								&& 
								(sizeof($va_label_ids) > 0)
							) {
								$vs_label_id = array_shift($va_label_ids);
							} else {
								$vs_label_id = 'new_0';
							}
							$vs_key_stub = 'P'.$bundle['placement_id'].(($bundle_info['type'] == 'nonpreferred_label') ? '_resultsEditor_NPref' : '_resultsEditor_Pref');
							$vs_key = $vs_key_stub.$t_subject->getLabelDisplayField().'_'.$vs_label_id;
							$request->setParameter($vs_locale_key = $vs_key_stub.'locale_id_'.$vs_label_id, $_REQUEST[$vs_locale_key] = 1);
							
							break;
						case 'attribute':
							$tmp = explode(".", $bundle['bundle_name']);
							$t_element = ca_metadata_elements::getInstance($tmp[1]);
							$vn_element_id = $t_element->getPrimaryKey();
							
							$vs_attribute_id = null;
							if (
								is_array($tmp = $t_subject->get($bundle['bundle_name'], ['returnWithStructure' => true]))
								&&
								is_array($va_vals = array_shift($tmp))
								&&
								is_array($va_attr_ids = array_keys($va_vals))
								&& 
								(sizeof($va_attr_ids) > 0)
							) {
								$vs_attribute_id = array_shift($va_attr_ids);
							} else {
								$vs_attribute_id = 'new_0';
							}
							$vs_key = 'P'.$bundle['placement_id'].'_resultsEditor_attribute_'.$vn_element_id.'_'.$vn_element_id.'_'.$vs_attribute_id;
							
							break;
						default:
							// noop
							break;
					}
					
					$vb_set_value = true;
					$request->setParameter($vs_key, $_REQUEST[$vs_key] = $va_change['change'][3]);

					
					if($vb_set_value) { 
						$save_options = [
							'bundles' => [$bundle], 'formName' => '_resultsEditor'
						];
						$t_subject->saveBundlesForScreen(null, $request, $save_options);
					}
					if ($request->numActionErrors()) { 
						$bundles = $request->getActionErrorSources();
						foreach($bundles as $bundle_name) {
							$errors_for_bundle = [];
							foreach($request->getActionErrors($bundle_name) as $o_error) {
								$errors_for_bundle[$id] = $o_error->getErrorDescription();
							}
							$va_error_list[$bundle_name] = join("; ", $errors_for_bundle);
						}
					}
				}
			}
		} else {
			return $this->saveResultsEditorComplexData($table, $options);
		}
		
		return [
			'status' => sizeof($va_error_list) ? 10 : 0,
			'id' => $va_ids,
			'row' => null, 'col' => null,
			'table' => $t_subject->tableName(),
			'type_id' => method_exists($t_subject, "getTypeID") ? $t_subject->getTypeID() : null,
			'display' => $this->getDisplayValue($t_subject, $placement_id),
			'time' => time(),
			'errors' => array_flip($va_error_list)
		];
	}
	# -------------------------------------------------------
	/**
	 * Saves the content of a form editing new or existing records. It returns the same form + status 
	 * messages rendered into the current view, inherited from ActionController.
	 *
	 * @param string $table Name of edited tables
	 * @param array $options Options include:
	 *		request = The current request, used to fetch data to save. This option is mandatory. If not set no data will be saved. [Default is null]
	 * @return 
	 */
	public function saveResultsEditorComplexData($table, $options=null) {
		if (!($request = caGetOption('request', $options, null))) { return null; }
		if (!($t_subject = Datamodel::getInstanceByTableName($table, true))) { return null; }
		
		$placement_id = $request->getParameter('placement_id', pInteger);
		$t_placement = new ca_bundle_display_placements($placement_id); 
		$bundle = 				$t_placement->get('bundle_name');
		$pn_id = 				$request->getParameter('id', pInteger);
		$pn_row = 				$request->getParameter('row', pInteger);
		$pn_col = 				$request->getParameter('col', pInteger);
		
		$display_config = 		$this->getDisplayListForResultsEditor($table, $options);
		
		$display_list = 		array_values($display_config['displayList']);
		$placement_id = 		$display_list[$pn_col]['placement_id'];
		
		if (!$t_subject->load($pn_id)) {
			return [
				'status' => 30,
				'id' => null,
				'row' => $pn_row, 'col' => $pn_col,
				'table' => $t_subject->tableName(),
				'type_id' => null,
				'display' => null,
				'time' => time(),
				'errors' => array_flip(array(_t("Invalid ID")))
			];
		}
		
		//
		// Is record of correct type?
		// 
		$restrict_to_types = null;
		if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
			$restrict_to_types = caGetTypeRestrictionsForUser($table, array('access' => __CA_BUNDLE_ACCESS_EDIT__));
		}
		if (is_array($restrict_to_types) && !in_array($t_subject->get('type_id'), $restrict_to_types)) {
			return [
				'status' => 30,
				'id' => $pn_id,
				'row' => $pn_row, 'col' => $pn_col,
				'table' => $t_subject->tableName(),
				'type_id' => null,
				'display' => null,
				'time' => time(),
				'errors' => array_flip(array(_t("Invalid Type ID")))
			];
		}
		
		//
		// Is record from correct source?
		// 
		$restrict_to_sources = null;
		if ($t_subject->getAppConfig()->get('perform_source_access_checking')) {
			if (is_array($restrict_to_sources = caGetSourceRestrictionsForUser($table, array('access' => __CA_BUNDLE_ACCESS_EDIT__)))) {
				if (
					(!$t_subject->get('source_id'))
					||
					($t_subject->get('source_id') && !in_array($t_subject->get('source_id'), $restrict_to_sources))
					||
					((strlen($vn_source_id = $request->getParameter('source_id', pInteger))) && !in_array($vn_source_id, $restrict_to_sources))
				) {
					$t_subject->set('source_id', $t_subject->getDefaultSourceID(array('request' => $request)));
				}
		
				if (is_array($restrict_to_sources) && !in_array($t_subject->get('source_id'), $restrict_to_sources)) {
					return [
						'status' => 30,
						'id' => $pn_id,
						'row' => $pn_row, 'col' => $pn_col,
						'table' => $t_subject->tableName(),
						'type_id' => null,
						'display' => null,
						'time' => time(),
						'errors' => array_flip(array(_t("Invalid Source ID")))
					];
				}
			}
		}
		
		// Make sure request isn't empty
		if(!sizeof($_POST)) {
			return [
				'status' => 20,
				'id' => null,
				'row' => $pn_row, 'col' => $pn_col,
				'table' => $t_subject->tableName(),
				'type_id' => null,
				'display' => null,
				'time' => time(),
				'errors' => array_flip(array(_t("Cannot save using empty request. Are you using a bookmark?")))
			];
		}
		
		// Set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
		$context_id = null;
		if ($idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
			if ($t_subject->getPrimaryKey() > 0) {
				$context_id = $t_subject->get($idno_context_field);
			} 
			
			if ($context_id) { $t_subject->set($idno_context_field, $context_id); }
		}
		
		// Set type name for display
		if (!($type_name = $t_subject->getTypeName())) {
			$type_name = $t_subject->getProperty('NAME_SINGULAR');
		}
		
		$o_app_plugin_manager = new ApplicationPluginManager();
		
		# trigger "BeforeSaveItem" hook 
		$o_app_plugin_manager->hookBeforeSaveItem([
			'id' => null, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 
			'instance' => $t_subject, 'is_insert' => false
		]);

		$save_options = ['bundles' => ca_bundle_displays::makeBundlesForResultsEditor([$bundle]), 'formName' => 'complex'];
		$vb_save_rc = $t_subject->saveBundlesForScreen(null, $request, $save_options);
		
		$vs_message = _t("Saved changes to %1", $type_name);
		
		$va_errors = $request->getActionErrors();							// all errors from all sources
		$va_general_errors = $request->getActionErrors('general');		// just "general" errors - ones that are not attached to a specific part of the form
		
		if(is_array($va_errors) && is_array($va_general_errors) && ((sizeof($va_errors) - sizeof($va_general_errors)) > 0)) {
			$error_list = [];
			$no_save_error = false;
			foreach($va_errors as $o_e) {
				$error_list[$o_e->getErrorDescription()] = $o_e->getErrorDescription()."\n";
				
				switch($o_e->getErrorNumber()) {
					case 1100:	// duplicate/invalid idno
						if (!$vn_subject_id) {		// can't save new record if idno is not valid (when updating everything but idno is saved if it is invalid)
							$no_save_error = true;
						}
						break;
				}
			}
		}
		
		# trigger "SaveItem" hook 
		$o_app_plugin_manager->hookSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => false));
		
		$id = $t_subject->getPrimaryKey();
		
		return [
			'status' => (is_array($error_list) && sizeof($error_list)) ? 10 : 0,
			'id' => $id,
			'row' => $pn_row, 'col' => $pn_col,
			'table' => $t_subject->tableName(),
			'type_id' => method_exists($t_subject, "getTypeID") ? $t_subject->getTypeID() : null,
			'display' => $this->getDisplayValue($t_subject, $placement_id),
			'time' => time(),
			'errors' => $error_list
		];
	}
	# ------------------------------------------------------
}
