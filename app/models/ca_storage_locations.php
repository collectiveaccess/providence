<?php
/** ---------------------------------------------------------------------
 * app/models/ca_storage_locations.php : table access class for table ca_storage_locations
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
require_once(__CA_LIB_DIR__.'/IHierarchy.php');
require_once(__CA_LIB_DIR__."/HistoryTrackingCurrentValueTrait.php");


BaseModel::$s_ca_models_definitions['ca_storage_locations'] = array(
 	'NAME_SINGULAR' 	=> _t('storage location'),
 	'NAME_PLURAL' 		=> _t('storage locations'),
 	'FIELDS' 			=> array(
 		'location_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this storage location')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Parent id'
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LIST_CODE' => 'storage_location_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the storage location. In CollectiveAccess every storage location has a single "instrinsic" type that determines the set of descriptive and administrative metadata that can be applied to it.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Location identifier'), 'DESCRIPTION' => _t('A unique alphanumeric identifier for this location.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Sortable location identifier', 'DESCRIPTION' => 'Value used for sorting locations on identifier value.',
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
				'LIST_CODE' => 'storage_location_sources',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of storage location. This value is often used to indicate the administrative sub-division or legacy database from which the object originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for storage location information retrieved via web services [NOT IMPLEMENTED YET].'
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if location information is accessible to the public or not. ')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the storage location record.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the storage location is deleted or not.'),
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
		'color' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COLORPICKER, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Color'), 'DESCRIPTION' => _t('Color to identify the editor UI with')
		),
		'icon' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_icons',
				'LABEL' => _t('Icon'), 'DESCRIPTION' => _t('Optional icon to identify the editor UI with')
		),
		'is_enabled' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '1',
				'LABEL' => _t('Is enabled?'), 'DESCRIPTION' => _t("If unchecked this item is disabled and can't be edited or used in new relationships"),
				'BOUNDS_VALUE' => array(0,1)
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
			'LABEL' => _t('Submitted by user'), 'DESCRIPTION' => _t('User submitting this location')
		),
		'submission_group_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted for group'), 'DESCRIPTION' => _t('Group this location was submitted under')
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

class ca_storage_locations extends RepresentableBaseModel implements IBundleProvider, IHierarchy {
	use HistoryTrackingCurrentValueTrait;
	
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
	protected $TABLE = 'ca_storage_locations';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'location_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('location_id');

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
	protected $ORDER_BY = array('location_id');

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
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_SIMPLE_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
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
	protected $LABEL_TABLE_NAME = 'ca_storage_location_labels';
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'StorageLocationSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'StorageLocationSearchResult';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'storage_location_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Sources
	# ------------------------------------------------------
	protected $SOURCE_ID_FLD = 'source_id';							// name of source field for this table
	protected $SOURCE_LIST_CODE = 'storage_location_sources';		// list code (ca_lists.list_code) of list defining sources for this table

	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	

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
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lots'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related sets'));
		$this->BUNDLES['ca_sets_checklist'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['authority_references_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('References'));

		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
		
		$this->BUNDLES['ca_storage_locations_contents'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current contents of location'), 'deprecated' => true);
		
		$this->BUNDLES['history_tracking_current_value'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History tracking – current value'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_current_date'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current history tracking date'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_chronology'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History'));
		$this->BUNDLES['history_tracking_current_contents'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current contents'));
		
		$this->BUNDLES['generic'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Display template'));
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function insert($pa_options=null) {
		$we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$we_set_transaction = true;
		}

		$o_trans = $this->getTransaction();

		if (!strlen($this->get('is_enabled'))) {
			$this->set('is_enabled', 1);
		}
		$vn_rc = parent::insert($pa_options);

		$this->handleMove($pa_options);
		if ($this->numErrors()) {
			if ($we_set_transaction) { $o_trans->rollback(); }
		} else {
			if ($we_set_transaction) { $o_trans->commit(); }
		}

		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function update($pa_options=null) {
		$we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$we_set_transaction = true;
		}
		$o_trans = $this->getTransaction();
		
		$parent_changed = $this->changed('parent_id');
		$rc = parent::update($pa_options);
		if($parent_changed) { $this->handleMove($pa_options); }
		
		if ($this->numErrors()) {
			if ($we_set_transaction) { $o_trans->rollback(); }
		} else {
			if ($we_set_transaction) { $o_trans->commit(); }
		}
		return $rc;
	}
	# ------------------------------------------------------
	/**
	 * Return array containing information about all storage location hierarchies, including their root_id's
	 */
	 public function getHierarchyList($pb_dummy=false) {
		$vn_root_id = $this->getHierarchyRootID();
		$t_root = new ca_storage_locations($vn_root_id);
		$qr_children = $t_root->getHierarchyChildrenAsQuery();
		$va_preferred_labels = $t_root->getPreferredLabels(null, false);
		
		return array(array(
			'location_id' => $vn_id = $t_root->getPrimaryKey(),
			'item_id' => $vn_id,
			'name' => _t('Storage locations'),
			'children' => $qr_children->numRows(),
			'has_children' => $qr_children->numRows() ? true : false
		));
	 }
	# ------------------------------------------------------
	/**
	 * Returns name of hierarchy for currently loaded storage location, which is *always* "Storage locations"
	 * $pn_id is always ignored and is optional. The parameter is included for consistency with getHierarchyName() implementations
	 * in other models (ca_objects, ca_places, ca_list_items, etc.)
	 */
	 public function getHierarchyName($pn_id=null) {
	 	return _t('Storage locations');
	 }
	# ------------------------------------------------------
	/**
	 * Override BundleableLabelableBaseModelWithAttributes::saveBundlesForScreen() to create
	 * related movement record when storage location is moved
	 */
	public function saveBundlesForScreen($screen, $request, &$options) {
		$parent_changed = (parent::saveBundlesForScreenWillChangeParent($screen, $request, $options) == __CA_PARENT_CHANGED__); 
		
		$old_parent_id = (int)$this->get('ca_storage_locations.parent_id');
		if (!$this->getPrimaryKey() && ($old_parent_id <= 0)) {   // For new records zero or negative parent_id means root
		    $request->setParameter('parent_id', $this->getHierarchyRootID());
		}
		
		$we_set_transaction = false;
		if ($parent_changed && !$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$we_set_transaction = true;
		}
		if (($rc = parent::saveBundlesForScreen($screen, $request, $options)) && $parent_changed) {
			$new_parent_id = (int)$this->get('ca_storage_locations.parent_id');
			
			if ($old_parent_id === $new_parent_id) { return $rc; }	// don't track if there's no actual movement
				
			unset($options['ui_instance']);
	
			// Get list of policies that involve movements – we'll  need to generate movement records for these policies if they are so configured.
			if (!is_array($policies = ca_movements::getDependentHistoryTrackingCurrentValuePolicies('ca_movements'))) { return $rc; }
			
			// Look for incoming movement form attached to hierarcy_location bundle
			$movement_form_name = $movemenr_form_screen = null;
			foreach($_REQUEST as $key => $val) {
				if (preg_match('!^(.*)_movement_form_name$!', $key, $matches)) {
					$movement_form_name = $request->getParameter($matches[1].'_movement_form_name', pString);
					$movement_form_screen = $request->getParameter($matches[1].'_movement_screen', pString); 					
				
					break;
				}
			}
			
			if ($movement_form_name && $movement_form_screen) {
				foreach($policies as $policy_code => $policy) {
					if (!is_array($policy_elements = caGetOption('elements', $policy, null))) { continue; }
				
					if(isset($policy_elements['ca_movements'])) {
						// Get movement config for this policy
						$policy_config = null;
						foreach($policy_elements['ca_movements'] as $mt => $mi) {
							if($mi['useRelatedRelationshipType'] && $mi['useRelated']) {
								$movement_type = ($mt === '__default__') ? null : $mt;
								$policy_config = $mi;
								break;
							}	
						}
						if(!$policy_config) { continue; }	// movements are not configured for storage location tracking 
															// (useRelated and useRelatedRelationshipType options must be set)
															
					
						// Create new movement record for changing in parent of this location
						$t_movement = new ca_movements();
						
						// Check movement type
						if (!$movement_type || !$t_movement->getTypeIDForCode($movement_type)) { $movement_type = $t_movement->getDefaultTypeID(); }	
						
						if($this->inTransaction()) { $t_movement->setTransaction($this->getTransaction()); }
						$t_movement->set('type_id', $movement_type);
					
						// Save movement
						$movement_opts = array_merge($options, ['formName' => $movement_form_name]);
						if(!$t_movement->saveBundlesForScreen($movement_form_screen, $request, $movement_opts)) {
							if($this->inTransaction()) { $this->removeTransaction(false); }
							
							$this->postError(3600, _t('Could not create movement: %1', join("; ", $t_movement->getErrors())), 'ca_storage_locations::saveBundlesForScreen()');
							return false;
						}
						
						// Link movement to storage location
						if (!$t_movement->addRelationship('ca_storage_locations', $this->getPrimaryKey(), $policy_config['useRelatedRelationshipType'])) {
							if($this->inTransaction()) { $this->removeTransaction(false); }
							$this->postError(3605, _t('Could not create storage location - movement relationship for history tracking: %1', join($t_movement->getErrors())), 'ca_storage_locations::saveBundlesForScreen()');
							return false;
						}
						
						// Link movement to old parent location and new parent location
						if(isset($policy_config['originalLocationTrackingRelationshipType'])) {
							if (!$t_movement->addRelationship('ca_storage_locations', $old_parent_id, $policy_config['originalLocationTrackingRelationshipType'])) {
								if($this->inTransaction()) { $this->removeTransaction(false); }
								$this->postError(3610, _t('Could not create storage location - movement original parent relationship for history tracking: %1', join($t_movement->getErrors())), 'ca_storage_locations::saveBundlesForScreen()');
								return false;
							}
						}
						if(isset($policy_config['newLocationTrackingRelationshipType'])) {
							if (!$t_movement->addRelationship('ca_storage_locations', $new_parent_id, $policy_config['newLocationTrackingRelationshipType'])) {
								if($this->inTransaction()) { $this->removeTransaction(false); }
								$this->postError(3615, _t('Could not create storage location - movement new parent relationship for history tracking: %1', join($t_movement->getErrors())), 'ca_storage_locations::saveBundlesForScreen()');
								return false;
							}
						}
						
						// Link movement to all sub-locations of the current location, if sublocation rel type is configured
						if(!is_array($sub_location_ids = $this->getHierarchyIDs($this->getPrimaryKey()))) { $sub_location_ids = []; }
						
						if($policy_config['subLocationTrackingRelationshipType']) {
							foreach($sub_location_ids as $sub_location_id) {
								if (!$t_movement->addRelationship('ca_storage_locations', $sub_location_id, $policy_config['subLocationTrackingRelationshipType'])) {
									if($this->inTransaction()) { $this->removeTransaction(false); }
									$this->postError(3620, _t('Could not create storage location - movement new sub-location relationship for history tracking: %1', join($t_movement->getErrors())), 'ca_storage_locations::saveBundlesForScreen()');
									return false;
								}
							}
						}
						
						// Link movement to objects currently linked to this location, or any sub-location, via the current policy 
						$location_ids = array_unique(array_merge([$this->getPrimaryKey()], $sub_location_ids));
						
						$content_ids = array_unique($this->getContentsForIDs($policy_code, $location_ids, ['idsOnly' => true]));
				
						foreach($content_ids as $content_id) {
							if(!$t_movement->addRelationship($policy['table'], $content_id, $policy_config['trackingRelationshipType'])) {
								if($this->inTransaction()) { $this->removeTransaction(false); }
								$this->postError(3625, _t('Could not create movement - %1 relationship for history tracking: %2', Datamodel::getTableProperty($policy['table'], 'NAME_SINGULAR'), join($t_movement->getErrors())), 'ca_storage_locations::saveBundlesForScreen()');
								return false;
							}
						}
					}
			
				}
			}
		}
		
		if($we_set_transaction) { $this->removeTransaction(true); }
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Set move information for contained items if configured to do so
	 *
	 * @param array $options 
	 *
	 * @return bool
	 */
	public function handleMove($options=null) {
		if(!($new_parent_id = $this->get('parent_id'))) { return null; }
		if(!($id = $this->getPrimaryKey())) { return null; }
		
		$policies = HistoryTrackingCurrentValueTrait::getDependentHistoryTrackingCurrentValuePolicies($this->tableName(), array_merge($options ?? [], ['type_id' => $this->getTypeID()]));
		if(!is_array($policies)) { return null; }
		$type_code = $this->getTypeCode();
		foreach($policies as $policy => $policy_info) {
			$dtls = $policy_info['elements']['ca_storage_locations'][$type_code] ?? $policy_info['elements']['ca_storage_locations']['__default__'] ?? null;
			if(!is_array($dtls)) { continue; }
			
			if(!is_array($container_types = $dtls['containerTypes'] ?? null)) { continue; }
			$container_ref_element_code = $dtls['containerReferenceElementCode'] ?? null;
			if(in_array($type_code, $container_types)) {
				// We're moving a container, so apply move to all enclosed items
				if($qr_contents = $this->getContents($policy, ['expandHierarchically' => true])) {
					while($qr_contents->nextHit()) {
						$t_instance = $qr_contents->getInstance();
						$t_rel = $t_instance->addRelationship('ca_storage_locations', $id, 'related', _t('now'));
						if($container_ref_element_code) {
							$t_rel->addAttribute([$container_ref_element_code => $new_parent_id], $container_ref_element_code);
							$t_rel->update();
							
							if($this->numErrors()) {
								$this->errors = $t_rel->errors;
								return false;
							}
						}
					}
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
}
