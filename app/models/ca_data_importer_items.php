<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_importer_items.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__."/ca_data_importers.php");
require_once(__CA_MODELS_DIR__."/ca_data_importer_groups.php");
require_once(__CA_LIB_DIR__."/ca/Import/RefineryManager.php");

define("__CA_DATA_IMPORTER_DESTINATION_INTRINSIC__", 0);
define("__CA_DATA_IMPORTER_DESTINATION_ATTRIBUTE__", 1);
define("__CA_DATA_IMPORTER_DESTINATION_RELATED__", 2);
define("__CA_DATA_IMPORTER_DESTINATION_META__", 3);

BaseModel::$s_ca_models_definitions['ca_data_importer_items'] = array(
 	'NAME_SINGULAR' 	=> _t('data importer item'),
 	'NAME_PLURAL' 		=> _t('data importer items'),
	'FIELDS' 			=> array(
		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this importer item')
		),
		'importer_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Importer id', 'DESCRIPTION' => 'Identifier for importer'
		),
		'group_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => 'Group id', 'DESCRIPTION' => 'Identifier for importer group'
		),
		'source' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Data source'), 'DESCRIPTION' => _t('Source in external format to map CollectiveAccess path to. The format of the external element is determined by the target. For XML-based formats this will typically be an XPath specification; for delimited targets this will be a column number.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'destination' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => _t('External element'), 'DESCRIPTION' => _t('Name of CollectiveAccess bundle to map to.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Importer item settings')
		)
	)
);
	
class ca_data_importer_items extends BaseModel {
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
	protected $TABLE = 'ca_data_importer_items';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'item_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('item_id');

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
	protected $ORDER_BY = array('item_id');

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
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	# ------------------------------------------------------
	public function __construct($pn_id=null) {		
		parent::__construct($pn_id);
		
		$this->initSettings();
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		
		// TODO
	}
	# ------------------------------------------------------
	public function initSettings($pa_settings=null) {
		$va_settings = is_array($pa_settings) ? $pa_settings : array();
		
		$va_settings['refineries'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 6,
			'takesLocale' => false,
			'default' => '',
			'options' => ca_data_importer_items::getAvailableRefineries(),
			'label' => _t('Refineries'),
			'description' => _t('Select the refinery that preforms the correct function to alter your data source as it maps to CollectiveAccess.')
		);
		$va_settings['original_values'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Original values'),
			'description' => _t('Return-separated list of values from the data source to be replaced.  For example photo is used in the data source, but photograph is used in CollectiveAccess.')
		);
		$va_settings['replacement_values'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Replacement values'),
			'description' => _t('Return-separated list of CollectiveAccess list item idnos that correspond to the mapped values from the original data source.  For example sound recording (entered in the Original values column) maps to audio_digital, which is entered here in the Replacement values column.')
		);
		$va_settings['skipGroupIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Skip group if empty'),
			'description' => _t('Skip all of the elements in the group if value for this element is empty.  For example, a field called Description Type would be irrelevant if the Description field is empty.')
		);
		$va_settings['skipIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Skip mapping if empty'),
			'description' => _t('Skip mapping if value for this element is empty.')
		);
		$va_settings['skipGroupIfValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip group if value'),
			'description' => _t('Skip all of the elements in the group if value for this element is equal to the specified value(s).')
		);
		$va_settings['skipGroupIfNotValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip group if not value'),
			'description' => _t('Skip all of the elements in the group if value for this element is not equal to any of the specified values(s).')
		);
		$va_settings['skipRowIfEmpty'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Skip row if empty'),
			'description' => _t('Skip row if value for this element is empty.  For example, do not import the row if the Description field is empty.')
		);
		$va_settings['skipRowIfValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip row if value'),
			'description' => _t('Skip the row if value for this element is equal to the specified value(s).')
		);
		$va_settings['skipRowIfNotValue'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip row if value is not'),
			'description' => _t('Skip the row if value for this element is not equal to any of the specified value(s).')
		);
		$va_settings['skipGroupIfExpression'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 10,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Skip group if expression'),
			'description' => _t('Skip all of the elements in the group if value for the expression is true.')
		);
		$va_settings['default'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Default value'),
			'description' => _t('Value to use if data source value is blank.')
		);
		$va_settings['delimiter'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Delimiter'),
			'description' => _t('Delimiter to split repeating values on.')
		);
		$va_settings['restrictToTypes'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Restrict to types'),
			'description' => _t('Restricts the the mapping to only records of the designated type.  For example the Duration field is only applicable to objects of the type moving_image and not photograph.')
		);
		$va_settings['prefix'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Prefix'),
			'description' => _t('Text to prepend to value prior to import.')
		);
		$va_settings['suffix'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Suffix'),
			'description' => _t('Text to append to value prior to import.')
		);
		$va_settings['formatWithTemplate'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Format with template'),
			'description' => _t('Format imported value with provided template. Template may include caret (^) prefixed placeholders that refer to data source values.')
		);
		$va_settings['maxLength'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Maximum length'),
			'description' => _t('Truncate to specified length if value exceeds that length.')
		);
		$va_settings['errorPolicy'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('ignore') => "ignore",
				_t('stop') => "stop"
			),
			'label' => _t('Error policy'),
			'description' => _t('Determines how errors are handled for the mapping.  Options are to ignore the error, stop the import when an error is encountered and to receive a prompt when the error is encountered.')
		);
		$va_settings['relationshipType'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Relationship type'),
			'description' => _t('Relationship type to use when linking to a related record.')
		);
		$va_settings['convertNewlinesToHTML'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Convert newlines to HTML'),
			'description' => _t('Convert newline characters in text to HTML &lt;BR/&gt; tags.')
		);
		$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);
	}
	# ------------------------------------------------------
	public function getDestinationType() {
		$vo_dm = Datamodel::load();
		$vs_destination = $this->get("destination");
		
		$t_importer = new ca_data_importers($this->get("importer_id"));
		$t_instance = $vo_dm->getInstanceByTableNum($t_importer->get("table_num"));
		
		$va_split = explode(".",$vs_destination);
		
		switch(sizeof($va_split)){
			case 1:
				return __CA_DATA_IMPORTER_DESTINATION_RELATED__;
			case 2:
				if(trim($va_split[0])==$t_instance->tableName()){
					if($t_instance->hasField(trim($va_split[1]))){
						return __CA_DATA_IMPORTER_DESTINATION_INTRINSIC__;
					} else if($t_instance->isValidMetadataElement(trim($va_split[1]))){
						return __CA_DATA_IMPORTER_DESTINATION_ATTRIBUTE__;
					} else {
						return __CA_DATA_IMPORTER_DESTINATION_META__;
					}
				} else {
					return __CA_DATA_IMPORTER_DESTINATION_RELATED__;
				}
			case 3:
			default:
				return __CA_DATA_IMPORTER_DESTINATION_META__;
		}
		
	}
	# ------------------------------------------------------
	public function getImportItemsInGroup(){
		if(!$this->getPrimaryKey()) return false;
		
		if($this->get("group_id")){
			$t_group = new ca_data_importer_groups($this->get("group_id"));
			return $t_group->getItems();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 */
	public function __call($ps_name, $pa_arguments) {
		if (($ps_name == 'setSetting') && ($pa_arguments[0] == 'refineries')) {
			//
			// Load refinery-specific settings as refineries are selected
			//
			if(is_array($pa_arguments[1])) {
				$va_current_settings = $this->SETTINGS->getAvailableSettings();
				foreach($pa_arguments[1] as $vs_refinery) {
					if (is_array($va_refinery_settings = ca_data_importer_items::getRefinerySettings($vs_refinery))) {
						$va_current_settings += $va_refinery_settings;
					}
				}
				$this->SETTINGS->setAvailableSettings($va_current_settings);
			}
		}
		if (method_exists($this->SETTINGS, $ps_name)) {
			return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
		}
		die($this->tableName()." does not implement method {$ps_name}");
	}
	
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getAvailableRefineries() {
		$va_refinery_names = RefineryManager::getRefineryNames();
		
		$va_refinery_list = array();
		foreach($va_refinery_names as $vs_name) {
			$o_refinery = RefineryManager::getRefineryInstance($vs_name);
			$va_refinery_list[$vs_name] = $o_refinery->getTitle();
		}
		
		return $va_refinery_list;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getRefinerySettings($ps_refinery) {
		if ($o_refinery = RefineryManager::getRefineryInstance($ps_refinery)) {
			return $o_refinery->getRefinerySettings();
		}
		return null;
	}
	# ------------------------------------------------------
}
?>