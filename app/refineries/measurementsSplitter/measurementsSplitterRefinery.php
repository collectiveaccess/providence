<?php
/* ----------------------------------------------------------------------
 * measurementsSplitterRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');

class measurementsSplitterRefinery extends BaseRefinery {
	# -------------------------------------------------------
	
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'measurementsSplitter';
		$this->ops_title = _t('Measurement splitter');
		$this->ops_description = _t('Splits strings representing physical measurements into individual quantities.');
		
		parent::__construct();
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true
	 */
	public function checkStatus() {
		return [
			'description' => $this->getDescription(),
			'errors' => [],
			'warnings' => [],
			'available' => true,
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options=null) {
		$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
		
		$va_group_dest = explode(".", $pa_group['destination']);
		$vs_terminal = array_pop($va_group_dest);
		$pm_value = $pa_source_data[$pa_item['source']];
		
		$vs_units = $pa_item['settings']['measurementsSplitter_units'];
		if (is_array($pm_value)) {
			$va_measurements = $pm_value;	// for input formats that support repeating values
		} else {
			$pm_value = preg_replace("!\([^\)]*\)!", "", $pm_value);        				// remove parentheticals
			$pm_value = preg_replace("![^\d\.,;A-Za-z\"\'\"’” \/]+!", " ", $pm_value);		// remove extranenous characters
			$va_measurements = [$pm_value];
		}
		
		$va_vals = [];	
		foreach($va_measurements as $vs_measurement) {
			$va_val = [];
			
			$va_parsed_measurements = caParseLengthExpression($vs_measurement, ['returnExtractedMeasurements' => true, 'delimiter' => $pa_item['settings']['measurementsSplitter_delimiter'], 'units' => $pa_item['settings']['measurementsSplitter_units']]);

			if(is_array($va_elements = $pa_item['settings']['measurementsSplitter_elements'])) {
				$vn_set_count = 0;
				foreach($va_elements as $vn_i => $va_element) {
					if (!is_array($va_element)) { continue; }
					if (!sizeof($va_parsed_measurements)) { break; }
					
					$va_measurement = array_shift($va_parsed_measurements);
					if(((strpos($va_measurement['source'], '/') !== false) || (preg_match("![\d\.]+[ ]*[\"'a-zA-Z\.]+[ ]+[\d\.]+[ ]*[a-zA-Z\.\"\']+!", $va_measurement['source'])))) {
						$vs_measurement = $va_measurement['source'];
						if (!preg_match("![A-Za-z\.\"']+[ ]*$!", $vs_measurement)) {  $vs_measurement .= " ".$va_measurement['units']; }
					} else {
						$vs_measurement = $va_measurement['string'];
					}
					if ($vs_measurement) {
						// Set label
						$va_val[$va_element['quantityElement']] = $vs_measurement;
						if (isset($va_element['typeElement']) && $va_element['typeElement']) {
							$va_val[$va_element['typeElement']] = BaseRefinery::parsePlaceholder($va_element["type"], $pa_source_data, $pa_item, $vn_c, ['reader' => caGetOption('reader', $pa_options, null), 'returnAsString' => true]);
						}
						$vn_set_count++;
					}
				}
		
				// Set attributes
				if (is_array($pa_item['settings']['measurementsSplitter_attributes'])) {
					$va_attr_vals = [];
					foreach($pa_item['settings']['measurementsSplitter_attributes'] as $vs_element_code => $vs_v) {
						// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
						// but we only supports non-repeating attribute values, so we join any values here and call it a day.
						$va_attr_vals[$vs_element_code] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $vn_c, ['reader' => caGetOption('reader', $pa_options, null), 'returnAsString' => true]);
					}
					$va_val = array_merge($va_val, $va_attr_vals);
				}
			}
			if ($vn_set_count > 0) { $va_vals[] = $va_val; }
		}
		return $va_vals;
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
	),
	'measurementsSplitter_attributes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attributes'),
		'description' => _t('Sets or maps metadata for the list item record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	)
);
