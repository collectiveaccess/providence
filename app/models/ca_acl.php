<?php
/** ---------------------------------------------------------------------
 * app/models/ca_acl.php : table access class for table ca_acl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
	 * @param int $pn_table_num The table number for the row to check
	 * @param int $pn_row_id The primary key value for the row to check.
	 * @param array $options Options include:
	 *		transaction = current transaction object. [Default is null]
	 * @return int An access value 
	 */
	public static function accessForRow($t_user, $pn_table_num, $pn_row_id, ?array $options=null) {
		if(!caACLIsEnabled(Datamodel::getInstance($pn_table_num, true), [])) { return true; }
		
		if (!is_object($t_user)) { $t_user = new ca_users(); }
		$trans = caGetOption('transaction', $options, null);
		$db = $trans ? $trans->getDb() : $t_user->getDb();
		
		$vn_user_id = (int)$t_user->getPrimaryKey();
		
		if (isset(ca_acl::$s_acl_access_value_cache[$vn_user_id][$pn_table_num][$pn_row_id])) {
			return ca_acl::$s_acl_access_value_cache[$vn_user_id][$pn_table_num][$pn_row_id];
		}
		
		$vn_access = null;
		
		// try to load ACL for user
		if ($vn_user_id) {
			$qr_res = $db->query("
				SELECT max(access) a
				FROM ca_acl
				WHERE
					table_num = ? AND row_id = ? AND user_id = ?
					
			", (int)$pn_table_num, (int)$pn_row_id, $vn_user_id);
			
			if ($qr_res->nextRow()) {
				if (strlen($vs_access = $qr_res->get('a'))) {
					$vn_access = (int)$vs_access;
					if ($vn_access >= __CA_ACL_EDIT_DELETE_ACCESS__) {
						return ca_acl::$s_acl_access_value_cache[$vn_user_id][$pn_table_num][$pn_row_id] = $vn_access; 
					} // max access found so just return
				}
			}

			// user group acls
			$va_groups = $t_user->getUserGroups();
			if (is_array($va_groups)) {
				$va_group_ids = array_keys($va_groups);
				if (is_array($va_group_ids) && (sizeof($va_group_ids) > 0)) {
					$qr_res = $db->query("
						SELECT max(access) a 
						FROM ca_acl
						WHERE
							table_num = ? AND row_id = ? AND group_id IN (?)
							
					", (int)$pn_table_num, (int)$pn_row_id, $va_group_ids);
					
					if ($qr_res->nextRow()) {
						if (strlen($vs_access = $qr_res->get('a'))) {
							$vn_acl_access = (int)$vs_access;
							if ($vn_acl_access >= $vn_access) { $vn_access = $vn_acl_access; }
							if ($vn_access >= __CA_ACL_EDIT_DELETE_ACCESS__) { 
								return ca_acl::$s_acl_access_value_cache[$vn_user_id][$pn_table_num][$pn_row_id] = $vn_access; 
							} // max access found so just return
						}
					}
				}
			}

			// exceptions trump global access and the config setting so if we found some ACLs for either
			// the user or one of their groups, we use the maximum access value from that list of ACLs
			if(!is_null($vn_access)) {
				return $vn_access;
			}
		}
		
		// If no valid exceptions found, get world access for this item
		$qr_res = $db->query("
			SELECT max(access) a 
			FROM ca_acl
			WHERE
				table_num = ? AND row_id = ? AND group_id IS NULL AND user_id IS NULL
				
		", (int)$pn_table_num, (int)$pn_row_id);
		while($qr_res->nextRow()) {
			if (strlen($vs_access = $qr_res->get('a')) && (is_null($vn_access) || ((int)$vs_access >= $vn_access))) {
				$vn_access = (int)$vs_access;
			}
		}
		if (!is_null($vn_access)) { 
			return ca_acl::$s_acl_access_value_cache[$vn_user_id][$pn_table_num][$pn_row_id] = $vn_access; 
		}
		
		// If no valid ACL exists return default from config
		$o_config = Configuration::load();
		return ca_acl::$s_acl_access_value_cache[$vn_user_id][$pn_table_num][$pn_row_id] = (int)$o_config->get('default_item_access_level');
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
		$ids = $subject->getHierarchyAsList($subject_id, ['idsOnly' => true, 'includeSelf' => false]);
		if(!sizeof($ids)) { return false; }
		
		$subject_pk = (string)$subject->primaryKey();
		$subject_table_name = (string)$subject->tableName();
		$subject_table_num = (int)$subject->tableNum();
		
		$qr = caMakeSearchResult($subject_table_name, $ids);
		$inherit_from_parent_flag_exists = $subject->hasField('acl_inherit_from_parent');
		
		// Get current ACL values for this row
		$current_acl = ca_acl::getACLValuesForRow($subject_table_num, $subject_id);
		
		// Delete existing inherited rows
		ca_acl::removeACLValuesForRow($subject_table_num, $subject_id);
			
		// Apply rows to all
		while($qr->nextHit()) {
			$id = $qr->get("{$subject_table_name}.{$subject_pk}");
			if($inherit_from_parent_flag_exists && !$qr->get("{$subject_table_name}.acl_inherit_from_parent")) { continue; }
			
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
	public static function getStatisticsForRow($subject, int $row_id) : ?array {
		$db = is_object($subject) ? $subject->getDb() : new Db();
		if(!($subject_table_num = is_object($subject) ? $subject->tableNum() : Datamodel::getTableNum($subject))) { return null; }
		
		$subject = is_object($subject) ? $subject : Datamodel::getInstance($subject_table_num, false, $row_id);
		if(!$subject->isLoaded()) { return null; }
		
		$statistics = [
			'subRecordCount' => 0,
			'inheritingSubRecordCount' => 0,
			'relatedObjectCount' => 0,
			'inheritingRelatedObjectCount' => 0,
			'inheritingAccessRelatedObjectCount' => 0
		];
		
		// Number of sub-records and inherited entries
		
		if($qr_sub_records = $subject->getHierarchy($row_id, [])) {
			$statistics['subRecordCount'] = $qr_sub_records->numRows()-1;
			$c = 0;
			while($qr_sub_records->nextRow()) {
				if($qr_sub_records->get($subject->primaryKey()) == $row_id) { continue; }
				if((bool)$qr_sub_records->get('acl_inherit_from_parent')) { $c++; }
			}
			$statistics['inheritingSubRecordCount'] = $c;
		}
		
		// Number of related objects and inherited entries
		if($subject->tableName() === 'ca_collections') {
			if($qr_sub_records = $subject->getHierarchy($row_id, ['includeSelf' => true])) {
				while($qr_sub_records->nextRow()) {
					if(!($t_coll = ca_collections::findAsInstance(['collection_id' => $qr_sub_records->get('ca_collections.collection_id')]))) { continue; }
					
					$statistics['relatedObjectCount'] += $t_coll->getRelatedItems('ca_objects', ['returnAs' => 'count', 'limit' => 50000]);
					
					$statistics['inheritingRelatedObjectCount'] += $t_coll->getRelatedItems('ca_objects', ['returnAs' => 'count', 'limit' => 50000, 'criteria' => ['ca_objects.acl_inherit_from_ca_collections']]);
					$statistics['inheritingAccessRelatedObjectCount'] += $t_coll->getRelatedItems('ca_objects', ['returnAs' => 'count', 'limit' => 50000, 'criteria' => ['ca_objects.access_inherit_from_parent']]);
				}
			}
		}
		return $statistics;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function setInheritanceSettingForAllChildRows($subject, int $row_id, bool $set_all) : ?bool {
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
	public static function setInheritanceSettingForRelatedObjects($subject, int $row_id, bool $set_all) : ?bool {
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
	 * Delete all existng ACL settings inherited from specified row
	 *
	 * @param mixed $subject Table name or number
	 * @param int $row_id ID of row
	 *
	 * @return ?bool True on success, false on error, null if table does not exist 
	 */
	public static function removeACLValuesForRow($subject, int $row_id) : ?bool {
		$db = is_object($subject) ? $subject->getDb() : new Db();
		
		if(!($subject_table_num = Datamodel::getTableNum($subject))) { return null; }
		
		return (bool)$db->query(
			"DELETE FROM ca_acl WHERE inherited_from_table_num = ? AND inherited_from_row_id = ? AND table_num = ?", 
				[$subject_table_num, $row_id, $subject_table_num]);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function updateACLInheritanceForRow($subject) : bool {
		$subject_id = (int)$subject->getPrimaryKey();
		if (!$subject_id) { return false; }
		
		$subject_pk = (string)$subject->primaryKey();
		$subject_table_name = (string)$subject->tableName();
		$subject_table_num = (int)$subject->tableNum();
		
		if($parent_id = $subject->get("parent_id")) {
			if($t_parent = $subject_table_name::findAsInstance([$subject_pk => $parent_id])) {
				ca_acl::applyACLInheritanceToChildrenFromRow($t_parent);
			}
		}
		
		$ids = $subject->getHierarchyAsList($subject_id, ['idsOnly' => true, 'includeSelf' => true]);
		if(!is_array($ids) || !sizeof($ids)) { return true; }
		
		$qr = caMakeSearchResult($subject_table_name, $ids);
		while($qr->nextHit()) {
			$t_child = $qr->getInstance();
			ca_acl::applyACLInheritanceToChildrenFromRow($t_child);
			
			if($subject_table_name === 'ca_collections') {
				ca_acl::applyACLInheritanceToRelatedFromRow($t_child, 'ca_objects');
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function applyACLInheritanceToRelatedFromRow($subject, $target) {
		$db = is_object($subject) ? $subject->getDb() : new Db();
		
		if ($t_link = $subject->getRelationshipInstance($target)) {
			if ($t_rel_item = Datamodel::getInstanceByTableName($target, false)) {
				$path = array_keys(Datamodel::getPath($cur_table = $subject->tableName(), $target));
				$table = array_shift($path);
				
				if (!$t_rel_item->hasField("acl_inherit_from_{$table}")) { return false; }
				
				$target_pk = (string)$t_rel_item->primaryKey();
				$target_table_num = (int)$t_rel_item->tableNum();
				
				$subject_pk = (string)$subject->primaryKey();
				$subject_table_name = (string)$subject->tableName();
				$subject_table_num = (int)$subject->tableNum();
				$subject_id = (int)$subject->getPrimaryKey();
				
				foreach($path as $join_table) {
					$rel_info = Datamodel::getRelationships($cur_table, $join_table);
					$joins[] = 'INNER JOIN '.$join_table.' ON '.$cur_table.'.'.$rel_info[$cur_table][$join_table][0][0].' = '.$join_table.'.'.$rel_info[$cur_table][$join_table][0][1]."\n";
					$cur_table = $join_table;
				}
				
				// Delete existing inherited rows
				$qr_del = $db->query("DELETE FROM ca_acl WHERE inherited_from_table_num = ? AND inherited_from_row_id = ? AND table_num = ?", array((int)$subject_table_num, (int)$subject_id, (int)$target_table_num));
				
				$qr_res = $db->query("
					SELECT {$target}.{$target_pk}
					FROM {$subject_table_name}
					".join("\n", $joins)."
					WHERE ({$subject_table_name}.{$subject_pk} = ?) AND {$target}.acl_inherit_from_{$subject_table_name} = 1", (int)$subject->getPrimaryKey());
			
				while($qr_res->nextRow()) {
					$target_id = $qr_res->get($target_pk);
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
		
		$subject_table_name = $subject->tableName();
		$subject_table_num = $subject->tableNum();
		$subject_pk = $subject->primaryKey();
		
		$new_entries = [];
		if($qr = $db->query("
			SELECT t.{$subject_pk}, a.*
			FROM {$subject_table_name} t
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
	/**
	 *	
	 */
	public static function setAccessInheritanceSettingToRelatedObjectsFromCollection($subject, int $row_id, bool $set_all, ?array $options=null) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		
		if(!($subject = is_object($subject) ? $subject : Datamodel::getInstance($subject, true, $row_id))) { return null; }
		if(($subject_table = $subject->tableName()) !== 'ca_collections') { return null; }
		if(!$subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled')) { return null; }
		if(!($rel_type = $subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type'))) { return null; }
		
		$db = $subject->getDb() ?? new Db();
		
		$ret = true;
		if ($t_link = $subject->getRelationshipInstance('ca_objects')) {
			if ($t_rel_item = Datamodel::getInstanceByTableName('ca_objects', false)) {
				if($qr_res = $subject->getRelatedItems('ca_objects', ['restrictToRelationshipTypes' => [$rel_type], 'returnAs' => 'searchResult', 'limit' => 50000])) {
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
							
							if(!$db->query("UPDATE ca_objects SET access_inherit_from_parent = ? WHERE object_id IN (?)", [$set_all ? 1 : 0, $ids])) {
								$ret = false;
							} else {
								$o_tq = new TaskQueue(['transaction' => $subject->getTransaction()]);
								$k = 'ca_collections::'.$subject->getPrimaryKey();
								
								$log_entries = [];
								foreach($ids as $id) {
									$log_entries[] = [
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
		SearchResult::clearCaches();
		
		return $ret;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function applyAccessInheritanceToRelatedObjectsFromCollection($subject, ?array $options=null) : ?bool {
		global $AUTH_CURRENT_USER_ID;
		
		if(!$subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled')) { return null; }
		if(!($rel_type = $subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type'))) { return null; }
		if($subject->tableName() !== 'ca_collections') { return false; }
		$db = $subject->getDb() ?? new Db();
		
		$access = (int)$subject->get('access');
		
		if ($t_link = $subject->getRelationshipInstance('ca_objects')) {
			if ($t_rel_item = Datamodel::getInstanceByTableName('ca_objects', false)) {
				if(is_array($ids = $subject->getRelatedItems('ca_objects', ['restrictToRelationshipTypes' => [$rel_type], 'returnAs' => 'ids', 'limit' => 50000])) && sizeof($ids)) {
					$db->query("UPDATE ca_objects SET access = ? WHERE object_id IN (?)", [$access, $ids]);
					
					$o_tq = new TaskQueue(['transaction' => $subject->getTransaction()]);
					$k = 'ca_collections::'.$subject->getPrimaryKey();
					
					$log_entries = [];
					foreach($ids as $id) {
						$log_entries[] = [
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
				}
				
			}
		}
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function applyACLInheritanceToRelatedRowFromRow($subject, $subject_id, $target, $target_id, $options=null) {
		$db = $subject->getDb() ?? new Db();
		
		if ($t_link = $subject->getRelationshipInstance($target)) {
			if ($t_rel_item = Datamodel::getInstanceByTableName($target, false)) {
				$target_pk = (string)$t_rel_item->primaryKey();
				$target_table_num = (int)$t_rel_item->tableNum();
				
				$subject_pk = (string)$subject->primaryKey();
				$subject = (string)$subject->tableName();
				$subject_table_num = (int)$subject->tableNum();
				$target_id = (int)$target_id;
				$subject_id = (int)$subject_id;
				
				if (!isset($options['deleteACLOnly']) || !$options['deleteACLOnly']) {
					if (!$t_rel_item->hasField("acl_inherit_from_{$subject}")) { return false; }
				}
				
				// Delete existing inherited rows
				$db->query("DELETE FROM ca_acl WHERE inherited_from_table_num = ? AND inherited_from_row_id = ? AND table_num = ? AND row_id = ?", array((int)$subject_table_num, (int)$subject_id, (int)$target_table_num, (int)$target_id));
				
				if (!isset($options['deleteACLOnly']) || !$options['deleteACLOnly']) {
					// only inherit if inherit_from field is set. $target and $target_pk have been verified at this pont
					$qr_inherit = $db->query("SELECT acl_inherit_from_{$subject} FROM {$target} WHERE {$target_pk} = ?", $target_id);
					if(!$qr_inherit->nextRow()) { return false; }
					if(!$qr_inherit->get("acl_inherit_from_{$subject}")) { return false; }

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
	# ------------------------------------------------------
}
