<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/PDFRenderer/BasePDFRendererPlugin.php : base class for PDFRenderer plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2025 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESSs FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Print
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */   
include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugPDFRenderer.php");
include_once(__CA_LIB_DIR__."/Configuration.php");
include_once(__CA_LIB_DIR__."/Print/PDFRenderer.php");

abstract class BasePDFRendererPlugin Extends WLPlug {
	# ------------------------------------------------
	/**
	 * properties for this plugin instance
	 */
	protected $properties = array(
		
	);
	
	/**
	 * app config
	 */
	protected $opo_config;
	
	/**
	 * plugin info
	 */
	protected $info = array(
		"NAME" => "?",
		"PROPERTIES" => array(
			"CODE" => "W",
		)
	);
	
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->opo_config = Configuration::load();
	}
	# ------------------------------------------------
	/**
	 * Initialize plugin and create new instance
	 */
	public function register() {
		$this->opo_config = Configuration::load();
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	/**
	 * Returns status of plugin. Normally this is overriden by the plugin subclass
	 *
	 * @return array - status info array; 'available' key determines if the plugin should be loaded or not
	 */
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		}
		
		return $va_status;
	}
	# ----------------------------------------------------------
	/**
	 * Get map property
	 *
	 * @param $property - name of property - must be defined as a property in the plugin's 'PROPERTIES' array
	 * @return string - the value of the property or null if the property is not valid
	 */
	public function get($property) {
		if ($this->info["PROPERTIES"][$property]) {
			return $this->properties[$property];
		} else {
			//print "Invalid property";
			return null;
		}
	}
	# ----------------------------------------------------------
	/**
	 * Set plugin property
	 *
	 * @param string $property
	 * @param mixed $value
	 *
	 * @return bool True on success, false on failure
	 */
	public function set($property, $value) {
		if ($this->info["PROPERTIES"][$property]) {
			switch($property) {
				default:
					if ($this->info["PROPERTIES"][$property] == 'W') {
						$this->properties[$property] = $value;
					} else {
						# read only
						return '';
					}
					break;
			}
		} else {
			# invalid property
			$this->postError(1650, _t("Can't set property %1", $property), "BasePDFRendererPlugin->set()");
			return false;
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Initialize plugin values (stub)
	 *
	 * @return bool True on success, false on failure
	 */
	public function init() {
		return true;
	}
	# ------------------------------------------------
	/**
	 * Clean up on deallocation (stub)
	 *
	 * @return bool True on success, false on failure
	 */
	public function cleanup() {
		return true;
	}
	# ------------------------------------------------
}
