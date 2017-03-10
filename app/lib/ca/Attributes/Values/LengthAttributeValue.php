<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/LengthAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2017 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	define("__CA_ATTRIBUTE_VALUE_LENGTH__", 8);
 	
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 	require_once(__CA_LIB_DIR__.'/core/Zend/Measure/Length.php');	
 
 	global $_ca_attribute_settings;
 	
 	$_ca_attribute_settings['LengthAttributeValue'] = array(		// global
		'fieldWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 40,
			'width' => 5, 'height' => 1,
			'label' => _t('Width of data entry field in user interface'),
			'description' => _t('Width, in characters, of the field when displayed in a user interface.')
		),
		'fieldHeight' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 1,
			'width' => 5, 'height' => 1,
			'label' => _t('Height of data entry field in user interface'),
			'description' => _t('Height, in characters, of the field when displayed in a user interface.')
		),
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your measurements to be locale-specific. (The default is not to be.)')
		),
		'requireValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Require value'),
			'description' => _t('Check this option if you want an error to be thrown if this measurement is left blank.')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
		),
		'canBeUsedInSearchForm' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in search form'),
			'description' => _t('Check this option if this attribute value can be used in search forms. (The default is to be.)')
		),
		'canBeUsedInDisplay' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in display'),
			'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
		),
		'canMakePDF' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Allow PDF output?'),
			'description' => _t('Check this option if this metadata element can be output as a printable PDF. (The default is not to be.)')
		),
		'canMakePDFForValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Allow PDF output for individual values?'),
			'description' => _t('Check this option if individual values for this metadata element can be output as a printable PDF. (The default is not to be.)')
		),
		'displayTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Display template'),
			'validForRootOnly' => 1,
			'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
		),
		'displayDelimiter' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '; ',
			'width' => 10, 'height' => 1,
			'label' => _t('Value delimiter'),
			'validForRootOnly' => 1,
			'description' => _t('Delimiter to use between multiple values when used in a display.')
		)
	);
 
	class LengthAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		private $opn_decimal_value;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			global $g_ui_locale;
 			global $g_ui_units_pref;
 			
 			if ($pa_value_array['value_decimal1'] === '' || is_null($pa_value_array['value_decimal1'])) {
 				$this->ops_text_value = '';
 				return;
 			}

			try {
				$vo_measurement = new Zend_Measure_Length((float)$pa_value_array['value_decimal1'], 'METER', $g_ui_locale);

				$o_config = Configuration::load();
				if ($o_config->get('force_use_of_fractions_for_measurements')) {
					$vs_units = 'fractions';
				} else {
					$vs_units = $g_ui_units_pref;
				}

				switch($vs_units) {
					case 'metric':
						$this->ops_text_value = $vo_measurement->convertTo(Zend_Measure_Length::METER, 4);
						break;
					case 'english':
						$this->ops_text_value = $vo_measurement->convertTo(Zend_Measure_Length::FEET, 4);
						break;
					case 'fractions':
						$vn_in_inches = preg_replace("![^\d\.\-]+!", "", $vo_measurement->convertTo(Zend_Measure_Length::INCH, 8));
						$this->ops_text_value = caLengthToFractions($vo_measurement->convertTo(Zend_Measure_Length::INCH, 8), preg_match("!\.1[0]*$!", $vn_in_inches) ? 10 : 16);	// if decimal is 1/10 set base-10 denominator to ensure use of 1/10 glyph
						break;
					default: // show value in unit entered, but adjusted for the UI locale
						$this->ops_text_value = $vo_measurement->convertTo($pa_value_array['value_longtext2'], 4);
						break;
				}
			} catch (Exception $e) { // derp
				$this->ops_text_value = $pa_value_array['value_longtext1'];
			}

			// Trim off trailing zeros in quantity
 			$this->ops_text_value = preg_replace("!\.([1-9]*)[0]+([A-Za-z ]+)$!", ".$1$2", $this->ops_text_value);
 			$this->ops_text_value = preg_replace("!\.([A-Za-z ]+)$!", "$1", $this->ops_text_value);
 			
 			$this->opn_decimal_value = $pa_value_array['value_decimal1'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Returns value suitable for display
 		 *
 		 * @param $pa_options array Options are:
 		 *		returnAsDecimalMetric = return length in meters as decimal number
 		 *
 		 * @return mixed Values as string or decimal
 		 */
		public function getDisplayValue($pa_options=null) {
			if (caGetOption('returnAsDecimalMetric', $pa_options, false)) {
				return $this->opn_decimal_value;
			}
			return $this->ops_text_value;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			global $g_ui_locale;
 			$ps_value = caConvertFractionalNumberToDecimal(trim($ps_value), $g_ui_locale);
 			
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('requireValue'));
 			if (!$va_settings['requireValue'] && !$ps_value) {
 				return array(
					'value_longtext1' => '',			// parsed measurement with units
					'value_longtext2' => '',										// units constant
					'value_decimal1'  => ''	// measurement in metric (for searching)
				);
 			}

			try {
				$vo_parsed_measurement = caParseLengthDimension($ps_value);
			} catch (Exception $e) {
				$this->postError(1970, _t('%1 is not a valid measurement', $pa_element_info['displayLabel']), 'WeightAttributeValue->parseValue()');
				return false;
			}

 			return array(
 				'value_longtext1' => $vo_parsed_measurement->toString(4),					// parsed measurement with units
 				'value_longtext2' => $vo_parsed_measurement->getType(),						// units constant
 				'value_decimal1'  => $vo_parsed_measurement->convertTo('METER',6, 'en_US')	// measurement in metric (for searching)
 			);
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight'));
 			$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : 'rulerBg');
 			
 			return caHTMLTextInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
 				array(
 					'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
 					'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'], 
 					'value' => '{{'.$pa_element_info['element_id'].'}}',
 					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
					'class' => $vs_class
 				)
 			);
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings($pa_element_info=null) {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['LengthAttributeValue'];
 		}
 		# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return 'value_decimal1';
		}
 		# ------------------------------------------------------------------
		/**
		 * Returns constant for length attribute value
		 * 
		 * @return int Attribute value type code
		 */
		public function getType() {
			return __CA_ATTRIBUTE_VALUE_LENGTH__;
		}
 		# ------------------------------------------------------------------
	}
