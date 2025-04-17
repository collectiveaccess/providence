<?php
/** ---------------------------------------------------------------------
 * app/models/ca_attributes.php : table access class for table ca_attributes
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
require_once(__CA_APP_DIR__.'/models/ca_attribute_values.php');
require_once(__CA_LIB_DIR__.'/Attributes/Attribute.php');
require_once(__CA_LIB_DIR__."/SyncableBaseModel.php");	

BaseModel::$s_ca_models_definitions['ca_attributes'] = array(
 	'NAME_SINGULAR' 	=> _t('attribute'),
 	'NAME_PLURAL' 		=> _t('attributes'),
 	'FIELDS' 			=> array(
		'attribute_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Attribute id', 'DESCRIPTION' => 'Identifier for Attribute'
		),
		'element_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Element id', 'DESCRIPTION' => 'Identifier for Element'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale best describing the origin of this information.')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Table', 'DESCRIPTION' => 'Table to which this attribute is applied.',
				'BOUNDS_VALUE' => array(1,255)
		),
		'row_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Row id', 'DESCRIPTION' => 'Identifier of row to which this attibute is applied.'
		),
		'value_source' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 5,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => '<em>Source</em>', 'DESCRIPTION' => 'Source of data value (for scientific citation).'
		)
	)
);

class ca_attributes extends BaseModel {
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
	protected $TABLE = 'ca_attributes';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'attribute_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array();

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
	protected $ORDER_BY = array();

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
	
	static $s_attribute_cache_size = 1024;
	static $s_get_attributes_cache = array();
	static $s_ca_attributes_element_instance_cache = array();
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
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
     *
     */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		$vn_primary_key = $this->getPrimaryKey();
		$vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);

		if($vn_primary_key && $vn_rc) {
			//$this->removeGUID($vn_primary_key);
		}

		return $vn_rc;
	}
	# -------------------------------------------------------
	/**
	 * Add new attribute value to row.
	 *
	 * @param int $pn_table_num
	 * @param int $pn_row_id
	 * @param mixed $pm_element_code_or_id
	 * @param array $pa_values
	 * @param array $pa_options Options include:
	 *		source = Attribute source identifier. [Default is null]
	 *
	 * @return int Attribute id of newly created attribute, false on error or null if attribute was skipped silently (Eg. empty value).
	 */
	public function addAttribute($pn_table_num, $pn_row_id, $pm_element_code_or_id, $pa_values, $pa_options=null) {
	    if (!is_array($pa_options)) { $pa_options = []; }
		
		global $g_ui_locale_id;
		
		$t_element = ca_attributes::getElementInstance($pm_element_code_or_id);
		
		$vb_web_set_transaction = false;
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$vb_web_set_transaction = true;
			$this->setTransaction($o_trans);
		} else {
			$o_trans = $this->getTransaction();
		}
		
		// create new attribute row
		$this->set('element_id', $t_element->getPrimaryKey());
		
		// Force default of locale-less attributes to current user locale if possible
		if (!isset($pa_values['locale_id']) || !$pa_values['locale_id']) { $pa_values['locale_id'] = $g_ui_locale_id; }
		if (isset($pa_values['locale_id'])) { $this->set('locale_id', $pa_values['locale_id']); }
		
		// @TODO: verify table_num/row_id combo
		$this->set('table_num', $pn_table_num);
		$this->set('row_id', $pn_row_id);
		
		// Save source value
		if($t_element->getSetting('includeSourceData')) {
			$this->set('value_source', caGetOption('source', $pa_options, $pa_values['value_source'] ?? null));
		}
		
		$this->insert($pa_options);
		if ($this->numErrors()) {
			if ($vb_web_set_transaction) {
				$o_trans->rollback();
			}
			
			$vs_errors = join('; ', $this->getErrors());
			$this->clearErrors();
			$this->postError(1971, $vs_errors, 'ca_attributes->addAttribute()');
			return false;
		}
		$t_attr_val = new ca_attribute_values();
		$t_attr_val->purify($this->purify());
		$t_attr_val->setTransaction($o_trans);
		
		$vn_attribute_id = $this->getPrimaryKey();
		$va_elements = $t_element->getElementsInSet();
		
		$vb_dont_create_attribute = true;
		foreach($va_elements as $va_element) {
			if ($va_element['datatype'] == 0) { continue; }	// 0 is always 'container' ...
			
			if(isset($pa_values[$va_element['element_id']])) {
				$vm_value = $pa_values[$va_element['element_id']];
			} else {
				$vm_value = isset($pa_values[$va_element['element_code']]) ? $pa_values[$va_element['element_code']] : null;
			}
			
			if ((isset($va_element['settings']['isDependentValue']) && (bool)$va_element['settings']['isDependentValue']) && (is_null($vm_value))) {
			    $vm_value = caProcessTemplate($va_element['settings']['dependentValueTemplate'], $pa_values);
			}
			
			if (($vb_status = $t_attr_val->addValue($vm_value, $va_element, $vn_attribute_id, array_merge($pa_options, ['t_attribute' => $this]))) === false) {
				$this->postError(1972, join('; ', $t_attr_val->getErrors()), 'ca_attributes->addAttribute()');
				$vb_dont_create_attribute = false;	// this causes an error to be displayed to the user, which is what we want here
				break;
			}
			
			if (!is_null($vb_status)) {
				$vb_dont_create_attribute = false;
			}
		}
		
		if ($vb_dont_create_attribute) {
			//
			// If we're here it means that all attribute values returned null, which indicates that 
			// we should simply skip the attribute without error. This behavior is typically used to allow
			// empty values to pass without complaint.
			//
			$this->delete(true);	// nuke existing ca_attributes record
			if ($vb_web_set_transaction) {
				$o_trans->rollback();
			}
			return null;	// we return null so the caller understands not to throw errors
		}
		
		if ($this->numErrors()) {
			if ($vb_web_set_transaction) {
				$o_trans->rollback();
			} else {
				$va_errors = $this->errors();
				$this->delete(true);
				$this->errors = $va_errors;
			}
			return false;
		}
		
		if ($vb_web_set_transaction) { $o_trans->commit(); }
		
		unset(ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$pn_row_id]);
		return $this->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Edit values for currently loaded attribute.
	 *
	 * @param array $pa_values
	 * @param array $pa_options Options include:
	 *		source = Attribute source identifier. [Default is null]
	 *
	 * @return bool
	 */
	public function editAttribute($pa_values, $pa_options=null) {
	    if (!is_array($pa_options)) { $pa_options = []; }
		global $g_ui_locale_id;
		if (!is_array($pa_options)) { $pa_options = []; }
		
		if (!$this->getPrimaryKey()) { return null; }
		$t_element = ca_attributes::getElementInstance($this->get('element_id'));
		
		$vb_web_set_transaction = false;
		if (!$this->inTransaction()) {
			$o_trans = new Transaction($this->getDb());
			$vb_web_set_transaction = true;
			$this->setTransaction($o_trans);
		} else {
			$o_trans = $this->getTransaction();
		}
		
		// Force default of locale-less attributes to current user locale if possible
		if (!isset($pa_values['locale_id']) || !$pa_values['locale_id']) { $pa_values['locale_id'] = $g_ui_locale_id; }
		if (isset($pa_values['locale_id'])) { $this->set('locale_id', $pa_values['locale_id']); }
		
		// Save source value
		if($t_element->getSetting('includeSourceData')) {
			if($source = caGetOption('source', $pa_options, $pa_values['value_source'] ?? null, ['trim' => true])) {
				$this->set('value_source', $source);
			}	
		}
		$this->update();

		if ($this->numErrors()) {
			if ($vb_web_set_transaction) {
				$o_trans->rollback();
			}
			$vs_errors = join('; ', $this->getErrors());
			$this->clearErrors();
			$this->postError(1971, $vs_errors, 'ca_attributes->editAttribute()');
			return false;
		}
		
		$t_attr_val = new ca_attribute_values();
		$t_attr_val->purify($this->purify());
		$t_attr_val->setTransaction($o_trans);
		
		$va_elements = $t_element->getElementsInSet();

		$va_attr_vals = $this->getAttributeValues();
		foreach($va_attr_vals as $o_attr_val) {
			$vn_element_id = intval($o_attr_val->getElementID());
			if ($t_attr_val->load($o_attr_val->getValueID())) {
			    $va_element = array_shift(array_filter($va_elements, function($e) use ($vn_element_id) { return $e['element_id'] == $vn_element_id; }));
				if(isset($pa_values[$vn_element_id])) {
					$vm_value = $pa_values[$vn_element_id] ?? null;
				} else {
					$vm_value = $pa_values[$o_attr_val->getElementCode()] ?? null;
				}
                if ((isset($va_element['settings']['isDependentValue']) && (bool)$va_element['settings']['isDependentValue']) && (is_null($vm_value))) {
                    $vm_value = caProcessTemplate($va_element['settings']['dependentValueTemplate'], $pa_values);
                }
                
				if ($t_attr_val->editValue($vm_value, $pa_options) === false) {
					$this->postError(1973, join('; ', $t_attr_val->getErrors()), 'ca_attributes->editAttribute()');
				}
				
				foreach($va_elements as $vn_i => $va_element_info) {
					if ($va_element_info['element_id'] == $vn_element_id) {
						unset($va_elements[$vn_i]);
					}
				}
			}
		}
		
		
		$vn_attribute_id = $this->getPrimaryKey();
		
		// Add values that don't already exist (added after the fact?)
		foreach($va_elements as $vn_index => $va_element) {
			if ($va_element['datatype'] == 0) { continue; }		// skip containers
			$vn_element_id = $va_element['element_id'];
			
			if(isset($pa_values[$vn_element_id])) {
				$vm_value = $pa_values[$vn_element_id];
			} else {
				$vm_value = $pa_values[$va_element['element_code']] ?? null;
			}
			
			if ($t_attr_val->addValue($vm_value, $va_element, $vn_attribute_id, array_merge($pa_options, ['t_attribute' => $this])) === false) {
				$this->postError(1972, join('; ', $t_attr_val->getErrors()), 'ca_attributes->editAttribute()');
				break;
			}
		}
		
		if ($this->numErrors()) {
			if ($vb_web_set_transaction) {
				$o_trans->rollback();
			}
			return false;
		}
		
		if ($vb_web_set_transaction) { $o_trans->commit(); }
		
		unset(ca_attributes::$s_get_attributes_cache[(int)$this->get('table_num').'/'.(int)$this->get('row_id')]);
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Remove currently loaded attribute.
	 *
	 * @return bool 
	 */
	public function removeAttribute() {
		if (!$this->getPrimaryKey()) { return null; }
		
		$table_num = (int)$this->get('table_num');
		$row_id = (int)$this->get('row_id');
		$rc = $this->delete(true);
		
		if ($this->numErrors()) {
			$vs_errors = join('; ', $this->getErrors());
			$this->clearErrors();
			$this->postError(1974, $vs_errors, 'ca_attributes->removeAttribute()');
			return false;
		}
		
		unset(ca_attributes::$s_get_attributes_cache[$table_num.'/'.(int)$row_id]);
		return (bool)$rc;
	}
	# ------------------------------------------------------
	/**
	 * Return values for currently loaded attribute.
	 *
	 * @param array $pa_options Options include:
	 *		returnAs = what to return; possible values are:
	 *			values					= an array of attribute values [Default]
	 *			attributeInstance		= an instance of the Attribute class loaded with the attribute value(s)
	 *			count					= the number of values in the attribute
	 *
	 * @return mixed An array, instance of class Attribute or an integer value count depending upon setting of returnAs option. Returns null if no attribute is loaded.
	 */
	public function getAttributeValues($pa_options=null) {
		if (!$this->getPrimaryKey()) { return null; }
		$o_db = $this->getDb();
		$qr_attrs = $o_db->query("
			SELECT *
			FROM ca_attribute_values cav
			INNER JOIN ca_metadata_elements AS cme ON cme.element_id = cav.element_id
			WHERE
				cav.attribute_id = ?
		", (int)$this->getPrimaryKey());
		
		$o_attr = new \CA\Attributes\Attribute($this->getFieldValuesArray());
		while($qr_attrs->nextRow()) {
			$va_raw_row = $qr_attrs->getRow();
			$o_attr->addValueFromRow($va_raw_row);
		}
		
		switch($vs_return_as = caGetOption('returnAs', $pa_options, null)) {
			case 'attributeInstance':
				return $o_attr;
				break;
			case 'count':
				return sizeof($o_attr->getValues());
				break;
			case 'array':
			    $va_ret = [];
			    foreach($o_attr->getValues() as $o_val) {
			        $va_ret[$o_val->getElementCode()] = $o_val->getDisplayValue($pa_options);
			    }
			    return $va_ret;
			    break;
			case 'values':
			default:
				return $o_attr->getValues();	
				break;
		}
	}
	# ------------------------------------------------------
	# Static methods
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getElementInstance($pm_element_code_or_id) {
		if (isset(ca_attributes::$s_ca_attributes_element_instance_cache[$pm_element_code_or_id])) { return ca_attributes::$s_ca_attributes_element_instance_cache[$pm_element_code_or_id]; }
		
		//require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');	// defer inclusion until runtime to ensure baseclasses are already loaded, otherwise you get circular dependencies
		$t_element = new ca_metadata_elements();
		
		if (!is_numeric($pm_element_code_or_id)) {
			if ($t_element->load(array('element_code' => $pm_element_code_or_id))) {
				return ca_attributes::$s_ca_attributes_element_instance_cache[$pm_element_code_or_id] = ca_attributes::$s_ca_attributes_element_instance_cache[$t_element->getPrimaryKey()] = $t_element;
			}
		}
		if ($t_element->load($pm_element_code_or_id)) {
			return ca_attributes::$s_ca_attributes_element_instance_cache[$pm_element_code_or_id] = $t_element;
		} else {
			//$this->postError(1950, _t("Element code or id is invalid"), "ca_attributes::getElementInstance()");
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function attributeHtmlFormElement($pa_element_info, $pa_options=null) {
		if (isset($pa_options['config']) && is_object($pa_options['config'])) {
			$o_config = $pa_options['config'];
		} else {
			$o_config = Configuration::load();
		}
		
		$vn_width = 25;
		$vn_max_length = 255;
		
		$vs_element = \CA\Attributes\Attribute::valueHTMLFormElement($pa_element_info['datatype'], $pa_element_info, $pa_options);
		
		$ps_format = isset($pa_options['format']) ? $pa_options['format'] : null;
		
		$vs_label = isset($pa_options['label']) ? trim($pa_options['label']) : '';
		$vs_description = isset($pa_options['description']) ? $pa_options['description'] : '';
		if (isset($pa_options['field_errors']) && is_array($pa_options['field_errors']) && sizeof($pa_options['field_errors'])) {
			$ps_format = $o_config->get('form_element_error_display_format');
			$va_field_errors = array();
			foreach($pa_options['field_errors'] as $o_e) {
				$va_field_errors[] = $o_e->getErrorDescription();
			}
			$vs_errors = join('; ', $va_field_errors);
		} else {
			if (!$ps_format) {
				if ($vs_label) {
					$ps_format = $o_config->get('form_element_display_format');
				} else {
					$ps_format = $o_config->get('form_element_display_format_without_label');
				}
			}
			$vs_errors = '';
		}

		$ps_formatted_element = str_replace("^LABEL", "<span class='_attribute_value_".$pa_element_info['element_code']."'>{$vs_label}</span>", $ps_format);
		$ps_formatted_element = str_replace("^ELEMENT", $vs_element, $ps_formatted_element);
		$ps_formatted_element = str_replace("^DESCRIPTION", "", $ps_formatted_element);
		$ps_formatted_element = str_replace("^EXTRA", "", $ps_formatted_element);
		$ps_formatted_element = str_replace("^BUNDLECODE", "", $ps_formatted_element);
	
		if ($vs_description) {
			// don't use TooltipManager to make sure the tooltip is also displayed when this element is added dynamically (via "add" button)
			//TooltipManager::add('#_attribute_value_'.$pa_element_info['element_code'], "<h3>".$vs_label."</h3>".$vs_description);

			$ps_formatted_element .= "\n".caGetTooltipJS(array('._attribute_value_'.$pa_element_info['element_code'] => $vs_description));
		}
		return $ps_formatted_element;
	}
	# ------------------------------------------------------
	/**
	 * Retrieve attributes attached to specified row_id in specified table
	 * Returns a list (indexed array) of Attribute objects.
	 *
	 * @param $po_db Db Database connection object
	 * @param $pn_table_num int The table number of the table to fetch attributes for
	 * @param $pa_row_ids array List of row_ids to fetch attributes for
	 * @param $pa_options array Optional array of options. Supported options include:
	 *			resetCache = Clear cache before prefetch. [Default is false]
	 *
	 * @return boolean Always return true
	 */
	static public function prefetchAttributes($po_db, $pn_table_num, $pa_row_ids, $pa_element_ids, $pa_options=null) {
		if(!sizeof($pa_row_ids)) { return true; }
		if(!is_array($pa_element_ids) || !sizeof($pa_element_ids)) { return true; }
		
		if (caGetOption('resetCache', $pa_options, false)) {
			ca_attributes::$s_get_attributes_cache = array();
		}
		// Make sure the element_id list looks like element_ids and does not have blanks
		$va_element_ids = array();
		foreach($pa_element_ids as $vn_i => $vn_element_id) {
			if ($vn_element_id) { $va_element_ids[] = $vn_element_id; }
		}
		if(!is_array($va_element_ids) || !sizeof($va_element_ids)) { return true; }

		$qr_attrs = $po_db->query("
			SELECT 
				caa.attribute_id, caa.locale_id, caa.element_id element_set_id, caa.row_id, caa.value_source,
				caav.value_id, caav.item_id, caav.value_longtext1, caav.value_longtext2,
				caav.value_decimal1, caav.value_decimal2, caav.value_integer1, caav.value_blob,
				caav.value_sortable,
				caav.element_id
			FROM ca_attributes caa
			INNER JOIN ca_attribute_values AS caav ON caa.attribute_id = caav.attribute_id
			WHERE
				(caa.table_num = ?) AND (caa.row_id IN (?)) AND (caa.element_id IN (?))
			ORDER BY
				caa.attribute_id
		", array((int)$pn_table_num, $pa_row_ids, $va_element_ids));
		
		if ($po_db->numErrors()) {
			return false;
		}
		$va_attrs = array();
		$vn_last_attribute_id = $vn_last_row_id = null;
		
		$vn_val_count = 0;
		$o_attr = $vn_last_element_id = null; 
		while($qr_attrs->nextRow()) {
			$va_raw_row = $qr_attrs->getRow();
			$va_raw_row['element_code'] = ca_metadata_elements::getElementCodeForID($va_raw_row['element_id']);
			$va_raw_row['datatype'] = ca_metadata_elements::getElementDatatype($va_raw_row['element_id']);
			
			if ($vn_last_attribute_id != $va_raw_row['attribute_id']) {
				if ($vn_last_attribute_id && $vn_last_row_id) {
					$va_attrs[$vn_last_row_id][$vn_last_element_id][] = $o_attr;
					$vn_val_count = 0;
				}
				$vn_last_attribute_id = $va_raw_row['attribute_id'];
				$vn_last_row_id = $va_raw_row['row_id'];
				$vn_last_element_id = $va_raw_row['element_set_id'];
				
				// when creating the attribute you want element_id = to the "set" id (ie. the element_id in the ca_attributes row) so we overwrite
				// the element_id of the ca_attribute_values row before we pass the array to Attribute() below
				$o_attr = new \CA\Attributes\Attribute(array_merge($va_raw_row, array('element_id' => $va_raw_row['element_set_id'])));
			}
			
			$o_attr->addValueFromRow($va_raw_row);
			$vn_val_count++;
		}
		if ($vn_val_count > 0) {
			$va_attrs[$vn_last_row_id][$vn_last_element_id][] = $o_attr;
		}
		
		$va_row_id_with_no_attributes = array_flip($pa_row_ids);
		foreach($va_attrs as $vn_row_id => $va_attrs_by_element) {
			foreach($va_attrs_by_element as $vn_element_id => $va_attrs_for_element) {
				unset($va_row_id_with_no_attributes[$vn_row_id]);
				ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$vn_row_id][(int)$vn_element_id] = $va_attrs_for_element;
				// Limit cache size
				if (sizeof(ca_attributes::$s_get_attributes_cache) > ca_attributes::$s_attribute_cache_size) {
					if (($vn_splice_length = ceil(sizeof(ca_attributes::$s_get_attributes_cache) - ca_attributes::$s_attribute_cache_size + (ca_attributes::$s_attribute_cache_size * 0.5))) > ca_attributes::$s_attribute_cache_size) {
						$vn_splice_length = ca_attributes::$s_attribute_cache_size;
					}
					
					array_splice(ca_attributes::$s_get_attributes_cache, 0, $vn_splice_length);
				}
			}
			
			foreach($pa_element_ids as $vn_id) {
				if(!isset(ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$vn_row_id][$vn_id])) {
					ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$vn_row_id][$vn_id] = false;
				}
			}
		}
		
		// Fill in cache entries for row_ids with no attributes as an empty array
		// to avoid repeated checks for values that don't exist
		foreach($va_row_id_with_no_attributes as $vn_row_id => $vn_dummy) {
			foreach($va_element_ids as $vn_element_id) {
				ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$vn_row_id][(int)$vn_element_id] = array();
			}
				
			// Limit cache size
			if (sizeof(ca_attributes::$s_get_attributes_cache) > ca_attributes::$s_attribute_cache_size) {
				if (($vn_splice_length = ceil(sizeof(ca_attributes::$s_get_attributes_cache) - ca_attributes::$s_attribute_cache_size + (ca_attributes::$s_attribute_cache_size * 0.5))) > ca_attributes::$s_attribute_cache_size) {
					$vn_splice_length = ca_attributes::$s_attribute_cache_size;
				}
				
				array_splice(ca_attributes::$s_get_attributes_cache, 0, $vn_splice_length);
			}
		}
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Retrieve attributes attached to specified row_id in specified table
	 * Returns a list (indexed array) of Attribute objects.
	 *
	 * @param Db $po_db Db() instance to use for database access
	 * @param int $pn_table_num Table number of table to fetch attributes values for
	 * @param int $pn_row_id Row to fetch attribute values for
	 * @param array $pa_element_ids Array of element_ids to fetch values for
	 * @param array $pa_options Options include:
	 *		noCache = Don't use cached attribute values. [Default is false]
	 *
	 * @return array
	 */
	static public function getAttributes($po_db, $pn_table_num, $pn_row_id, $pa_element_ids, $pa_options=null) {
		$pb_no_cache = (isset($pa_options['noCache']) && $pa_options['noCache']);
		if ($pb_no_cache) { $pa_options['resetCache'] = true; }
		
		$va_element_ids = array();
		foreach($pa_element_ids as $vn_element_id) {
			if (!isset(ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$pn_row_id][$vn_element_id]) || $pb_no_cache) {
				$va_element_ids[] = $vn_element_id;
			}
		}
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (sizeof($va_element_ids)) {
			if (!(ca_attributes::prefetchAttributes($po_db, $pn_table_num, array($pn_row_id), $va_element_ids, $pa_options))) {
				return null;
			}
		}
		return ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$pn_row_id] ?? null;
	}
	# ------------------------------------------------------
	/**
	 * Retrieves attribute value for a list of rows
	 *
	 * @return array Array of values indexed on row_id, then locale_id and finally an index (to accommodate repeating values)
	 */
	static public function getRawAttributeValuesForIDs($po_db, $pn_table_num, $pa_row_ids, $pn_element_id, $pa_options=null) {
		$qr_attrs = $po_db->query("
			SELECT 
				caa.attribute_id, caa.locale_id, caa.element_id element_set_id, caa.row_id, caa.value_source,
				caav.value_id, caav.item_id, caav.value_longtext1, caav.value_longtext2,
				caav.value_decimal1, caav.value_decimal2, caav.value_integer1, caav.value_blob,
				caav.value_sortable,
				cme.element_id, cme.datatype, cme.settings, cme.element_code
			FROM ca_attributes caa
			INNER JOIN ca_attribute_values AS caav ON caa.attribute_id = caav.attribute_id
			INNER JOIN ca_metadata_elements AS cme ON cme.element_id = caav.element_id
			WHERE
				(caa.table_num = ?) AND (caa.row_id IN (?)) AND (cme.element_id = ?)
			ORDER BY
				caa.attribute_id
		", array((int)$pn_table_num, $pa_row_ids, (int)$pn_element_id));
		if ($po_db->numErrors()) {
			return false;
		}
		
		$va_attrs = array();
		
		while($qr_attrs->nextRow()) {
			$va_raw_row = $qr_attrs->getRow();
			$va_attrs[$va_raw_row['row_id']][$va_raw_row['attribute_id']][$va_raw_row['value_id']] = $va_raw_row;
		}
		return $va_attrs;
	}
	# ------------------------------------------------------
	/**
	 * Return number of attributes with specified element_id attached to specified row in specified table. By
	 * default only non-blank attributes are counted. Set the includeBlanks option to get a count of all values.
	 *
	 * @param Db $po_db Db() instance to use for database access
	 * @param int $pn_table_num Table number of table attributes to count are attached to
	 * @param int $pn_row_id row_id of row attributes to count are attached to
	 * @param int $pn_element_id Metadata element of attribute to count
	 * @param array $pa_options Options include:
	 *		includeBlanks = include blank values in count. [Default is false]
	 *
	 * @return int number of attributes with specified element_id attached to specified row
	 */
	static public function getAttributeCount($po_db, $pn_table_num, $pn_row_id, $pn_element_id, $pa_options=null) {
		$pb_include_blanks = caGetOption('includeBlanks', $pa_options, false);
		$qr_attrs = $po_db->query("
			SELECT count(distinct caa.attribute_id) c
			FROM ca_attributes caa, ca_attribute_values cav
			WHERE
				(cav.attribute_id = caa.attribute_id) AND
				(caa.table_num = ?) AND (caa.row_id = ?) AND (caa.element_id = ?)
				".(!$pb_include_blanks ? ("AND (cav.item_id IS NOT NULL OR cav.value_longtext1 IS NOT NULL OR cav.value_decimal1 IS NOT NULL OR cav.value_integer1 IS NOT NULL OR cav.value_blob IS NOT NULL)") : "")."
		", (int)$pn_table_num, (int)$pn_row_id, (int)$pn_element_id);
		if ($po_db->numErrors()) {
			//$this->errors = $po_db->errors;
			return false;
		}
		
		$qr_attrs->nextRow();
		
		return (int)$qr_attrs->get('c');
	}
	# ------------------------------------------------------
	/**
	 * Retrieve attributes attached to specified row_id in specified table
	 * Returns a list (indexed array) of Attribute objects.
	 */
	static public function getReferencedAttributes($po_db, $pn_table_num, $pa_reference_limit_ids, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vs_element_sql = '';
		if (isset($pa_options['element_id']) && $pa_options['element_id']) {
			$vs_element_sql = ' AND (cme.element_id = '.intval($pa_options['element_id']).')';
		}
		
		$vs_reference_id_limit_sql = '';
		if (sizeof($pa_reference_limit_ids)) {
			$vs_reference_id_limit_sql = "AND (caa.row_id IN (".join(', ', $pa_reference_limit_ids)."))";
		}
		$qr_attrs = $po_db->query("
			SELECT 
				caa.attribute_id, caa.locale_id, caa.element_id element_set_id,
				caav.value_id, caav.item_id, caav.value_longtext1, caav.value_longtext2,
				caav.value_decimal1, caav.value_decimal2, caav.value_integer1,
				caav.value_sortable,
				cme.element_id, cme.datatype, cme.settings, cme.element_code
			FROM ca_attributes caa
			INNER JOIN ca_attribute_values AS caav ON caa.attribute_id = caav.attribute_id
			INNER JOIN ca_metadata_elements AS cme ON cme.element_id = caav.element_id
			WHERE
				(caa.table_num = ?) {$vs_reference_id_limit_sql} {$vs_element_sql}
			ORDER BY
				caa.attribute_id
		", (int)$pn_table_num);
		if ($po_db->numErrors()) {
			//$this->errors = $po_db->errors;
			return false;
		}
		$va_attrs = array();
		$vn_last_attribute_id = null;
		
		$o_attr = null;
		while($qr_attrs->nextRow()) {
			$va_raw_row = $qr_attrs->getRow();
			if ($vn_last_attribute_id != $va_raw_row['attribute_id']) {
				if ($vn_last_attribute_id) {
					$vs_key = join(';', array_values($o_attr->getDisplayValues()));
					if(is_array($va_attrs[$vs_key])) {
						$va_attrs[$vs_key]['cnt']++;
					} else {
						$va_attrs[$vs_key] = array(
							'attribute' => $o_attr,
							'cnt' => 1
						);
					}
				}
				$vn_last_attribute_id = $va_raw_row['attribute_id'];
				
				// when creating the attribute you want element_id = to the "set" id (ie. the element_id in the ca_attributes row) so we overwrite
				// the element_id of the ca_attribute_values row before we pass the array to Attribute() below
				$o_attr = new \CA\Attributes\Attribute(array_merge($va_raw_row, array('element_id' => $va_raw_row['element_set_id'])));
			}
			$o_attr->addValueFromRow($va_raw_row);
			
		}
		if ($vn_last_attribute_id) {
			$vs_key = join(';', array_values($o_attr->getDisplayValues()));
			
			if (is_array($va_attrs[$vs_key])) {
				$va_attrs[$vs_key]['cnt']++;
			} else {
				$va_attrs[$vs_key] = array(
					'attribute' => $o_attr,
					'cnt' => 1
				);
			}
		}
		return $va_attrs;
	}
	# ------------------------------------------------------
	/**
	 * Retrieves attribute value for a list of rows
	 *
	 * @return array Array of values indexed on row_id, then locale_id and finally an index (to accommodate repeating values)
	 */
	static public function getAttributeValueForIDs($po_db, $pn_table_num, $pa_row_ids, $pn_element_id, $pa_options=null) {
		$vb_is_cached = true;
		foreach($pa_row_ids as $vn_row_id) {
			if (!is_array(ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$vn_row_id][$pn_element_id])) {
				$vb_is_cached = false;
				break;
			}
		}
		
		if (!$vb_is_cached) {
			if (!(ca_attributes::prefetchAttributes($po_db, $pn_table_num, $pa_row_ids, array($pn_element_id)))) {
				return null;
			}
		}
		$va_values = array();
		foreach($pa_row_ids as $vn_i => $vn_row_id) {
			foreach(ca_attributes::$s_get_attributes_cache[(int)$pn_table_num.'/'.(int)$vn_row_id] as $va_elements) {
				foreach($va_elements as $vn_j => $o_attr) {
					if ((int)$o_attr->getElementID() === (int)$pn_element_id) {
						$va_attr_values = $o_attr->getValues();
						$vn_locale_id = $o_attr->getLocaleID();
						foreach($va_attr_values as $va_attr_value) {
							$va_values[$vn_row_id][$vn_locale_id][] = $va_attr_value->getDisplayValue($pa_options);
						}
					}
				}
			}
		}
		
		return $va_values;
	}
	# ------------------------------------------------------
	/**
	 * Get code for element
	 * @return string
	 * @throws MemoryCacheInvalidParameterException
	 */
	public function getElementCode() {
		if(!$this->getPrimaryKey()) { return false; }

		if(MemoryCache::contains($this->getPrimaryKey(), 'AttributeToElementCodeCache')) {
			return MemoryCache::fetch($this->getPrimaryKey(), 'AttributeToElementCodeCache');
		}

		$t_element = new ca_metadata_elements($this->get('element_id'));
		$vs_element_code = $t_element->get('element_code');

		MemoryCache::save($this->getPrimaryKey(), $vs_element_code, 'AttributeToElementCodeCache');
		return $vs_element_code;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function getRowInstance() {
		if(!$this->getPrimaryKey()) { return false; }

        if (($t_instance = Datamodel::getInstanceByTableNum($this->get('table_num'), true)) && ($t_instance->load($this->get('row_id')))) {
            return $t_instance;
        }
        
		return null;
	}
 	# ------------------------------------------------------
 	/**
 	 * Return attribute instance for a value_id, avoiding intermediate load of the attribute value.
 	 *
 	 * @param int $pn_value_id
 	 * @param array $pa_options Options include:
 	 *      transaction = transaction to perform queries within. [Default is null]
 	 *      db = use provided database connection. [Default is null; use new connection which may or may not be the most recently created one.]
 	 *
 	 *  @return ca_attributes instance or null if attribute cannot be loaded
 	 */
 	public static function getAttributeForValueID($pn_value_id, $pa_options=null) { 		
        $o_trans = caGetOption('transaction', $pa_options, null);	
        $o_db = caGetOption('db', $pa_options, null);
        
        if ((!$o_db) && ($o_trans)) {
            $o_db = $o_trans->getDb();
        } else {
            $o_db = new Db();
        }
        
        $qr_res = $o_db->query("SELECT attribute_id FROM ca_attribute_values WHERE value_id = ?", [(int)$pn_value_id]);
        while($qr_res->nextRow()) {
            return new ca_attributes($qr_res->get('attribute_id'));
        }
		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Return row instance for a value_id, avoiding intermediate loads of the attribute and attribute value.
 	 *
 	 * @param int $pn_value_id
 	 * @param array $pa_options Options include:
 	 *      transaction = transaction to perform queries within. [Default is null]
 	 *      db = use provided database connection. [Default is null; use new connection which may or may not be the most recently created one.]
 	 *
 	 *  @return BaseModel or null if row cannot be loaded
 	 */
 	public static function getRowInstanceForValueID($pn_value_id, $pa_options=null) { 		
        $o_trans = caGetOption('transaction', $pa_options, null);	
        $o_db = caGetOption('db', $pa_options, null);
        
        if ((!$o_db) && ($o_trans)) {
            $o_db = $o_trans->getDb();
        } else {
            $o_db = new Db();
        }
        
        $qr_res = $o_db->query("
            SELECT ca.attribute_id, ca.table_num, ca.row_id
            FROM ca_attribute_values cav
            INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
            WHERE cav.value_id = ?
        ", [(int)$pn_value_id]);
        
        while($qr_res->nextRow()) {
            if (($t_instance = Datamodel::getInstanceByTableNum($qr_res->get('table_num'), true)) && ($t_instance->load($qr_res->get('row_id')))) {
                return $t_instance;
            }
        }
		return null;
 	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function getTableNumForAttribute($pn_attribute_id, $po_db=null) {
		$o_db = $po_db ? $po_db : new Db();
		
		if ($qr_res = $o_db->query("SELECT table_num FROM ca_attributes WHERE attribute_id = ?", [$pn_attribute_id])) {
			if ($qr_res->nextRow()) {
				return $qr_res->get('table_num');
			}
		}
		return null;
	}
	# -------------------------------------------------------
    /**
     * Return maximum size of attribute cache
     *
     * @return int
     */
	public static function attributeCacheSize() {
		return self::$s_attribute_cache_size;
	}
	# ------------------------------------------------------
}
