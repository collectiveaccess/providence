<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/IDNumbering/IDNumber.php : base class for id number processing plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2012 Whirl-i-Gig
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
 
  /**
  *
  */
  
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_LIB_DIR__."/ca/IDNumbering/IIDNumbering.php");
	
	abstract class IDNumber implements IIDNumbering {
		# -------------------------------------------------------
		protected $opo_config;
		protected $opo_datamodel;
		
		protected $ops_format;
		protected $ops_type = '__default__';
		protected $ops_value;
		
		protected $opb_is_child = false;
		
		# -------------------------------------------------------
		public function __construct() {
			$this->opo_datamodel = Datamodel::load();
			$this->opo_config = Configuration::load();
		}
		# -------------------------------------------------------
		# Formats
		# -------------------------------------------------------
		public function setFormat($ps_format) {
			if ($this->isValidFormat($ps_format)) {
				$this->ops_format = $ps_format;
				return true;
			}
			return false;
		}
		# -------------------------------------------------------
		public function getFormat() {
			return $this->ops_format;
		}
		# -------------------------------------------------------
		# Child number generation
		# -------------------------------------------------------
		public function isChild($pb_is_child=null) {
			if (!is_null($pb_is_child)) {
				$this->opb_is_child = (bool)$pb_is_child;
			}
			return $this->opb_is_child;
		}
		# -------------------------------------------------------
		# Types
		# -------------------------------------------------------
		public function setType($pm_type) {
			if (!is_array($pm_type)) { $pm_type = array($pm_type); }
			
			foreach($pm_type as $ps_type) {
				if (!$ps_type) { continue; }
				if ($this->isValidType($ps_type)) {
					$this->ops_type = $ps_type;
					return true;
				}
			}
			$this->ops_type = '__default__';
			return false;
		}
		# -------------------------------------------------------
		public function getType() {
			return $this->ops_type;
		}
		# -------------------------------------------------------
		# Values
		# -------------------------------------------------------
		public function setValue($ps_value) {
			$this->ops_value = $ps_value;
		}
		# -------------------------------------------------------
		public function getValue() {
			return $this->ops_value;
		}
		# -------------------------------------------------------
	}
?>