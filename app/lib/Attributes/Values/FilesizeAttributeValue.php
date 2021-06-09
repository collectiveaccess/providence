<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/FilesizeAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_FILESIZE__", 33);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;

$_ca_attribute_settings['FilesizeAttributeValue'] = [		// global
    'fieldWidth' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_FIELD,
        'default' => 40,
        'width' => 5, 'height' => 1,
        'label' => _t('Width of data entry field in user interface'),
        'description' => _t('Width, in characters, of the field when displayed in a user interface.')
    ],
    'fieldHeight' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_FIELD,
        'default' => 1,
        'width' => 5, 'height' => 1,
        'label' => _t('Height of data entry field in user interface'),
        'description' => _t('Height, in characters, of the field when displayed in a user interface.')
    ],
    'doesNotTakeLocale' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Does not use locale setting'),
        'description' => _t('Check this option if you don\'t want your measurements to be locale-specific. (The default is not to be.)')
    ],
    'requireValue' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Require value'),
        'description' => _t('Check this option if you want an error to be thrown if this measurement is left blank.')
    ],
    'allowDuplicateValues' => [
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow duplicate values?'),
		'description' => _t('Check this option if you want to allow duplicate values to be set when element is not in a container and is repeating.')
	],
	'raiseErrorOnDuplicateValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Show error message for duplicate values?'),
		'description' => _t('Check this option to show an error message when value is duplicate and <em>allow duplicate values</em> is not set.')
	),
    'canBeUsedInSort' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Can be used for sorting'),
        'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
    ],
    'canBeUsedInSearchForm' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Can be used in search form'),
        'description' => _t('Check this option if this attribute value can be used in search forms. (The default is to be.)')
    ],
    'canBeUsedInDisplay' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Can be used in display'),
        'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
    ],
    'canMakePDF' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Allow PDF output?'),
        'description' => _t('Check this option if this metadata element can be output as a printable PDF. (The default is not to be.)')
    ],
    'canMakePDFForValue' => [
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Allow PDF output for individual values?'),
        'description' => _t('Check this option if individual values for this metadata element can be output as a printable PDF. (The default is not to be.)')
    ],
    'displayTemplate' => [
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 90, 'height' => 4,
        'label' => _t('Display template'),
        'validForRootOnly' => 1,
        'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
    ],
    'displayDelimiter' => [
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '; ',
        'width' => 10, 'height' => 1,
        'label' => _t('Value delimiter'),
        'validForRootOnly' => 1,
        'description' => _t('Delimiter to use between multiple values when used in a display.')
    ]
];

class FilesizeAttributeValue extends AttributeValue implements IAttributeValue {
    # ------------------------------------------------------------------
    private $ops_text_value;
    private $opn_decimal_value;
    private $config;
    # ------------------------------------------------------------------
    public function __construct($value_array=null) {
        parent::__construct($value_array);
    }
    # ------------------------------------------------------------------
    public function loadTypeSpecificValueFromRow($value_array) {
        $this->ops_text_value = $value_array['value_longtext1'];			
        $this->opn_decimal_value = $value_array['value_decimal1'];
    }
    # ------------------------------------------------------------------
    /**
     * Returns value suitable for display
     *
     * @param $options array Options are:
     *		normalize = Return filesize with normalized units - the largest unit that will include a whole number component. [Default is null]
     *
     * @return String Value for display
     */
    public function getDisplayValue($options=null) {
        if ($units = caGetOption('normalize', $options, true)) {
        	return caHumanFilesize($this->opn_decimal_value);
        }
        return $this->ops_text_value;
    }
    # ------------------------------------------------------------------
    public function parseValue($value, $element_info, $options=null) {
        $size_in_bytes = caParseHumanFilesize($value);
        print $size_in_bytes;
        $settings = $this->getSettingValuesFromElementArray($element_info, ['requireValue']);
        if (!$settings['requireValue'] && !strlen(trim($value))) {
            return [
                'value_longtext1' => '',			// originally entered filesize expression
                'value_decimal1'  => ''				// size in bytes
            ];
        }

        if(is_null($size_in_bytes)) {
            $this->postError(1970, _t('%1 is not a valid file size', $element_info['displayLabel']), 'FilesizeAttributeValue->parseValue()');
            return false;
        }

        return [
            'value_longtext1' => $value,					                            // originally entered filesize expression
            'value_decimal1'  => $size_in_bytes											// size in bytes
        ];
    }
    # ------------------------------------------------------------------
    /**
     *
     */
    public function htmlFormElement($element_info, $options=null) {
        $settings = $this->getSettingValuesFromElementArray($element_info, ['fieldWidth', 'fieldHeight']);
        $class = trim((isset($options['class']) && $options['class']) ? $options['class'] : 'filesizeBg');
        
        return caHTMLTextInput(
            '{fieldNamePrefix}'.$element_info['element_id'].'_{n}', 
            [
                'size' => (isset($options['width']) && $options['width'] > 0) ? $options['width'] : $settings['fieldWidth'],
                'height' => (isset($options['height']) && $options['height'] > 0) ? $options['height'] : $settings['fieldHeight'], 
                'value' => '{{'.$element_info['element_id'].'}}',
                'id' => '{fieldNamePrefix}'.$element_info['element_id'].'_{n}',
                'class' => $class
            ]
        );
    }
    # ------------------------------------------------------------------
    public function getAvailableSettings($element_info=null) {
        global $_ca_attribute_settings;
        return $_ca_attribute_settings['FilesizeAttributeValue'];
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
        return __CA_ATTRIBUTE_VALUE_FILESIZE__;
    }
    # ------------------------------------------------------------------
}
