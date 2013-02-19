<?php
/* ----------------------------------------------------------------------
 * measurementsSplitterRefinery.php : 
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
 
	class measurementsSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'measurementsSplitter';
			$this->ops_title = _t('Measurement splitter');
			$this->ops_description = _t('Splits measurements');
			
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
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			$pm_value = preg_replace("![^\d\.A-Za-z\"\"’”]+!", "", $pm_value);
			
			$vs_units = $pa_item['settings']['measurementsSplitter_units'];
			
			if ($vs_delimiter = $pa_item['settings']['measurementsSplitter_delimiter']) {
				$va_measurements = explode($vs_delimiter, $pm_value);
			} else {
				$vs_delimiter = '';
				$va_measurements = array($pm_value);
			}
			
			$va_elements = $pa_item['settings']['measurementsSplitter_elements'];
		
			$va_val = array();
			foreach($va_elements as $vn_i => $va_element) {
				if (!is_array($va_element)) { continue; }
				if (!sizeof($va_measurements)) { break; }
				
				$vs_measurement = array_shift($va_measurements);
				if (!preg_match("![^\d\.{$vs_delimiter} ]+!", $vs_measurement)) {
					$vs_measurement .= " {$vs_units}";
				}
				$vs_measurement = preg_replace("![ ]+!", " ", $vs_measurement);
			
				// Set label
				$va_val[$va_element['quantityElement']] = $vs_measurement;
				if (isset($va_element['typeElement']) && $va_element['typeElement']) {
					$va_val[$va_element['typeElement']] = BaseRefinery::parsePlaceholder($va_element["type"], $pa_source_data, $pa_item);
				}
			
				
			}
			return array(0 => array($vs_terminal => $va_val));
		}
		# -------------------------------------------------------	
		/**
		 * measurementsSplitter returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['measurementsSplitter'] = array(		
			'measurementsSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Delimiter')
			),
			'measurementsSplitter_units' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Units'),
				'description' => _t('Units of measurements')
			),
			'measurementsSplitter_elements' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 8,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Elements'),
				'description' => _t('Element list')
			)
		);
?>