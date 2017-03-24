<?php
/** ---------------------------------------------------------------------
 * app/models/ca_storage_locations.php : table access class for table ca_storage_locations
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2015 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/ca/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/ca/RepresentableBaseModel.php");
require_once(__CA_LIB_DIR__.'/ca/IHierarchy.php');
require_once(__CA_LIB_DIR__."/ca/BaseObjectLocationModel.php");


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
				'BOUNDS_VALUE' => array(0,1)
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
				'LABEL' => 'View count', 'DESCRIPTION' => 'Number of views for this record.'
		)
 	)
);

class ca_storage_locations extends BaseObjectLocationModel implements IBundleProvider, IHierarchy {
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
		parent::__construct($pn_id);	# call superclass constructor
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
		
		$this->BUNDLES['ca_storage_locations_contents'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current contents of location'));
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		$vb_web_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_web_set_transaction = true;
		}

		$o_trans = $this->getTransaction();

		if (!strlen($this->get('is_enabled'))) {
			$this->set('is_enabled', 1);
		}
		$vn_rc = parent::insert($pa_options);

		if ($this->numErrors()) {
			if ($vb_web_set_transaction) { $o_trans->rollback(); }
		} else {
			if ($vb_web_set_transaction) { $o_trans->commit(); }
		}

		return $vn_rc;
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
	 *
	 */
	public function getCurrentObjectIDs() {
		if (!$this->getPrimaryKey()) { return array(); }
		
		$va_object_ids = array();
		//
		// Get objects referenced via movements
		//
		if ($vs_movement_storage_element = $this->getAppConfig()->get('movement_storage_location_date_element')) {
			// Get current movements for location
			$va_movement_ids = $this->getRelatedItems('ca_movements', array('idsOnly' => true));
			if (is_array($va_movement_ids) && sizeof($va_movement_ids)) {
				// get list of objects on these movements...
				$t_movement = new ca_movements();
				$va_object_ids = $t_movement->getRelatedItems('ca_objects', array('idsOnly' => true, 'showCurrentOnly' => true, 'row_ids' => $va_movement_ids));
	
				// ... then get the list of objects for which the *current* movement is one of ours
				$t_object = new ca_objects();
				$va_current_movement_ids = $t_object->getRelatedItems('ca_movements', array('idsOnly' => false, 'showCurrentOnly' => true, 'row_ids' => $va_object_ids));
				
				$va_movement_rels = array(); 
				foreach($va_current_movement_ids as $vn_relation_id => $va_movement_info) {
					if (in_array($va_movement_info['movement_id'], $va_movement_ids)) { $va_movement_rels[] = $vn_relation_id; }
				}
				
				if (sizeof($va_movement_rels) > 0) {
					$qr_object_rels = caMakeSearchResult('ca_movements_x_objects', $va_movement_rels);
					$va_object_ids = $qr_object_rels->getAllFieldValues('ca_movements_x_objects.object_id');
				} else {
					$va_object_ids = array();
				}
			}
		}
		
		//
		// Get objects referenced via object-location relationships
		//
		$va_direct_object_ids = $this->getRelatedItems('ca_objects', array('idsOnly' => true, 'showCurrentOnly' => true));
		
		// Dedupe and return
		return array_unique(array_merge($va_object_ids, $va_direct_object_ids));
	}
	# ------------------------------------------------------
	/**
	 * Override BundleableLabelableBaseModelWithAttributes::saveBundlesForScreen() to create
	 * related movement record when storage location is moved
	 */
	public function saveBundlesForScreen($pm_screen, $po_request, &$pa_options) {
		$vb_parent_changed = (parent::saveBundlesForScreenWillChangeParent($pm_screen, $po_request, $pa_options) == __CA_PARENT_CHANGED__); 
		if (($vn_rc = parent::saveBundlesForScreen($pm_screen, $po_request, $pa_options)) && $vb_parent_changed) {
			unset($pa_options['ui_instance']);
	
			// get list of objects currently associated with this storage location
			$va_object_ids = $this->getCurrentObjectIDs();

			$vs_movement_storage_location_relationship_type = $this->getAppConfig()->get('movement_storage_location_tracking_relationship_type');
			$vs_movement_object_relationship_type = $this->getAppConfig()->get('movement_object_tracking_relationship_type');
			
			foreach($_REQUEST as $vs_key => $vs_val) {
				if (preg_match('!^(.*)_movement_form_name$!', $vs_key, $va_matches)) {
					$vs_form_name = $po_request->getParameter($va_matches[1].'_movement_form_name', pString);
					$vs_screen = $po_request->getParameter($va_matches[1].'_movement_screen', pString);
					
					if (is_array($va_object_ids) && sizeof($va_object_ids)) {
						$t_movement = new ca_movements();
						
						if($this->inTransaction()) { $t_movement->setTransaction($this->getTransaction()); }
						$t_movement->set('type_id', $t_movement->getDefaultTypeID());
						
						$va_movement_opts = array_merge($pa_options, array('formName' => $vs_form_name));
						$t_movement->saveBundlesForScreen($vs_screen, $po_request, $va_movement_opts);
		
						if ($vs_movement_storage_location_relationship_type) {
							$t_movement->addRelationship('ca_storage_locations', $this->getPrimaryKey(), $vs_movement_storage_location_relationship_type);
						}
						
						if ($vs_movement_object_relationship_type) {
							foreach($va_object_ids as $vn_object_id) {
								$t_movement->addRelationship('ca_objects', $vn_object_id, $vs_movement_object_relationship_type);
							}
						}
					}
				}
			}
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
 	/**
 	 * Returns HTML form bundle for location contents
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
 	public function getLocationContentsHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
 		require_once(__CA_MODELS_DIR__."/ca_movements.php");
 		require_once(__CA_MODELS_DIR__."/ca_movements_x_objects.php");
 		require_once(__CA_MODELS_DIR__."/ca_objects_x_storage_locations.php");
 		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$vs_display_template		= caGetOption('displayTemplate', $pa_bundle_settings, _t('No template defined'));
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
		$o_view->setVar('t_subject', $this);
		
		$o_view->setVar('mode', $vs_mode = caGetOption('locationTrackingMode', $pa_bundle_settings, 'ca_movements'));
		
		$o_view->setVar('qr_result', ($qr_result = $this->getLocationContents($vs_mode)));
		switch($vs_mode) {
			case 'ca_storage_locations':
				$o_view->setVar('t_subject_rel', new ca_objects_x_storage_locations());
				break;
			case 'ca_movements':
			default:
				$o_view->setVar('t_subject_rel', new ca_movements_x_objects());
				break;
		}
		
		return $o_view->render('ca_storage_locations_contents.php');
 	}
	# ------------------------------------------------------
	/**
	 * Return search result containing objects currently resident in this location
	 *
	 * @param string $ps_mode Location tracking mode: ca_storage_locations (for direct object-location relationship tracking) or ca_movements (for movement-based location tracking)
	 * @param array $pa_options No options are currently supported
	 *
	 * @return ObjectSearchResult Result set containing objects currently in this location
	 */
	public function getLocationContents($ps_mode, $pa_options=null) {
		switch($ps_mode) {
			case 'ca_storage_locations':
				// Get current objects for location
				$va_object_ids = $this->getRelatedItems('ca_objects', array('idsOnly' => true));
				if (is_array($va_object_ids) && sizeof($va_object_ids)) {
					// check each object for current location
					
					// ... then get the list of objects for which the *current* movement is one of ours
					$t_object = new ca_objects();
					$va_current_locations_ids = $t_object->getRelatedItems('ca_storage_locations', array('idsOnly' => false, 'showCurrentOnly' => true, 'row_ids' => $va_object_ids));
					
					$va_object_rels = array(); 
					foreach($va_current_locations_ids as $vn_relation_id => $va_location_info) {
						if ($va_location_info['location_id'] == $this->getPrimaryKey()) { $va_object_rels[] = $vn_relation_id; }
					}
					
					return sizeof($va_object_rels) ? caMakeSearchResult('ca_objects_x_storage_locations', $va_object_rels) : null;
				}
				break;
			case 'ca_movements':
			default:
				// Get current movements for location
				$va_location_ids = array_merge($this->get($x=$this->tableName().".children.".$this->primaryKey(), ['returnAsArray' => true]), [$this->getPrimaryKey()]);
			
				$va_movement_ids = $this->getRelatedItems('ca_movements', array('idsOnly' => true, 'row_ids' => $va_location_ids));
				if (is_array($va_movement_ids) && sizeof($va_movement_ids)) {
					// get list of objects on these movements...
					$t_movement = new ca_movements();
					$va_object_ids = $t_movement->getRelatedItems('ca_objects', array('idsOnly' => true, 'showCurrentOnly' => true, 'row_ids' => $va_movement_ids));
					
					// ... then get the list of objects for which the *current* movement is one of ours
					$t_object = new ca_objects();
					$va_current_movement_ids = $t_object->getRelatedItems('ca_movements', array('idsOnly' => false, 'showCurrentOnly' => true, 'row_ids' => $va_object_ids));
					
					$va_movement_rels = array(); 
					foreach($va_current_movement_ids as $vn_i => $va_movement_info) {
						if (in_array($va_movement_info['movement_id'], $va_movement_ids)) { $va_movement_rels[] = $va_movement_info['relation_id']; }
					}
					
					return sizeof($va_movement_rels) ? caMakeSearchResult('ca_movements_x_objects', $va_movement_rels) : null;
				}
				break;
		}
		return null;
	}
	# ------------------------------------------------------
}