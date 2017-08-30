<?php
/** ---------------------------------------------------------------------
 * app/models/ca_site_pages.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2017 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_site_templates.php');
require_once(__CA_MODELS_DIR__.'/ca_site_page_media.php');


BaseModel::$s_ca_models_definitions['ca_site_pages'] = array(
 	'NAME_SINGULAR' 	=> _t('site page'),
 	'NAME_PLURAL' 		=> _t('site pages'),
 	'FIELDS' 			=> array(
 		'page_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess ID'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this item')
		),
		'title' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Page metadata: title'), 'DESCRIPTION' => _t('A short descriptive title for page, used to distinguish the page from others.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'description' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 2,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Page metadata: description'), 'DESCRIPTION' => _t('Text describing the intended content and purpose of the page, to aid in distinguishing it from other pages.')
		),
		'template_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Page metadata: template'), 'DESCRIPTION' => _t('The template selected to format this page for presentation.')
		),
		'path' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Page metadata: URL path'), 'DESCRIPTION' => _t('The unique root-relative URL path used by the public to access this page. For example, if set to <em>/pages/staff</em> this page would be accessible to the public using a URL similiar to this: <em>http://your.domain.com/pages/staff</em>.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Controls whether the page is available publicly. Set to <em>accessible to public</em> to make it available to the public, or <em>not accessible to public</em> to prevent access to all but content editors.')
		),
		'content' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 5,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Page content'), 'DESCRIPTION' => _t('JSON-encoded page content.')
		),
		'keywords' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 5,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Page metdata: keywords'), 'DESCRIPTION' => _t('Optional keywords for this page.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if list item is deleted or not.')
		),
		'view_count' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('View count'), 'DESCRIPTION' => _t('Number of views for this page.')
		)
 	)
);

class ca_site_pages extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_site_pages';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'page_id';

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
	protected $ORDER_BY = array('idno');

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
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'path';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'SitePageSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'SitePageSearchResult';
	
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
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_site_pages_content'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Page content'));
		$this->BUNDLES['ca_site_page_media'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Page media'));
	}
	# ------------------------------------------------------
	/**
	 * Return array with information about all available pages
	 *
	 * @param array $pa_options No options are currently implemented
	 * 
	 * @return array An array of arrays, each of which contains fields values for an available page.
	 */
	public static function getPageList($pa_options=null) {
		$va_pages = ca_site_pages::find('*', ['returnAs' => 'arrays']);
		
		$va_templates_by_id = [];
		foreach(ca_site_templates::find('*', ['returnAs' => 'arrays']) as $va_template) {
			$va_templates_by_id[$va_template['template_id']] = $va_template['title'];
		}
		
		foreach($va_pages as $vn_i => $va_page) {
			$va_pages[$vn_i]['template_title'] = $va_templates_by_id[$va_pages[$vn_i]['template_id']]; 
		}
		
		return $va_pages;
	}
	# ------------------------------------------------------
	/**
	 * Return a list of content tags and HTML form element present in the template for the 
	 * currently loaded page.
	 *
	 * @param array $pa_options No options are currently implemented
	 * 
	 * @return array An array of arrays, each of which contains fields values for a content tag present in the page template.
	 */
	public function getHTMLFormElements($pa_options=null) {
	    if(!is_array($pa_options)) { $pa_options = []; }
		if (!($vn_template_id = $this->get('template_id'))) { return null; }
		
		if(!is_array($va_page_content = $this->get('content'))) { $va_page_content = []; }
		
		$t_template = new ca_site_templates($vn_template_id);
		
		$va_element_defs = $t_template->getHTMLFormElements($va_page_content, array_merge($pa_options, ['addTooltips' => true]));
		
		$va_form_elements = [];
		foreach($va_element_defs as $va_element_def) {
			$va_form_elements[] = [
				'code' => $va_element_def['code'],
				'label' => $va_element_def['label'],
				'element' => $va_element_def['element'],
				'element_with_label' => $va_element_def['element_with_label'],
				'value' => $va_page_content[$va_element_def['name']]
			];
		}
		return $va_form_elements;
	}
	
	# ------------------------------------------------------
	/**
	 * Render content for the currently loaded page
	 *
	 * @param ActionController $po_controller The controller into which to render the content
	 * @param array $pa_options Options include:
	 *		incrementViewCount = increment view count value for page. [Default is false]
	 *		checkAccess = Array of access values for which rendering should occur. If the page to render does not have one of the listed access values rendering will fail. [Default is null]
	 *
	 * @return string Returns null if page could not be rendered
	 */
	public function render($po_controller, $pa_options=null) {
		return ca_site_pages::renderPageForPath($po_controller, $this->get('path'), $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Render page content for a path
	 *
	 * @param ActionController $po_controller The controller into which to render the content
	 * @param string $ps_path The path of the page to render
	 * @param array $pa_options Options include:
	 *		incrementViewCount = increment view count value for page. [Default is false]
	 *		checkAccess = Array of access values for which rendering should occur. If the page to render does not have one of the listed access values rendering will fail. [Default is null]
	 *
	 * @return string Returns null if page cannot be rendered
	 */
	public static function renderPageForPath($po_controller, $ps_path, $pa_options=null) {
		if (($t_page = ca_site_pages::find(['path' => $ps_path], ['returnAs' => 'firstModelInstance', 'checkAccess' => caGetOption('checkAccess', $pa_options, null)])) && ($t_template = ca_site_templates::find(['template_id' => $t_page->get('template_id')], ['returnAs' => 'firstModelInstance']))) {
			$o_content_view = new View($po_controller->request, $po_controller->request->getViewsDirectoryPath());
			
			if (is_array($va_content = caUnserializeForDatabase($t_page->get('content')))) {
				foreach($va_content as $vs_tag => $vs_content) {
					$o_content_view->setVar($vs_tag, $vs_content);
				}
			}
			
			// Set standard page fields for use in template
			foreach(['title', 'description', 'path', 'access', 'keywords', 'view_count'] as $vs_field) {
				$o_content_view->setVar("page_{$vs_field}", $t_page->get($vs_field));
			}
			
			if (caGetOption('incrementViewCount', $pa_options, false)) {
				$t_page->setMode(ACCESS_WRITE);
				$t_page->set('view_count', (int)$t_page->get('view_count') + 1);
				$t_page->update();
			}
			
			return $o_content_view->render($t_template->get('template'), false, ['string' => true]); 
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Return the total number of pages 
	 *
	 * @return int
	 */
	public static function pageCount() {
		return ca_site_pages::find('*', ['returnAs' => 'count']);
	}
	# ------------------------------------------------------
	/**
	 * Return the total number of pages with a given access setting
	 *
	 * @return int
	 */
	public static function pageCountForAccess($pn_access) {
		return ca_site_pages::find(['access' => (int)$pn_access], ['returnAs' => 'count']);
	}
	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle (ca_site_pages_content) for page content 
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
	public function getPageContentHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
 		$o_view->setVar('t_page', $this);
 		$o_view->setVar('t_template', new ca_site_templates($this->get('template_id')));
		
		return $o_view->render('ca_site_pages_content.php');
	}
	# ------------------------------------------------------
	/** 
	 * Returns HTML form bundle (ca_site_page_media) for media on page instance
	 *
	 * @param HTTPRequest $po_request The current request
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options No options are currently supported.
	 *
	 * @return string Rendered HTML bundle
	 */
	public function getPageMediaHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$o_view->setVar('t_subject', $this);
 		$o_view->setVar('t_page', $this);
 		$o_view->setVar('t_item', new ca_site_page_media());
		
		return $o_view->render('ca_site_page_media.php');
	}
	# ------------------------------------------------------
	/**
	 * Return the total number of media for current page. Return null if no page is loaded 
	 *
	 * @return int
	 */
	public function pageMediaCount($pa_options=null) {
	    if (!($vn_page_id = $this->getPrimaryKey())) { return null; }
		return ca_site_page_media::find(['page_id' => $vn_page_id], ['returnAs' => 'count']);
	}
	
	# ------------------------------------------------------
	/**
	 * Return the total number of media for current page. Return null if no page is loaded 
	 *
	 * @return int
	 */
	public function getPageMedia($pa_options=null) {
	    if (!($vn_page_id = $this->getPrimaryKey())) { return null; }
		return ca_site_page_media::find(['page_id' => $vn_page_id], ['returnAs' => 'array']);
	}
	# ------------------------------------------------------
}