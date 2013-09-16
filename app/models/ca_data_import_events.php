<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_import_events.php : table access class for table ca_data_import_events
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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

	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_MODELS_DIR__.'/ca_data_import_event_log.php');
	require_once(__CA_MODELS_DIR__.'/ca_data_import_items.php');

/**
  * Constants for data import items (ca_data_import_items) "success" flag
  */
define("__CA_DATA_IMPORT_ITEM_FAILURE__", 0);
define("__CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__", 1);
define("__CA_DATA_IMPORT_ITEM_SUCCESS__", 2);

BaseModel::$s_ca_models_definitions['ca_data_import_events'] = array(
 	'NAME_SINGULAR' 	=> _t('data import event'),
 	'NAME_PLURAL' 		=> _t('data import events'),
 	'FIELDS' 			=> array(
 		'event_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Event id', 'DESCRIPTION' => 'Identifier for import event'
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('User'), 'DESCRIPTION' => _t('User who performed data import')
		),
		'occurred_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Began on'), 'DESCRIPTION' => _t('Date and time import began')
		),
		'type_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Code indicating type of import (eg. OAI)'),
				'BOUNDS_LENGTH' => array(0,10)
		),
		'description' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Description'), 'DESCRIPTION' => _t('Description of data import event'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'source' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Text indicating source of data imported. For OAI and other web service imports, this will be the URL used to access the service.'),
				'BOUNDS_LENGTH' => array(0,65535)
		)
 	)
);

class ca_data_import_events extends BaseModel {
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
	protected $TABLE = 'ca_data_import_events';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'event_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('description');

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
	protected $ORDER_BY = array('description');

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
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	protected $opo_datamodel;
	protected $opo_data_import_item;
	
	/** 
	 * @property $opn_start_time Microtime recorded on call to ca_data_import_events::beginItem(); used to calculate elapsed time on call to ca_data_import_events::endItem()
	 */
	protected $opn_start_time;
	
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
		$this->opo_datamodel = Datamodel::load();
		
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	/** 
	  *
	  */
	static public function newEvent($pn_user_id, $ps_type, $ps_source, $ps_description) {
		$o_instance = new ca_data_import_events();
		$o_instance->setMode(ACCESS_WRITE);
		$o_instance->set('user_id', $pn_user_id);
		$o_instance->set('type_code', $ps_type);
		$o_instance->set('source', $ps_source);
		$o_instance->set('description', $ps_description);
		$o_instance->insert();
		
		if ($o_instance->numErrors()) {
			return null;
		}
		return $o_instance;
	}
	# ------------------------------------------------------
	/**
	 * Add reference to imported item to the import event
	 *
	 * @param string $ps_source_ref A reference to the location of the item in the source data
	 * @param mixed $pm_table_name_or_num Table name or number for the imported item
	 * @param string $ps_typecode Code indicating import action. Valid values are 'I' for insert (creation of new record in database) or 'U' for update (updated existing record).
	 * @return int Returns id  for newly created item on success, false on error or null if no event is currently loaded.
	 */
	public function beginItem($ps_source_ref, $pm_table_name_or_num, $ps_typecode='I') {
		if (!($vn_event_id = $this->getPrimaryKey())) { return null; } 
		
		$vs_typecode = strtoupper($ps_typecode);
		if (!in_array($vs_typecode, array('I', 'U'))) {
			$vs_typecode = 'I';
		}
		
		$vn_table_num = $this->opo_datamodel->getTableNum($pm_table_name_or_num);
		
		$this->opo_data_import_item = new ca_data_import_items();
		$this->opo_data_import_item->setMode(ACCESS_WRITE);
		$this->opo_data_import_item->set('event_id', $vn_event_id);
		$this->opo_data_import_item->set('source_ref', $ps_source_ref);
		$this->opo_data_import_item->set('table_num', $vn_table_num);
		$this->opo_data_import_item->set('type_code', $vs_typecode);
		
		$this->opo_data_import_item->insert();
		
		if ($this->opo_data_import_item->numErrors()) {
			$this->errors = $this->opo_data_import_item->errors;
			return false;
		} 
		
		$this->opn_start_time = microtime(true);
		return $this->opo_data_import_item->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Add reference to imported item to the import event
	 *
	 * @param mixed $pm_table_name_or_num Table name or number for the imported item
	 * @param int $pn_row_id Primary key value of the imported item
	 * @param string $ps_typecode Code indicating import action. Valid values are 'I' for insert (creation of new record in database) or 'U' for update (updated existing record).
	 * @return bool Returns id for newly created item on success, false on error or null if no event is currently loaded.
	 */
	public function endItem($pn_row_id, $pn_success, $ps_message) {
		if (!($vn_event_id = $this->getPrimaryKey())) { return null; } 
		if (!$this->opo_data_import_item) { 
			throw new Exception("Must call ca_data_import_events::beginItem before ca_data_import_events::endItem");
		}
		
		$this->opo_data_import_item->setMode(ACCESS_WRITE);
		$this->opo_data_import_item->set('event_id', $vn_event_id);
		$this->opo_data_import_item->set('completed_on', _t("now"));
		$this->opo_data_import_item->set('elapsed_time', (microtime(true) - $this->opn_start_time));
	
		$this->opo_data_import_item->set('success', (int)$pn_success);
		$this->opo_data_import_item->set('message', $ps_message);
		$this->opo_data_import_item->set('row_id', $pn_row_id);
		
		$this->opo_data_import_item->update();
		
		if ($this->opo_data_import_item->numErrors()) {
			$this->errors = $this->opo_data_import_item->errors;
			return false;
		} 
		
		
		return $this->opo_data_import_item->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	public function getItemInstance() {
		return $this->opo_data_import_item;
	}
	# ------------------------------------------------------
	/**
	 * Returns a list of imported items associated with the currently loaded import event
	 *
	 * @param array $pa_options Optional array of options. [NOTE: no options are currently implemented]s
	 * @return array List of imported items. Each element of the list is an array with keys corresponding to fields in ca_data_import_items and values for each field.
	 */
	public function getItems(array $pa_options=null) {
		if (!($vn_event_id = $this->getPrimaryKey())) { return null; } 
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_data_import_items
			WHERE
				event_id = ?
		", (int)$vn_event_id);
		
		$va_items = array();
		while($qr_res->nextRow()) {
			$va_items[] = $qr_res->getRow();
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Returns timestamp indicating the last time a given row was modified during the currently loaded import event
	 *
	 * @param mixed $pm_table_name_or_num Table name or number for the imported item
	 * @param int $pn_row_id Primary key value of the imported item
	 * @return int Unix timestamp indicating the last date/time the specified row was modified by the current import event. Will return null if the row has not been modified by the event, or if no event is currently loaded.
	 */
	public function getLastUpdateTimestamp($pm_table_name_or_num, $pn_row_id) {
		if (!($vn_event_id = $this->getPrimaryKey())) { return null; } 
		$vn_table_num = $this->opo_datamodel->getTableNum((int)$pm_table_name_or_num);
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_data_import_items
			WHERE
				table_num = ? AND row_id = ?
			ORDER BY occurred_on DESC
			LIMIT 1
		", (int)$vn_table_num, (int)$pn_row_id);
		
		if($qr_res->nextRow()) {
			return $qr_res->get('occurred_on');
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Log a message against the current import event, and optionally against a specific imported row
	 * 
	 * @param string $ps_type_code A text code indicating the type of log message this is. Supported values are 'ERR' (error), 'WARN' (warning), 'NOTE' (notice), 'INFO' (informational), 'DEBG' (debug)
	 * @param string $ps_message The log message
	 * @param string $ps_source Text indicating the source of the log message
	 * @param int $pn_item_id Optional item_id of ca_data_import_items row that corresponds to the imported item this log entry is concerned with. Leave null for general event-scope log messages.
	 */
	 public function log($ps_type_code, $ps_message, $ps_source=null, $pn_item_id=null) {
	 	if (!($vn_event_id = $this->getPrimaryKey())) { return null; } 
	 	
		$t_log = new ca_data_import_event_log();
	 	$t_log->setMode(ACCESS_WRITE);
	 	$t_log->set('event_id', $vn_event_id);
	 	$t_log->set('item_id', $pn_item_id);
	 	$t_log->set('message', $ps_message);
	 	$t_log->set('source', $ps_source);
	 	$t_log->set('type_code', $ps_type_code);
	 	$t_log->insert();
	 	
	 	if ($t_log->numErrors()) {
			$this->errors = $t_log->errors;
			return false;
		} 
		
		return true;
	 }
	 # ------------------------------------------------------
	/**
	 * Returns a list of long entries associated with the currently loaded import event and, optionally, the specified imported item
	 *
	 * @param int $pn_item_id Optional item_id of ca_data_import_items row that corresponds to the imported item to fetch log entries for. Leave null to obtain all log entries for the event.
	 * @param array $pa_options Optional array of options. [NOTE: no options are currently implemented]s
	 * @return array List of log entries. Each element of the list is an array with keys corresponding to fields in ca_data_import_event_log and values for each field.
	 */
	public function getLogEntries($pn_item_id=null, array $pa_options=null) {
		if (!($vn_event_id = $this->getPrimaryKey())) { return null; } 
		
		$o_db = $this->getDb();
		
		if ($pn_item_id) {
			$qr_res = $o_db->query("
				SELECT *
				FROM ca_data_import_event_log
				WHERE
					(event_id = ?) AND (item_id = ?)
			", (int)$vn_event_id, (int)$pn_item_id);
		} else {
			$qr_res = $o_db->query("
				SELECT *
				FROM ca_data_import_event_log
				WHERE
					(event_id = ?)
			", (int)$vn_event_id);
		}
		
		$va_items = array();
		while($qr_res->nextRow()) {
			$va_items[] = $qr_res->getRow();
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_source
	 * @param string $ps_type_code
	 * @return array Event information
	 */
	static public function getLastEventForSourceAndType($ps_source, $ps_type_code) {
		$o_db = new Db();
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_data_import_events
			WHERE
				(source = ?) AND (type_code = ?)
			ORDER BY occurred_on DESC 
			LIMIT 1
		", (string)$ps_source, (string)$ps_type_code);
		
		if($qr_res->nextRow()) {
			return $qr_res->getRow();
		}
		
		return null;
	}
	# ------------------------------------------------------
}
?>