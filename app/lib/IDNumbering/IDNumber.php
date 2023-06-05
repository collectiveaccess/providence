<?php
/** ---------------------------------------------------------------------
 * app/lib/IDNumbering/IDNumber.php : base class for id number processing plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2022 Whirl-i-Gig
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
	protected $config;
	
	/**
	 * A configuration object loaded with multipart_id_numbering.conf
	 * @type Configuration
	 */
	protected $idnumber_config;
	
	/**
	 * A configuration object loaded with search.conf
	 * @type Configuration
	 */
	protected $search_config;
	
	/**
	 * The list of valid formats, related types and elements
	 * @type array
	 */
	protected $formats;
	
	/**
	 * The current format
	 * @type string
	 */
	protected $format;
	
	/**
	 * The current type
	 * @type string
	 */
	protected $type = '__default__';
	
	/**
	 * The current value
	 * @type string
	 */
	protected $value = null;
	
	/**
	 * Flag indicating whether record has a parent
	 * @type bool
	 */
	protected $is_child = false;
	
	/**
	 * Identifier value for parent, if present
	 * @type string
	 */
	protected $parent_value = null;
	
	/**
	 * The current database connection object
	 * @type Db
	 */
	protected $db;
	
	# -------------------------------------------------------
	/**
	 * Initialize the plugin
	 *
	 * @param string $format A format to set as current [Default is null]
	 * @param mixed $type A type to set a current [Default is __default__] 
	 * @param string $value A value to set as current [Default is null]
	 * @param Db $db A database connection to use for all queries. If omitted a new connection (may be pooled) is allocated. [Default is null]
	 */
	public function __construct($format=null, $type=null, $value=null, $db=null) {
		$this->config = Configuration::load();
		$this->idnumber_config = Configuration::load(__CA_APP_DIR__."/conf/multipart_id_numbering.conf");
		$this->search_config = Configuration::load(__CA_APP_DIR__."/conf/search.conf");
		$this->formats = caChangeArrayKeyCase($this->idnumber_config->getAssoc('formats'));
		
		if (!$type) { $type = ['__default__']; }
		
		if ($format) { $this->setFormat($format); }
		if ($type) { $this->setType($type); }
		if ($value) { $this->setValue($value); }
		
		
		if ((!$db) || !is_object($db)) {
			$this->db = new Db();
		} else {
			$this->db = $db;
		}
	}
	# -------------------------------------------------------
	# Formats
	# -------------------------------------------------------
	/**
	 * Set the current format
	 *
	 * @param string $format A valid format
	 * @return bool True on success, false if format was invalid
	 */
	public function setFormat($format) {
		$format = mb_strtolower($format);
		if ($this->isValidFormat($format)) {
			$this->format = $format;
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
		return $this->format;
	}
	# -------------------------------------------------------
	# Child number generation
	# -------------------------------------------------------
	/**
	 * Get or set is_child flag indicating if the current record value is for a record with a parent
	 *
	 * @param bool $is_child Set the is_child flag.  [Default is null]
	 * @param string $parent_value Optional parent identifier value, used to populate PARENT elements in multipart id numbers (and perhaps in other plugins as well) [Default is null]
	 * @return bool Current state is is_child flag
	 */
	public function isChild($is_child=null, $parent_value=null) {
		if (!is_null($is_child)) {
			
			$this->is_child = (bool)$is_child;
			$this->parent_value = $is_child ? $parent_value : null;
		}
		return $this->is_child;
	}
	# -------------------------------------------------------
	/**
	 * Get the current parent value
	 *
	 * @return string
	 */
	public function getParentValue() {
		return $this->parent_value;
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
	public function setType($types) {
		if (!is_array($types)) { $types = [$types]; }
		
		foreach($types as $type) {
			if (!$type) { continue; }
			$type = mb_strtolower($type);
			if ($this->isValidType($type)) {
				$this->type = $type;
				return true;
			}
		}
		$this->type = '__default__';
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Get the current type
	 *
	 * @return string 
	 */
	public function getType() {
		return $this->type;
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
	 * @param string $format The format to check
	 * @return bool
	 */
	public function isValidFormat($format) {
		$format = mb_strtolower($format);
		return in_array($format, $this->getFormats());
	}
	# -------------------------------------------------------
	/**
	 * Return property for current format
	 *
	 * @param string $property A format property name (eg. "separator")
	 * @param array $options Options include:
	 *		default = Value to return if property does not exist [Default is null]
	 * @return string
	 */
	public function getFormatProperty($property, $options=null) {
		if (($format = $this->getFormat()) && ($type = $this->getType()) && isset($this->formats[$format][$type][$property])) {
			return $this->formats[$format][$type][$property] ? $this->formats[$format][$type][$property] : '';
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
		if (($format = $this->getFormat()) && ($type = $this->getType()) && isset($this->formats[$format][$type]['sort_order'])) {
			return (is_array($this->formats[$format][$type]['sort_order']) && sizeof($this->formats[$format][$type]['sort_order'])) ? $this->formats[$format][$type]['sort_order'] : null;
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Determine if the specified format and type contains an element of a given type. A specific element position may be specified. If 
	 * omitted all elements will be examined.
	 *
	 * @param string $element_type The type of element to look for (Eg. SERIAL, YEAR, LIST)
	 * @param int $index The zero-based position in the element list to examine. If omitted all elements are examined. [Default is null]
	 * @param string $format A format to test. If omitted the current format is used. [Default is null]
	 * @param string $type A type to test. If omitted the current type is used. [Default is null]
	 * @param array $options Options include:
	 *		checkLastElementOnly = check only the last element in the element list. This is the same as setting $pn_index to the last element, but saves you having to calculate what that index is. [Default is null]
	 * @return bool
	 */
	public function formatHas($element_type, $index=null, $format=null, $type=null, $options=null) {
		if ($format = mb_strtolower($format)) {
			if (!$this->isValidFormat($format)) {
				return false;
			}
			$format = $format;
		} elseif(!($format = $this->getFormat())) {
			return false;
		}
		if ($type = mb_strtolower($type)) {
			if (!$this->isValidType($type)) {
				return false;
			}
			$t = $type;
		} elseif(!($t = $this->getType())) {
			return false;
		}

		$elements = $this->formats[$format][$t]['elements'];
		
		if (!is_null($index) && isset($va_elements[$index])) { $elements = [$elements[$index]]; }

		if(!is_array($elements)) { return false; }
		
		if (caGetOption('checkLastElementOnly', $options, false)) { 
			$last_element = array_pop($elements);
			return ($last_element['type'] == $element_type) ? true : false;
		} 
		
		
		foreach($elements as $element) {
			if ($element['type'] == $element_type) {
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
		$format = mb_strtolower($format);
		$types = [];
		if (is_array($this->formats[$format] ?? null)) {
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
		$type = mb_strtolower($type);
		return ($type) && in_array($type, $this->getTypes($format));
	}
	# -------------------------------------------------------
	# Elements
	# -------------------------------------------------------
	/**
	 * Return list of elements configured in multipart_id_numbering.conf for the current format and type
	 *
	 * @param string $format Format to get elements for. If omitted currently loaded format is used. [Default is null]
	 * @param string $type Type to get elements for. If omitted currently loaded type is used. [Default is null]
	 * @return array An array of element information arrays, of the same format as returned by getElementInfo(), or null if the format and type are not set
	 */
	public function getElements($format=null, $type=null) {
		if(is_null($format)) { $format = $this->getFormat(); }
		if(is_null($type)) { $type = $this->getType(); }
		
		$format = mb_strtolower($format);
		$type = mb_strtolower($type);
		if ($format && $type) {
			if (is_array($this->formats[$format][$type]['elements'] ?? null)) {
				$is_child = $this->isChild();
				$elements = [];
				foreach($this->formats[$format][$type]['elements'] as $k => $element_info) {
					if (!$is_child && isset($element_info['child_only']) && (bool)$element_info['child_only']) { continue; }
					if($is_child && $element_info['root_only']) { continue; }
					
					$elements[$k] = $element_info;
				}
			}
			return $elements;
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Return array of configuration from multipart_id_numbering.conf for the specified element in the current format and type
	 *
	 * @param string $element_name The element to return information for
	 * @return array An array of information with the same keys as in multipart_id_numbering.conf, or null if the element does not exist
	 */
	public function getElementInfo($element_name) {
		if (($format = $this->getFormat()) && ($type = $this->getType())) {
			return $this->formats[$format][$type]['elements'][$element_name];
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Returns true if editable is set to 1 for the identifier, otherwise returns false
	 * Also, if the identifier consists of multiple elements, false will be returned.
	 *
	 * @param string $format Name of format
	 * @param array $options Options include:
	 *		singleElementsOnly = Only consider formats with a single editable element to be editable. [Default is false]
	 * @return bool
	 */
	public function isFormatEditable($format, $options=null) {
		$format = mb_strtolower($format);
		$types = $this->getTypes($format);
		$single_elements_only = caGetOption('singleElementsOnly', $options, false);
		foreach($types as $type) {
			if (!is_array($elements = $this->getElements($format))) { continue; }
		
			foreach($elements as $ename => $info) {
				if (isset($info['editable']) && (bool)$info['editable']) { return true; }
				if ($single_elements_only) { break; }
			}
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
	public function setValue($value) {
		$this->value = $value;
	}
	# -------------------------------------------------------
	/**
	 * Get the current value
	 *
	 * @param array $pa_options No options are defined.
	 * @return string
	 */
	public function getValue($options=null) {
		return $this->value;
	}
	# -------------------------------------------------------
	/**
	 * Set database connection to use for queries
	 *
	 * @param Db $po_db A database connection instance
	 * @return void
	 */
	public function setDb($db) {
		$this->db = $db;
	}
	# -------------------------------------------------------
}
