<?php
/** ---------------------------------------------------------------------
 * includes/plugins/IDNumbering/MultipartIDNumber.php : plugin to generate id numbers
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2017 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/ca/IDNumbering/IDNumber.php");
require_once(__CA_LIB_DIR__."/ca/IDNumbering/IIDNumbering.php");
require_once(__CA_APP_DIR__."/helpers/navigationHelpers.php");

class MultipartIDNumber extends IDNumber {
	# -------------------------------------------------------
	/**
	 * A configuration object loaded with multipart_id_numbering.conf
	 * @type Configuration
	 */
	private $opo_idnumber_config;
	
	/**
	 * A configuration object loaded with search.conf
	 * @type Configuration
	 */
	private $opo_search_config;
	
	/**
	 * The list of valid formats, related types and elements
	 * @type array
	 */
	private $opa_formats;

	/**
	 * The current database connection object
	 * @type Db
	 */
	private $opo_db;

	# -------------------------------------------------------
	/**
	 * Initialize the plugin
	 *
	 * @param string $ps_format A format to set as current [Default is null]
	 * @param mixed $pm_type A type to set a current [Default is __default__] 
	 * @param string $ps_value A value to set as current [Default is null]
	 * @param Db $po_db A database connection to use for all queries. If omitted a new connection (may be pooled) is allocated. [Default is null]
	 */
	public function __construct($ps_format=null, $pm_type=null, $ps_value=null, $po_db=null) {
		if (!$pm_type) { $pm_type = array('__default__'); }

		parent::__construct();
		$this->opo_idnumber_config = Configuration::load(__CA_APP_DIR__."/conf/multipart_id_numbering.conf");
		$this->opo_search_config = Configuration::load(__CA_APP_DIR__."/conf/search.conf");
		$this->opa_formats = $this->opo_idnumber_config->getAssoc('formats');

		if ($ps_format) { $this->setFormat($ps_format); }
		if ($pm_type) { $this->setType($pm_type); }
		if ($ps_value) { $this->setValue($ps_value); }

		if ((!$po_db) || !is_object($po_db)) {
			$this->opo_db = new Db();
		} else {
			$this->opo_db = $po_db;
		}
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
		return array_keys($this->opa_formats);
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
	 * Return separator string for current format
	 *
	 * @return string Separator, or "." if no separator setting is present
	 */
	public function getSeparator() {
		return $this->getFormatProperty('separator', array('default' => '.'));
	}
	# -------------------------------------------------------
	/**
	 * Return property for current format
	 *
	 * @param string $ps_property A format property name (eg. "separator")
	 * @param array $pa_options Options include:
	 *		default = Value to return if property does not exist [Default is null]
	 * @return string
	 */
	public function getFormatProperty($ps_property, $pa_options=null) {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType()) && isset($this->opa_formats[$vs_format][$vs_type][$ps_property])) {
			return $this->opa_formats[$vs_format][$vs_type][$ps_property] ? $this->opa_formats[$vs_format][$vs_type][$ps_property] : '';
		}
		return caGetOption('default', $pa_options, null);
	}
	# -------------------------------------------------------
	/**
	 * Return list of elements for current format and type using order specified in optional "sort_order" setting. Returns null
	 * if option is not set
	 *
	 * @return array List of elements as specified in "sort_order" setting, or null if there is no setting value
	 */
	public function getElementOrderForSort() {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType()) && isset($this->opa_formats[$vs_format][$vs_type]['sort_order'])) {
			return (is_array($this->opa_formats[$vs_format][$vs_type]['sort_order']) && sizeof($this->opa_formats[$vs_format][$vs_type]['sort_order'])) ? $this->opa_formats[$vs_format][$vs_type]['sort_order'] : null;
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
	 * @param array $pa_options Options include:
	 *		checkLastElementOnly = check only the last element in the element list. This is the same as setting $pn_index to the last element, but saves you having to calculate what that index is. [Default is null]
	 * @return bool
	 */
	public function formatHas($ps_element_type, $pn_index=null, $ps_format=null, $ps_type=null, $pa_options=null) {
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

		$va_elements = $this->opa_formats[$vs_format][$vs_type]['elements'];
		
		if (!is_null($pn_index) && isset($va_elements[$pn_index])) { $va_elements = array($va_elements[$pn_index]); }

		if(!is_array($va_elements)) { return false; }
		
		if (caGetOption('checkLastElementOnly', $pa_options, false)) { 
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
	/**
	 * Determine if the specified format and type contain a SERIAL element its last element; that is, that the format and type 
	 * is designed as an auto incrementing sequence with 0 or more prefix elements.
	 *
	 * @param string $ps_format A format to test. If omitted the current format is used. [Default is null]
	 * @param string $ps_type A type to test. If omitted the current type is used. [Default is null]
	 * @return bool
	 */
	public function isSerialFormat($ps_format=null, $ps_type=null) {
		return $this->formatHas('SERIAL', null, $ps_format, $ps_type, array('checkLastElementOnly' => true));
	}
	# -------------------------------------------------------
	/**
	 * Returns true if the current format is an extension of $ps_format
	 * That is, the current format is the same as the $ps_form with an auto-generated
	 * extra element such that the system can auto-generate unique numbers using a $ps_format
	 * compatible number as the basis. This is mainly used to determine if the system configuration
	 * is such that object numbers can be auto-generated based upon lot numbers.
	 *
	 * @param string $ps_string
	 * @param string $ps_type [Default is __default__]
	 * @return bool
	 */
	public function formatIsExtensionOf($ps_format, $ps_type='__default__') {
		if (!$this->isSerialFormat()) {
			return false;	// If this format doesn't end in a SERIAL element it can't be autogenerated.
		}

		if (!$this->isValidFormat($ps_format)) {
			return false;	// specifed format does not exist
		}
		if (!$this->isValidType($ps_type)) {
			return false;	// specifed type does not exist
		}

		$va_base_elements = $this->opa_formats[$ps_format][$ps_type]['elements'];
		$va_ext_elements = $this->getElements();

		if (sizeof($va_ext_elements) != (sizeof($va_base_elements) + 1)) {
			return false;	// extension should have exactly one more element than base
		}

		$vn_num_elements = sizeof($va_base_elements);
		for($vn_i=0; $vn_i < $vn_num_elements; $vn_i++) {
			$va_base_element = array_shift($va_base_elements);
			$va_ext_element = array_shift($va_ext_elements);

			if ($va_base_element['type'] != $va_ext_element['type']) { return false; }
			if ($va_base_element['width'] > $va_ext_element['width']) { return false; }

			switch($va_base_element['type']) {
				case 'LIST':
					if (!is_array($va_base_element['values']) || !is_array($va_ext_element['values'])) { return false; }
					if (sizeof($va_base_element['values']) != sizeof($va_ext_element['values'])) { return false; }
					for($vn_j=0; $vn_j < sizeof($va_base_element['values']); $vn_j++) {
						if ($va_base_element['values'][$vn_j] != $va_ext_element['values'][$vn_j]) { return false; }
					}
					break;
				case 'CONSTANT';
					if ($va_base_element['value'] != $va_ext_element['value']) { return false; }
					break;
				case 'NUMERIC':
					if ($va_base_element['minimum_length'] < $va_ext_element['minimum_length']) { return false; }
					if ($va_base_element['maximum_length'] > $va_ext_element['maximum_length']) { return false; }
					if ($va_base_element['minimum_value'] < $va_ext_element['minimum_value']) { return false; }
					if ($va_base_element['maximum_value'] > $va_ext_element['maximum_value']) { return false; }
					break;
				case 'ALPHANUMERIC':
					if ($va_base_element['minimum_length'] < $va_ext_element['minimum_length']) { return false; }
					if ($va_base_element['maximum_length'] > $va_ext_element['maximum_length']) { return false; }
					break;
				case 'FREE':
					if ($va_base_element['minimum_length'] < $va_ext_element['minimum_length']) { return false; }
					if ($va_base_element['maximum_length'] > $va_ext_element['maximum_length']) { return false; }
					break;
			}
		}

		return true;

	}
	# -------------------------------------------------------
	# Types
	# -------------------------------------------------------
	/**
	 * Return a list of valid types for the current format
	 *
	 * @return array An array or types, or an emtpy array if the format is not set
	 */
	public function getTypes() {
		if (!($vs_format = $this->getFormat())) { return array(); }
		$va_types = array();
		if (is_array($this->opa_formats[$vs_format])) {
			foreach($this->opa_formats[$vs_format] as $vs_type => $va_info) {
				$va_types[$vs_type] = true;
			}
		}

		return array_keys($va_types);
	}
	# -------------------------------------------------------
	/**
	 * Determines if specified type is valid for the current format
	 *
	 * @param string $ps_type A type code
	 * @return bool
	 */
	public function isValidType($ps_type) {
		return ($ps_type) && in_array($ps_type, $this->getTypes());
	}
	# -------------------------------------------------------
	# Elements
	# -------------------------------------------------------
	/**
	 * Return list of elements configured in multipart_id_numbering.conf for the current format and type
	 *
	 * @return array An array of element information arrays, of the same format as returned by getElementInfo(), or null if the format and type are not set
	 */
	private function getElements() {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType())) {
			if (is_array($this->opa_formats[$vs_format][$vs_type]['elements'])) {
				$vb_is_child = $this->isChild();
				$va_elements = array();
				foreach($this->opa_formats[$vs_format][$vs_type]['elements'] as $vs_k => $va_element_info) {
					if (!$vb_is_child && isset($va_element_info['child_only']) && (bool)$va_element_info['child_only']) { continue; }
					$va_elements[$vs_k] = $va_element_info;
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
	private function getElementInfo($ps_element_name) {
		if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType())) {
			return $this->opa_formats[$vs_format][$vs_type]['elements'][$ps_element_name];
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Breaks apart value using configuration of current format and type. When a format type specifies a separator this is generally
	 * equivalent to explode()'ing the value on the separator, except when PARENT elements (which may container the separator) are configured.
	 * explodeValue() can also split values when no separator is configured, using configured element widths to determine boundaries.
	 *
	 * @param string $ps_value
	 * @return array List of values
	 */
	protected function explodeValue($ps_value) {
		$vs_separator = $this->getSeparator();
		
		if ($vs_separator && $this->formatHas('PARENT', 0)) {
			// starts with PARENT element so explode in reverse since parent value may include separators
				
			$va_element_vals_in_reverse = array_reverse(explode($vs_separator, $ps_value));
			$vn_num_elements = sizeof($va_elements = $this->getElements());
			
			$va_element_vals = array();
			while(sizeof($va_elements) > 1) {
				array_shift($va_elements);
				$va_element_vals[] = array_shift($va_element_vals_in_reverse);
				
				$vn_num_elements--;
			}
			
			$va_element_vals[] = join($vs_separator, array_reverse($va_element_vals_in_reverse));
			$va_element_vals = array_reverse($va_element_vals);
		} elseif ($vs_separator) {
			// Standard operation, use specified non-empty separator to split value
			$va_element_vals = explode($vs_separator, $ps_value);
		} else {
			// Separator is explicitly set to empty string, so use element widths to split value
			$va_element_vals = array();
			$vn_strpos = 0;
			foreach ($this->getElements() as $va_element_info) {
				switch ($va_element_info['type']) {
					case 'LIST':
						// The element has an implicit width depending on the selected value in a list
						$vs_matching_value = null;
						foreach ($va_element_info['values'] as $vs_value) {
							if (substr($ps_value, $vn_strpos, mb_strlen($vs_value)) === $vs_value && (is_null($vs_matching_value) || mb_strlen($vs_matching_value) < mb_strlen($vs_value))) {
								// We have a match, and it is either the first match or the longest match so far
								$vs_matching_value = $vs_value;
							}
						}
						$vn_width = !is_null($vs_matching_value) ? mb_strlen($vs_matching_value) : null;
						break;
					case 'CONSTANT':
						// The element has an implicit width because it is a constant, so read the width of the constant
						$vn_width = mb_strlen($va_element_info['value']);
						break;
					case 'SERIAL':
					case 'YEAR':
					case 'MONTH':
					case 'DAY':
					case 'NUMERIC':
						// Match a sequence of numeric digits
						$vn_width = mb_strlen(preg_replace('/^(\d+).*$/', '$1', substr($ps_value, $vn_strpos)));
						break;
					case 'ALPHANUMERIC':
						// Match a sequence of alphanumeric characters
						$vn_width = mb_strlen(preg_replace('/^([A-Za-z0-9]+).*$/', '$1', substr($ps_value, $vn_strpos)));
						break;
					case 'FREE':
					case 'PARENT':
					default:
						// Match free text
						$vn_width = null;
				}
				if (isset($va_element_info['width'])) {
					// Use the configured width as either a fallback or a maximum
					$vn_width = is_null($vn_width) ? intval($va_element_info['width']) : min($vn_width, intval($va_element_info['width']));
				}
				// Take the calculated width from the input value as the element value; if $vn_width is null, use the remainder
				// of the input string
				$va_element_vals[] = substr($ps_value, $vn_strpos, $vn_width);
				$vn_strpos = is_null($vn_width) ? mb_strlen($ps_value) : $vn_strpos + $vn_width;
			}
		}
		return $va_element_vals;
	}
	# -------------------------------------------------------
	/**
	 * Validate value against current format and return list of error messages. 
	 *
	 * @param string $ps_value A value to validate.
	 * @return array List of validation errors for value when applied to current format. Empty array if no error.
	 */
	public function validateValue($ps_value) {
		//if (!$ps_value) { return array(); }
		$va_elements = $this->getElements();
		if (!is_array($va_elements)) { return array(); }

		$va_element_vals = $this->explodeValue($ps_value);
		$vn_i = 0;
		$va_element_errors = array();
		foreach($va_elements as $vs_element_name => $va_element_info) {
			$vs_value = $va_element_vals[$vn_i];
			$vn_value_len = mb_strlen($vs_value);

			switch($va_element_info['type']) {
				case 'LIST':
					if (!in_array($vs_value, $va_element_info['values'])) {
						$va_element_errors[$vs_element_name] = _t("'%1' is not valid for %2", $vs_value, $va_element_info['description']);
					}
					break;
				case 'SERIAL':
					if ($vs_value) {
						if (!preg_match("/^[A-Za-z0-9]+$/", $vs_value)) {
							$va_element_errors[$vs_element_name] = _t("'%1' is not valid for %2; only letters and numbers are allowed", $vs_value, $va_element_info['description']);
						}
					}
					break;
				case 'CONSTANT':
					if ($vs_value && ($vs_value != $va_element_info['value'])) {
						$va_element_errors[$vs_element_name] = _t("%1 must be set to %2; was %3", $va_element_info['description'], $va_element_info['value'], $vs_value);
					}
					break;
				case 'FREE':
					if (isset($va_element_info['minimum_length']) && ($vn_value_len < $va_element_info['minimum_length'])) {
						if($va_element_info['minimum_length'] == 1) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 character", $va_element_info['description'], $va_element_info['minimum_length']);
						} else {
							$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 characters", $va_element_info['description'], $va_element_info['minimum_length']);
						}
					}
					if (isset($va_element_info['maximum_length']) && ($vn_value_len > $va_element_info['maximum_length'])) {
						if($va_element_info['minimum_length'] == 1) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 character", $va_element_info['description'], $va_element_info['maximum_length']);
						} else {
							$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 characters", $va_element_info['description'], $va_element_info['maximum_length']);
						}
					}
					break;
				case 'NUMERIC':
					if (!preg_match("/^[\d]+[a-zA-Z]{0,1}$/", $vs_value)) {
						$va_element_errors[$vs_element_name] = _t("%1 must be a number", $va_element_info['description']);
					}
					if (isset($va_element_info['minimum_value']) && ($vs_value < $va_element_info['minimum_value'])) {
						$va_element_errors[$vs_element_name] = _t("%1 must not be less than %2", $va_element_info['description'], $va_element_info['minimum_value']);
					}
					if (isset($va_element_info['maximum_value']) && ($vs_value > $va_element_info['maximum_value'])) {
						$va_element_errors[$vs_element_name] = _t("%1 must not be more than %2", $va_element_info['description'], $va_element_info['maximum_value']);
					}
					if (isset($va_element_info['minimum_length']) && ($vn_value_len < $va_element_info['minimum_length'])) {
						if ($va_element_info['minimum_length'] == 1) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 character", $va_element_info['description'], $va_element_info['minimum_length']);
						} else {
							$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 characters", $va_element_info['description'], $va_element_info['minimum_length']);
						}
					}
					if (isset($va_element_info['maximum_length']) && ($vn_value_len > $va_element_info['maximum_length'])) {
						if ($va_element_info['maximum_length'] == 1) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 character", $va_element_info['description'], $va_element_info['maximum_length']);
						} else {
							$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 characters", $va_element_info['description'], $va_element_info['maximum_length']);
						}
					}
					break;
				case 'ALPHANUMERIC':
					if ($vs_value != '' && !preg_match("/^[A-Za-z0-9]+$/", $vs_value)) {
						$va_element_errors[$vs_element_name] = _t("%1 must consist only letters and numbers", $va_element_info['description']);
					}
					if (isset($va_element_info['minimum_length']) && ($vn_value_len < $va_element_info['minimum_length'])) {
						if ($va_element_info['minimum_length'] == 1) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 character", $va_element_info['description'], $va_element_info['minimum_length']);
						} else {
							$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 characters", $va_element_info['description'], $va_element_info['minimum_length']);
						}
					}
					if (isset($va_element_info['maximum_length']) && ($vn_value_len > $va_element_info['maximum_length'])) {
						if ($va_element_info['maximum_length'] == 1) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 character", $va_element_info['description'], $va_element_info['maximum_length']);
						} else {
							$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 characters", $va_element_info['description'], $va_element_info['maximum_length']);
						}
					}
					break;
				case 'YEAR':
					$va_tmp = getdate();
					if ($vs_value != '') {
						if ($va_element_info['width'] == 2) {
							if(($vs_value < 0) || ($vs_value > 99)){
								$va_element_errors[$vs_element_name] = _t("%1 must be a valid two-digit year", $va_element_info['description']);
							}
						} elseif ((($vs_value < 1000) || ($vs_value > ($va_tmp['year'] + 10))) || ($vs_value != intval($vs_value))) {
							$va_element_errors[$vs_element_name] = _t("%1 must be a valid year", $va_element_info['description']);
						}
					}
					break;
				case 'MONTH':
					if ($vs_value != '') {
						if ((($vs_value < 1) || ($vs_value > 12)) || ($vs_value != intval($vs_value))) {
							$va_element_errors[$vs_element_name] = _t("%1 must be a valid numeric month (between 1 and 12)", $va_element_info['description']);
						}
					}
					break;
				case 'DAY':
					if ($vs_value != '') {
						if ((($vs_value < 1) || ($vs_value > 31)) || ($vs_value != intval($vs_value))) {
							$va_element_errors[$vs_element_name] = _t("%1 must be a valid numeric day (between 1 and 31)", $va_element_info['description']);
						}
					}
					break;
				default:
					# noop
					break;

			}
			$vn_i++;
		}
		return $va_element_errors;
	}
	# -------------------------------------------------------
	/**
	 * Check that value is valid for the current format
	 *
	 * @param string $ps_value [Default is null - use current value]
	 * @return bool
	 */
	public function isValidValue($ps_value=null) {
		return $this->validateValue(!is_null($ps_value) ? $ps_value : $this->getValue());
	}
	# -------------------------------------------------------
	/**
	 * Get next integer value in sequence for the specified SERIAL element
	 *
	 * @param string $ps_element_name
	 * @param mixed $pm_value [Default is null]
	 * @param bool  $pb_dont_mark_value_as_used [Default is false]
	 * @return int Next value for SERIAL element or the string "ERR" on error
	 */
	public function getNextValue($ps_element_name, $pm_value=null, $pb_dont_mark_value_as_used=false) {
		if (!$pm_value) { $pm_value = $this->getValue(); }
		$va_element_info = $this->getElementInfo($ps_element_name);

		$vs_table = $va_element_info['table'];
		$vs_field = $va_element_info['field'];
		$vs_sort_field = $va_element_info['sort_field'];

		if (!$vs_table) { return 'ERR';}
		if (!$vs_field) { return 'ERR';}
		if (!$vs_sort_field) { $vs_sort_field = $vs_field; }

		$vs_separator = $this->getSeparator();
		$va_elements = $this->getElements();

		if ($pm_value == null) {
			$va_element_vals = array();
			foreach($va_elements as $vs_element_name => $va_element_info) {
				switch($va_element_info['type']) {
					case 'CONSTANT':
						$va_element_vals[] = $va_element_info['value'];
						break;
					case 'YEAR':
					case 'MONTH':
					case 'DAY':
						$va_date = getDate();
						if ($va_element_info['type'] == 'YEAR') {
							if ($va_element_info['width'] == 2) {
								$va_date['year'] = substr($va_date['year'], 2, 2);
							}
							$va_element_vals[] = $va_date['year'];
						}
						if ($va_element_info['type'] == 'MONTH') { $va_element_vals[]  = $va_date['mon']; }
						if ($va_element_info['type'] == 'DAY') { $va_element_vals[]  = $va_date['mday']; }
						break;
					case 'LIST':
						if ($va_element_info['default']) {
							$va_element_vals[] = $va_element_info['default'];
						} else {
							if (is_array($va_element_info['values'])) {
								$va_element_vals[] = array_shift($va_element_info['values']);
							}
						}
						break;
					case 'PARENT':
						$va_element_vals[] = $this->getParentValue();
						break;
					default:
						$va_element_vals[] = '';
						break;
				}
			}
		} elseif(is_array($pm_value)) {
			$va_element_vals = array_values($pm_value);
		} else {
			$va_element_vals = $this->explodeValue($pm_value);
		}

		$va_tmp = array();
		$vn_i = 0;
		foreach($va_elements as $vs_element_name => $va_element_info) {
			if ($vs_element_name == $ps_element_name) { break; }
			$va_tmp[] = array_shift($va_element_vals);
			$vn_i++;
		}

		$vs_stub = trim(join($vs_separator, $va_tmp));

		$this->opo_db->dieOnError(false);

		// Get the next number based upon field data
		$vn_type_id = null;
		
		$o_dm = Datamodel::load();
		if (!($t_instance = $o_dm->getInstanceByTableName($vs_table, true))) { return 'ERR'; }
		if ((bool)$va_element_info['sequence_by_type']) {
			$vn_type_id = (int)$t_instance->getTypeIDForCode($this->getType());
		}
		
		if ($qr_res = $this->opo_db->query($x="
			SELECT $vs_field FROM ".$vs_table."
			WHERE
				$vs_field LIKE ? ".(($vn_type_id > 0) ? " AND type_id = {$vn_type_id}" : "")."
				".($t_instance->hasField('deleted') ? " AND (deleted = 0)" : '')."
			ORDER BY
				$vs_sort_field DESC
		", ($y=$vs_stub.(($vs_stub != '') ? $vs_separator.'%' : '%')))) {
			if ($this->opo_db->numErrors()) {
				return "ERR";
			}

			// Figure out what the sequence (last) number in the multipart number taken from the field is...
			if ($qr_res->numRows()) {
				while($qr_res->nextRow()) {
					$va_tmp = $this->explodeValue($qr_res->get($vs_field));
					if(is_numeric($va_tmp[$vn_i])) {
						$vn_num = intval($va_tmp[$vn_i]) + 1;
						break;
					}
				}
				if ($vn_num == '') { $vn_num = 1; }
				if (is_array($va_tmp)) {
					array_pop($va_tmp);
					$vs_stub = join($vs_separator, $va_tmp);
				} else {
					$vs_stub = '';
				}
			} else {
				$vn_num = 1;
			}

			// Now get the last used sequence number for this "stub"
			$vn_max_num = $this->getSequenceMaxValue($this->getFormat(), $ps_element_name, $vs_stub);

			// Make the new number one more than the last used number if it is less than the last
			// (this prevents numbers from being reused when records are deleted or renumbered)
			if ($vn_num <= $vn_max_num) {
				$vn_num = $vn_max_num + 1;
			}

			// Record this newly issued number as the new "last used" number, unless told not to do so
			if (!$pb_dont_mark_value_as_used) {
				$this->setSequenceMaxValue($this->getFormat(), $ps_element_name, $vs_stub, $vn_num);
			}

			if (($vn_zeropad_to_length = (int)$va_element_info['zeropad_to_length']) > 0) {
				return sprintf("%0{$vn_zeropad_to_length}d", $vn_num);
			} else {
				return $vn_num;
			}
		} else {
			return 'ERR'; //.join('; ',$this->opo_db->getErrors()).']';
		}
	}
	# -------------------------------------------------------
	/**
	 * Returns sortable value padding according to the format of the specified format and type
	 *
	 * @param string $ps_value Value from which to derive the sortable value. If omitted the current value is used. [Default is null]
	 * @return string The sortable value
	 */
	public function getSortableValue($ps_value=null) {
		$vs_separator = $this->getSeparator();
		if (!is_array($va_elements_normal_order = $this->getElements())) { $va_elements_normal_order = array(); }
		$va_element_names_normal_order = array_keys($va_elements_normal_order);

		if (!($va_elements = $this->getElementOrderForSort())) { $va_elements = $va_element_names_normal_order; }
		$va_element_vals = $this->explodeValue($ps_value ?: $this->getValue());
		$va_output = array();

		foreach ($va_elements as $vs_element) {
			$va_element_info = $va_elements_normal_order[$vs_element];
			$vn_i = array_search($vs_element, $va_element_names_normal_order);
			$vn_padding = 20;

			switch($va_element_info['type']) {
				case 'LIST':
					$vn_w = $vn_padding - mb_strlen($va_element_vals[$vn_i]);
					if ($vn_w < 0) { $vn_w = 0; }
					$va_output[] = str_repeat(' ', $vn_w).$va_element_vals[$vn_i];
					break;
				case 'CONSTANT':
					$vn_len = mb_strlen($va_element_info['value']);
					if ($vn_padding < $vn_len) { $vn_padding = $vn_len; }
					$vn_repeat_len = ($vn_padding - mb_strlen($va_element_vals[$vn_i]));
					$va_output[] = (($vn_repeat_len > 0) ? str_repeat(' ', $vn_padding - mb_strlen($va_element_vals[$vn_i])) : '').$va_element_vals[$vn_i];
					break;
				case 'FREE':
				case 'ALPHANUMERIC':
					$va_tmp = preg_split('![^A-Za-z0-9]+!',  $va_element_vals[$vn_i]);

					$va_zeroless_output = array();
					$va_raw_output = array();
					while(sizeof($va_tmp)) {
						$vs_piece = array_shift($va_tmp);
						if (preg_match('!^([\d]+)(.*)!', $vs_piece, $va_matches)) {
							$vs_piece = $va_matches[1];

							if (sizeof($va_matches) >= 3) {
								array_unshift($va_tmp, $va_matches[2]);
							}
						}
						$vn_pad_len = 12 - mb_strlen($vs_piece);

						if ($vn_pad_len >= 0) {
							if (is_numeric($vs_piece)) {
								$va_raw_output[] = str_repeat(' ', $vn_pad_len).$va_matches[1];
							} else {
								$va_raw_output[] = $vs_piece.str_repeat(' ', $vn_pad_len);
							}
						} else {
							$va_raw_output[] = $vs_piece;
						}
						if ($vs_tmp = preg_replace('!^[0]+!', '', $vs_piece)) {
							$va_zeroless_output[] = $vs_tmp;
						} else {
							$va_zeroless_output[] = $vs_piece;
						}
					}
					$va_output[] = join('', $va_raw_output); //.' '.join('.', $va_zeroless_output);
					break;
				case 'SERIAL':
				case 'NUMERIC':
					if ($vn_padding < $va_element_info['width']) { $vn_padding = $va_element_info['width']; }
					if (preg_match("/^([0-9]+)([A-Za-z]{1})$/", $va_element_vals[$vn_i], $va_matches)) {
						$va_output[] = str_repeat(' ', $vn_padding - mb_strlen(intval($va_matches[1]))).intval($va_matches[1]).$va_matches[2];
					} else {
						$va_output[] = str_repeat(' ', $vn_padding - mb_strlen(intval($va_element_vals[$vn_i]))).intval($va_element_vals[$vn_i]);
					}
					break;
				case 'YEAR':
					$vn_p = (($va_element_info['width'] == 2) ? 2 : 4) - mb_strlen($va_element_vals[$vn_i]);
					if ($vn_p < 0) { $vn_p = 0; }
					$va_output[] = str_repeat(' ', $vn_p).$va_element_vals[$vn_i];
					break;
				case 'MONTH':
				case 'DAY':
					$vn_p = 2 - mb_strlen($va_element_vals[$vn_i]);
					if ($vn_p < 0) { $vn_p = 0; }
					$va_output[] = str_repeat(' ', 2 - $vn_p).$va_element_vals[$vn_i];
					break;
				case 'PARENT':
					$va_output[] = $va_element_vals[$vn_i].str_repeat(' ', $vn_padding - mb_strlen($va_element_vals[$vn_i]));
					break;
				default:
					$va_output[] = str_repeat(' ', $vn_padding - mb_strlen($va_element_vals[$vn_i])).$va_element_vals[$vn_i];
					break;

			}
		}
		return join($vs_separator, $va_output);
	}
	# -------------------------------------------------------
	/**
	 * Return a list of modified identifier values suitable for search indexing according to the format of the specified format and type
	 * Modifications include removal of leading zeros, stemming and more.
	 *
	 * @param string $ps_value Value from which to derive the index values. If omitted the current value is used. [Default is null]
	 * @return array Array of string for indexing
	 */
	public function getIndexValues($ps_value=null) {
		$vs_separator = $this->getSeparator();
		if (!is_array($va_elements_normal_order = $this->getElements())) { $va_elements_normal_order = array(); }
		$va_element_names_normal_order = array_keys($va_elements_normal_order);

		if (!($va_elements = $this->getElementOrderForSort())) { $va_elements = $va_element_names_normal_order; }
		$va_element_vals = $this->explodeValue($ps_value ?: $this->getValue());
		$vn_i = 0;
		$va_output = array(join($vs_separator, $va_element_vals));
		$vn_max_value_count = 0;

		// element-specific processing
		foreach($va_elements as $vs_element) {
			$va_element_info = $va_elements_normal_order[$vs_element];
			$vn_i = array_search($vs_element, $va_element_names_normal_order);

			switch($va_element_info['type']) {
				case 'LIST':
					$va_output[$vn_i] = array($va_element_vals[$vn_i]);
					break;
				case 'CONSTANT':
					$va_output[$vn_i] = array($va_element_vals[$vn_i]);
					break;
				case 'FREE':
				case 'ALPHANUMERIC':
					$va_output[$vn_i] = array($va_element_vals[$vn_i]);
					if ((int)$va_element_vals[$vn_i] > 0) {
						$va_output[$vn_i][] = (int)$va_element_vals[$vn_i];
					}
					break;
				case 'SERIAL':
				case 'NUMERIC':
				case 'MONTH':
				case 'DAY':
				case 'YEAR':
					$va_output[$vn_i] = array($va_element_vals[$vn_i]);
					if (preg_match('!^([0]+)([\d]+)$!', $va_element_vals[$vn_i], $va_matches)) {
						for($vn_i=0; $vn_i < sizeof($va_matches[1]); $vn_i++) {
							$va_output[$vn_i][] = substr($va_element_vals[$vn_i], $vn_i);
						}
					}
					break;
				default:
					$va_output[$vn_i] = array($va_element_vals[$vn_i]);
					break;
			}

			if ($vn_max_value_count < sizeof($va_output[$vn_i])) { $vn_max_value_count = sizeof($va_output[$vn_i]); }
		}

		$va_output_values = array();

		// Generate permutations from element-specific processing
		for($vn_c=0; $vn_c < $vn_max_value_count; $vn_c++) {
			$va_output_values_buf = array();

			foreach($va_elements as $vs_element) {
				if (!isset($va_output[$vn_i][0])) { continue; }

				$vn_i = array_search($vs_element, $va_element_names_normal_order);
				if (isset($va_output[$vn_i][$vn_c])) {
					$va_output_values_buf[] = $va_output[$vn_i][$vn_c];
				} else {
					$va_output_values_buf[] = $va_output[$vn_i][0];
				}
			}

			$va_output_values[] = join($vs_separator, $va_output_values_buf);
		}

		// generate incremental "stems" of identifier by exploding on punctuation
		if(preg_match_all("![^A-Za-z0-9]+!", $ps_value, $va_delimiters)) {
			$va_element_values = preg_split("![^A-Za-z0-9]+!", $ps_value);
			$va_acc = array();
			foreach($va_element_values as $vs_element_value) {
				$va_acc[] = $vs_element_value;
				$va_output_values[] = join('', $va_acc);
				if (is_numeric($vs_element_value)) {
					array_pop($va_acc);
					$va_acc[] = $vs_element_value;
					$va_output_values[] = join('', $va_acc);
				}
				if (sizeof($va_delimiters[0]) > 0) { $va_acc[] = array_shift($va_delimiters[0]); }
			}
		}

		// generate versions without leading zeros
		$va_output_values[] = preg_replace("!^[0]+!", "", $ps_value);	// remove leading zeros
		if (preg_match_all("!([^0-9]+)([0]+)!", $ps_value, $va_matches)) {
			$vs_value_proc = $ps_value;
			for($vn_x=0; $vn_x < sizeof($va_matches[0]); $vn_x++) {
				$vs_value_proc = str_replace($va_matches[0][$vn_x], $va_matches[1][$vn_x], $vs_value_proc);
			}
			$va_output_values[] = $vs_value_proc;
		}

		// generate version without trailing letters after number (eg. KHF-134b => KHF-134)
		$va_tmp = $va_output_values;
		foreach($va_tmp as $vs_value_proc) {
			$va_output_values[] = preg_replace("!([\d]+)[A-Za-z]+$!", "$1", $vs_value_proc);
		}
		
		$va_output_values = array_unique($va_output_values);
		
		// generate tokenized version
		if($va_tokens = preg_split("![".$this->opo_search_config->get('indexing_tokenizer_regex')."]+!", $ps_value)) {
			$va_output_values = array_merge($va_output_values, $va_tokens);
		}
		
		return $va_output_values;
	}
	# -------------------------------------------------------
	# User interace (HTML)
	# -------------------------------------------------------
	/**
	 * Return HTML form elements for all elements using the current format, type and value
	 *
	 * @param string $ps_name Name of form element. Is used as a prefix for each form element. The number element name will be used as a suffix for each.
	 * @param array $pa_errors Passed-by-reference array. Will contain any validation errors for the value, indexed by element.
	 * @param array $pa_options Options include:
	 *		id_prefix = Prefix to add to element ID attributes. [Default is null]
	 *		for_search_form = Generate a blank form for search. [Default is false]
	 *		show_errors = Include error messages next to form elements. [Default is false]
	 *		error_icon = Icon to display next to error messages; should be ready-to-display HTML. [Default is null]
	 *		readonly = Make all form elements read-only. [Default is false]
	 *		request = the current request (an instance of RequestHTTP) [Default is null]
	 *		check_for_dupes = perform live checking for duplicate numbers. [Default is false]
	 *		progress_indicator = URL for spinner graphic to use while running duplicate number check. [Default is null] 
	 *		table = Table to perform duplicate number check in. [Default is null]
	 *		search_url = Search service URL to use when performing duplicate number check. [Default is null]
	 *		row_id = ID of row to exclude from duplicate number check (typically the current record id). [Default is null]
	 *		context_id = context ID of row to exclude from duplicate number check (typically the current record context). [Default is null]
	 * @return string HTML output
	 */
	public function htmlFormElement($ps_name, &$pa_errors=null, $pa_options=null) {
		$o_config = Configuration::load();
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vs_id_prefix = isset($pa_options['id_prefix']) ? $pa_options['id_prefix'] : null;
		$vb_generate_for_search_form = isset($pa_options['for_search_form']) ? true : false;

		$pa_errors = $this->validateValue($this->getValue());
		$vs_separator = $this->getSeparator();
		$va_element_vals = $this->explodeValue($this->getValue());
		
		$vb_dont_allow_editing = isset($pa_options['row_id']) && ($pa_options['row_id'] > 0) && $o_config->exists($this->getFormat().'_dont_allow_editing_of_codes_when_in_use') && (bool)$o_config->get($this->getFormat().'_dont_allow_editing_of_codes_when_in_use');
		if ($vb_dont_allow_editing) { $pa_options['readonly'] = true; }

		if (!is_array($va_elements = $this->getElements())) { $va_elements = array(); }

		$va_element_controls = $va_element_control_names = array();
		$vn_i=0;

		$vb_next_in_seq_is_present = false;
		foreach($va_elements as $vs_element_name => $va_element_info) {
			if (($va_element_info['type'] == 'SERIAL') && ($va_element_vals[$vn_i] == '')) {
				$vb_next_in_seq_is_present = true;
			}
			$vs_tmp = $this->genNumberElement($vs_element_name, $ps_name, $va_element_vals[$vn_i], $vs_id_prefix, $vb_generate_for_search_form, $pa_options);
			$va_element_control_names[] = $ps_name.'_'.$vs_element_name;

			if (($pa_options['show_errors']) && (isset($pa_errors[$vs_element_name]))) {
				$vs_error_message = preg_replace("/[\"\']+/", "", $pa_errors[$vs_element_name]);
				if ($pa_options['error_icon']) {
					$vs_tmp .= "<a href='#' id='caIdno_{$vs_id_prefix}_{$ps_name}'>".$pa_options['error_icon']."</a>";
				} else {
					$vs_tmp .= "<a href='#' id='caIdno_{$vs_id_prefix}_{$ps_name}'>["._t('Error')."]</a>";
				}
				TooltipManager::add("#caIdno_{$vs_id_prefix}_{$ps_name}", "<h2>"._t('Error')."</h2>{$vs_error_message}");
			}
			$va_element_controls[] = $vs_tmp;
			$vn_i++;
		}
		if ((sizeof($va_elements) < sizeof($va_element_vals)) && (bool)$this->getFormatProperty('allow_extra_elements', array('default' => 1))) {
			$va_extra_vals = array_slice($va_element_vals, sizeof($va_elements));
			
			if (($vn_extra_size = (int)$this->getFormatProperty('extra_element_width', array('default' => 10))) < 1) {
				$vn_extra_size = 10;
			}
			foreach($va_extra_vals as $vn_i => $vs_extra_val) {
				$va_element_controls[] = "<input type='text' name='{$ps_name}_extra_{$vn_i}' id='{$ps_name}_extra_{$vn_i}' value='".htmlspecialchars($vs_extra_val, ENT_QUOTES, 'UTF-8')."' size='{$vn_extra_size}'".($pa_options['readonly'] ? ' disabled="1" ' : '').">";
				$va_element_control_names[] = $ps_name.'_extra_'.$vn_i;
			}
		}
		
		if ($o_config->exists($this->getFormat().'_dont_allow_editing_of_codes_when_in_use')) {
			if (isset($pa_options['row_id']) && ($pa_options['row_id'] > 0)) {
				if ($vb_dont_allow_editing) {
					$va_element_controls[] =  '<span class="formLabelWarning"><i class="caIcon fa fa-info-circle fa-1x"></i> '._t('Value cannot be edited because it is in use').'</span>';	
				} else {
					$va_element_controls[] =  '<span class="formLabelWarning"><i class="caIcon fa fa-exclamation-triangle fa-1x"></i> '._t('Changing this value may break parts of the system configuration').'</span>';	
				}
			}
		}

		$vs_js = '';
		if (($pa_options['check_for_dupes']) && !$vb_next_in_seq_is_present){
			$va_ids = array();
			foreach($va_element_control_names as $vs_element_control_name) {
				$va_ids[] = "'#".$vs_id_prefix.$vs_element_control_name."'";
			}

			$vs_js = '<script type="text/javascript" language="javascript">'."\n// <![CDATA[\n";
			$va_lookup_url_info = caJSONLookupServiceUrl($pa_options['request'], $pa_options['table']);
			$vs_js .= "
				caUI.initIDNoChecker({
					errorIcon: \"".$pa_options['error_icon']."\",
					processIndicator: \"".$pa_options['progress_indicator']."\",
					idnoStatusID: 'idnoStatus',
					lookupUrl: '".$va_lookup_url_info['idno']."',
					searchUrl: '".$pa_options['search_url']."',
					idnoFormElementIDs: [".join(',', $va_ids)."],
					separator: '".$this->getSeparator()."',
					row_id: ".intval($pa_options['row_id']).",
					context_id: ".intval($pa_options['context_id']).",

					singularAlreadyInUseMessage: '".addslashes(_t('Identifier is already in use'))."',
					pluralAlreadyInUseMessage: '".addslashes(_t('Identifier is already in use %1 times'))."'
				});
			";

			$vs_js .= "// ]]>\n</script>\n";
		}

		return join($vs_separator, $va_element_controls).$vs_js;
	}
	# -------------------------------------------------------
	/**
	 * When displayed in a form for editing a multipart identifier will be composed of as many form elements as there are elements defined for the identifier format.
	 * Each form element will have a name beginning with the identifier field name and suffixed with the name of the identifier element. htmlFormValue() 
	 * will pull these values from either an incoming request or, if specified, from the value specified in the $ps_value parameter and return it as a string.
	 * This method is identical to htmlFormValuesAsArray() save that it returns a string rather than an array.
	 *
	 * @param string $ps_name Name of the identifier field (eg. idno)
	 * @param string $ps_value An optional value to extract form values from. If null, values are pulled from the current request. [Default is null]
	 * @param bool $pb_dont_mark_serial_value_as_used Don't record incoming value of the new maximum for SERIAL element sequences. [Default is false]
	 * @param bool $pb_generate_for_search_form Return array of empty values suitable for use in a search (not editing) form. [Default is false]
	 * @param bool $pb_always_generate_serial_values Always generate new values for SERIAL elements, even if they are not set with placeholders. [Default is false]
	 * @return String Identifier from extracted from form and returned as string
	 */
	public function htmlFormValue($ps_name, $ps_value=null, $pb_dont_mark_serial_value_as_used=false, $pb_generate_for_search_form=false, $pb_always_generate_serial_values=false) {
		$va_tmp = $this->htmlFormValuesAsArray($ps_name, $ps_value, $pb_dont_mark_serial_value_as_used, $pb_generate_for_search_form, $pb_always_generate_serial_values);
		if (!($vs_separator = $this->getSeparator())) { $vs_separator = ''; }

		return (is_array($va_tmp)) ? join($vs_separator, $va_tmp) : null;
	}
	# -------------------------------------------------------
	/**
	 * Generates an id numbering template (text with "%" characters where serial values should be inserted)
	 * from a value. The elements in the value that are generated as SERIAL incrementing numbers will be replaced
	 * with "%" characters, resulting is a template suitable for use with BundlableLabelableBaseModelWithAttributes::setIdnoTWithTemplate
	 * If the $pb_no_placeholders parameter is set to true then SERIAL values are omitted altogether from the returned template.
	 *
	 * Note that when the number of element replacements is limited, the elements are counted right-to-left. This means that
	 * if you limit the template to two replacements, the *rightmost* two SERIAL elements will be replaced with placeholders.
	 *
	 * @see BundlableLabelableBaseModelWithAttributes::setIdnoTWithTemplate
	 *
	 * @param string $ps_value The id number to use as the basis of the template
	 * @param int $pn_max_num_replacements The maximum number of elements to replace with placeholders. Set to 0 (or omit) to replace all SERIAL elements.
	 * @param bool $pb_no_placeholders If set SERIAL elements are omitted altogether rather than being replaced with placeholder values
	 *
	 * @return string A template
	 */
	public function makeTemplateFromValue($ps_value, $pn_max_num_replacements=0, $pb_no_placeholders=false) {
		$vs_separator = $this->getSeparator();
		$va_values = $this->explodeValue($ps_value);
		$va_elements = $this->getElements();
		$vn_num_serial_elements = 0;
		foreach ($va_elements as $va_element_info) {
			if ($va_element_info['type'] == 'SERIAL') { $vn_num_serial_elements++; }
		}

		$vn_i = 0;
		$vn_num_serial_elements_seen = 0;
		foreach ($va_elements as $va_element_info) {
			//if ($vn_i >= sizeof($va_values)) { break; }

			switch($va_element_info['type']) {
				case 'SERIAL':
					$vn_num_serial_elements_seen++;

					if ($pn_max_num_replacements <= 0) {	// replace all
						if ($pb_no_placeholders) { unset($va_values[$vn_i]); $vn_i++; continue; }
						$va_values[$vn_i] = '%';
					} else {
						if (($vn_num_serial_elements - $vn_num_serial_elements_seen) < $pn_max_num_replacements) {
							if ($pb_no_placeholders) { unset($va_values[$vn_i]); $vn_i++; continue; }
							$va_values[$vn_i] = '%';
						}
					}
					break;
				case 'CONSTANT':
					$va_values[$vn_i] = $va_element_info['value'];
					break;
				case 'YEAR':
					if (caGetOption('force_derived_values_to_current_year', $va_element_info, false)) {
						$va_tmp = getdate();
						$va_values[$vn_i] = $va_tmp['year'];
					}
					break;
				case 'MONTH':
					if (caGetOption('force_derived_values_to_current_month', $va_element_info, false)) {
						$va_tmp = getdate();
						$va_values[$vn_i] = $va_tmp['mon'];
					}
					break;
				case 'DAY':
					if (caGetOption('force_derived_values_to_current_day', $va_element_info, false)) {
						$va_tmp = getdate();
						$va_values[$vn_i] = $va_tmp['mday'];
					}
					break;
			}

			$vn_i++;
		}

		return join($vs_separator, $va_values);
	}
	# -------------------------------------------------------
	/**
	 * When displayed in a form for editing a multipart identifier will be composed of as many form elements as there are elements defined for the identifier format.
	 * Each form element will have a name beginning with the identifier field name and suffixed with the name of the identifier element. htmlFormValuesAsArray() 
	 * will pull these values from either an incoming request or, if specified, from the value specified in the $ps_value parameter and return them as an array
	 * indexed with keys that identifier name + "_" + element name.
	 *
	 * @param string $ps_name Name of the identifier field (eg. idno)
	 * @param string $ps_value An optional value to extract form values from. If null, values are pulled from the current request. [Default is null]
	 * @param bool $pb_dont_mark_serial_value_as_used Don't record incoming value of the new maximum for SERIAL element sequences. [Default is false]
	 * @param bool $pb_generate_for_search_form Return array of empty values suitable for use in a search (not editing) form. [Default is false]
	 * @param bool $pb_always_generate_serial_values Always generate new values for SERIAL elements, even if they are not set with placeholders. [Default is false]
	 * @return array Array of values for identifer extracted from request
	 */
	public function htmlFormValuesAsArray($ps_name, $ps_value=null, $pb_dont_mark_serial_value_as_used=false, $pb_generate_for_search_form=false, $pb_always_generate_serial_values=false) {
		if (is_null($ps_value)) {
			if(isset($_REQUEST[$ps_name]) && $_REQUEST[$ps_name]) { return $_REQUEST[$ps_name]; }
		}
		if (!is_array($va_element_list = $this->getElements())) { return null; }

		$va_element_names = array_keys($va_element_list);
		$vs_separator = $this->getSeparator();
		$va_element_values = array();
		if ($ps_value) {
			$va_tmp = $this->explodeValue($ps_value);
			foreach ($va_element_names as $vs_element_name) {
				if (!sizeof($va_tmp)) { break; }
				$va_element_values[$ps_name.'_'.$vs_element_name] = array_shift($va_tmp);
			}
			if ((sizeof($va_tmp) > 0) && (bool)$this->getFormatProperty('allow_extra_elements', array('default' => 1))) {
				$vn_i = 0;
				foreach($va_tmp as $vs_tmp) {
					$va_element_values[$ps_name.'_extra_'.$vn_i] = $vs_tmp;
					$vn_i++;
				}
			}
		} else {
			foreach ($va_element_names as $vs_element_name) {
				if(isset($_REQUEST[$ps_name.'_'.$vs_element_name])) {
					$va_element_values[$ps_name.'_'.$vs_element_name] = $_REQUEST[$ps_name.'_'.$vs_element_name];
				}
			}
			
			if ((bool)$this->getFormatProperty('allow_extra_elements', array('default' => 1))) {
				$vn_i = 0;
				while(true) {
					if(isset($_REQUEST[$ps_name.'_extra_'.$vn_i])) {
						$va_element_values[$ps_name.'_extra_'.$vn_i] = $_REQUEST[$ps_name.'_extra_'.$vn_i];
						$vn_i++;
					} else {
						break;
					}
				}
			}
		}

		$vb_isset = false;
		$vb_is_not_empty = false;
		$va_tmp = array();
		$va_elements = $this->getElements();
		foreach($va_elements as $vs_element_name => $va_element_info) {
			if ($va_element_info['type'] == 'SERIAL') {
				if ($pb_generate_for_search_form) {
					$va_tmp[$vs_element_name] = $va_element_values[$ps_name.'_'.$vs_element_name];
					continue;
				}

				if (($va_element_values[$ps_name.'_'.$vs_element_name] == '') || ($va_element_values[$ps_name.'_'.$vs_element_name] == '%') || $pb_always_generate_serial_values) {
					if ($va_element_values[$ps_name.'_'.$vs_element_name] == '%') { $va_element_values[$ps_name.'_'.$vs_element_name] = ''; }
					$va_tmp[$vs_element_name] = $this->getNextValue($vs_element_name, $va_tmp, $pb_dont_mark_serial_value_as_used);
					$vb_isset = $vb_is_not_empty = true;
					continue;
				} else {
					if (!$pb_dont_mark_serial_value_as_used && (intval($va_element_values[$ps_name.'_'.$vs_element_name]) > $this->getSequenceMaxValue($ps_name, $vs_element_name, ''))) {
						$this->setSequenceMaxValue($this->getFormat(), $vs_element_name, join($vs_separator, $va_tmp), $va_element_values[$ps_name.'_'.$vs_element_name]);
					}
				}
			}

			if ($pb_generate_for_search_form) {
				if ($va_element_values[$ps_name.'_'.$vs_element_name] == '') {
					$va_tmp[$vs_element_name] = '';
					break;
				}
			}
			$va_tmp[$vs_element_name] = $va_element_values[$ps_name.'_'.$vs_element_name];

			if ($vn_zeropad_to_length = caGetOption('zeropad_to_length', $va_element_info, null)) {
				$va_tmp[$vs_element_name] = str_pad($va_tmp[$vs_element_name], $vn_zeropad_to_length, "0", STR_PAD_LEFT);
			}

			if (isset($va_element_values[$ps_name.'_'.$vs_element_name])) {
				$vb_isset = true;
			}
			if ($va_element_values[$ps_name.'_'.$vs_element_name] != '') {
				$vb_is_not_empty = true;
			}
		}
		
		if((bool)$this->getFormatProperty('allow_extra_elements', array('default' => 1))) {
			$vn_i = 0;
			while(true) {
				if (isset($va_element_values[$ps_name.'_extra_'.$vn_i]) && ($vs_tmp = $va_element_values[$ps_name.'_extra_'.$vn_i])) {
					$va_tmp[$ps_name.'_extra_'.$vn_i] = $vs_tmp;
					$vn_i++;
				} else {
					break;
				}	
			}
		}
		
		return ($vb_isset && $vb_is_not_empty) ? $va_tmp : null;
	}
	# -------------------------------------------------------
	# Generated id number element
	# -------------------------------------------------------
	/**
	 * Return width of specified element
	 *
	 * @param array $pa_element_info Array of information about the specified element, as returned by getElements()
	 * @param int $pn_default Default width, in characters, to use when width is not set in element info [Default is 3]
	 * @return int Width, in characters
	 */
	private function getElementWidth($pa_element_info, $pn_default=3) {
		$vn_width = isset($pa_element_info['width']) ? $pa_element_info['width'] : 0;
		if ($vn_width <= 0) { $vn_width = $pn_default; }

		return $vn_width;
	}
	# -------------------------------------------------------
	/**
	 * Generate an individual HTML form element for a specific number element. Used by htmlFormElement() to create a set of form element for the current format type.
	 *
	 * @param string $ps_element_name Number element to generate form element for.
	 * @param string $ps_name Name of the identifier field (eg. idno)
	 * @param string $ps_value An optional value to extract form values from. If null, values are pulled from the current request. [Default is null]
	 * @param string $ps_id_prefix Prefix to add to element ID attributes. [Default is null]
	 * @param bool $pb_generate_for_search_form Return array of empty values suitable for use in a search (not editing) form. [Default is false]
	 * @param array $pa_options Options include:
	 *		readonly = Make form element read-only. [Default is false]
	 * @return string HTML output
	 */
	private function genNumberElement($ps_element_name, $ps_name, $ps_value, $ps_id_prefix=null, $pb_generate_for_search_form=false, $pa_options=null) {
		if (!($vs_format = $this->getFormat())) {
			return null;
		}
		if (!($vs_type = $this->getType())) {
			return null;
		}
		$vs_element = '';

		$va_element_info = $this->opa_formats[$vs_format][$vs_type]['elements'][$ps_element_name];
		$vs_element_form_name = $ps_name.'_'.$ps_element_name;

		$vs_element_value = $ps_value;
		switch($va_element_info['type']) {
			# ----------------------------------------------------
			case 'LIST':
				if (!is_array($va_element_info['values'])) { $va_element_info['values'] = []; }
				if (!$vs_element_value || $va_element_info['editable'] || $pb_generate_for_search_form) {
					if (!$vs_element_value && !$pb_generate_for_search_form) { $vs_element_value = $va_element_info['default']; }
					$vs_element = '<select name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'">';
					if ($pb_generate_for_search_form) {
						$vs_element .= "<option value='' selected='selected'>-</option>";
					}
					foreach ($va_element_info['values'] as $ps_value) {
						if (trim($ps_value) === trim($vs_element_value)) { $vs_selected = ' selected="selected"'; } else { $vs_selected = ''; }
						$vs_element .= '<option value="'.$ps_value.'"'.$vs_selected.'>'.$ps_value.'</option>';
					}

					if (!$pb_generate_for_search_form) {
						if (!in_array($vs_element_value, $va_element_info['values']) && strlen($vs_element_value) > 0) {
							$vs_element .= '<option value="'.$vs_element_value.'" selected="selected">'.$vs_element_value.'</option>';
						}
					}

					$vs_element .= '</select>';
				} else {
					$vs_element_val_proc = (in_array($vs_element_value, $va_element_info['values']) ? $vs_element_value : $va_element_info['values'][0]);
					$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_val_proc, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_val_proc;
				}

				break;
			# ----------------------------------------------------
			case 'SERIAL':
				$vn_width = $this->getElementWidth($va_element_info, 3);

				if ($pb_generate_for_search_form) {
					$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="" maxlength="'.$vn_width.'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
				} else {
					if ($vs_element_value == '') {
						$vs_next_num = $this->getNextValue($ps_element_name, null, true);
						$vs_element .= '&lt;'._t('Will be assigned %1 when saved', $vs_next_num).'&gt;';
					} else {
						if ($va_element_info['editable']) {
							$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
						} else {
							$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
						}
					}
				}
				break;
			# ----------------------------------------------------
			case 'CONSTANT':
				$vn_width = $this->getElementWidth($va_element_info, 3);

				if (!$vs_element_value) { $vs_element_value = $va_element_info['value']; }
				if ($va_element_info['editable'] || $pb_generate_for_search_form) {
					$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
				} else {
					$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
				}
				break;
			# ----------------------------------------------------
			case 'FREE':
			case 'NUMERIC':
			case 'ALPHANUMERIC':
				if (!$vs_element_value && !$pb_generate_for_search_form) { $vs_element_value = $va_element_info['default']; }
				$vn_width = $this->getElementWidth($va_element_info, 3);
				if (!$vs_element_value || $va_element_info['editable'] || $pb_generate_for_search_form) {
					$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
				} else {
					$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
				}
				break;
			# ----------------------------------------------------
			case 'YEAR':
			case 'MONTH':
			case 'DAY':
				$vn_width = $this->getElementWidth($va_element_info, 5);
				$va_date = getdate();
				if ($vs_element_value == '') {
					$vn_value = '';
					if (!$pb_generate_for_search_form) {
						if ($va_element_info['type'] == 'YEAR') { $vn_value = ($va_element_info['width'] == 2) ? substr($va_date['year'], 2, 2) : $va_date['year']; }
						if ($va_element_info['type'] == 'MONTH') { $vn_value = $va_date['mon']; }
						if ($va_element_info['type'] == 'DAY') { $vn_value = $va_date['mday']; }
					}

					if ($va_element_info['editable'] || $pb_generate_for_search_form) {
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vn_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vn_value, ENT_QUOTES, 'UTF-8').'"/>'.$vn_value;
					}
				} else {
					if ($va_element_info['editable'] || $pb_generate_for_search_form) {
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
					}
				}

				break;
			# ----------------------------------------------------
				case 'PARENT':
				$vn_width = $this->getElementWidth($va_element_info, 3);

				if ($pb_generate_for_search_form) {
					$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="" maxlength="'.$vn_width.'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
				} else {
					if ($vs_element_value == '') {
						$vs_next_num = $this->getParentValue();
						$vs_element .= '&lt;'._t('%1', $vs_next_num).'&gt;'.'<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_next_num, ENT_QUOTES, 'UTF-8').'"/>';
					} else {
						if ($va_element_info['editable']) {
							$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' disabled="1" ' : '').'/>';
						} else {
							$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
						}
					}
				}
					break;
			# ----------------------------------------------------
			default:
				return '[Invalid element type]';
				break;
			# ----------------------------------------------------
		}
		return $vs_element;
	}
	# -------------------------------------------------------
	/**
	 * Get maximum sequence value for SERIAL element
	 *
	 * @param string $ps_format Format to get maximum sequence value for
	 * @param string $ps_element Element name to get maximum sequence value for
	 * @param string $ps_idno_stub Identifier stub (identifier without serial value) to get maximum sequence value for
	 * @return int Integer value or false on error
	 */
	public function getSequenceMaxValue($ps_format, $ps_element, $ps_idno_stub) {
		$this->opo_db->dieOnError(false);

		$vn_minimum_value = caGetOption('minimum_value', $this->getElementInfo($ps_element), 0, ['castTo' => 'int']);
		if (!($qr_res = $this->opo_db->query("
			SELECT seq
			FROM ca_multipart_idno_sequences
			WHERE
				(format = ?) AND (element = ?) AND (idno_stub = ?)
		", $ps_format, $ps_element, $ps_idno_stub))) {
			return false;
		}
		if (!$qr_res->nextRow()) { return $vn_minimum_value - 1; }
		return (($vn_v = $qr_res->get('seq')) < $vn_minimum_value) ? ($vn_minimum_value - 1) : $vn_v;
	}
	# -------------------------------------------------------
	/**
	 * Record new maximum sequence value for SERIAL element
	 *
	 * @param string $ps_format Format to set sequence for
	 * @param string $ps_element Element name to set sequence for
	 * @param string $ps_idno_stub Identifier stub (identifier without serial value) to set sequence for
	 * @param string $pn_value Maximum SERIAL value for this format/element/stub
	 * @return bool True on success, false on failure
	 */
	public function setSequenceMaxValue($ps_format, $ps_element, $ps_idno_stub, $pn_value) {
		$this->opo_db->dieOnError(false);

		$this->opo_db->query("
			DELETE FROM ca_multipart_idno_sequences
			WHERE format = ? AND element = ? AND idno_stub = ?
		", [$ps_format, $ps_element, $ps_idno_stub]);

		$pn_value = (int)preg_replace("![^\d]+!", "", $pn_value);
		return $this->opo_db->query("
			INSERT INTO ca_multipart_idno_sequences
			(format, element, idno_stub, seq)
			VALUES
			(?, ?, ?, ?)
		", [$ps_format, $ps_element, $ps_idno_stub, $pn_value]);
	}
	# -------------------------------------------------------
	/**
	 * Set database connection to use for queries
	 *
	 * @param Db $po_db A database connection instance
	 * @return void
	 */
	public function setDb($po_db) {
		$this->opo_db = $po_db;
	}
	# -------------------------------------------------------
	/**
	 * Returns true if editable is set to 1 for the identifier, otherwise returns false
	 * Also, if the identifier consists of multiple elements, false will be returned.
	 *
	 * @param string $ps_format_name Name of format
	 * @param array $pa_options Options include:
	 *		singleElementsOnly = Only consider formats with a single editable element to be editable. [Default is false]
	 * @return bool
	 */
	public function isFormatEditable($ps_format_name, $pa_options=null) {
		if (!is_array($va_elements = $this->getElements())) { return false; }
		
		$vb_single_elements_only = caGetOption('singleElementsOnly', $pa_options, false);
		
		foreach($va_elements as $vs_element => $va_element_info) {
			if (isset($va_element_info['editable']) && (bool)$va_element_info['editable']) { return true; }
			if ($vb_single_elements_only) { return false; }
		}
		return false;
	}
	# -------------------------------------------------------
}