<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_dictionary_entries.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_metadata_dictionary_rules.php');

global $_ca_metadata_dictionary_entry_settings;
$_ca_metadata_dictionary_entry_settings = array(		// global
	'label' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 30, 'height' => 1,
		'takesLocale' => true,
		'label' => _t('Label to place on entry'),
		'description' => _t('Custom label text to use for this entry.')
	),
	'definition' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 30, 'height' => 5,
		'takesLocale' => true,
		'label' => _t('Descriptive text for bundle.'),
		'description' => _t('Definition text to display for this bundle.')
	),
	'mandatory' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'width' => "10", 'height' => "1",
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Bundle is mandatory'),
		'description' => _t('Bundle is mandatory and a valid value must be set before it can be saved.')
	),
	'restrict_to_relationship_types' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 35, 'height' => 5,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Restrict to relationship types'),
		'description' => _t('Restricts entry to items related using the specified relationship type(s). Leave all unchecked for no restriction.')
	),
	'restrict_to_types' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 35, 'height' => 5,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Restrict to types'),
		'description' => _t('Restricts entry to items of the specified type(s). Leave all unchecked for no restriction.')
	),
	
);

BaseModel::$s_ca_models_definitions['ca_metadata_dictionary_entries'] = array(
 	'NAME_SINGULAR' 	=> _t('Metadata dictionary entry'),
 	'NAME_PLURAL' 		=> _t('Metadata dictionary entries'),
 	'FIELDS' 			=> array(
 		'entry_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Entry id', 'DESCRIPTION' => 'Identifier for metadata dictionary entry'
		),
		'bundle_name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Bundle name'), 'DESCRIPTION' => _t('Bundle name'),
				'BOUNDS_VALUE' => array(1,255)
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



class ca_metadata_dictionary_entries extends BaseModel {
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
	protected $TABLE = 'ca_metadata_dictionary_entries';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'entry_id';

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
	protected $ORDER_BY = array('entry_id');

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
	protected $LOG_CHANGES_TO_SELF = true;
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
	
	# ------------------------------------------------------
	function __construct($pn_id=null, $pa_additional_settings=null, $pa_setting_values=null) {
		parent::__construct($pn_id);
		
		//
		if (!is_array($pa_additional_settings)) { $pa_additional_settings = array(); }
		$this->setSettingDefinitionsForEntry($pa_additional_settings);
		
		if (is_array($pa_setting_values)) {
			$this->setSettings($pa_setting_values);
		}
	}
	# ------------------------------------------------------
	/**
	  * Sets setting definitions for to use for the current entry. Note that these definitions persist no matter what row is loaded
	  * (or even if no row is loaded). You can set the definitions once and reuse the instance for many entries. All will have the set definitions.
	  *
	  * @param $pa_additional_settings array Array of settings definitions
	  * @return bool Always returns true
	  */
	public function setSettingDefinitionsForEntry($pa_additional_settings) {
		if (!is_array($pa_additional_settings)) { $pa_additional_settings = array(); }
		global $_ca_metadata_dictionary_entry_settings;
		$this->SETTINGS = new ModelSettings($this, 'settings', array_merge($_ca_metadata_dictionary_entry_settings, $pa_additional_settings));
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 *
	 */
	public static function entryExists($ps_bundle_name, $pa_options=null) {
		if (($va_ids = ca_metadata_dictionary_entries::find(array('bundle_name' => $ps_bundle_name), array('returnAs' => 'ids'))) && is_array($va_ids) && sizeof($va_ids)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 *
	 */
	public static function getEntry($ps_bundle_name, $pa_settings=null, $pa_options=null) {
		if(!is_array($va_types = caGetOption('restrict_to_types', $pa_settings, null)) && $va_types) {
			$va_types = array($va_types);
		}
		
		if ($t_entry = ca_metadata_dictionary_entries::find(array('bundle_name' => $ps_bundle_name), array('returnAs' => 'firstModelInstance'))) {
			if(($vs_return = caGetOption('return', $pa_options, null)) && ($t_entry->hasField($vs_return))) {
				return $t_entry->get($vs_return);
			}
			return $t_entry;
		}
		return null;
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
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
?>