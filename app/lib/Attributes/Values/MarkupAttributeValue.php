<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/MarkupAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_MARKUP__", 9001);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;
$_ca_attribute_settings['MarkupAttributeValue'] = array(		// global
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
        'width' => 10, 'height' => 1,
        'default' => 16777216,
        'label' => _t('Maximum number of characters'),
        'description' => _t('The maximum number of characters to allow. Input longer than required will be rejected.')
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
    'showlabelable' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Show Label'),
        'description' => _t('Check this option if you want a label above the feedback')
    ),
    'usewysiwygeditor' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Use rich text editor'),
        'description' => _t('Check this option if you want to use a word-processor like editor with this text field. If you expect users to enter rich text (italic, bold, underline) then you might want to enable this.')
    ),
    'doesNotTakeLocale' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Does not use locale setting'),
        'description' => _t('Check this option if you don\'t want your text to be locale-specific. (The default is to be.)'),
        'hideOnSelect' => ['singleValuePerLocale', 'allowLocales'],
    ),
    'singleValuePerLocale' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Allow single value per locale'),
        'description' => _t('Check this option to restrict entry to a single value per-locale.')
    ),
    'allowLocales' => [
        'formatType' => FT_TEXT,
        'displayType' => DT_SELECT,
        'default' => null,
        'showLocaleList' => true,
        'width' => '400px', 'height' => 10,
        'label' => _t('Allow locales'),
        'multiple' => true,
        'description' => _t('Specify specific locales to allow for this element.')
    ],
    'allowDuplicateValues' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Allow duplicate values?'),
        'description' => _t('Check this option if you want to allow duplicate values to be set when element is not in a container and is repeating.'),
        'showOnSelect' => ['raiseErrorOnDuplicateValue']
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
    'suggestExistingValues' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'width' => 1, 'height' => 1,
        'default' => 0,
        'label' => _t('Suggest existing values?'),
        'description' => _t('Check this option if you want this attribute to suggest previously saved values as text is entered. This option is only effective if the display height of the text entry is equal to 1.'),
        'showOnSelect' => ['suggestExistingValueSort']
    ),
    'suggestExistingValueSort' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_SELECT,
        'default' => 'value',
        'width' => 20, 'height' => 1,
        'label' => _t('Sort suggested values by?'),
        'description' => _t('If suggestion of existing values is enabled this option determines how returned values are sorted. Choose <em>value</em> to sort alphabetically. Choose <em>most recently added </em> to sort with most recently entered values first.'),
        'options' => array(
            _t('Value') => 'value',
            _t('Most recently added') => 'recent'
        )
    ),
    'default_text' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'width' => 70, 'height' => 4,
        'default' => '',
        'label' => _t('Default text'),
        'description' => _t('Text to pre-populate a newly created attribute with. You may use the following tags to include information about the currently logged in user: <em>^currentuser.fname</em> (user first name), <em>^currentuser.lname</em> (user last name), <em>^currentuser.email</em> (user email address) and  <em>^currentuser.user_name</em> (login name),  ')
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
        'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <em>^my_element_code</em>.')
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
    'isDependentValue' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Is dependent value?'),
        'validForNonRootOnly' => 1,
        'description' => _t('If set then this element is set using values in other fields in the same container. This is only relevant when the element is in a container and is ignored otherwise.')
    ),
    'dependentValueTemplate' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 90, 'height' => 4,
        'label' => _t('Dependent value template'),
        'validForNonRootOnly' => 1,
        'description' => _t('Template to be used to format content for dependent values. Template should reference container values using their bare element code prefixed with a caret (^). Do not include the table or container codes.')
    ),
    'mustBeUnique' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Must be unique'),
        'description' => _t('Check this option to enforce uniqueness across all values for this attribute.')
    ),
    'referenceMediaIn' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_SELECT,
        'showMediaElementBundles' => true,
        'default' => '',
        'width' => "200px", 'height' => 1,
        'label' => _t('Reference media in'),
        'description' => _t('Allow in-line references in text to a media element.')
    ),
    'moveArticles' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Omit leading definite and indefinite articles when sorting'),
        'description' => _t('Check this option to sort values without definite and indefinite articles when they are at the beginning of the text.')
    ),
);

class MarkupAttributeValue extends AttributeValue implements IAttributeValue {
    # ------------------------------------------------------------------
    private $ops_text_value;
    # ------------------------------------------------------------------
    public function __construct($value_array=null) {
        parent::__construct($value_array);
    }
    # ------------------------------------------------------------------
    public function loadTypeSpecificValueFromRow($value_array) {
        $this->ops_text_value = $value_array['value_longtext1'] ?? null;
    }
    # ------------------------------------------------------------------
    /**
     * @param array $options Options include:
     *      doRefSubstitution = Parse and replace reference tags (in the form [table idno="X"]...[/table]). [Default is false in Providence; true in Pawtucket].
     *		stripEnclosingParagraphTags = The CKEditor and QuillJS "rich text" editors automatically wrap text in paragraph (<p>) tags. This is usually desirable but can cause issues when embedding styled text into a template meant to be viewed as a single line. Setting this option will remove any enclosing "<p>" tags. [Default is false]
     * @return string
     */
    public function getDisplayValue($options=null) {
        global $g_request;

        return "";
    }
    # ------------------------------------------------------------------
    public function parseValue($value, $element_info, $options=null) {
        $settings = $this->getSettingValuesFromElementArray(
            $element_info,
            ['minChars', 'maxChars', 'regex', 'mustBeUnique', 'moveArticles']
        );

        return [];
    }
    # ------------------------------------------------------------------
    /**
     * Return HTML form element for editing.
     *
     * @param array $element_info An array of information about the metadata element being edited
     * @param array $options array Options include:
     *			usewysiwygeditor = overrides element level setting for visual text editor [Default=false]
     *			forSearch = settings and options regarding visual text editor are ignored [Default=false]
     *			class = the CSS class to apply to all visible form elements [Default=null]
     *			width = the width of the form element [Default=field width defined in metadata element definition]
     *			height = the height of the form element [Default=field height defined in metadata element definition]
     *			t_subject = an instance of the model to which the attribute belongs; required if suggestExistingValues lookups are enabled [Default is null]
     *			request = the RequestHTTP object for the current request; required if suggestExistingValues lookups are enabled [Default is null]
     *			suggestExistingValues = suggest values based on existing input for this element as user types [Default is false]
     *
     * @return string
     */
    public function htmlFormElement($element_info, $options=null) {
        global $g_request;

        $settings = $this->getSettingValuesFromElementArray($element_info, ['fieldWidth', 'fieldHeight', 'minChars', 'maxChars', 'suggestExistingValues', 'usewysiwygeditor', 'isDependentValue', 'dependentValueTemplate', 'mustBeUnique', 'referenceMediaIn']);


        $whereami = $_SERVER['REQUEST_URI'];
        preg_match('|.*/editor/(.*?)/.*/(\d+)|',$whereami,$matches);


        $t_instance = Datamodel::getInstance('ca_'.$matches[1]);
          //if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {    return false;   }
         $item = $t_instance->load($matches[2]);



       $element = "<div class='markup_elem_wrapper'><div class='markup_element' id='elem_".$element_info['element_id']."'>";
       $element .= $t_instance->getWithTemplate($element_info['settings']['displayTemplate']);
       $element .="</div></div>";


        return $element;
    }
    # ------------------------------------------------------------------
    public function getAvailableSettings($element_info=null) {
        global $_ca_attribute_settings;

        return $_ca_attribute_settings['MarkupAttributeValue'];
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
        return 'value_sortable';
    }
    # ------------------------------------------------------------------
    /**
     * Returns name of field in ca_attribute_values to use for query operations
     *
     * @return string Name of sort field
     */
    public function queryFields() : ?array {
        return ['value_longtext1'];
    }
    # ------------------------------------------------------------------
    /**
     * Returns sortable value for metadata value
     *
     * @param string $value
     *
     * @return string
     */
    public function sortableValue(?string $value, ?array $options=null) {
        return mb_strtolower(substr(trim(caSortableValue($value, $options)), 0, 100));
    }
    # ------------------------------------------------------------------
    /**
     * Returns constant for text attribute value
     *
     * @return int Attribute value type code
     */
    public function getType() {
        return __CA_ATTRIBUTE_VALUE_TEXT__;
    }
    # ------------------------------------------------------------------
}
