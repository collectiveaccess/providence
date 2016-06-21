<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_alert_rules.php : table access class for table ca_metadata_alert_rules
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
define('__CA_ALERT_RULE_ACCESS_READONLY__', 1);
define('__CA_ALERT_RULE_ACCESS_ACCESS_EDIT__', 2);

require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rule_type_restrictions.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rule_labels.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_notifications.php');
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
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'DONT_USE_AS_BUNDLE' => true,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Table', 'DESCRIPTION' => 'Table',
			'BOUNDS_VALUE' => array(0,255)
		),
		'code' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 30, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Code'), 'DESCRIPTION' => _t('Short code for rule (must be unique)'),
			'BOUNDS_LENGTH' => array(1,20)
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Trigger settings')
		),
	)
);

class ca_metadata_alert_rules extends BundlableLabelableBaseModelWithAttributes {
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
	protected $ID_NUMBERING_ID_FIELD = null;				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;				// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)

	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;

	/**
	 * @var resource|null
	 */
	static $s_lock_resource = null;

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
		BaseModel::$s_ca_models_definitions['ca_metadata_alert_rules']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caGetPrimaryTablesForHTMLSelect();
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_users'] = array('type' => 'special', 'repeating' => true, 'label' => _t('User access'));
		$this->BUNDLES['ca_user_groups'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Group access'));
		$this->BUNDLES['ca_metadata_alert_rule_type_restrictions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Type restrictions'));
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

		if (!($t_instance = $this->_DATAMODEL->getInstanceByTableNum($this->get('table_num')))) { return false; }

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

		if (!($t_instance = $this->_DATAMODEL->getInstanceByTableNum($vn_table_num = $this->get('table_num')))) { return null; }

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
	 * @param int $pn_user_id
	 * @return array
	 */
	public static function getList($pn_user_id) {
		$o_db = new Db();

		$o_db->query('SELECT * FROM ca_metadata_alert_rules WHERE user_id=?', $pn_user_id);

		return [];
	}
	# ------------------------------------------------------
}
