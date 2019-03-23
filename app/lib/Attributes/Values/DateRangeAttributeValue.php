<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/DateRangeAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2016 Whirl-i-Gig
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
  	define("__CA_ATTRIBUTE_VALUE_DATERANGE__", 2);
 	
 	require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser.php');
 
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
		'datePickerDateFormat' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => 'yy-mm-dd',
			'width' => 50, 'height' => 1,
			'label' => _t('Date picker date format'),
			'options' => array(
				_t('ISO-8601 (ex. 2012-07-03)') => 'yy-mm-dd',
				_t('US Delimited (ex. 07/03/2012)') => 'mm/dd/yy',
				_t('European Delimited (ex. 03/07/2012)') => 'dd/mm/yy',
				_t('Month Day, Year (ex. July 3, 2012)') => 'MM d, yy',
				_t('Month Day Year (ex. July 3 2012)') => 'MM d yy',
				_t('Day Month Year (ex. 3 July 2012)') => 'd MM yy',
				_t('Short month Day Year (ex. Jul 3 2012)') => 'M d yy',
				_t('Day Short month Year (ex. 3 July 2012)') => 'd M yy'
			),
			'description' => _t('Format to use for dates selected from the date picker. (The default is YY-MM-DD format.)')
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

		/**
		 * @var TimeExpressionParser
		 */
 		static private $o_tep;

		/**
		 * 
		 */
 		static private $o_search_config;

		/**
		 * 
		 */
 		static private $o_lang;
 		
		/**
		 * @var array
		 */
 		static private $s_date_cache = array();
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 			if(!DateRangeAttributeValue::$o_tep) { DateRangeAttributeValue::$o_tep = new TimeExpressionParser(); }
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
 		 * 		rawDate = if true, returns date as an array of start and end historic timestames
 		 *		sortable = if true a language-independent sortable representation is returned.
 		 *		getDirectDate = get underlying historic timestamp (floatval)
 		 *      getUnixTimestamp = Return date value as single Unix timestamp value. Only valid for dates in the Unix timestamp domain (post 1970).
 		 */
		public function getDisplayValue($pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (isset($pa_options['rawDate']) && $pa_options['rawDate']) {
				return array(
					0 => $this->opn_start_date,
					1 => $this->opn_end_date,
					'start' => $this->opn_start_date,
					'end' =>$this->opn_end_date
				);
			}
			if (caGetOption('GET_DIRECT_DATE', $pa_options, false) || caGetOption('getDirectDate', $pa_options, false)) {
				return $this->opn_start_date;
			}
			if (caGetOption('getUnixTimestamp', $pa_options, false)) {
				return caHistoricTimestampToUnixTimestamp($this->opn_start_date);
			}
			
			if (isset($pa_options['sortable']) && $pa_options['sortable']) {
				if (!$this->opn_start_date || !$this->opn_end_date) { return null; }
				return $this->opn_start_date.'/'.$this->opn_end_date;
			}
			
			$o_date_config = Configuration::load(__CA_CONF_DIR__.'/datetime.conf');

			$vs_date_format = $o_date_config->get('dateFormat');
			$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $vs_date_format.$this->opn_start_date.$this->opn_end_date);

			// pull from cache
			if(isset(DateRangeAttributeValue::$s_date_cache[$vs_cache_key])) {
				return DateRangeAttributeValue::$s_date_cache[$vs_cache_key];
			}

			// if neither start nor end date are set, the setHistoricTimestamps() call below will
			// fail and the TEP will return the text for whatever happened to be parsed previously 
			// so we have to init() before trying
			DateRangeAttributeValue::$o_tep->init();
			
			if ($vs_date_format == 'original') {
				return DateRangeAttributeValue::$s_date_cache[$vs_cache_key] = $this->ops_text_value;
			} else {
				if (!is_array($va_settings = ca_metadata_elements::getElementSettingsForId($this->getElementID()))) {
					$va_settings = [];
				}
				DateRangeAttributeValue::$o_tep->setHistoricTimestamps($this->opn_start_date, $this->opn_end_date);
				return DateRangeAttributeValue::$s_date_cache[$vs_cache_key] = DateRangeAttributeValue::$o_tep->getText(array_merge(array('isLifespan' => $va_settings['isLifespan']), $pa_options)); //$this->ops_text_value;
			}
		}
 		# ------------------------------------------------------------------
		public function getHistoricTimestamps() {
			return array(0 => $this->opn_start_date, 1 => $this->opn_end_date, 'start' => $this->opn_start_date, 'end' => $this->opn_end_date);
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			$o_date_config = Configuration::load(__CA_CONF_DIR__.'/datetime.conf');
            $show_Undated = $o_date_config->get('showUndated');
 
 			$ps_value = trim($ps_value);
 			$va_settings = $this->getSettingValuesFromElementArray(
 				$pa_element_info, 
 				array('dateRangeBoundaries', 'mustNotBeBlank')
 			);
 			
			if ($ps_value) {
				if (!DateRangeAttributeValue::$o_tep->parse($ps_value)) { 
					// invalid date
					$this->postError(1970, _t('%1 is invalid', $pa_element_info['displayLabel']), 'DateRangeAttributeValue->parseValue()');
					return false;
				}
				$va_dates = DateRangeAttributeValue::$o_tep->getHistoricTimestamps();
				if ($va_settings['dateRangeBoundaries']) {
					if (DateRangeAttributeValue::$o_tep->parse($va_settings['dateRangeBoundaries'])) { 
						$va_boundary_dates = DateRangeAttributeValue::$o_tep->getHistoricTimestamps();
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
					$o_date_config = Configuration::load(__CA_CONF_DIR__.'/datetime.conf');
			
					// Default to "undated" date for blanks
					$vs_undated_date = '';
					if ((bool)$o_date_config->get('showUndated')) {
						$o_lang_config = DateRangeAttributeValue::$o_tep->getLanguageSettings();
						$vs_undated_date = array_shift($o_lang_config->getList('undatedDate'));
					}
					
					return array(
						'value_longtext1' => $vs_undated_date,
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
 		 * Return HTML form element for editing.
 		 *
 		 * @param array $pa_element_info An array of information about the metadata element being edited
 		 * @param array $pa_options array Options include:
 		 *			class = the CSS class to apply to all visible form elements [Default=dateBg]
 		 *			width = the width of the form element [Default=field width defined in metadata element definition]
 		 *			height = the height of the form element [Default=field height defined in metadata element definition]
 		 *			t_subject = an instance of the model to which the attribute belongs; required if suggestExistingValues lookups are enabled [Default is null]
 		 *			request = the RequestHTTP object for the current request; required if suggestExistingValues lookups are enabled [Default is null]
 		 *			suggestExistingValues = suggest values based on existing input for this element as user types [Default is false]		
 		 *			useDatePicker = use calendar-style date picker [Default is false],
 		 8			datePickerDateFormat = Format to use for dates selected from the date picker [Default is 'yy-mm-dd']
 		 *
 		 * @return string
 		 */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'suggestExistingValues', 'useDatePicker', 'datePickerDateFormat'));
			$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : 'dateBg');
			
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
					'class' => $vs_class
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
 				global $g_ui_locale;

 				// nothing terrible happens if this fails. If no package is registered for the current 
 				// locale, the LoadManager simply ignores it and the default settings (en_US) apply
 				AssetLoadManager::register("datepicker_i18n_{$g_ui_locale}"); 

 				$vs_element .= "<script type='text/javascript'>
 					jQuery(document).ready(function() {
 						jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').datepicker({dateFormat: '".(isset($va_settings['datePickerDateFormat']) ? $va_settings['datePickerDateFormat'] : 'yy-mm-dd')."', constrainInput: false});
 					});
 				</script>\n";

				// load localization for datepicker. we can't use the asset manager here
				// because that doesn't get the script out in time for quickadd forms
				$vs_i18n_relative_path = '/assets/jquery/jquery-ui/i18n/jquery.ui.datepicker-'.$g_ui_locale.'.js';
				if(file_exists(__CA_BASE_DIR__.$vs_i18n_relative_path)) {
					$vs_element .= "<script src='".__CA_URL_ROOT__.$vs_i18n_relative_path."' type='text/javascript'></script>\n";
				}
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
		/**
		 * Returns constant for date range attribute value
		 * 
		 * @return int Attribute value type code
		 */
		public function getType() {
			return __CA_ATTRIBUTE_VALUE_DATERANGE__;
		}
		# ------------------------------------------------------------------
        /**
         * Get extra values to add to search index.
         *
         * @return array
         */
        public function getDataForSearchIndexing() {
            if (!self::$o_search_config) { self::$o_search_config = caGetSearchConfig(); };
            if (!self::$o_lang) { self::$o_lang = TimeExpressionParser::getSettingsForLanguage(__CA_DEFAULT_LOCALE__); }
            $circa_indicators = self::$o_lang->get('dateCircaIndicator');
            $p = explode(' ', $this->ops_text_value);
            
            $terms = [];
            if (in_array($p[0], $circa_indicators)) {
                $d = join(' ', array_slice($p, 1));
            } elseif (self::$o_search_config->get('treat_before_dates_as_circa') && ((int)$this->opn_start_date === -2000000000)) {
                $d = (int)$this->opn_end_date;
            } elseif (self::$o_search_config->get('treat_after_dates_as_circa') && ((int)$this->opn_end_date === 2000000000)) {
                $d = (int)$this->opn_start_date;
            }
            return ($d) ? array_map(function($v) use ($d) { return "{$v} {$d}"; }, $circa_indicators) : [];
        }
 		# ------------------------------------------------------------------
	}
 ?>
