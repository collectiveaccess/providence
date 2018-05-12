<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/IDNumbering/IDNumber.php : base class for id number processing plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2015 Whirl-i-Gig
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
		/**
		 * Instance of application configuration
		 * @type Configuration
		 */
		protected $opo_config;
		
		/**
		 * Instance of application data model
		 * @type Datamodel
		 */
		protected $opo_datamodel;
		
		/**
		 * The current format
		 * @type string
		 */
		protected $ops_format;
		
		/**
		 * The current type
		 * @type string
		 */
		protected $ops_type = '__default__';
		
		/**
		 * The current value
		 * @type string
		 */
		protected $ops_value = null;
		
		/**
		 * Flag indicating whether record has a parent
		 * @type bool
		 */
		protected $opb_is_child = false;
		
		/**
		 * Identifier value for parent, if present
		 * @type string
		 */
		protected $ops_parent_value = null;
		
		# -------------------------------------------------------
		/**
		 * Initialize and load configuration files
		 */
		public function __construct() {
			$this->opo_datamodel = Datamodel::load();
			$this->opo_config = Configuration::load();
		}
		# -------------------------------------------------------
		# Formats
		# -------------------------------------------------------
		/**
		 * Set the current format
		 *
		 * @param string $ps_format A valid format
		 * @return bool True on success, false if format was invalid
		 */
		public function setFormat($ps_format) {
			if ($this->isValidFormat($ps_format)) {
				$this->ops_format = $ps_format;
				return true;
			}
			return false;
		}
		# -------------------------------------------------------
		/**
		 * Get the current format
		 *
		 * @return string
		 */
		public function getFormat() {
			return $this->ops_format;
		}
		# -------------------------------------------------------
		# Child number generation
		# -------------------------------------------------------
		/**
		 * Get or set is_child flag indicating if the current record value is for a record with a parent
		 *
		 * @param bool $pb_is_child Set the is_child flag.  [Default is null]
		 * @param string $ps_parent_value Optional parent identifier value, used to populate PARENT elements in multipart id numbers (and perhaps in other plugins as well) [Default is null]
		 * @return bool Current state is is_child flag
		 */
		public function isChild($pb_is_child=null, $ps_parent_value=null) {
			if (!is_null($pb_is_child)) {
				
				$this->opb_is_child = (bool)$pb_is_child;
				$this->ops_parent_value = $pb_is_child ? $ps_parent_value : null;
			}
			return $this->opb_is_child;
		}
		# -------------------------------------------------------
		/**
		 * Get the current parent value
		 *
		 * @return string
		 */
		public function getParentValue() {
			return $this->ops_parent_value;
		}
		# -------------------------------------------------------
		# Types
		# -------------------------------------------------------
		/**
		 * Set the current type
		 *
		 * @param mixed A type (string) or array of types to set as current type. If an array is passed then each type is attempted in turn until a valid type is found. If no valid types are found the type will be set to '__default__'
		 * @return bool True if a valid type is found and set, false if no valid type is found.
		 */
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
		/**
		 * Get the current type
		 *
		 * @return string 
		 */
		public function getType() {
			return $this->ops_type;
		}
		# -------------------------------------------------------
		# Values
		# -------------------------------------------------------
		/**
		 * Set the current value
		 *
		 * @param string $ps_value The value of the current identifier
		 * @return void 
		 */
		public function setValue($ps_value) {
			$this->ops_value = $ps_value;
		}
		# -------------------------------------------------------
		/**
		 * Get the current value
		 *
		 * @param array $pa_options No options are defined.
		 * @return string
		 */
		public function getValue($pa_options=null) {
			return $this->ops_value;
		}
		# -------------------------------------------------------
	}