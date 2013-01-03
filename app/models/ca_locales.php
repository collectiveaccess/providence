<?php
/** ---------------------------------------------------------------------
 * app/models/ca_locales.php : table access class for table ca_locales
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

BaseModel::$s_ca_models_definitions['ca_locales'] = array(
 	'NAME_SINGULAR' 	=> _t('locale'),
 	'NAME_PLURAL' 		=> _t('locales'),
 	'FIELDS' 			=> array(
 		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this locale')
		),
		'name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Name'), 'DESCRIPTION' => _t('Name of locale. The chosen name should be descriptive and must be unique.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'language' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 3, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('ISO 639 language code'), 'DESCRIPTION' => _t('2 or 3 character language code for locale; use the ISO 639-1 standard for two letter codes or ISO 639-2 for three letter codes.'),
				'BOUNDS_LENGTH' => array(2,3)
		),
		'country' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 2, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('ISO 3166-1 alpha-2 country code'), 'DESCRIPTION' => _t('2 characer country code for locale; use the ISO 3166-1 alpha-2 standard.'),
				'BOUNDS_LENGTH' => array(0,2)
		),
		'dialect' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 8, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Dialect'), 'DESCRIPTION' => _t('Optional dialect specifier for locale. Up to 8 characters, this code is usually one defined by the language lists at http://www.ethnologue.com.'),
				'BOUNDS_LENGTH' => array(0,8)
		),
		'dont_use_for_cataloguing' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES, 
				'DISPLAY_WIDTH' => 2, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Do not use for cataloguing'), 'DESCRIPTION' => _t('If checked then locale cannot be used to tag content.')
		),
 	)
);

class ca_locales extends BaseModel {
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
	protected $TABLE = 'ca_locales';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'locale_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('name');

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
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	
	public static $s_locale_code_to_id = array();
	public static $s_locale_id_to_code = array();
	public static $s_locale_list_cache = array();
	public static $s_locale_count_cache = array();
	
	public static $s_default_locale_id = null;
	
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
	 *
	 */
	static public function getDefaultCataloguingLocaleID() {
		if (ca_locales::$s_default_locale_id) { return ca_locales::$s_default_locale_id; }
		global $g_ui_locale_id;
		
		$va_locale_list = ca_locales::getLocaleList(array('available_for_cataloguing_only' => true));
		
		$vn_default_id = null;
		if (isset($va_locale_list[$g_ui_locale_id])) { 
			$vn_default_id = $g_ui_locale_id; 
		} else {
			$va_tmp = array_keys($va_locale_list);
			$vn_default_id =  array_shift($va_tmp);
		}
		
		return ca_locales::$s_default_locale_id = $vn_default_id;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getLocaleList($pa_options=null) {
		
		$vs_sort_field 				= isset($pa_options['sort_field']) ? $pa_options['sort_field'] : '';
		$vs_sort_direction 			= isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : 'asc';
		$vb_index_by_code 			= (isset($pa_options['index_by_code']) && $pa_options['index_by_code']) ? true : false;
		$vb_return_display_values 	= (isset($pa_options['return_display_values']) && $pa_options['return_display_values']) ? true : false;
		$vb_available_for_cataloguing_only 	= (isset($pa_options['available_for_cataloguing_only']) && $pa_options['available_for_cataloguing_only']) ? true : false;

		$va_valid_sorts = array('name', 'language', 'country', 'dialect');
		if (!in_array($vs_sort_field, $va_valid_sorts)) {
			$vs_sort_field = 'name';
		}
	
		$vs_cache_key = $vs_sort_field.'/'.$vs_sort_direction.'/'.($vb_index_by_code ? 1 : 0).'/'.($vb_return_display_values ? 1 : 0).'/'.($vb_available_for_cataloguing_only ? 1 : 0);
		if(isset(ca_locales::$s_locale_list_cache[$vs_cache_key])) {
			return ca_locales::$s_locale_list_cache[$vs_cache_key];
		}
		
		$o_db = new Db();
		$vs_sort = 'ORDER BY '.$vs_sort_field;

		$qr_locales = $o_db->query("
			SELECT *
			FROM ca_locales
			$vs_sort
		");
		
		$va_locales = array();
		while($qr_locales->nextRow()) {
			if ($vb_available_for_cataloguing_only && $qr_locales->get('dont_use_for_cataloguing')) { continue; }
			if ($vb_return_display_values) {
				$vm_val = $qr_locales->get('name');
			} else {
				$vm_val = $qr_locales->getRow();
			}
			$vs_code = ($qr_locales->get('language').'_'.$qr_locales->get('country'));
			$vn_id = $qr_locales->get('locale_id');
			
			if (!$vb_return_display_values) {
				$vm_val['code'] = $vs_code;
			}
			if ($vb_index_by_code) {
				$va_locales[$vs_code] = $vm_val;
			} else {
 				$va_locales[$vn_id] = $vm_val;
 			}
 			
 			ca_locales::$s_locale_code_to_id[$vs_code] = $vn_id;
 			ca_locales::$s_locale_id_to_code[$vn_id] = $vs_code;
 		}
		
		ca_locales::$s_locale_list_cache[$vs_cache_key] = $va_locales;
		return $va_locales;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getName() {
		return $this->get('name')." (".$this->getCode().")";
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getCode() {
		return ($this->get('country')) ? ($this->get('language')."_".$this->get('country')) : $this->get('language');
	}
	# ------------------------------------------------------
	/**
	 * Returns number of locales configured
	 *
	 * @param array $pa_options Array of options. Supported options include:
	 *			forCataloguing - if set then only locales that are marked as available for cataloguing are counted
	 * @return int Number of locales
	 */
	public function numberOfLocales($pa_options=null) {
		
		$vs_for_cataloguing_sql = '';
		$vs_cache_key = 'all';
		if (isset($pa_options['forCataloguing']) && (bool)$pa_options['forCataloguing']) {
			$vs_for_cataloguing_sql = " WHERE dont_use_for_cataloguing = 0";
			$vs_cache_key = 'forCataloguing';
		}
		
		if (isset(ca_locales::$s_locale_count_cache[$vs_cache_key])) { return ca_locales::$s_locale_count_cache[$vs_cache_key]; }
		
		$qr_res = $this->getDb()->query("SELECT count(*) c FROM ca_locales {$vs_for_cataloguing_sql}");
		$vn_num_locales = 0;
		if ($qr_res->nextRow()) {
			$vn_num_locales = $qr_res->get('c');
		}
		
		return ca_locales::$s_locale_count_cache[$vs_cache_key] = $vn_num_locales;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of locales configured for cataloguing use
	 *
	 * @return int Number of locales configured for cataloguer
	 */
	static public function numberOfCataloguingLocales() {
		$t_locale = new ca_locales();
		return (int)$t_locale->numberOfLocales(array('forCataloguing' => true));
	}
	# ------------------------------------------------------
	/**
	 * Loads model with locale record having specifed code and returns the locale_id. Once the 
	 * model is loaded you can use get() and other model methods to get at fields in the locale's record
	 *
	 * @param string $ps_code A language code in the form <language>_<country> (eg. en_US)
	 * @return int The locale_id of the locale, or null if the code is invalid
	 */
	public function loadLocaleByCode($ps_code) {
		if (!isset(ca_locales::$s_locale_code_to_id[$ps_code])) {
			ca_locales::getLocaleList(array('index_by_code' => true));
		}
		
		$this->load(ca_locales::$s_locale_code_to_id[$ps_code]);
		
		return $this->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Returns the locale_id of the specified locale, or null if the code is invalid. Note that this does not 
	 * change the state of the model - it just returns the locale_id. If you want to actually load a model instance
	 * with a locale record, use loadLocaleByCode()
	 *
	 * @param string $ps_code A language code in the form <language>_<country> (eg. en_US)
	 * @return int The locale_id of the locale, or null if the code is invalid
	 */
	public function localeCodeToID($ps_code) {
		if (!isset(ca_locales::$s_locale_code_to_id[$ps_code])) {
			ca_locales::getLocaleList(array('index_by_code' => true));
		}
		return ca_locales::$s_locale_code_to_id[$ps_code];
	}
	# ------------------------------------------------------
	/**
	 * Returns the locale code of the specified locale_id, or null if the id is invalid. Note that this does not
	 * change the state of the model - it just returns the locale.
	 *
	 * @param int $pn_id The locale_id of the locale, or null if the code is invalid
	 * @return string A language code in the form <language>_<country> (eg. en_US)
	 */
	public function localeIDToCode($pn_id) {
		if(!isset(ca_locales::$s_locale_id_to_code[$pn_id])){
			ca_locales::getLocaleList();
		}
		return ca_locales::$s_locale_id_to_code[$pn_id];
	}
	# ------------------------------------------------------
}
?>
