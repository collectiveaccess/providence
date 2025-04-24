<?php
/** ---------------------------------------------------------------------
 * app/models/ca_search_indexing_queue.php : table access class for table ca_search_indexing_queue
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Db.php');
require_once(__CA_LIB_DIR__.'/Search/SearchIndexer.php');
require_once(__CA_LIB_DIR__.'/Utils/LockingTrait.php');

BaseModel::$s_ca_models_definitions['ca_search_indexing_queue'] = array(
	'NAME_SINGULAR' 	=> _t('search indexing queue entry'),
	'NAME_PLURAL' 		=> _t('search indexing queue entries'),
	'FIELDS' 			=> array(
		'entry_id' => array(
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
			'LABEL' => 'Table', 'DESCRIPTION' => 'Table to index for search',
			'BOUNDS_VALUE' => array(0,255)
		),
		'row_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Row id', 'DESCRIPTION' => 'Identifier of row to index for search'
		),
		'field_data' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Field data', 'DESCRIPTION' => 'Field data'
		),
		'reindex' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => 0,
			'OPTIONS' => array(
				_t('Yes') => 1,
				_t('No') => 0
			),
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => 'Reindex?', 'DESCRIPTION' => 'Indicates if this is a full reindex for this row'
		),
		'changed_fields' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Changed fields', 'DESCRIPTION' => 'Changed fields'
		),
		'options' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Options', 'DESCRIPTION' => 'Options'
		),
		'is_unindex' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => 0,
			'OPTIONS' => array(
				_t('Yes') => 1,
				_t('No') => 0
			),
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => 'Is unindex?', 'DESCRIPTION' => 'Indicates if this is a unindexing instruction'
		),
		'dependencies' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => 'Dependencies', 'DESCRIPTION' => 'Dependencies to unindex (only set if is_unindex = 1)'
		),
		'started_on' => array(
            'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
            'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
            'IS_NULL' => true, 
            'DEFAULT' => '',
            'LABEL' => _t('Started on'), 'DESCRIPTION' => _t('Started on')
		)
	)
);

class ca_search_indexing_queue extends BaseModel {
	use LockingTrait;
	
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
	protected $TABLE = 'ca_search_indexing_queue';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'entry_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('field_data');

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
	protected $ORDER_BY = array('field_data');

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
	/**
	 *
	 */
	static public function process() {
		if(self::lockAcquire()) {
		    set_time_limit(self::lockTimeout());
			$o_db = new Db();
			
			do {
				$num_entries = 0;
				if ($o_result = $o_db->query("SELECT * FROM ca_search_indexing_queue WHERE started_on IS NULL ORDER BY entry_id LIMIT 10")) {
					$num_entries = (int)$o_result->numRows();
					if($num_entries > 0) {
						$o_si = new SearchIndexer($o_db);

						while ($o_result->nextRow()) {
							$o_db->query('UPDATE ca_search_indexing_queue SET started_on = ? WHERE entry_id = ?', [time(), (int)$o_result->get('entry_id')]);
							if(!$o_result->get('is_unindex')) { // normal indexRow() call
								$o_si->indexRow(
									$o_result->get('table_num'), $o_result->get('row_id'),
									caUnserializeForDatabase($o_result->get('field_data')),
									(bool)$o_result->get('reindex'), null,
									caUnserializeForDatabase($o_result->get('changed_fields')),
									array_merge(caUnserializeForDatabase($o_result->get('options')), array('queueIndexing' => false))
								);
							} else { // is_unindex = 1, so it's a commitRowUnindexing() call
								$o_si->commitRowUnIndexing(
									$o_result->get('table_num'), $o_result->get('row_id'),
									['queueIndexing' => false, 'dependencies' => caUnserializeForDatabase($o_result->get('dependencies'))]
								);
							}

							$o_db->query('DELETE FROM ca_search_indexing_queue WHERE entry_id = ?', [$o_result->get('entry_id')]);
							self::lockRenew();
						}
					}
				}
			} while($num_entries > 0);

			self::lockRelease();
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function count() {
		$o_db = new Db();
		if($qr = $o_db->query("SELECT count(*) c FROM ca_search_indexing_queue")) {
			$qr->nextRow();
			return (int)$qr->get('c');
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function hasEntries() {
		$o_db = new Db();
		if($qr = $o_db->query("SELECT entry_id FROM ca_search_indexing_queue WHERE started_on IS NULL LIMIT 1")) {
			if(!$qr->nextRow()) { return false; }
			return (bool)$qr->get('entry_id');
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function isRunning() {
		return self::lockExists();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function flush() {
		$o_db = new Db();
		$o_db->query("DELETE FROM ca_search_indexing_queue");
	}
	# ------------------------------------------------------
}
