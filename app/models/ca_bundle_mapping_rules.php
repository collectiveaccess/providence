<?php
/** ---------------------------------------------------------------------
 * app/models/ca_bundle_mapping_rules.php
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
 
require_once(__CA_LIB_DIR__.'/core/ModelSettings.php'); 
	
BaseModel::$s_ca_models_definitions['ca_bundle_mapping_rules'] = array(
 	'NAME_SINGULAR' 	=> _t('mapping rule'),
 	'NAME_PLURAL' 		=> _t('mapping rules'),
 	'FIELDS'			=> array(
		'rule_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Rule id', 'DESCRIPTION' => 'Identifier for rule'
		),
		'group_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => 'Group id', 'DESCRIPTION' => 'Identifier for mapping group'
		),
		'ca_path_suffix' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess bundle'), 'DESCRIPTION' => _t('Name of CollectiveAccess bundle to map.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'external_path_suffix' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('External element'), 'DESCRIPTION' => _t('Destination in external format to map CollectiveAccess path to. The format of the external element  is determined by the target. For XML-based formats this will typically be an XPath specification; for delimited targets this will be a column number.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Rule settings')
		),
		'notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Notes'), 'DESCRIPTION' => _t('Notes and remarks relating to the mapping rule'),
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

class ca_bundle_mapping_rules extends BaseModel {
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
	protected $TABLE = 'ca_bundle_mapping_rules';
	      
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
	protected $LIST_FIELDS = array('ca_path', 'external_path');

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
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	# ----------------------------------------
	function __construct($pn_id=null) {
		global $_ca_bundle_mapping_rules_settings;
		parent::__construct($pn_id);
		
		$this->initSettings();
	}
	# ------------------------------------------------------
	protected function initSettings() {
		$va_settings = array(
			'split' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Split value on'),
				'description' => _t('Delimiter to use when splitting value into pieces. Should be blank if value is not to be split.')
			),
			'part' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Part to use'),
				'description' => _t('Part of split value to use, starting from 1 (eg. 1 is the first part, 2 is the second part, etc.). Should be blank if value is not to be split.')
			),
			'format' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 100, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Format'),
				'description' => _t('Custom format for output of this rule when exporting. Preface individual values with a caret (^) in the format string to embed values.')
			),
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
		
		$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);		
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
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
}
?>