<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_dictionary_rules.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2021 Whirl-i-Gig
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

BaseModel::$s_ca_models_definitions['ca_metadata_dictionary_rules'] = array(
 	'NAME_SINGULAR' 	=> _t('Metadata dictionary rule'),
 	'NAME_PLURAL' 		=> _t('Metadata dictionary rules'),
 	'FIELDS' 			=> array(
 		'rule_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Rule id', 'DESCRIPTION' => 'Identifier for rule'
		),
		'entry_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Entry id', 'DESCRIPTION' => 'Identifier for entry'
		),
		'rule_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => "200px", 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				//'FILTER' => '!^[\p{L}0-9_]+$!u',
				'LABEL' => _t('Rule code'), 'DESCRIPTION' => _t('Unique alphanumeric code for the rule.'),
				'BOUNDS_LENGTH' => array(1,30),
				//'UNIQUE_WITHIN' => array()
		),
		'expression' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => "620px", 'DISPLAY_HEIGHT' => 3,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Expression'), 'DESCRIPTION' => _t('Expression to evaluate'),
				'BOUNDS_VALUE' => array(1,65535)
		),
		'rule_level' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => "160px", 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Rule level'), 'DESCRIPTION' => _t('Level of importance of rule.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Error') => 'ERR',
					_t('Warning') => 'WARN',
					_t('Notice') => 'NOTE',
					_t('Debug') => 'DEBG' 
				)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Settings')
		)
 	)
);


class ca_metadata_dictionary_rules extends BaseModel {
	use ModelSettings;
	
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
	protected $TABLE = 'ca_metadata_dictionary_rules';
	      
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
	protected $ORDER_BY = array('rule_id');

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
	protected $LABEL_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	function __construct($pn_id=null, ?array $options=null, $pa_additional_settings=null, $pa_setting_values=null) {
		parent::__construct($id, $options);
		
		//
		if (!is_array($pa_additional_settings)) { $pa_additional_settings = array(); }
		$this->setSettingDefinitionsForRule($pa_additional_settings);
		
		if (is_array($pa_setting_values)) {
			$this->setSettings($pa_setting_values);
		}
	}
	# ------------------------------------------------------
	/**
	  * Sets setting definitions for to use for the current rule. Note that these definitions persist no matter what row is loaded
	  * (or even if no row is loaded). You can set the definitions once and reuse the instance for many rules. All will have the set definitions.
	  *
	  * @param $pa_additional_settings array Array of settings definitions
	  * @return bool Always returns true
	  */
	public function setSettingDefinitionsForRule($pa_additional_settings) {
		if (!is_array($pa_additional_settings)) { $pa_additional_settings = array(); }
		
		$standard_settings = [
			'label' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => "620px", 'height' => 1,
				'takesLocale' => true,
				'label' => _t('Rule display label'),
				'description' => _t('Short label for this rule, used for display in issue lists.')
			),
			'showasprompt' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_SELECT,
				'height' => 1,
				'default' => 0,
				'options' => [
					_t('Yes') => 1,
					_t('No') => 0,
				],
				'label' => _t('Show as prompt'),
				'description' => _t('Display violations of this rule as on-screen prompts.')
			),
			'violationMessage' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => "620px", 'height' => "35px",
				'takesLocale' => true,
				'label' => _t('Rule violation message'),
				'description' => _t('Message used for display to user when presenting issues.')
			),
			'description' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => "620px", 'height' => "35px",
				'takesLocale' => true,
				'label' => _t('Rule description'),
				'description' => _t('Long form description of rule, used for display to user when presenting issues.')
			)
		];
		$this->setAvailableSettings(array_merge($standard_settings, $pa_additional_settings));
		
		return true;
	}
	# ----------------------------------------
	/**
	 * Return all rules for all or selected bundles
	 *
	 * @param array $pa_options Options include:
	 *		db = Database connection to use. If omitted a new connection is created. [Default is null]
	 *		bundles = List of bundle name to return rules for. If omitted all rules for all bundles are returned. [Default is null]
	 *		table = Table to restrict entries to. If omitted rules for all tables are returned. [Default is null]
	 *
	 * @return array List of rules. Each rule is an array with rule data.
	 */
	static public function getRules($pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		$vs_sql = "
			SELECT cmdr.rule_id, cmdr.entry_id, cmde.bundle_name, cmde.settings entry_settings, cmde.table_num,
			cmdr.rule_code, cmdr.rule_level, cmdr.expression, cmdr.settings rule_settings
			FROM ca_metadata_dictionary_rules cmdr
			INNER JOIN ca_metadata_dictionary_entries AS cmde ON cmde.entry_id = cmdr.entry_id
		";
		
		$va_wheres = $va_params = array();
		if($va_bundles = caGetOption('bundles', $pa_options, null, array('castTo' => 'array'))) {
			$va_wheres[] = "(cmde.bundle_name IN (?))";
			$va_params[] = $va_bundles;
		}
		if(($table = caGetOption('table', $pa_options, null)) && $table_num = Datamodel::getTableNum($table)) {
			$va_wheres[] = "(cmde.table_num = ?)";
			$va_params[] = $table_num;
		}
		if (sizeof($va_wheres) > 0) { $vs_sql .= " WHERE ".join(" AND ", $va_wheres); }
		
		$qr_rules = $o_db->query($vs_sql, $va_params);
		
		$va_rules = $qr_rules->getAllRows();
		
		foreach($va_rules as $vn_i => $va_rule) {
			$va_rules[$vn_i]['entry_settings'] = unserialize(base64_decode($va_rule['entry_settings']));
			$va_rules[$vn_i]['rule_settings'] = unserialize(base64_decode($va_rule['rule_settings']));
		}
		
		return $va_rules;
	}
	# ----------------------------------------
}
