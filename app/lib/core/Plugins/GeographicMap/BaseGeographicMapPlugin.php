<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/GeographicMap/BaseGeographicMapPlugIn.php : base class for geographic map plugins
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
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugGeographicMap.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_LIB_DIR__."/core/GeographicMap.php");
include_once(__CA_APP_DIR__."/helpers/gisHelpers.php");

abstract class BaseGeographicMapPlugIn Extends WLPlug {
	// properties for this plugin instance
	protected $properties = array(
		
	);
	
	// app config
	protected $opo_config;
	
	// Width and height (in pixels) of map viewport
	protected $opn_map_width = 0;
	protected $opn_map_height = 0;
	
	// Extent of map (boundaries of visible map)
	protected $opn_map_extent_north;
	protected $opn_map_extent_south;
	protected $opn_map_extent_east;
	protected $opn_map_extent_west;
	
	// map item list
	protected $opa_map_items = array();

	// plugin info
	protected $info = array(
		"NAME" => "GoogleMaps",
		"PROPERTIES" => array(
			'id' => 'W'
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
	public function getDimensions() {
		return array(
			0 			=> $this->opn_width,
			1 			=> $this->opn_height,
			'width' 	=> $this->opn_width,
			'height'	=> $this->opn_height,
		);
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function setExtents($pn_north, $pn_south, $pn_east, $pn_west) {
		$this->opn_map_extent_north = $pn_north;
		$this->opn_map_extent_south = $pn_south;
		$this->opn_map_extent_east = $pn_east;
		$this->opn_map_extent_west = $pn_west;
		
		return true;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getExtents() {
		if (
			!isset($this->opn_map_extent_north) || is_null($this->opn_map_extent_north) || 
			!isset($this->opn_map_extent_south) || is_null($this->opn_map_extent_south) || 
			!isset($this->opn_map_extent_east) || is_null($this->opn_map_extent_east) || 
			!isset($this->opn_map_extent_west) || is_null($this->opn_map_extent_west)
		) {
			// if no extents then set them
			$this->fitExtentsToMapItems();
		}
		return array(
			'north' => $this->opn_map_extent_north, 'south' => $this->opn_map_extent_south,
			'east' => $this->opn_map_extent_east, 'west' => $this->opn_map_extent_west
		);
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function addMapItem($po_map_item) {
		if(is_object($po_map_item)) {
			$this->opa_map_items[] = $po_map_item;
			return true;
		}
		
		return false;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function addMapItems($pa_items) {
		if (!is_array($pa_items)) { return false; }
		
		$vn_count = 0;
		foreach($pa_items as $po_map_item) {
			if(is_object($po_map_item)) {
				$this->opa_map_items[] = $po_map_item;
				$vn_count++;
			}
		}
		
		return ($vn_count > 0) ? true : false;
	}
	# ------------------------------------------------
	/**
	 * Get all items for map
	 *
	 * @return array - map items
	 */
	public function getMapItems() {
		return $this->opa_map_items;
	}
	# ------------------------------------------------
	/**
	 * Clears all items from map
	 *
	 * @return boolean - always returns true
	 */
	public function clearMapItems() {
		$this->opa_map_items = array();
		
		return true;
	}
	# ------------------------------------------------
	/**
	 * Change map extents to fit all mapped items. This is essentially the same as "zooming out" the map to include all items.
	 *
	 * @param $pa_options - options to use when fitting map extents to current mapped items. Options are:
	 *		padding - the amount of space to add along each edge of the map after fitting it to the mapped items. This is in decimal degrees! (eg. a valid of 1 is quite large!)
	 *		minWidth - the minimum width of the horizontal extent in decimal degrees
	 *		minHeight - the minimum width of the vertical extent in decimal degrees
	 *
	 * @return array - an array of the new map extents key'ed on direction ('east', 'west', 'north', 'south')
	 */
	public function fitExtentsToMapItems($pa_options=null) {
		$vn_east = $vn_west = $vn_north = $vn_south = null;
		
		$vn_padding = isset($pa_options['padding']) ? (float)$pa_options['padding'] : 0;
		$vn_minimum_width = isset($pa_options['minWidth']) ? (float)$pa_options['minWidth'] : 0;
		$vn_minimum_height = isset($pa_options['minHeight']) ? (float)$pa_options['minHeight'] : 0;
		
		$va_map_items = $this->getMapItems();
		foreach($va_map_items as $o_map_item) {
			$va_coordinate_pairs = $o_map_item->getCoordinates();
			if (sizeof($va_coordinate_pairs) > 0) {
				foreach($va_coordinate_pairs as $va_pair) {
					if (!($va_pair['latitude']) || !($va_pair['longitude'])) { continue; }
					$vn_cur_lat = $va_pair['latitude'] + 90;
					$vn_cur_long = $va_pair['longitude'] + 180;
				
					
					if(($vn_east <= $vn_cur_long) || (is_null($vn_east))) {
						$vn_east = $vn_cur_long;
					}
					if(($vn_west >= $vn_cur_long) || (is_null($vn_west))) {
						$vn_west = $vn_cur_long;
					}
					if(($vn_north <= $vn_cur_lat) || (is_null($vn_north))) {
						$vn_north = $vn_cur_lat;
					}
					if(($vn_south >= $vn_cur_lat) || (is_null($vn_south))) {
						$vn_south = $vn_cur_lat;
					}
					$va_coordinate_pairs[] = $va_pair;
				}
			}
		}
		
		$vn_north -= 90; $vn_south -= 90;
		$vn_east -= 180; $vn_west -= 180;
		
		if ($vn_padding != 0) {
			if ($vn_north > 0) { $vn_north += $vn_padding; } else  {$vn_north -= $vn_padding; }
			if ($vn_south > 0) { $vn_south -= $vn_padding; } else  {$vn_south += $vn_padding; }
			if ($vn_east > 0) { $vn_east -= $vn_padding; } else  {$vn_east += $vn_padding; }
			if ($vn_west > 0) { $vn_west += $vn_padding; } else  {$vn_west -= $vn_padding; }
		}
		
		if ($vn_minimum_width > 0) {
			$vn_west_x = $vn_west + 180; $vn_east_x = $vn_east + 180;
			
			if (($vn_east_x - $vn_west_x) < $vn_minimum_width) {
				$vn_dx = ($vn_minimum_width - ($vn_east_x - $vn_west_x))/2;
				$vn_west_x -= $vn_dx; $vn_east_x += $vn_dx;
				$vn_west_x -= 180; $vn_east_x -= 180;
				
				$vn_west = $vn_west_x; $vn_east = $vn_east_x;
			}
		}
		if ($vn_minimum_height > 0) {
			$vn_north_x = $vn_north + 90; $vn_south_x = $vn_south + 90;
			
			if (($vn_north_x - $vn_south_x) < $vn_minimum_height) {
				$vn_dx = ($vn_minimum_height - ($vn_north_x - $vn_south_x))/2;
				$vn_north_x -= $vn_dx; $vn_south_x += $vn_dx;
				$vn_north_x -= 90; $vn_south_x -= 90;
				
				$vn_north = $vn_north_x; $vn_south = $vn_south_x;
			}
		}
		
		
		$this->setExtents($vn_north, $vn_south, $vn_east, $vn_west);
		return array("east" => $vn_east, "west" => $vn_west, "north" => $vn_north, "south" => $vn_south);
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
		$this->clearMapItems();
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
}
?>