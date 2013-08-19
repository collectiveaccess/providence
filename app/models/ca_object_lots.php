<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_lots.php : table access class for table ca_object_lots
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if object lot is accessible to the public or not. ')
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

class ca_object_lots extends RepresentableBaseModel {
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
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lots'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
	}
 	# ------------------------------------------------------
 	/**
 	 * Returns the number of ca_object rows related to the currently loaded object lot.
 	 *
 	 * @param int $pn_lot_id Optional lot_id to get object count for; if null then the id of the currently loaded lot will be used
 	 * @return int Number of objects related to the object lot or null if $pn_lot_id is not set and there is no currently loaded lot
 	 */
 	 public function numObjects($pn_lot_id=null) {
 	 	if (!$pn_lot_id) {
 	 		if (!($vn_lot_id = $this->getPrimaryKey())) {
				return null;
 	 		}
 	 	}
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
				SELECT count(*) c
				FROM ca_objects
				WHERE
					lot_id = ? AND deleted = 0
			", (int)$vn_lot_id);
			
		$qr_res->nextRow();
		return (int)$qr_res->get('c');
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
 	 * Returns a list of ca_object rows related to the currently loaded object lot.
 	 *
 	 * @param int $pn_lot_id Optional lot_id to get object list for; if null then the id of the currently loaded lot will be used
 	 * @return array List of objects related to the object lot or null if $pn_lot_id is not set and there is no currently loaded lot
 	 */
 	 public function getObjects($pn_lot_id=null) {
 	 	if (!$pn_lot_id) {
 	 		if (!($vn_lot_id = $this->getPrimaryKey())) {
				return null;
 	 		}
 	 	}
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
				SELECT *
				FROM ca_objects
				WHERE
					lot_id = ? AND deleted = 0
				ORDER BY
					idno_sort
			", (int)$vn_lot_id);
			
		$va_rows = array();
		while($qr_res->nextRow()) {
			$va_rows[$qr_res->get('object_id')] = $qr_res->getRow();
		}
		
		return $va_rows;
	}
	# ------------------------------------------------------
 	/**
 	 * 
 	 *
 	 * @return array List of objects with non-conforming idnos, or false if there are no non-conforming objects
 	 */
 	 public function getObjectsWithNonConformingIdnos() {
 	 	if (!$this->getPrimaryKey()) { return false; }
		
		$t_object = new ca_objects();
		$t_idno = $t_object->getIDNoPlugInInstance();
		$vs_separator = $t_idno->getSeparator();
		$va_objects = $this->getObjects();
		$vs_lot_num = $this->get('idno_stub');
		
		$va_non_conforming_objects= array();
		foreach($va_objects as $va_object) {
			if (!preg_match("!^{$vs_lot_num}{$vs_separator}!", $va_object['idno'])) {
				$va_non_conforming_objects[$va_object['object_id']] = $va_object;
			}
		}
		
		if (sizeof($va_non_conforming_objects)) {
			return $va_non_conforming_objects;
		} else {
			return false;
		}
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
			
			$t = new Transaction();
			$t_object = new ca_objects();
			$t_object->setTransaction($t);
			$t_idno = $t_object->getIDNoPlugInInstance();
			$vs_separator = $t_idno->getSeparator();
			$vn_i = 1;
			foreach($va_objects as $vn_object_id => $va_object_info) {
				if ($t_object->load($vn_object_id)) {
					if ($po_application_plugin_manager) {
						$po_application_plugin_manager->hookBeforeSaveItem(array('id' => $vn_object_id, 'table_num' => $t_object->tableNum(), 'table_name' => $t_object->tableName(), 'instance' => $t_object));
					}
					$t_object->setMode(ACCESS_WRITE);
					$t_object->set('idno', $vs_lot_num.$vs_separator.$vn_i);
					$t_object->update();
					if ($t_object->numErrors()) {
						$t->rollback();
						$this->errors = $t_object->errors;
						return false;
					}
					if ($po_application_plugin_manager) {
						$po_application_plugin_manager->hookSaveItem(array('id' => $vn_object_id, 'table_num' => $t_object->tableNum(), 'table_name' => $t_object->tableName(), 'instance' => $t_object));
					}
					$vn_i++;
				}
			}
			$t->commit();
		}
		
		return true;
	}
 	# ------------------------------------------------------
}
?>