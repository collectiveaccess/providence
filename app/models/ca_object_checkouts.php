<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_checkouts.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
require_once(__CA_MODELS_DIR__.'/ca_objects.php');

/**
 * Check out statuses
 */
define("__CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__", 0);
define("__CA_OBJECTS_CHECKOUT_STATUS_OUT__", 1);
define("__CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__", 2);
define("__CA_OBJECTS_CHECKOUT_STATUS_RESERVED__", 3);


BaseModel::$s_ca_models_definitions['ca_object_checkouts'] = array(
 	'NAME_SINGULAR' 	=> _t('object checkout'),
 	'NAME_PLURAL' 		=> _t('object checkouts'),
 	'FIELDS' 			=> array(
 		'checkout_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '','LABEL' => _t('Checkout id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this object checkout entry')
		),
		'group_uuid' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 36, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_LENGTH' => array(0, 36),
				'LABEL' => 'Group UUID', 'DESCRIPTION' => 'UUID for group checkout is part of.'
		),
		'object_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Object ID', 'DESCRIPTION' => 'The id of the object that was checked out.'
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
				'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who checked out the object.')
		),
		'created_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Created on'), 'DESCRIPTION' => _t('Date/time the checkout entry was created.'),
		),
		'checkout_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Checkout date'), 'DESCRIPTION' => _t('Date/time the item was checked out.'),
		),
		'due_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date due'), 'DESCRIPTION' => _t('Date/time the item is due to be returned.'),
		),
		'return_date' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 15, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date returned'), 'DESCRIPTION' => _t('Date/time the item was returned.'),
		),
		'checkout_notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 90, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_LENGTH' => array(0, 65535),
				'LABEL' => 'Checkout notes', 'DESCRIPTION' => 'Notes made at checkout time.'
		),
		'return_notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 90, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_LENGTH' => array(0, 65535),
				'LABEL' => 'Return notes', 'DESCRIPTION' => 'Notes at return of object.'
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the order is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		),
 	)
);

class ca_object_checkouts extends BaseModel {
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
	protected $TABLE = 'ca_object_checkouts';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'checkout_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('user_id', 'object_id', 'created_on');

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
	protected $ORDER_BY = array();

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	
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
			"object_id"
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = null;
	
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ObjectCheckoutSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ObjectCheckoutSearchResult';
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = false;
	
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
	/**
	 *
	 */
	static public function newCheckoutTransaction($ps_uuid=null) {
		$t_instance = new ca_object_checkouts();
		$ps_uuid ? $t_instance->getTransactionUUID($ps_uuid) : $t_instance->getTransactionUUID();
		
		return $t_instance;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getTransactionUUID() {
		if (!$this->ops_transaction_uuid) { $this->ops_transaction_uuid = caGenerateGUID(); }
		return $this->ops_transaction_uuid;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setTransactionUUID($ps_uuid) {
		return $this->ops_transaction_uuid = $ps_uuid;	
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function checkout($pn_object_id, $pn_user_id, $ps_note=null, $ps_due_date=null) {
		// TODO: does user have read access to object?
		$t_object = new ca_objects($pn_object_id);
		if (!$t_object->getPrimaryKey()) { return null; }
		
		// is object available?
		if ($t_object->getCheckoutStatus() !== __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__) { 
			throw new Exception(_t('Object is already out'));
		}
		
		$vs_uuid = $this->getTransactionUUID();
		$va_checkout_config = ca_object_checkouts::getObjectCheckoutConfigForType($t_object->getTypeCode());
		
		if (!($va_checkout_config['allow_override_of_due_dates'] && $ps_due_date && caDateToUnixTimestamp($ps_due_date))) {
			// calculate default return date
			$ps_due_date = isset($va_checkout_config['default_checkout_date']) ? $va_checkout_config['default_checkout_date'] : null;
		}
		
		$this->setMode(ACCESS_WRITE);
		$this->set(array(
			'group_uuid' => $vs_uuid,
			'object_id' => $pn_object_id,
			'user_id' => $pn_user_id,
			'checkout_notes' => $ps_note,
			'checkout_date' => _t('today'),
			'due_date' => $ps_due_date
		));	
		return $this->insert();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function checkin($pn_object_id, $ps_note=null) {
		// TODO: does user have read access to object?
		$t_object = new ca_objects($pn_object_id);
		if (!$t_object->getPrimaryKey()) { return null; }
		
		// is object out?
		if ($t_object->getCheckoutStatus() === __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__) { 
			throw new Exception(_t('Object is not out'));
		}
		
		$this->setMode(ACCESS_WRITE);
		$this->set(array(
			'return_date' => _t('now'),
			'return_notes' => $ps_note
		));	
		return $this->update();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function reserve($pn_object_id, $pn_user_id, $ps_note) {
		// TODO: does user have read access to object?
		$t_object = new ca_objects($pn_object_id);
		if (!$t_object->getPrimaryKey()) { return null; }
		
		$vs_uuid = $this->getTransactionUUID();
		$va_checkout_config = ca_object_checkouts::getObjectCheckoutConfigForType($t_object->getTypeCode());
		
		$this->setMode(ACCESS_WRITE);
		$this->set(array(
			'group_uuid' => $vs_uuid,
			'object_id' => $pn_object_id,
			'user_id' => $pn_user_id,
			'checkout_notes' => $ps_notes
		));	
		return $this->insert();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getObjectCheckoutConfigForType($pm_type_id) {
		$o_config = Configuration::load(__CA_APP_DIR__.'/conf/object_checkout.conf');
		$t_object = new ca_objects();
		
		$va_type_config = $o_config->getAssoc('checkout_types');
		$vs_type_code = is_numeric($pm_type_id) ? $t_object->getTypeCodeForID($pm_type_id) : $pm_type_id;
		
		if(isset($va_type_config[$vs_type_code])) {
			if (isset($va_type_config[$vs_type_code]['default_checkout_period'])) {
				if ($vn_due_date = strtotime($va_type_config[$vs_type_code]['default_checkout_period'])) {
					$va_type_config[$vs_type_code]['default_checkout_date'] = date('Y-m-d', $vn_due_date);
				}
			}
			
			return $va_type_config[$vs_type_code];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getObjectCheckoutTypes() {
		$o_config = Configuration::load(__CA_APP_DIR__.'/conf/object_checkout.conf');
		$t_object = new ca_objects();
		
		$va_type_config = $o_config->getAssoc('checkout_types');
		
		// TODO: expand hierarchicall?
		return array_keys($va_type_config);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function objectIsOut($pn_object_id) {
		// is it out?
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_object_checkouts
			WHERE
				(checkout_date <= ?)
				AND
				(return_date IS NULL)
				AND
				(object_id = ?)
		", array(time(), $pn_object_id));
		
		if ($qr_res->nextRow()) {
			return $qr_res->getRow();
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function objectHasReservations($pn_object_id) {
		// is it out?
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_object_checkouts
			WHERE
				(created_on <= ?)
				AND
				(checkout_date IS NULL)
				AND
				(return_date IS NULL)
				AND
				(object_id = ?)
		", array(time(), $pn_object_id));
		
		if ($qr_res->numRows() > 0) {
			$va_reservations = array();
			while($qr_res->nextRow()) {
				$va_reservations[] = $qr_res->getRow();
			}
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function isOut() {
		if (!$this->getPrimaryKey()) { return null; }
		
		if ($this->get('checkout_date') && !$this->get('return_date')) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function isReservation() {
		if (!$this->getPrimaryKey()) { return null; }
		
		if (!$this->get('checkout_date') && !$this->get('return_date')) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getCurrentCheckoutInstance($pn_object_id, $po_db=null) {
		$o_db = ($po_db) ? $po_db : new Db();
		
		// is it out?
		$qr_res = $o_db->query("
			SELECT checkout_id
			FROM ca_object_checkouts
			WHERE
				(created_on <= ?)
				AND
				(checkout_date IS NOT NULL)
				AND
				(return_date IS NULL)
				AND
				(object_id = ?)
			ORDER BY
				created_on
		", array(time(), $pn_object_id));
		
		if ($qr_res->numRows() > 0) {
			return new ca_object_checkouts($qr_res->get('checkout_id'));
		}
		
		// is it reserved?
		$qr_res = $o_db->query("
			SELECT checkout_id
			FROM ca_object_checkouts
			WHERE
				(created_on <= ?)
				AND
				(checkout_date IS NULL)
				AND
				(return_date IS NULL)
				AND
				(object_id = ?)
			ORDER BY
				created_on
		", array(time(), $pn_object_id));
		
		if ($qr_res->numRows() > 0) {
			return new ca_object_checkouts($qr_res->get('checkout_id'));
		}
		return null;
	}
	# ------------------------------------------------------
		 
}