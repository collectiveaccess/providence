<?php
/** ---------------------------------------------------------------------
 * app/lib/IDNumbering/IDNumber.php : base class for id number processing plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2021 Whirl-i-Gig
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
 * @subpackage IDNumbering
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
require_once(__CA_LIB_DIR__."/IDNumbering/IIDNumbering.php");

abstract class IDNumber implements IIDNumbering {
	# -------------------------------------------------------
	/**
	 * Instance of application configuration
	 * @type Configuration
	 */
	protected $opo_config;
	
	/**
	 * The list of valid formats, related types and elements
	 * @type array
	 */
	protected $formats;
	
	/**
	 * The current format
	 * @type string
	 */
	protected $ops_format;
	
	/**
	 * The current type
	 * @type string
	 */
	protected $ops_type = '__default__';
	
	/**
	 * The current value
	 * @type string
	 */
	protected $ops_value = null;
	
	/**
	 * Flag indicating whether record has a parent
	 * @type bool
	 */
	protected $opb_is_child = false;
	
	/**
	 * Identifier value for parent, if present
	 * @type string
	 */
	protected $ops_parent_value = null;
	
	# -------------------------------------------------------
	/**
	 * Initialize and load configuration files
	 */
	public function __construct() {
		$this->opo_config = Configuration::load();
	}
	# -------------------------------------------------------
	# Formats
	# -------------------------------------------------------
	/**
	 * Set the current format
	 *
	 * @param string $ps_format A valid format
	 * @return bool True on success, false if format was invalid
	 */
	public function setFormat($ps_format) {
		if ($this->isValidFormat($ps_format)) {
			$this->ops_format = $ps_format;
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Get the current format
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->ops_format;
	}
	# -------------------------------------------------------
	# Child number generation
	# -------------------------------------------------------
	/**
	 * Get or set is_child flag indicating if the current record value is for a record with a parent
	 *
	 * @param bool $pb_is_child Set the is_child flag.  [Default is null]
	 * @param string $ps_parent_value Optional parent identifier value, used to populate PARENT elements in multipart id numbers (and perhaps in other plugins as well) [Default is null]
	 * @return bool Current state is is_child flag
	 */
	public function isChild($pb_is_child=null, $ps_parent_value=null) {
		if (!is_null($pb_is_child)) {
			
			$this->opb_is_child = (bool)$pb_is_child;
			$this->ops_parent_value = $pb_is_child ? $ps_parent_value : null;
		}
		return $this->opb_is_child;
	}
	# -------------------------------------------------------
	/**
	 * Get the current parent value
	 *
	 * @return string
	 */
	public function getParentValue() {
		return $this->ops_parent_value;
	}
	# -------------------------------------------------------
	# Types
	# -------------------------------------------------------
	/**
	 * Set the current type
	 *
	 * @param mixed A type (string) or array of types to set as current type. If an array is passed then each type is attempted in turn until a 
	 * 				valid type is found. If no valid types are found the type will be set to '__default__'
	 * @return bool True if a valid type is found and set, false if no valid type is found.
	 */
	public function setType($pm_type) {
		if (!is_array($pm_type)) { $pm_type = array($pm_type); }
		
		foreach($pm_type as $ps_type) {
			if (!$ps_type) { continue; }
			if ($this->isValidType($ps_type)) {
				$this->ops_type = $ps_type;
				return true;
			}
		}
		$this->ops_type = '__default__';
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Get the current type
	 *
	 * @return string 
	 */
	public function getType() {
		return $this->ops_type;
	}
	# -------------------------------------------------------
	# Formats
	# -------------------------------------------------------
	/**
	 * Return list of formats configured in multipart_id_numbering.conf
	 *
	 * @return array
	 */
	public function getFormats() {
		return array_keys($this->formats);
	}
	# -------------------------------------------------------
	/**
	 * Check if format is present in configuration 
	 *
	 * @param string $ps_format The format to check
	 * @return bool
	 */
	public function isValidFormat($ps_format) {
		return in_array($ps_format, $this->getFormats());
	}
	# -------------------------------------------------------
	/**
	 * Return property for current format
	 *
	 * @param string $ps_property A format property name (eg. "separator")
	 * @param array $options Options include:
	 *		default = Value to return if property does not exist [Default is null]
	 * @return string
	 */
	public function getFormatProperty($ps_property, $options=null) {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType()) && isset($this->formats[$vs_format][$vs_type][$ps_property])) {
			return $this->formats[$vs_format][$vs_type][$ps_property] ? $this->formats[$vs_format][$vs_type][$ps_property] : '';
		}
		return caGetOption('default', $options, null);
	}
	# -------------------------------------------------------
	/**
	 * Return list of elements for current format and type using order specified in optional "sort_order" setting. Returns null
	 * if option is not set
	 *
	 * @return array List of elements as specified in "sort_order" setting, or null if there is no setting value
	 */
	public function getElementOrderForSort() {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType()) && isset($this->formats[$vs_format][$vs_type]['sort_order'])) {
			return (is_array($this->formats[$vs_format][$vs_type]['sort_order']) && sizeof($this->formats[$vs_format][$vs_type]['sort_order'])) ? $this->formats[$vs_format][$vs_type]['sort_order'] : null;
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Determine if the specified format and type contains an element of a given type. A specific element position may be specified. If 
	 * omitted all elements will be examined.
	 *
	 * @param string $ps_element_type The type of element to look for (Eg. SERIAL, YEAR, LIST)
	 * @param int $pn_index The zero-based position in the element list to examine. If omitted all elements are examined. [Default is null]
	 * @param string $ps_format A format to test. If omitted the current format is used. [Default is null]
	 * @param string $ps_type A type to test. If omitted the current type is used. [Default is null]
	 * @param array $options Options include:
	 *		checkLastElementOnly = check only the last element in the element list. This is the same as setting $pn_index to the last element, but saves you having to calculate what that index is. [Default is null]
	 * @return bool
	 */
	public function formatHas($ps_element_type, $pn_index=null, $ps_format=null, $ps_type=null, $options=null) {
		if ($ps_format) {
			if (!$this->isValidFormat($ps_format)) {
				return false;
			}
			$vs_format = $ps_format;
		} else {
			if(!($vs_format = $this->getFormat())) {
				return false;
			}
		}
		if ($ps_type) {
			if (!$this->isValidType($ps_type)) {
				return false;
			}
			$vs_type = $ps_type;
		} else {
			if(!($vs_type = $this->getType())) {
				return false;
			}
		}

		$va_elements = $this->formats[$vs_format][$vs_type]['elements'];
		
		if (!is_null($pn_index) && isset($va_elements[$pn_index])) { $va_elements = array($va_elements[$pn_index]); }

		if(!is_array($va_elements)) { return false; }
		
		if (caGetOption('checkLastElementOnly', $options, false)) { 
			$va_last_element = array_pop($va_elements);
			return ($va_last_element['type'] == $ps_element_type) ? true : false;
		} 
		
		
		foreach($va_elements as $va_element) {
			if ($va_element['type'] == $ps_element_type) {
				return true;
			}
		}
		return false;
	}
	# -------------------------------------------------------
	# Types
	# -------------------------------------------------------
	/**
	 * Return a list of valid types for a format
	 *
	 * @param string $format Format to return types for. If omitted the currently set format is used. [Default is null]
	 *
	 * @return array An array or types, or an empty array if the format is not set
	 */
	public function getTypes($format=null) {
		if (is_null($format)) { 
			if (!($format = $this->getFormat())) { return []; }
		}
		$types = [];
		if (is_array($this->formats[$format])) {
			foreach($this->formats[$format] as $type => $info) {
				$types[$type] = true;
			}
		}

		return array_keys($types);
	}
	# -------------------------------------------------------
	/**
	 * Determines if specified type is valid for the current format
	 *
	 * @param string $type A type code
	 * @param string $format Option format to fetch types for. If omitted the currently set format is used. [Default is null]
	 * @return bool
	 */
	public function isValidType($type, $format=null) {
		return ($type) && in_array($type, $this->getTypes($format));
	}
	# -------------------------------------------------------
	# Elements
	# -------------------------------------------------------
	/**
	 * Return list of elements configured in multipart_id_numbering.conf for the current format and type
	 *
	 * @return array An array of element information arrays, of the same format as returned by getElementInfo(), or null if the format and type are not set
	 */
	public function getElements() {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType())) {
			if (is_array($this->formats[$vs_format][$vs_type]['elements'])) {
				$vb_is_child = $this->isChild();
				$va_elements = array();
				foreach($this->formats[$vs_format][$vs_type]['elements'] as $vs_k => $element_info) {
					if (!$vb_is_child && isset($element_info['child_only']) && (bool)$element_info['child_only']) { continue; }
					$va_elements[$vs_k] = $element_info;
				}
			}
			return $va_elements;
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Return array of configuration from multipart_id_numbering.conf for the specified element in the current format and type
	 *
	 * @param string $ps_element_name The element to return information for
	 * @return array An array of information with the same keys as in multipart_id_numbering.conf, or null if the element does not exist
	 */
	public function getElementInfo($ps_element_name) {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType())) {
			return $this->formats[$vs_format][$vs_type]['elements'][$ps_element_name];
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Returns true if editable is set to 1 for the identifier, otherwise returns false
	 * Also, if the identifier consists of multiple elements, false will be returned.
	 *
	 * @param string $ps_format_name Name of format
	 * @param array $options Options include:
	 *		singleElementsOnly = Only consider formats with a single editable element to be editable. [Default is false]
	 * @return bool
	 */
	public function isFormatEditable($ps_format_name, $options=null) {
		if (!is_array($va_elements = $this->getElements())) { return false; }
		
		$vb_single_elements_only = caGetOption('singleElementsOnly', $options, false);
		
		foreach($va_elements as $vs_element => $element_info) {
			if (isset($element_info['editable']) && (bool)$element_info['editable']) { return true; }
			if ($vb_single_elements_only) { return false; }
		}
		return false;
	}
	# -------------------------------------------------------
	# Values
	# -------------------------------------------------------
	/**
	 * Set the current value
	 *
	 * @param string $ps_value The value of the current identifier
	 * @return void 
	 */
	public function setValue($ps_value) {
		$this->ops_value = $ps_value;
	}
	# -------------------------------------------------------
	/**
	 * Get the current value
	 *
	 * @param array $pa_options No options are defined.
	 * @return string
	 */
	public function getValue($pa_options=null) {
		return $this->ops_value;
	}
	# -------------------------------------------------------
}
