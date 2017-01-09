<?php
/** ---------------------------------------------------------------------
 * app/models/ca_site_templates.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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


BaseModel::$s_ca_models_definitions['ca_site_templates'] = array(
 	'NAME_SINGULAR' 	=> _t('site template'),
 	'NAME_PLURAL' 		=> _t('site templates'),
 	'FIELDS' 			=> array(
 		'template_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Template id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this item')
		),
		'template_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Template code'), 'DESCRIPTION' => _t('Unique alphanumeric code for this template.'),
				'BOUNDS_LENGTH' => array(1,100)
		),
		'title' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Short description'), 'DESCRIPTION' => _t('Short descriptive title for this template.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'description' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Full description'), 'DESCRIPTION' => _t('Full usage description for this template.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'template' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Template'), 'DESCRIPTION' => _t('Full template text.')
		),
		'tags' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Extracted template tags'), 'DESCRIPTION' => _t('List of tags extracted from template.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if list item is deleted or not.')
		)
 	)
);

class ca_site_templates extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_site_templates';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'template_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('title');

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
	protected $ORDER_BY = array('title');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = null;
	
	
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
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = null;
	protected $SEARCH_RESULT_CLASSNAME = null;
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = false;
	
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
	 * Generate list of available page templates as an HTML <select> element
	 *
	 * @param array $pa_options Options include:
	 *		name = value used for HTML <select> "name" attribute. [Default is template_id]
	 *		id = value used for HTML <select> "id" attribute. [Default is null]
	 *		
	 * @return string
	 */
	public static function getTemplateListAsHTMLSelect($pa_options=null) {
		$va_rows = self::find(['deleted' => 0], ['returnAs' => 'arrays']);
		if (!is_array($va_rows) || !sizeof($va_rows)) { return null; }
		
		$va_titles = caExtractValuesFromArrayList($va_rows, 'title');
		$va_template_ids = caExtractValuesFromArrayList($va_rows, 'template_id');
		
		$va_options = [];
		foreach($va_titles as $vn_i => $vs_title) {
			$va_options[$vs_title] = $va_template_ids[$vn_i];
		}
		
		return caHTMLSelect(caGetOption('name', $pa_options, 'template_id'), $va_options, ['id' => caGetOption('id', $pa_options, null)]);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getHTMLFormElements($pa_values=null, $pa_options=null) {
		if (!($vn_template_id = $this->get('template_id'))) { return null; }
		
		$o_config = Configuration::load();
		$vs_form_element_format = $o_config->get('form_element_display_format');
		$pb_include_tooltips = caGetOption('addTooltips', $pa_options, false);
		
		$vs_tagname_prefix = caGetOption('tagnamePrefix', $pa_options, 'page_field');
	
		if (!is_array($va_tags = $this->get('tags'))) { return []; }
		$va_form_elements = [];
		foreach($va_tags as $vs_tag => $va_tag_info) {
			if(!trim($vs_tag)) { continue; }
			
			$va_form_elements[] = [
				'code' => $vs_tag,
				'label' => ($vs_label = trim($va_tag_info['label'])) ? $vs_label : $vs_tag,
				'element' => caHTMLTextInput("{$vs_tagname_prefix}_{$vs_tag}", ['id' => "{$vs_tagname_prefix}_{$vs_tag}", 'value' => $pa_values[$vs_tag]], ['width' => caGetOption('width', $va_tag_info, '300px'), 'height' => caGetOption('height', $va_tag_info, '35px'), 'usewysiwygeditor' => caGetOption('usewysiwygeditor', $va_tag_info, false)]),
				'value' => $pa_values[$vs_tag]
			];
			
			if ($vs_form_element_format) {
				$vn_partial_index = sizeof($va_form_elements)-1;
				$va_form_elements[$vn_partial_index]['element_with_label'] = caProcessTemplate($vs_form_element_format, ["LABEL" => "<span id='{$vs_tagname_prefix}_{$vs_tag}_label'>".$va_form_elements[$vn_partial_index]['label']."</span>", "ELEMENT" => $va_form_elements[$vn_partial_index]['element'], "EXTRA" => '' ]);
							
				if($pb_include_tooltips && $va_tag_info['label']) {
					TooltipManager::add("#{$vs_tagname_prefix}_{$vs_tag}_label", "<strong>".$va_tag_info['label']."</strong><br/>".$va_tag_info['description']);
				}
			} elseif($pb_include_tooltips && $va_tag_info['label']) {
				TooltipManager::add("#{$vs_tagname_prefix}_{$vs_tag}", "<strong>".$va_tag_info['label']."</strong><br/>".$va_tag_info['description']);
			}
			
		}
		return $va_form_elements;
	}
	# ------------------------------------------------------
}