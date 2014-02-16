<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/DateRangeAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 
 	global $_ca_attribute_settings;
 	
 	$_ca_attribute_settings['DateRangeAttributeValue'] = array(		// global
		'dateRangeBoundaries' => array(
			'formatType' => FT_DATERANGE,
			'displayType' => DT_FIELD,
			'width' => 50, 'height' => 1,
			'default' => '',
			'label' => _t('Date range boundaries'),
			'description' => _t('The range of dates that are accepted. Input dates outside the range will be rejected. Leave blank if you do not require restrictions.')
		),
		'fieldWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 5, 'height' => 1,
			'label' => _t('Width of data entry field in user interface'),
			'description' => _t('Width, in characters, of the field when displayed in a user interface.')
		),
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your date ranges to be locale-specific. (The default is to not be.)')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
		),
		'useDatePicker' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Include date picker'),
			'description' => _t('Check this option if you want a calendar-based date picker to be available for date entry. (The default is to not include a picker.)')
		),
		'mustNotBeBlank' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Must not be blank'),
			'description' => _t('Check this option if this attribute value must be set to some value - it must not be blank in other words. (The default is not to be.)')
		),
		'isLifespan' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Is a lifespan'),
			'description' => _t('Check this option if this attribute value represents a persons lifespan. Lifespans are displayed in a slightly different format in many languages than standard dates. (The default is not to be.)')
		),
		'default_text' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 4,
			'default' => '',
			'label' => _t('Default value'),
			'description' => _t('Value to pre-populate a newly created attribute with')
		),
		'suggestExistingValues' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'width' => 1, 'height' => 1,
			'default' => 0,
			'label' => _t('Suggest existing values?'),
			'description' => _t('Check this option if you want this attribute to suggest previously saved values as text is entered.')
		),
		'suggestExistingValueSort' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => 'value',
			'width' => 20, 'height' => 1,
			'label' => _t('Sort suggested values by?'),
			'description' => _t('If suggestion of existing values is enabled this option determines how returned values are sorted. Choose <i>value</i> to sort alphabetically. Choose <i>most recently added </i> to sort with most recently entered values first.'),
			'options' => array(
				_t('Value') => 'value',
				_t('Most recently added') => 'recent'
			)
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
 
	class DateRangeAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		private $opn_start_date;
 		private $opn_end_date;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 			$this->opn_start_date = $pa_value_array['value_decimal1'];
 			$this->opn_end_date = $pa_value_array['value_decimal2'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Options:
 		 * 		rawDate - if true, returns date as an array of start and end historic timestames
 		 *		sortable - if true a language-independent sortable representation is returned.
 		 *		getDirectDate - get underlying historic timestamp (floatval)
 		 */
		public function getDisplayValue($pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (isset($pa_options['rawDate']) && $pa_options['rawDate']) {
				return array(0 => $this->opn_start_date, 1 =>$this->opn_end_date, 'start' => $this->opn_start_date, 'end' =>$this->opn_end_date);
			}
			if (	
				(isset($pa_options['GET_DIRECT_DATE']) && $pa_options['GET_DIRECT_DATE'])
				||
				(isset($pa_options['getDirectDate']) && $pa_options['getDirectDate'])
			) {
				return $this->opn_start_date;
			}
			
			if (isset($pa_options['sortable']) && $pa_options['sortable']) {
				if (!$this->opn_start_date || !$this->opn_end_date) { return null; }
				return $this->opn_start_date.'/'.$this->opn_end_date;
			}
			
			$o_config = Configuration::load();
			$o_date_config = Configuration::load($o_config->get('datetime_config'));
			
			if ($o_date_config->get('dateFormat') == 'original') {
				return $this->ops_text_value;
			} else {				
				$t_element = new ca_metadata_elements($this->getElementID());
				$va_settings = $this->getSettingValuesFromElementArray(
					$t_element->getFieldValuesArray(), 
					array('isLifespan')
				);
				
				$o_tep = new TimeExpressionParser();
				$o_tep->setHistoricTimestamps($this->opn_start_date, $this->opn_end_date);
				return $o_tep->getText(array_merge(array('isLifespan' => $va_settings['isLifespan']), $pa_options)); //$this->ops_text_value;
 			}
		}
 		# ------------------------------------------------------------------
		public function getHistoricTimestamps() {
			return array(0 => $this->opn_start_date, 1 => $this->opn_end_date, 'start' => $this->opn_start_date, 'end' => $this->opn_end_date);
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			$ps_value = trim($ps_value);
 			$va_settings = $this->getSettingValuesFromElementArray(
 				$pa_element_info, 
 				array('dateRangeBoundaries', 'mustNotBeBlank')
 			);
 			
 			$o_tep = new TimeExpressionParser();
			if ($ps_value) {
				if (!$o_tep->parse($ps_value)) { 
					// invalid date
					$this->postError(1970, _t('%1 is invalid', $pa_element_info['displayLabel']), 'DateRangeAttributeValue->parseValue()');
					return false;
				}
				$va_dates = $o_tep->getHistoricTimestamps();
				if ($va_settings['dateRangeBoundaries']) {
					if ($o_tep->parse($va_settings['dateRangeBoundaries'])) { 
						$va_boundary_dates = $o_tep->getHistoricTimestamps();
						if (
							($va_dates[0] < $va_boundary_dates[0]) ||
							($va_dates[0] > $va_boundary_dates[1]) ||
							($va_dates[1] < $va_boundary_dates[0]) ||
							($va_dates[1] > $va_boundary_dates[1])
						) {
							// date is out of bounds
							$this->postError(1970, _t('%1 must be within %2', $pa_element_info['displayLabel'], $va_settings['dateRangeBoundaries']), 'DateRangeAttributeValue->parseValue()');
							return false;
						}
					}
				}
			} else {
				if ((bool)$va_settings['mustNotBeBlank']) {
					$this->postError(1970, _t('%1 must not be empty', $pa_element_info['displayLabel']), 'DateRangeAttributeValue->parseValue()');
					return false;
				} else {
					// Default to "undated" date for blanks
					$o_config = $o_tep->getLanguageSettings();
					$va_undated_dates = $o_config->getList('undatedDate');
					return array(
						'value_longtext1' => $va_undated_dates[0],
						'value_decimal1' => null,
						'value_decimal2' => null
					);
				}
			}
			return array(
				'value_longtext1' => $ps_value,
				'value_decimal1' => $va_dates[0],
				'value_decimal2' => $va_dates[1]
			);
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'suggestExistingValues', 'useDatePicker'));

			if (isset($pa_options['useDatePicker'])) {
 				$va_settings['useDatePicker'] = $pa_options['useDatePicker'];
 			}

 			$vn_max_length = 255;
 			$vs_element .= caHTMLTextInput(
				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
				array(
					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
					'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
					'value' => '{{'.$pa_element_info['element_id'].'}}',
					'maxlength' => $vn_max_length,
					'class' => 'dateBg'
				)
			);
			
			$vs_bundle_name = $vs_lookup_url = null;
 			if (isset($pa_options['t_subject']) && is_object($pa_options['t_subject'])) {
 				$vs_bundle_name = $pa_options['t_subject']->tableName().'.'.$pa_element_info['element_code'];
 				
 				if ($pa_options['request']) {
 					$vs_lookup_url	= caNavUrl($pa_options['request'], 'lookup', 'AttributeValue', 'Get', array('bundle' => $vs_bundle_name, 'max' => 500));
 				}
 			}
 			
 			if ((bool)$va_settings['suggestExistingValues'] && $vs_lookup_url && $vs_bundle_name) { 
 				$vs_element .= "<script type='text/javascript'>
 					jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').autocomplete( 
						{ source: '{$vs_lookup_url}', minLength: 3, delay: 800}
					);
 				</script>\n";
 			}
 			
 			if ((bool)$va_settings['useDatePicker']) { 
 				$vs_element .= "<script type='text/javascript'>
 					jQuery(document).ready(function() {
 						jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').datepicker({constrainInput: false});
 					});
 				</script>\n";
 			}
 			
 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings($pa_element_info=null) {
 			global $_ca_attribute_settings;
 			return $_ca_attribute_settings['DateRangeAttributeValue'];
 		}
 		# ------------------------------------------------------------------
 		public function getDefaultValueSetting() {
 			return 'default_text';
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