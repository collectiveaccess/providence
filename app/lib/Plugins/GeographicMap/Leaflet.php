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
	 * Generate map output in specified format using Leaflet
	 *
	 * @param $ps_format - specifies format to generate output in. Currently only 'HTML' is supported.
	 * @param $pa_options - array of options to use when rendering output. Supported options are:
	 *		showScaleControls = if true, scale controls are displayed; default is to use 'leaflet_maps_show_scale_controls' setting in app.conf
	 *		delimiter = Delimiter to use to separate content for different items being plotted in the same location (and therefore being put in the same marker detail balloon); default is an HTML line break tag ("<br/>")
	 *		minZoomLevel = Minimum zoom level to allow; leave null if you don't want to enforce a limit
	 *		maxZoomLevel = Maximum zoom level to allow; leave null if you don't want to enforce a limit
	 *		noWrap = Prevent wrapping of map tile background when pan; leave null to allow wrapping
	 *		zoomLevel = Zoom map to specified level rather than fitting all markers into view; leave null if you don't want to specify a zoom level. IF this option is set minZoomLevel and maxZoomLevel will be ignored.
	 *		pathColor = used for paths and circles; default is to use 'leaflet_maps_path_color' setting in app.conf
	 *		pathWeight = used for paths and circles; default is to use 'leaflet_maps_path_weight' setting in app.conf
	 *		pathOpacity = used for paths and circles; default is to use 'leaflet_maps_path_opacity' setting in app.conf
	 *		fillColor = fill color for circles and polygons; default is to use 'leaflet_maps_fill_color' setting in app.conf
	 *		fillOpacity = fill opacioty for circles and polygons; default is to use 'leaflet_maps_fill_opacity' setting in app.conf
	 *
	 * @return string
	 */
	public function render($ps_format, $pa_options=null) {
 		AssetLoadManager::register('leaflet');
		
		list($vs_width, $vs_height) = $this->getDimensions();
		list($vn_width, $vn_height) = $this->getDimensions(array('returnPixelValues' => true));
		
		
 		$base_path = null;
 		if ($request = caGetOption(['request'], $pa_options, null)) {
 			$base_path = $request->getBaseUrlPath();
 		}
 		
 		if (!($base_map_url = $this->opo_config->get('leaflet_base_layer'))) { 
 			$base_map_url = 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png';
 		}
			
		$va_map_items = $this->getMapItems();
		$va_extents = $this->getExtents();
		
		$vb_show_scale_controls = (bool)caGetOption('showScaleControls', $pa_options, (bool)$this->opo_config->get('leaflet_maps_show_scale_controls'));
		$vs_delimiter = caGetOption('delimiter', $pa_options, '<br/>');
		$vn_zoom_level = caGetOption('zoomLevel', $pa_options, null);
		$we_set_zoom = false;
		if (!$vn_zoom_level) { $vn_zoom_level = 8; $we_set_zoom = true; }
		
		$vn_min_zoom_level = caGetOption('minZoomLevel', $pa_options, 0);
		$vn_max_zoom_level = caGetOption('maxZoomLevel', $pa_options, 18);
		$vb_no_wrap = (bool)caGetOption('noWrap', $pa_options, null);
		
		if (!($vs_path_color = caGetOption('pathColor', $pa_options, $this->opo_config->get('leaflet_maps_path_color')))) { $vs_path_color = '#ff0000'; }
		if (($vn_path_weight = caGetOption('pathWeight', $pa_options, $this->opo_config->get('leaflet_maps_path_weight'))) < 1) { $vn_path_weight = 1; }
		if (($vn_path_opacity = caGetOption('pathOpacity', $pa_options, $this->opo_config->get('leaflet_maps_path_opacity'))) < 0) { $vn_path_opacity = 1; }
		
		if (!($vs_fill_color = caGetOption('fillColor', $pa_options, $this->opo_config->get('leaflet_maps_fill_color')))) { $vs_fill_color = '#ff0000'; }
		if (($vn_fill_opacity = caGetOption('fillOpacity', $pa_options, $this->opo_config->get('leaflet_maps_fill_opacity'))) < 0) { $vn_fill_opacity = 1; }
		
		if (!$vs_id = trim($this->get('id'))) { $vs_id = 'map'; }
		
		
		$points = $paths = $circles = [];
		foreach($va_map_items as $o_map_item) {
			$va_coords = $o_map_item->getCoordinates();
			if (sizeof($va_coords) > 1) {
				// path
				$paths[] = ['path' => $va_coords, 'label' => $o_map_item->getLabel(), 'content' => $o_map_item->getContent(), 'ajaxContentUrl' => $o_map_item->getAjaxContentUrl(), 'ajaxContentID' => $o_map_item->getAjaxContentID()];
			} elseif($va_coords[0]['radius'] > 0) { // circle
				$va_coord = array_shift($va_coords);
				$r = (float)$va_coord['radius'];
				
				$circles[$va_coord['latitude']][$va_coord['longitude']][] =['radius' => $r, 'label' => $o_map_item->getLabel(), 'content' => $o_map_item->getContent(), 'ajaxContentUrl' => $o_map_item->getAjaxContentUrl(), 'ajaxContentID' => $o_map_item->getAjaxContentID()];
			} else {
				// point
				$va_coord = array_shift($va_coords);
				$angle = isset($va_coord['angle']) ? (float)$va_coord['angle'] : null;
				$points[$va_coord['latitude']][$va_coord['longitude']][] = ['label' => $o_map_item->getLabel(), 'content' => $o_map_item->getContent(), 'ajaxContentUrl' => $o_map_item->getAjaxContentUrl(), 'ajaxContentID' => $o_map_item->getAjaxContentID(), 'angle' => $angle];
			}
		}
		
		$vn_c = 0;
		
		$pointList = [];
		foreach($points as $lat => $va_locs_by_longitude) {
			foreach($va_locs_by_longitude as $lng => $content_items) {
				$va_buf = $va_ajax_ids = [];
				$vs_label = $vs_ajax_content_url = '';
				
				foreach($content_items as $content_item) {
					if (!$vs_label) {
						$vs_label = $content_item['label'];
					} else { // if there are multiple items in one location, we want to add the labels of the 2nd and all following items to the 'content' part of the overlay, while still not duplicating content (hence, md5)
						$va_buf[md5($content_item['label'])] = $content_item['label'];
					}
					if (!$vs_ajax_content_url) { $vs_ajax_content_url = $content_item['ajaxContentUrl']; }
					$va_ajax_ids[] = $content_item['ajaxContentID'];
					$va_buf[md5($content_item['content'])] = $content_item['content'];	// md5 is to ensure there is no duplicate content (eg. if something is mapped to the same location twice)
				}	
				
				if (!($lat && $lng)) { continue; }
				if (($lat < -90) || ($lat > 90)) { continue; }
				if (($lng < -180) || ($lng > 180)) { continue; }
				
				$vn_angle = isset($content_item['angle']) ? (float)$content_item['angle'] : null;
				$vs_label = preg_replace("![\n\r]+!", " ", $vs_label);
				$vs_content = preg_replace("![\n\r]+!", " ", join($vs_delimiter, $va_buf));
				$vs_ajax_url = preg_replace("![\n\r]+!", " ", ($vs_ajax_content_url ? ($vs_ajax_content_url."/id/".join(';', $va_ajax_ids)) : ''));
				
        		$l = ['lat' => $lat, 'lng' => $lng, 'label' => $vs_label, 'content' => $vs_content];
        		if ($vn_angle !== 0) { $l['angle'] = $vn_angle; }
        		if ($vs_ajax_url) { $l['ajaxUrl'] = $vs_ajax_url; } else { $l['content'] = $vs_content; }
        		$pointList[] = $l;
			}
			$vn_c++;
		}
		
		$circleList = [];
		foreach($circles as $lat => $va_locs_by_longitude) {
			foreach($va_locs_by_longitude as $lng => $content_items) {
				$va_buf = $va_ajax_ids = [];
				$vs_label = $vs_ajax_content_url = ''; $vn_radius = null;
				
				foreach($content_items as $content_item) {
					if (!$vn_radius) { $vn_radius = $content_item['radius']; }
					if (!$vs_label) {
						$vs_label = $content_item['label'];
					} else { // if there are multiple items in one location, we want to add the labels of the 2nd and all following items to the 'content' part of the overlay, while still not duplicating content (hence, md5)
						$va_buf[md5($content_item['label'])] = $content_item['label'];
					}
					if (!$vs_ajax_content_url) { $vs_ajax_content_url = $content_item['ajaxContentUrl']; }
					$va_ajax_ids[] = $content_item['ajaxContentID'];
					$va_buf[md5($content_item['content'])] = $content_item['content'];	// md5 is to ensure there is no duplicate content (eg. if something is mapped to the same location twice)
				}	
				
				if (!($lat && $lng)) { continue; }
				$vs_label = preg_replace("![\n\r]+!", " ", $vs_label);
				$vs_content = preg_replace("![\n\r]+!", " ", join($vs_delimiter, $va_buf));
				$vs_ajax_url = preg_replace("![\n\r]+!", " ", ($vs_ajax_content_url ? ($vs_ajax_content_url."/id/".join(';', $va_ajax_ids)) : ''));
				
        		$l = ['lat' => $lat, 'lng' => $lng, 'label' => $vs_label,  'content' => $vs_content, 'radius' => $vn_radius];
        		if ($vs_ajax_url) { $l['ajaxUrl'] = $vs_ajax_url; } else { $l['content'] = $vs_content; }
        		$circleList[] = $l;
			}
			$vn_c++;
		}
		
		switch(strtoupper($ps_format)) {
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
				
				
				$vs_buf = "<div style='width:{$vs_width}; height:{$vs_height}' id='map_{$vs_id}'> </div>\n
<script type='text/javascript'>
		var arrowIcon = L.icon({
			iconUrl: '{$base_path}/assets/leaflet/images/arrow-icon.png',
			retinaUrl: '{$base_path}/assets/leaflet/images/arrow-icon-2x.png',
			iconSize: [25, 50],
			iconAnchor: [12, 50],
			popupAnchor: [-3, -50]
		});
		var pointList{$vs_id} = ".json_encode($pointList).";
		var circleList{$vs_id} = ".json_encode($circleList).";
		var pathList{$vs_id} = ".json_encode($paths).";
		var map = L.map('map_{$vs_id}', { zoomControl: ".($vb_show_scale_controls ? "true" : "false").", attributionControl: false, minZoom: {$vn_min_zoom_level}, maxZoom: {$vn_max_zoom_level} }).setView([0, 0], {$vn_zoom_level});
		var b = L.tileLayer('{$base_map_url}', {noWrap: ".($vb_no_wrap ? "true" : "false")."}).addTo(map);	
		var g = new L.featureGroup();
		g.addTo(map);
		
		jQuery(pointList{$vs_id}).each(function(k, v) {
			var opts = { title: jQuery('<div>').html(v.label).text() };
			if (v.angle != 0) { opts['icon'] = arrowIcon; }
			var m = L.marker([v.lat, v.lng], opts);
			
			if (v.angle != 0) { m.setRotationAngle(v.angle); }
			if (v.label || v.content) { 
			    if (v.ajaxUrl) {
			        var ajaxUrl = v.ajaxUrl;
                    m.bindPopup(
                        (layer)=>{
                            var el = document.createElement('div');
                            $.get(ajaxUrl,function(data){
                                el.innerHTML = data + '<br/>';
                            });

                            return el;
                        }, { minWidth: 400, maxWidth : 560 });
			    } else {
			        m.bindPopup(v.label + v.content); 
			    }
			}
			m.addTo(g);
		});
		
		jQuery(circleList{$vs_id}).each(function(k, v) {
			var m = L.circle([v.lat, v.lng], { radius: v.radius, color: '{$vs_path_color}', weight: '{$vn_path_weight}', opacity: '{$vn_path_opacity}', fillColor: '{$vs_fill_color}', fillOpacity: '{$vn_fill_opacity}' });
			if (v.label || v.content) { 
			    if (v.ajaxUrl) {
			        var ajaxUrl = v.ajaxUrl;
                    m.bindPopup(
                        (layer)=>{
                            var el = document.createElement('div');
                            $.get(ajaxUrl,function(data){
                                el.innerHTML = data + '<br/>';
                            });

                            return el;
                        }, { minWidth: 400, maxWidth : 560 });
			    } else {
			        m.bindPopup(v.label + v.content); 
			    }
			}
			m.addTo(g);
		});
		
		jQuery(pathList{$vs_id}).each(function(k, v) {
			var splitPts = v.path.map(c => { return [c.latitude, c.longitude] });
			var m = L.polygon(splitPts, { color: '{$vs_path_color}', weight: '{$vn_path_weight}', opacity: '{$vn_path_opacity}', fillColor: '{$vs_fill_color}', fillOpacity: '{$vn_fill_opacity}' });
			if (v.label || v.content) { 
			    if (v.ajaxUrl) {
			        var ajaxUrl = v.ajaxUrl;
                    m.bindPopup(
                        (layer)=>{
                            var el = document.createElement('div');
                            $.get(ajaxUrl,function(data){
                                el.innerHTML = data + '<br/>';
                            });

                            return el;
                        }, { minWidth: 400, maxWidth : 560 });
			    } else {
			        m.bindPopup(v.label + v.content); 
			    }
			}
			m.addTo(g);
		});
			
		var bounds = g.getBounds();
		if (bounds.isValid()) { map.fitBounds(bounds)".((strlen($vn_zoom_level) && !$we_set_zoom) ? ".setZoom({$vn_zoom_level})" : "")."; }
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
		
		$va_element_width = caParseFormElementDimension($pa_element_info['settings']['fieldWidth']);
		$vn_element_width = $va_element_width['dimension'];
		$va_element_height = caParseFormElementDimension($pa_element_info['settings']['fieldHeight']);
		$vn_element_height = $va_element_height['dimension'];
		
		
		$points_are_directional = (bool)$pa_element_info['settings']['pointsAreDirectional'] ? 1 : 0;
 		
 		$element_id = (int)$pa_element_info['element_id'];
 		$vs_id = $pa_element_info['element_id']."_{n}";
 		
 		if (!($base_map_url = $this->opo_config->get('leaflet_base_layer'))) { 
 			$base_map_url = 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png';
 		}
 		
 		$base_path = null;
 		if ($request = caGetOption(['request'], $pa_options, null)) {
 			$base_path = $request->getBaseUrlPath();
 		}
		$vs_element = 	"
<div id='mapholder_{$element_id}_{n}' class='mapholder' style='z-index:0;'>
	 <div class='map' style='width:695px; height:400px;'></div>
</div>
<script type='text/javascript'>
	jQuery(document).ready(function() {
		var map_{$vs_id}_editing_enabled = false;
		var map_{$vs_id}_points_are_directional = {$points_are_directional};
		var map_{$vs_id}_rotation_in_progress = null;
		var map_{$vs_id}_loc_str = '{{{$element_id}}}';
		
		var arrowIcon = L.icon({
			iconUrl: '{$base_path}/assets/leaflet/images/arrow-icon.png',
			retinaUrl: '{$base_path}/assets/leaflet/images/arrow-icon-2x.png',
			iconSize: [25, 50],
			iconAnchor: [12, 50],
			popupAnchor: [-3, -50]
		});
		
		var re = /\[([\d\,\-\.\:\;~\*]+)\]/;

		var map_{$vs_id}_loc_label = jQuery.trim(map_{$vs_id}_loc_str.match(/^[^\[]+/));
		
		var map = L.map('mapholder_{$element_id}_{n}', { attributionControl: false, maxZoom: 18 }).setView([0, 0], 8);
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
					
					if (layer.options.rotationAngle !== 0) {
						objs.push(c.lat + ',' + c.lng + '*' + layer.options.rotationAngle);
					} else {
						objs.push(c.lat + ',' + c.lng);
					}
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
		
		var map_{$vs_id}_set_rotation_guide = function(layer, angle) {
			var transformation = new L.Transformation(
				1, Math.sin(angle*Math.PI / 180)*100,
				1, Math.cos(angle*Math.PI / 180)*-100
			),
			pointB = map.layerPointToLatLng(
				transformation.transform(map.latLngToLayerPoint(layer._latlng))
			);
			var pointList = [layer._latlng, pointB];
			
			var g = new L.featureGroup();
			var pl = new L.Polyline(pointList, {
				color: '#cc0000',
				weight: 5,
				stroke: true,
				opacity: 0.5,
				marker: layer,
				bubblingMouseEvents: false
			}).addTo(g);
			
			
			var circlePt = map.project(pointB);				
			
			new L.circle(map.unproject(circlePt), { 
				color: '#cc0000', 
				stroke: false,
				fill: true,
				fillOpacity: 0.5, 
				radius: 5,
				marker: layer
			}).on('mousedown', function(e) {
				this.options.rotationInProgress = true;
				map_{$vs_id}_rotation_in_progress = {'line': pl, 'end': this};
				this.setStyle({color: '#00cc00'});
				pl.setStyle({color: '#00cc00'});
				map.dragging.disable();
			}).on('mouseup', function(e) {
				this.options.rotationInProgress = false;
				map_{$vs_id}_rotation_in_progress = null;
				this.setStyle({color: '#cc0000'});
				pl.setStyle({color: '#cc0000'});
				map.dragging.enable();
			}).addTo(g);
			
			return g;
		};
		
		map.on('draw:created', function (e) {	
			e.layer.addTo(g);
			map_{$vs_id}_update_coord_list(e);
		});
		map.on('draw:edited', map_{$vs_id}_update_coord_list);
		map.on('draw:deletestop', map_{$vs_id}_update_coord_list);
		
		map.on('draw:editstart', function(e) {
			if (!map_{$vs_id}_points_are_directional) { return; }
			map_{$vs_id}_editing_enabled = true;
			
			// draw rotate controls on all markers
			map.eachLayer(function (layer) { 
				if (layer instanceof L.Marker) {
					layer.options.dirControl = map_{$vs_id}_set_rotation_guide(layer, layer.options.rotationAngle);
					
					map.on('mousemove', function(e) {
						if (map_{$vs_id}_rotation_in_progress) {
							var pointB = new L.LatLng(e.latlng.lat, e.latlng.lng);
							
							var line = map_{$vs_id}_rotation_in_progress['line'];
							var end = map_{$vs_id}_rotation_in_progress['end'];
							
							line.setLatLngs([line._latlngs[0], pointB]);
							
							var A = line._parts[0][0],
								B = line._parts[0][1];
							var angle = (Math.atan2(0,1) - Math.atan2((B.x - A.x),(B.y - A.y)))*180/Math.PI+180;
							end.options.marker.setRotationAngle(angle);
							
							var circlePt = map.project(pointB);
							
							circlePt.x += 8 * Math.sin(angle*(Math.PI/180));
							circlePt.y -= 8 * Math.cos(angle*(Math.PI/180));
							
							end.setLatLng(map.unproject(circlePt));
						}
					});
					layer.options.dirControl.addTo(map);
				}
			});
		});
		
		// Remove marker rotation controls
		map.on('draw:editstop', function(e) {
			if (!map_{$vs_id}_points_are_directional) { return; }
			map.eachLayer(function (layer) { 
				if (layer instanceof L.Marker) {
					map.removeLayer(layer.options.dirControl);
				}
			});
			
			map_{$vs_id}_editing_enabled = false;
		});
		
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
					var a = pt[1].split(/\*/);
					pt[1] = a[0];
					
					var angle = a[1] ? a[1] : 0;
					var opts = (map_{$vs_id}_points_are_directional && (angle != 0)) ? { icon: arrowIcon} : {};
					var m = L.marker([parseFloat(pt[0]), parseFloat(pt[1])], opts).on('dragstart', function(e) {
						if (!map_{$vs_id}_points_are_directional) { return; }
						map.removeLayer(e.target.options.dirControl);
					}).on('dragend', function(e) {
						if (!map_{$vs_id}_points_are_directional) { return; }
						e.target.options.dirControl = map_{$vs_id}_set_rotation_guide(e.target, e.target.options.rotationAngle);
						e.target.options.dirControl.addTo(map);
					}).addTo(g);
					
					
					if (map_{$vs_id}_points_are_directional && a) { m.setRotationAngle(angle); }
					if (map_{$vs_id}_loc_label) { m.bindPopup(map_{$vs_id}_loc_label); }
				}
			});
		} else {
			var c = localStorage.getItem('leafletLastPos');
			if (c) {
				var coord = c.split(/,/);
				map.setView(coord, 16, {animate: false});
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
