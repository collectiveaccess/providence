<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2023 Whirl-i-Gig
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
 * @subpackage Dashboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
/**
 *
 */ 
require_once(__CA_LIB_DIR__.'/ApplicationVars.php'); 	
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');

abstract class BaseRefinery {
	# -------------------------------------------------------
	/** 
	 *
	 */
	static $s_refinery_settings = array();
	
	/** 
	 *
	 */
	protected $ops_name = null;
	
	/** 
	 *
	 */
	protected $ops_title = null;
	
	/** 
	 *
	 */
	protected $ops_description = null;
	
	/**
	 *
	 */
	protected $opb_returns_multiple_values;

	/**
	 * @var bool if the refinery supports relationships
	 */
	protected $opb_supports_relationships = false;

	# -------------------------------------------------------
	public function __construct() {
	
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getRefinerySettings() {
		$va_base_settings = BaseRefinery::$s_refinery_settings[$this->getName()];
		if ($this->supportsRelationships()){
			$va_base_settings[$this->getName() . '_relationships'] =  array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationships'),
				'description' => _t('A list (array) of relationships related to the %1.', $this->getTitle())
			);
		}
		$va_base_settings[$this->getName() . '_applyImportItemSettings'] =  array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Apply import item settings to refinery values'),
			'description' => _t('Apply applyRegularExpressions and replacement values transformations to values in the %1.', $this->getTitle())
		);
		return $va_base_settings;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getName() {
		return $this->ops_name; 
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getTitle() {
		return $this->ops_title; 
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getDescription() {
		return $this->ops_description; 
	}
	# -------------------------------------------------------
	/**
	 * @return boolean
	 */
	public function supportsRelationships() {
		return $this->opb_supports_relationships;
	}

	# -------------------------------------------------------
	/**
	 * Process template expression, replacing "^" prefixed placeholders with data values.
	 *
	 * Some data formats (including XML formats such as FMPXML) support repeating values in field. When processing data with repeating values
	 * the $index parameter may be set to return the value at a specific index. A null value will be returned is the index is not defined.
	 * For values at a specified index BaseRefinery::parsePlaceholder() can optionally split the value into sub-values using a delimiter and return
	 * a specific sub-value using a zero-based index set in the "returnDelimitedValueAt" option.
	 *
	 * Depending upon parameters and options set BaseRefinery::parsePlaceholder() may return a string or array:
	 
	 * If $index is null (no value index set) an array with all values is returned. If $index is set the value at that index will be returned as a string.
	 * If $index is set and the delimiter and returnDelimitedValueAt options are set, a string will be returned with a delimited value extracted from the specificed value index.
	 *
	 * @param string $placeholder An expression with at least one placeholder. (Eg. "^1"). Can also be a text expression with embedded placeholders (Eg. "This is ^1 and this is ^2). The placeholders are valid specifiers for the data reader being used prefixed with a caret ("^"). For flat formats like Excel, they will look like ^1, ^2, etc. For XML formats they will be Xpath. Eg. ^/teiHeader/encodingDesc/projectDesc
	 * @param array $source_data An array of data to use in substitutions. Array is indexed by placeholder name *without* the leading caret.
	 * @param array $item The mapping item information array containing settings for the current mapping.
	 * @param int $index The index of the value to return. For non-repeating values this should be omitted or set to zero. For repeating values, this is a zero-based index indicating which value is returned. If a value for the specified index does not exist null will be returned. If the index is set to null then an array with all values is returned.
	 * @param array $options An array of options. Options include:
	 *		reader = An instance of BaseDataReader. Will be used to pull values for placeholders that are not defined in $source_data. This is useful for formats like XML where placeholders may be arbitrary XPath expressions that must be executed rather than parsed. [Default is null]
	 *		returnAsString = Return array of repeating values as string using delimiter. Has effect only is $index parameter is set to null. [Default is false]
	 *		delimiter = Delimiter to join array values with when returnAsString option is set; or the delimiter to use when breaking apart a value for return via the returnDelimitedValueAt option. [Default is ";"]
	 *		returnDelimitedValueAt = Return a specific part of a value delimited by the "delimiter" option when $index is set to a non-null value. The option value is a zero-based index. [Default is null – return entire value]
	 *		applyImportItemSettings = Apply mapping options such as applyRegularExpressions to value. [Default is true]
	 *
	 * @return mixed An array or string
	 */
	public static function parsePlaceholder(string $placeholder, array $source_data, array $item, ?int $value_index=null, ?array $options=null) {
		$reader = caGetOption('reader', $options, null);
		$return_as_string = caGetOption("returnAsString", $options, false);
		$get_at_index = caGetOption('returnDelimitedValueAt', $options, null);
		$apply_import_item_settings = caGetOption('applyImportItemSettings', $options, true);
		
		$placeholder = trim($placeholder);
		$key = substr($placeholder, 1);
		
		$delimiters = caGetOption("delimiter", $options, null);
		if (is_array($delimiters)) { $delimiter = $delimiters[0]; } else { $delimiter = $delimiters; $delimiters = [$delimiters]; }
		$delimiter = stripslashes($delimiter);
		
		if ($reader && !$reader->valuesCanRepeat()) {
			// Expand delimited values in non-repeating sources to simulate repeats
			foreach($source_data as $k => $v) {
				if (!is_array($source_data[$k])) {
				   //$source_data[$k] = array_filter(explode($delimiter, $source_data[$k]), "strlen");
				   $source_data[$k] = [0 => $source_data[$k]] ; //array_filter(preg_split("!(".preg_quote(join('|', $delimiters)'!').")!", $source_data[$k]), "strlen");
				}
			}
		}
		
		$mval = null;

		if (($placeholder[0] == '^') && (strpos($placeholder, '^', 1) === false) && (sizeof($t = caExtractTagsFromTemplate($placeholder)) == 1) && (array_shift($t) === $key)) {
			// Placeholder is a single caret-value
			$tag = explode('~', $key);
			
			if (isset($source_data[$tag[0]])) {
				$mval = $source_data[$tag[0]];
				if(!is_array($mval)) { $mval = [$mval]; }
			} elseif ($reader) {
				$mval = $reader->get($tag[0], ['returnAsArray' => true]);
				if(!is_array($mval)) { $mval = [$mval]; }
			} else {
				$mval = null;
			}
			
			if (is_array($mval) && $tag[1]) { 
				if(is_null($value_index)) {
					foreach($mval as $vn_i => $sval) {
						$mval[$vn_i] = caProcessTemplateTagDirectives($sval, [$tag[1]]);
					}
				} elseif(isset($mval[$value_index])) {
					$mval = [
						$value_index => caProcessTemplateTagDirectives($mval[$value_index], [$tag[1]])
					];
				} else {
					$mval = null;
				}
			}
		} elseif(strpos($placeholder, '^') !== false) {
			// Placeholder is a full template – requires extra processing
			if ($reader) {
				$tags = caExtractTagsFromTemplate($placeholder);
				
				// Make sure all tags are in source data array, otherwise try to pull them from the reader.
				// Some formats, mainly XML, can take expressions (XPath for XML) that are not precalculated in the array
				$extracted_data = [];
				foreach($tags as $vs_tag) {
					$tag = explode('~', $vs_tag);
					
					if (!isset($source_data[$tag[0]])) { 
						$mval = $reader->get($tag[0], ['returnAsArray' => true]);
						if(!is_array($mval)) { $mval = [$mval]; }
					} else {
						$mval = $source_data[$tag[0]];
						if(!is_array($mval)) { $mval = [$mval]; }
					}
					
					if(!is_null($value_index)) { 
						$mval = $va_val[$value_index] ?? null;
					}
					
					foreach($mval as $i => $v) {
						$extracted_data[$i][$vs_tag] = $v;
					}
				}
				
				$mval = [];
				foreach($extracted_data as $i => $iteration) {
					$mval[] = caProcessTemplate($placeholder, $va_iteration);
				}
			} else {
				// Is plain text
				if (!isset($source_data[substr($placeholder, 1)])) { return null; }
				$mval = caProcessTemplate($placeholder, $source_data);
			}
		} else {
			$mval = $placeholder;
		}
		
		// Get specific index for repeating value
		if (is_array($mval) && !is_null($value_index)) {
			$mval = isset($mval[$value_index]) ? [$mval[$value_index]] : null;
			
			if (is_array($item['settings']['original_values']) && (($ix = array_search(mb_strtolower($mval), $item['settings']['original_values'], true)) !== false)) {
				$mval = $item['settings']['replacement_values'][$ix];
			}
			if ($apply_import_item_settings) {
				$mval = caProcessImportItemSettingsForValue($mval, $item['settings'] ?? []);
			}
			// delimiter?
			if(!is_null($get_at_index)) {
				$dvals = preg_split('!'.preg_quote(join('|', $delimiters), '!').'!', $mval[0]);
				return $dvals[$get_at_index] ?? null;
			}
			return $mval[0];
		}

		// Do processing on members
		if(is_array($mval)) {
			foreach($mval as $vn_i => $sval) {
				if (is_array($item['settings']['original_values']) && (($ix = array_search(mb_strtolower($sval), $item['settings']['original_values'], true)) !== false)) {
					$sval = $item['settings']['replacement_values'][$ix];
				}
				$mval[$vn_i] = trim($sval);
			}
		}
		
		return ($return_as_string && is_array($mval)) ? trim(join($delimiter, $mval)) : $mval;
	}
	# -------------------------------------------------------
	/**
	 * Process a mapped value
	 *
	 * @param array $pa_destination_data Array of data that to be imported. Will contain the product of all mappings performed on the current source row *to date*. The refinery can make any required additions and modifications to this data; since it's passed by reference those changes will be returned.
	 * @param array $pa_group Specification and settings for the mapping group being processed
	 * @param mixed $pa_item Specification and settings for the mapping item being processed. Settings are in an array under the "settings" key
	 * @param array $pa_source_data The entire source row. You can extract the current value being processed by plugging the item "source" specification into $pa_source_data
	 * @param array $pa_options Refinery-specific processing options
	 *
	 * @return array The value(s) to add 
	 */
	abstract function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options=null);
	# -------------------------------------------------------	
	/**
	 * Identifies return value of refinery. If the refinery is designed to return a set of values overwriting all group values this method will return true. If
	 * the refinery returns a single transformed value intended to be inserted in place of the source value (Eg. a simple single-item mapping) this method
	 * will return false.
	 *
	 * @return bool True if refinery returns multiple values, false if it returns a single value
	 */
	abstract function returnsMultipleValues();
	# -------------------------------------------------------	
	/**
	 * 
	 *
	 * @return bool Always returns true
	 */
	public function setReturnsMultipleValues($pb_returns_multiple_values) {
		$this->opb_returns_multiple_values = (bool)$pb_returns_multiple_values;
		
		return true;
	}
	# -------------------------------------------------------	
	/**
	 * Does refinery return actual row_ids rather than idnos
	 *
	 * @return bool Always return false
	 */
	public function returnsRowIDs() {
		return false;
	}
	# -------------------------------------------------------	
}
