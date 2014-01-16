<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Visualizer/BaseVisualizerPlugin.php : base class for visualization plugins
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
 * MERCHANTABILITY or FITNESSs FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Geographic
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugVisualizer.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_LIB_DIR__."/ca/Visualizer.php");

abstract class BaseVisualizerPlugin Extends WLPlug {
	# ------------------------------------------------
	// properties for this plugin instance
	protected $properties = array(
		
	);
	
	// app config
	protected $opo_config;
	
	// Width and height (in pixels) of map viewport
	protected $opn_width = 0;
	protected $opn_height = 0;
	
	// SearchResult to pull data from
	protected $opo_data = array();

	// plugin info
	protected $info = array(
		"NAME" => "?",
		"PROPERTIES" => array(
			'id' => 'W'
		)
	);
	
	/**
	 * Render count
	 */
	protected $opn_num_items_rendered = 0;
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->opo_config = Configuration::load();
	}
	# ------------------------------------------------
	/**
	 *
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
	 *
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
			$this->postError(1650, _t("Can't set property %1", $property), "WLPlugGeographicMapGoogleMaps->set()");
			return '';
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Sets current dimensions of map (in pixels). Width and height must be greater than zero or they will be ignored
	 *
	 * @param $pn_width - integer representing pixel width of map
	 * @param $pn_height - integer representing pixel height of map
	 * @return boolean - true if dimensions were set, false if not
	 */
	public function setDimensions($pn_width, $pn_height) {
		if (($pn_width > 0) && ($pn_height > 0)) { 
			$this->opn_width = $pn_width; 
			$this->opn_height = $pn_height; 
			
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------
	/**
	 * Returns currently set dimensions (in pixels) of map
	 *
	 * @return - array with width and height in keys 0 and 1 respectively as well as 'width' and 'height' respectively
	 */
	public function getDimensions($pa_options=null) {
		return $this->_parseDimensions($this->opn_width, $this->opn_height, $pa_options);
	}
	# ------------------------------------------------
	/**
	 * Returns currently set dimensions (in pixels) of map
	 *
	 * @return - array with width and height in keys 0 and 1 respectively as well as 'width' and 'height' respectively
	 */
	protected function _parseDimensions($ps_width, $ps_height, $pa_options=null) {
		$vs_width = $ps_width;
		$vs_height = $ps_height;
		
		$vn_width = intval($vs_width);
		$vn_height = intval($vs_height);
		
		if ($vn_width < 1) { $vn_width = 200; }
		if ($vn_height < 1) { $vn_height = 200; }
		if (!preg_match('!^[\d]+%$!', $vs_width)) {
			$vn_width = intval($vn_width);
			if ($vn_width < 1) { $vn_width = 690; }
			$vs_width = "{$vn_width}px";
		}
		if (!preg_match('!^[\d]+%$!', $vs_height)) {
			$vn_height = intval($vn_height);
			if ($vn_height < 1) { $vn_height = 300; }
			$vs_height = "{$vn_height}px";
		}
		
		
		if (caGetOption('returnPixelValues', $pa_options, false)) {
			return array(
				0 			=> $vn_width,
				1 			=> $vn_height,
				'width' 	=> $vn_width,
				'height'	=> $vn_height,
			);

		} else {
			return array(
				0 			=> $vs_width,
				1 			=> $vs_height,
				'width' 	=> $vs_width,
				'height'	=> $vs_height,
			);
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function setData($po_search_result) {
		if(is_subclass_of($po_search_result, "SearchResult")) {
			$this->opo_data = $po_search_result;
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getData() {
		return $this->opo_data;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function clearData() {
		$this->opo_data = null;
		return true;
	}
	# ------------------------------------------------
	/**
	 * Render the map and return output. This *must* be overriden 
	 */
	abstract public function render($ps_format);
	# ------------------------------------------------
	/**
	 *
	 */
	public function init() {
		$this->clearData();
		return;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function numItemsRendered() {
		return $this->opn_num_items_rendered;
	}	
	# --------------------------------------------------------------------------------
	/**
	 * Register any required javascript and CSS for loading
	 *
	 * @return void 
	 */
	public function registerDependencies() {
		return;
	}
	# ------------------------------------------------
}
?>