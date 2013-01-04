<?php
/** ---------------------------------------------------------------------
 * app/models/ca_item_tags.php : table access class for table ca_item_tags
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/BaseModel.php');


BaseModel::$s_ca_models_definitions['ca_item_tags'] = array(
 	'NAME_SINGULAR' 	=> _t('tag'),
 	'NAME_PLURAL' 		=> _t('tags'),
 	'FIELDS' 			=> array(
 		'tag_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this tag')
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale of the tag.')
		),
		'tag' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Tag'), 'DESCRIPTION' => _t('Text of the tag.'),
				'BOUNDS_LENGTH' => array(1,255)
		)
 	)
);

class ca_item_tags extends BaseModel {
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
	protected $TABLE = 'ca_item_tags';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'tag_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('tag');

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
	protected $ORDER_BY = array('tag');

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
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = null;
	
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
	 *
	 */
	public function getModeratedTags($pn_limit=0) {
		return $this->getAllTags(false, $pn_limit);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedTags($pn_limit=0) {
		return $this->getAllTags(true, $pn_limit);
	}
	# ------------------------------------------------------
	/**
	 * Returns all tags attached to any kind of row.
	 * If the optional $pb_moderation_status parameter is passed then only tags matching the criteria will be returned:
	 *		Passing $pb_moderation_status = TRUE will cause only moderated tags to be returned
	 *		Passing $pb_moderation_status = FALSE will cause only unmoderated tags to be returned
	 *		If you want both moderated and unmoderated tags to be returned then omit the parameter or pass a null value
	 *
	 * @param bool $pb_moderation_status To return only unmoderated tags set to FALSE; to return only moderated tags set to TRUE; to return all tags set to null or omit
	 * @param int $pn_limit Maximum number of tags to return. Default is 0 - no limit.
	 * 
	 * @return array
	 */
	public function getAllTags($pb_moderation_status=null, $pn_limit=0) {
		$o_db = $this->getDb();
		
		$vs_where = '';
		
		if ($pb_moderation_status === true) {
			$vs_where = ' WHERE cixt.moderated_on IS NULL';
		} elseif($pb_moderation_status === false) {
			$vs_where = ' WHERE cixt.moderated_on IS NOT NULL';
		} else {
			$vs_where = '';
		}
		$vs_limit = "";
		if(intval($pn_limit) > 0){
			$vs_limit = " LIMIT ".intval($pn_limit);
		}
		
		$o_tep = new TimeExpressionParser();
		$qr_res = $o_db->query("
			SELECT cit.*, cixt.*, u.user_id, u.fname, u.lname, u.email user_email
			FROM ca_items_x_tags cixt
			INNER JOIN ca_users AS u ON u.user_id = cixt.user_id
			INNER JOIN ca_item_tags AS cit ON cit.tag_id = cixt.tag_id
			{$vs_where} ORDER BY cixt.created_on DESC {$vs_limit}
		");
		
		$o_datamodel = $this->getAppDatamodel();
		
		$va_tags = array();
		while($qr_res->nextRow()) {
			$vn_datetime = $qr_res->get('created_on');
			$o_tep->setUnixTimestamps($vn_datetime, $vn_datetime);
			
			$va_row = $qr_res->getRow();
			$va_row['created_on'] = $o_tep->getText();
			
			$t_table = $o_datamodel->getInstanceByTableNum($qr_res->get('table_num'), true);
			if ($t_table->load($qr_res->get('row_id'))) {
				$va_row['item_tagged'] = $t_table->getLabelForDisplay(false);
				if ($vs_idno = $t_table->get('idno')) {
					$va_row['item_tagged'] .= ' ['.$vs_idno.']';
				}
			}
			
			$va_tags[] = $va_row;
		}
		return $va_tags;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedTagCount() {
		return $this->getTagCount('unmoderated');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getModeratedTagCount() {
		return $this->getTagCount('moderated');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getTagCount($ps_mode='') {
		$vs_where = '';
		switch($ps_mode) {
			case 'unmoderated':
				$vs_where = 'WHERE moderated_on IS NULL';
				break;
			case 'moderated':
				$vs_where = 'WHERE moderated_on IS NOT NULL';
				break;
		}
	
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_items_x_tags
			{$vs_where}
		");
		
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemTagsCount() {
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_item_tags
		");
		
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
}
?>