<?php
/** ---------------------------------------------------------------------
 * app/models/ca_tour_stops.php : table access class for table ca_tour_stops
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/ca/BundlableLabelableBaseModelWithAttributes.php');
require_once(__CA_LIB_DIR__.'/ca/IHierarchy.php');
require_once(__CA_MODELS_DIR__.'/ca_tours.php');
require_once(__CA_MODELS_DIR__.'/ca_locales.php');
require_once(__CA_APP_DIR__.'/helpers/tourHelpers.php');


BaseModel::$s_ca_models_definitions['ca_tour_stops'] = array(
 	'NAME_SINGULAR' 	=> _t('tour stop'),
 	'NAME_PLURAL' 		=> _t('tour stops'),
 	'FIELDS' 			=> array(
 		'stop_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this item')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Parent'), 'DESCRIPTION' => _t('Parent list item for this item')
		),
		'tour_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Tour'), 'DESCRIPTION' => _t('Tour stop is part of')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DISPLAY_FIELD' => array('ca_list_items.item_value'),
				'DISPLAY_ORDERBY' => array('ca_list_items.item_value'),
				'IS_NULL' => true, 
				'LIST_CODE' => 'tour_stop_types',
				'DEFAULT' => '',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates the type of the stop.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Id number'), 'DESCRIPTION' => _t('Unique identifier for this tour stop'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Id number sort'), 'DESCRIPTION' => _t('Sortable value for id number'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'color' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COLORPICKER, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Color'), 'DESCRIPTION' => _t('Color to display stop in')
		),
		'icon' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_icons',
				'LABEL' => _t('Icon'), 'DESCRIPTION' => _t('Optional icon to use with stop')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
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
		'hier_stop_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Stop hierarchy', 'DESCRIPTION' => 'Identifier of stop that is root of the stop hierarchy.'
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if the list item is accessible to the public or not. ')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the list item.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if list item is deleted or not.')
		)
 	)
);

class ca_tour_stops extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_tour_stops';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'stop_id';

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
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_tour_stops';
	protected $HIERARCHY_ID_FLD				=	'hier_stop_id';
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			'tour_id'
		),
		"RELATED_TABLES" => array(
			
		)
	);
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';					// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'tour_stop_types';		// list code (ca_lists.list_code) of list defining types for this table
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_tour_stop_labels';
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_tour_stops_x_tour_stops';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = 'tour_id';		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'TourStopSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'TourStopSearchResult';
	
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
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_tour_stops'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related tour stops'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
	}
	# ------------------------------------------------------
	/**
	 * Override set() to do idno lookups on tours
	 *
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if (!is_array($pa_fields)) {
			$pa_fields = array($pa_fields => $pm_value);
		}
		foreach($pa_fields as $vs_fld => $vs_val) {
			if (($vs_fld == 'tour_id') && (preg_match("![^\d]+!", $vs_val))) {
				if ($vn_tour_id = caGetTourID($vs_val)) {
					$pa_fields[$vs_fld] = $vn_tour_id;
				}
			}
		}
		return parent::set($pa_fields, null, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Return array containing information about all hierarchies, including their root_id's
	 * For non-adhoc hierarchies such as places, this call returns the contents of the place_hierarchies list
	 * with some extra information such as the # of top-level items in each hierarchy.
	 *
	 * For an ad-hoc hierarchy like that of a tour stop, there is only ever one hierarchy to display - that of the current stop.
	 * So for adhoc hierarchies we just return a single entry corresponding to the root of the current tour stop hierarchy
	 */
	 public function getHierarchyList($pb_dummy=false) {
	 	$vn_pk = $this->getPrimaryKey();
	 	if (!$vn_pk) { return null; }		// have to load a row first
	 	
	 	$vs_template = $this->getAppConfig()->get('ca_tour_stops_hierarchy_browser_display_settings');
	 	if (!$vs_template) {
	 		$vs_template = "^ca_tour_stops.preferred_labels.name"; 
	 	}
	 	
	 	$vs_label = $this->getLabelForDisplay(false);
	 	$vs_hier_fld = $this->getProperty('HIERARCHY_ID_FLD');
	 	$vs_parent_fld = $this->getProperty('PARENT_ID_FLD');
	 	$vn_hier_id = $this->get($vs_hier_fld);
	 	
	 	if ($this->get($vs_parent_fld)) { 
	 		// currently loaded row is not the root so get the root
	 		$va_ancestors = $this->getHierarchyAncestors();
	 		if (!is_array($va_ancestors) || sizeof($va_ancestors) == 0) { return null; }
	 		$t_stop = new ca_tour_stops($va_ancestors[0]);
	 	} else {
	 		$t_stop =& $this;
	 	}
	 	
	 	$va_children = $t_stop->getHierarchyChildren(null, array('idsOnly' => true));
	 	$va_stop_hierarchy_root = array(
	 		$t_stop->get($vs_hier_fld) => array(
	 			'item_id' => $vn_pk,
	 			'name' => $vs_name = caProcessTemplateForIDs($vs_template, 'ca_tour_stops', array($vn_pk)),
	 			'hierarchy_id' => $vn_hier_id,
	 			'children' => sizeof($va_children)
	 		)
	 	);
	 	
	 	return $va_stop_hierarchy_root;
	}
	# ------------------------------------------------------
	/**
	 * Returns name of hierarchy for currently loaded row or, if specified, row identified by optional $pn_id parameter
	 */
	 public function getHierarchyName($pn_id=null) {
	 	if (!$pn_id) { $pn_id = $this->getPrimaryKey(); }
	 	
		$va_ancestors = $this->getHierarchyAncestors($pn_id, array('idsOnly' => true));
		if (is_array($va_ancestors) && sizeof($va_ancestors)) {
			$vn_parent_id = array_pop($va_ancestors);
			$t_stop = new ca_tour_stops($vn_parent_id);
			return $t_stop->getLabelForDisplay(false);
		} else {			
			if ($pn_id == $this->getPrimaryKey()) {
				return $this->getLabelForDisplay(true);
			} else {
				$t_stop = new ca_tour_stops($pn_id);
				return $t_stop->getLabelForDisplay(false);
			}
		}
	 }
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is save-able
 	 *
 	 * @param RequestHTTP $po_request
 	 * @return bool True if record can be saved, false if not
 	 */
 	public function isSaveable($po_request) {
 		// Check actions
 		if (!$this->getPrimaryKey() && !$po_request->user->canDoAction('can_create_ca_tours')) {
 			return false;
 		}
 		if ($this->getPrimaryKey() && !$po_request->user->canDoAction('can_edit_ca_tours')) {
 			return false;
 		}
 		
 		return true;
 	}
 	
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is deletable
 	 */
 	public function isDeletable($po_request) {
 		// Is row loaded?
 		if (!$this->getPrimaryKey()) { return false; }
 		
 		// Check actions
 		if (!$this->getPrimaryKey() && !$po_request->user->canDoAction('can_delete_ca_tours')) {
 			return false;
 		}
 		
 		return true;
 	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getTourStopIDsByName($ps_name, $pn_parent_id=null, $pn_type_id=null) {
		$o_db = $this->getDb();
		
		$va_params = array((string)$ps_name);
		
		$vs_type_sql = '';
		if ($pn_type_id) {
			if(sizeof($va_type_ids = caMakeTypeIDList('ca_tour_stops', array($pn_type_id)))) {
				$vs_type_sql = " AND cap.type_id IN (?)";
				$va_params[] = $va_type_ids;
			}
		}
		
		if ($pn_parent_id) {
			$vs_parent_sql = " AND cap.parent_id = ?";
			$va_params[] = (int)$pn_parent_id;
		} 
		
		
		$qr_res = $o_db->query("
			SELECT DISTINCT cap.stop_id
			FROM ca_tour_stops cap
			INNER JOIN ca_tour_stop_labels AS capl ON capl.stop_id = cap.stop_id
			WHERE
				capl.name = ? {$vs_type_sql} {$vs_parent_sql} AND cap.deleted = 0
		", $va_params);
		
		$va_stop_ids = array();
		while($qr_res->nextRow()) {
			$va_stop_ids[] = $qr_res->get('stop_id');
		}
		return $va_stop_ids;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getIDsByLabel($pa_label_values, $pn_parent_id=null, $pn_type_id=null) {
		return $this->getTourStopIDsByName($pa_label_values['name'], $pn_parent_id, $pn_type_id);
	}
	# ------------------------------------------------------
}
?>