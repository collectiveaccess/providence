<?php
/** ---------------------------------------------------------------------
 * app/models/ca_user_sorts.php : table access class for table ca_user_sorts
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

require_once(__CA_MODELS_DIR__.'/ca_user_sort_items.php');

BaseModel::$s_ca_models_definitions['ca_user_sorts'] = array(
	'NAME_SINGULAR' 	=> _t('user sort'),
	'NAME_PLURAL' 		=> _t('user sorts'),
	'FIELDS' 			=> array(
		'sort_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this sort')
		),
		'table_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'DONT_USE_AS_BUNDLE' => true,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'BOUNDS_VALUE' => array(1,255),
			'LABEL' => _t('Sort subject'), 'DESCRIPTION' => _t('Determines what kind of items (objects, entities, places, etc.) are being sorted.'),
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
		'user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
			'DEFAULT' => '',
			'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who created the sort.')
		),
		'name' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Name'), 'DESCRIPTION' => _t('Name of sort'),
			'BOUNDS_LENGTH' => array(1,255)
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Settings')
		),
		'sort_type' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 1, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => '',
			'LABEL' => _t('Action'), 'DESCRIPTION' => _t('Type of sort'),
			'BOUNDS_LENGTH' => array(0,1)
		),
		'rank' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of sort when displayed in a list. Lower numbers indicate higher priority.')
		),
		'deleted' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => 0,
			'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the sort is deleted or not.'),
			'BOUNDS_VALUE' => array(0,1)
		)
	)
);

class ca_user_sorts extends BaseModel {
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
	protected $TABLE = 'ca_user_sorts';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'sort_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('table_num');

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
	protected $ORDER_BY = array('sort_id');

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
	 * Add sort bundle to this sort
	 * @param $ps_bundle_name
	 * @param null|int $pn_rank
	 * @return bool
	 */
	public function addSortBundle($ps_bundle_name, $pn_rank=null) {
		if(!$ps_bundle_name) { return false; }
		if(!$this->getPrimaryKey()) { return false; }

		if(is_null($pn_rank)) {
			$qr_max_rank = $this->getDb()->query('SELECT MAX(rank) AS rnk FROM ca_user_sort_items WHERE sort_id=?', $this->getPrimaryKey());
			if($qr_max_rank->nextRow()) {
				$pn_rank = (int) $qr_max_rank->get('rnk');
			}
		}

		if(!is_int($pn_rank)) { $pn_rank = 0; }

		$this->getDb()->query(
			'INSERT INTO ca_user_sort_items (sort_id, bundle_name, rank) VALUES(?, ?, ?)',
			$this->getPrimaryKey(), $ps_bundle_name, $pn_rank
		);

		return true;
	}
	# ------------------------------------------------------
	/**
	 * Add a list of bundles to this sort
	 * @param array $pa_bundles
	 */
	public function addSortBundles(array $pa_bundles) {
		foreach($pa_bundles as $vn_k => $vs_bundle) {
			$vn_rank = null;
			if(is_numeric($vn_k)) { $vn_rank = $vn_k; }

			$this->addSortBundle($vs_bundle, $vn_rank);
		}
	}
	# ------------------------------------------------------
	/**
	 * Get list of sort bundles for this sort
	 * @return bool
	 */
	public function getSortBundles() {
		if(!$this->getPrimaryKey()) { return false; }

		$qr_sort_bundles = $this->getDb()->query('SELECT * FROM ca_user_sort_items WHERE sort_id=? ORDER BY rank, item_id', $this->getPrimaryKey());

		return $qr_sort_bundles->getAllRows();
	}
	# ------------------------------------------------------
	/**
	 * Get list of sort bundle names for this sort
	 * @return array|bool
	 */
	public function getSortBundleNames() {
		if(!$this->getPrimaryKey()) { return false; }

		$qr_sort_bundles = $this->getDb()->query('SELECT bundle_name,rank FROM ca_user_sort_items WHERE sort_id=? ORDER BY rank, item_id', $this->getPrimaryKey());

		$va_sort_bundle_names = array();
		while($qr_sort_bundles->nextRow()) {
			$va_sort_bundle_names[(int) $qr_sort_bundles->get('rank')] = $qr_sort_bundles->get('bundle_name');
		}

		return $va_sort_bundle_names;
	}
	# ------------------------------------------------------
	public function updateBundleNameAtRank($pn_rank, $ps_bundle_name) {
		$qr_sort_bundles = $this->getDb()->query('SELECT bundle_name FROM ca_user_sort_items WHERE sort_id=? AND rank=?', $this->getPrimaryKey(), $pn_rank);
		if($qr_sort_bundles->numRows() > 1) {
			return false;
		} elseif($qr_sort_bundles->numRows() == 1) {
			$this->getDb()->query('UPDATE ca_user_sort_items SET bundle_name=? WHERE sort_id=? AND rank=?', $ps_bundle_name, $this->getPrimaryKey(), $pn_rank);
		} else {
			$this->addSortBundle($ps_bundle_name, $pn_rank);
		}
	}
	# ------------------------------------------------------
	public function removeAllBundles() {
		if(!$this->getPrimaryKey()) { return false; }

		 $this->getDb()->query('DELETE FROM ca_user_sort_items WHERE sort_id=?', $this->getPrimaryKey());
		return true;
	}
	# ------------------------------------------------------
	public function removeBundleByItemID($pn_item_id) {
		if(!$this->getPrimaryKey()) { return false; }

		$this->getDb()->query('DELETE FROM ca_user_sort_items WHERE sort_id=? AND item_id=?', $this->getPrimaryKey(), $pn_item_id);
		return true;
	}
	# ------------------------------------------------------
	public static function getAvailableSortsForTable($pn_table_num) {
		if(!is_numeric($pn_table_num)) {
			$pn_table_num = Datamodel::getTableNum($pn_table_num);
		}

		if(!$pn_table_num) { return array(); }

		$o_db = new Db();

		$qr_sorts = $o_db->query('SELECT * FROM ca_user_sorts WHERE table_num=? AND deleted=0 ORDER BY rank', $pn_table_num);

		$va_sorts = array();
		while($qr_sorts->nextRow()) {
			$t_sort = new ca_user_sorts($qr_sorts->get('sort_id'));
			if(!$t_sort->getPrimaryKey()) { continue; }

			if($va_bundles = $t_sort->getSortBundleNames()) {
				$va_sorts[join(';', $va_bundles)] = $qr_sorts->get('name');
			}
		}

		return $va_sorts;
	}
	# ------------------------------------------------------
	public static function getAvailableSortsAsList() {
		$o_db = new Db();
		$qr_sorts = $o_db->query('SELECT * FROM ca_user_sorts WHERE deleted=0 ORDER BY table_num, rank');

		return $qr_sorts->getAllRows();
	}
	# ------------------------------------------------------
}
