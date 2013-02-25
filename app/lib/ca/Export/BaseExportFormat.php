<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Export/BaseExportFormat.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__.'/ca/Export/ExportFormats/ExportXML.php');
 	require_once(__CA_LIB_DIR__.'/ca/Export/ExportFormats/ExportMARC.php');
 
	abstract class BaseExportFormat {
		# -------------------------------------------------------
		static $s_format_settings = array();
		# -------------------------------------------------------
		protected $ops_name = null;
		/**
		 * The 'element' field in ca_data_exporter_items can have varying syntax and semantics, depending on the
		 * exporter format used (e.g. for XML, @foo addresses the attribute 'foo' of the current element.
		 * This string is used to describe that format in detail. 
		 * @var string
		 */
		protected $ops_element_description;
		# -------------------------------------------------------
		public function __construct(){

		}
		# -------------------------------------------------------
		public function getFormatSettings() {
			return BaseExportFormat::$s_format_settings[$this->getName()]; 
		}
		# -------------------------------------------------------
		public function getName() {
			return $this->ops_name; 
		}
		# -------------------------------------------------------
		public function getDescription() {
			return $this->ops_element_description;
		}
		# -------------------------------------------------------
		abstract public function addItem($ps_element,$ps_content);
		# -------------------------------------------------------
	}