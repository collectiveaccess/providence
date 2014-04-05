<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2014 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__.'/core/ApplicationVars.php'); 	
 	require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php'); 	
 
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
		# -------------------------------------------------------
		public function __construct() {
		
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function getRefinerySettings() {
			return BaseRefinery::$s_refinery_settings[$this->getName()]; 
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
		 *
		 */
		public static function parsePlaceholder($ps_placeholder, $pa_source_data, $pa_item, $ps_delimiter=null, $pn_index=0, $pa_options=null) {
			$o_reader = caGetOption('reader', $pa_options, null);
			
			$ps_placeholder = trim($ps_placeholder);
			$vs_key = substr($ps_placeholder, 1);
			
			if (($ps_placeholder[0] == '^') && (strpos($ps_placeholder, '^', 1) === false)) {
				// Placeholder is a single caret-value
				if ($o_reader) {
					$vm_val = $o_reader->get($vs_key, array('returnAsArray' => true));
				} else {
					if (!isset($pa_source_data[substr($ps_placeholder, 1)])) { return null; }
					$vm_val = $pa_source_data[substr($ps_placeholder, 1)];
				}
			} elseif(strpos($ps_placeholder, '^') !== false) {
				// Placeholder is a full template – requires extra processing
				if ($o_reader) {
					$va_tags = array();
					
					// get a list of all tags in placeholder
					if (preg_match_all(__CA_BUNDLE_DISPLAY_TEMPLATE_TAG_REGEX__, $ps_placeholder, $va_matches)) {
						foreach($va_matches[1] as $vn_i => $vs_possible_tag) {
							$va_matches[1][$vn_i] = rtrim($vs_possible_tag, "/.");	// remove trailing slashes and periods
						}
						$va_tags = $va_matches[1];
					}
					// Make sure all tags are in source data array, otherwise try to pull them from the reader.
					// Some formats, mainly XML, can take expressions (XPath for XML) that are not precalculated in the array
					foreach($va_tags as $vs_tag) {
						if (isset($pa_source_data[$vs_tag])) { continue; }
						$va_val = $o_reader->get($vs_key, array('returnAsArray' => true));
						$pa_source_data[$vs_tag] = $va_val[$pn_index];
					}
					$vm_val = caProcessTemplate($ps_placeholder, $pa_source_data);
				} else {
					// Is plain text
					if (!isset($pa_source_data[substr($ps_placeholder, 1)])) { return null; }
					$vm_val = $pa_source_data[substr($ps_placeholder, 1)];
				}
			} else {
				$vm_val = $ps_placeholder;
			}
			
			if (is_array($vm_val) && !is_null($pn_index)) {
				$vm_val = isset($vm_val[$pn_index]) ? $vm_val[$pn_index] : null;
			}
			
			if(is_array($vm_val)) {
				foreach($vm_val as $vn_i => $vs_val) {
					if (is_array($pa_item['settings']['original_values']) && (($vn_ix = array_search(mb_strtolower($vs_val), $pa_item['settings']['original_values'])) !== false)) {
						$vs_val = $pa_item['settings']['replacement_values'][$vn_ix];
					}
					$vm_val[$vn_i] = trim($vs_val);
				}
				
				$vm_val = caProcessImportItemSettingsForValue($vm_val, $pa_item['settings']);
				
				if (caGetOption("returnAsString", $pa_options, false)) {
					$vs_delimiter = caGetOption("delimiter", $pa_options, '');
					return join($vs_delimiter, $vm_val);
				}
				return $vm_val;
			}
			
			if ($ps_delimiter) {
				$va_val = explode($ps_delimiter, $vm_val);
				if ($pn_index < sizeof($va_val)) {
					if (!($vm_val = $va_val[$pn_index])) { $vm_val = ''; }
				} else {
					$vm_val = array_shift($va_val);
				}
			}
			$vm_val = trim($vm_val);
			
			if (is_array($pa_item['settings']['original_values']) && (($vn_i = array_search(mb_strtolower($vm_val), $pa_item['settings']['original_values'])) !== false)) {
				$vm_val = $pa_item['settings']['replacement_values'][$vn_i];
			}
			
			$vm_val = caProcessImportItemSettingsForValue($vm_val, $pa_item['settings']);
			
			return trim($vm_val);
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
	}
?>