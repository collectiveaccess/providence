<?php
/** ---------------------------------------------------------------------
 * app/models/ca_item_comments.php : table access class for table ca_item_comments
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2014 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');


BaseModel::$s_ca_models_definitions['ca_item_comments'] = array(
 	'NAME_SINGULAR' 	=> _t('comment'),
 	'NAME_PLURAL' 		=> _t('comments'),
 	'FIELDS' 			=> array(
 		'comment_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this comment')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Table comment applies to', 'DESCRIPTION' => 'The table number of the table this comment is applied to.',
				'BOUNDS_VALUE' => array(1,255)
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
				'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who created the comment.')
		),
		'row_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Row ID', 'DESCRIPTION' => 'Primary key value of the row in the table specified by table_num that this comment applies to.'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale of the comment.')
		),
		'comment' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Comment'), 'DESCRIPTION' => _t('Text of comment.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'email' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('E-mail address'), 'DESCRIPTION' => _t('E-mail address of commentor.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Name'), 'DESCRIPTION' => _t('Name of commenter.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'location' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Location'), 'DESCRIPTION' => _t('Location of commenter.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'rating' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'BOUNDS_CHOICE_LIST' => array(
					'-' => null,
					'1' => 1,
					'2' => 2,
					'3' => 3,
					'4' => 4,
					'5' => 5
				),
				'LABEL' => _t('Rating'), 'DESCRIPTION' => _t('User-provided rating for item.')
		),
		'media1' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
				'LABEL' => _t('Media to upload'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media2' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
				'LABEL' => _t('Media to upload'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media3' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
				'LABEL' => _t('Media to upload'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media4' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
				'LABEL' => _t('Media to upload'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if the comment is accessible to the public or not.')
		),
		'created_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Comment creation date'), 'DESCRIPTION' => _t('The date and time the comment was created.')
		),
		'ip_addr' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('IP address of commenter'), 'DESCRIPTION' => _t('The IP address of the commenter.'),
				'BOUNDS_LENGTH' => array(0,39)
		),
		'moderated_by_user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => null,
				'LABEL' => _t('Moderator'), 'DESCRIPTION' => _t('The user who examined the comment for validity and applicability.')
		),
		'moderated_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => null,
				'LABEL' => _t('Moderation date'), 'DESCRIPTION' => _t('The date and time the comment was examined for validity and applicability.')
		)
 	)
);

class ca_item_comments extends BaseModel {
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
	protected $TABLE = 'ca_item_comments';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'comment_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('name', 'email', 'comment');

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
	protected $ORDER_BY = array('name');

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
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ItemCommentSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ItemCommentSearchResult';
	
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
	public function insert($pa_options=null) {
		$this->set('ip_addr', $_SERVER['REMOTE_ADDR']);
		return parent::insert($pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Marks the currently loaded row as moderated, setting the moderator as the $pn_user_id parameter and the moderated time as the current time.
	 * "Moderated" status indicates that the comment has been reviewed for content; it does *not* indicate that the comment is ok for publication only
	 * that is has been reviewed. The publication status is indicated by the value of the 'access' field.
	 *
	 * @param $pn_user_id [integer] Valid ca_users.user_id value indicating the user who moderated the comment.
	 */
	public function moderate($pn_user_id) {
		if (!$this->getPrimaryKey()) { return null; }
		$this->setMode(ACCESS_WRITE);
		$this->set('moderated_by_user_id', $pn_user_id);
		$this->set('moderated_on', 'now');
		return $this->update();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedCommentCount() {
		return $this->getCommentCount('unmoderated');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedComments() {
		return $this->getComments('unmoderated');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getModeratedCommentCount() {
		return $this->getCommentCount('moderated');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCommentCount($ps_mode='') {
		$vs_where = '';
		switch($ps_mode) {
			case 'unmoderated':
				$vs_where = 'WHERE cic.moderated_on IS NULL';
				break;
			case 'moderated':
				$vs_where = 'WHERE cic.moderated_on IS NOT NULL';
				break;
		}
	
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_item_comments cic
			{$vs_where}
		");
		
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	public function getModeratedComments() {
		return $this->getComments('moderated');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getComments($ps_mode='', $pn_limit=0) {
		$o_db = $this->getDb();
		
		$vs_where = '';
		switch($ps_mode){ 
			case 'moderated':
				$vs_where = "WHERE cic.moderated_on IS NOT NULL";
				break;
			case 'unmoderated':
				$vs_where = "WHERE cic.moderated_on IS NULL";
				break;
		}
		if(intval($pn_limit) > 0){
			$vs_limit = " LIMIT ".intval($pn_limit);
		}
		
		
		$o_tep = new TimeExpressionParser();
		$qr_res = $o_db->query("
			SELECT cic.*, u.user_id, u.fname, u.lname, u.email user_email
			FROM ca_item_comments cic
			INNER JOIN ca_users AS u ON u.user_id = cic.user_id
			{$vs_where} ORDER BY cic.created_on DESC {$vs_limit}
		");
		
		$o_datamodel = $this->getAppDatamodel();
		
		$va_comments = array();
		while($qr_res->nextRow()) {
			$vn_datetime = $qr_res->get('created_on');
			$o_tep->setUnixTimestamps($vn_datetime, $vn_datetime);
			
			$va_row = $qr_res->getRow();
			$va_row['created_on'] = $o_tep->getText();
			
			$t_table = $o_datamodel->getInstanceByTableNum($qr_res->get('table_num'), true);
			if ($t_table->load($qr_res->get('row_id'))) {
				$va_row['commented_on'] = $t_table->getLabelForDisplay(false);
				if ($vs_idno = $t_table->get('idno')) {
					$va_row['commented_on'] .= ' ['.$vs_idno.']';
				}
			}
			foreach(array("media1", "media2", "media3", "media4") as $vs_media_field){
				$va_media_versions = array();
				$va_media_versions = $qr_res->getMediaVersions($vs_media_field);
				$va_media = array();
				if(is_array($va_media_versions) && (sizeof($va_media_versions) > 0)){
					foreach($va_media_versions as $vs_version){
						$va_image_info = array();
						$va_image_info  = $qr_res->getMediaInfo($vs_media_field, $vs_version);
						$va_image_info["TAG"] = $qr_res->getMediaTag($vs_media_field, $vs_version);
						$va_image_info["URL"] = $qr_res->getMediaUrl($vs_media_field, $vs_version);
						$va_media[$vs_version] = $va_image_info;
					}
					$va_row[$vs_media_field] = $va_media;
				}
			}
			$va_comments[] = $va_row;
		}
		return $va_comments;
		
	}
	# ------------------------------------------------------
}
?>