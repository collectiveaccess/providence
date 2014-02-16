<?php
/** ---------------------------------------------------------------------
 * app/models/ca_attribute_values.php : table access class for table ca_attribute_values
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
 
require_once(__CA_LIB_DIR__.'/ca/Attributes/Attribute.php');


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
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
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
	# ------------------------------------------------------
	/**
	 * Adds value to specified attribute. Returns value_id if new value on success, false on failure and
	 * null on "silent" failure, in which case no error message is displayed to the user.
	 *
	 * @param string $ps_value The user-input value to parse
	 * @param array $pa_element_info An array of information about the element for which this value will be set
	 * @param int $pn_attribute_id The attribute_id of the attribute to add the value to
	 *
	 * @return int Returns the value_id of the newly created value. If the value cannot be added due to an error, false is returned. "Silent" failures, for which the user should not see an error message, are indicated by a null return value.
	 */
	public function addValue($ps_value, $pa_element_info, $pn_attribute_id, $pa_options=null) {
		$this->clear();
		
		//$t_element = new ca_metadata_elements($pa_element_info['element_id']);
		$t_element = ca_attributes::getElementInstance($pa_element_info['element_id']);
		
		$this->setMode(ACCESS_WRITE);
		$this->set('attribute_id', $pn_attribute_id);
		$this->set('element_id', $pa_element_info['element_id']);
		
		$o_attr_value = Attribute::getValueInstance($pa_element_info['datatype']);
		$pa_element_info['displayLabel'] = $t_element->getLabelForDisplay(false);
		$va_values = $o_attr_value->parseValue($ps_value, $pa_element_info, $pa_options);
		
		if (is_array($va_values)) {
			$this->useBlobAsFileField(false);
			if (!$o_attr_value->numErrors()) {
				foreach($va_values as $vs_key => $vs_val) {
					if (substr($vs_key, 0, 1) === '_') { continue; }
					if (($vs_key === 'value_blob') && (isset($va_values['_file']) && $va_values['_file'])) {
						$this->useBlobAsFileField(true);		// force value_blob field to be treated as FT_FILE by BaseModel
						$this->set($vs_key, $vs_val, array('original_filename' => $va_values['value_longtext2']));
					} else {
						if (($vs_key === 'value_blob') && (isset($va_values['_media']) && $va_values['_media'])) {
							$this->useBlobAsMediaField(true);		// force value_blob field to be treated as FT_MEDIA by BaseModel
							$this->set($vs_key, $vs_val, array('original_filename' => $va_values['value_longtext2']));
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
				return $this->insert();
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
		
		//$t_element = new ca_metadata_elements($this->get('element_id'));
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
					} else {
						if (($vs_key === 'value_blob') && (isset($va_values['_media']) && $va_values['_media'])) {
							$this->useBlobAsMediaField(true);	// force value_blob field to be treated as FT_MEDIA by BaseModel
							$this->set($vs_key, $vs_val, array('original_filename' => $va_values['value_longtext2']));
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
		return parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
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
}
?>