<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Visualizer/Calendar.php : visualizes data as an agenda
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
 * @subpackage Visualization
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Calendar visualizer plugin created by idéesculture
 */

include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugVisualizer.php");
include_once(__CA_LIB_DIR__."/core/Plugins/Visualizer/BaseVisualizerPlugin.php");

class WLPlugVisualizerCalendar Extends BaseVisualizerPlugIn Implements IWLPlugVisualizer {
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'Calendar';

		$this->description = _t('Visualizes data as a calendar with FullCalendar');
	}
	# ------------------------------------------------
	/**
	 * Generate calendar output in specified format
	 *
	 * @param array $pa_viz_settings Array of visualization settings taken from visualization.conf
	 * @param string $ps_format Specifies format to generate output in. Currently only 'HTML' is supported.
	 * @param array $pa_options Array of options to use when rendering output. Supported options are:
	 *		width =
	 *		height =
	 *		request = current request; required for generation of editor links
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
		}

		$vs_buf = "
			<div id='calendar-embed' style='width: {$vs_width}; background:white;'></div><!-- {$vs_height} -->
			<div id='eventContent' title='Event Details'>
			    <span id='eventDate'></span>
			    <div id='eventInfo'></div>
			    <p><strong><a id='eventLink' href='#' target='_blank'>"._t("Open")."</a></strong></p>
			</div>
		    <script type='text/javascript'>
				function openModalA(title, info, url, start, end) {
				    alert('modal');
				}
				function openModal(title, info, url, date) {
				    jQuery('#eventDate').html('Date: ' + date + '<br />')
					jQuery('#eventInfo').html(info);
					jQuery('#eventLink').attr('href', url);
					jQuery('#eventContent').dialog({ modal:true, title: title, width:350, stack:false });
					jQuery('#eventContent').parent('.ui-dialog').css('z-index', '50000' ); // sending modal to front as caMediaPanel has 30k for z-index

				}
				jQuery(document).ready(function() {
					jQuery('#calendar-embed').fullCalendar({
						header: {
							left: 'prev,next today',
							center: 'title',
							right: 'month,agendaWeek,agendaDay'
						},
						defaultDate: '2014-06-12',
						editable: true,
						lang: 'fr',
						events: '".caNavUrl($po_request, '*', '*', '*', array('renderData' => '1', 'viz' => $pa_viz_settings['code']))."',
						eventRender: function (event, element) {
				            element.attr('href', 'javascript:void(0);');
				            element.attr('onclick', 'openModal(\"' + event.title + '\",\"' + event.description + '\",\"' + event.url + '\",\"' + event.display_date + '\",\"' + event.end + '\");');
				            // element.attr('onclick', 'alert(\'' + event.title + '\');');
				        },
						windowResize: function(view) { // refresh height on windowResize
							jQuery('#calendar-embed').fullCalendar('option', 'height', jQuery(window).height() - $('.caMediaOverlayControls').outerHeight(true));
						}
					});
					jQuery('#calendar-embed').fullCalendar('option', 'height', jQuery(window).height() - $('.caMediaOverlayControls').outerHeight(true)); // refresh start height
					
				  });
			</script>";

		return $vs_buf;
	}
	# ------------------------------------------------
	/**
	 * Generate timeline data feed
	 *
	 * @param array $pa_viz_settings Array of visualization settings taken from visualization.conf
	 * @param array $pa_options Array of options to use when rendering output. Supported options are:
	 *		NONE
	 */
	public function getDataForVisualization($pa_viz_settings, $pa_options=null) {
		$po_request = caGetOption('request', $pa_options, null);

		$qr_res = $this->getData();
		$vs_table_name = $qr_res->tableName();
		$vs_pk = $qr_res->primaryKey();

		$va_default_colors = array("#a7797e","#dc4671","#ab22b4","#0650be","#685dcd","#6c5ace","#7f34bd","#6b95b4","#56b5bc","#41b895","#008f4c","#d09a24");
		$va_default_colors_lighter = array("#c7999e","#fc6691","#cb42d4","#2670de","#887ded","#8c7aee","#9f54dd","#8bb5d4","#76d5dc","#61d8b5","#20af6c","#f0ba44");
		$va_default_colors_darker = array("#87595e","#bc2651","#8b0294","#06309e","#483dad","#4c3aae","#5f14bd","#4b7594","#36959c","#219875","#006f2c","#b07a04");
		$va_default_colors_num = count($va_default_colors);

		$vn_c = 0;

		while($qr_res->nextHit()) {
			foreach($pa_viz_settings['sources'] as $vs_source_name => $va_source) {
				$vn_event_num = 1;
				$vs_dates = $qr_res->get($va_source['data'], array('sortable' => true, 'returnAsArray'=> false, 'delimiter' => ';'));
				$vs_display_date = $qr_res->get($va_source['data']);
				$va_dates = explode(";", $vs_dates);
				if($vs_dates !== "") {
					$va_date_list = explode("/", $va_dates[0]);
					if (!$va_date_list[0] || !$va_date_list[1]) continue;
					$va_calendar_dates = caGetDateRangeForCalendar($va_date_list);

					$vn_row_id = $qr_res->get("{$vs_table_name}.{$vs_pk}");
					$data = array(
						"start" => $va_calendar_dates['start_iso'],
						"end" => $va_calendar_dates['end_iso'],
						"display_date" => $vs_display_date,
						"url" => $po_request ? caEditorUrl($po_request, $vs_table_name, $vn_row_id) : "#",
						"description" => $qr_res->getWithTemplate($va_source['display']['description_template']),
						"title" => $qr_res->getWithTemplate($va_source['display']['title_template']),
						"color" => "darkblue", //default color
						"textColor" => "white" //default text color
					);
					$data["color"] = $va_default_colors[$vn_c % $va_default_colors_num];
					if(($va_calendar_dates["start"]["hours"] === "00") && ($va_calendar_dates["end"]["hours"] === "23")
						&& ($va_calendar_dates["start"]["minutes"] === "00") && ($va_calendar_dates["end"]["minutes"] === "59")) {
						$data["allDay"] = true;
					};
					$va_data[] = $data;
					if (isset($va_source["before"])) {
						$start_date = new DateTime($va_calendar_dates['start_iso']);
						$data = array(
							"start" => $start_date->modify($va_source["before"])->format('c'),
							"end" => $va_calendar_dates['start_iso'],
							"display_date" => $start_date->format('d-m-Y'),
							"url" => $po_request ? caEditorUrl($po_request, $vs_table_name, $vn_row_id) : "#",
							"description" => $qr_res->getWithTemplate($va_source['display_before']['description_template']),
							"title" => $qr_res->getWithTemplate($va_source['display_before']['title_template']),
							"color" => "darkblue", //default color
							"textColor" => "white", //default text color
							"allDay" => true //as it's forecast, defaulting to allday event
						);
						$data["color"] = $va_default_colors_lighter[$vn_c % $va_default_colors_num];
						$va_data[] = $data;
					}
					if (isset($va_source["after"])) {
						$start_date = new DateTime($va_calendar_dates['end_iso']);
						$data = array(
							"start" => $start_date->modify("+1 days")->setTime(0,0)->format('c'),
							"end" => $start_date->modify($va_source["after"])->format('c'),
							"display_date" => $start_date->format('d-m-Y'),
							"url" => $po_request ? caEditorUrl($po_request, $vs_table_name, $vn_row_id) : "#",
							"description" => $qr_res->getWithTemplate($va_source['display_after']['description_template']),
							"title" => $qr_res->getWithTemplate($va_source['display_after']['title_template']),
							"color" => "darkblue", //default color
							"textColor" => "white", //default text color
							"allDay" => true //as it's forecast, defaulting to allday event
						);
						$data["color"] = $va_default_colors_darker[$vn_c % $va_default_colors_num];
						$va_data[] = $data;
					}
				}
				$vn_event_num++;
			}
			$vn_c++;
			if ($vn_c > 2000) { break; }
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

		$o_dm = Datamodel::load();

		//
		// Make sure sources actually exist
		//
		$va_sources = $pa_viz_settings['sources'];
		foreach($va_sources as $vs_source_code => $va_source_info) {
			$va_tmp = explode('.', $va_source_info['data']);
			$t_instance = $o_dm->getInstanceByTableName($va_tmp[0], true);
			if (!($t_instance = $o_dm->getInstanceByTableName($va_tmp[0], true))) { unset($va_sources[$vs_source_code]); continue; }
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
		$va_packages = array("fullcalendar");
		foreach($va_packages as $vs_package) { AssetLoadManager::register($vs_package); }
		return $va_packages;
	}
	# ------------------------------------------------
}
?>