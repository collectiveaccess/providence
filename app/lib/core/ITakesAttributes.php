<?php
/** ---------------------------------------------------------------------
 * app/interfaces/ITakesAttributes.php : interface for database entities that support attribute-based field values
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
 
 interface ITakesAttributes {
 	
 	// --- Managing
 	
 	// create an attribute linked to the current row using values in $pa_values
 	public function addAttribute($pa_values, $pm_element_code_or_id, $ps_error_key=null);
 	
 	// edit an attribute linked to the current row using values in $pa_values
 	public function editAttribute($pn_attribute_id, $pm_element_code_or_id, $pa_values, $ps_error_key=null);
 	
 	// remove attribute from current row
 	public function removeAttribute($pn_attribute_id, $ps_error_key=null);
 	
 	// remove all attributes from current row
 	public function removeAttributes($pm_element_code_or_id=null);
 	
 	// --- Forms
 	
 	// get HTML form element bundle for metadata element set
 	public function getAttributeHTMLFormBundle($po_request, $ps_form_name, $pm_element_code_or_id, $ps_placement_code, $pa_bundle_settings, $pa_options);
 	
 	// --- Retrieval
 	
 	// returns an array of all attributes attached to the current row
 	public function getAttributes($pa_options=null);
 	
 	// returns an array of all attributes with the specified element_id attached to the current row
 	public function getAttributesByElement($pm_element_code_or_id, $pa_options=null);
 	
 	// returns the specific attribute with the specified bundle_id
 	// ** assuming it's attached to the current row **
 	public function getAttributeByID($pn_attribute_id);
 	
 	// --- Utilties
 	
 	// copies all attributes attached to the current row to the row specified by $pn_id
 	public function copyAttributesTo($pn_row_id);
 	
 	// --- Methods to manage bindings between elements and tables
 	
 	// add element to type (or general use when type_id=null) for this table
 	public function addMetadataElementToType($pm_element_code_or_id, $pn_type_id);
 	
 	// remove element from type (or general use when type_id=null) for this table
 	public function removeMetadataElementFromType($pm_element_code_or_id, $pn_type_id);
 	
 	// returns list of metdata element codes applicable to the current row
 	public function getApplicableElementCodes($pn_type_id=null);
 }