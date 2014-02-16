<?php
/* ----------------------------------------------------------------------
 * dateJoinerRefinery.php : 
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_LIB_DIR__.'/ca/Import/BaseRefinery.php');
 	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 
	class dateJoinerRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'dateJoiner';
			$this->ops_title = _t('Date joiner');
			$this->ops_description = _t('Joins data with partial date values into a single valid date expression for import.');
			
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => true,
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options=null) {
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			
			$pm_value = $pa_source_data[$pa_item['source']];	// not actually used
			
			$va_item_dest = explode(".", $pa_item['destination']);
			$vs_item_terminal = array_pop($va_item_dest);
			$vs_group_terminal = array_pop($va_item_dest);
			
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			switch($vs_mode = $pa_item['settings']['dateJoiner_mode']) {
				default:
				case 'range':
					$vs_date_expression = $pa_item['settings']['dateJoiner_expression'];
					$vs_date_start = $pa_item['settings']['dateJoiner_start'];
					$vs_date_end = $pa_item['settings']['dateJoiner_end'];
			
					if ($vs_date_expression && ($vs_exp = BaseRefinery::parsePlaceholder($vs_date_expression, $pa_source_data, $pa_item))) {
						if ($o_tep->parse($vs_exp)) {
							return $o_tep->getText();
						} else {
							if ($o_log) { $o_log->logWarn(_t('[dateJoinerRefinery] Could not parse date expression %1 assembled from range', $vs_exp)); }
						}
					}
			
					$va_date = array();
					if ($vs_date_start = BaseRefinery::parsePlaceholder($vs_date_start, $pa_source_data, $pa_item)) { $va_date[] = $vs_date_start; }
					if ($vs_date_end = BaseRefinery::parsePlaceholder($vs_date_end, $pa_source_data, $pa_item)) { $va_date[] = $vs_date_end; }
					$vs_date_expression = join(" - ", $va_date);
					if ($vs_date_expression && ($vs_exp = BaseRefinery::parsePlaceholder($vs_date_expression, $pa_source_data, $pa_item))) {
						if ($o_tep->parse($vs_exp)) {
							return $o_tep->getText();
						} else {
							if ($o_log) { $o_log->logWarn(_t('[dateJoinerRefinery] Could not parse date expression %1 assembled from range', $vs_exp)); }
						}
					}
					break;
					
				case 'multiColumnDate':
					$va_month_list = $o_tep->getMonthList();
					
					$va_date = array();
					if ($vs_date_month = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_month'], $pa_source_data, $pa_item))) { 
						if (($vn_m = array_search($vs_date_month, $va_month_list)) !== false) {
							$vs_date_month = ($vn_m + 1);
						}
						$va_date[] = $vs_date_month; 
					}
					if ($vs_date_day = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_day'], $pa_source_data, $pa_item))) { $va_date[] = $vs_date_day; }
					if ($vs_date_year = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_year'], $pa_source_data, $pa_item))) { $va_date[] = $vs_date_year; }
		
					if(sizeof($va_date)) {							// TODO: this is assuming US-style dates for now
						if ($o_tep->parse(join("/", $va_date))) {
							return $o_tep->getText();
						} else {
							if ($o_log) { $o_log->logWarn(_t('[dateJoinerRefinery] Could not parse date expression %1 assembled from multiColumnDate', join("/", $va_date))); }
						}
					}
					break;
				case 'multiColumnRange':
					$va_dates = array();
					
					$va_month_list = $o_tep->getMonthList();
					
					// Process start date
					$va_date = array();
					if ($vs_date_month = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_startMonth'], $pa_source_data, $pa_item))) { 
						if (($vn_m = array_search($vs_date_month, $va_month_list)) !== false) {
							$vs_date_month = ($vn_m + 1);
						}
						$va_date[] = $vs_date_month; 
					}
					if ($vs_date_day = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_startDay'], $pa_source_data, $pa_item))) { $va_date[] = $vs_date_day; }
					if ($vs_date_year = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_startYear'], $pa_source_data, $pa_item))) { $va_date[] = $vs_date_year; }
		
					if(sizeof($va_date)) {
						if ($o_tep->parse(join("/", $va_date))) {	// TODO: this is assuming US-style dates for now
							$va_dates[] = $o_tep->getText();
						} else {
							if ($o_log) { $o_log->logWarn(_t('[dateJoinerRefinery] Could not parse date expression %1 assembled from multiColumnRange', join("/", $va_date))); }
						}
					}
					
					// Process end date
					$va_date = array();
					if ($vs_date_month = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_endMonth'], $pa_source_data, $pa_item))) { 
						if (($vn_m = array_search($vs_date_month, $va_month_list)) !== false) {
							$vs_date_month = ($vn_m + 1);
						}
						$va_date[] = $vs_date_month; 
					}
					if ($vs_date_day = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_endDay'], $pa_source_data, $pa_item))) { $va_date[] = $vs_date_day; }
					if ($vs_date_year = trim(BaseRefinery::parsePlaceholder($pa_item['settings']['dateJoiner_endYear'], $pa_source_data, $pa_item))) { $va_date[] = $vs_date_year; }
		
					if(sizeof($va_date)) {
						if ($o_tep->parse(join("/", $va_date))) {	// TODO: this is assuming US-style dates for now
							$va_dates[] = $o_tep->getText();
						} else {
							if ($o_log) { $o_log->logWarn(_t('[dateJoinerRefinery] Could not parse date expression %1 assembled from multiColumnRange', join("/", $va_date))); }
						}
					}
					
					if (sizeof($va_dates) > 0) {
						return join(" - ", $va_dates);
					}
					break;
			}
			
			return null;
		}
		# -------------------------------------------------------	
		/**
		 * dateJoiner returns a single transformed date value
		 *
		 * @return bool Always false
		 */
		public function returnsMultipleValues() {
			return false;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['dateJoiner'] = array(	
	 		'dateJoiner_mode' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'options' => array(
					_t('Two-column range') => 'range',
					_t('Multi-column range') => 'multiColumnRange',
					_t('Multi-column date') => 'multiColumnDate'
				),
				'label' => _t('Join mode'),
				'description' => _t('Determines how dateJoiner joins date values together. Two-column range is the default if mode is not specified.')
			),	
			'dateJoiner_expression' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date expression'),
				'description' => _t('Date expression (For Two-column range)')
			),
			'dateJoiner_start' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date start'),
				'description' => _t('Maps the date from the data source that is the beginning of the conjoined date range. (For Two-column range).')
			),
			'dateJoiner_end' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date end'),
				'description' => _t('Maps the date from the data source that is the end of the conjoined date range. (For Two-column range)')
			),
			'dateJoiner_startDay' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date start day'),
				'description' => _t('Maps the day value for the start date from the data source. (For Multi-column range)')
			),
			'dateJoiner_startMonth' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date start month'),
				'description' => _t('Maps the month value for the start date from the data source. (For Multi-column range)')
			),
			'dateJoiner_startYear' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date start year'),
				'description' => _t('Maps the year value for the start date from the data source. (For Multi-column range)')
			),
			'dateJoiner_endDay' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date end day'),
				'description' => _t('Maps the day value for the end date from the data source. (For Multi-column range)')
			),
			'dateJoiner_endMonth' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date end month'),
				'description' => _t('Maps the month value for the end date from the data source. (For Multi-column range)')
			),
			'dateJoiner_endYear' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date end year'),
				'description' => _t('Maps the year value for the end date from the data source. (For Multi-column range)')
			),
			'dateJoiner_day' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date day'),
				'description' => _t('Maps the day value for the date from the data source. (For Multi-column date)')
			),
			'dateJoiner_month' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date month'),
				'description' => _t('Maps the month value for the date from the data source. (For Multi-column date)')
			),
			'dateJoiner_year' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date year'),
				'description' => _t('Maps the year value for the date from the data source. (For Multi-column date)')
			)
		);
?>