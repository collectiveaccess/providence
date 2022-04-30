<?php
/** ---------------------------------------------------------------------
 * app/models/ca_item_comments.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2019 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/BaseModel.php');
require_once(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser.php');


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
	public function __construct($id=null) {
		parent::__construct($id);	# call superclass constructor
	}
	# ------------------------------------------------------
	public function insert($options=null) {
		$this->set('ip_addr', RequestHTTP::ip());
		return parent::insert($options);
	}
	# ------------------------------------------------------
	/**
	 * Marks the currently loaded row as moderated, setting the moderator as the $pn_user_id parameter and the moderated time as the current time.
	 * "Moderated" status indicates that the comment has been reviewed for content; it does *not* indicate that the comment is ok for publication only
	 * that is has been reviewed. The publication status is indicated by the value of the 'access' field.
	 *
	 * @param $pn_user_id [integer] Valid ca_users.user_id value indicating the user who moderated the comment.
	 */
	public function moderate($user_id) {
		if (!$this->getPrimaryKey()) { return null; }
		$this->setMode(ACCESS_WRITE);
		$this->set('moderated_by_user_id', $user_id);
		$this->set('moderated_on', TimeExpressionParser::nowExpression());
		return $this->update();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedCommentCount() {
		return $this->getCommentCount('unmoderated', true);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedComments($options=null) {
		return $this->getCommentsList('unmoderated', null, true, $options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getModeratedCommentCount() {
		return $this->getCommentCount('moderated', true);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCommentCount($mode='', $has_comment = true) {
		$vs_where = '';
		$va_wheres = array();
		switch($mode) {
			case 'unmoderated':
				$va_wheres[] = 'cic.moderated_on IS NULL';
				break;
			case 'moderated':
				$va_wheres[] = 'cic.moderated_on IS NOT NULL';
				break;
		}
	
		if($has_comment){
			$va_wheres[] = "cic.comment IS NOT NULL";
		}
		if(sizeof($va_wheres)){
			$vs_where = "WHERE ".join(" AND ", $va_wheres);
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
	public function getModeratedComments($options=null) {
		return $this->getCommentsList('moderated', null, true, $options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCommentsList($mode='', $limit=0, $has_comment = true, $options=null) {
	    $return_as = caGetOption('returnAs', $options, null);
		$o_db = $this->getDb();
		
		$vs_where = '';
		$va_wheres = array();
		switch($mode){ 
			case 'moderated':
				$va_wheres[] = "cic.moderated_on IS NOT NULL";
				break;
			case 'unmoderated':
				$va_wheres[] = "cic.moderated_on IS NULL";
				break;
		}
		if(intval($limit) > 0){
			$vs_limit = " LIMIT ".intval($limit);
		}
		if($has_comment){
			$va_wheres[] = "cic.comment IS NOT NULL";
		}
		if(sizeof($va_wheres)){
			$vs_where = "WHERE ".join(" AND ", $va_wheres);
		}	
		
		$o_tep = new TimeExpressionParser();
		$qr_res = $o_db->query("
			SELECT cic.*, u.user_id, u.fname, u.lname, u.email user_email
			FROM ca_item_comments cic
			LEFT JOIN ca_users AS u ON u.user_id = cic.user_id
			{$vs_where} ORDER BY cic.created_on DESC {$vs_limit}
		");
		
		if ($return_as === 'searchResult') {
		    return caMakeSearchResult('ca_item_comments', $qr_res->getAllFieldValues('comment_id'));
		}
		    
		$va_comments = array();
		while($qr_res->nextRow()) {
			$vn_datetime = $qr_res->get('created_on');
			$o_tep->setUnixTimestamps($vn_datetime, $vn_datetime);
			
			$va_row = $qr_res->getRow();
			$va_row['created_on'] = $o_tep->getText();
			
			$t_table = Datamodel::getInstanceByTableNum($qr_res->get('table_num'), true);
			if ($t_table->load($qr_res->get('row_id'))) {
				$va_row['commented_on'] = $t_table->getLabelForDisplay(false);
				if ($vs_idno = $t_table->get('idno')) {
					$va_row['commented_on'] .= ' ['.$vs_idno.']';
				}
			}
			foreach(array("media1", "media2", "media3", "media4") as $vs_media_field){
				$va_media_versions = $qr_res->getMediaVersions($vs_media_field);
				$va_media = array();
				if(is_array($va_media_versions) && (sizeof($va_media_versions) > 0)){
					foreach($va_media_versions as $vs_version){
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
    /**
     * Returns instance with item to which the comment is attached
     *
     * @return BaseModel instance of model for item associated with comment; null if no comment is loaded; or false if the associated item cannot be fetched.
     */
    public function getItem() {
        if (!$this->getPrimaryKey()) { return null; }

        if (!($t_item = Datamodel::getInstanceByTableNum($this->get('table_num')))) { return false; }

        if ($t_item->load($this->get('row_id'))) {
            return $t_item;
        }
        return false;
    }
    # ------------------------------------------------------
    /**
     * Returns instance with item to which the comment is attached
     *
     * @return BaseModel instance of model for item associated with comment; null if no comment is loaded; or false if the associated item cannot be fetched.
     */
    public static function getCommentUsersForSelect() {
        $o_db = new Db();
        $qr = $o_db->query("SELECT u.user_id, u.fname, u.lname, u.email FROM ca_item_comments c INNER JOIN ca_users AS u ON c.user_id = u.user_id GROUP BY u.user_id");
        
        $opts = [];
        while($qr->nextRow()) {
            $opts[$qr->get('fname').' '.$qr->get('lname').' ('.$qr->get('email').')'] = $qr->get('user_id');
        }
        
        return $opts;
    }
    # ------------------------------------------------------
    /**
     * Fetch item comment label data given a result set. When comments are attached to a set item placement
     * labels are returned from the item referenced in the set, rather than the set item itself.
     *
     * @param ItemCommentSearchResult $result A ca_item_comments search result
     * @param array $options Options include:
     *      itemsPerPage = Maximum number of comments to return at once. [Default is 36]
     *      request = The current request.
     *
     * @return array 
     */
    public static function getItemCommentDataForResult($result, $options=null) {
        $items_per_page = caGetOption('itemsPerPage', $options, 36);
        $request = caGetOption('request', $options, null);
        $item_count = 0;
        
        $t_set = new ca_sets();
        
        if(($current_index = $result->currentIndex()) < 0) { $current_index = 0; }
        
        $item_ids = $row_ids = [];
        while(($item_count < $items_per_page) && $result->nextHit()) {
		    $table_num = $result->get('ca_item_comments.table_num');
		    if ((int)$table_num === 105) { // ca_set_items
		        $item_ids[] = $result->get('ca_item_comments.row_id');
		    } else {
		        $row_ids[$table_num][] = $result->get('ca_item_comments.row_id');
		    }
		    
			$item_count++;
		}
		
	    $item_labels = $row_labels = $row_notes = [];
	    
	    if (sizeof($item_ids) > 0) {
            if (is_array($row_id_conv = ca_sets::getRowIDsForItemIDs($item_ids)) && sizeof($row_id_conv)) {
                foreach($row_id_conv as $tn => $l) {
                    if (!($t_table = Datamodel::getInstanceByTableNum($tn, true))) {
                        continue;
                    }
                    $labels = $t_table->getPreferredDisplayLabelsForIDs(array_values($l));
                    $idnos = $t_table->getFieldValuesForIDs(array_values($l), [$t_table->getProperty('ID_NUMBERING_ID_FIELD')]);
            
                    foreach($l as $item_id => $row_id) {
		                $set = array_shift(caExtractValuesByUserLocale($t_set->getSets(['item_id' => $item_id])));
                        $item_labels[$item_id] = ['table_num' => $tn, 'id' => $row_id, 'label' => $labels[$row_id], 'idno' => $idnos[$row_id], 'set_message' => _t("Comment made in set %1", $request ? caEditorLink($request, $set['name'], '', 'ca_sets', $set['set_id']) : '')];
                    }
                }
            }
        }
        if(sizeof($row_ids) > 0) {
            foreach($row_ids as $tn => $l) {
                if (!($t_table = Datamodel::getInstanceByTableNum($tn, true))) {
                    continue;
                }
                $labels = $t_table->getPreferredDisplayLabelsForIDs(array_values($l));
                $flds = $t_table->getFieldValuesForIDs(array_values($l), [$t_table->getProperty('ID_NUMBERING_ID_FIELD'), 'source_id']);
      
                foreach($l as $row_id) {
                    $row_labels[$row_id] = ['id' => $row_id, 'label' => $labels[$row_id], 'idno' => $flds[$row_id]['idno'], 'source' => $flds[$row_id]['source_id']];
                }
            }
        }
        
        $result->seek($current_index);
        
        return ['rowLabels' => $row_labels, 'itemLabels' => $item_labels, 'comment' => $result->get('comment'), 'created_on' => $result->get('created_on'), 'moderated_on' => $result->get('moderated_on')];
    }
    # ------------------------------------------------------
    /**
     * Fetch data for current item comment in a result set, using label data bulk-fetched via ca_item_comments::getItemCommentDataForResult()
     *
     * @param ItemCommentSearchResult $result A ca_item_comments search result
     * @param array $data Item comment label data extracted from the result using ca_item_comments::getItemCommentDataForResult()
     * @param array $options No options are currently supported.
     *
     * @return array 
     */
    public static function getItemCommentDataForDisplay($result, $data, $options=null) {
        $table_num = $result->get('ca_item_comments.table_num');
        $row_id = $result->get('ca_item_comments.row_id');
        $user_id = $result->get('ca_item_comments.user_id');
        
        $notes = '';
        
        if ($table_num == 105) { // ca_set_items
            $d = $data['itemLabels'][$row_id];
            $table_num = $d['table_num'];
            $label = $d['label'];
            $idno = $d['idno'];
            $row_id = $d['id'];
                    
            $notes = $d['set_message'];
        } else {		    
            if (!($t_table = Datamodel::getInstanceByTableNum($table_num, true))) {
                return null;
            }
            $label = $data['rowLabels'][$row_id]['label'];
            $idno = $data['rowLabels'][$row_id]['idno'];
            
        }
        if (!$row_id) { 
            $label = _t('Item has been deleted'); 
        }
        if(!$label) {
            $label = '['.caGetBlankLabelText('ca_item_comments').']';
        }
        
        $res = ['table_num' => $table_num, 'id' => $row_id, 'label' => $label, 'idno' => $idno, 'notes' => $notes];
        
        if($user_id > 0) {
            foreach(['ca_users.fname', 'ca_users.lname', 'ca_users.email'] as $f) {
                $ft = array_pop(explode('.', $f));
                $res[$ft] = $result->get($f);   
            }
            $res['name'] = trim($res['fname'].' '.$res['lname']);
        } else {
            $res['name'] = $result->get('ca_item_comments.name');
            $res['email'] = $result->get('ca_item_comments.email');
        }
        $res['source'] = caGetListItemIdno($data['rowLabels'][$row_id]['source']);
        
        foreach(['ca_item_comments.comment_id', 'ca_item_comments.comment', 'ca_item_comments.created_on', 'ca_item_comments.moderated_on'] as $f) {
            $ft = array_pop(explode('.', $f));
            $res[$ft] = $result->get($f);
        }
        
        return $res;
    }
    # ------------------------------------------------------
}
