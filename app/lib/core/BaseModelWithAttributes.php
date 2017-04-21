<?php
/** ---------------------------------------------------------------------
 * app/lib/core/BaseModelWithAttributes.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2016 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/core/ITakesAttributes.php');
 require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
 require_once(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser.php');
 require_once(__CA_APP_DIR__.'/models/ca_attributes.php');
 require_once(__CA_APP_DIR__.'/models/ca_attribute_values.php');
 require_once(__CA_APP_DIR__.'/models/ca_metadata_type_restrictions.php');
 require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AuthorityAttributeValue.php');

 
	class BaseModelWithAttributes extends BaseModel implements ITakesAttributes {
		# ------------------------------------------------------------------
		static $s_applicable_element_code_cache = array();
		static $s_element_label_cache = array();
		# ------------------------------------------------------------------
		protected $opa_failed_attribute_inserts;
		protected $opa_failed_attribute_updates;
		
		protected $opa_attributes_to_add;
		protected $opa_attributes_to_edit;
		protected $opa_attributes_to_remove;
		
		
		# ------------------------------------------------------------------
		public function __construct($pn_id=null) {
			parent::__construct($pn_id);
			$this->init();
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function clear() {
			parent::clear();
			$this->init();
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function init() {
			$this->opa_failed_attribute_inserts = array();
			$this->opa_failed_attribute_updates = array();
			$this->_initAttributeQueues();
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		private function _initAttributeQueues() {
			$this->opa_attributes_to_add = array();
			$this->opa_attributes_to_edit = array();
			$this->opa_attributes_to_remove = array();
		}
		# ------------------------------------------------------------------
		/**
		 * create an attribute linked to the current row using values in $pa_values
		 */
		public function addAttribute($pa_values, $pm_element_code_or_id, $ps_error_source=null, $pa_options=null) {
			require_once(__CA_APP_DIR__.'/models/ca_metadata_elements.php');
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) { return false; }
			if ($t_element->get('parent_id') > 0) { return false; }
			$vn_element_id = $t_element->getPrimaryKey();
			
			if (!is_array($pa_values)) { 
				// Try to make something of a non-array value (maybe this is a terrible idea?)
				$pa_values = array($t_element->get('element_code') => $pa_values);
			}
			
			if (!$ps_error_source) { $ps_error_source = $this->tableName().'.'.$t_element->get('element_code'); }
			
			// check restriction min/max settings
			$t_restriction = $t_element->getTypeRestrictionInstanceForElement($this->tableNum(), $this->getTypeID());
			if (!$t_restriction) { return null; }		// attribute not bound to this type
			$vn_min = $t_restriction->getSetting('minAttributesPerRow');
			$vn_max = $t_restriction->getSetting('maxAttributesPerRow');
			
			$vn_add_cnt = 0;
			foreach($this->opa_attributes_to_add as $va_attr) {
				if (ca_metadata_elements::getElementID($va_attr['element']) == $vn_element_id) {
					$vn_add_cnt++;
				}
			}
			$vn_del_cnt = 0;
			foreach($this->opa_attributes_to_remove as $va_attr) {
				if ($va_attr['element_id'] == $vn_element_id) {
					$vn_del_cnt++;
				}
			}
			
			if (!caGetOption('dontCheckMinMax', $pa_options, false)) { 
				$vn_count = $this->getAttributeCountByElement($vn_element_id)  + $vn_add_cnt - $vn_del_cnt;
				if (($vn_max > 0) && $vn_count >= $vn_max) { 
					if (caGetOption('showRepeatCountErrors', $pa_options, false)) {
						$this->postError(1965, ($vn_max == 1) ? _t('Cannot add another value; only %1 value is allowed', $vn_max) : _t('Cannot add another value; only %1 values are allowed', $vn_max), 'BaseModelWithAttributes->addAttribute()', $ps_error_source);
					}
					return null; 
				}	// # attributes is at upper limit
			}
			
			$this->opa_attributes_to_add[] = array(
				'values' => $pa_values,
				'element' => $pm_element_code_or_id,
				'error_source' => $ps_error_source,
				'options' => $pa_options
			);
			$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_element_id] = true;
			
			return true;
		}
		# ------------------------------------------------------------------
		// create an attribute linked to the current row using values in $pa_values
		public function _addAttribute($pa_values, $pm_element_code_or_id, $po_trans=null, $pa_info=null) {
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) { return false; }
			if ($t_element->get('parent_id') > 0) { return false; }
			
			$t_attr = new ca_attributes();
			$t_attr->purify($this->purify());
			if ($po_trans) { $t_attr->setTransaction($po_trans); }
			$vn_attribute_id = $t_attr->addAttribute($this->tableNum(), $this->getPrimaryKey(), $t_element->getPrimaryKey(), $pa_values, $pa_info['options']);
			if ($t_attr->numErrors()) {
				foreach($t_attr->errors as $o_error) {
					$this->postError($o_error->getErrorNumber(), $o_error->getErrorDescription(), $o_error->getErrorContext(), $pa_info['error_source']);
				}
				return false;
			}
						
			return $vn_attribute_id;
		}
		# ------------------------------------------------------------------
		public function editAttribute($pn_attribute_id, $pm_element_code_or_id, $pa_values, $ps_error_source=null, $pa_options=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			$t_attr->purify($this->purify());
			if (!$t_attr->getPrimaryKey()) { return false; }
			$vn_attr_element_id = $t_attr->get('element_id');
			
			$va_attr_values = $t_attr->getAttributeValues();
			
			if (sizeof($va_attr_values) != (sizeof($pa_values) - 1)) {		// -1 to remove locale_id which is not in attribute values array
				// Value arrays are different sizes - probably means the elements in the set have been reconfigured (sub-elements added or removed)
				// so we need to force a save.
				$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id] = true;
			} else {
				// Have any of the values changed?
				foreach($va_attr_values as $o_attr_value) {
					$vn_element_id = $o_attr_value->getElementID();
					$vs_element_code = ca_metadata_elements::getElementCodeForId($vn_element_id);
					
					if (
						(
							isset($pa_values[$vn_element_id]) && ($pa_values[$vn_element_id] !== $o_attr_value->getDisplayValue()) 
							&& 
							!(($pa_values[$vn_element_id] == "") && (is_null($o_attr_value->getDisplayValue())))
						)
						||
						(
							isset($pa_values[$vs_element_code]) && ($pa_values[$vs_element_code] !== $o_attr_value->getDisplayValue()) 
							&&
							!(($pa_values[$vs_element_code] == "") && (is_null($o_attr_value->getDisplayValue())))
						)
					) {
						$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id] = true;
						break;
					}
				}
			}
			
			if (!$ps_error_source) { $ps_error_source = $this->tableName().'.'.ca_metadata_elements::getElementCodeForId($pm_element_code_or_id); }
			
			if (isset($this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id]) && $this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id]) {
				$this->opa_attributes_to_edit[] = array(
					'values' => $pa_values,
					'attribute_id' => $pn_attribute_id,
					'element' => $pm_element_code_or_id,
					'error_source' => $ps_error_source.'/'.$pn_attribute_id,
					'options' => $pa_options
				);
			}
		}
		# ------------------------------------------------------------------
		// edit attribute from current row
		public function _editAttribute($pn_attribute_id, $pa_values, $po_trans=null, $pa_info=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			$t_attr->purify($this->purify());
			if ($po_trans) { $t_attr->setTransaction($po_trans); }
			if ((!$t_attr->getPrimaryKey()) || ($t_attr->get('table_num') != $this->tableNum()) || ($this->getPrimaryKey() != $t_attr->get('row_id'))) {
				$this->postError(1969, _t('Can\'t edit invalid attribute'), 'BaseModelWithAttributes->editAttribute()', $pa_info['error_source']);
				return false;
			}
			
			if (!$t_attr->editAttribute($pa_values, $pa_info['options'])) {
				foreach($t_attr->errors as $o_error) {
					$this->postError($o_error->getErrorNumber(), $o_error->getErrorDescription(), $o_error->getErrorContext(), $pa_info['error_source']);
				}
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Replaces first attribute value with specified values; will add attribute value if no attributes are defined 
		 * This is handy for doing editing on non-repeating attributes
		 */
		public function replaceAttribute($pa_values, $pm_element_code_or_id, $ps_error_source=null) {
			$va_attrs = $this->getAttributesByElement($pm_element_code_or_id);
			
			if (sizeof($va_attrs)) {
				return $this->editAttribute(
					$va_attrs[0]->getAttributeID(),
					$pm_element_code_or_id, $pa_values, $ps_error_source
				);
			} else {
				return $this->addAttribute(
					$pa_values, $pm_element_code_or_id, $ps_error_source
				);
			}
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 *
		 */
		public function removeAttribute($pn_attribute_id, $ps_error_source=null, $pa_extra_info=null, $pa_options=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			$t_attr->purify($this->purify());
			if (!$t_attr->getPrimaryKey()) { return false; }
			$vn_element_id = (int)$t_attr->get('element_id');
			
			if (!$ps_error_source) { $ps_error_source = $this->tableName().'.'.ca_metadata_elements::getElementCodeForId($vn_element_id); }
			
			$vn_add_cnt = 0;
			if (isset($pa_extra_info['pending_adds']) && is_array($pa_extra_info['pending_adds'])) {
				$vn_add_cnt = sizeof($pa_extra_info['pending_adds']);
			} else {
				foreach($this->opa_attributes_to_add as $va_attr) {
					if (ca_metadata_elements::getElementID($va_attr['element']) == $vn_element_id) {
						$vn_add_cnt++;
					}
				}
			}
			if (!($t_element = ca_metadata_elements::getInstance($t_attr->get('element_id')))) { return false; }
			
			if ($vb_require_value = (bool)$t_element->getSetting('requireValue')) {
				$pa_options['dontCheckMinMax'] = false;
			}
			
			// check restriction min/max settings
			if (!caGetOption('dontCheckMinMax', $pa_options, false)) {
				$t_restriction = $t_element->getTypeRestrictionInstanceForElement($this->tableNum(), $this->getTypeID());
				if (!$t_restriction) { return null; }		// attribute not bound to this type
				if ((($vn_min = $t_restriction->getSetting('minAttributesPerRow')) == 0) && $vb_require_value) {
					$vn_min = 1;
				}
				$vn_max = $t_restriction->getSetting('maxAttributesPerRow');
				
				$vn_del_cnt = 0;
				foreach($this->opa_attributes_to_remove as $va_attr) {
					if ($va_attr['element_id'] == $vn_element_id) {
						$vn_del_cnt++;
					}
				}
				
				$vn_count = $this->getAttributeCountByElement($t_element->getPrimaryKey(), ['includeBlanks' => true])  + $vn_add_cnt - $vn_del_cnt;
				if ($vn_count <= $vn_min) { 
					if (caGetOption('showRepeatCountErrors', $pa_options, false)) {
						$this->postError(1967, ($vn_min == 1) ? _t('Cannot remove value; at least %1 value is required', $vn_min) : _t('Cannot remove value; at least %1 values are required', $vn_min), 'BaseModelWithAttributes->removeAttribute()', $ps_error_source);
					}
					return null; 
				}	// # attributes is at lower limit
			}
			
			$this->opa_attributes_to_remove[] = array(
				'attribute_id' => $pn_attribute_id,
				'error_source' => $ps_error_source,
				'element_id' => $t_attr->get('element_id')
			);
			
			$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$t_attr->get('element_id')] = true;
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * remove attribute from current row
		 */
		public function _removeAttribute($pn_attribute_id, $po_trans=null, $pa_options=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			$t_attr->purify($this->purify());
			if ($po_trans) { $t_attr->setTransaction($po_trans); }
			if ((!$t_attr->getPrimaryKey()) || ($t_attr->get('table_num') != $this->tableNum()) || ($this->getPrimaryKey() != $t_attr->get('row_id'))) {
				$this->postError(1969, _t('Can\'t edit invalid attribute'), 'BaseModelWithAttributes->editAttribute()', $pa_options['error_source']);
				return false;
			}
			if (!$t_attr->removeAttribute()) {
				foreach($t_attr->errors as $o_error) {
					$this->postError($o_error->getErrorNumber(), $o_error->getErrorDescription(), $o_error->getErrorContext(), $pa_options['error_source']);
				}
				return false;
			}
			//$this->setFieldValuesArray($this->addAttributesToFieldValuesArray());
			return true;
		}
		# ------------------------------------------------------------------
		/** 
		 * Removes all attributes from current row of specified element, or all attributes regardless of 
		 * element if $pm_element_code_or_id is omitted
		 *
		 * Note that this method respects the minAttributesPerRow type restriction setting and will only delete attributes until the minimum permissible
		 * number of reached. If you wish to remove *all* attributes, ignoring any minAttributesPerRow constraints, pass the 'force' option set to true. 
		 *
		 * @param mixed $pm_element_code_or_id
		 * @param array $pa_options Options are:
		 *		force = if set to true all attributes are removed, even if there is a non-zero minAttributesPerRow constraint set
		 * @return bool True on success, false on error
		 */
		public function removeAttributes($pm_element_code_or_id=null, $pa_options=null) {
			if(!$this->getPrimaryKey()) { return null; }
			
			if ($pm_element_code_or_id) {
				$va_attributes = $this->getAttributesByElement($pm_element_code_or_id);
			
				foreach($va_attributes as $o_attribute) {
					$this->removeAttribute($o_attribute->getAttributeID(), null, null, array('dontCheckMinMax' => isset($pa_options['force']) && $pa_options['force']));
				}
			} else {
				if(is_array($va_element_codes = $this->getApplicableElementCodes($this->getTypeID(), false, false))) {
					foreach($va_element_codes as $vs_element_code) {
						$va_attributes = $this->getAttributesByElement($vs_element_code);
			
						foreach($va_attributes as $o_attribute) {
							$this->removeAttribute($o_attribute->getAttributeID(), null, null, array('dontCheckMinMax' => isset($pa_options['force']) && $pa_options['force']));
						}
					}
				}
			}
			return $this->update();
		}
		# ------------------------------------------------------------------
		private function _commitAttributes($po_trans=null) {
			$va_attribute_change_list = array();
			$va_inserted_attributes_that_errored = array();
			
			foreach($this->opa_attributes_to_add as $va_info) {
				if ((!($vn_attribute_id = $this->_addAttribute($va_info['values'], $va_info['element'], $po_trans, $va_info))) && !is_null($vn_attribute_id)) {
					$va_info['values']['_errors'] = $this->_getErrorsForBundleUI($va_info['error_source']);
					$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
				} else {
					// noop
				}
			}
			foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeInserts($vs_element, $va_list);
			}
			
			$va_updated_attributes_that_errored = array();
			foreach($this->opa_attributes_to_edit as $va_info) {
				if (!$this->_editAttribute($va_info['attribute_id'], $va_info['values'], $po_trans, $va_info)) {
					$va_updated_attributes_that_errored[$va_info['element']][$va_info['attribute_id']] = $va_info['values'];
				}
			}
			foreach($va_updated_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeUpdates($vs_element, $va_list);
			}
			
			foreach($this->opa_attributes_to_remove as $va_info) {
				$this->_removeAttribute($va_info['attribute_id'], $po_trans, $va_info);
			}
			$this->_initAttributeQueues();
			
			// set the field values array for this instance
			//$this->setFieldValuesArray($this->addAttributesToFieldValuesArray());
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns an array of errors is the specified source. Each element of the array is an associative array
		 * with keys for 'errorDescription' and 'errorCode'; serialized into a JSON format array, this array can be
		 * passed directly to a generic bundle-based UI component (eg. as defined in js/ca.genericbundle.js)
		 */
		private function _getErrorsForBundleUI($ps_source=null) {
			$va_errors = array();
			foreach($this->errors($ps_source) as $o_error) {
				$va_errors[] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
			}
			return $va_errors;
		}
		# ------------------------------------------------------------------
		#
		# ------------------------------------------------------------------
		private function addAttributesToFieldValuesArray() {
			$va_field_values = $this->getFieldValuesArray();
			
			// clear out all attribute values
			foreach($va_field_values as $vs_k => $vs_v) {
				if ((substr($vs_k, 0, 14) == '_ca_attribute_')) { $va_field_values[$vs_k] = null; }
			}
			
			$va_attributes = $this->getAttributes(array());
			$va_field_content = array();
			foreach($va_attributes as $o_attr) {
				foreach($o_attr->getValues() as $o_attr_value) {
					$va_field_content['_ca_attribute_'.$o_attr->getElementID()][$o_attr->getAttributeID()][$o_attr_value->getValueID()] = $o_attr_value->getDisplayValue();
				}
			}
			foreach($va_field_content as $vs_attr_fld => $va_attributes) {
				$va_tmp = array();
				foreach($va_attributes as $vn_attribute_id => $va_values) {
					$va_tmp[] = join("\n", array_values($va_values));
				}
				$va_field_values[$vs_attr_fld] = join("; ",$va_tmp);
			}
			return $va_field_values;
		}
		# ------------------------------------------------------------------
		public function load($pm_id=null, $pb_use_cache=true) {
			$this->init();
			$this->setFieldValuesArray(array());
			if ($vn_c = parent::load($pm_id, $pb_use_cache)) {
				// Copy attributes into field values array in BaseModel
				//$this->setFieldValuesArray($this->addAttributesToFieldValuesArray());
			}
			return $vn_c;
		}
		# ------------------------------------------------------------------
		public function insert($pa_options=null) {
			$vb_we_set_transaction = false;
			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));
				$vb_we_set_transaction = true;
			}
			$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
			if (!is_array($pa_options)) { $pa_options = array(); }
			$pa_options['dont_do_search_indexing'] = true;
			
			$va_field_values = $this->getFieldValuesArray();		// get pre-insert field values (including attribute values)
			
			// change status for attributes is only available **before** insert
			$va_fields_changed_array = $this->_FIELD_VALUE_CHANGED;
			if($vn_id = parent::insert($pa_options)) {
				$this->_commitAttributes($this->getTransaction());
				
				if (sizeof($this->opa_failed_attribute_inserts)) {
					if ($vb_we_set_transaction) { $this->removeTransaction(false); }
					$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
					return false;
				}
				
				$va_field_values_with_updated_attributes = $this->addAttributesToFieldValuesArray();	// copy committed attribute values to field values array
				
				// set changed flag for attributes that have changed
				foreach($va_field_values_with_updated_attributes as $vs_k => $vs_v) {
					if (!isset($va_field_values[$vs_k])) { $va_field_values[$vs_k] = null; }
					if ($va_field_values[$vs_k] != $vs_v) {
						$this->_FIELD_VALUE_CHANGED[$vs_k] = true;
					}
				}
				
				// set the field values array for this instance
				//$this->setFieldValuesArray($va_field_values_with_updated_attributes);

				$va_index_options = array('isNewRow' => true);
				if(caGetOption('queueIndexing', $pa_options, false)) {
					$va_index_options['queueIndexing'] = true;
				}
				$this->doSearchIndexing(array_merge($this->getFieldValuesArray(true), $va_fields_changed_array), false, $va_index_options);
				
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				if ($this->numErrors() > 0) {
					if ($vb_we_set_transaction) { $this->removeTransaction(false); }
					$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
					return false;
				}
				
				if ($vb_we_set_transaction) { $this->removeTransaction(true); }
				return $vn_id;
			} else {
				// push all attributes onto errored list
				$va_inserted_attributes_that_errored = array();
				foreach($this->opa_attributes_to_add as $va_info) {
					$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
				}
				foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
					$this->setFailedAttributeInserts($vs_element, $va_list);
				}
			}
		
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
			return false;
		}
		# ------------------------------------------------------------------
		public function update($pa_options=null) {
			$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$pa_options['dont_do_search_indexing'] = true;
			
			$va_field_values = $this->getFieldValuesArray();		// get pre-update field values (including attribute values)
			// change status for attributes is only available **before** update
			$va_fields_changed_array = $this->_FIELD_VALUE_CHANGED;
			if(parent::update($pa_options)) {
				$this->_commitAttributes($this->getTransaction());
				
			//	$va_field_values_with_updated_attributes = $this->addAttributesToFieldValuesArray();	// copy committed attribute values to field values array
				
				// set the field values array for this instance
				//$this->setFieldValuesArray($va_field_values_with_updated_attributes);

				$va_index_options = array();
				if(caGetOption('queueIndexing', $pa_options, false)) {
					$va_index_options['queueIndexing'] = true;
				}
				
				$this->doSearchIndexing($va_fields_changed_array, false, $va_index_options);
				
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				if ($this->numErrors() > 0) {
					return false;
				}
				return true;
			}
			
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			return false;
		}
		# ------------------------------------------------------------------
		public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
			$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
			
			$o_trans = null;
			if (!$this->inTransaction()) {
				$o_trans = new Transaction($this->getDb());
				$this->setTransaction($o_trans);
			}
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			$vn_id = $this->getPrimaryKey();
			if(($vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list)) && (!$this->hasField('deleted') || caGetOption('hard', $pa_options, false))) {
				// Delete any associated attributes and attribute_values
				if (!($qr_res = $this->getDb()->query("
					DELETE FROM ca_attribute_values 
					USING ca_attributes 
					INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id 
					WHERE ca_attributes.table_num = ? AND ca_attributes.row_id = ?
				", array((int)$this->tableNum(), (int)$vn_id)))) { 
					$this->errors = $this->getDb()->errors();
					if ($o_trans) { $o_trans->rollback(); }
					
					if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
					return false; 
				}
				
				if (!($qr_res = $this->getDb()->query("
					DELETE FROM ca_attributes
					WHERE
						table_num = ? AND row_id = ?
				", array((int)$this->tableNum(), (int)$vn_id)))) {
					$this->errors = $this->getDb()->errors();
					if ($o_trans) { $o_trans->rollback(); }
					
					if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
					return false;
				}
				
				//
				// Remove any authority attributes that reference this row
				//
				if ($vn_element_type = (int)AuthorityAttributeValue::tableToElementType($this->tableName())) {
					if (($qr_res = $this->getDb()->query("
						SELECT ca.table_num, ca.row_id FROM ca_attributes ca
						INNER JOIN ca_attribute_values AS cav ON cav.attribute_id = ca.attribute_id 
						INNER JOIN ca_metadata_elements AS cme ON cav.element_id = cme.element_id 
						WHERE cme.datatype = ? AND cav.value_integer1 = ?
					", array($vn_element_type, (int)$vn_id)))) { 
						$va_ids = array();
						while($qr_res->nextRow()) {
							$va_ids[$qr_res->get('table_num')][] = $qr_res->get('row_id');
						}
						if (!($qr_res = $this->getDb()->query("
							DELETE FROM ca_attribute_values 
							USING ca_metadata_elements 
							INNER JOIN ca_attribute_values ON ca_attribute_values.element_id = ca_metadata_elements.element_id 
							WHERE ca_metadata_elements.datatype = ? AND ca_attribute_values.value_integer1 = ?
						", array($vn_element_type, (int)$vn_id)))) { 
							$this->errors = $this->getDb()->errors();
							if ($o_trans) { $o_trans->rollback(); }
					
							if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
							return false; 
						} else {
							$o_indexer = new SearchIndexer($this->getDb());
							foreach($va_ids as $vs_table => $va_ids) {
								$o_indexer->reindexRows($vs_table, $va_ids, array('transaction' => $o_trans));
							}
						}
					}
				}
				
				if ($o_trans) { $o_trans->commit(); }
					
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				return $vn_rc;
			}
			
			if ($o_trans) { $vn_rc ? $o_trans->commit() : $o_trans->rollback(); }
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			return $vn_rc;
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function getValuesForExport($pa_options=null) {
			$va_codes = $this->getApplicableElementCodes();
			
			$va_data = parent::getValuesForExport($pa_options);		// get intrinsics
			
			$t_locale = new ca_locales();
			$t_list = new ca_lists();
			
			// get attributes
			foreach($va_codes as $vs_code) {
				if ($va_v = $this->get($this->tableName().'.'.$vs_code, array('returnWithStructure' => true, 'returnAllLocales' => true, 'returnAsArray' => true, 'return' => 'url', 'version' => 'original'))) {
					foreach($va_v as $vn_id => $va_v_by_locale) {
						foreach($va_v_by_locale as $vn_locale_id => $va_v_list) {
							if(!is_array($va_v_list)) { continue; }
							
							if (!($vs_locale = $t_locale->localeIDToCode($vn_locale_id))) {
								$vs_locale = 'NONE';
							}
							$vn_i = 0;
							foreach($va_v_list as $vn_index => $va_values) {	
								if (is_array($va_values)) {
									foreach($va_values as $vs_sub_code => $vs_value) {
										if(!$vs_sub_code) { continue; }
										if (!$t_element = ca_metadata_elements::getInstance($vs_sub_code)) { continue; }
										
										switch((int)$t_element->get('datatype')) {
											case 3:		// list
												$va_list_item = $t_list->getItemFromListByItemID($t_element->get('list_id'), $vs_value);
												$vs_value = $vs_value.":".$va_list_item['idno'];
												break;
										}
										$va_data['ca_attribute_'.$vs_code][$vs_locale]['value_'.$vn_i][$vs_sub_code] = $vs_value;
									}
									$vn_i++;
															
								} else {
									$va_data['ca_attribute_'.$vs_code][$vs_locale]['value_'.$vn_i][$vs_code] = $va_values;
								}
							}
						}	
					}
				}
			}
			return $va_data;	
		}
		# ------------------------------------------------------------------
		/**
		 * Set field value(s) for the table row represented by this object
		 *
		 */
		public function set($pa_fields, $pm_value="", $pa_options=null) {
			if ($this->ATTRIBUTE_TYPE_LIST_CODE) {
				if(is_array($pa_fields)) {
					if (isset($pa_fields[$this->ATTRIBUTE_TYPE_ID_FLD]) && !is_numeric($pa_fields[$this->ATTRIBUTE_TYPE_ID_FLD])) {
						if ($vn_id = ca_lists::getItemID($this->ATTRIBUTE_TYPE_LIST_CODE, $pa_fields[$this->ATTRIBUTE_TYPE_ID_FLD])) {
							$pa_fields[$this->ATTRIBUTE_TYPE_ID_FLD] = $vn_id;
						}
					}
				} else {
					if (($pa_fields ==  $this->ATTRIBUTE_TYPE_ID_FLD) && (!is_numeric($pm_value))) {
						if ($vn_id = ca_lists::getItemID($this->ATTRIBUTE_TYPE_LIST_CODE, $pm_value)) {
							$pm_value = $vn_id;
						}
					}
				}
			}
			return parent::set($pa_fields, $pm_value, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Get value(s) for specified attribute. $ps_field specifies the value to fetch in <table_name>.<element_code> or <table_name>.<element_code>.<subelement_code>
		 * Will return a string containing the retrieved value or values (since attributes can repeat). The values will
		 * be formatted using the 'template' option with values separated by a delimiter as set in the 'delimiter'
		 * option (default is a space). 
		 *
		 * If the 'returnAsArray' option is set the an array containing all values will be returned.
		 * The array will be keyed on the current row primary key, and then attribute_id, with each attribute_id value containing an array keyed on element code and having
		 * values set to attribute values (this is a bit more complicated than one might hope since not only can
		 * values repeat, but they can be composed of many sub-values... the final array key'ed on element_code may have several values if the attribute is complex). 
		 *
		 * If the 'returnAllLocales' option is set *and* 'returnAsArray' is set then the returned array will include an extra dimension (or key if that's what you prefer to call it)
		 * that separates values by numeric locale_id. Thus the returned array will have several layers of keys: current row primary key, then locale_id, then attribute_id and then
		 * finally, element codes. This format is, incidentally, compatible with the caExtractValuesByUserLocale() helper function, which would strip all values not needed for
		 * display in the current locale.
		 *
		 * @param $pa_options array - array of options for get; in addition to the standard get() options, will also pass through options to attribute value handlers
		 *		Supported options include:
		 *			locale = 
		 *			returnAsArray = if true, return an array, otherwise return a string (default is false)
		 *			returnAllLocales = 
		 *			template = 
		 *			delimiter = 
		 *			convertCodesToDisplayText =
 	 	 *			returnAsLink = if true and $ps_field is a URL attribute and returnAllLocales is not set, then returned values will be links. Default is false.
 	 	 *			returnAsLinkText = *For URL attributes only* Text to use a content of HTML link. If omitted the url itself is used as the link content. 	 	 
 	 	 *			returnAsLinkAttributes = array of attributes to include in link <a> tag. Use this to set class, alt and any other link attributes.
		 *
		 * @return mixed - 
		 *
		 * 
		 */
		public function get($ps_field, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			$vs_template = 				(isset($pa_options['template'])) ? $pa_options['template'] : null;
			$vs_delimiter = 			(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
			$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
			$vb_return_all_locales = 	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
			
			$vb_return_as_link = 		(isset($pa_options['returnAsLink'])) ? (bool)$pa_options['returnAsLink'] : false;
			$vs_return_as_link_text = 	(isset($pa_options['returnAsLinkText'])) ? (string)$pa_options['returnAsLinkText'] : '';
			$vs_return_as_link_attributes = 	(isset($pa_options['returnAsLinkAttributes'])) ? (string)$pa_options['returnAsLinkAttributes'] : array();
			
			if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
			if (!isset($pa_options['convertCodesToDisplayText'])) { $pa_options['convertCodesToDisplayText'] = false; }
		
			// does get refer to an attribute?
			$va_tmp = explode('.', $ps_field);
			
			$pa_options = array_merge($pa_options, array('indexByRowID' => true));		// force arrays to be indexed by current row_id
			
			$t_instance = $this;
			if ((sizeof($va_tmp) >= 2) && (!$this->hasField($va_tmp[2]))) {
				if (($va_tmp[1] == 'parent') && ($this->isHierarchical()) && ($vn_parent_id = $this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD')))) {
					$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
					if (!$t_instance->load($vn_parent_id)) {
						$t_instance = $this;
					} else {
						unset($va_tmp[1]);
						$va_tmp = array_values($va_tmp);
					}
				} else {
					if (($va_tmp[1] == 'children') && ($this->isHierarchical())) {
						unset($va_tmp[1]);					// remove 'children' from field path
						$va_tmp = array_values($va_tmp);
						$vs_childless_path = join('.', $va_tmp);
						
						$va_data = array();
						$va_children_ids = $this->getHierarchyChildren(null, array('idsOnly' => true));
						
						$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
						
						foreach($va_children_ids as $vn_child_id) {
							if ($t_instance->load($vn_child_id)) {
								$vm_val = $t_instance->get($vs_childless_path, $pa_options);
								$va_data = array_merge($va_data, is_array($vm_val) ? $vm_val : array($vm_val));
							}
						}
						
						if ($vb_return_as_array) {
							return $va_data;
						} else {
							return join($vs_delimiter, $va_data);
						}
					} 
				}
			}
			
			switch(sizeof($va_tmp)) {
				# -------------------------------------
				case 1:		// simple name
					if (!$t_instance->hasField($va_tmp[0])) {	// is it intrinsic?
						// nope... so try it as an attribute
						if (!$vb_return_as_array) {
							return $t_instance->getAttributesForDisplay($va_tmp[0], $vs_template, $pa_options);
						} else {
							$va_values = $t_instance->getAttributeDisplayValues($va_tmp[0], $t_instance->getPrimaryKey(), $pa_options);
							if (!$vb_return_all_locales) {
								$va_values = array_shift($va_values);
							}
							return $va_values;
						}
					}
					break;
				# -------------------------------------
				case 2:		// table_name.field_name
					if ($va_tmp[0] === $t_instance->tableName()) {
						if (!$t_instance->hasField($va_tmp[1]) && ($va_tmp[1] != 'created') && ($va_tmp[1] != 'lastModified')) {
							// try it as an attribute
							if (!$vb_return_as_array) {
								return $t_instance->getAttributesForDisplay($va_tmp[1], $vs_template, $pa_options);
							} else {
								$va_values = $t_instance->getAttributeDisplayValues($va_tmp[1], $vn_row_id = $t_instance->getPrimaryKey(), $pa_options);
								if (!$vb_return_all_locales) {
									$va_values = array_shift($va_values);
									
									if ($vs_template) {
										$va_values_tmp = array();
										foreach($va_values as $vn_i => $va_value_list) {
											$va_values_tmp[] = caProcessTemplateForIDs($vs_template, $va_tmp[0], array($vn_row_id), array_merge($pa_options, array('returnAsArray' => false, 'placeholderPrefix' => array_slice($va_tmp, 0, 2))));
										}
				
										$va_values = $va_values_tmp;
									}
								}
								return $va_values;
							}
						}
					}
					break;
				# -------------------------------------
				case 3:		// table_name.field_name.sub_element / table_name.field_name.hierarchy
				case 4:		// table_name.field_name.sub_element.hierarchy
					if(!$this->hasField($va_tmp[2]) || ($va_tmp[2] === 'hierarchy')) {
						if ($va_tmp[0] === $t_instance->tableName()) {
							$vb_is_in_container = false;
							
							if	(!$this->hasField($va_tmp[1])) {
								if (($va_tmp[2] === 'hierarchy') || ($va_tmp[3] === 'hierarchy')) {
									if ($va_tmp[3] === 'hierarchy') { $vb_is_in_container = true; }
									if (in_array(ca_metadata_elements::getElementDatatype($vb_is_in_container ? $va_tmp[2] : $va_tmp[1]), array(__CA_ATTRIBUTE_VALUE_LIST__))) {
										
										$va_items = $this->get(join('.', $vb_is_in_container ? array($va_tmp[0], $va_tmp[1], $va_tmp[2]) : array($va_tmp[0], $va_tmp[1])), array('returnAsArray' => true));
										$va_item_ids = $va_item_ids = caExtractValuesFromArrayList($va_items, $vb_is_in_container ? $va_tmp[2] : $va_tmp[1], array('preserveKeys' => false));
							
										$qr_items = caMakeSearchResult('ca_list_items', $va_item_ids);
		
										if (!$va_item_ids || !is_array($va_item_ids) || !sizeof($va_item_ids)) {  return $vb_return_as_array ? array() : null; } 
									
		
										$va_get_spec = $va_tmp;
										array_shift($va_get_spec); array_shift($va_get_spec);
										if ($vb_is_in_container) { array_shift($va_get_spec); }
										array_unshift($va_get_spec, 'ca_list_items');
										
										$vs_get_spec = join('.', $va_get_spec);
										
										$va_vals = array();
										while($qr_items->nextHit()) {
											$va_hier = $qr_items->get($vs_get_spec, array('returnAsArray' => true));
											array_shift($va_hier);	// get rid of root
											$va_vals[] = $vb_return_as_array ? $va_hier : join($vs_delimiter, $va_hier);
										}
										
										return $va_vals;
									}
								} 
							}
			
							if (!$t_instance->hasField($va_tmp[1])) {
								// try it as an attribute
									
								if (!$vb_return_as_array) {
									if (!$vs_template) { $vs_template = '^'.$va_tmp[2]; }
									return $t_instance->getAttributesForDisplay($va_tmp[1], $vs_template, $pa_options);
								} else {
									$va_values = $t_instance->getAttributeDisplayValues($va_tmp[1], $vn_row_id = $t_instance->getPrimaryKey(), $pa_options);
									$va_subvalues = array();
									
									if ($vb_return_all_locales) {
										foreach($va_values as $vn_attribute_id => $va_attributes_by_locale) {
											foreach($va_attributes_by_locale as $vn_locale_id => $va_attribute_values) {
												foreach($va_attribute_values as $vn_attribute_id => $va_data) {
													if(isset($va_data[$va_tmp[2]])) {
														$va_subvalues[$vn_attribute_id][(int)$vn_locale_id][$vn_attribute_id] = $va_data[$va_tmp[2]];
													}
												}
											}
										}	
									} else {
										foreach($va_values as $vn_id => $va_attribute_values) {
											foreach($va_attribute_values as $vn_attribute_id => $va_data) {
												if(isset($va_data[$va_tmp[2]])) {
													if ($vs_template) { 
														$va_subvalues[$vn_attribute_id] = caProcessTemplateForIDs($vs_template, $va_tmp[0], array($vn_row_id), array_merge($pa_options, array('requireLinkTags' => true, 'returnAsArray' => false, 'placeholderPrefix' => array_slice($va_tmp, 0, 2))));
													} else {
														$va_subvalues[$vn_attribute_id] = $va_data[$va_tmp[2]];
													}
												}
											}
										}
									}
									return $va_subvalues;
								}
							}
						}
					}
					break;
				# -------------------------------------
			}
			return parent::get($ps_field, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function getSourceID() {
			if (!isset($this->SOURCE_ID_FLD) || !$this->SOURCE_ID_FLD) { return null; }
			return $this->get($this->SOURCE_ID_FLD);
		}
		# ------------------------------------------------------------------
		/**
		 * Field in this table that defines the source of the row
		 */
		public function getSourceFieldName() {
			return $this->SOURCE_ID_FLD;
		}
		# ------------------------------------------------------------------
		/**
		 * List code (from ca_lists.list_code) of list defining sources for this table
		 */
		public function getSourceListCode() {
			return isset($this->SOURCE_LIST_CODE) ? $this->SOURCE_LIST_CODE : null;
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function getSourceName($pn_source_id=null) {
			if ($t_list_item = $this->getTypeInstance($pn_source_id)) {
				return $t_list_item->getLabelForDisplay(false);
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns ca_list_items.idno (aka "item code") for the source of the currently loaded row
		 *
		 * @return string - idno (aka "item code") for current row's source or null if no row is loaded or model does not support sources
		 */
		public function getSourceCode() {
			if ($t_list_item = $this->getSourceInstance()) {
				return $t_list_item->get('idno');
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns ca_list_items.item_id (aka "source_id") for $ps_type_code
		 *
		 * @param string $ps_type_code Alphanumeric code for the type
		 * @return int - item_id (aka "source_id") for specified list item idno (aka "source code")
		 */
		public function getSourceIDForCode($ps_source_code) {
			$va_sources = $this->getSourceList();
			
			foreach($va_sources as $vn_source_id => $va_source_info) {
				if ($va_source_info['idno'] == $ps_source_code) {
					return $vn_source_id;
				}
			}
			
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns default ca_list_items.item_id (aka "source_id") for this model
		 *
		 * @return int - item_id (aka "source_id") for default type
		 */
		public function getDefaultSourceID($pa_options=null) {
			global $g_request;
			if (!($po_request = caGetOption('request', $pa_options, null))) { $po_request = $g_request; }
			
			// Try to load default for user
			if ($po_request && ($po_request->isLoggedIn())) {
				$va_roles = $po_request->user->getUserRoles(); 
				foreach($va_roles as $vn_i => $va_role) {
					$va_vars = $va_role['vars'];
					if ($vn_id = $va_vars['source_access_settings'][$this->tableName().'_default_id']) {
						return $vn_id;
					}
				}	
			}
			
			// Bail and return list default
			$t_list = new ca_lists();
			return $t_list->getDefaultItemID($this->getSourceListCode(), ['useFirstElementAsDefaultDefault' => true]);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of sources for this table with locale-appropriate labels, keyed by source_id
		 *
		 * @param array $pa_options Array of options, passed as-is to ca_lists::getItemsForList() [the underlying implemenetation]
		 * @return array List of types
		 */ 
		public function getSourceList($pa_options=null) {
			$t_list = new ca_lists();
			if (isset($pa_options['childrenOfCurrentTypeOnly']) && $pa_options['childrenOfCurrentTypeOnly']) {
				$pa_options['item_id'] = $this->get('type_id');
			}
			
			$va_list = $t_list->getItemsForList($this->getSourceListCode(), $pa_options);
			if (caGetOption('idsOnly', $pa_options, false)) { return $va_list; }
			return is_array($va_list) ? caExtractValuesByUserLocale($va_list): array();
		}
		# ------------------------------------------------------------------
		/**
		 * Return ca_list_item instance for the source of the currently loaded row
		 */ 
		public function getSourceInstance($pn_source_id=null) {
			if (!isset($this->SOURCE_ID_FLD) || !$this->SOURCE_ID_FLD) { return null; }
			if ($pn_source_id) { 
				$vn_source_id = $pn_source_id; 
			} else {
				if (!($vn_source_id = $this->get($this->SOURCE_ID_FLD))) { return null; }
			}
			
			$t_list_item = new ca_list_items($vn_source_id);
			return ($t_list_item->getPrimaryKey()) ? $t_list_item : null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns HTML <select> form element with source list
		 *
		 * @param string $ps_name The name of the returned form element
		 * @param array $pa_attributes An optional array of HTML attributes to place into the returned <select> tag
		 * @param array $pa_options An array of options. Supported options are anything supported by ca_lists::getListAsHTMLFormElement as well as:
		 *		childrenOfCurrentSourceOnly = Returns only sourcs below the current source
		 *		restrictToSources = Array of source_ids to restrict source list to
		 * @return string HTML for list element
		 */ 
		public function getSourceListAsHTMLFormElement($ps_name, $pa_attributes=null, $pa_options=null) {
			if(!($vs_source_id_fld_name = $this->getSourceFieldName())) { return null; }
			$t_list = new ca_lists();
			if (isset($pa_options['childrenOfCurrentSourceOnly']) && $pa_options['childrenOfCurrentSourceOnly']) {
				$pa_options['childrenOnlyForItemID'] = $this->get($vs_source_id_fld_name);
			}
			
			$pa_options['limitToItemsWithID'] = caGetSourceRestrictionsForUser($this->tableName(), $pa_options);
			
			if (isset($pa_options['restrictToTypes']) && is_array($pa_options['restrictToTypes'])) {
				if (!$pa_options['limitToItemsWithID'] || !is_array($pa_options['limitToItemsWithID'])) {
					$pa_options['limitToItemsWithID'] = $pa_options['restrictToSources'];
				} else {
					$pa_options['limitToItemsWithID'] = array_intersect($pa_options['limitToItemsWithID'], $pa_options['restrictToSources']);
				}
			}
			
			return $t_list->getListAsHTMLFormElement($this->getSourceListCode(), $ps_name, $pa_attributes, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Fetch the type_id for the currently loaded row or, optionally, the row specified by $pn_id
		 *
		 * @param $pn_id Option primary key to fetch type_id for. If omitted the type_id for currently loaded row is returned. [Default=null]
		 * @return int 
		 */
		public function getTypeID($pn_id=null) {
			if (!isset($this->ATTRIBUTE_TYPE_ID_FLD) || !$this->ATTRIBUTE_TYPE_ID_FLD) { return null; }
			if ($pn_id) {
				$qr_res = $this->getDb()->query("SELECT {$this->ATTRIBUTE_TYPE_ID_FLD} FROM ".$this->tableName()." WHERE ".$this->primaryKey()." = ?", array((int)$pn_id));
				if($qr_res->nextRow()) {
					return $qr_res->get($this->ATTRIBUTE_TYPE_ID_FLD);
				}
				return null;
			}
			return $this->get($this->ATTRIBUTE_TYPE_ID_FLD);
		}
		# ------------------------------------------------------------------
		/**
		 * Field in this table that defines the type of the row; the type determines which attributes are applicable to the row
		 */
		public function getTypeFieldName() {
			return $this->ATTRIBUTE_TYPE_ID_FLD;
		}
		# ------------------------------------------------------------------
		/**
		 * Determine if type for this model is mandatory 
		 *
		 * @return bool Returns true if type is optional (may be null in the database), false if it is mandatory or null if the model does not support types.
		 */
		public function typeIDIsOptional() {
			if (!property_exists($this->tableName(), 'ATTRIBUTE_TYPE_ID_FLD')) { return null; }
			return $this->getFieldInfo($this->ATTRIBUTE_TYPE_ID_FLD, 'IS_NULL');
		}
		# ------------------------------------------------------------------
		/**
		 * List code (from ca_lists.list_code) of list defining types for this table
		 */
		public function getTypeListCode() {
			return isset($this->ATTRIBUTE_TYPE_LIST_CODE) ? $this->ATTRIBUTE_TYPE_LIST_CODE : null;
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function getTypeName($pn_type_id=null) {
			if ($pn_type_id && !is_numeric($pn_type_id)) { $pn_type_id = $this->getTypeIDForCode($pn_type_id); }
			if ($t_list_item = $this->getTypeInstance($pn_type_id)) {
				return $t_list_item->getLabelForDisplay(false);
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns ca_list_items.idno (aka "item code") for the type of the currently loaded row
		 *
		 * @return string - idno (aka "item code") for current row's type or null if no row is loaded or model does not support types
		 */
		public function getTypeCode($pn_type_id=null) {
			if ($t_list_item = $this->getTypeInstance($pn_type_id)) {
				return $t_list_item->get('idno');
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns ca_list_items.item_id (aka "type_id") for $ps_type_code
		 *
		 * @param string $ps_type_code Alphanumeric code for the type
		 * @return int - item_id (aka "type_id") for specified list item idno (aka "type code")
		 */
		public function getTypeIDForCode($ps_type_code) {
			$va_types = $this->getTypeList();
			
			foreach($va_types as $vn_type_id => $va_type_info) {
				if ($va_type_info['idno'] == $ps_type_code) {
					return $vn_type_id;
				}
			}
			
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns ca_list_items.idno (aka "type code") for $pn_type_id
		 *
		 * @param int $pn_type_id Number id for the type
		 * @return string idno (aka "type code") for specified list item id (aka "type id")
		 */
		public function getTypeCodeForID($pn_type_id) {
			$va_types = $this->getTypeList();
			return isset($va_types[$pn_type_id]) ? $va_types[$pn_type_id]['idno'] : null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns default ca_list_items.item_id (aka "type_id") for this model
		 *
		 * @return int - item_id (aka "type_id") for default type
		 */
		public function getDefaultTypeID() {
			$t_list = new ca_lists();
			return $t_list->getDefaultItemID($this->getTypeListCode(), ['omitRoot' => true, 'useFirstElementAsDefaultDefault' => true]);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of types for this table with locale-appropriate labels, keyed by type_id
		 *
		 * @param array $pa_options Array of options, passed as-is to ca_lists::getItemsForList() [the underlying implemenetation]
		 * @return array List of types
		 */ 
		public function getTypeList($pa_options=null) {
			$t_list = new ca_lists();
			if (isset($pa_options['childrenOfCurrentTypeOnly']) && $pa_options['childrenOfCurrentTypeOnly']) {
				$pa_options['item_id'] = $this->get('type_id');
			}
			
			$va_list = $t_list->getItemsForList($this->getTypeListCode(), $pa_options);
			if (caGetOption('idsOnly', $pa_options, false)) { return $va_list; }
			return is_array($va_list) ? caExtractValuesByUserLocale($va_list): array();
		}
		# ------------------------------------------------------------------
		/**
		 * Return ca_list_item instance for the type of the currently loaded row
		 */ 
		public function getTypeInstance($pn_type_id=null) {
			if (!isset($this->ATTRIBUTE_TYPE_ID_FLD) || !$this->ATTRIBUTE_TYPE_ID_FLD) { return null; }
			if ($pn_type_id) { 
				$vn_type_id = $pn_type_id; 
			} else {
				if (!($vn_type_id = $this->get($this->ATTRIBUTE_TYPE_ID_FLD))) { return null; }
			}
			
			$t_list_item = new ca_list_items($vn_type_id);
			return ($t_list_item->getPrimaryKey()) ? $t_list_item : null;
		}
		# ------------------------------------------------------------------
		/**
		 * Return setting, if defined, from list item for type in the currently loaded row
		 */ 
		public function getTypeSetting($ps_setting, $pn_type_id=null) {
			if ($t_type = $this->getTypeInstance($pn_type_id)) {
				return $t_type->getSetting($ps_setting);
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns HTML <select> form element with type list
		 *
		 * @param string $ps_name The name of the returned form element
		 * @param array $pa_attributes An optional array of HTML attributes to place into the returned <select> tag
		 * @param array $pa_options An array of options. Supported options are anything supported by ca_lists::getListAsHTMLFormElement as well as:
		 *		childrenOfCurrentTypeOnly = Returns only types below the current type
		 *		restrictToTypes = Array of type_ids to restrict type list to
		 *		inUse = Return only types that are used by at least one record. [Default is false]
		 *		checkAccess = Array of access values to filter returned values on. Available for any related table with an "access" field (ca_objects, ca_entities, etc.). If omitted no filtering is performed. [Default is null]
		 * @return string HTML for list element
		 */ 
		public function getTypeListAsHTMLFormElement($ps_name, $pa_attributes=null, $pa_options=null) {
			$t_list = new ca_lists();
			if (isset($pa_options['childrenOfCurrentTypeOnly']) && $pa_options['childrenOfCurrentTypeOnly']) {
				$pa_options['childrenOnlyForItemID'] = $this->get('type_id');
			}
			
			$pa_options['limitToItemsWithID'] = caGetTypeRestrictionsForUser($this->tableName(), $pa_options);
			
			if (caGetOption('inUse', $pa_options, false)) {
				$vs_access_sql = '';
				$va_sql_params = array();
				if (($va_check_access = caGetOption('checkAccess', $pa_options, null)) && is_array($va_check_access) && sizeof($va_check_access) && $this->hasField('access')) {
					array_walk($va_check_access, function(&$pm_item, $ps_key) { $pm_item = (int)$pm_item; });
					$vs_access_sql = " AND (access IN (?))";
					$va_sql_params[] = $va_check_access;
					
					$qr_types_in_use = $this->getDb()->query("SELECT DISTINCT type_id FROM ".$this->tableName().($this->hasField('deleted') ? " WHERE deleted = 0 {$vs_access_sql}" : ""), $va_sql_params);
				}
				if(!is_array($pa_options['limitToItemsWithID'])) { $pa_options['limitToItemsWithID'] = array(); }
				
				if($qr_types_in_use->numRows() > 0) {
					$pa_options['limitToItemsWithID'] += $qr_types_in_use->getAllFieldValues('type_id');
				}
			}
			
			if (isset($pa_options['restrictToTypes']) && is_array($pa_options['restrictToTypes'])) {
				$pa_options['restrictToTypes'] = caMakeTypeIDList($this->tableName(), $pa_options['restrictToTypes'], $pa_options);
				if (!$pa_options['limitToItemsWithID'] || !is_array($pa_options['limitToItemsWithID'])) {
					$pa_options['limitToItemsWithID'] = $pa_options['restrictToTypes'];
				} else {
					$pa_options['limitToItemsWithID'] = array_intersect($pa_options['limitToItemsWithID'], $pa_options['restrictToTypes']);
				}
			}
			
			return $t_list->getListAsHTMLFormElement($this->getTypeListCode(), $ps_name, $pa_attributes, $pa_options);
		}
		# ------------------------------------------------------------------
		// --- Forms
		# ------------------------------------------------------------------
		/**
		  * Returns field label text for element specified by standard "get" bundle code (eg. <table_name>.<element_code> format)
		  */
		public function getDisplayLabel($ps_field) {
			$va_tmp = explode('.', $ps_field);
			if ($va_tmp[0] == $this->tableName()) {
				if (!$this->hasField($va_tmp[1]) && !in_array($va_tmp[1], array('created', 'modified', 'lastModified')) && !in_array($va_tmp[0], array('created', 'modified', 'lastModified'))) {
					$va_tmp[1] = preg_replace('!^ca_attribute_!', '', $va_tmp[1]);	// if field space is a bundle placement-style bundlename (eg. ca_attribute_<element_code>) then strip it before trying to pull label
					return $this->getAttributeLabel($va_tmp[1]);	
				}
			}
			return parent::getDisplayLabel($ps_field);
		}
		# --------------------------------------------------------------------------------------------
		/**
		  * Returns display description for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
		  */
		public function getDisplayDescription($ps_field) {
			$va_tmp = explode('.', $ps_field);
			if (($va_tmp[0] != $this->tableName()) && !in_array($va_tmp[0], array('created', 'modified', 'lastModified'))) { return null; }
			if (!$this->hasField($va_tmp[1]) && !in_array($va_tmp[1], array('created', 'modified', 'lastModified')) && !in_array($va_tmp[0], array('created', 'modified', 'lastModified'))) {
				$va_tmp[1] = preg_replace('!^ca_attribute_!', '', $va_tmp[1]);	// if field space is a bundle placement-style bundlename (eg. ca_attribute_<element_code>) then strip it before trying to pull label
				return $this->getAttributeDescription($va_tmp[1]);	
			}
			return parent::getDisplayDescription($ps_field);
		}
		# --------------------------------------------------------------------------------------------
		/**
		  * Returns HTML search form input widget for bundle specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
		  * This method handles generation of search form widgets for all metadata elements bound to the primary table. If this method can't handle 
		  * the bundle (because it is not a metadata element bound to the primary table...) it will pass the request to the superclass implementation of 
		  * htmlFormElementForSearch()
		  *
		  * @param $po_request HTTPRequest
		  * @param $ps_field string
		  * @param $pa_options array
		  * @return string HTML text of form element. Will return null (from superclass) if it is not possible to generate an HTML form widget for the bundle.
		  * 
		  */
		public function htmlFormElementForSearch($po_request, $ps_field, $pa_options=null) {
			if(!$pa_options) { $pa_options = array(); }
			$va_tmp = explode('.', $ps_field);
			
			if ($vs_rel_types = join(";", caGetOption('restrictToRelationshipTypes', $pa_options, array()))) { $vs_rel_types = "/{$vs_rel_types}"; }
			if ($va_tmp[1] == $this->getTypeFieldName()) {
				return $this->getTypeListAsHTMLFormElement($ps_field.$vs_rel_types, array('class' => caGetOption('class', $pa_options, null)), array_merge($pa_options, array('nullOption' => '-')));
			}
			
			if ($ps_render = caGetOption('render', $pa_options, null)) {
				switch($ps_render) {
					case 'is_set':
						return caHTMLCheckboxInput($ps_field.$vs_rel_types, array('value' => '['._t('SET').']'));
						break;
					case 'is':
						return caHTMLCheckboxInput($ps_field.$vs_rel_types, array('value' => caGetOption('value', $pa_options, null)));
						break;
				}
			}
											
			if (in_array($va_tmp[1], array('preferred_labels', 'nonpreferred_labels'))) {
				return caHTMLTextInput($ps_field.$vs_rel_types.($vb_as_array_element ? "[]" : ""), array('value' => $pa_options['values'][$ps_field], 'class' => $pa_options['class'], 'id' => str_replace('.', '_', $ps_field)), $pa_options);
			}
			
			if (!in_array($va_tmp[0], array('created', 'modified'))) {		// let change log searches filter down to BaseModel
				if ($va_tmp[0] != $this->tableName()) { return null; }
				$vs_fld = array_pop($va_tmp);
				if (!$this->hasField($vs_fld)) {
					$vs_fld = preg_replace('!^ca_attribute_!', '', $vs_fld);	// if field space is a bundle placement-style bundlename (eg. ca_attribute_<element_code>) then strip it before trying to pull label
					
					return $this->htmlFormElementForAttributeSearch($po_request, $vs_fld, array_merge($pa_options, array(
								'values' => (isset($pa_options['values']) && is_array($pa_options['values'])) ? $pa_options['values'] : array(),
								'width' => (isset($pa_options['width']) && ($pa_options['width'] > 0)) ? $pa_options['width'] : 20, 
								'height' => (isset($pa_options['height']) && ($pa_options['height'] > 0)) ? $pa_options['height'] : 1, 
								'class' => (isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : '',
								'format' => '^ELEMENT',
								'multivalueFormat' => '<i>^LABEL</i><br/>^ELEMENT'
							)));
				}
			}
			return parent::htmlFormElementForSearch($po_request, $ps_field, $pa_options);
		}
		# --------------------------------------------------------------------------------------------
		/**
		  * Returns HTML form input widget for bundle specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format) suitable
		  * for use in a simple data entry form, such as the front-end "contribute" user-provided content submission form
		  * 
		  * This method handles generation of form widgets for all metadata elements bound to the primary table. If this method can't handle 
		  * the bundle (because it is not a metadata element bound to the primary table...) it will pass the request to the superclass implementation of 
		  * htmlFormElementForSimpleForm()
		  *
		  * @param $po_request HTTPRequest
		  * @param $ps_field string
		  * @param $pa_options array
		  * @return string HTML text of form element. Will return null (from superclass) if it is not possible to generate an HTML form widget for the bundle.
		  * 
		  */
		public function htmlFormElementForSimpleForm($po_request, $ps_field, $pa_options=null) {
			if(!$pa_options) { $pa_options = array(); }
			$va_tmp = explode('.', $ps_field);
			
			if ($va_tmp[1] == $this->getTypeFieldName()) {
				return $this->getTypeListAsHTMLFormElement(null, null, $pa_options);
			}
			
			if ($va_tmp[0] != $this->tableName()) { return null; }
			if (!$this->hasField($va_tmp[1])) {
				$va_tmp[1] = preg_replace('!^ca_attribute_!', '', $va_tmp[1]);	// if field space is a bundle placement-style bundlename (eg. ca_attribute_<element_code>) then strip it before trying to pull label
				
				$vs_fld = array_pop($va_tmp);
				return $this->htmlFormElementForAttributeSearch($po_request, $vs_fld, array_merge($pa_options, array(
							'values' => (isset($pa_options['values']) && is_array($pa_options['values'])) ? $pa_options['values'] : array(),
							'width' => (isset($pa_options['width']) && ($pa_options['width'] > 0)) ? $pa_options['width'] : 20, 
							'height' => (isset($pa_options['height']) && ($pa_options['height'] > 0)) ? $pa_options['height'] : 1, 
							'class' => (isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : '',
							'format' => '^ELEMENT',
							'forSimpleForm' => true,
							'multivalueFormat' => '<i>^LABEL</i><br/>^ELEMENT'
						)));
			}
			return parent::htmlFormElementForSimpleForm($po_request, $ps_field, array_merge($pa_options, array('view' => caGetOption('view', $pa_options, 'ca_simple_form_attributes.php'))));
		}
		# ------------------------------------------------------------------
		/**
		  * Get HTML form element bundle for metadata element
		  */
		public function getAttributeLabel($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_label_cache[$pm_element_code_or_id])) {
				$va_cached_labels = (BaseModelWithAttributes::$s_element_label_cache);
				return $va_cached_labels[$pm_element_code_or_id]['name'];
			}
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			return $t_element->getLabelForDisplay(false);
		}
		# ------------------------------------------------------------------
		// get HTML form element bundle for metadata element
		public function getAttributeDescription($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_label_cache[$pm_element_code_or_id])) {
				$va_cached_labels = (BaseModelWithAttributes::$s_element_label_cache);
				return $va_cached_labels[$pm_element_code_or_id]['description'];
			}
			
			$va_label = $this->getAttributeLabelAndDescription($pm_element_code_or_id);
			
			return isset($va_label['description']) ? $va_label['description'] : '';
		}
		# ------------------------------------------------------------------
		// get HTML form element bundle for metadata element
		public function getAttributeDocumentationUrl($pm_element_code_or_id) {
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			$va_documentation_url = $t_element->get("documentation_url");

			return (isset($va_documentation_url) && $va_documentation_url) ? $va_documentation_url : false;
		}

		# ------------------------------------------------------------------
		// get HTML form element bundle for metadata element
		public function getAttributeLabelAndDescription($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_label_cache[$pm_element_code_or_id])) {
				$va_cached_labels = (BaseModelWithAttributes::$s_element_label_cache);
				return $va_cached_labels[$pm_element_code_or_id];
			}
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			$va_labels =  caExtractValuesByUserLocale($t_element->getPreferredLabels(null, false));
			foreach($va_labels as $vn_i => $va_labels_by_locale) {
				foreach($va_labels_by_locale as $vn_j => $va_label_values) {
					return $va_label_values;
				}
			}
			return '';
		}
		# ------------------------------------------------------------------
		// returns number of elements that comprise the attribute
		public function getNumberAttributeElements($pm_element_code_or_id) {
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			
			$qr_hier = $t_element->getHierarchy();
			
			return ($qr_hier) ? $qr_hier->numRows() : 0;
		}
		# ------------------------------------------------------------------
		/**
		 * Get HTML form element bundle for metadata element
		 *
		 * @param $pa_options array Options include:
		 *		elementsOnly = Render only form elements and structural formattings, but not headers, enclosing divs or controls. [Default is false]
		 *		batch = render for batch editor. [Default is false]
		 *
		 */
		public function getAttributeHTMLFormBundle($po_request, $ps_form_name, $pm_element_code_or_id, $ps_placement_code, $pa_bundle_settings, $pa_options) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }
			
			$vb_elements_only = caGetOption('elementsOnly', $pa_options, false);
			$vb_batch = caGetOption('batch', $pa_options, false);
			
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			if ($t_element->get('parent_id')) { 
				$this->postError(1930, _t('Element is not the root of the element set'), 'BaseModelWithAttributes->getAttributeHTMLFormBundle()');
				return false;
			}
			
			$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
			$o_view = new View($po_request, "{$vs_view_path}/bundles/");
			$o_view->setVar('graphicsPath', $pa_options['graphicsPath']);
			
			// get all elements of this element set
			$va_element_set = $t_element->getElementsInSet();

			// get attributes of this element attached to this row
			$va_attributes = $this->getAttributesByElement($pm_element_code_or_id);
			
			$t_attr = new ca_attributes();
			$t_attr->purify($this->purify());
			
			$va_element_codes = array();
			$va_elements_by_container = array();
			$vb_should_output_locale_id = ($t_element->getSetting('doesNotTakeLocale')) ? false : true;
			$va_element_value_defaults = array();
			$va_elements_without_break_by_container = array();
			$va_elements_break_by_container = array();
			
			$va_element_info = array();

			// fine element breaks by container
			foreach($va_element_set as $va_element) {
				if ($va_element['datatype'] == 0) {		// containers are not active form elements
					if(isset($va_element['settings']) && isset($va_element["settings"]["lineBreakAfterNumberOfElements"])) {
						$va_elements_break_by_container[$va_element['element_id']] = (int)$va_element["settings"]["lineBreakAfterNumberOfElements"];
					}
				}
			}
			
			foreach($va_element_set as $va_element) {
				$va_element_info[$va_element['element_id']] = $va_element;
				if (($va_element['datatype'] == 0) && ($va_element['parent_id'] > 0)) { continue; }

				
				$va_label = $this->getAttributeLabelAndDescription($va_element['element_id']);

				if(!isset($va_elements_without_break_by_container[$va_element['parent_id']])){
					$va_elements_without_break_by_container[$va_element['parent_id']] = 1;
				} else {
					$va_elements_without_break_by_container[$va_element['parent_id']] += 1;
				}

				$vs_br = "";
				if(isset($va_elements_break_by_container[$va_element['parent_id']])) {
					if ($va_elements_without_break_by_container[$va_element['parent_id']] == $va_elements_break_by_container[$va_element['parent_id']] + 1) {
						$va_elements_without_break_by_container[$va_element['parent_id']] = 1;
						$vs_br = "</td></tr></table><table class=\"attributeListItem\"><tr><td class=\"attributeListItem\">";
					}
				}

				if (isset($pa_bundle_settings['usewysiwygeditor']) && strlen($pa_bundle_settings['usewysiwygeditor']) == 0) {
					unset($pa_bundle_settings['usewysiwygeditor']);	// let null usewysiwygeditor bundle option fall back to metadata element setting
				}
				if(!is_array($va_label)) { 
					$va_label = array('name' => '???', 'description' => '');
				}
				$va_elements_by_container[$va_element['parent_id']][] = ($va_element['datatype'] == 0) ? '' : $vs_br.ca_attributes::attributeHtmlFormElement($va_element, array_merge($pa_bundle_settings, array_merge($pa_options, array(
					'label' => (sizeof($va_element_set) > 1) ? $va_label['name'] : '',
					'description' => $va_label['description'],
					't_subject' => $this,
					'request' => $po_request,
					'form_name' => $ps_form_name,
					'format' => ''
				))));
				
				//if the elements datatype returns true from renderDataType, then force render the element
				if(Attribute::renderDataType($va_element)) {
					return array_pop($va_elements_by_container[$va_element['element_id']]);
				}
				$va_element_ids[] = $va_element['element_id'];
				
				$vs_setting = Attribute::getValueDefault($va_element);
				if (strlen($vs_setting)) {
					$tmp_element = ca_metadata_elements::getInstance($va_element['element_id']);
					$va_element_value_defaults[$va_element['element_id']] = $tmp_element->getSetting($vs_setting);
				}
			}
			
			if ($vb_should_output_locale_id) {	// output locale_id, if necessary, in its' own special '_locale_id' container
				$va_elements_by_container['_locale_id'] = array('hidden' => false, 'element' => $t_attr->htmlFormElement('locale_id', '^ELEMENT', array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}locale_id_{n}", 'name' => "{fieldNamePrefix}locale_id_{n}", "value" => "{locale_id}", 'no_tooltips' => true, 'dont_show_null_value' => true, 'hide_select_if_only_one_option' => true, 'WHERE' => array('(dont_use_for_cataloguing = 0)'))));
				if (stripos($va_elements_by_container['_locale_id']['element'], "'hidden'")) {
					$va_elements_by_container['_locale_id']['hidden'] = true;
				}
			}
			
			$o_view->setVar('t_element', $t_element);
			$o_view->setVar('t_instance', $this);
			$o_view->setVar('request', $po_request);
			$o_view->setVar('id_prefix', $ps_form_name.'_attribute_'.$t_element->get('element_id'));
			$o_view->setVar('elements', $va_elements_by_container);
			$o_view->setVar('error_source_code', 'ca_attribute_'.$t_element->get('element_code'));
			$o_view->setVar('element_ids', $va_element_ids);
			$o_view->setVar('element_info', $va_element_info);
			$o_view->setVar('element_set_label', $this->getAttributeLabel($t_element->get('element_id')));
			$o_view->setVar('element_code', $t_element->get('element_code'));
			$o_view->setVar('placement_code', $ps_placement_code);
			$o_view->setVar('render_mode', $t_element->getSetting('render'));	// only set for list attributes (as of 26 Sept 2010 at least)
			
			if ($t_restriction = $this->getTypeRestrictionInstance($t_element->get('element_id'))) {
				// If batch mode force minimums to zero
				$o_view->setVar('max_num_repeats', $vb_batch  ? 9999 : $t_restriction->getSetting('maxAttributesPerRow'));
				
				$vn_min_repeats = $t_restriction->getSetting('minAttributesPerRow');
				if (($vn_min_repeats < 1) && (isset($va_element['settings']['requireValue'])) && ((bool)$va_element['settings']['requireValue'])) { $vn_min_repeats = 1; }
				
				$o_view->setVar('min_num_repeats', $vb_batch ? 0 : $vn_min_repeats);
				$o_view->setVar('min_num_to_display', $vb_batch ? 1 : $t_restriction->getSetting('minimumAttributeBundlesToDisplay'));
			}
			
			// these are lists of associative arrays representing attributes that were rejected in a save() action
			// during the current request. They are used to maintain the state of the form so the user can modify the
			// input that caused the error
			$o_view->setVar('failed_insert_attribute_list', $this->getFailedAttributeInserts($pm_element_code_or_id));
			$o_view->setVar('failed_update_attribute_list', $this->getFailedAttributeUpdates($pm_element_code_or_id));
		
			// set the list of existing attributes for the current row
			
			$vs_sort = $pa_bundle_settings['sort'];
			$vs_sort_dir = $pa_bundle_settings['sortDirection'];
			$va_attribute_list = $this->getAttributesByElement($t_element->get('element_id'), array('sort' => $vs_sort, 'sortDirection' => $vs_sort_dir));
			
			$o_view->setVar('attribute_list', $va_attribute_list);
			
			// pass list of element default values
			$o_view->setVar('element_value_defaults', $va_element_value_defaults);
			
			// pass bundle settings to view
			$o_view->setVar('settings', $pa_bundle_settings);
			
			// Is this being used in the batch editor?
			$o_view->setVar('batch', $vb_batch);
			$o_view->setVar('elementsOnly', $vb_elements_only);
			
			return $o_view->render($vb_elements_only ? 'ca_attributes_elements_only.php' : 'ca_attributes.php');
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function htmlFormElementForAttributeSearch($po_request, $pm_element_code_or_id, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			
			$vb_is_sub_element = (bool)($t_element->get('parent_id'));
			$t_parent = $vb_is_sub_element ? ca_metadata_elements::getInstance($t_element->get('parent_id')) : null;
			while($vb_is_sub_element && ($t_parent->get('datatype') == 0) && ($t_parent->get('parent_id') > 0)) {
				$t_parent = ca_metadata_elements::getInstance($t_parent->get('parent_id'));
			}
			
			$vs_element_code = $t_element->get('element_code');
			
			// get all elements of this element set
			$va_element_set = $vb_is_sub_element ? $t_parent->getElementsInSet() : $t_element->getElementsInSet();
			
			if ($vb_is_sub_element) {
				foreach($va_element_set as $vn_i => $va_element) {
					if ($va_element['element_code'] !== $vs_element_code) { unset($va_element_set[$vn_i]); }
				}
			}
			
			$t_attr = new ca_attributes();
			$t_attr->purify($this->purify());
			
			$va_element_codes = array();
			$va_elements_by_container = array();
			
			if (sizeof($va_element_set) > 1) {
				$vs_format = isset($pa_options['multivalueFormat']) ? $pa_options['multivalueFormat'] : null;
			} else {
				$vs_format = isset($pa_options['format']) ? $pa_options['format'] : null;
			}
			$pa_options['format'] = $vs_format;
			
			if ($vs_rel_types = join(";", caGetOption('restrictToRelationshipTypes', $pa_options, array()))) { $vs_rel_types = "/{$vs_rel_types}"; }
			
			foreach($va_element_set as $va_element) {
				$va_override_options = array();
				if ($va_element['datatype'] == 0) {		// containers are not active form elements
					continue;
				}
				
				$va_label = $this->getAttributeLabelAndDescription($va_element['element_id']);
				
				$vs_subelement_code = $this->tableName().'.'.($vb_is_sub_element ? $t_parent->get('element_code').'.' : '').(($vs_element_code !== $va_element['element_code']) ? "{$vs_element_code}." : "").$va_element['element_code'];
				
				$vs_value = (isset($pa_options['values']) && isset($pa_options['values'][$vs_subelement_code])) ? $pa_options['values'][$vs_subelement_code] : '';

				$va_element_opts = array_merge(array(
					'label' => $va_label['name'],
					'description' => $va_label['description'],
					't_subject' => $this,
					'table' => $this->tableName(),
					'request' => $po_request,
					'class' => $pa_options['class'],
					'nullOption' => '-',
					'value' => $vs_value,
					'forSearch' => true,
					'render' => (isset($va_element['settings']['render']) && ($va_element['settings']['render'] == 'lookup')) ? $va_element['settings']['render'] : isset($pa_options['render']) ? $pa_options['render'] : 'select'
				), array_merge($pa_options, $va_override_options));
				
				if (caGetOption('forSimpleForm', $pa_options, false)) { 
					unset($va_element_opts['nullOption']);
				}
				
				// We don't want to pass the entire set of values to ca_attributes::attributeHtmlFormElement() since it'll treat it as a simple list
				// of values for an individual element and the 'values' array is actually set to values for *all* elements in the form
				unset($va_element_opts['values']);
				$va_element_opts['values'] = '';
				
				// ... replace name of form element
				$vs_fld_name = $vs_subelement_code.$vs_rel_types; //str_replace('.', '_', $vs_subelement_code);
				if (caGetOption('asArrayElement', $pa_options, false)) { $vs_fld_name .= "[]"; } 
				
				if ($vs_force_value = caGetOption('force', $pa_options, false)) {
					$vs_form_element = caHTMLHiddenInput($vs_fld_name, array('value' =>$vs_force_value));
				} else {
					$vs_form_element = ca_attributes::attributeHtmlFormElement($va_element, $va_element_opts);
					//
					// prep element for use as search element
					//
					// ... replace value
					$vs_form_element = str_replace('{{'.$va_element['element_id'].'}}', $vs_value, $vs_form_element);
				
				
					// escape any special characters in jQuery selectors
					$vs_form_element = str_replace(
						"jQuery('#{fieldNamePrefix}".$va_element['element_id']."_{n}')",
						"jQuery('#".str_replace(array("[", "]", "."), array("\\\\[", "\\\\]", "\\\\."), $vs_fld_name)."')", 
						$vs_form_element
					);
					$vs_form_element = str_replace('{fieldNamePrefix}'.$va_element['element_id'].'_{n}', $vs_fld_name, $vs_form_element);
				
					$vs_form_element = str_replace('{n}', '', $vs_form_element);
					$vs_form_element = str_replace('{'. $va_element['element_id'].'}', '', $vs_form_element);
				}
				
				$va_elements_by_container[$va_element['parent_id'] ? $va_element['parent_id'] : $va_element['element_id']][] = $vs_form_element;
				
				// If the elements datatype returns true from renderDataType, then force render the element
				if(Attribute::renderDataType($va_element)) {
					return array_pop($va_elements_by_container[$va_element['element_id']]);
				}
				$va_element_ids[] = $va_element['element_id'];
			}
			
			$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
			$o_view = new View($po_request, "{$vs_view_path}/bundles/");
			
			$o_view->setVar('request', $po_request);
			$o_view->setVar('elements', $va_elements_by_container);
			$o_view->setVar('element_ids', $va_element_ids);
			$o_view->setVar('element_set_label', $this->getAttributeLabel($t_element->get('element_id')));
			
			return $o_view->render(caGetOption('view', $pa_options, 'ca_search_form_attributes.php'));
		}
		# ------------------------------------------------------------------
		public function getReferencedAttributeValues($pm_element_code_or_id, $pa_reference_limit_ids) {
			if (!($vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id))) { return null; }
			return ca_attributes::getReferencedAttributes($this->getDb(), $this->tableNum(), $pa_reference_limit_ids, array('element_id' => $vn_element_id));s;
		}
		# ------------------------------------------------------------------
		/** 
		 *
		 */
		public function htmlFormElement($ps_field, $ps_format=null, $pa_options=null) {
			if ($vs_source_id_fld_name = $this->getSourceFieldName()) {
				switch($ps_field) {
					case $vs_source_id_fld_name:
						if ((bool)$this->getAppConfig()->get('perform_source_access_checking')) {
							$pa_options['value'] = $this->get($ps_field);
							$pa_options['disableItemsWithID'] = caGetSourceRestrictionsForUser($this->tableName(), array('access' => __CA_BUNDLE_ACCESS_READONLY__, 'exactAccess' => true));
							return $this->getSourceListAsHTMLFormElement($pa_options['name'], array(), $pa_options);
						}
						break;
				}
			}
			
			return parent::htmlFormElement($ps_field, $ps_format, $pa_options);
		}
		# ------------------------------------------------------------------
		// --- Retrieval
		# ------------------------------------------------------------------
		// returns an array of all attributes attached to the current row
		public function getAttributes($pa_options=null, $pa_element_ids=null) {
			if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
			
			if (is_array($pa_element_ids) && sizeof($pa_element_ids)) {
				$va_element_ids = $pa_element_ids;
			} else {
				$va_element_ids = $this->getApplicableElementCodes(method_exists($this, "getTypeID") ? $this->getTypeID() : null, false, false);
			}
			if (!sizeof($va_element_ids)) { return array(); }
			
			$va_attributes = ca_attributes::getAttributes($this->getDb(), $this->tableNum(), $vn_row_id, array_keys($va_element_ids), $pa_options);
			$va_attributes_without_element_ids = array();
			if(!is_array($va_attributes)) {	return array(); }
			foreach($va_attributes as $vn_element_id => $va_values) {
				if (!is_array($va_values)) { continue; }
				$va_attributes_without_element_ids = array_merge($va_attributes_without_element_ids, $va_values);
			}
			return $va_attributes_without_element_ids;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns an array of all attributes with the specified element_id attached to the current row
		 *
		 * @param mixed $pm_element_code_or_id
		 * @param array $pa_options Options include
		 *		sort = 
		 *		sortDirection = 
		 *
		 * @return array
		 */
		public function getAttributesByElement($pm_element_code_or_id, $pa_options=null) {
			if (isset($pa_options['row_id']) && $pa_options['row_id']) {
				$vn_row_id = $pa_options['row_id'];
			} else {
				$vn_row_id = $this->getPrimaryKey();
			}
			if (!$vn_row_id) { return null; }

			$vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id);
			$va_attributes = ca_attributes::getAttributes($this->getDb(), $this->tableNum(), $vn_row_id, array($vn_element_id), $pa_options);
		
			$va_attribute_list =  is_array($va_attributes[$vn_hier_id = ca_metadata_elements::getElementHierarchyID($vn_element_id)]) ? $va_attributes[$vn_hier_id] : array();
		
			$vs_sort_dir = (isset($pa_options['sort']) && (in_array(strtolower($pa_options['sortDirection']), array('asc', 'desc')))) ? strtolower($pa_options['sortDirection']) : 'asc';	
			if ((isset($pa_options['sort']) && ($vs_sort = $pa_options['sort'])) || ($vs_sort_dir == 'desc')) {
				$va_tmp = array();
				foreach($va_attribute_list as $vn_id => $o_attribute) {
					$va_attribute_values = $o_attribute->getValues();
					
					$vb_isset = false;
					foreach($va_attribute_values as $o_attribute_value) {
						if ($o_attribute_value->getElementCode() == $vs_sort) {
							$va_tmp[$o_attribute_value->getSortValue()][$vn_id] = $o_attribute;
							$vb_isset = true;
						}
					}
					
					// If the sort key was not valid for some reason we default to using the attribute id
					// since if we don't then the attribute will disppear from the UI. We need to have something to order on.
					if (!$vb_isset) {
						$va_tmp[$vn_id][$vn_id] = $o_attribute;
					}
				}
				
				ksort($va_tmp);
			
				if ($vs_sort_dir == 'desc') {
					$va_tmp = array_reverse($va_tmp);
				}
				
				$va_attribute_list = array();
				foreach($va_tmp as $vs_key => $va_attr_values) {
					$va_attribute_list += $va_attr_values;
				}
			} else {
				// handle reverse sorting of "natural" (creation) order
				if ($vs_sort_dir == 'desc') {
					$va_attribute_list = array_reverse($va_attribute_list);
				}
			}
			
			return $va_attribute_list;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 *
		 * @param mixed $pm_element_code_or_id
		 * @param array $pa_options
		 *
		 *
		 * @return int Number of attributes attached to the current row for the specified metadata element
		 */
		public function getAttributeCountByElement($pm_element_code_or_id, $pa_options=null) {
			if (!($vn_row_id = $this->getPrimaryKey())) { 
				if (isset($pa_options['row_id']) && $pa_options['row_id']) {
					$vn_row_id = $pa_options['row_id'];
				} else {
					return null; 
				}
			}

			$vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id);
			return ca_attributes::getAttributeCount($this->getDb(), $this->tableNum(), $vn_row_id, $vn_element_id, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns single attribute value for the loaded row. 
		 * TODO: always returns the first value of the attribute; for simple single-value attributes this is
		 * the right thing to do, but this doesn't work with complex (multi-value) attributes since the first value
		 * is always the root container, which is always value-less.
		 */
		public function getSimpleAttributeValue($pm_element_code_or_id, $pn_index=0, $pa_options=null) {
			$va_attrs = $this->getAttributesByElement($pm_element_code_or_id);
			
			if (sizeof($va_attrs)) {
				if (($pn_index >= 0) && ($pn_index < sizeof($va_attrs))) { $pn_index = 0; }
				
				$va_attr = $va_attrs[$pn_index];
				
				$va_values = $va_attr->getValues();
				$va_value = $va_values[0];
				return $va_value->getDisplayValue($pa_options);
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Return the specific attribute with the specified attribute_id (assuming it's attached to the current row)
		 */
		public function getAttributeByID($pn_attribute_id, $pa_options=null) {
			if (isset($pa_options['row_id']) && $pa_options['row_id']) {
				$vn_row_id = $pa_options['row_id'];
			} else {
				$vn_row_id = $this->getPrimaryKey();
			}
			if (!$vn_row_id) { return null; }
			
			$t_attr = new ca_attributes($pn_attribute_id);
			if (!$t_attr->getPrimaryKey()) { return false; }
			if ((int)$t_attr->get('row_id') !== (int)$vn_row_id) { return false; }
			
			return $t_attr->getAttributeValues(array('returnAs' => 'attributeInstance'));
		}
		# ------------------------------------------------------------------
		/**
		 * Returns array of attributes, each of which is an array of values ready for display. 
		 * The returned array is indexed by attribute element ID. Each array value is an array
		 * indexed by attribute_id. The corresponding values are arrays of attribute values indexed by element_code
		 *
		 * @param $pm_element_code_or_id string|integer -
		 * @param $pn_row_id integer -
		 * @param $pa_options array -
		 *				convertLineBreaks = if set to true, will attempt to convert line break characters to HTML <p> and <br> tags; default is false.
		 *				locale = if set to a valid locale_id or locale code, values will be returned in locale *if available*, otherwise will fallback to values in languages that are available using the standard fallback mechanism. Default is to use user's current locale.
		 *				returnAllLocales = if set to true, values for all locales are returned, locale option is ignored and the returned array is indexed first by attribute_id and then by locale_id. Default is false.
		 *				indexByRowID = if true first index of returned array is $pn_row_id, otherwise it is the element_id of the retrieved metadata element	
		 *				indexValuesByValueID = index value array by value_id [default=false]
		 *				indexValuesByElementCode = index value array by element code [default=true]
		 *				indexValuesByElementCodeAndValueID = index value array by element codes, each of which has a sub-array indexed by value_id. Implies indexValuesByElementCode. [default=false]
		 *				convertCodesToDisplayText =
		 *				filter =
		 *				ignoreLocale = if set all values are returned regardless of locale, but in the flattened structure returned when returnAllLocales is false
		 *				sort = the full bundle specification for the element value or subvalue (if the element is a container) to sort returned values on.
		 *				sortDirection = determines the direction of the sort. Valid values are ASC (ascending) and DESC (descending). [Default=ASC]
		 * @return array
		 */
		public function getAttributeDisplayValues($pm_element_code_or_id, $pn_row_id, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$ps_filter_expression = caGetOption('filter', $pa_options, null);
			$pb_ignore_locale = caGetOption('ignoreLocale', $pa_options, null);
			$ps_sort = caGetOption('sort', $pa_options, null);
			$ps_sort_direction = caGetOption('sortDirection', $pa_options, 'asc', array('forceLowercase' => true, 'validValues' => array('asc', 'desc')));
			
			$pb_index_by_element_code = caGetOption('indexValuesByElementCode', $pa_options, true);
			$pb_index_by_value_id = caGetOption('indexValuesByValueID', $pa_options, false);
			$pb_index_by_element_code_and_value_id = caGetOption('indexValuesByElementCodeAndValueID', $pa_options, false);
			if ($pb_index_by_element_code_and_value_id) { $pb_index_by_element_code = true; }
			
			$va_attribute_list = $this->getAttributesByElement($pm_element_code_or_id, array('row_id' => $pn_row_id));
			if (!is_array($va_attribute_list)) { return array(); }
			$va_attributes = array();
			
			$vs_table_name = $this->tableName();
			$vs_container_element_code = ca_metadata_elements::getElementCodeForId($pm_element_code_or_id);
			
			$vs_sort_fld = null;
			if ($ps_sort) {
				if(is_array($ps_sort)) { $ps_sort = array_shift($ps_sort); }
				$va_sort_tmp = explode(".", $ps_sort);
				if (($va_sort_tmp[0] == $vs_table_name) && ($va_sort_tmp[1] == $vs_container_element_code)) {
					$vs_sort_fld = array_pop($va_sort_tmp);
				}
			}
			
			foreach($va_attribute_list as $o_attribute) {
				$va_values = $o_attribute->getValues();
				$va_raw_values = array();
				
				$va_display_values = array();
				
				$vs_sort_key = null;
				foreach($va_values as $o_value) {
					$vs_element_code = ca_metadata_elements::getElementCodeForId($o_value->getElementID());
					
					if (get_class($o_value) == 'ListAttributeValue') {
						$t_element = ca_metadata_elements::getInstance($o_value->getElementID());
						$vn_list_id = (!isset($pa_options['convertCodesToDisplayText']) || !(bool)$pa_options['convertCodesToDisplayText']) ? null : $t_element->get('list_id');
					} else {
						$vn_list_id = null;
					}
					
					if ($ps_filter_expression) { 
						$va_raw_values[($vs_container_element_code == $vs_element_code) ? "{$vs_table_name}.{$vs_element_code}" : "{$vs_table_name}.{$vs_container_element_code}.{$vs_element_code}"] = $va_raw_values[$vs_element_code] = $o_value->getDisplayValue(array('list_id' => $vn_list_id, 'returnIdno' => true));
					}
					
					if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
						$vs_converted_value = preg_replace("!(\n|\r\n){2}!","<p/>",$o_value->getDisplayValue(array_merge($pa_options, array('list_id' => $vn_list_id))));
						$vs_display_value = preg_replace("![\n]{1}!","<br/>",$vs_converted_value);
					} else {
						$vs_display_value = $o_value->getDisplayValue(array_merge($pa_options, array('list_id' => $vn_list_id)));
					}
					
					if ($pb_index_by_element_code) { 
						if ($pb_index_by_element_code_and_value_id) {
							$va_display_values[$vs_element_code][$o_value->getValueID()] = $vs_display_value; 
						} else {
							$va_display_values[$vs_element_code] = $vs_display_value; 
						}
					}
					if ($pb_index_by_value_id) { $va_display_values[$o_value->getValueID()] = $vs_display_value; }
					
					if ($vs_sort_fld && ($vs_sort_fld == $vs_element_code)) {
						$vs_sort_key = $o_value->getDisplayValue(array_merge($pa_options, array('getDirectDate' => true, 'list_id' => $vn_list_id)));
					}
				}
				if (!$vs_sort_key) { $vs_sort_key = '0'; }
				
				// Process filter if defined
				if ($ps_filter_expression && !ExpressionParser::evaluate($ps_filter_expression, $va_raw_values)) { continue; }
				
				if (isset($pa_options['indexByRowID']) && $pa_options['indexByRowID']) {
					$vs_index = $pn_row_id;
				} else {
					$vs_index = $o_attribute->getElementID();
				}
				
				
				if ($pb_ignore_locale) {
					$va_attributes[$vs_sort_key][$vs_index][$o_attribute->getAttributeID()] = $va_display_values;
				} else {
					$va_attributes[$vs_sort_key][$vs_index][(int)$o_attribute->getLocaleID()][$o_attribute->getAttributeID()] = $va_display_values;
				}
			}
			
			ksort($va_attributes);
			if ($ps_sort_direction == 'desc') { $va_attributes = array_reverse($va_attributes); }
			
			$va_sorted_attr = array();
			foreach($va_attributes as $vs_sort_key => $va_attr_by_index) {
				foreach($va_attr_by_index as $vs_index => $va_attr_by_id) {
					foreach($va_attr_by_id as $vs_id => $va_attr) {
						if ($pb_ignore_locale) {
							$va_sorted_attr[$vs_index][$vs_id] = $va_attr;
						} else {
							foreach($va_attr as $vn_attr_id => $va_val) {
								$va_sorted_attr[$vs_index][$vs_id][$vn_attr_id] = $va_val;
							}
						}
					}
				}
			}
			
			$va_attributes = $va_sorted_attr;
			
			if (!$pb_ignore_locale) { 
				if (!isset($pa_options['returnAllLocales']) || !$pa_options['returnAllLocales']) {
					// if desired try to return values in a preferred language/locale
					$va_preferred_locales = null;
					if (isset($pa_options['locale']) && $pa_options['locale']) {
						$va_preferred_locales = array($pa_options['locale']);
					}
					
					return caExtractValuesByUserLocale($va_attributes, null, $va_preferred_locales, array());
				}
			}
			return $va_attributes;
		}
		# ------------------------------------------------------------------
		/**
		 * Return raw value of attribute for a given row. The "raw" value is the display value, or values, joined with the specified
		 * delimiter and filtered on the current user locale.
		 *
		 * @param int $pn_row_id row_id attribute is attached to in the table the instance represents
		 * @param mixed $pm_element_code_or_id Element code or element_id of the metadata element to load the attribute for
		 * @param mixed $pm_sub_element_code_or_id Optional sub-element code or element_id to fetch value for. This is used to select a sub-element in complex attributes; if you want the top-level (aka root) element leave this set to null
		 * @param string $ps_delimiter Optional delimiter to use when multiple values are defined. Default is a comma (",").
		 * @return string The "raw" value
		 */
		public function getRawValue($pn_row_id, $pm_element_code_or_id, $pm_sub_element_code_or_id=null, $ps_delimiter=',', $pa_options=null) {
			$vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id);
			$va_attributes = ca_attributes::getAttributes($this->getDb(), $this->tableNum(), $pn_row_id, array($vn_element_id), array());
			
			if (!is_array($va_attributes = $va_attributes[$vn_element_id])) { return null; }
			
			$vn_sub_element_id = $pm_sub_element_code_or_id ? ca_metadata_elements::getElementID($pm_sub_element_code_or_id) : null;
			
			$va_ret_values = array();
			foreach($va_attributes as $o_attr) {
				if ($o_attr->getElementID() == $vn_element_id) { 
					$va_values = $o_attr->getDisplayValues(true, $pa_options);
					$va_ret_values[][(int)$o_attr->getLocaleID()] = $va_values[$vn_sub_element_id ? $vn_sub_element_id : $vn_element_id];
				}
			}
			
			$va_ret_values = array_values(caExtractValuesByUserLocale($va_ret_values));
			
			return join($ps_delimiter, $va_ret_values);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns associative array, keyed by primary key value with values being
		 * the preferred label of the row from a suitable locale, ready for display 
		 * 
		 * @param $pa_ids indexed array of primary key values to fetch attribute for
		 */
		public function getAttributeForIDs($pm_element_code_or_id, $pa_ids, $pa_options=null) {
			if (!($vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id))) { return null; }

			return caExtractValuesByUserLocale(ca_attributes::getAttributeValueForIDs($this->getDb(), $this->tableNum(), $pa_ids, $vn_element_id, $pa_options));
		}
		# ------------------------------------------------------------------
		/**
		 * Returns text of attributes in the user's currently selected locale, or else falls back to
	     * whatever locale is available
	     *
	     * Supported options
	     *	delimiter = text to use between attribute values; default is a single space
	     *	convertLineBreaks = if true will convert line breaks to HTML <br/> tags for display in a web browser; default is false
	     *	dontUseElementTemplate = By default any display template set in the metadata element is used if the $ps_template parameter is blank or null. Set this option to prevent the metadata element template from being used in any event. [Default is false]
		 */
		public function getAttributesForDisplay($pm_element_code_or_id, $ps_template=null, $pa_options=null) {
			if (!($vn_row_id = $this->getPrimaryKey())) { 
				if (!($vn_row_id = $pa_options['row_id'])) {
					return null; 
				}
			}
			if (!$pm_element_code_or_id || !($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (!isset($pa_options['convertCodesToDisplayText'])) { $pa_options['convertCodesToDisplayText'] = true; }
			
			$va_tmp = $this->getAttributeDisplayValues($vn_hier_id = ca_metadata_elements::getElementHierarchyID($pm_element_code_or_id), $vn_row_id, array_merge($pa_options, array('returnAllLocales' => false)));
		
			if (!$ps_template && ($vs_template_tmp = $t_element->getSetting('displayTemplate', true))&& !caGetOption('dontUseElementTemplate', $pa_options, false)) {	// grab template from metadata element if none is passed in $ps_template
				$ps_template = $vs_template_tmp;
			}
			$vs_delimiter = $t_element->getSetting('displayDelimiter', true);
			
			if (isset($pa_options['delimiter'])) {
				$vs_delimiter = $pa_options['delimiter'];
			}
			
			if ($ps_template) {
				unset($pa_options['template']);
				return caProcessTemplateForIDs($ps_template, $this->tableNum(), array($vn_row_id), array_merge($pa_options, array('requireLinkTags' => true, 'placeholderPrefix' => $this->tableName().'.'.$t_element->get('element_code'))));
			} else {
				// no template
				$va_attribute_list = array();
				foreach($va_tmp as $vn_id => $va_value_list) {
					foreach($va_value_list as $va_value) {
						foreach($va_value as $vs_element_code => $vs_value) {
							if (strlen($vs_value)) { 
								$va_attribute_list[] = $vs_value;
							}
						}
					}
				}

				//Allow getAttributesForDisplay to return an array value (for "special" returns such as coordinates or raw dates)
				// if the value returns only a single value and it's an array. This is useful for getting "specials" via SearchResult::get()
				if ((sizeof($va_attribute_list) === 1) && (is_array($va_attribute_list[0]))) { return $va_attribute_list[0]; }
				
				
				$vs_text = join($vs_delimiter, $va_attribute_list);
				
				if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
					$vs_text = caConvertLineBreaks($vs_text);
				}
				return $vs_text;
			}
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function getRawAttributeValuesForIDs($pm_element_code_or_id, $pa_ids, $pa_options=null) {
			if (!($vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id))) { return null; }

			return ca_attributes::getRawAttributeValuesForIDs($this->getDb(), $this->tableNum(), $pa_ids, $vn_element_id, $pa_options);
		}
		# ------------------------------------------------------------------
		// --- Utilities
		# ------------------------------------------------------------------
		/**
		 * Copies all attributes attached to the current row to the row specified by $pn_row_id
		 *
		 * @param int $pn_row_id
		 * @param array $pa_options
		 * @return bool True on success, false if an error occurred
		 *
		 * Supported options
		 *	restrictToAttributesByCodes = array of element codes to restrict the duplication to
		 *	restrictToAttributesByIds = array of element ids to restrict the duplication to
		 *
		 */
		public function copyAttributesTo($pn_row_id, $pa_options=null) {
			global $g_ui_locale_id;

			$vb_we_set_transaction = false;
			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));
				$vb_we_set_transaction = true;
			}

			$va_restrictToAttributesByCodes = caGetOption('restrictToAttributesByCodes', $pa_options, array());
			$va_restrictToAttributesByIds = caGetOption('restrictToAttributesByIds', $pa_options, array());

			$va_exclude_attributes_by_codes = caGetOption('excludeAttributesByCodes', $pa_options, array());
			if(!is_array($va_exclude_attributes_by_codes)) { $va_exclude_attributes_by_codes = []; }

			if (!($t_dupe = $this->_DATAMODEL->getInstanceByTableNum($this->tableNum()))) { return null; }
			$t_dupe->purify($this->purify());
			if (!$this->getPrimaryKey()) { return null; }
			if (!$t_dupe->load($pn_row_id)) { return null; }
			$t_dupe->setTransaction($this->getTransaction());

			$va_elements = $this->getApplicableElementCodes($t_dupe->getTypeID(), false, true);

			$vs_table = $this->tableName();
			foreach($va_elements as $vn_element_id => $vs_element_code) {
				// restrict by code
				if (sizeof($va_restrictToAttributesByCodes)>0) {
					if (!in_array($vs_element_code, $va_restrictToAttributesByCodes)) {
						continue;
					}
				}

				// restrict by id
				if (sizeof($va_restrictToAttributesByIds)>0) {
					if (!in_array($vn_element_id, $va_restrictToAttributesByIds)) {
						continue;
					}
				}

				// exclude by code
				if (in_array($vs_element_code, $va_exclude_attributes_by_codes)) {
					continue;
				}

				$va_vals = $this->get("{$vs_table}.{$vs_element_code}", array("returnAsArray" => true, "returnWithStructure" => true, "returnAllLocales" => true, 'forDuplication' => true));
				if (!is_array($va_vals)) { continue; }

				foreach($va_vals as $vn_id => $va_vals_by_locale) {
					foreach($va_vals_by_locale as $vn_locale_id => $va_vals_by_attr_id) {
						foreach($va_vals_by_attr_id as $vn_attribute_id => $va_val) {
							$va_val['locale_id'] = ($vn_locale_id) ? $vn_locale_id : $g_ui_locale_id;

							$t_dupe->addAttribute($va_val, $vs_element_code);
						}
					}
				}
			}

			$t_dupe->setMode(ACCESS_WRITE);
			$t_dupe->update();

			if($t_dupe->numErrors()) {
				$this->errors = $t_dupe->errors;
				if ($vb_we_set_transaction) { $this->removeTransaction(false);}
				return false;
			}
			if ($vb_we_set_transaction) { $this->removeTransaction(true);}
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Copies all attributes attached from the row specified by $pn_row_id to the current row
		 *
		 * @param int $pn_row_id
		 * @param array $pa_options
		 * @return bool True on success, false if an error occurred
		 *
		 * Supported options
		 *	restrictToAttributesByCodes = array of attributes codes to restrict the duplication
		 *	restrictToAttributesByIds = array of attributes ids to restrict the duplication
		 */
		public function copyAttributesFrom($pn_row_id, $pa_options=null) {
			if (!($t_dupe = $this->_DATAMODEL->getInstanceByTableNum($this->tableNum()))) { return null; }
			$t_dupe->purify($this->purify());
			if (!$this->getPrimaryKey()) { return null; }
			if (!$t_dupe->load($pn_row_id)) { return null; }

			if ($this->inTransaction()) {
				$t_dupe->setTransaction($this->getTransaction());
			}

			$vn_rc = $t_dupe->copyAttributesTo($this->getPrimaryKey(), $pa_options);
			$this->errors = $t_dupe->errors;
			return $vn_rc;
		}
		# ------------------------------------------------------------------
		// --- Methods to manage bindings between elements and tables
		# ------------------------------------------------------------------
		// add element to type (or general use when type_id=null) for this table
		public function addMetadataElementToType($pm_element_code_or_id, $pn_type_id) {
			
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_elements.php');
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_type_restrictions.php');
 
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			$t_restriction = new ca_metadata_type_restrictions();
			$t_restriction->setMode(ACCESS_WRITE);
			$t_restriction->set('table_num', $this->tableNum());
			$t_restriction->set('element_id', $t_element->getPrimaryKey());
			$t_restriction->set('type_id', $pn_type_id);	// TODO: validate $pn_type_id
			$t_restriction->insert();
			
			if ($t_restriction->numErrors()) {
				$this->postError(1980, _t("Couldn't add element to restriction list: %1", join('; ', $t_restriction->getErrors())), 'BaseModelWithAttributes->addMetadataElementToType()');
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------
		// remove element from type (or general use when type_id=null) for this table
		public function removeMetadataElementFromType($pm_element_code_or_id, $pn_type_id) {
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_elements.php');
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_type_restrictions.php');
 
			if (!($t_element = ca_metadata_elements::getInstance($pm_element_code_or_id))) {
				return false;
			}
			$t_restriction = new ca_metadata_type_restrictions();
			if ($t_restriction->load(array('element_id' => $t_element->getPrimaryKey(), 'type_id' => $pn_type_id, 'table_num' => $this->tableNum()))) {
				$t_restriction->setMode(ACCESS_WRITE);
				$t_restriction->delete();
				if ($t_restriction->numErrors()) {
					$this->postError(1981, _t("Couldn't remove element from restriction list: %1",join('; ', $t_restriction->getErrors())), 'BaseModelWithAttributes->addMetadataElementToType()');
					return false;
				}
			}
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function getAuthorityReferenceListHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings, $pa_options) {
			$va_errors = array();
			
			$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
			$o_view = new View($po_request, "{$vs_view_path}/bundles/");
			
			$o_view->setVar('t_instance', $this);
			$o_view->setVar('request', $po_request);
			$o_view->setVar('id_prefix', $ps_form_name.'_'.$ps_placement_code);
			
			// pass bundle settings to view
			$o_view->setVar('settings', $pa_bundle_settings);
			
			if (!($vn_datatype = $this->authorityElementDatatype())) {
				$va_errors[] = _t('Cannot be used as a reference');
			} else {
				$va_references = $this->getAuthorityElementReferences();
				
				$o_view->setVar('references', $va_references);
				
				// make search strings
				$va_element_list = $this->getAuthorityElementReferencesElementList();
				
				$va_search_strings = array();
				
				$vn_id = $this->getPrimaryKey();

				foreach($va_element_list as $vs_table => $va_elements_by_table) {
					foreach($va_elements_by_table as $vn_element_id => $va_element_info) {
						$va_search_strings[$vs_table][] = "{$vs_table}.".$va_element_info['element_code'].":{$vn_id}";
					}
				}
				$o_view->setVar('search_strings', $va_search_strings);
			}
			
			$o_view->setVar('errors', $va_errors);
			
			return $o_view->render('authority_references_list.php');
		}
		# ------------------------------------------------------------------
		/**
		 * Returns attribute data type code for authority element used to reference this model. 
		 * Eg. for the ca_entities model the __CA_ATTRIBUTE_VALUE_ENTITIES__ constant (numeric 22) is returned.
		 * Returns null if the model cannot be referenced using a metadata element.
		 *
		 * @return int
		 */
		public static function getAuthorityElementDatatypeList() {
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/CollectionsAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/EntitiesAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/LoansAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/MovementsAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/ObjectLotsAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/ObjectRepresentationsAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/ObjectsAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/OccurrencesAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/PlacesAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/StorageLocationsAttributeValue.php');
			require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/ListAttributeValue.php');
			
			return array(
				'ca_objects' => __CA_ATTRIBUTE_VALUE_OBJECTS__, 'ca_object_lots' => __CA_ATTRIBUTE_VALUE_OBJECTLOTS__, 'ca_entities' => __CA_ATTRIBUTE_VALUE_ENTITIES__, 'ca_places' => __CA_ATTRIBUTE_VALUE_PLACES__, 'ca_occurrences' => __CA_ATTRIBUTE_VALUE_OCCURRENCES__, 'ca_collections' => __CA_ATTRIBUTE_VALUE_COLLECTIONS__, 'ca_storage_locations' => __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__, 'ca_list_items' => __CA_ATTRIBUTE_VALUE_LIST__, 'ca_loans' => __CA_ATTRIBUTE_VALUE_LOANS__, 'ca_movements' => __CA_ATTRIBUTE_VALUE_MOVEMENTS__, 'ca_object_representations' => __CA_ATTRIBUTE_VALUE_OBJECTREPRESENTATIONS__
			);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns attribute data type code for authority element used to reference this model. 
		 * Eg. for the ca_entities model the __CA_ATTRIBUTE_VALUE_ENTITIES__ constant (numeric 22) is returned.
		 * Returns null if the model cannot be referenced using a metadata element.
		 *
		 * @return int
		 */
		public function authorityElementDatatype() {
			$va_element_datatypes = BaseModelWithAttributes::getAuthorityElementDatatypeList();
			
			if (isset($va_element_datatypes[$this->tableName()])) { 
				return $va_element_datatypes[$this->tableName()];
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Return list of metadata elements that have references to the current row.
		 *
		 * @param array $pa_options Option include:
		 *		row_id = Return references for specified row. [Default is to return references for currently loaded row]
		 *
		 * @return mixed 
		 */
		public function getAuthorityElementReferencesElementList($pa_options=null) {
			if (!($vn_datatype = $this->authorityElementDatatype())) { return array(); }
			if (!($vn_id = caGetOption('row_id', $pa_options, null))) { 
				if (!($vn_id = $this->getPrimaryKey())) {
					return array();
				}
			}
			
			$o_db = $this->getDb();
			
			switch($vn_datatype) {
				case 3: 	// Lists
					$qr_res = $o_db->query("
						SELECT DISTINCT a.element_id, a.table_num, md.element_code, md.parent_id, md.hier_element_id
						FROM ca_attribute_values cav
						INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
						INNER JOIN ca_metadata_elements AS md ON md.element_id = cav.element_id
						WHERE
							md.datatype = ? AND cav.item_id = ?
					", array($vn_datatype, $vn_id));
					break;
				default:
					$qr_res = $o_db->query("
						SELECT DISTINCT a.element_id, a.table_num, md.element_code, md.parent_id, md.hier_element_id
						FROM ca_attribute_values cav
						INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
						INNER JOIN ca_metadata_elements AS md ON md.element_id = cav.element_id
						WHERE
							md.datatype = ? AND cav.value_integer1 = ?
					", array($vn_datatype, $vn_id));
					break;
			}
			
			$va_elements = array();
			
			$o_dm = Datamodel::load();
			while($qr_res->nextRow()) {
				$va_row = $qr_res->getRow();
				$va_elements[$o_dm->getTableName($va_row['table_num'])][$va_row['element_id']] = array(
					'element_id' => $va_row['element_id'],
					'element_code' => $va_row['element_code'],
					'parent_id' => $va_row['parent_id'],
					'hier_element_id' => $va_row['hier_element_id']
				);
			}
			
			
			return $va_elements;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns a list of row_ids indexed by table that reference the currently loaded row using authority metadata elements.
		 *
		 * @param array $pa_options Option include:
		 *		countOnly = Return number of references only. [Default is false]
		 *		row_id = Return references for specified row. [Default is to return references for currently loaded row]
		 *
		 * @return mixed A list of references, key'ed on table number and then primary key value; values are arrays of element_ids. If the 'countOnly' option is set then an integer count of the references is returned.
		 */
		public function getAuthorityElementReferences($pa_options=null) {
			if (!($vn_datatype = $this->authorityElementDatatype())) { return null; }
			if (!($vn_id = caGetOption('row_id', $pa_options, null))) { 
				if (!($vn_id = $this->getPrimaryKey())) { 
					return null; 
				}
			}
			
			$pn_count_only = caGetOption('countOnly', $pa_options, false);
			
			$o_db = $this->getDb();
			
			switch($vn_datatype) {
				case 3: 	// Lists
					$qr_res = $o_db->query("
						SELECT cav.value_id, a.element_id, a.attribute_id, a.table_num, a.row_id 
						FROM ca_attribute_values cav
						INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
						WHERE
							cav.element_id IN (SELECT element_id FROM ca_metadata_elements WHERE datatype = ?) AND cav.item_id = ?
					", array($vn_datatype, $vn_id));
					break;
				default:
					$qr_res = $o_db->query("
						SELECT cav.value_id, a.element_id, a.attribute_id, a.table_num, a.row_id 
						FROM ca_attribute_values cav
						INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
						WHERE
							cav.element_id IN (SELECT element_id FROM ca_metadata_elements WHERE datatype = ?) AND cav.value_integer1 = ?
					", array($vn_datatype, $vn_id));
					break;
			}
			
			$va_references = array();
			
			$o_dm = Datamodel::load();
			while($qr_res->nextRow()) {
				$va_row = $qr_res->getRow();
				$va_references[$va_row['table_num']][$va_row['row_id']][] = $va_row['element_id'];
			}
			
			foreach($va_references as $vn_table_num => $va_rows) {
				$va_row_ids = array_keys($va_rows);
				if ((sizeof($va_row_ids) > 0) && $t_instance = $o_dm->getInstanceByTableNum($vn_table_num, true)) {
					if (!$t_instance->hasField('deleted')) { continue; }
					$vs_pk = $t_instance->primaryKey();
					$qr_del = $o_db->query("SELECT {$vs_pk} FROM ".$t_instance->tableName()." WHERE {$vs_pk} IN (?)".($t_instance->hasField('deleted') ? "AND deleted = 1" : ''), array($va_row_ids));
					
					while($qr_del && $qr_del->nextRow()) {
						unset($va_references[$vn_table_num][$qr_del->get($vs_pk)]);
					}
				}
			}
			
			if ($pn_count_only) {
				$vn_c = 0;
				foreach($va_references as $vn_table_num => $va_rows) {
					$vn_c += sizeof($va_rows);
				}
				
				return $vn_c;
			}
			
			return $va_references;
		}
		# ------------------------------------------------------------------
		/**
		 * Redirects all references to the currently loaded row using authority metadata elements to another row.
		 *
		 * @param int $pn_to_id Primary key of row to move references to 
		 * @param array $pa_options No options supported
		 *
		 * @return bool True on success, false on error, null if this model is not used for references or no row is loaded.
		 */
		public function moveAuthorityElementReferences($pn_to_id, $pa_options=null) {
			if (!($vn_datatype = $this->authorityElementDatatype())) { return null; }
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			
			if (is_array($va_references = $this->getAuthorityElementReferences()) && sizeof($va_references)) {
				$o_db = $this->getDb();
				
				switch($vn_datatype) {
					case 3: 	// Lists
						$qr_res = $o_db->query("
								UPDATE ca_attribute_values 
								SET item_id = ?, value_longtext1 = ? 
								WHERE 
									element_id IN (SELECT element_id FROM ca_metadata_elements WHERE datatype = ?)
									AND
									item_id = ?",
							array((int)$pn_to_id, (string)$pn_to_id, $vn_datatype, $vn_id));
						break;
					default:
						$qr_res = $o_db->query("
								UPDATE ca_attribute_values 
								SET value_integer1 = ?, value_longtext1 = ? 
								WHERE 
									element_id IN (SELECT element_id FROM ca_metadata_elements WHERE datatype = ?)
									AND
									value_integer1 = ?",
							array((int)$pn_to_id, (string)$pn_to_id, $vn_datatype, $vn_id));
						break;
				}
				
				if(!$o_db->numErrors()) {
					$o_indexer = $this->getSearchIndexer();
					foreach($va_references as $vs_table_num => $va_rows) {
						foreach($va_rows as $vn_row_id => $va_element_ids) {
							$va_changed_values = array();
							foreach($va_element_ids as $vn_element_id) {
								$va_changed_values["_ca_attribute_{$vn_element_id}"] = true;
							}
							$o_indexer->indexRow($vs_table_num, $vn_row_id, null, false, null, $va_changed_values);
						}
					}
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Removes all references to the currently loaded row using authority metadata elements.
		 *
		 * @param array $pa_options No options supported
		 *
		 * @return bool True on success, false on error, null if this model is not used for references or no row is loaded.
		 */
		public function deleteAuthorityElementReferences($pa_options=null) {
			if (!($vn_datatype = $this->authorityElementDatatype())) { return null; }
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			
			if (is_array($va_references = $this->getAuthorityElementReferences()) && sizeof($va_references)) {
				$o_db = $this->getDb();
				
				switch($vn_datatype) {
					case 3: 	// Lists
						$qr_res = $o_db->query("
								DELETE FROM ca_attribute_values 
								WHERE 
									element_id IN (SELECT element_id FROM ca_metadata_elements WHERE datatype = ?)
									AND
									item_id = ?",
							array($vn_datatype, $vn_id));
						break;
					default:
						$qr_res = $o_db->query("
								DELETE FROM ca_attribute_values 
								WHERE 
									element_id IN (SELECT element_id FROM ca_metadata_elements WHERE datatype = ?)
									AND
									value_integer1 = ?",
							array($vn_datatype, $vn_id));
						break;
				}
				
				if(!$o_db->numErrors()) {
					$o_indexer = $this->getSearchIndexer();
					foreach($va_references as $vs_table_num => $va_rows) {
						foreach($va_rows as $vn_row_id => $va_element_ids) {
							$va_changed_values = array();
							foreach($va_element_ids as $vn_element_id) {
								$va_changed_values["_ca_attribute_{$vn_element_id}"] = true;
							}
							$o_indexer->indexRow($vs_table_num, $vn_row_id, null, false, null, $va_changed_values);
						}
					}
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Get list of authority elements with references to other rows bound to the current row.
		 *
		 * @param array $pa_options Options include:
		 *		row_id = Return used elements for specified row. [Default is to return references for currently loaded row]
		 *		idsOnly = Return element_id values only. [Default is false]
		 *		rootIdsOnly = Return element_id values only. [Default is false]
		 *		omitLists = Don't include list elements. [Default is true]
		 */
		public function getAuthorityElementList($pa_options=null) {
			if (!($vn_id = caGetOption('row_id', $pa_options, null))) { 
				if (!($vn_id = $this->getPrimaryKey())) {
					return array();
				}
			}
			
			$pb_root_ids_only = caGetOption('rootIdsOnly', $pa_options, false);
			$pb_ids_only = caGetOption('idsOnly', $pa_options, false);
			
			$o_db = $this->getDb();
			
			$va_element_types = BaseModelWithAttributes::getAuthorityElementDatatypeList();
			if (caGetOption('omitLists', $pa_options, true)) { unset($va_element_types['ca_list_items']); }
			
			$qr_res = $o_db->query("
				SELECT count(*) count, a.element_id, a.table_num, md.element_code, md.element_id, md.parent_id, md.hier_element_id, md.datatype
				FROM ca_attribute_values cav
				INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
				INNER JOIN ca_metadata_elements AS md ON md.element_id = cav.element_id
				WHERE
					a.table_num = ? AND a.row_id = ? AND (cav.item_id > 0 OR cav.value_integer1 > 0) AND
					md.datatype IN (?)
				GROUP BY a.element_id, a.table_num, md.element_id
			", [$this->tableNum(), $vn_id, $va_element_types]);
	
			$va_elements = array();
			
			while($qr_res->nextRow()) {
				$va_row = $qr_res->getRow();
				
				if ($pb_root_ids_only) {
					$va_elements[$va_row['hier_element_id']] = true;
				} elseif($pb_ids_only) {
					$va_elements[$va_row['element_id']] = true;
				} else {
					$va_elements[$va_row['element_id']] = $va_row;
				}
			}
			
			if($pb_root_ids_only || $pb_ids_only) { return array_keys($va_elements); }
			if(caGetOption('countOnly', $pa_options, false)) { return sizeof($va_elements); }
			return $va_elements;
		}
		# ------------------------------------------------------------------
		/**
		 * Move specific attributes from the loaded row to another row. After the move is performed the specified 
		 * attributes will be referenced by the target only. Internally, the same attribute entries are reused for the target
		 * with attribute row_id values rewritten.
		 *
		 * Note that if a sub-element code or element_id is specified the entire container for the sub-element will be moved. 
		 *
		 * @param int $pn_to_id Primary key of row to move element data to 
		 * @param array $pa_element_codes_or_ids A list of element codes or element_ids of attributes to move. If sub-elements are specified the enclosing containers will be moved. 
		 * @param array $pa_options Options include:
		 *		row_id = Return used elements for specified row. [Default is to return references for currently loaded row]
		 *
		 * @return bool True on success, false on error, null if this model is not used for references or no row is loaded.
		 * @throws ApplicationException Thrown if $pn_to_id does not refer to a valid (existing, non-deleted) row.
		 */
		public function moveAttributes($pn_to_id, $pa_element_codes_or_ids, $pa_options=null) {
			if (!($vn_id = caGetOption('row_id', $pa_options, null))) { 
				if (!($vn_id = $this->getPrimaryKey())) {
					return null;
				}
			}
			
			if (is_array($pa_element_codes_or_ids) && sizeof($pa_element_codes_or_ids)) {
				$o_db = $this->getDb();
				
				$t_instance = $this->getAppDatamodel()->getInstanceByTableName($this->tableName(), false);
				if (!$t_instance->load($pn_to_id) || (bool)$t_instance->get('deleted')) { throw new ApplicationException(_t('Invalid target ID')); }
				
				$va_element_ids = [];
				$va_changed_values = [];
				foreach($pa_element_codes_or_ids as $vm_element) {
					if (!($vn_element_id = ca_metadata_elements::getElementID($vm_element))) { continue; }
					
					// is element valid for target? (we use top-level container if the element is a container sub-element)
					if (!$t_instance->hasElement(ca_metadata_elements::getElementCodeForId($vn_hier_element_id = ca_metadata_elements::getElementHierarchyID($vn_element_id)))) { continue; }
					$va_element_ids[$vn_hier_element_id] = true;
					$va_changed_values["_ca_attribute_{$vn_hier_element_id}"] = true;
				}
			
				if (sizeof($va_element_ids) > 0) {
					$qr_res = $o_db->query("
							UPDATE ca_attributes
							SET row_id = ?
							WHERE 
								element_id IN (?)
								AND
								table_num = ? AND row_id = ?",
						array((int)$pn_to_id, array_keys($va_element_ids), $this->tableNum(), $vn_id));

					if(!$o_db->numErrors()) {
						$o_indexer = $this->getSearchIndexer();
					
						// reindex source
						$o_indexer->indexRow($this->tableNum(), $vn_id, null, false, null, $va_changed_values);
						// reindex target
						$o_indexer->indexRow($this->tableNum(), $pn_to_id, null, false, null, $va_changed_values);
					}
				}
			}
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns true if bundle is valid for this model
		 * 
		 * @access public
		 * @param string $ps_bundle bundle name
		 * @param int $pn_type_id Optional record type
		 * @return bool
		 */ 
		public function hasBundle($ps_bundle, $pn_type_id=null) {
			$va_bundle_bits = explode(".", $ps_bundle);
			$vn_num_bits = sizeof($va_bundle_bits);
			
			if ($vn_num_bits == 1) {
				return ($this->hasElement($va_bundle_bits[0])) ? true : parent::hasBundle($ps_bundle, $pn_type_id);
			} elseif ($vn_num_bits > 1) {
				if ($va_bundle_bits[0] == $this->tableName()) {
					return ($this->hasElement($va_bundle_bits[1])) ? true : parent::hasBundle($ps_bundle, $pn_type_id);
				} elseif (($va_bundle_bits[0] != $this->tableName()) && ($t_rel = $this->getAppDatamodel()->getInstanceByTableName($va_bundle_bits[0], true))) {
					return $t_rel->hasBundle($ps_bundle, $pn_type_id);
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		# ------------------------------------------------------------------
		/**
		 * @param string $ps_element_code
		 * @param null|int $pn_type_id
		 * @param bool $pb_include_sub_element_codes
		 * @param array $pa_options Options include:
		 *		dontCache = Don't cache values [Default is false]
		 * @return bool
		 */
		public function hasElement($ps_element_code, $pn_type_id=null, $pb_include_sub_element_codes=false, $pa_options=null) {
			if (is_null($pn_type_id)) { $pn_type_id = $this->getTypeID(); }
			$va_codes = $this->getApplicableElementCodes($pn_type_id, $pb_include_sub_element_codes, caGetOption('dontCache', $pa_options, false));
			return (in_array($ps_element_code, $va_codes));
		}
		# ------------------------------------------------------------------
		/**
		 * Check is a value already is set for a metadata element
		 *
		 * @param mixed $pm_element_code_or_id The element code or id
		 * @param string $ps_value The value
		 * @param array $pa_options Options include:
		 *		transaction = A database transaction to execute the search within. [Default is null]
		 *		value_id = An optional value id to exclude when looking for existing values. [Default is null]
		 * @return bool True if a value exists
		 */
		static public function valueExistsForElement($pm_element_code_or_id, $ps_value, $pa_options=null) {
			if (!($vn_element_id = ca_metadata_elements::getElementID($pm_element_code_or_id))) { return false; }
			
			$o_db = ($o_trans = caGetOption('transaction', $pa_options, null)) ? $o_trans->getDb() : new Db();
			
			$va_sql_params = [$vn_element_id, $ps_value];
			$vs_value_sql = '';
			if($pn_value_id = caGetOption('value_id', $pa_options, null, ['castTo' => 'int'])) {
				$va_sql_params[] = $pn_value_id;
				$vs_value_sql = " AND cav.value_id <> ?";
			}
			
			$qr_values = $o_db->query("
				SELECT cav.value_id, ca.attribute_id, ca.table_num, ca.row_id
				FROM ca_attribute_values cav
				INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
				WHERE 
					cav.element_id = ? AND cav.value_longtext1 = ? {$vs_value_sql}", $va_sql_params);

			// filter deleted
			$va_ids_by_table = [];
			while($qr_values->nextRow()) {
				$va_ids_by_table[$qr_values->get('table_num')][] = $qr_values->get('row_id');
			}
			
			$o_dm = Datamodel::load();
			foreach($va_ids_by_table as $vn_table_num => $va_row_ids) {
				if (!($t_instance = $o_dm->getInstanceByTableNum($vn_table_num, true))) {
					continue;
				}
				if (!$t_instance->hasField('deleted')) { continue; }
				$vs_table_name = $o_dm->getTableName($vn_table_num);
				$vs_table_pk = $o_dm->primaryKey($vn_table_num);
				
				$qr_existant = $o_db->query("
					SELECT {$vs_table_pk}
					FROM {$vs_table_name}
					WHERE 
						deleted = 0 AND {$vs_table_pk} IN (?)
					
				", [$va_row_ids]);
				if($qr_existant->numRows()>0) {
					return true;
				}
			}
			return false;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of metadata element codes applicable to the specified type. If no type is specified 
		 * then all attributes applicable to the model as a whole (regardless of type restrictions) are returned.
		 *
		 * Normally only top-level attribute codes are returned. This is good: in general you should only be dealing with attributes
		 * via the top-level element. However, there are a few cases where you might need an inventory of *all* element codes that can
		 * be attached to a model, even those that are part of an element hierarchy. Setting the $pb_include_sub_element_codes to true
		 * will include sub-elements in the returned list.
		 */
 		public function getApplicableElementCodes($pn_type_id=null, $pb_include_sub_element_codes=false, $pb_dont_cache=true) {
			if (!$pn_type_id) { $pn_type_id = null; }
 			 
			if (!$pb_dont_cache && is_array($va_tmp = BaseModelWithAttributes::$s_applicable_element_code_cache[$this->tableNum().'/'.$pn_type_id.'/'.($pb_include_sub_element_codes ? 1 : 0)])) {
				return $va_tmp;
			}
 			
 			$vs_type_sql = '';
 			if (
 				isset($this->ATTRIBUTE_TYPE_ID_FLD) && $pn_type_id
 			) {
 				$va_ancestors = array();
 				if ($t_type_instance = $this->getTypeInstance()) {
 					$va_ancestors = $t_type_instance->getHierarchyAncestors(null, array('idsOnly' => true, 'includeSelf' => true));
 					if (is_array($va_ancestors)) { array_pop($va_ancestors); } // remove hierarchy root
 				}
 				
 				if (sizeof($va_ancestors) > 1) {
 					$vs_type_sql = '((camtr.type_id = '.intval($pn_type_id).') OR (camtr.type_id IS NULL) OR ((camtr.include_subtypes = 1) AND camtr.type_id IN ('.join(',', $va_ancestors).'))) AND ';
 				} else {
 					$vs_type_sql = '((camtr.type_id = '.intval($pn_type_id).') OR (camtr.type_id IS NULL)) AND ';
 				}
 			} elseif (is_subclass_of($this, "BaseRelationshipModel")) {
 				if ($pn_type_id > 0) { $vs_type_sql = '((camtr.type_id = '.intval($pn_type_id).') OR (camtr.type_id IS NULL)) AND '; }
			} 
 			
 			$o_db = $this->getDb();
 			
 			$qr_res = $o_db->query("
 				SELECT camtr.element_id, came.element_code, cmel.name, cmel.description, cmel.locale_id
 				FROM ca_metadata_type_restrictions camtr
 				INNER JOIN ca_metadata_elements AS came ON camtr.element_id = came.element_id
 				INNER JOIN ca_metadata_element_labels AS cmel ON cmel.element_id = came.element_id
 				WHERE
 					$vs_type_sql (camtr.table_num = ?) AND came.parent_id IS NULL
 			", (int)$this->tableNum());
 			$va_codes = array();
 			
 			$va_element_labels_by_locale = array();
 			while($qr_res->nextRow()) {
 				$vn_element_id = (int)$qr_res->get('element_id');
 				$vs_element_code = (string)$qr_res->get('element_code');
 				$vn_locale_id = (int)$qr_res->get('locale_id');
 				
 				$va_element_labels_by_locale[$vn_element_id][$vn_locale_id] = $va_element_labels_by_locale[$vs_element_code][$vn_locale_id] = $qr_res->getRow();
 				if (isset($va_codes[$vn_element_id])) { continue; }
 				$va_codes[$vn_element_id] = $vs_element_code;
 			}
 			if (!is_array(BaseModelWithAttributes::$s_element_label_cache)) { BaseModelWithAttributes::$s_element_label_cache = array(); }
 			BaseModelWithAttributes::$s_element_label_cache += caExtractValuesByUserLocale($va_element_labels_by_locale);
 			
 			if ($pb_include_sub_element_codes && sizeof($va_codes)) {
 				$qr_res = $o_db->query("
					SELECT came.element_id, came.element_code
					FROM ca_metadata_elements came
					WHERE
						came.hier_element_id IN (".join(',', array_keys($va_codes)).")
				");
				while($qr_res->nextRow()) {
					$va_codes[$qr_res->get('element_id')] = $qr_res->get('element_code');
				}
 			}
 			BaseModelWithAttributes::$s_applicable_element_code_cache[$this->tableNum().'/'.$pn_type_id.'/'.($pb_include_sub_element_codes ? 1 : 0)] = $va_codes;
 			return $va_codes;
 		}
 		# ------------------------------------------------------------------
		/**
		 *
		 */
 		public function getApplicableElementCodesForTypes($pa_type_ids, $pb_include_sub_element_codes=false, $pb_dont_cache=true) {
 			$va_codes = array();
 			foreach($pa_type_ids as $vn_i => $vn_type_id) {
 				$va_tmp = $this->getApplicableElementCodes($vn_type_id, $pb_include_sub_element_codes, $pb_dont_cache);
 				foreach($va_tmp as $vn_element_id => $vs_element_code) {
 					$va_codes[$vn_element_id] = $vs_element_code;
 				}
 			}
 			return $va_codes;
 		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		 public function isValidMetadataElement($pn_element_code_or_id, $pb_include_sub_element_codes=false) {
		 	$vn_element_id = ca_metadata_elements::getElementID($pn_element_code_or_id);
		 	$va_codes = $this->getApplicableElementCodes(null, $pb_include_sub_element_codes, false);
		
		 	return (bool)$va_codes[$vn_element_id];
		 }
		# ------------------------------------------------------------------
		/**
		 * Returns an instance of ca_metadata_type_restrictions containing the row (and settings) for the
		 * specified element_set (identified by $pn_element_id) as it relates to the current row; returns
		 * null if element_id is not applicable to the current row
		 */
		public function getTypeRestrictionInstance($pn_element_id) {
			$t_restriction = new ca_metadata_type_restrictions();
			if (is_subclass_of($this, 'BaseRelationshipModel') && ($t_restriction->load(array('element_id' => (int)$pn_element_id, 'table_num' => (int)$this->tableNum(), 'type_id' => $this->get('type_id'))))) {
				return $t_restriction;
			} elseif ($t_restriction->load(array('element_id' => (int)$pn_element_id, 'table_num' => (int)$this->tableNum(), 'type_id' => $this->get($this->ATTRIBUTE_TYPE_ID_FLD)))) {
				return $t_restriction;
			} elseif ($t_restriction->load(array('element_id' => (int)$pn_element_id, 'table_num' => (int)$this->tableNum(), 'type_id' => null))) {
				return $t_restriction;
			}
			
			// try going up the hierarchy to find one that we can inherit from
			if ($t_type_instance = $this->getTypeInstance()) {
				$va_ancestors = $t_type_instance->getHierarchyAncestors(null, array('idsOnly' => true));
				if (is_array($va_ancestors)) {
					array_pop($va_ancestors); // get rid of root
					if (sizeof($va_ancestors)) {
						$qr_res = $this->getDb()->query("
							SELECT restriction_id
							FROM ca_metadata_type_restrictions
							WHERE
								type_id IN (?) AND table_num = ? AND include_subtypes = 1 AND element_id = ?
						", array($va_ancestors, (int)$this->tableNum(), (int)$pn_element_id));
						
						if ($qr_res->nextRow()) {
							if ($t_restriction->load($qr_res->get('restriction_id'))) {
								return $t_restriction;
							}
						}
					}
				}
			}
			
			return null;
		}
		# ------------------------------------------------------------------
		# State maintenance
		# ------------------------------------------------------------------
		public function setFailedAttributeInserts($pm_element_code_or_id, $pa_attribute_values) {
			$this->opa_failed_attribute_inserts[ca_metadata_elements::getElementID($pm_element_code_or_id)] = $pa_attribute_values;
		}
		# ------------------------------------------------------------------
		public function getFailedAttributeInserts($pm_element_code_or_id) {
			$vs_k = ca_metadata_elements::getElementID($pm_element_code_or_id);
			return isset($this->opa_failed_attribute_inserts[$vs_k]) ? $this->opa_failed_attribute_inserts[$vs_k] : null;
		}
		# ------------------------------------------------------------------
		public function setFailedAttributeUpdates($pm_element_code_or_id, $pa_attribute_values) {
			$this->opa_failed_attribute_updates[ca_metadata_elements::getElementID($pm_element_code_or_id)] = $pa_attribute_values;
		}
		# ------------------------------------------------------------------
		public function getFailedAttributeUpdates($pm_element_code_or_id) {
			$vs_k = ca_metadata_elements::getElementID($pm_element_code_or_id);
			return isset($this->opa_failed_attribute_updates[$vs_k]) ? $this->opa_failed_attribute_updates[$vs_k] : null;
		}
	}
