<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_representations.php : table access class for table ca_object_representations
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/ca/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/ca/BundlableLabelableBaseModelWithAttributes.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_labels.php");
require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");
require_once(__CA_MODELS_DIR__."/ca_representation_annotation_labels.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_multifiles.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_captions.php");
require_once(__CA_MODELS_DIR__."/ca_commerce_order_items.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");


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
		'media' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_object_representations',
				
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				
				'LABEL' => _t('Media to upload'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
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
				'BOUNDS_VALUE' => array(0,1)
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		)
 	)
);

class ca_object_representations extends BundlableLabelableBaseModelWithAttributes implements IBundleProvider {
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
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_representation_annotations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related annotations'));
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));	
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lot'));
		
		$this->BUNDLES['ca_object_representations_media_display'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media and preview images'));
		$this->BUNDLES['ca_object_representation_captions'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Captions/subtitles'));
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		// reject if media is empty
		if ($this->mediaIsEmpty()) {
			$this->postError(2710, _t('No media was specified'), 'ca_object_representations->insert()');
			return false;
		}
		
		// do insert
		if ($vn_rc = parent::insert($pa_options)) {
			if (is_array($va_media_info = $this->getMediaInfo('media', 'original'))) {
				$this->set('md5', $va_media_info['MD5']);
				$this->set('mimetype', $va_media_info['MIMETYPE']);
			
				if(is_array($va_media_info = $this->getMediaInfo('media'))) {
					$this->set('original_filename', $va_media_info['ORIGINAL_FILENAME']);
				}
			}
			$va_metadata = $this->get('media_metadata', array('binary' => true));
			caExtractEmbeddedMetadata($this, $va_metadata, $this->get('locale_id'));
			
			$vn_rc = parent::update();
		}
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function update($pa_options=null) {
		$vb_media_has_changed = $this->changed('media');
		if ($vn_rc = parent::update($pa_options)) {
			if(is_array($va_media_info = $this->getMediaInfo('media', 'original'))) {
				$this->set('md5', $va_media_info['MD5']);
				$this->set('mimetype', $va_media_info['MIMETYPE']);
				if (is_array($va_media_info = $this->getMediaInfo('media'))) {
					$this->set('original_filename', $va_media_info['ORIGINAL_FILENAME']);
				}
			}
			if ($vb_media_has_changed) {
				$va_metadata = $this->get('media_metadata', array('binary' => true));
				caExtractEmbeddedMetadata($this, $va_metadata, $this->get('locale_id'));
			}
			
			$vn_rc = parent::update($pa_options);
		}
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 *
	 * @param bool $pb_delete_related
	 * @param array $pa_options
	 *		dontCheckPrimaryValue = if set the is_primary state of other related representations is not considered during the delete
	 * @param array $pa_fields
	 * @param array $pa_table_list
	 *
	 * @return bool
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		if (!isset($pa_options['dontCheckPrimaryValue']) && !$pa_options['dontCheckPrimaryValue']) {
			// make some other row primary
			$o_db = $this->getDb();
			if ($vn_representation_id = $this->getPrimaryKey()) {
				$qr_res = $o_db->query("
					SELECT oxor.relation_id
					FROM ca_objects_x_object_representations oxor
					INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = oxor.representation_id
					WHERE
						oxor.representation_id = ? AND oxor.is_primary = 1 AND o_r.deleted = 0
					ORDER BY
						oxor.rank, oxor.relation_id
				", (int)$vn_representation_id);
				while($qr_res->nextRow()) {
					// nope - force this one to be primary
					$t_rep_link = new ca_objects_x_object_representations();
					$t_rep_link->setTransaction($this->getTransaction());
					if ($t_rep_link->load($qr_res->get('relation_id'))) {
						$t_rep_link->setMode(ACCESS_WRITE);
						$t_rep_link->set('is_primary', 0);
						$t_rep_link->update();
			
						if ($t_rep_link->numErrors()) {
							$this->postError(2700, _t('Could not update primary flag for representation: %1', join('; ', $t_rep_link->getErrors())), 'ca_objects_x_object_representations->delete()');
							return false;
						}
					} else {
						$this->postError(2700, _t('Could not load object-representation link'), 'ca_objects_x_object_representations->delete()');
						return false;
					}				
				}
			}
		}

		return parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
	}
	# ------------------------------------------------------
	/**
	 * Returns true if the media field is set to a non-empty file
	 **/
	public function mediaIsEmpty() {
		if (!($vs_media_path = $this->getMediaPath('media', 'original'))) {
			$vs_media_path = $this->get('media');
		}
		if ($vs_media_path) {
			if (file_exists($vs_media_path) && (abs(filesize($vs_media_path)) > 0)) {
				return false;
			}
		}
		// is it a URL?
		if ($this->_CONFIG->get('allow_fetching_of_media_from_remote_urls')) {
			if  (isURL($vs_media_path)) {
				return false;
			}
		}
		return true;
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
		}
 		
 		$va_media_info = $t_rep->getMediaInfo('media');
 		if (!isset($va_media_info['INPUT'])) { return null; }
 		if (!isset($va_media_info['INPUT']['MIMETYPE'])) { return null; }
 		
 		$vs_mimetype = $va_media_info['INPUT']['MIMETYPE'];
 		
 		$o_type_config = Configuration::load($this->getAppConfig()->get('annotation_type_config'));
 		$va_mappings = $o_type_config->getAssoc('mappings');
 		
 		return $va_mappings[$vs_mimetype];
 	}
 	# ------------------------------------------------------
 	public function getAnnotationPropertyCoderInstance($ps_type) {
 		return ca_representation_annotations::getPropertiesCoderInstance($ps_type);
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns number of annotations attached to current representation
 	 *
 	 * @param array $pa_options Optional array of options. Supported options are:
 	 *			checkAccess - array of access codes to filter count by. Only annotations with an access value set to one of the specified values will be counted.
 	 * @return int Number of annotations
 	 */
 	public function getAnnotationCount($pa_options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		
 		$vs_access_sql = '';
 		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
			$vs_access_sql = ' AND cra.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
 		$o_db = $this->getDb();
 		
 		$qr_annotations = $o_db->query("
 			SELECT 	cra.annotation_id, cra.locale_id, cra.props, cra.representation_id, cra.user_id, cra.type_code, cra.access, cra.status
 			FROM ca_representation_annotations cra
 			WHERE
 				cra.representation_id = ? {$vs_access_sql}
 		", (int)$vn_representation_id);
 		
 		return (int)$qr_annotations->numRows();
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns data for annotations attached to current representation
 	 *
 	 * @param array $pa_options Optional array of options. Supported options are:
 	 *			checkAccess = array of access codes to filter count by. Only annotations with an access value set to one of the specified values will be returned
 	 *			start =
 	 *			max = 
 	 *			labelsOnly =
 	 * @return array List of annotations attached to the current representation, key'ed on annotation_id. Value is an array will all values; annotation labels are returned in the current locale.
 	 */
 	public function getAnnotations($pa_options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		$o_db = $this->getDb();
 		
 		$vs_access_sql = '';
 		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
			$vs_access_sql = ' AND cra.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
 		
 		$qr_annotations = $o_db->query("
 			SELECT 	cra.annotation_id, cra.locale_id, cra.props, cra.representation_id, cra.user_id, cra.type_code, cra.access, cra.status
 			FROM ca_representation_annotations cra
 			WHERE
 				cra.representation_id = ? {$vs_access_sql}
 		", (int)$vn_representation_id);
 		
 		$vs_sort_by_property = $this->getAnnotationSortProperty();
 		$va_annotations = array();
 		
 		$vn_start = (is_array($pa_options) && isset($pa_options['start']) && ((int)$pa_options['start'] > 0)) ? (int)$pa_options['start'] : null;
 		$vn_max = (is_array($pa_options) && isset($pa_options['max']) && ((int)$pa_options['max'] > 0)) ? (int)$pa_options['max'] : 0;
 		
 		while($qr_annotations->nextRow()) {
 			$va_tmp = $qr_annotations->getRow();
 			unset($va_tmp['props']);
 			$o_coder->setPropertyValues($qr_annotations->getVars('props'));
 			foreach($o_coder->getPropertyList() as $vs_property) {
 				$va_tmp[$vs_property] = $o_coder->getProperty($vs_property);
 				$va_tmp[$vs_property.'_raw'] = $o_coder->getProperty($vs_property, true);
 				if ($va_tmp[$vs_property] == $va_tmp[$vs_property.'_raw']) { unset($va_tmp[$vs_property.'_raw']); }
 			}
 			
 			if (!($vs_sort_key = $va_tmp[$vs_sort_by_property])) {
 				$vs_sort_key = '_default_';
 			}
 			
 			$va_annotations[$vs_sort_key][$qr_annotations->get('annotation_id')] = $va_tmp;
 		}
 		
 		ksort($va_annotations, SORT_NUMERIC);
 		
 		// get annotation labels
 		$qr_annotation_labels = $o_db->query("
 			SELECT 	cral.annotation_id, cral.locale_id, cral.name, cral.label_id
 			FROM ca_representation_annotation_labels cral
 			INNER JOIN ca_representation_annotations AS cra ON cra.annotation_id = cral.annotation_id
 			WHERE
 				cra.representation_id = ? AND cral.is_preferred = 1
 		", (int)$vn_representation_id);
 		
 		$va_labels = array();
 		while($qr_annotation_labels->nextRow()) {
 			$va_labels[$qr_annotation_labels->get('annotation_id')][$qr_annotation_labels->get('locale_id')][] = $qr_annotation_labels->get('name');
 		}
 		
 		$va_labels_for_locale = caExtractValuesByUserLocale($va_labels);
 		
 		
 		$va_sorted_annotations = array();
 		foreach($va_annotations as $vs_key => $va_values) {
 			foreach($va_values as $va_val) {
 				$vs_label = is_array($va_labels_for_locale[$va_val['annotation_id']]) ? array_shift($va_labels_for_locale[$va_val['annotation_id']]) : '';
 				$va_val['labels'] = $va_labels[$va_val['annotation_id']] ? $va_labels[$va_val['annotation_id']] : array();
 				$va_val['label'] = $vs_label;
 				$va_sorted_annotations[$va_val['annotation_id']] = $va_val;
 			}
 		}
 		
 		if (($vn_start > 0) || ($vn_max > 0)) {
 			if ($vn_max > 0) {
 				$va_sorted_annotations = array_slice($va_sorted_annotations, (int)$vn_start - 1, (int)$vn_max);
 			} else {
 				$va_sorted_annotations = array_slice($va_sorted_annotations, (int)$vn_start - 1);
 			}
 		}
 		
 		return $va_sorted_annotations;
 	} 
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function addAnnotation($ps_title, $pn_locale_id, $pn_user_id, $pa_properties, $pn_status, $pn_access, $pa_values=null, $pa_options=null) {
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
		
 		$t_annotation = new ca_representation_annotations();
 		$t_annotation->setMode(ACCESS_WRITE);
 		
 		$t_annotation->set('representation_id', $vn_representation_id);
 		$t_annotation->set('type_code', $o_coder->getType());
 		$t_annotation->set('locale_id', $pn_locale_id);
 		$t_annotation->set('user_id', $pn_user_id);
 		$t_annotation->set('status', $pn_status);
 		$t_annotation->set('access', $pn_access);
 		
 		$t_annotation->insert();
 		
 		if ($t_annotation->numErrors()) {
			$this->errors = $t_annotation->errors;
			return false;
		}
		
		if (!$ps_title) { $ps_title = "[BLANK]"; }
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
 		
 		if (isset($pa_options['returnAnnotation']) && (bool)$pa_options['returnAnnotation']) {
 			return $t_annotation;
 		}
 		return $t_annotation->getPrimaryKey();
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function editAnnotation($pn_annotation_id, $pn_locale_id, $pa_properties, $pn_status, $pn_access, $pa_values=null, $pa_options=null) {
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
		
 		$t_annotation = new ca_representation_annotations($pn_annotation_id);
 		if ($t_annotation->getPrimaryKey() && ($t_annotation->get('representation_id') == $vn_representation_id)) {
 			foreach($o_coder->getPropertyList() as $vs_property) {
 				$t_annotation->setPropertyValue($vs_property, $o_coder->getProperty($vs_property));
 			}
 		
 			$t_annotation->setMode(ACCESS_WRITE);
 		
			$t_annotation->set('type_code', $o_coder->getType());
			$t_annotation->set('locale_id', $pn_locale_id);
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
			if (isset($pa_options['returnAnnotation']) && (bool)$pa_options['returnAnnotation']) {
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
 		
 		$t_annotation = new ca_representation_annotations($pn_annotation_id);
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
	protected function getRepresentationAnnotationHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		//if (!$this->getAnnotationType()) { return; }	// don't show bundle if this representation doesn't support annotations
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		$t_item = new ca_representation_annotations();
		$t_item_label = new ca_representation_annotation_labels();
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('t_item', $t_item);
		$o_view->setVar('t_item_label', $t_item_label);
		
		$o_view->setVar('t_subject', $this);
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$va_inital_values = array();
		if (sizeof($va_items = $this->getAnnotations())) {
			$t_rel = $this->getAppDatamodel()->getInstanceByTableName('ca_representation_annotations', true);
			$vs_rel_pk = $t_rel->primaryKey();
			foreach ($va_items as $vn_id => $va_item) {
				if (!($vs_label = $va_item['label'])) { $vs_label = ''; }
				$va_inital_values[$va_item[$t_item->primaryKey()]] = array_merge($va_item, array('id' => $va_item[$vs_rel_pk], 'item_type_id' => $va_item['item_type_id'], 'relationship_type_id' => $va_item['relationship_type_id'], 'label' => $vs_label));
			}
		}
		
		$o_view->setVar('initialValues', $va_inital_values);
		
		return $o_view->render('ca_representation_annotations.php');
	}	
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	protected function _processRepresentationAnnotations($po_request, $ps_form_prefix, $ps_placement_code) {
 		$va_rel_items = $this->getAnnotations();
		$o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType());
		foreach($va_rel_items as $vn_id => $va_rel_item) {
			$this->clearErrors();
			if (strlen($vn_status = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_status_'.$va_rel_item['annotation_id'], pString))) {
				$vn_access = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_access_'.$va_rel_item['annotation_id'], pInteger);
				$vn_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_locale_id_'.$va_rel_item['annotation_id'], pInteger);
				
				$va_properties = array();
				foreach($o_coder->getPropertyList() as $vs_property) {
					$va_properties[$vs_property] = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_'.$vs_property.'_'.$va_rel_item['annotation_id'], pString);
				}

				// edit annotation
				$this->editAnnotation($va_rel_item['annotation_id'], $vn_locale_id, $va_properties, $vn_status, $vn_access);
			
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), 'ca_representation_annotations', $va_rel_item['annotation_id']);
				} else {
					// try to add/edit label
					if ($vs_label = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_label_'.$va_rel_item['annotation_id'], pString)) {
						$t_annotation = new ca_representation_annotations($va_rel_item['annotation_id']);
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
								$po_request->addActionErrors($t_annotation->errors(), 'ca_representation_annotations', 'new_'.$vn_c);
							}
						}
					}
				}
			} else {
				// is it a delete key?
				$this->clearErrors();
				if (($po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_'.$va_rel_item['annotation_id'].'_delete', pInteger)) > 0) {
					// delete!
					$this->removeAnnotation($va_rel_item['annotation_id']);
					if ($this->numErrors()) {
						$po_request->addActionErrors($this->errors(), 'ca_representation_annotations', $va_rel_item['annotation_id']);
					}
				}
			}
		}
 		
 		// check for new annotations to add
 		foreach($_REQUEST as $vs_key => $vs_value ) {
			if (!preg_match('/^'.$ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_status_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
			$vn_c = intval($va_matches[1]);
			if (strlen($vn_status = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_status_new_'.$vn_c, pString)) > 0) {
				$vn_access = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_access_new_'.$vn_c, pInteger);
				$vn_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_locale_id_new_'.$vn_c, pInteger);
				
				$va_properties = array();
				foreach($o_coder->getPropertyList() as $vs_property) {
					$va_properties[$vs_property] = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_'.$vs_property.'_new_'.$vn_c, pString);
				}
				
				// create annotation
				$vs_label = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_label_new_'.$vn_c, pString);
				$vn_annotation_id = $this->addAnnotation($vs_label, $vn_locale_id, $po_request->getUserID(), $va_properties, $vn_status, $vn_access);
				
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), 'ca_representation_annotations', 'new_'.$vn_c);
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
 	public function getDisplayMediaWithAnnotationsHTMLBundle($po_request, $ps_version, $pa_options=null) {
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		$pa_options['poster_frame_url'] = $this->getMediaUrl('media', 'medium');
 		
 		if (!($vs_tag = $this->getMediaTag('media', $ps_version, $pa_options))) {
 			return '';
 		}
 		
 		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('viewer_tag', $vs_tag);
		$o_view->setVar('annotations', $this->getAnnotations($pa_options));
		
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
 	 * @param array $pa_versions A list of file versions to return. If omitted only the "preview" version is returned.
 	 * @return array A list of files attached to the representations. If no files are associated an empty array is returned.
 	 */
 	public function getFileList($pn_representation_id=null, $pn_start=null, $pn_num_files=null, $pa_versions=null) {
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		if (!is_array($pa_versions)) {
 			$pa_versions = array('preview');
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
 			
 			foreach($pa_versions as $vn_i => $vs_version) {
 				$va_files[$vn_multifile_id][$vs_version.'_path'] = $qr_res->getMediaPath('media', $vs_version);
 				$va_files[$vn_multifile_id][$vs_version.'_tag'] = $qr_res->getMediaTag('media', $vs_version);
 				$va_files[$vn_multifile_id][$vs_version.'_url'] = $qr_res->getMediaUrl('media', $vs_version);
 				
 				$va_info = $qr_res->getMediaInfo('media', $vs_version);
 				$va_files[$vn_multifile_id][$vs_version.'_width'] = $va_info['WIDTH'];
 				$va_files[$vn_multifile_id][$vs_version.'_height'] = $va_info['HEIGHT'];
 				$va_files[$vn_multifile_id][$vs_version.'_mimetype'] = $va_info['MIMETYPE'];
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
 	public function addCaptionFile($ps_filepath, $pn_locale_id, $pa_options=null) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_caption = new ca_object_representation_captions();
 		if ($t_caption->load(array('representation_id' => $this->getPrimaryKey(), 'locale_id' => $pn_locale_id))) {
 			return null;
 		}
 		
 		$t_caption->setMode(ACCESS_WRITE);
 		$t_caption->set('representation_id', $this->getPrimaryKey());
 		$va_tmp = explode("/", $ps_filepath);
 		$t_caption->set('caption_file', $ps_filepath, array('original_filename' => caGetOption('originalFilename', $pa_options, array_pop($va_tmp))));
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
 	 * @param array $pa_options
 	 * @return array A list of caption files attached to the representations. If no files are associated an empty array is returned.
 	 */
 	public function getCaptionFileList($pn_representation_id=null, $pa_locale_ids=null, $pa_options=null) {
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
 			$va_files[$vn_caption_id]['filesize'] = caFormatFileSize(filesize($va_files[$vn_caption_id]['path']));
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
 	#
 	# ------------------------------------------------------
 	/**
 	 * Matching method to ca_objects::getRepresentations(), except this one only returns a single representation - the currently loaded one
 	 *
 	 * @param array $pa_versions
 	 * @param array $pa_version_sizes
 	 * @param array $pa_options
 	 *
 	 * @return array
 	 */
 	public function getRepresentations($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!is_array($pa_versions)) { 
 			$pa_versions = array('preview170');
 		}
 		
 		$o_db = $this->getDb();
 		
 		$qr_reps = $o_db->query("
 			SELECT caor.representation_id, caor.media, caor.access, caor.status, l.name, caor.locale_id, caor.media_metadata, caor.type_id
 			FROM ca_object_representations caor
 			LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
 			WHERE
 				caor.representation_id = ?  AND caor.deleted = 0
 				{$vs_is_primary_sql}
 				{$vs_access_sql}
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
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, array_merge($pa_options, array('viewer_width' => $vn_width, 'viewer_height' => $vn_height)));
 				} else {
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, $pa_options);
 				}
 				$va_tmp['urls'][$vs_version] = $qr_reps->getMediaUrl('media', $vs_version);
 				$va_tmp['paths'][$vs_version] = $qr_reps->getMediaPath('media', $vs_version);
 				$va_tmp['info'][$vs_version] = $qr_reps->getMediaInfo('media', $vs_version);
 				
 				$va_dimensions = array();
 				if (isset($va_tmp['info'][$vs_version]['WIDTH']) && isset($va_tmp['info'][$vs_version]['HEIGHT'])) {
					if (($vn_w = $va_tmp['info'][$vs_version]['WIDTH']) && ($vn_h = $va_tmp['info'][$vs_version]['WIDTH'])) {
						$va_dimensions[] = $va_tmp['info'][$vs_version]['WIDTH'].'p x '.$va_tmp['info'][$vs_version]['HEIGHT'].'p';
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
	 * @param array $pa_options An array of options:
	 *		checkAccess = Array of access values to filter on
	 * @return array List of media, key'ed by representation_id
	 */
	public function getRepresentationMediaForIDs($pa_ids, $pa_versions, $pa_options = null) {
		if (!is_array($pa_ids) || !sizeof($pa_ids)) { return array(); }
		if (!is_array($pa_options)) { $pa_options = array(); }
		$va_access_values = $pa_options["checkAccess"];
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
 	public function getValuesForExport($pa_options=null){
 		$va_export = parent::getValuesForExport($pa_options);
 		// this section tends to contain wonky chars that are close to impossible to clean up
 		// if you read through the EXIF specs you know why ...
 		if(isset($va_export['media_metadata']['EXIF']['IFD0'])){
 			unset($va_export['media_metadata']['EXIF']['IFD0']);
 		}
 		return $va_export;
 	}
 	/**
 	 * 
 	 *
 	 * @param RequestHTTP $po_request
 	 * @param array $pa_options
 	 * @param array $pa_additional_display_options
 	 * @return string HTML output
 	 */
 	public function getRepresentationViewerHTMLBundle($po_request, $pa_options=null, $pa_additional_display_options=null) {
 		$va_access_values = (isset($pa_options['access']) && is_array($pa_options['access'])) ? $pa_options['access'] : array();	
 		$vs_display_type = (isset($pa_options['display']) && $pa_options['display']) ? $pa_options['display'] : 'media_overlay';	
 		$vs_container_dom_id = (isset($pa_options['containerID']) && $pa_options['containerID']) ? $pa_options['containerID'] : null;	
 		$vn_object_id = (isset($pa_options['object_id']) && $pa_options['object_id']) ? $pa_options['object_id'] : null;
 		$vn_item_id = (isset($pa_options['item_id']) && $pa_options['item_id']) ? $pa_options['item_id'] : null;
 		$vn_order_item_id = (isset($pa_options['order_item_id']) && $pa_options['order_item_id']) ? $pa_options['order_item_id'] : null;
 		$vb_media_editor = (isset($pa_options['mediaEditor']) && $pa_options['mediaEditor']) ? true : false;
 		$vb_no_controls = (isset($pa_options['noControls']) && $pa_options['noControls']) ? true : false;
 		
 		$vn_item_id = (isset($pa_options['item_id']) && $pa_options['item_id']) ? $pa_options['item_id'] : null;
 		
 		$t_object = new ca_objects($vn_object_id);
 		//if (!$t_object->getPrimaryKey()) { return false; }
 		
 		if(!$this->getPrimaryKey()) {
 			$this->load($t_object->getPrimaryRepresentationID(array('checkAccess' => $va_access_values)));
 		}
 		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$t_set_item = new ca_set_items();
		if ($vn_item_id) { $t_set_item->load($vn_item_id); }
		
		$t_order_item = new ca_commerce_order_items();
		if ($vn_order_item_id) { $t_order_item->load($vn_order_item_id); }
		
		$o_view->setVar('containerID', $vs_container_dom_id);
		
		$o_view->setVar('t_object_representation', $this);
 		if (($vn_representation_id = $this->getPrimaryKey()) && ((!sizeof($va_access_values) || in_array($this->get('access'), $va_access_values)))) { 		// check rep access
			$va_rep_display_info = caGetMediaDisplayInfo($vs_display_type, $this->getMediaInfo('media', 'INPUT', 'MIMETYPE'));
			$va_rep_display_info['poster_frame_url'] = $this->getMediaUrl('media', $va_rep_display_info['poster_frame_version']);
			
			$o_view->setVar('num_multifiles', $this->numFiles());
				
 			if (isset($pa_options['use_book_viewer'])) {
 				$va_rep_display_info['use_book_viewer'] = (bool)$pa_options['use_book_viewer'];
 			}		
			$o_view->setVar('display_type', $vs_display_type);
			
			if (is_array($pa_additional_display_options)) { $va_rep_display_info = array_merge($va_rep_display_info, $pa_additional_display_options); }
			$o_view->setVar('display_options', $va_rep_display_info);
			$o_view->setVar('representation_id', $pn_representation_id);
			$o_view->setVar('t_object_representation', $this);
			$o_view->setVar('versions', $va_versions = $this->getMediaVersions('media'));
			
			$t_media = new Media();
			$o_view->setVar('version_type', $t_media->getMimetypeTypename($this->getMediaInfo('media', 'original', 'MIMETYPE')));
		
			if ($t_object->getPrimaryKey()) { 
				$o_view->setVar('reps', $va_reps = $t_object->getRepresentations(array('icon'), null, array("return_with_access" => $va_access_values)));
				
				$vn_next_rep = $vn_prev_rep = null;
				
				$va_rep_list = array_values($va_reps);
				foreach($va_rep_list as $vn_i => $va_rep) {
					if ($va_rep['representation_id'] == $vn_representation_id) {
						if (isset($va_rep_list[$vn_i - 1])) {
							$vn_prev_rep = $va_rep_list[$vn_i - 1]['representation_id'];
						}
						if (isset($va_rep_list[$vn_i + 1])) {
							$vn_next_rep = $va_rep_list[$vn_i + 1]['representation_id'];
						}
						$o_view->setVar('representation_index', $vn_i + 1);
					}
				}
				$o_view->setVar('previous_representation_id', $vn_prev_rep);
				$o_view->setVar('next_representation_id', $vn_next_rep);
			}	
			$ps_version 	= $po_request->getParameter('version', pString);
			if (!in_array($ps_version, $va_versions)) { 
				if (!($ps_version = $va_rep_display_info['display_version'])) { $ps_version = null; }
			}
			$o_view->setVar('version', $ps_version);
			$o_view->setVar('version_info', $this->getMediaInfo('media', $ps_version));
			
 			$o_view->setVar('t_object', $t_object);
 			$o_view->setVar('t_set_item', $t_set_item);
 			$o_view->setVar('t_order_item', $t_order_item);
 			$o_view->setVar('only_show_reps_in_order', $vb_only_show_reps_in_order);
 			$o_view->setVar('use_media_editor', $vb_media_editor);
 			$o_view->setVar('noControls', $vb_no_controls);
		}
		return $o_view->render('representation_viewer_html.php');
 	}
 	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle (for use in a ca_object_representations editor form) for media
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getMediaDisplayHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name.'_media_display');
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
	 * @param array $pa_options Array of options. Supported options are 
	 *			noCache = If set to true then label cache is bypassed; default is true
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getCaptionHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name.'_captions');
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
		$o_view->setVar('t_caption', new ca_object_representation_captions());
		
		//$va_media_info = $this->getMediaInfo('media');
		//if (!is_array($va_media_info)) { $va_media_info = array('original' => array('PROPERTIES' => array('typename' => null))); }
		//$o_view->setVar('representation_typename', $va_media_info['original']['PROPERTIES']['typename']);
		$o_view->setVar('representation_num_caption_files', $this->numCaptionFiles());
		$o_view->setVar('initialValues', $this->getCaptionFileList());
		
		return $o_view->render('ca_object_representation_captions.php');
	}
	# ------------------------------------------------------
	/** 
	 * 
	 */
	protected function processBundlesBeforeBaseModelSave($pa_bundles, $ps_form_prefix, $po_request, $pa_options=null) {
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
	 * Check it a file already exists in the database as a representation
	 *
	 * @param string $ps_filepath The full path to the file
	 * @return mixed ca_object_representations instance representing the first representation that contains the file, if representation exists with this file, false if the file does not yet exist
	 */
	static function mediaExists($ps_filepath) {
		if (!file_exists($ps_filepath)) { return null; }
		$vs_md5 = md5_file($ps_filepath);
		$t_rep = new ca_object_representations();
		if ($t_rep->load(array('md5' => $vs_md5, 'deleted' => 0))) { 
			return $t_rep;
		}
		
		return false;
	}
	# ------------------------------------------------------
}
?>