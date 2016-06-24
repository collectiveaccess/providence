<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_alert_triggers.php : table access class for table ca_metadata_alert_triggers
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

require_once(__CA_LIB_DIR__.'/ca/MetadataAlerts/TriggerTypes/Base.php');

BaseModel::$s_ca_models_definitions['ca_metadata_alert_triggers'] = array(
	'NAME_SINGULAR' 	=> _t('metadata alert triggers'),
	'NAME_PLURAL' 		=> _t('metadata alert triggers'),
	'FIELDS' 			=> array(
		'trigger_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this trigger')
		),
		'rule_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for rule this trigger belongs to'
		),
		'element_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Element id', 'DESCRIPTION' => 'Identifier for trigger element'
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Trigger settings')
		),
		'trigger_type' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'BOUNDS_CHOICE_LIST' => CA\MetadataAlerts\TriggerTypes\Base::getAvailableTypes(),
			'LABEL' => _t('Trigger type'), 'DESCRIPTION' => _t('Element indicating type of trigger.'),
		),
	)
);

class ca_metadata_alert_triggers extends BaseModel {
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
	protected $TABLE = 'ca_metadata_alert_triggers';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'trigger_id';

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
	protected $ORDER_BY = array('trigger_id');

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
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			"rule_id"
		),
		"RELATED_TABLES" => array(

		)
	);
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;

	/**
	 * @var resource|null
	 */
	static $s_lock_resource = null;

	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;

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

		$this->loadSettingsForTriggerType();
	}
	# ------------------------------------------------------
	protected function loadSettingsForTriggerType() {
		if($vs_trigger_type = $this->get('trigger_type')) {
			/** @var CA\MetadataAlerts\TriggerTypes\Base $o_trigger_type */
			$o_trigger_type = CA\MetadataAlerts\TriggerTypes\Base::getInstance($vs_trigger_type);
			$this->SETTINGS = new ModelSettings($this, 'settings', $o_trigger_type->getAvailableSettings());
		}
	}
	# ------------------------------------------------------
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		$vm_ret = parent::set($pa_fields, $pm_value, $pa_options);

		if($this->changed('trigger_type')) {
			$this->loadSettingsForTriggerType();
		}

		return $vm_ret;
	}
	# ------------------------------------------------------
	public function load($pm_id=null, $pb_use_cache=true) {
		$vm_ret = parent::load($pm_id, $pb_use_cache);

		$this->loadSettingsForTriggerType();
		return $vm_ret;
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
}
