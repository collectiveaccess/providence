<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Visualizer/WLPlugVisualizerTimelineJS.php : visualizes data as a timeline 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugVisualizer.php");
include_once(__CA_LIB_DIR__."/Plugins/Visualizer/BaseVisualizerPlugin.php");
include_once(__CA_APP_DIR__."/helpers/gisHelpers.php");

class WLPlugVisualizerTimelineJS Extends BaseVisualizerPlugIn Implements IWLPlugVisualizer {
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'TimelineJS';
		
		$this->description = _t('Visualizes data as a timeline using TimelineJS');
	}
	# ------------------------------------------------
	/**
	 * Generate timeline output in specified format
	 *
	 * @param array $pa_viz_settings Array of visualization settings taken from visualization.conf
	 * @param string $ps_format Specifies format to generate output in. Currently only 'HTML' is supported.
	 * @param array $pa_options Array of options to use when rendering output. Supported options are:
	 *		width =
	 *		height =
	 *		request = current request; required for generation of editor links
	 * @return string
	 */
	public function render($pa_viz_settings, $ps_format='HTML', $pa_options=null) {
		if (!($vo_data = $this->getData())) { return null; }
		$this->opn_num_items_rendered = 0;
		
		$po_request = (isset($pa_options['request']) && $pa_options['request']) ? $pa_options['request'] : null;
		if (!$po_request) { return ''; }
		
		list($vs_width, $vs_height) = $this->_parseDimensions(caGetOption('width', $pa_options, 500), caGetOption('height', $pa_options, 500));
		
		// Calculate how many items will be rendered on the timeline
		// from the entire data set
		$qr_res = $this->getData();
		while($qr_res->nextHit()) {
			foreach($pa_viz_settings['sources'] as $vs_source_name => $va_source) {
				if($qr_res->get($va_source['data'])) {
					$this->opn_num_items_rendered++;
				}
			}

			if($this->opn_num_items_rendered >= 250) { break; }
		}

		$vs_buf = $this->getLocaleJSSrc($po_request)."
	<div id='timeline-embed' style='width: {$vs_width}; height: {$vs_height};'></div>
    <script type='text/javascript'>
		jQuery(document).ready(function() {
			createStoryJS({
				type:       'timeline',
				width:      '{$vs_width}',
				height:     '{$vs_height}',
				source:     '".caNavUrl($po_request, '*', '*', '*', array('renderData' => '1', 'viz' => $pa_viz_settings['code']))."',
				embed_id:   'timeline-embed'
			});
		});
	</script>
";

		return $vs_buf;
	}
	# ------------------------------------------------
	/**
	 * Generate timeline data feed
	 *
	 * @param array $pa_viz_settings Array of visualization settings taken from visualization.conf
	 * @param array $pa_options Array of options to use when rendering output. Supported options are:
	 *		request - RequestHTTP object
	 * @return string
	 */
	public function getDataForVisualization($pa_viz_settings, $pa_options=null) {

		// title slide
		$va_data = [
			'title' => [
				'text' => [
					'headline' => isset($pa_viz_settings['display']['headline']) ? $pa_viz_settings['display']['headline'] : '',
					'text' => isset($pa_viz_settings['display']['introduction']) ? $pa_viz_settings['display']['introduction'] : '',
				],
				'media' => [
					'url' => '',
					'credit' => '',
					'caption' => ''
				]
			],
			'scale' => 'human'
		];
        
        $po_request = caGetOption('request', $pa_options, null);
		
		$qr_res = $this->getData();
		$vs_table_name = $qr_res->tableName();
		$vs_pk = $qr_res->primaryKey();

		$vn_c = 0;

		while($qr_res->nextHit()) {
			foreach($pa_viz_settings['sources'] as $vs_source_name => $va_source) {
				$vs_dates = $qr_res->get($va_source['data'], array('sortable' => true, 'returnAsArray'=> false, 'delimiter' => ';'));
				$va_dates = explode(";", $vs_dates);
	
				$va_date_list = explode("/", $va_dates[0]);
				if (!$va_date_list[0] || !$va_date_list[1]) continue; 
				$va_timeline_dates = caGetDateRangeForTimelineJS($va_date_list);
		
				$vn_row_id = $qr_res->get("{$vs_table_name}.{$vs_pk}");
				$vs_title = $qr_res->getWithTemplate($va_source['display']['title_template']);

				$va_data['events'][] = [
					'text' => [
						'headline' => $po_request ? caEditorLink($po_request, $vs_title, '', $vs_table_name, $vn_row_id) : $vs_title,
						'text' => $qr_res->getWithTemplate($va_source['display']['description_template']),
					],
					'media' => [
						'url' => $qr_res->getWithTemplate($va_source['display']['image'], array('returnURL' => true)),
						'thumbnail' => $qr_res->getWithTemplate($va_source['display']['icon'], array('returnURL' => true)),
						'credit' => $qr_res->getWithTemplate($va_source['display']['credit_template']),
						'caption' => $qr_res->getWithTemplate($va_source['display']['caption_template'])
					],
					'start_date' => $va_timeline_dates['start_date'],
					'end_date' => $va_timeline_dates['end_date'],
				];
			}

			$vn_c++;
			if ($vn_c >= 250) { break; }
		}

		return json_encode($va_data);
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
		
		
		//
		// Make sure sources actually exist
		//
		$va_sources = $pa_viz_settings['sources'];
		foreach($va_sources as $vs_source_code => $va_source_info) {
			$va_tmp = explode('.', $va_source_info['data']);
			$t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true);
			if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { unset($va_sources[$vs_source_code]); continue; } 
			if (!$t_instance->hasField($va_tmp[1]) && (!$t_instance->hasElement($va_tmp[1]))) { unset($va_sources[$vs_source_code]); }
		}
		
		$vn_c = 0;
		//
		// Only check the first 10,000 returned rows before giving up, to avoid timeouts
		//
		while($po_data->nextHit() && ($vn_c < 10000)) {
			foreach($va_sources as $vs_source_code => $va_source_info) {
				if (trim($po_data->get($va_source_info['data']))) {
					$po_data->seek($vn_cur_pos);
					return true;
				}
			}
			$vn_c++;
		}
		$po_data->seek($vn_cur_pos);
		return false;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Register any required javascript and CSS for loading
	 *
	 * @return void 
	 */
	public function registerDependencies() {
		$va_packages = array("timelineJS");
		foreach($va_packages as $vs_package) { AssetLoadManager::register($vs_package); }
		return $va_packages;
	}
	# ------------------------------------------------
	private function getLocaleJSSrc($po_request) {
		// try to include locale file
		global $g_ui_locale; $va_matches = array();
		preg_match("/^([a-z]{2,3})\_[A-Z]{2,3}$/", $g_ui_locale, $va_matches);
		if(isset($va_matches[1]) && file_exists(__CA_BASE_DIR__.'/assets/timelinejs/js/locale/'.$va_matches[1].'.js')) {
			return "<script src='".$po_request->getBaseUrlPath()."/assets/timelinejs/js/locale/".$va_matches[1].".js' type='text/javascript'></script>";
		}
		return '';
	}
	# ------------------------------------------------
}
