<?php
/** ---------------------------------------------------------------------
 * app/models/ca_ip_whitelist.php
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
BaseModel::$s_ca_models_definitions['ca_ip_whitelist'] = array(
 	'NAME_SINGULAR' 	=> _t('IP-based authentication whitelist entries'),
 	'NAME_PLURAL' 		=> _t('IP-based authentication whitelist entries'),
 	'FIELDS' 			=> array(
 		'whitelist_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '','LABEL' => _t('Whitelist id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this IP address block')
		),
		'ip_addr' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('IP address of user'), 'DESCRIPTION' => _t('The IP address of the user.'),
				'BOUNDS_LENGTH' => array(0,39)
		),
		'reason' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Reason'), 'DESCRIPTION' => _t('Reason for whitelisting'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'created_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Ban creation date'), 'DESCRIPTION' => _t('The date and time the whitelist entry was created.')
		),
		'expires_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Ban expiration date'), 'DESCRIPTION' => _t('The date and time the whitelist entry expires on. An empty value indicates indefinite whitelisting.')
		),
 	)
);

class ca_ip_whitelist extends BaseModel {
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
	protected $TABLE = 'ca_ip_whitelist';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'whitelist_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('ip_addr');

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
	protected $ORDER_BY = array('ip_addr');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	/**
	 *
	 */
	private static $config;
	
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
	 *
	 */
	static public function init() {
		if(!self::$config) { self::$config = Configuration::load("ban_hammer.conf"); }
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function whitelist($request, $ttl=null, $reason=null) {
		self::init();
		if (!($ip = RequestHTTP::ip())) { return false; }
		if (self::isWhitelisted()) { return true; } 
		
		$ban = new ca_ip_whitelist();
		$ban->set('ip_addr', $ip);
		$ban->set('reason', $reason);
		$ban->set('expires_on', $ttl ? date('c', time() + $ttl) : null);
		if($rc = $ban->insert()) {
			// remove any exiting bans on now-whitelisted user
			ca_ip_bans::removeBans(['ip' => $ip]);
		}
		return $rc;
	}
	# ------------------------------------------------------
	/**
	 * Clean expired bans. If 'all' option is passed all bans will be removed
	 * regardless of expiration.
	 *
	 * @param array $options Options include:
	 *		all = Remove all bans. [Default is false]
	 */
	static public function clean($options=null) {
		self::init();
		$db = new Db();
		if (caGetOption('all', $options, false)) {
			return $db->query("TRUNCATE TABLE ca_ip_whitelist");
		}
		return $db->query("DELETE FROM ca_ip_whitelist WHERE expired_on <= ?", [time()]);
	}
	# ------------------------------------------------------
	/**
	 * Remove existing bans. Options may be used to limit which bans are cleared based upon reason
	 * and/or creation date/time. If reasons and/or date/time is passed and is not valid
	 * no bans will be removed and null will be returned. If bans are successfully removed the
	 * number removed is returned. 
	 *
	 * @param array $options Options include:
	 *		from = Remove all bans created before the specified date. Value is any valid date/time expression. [Default is null]
	 *		reasons = Clear bans with specific reasons. Value is an array or comma separated list of ban reasons. [Default is null]
	 * 
	 * @return int
	 */
	static public function removeWhitelistEntries(?array $options=null) : ?int {
		self::init();
		$db = new Db();
		
		if($from = caGetOption('from', $options, null)) {
			if(!($from = caDateToUnixTimestamp($from))) { return null; }
		}
		if($reasons = caGetOption('reasons', $options, null)) {
			if(!is_array($reasons)) { $reasons = preg_split('/[;,]/', $reasons); }
			$valid_reasons = array_map('strtolower', self::validReasons());
			$reasons = array_filter($reasons, function($v) use ($valid_reasons) {
				return in_array(strtolower($v), $valid_reasons, true);
			});
			if(!sizeof($reasons)) { return null; }
		}
		
		if (!$reasons && !$from) {
			if($db->query("DELETE FROM ca_ip_whitelist")) {
				return $db->affectedRows();
			}
			return null;
		}
		
		$wheres = $params = [];
		if($reasons) {
			$wheres[] = "(reason IN (?))";
			$params[] = $reasons;
		}
		if($from > 0) {
			$wheres[] = "(created_on < ?)";
			$params[] = $from;
		}
		
		if($db->query("DELETE FROM ca_ip_whitelist WHERE ".join(' AND ', $wheres), $params)) {
			return $db->affectedRows();
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function isWhitelisted($options=null) {
		return ca_ip_bans::isWhitelisted($options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function validReasons() {
		return ['CAPTCHA'];
	}
	# ------------------------------------------------------
}
