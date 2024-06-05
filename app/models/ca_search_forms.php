<?php
/** ---------------------------------------------------------------------
 * app/models/ca_search_forms.php : table access class for table ca_search_forms
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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

define('__CA_SEARCH_FORM_NO_ACCESS__', 0);
define('__CA_SEARCH_FORM_READ_ACCESS__', 1);
define('__CA_SEARCH_FORM_EDIT_ACCESS__', 2);


BaseModel::$s_ca_models_definitions['ca_search_forms'] = array(
	'NAME_SINGULAR' 	=> _t('search form'),
	'NAME_PLURAL' 		=> _t('search forms'),
	'FIELDS' 			=> array(
		'form_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this form')
		),
		'user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
			'DEFAULT' => '',
			'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who created the form.')
		),
		'form_code' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 22, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'LABEL' => _t('Form code'), 'DESCRIPTION' => _t('Unique code for form, used to identify the form for configuration purposes. You will need to specify this if you are using this form in a special context (on a web front-end, for example) in which the form must be unambiguously identified.'),
			'BOUNDS_LENGTH' => array(0,100),
			'REQUIRES' => array('is_administrator'),
			'UNIQUE_WITHIN' => []
		),
		'is_system' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Is system form'), 'DESCRIPTION' => _t('Set this if the form is a form used by the system (as opposed to a user defined form). In general, system forms are defined by the system installer - you should not have to create system forms on your own.'),
			'BOUNDS_VALUE' => array(0,1),
			'REQUIRES' => array('is_administrator')
		),
		'table_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DONT_USE_AS_BUNDLE' => true,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Search type'), 'DESCRIPTION' => _t('Determines what type of search (objects, entities, places, etc.) the form will conduct.'),
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
				_t('object representations') => 56
			)
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Search form settings.')
		)
	)
);

class ca_search_forms extends BundlableLabelableBaseModelWithAttributes {
	use ModelSettings, SetUniqueIdnoTrait;

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
	protected $TABLE = 'ca_search_forms';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'form_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('idno');

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
	protected $ORDER_BY = array('form_code');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20;

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

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
	protected $USERS_RELATIONSHIP_TABLE = 'ca_search_forms_x_users';
	protected $USER_GROUPS_RELATIONSHIP_TABLE = 'ca_search_forms_x_user_groups';

	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_search_form_labels';

	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = null;				// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = null;				// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = null;

	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'form_code';		// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;			// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	

	# Local config files containing specs for search elements
	private $opo_search_config;
	private $opo_search_indexing_config;

	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;

	# cache for haveAccessToForm()
	static $s_have_access_to_form_cache = [];


	static $s_placement_list_cache;		// cache for getPlacements()

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
	public function __construct($id=null, ?array $options=null) {
		// Filter list of tables form can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_search_forms']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caFilterTableList(BaseModel::$s_ca_models_definitions['ca_search_forms']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);

		parent::__construct($id, $options);	# call superclass constructor

		$this->opo_search_config = Configuration::load(__CA_CONF_DIR__.'/search.conf');
		$this->opo_search_indexing_config = Configuration::load(__CA_CONF_DIR__.'/search_indexing.conf');

		$this->setAvailableSettings([
			'form_width' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 3,
				'label' => _t('Number of columns in form'),
				'description' => _t('The number of columns wide the search will be.')
			)
		]);
	}
	# ------------------------------------------------------
	/**
	 * Override update() to set form_code if not specifically set by user and remove all placements if type changes
	 */
	public function update($pa_options=null) {
		$this->_setUniqueIdno(['noUpdate' => true]);
		if ($this->changed('table_num')) {
			$this->removeAllPlacements();
		}
		return parent::update($pa_options);
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
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_users'] = array('type' => 'special', 'repeating' => true, 'label' => _t('User access'));
		$this->BUNDLES['ca_user_groups'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Group access'));
		$this->BUNDLES['ca_search_form_placements'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Search form contents'));
		$this->BUNDLES['settings'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Search form settings'));
		
		$this->BUNDLES['ca_search_form_type_restrictions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Type restrictions'));
	}
	# ------------------------------------------------------
	# Form settings
	# ------------------------------------------------------
	/**
	 * Add bundle placement to currently loaded form
	 *
	 * @param string $ps_bundle_name Name of bundle to add (eg. ca_objects.idno, ca_objects.preferred_labels.name)
	 * @param array $pa_settings Placement settings array; keys should be valid setting names
	 * @param int $pn_rank Optional value that determines sort order of bundles in the form. If omitted, placement is added to the end of the form.
	 * @param array $pa_options Optional array of options. Supports the following options:
	 * 		user_id = if specified then add will fail if specified user does not have edit access for the form
	 * @return int Returns placement_id of newly created placement on success, false on error
	 */
	public function addPlacement($ps_bundle_name, $pa_settings, $pn_rank=null, $pa_options=null) {
		if (!($vn_form_id = $this->getPrimaryKey())) { return null; }
		unset(ca_search_forms::$s_placement_list_cache[$vn_form_id]);

		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;

		if ($pn_user_id && !$this->haveAccessToForm($pn_user_id, __CA_SEARCH_FORM_EDIT_ACCESS__)) {
			return null;
		}

		$t_placement = new ca_search_form_placements(null, null, is_array($pa_options['additional_settings']) ? $pa_options['additional_settings'] : null);
		$t_placement->set('form_id', $vn_form_id);
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
		return $t_placement->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Removes bundle placement from form
	 *
	 * @param int $pn_placement_id Placement_id of placement to remove
	 * @param array $pa_options Optional array of options. Supports the following options:
	 * 		user_id = if specified then remove will fail if specified user does not have edit access for the form
	 * @return bool Returns true on success, false on error
	 */
	public function removePlacement($pn_placement_id, $pa_options=null) {
		if (!($vn_form_id = $this->getPrimaryKey())) { return null; }
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;

		if ($pn_user_id && !$this->haveAccessToForm($pn_user_id, __CA_SEARCH_FORM_EDIT_ACCESS__)) {
			return null;
		}

		$t_placement = new ca_search_form_placements($pn_placement_id);
		if ($t_placement->getPrimaryKey() && ($t_placement->get('form_id') == $vn_form_id)) {
			$t_placement->delete(true);

			if ($t_placement->numErrors()) {
				$this->errors = array_merge($this->errors, $t_placement->errors);
				return false;
			}

			unset(ca_search_forms::$s_placement_list_cache[$vn_form_id]);
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Removes all bundle placements from form
	 *
	 * @param array $pa_options Optional array of options. Supports the following options:
	 * 		user_id = if specified then remove will fail if specified user does not have edit access for the form
	 * @return bool Returns true on success, false on error
	 */
	public function removeAllPlacements($pa_options=null) {
		if (!($vn_form_id = $this->getPrimaryKey())) { return null; }
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;

		if ($pn_user_id && !$this->haveAccessToForm($pn_user_id, __CA_SEARCH_FORM_EDIT_ACCESS__)) {
			return null;
		}

		$this->getDb()->query("
			DELETE FROM ca_search_form_placements WHERE form_id = ?
		", (int)$vn_form_id);


		if ($this->getDb()->numErrors()) {
			$this->errors = array_merge($this->errors, $this->getDb()->errors);
			return false;
		}

		unset(ca_search_forms::$s_placement_list_cache[$vn_form_id]);

		return true;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of placements for the currently loaded form.
	 *
	 * @param array $pa_options Optional array of options. Supports the following options:
	 * 		noCache = if set to true then the returned list if always generated directly from the database, otherwise it is returned from the cache if possible. Set this to true if you expect the cache may be stale. Default is false.
	 *		returnAllAvailableIfEmpty = if set to true then the list of all available bundles will be returned if the currently loaded form has no placements, or if there is no form loaded
	 *		table = if using the returnAllAvailableIfEmpty option and you expect a list of available bundles to be returned if no form is loaded, you must specify the table the bundles are intended for use with with this option. Either the table name or number may be used.
	 *		user_id = if specified then placements are only returned if the user has at least read access to the form
	 * @return array List of placements in display order. Array is keyed on bundle name. Values are arrays with the following keys:
	 *		placement_id = primary key of ca_search_form_placements row - a unique id for the placement
	 *		bundle_name = bundle name (a code - not for form)
	 *		settings = array of placement settings. Keys are setting names.
	 *		form = form string for bundle
	 */
	public function getPlacements($pa_options=null) {
		$pb_no_cache = (isset($pa_options['noCache'])) ? (bool)$pa_options['noCache'] : false;
		$pb_settings_only = (isset($pa_options['settingsOnly'])) ? (bool)$pa_options['settingsOnly'] : false;
		$pb_return_all_available_if_empty = (isset($pa_options['returnAllAvailableIfEmpty']) && !$pb_settings_only) ? (bool)$pa_options['returnAllAvailableIfEmpty'] : false;
		$ps_table = (isset($pa_options['table'])) ? $pa_options['table'] : null;
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;

		if ($pn_user_id && !$this->haveAccessToForm($pn_user_id, __CA_SEARCH_FORM_READ_ACCESS__)) {
			return [];
		}

		if (!($vn_form_id = $this->getPrimaryKey())) {
			if ($pb_return_all_available_if_empty && $ps_table) {
				return ca_search_forms::$s_placement_list_cache[$vn_form_id] = $this->getAvailableBundles($ps_table);
			}
			return [];
		}

		if (!$pb_no_cache && isset(ca_search_forms::$s_placement_list_cache[$vn_form_id]) && ca_search_forms::$s_placement_list_cache[$vn_form_id]) {
			return ca_search_forms::$s_placement_list_cache[$vn_form_id];
		}

		$o_db = $this->getDb();

		$qr_res = $o_db->query("
			SELECT placement_id, bundle_name, settings
			FROM ca_search_form_placements
			WHERE
				form_id = ?
			ORDER BY `rank`
		", (int)$vn_form_id);

		$va_available_bundles = ($pb_settings_only) ? [] : $this->getAvailableBundles();
		$va_placements = [];

		if ($qr_res->numRows() > 0) {
			$t_placement = new ca_search_form_placements();
			while($qr_res->nextRow()) {
				$vs_bundle_name = $qr_res->get('bundle_name');
				$va_placements[$vn_placement_id = (int)$qr_res->get('placement_id')] = $qr_res->getRow();
				$va_placements[$vn_placement_id]['settings'] = $va_settings = caUnserializeForDatabase($qr_res->get('settings'));
				if (!$pb_settings_only) {
					$t_placement->setSettingDefinitionsForPlacement($va_available_bundles[$vs_bundle_name]['settings'] ?? null);
					$va_placements[$vn_placement_id]['form'] = $va_available_bundles[$vs_bundle_name]['form'] ?? null;
					$va_placements[$vn_placement_id]['settingsForm'] = $t_placement->getHTMLSettingForm(array('id' => $vs_bundle_name.'_'.$vn_placement_id, 'settings' => $va_settings));
				}
			}
		} else {
			if ($pb_return_all_available_if_empty) {
				$va_placements = $this->getAvailableBundles($this->get('table_num'));
			}
		}
		ca_search_forms::$s_placement_list_cache[$vn_form_id] = $va_placements;
		return $va_placements;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of search forms subject to options
	 *
	 * @param array $pa_options Optional array of options. Supported options are:
	 *			table = if set, list is restricted to forms that pertain to the specified table. You can pass a table name or number. If omitted forms for all tables will be returned. [Default is null]
	 *			user_id = Restricts returned forms to those accessible by the current user. If omitted then all forms, regardless of access are returned. [Default is null]
	 *			restrictToTypes = Restricts returned forms to those bound to the specified type. [Default is null]
	 *			access = Restricts returned forms to those with at least the specified access level for the specified user. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then forms where the user has at least read access will be returned. [Default is null]
	 *			restrictToTypes = restrict forms to specific types; only relevant if table option is set. [Default is null]
	 * @return array Array of forms keyed on form_id and then locale_id. Keys for the per-locale value array include: form_id,  form_code, user_id, table_num,  label_id, name (display name of form), locale_id (locale of form name), search_form_content_type (display name of content this form searches on)
	 */
	public function getForms($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = []; }
		$pm_table_name_or_num = caGetOption('table', $pa_options, null);
		$pn_user_id = caGetOption('user_id', $pa_options, null);
		$pn_access = caGetOption('access', $pa_options, null);
		$pa_restrict_to_types = caGetOption('restrictToTypes', $pa_options, null, ['castTo' => 'array']);
		$pa_restrict_to_types = array_filter($pa_restrict_to_types, function($v) { return (bool)$v; });

		$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);
		if ($pm_table_name_or_num && !$vn_table_num) { return []; }

		$o_db = $this->getDb();

		$va_wheres = array('((sfl.is_preferred = 1) or (sfl.is_preferred is null))');
		if ($vn_table_num > 0) {
			$va_wheres[] = "(sf.table_num = ".intval($vn_table_num).")";
		}

		$va_params = [];
		$va_access_wheres = [];
		if ($pn_user_id) {
			$t_user = Datamodel::getInstanceByTableName('ca_users', true);
			$t_user->load($pn_user_id);

			if ($t_user->getPrimaryKey()) {
				$vs_access_sql = ($pn_access > 0) ? " AND (access >= ".intval($pn_access).")" : "";
				if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
					$vs_sql = "(
						(sf.user_id = ".intval($pn_user_id).") OR 
						(sf.form_id IN (
								SELECT form_id 
								FROM ca_search_forms_x_user_groups 
								WHERE 
									group_id IN (".join(',', array_keys($va_groups)).") {$vs_access_sql}
							)
						)
					)";
				} else {
					$vs_sql = "(sf.user_id = {$pn_user_id})";
				}

				$vs_sql .= " OR (sf.form_id IN (
										SELECT form_id 
										FROM ca_search_forms_x_users 
										WHERE 
											user_id = {$pn_user_id} {$vs_access_sql}
									)
								)";


				$va_access_wheres[] = "({$vs_sql})";
			}
		}

		if ($pm_table_name_or_num && is_array($pa_restrict_to_types) && sizeof($pa_restrict_to_types) && is_array($va_ancestors = caGetAncestorsForItemID($pa_restrict_to_types, ['includeSelf' => true])) && sizeof($va_ancestors)) {
			$va_wheres[] = "(sftr.type_id IS NULL OR sftr.type_id IN (?) OR (sftr.include_subtypes = 1 AND sftr.type_id IN (?)))";
			$va_params[] = $pa_restrict_to_types;
			$va_params[] = $va_ancestors;
		}
	

		if ($pn_access == __CA_SEARCH_FORM_READ_ACCESS__) {
			$va_access_wheres[] = "(sf.is_system = 1)";
		}

		if (sizeof($va_access_wheres)) {
			$va_wheres[] = "(".join(" OR ", $va_access_wheres).")";
		}

		// get forms
		$qr_res = $o_db->query($vs_sql = "
			SELECT
				sf.form_id, sf.form_code, sf.user_id, sf.table_num, 
				sfl.label_id, sfl.name, sfl.locale_id, u.fname, u.lname, u.email,
				l.language, l.country
			FROM ca_search_forms sf
			LEFT JOIN ca_search_form_labels AS sfl ON sf.form_id = sfl.form_id
			LEFT JOIN ca_locales AS l ON sfl.locale_id = l.locale_id
			LEFT JOIN ca_search_form_type_restrictions AS sftr ON sf.form_id = sftr.form_id
			INNER JOIN ca_users AS u ON sf.user_id = u.user_id
			".(sizeof($va_wheres) ? 'WHERE ' : '')."
			".join(' AND ', $va_wheres)."
		", $va_params);
		$va_forms = [];

		$va_form_type = ca_search_forms::getFormTypeNames($qr_res->getAllFieldValues('form_id'));
		$qr_res->seek(0);
		
		$t_list = new ca_lists();
		$va_type_name_cache = [];
		while($qr_res->nextRow()) {
			$vn_table_num = $qr_res->get('table_num');
			$vs_display_type = $va_form_type[$qr_res->get('form_id')];
		
			$va_forms[$qr_res->get('form_id')][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array('search_form_content_type' => $vs_display_type));
		}
		return $va_forms;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of forms conforming to specification in options
	 *
	 * @param array $pa_options Optional array of options. Supported options are:
	 *			table - if set, list is restricted to forms that pertain to the specified table. You can pass a table name or number. If omitted forms for all tables will be returned.
	 *			user_id - Restricts returned forms to those accessible by the current user. If omitted then all forms, regardless of access are returned.
	 *			access - Restricts returned forms to those with at least the specified access level for the specified user. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then forms where the user has at least read access will be returned.
	 * @return int  Number of forms available
	 */
	public function getFormCount($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = []; }

		$va_forms = $this->getForms($pa_options);

		if (is_array($va_forms)) { return sizeof($va_forms); } else { return 0; }
	}
	# ------------------------------------------------------
	/**
	 * Return available search forms as HTML <select> drop-down menu
	 *
	 * @param string $ps_select_name Name attribute for <select> form element
	 * @param array $pa_attributes Optional array of attributes to embed in HTML <select> tag. Keys are attribute names and values are attribute values.
	 * @param array $pa_options Optional array of options. Supported options include:
	 * 		Supports all options supported by caHTMLSelect() and ca_search_forms::getForms() + the following:
	 *			addDefaultForm = if true, the "default" form is included at the head of the list; this is simply a form called "default" that is assumed to be handled by your code; the default is not to add the default value (false)
	 *			addDefaultFormIfEmpty = same as 'addDefaultForm' except that the default value is only added if the form list is empty
	 *			restrictToTypes = 
	 * @return string HTML code defining <select> drop-down
	 */
	public function getFormsAsHTMLSelect($ps_select_name, $pa_attributes=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = []; }

		$va_available_forms = caExtractValuesByUserLocale($this->getForms($pa_options));

		$va_content = [];

		if (
			(isset($pa_options['addDefaultForm']) && $pa_options['addDefaultForm'])
			||
			(isset($pa_options['addDefaultFormIfEmpty']) &&  ($pa_options['addDefaultFormIfEmpty']) && (!sizeof($va_available_forms)))
		) {
			$va_content[_t('Default')] = 0;
		}

		foreach($va_available_forms as $vn_form_id => $va_info) {
			$va_content[$va_info['name']] = $vn_form_id;
		}

		if (sizeof($va_content) == 0) { return ''; }
		return caHTMLSelect($ps_select_name, $va_content, $pa_attributes, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Returns names of types of content (synonymous with the table name for the content) for a list of form_ids. Will return name in singular number unless the 'number' option is set to 'plural'
	 *
	 * @param array $pa_form_ids
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		useSingular = Return singular forms of type names. [Default is false]
	 * @return string The name of the type of content or null if $pn_table_num is not set to a valid table and no form is loaded.
	 */
	static public function getFormTypeNames($pa_form_ids, $pa_options=null) {
		if(!is_array($pa_form_ids) && $pa_form_ids) { $pa_form_ids = [$pa_form_ids]; }
		if (!$pa_form_ids) { return null; }
		if (!sizeof($pa_form_ids = array_filter($pa_form_ids, "intval"))) { return null; }
		$o_db = ($o_trans = caGetOption('transaction', $pa_options, null)) ? $o_trans->getDb() : new Db();
		
		$t_form = new ca_search_forms();
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_search_forms
			WHERE
				form_id IN (?)
		", [$pa_form_ids]);
		
		$vb_use_singular = caGetOption('useSingular', $pa_options, false);
		
		$va_names = [];
		while($qr_res->nextRow()) {
			$t_instance = Datamodel::getInstanceByTableNum($qr_res->get('table_num'), true);
			$va_restriction_names = array_map(function($v) use ($vb_use_singular) { return caUcFirstUTF8Safe(caGetListItemByIDForDisplay($v['type_id'], ['return' => $vb_use_singular ? 'singular' : 'plural'])); }, $t_form->getTypeRestrictions(null, ['form_id' => $qr_res->get('form_id')]));
			
			switch($t_instance->tableName()) {
				case 'ca_occurrences':
					$va_names[$qr_res->get('form_id')] = join(", ", $va_restriction_names);
					break;
				default:
					$va_names[$qr_res->get('form_id')] = caUcFirstUTF8Safe($t_instance->getProperty(($vb_use_singular ? 'NAME_SINGULAR' : 'NAME_PLURAL'))).((sizeof($va_restriction_names) > 0) ? " (".join(", ", $va_restriction_names).")" : '');
					break;
			}		
		}
		
		return $va_names;
	}
	# ------------------------------------------------------
	/**
	 * Determines if user has access to a form at a specified access level.
	 *
	 * @param int $pn_user_id user_id of user to check form access for
	 * @param int $pn_access type of access required. Use __CA_SEARCH_FORM_READ_ACCESS__ for read-only access or __CA_SEARCH_FORM_EDIT_ACCESS__ for editing (full) access
	 * @param int $pn_form_id The id of the form to check. If omitted then currently loaded form will be checked.
	 * @return bool True if user has access, false if not
	 */
	public function haveAccessToForm($pn_user_id, $pn_access, $pn_form_id=null) {
		$vn_form_id = null;
		if ($pn_form_id) {
			$vn_form_id = $pn_form_id;
			$t_form = new ca_search_forms($vn_form_id);
			$vn_form_user_id = $t_form->get('user_id');
		} else {
			$vn_form_user_id = $this->get('user_id');
			$t_form = $this;
		}
		if(!$vn_form_id && !($vn_form_id = $t_form->getPrimaryKey())) {
			return true; // new form
		}
		if (isset(ca_search_forms::$s_have_access_to_form_cache[$vn_form_id.'/'.$pn_user_id.'/'.$pn_access])) {
			return ca_search_forms::$s_have_access_to_form_cache[$vn_form_id.'/'.$pn_user_id.'/'.$pn_access];
		}

		if (($vn_form_user_id == $pn_user_id)) {	// owners have all access
			return ca_search_forms::$s_have_access_to_form_cache[$vn_form_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}

		if ((bool)$t_form->get('is_system') && ($pn_access == __CA_SEARCH_FORM_READ_ACCESS__)) {	// system forms are readable by all
			return ca_search_forms::$s_have_access_to_form_cache[$vn_form_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}

		$o_db =  $this->getDb();
		$qr_res = $o_db->query("
			SELECT fxg.form_id 
			FROM ca_search_forms_x_user_groups fxg 
			INNER JOIN ca_user_groups AS ug ON fxg.group_id = ug.group_id
			INNER JOIN ca_users_x_groups AS uxg ON uxg.group_id = ug.group_id
			WHERE 
				(fxg.access >= ?) AND (uxg.user_id = ?) AND (fxg.form_id = ?)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_form_id);

		if ($qr_res->numRows() > 0) { return ca_search_forms::$s_have_access_to_form_cache[$vn_form_id.'/'.$pn_user_id.'/'.$pn_access] = true; }

		$qr_res = $o_db->query("
			SELECT fxu.form_id 
			FROM ca_search_forms_x_users fxu 
			INNER JOIN ca_users AS u ON fxu.user_id = u.user_id
			WHERE 
				(fxu.access >= ?) AND (u.user_id = ?) AND (fxu.form_id = ?)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_form_id);

		if ($qr_res->numRows() > 0) { return ca_search_forms::$s_have_access_to_form_cache[$vn_form_id.'/'.$pn_user_id.'/'.$pn_access] = true; }


		return ca_search_forms::$s_have_access_to_form_cache[$vn_form_id.'/'.$pn_user_id.'/'.$pn_access] = false;
	}
	# ------------------------------------------------------
	# Support methods for search form setup UI
	# ------------------------------------------------------
	/**
	 * Returns all available search form placements - those data bundles that can be searches for the given content type, in other words.
	 * The returned value is a list of arrays; each array contains a 'bundle' specifier than can be passed got Model::get() or SearchResult::get() and a display name
	 *
	 * @param mixed $pm_table_name_or_num The table name or number specifying the content type to fetch bundles for. If omitted the content table of the currently loaded search form will be used.
	 * @param array $pa_options Options include:
	 *		omitGeneric = Omit "generic" bundle from returned list. [Default is false]
	 *
	 * @return array An array of bundles keyed on display label. Each value is an array with these keys:
	 *		bundle = The bundle name (eg. ca_objects.idno)
	 *		display = Display label for each available bundle
	 *		description = Description of bundle
	 *		useDisambiguationLabels = Use alternate labels for metadata elements suitable for list display. [Default is false]
	 *
	 * Will return null if table name or number is invalid.
	 */
	public function getAvailableBundles($pm_table_name_or_num=null, $pa_options=null) {
		if (!$pm_table_name_or_num) { $pm_table_name_or_num = $this->get('table_num'); }
		$pm_table_name_or_num = Datamodel::getTableNum($pm_table_name_or_num);
		if (!$pm_table_name_or_num) { return null; }
		
		$use_disambiguation_labels = caGetOption('useDisambiguationLabels', $pa_options, false);
		
		if(is_array($restrict_to_types = caGetOption('restrictToTypes', $pa_options, null)) && sizeof($restrict_to_types)) {
			$restrict_to_types = caMakeTypeIDList($pm_table_name_or_num, $restrict_to_types);
		}

		$t_instance = Datamodel::getInstanceByTableNum($pm_table_name_or_num, true);
		$va_search_settings = $this->opo_search_indexing_config->getAssoc(Datamodel::getTableName($pm_table_name_or_num));

		$vs_primary_table = $t_instance->tableName();
		$vs_table_display_name = $t_instance->getProperty('NAME_PLURAL');

		$t_placement = new ca_search_form_placements(null, null, []);

		$va_available_bundles = [];

		$va_additional_settings = array(
			'width' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 4, 'height' => 1,
				'takesLocale' => false,
				'default' => "100px",
				'label' => _t('Width'),
				'description' => _t('Width, in characters, of search form elements.')
			)
		);
		$t_placement->setSettingDefinitionsForPlacement($va_additional_settings);

		// Full-text 
		$vs_bundle = "_fulltext";
		$vs_display = "<div id='searchFormEditor__fulltext'><span class='bundleDisplayEditorPlacementListItemTitle'>"._t("General").'</span> '.($vs_label = _t('Full text'))."</div>";
		$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
			'bundle' => $vs_bundle,
			'label' => caUcFirstUTF8Safe($vs_label),
			'display' => $vs_display,
			'description' => $vs_description = _t('Searches on all content that has been indexed'),
			'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
			'settings' => $va_additional_settings
		);

		TooltipManager::add(
			"#searchFormEditor__fulltext",
			"<h2>{$vs_label}</h2>{$vs_description}"
		);
		
		if(!caGetOption('omitGeneric', $pa_options, false)) {
			// GENERIC 
			$vs_bundle = "_generic";
			$vs_display = "<div id='searchFormEditor__fulltext'><span class='bundleDisplayEditorPlacementListItemTitle'>"._t("General").'</span> '.($vs_label = _t('Generic'))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'label' => $vs_label,
				'display' => $vs_display,
				'description' => $vs_description = _t('Searches on any bundle as specified'),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => array_merge($va_additional_settings, [
					'bundle' => [
						'formatType' => FT_TEXT,
						'displayType' => DT_FIELD,
						'width' => 70, 'height' => 2,
						'takesLocale' => false,
						'default' => "",
						'label' => _t('Bundle'),
						'description' => _t('Bundle specifier')
					]
				])
			);

			TooltipManager::add(
				"#searchFormEditor__generic",
				"<h2>{$vs_label}</h2>{$vs_description}"
			);
		}


		// get fields 
		if (is_array($va_search_settings)) { 
			foreach($va_search_settings as $vs_table => $va_fields) {
			
				if (preg_match("!\.related$!", $vs_table)) { continue; }
				if (!is_array($va_fields['fields'] ?? null)) { continue; }

				if ($vs_table == $vs_primary_table) {
					$va_element_codes = (method_exists($t_instance, 'getApplicableElementCodes') ? $t_instance->getApplicableElementCodes(null, false, false) : []);

					$va_field_list = [];
					foreach($va_fields['fields'] as $vs_field => $va_field_indexing_info) {
                        $vs_field = str_replace("_ca_attribute_", "", $vs_field);
						if ($vs_field === '_metadata') {
							foreach($va_element_codes as $vs_code) {
								$va_field_list[$vs_code] = $va_field_indexing_info;
							}
						} else {
							$va_field_list[$vs_field] = $va_field_indexing_info;
						}
					}
					
					if(is_array($va_fields['current_values'] ?? null)) {
                        foreach($va_fields['current_values'] as $p => $pinfo) {
                            foreach($pinfo as $f => $finfo) {
                                $f = str_replace("_ca_attribute_", "", $f);
                                if ($vs_field === '_metadata') {
                                    foreach($va_element_codes as $vs_code) {
                                        $va_field_list["CV{$p}_{$vs_code}"] = $finfo;
                                    }
                                } else {
                                    $va_field_list["CV|{$p}|{$vs_field}"] = $finfo;
                                }
                            }
                        }
                    }
 
// Don't include fields in self-related records (Eg. objects related to objects) as it appears to confuse more than help                  
                    if(is_array($va_fields['related'] ?? null) && is_array($va_fields['related']['fields'] ?? null)) {
                        foreach($va_fields['related']['fields'] as $f => $finfo) {
							$va_field_list["{$vs_table}.related.{$f}"] = $finfo;
                        }
                    }

					foreach($va_field_list as $vs_field => $va_field_indexing_info) {
						if(in_array('DONT_INCLUDE_IN_SEARCH_FORM', $va_field_indexing_info)) { continue; }
						if(Datamodel::getFieldInfo($vs_table, $vs_field, 'DONT_INCLUDE_IN_SEARCH_FORM')) { continue; }
						
						$label = caGetOption('LABEL', $va_field_indexing_info, null);
						$bundle_bits = explode('.', $vs_field);
						
                        $policy = $policy_label = null;
                        $tmp = explode('|', $vs_field);
                        if ($tmp[0] == 'CV') {
                            $policy = $tmp[1];
                            $vs_field = $tmp[2];
                            
                            $policy_label = $policy ? _t('Current value for %1', mb_strtolower($policy)) : null;
                        }

						if (($va_field_info = $t_instance->getFieldInfo($vs_field))) {
							if ($va_field_info['DONT_USE_AS_BUNDLE'] ?? null) { continue; }
							if (in_array($va_field_info['FIELD_TYPE'] ?? null, array(FT_MEDIA, FT_FILE))) { continue; }

							$vs_bundle = $vs_table.'.'.$vs_field;
							if (caGetBundleAccessLevel($vs_primary_table, $vs_bundle) == __CA_BUNDLE_ACCESS_NONE__) { continue;}

							$vs_label = $label ?? $t_instance->getDisplayLabel($vs_bundle);
							
							$vs_display = "<div id='searchFormEditor_{$vs_table}_{$vs_field}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> ".$policy_label.$vs_label."</div>";
							$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
								'bundle' => $vs_bundle,
								'label' => caUcFirstUTF8Safe($vs_label),
								'display' => $vs_display,
								'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
								'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
								'settings' => $va_additional_settings
							);

							TooltipManager::add(
								"#searchFormEditor_{$vs_table}_{$vs_field}",
								"<h2>{$vs_label}</h2>{$vs_description}"
							);
						} elseif (in_array($vs_field, $va_element_codes)) {
							// is it an attribute?
							$t_element = ca_metadata_elements::getInstance($vs_field);
							if(!$t_element) { continue; }
							if (in_array($t_element->get('datatype'), array(15, 16))) { continue; } 		// skip file and media attributes - never searchable
							if (!$t_element->getSetting('canBeUsedInSearchForm')) { continue; }

							if (caGetBundleAccessLevel($vs_primary_table, $vs_field) == __CA_BUNDLE_ACCESS_NONE__) { continue;}

							if(is_array($restrict_to_types) && sizeof($restrict_to_types) && !ca_metadata_elements::elementIsApplicableToType($vs_table, $restrict_to_types, $vs_field)) { continue; }

							$vs_bundle = $vs_table.'.'.$vs_field;
							$vs_label = $label ?? $t_instance->getDisplayLabel($vs_bundle, ['useDisambiguationLabels' => $use_disambiguation_labels]);

							$vs_display = "<div id='searchFormEditor_{$vs_table}_{$vs_field}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> ".$policy_label.$vs_label."</div>";
							$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
								'bundle' => $vs_bundle,
								'label' => caUcFirstUTF8Safe($vs_label),
								'display' => $vs_display,
								'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
								'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
								'settings' => $va_additional_settings
							);

							TooltipManager::add(
								"#searchFormEditor_{$vs_table}_{$vs_field}",
								"<h2>{$vs_label}</h2>{$vs_description}"
							);
						} elseif((sizeof($bundle_bits) > 2) && ($bundle_bits[1] === 'related')) {
							// self-related?
							if (caGetBundleAccessLevel($vs_primary_table, $bundle_bits[2]) == __CA_BUNDLE_ACCESS_NONE__) { continue;}

							$vs_bundle = $vs_field;
							
							$vs_label = $label ?? $t_instance->getDisplayLabel("{$vs_primary_table}.{$bundle_bits[2]}");

							$vs_display = "<div id='searchFormEditor_{$vs_table}_{$bundle_bits[2]}'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_instance->getProperty('NAME_SINGULAR'))."</span> "._t('Related %1', $vs_label)."</div>";
							$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
								'bundle' => $vs_field,
								'label' => caUcFirstUTF8Safe($vs_label),
								'display' => $vs_display,
								'description' => $vs_description = $t_instance->getDisplayDescription("{$vs_primary_table}.{$bundle_bits[2]}"),
								'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
								'settings' => $va_additional_settings
							);

							TooltipManager::add(
								"#searchFormEditor_{$vs_field}",
								"<h2>{$vs_label}</h2>{$vs_description}"
							);
						}
					}
				} else {
					// related table
					if ($this->getAppConfig()->get($vs_table.'_disable')) { continue; }
					$t_table = Datamodel::getInstanceByTableName($vs_table, true);
					if ((method_exists($t_table, "getSubjectTableName") && $vs_subject_table = $t_table->getSubjectTableName())) {
						if ($this->getAppConfig()->get($vs_subject_table.'_disable')) { continue; }
					}
					if (caGetBundleAccessLevel($vs_primary_table, $vs_subject_table) == __CA_BUNDLE_ACCESS_NONE__) { continue;}
				
					$va_element_codes = (method_exists($t_table, 'getApplicableElementCodes') ? $t_table->getApplicableElementCodes(null, false, false) : []);
				
					$va_field_list = [];
					foreach($va_fields['fields'] as $vs_field => $va_field_indexing_info) {
                        $vs_field = str_replace("_ca_attribute_", "", $vs_field);
						if ($vs_field === '_metadata') {
							foreach($va_element_codes as $vs_code) {
								$va_field_list[$vs_code] = $va_field_indexing_info;
							}
						} else {
							$va_field_list[$vs_field] = $va_field_indexing_info;
						}
					}
					
					if(is_array($va_fields['current_values'] ?? null)) {
                        foreach($va_fields['current_values'] as $p => $pinfo) {
                            foreach($pinfo as $f => $finfo) {
                                $f = str_replace("_ca_attribute_", "", $f);
                                if ($vs_field === '_metadata') {
                                    foreach($va_element_codes as $vs_code) {
                                        $va_field_list["CV{$p}_{$vs_code}"] = $finfo;
                                    }
                                } else {
                                    $va_field_list["CV|{$p}|{$f}"] = $finfo;
                                }
                            }
                        }
                    }
                    
                    if(is_array($va_fields['related'] ?? null) && is_array($va_fields['related']['fields'] ?? null)) {
                        foreach($va_fields['related']['fields'] as $f => $finfo) {
							$va_field_list["{$vs_table}.related.{$f}"] = $finfo;
                        }
                    }

					foreach($va_field_list as $vs_field => $va_field_indexing_info) {
						if (in_array('DONT_INCLUDE_IN_SEARCH_FORM', $va_field_indexing_info)) { continue; }
						if(Datamodel::getFieldInfo($vs_table, $vs_field, 'DONT_INCLUDE_IN_SEARCH_FORM')) { continue; }
												
						$label = caGetOption('LABEL', $va_field_indexing_info, null);
						$bundle_bits = explode('.', $vs_field);
						
						$policy = $policy_label = null;
                        $tmp = explode('|', $vs_field);
                        if ($tmp[0] == 'CV') {  // current value bundle
                            $policy = $tmp[1];
                            $vs_field = $tmp[2];
                        }
                        
						if (($va_field_info = $t_table->getFieldInfo($vs_field)) || (method_exists($t_table, "hasElement") && $t_table->hasElement($vs_field)) || (($bundle_bits[1] ?? null) === 'related')) {
							if (isset($va_field_info['DONT_USE_AS_BUNDLE']) && $va_field_info['DONT_USE_AS_BUNDLE']) { continue; }


							$subject_table = null;                           
							$vs_related_table = caUcFirstUTF8Safe($t_table->getProperty('NAME_SINGULAR'));
							if (method_exists($t_table, 'getSubjectTableInstance')) {
								$t_subject = $t_table->getSubjectTableInstance();
								$vs_related_table = caUcFirstUTF8Safe($t_subject->getProperty('NAME_SINGULAR'));
								$subject_table = $t_subject->tableName();
							}
							
							$vs_base_bundle = (($bundle_bits[1] ?? null) === 'related') ? "{$subject_table}.preferred_labels.{$bundle_bits[2]}"  : "{$vs_table}.{$vs_field}";
							$vs_bundle = $policy ? "{$vs_table}.current_value.{$p}.{$vs_field}" : ((($bundle_bits[1] ?? null) === 'related') ? "{$subject_table}.preferred_labels.{$bundle_bits[2]}" : "{$vs_table}.{$vs_field}");


							$vs_label = $label ?? $t_instance->getDisplayLabel($vs_base_bundle, ['useDisambiguationLabels' => $use_disambiguation_labels, 'includeSourceSuffix' => false]);
							
							if ($policy) { 
							    $vs_label = _t('<em>%1</em> using <em>%2</em>', caUcFirstUTF8Safe(mb_strtolower(ca_objects::getHistoryTrackingCurrentValuePolicy($policy, 'name'))), mb_strtolower($vs_label));
							}
							
							if  (method_exists($t_table, "getSubjectTableName") && ($vs_primary_table == $vs_subject_table)) {
								$vs_display = "<div id='searchFormEditor_".str_replace('.', '_', $vs_base_bundle)."'><span class='bundleDisplayEditorPlacementListItemTitle'>".caUcFirstUTF8Safe($t_subject->getProperty('NAME_SINGULAR'))."</span> {$vs_label}</div>";
							} else {
								$vs_display = "<div id='searchFormEditor_".str_replace('.', '_', $vs_base_bundle)."'><span class='bundleDisplayEditorPlacementListItemTitle'>{$vs_related_table}</span> {$vs_label}</div>";
							}

							$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
								'bundle' => $vs_bundle,
								'label' => caUcFirstUTF8Safe($vs_label),
								'display' => $vs_display,
								'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
								'settingsForm' => $t_placement->getHTMLSettingForm(['id' => $vs_bundle.'_0']),
								'settings' => $va_additional_settings
							);

							TooltipManager::add(
								"#searchFormEditor_{$vs_table}_{$vs_field}",
								"<h2>{$vs_label}</h2>{$vs_description}"
							);
						}
					}

				}
			}
		}

		//
		// access points
		//
		$va_access_points = (is_array($va_search_settings['_access_points'] ?? null)) ? $va_search_settings['_access_points'] : [];
		//unset($va_search_settings['_access_points']);

		foreach($va_access_points as $vs_access_point => $va_access_point_info) {
			if (is_array($va_access_point_info['options'] ?? null)) {
				if (in_array('DONT_INCLUDE_IN_SEARCH_FORM', $va_access_point_info['options'])) { continue; }
			}
			$vs_display = "<div id='searchFormEditor_{$vs_access_point}'><span class='bundleDisplayEditorPlacementListItemTitle'>"._t('Access point').'</span> '.($vs_label = ((isset($va_access_point_info['name']) && $va_access_point_info['name'])  ? $va_access_point_info['name'] : $vs_access_point))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_access_point] = array(
				'bundle' => $vs_access_point,
				'label' => caUcFirstUTF8Safe($vs_label),
				'display' => $vs_display,
				'description' =>  $vs_description = ((isset($va_access_point_info['description']) && $va_access_point_info['description'])  ? $va_access_point_info['description'] : ''),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_access_point.'_0')),
				'settings' => $va_additional_settings
			);

			TooltipManager::add(
				"#searchFormEditor_{$vs_access_point}",
				"<h2>{$vs_label}</h2>{$vs_description}"
			);
		}

		//
		// created and modified
		//
		$t_placement->setSettingDefinitionsForPlacement($va_additional_settings);
		foreach(array('created', 'modified') as $vs_bundle) {
			$vs_display = "<div id='searchFormEditor_{$vs_bundle}'><span class='bundleDisplayEditorPlacementListItemTitle'>"._t('General')."</span> ".($vs_label = $t_instance->getDisplayLabel($vs_bundle))."</div>";
			$va_available_bundles[strip_tags($vs_display)][$vs_bundle] = array(
				'bundle' => $vs_bundle,
				'label' => $vs_label,
				'display' => $vs_display,
				'description' => $vs_description = $t_instance->getDisplayDescription($vs_bundle),
				'settingsForm' => $t_placement->getHTMLSettingForm(array('id' => $vs_bundle.'_0')),
				'settings' => $va_additional_settings
			);

			TooltipManager::add(
				"#searchFormEditor_{$vs_bundle}",
				"<h2>{$vs_label}</h2>{$vs_description}"
			);
		}

		ksort($va_available_bundles);

		$va_sorted_bundles = [];
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
	 *		user_id = if specified then placements are only returned if the user has at least read access to the display
	 * @return array List of placements. Each element in the list is an array with the following keys:
	 *		display = A display label for the bundle
	 *		bundle = The bundle name
	 */
	public function getPlacementsInForm($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = []; }
		$pb_no_cache = isset($pa_options['noCache']) ? (bool)$pa_options['noCache'] : false;
		$pn_user_id = isset($pa_options['user_id']) ? $pa_options['user_id'] : null;

		if ($pn_user_id && !$this->haveAccessToForm($pn_user_id, __CA_SEARCH_FORM_READ_ACCESS__)) {
			return [];
		}

		if (!($pn_table_num = Datamodel::getTableNum($this->get('table_num')))) { return null; }

		$t_instance = Datamodel::getInstanceByTableNum($pn_table_num, true);

		if(!is_array($va_placements = $this->getPlacements($pa_options))) { $va_placements = []; }

		$va_available_bundles = $this->getAvailableBundles($pn_table_num);

		$va_placements_in_form = [];
		foreach($va_placements as $vn_placement_id => $va_placement) {
			$vs_label =  $va_available_bundles[$va_placement['bundle_name']]['label'];
			$vs_display = $va_available_bundles[$va_placement['bundle_name']]['display'];

			if(is_array($va_placement['settings']['label'] ?? null)){
				$va_tmp = caExtractValuesByUserLocale(array($va_placement['settings']['label']));
				if ($vs_user_set_label = array_shift($va_tmp)) {
					$vs_label = "{$vs_label} (<em>{$vs_user_set_label}</em>)";
					$vs_display = "{$vs_display} (<em>{$vs_user_set_label}</em>)";
				}
			}

			$va_placement['display'] = $vs_display =  preg_replace("!<div id='searchFormEditor_[^']+!", "<div id='searchFormEditorSelected_{$vn_placement_id}", $vs_display);

			$va_placement['bundle'] = $va_placement['bundle_name']; // we used 'bundle' in the arrays, but the database field is called 'bundle_name' and getPlacements() returns data directly from the database
			unset($va_placement['bundle_name']);

			$va_placements_in_form[$vn_placement_id] = $va_placement;

			$vs_description = $t_instance->getDisplayDescription($va_placement['bundle']);
			TooltipManager::add(
				"#searchFormEditorSelected_{$vn_placement_id}",
				"<h2>{$vs_label}</h2>{$vs_description}"
			);
		}

		return $va_placements_in_form;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of placements in the currently loaded search form
	 *
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		noCache = if set to true, no caching of placement values is performed.
	 *		user_id = if specified then placements are only returned if the user has at least read access to the form
	 * @return int Number of placements.
	 */
	public function getPlacementCount($pa_options=null) {
	    $placements = $this->getPlacementsInForm($pa_options);
		return is_array($placements) ? sizeof($placements) : 0;
	}
	# ------------------------------------------------------
	#

	# ------------------------------------------------------
	#
	# Search form processing
	#
	# ------------------------------------------------------
	# Form renderer
	# ------------------------------------------------------
	/**
	 * Render currently loaded form as HTML
	 */
	public function getHTMLFormElements($po_request, $pa_form_data=null) {
		if (!$this->getPrimaryKey()) { return null; }
		if (!is_array($pa_form_data)) { $pa_form_data = []; }

		foreach($pa_form_data as $vs_k => $vs_v) {
			$pa_form_data[$vs_k] = trim((string)$pa_form_data[$vs_k]);
		}

		$va_form_contents = $this->getPlacements();

		$va_output = [];

		if (!($vs_form_table_name = Datamodel::getTableName($this->get('table_num')))) { return null; }
		$t_subject = Datamodel::getInstanceByTableName($vs_form_table_name, true);

		foreach($va_form_contents as $vn_i => $va_element) {

			$vs_field_label = '';
			if (is_array($va_element['settings']['label'] ?? null) && (sizeof($va_element['settings']['label']) > 0)) {
				if ((is_array($va_field_labels = caExtractValuesByUserLocale(array($va_element['settings']['label']))) && sizeof($va_field_labels) > 0)) {
					$vs_field_label = array_shift($va_field_labels);
				}
			}

			switch($va_element['bundle_name'] ?? null) {
				case '_fulltext':
					if (!$vs_field_label) { $vs_field_label = _t('Full text'); }
					$va_output[] = array(
						'element' => caHTMLTextInput("_fulltext", array(
							'value' => $pa_form_data["_fulltext"],
							'id' => "_fulltext"
						),
							array(
								'width' => (isset($va_element['settings']['width']) && ($va_element['settings']['width'] > 0)) ? $va_element['settings']['width'] : "100px",
								'height' => (isset($va_element['settings']['height']) && ($va_element['settings']['height'] > 0)) ? $va_element['settings']['height'] : 1
							)),
						'label' => $vs_field_label,
						'name' => $va_element['bundle_name']
					);
					continue(2);
				case '_generic':
					$bundle = $va_element['settings']['bundle'];
					$bundle_proc = str_replace('.', '_', $bundle);
					$va_output[] = array(
						'element' => caHTMLTextInput($bundle_proc, array(
							'value' => $pa_form_data[$bundle],
							'id' => $bundle_proc
						),
							array(
								'width' => (isset($va_element['settings']['width']) && ($va_element['settings']['width'] > 0)) ? $va_element['settings']['width'] : "100px",
								'height' => (isset($va_element['settings']['height']) && ($va_element['settings']['height'] > 0)) ? $va_element['settings']['height'] : 1
							)),
						'label' => $vs_field_label,
						'name' => $bundle
					);
					continue(2);
				case 'created':
				case 'modified':
					$va_output[] = array(
						'element' => $t_subject->htmlFormElementForSearch($po_request, $va_element['bundle_name'], array(
							'values' => $pa_form_data,
							'width' => (isset($va_element['settings']['width']) && ($va_element['settings']['width'] > 0)) ? $va_element['settings']['width'] : "100px",
							'height' => (isset($va_element['settings']['height']) && ($va_element['settings']['height'] > 0)) ? $va_element['settings']['height'] : 1,

							'format' => '^ELEMENT',
							'multivalueFormat' => '<i>^LABEL</i><br/>^ELEMENT',
							'id' => str_replace('.', '_', $va_element['bundle_name'])
						)),
						'label' => ($vs_field_label) ? $vs_field_label : $t_subject->getDisplayLabel($va_element['bundle_name']),
						'name' => $va_element['bundle_name']
					);
					continue(2);
			}

			$va_tmp = explode('.', $vs_field = $va_element['bundle_name']);
			if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) {
				// is this an access point?
				$va_search_settings = $this->opo_search_indexing_config->getAssoc(Datamodel::getTableName($vs_form_table_name));
				$va_access_points = (isset($va_search_settings['_access_points']) && is_array($va_search_settings['_access_points'])) ? $va_search_settings['_access_points'] : [];

				if (isset($va_access_points[$va_tmp[0]])) {

					if (is_array($va_element['settings']['label'])) {
						$va_labels = caExtractValuesByUserLocale(array(0 => $va_element['settings']['label']));
						$vs_label = array_shift($va_labels);
					}
					if (!$vs_label && !($vs_label = $va_access_points[$va_tmp[0]]['name'])) {
						$vs_label = $vs_field;
					}

					$va_output[] = array(
						'element' => caHTMLTextInput($vs_field, array(
							'value' => $pa_form_data[$vs_field],
							'id' => str_replace('.', '_', $vs_field)
						),
							array(
								'width' => (isset($va_element['settings']['width']) && ($va_element['settings']['width'] > 0)) ? $va_element['settings']['width'] : "100px",
								'height' => (isset($va_element['settings']['height']) && ($va_element['settings']['height'] > 0)) ? $va_element['settings']['height'] : 1
							)),
						'label' => $vs_label,
						'name' => $vs_field
					);
				}
				continue;
			}
		    $tmp = explode(".", $va_element['bundle_name']);
		    $policy = null;
		    $bundle = $vs_field;
            if ($tmp[1] == 'current_value') {    // getHistoryTrackingCurrentValuePolicy
                $policy = $tmp[2];
                $bundle = $va_element['bundle_name'];
                $vs_field = $tmp[0].".".$tmp[3];
            }

			$va_output[] = array(
				'element' => $t_instance->htmlFormElementForSearch($po_request, $vs_field, array(
					'values' => $pa_form_data,
					'width' => (isset($va_element['settings']['width']) && ($va_element['settings']['width'] > 0)) ? $va_element['settings']['width'] : "100px",
					'height' => (isset($va_element['settings']['height']) && ($va_element['settings']['height'] > 0)) ? $va_element['settings']['height'] : 1,

					'format' => '^ELEMENT',
					'multivalueFormat' => '<i>^LABEL</i><br/>^ELEMENT',
					'id' => str_replace('.', '_', $vs_field),
					'policy' => $policy,
				    'name' => $bundle
				)),
				'label' => ($vs_field_label) ? $vs_field_label :  $t_instance->getDisplayLabel($vs_field),
				'name' => $bundle
			);
		}

		return $va_output;
	}
	# ------------------------------------------------------
	# Form content processor
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getLuceneQueryStringForHTMLFormInput($pa_form_content, array $options=null) {
		$va_values = $this->extractFormValuesFromArray($pa_form_content);

		$va_query_elements = [];
		if (is_array($va_values) && sizeof($va_values)) {
			foreach($va_values as $vs_element => $va_values) {
				if (!is_array($va_values)) { $va_values = array($va_values); }
				foreach($va_values as $vs_value) {
					if (!strlen(trim($vs_value))) { continue; }
					if (preg_match('![^A-Za-z0-9]+!', $vs_value) && !preg_match('![\*]+!', $vs_value) && ($vs_value[0] != '[')) {
						$vs_query_element = '"'.str_replace('"', '', $vs_value).'"';
					} else {
						$vs_query_element = $vs_value;
					}
					
					$vs_query_element = caMatchOnStem($vs_query_element);
					
					switch($vs_element){
						case '_fulltext':		// don't qualify special "fulltext" element
							$va_query_elements[] = $vs_query_element;
							break;
						default:
							$va_tmp = explode(".", $vs_element);
							if ($va_tmp[1] == 'current_value') { // history tracking current value search (format is <table>.current_value.<policy name>
							    $va_query_elements[] = "({$vs_element}:{$vs_query_element})";
							} else {
                                $t_element = ca_metadata_elements::getInstance($vs_element_code = array_pop($va_tmp));
                                switch(ca_metadata_elements::getDataTypeForElementCode($vs_element_code)) {
                                    case __CA_ATTRIBUTE_VALUE_CURRENCY__:
                                        // convert bare ranges to Lucene range syntax
                                        if (preg_match("!^([A-Z\$£¥€]+[ ]*[\d\.]+)[ ]*([-–]{1}|to)[ ]*([A-Z\$£¥€]+[ ]*[\d\.]+)$!", trim(str_replace('"', '', $vs_query_element)), $m)) {
                                            $vs_query_element = "[".$m[1]." to ".$m[3]."]";
                                        }
                                        break;
                                    case __CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__:
                                        $o_value = new InformationServiceAttributeValue();
                                        $va_data = $o_value->parseValue($vs_query_element, ['settings' => $t_element->getSettings()]);
                                
                                        $vs_query_element = $va_data['value_longtext1'];
                                        break;
                                }
                                $va_query_elements[] = "({$vs_element}:{$vs_query_element})";
                            }
							break;
					}
				}
			}
		}
		
		return join(' AND ', $va_query_elements);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function extractFormValuesFromArray($pa_form_content) {
		if (!($vn_form_id = $this->getPrimaryKey())) { return null; }

		$va_form_contents = $this->getElementsForForm();
		
		$va_values = [];
		foreach($va_form_contents as $vn_i => $vs_element) {
			switch($vs_element) {
				case '_generic':
					$info = $this->getPlacementInfo($vn_i);
					$vs_element = $info['settings']['bundle'];
					break;
			}
			$vs_dotless_element = str_replace('.', '_', $vs_element);
			if (isset($pa_form_content[$vs_dotless_element])) { // && strlen($pa_form_content[$vs_dotless_element])) {
				$va_values[$vs_element] = strlen(($pa_form_content[$vs_dotless_element])) ? $pa_form_content[$vs_dotless_element] : ' ';
			} else {
				// maybe it's a check list where the value is hung off the end of the field name?
				foreach($pa_form_content as $vs_key => $vs_val) {
					if (preg_match("!{$vs_dotless_element}_!", $vs_key)) {
						$va_values[$vs_element][] = $vs_val;
					}
				}
			}
		}
		return $va_values;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getElementsForForm($pa_options=null) {
		$va_placements = $this->getPlacements();

		$t_element = new ca_metadata_elements();
		$va_elements = [];
		foreach($va_placements as $vn_i => $va_placement) {
			$va_tmp = explode('.',  $va_placement['bundle_name']);
			if ($va_tmp[1] == 'current_value') { 
			    $element_code = $va_tmp[3];
			    $element_prefix = $va_placement['bundle_name'];
			} else {
			    $element_code = $va_tmp[1];
			    $element_prefix = $va_tmp[0].'.'.$va_tmp[1];
			}
			if ($t_element->load(array('element_code' => $element_code))) {
				if ($t_element->get('datatype') > 0) {
					$va_elements[$vn_i] = $va_placement['bundle_name'];
				}
				if (sizeof($va_sub_elements = $t_element->getElementsInSet()) > 1) {
					foreach($va_sub_elements as $vn_element_id => $va_element_info) {
						if($element_code == $va_element_info['element_code']) { continue; }
						if($va_element_info['datatype'] == 0) { continue; }
						$va_elements[$vn_i.'_'.$vn_element_id] = $element_prefix.'.'.$va_element_info['element_code'];
					}
				}
			} else {
				$va_elements[$vn_i] = $va_placement['bundle_name'];
			}
		}
		return $va_elements;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getPlacementInfo($index, $options=null) {
		$placements = $this->getPlacements();
		return isset($placements[$index]) ? $placements[$index] : null;
	}
	# ------------------------------------------------------
	# Bundles
	# ------------------------------------------------------
	/**
	 * Returns HTML bundle for adding/editing/deleting placements from a form
	 *
	 * @param object $po_request The current request
	 * @param $ps_form_name The name of the HTML form this bundle will be part of
	 * @return string HTML for bundle
	 */
	public function getSearchFormHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null) {
		if (!$this->haveAccessToForm($po_request->getUserID(), __CA_SEARCH_FORM_EDIT_ACCESS__)) {
			return null;
		}

		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");

		$o_view->setVar('lookup_urls', caJSONLookupServiceUrl($po_request, Datamodel::getTableName($this->get('table_num'))));
		$o_view->setVar('t_form', $this);
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);

		return $o_view->render('ca_search_form_placements.php');
	}
	# ----------------------------------------
	public function savePlacementsFromHTMLForm($po_request, $ps_form_name, $ps_placement_code, $pa_options=null) {
		if ($vs_bundles = $po_request->getParameter("{$ps_placement_code}{$ps_form_name}displayBundleList", pString)) {
			$va_bundles = explode(';', $vs_bundles);

			$t_form = new ca_search_forms($this->getPrimaryKey());
			$va_placements = $t_form->getPlacements(array('user_id' => $po_request->getUserID()));

			// remove deleted bundles

			foreach($va_placements as $vn_placement_id => $va_bundle_info) {
				if (!in_array($va_bundle_info['bundle_name'].'_'.$va_bundle_info['placement_id'], $va_bundles)) {
					$t_form->removePlacement($va_bundle_info['placement_id'], array('user_id' => $po_request->getUserID()));
					if ($t_form->numErrors()) {
						$this->errors = $t_form->errors;
						return false;
					}
				}
			}

			$va_locale_list = ca_locales::getLocaleList(array('index_by_code' => true));

			$va_available_bundles = $t_form->getAvailableBundles();
			foreach($va_bundles as $vn_i => $vs_bundle) {
				// get settings

				if (preg_match('!^(.*)_([\d]+)$!', $vs_bundle, $va_matches)) {
					$vn_placement_id = (int)$va_matches[2];
					$vs_bundle = $va_matches[1];
				} else {
					$vn_placement_id = null;
				}
				$vs_bundle_proc = str_replace(".", "_", $vs_bundle);

				$va_settings = [];

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
					$t_form->addPlacement($vs_bundle, $va_settings[$vn_placement_id] ?? null, $vn_i + 1, array('user_id' => $po_request->getUserID(), 'additional_settings' => $va_available_bundles[$vs_bundle]['settings'] ?? []));
					if ($t_form->numErrors()) {
						$this->errors = $t_form->errors;
						return false;
					}
				} else {
					$t_placement = new ca_search_form_placements($vn_placement_id, null, $va_available_bundles[$vs_bundle]['settings']);
					$t_placement->set('rank', $vn_i + 1);

					if (is_array($va_settings[$vn_placement_id] ?? null)) {
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
	 * Adds restriction (a binding between the ui and item type)
	 *
	 * @param int $pn_type_id the type
	 * @param array $pa_settings Options include:
	 *		includeSubtypes = automatically expand type restriction to include sub-types. [Default is false]
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function addTypeRestriction($pn_type_id, $pa_settings=null) {
		if (!($vn_form_id = $this->getPrimaryKey())) { return null; }		// UI must be loaded
		if (!is_array($pa_settings)) { $pa_settings = []; }
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('table_num')))) { return false; }

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
		
		$t_restriction = new ca_search_form_type_restrictions();
		$t_restriction->set('table_num', $this->get('table_num'));
		$t_restriction->set('type_id', $pn_type_id);
		$t_restriction->set('include_subtypes', caGetOption('includeSubtypes', $pa_settings, 0));
		$t_restriction->set('form_id', $this->getPrimaryKey());
		
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
		if (!($vn_form_id = $this->getPrimaryKey())) { return null; }		// UI must be loaded
		$t_restriction = new ca_search_form_type_restrictions($pn_restriction_id);
		if ($t_restriction->isLoaded()) {
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
		if (!($vn_form_id = $this->getPrimaryKey())) { return null; }		// UI must be loaded
		if (!is_array($pa_type_ids)) {
			if (is_numeric($pa_type_ids)) { 
				$pa_type_ids = array($pa_type_ids); 
			} else {
				$pa_type_ids = [];
			}
		}
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('table_num')))) { return false; }

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
		if (!($vn_form_id = (int)$this->getPrimaryKey())) { return null; }		// ui must be loaded

		$va_params = ['form_id' => $vn_form_id];
		if ((int)$pn_type_id > 0) { $va_params['type_id'] = (int)$pn_type_id; }

		if (is_array($va_uis = ca_search_form_type_restrictions::find($va_params, ['returnAs' => 'modelInstances']))) {
			foreach($va_uis as $t_ui) {
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
	 * @param array $pa_options Support options include:
	 *		form_id = form to get types for instead of currently loaded form. [Default is null]
	 * @return array A list of restrictions, false on error or null if no ui is loaded
	 */
	public function getTypeRestrictions($pn_type_id=null, $pa_options=null) {
		if (!($pn_form_id = caGetOption('form_id', $pa_options, null))) {
			if (!($pn_form_id = (int)$this->getPrimaryKey())) { return null; }
		}
		
		$va_params = ['form_id' => $pn_form_id];
		if ((int)$pn_type_id > 0) { $va_params['type_id'] = (int)$pn_type_id; }

		return ca_search_form_type_restrictions::find($va_params, ['returnAs' => 'arrays']);
	}
	# ----------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of type restriction in the currently loaded form
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getTypeRestrictionsHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_form', $this);
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
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($vn_table_num = $this->get('table_num')))) { return null; }

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
	
		return $o_view->render('ca_search_form_type_restrictions.php');
	}
	# ----------------------------------------
	public function saveTypeRestrictionsFromHTMLForm($po_request, $ps_form_prefix, $ps_placement_code) {
		if (!$this->getPrimaryKey()) { return null; }
		
		return $this->setTypeRestrictions($po_request->getParameter('type_restrictions', pArray), ['includeSubtypes' => $po_request->getParameter('type_restriction_include_subtypes', pInteger)]);
	}
	# ----------------------------------------
}
