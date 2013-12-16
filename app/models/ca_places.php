<?php
/** ---------------------------------------------------------------------
 * app/models/ca_places.php : table access class for table ca_places
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
require_once(__CA_LIB_DIR__.'/ca/IHierarchy.php');
require_once(__CA_MODELS_DIR__."/ca_lists.php");


BaseModel::$s_ca_models_definitions['ca_places'] = array(
 	'NAME_SINGULAR' 	=> _t('place'),
 	'NAME_PLURAL' 		=> _t('places'),
 	'FIELDS' 			=> array(
 		'place_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this place')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Parent id'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The primary locale associated with the place.')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LIST_CODE' => 'place_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the place. In CollectiveAccess every place has a single "instrinsic" type that determines the set of descriptive and administrative metadata that can be applied to it.')
		),
		'source_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'place_sources',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of the place. This value is often used to indicate the administrative sub-division or legacy database from which the place information originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'hierarchy_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LIST_CODE' => 'place_hierarchies',
				'LABEL' => 'Place hierarchy', 'DESCRIPTION' => 'Hierarchy this place belongs to.'
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Place identifier'), 'DESCRIPTION' => _t('Unique identifier for place'),
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
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for place information retrieved via web services [NOT IMPLEMENTED YET].'
		),
		'lifespan' => array(
				'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'DEFAULT' => '', 'START' => 'lifespan_sdate', 'END' => 'lifespan_edate',
				'LABEL' => _t('Lifespan'), 'DESCRIPTION' => _t('Lifespan of place (range of dates)')
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if place information is accessible to the public or not. ')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the place record.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the place is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
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
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		)
 	)
);

class ca_places extends RepresentableBaseModel implements IBundleProvider, IHierarchy {
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
	protected $TABLE = 'ca_places';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'place_id';

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
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_MULTI_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_list_items';
	protected $HIERARCHY_ID_FLD				=	'hierarchy_id';
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
	protected $LABEL_TABLE_NAME = 'ca_place_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'place_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_places_x_places';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = 'hierarchy_id';	// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'PlaceSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'PlaceSearchResult';
	
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
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lots'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
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
	 * Returns a flat list of all entities in the specified list referenced by items in the specified table
	 * (and optionally a search on that table)
	 */
	public function getReferenced($pm_table_num_or_name, $pn_type_id=null, $pa_reference_limit_ids=null, $pn_access=null) {
		if (is_numeric($pm_table_num_or_name)) {
			$vs_table_name = $this->getAppDataModel()->getTableName($pm_table_num_or_name);
		} else {
			$vs_table_name = $pm_table_num_or_name;
		}
		
		if (!($t_ref_table = $this->getAppDatamodel()->getInstanceByTableName($vs_table_name, true))) {
			return null;
		}
		
		
		if (!$vs_table_name) { return null; }
		
		$o_db = $this->getDb();
		$va_path = $this->getAppDatamodel()->getPath($this->tableName(), $vs_table_name);
		array_shift($va_path); // remove table name from path
		
		$vs_last_table = $this->tableName();
		$va_joins = array();
		foreach($va_path as $vs_rel_table_name => $vn_rel_table_num) {
			$va_rels = $this->getAppDatamodel()->getRelationships($vs_last_table, $vs_rel_table_name);
			$va_rel = $va_rels[$vs_last_table][$vs_rel_table_name][0];
			
			
			$va_joins[] = "INNER JOIN {$vs_rel_table_name} ON {$vs_last_table}.".$va_rel[0]." = {$vs_rel_table_name}.".$va_rel[1];
			
			$vs_last_table = $vs_rel_table_name;
		}
		
		$va_sql_wheres = array();
		if (is_array($pa_reference_limit_ids) && sizeof($pa_reference_limit_ids)) {
			$va_sql_wheres[] = "({$vs_table_name}.".$t_ref_table->primaryKey()." IN (".join(',', $pa_reference_limit_ids)."))";
		}
		
		if (!is_null($pn_access)) {
			$va_sql_wheres[] = "({$vs_table_name}.access = ".intval($pn_access).")";
		}
		
		// get place counts
		$vs_sql = "
			SELECT ca_places.place_id, count(*) cnt
			FROM ca_places
			".join("\n", $va_joins)."
			".(sizeof($va_sql_wheres) ? " WHERE ".join(' AND ', $va_sql_wheres) : "")."
			GROUP BY
				ca_places.place_id, {$vs_table_name}.".$t_ref_table->primaryKey()."
		";
		$qr_items = $o_db->query($vs_sql);
		
		$va_item_counts = array();
		while($qr_items->nextRow()) {
			$va_item_counts[$qr_items->get('place_id')]++;
		}
		
		$vs_sql = "
			SELECT ca_places.place_id, ca_places.idno, ca_place_labels.*, count(*) c
			FROM ca_places
			INNER JOIN ca_place_labels ON ca_place_labels.place_id = ca_places.place_id
			".join("\n", $va_joins)."
			WHERE
				(ca_place_labels.is_preferred = 1)
				".(sizeof($va_sql_wheres) ? " AND ".join(' AND ', $va_sql_wheres) : "")."
			GROUP BY
				ca_place_labels.label_id
			ORDER BY 
				ca_place_labels.name
		";
		
		$qr_items = $o_db->query($vs_sql);
		
		$va_items = array();
		while($qr_items->nextRow()) {
			$vn_place_id = $qr_items->get('place_id');
			$va_items[$vn_place_id][$qr_items->get('locale_id')] = array_merge($qr_items->getRow(), array('cnt' => $va_item_counts[$vn_place_id]));
		}
		
		return caExtractValuesByUserLocale($va_items);
	}
	# ------------------------------------------------------
	/**
	 * Return array containing information about all place hierarchies, including their root_id's
	 */
	 public function getHierarchyList($pb_dummy=false) {
	 	$t_list = new ca_lists();
	 	$va_place_hierarchies = caExtractValuesByUserLocale($t_list->getItemsForList('place_hierarchies'));
		
		$o_db = $this->getDb();
		
		$va_hierarchy_ids = array();
		foreach($va_place_hierarchies as $vn_i => $va_item) {
			$va_hierarchy_ids[] = intval($va_item['item_id']);
		}
		
		if (!sizeof($va_hierarchy_ids)) { return array(); }
		
		// get root for each hierarchy
		$qr_res = $o_db->query("
			SELECT p.place_id, p.hierarchy_id, count(*) children
			FROM ca_places p
			INNER JOIN ca_places AS p2 ON p.place_id = p2.place_id
			WHERE 
				p.parent_id IS NULL and p.hierarchy_id IN (".join(',', $va_hierarchy_ids).")
			GROUP BY
				p.place_id
		");
		while ($qr_res->nextRow()) {
			$vn_hierarchy_id = $qr_res->get('hierarchy_id');
			$va_place_hierarchies[$vn_hierarchy_id]['place_id'] = $va_place_hierarchies[$vn_hierarchy_id]['item_id'] = $qr_res->get('place_id');
			$va_place_hierarchies[$vn_hierarchy_id]['name'] = $va_place_hierarchies[$vn_hierarchy_id]['name_plural'];
			$va_place_hierarchies[$vn_hierarchy_id]['children'] = $qr_res->get('children');
		}
		return $va_place_hierarchies;
	 }
	# ------------------------------------------------------
	/**
	 * Returns name of hierarchy for currently loaded place or, if specified, place with place_id = to optional $pn_id parameter
	 */
	 public function getHierarchyName($pn_id=null) {
	 	$t_list = new ca_list_items();
	 	if ($pn_id) {
	 		$t_place = new ca_places($pn_id);
	 		$vn_hierarchy_id = $t_place->get('hierarchy_id');
	 	} else {
	 		$vn_hierarchy_id = $this->get('hierarchy_id');
	 	}
	 	$t_list->load($vn_hierarchy_id);
	 	return $t_list->getLabelForDisplay(false);
	 }
	# ------------------------------------------------------
	/**
	 * Returns the place_id in ca_places table for root of specified hierarchy. Place hierarchies are enumerated in the "place_hierarchies" list,
	 * so hierarchy specifications are really just plain old list items (ca_list_items records). You can specify a hierarchy using an hierarchy id 
	 * (really just a ca_list_items item_id value for the item representing the hierarchy), or by passing the idno of the hierarchy (eg. ca_list_items.idno).
	 *
	 * @param mixed $pm_hierarchy_code_or_id The numeric id or alphanumeric code for the hierarchy. Since hierarchies are represented with list items these are the item_id or idno values of the hierarchy's list item.
	 * @return int Place ID of the place hierarchy root
	 */
	public function getRootIDForHierarchy($pm_hierarchy_code_or_id) {	
		$o_db = $this->getDb();
		
		if (!is_numeric($pm_hierarchy_code_or_id)) {
			$t_list = new ca_lists();
			$pn_hierarchy_id = (int)$t_list->getItemIDFromList('place_hierarchies', $pm_hierarchy_code_or_id);
		} else {
			$pn_hierarchy_id = (int)$pm_hierarchy_code_or_id;
		}
		
		$qr_res = $o_db->query("
			SELECT place_id
			FROM ca_places 
			WHERE 
				(parent_id IS NULL) AND (hierarchy_id = ?)
		", (int)$pn_hierarchy_id);
		if ($qr_res->nextRow()) {
			return $qr_res->get('place_id');
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getPlaceIDsByName($ps_name, $pn_parent_id=null, $pn_type_id=null) {
		$o_db = $this->getDb();
		
		$va_params = array((string)$ps_name);
		
		$vs_type_sql = '';
		if ($pn_type_id) {
			if(sizeof($va_type_ids = caMakeTypeIDList('ca_places', array($pn_type_id)))) {
				$vs_type_sql = " AND cap.type_id IN (?)";
				$va_params[] = $va_type_ids;
			}
		}
		
		if ($pn_parent_id) {
			$vs_parent_sql = " AND cap.parent_id = ?";
			$va_params[] = (int)$pn_parent_id;
		} 
		
		
		$qr_res = $o_db->query("
			SELECT DISTINCT cap.place_id
			FROM ca_places cap
			INNER JOIN ca_place_labels AS capl ON capl.place_id = cap.place_id
			WHERE
				capl.name = ? {$vs_type_sql} {$vs_parent_sql} AND cap.deleted = 0
		", $va_params);
		
		$va_place_ids = array();
		while($qr_res->nextRow()) {
			$va_place_ids[] = $qr_res->get('place_id');
		}
		return $va_place_ids;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getIDsByLabel($pa_label_values, $pn_parent_id=null, $pn_type_id=null) {
		return $this->getPlaceIDsByName($pa_label_values['name'], $pn_parent_id, $pn_type_id);
	}
	# ------------------------------------------------------
}
?>