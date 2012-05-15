<?php
/** ---------------------------------------------------------------------
 * app/models/ca_watch_list.php : table access class for table ca_watch_list
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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


BaseModel::$s_ca_models_definitions['ca_watch_list'] = array(
 	'NAME_SINGULAR' 	=> _t('watched item'),
 	'NAME_PLURAL' 		=> _t('watched items'),
 	'FIELDS' 			=> array(
 		'watch_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '','LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this watch list entry')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Table watch applies to', 'DESCRIPTION' => 'The table number of the table this watch is applied to.',
				'BOUNDS_VALUE' => array(1,255)
		),
		'row_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Row ID', 'DESCRIPTION' => 'Primary key value of the row in the table specified by table_num that this watch applies to.'
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
				'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who is watching the specified record.')
		)
 	)
);

class ca_watch_list extends BaseModel {
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
	protected $TABLE = 'ca_watch_list';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'watch_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('user_id', 'table_num', 'row_id');

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
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(

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
	public function isItemWatched($pn_row_id, $pn_table_num, $pn_user_id){
		if(!$pn_row_id || !$pn_table_num || !$pn_user_id) { return null; }
		$o_db = $this->getDb();
		$q_is_watched = $o_db->query("SELECT watch_id from ca_watch_list WHERE row_id = ? AND table_num = ? AND user_id = ?", $pn_row_id, $pn_table_num, $pn_user_id);
		if($q_is_watched->numRows() >0){
			return 1;
		}else{
			return 0;
		}
	}
	
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getWatchedItems($pn_user_id, $pn_table_num = null){
		require_once(__CA_LIB_DIR__.'/core/ApplicationChangeLog.php');
		
		if(!$pn_user_id) { return null; }
		
		$t_changelog = new ApplicationChangeLog();
		$o_db = $this->getDb();
		$o_dm = $this->getAppDatamodel();
		
		$sql = "";
		$va_items = array();
		
		if($pn_table_num){
			$sql = " AND table_num = $pn_table_num";
		}
		$q_watched_items = $o_db->query("
			SELECT watch_id, row_id, table_num 
			FROM ca_watch_list 
			WHERE 
				user_id = ? {$sql} 
			ORDER BY watch_id DESC", $pn_user_id);
		if($q_watched_items->numRows() > 0){
			while($q_watched_items->nextRow()){
				$t_item_table = $o_dm->getInstanceByTableNum($q_watched_items->get("table_num"), true);
				if ($t_item_table->hasField('deleted') && ($t_item_table->get('deleted') == 1)) { continue; }
				
				$t_item_table->load($q_watched_items->get("row_id"));
				$va_items[] = array("watch_id" => $q_watched_items->get("watch_id"), "row_id" => $q_watched_items->get("row_id"), "table_num" => $q_watched_items->get("table_num"), "table_name" => $t_item_table->TableName(), "displayName" => $t_item_table->getLabelForDisplay(), "idno" => $t_item_table->get("idno"), "item_type" => $t_item_table->getProperty('NAME_SINGULAR'), "primary_key" => $t_item_table->getPrimaryKey(), "change_log" => $t_changelog->getChangeLogForRowForDisplay($t_item_table));
			}
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
}
?>