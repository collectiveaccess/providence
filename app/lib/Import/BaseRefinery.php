<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2015 Whirl-i-Gig
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
						'displayType' => DT_SELECT,
						'width' => 10, 'height' => 1,
						'takesLocale' => false,
						'default' => '',
						'label' => _t('Relationships'),
						'description' => _t('A list (array) of relationships related to the %refinery.', array('refinery' => $this->getTitle()))
				);
			}
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
		 * Process template expression, replacing "^" prefixed placeholders with data values
		 *
		 * @param string $ps_placeholder An expression with at least one placeholder. (Eg. "^1"). Can also be a text expression with embedded placeholders (Eg. "This is ^1 and this is ^2). The placeholders are valid specifiers for the data reader being used prefixed with a caret ("^"). For flat formats like Excel, they will look like ^1, ^2, etc. For XML formats they will be Xpath. Eg. ^/teiHeader/encodingDesc/projectDesc
		 * @param array $pa_source_data An array of data to use in substitutions. Array is indexed by placeholder name *without* the leading caret.
		 * @param array $pa_item The mapping item information array containing settings for the current mapping.
		 * @param int $pn_index The index of the value to return. For non-repeating values this should be omitted or set to zero. For repeating values, this is a zero-based index indicating which value is returned. If a value for the specified index does not exist null will be returned. If the index is set to null then an array with all values is returned.
		 * @param array $pa_options An array of options. Options include:
		 *		reader = An instance of BaseDataReader. Will be used to pull values for placeholders that are not defined in $pa_source_data. This is useful for formats like XML where placeholders may be arbitrary XPath expressions that must be executed rather than parsed. [Default is null]
		 *		returnAsString = Return array of repeating values as string using delimiter. Has effect only is $pn_index parameter is set to null. [Default is false]
		 *		delimiter = Delimiter to join array values with when returnAsString option is set; or the delimiter to use when breaking apart a value for return via the returnDelimitedValueAt option. [Default is ";"]
		 *		returnDelimitedValueAt = Return a specific part of a value delimited by the "delimiter" option. Only has effect when returning a specific index of a repeating value (Eg. $pn_index is not null). The option value is a zero-based index. [Default is null – return entire value]
		 *
		 * @return mixed An array or string
		 */
		public static function parsePlaceholder($ps_placeholder, $pa_source_data, $pa_item, $pn_index=null, $pa_options=null) {
			$o_reader = caGetOption('reader', $pa_options, null);
			$pb_return_as_string = caGetOption("returnAsString", $pa_options, false);
			$pn_get_at_index = caGetOption('returnDelimitedValueAt', $pa_options, null);
			
			$ps_placeholder = trim($ps_placeholder);
			$vs_key = substr($ps_placeholder, 1);
			
			$va_delimiter = caGetOption("delimiter", $pa_options, null);
			if (is_array($va_delimiter)) { $vs_delimiter = $va_delimiter[0]; } else { $vs_delimiter = $va_delimiter; $va_delimiter = [$va_delimiter]; }
			
			if ($o_reader && !$o_reader->valuesCanRepeat() && $vs_delimiter) {
			    // Expand delimited values in non-repeating sources to simulate repeats
			    foreach($pa_source_data as $vs_k => $vm_v) {
			        if (!is_array($pa_source_data[$vs_k])) {
			            $pa_source_data[$vs_k] = array_filter(explode($vs_delimiter, $pa_source_data[$vs_k]), "strlen");
			        }
			    }
			}
			if($vs_delimiter && !is_null($pn_index) && !is_null($pn_get_at_index)) {
			    // 
			    foreach($va_delimiter as $vn_index => $vs_delim) {
                    if (!trim($vs_delim, "\t ")) { unset($va_delimiter[$vn_index]); continue; }
                    $va_delimiter[$vn_index] = preg_quote($vs_delim, "!");
                }
			    foreach($pa_source_data as $vs_k => $vm_v) {
			        if(!is_array($vm_v)) { $pa_source_data[$vs_k] = [$vm_v]; }
			        if(isset($pa_source_data[$vs_k][$pn_index])) {
			            $va_tmp = preg_split("!(".join("|", $va_delimiter).")!", $pa_source_data[$vs_k][$pn_index]);
			            
			            // If only one delimited value in value string then return that regardless of the index (it's not a delimited string)
			            $pa_source_data[$vs_k][$pn_index] = (sizeof($va_tmp) == 1) ? $va_tmp[0] : (isset($va_tmp[$pn_get_at_index]) ? $va_tmp[$pn_get_at_index] : null);
			        
			        }
                }
			}
			if (($ps_placeholder[0] == '^') && (strpos($ps_placeholder, '^', 1) === false)) {
				// Placeholder is a single caret-value
				$va_tag = explode('~', $vs_key);
				
				if (isset($pa_source_data[$va_tag[0]])) {
				    $vm_val = $pa_source_data[$va_tag[0]];
				} elseif ($o_reader) {
					$vm_val = $o_reader->get($va_tag[0], array('returnAsArray' => true, 'delimiter' => $vs_delimiter));
					
				} else {
					$vm_val = null;
				}
				if(!is_array($vm_val) && $vs_delimiter) {
                    $vm_val = explode($vs_delimiter, $vm_val);
                } elseif(!is_array($vm_val)) {
                    $vm_val = [$vm_val];
                }
				
				if ($va_tag[1]) { 
					foreach($vm_val as $vn_i => $vs_val) {
						$vm_val[$vn_i] = caProcessTemplateTagDirectives($vs_val, [$va_tag[1]]);
					}
				}
				
			} elseif(strpos($ps_placeholder, '^') !== false) {
				// Placeholder is a full template – requires extra processing
				if ($o_reader) {
					$va_tags = caExtractTagsFromTemplate($ps_placeholder);
					
					// Make sure all tags are in source data array, otherwise try to pull them from the reader.
					// Some formats, mainly XML, can take expressions (XPath for XML) that are not precalculated in the array
					$va_extracted_data = [];
					foreach($va_tags as $vs_tag) {
						$va_tag = explode('~', $vs_tag);
						if (!isset($pa_source_data[$va_tag[0]])) { 
						    $va_val = $o_reader->get($va_tag[0], array('returnAsArray' => true));
						} else {
						    $va_val = $pa_source_data[$va_tag[0]];
						}
						
						if(!is_array($va_val)) { $va_val = [$va_val]; }
                        foreach($va_val as $i => $v) {
                            $va_extracted_data[$i][$vs_tag] = $v;
                        }
					}
					
					foreach($va_extracted_data as $i => $va_iteration) {
					    $vm_val[] = caProcessTemplate($ps_placeholder, $va_iteration);
					}
				} else {
					// Is plain text
					if (!isset($pa_source_data[substr($ps_placeholder, 1)])) { return null; }
					$vm_val = caProcessTemplate($ps_placeholder, $pa_source_data);
				}
			} else {
				$vm_val = $ps_placeholder;
			}
			
			// Get specific index for repeating value
			if (is_array($vm_val) && !is_null($pn_index)) {
				$vm_val = isset($vm_val[$pn_index]) ? [$vm_val[$pn_index]] : null;
			}
	
			// If we're returning the entire array, do processing on members and return
			if(is_array($vm_val)) {
				foreach($vm_val as $vn_i => $vs_val) {
					if (is_array($pa_item['settings']['original_values']) && (($vn_ix = array_search(mb_strtolower($vs_val), $pa_item['settings']['original_values'])) !== false)) {
						$vs_val = $pa_item['settings']['replacement_values'][$vn_ix];
					}
					$vm_val[$vn_i] = trim($vs_val);
				}
				
				$vm_val = caProcessImportItemSettingsForValue($vm_val, $pa_item['settings']);
				
				if (is_null($pn_get_at_index)) {
                    if ($pb_return_as_string) {
                        return join($vs_delimiter, $vm_val);
                    }
				    return $vm_val;
				}
			}
			if (!is_array($vm_val)) { $vm_val = [$vm_val]; }
			foreach($vm_val as $i => $v) {
                if (is_array($pa_item['settings']['original_values']) && (($vn_i = array_search(mb_strtolower($v), $pa_item['settings']['original_values'])) !== false)) {
                    $v = $pa_item['settings']['replacement_values'][$vn_i];
                }
                $v = caProcessImportItemSettingsForValue($v, $pa_item['settings']);
                $vm_val[$i] = $v;
            }
			
			return $pb_return_as_string ? trim(join($vs_delimiter, $vm_val)) : $vm_val;
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
