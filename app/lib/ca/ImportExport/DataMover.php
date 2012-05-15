<?php
/** ---------------------------------------------------------------------
 * DataMover.php : base class for data import and export managers
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
	require_once(__CA_MODELS_DIR__.'/ca_bundle_mappings.php');
	require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
	
	class DataMover extends BaseObject {
		# -------------------------------------------------------
		protected $opo_bundle_mapping;
		# -------------------------------------------------------
		/**
		 *
		 */
		public function __construct() {
			parent::__construct();
			$this->opo_bundle_mapping = new ca_bundle_mappings();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function getAvailableFormats() {
			die("Must override getAvailableFormats()");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function formatIsAvailable($ps_format) {
			die("Must override formatIsAvailable()");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function getAvailableMappings($ps_direction) {
			return $this->o_bundle_mapping->getAvailableMappings($ps_direction);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function mappingIsAvailable($pm_mapping_code_or_id) {
			return $this->o_bundle_mapping->mappingIsAvailable($pm_mapping_code_or_id);
		}
		# -------------------------------------------------------
	}
?>