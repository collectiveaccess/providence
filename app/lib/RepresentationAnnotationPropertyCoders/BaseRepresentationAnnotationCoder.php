<?php
/** ---------------------------------------------------------------------
 * app/lib/RepresentationAnnotationPropertyCoders/BaseRepresentationAnnotationCoder.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/BaseObject.php');
require_once(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/IRepresentationAnnotationPropertyCoder.php');

abstract class BaseRepresentationAnnotationCoder extends BaseObject implements IRepresentationAnnotationPropertyCoder {
	# ------------------------------------------------------------------
	protected $opo_config;
	protected $opo_type_config;
	protected $ops_type = 'Unknown';		// show be overridden in subclasses
	
	protected $opa_type_info;
	
	protected $opa_property_values;
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->opo_config = Configuration::load();
		$this->opo_type_config = Configuration::load(__CA_CONF_DIR__.'/annotation_types.conf');
		
		$this->opa_type_info = $this->opo_type_config->getAssoc('types');
		$this->opa_property_values = array();
	}
	# ------------------------------------------------------------------
	/** 
	 * Return type code for annotation 
	 *
	 */
	public function getType() {
		return $this->ops_type;
	}
	# ------------------------------------------------------------------
	/** 
	 * Return list of properties for annotation type 
	 *
	 */
	public function getPropertyList() {
		if (!is_array($va_type_specific_info = $this->opa_type_info[$this->getType()])) { return null; }
		if (!is_array($va_properties = $va_type_specific_info['properties'])) { return null; }
		
		return array_keys($va_properties);
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for specified property 
	 *
	 */
	public function htmlFormElement($ps_property, $pa_attributes=null) {
		return 'No controls available';
	}
	# ------------------------------------------------------------------
	/** 
	 * Set the specified property; return true on success, false if value is invalid, null if property doesn't exist 
	 *
	 */
	public function setProperty($ps_property, $pm_value) {
		if ($this->getPropertyInfo($ps_property)) {
			$this->opa_property_values[$ps_property] = $pm_value;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Returns the property value or null if the property doesn't exist
	 *
	 */
	public function getProperty($ps_property, $pb_return_raw_value=false) {
		return $this->opa_property_values[$ps_property];
	}
	# ------------------------------------------------------------------
	/**
	 * Set property values (deserialized array out of db) 
	 *
	 */
	public function setPropertyValues($pa_serialized_values) {
		#
		# Deal with older serialized values that are numerically indexed
		# 
		if (caIsIndexedArray($pa_serialized_values)) {
			$pa_serialized_values = array_shift($pa_serialized_values);
		}
		$this->opa_property_values = $pa_serialized_values;
	}
	# ------------------------------------------------------------------
	/**
	 * Return array containing all property values 
	 *
	 */
	public function getPropertyValues() {
		return $this->opa_property_values;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getPropertyInfo($ps_property) {
		if (!is_array($va_type_specific_info = $this->opa_type_info[$this->getType()])) { return null; }
		if (!is_array($va_properties = $va_type_specific_info['properties'])) { return null; }
		
		if (isset($va_properties[$ps_property])) {
			return $va_properties[$ps_property];
		}
		
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function validate() {
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getDisplayMediaVersion($ps_type=null) {
		if (!$ps_type) { $ps_type = $this->getType(); }
		return $this->opa_type_info[$ps_type]['displayVersion'] ?? null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function useBundleBasedAnnotationEditor($ps_type=null) {
		if (!$ps_type) { $ps_type = $this->getType(); }
		return $this->opa_type_info[$ps_type]['useBundleEditor'] ?? false;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getAnnotationSortProperty($ps_type=null) {
		if (!$ps_type) { $ps_type = $this->getType(); }
		return $this->opa_type_info[$ps_type]['sortByProperty'] ?? null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getAnnotationGotoProperty($ps_type=null) {
		if (!$ps_type) { $ps_type = $this->getType(); }
		return $this->opa_type_info[$ps_type]['gotoToPropery'] ?? null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function useInEditor() {
		return false;
	}
	# ------------------------------------------------------------------
}
