<?php
/** ---------------------------------------------------------------------
 * app/models/ca_notifications.php : table access class for table ca_notifications
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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

define('__CA_NOTIFICATION_TYPE_GENERIC__', 0);
define('__CA_NOTIFICATION_TYPE_METADATA_ALERT__', 1);
define('__CA_NOTIFICATION_TYPE_URL_REFERENCE_CHECK__', 2);

require_once(__CA_APP_DIR__.'/models/ca_notification_subjects.php');

BaseModel::$s_ca_models_definitions['ca_notifications'] = array(
	'NAME_SINGULAR' 	=> _t('notifications'),
	'NAME_PLURAL' 		=> _t('notifications'),
	'FIELDS' 			=> array(
		'notification_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this notification')
		),
		'notification_type' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => 0,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'BOUNDS_CHOICE_LIST' => array(
				_t('Generic') => __CA_NOTIFICATION_TYPE_GENERIC__,
				_t('Metadata alert') => __CA_NOTIFICATION_TYPE_METADATA_ALERT__,
				_t('Url reference check') => __CA_NOTIFICATION_TYPE_URL_REFERENCE_CHECK__
			),
			'LABEL' => _t('Notification type'), 'DESCRIPTION' => _t('Indicates the type of this notification.')
		),
		'datetime' => array(
			'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Notification date and time'), 'DESCRIPTION' => _t('Date and time for notification')
		),
		'message' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Message'), 'DESCRIPTION' => _t('Notification message')
		),
		'is_system' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'LABEL' => _t('Is system notification'),
			'DESCRIPTION' => _t('Set this if the notification is available system-wide and readable by everyone.'),
			'BOUNDS_VALUE' => array(0,1),
			'REQUIRES' => array('is_administrator')
		),
		'notification_key' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('MD5 hash'), 'DESCRIPTION' => _t('MD5-generated identifier for this notification.'),
			'BOUNDS_LENGTH' => array(0,32)
		),
		'extra_data' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Notification-specific data', 'DESCRIPTION' => 'Additional data attached to this notification'
		)
	)
);

class ca_notifications extends BaseModel {
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
	protected $TABLE = 'ca_notifications';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'notification_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	#
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('table_num', 'row_id');

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
	protected $ORDER_BY = array('notification_id');

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
	 * Static utility to add a notification
	 *
	 * @param int $pn_type
	 * @param string $ps_message
	 * @param array $pa_subjects
	 * @param bool $pb_system
	 * @param array $pa_options
	 * 		datetime --
	 * @return bool
	 */
	public static function add($pn_type, $ps_message, array $pa_subjects, $pb_system=false, array $pa_options = []) {
		$t_notification = new ca_notifications();

		$t_notification->setMode(ACCESS_WRITE);
		$t_notification->set('notification_type', $pn_type);
		$t_notification->set('message', $ps_message);
		$t_notification->set('datetime', caGetOption('datetime', $pa_options, time()));
		$t_notification->set('is_system', $pb_system ? 1 : 0);

		$t_notification->insert();

		if(!$t_notification->getPrimaryKey()) {
			return false;
		}

		foreach($pa_subjects as $va_subject) {
			if(!is_array($va_subject) || !isset($va_subject['table_num']) || !isset($va_subject['row_id'])) {
				continue;
			}

			$t_subject = new ca_notification_subjects();
			$t_subject->setMode(ACCESS_WRITE);

			$t_subject->set('notification_id', $t_notification->getPrimaryKey());
			$t_subject->set('table_num', $va_subject['table_num']);
			$t_subject->set('row_id', $va_subject['row_id']);

			$t_subject->insert();

			if(!$t_subject->getPrimaryKey()) {
				return false;
			}
		}

		return true;
	}
	# ------------------------------------------------------
	/**
	 * Make notification subject as read. An ownership check is performed when notification is attached 
	 * to a ca_users record and a user_id is provided.
	 *
	 * @param int $pn_subject_id 
	 * @param int $pn_user_id An optional user_id. If provided, notifications attached to ca_users records will only be marked as read if the subject user_id matches. [Default is null] 
	 *
	 * @return bool True on success
	 */
	public static function markAsRead($pn_subject_id, $pn_user_id=null) {
		$t_subject = new ca_notification_subjects($pn_subject_id);
		if($t_subject->isLoaded()) {
			if (($t_subject->get('table_num') == 94) && ($pn_user_id) && ((int)$pn_user_id !== (int)$t_subject->get('row_id'))) { // 94 = ca_users
				return false;
			}
			$t_subject->setMode(ACCESS_WRITE);

			$t_subject->set('was_read', 1);
			return $t_subject->update();
		}
		return false;
	}
	# ------------------------------------------------------
}
