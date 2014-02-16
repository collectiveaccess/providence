<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_elements.php : table access class for table ca_metadata_elements
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
 
require_once(__CA_LIB_DIR__.'/ca/ITakesSettings.php'); 
require_once(__CA_LIB_DIR__.'/ca/LabelableBaseModelWithAttributes.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_type_restrictions.php');


BaseModel::$s_ca_models_definitions['ca_metadata_elements'] = array(
 	'NAME_SINGULAR' 	=> _t('metadata element'),
 	'NAME_PLURAL' 		=> _t('metadata elements'),
 	'FIELDS' 			=> array(
 		'element_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this metadata element')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'Parent id', 'DESCRIPTION' => 'Parent id',
				'BOUNDS_VALUE' => array(0,65535)
		),
		'hier_element_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Element hierarchy', 'DESCRIPTION' => 'Identifier of element that is root of the element hierarchy.'
		),
		'element_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 30, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'FILTER' => '!^[\p{L}0-9_]+$!u',
				'LABEL' => _t('Element code'), 'DESCRIPTION' => _t('Unique alphanumeric code for the metadata element.'),
				'BOUNDS_LENGTH' => array(1,30),
				'UNIQUE_WITHIN' => array()
		),
		'documentation_url' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Documentation url'), 'DESCRIPTION' => _t('URL pointing to documentation for this metadata element. Leave blank if no documentation URL exists.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'datatype' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Datatype'), 'DESCRIPTION' => _t('Data type of metadata element.'),
				'BOUNDS_VALUE' => array(0,255)
		),
		'list_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_lists.list_code'),
				'DISPLAY_ORDERBY' => array('ca_lists.list_code'),
				'DEFAULT' => '',
				'LABEL' => _t('Use list (for list elements only)'), 'DESCRIPTION' => _t('Specifies the list to use as value for this element. Element must be a list type for this to apply.'),
				'BOUNDS_VALUE' => array(0,65535)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Type-specific settings for metadata element')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the element when displayed in a list with other element. Lower numbers indicate higher priority.'),
				'BOUNDS_VALUE' => array(0,65535)
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
		)
 	)
);

class ca_metadata_elements extends LabelableBaseModelWithAttributes implements ITakesSettings {
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
	protected $TABLE = 'ca_metadata_elements';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'element_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('element_code');

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
	protected $ORDER_BY = array('element_code');

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
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_ADHOC_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_metadata_elements';
	protected $HIERARCHY_ID_FLD				=	'hier_element_id';
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
	protected $LABEL_TABLE_NAME = 'ca_metadata_element_labels';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	
	static $s_element_list_cache;
	static $s_element_set_cache;
	
	static $s_settings_cache = array();
	static $s_setting_value_cache = array();
	
	static $s_element_instance_cache = array();
	
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
		ca_metadata_elements::$s_settings_cache[null] = array();
		ca_metadata_elements::$s_setting_value_cache[null] = array();
		
		parent::__construct($pn_id);	# call superclass constructor
		$this->FIELDS['datatype']['BOUNDS_CHOICE_LIST'] = array_flip(ca_metadata_elements::getAttributeTypes());
	}
	# ------------------------------------------------------
	public function load($pm_id=null, $pb_use_cache = true) {
		if ($vn_rc = parent::load($pm_id)) {
			if (!isset(ca_metadata_elements::$s_settings_cache[$this->getPrimaryKey()])) {
				ca_metadata_elements::$s_settings_cache[$this->getPrimaryKey()] = $this->get('settings');
			}
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		$this->set('settings', ca_metadata_elements::$s_settings_cache[null]);
		if ($vn_rc =  parent::insert($pa_options)) {
			ca_metadata_elements::$s_settings_cache[$this->getPrimaryKey()] = ca_metadata_elements::$s_settings_cache[null];
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function update($pa_options=null) {
		$this->set('settings', ca_metadata_elements::$s_settings_cache[$this->getPrimaryKey()]);
		return parent::update($pa_options);
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related = false, $pa_options = NULL, $pa_fields = NULL, $pa_table_list = NULL) {
		$vn_id = $this->getPrimaryKey();
		if ($vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list)) {
			unset(ca_metadata_elements::$s_settings_cache[$vn_id]);
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	# Element set methods
	# ------------------------------------------------------
	/**
		Returns array of elements in set of currently loaded row
	 */
	public function getElementsInSet($pn_element_id=null, $pb_use_cache=true) {
		if (!$pn_element_id) {
			$pn_element_id = $this->getPrimaryKey();
		}
		if (!$pn_element_id) { return null; }
		
		if ($pb_use_cache && isset(ca_metadata_elements::$s_element_set_cache[$pn_element_id])) { return ca_metadata_elements::$s_element_set_cache[$pn_element_id]; }
		
		$va_hier = $this->getHierarchyAsList($pn_element_id);
		$va_element_set = array();
		
		$va_element_ids = array();
		foreach($va_hier as $va_element) {
			$va_element_ids[] = $va_element['NODE']['element_id'];
		}
		
		// Get labels
		$va_labels = $this->getPreferredDisplayLabelsForIDs($va_element_ids);
		
		$va_root = null;
		foreach($va_hier as $va_element) {
			$va_element['NODE']['settings'] = unserialize(base64_decode($va_element['NODE']['settings']));	// decode settings vars into associative array
			$va_element['NODE']['display_label'] = $va_labels[$va_element['NODE']['element_id']];
			if (!trim($va_element['NODE']['parent_id'])) {
				$va_root = $va_element['NODE'];
			} else {
				$va_element_set[$va_element['NODE']['parent_id']][$va_element['NODE']['rank']][$va_element['NODE']['element_id']] = $va_element['NODE'];
			}
		}
	
		$va_tmp = $this->_getSortedElementsForParent($va_element_set, $va_root['element_id']);
		array_unshift($va_tmp, $va_root);
		
		return ca_metadata_elements::$s_element_set_cache[$pn_element_id] = $va_tmp;
	}
	# ------------------------------------------------------
	private function _getSortedElementsForParent(&$pa_element_set, $pn_parent_id) {
		if (!isset($pa_element_set[$pn_parent_id]) || !$pa_element_set[$pn_parent_id]) { return array(); }
		
		ksort($pa_element_set[$pn_parent_id]);
		
		$va_tmp = array();
		foreach($pa_element_set[$pn_parent_id] as $vn_rank => $va_elements_by_id) {
			foreach($va_elements_by_id as $vn_element_id => $va_element) {
				$va_tmp[$vn_element_id] = $va_element;
				$va_tmp = array_merge($va_tmp, $this->_getSortedElementsForParent($pa_element_set, $vn_element_id));
			}
		}
		
		return $va_tmp;
	}
	# ------------------------------------------------------
	# Settings
	# ------------------------------------------------------
	/**
	 * Returns associative array of setting descriptions (but *not* the setting values)
	 * The keys of this array are the setting codes, the values associative arrays containing
	 * info about the setting itself (label, description type of value, how to display an entry element for the setting in a form)
	 */
	public function getAvailableSettings() {
		$t_attr_val = Attribute::getValueInstance((int)$this->get('datatype'));
		return $t_attr_val ? $t_attr_val->getAvailableSettings($this->getSettings()) : null;
	}
	# ------------------------------------------------------
	/**
	 * Returns an associative array with the setting values for this element
	 * The keys of the array are setting codes, the values are the setting values
	 */
	public function getSettings() {
		if (is_null($this->get('datatype'))) { return null; }
		return ca_metadata_elements::$s_settings_cache[$this->getPrimaryKey()];
	}
	# ------------------------------------------------------
	/**
	 * Set setting value 
	 * (you must call insert() or update() to write the settings to the database)
	 */
	public function setSetting($ps_setting, $pm_value, &$ps_error=null) {
		if (is_null($this->get('datatype'))) { return null; }
		if (!$this->isValidSetting($ps_setting)) { return null; }
		
		$o_value_instance = Attribute::getValueInstance($this->get('datatype'), null, true);
		if (!$o_value_instance->validateSetting($this->getFieldValuesArray(), $ps_setting, $pm_value, $vs_error)) {
			$ps_error = $vs_error;
			return false;
		}
		$va_settings = $this->getSettings();
		
		$va_available_settings = $this->getAvailableSettings();
		$va_properties = $va_available_settings[$ps_setting];
		
		if (($va_properties['formatType'] == FT_NUMBER) && ($va_properties['displayType'] == DT_CHECKBOXES) && (!$pm_value)) {
			$pm_value = '0';
		}
		$va_settings[$ps_setting] = $pm_value;
		ca_metadata_elements::$s_settings_cache[$this->getPrimaryKey()] = $va_settings;
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Return setting value
	 */
	public function getSetting($ps_setting, $pb_use_cache=false) {
		if ($pb_use_cache && isset(ca_metadata_elements::$s_setting_value_cache[$vn_id = $this->getPrimaryKey()][$ps_setting])) { return ca_metadata_elements::$s_setting_value_cache[$vn_id][$ps_setting]; }
		if (is_null($this->get('datatype'))) { return null; }
		$va_settings = $this->getSettings();
		$va_available_settings = $this->getAvailableSettings();
		
		$vs_default = isset($va_available_settings[$ps_setting]['default']) ? $va_available_settings[$ps_setting]['default'] : null;
		return ca_metadata_elements::$s_setting_value_cache[$vn_id][$ps_setting] = isset($va_settings[$ps_setting]) ? $va_settings[$ps_setting] : $vs_default;
	}
	# ------------------------------------------------------
	/**
	 * Returns true if setting code exists for the current element's datatype
	 */ 
	public function isValidSetting($ps_setting) {
		if (is_null($this->get('datatype'))) { return false; }
		$va_settings = $this->getAvailableSettings();
		return (isset($va_settings[$ps_setting])) ? true : false;
	}
	# ------------------------------------------------------
	/**
	 * Returns HTML form element for editing of setting
	 */ 
	public function settingHTMLFormElement($ps_setting, $pa_options=null) {
		if(!$this->isValidSetting($ps_setting)) {
			return false;
		}
		$va_available_settings = $this->getAvailableSettings();
		$va_properties = $va_available_settings[$ps_setting];
		
		if (((int)$this->get('parent_id') > 0) && isset($va_properties['validForRootOnly']) && $va_properties['validForRootOnly']) {
			return false;
		}
		
		$vs_input_name = "setting_$ps_setting";
		
		if(isset($pa_options['label_id'])) {
			$vs_label_id = $pa_options['label_id'];
		} else {
			$vs_label_id = "setting_{$ps_setting}_label";
		}
		
		
		$vs_return = "\n".'<div class="formLabel">'."\n";
		$vs_return .= '<span class="'.$vs_label_id.'">'.$va_properties['label'].'</span><br />'."\n";
		
		switch($va_properties['displayType']){
			# --------------------------------------------
			case DT_FIELD:
				if($va_properties["height"]==1){
					$vs_return .= '<input name="'.$vs_input_name.'" type="text" size="'.$va_properties["width"].'" value="'.$this->getSetting($ps_setting).'" />'."\n";
				} else if($va_properties["height"]>1){
					$vs_return .= '<textarea name="'.$vs_input_name.'" cols="'.$va_properties["width"].'" rows="'.$va_properties["height"].'">'.$this->getSetting($ps_setting).'</textarea>'."\n";
				}
				break;
			# --------------------------------------------
			case DT_PASSWORD:
				$vs_return .= '<input name="'.$vs_input_name.'" type="password" size="'.$va_properties["width"].'" value="'.$this->getSetting($ps_setting).'" />'."\n";
				break;
			# --------------------------------------------
			case DT_CHECKBOXES:
				$va_attributes = array('value' => '1');
				if (trim($this->getSetting($ps_setting))) {
					$va_attributes['checked'] = '1';
				}
				$vs_return .= caHTMLCheckboxInput($vs_input_name, $va_attributes);
				break;
			# --------------------------------------------
			case DT_SELECT:
 				$vn_width = (isset($va_properties['width']) && (strlen($va_properties['width']) > 0)) ? $va_properties['width'] : "100px";
				$vn_height = (isset($va_properties['height']) && (strlen($va_properties['height']) > 0)) ? $va_properties['height'] : "50px";
				
				if (!$vs_input_id) { $vs_input_id = $vs_input_name; }
				if ($vn_height > 1) { $va_attr['multiple'] = 1; $vs_input_name .= '[]'; }
				$va_opts = array('id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height);
				
				$vm_value = $this->getSetting($ps_setting);
				
				if(is_array($vm_value)) {
					$va_opts['values'] = $vm_value;
				} else {
					$va_opts['value'] = $vm_value;
					if(!isset($va_opts['value'])) { $va_opts['value'] = -1; }		// make sure default list item is never selected
				}
				
				// reload settings form when value for this element changes
				if (isset($va_properties['refreshOnChange']) && (bool)$va_properties['refreshOnChange']) {
					$va_attr['onchange'] = "caSetElementsSettingsForm({ {$vs_input_id} : jQuery(this).val() }); return false;";
				}
				$vs_return .= caHTMLSelect($vs_input_name, $va_properties['options'], $va_attr, $va_opts);
				break;			
			# --------------------------------------------
			default:
				break;
			# --------------------------------------------
		}
		$vs_return .= '</div>'."\n";
		
		TooltipManager::add('.'.$vs_label_id, "<h3>".$va_properties["label"]."</h3>".$va_properties["description"]);

		return $vs_return;
	}
	# ------------------------------------------------------
	# Static
	# ------------------------------------------------------
	public static function getAttributeTypes() {
		$o_config = Configuration::load();
		$o_types = Configuration::load($o_config->get('attribute_type_config'));
		
		$va_types = $o_types->getList('types');
		foreach($va_types as $vn_i => $vs_typename) {
			if ($vs_typename == 'NOT_USED') { unset($va_types[$vn_i]); }
		}
		return $va_types;
	}
	# ------------------------------------------------------
	/**
	 * Returns numeric code for data type name
	 *
	 * @param $ps_datatype_name string Name of data type (eg. "Text")
	 * @return int Numeric code for data type
	 */
	public static function getAttributeTypeCode($ps_datatype_name) {
		$va_types = ca_metadata_elements::getAttributeTypes();
		return array_search($ps_datatype_name, $va_types);
	}
	# ------------------------------------------------------
	/**
	 * Returns data type name for numeric code
	 *
	 * @param $pn_type_code numeric type code
	 * @return string Name of data type (eg. 'Text') or null if code is not defined
	 */
	public static function getAttributeNameForTypeCode($pn_type_code) {
		$va_types = ca_metadata_elements::getAttributeTypes();
		return isset($va_types[$pn_type_code]) ? $va_types[$pn_type_code] : null;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of "root" metadata elements – elements that are either freestanding or at the top of an element hierarchy (Eg. have sub-elements but are not sub-elements themselves).
	 * Root elements are used to reference the element as a whole (including sub-elements), so it is useful to be able to obtain
	 * a list of these elements with sub-elements filtered out.
	 *
	 * @param $pm_table_name_or_num mixed Optional table name or number to filter list with. If specified then only elements that have a type restriction to the table are returned. If omitted (default) then all root elements, regardless of type restrictions, are returned.
	 * @param $pm_type_name_or_id mixed
	 * @return array A List of elements. Each list item is an array with keys set to field names; there is one additional value added with key "display_label" set to the display label of the element in the current locale
	 */
	public static function getRootElementsAsList($pm_table_name_or_num=null, $pm_type_name_or_id=null, $pb_use_cache=true, $pb_return_stats=false){
		return ca_metadata_elements::getElementsAsList(true, $pm_table_name_or_num, $pm_type_name_or_id, $pb_use_cache, $pb_return_stats);
	}
	# ------------------------------------------------------
	/**
	 * Returns all elements in system as list
	 *
	 * @param $pb_root_elements_only boolean If true, then only root elements are returned; default is false
	 * @param $pm_table_name_or_num mixed Optional table name or number to filter list with. If specified then only elements that have a type restriction to the table are returned. If omitted (default) then all elements, regardless of type restrictions, are returned.
	 * @param $pm_type_name_or_id mixed Optional type code or type_id to restrict elements to.  If specified then only elements that have a type restriction to the specified table and type are returned. 
	 * @param $pb_use_cache boolean Optional control for list cache; if true [default] then the will be cached for the request; if false the list will be generated from the database. The list is always generated at least once in the current request - there is no inter-request caching
	 * @param $pa_data_types array Optional list of element data types to filter on.
	 *
	 * @return array A List of elements. Each list item is an array with keys set to field names; there is one additional value added with key "display_label" set to the display label of the element in the current locale
	 */
	public static function getElementsAsList($pb_root_elements_only=false, $pm_table_name_or_num=null, $pm_type_name_or_id=null, $pb_use_cache=true, $pb_return_stats=false, $pb_index_by_element_code=false, $pa_data_types=null){
		$o_dm = Datamodel::load();
		$vn_table_num = $o_dm->getTableNum($pm_table_name_or_num);
		$vs_cache_key = md5($pm_table_name_or_num.'/'.$pm_type_name_or_id.'/'.($pb_root_elements_only ? '1' : '0').'/'.($pb_index_by_element_code ? '1' : '0').print_R($pa_data_types, true));
		if ($pb_use_cache && ca_metadata_elements::$s_element_list_cache[$vs_cache_key]) {
			if (($pb_return_stats && isset(ca_metadata_elements::$s_element_list_cache[$vs_cache_key]['ui_counts'])) || !$pb_return_stats) {
				return ca_metadata_elements::$s_element_list_cache[$vs_cache_key];
			}
		}
		
		if ($pb_return_stats) {
			$va_counts_by_attribute = ca_metadata_elements::getUIUsageCounts();
			$va_restrictions_by_attribute = ca_metadata_elements::getTypeRestrictionsAsList();
		}
		$vo_db = new Db();
		
		
		$va_wheres = array();
		$va_where_params = array();
		if ($pb_root_elements_only) {
			$va_wheres[] = 'cme.parent_id is NULL';
		}
		if ($vn_table_num) {
			$va_wheres[] = 'cmtr.table_num = ?';
			$va_where_params[] = (int)$vn_table_num;
			
			if ($pm_type_name_or_id) {
				$t_list_item = new ca_list_items();
				if (!is_numeric($pm_type_name_or_id)) {
					$t_list_item->load(array('idno' => $pm_type_name_or_id));
				} else {
					$t_list_item->load((int)$pm_type_name_or_id);
				}
				$va_type_ids = array();
				if ($vn_type_id = $t_list_item->getPrimaryKey()) {
					$va_type_ids[$vn_type_id] = true;
					if ($qr_children = $t_list_item->getHierarchy($vn_type_id, array())) {
						while($qr_children->nextRow()) {
							$va_type_ids[$qr_children->get('item_id')] = true;
						}
					}
					$va_wheres[] = '((cmtr.type_id = ?) OR (cmtr.include_subtypes = 1 AND cmtr.type_id IN (?)))';
					$va_where_params[] = (int)$vn_type_id;
					$va_where_params[] = $va_type_ids;
				}
			}
			
			$vs_wheres = ' WHERE '.join(' AND ', $va_wheres);
			$qr_tmp = $vo_db->query("
				SELECT cme.*
				FROM ca_metadata_elements cme
				INNER JOIN ca_metadata_type_restrictions AS cmtr ON cme.hier_element_id = cmtr.element_id
				{$vs_wheres}
			", $va_where_params);
		} else {
			if (sizeof($va_wheres)) {
				$vs_wheres = ' WHERE '.join(' AND ', $va_wheres);
			} else {
				$vs_wheres = '';
			}
			$qr_tmp = $vo_db->query("
				SELECT * 
				FROM ca_metadata_elements cme
				{$vs_wheres}
			");
		}
		$va_return = array();
		$t_element = new ca_metadata_elements();
		
		$va_element_ids = array();
		while($qr_tmp->nextRow()){
			$vn_element_id = $qr_tmp->get('element_id');
			$vs_element_code = $qr_tmp->get('element_code');
			$vs_datatype = $qr_tmp->get('datatype');
			
			if (is_array($pa_data_types) && !in_array($vs_datatype, $pa_data_types)) { continue; }
			
			foreach($t_element->getFields() as $vs_field){
				$va_record[$vs_field] = $qr_tmp->get($vs_field);
			}
			$va_record['settings'] = caUnserializeForDatabase($qr_tmp->get('settings'));
			
			if ($pb_return_stats) {
				$va_record['ui_counts'] = $va_counts_by_attribute[$vs_code = $qr_tmp->get('element_code')];
				$va_record['restrictions'] = $va_restrictions_by_attribute[$vs_code];
			}
			$va_return[$vn_element_id] = $va_record;
		}
		
		// Get labels
		$va_labels = $t_element->getPreferredDisplayLabelsForIDs(array_keys($va_return));
		foreach($va_labels as $vn_id => $vs_label) {
			$va_return[$vn_id]['display_label'] = $vs_label;
		}
		if ($pb_index_by_element_code) {
			$va_return_proc = array();
			foreach($va_return as $vn_id => $va_element) {
				$va_return_proc[$va_element['element_code']] = $va_element;
			}
			$va_return = $va_return_proc;
		}
		
		return ca_metadata_elements::$s_element_list_cache[$vs_cache_key] = sizeof($va_return) > 0 ? $va_return : false;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of elements in system
	 *
	 * @param $pb_root_elements_only boolean If true, then only root elements are counted; default is false
	 * @param $pm_table_name_or_num mixed Optional table name or number to filter list with. If specified then only elements that have a type restriction to the table are counted. If omitted (default) then all elements, regardless of type restrictions, are returned.
	 * @param $pm_type_name_or_id mixed Optional type code or type_id to restrict elements to.  If specified then only elements that have a type restriction to the specified table and type are counted. 
	 * @return int The number of elements
	 */
	public static function getElementCount($pb_root_elements_only=false, $pm_table_name_or_num=null, $pm_type_name_or_id=null){
		$o_dm = Datamodel::load();
		$vn_table_num = $o_dm->getTableNum($pm_table_name_or_num);
		
		$vo_db = new Db();
		
		$va_wheres = array();
		if ($pb_root_elements_only) {
			$va_wheres[] = 'cme.parent_id is NULL';
		}
		if ($vn_table_num) {
			$va_wheres[] = 'cmtr.table_num = ?';
			$va_where_params[] = (int)$vn_table_num;
			
			if ($pm_type_name_or_id) {
				$t_list_item = new ca_list_items();
				if (!is_numeric($pm_type_name_or_id)) {
					$t_list_item->load(array('idno' => $pm_type_name_or_id));
				} else {
					$t_list_item->load((int)$pm_type_name_or_id);
				}
				$va_type_ids = array();
				if ($vn_type_id = $t_list_item->getPrimaryKey()) {
					$va_type_ids[$vn_type_id] = true;
					if ($qr_children = $t_list_item->getHierarchy($vn_type_id, array())) {
						while($qr_children->nextRow()) {
							$va_type_ids[$qr_children->get('item_id')] = true;
						}
					}
					$va_wheres[] = '((cmtr.type_id = ?) OR (cmtr.include_subtypes = 1 AND cmtr.type_id IN (?)))';
					$va_where_params[] = (int)$vn_type_id;
					$va_where_params[] = $va_type_ids;
				}
			}
			
			$vs_wheres = ' WHERE '.join(' AND ', $va_wheres);
			$qr_tmp = $vo_db->query("
				SELECT count(*) c
				FROM ca_metadata_elements cme
				INNER JOIN ca_metadata_type_restrictions AS cmtr ON cme.hier_element_id = cmtr.element_id
				{$vs_wheres}
			", $va_where_params);
		} else {
			if (sizeof($va_wheres)) {
				$vs_wheres = ' WHERE '.join(' AND ', $va_wheres);
			} else {
				$vs_wheres = '';
			}
			$qr_tmp = $vo_db->query("
				SELECT count(*) c
				FROM ca_metadata_elements cme
				{$vs_wheres}
			");
		}
		
		if($qr_tmp->nextRow()){
			return $qr_tmp->get('c');
		}
		return 0;
	}
	# ------------------------------------------------------
	/*
	 *
	 */
	public static function getSortableElements($pm_table_name_or_num, $pm_type_name_or_id=null, $pa_options=null){
		$va_elements = ca_metadata_elements::getElementsAsList(false, $pm_table_name_or_num, $pm_type_name_or_id);
		if (!is_array($va_elements) || !sizeof($va_elements)) { return array(); }
		
		$va_sortable_elements = array();
		
		$vs_key = caGetOption('indexByElementCode', $pa_options, false) ? 'element_code' : 'element_id';
		foreach($va_elements as $vn_id => $va_element) {
			if ((int)$va_element['datatype'] === 0) { continue; }
			if (!isset($va_element['settings']['canBeUsedInSort'])) { $va_element['settings']['canBeUsedInSort'] = true; }
			if ($va_element['settings']['canBeUsedInSort']) {
				$va_sortable_elements[$va_element[$vs_key]] = $va_element;
			}
		}
		return $va_sortable_elements;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of user interfaces that reference the currently loaded metadata element
	 *
	 * @return array List of user interfaces that include the currently loaded element. Array is keyed on ui_id. Values are arrays with keys set to ca_ui_editor_labels field names and associated values.
	 */
	public function getUIs() {
		if (!($vn_element_id = $this->getPrimaryKey())) {
			return null;
		}
		
		$vs_element_code = $this->get('element_code');
		
		$qr_res = $this->getDb()->query("
			SELECT ui.ui_id, uil.*, p.screen_id, ui.editor_type
			FROM ca_editor_uis ui
			INNER JOIN ca_editor_ui_labels AS uil ON uil.ui_id = ui.ui_id
			INNER JOIN ca_editor_ui_screens AS s ON s.ui_id = ui.ui_id
			INNER JOIN ca_editor_ui_bundle_placements AS p ON p.screen_id = s.screen_id
			WHERE
				p.bundle_name = 'ca_attribute_{$vs_element_code}'
		");
		
		$va_uis = array();
		while($qr_res->nextRow()) {
			$va_uis[$qr_res->get('ui_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
		}
		
		return caExtractValuesByUserLocale($va_uis);
	}
	# ------------------------------------------------------
	/**
	 * Returns array of with information about usage of metadata element(s) in user interfaces
	 *
	 * @param $pm_element_code_or_id mixed Optional element_id or code. If set then counts are only returned for the specified element. If omitted then counts are returned for all elements.
	 * @return array Array of counts. Keys are element_codes, values are arrays keyed on table DISPLAY name (eg. "set items", not "ca_set_items"). Values are the number of times the element is references in a user interface for the table.
	 */
	static public function getUIUsageCounts($pm_element_code_or_id=null) {
		// Get UI usage counts
		$vo_db = new Db();
		
		$vn_element_id = null;
		
		if ($pm_element_code_or_id) {
			$t_element = new ca_metadata_elements($pm_element_code_or_id);
			
			if (!($vn_element_id = $t_element->getPrimaryKey())) {
				if ($t_element->load(array('element_code' => $pm_element_code_or_id))) {
					$vn_element_id = $t_element->getPrimaryKey();
				}
			}
		}
		
		$vs_sql_where = '';
		if ($vn_element_id) {
			$vs_sql_where = " WHERE p.bundle_name = 'ca_attribute_".$t_element->get('element_code')."'";
		}
		
		$qr_use_counts = $vo_db->query("
			SELECT count(*) c, p.bundle_name, u.editor_type 
			FROM ca_editor_ui_bundle_placements p 
			INNER JOIN ca_editor_ui_screens AS s ON s.screen_id = p.screen_id 
			INNER JOIN ca_editor_uis AS u ON u.ui_id = s.ui_id 
			{$vs_sql_where}
			GROUP BY 
				p.bundle_name, u.editor_type
		");
		
		$va_counts_by_attribute = array();
		$o_dm = Datamodel::load();
		while($qr_use_counts->nextRow()) {
			if (preg_match('!^ca_attribute_([A-Za-z0-9_\-]+)$!', $qr_use_counts->get('bundle_name'), $va_matches)) {
				if (!($t_table = $o_dm->getInstanceByTableNum($qr_use_counts->get('editor_type'), true))) { continue; }
				$va_counts_by_attribute[$va_matches[1]][$t_table->getProperty('NAME_PLURAL')] = $qr_use_counts->get('c');
			}
		}
		
		return $va_counts_by_attribute;
	}
	# ------------------------------------------------------
	/**
	 * Returns array of with information about usage of metadata element(s) in user interfaces
	 *
	 * @param $pm_element_code_or_id mixed Optional element_id or code. If set then counts are only returned for the specified element. If omitted then counts are returned for all elements.
	 * @return array Array of counts. Keys are element_codes, values are arrays keyed on table DISPLAY name (eg. "set items", not "ca_set_items"). Values are the number of times the element is references in a user interface for the table.
	 */
	static public function getTypeRestrictionsAsList($pm_element_code_or_id=null) {
		// Get UI usage counts
		$vo_db = new Db();
		
		$vn_element_id = null;
		
		if ($pm_element_code_or_id) {
			$t_element = new ca_metadata_elements($pm_element_code_or_id);
			
			if (!($vn_element_id = $t_element->getPrimaryKey())) {
				if ($t_element->load(array('element_code' => $pm_element_code_or_id))) {
					$vn_element_id = $t_element->getPrimaryKey();
				}
			}
		}
		
		$vs_sql_where = '';
		if ($vn_element_id) {
			$vs_sql_where = " WHERE cmtr.element_id = {$vn_element_id}";
		}
		
		$qr_restrictions = $vo_db->query("
			SELECT cmtr.*, cme.element_code
			FROM ca_metadata_type_restrictions cmtr 
			INNER JOIN ca_metadata_elements AS cme ON cme.element_id = cmtr.element_id
			{$vs_sql_where}
		");
		
		$va_restrictions = array();
		$o_dm = Datamodel::load();
		$t_list = new ca_lists();
		while($qr_restrictions->nextRow()) {
			if (!($t_table = $o_dm->getInstanceByTableNum($qr_restrictions->get('table_num'), true))) { continue; }
			
			if ($vn_type_id = $qr_restrictions->get('type_id')) {
				$vs_type_name = $t_list->getItemForDisplayByItemID($vn_type_id);
			} else {
				$vs_type_name = '*';
			}
			$va_restrictions[$qr_restrictions->get('element_code')][$t_table->getProperty('NAME_PLURAL')][$vn_type_id] = $vs_type_name;
		}
		
		return $va_restrictions;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	static public function getElementDatatype($pm_element_code_or_id) {
		if ($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id)) {
			return $t_element->get('datatype');
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	static public function getInstance($pm_element_code_or_id) {
		if (isset(ca_metadata_elements::$s_element_instance_cache[$pm_element_code_or_id])) { return ca_metadata_elements::$s_element_instance_cache[$pm_element_code_or_id]; }
		
		$t_element = new ca_metadata_elements(is_numeric($pm_element_code_or_id) ? $pm_element_code_or_id : null);
		
		if (!($vn_element_id = $t_element->getPrimaryKey())) {
			if ($t_element->load(array('element_code' => $pm_element_code_or_id))) {
				return ca_metadata_elements::$s_element_instance_cache[$t_element->getPrimaryKey()] = ca_metadata_elements::$s_element_instance_cache[$t_element->get('element_code')] = $t_element;
			}
		} else {
			return ca_metadata_elements::$s_element_instance_cache[$vn_element_id] = ca_metadata_elements::$s_element_instance_cache[$t_element->get('element_code')] = $t_element;
		}
		return null;
	}
	# ------------------------------------------------------
	#
	# ------------------------------------------------------
	/**
	 * Adds restriction (a binding between a specific item and optional type and the attribute)
	 *
	 * $pn_table_num: the number of the table to bind to
	 * 
	 */
	public function addTypeRestriction($pn_table_num, $pn_type_id, $va_settings) {
		if (!($vn_element = $this->getPrimaryKey())) { return null; }		// element must be loaded
		if ($this->get('parent_id')) { return null; }						// element must be root of hierarchy
		if (!is_array($va_settings)) { $va_settings = array(); }
		
		$t_restriction = new ca_metadata_type_restrictions();
		$t_restriction->setMode(ACCESS_WRITE);
		$t_restriction->set('table_num', $pn_table_num);
		$t_restriction->set('type_id', $pn_type_id);
		$t_restriction->set('element_id', $this->getPrimaryKey());
		foreach($va_settings as $vs_setting => $vs_setting_value) {
			$t_restriction->setSetting($vs_setting, $vs_setting_value);
		}
		$t_restriction->insert();
		
		if ($t_restriction->numErrors()) {
			$this->errors = $t_restriction->errors();
			return false;
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Remove type restrictions from this element for specified table and, optionally, type
	 *
	 * $pn_table_num: the number of the table to bind to
	 * 
	 */
	public function removeTypeRestriction($pn_table_num, $pn_type_id=null) {
		if (!($vn_element = $this->getPrimaryKey())) { return null; }		// element must be loaded
		if ($this->get('parent_id')) { return null; }						// element must be root of hierarchy
		
		$o_db = $this->getDb();
		
		$vs_type_id_sql = ($pn_type_id) ? 'AND type_id = '.intval($pn_type_id) : 'AND type_id IS NULL ';
		$qr_res = $o_db->query("
			DELETE FROM ca_metadata_type_restrictions
			WHERE
				table_num = ? AND element_id = ? AND {$vs_type_id_sql}
		", (int)$pn_table_num, (int)$this->getPrimaryKey());
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Remove all type restrictions from loaded element
	 * 
	 */
	public function removeAllTypeRestrictions() {
		if (!($vn_element = $this->getPrimaryKey())) { return null; }		// element must be loaded
		if ($this->get('parent_id')) { return null; }						// element must be root of hierarchy
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			DELETE FROM ca_metadata_type_restrictions
			WHERE
				element_id = ?
		", (int)$this->getPrimaryKey());
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Return all type restrictions
	 * 
	 */
	public function getTypeRestrictions($pn_table_num=null, $pn_type_id=null) {
		if (!($vn_element_id = $this->getPrimaryKey())) { return null; }		// element must be loaded
		if ($this->get('parent_id')) { 
			// element must be root of hierarchy...
			// if not, then use root of hierarchy since all type restrictions are bound to the root
			$vn_element_id = $this->getHierarchyRootID(null);
		}	
		
		$o_db = $this->getDb();
		
		$vs_table_type_sql = '';
		if ($pn_table_num > 0) {
			$vs_table_type_sql .= ' AND table_num = '.intval($pn_table_num);
		}
		if ($pn_type_id > 0) {
			$vs_table_type_sql .= ' AND type_id = '.intval($pn_type_id);
		}
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_metadata_type_restrictions
			WHERE
				element_id = ? {$vs_table_type_sql}
		", (int)$vn_element_id);
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors();
			return false;
		}
		
		$va_restrictions = array();
		while($qr_res->nextRow()) {
			$va_restrictions[] = $qr_res->getRow();
		}
		return $va_restrictions;
	}
	# ------------------------------------------------------
	/**
	 * Return type restrictions for current element and specified table for display
	 * Display consists of type names in the current locale
	 *
	 * @param int $pn_table_num The table to return restrictions for
	 * @return array An array of type names to which this element is restricted, in the current locale
	 * 
	 */
	public function getTypeRestrictionsForDisplay($pn_table_num) {
		$va_restrictions = $this->getTypeRestrictions($pn_table_num);
		
		$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($pn_table_num, true);
		$va_restriction_names = array();
		$va_type_names = $t_instance->getTypeList();
		foreach($va_restrictions as $vn_i => $va_restriction) {
			if (!$va_restriction['type_id']) { continue; }
			$va_restriction_names[] = $va_type_names[$va_restriction['type_id']]['name_plural'];
		}
		return $va_restriction_names;
	}
	# ------------------------------------------------------
	/**
	 * Load type restriction for specified table and type and return loaded model instance.
	 * Will return specific restriction for type_id, or a general (type_id=null) restriction if no
	 * type-specific restriction is defined.
	 *
	 * @param $pn_table_num - table_num of type restriction
	 * @param $pn_type_id - type_id of type restriction; leave null if you want a non-type-specific restriction
	 * @return ca_metadata_type_restrictions instance - will be loaded with type restriction
	 */
	public function getTypeRestrictionInstanceForElement($pn_table_num, $pn_type_id) {
		if (!($vn_element_id = $this->getPrimaryKey())) { return null; }		// element must be loaded
		if ($this->get('parent_id')) { return null; }						// element must be root of hierarchy
		
		$t_restriction = new ca_metadata_type_restrictions();
		
		if (($pn_type_id > 0) && $t_restriction->load(array('table_num' => (int)$pn_table_num, 'type_id' => (int)$pn_type_id, 'element_id' => (int)$vn_element_id))) {
			return $t_restriction;
		} else {
			if ($t_restriction->load(array('table_num' => (int)$pn_table_num, 'type_id' => null, 'element_id' => (int)$vn_element_id))) {
				return $t_restriction;
			}
			
			// try going up the hierarchy to find one that we can inherit from
			if ($pn_type_id && ($t_type_instance = new ca_list_items($pn_type_id))) {
				$va_ancestors = $t_type_instance->getHierarchyAncestors(null, array('idsOnly' => true));
				if (is_array($va_ancestors)) {
					array_pop($va_ancestors); // get rid of root
					if (sizeof($va_ancestors)) {
						$qr_res = $this->getDb()->query("
							SELECT restriction_id
							FROM ca_metadata_type_restrictions
							WHERE
								type_id IN (?) AND table_num = ? AND include_subtypes = 1 AND element_id = ?
						", array($va_ancestors, (int)$pn_table_num, (int)$vn_element_id));
						
						if ($qr_res->nextRow()) {
							if ($t_restriction->load($qr_res->get('restriction_id'))) {
								return $t_restriction;
							}
						}
					}
				}
			}
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	  *
	  */
	public function htmlFormElement($ps_field, $ps_format=null, $pa_options=null) {
		if ($ps_field == 'list_id') {
			// Custom list drop-down
			$vs_format = $this->_CONFIG->get('form_element_display_format');
			
			$t_list = new ca_lists();
			$va_lists = caExtractValuesByUserLocale($t_list->getListOfLists());
			$va_opts = array();
			foreach($va_lists as $vn_list_id => $va_list_info) {
				$va_opts[$va_list_info['name'].' ('.$va_list_info['list_code'].')'] = $vn_list_id;
			}
			ksort($va_opts);
			$vs_format = str_replace('^LABEL', $vs_field_label = $this->getFieldInfo('list_id', 'LABEL'), $vs_format);
			$vs_format = str_replace('^EXTRA', '', $vs_format);
			
			$vs_format = str_replace('^ELEMENT', caHTMLSelect($ps_field, $va_opts, array('id' => $ps_field), array('value' => $this->get('list_id'))), $vs_format);
			
			if (!isset($pa_options['no_tooltips']) || !$pa_options['no_tooltips']) {
				TooltipManager::add('#list_id', "<h3>{$vs_field_label}</h3>".$this->getFieldInfo('list_id', 'DESCRIPTION'), $pa_options['tooltip_namespace']);
			}
			return $vs_format;
		}
		return parent::htmlFormElement($ps_field, $ps_format, $pa_options);
	}
	# ------------------------------------------------------
	/**
	  *
	  */
	public function getPresetsAsHTMLFormElement($pa_options=null) {
		if (!($vn_element_id = $this->getPrimaryKey())) { return null; }		// element must be loaded
	
		$o_presets = Configuration::load(__CA_APP_DIR__."/conf/attributePresets.conf");
		
		if ($va_presets = $o_presets->getAssoc($this->get('element_code'))) {
			$vs_form_element_name = caGetOption('name', $pa_options, "{fieldNamePrefix}_presets_{n}");
		
			$va_opts = array(_t('SELECT PRESET') => '');
			foreach($va_presets as $vs_code => $va_preset) {
				$va_opts[$va_preset['name']] = $vs_code;
			}
			
			$va_attr = array(
				'id' => $vs_form_element_name,
				'onchange' => "caHandlePresets_{fieldNamePrefix}(jQuery(this).val(),\"{n}\");",
				'style' => 'font-size: 9px;'
			);
			
			$vs_buf = caHTMLSelect($vs_form_element_name, $va_opts, $va_attr, $pa_options);
			
			return $vs_buf;
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	  *
	  */
	public function getPresetsJavascript($ps_field_prefix, $pa_options=null) {
		if (!($vn_element_id = $this->getPrimaryKey())) { return null; }		// element must be loaded
	
		$o_presets = Configuration::load(__CA_APP_DIR__."/conf/attributePresets.conf");
		
		if ($va_presets = $o_presets->getAssoc($this->get('element_code'))) {
			$va_elements = $this->getElementsInSet();
		
			$va_element_code_to_ids = $va_element_info = array();
			foreach($va_elements as $va_element) {
				$va_element_code_to_ids[$va_element['element_code']] = $va_element['element_id'];
				$va_element_info[$va_element['element_code']] = $va_element;
			}
			
			foreach($va_presets as $vs_code => $va_preset) {
				foreach($va_preset['values'] as $vs_k => $vs_v) {
					if(!$va_element_code_to_ids[$vs_k]) { continue; }
					
					switch((int)$va_element_info[$vs_k]['datatype']) {
						case 3:
							$va_presets[$vs_code]['values'][$va_element_code_to_ids[$vs_k]] = caGetListItemID($va_element_info[$vs_k]['list_id'], $vs_v);
							break;
						default: 
							$va_presets[$vs_code]['values'][$va_element_code_to_ids[$vs_k]] = $vs_v;
							break;
					}
					unset($va_presets[$vs_code]['values'][$vs_k]);
				}
			}
			
			$vs_buf .= "\n
	function caHandlePresets_{$ps_field_prefix}_(s, n) {
		var presets = ".json_encode($va_presets).";
		if (presets[s]){ 
			for(var k in presets[s]['values']) {
				if (!presets[s]['values'][k]) { continue; }
				jQuery('#{$ps_field_prefix}' + '_' + k + '_' + n + '').val(presets[s]['values'][k]);
			}
		}
	}\n";
			return $vs_buf;
		}
		return null;
	}
	# ------------------------------------------------------
}
?>