<?php
/** ---------------------------------------------------------------------
 * app/models/ca_objects.php : table access class for table ca_objects
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_objects_x_object_representations.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");


BaseModel::$s_ca_models_definitions['ca_objects'] = array(
 	'NAME_SINGULAR' 	=> _t('object'),
 	'NAME_PLURAL' 		=> _t('objects'),
 	'FIELDS' 			=> array(
		'object_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this object')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Identifier of parent object; is null if object is root of hierarchy.'
		),
		'hier_object_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Object hierarchy', 'DESCRIPTION' => 'Identifier of object that is root of the object hierarchy.'
		),
		'lot_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'DEFAULT' => '',
				'LABEL' => _t('Lot'), 'DESCRIPTION' => _t('Lot this object belongs to; is null if object is not part of a lot.')
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the object originates.')
		),
		'source_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'object_sources',
				'LABEL' => _t('Source'), 'DESCRIPTION' => _t('Administrative source of object. This value is often used to indicate the administrative sub-division or legacy database from which the object originates, but can also be re-tasked for use as a simple classification tool if needed.')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LIST_CODE' => 'object_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the object. In CollectiveAccess every object has a single "instrinsic" type that determines the set of descriptive, technical and administrative metadata that can be applied to it. As such this type is "low-level" and directly tied to the form of the object - eg. photograph, book, analog video recording, etc.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Object identifier'), 'DESCRIPTION' => _t('A unique alphanumeric identifier for this object. This is usually equivalent to the "accession number" in museum settings.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Sortable object identifier', 'DESCRIPTION' => 'Value used for sorting objects on identifier value.',
				'BOUNDS_LENGTH' => array(0,255)
		),
		'item_status_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LIST_CODE' => 'object_statuses',
				'LABEL' => _t('Accession status'), 'DESCRIPTION' => _t('Indicates accession/collection status of object. (eg. accessioned, pending accession, loan, non-accessioned item, etc.)')
		),
		'acquisition_type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LIST_CODE' => 'object_acq_types',
				'LABEL' => _t('Acquisition method'), 'DESCRIPTION' => _t('Indicates method employed to acquire the object.')
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Serialized array used to store source information for object information retrieved via web services [NOT IMPLEMENTED YET].'
		),
		'hier_left' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - left bound', 'DESCRIPTION' => 'Left-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		),
		'hier_right' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - right bound', 'DESCRIPTION' => 'Right-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		),
		'extent' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Extent'), 'DESCRIPTION' => _t('The extent of the object. This is typically the number of discrete items that compose the object represented by this record. It is stored as a whole number (eg. 1, 2, 3...).')
		),
		'extent_units' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				'LABEL' => _t('Extent units'), 'DESCRIPTION' => _t('Units of extent value. (eg. pieces, items, components, reels, etc.)'),
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if object is accessible to the public or not.')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the object record.')
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

class ca_objects extends BundlableLabelableBaseModelWithAttributes implements IBundleProvider {
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
	protected $TABLE = 'ca_objects';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'object_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('idno');

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
	protected $ORDER_BY = array('idno');

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
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_ADHOC_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_objects';
	protected $HIERARCHY_ID_FLD				=	'hier_object_id';
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			'lot_id'
		),
		"RELATED_TABLES" => array(
		
		)
	);

	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_object_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'object_types';	// list code (ca_lists.list_code) of list defining types for this table
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_objects_x_objects';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ObjectSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ObjectSearchResult';
	
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
		parent::__construct($pn_id);
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
		$this->BUNDLES['ca_object_representations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Media representations'));
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		$this->BUNDLES['ca_storage_locations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations'));
		$this->BUNDLES['ca_loans'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans'));
		$this->BUNDLES['ca_movements'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements'));
		
		$this->BUNDLES['ca_object_lots'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related lot'));
		$this->BUNDLES['ca_object_events'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related events'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		$this->BUNDLES['ca_sets'] = array('type' => 'special', 'repeating' => true, 'label' => _t('Sets'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related=false){
		// nuke related representations
		foreach($this->getRepresentations() as $va_rep){
			$this->removeRepresentation($va_rep["representation_id"]);
		}

		return parent::delete($pb_delete_related);
	}
	# ------------------------------------------------------
	/**
	 * @param array $pa_options
	 *		duplicate_media
	 */
	public function duplicate($pa_options=null) {
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction($o_t = new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			$o_t = $this->getTransaction();
		}
		
		if ($t_dupe = parent::duplicate($pa_options)) {
			$vb_duplicate_media = isset($pa_options['duplicate_media']) && $pa_options['duplicate_media'];
		
			if ($vb_duplicate_media) { 
				// Try to link representations
				$o_db = $this->getDb();
				
				$qr_res = $o_db->query("
					SELECT *
					FROM ca_objects_x_object_representations
					WHERE object_id = ?
				", (int)$this->getPrimaryKey());
				
				$va_reps = array();
				while($qr_res->nextRow()) {
					$va_reps[$qr_res->get('representation_id')] = $qr_res->getRow();
				}
				
				$t_object_x_rep = new ca_objects_x_object_representations();
				foreach($va_reps as $vn_representation_id => $va_rep) {
					$t_object_x_rep->setMode(ACCESS_WRITE);
					$va_rep['object_id'] = $t_dupe->getPrimaryKey();
					$t_object_x_rep->set($va_rep);
					$t_object_x_rep->insert();
					
					if ($t_object_x_rep->numErrors()) {
						$this->errors = $t_object_x_rep->errors;
						if ($vb_we_set_transaction) { $this->removeTransaction(false);}
						return false;
					}
				}
			}
		}
		
		
		if ($vb_we_set_transaction) { $this->removeTransaction(true);}
		return $t_dupe;
	}
	# ------------------------------------------------------
	
 	# ------------------------------------------------------
 	# Representations
 	# ------------------------------------------------------
 	/**
 	 * Returns information about representations linked to the currently loaded object. Use this if you want to get the urls, tags and other information for all representations associated with a given object.
 	 *
 	 * @param array $pa_versions An array of media versions to include information for. If you omit this then a single version, 'preview170', is assumed by default.
 	 * @param array $pa_version_sizes Optional array of sizes to force specific versions to. The array keys are version names; the values are arrays with two keys: 'width' and 'height'; if present these values will be used in lieu of the actual values in the database
 	 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
 	 *		return_primary_only - If true then only the primary representation will be returned
 	 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
 	 *		checkAccess - synonym for return_with_access
 	 *		.. and options supported by getMediaTag() .. [they are passed through]
 	 *	
 	 * @return array An array of information about the linked representations
 	 */
 	public function getRepresentations($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (caGetBundleAccessLevel('ca_objects', 'ca_object_representations') == __CA_BUNDLE_ACCESS_NONE__) {
			return null;
		}
 		
 		if (!is_array($pa_versions)) { 
 			$pa_versions = array('preview170');
 		}
 		
 		if (isset($pa_options['return_primary_only']) && $pa_options['return_primary_only']) {
 			$vs_is_primary_sql = ' AND (caoor.is_primary = 1)';
 		} else {
 			$vs_is_primary_sql = '';
 		}
 		
 		if ($pa_options['checkAccess']) { $pa_options['return_with_access'] = $pa_options['checkAccess']; }
 		if (is_array($pa_options['return_with_access']) && sizeof($pa_options['return_with_access']) > 0) {
 			$vs_access_sql = ' AND (caor.access IN ('.join(", ", $pa_options['return_with_access']).'))';
 		} else {
 			$vs_access_sql = '';
 		}

 		
 		$o_db = $this->getDb();
 		
 		$qr_reps = $o_db->query("
 			SELECT caor.representation_id, caor.media, caoor.is_primary, caor.access, caor.status, l.name, caor.locale_id, caor.media_metadata, caor.type_id, caor.idno, caor.idno_sort
 			FROM ca_object_representations caor
 			INNER JOIN ca_objects_x_object_representations AS caoor ON caor.representation_id = caoor.representation_id
 			LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
 			WHERE
 				caoor.object_id = ? AND deleted = 0
 				{$vs_is_primary_sql}
 				{$vs_access_sql}
 			ORDER BY
 				caoor.rank, caoor.is_primary DESC
 		", (int)$vn_object_id);
 		
 		$va_reps = array();
 		$t_rep = new ca_object_representations();
 		while($qr_reps->nextRow()) {
 			$vn_rep_id = $qr_reps->get('representation_id');
 			
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
 				
 				$va_tmp['dimensions'][$vs_version] = caGetRepresentationDimensionsForDisplay($qr_reps, 'original', array());
 			}
 			
 				
			if (isset($va_info['INPUT']['FETCHED_FROM']) && ($vs_fetched_from_url = $va_info['INPUT']['FETCHED_FROM'])) {
				$va_tmp['fetched_from'] = $vs_fetched_from_url;
				$va_tmp['fetched_on'] = (int)$va_info['INPUT']['FETCHED_ON'];
			}
 			
 			$va_tmp['num_multifiles'] = $t_rep->numFiles($vn_rep_id);
 			$va_reps[$vn_rep_id] = $va_tmp;
 		}
 		
 		$va_labels = $t_rep->getPreferredDisplayLabelsForIDs(array_keys($va_reps));
 		foreach($va_labels as $vn_rep_id => $vs_label) {
 			$va_reps[$vn_rep_id]['label'] = $vs_label;
 		}
 		return $va_reps;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Finds and returns information about representations meeting the specified criteria. Returns information in the same format as getRepresentations()
 	 *
 	 * @param array $pa_options Array of criteria options to use when selecting representations. Options include: 
 	 *		mimetypes = array of mimetypes to return
 	 *		sortby = if set, representations are return sorted using the criteria in ascending order. Valid values are 'filesize' (sort by file size), 'duration' (sort by length of time-based media)
 	 *
 	 * @return array List of representations. Each entry in the list is an associative array of the same format as returned by getRepresentations() and includes properties, tags and urls for the representation.
 	 */
 	public function findRepresentations($pa_options) {
 		$va_mimetypes = array();
 		if (isset($pa_options['mimetypes']) && (is_array($pa_options['mimetypes'])) && (sizeof($pa_options['mimetypes']))) {
 			$va_mimetypes = array_flip($pa_options['mimetypes']);
 		}
 		
 		$vs_sortby = null;
 		if (isset($pa_options['sortby']) && $pa_options['sortby'] && in_array($pa_options['sortby'], array('filesize', 'duration'))) {
 			$vs_sortby = $pa_options['sortby'];
 		}
 		
 		$va_reps = $this->getRepresentations(array('original'));
 		$va_found_reps = array();
 		foreach($va_reps as $vn_i => $va_rep) {
 			if((sizeof($va_mimetypes)) && isset($va_mimetypes[$va_rep['info']['original']['MIMETYPE']])) {
 				switch($vs_sortby) {
 					case 'filesize':
 						$va_found_reps[$va_rep['info']['original']['FILESIZE']][] = $va_rep;
 						break;
 					case 'duration':
 						$vn_duration = $va_rep['info']['original']['PROPERTIES']['duration'];
 						$va_found_reps[$vn_duration][] = $va_rep;
 						break;
 					default:
 						$va_found_reps[] = $va_rep;
 						break;
 				}
 			}
 		}
 		
 		if ($vs_sortby) {
 			ksort($va_found_reps);
 			
 			$va_tmp = array();
 			foreach($va_found_reps as $va_found_rep_groups) {
 				foreach($va_found_rep_groups as $va_found_rep) {
 					$va_tmp[] = $va_found_rep;
 				}
 			}
 			$va_found_reps = $va_tmp;
 		}
 		
 		return $va_found_reps;
 	}
 	# ------------------------------------------------------
 	# Representations
 	# ------------------------------------------------------
 	/**
 	 * Returns array containing representation_ids for all representations linked to the currently loaded ca_objects row
 	 *
 	 * @param array $pa_options An array of options. Supported options are:
 	 *		return_primary_only - If true then only the primary representation will be returned
 	 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
 	 *		checkAccess - synonym for return_with_access
 	 *
 	 * @return array A list of representation_ids
 	 */
 	public function getRepresentationIDs($pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!is_array($pa_versions)) { 
 			$pa_versions = array('preview170');
 		}
 		
 		if (isset($pa_options['return_primary_only']) && $pa_options['return_primary_only']) {
 			$vs_is_primary_sql = ' AND (caoor.is_primary = 1)';
 		} else {
 			$vs_is_primary_sql = '';
 		}
 		
 		if (!is_array($pa_options['return_with_access']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) > 0) {
 			$pa_options['return_with_access'] = $pa_options['checkAccess'];
 		}
 		
 		if (is_array($pa_options['return_with_access']) && sizeof($pa_options['return_with_access']) > 0) {
 			$vs_access_sql = ' AND (caor.access IN ('.join(", ", $pa_options['return_with_access']).'))';
 		} else {
 			$vs_access_sql = '';
 		}

 		
 		$o_db = $this->getDb();
 		$qr_reps = $o_db->query("
 			SELECT caor.representation_id, caoor.is_primary
 			FROM ca_object_representations caor
 			INNER JOIN ca_objects_x_object_representations AS caoor ON caor.representation_id = caoor.representation_id
 			WHERE
 				caoor.object_id = ? AND caor.deleted = 0
 				{$vs_is_primary_sql}
 				{$vs_access_sql}
 		", (int)$vn_object_id);
 		
 		$va_rep_ids = array();
 		while($qr_reps->nextRow()) {
 			$va_rep_ids[$qr_reps->get('representation_id')] = ($qr_reps->get('is_primary') == 1) ? true : false;
 		}
 		return $va_rep_ids;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns number of representations attached to the currently loaded ca_objects row
 	 *
 	 * @param array $pa_options Optional array of options. Supported options include:
 	 *		return_with_type - A type to restrict the count to. Can be either an integer type_id or item_code string
 	 *		return_with_access - An array of access values to restrict counts to
 	 *		checkAccess - synonym for return_with_access
 	 *
 	 * @return integer The number of representations
 	 */
 	public function getRepresentationCount($pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		$vs_type_sql = '';
 		if (isset($pa_options['return_with_type']) && $pa_options['return_with_type']) {
 			if (!is_numeric($pa_options['return_with_type'])) {
 				$t_list = new ca_lists();
 				$pa_options['return_with_type'] = $t_list->getItemIDFromList('object_representation_types', $pa_options['return_with_type']);
 			}
 			if (intval($pa_options['return_with_type']) > 0) {
 				$vs_type_sql = ' AND (caor.type_id = '.intval($pa_options['return_with_type']).')';
 			}
 		} 
 		
 		if (!is_array($pa_options['return_with_access']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) > 0) {
 			$pa_options['return_with_access'] = $pa_options['checkAccess'];
 		}
 		
 		if (is_array($pa_options['return_with_access']) && sizeof($pa_options['return_with_access']) > 0) {
 			$vs_access_sql = ' AND (caor.access IN ('.join(", ", $pa_options['return_with_access']).'))';
 		} else {
 			$vs_access_sql = '';
 		}
 		
 		$o_db = $this->getDb();
 		
 		$qr_reps = $o_db->query("
 			SELECT count(*) c
 			FROM ca_object_representations caor
 			INNER JOIN ca_objects_x_object_representations AS caoor ON caor.representation_id = caoor.representation_id
 			LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
 			WHERE
 				caoor.object_id = ? AND caor.deleted = 0
 				{$vs_type_sql}
 				{$vs_access_sql}
 		", (int)$vn_object_id);

		$qr_reps->nextRow();
		
		return (int)$qr_reps->get('c');
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns primary representation for the object; versions specified in $pa_versions are included. See description
 	 * of ca_objects::getRepresentations() for a description of returned values.
 	 *
 	 * @param array $pa_versions An array of media versions to include information for. If you omit this then a single version, 'preview170', is assumed by default.
 	 * @param array $pa_version_sizes Optional array of sizes to force specific versions to. The array keys are version names; the values are arrays with two keys: 'width' and 'height'; if present these values will be used in lieu of the actual values in the database
 	 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
 	 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
 	 *		checkAccess - synonym for return_with_access
 	 *		.. and options supported by getMediaTag() .. [they are passed through]
 	 *
 	 * @return array An array of information about the linked representations
 	 */
 	public function getPrimaryRepresentation($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		if(is_array($va_reps = $this->getRepresentations($pa_versions, $pa_version_sizes, array_merge($pa_options, array('return_primary_only' => 1))))) {
 			return array_pop($va_reps);
 		}
 		return array();
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns representation_id of primary representation for the object.
 	 *
 	 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
 	 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
 	 *		checkAccess - synonym for return_with_access
 	 *
 	 * @return integer A representation_id
 	 */
 	public function getPrimaryRepresentationID($pa_options=null) {
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		$va_rep_ids = $this->getRepresentationIDs(array_merge($pa_options, array('return_primary_only' => 1)));
 		if (!is_array($va_rep_ids)) { return null; }
 		foreach($va_rep_ids as $vn_representation_id => $vb_is_primary) {
 			if ($vb_is_primary) { return $vn_representation_id; }
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns ca_object_representations instance loaded with primary representation for the current ca_objects row
 	 *
 	 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
 	 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
 	 *		checkAccess - synonym for return_with_access
 	 *
 	 * @return ca_object_representation A model instance for the primary representation
 	 */
 	public function getPrimaryRepresentationInstance($pa_options=null) {
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		if (!($vn_rep_id = $this->getPrimaryRepresentationID($pa_options))) { return null; }
 		
 		$t_rep = new ca_object_representations($vn_rep_id);
 		
 		return ($t_rep->getPrimaryKey()) ? $t_rep : null;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns representations linked to the currently loaded object in a ObjectRepresentationSearchResult instance. 
 	 * Use this if you want to efficiently access information, including attributes, labels and intrinsics, for all representations associated with a given object.
 	 *
 	 *  @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
 	 *		return_primary_only - If true then only the primary representation will be returned
 	 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
 	 *		checkAccess - synonym for return_with_access
 	 *
 	 * @return ObjectRepresentationSearchResult Search result containing all representations linked to the currently loaded object
 	 */
 	public function getRepresentationsAsSearchResult($pa_options=null) {
 		$va_representation_ids = $this->getRepresentationIDs($pa_options);
 		
 		if(is_array($va_representation_ids) && sizeof($va_representation_ids)) {
 			return $this->makeSearchResult('ca_object_representations', array_keys($va_representation_ids));
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/** 
 	 * Add media represention to currently loaded object
 	 *
 	 * @param $ps_media_path - the path to the media you want to add
 	 * @param $pn_type_id - the item_id of the representation type, in the ca_list with list_code 'object_represention_types'
 	 * @param $pn_locale_id - the locale_id of the locale of the representation
 	 * @param $pn_status - the status code for the representation (as defined in item_value fields of items in the 'workflow_statuses' ca_list)
 	 * @param $pn_access - the access code for the representation (as defined in item_value fields of items in the 'access_statuses' ca_list)
 	 * @param $pb_is_primary - if set to true, representation is designated "primary." Primary representation are used in cases where only one representation is required (such as search results). If a primary representation is already attached to this object, then it will be changed to non-primary as only one representation can be primary at any given time. If no primary representations exist, then the new representation will always be marked primary no matter what the setting of this parameter (there must always be a primary representation, if representations are defined).
 	 * @param $pa_values - array of attributes to attach to new representation
 	 * @param $pa_options - an array of options passed through to BaseModel::set() when creating the new representation. Currently supported options:
 	 *		original_filename - the name of the file being uploaded; will be recorded in the database and used as the filename when the file is subsequently downloaded
 	 *		rank - a numeric rank used to order the representations when listed
 	 */
 	public function addRepresentation($ps_media_path, $pn_type_id, $pn_locale_id, $pn_status, $pn_access, $pb_is_primary, $pa_values=null, $pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!$pn_locale_id) { $pn_locale_id = ca_locales::getDefaultCataloguingLocaleID(); }
 		
 		$t_rep = new ca_object_representations();
 		
 		if ($this->inTransaction()) {
 			$t_rep->setTransaction($this->getTransaction());
 		}
 		
 		$t_rep->setMode(ACCESS_WRITE);
 		$t_rep->set('type_id', $pn_type_id);
 		$t_rep->set('locale_id', $pn_locale_id);
 		$t_rep->set('status', $pn_status);
 		$t_rep->set('access', $pn_access);
 		$t_rep->set('media', $ps_media_path, $pa_options);
 		
 		
 		if (is_array($pa_values)) {
 			if (isset($pa_values['idno'])) {
 				$t_rep->set('idno', $pa_values['idno']);
 			}
			foreach($pa_values as $vs_element => $va_value) { 					
				if (is_array($va_value)) {
					// array of values (complex multi-valued attribute)
					$t_rep->addAttribute(
						array_merge($va_value, array(
							'locale_id' => $pn_locale_id
						)), $vs_element);
				} else {
					// scalar value (simple single value attribute)
					if ($va_value) {
						$t_rep->addAttribute(array(
							'locale_id' => $pn_locale_id,
							$vs_element => $va_value
						), $vs_element);
					}
				}
			}
		}
 		
 		$t_rep->insert();
 		
 		if ($t_rep->numErrors()) {
 			$this->errors = array_merge($this->errors, $t_rep->errors());
 			return false;
 		}
 		
 		if ($t_rep->getPreferredLabelCount() == 0) {
			$vs_label = (isset($pa_values['name']) && $pa_values['name']) ? $pa_values['name'] : '['._t('BLANK').']';
			
			$t_rep->addLabel(array('name' => $vs_label), $pn_locale_id, null, true);
			if ($t_rep->numErrors()) {
				$this->errors = array_merge($this->errors, $t_rep->errors());
				return false;
			}
		}
			
 		$t_oxor = new ca_objects_x_object_representations();
 		if ($this->inTransaction()) {
 			$t_oxor->setTransaction($this->getTransaction());
 		}
 		$t_oxor->setMode(ACCESS_WRITE);
 		$t_oxor->set('object_id', $vn_object_id);
 		$t_oxor->set('representation_id', $t_rep->getPrimaryKey());
 		$t_oxor->set('is_primary', $pb_is_primary ? 1 : 0);
 		$t_oxor->set('rank', isset($pa_options['rank']) ? (int)$pa_options['rank'] : null);
 		$t_oxor->insert();
 		
 		
 		if ($t_oxor->numErrors()) {
 			$this->errors = array_merge($this->errors, $t_oxor->errors());
 			$t_rep->delete();
 			if ($t_rep->numErrors()) {
 				$this->errors = array_merge($this->errors, $t_rep->errors());
 			}
 			return false;
 		}

		//
		// Perform mapping of embedded metadata for newly uploaded representation with respect
		// to ca_objects and ca_object_representation records
		//
		$va_metadata = $t_rep->get('media_metadata', array('binary' => true));
		if (caExtractEmbeddedMetadata($this, $va_metadata, $pn_locale_id)) {
			$this->update();
		}
		
 		return $t_oxor->getPrimaryKey();
 	}
 	# ------------------------------------------------------
 	/**
 	 * Convenience method to edit a representation instance. Allows to you edit a linked representation from a ca_objects instance.
 	 * 
 	 * @param int representation_id
 	 * @param string $ps_media_path
 	 * @param int $pn_locale_id
 	 * @param int $pn_status
 	 * @param int $pn_access
 	 * @param bool $pb_is_primary
 	 * @param array $pa_values
 	 * @param array $pa_options
 	 *
 	 * @return bool True on success, false on failure, null if no row has been loaded into the object model 
 	 */
 	public function editRepresentation($pn_representation_id, $ps_media_path, $pn_locale_id, $pn_status, $pn_access, $pb_is_primary, $pa_values=null, $pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		
 		$t_rep = new ca_object_representations();
 		if ($this->inTransaction()) {
 			$t_rep->setTransaction($this->getTransaction());
 		}
 		if (!$t_rep->load(array('representation_id' => $pn_representation_id))) {
 			$this->postError(750, _t("Representation id=%1 does not exist", $pn_representation_id), "ca_objects->editRepresentation()");
 			return false;
 		} else {
			$t_rep->setMode(ACCESS_WRITE);
			$t_rep->set('locale_id', $pn_locale_id);
			$t_rep->set('status', $pn_status);
			$t_rep->set('access', $pn_access);
			
			if ($ps_media_path) {
				$t_rep->set('media', $ps_media_path, $pa_options);
			}
			
			if (is_array($pa_values)) {
				if (isset($pa_values['idno'])) {
					$t_rep->set('idno', $pa_values['idno']);
				}
				foreach($pa_values as $vs_element => $va_value) { 					
					if (is_array($va_value)) {
						// array of values (complex multi-valued attribute)
						$t_rep->replaceAttribute(
							array_merge($va_value, array(
								'locale_id' => $pn_locale_id
							)), $vs_element);
					} else {
						// scalar value (simple single value attribute)
						if ($va_value) {
							$t_rep->replaceAttribute(array(
								'locale_id' => $pn_locale_id,
								$vs_element => $va_value
							), $vs_element);
						}
					}
				}
			}
			
			$t_rep->update();
			
			if ($t_rep->numErrors()) {
				$this->errors = array_merge($this->errors, $t_rep->errors());
 				return false;
			}
			
			$t_oxor = new ca_objects_x_object_representations();
			if (!$t_oxor->load(array('object_id' => $vn_object_id, 'representation_id' => $pn_representation_id))) {
				$this->postError(750, _t("Representation id=%1 is not related to object id=%2", $pn_representation_id, $vn_object_id), "ca_objects->editRepresentation()");
				return false;
			} else {
				$t_oxor->setMode(ACCESS_WRITE);
				$t_oxor->set('is_primary', $pb_is_primary ? 1 : 0);
				
				if (isset($pa_options['rank']) && ($vn_rank = (int)$pa_options['rank'])) {
					$t_oxor->set('rank', $vn_rank);
				}
				
				$t_oxor->update();
				
				if ($t_oxor->numErrors()) {
					$this->errors = array_merge($this->errors, $t_oxor->errors());
					return false;
				}
			}
			
			return true;
		}
		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Remove a single representation from the currently loaded object. Note that the representation will be removed from the database completed, so if it is also linked to other objects it will be removed from them as well.
 	 *
 	 * @param int $pn_representation_id The representation_id of the representation to remove
 	 * @return bool True if delete succeeded, false if there was an error. You can get the error messages by calling getErrors() on the ca_objects instance.
 	 */
 	public function removeRepresentation($pn_representation_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_rep = new ca_object_representations();
 		if (!$t_rep->load(array('representation_id' => $pn_representation_id))) {
 			$this->postError(750, _t("Representation id=%1 does not exist", $pn_representation_id), "ca_objects->removeRepresentation()");
 			return false;
 		} else {
 			$t_rep->setMode(ACCESS_WRITE);
 			$t_rep->delete(true);
 			
 			if ($t_rep->numErrors()) {
 				$this->errors = array_merge($this->errors, $t_rep->errors());
 				return false;
 			}
 			
 			return true;
 		}
 		
 		return false;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Removes all representations from the currently loaded object.
 	 *
 	 * @return bool True if delete succeeded, false if there was an error. You can get the error messages by calling getErrors() on the ca_objects instance.
 	 */
 	public function removeAllRepresentations() {
 		if (is_array($va_reps = $this->getRepresentations())) {
 			foreach($va_reps as $vn_i => $va_rep_info) {
 				if (!$this->removeRepresentation($va_rep_info['representation_id'])) {
 					// Representation remove failed
 					return false;
 				}
 			}
 		}
 		return false;
 	}
 	# ------------------------------------------------------
 	#
 	# ------------------------------------------------------
	/**
	 * Return array containing information about all hierarchies, including their root_id's
	 * For non-adhoc hierarchies such as places, this call returns the contents of the place_hierarchies list
	 * with some extra information such as the # of top-level items in each hierarchy.
	 *
	 * For an ad-hoc hierarchy like that of an object, there is only ever one hierarchy to display - that of the current object.
	 * So for adhoc hierarchies we just return a single entry corresponding to the root of the current object hierarchy
	 */
	 public function getHierarchyList($pb_dummy=false) {
	 	$vn_pk = $this->getPrimaryKey();
	 	if (!$vn_pk) { 
	 		$o_db = new Db();
	 		if (is_array($va_type_ids = caMergeTypeRestrictionLists($this, array())) && sizeof($va_type_ids)) {
				$qr_res = $o_db->query("
					SELECT o.object_id, count(*) c
					FROM ca_objects o
					INNER JOIN ca_objects AS p ON p.parent_id = o.object_id
					WHERE o.parent_id IS NULL AND o.type_id IN (?)
					GROUP BY o.object_id
				", array($va_type_ids));
			} else {
				$qr_res = $o_db->query("
					SELECT o.object_id, count(*) c
					FROM ca_objects o
					INNER JOIN ca_objects AS p ON p.parent_id = o.object_id
					WHERE o.parent_id IS NULL
					GROUP BY o.object_id
				");
			}
	 		$va_hiers = array();
	 		
	 		$va_object_ids = $qr_res->getAllFieldValues('object_id');
	 		$qr_res->seek(0);
	 		$va_labels = $this->getPreferredDisplayLabelsForIDs($va_object_ids);
	 		while($qr_res->nextRow()) {
	 			$va_hiers[$vn_object_id = $qr_res->get('object_id')] = array(
	 				'object_id' => $vn_object_id,
	 				'name' => $va_labels[$vn_object_id],
	 				'hierarchy_id' => $vn_object_id,
	 				'children' => (int)$qr_res->get('c')
	 			);
	 		}
	 		return $va_hiers;
	 	} else {
	 		// return specific object as root of hierarchy
			$vs_label = $this->getLabelForDisplay(false);
			$vs_hier_fld = $this->getProperty('HIERARCHY_ID_FLD');
			$vs_parent_fld = $this->getProperty('PARENT_ID_FLD');
			$vn_hier_id = $this->get($vs_hier_fld);
			
			if ($this->get($vs_parent_fld)) { 
				// currently loaded row is not the root so get the root
				$va_ancestors = $this->getHierarchyAncestors();
				if (!is_array($va_ancestors) || sizeof($va_ancestors) == 0) { return null; }
				$t_object = new ca_objects($va_ancestors[0]);
			} else {
				$t_object =& $this;
			}
			
			$va_children = $t_object->getHierarchyChildren(null, array('idsOnly' => true));
			$va_object_hierarchy_root = array(
				$t_object->get($vs_hier_fld) => array(
					'object_id' => $vn_pk,
					'name' => $vs_label,
					'hierarchy_id' => $vn_hier_id,
					'children' => sizeof($va_children)
				),
				'object_id' => $vn_pk,
				'name' => $vs_label,
				'hierarchy_id' => $vn_hier_id,
				'children' => sizeof($va_children)
			);
				
	 		return $va_object_hierarchy_root;
		}
	}
	# ------------------------------------------------------
	/**
	 * Returns name of hierarchy for currently loaded row or, if specified, row identified by optional $pn_id parameter
	 *
	 * @param int $pn_id Optional object_id to return hierarchy name for. If not specified, the currently loaded row is used.
	 * @return string The name of the hierarchy
	 */
	 public function getHierarchyName($pn_id=null) {
	 	if (!$pn_id) { $pn_id = $this->getPrimaryKey(); }
	 	
		$va_ancestors = $this->getHierarchyAncestors($pn_id, array('idsOnly' => true));
		if (is_array($va_ancestors) && sizeof($va_ancestors)) {
			$vn_parent_id = array_pop($va_ancestors);
			$t_object = new ca_objects($vn_parent_id);
			return $t_object->getLabelForDisplay(false);
		} else {			
			if ($pn_id == $this->getPrimaryKey()) {
				return $this->getLabelForDisplay(false);
			} else {
				$t_object = new ca_objects($pn_id);
				return $t_object->getLabelForDisplay(false);
			}
		}
	 }
	 # ------------------------------------------------------------------
	/**
	 * Returns associative array, keyed by primary key value with values being
	 * the preferred label of the row from a suitable locale, ready for display 
	 * 
	 * @param array $pa_ids indexed array of primary key values to fetch labels for
	 * @param array $pa_versions
	 * @param array $pa_options
	 * @return array List of media
	 */
	public function getPrimaryMediaForIDs($pa_ids, $pa_versions, $pa_options = null) {
		if (!is_array($pa_ids) || !sizeof($pa_ids)) { return array(); }
		if (!is_array($pa_options)) { $pa_options = array(); }
		$va_access_values = $pa_options["checkAccess"];
		if (isset($va_access_values) && is_array($va_access_values) && sizeof($va_access_values)) {
			$vs_access_where = ' AND orep.access IN ('.join(',', $va_access_values).')';
		}
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT oxor.object_id, orep.media
			FROM ca_object_representations orep
			INNER JOIN ca_objects_x_object_representations AS oxor ON oxor.representation_id = orep.representation_id
			WHERE
				(oxor.object_id IN (".join(',', $pa_ids).")) AND oxor.is_primary = 1 AND orep.deleted = 0 {$vs_access_where}
		");
		
		$va_media = array();
		while($qr_res->nextRow()) {
			$va_media_tags = array();
			foreach($pa_versions as $vs_version) {
				$va_media_tags['tags'][$vs_version] = $qr_res->getMediaTag('ca_object_representations.media', $vs_version);
				$va_media_tags['info'][$vs_version] = $qr_res->getMediaInfo('ca_object_representations.media', $vs_version);
				$va_media_tags['urls'][$vs_version] = $qr_res->getMediaUrl('ca_object_representations.media', $vs_version);
			}
			$va_media[$qr_res->get('object_id')] = $va_media_tags;
		}
		return $va_media;
	}
	# ------------------------------------------------------
	/**
	 * Return object_ids for objects with labels exactly matching $ps_name
	 *
	 * @param string $ps_name The label value to search for
	 * @param int $pn_parent_id Optional parent_id. If specified search is restricted to direct children of the specified parent object.
	 * @return array An array of object_ids
	 */
	public function getObjectIDsByName($ps_name, $pn_parent_id=null) {
		$o_db = $this->getDb();
		
		if ($pn_parent_id) {
			$qr_res = $o_db->query("
				SELECT DISTINCT cap.object_id
				FROM ca_objects cap
				INNER JOIN ca_object_labels AS capl ON capl.object_id = cap.object_id
				WHERE
					capl.name = ? AND cap.parent_id = ?
			", (string)$ps_name, (int)$pn_parent_id);
		} else {
			$qr_res = $o_db->query("
				SELECT DISTINCT cap.object_id
				FROM ca_objects cap
				INNER JOIN ca_object_labels AS capl ON capl.object_id = cap.object_id
				WHERE
					capl.name = ?
			", (string)$ps_name);

		}
		$va_object_ids = array();
		while($qr_res->nextRow()) {
			$va_object_ids[] = $qr_res->get('object_id');
		}
		return $va_object_ids;
	}
 	# ------------------------------------------------------
}
?>