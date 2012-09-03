<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/GeographicMap/WLPlugGeographicMapOpenLayers.php : generates maps via OpenLayers API
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
    
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugGeographicMap.php");
include_once(__CA_LIB_DIR__."/core/Plugins/GeographicMap/BaseGeographicMapPlugin.php");

class WLPlugGeographicMapOpenLayers Extends BaseGeographicMapPlugIn Implements IWLPlugGeographicMap {
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'OpenLayers';
		
		$this->description = _t('Generates maps using the OpenLayers API');
		
		JavascriptLoadManager::register("openlayers");
	}
	# ------------------------------------------------
	/**
	 * Generate OpenLayers output in specified format
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
	 *		pathColor - 
	 *		pathWeight -
	 *		pathOpacity - 
	 */
	public function render($ps_format, $pa_options=null) {
		list($vn_width, $vn_height) = $this->getDimensions();
		$vn_width = intval($vn_width);
		$vn_height = intval($vn_height);
		if ($vn_width < 1) { $vn_width = 200; }
		if ($vn_height < 1) { $vn_height = 200; }
		
		$va_map_items = $this->getMapItems();
		$va_extents = $this->getExtents();
		
		$vs_delimiter = isset($pa_options['delimiter']) ? $pa_options['delimiter'] : "<br/>";
		$vn_zoom_level = (isset($pa_options['zoomLevel']) && ((int)$pa_options['zoomLevel'] > 0)) ? (int)$pa_options['zoomLevel'] : null;
		$vn_min_zoom_level = (isset($pa_options['minZoomLevel']) && ((int)$pa_options['minZoomLevel'] > 0)) ? (int)$pa_options['minZoomLevel'] : null;
		$vn_max_zoom_level = (isset($pa_options['maxZoomLevel']) && ((int)$pa_options['maxZoomLevel'] > 0)) ? (int)$pa_options['maxZoomLevel'] : null;
		
		$vs_path_color = (isset($pa_options['pathColor'])) ? $pa_options['pathColor'] : '#cc0000';
		$vn_path_weight = (isset($pa_options['pathWeight']) && ((int)$pa_options['pathWeight'] > 0)) ? (int)$pa_options['pathWeight'] : 2;
		$vn_path_opacity = (isset($pa_options['pathOpacity']) && ((int)$pa_options['pathOpacity'] >= 0)  && ((int)$pa_options['pathOpacity'] <= 1)) ? (int)$pa_options['pathOpacity'] : 0.5;
		
		
		$vs_type = (isset($pa_options['mapType'])) ? strtoupper($pa_options['mapType']) : strtoupper($this->opo_config->get('google_maps_default_type'));
		if (!in_array($vs_type, array('ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN'))) {
			$vs_type = 'SATELLITE';
		}
		if (!$vs_id = trim($this->get('id'))) { $vs_id = 'map'; }
		
		switch(strtoupper($ps_format)) {
			# ---------------------------------
			case 'HTML':
			default:
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
				
				$vs_buf = "
<script type='text/javascript'>;
	jQuery(document).ready(function() {
		var map_{$vs_id} = new OpenLayers.Map({
		div: '{$vs_id}',
		layers: [new OpenLayers.Layer.OSM()],
		controls: [
			new OpenLayers.Control.Navigation({
				dragPanOptions: {
					enableKinetic: true
				}
			}),
			new OpenLayers.Control.Attribution(),
			new OpenLayers.Control.Zoom()
		],
		center: [0, 0],
		zoom: 1
	});
	var markers_{$vs_id} = new OpenLayers.Layer.Markers('Markers');
	
	var size = new OpenLayers.Size(21,25);
	var icon = new OpenLayers.Icon('http://www.openlayers.org/dev/img/marker.png', size, new OpenLayers.Pixel(-(size.w/2), -size.h));\n\n
	var popup_{$vs_id} = null;
	var markerClick_{$vs_id} = function (evt) {
			if (popup_{$vs_id} == null) {
				popup_{$vs_id} = this.createPopup(this.closeBox);
				map_{$vs_id}.addPopup(popup_{$vs_id});
				popup_{$vs_id}.show();
			} else {
				popup_{$vs_id}.lonlat = this.lonlat;
				jQuery(popup_{$vs_id}.contentDiv).html('".htmlspecialchars(_t('Loading...'), ENT_QUOTES)."');
				popup_{$vs_id}.show();
			}
			if (this.data.ajaxUrl) {
				jQuery(popup_{$vs_id}.div).css('width', '200px').css('height', '200px').css('overflow', 'auto');
				jQuery(popup_{$vs_id}.contentDiv).load(this.data.ajaxUrl).css('width', '100%').css('height', '100%');
			}
			console.log(this);
			OpenLayers.Event.stop(evt);
		};\n\n
";

$va_locs = $va_paths = array();
		foreach($va_map_items as $o_map_item) {
			$va_coords = $o_map_item->getCoordinates();
			if (sizeof($va_coords) > 1) {
				// is path
				$va_path = array();
				foreach($va_coords as $va_coord) {
					$va_path[] = "new google.maps.LatLng({$va_coord['latitude']},{$va_coord['longitude']})";
				}
				$va_paths[] = array('path' => $va_coords, 'pathJS' => $va_path, 'label' => $o_map_item->getLabel(), 'content' => $o_map_item->getContent(), 'ajaxContentUrl' => $o_map_item->getAjaxContentUrl(), 'ajaxContentID' => $o_map_item->getAjaxContentID());
			} else {
				// is point
				$va_coord = array_shift($va_coords);
				$va_locs[$va_coord['latitude']][$va_coord['longitude']][] = array('label' => $o_map_item->getLabel(), 'content' => $o_map_item->getContent(), 'ajaxContentUrl' => $o_map_item->getAjaxContentUrl(), 'ajaxContentID' => $o_map_item->getAjaxContentID());
			}
		}
		
		$vn_c = 0;
		foreach($va_locs as $vn_latitude => $va_locs_by_longitude) {
			foreach($va_locs_by_longitude as $vn_longitude => $va_marker_content_items) {
				$va_buf = array();
				$va_ajax_ids = array();
				$vs_label = $vs_ajax_content_url = '';
				foreach($va_marker_content_items as $va_marker_content_item) {
					if (!$vs_label) { $vs_label = $va_marker_content_item['label']; }
					if (!$vs_ajax_content_url) { $vs_ajax_content_url = $va_marker_content_item['ajaxContentUrl']; }
					$va_ajax_ids[] = $va_marker_content_item['ajaxContentID'];
					$va_buf[md5($va_marker_content_item['content'])] = $va_marker_content_item['content'];	// md5 is to ensure there is no duplicate content (eg. if something is mapped to the same location twice)
				}	
				
				$vn_latitude = sprintf("%3.4f", $vn_latitude);
				$vn_longitude = sprintf("%3.4f", $vn_longitude);
				
				if (!($vn_latitude && $vn_longitude)) { continue; }
				$vs_content = preg_replace("![\n\r]+!", " ", addslashes($vs_label))."', '".preg_replace("![\n\r]+!", " ", addslashes(join($vs_delimiter, $va_buf)))."'";
				$vs_ajax_url = preg_replace("![\n\r]+!", " ", ($vs_ajax_content_url ? addslashes($vs_ajax_content_url."/id/".join(';', $va_ajax_ids)) : ''));
				//$vs_buf .= "	caMap_{$vs_id}_markers.push(caMap_{$vs_id}.makeMarker(".$vn_latitude.", ".$vn_longitude.", '".preg_replace("![\n\r]+!", " ", addslashes($vs_label))."', '".preg_replace("![\n\r]+!", " ", addslashes(join($vs_delimiter, $va_buf)))."', '".preg_replace("![\n\r]+!", " ", ($vs_ajax_content_url ? addslashes($vs_ajax_content_url."/id/".join(';', $va_ajax_ids)) : ''))."'));\n";
				$vs_buf .= "markers_{$vs_id}.addMarker(m = new OpenLayers.Marker(lonLat = new OpenLayers.LonLat({$vn_longitude},{$vn_latitude}).transform(
        new OpenLayers.Projection('EPSG:4326'),map_{$vs_id}.getProjectionObject()),".($vn_c ? "icon.clone()" : "icon")."));\n";
        
        		$vs_buf .= "m.feature = new OpenLayers.Feature(markers_{$vs_id}, lonLat);
							m.feature.closeBox = true;
							m.feature.popupClass = OpenLayers.Class(OpenLayers.Popup.AnchoredBubble, { autoSize: true });
							m.feature.data.popupContentHTML = '".htmlspecialchars(_t('Loading...'), ENT_QUOTES)."';
							m.feature.data.ajaxUrl = '{$vs_ajax_url}';
							m.feature.data.overflow = 'auto';\n";
				$vs_buf .= "
    m.events.register('mousedown', m.feature, markerClick_{$vs_id});\n
			";
			}
			$vn_c++;
		}
		
$vs_buf .= "

	map_{$vs_id}.addLayer(markers_{$vs_id});
	map_{$vs_id}.zoomToExtent(markers_{$vs_id}.getDataExtent());
});
</script>
";
				break;
			# ---------------------------------
		}
		
		return $vs_buf;
	}
	# ------------------------------------------------
}
?>