<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_alert_rules.php : table access class for table ca_metadata_alert_rules
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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

define('__CA_ALERT_RULE_NO_ACCESS__', 0);
define('__CA_ALERT_RULE_ACCESS_NOTIFICATION__', 1);
define('__CA_ALERT_RULE_ACCESS_ACCESS_EDIT__', 2);

require_once(__CA_LIB_DIR__.'/SetUniqueIdnoTrait.php'); 
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rule_type_restrictions.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rule_labels.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rules_x_user_groups.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rules_x_users.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_triggers.php');

BaseModel::$s_ca_models_definitions['ca_metadata_alert_rules'] = array(
	'NAME_SINGULAR' 	=> _t('metadata alert rule'),
	'NAME_PLURAL' 		=> _t('metadata alert rules'),
	'FIELDS' 			=> array(
		'rule_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this rule')
		),
		'user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
			'DEFAULT' => '',
			'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who created the form.')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DONT_USE_AS_BUNDLE' => true,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_VALUE' => array(1,255),
				'LABEL' => _t('Alert target'), 'DESCRIPTION' => _t('Determines what kind of item (objects, entities, places, etc.) is covered by the alert .'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Objects') => 57,
					_t('Object lots') => 51,
					_t('Entities') => 20,
					_t('Places') => 72,
					_t('Occurrences') => 67,
					_t('Collections') => 13,
					_t('Storage locations') => 89,
					_t('Object representations') => 56,
					_t('Loans') => 133,
					_t('Movements') => 137,
					_t('List items') => 33,
					_t('Tours') => 153,
					_t('Tour stops') => 155
				)
		),
		'code' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 30, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Code'), 'DESCRIPTION' => _t('Short code for rule (must be unique)'),
			'BOUNDS_LENGTH' => array(0,20)
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Trigger settings')
		)
	)
);

class ca_metadata_alert_rules extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_metadata_alert_rules';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'rule_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array();

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
	protected $ORDER_BY = array('rule_id');

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
	protected $USERS_RELATIONSHIP_TABLE = 'ca_metadata_alert_rules_x_users';
	protected $USER_GROUPS_RELATIONSHIP_TABLE = 'ca_metadata_alert_rules_x_user_groups';

	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_metadata_alert_rule_labels';

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
	protected $ID_NUMBERING_ID_FIELD = 'code';			// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;			// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;

	/**
	 * @var resource|null
	 */
	static $s_lock_resource = null;

	/** @var array access to rule cache */
	static $s_have_access_to_rule_cache = [];

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

		// Filter list of tables form can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_metadata_alert_rules']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = array_flip(caGetPrimaryTables(true));
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);

		$this->BUNDLES['ca_users'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Recipient users'));
		$this->BUNDLES['ca_user_groups'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Recipient user groups'));

		$this->BUNDLES['ca_metadata_alert_rule_type_restrictions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Type restrictions'));
		$this->BUNDLES['ca_metadata_alert_triggers'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Trigger'));
	}
	# ----------------------------------------
	/**
	 * Return restrictions for currently loaded rule
	 *
	 * @param int $pn_type_id Type to limit returned restrictions to; if omitted or null then all restrictions are returned
	 * @return array A list of restrictions, false on error or null if no ui is loaded
	 */
	public function getTypeRestrictions($pn_type_id=null) {
		if (!($this->getPrimaryKey())) { return null; }		// rule must be loaded

		$o_db = $this->getDb();

		$vs_table_type_sql = '';
		if ($pn_type_id > 0) {
			$vs_table_type_sql .= ' AND type_id = '.intval($pn_type_id);
		}
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_metadata_alert_rule_type_restrictions
			WHERE
				rule_id = ? {$vs_table_type_sql}
		", (int)$this->getPrimaryKey());

		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}

		$va_restrictions = array();
		while($qr_res->nextRow()) {
			$va_restriction = $qr_res->getRow();
			$va_restriction['type_code'] = caGetListItemIdno($va_restriction['type_id']);
			$va_restrictions[] = $va_restriction;
		}
		return $va_restrictions;
	}
	# ----------------------------------------
	/**
	 * Adds restriction (a binding between the display and item type)
	 *
	 * @param int $pn_type_id the type
	 * @param array $pa_settings Array of options for the restriction. (No options are currently implemented).
	 * @return bool True on success, false on error, null if no screen is loaded
	 *
	 */
	public function addTypeRestriction($pn_type_id, $pa_settings=null) {
		if (!($vn_rule_id = $this->getPrimaryKey())) { return null; }		// display must be loaded
		if (!is_array($pa_settings)) { $pa_settings = array(); }

		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('table_num')))) { return false; }

		$va_type_list = $t_instance->getTypeList();
		if (!isset($va_type_list[$pn_type_id])) { return false; }

		$t_restriction = new ca_metadata_alert_rule_type_restrictions();
		if ($this->inTransaction()) { $t_restriction->setTransaction($this->getTransaction()); }
		$t_restriction->setMode(ACCESS_WRITE);
		$t_restriction->set('table_num', $this->get('table_num'));
		$t_restriction->set('type_id', $pn_type_id);
		$t_restriction->set('rule_id', $this->getPrimaryKey());
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
	 * Remove restriction from currently loaded display for specified type
	 *
	 * @param int $pn_type_id The type of the restriction
	 * @return bool True on success, false on error, null if no screen is loaded
	 */
	public function removeTypeRestriction($pn_type_id) {
		if (!($vn_rule_id = $this->getPrimaryKey())) { return null; }		// display must be loaded

		$o_db = $this->getDb();

		$qr_res = $o_db->query("
			DELETE FROM ca_metadata_alert_rule_type_restrictions
			WHERE
				rule_id = ? AND type_id = ?
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
		if (!($vn_rule_id = $this->getPrimaryKey())) { return null; }		// display must be loaded

		$o_db = $this->getDb();

		$qr_res = $o_db->query("
			DELETE FROM ca_metadata_alert_rule_type_restrictions
			WHERE
				rule_id = ?
		", (int)$this->getPrimaryKey());

		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Sets restrictions for currently loaded rule
	 *
	 * @param array $pa_type_ids list of types to restrict to
	 * @return bool True on success, false on error, null if no screen is loaded
	 *
	 */
	public function setTypeRestrictions($pa_type_ids) {
		if (!($vn_rule_id = $this->getPrimaryKey())) { return null; }		// rule must be loaded
		if (!is_array($pa_type_ids)) {
			if (is_numeric($pa_type_ids)) {
				$pa_type_ids = array($pa_type_ids);
			} else {
				$pa_type_ids = array();
			}
		}

		if (!($t_instance = Datamodel::getInstanceByTableNum($this->get('table_num')))) { return false; }

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
	 * Renders and returns HTML form bundle for management of type restriction in the currently loaded alert rule
	 *
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for alert rule
	 */
	public function getTypeRestrictionsHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');

		$o_view->setVar('t_rule', $this);
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);
		$o_view->setVar('request', $po_request);

		$va_type_restrictions = $this->getTypeRestrictions();
		$va_restriction_type_ids = array();
		if (is_array($va_type_restrictions)) {
			foreach($va_type_restrictions as $vn_i => $va_restriction) {
				$va_restriction_type_ids[] = $va_restriction['type_id'];
			}
		}

		if (!($t_instance = Datamodel::getInstanceByTableNum($vn_table_num = $this->get('table_num')))) { return null; }

		$o_view->setVar('type_restrictions', $t_instance->getTypeListAsHTMLFormElement('type_restrictions[]', array('multiple' => 1, 'height' => 5), array('value' => 0, 'values' => $va_restriction_type_ids)));

		return $o_view->render('ca_metadata_alert_rule_type_restrictions.php');
	}
	# ----------------------------------------
	public function saveTypeRestrictionsFromHTMLForm($po_request, $ps_form_prefix, $ps_placement_code) {
		if (!$this->getPrimaryKey()) { return null; }

		return $this->setTypeRestrictions($po_request->getParameter('type_restrictions', pArray));
	}
	# ------------------------------------------------------
	/**
	 * Returns list of metadata alert rules subject to options
	 *
	 * @param array $pa_options Optional array of options. Supported options are:
	 *			table = If set, list is restricted to rules that pertain to the specified table. You can pass a table name or number. If omitted rules for all tables will be returned.
	 *			user_id = Restricts returned rules to those accessible by the current user. If omitted then all rules, regardless of access are returned.
	 *			restrictToTypes = Restricts returned rules to those bound to the specified type. Default is to not restrict by type.
	 *			dontIncludeSubtypesInTypeRestriction = If restrictToTypes is set, controls whether or not subtypes are automatically included in the restriction. Default is false – subtypes are included.
	 *			access = Restricts returned rules to those with at least the specified access level for the specified user. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then rules where the user has at least read access will be returned.
	 * @return array
	 */
	public function getRules($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$pm_table_name_or_num = 							caGetOption('table', $pa_options, null);
		$pn_user_id = 										caGetOption('user_id', $pa_options, null);
		$pn_user_access = 									caGetOption('access', $pa_options, null);
		$pa_access = 										caGetOption('checkAccess', $pa_options, null);
		$pa_restrict_to_types = 							caGetOption('restrictToTypes', $pa_options, null);
		$pb_dont_include_subtypes_in_type_restriction = 	caGetOption('dontIncludeSubtypesInTypeRestriction', $pa_options, false);

		$vn_table_num = 0;
		if ($pm_table_name_or_num && !($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return array(); }

		$o_db = $this->getDb();

		$va_sql_wheres = array(
			'((marl.is_preferred = 1) OR (marl.is_preferred is null))'
		);
		if ($vn_table_num > 0) {
			$va_sql_wheres[] = "(mar.table_num = ".intval($vn_table_num).")";
		}

		if(is_array($pa_restrict_to_types) && sizeof($pa_restrict_to_types)) {
			$va_type_list = caMakeTypeIDList($pm_table_name_or_num, $pa_restrict_to_types, array('dontIncludeSubtypesInTypeRestriction' => $pb_dont_include_subtypes_in_type_restriction));
			if (sizeof($va_type_list) > 0) {
				$va_sql_wheres[] = "(martr.type_id IS NULL OR martr.type_id IN (".join(",", $va_type_list)."))";
			}
		}
		if (is_array($pa_access) && (sizeof($pa_access))) {
			$pa_access = array_map("intval", $pa_access);
			$va_sql_wheres[] = "(mar.access IN (".join(",", $pa_access)."))";
		}

		$va_sql_access_wheres = array();
		if ($pn_user_id) {
			$t_user = Datamodel::getInstanceByTableName('ca_users', true);
			$t_user->load($pn_user_id);

			if ($t_user->getPrimaryKey()) {
				$vs_access_sql = ($pn_user_access > 0) ? " AND (access >= ".intval($pn_user_access).")" : "";
				if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
					$vs_sql = "(
						(mar.user_id = ".intval($pn_user_id).") OR
						(mar.rule_id IN (
								SELECT rule_id
								FROM ca_metadata_alert_rules_x_users
								WHERE
									group_id IN (".join(',', array_keys($va_groups)).") {$vs_access_sql}
							)
						)
					)";
				} else {
					$vs_sql = "(mar.user_id = {$pn_user_id})";
				}

				$vs_sql .= " OR (mar.rule_id IN (
										SELECT rule_id
										FROM ca_metadata_alert_rules_x_user_groups
										WHERE
											user_id = {$pn_user_id} {$vs_access_sql}
									)
								)";


				$va_sql_access_wheres[] = "({$vs_sql})";
			}
		}

		if (($pn_user_access == __CA_ALERT_RULE_ACCESS_NOTIFICATION__)) {
			$va_sql_access_wheres[] = "(mar.is_system = 1)";
		}

		if (sizeof($va_sql_access_wheres)) {
			$va_sql_wheres[] = "(".join(" OR ", $va_sql_access_wheres).")";
		}

		// get triggers
		$va_triggers_by_rule_id = [];
		$qr_triggers = $o_db->query("
			SELECT rule_id, trigger_type
			FROM ca_metadata_alert_triggers
		");
		while($qr_triggers->nextRow()) {
			$va_triggers_by_rule_id[$qr_triggers->get('rule_id')][] = $qr_triggers->get('trigger_type');
		}
		
		
		// get rules
		$qr_res = $o_db->query("
			SELECT
				mar.rule_id, mar.code, mar.user_id, mar.table_num,
				marl.label_id, marl.name, marl.locale_id, u.fname, u.lname, u.email,
				l.language, l.country
			FROM ca_metadata_alert_rules AS mar
			LEFT JOIN ca_metadata_alert_rule_labels AS marl ON mar.rule_id = marl.rule_id
			LEFT JOIN ca_locales AS l ON marl.locale_id = l.locale_id
			LEFT JOIN ca_metadata_alert_rule_type_restrictions AS martr ON mar.rule_id = martr.rule_id
			INNER JOIN ca_users AS u ON mar.user_id = u.user_id
			".(sizeof($va_sql_wheres) ? 'WHERE ' : '')."
			".join(' AND ', $va_sql_wheres)."
			ORDER BY martr.rule_id DESC, marl.name ASC
		");
		$va_rules = array();

		$va_type_name_cache = array();
		while($qr_res->nextRow()) {
			$vn_table_num = $qr_res->get('table_num');
			$vn_rule_id = $qr_res->get('rule_id');
			
			if (!isset($va_type_name_cache[$vn_table_num]) || !($vs_display_type = $va_type_name_cache[$vn_table_num])) {
				$vs_display_type = $va_type_name_cache[$vn_table_num] = $this->getMetadataAlertRuleTypeName($vn_table_num, array('number' => 'plural'));
			}
			$va_rules[$vn_rule_id][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array('metadata_alert_rule_content_type' => $vs_display_type, 'trigger_types' => is_array($va_triggers_by_rule_id[$vn_rule_id]) ? join(', ', $va_triggers_by_rule_id[$vn_rule_id]) : ""));
		}
		return $va_rules;
	}
	# ------------------------------------------------------
	/**
	 * Determines if user has access to a rule at a specified access level.
	 *
	 * @param int $pn_user_id user_id of user to check rule access for
	 * @param int $pn_access type of access required. Use __CA_ALERT_RULE_ACCESS_NOTIFICATION__ for read-only access or __CA_ALERT_RULE_ACCESS_ACCESS_EDIT__ for editing (full) access
	 * @param int $pn_rule_id The id of the rule to check. If omitted then currently loaded rule will be checked.
	 * @return bool True if user has access, false if not
	 */
	public function haveAccessToForm($pn_user_id, $pn_access, $pn_rule_id=null) {
		$vn_rule_id = null;
		if ($pn_rule_id) {
			$vn_rule_id = $pn_rule_id;
			$t_rule = new ca_metadata_alert_rules($vn_rule_id);
			$vn_form_user_id = $t_rule->get('user_id');
		} else {
			$vn_form_user_id = $this->get('user_id');
			$t_rule = $this;
		}
		if(!$vn_rule_id && !($vn_rule_id = $t_rule->getPrimaryKey())) {
			return true; // new rule
		}

		// return from cache
		if (isset(ca_metadata_alert_rules::$s_have_access_to_rule_cache[$vn_rule_id.'/'.$pn_user_id.'/'.$pn_access])) {
			return ca_metadata_alert_rules::$s_have_access_to_rule_cache[$vn_rule_id.'/'.$pn_user_id.'/'.$pn_access];
		}

		if (($vn_form_user_id == $pn_user_id)) {	// owners have all access
			return ca_metadata_alert_rules::$s_have_access_to_rule_cache[$vn_rule_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}

		if ((bool)$t_rule->get('is_system') && ($pn_access == __CA_ALERT_RULE_ACCESS_NOTIFICATION__)) {	// system forms are readable by all
			return ca_metadata_alert_rules::$s_have_access_to_rule_cache[$vn_rule_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}

		$o_db =  $this->getDb();
		$qr_res = $o_db->query("
			SELECT rxg.rule_id
			FROM ca_metadata_alert_rules_x_user_groups rxg
			INNER JOIN ca_user_groups AS ug ON rxg.group_id = ug.group_id
			INNER JOIN ca_users_x_groups AS uxg ON uxg.group_id = ug.group_id
			WHERE
				(rxg.access >= ?) AND (uxg.user_id = ?) AND (rxg.rule_id = ?)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_rule_id);

		if ($qr_res->numRows() > 0) { return ca_metadata_alert_rules::$s_have_access_to_rule_cache[$vn_rule_id.'/'.$pn_user_id.'/'.$pn_access] = true; }

		$qr_res = $o_db->query("
			SELECT rxu.rule_id
			FROM ca_metadata_alert_rules_x_users rxu
			INNER JOIN ca_users AS u ON rxu.user_id = u.user_id
			WHERE
				(rxu.access >= ?) AND (u.user_id = ?) AND (rxu.rule_id = ?)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_rule_id);

		if ($qr_res->numRows() > 0) { return ca_metadata_alert_rules::$s_have_access_to_rule_cache[$vn_rule_id.'/'.$pn_user_id.'/'.$pn_access] = true; }


		return ca_metadata_alert_rules::$s_have_access_to_rule_cache[$vn_rule_id.'/'.$pn_user_id.'/'.$pn_access] = false;
	}
	# ------------------------------------------------------
	/**
	 * Returns name of type of content (synonymous with the table name for the content) currently loaded bundle display contains for display. Will return name in singular number unless the 'number' option is set to 'plural'
	 *
	 * @param int $pm_table_name_or_num Table number to return name for. If omitted then the name for the content type contained by the current bundle display will be returned. Use this parameter if you want to force a content type without having to load a bundle display.
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		number = Set to 'plural' to return plural version of name; set to 'singular' [default] to return the singular version
	 * @return string The name of the type of content or null if $pn_table_num is not set to a valid table and no form is loaded.
	 */
	public function getMetadataAlertRuleTypeName($pm_table_name_or_num=null, $pa_options=null) {
		if (!$pm_table_name_or_num && !($pm_table_name_or_num = $this->get('table_num'))) { return null; }
		if (!($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return null; }

		$t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
		if (!$t_instance) { return null; }
		return (isset($pa_options['number']) && ($pa_options['number'] == 'plural')) ? $t_instance->getProperty('NAME_PLURAL') : $t_instance->getProperty('NAME_SINGULAR');

	}
	# ------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options
	 * @return string
	 */
	public function getTriggerHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, array $pa_bundle_settings=[], array $pa_options=[]) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');

		$o_view->setVar('t_rule', $this);
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);
		$o_view->setVar('request', $po_request);

		if(!($vn_table_num = $this->get('table_num'))) { return null; }

		$o_view->setVar('table_num', $vn_table_num);

		$vn_trigger_id = null;
		if($va_triggers = $this->getTriggers()) {
			$va_trigger = array_shift($va_triggers);
			$vn_trigger_id = $va_trigger['trigger_id'];
		}

		$t_trigger = new ca_metadata_alert_triggers($vn_trigger_id);
		$o_view->setVar('t_trigger', $t_trigger);

		return $o_view->render('ca_metadata_alert_triggers.php');
	}
	# ------------------------------------------------------
	/**
	 * Get all triggers for this rule
	 *
	 * @param array $pa_options
	 * @return array
	 */
	public function getTriggers(array $pa_options = []) {
		if(!$this->getPrimaryKey()) { return []; }

		$qr_triggers = $this->getDb()->query('SELECT * FROM ca_metadata_alert_triggers WHERE rule_id = ?', $this->getPrimaryKey());

		$va_return = [];

		while($qr_triggers->nextRow()) {
			$va_return[$qr_triggers->get('trigger_id')] = $qr_triggers->getRow();
			$va_return[$qr_triggers->get('trigger_id')]['settings'] = caUnserializeForDatabase($qr_triggers->get('settings'));
			$va_return[$qr_triggers->get('trigger_id')]['element_filters'] = caUnserializeForDatabase($qr_triggers->get('element_filters'));
		}

		return $va_return;
	}
	# ------------------------------------------------------
	/**
	 * Save trigger bundle
	 *
	 * @param $po_request
	 * @param $ps_form_prefix
	 * @param $ps_placement_code
	 */
	public function saveTriggerHTMLFormBundle($po_request, $ps_form_prefix, $ps_placement_code) {
		$vs_id_prefix = $ps_placement_code.$ps_form_prefix;

		$va_triggers = $this->getTriggers();

		$vn_trigger_id = null;
		if(is_array($va_triggers) && (sizeof($va_triggers)>0)) {
			$va_trigger = array_shift($va_triggers);
			$vn_trigger_id = $va_trigger['trigger_id'];
		}

		$t_trigger = new ca_metadata_alert_triggers($vn_trigger_id);

		// set vars for trigger
		if($vs_trigger_type = $_REQUEST["{$vs_id_prefix}_trigger_type"]) {
			$t_trigger->set('trigger_type', $vs_trigger_type);
		}
		
		// find settings keys in request and set them
		// find element filters
		$va_element_filters = [];
		
		$vn_element_id = (int)$_REQUEST["{$vs_id_prefix}_element_id"];
		$t_trigger->set('element_id', $vn_element_id ? $vn_element_id : null);
		
		if(in_array($_REQUEST["{$vs_id_prefix}_element_id"], ['_intrinsic_idno', '_preferred_labels', '_nonpreferred_labels'])) {
			$va_element_filters['_non_element_filter'] = $_REQUEST["{$vs_id_prefix}_element_id"];
		}
		
		foreach($_REQUEST as $vs_k => $vm_v) {
			if(preg_match("/^{$vs_id_prefix}_setting_(.+)$/u", $vs_k, $va_matches)) {
				$t_trigger->setSetting($va_matches[1], $vm_v);
			} elseif(preg_match("/^{$vs_id_prefix}_element_filter_(.+)$/u", $vs_k, $va_matches)) {
				$va_element_filters[$va_matches[1]] = $vm_v;
			}
		}
		
		$t_trigger->set('element_filters', $va_element_filters);
		$t_trigger->set('rule_id', $this->getPrimaryKey());
		$t_trigger->setMode(ACCESS_WRITE);

		// insert or update this trigger
		if($t_trigger->getPrimaryKey()) {
			$t_trigger->update();
		} else {
			$t_trigger->insert();
		}

		if($t_trigger->numErrors() > 0) {
			$this->errors = $t_trigger->errors;
			return false;
		}
		return true;
	}
	# ------------------------------------------------------
}
