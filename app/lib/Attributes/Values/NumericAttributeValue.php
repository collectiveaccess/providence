<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/NumericAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_NUMERIC__", 12);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;
$_ca_attribute_settings['NumericAttributeValue'] = array(		// global
	'minChars' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => 0,
		'label' => _t('Minimum number of characters'),
		'description' => _t('The minimum number of characters to allow. Input shorter than required will be rejected.')
	),
	'maxChars' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => 10,
		'label' => _t('Maximum number of characters'),
		'description' => _t('The maximum number of characters to allow. Input longer than required will be rejected.')
	),
	'minValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => '',
		'label' => _t('Minimum value'),
		'description' => _t('The minimum numeric value to allow. Values smaller than required will be rejected. Leave blank to indicate no lower limit.')
	),
	'maxValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => '',
		'label' => _t('Maximum value'),
		'description' => _t('The maximum numeric value to allow. Values larger than required will be rejected. Leave blank to indicate no upper limit.')
	),
	'regex' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 60, 'height' => 1,
		'default' => '',
		'label' => _t('Regular expression to validate input with'),
		'description' => _t('A Perl-format regular expression with which to validate the input. Input not matching the expression will be rejected. Do not include the leading and trailling delimiter characters (typically "/") in your expression. Leave blank if you don\'t want to use regular expression-based validation.')
	),
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
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Does not use locale setting'),
		'description' => _t('Check this option if you don\'t want your numeric values to be locale-specific. (The default is to be.)')
	),
	'singleValuePerLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow single value per locale'),
		'description' => _t('Check this option to restrict entry to a single value per-locale.')
	),
	'allowDuplicateValues' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow duplicate values?'),
		'description' => _t('Check this option if you want to allow duplicate values to be set when element is not in a container and is repeating.')
	),
	'raiseErrorOnDuplicateValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Show error message for duplicate values?'),
		'description' => _t('Check this option to show an error message when value is duplicate and <em>allow duplicate values</em> is not set.')
	),
	'canBeUsedInSort' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be used for sorting'),
		'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
	),
	'mustNotBeBlank' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Must not be blank'),
		'description' => _t('Check this option if this attribute value must be set to some value - it must not be blank in other words. (The default is not to be.)')
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

class NumericAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	private $ops_text_value;
	private $opn_numeric_value;
	# ------------------------------------------------------------------
	public function __construct($pa_value_array=null) {
		parent::__construct($pa_value_array);
	}
	# ------------------------------------------------------------------
	public function loadTypeSpecificValueFromRow($pa_value_array) {
		global $g_ui_locale;
		$this->ops_text_value = (strlen($pa_value_array['value_longtext1']) && is_numeric($pa_value_array['value_longtext1'])) ? Zend_Locale_Format::toNumber((float)$pa_value_array['value_longtext1'], ['locale' => $g_ui_locale ?? (defined('__CA_DEFAULT_LOCALE__') ? __CA_DEFAULT_LOCALE__ : 'en_US')]) : null;
		$this->opn_numeric_value = $pa_value_array['value_decimal1'];
	}
	# ------------------------------------------------------------------
	public function getDisplayValue($pa_options=null) {
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
		global $g_ui_locale;
		
		$ps_value = trim($ps_value);
		$va_settings = $this->getSettingValuesFromElementArray(
			$pa_element_info, 
			['minChars', 'maxChars', 'minValue', 'maxValue', 'regex', 'mustNotBeBlank']
		);
		$vn_strlen = mb_strlen($ps_value);
		if ($va_settings['minChars'] && ($vn_strlen < $va_settings['minChars'])) {
			// Length too short
			$vs_err_str = ($va_settings['minChars'] == 1) ? _t('%1 must be at least 1 character long', $pa_element_info['displayLabel']) : _t('%1 must be at least %2 characters long', $pa_element_info['displayLabel'], $va_settings['minChars']); 
			$this->postError(1970, $vs_err_str, 'NumericAttributeValue->parseValue()');
			return false;
		}
		if ($va_settings['maxChars'] && ($vn_strlen > $va_settings['maxChars'])) {
			// Length too long
			$vs_err_str = ($va_settings['minChars'] == 1) ? _t('%1 must be no more than 1 character long', $pa_element_info['displayLabel']) : _t('%1 must be no more than %2 characters long', $pa_element_info['displayLabel'], $va_settings['maxChars']); 
			$this->postError(1970, $vs_err_str, 'NumericAttributeValue->parseValue()');
			return false;
		}
		
		try {
			$pn_value = strlen($ps_value) ? Zend_Locale_Format::getNumber($ps_value, ['locale' => $g_ui_locale]) : null;
		} catch(Exception $e) {			
			if($ps_value && !(is_numeric($ps_value))){
				// This is not an number, it contains symbols other than [0-9]
				$this->postError(1970, _t('%1 is not a numeric value', $pa_element_info['displayLabel']), 'NumericAttributeValue->parseValue()');
				return false;
			}
			$pn_value = (float)$ps_value;
		}
		
		if (strlen($pn_value) && !is_numeric($pn_value)) {
			// Value is not numeric
			$vs_err_str = _t('%1 must be a number', $pa_element_info['displayLabel']); 
			$this->postError(1970, $vs_err_str, 'NumericAttributeValue->parseValue()');
			return false;
		}
		
		if (strlen($pn_value) == 0) {
			if ((bool)$va_settings['mustNotBeBlank']) {
				$this->postError(1970, _t('%1 must not be empty', $pa_element_info['displayLabel']), 'NumericAttributeValue->parseValue()');
				return false;
			} else {
				return [
					'value_longtext1' => null,
					'value_decimal1' => null
				];
			}
		}
		
		if (strlen($va_settings['minValue']) && ($pn_value < $va_settings['minValue'])) {
			// Value is too small
			$vs_err_str = _t('%1 must be at least %2', $pa_element_info['displayLabel'], $va_settings['minValue']); 
			$this->postError(1970, $vs_err_str, 'NumericAttributeValue->parseValue()');
			return false;
		}
		if (strlen($va_settings['maxValue']) && ($pn_value > $va_settings['maxValue'])) {
			// Value is too large
			$vs_err_str = _t('%1 must be no more than %2', $pa_element_info['displayLabel'], $va_settings['maxValue']); 
			$this->postError(1970, $vs_err_str, 'NumericAttributeValue->parseValue()');
			return false;
		}
		
		if ($va_settings['regex'] && !preg_match("!".$va_settings['regex']."!", $ps_value)) {
			// Regex failed
			$this->postError(1970, _t('%1 does not conform to required format', $pa_element_info['displayLabel']), 'NumericAttributeValue->parseValue()');
			return false;
		}
		
		return [
			'value_longtext1' => (string)(float)$pn_value,
			'value_decimal1' => (float)$pn_value
		];
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $pa_element_info An array of information about the metadata element being edited
	 * @param array $pa_options array Options include:
	 *			class = the CSS class to apply to all visible form elements [Default=null]
	 *			width = the width of the form element [Default=field width defined in metadata element definition]
	 *			height = the height of the form element [Default=field height defined in metadata element definition]
	 *
	 * @return string
	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'minChars', 'maxChars'));
		$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : '');
		
		return caHTMLTextInput(
			'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
			[
				'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
				'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'], 
				'value' => '{{'.$pa_element_info['element_id'].'}}', 
				'maxlength' => $va_settings['maxChars'],
				'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
				'class' => $vs_class
			]
		);
	}
	# ------------------------------------------------------------------
	public function getAvailableSettings($pa_element_info=null) {
		global $_ca_attribute_settings;
		
		return $_ca_attribute_settings['NumericAttributeValue'];
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
	 * Returns name of field in ca_attribute_values to use for query operations
	 *
	 * @return string Name of sort field
	 */
	public function queryFields() : ?array {
		return ['value_decimal1'];
	}
	# ------------------------------------------------------------------
	/**
	 * Returns constant for numeric attribute value
	 * 
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_NUMERIC__;
	}
	# ------------------------------------------------------------------
}
