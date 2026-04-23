<?php
/** ---------------------------------------------------------------------
 * app/models/ca_relationship_types.php : table access class for table ca_relationship_types
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BundlableLabelableBaseModelWithAttributes.php');

BaseModel::$s_ca_models_definitions['ca_relationship_types'] = array(
 	'NAME_SINGULAR' 	=> _t('relationship type'),
 	'NAME_PLURAL' 		=> _t('relationship types'),
 	'FIELDS' 			=> array(
 		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this relationship type')
		),
		'sub_type_left_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Type restriction for "left" side of relationship'), 'DESCRIPTION' => _t('Type restriction for "left" side of relationship (eg. if relationship is object-entity, then the restriction controls for which types of objects this relationship type is valid.')
		),
		'include_subtypes_left' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '0',
				'LABEL' => _t('Include subtypes in "left" side restriction?'), 'DESCRIPTION' => _t('Automatically include subtypes of selected left-side restriction.')
		),
		'sub_type_right_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Type restriction for "right" side of relationship'), 'DESCRIPTION' => _t('Type restriction for "right" side of relationship (eg. if relationship is object-entity, then the restriction controls for which types of entities this relationship type is valid.')
		),
		'include_subtypes_right' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '0',
				'LABEL' => _t('Include subtypes in "right" side restriction?'), 'DESCRIPTION' => _t('Automatically include subtypes of selected left-side restriction.')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => 'Parent', 'DESCRIPTION' => 'Identifier of parent relationship type; is null if relationship type is root of hierarchy.',
				'DONT_USE_AS_BUNDLE' => true
		),
		'hier_type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => _t('Relationship type hierarchy identifier'), 'DESCRIPTION' => _t('Identifier of relationship_type that is root of the relationship_type hierarchy.'),
				'DONT_USE_AS_BUNDLE' => true
		),
		'type_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Relationship type code'), 'DESCRIPTION' => _t('Unique identifier for the relationship type; must be unique for relationship types for the current relationship.'),
				'BOUNDS_VALUE' => array(1,100),
				'UNIQUE_WITHIN' => array('table_num')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Relationship'), 'DESCRIPTION' => _t('Indicates the type of relationship for which this type is applicable.'),
				'BOUNDS_VALUE' => array(0,255),
				'DONT_USE_AS_BUNDLE' => true
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '10',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the relationship type when displayed in a list with other relationship types. Lower numbers indicate higher priority.'),
				'BOUNDS_VALUE' => array(0,65535)
		),
		'is_default' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is default?'), 'DESCRIPTION' => _t('If checked this relationship type will be the default type for the relationship.')
		),
		'hier_left' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - left bound', 'DESCRIPTION' => 'Left-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.',
				'DONT_USE_AS_BUNDLE' => true
		),
		'hier_right' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - right bound', 'DESCRIPTION' => 'Right-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.',
				'DONT_USE_AS_BUNDLE' => true
		)
 	)
);

class ca_relationship_types extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_relationship_types';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'type_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('typename');

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
	protected $ORDER_BY = array('typename');

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
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_relationship_types';
	protected $HIERARCHY_ID_FLD				=	'hier_type_id';
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
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = null;					// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = null;		// list code (ca_lists.list_code) of list defining types for this table
	
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_relationship_type_labels';
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'RelationshipTypeSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'RelationshipTypeSearchResult';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * @var cached relationship type_ids as loaded/created by getRelationshipTypeID()
	 */
	static $s_relationship_type_id_cache = array();
	
	
	/**
	 * @var cached relationship type_ids as loaded/created by getRelationshipTypeID()
	 */
	//static $s_relationship_type_id_cache = array();
	
	
	/**
	 * @var cached relationship tables; used by getRelationshipTypeTable()
	 */
	static $s_relationship_type_table_cache = array();
	
	/**
	 * @var cached type_id to type_code entries; used by relationshipTypeIDsToTypeCodes()
	 */
	static $s_relationship_type_id_to_code_cache = array();
	
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
		parent::__construct($id, $options);	# call superclass constructor
		
		// 
		if ($id) { $this->loadSubtypeLists();}
	}
	# ------------------------------------------------------
	public function load($pm_id = NULL, $pb_use_cache = true) {
		if ($vn_rc = parent::load($pm_id)) {
			$this->loadSubtypeLists();
			return $vn_rc;
		}
		
		$this->FIELDS['sub_type_left_id']['BOUNDS_CHOICE_LIST'] = $this->FIELDS['sub_type_right_id']['BOUNDS_CHOICE_LIST'] = array();
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Load sub_type_left_id and sub_type_right_id fields with type lists appropriate to relationship table
	 * indicated by table_num field
	 */
	protected function loadSubtypeLists() {
		$va_left_type_list = array();
		$va_right_type_list = array();
		if ($vn_table_num = $this->get('table_num')) {
			$va_relationship_tables = $this->getRelationshipsUsingTypes();
			if (!isset($va_relationship_tables[$vn_table_num])) { return null; }
			
			$t_rel_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
			$t_instance = Datamodel::getInstanceByTableName($t_rel_instance->getLeftTableName(), true);
			
			
			if (method_exists($t_instance, 'getTypeList')) {
				$va_types = $t_instance->getTypeList();
				
				foreach($va_types as $vn_type_id => $va_type_info) {
					$va_left_type_list[$va_type_info['name_plural']] = $vn_type_id;
				}
			}
			
			$t_instance = Datamodel::getInstanceByTableName($t_rel_instance->getRightTableName(), true);
			
			if (method_exists($t_instance, 'getTypeList')) {
				$va_types = $t_instance->getTypeList();
			
				foreach($va_types as $vn_type_id => $va_type_info) {
					$va_right_type_list[$va_type_info['name_plural']] = $vn_type_id;
				}
			}
		}
				
		$this->FIELDS['sub_type_left_id']['DISPLAY_TYPE'] = DT_SELECT;
		$this->FIELDS['sub_type_left_id']['BOUNDS_CHOICE_LIST'] = $va_left_type_list;
		
		$this->FIELDS['sub_type_right_id']['DISPLAY_TYPE'] = DT_SELECT;
		$this->FIELDS['sub_type_right_id']['BOUNDS_CHOICE_LIST'] = $va_right_type_list;
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['hierarchy_navigation'] = ['type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation')];
		$this->BUNDLES['hierarchy_location'] = ['type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy')];
		
		$this->BUNDLES['ca_relationship_type_restrictions'] = ['type' => 'special', 'repeating' => false, 'label' => _t('Type restrictions')];
	}
	# ------------------------------------------------------
	/**
	 * Return information, including typenames filtered by user locale, for relationship types for the 
	 * specified relationship table (eg. ca_objects_x_entities, ca_entities_x_occurrences).
	 *
	 * @params mixed $pm_table_name_or_num
	 * @params string $ps_type_code
	 * @params array $options Options include:
	 *      includeTypeCodesAsKeys = Also set type codes are keys in the returned array. [Default is false]
	 *		returnAllLocales = 
	 *
	 * Returns array keyed on relationship type_id; values are associative arrays keys on ca_relationship_types/ca_relationship_type_labels field names
	 */
	public function getRelationshipInfo($pm_table_name_or_num, $ps_type_code=null, $options=null) {
		if (!is_numeric($pm_table_name_or_num)) {
			$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);
		} else {
			$vn_table_num = $pm_table_name_or_num;
		}
		
		$include_type_codes_as_keys = caGetOption('includeTypeCodesAsKeys', $options, false);
		
		$params = [(int)$vn_table_num];
		$vs_type_sql = '';
		if ($ps_type_code) {
			$vs_type_sql = " AND ((crt.type_code = ?) || (crtl.typename = ?) || (crtl.typename_reverse = ?))";
			$params[] = $ps_type_code; $params[] = $ps_type_code; $params[] = $ps_type_code;
		}
		
		$qr_res = $this->getDb()->query("
			SELECT *
			FROM ca_relationship_types crt
			INNER JOIN ca_relationship_type_labels AS crtl ON crt.type_id = crtl.type_id
			WHERE
				(crt.table_num = ?) {$vs_type_sql}
		", $params);
		
		$va_relationships = [];
		while ($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_relationships[$qr_res->get('type_id')][$locale_id = $qr_res->get('locale_id')] = $va_row;
			if ($include_type_codes_as_keys) {
			    $va_relationships[$va_row['type_code']][$locale_id] = $va_row;
			}
		}
		return caGetOption('returnAllLocales', $options, false)  ? $va_relationships : caExtractValuesByUserLocale($va_relationships);
	}
	# ------------------------------------------------------
	/**
	 * @param array $pa_options Option are
	 *		create = create relationship type using parameters if one with the specified type code or type_id doesn't exist already [default=false]
	 *		cache = cache relationship types as they are referenced and return cached value if possible [default=true]
	 *		matchOn = List of values to match on. Valid entries as "type_code", "typecode", "label", "label". [default=null]
	 */
	public function getRelationshipTypeID($pm_table_name_or_num, $pm_type_code_or_id, $pn_locale_id=null, $pa_values=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!isset($pa_options['create'])) { $pa_options['create'] = false; }
		if (!isset($pa_options['cache'])) { $pa_options['cache'] = true; }
		
		if(!is_array($match_on = caGetOption('matchOn', $pa_options, []))) { $match_on = []; }
		$match_on = array_filter(array_map('strtolower', $match_on), function ($v) { return in_array($v, ['type_code', 'typecode', 'label', 'labels']); });
		if(!sizeof($match_on)) { $match_on = ['type_code', 'label']; }
		
		$pm_type_code_or_id = mb_strtolower($pm_type_code_or_id);
		
		if (!is_numeric($pm_table_name_or_num)) {
			$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);
		} else {
			$vn_table_num = $pm_table_name_or_num;
		}
		if ($pa_options['cache'] && isset(ca_relationship_types::$s_relationship_type_id_cache[$vn_table_num.'/'.$pm_type_code_or_id])) {
			return ca_relationship_types::$s_relationship_type_id_cache[$vn_table_num.'/'.$pm_type_code_or_id];
		}
		
		if (is_numeric($pm_type_code_or_id)) {
			if ($va_relationships = $this->getRelationshipInfo($pm_table_name_or_num)) {
				if (isset($va_relationships[$pm_type_code_or_id])) { 
					return ca_relationship_types::$s_relationship_type_id_cache[$vn_table_num.'/'.$pm_type_code_or_id] = $pm_type_code_or_id; 
				}
			}
		} else {
			if ($va_relationships = $this->getRelationshipInfo($pm_table_name_or_num, $pm_type_code_or_id)) {
				foreach($va_relationships as $vn_type_id => $va_type_info) {
					foreach($match_on as $m) {
						switch($m) {
							case 'type_code':
							case 'typecode':
								if ((mb_strtolower($va_type_info['type_code']) === $pm_type_code_or_id)) {
									return ca_relationship_types::$s_relationship_type_id_cache[$vn_table_num.'/'.$pm_type_code_or_id] = $vn_type_id;
								}	
								break;
							case 'label':
							case 'labels':
								if ((mb_strtolower($va_type_info['typename']) === $pm_type_code_or_id) || (mb_strtolower($va_type_info['typename_reverse']) === $pm_type_code_or_id)) {
									return ca_relationship_types::$s_relationship_type_id_cache[$vn_table_num.'/'.$pm_type_code_or_id] = $vn_type_id;
								}	
								break;
						}
					}
				}
			}
		}
		
		if (isset($pa_options['create']) && $pa_options['create'] && $pn_locale_id && is_array($pa_values)) {
			$t_rel = new ca_relationship_types();
			$t_rel->setMode(ACCESS_WRITE);
			$t_rel->set('type_code', $pm_type_code_or_id);
			$t_rel->set('table_num', $vn_table_num);
			$t_rel->set('sub_type_left_id', isset($pa_values['sub_type_left_id']) ? (int)$pa_values['sub_type_left_id'] : null);
			$t_rel->set('include_subtypes_left', (isset($pa_values['include_subtypes_left']) && (bool)$pa_values['include_subtypes_left']) ? 1 : 0);
			$t_rel->set('sub_type_right_id', isset($pa_values['sub_type_right_id']) ? (int)$pa_values['sub_type_right_id'] : null);
			$t_rel->set('include_subtypes_right', (isset($pa_values['include_subtypes_right']) && (bool)$pa_values['include_subtypes_right']) ? 1 : 0);
			$t_rel->set('parent_id', isset($pa_values['parent_id']) ? (int)$pa_values['parent_id'] : null);
			$t_rel->set('rank', isset($pa_values['rank']) ? (int)$pa_values['rank'] : 0);
			$t_rel->set('is_default', isset($pa_values['is_default']) ? (int)$pa_values['is_default'] : 0);
			
			$t_rel->insert();
			
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
			
			if (!isset($pa_values['typename_reverse']) || !$pa_values['typename_reverse']) { $pa_values['typename_reverse'] = $pa_values['typename']; }
			
			$t_rel->addLabel(
				array(
					'typename' => isset($pa_values['typename']) ? $pa_values['typename'] : $pm_type_code_or_id,
					'typename_reverse' => isset($pa_values['typename_reverse']) ? $pa_values['typename_reverse'] : $pm_type_code_or_id,
					
					'description' => isset($pa_values['description']) ? $pa_values['description'] : '',
					'description_reverse' => isset($pa_values['description_reverse']) ? $pa_values['description_reverse'] : ''
				),
				$pn_locale_id, null, true
			);
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
			return ca_relationship_types::$s_relationship_type_id_cache[$vn_table_num.'/'.$pm_type_code_or_id] = $t_rel->getPrimaryKey();
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns array of tables that use relationship types
	 *
	 * @return array List of tables that use relationship types
	 */ 
	public function getRelationshipsUsingTypes() {
	 	$va_tables = Datamodel::getTableNames();
		$va_relationship_tables = array();
	 	foreach($va_tables as $vs_table) {
	 		if (preg_match('!_x_!', $vs_table)) {
	 			$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
	 			if (!$t_instance || !$t_instance->hasField('type_id')) { continue; }	// some relationships don't use types (eg. ca_users_x_roles)
	 			$vs_name = $t_instance->getProperty('NAME_PLURAL');
	 			$va_relationship_tables[$t_instance->tableNum()] = array(
	 				'name' => $vs_name,
					'table' => $vs_table
				);
	 		}
	 	}
	 	return $va_relationship_tables;
	 }
	# ------------------------------------------------------
	/**
	 * Returns name of many-to-many table using relationship types between two tables
	 *
	 * @param string $ps_table1 A valid table name
	 * @param string $ps_table2 A valid table name
	 * @return string The name of a table relating the specified tables 
	 */
	 public function getRelationshipTypeTable($ps_table1, $ps_table2) {
	 	if (isset(ca_relationship_types::$s_relationship_type_table_cache[$ps_table1][$ps_table2])) { return ca_relationship_types::$s_relationship_type_table_cache[$ps_table1][$ps_table2]; }
	 	$va_path = array_keys(Datamodel::getPath($ps_table1, $ps_table2));
	 	switch(sizeof($va_path)) {
	 		case 2:
			case 3:
				return ca_relationship_types::$s_relationship_type_table_cache[$ps_table1][$ps_table2] = $va_path[1];
				break;
		}
		
		return ca_relationship_types::$s_relationship_type_table_cache[$ps_table1][$ps_table2] = null;
	 }
	 # ------------------------------------------------------
	/**
	 * Returns instance of many-to-many table using relationship types between two tables
	 *
	 * @param string $ps_table1 A valid table name
	 * @param string $ps_table2 A valid table name
	 * @return BaseRelationshipModel An model instance for the table relating the specified tables 
	 */
	 static public function getRelationshipTypeInstance($ps_table1, $ps_table2) {
	 	$t_rel = new ca_relationship_types();
	 	if ($vs_table = $t_rel->getRelationshipTypeTable($ps_table1, $ps_table2)) {
	 		return Datamodel::getInstanceByTableName($vs_table);
	 	}
	 	return null;
	 }
	 # ------------------------------------------------------
	/**
	 * Converts a list of relationship type_code string and/or numeric type_ids to a list of numeric type_ids
	 *
	 * @param mixed $pm_table_name_or_num The name or number of the relationship table that the types are valid for (Eg. ca_objects_x_entities)
	 * @param array $pa_list A list of relationship type_code string and/or numeric type_ids
	 * @param array $pa_options Options include:
	 *			includeChildren = If set to true, ids of children of relationship types are included in the returned values
	 * @return array A list of corresponding type_ids 
	 */
	 public function relationshipTypeListToIDs($pm_table_name_or_num, $pa_list, $pa_options=null) {
	 	$va_rel_ids = array();
		foreach($pa_list as $vm_type) {
			if ($vn_type_id = $this->getRelationshipTypeID($pm_table_name_or_num, $vm_type, null, null, $pa_options)) {
				$va_rel_ids[] = $vn_type_id;
			}
		}
		
		if (isset($pa_options['includeChildren']) && $pa_options['includeChildren']) {
			$va_children = $va_rel_ids;
			
			foreach($va_rel_ids as $vn_id) {
				$va_children = array_merge($va_children, $this->getHierarchyChildren($vn_id, array('idsOnly' => true)));
			}
			$va_rel_ids= array_keys(array_flip($va_children));
		}
		
		return $va_rel_ids;
	}
	# ------------------------------------------------------
	/**
	 * Check a list of relationship type codes and/or ids for validity
	 *
	 * @param mixed $table_name_or_num The name or number of the relationship table that the types are valid for (Eg. ca_objects_x_entities)
	 * @param array $list A list of relationship type_code string and/or numeric type_ids
	 * @param array $options No options are supported
	 * @return array 
	 */
	 public function validateRelationshipTypeCodes($table_name_or_num, array $list, ?array $options=null) {
	 	$ret = [];
		foreach($list as $type) {
			$ret[$type] = (bool)$this->getRelationshipTypeID($table_name_or_num, $type, null, null, ['cache' => false]);
		}
		return $ret;
	}
	# ------------------------------------------------------
	/**
	 * Converts a list of relationship type_code string and/or numeric type_ids to a list of  type_code strings
	 *
	 * @param mixed $pm_table_name_or_num The name or number of the relationship table that the types are valid for (Eg. ca_objects_x_entities)
	 * @param array $pa_list A list of relationship type_code string and/or numeric type_ids
	 * @param array $pa_options Options include:
	 *			includeChildren = If set to true, ids of children of relationship types are included in the returned values
	 * @return array A list of corresponding type_codes 
	 */
	 public function relationshipTypeListToTypeCodes($pm_table_name_or_num, $pa_list, $pa_options=null) {
	 	if (!is_numeric($pm_table_name_or_num)) {
			$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);
		} else {
			$vn_table_num = $pm_table_name_or_num;
		}
		
		if (!is_array($pa_list)) { $pa_list = array($pa_list); }
	 	$o_db = $this->getDb();
	 	$qr_res = $o_db->query("
	 		SELECT type_id, type_code 
	 		FROM ca_relationship_types
	 		WHERE
	 			table_num = ?
	 	", (int)$vn_table_num);
	 	
	 	$va_type_ids_to_codes = array();
	 	while($qr_res->nextRow()) {
	 		$va_type_ids_to_codes[$qr_res->get('type_id')] = $qr_res->get('type_code');
	 	}
	 	
	 	$va_rel_type_codes = array();
	 	$va_rel_ids = array();
		foreach($pa_list as $vm_type) {
			if (isset($va_type_ids_to_codes[$vm_type])) {
				$va_rel_type_codes[$va_type_ids_to_codes[$vm_type]] = true;
				$va_rel_ids[$vm_type] = true;
			} else {
				if (in_array($vm_type, $va_type_ids_to_codes)) {
					$va_rel_type_codes[$vm_type] = true;
					$va_rel_ids[array_search($vm_type, $va_type_ids_to_codes)] = true;
				}
			}
		}
		
		if (isset($pa_options['includeChildren']) && $pa_options['includeChildren']) {
			foreach(array_keys($va_rel_ids) as $vn_id) {
				$va_children = $this->getHierarchyChildren($vn_id, array('idsOnly' => true));
				foreach($va_children as $vn_child_id) {
					//$va_rel_ids[$vn_child_id] = true;
					$va_rel_type_codes[$va_type_ids_to_codes[$vn_child_id]] = true;
				}
			}
		}
		
		return array_keys($va_rel_type_codes);
	}
	# ------------------------------------------------------
	/**
	 * Converts a list of relationship type_id's to a list of type_code strings. The conversion is literal without hierarchical expansion.
	 *
	 * @param array $pa_list A list of relationship numeric type_ids
	 * @param array $pa_options Options include:
	 *			NONE YET
	 * @return array A list of corresponding type_codes 
	 */
	 static public function relationshipTypeIDsToTypeCodes($pa_ids, $pa_options=null) {
	 	if (!is_array($pa_ids) || !sizeof($pa_ids)) { return null; }
	 	
	 	$vs_key = md5(print_r($pa_ids, true));
	 	if (isset(ca_relationship_types::$s_relationship_type_id_to_code_cache[$vs_key])) {
	 		return ca_relationship_types::$s_relationship_type_id_to_code_cache[$vs_key];
	 	}
	 	
	 	$va_ids = $va_non_numerics = array();
	 	foreach($pa_ids as $pn_id) {
	 		if (!is_numeric($pn_id)) {
	 			$va_non_numerics[] = $pn_id;
	 		} else {
	 			$va_ids = [(int)$pn_id];
	 		}
	 	}
	 	if (!sizeof($va_ids)) { return ca_relationship_types::$s_relationship_type_id_to_code_cache[$vs_key] = $pa_ids; }
	 	
	 	$o_db = new Db();
	 	$qr_res = $o_db->query("
	 		SELECT type_id, type_code 
	 		FROM ca_relationship_types
	 		WHERE
	 			type_id IN (?)
	 	", array($va_ids));
	 	
	 	$va_type_ids_to_codes = array();
	 	while($qr_res->nextRow()) {
	 		$va_type_ids_to_codes[$qr_res->get('type_id')] = $qr_res->get('type_code');
	 	}
	 	return ca_relationship_types::$s_relationship_type_id_to_code_cache[$vs_key] = array_merge($va_type_ids_to_codes, $va_non_numerics);
	}
	# ------------------------------------------------------
	 public function getHierarchyList($pb_vocabularies=false) {
	 	$va_relationship_tables = $this->getRelationshipsUsingTypes();
		if (!sizeof($va_relationship_tables)) { return array(); }
		
		$o_db = $this->getDb();
		
		// get root for each hierarchy
		$qr_res = $o_db->query("
			SELECT rt.type_id, rt.table_num
			FROM ca_relationship_types rt
			WHERE 
				rt.parent_id IS NULL and rt.table_num IN (".join(',', array_keys($va_relationship_tables)).")
		");
		
		$va_hierarchies = array();
		while ($qr_res->nextRow()) {
			$vn_type_id = $qr_res->get('type_id');
			$va_hierarchies[$vn_type_id]['type_id'] = $va_hierarchies[$vn_type_id]['item_id'] = $vn_type_id;	
			$va_hierarchies[$vn_type_id]['name'] = $va_relationship_tables[$qr_res->get('table_num')]['name'];	
			$va_hierarchies[$vn_type_id]['table_num'] = $qr_res->get('table_num');
			
			$qr_children = $o_db->query("
				SELECT count(*) children
				FROM ca_relationship_types rt
				WHERE 
					rt.parent_id = ?
			", (int)$vn_type_id);
			$vn_children_count = 0;
			if ($qr_children->nextRow()) {
				$vn_children_count = $qr_children->get('children');
			}
			$va_hierarchies[$vn_type_id]['children'] = intval($vn_children_count);
			$va_hierarchies[$vn_type_id]['has_children'] = ($vn_children_count > 0) ? 1 : 0;
		}
		
		// sort by label
		$va_hierarchies_indexed_by_label = array();
		foreach($va_hierarchies as $vs_k => $va_v) {
			$va_hierarchies_indexed_by_label[$va_v['name']][$vs_k] = $va_v;
		}
		ksort($va_hierarchies_indexed_by_label);
		$va_sorted_hierarchies = array();
		foreach($va_hierarchies_indexed_by_label as $vs_l => $va_v) {
			foreach($va_v as $vs_k => $va_hier) {
				$va_sorted_hierarchies[$vs_k] = $va_hier;
			}
		}
		return $va_sorted_hierarchies;
	 }
	 # ------------------------------------------------------
	 /**
	 * Returns name of hierarchy for currently loaded item or, if specified, item with item_id = to optional $pn_id parameter
	 */
	 public function getHierarchyName($pn_id=null) {
	 	return _t('Relationship types');
	 }
	# ------------------------------------------------------
	/**
	 * Override insert() to set table_num to whatever the parent is
	 */
	public function insert($pa_options=null) {
		if ($vn_parent_id = $this->get('parent_id')) {
			$t_root_rel_type = new ca_relationship_types($vn_parent_id);
		
			if ($vn_table_num = $t_root_rel_type->get('table_num')) {
				$this->set('table_num', $vn_table_num);
			}
		}
		
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$this->setTransaction($o_trans);
			$vb_we_set_transaction = true;
		} else {
			$o_trans = $this->getTransaction();
		}
		if ($this->get('is_default')) {
			$o_trans->getDb()->query("
				UPDATE ca_relationship_types 
				SET is_default = 0 
				WHERE table_num = ?
			", (int)$t_root_rel_type->get('table_num'));
		}
		if (!($vn_rc = parent::insert($pa_options))) {
			if ($vb_we_set_transaction) { $o_trans->rollback(); }
		} else {
			if ($vb_we_set_transaction) { $o_trans->commit(); }
		}

		$this->loadSubtypeLists();
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override update() to set table_num to whatever the parent is
	 */
	public function update($pa_options=null) {
		if ($vn_parent_id = $this->get('parent_id')) {
			$t_root_rel_type = new ca_relationship_types($vn_parent_id);
			if ($vn_table_num = $t_root_rel_type->get('table_num')) {
				$this->set('table_num', $vn_table_num);
			}
		}
		
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$this->setTransaction($o_trans);
			$vb_we_set_transaction = true;
		} else {
			$o_trans = $this->getTransaction();
		}
		
		if ($this->get('is_default')) {
			$o_trans->getDb()->query("
				UPDATE ca_relationship_types 
				SET is_default = 0 
				WHERE table_num = ? AND type_id <> ?
			",[(int)$t_root_rel_type->get('table_num'), $this->getPrimaryKey()]);
		}
		if (!($vn_rc = parent::update($pa_options))) {
			if ($vb_we_set_transaction) { $o_trans->rollback(); }
		} else {
			if ($vb_we_set_transaction) { $o_trans->commit(); }
		}

		$this->loadSubtypeLists();
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function getRelationshipCountForType($pm_table_name_or_num=null, $pm_type_code_or_id=null) {
		if ($pm_table_name_or_num && $pm_type_code_or_id) {
			$vn_type_id = $this->getRelationshipTypeID($pm_table_name_or_num, $pm_type_code_or_id);
		} else {
			$vn_type_id = $this->getPrimaryKey();
			$pm_table_name_or_num = $this->get('table_num');
		}
		if (!$vn_type_id) { return null; }
		
		$va_info = $this->getRelationshipInfo($pm_table_name_or_num);
		if (!isset($va_info[$vn_type_id])) { return null; }
		
		$vn_rel_table_num = $va_info[$vn_type_id]['table_num'];
		if ($vs_rel_table_name = Datamodel::getTableName($vn_rel_table_num)) {
			$qr_res = $this->getDb()->query("
				SELECT count(*) c
				FROM {$vs_rel_table_name}
				WHERE
					type_id = ?
			", (int)$vn_type_id);
			if ($qr_res->nextRow()) {
				return (int)$qr_res->get('c');
			}
			return 0;
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function moveRelationshipsToType($pm_type_code_or_id) {
		if (!($vn_source_id = $this->getPrimaryKey())) { return null; }
		if (!($vn_type_id = $this->getRelationshipTypeID($vn_table_num = $this->get('table_num'), $pm_type_code_or_id))) { return null; }
		
		if (!($vs_table_name = Datamodel::getTableName($vn_table_num))) { return null; }
		$qr_res = $this->getDb()->query("
				SELECT * 
				FROM {$vs_table_name}
				WHERE type_id = ?
			", (int)$vn_source_id);
			
		$va_to_reindex_relations = array();
		while($qr_res->nextRow()) {
			$va_to_reindex_relations[$qr_res->get('relation_id')] = $qr_res->getRow();
		}
		$qr_res = $this->getDb()->query("
				UPDATE {$vs_table_name}
				SET type_id = ? WHERE type_id = ?
			", (int)$vn_type_id, (int)$vn_source_id);
		if ($this->getDb()->numErrors() > 0) {
			$this->errors = $this->getDb()->errors;
			return null;
		}
		$vn_num_rows = (int)$this->getDb()->affectedRows();
		
		// Reindex modified relationships
		$si = $this->getSearchIndexer();
		foreach($va_to_reindex_relations as $vn_relation_id => $va_row) {
			$si->indexRow($vn_table_num, $vn_relation_id, $va_row, false, null, ['type_id' => true], ['queueIndexing' => true]);
		}
		
		return $vn_num_rows;
	}
	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is save-able
 	 *
 	 * @param RequestHTTP $po_request
 	 * @return bool True if record can be saved, false if not
 	 */
 	public function isSaveable($po_request, $ps_bundle_name=null) {
 		// Check actions
 		if ($po_request->user->canDoAction('is_administrator') || $po_request->user->canDoAction('can_configure_relationship_types')) {
 			return true;
 		}
 		
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is deletable
 	 */
 	public function isDeletable($po_request) {
 		// Is row loaded?
 		if (!$this->getPrimaryKey()) { return false; }
 			
 		// Check actions
 		if ($this->getPrimaryKey() && ($po_request->user->canDoAction('is_administrator') || $po_request->user->canDoAction('can_configure_relationship_types'))) {
 			return true;
 		}
 		
 		return false;
 	}
 	# ----------------------------------------
	# Type restrictions
	# ----------------------------------------
	/**
	 * Adds restriction to relationship type
	 *
	 * @param int $left_type_id 
	 * @param int $right_type_id 
	 * @param array $settings Options include:
	 *		includeSubtypes = automatically expand type restriction to include sub-types. [Default is false]
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function addTypeRestriction(?int $left_type_id, ?int $right_type_id, ?array $settings=null) {
		if (!($type_id = $this->getPrimaryKey())) { return null; }		// relationshp must be loaded
		if (!is_array($settings)) { $settings = []; }
		
		$t_restriction = new ca_relationship_type_restrictions();
		$t_restriction->set('type_id', $this->getPrimaryKey());
		$t_restriction->set('sub_type_left_id', $left_type_id);
		$t_restriction->set('sub_type_right_id', $right_type_id);
		//$t_restriction->set('include_subtypes', caGetOption('includeSubtypes', $settings, 0));
		
		unset($settings['includeSubtypes']);
		foreach($settings as $setting => $setting_value) {
			$t_restriction->setSetting($setting, $setting_value);
		}
		$t_restriction->insert();
		
		if ($t_restriction->numErrors()) {
			$this->errors = $t_restriction->errors();
			return false;
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Edit settings for an existing type restriction on the currently loaded row
	 *
	 * @param int $restriction_id
	 * @param int $type_id New type for relationship
	 */
	public function editTypeRestriction(int $restriction_id, ?int $left_type_id, ?int $right_type_id, ?array $settings=null) {
		if (!($type_id = $this->getPrimaryKey())) { return null; }		// relationship type must be loaded
		$t_restriction = new ca_relationship_type_restrictions($restriction_id);
		if ($t_restriction->isLoaded()) {
			$t_restriction->set('sub_type_left_id', $left_type_id);
			$t_restriction->set('sub_type_right_id', $right_type_id);
			//$t_restriction->set('include_subtypes', caGetOption('includeSubtypes', $settings, 0));
			$t_restriction->update();
			if ($t_restriction->numErrors()) {
				$this->errors = $t_restriction->errors();
				return false;
			}
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Sets restrictions for currently loaded ui
	 *
	 * @param array $type_ids list of types to restrict to
	 * @param array $options Options include:
	 *		includeSubtypes = Automatically include subtypes for all set type restrictions. [Default is false]
	 * @return bool True on success, false on error, null if no screen is loaded
	 * 
	 */
	public function setTypeRestrictions(array $restrictions, ?array $options=null) {
		if (!($type_id = $this->getPrimaryKey())) { return null; }		// relationship type must be loaded
		if (!is_array($restrictions)) {
			return null;
		}
		
		$current_restrictions = $this->getTypeRestrictions();
		$current_type_ids = [];
		foreach($current_restrictions as $i => $restriction) {
			$current_type_ids[$restriction['type_id']] = $restriction['restriction_id'];
		}
		
		foreach($restrictions as $k => $r) {
			if(preg_match("!^new_[\d]+$!", $k)) {
				$this->addTypeRestriction($r['sub_type_left_id'], $r['sub_type_right_id'], $options);
			} elseif(is_numeric($k)) {
				$cr = $current_restrictions[$k];
				$this->editTypeRestriction($cr['restriction_id'], $r['sub_type_left_id'], $r['sub_type_right_id']);
				unset($current_restrictions[$k]);
			}
		}
		foreach($current_restrictions as $r) {
			$this->removeTypeRestriction($r['restriction_id']);
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Remove restriction from currently loaded relationship type for specified type pair
	 *
	 * @param int $type_id The type of the restriction
	 * @return bool True on success, false on error, null if no screen is loaded
	 */
	public function removeTypeRestriction(int $restriction_id) : ?bool {
		if (!($type_id = (int)$this->getPrimaryKey())) { return null; }		// relationship type must be loaded

		$params = ['type_id' => $type_id];
		if ((int)$type_id > 0) { $params['type_id'] = (int)$type_id; }

		if ($t_restriction = ca_relationship_type_restrictions::find($restriction_id)) {
			$t_restriction->delete(true);
			if ($t_restriction->numErrors()) {
				$this->errors = $t_restriction->errors();
				return false;
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Remove all type restrictions from loaded ui
	 *
	 * @return bool True on success, false on error, null if no screen is loaded 
	 */
	public function removeAllTypeRestrictions() : ?bool {
		return $this->removeTypeRestriction();
	}
	# ----------------------------------------
	/**
	 * Return restrictions for currently loaded relationship type
	 *
	 * @param int Type_id of relationship type to return restrictios for. If null currentload loaded type is used. [Default is null]
	 * @return array A list of restrictions, false on error or null if no relationship type is loaded
	 */
	public function getTypeRestrictions(?int $type_id=null) : ?array {
		if(!$type_id) {
			if (!($type_id = (int)$this->getPrimaryKey())) { return null; }
		}
		
		$params = ['type_id' => $type_id];
		if ((int)$type_id > 0) { $params['type_id'] = (int)$type_id; }

		return ca_relationship_type_restrictions::find($params, ['returnAs' => 'arrays']);
	}
 	# ----------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of type restriction in the currently loaded ui
	 * 
	 * @param object $request The current request object
	 * @param string $form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getTypeRestrictionsHTMLFormBundle(RequestHTTP $request, string $form_name, string $placement_code, ?array $options=null) : ?string {
		$o_view = new View($request, $request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_relationship_type', $this);
		$o_view->setVar('id_prefix', $form_name);
		$o_view->setVar('placement_code', $placement_code);
		$o_view->setVar('request', $request);
		
		$type_restrictions = $this->getTypeRestrictions();
		$restriction_type_ids = [];
		$include_subtypes = false;
		if (is_array($type_restrictions)) {
			foreach($type_restrictions as $i => $restriction) {
				$restriction_type_ids[] = $restriction['type_id'];
				if ($restriction['include_subtypes'] && !$include_subtypes) { $include_subtypes = true; }
			}
		}
		
		$o_view->setVar('type_restrictions', $type_restrictions);
		
		$type_lists = $this->getRelatedTypeLists();
		$o_view->setVar('left_select', caHTMLSelect("{$placement_code}{$form_name}sub_type_left_id_{n}", $type_lists['left'], ['id' => "{$placement_code}{$form_name}sub_type_left_id_{n}"], ['value' => "sub_type_left_id"]));
		$o_view->setVar('right_select', caHTMLSelect("{$placement_code}{$form_name}sub_type_right_id_{n}", $type_lists['right'], ['id' => "{$placement_code}{$form_name}sub_type_right_id_{n}"], ['value' => "sub_type_right_id"]));
		$o_view->setVar('left_label',  caUcFirstUTF8Safe($type_lists['left_label']));
		$o_view->setVar('right_label',  caUcFirstUTF8Safe($type_lists['right_label']));
		
		
		return $o_view->render('ca_relationship_type_restrictions.php');
	}
	# ----------------------------------------
	/**
	 * Save type restrictions for bundle in editor
	 */
	public function saveTypeRestrictionsFromHTMLForm(RequestHTTP $request, string $form_prefix, string $placement_code) : ?array {
		if (!$this->getPrimaryKey()) { return null; }
		
		$acc = [];
		foreach($_REQUEST as $k => $v) {
			if(preg_match("!^{$placement_code}{$form_prefix}(sub_type_left_id|sub_type_right_id)_(.*)$!", $k, $m)) {
				$acc[$m[2]][$m[1]] = $v;
			}
		}
		
		return $this->setTypeRestrictions($acc, [
			//'includeSubtypes' => $request->getParameter('type_restriction_include_subtypes', pInteger)
		]);
	}
	# ------------------------------------------------------
	/**
	 * Return type lists for tables at each end of relationship. An array
	 * is returned with the following keys:
	 *
	 * 	left = List of types for table on the left side of the relationship
	 * 	left_label = Display label for left-side type list
	 * 	right = List of types for table on the right side of the relationship
	 * 	right_label = Display label for right-side type list
	 *
	 * @return array Type list
	 */
	protected function getRelatedTypeLists() : ?array {
		$left_type_list = $right_type_list = [];
		if ($table_num = $this->get('table_num')) {
			$relationship_tables = $this->getRelationshipsUsingTypes();
			if (!isset($relationship_tables[$table_num])) { return null; }
			
			$t_rel_instance = Datamodel::getInstanceByTableNum($table_num, true);
			$t_instance = Datamodel::getInstanceByTableName($t_rel_instance->getLeftTableName(), true);
			
			
			$left_name = $t_instance->getProperty('NAME_PLURAL');
			if (method_exists($t_instance, 'getTypeList')) {
				$types = $t_instance->getTypeList();
				
				$left_type_list[_t('- ANY -')] = '';
				foreach($types as $type_id => $type_info) {
					$left_type_list[$type_info['name_plural']] = $type_id;
				}
			}
			
			$t_instance = Datamodel::getInstanceByTableName($t_rel_instance->getRightTableName(), true);
			
			$right_name = $t_instance->getProperty('NAME_PLURAL');
			if (method_exists($t_instance, 'getTypeList')) {
				$types = $t_instance->getTypeList();
			
				$right_type_list[_t('- ANY -')] = '';
				foreach($types as $type_id => $type_info) {
					$right_type_list[$type_info['name_plural']] = $type_id;
				}
			}
		}
		
		return ['left' => $left_type_list, 'left_label' => $left_name, 'right' => $right_type_list, 'right_label' => $right_name];
	}
	# ------------------------------------------------------
}
