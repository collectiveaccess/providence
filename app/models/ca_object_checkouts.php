<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_checkouts.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once(__CA_APP_DIR__.'/helpers/libraryServicesHelpers.php');

/**
 * Check out statuses
 */
define("__CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__", 0);
define("__CA_OBJECTS_CHECKOUT_STATUS_OUT__", 1);
define("__CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__", 2);
define("__CA_OBJECTS_CHECKOUT_STATUS_RESERVED__", 3);
define("__CA_OBJECTS_CHECKOUT_STATUS_UNAVAILABLE__", 4);

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
		'last_sent_coming_due_email' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 15, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date of last coming due notice'), 'DESCRIPTION' => _t('Date/time a coming due notice was last sent.'),
		),
		'last_sent_overdue_email' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 15, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date of last overdue notice'), 'DESCRIPTION' => _t('Date/time an overdue notice was last sent.'),
		),
		'last_reservation_available_email' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 15, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Date of last reservation available notice'), 'DESCRIPTION' => _t('Date/time a reservation available notice was last sent.'),
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

class ca_object_checkouts extends BundlableLabelableBaseModelWithAttributes {
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
	 * Creates a new ca_object_checkouts instance and initialize with a new group uuid. The returned instance
	 * can be used to do one or more checkouts, checkins and reservations. All checkouts and reservations will be
	 * bound together with the same uuid.
	 *
	 * @param string $ps_uuid A uuid to use; if omitted one will be generated
	 * @return ca_object_checkouts 
	 */
	static public function newCheckoutTransaction($ps_uuid=null) {
		$t_instance = new ca_object_checkouts();
		$ps_uuid ? $t_instance->getTransactionUUID($ps_uuid) : $t_instance->getTransactionUUID();
		
		return $t_instance;
	}
	# ------------------------------------------------------
	/**
	 * Return uuid for current transaction group. Will generate a new uuid if required.
	 *
	 * @return string
	 */
	public function getTransactionUUID() {
		if (!$this->ops_transaction_uuid) { $this->ops_transaction_uuid = caGenerateGUID(); }
		return $this->ops_transaction_uuid;
	}
	# ------------------------------------------------------
	/**
	 * Set uuid for current transaction group directly.
	 *
	 * @param string The uuid
	 * @return string The value that was set
	 */
	public function setTransactionUUID($ps_uuid) {
		return $this->ops_transaction_uuid = $ps_uuid;	
	}
	# ------------------------------------------------------
	/**
	 * Checkout an object for a user. Will throw an exception if the item is currently out or object or user_id are invalid;
	 *
	 * @param int $pn_object_id
	 * @param int $pn_user_id
	 * @param string $ps_note Optional checkin notes
	 * @param string $ps_due_date A valid date time expression for the date item is due to be returned. If omitted the default date as configured will be used.
	 * @param array $pa_options
	 *
	 * @return bool True on success, false on failure
	 */
	public function checkout($pn_object_id, $pn_user_id, $ps_note=null, $ps_due_date=null, $pa_options=null) {
		global $g_ui_locale_id;
		
		$vb_we_set_transaction = false;
		if ($this->inTransaction()) { 
			$o_trans = $this->getTransaction();
		} else {	
			$vb_we_set_transaction = true;
			$this->setTransaction($o_trans = new Transaction());
		}
		
		$o_request = caGetOption('request', $pa_options, null);
		
		$t_object = new ca_objects($pn_object_id);
		$t_object->setTransaction($o_trans);
		if (!$t_object->getPrimaryKey()) { throw new Exception(_t('Object_id is not valid')); }
		if ($o_request && !$t_object->isReadable($o_request)) { throw new Exception(_t('Object_id is not accessible')); }
		$t_user = new ca_users($pn_user_id);
		if (!$t_user->getPrimaryKey()) { throw new Exception(_t('User_id is not valid')); }
		
		// is object available?
		if (!in_array($t_object->getCheckoutStatus(), array(__CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__, __CA_OBJECTS_CHECKOUT_STATUS_RESERVED__))) { 
			throw new Exception(_t('Item is already out'));
		}
		
		$o_db = $o_trans->getDb();
		
		// Does this user already have it?
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_object_checkouts
			WHERE
				user_id = ? AND object_id = ? AND checkout_date IS NOT NULL AND return_date IS NULL
			ORDER BY 
				created_on
		", array($pn_user_id, $pn_object_id));
		
		if ($qr_res->nextRow()) {
			throw new Exception(_t('User already has item'));
		}
		
		// is there a reservation for this user?
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_object_checkouts
			WHERE
				user_id = ? AND object_id = ? AND checkout_date IS NULL AND return_date IS NULL
			ORDER BY 
				created_on
		", array($pn_user_id, $pn_object_id));
		$vb_update = false;
		if ($qr_res->nextRow()) {
			$vs_uuid = $qr_res->get('group_uuid');
			if ($this->load($qr_res->get('checkout_id'))) {
				$vb_update = true;
			}
		} else {
			$vs_uuid = $this->getTransactionUUID();
		}
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
		
		// Do we need to set values?
		if (is_array($va_checkout_config['set_values']) && sizeof($va_checkout_config['set_values'])) {
			$t_object->setMode(ACCESS_WRITE);
			foreach($va_checkout_config['set_values'] as $vs_attr => $va_attr_values_by_event) {
				if (!is_array($va_attr_values_by_event['checkout'])) {
					if ($t_object->hasField($vs_attr)) {
						// Intrinsic
						$t_object->set($vs_attr, $va_attr_values_by_event['checkout']);
					}
				} else {
					$va_attr_values['locale_id'] = $g_ui_locale_id;
					$t_object->replaceAttribute($va_attr_values_by_event['checkout'], $vs_attr);
				}
				$t_object->update();
				if($t_object->numErrors()) {
					$this->errors = $t_object->errors;
					if ($vb_we_set_transaction) { $o_trans->rollback(); }
					return false;
				}
			}
		} 
		$vn_rc = $vb_update ? $this->update() : $this->insert();
		
		if ($vb_we_set_transaction) { 
			$vn_rc ? $o_trans->commit() : $o_trans->rollback();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function checkin($pn_object_id, $ps_note=null, $pa_options=null) {
		global $g_ui_locale_id;
		
		$vb_we_set_transaction = false;
		if ($this->inTransaction()) { 
			$o_trans = $this->getTransaction();
		} else {	
			$vb_we_set_transaction = true;
			$this->setTransaction($o_trans = new Transaction());
		}
		
		$o_request = caGetOption('request', $pa_options, null);
		
		$t_object = new ca_objects($pn_object_id);
		$t_object->setTransaction($o_trans);
		if (!$t_object->getPrimaryKey()) { return null; }
		if ($o_request && !$t_object->isReadable($o_request)) { return null; }
		
		// is object out?
		if ($t_object->getCheckoutStatus() === __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__) { 
			throw new Exception(_t('Item is not out'));
		}
		
		$this->setMode(ACCESS_WRITE);
		$this->set(array(
			'return_date' => _t('now'),
			'return_notes' => $ps_note
		));	
		
		// Do we need to set values?
		if (is_array($va_checkout_config['set_values']) && sizeof($va_checkout_config['set_values'])) {
			$t_object->setMode(ACCESS_WRITE);
			foreach($va_checkout_config['set_values'] as $vs_attr => $va_attr_values_by_event) {
				if (!is_array($va_attr_values_by_event['checkin'])) {
					if ($t_object->hasField($vs_attr)) {
						// Intrinsic
						$t_object->set($vs_attr, $va_attr_values_by_event['checkin']);
					}
				} else {
					$va_attr_values['locale_id'] = $g_ui_locale_id;
					$t_object->replaceAttribute($va_attr_values_by_event['checkin'], $vs_attr);
				}
				$t_object->update();
				if($t_object->numErrors()) {
					$this->errors = $t_object->errors;
					if ($vb_we_set_transaction) { $o_trans->rollback(); }
					return false;
				}
			}
		} 
		$vn_rc = $this->update();
		
		if ($vb_we_set_transaction) { 
			$vn_rc ? $o_trans->commit() : $o_trans->rollback();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function reserve($pn_object_id, $pn_user_id, $ps_note, $pa_options=null) {
		global $g_ui_locale_id;
		
		$vb_we_set_transaction = false;
		if ($this->inTransaction()) { 
			$o_trans = $this->getTransaction();
		} else {	
			$vb_we_set_transaction = true;
			$this->setTransaction($o_trans = new Transaction());
		}
		
		$o_request = caGetOption('request', $pa_options, null);
		
		$t_object = new ca_objects($pn_object_id);
		$t_object->setTransaction($o_trans);
		if (!$t_object->getPrimaryKey()) { return null; }
		if ($o_request && !$t_object->isReadable($o_request)) { return null; }
		
		// is object out?
		if ($t_object->getCheckoutStatus() === __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__) { 
			throw new Exception(_t('Item is not out'));
		}
		$va_reservations = $this->objectHasReservations($pn_object_id);
		// is object already reserved by this user?
		if (is_array($va_reservations)) {
			foreach($va_reservations as $va_reservation) {
				if ($va_reservation['user_id'] == $pn_user_id) {
					throw new Exception(_t('Item is already reserved by this user'));
				}
			}
		}
		
		$vs_uuid = $this->getTransactionUUID();
		$va_checkout_config = ca_object_checkouts::getObjectCheckoutConfigForType($t_object->getTypeCode());
		
		$this->setMode(ACCESS_WRITE);
		$this->set(array(
			'group_uuid' => $vs_uuid,
			'object_id' => $pn_object_id,
			'user_id' => $pn_user_id,
			'checkout_notes' => $ps_notes
		));	
		
		// Do we need to set values?
		if (is_array($va_checkout_config['set_values']) && sizeof($va_checkout_config['set_values'])) {
			$t_object->setMode(ACCESS_WRITE);
			foreach($va_checkout_config['set_values'] as $vs_attr => $va_attr_values_by_event) {
				if (!is_array($va_attr_values_by_event['reserve'])) {
					if ($t_object->hasField($vs_attr)) {
						// Intrinsic
						$t_object->set($vs_attr, $va_attr_values_by_event['reserve']);
					}
				} else {
					$va_attr_values['locale_id'] = $g_ui_locale_id;
					$t_object->replaceAttribute($va_attr_values_by_event['reserve'], $vs_attr);
				}
				$t_object->update();
				if($t_object->numErrors()) {
					$this->errors = $t_object->errors;
					if ($vb_we_set_transaction) { $o_trans->rollback(); }
					return false;
				}
			}
		} 
		$vn_rc = $this->insert();
		
		if ($vb_we_set_transaction) { 
			$vn_rc ? $o_trans->commit() : $o_trans->rollback();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getObjectCheckoutConfigForType($pm_type_id) {
		$o_config = caGetLibraryServicesConfiguration();
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
		$o_config = caGetLibraryServicesConfiguration();
		$va_type_config = $o_config->getAssoc('checkout_types');
		
		return array_keys($va_type_config);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function objectIsOut($pn_object_id, $pa_options=null) {
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
	public function objectHasReservations($pn_object_id, $pa_options=null) {
		// is it out?
		$o_db = $this->getDb();
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
		", array(time(), $pn_object_id));
		
		$va_checkout_ids = $qr_res->getAllFieldValues('checkout_id');
		if (!sizeof($va_checkout_ids)) { return array(); }
		
		if (!($qr_history = caMakeSearchResult('ca_object_checkouts', $va_checkout_ids))) { return array(); }
		
		$va_reservations = array();
		while($qr_history->nextHit()) {
			$va_reservations[] = array(
				'group_uuid' => $qr_history->get('ca_object_checkouts.group_uuid'),
				'checkout_id' => $qr_history->get('ca_object_checkouts.checkout_id'),
				'user_id' => $qr_history->get('ca_object_checkouts.user_id'),
				'user_name' => $qr_history->get('ca_users.fname').' '.$qr_history->get('ca_users.lname').(($vs_email = $qr_history->get('ca_users.email')) ? " ({$vs_email})" : ''),
				'created_on' => $qr_history->get('ca_object_checkouts.created_on', array('timeOmit' => true)),
				'checkout_notes' => $qr_history->get('ca_object_checkouts.checkout_notes')
			);
		}
		return $va_reservations;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function objectHistory($pn_object_id, $pa_options=null) {
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT checkout_id
			FROM ca_object_checkouts
			WHERE
				(object_id = ?) AND (deleted = 0) AND (checkout_date IS NOT NULL)
			ORDER BY 
				created_on
		", array($pn_object_id));
		
		$va_checkout_ids = $qr_res->getAllFieldValues('checkout_id');
		if (!sizeof($va_checkout_ids)) { return array(); }
		
		if (!($qr_history = caMakeSearchResult('ca_object_checkouts', $va_checkout_ids))) { return array(); }
		
		$va_history = array();
		
		while($qr_history->nextHit()) {
			$va_history[] = array(
				'checkout_id' => $qr_history->get('ca_object_checkouts.checkout_id'),
				'group_uuid' => $qr_history->get('ca_object_checkouts.group_uuid'),
				'user_id' => $qr_history->get('ca_object_checkouts.user_id'),
				'user_name' => $qr_history->get('ca_users.fname').' '.$qr_history->get('ca_users.lname').(($vs_email = $qr_history->get('ca_users.email')) ? " ({$vs_email})" : ''),
				'created_on' => $qr_history->get('ca_object_checkouts.created_on', array('timeOmit' => true)),
				'checkout_date' => $qr_history->get('ca_object_checkouts.checkout_date', array('timeOmit' => true)),
				'due_date' => $qr_history->get('ca_object_checkouts.due_date', array('timeOmit' => true)),
				'return_date' => $qr_history->get('ca_object_checkouts.return_date', array('timeOmit' => true)),
				'return_notes' => $qr_history->get('ca_object_checkouts.return_notes'),
				'checkout_notes' => $qr_history->get('ca_object_checkouts.checkout_notes')
			);
		}
		
		return $va_history;
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
	static public function getCurrentCheckoutInstance($pn_object_id, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
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
		
		if ($qr_res->nextRow()) {
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
		
		if ($qr_res->nextRow()) {
			return new ca_object_checkouts($qr_res->get('checkout_id'));
		}
		return null;
	}
	# ------------------------------------------------------
	# Objects by type of event (checkin, checkout, reservation)
	# ------------------------------------------------------
	/**
	 * Return list of object_ids for objects that are checked out
	 *
	 * @param string $ps_datetime 
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return array 
	 */
	static public function getObjectIDsForOutstandingCheckouts($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT DISTINCT object_id
				FROM ca_object_checkouts
				WHERE
					checkout_date BETWEEN ? AND ?
					AND
					return_date IS NULL
					AND
					deleted = 0
			", array($va_dates[0], $va_dates[1]));
		} else {
			$qr_res = $o_db->query("
				SELECT DISTINCT object_id
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date IS NULL
					AND
					deleted = 0
			", array());
		}
		
		return $qr_res->getAllFieldValues('object_id');
	}
	# ------------------------------------------------------
	/**
	 * Return list of object_ids for objects that have been checked in
	 *
	 * @param string $ps_datetime 
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return array 
	 */
	static public function getObjectIDsForCheckins($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT DISTINCT object_id
				FROM ca_object_checkouts
				WHERE
					checkout_date BETWEEN ? AND ?
					AND
					return_date IS NOT NULL
					AND
					deleted = 0
			", array($va_dates[0], $va_dates[1]));
		} else {
			$qr_res = $o_db->query("
				SELECT DISTINCT object_id
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date IS NOT NULL
					AND
					deleted = 0
			", array());
		}
		
		return $qr_res->getAllFieldValues('object_id');
	}
	# ------------------------------------------------------
	/**
	 * Return list of object_ids for objects that have current reservations
	 *
	 * @param string $ps_datetime 
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return array 
	 */
	static public function getObjectIDsForReservations($pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		$qr_res = $o_db->query("
			SELECT DISTINCT object_id
			FROM ca_object_checkouts
			WHERE
				checkout_date IS NULL
				AND
				return_date IS NULL
				AND
				deleted = 0
		", array());
		
		return $qr_res->getAllFieldValues('object_id');
	}
	# ------------------------------------------------------
	# By User
	# ------------------------------------------------------
	/**
	 * Return list of outstanding checkouts for a user. If a time period is specified then only checkouts
	 * made within that period are considered.
	 *
	 * @param int $pn_user_id
	 * @param string $ps_display_template Display template evaluated relative to each ca_object_checkouts records; return in array with key '_display'
	 * @param string $ps_datetime Date/time range expression. [Default is null]
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 *		omitOverdue = Omit overdue checkouts. [Default is false]
	 * @return array 
	 */
	static public function getOutstandingCheckoutsForUser($pn_user_id, $ps_display_template=null, $ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		$pb_omit_overdue = caGetOption('omitOverdue', $pa_options, false);
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT checkout_id
				FROM ca_object_checkouts
				WHERE
					checkout_date BETWEEN ? AND ?
					AND
					return_date IS NULL
					AND
					user_id = ?
					".(($pb_omit_overdue ? " AND (due_date > ".time().") " : ''))."
					AND
					deleted = 0
				ORDER BY
					checkout_date ASC
			", array($va_dates[0], $va_dates[1], $pn_user_id));
		} else {
			$qr_res = $o_db->query("
				SELECT checkout_id
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date IS NULL
					AND
					user_id = ?
					".(($pb_omit_overdue ? " AND (due_date > ".time().") " : ''))."
					AND
					deleted = 0
				ORDER BY
					checkout_date ASC
			", array($pn_user_id));
		}
		
		return ca_object_checkouts::_collectCheckoutData(caMakeSearchResult('ca_object_checkouts', $qr_res->getAllFieldValues('checkout_id')), $ps_display_template);
	}
	# ------------------------------------------------------
	/**
	 * Return list of overdue checkouts for a user. If a time period is specified then only checkouts
	 * made within that period are considered.
	 *
	 * @param int $pn_user_id
	 * @param string $ps_display_template Display template evaluated relative to each ca_object_checkouts records; return in array with key '_display'
	 * @param string $ps_datetime Date/time range expression. [Default is null]
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 *		omitOverdue = Omit overdue checkouts. [Default is false]
	 * @return array 
	 */
	static public function getOverdueCheckoutsForUser($pn_user_id, $ps_display_template=null, $ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		$pb_omit_overdue = caGetOption('omitOverdue', $pa_options, false);
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT checkout_id
				FROM ca_object_checkouts
				WHERE
					checkout_date BETWEEN ? AND ?
					AND
					return_date IS NULL
					AND
					(due_date < ?)
					AND
					user_id = ?
					AND
					deleted = 0
				ORDER BY
					checkout_date ASC
			", array($va_dates[0], $va_dates[1], time(), $pn_user_id));
		} else {
			$qr_res = $o_db->query("
				SELECT checkout_id
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date IS NULL
					AND
					(due_date < ?)
					AND
					user_id = ?
					AND
					deleted = 0
				ORDER BY
					checkout_date ASC
			", array($pn_user_id, time()));
		}
		
		return ca_object_checkouts::_collectCheckoutData(caMakeSearchResult('ca_object_checkouts', $qr_res->getAllFieldValues('checkout_id')), $ps_display_template);
	}
	# ------------------------------------------------------
	/**
	 * Return list of checkins for a user
	 *
	 * @param int $pn_user_id
	 * @param string $ps_display_template Display template evaluated relative to each ca_object_checkouts records; return in array with key '_display'
	 * @param string $ps_datetime Date/time range expression. [Default is null]
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return array 
	 */
	static public function getCheckinsForUser($pn_user_id, $ps_display_template=null, $ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT checkout_id
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date BETWEEN ? AND ?
					AND
					user_id = ?
					AND
					deleted = 0
				ORDER BY
					checkout_date ASC
			", array($va_dates[0], $va_dates[1], $pn_user_id));
		} else {
			$qr_res = $o_db->query("
				SELECT checkout_id
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date IS NOT NULL
					AND
					user_id = ?
					AND
					deleted = 0
				ORDER BY
					checkout_date ASC
			", array($pn_user_id));
		}
		
		return ca_object_checkouts::_collectCheckoutData(caMakeSearchResult('ca_object_checkouts', $qr_res->getAllFieldValues('checkout_id')), $ps_display_template);
	}
	# ------------------------------------------------------
	/**
	 * Return list of outstanding checkouts for a user. If a time period is specified then only checkouts
	 * made within that period are considered.
	 *
	 * @param int $pn_user_id
	 * @param string $ps_display_template Display template evaluated relative to each ca_object_checkouts records; return in array with key '_display'
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return array 
	 */
	static public function getOutstandingReservationsForUser($pn_user_id, $ps_display_template=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		$qr_res = $o_db->query("
			SELECT checkout_id
			FROM ca_object_checkouts
			WHERE
				checkout_date IS NULL
				AND
				return_date IS NULL
				AND
				user_id = ?
				AND
				deleted = 0
			ORDER BY
				checkout_date ASC
		", array($pn_user_id));
		
		return ca_object_checkouts::_collectCheckoutData(caMakeSearchResult('ca_object_checkouts', $qr_res->getAllFieldValues('checkout_id')), $ps_display_template);
	}
	# ------------------------------------------------------
	/**
	 * Roll up by-user data for return. 
	 *
	 * @param SearchResult $qr_res A ca_object_checkouts result set
	 * @param string $ps_display_template Optional display template; will be returned in _display key in returned array
	 *
	 * @return array List of events (checkins, checkouts, etc.)
	 */
	private static function _collectCheckoutData($qr_res, $ps_display_template=null) {
		$va_checkouts = array();
		if ($qr_res) {
			while ($qr_res->nextHit()) {
				$va_tmp = array(
					'checkout_id' => $qr_res->get('ca_object_checkouts.checkout_id'),
					'group_uuid' => $qr_res->get('ca_object_checkouts.group_uuid'),
					'object_id' => $qr_res->get('ca_object_checkouts.object_id'),
					'created_on' => $qr_res->get('ca_object_checkouts.created_on', array('timeOmit' => true)),
					'checkout_date' => $qr_res->get('ca_object_checkouts.checkout_date', array('timeOmit' => true)),
					'due_date' => $qr_res->get('ca_object_checkouts.due_date', array('timeOmit' => true)),
					'return_date' => $qr_res->get('ca_object_checkouts.return_date', array('timeOmit' => true)),
					'checkout_notes' => $qr_res->get('ca_object_checkouts.checkout_notes')
				);
				if ($ps_display_template) {
					$va_tmp['_display'] = $qr_res->getWithTemplate($ps_display_template);
				}
				$va_checkouts[] = $va_tmp;
			}
		}
		return $va_checkouts;
	}
	# ------------------------------------------------------
	# Stats
	# ------------------------------------------------------
	/**
	 * Return number of outstanding checkouts that need to be returned. If a time period is specified 
	 * then only checkouts in that period are considered.
	 *
	 * @param string $ps_datetime Options date/time expression to return statistics for. If omitted current checkouts are considered. If provided, only checkouts out in the specified interval are counted.
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function numOutstandingCheckouts($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT count(*) c
				FROM ca_object_checkouts
				WHERE
					checkout_date BETWEEN ? AND ?
					AND
					return_date IS NULL
					AND
					deleted = 0
			", array($va_dates[0], $va_dates[1]));
		} else {
			$qr_res = $o_db->query("
				SELECT count(*) c
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date IS NULL
					AND
					deleted = 0
			");
		}
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	/**
	 * Return list of outstanding checkouts that need to be returned. If a time period is provided then only
	 * checkouts in that period are considered.
	 *
	 * @param string $ps_datetime Options date/time expression to return statistics for. If omitted current checkouts are considered. If provided, only checkouts out in the specified interval are counted.
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function outstandingCheckoutUserList($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("				
				SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
				FROM ca_object_checkouts c
				INNER JOIN ca_users AS u ON u.user_id = c.user_id
				WHERE
					c.checkout_date BETWEEN ? AND ?
					AND
					c.return_date IS NULL
					AND
					c.deleted = 0
			", array($va_dates[0], $va_dates[1]));
		} else {
			$qr_res = $o_db->query("				
				SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
				FROM ca_object_checkouts c
				INNER JOIN ca_users AS u ON u.user_id = c.user_id
				WHERE
					c.checkout_date IS NOT NULL
					AND
					c.return_date IS NULL
					AND
					c.deleted = 0
			");
		}
		
		$va_list = array();
		while ($qr_res->nextRow()) {
			$va_list[] = $qr_res->getRow();
		}
		return $va_list;
	}
	# ------------------------------------------------------
	/**
	 * Return number of checkins. If a time period is provided then only returns 
	 * made during that period are considered.
	 *
	 * @param string $ps_datetime Options date/time expression to return statistics for. If omitted current checkouts are considered. If provided, only checkouts out in the specified interval are counted.
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function numCheckins($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT count(*) c
				FROM ca_object_checkouts
				WHERE
					return_date BETWEEN ? AND ?
					AND
					deleted = 0
			", array($va_dates[0], $va_dates[1]));
		} else {
			$qr_res = $o_db->query("
				SELECT count(*) c
				FROM ca_object_checkouts
				WHERE
					return_date IS NOT NULL
					AND
					deleted = 0
			");
		}
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	/**
	 * Return list of users returning items. If a time period is specified then only returns made
	 * in that period are considered.
	 *
	 * @param string $ps_datetime Options date/time expression to return statistics for. If omitted current checkouts are considered. If provided, only checkouts out in the specified interval are counted.
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function checkinUserList($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("				
				SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
				FROM ca_object_checkouts c
				INNER JOIN ca_users AS u ON u.user_id = c.user_id
				WHERE
					c.return_date BETWEEN ? AND ?
					AND
					c.deleted = 0
			", array($va_dates[0], $va_dates[1]));
		} else {
			$qr_res = $o_db->query("				
				SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
				FROM ca_object_checkouts c
				INNER JOIN ca_users AS u ON u.user_id = c.user_id
				WHERE
					c.return_date IS NOT NULL
					AND
					c.deleted = 0
			");
		}
		
		$va_list = array();
		while ($qr_res->nextRow()) {
			$va_list[] = $qr_res->getRow();
		}
		return $va_list;
	}
	# ------------------------------------------------------
	/**
	 * Return number of overdue checkouts that need to be returned. If a time period is specified then 
	 * only checkouts made in that period are considered.
	 *
	 * @param string $ps_datetime Options date/time expression to return statistics for. If omitted current checkouts are considered. If provided, only checkouts out in the specified interval are counted.
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function numOverdueCheckouts($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT count(*) c
				FROM ca_object_checkouts
				WHERE
					checkout_date BETWEEN ? AND ?
					AND
					return_date IS NULL
					AND
					due_date < ?
					AND
					deleted = 0
			", array($va_dates[0], $va_dates[1], time()));
		} else {
			$qr_res = $o_db->query("
				SELECT count(*) c
				FROM ca_object_checkouts
				WHERE
					checkout_date IS NOT NULL
					AND
					return_date IS NULL
					AND
					due_date < ?
					AND
					deleted = 0
			", array(time()));
		}
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	
	# ------------------------------------------------------
	/**
	 * Return list of overdue checkouts needing returned. If a time period is specified then only checkouts
	 * made within that period are considered.
	 *
	 * @param string $ps_datetime Options date/time expression to return statistics for. If omitted current checkouts are considered. If provided, only checkouts out in the specified interval are counted.
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function overdueCheckoutUserList($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		if ($ps_datetime && is_array($va_dates = caDateToUnixTimestamps($ps_datetime))) {
			$qr_res = $o_db->query("
				SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
				FROM ca_object_checkouts c
				INNER JOIN ca_users AS u ON u.user_id = c.user_id
				WHERE
					c.checkout_date BETWEEN ? AND ?
					AND
					c.return_date IS NULL
					AND
					c.due_date < ?
					AND
					c.deleted = 0
			", array($va_dates[0], $va_dates[1], time()));
		} else {
			$qr_res = $o_db->query("
				SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
				FROM ca_object_checkouts c
				INNER JOIN ca_users AS u ON u.user_id = c.user_id
				WHERE
					c.checkout_date IS NOT NULL
					AND
					c.return_date IS NULL
					AND
					c.due_date < ?
					AND
					c.deleted = 0
			", array(time()));
		}
		
		$va_list = array();
		while ($qr_res->nextRow()) {
			$va_list[] = $qr_res->getRow();
		}
		return $va_list;
	}
	# ------------------------------------------------------
	/**
	 * Return number of outstanding reservations.
	 *
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function numOutstandingReservations($pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_object_checkouts
			WHERE
				checkout_date IS NULL
				AND
				return_date IS NULL
				AND
				deleted = 0
		");
		
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	/**
	 * Return list of users with outstanding reservations.
	 *
	 * @param array $pa_options Array of options. Options include
	 * 		db = A Db instance to use for database operations. If omitted a new Db instance will be used. [Default=null]
	 * @return int 
	 */
	static public function reservationUserList($pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		
		$qr_res = $o_db->query("
			SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
			FROM ca_object_checkouts c
			INNER JOIN ca_users AS u ON u.user_id = c.user_id
			WHERE
				c.checkout_date IS NULL
				AND
				c.return_date IS NULL
				AND
				c.deleted = 0
		");
		
		$va_list = array();
		while($qr_res->nextRow()) {
			$va_list[] = $qr_res->getRow();
		}
		return $va_list;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getDashboardStatistics($ps_datetime=null, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		if (!$ps_datetime) { $ps_datetime = _t('today'); }
		
		$va_stats = array(
			'numOverdueCheckouts' => ca_object_checkouts::numOverdueCheckouts($ps_datetime),			//	Number of individual copies checked-out and overdue
			'overdueCheckoutUserList' => ca_object_checkouts::overdueCheckoutUserList($ps_datetime),
			'numCheckouts' => ca_object_checkouts::numOutstandingCheckouts($ps_datetime),				//	Number of individual copies checked-out
			'checkoutUserList' => ca_object_checkouts::outstandingCheckoutUserList($ps_datetime),
			'numCheckins' => ca_object_checkouts::numCheckins($ps_datetime),							//	Number of individual copies checked-in
			'checkinUserList' => ca_object_checkouts::checkinUserList($ps_datetime),
			'numReservations' => ca_object_checkouts::numOutstandingReservations(),						//	Number of individual copies currently reserved
			'reservationUserList' => ca_object_checkouts::reservationUserList(),		
		);
			
		//	Number of check-outs per day, week, month, year
		
		return $va_stats;
	} 
	# ------------------------------------------------------
	# Checkout lists
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getItemsDueWithin($ps_interval, $pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		$ps_group_by = caGetOption('groupBy', $pa_options, null); 
		$ps_notification_interval = caGetOption('notificationInterval', $pa_options, null); 
		
		if (!($vn_due_date = strtotime($ps_interval))) {
			throw new Exception(_t('Invalid interval'));
		}
		
		$vn_notification_interval = 0;
		if ($ps_notification_interval) {
			if (($vn_notification_interval = (int)(strtotime($ps_notification_interval) - time())) < 0) { $vn_notification_interval = 0;}
		}
		
		$va_items = array();
		
		$qr_res = $o_db->query("
			SELECT c.*, u.user_id, u.user_name, u.fname, u.lname, u.email
			FROM ca_object_checkouts c
			INNER JOIN ca_users AS u ON u.user_id = c.user_id
			WHERE
				c.checkout_date IS NOT NULL
				AND
				c.return_date IS NULL
				AND
				c.due_date BETWEEN ? AND ?
				AND
				(
					(c.last_sent_coming_due_email + {$vn_notification_interval} < ?)
					OR 
					c.last_sent_coming_due_email IS NULL
				)
				AND
				c.deleted = 0
			ORDER BY c.due_date
		", array(time(), $vn_due_date, time()));
		if ($qr_res->numRows() == 0) { return array(); }
		
		$va_template_values = array();
		if ($vs_template = caGetOption('template', $pa_options, null)) {
			$va_checkout_ids = $qr_res->getAllFieldValues('checkout_id');
			$va_template_values = caProcessTemplateForIDs($vs_template, 'ca_object_checkouts', $va_checkout_ids, array('returnAsArray' => true));
		}
		$qr_res->seek(0);
		$vn_i = 0;
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			
			if ($vs_template) {
				$va_row['_display'] = $va_template_values[$vn_i];
			}
			
			switch($ps_group_by) {
				case 'user_id':
					$va_items[$va_row['user_id']][] = $va_row;
					break;
				default:
					$va_items[] = $va_row;
					break;
			}
			
			$vn_i++;
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getOverdueItems($pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		$ps_group_by = caGetOption('groupBy', $pa_options, null); 
		$ps_notification_interval = caGetOption('notificationInterval', $pa_options, null); 
		
		$vn_notification_interval = 0;
		if ($ps_notification_interval) {
			if (($vn_notification_interval = (int)(strtotime($ps_notification_interval) - time())) < 0) { $vn_notification_interval = 0;}
		}
		
		$va_items = array();
		
		$qr_res = $o_db->query("
			SELECT c.*, u.user_id, u.user_name, u.fname, u.lname, u.email
			FROM ca_object_checkouts c
			INNER JOIN ca_users AS u ON u.user_id = c.user_id
			WHERE
				c.checkout_date IS NOT NULL
				AND
				c.return_date IS NULL
				AND
				c.due_date < ?
				AND
				(
					(c.last_sent_overdue_email + {$vn_notification_interval} < ?)
					OR 
					c.last_sent_overdue_email IS NULL
				)
				AND
				c.deleted = 0
			ORDER BY c.due_date
		", array(time(), time()));
		if ($qr_res->numRows() == 0) { return array(); }
		
		$va_template_values = array();
		if ($vs_template = caGetOption('template', $pa_options, null)) {
			$va_checkout_ids = $qr_res->getAllFieldValues('checkout_id');
			$va_template_values = caProcessTemplateForIDs($vs_template, 'ca_object_checkouts', $va_checkout_ids, array('returnAsArray' => true));
		}
		$qr_res->seek(0);
		$vn_i = 0;
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			
			if ($vs_template) {
				$va_row['_display'] = $va_template_values[$vn_i];
			}
			
			switch($ps_group_by) {
				case 'user_id':
					$va_items[$va_row['user_id']][] = $va_row;
					break;
				default:
					$va_items[] = $va_row;
					break;
			}
			
			$vn_i++;
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getReservedAvailableItems($pa_options=null) {
		if (!($o_db = caGetOption('db', $pa_options, null))) { $o_db = new Db(); }
		$ps_group_by = caGetOption('groupBy', $pa_options, null); 
		$ps_notification_interval = caGetOption('notificationInterval', $pa_options, null); 
		
		$vn_notification_interval = 0;
		if ($ps_notification_interval) {
			if (($vn_notification_interval = (int)(strtotime($ps_notification_interval) - time())) < 0) { $vn_notification_interval = 0;}
		}
		
		$va_items = array();
		
		$qr_res = $o_db->query("
			SELECT c.*, u.user_id, u.user_name, u.fname, u.lname, u.email
			FROM ca_object_checkouts c
			INNER JOIN ca_users AS u ON u.user_id = c.user_id
			WHERE
				c.checkout_date IS NULL
				AND
				c.return_date IS NULL
				AND
				c.due_date IS NULL
				AND
				c.object_id NOT IN (
					SELECT object_id 
					FROM ca_object_checkouts
					WHERE
						checkout_date IS NOT NULL	
						AND
						return_date IS NULL
						AND
						deleted = 0
				)
				AND
				(
					(c.last_reservation_available_email + {$vn_notification_interval} < ?)
					OR 
					c.last_reservation_available_email IS NULL
				)
				AND
				c.deleted = 0
			ORDER BY c.created_on
		", array(time()));
		if ($qr_res->numRows() == 0) { return array(); }
		
		$va_template_values = array();
		if ($vs_template = caGetOption('template', $pa_options, null)) {
			$va_checkout_ids = $qr_res->getAllFieldValues('checkout_id');
			$va_template_values = caProcessTemplateForIDs($vs_template, 'ca_object_checkouts', $va_checkout_ids, array('returnAsArray' => true));
		}
		$qr_res->seek(0);
		$vn_i = 0;
		$va_objects_seen = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			
			// Only return first reservation for an object; others have to wait.
			if (isset($va_objects_seen[$va_row['object_id']]) && $va_objects_seen[$va_row['object_id']]) { continue; }
			
			if ($vs_template) {
				$va_row['_display'] = $va_template_values[$vn_i];
			}
			
			switch($ps_group_by) {
				case 'user_id':
					$va_items[$va_row['user_id']][] = $va_row;
					break;
				default:
					$va_items[] = $va_row;
					break;
			}
			
			$va_objects_seen[$va_row['object_id']] = true;
			$vn_i++;
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
}