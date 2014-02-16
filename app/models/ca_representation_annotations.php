<?php
/** ---------------------------------------------------------------------
 * app/models/ca_representation_annotations.php : table access class for table ca_representation_annotations
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

require_once(__CA_LIB_DIR__."/ca/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/ca/BundlableLabelableBaseModelWithAttributes.php");
require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');


BaseModel::$s_ca_models_definitions['ca_representation_annotations'] = array(
 	'NAME_SINGULAR' 	=> _t('representation annotation'),
 	'NAME_PLURAL' 		=> _t('representation annotations'),
 	'FIELDS' 			=> array(
 		'annotation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this item')
		),
		'representation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Representation id', 'DESCRIPTION' => 'Identifier for Representation'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the annotation originates.')
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
				'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who created the annotation.')
		),
		'type_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Type code'), 'DESCRIPTION' => _t('Code indicating type of annotation.'),
				'BOUNDS_LENGTH' => array(1,30)
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Source information'
		),
		'props' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Properties', 'DESCRIPTION' => 'Container for annotation properties.'
		),
		'preview' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_representation_annotation_previews',
				
				'ALLOW_BUNDLE_ACCESS_CHECK' => true,
				
				'LABEL' => _t('Preview media'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload for use as a preview.')
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
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if annotation is accessible to the public or not. ')
		),
		'status' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Newly created') => 0,
					_t('Editing in progress') => 1,
					_t('Editing complete - pending review') => 2,
					_t('Review in progress') => 3,
					_t('Completed') => 4
				),
				'LIST' => 'workflow_statuses',
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the annotation .')
		)
 	)
);

class ca_representation_annotations extends BundlableLabelableBaseModelWithAttributes implements IBundleProvider {
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
	protected $TABLE = 'ca_representation_annotations';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'annotation_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('source_info');

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
	protected $ORDER_BY = array('source_info');

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
	protected $LABEL_TABLE_NAME = 'ca_representation_annotation_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = null;			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = null;			// list code (ca_lists.list_code) of list defining types for this table

	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# Annotation properties instance
	# ------------------------------------------------------
	protected $opo_annotations_properties = null;
	protected $opo_type_config = null;
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'RepresentationAnnotationSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'RepresentationAnnotationSearchResult';
	
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
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
		
 		$o_config = $this->getAppConfig();
 		$this->opo_type_config = Configuration::load($o_config->get('annotation_type_config'));
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_representation_annotation_properties'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Annotation properties'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
	}
 	# ------------------------------------------------------
	public function load($pm_id=null, $pb_use_cache=true) {
		$vn_rc = parent::load($pm_id, $pb_use_cache);
		
		$vs_type = $this->getAnnotationType();
		$this->opo_annotations_properties = $this->loadProperties($vs_type);
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		$this->set('type_code', $vs_type = $this->getAnnotationType());
		$this->opo_annotations_properties = $this->loadProperties($vs_type);
		if (!$this->opo_annotations_properties) {
			$this->postError(1101, _t('No type code set'), 'ca_representation_annotations->insert');
			return false;
		}
		if (!$this->opo_annotations_properties->validate()) {
			$this->errors = $this->opo_annotations_properties->errors;
			return false;
		}
		$this->set('props', $this->opo_annotations_properties->getPropertyValues());
		$vn_rc = parent::insert($pa_options);
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Update annotation. If time code of annotation has changed media preview will be regenerated. 
	 * You can force the media preview to be regenerated whether the time code has changed or not
	 * by passing the 'forcePreviewGeneration' option.
	 *
	 * @param array $pa_options An array of options:
	 *		forcePreviewGeneration = if set preview media will be regenerated whether time code has changed or not. Default is false.
	 * @return bool True on success, false on failure
	 */
	public function update($pa_options=null) {
		$this->set('type_code', $vs_type = $this->getAnnotationType());
		if (!$this->opo_annotations_properties->validate()) {
			$this->errors = $this->opo_annotations_properties->errors;
			return false;
		}
		$this->set('props', $this->opo_annotations_properties->getPropertyValues());
		
		if (!$this->getAppConfig()->get('dont_generate_annotation_previews') && $this->getPrimaryKey() && ($this->changed('props') || (isset($pa_options['forcePreviewGeneration']) && $pa_options['forcePreviewGeneration']))) {
			$vs_start = $this->getPropertyValue('startTimecode');
			$vs_end = $this->getPropertyValue('endTimecode');
			
			$va_data['start'] = $vs_start;
			$va_data['end'] = $vs_end;
			
			$t_rep = new ca_object_representations($this->get('representation_id'));
			if (($vs_file = $t_rep->getMediaPath('media', 'original')) && file_exists($vs_file)) {
				$o_media = new Media();
				if ($o_media->read($vs_file)) {
					if ($o_media->writeClip($vs_file = tempnam(caGetTempDirPath(), 'annotationPreview'), $vs_start, $vs_end)) {
						$this->set('preview', $vs_file);
					}
				}
			}
		}	
		
		$vn_rc = parent::update($pa_options);
		if (!$this->numErrors()) {
			$this->opo_annotations_properties = $this->loadProperties($vs_type);
		}
		if ($vs_file) { @unlink($vs_file); }
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		$vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
		
		if (!$this->numErrors()) {
			$this->opo_annotations_properties = null;
		}
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Returns true if currently set property values are valid and can be inserted into database, 
	 * false if they violate some annotation-type-specific constraint
	 */
	public function validatePropertyValues() {
		if (!($vn_rc = $this->opo_annotations_properties->validate())) {
			$this->errors = $this->opo_annotations_properties->errors();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
 	public function getPropertyList() {
 		return is_object($this->opo_annotations_properties) ? $this->opo_annotations_properties->getPropertyList() : array();
 	}
 	# ------------------------------------------------------
 	public function getPropertyHTMLFormElement($ps_property, $pa_attributes=null) {
 		return $this->opo_annotations_properties->htmlFormElement($ps_property, $pa_attributes);
 	}
 	# ------------------------------------------------------
 	public function getPropertyHTMLFormBundle($po_request, $ps_property, $pa_attributes=null) {
 		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		$o_view->setVar('property', $ps_property);
 		$o_view->setVar('form_element', $this->getPropertyHTMLFormElement($ps_property, $pa_attributes));
 		
		return $o_view->render('ca_representation_annotation_properties.php');
 	}
 	# ------------------------------------------------------
 	public function getPropertyValue($ps_property) {
 		return $this->opo_annotations_properties->getProperty($ps_property);
 	}
 	# ------------------------------------------------------
 	public function getPropertyValues() {
 		return $this->opo_annotations_properties->getPropertyValues();
 	}
 	# ------------------------------------------------------
 	public function setPropertyValue($ps_property, $pm_value) {
 		return $this->opo_annotations_properties->setProperty($ps_property, $pm_value);
 	}
 	# ------------------------------------------------------
 	public function getAnnotationType($pn_representation_id=null) {
 		if (!$pn_representation_id) {
			if (!$vn_representation_id = $this->get('representation_id')) {
				return false;
			}
		} else {
			$vn_representation_id = $pn_representation_id;
		}
 		$t_rep = new ca_object_representations();
 		
 		return $t_rep->getAnnotationType($vn_representation_id);
 	}
 	# ------------------------------------------------------
 	public function getPropertiesForType($ps_type) {
 		$va_types = $this->opo_type_config->getAssoc('types');
 		return array_keys($va_types[$ps_type]['properties']);
 	}
 	# ------------------------------------------------------
 	public function loadProperties($ps_type, $pa_parameters=null) {
 		$vs_classname = $ps_type.'RepresentationAnnotationCoder';
 		if (!file_exists(__CA_LIB_DIR__.'/ca/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php')) {
 			return false;
 		}
 		include_once(__CA_LIB_DIR__.'/ca/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php');
 		
 		$this->opo_annotations_properties = new $vs_classname;
 		$this->opo_annotations_properties->setPropertyValues(is_array($pa_parameters) ? $pa_parameters : $this->get('props'));
 		return $this->opo_annotations_properties;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Matching method to ca_objects::getRepresentations(), except this one only returns a single representation - the currently loaded one
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
 				caor.representation_id = ? 
 				{$vs_access_sql}
 			ORDER BY
 				l.name ASC 
 		", (int)$this->get('representation_id'));
 		
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
 			
 			//$va_tmp['num_multifiles'] = $t_rep->numFiles($this->get('representation_id'));
 			$va_reps[] = $va_tmp;
 		}
 		return $va_reps;
 	}
 	# ------------------------------------------------------
 	# STATIC
 	# ------------------------------------------------------
 	static public function getPropertiesCoderInstance($ps_type) {
 		$vs_classname = $ps_type.'RepresentationAnnotationCoder';
 		if (!file_exists(__CA_LIB_DIR__.'/ca/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php')) {
 			return false;
 		}
 		include_once(__CA_LIB_DIR__.'/ca/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php');
 		
 		return new $vs_classname;
 	}
 	# ------------------------------------------------------
}
?>