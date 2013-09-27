<?php
/** ---------------------------------------------------------------------
 * app/models/ca_entities.php : table access class for table ca_entities
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


BaseModel::$s_ca_models_definitions['ca_entities'] = array(
 	'NAME_SINGULAR' 	=> _t('entity'),
 	'NAME_PLURAL' 		=> _t('entities'),
 	'FIELDS' 			=> array(
 		'entity_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this entity')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Identifier of parent entity'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the entity originates.')
		),
		'source_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'entity_sources',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of the entity. This value is often used to indicate the administrative sub-division or legacy database from which the entity information originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LIST_CODE' => 'entity_types',
				'LABEL' => _t('Entity type'), 'DESCRIPTION' => _t('The type of the entity. eg. individual, organization, family, etc.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Entity identifier'), 'DESCRIPTION' => _t('A unique alphanumeric identifier for this entity.'),
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
				'LABEL' => _t('Data source information'), 'DESCRIPTION' => _t('Serialized array used to store source information for entity information retrieved via web services [NOT IMPLEMENTED YET].')
		),
		'lifespan' => array(
				'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'START' => 'life_sdatetime', 'END' => 'life_edatetime',
				'LABEL' => _t('Lifetime'), 'DESCRIPTION' => _t('Lifetime of entity (date range)')
		),
		'hier_entity_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Entity hierarchy'), 'DESCRIPTION' => _t('Identifier of entity that is root of the entity hierarchy.')
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if entity information is accessible to the public or not. ')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the entity record.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the entity is deleted or not.'),
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

class ca_entities extends RepresentableBaseModel implements IBundleProvider {
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
	protected $TABLE = 'ca_entities';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'entity_id';

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
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_entities';
	protected $HIERARCHY_ID_FLD				=	'hier_entity_id';
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
	protected $LABEL_TABLE_NAME = 'ca_entity_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'entity_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_entities_x_entities';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'EntitySearch';
	protected $SEARCH_RESULT_CLASSNAME = 'EntitySearchResult';
	
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
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
		$this->BUNDLES['ca_object_representations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Media representations'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lot'));
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
	}
 	# ------------------------------------------------------
	/**
	 * Returns entity_id for entities with matching fore- and surnames
	 *
	 * @param string $ps_forename The forename to search for
	 * @param string $ps_surnamae The surname to search for
	 * @return array Entity_id's for matching entities
	 */
	public function getEntityIDsByName($ps_forename, $ps_surname, $pn_parent_id=null, $pn_type_id=null) {
		$o_db = $this->getDb();
		
		$va_params = array((string)$ps_forename, (string)$ps_surname);
		
		$vs_type_sql = '';
		if ($pn_type_id) {
			if(sizeof($va_type_ids = caMakeTypeIDList('ca_entities', array($pn_type_id)))) {
				$vs_type_sql = " AND cae.type_id IN (?)";
				$va_params[] = $va_type_ids;
			}
		}
		
		if ($pn_parent_id) {
			$vs_parent_sql = " AND cae.parent_id = ?";
			$va_params[] = (int)$pn_parent_id;
		} 
		
		
		$qr_res = $o_db->query("
			SELECT DISTINCT cae.entity_id
			FROM ca_entities cae
			INNER JOIN ca_entity_labels AS cael ON cael.entity_id = cae.entity_id
			WHERE
				cael.forename = ? AND cael.surname = ? {$vs_type_sql} {$vs_parent_sql} AND cae.deleted = 0
		", $va_params);
		
		$va_entity_ids = array();
		while($qr_res->nextRow()) {
			$va_entity_ids[] = $qr_res->get('entity_id');
		}
		return $va_entity_ids;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getIDsByLabel($pa_label_values, $pn_parent_id=null, $pn_type_id=null) {
		if (!isset($pa_label_values['forename']) && !isset($pa_label_values['surname']) && isset($pa_label_values['displayname'])) {
			$pa_label_values = DataMigrationUtils::splitEntityName($pa_label_values['displayname']);
		}
		return $this->getEntityIDsByName($pa_label_values['forename'], $pa_label_values['surname'], $pn_parent_id, $pn_type_id);
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
		
		// get entity counts
		$vs_sql = "
			SELECT ca_entities.entity_id, count(*) cnt
			FROM ca_entities
			".join("\n", $va_joins)."
			".(sizeof($va_sql_wheres) ? " WHERE ".join(' AND ', $va_sql_wheres) : "")."
			GROUP BY
				ca_entities.entity_id, {$vs_table_name}.".$t_ref_table->primaryKey()."
		";
		$qr_items = $o_db->query($vs_sql);
		
		$va_item_counts = array();
		while($qr_items->nextRow()) {
			$va_item_counts[$qr_items->get('entity_id')]++;
		}
		
		$vs_sql = "
			SELECT ca_entities.entity_id, ca_entities.idno, ca_entities.type_id, 
			ca_entity_labels.forename, ca_entity_labels.middlename, ca_entity_labels.surname, ca_entity_labels.displayname,
			count(*) c
			FROM ca_entities
			INNER JOIN ca_entity_labels ON ca_entity_labels.entity_id = ca_entities.entity_id
			".join("\n", $va_joins)."
			WHERE
				(ca_entity_labels.is_preferred = 1)
				".(sizeof($va_sql_wheres) ? " AND ".join(' AND ', $va_sql_wheres) : "")."
			GROUP BY
				ca_entity_labels.label_id
			ORDER BY ca_entity_labels.surname, ca_entity_labels.forename
		";
		
		$qr_items = $o_db->query($vs_sql);
		
		$va_items = array();
		while($qr_items->nextRow()) {
			$vn_entity_id = $qr_items->get('entity_id');
			$va_items[$vn_entity_id][$qr_items->get('locale_id')] = array_merge($qr_items->getRow(), array('cnt' => $va_item_counts[$vn_entity_id]));
		}
		
		return caExtractValuesByUserLocale($va_items);
	}
	# ------------------------------------------------------
	/**
	 * Return array containing information about all hierarchies, including their root_id's
	 * For non-adhoc hierarchies such as places, this call returns the contents of the place_hierarchies list
	 * with some extra information such as the # of top-level items in each hierarchy.
	 *
	 * For an ad-hoc hierarchy like that of an entity, there is only ever one hierarchy to display - that of the current entity.
	 * So for adhoc hierarchies we just return a single entry corresponding to the root of the current entity hierarchy
	 */
	 public function getHierarchyList($pb_dummy=false) {
	 	$vn_pk = $this->getPrimaryKey();
	 	if (!$vn_pk) { return null; }		// have to load a row first
	 	$vs_template = $this->getAppConfig()->get('ca_entities_hierarchy_browser_display_settings');
	 	
	 	$vs_label = $this->getLabelForDisplay(false);
	 	$vs_hier_fld = $this->getProperty('HIERARCHY_ID_FLD');
	 	$vs_parent_fld = $this->getProperty('PARENT_ID_FLD');
	 	$vn_hier_id = $this->get($vs_hier_fld);
	 	
	 	if ($this->get($vs_parent_fld)) { 
	 		// currently loaded row is not the root so get the root
	 		$va_ancestors = $this->getHierarchyAncestors();
	 		if (!is_array($va_ancestors) || sizeof($va_ancestors) == 0) { return null; }
	 		$t_entity = new ca_entities($va_ancestors[0]);
	 	} else {
	 		$t_entity =& $this;
	 	}
	 	
	 	$va_children = $t_entity->getHierarchyChildren(null, array('idsOnly' => true));
	 	$va_entity_hierarchy_root = array(
	 		$t_entity->get($vs_hier_fld) => array(
	 			'entity_id' => $vn_pk,
	 			'name' => $vs_name = caProcessTemplateForIDs($vs_template, 'ca_entities', array($vn_pk)),
	 			'hierarchy_id' => $vn_hier_id,
	 			'children' => sizeof($va_children)
	 		),
	 		'entity_id' => $vn_pk,
			'name' => $vs_name,
			'hierarchy_id' => $vn_hier_id,
			'children' => sizeof($va_children)
	 	);
	 	
	 	return $va_entity_hierarchy_root;
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
			$t_entity = new ca_entities($vn_parent_id);
			return $t_entity->getLabelForDisplay(false);
		} else {			
			if ($pn_id == $this->getPrimaryKey()) {
				return $this->getLabelForDisplay(true);
			} else {
				$t_entity = new ca_entities($pn_id);
				return $t_entity->getLabelForDisplay(false);
			}
		}
	}
	# ------------------------------------------------------
}
?>