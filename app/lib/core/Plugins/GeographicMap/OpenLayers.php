<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/GeographicMap/WLPlugGeographicMapOpenLayers.php : generates maps via OpenLayers API
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
	 *		width = Width of map + controls in pixels; default is 690
	 *		height = Height of map + controls in pixels; default is 300
	 *		baseLayer = Tiles to user for base layer. Should be full class name with optional constructor string (Eg. OpenLayers.Layer.Stamen('toner')); default is OpenLayers.Layer.OSM()
	 *		pointRadius = Radius, in pixels, of plotted points. Default is 5 pixels
	 *		fillColor = Color (in hex format with leading "#") to fill regions and points with
	 *		strokeWidth = Width of plotted paths, in pixels. Default is 2
	 *		strokeColor = Color of plotted paths, in hex format with leading "#"
	 *		fillColorSelected = Color to fill regions with when selected, in hex format with leading "#"
	 *		strokeColorSelected = Color of plotted paths when selected, in hex format with leading "#"
	 *
	 * 		delimiter = HTML to place between items displayed in info overlays for plotted items. Default is an HTML break ("<br/>")
	 * @return string HTML output
	 */
	public function render($ps_format, $pa_options=null) {
		$o_config = Configuration::load();
		
		list($vn_width, $vn_height) = $this->getDimensions();
		
		if (!preg_match('!^[\d]+%$!', $vn_width)) {
			$vn_width = intval($vn_width)."px";
			if ($vn_width < 1) { $vn_width = 690; }
		}
		if (!preg_match('!^[\d]+%$!', $vn_height)) {
			$vn_height = intval($vn_height)."px";
			if ($vn_height < 1) { $vn_height = 300; }
		}
		
		$va_options = caGetOptions($pa_options, array());
		
		$va_map_items = $this->getMapItems();
		$va_extents = $this->getExtents();
		
		$vs_delimiter = isset($pa_options['delimiter']) ? $pa_options['delimiter'] : "<br/>";
		
		if (!($vs_base_layer = $va_options['baseLayer'])) { 
			if (!($vs_base_layer = $o_config->get('openlayers_base_layer'))) {
				$vs_base_layer = 'OpenLayers.Layer.OSM()';
			}
		}
		
		if (($vn_point_radius = $va_options['pointRadius']) < 1) { 
			if (!($vn_point_radius = $o_config->get('openlayers_point_radius'))) {
				$vn_point_radius = 5; 
			}
		}
		if (($vs_fill_color = $va_options['fillColor']) < 1) { 
			if (!($vs_fill_color = $o_config->get('openlayers_fill_color'))) {
				$vs_fill_color = '#ffcc66'; 
			}
		}
		if (($vn_stroke_width = $va_options['strokeWidth']) < 1) { 
			if (!($vn_stroke_width = $o_config->get('openlayers_stroke_width'))) {
				$vn_stroke_width = 2; 
			}
		}
		if (($vs_stroke_color = $va_options['strokeColor']) < 1) { 
			if (!($vs_stroke_color = $o_config->get('openlayers_stroke_color'))) {
				$vs_stroke_color = '#ff9933'; 
			}
		}
		
		if (($vs_fill_color_selected = $va_options['fillColorSelected']) < 1) { 
			if (!($vs_fill_color_selected = $o_config->get('openlayers_fill_color_selected'))) {
				$vs_fill_color_selected = '#66ccff'; 
			}
		}
		if (($vs_stroke_color_selected = $va_options['strokeColorSelected']) < 1) { 
			if (!($vs_stroke_color_selected = $o_config->get('openlayers_stroke_color_selected'))) {
				$vs_stroke_color_selected = '#3399ff'; 
			}
		}
		
		
		
		
		if (!$vs_id = trim($this->get('id'))) { $vs_id = 'map'; }
		
		switch(strtoupper($ps_format)) {
			# ---------------------------------
			case 'HTML':
			default:
				$va_layers = array();
				
				if ($vs_tileserver_url = caGetOption('tileServerURL', $pa_options, null)) {
					if (!($vs_tile_layer_name = trim(caGetOption('tileLayerName', $pa_options, null)))) {
						$vs_tile_layer_name = 'Custom layer';
					}
					$va_layers[] = "new OpenLayers.Layer.OSM('{$vs_tile_layer_name}', '{$vs_tileserver_url}',{ isBaseLayer: false, tileOptions : {crossOriginKeyword: null}})";
				}
		
				$vs_layer_switcher_control = caGetOption('layerSwitcherControl', $pa_element_info['settings'], null) ? "map_{$vs_id}.addControl(new OpenLayers.Control.LayerSwitcher());" : "";
		
		
				$va_layers[] = "new {$vs_base_layer}";
				$vs_buf = "<div style='width:{$vn_width}; height:{$vn_height}' id='{$vs_id}' ".((isset($pa_options['classname']) && $pa_options['classname']) ? "class='".$pa_options['classname']."'" : "")."> </div>\n";
				$vs_buf .= "
<script type='text/javascript'>;
	jQuery(document).ready(function() {
		var map_{$vs_id} = new OpenLayers.Map({
		div: '{$vs_id}',
		layers: [".join(",", $va_layers)."],
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
	{$vs_layer_switcher_control}
		var features_{$vs_id} = [];
		
		var styles_{$vs_id} = new OpenLayers.StyleMap({
			'default': new OpenLayers.Style({
				pointRadius: '{$vn_point_radius}',
				fillColor: '{$vs_fill_color}',
				strokeColor: '{$vs_stroke_color}',
				strokeWidth: '{$vn_stroke_width}',
				graphicZIndex: 1
			}),
			'select': new OpenLayers.Style({
				fillColor: '{$vs_fill_color_selected}',
				strokeColor: '{$vs_stroke_color_selected}',
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
			  strokeColor: '{$vs_stroke_color}', 
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
				
			$vs_label = preg_replace("![\n\r]+!", " ", addslashes($va_path['label']));
			$vs_content = preg_replace("![\n\r]+!", " ", addslashes($va_path['content']));
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
						 feature.data.label + feature.data.content,
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
	/**
	 * Returns HTML for editable Geocode attribute, suitable for inclusion in a bundleable editing form 
	 *
	 * @param array $pa_element_info Array of information about the element the bundle is being generate for
	 * @param array $pa_options Options are:
	 *		width = Width of map + controls in pixels; default is 690
	 *		height = Height of map + controls in pixels; default is 300
	 *		baseLayer = Tiles to user for base layer. Should be full class name with optional constructor string (Eg. OpenLayers.Layer.Stamen('toner')); default is OpenLayers.Layer.OSM()
	 *		pointRadius = Radius, in pixels, of plotted points. Default is 5 pixels
	 *		fillColor = Color (in hex format with leading "#") to fill regions and points with
	 *		strokeWidth = Width of plotted paths, in pixels. Default is 2
	 *		strokeColor = Color of plotted paths, in hex format with leading "#"
	 *		fillColorSelected = Color to fill regions with when selected, in hex format with leading "#"
	 *		strokeColorSelected = Color of plotted paths when selected, in hex format with leading "#"
	 *
	 * @return string HTML output
	 */
	public function getAttributeBundleHTML($pa_element_info, $pa_options=null) {
		JavascriptLoadManager::register('openlayers');
		$o_config = Configuration::load();
		
		$va_element_width = caParseFormElementDimension($pa_element_info['settings']['fieldWidth']);
		$vn_element_width = $va_element_width['dimension'];
		$va_element_height = caParseFormElementDimension($pa_element_info['settings']['fieldHeight']);
		$vn_element_height = $va_element_height['dimension'];
		$va_options = caGetOptions($pa_options, array(
			'width' => $vn_element_width, 'height' => $vn_element_height
		));
		
		if (($vn_width = $va_options['width']) < 100) { $vn_width = 690; }
		if (($vn_height = $va_options['height']) < 100) { $vn_height = 300; }
		if (!($vs_base_layer = $va_options['baseLayer'])) { 
			if (!($vs_base_layer = $o_config->get('openlayers_base_layer'))) {
				$vs_base_layer = 'OpenLayers.Layer.OSM()';
			}
		}
		
		if (($vn_point_radius = $va_options['pointRadius']) < 1) { 
			if (!($vn_point_radius = $o_config->get('openlayers_point_radius'))) {
				$vn_point_radius = 5; 
			}
		}
		if (($vs_fill_color = $va_options['fillColor']) < 1) { 
			if (!($vs_fill_color = $o_config->get('openlayers_fill_color'))) {
				$vs_fill_color = '#ffcc66'; 
			}
		}
		if (($vn_stroke_width = $va_options['strokeWidth']) < 1) { 
			if (!($vn_stroke_width = $o_config->get('openlayers_stroke_width'))) {
				$vn_stroke_width = 2; 
			}
		}
		if (($vs_stroke_color = $va_options['strokeColor']) < 1) { 
			if (!($vs_stroke_color = $o_config->get('openlayers_stroke_color'))) {
				$vs_stroke_color = '#ff9933'; 
			}
		}
		
		if (($vs_fill_color_selected = $va_options['fillColorSelected']) < 1) { 
			if (!($vs_fill_color_selected = $o_config->get('openlayers_fill_color_selected'))) {
				$vs_fill_color_selected = '#66ccff'; 
			}
		}
		if (($vs_stroke_color_selected = $va_options['strokeColorSelected']) < 1) { 
			if (!($vs_stroke_color_selected = $o_config->get('openlayers_stroke_color_selected'))) {
				$vs_stroke_color_selected = '#3399ff'; 
			}
		}
 		
		$po_request = isset($pa_options['request']) ? $pa_options['request'] : null;
		
		$vs_id = $pa_element_info['element_id'];
		
		$vs_custom_tile_layer = '';
		if ($vs_tileserver_url = caGetOption('tileServerURL', $pa_element_info['settings'], null)) {
			if (!($vs_tile_layer_name = trim(caGetOption('tileLayerName', $pa_element_info['settings'], null)))) {
				$vs_tile_layer_name = 'Custom layer';
			}
			$vs_custom_tile_layer = "	map_{$vs_id}.addLayer(
		new OpenLayers.Layer.OSM('{$vs_tile_layer_name}', '{$vs_tileserver_url}', 
			{
				isBaseLayer: false,
				tileOptions : {crossOriginKeyword: null}
			}
	));";
		}
		
		$vs_layer_switcher_control = caGetOption('layerSwitcherControl', $pa_element_info['settings'], null) ? "map_{$vs_id}.addControl(new OpenLayers.Control.LayerSwitcher());" : "";
		
		
		$vs_element = '<div id="{fieldNamePrefix}mapholder_'.$vs_id.'_{n}" class="mapholder" style="width:'.$vn_width.'spx; height:'.($vn_height + 40).'px; float: left; margin:-18px 0 0 0;">';

		$vs_element .= 		'<div class="olMapSearchControls" id="{fieldNamePrefix}Controls_{n}">';
		if ($po_request) {
			$vs_element .= 		'<div class="olMapSearchBox">';
			$vs_element .=				'<input type="text" class="olMapSearchText" name="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}_search"  id="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}_search" size="30" value="" autocomplete="off" onfocus="this.value = \'\';" onkeypress="return map_geocode_'.$vs_id.'(event);"/>';
			$vs_element .= 				"<a href='#' onclick='map_geocode_{$vs_id}();'><img src='".$po_request->getThemeUrlPath()."/graphics/buttons/glass.png' border='0' width='11' height='11' alt='"._t('Search')."' class='olMapSearchBoxIcon' id='{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_search_button'/></a>";
			$vs_element .= 		'</div>';
		}
		$vs_element .= 			'<div class="olMapKmlControl" id="{fieldNamePrefix}showKmlControl_{n}">';
		$vs_element .=					'<div style="position: absolute; bottom: 0px; left: 0px;"><a href="#" class="button" id="{fieldNamePrefix}showKmlControl_{n}_button">'._t('Upload KML file').' &rsaquo;</a></div>';
		$vs_element .= 			'</div>';
		$vs_element .= 		'</div>';
		
		$vs_element .=		'<div class="olMapKMLInput" id="{fieldNamePrefix}KmlControl_{n}">';
		$vs_element .=			_t("Select KML or KMZ file").': <input type="file" name="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}"/><a href="#" class="button"  id="{fieldNamePrefix}hideKmlControl_{n}_button">'._t('Use map').' &rsaquo;</a>';
		$vs_element .=		'</div>';
		$vs_element .= 		'<div class="olMap" id="{fieldNamePrefix}map_'.$vs_id.'_{n}" style="width:'.$vn_width.'px; height:'.$vn_height.'px;"> </div>';
		$vs_element .='</div>';
		$vs_element .= "<script type='text/javascript'>
		
		
	var map_{$vs_id};
	var points_{$vs_id};
	jQuery(document).ready(function() {
		OpenLayers.Util.onImageLoadError = function(){
    		this.src = 'images/blank.png';
		};

		// Styles
		var styles_{$vs_id} = new OpenLayers.StyleMap({
			'default': new OpenLayers.Style({
				pointRadius: '{$vn_point_radius}',
				fillColor: '{$vs_fill_color}',
				strokeColor: '{$vs_stroke_color}',
				strokeWidth: '{$vn_stroke_width}',
				graphicZIndex: 1
			}),
			'select': new OpenLayers.Style({
				fillColor: '{$vs_fill_color_selected}',
				strokeColor: '{$vs_stroke_color_selected}',
				graphicZIndex: 2
			})
		});

		var map_{$vs_id}_editing_toolbar;
		
		function map_serialize_features_{$vs_id}() {
			// get all points
			var features = [];
			for(var i=0; i < points_{$vs_id}.features.length; i++) {
				var pl = [];
				var geometry_type = points_{$vs_id}.features[i].geometry.CLASS_NAME;
				var n = points_{$vs_id}.features[i].geometry.getVertices();
				for (var j=0; j<n.length; j++) {
					var np = n[j].clone();
					np.transform(map_{$vs_id}.getProjectionObject(), new OpenLayers.Projection('EPSG:4326'));
					pl.push(np.y + ',' + np.x);
				}
				if ((pl.length > 1) && (geometry_type == 'OpenLayers.Geometry.Polygon')) { pl.push(pl[0]); } // close polygons
				features.push(pl.join(';'));
			}
			
			jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val('[' + features.join(':') + ']');
		}
		
		// Set up layer for added points/paths
		points_{$vs_id} = new OpenLayers.Layer.Vector('Points', {
			styleMap: styles_{$vs_id},
			rendererOptions: {zIndexing: true},
			eventListeners: {
				'featureadded': map_serialize_features_{$vs_id}
			}
		});
		
		map_{$vs_id}_editing_toolbar = new OpenLayers.Control.EditingToolbar(points_{$vs_id});
		
		// Set up map
		map_{$vs_id} = new OpenLayers.Map({
			div: '{fieldNamePrefix}map_{$vs_id}_{n}',
			layers: [new {$vs_base_layer}],
			controls: [
				new OpenLayers.Control.Navigation({
					dragPanOptions: {
						enableKinetic: true
					}
				}),
				new OpenLayers.Control.Zoom()
			],
			center: [0,0],
			zoom: 1
		});
		
		
		{$vs_custom_tile_layer}		
		
		var map_{$vs_id}_drag_ctrl = new OpenLayers.Control.DragFeature(points_{$vs_id}, {
			onComplete: function(f) { map_serialize_features_{$vs_id}(f); }
		});
		map_{$vs_id}.addControl(map_{$vs_id}_drag_ctrl);
		map_{$vs_id}_drag_ctrl.activate();
		
		// add delete control
		var map_{$vs_id}_delete_button = new OpenLayers.Control.Button ({displayClass: 'olControlDelete', trigger: function() { 
			if (points_{$vs_id}.selectedFeatures) { 
				points_{$vs_id}.removeFeatures(points_{$vs_id}.selectedFeatures);
				map_serialize_features_{$vs_id}();
			}
		}, title: '"._t('Remove')."'});
		var map_{$vs_id}_delete_panel = new OpenLayers.Control.Panel({type: OpenLayers.Control.TYPE_BUTTON, displayClass: 'olControlDeletePanel'});
		map_{$vs_id}_delete_panel.addControls([map_{$vs_id}_delete_button]);
		map_{$vs_id}.addControl(map_{$vs_id}_delete_panel);
		map_{$vs_id}_delete_button.activate();
		
		// Grab current map coordinates from input
		var map_{$ps_id}_loc_str = '{".$pa_element_info['element_id']."}';
		var map_{$ps_id}_loc_features = map_{$ps_id}_loc_str.match(/\[([\d\,\-\.\:\;]+)\]/)
		if (map_{$ps_id}_loc_features && (map_{$ps_id}_loc_features.length > 1)) {
			map_{$ps_id}_loc_features = map_{$ps_id}_loc_features[1].split(/:/);
		} else {
			map_{$ps_id}_loc_features = [];
		}
		var features_{$vs_id} = [];
		
		var i, j, c=0;
		for(i=0; i < map_{$ps_id}_loc_features.length; i++) {
			var ptlist = map_{$ps_id}_loc_features[i].split(/;/);
			
			if (ptlist.length > 1) {
				// path
				var ptolist = [];
				for(j=0; j < ptlist.length; j++) {
					var pt = ptlist[j].split(/,/);
					ptolist.push(new OpenLayers.Geometry.Point(pt[1], pt[0]));
					
				}
				features_{$vs_id}.push(new OpenLayers.Feature.Vector(
					new OpenLayers.Geometry.LineString(ptolist).transform(new OpenLayers.Projection('EPSG:4326'),map_{$vs_id}.getProjectionObject()), {}
				));
			} else {
				// point
				var pt = ptlist[0].split(/,/);
				features_{$vs_id}.push(new OpenLayers.Feature.Vector(
					new OpenLayers.Geometry.Point(pt[1], pt[0]).transform(new OpenLayers.Projection('EPSG:4326'),map_{$vs_id}.getProjectionObject()), {}
				));
			}
			c++;
		}
	
		points_{$vs_id}.addFeatures(features_{$vs_id});
		
		var map_{$vs_id}_highlight_ctrl = new OpenLayers.Control.SelectFeature(points_{$vs_id}, {
			hover: false,
			renderIntent: 'temporary',
			multiple: true, clickout: true, toggle: true, box: true,
			eventListeners: {}
		});
		map_{$vs_id}.addControl(map_{$vs_id}_highlight_ctrl);
		map_{$vs_id}_highlight_ctrl.activate();
		
		map_{$vs_id}.addControl(map_{$vs_id}_editing_toolbar);
		map_{$vs_id}_editing_toolbar.activate();
 
		map_{$vs_id}.addLayer(points_{$vs_id});
		{$vs_layer_switcher_control}
		
		if (c > 0) {
			map_{$vs_id}.zoomToExtent(points_{$vs_id}.getDataExtent());
			if (map_{$vs_id}.zoom > 14) { map_{$vs_id}.zoomTo(14); }
		}
		
		jQuery('#{fieldNamePrefix}showKmlControl_{n}_button').click(function(event) {
			event.preventDefault();
			jQuery('#{fieldNamePrefix}Controls_{n}').hide(200, function() {
				jQuery('#{fieldNamePrefix}KmlControl_{n}').slideDown(200);
			});
		});
		
		jQuery('#{fieldNamePrefix}hideKmlControl_{n}_button').click(function(event) {
				event.preventDefault();
				jQuery(this).parent().hide(200, function() {
					jQuery('#{fieldNamePrefix}Controls_{n}').slideDown(200);
				});
			});
	});
		function map_geocode_{$vs_id}(e) {
			if (e && ((e.keyCode || e.which || e.charCode || 0) !== 13)) { return true; }
			var t = jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_search').val();
			jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_search_button').attr('src', '".$po_request->getThemeUrlPath()."/graphics/icons/indicator.gif');
			var geocoder = new google.maps.Geocoder();
			geocoder.geocode( { 'address': t}, function(results, status) {
				jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_search_button').attr('src', '".$po_request->getThemeUrlPath()."/graphics/buttons/glass.png');
				if (status == google.maps.GeocoderStatus.OK) {
					var loc = results[0]['geometry']['location'];
					var pt = new OpenLayers.LonLat(loc.lng(), loc.lat()).transform(new OpenLayers.Projection('EPSG:4326'),map_{$vs_id}.getProjectionObject());
					map_{$vs_id}.panTo(pt);
					map_{$vs_id}.zoomTo((results[0]['geometry']['location_type'] == 'APPROXIMATE') ? 10 : 14);
					points_{$vs_id}.addFeatures([new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(loc.lng(), loc.lat()).transform(new OpenLayers.Projection('EPSG:4326'),map_{$vs_id}.getProjectionObject()))]);
				}
			});
			return false;
		}
	</script>";
		$vs_element .= '<input class="coordinates mapCoordinateDisplay" type="text" name="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}" id="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}"/>';
		
		return $vs_element;
	}
	# ------------------------------------------------
}
?>