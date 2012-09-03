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

		var features_{$vs_id} = [];
		
		var styles_{$vs_id} = new OpenLayers.StyleMap({
			'default': new OpenLayers.Style({
				pointRadius: '5',
				fillColor: '#ffcc66',
				strokeColor: '#ff9933',
				strokeWidth: 2,
				graphicZIndex: 1
			}),
			'select': new OpenLayers.Style({
				fillColor: '#66ccff',
				strokeColor: '#3399ff',
				graphicZIndex: 2
			})
		});
";

		$va_locs = $va_paths = array();
		foreach($va_map_items as $o_map_item) {
			$va_coords = $o_map_item->getCoordinates();
			if (sizeof($va_coords) > 1) {
				// is path
				$va_paths[] = array('path' => $va_coords, 'label' => $o_map_item->getLabel(), 'content' => $o_map_item->getContent(), 'ajaxContentUrl' => $o_map_item->getAjaxContentUrl(), 'ajaxContentID' => $o_map_item->getAjaxContentID());
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
				
				if (!($vn_latitude && $vn_longitude)) { continue; }
				$vs_label = preg_replace("![\n\r]+!", " ", addslashes($vs_label));
				$vs_content = preg_replace("![\n\r]+!", " ", addslashes(join($vs_delimiter, $va_buf)));
				$vs_ajax_url = preg_replace("![\n\r]+!", " ", ($vs_ajax_content_url ? addslashes($vs_ajax_content_url."/id/".join(';', $va_ajax_ids)) : ''));
				
        		$vs_buf .= "
        		features_{$vs_id}.push(new OpenLayers.Feature.Vector(
					new OpenLayers.Geometry.Point({$vn_longitude},{$vn_latitude}).transform(new OpenLayers.Projection('EPSG:4326'),map_{$vs_id}.getProjectionObject()), {
						label: '{$vs_label}',
						content: '{$vs_content}',
						ajaxUrl: '{$vs_ajax_url}'
					}
				));\n";
        
			}
			$vn_c++;
		}

		$vs_buf .= "
			var style = { 
			  strokeColor: '#0000ff', 
			  strokeOpacity: 0.5,
			  strokeWidth: 5
			};
				
			var points_{$vs_id} = new OpenLayers.Layer.Vector('Points', {
				styleMap: styles_{$vs_id},
				rendererOptions: {zIndexing: true}
			});\n";
		
		foreach($va_paths as $vn_i => $va_path) {
			$va_buf = array();
			$va_ajax_ids = array();
				
			$vs_label = $va_path['label'];
			$vs_content = $va_path['content'];
			$vs_ajax_url = preg_replace("![\n\r]+!", " ", ($va_path['ajaxContentUrl'] ? addslashes($va_path['ajaxContentUrl']."/id/".$va_path['ajaxContentID']) : ''));
	
			
			$va_path_coords = array();
			foreach($va_path['path'] as $va_path_point) {
				$va_path_coords[] = "new OpenLayers.Geometry.Point(".$va_path_point['longitude'].",".$va_path_point['latitude'].").transform(new OpenLayers.Projection('EPSG:4326'), map_{$vs_id}.getProjectionObject())";
			}
			
			$vs_buf .= "var lineFeature = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString([".join(",", $va_path_coords)."]), {
					label: '{$vs_label}',
					content: '{$vs_content}',
					ajaxUrl: '{$vs_ajax_url}'
				}, style);\n";
			$vs_buf .= "points_{$vs_id}.addFeatures([lineFeature]);\n";
		}
		
		$vs_buf .= "
			var popup_{$vs_id} = null;
			points_{$vs_id}.addFeatures(features_{$vs_id});
			
			var selectedFeature_{$vs_id};
			var markerClick_{$vs_id} = function (feature) {
				selectedFeature_{$vs_id} = feature;
				
				if (!popup_{$vs_id}) {
					popup_{$vs_id} = new OpenLayers.Popup.AnchoredBubble('infoBubble', 
						 feature.geometry.getBounds().getCenterLonLat(),
						 null,
						 feature.data.label,
						 null, true, onPopupClose);
					feature.popup = popup_{$vs_id};
					map_{$vs_id}.addPopup(popup_{$vs_id});
				} else {
					jQuery(popup_{$vs_id}.contentDiv).html(feature.data.label + feature.data.content);
					
					if (feature.geometry && feature.geometry.x) {
						popup_{$vs_id}.lonlat = new OpenLayers.LonLat(feature.geometry.x,feature.geometry.y);
					} else {
						popup_{$vs_id}.lonlat = new OpenLayers.LonLat(feature.geometry.bounds.left,feature.geometry.bounds.top);
					}
					popup_{$vs_id}.show();
				}
				
				popup_{$vs_id}.setSize(new OpenLayers.Size(300, 150));
				
				if (feature.data.ajaxUrl) {
					jQuery(popup_{$vs_id}.contentDiv).html('".htmlspecialchars(_t('Loading...'), ENT_QUOTES)."');
					jQuery(popup_{$vs_id}.div).css('width', '300px').css('height', '150px').css('overflow', 'auto');
					jQuery(popup_{$vs_id}.contentDiv).load(feature.data.ajaxUrl);
				}
			};
			
			var selectControl_{$vs_id} = new OpenLayers.Control.SelectFeature(points_{$vs_id}, {hover: false, onSelect: markerClick_{$vs_id}});
            map_{$vs_id}.addControl(selectControl_{$vs_id});
            selectControl_{$vs_id}.activate();
            
			function onPopupClose(evt) {
          	  selectControl_{$vs_id}.unselect(selectedFeature_{$vs_id});
          	  popup_{$vs_id}.hide();
        	}
			
			map_{$vs_id}.addLayer(points_{$vs_id});
			map_{$vs_id}.zoomToExtent(points_{$vs_id}.getDataExtent());
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