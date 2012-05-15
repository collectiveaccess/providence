<?php
/** ---------------------------------------------------------------------
 * app/models/ca_bookmark_folders.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_bookmarks.php');


BaseModel::$s_ca_models_definitions['ca_bookmark_folders'] = array(
 	'NAME_SINGULAR' 	=> _t('bookmark folder'),
 	'NAME_PLURAL' 		=> _t('bookmark folders'),
 	'FIELDS' 			=> array(
 		'folder_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this bookmark folder')
		),
		'name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Folder name'), 'DESCRIPTION' => _t('A name to display for the folder.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DISPLAY_FIELD' => array('ca_users.fname', 'ca_users.lname'),
				'DISPLAY_ORDERBY' => array('ca_users.lname'),
				'DEFAULT' => '',
				'LABEL' => 'Owner', 'DESCRIPTION' => 'Identifier for owner of bookmark folder'
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

class ca_bookmark_folders extends BaseModel {
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
	protected $TABLE = 'ca_bookmark_folders';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'folder_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('folder_id');

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
	protected $ORDER_BY = array('folder_id');

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
	#
	# ----------------------------------------
	/**
	 *
	 */
	private function _getFolderID($pn_folder_id=null, $pn_user_id=null) {
		global $AUTH_CURRENT_USER_ID;
		
		if (!$pn_user_id) { $pn_user_id = $AUTH_CURRENT_USER_ID; }
		$vn_folder_id = $this->getPrimaryKey();
		if ($pn_folder_id && ($vn_folder_id != $pn_folder_id)) {
			$t_folder = new ca_bookmark_folders($pn_folder_id);
			if ($t_folder->get('user_id') == $pn_user_id) {
				return $t_folder->getPrimaryKey();
			}
			return false;
		}
		
		if ($this->get('user_id') == $pn_user_id) {
			return $this->getPrimaryKey();
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns all bookmarks within the currently loaded folder, or in the specified folder
	 */
	public function getFolders($pn_user_id) {
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT bf.*, count(*) bookmark_count
			FROM ca_bookmark_folders bf
			LEFT JOIN ca_bookmarks AS b ON b.folder_id = bf.folder_id
			WHERE 
				bf.user_id = ?
			GROUP BY bf.folder_id
		", (int)$pn_user_id);
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors;
			return false;
		}
		
		$va_folders = array();
		
		while($qr_res->nextRow()) {
			$va_folders[$qr_res->get('folder_id')] = $qr_res->getRow();
		}
		
		return $va_folders;
	}
	# ----------------------------------------
	/**
	 * Returns all bookmarks within the currently loaded folder, or in the specified folder
	 */
	public function getBookmarks($pn_folder_id=null, $pn_user_id=null) {
		if (!($vn_folder_id = $this->_getFolderID($pn_folder_id, $pn_user_id))) { return false; }
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT * 
			FROM ca_bookmarks
			WHERE 
				folder_id = ?
		", (int)$vn_folder_id);
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors;
			return false;
		}
		
		
		$va_rows = array();
		$va_bookmarks = array();
		
		// Get raw bookmarks
		while($qr_res->nextRow()) {
			$va_bookmarks[$qr_res->get('bookmark_id')] = $qr_res->getRow();
			$va_rows[$qr_res->get('table_num')][$qr_res->get('row_id')] = $qr_res->get('bookmark_id');
		}
		
		// Do lookups for rows
		foreach($va_rows as $vn_table_num => $va_bookmark_list) {
			//foreach($va_bookmark_list as $vn_bookmark_id => $vn_row_id
			$va_row_ids = array_keys($va_bookmark_list);
			$t_instance = $this->_DATAMODEL->getInstanceByTableNum($vn_table_num, true);
			$va_labels = $t_instance->getPreferredDisplayLabelsForIDs($va_row_ids);
			foreach($va_labels as $vn_row_id => $vs_label) {
				$va_bookmarks[$va_bookmark_list[$vn_row_id]]['label'] = $vs_label;
				# --- pass primary key of table, tablename and controller to link to detail page for creating links in bookmark lists
				# --- doing this here since the table instance is loaded already
				$va_bookmarks[$va_bookmark_list[$vn_row_id]]['primary_key'] = $t_instance->PrimaryKey();
				$va_bookmarks[$va_bookmark_list[$vn_row_id]]['tablename'] = $t_instance->Tablename();
				$vs_controller = "";
				switch($t_instance->Tablename()){
					case "ca_objects":
						$vs_controller = "Object";
					break;
					# -----------------------------
					case "ca_entities":
						$vs_controller = "Entity";
					break;
					# -----------------------------
					case "ca_places":
						$vs_controller = "Place";
					break;
					# -----------------------------
					case "ca_occurrences":
						$vs_controller = "Occurrence";
					break;
					# -----------------------------
					case "ca_collections":
						$vs_controller = "Collection";
					break;
					# -----------------------------
				}
				$va_bookmarks[$va_bookmark_list[$vn_row_id]]['controller'] = $vs_controller;
			
			}			
		}
		
		return $va_bookmarks;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function addBookmark($pn_table_name_or_num, $pn_row_id, $ps_notes=null, $pn_rank=null, $pn_folder_id=null, $pn_user_id=null) {
		if (!($vn_folder_id = $this->_getFolderID($pn_folder_id, $pn_user_id))) { return false; }
		if (!$pn_rank) { $pn_rank = 0; }
		
		if ($pn_table_name_or_num && !($vn_table_num = $this->_getTableNum($pn_table_name_or_num))) { return null; }

		$t_bookmark = new ca_bookmarks();
		
		# --- check if this item already exists in this folder
		$t_bookmark->load(array("folder_id" => $vn_folder_id, "table_num" => $vn_table_num, "row_id" => $pn_row_id));
		if($t_bookmark->getPrimaryKey()){
			return $t_bookmark->getPrimaryKey();
		}
		
		$t_bookmark->setMode(ACCESS_WRITE);
		$t_bookmark->set('folder_id', $vn_folder_id);
		$t_bookmark->set('table_num', $vn_table_num);
		$t_bookmark->set('row_id', $pn_row_id);
		$t_bookmark->set('notes', $ps_notes);
		$t_bookmark->set('rank', $pn_rank);
		
		$t_bookmark->insert();
		
		if ($t_bookmark->numErrors()) {
			$this->errors = $t_bookmark->errors;
			return false;
		}
		return $t_bookmark->getPrimaryKey();
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function removeBookmark($pn_bookmark_id, $pn_folder_id=null, $pn_user_id=null) {
		if (!($vn_folder_id = $this->_getFolderID($pn_folder_id, $pn_user_id))) { return false; }
	
		$t_bookmark = new ca_bookmarks($pn_bookmark_id);
		if ($t_bookmark->get('folder_id') != $vn_folder_id) { 
			return false;
		}
		
		$t_bookmark->setMode(ACCESS_WRITE);
		$t_bookmark->delete();
		
		if ($t_bookmark->numErrors()) {
			$this->errors = $t_bookmark->errors;
			return false;
		}
		return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function removeAllBookmarks($pn_folder_id=null, $pn_user_id=null) {
		if (!($vn_folder_id = $this->_getFolderID($pn_folder_id, $pn_user_id))) { return false; }
		
		$o_db = $this->getDb();
		
		$o_db->query("
			DELETE FROM ca_bookmarks WHERE folder_id = ? 
		", (int)$vn_folder_id);
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors;
			return false;
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Sets order of bookmarks in the currently loaded folder to the order of bookmark_ids as set in $pa_bookmark_ids
	 *
	 * @param array $pa_bookmark_ids A list of bookmark_ids in the folder, in the order in which they should be displayed in the ui
	 * @param array $pa_options An optional array of options. Supported options include:
	 *			NONE
	 * @return array An array of errors. If the array is empty then no errors occurred
	 */
	public function reorderBookmarks($pa_bookmark_ids, $pa_options=null) {
		if (!($vn_folder_id = $this->_getFolderID($pn_folder_id, $pn_user_id))) { return false; }
		
		$va_bookmark_ranks = $this->getBookmarkIDRanks($pa_options);	// get current ranks
		
		$vn_i = 0;
		$o_trans = new Transaction();
		$t_bookmark = new ca_bookmarkss();
		$t_bookmark->setTransaction($o_trans);
		$t_bookmark->setMode(ACCESS_WRITE);
		$va_errors = array();
		
		
		// delete rows not present in $pa_stop_ids
		$va_to_delete = array();
		foreach($va_bookmark_ranks as $vn_stop_id => $va_rank) {
			if (!in_array($vn_stop_id, $pa_stop_ids)) {
				if ($t_bookmark->load(array('folder_id' => $vn_folder_id, 'stop_id' => $vn_stop_id))) {
					$t_bookmark->delete(true);
				}
			}
		}
		
		
		// rewrite ranks
		foreach($pa_stop_ids as $vn_rank => $vn_stop_id) {
			if (isset($va_bookmark_ranks[$vn_stop_id]) && $t_bookmark->load(array('folder_id' => $vn_folder_id, 'stop_id' => $vn_stop_id))) {
				if ($va_bookmark_ranks[$vn_stop_id] != $vn_rank) {
					$t_bookmark->set('rank', $vn_rank);
					$t_bookmark->update();
				
					if ($t_bookmark->numErrors()) {
						$va_errors[$vn_stop_id] = _t('Could not reorder stop %1: %2', $vn_stop_id, join('; ', $t_bookmark->getErrors()));
					}
				}
			} 
		}
		
		if(sizeof($va_errors)) {
			$o_trans->rollback();
		} else {
			$o_trans->commit();
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------
	/**
 	 * Returns a list of bookmarks for the current folder with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			user_id = the user_id of the current user; used to determine which folders the user has access to
	 * @return array Array keyed on row_id with values set to ranks for each bookmark. 
	 */
	public function getBookmarkIDRanks($pa_options=null) {
		if (!($vn_folder_id = $this->_getFolderID($pn_folder_id, $pn_user_id))) { return false; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT b.bookmark_id, b.rank
			FROM ca_bookmarks b
			WHERE
				b.folder_id = ?
			ORDER BY 
				b.rank ASC
		", (int)$vn_folder_id);
		$va_bookmarks = array();
		
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_bookmarks[$qr_res->get('bookmark_id')] = $qr_res->get('rank');
		}
		return $va_bookmarks;
	}
	# ----------------------------------------
	/**
	 * Returns table number for specified table name (or number) and validates that it exists.
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @return int Corresponding table number or null if table does not exist
	 */
	private function _getTableNum($pm_table_name_or_num) {
		$o_dm = $this->getAppDatamodel();
		if (!is_numeric($pm_table_name_or_num)) {
			$vn_table_num = $o_dm->getTableNum($pm_table_name_or_num);
		} else {
			$vn_table_num = $pm_table_name_or_num;
		}
		
		if (!$o_dm->getInstanceByTableNum($vn_table_num, true)) {
			// table name or number is not valid
			return null;
		}
		return $vn_table_num;
	}
	# ------------------------------------------------------
}
?>