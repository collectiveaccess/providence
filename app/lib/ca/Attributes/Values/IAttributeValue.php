<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/IAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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
 	
 	require_once(__CA_LIB_DIR__.'/core/Error/IErrorSetter.php');
 
	interface IAttributeValue extends IErrorSetter {
		// $pa_value_array is used to initialize the value object by calling loadValueFromRow() and
		// is an associative array containing *all* of the ca_attribute_values table value_* fields
		// 
		// If you are using the AttributeValue instance to represent an existing value you pass it either
		// in the constructor or by a subsequent call to loadValueFromRow()
		public function __construct($pa_value_array=null);
		
		public function loadValueFromRow($pa_value_array);
		
		// returns displayable value for attribute; this value can be used in form elements for editing (eg. for dates, this value is parse-able)
 		public function getDisplayValue();
 		
 		// ----
 		
 		// Parses the value and, if valid, returns a populated associative array with keys equal to the value fields 
 		// in the ca_attribute_values table. The returned value is intended to be written into a ca_attribute_values
 		// row. If the row is not valid, will return null and set errors
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null);
 		
 		// Return an HTML form element for the attribute value with the passed element info (an associative array
 		// containing a row from the ca_metadata_elements table
 		// $pa_options is an optional associative array of form options; these are type-specific.
 		public function htmlFormElement($pa_element_info, $pa_options=null);
 		
 		// Loads type specific data into the value object out of the same associative array you'd pass to loadValueFromRow()
 		// The default implementation of loadValueFromRow() in the AttributeValue baseclass calls this so there is
 		// normally no need to call this yourself
 		public function loadTypeSpecificValueFromRow($pa_value_array);
 		
 		public function getAvailableSettings($pa_element_info=null);
 		
 		// Checks validity of setting value for attribute; used by ca_metadata_elements to validate settings before they are saved.
		public function validateSetting($pa_element_info, $ps_key, $ps_value, &$ps_error);
 		
 		//Returns name of field in ca_attribute_values to use for sort operations
		public function sortField();
 		
 		// Return name of setting whose value is to be used for attribute value default
 		public function getDefaultValueSetting();
 		
	}
 ?>