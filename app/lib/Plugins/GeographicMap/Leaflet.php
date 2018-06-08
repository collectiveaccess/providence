<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/GeographicMap/WLPlugGeographicMapLeaflet.php : generates maps via Leaflet JS library
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
    
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugGeographicMap.php");
include_once(__CA_LIB_DIR__."/Plugins/GeographicMap/BaseGeographicMapPlugin.php");

class WLPlugGeographicMapLeaflet Extends BaseGeographicMapPlugIn Implements IWLPlugGeographicMap {
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'Leaflet';
		
		$this->description = _t('Generates maps using the Leaflet JS Library');
		
		AssetLoadManager::register("leaflet");
	}
	# ------------------------------------------------
	/**
	 * Generate GoogleMaps output in specified format
	 *
	 * @param $ps_format - specifies format to generate output in. Currently only 'HTML' is supported.
	 * @param $pa_options - array of options to use when rendering output. Supported options are:
	 *		mapType - type of map to render; valid values are 'ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN'; if not specified 'google_maps_default_type' setting in app.conf is used; if that is not set default is 'SATELLITE'
	 *		showNavigationControls - if true, navigation controls are displayed; default is to use 'google_maps_show_navigation_controls' setting in app.conf
	 *		showScaleControls -  if true, scale controls are displayed; default is to use 'google_maps_show_scale_controls' setting in app.conf
	 *		showMapTypeControls -  if true, map type controls are displayed; default is to use 'google_maps_show_map_type_controls' setting in app.conf
	 *		cycleRandomly - if true, map cycles randomly through markers; default is false
	 *		cycleRandomlyInterval - Interval between movement between markers; specify in milliseconds or seconds followed by 's' (eg. 4s); default is 2s
	 *		stopAfterRandomCycles - Stop cycling after a number of movements; set to zero to cycle forever; default is zero.
	 *		delimiter - Delimiter to use to separate content for different items being plotted in the same location (and therefore being put in the same marker detail balloon); default is an HTML line break tag ("<br/>")
	 *		minZoomLevel - Minimum zoom level to allow; leave null if you don't want to enforce a limit
	 *		maxZoomLevel - Maximum zoom level to allow; leave null if you don't want to enforce a limit
	 *		zoomLevel - Zoom map to specified level rather than fitting all markers into view; leave null if you don't want to specify a zoom level. IF this option is set minZoomLevel and maxZoomLevel will be ignored.
	 *		balloonView -
	 *		pathColor - used for paths and circles
	 *		pathWeight - used for paths and circles
	 *		pathOpacity - used for paths and circles
	 *		obscure - do not map exact point, instead show broad area around point, also do not show label since this would tell the specific location
	 *		circle - render circle instead of point
	 *		radius - circle radius
	 *		fillColor - circle fill
	 *		fillOpacity - circle fill opacity
	 */
	public function render($ps_format, $pa_options=null) {
		$o_config = Configuration::load();
		
		list($vs_width, $vs_height) = $this->getDimensions();
		list($vn_width, $vn_height) = $this->getDimensions(array('returnPixelValues' => true));
		
		
		$va_map_items = $this->getMapItems();
		$va_extents = $this->getExtents();
		
		$vs_delimiter = isset($pa_options['delimiter']) ? $pa_options['delimiter'] : "<br/>";
		$vn_zoom_level = (isset($pa_options['zoomLevel']) && ((int)$pa_options['zoomLevel'] > 0)) ? (int)$pa_options['zoomLevel'] : null;
		$vn_min_zoom_level = (isset($pa_options['minZoomLevel']) && ((int)$pa_options['minZoomLevel'] > 0)) ? (int)$pa_options['minZoomLevel'] : null;
		$vn_max_zoom_level = (isset($pa_options['maxZoomLevel']) && ((int)$pa_options['maxZoomLevel'] > 0)) ? (int)$pa_options['maxZoomLevel'] : null;
		
		$vs_path_color = (isset($pa_options['pathColor'])) ? $pa_options['pathColor'] : $this->opo_config->get('google_maps_path_color');
		$vn_path_weight = (isset($pa_options['pathWeight']) && ((int)$pa_options['pathWeight'] > 0)) ? (int)$pa_options['pathWeight'] : 2;
		$vn_path_opacity = (isset($pa_options['pathOpacity']) && ((int)$pa_options['pathOpacity'] >= 0)  && ((int)$pa_options['pathOpacity'] <= 1)) ? (int)$pa_options['pathOpacity'] : 0.5;
		
		$vs_balloon_view = (isset($pa_options['balloonView'])) ? $pa_options['balloonView'] : null;
		$vb_obscure = (isset($pa_options['obscure'])) ? $pa_options['obscure'] : false;
		$vb_circle = (isset($pa_options['circle'])) ? $pa_options['circle'] : false;
		$vn_radius = (isset($pa_options['radius'])) ? $pa_options['radius'] : 500;
		$vs_fill_color = (isset($pa_options['fillColor'])) ? $pa_options['fillColor'] : '#cc0000';
		$vn_fill_opacity = (isset($pa_options['fillOpacity']) && ((int)$pa_options['fillOpacity'] >= 0)  && ((int)$pa_options['fillOpacity'] <= 1)) ? (int)$pa_options['fillOpacity'] : 0.3;
		
		$vs_type = (isset($pa_options['mapType'])) ? strtoupper($pa_options['mapType']) : strtoupper($this->opo_config->get('google_maps_default_type'));
		if (!in_array($vs_type, array('ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN'))) {
			$vs_type = 'SATELLITE';
		}
		$vs_type = strtolower($vs_type);
		if (!$vs_id = trim($this->get('id'))) { $vs_id = 'map'; }
		
		switch(strtoupper($ps_format)) {
			# ---------------------------------
			case 'JPEG':
			case 'PNG':
			case 'GIF':
				$vn_width = intval($vn_width);
				$vn_height = intval($vn_height);
				if ($vn_width < 1) { $vn_width = 200; }
				if ($vn_height < 1) { $vn_height = 200; }
				
				break;
			# ---------------------------------
			case 'HTML':
			default:
				if(!strpos($vn_width, "%")){
					$vn_width = intval($vn_width);
					if ($vn_width < 1) { $vn_width = 200; }
					$vn_width = $vn_width."px";
				}
				if(!strpos($vn_height, "%")){
					$vn_height = intval($vn_height);
					if ($vn_height < 1) { $vn_height = 200; }
					$vn_height = $vn_height."px";
				}
				
				if (isset($pa_options['showNavigationControls'])) {
					$vb_show_navigation_control 	= $pa_options['showNavigationControls'] ? 'true' : 'false';
				} else {
					$vb_show_navigation_control 	= $this->opo_config->get('google_maps_show_navigation_controls') ? 'true' : 'false';
				}
				if (isset($pa_options['showScaleControls'])) {
					$vb_show_scale_control 				= $pa_options['showScaleControls'] ? 'true' : 'false';
				} else {
					$vb_show_scale_control 			= $this->opo_config->get('google_maps_show_scale_controls') ? 'true' : 'false';
				}
				if (isset($pa_options['showMapTypeControls'])) {
					$vb_show_map_type_control 		= $pa_options['showMapTypeControls'] ? 'true' : 'false';
				} else {
					$vb_show_map_type_control 		= $this->opo_config->get('google_maps_show_map_type_controls') ? 'true' : 'false';
				}
				
				$vs_buf = "<div style='width:{$vs_width}; height:{$vs_height}' id='{$vs_id}'> </div>\n
<script type='text/javascript'>";
	
$vs_buf .= "
	});
</script>\n"; 
				break;
			# ---------------------------------
		}
		
		return $vs_buf;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getAttributeBundleHTML($pa_element_info, $pa_options=null) {
 		AssetLoadManager::register('leaflet');
		$o_config = Configuration::load();
		
		$va_element_width = caParseFormElementDimension($pa_element_info['settings']['fieldWidth']);
		$vn_element_width = $va_element_width['dimension'];
		$va_element_height = caParseFormElementDimension($pa_element_info['settings']['fieldHeight']);
		$vn_element_height = $va_element_height['dimension'];
 		
 		$element_id = (int)$pa_element_info['element_id'];
 		$vs_id = $pa_element_info['element_id']."_{n}";
 		
 		if (!($base_map_url = $o_config->get('leaflet_base_layer'))) { 
 			$base_map_url = 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png';
 		}
		
		$vs_element = 	"
<div id='mapholder_{$element_id}_{n}' class='mapholder'>
	 <div class='map' style='width:695px; height:400px;'></div>
</div>
<script type='text/javascript'>
	jQuery(document).ready(function() {
		var map_{$vs_id}_loc_str = '{{{$element_id}}}';
		
		var re = /\[([\d\,\-\.\:\;~]+)\]/;

		var map_{$vs_id}_loc_label = jQuery.trim(map_{$vs_id}_loc_str.match(/^[^\[]+/));
		
		var map = L.map('mapholder_{$element_id}_{n}', { minZoom: 2, maxZoom: 5 }).setView([0, 0], 8);
		var b = L.tileLayer('{$base_map_url}').addTo(map);	
		map.addControl(new L.Control.OSMGeocoder({ text: '"._t('Go')."', 'collapsed': false }));
		
		var g = new L.featureGroup();
		
		var map_{$vs_id}_update_coord_list = function (e) {
			var label = jQuery.trim(map_{$vs_id}_loc_str.match(/^[^\[]+/));
			var objs = [];
			g.eachLayer(function (layer) {
				if (layer.getRadius) { // circle
					var c = layer.getLatLng();
					objs.push(c.lat + ',' + c.lng + '~' + layer.getRadius());
				} else if (layer.getLatLngs) { // path
					var cs = layer.getLatLngs()[0].map(c => { return c.lat + ',' + c.lng});
					objs.push(cs.join(';'));
				} else if (layer.getLatLng) { // marker
					var c = layer.getLatLng();
					objs.push(c.lat + ',' + c.lng);
				}
			});
			var coords = objs.join(':');
			jQuery('#{fieldNamePrefix}{$element_id}_{n}').val((label ? label : '') + (coords ? (label ? ' ' : '') + '[' + coords + ']' : ''));
		};
		
		var drawControl = new L.Control.Draw({
			edit: { featureGroup: g, edit: true },
			draw: { circlemarker: false }
		});
		map.addControl(drawControl);
		
		map.on('draw:created', function (e) {	
			e.layer.addTo(g);
			map_{$vs_id}_update_coord_list(e);
		});
		map.on('draw:edited', map_{$vs_id}_update_coord_list);
		map.on('draw:deletestop', map_{$vs_id}_update_coord_list);
		
		map.on('moveend', function(e) {
			var c = map.getCenter();
			localStorage.setItem('leafletLastPos', c.lat + ',' + c.lng);
		});
		
		var f = re.exec(map_{$vs_id}_loc_str);
		if (f && f[1]) {
			var featureList = f[1].split(/:/);

			jQuery(featureList).each(function(k, v) {
				var r = v.split(/~/);
				var ptlist = r[0].split(/;/);
				var radius = r[1] ? r[1] : 0;
				
				if (radius > 0) {
					var pt = ptlist[0].split(/,/);
					L.circle([parseFloat(pt[0]), parseFloat(pt[1])], {radius: radius}).addTo(g);
				} else if (ptlist.length > 1) {	// path
					var splitPts = ptlist.map(c => { return c.split(/,/).map(x => { return parseFloat(x)}) });
					L.polygon(splitPts).addTo(g);
				} else { // point
					var pt = ptlist[0].split(/,/);
					var m = L.marker([parseFloat(pt[0]), parseFloat(pt[1])], {  }).addTo(g);
					if (map_{$vs_id}_loc_label) { m.bindPopup(map_{$vs_id}_loc_label); }
				}
			});
		} else {
			var c = localStorage.getItem('leafletLastPos');
			if (c) {
				var coord = c.split(/,/);
				map.setView(coord, 6, {animate: false});
			} else {
				map.setZoom(2, {animate: false});
			}
		}
		
		g.addTo(map);
		var bounds = g.getBounds();
		if (bounds.isValid()) { map.fitBounds(bounds); }
	});
</script>
	<input class='coordinates mapCoordinateDisplay' type='text' name='{fieldNamePrefix}{$element_id}_{n}' id='{fieldNamePrefix}{$element_id}_{n}' size='80' value='{{$element_id}}'/>
";
		return $vs_element;
	}
	# ------------------------------------------------
}
