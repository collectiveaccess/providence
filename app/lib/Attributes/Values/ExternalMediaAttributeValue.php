<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/ExternalMediaAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_EXTERNAL_MEDIA__", 34);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_APP_DIR__.'/helpers//externalMediaHelpers.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;

$_ca_attribute_settings['ExternalMediaAttributeValue'] = [		// global
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
        'description' => _t('Check this option if you don\'t want your media to be locale-specific. (The default is not to be.)')
    ],
    'singleValuePerLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow single value per locale'),
		'description' => _t('Check this option to restrict entry to a single value per-locale.')
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
    ],
	'mediaWidth' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => '670px',
		'width' => 5, 'height' => 1,
		'label' => _t('Default width of media display in user interface'),
		'description' => _t('Width in pixels of the media display.')
	),
	'mediaHeight' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => '200px',
		'width' => 5, 'height' => 1,
		'label' => _t('Height of media display in user interface'),
		'description' => _t('Height in pixels of the media display.')
	)
];

class ExternalMediaAttributeValue extends AttributeValue implements IAttributeValue {
    # ------------------------------------------------------------------
    private $url_value;
    private $url_source;
    private $config;
    # ------------------------------------------------------------------
    public function __construct($value_array=null) {
        parent::__construct($value_array);
    }
    # ------------------------------------------------------------------
    public function loadTypeSpecificValueFromRow($value_array) {
        $this->url_value = $value_array['value_longtext1'];		
        $this->url_source = $value_array['value_longtext2'];
    }
    # ------------------------------------------------------------------
    /**
     * Returns external media for display. By default the URL of the media is returned.
     *
     * @param $options array Options are:
     *		embed = Return embed tag for playback of media. [Default is false]
     *
     * @return String  for display
     */
    public function getDisplayValue($options=null) {
        if(caGetOption('embed', $options, false)) {
        	$t_element = ca_metadata_elements::getInstance($this->getElementCode
        	return caGetExternalMediaEmbedCode($url, ['width' => caGetOption('width', $options, $t_element->getSetting('mediaWidth')), 'height' => caGetOption('height', $options, $t_element->getSetting('mediaHeight'))]));
        }
        return $this->url_value;
    }
    # ------------------------------------------------------------------
    public function parseValue($value, $element_info, $options=null) {
      
        $settings = $this->getSettingValuesFromElementArray($element_info, ['requireValue']);
        if (!$settings['requireValue'] && !strlen(trim($value))) {
            return [
                'value_longtext1' => '',				// media url
                'value_longtext2' => ''					// media source
            ];
        }
        
		$regex = "(http|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&;:/~\+#]*[\w\-\@?^=%&/~\+#])?";
		if ($regex && !preg_match("!{$regex}!", $value)) {
			// default to https if it's just a hostname + path
			if (!preg_match("!^[A-Za-z]+:\/\/!", $value)) {
				$value = "https://{$value}";
			} else {
				// regex failed
				$this->postError(1970, _t('%1 is not a valid url', $element_info['displayLabel']), 'ExternalMediaAttributeValue->parseValue()');
				return false;
			}
		}

		if(!($info = caGetExternalMediaUrlInfo($value))) {
			$this->postError(1970, _t('%1 is not a valid external media url. Only %2 are supported.', $element_info['displayLabel'], caMakeCommaListWithConjunction(caGetExternalMediaUrlSupportedFormats(['names' => true]))), 'ExternalMediaAttributeValue->parseValue()');
			return false;
		}

        return [
            'value_longtext1' => $value,									// media url
            'value_longtext2' => $info['source'].':'.$info['code']			// media source
        ];
    }
    # ------------------------------------------------------------------
    /**
     *
     */
    public function htmlFormElement($element_info, $options=null) {
        $settings = $this->getSettingValuesFromElementArray($element_info, ['fieldWidth', 'fieldHeight']);
        $class = trim((isset($options['class']) && $options['class']) ? $options['class'] : 'externalMediaBg');
        
        $element = caHTMLTextInput(
            '{fieldNamePrefix}'.$element_info['element_id'].'_{n}', 
            [
                'size' => (isset($options['width']) && $options['width'] > 0) ? $options['width'] : $settings['fieldWidth'],
                'height' => (isset($options['height']) && $options['height'] > 0) ? $options['height'] : $settings['fieldHeight'], 
                'value' => '{{'.$element_info['element_id'].'}}',
                'id' => '{fieldNamePrefix}'.$element_info['element_id'].'_{n}',
                'class' => $class,
                'placeholder' => _t('%1 media url', caMakeCommaListWithConjunction(caGetExternalMediaUrlSupportedFormats(['names' => true]), ['conjunction' => _t('or')]))
            ]
        );
        
        $element .= " <a href='#' class='caExternalMediaMoreLink' id='{fieldNamePrefix}".$element_info['element_id']."_link{n}'>"._t("View &rsaquo;")."</a>";
        $element .= "<div id='{fieldNamePrefix}".$element_info['element_id']."_detail{n}' class='caExternalMediaDetail'></div></div>";
    
		$detail_url =  $options['request'] ? caNavUrl($options['request'], 'lookup', 'ExternalMedia', 'GetDetail', ['element_id' => $element_info['element_id']]) : null;
            
    	$element .= "<script type='text/javascript'>
	jQuery(document).ready(function() {
		if ('{{".$element_info['element_id']."}}') {
			jQuery('#{fieldNamePrefix}".$element_info['element_id']."_link{n}').css('display', 'inline').on('click', function(e) {
				if (jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').css('display') == 'none') {
					jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').slideToggle(250, function() {
						jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').load('{$detail_url}/id/{n}');
					});
					jQuery('#{fieldNamePrefix}".$element_info['element_id']."_link{n}').html('".addslashes(_t("Less &rsaquo;"))."');
				} else {
					jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').slideToggle(250);
					jQuery('#{fieldNamePrefix}".$element_info['element_id']."_link{n}').html('".addslashes(_t("More &rsaquo;"))."');
				}
				return false;
			});
		}
	});
</script>
";        
    	return $element;
    }
    # ------------------------------------------------------------------
    public function getAvailableSettings($element_info=null) {
        global $_ca_attribute_settings;
        return $_ca_attribute_settings['ExternalMediaAttributeValue'];
    }
    # ------------------------------------------------------------------
    /**
     * Returns name of field in ca_attribute_values to use for sort operations
     * 
     * @return string Name of sort field
     */
    public function sortField() {
        return 'value_longtext1';
    }
    # ------------------------------------------------------------------
    /**
     * Returns constant for length attribute value
     * 
     * @return int Attribute value type code
     */
    public function getType() {
        return __CA_ATTRIBUTE_VALUE_EXTERNAL_MEDIA__;
    }
    # ------------------------------------------------------------------
}
