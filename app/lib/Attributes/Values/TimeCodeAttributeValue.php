<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/TimeCodeAttributeValue.php : 
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
define("__CA_ATTRIBUTE_VALUE_TIMECODE__", 10);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/Parsers/TimecodeParser.php');

global $_ca_attribute_settings;
$_ca_attribute_settings['TimeCodeAttributeValue'] = array(		// global
	'fieldWidth' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 5, 'height' => 1,
		'label' => _t('Width of data entry field in user interface'),
		'description' => _t('Width, in characters, of the field when displayed in a user interface.')
	),
	'fieldHeight' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => '',
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
		'description' => _t('Check this option if you don\'t want your time code values to be locale-specific. (The default is to not be.)')
	),
	'singleValuePerLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow single value per locale'),
		'description' => _t('Check this option to restrict entry to a single value per-locale.')
	),
	'requireValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Require value'),
		'description' => _t('Check this option if you want an error to be thrown if this measurement is left blank.')
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
	),
	'format' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => 'COLON_DELIMITED',
		'width' => 90, 'height' => 1,
		'label' => _t('Time code format'),
		'description' => _t('Selects display format for timecode value.'),
		'options' => array(
			_t('Hours/minutes/seconds') => 'HOURS_MINUTES_SECONDS',
			_t('Hours/minute/second (long suffixes)') => 'HOURS_MINUTES_SECONDS_LONG',
			_t('Colon delimited') => 'COLON_DELIMITED',
			_t('VTT timestamp') => 'VTT',
			_t('12 hour time') => 'TIME_12_HOUR',
			_t('Seconds') => 'SECONDS',
			_t('Raw integer value') => 'RAW',
		)
	),
	'omitSeconds' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Omit seconds from value?'),
		'description' => _t('Check this option to omit display of seconds in timecode values.')
	),
);

class TimeCodeAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	private $ops_text_value;
	private $opn_duration;
	# ------------------------------------------------------------------
	public function __construct($pa_value_array=null) {
		parent::__construct($pa_value_array);
	}
	# ------------------------------------------------------------------
	public function loadTypeSpecificValueFromRow($pa_value_array) {
		$this->ops_text_value = $pa_value_array['value_longtext1'];
		$this->opn_duration = (float)$pa_value_array['value_decimal1'];
	}
	# ------------------------------------------------------------------
	/**
	 * Returns value suitable for display
	 *
	 * @param $pa_options array Options are:
	 *		format = timecode format, either colonDelimited, hoursMinutesSeconds, or raw. [Default is hoursMinutesSeconds]
	 *		returnAsDecimal = return duration in seconds as decimal number. [Default is false]
	 *		omitSeconds = omit seconds from returned value. [Default is false]
	 *
	 * @return mixed Values as string or decimal
	 */
	public function getDisplayValue($pa_options=null) {
		if (caGetOption('returnAsDecimal', $pa_options, false)) {
			return (float)$this->opn_duration;
		}
		if (!strlen($this->opn_duration) || ((float)$this->opn_duration === 0.0)) { return ''; }
		$o_tcp = new TimecodeParser();
		$o_tcp->setParsedValueInSeconds($this->opn_duration);
		
		$o_config = Configuration::load();
		
		$va_settings = ca_metadata_elements::getElementSettingsForId($this->getElementID());
		if(!($ps_format = caGetOption('format', $va_settings, caGetOption('format', $pa_options, $o_config->get('timecode_output_format'))))) {
			$ps_format = 'HOURS_MINUTES_SECONDS';
		}
		if(caGetOption('omitSeconds', $va_settings, false)) {
			$pa_options['omitSeconds'] = true;
		}
		return $o_tcp->getText($ps_format, $pa_options); 
	}
	# ------------------------------------------------------------------
	public function getNumberOfSeconds() {
		return $this->opn_duration;
	}
	# ------------------------------------------------------------------
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
		$ps_value = trim($ps_value);
		$va_settings = $this->getSettingValuesFromElementArray(
			$pa_element_info, 
			array('dateRangeBoundaries', 'requireValue')
		);
		
		$o_tcp = new TimecodeParser();
		if (strlen($ps_value)) {
			if ($o_tcp->parse($ps_value) === false) { 
				// invalid timecode
				$this->postError(1970, _t('%1 is invalid', $pa_element_info['displayLabel']), 'TimeCodeAttributeValue->parseValue()');
				return false;
			}
			$vn_seconds = (float)$o_tcp->getParsedValueInSeconds();
			
		} else {
			if (isset($va_settings['requireValue']) && $va_settings['requireValue']) {
				$this->postError(1970, _t('%1 must not be empty', $pa_element_info['displayLabel']), 'DataRangeAttributeValue->parseValue()');
				return false;
			}
			$ps_value = null;
			$vn_seconds = null;
		}
		return array(
			'value_longtext1' => $ps_value,
			'value_decimal1' => $vn_seconds
		);
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $pa_element_info An array of information about the metadata element being edited
	 * @param array $pa_options array Options include:
	 *			class = the CSS class to apply to all visible form elements [Default=timecodeBg]
	 *			width = the width of the form element [Default=field width defined in metadata element definition]
	 *			height = the height of the form element [Default=field height defined in metadata element definition]	
	 *
	 * @return string
	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight'));
		
		$vs_width = trim((isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth']);
		$vs_height = trim((isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight']);
		
		$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : 'timecodeBg');
		$vn_max_length = 255;
		return caHTMLTextInput(
			'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
			array('id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
				'size' => $vs_width,
				'height' => $vs_height,
				'value' => '{{'.$pa_element_info['element_id'].'}}',
				'maxlength' => $vn_max_length,
				'class' => $vs_class
			)
		);
	}
	# ------------------------------------------------------------------
	public function getAvailableSettings($pa_element_info=null) {
		global $_ca_attribute_settings;
		
		return $_ca_attribute_settings['TimeCodeAttributeValue'];
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
	 * Returns constant for timecode attribute value
	 * 
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_TIMECODE__;
	}
	# ------------------------------------------------------------------
}
