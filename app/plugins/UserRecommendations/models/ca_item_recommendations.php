<?php
/** ---------------------------------------------------------------------
 * app/plugins/UserRecommendations/models/ca_item_recommendations.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */

require_once(__CA_APP_DIR__.'/plugins/UserRecommendations/CustomSearchIndexer.php');
require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');


BaseModel::$s_ca_models_definitions['ca_item_recommendations'] = array(
 	'NAME_SINGULAR' 	=> _t('recommendation'),
 	'NAME_PLURAL' 		=> _t('recommendations'),
 	'FIELDS' 			=> array(
 		'recommendation_id' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_HIDDEN, 
                'IDENTITY' => true,
                'DISPLAY_WIDTH' => 10,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
                'LABEL' => _t('CollectiveAccess id'),
                'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this recommendation')
		),
		'table_num' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 10,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
                'LABEL' => 'Table recommendation applies to',
                'DESCRIPTION' => 'The table number of the table this comment is applied to.',
				'BOUNDS_VALUE' => array(1,255)
		),
		'user_id' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_OMIT, 
                'DISPLAY_WIDTH' => 40,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
                'LABEL' => _t('User'),
                'DESCRIPTION' => _t('The user who created the recommendation.')
		),
		'row_id' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 10,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
                'LABEL' => 'Row ID',
                'DESCRIPTION' => 'Primary key value of the row in the table specified by table_num that this recommendation applies to.'
		),
		'locale_id' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_SELECT, 
                'DISPLAY_WIDTH' => 40,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
                'LABEL' => _t('Locale'),
                'DESCRIPTION' => _t('The locale of the recommendation.')
		),
		'recommendation' => array(
                'FIELD_TYPE' => FT_TEXT,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 50,
                'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => true, 
				'DEFAULT' => '',
                'LABEL' => _t('Recommendation'),
                'DESCRIPTION' => _t('Text of recommendations.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'email' => array(
                'FIELD_TYPE' => FT_TEXT,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 50,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
                'LABEL' => _t('E-mail address'),
                'DESCRIPTION' => _t('E-mail address of recommendor.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'name' => array(
                'FIELD_TYPE' => FT_TEXT,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 50,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
                'LABEL' => _t('Name'),
                'DESCRIPTION' => _t('Name of recommendor.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'location' => array(
                'FIELD_TYPE' => FT_TEXT,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 50,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
                'LABEL' => _t('Location'),
                'DESCRIPTION' => _t('Location of recommender.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'type' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_SELECT, 
                'DISPLAY_WIDTH' => 20,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
                    _t('Association') => 0,
                    _t('Creation') => 1
				),
                'LABEL' => _t('Type'),
                'DESCRIPTION' => _t('The type of the recommendation')
		),
		'media1' => array(
                'FIELD_TYPE' => FT_MEDIA,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 88,
                'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
                'LABEL' => _t('Media to upload'),
                'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media2' => array(
                'FIELD_TYPE' => FT_MEDIA,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 88,
                'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
                'LABEL' => _t('Media to upload'),
                'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media3' => array(
                'FIELD_TYPE' => FT_MEDIA,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 88,
                'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
                'LABEL' => _t('Media to upload'),
                'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media4' => array(
                'FIELD_TYPE' => FT_MEDIA,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 88,
                'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_item_comments_media',
				
                'LABEL' => _t('Media to upload'),
                'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'access' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_SELECT, 
                'DISPLAY_WIDTH' => 40,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
                'LABEL' => _t('Access'),
                'DESCRIPTION' => _t('Indicates if the recommendation is accessible to the public or not.')
		),
		'created_on' => array(
                'FIELD_TYPE' => FT_TIMESTAMP,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 20,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
                'LABEL' => _t('Recommendation creation date'),
                'DESCRIPTION' => _t('The date and time the recommendation was created.')
		),
		'ip_addr' => array(
                'FIELD_TYPE' => FT_TEXT,
                'DISPLAY_TYPE' => DT_FIELD, 
                'DISPLAY_WIDTH' => 40,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
                'LABEL' => _t('IP address of recommender'),
                'DESCRIPTION' => _t('The IP address of the recommender.'),
				'BOUNDS_LENGTH' => array(0,39)
		),
		'moderated_by_user_id' => array(
                'FIELD_TYPE' => FT_NUMBER,
                'DISPLAY_TYPE' => DT_OMIT, 
                'DISPLAY_WIDTH' => 40,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => null,
                'LABEL' => _t('Moderator'),
                'DESCRIPTION' => _t('The user who examined the recommendation for validity and applicability.')
		),
		'moderated_on' => array(
                'FIELD_TYPE' => FT_DATETIME,
                'DISPLAY_TYPE' => DT_OMIT, 
                'DISPLAY_WIDTH' => 20,
                'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => null,
                'LABEL' => _t('Moderation date'),
                'DESCRIPTION' => _t('The date and time the recommendation was examined for validity and applicability.')
        ),
        'assoc_table_num' => array(
            'FIELD_TYPE' => FT_NUMBER,
            'DISPLAY_TYPE' => DT_FIELD, 
            'DISPLAY_WIDTH' => 10,
            'DISPLAY_HEIGHT' => 1,
            'IS_NULL' => false, 
            'DEFAULT' => '',
            'LABEL' => 'Table recommendation for the item to be associate with',
            'DESCRIPTION' => 'The table number of the table this recommendation wants the item to be associated with.',
            'BOUNDS_VALUE' => array(1,255)
        ),
        'assoc_row_id' => array(
            'FIELD_TYPE' => FT_NUMBER,
            'DISPLAY_TYPE' => DT_FIELD, 
            'DISPLAY_WIDTH' => 10,
            'DISPLAY_HEIGHT' => 1,
            'IS_NULL' => true, 
            'DEFAULT' => '',
            'LABEL' => 'Row ID for the item to be associate with',
            'DESCRIPTION' => 'Primary key value of the row in the table specified by assoc_table_num that this recommendation suggests the item should be associate with.'
        ),
        'new_assoc_name' => array(
            'FIELD_TYPE' => FT_TEXT,
            'DISPLAY_TYPE' => DT_FIELD, 
            'DISPLAY_WIDTH' => 10,
            'DISPLAY_HEIGHT' => 1,
            'IS_NULL' => true, 
            'DEFAULT' => '',
            'LABEL' => 'Label for proposed entry',
            'DESCRIPTION' => 'Label value of the proposed newly created entry in the table specified by assoc_table_num that this recommendation suggests.'
        )
 	)
);

class ca_item_recommendations extends BaseModel {
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
	protected $TABLE = 'ca_item_recommendations';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'recommendation_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('name', 'email', 'recommendation');

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
	protected $TYPE = '';
	
	
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
	protected $SEARCH_CLASSNAME = 'ItemRecommendationSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ItemRecommendationSearchResult';
	
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
    
    /**
	 * Perform search indexing on currently load row. All parameters are intended to override typical useful behavior.
	 * Don't use these options unless you know what you're doing.
	 *
	 * @param array $pa_changed_field_values_array List of changed field values. [Default is to load list from model]
	 * @param bool $pb_reindex_mode If set indexing is done in "reindex mode"; that is the row is reindexed from scratch as if the entire database is being reindexed. [Default is false]
	 * @param array $pa_options Options include 
	 * 		engine = Name of the search engine to use. [Default is the engine configured using "search_engine_plugin" in app.conf] 
	 *		isNewRow = Set to true if row is being indexed for the first time. BaseModel::insert() should set this. [Default is false]
	 *
	 * @return bool true on success, false on failure of indexing
	 */
	public function doSearchIndexing($pa_changed_field_values_array=null, $pb_reindex_mode=false, $pa_options=null) {
		if (defined("__CA_DONT_DO_SEARCH_INDEXING__") || defined('__CA_IS_SIMPLE_SERVICE_CALL__')) { return; }
		if (is_null($pa_changed_field_values_array)) { 
			$pa_changed_field_values_array = $this->getChangedFieldValuesArray();
		}
        
        return true;

		$o_indexer = $this->getSearchIndexer(caGetOption('engine', $pa_options, null));
		return $o_indexer->indexRow(
			$this->tableNum(), $this->getPrimaryKey(), // identify record
			$this->getFieldValuesArray(true), // data to index
			$pb_reindex_mode,
			null, // exclusion list, always null in the beginning
			$pa_changed_field_values_array, // changed values
			$pa_options
		);
    }

    /**
     * Get a SearchIndexer instance. Will return a single instance repeatedly within the context of
     * any currently running transaction. That is, if the current model is in a transaction the indexing
     * will be performed within that transaction.
     *
     * @param string $ps_engine Name of the search engine to use. [Default is the engine configured using "search_engine_plugin" in app.conf] 
     *
     * @return SearchIndexer
     */
    /*public function getSearchIndexer($ps_engine=null) {
        $this->search_indexer = parent::getSearchIndexer($ps_engine);//new SearchIndexer($this->getDb(), $ps_engine);
        
        return $this->search_indexer;
    }*/
    
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
    public function getAssociationRecommendationCount() {
        return $this->getRecommendationCount('association', true);
    }
    public function getCreationRecommendationCount() {
        return $this->getRecommendationCount('creation', true);
    }
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedRecommendationCount() {
		return $this->getRecommendationCount('unmoderated', true);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getUnmoderatedRecommendations() {
		return $this->getRecommendationList('unmoderated', null, true);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getModeratedRecommendationCount() {
		return $this->getRecommendationCount('moderated', true);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getRecommendationCount($ps_mode='', $vb_has_recommendation = true) {
		$vs_where = '';
		$va_wheres = array();
		switch($ps_mode) {
            case 'association':
                $va_wheres[] = 'cir.type = 0';
                break;
            case 'creation':
                $va_wheres[] = 'cir.type = 1';
                break;
			case 'unmoderated':
				$va_wheres[] = 'cir.moderated_on IS NULL';
				break;
			case 'moderated':
				$va_wheres[] = 'cir.moderated_on IS NOT NULL';
				break;
		}
	
		if($vb_has_recommendation){
			$va_wheres[] = "cir.recommendation IS NOT NULL";
		}
		if(sizeof($va_wheres)){
			$vs_where = "WHERE ".join(" AND ", $va_wheres);
		}
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_item_recommendations cir
			{$vs_where}
		");
		
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('c');
        }
        
		return 0;
	}
	# ------------------------------------------------------
	public function getModeratedRecommendations() {
		return $this->getRecommendationList('moderated');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getRecommendationList($ps_mode='', $pn_limit=0, $vb_has_comment = true) {
		$o_db = $this->getDb();
		
		$vs_where = '';
		$va_wheres = array();
		switch($ps_mode){ 
			case 'moderated':
				$va_wheres[] = "cir.moderated_on IS NOT NULL";
				break;
			case 'unmoderated':
				$va_wheres[] = "cir.moderated_on IS NULL";
				break;
		}
		if(intval($pn_limit) > 0){
			$vs_limit = " LIMIT ".intval($pn_limit);
		}
		if($vb_has_comment){
			$va_wheres[] = "cir.recommendation IS NOT NULL";
		}
		if(sizeof($va_wheres)){
			$vs_where = "WHERE ".join(" AND ", $va_wheres);
		}	
		
		$o_tep = new TimeExpressionParser();
		$qr_res = $o_db->query("
			SELECT cir.*, u.user_id, u.fname, u.lname, u.email user_email
			FROM ca_item_recommendations cir
			LEFT JOIN ca_users AS u ON u.user_id = cir.user_id
			{$vs_where} ORDER BY cir.created_on DESC {$vs_limit}
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

        if (!($t_item = $this->getAppDatamodel()->getInstanceByTableNum($this->get('table_num')))) { return false; }

        if ($t_item->load($this->get('row_id'))) {
            return $t_item;
        }
        return false;
    }
    # ------------------------------------------------------

    public function test() {
        return 'test';
    }
}
