<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_lots.php : table access class for table ca_object_lots
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2023 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__."/ca_objects.php");

BaseModel::$s_ca_models_definitions['ca_object_lots'] = array(
 	'NAME_SINGULAR' 	=> _t('object lot'),
 	'NAME_PLURAL' 		=> _t('object lots'),
 	'FIELDS' 			=> array(
 		'lot_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this lot')
		),
		'type_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LIST_CODE' => 'object_lot_types',
			'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the object lot. In CollectiveAccess every lot has a single "instrinsic" type that determines the set of descriptive and administrative metadata that can be applied to it.')
		),
		'lot_status_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'NOT_MANDATORY' => true,
			'LIST_CODE' => 'object_lot_statuses',
			'LABEL' => _t('Accession status'), 'DESCRIPTION' => _t('Indicates accession/collection status of lot. (eg. accessioned, pending accession, loan, non-accessioned item, etc.)')
		),
		'idno_stub' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Lot identifier'), 'DESCRIPTION' => _t('Unique alphanumeric code identifying the lot.'),
			'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_stub_sort' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 255, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Idno stub sort', 'DESCRIPTION' => 'Sortable version of idno_stub',
			'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_stub_sort_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Sortable object identifier as integer', 'DESCRIPTION' => 'Integer value used for sorting objects; used for idno range query.'
		),
		'extent' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Extent'), 'DESCRIPTION' => _t('The extent of the object lot. This is typically the number of discrete items that compose the lot by this record. It is stored as a whole number (eg. 1, 2, 3...).')
		),
		'extent_units' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Extent units'), 'DESCRIPTION' => _t('Units of extent value. (eg. pieces, items, components, reels, etc.)'),
			'BOUNDS_LENGTH' => array(0,255)
		),
		'home_location_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Home location'), 'DESCRIPTION' => _t('The customary storage location for this lot.')
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
			'LABEL' => _t('Is deaccessioned?'), 'DESCRIPTION' => _t('Check if lot is deaccessioned')
		),
		'deaccession_date' => array(
			'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'DONT_ALLOW_IN_UI' => true,
			'START' => 'deaccession_sdatetime', 'END' => 'deaccession_edatetime',
			'LABEL' => _t('Date of deaccession'), 'DESCRIPTION' => _t('Enter the date the lot was deaccessioned.')
		),
		'deaccession_disposal_date' => array(
			'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'DONT_ALLOW_IN_UI' => true,
			'START' => 'deaccession_disposal_sdatetime', 'END' => 'deaccession_disposal_edatetime',
			'LABEL' => _t('Date of disposal'), 'DESCRIPTION' => _t('Enter the date the deaccessioned lot was disposed of.')
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
		'access' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'BOUNDS_CHOICE_LIST' => array(
				_t('Not accessible to public') => 0,
				_t('Accessible to public') => 1
			),
			'LIST' => 'access_statuses',
			'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if lot is accessible to the public or not. ')
		),
		'status' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'BOUNDS_CHOICE_LIST' => array(
				_t('Newly created') => 0,
				_t('Editing in progress') => 1,
				_t('Editing complete - pending review') => 2,
				_t('Review in progress') => 3,
				_t('Completed') => 4
			),
			'LIST' => 'workflow_statuses',
			'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the object lot record.')
		),
		'source_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LIST_CODE' => 'object_lot_sources',
			'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of lot. This value is often used to indicate the administrative sub-division or legacy database from which the object originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'source_info' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for object lot information retrieved via web services [NOT IMPLEMENTED YET].'
		),
		'deleted' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the object lot is deleted or not.'),
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
			'LABEL' => _t('Submitted by user'), 'DESCRIPTION' => _t('User submitting this object lot')
		),
		'submission_group_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted for group'), 'DESCRIPTION' => _t('Group this object lot was submitted under')
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
		)
 	)
);

class ca_object_lots extends RepresentableBaseModel {
	use HistoryTrackingCurrentValueTrait;
	use DeaccessionTrait;
	
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
	protected $TABLE = 'ca_object_lots';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'lot_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('idno_stub');

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
	protected $ORDER_BY = array('idno_stub');

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
	protected $LABEL_TABLE_NAME = 'ca_object_lot_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';				// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'object_lot_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Sources
	# ------------------------------------------------------
	protected $SOURCE_ID_FLD = 'source_id';					// name of source field for this table
	protected $SOURCE_LIST_CODE = 'object_lot_sources';		// list code (ca_lists.list_code) of list defining sources for this table

	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno_stub';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_stub_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_object_lots_x_object_lots';
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ObjectLotSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ObjectLotSearchResult';
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Cache for object counts used by ca_object_lots::numObjects()
	 *
	 * @see ca_object_lots::numObjects()
	 */
	static $s_object_count_cache = array();
	

	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_object_representations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Media representations'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lots'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related sets'));
		$this->BUNDLES['ca_sets_checklist'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['ca_item_tags'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Tags'));
		$this->BUNDLES['ca_item_comments'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Comments'));
		
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
	 * Override insert() to check type_id (or whatever the type key is called in the table as returned by getTypeFieldName())
	 * against the ca_lists list for the table (as defined by getTypeListCode())
	 */ 
	public function insert($pa_options=null) {
		if(!$this->get('lot_status_id')) {
			$this->set('lot_status_id', caGetDefaultItemID('object_lot_statuses'));
		}
		return parent::insert($pa_options);
	}
	# ------------------------------------------------------
 	/**
 	 * Unlinks any ca_objects rows related to the currently loaded ca_object_lots record. Note that this does *not*
 	 * delete the related objects. It only removes their link to this lot.  Note that on error, the database maybe left in 
 	 * an inconsistent state where some objects are still linked to the lot. If you want to prevent this then wrap your
 	 * call to removeAllObjects in a transaction and rollback the transaction on error.
 	 *
 	 * @return boolean Returns true on success, false if there were errors.
 	 */
 	 public function removeAllObjects() {
 	 	if (!($vn_lot_id = $this->getPrimaryKey())) {
			return null;
 	 	}
 	
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
				SELECT object_id
				FROM ca_objects
				WHERE
					lot_id = ? AND deleted = 0
			", (int)$vn_lot_id);
			
		$t_object = new ca_objects();
		if ($o_t = $this->getTransaction()) {
			$t_object->setTransaction($o_t);
		}
		
		$vb_errors = false;
		while($qr_res->nextRow()) {
			if ($t_object->load($qr_res->get('object_id'))) {
				$t_object->setMode(ACCESS_WRITE);
				$t_object->set('lot_id', null);
				$t_object->update();
				
				if ($t_object->numErrors()) {
					$this->errors = array_merge($this->errors, $t_object->errors);
					$vb_errors = true;
				}
			}
		}
		
		if ($t_object->inTransaction()) {
			$t_object->removeTransaction(true);
		}
		
		return !$vb_errors;	// return true if no errors, false if errors
	}
	# ------------------------------------------------------
 	/**
 	 * Returns the number of ca_object rows related to the currently loaded object lot.
 	 *
 	 * @param int $pn_lot_id Optional lot_id to get object count for; if null then the id of the currently loaded lot will be used
 	 * @param array $pa_options Options include:
 	 *		return = Set to "components" to return the count of component objects only; "objects" to return the count of objects (but not components) or "all" to return a count of any kind of object. [Default = "all"]
 	 *		noCache = If set cached object counts are generated from the database and any cached counts are ignored. [Default = false]
 	 * @return int Number of objects related to the object lot or null if $pn_lot_id is not set and there is no currently loaded lot
 	 */
 	 public function numObjects($pn_lot_id=null, $pa_options=null) {
 	 	if(!is_array($pa_options)) { $pa_options = []; }
 	 	$vn_lot_id = $this->getPrimaryKey();
 	 	if ($pn_lot_id && ($pn_lot_id != $vn_lot_id)) {
 	 		$vn_lot_id = $pn_lot_id;
 	 	}
 	 	
 	 	$pb_no_cache = caGetOption('noCache', $pa_options, false);
 	 	
 	 	if (!$pb_no_cache) {
 	 		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options);
 	 		if (isset(ca_object_lots::$s_object_count_cache[$vn_lot_id][$vs_cache_key])) {
 	 			return ca_object_lots::$s_object_count_cache[$vn_lot_id][$vs_cache_key];
 	 		}
 	 	}
 	 	return sizeof($this->getObjects($pn_lot_id, $pa_options));
	}
	# ------------------------------------------------------
 	/**
 	 * Returns a list of ca_object rows related to the currently loaded object lot.
 	 *
 	 * @param int $pn_lot_id Optional lot_id to get object list for; if null then the id of the currently loaded lot will be used
 	 * @param array $pa_options Options include:
 	 *		return = Set to "components" to return the count of component objects only; "objects" to return the count of objects (but not components) or "all" to return a count of any kind of object. [Default = "all"]
 	 *		excludeChildObjects = Only return top-level objects, excluding sub-objects. [Default is false]
 	 * @return array List of objects related to the object lot or null if $pn_lot_id is not set and there is no currently loaded lot
 	 */
 	 public function getObjects($pn_lot_id=null, $pa_options=null) {
 	 	if(!is_array($pa_options)) { $pa_options = []; }
 	 	$vn_lot_id = $this->getPrimaryKey();
 	 	if ($pn_lot_id && ($pn_lot_id != $vn_lot_id)) {
 	 		$vn_lot_id = $pn_lot_id;
 	 	}
 	 	
 	 	$ps_return = caGetOption('return', $pa_options, 'all');
 	 	$vs_cache_key = caMakeCacheKeyFromOptions($pa_options);
 	 	
		if (is_array($va_component_types = $this->getAppConfig()->getList('ca_objects_component_types')) && sizeof($va_component_types)) {
			$va_component_types = caMakeTypeIDList('ca_objects', $va_component_types);
		}
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
				SELECT object_id
				FROM ca_objects
				WHERE
					lot_id = ? AND deleted = 0 ".(caGetOption('excludeChildObjects', $pa_options, false) ? " AND parent_id IS NULL" : "")."
				ORDER BY
					idno_sort
			", (int)$vn_lot_id);
	
		$va_rows = array();
		while($qr_res->nextRow()) {
			$va_rows[$qr_res->get('object_id')] = 1;
		}
		if (!sizeof($va_rows)) { ca_object_lots::$s_object_count_cache[$vn_lot_id][$vs_cache_key] = 0; return array(); }
		
		$va_rows = array_merge(array_keys($va_rows), ca_objects::getHierarchyChildrenForIDs(array_keys($va_rows), ['returnAs' => 'ids']));
	
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_objects
			WHERE
				object_id IN (?) AND deleted = 0 ".(caGetOption('excludeChildObjects', $pa_options, false) ? " AND parent_id IS NULL" : "")."
			ORDER BY
				idno_sort
		", array($va_rows));
	
		$va_objects = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			if (($ps_return == 'objects') && in_array($va_row['type_id'], $va_component_types)) { continue; }
			if (($ps_return == 'components') && !in_array($va_row['type_id'], $va_component_types)) { continue; }
			$va_objects[$va_row['object_id']] = $va_row;
		}
				
		ca_object_lots::$s_object_count_cache[$vn_lot_id][$vs_cache_key] = sizeof($va_objects); 
 	 	return $va_objects;
	}
	# ------------------------------------------------------
 	/**
 	 * 
 	 * @param array $pa_options Options include:
 	 *      reindex = force any idno that is out of the current 1-based index to be renumbered. Eg. if there are 12 objects in the lot, any one numbered > 12 will be renumbered to be between 1 and 12. [Default is false]
 	 * @return array List of objects with non-conforming idnos, or false if there are no non-conforming objects
 	 */
 	 public function getObjectsWithNonConformingIdnos($pa_options=null) {
 	 	if (!$this->getPrimaryKey()) { return false; }
		
		$t_object = new ca_objects();
		$t_idno = $t_object->getIDNoPlugInInstance();
		$vs_separator = $t_idno->getSeparator();
		$va_objects = $this->getObjects();
		$vs_lot_num = $this->get('idno_stub');
		$va_lot_num = explode($vs_separator, $vs_lot_num);
		
		$va_non_conforming_objects= array();
		
		$c = sizeof($va_objects);
		$seen_idnos = [];
		foreach($va_objects as $va_object) {
			if (!preg_match("!^{$vs_lot_num}{$vs_separator}!", $va_object['idno'])) {
				$va_non_conforming_objects[$va_object['object_id']] = array_merge($va_object, ['_reason' => 'out-of-lot']);
				$seen_idnos[$va_object['idno']] = true;
				continue;
			}
			
			// Detect dupes
			if(isset($seen_idnos[$va_object['idno']])) {
			    $va_non_conforming_objects[$va_object['object_id']] = array_merge($va_object, ['_reason' => 'dupe']);
			    continue;
			}
			
			$seen_idnos[$va_object['idno']] = true;
			
			if(caGetOption('reindex', $pa_options, false)) {
                $tmp = explode($vs_separator, $va_object['idno']);
                $n = (int)$tmp[sizeof($va_lot_num)];
                if ($n > $c) { 
                    $va_non_conforming_objects[$va_object['object_id']] = array_merge($va_object, ['_reason' => 'out-of-range']);
                    continue;
                }
            }
		}
		
		return (is_array($va_non_conforming_objects) && sizeof($va_non_conforming_objects)) ? $va_non_conforming_objects : false;
	}
	# ------------------------------------------------------
 	/**
 	 * 
 	 *
 	 * @return boolean 
 	 */
 	 public function renumberObjects($po_application_plugin_manager=null) {
 	 	if (!$this->getPrimaryKey()) { return false; }
		
		if ($va_non_conforming_objects = $this->getObjectsWithNonConformingIdnos()) {
			$va_objects = $this->getObjects();
			$vs_lot_num = $this->get('idno_stub');
			
			$t_object = new ca_objects();
			
			$vb_web_set_transaction = false;
			if (!$this->inTransaction()) {
				$o_trans = new Transaction($this->getDb());
				$vb_web_set_transaction = true;
			} else {
				$o_trans = $this->getTransaction();
			}
			$t_object->setTransaction($o_trans);
			$t_idno = $t_object->getIDNoPlugInInstance();
			$vs_separator = $t_idno->getSeparator();
			$va_lot_num = explode($vs_separator, $vs_lot_num);
			
			$nums = [];
			$maxnum = 0;
			foreach($va_objects as $vn_object_id => $va_object_info) {
			    $tmp = explode($vs_separator, $va_object_info['idno']);
			    $n = (int)$tmp[sizeof($va_lot_num)];
			    $nums[$n] = true;
			    if ($n > $maxnum) { $maxnum = $n; }
			}
			
			$i = 1;
			foreach($va_non_conforming_objects as $vn_object_id => $va_object_info) {
				if ($t_object->load($vn_object_id)) {
					if ($po_application_plugin_manager) {
						$po_application_plugin_manager->hookBeforeSaveItem(array('id' => $vn_object_id, 'table_num' => $t_object->tableNum(), 'table_name' => $t_object->tableName(), 'instance' => $t_object));
					}
					
					$tmp = $va_lot_num;
					
					$cur_num_tmp = explode($vs_separator, $t_object->get('idno'));
			        $n = (int)$cur_num_tmp[sizeof($va_lot_num)];
			        
			        switch($va_object_info['_reason']) {
			            case 'out-of-lot':
			                $tmp[] = $n;
			                break;
			            case 'dupe':
			                $maxnum++;
			                $tmp[] = $maxnum;
			                break;
			            case 'out-of-range':
                            while(isset($nums[$i])) {
                                $i++;
                            }
                            $tmp[] = $i;
                            $nums[$i] = true;
			                break;
			        }
			        
			        $tail = array_slice($cur_num_tmp, sizeof($tmp));
			        $tmp += $tail;
			        
			       // print join($vs_separator, $cur_num_tmp)." => ".join($vs_separator, $tmp)."<br>\n";
					
					$t_object->setIdnoWithTemplate(join($vs_separator, $tmp));
				
					$t_object->update();
					if ($t_object->numErrors()) {
						$t_object->rollback();
						$this->errors = $t_object->errors;
						return false;
					}
					if ($po_application_plugin_manager) {
						$po_application_plugin_manager->hookSaveItem(array('id' => $vn_object_id, 'table_num' => $t_object->tableNum(), 'table_name' => $t_object->tableName(), 'instance' => $t_object));
					}
				}
			}
			
			
			if ($vb_web_set_transaction) {
				$o_trans->commit();
			}
		}
		
		return true;
	}
 	# ------------------------------------------------------
}
