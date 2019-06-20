<?php
/** ---------------------------------------------------------------------
 * app/models/ca_sets.php : table access class for table ca_sets
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/BundlableLabelableBaseModelWithAttributes.php");
require_once(__CA_LIB_DIR__.'/SetUniqueIdnoTrait.php'); 
require_once(__CA_APP_DIR__.'/models/ca_set_items.php');
require_once(__CA_APP_DIR__.'/models/ca_set_item_labels.php');
require_once(__CA_APP_DIR__.'/models/ca_users.php');
require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');

define('__CA_SET_NO_ACCESS__', 0);
define('__CA_SET_READ_ACCESS__', 1);
define('__CA_SET_EDIT_ACCESS__', 2);

BaseModel::$s_ca_models_definitions['ca_sets'] = array(
 	'NAME_SINGULAR' 	=> _t('set'),
 	'NAME_PLURAL' 		=> _t('sets'),
 	'FIELDS' 			=> array(
 		'set_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this set')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Identifier of parent set; is null if set is root of hierarchy.'
		),
		'hier_set_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Set hierarchy', 'DESCRIPTION' => 'Identifier of set that is root of the set hierarchy.'
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
				'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who created the set.')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DONT_USE_AS_BUNDLE' => true,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_VALUE' => array(1,255),
				'LABEL' => _t('Set content'), 'DESCRIPTION' => _t('Determines what kind of items (objects, entities, places, etc.) are stored by the set.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Objects') => 57,
					_t('Object lots') => 51,
					_t('Entities') => 20,
					_t('Places') => 72,
					_t('Occurrences') => 67,
					_t('Collections') => 13,
					_t('Storage locations') => 89,
					_t('Object representations') => 56,
					_t('Loans') => 133,
					_t('Movements') => 137,
					_t('List items') => 33,
					_t('Tours') => 153,
					_t('Tour stops') => 155
				)
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LIST_CODE' => 'set_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the set determines what sorts of information the set and each item in the set can have associated with them.')
		),
		'set_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Set code'), 'DESCRIPTION' => _t('A unique alphanumeric code for this set. You will need to specify this if you are using this set in a special context (on a web front-end, for example) in which the set must be unambiguously identified.'),
				'BOUNDS_LENGTH' => array(0, 100),
				'UNIQUE_WITHIN' => array()
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if object is accessible to the public or not. ')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the object record.')
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
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the set is deleted or not.'),
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

class ca_sets extends BundlableLabelableBaseModelWithAttributes implements IBundleProvider {
	use SetUniqueIdnoTrait;

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
	protected $TABLE = 'ca_sets';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'set_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('user_id');

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
	protected $ORDER_BY = array('user_id');

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
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_sets';
	protected $HIERARCHY_ID_FLD				=	'hier_set_id';
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
	# Group-based access control
	# ------------------------------------------------------
	protected $USERS_RELATIONSHIP_TABLE = 'ca_sets_x_users';
	protected $USER_GROUPS_RELATIONSHIP_TABLE = 'ca_sets_x_user_groups';
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_set_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'set_types';		// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'set_code';		// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;			// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'SetSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'SetSearchResult';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# cache for haveAccessToSet()
	static $s_have_access_to_set_cache = array();
	
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
		// Filter list of tables set can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_sets']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caFilterTableList(BaseModel::$s_ca_models_definitions['ca_sets']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);
		
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		unset($this->BUNDLES['nonpreferred_labels']); // sets have no nonpreferred labels
		$this->BUNDLES['ca_users'] = array('type' => 'special', 'repeating' => true, 'label' => _t('User access'));
		$this->BUNDLES['ca_user_groups'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Group access'));
		$this->BUNDLES['ca_set_items'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Set items'));
	}
	# ------------------------------------------------------
	/**
	 * Overrides default implementation with code to ensure consistency of set contents
	 */
	public function update($pa_options=null) {
		$this->_setUniqueIdno(['noUpdate' => true]);
		if ($vn_rc = parent::update($pa_options)) {
			// make sure all items have the same type as the set
			$this->getDb()->query("
				UPDATE ca_set_items
				SET type_id = ?
				WHERE
					set_id = ?
			", (int)$this->get('type_id'), (int)$this->getPrimaryKey());
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/** 
	 * Override set() to reject changes to user_id for existing rows
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if ($this->getPrimaryKey()) {
			if (is_array($pa_fields)) {
				if (isset($pa_fields['user_id'])) { unset($pa_fields['user_id']); }
				if (isset($pa_fields['table_num'])) { unset($pa_fields['table_num']); }
			} else {
				if ($pa_fields === 'user_id') { return false; }
				if ($pa_fields === 'table_num') { return false; }
			}
		}
		return parent::set($pa_fields, $pm_value, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Override delete() to scramble the set_code before we soft-delete. This is useful
	 * because the database field has a unique key that really enforces uniqueneness
	 * and we might wanna reuse a code of a set we previously deleted.
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();

		if($pb_delete_related) {
			// quickly delete all labels for all set items in this set
			$this->getDb()->query('DELETE FROM ca_set_item_labels WHERE item_id IN (SELECT item_id FROM ca_set_items WHERE set_id=?)', $this->getPrimaryKey());

			// quickly delete attribute values
			$this->getDb()->query('
				DELETE FROM ca_attribute_values WHERE attribute_id IN
				(SELECT attribute_id FROM ca_attributes WHERE table_num=? and row_id IN (SELECT item_id FROM ca_set_items WHERE set_id = ?))
			', array($this->tableNum(), $this->getPrimaryKey()));

			// quickly delete attributes
			$this->getDb()->query('
				DELETE FROM ca_attributes WHERE table_num=? and row_id IN (SELECT item_id FROM ca_set_items WHERE set_id = ?)
			', array($this->tableNum(), $this->getPrimaryKey()));

			// get list of set item ids
			$qr_items = $this->getDb()->query('SELECT item_id FROM ca_set_items WHERE set_id = ?', array($this->getPrimaryKey()));
			$va_item_ids = $qr_items->getAllFieldValues('item_id');

			// nuke set items
			$this->getDb()->query('DELETE FROM ca_set_items WHERE set_id = ?', array($this->getPrimaryKey()));

			// remove search indexing for deleted set items
			foreach($va_item_ids as $vn_item_id) {
				$this->getSearchIndexer()->commitRowUnIndexing($this->tableNum(), $vn_item_id, array('queueIndexing' => true));
			}
		}

		if($vn_rc = parent::delete($pb_delete_related, array_merge(array('queueIndexing' => true), $pa_options), $pa_fields, $pa_table_list)) {
			if(!caGetOption('hard', $pa_options, false)) { // only applies if we don't hard-delete
				$vb_we_set_transaction = false;
				if (!$this->inTransaction()) {
					$this->setTransaction($o_t = new Transaction($this->getDb()));
					$vb_we_set_transaction = true;
				}
				$this->set('set_code', $this->get('set_code') . '_' . time());
				$this->update(array('force' => true));

				if ($vb_we_set_transaction) { $this->removeTransaction(true); }
			}
		}

		if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }

		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * @param array $pa_options
	 *		duplicate_subitems
	 */
	public function duplicate($pa_options=null) {
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction($o_trans = new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			$o_trans = $this->getTransaction();
		}
		
		if ($t_dupe = parent::duplicate($pa_options)) {
			$vb_duplicate_subitems = caGetOption('duplicate_subitems', $pa_options, false);
		
			if ($vb_duplicate_subitems) { 
				// Try to dupe related ca_set_items rows
				$o_db = $this->getDb();
				
				$qr_res = $o_db->query("
					SELECT *
					FROM ca_set_items
					WHERE set_id = ?
				", (int)$this->getPrimaryKey());
				
				$va_items = array();
				while($qr_res->nextRow()) {
					$va_items[$qr_res->get('item_id')] = $qr_res->getRow();
				}
				
				foreach($va_items as $vn_item_id => $va_item) {
					$t_item = new ca_set_items();
					$t_item->setTransaction($o_trans);
					$t_item->setMode(ACCESS_WRITE);
					$va_item['set_id'] = $t_dupe->getPrimaryKey();
					$t_item->set($va_item);
					$t_item->insert();
					
					if ($t_item->numErrors()) {
						$this->errors = $t_item->errors;
						if ($vb_we_set_transaction) { $this->removeTransaction(false);}
						return false;
					}
				}
			}
		}
		
		
		if ($vb_we_set_transaction) { $this->removeTransaction(true);}
		return $t_dupe;
	}
	# ------------------------------------------------------
	/**
	 * Convert provided row_ids in currently loaded set to their corresponding set item_ids
	 *
	 * @param array $pa_row_ids Array of "row_ids" (eg. object_id's for object sets)
	 * @param array $pa_options Options include:
	 *		returnAsInfoArray = Return an array for each item_id containing keys for set_id and item_id [Default is false]
	 *
	 * @return array A list of item_ids, or arrays if returnAsInfoArray option is set
	 */
	public function rowIDsToItemIDs($pa_row_ids, $pa_options=null) {
		if (!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids)) { return null; }
		
		$pa_row_ids = array_filter($pa_row_ids, function($a) { return (bool)$a; });
		if (!sizeof($pa_row_ids)) { return null; }
		
		$o_db = $this->getDb();
		$qr_items = $o_db->query("
			SELECT item_id, row_id
			FROM ca_set_items
			WHERE
			  row_id IN (?) AND set_id = ?
		", array($pa_row_ids, $vn_set_id));

		if (caGetOption('returnAsInfoArray', $pa_options, false)) {
			$va_acc = array();
			while ($qr_items->nextRow()) {
				$va_acc[$qr_items->get('row_id')][] = array(
					'item_id' => $qr_items->get('item_id'),
					'set_id' => $vn_set_id
				);
			}
			
			return $va_acc;
		}
		
		return $qr_items->getAllFieldValues('item_id');
	}
	# ------------------------------------------------------
	# Set lists
	# ------------------------------------------------------
	/**
	 * Returns list of sets subject to options
	 *
	 * @param array $pa_options Array of options. Supported options are:
	 *			table - if set, list is restricted to sets that can contain the specified item. You can pass a table name or number. If omitted sets containing any content will be returned.
	 *			setType - Restricts returned sets to those of the specified type. You can pass a type_id or list item code for the set type. If omitted sets are returned regardless of type.
	 *			user_id - Restricts returned sets to those accessible by the current user. If omitted then all sets, regardless of access are returned.
	 *			access - Restricts returned sets to those with at least the specified access level for the specified user. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then sets where the user has at least read access will be returned. 
	 *			checkAccess - Restricts returned sets to those with an public access level with the specified values. If omitted sets are returned regardless of public access (ca_sets.access) value. Can be a single value or array if you wish to filter on multiple public access values.
	 *			row_id = if set to an integer only sets containing the specified row are returned
	 *			setIDsOnly = if set to true only set_id values are returned, in a simple array
	 *			omitCounts = 
	 *			all = 
	 *			allUsers =
	 *			publicUsers =
	 *			name = 
	 *          byUser = return sets grouped by user with access. The array will be key'ed by sortable user name. Each entry includes a 'user' array with information about the user and a 'sets' array with the list of sets that user has access to. [Default is false]
	 *
	 * @return array A list of sets keyed by set_id and then locale_id. Keys for the per-locale value array include: set_id, set_code, status, public access, owner user_id, content table_num, set type_id, set name, number of items in the set (item_count), set type name for display and set content type name for display. If setIDsOnly option is set then a simple array of set_id values is returned instead.
	 */
	public function getSets($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$pm_table_name_or_num = isset($pa_options['table']) ? $pa_options['table'] : null;
		$pm_type = isset($pa_options['setType']) ? $pa_options['setType'] : null;
		$pn_user_id = isset($pa_options['user_id']) ? (int)$pa_options['user_id'] : null;
		$pn_access = isset($pa_options['access']) ? $pa_options['access'] : null;
		$pb_set_ids_only = isset($pa_options['setIDsOnly']) ? (bool)$pa_options['setIDsOnly'] : false;
		$pb_omit_counts = isset($pa_options['omitCounts']) ? (bool)$pa_options['omitCounts'] : false;
		$ps_set_name = isset($pa_options['name']) ? $pa_options['name'] : null;
		
		$pb_by_user = caGetOption('byUser', $pa_options, null);
		
		$pn_row_id = (isset($pa_options['row_id']) && ((int)$pa_options['row_id'])) ? (int)$pa_options['row_id'] : null;

		$ps_sort = caGetOption('sort', $pa_options, null);
		$ps_sort_direction = caGetOption('sortDirection', $pa_options, null);
		
		$pa_public_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		if ($pa_public_access && is_numeric($pa_public_access) && !is_array($pa_public_access)) {
			$pa_public_access = array($pa_public_access);
		}
		if (!is_array($pa_public_access)) { $pa_public_access = []; }
		for($vn_i=0; $vn_i < sizeof($pa_public_access); $vn_i++) { $pa_public_access[$vn_i] = intval($pa_public_access[$vn_i]); }
		
		
		if ($pm_table_name_or_num && !($vn_table_num = $this->_getTableNum($pm_table_name_or_num))) { return null; }
		
		$va_extra_joins = array();
		$o_db = $this->getDb();
		
		$va_sql_wheres = array("(cs.deleted = 0)");
		$va_sql_params = array();
		if ($vn_table_num) {
			$va_sql_wheres[] = "(cs.table_num = ?)";
			$va_sql_params[] = (int)$vn_table_num;
		}
		
		if ($pb_set_ids_only) {
			$va_sql_selects = array('cs.set_id');
		} else {
			$va_sql_selects = array(
				'cs.set_id', 'cs.set_code', 'cs.status', 'cs.access', 'cs.user_id', 'cs.table_num', 'cs.type_id',
				'csl.label_id', 'csl.name', 'csl.locale_id', 'l.language', 'l.country', 'u.fname', 'u.lname', 'u.email'
			);
		}
		
		if (isset($pa_options['all']) && $pa_options['all']) {
			$va_sql_wheres[] = "(cs.user_id IN (SELECT user_id FROM ca_users WHERE userclass != 255))";
		} elseif (isset($pa_options['allUsers']) && $pa_options['allUsers']) {
			$va_sql_wheres[] = "(cs.user_id IN (SELECT user_id FROM ca_users WHERE userclass = 0))";
		} elseif (isset($pa_options['publicUsers']) && $pa_options['publicUsers']) {
			$va_sql_wheres[] = "(cs.user_id IN (SELECT user_id FROM ca_users WHERE userclass = 1))";
		} else {
			if ($pn_user_id && !$this->getAppConfig()->get('dont_enforce_access_control_for_ca_sets')) {
				$t_user = Datamodel::getInstanceByTableName('ca_users', true);
				$t_user->load($pn_user_id);
				
				if ($t_user->getPrimaryKey()) {
					$vs_access_sql = ($pn_access > 0) ? " AND (access >= ".intval($pn_access).")" : "";
					if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
						$vs_sql = "(
							(cs.user_id = ".intval($pn_user_id).") OR 
							(cs.set_id IN (
									SELECT set_id 
									FROM ca_sets_x_user_groups 
									WHERE 
										group_id IN (".join(',', array_keys($va_groups)).") {$vs_access_sql}
										AND
										(
											 (sdatetime IS NULL AND edatetime IS NULL)
											 OR 
											 (
												sdatetime <= ".time()." AND edatetime >= ".time()."
											 )
										)
								)
							)
						)";
					} else {
						$vs_sql = "(cs.user_id = {$pn_user_id})";
					}
					
					$vs_sql .= " OR (cs.set_id IN (
											SELECT set_id 
											FROM ca_sets_x_users 
											WHERE 
												user_id = {$pn_user_id} {$vs_access_sql}
												AND
												(
													 (sdatetime IS NULL AND edatetime IS NULL)
													 OR 
													 (
														sdatetime <= ".time()." AND edatetime >= ".time()."
													 )
												)
										)
									)";
					
					
					$va_sql_wheres[] = "({$vs_sql})";
				}
			}
		}
		
		if (!is_null($pa_public_access) && is_array($pa_public_access) && sizeof($pa_public_access)) {
			$va_sql_wheres[] = "(cs.access IN (?))";
			$va_sql_params[] = $pa_public_access;
		}
		
		if (isset($pm_type) && $pm_type) {
			if(is_numeric($pm_type)){
				$va_sql_wheres[] = "(cs.type_id = ?)";
				$va_sql_params[] = (int)$pm_type;
			}else{
				# --- look up code of set type
				$t_list = new ca_lists();
				$vn_type_id = $t_list->getItemIDFromList("set_types", $pm_type);
				if($vn_type_id){
					$va_sql_wheres[] = "(cs.type_id = ?)";
					$va_sql_params[] = (int)$vn_type_id;
				}
			}
		}

		if($va_restrict_to_types = caGetOption('restrict_to_types', $pa_options, false)) {
			$va_restrict_to_type_ids = array();
			foreach($va_restrict_to_types as $vm_type) {
				if(is_numeric($vm_type)){
					$va_restrict_to_type_ids[] = (int)$vm_type;
				} else {
					# --- look up code of set type
					$vn_type_id = caGetListItemID('set_types', $pm_type);
					if($vn_type_id){
						$va_restrict_to_type_ids[] = (int) $vn_type_id;
					}
				}
			}

			if(sizeof($va_restrict_to_type_ids)) {
				$va_sql_wheres[] = "(cs.type_id IN (?))";
				$va_sql_params[] = $va_restrict_to_type_ids;
			}
		}
		
		if ($pn_row_id > 0) {
			$va_sql_wheres[] = "((csi.row_id = ?) AND (csi.table_num = ?))";
			$va_extra_joins[] = "INNER JOIN ca_set_items AS csi ON cs.set_id = csi.set_id";
			$va_sql_selects[] = 'csi.item_id';
			$va_sql_params[] = (int)$pn_row_id;
			$va_sql_params[] = (int)$vn_table_num;
		}
		
		if ($ps_set_name) { 
			$va_sql_wheres[] = "(csl.name = ?)";
			$va_sql_params[] = (string)$ps_set_name;
		}
		
		if (!$pb_set_ids_only && !$pb_omit_counts) {
			// get set item counts
			$qr_table_nums = $o_db->query("
				SELECT DISTINCT cs.table_num 
				FROM ca_sets cs
				INNER JOIN ca_set_items AS csi ON cs.set_id = csi.set_id
				".(sizeof($va_sql_wheres) ? 'WHERE ' : '')."
				".join(' AND ', $va_sql_wheres)."
			", $va_sql_params);
			
			$va_item_counts = array();
			while($qr_table_nums->nextRow()) {
				$t_instance = Datamodel::getInstanceByTableNum($vn_table_num = (int)$qr_table_nums->get('table_num'), true);
				if (!$t_instance) { continue; }
				
				$va_item_sql_params = $va_sql_params;
				$va_item_wheres = $va_sql_wheres;
				$va_item_wheres[] = "(cs.table_num = {$vn_table_num})";
				if ($t_instance->hasField('deleted')) {
					$va_item_wheres[] = "(t.deleted = 0)";
				}
				if (!is_null($pa_public_access) && is_array($pa_public_access) && sizeof($pa_public_access)) {
					$va_item_wheres[] = "(t.access IN (?))";
					$va_item_sql_params[] = $pa_public_access;
				}
				
				$qr_res = $o_db->query("
					SELECT cs.set_id, count(distinct row_id) item_count
					FROM ca_sets cs
					INNER JOIN ca_set_items AS csi ON cs.set_id = csi.set_id
					INNER JOIN ".$t_instance->tableName()." AS t ON t.".$t_instance->primaryKey()." = csi.row_id
					".(sizeof($va_item_wheres) ? 'WHERE ' : '')."
					".join(' AND ', $va_item_wheres)."
					GROUP BY cs.set_id
				", $va_item_sql_params);
				while($qr_res->nextRow()) {
					$va_item_counts[(int)$qr_res->get('set_id')] = (int)$qr_res->get('item_count');
				}
			}
		
			// get sets
			$qr_res = $o_db->query("
				SELECT ".join(', ', $va_sql_selects)."
				FROM ca_sets cs
				LEFT JOIN ca_set_labels AS csl ON cs.set_id = csl.set_id
				LEFT JOIN ca_locales AS l ON csl.locale_id = l.locale_id
				INNER JOIN ca_users AS u ON cs.user_id = u.user_id
				".join("\n", $va_extra_joins)."
				".(sizeof($va_sql_wheres) ? 'WHERE ' : '')."
				".join(' AND ', $va_sql_wheres)."
				ORDER BY csl.name
			", $va_sql_params);
			$va_sets = array();
			$va_type_name_cache = array();
			
			$t_list = new ca_lists();
			while($qr_res->nextRow()) {
				$vn_table_num = $qr_res->get('table_num');
				if (!isset($va_type_name_cache[$vn_table_num]) || !($vs_set_type = $va_type_name_cache[$vn_table_num])) {
					$vs_set_type = $va_type_name_cache[$vn_table_num] = $this->getSetContentTypeName($vn_table_num, array('number' => 'plural'));
				}
				
				$vs_type = $t_list->getItemFromListForDisplayByItemID('set_types', $qr_res->get('type_id'));
				
				$va_sets[$qr_res->get('set_id')][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array('item_count' => intval($va_item_counts[$qr_res->get('set_id')]), 'set_content_type' => $vs_set_type, 'set_type' => $vs_type));
			}
			
			if ($pb_by_user) {
			    $va_sets_by_user = [];
			    $va_sets = caExtractValuesByUserLocale($va_sets);
			    foreach($va_sets as $va_set) {
			        $va_users = $this->getUsers(['row_id' => $va_set['set_id']]);
			        
			        $vs_user_key = strtolower(str_pad(substr($va_set['lname'], 0, 20), 20, ' ', STR_PAD_RIGHT).str_pad(substr($va_set['fname'], 0, 20), 20, ' ', STR_PAD_RIGHT).str_pad($va_set['user_id'], 10, '0', STR_PAD_LEFT));
			        if (!isset($va_sets_by_user[$vs_user_key]['user'])) {
			            $va_sets_by_user[$vs_user_key]['user'] = [
			                'user_id' => $va_set['user_id'], 'user_name' => $va_set['user_name'],
			                'fname' => $va_set['fname'], 'lname' => $va_set['lname'],
			                'email' => $va_set['email']
			            ];
			        }
			        $va_sets_by_user[$vs_user_key]['sets'][] = $va_set;
			        
			        foreach($va_users as $va_user) {
			            $vs_user_key = strtolower(str_pad(substr($va_user['lname'], 0, 20), 20, ' ', STR_PAD_RIGHT).str_pad(substr($va_user['fname'], 0, 20), 20, ' ', STR_PAD_RIGHT).str_pad($va_user['user_id'], 10, '0', STR_PAD_LEFT));
			            $va_sets_by_user[$vs_user_key]['user'] = $va_user;
			            $va_sets_by_user[$vs_user_key]['sets'][] = $va_set;
			        }
			    }
			    ksort($va_sets_by_user);
			    return $va_sets_by_user;
			}
			
			return $va_sets;
		} elseif ($pb_set_ids_only) {
			// get sets
			$qr_res = $o_db->query("
				SELECT ".join(', ', $va_sql_selects)."
				FROM ca_sets cs
				INNER JOIN ca_users AS u ON cs.user_id = u.user_id
				LEFT JOIN ca_set_labels AS csl ON cs.set_id = csl.set_id
				".join("\n", $va_extra_joins)."
				".(sizeof($va_sql_wheres) ? 'WHERE ' : '')."
				".join(' AND ', $va_sql_wheres)."
				ORDER BY csl.name
			", $va_sql_params);
			return $qr_res->getAllFieldValues("set_id");
		} else {
			$qr_res = $o_db->query("
				SELECT ".join(', ', $va_sql_selects)."
				FROM ca_sets cs
				INNER JOIN ca_users AS u ON cs.user_id = u.user_id
				LEFT JOIN ca_set_labels AS csl ON cs.set_id = csl.set_id
				LEFT JOIN ca_locales AS l ON csl.locale_id = l.locale_id
				".join("\n", $va_extra_joins)."
				".(sizeof($va_sql_wheres) ? 'WHERE ' : '')."
				".join(' AND ', $va_sql_wheres)."
				ORDER BY csl.name
			", $va_sql_params);
			$t_list = new ca_lists();
			$va_sets = array();
			while($qr_res->nextRow()) {
				$vn_table_num = $qr_res->get('table_num');
				if (!isset($va_type_name_cache[$vn_table_num]) || !($vs_set_type = $va_type_name_cache[$vn_table_num])) {
					$vs_set_type = $va_type_name_cache[$vn_table_num] = $this->getSetContentTypeName($vn_table_num, array('number' => 'plural'));
				}
				
				$vs_type = $t_list->getItemFromListForDisplayByItemID('set_types', $qr_res->get('type_id'));
				
				$va_sets[$qr_res->get('set_id')][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array('item_count' => intval($va_item_counts[$qr_res->get('set_id')]), 'set_content_type' => $vs_set_type, 'set_type' => $vs_type));
			}

			return $va_sets;
		}
	}
	# ------------------------------------------------------
	/**
	 * Returns list of sets to which the item (as specified by $pm_table_name_or_num and $pn_row_id) can be associated and is not already part of.
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @param int $pn_row_id ID of row in table specified by $pm_table_name_or_num to check for
	 * @param array $pa_options Optional list of options. Supported options are the same as those for ca_sets::getSets() and ca_sets::getSetsForItem()
	 * @return array A list of sets keyed by set_id and then locale_id. Keys for the per-locale value array include: set_id, set_code, status, public access, owner user_id, content table_num, set type_id, set name, number of items in the set (item_count), set type name for display and set content type name for display. This is the same format as returned by ca_sets::getSets().
	 */
	public function getAvailableSetsForItem($pm_table_name_or_num, $pn_row_id, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!($va_full_set_list = $this->getSets(array_merge(array('table' => $pm_table_name_or_num), $pa_options)))) { return null; }
		if (!($va_current_set_list = $this->getSetsForItem($pm_table_name_or_num, $pn_row_id, $pa_options))) { return null; }
		
		$va_available_sets = array();
		foreach($va_full_set_list as $vn_set_id => $va_set_info_by_locale) {
			if (isset($va_current_set_list[$vn_set_id]) && $va_current_set_list[$vn_set_id]) { continue;}
			$va_available_sets[$vn_set_id] = $va_set_info_by_locale;
		}
		
		return $va_available_sets;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of sets in which the specified item is a member
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @param int $pn_row_id ID of row in table specified by $pm_table_name_or_num to check for
	 * @param array $pa_options Optional list of options. Supported options are the same as those for ca_sets::getSets() and ca_sets::getSetsForItem()
	 * @return array A list of sets keyed by set_id and then locale_id. Keys for the per-locale value array include: set_id, set_code, status, public access, owner user_id, content table_num, set type_id, set name, number of items in the set (item_count), set type name for display and set content type name for display. This is the same format as returned by ca_sets::getSets().
	 */
	public function getSetsForItem($pm_table_name_or_num, $pn_row_id, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!$pn_row_id) { return array(); }
		return $this->getSets(array_merge($pa_options, array('table' => $pm_table_name_or_num, 'row_id' => $pn_row_id)));
	}
	# ------------------------------------------------------
	/**
	  * Returns list of set_ids of sets in which the specified item is a member
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @param int $pn_row_id ID of row in table specified by $pm_table_name_or_num to check for
	 * @param array $pa_options Optional list of options. Supported options are the same as those for ca_sets::getSets() and ca_sets::getSetsForItem()
	 * @return array A list of set_ids, or null if parameters are invalid
	 */
	public function getSetIDsForItem($pm_table_name_or_num, $pn_row_id, $pa_options=null) {
		if (is_array($va_sets = $this->getSetsForItem($pm_table_name_or_num, $pn_row_id, $pa_options))) {
			return array_keys($va_sets);
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Checks if a row of a table is in a set.
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @param int $pn_row_id ID of row in table specified by $pm_table_name_or_num to check for
	 * @param mixed $pm_set_code_or_id Set code or set_id of set to check for item
	 * @return bool True if row is in set, false if now. If the table or set are invalid null will be returned.
	 */
	public function isInSet($pm_table_name_or_num, $pn_row_id, $pm_set_code_or_id) {
		if (!($vn_table_num = $this->_getTableNum($pm_table_name_or_num))) { return null; }
		if (!($vn_set_id = $this->_getSetID($pm_set_code_or_id))) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT cs.set_id
			FROM ca_sets cs
			INNER JOIN ca_set_items AS csi ON cs.set_id = csi.set_id
			WHERE
				(cs.deleted = 0) AND (cs.set_id = ?) AND (csi.row_id = ?) AND (cs.table_num = ?)
		", (int)$vn_set_id, (int)$pn_row_id, (int)$vn_table_num);
		
		if ($qr_res->numRows() > 0) {
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Checks if array of row ids of a table are in a set.
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @param int $ps_row_ids array of row ids in table specified by $pm_table_name_or_num to check for
	 * @param mixed $pm_set_code_or_id Set code or set_id of set to check for item
	 * @return array of row_ids found in set. If the table or set are invalid null will be returned.
	 */
	public function areInSet($pm_table_name_or_num, $pa_row_ids, $pm_set_code_or_id) {
		if (!($vn_table_num = $this->_getTableNum($pm_table_name_or_num))) { return null; }
		if (!($vn_set_id = $this->_getSetID($pm_set_code_or_id))) { return null; }
		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids)) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT csi.row_id
			FROM ca_sets cs
			INNER JOIN ca_set_items AS csi ON cs.set_id = csi.set_id
			WHERE
				(cs.deleted = 0) AND (cs.set_id = ?) AND (csi.row_id IN (".join(", ", $pa_row_ids).")) AND (cs.table_num = ?)
		", (int)$vn_set_id, (int)$vn_table_num);
		
		$va_found_row_ids = array();
		if ($qr_res->numRows() > 0) {
			while($qr_res->nextRow()){
				$va_found_row_ids[] = $qr_res->get("row_id");
			}
		}
		
		return $va_found_row_ids;
	}
	# ------------------------------------------------------
	/**
	 * Returns a random set_id from a group defined by specified options. These options are the same as those for ca_sets::getSets()
	 *
	 * @param array $pa_options Array of options. Supported options are:
	 *			table - if set, list is restricted to sets that can contain the specified item. You can pass a table name or number. If omitted sets containing any content will be returned.
	 *			setType - Restricts returned sets to those of the specified type. You can pass a type_id or list item code for the set type. If omitted sets are returned regardless of type.
	 *			user_id - Restricts returned sets to those accessible by the current user. If omitted then all sets, regardless of access are returned.
	 *			access - Restricts returned sets to those with at least the specified access level for the specified user. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then sets where the user has at least read access will be returned. 
	 *			checkAccess - Restricts returned sets to those with an public access level with the specified values. If omitted sets are returned regardless of public access (ca_sets.access) value. Can be a single value or array if you wish to filter on multiple public access values.
	 *			row_id = if set to an integer only sets containing the specified row are returned
	 * @return int A randomly selected set_id value
	 */
	public function getRandomSetID($pa_options=null) {
		$va_set_ids = $this->getSets(array_merge($pa_options, array('setIDsOnly' => true)));
		
		return $va_set_ids[rand(0, sizeof($va_set_ids) - 1)];
	}
	# ------------------------------------------------------
	/**
	 * Determines if user has access to a set at a specified access level.
	 *
	 * @param int $pn_user_id user_id of user to check set access for
	 * @param int $pn_access type of access required. Use __CA_SET_READ_ACCESS__ for read-only access or __CA_SET_EDIT_ACCESS__ for editing (full) access
	 * @param int $pn_set_id The id of the set to check. If omitted then currently loaded set will be checked.
	 * @param array $pa_options No options yet
	 * @return bool True if user has access, false if not
	 */
	public function haveAccessToSet($pn_user_id, $pn_access, $pn_set_id=null, $pa_options=null) {
		if ($this->getAppConfig()->get('dont_enforce_access_control_for_ca_sets')) { return true; }
		
		if ($pn_set_id) { 
			$vn_set_id = $pn_set_id; 
			$t_set = new ca_sets($vn_set_id);
			$vn_set_user_id = $t_set->get('user_id');
		} else {
			$t_set = $this;
			$vn_set_user_id = $t_set->get('user_id');
		}
		if(!$vn_set_id && !($vn_set_id = $t_set->getPrimaryKey())) { 
			return true; // new set
		}
		
		if ($t_set->get('deleted') != 0) { return false; } 		// set is deleted
		
		if (isset(ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access])) {
			return ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access];
		}
		
		if (($vn_set_user_id == $pn_user_id)) {	// owners have all access
			return ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}
		
		if (($t_set->get('access') > 0) && ($pn_access == __CA_SET_READ_ACCESS__)) {	 // public sets are readable by all
			return ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access] = true; 
		}
		
		//
		// If user is admin or has set admin privs allow them access to the set
		//
		$t_user = new ca_users();
		if ($t_user->load($pn_user_id) && ($t_user->canDoAction('is_administrator') || $t_user->canDoAction('can_administrate_sets'))) {
			return ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access] = true;
		}
		
		
		$o_db =  $this->getDb();
		$qr_res = $o_db->query($vs_sql="
			SELECT sxg.set_id 
			FROM ca_sets_x_user_groups sxg 
			INNER JOIN ca_user_groups AS ug ON sxg.group_id = ug.group_id
			INNER JOIN ca_users_x_groups AS uxg ON uxg.group_id = ug.group_id
			WHERE 
				(sxg.access >= ?) AND (uxg.user_id = ?) AND (sxg.set_id = ?)
				AND
				(
					(sxg.sdatetime <= ".time()." AND sxg.edatetime >= ".time().")
					OR
					(sxg.sdatetime IS NULL and sxg.edatetime IS NULL)
				)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_set_id);
		
		if ($qr_res->numRows() > 0) { return ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access] = true; }
		
		$qr_res = $o_db->query("
			SELECT sxu.set_id 
			FROM ca_sets_x_users sxu
			INNER JOIN ca_users AS u ON sxu.user_id = u.user_id
			WHERE 
				(sxu.access >= ?) AND (u.user_id = ?) AND (sxu.set_id = ?)
				AND
				(
					(sxu.sdatetime <= ".time()." AND sxu.edatetime >= ".time().")
					OR
					sxu.sdatetime IS NULL and sxu.edatetime IS NULL
				)
		", (int)$pn_access, (int)$pn_user_id, (int)$vn_set_id);
		
		if ($qr_res->numRows() > 0) { return ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access] = true; }
		
		return ca_sets::$s_have_access_to_set_cache[$vn_set_id.'/'.$pn_user_id.'/'.$pn_access] = false;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @param int $pn_user_id user_id of user to check set access for
	 * @param int $pn_access type of access required. Use __CA_SET_READ_ACCESS__ for read-only access or __CA_SET_EDIT_ACCESS__ for editing (full) access
	 * @param int $pa_set_ids The ids of the sets to check. If omitted then currently loaded set will be checked. Can also be set to a single integer.
	 * @return int
	 */
	public function getAccessExpirationDates($pn_user_id, $pn_access, $pa_set_ids=null, $pa_options=null) {
		if (!is_array($pa_set_ids)) {
			if (!$pa_set_ids) { $pa_set_ids = $this->get('set_id'); }
			if(!$pa_set_ids) { return null; }
			$pa_set_ids = array($pa_set_ids);
		}
		
		foreach($pa_set_ids as $vn_i => $vn_set_id) {
			$pa_set_ids[$vn_i] = (int)$vn_set_id;
		}
		
		$o_db =  $this->getDb();
		$qr_res = $o_db->query("
			SELECT sxg.set_id, sxg.sdatetime, sxg.edatetime, s.user_id
			FROM ca_sets_x_user_groups sxg
			INNER JOIN ca_user_groups AS ug ON sxg.group_id = ug.group_id
			INNER JOIN ca_users_x_groups AS uxg ON uxg.group_id = ug.group_id
			INNER JOIN ca_sets AS s ON s.set_id = sxg.set_id
			WHERE 
				(sxg.access >= ?) AND (uxg.user_id = ?) AND (sxg.set_id IN (?)) AND (s.deleted = 0)
				AND
				(
					sxg.sdatetime <= ".time()." AND sxg.edatetime >= ".time()."
				)
		", (int)$pn_access, (int)$pn_user_id, $pa_set_ids);
		
		$o_tep = new TimeExpressionParser();
		
		$va_expiration_times = array();
		while($qr_res->nextRow()) {
			if ($qr_res->get('user_id') == $pn_user_id) {
				$va_expiration_times[$vn_set_id] = -1;
				continue;
			}
			$vn_set_id = $qr_res->get('set_id');
			$vn_exp = $qr_res->get('edatetime');
			if (!isset($va_expiration_times[$vn_set_id]) || ($va_expiration_times[$vn_set_id] < $vn_exp)) {
				$o_tep->setUnixTimestamps($vn_exp, $vn_exp);
				$vs_text = $o_tep->getText();
				$va_expiration_times[$vn_set_id] = $vs_text;
			}
		}
		
		$qr_res = $o_db->query("
			SELECT sxu.set_id, sxu.sdatetime, sxu.edatetime, s.user_id
			FROM ca_sets_x_users sxu
			INNER JOIN ca_users AS u ON sxu.user_id = u.user_id
			INNER JOIN ca_sets AS s ON s.set_id = sxu.set_id
			WHERE 
				(sxu.access >= ?) AND (u.user_id = ?) AND (sxu.set_id IN (?)) AND (s.deleted = 0)
				AND
				(
					sxu.sdatetime <= ".time()." AND sxu.edatetime >= ".time()."
				)
		", (int)$pn_access, (int)$pn_user_id, $pa_set_ids);
		
		while($qr_res->nextRow()) {
			if ($qr_res->get('user_id') == $pn_user_id) {
				$va_expiration_times[$vn_set_id] = -1;
				continue;
			}
			$vn_set_id = $qr_res->get('set_id');
			$vn_exp = $qr_res->get('edatetime');
			if (!isset($va_expiration_times[$vn_set_id]) || ($va_expiration_times[$vn_set_id] < $vn_exp)) {
				$o_tep->setUnixTimestamps($vn_exp, $vn_exp);
				$vs_text = $o_tep->getText();
				$va_expiration_times[$vn_set_id] = $vs_text;
			}
		}
		
		return $va_expiration_times;
	}
	# ------------------------------------------------------
	# Set maintenance
	# ------------------------------------------------------
	/**
	 * Add item to currently loaded set
	 *
	 * @param int $pn_row_id The row_id to add to the set. Assumed to be a key to a record in the sets's content type's table.
	 * @param array $pa_labels An array of ca_set_item label values to create. The array is keyed on locale_id; each value is an array with keys set to ca_set_item_labels fields with associated values.
	 * @param int $pn_user_id user_id of user adding the item. Used to check if the user has editing access to the set. If omitted no checking of access is done.
	 * @param int $pn_rank position in the set of the newly added item
	 * @return int Returns item_id of newly created set item entry. The item_id is a unique identifier for the row_id in the city at the specified position (rank). It is *not* the same as the row_id.
	 */
	public function addItem($pn_row_id, $pa_labels=null, $pn_user_id=null, $pn_rank=null) {
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if ($pn_user_id && (!$this->haveAccessToSet($pn_user_id, __CA_SET_EDIT_ACCESS__))) { return false; }
		
		$vn_table_num = $this->get('table_num');
		
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$vb_we_set_transaction = true;
		} else {
			$o_trans = $this->getTransaction();
		}
		
		// Verify existance of row before adding to set
		$t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
		if ($o_trans) { $t_instance->setTransaction($o_trans); }
		if (!$t_instance->load($pn_row_id)) {
			$this->postError(750, _t('Item does not exist'), 'ca_sets->addItem()');
			return false;
		}
		
		// Add it to the set
		$t_item = new ca_set_items();
		if ($o_trans) { $t_item->setTransaction($o_trans); }
		$t_item->setMode(ACCESS_WRITE);
		$t_item->set('set_id', $this->getPrimaryKey());
		$t_item->set('table_num', $vn_table_num);
		$t_item->set('row_id', $pn_row_id);
		$t_item->set('type_id', $this->get('type_id'));
		
		$t_item->insert();
		if (!is_null($pn_rank)) {
			$t_item->set('rank', $pn_rank);
			$t_item->update();
		}
		
		if ($t_item->numErrors()) {
			$this->errors = $t_item->errors;
			if ($vb_we_set_transaction) { $o_trans->rollback();}
			return false;
		}
		
		if (is_array($pa_labels) && sizeof($pa_labels)) {
			foreach($pa_labels as $vn_locale_id => $va_label) {
				if (!isset($va_label['caption']) || !trim($va_label['caption'])) { continue; }
				$t_item->addLabel(
					$va_label, $vn_locale_id
				);
				if ($t_item->numErrors()) {
					$this->errors = $t_item->errors;
					
					if ($vb_we_set_transaction) { $o_trans->rollback();}
					return false;
				}
			}
		} else {
			global $g_ui_locale_id;
			if(!$g_ui_locale_id) { $g_ui_locale_id = 1; }

			$t_item->addLabel(array(
				'caption' => '['.caGetBlankLabelText().']',
			), $g_ui_locale_id);
			
			if ($t_item->numErrors()) {
				$t_item->delete();
				$this->errors = $t_item->errors;
				
				if ($vb_we_set_transaction) { $o_trans->rollback();}
				return false;
			}
		}
		
		if ($vb_we_set_transaction) { $o_trans->commit();}
		return (int)$t_item->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Add a list of row_ids to the currently loaded set with minimal overhead.
	 * Note: this method doesn't check access rights for the set
	 *
	 * @param array $pa_row_ids
	 * @param array $pa_options
	 * 		queueIndexing = [Default is true]
	 *		user_id = [Default is null]
	 * @return int Returns item_id of newly created set item entry. The item_id is a unique identifier for the row_id in the city at the specified position (rank). It is *not* the same as the row_id.
	 */
	public function addItems($pa_row_ids, $pa_options = []) {
		$vn_set_id = $this->getPrimaryKey();
		global $g_ui_locale_id;
		if(!$g_ui_locale_id) { $g_ui_locale_id = 1; }
		if (!$vn_set_id) { return false; } 
		if (!is_array($pa_row_ids)) { return false; } 
		if (!sizeof($pa_row_ids)) { return false; } 
		
		$pn_user_id = caGetOption('user_id', $pa_options, null);
		
		$vn_table_num = $this->get('table_num');
		$vn_type_id = $this->get('type_id');
		
		$va_item_values = array();
		$va_row_ids = array_unique($pa_row_ids);
		foreach($va_row_ids as $vn_row_id) {
			$va_item_values[] = "(".(int)$vn_set_id.",".(int)$vn_table_num.",".(int)$vn_row_id.",".(int)$vn_type_id.", '')";
		}
		
		if(sizeof($va_item_values)) {
			// Quickly create set item links
			// Peforming this with a single direct scales much much better than repeatedly populating a model and calling insert()
			$this->getDb()->query("INSERT INTO ca_set_items (set_id, table_num, row_id, type_id, vars) VALUES ".join(",", $va_item_values));
			if ($this->getDb()->numErrors()) {
				$this->errors = $this->getDb()->errors;
				return false;
			}
			
			// Get the item_ids for the newly created links
			$qr_res = $this->getDb()->query("SELECT item_id FROM ca_set_items WHERE set_id = ? AND table_num = ? AND type_id = ? AND row_id IN (?)", array(
				(int)$vn_set_id, (int)$vn_table_num, (int)$vn_type_id, $va_row_ids
			));
			$va_item_ids = $qr_res->getAllFieldValues('item_id');
			
			// Set the ranks of the newly created links
			$this->getDb()->query("UPDATE ca_set_items SET rank = item_id WHERE set_id = ? AND table_num = ? AND type_id = ? AND row_id IN (?)", array(
				$vn_set_id, $vn_table_num, $vn_type_id, $va_row_ids
			));

			// Add empty labels to newly created items
			foreach($va_item_ids as $vn_item_id) {
				$va_label_values[] = "(".(int)$vn_item_id.",".(int)$g_ui_locale_id.",'["._t("BLANK")."]')";
			}
			$this->getDb()->query("INSERT INTO ca_set_item_labels (item_id, locale_id, caption) VALUES ".join(",", $va_label_values));
			if ($this->getDb()->numErrors()) {
				$this->errors = $this->getDb()->errors;
				return false;
			}
			
			// Index the links
			$this->getSearchIndexer()->reindexRows('ca_set_items', $va_item_ids, array('queueIndexing' => (bool) caGetOption('queueIndexing', $pa_options, true)));
		
			// Create change log entries
			if(sizeof($va_item_ids)) {
				$qr_res = $this->getDb()->query("SELECT * FROM ca_set_items WHERE item_id IN (?)", array($va_item_ids));
			
				$t_set_item = new ca_set_items();
				
				$va_set_ids = [];
				while($qr_res->nextRow()) {
					$va_snapshot = $qr_res->getRow();
					$va_set_ids[$qr_res->get('ca_set_items.set_id')] = 1;
					$t_set_item->logChange("I", $pn_user_id, ['row_id' => $qr_res->get('ca_set_items.item_id'), 'snapshot' => $va_snapshot]);
				}
			
				$t_set_item_label = new ca_set_item_labels();
				$qr_res = $this->getDb()->query("SELECT * FROM ca_set_item_labels WHERE item_id IN (?)", array($va_item_ids));
				while($qr_res->nextRow()) {
					$va_snapshot = $qr_res->getRow();
					$t_set_item_label->logChange("I", $pn_user_id, ['row_id' => $qr_res->get('ca_set_item_labels.label_id'), 'snapshot' => $va_snapshot]);
				}
				
				$qr_res = $this->getDb()->query("SELECT * FROM ca_sets WHERE set_id IN (?)", array(array_keys($va_set_ids)));
			
				$t_set = new ca_sets();
				while($qr_res->nextRow()) {
					$va_snapshot = $qr_res->getRow();
					$t_set->logChange("U", $pn_user_id, ['row_id' => $qr_res->get('ca_sets.set_id'), 'snapshot' => $va_snapshot]);
				}
			}
			
		}
		
		return sizeof($va_item_values);
	}
	# ------------------------------------------------------
	/**
	 * Removes all instances of the specified set item from the set as specified by the row_id
	 *
	 * @param int $pn_row_id The row_id of the item to remove
	 * @param int $pn_user_id Option user_id of user to check set access for; if no user_id is specified then no access checking is done
	 * @return bool True on success, false if an error occurred
	 */
	public function removeItem($pn_row_id, $pn_user_id=null) {
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if ($pn_user_id && (!$this->haveAccessToSet($pn_user_id, __CA_SET_EDIT_ACCESS__))) { return false; }
		
		$t_item = new ca_set_items();
		$t_item->setMode(ACCESS_WRITE);
		while ($t_item->load(array('set_id' => $this->getPrimaryKey(), 'row_id' => $pn_row_id))) {
			$t_item->delete(true);
			if ($t_item->numErrors()) {
				$this->errors = $t_item->errors;
				return false;
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Removes the specified set item from the set as specified by the item_id
	 *
	 * @param int $pn_item_id The item_id of the ca_set_items row in the set
	 * @param int $pn_user_id Option user_id of user to check set access for; if no user_id is specified then no access checking is done
	 * @return bool True on success, false if an error occurred
	 */
	public function removeItemByItemID($pn_item_id, $pn_user_id=null) {
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if (!$this->haveAccessToSet($pn_user_id, __CA_SET_EDIT_ACCESS__)) { return false; }
		
		$t_item = new ca_set_items();
		$t_item->setMode(ACCESS_WRITE);
		if ($t_item->load($pn_item_id)) {
			$t_item->delete(true);
			if ($t_item->numErrors()) {
				$this->errors = $t_item->errors;
				return false;
			}
			return true;
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Removes the specified set item as specified by row_id from all sets in which it is a member.
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @param int $pn_row_id The row_id of the item to remove from sets
	 * @param int $pn_user_id Option user_id of user to check set access for; if no user_id is specified then no access checking is done
	 * @return bool True on success, false if item was not removed from one or more sets due to error or access restrictions
	 */
	public function removeItemFromAllSets($pm_table_name_or_num, $pn_row_id, $pn_user_id=null) {
		if (!($vn_table_num = $this->_getTableNum($pm_table_name_or_num))) { return null; }
		
		$t_item = new ca_set_items();
		$t_item->setMode(ACCESS_WRITE);
		
		$va_set_ids = $this->getSetIDsForItem($vn_table_num, $pn_row_id);
		$vb_skipped = false;
		foreach($va_set_ids as $vn_set_id) {
			if ($pn_user_id && (!$this->haveAccessToSet($pn_user_id, __CA_SET_EDIT_ACCESS__, $vn_set_id))) { $vb_skipped = true; continue; }
			
			if ($t_item->load(array('set_id' => $vn_set_id, 'row_id' => $pn_row_id))) {
				$t_item->delete(true);
				if ($t_item->numErrors()) {
					$this->errors = $t_item->errors;
					return false;
				}
			}	
		}
		return !$vb_skipped;
	}
	# ------------------------------------------------------
	/**
	 * Returns a list of item_ids for the current set with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			user_id = the user_id of the current user; used to determine which sets the user has access to
	 * @return array Array keyed on item_id with values set to ranks for each item
	 */
	public function getItemRanks($pa_options=null) {
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if (!$this->haveAccessToSet($pa_options['user_id'], __CA_SET_READ_ACCESS__)) { return false; }
		
		$va_items = caExtractValuesByUserLocale($this->getItems($pa_options));
		$va_ranks = array();
		foreach($va_items as $vn_item_id => $va_item) {
			$va_ranks[$vn_item_id] = $va_item['rank'];
		}
		return $va_ranks;
	}
	# ------------------------------------------------------
	/**
 	 * Returns a list of row_ids for the current set with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			user_id = the user_id of the current user; used to determine which sets the user has access to
	 *			treatRowIDsAsRIDs = use combination row_id/item_id indices in returned array instead of solely row_ids. Since a set can potentially contain multiple instances of the same row_id, only "rIDs" – a combination of the row_id and the set item_id (row_id + "_" + item_id) – are guaranteed to be unique. [Default=false]
	 * @return array Array keyed on row_id with values set to ranks for each item. If the set contains duplicate row_ids then the list will only have the largest rank. If you have sets with duplicate rows use getItemRanks() instead
	 */
	public function getRowIDRanks($pa_options=null) {
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if (!$this->haveAccessToSet($pa_options['user_id'], __CA_SET_READ_ACCESS__)) { return false; }
		
		$vb_treat_row_ids_as_rids = caGetOption('treatRowIDsAsRIDs', $pa_options, false);
		
		$va_items = caExtractValuesByUserLocale($this->getItems($pa_options));
		$va_ranks = array();
		foreach($va_items as $vn_item_id => $va_item) {
			$va_ranks[$vb_treat_row_ids_as_rids ? $va_item['row_id']."_{$vn_item_id}" : $va_item['row_id']] = $va_item['rank'];
		}
		return $va_ranks;
	}
	# ------------------------------------------------------
	/**
	 * Returns a list of row_ids for a set with ranks for each, in rank order. This is a faster alternative to getRowIDRanks() that
	 * queries the database directly and does no access checking. It is intended for use with lower level functions that need to sort
	 * potentially large sets quickly.
	 *
	 * @param int $pn_set_id
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			treatRowIDsAsRIDs = use combination row_id/item_id indices in returned array instead of solely row_ids. Since a set can potentially contain multiple instances of the same row_id, only "rIDs" – a combination of the row_id and the set item_id (row_id + "_" + item_id) – are guaranteed to be unique. [Default=false]
	 * @return array ray keyed on row_id with values set to ranks for each item. If the set contains duplicate row_ids then the list will only have the largest rank.
	 */
	static public function getRowIDRanksForSet($pn_set_id, $pa_options=null) {
		$vb_treat_row_ids_as_rids = caGetOption('treatRowIDsAsRIDs', $pa_options, false);
		
		$o_db = new Db();
		$qr_res = $o_db->query("SELECT row_id, item_id, rank FROM ca_set_items WHERE set_id = ? AND deleted = 0 ORDER BY rank", [$pn_set_id]);
	
		$va_ranks = [];
		
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_ranks[$vb_treat_row_ids_as_rids ? $va_row['row_id']."_".$va_row['item_id'] : $va_row['row_id']] = $va_row['rank'];
		}
		return $va_ranks;
	}
	# ------------------------------------------------------
	/**
	 * Sets order of items in the currently loaded set to the order of row_ids as set in $pa_row_ids
	 *
	 * @param array $pa_row_ids A list of row_ids in the set, in the order in which they should be displayed in the set
	 * @param array $pa_options An optional array of options. Supported options include:
	 *			user_id = the user_id of the current user; used to determine which sets the user has access to
	 *			treatRowIDsAsRIDs = assume combination row_id/item_id indices in $pa_row_ids array instead of solely row_ids. Since a set can potentially contain multiple instances of the same row_id, only "rIDs" – a combination of the row_id and the set item_id (row_id + "_" + item_id) – are guaranteed to be unique. [Default=false]
	 * 			deleteExcludedItems = should the set items not passed in pa_row_ids be deleted?  default is false
	 * @return array An array of errors. If the array is empty then no errors occurred
	 */
	public function reorderItems($pa_row_ids, $pa_options=null) {
		if (!($vn_set_id = $this->getPrimaryKey())) {	
			return null;
		}
		
		$pn_user_id = isset($pa_options['user_id']) ? (int)$pa_options['user_id'] : null; 
		$vb_treat_row_ids_as_rids = caGetOption('treatRowIDsAsRIDs', $pa_options, false); 
		$vb_delete_excluded_items = caGetOption('deleteExcludedItems', $pa_options, false);
		
		// does user have edit access to set?
		if ($pn_user_id && !$this->haveAccessToSet($pn_user_id, __CA_SET_EDIT_ACCESS__)) {
			return false;
		}
	
		$va_row_ranks = $this->getRowIDRanks($pa_options);	// get current ranks
		$vn_i = 0;
		
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$vb_we_set_transaction = true;
		} else {
			$o_trans = $this->getTransaction();
		}
		
		$t_set_item = new ca_set_items();
		$t_set_item->setTransaction($o_trans);
		$t_set_item->setMode(ACCESS_WRITE);
		$va_errors = array();
		
		
		// delete rows not present in $pa_row_ids
		$va_excluded_item_ids = array();
		foreach($va_row_ranks as $vn_row_id => $va_rank) {
			if (!in_array($vn_row_id, $pa_row_ids)) {
				
				if ($vb_treat_row_ids_as_rids) {
					$va_tmp = explode("_", $vn_row_id);
					if ($t_set_item->load(array('set_id' => $vn_set_id, 'row_id' => $va_tmp[0], 'item_id' => $va_tmp[1]))) {
						$va_excluded_item_ids[$t_set_item->get("rank")] = $t_set_item->get("item_id");
						if($vb_delete_excluded_items){
							$t_set_item->delete(true);
						}
					}
				} else {
					if ($t_set_item->load(array('set_id' => $vn_set_id, 'row_id' => $vn_row_id))) {
						$va_excluded_item_ids[$t_set_item->get("rank")] = $t_set_item->get("item_id");
						if($vb_delete_excluded_items){
							$t_set_item->delete(true);
						}
					}
				}
				if(($vn_k = array_search($pa_row_ids, $pa_row_ids)) !== false) {
					unset($pa_row_ids[$vn_k]);
				}
				unset($va_row_ranks[$vn_row_id]);
			}
		}
		
	
		// rewrite ranks
		$va_existing_ranks = array_values($va_row_ranks);
		$vn_rank_acc = end(array_values($va_row_ranks));
		
		$va_rank_updates = array();
		foreach($pa_row_ids as $vn_rank => $vn_row_id) {
			if (isset($va_existing_ranks[$vn_rank])) {
				$vn_rank_inc = $va_existing_ranks[$vn_rank];
			} else {
				$vn_rank_acc++;
				$vn_rank_inc = $vn_rank_acc;
			}
			
			if ($vb_treat_row_ids_as_rids) { $va_tmp = explode("_", $vn_row_id); }
			if (isset($va_row_ranks[$vn_row_id]) && $t_set_item->load($vb_treat_row_ids_as_rids ? array('set_id' => $vn_set_id, 'row_id' => $va_tmp[0], 'item_id' => $va_tmp[1]) : array('set_id' => $vn_set_id, 'row_id' => $vn_row_id))) {
				if ($va_row_ranks[$vn_row_id] != $vn_rank_inc) {
					$t_set_item->set('rank', $vn_rank_inc);
					$t_set_item->update();
				
					if ($t_set_item->numErrors()) {
						$va_errors[$vn_row_id] = _t('Could not reorder item %1: %2', $vn_row_id, join('; ', $t_set_item->getErrors()));
					}
				}
			} else {
				// add item to set
				$this->addItem($vb_treat_row_ids_as_rids ? $va_tmp[0] : $vn_row_id, null, $pn_user_id, $vn_rank_inc);
			}
		}
		
		$va_updated_item_ids = [];
		foreach($va_rank_updates as $vn_row_id => $vn_new_rank) {
			if($vb_treat_row_ids_as_rids) {
				$va_tmp = explode("_", $vn_row_id);
				$this->getDb()->query("UPDATE ca_set_items SET rank = ? WHERE set_id = ? AND row_id = ? AND item_id = ?", array($vn_new_rank, $vn_set_id, $va_tmp[0], $va_tmp[1]));
				$va_updated_item_ids[$va_tmp[1]] = 1;
			} else {
				$this->getDb()->query("UPDATE ca_set_items SET rank = ? WHERE set_id = ? AND row_id = ?", array($vn_set_id, $vn_new_rank));
			}
		}
		
		if(sizeof($va_updated_item_ids) > 0) {
			$qr_res = $this->getDb()->query("SELECT * FROM ca_set_items WHERE item_id IN (?)", array(array_keys($va_updated_item_ids)));
			
			$t_set_item = new ca_set_items();
			
			$va_set_ids = [];
			while($qr_res->nextRow()) {
				$va_snapshot = $qr_res->getRow();
				$va_set_ids[$qr_res->get('ca_set_items.set_id')] = 1;
				$t_set_item->logChange("I", $pn_user_id, ['row_id' => $qr_res->get('ca_set_items.item_id'), 'snapshot' => $va_snapshot]);
			}
			
			if (sizeof($va_set_ids)) {
				$qr_res = $this->getDb()->query("SELECT * FROM ca_sets WHERE set_id IN (?)", array(array_keys($va_set_ids)));
			
				$t_set = new ca_sets();
				while($qr_res->nextRow()) {
					$va_snapshot = $qr_res->getRow();
					$t_set->logChange("U", $pn_user_id, ['row_id' => $qr_res->get('ca_sets.set_id'), 'snapshot' => $va_snapshot]);
				}
			}
		}
		
		
		if(sizeof($va_errors)) {
			if ($vb_we_set_transaction) { $o_trans->rollback(); }
		} else {
			if ($vb_we_set_transaction) { $o_trans->commit(); }
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------
	/**
	 * Returns row_ids on items in set, in order of appearance in set
	 * unless 'shuffle' option is set, in which case row_ids are returned
	 * in a randomized order
	 *
	 * @param array $pa_options Optional list of options.
	 * @return array List of row_ids
	 */
	public function getItemRowIDs($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$pa_options['returnRowIdsOnly'] = true;
		$va_row_ids = $this->getItems($pa_options);
		
		if (isset($pa_options['shuffle']) && $pa_options['shuffle'] && isset($va_row_ids) && is_array($va_row_ids)) {
			$va_row_ids = array_keys($va_row_ids);
			shuffle($va_row_ids);
			$va_row_ids = array_flip($va_row_ids);
		}
		return $va_row_ids;
	}
	# ------------------------------------------------------
	/**
	 * Returns information on items in current set
	 *
	 * @param array $pa_options Optional array of options. Supported options are:
	 *			user_id = user_id of the current user; used to determine what may be shown
	 *			thumbnailVersions = A list of of a media versions to return with each item. Only used if the set content type is ca_objects.
	 *			thumbnailVersion = Same as 'thumbnailVersions' except it is a single value. (Maintained for compatibility with older code.)
	 *			limit = Limits the total number of records to be returned
	 *			checkAccess = An array of row-level access values to check set members for, often produced by the caGetUserAccessValues() helper. Set members with access values not in the list will be omitted. If this option is not set or left null no access checking is done.
	 *			returnRowIdsOnly = If true a simple array of row_ids (keys of the set members) for members of the set is returned rather than full item-level info for each set member. IDs are keys in the returned array.
	 *			returnItemIdsOnly = If true a simple array of item_ids (keys for the ca_set_items rows themselves) is returned rather than full item-level info for each set member.
	 *			returnItemAttributes = A list of attribute element codes for the ca_set_item record to return values for.
	 *			idsOnly = Return a simple numerically indexed array of row_ids
	 * 			template =
	 *			item_ids = array of set item_ids to limit results to -> used by getPrimaryItemsFromSets so don't have to replicate all the functionality in this function
	 *
	 * @return array An array of items. The format varies depending upon the options set. If returnRowIdsOnly or returnItemIdsOnly are set then the returned array is a 
	 *			simple list of ids. The full return array is key'ed on ca_set_items.item_id and then on locale_id. The values are arrays with keys set to a number of fields including:
	 *			set_id, item_id, row_id, rank, label_id, locale_id, caption (from the ca_set_items label), all instrinsic field content from the row_id, the display label of the row
	 *			as 'set_item_label'. 
	 *			If 'thumbnailVersion' is set then additional keys will be available for the selected media version:
	 *				representation_tag, representation_url, representation_width and representation_height (the HTML tag, URL, pixel width and pixel height of the representation respectively)
	 *			If 'thumbnailVersions' is set then additional keys will be available for the selected media versions:
	 *				representation_tag_<version_name>, representation_url_<version_name>, representation_width_<version_name> and representation_height_<version_name> (the HTML tag, URL, pixel width and pixel height of the representation respectively)
	 *			If 'returnItemAttributes' is set then there will be an additional key for each element_code prefixed with 'ca_attribute_' to ensure it doesn't conflict with any other key in the array.		
	 *			
	 */
	public function getItems($pa_options=null) {
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if (!is_array($pa_options)) { $pa_options = array(); }
		if ($pa_options['user_id'] && !$this->haveAccessToSet($pa_options['user_id'], __CA_SET_READ_ACCESS__)) { return false; }
		
		$o_db = $this->getDb();
		
		$t_rel_label_table = null;
		if (!($t_rel_table = Datamodel::getInstanceByTableNum($this->get('table_num'), true))) { return null; }
		if (method_exists($t_rel_table, 'getLabelTableName')) {
			if ($vs_label_table_name = $t_rel_table->getLabelTableName()) {
				$t_rel_label_table = Datamodel::getInstanceByTableName($vs_label_table_name, true);
			}
		}
		
		$vs_label_join_sql = '';
		if ($t_rel_label_table) {
			if ($t_rel_label_table->hasField("is_preferred")) { $vs_preferred_sql = " AND rel_label.is_preferred = 1 "; }
			$vs_label_join_sql = "LEFT JOIN ".$t_rel_label_table->tableName()." AS rel_label ON rel.".$t_rel_table->primaryKey()." = rel_label.".$t_rel_table->primaryKey()." {$vs_preferred_sql}\n";
		}
		
		
		$vs_limit_sql = '';
		if (isset($pa_options['limit']) && ($pa_options['limit'] > 0)) {
			$vs_limit_sql = "LIMIT ".$pa_options['limit'];
		}
		$vs_item_ids_sql = '';
		if (isset($pa_options['item_ids']) && (is_array($pa_options['item_ids'])) && (sizeof($pa_options['item_ids']) > 0)) {
			$vs_item_ids_sql = " AND casi.item_id IN (".join(", ", $pa_options['item_ids']).") ";
		}
		// get set items
		$vs_access_sql = '';
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_table->hasField('access')) {
			$vs_access_sql = ' AND rel.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
		$vs_deleted_sql = '';
		if ($t_rel_table->hasField('deleted')) {
			$vs_deleted_sql = ' AND rel.deleted = 0';
		}
		
		$va_representation_counts = array();
		
		$vs_rep_join_sql = $vs_rep_where_sql = $vs_rep_select = '';
		if (($t_rel_table->tableName() === 'ca_objects') && (isset($pa_options['thumbnailVersion']) || isset($pa_options['thumbnailVersions']))) {
			$vs_rep_join_sql = "LEFT JOIN ca_objects_x_object_representations AS coxor ON rel.object_id = coxor.object_id
LEFT JOIN ca_object_representations AS cor ON coxor.representation_id = cor.representation_id\n";
			$vs_rep_where_sql = " AND (coxor.is_primary = 1 OR coxor.is_primary IS NULL)";
			
			$vs_rep_select = ', coxor.*, cor.media, cor.access rep_access';
			
			// get representation counts
			$qr_rep_counts = $o_db->query("
				SELECT 
					rel.object_id, count(*) c
				FROM ca_set_items casi
				INNER JOIN ca_objects AS rel ON rel.object_id = casi.row_id
				INNER JOIN ca_objects_x_object_representations AS coxor ON coxor.object_id = rel.object_id
				WHERE
					casi.set_id = ? {$vs_access_sql} {$vs_deleted_sql} AND casi.deleted = 0
				GROUP BY
					rel.object_id
			", (int)$vn_set_id);
			
			while($qr_rep_counts->nextRow()) {
				$va_representation_counts[(int)$qr_rep_counts->get('object_id')] = (int)$qr_rep_counts->get('c');
			}
		}
		
		
		// get row labels
		$qr_res = $o_db->query("
			SELECT 
				casi.set_id, casi.item_id, casi.row_id, casi.rank,
				rel_label.".$t_rel_label_table->getDisplayField().", rel_label.locale_id
			FROM ca_set_items casi
			INNER JOIN ".$t_rel_table->tableName()." AS rel ON rel.".$t_rel_table->primaryKey()." = casi.row_id
			{$vs_label_join_sql}
			WHERE
				casi.set_id = ? {$vs_access_sql} {$vs_deleted_sql} {$vs_item_ids_sql} AND casi.deleted = 0
			ORDER BY 
				casi.rank ASC
			{$vs_limit_sql}
		", (int)$vn_set_id);
		
		$va_labels = array();
		while($qr_res->nextRow()) {
			$va_labels[$qr_res->get('item_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
		}
		
		$va_labels = caExtractValuesByUserLocale($va_labels);
		
		// get set items
		$vs_access_sql = '';
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_table->hasField('access')) {
			$vs_access_sql = ' AND rel.access IN ('.join(',', $pa_options['checkAccess']).')';
		}

		// list items happen to have the same primary key name as set items, which leads to weird side-effects
		// in the code below. so instead of getting rel.* we explicitly list the fields for ca_list_items and
		// rename cli.item_id to list_item_id so that any get('item_id') calls below refer to the set item id
		if (($t_rel_table->tableName() === 'ca_list_items')) {
			$va_rel_field_list = array();
			foreach($t_rel_table->getFields() as $vs_rel_field) {
				if($vs_rel_field == $t_rel_table->primaryKey()) {
					$va_rel_field_list[] = "rel.{$vs_rel_field} as list_{$vs_rel_field}";
				} else {
					$va_rel_field_list[] = "rel.{$vs_rel_field}";
				}
			}
			$vs_rel_field_list_sql = join(', ', $va_rel_field_list);
		} else {
			$vs_rel_field_list_sql = 'rel.*';
		}
		
		$qr_res = $o_db->query("
			SELECT 
				casi.set_id, casi.item_id, casi.row_id, casi.rank, casi.vars,
				casil.label_id, casil.caption, casil.locale_id set_item_label_locale_id,
				{$vs_rel_field_list_sql}, rel_label.".$t_rel_label_table->getDisplayField()." set_item_label, rel_label.locale_id rel_locale_id
				{$vs_rep_select}
			FROM ca_set_items casi
			LEFT JOIN ca_set_item_labels AS casil ON casi.item_id = casil.item_id
			INNER JOIN ".$t_rel_table->tableName()." AS rel ON rel.".$t_rel_table->primaryKey()." = casi.row_id
			{$vs_label_join_sql}
			{$vs_rep_join_sql}
			WHERE
				casi.set_id = ? {$vs_rep_where_sql} {$vs_access_sql} {$vs_deleted_sql} {$vs_item_ids_sql}  AND casi.deleted = 0
			ORDER BY 
				casi.rank ASC
			{$vs_limit_sql}
		", (int)$vn_set_id);

		if($ps_template = caGetOption('template', $pa_options, null)) {
			$va_processed_templates = caProcessTemplateForIDs($ps_template, $t_rel_table->tableName(), $qr_res->getAllFieldValues('row_id'), array('returnAsArray' => true));
			$qr_res->seek(0);
		}
		$va_items = array();

		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			
			unset($va_row['media']);
			
			if (
				(isset($pa_options['returnRowIdsOnly']) && ($pa_options['returnRowIdsOnly']))
				||
				(isset($pa_options['idsOnly']) && ($pa_options['idsOnly']))
			) {
				$va_items[$qr_res->get('row_id')] = true;
				continue;
			}
			if (isset($pa_options['returnItemIdsOnly']) && ($pa_options['returnItemIdsOnly'])) {
				$va_items[$qr_res->get('item_id')] = true;
				continue;
			}
			
			$va_vars = caUnserializeForDatabase($va_row['vars']);
			
			$vb_has_access_to_media = true;
			if ($vs_rep_join_sql && isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
				$vb_has_access_to_media = in_array($va_row['rep_access'], $pa_options['checkAccess']);
			}
			if ($vs_rep_join_sql && $vb_has_access_to_media) {
				if (isset($pa_options['thumbnailVersion'])) {
					$va_row['representation_tag'] = $qr_res->getMediaTag('media', $pa_options['thumbnailVersion']);
					$va_row['representation_url'] = $qr_res->getMediaUrl('media', $pa_options['thumbnailVersion']);
					$va_row['representation_path'] = $qr_res->getMediaPath('media', $pa_options['thumbnailVersion']);
					$va_row['representation_width'] = $qr_res->getMediaInfo('media',  $pa_options['thumbnailVersion'], 'WIDTH');
					$va_row['representation_height'] = $qr_res->getMediaInfo('media',  $pa_options['thumbnailVersion'], 'HEIGHT');
				}
				
				if (isset($pa_options['thumbnailVersions']) && is_array($pa_options['thumbnailVersions'])) {
					foreach($pa_options['thumbnailVersions'] as $vs_version) {
						$va_row['representation_tag_'.$vs_version] = $qr_res->getMediaTag('media', $vs_version);
						$va_row['representation_url_'.$vs_version] = $qr_res->getMediaUrl('media', $vs_version);
						$va_row['representation_path_'.$vs_version] = $qr_res->getMediaPath('media', $vs_version);
						$va_row['representation_width_'.$vs_version] = $qr_res->getMediaInfo('media',  $vs_version, 'WIDTH');
						$va_row['representation_height_'.$vs_version] = $qr_res->getMediaInfo('media',  $vs_version, 'HEIGHT');
					}
				}				
			}
			
			if (($t_rel_table->tableName() === 'ca_objects')) {
				if (isset($va_vars['selected_services'])) {
					$va_row['selected_services'] = array_keys($va_vars['selected_services']);
				} else {
					$va_row['selected_services'] = array();
				}
				
				if (isset($va_vars['selected_representations'])) {
					$va_row['selected_representations'] = array_keys($va_vars['selected_representations']);
				} else {
					$va_row['selected_representations'] = array();
				}
				
				$va_row['representation_count'] = (int)$va_representation_counts[$qr_res->get('row_id')];
			}	
			
			if (is_array($va_labels[$vn_item_id = $qr_res->get('item_id')])) {
				$va_row = array_merge($va_row, $va_labels[$vn_item_id]);
			}
			if (isset($pa_options['returnItemAttributes']) && is_array($pa_options['returnItemAttributes']) && sizeof($pa_options['returnItemAttributes'])) {
				// TODO: doing a load for each item is inefficient... must replace with a query
				$t_item = new ca_set_items($va_row['item_id']);
				
				foreach($pa_options['returnItemAttributes'] as $vs_element_code) {
					$va_row['ca_attribute_'.$vs_element_code] = $t_item->getAttributesForDisplay($vs_element_code);
				}
				
				$va_row['set_item_label'] = $t_item->getLabelForDisplay(false);
			}

			if($ps_template) {
				$va_row['displayTemplate'] = array_shift($va_processed_templates);
			}
		
			$va_items[$qr_res->get('item_id')][($qr_res->get('rel_locale_id') ? $qr_res->get('rel_locale_id') : 0)] = $va_row;
		}
		
		if (caGetOption('idsOnly', $pa_options, false)) {
			return array_keys($va_items);
		}
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Returns item_ids on items in set, in order of appearance in set.
	 *
	 * @param array $pa_options Optional array of options. Supports all options the ca_sets::getItems() supports
	 * @return array An array if item_ids
	 */
	public function getItemIDs($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$pa_options['returnItemIdsOnly'] = true;
		return $this->getItems($pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Return number of items in currently loaded set. Will return null if no set is
	 * loaded and zero if set is loaded but you don't have access to it.
	 *
	 * @return int Number of items in the set
	 */
	public function getItemCount($pa_options=null) {
		$vn_user_id = isset($pa_options['user_id']) ? (int)$pa_options['user_id'] : null;
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if ($vn_user_id && !$this->haveAccessToSet($vn_user_id, __CA_SET_READ_ACCESS__)) { return 0; }
		
		$o_db = $this->getDb();
		if (!($t_rel_table = Datamodel::getInstanceByTableNum($this->get('table_num'), true))) { return null; }
		$vs_rel_table_name = $t_rel_table->tableName();
		$vs_rel_table_pk = $t_rel_table->primaryKey();
		
		$vs_access_sql = '';
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_table->hasField('access')) {
			$vs_access_sql = ' AND '.$vs_rel_table_name.'.access IN ('.join(',', $pa_options['checkAccess']).')';
		}	
		$vs_deleted_sql = '';
		if ($t_rel_table->hasField('deleted')) {
			$vs_deleted_sql = " AND {$vs_rel_table_name}.deleted = 0";
		}
		
		$qr_res = $o_db->query("
			SELECT count(distinct ca_set_items.row_id) c
			FROM ca_set_items
			INNER JOIN {$vs_rel_table_name} ON {$vs_rel_table_name}.{$vs_rel_table_pk} = ca_set_items.row_id
			WHERE
				ca_set_items.set_id = ? {$vs_deleted_sql} {$vs_access_sql} AND (ca_set_items.deleted = 0)
		", (int)$vn_set_id);
		
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of types present for items in set
	 *
	 * @param array $pa_options
	 *		user_id = Restricts access to sets accessible by the current user. If omitted then all sets, regardless of access are returned.
	 *		checkAccess = Restricts returned sets to those with an public access level with the specified values. If omitted sets are returned regardless of public access (ca_sets.access) value. Can be a single value or array if you wish to filter on multiple public access values.
	 *		includeParents =  Include parent types in the returned type list. [Default is false]
	 * @return array List of types. Keys are integer type_ids, values are plural type names for the current locale
	 */
	public function getTypesForItems($pa_options=null) {
		if(!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if (!is_array($pa_options)) { $pa_options = array(); }
		if ($pa_options['user_id'] && !$this->haveAccessToSet($pa_options['user_id'], __CA_SET_READ_ACCESS__)) { return false; }
		
		$o_db = $this->getDb();
		
		if (!($t_rel_table = Datamodel::getInstanceByTableNum($this->get('table_num'), true))) { return null; }
		if (!($vs_type_id_fld = $t_rel_table->getTypeFieldName())) { return array(); }
		
		// get set items
		$vs_access_sql = '';
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_table->hasField('access')) {
			$vs_access_sql = ' AND rel.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
		$vs_deleted_sql = '';
		if ($t_rel_table->hasField('deleted')) {
			$vs_deleted_sql = ' AND rel.deleted = 0';
		}
		
		$va_type_list = method_exists($t_rel_table, "getTypeList") ? $t_rel_table->getTypeList() : array();
		
		$qr_res = $o_db->query("
			SELECT distinct rel.{$vs_type_id_fld}
			FROM ca_set_items casi
			INNER JOIN ".$t_rel_table->tableName()." AS rel ON rel.".$t_rel_table->primaryKey()." = casi.row_id
			WHERE
				casi.set_id = ? {$vs_access_sql} {$vs_deleted_sql} AND casi.deleted = 0
		", array($vn_set_id));
		
		$va_type_ids = array();
		while($qr_res->nextRow()) {
			$va_type_ids[$vn_type_id = $qr_res->get($vs_type_id_fld)] = $va_type_list[$vn_type_id]['name_plural'];
		}
		
		if (caGetOption('includeParents', $pa_options, false)) {
			$t_item = new ca_list_items();
			$va_expanded_types = $va_type_ids;
			$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_type_ids);
			foreach($va_type_ids as $vn_type_id => $vs_type) {
				if (is_array($va_parents = $t_item->getHierarchyAncestors($vn_type_id, array('idsOnly' => true)))) {
					foreach($va_parents as $vn_parent_id) {
						$va_expanded_types[$vn_parent_id] = $va_labels[$vn_parent_id];
					}
				}
			}
			$va_type_ids = $va_expanded_types;
		}
		
		return $va_type_ids;
	}
	# ------------------------------------------------------
	# Bundles
	# ------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of items in the currently loaded set
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getSetItemHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null, $pa_bundle_settings=null) {
		if ($this->getItemCount() > 50) {
			$vs_thumbnail_version = 'tiny';
		} else {
			$vs_thumbnail_version = "thumbnail";
		}
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_set', $this);		
		$o_view->setVar('id_prefix', $ps_form_name);	
		$o_view->setVar('placement_code', $ps_placement_code);		
		$o_view->setVar('request', $po_request);
		
		if ($this->getPrimaryKey()) {
			$vs_set_table_name = Datamodel::getTableName($this->get('table_num'));
			if (!($vs_template = caGetOption("{$vs_set_table_name}_display_template", $pa_bundle_settings, null))) {		// display template by table
				if (!($vs_template = caGetOption('display_template', $pa_bundle_settings, null))) {						// try the old non-table-specific template?
					$vs_template = $this->getAppConfig()->get("{$vs_set_table_name}_set_item_display_template");			// use default in app.conf
				}
			} 
		
			$va_items = caExtractValuesByUserLocale($this->getItems(array(
				'thumbnailVersion' => $vs_thumbnail_version,
				'user_id' => $po_request->getUserID(),
				'template' => $vs_template
			)), null, null, array());
			$o_view->setVar('items', $va_items);
		} else {
			$o_view->setVar('items', array());
		}

		$o_view->setVar('settings', $pa_bundle_settings);
		
		if ($t_row = $this->getItemTypeInstance()) {
			$o_view->setVar('t_row', $t_row);
			$o_view->setVar('type_singular', $t_row->getProperty('NAME_SINGULAR'));
			$o_view->setVar('type_plural', $t_row->getProperty('NAME_PLURAL'));
		}
		
		$o_view->setVar('lookup_urls', caJSONLookupServiceUrl($po_request, Datamodel::getTableName($this->get('table_num'))));
		
		return $o_view->render('ca_set_items.php');
	}
	# ------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for set membership control. This a bundle that can appear in the editor of any item type (objects, entities, places, collections, etc.)
	 * that can be the content type of a set, and enables users to toggle membership in any set for the open record.
	 *
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the containing HTML form
	 * @param int $pn_table_num The table_num of the currently open item record
	 * @param int $pn_item_id The row_id of the currently open item record
	 * @param int $pn_user_id The user_id of the current user; used to determine which sets are displayed as choices
	 * @param array $pa_bundle_settings Bundle settings
	 * @param array $pa_options Array of options (none supported yet)
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getItemSetMembershipHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pn_table_num, $pn_row_id, $pn_user_id=null, $pa_bundle_settings=null, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
 		$va_cur_sets = caExtractValuesByUserLocale($this->getSetsForItem($pn_table_num, $pn_row_id, array('user_id' => $pn_user_id)));
 		
		$o_view->setVar('t_set', $this);		
		$o_view->setVar('id_prefix', $ps_form_name);	
		$o_view->setVar('placement_code', $ps_placement_code);		
		$o_view->setVar('request', $po_request);
 		$o_view->setVar('set_ids', array_keys($va_cur_sets));
 		$o_view->setVar('available_set_ids', $va_available_sets = $this->getAvailableSetsForItem($pn_table_num, $pn_row_id, array('user_id' => $pn_user_id, 'access' => __CA_SET_EDIT_ACCESS__)));
 		$o_view->setVar('sets', $va_sets = $this->getSets(array('table' => $pn_table_num, 'user_id' => $pn_user_id, 'access' => __CA_SET_EDIT_ACCESS__)));
 		$o_view->setVar('settings', $pa_bundle_settings);
 		
 		$va_set_options = array();
 		foreach($va_sets as $vn_set_id => $va_sets_by_locale) {
 			foreach($va_sets_by_locale as $vn_locale_id => $va_set) {
 				$va_set_options[$va_set['name']] = $vn_set_id;
 			}
 		}
 		
 		$o_view->setVar('set_options', $va_set_options);
 		
 		$vn_i=0;
 		$va_initial_values = array();
 		foreach($va_cur_sets as $va_set_info) {
 			$va_initial_values[$va_set_info['item_id'].''] = array('set_id' => $va_set_info['set_id'], 'set_name' => $va_set_info['name']);
 			$vn_i++;
 		}
 		$o_view->setVar('initial_values', $va_initial_values);
 		
 		$o_view->setVar('batch', (bool)(isset($pa_options['batch']) && $pa_options['batch']));
 		
		return $o_view->render('ca_sets_checklist.php');
	}
	# ------------------------------------------------------
	# Utilities
	# ------------------------------------------------------
	/**
	 * Returns an empty model instance corresponding to the table that is the content type for the currently loaded set.
	 *
	 * @return object Instance of model corresponding to content type of the currently loaded set, or null if not set is loaded.
	 */
	public function getItemTypeInstance() {
		if (!$this->getPrimaryKey()) { return null; }
		
		return Datamodel::getInstanceByTableNum($this->get('table_num'), true);
	}
	# ------------------------------------------------------
	/**
	 * Returns the name of the table that is the content type for the currently loaded set.
	 *
	 * @return string
	 */
	public function getItemType() {
		if (!$this->getPrimaryKey()) { return null; }
		
		return Datamodel::getTableName($this->get('table_num'));
	}
	# ------------------------------------------------------
	/**
	 * Returns the first item from each set listed in $pa_set_ids.
	 *
	 * @param array $pa_set_ids The set_ids (*not* set codes) for which the first item should be fetched
	 * @param array $pa_options And optional array of options. Supported values include:
	 *			version = the media version to include with returned set items, when media is available
	 *
	 * @return array A list of items; the keys of the array are set_ids while the values are associative arrays containing the latest information.
	 */
	public static function getFirstItemsFromSets($pa_set_ids, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if($pa_options["version"]){
			$vs_version = $pa_options["version"];
		}else{
			$vs_version = "thumbnail";
		}
		$pa_options["thumbnailVersion"] = $vs_version;
		$pa_options["limit"] = 1;
		$t_set = new ca_sets();
		$va_items = array();
		foreach($pa_set_ids as $vn_set_id) {
			if ($t_set->load($vn_set_id)) {
				$va_item_list = caExtractValuesByUserLocale($t_set->getItems($pa_options));
				$va_items[$vn_set_id] = $va_item_list;	
			}
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Returns the primary items from each set listed in $pa_set_ids.  If no primary item is selected, default to first item in set
	 *
	 * @param array $pa_set_ids The set_ids (*not* set codes) for which the first item should be fetched
	 * @param array $pa_options And optional array of options. Supported values include:
	 *			version = the media version to include with returned set items, when media is available
	 *			primary_attribute = md attribute used to indicate set item is primary; default = set_item_is_primary
	 *			primary_list = list for primary attribute; default = set_item_is_primary
	 *			primary_value = list item value for primary items; default = is_primary
	 *
	 * @return array A list of items; the keys of the array are set_ids while the values are associative arrays containing the latest information.
	 */
	public static function getPrimaryItemsFromSets($pa_set_ids, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vs_version = (isset($pa_options['version']) && $pa_options['version']) ? $pa_options['version'] : 'thumbnail';
		$pa_options["thumbnailVersion"] = $vs_version;
		$vs_primary_attribute = (isset($pa_options['primary_attribute']) && $pa_options['primary_attribute']) ? $pa_options['primary_attribute'] : 'set_item_is_primary';
		$vs_primary_list = (isset($pa_options['primary_list']) && $pa_options['primary_list']) ? $pa_options['primary_list'] : 'set_item_is_primary';
		$vs_primary_value = (isset($pa_options['primary_value']) && $pa_options['primary_value']) ? $pa_options['primary_value'] : 'is_primary';
		
		
		#$pa_options["limit"] = 1;
		$t_set_item = new ca_set_items();
		$va_items = array();
		$o_db = new Db();
		$qr_element = $o_db->query("
			select element_id from ca_metadata_elements where element_code = ?
		", array($vs_primary_attribute));
		$va_primary_set_item_ids_for_set = array();
		if($qr_element->numRows()){
			$qr_element->nextRow();
			$vn_element_id = $qr_element->get("element_id");
			$t_lists = new ca_lists();
			$vn_is_primary_item_id = $t_lists->getItemIDFromList($vs_primary_list, $vs_primary_value);
			if($vn_is_primary_item_id){
				foreach($pa_set_ids as $vn_set_id) {					
					# --- query for set items based on attribute value of primary_attribute
					$qr_primary_items = $o_db->query("
						SELECT si.item_id, si.row_id
						FROM ca_attribute_values av
						INNER JOIN ca_attributes as a ON av.attribute_id = a.attribute_id
						INNER JOIN ca_set_items as si ON si.item_id = a.row_id
						WHERE a.table_num = ? AND av.item_id = ? AND si.set_id = ?					
						", array($t_set_item->tableNum(), $vn_is_primary_item_id, $vn_set_id));
					if($qr_primary_items->numRows()){
						$va_tmp = array();
						while($qr_primary_items->nextRow()){
							$va_tmp[] = $qr_primary_items->get("item_id");
						}
						$va_primary_set_item_ids_for_set[$vn_set_id] = $va_tmp;
					}					
				}			
			}
		}
		$t_set = new ca_sets();
		foreach($pa_set_ids as $vn_set_id) {
			if ($t_set->load($vn_set_id)) {
				if(is_array($va_primary_set_item_ids_for_set[$vn_set_id])){
					$pa_options["item_ids"] = $va_primary_set_item_ids_for_set[$vn_set_id];
					$pa_options["limit"] = null;
				}else{
					$pa_options["limit"] = 1;
				}
				$va_item_list = caExtractValuesByUserLocale($t_set->getItems($pa_options));
				$va_items[$vn_set_id] = $va_item_list;	
			}
		}
		
		return $va_items;		

		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Returns name of type of content (synonymous with the table name for the content) currently loaded set contains for display. Will return name in singular number unless the 'number' option is set to 'plural'
	 *
	 * @param int $pn_table_num Table number to return name for. If omitted then the name for the content type contained by the current set will be returned. Use this parameter if you want to force a content type without having to load a set.
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		number = Set to 'plural' to return plural version of name; set to 'singular' [default] to return the singular version
	 * @return string The name of the type of content or null if $pn_table_num is not set to a valid table and no set is loaded.
	 */
	public function getSetContentTypeName($pm_table_name_or_num=null, $pa_options=null) {
		if (!$pm_table_name_or_num && !($pm_table_name_or_num = $this->get('table_num'))) { return null; }
	 	if (!($vn_table_num = Datamodel::getTableNum($pm_table_name_or_num))) { return null; }
		
		$t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true);
		return (isset($pa_options['number']) && ($pa_options['number'] == 'plural')) ? $t_instance->getProperty('NAME_PLURAL') : $t_instance->getProperty('NAME_SINGULAR');
	}
	# ------------------------------------------------------
	/**
	 * Returns specified attribute for each of the specified set_ids.
	 *
	 * @param mixed $pm_element_code_or_id Metadata element code or element_id
	 * @param array $pa_set_ids Array of set_ids to fetch values for
	 * @param array $pa_options Optional array of options. Supports all options that BaseModeWithAttributes::getAttributeForIDs() does
	 */
	public function getAttributeFromSets($pm_element_code_or_id, $pa_set_ids, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		return $this->getAttributeForIDs($pm_element_code_or_id, $pa_set_ids, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Returns list of primary representation tags from the current set, if it's content type is ca_objects
	 *
	 * @param string $ps_version Representation version to return tags for (ex. "small", "thumbnail")
	 * @param array $pa_options Array of options. Support options are:
	 *			checkAccess = an array of access values to filter returned representations on. If not set no access checking is performed.
	 *			quote = if set to true the media tags are return in double quotes suitable for use in a Javascript array. This options makes it convenient to join the return array into a Javascript array for use with UI libraries. Default is false.
	 * @return array A list of HTML tags for primary representations of objects in the set, in set order. They array is key'ed on object_id.
	 */
	public function getRepresentationTags($ps_version, $pa_options=null) {
		if (!($vn_set_id = $this->getPrimaryKey())) { return null; }
		if (!$this->get('table_num') == Datamodel::getTableNum("ca_objects")) { return null; }
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vs_access_sql = '';
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
			$vs_access_sql = ' AND o.access IN ('.join(',', $pa_options['checkAccess']).') AND caor.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT 
				o.object_id, caor.representation_id, caor.media
			FROM ca_set_items casi
			INNER JOIN ca_objects AS o ON o.object_id = casi.row_id
			INNER JOIN ca_objects_x_object_representations AS caxor ON caxor.object_id = o.object_id
			INNER JOIN ca_object_representations AS caor ON caor.representation_id = caxor.representation_id
			
			WHERE
				(casi.set_id = ?) AND (caxor.is_primary = 1) AND (o.deleted = 0) AND (casi.deleted = 0)
				{$vs_access_sql}
			ORDER BY 
				casi.rank ASC
		", (int)$vn_set_id);
		
		$va_reps = array();
		while($qr_res->nextRow()) {
			$va_reps[$qr_res->get('object_id')] = (isset($pa_options['quote']) && $pa_options['quote']) ? '"'.$qr_res->getMediaTag('media', $ps_version).'"' : $qr_res->getMediaTag('media', $ps_version);
		}
		
		return $va_reps;
	}
	# ------------------------------------------------------
	# Private
	# ------------------------------------------------------
	/**
	 * Returns set_id corresponding to set code, or passed back set_id if input in numeric (it doesn't actually verify that a numeric set_id is actually valid)
	 *
	 * @param mixed $pm_set_code_or_id
	 * @return int set_id corresponding to $pm_set_code_or_id
	 */
	private function _getSetID($pm_set_code_or_id) {
		if (is_numeric($pm_set_code_or_id)) {
			$vn_set_id = intval($pm_set_code_or_id);
		} else {
			$t_set = new ca_sets();
			if (!$t_set->load(array('set_code' => $pm_set_code_or_id))) {
				return null;
			}
			$vn_set_id = $t_set->getPrimaryKey();
		}
		
		return $vn_set_id;
	}
	# ------------------------------------------------------
	/**
	 * Returns table number for specified table name (or number) and validates that it exists.
	 *
	 * @param mixed $pm_table_name_or_num Name or number of table
	 * @return int Corresponding table number or null if table does not exist
	 */
	private function _getTableNum($pm_table_name_or_num) {
		if (!is_numeric($pm_table_name_or_num)) {
			$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);
		} else {
			$vn_table_num = $pm_table_name_or_num;
		}
		
		if (!Datamodel::getInstanceByTableNum($vn_table_num, true)) {
			// table name or number is not valid
			return null;
		}
		return $vn_table_num;
	}
	# ------------------------------------------------------
	# new functions for pawtucket lightbox
	# ------------------------------------------------------
	/*
	* options
	*			user_id -> ca_users.user_id that owns or has access to set
	*			owner - if set, returns only sets owned by the passed user_id
	*			table - if set, list is restricted to sets that can contain the specified item. You can pass a table name or number. If omitted sets containing any content will be returned.
	*			setType - Restricts returned sets to those of the specified type. You can pass a type_id or list item code for the set type. If omitted sets are returned regardless of type.
	*			access - read = 1, write = 2; Restricts returned sets to those with at least the specified access level for the specified user. If "owner" is true then this option has no effect.
	*			checkAccess - Restricts returned sets to those with an access level of the specified values. If omitted sets are returned regardless of public access (ca_sets.access) value. Can be a single value or array if you wish to filter on multiple public access values.
	*			parents_only - Only show those sets with parent_id IS NULL  
	*
	*
	*/
	public function getSetsForUser($pa_options){
		if (!is_array($pa_options)) { $pa_options = array(); }
		$pn_user_id = isset($pa_options['user_id']) ? (int)$pa_options['user_id'] : null;
		$pm_table_name_or_num = isset($pa_options['table']) ? $pa_options['table'] : null;
		if ($pm_table_name_or_num && !($vn_table_num = $this->_getTableNum($pm_table_name_or_num))) { return null; }
		$pm_type = isset($pa_options['setType']) ? $pa_options['setType'] : null;
		$pn_access = isset($pa_options['access']) ? $pa_options['access'] : null;
		$pa_public_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		if ($pa_public_access && is_numeric($pa_public_access) && !is_array($pa_public_access)) {
			$pa_public_access = array($pa_public_access);
		}
		if (!is_array($pa_public_access)) { $pa_public_access = []; }
		for($vn_i=0; $vn_i < sizeof($pa_public_access); $vn_i++) { $pa_public_access[$vn_i] = intval($pa_public_access[$vn_i]); }

		if($pn_user_id){
			$va_extra_joins = array();
			$va_sql_wheres = array("(cs.deleted = 0)");
			$va_sql_params = array();
			$o_db = $this->getDb();
			
			if ($vn_table_num) {
				$va_sql_wheres[] = "(cs.table_num = ?)";
				$va_sql_params[] = (int)$vn_table_num;
			}
			if (!is_null($pa_public_access) && is_array($pa_public_access) && sizeof($pa_public_access)) {
				$va_sql_wheres[] = "(cs.access IN (?))";
				$va_sql_params[] = $pa_public_access;
			}			
			if (isset($pm_type) && $pm_type) {
				if(is_numeric($pm_type)){
					$va_sql_wheres[] = "(cs.type_id = ?)";
					$va_sql_params[] = (int)$pm_type;
				}else{
					# --- look up code of set type
					$t_list = new ca_lists();
					$vn_type_id = $t_list->getItemIDFromList("set_types", $pm_type);
					if($vn_type_id){
						$va_sql_wheres[] = "(cs.type_id = ?)";
						$va_sql_params[] = (int)$vn_type_id;
					}
				}
			}
			if($pa_options["owner"]){
				$va_sql_wheres[] = "(cs.user_id = ".$pn_user_id.")";
			}else{
				# --- if owner is not set to true, we're finding all sets the user has access to or is owner of
				# --- we also check the users' access to the set if set
				$t_user = new ca_users();
				$t_user->load($pn_user_id);
				
				if ($t_user->getPrimaryKey()) {
					$vs_access_sql = ($pn_access > 0) ? " AND (access >= ".intval($pn_access).")" : "";
					if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
						$vs_sql = "(
							(cs.user_id = ".intval($pn_user_id).") OR 
							(cs.set_id IN (
									SELECT set_id 
									FROM ca_sets_x_user_groups 
									WHERE 
										group_id IN (".join(',', array_keys($va_groups)).") {$vs_access_sql}
										AND
										(
											 (sdatetime IS NULL AND edatetime IS NULL)
											 OR 
											 (
												sdatetime <= ".time()." AND edatetime >= ".time()."
											 )
										)
								)
							)
						)";
					} else {
						$vs_sql = "(cs.user_id = {$pn_user_id})";
					}
					
					$vs_sql .= " OR (cs.set_id IN (
											SELECT set_id 
											FROM ca_sets_x_users 
											WHERE 
												user_id = {$pn_user_id} {$vs_access_sql}
												AND
												(
													 (sdatetime IS NULL AND edatetime IS NULL)
													 OR 
													 (
														sdatetime <= ".time()." AND edatetime >= ".time()."
													 )
												)
										)
									)";
					
					
					$va_sql_wheres[] = "({$vs_sql})";
				}
			}
			if($pa_options["parents_only"]){
				$va_sql_wheres[] = "cs.parent_id IS NULL";
			}
			$qr_res = $o_db->query("SELECT cs.set_id, cs.user_id, type_id, cu.fname, cu.lname
									FROM ca_sets cs
									INNER JOIN ca_users AS cu ON cs.user_id = cu.user_id
									".join("\n", $va_extra_joins)."
									".(sizeof($va_sql_wheres) ? "WHERE " : "")." ".join(" AND ", $va_sql_wheres)."
									", $va_sql_params);
			$va_sets = array();
			$t_list = new ca_lists();
			while($qr_res->nextRow()) {
				$vn_table_num = $qr_res->get('table_num');
				if (!isset($va_type_name_cache[$vn_table_num]) || !($vs_set_type = $va_type_name_cache[$vn_table_num])) {
					$vs_set_type = $va_type_name_cache[$vn_table_num] = $this->getSetContentTypeName($vn_table_num, array('number' => 'plural'));
				}
				
				$vs_type = $t_list->getItemFromListForDisplayByItemID('set_types', $qr_res->get('type_id'));
			
				$va_sets[$qr_res->get('set_id')] = array_merge($qr_res->getRow(), array('set_content_type' => $vs_set_type, 'set_type' => $vs_type));
			}
			return $va_sets;
		}else{
			return false;
		}
	}
	# ---------------------------------------------------------------
	public function getSetChangeLog($va_set_ids){
		if(is_array($va_set_ids) && sizeof($va_set_ids)){
			$o_db = $this->getDB();


// 			$q_change_log_sets = $o_db->query("
// 						SELECT DISTINCT
// 							wcl.log_id, wcl.log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
// 							wclsnap.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname
// 						FROM ca_change_log wcl
// 						INNER JOIN ca_change_log_snapshots AS wclsnap ON wclsnap.log_id = wcl.log_id
// 						LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
// 						LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
// 						WHERE
// 							(
// 								(wcl.logged_table_num = ".((int)Datamodel::getTableNum("ca_set_items")).") AND (wcl.logged_row_id IN (".implode(", ", $va_set_ids)."))
// 							)
// 						ORDER BY wcl.log_datetime desc
// 					");
// 				$va_set_change_log = array();
// 				if($q_change_log_sets->numRows()){
// 					while($q_change_log_sets->nextRow()){
// 						$va_tmp = array();
// 						$va_tmp = $q_change_log_sets->getRow();
// 						$va_tmp['snapshot'] = caUnserializeForDatabase($q_change_log_sets->get('snapshot'));
// 						$va_set_change_log[$q_change_log_sets->get('log_datetime')] = $va_tmp;
// 					}
// 				}
			
			global $g_ui_locale_id;
			$q_change_log = $o_db->query("
						SELECT DISTINCT
							wcl.log_id, wcl.log_datetime log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
							wclsnap.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname, csl.name, wcl.logged_row_id set_id
						FROM ca_change_log wcl
						INNER JOIN ca_change_log_snapshots AS wclsnap ON wclsnap.log_id = wcl.log_id
						LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
						LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
						INNER JOIN ca_set_labels AS csl ON csl.set_id = wcl.logged_row_id
						WHERE
							(
								(wcl.logged_table_num = ".((int)$this->tableNum()).") AND (wcl.logged_row_id IN (".implode(", ", $va_set_ids)."))
								AND (csl.locale_id = ?)
							)
						UNION
						SELECT DISTINCT
							wcl.log_id, wcl.log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
							wclsnap.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname, csl.name, wcls.subject_row_id set_id
						FROM ca_change_log wcl
						INNER JOIN ca_change_log_snapshots AS wclsnap ON wclsnap.log_id = wcl.log_id
						LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
						LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
						INNER JOIN ca_set_labels AS csl ON csl.set_id = wcls.subject_row_id
						WHERE
							 (
								(wcls.subject_table_num = ".((int)$this->tableNum()).") AND (wcls.subject_row_id IN (".implode(", ", $va_set_ids).")) AND wcl.logged_table_num IN (".Datamodel::getTableNum("ca_set_items").", ".Datamodel::getTableNum("ca_sets_x_users").", ".Datamodel::getTableNum("ca_sets_x_user_groups").")
								AND (csl.locale_id = ?)
							)
						ORDER BY log_datetime desc
					", $g_ui_locale_id, $g_ui_locale_id);
				$va_set_change_log = array();
				if($q_change_log->numRows()){
					while($q_change_log->nextRow()){
						$va_tmp = array();
						$va_tmp = $q_change_log->getRow();
 						$va_tmp['snapshot'] = caUnserializeForDatabase($q_change_log->get('snapshot'));
 						$va_set_change_log[$q_change_log->get('log_datetime')] = $va_tmp;
					}
				}
				
				# --- comments
				# --- set comments
				$q_set_comments = $o_db->query("
								SELECT c.created_on log_datetime, c.comment, wu.email, wu.fname, wu.lname, c.table_num, c.row_id set_id, csl.name
								FROM ca_item_comments c
								LEFT JOIN ca_users AS wu ON c.user_id = wu.user_id
								INNER JOIN ca_set_labels AS csl ON csl.set_id = c.row_id
								WHERE c.table_num = ".((int)$this->tableNum())." AND c.row_id IN (".implode(", ", $va_set_ids).")
								ORDER BY c.created_on desc
							");
				if($q_set_comments->numRows()){
					while($q_set_comments->nextRow()){
						$va_tmp = array();
						$va_tmp = $q_set_comments->getRow();
 						$va_tmp["logged_table_num"] = Datamodel::getTableNum("ca_item_comments");
 						$va_set_change_log[$q_set_comments->get('log_datetime')] = $va_tmp;
					}
				}
				
				# --- item comments
				$q_set_item_comments = $o_db->query("
								SELECT c.created_on log_datetime, c.comment, wu.email, wu.fname, wu.lname, c.table_num, si.set_id set_id, csl.name
								FROM ca_item_comments c
								LEFT JOIN ca_users AS wu ON c.user_id = wu.user_id
								INNER JOIN ca_set_items as si ON si.item_id = c.row_id
								INNER JOIN ca_set_labels AS csl ON csl.set_id = si.set_id
								WHERE c.table_num = ".(Datamodel::getTableNum("ca_set_items"))." AND si.set_id IN (".implode(", ", $va_set_ids).")
								ORDER BY c.created_on desc
							");
				if($q_set_item_comments->numRows()){
					while($q_set_item_comments->nextRow()){
						$va_tmp = array();
						$va_tmp = $q_set_item_comments->getRow();
 						$va_tmp["logged_table_num"] = Datamodel::getTableNum("ca_item_comments");
 						$va_set_change_log[$q_set_item_comments->get('log_datetime')] = $va_tmp;
					}
				}
				ksort($va_set_change_log);
				return array_reverse($va_set_change_log);
		}else{
			return false;
		}
	}
	# ---------------------------------------------------------------
	# --- returns array of users with access to set
	# ---------------------------------------------------------------
	public function getSetUsers(){
		if (!($vn_set_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDB();
		$va_users = array();
		# --- add the owner of the set
		$q_set_users = $o_db->query("
						SELECT u.user_id, u.email, u.fname, u.lname, su.access
						FROM ca_sets_x_users su
						INNER JOIN ca_users AS u ON su.user_id = u.user_id
						WHERE su.set_id = ?
						", $vn_set_id);
		if($q_set_users->numRows()){
			while($q_set_users->nextRow()){
				$va_users[$q_set_users->get("user_id")] = array("user_id" => $q_set_users->get("user_id"), "name" => trim($q_set_users->get("fname")." ".$q_set_users->get("lname")), "email" => $q_set_users->get("email"), "access" => $q_set_users->get("access"));
			}
		}
		$q_set_owner = $o_db->query("
						SELECT u.user_id, u.email, u.fname, u.lname
						FROM ca_users u
						WHERE u.user_id = ?
						", $this->get("user_id"));
		if($q_set_owner->numRows()){
			$q_set_owner->nextRow();
			$va_users[$this->get("user_id")] = array("owner" => true, "user_id" => $this->get("user_id"), "name" => trim($q_set_owner->get("fname")." ".$q_set_owner->get("lname")), "email" => $q_set_owner->get("email"), "access" => 2);
		}
		return $va_users;
	}
	# ---------------------------------------------------------------
	public function getSetGroups(){
		if (!($vn_set_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDB();
		$va_groups = array();
		$q_set_groups = $o_db->query("
						SELECT g.group_id, g.name, sg.access
						FROM ca_sets_x_user_groups sg
						INNER JOIN ca_user_groups AS g ON sg.group_id = g.group_id
						WHERE sg.set_id = ?
						", $vn_set_id);
		if($q_set_groups->numRows()){
			while($q_set_groups->nextRow()){
				$va_groups[$q_set_groups->get("group_id")] = array("group_id" => $q_set_groups->get("group_id"), "name" => $q_set_groups->get("name"), "access" => $q_set_groups->get("access"));
			}
		}
		return $va_groups;
	}
	# ---------------------------------------------------------------
	public function getSetResponseIds($pn_user_id = null, $pn_set_id = null){
		if(!$pn_set_id){
			$pn_set_id = $this->getPrimaryKey();
		}
		if (!$pn_set_id) { return null; }
		$o_db = $this->getDB();
		$va_set_ids = array();
		$va_wheres = array();
		if($pn_user_id){
			$va_wheres[] = " AND s.user_id = ".(int)$pn_user_id;
		}
		$q_responses = $o_db->query("
						SELECT s.set_id
						FROM ca_sets s
						WHERE s.deleted = 0 AND s.parent_id = ?
						".join(" ", $va_wheres), $pn_set_id);
		if($q_responses->numRows()){
			while($q_responses->nextRow()){
				$va_set_ids[] = $q_responses->get("set_id");
			}
		}
		return $va_set_ids;
	}
	# ---------------------------------------------------------------
	/**
	 * Check if currently loaded row is save-able
	 *
	 * @param RequestHTTP $po_request
	 * @param string $ps_bundle_name Optional bundle name to test write-ability on. If omitted write-ability is considered for the item as a whole.
	 * @return bool True if record can be saved, false if not
	 */
	public function isSaveable($po_request, $ps_bundle_name=null) {
		// Check type restrictions
		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $po_request->user->getTypeAccessLevel($this->tableName(), $this->getTypeID());
			if ($vn_type_access != __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}

		// Check source restrictions
		if ((bool)$this->getAppConfig()->get('perform_source_access_checking')) {
			$vn_source_access = $po_request->user->getSourceAccessLevel($this->tableName(), $this->getSourceID());
			if ($vn_source_access < __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}

		// Check item level restrictions
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking') && $this->getPrimaryKey()) {
			$vn_item_access = $this->checkACLAccessForUser($po_request->user);
			if ($vn_item_access < __CA_ACL_EDIT_ACCESS__) {
				return false;
			}
		}

		// Check actions
		if (!$this->getPrimaryKey() && !$po_request->user->canDoAction('can_create_sets')) {
			return false;
		}
		if ($this->getPrimaryKey() && !$po_request->user->canDoAction('can_edit_sets')) {
			return false;
		}

		if ($ps_bundle_name) {
			if ($po_request->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) < __CA_BUNDLE_ACCESS_EDIT__) { return false; }
		}

		return true;
	}
	# ---------------------------------------------------------------
	/**
	 * Duplicate all items in this set
	 *
	 * @param int $pn_user_id
	 * @param array $pa_options
	 * @return ca_sets|bool
	 */
	public function duplicateItemsInSet($pn_user_id, $pa_options=array()) {
		if(!$this->getPrimaryKey()) { return false; }
		if($this->getItemCount() < 1) { return false; }
		$t_user = new ca_users($pn_user_id);
		if(!$t_user->getPrimaryKey()) { return false; } // we need a user for duplication
		global $g_ui_locale_id;

		if(caGetOption('addToCurrentSet', $pa_options, false)) {
			$t_set_to_add_dupes_to = $this;
		} else { // create new set for dupes
			$t_set_to_add_dupes_to = new ca_sets();
			$t_set_to_add_dupes_to->set('type_id', $this->get('type_id'));
			$t_set_to_add_dupes_to->set('table_num', $this->get('table_num'));
			$t_set_to_add_dupes_to->set('user_id', $this->get('user_id'));
			$t_set_to_add_dupes_to->set('set_code', $this->get('set_code').'-'._t('dupes'));
			$t_set_to_add_dupes_to->setMode(ACCESS_WRITE);
			$t_set_to_add_dupes_to->insert();
			if(!$t_set_to_add_dupes_to->getPrimaryKey()) {
				$this->errors = $t_set_to_add_dupes_to->errors;
				return false;
			}

			$t_set_to_add_dupes_to->addLabel(array('name' => $this->getLabelForDisplay().' '._t('[Duplicates]')),$g_ui_locale_id, null, true);
		}

		$va_items = array_keys($this->getItemRowIDs());
		$va_dupes = array();

		foreach($va_items as $vn_row_id) {
			/** @var BundlableLabelableBaseModelWithAttributes $t_instance */
			$t_instance = Datamodel::getInstance($this->get('table_num'));
			if(!$t_user->canDoAction('can_duplicate_' . $t_instance->tableName())) {
				$this->postError(2580, _t('You do not have permission to duplicate these items'), 'ca_sets->duplicateItemsInSet()');
				return false;
			}
			if(!$t_instance->load($vn_row_id)) { continue; }

			// let's dupe
			$t_dupe = $t_instance->duplicate(array(
				'user_id' => $pn_user_id,
				'duplicate_nonpreferred_labels' => $t_user->getPreference($t_instance->tableName().'_duplicate_nonpreferred_labels'),
				'duplicate_attributes' => $t_user->getPreference($t_instance->tableName().'_duplicate_attributes'),
				'duplicate_relationships' => $t_user->getPreference($t_instance->tableName().'_duplicate_relationships'),
				'duplicate_media' => $t_user->getPreference($t_instance->tableName().'_duplicate_media'),
				'duplicate_subitems' => $t_user->getPreference($t_instance->tableName().'_duplicate_subitems')
			));

			if($t_dupe instanceof BaseModel) {
				$va_dupes[] = $t_dupe->getPrimaryKey();
			}
		}

		$t_set_to_add_dupes_to->addItems($va_dupes);

		return $t_set_to_add_dupes_to;
	}
	# ---------------------------------------------------------------
	/**
	 * Test if set code exists
	 *
	 * @param string $set_code
	 * @param array $options Options include:
	 *		user_id = Considers set existance subject to acccess the user. 
	 *		access = Consider set to exist if user has at least the specified access level. If user_id is omitted then this option has no effect. If user_id is set and this option is omitted, then a set will be considered to exist if the user has at least read access. 
	 *		checkAccess = Consider set to exist if it has a public access level with the specified values. Can be a single value or array if you wish to filter on multiple public access values.
	 *			
	 * @return bool
	 */
	static public function setExists($set_code, $options=null) {
		if ($ids = ca_sets::find(['set_code' => $set_code], ['returnAs' => 'ids'])) {
			
			if ($user_id = caGetOption('user_id', $options, null)) {
				$id = array_shift($ids);
				$t_set = new ca_sets();
				return $t_set->haveAccessToSet($user_id, caGetOption('access', $options, null), $id, ['access' => caGetOption('access', $options, null)]);
			}
			return true;
		}
		return false;
	}
	# ---------------------------------------------------------------
}
