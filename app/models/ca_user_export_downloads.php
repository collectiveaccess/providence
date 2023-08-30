<?php
/** ---------------------------------------------------------------------
 * app/models/ca_user_export_downloads.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
BaseModel::$s_ca_models_definitions['ca_user_export_downloads'] = array(
	'NAME_SINGULAR' 	=> _t('export download'),
	'NAME_PLURAL' 		=> _t('export downloads'),
	'FIELDS' 			=> array(
		'download_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this log entry')
		),
		'user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted by user'), 'DESCRIPTION' => _t('User submitting this upload.')
		),
		'status' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 30, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'BOUNDS_LENGTH' => [0, 30],
			'DEFAULT' => 'IN_PROGRESS',
			'BOUNDS_CHOICE_LIST' => array(
				_t('Queued') => 'QUEUED',
				_t('Processing') => 'PROCESSING',
				_t('Available for download') => 'COMPLETE',
				_t('Downloaded') => 'DOWNLOADED',
				_t('Error') => 'ERROR'
			),
			'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Status of export. Possible states: QUEUED, PROCESSING, COMPLETE, DOWNLOADED, ERROR.')
		),
		'download_type' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 30, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'BOUNDS_LENGTH' => [0, 30],
			'DEFAULT' => 'IN_PROGRESS',
			'BOUNDS_CHOICE_LIST' => array(
				_t('Summary') => 'SUMMARY',
				_t('Results') => 'RESULTS',
				_t('Labels') => 'LABELS',
				_t('Set') => 'SET'
			),
			'LABEL' => _t('Download type'), 'DESCRIPTION' => _t('Type of export download. Possible values are: SUMMARY, RESULTS, LABELS, SET')
		),
		'created_on' => array(
			'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => null,
			'LABEL' => _t('Creation date'), 'DESCRIPTION' => _t('The date and time the export was requested.')
		),
		'generated_on' => array(
			'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'LABEL' => _t('Submission  date'), 'DESCRIPTION' => _t('The date and time the export was generated.')
		),
		'downloaded_on' => array(
			'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'LABEL' => _t('Upload completion date'), 'DESCRIPTION' => _t('The date and time the export was first downloaded on.')
		),
		'metadata' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('Associated metadata'), 'DESCRIPTION' => _t('Metadata for export.')
		),
		'export_file' => array(
			'FIELD_TYPE' => FT_FILE, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'FILE_VOLUME' => 'workspace',
			'LABEL' => _t('Export file'), 'DESCRIPTION' => _t('Data export file.')
		)
	)
);

class ca_user_export_downloads extends BaseModel {
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
	protected $TABLE = 'ca_user_export_downloads';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'download_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('download_id', 'user_id', 'status');

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
	protected $ORDER_BY = array('download_id', 'source_system_guid');

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
		"FOREIGN_KEYS" => [],
		"RELATED_TABLES" => []
	);
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	/**
	 * Returns number of downloads conforming to specification in options
	 *
	 * @param array $options Optional array of options. Supported options are:
	 *		user_id = Restricts returned forms to those accessible by the current user. If omitted then all forms, regardless of access are returned.
	 * @return int  Number of downloads available
	 */
	public function getDownloadCount($options=null) {
		if (!is_array($options)) { $options = []; }

		$downloads = $this->getDownloads($options);

		if (is_array($downloads)) { return sizeof($downloads); } else { return 0; }
	}
	# ------------------------------------------------------
	/**
	 * Returns list of downloads subject to options
	 *
	 * @param array $options Optional array of options. Supported options are:
	 *			user_id = Restricts returned forms to those accessible by the current user. If omitted then all forms, regardless of access are returned. [Default is null]
	 * @return array Array of downloads keyed on download_id. Each download is represented by an array, whose keys include: download_id, created_on, generated_on, user_id, download_type, ...)
	 */
	public function getDownloads($options=null) {
		if (!is_array($options)) { $options = []; }
		$user_id = caGetOption('user_id', $options, null);

		$o_db = $this->getDb();

		$wheres = [];
		$params = [];
		if ($user_id > 0) {
			$wheres[] = "(d.user_id = ?)";
			$params[] = $user_id;
		}

		

		// get downloads
		$qr_res = $o_db->query("
			SELECT d.*, u.email, u.fname, u.lname, u.user_name
			FROM ca_user_export_downloads d
			INNER JOIN ca_users AS u ON u.user_id = d.user_id
			".(sizeof($wheres) ? 'WHERE ' : '')."
			".join(' AND ', $wheres)."
			ORDER BY d.created_on  
		", $params);
		
		$downloads = [];

		while($qr_res->nextRow()) {
			$downloads[(int)$qr_res->get('download_id')] = $qr_res->getRow();
		}
		return $downloads;
	}
	# ------------------------------------------------------
}
