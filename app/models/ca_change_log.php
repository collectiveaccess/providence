<?php
/** ---------------------------------------------------------------------
 * app/models/ca_change_log.php : table access class for table ca_change_log
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2016 Whirl-i-Gig
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


BaseModel::$s_ca_models_definitions['ca_change_log'] = array(
	'NAME_SINGULAR' 	=> _t('change log entry'),
	'NAME_PLURAL' 		=> _t('change log entries'),
	'FIELDS' 			=> array(
		'log_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Log id', 'DESCRIPTION' => 'Identifier for Log'
		),
		'log_datetime' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Logged date and time'), 'DESCRIPTION' => _t('Date and time logged event occurred')
		),
		'user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'LABEL' => _t('User'), 'DESCRIPTION' => _t('User who performed event')
		),
		'changetype' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 1, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Action'), 'DESCRIPTION' => _t('Type of action performed by user'),
			'BOUNDS_LENGTH' => array(0,1)
		),
		'logged_table_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Table', 'DESCRIPTION' => 'Table to which action was applied',
			'BOUNDS_VALUE' => array(0,255)
		),
		'logged_row_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Row id', 'DESCRIPTION' => 'Identifier of row to which action was applied'
		),
		'unit_id' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'LABEL' => 'Unit id', 'DESCRIPTION' => ''
		)
	)
);

require_once(__CA_MODELS_DIR__ . '/ca_guids.php');

class ca_change_log extends BaseModel {
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
	protected $TABLE = 'ca_change_log';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'log_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('user_data');

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
	protected $ORDER_BY = array('user_data');

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
	 * Get next ca_change_log.log_id for a given timestamp
	 * @param int $pn_timestamp
	 * @return int|bool
	 */
	public static function getLogIDForTimestamp($pn_timestamp) {
		if(!is_numeric($pn_timestamp)) { return false; }

		$o_db = new Db();

		$qr_results = $o_db->query("
			SELECT log_id FROM ca_change_log WHERE log_datetime > ? LIMIT 1
		", $pn_timestamp);

		if($qr_results->nextRow()) {
			return (int) $qr_results->get('log_id');
		}

		return false;
	}
	# ------------------------------------------------------
	/**
	 * Get next ca_change_log.log_id for a given timestamp
	 * @return int|bool
	 */
	public static function getLastLogID() {
		$o_db = new Db();

		$qr_results = $o_db->query("SELECT max(log_id) as log_id FROM ca_change_log");

		if($qr_results->nextRow()) {
			return (int) $qr_results->get('log_id');
		}

		return false;
	}
	# ------------------------------------------------------
	/**
	 * @param int $pn_from
	 * @param null|int $pn_limit
	 * @param null|array $pa_options
	 * @param null|array $pa_media optional array that will contain media references
	 * @return array
	 */
	public static function getLog($pn_from, $pn_limit=null, $pa_options=null, &$pa_media=null) {
		require_once(__CA_MODELS_DIR__ . '/ca_metadata_elements.php');

		$o_dm = Datamodel::load();

		if(!is_null($pn_limit)) {
			$vs_limit_sql = "LIMIT $pn_limit";
		} else {
			$vs_limit_sql = '';
		}

		$pa_skip_if_expression = caGetOption('skipIfExpression', $pa_options);
		if(!is_array($pa_skip_if_expression)) { $pa_skip_if_expression = array(); }

		$pa_ignore_tables = caGetOption('ignoreTables', $pa_options);
		if(!is_array($pa_ignore_tables)) { $pa_ignore_tables = array(); }
		
		$pa_only_tables = caGetOption('onlyTables', $pa_options);
		if(!is_array($pa_only_tables)) { $pa_only_tables = array(); }
		
		$pa_include_metadata = caGetOption('includeMetadata', $pa_options);
		if(!is_array($pa_include_metadata)) { $pa_include_metadata = array(); }
		
		$pa_exclude_metadata = caGetOption('excludeMetadata', $pa_options);
		if(!is_array($pa_exclude_metadata)) { $pa_exclude_metadata = array(); }
		
		$ps_for_guid = caGetOption('forGUID', $pa_options);
		$ps_for_logged_guid = caGetOption('forLoggedGUID', $pa_options);

		$va_ignore_tables = [];
		foreach($pa_ignore_tables as $vs_ignore_table) {
			if($vn_ignore_table_num = $o_dm->getTableNum($vs_ignore_table)) {
				$va_ignore_tables[] = $vn_ignore_table_num;
			}
		}		
		$va_only_tables = [];
		foreach($pa_only_tables as $vs_only_table) {
			if($vn_only_table_num = $o_dm->getTableNum($vs_only_table)) {
				$va_only_tables[] = $vn_only_table_num;
			}
		}
		
		
		$vs_table_filter_sql = $vs_table_filter_subject_sql = '';

		$o_db = new Db();

		if ($ps_for_logged_guid) {
			if(sizeof($va_only_tables)) {
				$vs_table_filter_sql = 'AND cl.logged_table_num IN (' . join(',', $va_only_tables) . ')';
			} elseif(sizeof($va_ignore_tables)) {
				$vs_table_filter_sql = 'AND cl.logged_table_num NOT IN (' . join(',', $va_ignore_tables) . ')';
			}
			$qr_results = $o_db->query("
				SELECT cl.log_id i, cl.*, cls.* 
				FROM ca_change_log cl
				INNER JOIN ca_change_log_snapshots AS cls ON cl.log_id = cls.log_id
				INNER JOIN ca_guids AS g ON g.table_num = cl.logged_table_num AND g.row_id = cl.logged_row_id
				WHERE 
					g.guid=?
				{$vs_table_filter_sql}
				{$vs_limit_sql}
			", [$ps_for_guid]);
		} elseif ($ps_for_guid) {				
			if(sizeof($va_only_tables)) {
				$vs_table_filter_sql = 'AND (cl.logged_table_num IN (' . join(',', $va_only_tables) . ')) AND (csub.subject_table_num IN (' . join(',', $va_only_tables) . ') OR csub.subject_table_num IS NULL) ';
				$vs_table_filter_subject_sql = 'AND csub.subject_table_num IN (' . join(',', $va_only_tables) . ') AND (cl.logged_table_num IN (' . join(',', $va_only_tables) . ')) ';
			} elseif(sizeof($va_ignore_tables)) {
				$vs_table_filter_sql = 'AND cl.logged_table_num NOT IN (' . join(',', $va_ignore_tables) . ') AND (csub.subject_table_num NOT IN (' . join(',', $va_ignore_tables) . ') OR csub.subject_table_num IS NULL) ';
				$vs_table_filter_subject_sql = 'AND (csub.subject_table_num NOT IN (' . join(',', $va_ignore_tables) . ')) AND (cl.logged_table_num NOT IN (' . join(',', $va_ignore_tables) . ')) ';
			}
			$qr_results = $o_db->query("
				(SELECT cl.log_id i, cl.*, cls.* 
				FROM ca_change_log cl
				INNER JOIN ca_change_log_snapshots AS cls ON cl.log_id = cls.log_id
				LEFT JOIN ca_change_log_subjects AS csub ON cl.log_id = csub.log_id
				INNER JOIN ca_guids AS g ON g.table_num = cl.logged_table_num AND g.row_id = cl.logged_row_id
				WHERE 
					g.guid=?
				{$vs_table_filter_sql})
			
				UNION
			
				(SELECT cl.log_id i, cl.*, cls.* 
				FROM ca_change_log cl
				INNER JOIN ca_change_log_snapshots AS cls ON cl.log_id = cls.log_id
				INNER JOIN ca_change_log_subjects AS csub ON cl.log_id = csub.log_id
				INNER JOIN ca_guids AS g ON g.table_num = csub.subject_table_num AND g.row_id = csub.subject_row_id
				WHERE 
					g.guid=?
				{$vs_table_filter_subject_sql})
				{$vs_limit_sql}
			", [$ps_for_guid, $ps_for_guid]);
			
			// is GUID for ca_attributes?
			if (($qr_results->numRows() == 0) && is_array($va_guid_info = ca_guids::getInfoForGUID($ps_for_guid)) && ($va_guid_info['table_num'] == 4)) {
				// Synthesize an insert record for this based upon existing row
				$t_attr = new ca_attributes($va_guid_info['row_id']);
				if ($t_attr->isLoaded()) {
					
					$qr_log_start = $o_db->query("
						SELECT log_id 
						FROM ca_change_log
						WHERE
							changetype = 'I' AND logged_table_num = ? AND logged_row_id = ?
					", [$t_attr->get('table_num'), $t_attr->get('row_id')]);
					if ($qr_log_start->nextRow()) {
						$vn_log_start = $qr_log_start->get('log_id');
						$va_ret[$vn_log_start] = [
							'SYN' => true,
							'i' => $vn_log_start,
							'log_id' => $vn_log_start,
							'log_datetime' => time(),
							'user_id' => null,
							'changetype' => 'I',
							'logged_table_num' => 4,
							'logged_row_id' => $va_guid_info['row_id'],
							'rolledback' => 0,
							'unit_id' => null,
							'batch_id' => null,
							'snapshot' => [
								'attribute_id' => $t_attr->getPrimaryKey(),
								'element_id' => $t_attr->get('element_id'),
								'element_code' => ca_metadata_elements::getElementCodeForId($t_attr->get('element_id')),
								'element_id_guid' => ca_guids::getForRow(42,$t_attr->get('element_id')),
								'locale_id' => $t_attr->get('locale_id'),
								'locale_id_guid' => ca_guids::getForRow(37, $t_attr->get('locale_id')),
								'table_num' => $t_attr->get('table_num'),
								'row_id' => $t_attr->get('row_id'),
								'row_guid' => ca_guids::getForRow($t_attr->get('table_num'), $t_attr->get('row_id'))
							],
							'guid' => $ps_for_guid,
							'subjects' => [
								0 => [
									'log_id' => $vn_log_start,
									'subject_table_num' => $vs_n = $t_attr->get('table_num'),
									'subject_row_id' => $vn_r = $t_attr->get('row_id'),
									'guid' => ca_guids::getForRow($vs_n, $vn_r)
								]
							]
						];
					}	
				}
				return $va_ret;
			} 
		} else {		
			if(sizeof($va_only_tables)) {
				$vs_table_filter_sql = 'AND cl.logged_table_num IN (' . join(',', $va_only_tables) . ')';
			} elseif(sizeof($va_ignore_tables)) {
				$vs_table_filter_sql = 'AND cl.logged_table_num NOT IN (' . join(',', $va_ignore_tables) . ')';
			}
			$qr_results = $o_db->query("
				SELECT * FROM ca_change_log cl, ca_change_log_snapshots cls
				WHERE cl.log_id = cls.log_id AND cl.log_id>=?
				{$vs_table_filter_sql}
				ORDER BY cl.log_id
				{$vs_limit_sql}
			", [$pn_from]);
		}

		$va_ret = array();
		if ($qr_results) {
			while($qr_results->nextRow()) {
				$va_row = $qr_results->getRow();

				// skip log entries without GUID -- we don't care about those
				if(!($vs_guid = ca_guids::getForRow($qr_results->get('logged_table_num'), $qr_results->get('logged_row_id')))) {
					continue;
				}
				$va_row['guid'] = $vs_guid;

				// don't sync inserts/updates for deleted records, UNLESS they're in a hierarchical table,
				// in which case other records may have depended on them when they were inserted
				// (meaning their insert() could fail if a related/parent record is absent)
				$t_instance = $o_dm->getInstance((int) $qr_results->get('logged_table_num'), true);
				//if(!$t_instance->isHierarchical() && ca_guids::isDeleted($vs_guid) && ($va_row['changetype'] != 'D')) {
				//	continue;
				//}

				// decode snapshot
				$va_snapshot = caUnserializeForDatabase($qr_results->get('snapshot'));
			
				$va_many_to_one_rels = $o_dm->getManyToOneRelations($t_instance->tableName());

				// add additional sync info to snapshot. we need to be able to properly identify
				// attributes and elements on the far side of the sync and the primary key doesn't cut it
				foreach($va_snapshot as $vs_fld => $vm_val) {
					switch($vs_fld) {
						case 'element_id':
							if(preg_match("!^ca_metadata_element!", $t_instance->tableName())) {
								goto deflabel;
							} elseif($vs_code = ca_metadata_elements::getElementCodeForId($vm_val)) {
								$va_snapshot['element_code'] = $vs_code;
							
								$vs_table_name = $o_dm->getTableName(ca_attributes::getTableNumForAttribute($va_snapshot['attribute_id']));
							
								// Skip elements not in include list, when a list is provided for the current table
								if (!$vs_table_name || (is_array($pa_include_metadata[$vs_table_name]) && !isset($pa_include_metadata[$vs_table_name][$vs_code]))) {
									$va_snapshot = ['SKIP' => true];
									continue(2);
								}
							
								// Skip elements present in the exclude list
								if (is_array($pa_exclude_metadata[$vs_table_name]) && isset($pa_exclude_metadata[$vs_table_name][$vs_code])) {
									$va_snapshot = ['SKIP' => true];
									continue(2);
								}
							} else {
								$va_snapshot = ['SKIP' => true];
								continue(2);
							}
							break;
						case 'attribute_id':
							if($vs_attr_guid = ca_attributes::getGUIDByPrimaryKey($vm_val)) {
								$va_snapshot['attribute_guid'] = $vs_attr_guid;
							} else {
								$va_snapshot = ['SKIP' => true];
								continue(2);
							}
							break;
						case 'type_id':
							if(preg_match("!^ca_relationship_type!", $t_instance->tableName())) {
								goto deflabel;
							} elseif($t_instance) {
								if($t_instance instanceof BaseRelationshipModel) {
									if (!($va_snapshot['type_code'] = caGetRelationshipTypeCode($vm_val))) { continue(2); }
								} elseif($t_instance instanceof BaseModel) {
									if (!($va_snapshot['type_code'] = caGetListItemIdno($vm_val)) && (!$t_instance->getFieldInfo('type_id', 'IS_NULL'))) { continue(2); }
								} 
							} else {
								$va_snapshot = ['SKIP' => true];
								continue(2);
							}
							break;
						case 'row_id':
							if(isset($va_snapshot['table_num']) && ($vn_table_num = $va_snapshot['table_num'])) {
								$va_snapshot['row_guid'] = \ca_guids::getForRow($vn_table_num, $vm_val);
							} else {
								$va_snapshot = ['SKIP' => true];
								continue(2);
							}
							break;
						default:
						deflabel:
							if(
								// don't break ca_list_items.item_id!!
								($o_dm->getTableName((int) $qr_results->get('logged_table_num')) == 'ca_attribute_values')
								&&
								($vs_fld == 'item_id')
							) {
								$va_snapshot['item_code'] = caGetListItemIdno($vm_val);
								$va_snapshot['item_label'] = caGetListItemForDisplayByItemID($vm_val);
							}

							$t_instance = $o_dm->getInstance((int) $qr_results->get('logged_table_num'), true);
							if(!is_null($vm_val) && ($va_fld_info = $t_instance->getFieldInfo($vs_fld))) {
								// handle all other list referencing fields
								$vs_new_fld = str_replace('_id', '', $vs_fld) . '_code';
								if(isset($va_fld_info['LIST'])) {
									$va_snapshot[$vs_new_fld] = caGetListItemIdno(caGetListItemIDForValue($va_fld_info['LIST'], $vm_val));
								} elseif(isset($va_fld_info['LIST_CODE'])) {
									$va_snapshot[$vs_new_fld] = caGetListItemIdno($vm_val);
								}

								if($vs_fld == $t_instance->getProperty('HIERARCHY_PARENT_ID_FLD')) {
									// handle monohierarchy (usually parent_id) fields
									$va_snapshot[$vs_fld . '_guid'] = ca_guids::getForRow($t_instance->tableNum(), $vm_val);
								} elseif (isset($va_many_to_one_rels[$vs_fld]) && ($t_rel_item = $o_dm->getInstanceByTableName($va_many_to_one_rels[$vs_fld]['one_table'], true))) {
									// handle many-one keys
									$va_snapshot[$vs_fld . '_guid'] = ca_guids::getForRow($t_rel_item->tableNum(), $vm_val);
								}

								// handle media ...
								if (($va_fld_info['FIELD_TYPE'] === FT_MEDIA)
									||
									(
										($o_dm->getTableName((int) $qr_results->get('logged_table_num')) == 'ca_attribute_values')
										&&
										($vs_fld == 'value_blob')
									)
								) {

									// we only put the URL/path if it's still the latest file. can figure that out with a simple query
									 $qr_future_entries = $o_db->query("
											SELECT * FROM ca_change_log cl, ca_change_log_snapshots cls
											WHERE cl.log_id = cls.log_id AND cl.log_id>?
											AND cl.logged_row_id = ? AND cl.logged_table_num = ?
											ORDER BY cl.log_id
										", $va_row['log_id'], $va_row['logged_row_id'], $va_row['logged_table_num']);
 
									$pb_is_latest = true;
									while($qr_future_entries->nextRow()) {
										$va_future_snap = caUnserializeForDatabase($qr_future_entries->get('snapshot'));
										if(isset($va_future_snap[$vs_fld]) && $va_future_snap[$vs_fld]) {
											$pb_is_latest = false;
											break;
										}
									}

									if($pb_is_latest) {
										// nowadays the change log entry is an <img> tag ... the default behavior for get('media'), presumably
										// it usually points to a non-original version, so we have to actually load() here to get the original
										if(is_string($va_snapshot[$vs_fld])) {
											if ($va_row['logged_table_num'] == 3) {
												$x = new ca_attribute_values($va_row['logged_row_id']);
												$va_snapshot[$vs_fld] = $x->getMediaUrl($vs_fld, 'original');
											} else {
												$t_instance->load($va_row['logged_row_id']);
												$va_snapshot[$vs_fld] = $t_instance->getMediaUrl($vs_fld, 'original');
												$va_snapshot[$vs_fld."_media_desc"] = array_shift($t_instance->get($vs_fld, array('returnWithStructure' => true)));
											}
										} elseif(is_array($va_snapshot[$vs_fld])) { // back in the day it would store the full media array here
											$o_coder = MediaInfoCoder::load();
											$va_snapshot[$vs_fld] = $o_coder->getMediaUrl($va_snapshot[$vs_fld], 'original');
										}
									} else { // if it's not the latest, we don't care about the media
										unset($va_snapshot[$vs_fld]);
									}
								
								

									// if caller wants media references, collect them here and replace
									if(is_array($pa_media) && isset($va_snapshot[$vs_fld])) {
										$vs_md5 = md5($va_snapshot[$vs_fld]);
										$pa_media[$vs_md5] = $va_snapshot[$vs_fld];
										$va_snapshot[$vs_fld] = $vs_md5;
									}

									// also unset media metadata, because otherwise json_encode is likely to bail
									unset($va_snapshot['media_metadata']);	
								}

								// handle left and right foreign keys in foo_x_bar table
								if($t_instance instanceof BaseRelationshipModel) {
									if($vs_fld == $t_instance->getProperty('RELATIONSHIP_LEFT_FIELDNAME')) {
										$vs_left_guid = ca_guids::getForRow($t_instance->getLeftTableNum(), $vm_val);
										$va_snapshot[$vs_fld . '_guid'] = $vs_left_guid;

										// don't sync relationships involving deleted records
										//if(ca_guids::isDeleted($vs_left_guid) && ($va_row['changetype'] != 'D')) {
										//	continue 3;
										//}
									}

									if($vs_fld == $t_instance->getProperty('RELATIONSHIP_RIGHT_FIELDNAME')) {
										$vs_right_guid = ca_guids::getForRow($t_instance->getRightTableNum(), $vm_val);
										$va_snapshot[$vs_fld . '_guid'] = $vs_right_guid;

										// don't sync relationships involving deleted records
										//if(ca_guids::isDeleted($vs_right_guid) && ($va_row['changetype'] != 'D')) {
										//	continue 3;
										//}
									}
								}

								// handle foreign keys for labels (add guid for main record)
								if($t_instance instanceof BaseLabel) {

									if($vs_fld == $t_instance->getSubjectKey()) {
										$vs_label_subject_guid_field = str_replace('_id', '', $vs_fld) . '_guid';
										$va_snapshot[$vs_label_subject_guid_field] = ca_guids::getForRow($t_instance->getSubjectTableInstance()->tableNum(), $vm_val);
									}
								}

								// handle 1:n foreign keys like ca_representation_annotations.representation_id
								// @todo: don't use hardcoded field names!? -- another case would be ca_objects.lot_id
								if(($t_instance instanceof \ca_representation_annotations) && ($vs_fld == 'representation_id')) {
									$va_snapshot['representation_guid'] = ca_guids::getForRow($o_dm->getTableNum('ca_object_representations'), $vm_val);
								}
							}
							break;
					}
				}

				if ($va_snapshot['SKIP']) { $va_row['SKIP'] = true; unset($va_snapshot['SKIP']); }	// row skipped because it's invalid, not on the whitelist, etc.
				$va_row['snapshot'] = $va_snapshot;

				// get subjects
				$qr_subjects = $o_db->query("SELECT * FROM ca_change_log_subjects WHERE log_id=?", $qr_results->get('log_id'));

				while($qr_subjects->nextRow()) {
					// skip subjects without GUID -- we don't care about those
					if(!($vs_subject_guid = ca_guids::getForRow($qr_subjects->get('subject_table_num'), $qr_subjects->get('subject_row_id')))) {
						continue;
					}

					// handle skip if expression relative to subjects
					$vs_subject_table_name = $o_dm->getTableName($qr_subjects->get('subject_table_num'));
					if(isset($pa_skip_if_expression[$vs_subject_table_name])) {
						$t_subject_instance = $o_dm->getInstance($vs_subject_table_name);
						$vs_exp = $pa_skip_if_expression[$vs_subject_table_name];
						// have to load() unfortch.
						$t_subject_instance->load($qr_subjects->get('subject_row_id'));
						$va_exp_vars = array();
						foreach(ExpressionParser::getVariableList($vs_exp) as $vs_var_name) {
							$va_exp_vars[$vs_var_name] = $t_subject_instance->get($vs_var_name, array('convertCodesToIdno' => true));
						}

						if (ExpressionParser::evaluate($vs_exp, $va_exp_vars)) {
							continue 2; // skip this whole log entry! (continue; would skip the subject entry)
						}
					}

					$va_row['subjects'][] = array_replace($qr_subjects->getRow(), array('guid' => $vs_subject_guid));
				}

				$va_ret[(int) $qr_results->get('log_id')] = $va_row;
			}
		}

		return $va_ret;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function logEntryExists($pn_log_id) {
		$o_db = new Db();
		
		$qr_res = $o_db->query("SELECT log_id FROM ca_change_log WHERE log_id = ?", [(int)$pn_log_id]);
		if ($qr_res->nextRow() && $qr_res->get('log_id')) { return true; }
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function logEntryHasAccess($pa_log_entry, $pa_access) {
		$o_db = new Db();
		$o_dm = Datamodel::load();
		
		if (!($t_instance = $o_dm->getInstanceByTableNum($pa_log_entry['logged_table_num'], true))) { return false; }
		if ($t_instance->hasField('access')) { 
			$qr_res = $o_db->query("SELECT access FROM ".$t_instance->tableName()." WHERE ".$t_instance->primaryKey()." = ?", [(int)$pa_log_entry['logged_row_id']]);
			if ($qr_res->nextRow()) {
				if (in_array($vn_access = $qr_res->get('access'), $pa_access)) { return true; }
			}
		}
		
		if (is_array($pa_log_entry['subjects'])) {
			foreach($pa_log_entry['subjects'] as $va_subject) {
				if (!($t_instance = $o_dm->getInstanceByTableNum($va_subject['subject_table_num'], true))) { continue; }
				if ($t_instance->hasField('access')) { 
					$qr_res = $o_db->query("SELECT access FROM ".$t_instance->tableName()." WHERE ".$t_instance->primaryKey()." = ?", [(int)$va_subject['subject_row_id']]);
					if ($qr_res->nextRow()) {
						if (in_array($qr_res->get('access'), $pa_access)) { return true; }
					}
				}
			}
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function rowHasAccess($pn_table_num, $pn_row_id, $pa_access) {
		$o_db = new Db();
		$o_dm = Datamodel::load();
		
		if (!($t_instance = $o_dm->getInstanceByTableNum($pn_table_num, true))) { return false; }
		if ($t_instance->hasField('access')) { 
			$qr_res = $o_db->query("SELECT access FROM ".$t_instance->tableName()." WHERE ".$t_instance->primaryKey()." = ?", [(int)$pn_row_id]);
			if ($qr_res->nextRow()) {
				if (in_array($vn_access = $qr_res->get('access'), $pa_access)) { return true; }
			}
		} else {
			return true;
		}
		
		
		return false;
	}
	# ------------------------------------------------------
}
