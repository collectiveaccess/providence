<?php
/** ---------------------------------------------------------------------
 * Tab.php : import/export module for tab-delimited data format
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

	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/BaseDelimitedDataMover.php');
	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/Tab/TabReader.php');
	
	
global $g_ca_data_import_export_format_definitions;
$g_ca_data_import_export_format_definitions['Tab'] = array(
	'name' 				=> _t('Tab delimited text'),
	'version' 			=> '2011',
	'description' 		=> _t('Tab delimited text'),
	'url' 				=> '',
	'output_mimetype'	=> 'text/plain',
	'file_extension'	=> 'tab',
	'delimiter' => "\t"
);

	class DataMoverTab extends BaseDelimitedDataMover {
		# -------------------------------------------------------
		/** Name of format. Should be same as filename without extension */
		protected $ops_name = 'Tab';
		
		/** Short text describing format */ 
		protected $ops_description = '';
		
		/** Format version number */
		const VERSION = '1.0';
		
		/** URL with information on format */
		const INFO_URL = '';
		
		/** Extension to use when outputting this format to a file */
		const EXTENSION = 'tab';
		
		/** Mimetype to use when outputting this format */
		const MIMETYPE = 'text/plain';
		
		 /** Metadata prefix */
		const METADATA_PREFIX = '';
		
		/** XML namespace uri for output format */
		const METADATA_NAMESPACE = '';
		
		/** XML schema uri for output format */
		const METADATA_SCHEMA = '';
		
		/** XML namespace uri for unqualified Dublin Core */
		const DC_NAMESPACE_URI = '';
		
		/** XML namespace URI for XML schema */
		const XML_SCHEMA_NAMESPACE_URI = '';
		
		# -------------------------------------------------------
		public function __construct() {
		
		}
		# -------------------------------------------------------
		# Import
		# -------------------------------------------------------
		/**
		 * Read and parse metadata
		 *
		 * @param $ps_url_or_path string - URL or directory path to a tab-delimited text file 
		 * @param $po_caller DataImporter - Instance of DataImporter to call importRecord() on for each processed row 
		 * @param $pa_mappings_by_group array - Array of import mappings keyed on mapping group name
		 * @param $po_instance - An instance of the model class for the table we're importing into
		 * @param $pa_options array - An array of options to use
		 */
		public function import($ps_url_or_path, $po_caller, $pa_mappings_by_group, $po_instance, $pa_options=null) {
			$o_reader = new TabReader($ps_url_or_path, $po_caller, $pa_mappings_by_group, $po_instance, $pa_options);
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Outputs metadata to specified target using specified options
		 *
		 * @param $pm_target string|file resource - a file path or file resource to output the metadata to. If set to null metadata is used as return value (this can be memory intensive for very large metadata sets as the entire data set must be kept in memory)
		 * @param $pa_options array -
		 * @return boolean|string - true on success, false on failure; if $pm_target is null or 'returnOutput' option is set to true then string representation of output metadata will be returned
		 */
		public function output($pm_target, $pa_options=null) {
			return parent::output(array(), $pm_target, $pa_options);
		}
		# -------------------------------------------------------
	}
?>