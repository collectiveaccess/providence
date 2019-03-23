<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/LengthAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2019 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
require_once(__CA_LIB_DIR__.'/Zend/Measure/Length.php');	

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
    private $config;
    # ------------------------------------------------------------------
    public function __construct($pa_value_array=null) {
        $this->config = Configuration::load(__CA_APP_DIR__."/conf/dimensions.conf");
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

        $this->ops_text_value = $this->_getValueAsText($pa_value_array, ['precision' => 4]);			

        // Trim off trailing zeros in quantity
        $this->ops_text_value = preg_replace("!\.([1-9]*)[0]+([A-Za-z ]+)$!", ".$1$2", $this->ops_text_value);
        $this->ops_text_value = preg_replace("!\.([A-Za-z ]+)$!", "$1", $this->ops_text_value);
        
        $this->opn_decimal_value = $pa_value_array['value_decimal1'];
    }
    # ------------------------------------------------------------------
    /**
     * 
     *
     * @param $pa_options array Options are:
     *		units = force units used for display. Values are: metric, english, as_entered. [Default is to use units system of as entered value]
     *
     * @return string
     */
    public function _getValueAsText($pa_value_array, $pa_options=null) {
        global $g_ui_locale;
    
        try {
            $vo_measurement = new Zend_Measure_Length((float)$pa_value_array['value_decimal1'], 'METER', $g_ui_locale);

            $o_config = Configuration::load();
            $vs_units = caGetOption('unit', $pa_options, null);
            
            $vs_value = '';
            $vn_precision = caGetOption('precision', $pa_options, null);
            
            if (!is_array($unicode_fracs = $this->config->get('use_unicode_fraction_glyphs_for'))) { $unicode_fracs = []; }
            if (!is_array($fracs = $this->config->get('display_fractions_for'))) { $fracs = []; }
            $vn_maximum_denominator = array_reduce($fracs, function($acc, $v) { 
                $t = explode("/", $v); return ((int)$t[1] > $acc) ? (int)$t[1] : $acc; 
            }, 0);
            
            if (!in_array($vs_units, ['metric', 'english','as_entered'])) {
                $vs_as_entered_units = caParseLengthDimension($pa_value_array['value_longtext1'])->getType();
                $vs_units = 'as_entered'; //(in_array($vs_as_entered_units, [Zend_Measure_Length::INCH, Zend_Measure_Length::FEET, Zend_Measure_Length::MILE])) ? 'english' : 'metric';
            }
            
            switch($vs_units) {
                default:
                case 'metric':
                    $vs_value_in_cm = $vo_measurement->convertTo(Zend_Measure_Length::CENTIMETER, 15);
                    $vn_value_in_cm = (float)preg_replace("![^0-9\.\,]+!", "", $vs_value_in_cm);
                    
                    $vn_mm_threshold = $this->config->get('use_millimeters_for_display_up_to');
                    $vn_cm_threshold = $this->config->get('use_centimeters_for_display_up_to');
                    $vn_m_threshold = $this->config->get('use_meters_for_display_up_to');
                    
                    $vs_convert_to_units = Zend_Measure_Length::MILLIMETER;
                    if (($vn_mm_threshold > 0) && ($vn_value_in_cm > $vn_mm_threshold)) {
                        $vs_convert_to_units = Zend_Measure_Length::CENTIMETER;
                    }
                    if (($vn_cm_threshold > 0) && ($vn_value_in_cm > $vn_cm_threshold)) {
                        $vs_convert_to_units = Zend_Measure_Length::METER;
                    }
                    if (($vn_m_threshold > 0) && ($vn_value_in_cm > $vn_m_threshold)) {
                        $vs_convert_to_units = Zend_Measure_Length::KILOMETER;
                    }
                    
                    if (is_null($vn_precision)) {
                        $vn_precision = $this->config->get(strtolower($vs_convert_to_units).'_decimal_precision');
                    }
                    $vs_value = $vo_measurement->convertTo($vs_convert_to_units, $vn_precision);
                    break;
                case 'english':
                    $vs_value_in_inches = $vo_measurement->convertTo(Zend_Measure_Length::INCH, 15);
                    $vn_value_in_inches = (float)preg_replace("![^0-9\.\,]+!", "", $vs_value_in_inches);
                    
                    $vn_inch_threshold = $this->config->get('use_inches_for_display_up_to');
                    $vn_feet_threshold = $this->config->get('use_feet_for_display_up_to');
                    
                    $vs_convert_to_units = Zend_Measure_Length::INCH;
                    if (($vn_inch_threshold > 0) && ($vn_value_in_inches > $vn_inch_threshold)) {
                        $vs_convert_to_units = Zend_Measure_Length::FEET;
                    }
                    if (($vn_feet_threshold > 0) && ($vn_value_in_inches > $vn_feet_threshold)) {
                        $vs_convert_to_units = Zend_Measure_Length::MILE;
                    }
                    
                    if (is_null($vn_precision)) {
                        $vn_precision = $this->config->get(strtolower($vs_convert_to_units).'_decimal_precision');
                    }
                    
                    $vs_value = $vo_measurement->convertTo($vs_convert_to_units, $vn_precision);
                    list($vn_whole, $vn_decimal) = explode(".",$vs_value);
                    if($vn_decimal > 0) {
                        switch($vs_convert_to_units) {
                            case Zend_Measure_Length::FEET:
                                $vn_inches = (float)(".{$vn_decimal}") * 12;
                                $vo_feet = new Zend_Measure_Length($vn_whole, $vs_convert_to_units, $g_ui_locale);                                    
                                $vo_inches = new Zend_Measure_Length($vn_inches, Zend_Measure_Length::INCH, $g_ui_locale);
                                
                                $vs_value = $vo_feet->convertTo($vs_convert_to_units, $vn_precision);
                                if(in_array($vs_convert_to_units, $this->config->get('add_period_after_units'))) { $vs_value .= '.'; }
                                
                                if ($vn_inches > 0) {
                                    $vs_value .= " ".caLengthToFractions($vn_inches, $vn_maximum_denominator, true, ['precision' => $this->config->get('inch_decimal_precision'), 'allowFractionsFor' => $fracs, 'useUnicodeFractionGlyphsFor' => $unicode_fracs]);
                                    if(in_array(Zend_Measure_Length::INCH, $this->config->get('add_period_after_units'))) { $vs_value .= '.'; }
                                }
                                return trim($vs_value);
                                break;
                            case Zend_Measure_Length::MILE:
                                $vn_feet = (float)(".{$vn_decimal}") * 5280;
                                list($vn_whole_feet, $vn_decimal_inches) = explode(".", $vn_feet);
                                $vn_inches = (float)(".{$vn_decimal_inches}") * 12;
                                
                                $vo_miles = new Zend_Measure_Length($vn_whole, $vs_convert_to_units, $g_ui_locale);    
                                $vo_feet = new Zend_Measure_Length($vn_whole_feet, Zend_Measure_Length::FEET, $g_ui_locale);
                                $vo_inches = new Zend_Measure_Length($vn_inches, Zend_Measure_Length::INCH, $g_ui_locale);
                                
                                
                                if (is_null($vn_precision)) {
                                    $vn_precision = $this->config->get(strtolower($vs_convert_to_units).'_decimal_precision');
                                }
                                $vs_value = $vo_miles->convertTo($vs_convert_to_units, $vn_precision);
                                if(in_array($vs_convert_to_units, $this->config->get('add_period_after_units'))) { $vs_value .= '.'; }
                                
                                if ($vn_whole_feet > 0) {
                                    $vs_value .= " ".$vo_feet->convertTo(Zend_Measure_Length::FEET, $this->config->get('feet_decimal_precision'));
                                    if(in_array(Zend_Measure_Length::INCH, $this->config->get('add_period_after_units'))) { $vs_value .= '.'; }
                                }
                                if ($vn_inches > 0) {
                                    $vs_value .= " ".caLengthToFractions($vn_inches, $vn_maximum_denominator, true, ['units' => Zend_Measure_Length::INCH, 'allowFractionsFor' => $fracs, 'useUnicodeFractionGlyphsFor' => $unicode_fracs]);
                                    if(in_array(Zend_Measure_Length::INCH, $this->config->get('add_period_after_units'))) { $vs_value .= '.'; }
                                }
                                return trim($vs_value);
                                break;
                            case Zend_Measure_Length::INCH:
                            default:
                                $vs_value = caLengthToFractions($vs_value_in_inches, $vn_maximum_denominator, true, ['units' => $vs_convert_to_units, 'allowFractionsFor' => $fracs, 'useUnicodeFractionGlyphsFor' => $unicode_fracs]);
                                break;
                        }
                    } 
                    break;
                case 'as_entered': 
                    // as-entered
                    return $pa_value_array['value_longtext1'];
                    break;
            }
    
            if(in_array($pa_value_array['value_longtext2'], $this->config->get('add_period_after_units'))) {
                $vs_value .= '.';
            }
            return $vs_value;
        } catch (Exception $e) { 
            return $pa_value_array['value_longtext1'];
        }
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
        
        $ps_value = preg_replace("![^\d\.\,A-Za-z\"\'\"’” \/]+!", " ", $ps_value);
        $ps_value_proc = caConvertFractionalNumberToDecimal(trim($ps_value), $g_ui_locale);
        
        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('requireValue'));
        if (!$va_settings['requireValue'] && !$ps_value_proc) {
            return array(
                'value_longtext1' => '',			// parsed measurement with units
                'value_longtext2' => '',										// units constant
                'value_decimal1'  => ''	// measurement in metric (for searching)
            );
        }

        try {
            $vo_parsed_measurement = caParseLengthDimension($ps_value_proc);
        } catch (Exception $e) {
            $this->postError(1970, _t('%1 is not a valid measurement', $pa_element_info['displayLabel']), 'WeightAttributeValue->parseValue()');
            return false;
        }

        return array(
            'value_longtext1' => $ps_value,					                            // parsed measurement with units
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
