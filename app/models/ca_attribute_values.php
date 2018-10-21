<?php
/** ---------------------------------------------------------------------
 * app/models/ca_attribute_values.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2018 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/Attributes/Attribute.php');
require_once(__CA_MODELS_DIR__.'/ca_attribute_value_multifiles.php');
require_once(__CA_LIB_DIR__."/SyncableBaseModel.php");


BaseModel::$s_ca_models_definitions['ca_attribute_values'] = array(
 	'NAME_SINGULAR' 	=> _t('attribute value'),
 	'NAME_PLURAL' 		=> _t('attribute values'),
 	'FIELDS' 			=> array(
		'value_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Attribute value id', 'DESCRIPTION' => 'Unique identifier for this attribute value'
		),
		'element_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Element id', 'DESCRIPTION' => 'Identifier for Element'
		),
		'attribute_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Attribute', 'DESCRIPTION' => 'Attribute value is part of'
		),
		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'List', 'DESCRIPTION' => 'List item this value uses (only set for list attributes)'
		),
		'value_longtext1' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Longtext value container 1', 'DESCRIPTION' => 'First longtext attribute value container'
		),
		'value_longtext2' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Longtext value container 2', 'DESCRIPTION' => 'Second longtext attribute value container'
		),
		'value_blob' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_object_representations',
				"FILE_VOLUME" => 'workspace',
				'LABEL' => 'BLOB value container', 'DESCRIPTION' => 'BLOB attribute value container'
		),
		'value_decimal1' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Decimal value container 1', 'DESCRIPTION' => 'First decimal attribute value container'
		),
		'value_decimal2' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Decimal value container 2', 'DESCRIPTION' => 'Second decimal attribute value container'
		),
		'value_integer1' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Integer value container', 'DESCRIPTION' => 'Integer attribute value container'
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Source information'
		)
	)
);

class ca_attribute_values extends BaseModel {
	# ---------------------------------
	use SyncableBaseModel;
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
	protected $TABLE = 'ca_attribute_values';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'value_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('value_longtext1');

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
	protected $ORDER_BY = array('value_longtext1');

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
	 * Stub out indexing for this table - it is never indexed
	 */
	public function doSearchIndexing($pa_changed_field_values_array=null, $pb_reindex_mode=false, $ps_engine=null) {
		return;
	}
	# -------------------------------------------------------
	public function insert($pa_options=null) {
		if($vm_ret = parent::insert($pa_options)) {
			$this->setGUID($pa_options); // generate and set GUID
		}

		return $vm_ret;
	}
	# -------------------------------------------------------
	/**
	 * Adds value to specified attribute. Returns value_id if new value on success, false on failure and
	 * null on "silent" failure, in which case no error message is displayed to the user.
	 *
	 * @param string $ps_value The user-input value to parse
	 * @param array $pa_element_info An array of information about the element for which this value will be set
	 * @param int $pn_attribute_id The attribute_id of the attribute to add the value to
	 * @param array $pa_options Options include:
	 *      skipExistingValues = attempt to detect and skip values already attached to the specified row to which the attribute is bound. [Default is false]
	 *
	 * @return int Returns the value_id of the newly created value. If the value cannot be added due to an error, false is returned. "Silent" failures, for which the user should not see an error message, are indicated by a null return value.
	 */
	public function addValue($ps_value, $pa_element_info, $pn_attribute_id, $pa_options=null) {
		$this->clear();
		
		$t_element = ca_attributes::getElementInstance($pa_element_info['element_id']);
		
		if ($this->inTransaction()) { $pa_options['transaction'] = $this->getTransaction(); }
		
		$this->setMode(ACCESS_WRITE);
		$this->set('attribute_id', $pn_attribute_id);
		$this->set('element_id', $pa_element_info['element_id']);
		
		$o_attr_value = Attribute::getValueInstance($pa_element_info['datatype']);
		$pa_element_info['displayLabel'] = $t_element->getLabelForDisplay(false);
		$va_values = $o_attr_value->parseValue($ps_value, $pa_element_info, $pa_options);
		if (isset($va_values['_dont_save']) && $va_values['_dont_save']) { return true; }
		
		if (is_array($va_values)) {
		    if ((caGetOption('skipExistingValues', $pa_options, false)) && ($t_attr = caGetOption('t_attribute', $pa_options, null)) && ($t_instance = $t_attr->getRowInstance())) {
                if(is_array($va_attrs = $t_instance->getAttributesByElement($vn_attr_element_id = $t_attr->get('element_id')))){
                    $o_attr_value->loadTypeSpecificValueFromRow($va_values);
                    $vs_new_value = (string)$o_attr_value->getDisplayValue($pa_options);
                    
                    $vb_already_exists = false;
                    foreach($va_attrs as $o_attr) {
                        foreach($o_attr->getValues() as $o_val) {
                            if ((int)$o_val->getElementID() !== (int)$pa_element_info['element_id']) { continue; }
                            $vs_old_value = (string)$o_val->getDisplayValue($pa_options);
                            if (strlen($vs_old_value) && strlen($vs_new_value) && ($vs_old_value === $vs_new_value)) {
                                return null;
                                break;
                            }
                        }
                    }
                }
            }
		
		
			$this->useBlobAsFileField(false);
			if (!$o_attr_value->numErrors()) {
				foreach($va_values as $vs_key => $vs_val) {
					if (substr($vs_key, 0, 1) === '_') { continue; }
					if (($vs_key === 'value_blob') && (isset($va_values['_file']) && $va_values['_file'])) {
						$this->useBlobAsFileField(true);		// force value_blob field to be treated as FT_FILE by BaseModel
						$this->set($vs_key, $vs_val, array('original_filename' => $va_values['value_longtext2']));
						$this->set('source_info', md5_file($vs_val));
					} else {
						if (($vs_key === 'value_blob') && (isset($va_values['_media']) && $va_values['_media'])) {
							$this->useBlobAsMediaField(true);		// force value_blob field to be treated as FT_MEDIA by BaseModel
							$this->set($vs_key, $vs_val, array('original_filename' => $va_values['value_longtext2']));
							$this->set('source_info', md5_file($vs_val));
						} else {
							$this->set($vs_key, $vs_val);
						}
					}
				}
			} else {
				// error
				$this->errors = $o_attr_value->errors;
				return false;
			}
		
	
			if (!$this->numErrors()) {
				return $this->insert($pa_options);
			} else {
				return false;
			}
		} else {
			if ($va_values === false) { $this->errors = $o_attr_value->errors; }
			return $va_values;
		}
	}
	# ------------------------------------------------------
	/**
	 * Edits the value of the currently loaded ca_attribute_values record. 
	 * Returns the value_id of the edited value on success, false on failure and
	 * null on "silent" failure, in which case no error message is displayed to the user.
	 *
	 * @param string $ps_value The user-input value to parse
	 *
	 * @return int Returns the value_id of the value on success. If the value cannot be edited due to an error, false is returned. "Silent" failures, for which the user should not see an error message, are indicated by a null return value.
	 */
	public function editValue($ps_value, $pa_options=null) {
		if (!$this->getPrimaryKey()) { return null; }
		
		if ($this->inTransaction()) { $pa_options['transaction'] = $this->getTransaction(); }
		
		$t_element = ca_attributes::getElementInstance($this->get('element_id'));
		$pa_element_info = $t_element->getFieldValuesArray();
		
		$this->setMode(ACCESS_WRITE);
		
		$o_attr_value = Attribute::getValueInstance($t_element->get('datatype'));
		$pa_element_info['displayLabel'] = $t_element->getLabelForDisplay(false);
		$va_values = $o_attr_value->parseValue($ps_value, $pa_element_info, $pa_options);
		if (isset($va_values['_dont_save']) && $va_values['_dont_save']) { return true; }
		
		if (is_array($va_values)) {
			$this->useBlobAsFileField(false);
			if (!$o_attr_value->numErrors()) {
				foreach($va_values as $vs_key => $vs_val) {
					if (substr($vs_key, 0, 1) === '_') { continue; }
					if (($vs_key === 'value_blob') && (isset($va_values['_file']) && $va_values['_file'])) {
						$this->useBlobAsFileField(true);	// force value_blob field to be treated as FT_FILE by BaseModel
						$this->set($vs_key, $vs_val, array('original_filename' => $va_values['value_longtext2']));
						$this->set('source_info', md5_file($vs_val));
					} else {
						if (($vs_key === 'value_blob') && (isset($va_values['_media']) && $va_values['_media'])) {
							$this->useBlobAsMediaField(true);	// force value_blob field to be treated as FT_MEDIA by BaseModel
							$this->set($vs_key, $vs_val, array('original_filename' => $va_values['value_longtext2']));
							$this->set('source_info', md5_file($vs_val));
						} else {
							$this->set($vs_key, $vs_val);
						}
					}
				}
			} else {
				// error
				$this->errors = $o_attr_value->errors;
				return false;
			}
		} else {
			if ($va_values === false) { $this->errors = $o_attr_value->errors; }
			return $va_values;
		}
		
		// Clear cache against attribute and any pages
		$vn_id = $this->getPrimaryKey();
		CompositeCache::delete("attribute:{$vn_id}", 'IIIFMediaInfo');
		CompositeCache::delete("attribute:{$vn_id}", 'IIIFTileCounts');
		
		$vn_p = 1;
		while(CompositeCache::contains($vs_key = "attribute:{$vn_id}:{$vn_p}", "IIIFMediaInfo")) {
			CompositeCache::delete($vs_key, 'IIIFMediaInfo');
			CompositeCache::delete($vs_key, 'IIIFTileCounts');
			$vn_p++;
		}
		
		$this->update();
		
		if ($this->numErrors()) {
			return false;
		}
		
		return $this->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		//$t_element = new ca_metadata_elements($this->get('element_id'));
		$t_element = ca_attributes::getElementInstance($this->get('element_id'));
		switch($vn_data_type = $t_element->get('datatype')) {
			case 15:		// FT_FILE
				$this->useBlobAsFileField(true);			// force value_blob field to be treated as FT_FILE field by BaseModel
				break;
			case 16:		// FT_MEDIA
				$this->useBlobAsMediaField(true);			// force value_blob field to be treated as FT_MEDIA field by BaseModel
				break;
			default:
				// Reset value_blob field to default (FT_TEXT) – should already be that but we reset it just in case
				$this->useBlobAsMediaField(false);
				break;
		}

		$vn_primary_key = $this->getPrimaryKey();
		$vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
		if($vn_primary_key && $vn_rc) {
			//$this->removeGUID($vn_primary_key);
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function useBlobAsFileField($pb_setting) {
		$this->FIELDS['value_blob']['FIELD_TYPE'] = ($pb_setting) ? FT_FILE : FT_TEXT;
		// We have to deserialize the FT_FILE info array ourselves since when we loaded the attribute value model
		// BaseModel didn't know it was an FT_FILE field
		$this->_FIELD_VALUES['value_blob'] = caUnserializeForDatabase($this->_FIELD_VALUES['value_blob']);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function useBlobAsMediaField($pb_setting) {
		$this->FIELDS['value_blob']['FIELD_TYPE'] = ($pb_setting) ? FT_MEDIA : FT_TEXT;

		// We have to deserialize the FT_MEDIA info array ourselves since when we loaded the attribute value model
		// BaseModel didn't know it was an FT_MEDIA field
		$this->_FIELD_VALUES['value_blob'] = caUnserializeForDatabase($this->_FIELD_VALUES['value_blob']);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getAttributeValueArray($pn_element_id, $pa_options=null) {
		if (!$pn_element_id) { return null; }
		$o_db = new Db();
		$qr_attr = $o_db->query("
			SELECT *
			FROM ca_attribute_values cav
			INNER JOIN ca_metadata_elements AS cme ON cme.element_id = cav.element_id
			WHERE
				cav.attribute_id = ?
		", array((int)$pn_element_id));
		
		if($qr_attr->nextRow()) {
			return $qr_attr->getRow();
		}
		
		return null;
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
 		
 		$t_multifile = new ca_attribute_value_multifiles();
 		if (!$pb_allow_duplicates) {
 			if ($t_multifile->load(array('resource_path' => $ps_resource_path, 'value_id' => $this->getPrimaryKey()))) {
 				return null;
 			}
 		}
 		$t_multifile->setMode(ACCESS_WRITE);
 		$t_multifile->set('value_id', $this->getPrimaryKey());
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
 		
 		$t_multifile = new ca_attribute_value_multifiles($pn_multifile_id);
 		
 		if ($t_multifile->get('value_id') == $this->getPrimaryKey()) {
 			$t_multifile->setMode(ACCESS_WRITE);
 			$t_multifile->delete();
 			
			if ($t_multifile->numErrors()) {
				$this->errors = array_merge($this->errors, $t_multifile->errors);
				return false;
			}
		} else {
			$this->postError(2720, _t('File is not part of this value'), 'ca_attribute_values->removeFile()');
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
 	 * Returns list of additional files (page or frame previews for documents or videos, typically) attached to a value
 	 * The return value is an array key'ed on the multifile_id (a unique identifier for each attached file); array values are arrays
 	 * with keys set to values for each file version returned. They keys are:
 	 *		<version name>_path = The absolute file path to the file
 	 *		<version name>_tag = An HTML tag that will display the file
 	 *		<version name>_url = The URL for the file
 	 *		<version name>_width = The pixel width of the file when displayed
 	 *		<version name>_height = The pixel height of the file when displayed
 	 * The available versions are set in media_processing.conf
 	 *
 	 * @param int $pn_value_id The value_id of the attribute value to return files for. If omitted the currently loaded attribute value is used. If no value_id is specified and no row is loaded null will be returned.
 	 * @param int $pn_start The index of the first file to return. Files are numbered from zero. If omitted the first file found is returned.
 	 * @param int $pn_num_files The maximum number of files to return. If omitted all files are returned.
 	 * @param array $pa_versions A list of file versions to return. If omitted only the "preview" version is returned.
 	 * @return array A list of files attached to the attribute value. If no files are associated an empty array is returned.
 	 */
 	public function getFileList($pn_value_id=null, $pn_start=null, $pn_num_files=null, $pa_versions=null) {
 		if(!($vn_value_id = $pn_value_id)) { 
 			if (!($vn_value_id = $this->getPrimaryKey())) {
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
 			FROM ca_attribute_value_multifiles
 			WHERE
 				value_id = ?
 			{$vs_limit_sql}
 		", (int)$vn_value_id);
 		
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
 	
 		$t_multifile = new ca_attribute_value_multifiles($pn_multifile_id);
 		
 		if ($t_multifile->get('value_id') == $this->getPrimaryKey()) {
 			return $t_multifile;
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function numFiles($pn_value_id=null) { 		
 		if(!($vn_value_id = $pn_value_id)) { 
 			if (!($vn_value_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT count(*) c
 			FROM ca_attribute_value_multifiles
 			WHERE
 				value_id = ?
 		", (int)$vn_value_id);
 		
 		if($qr_res->nextRow()) {
 			return intval($qr_res->get('c'));
 		}
 		return 0;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return first attribute value id found for the specified element and value.
 	 *
 	 * @param mixed $element_id An element code or numeric element_id
 	 * @param mixed $value An attribute value (numeric or text)
 	 * @param array $options Options include:
 	 *      transaction = A transaction within which to perform the value search. [Default is null]
 	 *
 	 * @return int A value_id or null if no value is found.
 	 */
 	static public function getValueIDFor($element_id, $value, $options=null) {
 	    if (!is_numeric($element_id)) { 
            require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
 	        $element_id = ca_metadata_elements::getElementID($element_id); 
 	   }
 	
        if ($element_id > 0) {
 	        $db = ($trans = caGetOption('transaction', $options, null)) ? $trans->getDb() : new Db();
 	        
 	        $qr = $db->query("
 	            SELECT value_id FROM ca_attribute_values WHERE element_id = ? AND value_longtext1 = ? LIMIT 1
 	        ", [$element_id, $value]);
 	        
 	        if ($qr->nextRow()) {
 	            return $qr->get('value_id');
 	        }
 	    }
 	    return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return raw attribute value data for a value_id.
 	 *
 	 * @param int $value_id 
 	 * @param array $options Options include:
 	 *      transaction = A transaction within which to perform the value search. [Default is null]
 	 *
 	 * @return array Dictionary with raw attribute value fields (value_longtext1, value_longtext2, value_blob, value_decimal1, value_decimal2, value_integer1, item_id) or null if value_id is invalid.
 	 */
 	static public function getValuesFor($value_id, $options=null) {
        if ($value_id > 0) {
 	        $db = ($trans = caGetOption('transaction', $options, null)) ? $trans->getDb() : new Db();
 	        
 	        $qr = $db->query("
 	            SELECT value_longtext1, value_longtext2, value_blob, value_decimal1, value_decimal2, value_integer1, item_id FROM ca_attribute_values WHERE value_id = ?
 	        ", [(int)$value_id]);

 	        if ($qr->nextRow()) {
 	            return $qr->getRow();
 	        }
 	    }
 	    return null;
 	}
	# ------------------------------------------------------
}
