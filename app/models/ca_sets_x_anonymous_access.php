<?php
/** ---------------------------------------------------------------------
 * app/models/ca_sets_x_anonymous_access.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BaseRelationshipModel.php');
require_once(__CA_LIB_DIR__.'/ModelSettings.php');
require_once(__CA_MODELS_DIR__.'/ca_sets.php');

BaseModel::$s_ca_models_definitions['ca_sets_x_anonymous_access'] = array(
 	'NAME_SINGULAR' 	=> _t('anonymous access token'),
 	'NAME_PLURAL' 		=> _t('anonymous access tokens'),
 	'FIELDS' 			=> array(
 		'relation_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Relation id', 'DESCRIPTION' => 'Identifier for token'
		),
		'set_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Set id', 'DESCRIPTION' => 'Identifier for set'
		),
		'access' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => __CA_SET_READ_ACCESS__,
			'BOUNDS_CHOICE_LIST' => array(
				_t('has no access') => __CA_SET_NO_ACCESS__,
				_t('can read') => __CA_SET_READ_ACCESS__,
				_t('can edit') => __CA_SET_EDIT_ACCESS__
			),
			'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates level of access to the set. ')
		),
		'guid' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false,
			'DEFAULT' => '',
			'BOUNDS_LENGTH' => [0, 100],
			'LABEL' => _t('guid'), 'DESCRIPTION' => _t('Unique access token.')
		),
		'name' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => true,
			'DEFAULT' => false,
			'BOUNDS_LENGTH' => [0, 255],
			'LABEL' => _t('Name of link'), 'DESCRIPTION' => _t('Descriptive name for token.')
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Display settings')
		),
		'effective_date' => array(
			'FIELD_TYPE' => FT_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'START' => 'sdatetime', 'END' => 'edatetime',
			'LABEL' => _t('Effective date'), 'DESCRIPTION' => _t('Period of time for which this access is in effect. Leave blank if you do not wish to restrict access to a specific period of time.')
		),
		'settings' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Share settings')
		),
 	)
);

class ca_sets_x_anonymous_access extends BaseModel {
	use ModelSettings;
	
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
	protected $TABLE = 'ca_sets_x_anonymous_access';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'relation_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('relation_id');

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
	protected $ORDER_BY = array('relation_id');

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
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			'set_id'
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
	public function __construct($id=null, ?array $options=null) {
		parent::__construct($id, $options);
		
		$this->initSettings();
	}
	# ------------------------------------------------------
	/**
	 * Override insert() to add set token
	 */ 
	public function insert($options=null) {
		$this->set('guid', caGenerateGUID());
		return parent::insert($options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	protected function initSettings() {
		$settings = [
			'download_versions' => [
				'formatType' => FT_TEXT,
				'displayType' => DT_TEXT,
				'width' => 4, 'height' => 1,
				'takesLocale' => false,
				'default' => null,
				'label' => _t('Download versions'),
				'description' => _t('Media versions available for download with this token')
			]
		];
				
		$this->setAvailableSettings($settings);
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($options=null) {
		parent::initLabelDefinitions($options);
		$this->BUNDLES['settings'] = ['type' => 'special', 'repeating' => false, 'label' => _t('Share settings')];
	}
	# ------------------------------------------------------
}
