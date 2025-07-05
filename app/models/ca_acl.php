<?php
/** ---------------------------------------------------------------------
 * app/models/ca_acl.php : table access class for table ca_acl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
if(!defined('__CA_ACL_NO_ACCESS__')) { define('__CA_ACL_NO_ACCESS__', 0); }
if(!defined('__CA_ACL_READONLY_ACCESS__')) { define('__CA_ACL_READONLY_ACCESS__', 1); }
if(!defined('__CA_ACL_EDIT_ACCESS__')) { define('__CA_ACL_EDIT_ACCESS__', 2); }
if(!defined('__CA_ACL_EDIT_DELETE_ACCESS__')) { define('__CA_ACL_EDIT_DELETE_ACCESS__', 3); }

BaseModel::$s_ca_models_definitions['ca_acl'] = array(
 	'NAME_SINGULAR' 	=> _t('access control list'),
 	'NAME_PLURAL' 		=> _t('access control lists'),
 	'FIELDS' 			=> array(
		'acl_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'ACL id', 'DESCRIPTION' => 'Identifier for ACL'
		),
		'group_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Group id', 'DESCRIPTION' => 'Identifier for Group'
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for User'
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Table num'), 'DESCRIPTION' => _t('Table num'),
				'BOUNDS_VALUE' => array(1,255)
		),
		'row_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Row id', 'DESCRIPTION' => 'Identifier for Row'
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Access'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('no access') => __CA_ACL_NO_ACCESS__,
					_t('can read') => __CA_ACL_READONLY_ACCESS__,
					_t('can edit') => __CA_ACL_EDIT_ACCESS__,
					_t('can edit + delete') => __CA_ACL_EDIT_DELETE_ACCESS__
				)
		),
		'notes' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Notes'), 'DESCRIPTION' => _t('Notes'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'inherited_from_table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Inherited from table num'), 'DESCRIPTION' => _t('ACL inherited from table number'),
				'BOUNDS_VALUE' => array(1,255)
		),
		'inherited_from_row_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Inherited from row id', 'DESCRIPTION' => 'ACL inherited from row'
		)
	)
);

class ca_acl extends BaseModel {
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
	protected $TABLE = 'ca_acl';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'acl_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('notes');

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
	protected $ORDER_BY = array('notes');

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
	
	
	static $s_acl_access_value_cache = [];
	
	static $temporary_tables = [];
	

	# ------------------------------------------------------
	/**
	 * Checks access control list for the specified row and user and returns an access value. Values are:
	 *
	 * __CA_ACL_NO_ACCESS__   (0)
	 * __CA_ACL_READONLY_ACCESS__ (1)
     * __CA_ACL_EDIT_ACCESS__ (2)
     * __CA_ACL_EDIT_DELETE_ACCESS__ (3)
	 *
	 * @param ca_users $t_user A ca_users object
	 * @param int $table_num The table number for the row to check
	 * @param int $row_id The primary key value for the row to check.
	 * @param array $options Options include:
	 *		transaction = current transaction object. [Default is null]
	 * @return int An access value 
	 */
	public static function accessForRow($t_user, $table_num, $row_id, ?array $options=null) {
		if(caDontEnforceACLForAdministrators($t_user)) { return __CA_ACL_EDIT_DELETE_ACCESS__; }
		$access_values = self::accessForRows($t_user, $table_num, [$row_id], $options);
		
		return (is_array($access_values) && isset($access_values[$row_id])) ? $access_values[$row_id]['access'] ?? __CA_ACL_NO_ACCESS__ : __CA_ACL_NO_ACCESS__;
	}
	# ------------------------------------------------------
	/**
	 * Checks access control list for the specified row and user and returns an access value. Values are:
	 *
	 * __CA_ACL_NO_ACCESS__   (0)
	 * __CA_ACL_READONLY_ACCESS__ (1)
     * __CA_ACL_EDIT_ACCESS__ (2)
     * __CA_ACL_EDIT_DELETE_ACCESS__ (3)
	 *
	 * @param ca_users $t_user A ca_users object
	 * @param int $pn_table_num The table number for the row to check
	 * @param int $pn_row_id The primary key value for the row to check.
	 * @param array $options Options include:
	 *		transaction = current transaction object. [Default is null]
	 * @return int An access value 
	 */
	public static function accessForRows($t_user, $table_num, array $row_ids, ?array $options=null) : ?array {
		if(!sizeof($row_ids)) { return []; }
		if(!caACLIsEnabled(Datamodel::getInstance($table_num, true), [])) { return null; }
		
		if (!is_object($t_user)) { $t_user = new ca_users(); }
		if (caDontEnforceACLForAdministrators($t_user)) { 
			$ret = [];
			foreach($row_ids as $row_id) {
				$ret[$row_id] = [
					'access' => __CA_ACL_EDIT_DELETE_ACCESS__,
					'source' => 'ADMIN'
				]; 
			}
			return $ret;
		}
		$trans = caGetOption('transaction', $options, null);
		$db = $trans ? $trans->getDb() : $t_user->getDb();
		
		$user_id = (int)$t_user->getPrimaryKey();
		$access_values = [];
		
		// try to load ACL for user
		if ($user_id) {
			$qr_res = $db->query("
				SELECT max(access) a, row_id
				FROM ca_acl
				WHERE
					table_num = ? AND row_id IN (?) AND user_id = ?
				GROUP BY 
					row_id
			", (int)$table_num, $row_ids, $user_id);
			
			while($qr_res->nextRow()) {
				if (strlen($access = $qr_res->get('a'))) {
					$access = (int)$access;
					$row_id = $qr_res->get('row_id');
					
					$access_values[$row_id] = ['access' => $access, 'source' => 'USER_EXCEPTION'];
				}
			}

			// user group acls
			$groups = $t_user->getUserGroups();
			if (is_array($groups)) {
				$group_ids = array_keys($groups);
				if (is_array($group_ids) && (sizeof($group_ids) > 0)) {
					$qr_res = $db->query("
						SELECT max(access) a, row_id
						FROM ca_acl
						WHERE
							table_num = ? AND row_id IN (?) AND group_id IN (?)
						GROUP BY row_id
							
					", (int)$table_num, $row_ids, $group_ids);
					
					while($qr_res->nextRow()) {
						if (strlen($vs_access = $qr_res->get('a'))) {
							$access = (int)$vs_access;
							$row_id = $qr_res->get('row_id');
							
							$cur_access = $access_values[$row_id]['access'] ?? null;
							if ($access >= $cur_access) { $cur_access = $access; }
							$access_values[$row_id] = ['access' => $cur_access, 'source' => 'GROUP_EXCEPTION'];
						}
					}
				}
			}
		}
		
		// If no valid exceptions found, get world access for this item
		$qr_res = $db->query("
			SELECT max(access) a, row_id
			FROM ca_acl
			WHERE
				table_num = ? AND row_id IN (?) AND group_id IS NULL AND user_id IS NULL
			GROUP BY row_id
				
		", (int)$table_num, $row_ids);
		while($qr_res->nextRow()) {
			$row_id = $qr_res->get('row_id');
			$cur_access = $access_values[$row_id]['access'] ?? null;
			$access = $qr_res->get('a');
			
			//print "[WORLD] $row_id / $cur_access / $access <br>";
			
			if (strlen($access) && (is_null($cur_access) || ((int)$access >= $cur_access))) {
				$access_values[$row_id] = ['access' => (int)$access, 'source' => 'WORLD'];
			}
		}
		
		// If no valid ACL exists return default from config
		$o_config = Configuration::load();
		$default_item_access_level = (int)$o_config->get('default_item_access_level');
		foreach($row_ids as $row_id) {
			if(!isset($access_values[$row_id]) || is_null($access_values[$row_id])) {
				$access_values[$row_id] = ['access' => $default_item_access_level, 'source' => 'DEFAULT'];
			}
			ca_acl::$s_acl_access_value_cache[$user_id][$table_num][$row_id] = $access_values[$row_id];
		}
		return ca_acl::$s_acl_access_value_cache[$user_id][$table_num];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function getACLValuesForRow($subject, int $row_id) : ?array {
		$db = is_object($subject) ? $subject->getDb() : new Db();
		
		if(!($subject_table_num = Datamodel::getTableNum($subject))) { return null; }
		
		$current_acl = ['group' => [], 'user' => [], 'world' => null];
		$qr_current = $db->query("
				SELECT group_id, user_id, access, notes
				FROM ca_acl
				WHERE
					table_num = ? AND row_id = ?
			", [$subject_table_num, $row_id]);
		
		while($qr_current->nextRow()) {
			$row = $qr_current->getRow();
			if($row['group_id'] > 0) {
				$current_acl['group'][$row['group_id']] = $row['access'];
			}
			if($row['user_id'] > 0) {
				$current_acl['user'][$row['user_id']] = $row['access'];
			}
			if(!$row['group_id'] && !$row['user_id']) {
				$current_acl['world'] = $row['access'];
			}
		}
		return $current_acl;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function copyACL(BaseModel $subject, string $target, int $target_id) {
		$db = $subject->getDb() ?? new Db();
		
		if ($t_target = Datamodel::getInstanceByTableName($target, false)) {
			$subject_table_num = $subject->tableNum();
			$subject_id = $subject->getPrimaryKey();
			$target_table_num = $t_target->tableNum();
			
			$qr = $db->query("
				SELECT group_id, user_id, access, notes
				FROM ca_acl
				WHERE
					table_num = ? AND row_id = ?
			", [$target_table_num, (int)$target_id]);
			$existing_group_ids = $existing_user_ids = [];
			while($qr->nextRow()) {
				$row = $qr->getRow();
				
				if($row['group_id']) {
					$existing_group_ids[$row['group_id']]++;
				}
				if($row['user_id']) {
					$existing_user_ids[$row['user_id']]++;
				}
			}
			
			$qr = $db->query("
				SELECT group_id, user_id, access, notes, inherited_from_table_num, inherited_from_row_id
				FROM ca_acl
				WHERE
					table_num = ? AND row_id = ?
			", [$subject_table_num, (int)$subject_id]);
			
			$acl_data = [];
			while($qr->nextRow()) {
				$row = $qr->getRow();
				if(isset($existing_group_ids[$row['group_id']])) { continue; }
				if(isset($existing_user_ids[$row['user_id']])) { continue; }
				
				$group_id = ((int)$row['group_id'] ?: 'null');
				$user_id = ((int)$row['user_id'] ?: 'null');
				$access = ((int)$row['access'] ?: 0);
				$inherited_from_table_num = ((int)$row['inherited_from_table_num'] ?: 'null');
				$inherited_from_row_id = ((int)$row['inherited_from_row_id'] ?: 'null');
				
				$acl_data[] = "({$group_id}, {$user_id}, {$target_table_num}, {$target_id}, {$access}, '', {$inherited_from_table_num}, {$inherited_from_row_id})";
			}
			
			if(sizeof($acl_data) > 0) {
				return $db->query("
					INSERT INTO ca_acl
					(group_id, user_id, table_num, row_id, access, notes, inherited_from_table_num, inherited_from_row_id)
					VALUES 
					".join(", ", $acl_data)."
				");
			} else {
				return true;
			}
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function getStatisticsForRow($subject, ?int $row_id) : ?array {
		if(!$row_id) { return null; }
		$db = is_object($subject) ? $subject->getDb() : new Db();
		if(!($subject_table_num = is_object($subject) ? $subject->tableNum() : Datamodel::getTableNum($subject))) { return null; }
		
		$subject = is_object($subject) ? $subject : Datamodel::getInstance($subject_table_num, false, $row_id);
		if(!$subject->isLoaded()) { return null; }
		
		$statistics = [
			'subRecordCount' => 0,
			'inheritingSubRecordCount' => 0,
			'inheritingAccessSubRecordCount' => 0,
			'relatedObjectCount' => 0,
			'inheritingRelatedObjectCount' => 0,
			'inheritingAccessRelatedObjectCount' => 0,
			'potentialInheritingRelatedObjectCount' => 0,
			'potentialInheritingAccessRelatedObjectCount' => 0,
		];
		
		// Number of sub-records and inherited entries
		
		if($qr_sub_records = $subject->getHierarchy($row_id, [])) {
			$statistics['subRecordCount'] = $qr_sub_records->numRows()-1;
			$c = $x = 0;
			while($qr_sub_records->nextRow()) {
				if($qr_sub_records->get($subject->primaryKey()) == $row_id) { continue; }
				if((bool)$qr_sub_records->get('acl_inherit_from_parent')) { $c++; }
				if((bool)$qr_sub_records->get('access_inherit_from_parent')) { $x++; }
			}
			$statistics['inheritingSubRecordCount'] = $c;
			$statistics['inheritingAccessSubRecordCount'] = $x;
		}
		
		// Number of related objects and inherited entries
		if($subject->tableName() === 'ca_collections') {
			if($qr_sub_records = $subject->getHierarchy($row_id, ['includeSelf' => true])) {
				while($qr_sub_records->nextRow()) {
					$is_root = ($qr_sub_records->get('collection_id') == $row_id);
					if(!($t_coll = ca_collections::findAsInstance(['collection_id' => $qr_sub_records->get('ca_collections.collection_id')]))) { continue; }
					
					$statistics['relatedObjectCount'] += ($c = $t_coll->getRelatedItems('ca_objects', ['returnAs' => 'count', 'limit' => 50000]));
					
					if($is_root || (bool)$t_coll->get('acl_inherit_from_parent')) {
						$statistics['inheritingRelatedObjectCount'] += $t_coll->getRelatedItems('ca_objects', ['returnAs' => 'count', 'limit' => 50000, 'criteria' => ['ca_objects.acl_inherit_from_ca_collections']]);
						$statistics['potentialInheritingRelatedObjectCount'] += $c;
					}
					if($is_root || (bool)$t_coll->get('access_inherit_from_parent')) {
						$statistics['inheritingAccessRelatedObjectCount'] += $t_coll->getRelatedItems('ca_objects', ['returnAs' => 'count', 'limit' => 50000, 'criteria' => ['ca_objects.access_inherit_from_parent']]);
						$statistics['potentialInheritingAccessRelatedObjectCount'] += $c;
					}
				}
			}
		}
		return $statistics;
	}
	# --------------------------------------------------------------------------------------------		
	# ACL world access
	# --------------------------------------------------------------------------------------------		
	/**
	 * 
	 */
	public static function setACLWorldAccess($subject, $world_access, ?array $options=null) {
		if (!($id = (int)$subject->getPrimaryKey())) { return null; }
		
		$table_num = $subject->tableNum();
		
		$t_acl = new ca_acl();	
		$t_acl->setTransaction($subject->getTransaction());
		
		$t_acl->load(['group_id' => null, 'user_id' => null, 'table_num' => $table_num, 'row_id' => $id]);		// try to load existing record
		
		$t_acl->set('table_num', $table_num);
		$t_acl->set('row_id', $id);
		$t_acl->set('user_id', null);
		$t_acl->set('group_id', null);
		$t_acl->set('access', $world_access);
		
		if ($t_acl->getPrimaryKey()) {
			$t_acl->update();
		} else {
			$t_acl->insert();
		}
		if ($t_acl->numErrors()) {
			$subject->errors = $t_acl->errors;
			return false;
		}
		
		return true;
	}
	# --------------------------------------------------------------------------------------------		
	/**
	 * 
	 */
	public static function setACLWorldAccessForRows($table, array $row_ids, int $world_access, ?array $options=null) {
		if(!($subject = Datamodel::getInstance($table))) {
			print caPrintStackTrace();
			throw new ApplicationException(_t('Invalid table: %1', $table));
		}
		$table_num = (int)$subject->tableNum();
		
		$row_ids = array_filter(array_map(function($v) { return (int)$v; }, $row_ids), function($v) { return $v > 0; });
		
		$trans = caGetOption('transaction', $options, null);
		$db = $trans ? $trans->getDb() : $subject->getDb();
		
		$acc = [];
		while(sizeof($row_ids) > 0) {
			$ids = array_splice($row_ids, 0, 500);
			
			$acc = array_map(function($id) use ($table_num, $world_access) {
				$id = (int)$id;
				return "(null, null, {$table_num}, {$id}, {$world_access}, '')";
			}, $ids);
			
			if(sizeof($acc) > 0) {
				$db->query("DELETE FROM ca_acl WHERE group_id IS NULL AND user_id IS NULL AND table_num = ? AND row_id IN (?)", [$table_num, $ids]);
				if(!$db->query("INSERT INTO ca_acl (group_id, user_id, table_num, row_id, access, notes) VALUES ".join(', ', $acc))) {
					return false;
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Remove duplicate ACL entries, leaving the entry granting the most access. The inheritance process
	 * can create duplicate entries when combined with user-specific entries. 
	 *
	 * @param Db $db 
	 *
	 * @return bool
	 */
	public static function setGlobalEntries(string $subject, Db $db) : bool {
		if(!($subject = is_object($subject) ? $subject : Datamodel::getInstance($subject, true))) { return null; }
		
		$o_config = Configuration::load();
		$default_item_access_level = (int)$o_config->get('default_item_access_level');
		
		$subject_table = $subject->tableName();
		$subject_table_num = $subject->tableNum();
		$subject_pk = $subject->primaryKey();
		
		$new_entries = [];
		if($qr = $db->query("
			SELECT t.{$subject_pk}, a.*
			FROM {$subject_table} t
			LEFT JOIN ca_acl AS a ON t.{$subject_pk} = a.row_id AND (a.table_num = {$subject_table_num} OR a.table_num IS NULL) and a.user_id IS NULL and a.group_id IS NULL
		")) {
			while($qr->nextRow()) {
				$row = $qr->getRow();
				
				if(!($row['acl_id'] ?? null)) {
					$new_entries[] = "(NULL, NULL, {$subject_table_num}, {$row[$subject_pk]}, {$default_item_access_level}, '', NULL, NULL)";
				}
			}
			if(sizeof($new_entries)) {
				$db->query("INSERT IGNORE INTO ca_acl 
					(group_id, user_id, table_num, row_id, access, notes, inherited_from_table_num, inherited_from_row_id)
					VALUES
					".join(",", $new_entries)."
				");
			}
		}
		return true;
	}
	# ------------------------------------------------------
	# ACL entry management
	# ------------------------------------------------------
	/**
	 * Delete all existng ACL settings inherited from specified row
	 *
	 * @param BaseModel $subject 
	 *
	 * @return ?bool True on success, false on error, null if table does not exist 
	 */
	public static function removeACLValuesInheritedFromRow(BaseModel $subject) : ?bool {
		$db = $subject->getDb();
		
		$subject_table_num = $subject->tableNum();
		$subject_id = $subject->getPrimaryKey();
		
		return (bool)$db->query(
			"DELETE FROM ca_acl WHERE inherited_from_table_num = ? AND inherited_from_row_id = ? AND table_num = ?", 
				[$subject_table_num, $subject_id, $subject_table_num]);
	}
	# ------------------------------------------------------
	/**
	 * Delete all existng ACL settings on specified row
	 *
	 * @param BaseModel $subject 
	 * @param array $options Options include:
	 *		onlyInherited = Only remove inherited entries. [Default is false]
	 *
	 * @return ?bool True on success, false on error, null if table does not exist 
	 */
	public static function removeACLValuesForRow(BaseModel $subject, ?array $options=null) : ?bool {
		$only_inherited = caGetOption('onlyInherited', $options, false);
		
		$db = $subject->getDb();
		
		$subject_table_num = $subject->tableNum();
		$subject_id = $subject->getPrimaryKey();
		
		if($only_inherited) {
			return (bool)$db->query(
			"DELETE FROM ca_acl WHERE table_num = ? AND row_id = ? AND inherited_from_table_num IS NOT NULL", 
				[$subject_table_num, $subject_id]);
		}
		return (bool)$db->query(
			"DELETE FROM ca_acl WHERE table_num = ? AND row_id = ?", 
				[$subject_table_num, $subject_id]);

	}
	# ------------------------------------------------------
	/**
	 * Remove duplicate ACL entries, leaving the entry granting the most access. The inheritance process
	 * can create duplicate entries when combined with user-specific entries. 
	 *
	 * @param Db $db 
	 *
	 * @return bool
	 */
	public static function removeRedundantACLEntries(Db $db) : bool {
		// 
		if(!($temp_table = ca_acl::_createTempTableForRedundantACL($db))) {
			throw new ApplicationException(_t('Cannot create temporary table for removal of redundant ACL entries'));
		}
		
		// Clean up users
		$db->query("
			INSERT IGNORE INTO {$temp_table} 
			SELECT table_num, row_id, user_id, NULL, max(access) FROM ca_acl 
			WHERE
				user_id IS NOT NULL
			GROUP BY table_num, row_id, user_id 
			HAVING (count(*) > 1)
		");
		
		$db->query("
			DELETE a.* FROM ca_acl a 
			INNER JOIN {$temp_table} AS t ON a.table_num = t.table_num AND a.row_id = t.row_id AND a.user_id = t.user_id
			WHERE a.access < t.access AND a.user_id IS NOT NULL
		");
		
		// Clean up groups
		$db->query("
			INSERT IGNORE INTO {$temp_table} 
			SELECT table_num, row_id, NULL, group_id, max(access) FROM ca_acl 
			WHERE
				group_id IS NOT NULL
			GROUP BY table_num, row_id, group_id 
			HAVING (count(*) > 1)
		");
		
		$db->query("
			DELETE a.* FROM ca_acl a 
			INNER JOIN {$temp_table} AS t ON a.table_num = t.table_num AND a.row_id = t.row_id AND a.group_id = t.group_id
			WHERE a.access < t.access AND a.group_id IS NOT NULL
		");
		
		// Clean up world
		$db->query("
			INSERT IGNORE INTO {$temp_table} 
			SELECT table_num, row_id, NULL, NULL, max(access) FROM ca_acl 
			WHERE
				user_id IS NULL AND group_id IS NULL
			GROUP BY table_num, row_id, user_id, group_id 
			HAVING (count(*) > 1)
		");
		
		$db->query("
			DELETE a.* FROM ca_acl a 
			INNER JOIN {$temp_table} AS t ON a.table_num = t.table_num AND a.row_id = t.row_id
			WHERE a.access < t.access AND a.user_id IS NULL AND a.group_id IS NULL
		");
		ca_acl::_dropTempTable($db, $temp_table);
		
		return true;
	}
	# ------------------------------------------------------
	# ACL inheritance
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function setACLInheritanceSettingForAllChildRows($subject, int $row_id, bool $set_all) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		
		if(!($subject = is_object($subject) ? $subject : Datamodel::getInstance($subject, true, $row_id))) { return null; }
		$subject_table = $subject->tableName();
		$subject_pk = $subject->primaryKey();
		
		$db = is_object($subject) ? $subject->getDb() : new Db();
		
		$ret = true;
		$ids_to_set = [];
		if(sizeof(($child_ids = $subject->getHierarchy($row_id, ['idsOnly' => true])) ?? [])) {
			if($qr_res = caMakeSearchResult($subject_table, $child_ids)) {
				while($qr_res->nextHit()) {
					$cv = $qr_res->get('acl_inherit_from_parent');
					if($cv && $set_all) { continue; }
					if(!$cv && !$set_all) { continue; }
					
					// @TODO: do we only allow setting of ACL for rows that the user has write access to?
					// Right now we assume that if the user has the can_change_acl_* priv they can set it on anything
					$ids_to_set[] = $qr_res->getPrimaryKey();
				}
			}
		}
		
		// Apply changes to ids. There can potentially be *a lot* of rows to update. We try to do it as quickly
		// as possible by directly executing SQL UPDATE queries on batches of 500 records.
		$o_tq = new TaskQueue(['transaction' => $subject->getTransaction()]);
		if(sizeof($ids_to_set)) {
			while(sizeof($ids_to_set)) {
				$ids = array_splice($ids_to_set, 0, 500);
				
				// @TODO: for performance reasons we don't attempt to log changes to affected rows. Rather we just apply
				// the change directly in the database. This means these changes don't appear in the log and won't
				// be transmitted to replicated systems. Might be a problem for someone someday, so we should consider ways
				// to enable logging (via background processing?)
				if(!$db->query("UPDATE {$subject_table} SET acl_inherit_from_parent = ? WHERE {$subject_pk} IN (?)", [$set_all ? 1 : 0, $ids])) {
					$ret = false;
				} else {
					$k = "{$subject_table}::".$subject->getPrimaryKey();
					
					$log_entries = [];
					foreach($ids as $id) {
						$log_entries[] = [
							'datetime' => time(),
							'table' => $subject_table,
							'row_id' => $id,
							'user_id' => $AUTH_CURRENT_USER_ID,
							'type' => 'U',
							'snapshot' => [
								'acl_inherit_from_parent' => $set_all ? 1 : 0
							]
						];
					}
					if (!$o_tq->addTask(
						'bulkLogger',
						[
							"logEntries" => $log_entries,
						],
						["priority" => 50, "entity_key" => $k, "row_key" => $k, 'user_id' => $AUTH_CURRENT_USER_ID]))
					{
						// Error adding queue item
						throw new ApplicationException(_t('Could not add logging tasks to queue'));
					}
				}
			}
		}
	
		return $ret;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function setACLInheritanceSettingForRelatedObjects($subject, int $row_id, bool $set_all) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		
		if(!($subject = is_object($subject) ? $subject : Datamodel::getInstance($subject, true, $row_id))) { return null; }
		if(($subject_table = $subject->tableName()) !== 'ca_collections') { return null; }
		
		$db = is_object($subject) ? $subject->getDb() : new Db();
		
		$ret = true;
		if($qr_sub_records = $subject->getHierarchy($row_id, ['includeSelf' => true])) {
			$ids_to_set = [];
			while($qr_sub_records->nextRow()) {
				if(!($t_coll = ca_collections::findAsInstance(['collection_id' => $qr_sub_records->get('ca_collections.collection_id')]))) { continue; }
				if($qr_res = $t_coll->getRelatedItems('ca_objects', ['returnAs' => 'searchResult', 'limit' => 50000])) {
					
					while($qr_res->nextHit()) {
						$cv = $qr_res->get('acl_inherit_from_ca_collections');
						if($cv && $set_all) { continue; }
						if(!$cv && !$set_all) { continue; }
						
						// @TODO: do we only allow setting of ACL for rows that the user has write access to?
						// Right now we assume that if the user has the can_change_acl_* priv they can set it on anything
						$ids_to_set[] = $qr_res->getPrimaryKey();
					}
				}
			}
			
			// Apply changes to ids. There can potentially be *a lot* of rows to update. We try to do it as quickly
			// as possible by directly executing SQL UPDATE queries on batches of 500 records.
			// @TODO: this may well still be too slow for very large collections; we might consider investigating other ways
			// to apply these changes, esp when the batch size is very large. Maybe background processing?
			if(sizeof($ids_to_set)) {
				$o_tq = new TaskQueue(['transaction' => $subject->getTransaction()]);
				while(sizeof($ids_to_set)) {
					$ids = array_splice($ids_to_set, 0, 500);
					
					if(!$db->query("UPDATE ca_objects SET acl_inherit_from_ca_collections = ? WHERE object_id IN (?)", [$set_all ? 1 : 0, $ids])) {
						$ret = false;
					} else {
						$k = "ca_objects::".$subject->getPrimaryKey();
						
						$log_entries = [];
						foreach($ids as $id) {
							$log_entries[] = [
								'datetime' => time(),
								'table' => 'ca_objects',
								'row_id' => $id,
								'user_id' => $AUTH_CURRENT_USER_ID,
								'type' => 'U',
								'snapshot' => [
									'acl_inherit_from_ca_collections' => $set_all ? 1 : 0
								]
							];
						}
						if (!$o_tq->addTask(
							'bulkLogger',
							[
								"logEntries" => $log_entries,
							],
							["priority" => 50, "entity_key" => $k, "row_key" => $k, 'user_id' => $AUTH_CURRENT_USER_ID]))
						{
							// Error adding queue item
							throw new ApplicationException(_t('Could not add logging tasks to queue'));
						}
					}
				}
				SearchResult::clearCaches();
			}
		}
		return $ret;
	}
	
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function applyACLInheritanceToChildrenFromRow($subject) : bool {
		if (!$subject->isHierarchical()) { return false; }
		
		$subject_id = (int)$subject->getPrimaryKey();
		if (!$subject_id) { return false; }
		
		$db = is_object($subject) ? $subject->getDb() : new Db();
		$ids = ca_acl::getACLInheritanceHierarchy($subject);
	
		if(!sizeof($ids)) { return false; }
		
		$subject_pk = (string)$subject->primaryKey();
		$subject_table = (string)$subject->tableName();
		$subject_table_num = (int)$subject->tableNum();
		
		$qr = caMakeSearchResult($subject_table, $ids);
		$inherit_from_parent_flag_exists = $subject->hasField('acl_inherit_from_parent');
		
		// Get current ACL values for this row
		$current_acl = ca_acl::getACLValuesForRow($subject_table_num, $subject_id);
		
		// Delete existing inherited rows
		ca_acl::removeACLValuesInheritedFromRow($subject, ['onlyInherited' => true]);
			
		// Apply rows to all
		while($qr->nextHit()) {
			$id = $qr->get("{$subject_table}.{$subject_pk}");
			if($inherit_from_parent_flag_exists && !$qr->get("{$subject_table}.acl_inherit_from_parent")) { continue; }
			
			$row_acl = ca_acl::getACLValuesForRow($subject_table_num, $id);
			// compute required ACL copies
			$acl_to_copy = $current_acl;
			$acl_to_delete = [];
			foreach($acl_to_copy as $kind => $entries) {
				if($kind === 'world') {
					if(isset($row_acl[$kind]) && ($row_acl[$kind] >= $access)) {
						unset($acl_to_copy[$kind]);
					} else {
						$acl_to_delete[$kind] = $row_acl[$kind];
					}
				} else {
					foreach($entries as $entry_id => $access) {
						if(isset($row_acl[$kind][$entry_id])) {
							if($row_acl[$kind][$entry_id] >= $access) {
								unset($acl_to_copy[$kind][$entry_id]);
							} else {
								$acl_to_delete[$kind][$entry_id] = $row_acl[$kind][$entry_id];
							}
						} 
					}
				}
			}
			
			// Remove existing ACL that will be replaced
			foreach($acl_to_delete as $kind => $entries) {
				$deletes = [];
				switch($kind) {
					case 'world':
						if(strlen($entries)) {
							$deletes[] = "((user_id IS NULL) AND (group_id IS NULL) AND (access = {$entries}))";
						}
						break;
					case 'user':
						foreach($entries as $user_id => $access) {
							$deletes[] = "((user_id = {$user_id}) AND (group_id IS NULL) AND (access = {$access}))";
						}
						break;
					case 'group':
						foreach($entries as $group_id => $access) {
							$deletes[] = "((user_id IS NULL) AND (group_id = {$group_id}) AND (access = {$access}))";
						}
						break;
				}
				
				if(sizeof($deletes) > 0) {
					$qr_delete = $db->query("
						DELETE FROM ca_acl
						WHERE
						".join(" OR ", $deletes), []);
				}
			}
			
			
			// Apply inherited ACL
			foreach($acl_to_copy as $kind => $entries) {
				$inserts = [];
				switch($kind) {
					case 'world':
						if(strlen($entries)) {
							$inserts[] = "(NULL,NULL,{$subject_table_num},{$id},{$entries},'',{$subject_table_num},{$subject_id})";
						}
						break;
					case 'user':
						foreach($entries as $user_id => $access) {
							$inserts[] = "(NULL,{$user_id},{$subject_table_num},{$id},{$access},'',{$subject_table_num},{$subject_id})";
						}
						break;
					case 'group':
						foreach($entries as $group_id => $access) {
							$inserts[] = "({$group_id},NULL,{$subject_table_num},{$id},{$access},'',{$subject_table_num},{$subject_id})";
						}
						break;
				}
				
				if(sizeof($inserts) > 0) {
					$qr_clone = $db->query("
						INSERT IGNORE INTO ca_acl
						(group_id, user_id, table_num, row_id, access, notes, inherited_from_table_num, inherited_from_row_id)
						VALUES
						".join(",", $inserts), []);
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function updateACLInheritanceForRow($subject) : bool {
		$subject_id = (int)$subject->getPrimaryKey();
		if (!$subject_id) { return false; }
		
		$object_collections_hier_enabled = $subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled');
		$object_collections_rel_types = caGetObjectCollectionHierarchyRelationshipTypes();
		
		$subject_pk = (string)$subject->primaryKey();
		$subject_table = (string)$subject->tableName();
		$subject_table_num = (int)$subject->tableNum();
		
		if($parent_id = $subject->get("parent_id")) {
			if($t_parent = $subject_table::findAsInstance([$subject_pk => $parent_id])) {
				ca_acl::applyACLInheritanceToChildrenFromRow($t_parent);
			}
		}
		
		$ids = ca_acl::getACLInheritanceHierarchy($subject);
		if(!is_array($ids) || !sizeof($ids)) { return true; }
		
		$colls = null;
		if($object_collections_hier_enabled && ($subject_table == 'ca_objects')) {
			$colls = $subject->getRelatedItems('ca_collections', ['restrictToRelationshipTypes' => $object_collections_rel_types, 'returnAs' => 'modelInstances']);
		}
		
		$qr = caMakeSearchResult($subject_table, $ids);
		
		// Update children in current hierarchy
		while($qr->nextHit()) {
			$t_child = $qr->getInstance();
			ca_acl::applyACLInheritanceToChildrenFromRow($t_child);
		}
		
		// Update children on opposite end of collection-object hierarchy gap
		$qr->seek(0);
		while($qr->nextHit()) {
			$t_child = $qr->getInstance();
			
			if($object_collections_hier_enabled) {
				switch($subject_table) {
					case 'ca_collections':
						ca_acl::applyACLInheritanceToRelatedFromRow($t_child, 'ca_objects', ['restrictToRelationshipTypes' => $object_collections_rel_types]);
						break;
					case 'ca_objects':
						if(is_array($colls)) {
							foreach($colls as $coll) {
								ca_acl::applyACLInheritanceToRelatedFromRow($coll, 'ca_objects', ['limitToIDs' => [$subject_id], 'restrictToRelationshipTypes' => $object_collections_rel_types]);
							}
						}
						break;
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Apply ACL entries from subject to related records in target table. 
	 *
	 * @param BaseModel $subject
	 * @param string $target
	 * @param array $options Options include:
	 *		restrictToRelationshipTypes = Restrict related rows to those with listed relationship types. If omitted all related rows are processed. [Default is null]
	 *		limitToIDs = List of target primary key values. ACL inheritance will only be applied to rows in the list. All other rows will be skipped. [Default is null] 
	 */
	public static function applyACLInheritanceToRelatedFromRow(BaseModel $subject, string $target, ?array $options=null) {
		$db = $subject->getDb();
		
		if ($t_link = $subject->getRelationshipInstance($target)) {
			if ($t_rel_item = Datamodel::getInstanceByTableName($target, false)) {
				$restrict_to_relationship_types = caGetOption('restrictToRelationshipTypes', $options, null);
				$limit_to_ids = caGetOption('limitToIDs', $options, null);
			
				$path = array_keys(Datamodel::getPath($cur_table = $subject->tableName(), $target));
				$table = array_shift($path);
				
				if (!$t_rel_item->hasField("acl_inherit_from_{$table}")) { return false; }
				
				$target_pk = (string)$t_rel_item->primaryKey();
				$target_table_num = (int)$t_rel_item->tableNum();
				
				$subject_pk = (string)$subject->primaryKey();
				$subject_table = (string)$subject->tableName();
				$subject_table_num = (int)$subject->tableNum();
				$subject_id = (int)$subject->getPrimaryKey();
				
				$params = [$subject_id];
				$relationship_type_sql = null;
				foreach($path as $join_table) {
					if(!($t_rel = Datamodel::getInstance($join_table, true))) { throw new ApplicationException(_t('Invalid join table: %1', $join_table)); }
					
					$rel_info = Datamodel::getRelationships($cur_table, $join_table);
					$joins[] = 'INNER JOIN '.$join_table.' ON '.$cur_table.'.'.$rel_info[$cur_table][$join_table][0][0].' = '.$join_table.'.'.$rel_info[$cur_table][$join_table][0][1]."\n";
					$cur_table = $join_table;
					
					if(is_array($restrict_to_relationship_types) && sizeof($restrict_to_relationship_types) && $t_rel->isRelationship() && $t_rel->hasField('type_id')) {
						$restrict_to_relationship_type_ids = caMakeRelationshipTypeIDList($join_table, $restrict_to_relationship_types);
						if(is_array($restrict_to_relationship_type_ids) && sizeof($restrict_to_relationship_type_ids)) {
							$relationship_type_sql = " AND ({$join_table}.type_id IN (?))";
							$params[] = $restrict_to_relationship_type_ids;
						}
					}
				}
				
				// Delete existing inherited rows
				$qr_del = $db->query("DELETE FROM ca_acl WHERE inherited_from_table_num = ? AND inherited_from_row_id = ? AND table_num = ?", [(int)$subject_table_num, (int)$subject_id, (int)$target_table_num]);
				
				$qr_res = $db->query("
					SELECT {$target}.{$target_pk}
					FROM {$subject_table}
					".join("\n", $joins)."
					WHERE 
						({$subject_table}.{$subject_pk} = ?) AND 
						({$target}.acl_inherit_from_{$subject_table} = 1) 
						{$relationship_type_sql}
					", $params);
				while($qr_res->nextRow()) {
					$target_id = $qr_res->get($target_pk);
					if(is_array($limit_to_ids) && sizeof($limit_to_ids) && !in_array($target_id, $limit_to_ids)) { continue; }
					
					// Remove existing non-inherited ACL entries that conflict with inherited entries
					$qr_clean = $db->query("
						SELECT group_id, user_id, access, notes
						FROM ca_acl
						WHERE
							table_num = ? AND row_id = ? 
					", (int)$subject_table_num, (int)$subject_id);
					while($qr_clean->nextRow()) {
						$user_id = $qr_clean->get('user_id');
						$group_id = $qr_clean->get('group_id');
						
						if($user_id) {
							$qr_del = $db->query("DELETE FROM ca_acl WHERE table_num = ? AND row_id = ? AND user_id = ?", [(int)$target_table_num, (int)$target_id, $user_id]);
						} elseif($group_id) {
							$qr_del = $db->query("DELETE FROM ca_acl WHERE table_num = ? AND row_id = ? AND group_id = ?", [(int)$target_table_num, (int)$target_id, $group_id]);
						}
					}
					$qr_clone = $db->query("
						INSERT IGNORE INTO ca_acl
						(group_id, user_id, table_num, row_id, access, notes, inherited_from_table_num, inherited_from_row_id)
						SELECT group_id, user_id, {$target_table_num}, {$target_id}, access, notes, {$subject_table_num}, {$subject_id}
						FROM ca_acl
						WHERE
							table_num = ? AND row_id = ? 
					", (int)$subject_table_num, (int)$subject_id);
				}
					
				ca_acl::removeRedundantACLEntries($db);
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Set ACL inheritance for specific row, subject to acl_inherit_from_<table> values
	 */
	public static function applyACLInheritanceToRelatedRowFromRow($subject, $subject_id, $target, $target_id, $options=null) {
		$db = $subject->getDb() ?? new Db();
		
		if ($t_link = $subject->getRelationshipInstance($target)) {
			if ($t_rel_item = Datamodel::getInstanceByTableName($target, false)) {
				$target_pk = (string)$t_rel_item->primaryKey();
				$target_table_num = (int)$t_rel_item->tableNum();
				
				$subject_pk = (string)$subject->primaryKey();
				$subject_table = (string)$subject->tableName();
				$subject_table_num = (int)$subject->tableNum();
				$target_id = (int)$target_id;
				$subject_id = (int)$subject_id;
				
				if (!isset($options['deleteACLOnly']) || !$options['deleteACLOnly']) {
					if (!$t_rel_item->hasField("acl_inherit_from_{$subject_table}")) { return false; }
				}
				
				// Delete existing inherited rows
				$db->query("DELETE FROM ca_acl WHERE inherited_from_table_num = ? AND inherited_from_row_id = ? AND table_num = ? AND row_id = ?", array((int)$subject_table_num, (int)$subject_id, (int)$target_table_num, (int)$target_id));
				
				if (!isset($options['deleteACLOnly']) || !$options['deleteACLOnly']) {
					// only inherit if inherit_from field is set. $target and $target_pk have been verified at this pont
					$qr_inherit = $db->query("SELECT acl_inherit_from_{$subject_table} FROM {$target} WHERE {$target_pk} = ?", $target_id);
					if(!$qr_inherit->nextRow()) { return false; }
					if(!$qr_inherit->get("acl_inherit_from_{$subject_table}")) { return false; }

					// insert inherited ACLs
					$db->query("
						INSERT IGNORE INTO ca_acl
						(group_id, user_id, table_num, row_id, access, notes, inherited_from_table_num, inherited_from_row_id)
						SELECT group_id, user_id, {$target_table_num}, {$target_id}, access, notes, {$subject_table_num}, {$subject_id}
						FROM ca_acl
						WHERE
							table_num = ? AND row_id = ? 
					", (int)$subject_table_num, (int)$subject_id);
					
					ca_acl::removeRedundantACLEntries($db);
				}
		
			}
		}
		return true;
	}
	# ------------------------------------------------------
	# "Access" inheritance
	# ------------------------------------------------------
	/**
	 *	Set access_inherit_from_parent value for children of row
	 */
	public static function setAccessInheritanceSettingForChildrenFromRow($subject, int $row_id, bool $set_all, ?array $options=null) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		
		if(!($subject = is_object($subject) ? $subject : Datamodel::getInstance($subject, true, $row_id))) { return null; }
		if(!$subject->hasField('access_inherit_from_parent')) { return null; }
		
		$db = $subject->getDb() ?? new Db();
		
		$subject_pk = $subject->primaryKey();
		$subject_table = $subject->tableName();
		
		$ret = true;
		$inherit = (int)$subject->get('access_inherit_from_parent');
		$access = (int)$subject->get('access');
		
		$set_inherit = $set_all ? 1 : $inherit;
		
		$snapshot = [
			'access_inherit_from_parent' => $set_inherit
		];
		if($set_inherit) { $snapshot['access'] = $access; }
		$sql = $set_inherit ? 
			"UPDATE {$subject_table} SET access_inherit_from_parent = ?, access = ? WHERE {$subject_pk} IN (?)" 
			:
			"UPDATE {$subject_table} SET access_inherit_from_parent = ? WHERE {$subject_pk} IN (?)"
		;
		$params = $set_inherit ? [$set_inherit, $access] : [$set_inherit];
		if(is_array($ids_to_set = $subject->getHierarchy($row_id, ['includeSelf' => true, 'idsOnly' => true])) && sizeof($ids_to_set)) {
			while(sizeof($ids_to_set)) {
				$ids = array_splice($ids_to_set, 0, 500);
				
				if(!$db->query($sql, array_merge($params, [$ids]))) {
					$ret = false;
				} else {
					$o_tq = new TaskQueue(['transaction' => $subject->getTransaction()]);
					$k = "{$subject_table}::".$subject->getPrimaryKey();
					
					$log_entries = [];
					foreach($ids as $id) {
						$log_entries[] = [
							'datetime' => time(),
							'table' => $subject_table,
							'row_id' => $id,
							'user_id' => $AUTH_CURRENT_USER_ID,
							'type' => 'U',
							'snapshot' => $snapshot
						];
					}
					if (!$o_tq->addTask(
						'bulkLogger',
						[
							"logEntries" => $log_entries,
						],
						["priority" => 50, "entity_key" => $k, "row_key" => $k, 'user_id' => $AUTH_CURRENT_USER_ID]))
					{
						// Error adding queue item
						throw new ApplicationException(_t('Could not add logging tasks to queue'));
					}
				}
			}
		}
		SearchResult::clearCaches();
		
		return $ret;
	}
	# ------------------------------------------------------
	/**
	 *	Set access_inherit_from_parent value for objects related to a collection.
	 */
	public static function setAccessInheritanceSettingToRelatedObjectsFromCollection($subject, int $row_id, bool $set_all, ?array $options=null) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		
		if(!($subject = is_object($subject) ? $subject : Datamodel::getInstance($subject, true, $row_id))) { return null; }
		if(($subject_table = $subject->tableName()) !== 'ca_collections') { return null; }
		if(!$subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled')) { return null; }
		if(!is_array($rel_types = caGetObjectCollectionHierarchyRelationshipTypes()) || !sizeof($rel_types)) { return null; }
		
		$db = $subject->getDb() ?? new Db();
		
		$access = (int)$subject->get('access_inherit_from_parent');
		$ret = true;
		if($qr_sub_records = $subject->getHierarchy($row_id, ['includeSelf' => true])) {
			$ids_to_set = [];
			while($qr_sub_records->nextRow()) {
				if(($qr_sub_records->get('collection_id') != $row_id) && !$qr_sub_records->get('access_inherit_from_parent')) { continue; }
				if(!($t_coll = ca_collections::findAsInstance(['collection_id' => $qr_sub_records->get('ca_collections.collection_id')]))) { continue; }
			
				if ($t_link = $t_coll->getRelationshipInstance('ca_objects')) {
					if ($t_rel_item = Datamodel::getInstanceByTableName('ca_objects', false)) {
						if($qr_res = $t_coll->getRelatedItems('ca_objects', ['restrictToRelationshipTypes' => $rel_types, 'returnAs' => 'searchResult', 'limit' => 50000])) {
							$ids_to_set = [];
							while($qr_res->nextHit()) {						
								$cv = $qr_res->get('access_inherit_from_parent');
								if($cv && $set_all) { continue; }
								if(!$cv && !$set_all) { continue; }
								
								// @TODO: do we only allow setting of ACL for rows that the user has write access to?
								// Right now we assume that if the user has the can_change_acl_* priv they can set it on anything
								$ids_to_set[] = $qr_res->getPrimaryKey();
							}
							
							if(sizeof($ids_to_set)) {
								while(sizeof($ids_to_set)) {
									$ids = array_splice($ids_to_set, 0, 500);
									
									if(!$db->query("UPDATE ca_objects SET access_inherit_from_parent = ? WHERE object_id IN (?)", [$set_all ? 1 : $access, $ids])) {
										$ret = false;
									} else {
										$o_tq = new TaskQueue(['transaction' => $t_coll->getTransaction()]);
										$k = 'ca_collections::'.$t_coll->getPrimaryKey();
										
										$log_entries = [];
										foreach($ids as $id) {
											$log_entries[] = [
												'datetime' => time(),
												'table' => 'ca_objects',
												'row_id' => $id,
												'user_id' => $AUTH_CURRENT_USER_ID,
												'type' => 'U',
												'snapshot' => [
													'access_inherit_from_parent' => $set_all ? 1 : 0
												]
											];
										}
										if (!$o_tq->addTask(
											'bulkLogger',
											[
												"logEntries" => $log_entries,
											],
											["priority" => 50, "entity_key" => $k, "row_key" => $k, 'user_id' => $AUTH_CURRENT_USER_ID]))
										{
											// Error adding queue item
											throw new ApplicationException(_t('Could not add logging tasks to queue'));
										}
									}
								}
							}
						}
					}
				}
			}
		}
		SearchResult::clearCaches();
		
		return $ret;
	}
	# ------------------------------------------------------
	/**
	 * Apply parent's access inheritance to subject. Supports inheritance of access for objects
	 * from parent collection when object-collection hierarchies are enabled.
	 *
	 * @param BaseModel $t_subject
	 *
	 * @return ?bool False or null on failure; true on success
	 */
	public static function applyAccessInheritance($subject) : ?bool {
		if(!$subject->getAppConfig()->get($subject->tableName().'_allow_access_inheritance')) { return null; }
		if(!$subject->hasField('access_inherit_from_parent')) { return null; }
		if(!$subject->get('access_inherit_from_parent')) { return false; }
		
		$object_collections_hier_enabled = $subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled');
		$object_collections_rel_types = caGetObjectCollectionHierarchyRelationshipTypes();
		
		$subject_table = $subject->tableName();
		if($parent_id = $subject->get('parent_id')) {
			$parent = $subject_table::find($parent_id, ['returnAs' => 'arrays']);
			if(is_array($parent) && (sizeof($parent) > 0)) {
				$parent = array_shift($parent);
			} else {
				$parent = ['access' => 0];
			}
			$subject->set('access', $parent['access']);
			return (bool)$subject->update(['skipACLInheritance' => true]);
		} elseif(($subject_table == 'ca_objects') && $object_collections_hier_enabled && is_array($object_collections_rel_types) && sizeof($object_collections_rel_types)) {
			if($coll = $subject->getRelatedItems('ca_collections', ['restrictToRelationshipTypes' => $object_collections_rel_types, 'returnAs' => 'firstModelInstance'])) {
				$subject->set('access', $coll->get('access'));
				return (bool)$subject->update(['skipACLInheritance' => true]);
			}
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Set access value for objects related to a collection, subject to collection and object access inheritance settings
	 *
	 * @param BaseModel $subject A ca_collections model instance
	 * @param array $options No options currently supported
	 *
	 * @return ?bool
	 */
	public static function applyAccessInheritanceToRelatedObjectsFromCollection(BaseModel $subject, ?array $options=null) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		
		if(!$subject->getAppConfig()->get('ca_collections_allow_access_inheritance')) { return null; }
		if(!$subject->getAppConfig()->get('ca_objects_allow_access_inheritance')) { return null; }
		if(!$subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled')) { return null; }
		if(!is_array($rel_types = caGetObjectCollectionHierarchyRelationshipTypes()) || !sizeof($rel_types)) { return null; }
		if(($subject_table = $subject->tableName()) !== 'ca_collections') { return false; }
		$db = $subject->getDb() ?? new Db();
		
		$access = (int)$subject->get('access');
		$subject_id = $subject->getPrimaryKey();
		
		// get sub-collections
		
		if ($t_rel_item = Datamodel::getInstanceByTableName('ca_objects', false)) {
			$collection_ids = ca_acl::getAccessInheritanceHierarchy($subject);

			if(is_array($ids = $subject->getRelatedItems('ca_objects', ['restrictToRelationshipTypes' => $rel_types, 'row_ids' => $collection_ids, 'returnAs' => 'ids', 'limit' => 50000])) && sizeof($ids)) {
				$db->query("UPDATE ca_objects SET access = ? WHERE object_id IN (?) AND access_inherit_from_parent = 1", [$access, $ids]);
				
				$o_tq = new TaskQueue(['transaction' => $subject->getTransaction()]);
				$k = 'ca_collections::'.$subject->getPrimaryKey();
				
				$log_entries = [];
				foreach($ids as $id) {
					$log_entries[] = [
						'datetime' => time(),
						'table' => 'ca_objects',
						'row_id' => $id,
						'user_id' => $AUTH_CURRENT_USER_ID,
						'type' => 'U',
						'snapshot' => [
							'access' => $access
						]
					];
				}
				if (!$o_tq->addTask(
					'bulkLogger',
					[
						"logEntries" => $log_entries,
					],
					["priority" => 50, "entity_key" => $k, "row_key" => $k, 'user_id' => $AUTH_CURRENT_USER_ID]))
				{
					// Error adding queue item
					throw new ApplicationException(_t('Could not add logging tasks to queue'));
				}
				
				$map = caGetACLItemLevelMap();
				$acl_access = $map[$access] ?? $subject->getAppConfig()->get('default_item_access_level');
				
				ca_acl::setACLWorldAccessForRows($subject_table, $ids, $acl_access);
			} 
		}
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Set access values set on subject on child records with access inheritance set
	 *
	 * @param BaseModel $subject
	 * @param array $options No options currently supported
	 *
	 * @return bool
	 */
	public static function applyAccessInheritanceToChildrenFromRow(BaseModel $subject, ?array $options=null) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		if(!$subject->getAppConfig()->get($subject->tableName().'_allow_access_inheritance')) { return null; }
		if (!$subject->isHierarchical()) { return null; }
		
		$subject_id = (int)$subject->getPrimaryKey();
		if (!$subject_id) { return false; }
		
		$db = is_object($subject) ? $subject->getDb() : new Db();
		$ids = ca_acl::getAccessInheritanceHierarchy($subject);
		if(!sizeof($ids)) { return false; }
		
		$subject_pk = (string)$subject->primaryKey();
		$subject_table = (string)$subject->tableName();
		$subject_table_num = (int)$subject->tableNum();
		
		$qr = caMakeSearchResult($subject_table, $ids);
		$inherit_from_parent_flag_exists = $subject->hasField('access_inherit_from_parent');
		
		// Apply rows to all
		$ids_to_set = [];
		while($qr->nextHit()) {
			$id = $qr->get("{$subject_table}.{$subject_pk}");
			if($inherit_from_parent_flag_exists && !$qr->get("{$subject_table}.access_inherit_from_parent")) { continue; }
			$ids_to_set[] = $id;
		}
		
		$access = $subject->get("{$subject_table}.access");
		
		$o_tq = new TaskQueue(['transaction' => $subject->getTransaction()]);
		if(sizeof($ids_to_set)) {
			while(sizeof($ids_to_set)) {
				$ids = array_splice($ids_to_set, 0, 500);
				
				// @TODO: for performance reasons we don't attempt to log changes to affected rows. Rather we just apply
				// the change directly in the database. This means these changes don't appear in the log and won't
				// be transmitted to replicated systems. Might be a problem for someone someday, so we should consider ways
				// to enable logging (via background processing?)
				if(!$db->query("UPDATE {$subject_table} SET access = ? WHERE {$subject_pk} IN (?) AND access_inherit_from_parent = 1", [$access, $ids])) {
					return false;
				} else {
					$k = "{$subject_table}::".$subject->getPrimaryKey();
					
					$log_entries = [];
					foreach($ids as $id) {
						$log_entries[] = [
							'datetime' => time(),
							'table' => $subject_table,
							'row_id' => $id,
							'user_id' => $AUTH_CURRENT_USER_ID,
							'type' => 'U',
							'snapshot' => [
								'access' => $access
							]
						];
					}
					if (!$o_tq->addTask(
						'bulkLogger',
						[
							"logEntries" => $log_entries,
						],
						["priority" => 50, "entity_key" => $k, "row_key" => $k, 'user_id' => $AUTH_CURRENT_USER_ID]))
					{
						// Error adding queue item
						throw new ApplicationException(_t('Could not add logging tasks to queue'));
					}
					
					$map = caGetACLItemLevelMap();
					$acl_access = $map[$access] ?? $subject->getAppConfig()->get('default_item_access_level');
					
					ca_acl::setACLWorldAccessForRows($subject_table, $ids, $acl_access);
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * For regeneration of ACL inherited rules 
	 *
	 * @param BaseModel $subject
	 *
	 * @return bool
	 */
	public static function forceACLInheritanceUpdateForRow(BaseModel $subject) {
		if(ca_acl::removeACLValuesForRow($subject, ['onlyInherited' => true])) {
			if(ca_acl::updateACLInheritanceForRow($subject)) {
				if(ca_acl::applyACLInheritanceToChildrenFromRow($subject)) {
					return true;
				}
			}
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @param BaseModel $subject
	 *
	 * @return bool
	 */
	public static function forceAccessInheritanceUpdate(BaseModel $subject) : ?bool {
		if(ca_acl::applyAccessInheritance($subject)) {
			if(ca_acl::applyAccessInheritanceToChildrenFromRow($subject)) {
				if(ca_acl::applyAccessInheritanceToRelatedObjectsFromCollection($subject)) {
					return true;
				}
			}
		}
		return false;
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	/**
	 * Get hierarchy under subject row returning only children having access inheritance set
	 */
	private static function getAccessInheritanceHierarchy(BaseModel $subject) {
		return ca_acl::getInheritanceHierarchy($subject, ['mode' => 'access']);
	}
	# -------------------------------------------------------
	/**
	 * Get hierarchy under subject row returning only children having ACL inheritance set
	 */
	private static function getACLInheritanceHierarchy(BaseModel $subject) {
		return ca_acl::getInheritanceHierarchy($subject, ['mode' => 'acl']);
	}
	# -------------------------------------------------------
	/**
	 * Get hierarchy under subject row returning only children having inheritance set and all ancestors 
	 * uo to the subject having inheritance set. Will check for access or ACL inheritance depending upon
	 * the value of the 'mode' option. 
	 *
	 * @param BaseMode $subject
	 * @param array $options Options include:
	 *		mode = Type in inheritance to filter on. Valid values are 'access' and 'acl'. [Default is 'acl']
	 *
	 * return @array List of ids in hierarchy meeting inheritance requirements
	 */
	private static function getInheritanceHierarchy(BaseModel $subject, ?array $options=null) : array {
		$subject_id = $subject->getPrimaryKey();
		$subject_table = $subject->tableName();
		
		switch($mode = caGetOption('mode', $options, 'acl')) {
			case 'access':
				$key = 'access_inherit_from_parent';
				break;
			case 'acl':
				$key = 'acl_inherit_from_parent';
				break;
			default:
				throw new ApplicationException(_t('Invalid mode: %1', $mode));
				break;
		}
		
		
		$ids = $subject->getHierarchy(null, ['idsOnly' => true, 'includeSelf' => true]);
		$qr = caMakeSearchResult($subject_table, $ids);
		
		$items = [];
		while($qr->nextHit()) {
			$id = $qr->getPrimaryKey();
			$items[$id] = ['id' => $id, 'parent_id' => $qr->get('parent_id'), 'inherit' => $qr->get($key), 'access' => $qr->get('access')];
		}
		$hier = [];
		foreach($items as $c) {
			if(!$c['inherit'] && ($c['id'] != $subject_id)) { continue; }
			if($c['id'] == $subject_id) { $hier[] = $subject_id; continue; }
			
			$in_hier = true;
			$i = $c['id'];
			do {
				if($items[$i] && $items[$i]['parent_id'] && $items[$i]['inherit']) {
					$i = $items[$i]['parent_id'];
				} else {
					$in_hier = false;
					break;
				}
			} while($items[$i] && ($items[$i]['id'] != $subject_id));
			
			if($in_hier) {
				$hier[] = $c['id'];
			}
		}
		return $hier;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _createTempTableForRedundantACL(Db $db) {
		$table_name = '_caACLTmp'.str_replace('-', '',caGenerateGUID());
		$db->query("DROP TEMPORARY TABLE IF EXISTS {$table_name}");
		$db->query("
			CREATE TEMPORARY TABLE {$table_name} (
				table_num tinyint unsigned not null,
				row_id int unsigned not null,
				user_id int unsigned null,
				group_id int unsigned null,
				access tinyint unsigned not null,
							
				PRIMARY KEY i_row(table_num, row_id),
				KEY i_user(user_id, table_num, row_id),				
				KEY i_group(group_id, table_num, row_id)
			) engine=memory;
		");
		
		if ($db->numErrors()) {
			return false;
		}
		
		ca_acl::$temporary_tables[$table_name] = true;
		return $table_name;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _dropTempTable(Db $db, $table_name) {
		$db->query("
			DROP TEMPORARY TABLE IF EXISTS {$table_name};
		");
		if ($db->numErrors()) {
			return false;
		}
		unset(ca_acl::$temporary_tables[$table_name]);
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _dropTempTables(Db $db) {
		foreach(array_keys(ca_acl::$temporary_tables) as $t) {
			try {
				ca_acl::dropTempTable($db, $t);	
			} catch(Exception $e) {
				// noop - is unrecoverable
			}
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Override htmlFormElement to truncate "access" option list when "forPawtucket" option is set
	 * 
	 * @param string $field field name
	 * @param string $format field format
	 * @param array $options additional options
	 */
	public function htmlFormElement($field, $format=null, $options=null) {
		switch($field) {
			case 'access':
				if(caGetOption('forPawtucket', $options, false)) {
					$opts = array_filter(BaseModel::$s_ca_models_definitions['ca_acl']['FIELDS'][$field]['BOUNDS_CHOICE_LIST'], function($v) { 
						return ($v <= 1);
					});
					return caHTMLSelect($options['name'] ?? $field, $opts, ['id' => $options['id'] ?? null], $options);
				}
				break;
		}
		return parent::htmlFormElement($field, $format, $options);
	}
	# ------------------------------------------------------
}
