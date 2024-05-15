<?php
/** ---------------------------------------------------------------------
 * app/models/ca_collections.php : table access class for table ca_collections
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/RepresentableBaseModel.php");
require_once(__CA_LIB_DIR__."/HistoryTrackingCurrentValueTrait.php");
require_once(__CA_LIB_DIR__."/DeaccessionTrait.php");

BaseModel::$s_ca_models_definitions['ca_collections'] =  array(
	'NAME_SINGULAR' 	=> _t('collection'),
 	'NAME_PLURAL' 		=> _t('collections'),
 	'FIELDS' 			=> array(
		'collection_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this collection')
		),
		'parent_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'LABEL' => 'Parent id', 'DESCRIPTION' => 'Identifier for parent of collection'
		),
		'locale_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DISPLAY_FIELD' => array('ca_locales.name'),
			'DEFAULT' => '',
			'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the collection originates.')
		),
		'type_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LIST_CODE' => 'collection_types',
			'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the collection. In CollectiveAccess every collection has a single "instrinsic" type that determines the set of descriptive and administrative metadata that can be applied to it.')
		),
		'idno' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Collection identifier'), 'DESCRIPTION' => _t('A unique alphanumeric identifier for this collection.'),
			'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 255, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Idno sort', 'DESCRIPTION' => 'Sortable version of value in idno',
			'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Sortable object identifier as integer', 'DESCRIPTION' => 'Integer value used for sorting objects; used for idno range query.'
		),
		'source_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LIST_CODE' => 'collection_sources',
			'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of the collection. This value is often used to indicate the administrative sub-division or legacy database from which the collection originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'source_info' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for collection information retrieved via web services [NOT IMPLEMENTED YET].'
		),
		'home_location_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Home location'), 'DESCRIPTION' => _t('The customary storage location for this collection.')
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
			'LABEL' => _t('Is deaccessioned?'), 'DESCRIPTION' => _t('Check if collection is deaccessioned')
		),
		'deaccession_date' => array(
			'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'DONT_ALLOW_IN_UI' => true,
			'START' => 'deaccession_sdatetime', 'END' => 'deaccession_edatetime',
			'LABEL' => _t('Date of deaccession'), 'DESCRIPTION' => _t('Enter the date the collection was deaccessioned.')
		),
		'deaccession_disposal_date' => array(
			'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'DONT_ALLOW_IN_UI' => true,
			'START' => 'deaccession_disposal_sdatetime', 'END' => 'deaccession_disposal_edatetime',
			'LABEL' => _t('Date of disposal'), 'DESCRIPTION' => _t('Enter the date the deaccessioned collection was disposed of.')
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
		'hier_collection_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Collection hierarchy', 'DESCRIPTION' => 'Identifier of collection that is root of the collection hierarchy.'
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
			'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if collection information is accessible to the public or not. ')
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
			'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the collection record.')
		),
		'deleted' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the collection is deleted or not.'),
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
		'acl_inherit_from_parent' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'BOUNDS_CHOICE_LIST' => array(
				_t('Do not inherit item-level access settings from parents') => 0,
				_t('Inherit item-level access settings from parents') => 1
			),
			'LABEL' => _t('Inherit item-level access settings from parents?'), 'DESCRIPTION' => _t('Determines whether access settings set for parent collections are applied to this collection.')
		),
		'view_count' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'View count', 'DESCRIPTION' => _t('Number of views for this record.')
		),
		'submission_user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted by user'), 'DESCRIPTION' => _t('User submitting this collection')
		),
		'submission_group_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted for group'), 'DESCRIPTION' => _t('Group this collection was submitted under')
		),
		'submission_status_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LIST_CODE' => 'submission_statuses',
			'LABEL' => _t('Submission status'), 'DESCRIPTION' => _t('Indicates submission status')
		),
		'submission_via_form' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Submission via form'), 'DESCRIPTION' => _t('Indicates what contribute form was used to create the submission')
		),
		'submission_session_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Submission session'), 'DESCRIPTION' => _t('Indicates submission session')
		),
	)
);

class ca_collections extends RepresentableBaseModel implements IBundleProvider {
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
	protected $TABLE = 'ca_collections';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'collection_id';

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
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_collections';
	protected $HIERARCHY_ID_FLD				=	'hier_collection_id';
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
	protected $LABEL_TABLE_NAME = 'ca_collection_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';				// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'collection_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Sources
	# ------------------------------------------------------
	protected $SOURCE_ID_FLD = 'source_id';					// name of source field for this table
	protected $SOURCE_LIST_CODE = 'collection_sources';		// list code (ca_lists.list_code) of list defining sources for this table

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_collections_x_collections';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)

	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'CollectionSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'CollectionSearchResult';
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	/** 
	 * Other tables can inherit ACL from this one
	 */
	protected $SUPPORTS_ACL_INHERITANCE = true;
	
	/**
	 * List of tables that can inherit ACL from this one
	 */
	protected $ACL_INHERITANCE_LIST = array('ca_objects');
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
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
	public function __construct($id=null, ?array $options=null) {
		
		if (!is_null(BaseModel::$s_ca_models_definitions['ca_collections']['FIELDS']['acl_inherit_from_parent']['DEFAULT'])) {
			$o_config = Configuration::load();
			BaseModel::$s_ca_models_definitions['ca_collections']['FIELDS']['acl_inherit_from_parent']['DEFAULT'] = (int)$o_config->get('ca_collections_acl_inherit_from_parent_default');
		}
		parent::__construct($id, $options);	# call superclass constructor
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_object_representations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Media representations'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
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
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lot'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related sets'));
		$this->BUNDLES['ca_sets_checklist'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['ca_item_tags'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Tags'));
		$this->BUNDLES['ca_item_comments'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Comments'));
		
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		
		$this->BUNDLES['ca_tour_stops'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related tour stops'));

		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
		
		$this->BUNDLES['ca_objects_deaccession'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Deaccession status'));
		
		$this->BUNDLES['authority_references_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('References'));
		
		$this->BUNDLES['history_tracking_current_value'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History tracking – current value'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_current_date'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current history tracking date'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_chronology'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History'));
		$this->BUNDLES['history_tracking_current_contents'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current contents'));
		
		$this->BUNDLES['generic'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Display template'));
	}
	# ------------------------------------------------------
	/**
	 * Override BaseModel::hierarchyWithTemplate() to optionally include top level of objects in collection hierarchy when 
	 * object-collection hierarchies are enabled. 
	 * 
	 * @param string $ps_template 
	 * @param array $pa_options Any options supported by BaseModel::getHierarchyAsList() and caProcessTemplateForIDs() as well as:
	 *		sort = An array or semicolon delimited list of elements to sort on. [Default is null]
	 * 		sortDirection = Direction to sorting, either 'asc' (ascending) or 'desc' (descending). [Default is asc]
	 *		includeObjects = Include top level of objects in collection hierarchy when object-collection hierarchies are enabled. id values for included objects will be prefixed with "ca_objects:" [Default is true]
	 *		objectTemplate = Display template to use for included objects. [Default is to use the value set in app.conf in the "ca_objects_hierarchy_browser_display_settings" directive]
	 * @return array
	 */
	public function hierarchyWithTemplate($ps_template, $pa_options=null) {
		$va_vals = parent::hierarchyWithTemplate($ps_template, $pa_options);
		if (
			$this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && 
			caGetOption('includeObjects', $pa_options, true) && 
			($rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type')) &&
			($ps_object_template = caGetOption('objectTemplate', $pa_options, $this->getAppConfig()->get('ca_objects_hierarchy_browser_display_settings')))
		) {
			$va_collection_ids = array_map(function($v) { return $v['id']; }, $va_vals);
			
			$qr = ca_objects_x_collections::find(['collection_id' => ['IN', $va_collection_ids], 'type_id' => $rel_type], ['returnAs' => 'searchResult']);
			$va_objects_by_collection = [];
			while($qr->nextHit()) {
				$va_objects_by_collection[$qr->get('ca_objects_x_collections.collection_id')][] = $qr->get('ca_objects_x_collections.object_id');
 			}
 			
 			$t_obj = new ca_objects();
 			$va_objects = [];
 			
 			if ($this->getAppConfig()->get('ca_collections_hierarchy_summary_show_full_object_hierarachy')) {
                foreach($va_objects_by_collection as $vn_collection_id => $va_object_ids) {
                    foreach($va_object_ids as $vn_object_id) {
                        $va_objects[$vn_collection_id][$vn_object_id] = $t_obj->hierarchyWithTemplate($ps_object_template, ['object_id' => $vn_object_id, 'returnAsArray' => true, 'sort' => $this->getAppConfig()->get('ca_objects_hierarchy_browser_sort_values'), 'sortDirection' => $this->getAppConfig()->get('ca_objects_hierarchy_browser_sort_direction')]);
                    }
                }
            
                $va_vals_proc = [];
                foreach($va_vals as $vn_i => $va_val) {
                    $va_vals_proc[] = $va_val;
                    if(isset($va_objects[$va_val['id']])) {
                        foreach($va_objects[$va_val['id']] as $vn_object_id => $va_object_hierarchy) {
                            foreach($va_object_hierarchy as $va_obj) {
                                $va_vals_proc[] = [
                                    'level' => $va_val['level'] + (int)$va_obj['level'],
                                    'id' => "ca_objects:".$va_obj['id'],
                                    'parent_id' => "ca_collections:{$va_val['id']}",
                                    'display' => $va_obj['display']
                                ];
                            }
                        }
                    }
                }
            } else {
                foreach($va_objects_by_collection as $vn_collection_id => $va_object_ids) {
                    $va_objects[$vn_collection_id] = caProcessTemplateForIDs($ps_object_template, 'ca_objects', $va_object_ids, ['returnAsArray' => true, 'sort' => $this->getAppConfig()->get('ca_objects_hierarchy_browser_sort_values'), 'sortDirection' => $this->getAppConfig()->get('ca_objects_hierarchy_browser_sort_direction')]);
                }
            
                $va_vals_proc = [];
                foreach($va_vals as $vn_i => $va_val) {
                    $va_vals_proc[] = $va_val;
                    if(isset($va_objects[$va_val['id']])) {
                        foreach($va_objects[$va_val['id']] as $vn_j => $vs_object_display_value) {
                            $va_vals_proc[] = [
                                'level' => $va_val['level'] + 1,
                                'id' => "ca_objects:".$va_objects_by_collection[$va_val['id']][$vn_j],
                                'parent_id' => "ca_collections:{$va_val['id']}",
                                'display' => $vs_object_display_value
                            ];
                        }
                    }
                }
            }
 			$va_vals = $va_vals_proc;
		}
		
		return $va_vals;
	}
	# ------------------------------------------------------
}
