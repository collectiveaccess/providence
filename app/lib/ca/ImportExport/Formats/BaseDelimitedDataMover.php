<?php
/** ---------------------------------------------------------------------
 * BaseDelimitedDataMover.php : base class for all delimited text-based import/export formats
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @subpackage ImportExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/BaseDataMoverFormat.php');
	

	class BaseDelimitedDataMover extends BaseDataMoverFormat {
		# -------------------------------------------------------
		public function __construct() {
			parent::__construct();
		}
		# -------------------------------------------------------
		# Import
		# -------------------------------------------------------
		/**
		 * Read and parse metadata
		 *
		 * @param $pm_input mixed - A file path or file resource containing the metadata to be parsed
		 * @param $pa_options array - An array of parse options
		 */
		public function parse($pm_input, $pa_options=null) {
			parent::parse($pm_input, $pa_options=null);
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Outputs metadata to specified target using specified options
		 *
		 * @param $pm_target string|file resource - a file path or file resource to output the metadata to. If set to null metadata is used as return value (this can be memory intensive for very large metadata sets as the entire data set must be kept in memory)
		 * @param boolean|string - true on success, false on failure; if $pm_target is null or 'returnOutput' option is set to true then string representation of output metadata will be returned
		 * 		Support options are:
		 *				returnOutput - if true, then output() will return metadata, otherwise output is only sent to $pm_target
		 *				returnAsString - if true (and returnOutput is true as well) then returned output is a string (all records concatenated), otherwise output will be an array of strings, one for each record
		 * 				
		 * return array Returns array of output, one item for each record, except if the 'returnAsString' option is set in which case records are returned concatenated as a string
		 */
		public function output($pm_target, $pa_options=null) {
			global $g_ca_data_import_export_format_definitions;
			
			if (!$this->opa_records || !sizeof($this->opa_records)) { return false; }
			
			$va_elements = $this->getElementList();
			$va_format_info = $this->getFormatInfo();
			
			$r_fp = null;
			if ($pm_target) {
				if(is_string($pm_target)) {
					$r_fp = fopen($pm_target, 'w');
				} else {
					if(is_file($pm_target)) {
						$r_fp = $pm_target;
					} else {
						return false;
					}
				}
			}
	
			print_r($this->opa_records); die;
			
			$va_record_output = array();			// delimited for each record
			foreach($this->opa_records as $vn_i => $va_record) {
				$va_row = array();
				
				foreach($va_record as $vs_group => $va_mappings) {
					
				
				}
				
				
				$va_record_output[] = join($g_ca_data_import_export_format_definitions['Tab']['delimiter'], $va_row);
				
				if ($r_fp) {
					fputs($r_fp, $vs_output);
					
					if (!(isset($pa_options['returnOutput']) && $pa_options['returnOutput'])) {
						$vs_output = '';
					}
				}
			}
			
			if ($r_fp) {
				fclose($r_fp);
			}
			
			if (is_null($pm_target) || (isset($pa_options['returnOutput']) && $pa_options['returnOutput'])) {
				if (isset($pa_options['returnAsString']) && $pa_options['returnAsString']) {
					return join('', $va_record_output);
				}
				return $va_record_output;
			}
			return true;
		}
		# -------------------------------------------------------
	}
?>