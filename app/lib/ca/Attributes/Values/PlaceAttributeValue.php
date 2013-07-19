<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/PlaceAttributeValue.php :
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

global $_ca_attribute_settings;

$_ca_attribute_settings['PlaceAttributeValue'] = array(
	'fieldWidth' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 60,
		'width' => 5, 'height' => 1,
		'label' => _t('Width of field in user interface'),
		'description' => _t('Width, in characters, of the field when displayed in a user interface.')
	),
	'doesNotTakeLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Does not use locale setting'),
		'description' => _t('Check this option if you don\'t want your field values to be locale-specific. (The default is to not be.)')
	),
	'canBeUsedInSort' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be used for sorting'),
		'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
	),
	'canBeEmpty' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be empty'),
		'description' => _t('Check this option if you want to allow empty attribute values. This - of course - only makes sense if you bundle several elements in a container.')
	),
	'restrictToPlaceTypeIdno' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 50, 'height' => 1,
		'label' => _t('Place type restriction'),
		'description' => _t('Insert idno of a place type here to restrict the lookup mechanism to a certain place type. (The default is empty.)')
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

class PlaceAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	private $opn_place_id;
	private $ops_text;
	# ------------------------------------------------------------------
	public function __construct($pa_value_array=null) {
		parent::__construct($pa_value_array);
	}
	# ------------------------------------------------------------------
	public function loadTypeSpecificValueFromRow($pa_value_array) {
		$this->opn_place_id = $pa_value_array['value_integer1'];
		require_once(__CA_MODELS_DIR__.'/ca_places.php');
		$t_place = new ca_places($this->opn_place_id);
		$this->ops_text = $t_place->getLabelForDisplay().($t_place->get("idno") ? " [".$t_place->get("idno")."]" : "");
	}
	# ------------------------------------------------------------------
	public function getDisplayValue($pa_options=null) {
		return $this->ops_text;
	}
 	# ------------------------------------------------------------------
	public function getPlaceID() {
		return $this->opn_place_id;
	}
 	# ------------------------------------------------------------------
 	public function parseValue($ps_value, $pa_element_info) {
		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('canBeEmpty'));

		$ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
		if ($ps_value=="") {
			if(intval($va_settings["canBeEmpty"])!=1){
				$this->postError(1970, _t('Entry was blank.'), 'PlaceAttributeValue->parseValue()');
				return false;
			}
			return array();
		} else {
			$va_tmp = explode('|', $ps_value);
			if($va_tmp[1]){
				return array(
					'value_integer1' => $va_tmp[1],
				);
			} else {
				if($this->opn_place_id){ // odds are that the same value was saved again (e.g. due to changes in the same Container). problem is that we lost the original ID so we have to restore it here.
					return array(
						'value_integer1' => $this->opn_place_id,
					);
				}
				if(!$va_settings["canBeEmpty"]){
					$this->postError(1970, _t('Entry was blank.'), 'PlaceAttributeValue->parseValue()');
					return false;
				}
				return array();
			}
		}
 	}
 	# ------------------------------------------------------------------
 	public function htmlFormElement($pa_element_info, $pa_options=null) {
 		$o_config = Configuration::load();

 		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth'));

 		$vs_element = //'<div id="place_'.$pa_element_info['element_id'].'_input{n}">'.
 			caHTMLTextInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_autocomplete{n}',
				array(
					'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
					'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1,
					'value' => '{{'.$pa_element_info['element_id'].'}}',
					'maxlength' => 512,
					'id' => "place_".$pa_element_info['element_id']."_autocomplete{n}"
				)
			).
			caHTMLHiddenInput(
				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
				array(
					'value' => '{{'.$pa_element_info['element_id'].'}}',
					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}'
				)
			);

		$va_params = array('max' => 50);
		if ($pa_options['request']) {
			if($va_settings['restrictToPlaceTypeIdno'] && $va_settings['restrictToPlaceTypeIdno'] != ''){
				$va_params = array_merge($va_params, array("type" => $va_settings['restrictToPlaceTypeIdno']));
			} else {
				$va_params = null;
			}
			$vs_url = caNavUrl($pa_options['request'], 'lookup', 'Place', 'Get', $va_params);
		} else {
			// hardcoded default for testing.
			$vs_url = '/index.php/lookup/Place/Get';
		}

		$vs_element .= " <a href='#' style='display: none;' id='{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}' target='_place_details'>"._t("More")."</a>";

		$vs_element .= "
			<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('#place_".$pa_element_info['element_id']."_autocomplete{n}').autocomplete(
						{ 
							source: '{$vs_url}', minLength: 3, delay: 800,
							select: function(event, ui) {
								jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(ui.item.label + '|' + ui.item.id);
							}
						}
					);
				});
			</script>
		";

 		return $vs_element;
 	}
 	# ------------------------------------------------------------------
 	public function getAvailableSettings() {
 		global $_ca_attribute_settings;

 		return $_ca_attribute_settings['PlaceAttributeValue'];
 	}
 	# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return null;
		}
 		# ------------------------------------------------------------------
}
?>