<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_type_restrictions.php : table access class for table ca_metadata_type_restrictions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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

global $_ca_metadata_type_restriction_settings;
$_ca_metadata_type_restriction_settings = array(		// global
	'minAttributesPerRow' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => 0,
		'label' => _t('Minimum number of attributes of this kind that must be associated with an item'),
		'description' => _t('An error will occur if an attempt is made to save an item with fewer than this number of attributes of this type. Set to &gt; 1 to make this attribute effectively mandatory. Set to zero if you don\'t wish to set a limit.')
	),
	'maxAttributesPerRow' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => 65535,
		'label' => _t('Maximum number of attributes of this kind that can be associated with an item'),
		'description' => _t('An error will occur if an attempt is made to save an item with more than this number of attributes of this type. Set to &gt; 1 to make this attribute single-value. Otherwise set it to an appropriately high value.')
	),
	'minimumAttributeBundlesToDisplay' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => 0,
		'label' => _t('Minimum number of attribute bundles to show in an editing form.'),
		'description' => _t('The minimum number of attribute bundles to show in an editing form. If the number of actual attributes is less than this number then the user interface will show empty form bundles to reach this number. This number should be less than or equal to the maximum number of attributes per row.')
	)
);
 
	

BaseModel::$s_ca_models_definitions['ca_metadata_type_restrictions'] = array(
 	'NAME_SINGULAR' 	=> _t('metadata type restriction'),
 	'NAME_PLURAL' 		=> _t('metadata type restrictions'),
 	'FIELDS' 			=> array(
 		'restriction_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Restriction id', 'DESCRIPTION' => 'Identifier for Restriction'
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Bind attribute to'), 'DESCRIPTION' => _t('Type of item to bind element to.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('objects') => 57,
					_t('object lots') => 51,
					_t('entities') => 20,
					_t('places') => 72,
					_t('occurrences') => 67,
					_t('collections') => 13,
					_t('storage_locations') => 89,
					_t('loans') => 133,
					_t('movements') => 137,
					_t('object events') => 45,
					_t('object representations') => 56,
					_t('representation annotations') => 82,
					_t('object lot events') => 38,
					_t('sets') => 103,
					_t('set items') => 105,
					_t('lists') => 36,
					_t('list items') => 33,
					_t('search forms') => 121,
					_t('tours') => 153,
					_t('tour stops') => 155
				)
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Type of item to bind element to.')
		),
		'include_subtypes' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '0',
				'LABEL' => _t('Include subtypes in restriction?'), 'DESCRIPTION' => _t('???')
		),
		'element_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'DISPLAY_FIELD' => array('ca_metadata_elements.code'),
				'DISPLAY_ORDERBY' => array('ca_metadata_elements.code'),
				'LABEL' => 'Element id', 'DESCRIPTION' => 'Identifier for Element',
				'BOUNDS_VALUE' => array(0,65535)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Settings', 'DESCRIPTION' => 'Settings for type restriction'
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the restriction when displayed in a list with other restrictions. Lower numbers indicate higher priority.')
		)
 	)
);

class ca_metadata_type_restrictions extends BaseModel {
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
	protected $TABLE = 'ca_metadata_type_restrictions';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'restriction_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('restriction_id');

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
	protected $ORDER_BY = array('restriction_id');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

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
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
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
	/**
	 * 
	 */
	public function __construct($pn_id=null) {
		global $_ca_metadata_type_restriction_settings;
		parent::__construct($pn_id);	# call superclass constructor
		
		//
		$this->SETTINGS = new ModelSettings($this, 'settings', $_ca_metadata_type_restriction_settings);
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
	/**
	 * 
	 */
	public function getTypeListsForTables() {
		$va_tables = $this->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST');
		
		$va_types = array();
		foreach($va_tables as $vs_table_name => $vn_table_num) {
			$t_instance = $this->_DATAMODEL->getInstanceByTableNum($vn_table_num, true);
			$va_types[$vn_table_num] = array('' => '-');
			if (method_exists($t_instance, 'getTypeList')) {
				$va_items = $t_instance->getTypeList();
				foreach($va_items as $vn_item_id => $va_item) {
					$va_types[$vn_table_num][$vn_item_id] = $va_item['name_plural'];
				}
			}
		}
		return $va_types;
	}
	# ------------------------------------------------------
}
?>