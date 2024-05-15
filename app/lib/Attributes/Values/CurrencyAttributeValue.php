<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/CurrencyAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_CURRENCY__", 6);
	
require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;

$_ca_attribute_settings['CurrencyAttributeValue'] = array(		// global
	'minValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => 0,
		'label' => _t('Minimum value'),
		'description' => _t('The minimum value allowed. Input less than the required value will be rejected.')
	),
	'maxValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 5, 'height' => 1,
		'default' => 0,
		'label' => _t('Maximum value'),
		'description' => _t('The maximum value allowed. Input greater than the required value will be rejected.')
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
	'dollarCurrency' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => '',
		'options' => [
			_t('System default') => '',
			_t('US Dollar (USD)') => 'USD',
			_t('Canadian Dollar (CDN)') => 'CDN',
			_t('Australian Dollar (AUD)') => 'AUD'
		],
		'width' => 40, 'height' => 1,
		'label' => _t('Use currency for "$"'),
		'description' => _t('Currency indicated for values using the "$" character.')
	),
	'doesNotTakeLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Does not use locale setting'),
		'description' => _t('Check this option if you don\'t want your currency values to be locale-specific. (The default is to not be.)')
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
	'mustNotBeBlank' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Must not be blank'),
		'description' => _t('Check this option if this attribute value must be set to some value - it must not be blank in other words. (The default is not to be.)')
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

class CurrencyAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	private $ops_currency_specifier;
	private $opn_value;
	
	private $opb_display_currency_conversion = false;
	# ------------------------------------------------------------------
	public function __construct($pa_value_array=null) {
		parent::__construct($pa_value_array);
	}
	# ------------------------------------------------------------------
	public function loadTypeSpecificValueFromRow($pa_value_array) {
		$this->ops_currency_specifier = $pa_value_array['value_longtext1'];
		$this->opn_value = $pa_value_array['value_decimal1'];
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getDisplayValue($pa_options=null) {
		global $_locale;
		
		if (caGetOption('returnAsDecimalWithCurrencySpecifier', $pa_options, false)) {
			if (!$this->ops_currency_specifier) { return null; }
			return caGetCurrencySymbol($this->ops_currency_specifier, $this->getElementID()).' '.$this->opn_value;
		}
		
		$o_locale = $_locale ? $_locale : new Zend_Locale(__CA_DEFAULT_LOCALE__);
		
		if (!$this->ops_currency_specifier) { return null; }
		$vs_format = Zend_Locale_Data::getContent($o_locale, 'currencynumber');

		// this returns a string like '50,00 ¤' for locale de_DE
		$vs_decimal_with_placeholder = Zend_Locale_Format::toNumber($this->opn_value, array('locale' => $o_locale, 'number_format' => $vs_format, 'precision' => 2));

		// if the currency placeholder is the first character, for instance in en_US locale ($10), insert a space.
		// we do this because we don't print "$10" (which is expected in the Zend locale rules) but "USD 10" ... and that looks nicer with an additional space.
		// we also replace the weird multibyte nonsense Zend uses as placeholder with something more reasonable so that
		// whatever we output here isn't rejected if thrown into parseValue() again
		if(substr($vs_decimal_with_placeholder,0,2)=="¤") { // '¤' has length 2
			$vs_decimal_with_placeholder = preg_replace("!¤[^\d]*!u", '% ', $vs_decimal_with_placeholder);
		} elseif(substr($vs_decimal_with_placeholder, -2)=="¤") { // placeholder at the end
			$vs_decimal_with_placeholder = preg_replace("![^\d\,\.]!", "", $vs_decimal_with_placeholder)." %";
		}

		// insert currency which is not locale-dependent in our case
		$vs_val = str_replace('%', caGetCurrencySymbol($this->ops_currency_specifier, $this->getElementID()), $vs_decimal_with_placeholder);
		if (($vs_to_currency = caGetOption('displayCurrencyConversion', $pa_options, false)) && ($this->ops_currency_specifier != $vs_to_currency)) {
			$vs_val .= " ("._t("~%1", caConvertCurrencyValue(caGetCurrencySymbol($this->ops_currency_specifier, $this->getElementID()).' '.$this->opn_value, $vs_to_currency)).")";
		}
		return $vs_val;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
		global $_locale;
		// If the locale is valid, locale is set
		$o_locale = $_locale ? $_locale : new Zend_Locale(__CA_DEFAULT_LOCALE__);
		
		$o_config = Configuration::load();
		
		$ps_value = trim($ps_value);
		$va_settings = $this->getSettingValuesFromElementArray(
			$pa_element_info, 
			['minValue', 'maxValue', 'mustNotBeBlank', 'dollarCurrency']
		);
		
		if (strlen($ps_value) == 0) {
			if ((bool)$va_settings['mustNotBeBlank']) {
				$this->postError(1970, _t('%1 must not be empty', $pa_element_info['displayLabel']), 'CurrencyAttributeValue->parseValue()');
				return false;
			}
			return [
				'value_longtext1' => '',
				'value_decimal1' => null
			];
		}
		
		if (is_array($va_parsed_value = caParseCurrencyValue($ps_value))) {
			$vs_currency_specifier = $va_parsed_value['currency'];
			$vs_decimal_value = $va_parsed_value['value'];
		} else {
			$this->postError(1970, _t('%1 is not a valid currency value; be sure to include a currency symbol', $pa_element_info['displayLabel'] ?? null), 'CurrencyAttributeValue->parseValue()');
			return false;
		}

		if(!$vs_currency_specifier){
			$vs_currency_specifier = $o_config->get('default_currency');
		}
		if(!$vs_currency_specifier){
			// this respects the global UI locale which is set using Zend_Locale
			$o_currency = new Zend_Currency($o_locale);
			$vs_currency_specifier = $o_currency->getShortName();
		}

		// get UI locale from registry and convert string to actual php float
		// based on rules for this locale (e.g. most non-US locations use 10.000,00 as notation)
		
		try {
			$vn_value = preg_match("!^[\d\.]+$!", $vs_decimal_value) ? (float)$vs_decimal_value : Zend_Locale_Format::getFloat($vs_decimal_value, ['locale' => $o_locale, 'precision' => 2]);
		} catch (Zend_Locale_Exception $e){
			$this->postError(1970, _t('%1 does not use a valid decimal notation for your locale', $pa_element_info['displayLabel']), 'CurrencyAttributeValue->parseValue()');
			return false;
		}
		
		switch($vs_currency_specifier) {
			case '$':
				$vs_dollars_are_this = caGetOption('dollarCurrency', $va_settings, $o_config->get('default_dollar_currency'), ['defaultOnEmptyString' => true]);
				$vs_currency_specifier = $vs_dollars_are_this ? $vs_dollars_are_this : 'USD';
				break;
			case '¥':
				$vs_currency_specifier = 'JPY';
				break;
			case '£':
				$vs_currency_specifier = 'GBP';
				break;
			case '€':
				$vs_currency_specifier = 'EUR';
				break;
			default:
				$vs_currency_specifier = strtoupper($vs_currency_specifier);
				break;
		}
		
		if (strlen($vs_currency_specifier) != 3) {
			$this->postError(1970, _t('Currency specified for %1 does not appear to be valid', $pa_element_info['displayLabel']), 'CurrencyAttributeValue->parseValue()');
			return false;
		}
		
		if ($vn_value < 0) {
			$this->postError(1970, _t('%1 must not be negative', $pa_element_info['displayLabel']), 'CurrencyAttributeValue->parseValue()');
			return false;
		}
		if ($vn_value < floatval($va_settings['minValue'])) {
			// value is too low
			$this->postError(1970, _t('%1 must be at least %2', $pa_element_info['displayLabel'], $va_settings['minValue']), 'CurrencyAttributeValue->parseValue()');
			return false;
		}
		if ((floatval($va_settings['maxValue']) > 0) && ($vn_value > floatval($va_settings['maxValue']))) {
			// value is too high
			$this->postError(1970, _t('%1 must be less than %2', $pa_element_info['displayLabel'], $va_settings['maxValue']), 'CurrencyAttributeValue->parseValue()');
			return false;
		}
		
		return array(
			'value_longtext1' => $vs_currency_specifier,
			'value_decimal1' => $vn_value
		);
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $pa_element_info An array of information about the metadata element being edited
	 * @param array $pa_options array Options include:
	 *			class = the CSS class to apply to all visible form elements [Default=currencyBg]
	 *			width = the width of the form element [Default=field width defined in metadata element definition]
	 *			height = the height of the form element [Default=field height defined in metadata element definition]
	 *
	 * @return string
	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'minValue', 'maxValue'));
		$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : 'currencyBg');
		
		return caHTMLTextInput(
			'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
			array(
				'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'] ?? null,
				'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'] ?? null, 
				'value' => '{{'.$pa_element_info['element_id'].'}}', 
				'maxlength' => $va_settings['maxChars'] ?? null,
				'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
				'class' => $vs_class
			)
		);
	}
	# ------------------------------------------------------------------
	public function getAvailableSettings($pa_element_info=null) {
		global $_ca_attribute_settings;
		
		return $_ca_attribute_settings['CurrencyAttributeValue'];
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
	 * Returns constant for currency attribute value
	 * 
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_CURRENCY__;
	}
	# ------------------------------------------------------------------
}
