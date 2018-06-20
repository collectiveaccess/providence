<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Attribute.php : 
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */ 	
require_once(__CA_LIB_DIR__.'/Configuration.php');

require_once(__CA_LIB_DIR__.'/Attributes/Values/ContainerAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/TextAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/DateRangeAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/ListAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/GeocodeAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/UrlAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/CurrencyAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/LengthAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/WeightAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/TimeCodeAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/IntegerAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/NumericAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/LCSHAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/GeoNamesAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/FileAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/MediaAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/TaxonomyAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/InformationServiceAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/FloorplanAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/ColorAttributeValue.php');
 
define("__CA_ATTRIBUTE_VALUE_OBJECTREPRESENTATIONS__", 21);
define("__CA_ATTRIBUTE_VALUE_ENTITIES__", 22);
define("__CA_ATTRIBUTE_VALUE_PLACES__", 23);
define("__CA_ATTRIBUTE_VALUE_OCCURRENCES__", 24);
define("__CA_ATTRIBUTE_VALUE_COLLECTIONS__", 25);
define("__CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__", 26);
define("__CA_ATTRIBUTE_VALUE_LOANS__", 27);
define("__CA_ATTRIBUTE_VALUE_MOVEMENTS__", 28);
define("__CA_ATTRIBUTE_VALUE_OBJECTS__", 29);
define("__CA_ATTRIBUTE_VALUE_OBJECTLOTS__", 30);
 
	class Attribute {
 		# ------------------------------------------------------------------
 		private $opa_values;
 		private $opn_attribute_id;
 		private $opn_element_id;
 		private $opn_locale_id;
 		
 		static $s_instance_cache = array();
 		static $s_attribute_types = array();
 		
 		# ------------------------------------------------------------------
 		public function __construct($pa_values=null) {
 			$this->opa_values = array();
 			
 			if (is_array($pa_values)) {
 				$this->setInfo($pa_values);
 			}
 		}
 		# ------------------------------------------------------------------
 		public function setInfo($pa_values) {
 			foreach($pa_values as $vs_key => $vs_val) {
 				if (!in_array($vs_key, array('attribute_id', 'element_id', 'locale_id', 'table_num', 'row_id'))) { continue; }
 				$this->{'opn_'.$vs_key} = $vs_val;
 			}
 			
 			return true;
 		}
 		# ------------------------------------------------------------------
 		public function addValuesFromRows($pa_rows) {
 			foreach($pa_rows as $va_row) {
 				$this->addValueFromRow($va_row);
 			}
 		}
 		# ------------------------------------------------------------------
 		public function addValueFromRow($pa_row) {
 			if ($t_value = Attribute::getValueInstance($pa_row['datatype'], $pa_row)) {
 				$this->opa_values[] = $t_value;
				return true;
 			}
 			return false;
 		}
 		# ------------------------------------------------------------------
 		public function addValues($pa_values) {
 			array_merge($this->opa_values, $pa_values);
 			
 			return $this->numValues();
 		}
 		# ------------------------------------------------------------------
 		public function getLocaleID() {
 			return $this->opn_locale_id;
 		}
 		# ------------------------------------------------------------------
 		public function getAttributeID() {
 			return $this->opn_attribute_id;
 		}
 		# ------------------------------------------------------------------
 		public function getElementID() {
 			return $this->opn_element_id;
 		}
 		# ------------------------------------------------------------------
 		public function getTableNum() {
 			return $this->opn_table_num;
 		}
 		# ------------------------------------------------------------------
 		public function getRowID() {
 			return $this->opn_row_id;
 		}
 		# ------------------------------------------------------------------
 		public function getValues() {
 			return $this->opa_values;
 		}
 		# ------------------------------------------------------------------
 		public function getDisplayValues($pb_index_with_element_ids=false, $pa_options=null) {
 			if (!is_array($this->opa_values)) { return null; }
 			
 			$va_display_values = array();
 			foreach($this->opa_values as $o_val) {
 				$va_display_values[$pb_index_with_element_ids ? $o_val->getElementID() : $o_val->getElementCode()] = $o_val->getDisplayValue($pa_options);
 			}
 			return $va_display_values;
 		}
 		# ------------------------------------------------------------------
 		public function numValues() {
 			return sizeof($this->opa_values);
 		}
 		# ------------------------------------------------------------------
 		# Value instances
 		# ------------------------------------------------------------------
 		/**
 		 * Returns an associative array of available attribute types
 		 * They keys of the name are the attribute codes 
 		 * (1-byte int as stored in ca_attribute_values datatype field), 
 		 * the values are the attribute names
 		 */
 		static public function getAttributeTypes() {
 			if (Attribute::$s_attribute_types) { return Attribute::$s_attribute_types; }
			
 			$o_attribute_types = Configuration::load(__CA_CONF_DIR__.'/attribute_types.conf');
 			return Attribute::$s_attribute_types = $o_attribute_types->getList('types');
 		}
 		# ------------------------------------------------------------------
 		static public function renderDataType($pa_element_info,$pa_options=null) {
 			$vs_element = Attribute::getValueInstance($pa_element_info['datatype']);
 			if(method_exists($vs_element,'renderDataType')) {
 				return $vs_element->renderDataType();
 			}
 			return false;
 		}
 		# ------------------------------------------------------------------
 		static public function getValueDefault($pa_element_info) {
 			$vs_element = Attribute::getValueInstance($pa_element_info['datatype']);
 			return ($vs_element) ? $vs_element->getDefaultValueSetting() : null;
 		}
 		# ------------------------------------------------------------------
 		static public function getValueInstance($pn_datatype, $pa_value_array=null, $pb_use_cache=false) {
 			if ($pb_use_cache && Attribute::$s_instance_cache[$pn_datatype]) {
 				$o_attr = Attribute::$s_instance_cache[$pn_datatype];
 				if (is_array($pa_value_array)) { $o_attr->loadValueFromRow($pa_value_array); }
 				return $o_attr;
 			}
 			
 			$va_types = Attribute::getAttributeTypes();
 			if (isset($va_types[$pn_datatype])) {
 				// we look for a class in lib/Attributes/Values with the datatype name + 'AttributeValue'
 				$vs_classname = $va_types[$pn_datatype].'AttributeValue';
 				
 				if(!class_exists($vs_classname)) {
 					if (!file_exists(__CA_LIB_DIR__.'/Attributes/Values/'.$vs_classname.'.php')) { return null; }
 					include_once(__CA_LIB_DIR__.'/Attributes/Values/'.$vs_classname.'.php');
 				}
 				return Attribute::$s_instance_cache[$pn_datatype] = new $vs_classname($pa_value_array);
 			}
 			return null;
 		}
 		# ------------------------------------------------------------------
 		static public function getSortFieldForDatatype($pn_datatype) {
 			if ($t_instance = Attribute::getValueInstance($pn_datatype, null, true)) {
 				return $t_instance->sortField();
 			}
 			return null;
 		}
 		# ------------------------------------------------------------------
 		static public function valueHTMLFormElement($pn_datatype, $pa_element_info, $pa_options=null) {
 			if ($o_value = Attribute::getValueInstance($pn_datatype)) {
 				return $o_value->htmlFormElement($pa_element_info, $pa_options);	
 			}
 			return null;
 		}
 		# ------------------------------------------------------------------
	}
	
