<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Visualizer/WLPlugVisualizerMap.php : visualizes data as a map 
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
include_once(__CA_LIB_DIR__."/core/Plugins/Visualizer/BaseVisualizerPlugin.php");
include_once(__CA_APP_DIR__."/helpers/gisHelpers.php");

class WLPlugVisualizerMap Extends BaseVisualizerPlugIn Implements IWLPlugVisualizer {
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'Map';
		
		$this->description = _t('Visualizes data as a map');
	}
	# ------------------------------------------------
	/**
	 * Generate maps output in specified format
	 *
	 * @param array $pa_viz_settings Array of visualization settings taken from visualization.conf
	 * @param string $ps_format Specifies format to generate output in. Currently only 'HTML' is supported.
	 * @param array $pa_options Array of options to use when rendering output. Supported options are:
	 *		width =
	 *		height =
	 *		mapType - type of map to render; valid values are 'ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN'; if not specified 'google_maps_default_type' setting in app.conf is used; if that is not set default is 'SATELLITE'
	 *		showNavigationControls - if true, navigation controls are displayed; default is to use 'google_maps_show_navigation_controls' setting in app.conf
	 *		showScaleControls -  if true, scale controls are displayed; default is to use 'google_maps_show_scale_controls' setting in app.conf
	 *		showMapTypeControls -  if true, map type controls are displayed; default is to use 'google_maps_show_map_type_controls' setting in app.conf
	 *		minZoomLevel - Minimum zoom level to allow; leave null if you don't want to enforce a limit
	 *		maxZoomLevel - Maximum zoom level to allow; leave null if you don't want to enforce a limit
	 *		zoomLevel - Zoom map to specified level rather than fitting all markers into view; leave null if you don't want to specify a zoom level. IF this option is set minZoomLevel and maxZoomLevel will be ignored.
	 *		pathColor - 
	 *		pathWeight -
	 *		pathOpacity - 
	 *		request = current request; required for generation of editor links
	 */
	public function render($pa_viz_settings, $ps_format='HTML', $pa_options=null) {
		if (!($vo_data = $this->getData())) { return null; }
		
		$po_request = (isset($pa_options['request']) && $pa_options['request']) ? $pa_options['request'] : null;
		if (!isset($pa_options['width']) || ((int)$pa_options['width'] < 50)) { $pa_options['width'] = 500; }
		if (!isset($pa_options['height']) || ((int)$pa_options['height'] < 50)) { $pa_options['height'] = 500; }
		
		$o_map = new GeographicMap($pa_options['width'], $pa_options['height'], $pa_options['id']);
		$this->opn_num_items_rendered = 0;
		
		foreach($pa_viz_settings['sources'] as $vs_source_code => $va_source_info) {
			$va_ret = $o_map->mapFrom($vo_data, $va_source_info['data'], array('request' => $po_request, 'labelTemplate' => "<p class='vizTitle'>".$va_source_info['display']['title_template']."</div>", 'renderLabelAsLink' => true, 'contentTemplate' => "<p>".$va_source_info['display']['description_template']."</p>"));
			if (is_array($va_ret) && isset($va_ret['items'])) {
				$this->opn_num_items_rendered += (int)$va_ret['items'];
			}
		}
		return $o_map->render($ps_format, $pa_options);
	}
	# ------------------------------------------------
	/**
	 * Determines if there is any data in the data set that can be visualized by this plugin using the provided settings
	 *
	 * @param SearchResult $po_data
	 * @param array $pa_viz_settings Visualization settings
	 *
	 * @return bool True if data can be visualized
	 */
	public function canHandle($po_data, $pa_viz_settings) {
		$vn_cur_pos = $po_data->currentIndex();
		if ($vn_cur_pos < 0) { $vn_cur_pos = 0; }
		$po_data->seek(0);
		
		$va_sources = $pa_viz_settings['sources'];
		while($po_data->nextHit()) {
			foreach($va_sources as $vs_source_code => $va_source_info) {
				if (trim($po_data->get($va_source_info['data']))) {
					$po_data->seek($vn_cur_pos);
					return true;
				}
			}
		}
		$po_data->seek($vn_cur_pos);
		return false;
	}
	# ------------------------------------------------
}
?>