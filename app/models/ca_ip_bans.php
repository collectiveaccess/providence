<?php
/** ---------------------------------------------------------------------
 * app/models/ca_ip_bans.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage models
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

BaseModel::$s_ca_models_definitions['ca_ip_bans'] = array(
	'NAME_SINGULAR' => _t( 'IP-based authentication block' ),
	'NAME_PLURAL'   => _t( 'IP-based authentication blocks' ),
	'FIELDS'        => array(
		'ban_id'     => array(
			'FIELD_TYPE'     => FT_NUMBER,
			'DISPLAY_TYPE'   => DT_HIDDEN,
			'IDENTITY'       => true,
			'DISPLAY_WIDTH'  => 10,
			'DISPLAY_HEIGHT' => 1,
			'IS_NULL'        => false,
			'DEFAULT'        => '',
			'LABEL'          => _t( 'CollectiveAccess id' ),
			'DESCRIPTION'    => _t( 'Unique numeric identifier used by CollectiveAccess internally to identify this IP address block' )
		),
		'ip_addr'    => array(
			'FIELD_TYPE'     => FT_TEXT,
			'DISPLAY_TYPE'   => DT_FIELD,
			'DISPLAY_WIDTH'  => 40,
			'DISPLAY_HEIGHT' => 1,
			'IS_NULL'        => false,
			'DEFAULT'        => '',
			'LABEL'          => _t( 'IP address of commenter' ),
			'DESCRIPTION'    => _t( 'The IP address of the commenter.' ),
			'BOUNDS_LENGTH'  => array( 0, 39 )
		),
		'reason'     => array(
			'FIELD_TYPE'     => FT_TEXT,
			'DISPLAY_TYPE'   => DT_FIELD,
			'DISPLAY_WIDTH'  => 88,
			'DISPLAY_HEIGHT' => 15,
			'IS_NULL'        => false,
			'DEFAULT'        => '',
			'LABEL'          => _t( 'Reason' ),
			'DESCRIPTION'    => _t( 'Reason for ban' ),
			'BOUNDS_LENGTH'  => array( 0, 255 )
		),
		'created_on' => array(
			'FIELD_TYPE'     => FT_TIMESTAMP,
			'DISPLAY_TYPE'   => DT_FIELD,
			'DISPLAY_WIDTH'  => 20,
			'DISPLAY_HEIGHT' => 1,
			'IS_NULL'        => false,
			'DEFAULT'        => '',
			'LABEL'          => _t( 'Ban creation date' ),
			'DESCRIPTION'    => _t( 'The date and time the ban was created.' )
		),
		'expires_on' => array(
			'FIELD_TYPE'     => FT_DATETIME,
			'DISPLAY_TYPE'   => DT_FIELD,
			'DISPLAY_WIDTH'  => 20,
			'DISPLAY_HEIGHT' => 1,
			'IS_NULL'        => true,
			'DEFAULT'        => '',
			'LABEL'          => _t( 'Ban expiration date' ),
			'DESCRIPTION'    => _t( 'The date and time the ban expires on. An empty value indicates an indefinite ban.' )
		),
	)
);

class ca_ip_bans extends BaseModel {
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
	protected $TABLE = 'ca_ip_bans';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'ban_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array( 'ip_addr' );

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
	protected $ORDER_BY = array( 'ip_addr' );

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
	protected $HIERARCHY_TYPE = null;
	protected $HIERARCHY_LEFT_INDEX_FLD = null;
	protected $HIERARCHY_RIGHT_INDEX_FLD = null;
	protected $HIERARCHY_PARENT_ID_FLD = null;
	protected $HIERARCHY_DEFINITION_TABLE = null;
	protected $HIERARCHY_ID_FLD = null;
	protected $HIERARCHY_POLY_TABLE = null;

	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT
		= array(
			"FOREIGN_KEYS"   => [],
			"RELATED_TABLES" => []
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
	public function __construct( $pn_id = null ) {
		parent::__construct( $pn_id );    # call superclass constructor
	}
	# ------------------------------------------------------

	/**
	 *
	 */
	static public function init() {
		if ( ! self::$config ) {
			self::$config = Configuration::load( "ban_hammer.conf" );
		}
	}
	# ------------------------------------------------------

	/**
	 *
	 */
	static public function ban( $request, $ttl = null, $reason = null ) {
		self::init();
		if ( ! ( $ip = RequestHTTP::ip() ) ) {
			return false;
		}
		if ( self::isWhitelisted() ) {
			return false;
		}

		if ( self::isBanned( $request ) ) {
			return true;
		}
		$ban = new ca_ip_bans();
		$ban->setMode( ACCESS_WRITE );
		$ban->set( 'ip_addr', $ip );
		$ban->set( 'reason', $reason );
		$ban->set( 'expires_on', $ttl ? date( 'c', time() + $ttl ) : null );

		return $ban->insert();
	}
	# ------------------------------------------------------

	/**
	 *
	 */
	static public function isBanned( $request ) {
		self::init();
		$ip = RequestHTTP::ip();
		if ( ! ( $entries = self::find( [ 'ip_addr' => $ip, 'expires_on' => null ], [ 'returnAs' => 'count' ] ) ) ) {
			$entries = self::find( [ 'ip_addr' => $ip, 'expires_on' => [ '>', time() ] ], [ 'returnAs' => 'count' ] );
		}
		if ( $entries > 0 ) {
			return true;
		}

		return false;
	}
	# ------------------------------------------------------

	/**
	 *
	 */
	static public function clean( $options = null ) {
		self::init();
		$db = new Db();
		if ( caGetOption( 'all', $options, false ) ) {
			return $db->query( "TRUNCATE TABLE ca_ip_bans" );
		}

		return $db->query( "DELETE FROM ca_ip_bans WHERE expired_on <= ?", [ time() ] );
	}
	# ------------------------------------------------------

	/**
	 *
	 */
	static public function isWhitelisted( $options = null ) {
		self::init();
		if ( ! is_array( $whitelist = self::$config->get( 'ip_whitelist' ) ) || ! sizeof( $whitelist ) ) {
			return false;
		}

		$request_ip      = RequestHTTP::ip();
		$request_ip_long = ip2long( $request_ip );

		foreach ( $whitelist as $ip ) {
			$ip_s = ip2long( str_replace( "*", "0", $ip ) );
			$ip_e = ip2long( str_replace( "*", "255", $ip ) );
			if ( ( $request_ip_long >= $ip_s ) && ( $request_ip_long <= $ip_e ) ) {
				return true;
			}
		}

		return false;
	}
	# ------------------------------------------------------
}
