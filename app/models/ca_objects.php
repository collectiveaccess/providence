<?php
/** ---------------------------------------------------------------------
 * app/models/ca_objects.php : table access class for table ca_objects
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
require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_objects_x_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_commerce_orders.php");
require_once(__CA_MODELS_DIR__."/ca_commerce_order_items.php");
require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");


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
				'BOUNDS_VALUE' => array(0,1)
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
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Do not inherit access settings from parent') => 0,
					_t('Inherit access settings from parent') => 1
				),
				'LABEL' => _t('Inherit access settings from parent?'), 'DESCRIPTION' => _t('Determines whether access settings set for parent objects are applied to this object.')
		)
	)
);

class ca_objects extends RepresentableBaseModel implements IBundleProvider {
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
			'lot_id'
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
		$this->BUNDLES['ca_sets'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
		
		$this->BUNDLES['ca_commerce_order_history'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Order history'));
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
				if ($t_lot->load(array('idno_stub' => $vs_val))) {
					$vn_lot_id = (int)$t_lot->getPrimaryKey();
					$pm_fields[$vs_fld] = $vn_lot_id;
				}
			}
		}
		return parent::set($pm_fields, null, $pa_options);
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null){
		// nuke related representations
		foreach($this->getRepresentations() as $va_rep){
			// check if representation is in use anywhere else 
			$qr_res = $this->getDb()->query("SELECT count(*) c FROM ca_objects_x_object_representations WHERE object_id <> ? AND representation_id = ?", (int)$this->getPrimaryKey(), (int)$va_rep["representation_id"]);
			if ($qr_res->nextRow() && ($qr_res->get('c') == 0)) {
				$this->removeRepresentation($va_rep["representation_id"], array('dontCheckPrimaryValue' => true));
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
	
 	
 	# ------------------------------------------------------
 	#
 	# ------------------------------------------------------
	/**
	 * Return array containing information about all hierarchies, including their root_id's
	 * For non-adhoc hierarchies such as places, this call returns the contents of the place_hierarchies list
	 * with some extra information such as the # of top-level items in each hierarchy.
	 *
	 * For an ad-hoc hierarchy like that of an object, there is only ever one hierarchy to display - that of the current object.
	 * So for adhoc hierarchies we just return a single entry corresponding to the root of the current object hierarchy
	 */
	 public function getHierarchyList($pb_dummy=false) {
	 	$vn_pk = $this->getPrimaryKey();
	 	$vs_template = $this->getAppConfig()->get('ca_objects_hierarchy_browser_display_settings');
	 	
	 	if (!$vn_pk) { 
	 		$o_db = new Db();
	 		if (is_array($va_type_ids = caMergeTypeRestrictionLists($this, array())) && sizeof($va_type_ids)) {
				$qr_res = $o_db->query("
					SELECT o.object_id, count(*) c
					FROM ca_objects o
					INNER JOIN ca_objects AS p ON p.parent_id = o.object_id
					WHERE o.parent_id IS NULL AND o.type_id IN (?)
					GROUP BY o.object_id
				", array($va_type_ids));
			} else {
				$qr_res = $o_db->query("
					SELECT o.object_id, count(*) c
					FROM ca_objects o
					INNER JOIN ca_objects AS p ON p.parent_id = o.object_id
					WHERE o.parent_id IS NULL
					GROUP BY o.object_id
				");
			}
	 		$va_hiers = array();
	 		
	 		$va_object_ids = $qr_res->getAllFieldValues('object_id');
	 		$qr_res->seek(0);
	 		$va_labels = $this->getPreferredDisplayLabelsForIDs($va_object_ids);
	 		while($qr_res->nextRow()) {
	 			$va_hiers[$vn_object_id = $qr_res->get('object_id')] = array(
	 				'object_id' => $vn_object_id,
	 				'item_id' => $vn_object_id,
	 				'name' => caProcessTemplateForIDs($vs_template, 'ca_objects', array($vn_object_id)),
	 				'hierarchy_id' => $vn_object_id,
	 				'children' => (int)$qr_res->get('c')
	 			);
	 		}
	 		return $va_hiers;
	 	} else {
	 		// return specific object as root of hierarchy
			$vs_label = $this->getLabelForDisplay(false);
			$vs_hier_fld = $this->getProperty('HIERARCHY_ID_FLD');
			$vs_parent_fld = $this->getProperty('PARENT_ID_FLD');
			$vn_hier_id = $this->get($vs_hier_fld);
			
			if ($this->get($vs_parent_fld)) { 
				// currently loaded row is not the root so get the root
				$va_ancestors = $this->getHierarchyAncestors();
				if (!is_array($va_ancestors) || sizeof($va_ancestors) == 0) { return null; }
				$t_object = new ca_objects($va_ancestors[0]);
			} else {
				$t_object =& $this;
			}
			
			$va_children = $t_object->getHierarchyChildren(null, array('idsOnly' => true));
			$va_object_hierarchy_root = array(
				$t_object->get($vs_hier_fld) => array(
					'object_id' => $vn_pk,
	 				'item_id' => $vn_pk,
					'name' => $vs_name = caProcessTemplateForIDs($vs_template, 'ca_objects', array($vn_pk)),
					'hierarchy_id' => $vn_hier_id,
					'children' => sizeof($va_children)
				)
			);
				
	 		return $va_object_hierarchy_root;
		}
	}
	# ------------------------------------------------------
	/**
	 * Returns name of hierarchy for currently loaded row or, if specified, row identified by optional $pn_id parameter
	 *
	 * @param int $pn_id Optional object_id to return hierarchy name for. If not specified, the currently loaded row is used.
	 * @return string The name of the hierarchy
	 */
	 public function getHierarchyName($pn_id=null) {
	 	if (!$pn_id) { $pn_id = $this->getPrimaryKey(); }
	 	
		$va_ancestors = $this->getHierarchyAncestors($pn_id, array('idsOnly' => true));
		if (is_array($va_ancestors) && sizeof($va_ancestors)) {
			$vn_parent_id = array_pop($va_ancestors);
			$t_object = new ca_objects($vn_parent_id);
			return $t_object->getLabelForDisplay(false);
		} else {			
			if ($pn_id == $this->getPrimaryKey()) {
				return $this->getLabelForDisplay(true);
			} else {
				$t_object = new ca_objects($pn_id);
				return $t_object->getLabelForDisplay(true);
			}
		}
	 }
	# ------------------------------------------------------
	/**
	 * Return object_ids for objects with labels exactly matching $ps_name
	 *
	 * @param string $ps_name The label value to search for
	 * @param int $pn_parent_id Optional parent_id. If specified search is restricted to direct children of the specified parent object.
	 * @param int $pn_type_id Optional type_id.
	 * @return array An array of object_ids
	 */
	public function getObjectIDsByName($ps_name, $pn_parent_id=null, $pn_type_id=null) {
		$o_db = $this->getDb();
		
		$va_params = array((string)$ps_name);
		
		$vs_type_sql = '';
		if ($pn_type_id) {
			if(sizeof($va_type_ids = caMakeTypeIDList('ca_objects', array($pn_type_id)))) {
				$vs_type_sql = " AND cap.type_id IN (?)";
				$va_params[] = $va_type_ids;
			}
		}
		
		if ($pn_parent_id) {
			$vs_parent_sql = " AND cap.parent_id = ?";
			$va_params[] = (int)$pn_parent_id;
		} 
		
		$qr_res = $o_db->query($x="
				SELECT DISTINCT cap.object_id
				FROM ca_objects cap
				INNER JOIN ca_object_labels AS capl ON capl.object_id = cap.object_id
				WHERE
					capl.name = ? {$vs_type_sql} {$vs_parent_sql} AND cap.deleted = 0
			", $va_params);
		
		$va_object_ids = array();
		while($qr_res->nextRow()) {
			$va_object_ids[] = $qr_res->get('object_id');
		}
		return $va_object_ids;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getIDsByLabel($pa_label_values, $pn_parent_id=null, $pn_type_id=null) {
		return $this->getObjectIDsByName($pa_label_values['name'], $pn_parent_id, $pn_type_id);
	}
 	# ------------------------------------------------------
 	# Client services
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function isOnLoan() {
 		if (!$this->getPrimaryKey()) { return null; }
 		$t_order = new ca_commerce_orders();
 		if (is_array($va_orders = $t_order->getOrders(array('object_id' => $this->getPrimaryKey(), 'type' => 'L'))) && sizeof($va_orders)) {
 			$va_order = array_shift($va_orders);
 			$t_order_item = new ca_commerce_order_items();
 			if ($t_order_item->load(array('order_id' => $va_order['order_id'], 'object_id' => $this->getPrimaryKey()))) {
 				if (!$t_order_item->get('loan_return_date', array('GET_DIRECT_DATE' => true))) {
 					return array(
 						'loan_checkout_date' => $t_order_item->get('loan_checkout_date'),
 						'loan_checkout_date_raw' => $t_order_item->get('loan_checkout_date', array('GET_DIRECT_DATE' => true)),
 						'loan_due_date' => $t_order_item->get('loan_due_date'),
 						'loan_due_date_raw' => $t_order_item->get('loan_due_date', array('GET_DIRECT_DATE' => true)),
 						'client' => $va_order['billing_fname'].' '.$va_order['billing_lname']." (".$va_order['billing_email'].")",
 						'billing_fname' => $va_order['billing_fname'],
 						'billing_lname' => $va_order['billing_lname'],
 						'billing_email' => $va_order['billing_email'],
 						'order_id' => $va_order['order_id']
 					);
 				}
 			}
 		}
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns history of client orders. Orders are sorted most recent first.
 	 *
 	 * @param string $ps_order_type Type of order to return history for. L=loans, O=sales orders. If set to any other value all types of orders will be returned.
 	 * @return array List of orders
 	 */
 	public function getClientHistory($ps_order_type) {
 		if (!$this->getPrimaryKey()) { return null; }
 		$vn_object_id = $this->getPrimaryKey();
 		$ps_order_type = strtoupper($ps_order_type);
 		
 		$va_options = array();
 		if (!in_array($ps_order_type, array('O', 'L'))) { $ps_order_type = null; } else { $va_options['type'] = $ps_order_type; }
 		
 		$va_orders = ca_commerce_orders::getUsageOfItemInOrders($vn_object_id, $va_options);
 		
 		$va_history = array();
		foreach($va_orders as $vn_id => $va_order) {
			$va_order['loan_checkout_date_raw'] = $va_order['loan_checkout_date'];
			$va_order['loan_checkout_date'] = caGetLocalizedDate($va_order['loan_checkout_date'], array('timeOmit' => true, 'dateFormat' => 'delimited')); 
			
			$va_order['loan_due_date_raw'] = $va_order['loan_due_date'];
			$va_order['loan_due_date'] = $va_order['loan_due_date'] ? caGetLocalizedDate($va_order['loan_due_date'], array('timeOmit' => true, 'dateFormat' => 'delimited')) : ''; 
			
			$va_order['loan_return_date_raw'] = $va_order['loan_return_date'];
			$va_order['loan_return_date'] = $va_order['loan_return_date'] ? caGetLocalizedDate($va_order['loan_return_date'], array('timeOmit' => true, 'dateFormat' => 'delimited')) : ''; 
			
			$va_order['order_number'] = ca_commerce_orders::generateOrderNumber($va_order['order_id'], $va_order['created_on']);
			$va_history[$va_order['loan_checkout_date']] = $va_order;
		}
		ksort($va_history);
		return array_reverse($va_history);;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return history for client loans of the currently loaded object
 	 *
 	 * @return array Loan history
 	 */
 	public function getClientLoanHistory() {
 		return $this->getClientHistory('L');
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return history for client sales orders that include the currently loaded object
 	 *
 	 * @return array Loan history
 	 */
 	public function getClientOrderHistory() {
 		return $this->getClientHistory('O');
 	}
 	# ------------------------------------------------------
 	# HTML form bundles
 	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle (for use in a ca_object_representations editor form) for media
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
	public function getCommerceOrderHistoryHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name.'_commerce_order_history');
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
		
		
		
		return $o_view->render('ca_commerce_order_history.php');
	}
 	# ------------------------------------------------------
}
?>