<?php
/** ---------------------------------------------------------------------
 * app/models/ca_unsaved_edits.php : table access class for table ca_unsaved_edits
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
BaseModel::$s_ca_models_definitions['ca_unsaved_edits'] = array(
 	'NAME_SINGULAR' 	=> _t('locale'),
 	'NAME_PLURAL' 		=> _t('locales'),
 	'FIELDS' 			=> array(
 		'edit_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this edit')
		),
		'edit_datetime' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Edit date and time'), 'DESCRIPTION' => _t('Edit date and time')
		),
		'user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'LABEL' => _t('User'), 'DESCRIPTION' => _t('User who performed edit')
		),
		'table_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Table', 'DESCRIPTION' => 'Table to which edit was applied',
			'BOUNDS_VALUE' => array(0,255)
		),
		'row_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Row id', 'DESCRIPTION' => 'Identifier of row to which edit was applied'
		),
		'snapshot' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Unsaved form content', 'DESCRIPTION' => 'Data structure with unsaved form data'
		),
 	)
);

class ca_unsaved_edits extends BaseModel {
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
	protected $TABLE = 'ca_unsaved_edits';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'edit_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('user_id', 'edit_datetime');

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
	protected $ORDER_BY = array('edit_datetime');

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
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	

	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct($id=null, ?array $options=null) {
		return parent::__construct($id, $options);
	}
	# ------------------------------------------------------
	/**
	 * Save unsaved edits for form
	 */
	public static function saveForm(RequestHTTP $request, $table, int $row_id, array $data, ?array $options=null) : ?ca_unsaved_edits {
		if(!($table_num = Datamodel::getTableNum($table))) { return null; }
		if(!($user_id = $request->getUserID())) { return null; }
		
		$snapshot = [];
		if(!($t = ca_unsaved_edits::find(['table_num' => $table_num, 'row_id' => $row_id, 'user_id' => $user_id], ['returnAs' => 'firstModelInstance']))) {
			$t = new ca_unsaved_edits();
			$t->set([
				'user_id' => $user_id,
				'table_num' => $table_num,
				'row_id' => $row_id,
				'edit_datetime' => time()
			]);
		} else {
			$snapshot = $t->get('snapshot');
		}
		
		foreach($data as $bundle => $bdata) {
			$snapshot[$bundle] = array_merge($snapshot[$bundle] ?? [], $bdata);
		}
		$t->set('snapshot', $snapshot);
		if($t->isLoaded()) {
			$ret = $t->update();
		} else {
			$ret = $t->insert();
		}
		return $t;
	}
	# ------------------------------------------------------
	/**
	 * Get unsaved edits for form
	 */
	public static function getForm(RequestHTTP $request, $table, int $row_id, ?array $options=null) : ?array {
		if(!($table_num = Datamodel::getTableNum($table))) { return null; }
		if(!($user_id = $request->getUserID())) { return null; }
		
		$snapshot = [];
		if(!($t = ca_unsaved_edits::find($z=['table_num' => $table_num, 'row_id' => $row_id, 'user_id' => $user_id], ['returnAs' => 'firstModelInstance']))) {
			return null;
		} else {
			$snapshot = $t->get('snapshot');
		}
		return $snapshot;
	}
	# ------------------------------------------------------
	/**
	 * Remove unsaved edit buffer for row
	 */
	public static function clearUnsavedEdits(RequestHTTP $request, $table, ?int $row_id, ?array $options=null) : ?bool {
		if(!($table_num = Datamodel::getTableNum($table))) { return null; }
		if(!($user_id = $request->getUserID())) { return null; }
		if(!$row_id) { return null; }
		
		if(!($t = ca_unsaved_edits::find(['table_num' => $table_num, 'row_id' => $row_id, 'user_id' => $user_id], ['returnAs' => 'firstModelInstance']))) {
			return null;
		} 
		
		return $t->delete();
	}
	# ------------------------------------------------------
}
