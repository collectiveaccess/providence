<?php
/** ---------------------------------------------------------------------
 * app/models/ca_guids.php : table access class for table ca_guids
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2023 Whirl-i-Gig
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

BaseModel::$s_ca_models_definitions['ca_guids'] = array(
	'NAME_SINGULAR' 	=> _t('globally unique identifier'),
	'NAME_PLURAL' 		=> _t('globally unique identifiers'),
	'FIELDS' 			=> array(
		'guid_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this queue entry')
		),
		'table_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Table', 'DESCRIPTION' => 'Table',
			'BOUNDS_VALUE' => array(0,255)
		),
		'row_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Row id', 'DESCRIPTION' => 'Row identifier'
		),
		'guid' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'GUID', 'DESCRIPTION' => 'Globally unique identifier'
		)
	)
);

class ca_guids extends BaseModel {
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
	protected $TABLE = 'ca_guids';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'guid_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('table_num', 'row_id', 'guid');

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
	protected $ORDER_BY = array('guid');

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

	/**
	 * @var resource|null
	 */
	static $s_lock_resource = null;


	# ------------------------------------------------------
	/**
	 * Get GUID for given row
	 * @param int $pn_table_num
	 * @param int $pn_row_id
	 * @param array $pa_options
	 * @return bool|string
	 */
	public static function getForRow($pn_table_num, $pn_row_id, $pa_options=null) {
		if(!$pn_table_num || !$pn_row_id) { return false; }

		/** @var Transaction $o_tx */
		if($o_tx = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db();
		}

		$qr_guid = $o_db->query('
			SELECT guid FROM ca_guids WHERE table_num=? AND row_id=?
		', $pn_table_num, $pn_row_id);

		if($qr_guid->nextRow()) {
			$vs_guid = $qr_guid->get('guid');
			return $vs_guid;
		} else {
			if(!caGetOption('dontAdd', $pa_options, false) && ($t_instance = Datamodel::getInstance($pn_table_num, true))) {
				if($vs_guid = self::addForRow($pn_table_num, $pn_row_id, $pa_options)) {
					return $vs_guid;
				}
			}
		}

		return false;
	}
	# ------------------------------------------------------
	/**
	 * Generates and adds a GUID for a given row.
	 * False is returned on error
	 * @param int $pn_table_num
	 * @param int $pn_row_id
	 * @param array $pa_options
	 * @return bool|string
	 */
	private static function addForRow($pn_table_num, $pn_row_id, $pa_options=null) {
		/** @var Transaction $o_tx */
		if($o_tx = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db();
		}

		$vs_guid = caGenerateGUID();
		$o_db->query("INSERT INTO ca_guids(table_num, row_id, guid) VALUES (?,?,?)", $pn_table_num, $pn_row_id, $vs_guid);
		return $vs_guid;
	}
	# ------------------------------------------------------
	/**
	 * Get row id and table num for given GUID
	 *
	 * @param string $ps_guid
	 * @param array $pa_options
	 * @return array|null
	 * 			keys are 'row_id' and 'table_num'
	 */
	public static function getInfoForGUID($ps_guid, $pa_options=null) {
		/** @var Transaction $o_tx */
		if($o_tx = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db();
		}

		$qr_guid = $o_db->query('
			SELECT table_num, row_id FROM ca_guids WHERE guid=?
		', $ps_guid);

		if($qr_guid->nextRow()) {
			return $qr_guid->getRow();
		}

		return null;
	}
	# ------------------------------------------------------
	/**
	 * Get row id and table num for given GUID
	 *
	 * @param array $guids
	 * @param array $options
	 * @return array|null
	 * 			keys are 'row_id' and 'table_num'
	 */
	public static function getInfoForGUIDs(array $guids, ?array $options=null) : ?array {
		if(!sizeof($guids)) { return null; }
		
		/** @var Transaction $o_tx */
		if($o_tx = caGetOption('transaction', $options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db();
		}

		$qr_guid = $o_db->query('
			SELECT table_num, row_id FROM ca_guids WHERE guid IN (?)
		', [$guids]);

		$ret = [];
		while($qr_guid->nextRow()) {
			$ret[] = $qr_guid->getRow();
		}

		return $ret;
	}
	# ------------------------------------------------------
	/**
	 * Return access value for row identified by GUID
	 *
	 * @param string $ps_guid
	 * @param array $pa_options
	 * @return int|null
	 */
	public static function getAccessForGUID($ps_guid, $pa_access, $pa_options=null) {
		/** @var Transaction $o_tx */
		if($o_tx = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db($ps_guid);
		}
		
		if (!($va_info = self::getInfoForGUID($ps_guid))) { return null; }

        $pa_access = array_map("intval", $pa_access);
        
        if (in_array(Datamodel::getTableName($va_info['table_num']), ['ca_lists', 'ca_list_items', 'ca_list_labels', 'ca_list_item_labels', 'ca_object_lots', 'ca_object_lot_labels'])) {   //TODO: make tables for which we should ignore access configurable
            return true;
        } elseif (Datamodel::isLabel($va_info['table_num'])) {
            if (($t_label = Datamodel::getInstanceByTableNum($va_info['table_num'], true)) && $t_label->load($va_info['row_id'])) {
                if (($t_subject = $t_label->getSubjectTableInstance())) {
                	if ($t_subject->hasField('deleted')) {
						if((int)$t_subject->get('deleted') !== 0) {
							return false;
						}
					}
                	if ($t_subject->hasField('access')) {
						if(!in_array((int)$t_subject->get('access'), $pa_access, true)) {
							return false;
						}
					}
                }
                return true;
            }
        } elseif (Datamodel::isRelationship($va_info['table_num'])) {
            if ($t_rel = Datamodel::getInstanceByTableNum($va_info['table_num'], true)) {
                $t_left = $t_rel->getLeftTableInstance();
                $t_right = $t_rel->getRightTableInstance();
                
                $t_rel->load($va_info['row_id']);
            
                $vb_left = $vb_right = null;
                
                if($t_left->load($t_rel->get($t_rel->getLeftTableFieldName()))) {
					if ($t_left->hasField('deleted')) {
						if((int)$t_left->get('deleted') !== 0) { return false; }
					}
					if ($t_left->hasField('access')) {
						if (!in_array((int)$t_left->get('access'), $pa_access, true)) { return false; }
					}
					$vb_left = true;
				}
                if ($t_right->load($t_rel->get($t_rel->getRightTableFieldName()))) {
                	if ($t_right->hasField('deleted')) {
						if((int)$t_right->get('deleted') !== 0) { return false; }
					}
					if ($t_right->hasField('access')) {
						if (!in_array((int)$t_right->get('access'), $pa_access, true)) { return false; }
					}
					$vb_right = true;
                }
                if (is_null($vb_left) && is_null($vb_right)) { return null; }
                return true;
            }
        } elseif(in_array((int)$va_info['table_num'], [3,4], true)) {
            if ($va_info['table_num'] == 3) {
                $t_attr_val = new ca_attribute_values($va_info['row_id']);
                $t_attr = new ca_attributes($t_attr_val->get('attribute_id'));
            } else {
                $t_attr = new ca_attributes($va_info['row_id']);
            }
            $vn_table_num = $t_attr->get('table_num');
            $vn_row_id = $t_attr->get('row_id');
            
            // TODO: make configurable
            if(in_array(Datamodel::getTableName($vn_table_num), ['ca_object_lots', 'ca_object_lot_labels', 'ca_lists', 'ca_list_items', 'ca_list_labels', 'ca_list_item_labels']))  { return true; }
            
            if (!Datamodel::getFieldInfo($vn_table_num, 'access')) { return false; }        // TODO: support attributes on non-acess control tables (eg. config tables; interstitial attributes on relationships)
            $qr_guid = $o_db->query('
                SELECT access FROM '.Datamodel::getTableName($vn_table_num)." WHERE ".Datamodel::primaryKey($vn_table_num).' = ?
            '.(Datamodel::getFieldInfo($vn_table_num, 'deleted') ? ' AND deleted = 0' : ''), [$vn_row_id]);

            if($qr_guid->nextRow()) {
                return in_array((int)$qr_guid->get('access'), $pa_access, true);
            }
            return false;
        } elseif(in_array((int)$va_info['table_num'], [105], true)) {
            
                $qr_guid = $o_db->query("
                    SELECT s.access 
                    FROM ca_set_items t
                    INNER JOIN ca_sets AS s ON s.set_id = t.set_id
                    WHERE t.item_id = ?
                ", [$va_info['row_id']]);

                if($qr_guid->nextRow()) {
                    return in_array((int)$qr_guid->get('access'), $pa_access); 
                }
                return false;
        } else {
            if (!Datamodel::getFieldInfo($va_info['table_num'], 'access')) { return null; }
            $qr_guid = $o_db->query('
                SELECT access FROM '.Datamodel::getTableName($va_info['table_num'])." WHERE ".Datamodel::primaryKey($va_info['table_num']).' = ?
            '.(Datamodel::getFieldInfo($va_info['table_num'], 'deleted') ? ' AND deleted = 0' : ''), [$va_info['row_id']]);

            if($qr_guid->nextRow()) {
                return in_array((int)$qr_guid->get('access'), $pa_access, true);
            }
        }

		return null;
	}
	# ------------------------------------------------------
	/**
	 * Check if a given GUID is deleted
	 * @param string $ps_guid
	 * @param array $pa_options
	 * @return bool
	 */
	public static function isDeleted($ps_guid, $pa_options = null) {
		$va_info = self::getInfoForGUID($ps_guid, $pa_options);
		if(!$va_info) { return false; }

		$t_instance = Datamodel::getInstance($va_info['table_num'], true);
		if(!$t_instance) { return false; }

		/** @var Transaction $o_tx */
		if($o_tx = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db();
		}

		if(!$t_instance->hasField('deleted')) { return false; }

		$qr_record = $o_db->query(
			"SELECT {$t_instance->primaryKey()}, deleted FROM {$t_instance->tableName()} WHERE {$t_instance->primaryKey()} = ?",
			$va_info['row_id']
		);
		if(!$qr_record->nextRow()) { return false; }

		return (bool) $qr_record->get('deleted');
	}
	# ------------------------------------------------------
	/**
	 * Return GUIDs for table
	 *
	 * @param string $table
	 * @param array $options Options include:
	 *		limit = Maximum number of GUIDs to return. [Default is 1000]
	 * @return array List of guids
	 */
	public static function guidsForTable($table, ?array $options=null) : array {
		if(!($table_num = Datamodel::getTableNum($table))) { return []; }
		if($o_tx = caGetOption('transaction', $options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db();
		}
		$limit = caGetOption('limit', $options, 1000);
		$qr = $o_db->query(
			"SELECT * FROM ca_guids WHERE table_num = ? LIMIT {$limit}", [$table_num]
		);
		$guids = $qr->getAllFieldValues('guid');
		
		return is_array($guids) ? $guids : [];
	}
	# ------------------------------------------------------
	/**
	 * Remove GUIDs for table that do not reference a valid row. Note rows marked as deleted
	 * are considered valid.
	 *
	 * @param string $table
	 * @param array $options No options are available.
	 * @return bool
	 */
	public static function removeUnusedGUIDs($table, ?array $options=null) : bool {
		if(!($table_num = Datamodel::getTableNum($table))) { return false; }
		if($o_tx = caGetOption('transaction', $options, null)) {
			$o_db = $o_tx->getDb();
		} else {
			$o_db = new Db();
		}
		$pk = Datamodel::primaryKey($table);
		
		try {
			$qr = $o_db->query(
				"DELETE FROM ca_guids WHERE table_num = ? AND row_id NOT IN (SELECT {$pk} FROM {$table})", [$table_num]
			);
		} catch (DatabaseException $e) {
			return false;
		}
		
		return true;
	}
	# ------------------------------------------------------
}
