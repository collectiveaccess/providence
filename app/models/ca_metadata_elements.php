<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_elements.php : table access class for table ca_metadata_elements
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

require_once(__CA_LIB_DIR__.'/ITakesSettings.php');
require_once(__CA_LIB_DIR__.'/LabelableBaseModelWithAttributes.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_type_restrictions.php');
require_once(__CA_LIB_DIR__."/SyncableBaseModel.php");


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
			'LABEL' => _t('Documentation URL'), 'DESCRIPTION' => _t('URL pointing to documentation for this metadata element. Leave blank if no documentation URL exists.'),
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

	/**
	 * Temporary element settings stash for this instance
	 * @var array
	 */
	protected $opa_element_settings = array();

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
		$this->FIELDS['datatype']['BOUNDS_CHOICE_LIST'] = array_flip(ca_metadata_elements::getAttributeTypes());

		if($pn_id) { $this->opa_element_settings = $this->get('settings'); }
	}
	# ------------------------------------------------------
	public function load($pm_id=null, $pb_use_cache = true) {
		if ($vn_rc = parent::load($pm_id, $pb_use_cache)) {
			$this->opa_element_settings = $this->get('settings');
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		$this->set('settings', $this->getSettings());
		if ($vn_rc =  parent::insert($pa_options)) {
			$this->flushCacheForElement();
			$this->setGUID($pa_options);
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function update($pa_options=null) {
		$this->set('settings', $this->getSettings());
		$this->flushCacheForElement();
		return parent::update($pa_options);
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related = false, $pa_options = NULL, $pa_fields = NULL, $pa_table_list = NULL) {
		$this->flushCacheForElement();
		
		$vn_primary_key = $this->getPrimaryKey();
		
		$vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
		if($vn_primary_key && $vn_rc && caGetOption('hard', $pa_options, false)) {
			$this->removeGUID($vn_primary_key);
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function elementCodesToIDs($pa_element_codes, $pa_options=null) {
		$o_db = caGetOption('db', $pa_options, new Db());

		$qr_res = $o_db->query("
			SELECT element_id, element_code
			FROM ca_metadata_elements
			WHERE
				element_code IN (?)
		", array($pa_element_codes));

		$va_element_ids = array();
		while($qr_res->nextRow()) {
			$va_element_ids[$qr_res->get('element_code')] = $qr_res->get('element_id');
		}
		return $va_element_ids;
	}
	# ------------------------------------------------------
	/**
	 * Flushes the element set cache for current record, its parent and the whole element set
	 */
	public function flushCacheForElement() {
		if(!$this->getPrimaryKey()) { return; }

		if($vn_parent_id = $this->get('parent_id')) {
			CompositeCache::delete($vn_parent_id, 'ElementSets');
		}

		if($vn_hier_element_id = $this->get('hier_element_id')) {
			CompositeCache::delete($vn_hier_element_id, 'ElementSets');
		}

		CompositeCache::delete($this->getPrimaryKey(), 'ElementSets');
		$vs_key = caMakeCacheKeyFromOptions(['table_num' => null, 'type_id' => null, 'element_id' => $this->getPrimaryKey()]);
		CompositeCache::delete($vs_key, 'ElementTypeRestrictions');

		// flush getElementsAsList() cache too
		if(CompositeCache::contains('cacheKeys', 'ElementList')) {
			$va_cache_keys = CompositeCache::fetch('cacheKeys', 'ElementList');
			foreach($va_cache_keys as $vs_cache_key) {
				CompositeCache::delete($vs_cache_key, 'ElementList');
			}
		}
	}
	# ------------------------------------------------------
	# Element set methods
	# ------------------------------------------------------
	/**
	 * @param array $pa_options Options include:
	 * 		noCache = don't use cached values. Default is false (ie. use cached values)
	 */
	static public function getElementsForSet($pn_element_id, $pa_options=null) {
		$t_element = new ca_metadata_elements();
		return $t_element->getElementsInSet($pn_element_id, !caGetOption('noCache', $pa_options, false), $pa_options);
	}
	/**
	 * Returns array of elements in set of currently loaded row
	 *
	 * @param null|int $pn_element_id
	 * @param bool $pb_use_cache
	 * @param null|array $pa_options
	 * @return array|null
	 */
	public function getElementsInSet($pn_element_id=null, $pb_use_cache=true, $pa_options=null) {
		if (!$pn_element_id) { $pn_element_id = $this->getPrimaryKey(); }
		if (!$pn_element_id) { return null; }

		if($pb_use_cache && CompositeCache::contains($pn_element_id, 'ElementSets')) {
			$va_set = CompositeCache::fetch($pn_element_id, 'ElementSets');
			return (caGetOption('idsOnly', $pa_options, false) ?  caExtractArrayValuesFromArrayOfArrays($va_set, 'element_id') : $va_set);
		}

		$va_hier = $this->getHierarchyAsList($pn_element_id);
		if(!is_array($va_hier)) { return null; }
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

		$va_tmp[$va_root['element_id']] = $va_root;
		$va_tmp = array_merge($va_tmp, $this->_getSortedElementsForParent($va_element_set, $va_root['element_id']));

		CompositeCache::save($pn_element_id, $va_tmp, 'ElementSets');

		if (caGetOption('idsOnly', $pa_options, false)) { return $va_element_ids; }
		return $va_tmp;
	}
	# ------------------------------------------------------
	private function _getSortedElementsForParent(&$pa_element_set, $pn_parent_id) {
		if (!isset($pa_element_set[$pn_parent_id]) || !$pa_element_set[$pn_parent_id]) { return array(); }

		ksort($pa_element_set[$pn_parent_id]);

		$va_tmp = array();
		foreach($pa_element_set[$pn_parent_id] as $vn_rank => $va_elements_by_id) {
			foreach($va_elements_by_id as $vn_element_id => $va_element) {
				$va_tmp[$vn_element_id] = $va_element;
				//$va_tmp = array_merge($va_tmp, $this->_getSortedElementsForParent($pa_element_set, $vn_element_id));	// merge keeps keys in the correct order
				foreach($this->_getSortedElementsForParent($pa_element_set, $vn_element_id) as $k => $v) {
					if (isset($va_tmp[$k])) { unset($va_tmp[$k]); }
					$va_tmp[$k] = $v;
				}
			}
		}

		return $va_tmp;
	}
	# ------------------------------------------------------
	/**
	 * Return array of information about elements with a setting set to a given value.
	 *
	 * @param string $ps_setting Setting code
	 * @param mixed $pm_value  Setting value
	 * @param array $pa_options No options are currently supported
	 *
	 * @return array
	 */
	public static function getElementSetsWithSetting($ps_setting, $pm_value, $pa_options=null) {
	    return array_map(function($v) { $v['settings'] = caUnserializeForDatabase($v['settings']); return $v; }, array_filter(ca_metadata_elements::find('*', ['returnAs' => 'arrays']), function($v) use ($ps_setting, $pm_value) {
	        $va_settings = caUnserializeForDatabase($v['settings']);
	        if (isset($va_settings[$ps_setting]) && ($va_settings[$ps_setting] == $pm_value)) {
	            return true;
	        }
	        return false;
	    }));
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
		$va_element_info = $this->getFieldValuesArray();
		$va_element_info['settings'] = $this->getSettings();
		return $t_attr_val ? $t_attr_val->getAvailableSettings($va_element_info) : null;
	}
	# ------------------------------------------------------
	/**
	 * Returns an associative array with the setting values for this element
	 * The keys of the array are setting codes, the values are the setting values
	 */
	public function getSettings() {
		if (is_null($this->get('datatype'))) { return null; }

		return $this->opa_element_settings;
	}
	# ------------------------------------------------------
	/**
	 * Set setting value
	 * (you must call insert() or update() to write the settings to the database)
	 */
	public function setSetting($ps_setting, $pm_value, &$ps_error=null) {
		if (is_null($this->get('datatype'))) { return null; }
		if (!$this->isValidSetting($ps_setting)) { return null; }
		
		if ($ps_setting == 'canBeUsedInSort') {
			CompositeCache::delete('ElementsSortable');
			CompositeCache::delete('available_sorts');
		}

		$o_value_instance = Attribute::getValueInstance($this->get('datatype'), null, true);
		$vs_error = null;
		if (!$o_value_instance->validateSetting($this->getFieldValuesArray(), $ps_setting, $pm_value, $vs_error)) {
			$ps_error = $vs_error;
			return false;
		}

		$va_available_settings = $this->getAvailableSettings();
		$va_properties = $va_available_settings[$ps_setting];

		if (($va_properties['formatType'] == FT_NUMBER) && ($va_properties['displayType'] == DT_CHECKBOXES) && (!$pm_value)) {
			$pm_value = '0';
		}
		$this->opa_element_settings[$ps_setting] = $pm_value;

		return true;
	}
	# ------------------------------------------------------
	/**
	 * Return setting value
	 *
	 * @param string $ps_setting
	 * @param bool $pb_use_cache
	 * @return null|mixed
	 */
	public function getSetting($ps_setting, $pb_use_cache=false) {
		if (is_null($this->get('datatype'))) { return null; }
		$va_available_settings = $this->getAvailableSettings();

		$vs_default = isset($va_available_settings[$ps_setting]['default']) ? $va_available_settings[$ps_setting]['default'] : null;
		return (isset($this->opa_element_settings[$ps_setting]) ? $this->opa_element_settings[$ps_setting] : $vs_default);
	}
	# ------------------------------------------------------
	/**
	 * Returns true if setting code exists for the current element's datatype
	 */
	public function isValidSetting($ps_setting) {
		if (is_null($this->get('datatype'))) { return false; }
		$va_available_settings = $this->getAvailableSettings();
		return (isset($va_available_settings[$ps_setting])) ? true : false;
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

		if (((int)$this->get('parent_id') == 0) && isset($va_properties['validForNonRootOnly']) && $va_properties['validForNonRootOnly']) {
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

				if ($vn_height > 1) { $va_attr['multiple'] = 1; $vs_input_name .= '[]'; }
				$va_opts = array('id' => $vs_input_name, 'width' => $vn_width, 'height' => $vn_height);
				
				
 				if($va_properties['showMediaElementBundles']) {
 				    $va_restrictions = $this->getTypeRestrictions();
 				    
                    $va_select_opts = ['-' => ''];
 				    foreach($va_restrictions as $va_restriction) {
                        // find metadata elements that are either (a) Media or (b) a container that includes a media element
                        if (is_array($va_elements = ca_metadata_elements::getElementsAsList(false, $va_restriction['table_num'], null, true, false, false, null))) {
                            foreach($va_elements as $vn_element_id => $va_element_info) {
                                if ($va_element_info['datatype'] == __CA_ATTRIBUTE_VALUE_MEDIA__) {
                                    if ($va_element_info['parent_id'] > 0) {
                                        $va_select_opts[$va_elements[$va_element_info['hier_element_id']]['display_label']." &gt; ".$va_element_info['display_label']] = $va_elements[$va_element_info['hier_element_id']]['element_code'].".".$va_element_info['element_code'];
                                    } else {
                                        $va_select_opts[$va_element_info['display_label']] = $va_element_info['element_code'];
                                    }
                                }
                            }
                        }
                        $va_properties['options'] = $va_select_opts;
                    } 
                }

				$vm_value = $this->getSetting($ps_setting);

				if(is_array($vm_value)) {
					$va_opts['values'] = $vm_value;
				} else {
					$va_opts['value'] = $vm_value;
					if(!isset($va_opts['value'])) { $va_opts['value'] = -1; }		// make sure default list item is never selected
				}

				// reload settings form when value for this element changes
				if (isset($va_properties['refreshOnChange']) && (bool)$va_properties['refreshOnChange']) {
					$va_attr['onchange'] = "caSetElementsSettingsForm({ {$vs_input_name} : jQuery(this).val() }); return false;";
				}
				
				if($va_properties['useList']) {
                	$t_list = new ca_lists($va_properties['useList']);
					if(!isset($va_opts['value'])) { $va_opts['value'] = -1; }		// make sure default list item is never selected
					$vs_return .= $t_list->getListAsHTMLFormElement($va_properties['useList'], $vs_input_name, $va_attr, $va_opts);
                } else {
					$vs_return .= caHTMLSelect($vs_input_name, $va_properties['options'], $va_attr, $va_opts);
				}
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
		$o_types = Configuration::load(__CA_CONF_DIR__."/attribute_types.conf");

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
	 * Get element list as HTML select
	 * @param string $ps_select_name
	 * @param array $pa_attributes
	 * @param array $pa_options
	 * @return string
	 */
	public static function getElementListAsHTMLSelect($ps_select_name, array $pa_attributes=[], array $pa_options=[]) {
		$pb_root_elements_only = caGetOption('rootElementsOnly', $pa_options, false);
		$pm_table_name_or_num = caGetOption('tableNum', $pa_options, null);
		$pm_type_name_or_id = caGetOption('typeNameOrID', $pa_options, null);
		$pb_add_empty_option = caGetOption('addEmptyOption', $pa_options, false);
		$ps_empty_option = caGetOption('emptyOption', $pa_options, '---');
		$pb_no_containers = caGetOption('noContainers', $pa_options, false);
		$pa_restrict_to_datatypes = caGetOption('restrictToDataTypes', $pa_options, null);
		$pa_add_items = caGetOption('addItems', $pa_options, null);
		$pm_value = caGetOption('value', $pa_options, null);

		$va_elements = self::getElementsAsList($pb_root_elements_only, $pm_table_name_or_num, $pm_type_name_or_id);

		if($pb_add_empty_option) {
			$va_list = [0 => $ps_empty_option];
		} else {
			$va_list = [];
		}
		
		if (is_array($pa_add_items)) { $va_list = array_merge($va_list, $pa_add_items); }

		$va_options = ['contentArrayUsesKeysForValues' => true];
		if($pm_value) {
			$va_options['value'] = $pm_value;
		}

		foreach($va_elements as $va_element) {
			if($pb_no_containers && ($va_element['datatype'] == __CA_ATTRIBUTE_VALUE_CONTAINER__)) { continue; }
			if (is_array($pa_restrict_to_datatypes) && !in_array($va_element['datatype'], $pa_restrict_to_datatypes)) { continue; }

			$va_list[$va_element['element_id']] = (($va_element['parent_id'] > 0) ? self::getElementLabel($va_element['hier_element_id']). " &gt; " : '').$va_element['display_label'] . ' (' . $va_element['element_code'] . ')';
		}
		natsort($va_list);

		return caHTMLSelect($ps_select_name, $va_list, $pa_attributes, $va_options);
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
		$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);
		$vs_cache_key = md5($vn_table_num.'/'.$pm_type_name_or_id.'/'.($pb_root_elements_only ? '1' : '0').'/'.($pb_index_by_element_code ? '1' : '0').serialize($pa_data_types));

		if($pb_use_cache && CompositeCache::contains($vs_cache_key, 'ElementList')) {
			$va_element_list = CompositeCache::fetch($vs_cache_key, 'ElementList');
			if (!$pb_return_stats || isset($va_element_list['ui_counts'])) {
				return $va_element_list;
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

		while($qr_tmp->nextRow()){
			$vn_element_id = $qr_tmp->get('element_id');
			$vs_datatype = $qr_tmp->get('datatype');

			if (is_array($pa_data_types) && !in_array($vs_datatype, $pa_data_types)) { continue; }

			foreach($t_element->getFields() as $vs_field){
				$va_record[$vs_field] = $qr_tmp->get($vs_field);
			}
			$va_record['settings'] = caUnserializeForDatabase($qr_tmp->get('settings'));

			if ($pb_return_stats) {
				$va_record['ui_counts'] = $va_counts_by_attribute[$vs_code = $qr_tmp->get('element_code')];

				if(!$pb_root_elements_only && !$va_record['ui_counts'] && $va_record['parent_id']) {
					$t_element->load($va_record['parent_id']);

					while($t_element->get('parent_id')) {
						$t_element->load($t_element->get('parent_id'));
					}

					$va_record['ui_counts'] = $va_counts_by_attribute[$t_element->get('element_code')];
				}

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

		$vm_return = sizeof($va_return) > 0 ? $va_return : false;;

		// keep track of cache keys so we can flush them when necessary
		$va_element_list_cache_keys = CompositeCache::fetch('cacheKeys', 'ElementList');
		if(!is_array($va_element_list_cache_keys)) { $va_element_list_cache_keys = array(); }
		$va_element_list_cache_keys[] = $vs_cache_key;

		// do cache
		CompositeCache::save('cacheKeys', $va_element_list_cache_keys, 'ElementList');
		CompositeCache::save($vs_cache_key, $vm_return, 'ElementList');

		return $vm_return;
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
		$vn_table_num = Datamodel::getTableNum($pm_table_name_or_num);

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
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $pm_table_name_or_num.'/'.$pm_type_name_or_id);
		if(!caGetOption('noCache', $pa_options, false) && CompositeCache::contains('ElementsSortable') && is_array($va_cached_data = CompositeCache::fetch('ElementsSortable')) && isset($va_cached_data[$vs_cache_key])) {
			return $va_cached_data[$vs_cache_key];
		}
		$va_elements = ca_metadata_elements::getElementsAsList(false, $pm_table_name_or_num, $pm_type_name_or_id);
		if (!is_array($va_elements) || !sizeof($va_elements)) { return array(); }

		$va_sortable_elements = array();

		$vs_key = caGetOption('indexByElementCode', $pa_options, false) ? 'element_code' : 'element_id';
		foreach($va_elements as $vn_id => $va_element) {

			if ((int)$va_element['datatype'] === 0) { continue; }
			if (!isset($va_element['settings']['canBeUsedInSort'])) { $va_element['settings']['canBeUsedInSort'] = true; }
			if ($va_element['settings']['canBeUsedInSort']) {
				$va_element['typeRestrictions'] = array_shift(self::getTypeRestrictionsAsList($va_element['element_code']));

				$va_sortable_elements[$va_element[$vs_key]] = $va_element;
			}
		}
		$va_cached_data[$vs_cache_key] = $va_sortable_elements;
		CompositeCache::save('ElementsSortable', $va_cached_data);
		return $va_sortable_elements;
	}
	# ------------------------------------------------------
	public static function getDataTypeForElementCode($ps_element_code) {
		if(is_numeric($ps_element_code)) { $ps_element_code = self::getElementCodeForId($ps_element_code); }

		$t_element = new ca_metadata_elements();
		if($t_element->load(array('element_code' => $ps_element_code))) {
			return (int) $t_element->get('datatype');
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 * Check if element is used for any recorded values
	 *
	 * @param mixed $pm_element_code_or_id 
	 * @param array $pa_options No options are currently suported.
	 */
	public static function elementIsInUse($pm_element_code_or_id, $pa_options=null) {
		if(!($vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id))) { return null; }
		$t_element = new ca_metadata_elements();
		
		$o_db = new Db();
		$qr_res = $o_db->query("SELECT count(*) c FROM ca_attribute_values WHERE element_id = ? LIMIT 1", [$vn_element_id]);
		
		if ($qr_res->nextRow() && ($qr_res->get('c') > 0)) {
			return true;
		}
		return false;
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
		while($qr_use_counts->nextRow()) {
			if (preg_match('!^ca_attribute_([A-Za-z0-9_\-]+)$!', $qr_use_counts->get('bundle_name'), $va_matches)) {
				if (!($t_table = Datamodel::getInstanceByTableNum($qr_use_counts->get('editor_type'), true))) { continue; }
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
		$t_list = new ca_lists();
		while($qr_restrictions->nextRow()) {
			if (!($t_table = Datamodel::getInstanceByTableNum($qr_restrictions->get('table_num'), true))) { continue; }

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
	 * Get datatype for given element
	 *
	 * @param string|int $pm_element_code_or_id
	 * @return int
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getElementDatatype($pm_element_code_or_id) {
		if(!$pm_element_code_or_id) { return null; }
		if(is_numeric($pm_element_code_or_id)) { $pm_element_code_or_id = (int) $pm_element_code_or_id; }

		if(MemoryCache::contains($pm_element_code_or_id, 'ElementDataTypes')) {
			return MemoryCache::fetch($pm_element_code_or_id, 'ElementDataTypes');
		}

		$vm_return = null;
		if ($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id)) {
			$vm_return = (int) $t_element->get('datatype');
		}

		MemoryCache::save($pm_element_code_or_id, $vm_return, 'ElementDataTypes');
		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get element code for given element_id (or code)
	 * @param mixed $pm_element_id
	 * @return string
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getElementCodeForId($pm_element_id) {
		if(!$pm_element_id) { return null; }
		if(is_numeric($pm_element_id)) { $pm_element_id = (int) $pm_element_id; }

		if(MemoryCache::contains($pm_element_id, 'ElementCodes')) {
			return MemoryCache::fetch($pm_element_id, 'ElementCodes');
		}

		$vm_return = null;
		if (!$t_element = self::getInstance($pm_element_id)) { return null; }

		if($t_element->getPrimaryKey()) {
			$vm_return = $t_element->get('element_code');
		}

		MemoryCache::save($pm_element_id, $vm_return, 'ElementCodes');
		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get element label for given element_id (or code)
	 * @param mixed $pm_element_id
	 * @return string
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getElementLabel($pm_element_id) {
		if(!$pm_element_id) { return null; }
		if(is_numeric($pm_element_id)) { $pm_element_id = (int) $pm_element_id; }

		if(MemoryCache::contains($pm_element_id, 'ElementLabels')) {
			return MemoryCache::fetch($pm_element_id, 'ElementLabels');
		}

		$vm_return = null;
		if (!$t_element = self::getInstance($pm_element_id)) { return null; }

		if($t_element->getPrimaryKey()) {
			$vm_return = $t_element->get('ca_metadata_elements.preferred_labels.name');
		}

		MemoryCache::save($pm_element_id, $vm_return, 'ElementLabels');
		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get element code for the parent of a given element_id (or code). Returns null if given ID has no parent
	 * @param mixed $pm_element_id
	 * @return string|null
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getParentCode($pm_element_id) {
		if(!$pm_element_id) { return null; }
		if(is_numeric($pm_element_id)) { $pm_element_id = (int) $pm_element_id; }

		if(MemoryCache::contains($pm_element_id, 'ElementParentCodes')) {
			return MemoryCache::fetch($pm_element_id, 'ElementParentCodes');
		}

		$vm_return = null;
		$t_element = self::getInstance($pm_element_id);

		if($t_element->getPrimaryKey() && ($vn_parent_id = $t_element->get('parent_id'))) {
			$t_parent = self::getInstance($vn_parent_id);
			$vm_return = $t_parent->get('element_code');
		}

		MemoryCache::save($pm_element_id, $vm_return, 'ElementParentCodes');
		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get element id for given element code (or id)
	 * @param mixed $pm_element_code_or_id
	 * @param array $pa_options Supported options are:
	 *      noCache = Don't use cache. [Default is false]
	 * @return int
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getElementID($pm_element_code_or_id, $pa_options=null) {
		if(!$pm_element_code_or_id) { return null; }
		if(is_numeric($pm_element_code_or_id)) { $pm_element_code_or_id = (int) $pm_element_code_or_id; }

		if(!caGetOption('noCache', $pa_options, false) && MemoryCache::contains($pm_element_code_or_id, 'ElementIDs')) {
			return MemoryCache::fetch($pm_element_code_or_id, 'ElementIDs');
		}

		$vm_return = null;
		$t_element = self::getInstance($pm_element_code_or_id);

		if($t_element && ($t_element->getPrimaryKey())) {
			$vm_return = (int) $t_element->getPrimaryKey();
		}

		MemoryCache::save($pm_element_code_or_id, $vm_return, 'ElementIDs');
		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get hier_element id for given element code (or id)
	 * @param mixed $pm_element_code_or_id
	 * @return int
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getElementHierarchyID($pm_element_code_or_id) {
		if(!$pm_element_code_or_id) { return null; }
		if(is_numeric($pm_element_code_or_id)) { $pm_element_code_or_id = (int) $pm_element_code_or_id; }

		if(MemoryCache::contains($pm_element_code_or_id, 'ElementHierarchyIDs')) {
			return MemoryCache::fetch($pm_element_code_or_id, 'ElementHierarchyIDs');
		}

		$vm_return = null;
		$t_element = self::getInstance($pm_element_code_or_id);

		if($t_element && ($t_element->getPrimaryKey())) {
			$vm_return = (int) $t_element->get('hier_element_id');
		}

		MemoryCache::save($pm_element_code_or_id, $vm_return, 'ElementHierarchyIDs');
		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get element list_id for given element code (or id)
	 * @param mixed $pm_element_code_or_id
	 * @return int
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getElementListID($pm_element_code_or_id) {
		if(!$pm_element_code_or_id) { return null; }
		if(is_numeric($pm_element_code_or_id)) { $pm_element_code_or_id = (int) $pm_element_code_or_id; }

		$vm_return = null;
		if (!($t_element = self::getInstance($pm_element_code_or_id))) { return null; }

		if($t_element->getPrimaryKey()) {
			$vm_return = (int) $t_element->get('list_id');
		}

		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get element settings for given element_id (or code)
	 * @param mixed $pm_element_id
	 * @return string
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getElementSettingsForId($pm_element_id) {
		if(!$pm_element_id) { return null; }
		if(is_numeric($pm_element_id)) { $pm_element_id = (int) $pm_element_id; }

		if(MemoryCache::contains($pm_element_id, 'ElementSettings')) {
			return MemoryCache::fetch($pm_element_id, 'ElementSettings');
		}

		$vm_return = null;
		$t_element = self::getInstance($pm_element_id);

		if($t_element->getPrimaryKey()) {
			$vm_return = $t_element->getSettings();
		}

		MemoryCache::save($pm_element_id, $vm_return, 'ElementSettings');
		return $vm_return;
	}
	# ------------------------------------------------------
	/**
	 * Get ca_metadata_elements instance for given code or ID
	 * @param $pm_element_code_or_id
	 * @return ca_metadata_elements|mixed|null
	 * @throws MemoryCacheInvalidParameterException
	 */
	static public function getInstance($pm_element_code_or_id) {
		if(!$pm_element_code_or_id) { return null; }
		if(is_numeric($pm_element_code_or_id)) { $pm_element_code_or_id = (int) $pm_element_code_or_id; }

		if(MemoryCache::contains($pm_element_code_or_id, 'ElementInstances')) {
			return MemoryCache::fetch($pm_element_code_or_id, 'ElementInstances');
		}

		$t_element = new ca_metadata_elements(is_numeric($pm_element_code_or_id) ? $pm_element_code_or_id : null);

		if (!($vn_element_id = $t_element->getPrimaryKey())) {
			if ($t_element->load(array('element_code' => $pm_element_code_or_id))) {
				MemoryCache::save((int) $t_element->getPrimaryKey(), $t_element, 'ElementInstances');
				MemoryCache::save($t_element->get('element_code'), $t_element, 'ElementInstances');
				return $t_element;
			}
		} else {
			MemoryCache::save((int) $t_element->getPrimaryKey(), $t_element, 'ElementInstances');
			MemoryCache::save($t_element->get('element_code'), $t_element, 'ElementInstances');
			return $t_element;
		}
		return null;
	}
	# ------------------------------------------------------
	#
	# ------------------------------------------------------
	/**
	 * Adds restriction (a binding between a specific item and optional type and the attribute)
	 *
	 * @param int $pn_table_num the number of the table to bind to
	 * @param int $pn_type_id
	 * @param array $pa_settings
	 * @return bool|null
	 */
	public function addTypeRestriction($pn_table_num, $pn_type_id, $pa_settings) {
		if (!($vn_element = $this->getPrimaryKey())) { return null; }		// element must be loaded
		if ($this->get('parent_id')) { return null; }						// element must be root of hierarchy
		if (!is_array($pa_settings)) { $pa_settings = array(); }

		$t_restriction = new ca_metadata_type_restrictions();
		$t_restriction->setMode(ACCESS_WRITE);
		$t_restriction->set('table_num', $pn_table_num);
		$t_restriction->set('type_id', $pn_type_id);
		$t_restriction->set('element_id', $this->getPrimaryKey());
		foreach($pa_settings as $vs_setting => $vs_setting_value) {
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
	 * @param int $pn_table_num the number of the table to bind to
	 * @param null|int $pn_type_id
	 * @return bool|null
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
	 * @return bool|null
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
	 * @param int|null $pn_table_num
	 * @param int|null $pn_type_id
	 * @return array|bool|null
	 */
	public function getTypeRestrictions($pn_table_num=null, $pn_type_id=null) {
		if (!($vn_element_id = $this->getPrimaryKey())) { return null; }		// element must be loaded
		if ($this->get('parent_id')) {
			// element must be root of hierarchy...
			// if not, then use root of hierarchy since all type restrictions are bound to the root
			$vn_element_id = $this->getHierarchyRootID(null);
		}	
		
		$vs_key = caMakeCacheKeyFromOptions(['table_num' => $pn_table_num, 'type_id' => $pn_type_id, 'element_id' => $vn_element_id]);
		
		if (CompositeCache::contains($vs_key, 'ElementTypeRestrictions')) { 
			return CompositeCache::fetch($vs_key, 'ElementTypeRestrictions');
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
		CompositeCache::save($vs_key, $va_restrictions, 'ElementTypeRestrictions');
		return $va_restrictions;
	}
	# ------------------------------------------------------
	/**
	 * Return type restrictions for current element and specified table for display
	 * Display consists of type names in the current locale
	 *
	 * @param int $pn_table_num The table to return restrictions for
	 * @return array An array of type names to which this element is restricted, in the current locale
	 */
	public function getTypeRestrictionsForDisplay($pn_table_num) {
		$va_restrictions = $this->getTypeRestrictions($pn_table_num);

		$t_instance = Datamodel::getInstanceByTableNum($pn_table_num, true);
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
	 * Get HTML form element for this attribute
	 *
	 * @param string $ps_field
	 * @param string|null $ps_format
	 * @param null|array $pa_options
	 * @return string
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
	 * Get presets as html form element
	 * @param array|null $pa_options
	 * @return null|string
	 */
	public function getPresetsAsHTMLFormElement($pa_options=null) {
		if (!($vn_element_id = $this->getPrimaryKey())) { return null; }		// element must be loaded

		$o_presets = Configuration::load(__CA_CONF_DIR__."/attribute_presets.conf");

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
	 * @param string $ps_field_prefix
	 * @param null|array $pa_options
	 * @return null|string
	 */
	public function getPresetsJavascript($ps_field_prefix, $pa_options=null) {
		if (!($vn_element_id = $this->getPrimaryKey())) { return null; }		// element must be loaded

		$o_presets = Configuration::load(__CA_CONF_DIR__."/attribute_presets.conf");

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

			$vs_buf = "\n
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
