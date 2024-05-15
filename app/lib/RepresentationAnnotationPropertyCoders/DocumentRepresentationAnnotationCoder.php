<?php
/** ---------------------------------------------------------------------
 * app/lib/RepresentationAnnotationPropertyCoders/DocumentRepresentationAnnotationCoder.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/IRepresentationAnnotationPropertyCoder.php');
require_once(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/BaseRepresentationAnnotationCoder.php');
require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');

class DocumentRepresentationAnnotationCoder extends BaseRepresentationAnnotationCoder implements IRepresentationAnnotationPropertyCoder {
	# ------------------------------------------------------------------
	/**
	 * 
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->ops_type = 'Document';
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for specified property 
	 *
	 */
	public function htmlFormElement($ps_property, $pa_attributes=null) {
		$vs_element = $vs_label = '';
		
		if (!($vs_format = $pa_attributes['format'])) {
			$vs_format = $this->opo_config->get('form_element_display_format');
		}
		if ($va_property_info = $this->getPropertyInfo($ps_property)) {
			switch($va_property_info['fieldType']) {
				case 'FT_TEXT':
				case 'FT_NUMBER':
					$vs_label = $va_property_info['label'];
					if (!isset($pa_attributes['value'])) { $pa_attributes['value'] = $this->getProperty($ps_property); }
					if (!isset($pa_attributes['size'])) { $pa_attributes['size'] = $va_property_info['fieldWidth']; }
					
					switch($va_property_info['displayType']) {
						case 'DT_SELECT':
							$vs_element = caHTMLMakeLabeledFormElement(
								caHTMLSelect($pa_attributes['name'] ? $pa_attributes['name'] : $ps_property, $va_property_info['options'], $pa_attributes, array('height' => 1)), 
								$vs_label, $pa_attributes['name'] ? $pa_attributes['name'] : $ps_property, $va_property_info['description'], $vs_format, false
							);
							break;
						default:
							$vs_element = caHTMLMakeLabeledFormElement(
								caHTMLTextInput($pa_attributes['name'] ? $pa_attributes['name'] : $ps_property, $pa_attributes), 
								$vs_label, $pa_attributes['name'] ? $pa_attributes['name'] : $ps_property, $va_property_info['description'], $vs_format, false
							);
							break;
					}

					break;
				case 'FT_VARS':
					$vs_element = ''; // skip
					break;
				default:
					return 'Invalid field type for \''.$ps_property.'\'';
					break;
			}
		}
		
		return $vs_element;
	}
	# ------------------------------------------------------------------
	/**
	 * Set the specified property; return true on success, false if value is invalid, null if property doesn't exist 
	 *
	 */
	public function setProperty($ps_property, $pm_value) {
		if (!($va_info = $this->getPropertyInfo($ps_property))) { return null; }	// invalid property
		switch($va_info['fieldType']) {
			case 'FT_NUMBER':
				switch($ps_property) {
					case 'x':
					case 'y':
					case 'w':
					case 'h':
					case 'page':
						if(is_string($pm_value) && (trim($pm_value) == '*')) {		// Assign random value between 0% and 100%
							$pm_value = rand(0,100);
						}
						break;
				}
				if (is_numeric($pm_value)) {
					$this->opa_property_values[$ps_property] = (float)$pm_value;
					return true;
				}
				
				$this->postError(1500, _t("Invalid numeric value '%1' for %2", $pm_value, $va_info['label']), 'DocumentRepresentationAnnotationCoder->setProperty()');
				return false;
				break;
			default:
				$this->opa_property_values[$ps_property] = $pm_value;
				return true;
				break;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Returns the property value or null if the property doesn't exist 
	 *
	 */
	public function getProperty($ps_property, $pb_return_raw_value=false) {
		if (!($va_info = $this->getPropertyInfo($ps_property))) { return null; }	// invalid property
		
		if ($pb_return_raw_value) {
			return $this->opa_property_values[$ps_property];
		}
		
		switch($va_info['fieldType']) {
			default:
				return $this->opa_property_values[$ps_property];
				break;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Returns a combination of all properties for text display
	 */
	public function getPropertiesForDisplay($pa_options=null) {
		// to be consistent with the polygon display, we're going to return a list of 4 points
		$va_points = array();
		$va_points['tl']['x'] = $this->getProperty('x');
		$va_points['tl']['y'] = $this->getProperty('y');

		$va_points['tr']['x'] = $this->getProperty('x') + $this->getProperty('w');
		$va_points['tr']['y'] = $this->getProperty('y');

		$va_points['bl']['x'] = $this->getProperty('x');
		$va_points['bl']['y'] = $this->getProperty('y') + $this->getProperty('h');

		$va_points['br']['x'] = $this->getProperty('x') + $this->getProperty('w');
		$va_points['br']['y'] = $this->getProperty('y') + $this->getProperty('h');

		return $this->_formatPoints($va_points, $pa_options);
	}
	# ------------------------------------------------------------------
	private function _formatPoints($pa_points, $pa_options=null) {
		if(!is_array($pa_points)) { return ''; }

		$va_return = array();

		foreach($pa_points as $va_point) {
			// round values for display
			$va_return[] = round($va_point['x'],2).','.round($va_point['y'],2);
		}

		$vs_delimiter = caGetOption('delimiter', $pa_options, '; ');

		return join($vs_delimiter, $va_return);
	}
	# ------------------------------------------------------------------
	/**
	 * Validate property values prior to insertion into the database
	 * This function checks whether the values make sense
	 *
	 */
	public function validate() {
		// TODO: implement
		return true;
	}
	# ------------------------------------------------------------------
}
