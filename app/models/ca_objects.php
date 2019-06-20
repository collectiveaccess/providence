<?php
/** ---------------------------------------------------------------------
 * app/models/ca_objects.php : table access class for table ca_objects
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/RepresentableBaseModel.php");
require_once(__CA_LIB_DIR__."/BaseObjectLocationModel.php");
require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_objects_x_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_loans_x_objects.php");
require_once(__CA_MODELS_DIR__."/ca_movements_x_objects.php");
require_once(__CA_MODELS_DIR__."/ca_objects_x_storage_locations.php");
require_once(__CA_MODELS_DIR__."/ca_object_checkouts.php");
require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");


BaseModel::$s_ca_models_definitions['ca_objects'] = array(
 	'NAME_SINGULAR' 	=> _t('object'),
 	'NAME_PLURAL' 		=> _t('objects'),
 	'FIELDS' 			=> array(
		'object_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this object')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Identifier of parent object; is null if object is root of hierarchy.'
		),
		'hier_object_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Object hierarchy', 'DESCRIPTION' => 'Identifier of object that is root of the object hierarchy.'
		),
		'lot_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'ALLOW_BUNDLE_ACCESS_CHECK' => true, 'DONT_ALLOW_IN_UI' => true,
				'DEFAULT' => '',
				'LABEL' => _t('Lot'), 'DESCRIPTION' => _t('Lot this object belongs to; is null if object is not part of a lot.')
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the object originates.')
		),
		'source_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'object_sources',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of object. This value is often used to indicate the administrative sub-division or legacy database from which the object originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LIST_CODE' => 'object_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the object. In CollectiveAccess every object has a single "instrinsic" type that determines the set of descriptive, technical and administrative metadata that can be applied to it. As such this type is "low-level" and directly tied to the form of the object - eg. photograph, book, analog video recording, etc.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Object identifier'), 'DESCRIPTION' => _t('A unique alphanumeric identifier for this object. This is usually equivalent to the "accession number" in museum settings.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Sortable object identifier', 'DESCRIPTION' => 'Value used for sorting objects on identifier value.',
				'BOUNDS_LENGTH' => array(0,255)
		),
		'is_deaccessioned' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'OPTIONS' => array(
					_t('Yes') => 1,
					_t('No') => 0
				),
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'DONT_ALLOW_IN_UI' => true,
				'RESULTS_EDITOR_BUNDLE' => 'ca_objects_deaccession',	// bundle to use when editing this in the search/browse "results" editing interface
				'LABEL' => _t('Is deaccessioned'), 'DESCRIPTION' => _t('Check if object is deaccessioned')
		),
		'deaccession_date' => array(
				'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'DONT_ALLOW_IN_UI' => true,
				'START' => 'deaccession_sdatetime', 'END' => 'deaccession_edatetime',
				'LABEL' => _t('Date of deaccession'), 'DESCRIPTION' => _t('Enter the date the object was deaccessioned.')
		),
		'deaccession_disposal_date' => array(
				'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'DONT_ALLOW_IN_UI' => true,
				'START' => 'deaccession_disposal_sdatetime', 'END' => 'deaccession_disposal_edatetime',
				'LABEL' => _t('Date of disposal'), 'DESCRIPTION' => _t('Enter the date the deaccessioned object was disposed of.')
		),
		'deaccession_notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => "700px", 'DISPLAY_HEIGHT' => 6,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => _t('Deaccession notes'), 'DESCRIPTION' => _t('Justification for deaccession.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'deaccession_type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'DONT_ALLOW_IN_UI' => true,
				'LIST_CODE' => 'object_deaccession_types',
				'LABEL' => _t('Deaccession type'), 'DESCRIPTION' => _t('Indicates type of deaccession.')
		),
		'current_loc_class' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DONT_ALLOW_IN_UI' => true,
				'BOUNDS_CHOICE_LIST' => array(
					_t('occurrences') => 67,
					_t('storage locations') => 119,	// we store the ca_objects_x_storage_locations relation_id for locations
					_t('loans') => 133,
					_t('movements') => 137,
					_t('object lots') => 51
				),
				'LABEL' => _t('Current location class'), 'DESCRIPTION' => _t('Indicates classification of last location for objects (eg. storage location, occurrence, loan, movement)')
		),
		'current_loc_subclass' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => _t('Current location sub-class'), 'DESCRIPTION' => _t('Indicates sub-classification of last location for objects (eg. storage location relationship type, occurrence type, loan type, movement)')
		),
		'current_loc_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DONT_ALLOW_IN_UI' => true,
				'LABEL' => _t('Current location'), 'DESCRIPTION' => _t('Reference to record recording details of current object location.')
		),
		'item_status_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LIST_CODE' => 'object_statuses',
				'LABEL' => _t('Accession status'), 'DESCRIPTION' => _t('Indicates accession/collection status of object. (eg. accessioned, pending accession, loan, non-accessioned item, etc.)')
		),
		'acquisition_type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'object_acq_types',
				'LABEL' => _t('Acquisition method'), 'DESCRIPTION' => _t('Indicates method employed to acquire the object.')
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for object information retrieved via web services [NOT IMPLEMENTED YET].'
		),
		'hier_left' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - left bound', 'DESCRIPTION' => 'Left-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		),
		'hier_right' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - right bound', 'DESCRIPTION' => 'Right-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		),
		'extent' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Extent'), 'DESCRIPTION' => _t('The extent of the object. This is typically the number of discrete items that compose the object represented by this record. It is stored as a whole number (eg. 1, 2, 3...).')
		),
		'extent_units' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Extent units'), 'DESCRIPTION' => _t('Units of extent value. (eg. pieces, items, components, reels, etc.)'),
				'BOUNDS_LENGTH' => array(0,255)
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if object is accessible to the public or not.')
		),
		'status' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Newly created') => 0,
					_t('Editing in progress') => 1,
					_t('Editing complete - pending review') => 2,
					_t('Review in progress') => 3,
					_t('Completed') => 4
				),
				'LIST' => 'workflow_statuses',
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the object record.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the object is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		),
		'acl_inherit_from_ca_collections' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Do not inherit access settings from related collections') => 0,
					_t('Inherit access settings from related collections') => 1
				),
				'LABEL' => _t('Inherit access settings from collections?'), 'DESCRIPTION' => _t('Determines whether access settings set for related collections are applied to this object.')
		),
		'acl_inherit_from_parent' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'ALLOW_BUNDLE_ACCESS_CHECK' => false,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Do not inherit item-level access control settings from parent') => 0,
					_t('Inherit item-level access control settings from parent') => 1
				),
				'LABEL' => _t('Inherit item-level access control settings from parent?'), 'DESCRIPTION' => _t('Determines whether item-level access control settings set for parent object is applied to this object.')
		),
		'access_inherit_from_parent' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'ALLOW_BUNDLE_ACCESS_CHECK' => false,
				'DONT_ALLOW_IN_UI' => true,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Do not inherit access settings from parent') => 0,
					_t('Inherit access settings from parent') => 1
				),
				'LABEL' => _t('Inherit access settings from parent?'), 'DESCRIPTION' => _t('Determines whether front-end access settings set for parent object is applied to this object.')
		),
		'view_count' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'View count', 'DESCRIPTION' => 'Number of views for this record.'
		),
		'circulation_status_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LIST_CODE' => 'object_circulation_statuses',
			'LABEL' => _t('Circulation status'), 'DESCRIPTION' => _t('Indicates circulation status of the object.')
		),
	)
);

class ca_objects extends BaseObjectLocationModel implements IBundleProvider {
	# ------------------------------------------------------
	# --- Object attribute properties
	# ------------------------------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_objects';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'object_id';

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
	protected $ORDER_BY = array('idno');

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
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_ADHOC_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_objects';
	protected $HIERARCHY_ID_FLD				=	'hier_object_id';
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
	protected $LABEL_TABLE_NAME = 'ca_object_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'object_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Sources
	# ------------------------------------------------------
	protected $SOURCE_ID_FLD = 'source_id';				// name of source field for this table
	protected $SOURCE_LIST_CODE = 'object_sources';		// list code (ca_lists.list_code) of list defining sources for this table
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_objects_x_objects';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ObjectSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ObjectSearchResult';
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Cache for current location type configuration data
	 *
	 * @see ca_objects::getConfigurationForCurrentLocationType()
	 */
	static $s_current_location_type_configuration_cache = array();
	
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
		if (
			!is_null(BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['acl_inherit_from_parent']['DEFAULT'])
			||
			!is_null(BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['acl_inherit_from_ca_collections']['DEFAULT'])
		) {
			$o_config = Configuration::load();
		
			if (!is_null(BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['acl_inherit_from_parent']['DEFAULT'])) {
				BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['acl_inherit_from_parent']['DEFAULT'] = (int)$o_config->get('ca_objects_acl_inherit_from_parent_default');
			}
			if (!is_null(BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['acl_inherit_from_ca_collections']['DEFAULT'])) {
				BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['acl_inherit_from_ca_collections']['DEFAULT'] = (int)$o_config->get('ca_objects_acl_inherit_from_ca_collections_default');
			}
		}
		parent::__construct($pn_id);
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_object_representations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Media representations'));
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_objects_table'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_objects_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_object_representations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object representations list'));
		$this->BUNDLES['ca_entities_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities list'));
		$this->BUNDLES['ca_places_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places list'));
		$this->BUNDLES['ca_occurrences_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences list'));
		$this->BUNDLES['ca_collections_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections list'));
		$this->BUNDLES['ca_list_items_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related list items list'));
		$this->BUNDLES['ca_storage_locations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations list'));
		$this->BUNDLES['ca_loans_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans list'));
		$this->BUNDLES['ca_movements_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements list'));
		$this->BUNDLES['ca_object_lots_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object lots list'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		
		$this->BUNDLES['ca_tour_stops'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related tour stops'));
		
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lot'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related sets'));
		$this->BUNDLES['ca_sets_checklist'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['ca_item_tags'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Tags'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
		
		$this->BUNDLES['ca_objects_components_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Components'));
		$this->BUNDLES['ca_objects_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current location'));
		$this->BUNDLES['ca_objects_location_date'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current location date'));
		
		$this->BUNDLES['ca_objects_history'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Object use history'));
		$this->BUNDLES['ca_objects_deaccession'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Deaccession status'));
		$this->BUNDLES['ca_object_checkouts'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Object checkouts'));
		
		$this->BUNDLES['ca_object_representations_access_status'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Media representation access and status'));
	
		$this->BUNDLES['authority_references_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('References'));

		$this->BUNDLES['ca_object_circulation_status'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Circulation Status'));
	}
	# ------------------------------------------------------
	/**
	 * Override insert() to add child records to the same lot as parent
	 */ 
	public function insert($pa_options=null) {
		if ($vn_parent_id = $this->get('parent_id')) {
			if(!$this->getAppConfig()->get(['ca_objects_dont_inherit_lot_from_parent', 'ca_objects_dont_inherit_lot_id_from_parent'])) {
				$t_parent = new ca_objects();
				if ($this->inTransaction()) {
					$t_parent->setTransaction($this->getTransaction());
				}

				if ($t_parent->load($vn_parent_id) && ($vn_lot_id = $t_parent->get('lot_id'))) {
					$this->set('lot_id', $vn_lot_id);
				}
			}
		}
		return parent::insert($pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Override update() to set child records to the same lot as parent
	 */ 
	public function update($pa_options=null) {
		if ($vn_parent_id = $this->get('parent_id')) {
			if(!$this->getAppConfig()->get(['ca_objects_dont_inherit_lot_from_parent', 'ca_objects_dont_inherit_lot_id_from_parent'])) {
				$t_parent = new ca_objects();
				if ($this->inTransaction()) { $t_parent->setTransaction($this->getTransaction()); }
				if ($t_parent->load($vn_parent_id) && ($vn_lot_id = $t_parent->get('lot_id'))) {
					$this->set('lot_id', $vn_lot_id);
				}
			}
		}
		return parent::update($pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Override set() to do idno_stub lookups on lots
	 *
	 * @param mixed $pm_fields
	 * @param mixed $pm_value
	 * @param array $pa_options Most options are handled by subclasses. Options defined here include:
	 *		assumeIdnoStubForLotID = set to force lookup of lot_id values as ca_object_lots.idno_stub values first not matter what, before consideration as a numeric lot_id. The default is false, in which case integer values are considered lot_ids and non-numeric values possible idno_stubs.
	 *		
	 * @return int 
	 */
	public function set($pm_fields, $pm_value="", $pa_options=null) {
		if (!is_array($pm_fields)) {
			$pm_fields = array($pm_fields => $pm_value);
		}
		$pb_assume_idno_stub_for_lot_id = caGetOption('assumeIdnoStubForLotID', $pa_options, false);
		foreach($pm_fields as $vs_fld => $vs_val) {
			if (($vs_fld == 'lot_id') && ($pb_assume_idno_stub_for_lot_id || preg_match("![^\d]+!", $vs_val))) {
				$t_lot = new ca_object_lots();
				if ($this->inTransaction()) { $t_lot->setTransaction($this->getTransaction()); }
				if ($t_lot->load(array('idno_stub' => $vs_val, 'deleted' => 0))) {
					$vn_lot_id = (int)$t_lot->getPrimaryKey();
					$pm_fields[$vs_fld] = $vn_lot_id;
				} else {
					return false;
				}
			}
		}
		return parent::set($pm_fields, null, $pa_options);
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null){
		// nuke related representations
		$va_representations = $this->getRepresentations();
		if (is_array($va_representations)) {
			foreach ($va_representations as $va_rep){
				// check if representation is in use anywhere else
				$qr_res = $this->getDb()->query("SELECT count(*) c FROM ca_objects_x_object_representations WHERE object_id <> ? AND representation_id = ?", (int)$this->getPrimaryKey(), (int)$va_rep["representation_id"]);
				if ($qr_res->nextRow() && ($qr_res->get('c') == 0)) {
					$this->removeRepresentation($va_rep["representation_id"], array('dontCheckPrimaryValue' => true));
				}
			}
		}
		return parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
	}
	# ------------------------------------------------------
	/**
	 * @param array $pa_options
	 *		duplicate_media
	 */
	public function duplicate($pa_options=null) {
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction($o_t = new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			$o_t = $this->getTransaction();
		}
		
		if ($t_dupe = parent::duplicate($pa_options)) {
			$vb_duplicate_media = isset($pa_options['duplicate_media']) && $pa_options['duplicate_media'];
		
			if ($vb_duplicate_media) { 
				// Try to link representations
				$o_db = $this->getDb();
				
				$qr_res = $o_db->query("
					SELECT *
					FROM ca_objects_x_object_representations
					WHERE object_id = ?
				", (int)$this->getPrimaryKey());
				
				$va_reps = array();
				while($qr_res->nextRow()) {
					$va_reps[$qr_res->get('representation_id')] = $qr_res->getRow();
				}
				
				$t_object_x_rep = new ca_objects_x_object_representations();
				$t_object_x_rep->setTransaction($o_t);
				foreach($va_reps as $vn_representation_id => $va_rep) {
					$t_object_x_rep->setMode(ACCESS_WRITE);
					$va_rep['object_id'] = $t_dupe->getPrimaryKey();
					$t_object_x_rep->set($va_rep);
					$t_object_x_rep->insert();
					
					if ($t_object_x_rep->numErrors()) {
						$this->errors = $t_object_x_rep->errors;
						if ($vb_we_set_transaction) { $o_t->rollback();}
						return false;
					}
				}
			}
		} else {
			if ($vb_we_set_transaction) { $o_t->rollback(); }
			return false;
		}
		
		
		if ($vb_we_set_transaction) { $o_t->commit();}
		return $t_dupe;
	}
 	# ------------------------------------------------------
 	# HTML form bundles
	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle for object deaccession information
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getObjectDeaccessionHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
		
		
		return $o_view->render('ca_objects_deaccession.php');
	}
	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle for object checkout information
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getObjectCheckoutsHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
		
		$o_view->setVar('checkout_history', $va_history = $this->getCheckoutHistory());
		$o_view->setVar('checkout_count', sizeof($va_history));
		$o_view->setVar('client_list', $va_client_list = array_unique(caExtractValuesFromArrayList($va_history, 'user_name')));
		$o_view->setVar('client_count', sizeof($va_client_list));
		
		
		return $o_view->render('ca_object_checkouts.php');
	}
	# ------------------------------------------------------
 	# Object location tracking
 	# ------------------------------------------------------
 	/**
 	 * Returns HTML form bundle for location tracking
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options Array of options. Options include:
	 *			None yet.
	 *
	 * @return string Rendered HTML bundle
 	 */
 	public function getObjectLocationHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
 		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		if (is_array($vs_display_template = caGetOption('displayTemplate', $pa_bundle_settings, _t('No template defined')))) {
		    $vs_display_template = caExtractSettingValueByLocale($pa_bundle_settings, 'displayTemplate', $g_ui_locale);
		}
		if (is_array($vs_history_template = caGetOption('historyTemplate', $pa_bundle_settings, $vs_display_template))) {
		     $vs_history_template = caExtractSettingValueByLocale($pa_bundle_settings, 'historyTemplate', $g_ui_locale);
		}
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
		$o_view->setVar('t_subject', $this);
		
		$o_view->setVar('mode', $vs_mode = caGetOption('locationTrackingMode', $pa_bundle_settings, 'ca_storage_locations'));
		
		switch($vs_mode) {
			case 'ca_storage_locations':
				$t_last_location = $this->getLastLocation(array());
				
				if (!$vs_display_template) { $vs_display_template = "<unit relativeTo='ca_storage_locations'><l>^ca_storage_locations.hierarchy.preferred_labels.name%delimiter=_➜_</l></unit> (^ca_objects_x_storage_locations.effective_date)"; }
				$o_view->setVar('current_location', $t_last_location ? $t_last_location->getWithTemplate($vs_display_template) : null);
				
				if (!$vs_history_template) { $vs_history_template = $vs_display_template; }
				$o_view->setVar('location_history', $this->getLocationHistory(array('template' => $vs_history_template)));
				
				$o_view->setVar('location_relationship_type', $this->getAppConfig()->get('object_storage_location_tracking_relationship_type'));
				$o_view->setVar('location_change_url',  null);
				break;
			case 'ca_movements':
			default:
				$t_last_movement = $this->getLastMovement(array('dateElement' => $vs_movement_date_element = $this->getAppConfig()->get('movement_storage_location_date_element')));
				
				if (!$vs_display_template) { $vs_display_template = "<l>^ca_storage_locations.hierarchy.preferred_labels.name%delimiter=_➜_</l> (^ca_movements.{$vs_movement_date_element})"; }
				$o_view->setVar('current_location', $t_last_movement ? $t_last_movement->getWithTemplate($vs_display_template) : null);
				
				if (!$vs_history_template) { $vs_history_template = $vs_display_template; }
				$o_view->setVar('location_history', $this->getMovementHistory(array('dateElement' => $vs_movement_date_element, 'template' => $vs_history_template)));
				
				$o_view->setVar('location_relationship_type', $this->getAppConfig()->get('movement_object_tracking_relationship_type'));
				$o_view->setVar('location_change_url', caNavUrl($po_request, 'editor/movements', 'MovementQuickAdd', 'Form', array('movement_id' => 0)));
				break;
		}

		return $o_view->render('ca_objects_location.php');
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return array with list of significant events in object life cycle as configured for 
 	 * a ca_objects_history editor bundle.
	 *
	 * @param array $pa_bundle_settings The settings for a ca_objects_history editing BUNDLES
	 * @param array $pa_options Array of options. Options include:
	 *		noCache = Don't use any cached history data. [Default is false]
	 *		currentOnly = Only return history entries dates that include the current date. [Default is false]
	 *		limit = Only return a maximum number of history entries. [Default is null; no limit]
	 *      showChildHistory = [Default is false]
	 *
	 * @return array A list of life cycle events, indexed by historic timestamp for date of occurrrence. Each list value is an array of history entries.
	 *
	 * @used-by ca_objects::getObjectHistoryHTMLFormBundle
 	 */
 	public function getObjectHistory($pa_bundle_settings=null, $pa_options=null) {
 		global $g_ui_locale;
 		
		if(!is_array($pa_options)) { $pa_options = array(); }
		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }

		$pa_bundle_settings = $this->_processObjectHistoryBundleSettings($pa_bundle_settings);
		$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_bundle_settings, $pa_options, array('object_id' => $this->getPrimaryKey())));
		
		$pb_no_cache 				= caGetOption('noCache', $pa_options, false);
		//if (!$pb_no_cache && ExternalCache::contains($vs_cache_key, "objectHistory")) { return ExternalCache::fetch($vs_cache_key, "objectHistory"); }
		
		$pb_display_label_only 		= caGetOption('displayLabelOnly', $pa_options, false);
		
		$pb_get_current_only 		= caGetOption('currentOnly', $pa_options, false);
		$pn_limit 					= caGetOption('limit', $pa_options, null);
		
		$vs_display_template		= caGetOption('display_template', $pa_bundle_settings, _t('No template defined'));
		$vs_history_template		= caGetOption('history_template', $pa_bundle_settings, $vs_display_template);
		
		$pb_show_child_history 		= caGetOption('showChildHistory', $pa_options, false);
		
		$vn_current_date = TimeExpressionParser::now();

		$o_media_coder = new MediaInfoCoder();
		
		$object_id = $this->getPrimaryKey();
				
//
// Get history
//
		$va_history = [];
		
		// Lots
		if(is_array($va_lot_types = caGetOption('ca_object_lots_showTypes', $pa_bundle_settings, null)) && ($vn_lot_id = $this->get('lot_id'))) {
			require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
			
			$lot_ids = [$vn_lot_id];
			if(caGetOption('ca_object_lots_includeFromChildren', $pa_bundle_settings, false)) {
                $va_child_lots = $this->get('ca_object_lots.lot_id', ['returnAsArray' => true]);
                if ($pb_show_child_history) { $va_child_lots = array_merge($lot_ids, $va_child_lots); }
            }
			
			foreach($lot_ids as $vn_lot_id) {
                $t_lot = new ca_object_lots($vn_lot_id);
                if (!$t_lot->get('ca_object_lots.deleted')) {
                    $va_lot_type_info = $t_lot->getTypeList(); 
                    $vn_type_id = $t_lot->get('ca_object_lots.type_id');
            
                    $vs_color = $va_lot_type_info[$vn_type_id]['color'];
                    if (!$vs_color || ($vs_color == '000000')) {
                        $vs_color = caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
                    }
                    $vs_color = str_replace("#", "", $vs_color);
            
                    $va_dates = array();
                
                    $va_date_elements = caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null);
                   
                    if (!is_array($va_date_elements) && $va_date_elements) { $va_date_elements = array($va_date_elements); }
            
                    if (is_array($va_date_elements) && sizeof($va_date_elements)) {
                        foreach($va_date_elements as $vs_date_element) {
                            $va_date_bits = explode('.', $vs_date_element);
                            $vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_object_lots.{$vs_date_element}";
                            $va_dates[] = array(
                                'sortable' => $t_lot->get($vs_date_spec, array('sortable' => true)),
                                'bounds' => explode("/", $t_lot->get($vs_date_spec, array('sortable' => true))),
                                'display' => $t_lot->get($vs_date_spec)
                            );
                        }
                    }
                    if (!sizeof($va_dates)) {
                        $va_dates[] = array(
                            'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($t_lot->getCreationTimestamp(null, array('timestampOnly' => true))),
                            'bounds' => array(0, $vn_date),
                            'display' => caGetLocalizedDate($vn_date)
                        );
                    }
            
                    foreach($va_dates as $va_date) {
                        if (!$va_date['sortable']) { continue; }
                        if (!in_array($vn_type_id, $va_lot_types)) { continue; }
                        if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
                
                
                        $vs_default_display_template = '^ca_object_lots.preferred_labels.name (^ca_object_lots.idno_stub)';
                        $vs_display_template = $pb_display_label_only ? "" : caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
                
                        $o_media_coder->setMedia($va_lot_type_info[$vn_type_id]['icon']);
                        if(!is_array($va_history[$va_date['sortable']])) { $va_history[$va_date['sortable']] = []; }
                        array_unshift($va_history[$va_date['sortable']], array(
                            'type' => 'ca_object_lots',
                            'id' => $vn_lot_id,
                            'display' => $t_lot->getWithTemplate($vs_display_template),
                            'color' => $vs_color,
                            'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
                            'typename_singular' => $vs_typename = $va_lot_type_info[$vn_type_id]['name_singular'],
                            'typename_plural' => $va_lot_type_info[$vn_type_id]['name_plural'],
                            'type_id' => $vn_type_id,
                            'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
                            'date' => $va_date['display']
                        ));
                    }
                }
            }
		}
		
		// Loans
		$va_loans = $this->get('ca_loans_x_objects.relation_id', array('returnAsArray' => true));
		$va_child_loans = [];
	    if(caGetOption('ca_loans_includeFromChildren', $pa_bundle_settings, false)) {
            $va_child_loans = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_loans_x_objects.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
            if ($pb_show_child_history) { $va_loans = array_merge($va_loans, $va_child_loans); }
		}
		if(is_array($va_loan_types = caGetOption('ca_loans_showTypes', $pa_bundle_settings, null)) && is_array($va_loans)) {	
			$qr_loans = caMakeSearchResult('ca_loans_x_objects', $va_loans);
			require_once(__CA_MODELS_DIR__."/ca_loans.php");
			$t_loan = new ca_loans();
			$va_loan_type_info = $t_loan->getTypeList(); 
			
			$va_date_elements_by_type = array();
			foreach($va_loan_types as $vn_type_id) {
				if (!is_array($va_date_elements = caGetOption("ca_loans_{$va_loan_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
					$va_date_elements = array($va_date_elements);
				}
				if (!$va_date_elements) { continue; }
				$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
			}
		
			while($qr_loans->nextHit()) {
				$vn_rel_object_id = $qr_loans->get('ca_loans_x_objects.object_id');
				$vn_loan_id = $qr_loans->get('ca_loans.loan_id');
				if ((string)$qr_loans->get('ca_loans.deleted') !== '0') { continue; }	// filter out deleted
				$vn_type_id = $qr_loans->get('ca_loans.type_id');
				
				$va_dates = array();
				if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
					foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
						$va_date_bits = explode('.', $vs_date_element);
						$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_loans.{$vs_date_element}";
						$va_dates[] = array(
							'sortable' => $qr_loans->get($vs_date_spec, array('sortable' => true)),
							'bounds' => explode("/", $qr_loans->get($vs_date_spec, array('sortable' => true))),
							'display' => $qr_loans->get($vs_date_spec)
						);
					}
				}
				if (!sizeof($va_dates)) {
					$va_dates[] = array(
						'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_loans->get('lastModified.direct')),
						'bounds' => array(0, $vn_date),
						'display' => caGetLocalizedDate($vn_date)
					);
				}
				
				$vs_default_display_template = '^ca_loans.preferred_labels.name (^ca_loans.idno)';
				$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_loans_{$va_loan_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
		
				foreach($va_dates as $va_date) {
					if (!$va_date['sortable']) { continue; }
					if (!in_array($vn_type_id, $va_loan_types)) { continue; }
					if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date))) { continue; }
					
					$vs_color = $va_loan_type_info[$vn_type_id]['color'];
					if (!$vs_color || ($vs_color == '000000')) {
						$vs_color = caGetOption("ca_loans_{$va_loan_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
					}
					$vs_color = str_replace("#", "", $vs_color);
					
					$o_media_coder->setMedia($va_loan_type_info[$vn_type_id]['icon']);
					
					if(!is_array($va_history[$va_date['sortable']])) { $va_history[$va_date['sortable']] = []; }
					array_unshift($va_history[$va_date['sortable']], array(
						'type' => 'ca_loans',
						'id' => $vn_loan_id,
						'display' => $qr_loans->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
						'color' => $vs_color,
						'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
						'typename_singular' => $vs_typename = $va_loan_type_info[$vn_type_id]['name_singular'],
						'typename_plural' => $va_loan_type_info[$vn_type_id]['name_plural'],
						'type_id' => $vn_type_id,
						'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
						'date' => $va_date['display'],
						'hasChildren' => sizeof($va_child_loans) ? 1 : 0
					));
				}
			}
		}
		
		// Movements
		$va_movements = $this->get('ca_movements_x_objects.relation_id', array('returnAsArray' => true));
		$va_child_movements = [];
	    if(caGetOption('ca_movements_includeFromChildren', $pa_bundle_settings, false)) {
            $va_child_movements = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_movements_x_objects.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
            if ($pb_show_child_history) { $va_movements = array_merge($va_movements, $va_child_movements); }
		}
		if(is_array($va_movement_types = caGetOption('ca_movements_showTypes', $pa_bundle_settings, null)) && is_array($va_movements)) {	
			$qr_movements = caMakeSearchResult('ca_movements_x_objects', $va_movements);
			require_once(__CA_MODELS_DIR__."/ca_movements.php");
			$t_movement = new ca_movements();
			$va_movement_type_info = $t_movement->getTypeList(); 
			
			$va_date_elements_by_type = array();
			foreach($va_movement_types as $vn_type_id) {
				if (!is_array($va_date_elements = caGetOption("ca_movements_{$va_movement_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
					$va_date_elements = array($va_date_elements);
				}
				if (!$va_date_elements) { continue; }
				$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
			}
			
			while($qr_movements->nextHit()) {
				$vn_rel_object_id = $qr_movements->get('ca_movements_x_objects.object_id');
				$vn_movement_id = $qr_movements->get('ca_movements.movement_id');
				if ((string)$qr_movements->get('ca_movements.deleted') !== '0') { continue; }	// filter out deleted
				$vn_type_id = $qr_movements->get('ca_movements.type_id');
				
				$va_dates = array();
				if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
					foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
						$va_date_bits = explode('.', $vs_date_element);
						$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_movements.{$vs_date_element}";
						$va_dates[] = array(
							'sortable' => $qr_movements->get($vs_date_spec, array('sortable' => true)),
							'bounds' => explode("/", $qr_movements->get($vs_date_spec, array('sortable' => true))),
							'display' => $qr_movements->get($vs_date_spec)
						);
					}
				}
				if (!sizeof($va_dates)) {
					$va_dates[] = array(
						'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_movements->get('lastModified.direct')),
						'bound' => array(0, $vn_date),
						'display' => caGetLocalizedDate($vn_date)
					);
				}
		
				$vs_default_display_template = '^ca_movements.preferred_labels.name (^ca_movements.idno)';
				$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_movements_{$va_movement_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
				
				foreach($va_dates as $va_date) {
					if (!$va_date['sortable']) { continue; }
					if (!in_array($vn_type_id, $va_movement_types)) { continue; }
					//if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
					if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date))) { continue; }
					
					$vs_color = $va_movement_type_info[$vn_type_id]['color'];
					if (!$vs_color || ($vs_color == '000000')) {
						$vs_color = caGetOption("ca_movements_{$va_movement_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
					}
					$vs_color = str_replace("#", "", $vs_color);
					
					$o_media_coder->setMedia($va_movement_type_info[$vn_type_id]['icon']);
					
					if(!is_array($va_history[$va_date['sortable']])) { $va_history[$va_date['sortable']] = []; }
					array_unshift($va_history[$va_date['sortable']], array(
						'type' => 'ca_movements',
						'id' => $vn_movement_id,
						'display' => $qr_movements->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
						'color' => $vs_color,
						'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
						'typename_singular' => $vs_typename = $va_movement_type_info[$vn_type_id]['name_singular'],
						'typename_plural' => $va_movement_type_info[$vn_type_id]['name_plural'],
						'type_id' => $vn_type_id,
						'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
						'date' => $va_date['display'],
						'hasChildren' => sizeof($va_child_movements) ? 1 : 0
					));
				}
			}
		}
		
		
		// Occurrences
		$va_occurrences = $this->get('ca_objects_x_occurrences.relation_id', array('returnAsArray' => true));
	    $va_child_occurrences = [];
		if(is_array($va_occurrence_types = caGetOption('ca_occurrences_showTypes', $pa_bundle_settings, null)) && is_array($va_occurrences)) {	
			require_once(__CA_MODELS_DIR__."/ca_occurrences.php");
			$t_occurrence = new ca_occurrences();
			$va_occurrence_type_info = $t_occurrence->getTypeList(); 
			
			foreach($va_occurrence_types as $vn_type_id) {
                if(caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_includeFromChildren", $pa_bundle_settings, false)) {
                    $va_child_occurrences = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_objects_x_occurrences.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
                    if ($pb_show_child_history) { $va_occurrences = array_merge($va_occurrences, $va_child_occurrences); }
                }
            }
			
			$qr_occurrences = caMakeSearchResult('ca_objects_x_occurrences', $va_occurrences);
			
			$va_date_elements_by_type = array();
			foreach($va_occurrence_types as $vn_type_id) {
				if (!is_array($va_date_elements = caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
					$va_date_elements = array($va_date_elements);
				}
				if (!$va_date_elements) { continue; }
				$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
			}
			
			while($qr_occurrences->nextHit()) {
				$vn_rel_object_id = $qr_occurrences->get('ca_objects_x_occurrences.object_id');
				$vn_occurrence_id = $qr_occurrences->get('ca_occurrences.occurrence_id');
				if ((string)$qr_occurrences->get('ca_occurrences.deleted') !== '0') { continue; }	// filter out deleted
				$vn_type_id = $qr_occurrences->get('ca_occurrences.type_id');
				$vs_type_idno = $va_occurrence_type_info[$vn_type_id]['idno'];
				
				$va_dates = array();
				if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
					foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
						$va_date_bits = explode('.', $vs_date_element);	
						$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_occurrences.{$vs_date_element}";
						$va_dates[] = array(
							'sortable' => $qr_occurrences->get($vs_date_spec, array('sortable' => true)),
							'bounds' => explode("/", $qr_occurrences->get($vs_date_spec, array('sortable' => true))),
							'display' => $qr_occurrences->get($vs_date_spec)
						);
					}
				}
				if (!sizeof($va_dates)) {
					$va_dates[] = array(
						'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_occurrences->get('lastModified.direct')),
						'bounds' => array(0, $vn_date),
						'display' => caGetLocalizedDate($vn_date)
					);
				}
				
				$vs_default_display_template = '^ca_occurrences.preferred_labels.name (^ca_occurrences.idno)';
				$vs_default_child_display_template = '^ca_occurrences.preferred_labels.name (^ca_occurrences.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
				$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_occurrences_{$vs_type_idno}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
		        $vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(["ca_occurrences_{$vs_type_idno}_childDisplayTemplate", "ca_occurrences_{$vs_type_idno}_childTemplate"], $pa_bundle_settings, $vs_display_template);
		       
				foreach($va_dates as $va_date) {
					if (!$va_date['sortable']) { continue; }
					if (!in_array($vn_type_id, $va_occurrence_types)) { continue; }
					if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
					
					$vs_color = $va_occurrence_type_info[$vn_type_id]['color'];
					if (!$vs_color || ($vs_color == '000000')) {
						$vs_color = caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
					}
					$vs_color = str_replace("#", "", $vs_color);
					
					$o_media_coder->setMedia($va_occurrence_type_info[$vn_type_id]['icon']);
					
					if(!is_array($va_history[$va_date['sortable']])) { $va_history[$va_date['sortable']] = []; }
					array_unshift($va_history[$va_date['sortable']], array(
						'type' => 'ca_occurrences',
						'id' => $vn_occurrence_id,
						'display' => $qr_occurrences->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
						'color' => $vs_color,
						'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
						'typename_singular' => $vs_typename = $va_occurrence_type_info[$vn_type_id]['name_singular'],
						'typename_plural' => $va_occurrence_type_info[$vn_type_id]['name_plural'],
						'type_id' => $vn_type_id,
						'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
						'date' => $va_date['display'],
						'hasChildren' => sizeof($va_child_occurrences) ? 1 : 0
					));
				}
			}
		}
		
		// Collections
		$va_collections = $this->get('ca_objects_x_collections.relation_id', array('returnAsArray' => true));
		$va_child_collections = [];
		if(caGetOption('ca_collections_includeFromChildren', $pa_bundle_settings, false)) {
	        $va_child_collections = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_objects_x_collections.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);    
            if($pb_show_child_history) { $va_collections = array_merge($va_collections, $va_child_collections); }
		}
		
		if(is_array($va_collection_types = caGetOption('ca_collections_showTypes', $pa_bundle_settings, null)) && is_array($va_collections)) {	
			$qr_collections = caMakeSearchResult('ca_objects_x_collections', $va_collections);
			require_once(__CA_MODELS_DIR__."/ca_collections.php");
			$t_collection = new ca_collections();
			$va_collection_type_info = $t_collection->getTypeList(); 
			
			$va_date_elements_by_type = array();
			foreach($va_collection_types as $vn_type_id) {
				if (!is_array($va_date_elements = caGetOption("ca_collections_{$va_collection_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
					$va_date_elements = array($va_date_elements);
				}
				if (!$va_date_elements) { continue; }
				$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
			}
			
			while($qr_collections->nextHit()) {
				$vn_rel_object_id = $qr_collections->get('ca_objects_x_collections.object_id');
				$vn_collection_id = $qr_collections->get('ca_collections.collection_id');
				if ((string)$qr_collections->get('ca_collections.deleted') !== '0') { continue; }	// filter out deleted
				$vn_type_id = $qr_collections->get('ca_collections.type_id');
				
				$va_dates = array();
				if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
					foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
						$va_date_bits = explode('.', $vs_date_element);
						$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_collections.{$vs_date_element}";
						$va_dates[] = array(
							'sortable' => $qr_collections->get($vs_date_spec, array('sortable' => true)),
							'bounds' => explode("/", $qr_collections->get($vs_date_spec, array('sortable' => true))),
							'display' => $qr_collections->get($vs_date_spec)
						);
					}
				}
				if (!sizeof($va_dates)) {
					$va_dates[] = array(
						'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_collections->get('lastModified.direct')),
						'bounds' => array(0, $vn_date),
						'display' => caGetLocalizedDate($vn_date)
					);
				}
				
				$vs_default_display_template = '^ca_collections.preferred_labels.name (^ca_collections.idno)';
				$vs_default_child_display_template = '^ca_collections.preferred_labels.name (^ca_collections.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
				$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_collections_{$va_collection_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
		        $vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(['ca_collections_childDisplayTemplate', 'ca_collections_childTemplate'], $pa_bundle_settings, $vs_display_template);
		       
				foreach($va_dates as $va_date) {
					if (!$va_date['sortable']) { continue; }
					if (!in_array($vn_type_id, $va_collection_types)) { continue; }
					if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
					
					$vs_color = $va_collection_type_info[$vn_type_id]['color'];
					if (!$vs_color || ($vs_color == '000000')) {
						$vs_color = caGetOption("ca_collections_{$va_collection_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
					}
					$vs_color = str_replace("#", "", $vs_color);
					
					$o_media_coder->setMedia($va_collection_type_info[$vn_type_id]['icon']);
					
					if(!is_array($va_history[$va_date['sortable']])) { $va_history[$va_date['sortable']] = []; }
					array_unshift($va_history[$va_date['sortable']], array(
						'type' => 'ca_collections',
						'id' => $vn_collection_id,
						'display' => $qr_collections->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
						'color' => $vs_color,
						'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
						'typename_singular' => $vs_typename = $va_collection_type_info[$vn_type_id]['name_singular'],
						'typename_plural' => $va_collection_type_info[$vn_type_id]['name_plural'],
						'type_id' => $vn_type_id,
						'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
						'date' => $va_date['display'],
						'hasChildren' => sizeof($va_child_collections) ? 1 : 0
					));
				}
			}
		}
		
		// Storage locations
		$va_locations = $this->get('ca_objects_x_storage_locations.relation_id', array('returnAsArray' => true));
		$va_child_locations = [];
	    if(caGetOption('ca_storage_locations_includeFromChildren', $pa_bundle_settings, false)) {
	        $va_child_locations = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_objects_x_storage_locations.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
            if ($pb_show_child_history) { $va_locations = array_merge($va_locations, $va_child_locations); }
		}
		
		if(is_array($va_location_types = caGetOption('ca_storage_locations_showRelationshipTypes', $pa_bundle_settings, null)) && is_array($va_locations)) {	
			require_once(__CA_MODELS_DIR__."/ca_storage_locations.php");
			$t_location = new ca_storage_locations();
			if ($this->inTransaction()) { $t_location->setTransaction($this->getTransaction()); }
			$va_location_type_info = $t_location->getTypeList(); 
			
			$vs_name_singular = $t_location->getProperty('NAME_SINGULAR');
			$vs_name_plural = $t_location->getProperty('NAME_PLURAL');
			
			$qr_locations = caMakeSearchResult('ca_objects_x_storage_locations', $va_locations);
			
			$vs_default_display_template = '^ca_storage_locations.parent.preferred_labels.name ➜ ^ca_storage_locations.preferred_labels.name (^ca_storage_locations.idno)';
			$vs_default_child_display_template = '^ca_storage_locations.parent.preferred_labels.name ➜ ^ca_storage_locations.preferred_labels.name (^ca_storage_locations.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
			$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption(['ca_storage_locations_displayTemplate', 'ca_storage_locations_template'], $pa_bundle_settings, $vs_default_display_template);
			$vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(['ca_storage_locations_childDisplayTemplate', 'ca_storage_locations_childTemplate'], $pa_bundle_settings, $vs_display_template);
			
			while($qr_locations->nextHit()) {
				$vn_rel_object_id = $qr_locations->get('ca_objects_x_storage_locations.object_id');
				$vn_location_id = $qr_locations->get('ca_objects_x_storage_locations.location_id');
				if ((string)$qr_locations->get('ca_storage_locations.deleted') !== '0') { continue; }	// filter out deleted
				
				$va_date = array(
					'sortable' => $qr_locations->get("ca_objects_x_storage_locations.effective_date", array('getDirectDate' => true)),
					'bounds' => explode("/", $qr_locations->get("ca_objects_x_storage_locations.effective_date", array('sortable' => true))),
					'display' => $qr_locations->get("ca_objects_x_storage_locations.effective_date")
				);

				if (!$va_date['sortable']) { continue; }
				if (!in_array($vn_rel_type_id = $qr_locations->get('ca_objects_x_storage_locations.type_id'), $va_location_types)) { continue; }
				$vn_type_id = $qr_locations->get('ca_storage_locations.type_id');
				
				//if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
				if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date))) { continue; }
				
				$vs_color = $va_location_type_info[$vn_type_id]['color'];
				if (!$vs_color || ($vs_color == '000000')) {
					$vs_color = caGetOption("ca_storage_locations_color", $pa_bundle_settings, 'ffffff');
				}
				$vs_color = str_replace("#", "", $vs_color);
				
				$o_media_coder->setMedia($va_location_type_info[$vn_type_id]['icon']);
				if(!is_array($va_history[$va_date['sortable']])) { $va_history[$va_date['sortable']] = []; }
				array_unshift($va_history[$va_date['sortable']], array(
					'type' => 'ca_storage_locations',
					'id' => $vn_location_id,
					'relation_id' => $qr_locations->get('relation_id'),
					'display' => $qr_locations->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
					'color' => $vs_color,
					'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
					'typename_singular' => $vs_name_singular, //$vs_typename = $va_location_type_info[$vn_type_id]['name_singular'],
					'typename_plural' => $vs_name_plural, //$va_location_type_info[$vn_type_id]['name_plural'],
					'type_id' => $vn_type_id,
					'rel_type_id' => $vn_rel_type_id,
					'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_name_singular.'</div>').'</div></div>',
					'date' => $va_date['display'],
					'hasChildren' => sizeof($va_child_locations) ? 1 : 0
				));
			}
		}
		
		// Deaccession
		if ((caGetOption('showDeaccessionInformation', $pa_bundle_settings, false) || (caGetOption('deaccession_displayTemplate', $pa_bundle_settings, false)))) {
			$vs_color = caGetOption('deaccession_color', $pa_bundle_settings, 'cccccc');
			$vs_color = str_replace("#", "", $vs_color);
			
			$vn_date = $this->get('deaccession_date', array('sortable'=> true));
			
			$vs_default_display_template = '^ca_objects.deaccession_notes';
			$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption('deaccession_displayTemplate', $pa_bundle_settings, $vs_default_display_template);
			
			$vs_name_singular = _t('deaccession');
			$vs_name_plural = _t('deaccessions');
			
			if ($this->get('is_deaccessioned') && !($pb_get_current_only && ($vn_date > $vn_current_date))) {
				$va_history[$vn_date.(int)$this->getPrimaryKey()][] = array(
					'type' => 'ca_objects_deaccession',
					'id' => $this->getPrimaryKey(),
					'display' => $this->getWithTemplate($vs_display_template),
					'color' => $vs_color,
					'icon_url' => '',
					'typename_singular' => $vs_name_singular, 
					'typename_plural' => $vs_name_plural, 
					'type_id' => null,
					'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon"><div class="caUseHistoryIconText">'.$vs_name_singular.'</div>'.'</div></div>',
					'date' => $this->get('deaccession_date')
				);
			}
			
			// get children
			if(caGetOption(['deaccession_includeFromChildren'], $pa_bundle_settings, false)) {
                if (is_array($va_child_object_ids = $this->get("ca_objects.children.object_id", ['returnAsArray' => true])) && sizeof($va_child_object_ids) && ($q = caMakeSearchResult('ca_objects', $va_child_object_ids))) {
                    while($q->nextHit()) {
                        if(!$q->get('is_deaccessioned')) { continue; }
                        $vn_date = $q->get('deaccession_date', array('sortable'=> true));
                        $vn_id = (int)$q->get('ca_objects.object_id');
                        $va_history[$vn_date.$vn_id][] = array(
                            'type' => 'ca_objects_deaccession',
                            'id' => $vn_id,
                            'display' => $q->getWithTemplate($vs_display_template),
                            'color' => $vs_color,
                            'icon_url' => '',
                            'typename_singular' => $vs_name_singular, 
                            'typename_plural' => $vs_name_plural, 
                            'type_id' => null,
                            'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon"><div class="caUseHistoryIconText">'.$vs_name_singular.'</div>'.'</div></div>',
                            'date' => $q->get('deaccession_date')
                        );    
                    
                    }
                }
            }
		}
		
		ksort($va_history);
		if(caGetOption('sortDirection', $pa_bundle_settings, 'DESC', ['forceUppercase' => true]) !== 'ASC') { $va_history = array_reverse($va_history); }
		
		if ($pn_limit > 0) {
			$va_history = array_slice($va_history, 0, $pn_limit);
		}
		
		ExternalCache::save($vs_cache_key, $va_history, "objectHistory");
		return $va_history;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns HTML editor form bundle for ca_objects_history (object use history bundle)
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options Array of options. Options include:
	 *		noCache = Don't use any cached history data. [Default is false]
	 *		currentOnly = Only return history entries dates before or on the current date. [Default is false]
	 *		limit = Only return a maximum number of history entries. [Default is null; no limit]
	 *
	 * @return string Rendered HTML bundle
	 *
	 * @uses ca_objects::getObjectHistory()
 	 */
 	public function getObjectHistoryHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		require_once(__CA_MODELS_DIR__."/ca_occurrences.php");
		require_once(__CA_MODELS_DIR__."/ca_loans_x_objects.php");
		require_once(__CA_MODELS_DIR__."/ca_objects_x_storage_locations.php");
		
 		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$vs_display_template		= caGetOption('display_template', $pa_bundle_settings, _t('No template defined'));
		$vs_history_template		= caGetOption('history_template', $pa_bundle_settings, $vs_display_template);
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);

		$pa_bundle_settings = $this->_processObjectHistoryBundleSettings($pa_bundle_settings);
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
		$o_view->setVar('t_subject', $this);
		
		//
		// Occurrence update
		//
		$t_occ = new ca_occurrences();
		$va_occ_types = $t_occ->getTypeList();
		$va_occ_types_to_show =  caGetOption('add_to_occurrence_types', $pa_bundle_settings, array(), ['castTo' => 'array']);
		foreach($va_occ_types as $vn_type_id => $va_type_info) {
			if (!in_array($vn_type_id, $va_occ_types_to_show) && !in_array($va_type_info['idno'], $va_occ_types_to_show)) { unset($va_occ_types[$vn_type_id]); }
		}
		
		$o_view->setVar('occurrence_types', $va_occ_types);
		$t_occ_rel = new ca_objects_x_occurrences();
		$o_view->setVar('occurrence_relationship_types', $t_occ_rel->getRelationshipTypes(null, null,  array_merge($pa_options, $pa_bundle_settings)));
		$o_view->setVar('occurrence_relationship_types_by_sub_type', $t_occ_rel->getRelationshipTypesBySubtype($this->tableName(), $this->get('type_id'),  array_merge($pa_options, $pa_bundle_settings)));
		
		//
		// Loan update
		//
		$t_loan_rel = new ca_loans_x_objects();
		$o_view->setVar('loan_relationship_types', $t_loan_rel->getRelationshipTypes(null, null,  array_merge($pa_options, $pa_bundle_settings)));
		$o_view->setVar('loan_relationship_types_by_sub_type', $t_loan_rel->getRelationshipTypesBySubtype($this->tableName(), $this->get('type_id'),  array_merge($pa_options, $pa_bundle_settings)));

		// Location update
		$t_location_rel = new ca_objects_x_storage_locations();
		$o_view->setVar('location_relationship_types', $t_location_rel->getRelationshipTypes(null, null,  array_merge($pa_options, $pa_bundle_settings)));
		$o_view->setVar('location_relationship_types_by_sub_type', $t_location_rel->getRelationshipTypesBySubtype($this->tableName(), $this->get('type_id'),  array_merge($pa_options, $pa_bundle_settings)));

		//
		// Location update
		//
		$o_view->setVar('mode', $vs_mode = caGetOption('locationTrackingMode', $pa_bundle_settings, 'ca_storage_locations'));
		
		switch($vs_mode) {
			case 'ca_storage_locations':
				$t_last_location = $this->getLastLocation(array());
				
				if (!$vs_display_template) { $vs_display_template = "<unit relativeTo='ca_storage_locations'><l>^ca_storage_locations.hierarchy.preferred_labels.name%delimiter=_➜_</l></unit> (^ca_objects_x_storage_locations.effective_date)"; }
				$o_view->setVar('current_location', $t_last_location ? $t_last_location->getWithTemplate($vs_display_template) : null);
				
				if (!$vs_history_template) { $vs_history_template = $vs_display_template; }
				$o_view->setVar('location_history', $this->getLocationHistory(array('template' => $vs_history_template)));
				
				$o_view->setVar('location_relationship_type', $this->getAppConfig()->get('object_storage_location_tracking_relationship_type'));
				$o_view->setVar('location_change_url',  null);
				break;
			case 'ca_movements':
			default:
				$t_last_movement = $this->getLastMovement(array('dateElement' => $vs_movement_date_element = $this->getAppConfig()->get('movement_storage_location_date_element')));
				
				if (!$vs_display_template) { $vs_display_template = "<l>^ca_storage_locations.hierarchy.preferred_labels.name%delimiter=_➜_</l> (^ca_movements.{$vs_movement_date_element})"; }
				$o_view->setVar('current_location', $t_last_movement ? $t_last_movement->getWithTemplate($vs_display_template) : null);
				
				if (!$vs_history_template) { $vs_history_template = $vs_display_template; }
				$o_view->setVar('location_history', $this->getMovementHistory(array('dateElement' => $vs_movement_date_element, 'template' => $vs_history_template)));
				
				$o_view->setVar('location_relationship_type', $this->getAppConfig()->get('movement_storage_location_tracking_relationship_type'));
				$o_view->setVar('location_change_url', caNavUrl($po_request, 'editor/movements', 'MovementQuickAdd', 'Form', array('movement_id' => 0)));
				break;
		}
		
		
		
		$h = $this->getObjectHistory($pa_bundle_settings, $pa_options);
		$o_view->setVar('child_count', $child_count = sizeof(array_filter($h, function($v) { return sizeof(array_filter($v, function($x) { return $x['hasChildren']; })); })));
		$o_view->setVar('history', $h);
		
		return $o_view->render('ca_objects_history.php');
 	}
 	# ------------------------------------------------------
 	/**
 	 * 
 	 */
	private function _processObjectHistoryBundleSettings($pa_bundle_settings) {

		if (($vb_use_app_defaults = caGetOption('useAppConfDefaults', $pa_bundle_settings, false)) && is_array($va_current_location_criteria = $this->getAppConfig()->getAssoc('current_location_criteria')) && sizeof($va_current_location_criteria)) {
			// Copy app.conf "current_location_criteria" settings into bundle settings (with translation)
			$va_bundle_settings = array();
			foreach($va_current_location_criteria as $vs_table => $va_info) {
				switch($vs_table) {
					case 'ca_storage_locations':
						if(is_array($va_info)) {
							foreach($va_info as $vs_rel_type => $va_options) {
								$va_bundle_settings["{$vs_table}_showRelationshipTypes"][] = $vs_rel_type;
								foreach($va_options as $vs_opt => $vs_opt_val) {
									switch($vs_opt) {
										case 'template':
											$vs_opt = 'displayTemplate';
											break;
									}
									$va_bundle_settings["{$vs_table}_{$vs_opt}"] = $vs_opt_val;
								}
							}
							$va_bundle_settings["{$vs_table}_showRelationshipTypes"] = caMakeRelationshipTypeIDList('ca_objects_x_storage_locations', $va_bundle_settings["{$vs_table}_showRelationshipTypes"]);
						}
						break;
					case 'ca_objects':
						if(is_array($va_info)) {
							$va_bundle_settings['showDeaccessionInformation'] = 1;
							foreach($va_info as $vs_opt => $vs_opt_val) {
								switch($vs_opt) {
									case 'template':
										$vs_opt = 'displayTemplate';
										break;
								}
								$va_bundle_settings["deaccession_{$vs_opt}"] = $vs_opt_val;
							}
						}
						break;
					default:
						if(is_array($va_info)) {
							foreach($va_info as $vs_type => $va_options) {
							    if(!is_array($va_options)) { continue; }
								$va_bundle_settings["{$vs_table}_showTypes"][] = $vs_type;
								foreach($va_options as $vs_opt => $vs_opt_val) {
									switch($vs_opt) {
										case 'date':
											$vs_opt = 'dateElement';
											break;
										case 'template':
											$vs_opt = 'displayTemplate';
											break;
									}
									$va_bundle_settings["{$vs_table}_{$vs_type}_{$vs_opt}"] = $vs_opt_val;
								}
							}
							$va_bundle_settings["{$vs_table}_showTypes"] = caMakeTypeIDList($vs_table, $va_bundle_settings["{$vs_table}_showTypes"]);
						}
						break;
				}
			}

			foreach(array(
						'locationTrackingMode', 'width', 'height', 'readonly', 'documentation_url', 'expand_collapse',
						'label', 'description', 'useHierarchicalBrowser', 'hide_add_to_loan_controls', 'hide_update_location_controls',
						'hide_add_to_occurrence_controls', 'hide_include_child_history_controls', 'add_to_occurrence_types', 'ca_storage_locations_elements', 'sortDirection'
					) as $vs_key) {
				if (isset($va_current_location_criteria[$vs_key]) && $vb_use_app_defaults) {
					$va_bundle_settings[$vs_key] = $va_current_location_criteria[$vs_key];
				} elseif(!$vb_use_app_defaults || !in_array($vs_key, ['sortDirection'])) {
					$va_bundle_settings[$vs_key] = $pa_bundle_settings[$vs_key];
				}
			}
			$pa_bundle_settings = $va_bundle_settings;
		}
		
		return $pa_bundle_settings;
	}
	# ------------------------------------------------------
 	/**
 	 * 
 	 */
 	public function getLastMovement($pa_options=null) {
 		$pn_object = caGetOption('object_id', $pa_options, null);
 		if (!($vn_object_id = ($pn_object_id > 0) ? $pn_object_id : $this->getPrimaryKey())) { return null; }
 		
 		if (!($ps_date_element = caGetOption('dateElement', $pa_options, null))) { return null; }
 		if (!($t_element = ca_metadata_elements::getInstance($ps_date_element))) { return null; }
 		
 		$vn_current_date = TimeExpressionParser::now();
 		
 		$o_db = $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT cmo.relation_id
 			FROM ca_movements_x_objects cmo
 			INNER JOIN ca_movements AS m ON m.movement_id = cmo.movement_id
 			INNER JOIN ca_attributes AS a ON a.row_id = m.movement_id
 			INNER JOIN ca_attribute_values AS av ON av.attribute_id = a.attribute_id
 			WHERE
 				(cmo.object_id = ?) AND 
 				(av.element_id = ?) AND (a.table_num = 137) AND 
 				(m.deleted = 0) AND (av.value_decimal1 <= ?)
 			ORDER BY
 				av.value_decimal1 DESC, cmo.relation_id DESC
 			LIMIT 1
 		", array($vn_object_id, (int)$t_element->getPrimaryKey(), $vn_current_date));
 		if($qr_res->nextRow()) {
 			return new ca_movements_x_objects($qr_res->get('relation_id'));
 		}
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 *
 	 * @param array $pa_options Array of options:
 	 *		template =
 	 *		dateElement = 
 	 */
 	public function getMovementHistory($pa_options=null) {
 		$pn_object = caGetOption('object_id', $pa_options, null);
 		if (!($vn_object_id = ($pn_object_id > 0) ? $pn_object_id : $this->getPrimaryKey())) { return null; }
 		
 		$ps_display_template = caGetOption('template', $pa_options, '^ca_movements_x_objects.relation_id');
 		
 		if (!($ps_date_element = caGetOption('dateElement', $pa_options, null))) { return null; }
 		if (!($t_element = ca_metadata_elements::getInstance($ps_date_element))) { return null; }
 		
 		$vn_current_date = TimeExpressionParser::now();
 		
 		//
 		// Directly query the date attribute for performance
 		// 
 		$vn_movements_table_num = (int)Datamodel::getTableNum("ca_movements");
 		
 		$o_db = $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT cmo.relation_id, cmo.movement_id, cmo.object_id, av.value_decimal1
 			FROM ca_movements_x_objects cmo
 			INNER JOIN ca_movements AS m ON m.movement_id = cmo.movement_id
 			INNER JOIN ca_attributes AS a ON a.row_id = m.movement_id
 			INNER JOIN ca_attribute_values AS av ON av.attribute_id = a.attribute_id
 			WHERE
 				(cmo.object_id = ?) AND 
 				(av.element_id = ?) AND (a.table_num = ?) AND 
 				(m.deleted = 0)
 			ORDER BY
 				av.value_decimal1 DESC, cmo.relation_id DESC
 		", array($vn_object_id, (int)$t_element->getPrimaryKey(), $vn_movements_table_num));
 		
 		$va_relation_ids = $qr_res->getAllFieldValues('relation_id');
 	
		$va_paths = $va_location_ids = array();
 		if(sizeof($va_relation_ids)) {
			// get location paths
			$qr_paths = $o_db->query("
				SELECT cmo.relation_id, cmo.movement_id, cxsl.source_info
				FROM ca_movements_x_objects cmo
				INNER JOIN ca_movements_x_storage_locations AS cxsl ON cxsl.movement_id = cmo.movement_id
				WHERE
					(cmo.relation_id IN (?))
			", array($va_relation_ids));
		
			while($qr_paths->nextRow()) {
				$va_data = caUnserializeForDatabase($qr_paths->get('source_info'));
				$va_paths[$qr_paths->get('movement_id')] = is_array($va_data['path']) ? join(" ➜ ", $va_data['path']) : $qr_paths->get('ca_storage_locations.hierarchy.preferred_labels.name', array('delimiter' => ' ➜ '));
				$va_location_ids[$qr_paths->get('movement_id')] = $va_data['ids'];
			}
 		}
 		$va_displays = caProcessTemplateForIDs($ps_display_template, 'ca_movements_x_objects', $va_relation_ids, array('returnAsArray' => true, 'forceValues' => array('ca_storage_locations.hierarchy.preferred_labels.name' => $va_paths)));
 
		$qr_res->seek(0);
 		$va_items = array();
 		
 		$vb_have_seen_the_present = false;
 		while($qr_res->nextRow()) {
 			$va_row = $qr_res->getRow();
 			$vn_relation_id = $va_row['relation_id'];
 			
 			if ($va_row['value_decimal1'] > $vn_current_date) { 
 				$vs_status = 'FUTURE';
 			} else {
 				$vs_status = $vb_have_seen_the_present ? 'PAST' : 'PRESENT';
 				$vb_have_seen_the_present = true;
 			}
 			
 			$va_items[$vn_relation_id] = array(
 				'object_id' => $va_row['object_id'],
 				'movement_id' => $va_row['movement_id'],
 				'display' => array_shift($va_displays),
 				'status' => $vs_status
 			);
 		}
 		return $va_items;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return last storage location as an ca_objects_x_storage_locations instance
 	 */
 	public function getLastLocation($pa_options=null) {
 		$pn_object_id = caGetOption('object_id', $pa_options, null);
 		if (!($vn_object_id = ($pn_object_id > 0) ? $pn_object_id : $this->getPrimaryKey())) { return null; }
 		
 		$vn_current_date = TimeExpressionParser::now();
 		
 		$o_db = $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT csl.relation_id
 			FROM ca_objects_x_storage_locations csl
 			INNER JOIN ca_storage_locations AS sl ON sl.location_id = csl.location_id
 			WHERE
 				(csl.object_id = ?) AND 
 				(sl.deleted = 0) AND ((csl.sdatetime <= ?) || (csl.sdatetime IS NULL))
 			ORDER BY
 				csl.sdatetime DESC, csl.relation_id DESC
 			LIMIT 1
 		", array($vn_object_id, $vn_current_date));
 		
 		if($qr_res->nextRow()) {
 			$t_loc =  new ca_objects_x_storage_locations($qr_res->get('relation_id'));
 			if ($this->inTransaction()) { $t_loc->setTransaction($this->getTransaction()); }
 			return $t_loc;
 		}
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return last storage location formatted using provided template
 	 */
 	public function getLastLocationForDisplay($ps_template, $pa_options=null) {
 		if ($t_last_loc = $this->getLastLocation($pa_options)) {
 			return $t_last_loc->getWithTemplate($ps_template, $pa_options);
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 *
 	 * @param array $pa_options Array of options:
 	 *		template =
 	 */
 	public function getLocationHistory($pa_options=null) {
 		$pn_object_id = caGetOption('object_id', $pa_options, null);
 		if (!($vn_object_id = ($pn_object_id > 0) ? $pn_object_id : $this->getPrimaryKey())) { return null; }
 		
 		$ps_display_template = caGetOption('template', $pa_options, '^ca_objects_x_storage_locations.relation_id');
 		
 		$vn_current_date = TimeExpressionParser::now();
 		
 		//
 		// Directly query the date field for performance
 		// 
 		
 		$o_db = $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT csl.relation_id, csl.location_id, csl.object_id, csl.sdatetime, csl.edatetime, csl.source_info
 			FROM ca_objects_x_storage_locations csl
 			INNER JOIN ca_storage_locations AS sl ON sl.location_id = csl.location_id
 			WHERE
 				(csl.object_id = ?) AND 
 				(sl.deleted = 0)
 			ORDER BY
 				 csl.sdatetime DESC, csl.relation_id DESC
 		", array($vn_object_id));
 		
 		
 		$va_relation_ids = $qr_res->getAllFieldValues('relation_id');
 		$va_displays = caProcessTemplateForIDs($ps_display_template, 'ca_objects_x_storage_locations', $va_relation_ids, array('returnAsArray' => true));
 
		$qr_res->seek(0);
 		$va_items = array();
 		
 		while($qr_res->nextRow()) {
 			$va_row = $qr_res->getRow();
 			$vn_relation_id = $va_row['relation_id'];
 			
 			if ($va_row['sdatetime'] > $vn_current_date) { 
 				$vs_status = 'FUTURE';
 			} elseif ($va_row['source_info'] == 'current') {
 				$vs_status = 'PRESENT';
 			} else {
 				$vs_status = 'PAST';
 			}
 			
 			$va_items[$vn_relation_id] = array(
 				'object_id' => $va_row['object_id'],
 				'location_id' => $va_row['location_id'],
 				'display' => array_shift($va_displays),
 				'status' => $vs_status
 			);
 		}
 		return $va_items;
 	}
 	# ------------------------------------------------------
 	# Components
 	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle for component listing
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options Array of options. Options include:
	 *			noCache = If set to true then label cache is bypassed. [Default = true]
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getComponentListHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
		$o_view->setVar('t_subject', $this);
		
		
		
		return $o_view->render('ca_objects_components_list.php');
	}
	# ------------------------------------------------------
	/** 
	 * Return number of components directly linked to this container
	 *
	 * @param array $pa_options Array of options:
	 *			object_id = object to return components for, rather than currently loaded object. [Default = null; use loaded object]
	 *	
	 * @return int 
	 */
	public function getComponentCount($pa_options=null) {
		$pn_object_id = caGetOption('object_id', $pa_options, null);
		if (!(int)$pn_object_id) { $pn_object_id = (int)$this->getPrimaryKey(); }
		if (!$pn_object_id) { return null; }
		
		$va_component_types = $this->getAppConfig()->getList('ca_objects_component_types');
		
		if (is_array($va_component_types) && (sizeof($va_component_types) && !in_array('*', $va_component_types))) {
			$va_ids = ca_objects::find(array('parent_id' => $pn_object_id, 'type_id' => $va_component_types, array('returnAs' => 'ids')));
		} else {
			$va_ids = ca_objects::find(array('parent_id' => $pn_object_id, array('returnAs' => 'ids')));
		}
	
		if (!is_array($va_ids)) { return 0; }
		return sizeof($va_ids);	
	}
	# ------------------------------------------------------
	/** 
	 * Return number of components directly linked to this container
	 *
	 * @param array $pa_options Array of options:
	 *		object_id = object to return components for, rather than currently loaded object. [Default = null; use loaded object]
	 *		returnAs = what to return; possible values are:
	 *			searchResult			= a search result instance (aka. a subclass of BaseSearchResult), when the calling subclass is searchable (ie. <classname>Search and <classname>SearchResult classes are defined) 
	 *			ids						= an array of ids (aka. primary keys)
	 *			modelInstances			= an array of instances, one for each match. Each instance is the same class as the caller, a subclass of BaseModel 
	 *			firstId					= the id (primary key) of the first match. This is the same as the first item in the array returned by 'ids'
	 *			firstModelInstance		= the instance of the first match. This is the same as the first instance in the array returned by 'modelInstances'
	 *			info					= an array, keyed on object_id with label, type and idno of each component
	 *			
	 * @return int 
	 */
	public function getComponents($pa_options=null) {
		$pn_object_id = caGetOption('object_id', $pa_options, null);
		if (!(int)$pn_object_id) { $pn_object_id = (int)$this->getPrimaryKey(); }
		if (!$pn_object_id) { return null; }
		
		if (caGetOption('idsOnly', $pa_options, false)) { $pa_options['returnAs'] = 'ids'; }
		$vs_return_as = caGetOption('returnAs', $pa_options, 'info');
	
		$va_component_types = $this->getAppConfig()->getList('ca_objects_component_types');
		
		if (is_array($va_component_types) && (sizeof($va_component_types) && !in_array('*', $va_component_types))) {
			$vm_res = ca_objects::find(array('parent_id' => $pn_object_id, 'type_id' => $va_component_types), array('sort' => 'ca_objects.idno', 'returnAs' => ($vs_return_as == 'info') ? 'searchResult' : $vs_return_as));
		} else {
			$vm_res = ca_objects::find(array('parent_id' => $pn_object_id), array('sort' => 'ca_objects.idno', 'returnAs' => ($vs_return_as == 'info') ? 'searchResult' : $vs_return_as));
		}
	
		if ($vs_return_as == 'info') {
			$va_data = array();
			while($vm_res->nextHit()) {
				$vn_object_id = $vm_res->get('ca_objects.object_id');
				$va_data[$vn_object_id] = array(
					'object_id' => $vn_object_id,
					'id' => $vn_object_id,
					'label' => $vm_res->get('ca_objects.preferred_labels.name'),
					'idno' => $vm_res->get('ca_objects.idno'),
					'type_id' => $vm_res->get('ca_objects.type_id'),
					'source_id' => $vm_res->get('ca_objects.source_id')
				);
			}
			
			return caSortArrayByKeyInValue($va_data, array('idno'));
		}
		return $vm_res;	
	}
	# ------------------------------------------------------
	/** 
	 * Check if currently loaded object is a component container
	 *	
	 * @return bool 
	 */
	public function canTakeComponents() {
		$va_container_types = $this->getAppConfig()->getList('ca_objects_container_types');
		if (!is_array($va_container_types) || !sizeof($va_container_types)) { return false; }
		if (in_array('*', $va_container_types)) { return true; }
		return in_array($this->getTypeCode(), $va_container_types);
	}
	# ------------------------------------------------------
	/** 
	 * Check if currently loaded object is a component 
	 *	
	 * @return bool 
	 */
	public function isComponent() {
		$va_container_types = $this->getAppConfig()->getList('ca_objects_container_types');
		$va_component_types = $this->getAppConfig()->getList('ca_objects_component_types');
		if (!is_array($va_container_types) || !sizeof($va_container_types)) { return false; }
		if (!is_array($va_component_types) || !sizeof($va_component_types)) { return false; }
		if (!($vn_parent_id = $this->get('parent_id'))) { return false; }		// component must be in a container
		
		if (!in_array($this->getTypeCode(), $va_component_types) && !in_array('*', $va_component_types)) { return false; }	// check component type
		$t_parent = new ca_objects($vn_parent_id);
		if (!$t_parent->getPrimaryKey()) { return false; }
		if (!in_array($t_parent->getTypeCode(), $va_container_types) && !in_array('*', $va_container_types)) { return false; }	// check container type
		
		return true;
	}
 	# ------------------------------------------------------
 	# Current location browse support
 	# ------------------------------------------------------
 	/**
 	 * Override BaseModel::addRelationship() to update current location fields in ca_objects
 	 *
 	 * @param mixed $pm_rel_table_name_or_num
 	 * @param int $pn_rel_id
 	 * @param mixed $pm_type_id
 	 * @param string $ps_effective_date
 	 * @param string $ps_source_info
 	 * @param string $ps_direction
 	 * @param int $pn_rank
 	 * @param array $pa_options
 	 *
 	 * @return BaseRelationshipModel
 	 */
 	public function addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $ps_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {
 		if ($vn_rc = parent::addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id, $ps_effective_date, $ps_source_info, $ps_direction, $pn_rank, $pa_options)) {
 			
 			if ($this->relationshipChangeMayAffectCurrentLocation($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id)) {
 				$this->deriveCurrentLocationForBrowse();
 			}
 		}
 		
 		return $vn_rc;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Override BaseModel::editRelationship() to update current location fields in ca_objects
 	 *
 	 * @param mixed $pm_rel_table_name_or_num
 	 * @param int $pn_relation_id
 	 * @param mixed $pm_type_id
 	 * @param string $ps_effective_date
 	 * @param string $ps_source_info
 	 * @param string $ps_direction
 	 * @param int $pn_rank
 	 * @param array $pa_array
 	 *
 	 * @return int
 	 */
 	public function editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $ps_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {
 		if ($vn_rc = parent::editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id, $ps_effective_date, $ps_source_info, $ps_direction, $pn_rank, $pa_options)) {
 			
 		//	if ($this->relationshipChangeMayAffectCurrentLocation($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id)) {
 		//		$this->deriveCurrentLocationForBrowse();
 		//	}
 		}
 		
 		return $vn_rc;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Override BaseModel::removeRelationship() to update current location fields in ca_objects
 	 *
 	 * @param mixed $pm_rel_table_name_or_num
 	 * @param int $pn_relation_id
 	 *
 	 * @return int
 	 */
 	public function removeRelationship($pm_rel_table_name_or_num, $pn_relation_id) {
 		if ($vn_rc = parent::removeRelationship($pm_rel_table_name_or_num, $pn_relation_id)) {
 			
 			if ($this->relationshipChangeMayAffectCurrentLocation($pm_rel_table_name_or_num, null, null)) {
 				$this->deriveCurrentLocationForBrowse();
 			}
 		}
 		
 		return $vn_rc;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Override BaseModel::removeRelationships() to update current location fields in ca_objects
 	 *
 	 * @param mixed $pm_rel_table_name_or_num
 	 * @param mixed $pm_type_id
 	 *
 	 * @return int
 	 */
 	public function removeRelationships($pm_rel_table_name_or_num, $pm_type_id=null, $pa_options=null) {
 		if ($vn_rc = parent::removeRelationships($pm_rel_table_name_or_num, $pm_type_id, $pa_options)) {
 			
 			if ($this->relationshipChangeMayAffectCurrentLocation($pm_rel_table_name_or_num, null, $pm_type_id)) {
 				$this->deriveCurrentLocationForBrowse();
 			}
 		}
 		
 		return $vn_rc;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Determines is a change being made to the object's relationships maight affect current location. I
 	 *
 	 * @param mixed $pm_rel_table_name_or_num Table bame or number of the related table
 	 * @param int $pn_rel_id Primary key of the record being related to the object
 	 * @param mixed $pm_type_id Type_id or type code for relationship
 	 * @param array $pa_options No options are currently supported.
 	 *
 	 * @return bool
 	 */
 	private function relationshipChangeMayAffectCurrentLocation($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id=null, $pa_options=null) {
 		ExternalCache::flush("objectHistory");
 		return true;
 		if(!$pn_rel_id) { return true; }	// null record means we are batch deleting so go ahead and recalculate
 		
 		if (!($t_instance = Datamodel::getInstance($pm_rel_table_name_or_num, true))) { return null; }
 		if ((($vs_table_name = $t_instance->tableName())) !== 'ca_storage_locations') {
 			$pm_type_id = $t_instance->getTypeID($pn_rel_id);
 		}
 		if (ca_objects::getConfigurationForCurrentLocationType($pm_rel_table_name_or_num, $pm_type_id)) { return true; }
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Calculates the current location of the currently loaded object and stores them in the ca_objects.current_loc_class,
 	 * ca_objects.current_loc_subclass and ca_objects.current_loc_id fields.
 	 *
 	 * @param array $pa_options
 	 *
 	 * @return bool
 	 */
 	public function deriveCurrentLocationForBrowse($pa_options=null) {
 		$va_bundle_settings = array();
 		$t_rel_type = new ca_relationship_types();
 		$va_map = $this->getAppConfig()->getAssoc('current_location_criteria');
 		if(!is_array($va_map)){
		    $va_map = array();
	    }
	 
 		foreach($va_map as $vs_table => $va_types) {
 			$va_bundle_settings["{$vs_table}_showTypes"] = array();
 			if(is_array($va_types)) {
				foreach($va_types as $vs_type => $va_config) {
					switch($vs_table) {
						case 'ca_storage_locations':
						case 'ca_objects_x_storage_locations':
							$va_bundle_settings["{$vs_table}_showRelationshipTypes"][] = $t_rel_type->getRelationshipTypeID('ca_objects_x_storage_locations', $vs_type);
							break;
						default:
							if(!is_array($va_config)) { break; }
							$va_bundle_settings["{$vs_table}_showTypes"][] = array_shift(caMakeTypeIDList($vs_table, array($vs_type)));
							$va_bundle_settings["{$vs_table}_{$vs_type}_dateElement"] = $va_config['date'];
							break;
					}
				}
			}
 		}
 		
		if (is_array($va_history = $this->getObjectHistory($va_bundle_settings, array('displayLabelOnly' => true, 'limit' => 1, 'currentOnly' => true, 'noCache' => true))) && (sizeof($va_history) > 0)) {
			$va_current_location = array_shift(array_shift($va_history));
			
			if ($va_current_location['type'] == 'ca_storage_locations') {
				return $this->setCurrentLocationForBrowse('ca_objects_x_storage_locations', $va_current_location['rel_type_id'], $va_current_location['id'], array('dontCheckID' => true));
			} else {
				return $this->setCurrentLocationForBrowse($va_current_location['type'], $va_current_location['type_id'], $va_current_location['id'], array('dontCheckID' => true));
			}
		}
		
		return $this->setCurrentLocationForBrowse(null, null, array('dontCheckID' => true));
 	}
 	# ------------------------------------------------------
 	/**
 	 * Sets the ca_objects.current_loc_class, ca_objects.current_loc_subclass and ca_objects.current_loc_id
 	 * fields in the currently loaded object row with information about the current location. These fields are used 
 	 * by BrowseEngine to browse objects on current location
 	 *
 	 * @param mixed $pm_loc_class
 	 * @param mixed $pm_current_loc_subclass
 	 * @param int $pn_current_loc_id
 	 * @param array $pa_options Options include:
 	 *		dontCheckID = Don't verify that the referenced row exists. This can save time if you're updating many object rows. [Default=false]
 	 *
 	 * @see BrowseEngine
 	 *
 	 * @return bool
 	 */
 	private function setCurrentLocationForBrowse($pm_current_loc_class, $pm_current_loc_subclass, $pn_current_loc_id, $pa_options=null) {
 		if (!$this->getPrimaryKey()) { return null; }
 		if ($vn_table_num = Datamodel::getTableNum($pm_current_loc_class)) {
 			$t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
 			if (!caGetOption('dontCheckID', $pa_options, false)) {
 				if (!$t_instance->load(array($t_instance->primaryKey() => $pn_current_loc_id, 'deleted' => 0))) {
 					return false;
 				}
 			}
 			
 			if(!is_numeric($vn_type_id = $pm_current_loc_subclass)) {
				switch($vs_table_name) {
					case 'ca_storage_locations':
						$t_rel_type = new ca_relationship_types();
						$vn_type_id = $t_rel_type->getRelationshipTypeID('ca_objects_x_storage_locations', $pm_current_loc_subclass);
						break;
					default:
						$vn_type_id = $t_instance->getTypeIDForCode($pm_current_loc_subclass);
						break;
				}
			}
			
 			$this->setMode(ACCESS_WRITE);
 			$this->set('current_loc_class', $vn_table_num);
 			$this->set('current_loc_subclass', $vn_type_id);
 			$this->set('current_loc_id', $pn_current_loc_id);
 			$this->update();
 			
 			if ($this->numErrors()) {
 				return false;
 			} 
 			
 			return true;
 		} else {
 			$this->setMode(ACCESS_WRITE);
 			$this->set('current_loc_class', null);
 			$this->set('current_loc_subclass', null);
 			$this->set('current_loc_id', null);
 			$this->update();
 			
 			
 			if ($this->numErrors()) {
 				return false;
 			} 
 			return true;
 		}
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Fetches configuration for the specified location class/subclass
 	 *
 	 * @param mixed $pm_current_loc_class Table name or number (aka. class)
 	 * @param mixed $pm_current_loc_subclass Type_id or code (aka. subclass)
 	 * 
 	 * @return array
 	 */
 	public static function getConfigurationForCurrentLocationType($pm_current_loc_class, $pm_current_loc_subclass=null, $pa_options=null) {
 		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$pm_current_loc_class}/{$pm_current_loc_subclass}");
 		
 		if (isset(ca_objects::$s_current_location_type_configuration_cache[$vs_cache_key])) { return ca_objects::$s_current_location_type_configuration_cache[$vs_cache_key]; }
 		$o_config = Configuration::load();
 		
 		$va_map = $o_config->getAssoc('current_location_criteria');
 		
 		if (isset($va_map['ca_storage_locations'])) { 
 			$va_map['ca_objects_x_storage_locations'] = $va_map['ca_storage_locations'];
 			unset($va_map['ca_storage_locations']);
 		}
 		
 		if (!($t_instance = Datamodel::getInstance($pm_current_loc_class, true))) { return ca_objects::$s_current_location_type_configuration_cache[$vs_cache_key] = null; }
 		$vs_table_name = $t_instance->tableName();
 		
 		if (isset($va_map[$vs_table_name])) {
 			if ((!$pm_current_loc_subclass) && isset($va_map[$vs_table_name]['*'])) { return ca_objects::$s_current_location_type_configuration_cache[caMakeCacheKeyFromOptions($pa_options, "{$vs_table_name}/{$pm_type_id}")] = ca_objects::$s_current_location_type_configuration_cache[$vs_cache_key] = $va_map[$vs_table_name]['*']; }	// return default config if no type is specified
 			
 			if ($pm_current_loc_subclass) { 
 				switch($vs_table_name) {
 					case 'ca_objects_x_storage_locations':
 						$va_types = ca_relationship_types::relationshipTypeIDsToTypeCodes(array($pm_current_loc_subclass));
 						$vs_type = array_shift($va_types);
 						break;
 					default:
 						$vs_type = $t_instance->getTypeCode($pm_current_loc_subclass);
 						break;
 				}
 				
 				$va_facet_display_config = caGetOption('facet', $pa_options, null); 
 				if ($vs_type && isset($va_map[$vs_table_name][$vs_type])) {
 					if (is_array($va_facet_display_config) && isset($va_facet_display_config[$vs_table_name][$vs_type])) {
 						$va_map[$vs_table_name][$vs_type] = array_merge($va_map[$vs_table_name][$vs_type], $va_facet_display_config[$vs_table_name][$vs_type]);
 					}
					return ca_objects::$s_current_location_type_configuration_cache[caMakeCacheKeyFromOptions($pa_options, "{$vs_table_name}/{$pm_current_loc_subclass}")] = ca_objects::$s_current_location_type_configuration_cache[$vs_cache_key] = $va_map[$vs_table_name][$vs_type];
				} elseif (isset($va_map[$vs_table_name]['*'])) {
					if (is_array($va_facet_display_config) && isset($va_facet_display_config[$vs_table_name]['*'])) {
 						$va_map[$vs_table_name][$vs_type] = array_merge($va_map[$vs_table_name]['*'], $va_facet_display_config[$vs_table_name]['*']);
 					}
					return ca_objects::$s_current_location_type_configuration_cache[caMakeCacheKeyFromOptions($pa_options, "{$vs_table_name}/{$pm_current_loc_subclass}")] = ca_objects::$s_current_location_type_configuration_cache[$vs_cache_key] = $va_map[$vs_table_name]['*'];
				}
 			} 
 			
 		}
 		return ca_objects::$s_current_location_type_configuration_cache[caMakeCacheKeyFromOptions($pa_options, "{$vs_table_name}/{$pm_current_loc_subclass}")] = ca_objects::$s_current_location_type_configuration_cache[$vs_cache_key] = null;
 	}
 	# ------------------------------------------------------
 	# Object checkout 
 	# ------------------------------------------------------
 	/**
	 * Determine if the currently loaded object may be marked as loaned out using the library checkout system.
	 * Only object types configured for checkout in the object_checkout.conf configuration file may be used with the checkout system
	 *
	 * @return bool 
	 */
 	public function canBeCheckedOut() {
 		if (!$this->getPrimaryKey()) { return false; }
 		return ca_object_checkouts::getObjectCheckoutConfigForType($this->getTypeCode()) ? true : false;
 	}
	# ------------------------------------------------------
	/**
	 * Return checkout status for currently loaded object. By default a numeric constant is returned. Possible values are:
	 *		__CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__ (0)
	 *		__CA_OBJECTS_CHECKOUT_STATUS_OUT__ (1)
	 *		__CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__ (2)	
	 *		__CA_OBJECTS_CHECKOUT_STATUS_RESERVED__ (3)	
	 * 
	 * If the returnAsText option is set then a text status value suitable for display is returned. For detailed information
	 * about the current status use the returnAsArray option, which returns an array with the following elements:
	 *
	 *		status = numeric status code
	 *		status_display = status display text
	 *		user_id = The user_id of the user who checked out the object (if status is __CA_OBJECTS_CHECKOUT_STATUS_OUT__ or __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__)
	 *		user_name = Displayable user name and email 
	 *		checkout_date = Date of checkout (if status is __CA_OBJECTS_CHECKOUT_STATUS_OUT__ or __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__) as printable text
	 *
	 * @param array $pa_options Options include:
	 *		returnAsText = return status as displayable text [Default=false]
	 *		returnAsText = return detailed status information in an array [Default=false]
	 * @return mixed Will return a numeric status code by default; status text for display if returnAsText option is set; or an array with details on the checkout if the returnAsArray option is set. Will return null if not object is loaded or if the object may not be checked out.
	 */
	public function getCheckoutStatus($pa_options=null) {
		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
		
		if (!$this->canBeCheckedOut()) { return null; }
		
		$vb_return_as_text = caGetOption('returnAsText', $pa_options, false);
		$vb_return_as_array = caGetOption('returnAsArray', $pa_options, false);
		
		$t_checkout = new ca_object_checkouts();
		$va_is_out = $t_checkout->objectIsOut($vn_object_id);
		$va_reservations = $t_checkout->objectHasReservations($vn_object_id);
		$vn_num_reservations = sizeof($va_reservations);
		$vb_is_reserved = is_array($va_reservations) && sizeof($va_reservations);
		
		$va_info = array('user_name' => null, 'checkout_date' => null, 'user_id' => null, 'due_date' => null, 'checkout_notes' => null);

		$vb_is_unavailable = false;
		$o_lib_conf = caGetLibraryServicesConfiguration();
		if($va_restrict_to_circ_statuses = $o_lib_conf->get('restrict_to_circulation_statuses')) {
			if(sizeof($va_restrict_to_circ_statuses)) {
				if(!in_array($this->get('circulation_status_id', ['convertCodesToIdno' => true]), $va_restrict_to_circ_statuses)) {
					$vb_is_unavailable = true;
				}
			}
		}

		if ($va_is_out) {
			$t_checkout->load($va_is_out['checkout_id']);
			$va_info['status'] = $vb_is_reserved ? __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__ : __CA_OBJECTS_CHECKOUT_STATUS_OUT__;
			$va_info['status_display'] = $vb_is_reserved ? ((($vn_num_reservations == 1) ? _t('Out; %1 reservation', $vn_num_reservations) : _t('Out; %1 reservations', $vn_num_reservations))) : _t('Out');
			$va_info['user_id'] = $va_is_out['user_id'];
			$va_info['checkout_date'] = $t_checkout->get('checkout_date', array('timeOmit' => true));
			$va_info['checkout_notes'] = $t_checkout->get('checkout_notes');
			$va_info['due_date'] = $t_checkout->get('due_date', array('timeOmit' => true));
			$va_info['user_name'] = $t_checkout->get('ca_users.fname').' '.$t_checkout->get('ca_users.lname').(($vs_email = $t_checkout->get('ca_users.email')) ? " ({$vs_email})" : '');
		} elseif ($vb_is_reserved) {
			$va_info['status'] = __CA_OBJECTS_CHECKOUT_STATUS_RESERVED__;
			$va_info['status_display'] = ($vn_num_reservations == 1) ? _t('Reserved') : _t('%1 reservations', $vn_num_reservations);
		} elseif($vb_is_unavailable) {
			$va_info['status'] = __CA_OBJECTS_CHECKOUT_STATUS_UNAVAILABLE__;
			$va_info['status_display'] = _t('Unavailable');
		} else {
			$va_info['status'] = __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__;
			$va_info['status_display'] = _t('Available');
		}
		
		if ($vb_return_as_array) { return $va_info; }
		if ($vb_return_as_text) { return $va_info['status_display']; }
		return $va_info['status'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCheckoutHistory($pa_options=null) {
		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
		
		$t_checkout = new ca_object_checkouts();
		return is_array($va_history = $t_checkout->objectHistory($vn_object_id)) ? $va_history : array();
	}
	# ------------------------------------------------------
	/**
	 * Return list of pending reservations for object. Each item in the list is an array with the following elements
	 * 
	 *
	 * @param array $pa_options No options are currently supported
	 * @return array A list of pending reservations, or null if not object is loaded
	 */
	public function getCheckoutReservations($pa_options=null) {
		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
		
		$t_checkout = new ca_object_checkouts();
		$va_reservations = $t_checkout->objectHasReservations($vn_object_id);
		$vb_is_reserved = is_array($va_reservations) && sizeof($va_reservations);
		
		return $vb_is_reserved ? $va_reservations : array();
	}
	# ------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param null|array $pa_bundle_settings
	 * @param null|array $pa_options
	 * @return string
	 */
	public function getObjectCirculationStatusHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');

		if(!is_array($pa_options)) { $pa_options = array(); }

		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		$o_view->setVar('settings', $pa_bundle_settings);
		$o_view->setVar('t_subject', $this);

		return $o_view->render('ca_object_circulation_status_html.php');
	}
	# --------------------------------------------------------------------------------------------
	#
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function renderBundleForDisplay($ps_bundle_name, $pn_row_id, $pa_values, $pa_options=null) {
		switch($ps_bundle_name) {
			case 'ca_objects_location':
			case 'ca_objects_location_date':
				
				if ((method_exists($this, "getObjectHistory")) && (is_array($va_bundle_settings = $this->_processObjectHistoryBundleSettings(['useAppConfDefaults' => true]))) && (sizeof($va_bundle_settings) > 0)) {
					$t_object = new ca_objects($pn_row_id);
					//
					// Output current "location" of object in life cycle. Configuration is taken from a ca_objects_history bundle configured for the current editor
					//
					if (is_array($va_history = $t_object->getObjectHistory($va_bundle_settings, array('limit' => 1, 'currentOnly' => true))) && (sizeof($va_history) > 0)) {
						$va_current_location = array_shift(array_shift($va_history));
                        
                        $va_path_components = caGetOption('pathComponents', $pa_options, null);
                        if (is_array($va_path_components) && $va_path_components['subfield_name']) {
                            if (($t_loc = Datamodel::getInstanceByTableName($va_current_location['type'], true)) && $t_loc->load($va_current_location['id'])) {
                                return $t_loc->get($va_current_location['type'].'.'.$va_path_components['subfield_name']);
                            }
                        } 
                        return ($ps_bundle_name == 'ca_objects_location_date') ? $va_current_location['date'] : $va_current_location['display'];
					}
				} elseif (method_exists($this, "getLastLocationForDisplay")) {
					// If no ca_objects_history bundle is configured then display the last storage location
					return $this->getLastLocationForDisplay("^ca_storage_locations.hierarchy.preferred_labels.name%delimiter=_➜_", ['object_id' => $pn_row_id]);
				}
				return '';
				break;
		}
		
		return null;
	}
	# ------------------------------------------------------
}
