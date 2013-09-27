<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Visualizer/WLPlugVisualizerTimeline.php : visualizes data as a timeline 
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
    
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugVisualizer.php");
include_once(__CA_LIB_DIR__."/core/Plugins/Visualizer/BaseVisualizerPlugin.php");
include_once(__CA_APP_DIR__."/helpers/gisHelpers.php");

class WLPlugVisualizerTimeline Extends BaseVisualizerPlugIn Implements IWLPlugVisualizer {
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'Timeline';
		
		$this->description = _t('Visualizes data as a timeline');
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
	 */
	public function render($pa_viz_settings, $ps_format='HTML', $pa_options=null) {
		if (!($vo_data = $this->getData())) { return null; }
		$this->opn_num_items_rendered = 0;
		
		$po_request = (isset($pa_options['request']) && $pa_options['request']) ? $pa_options['request'] : null;
		$vn_width = (isset($pa_options['width']) && $pa_options['width']) ? $pa_options['width'] : 690;
		$vn_height = (isset($pa_options['height']) && $pa_options['height']) ? $pa_options['height'] : 300;
		
		if (!preg_match('!^[\d]+%$!', $vn_width)) {
			$vn_width = intval($vn_width)."px";
			if ($vn_width < 1) { $vn_width = 690; }
		}
		if (!preg_match('!^[\d]+%$!', $vn_height)) {
			$vn_height = intval($vn_height)."px";
			if ($vn_height < 1) { $vn_height = 300; }
		}
		
		
		$o_dm = Datamodel::load();
		
		// generate events
		$va_events = array();
		$va_sources = $pa_viz_settings['sources'];
		
		$vs_table = $vo_data->tableName();
		$vs_pk = $o_dm->getTablePrimaryKeyName($vs_table);
		
		$vs_first_date = $vn_first_date = null;
		$vs_last_date = $vn_last_date = null;
		$va_dates = array();
		while($vo_data->nextHit()) {
			foreach($va_sources as $vs_source_code => $va_source_info) {
				$vs_start = trim($vo_data->get($va_source_info['data'], array('start_as_iso8601' => true, 'dateFormat' => 'iso8601')));
				$vs_end = trim($vo_data->get($va_source_info['data'], array('end_as_iso8601' => true, 'dateFormat' => 'iso8601')));
				
				$vn_start = $vo_data->get($va_source_info['data'], array('startHistoricTimestamp' => true));
				$vn_end = $vo_data->get($va_source_info['data'], array('endHistoricTimestamp' => true));
				
				if (($vn_start < 0) || ($vn_end < 0)) { continue; }	// TODO: negative numbers mean "BC" which apparently cannot be plotted
				if ($vn_end >= 2000000) { 
					$va_iso = caGetISODates(_t("today"));
					$vs_end = $va_iso['end'];
					$va_historic = caDateToHistoricTimestamps(_t("today"));
					$vn_end = $va_historic['end'];
				}
				if (!$vs_start || !$vs_end) { continue; }
				if (($vs_start == _t('undated')) || ($vs_end == _t('undated'))) { continue; }
				
				if (is_null($vn_first_date) || ($vn_first_date > $vn_start)) { 
					$vn_first_date = $vn_start; 
					$vs_first_date = $vs_start;
				}
				if (is_null($vn_last_date) || ($vn_last_date < $vn_end)) { 
					$vn_last_date = $vn_end; 
					$vs_last_date = $vs_end;
				}
				$va_dates[] = $vs_start;
				$va_events[] = array(
					'id' => $vs_table.'_'.($vn_id = $vo_data->get("{$vs_table}.{$vs_pk}")),
					'start' => $vs_start,
					'end' => $vs_end,
					'isDuration' => ((int)$vn_start != (int)$vn_end) ? true : false,
					'title' => caProcessTemplateForIDs(strip_tags($va_source_info['display']['title_template']), $vs_table, array($vn_id)),
					'description' => caProcessTemplateForIDs($va_source_info['display']['description_template'], $vs_table, array($vn_id)),
					'link' => $po_request ? caEditorUrl($po_request, $vo_data->tableName(), $vn_id) : null,
					'image' => $va_source_info['display']['image'] ? $vo_data->get($va_source_info['display']['image'], array('returnURL' => true)) : null,
					'icon' => $va_source_info['display']['icon'] ? $vo_data->get($va_source_info['display']['icon'], array('returnURL' => true)) : null
				);
			}
		}
		
		$this->opn_num_items_rendered = sizeof($va_events);
		
		// Find median date - timeline will open there (as good a place as any, no?)
		$vs_default_date = $va_dates[floor((sizeof($va_dates) - 1)/2)];
		
		// Derive scale for timeline bands
		$vn_span = $vn_last_date - $vn_first_date;
		
		if ($vn_span > 1000) {
			// millennia
			$vs_detail_band_scale = " Timeline.DateTime.CENTURY";
			$vs_overview_band_scale = " Timeline.DateTime.MILLENNIUM";
		} elseif ($vn_span > 100) {
			// centuries
			$vs_detail_band_scale = " Timeline.DateTime.DECADE";
			$vs_overview_band_scale = " Timeline.DateTime.CENTURY";
		} elseif ($vn_span > 10) {
			// decades
			$vs_detail_band_scale = " Timeline.DateTime.YEAR";
			$vs_overview_band_scale = " Timeline.DateTime.DECADE";
		} elseif ($vn_span > 1) {
			// years
			$vs_detail_band_scale = " Timeline.DateTime.MONTH";
			$vs_overview_band_scale = " Timeline.DateTime.YEAR";
		} elseif ($vn_span > 0.1) {
			// months
			$vs_detail_band_scale = " Timeline.DateTime.DAY";
			$vs_overview_band_scale = " Timeline.DateTime.MONTH";	
		} else {
			// days
			$vs_detail_band_scale = " Timeline.DateTime.HOUR";
			$vs_overview_band_scale = " Timeline.DateTime.DAY";	
		}
		
		$va_highlight_spans = array();
		$vs_highlight_span = '';
		if (isset($pa_options['highlightSpans']) && is_array($pa_options['highlightSpans'])) {
			foreach($pa_options['highlightSpans'] as $vs_span_name => $va_span_info) {
				$va_range = caGetISODates($va_span_info['range']);
				$vs_span_color = (isset($va_span_info['color']) && $va_span_info['color']) ? $va_span_info['color'] : '#FFC080';
				$vs_start_label = (isset($va_span_info['startLabel']) && $va_span_info['startLabel']) ? $va_span_info['startLabel'] : '';
				$vs_end_label = (isset($va_span_info['endLabel']) && $va_span_info['endLabel']) ? $va_span_info['endLabel'] : '';
				$vs_span_css_class = (isset($va_span_info['class']) && $va_span_info['class']) ? $va_span_info['class'] : 't-highlight1';
				$va_highlight_spans[] = "new Timeline.SpanHighlightDecorator({
						dateTimeFormat: 'iso8601',
                        startDate:  '".$va_range['start']."',
                        endDate:    '".$va_range['end']."',
                        color:      '{$vs_span_color}', 
                        opacity:    50,
                        startLabel: '{$vs_start_label}', 
                        endLabel:   '{$vs_end_label}',
                        cssClass: '{$vs_span_css_class}'
                    })";
			}
			
			$vs_highlight_span = "caTimelineBands[0].decorators = [".join(",\n", $va_highlight_spans)."];";
		}
		
		$vs_buf = "
	<div id='caResultTimeline' style='width: {$vn_width}; height: {$vn_height}; border: 1px solid #aaa'></div>
<script type='text/javascript'>
	var caTimelineEventSource = new Timeline.DefaultEventSource();
	var caTimelineEventJson = {
		\"dateTimeFormat\": \"iso8601\", 
		\"events\" : ".json_encode($va_events)."
	}; 
	caTimelineEventSource.loadJSON(caTimelineEventJson, '');
	
	var theme = Timeline.ClassicTheme.create();
	
	var caTimelineBands = [
     Timeline.createBandInfo({
			eventPainter:   Timeline.CompactEventPainter,
			eventPainterParams: {
				iconLabelGap:     5,
				labelRightMargin: 20,

				iconWidth:        72,
				iconHeight:       72,

				stackConcurrentPreciseInstantEvents: {
					limit: 5,
					moreMessageTemplate:    '%0 More',
					icon:                   null,
					iconWidth:              72,
					iconHeight:             72
				}
			},
			eventSource:	 caTimelineEventSource,
			date:			'{$vs_default_date}',
			width:          '85%', 
			intervalUnit:  	{$vs_detail_band_scale}, 
			intervalPixels: 100,
			theme: 			theme
	}),
     Timeline.createBandInfo({
     	 eventSource:	 caTimelineEventSource,
     	 date:			'{$vs_default_date}',
         width:          '10%', 
         intervalUnit:   {$vs_overview_band_scale}, 
         intervalPixels: 200,
         layout: 		'overview',
     	 theme: 		 theme
     })
   ];
	caTimelineBands[1].syncWith = 0;
	caTimelineBands[1].highlight = true;
	
	{$vs_highlight_span}
	
	var caTimeline = Timeline.create(document.getElementById('caResultTimeline'), caTimelineBands);
</script>	
";
		
		return $vs_buf;
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