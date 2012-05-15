<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/RepresentationAnnotationPropertyCoders/IRepresentationAnnotationPropertyCoder.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 	
 
	interface IRepresentationAnnotationPropertyCoder {
		
		/* return type code for annotation properties */
		public function getType();
		
		/* return list of properties for annotation type */
		public function getPropertyList();
		
		/* Return associative array containing information about the specified property */
		public function getPropertyInfo($ps_property);
		
		/* return HTML form element for specified property */
		public function htmlFormElement($ps_property, $pa_attributes=null);
		
		/* Set the specified property; return true on success, false if value is invalid, null if property doesn't exist */
		public function setProperty($ps_property, $pm_value);
		
		/* returns the property value or null if the property doesn't exist */
		public function getProperty($ps_property, $pb_return_raw_value=false);
		
		/* Sets the property values using an associative array with property names as keys and raw form input as values */
		/* Typically the array is extracted directly from the database */
		public function setPropertyValues($pa_values);
		
		/* Returns an associative array with property names as keys and property values as values */
		public function getPropertyValues();
		
		/* Validate property values prior to insertion into the database. This function checks whether the values make sense. */
		/* returns true if the values are ok, false if they are not */
		public function validate();
		
		/* Returns the media version to use for display while doing annotation */
		public function getDisplayMediaVersion($ps_type=null);
		
		/* Returns true if the annotation uses the bundle-based editor */
		/* Timebased annotations do use the bundle-based editor; other types, such as image annotations */
		/* use a custom client (for images, the Bischen editor) rather than bundles */
		public function useBundleBasedAnnotationEditor($ps_type=null);
		
		/* Returns name of property to be used to sort annotations in a list */
		public function getAnnotationSortProperty($ps_type=null);
	}
 ?>