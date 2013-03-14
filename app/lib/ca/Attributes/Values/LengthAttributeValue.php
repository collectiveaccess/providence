<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/LengthAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
			'default' => ',',
			'width' => 10, 'height' => 1,
			'label' => _t('Value delimiter'),
			'validForRootOnly' => 1,
			'description' => _t('Delimiter to use between multiple values when used in a display.')
		)
	);
 
	class LengthAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			global $g_ui_locale;
 			global $g_ui_units_pref;
 			
 			if ($pa_value_array['value_decimal1'] === '') {
 				$this->ops_text_value = '';
 				return;
 			}
 			
 			switch($g_ui_units_pref) {
 				case 'metric':
 					$vo_measurement = new Zend_Measure_Length((float)$pa_value_array['value_decimal1'], 'METER', $g_ui_locale);
 					$this->ops_text_value = $vo_measurement->convertTo(Zend_Measure_Length::METER, 4);
 					break;
 				case 'english':
 					$vo_measurement = new Zend_Measure_Length((float)$pa_value_array['value_decimal1'], 'METER', $g_ui_locale);
 					$this->ops_text_value = $vo_measurement->convertTo(Zend_Measure_Length::FEET, 4);
 					break;
 				default:		// show as-is
 					$this->ops_text_value = $pa_value_array['value_longtext1'];
 					break;
 			}	
 		}
 		# ------------------------------------------------------------------
		public function getDisplayValue($pa_options=null) {
			return $this->ops_text_value;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info) {
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
 			
 			$vo_parsed_measurement = null;
 			$pa_values = array($ps_value);
 			while($vs_expression = array_shift($pa_values)) {
					// parse units of measurement
 				if (preg_match("!^([\d\.\,/ ]+)[ ]*([^\d ]+)!", $vs_expression, $va_matches)) {
 					$vs_value = trim($va_matches[1]);
 					$va_values = explode(" ", $vs_value);
 					$vs_unit_expression = strtolower(trim($va_matches[2]));
 					if ($vs_expression = trim(str_replace($va_matches[0], '', $vs_expression))) {
 						array_unshift($pa_values, $vs_expression);
 					}
 					
 					$vs_value  = 0;
 					foreach($va_values as $vs_v) {
 						$vs_value += caConvertLocaleSpecificFloat(trim($vs_v), $g_ui_locale);
					}
					
					switch($vs_unit_expression) {
						case "'":
						case "’":
						case 'ft':
						case 'ft.':
						case 'feet':
						case 'foot':
							$vs_units = Zend_Measure_Length::FEET;
							break;
						case '"':
						case "”":
						case 'in':
						case 'in.':
						case 'inch':
						case 'inches':
							$vs_units = Zend_Measure_Length::INCH;
							break;
						case 'm':
						case 'm.':
						case 'meter':
						case 'meters':
						case 'metre':
						case 'metres':
						case 'mt':
							$vs_units = Zend_Measure_Length::METER;
							break;
						case 'cm':
						case 'cm.':
						case 'centimeter':
						case 'centimeters':
						case 'centimetre':
						case 'centimetres':
							$vs_units = Zend_Measure_Length::CENTIMETER;
							break;
						case 'mm':
						case 'mm.':
						case 'millimeter':
						case 'millimeters':
						case 'millimetre':
						case 'millimetres':
							$vs_units = Zend_Measure_Length::MILLIMETER;
							break;
						case 'point':
						case 'pt':
						case 'pt.':
							$vs_units = Zend_Measure_Length::POINT;
							break;
						case 'mile':
						case 'miles':
							$vs_units = Zend_Measure_Length::MILE;
							break;
						case 'km':
						case 'k':
						case 'kilometer':
						case 'kilometers':
						case 'kilometre':
						case 'kilometres':
							$vs_units = Zend_Measure_Length::KILOMETER;
							break;
						default:	
							$this->postError(1970, _t('%1 is not a valid unit of measurement', $va_matches[2]), 'LengthAttributeValue->parseValue()');
							return false;
							break;
					}
				
					try {
						$o_tmp = new Zend_Measure_Length($vs_value, $vs_units, $g_ui_locale);
					} catch (Exception $e) {
						$this->postError(1970, _t('%1 is not a valid measurement', $pa_element_info['displayLabel']), 'LengthAttributeValue->parseValue()');
						return false;
					}
					if ($o_tmp->getValue() < 0) {
						// length can't be negative in our universe
						$this->postError(1970, _t('%1 must not be less than zero', $pa_element_info['displayLabel']), 'LengthAttributeValue->parseValue()');
						return false;
					}
					
					if ($vo_parsed_measurement) {
						$vo_parsed_measurement = $vo_parsed_measurement->add($o_tmp);
					} else {
						$vo_parsed_measurement = $o_tmp;
					}
				}
			}
 			if (!$vo_parsed_measurement) { 
 				$this->postError(1970, _t('%1 is not a valid measurement [%2]', $pa_element_info['displayLabel'], $ps_value), 'LengthAttributeValue->parseValue()');
				return false; 
 			}
 			return array(
 				'value_longtext1' => $vo_parsed_measurement->toString(4),			// parsed measurement with units
 				'value_longtext2' => $vs_units,										// units constant
 				'value_decimal1'  => $vo_parsed_measurement->convertTo('METER',6, 'en_US')	// measurement in metric (for searching)
 			);
 		}
 		# ------------------------------------------------------------------
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight'));
 			
 			return caHTMLTextInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
 				array(
 					'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
 					'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'], 
 					'value' => '{{'.$pa_element_info['element_id'].'}}',
 					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
					'class' => 'rulerBg'
 				)
 			);
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings() {
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
	}
 ?>