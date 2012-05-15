<?php
/** ---------------------------------------------------------------------
 * TabReader.php : delimited reader for tab-delimited data format
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

	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Delimited/BaseDelimitedReader.php');
	require_once(__CA_LIB_DIR__.'/core/Parsers/DelimitedDataParser.php');
	
	
	class TabReader extends BaseDelimitedReader {
		# -------------------------------------------------------
		protected $ops_name = 'Tab';
		protected $ops_delimiter = "\t";
		# -------------------------------------------------------
		const METADATA_SCHEMA = '';
		const METADATA_PREFIX = 'tab';
		
		# -------------------------------------------------------
		/**
		 * @param $ps_url_or_path string URL or path to PBCore file
		 * @param $po_processor object Instance of class implementing 
		 * @param $pa_mappings_by_group
		 * @param $po_instance
		 * @param $pa_options array Read options
		 */
		public function __construct($ps_url_or_path, $po_processor, $pa_mappings_by_group, $po_instance, $pa_options=null) {
			parent::__construct($ps_url_or_path, $po_processor, $pa_mappings_by_group, $po_instance, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * @param string $ps_file_path
		 */
		protected function parseRecords($ps_file_path){
			$o_parser = new DelimitedDataParser($this->ops_delimiter);
			$o_parser->parse($ps_file_path);
			return $o_parser;
		}
		# -------------------------------------------------------
	}
?>