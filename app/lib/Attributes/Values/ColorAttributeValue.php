<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/ColorAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 	define("__CA_ATTRIBUTE_VALUE_COLOR__", 32);
 	
 	require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 
 	global $_ca_attribute_settings;
 	$_ca_attribute_settings['ColorAttributeValue'] = array(		// global
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
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
		),
		'default_text' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 4,
			'default' => '',
			'label' => _t('Default'),
			'description' => _t('Default color')
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
		'showHexValueText' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Show hex color value below color chip?'),
			'description' => _t('Check this option to display the hex value of the selected color below the color chip.')
		),
		'showRGBValueText' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Show RGB color value below color chip?'),
			'description' => _t('Check this option to display the RGB value of the selected color below the color chip.')
		),
		'displayTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Display template'),
			'validForRootOnly' => 1,
			'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
		)
	);
 
	class ColorAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * @param array $pa_options Options include:
 		 *      doRefSubstitution = Parse and replace reference tags (in the form [table idno="X"]...[/table]). [Default is false in Providence; true in Pawtucket].
 		 * @return string
 		 */
		public function getDisplayValue($pa_options=null) {
			return $this->ops_text_value;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray(
 				$pa_element_info, 
 				array()
 			);
 			
 			if (!preg_match("!^[A-Fa-f0-9]{3,6}$!", $ps_value) && trim($ps_value)) {
 				// not a valid hex color
 				$this->postError(1970, _t('Color is invalid'), 'ColorAttributeValue->parseValue()');
				return false;
 			}
 			
 			return array(
 				'value_longtext1' => $ps_value
 			);
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return HTML form element for editing.
 		 *
 		 * @param array $pa_element_info An array of information about the metadata element being edited
 		 * @param array $pa_options array Options include:
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
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 		    AssetLoadManager::register('jquery', 'colorpicker');
 			global $g_request;
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'showHexValueText', 'showRGBValueText'));

 			$vs_width = trim((isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth']);
 			$vs_height = trim((isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight']);
 			$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : '');
			
			if (!$vs_width) { $vs_width = "72px"; }
			if (!$vs_height) { $vs_height = "72px"; }
			
 			if (!preg_match("!^[\d\.]+px$!i", $vs_width)) {
 				$vs_width = ((int)$vs_width * 6)."px";
 			}
 			if (!preg_match("!^[\d\.]+px$!i", $vs_height) && ((int)$vs_height > 1)) {
 				$vs_height = ((int)$vs_height * 16)."px";
 			}
 			
            $id = '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}';
            $vs_element = "<input name='{$id}' type='hidden' value='{{".$pa_element_info['element_id']."}}' id='{$id}'/>\n";
            
            if (isset($va_settings['showHexValueText']) && (bool)$va_settings['showHexValueText']) {
                $vs_element .= "<div class='colorpickerText' id='{$id}_hexdisplay'>#{{".$pa_element_info['element_id']."}}</div>";  
            }
            if (isset($va_settings['showRGBValueText']) && (bool)$va_settings['showRGBValueText']) {
                $vs_element .= "<div class='colorpickerText' id='{$id}_rgbdisplay'></div>";  
            }
             $vs_element .= "<div id='{$id}_colorchip' class='colorpicker_chip {$vs_class}' style='background-color: #{{".$pa_element_info['element_id']."}}; width: {$vs_width}; height: {$vs_height};'><!-- empty --></div>";
           
            $vs_element .= "<script type='text/javascript'>jQuery(document).ready(function() {
                    jQuery('#".$id."_colorchip').ColorPicker({
                            onShow: function (colpkr) {
                                jQuery(colpkr).fadeIn(500);
                                return false;
                            },
                            onHide: function (colpkr) {
                                jQuery(colpkr).fadeOut(500);
                                return false;
                            },
                            onChange: function (hsb, hex, rgb) {
                                jQuery('#{$id}').val(hex);
                                jQuery('#{$id}_colorchip').css('backgroundColor', '#' + hex);
                                jQuery('#{$id}_hexdisplay').html('#' + hex);
                                jQuery('#{$id}_rgbdisplay').html('R: ' + rgb.r + ' G: ' + rgb.g + ' B: ' + rgb.b);
                            },
                            color: '{{".$pa_element_info['element_id']."}}'
                        });
                        
                        jQuery('#{$id}_rgbdisplay').html(caUI.utils.hexToRgb('{{".$pa_element_info['element_id']."}}', 'R: %r G: %g B: %b'));
 	});									
</script>";
 			
 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings($pa_element_info=null) {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['ColorAttributeValue'];
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
			return 'value_longtext1';
		}
 		# ------------------------------------------------------------------
		/**
		 * Returns constant for text attribute value
		 * 
		 * @return int Attribute value type code
		 */
		public function getType() {
			return __CA_ATTRIBUTE_VALUE_COLOR__;
		}
 		# ------------------------------------------------------------------
	}
