<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/AttributeValue.php : 
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	
 	require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 
	abstract class AttributeValue extends BaseObject {
 		# ------------------------------------------------------------------
 		private $opn_element_id;
 		private $ops_element_code;
 		private $opn_value_id;
 		private $opa_source_info;
 		private $ops_sort_value;
 		
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct();
 			if (is_array($pa_value_array)) {
				$this->loadValueFromRow($pa_value_array);
 			}
 		}
 		# ------------------------------------------------------------------
 		public function loadValueFromRow($pa_value_array) {
 			$this->opn_element_id = isset($pa_value_array['element_id']) ? $pa_value_array['element_id'] : null;
 			$this->ops_element_code = isset($pa_value_array['element_code']) ? $pa_value_array['element_code'] : null;
 			$this->opn_value_id = isset($pa_value_array['value_id']) ? $pa_value_array['value_id'] : null;
 			$this->opa_source_info = isset($pa_value_array['source_info']) ? $pa_value_array['source_info'] : null;
 			
 			$this->loadTypeSpecificValueFromRow($pa_value_array);
 			
 			$this->ops_sort_value = null;
 			if ($vs_sort_field = $this->sortField()) {
 				$this->ops_sort_value = $pa_value_array[$vs_sort_field];
 			}
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Parse attribute value and return parsed component values as an array of keys corresponding to ca_attribute_values field.
 		 * If parse is successful an array is returned. If parse is unsuccessful and an error message should be displayed, boolean 
 		 * false is returned and the error(s) posted via BaseObject::postError() [inherited by this class]. If the parse is silently
 		 * unsuccessful - that is nothing can be parsed but no error message should be displayed to the user - then null is returned 
 		 * and no errors posted.
 		 *
 		 * @param string $ps_value The value to parse
 		 * @param array An array of information about the attribute for which we are parsing the value, including settings
 		 * @param mixed An array of parsed component values on success, false on failure and null on "silent" failure (eg. failed but don't show an error message to the user)
 		 */
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			return null;
 		}
 		# ------------------------------------------------------------------
 		public function getElementCode() {
 			return $this->ops_element_code;
 		}
 		# ------------------------------------------------------------------
 		public function getElementID() {
 			return $this->opn_element_id;
 		}
 		# ------------------------------------------------------------------
 		public function getValueID() {
 			return $this->opn_value_id;
 		}
 		# ------------------------------------------------------------------
 		protected function getSettingValuesFromElementArray($pa_element_info, $pa_settings) {
 			if (!is_array($pa_settings)) { $pa_settings = array($pa_settings); }
 			$va_setting_values = array();
 			$va_settings = null;
 			foreach($pa_settings as $vs_setting) {
				if (isset($pa_element_info['settings'][$vs_setting])) {
					$va_setting_values[$vs_setting] = $pa_element_info['settings'][$vs_setting];
				} else {
					// get default
					if (!$va_settings) { $va_settings = $this->getAvailableSettings(); }
					if(isset($va_settings[$vs_setting])) {
						$va_setting_values[$vs_setting] = $va_settings[$vs_setting]['default'];
					}
				}
			}
			
			return $va_setting_values;
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return name of setting whose value to use as default for attribute value
 		 * Return value of null indicates that there's no default value to be used
 		 */
 		public function getDefaultValueSetting() {
 			return null;
 		}
 		# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns value suitable for sorting
		 * 
		 * @return string Sortable value
		 */
		public function getSortValue() {
			return $this->ops_sort_value;
		}
		# ------------------------------------------------------------------
		/**
		 * Checks validity of setting value for attribute; used by ca_metadata_elements to
		 * validate settings before they are saved.
		 *
		 * @param array $pa_element_info Associative array containing data from a ca_metadata_elements row
		 * @param string $ps_setting_key Alphanumeric setting code
		 * @param string $ps_value Value of setting
		 * @param string $ps_error Variable to place error message in, if setting fails validation
		 * @return boolean True if value is valid for setting, false if not. If validation fails an error message is returned in $ps_error
		 */
		public function validateSetting($pa_element_info, $ps_setting_key, $ps_value, &$ps_error) {
			$ps_error = '';
			return true;
		}
 		# ------------------------------------------------------------------
	}
 ?>