<?php
/** ---------------------------------------------------------------------
 * app/models/ca_occurrences.php : table access class for table ca_occurrences
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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


BaseModel::$s_ca_models_definitions['ca_occurrences'] = array(
 	'NAME_SINGULAR' 	=> _t('occurrence'),
 	'NAME_PLURAL' 		=> _t('occurrences'),
 	'FIELDS' 			=> array(
 		'occurrence_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this occurrence')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent', 'DESCRIPTION' => 'Identifier of parent occurrence'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the item originates.')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LIST_CODE' => 'occurrence_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the occurrence. In CollectiveAccess every occurrence has a single "instrinsic" type that determines the set of descriptive and administrative metadata that can be applied to it.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Identifier'), 'DESCRIPTION' => _t('A unique alphanumeric identifier for this entry.'),
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
		'source_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'occurrence_sources',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source. This value is often used to indicate the administrative sub-division or legacy database from which the information originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for occurrence information retrieved via web services [NOT IMPLEMENTED YET].'
		),
		'hier_occurrence_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Occurrence hierarchy', 'DESCRIPTION' => 'Identifier of occurrence that is root of the occurrence hierarchy.'
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if object is accessible to the public or not. ')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the record.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the record is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		)
 	)
);

class ca_occurrences extends RepresentableBaseModel implements IBundleProvider {
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
	protected $TABLE = 'ca_occurrences';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'occurrence_id';

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

	# If you want to order records arbitrarily, add a numeric field to the table and occurrence
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = 'rank';
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_ADHOC_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_occurrences';
	protected $HIERARCHY_ID_FLD				=	'hier_occurrence_id';
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
	protected $LABEL_TABLE_NAME = 'ca_occurrence_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';				// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'occurrence_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_occurrences_x_occurrences';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = 'type_id';		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'OccurrenceSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'OccurrenceSearchResult';
	
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
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lot'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		
		$this->BUNDLES['ca_tour_stops'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related tour stops'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
	
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
	}
	# ------------------------------------------------------
	/**
	 * Return array containing information about all hierarchies, including their root_id's
	 * For non-adhoc hierarchies such as occurrences, this call returns the contents of the occurrence_hierarchies list
	 * with some extra information such as the # of top-level items in each hierarchy.
	 *
	 * For an ad-hoc hierarchy like that of an collection, there is only ever one hierarchy to display - that of the current collection.
	 * So for adhoc hierarchies we just return a single entry corresponding to the root of the current occurrence hierarchy
	 */
	 public function getHierarchyList($pb_dummy=false) {
	 	$vn_pk = $this->getPrimaryKey();
	 	$vs_template = $this->getAppConfig()->get('ca_occurrences_hierarchy_browser_display_settings');
	 	
	 	if (!$vn_pk) { 
	 		$o_db = new Db();
	 		if (is_array($va_type_ids = caMergeTypeRestrictionLists($this, array())) && sizeof($va_type_ids)) {
				$qr_res = $o_db->query("
					SELECT o.occurrence_id, count(*) c
					FROM ca_occurrences o
					INNER JOIN ca_occurrences AS p ON p.parent_id = o.occurrence_id
					WHERE o.parent_id IS NULL AND o.type_id IN (?)
					GROUP BY o.occurrence_id
				", array($va_type_ids));
			} else {
				$qr_res = $o_db->query("
					SELECT o.occurrence_id, count(*) c
					FROM ca_occurrences o
					INNER JOIN ca_occurrences AS p ON p.parent_id = o.occurrence_id
					WHERE o.parent_id IS NULL
					GROUP BY o.occurrence_id
				");
			}
	 		$va_hiers = array();
	 		
	 		$va_occurrence_ids = $qr_res->getAllFieldValues('occurrence_id');
	 		$qr_res->seek(0);
	 		$va_labels = $this->getPreferredDisplayLabelsForIDs($va_occurrence_ids);
	 		while($qr_res->nextRow()) {
	 			$va_hiers[$vn_occurrence_id = $qr_res->get('occurrence_id')] = array(
	 				'occurrence_id' => $vn_occurrence_id,
	 				'name' => caProcessTemplateForIDs($vs_template, 'ca_occurrences', array($vn_occurrence_id)),
	 				'hierarchy_id' => $vn_occurrence_id,
	 				'children' => (int)$qr_res->get('c')
	 			);
	 		}
	 		return $va_hiers;
	 	} else {
	 		// return specific occurrence as root of hierarchy
			$vs_label = $this->getLabelForDisplay(false);
			$vs_hier_fld = $this->getProperty('HIERARCHY_ID_FLD');
			$vs_parent_fld = $this->getProperty('PARENT_ID_FLD');
			$vn_hier_id = $this->get($vs_hier_fld);
			
			if ($this->get($vs_parent_fld)) { 
				// currently loaded row is not the root so get the root
				$va_ancestors = $this->getHierarchyAncestors();
				if (!is_array($va_ancestors) || sizeof($va_ancestors) == 0) { return null; }
				$t_occurrence = new ca_occurrences($va_ancestors[0]);
			} else {
				$t_occurrence =& $this;
			}
			
			$va_children = $t_occurrence->getHierarchyChildren(null, array('idsOnly' => true));
			$va_occurrence_hierarchy_root = array(
				$t_occurrence->get($vs_hier_fld) => array(
					'occurrence_id' => $vn_pk,
	 				'item_id' => $vn_pk,
					'name' => $vs_name = caProcessTemplateForIDs($vs_template, 'ca_occurrences', array($vn_pk)),
					'hierarchy_id' => $vn_hier_id,
					'children' => sizeof($va_children)
				)
			);
				
	 		return $va_occurrence_hierarchy_root;
		}
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
			$t_occ = new ca_occurrences($vn_parent_id);
			return $t_occ->getLabelForDisplay(false);
		} else {			
			if ($pn_id == $this->getPrimaryKey()) {
				return $this->getLabelForDisplay(true);
			} else {
				$t_occ = new ca_occurrences($pn_id);
				return $t_occ->getLabelForDisplay(false);
			}
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getOccurrenceIDsByName($ps_name, $pn_parent_id=null, $pn_type_id=null) {
		$o_db = $this->getDb();
		
		$va_params = array((string)$ps_name);
		
		$vs_type_sql = '';
		if ($pn_type_id) {
			if(sizeof($va_type_ids = caMakeTypeIDList('ca_occurrences', array($pn_type_id)))) {
				$vs_type_sql = " AND cap.type_id IN (?)";
				$va_params[] = $va_type_ids;
			}
		}
		
		if ($pn_parent_id) {
			$vs_parent_sql = " AND cap.parent_id = ?";
			$va_params[] = (int)$pn_parent_id;
		} 
		
		$qr_res = $o_db->query($x="
				SELECT DISTINCT cap.occurrence_id
				FROM ca_occurrences cap
				INNER JOIN ca_occurrence_labels AS capl ON capl.occurrence_id = cap.occurrence_id
				WHERE
					capl.name = ? {$vs_type_sql} {$vs_parent_sql} AND cap.deleted = 0
			", $va_params);
		
		$va_occurrence_ids = array();
		while($qr_res->nextRow()) {
			$va_occurrence_ids[] = $qr_res->get('occurrence_id');
		}
		return $va_occurrence_ids;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getIDsByLabel($pa_label_values, $pn_parent_id=null, $pn_type_id=null) {
		return $this->getOccurrenceIDsByName($pa_label_values['name'], $pn_parent_id, $pn_type_id);
	}
	# ------------------------------------------------------
}
?>