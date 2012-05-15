<?php
/** ---------------------------------------------------------------------
 * BaseDataMoverFormat.php : base class for data import/export format processors
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
	
	class BaseDataMoverFormat {
		# -------------------------------------------------------
		protected $opn_current_index;
		protected $opa_records;
		# -------------------------------------------------------
		/**
		 *
		 */
		public function __construct() {
			$this->opn_current_index = 0;
			$this->opa_records = array();
		}
		# -------------------------------------------------------
		# Format info
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getFormatInfo() {
			global $g_ca_data_import_export_format_definitions;
			
			return $g_ca_data_import_export_format_definitions[$this->ops_name];
		}
		# -------------------------------------------------------
		
		/**
		 * Returns list of metadata elements that can be set or gotten
		 */
		public function getElementNames() {
			global $g_ca_data_import_export_format_definitions;
			
			return array_keys($g_ca_data_import_export_format_definitions[$this->ops_name]['element_list']);
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getElementList() {
			global $g_ca_data_import_export_format_definitions;
			
			return $g_ca_data_import_export_format_definitions[$this->ops_name]['element_list'];
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getElementInfo($ps_element_name) {
			global $g_ca_data_import_export_format_definitions;
			
			return isset($g_ca_data_import_export_format_definitions[$this->ops_name]['element_list'][$ps_element_name]) ?
				$g_ca_data_import_export_format_definitions[$this->ops_name]['element_list'][$ps_element_name]
				:
				null;
		}
		# -------------------------------------------------------
		#
		# -------------------------------------------------------
		/**
		 *
		 */
		public function add($pn_id, $pa_values) {
			if (!is_array($pa_values) || !sizeof($pa_values)) { return false; }
			$this->opa_records[$pn_id] = $pa_values;
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function removeAll() {
			$this->opa_records = array();
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function get($ps_element, $pa_options=null) {
			if (!isset($this->opa_records[$this->opn_current_index][$ps_element])) { return null; }
		 	$vs_val = $this->opa_records[$this->opn_current_index][$ps_element];
		 	
		 	// TODO: implement options
		 	
		 	return $vs_val;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function set($ps_element, $ps_value, $pa_options=null) {
		 
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function next() {
			$this->opn_current_index++;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function previous() {
			$this->opn_current_index--;
			if ($this->opn_current_index < 0) { $this->opn_current_index = 0; }
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function seek($pn_index) {
		 	if ($pn_index < 0) { $pn_index = 0; }
		 	$this->opn_current_index = $pn_index;
		 	
		 	return true;
		}
		# -------------------------------------------------------
		/**
		 * Return file extension used for output of this format. Must be overridden
		 * by sub-class.
		 *
		 * @return string File extension. Null is returned by the base implementation.
		 */
		public function getFileExtension() {
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return mimetype used for output of this format. Must be overridden
		 * by sub-class.
		 *
		 * @return string Mimetype. Null is returned by the base implementation.
		 */
		public function getMimetype() {
			return null;
		}
		# ---
		# -------------------------------------------------------
	}
?>