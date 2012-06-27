<?php
/** ---------------------------------------------------------------------
 * app/models/ca_commerce_communications.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_commerce_transactions.php');
require_once(__CA_MODELS_DIR__.'/ca_commerce_communications_read_log.php');
require_once(__CA_LIB_DIR__.'/ca/Search/CommerceCommunicationSearch.php');

define("__CA_COMMERCE_COMMUNICATION_SOURCE_USER__", 0);
define("__CA_COMMERCE_COMMUNICATION_SOURCE_INSTITUTION__", 1);
  
BaseModel::$s_ca_models_definitions['ca_commerce_communications'] = array(
 	'NAME_SINGULAR' 	=> _t('communication'),
 	'NAME_PLURAL' 		=> _t('communications'),
 	'FIELDS' 			=> array(
 		'communication_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Tour id', 'DESCRIPTION' => 'Identifier for tour'
		),
		'transaction_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DISPLAY_FIELD' => array('ca_commerce_transactions.short_description'),
				'DISPLAY_ORDERBY' => array('ca_commerce_transactions.created_on'),
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Transaction'), 'DESCRIPTION' => _t('Indicates the transaction to which the communication belongs.')
		),
		'communication_type' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => "120px", 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'O',
				'LABEL' => _t('Communication type'), 'DESCRIPTION' => _t('Indicates whether communication relates is a library loan or sale.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('sales order') => 'O',							
					_t('library loan') => 'L'
				)
		),
		'from_user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('From'), 'DESCRIPTION' => _t('Indicates who sent the message.')
		),
		'source' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Source of communication'), 'DESCRIPTION' => _t('Indicates who created the communication.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('user') => __CA_COMMERCE_COMMUNICATION_SOURCE_USER__,
					_t('institution') => __CA_COMMERCE_COMMUNICATION_SOURCE_INSTITUTION__
				)
		),
		'subject' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Subject'), 'DESCRIPTION' => _t('Subject, or short description, of message.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'message' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 20,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Message'), 'DESCRIPTION' => _t('Text of message.'),
				'BOUNDS_LENGTH' => array(1,65535)
		),
		'set_snapshot' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 20,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Set snapshot'), 'DESCRIPTION' => _t('List of items in associated set at time of message creation.')
		),
		'created_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD, 'UPDATE_ON_UPDATE' => true,
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Message created on'), 'DESCRIPTION' => _t('Date/time the message was created.'),
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the message is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		),
		'read_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Message first read on'), 'DESCRIPTION' => _t('Date/time the message was first read.'),
		)
 	)
);

class ca_commerce_communications extends BaseModel {
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
	protected $TABLE = 'ca_commerce_communications';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'communication_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('communication_id');

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
	protected $ORDER_BY = array('communication_id');

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
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function insert($pa_options=null) {
		$t_trans = new ca_commerce_transactions($this->get('transaction_id'));
		if ($t_trans->getPrimaryKey()) {
			if ($vn_set_id = $t_trans->get('set_id')) {
				$t_set = new ca_sets($vn_set_id);
				if ($t_set->getPrimaryKey()) {
					$va_row_ids = $t_set->getItemRowIDs();
					$this->set('set_snapshot', array(
						'table_num' => $t_set->get('table_num'),
						'set_id' => $vn_set_id,
						'datetime' => time(),
						'items' => $va_row_ids
					));
				}
			}
			return parent::insert($pa_options);
		} else {
			$this->postError(1500, _t('Transaction does not exist'), 'ca_commerce_communications->insert()');
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Gets list of messages conforming to specified options.
	 * @param array $pa_options
	 *		readOnly =
	 *		unreadOnly = 
	 *		user_id = 
	 *		created_on = 
	 *		transaction_id = 
	 *		type = 
	 * @return array
	 */
	 public function getMessages($pn_user_id, $pa_options=null) {
	 	$o_db = $this->getDb();

	 	$pb_read_only = (bool)(isset($pa_options['readOnly']) && $pa_options['readOnly']);
	 	$pb_unread_only = (bool)(isset($pa_options['unreadOnly']) && $pa_options['unreadOnly']);
	 	$pn_restrict_to_transaction_user_id = ((isset($pa_options['user_id']) && (int)$pa_options['user_id']) ? (int)$pa_options['user_id'] : null);
	 	$ps_created_on = ((isset($pa_options['created_on']) && (string)$pa_options['created_on']) ? (string)$pa_options['created_on'] : null);
	 	
	 	$va_sql_wheres = array();
	 	$va_sql_params = array();
	 	if ($pn_restrict_to_transaction_user_id) {
	 		$va_sql_wheres[] = "tra.user_id = ?";
	 		$va_sql_params[] = $pn_restrict_to_transaction_user_id;
	 	}
	 	if (is_array($pa_options) && array_key_exists('transaction_id', $pa_options)) {
	 		$va_sql_wheres[] = "tra.transaction_id = ?";
	 		$va_sql_params[] = (int)$pa_options['transaction_id'];
	 	}
	 	if ($ps_created_on) {
	 		$o_tep = new TimeExpressionParser();
	 		if ($o_tep->parse($ps_created_on) && ($va_dates = $o_tep->getUnixTimestamps())) {	 			
				$va_sql_wheres[] = "(comm.created_on BETWEEN ? AND ?)";
				$va_sql_params[] = $va_dates['start'];
				$va_sql_params[] = $va_dates['end'];
	 		}
	 	}
	 	
	 	if (isset($pa_options['type']) && in_array($pa_options['type'], array('O', 'L'))) {
	 		$va_sql_wheres[] = "(comm.communication_type = ?)";
	 		$va_sql_params[] = (string)$pa_options['type'];
	 	}
	 	
	 	if (isset($pa_options['search']) && strlen($pa_options['search'])) {
	 		$o_search = new CommerceCommunicationSearch();
	 		
	 		if ($qr_hits = $o_search->search($pa_options['search'])) {
	 			$va_ids = array();
	 			while($qr_hits->nextHit()) {
	 				$va_ids[] = $qr_hits->get('communication_id');
	 			}
	 			
	 			if (sizeof($va_ids)) {
	 				$va_sql_wheres[] = "(comm.communication_id IN (?))";
	 				$va_sql_params[] = $va_ids;
	 			} else {
	 				$va_sql_wheres[] = "(comm.communication_id = 0)";
	 			}
	 		}
	 	}
	 	
	 	if ($pb_read_only) {
	 		$va_sql_wheres[] = "(comm.read_on IS NOT NULL) AND (comm.from_user_id <> ".(int)$pn_user_id.")";
	 	}
	 	if ($pb_unread_only) {
	 		$va_sql_wheres[] = "(comm.read_on IS NULL) AND (comm.from_user_id <> ".(int)$pn_user_id.")";
	 	}
	 	
	 	$qr_res = $o_db->query($vs_sql = "
	 		SELECT comm.*, tra.short_description, tra.transaction_id, tra.created_on transaction_created_on, tra.set_id
	 		FROM ca_commerce_communications comm
	 		INNER JOIN ca_commerce_transactions AS tra ON tra.transaction_id = comm.transaction_id
	 		".(sizeof($va_sql_wheres) ? " WHERE ".join(" AND ", $va_sql_wheres) : '')."
	 		ORDER BY
	 			comm.created_on DESC
	 	", $va_sql_params);
	 	//print $vs_sql;
	 	$va_messages = array();
	 	
	 	while($qr_res->nextRow()) {
	 		$va_messages[$qr_res->get('transaction_id')][] = $qr_res->getRow();
	 	}
	 	
	 	return $va_messages;
	 }
	 # ----------------------------------------
	/**
	 * @param int $pn_transaction_id, 
	 * @param string $ps_type = "O" for sales order, "L" for library loans
	 * @param int $pn_source
	 * @param string $ps_subject
	 * @param string $ps_message
	 * @param array $pa_options
	 */
	 static public function sendMessage($pn_transaction_id, $ps_type, $pn_source, $pn_user_id, $ps_subject, $ps_message, $pa_options=null) {
	 	global $g_request;
	 	
	 	$t_comm = new ca_commerce_communications();
	 	
	 	$t_comm->setMode(ACCESS_WRITE);
	 	$t_comm->set('transaction_id', $pn_transaction_id);
	 	$t_comm->set('communications_type', $ps_type);
	 	$t_comm->set('source', $pn_source);
	 	$t_comm->set('subject', $ps_subject);
	 	$t_comm->set('message', $ps_message);
	 	$t_comm->set('from_user_id', $pn_user_id);
	 	$t_comm->insert();
	 	
	 	if (($pn_source == __CA_COMMERCE_COMMUNICATION_SOURCE_INSTITUTION__) && ($g_request)) {
			$t_trans = new ca_commerce_transactions($pn_transaction_id);
			$t_from_user = new ca_users($pn_user_id);
			$t_to_user = new ca_users($t_trans->get('user_id'));
			
			$vs_sender_email = $t_from_user->get('email');
			$vs_to_email = $t_to_user->get('email');
			caSendMessageUsingView($g_request, $vs_to_email, $vs_sender_email, "[".$t_from_user->getAppConfig()->get('app_display_name')."] {$ps_subject}", "commerce_communication.tpl", array('subject' => $ps_subject, 'message' => $ps_message, 'from_user_id' => $pn_user_id, 'sender_name' => $t_from_user->get('fname').' '.$t_from_user->get('lname'), 'sender_email' => $t_from_user->get('email'), 'sent_on' => time(), 'login_url' => $t_from_user->getAppConfig()->get('site_host').'/'.$t_from_user->getAppConfig()->get('ca_url_root')));
		
		}
	 	return $t_comm;
	 }
	 # ----------------------------------------
	/**
	 *
	 */
	 public function logRead($pn_user_id, $pn_communication_id=null) {
	 	$t_comm = null;
	 	if ($pn_communication_id) {
	 		$t_comm = new ca_commerce_communications($pn_communication_id);
	 		if (!$t_comm->getPrimaryKey()) { return false; }
	 	} else {
	 		$t_comm = $this;
	 	}
	 	$pn_communication_id = $t_comm->getPrimaryKey();
	 	
	 	$t_log = new ca_commerce_communications_read_log();
	 	
	 	$t_log->setMode(ACCESS_WRITE);
	 	$t_log->set('communication_id', $pn_communication_id);
	 	$t_log->set('read_on', "now");
	 	$t_log->set('read_by_user_id', $pn_user_id);
	 	$t_log->insert();
	 	
	 	if ($t_log->numErrors()) {
	 		$this->errors = $t_log->errors;
	 		return false;
	 	}
	 	
		$t_trans = new ca_commerce_transactions($t_comm->get('transaction_id'));
	 	if (
	 		(	// Don't mark as read if the user reading sent the message
	 			$t_comm->get('from_user_id') != $pn_user_id
	 		) 
	 		&&
	 		(
	 			(($pn_user_id == $t_trans->get('user_id')) && ($t_comm->get('source') != __CA_COMMERCE_COMMUNICATION_SOURCE_USER__)
	 			|| 
	 			($t_comm->get('source') == __CA_COMMERCE_COMMUNICATION_SOURCE_USER__))
	 		)
	 	) {
			if (!$t_comm->get('read_on')) {
				$t_comm->setMode(ACCESS_WRITE);
				$t_comm->set('read_on', 'now');
				$t_comm->update();
				
				if ($t_comm->numErrors()) {
					$this->errors = $t_comm->errors;
					return false;
				}
			}
		}
	 	
	 	return true;
	 }
	# ----------------------------------------
	/**
	 * Returns true if the communication has been read at least once, false if not.
	 * If the $pn_communication_id is not specified then the currently loaded communication is tested.
	 *
	 * @param int $pn_communication_id A communication_id to test
	 * @return bool True if the communication has been read at least once, false if not. 
	 */
	 public function isRead($pn_communication_id=null) {
	 	$t_comm = null;
	 	if ($pn_communication_id) {
	 		$t_comm = new ca_commerce_communications($pn_communication_id);
	 		if (!$t_comm->getPrimaryKey()) { return false; }
	 	} else {
	 		$t_comm = $this;
	 	}
	 	
	 	return (bool)$t_comm->get('read_on');
	 }
	 # ----------------------------------------
	/**
	 *
	 */
	 public function haveAccessToMessage($pn_user_id, $pn_communication_id=null) {
	 	$t_user = new ca_users($pn_user_id);
	 	if ($t_user->canDoAction('can_manage_clients')) { return true; }
	 	if ($pn_communication_id) {
	 		$t_comm = new ca_commerce_communications($pn_communication_id);
	 		if (!$t_comm->getPrimaryKey()) { return false; }
	 	} else {
	 		$t_comm = $this;
	 	}
	 	$t_trans = new ca_commerce_transactions($t_comm->get('transaction_id'));
	 	if ($t_trans->getPrimaryKey()) {
	 		if ($t_trans->get('user_id') == $pn_user_id) {
	 			return true;
	 		}
	 	}
	 	return false;
	 }
	 # ----------------------------------------
}
?>