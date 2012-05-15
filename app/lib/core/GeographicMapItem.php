<?php
/** ---------------------------------------------------------------------
 * app/lib/core/GeographicMapItem.php : class for map items (points and paths)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * @subpackage Geographic
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
  
 /**
  *
  */
 
 class GeographicMapItem {
 	# -------------------------------------------------------------------
 	private $opa_coordinate_list = array();
 	private $ops_label = '';							// the overall "title" for the item (used for tooltips)
 	private $ops_content = '';						// content to dispay for item
 	private $ops_ajax_content_url = '';		// the URL to use when loading content via Ajax call
 	private $ops_ajax_content_id = null;		// the id to pin onto the end of the Ajax URL
 	# -------------------------------------------------------------------
 	/**
 	 * @param $pa_item_info - optional array of settings to initialize map item with. Supported settings are:
 	 *		latitude - the latitude in decimal degrees of the item
 	 *		longitude - the longitude in decimal degrees of the item
 	 *		coordinates - an array or coordinate pairs as would be passed to setCoordinates(); if this setting is passed latitude and longitude settings will be ignored.
 	 *		label - the text label to use for the item; this is typically used for the item tooltip
 	 *		content - text content to use in the on-click info window; if you leave this blank no info window will appear
 	 */
 	public function __construct($pa_item_info=null) {
 		if (is_array($pa_item_info)) {
 			if (isset($pa_item_info['coordinates']) && is_array($pa_item_info['coordinates']) && sizeof($pa_item_info['coordinates'])) {
 				$this->setCoordinates($pa_item_info['coordinates']);
 			} else {
				if (isset($pa_item_info['latitude']) && isset($pa_item_info['longitude'])) {
					$this->addCoordinate($pa_item_info['latitude'], $pa_item_info['longitude']);
				}
			}
 			
 			if (isset($pa_item_info['label'])) {
 				$this->setLabel($pa_item_info['label']);
 			}
 			
 			if (isset($pa_item_info['content'])) {
 				$this->setContent($pa_item_info['content'], $pa_item_info['content']);
 			}
 			
 			if (isset($pa_item_info['ajaxContentUrl'])) {
 				$this->setAjaxContentUrl($pa_item_info['ajaxContentUrl']);
 			}
 			
 			if (isset($pa_item_info['ajaxContentID'])) {
 				$this->setAjaxContentID($pa_item_info['ajaxContentID']);
 			}
 		}
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * Sets coordinates for item to this in the provided array, overwriting any existing coordinates. The
 	 * coordinate list is an array of arrays, each of which contains 'latitude' and 'longitude' keys with 
 	 * values in decimal degrees.
 	 *
 	 * @param $pa_coordinate_pairs - array of coordinate pairs; each pair is an array with 'latitude' and 'longitude' keys
 	 * @return boolean - returns true if successful false if not
 	 */
 	public function setCoordinates($pa_coordinate_pairs) {
 		if (is_array($pa_coordinate_pairs)) {
 			$this->opa_coordinate_list = $pa_coordinate_pairs;
 			return true;
 		}
 		return false;
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * Returns the number of coordinate pairs defined for this item
 	 *
 	 * @return int - number of coordinate pairs
 	 */
	public function numCoordinates() {
		return sizeof($this->opa_coordinate_list);
	}
	# -------------------------------------------------------------------
 	/**
 	 * Returns coordinate in list at specified index; coordinates are indexed from zero (eg. index 0 is the first coordinate pair)
 	 *
 	 * @param $pn_i - index of coordinate pair to return
 	 * @return array - array with coordinate data in keys 'latitude' and 'longitude'; or null if index is invalid
 	 */
	public function getCoordinatesAtIndex($pn_i) {
		return isset($this->opa_coordinate_list[$pn_i]) ? $this->opa_coordinate_list[$pn_i] : null;
	}
 	# -------------------------------------------------------------------
 	/**
 	 * Returns current list of coordinates
 	 *
 	 * @return array - list of coordinate pairs currently associated with this item
 	 */
	public function getCoordinates() {
		return $this->opa_coordinate_list;
	}
	# -------------------------------------------------------------------
 	/**
 	 * Add coordinate to map item; latitude and longitude should be in decimal format
 	 */
	public function addCoordinate($pn_latitude, $pn_longitude) {
		$this->opa_coordinate_list[] = array('latitude' => $pn_latitude, 'longitude' => $pn_longitude);
		
		return sizeof($this->opa_coordinate_list);
	}
	# -------------------------------------------------------------------
 	/**
 	 * Clears the item's coordinate list
 	 *
 	 * @return boolean - always returns true
 	 */
	public function clearCoordinates() {
		$this->opa_coordinate_list = array();
		
		return true;
	}
	# -------------------------------------------------------------------
 	/**
 	 * Sets label of item; label is used in cases where short descriptive text is needed; in tooltips for example
 	 *
 	 * @param $ps_text - label text
 	 * @return boolean - always returns true
 	 */
	public function setLabel($ps_text) {
		$this->ops_label = $ps_text;
		return true;
	}
	# -------------------------------------------------------------------
 	/**
 	 * Get currently set label for item
 	 *
 	 * @return string - the current label
 	 */
	public function getLabel() {
		return $this->ops_label;
	}
	# -------------------------------------------------------------------
 	/**
 	 * Sets content for this item. Content is typically used to fill an "info window" attached to the item when it is displayed on a map.
 	 *
 	 * @param $ps_content - content text; can contain HTML code if needed
 	 * @return boolean - always returns true
 	 */
	public function setContent($ps_content) {
		$this->ops_content = $ps_content;
		
		return true;
	}
	# -------------------------------------------------------------------
 	/**
 	 * Gets the currently set content for the item
 	 *
 	 * @return string - the currently set content text
 	 */
	public function getContent() {
		return $this->ops_content;
	}
	# -------------------------------------------------------------------
 	/**
 	 *
 	 *
 	 * @param $ps_url - url to Ajax handler 
 	 * @return boolean - always returns true
 	 */
	public function setAjaxContentUrl($ps_url) {
		$this->ops_ajax_content_url = $ps_url;
		
		return true;
	}
	# -------------------------------------------------------------------
 	/**
 	 * Gets the currently set Ajax content URL for the item
 	 *
 	 * @return string - the currently set Ajax URL
 	 */
	public function getAjaxContentUrl() {
		return $this->ops_ajax_content_url;
	}
	# -------------------------------------------------------------------
 	/**
 	 *
 	 *
 	 * @param $ps_id - unique id for map item; pinned onto end of Ajax URL when fetching info 
 	 * @return boolean - always returns true
 	 */
	public function setAjaxContentID($ps_id) {
		$this->ops_ajax_content_id = $ps_id;
		
		return true;
	}
	# -------------------------------------------------------------------
 	/**
 	 * Gets the currently set Ajax content ID for the item
 	 *
 	 * @return string - the currently set ID
 	 */
	public function getAjaxContentID() {
		return $this->ops_ajax_content_id;
	}
	# -------------------------------------------------------------------
 	/**
	 * Return extents of map item - a bounding box for the item in other words.
	 *
	 * @param $pa_options - options to use when fitting map extents to current mapped items. Options are:
	 *		padding - the amount of space to add along each edge of the map after fitting it to the mapped items. This is in decimal degrees! (eg. a valid of 1 is quite large!)
	 *		minWidth - the minimum width of the horizontal extent in decimal degrees
	 *		minHeight - the minimum width of the vertical extent in decimal degrees
	 *
	 * @return array - an array of the new map extents key'ed on direction ('east', 'west', 'north', 'south')
	 */
	public function getExtents($pa_options=null) {
		$vn_east = $vn_west = $vn_north = $vn_south = null;
		
		$vn_padding = isset($pa_options['padding']) ? (float)$pa_options['padding'] : 0;
		$vn_minimum_width = isset($pa_options['minWidth']) ? (float)$pa_options['minWidth'] : 0;
		$vn_minimum_height = isset($pa_options['minHeight']) ? (float)$pa_options['minHeight'] : 0;
		
		$va_coordinate_pairs = $this->getCoordinates();
		if (sizeof($va_coordinate_pairs) > 0) {
			foreach($va_coordinate_pairs as $va_pair) {
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

		return array("east" => $vn_east, "west" => $vn_west, "north" => $vn_north, "south" => $vn_south);
	}
 	# -------------------------------------------------------------------
 }
 ?>