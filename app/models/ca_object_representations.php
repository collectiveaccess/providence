<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_representations.php : table access class for table ca_object_representations
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/BundlableLabelableBaseModelWithAttributes.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_labels.php");
require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");
require_once(__CA_MODELS_DIR__."/ca_representation_annotation_labels.php");
require_once(__CA_MODELS_DIR__."/ca_user_representation_annotations.php");
require_once(__CA_MODELS_DIR__."/ca_user_representation_annotation_labels.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_multifiles.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_captions.php");
require_once(__CA_MODELS_DIR__."/ca_representation_transcriptions.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");
require_once(__CA_LIB_DIR__."/HistoryTrackingCurrentValueTrait.php");

BaseModel::$s_ca_models_definitions['ca_object_representations'] = array(
 	'NAME_SINGULAR' 	=> _t('object representation'),
 	'NAME_PLURAL' 		=> _t('object representations'),
 	'FIELDS' 			=> array(
 		'representation_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this representation')
		),
		'locale_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'DISPLAY_FIELD' => array('ca_locales.name'),
			'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the representation originates.')
		),
		'type_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'DISPLAY_FIELD' => array('ca_list_items.item_value'),
			'DISPLAY_ORDERBY' => array('ca_list_items.item_value'),
			'IS_NULL' => false, 
			'LIST_CODE' => 'object_representation_types',
			'DEFAULT' => '',
			'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates the type of the representation. The type can only be set when creating a new representation and cannot be changed once the representation is saved.')
		),
		'idno' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Representation identifier'), 'DESCRIPTION' => _t('A unique alphanumeric identifier for this representation.'),
			'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Sortable representation identifier', 'DESCRIPTION' => 'Value used for sorting representations on identifier value.',
			'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort_num' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Sortable object identifier as integer', 'DESCRIPTION' => 'Integer value used for sorting objects; used for idno range query.'
		),
		'media' => array(
			'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			
			"MEDIA_PROCESSING_SETTING" => 'ca_object_representations',
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('Media'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media_metadata' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'DONT_PROCESS_DURING_INSERT_UPDATE' => true,
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('Media metadata'), 'DESCRIPTION' => _t('Media metadata')
		),
		'media_content' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'DONT_PROCESS_DURING_INSERT_UPDATE' => true,
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('Media content'), 'DESCRIPTION' => _t('Media content')
		),
		'md5' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('MD5 hash'), 'DESCRIPTION' => _t('MD5-generated "fingerprint" for this media.'),
			'BOUNDS_LENGTH' => array(0,32)
		),
		'original_filename' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 90, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('Original filename'), 'DESCRIPTION' => _t('The filename of the media at the time of upload.'),
			'BOUNDS_LENGTH' => array(0,1024)
		),
		'mimetype' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 90, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('Original MIME type'), 'DESCRIPTION' => _t('The MIME type of the media at the time of upload.'),
			'BOUNDS_LENGTH' => array(0,255)
		),
		'media_class' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 90, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			
			'LABEL' => _t('Media class'), 'DESCRIPTION' => _t('The type of media uploaded (image, video, audio, document).'),
			'BOUNDS_LENGTH' => array(0,255)
		),
		'is_transcribable' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Transcribe?'), 'DESCRIPTION' => _t('Indicates that the representation is a candidate for transcription.')
		),
		'home_location_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Home location'), 'DESCRIPTION' => _t('The customary storage location for this object reprsentation.')
		),
		'access' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'BOUNDS_CHOICE_LIST' => array(
				_t('Not accessible to public') => 0,
				_t('Accessible to public') => 1
			),
			'LIST' => 'access_statuses',
			'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if representation is accessible to the public or not. ')
		),
		'status' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'BOUNDS_CHOICE_LIST' => array(
				_t('Newly created') => 0,
				_t('Editing in progress') => 1,
				_t('Editing complete - pending review') => 2,
				_t('Review in progress') => 3,
				_t('Completed') => 4
			),
			'LIST' => 'workflow_statuses',
			'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the representation.')
		),
		'deleted' => array(
			'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the object is deleted or not.'),
			'BOUNDS_VALUE' => array(0,1),
			'DONT_INCLUDE_IN_SEARCH_FORM' => true
		),
		'rank' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		),
		'source_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => '',
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LIST_CODE' => 'object_representation_sources',
			'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of object representation. This value is often used to indicate the administrative sub-division or legacy database from which the object originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'source_info' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for object representation information retrieved via web services [NOT IMPLEMENTED YET].'
		),
		'view_count' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => 'View count', 'DESCRIPTION' => _t('Number of views for this record.')
		),
		'submission_user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted by user'), 'DESCRIPTION' => _t('User submitting this object representation')
		),
		'submission_group_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted for group'), 'DESCRIPTION' => _t('Group this object representation was submitted under')
		),
		'submission_status_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LIST_CODE' => 'submission_statuses',
			'LABEL' => _t('Submission status'), 'DESCRIPTION' => _t('Indicates submission status')
		),
		'submission_via_form' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Submission via form'), 'DESCRIPTION' => _t('Indicates what contribute form was used to create the submission')
		),
		'submission_session_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true,
			'DEFAULT' => null,
			'ALLOW_BUNDLE_ACCESS_CHECK' => true,
			'LABEL' => _t('Submission session'), 'DESCRIPTION' => _t('Indicates submission session')
		)
 	)
);

class ca_object_representations extends BundlableLabelableBaseModelWithAttributes implements IBundleProvider {
	use HistoryTrackingCurrentValueTrait;
	
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
	protected $TABLE = 'ca_object_representations';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'representation_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('media');

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
	protected $ORDER_BY = array('media');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = 'rank';
	
	
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
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_object_representation_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';								// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'object_representation_types';		// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Sources
	# ------------------------------------------------------
	protected $SOURCE_ID_FLD = 'source_id';								// name of source field for this table
	protected $SOURCE_LIST_CODE = 'object_representation_sources';		// list code (ca_lists.list_code) of list defining sources for this table

	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)

	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ObjectRepresentationSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ObjectRepresentationSearchResult';
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	
	protected $ANNOTATION_MODE = 'cataloguer'; 
	

	# ------------------------------------------------------
	protected function initLabelDefinitions($options=null) {
		parent::initLabelDefinitions($options);
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_objects_table'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_objects_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_object_representations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object representations list'));
		$this->BUNDLES['ca_entities_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities list'));
		$this->BUNDLES['ca_places_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places list'));
		$this->BUNDLES['ca_occurrences_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences list'));
		$this->BUNDLES['ca_collections_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections list'));
		$this->BUNDLES['ca_list_items_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related list items list'));
		$this->BUNDLES['ca_storage_locations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations list'));
		$this->BUNDLES['ca_loans_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans list'));
		$this->BUNDLES['ca_movements_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements list'));
		$this->BUNDLES['ca_object_lots_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object lots list'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		$this->BUNDLES['ca_representation_annotations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related annotations'));
		$this->BUNDLES['ca_user_representation_annotations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related user-generated annotations'));
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related sets'));
		$this->BUNDLES['ca_sets_checklist'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));	
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lot'));
		
		$this->BUNDLES['ca_item_tags'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Tags'));
		$this->BUNDLES['ca_item_comments'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Comments'));
		
		$this->BUNDLES['ca_representation_transcriptions'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Transcriptions'));
		
		$this->BUNDLES['ca_object_representations_media_display'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media and preview images'));
		$this->BUNDLES['ca_object_representation_captions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Captions/subtitles'));
		$this->BUNDLES['ca_object_representation_sidecars'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Sidecar files'));
		
		$this->BUNDLES['authority_references_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('References'));
		
		$this->BUNDLES['transcription_count'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Number of transcriptions'));
		$this->BUNDLES['page_count'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Number of pages'));
		$this->BUNDLES['preview_count'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Number of previews'));
		$this->BUNDLES['media_dimensions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media dimensions'));
		$this->BUNDLES['media_duration'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media duration'));
		$this->BUNDLES['media_class'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media class'));
		$this->BUNDLES['media_format'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media format'));
		$this->BUNDLES['media_colorspace'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media colorspace'));
		$this->BUNDLES['media_resolution'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media resolution'));
		$this->BUNDLES['media_bitdepth'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media bit depth'));
		$this->BUNDLES['media_filesize'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media filesize'));
		$this->BUNDLES['media_center_x'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Center of media x-coordinate'));
		$this->BUNDLES['media_center_y'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Center of media y-coordinate'));
		
		$this->BUNDLES['history_tracking_current_value'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History tracking – current value'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_current_date'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current history tracking date'), 'displayOnly' => true);
		$this->BUNDLES['history_tracking_chronology'] = array('type' => 'special', 'repeating' => false, 'label' => _t('History'));
		$this->BUNDLES['history_tracking_current_contents'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Current contents'));
		
		$this->BUNDLES['generic'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Display template'));
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function insert($options=null) {
		if(!is_array($options)) { $options = []; }
		// reject if media is empty
		if ($this->mediaIsEmpty() && !(bool)$this->getAppConfig()->get('allow_representations_without_media')) {
			$this->postError(2710, _t('No media was specified'), 'ca_object_representations->insert()');
			return false;
		}
		
		// does media already exist?
		if(!caGetOption('force', $options, false)) {
			if (!($media_path = $this->getMediaPath('media', 'original'))) {
				if(!($media_path = $this->getOriginalMediaPath('media'))) {
					$media_path = array_shift($this->get('media', ['returnWithStructure' => true]));
				}
			}
			if($media_path && !$this->getAppConfig()->get('allow_representations_duplicate_media') && ($t_existing_rep = ca_object_representations::mediaExists($media_path))) {
				throw new MediaExistsException(_t('Media already exists'), $t_existing_rep);
			}
		}
		
		// do insert
		$reader = ($media_path && !isUrl($media_path)) ? $this->_readEmbeddedMetadata($media_path) : null;
		
		if ($vn_rc = parent::insert($options)) {
			if (is_array($va_media_info = $this->getMediaInfo('media'))) {
				$this->set('md5', $va_media_info['INPUT']['MD5']);
				$this->set('mimetype', $media_mimetype = $va_media_info['INPUT']['MIMETYPE']);
				$this->set('media_class', caGetMediaClass($va_media_info['INPUT']['MIMETYPE']));
				
				if(is_array($type_defaults = $this->getAppConfig()->get('object_representation_media_based_type_defaults')) && sizeof($type_defaults)) {
					foreach($type_defaults as $m => $default_type) {
						if(caCompareMimetypes($media_mimetype, $m)) {
							$this->set('type_id', $default_type, ['allowSettingOfTypeID' => true]);
							if (!($vn_rc = parent::update($options))) {
								$this->postError(2710, _t('Could not update representation type using media-based default'), 'ca_object_representations->insert()');
							}
							break;
						}
					}	
				}
			
				if(isset($va_media_info['ORIGINAL_FILENAME']) && strlen($va_media_info['ORIGINAL_FILENAME'])) {
					$this->set('original_filename', $va_media_info['ORIGINAL_FILENAME']);
				}
			}
			$va_metadata = $this->get('media_metadata', array('binary' => true));
			caExtractEmbeddedMetadata($this, $va_metadata, $this->get('locale_id'));	// TODO: deprecate in favor of import mapping based system below?
			
			// Extract metadata mapping with configured mappings
			$this->_importEmbeddedMetadata(array_merge($options, ['path' => !isUrl($media_path) ? $media_path : null, 'reader' => $reader]));
			
			$vn_rc = parent::update($options);

		}
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function update($options=null) {
		$media_path = null;
		if(!is_array($options)) { $options = []; }
		if($vb_media_has_changed = $this->changed('media')) {
		
			if(!caGetOption('force', $options, false)) {
				// does media already exist?
				//if(!($media_path = array_shift($this->get('media', ['returnWithStructure' => true])))) {
					if (!($media_path = $this->getMediaPath('media', 'original'))) {
						$media_path = $this->getOriginalMediaPath('media');
					}
				//}
				if(!$this->getAppConfig()->get('allow_representations_duplicate_media') && ($t_existing_rep = ca_object_representations::mediaExists($media_path, $this->getPrimaryKey()))) {
					throw new MediaExistsException(_t('Media already exists'), $t_existing_rep);
				}
			}
		}
		
		$reader = $media_path ? $this->_readEmbeddedMetadata($media_path) : null;
		
		if ($vn_rc = parent::update($options)) {
			if(is_array($va_media_info = $this->getMediaInfo('media'))) {
				$this->set('md5', $va_media_info['INPUT']['MD5']);
				$this->set('mimetype', $media_mimetype = $va_media_info['INPUT']['MIMETYPE']);
				$this->set('media_class', caGetMediaClass($va_media_info['INPUT']['MIMETYPE']));
				
				if(is_array($type_defaults = $this->getAppConfig()->get('object_representation_media_based_type_defaults')) && sizeof($type_defaults)) {
					foreach($type_defaults as $m => $default_type) {
						if(caCompareMimetypes($media_mimetype, $m)) {
							$this->set('type_id', $default_type, ['allowSettingOfTypeID' => true]);
							if (!($vn_rc = parent::update($options))) {
								$this->postError(2710, _t('Could not update representation type using media-based default'), 'ca_object_representations->insert()');
							}
							break;
						}
					}	
				}
				
				if (isset($va_media_info['ORIGINAL_FILENAME']) && strlen($va_media_info['ORIGINAL_FILENAME'])) {
					$this->set('original_filename', $va_media_info['ORIGINAL_FILENAME']);
				}
			}
			if ($vb_media_has_changed) {
				$va_metadata = $this->get('media_metadata', array('binary' => true));
				caExtractEmbeddedMetadata($this, $va_metadata, $this->get('locale_id'));	// TODO: deprecate in favor of import mapping based system below?
								
				// Extract metadata mapping with configured mappings
				$this->_importEmbeddedMetadata(array_merge($options, ['path' => !isUrl($media_path) ? $media_path : null, 'reader' => $reader]));
			}
			
			$vn_rc = parent::update($options);
		}
		
		CompositeCache::delete('representation:'.$this->getPrimaryKey(), 'IIIFMediaInfo');
		CompositeCache::delete('representation:'.$this->getPrimaryKey(), 'IIIFTileCounts');
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _readEmbeddedMetadata(string $media_path, ?array $options=null) {
		$t_mapping = new ca_data_importers();
		$m = new Media();
		$mimetype = $m->divineFileFormat($media_path);
		
		$type = 'EXIF';
		if ($object_representation_mapping_id = $this->_getEmbeddedMetadataMappingID(['mimetype' => $mimetype])) {
			$t_mapping = ca_data_importers::find(['importer_id' => $object_representation_mapping_id], ['returnAs' => 'firstModelInstance']);
			$formats = $t_mapping->getSetting('inputFormats');
			if(is_array($formats) && sizeof($formats)) {
				$type = array_shift($formats);
			}
		}
		if (!($reader = $t_mapping->getDataReader(null, $type))) { return null; }
		$reader->read($media_path);
		
		return $reader;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _importEmbeddedMetadata($options=null) {
		if(caGetOption('dontImportEmbeddedMetadata', $options, false)) { return true; }
		if(!($path = caGetOption('path', $options, $this->getMediaPath('media', 'original')))) {
			$path = $this->getOriginalMediaPath('media');
		}
		
		$log = caGetImportLogger(['logLevel' => $this->_CONFIG->get('embedded_metadata_extraction_mapping_log_level')]);
		if(!($object_representation_mapping_id = caGetOption('mapping_id', $options, null))) {
			$object_representation_mapping_id = $this->_getEmbeddedMetadataMappingID(['log' => $log]);
		}
		
		if ($object_representation_mapping_id && ($t_mapping = ca_data_importers::find(['importer_id' => $object_representation_mapping_id], ['returnAs' => 'firstModelInstance']))) {
			$format = $t_mapping->getSetting('inputFormats');
			if(is_array($format)) { $format = array_shift($format); }
			if ($log) { $log->logDebug(_t('Using embedded media mapping %1 with path %3 (format %2)', $t_mapping->get('importer_code'), $format, $path)); }
			
			$va_media_info = $this->getMediaInfo('media');
			return $t_mapping->importDataFromSource($path, $object_representation_mapping_id, [
				'logLevel' => $this->_CONFIG->get('embedded_metadata_extraction_mapping_log_level'), 
				'format' => $format, 'forceImportForPrimaryKeys' => [$this->getPrimaryKey(), 
				'transaction' => $this->getTransaction()],
				'reader' => caGetOption('reader', $options, null),
				'environment' => ['original_filename' => $va_media_info['ORIGINAL_FILENAME'], '/original_filename' => $va_media_info['ORIGINAL_FILENAME']]
			]); 
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _getEmbeddedMetadataMappingID(?array $options=null) : ?int {
		$object_representation_mapping_id = null;
		
		$log = caGetOption('log', $options, null);
		if(is_array($media_metadata_extraction_defaults = $this->_CONFIG->getAssoc('embedded_metadata_extraction_mapping_defaults'))) {
			$media_mimetype = caGetOption('mimetype', $options, $this->get('mimetype'));
			
			foreach($media_metadata_extraction_defaults as $m => $importer_code) {
				if(!trim($importer_code)) { continue; }
				if(caCompareMimetypes($media_mimetype, $m)) {
					if (!($object_representation_mapping_id = ca_data_importers::find(['importer_code' => $importer_code], ['returnAs' => 'firstId']))) {
						if ($log) { $log->logInfo(_t('Could not find embedded metadata importer with code %1', $importer_code)); }
					}
					break;
				}
			}
		}
		return $object_representation_mapping_id;
	}
	# ------------------------------------------------------
	/**
	 *
	 *
	 * @param bool $pb_delete_related
	 * @param array $options
	 *		dontCheckPrimaryValue = if set the is_primary state of other related representations is not considered during the delete
	 * @param array $pa_fields
	 * @param array $pa_table_list
	 *
	 * @return bool
	 */
	public function delete($delete_related=false, $options=null, $fields=null, $table_list=null) {
		$representation_id = $this->getPrimaryKey();
		
		CompositeCache::delete("representation:{$representation_id}", 'IIIFMediaInfo');
		CompositeCache::delete("representation:{$representation_id}", 'IIIFTileCounts');
		return parent::delete($delete_related, $options, $fields, $table_list);
	}
	# ------------------------------------------------------
	/**
	 * Returns true if the media field is set to a non-empty file
	 **/
	public function mediaIsEmpty() {
		if (!($media_path = $this->getMediaPath('media', 'original'))) {
			$media_path = array_shift($this->get('media', array('returnWithStructure' => true)));
		}
		if ($media_path) {
			if (file_exists($media_path) && (abs(filesize($media_path)) > 0)) {
				return false;
			}
		}
		// is it a URL?
		if ($this->_CONFIG->get('allow_fetching_of_media_from_remote_urls')) {
			if  (isURL($media_path)) {
				return false;
			}
		}
		// is it a userMedia?
		if (is_readable($tmp_directory = $this->getAppConfig()->get('media_uploader_root_directory'))) {
			if(preg_match("!^".caGetUserDirectoryName()."/!", $media_path) && file_exists("{$tmp_directory}/{$media_path}")) {
				return false;
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/** 
	 * Check if representation media is a binary stream accepted by the BinaryFile plugin. No format-specific
	 * metadata is available for binary files limiting display choices. Use this method to determine if such metadata can be expected to 
	 * be available.
	 *
	 * @return bool
	 */
	public function mediaIsBinary() {
		return ($this->getMediaInfo('media', 'INPUT', 'MIMETYPE') == 'application/octet-stream') ? true : false;
	}
	# ------------------------------------------------------
	/**
	 * The media field is mandatory for representations
	 * @return array
	 */
	public function getMandatoryFields() {
		return array_merge(parent::getMandatoryFields(), array('media'));
	}
	# ------------------------------------------------------
	# Annotations
	# ------------------------------------------------------
	/**
	 * Returns annotation type code for currently loaded representation
	 * Type codes are based upon the mimetype of the representation's media as defined in the annotation_types.conf file
	 * 
	 * If you pass the options $pn_representation_id parameter then the returned type is for the specified representation rather
	 * than the currently loaded one.
	 */
 	public function getAnnotationType($pn_representation_id=null) {
 		if (!$pn_representation_id) {
			$t_rep = $this;
		} else {
			$t_rep = new ca_object_representations($pn_representation_id);
			if($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction()); }
		}
 		
 		$va_media_info = $t_rep->getMediaInfo('media');
 		if (!isset($va_media_info['INPUT'])) { return null; }
 		if (!isset($va_media_info['INPUT']['MIMETYPE'])) { return null; }
 		
 		$vs_mimetype = $va_media_info['INPUT']['MIMETYPE'];
 		
 		$o_type_config = Configuration::load(__CA_CONF_DIR__.'/annotation_types.conf');
 		$va_mappings = $o_type_config->getAssoc('mappings');
 		
 		return $va_mappings[$vs_mimetype];
 	}
 	# ------------------------------------------------------
 	public function getAnnotationPropertyCoderInstance($ps_type) {
 		return ca_representation_annotations::getPropertiesCoderInstance($ps_type);
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function annotationMode($ps_mode=null) {
 		if ($ps_mode) { $this->ANNOTATION_MODE = (strtolower($ps_mode) == 'user') ? 'user' : 'cataloguer'; }
 		return $this->ANNOTATION_MODE;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	protected function annotationTable() {
 		return ($this->ANNOTATION_MODE == 'user') ? 'ca_user_representation_annotations' : 'ca_representation_annotations';
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	protected function annotationLabelTable() {
 		return ($this->ANNOTATION_MODE == 'user') ? 'ca_user_representation_annotation_labels' : 'ca_representation_annotation_labels';
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns number of annotations attached to current representation
 	 *
 	 * @param array $options Optional array of options. Supported options are:
 	 *			checkAccess - array of access codes to filter count by. Only annotations with an access value set to one of the specified values will be counted.
 	 * @return int Number of annotations
 	 */
 	public function getAnnotationCount($options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!is_array($options)) { $options = array(); }
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		
 		$vs_access_sql = '';
 		if (is_array($options['checkAccess']) && sizeof($options['checkAccess'])) {
			$vs_access_sql = ' AND cra.access IN ('.join(',', $options['checkAccess']).')';
		}
		
 		$o_db = $this->getDb();
 		
 		$qr_annotations = $o_db->query("
 			SELECT 	cra.annotation_id, cra.locale_id, cra.props, cra.representation_id, cra.user_id, cra.type_code, cra.access, cra.status
 			FROM ".$this->annotationTable()." cra
 			WHERE
 				cra.representation_id = ? {$vs_access_sql}
 		", (int)$vn_representation_id);
 		
 		return (int)$qr_annotations->numRows();
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns data for annotations attached to current representation
 	 *
 	 * @param array $options Optional array of options. Supported options are:
 	 *			checkAccess = array of access codes to filter count by. Only annotations with an access value set to one of the specified values will be returned
 	 *			start =
 	 *			max = 
 	 *			labelsOnly =
 	 *			idsOnly = 
 	 *			user_id = 
 	 *			item_id =
 	 * @return array List of annotations attached to the current representation, key'ed on annotation_id. Value is an array will all values; annotation labels are returned in the current locale.
 	 */
 	public function getAnnotations($options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!is_array($options)) { $options = array(); }
 		
 		$pn_user_id = caGetOption('user_id', $options, null);
 		$pn_item_id = caGetOption('item_id', $options, null);
 		$pb_ids_only = caGetOption('idsOnly', $options, false);
 		$pb_labels_only = caGetOption('labelsOnly', $options, false);
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		
 		$va_params = array((int)$vn_representation_id);
 		
 		$o_db = $this->getDb();
 		
 		$vs_annotation_table = $this->annotationTable(); 		
 		$vs_annotation_label_table = $this->annotationLabelTable();
 		
 		$vs_access_sql = '';
 		if (is_array($options['checkAccess']) && sizeof($options['checkAccess'])) {
			$vs_access_sql = ' AND cra.access IN ('.join(',', $options['checkAccess']).')';
		}
		
		$vs_limit_sql = '';
 		if ($pn_user_id) {
			$vs_limit_sql = ' AND cra.user_id = ?';
			$va_params[] = $pn_user_id;
		}
		
 		if ($pn_item_id) {
			$vs_limit_sql .= ' AND cra.item_id = ?';
			$va_params[] = $pn_item_id;
		}
 		
 		$qr_annotations = $o_db->query("
 			SELECT 	cra.annotation_id, cra.locale_id, cra.props, cra.representation_id, cra.user_id, cra.type_code, cra.access, cra.status
 			FROM {$vs_annotation_table} cra
 			WHERE
 				cra.representation_id = ? {$vs_access_sql} {$vs_limit_sql}
 		", $va_params);
 		
 		$vs_sort_by_property = $this->getAnnotationSortProperty();
 		$va_annotations = array();
 		
 		$vn_start = caGetOption('start', $options, 0, array('castTo' => 'int'));
 		$vn_max = caGetOption('max', $options, 100, array('castTo' => 'int'));
 		
 		$va_rep_props = $this->getMediaInfo('media', 'original');
 		$vn_timecode_offset = isset($va_rep_props['PROPERTIES']['timecode_offset']) ? (float)$va_rep_props['PROPERTIES']['timecode_offset'] : 0;
 		
 		$va_annotation_ids = [];
 		while($qr_annotations->nextRow()) {
 			$va_tmp = $qr_annotations->getRow();
 			$va_annotation_ids[] = $va_tmp['annotation_id'];
 			
 			unset($va_tmp['props']);
 			$o_coder->setPropertyValues($qr_annotations->getVars('props'));
 			foreach($o_coder->getPropertyList() as $vs_property) {
 				$va_tmp[$vs_property] = $o_coder->getProperty($vs_property);
 				$va_tmp[$vs_property.'_raw'] = $o_coder->getProperty($vs_property, true);
 				$va_tmp[$vs_property.'_vtt'] = $o_coder->getProperty($vs_property, false, ['vtt' => true]);
 			}
 			
 			$va_tmp['timecodeOffset'] = $vn_timecode_offset;
 			
 			if (!($vs_sort_key = $va_tmp[$vs_sort_by_property])) {
 				$vs_sort_key = '_default_';
 			}
 			
 			$va_annotations[$vs_sort_key][$va_tmp['annotation_id']] = $va_tmp;
 		}
 		if (!sizeof($va_annotation_ids)) { return array(); }
 		
 		ksort($va_annotations, SORT_NUMERIC);
 		
 		// get annotation labels
 		$qr_annotations = caMakeSearchResult($vs_annotation_table, $va_annotation_ids);
 		$va_labels = $va_annotation_classes = array();
 		
 		// Check if "class" element is configured, exists and is a list element
 		if ($vs_class_element = $this->getAppConfig()->get('annotation_class_element')) {
 			$t_anno = new ca_representation_annotations();
 			if (!$t_anno->hasElement($vs_class_element)) { 
 				$vs_class_element = null; 
 			} elseif(ca_metadata_elements::getElementDatatype($vs_class_element) != __CA_ATTRIBUTE_VALUE_LIST__)  {
 				// not a list element
 				$vs_class_element = null; 
 			}
 		}
 		
 		while($qr_annotations->nextHit()) {
 			$va_labels[$vn_annotation_id = $qr_annotations->get("{$vs_annotation_table}.annotation_id")][$qr_annotations->get("{$vs_annotation_label_table}.locale_id")][] = $qr_annotations->get("{$vs_annotation_table}.preferred_labels.name");
 			
 			if ($vs_class_element) { 
 				$va_annotation_classes[$vn_annotation_id] = $qr_annotations->get("{$vs_annotation_table}.{$vs_class_element}", array('returnAsArray' => true));
 			}
 		}
 		$va_labels_for_locale = caExtractValuesByUserLocale($va_labels);
 		if ($pb_labels_only) { return $va_labels_for_locale; }
 		
 		$va_annotation_classes_flattened = array();
 		foreach($va_annotation_classes as $vn_annotation_id => $va_classes) {
 			$va_annotation_classes_flattened[$vn_annotation_id] = array_shift($va_classes);
 		}
 		
 		$va_key = array();
 		if ($qr_list_items = caMakeSearchResult('ca_list_items', array_values($va_annotation_classes_flattened))) {
 			while($qr_list_items->nextHit()) {
 				$va_key[$qr_list_items->get('item_id')] = array(
 					'name' => $qr_list_items->get('ca_list_items.preferred_labels.name_plural'),
 					'idno' => $qr_list_items->get('ca_list_items.idno'),
 					'color' => $qr_list_items->get('ca_list_items.color'),
 				);
 			}
 		}
 
 		$va_sorted_annotations = array();
 		foreach($va_annotations as $vs_key => $va_values) {
 			foreach($va_values as $va_val) {
 				$vn_annotation_id = $va_val['annotation_id'];	
 				$vs_label = is_array($va_labels_for_locale[$va_val['annotation_id']]) ? array_shift($va_labels_for_locale[$va_val['annotation_id']]) : '';
 				
 				if ($pb_ids_only) {
 					$va_val = $vn_annotation_id;
 				} elseif ($pb_labels_only) { 
 					$va_val = $vs_label;
 				} else {
					$va_val['labels'] = $va_labels[$vn_annotation_id] ? $va_labels[$vn_annotation_id] : array();
					$va_val['label'] = $vs_label;
					$va_val['key'] = $va_key[$va_annotation_classes_flattened[$vn_annotation_id]];
				}
 				$va_sorted_annotations[$vn_annotation_id] = $va_val;
 			}
 		}
 		if ($pb_ids_only || $pb_labels_only) { return array_values($va_sorted_annotations); }
 		
 		
 		if (($vn_start > 0) || ($vn_max > 0)) {
 			if ($vn_max > 0) {
 				$va_sorted_annotations = array_slice($va_sorted_annotations, $vn_start, $vn_max);
 			} else {
 				$va_sorted_annotations = array_slice($va_sorted_annotations, $vn_start);
 			}
 		}
 		return $va_sorted_annotations;
 	} 
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function addAnnotation($ps_title, $pn_locale_id, $pn_user_id, $pa_properties, $pn_status, $pn_access, $pa_values=null, $options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		
 		foreach($o_coder->getPropertyList() as $vs_property) {
			if (!$o_coder->setProperty($vs_property, $pa_properties[$vs_property])) {
				// error setting values
				$this->errors = $o_coder->errors;
				return false;
			}
		}
		
		if (!$o_coder->validate()) {
			$this->errors = $o_coder->errors;
			return false;
		}
		
 		$vs_annotation_table = $this->annotationTable(); 		
 		
 		$t_annotation = new $vs_annotation_table();
 		if($this->inTransaction()) { $t_annotation->setTransaction($this->getTransaction()); }
 		
 		$t_annotation->set('representation_id', $vn_representation_id);
 		$t_annotation->set('type_code', $o_coder->getType());
 		$t_annotation->set('locale_id', $pn_locale_id);
 		$t_annotation->set('user_id', $pn_user_id);
 		
 		// TODO: verify that item_id exists and is accessible by user
 		$t_annotation->set('item_id', caGetOption('item_id', $options, null));
 		$t_annotation->set('status', $pn_status);
 		$t_annotation->set('access', $pn_access);
 		
 		$t_annotation->insert();
 		
 		if ($t_annotation->numErrors()) {
			$this->errors = $t_annotation->errors;
			return false;
		}
		
		if (!$ps_title) { $ps_title = '['.caGetBlankLabelText('ca_object_representations').']'; }
		$t_annotation->addLabel(array('name' => $ps_title), $pn_locale_id, null, true);
		if ($t_annotation->numErrors()) {
			$this->errors = $t_annotation->errors;
			return false;
		}
		
		foreach($o_coder->getPropertyList() as $vs_property) {
			$t_annotation->setPropertyValue($vs_property, $o_coder->getProperty($vs_property));
		}
		
		$t_annotation->update();
 		
 		if ($t_annotation->numErrors()) {
			$this->errors = $t_annotation->errors;
			return false;
		}
		
		if (is_array($pa_values)) {
			foreach($pa_values as $vs_element => $va_value) { 					
				if (is_array($va_value)) {
					// array of values (complex multi-valued attribute)
					$t_annotation->addAttribute(
						array_merge($va_value, array(
							'locale_id' => $pn_locale_id
						)), $vs_element);
				} else {
					// scalar value (simple single value attribute)
					if ($va_value) {
						$t_annotation->addAttribute(array(
							'locale_id' => $pn_locale_id,
							$vs_element => $va_value
						), $vs_element);
					}
				}
			}
		}
		
		$t_annotation->update();
 		
 		if ($t_annotation->numErrors()) {
			$this->errors = $t_annotation->errors;
			return false;
		}
 		
 		if (isset($options['returnAnnotation']) && (bool)$options['returnAnnotation']) {
 			return $t_annotation;
 		}
 		return $t_annotation->getPrimaryKey();
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function editAnnotation($pn_annotation_id, $pn_locale_id, $pa_properties, $pn_status, $pn_access, $pa_values=null, $options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 	
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		foreach($o_coder->getPropertyList() as $vs_property) {
			if (!$o_coder->setProperty($vs_property, $pa_properties[$vs_property])) {
				// error setting values
				$this->errors = $o_coder->errors;
				return false;
			}
		}
		
		if (!$o_coder->validate()) {
			$this->errors = $o_coder->errors;
			return false;
		}
		
 		$vs_annotation_table = $this->annotationTable(); 		
 		
 		$t_annotation = new $vs_annotation_table($pn_annotation_id);
 		if($this->inTransaction()) { $t_annotation->setTransaction($this->getTransaction()); }
 		if ($t_annotation->getPrimaryKey() && ($t_annotation->get('representation_id') == $vn_representation_id)) {
 			foreach($o_coder->getPropertyList() as $vs_property) {
 				$t_annotation->setPropertyValue($vs_property, $o_coder->getProperty($vs_property));
 			}
 		
 			$t_annotation->setMode(ACCESS_WRITE);
 		
			$t_annotation->set('type_code', $o_coder->getType());
			$t_annotation->set('locale_id', $pn_locale_id);
			
			// TODO: verify that item_id exists and is accessible by user
			if (isset($options['item_id'])) {
 				$t_annotation->set('item_id', caGetOption('item_id', $options, null));
 			}
			$t_annotation->set('status', $pn_status);
			$t_annotation->set('access', $pn_access);
			
			$t_annotation->update();
			if ($t_annotation->numErrors()) {
				$this->errors = $t_annotation->errors;
				return false;
			}
			
			if (is_array($pa_values)) {
				foreach($pa_values as $vs_element => $va_value) { 					
					if (is_array($va_value)) {
						// array of values (complex multi-valued attribute)
						$t_annotation->addAttribute(
							array_merge($va_value, array(
								'locale_id' => $pn_locale_id
							)), $vs_element);
					} else {
						// scalar value (simple single value attribute)
						if ($va_value) {
							$t_annotation->addAttribute(array(
								'locale_id' => $pn_locale_id,
								$vs_element => $va_value
							), $vs_element);
						}
					}
				}
			}
			
			$t_annotation->update();
			
			if ($t_annotation->numErrors()) {
				$this->errors = $t_annotation->errors;
				return false;
			}
			if (is_array($pa_properties) && isset($pa_properties['label'])) {
				$t_annotation->replaceLabel(array('name' => $pa_properties['label']), $pn_locale_id, null, true);
			}
			if (isset($options['returnAnnotation']) && (bool)$options['returnAnnotation']) {
				return $t_annotation;
			}
			return true;
 		}
 		
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeAnnotation($pn_annotation_id) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		$vs_annotation_table = $this->annotationTable(); 		
 		
 		$t_annotation = new $vs_annotation_table($pn_annotation_id);
 		if($this->inTransaction()) { $t_annotation->setTransaction($this->getTransaction()); }
 		if ($t_annotation->get('representation_id') == $vn_representation_id) {
 			$t_annotation->setMode(ACCESS_WRITE);
 			$t_annotation->delete(true);
 			
 			if ($t_annotation->numErrors()) {
 				$this->errors = $t_annotation->errors;
 				return false;
 			}
 			return true;
 		}
 		
 		return false;
 	}
 	# ------------------------------------------------------
 	#
 	# ------------------------------------------------------
 	/**
 	 * Return list of representations that are related to the object(s) this representation is related to
 	 */ 
 	public function getOtherRepresentationsInRelatedObjects() {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		$o_db = $this->getDb();
 		
 		$qr_res = $o_db->query("
 			SELECT *
 			FROM ca_object_representations cor
 			INNER JOIN ca_objects_x_object_representations AS coxor ON cor.representation_id = coxor.representation_id
 			WHERE
 				coxor.object_id IN (
 					SELECT object_id
 					FROM ca_objects_x_object_representations 
 					WHERE 
 						representation_id = ?
 				)
 				AND cor.deleted = 0
 		", (int)$vn_representation_id);
 		
 		$va_reps = array();
 		while($qr_res->nextRow()) {
 			$va_reps[$qr_res->get('representation_id')] = $qr_res->getRow();
 		}
 		
 		return $va_reps;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Bundle generator - called from BundlableLabelableBaseModelWithAttributes::getBundleFormHTML()
 	 */
	protected function getRepresentationAnnotationHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $options=null) {
		//if (!$this->getAnnotationType()) { return; }	// don't show bundle if this representation doesn't support annotations
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
 		$vs_annotation_table = $this->annotationTable(); 		
 		$vs_annotation_label_table = $this->annotationLabelTable();
 		
		$t_item = new $vs_annotation_table();
		if($this->inTransaction()) { $t_item->setTransaction($this->getTransaction()); }
		$t_item_label = new $vs_annotation_label_table();
		if($this->inTransaction()) { $t_item_label->setTransaction($this->getTransaction()); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('t_item', $t_item);
		$o_view->setVar('t_item_label', $t_item_label);
		
		$o_view->setVar('t_subject', $this);
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$va_inital_values = array();
		if (is_array($va_items = $this->getAnnotations()) && sizeof($va_items)) {
			$t_rel = Datamodel::getInstanceByTableName("{$vs_annotation_table}", true);
			$vs_rel_pk = $t_rel->primaryKey();
			foreach ($va_items as $vn_id => $va_item) {
				if (!($vs_label = $va_item['label'])) { $vs_label = ''; }
				$va_inital_values[$va_item[$t_item->primaryKey()]] = array_merge($va_item, array('id' => $va_item[$vs_rel_pk], 'item_type_id' => $va_item['item_type_id'], 'relationship_type_id' => $va_item['relationship_type_id'], 'label' => $vs_label));
			}
		}
		
		$o_view->setVar('initialValues', $va_inital_values);
		
		return $o_view->render("{$vs_annotation_table}.php");
	}	
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	protected function _processRepresentationAnnotations($po_request, $ps_form_prefix, $ps_placement_code) {
 		$va_rel_items = $this->getAnnotations();
		$o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType());
		
 		$vs_annotation_table = $this->annotationTable(); 	
 		
		$vn_c = 0;
		foreach($va_rel_items as $vn_id => $va_rel_item) {
			$this->clearErrors();
			if (strlen($vn_status = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_status_'.$va_rel_item['annotation_id'], pString))) {
				$vn_access = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_access_'.$va_rel_item['annotation_id'], pInteger);
				$vn_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_locale_id_'.$va_rel_item['annotation_id'], pInteger);
				
				$va_properties = array();
				foreach($o_coder->getPropertyList() as $vs_property) {
					$va_properties[$vs_property] = $po_request->getParameter($x=$ps_placement_code.$ps_form_prefix.'_'.$vs_property.'_'.$va_rel_item['annotation_id'], pString);
				}

				// edit annotation
				$this->editAnnotation($va_rel_item['annotation_id'], $vn_locale_id, $va_properties, $vn_status, $vn_access);
			
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), $vs_annotation_table, $va_rel_item['annotation_id']);
				} else {
					// try to add/edit label
					if ($vs_label = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_label_'.$va_rel_item['annotation_id'], pString)) {
						$t_annotation = new $vs_annotation_table($va_rel_item['annotation_id']);
						if($this->inTransaction()) { $t_annotation->setTransaction($this->getTransaction()); }
						if ($t_annotation->getPrimaryKey()) {
							$t_annotation->setMode(ACCESS_WRITE);
							
							$va_pref_labels = $t_annotation->getPreferredLabels(array($vn_locale_id), false);
							
							if (sizeof($va_pref_labels)) {
								// edit existing label
								foreach($va_pref_labels as $vn_annotation_dummy_id => $va_labels_by_locale) {
									foreach($va_labels_by_locale as $vn_locale_dummy_id => $va_labels) {
										$t_annotation->editLabel($va_labels[0]['label_id'], array('name' => $vs_label), $vn_locale_id, null, true);
									}
								}
							} else {
								// create new label
								$t_annotation->addLabel(array('name' => $vs_label), $vn_locale_id, null, true);
							}
							
							if ($t_annotation->numErrors()) {
								$po_request->addActionErrors($t_annotation->errors(), $vs_annotation_table, 'new_'.$vn_c);
								$vn_c++;
							}
						}
					}
				}
			} else {
				// is it a delete key?
				$this->clearErrors();
				if (($po_request->getParameter($ps_placement_code.$ps_form_prefix.'_'.$va_rel_item['annotation_id'].'_delete', pInteger)) > 0) {
					// delete!
					$this->removeAnnotation($va_rel_item['annotation_id']);
					if ($this->numErrors()) {
						$po_request->addActionErrors($this->errors(), $vs_annotation_table, $va_rel_item['annotation_id']);
					}
				}
			}
		}
 		
 		// check for new annotations to add
 		foreach($_REQUEST as $vs_key => $vs_value ) {
			if (!preg_match('/^'.$ps_placement_code.$ps_form_prefix.'_status_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
			$vn_c = intval($va_matches[1]);
			if (strlen($vn_status = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_status_new_'.$vn_c, pString)) > 0) {
				$vn_access = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_access_new_'.$vn_c, pInteger);
				$vn_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_locale_id_new_'.$vn_c, pInteger);
				
				$va_properties = array();
				foreach($o_coder->getPropertyList() as $vs_property) {
					$va_properties[$vs_property] = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_'.$vs_property.'_new_'.$vn_c, pString);
				}
				
				// create annotation
				$vs_label = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_label_new_'.$vn_c, pString);
				$vn_annotation_id = $this->addAnnotation($vs_label, $vn_locale_id, $po_request->getUserID(), $va_properties, $vn_status, $vn_access);
				
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), $vs_annotation_table, 'new_'.$vn_c);
				} 
			}
		}
		
		return true;
 	}
 	# ------------------------------------------------------
 	public function useBundleBasedAnnotationEditor() {
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return false;
 		}
 		
 		return $o_coder->useBundleBasedAnnotationEditor();
 	}
 	# ------------------------------------------------------
 	public function getAnnotationSortProperty() {
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return false;
 		}
 		
 		return $o_coder->getAnnotationSortProperty();
 	}
 	# ------------------------------------------------------
 	# Annotation display
 	# ------------------------------------------------------
 	public function getDisplayMediaWithAnnotationsHTMLBundle($po_request, $ps_version, $options=null) {
 		if (!is_array($options)) { $options = array(); }
 		$options['poster_frame_url'] = $this->getMediaUrl('media', 'medium');
 		
 		if (!($vs_tag = $this->getMediaTag('media', $ps_version, $options))) {
 			return '';
 		}
 		
 		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('viewer_tag', $vs_tag);
		$o_view->setVar('annotations', $this->getAnnotations($options));
		
		return $o_view->render('ca_object_representations_display_with_annotations.php', false);
 	}
 	# ------------------------------------------------------
 	# Multifiles
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function addFile($ps_filepath, $ps_resource_path='/', $pb_allow_duplicates=true) {
 		if(!$this->getPrimaryKey()) { return null; }
 		if (!trim($ps_resource_path)) { $ps_resource_path = '/'; }
 		
 		$t_multifile = new ca_object_representation_multifiles();
 		if($this->inTransaction()) { $t_multifile->setTransaction($this->getTransaction()); }
 		if (!$pb_allow_duplicates) {
 			if ($t_multifile->load(array('resource_path' => $ps_resource_path, 'representation_id' => $this->getPrimaryKey()))) {
 				return null;
 			}
 		}
 		$t_multifile->setMode(ACCESS_WRITE);
 		$t_multifile->set('representation_id', $this->getPrimaryKey());
 		$t_multifile->set('media', $ps_filepath);
 		$t_multifile->set('resource_path', $ps_resource_path);
 		
 		$t_multifile->insert();
 		
 		if ($t_multifile->numErrors()) {
 			$this->errors = array_merge($this->errors, $t_multifile->errors);
 			return false;
 		}
 		
 		return $t_multifile;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeFile($pn_multifile_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_multifile = new ca_object_representation_multifiles($pn_multifile_id);
 		if($this->inTransaction()) { $t_multifile->setTransaction($this->getTransaction()); }
 		
 		if ($t_multifile->get('representation_id') == $this->getPrimaryKey()) {
 			$t_multifile->setMode(ACCESS_WRITE);
 			$t_multifile->delete();
 			
			if ($t_multifile->numErrors()) {
				$this->errors = array_merge($this->errors, $t_multifile->errors);
				return false;
			}
		} else {
			$this->postError(2720, _t('File is not part of this representation'), 'ca_object_representations->removeFile()');
			return false;
		}
		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeAllFiles() {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$va_file_ids = array_keys($this->getFileList());
 		
 		foreach($va_file_ids as $vn_id) {
 			$this->removeFile($vn_id);
 			
 			if($this->numErrors()) {
 				return false;
 			}
 		}
 		
 		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns list of additional files (page or frame previews for documents or videos, typically) attached to a representation
 	 * The return value is an array key'ed on the multifile_id (a unique identifier for each attached file); array values are arrays
 	 * with keys set to values for each file version returned. They keys are:
 	 *		<version name>_path = The absolute file path to the file
 	 *		<version name>_tag = An HTML tag that will display the file
 	 *		<version name>_url = The URL for the file
 	 *		<version name>_width = The pixel width of the file when displayed
 	 *		<version name>_height = The pixel height of the file when displayed
 	 * The available versions are set in media_processing.conf
 	 *
 	 * @param int $pn_representation_id The representation_id of the representation to return files for. If omitted the currently loaded representation is used. If no representation_id is specified and no row is loaded null will be returned.
 	 * @param int $pn_start The index of the first file to return. Files are numbered from zero. If omitted the first file found is returned.
 	 * @param int $pn_num_files The maximum number of files to return. If omitted all files are returned.
 	 * @param array $options Options include:
 	 *			versions = A list of versions to return. If omitted only the "preview" version is returned. [Default is null]
 	 *			returnAllVersions = Return data for all versions. [Default is false]
 	 *
 	 *			Note: The fourth parameter to this method was originally a list of versions to return. To maintain compatibility with 
 	 *			older code, if an indexed array is passed in place of $options, it will be used a a list of versions to return.
 	 *
 	 * @return array A list of files attached to the representations. If no files are associated an empty array is returned.
 	 */
 	public function getFileList($pn_representation_id=null, $pn_start=null, $pn_num_files=null, $options=null) {
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$return_all_versions = false;
 		$versions = null;
 		if(caIsIndexedArray($options)) {
 			$versions = $options;
 		} elseif(caGetOption('returnAllVersions', $options, false)) {
 			$return_all_versions = true;
 		} else {
 			$versions = caGetOption('versions', $options, ['preview']);
 		}
 		
 		$vs_limit_sql = '';
 		if (!is_null($pn_start) && !is_null($pn_num_files)) {
 			if (($pn_start >= 0) && ($pn_num_files >= 1)) {
 				$vs_limit_sql = "LIMIT {$pn_start}, {$pn_num_files}";
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT *
 			FROM ca_object_representation_multifiles
 			WHERE
 				representation_id = ?
 			{$vs_limit_sql}
 		", (int)$vn_representation_id);
 		
 		$va_files = array();
 		while($qr_res->nextRow()) {
 			$vn_multifile_id = $qr_res->get('multifile_id');
 			$va_files[$vn_multifile_id] = $qr_res->getRow();
 			unset($va_files[$vn_multifile_id]['media']);
 			
 			if ($return_all_versions) { $versions = $qr_res->getMediaVersions('media'); }
 			
 			if(is_array($versions)) {
				foreach($versions as $vn_i => $vs_version) {
					$va_files[$vn_multifile_id][$vs_version.'_path'] = $qr_res->getMediaPath('media', $vs_version);
					$va_files[$vn_multifile_id][$vs_version.'_tag'] = $qr_res->getMediaTag('media', $vs_version);
					$va_files[$vn_multifile_id][$vs_version.'_url'] = $qr_res->getMediaUrl('media', $vs_version);
				
					$va_info = $qr_res->getMediaInfo('media', $vs_version);
					
					$va_files[$vn_multifile_id][$vs_version.'_width'] = $va_info['WIDTH'];
					$va_files[$vn_multifile_id][$vs_version.'_height'] = $va_info['HEIGHT'];
					$va_files[$vn_multifile_id][$vs_version.'_mimetype'] = $va_info['MIMETYPE'];
				}
			}
			
			if(!isset($va_files['original_width'])) {
				$va_info = $qr_res->getMediaInfo('media');
				$va_files[$vn_multifile_id]['original_width'] = $va_info['INPUT']['WIDTH'] ?? null;
				$va_files[$vn_multifile_id]['original_height'] = $va_info['INPUT']['HEIGHT'] ?? null;
				$va_files[$vn_multifile_id]['original_mimetype'] = $va_info['INPUT']['MIMETYPE'] ?? null;
			}
			
 		}
 		return $va_files;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getFileInstance($pn_multifile_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 	
 		$t_multifile = new ca_object_representation_multifiles($pn_multifile_id);
 		if($this->inTransaction()) { $t_multifile->setTransaction($this->getTransaction()); }
 		
 		if ($t_multifile->get('representation_id') == $this->getPrimaryKey()) {
 			return $t_multifile;
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function numFiles($pn_representation_id=null) { 		
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT count(*) c
 			FROM ca_object_representation_multifiles
 			WHERE
 				representation_id = ?
 		", (int)$vn_representation_id);
 		
 		if($qr_res->nextRow()) {
 			return intval($qr_res->get('c'));
 		}
 		return 0;
 	}
 	# ------------------------------------------------------
 	# Captions/subtitles
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function addCaptionFile($ps_filepath, $pn_locale_id, $options=null) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_caption = new ca_object_representation_captions();
 		if($this->inTransaction()) { $t_caption->setTransaction($this->getTransaction()); }
 		if ($t_caption->load(array('representation_id' => $this->getPrimaryKey(), 'locale_id' => $pn_locale_id))) {
 			return null;
 		}
 		
 		$t_caption->setMode(ACCESS_WRITE);
 		$t_caption->set('representation_id', $this->getPrimaryKey());
 		$va_tmp = explode("/", $ps_filepath);
 		$t_caption->set('caption_file', $ps_filepath, array('original_filename' => caGetOption('originalFilename', $options, array_pop($va_tmp))));
 		$t_caption->set('locale_id', $pn_locale_id);
 		
 		$t_caption->insert();
 		
 		if ($t_caption->numErrors()) {
 			$this->errors = array_merge($this->errors, $t_caption->errors);
 			return false;
 		}
 		
 		return $t_caption;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeCaptionFile($pn_caption_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_caption = new ca_object_representation_captions($pn_caption_id);
 		if($this->inTransaction()) { $t_caption->setTransaction($this->getTransaction()); }
 		
 		if ($t_caption->get('representation_id') == $this->getPrimaryKey()) {
 			$t_caption->setMode(ACCESS_WRITE);
 			$t_caption->delete();
 			
			if ($t_caption->numErrors()) {
				$this->errors = array_merge($this->errors, $t_caption->errors);
				return false;
			}
		} else {
			$this->postError(2720, _t('Caption file is not part of this representation'), 'ca_object_representations->removeCaptionFile()');
			return false;
		}
		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeAllCaptionFiles() {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$va_file_ids = array_keys($this->getCaptionFileList());
 		
 		foreach($va_file_ids as $vn_id) {
 			$this->removeCaptionFile($vn_id);
 			
 			if($this->numErrors()) {
 				return false;
 			}
 		}
 		
 		return true;
 	}
 	# ------------------------------------------------------
 	# Sidecar files
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function addSidecarFile($filepath, $notes=null, $options=null) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_sidecar = new ca_object_representation_sidecars();
 		if($this->inTransaction()) { $t_sidecar->setTransaction($this->getTransaction()); }
 		
 		
 		$t_sidecar->setMode(ACCESS_WRITE);
 		$t_sidecar->set('representation_id', $this->getPrimaryKey());
 		$tmp = explode("/", $filepath);
 		$t_sidecar->set('sidecar_file', $filepath, ['original_filename' => caGetOption('originalFilename', $options, array_pop($tmp))]);
 		$t_sidecar->set('notes', $notes);
 		$t_sidecar->insert();
 		
 		if ($t_sidecar->numErrors()) {
 			$this->errors = array_merge($this->errors, $t_sidecar->errors);
 			return false;
 		}
 		
 		return $t_sidecar;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeSidecarFile($sidecar_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_sidecar = new ca_object_representation_sidecars($sidecar_id);
 		if($this->inTransaction()) { $t_sidecar->setTransaction($this->getTransaction()); }
 		
 		if ($t_sidecar->get('representation_id') == $this->getPrimaryKey()) {
 			$t_sidecar->setMode(ACCESS_WRITE);
 			$t_sidecar->delete();
 			
			if ($t_sidecar->numErrors()) {
				$this->errors = array_merge($this->errors, $t_sidecar->errors);
				return false;
			}
		} else {
			$this->postError(2720, _t('Sidecar file is not part of this representation'), 'ca_object_representations->removeSidecarFile()');
			return false;
		}
		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeAllSidecarFiles() {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$file_ids = array_keys($this->getSidecarFileList());
 		
 		foreach($file_ids as $id) {
 			$this->removeSidecarFile($id);
 			
 			if($this->numErrors()) {
 				return false;
 			}
 		}
 		
 		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns list of caption/subtitle files attached to a representation
 	 * The return value is an array key'ed on the caption_id; array values are arrays
 	 * with keys set to values for each file returned. They keys are:
 	 *		path = The absolute file path to the file
 	 *		url = The URL for the file
 	 *		caption_id = a unique identifier for each attached caption file
 	 *
 	 * @param int $pn_representation_id The representation_id of the representation to return files for. If omitted the currently loaded representation is used. If no representation_id is specified and no row is loaded null will be returned.
 	 * @param array $pa_locale_ids 
 	 * @param array $options
 	 * @return array A list of caption files attached to the representation. If no files are associated an empty array is returned.
 	 */
 	public function getCaptionFileList($pn_representation_id=null, $pa_locale_ids=null, $options=null) {
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$t_locale = new ca_locales();
 		$va_locale_ids = array();
 		if ($pa_locale_ids) {
 			if (!is_array($pa_locale_ids)) { $pa_locale_ids = array($pa_locale_ids); }
 			foreach($pa_locale_ids as $vn_i => $vm_locale) {
 				if (is_numeric($vm_locale) && (int)$vm_locale) {
 					$va_locale_ids[] = (int)$vm_locale;
 				} else {
 					if ($vn_locale_id = $t_locale->localeCodeToID($vm_locale)) {
 						$va_locale_ids[] = $vn_locale_id;
 					}
 				}
 			}	
 			
 		}
 		
 		$vs_locale_sql = '';
 		$va_params = array((int)$vn_representation_id);
 		if (sizeof($va_locale_ids) > 0) {
 			$vs_locale_sql = " AND locale_id IN (?)";
 			$va_params[] = $va_locale_ids;
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT *
 			FROM ca_object_representation_captions
 			WHERE
 				representation_id = ?
 			{$vs_locale_sql}
 		", $va_params);
 		
 		$va_files = array();
 		while($qr_res->nextRow()) {
 			$vn_caption_id = $qr_res->get('caption_id');
 			$vn_locale_id = $qr_res->get('locale_id');
 			
 			$va_files[$vn_caption_id] = $qr_res->getRow();
 			unset($va_files[$vn_caption_id]['caption_file']);
 			
 			$va_files[$vn_caption_id]['path'] = $qr_res->getFilePath('caption_file');
 			$va_files[$vn_caption_id]['url'] = $qr_res->getFileUrl('caption_file');
			if(file_exists($va_files[$vn_caption_id]['path'])) {
				$va_files[$vn_caption_id]['filesize'] = caFormatFileSize(filesize($va_files[$vn_caption_id]['path']));
			}
 			$va_files[$vn_caption_id]['caption_id'] = $vn_caption_id;
 			$va_files[$vn_caption_id]['locale_id'] = $vn_locale_id;
 			$va_files[$vn_caption_id]['locale'] = $t_locale->localeIDToName($vn_locale_id);
 			$va_files[$vn_caption_id]['locale_code'] = $t_locale->localeIDToCode($vn_locale_id);
 		}
 		return $va_files;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getCaptionFileInstance($pn_caption_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 	
 		$t_caption = new ca_object_representation_captions($pn_caption_id);
 		if($this->inTransaction()) { $t_caption->setTransaction($this->getTransaction()); }
 		
 		if ($t_caption->get('representation_id') == $this->getPrimaryKey()) {
 			return $t_caption;
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function numCaptionFiles($pn_representation_id=null) { 		
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT count(*) c
 			FROM ca_object_representation_captions
 			WHERE
 				representation_id = ?
 		", (int)$vn_representation_id);
 		
 		if($qr_res->nextRow()) {
 			return intval($qr_res->get('c'));
 		}
 		return 0;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns list of sidecar files attached to a representation
 	 * The return value is an array key'ed on the sidecar_id; array values are arrays
 	 * with keys set to values for each file returned. They keys are:
 	 *		path = The absolute file path to the file
 	 *		url = The URL for the file
 	 *		sidecar_id = a unique identifier for each attached sidecar file
 	 *
 	 * @param int $representation_id The representation_id of the representation to return files for. If omitted the currently loaded representation is used. 
 	 * 				If no representation_id is specified and no row is loaded null will be returned.
 	 * @param array $mimetypes 
 	 * @param array $options
 	 * @return array A list of sidecar files attached to the representation. If no files are associated an empty array is returned.
 	 */
 	public function getSidecarFileList($representation_id=null, $mimetypes=null, $options=null) {
 		if(!($vn_representation_id = $representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		if(!is_array($mimetypes) && strlen($mimetypes)) {
 			$mimetypes = [$mimetypes];
 		}
 		
 		$mimetype_sql = '';
 		$params = array((int)$vn_representation_id);
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT *
 			FROM ca_object_representation_sidecars
 			WHERE
 				representation_id = ?
 			{$mimetype_sql}
 		", $params);
 		
 		$files = [];
 		while($qr_res->nextRow()) {
 			$m = $qr_res->get('mimetype');
 			if(is_array($mimetypes) && !caMimetypeIsValid($m, $mimetypes)) { continue; }
 			
 			$file_info = $qr_res->getFileInfo('sidecar_file');
 			$sidecar_id = $qr_res->get('sidecar_id');
 			
 			$files[$sidecar_id] = $qr_res->getRow();
 			unset($files[$sidecar_id]['sidecar_file']);
 			$files[$sidecar_id]['path'] = $qr_res->getFilePath('sidecar_file');
 			$files[$sidecar_id]['url'] = $qr_res->getFileUrl('sidecar_file');
			if(file_exists($files[$sidecar_id]['path'])) {
				$files[$sidecar_id]['filesize'] = caFormatFileSize(filesize($files[$sidecar_id]['path']));
			}
 			$files[$sidecar_id]['sidecar_id'] = $sidecar_id;
 			$files[$sidecar_id]['mimetype'] = $m;
 			
 			if (!($typename = Media::getTypenameForMimetype($m))) {
 				$typename = $file_info['PROPERTIES']['format_name'];
 			}
 			$files[$sidecar_id]['typename'] = $typename ? $typename : $m;
 			$files[$sidecar_id]['original_filename'] = $file_info['ORIGINAL_FILENAME'];
 		}
 		return $files;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getSidecarFileInstance($pn_sidecar_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 	
 		$t_sidecar = new ca_object_representation_sidecars($pn_sidecar_id);
 		if($this->inTransaction()) { $t_sidecar->setTransaction($this->getTransaction()); }
 		
 		if ($t_sidecar->get('representation_id') == $this->getPrimaryKey()) {
 			return $t_sidecar;
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function numSidecarFiles($representation_id=null) { 		
 		if(!($vn_representation_id = $representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT count(*) c
 			FROM ca_object_representation_sidecars
 			WHERE
 				representation_id = ?
 		", [(int)$vn_representation_id]);
 		
 		if($qr_res->nextRow()) {
 			return intval($qr_res->get('c'));
 		}
 		return 0;
 	}
 	# ------------------------------------------------------
 	#
 	# ------------------------------------------------------
 	/**
 	 * Matching method to ca_objects::getRepresentations(), except this one only returns a single representation - the currently loaded one
 	 *
 	 * @param array $pa_versions
 	 * @param array $pa_version_sizes
 	 * @param array $options
 	 *
 	 * @return array
 	 */
 	public function getRepresentations($pa_versions=null, $pa_version_sizes=null, $options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!is_array($options)) { $options = array(); }
 		
 		if (!is_array($pa_versions)) { 
 			$pa_versions = array('preview170');
 		}
 		
 		$o_db = $this->getDb();
 		
 		$va_access_values = caGetOption('checkAccess', $options, null);
 		$vs_access_where = '';
 		if (isset($va_access_values) && is_array($va_access_values) && sizeof($va_access_values)) {
 			$vs_access_where = ' AND caor.access IN ('.join(',', $va_access_values).')';
 		}
 		
 		$qr_reps = $o_db->query("
 			SELECT caor.representation_id, caor.media, caor.access, caor.status, l.name, caor.locale_id, caor.media_metadata, caor.type_id
 			FROM ca_object_representations caor
 			LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
 			WHERE
 				caor.representation_id = ?  AND caor.deleted = 0
 				{$vs_access_where}
 			ORDER BY
 				l.name ASC 
 		", (int)$this->getPrimaryKey());
 		
 		$va_reps = array();
 		while($qr_reps->nextRow()) {
 			$va_tmp = $qr_reps->getRow();
 			$va_tmp['tags'] = array();
 			$va_tmp['urls'] = array();
 			
 			$va_info = $qr_reps->getMediaInfo('media');
 			$va_tmp['info'] = array('original_filename' => $va_info['ORIGINAL_FILENAME']);
 			foreach ($pa_versions as $vs_version) {
 				if (is_array($pa_version_sizes) && isset($pa_version_sizes[$vs_version])) {
 					$vn_width = $pa_version_sizes[$vs_version]['width'];
 					$vn_height = $pa_version_sizes[$vs_version]['height'];
 				} else {
 					$vn_width = $vn_height = 0;
 				}
 				
 				if ($vn_width && $vn_height) {
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, array_merge($options, array('viewer_width' => $vn_width, 'viewer_height' => $vn_height)));
 				} else {
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, $options);
 				}
 				$va_tmp['urls'][$vs_version] = $qr_reps->getMediaUrl('media', $vs_version);
 				$va_tmp['paths'][$vs_version] = $qr_reps->getMediaPath('media', $vs_version);
 				$va_tmp['info'][$vs_version] = $qr_reps->getMediaInfo('media', $vs_version);
 				
 				$va_dimensions = array();
 				if (isset($va_tmp['info'][$vs_version]['WIDTH']) && isset($va_tmp['info'][$vs_version]['HEIGHT'])) {
					if (($vn_w = $va_tmp['info'][$vs_version]['WIDTH']) && ($vn_h = $va_tmp['info'][$vs_version]['HEIGHT'])) {
						$va_dimensions[] = "{$vn_w}p x {$vn_h}p";
					}
				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth']) && ($vn_depth = $va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth'])) {
 					$va_dimensions[] = intval($vn_depth).' bpp';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['colorspace']) && ($vs_colorspace = $va_tmp['info'][$vs_version]['PROPERTIES']['colorspace'])) {
 					$va_dimensions[] = $vs_colorspace;
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['resolution']) && is_array($va_resolution = $va_tmp['info'][$vs_version]['PROPERTIES']['resolution'])) {
 					if (isset($va_resolution['x']) && isset($va_resolution['y']) && $va_resolution['x'] && $va_resolution['y']) {
 						// TODO: units for resolution? right now assume pixels per inch
 						if ($va_resolution['x'] == $va_resolution['y']) {
 							$va_dimensions[] = $va_resolution['x'].'ppi';
 						} else {
 							$va_dimensions[] = $va_resolution['x'].'x'.$va_resolution['y'].'ppi';
 						}
 					}
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['duration']) && ($vn_duration = $va_tmp['info'][$vs_version]['PROPERTIES']['duration'])) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_duration).'s';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['pages']) && ($vn_pages = $va_tmp['info'][$vs_version]['PROPERTIES']['pages'])) {
 					$va_dimensions[] = $vn_pages.' '.(($vn_pages == 1) ? _t('page') : _t('pages'));
 				}
 				if (!isset($va_tmp['info'][$vs_version]['PROPERTIES']['filesize']) || !($vn_filesize = $va_tmp['info'][$vs_version]['PROPERTIES']['filesize'])) {
 					$vn_filesize = @filesize($qr_reps->getMediaPath('media', $vs_version));
 				}
 				if ($vn_filesize) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_filesize/(1024*1024)).'mb';
 				}
 				$va_tmp['dimensions'][$vs_version] = join('; ', $va_dimensions);
 			}
 			
 				
			if (isset($va_info['INPUT']['FETCHED_FROM']) && ($vs_fetched_from_url = $va_info['INPUT']['FETCHED_FROM'])) {
				$va_tmp['fetched_from'] = $vs_fetched_from_url;
				$va_tmp['fetched_original_url'] = caGetOption('FETCHED_ORIGINAL_URL', $va_info['INPUT'], null);
				$va_tmp['fetched_by'] = caGetOption('FETCHED_BY', $va_info['INPUT'], null);
				$va_tmp['fetched_on'] = (int)$va_info['INPUT']['FETCHED_ON'];
			}
 			
 			$va_tmp['num_multifiles'] = $this->numFiles($this->get('representation_id'));
 			$va_reps[] = $va_tmp;
 		}
 		return $va_reps;
 	}
 	# ------------------------------------------------------------------
	/**
	 * Fetches information about media in a list of representations
	 * 
	 * @param array $pa_ids indexed array of representation_id values to fetch media for
	 * @param array $pa_versions List of versions to fetch information for
	 * @param array $options An array of options:
	 *		checkAccess = Array of access values to filter on
	 * @return array List of media, key'ed by representation_id
	 */
	public function getRepresentationMediaForIDs($pa_ids, $pa_versions, $options = null) {
		if (!is_array($pa_ids) || !sizeof($pa_ids)) { return array(); }
		if (!is_array($options)) { $options = array(); }
		$va_access_values = caGetOption('checkAccess', $options, null);
		$vs_access_where = '';
		if (isset($va_access_values) && is_array($va_access_values) && sizeof($va_access_values)) {
			$vs_access_where = ' AND orep.access IN ('.join(',', $va_access_values).')';
		}
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT orep.representation_id, orep.media
			FROM ca_object_representations orep
			WHERE
				(orep.representation_id IN (".join(',', $pa_ids).")) AND orep.deleted = 0 {$vs_access_where}
		");
		
		$va_media = array();
		while($qr_res->nextRow()) {
			$va_media_tags = array();
			foreach($pa_versions as $vs_version) {
				$va_media_tags['tags'][$vs_version] = $qr_res->getMediaTag('ca_object_representations.media', $vs_version);
				$va_media_tags['info'][$vs_version] = $qr_res->getMediaInfo('ca_object_representations.media', $vs_version);
				$va_media_tags['urls'][$vs_version] = $qr_res->getMediaUrl('ca_object_representations.media', $vs_version);
			}
			$va_media[$qr_res->get('representation_id')] = $va_media_tags;
		}
		return $va_media;
	}
	# ------------------------------------------------------------------
	/**
	 * Checks if currently loaded representation is of specified media class. Valid media classes are 'image', 'audio', 'video' and 'document'
	 * 
	 * @param string The media class to check for
	 * @return True if representation is of specified class, false if not
	 */
	public function representationIsOfClass($ps_class) {
 		if (!($vs_mimetypes_regex = caGetMimetypesForClass($ps_class, array('returnAsRegex' => true)))) { return array(); }
		
		return (preg_match("!{$vs_mimetypes_regex}!", $this->get('mimetype'))) ? true  : false;
	}
	# ------------------------------------------------------------------
	/**
	 * Checks if currently loaded representation has specified MIME type
	 * 
	 * @param string The MIME type to check for
	 * @return bool True if representation has MIME type, false if not
	 */
	public function representationIsWithMimetype($ps_mimetype) {
		return ($this->get('mimetype') == $ps_mimetype) ? true : false;
	}
 	# ------------------------------------------------------
 	/**
 	 * Override export function to do some cleanup in the media_metadata part.
 	 * XML parsers and wrappers like DOMDocument tend to be rather picky with their input as far as invalid
 	 * characters go and the return value of this function is usually used for something like that.
 	 */
 	public function getValuesForExport($options=null){
 		$va_export = parent::getValuesForExport($options);
 		// this section tends to contain wonky chars that are close to impossible to clean up
 		// if you read through the EXIF specs you know why ...
 		if(isset($va_export['media_metadata']['EXIF']['IFD0'])){
 			unset($va_export['media_metadata']['EXIF']['IFD0']);
 		}
 		return $va_export;
 	}
 	# ------------------------------------------------------
 	/**
 	 * 
 	 *
 	 * @param RequestHTTP $po_request
 	 * @param array $options
 	 * @param array $pa_additional_display_options
 	 * @return string HTML output
 	 */
 	public function getRepresentationViewerHTMLBundle($po_request, $options=null, $pa_additional_display_options=null) {
 		return caRepresentationViewerHTMLBundle($this, $po_request, $options, $pa_additional_display_options);
 	}
 	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle (for use in a ca_object_representations editor form) for media
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getMediaDisplayHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($options)) { $options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
		
		$va_media_info = $this->getMediaInfo('media');
		if (!is_array($va_media_info)) { $va_media_info = array('original' => array('PROPERTIES' => array('typename' => null))); }
		$o_view->setVar('representation_typename', $va_media_info['original']['PROPERTIES']['typename']);
		$o_view->setVar('representation_num_multifiles', $this->numFiles());
		
		
		return $o_view->render('ca_object_representations_media_display.php');
	}
	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle (for use in a ca_object_representations editor form) for captions
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getCaptionHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($options)) { $options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
		$o_view->setVar('t_caption', new ca_object_representation_captions());
		
		$o_view->setVar('representation_num_caption_files', $this->numCaptionFiles());
		$o_view->setVar('initialValues', $this->getCaptionFileList());
		
		return $o_view->render('ca_object_representation_captions.php');
	}
	
	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle (for use in a ca_object_representations editor form) for sidecar files
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getSidecarHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($options)) { $options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
		$o_view->setVar('t_sidecar', new ca_object_representation_sidecars());
		
		$o_view->setVar('representation_num_sidecar_files', $this->numSidecarFiles());
		$o_view->setVar('initialValues', $this->getSidecarFileList());
		
		return $o_view->render('ca_object_representation_sidecars.php');
	}
	# ------------------------------------------------------
	/** 
	 * 
	 */
	protected function processBundlesBeforeBaseModelSave($pa_bundles, $ps_form_prefix, $po_request, $options=null) {
		if ($this->getMediaInfo('media')) { return false; }
		if (is_array($pa_bundles)) {
			foreach($pa_bundles as $va_info) {
				if ($va_info['bundle_name'] == 'ca_object_representations_media_display') {
					$vs_key = $va_info['placement_code'].$ps_form_prefix.'_media_display_media';
					if (isset($_FILES[$vs_key])) {
						$this->set('media', $_FILES[$vs_key]['tmp_name'], array('original_filename' => $_FILES[$vs_key]['name']));
					}
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------
	/** 
	 * Check if a file already exists in the database as a representation
	 *
	 * @param string $filepath The full path to the file
	 * @param int $representation_id Optional representation_id to ignore when checking for duplicated. [Default is null]
	 * @return mixed ca_object_representations instance representing the first representation that contains the file, if representation exists with this file, false if the file does not yet exist
	 */
	static function mediaExists(?string $filepath, ?int $representation_id=null) {
		if (!file_exists($filepath) || !is_readable($filepath)) { return null; }
		$md5 = @md5_file($filepath);
		
		$criteria = ['md5' => $md5];
		if($representation_id > 0) {
			$criteria['representation_id'] = ['<>', $representation_id];
		}
		if ($md5 && ($t_rep = ca_object_representations::find($criteria, ['returnAs' => 'firstModelInstance']))) { 
			return $t_rep;
		}
		
		return false;
	}	
	# ------------------------------------------------------
	/**
	 * Returns number of representations attached to the current item of the specified class. 
	 * Provided interface compatibility with RepresentableBaseModel classes.
	 *
	 * @param string $ps_class The class of representation to return a count for. Valid classes are "image", "audio", "video" and "document"
	 * @param array $options No options are currently supported.
	 *
	 * @return int Number of representations
	 */
	public function numberOfRepresentationsOfClass($ps_class, $options=null) {
		$reps = $this->representationsOfClass($ps_class, $options);
		return is_array($reps) ? sizeof($reps) : 0;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of representations attached to the current item with the specified mimetype. 
	 * Provided interface compatibility with RepresentableBaseModel classes.
	 *
	 * @param string $ps_mimetype The mimetype to return a count for. 
	 * @param array $options No options are currently supported.
	 *
	 * @return int Number of representations
	 */
	public function numberOfRepresentationsWithMimeType($ps_mimetype, $options=null) {
		return sizeof($this->representationsWithMimeType($ps_mimetype, $options));
	}
	# ------------------------------------------------------
	/**
	 * Returns information for representations of the specified class attached to the current item. 
	 * Provided interface compatibility with RepresentableBaseModel classes.
	 *
	 * @param string $ps_class The class of representation to return information for. Valid classes are "image", "audio", "video" and "document"
	 * @param array $options No options are currently supported.
	 *
	 * @return array An array of representation_ids, or null if there is no match
	 */
	public function representationsOfClass($ps_class, $options=null) {
		if (!($vs_mimetypes_regex = caGetMimetypesForClass($ps_class, array('returnAsRegex' => true)))) { return array(); }
	
		$vs_mimetype = $this->getMediaInfo('media', 'MIMETYPE');
		if (preg_match("!{$vs_mimetypes_regex}!", $vs_mimetype)) {	
			return [$this->getPrimaryKey()];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns information for representations attached to the current item with the specified mimetype. 
	 * Provided interface compatibility with RepresentableBaseModel classes.
	 *
	 * @param array $pa_mimetypes List of mimetypes to return representations for. 
	 * @param array $options No options are currently supported.
	 *
	 * @return array An array of representation_ids, or null if there is no match
	 */
	public function representationsWithMimeType($pa_mimetypes, $options=null) {
		if (!$pa_mimetypes) { return array(); }
		if (!is_array($pa_mimetypes) && $pa_mimetypes) { $pa_mimetypes = array($pa_mimetypes); }
		
		$vs_mimetype = $this->getMediaInfo('media', 'MIMETYPE');
		if (in_array($vs_mimetype, $pa_mimetypes)) {	
			return [$this->getPrimaryKey()];
		}

		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns information for representation attached to the current item with the specified MD5 hash. 
	 # Provided interface compatibility with RepresentableBaseModel classes.
	 *
	 * @param string $md5 The MD5 hash to return representation info for. 
	 * @param array $options No options are currently supported.
	 *
	 * @return array An array of representation_ids, or null if there is no match
	 */
	public function representationWithMD5(string $md5, $options=null) {
		if ($this->get('md5') == $md5) {
			return [$this->getPrimaryKey()];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of representations (always 1). Provided interface compatibility with RepresentableBaseModel classes.
	 *
	 * @param array $options No options are currently supported
	 *
	 * @return integer The number of representations
	 */
	public function getRepresentationCount($options=null) {
		return 1;
	}
	# -------------------------------------------------------
	/**
	 * Set transcription on currently loaded representation. If an existing transcription
	 * record (ca_representation_transcriptions) exists for the representation by the current
	 * user (as identified by the user_id option) or client IP address it will be updated,
	 * otherwise a new transcription record will be created.
	 *
	 * @param string $transcription
	 * @param bool $complete Indicates the transcription should be marked as complete. Once marked, it can only be unmarked by setting the uncomplete option. 
	 * @param array $options Options include:
	 *		user_id = Marks transcription with specified user_id. The user_id will also be used to determine if a transcription for the current user already exists. If null, only client IP address will be used to find existing transcriptions. [Default is null]
	 *		uncomplete = Force the completed on flag for the transcription to be unset. [Default is false]
	 *
	 * @return ca_representation_transcriptions instance of transcript, null if no representation is loaded or false if there was an error.
	 */
	public function setTranscription($transcription, $complete=false, $options=null) {
		if (!($rep_id = $this->getPrimaryKey())) { return null; }
		
		// Try to find transcript by IP address
		$ip = RequestHTTP::ip();
		
		// Try to find transcript by user
		$user_id = caGetOption('user_id', $options, null);
		
		if (!($transcript = $this->getTranscription($options))) {
			$transcript = new ca_representation_transcriptions();
		}
		
		$transcript->set('representation_id', $rep_id);
		$transcript->set('transcription', $transcription);
		
		if (caGetOption('uncomplete', $options, false)) {
			$transcript->set('completed_on', null);
		} elseif ($complete) {
			$transcript->set('completed_on', _t('now'));	
		}
		if ($user_id) {
			$transcript->set('user_id', $user_id);
		}
		$transcript->set('ip_addr', $ip);
		$transcript->isLoaded() ? $transcript->update() : $transcript->insert();
		
		if($transcript->numErrors()) {
			$this->errors = $transcript->errors;
			return false;
		}
		return $transcript;
	}
	# -------------------------------------------------------
	/**
	 * Return transcript for current user. Existing transcriptions for the representation are
	 * located by user_id (if provided) or IP address (if no user_id match is available)
	 *
	 * @param array $options Options include:
	 *		user_id = Finds transcription with specified user_id. The user_id will also be used to determine if a transcription for the current user already exists. If null, only client IP address will be used to find existing transcriptions. [Default is null]
	 *
	 * @return ca_representation_transcriptions instance of transcript, null if no representation is loaded or a transcript could not be located.
	 */
	public function getTranscription($options=null) {
		if (!($rep_id = $this->getPrimaryKey())) { return null; }
		
		// Try to find transcript by IP address
		$ip = RequestHTTP::ip();
		
		// Try to find transcript by user
		$user_id = caGetOption('user_id', $options, null);
		
		if (
			!($user_id && ($transcript = ca_representation_transcriptions::find(['representation_id' => $rep_id, 'user_id' => $user_id], ['returnAs' => 'firstModelInstance'])))
			&&
			!($transcript = ca_representation_transcriptions::find(['representation_id' => $rep_id, 'ip_addr' => $ip], ['returnAs' => 'firstModelInstance']))
		) {
			$transcript = null;
		}
		return $transcript;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Permanently deletes the transcription specified by $transcription_id. Will only delete transcriptions attached to the
	 * currently loaded row. If you attempt to delete a transcription_id not attached to the current row removeTranscription()
	 * will return false and post an error. If you attempt to call removeTranscription() with no row loaded null will be returned.
	 * If the user_id option is specified only transcriptions created by the specified user will be deleted; if the transcription being
	 * deleted is not created by the user then false is returned and an error posted.
	 *
	 * @param $transcription_id [integer] a valid transcription to be removed; must be related to the currently loaded row (mandatory)
	 * @param $options Options include:
	 *        	force = Remove transcription even if it's not part of the current representation or created by the specified user. [Default is false]
	 *			user_id = a valid ca_users.user_id value; if specified then only transcriptions by the specified user will be deleted (optional - default is null)
	 *
	 * @return bool
	 */
	public function removeTranscription($transcription_id, $options=null) {
	    $force = caGetOption('force', $options, false);
		if (!($rep_id = $this->getPrimaryKey()) && !$force) { return null; }
		
		$user_id = caGetOption('user_id', $options, null);
		$transcription = new ca_representation_transcriptions($transcription_id);
		if (!$transcription->getPrimaryKey()) {
			$this->postError(2800, _t('Transcription id is invalid'), 'ca_object_representations->removeTranscription()', 'ca_representation_transcriptions');
			return false;
		}
		
		if (!$force) {
            if ($transcription->get('representation_id') != $rep_id) {
                $this->postError(2810, _t('Transcription is not part of representation'), 'ca_object_representations->removeTranscription()', 'ca_representation_transcriptions');
                return false;
            }
        
            if ($user_id) {
                if ($transcription->get('user_id') != $user_id) {
                    $this->postError(2820, _t('Transcription was not created by specified user'), 'ca_object_representations->removeTranscription()', 'ca_representation_transcriptions');
                    return false;
                }
            }
        }
		
		$transcription->delete();
		
		if ($transcription->numErrors()) {
			$this->errors = $transcription->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Removes all comments associated with the currently loaded row. Will return null if no row is currently loaded.
	 * If the optional $ps_user_id parameter is passed then only comments created by the specified user will be removed.
	 *
	 * @param $pn_user_id [integer] A valid ca_users.user_id value. If specified, only comments by the specified user will be removed. (optional - default is null)
	 */
	public function removeAllTranscriptions($options=null) {
		if (!($rep_id = $this->getPrimaryKey())) { return null; }
		
		$transcriptions = $this->getTranscriptions(null, $options);
		
		foreach($transcriptions as $transcription) {
			if (!$this->removeTranscription($transcription['transcription_id'], $options)) {
				return false;
			}
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns all transcriptions associated with the currently loaded row. Will return null if not row is currently loaded.
	 * If the optional $moderation_status parameter is passed then only transcriptions matching the criteria will be returned:
	 *		Passing $moderation_status = TRUE will cause only validted transcriptions to be returned
	 *		Passing $moderation_status = FALSE will cause only unvalidated transcriptions to be returned
	 *		If you want both validated and unvalidated transcriptions to be returned then omit the parameter or pass a null value
	 *
	 * @param bool $moderation_status 
	 * @param array $options Options include:
     * 	    transaction = optional Transaction instance. If set then all database access is done within the context of the transaction
     *		returnAs = what to return; possible values are:
     *          array                   = an array of comments
     *			searchResult			= a search result instance (aka. a subclass of BaseSearchResult), when the calling subclass is searchable (ie. <classname>Search and <classname>SearchResult classes are defined)
     *			ids						= an array of ids (aka. primary keys)
     *			modelInstances			= an array of instances, one for each match. Each instance is the same class as the caller, a subclass of BaseModel
     *			firstId					= the id (primary key) of the first match. This is the same as the first item in the array returned by 'ids'
     *			firstModelInstance		= the instance of the first match. This is the same as the first instance in the array returned by 'modelInstances'
     *			count					= the number of matches
     *
     *			The default is array
     *      request = the current request (optional)
     *		user_id  = A valid ca_users.user_id value. If specified, only transcriptions by the specified user will be returned. (optional - default is null)
     *
     * @return array
     */
	public function getTranscriptions($moderation_status=null, $options=null) {
		if (!($rep_id = $this->getPrimaryKey())) { return null; }

        $o_request = caGetOption('request', $options, null);
        $o_trans = caGetOption('transaction', $options, null);
        $return_as = caGetOption('returnAs', $options, 'array');

		$o_db = $o_trans ? $o_trans->getDb() : $this->getDb();
		
		$user_id = caGetOption('user_id', $options, null);
		
		$user_sql = ($user_id) ? ' AND (c.user_id = '.intval($user_id).')' : '';
		
		$moderation_sql = '';
		if (!is_null($moderation_status)) {
			$moderation_sql = ($moderation_status) ? ' AND (t.validated_on IS NOT NULL)' : ' AND (t.validated_on IS NULL)';
		}
		
		$qr = $o_db->query("
			SELECT 
			    t.transcription_id, t.transcription, t.created_on, t.completed_on, t.validated_on, t.user_id, t.ip_addr,
			    u.fname, u.lname, u.email user_email
			FROM ca_representation_transcriptions t
			LEFT JOIN ca_users AS u ON t.user_id = u.user_id
			WHERE
				(t.representation_id = ?) 
				
				{$user_sql} {$moderation_sql}
		", [$rep_id]);
		
		
        switch($return_as) {
            case 'count':
                return $qr->numRows();
                break;
            case 'ids':
            case 'firstId':
            case 'searchResult':
            case 'modelInstances':
            case 'firstModelInstance':
                $ids = $qr->getAllFieldValues('transcription_id');
                if ($return_as === 'ids') { return $ids; }
                if ($return_as === 'firstId') { return array_shift($ids); }
                if (($return_as === 'modelInstances') || ($return_as === 'firstModelInstance')) {
                    $acc = array();
                    foreach($ids as $id) {
                        $t_instance = new ca_representation_transcriptions($id);
                        if ($return_as === 'firstModelInstance') { return $t_instance; }
                        $acc[] = $t_instance;
                    }
                    return $acc;
                }
                return caMakeSearchResult('ca_representation_transcriptions', $ids);
                break;
            case 'array':
            default:
                $transcriptions = [];
                while ($qr->nextRow()) {
                    $transcription_id = $qr->get("transcription_id");
                    $transcriptions[$transcription_id] = $qr->getRow();
                    $transcriptions[$transcription_id]['created_on_display'] = caGetLocalizedDate($transcriptions[$transcription_id]['created_on']);
                    $transcriptions[$transcription_id]['completed_on_display'] = caGetLocalizedDate($transcriptions[$transcription_id]['completed_on']);
                    $transcriptions[$transcription_id]['transcription_duration'] = ($transcriptions[$transcription_id]['completed_on'] > 0) && (($transcriptions[$transcription_id]['completed_on'] - $transcriptions[$transcription_id]['created_on'] > 0)) ? caFormatInterval($transcriptions[$transcription_id]['completed_on'] - $transcriptions[$transcription_id]['created_on']) : '';
                    $transcriptions[$transcription_id]['validated_on_display'] = caGetLocalizedDate($transcriptions[$transcription_id]['validated_on']);
					
					if ($transcriptions[$transcription_id]['user_id']) {
						$transcriptions[$transcription_id]['name'] = trim($transcriptions[$transcription_id]['fname'].' '.$transcriptions[$transcription_id]['lname']);
						$transcriptions[$transcription_id]['email'] =  $transcriptions[$transcription_id]['user_email'];
						$transcriptions[$transcription_id]['transcriber'] = $transcriptions[$transcription_id]['fname']." ".transcriptions[$transcription_id]['lname']." (".$transcriptions[$transcription_id]['email'].")";
					} else {
						$transcriptions[$transcription_id]['transcriber'] = _t('user at %1', $transcriptions[$transcription_id]['ip_addr']);
					}
					$transcriptions[$transcription_id]['status_message'] = _t('Created %1 by %2', $transcriptions[$transcription_id]['created_on_display'], $transcriptions[$transcription_id]['transcriber']);
					if ($transcriptions[$transcription_id]['completed_on'] > 0) {
						$transcriptions[$transcription_id]['status_message'] .= ($transcriptions[$transcription_id]['transcription_duration']) ? "<br/>"._t('Completed on %1 (%2)',  $transcriptions[$transcription_id]['completed_on_display'], $transcriptions[$transcription_id]['transcription_duration']) : "<br>\n"._t('Completed on %1',  $transcriptions[$transcription_id]['completed_on_display']);
					}
					if ($transcriptions[$transcription_id]['validated_on'] > 0) {
						$transcriptions[$transcription_id]['status_message'] .= "; "._t('validated on %1', $transcriptions[$transcription_id]['validated_on_display']);
					}
                }
                break;
        }
        
        $transcriptions = array_map(function($v) { $v['moderation_message'] = $v['validated_on'] ? '' : _t('Needs moderation'); return $v; }, $transcriptions);
        ksort($transcriptions);
        
		if (caGetOption('sortDirection', $options, 'ASC', ['validValues' => ['ASC', 'DESC'], 'forceToUppercase' => true]) === 'DESC') {
		    $transcriptions = array_reverse($transcriptions);
		}
		return $transcriptions;
	}
	# -------------------------------------------------------
	/**
	 * Check if the currently loaded representation has a completed transcription by any user.
	 * If the currentUser option is set then only transcriptions by the current user (as specified by the 
	 * user_id option and/or the client IP address) are considered.
	 *
	 * @param array $options Options include:
	 *		currentUser = Only consider transcriptions by the current user by user_id (if specified) and/or client IP address. [Default is false]
	 *		user_id = Finds transcription with specified user_id. The user_id will also be used to determine if a transcription for the current user already exists. If null, only client IP address will be used to find existing transcriptions. [Default is null]
	 * 
	 * @return bool
	 */
	public function transcriptionIsComplete(array $options=null) {
		if (!($rep_id = $this->getPrimaryKey())) { return null; }
		$current_user_only = caGetOption('currentUser', $options, false);
		
		if($current_user_only) {
			if ($transcript = $this->getTranscription($options)) {
				return $transcript->isCompleted();
			}
		} else {
			$ip = RequestHTTP::ip();
			return ca_representation_transcriptions::find(['representation_id' => $rep_id, 'completed_on' => ['>', 0]], ['returnAs' => 'firstModelInstance']) ? true : false;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of user transcriptions in the currently loaded record
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getTranscriptionHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $options=null, $pa_bundle_settings=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_subject', $this);		
		$o_view->setVar('id_prefix', $ps_form_name);	
		$o_view->setVar('placement_code', $ps_placement_code);		
		$o_view->setVar('request', $po_request);
		$o_view->setVar('batch', caGetOption('batch', $options, false));
		
		$initial_values = [];
		foreach($this->getTranscriptions() as $v) {
			$initial_values[$v['transcription_id']] = $v;
		}
		
		$o_view->setVar('initialValues', $initial_values);
		$o_view->setVar('settings', $pa_bundle_settings);
		
		
		return $o_view->render('ca_representation_transcriptions.php');
	}
	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function numTranscriptions($representation_id=null) { 		
 		if(!($vn_representation_id = $representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT count(*) c
 			FROM ca_representation_transcriptions
 			WHERE
 				representation_id = ?
 		", (int)$vn_representation_id);
 		
 		if($qr_res->nextRow()) {
 			return intval($qr_res->get('c'));
 		}
 		return 0;
 	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function renderBundleForDisplay($bundle_name, $row_id, $values, $options=null) {
		switch($bundle_name) {
			case 'transcription_count':
				return $this->numTranscriptions($row_id);
				break;
			case 'page_count':
			case 'preview_count':
				if (($qr = caMakeSearchResult('ca_object_representations', [$row_id])) && $qr->nextHit()) {
					$mimetype = $qr->getMediaInfo('media', 'INPUT', 'MIMETYPE');
					$class = caGetMediaClass($mimetype);
					
					if (($bundle_name === 'page_count') && in_array($class, ['document', 'image'])) {
						return $this->numFiles($row_id);
					}
					if (($bundle_name === 'preview_count') && in_array($class, ['audio', 'video'])) {
						return $this->numFiles($row_id);
					}
				}
				return null;
				break;
			case 'media_dimensions':
			case 'media_duration':
			case 'media_class':
			case 'media_format':
			case 'media_filesize':
			case 'media_colorspace':
			case 'media_resolution':
			case 'media_bitdepth':
			case 'media_center_x':
			case 'media_center_y':
				if (($qr = caMakeSearchResult('ca_object_representations', [$row_id])) && $qr->nextHit()) {
					$info = $qr->getMediaInfo('media');
					$version = caGetOption('version', $options, 'original');
					if(!isset($info[$version])) {
						$version = array_keys(is_array($info) ? $info : []); 
						$version = array_pop($version);
					}
					switch($bundle_name) {
						case 'media_dimensions':
							if (($w = $info[$version]['WIDTH']) && ($h = $info[$version]['HEIGHT'])) {
								return "{$w}p x {$h}p";
							}
							break;
						case 'media_duration':
							if (isset($info[$version]['PROPERTIES']['duration']) && ($duration = (float)$info[$version]['PROPERTIES']['duration'])) {
								$tp = new TimecodeParser(sprintf("%4.1f", $duration).'s');
								return $tp->getText(caGetOption('durationFormat', $options, 'hms'));
							}
							break;
						case 'media_class':
							return caGetMediaClass($qr->get('mimetype'));
							break;
						case 'media_format':
							return Media::getTypenameForMimetype($qr->get('mimetype'));
							break;
						case 'media_filesize':
							$filesize = null;
							if (!isset($info[$version]['PROPERTIES']['filesize']) || !($filesize = $info[$version]['PROPERTIES']['filesize'])) {
								$filesize = @filesize($qr->getMediaPath('media', $version));
							}
							if($filesize > 0) {
								return caHumanFilesize($filesize);
							}
							break;
						case 'media_colorspace':
							if (isset($info[$version]['PROPERTIES']['colorspace']) && ($colorspace = $info[$version]['PROPERTIES']['colorspace'])) {
								return $colorspace;
							}
							break;
						case 'media_resolution':
							if (isset($info[$version]['PROPERTIES']['resolution']) && is_array($resolution = $info[$version]['PROPERTIES']['resolution'])) {
								if (isset($resolution['x']) && isset($resolution['y']) && $resolution['x'] && $resolution['y']) {
									if ($resolution['x'] == $resolution['y']) {
										return $resolution['x'].'ppi';
									} else {
										return $resolution['x'].'x'.$resolution['y'].'ppi';
									}
								}
							}
							break;
						case 'media_bitdepth':
							if (isset($info[$version]['PROPERTIES']['bitdepth']) && ($depth = $info[$version]['PROPERTIES']['bitdepth'])) {
								return intval($depth).' bpp';
							}
							break;
						case 'media_center_x':
						case 'media_center_y':
							if (($rep = $qr->getInstance()) && ($center = $rep->getMediaCenter('media'))) {
								return ($bundle_name === 'media_center_x') ? $center['x'] : $center['y'];
							}
							break;
					}
				}
				break;
		}
		return null;
	}
	# ------------------------------------------------------
}
