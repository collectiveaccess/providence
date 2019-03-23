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
 
require_once(__CA_LIB_DIR__.'/BaseModel.php');
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
				'BOUNDS_LENGTH' => array(2,255)
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
		
		$va_element_defs = $t_template->getHTMLFormElements($va_page_content, array_merge($pa_options, ['addTooltips' => true, 'contentUrl' => caGetOption('contentUrl', $pa_options, null)]));
		
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

		if (
			($t_page = ca_site_pages::find(['path' => $ps_path], ['returnAs' => 'firstModelInstance', 'checkAccess' => caGetOption('checkAccess', $pa_options, null)])) && ($t_template = ca_site_templates::find(['template_id' => $t_page->get('template_id')], ['returnAs' => 'firstModelInstance']))
			||
			($t_page = ca_site_pages::find(['path' => $ps_path."/"], ['returnAs' => 'firstModelInstance', 'checkAccess' => caGetOption('checkAccess', $pa_options, null)])) && ($t_template = ca_site_templates::find(['template_id' => $t_page->get('template_id')], ['returnAs' => 'firstModelInstance']))
		) {
			$o_content_view = new View($po_controller->request, $po_controller->request->getViewsDirectoryPath());
	
			if (is_array($va_content = caUnserializeForDatabase($t_page->get('content')))) {
				foreach($va_content as $vs_tag => $vs_content) {
					$o_content_view->setVar($vs_tag, caProcessReferenceTags($po_controller->request, $vs_content, ['page' => $t_page->getPrimaryKey()]));
				}
			}
			
			$va_tags = $o_content_view->getTagList($t_template->get('template'), ['string' => true]);
		    $va_media_to_render = [];
			foreach($va_tags as $vs_tag) {
			    if (substr($vs_tag, 0, 5) === 'media') {
			        $va_tmp = explode(':', $vs_tag);
			        $va_media_to_render[] = [
			            'tag' => $vs_tag,
			            'index' => (int)$va_tmp[1],
			            'version' => (string)$va_tmp[2],
			            'mode' => (string)$va_tmp[3]
			        ]; 
			    }
			}
			if (sizeof($va_media_to_render) > 0) {
			    $va_media_list = array_values($t_page->getPageMedia(array_unique(array_map(function($v) { return $v['version']; }, $va_media_to_render))));

                if (!is_array($va_access_values = caGetUserAccessValues($po_controller->request)) || !sizeof($va_access_values)) { $va_access_values = null; }
			    foreach($va_media_to_render as $va_media) {
			        $vn_index = (int)caGetOption('index', $va_media, 0) - 1;
			        if ($vn_index < 0) { $vn_index = 0; }
			        if ($vn_index > sizeof($va_media_list) - 1) { $vn_index = sizeof($va_media_list) - 1; }
			        
			        if (!isset($va_media_list[$vn_index])) { continue; }
			        if (is_array($va_access_values) && !in_array($va_media_list[$vn_index]['access'], $va_access_values)) { continue; }
			        
			        $vs_media_tag = null;
			        switch($vs_version = caGetOption('version', $va_media, 'small')) {
			            case 'caption':
			                $vs_media_tag = $va_media_list[$vn_index]['caption'];
			                break;
			            case 'title':
			                $vs_media_tag = $va_media_list[$vn_index]['title'];
			                break;
			            case 'idno':
			                $vs_media_tag = $va_media_list[$vn_index]['idno'];
			                break;
			        }
			        
			        if (is_null($vs_media_tag)) {
                        switch($va_media['mode']) {
                            case 'url':
                                $vs_media_tag = $va_media_list[$vn_index]['urls'][$vs_version];
                                break;
                            case 'path':
                                $vs_media_tag = $va_media_list[$vn_index]['paths'][$vs_version];
                                break;
                            case 'tag':
                            default:
                                $vs_media_tag = $va_media_list[$vn_index]['tags'][$vs_version];
                                break;
                        }
                    }
			        $o_content_view->setVar($va_media['tag'], $vs_media_tag);
			    }
			}
			
		    $o_content_view->setVar("page", $t_page);
		     
			// Set standard page fields for use in template
			foreach(['title', 'description', 'path', 'access', 'keywords', 'view_count'] as $vs_field) {
				$o_content_view->setVar("page_{$vs_field}", caProcessReferenceTags($po_controller->request, $t_page->get($vs_field), ['page' => $t_page->getPrimaryKey()]));
			}
			
			if (caGetOption('incrementViewCount', $pa_options, false)) {
				$t_page->setMode(ACCESS_WRITE);
				$t_page->set('view_count', (int)$t_page->get('view_count') + 1);
				$t_page->update();
			}
	
			if ((bool)$t_page->getAppConfig()->get('allow_php_in_site_page_templates')) {
			    ob_start();
			    $that = $o_content_view;    // Simulate "$this" in a view while eval'ing the raw template code by copying view into "$that"...
			    $vs_template = preg_replace('!\$this([^A-Za-z0-9]+)!', '$that\1', $t_template->get('template'));    // ... and then rewrite all instances of "$this" to "$that"
			    eval("?>{$vs_template}");
			    $vs_template_content = ob_get_clean();
			} else {
			    $vs_template_content = $t_template->get('template');
			}
			
			return $o_content_view->render($vs_template_content, false, ['string' => true]); 
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
		
        $o_view->setVar('lookup_urls', caGetLookupUrlsForTables($po_request));
		
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
 		
		$o_view->setVar('defaultRepresentationUploadType', $po_request->user->getVar('defaultRepresentationUploadType'));
		
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
	 * 
	 *
	 * @return array
	 */
	public function getPageMedia($pa_versions=null, $pa_options=null) {
	    if (!($vn_page_id = $this->getPrimaryKey())) { return null; }
	    if (!is_array($pa_versions) || !sizeof($pa_versions)) { $pa_versions = ['original']; }
		$va_media =  ca_site_page_media::find(['page_id' => $vn_page_id], ['returnAs' => 'arrays', 'sort' => 'rank']);
	
	    $va_media_list = []; 
        foreach($va_media as $i => $va_media_info) {
            $o_coder = new MediaInfoCoder($va_media_info['media']);
            unset($va_media_info['media']);
            unset($va_media_info['media_metadata']);
            unset($va_media_info['media_content']);
            $va_media_list[$va_media_info['media_id']] = array_merge($va_media_info, [
                'info' => $o_coder->getMedia(),
                'tags' => [],
                'urls' => [],
                'paths' => [],
                'versions' => $o_coder->getMediaVersions(),
                'fetched_on' => null,
                'fetched_from' => null,
                'dimensions' => null
            ]);
            $va_media_list[$va_media_info['media_id']]['info']['original_filename'] = $va_media_list[$va_media_info['media_id']]['info']['ORIGINAL_FILENAME'];
            
	        foreach($pa_versions as $vs_version) {
                $va_disp = caGetMediaInfoForDisplay($o_coder, $vs_version);
                
                $va_media_list[$va_media_info['media_id']]['tags'][$vs_version] = $o_coder->getMediaTag($vs_version);
                $va_media_list[$va_media_info['media_id']]['urls'][$vs_version] = $o_coder->getMediaUrl($vs_version);
                $va_media_list[$va_media_info['media_id']]['paths'][$vs_version] = $o_coder->getMediaPath($vs_version);
                $va_media_list[$va_media_info['media_id']]['dimensions'][$vs_version] = $va_disp['dimensions'];
            }
        }
		return $va_media_list;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return array
	 */
	public function addMedia($ps_path, $ps_title, $ps_caption, $ps_idno, $pn_access, $pa_options=null) {
	    if (!$this->getPrimaryKey()) { return false; }
	    $t_media = new ca_site_page_media();
	    if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_media->setTransaction($o_trans); }
	    $t_media->setMode(ACCESS_WRITE);
	    
	    if (!$ps_idno) { $ps_idno = uniqid("Media-".$this->getPrimaryKey()); }
	    if (!$ps_title) { $ps_title = $ps_idno; }
	    
	    $va_fld_data = [
	        'page_id' => $this->getPrimaryKey(),
	        'media' => $ps_path,
	        'title' => $ps_title,
	        'caption' => $ps_caption,
	        'idno' => $ps_idno,
	        'access' => $pn_access
	    ];
	    
	    $vb_errored = false;
	    foreach($va_fld_data as $vs_f => $vs_v) {
	        if (!($t_media->set($vs_f, $vs_v, $pa_options))) {
	            $this->errors += $t_media->errors;
	            $vb_errored = true;
	        }
	    }
	    if ($vb_errored) return false;
	    if (!($vn_rc = $t_media->insert($pa_options))) {
	        $this->errors = $t_media->errors;
	        return $vn_rc;
	    }
	    return $t_media;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return array
	 */
	public function editMedia($pn_media_id, $ps_path, $ps_title, $ps_caption, $ps_idno, $pn_access, $pa_options=null) {
	    if (!$this->getPrimaryKey()) { return false; }
	    $t_media = new ca_site_page_media($pn_media_id);
	    if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_media->setTransaction($o_trans); }
	    if (!$t_media->isLoaded()) { return null; }
	    $t_media->setMode(ACCESS_WRITE);
	    
	    $va_fld_data = [
	        'page_id' => $this->getPrimaryKey(),
	        'media' => $ps_path,
	        'title' => $ps_title,
	        'caption' => $ps_caption,
	        'idno' => $ps_idno,
	        'access' => $pn_access
	    ];
	    if ($vn_rank = caGetOption('rank', $pa_options, null)) {
	        $va_fld_data['rank'] = $vn_rank;
	        unset($pa_options['rank']);
	    }
	    
	    $vb_errored = false;
	    foreach($va_fld_data as $vs_f => $vs_v) {
	        $t_media->set($vs_f, $vs_v, $pa_options);
	        if ($t_media->numErrors() > 0) {
	            $this->errors += $t_media->errors;
	            $vb_errored = true;
	        }
	    }
	    if ($vb_errored) return false;
	    if (!($vn_rc = $t_media->update($pa_options))) {
	        $this->errors = $t_media->errors;
	        return $vn_rc;
	    }
	    return $t_media;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return array
	 */
	public function removeMedia($pn_media_id, $pa_options=null) {
	    if (!$this->getPrimaryKey()) { return false; }
	    $t_media = new ca_site_page_media($pn_media_id);
	    if ($t_media->get('page_id') != $this->getPrimaryKey()) { return false; } 
	    if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_media->setTransaction($o_trans); }
	    if (!$t_media->isLoaded()) { return null; }
	    $t_media->setMode(ACCESS_WRITE);
	    if (!($vn_rc = $t_media->delete(true))) {
	        $this->errors = $t_media->errors;
	    }
	    return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return array
	 */
	public function getBundleFormValues($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options=null) {
	    $va_media = $this->getPageMedia(array('thumbnail', 'original'), $pa_options);
				       
        $t_item = new ca_site_page_media();
        $va_initial_values = [];
        foreach($va_media as $vn_media_id => $va_m) {
            $va_initial_values[$vn_media_id] = array(
                    'idno' => $va_m['idno'], 
                    'title' => $va_m['title'],
                    'caption' => $va_m['caption'],
                    'access' => $va_m['access'],
                    'access_display' => $t_item->getChoiceListValue('access', $va_m['access']), 
                    'icon' => $va_m['tags']['thumbnail'], 
                    'mimetype' => $va_m['info']['original']['PROPERTIES']['mimetype'], 
                    'filesize' => @filesize($va_m['paths']['original']), 
                    'type' => $va_m['info']['original']['PROPERTIES']['typename'], 
                    'dimensions' => $va_m['dimensions']['original'], 
                    'filename' => $va_m['info']['ORIGINAL_FILENAME'] ? $va_m['info']['ORIGINAL_FILENAME'] : _t('Unknown'),
                    'metadata' => $vs_extracted_metadata,
                    'md5' => $va_m['info']['original']['PROPERTIES']['MD5'],
                    'versions' => join("; ", $va_m['versions']),
                    'page_id' => $va_m['page_id'],
                    'fetched_from' => $va_m['fetched_from'],
                    'fetched_on' => $va_m['fetched_on'] ? date('c', $va_m['fetched_on']) : null,
                    'fetched' => $va_m['fetched_from'] ? _t("<h3>Fetched from:</h3> URL %1 on %2", '<a href="'.$va_m['fetched_from'].'" target="_ext" title="'.$va_m['fetched_from'].'">'.$va_m['fetched_from'].'</a>', date('c', $va_m['fetched_on'])): ""
                );
        }
        return $va_initial_values;
	}
	# ------------------------------------------------------
}
