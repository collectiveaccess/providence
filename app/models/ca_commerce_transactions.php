<?php
/** ---------------------------------------------------------------------
 * app/models/ca_commerce_transactions.php
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
require_once(__CA_MODELS_DIR__.'/ca_commerce_orders.php');
require_once(__CA_MODELS_DIR__.'/ca_commerce_order_items.php');
require_once(__CA_MODELS_DIR__.'/ca_commerce_communications.php');
require_once(__CA_MODELS_DIR__.'/ca_commerce_fulfillment_events.php');

BaseModel::$s_ca_models_definitions['ca_commerce_transactions'] = array(
 	'NAME_SINGULAR' 	=> _t('commerce transaction'),
 	'NAME_PLURAL' 		=> _t('commerce transactions'),
 	'FIELDS' 			=> array(
 		'transaction_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Transaction id', 'DESCRIPTION' => 'Identifier for transaction'
		),
		'short_description' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Conversation tag'), 'DESCRIPTION' => _t('Short description of subject of conversation'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DISPLAY_FIELD' => array('ca_users.fname', 'ca_users.lname'),
				'DISPLAY_ORDERBY' => array('ca_users.lname'),
				'DEFAULT' => '',
				'LABEL' => _t('Customer'), 'DESCRIPTION' => _t('Customer initiating transaction')
		),
		'set_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Set id', 'DESCRIPTION' => 'Identifier for set attached to message'
		),
		'notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 5,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Notes'), 'DESCRIPTION' => _t('Notes pertaining to the bookmark and/or bookmarked item.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the set is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		),
		'created_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD, 'UPDATE_ON_UPDATE' => true,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Bookmark created on'), 'DESCRIPTION' => _t('Date/time the bookmark was created.'),
		),
 	)
);

class ca_commerce_transactions extends BaseModel {
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
	protected $TABLE = 'ca_commerce_transactions';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'transaction_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('transaction_id');

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
	protected $ORDER_BY = array('transaction_id');

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
	 public function sendMessage($ps_type, $pn_source, $pn_user_id, $ps_subject, $ps_message, $pa_options=null) {
	 	if (!($vn_transaction_id = $this->getPrimaryKey())) { return null; }
	 	
	 	return ca_commerce_communications::sendMessage($vn_transaction_id, $ps_type, $pn_source, $pn_user_id, $ps_subject, $ps_message, $pa_options);
	 }
	 # ----------------------------------------
	/**
	 *
	 */
	 public function sendUserMessage($ps_type, $ps_subject, $ps_message, $pn_user_id, $pa_options=null) {
	 	return $this->sendMessage($ps_type, __CA_COMMERCE_COMMUNICATION_SOURCE_USER__, $pn_user_id, $ps_subject, $ps_message, $pa_options);
	 }
	 # ----------------------------------------
	/**
	 *
	 */
	 public function sendInstitutionMessage($ps_type, $ps_subject, $ps_message, $pn_user_id, $pa_options=null) {
	 	return $this->sendMessage($ps_type, __CA_COMMERCE_COMMUNICATION_SOURCE_INSTITUTION__, $pn_user_id, $ps_subject, $ps_message, $pa_options);
	 }
	 # ----------------------------------------
	 /**
	 * Get all messages associated with the current transaction. Messages are returned sorted by date/time, with the earliest message first.
	 * 
	 * @param array $pa_options
	 *		type = set to "O" to limit to sales orders or "L" to limit to library loans
	 * @return array List of messages
	 */
	 public function getMessages($pa_options=null) {
	 	$o_db = $this->getDb();
	 	if (!($vn_transaction_id = $this->getPrimaryKey())) { return null; }
	 	
	 	$va_params = array((int)$vn_transaction_id);
	 	
	 	$vs_type_sql = '';
	 	if (isset($pa_options['type']) && in_array($pa_options['type'], array('O', 'L'))) {
	 		$va_params[] = $pa_options['type'];
	 		$vs_type_sql = " AND comm.communications_type = ?";
	 	}
	 	
	 	$qr_res = $o_db->query("
	 		SELECT comm.*, tra.short_description, tra.transaction_id, tra.created_on transaction_created_on, tra.set_id
	 		FROM ca_commerce_communications comm
	 		INNER JOIN ca_commerce_transactions AS tra ON tra.transaction_id = comm.transaction_id
	 		WHERE
	 			tra.transaction_id = ? {$vs_type_sql}
	 		ORDER BY
	 			comm.created_on
	 	", $va_params);
	 	
	 	$va_messages = array();
	 	
	 	while($qr_res->nextRow()) {
	 		$va_messages[] = $qr_res->getRow();
	 	}
	 	
	 	return $va_messages;
	 }
	 # ----------------------------------------
	/**
	 * Checks if specified user is owner of transaction, or has privileges to manage user transactions
	 *
	 * @param int $pn_user_id User_id for user to check access for
	 * @param int $pn_transaction_id Optional transaction_id. If omitted the currently loaded transaction is used
	 * @return bool True if user has access
	 *
	 */
	 public function haveAccessToTransaction($pn_user_id, $pn_transaction_id=null) {
	 	$t_user = new ca_users($pn_user_id);
	 	if ($t_user->canDoAction('can_manage_clients')) { return true; }
	 	if ($pn_transaction_id) {
	 		$t_trans = new ca_commerce_transactions($pn_transaction_id);
	 		if (!$t_trans->getPrimaryKey()) { return false; }
	 	} else {
	 		$t_trans = $this;
	 	}
	 	if ($t_trans->getPrimaryKey()) {
	 		if ($t_trans->get('user_id') == $pn_user_id) {
	 			return true;
	 		}
	 	}
	 	return false;
	 }
	 # ----------------------------------------
	/**
	 * Returns ca_users instance for user linked to currently loaded transaction
	 *
	 * @return ca_users ca_users instance or null if no transaction is loaded
	 */
	 public function getTransactionUser() {
	 	if (!($vn_user_id = $this->get('user_id'))) { return null; }
	 	return new ca_users($vn_user_id);
	 }
	 # ----------------------------------------
}
?>