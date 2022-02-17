<?php
/** ---------------------------------------------------------------------
 * app/models/ca_objects.php : table access class for table ca_objects
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2022 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_objects_x_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_loans_x_objects.php");
require_once(__CA_MODELS_DIR__."/ca_movements_x_objects.php");
require_once(__CA_MODELS_DIR__."/ca_objects_x_storage_locations.php");
require_once(__CA_MODELS_DIR__."/ca_object_checkouts.php");
require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
require_once(__CA_LIB_DIR__."/HistoryTrackingCurrentValueTrait.php");
require_once(__CA_LIB_DIR__."/DeaccessionTrait.php");


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
			'DONT_INCLUDE_IN_SEARCH_FORM' => true,
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
		'idno_sort_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Sortable object identifier as integer', 'DESCRIPTION' => 'Integer value used for sorting objects; used for idno range query.'
		),
		'home_location_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Home location ID'), 'DESCRIPTION' => _t('The customary storage location for this object.')
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
			'LABEL' => _t('Is deaccessioned?'), 'DESCRIPTION' => _t('Check if object is deaccessioned')
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
		'deaccession_authorized_by' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => "700px", 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Authorized by'), 'DESCRIPTION' => _t('Deaccession authorized by'),
			'BOUNDS_LENGTH' => array(0,255)
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
			'BOUNDS_VALUE' => array(0,1),
			'DONT_INCLUDE_IN_SEARCH_FORM' => true
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
		'submission_user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted by user'), 'DESCRIPTION' => _t('User submitting this object')
		),
		'submission_group_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted for group'), 'DESCRIPTION' => _t('Group this object was submitted under')
		),
		'submission_status_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LIST_CODE' => 'submission_statuses',
			'LABEL' => _t('Submission status'), 'DESCRIPTION' => _t('Indicates submission status of the object.')
		),
		'submission_via_form' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Submission via form'), 'DESCRIPTION' => _t('Indicates what contribute form was used to create the submission.')
		)
	)
);

class ca_objects extends RepresentableBaseModel implements IBundleProvider {
	use HistoryTrackingCurrentValueTrait;
	use DeaccessionTrait;

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
		$this->BUNDLES['ca_item_comments'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Comments'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
		
		$this->BUNDLES['ca_objects_components_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Components'));
		
		$this->BUNDLES['ca_objects_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current location'), 'deprecated' => true);
		$this->BUNDLES['ca_objects_location_date'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current location date'), 'deprecated' => true);
		$this->BUNDLES['ca_objects_history'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Object use history'), 'deprecated' => true);
		
		$this->BUNDLES['history_tracking_current_value'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History tracking – current value'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_current_date'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current history tracking date'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_chronology'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History tracking – chronology'));
		$this->BUNDLES['history_tracking_current_contents'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History tracking – current contents'));
		
		$this->BUNDLES['ca_objects_deaccession'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Deaccession status'));
		$this->BUNDLES['ca_object_checkouts'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Object checkouts'));
		
		$this->BUNDLES['ca_object_representations_access_status'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Media representation access and status'));
	
		$this->BUNDLES['authority_references_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('References'));

		$this->BUNDLES['ca_object_circulation_status'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Circulation Status'));
		
		
		$this->BUNDLES['submitted_by_user'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Submitted by'), 'displayOnly' => true);
		$this->BUNDLES['submission_group'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Submission group'), 'displayOnly' => true);
		
		$this->BUNDLES['home_location_value'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Home location display value'), 'displayOnly' => true);
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
	# ------------------------------------------------------
	/**
	 *
	 */
	public function renderBundleForDisplay($ps_bundle_name, $pn_row_id, $pa_values, $pa_options=null) {
		switch($ps_bundle_name) {
			case 'home_location_value':
				$q = caMakeSearchResult('ca_objects', [$pn_row_id]);
				if ($q && $q->nextHit()) {
					if(($home_location_id = $q->get('ca_objects.home_location_id')) && ($t_loc = ca_storage_locations::find(['location_id' => $home_location_id], ['returnAs' => 'firstModelInstance']))) {
						if (!($t = Configuration::load()->get('home_location_display_template'))) {
							$t = caGetOption('display_template', $pa_options, '^ca_storage_locations.hierarchy.preferred_labels');
						}
						return $t_loc->getWithTemplate($t);
					}
				}
				break;
			default:
				return self::renderHistoryTrackingBundleForDisplay($ps_bundle_name, $pn_row_id, $pa_values, $pa_options);
				break;
		}
	}	
	# ------------------------------------------------------
}
