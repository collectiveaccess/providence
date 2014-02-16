<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/GeoNamesAttributeValue.php :
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
  
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

 require_once(__CA_APP_DIR__.'/helpers/gisHelpers.php');

 global $_ca_attribute_settings;
 
 $_ca_attribute_settings['GeoNamesAttributeValue'] = array(		// global
	'fieldWidth' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 60,
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
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Does not use locale setting'),
		'description' => _t('Check this option if you don\'t want your GeoNames values to be locale-specific. (The default is to not be.)')
	),
	'canBeUsedInSort' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be used for sorting'),
		'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is not to be.)')
	),
	'disableMap' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Disable map'),
		'description' => _t('Check this option if you want to disable location map display.')
	),
	'canBeEmpty' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be empty'),
		'description' => _t('Check this option if you want to allow empty attribute values. This - of course - only makes sense if you bundle several elements in a container.')
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
	),
	'maxResults' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 20,
		'width' => 5, 'height' => 1,
		'label' => _t('Maximum number of GeoNames results'),
		'description' => _t('Determines the maximum number of results returned by GeoNames. Tweak this number if you want to speed up lookups.')
	),
	'gnElements' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => 'name,countryName,continentCode',
		'width' => 90, 'height' => 4,
		'label' => _t('GeoNames elements'),
		'validForRootOnly' => 1,
		'description' => _t('Comma-separated list of GeoNames attributes to be pulled from the service to build the text representation for the selected location. See http://www.geonames.org/export/geonames-search.html for further reference, including the available element names. Note that latitude and longitude are always added to the text value to enable map display.')
	),
	'gnDelimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => ', ',
		'width' => 10, 'height' => 1,
		'label' => _t('GeoNames element delimiter'),
		'validForRootOnly' => 1,
		'description' => _t('Delimiter to use between multiple values pulled from GeoNames service.')
	),
);

class GeoNamesAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
 	private $ops_text_value;
 	private $ops_uri_value;
 	# ------------------------------------------------------------------
 	public function __construct($pa_value_array=null) {
 		parent::__construct($pa_value_array);
 	}
 	# ------------------------------------------------------------------
 	public function loadTypeSpecificValueFromRow($pa_value_array) {
 		$this->ops_text_value = $pa_value_array['value_longtext1'];
 		$this->ops_uri_value =  $pa_value_array['value_longtext2'];
 	}
 	# ------------------------------------------------------------------
 	/**
 	 * @param array $pa_options Options are:
 	 *		forDuplication = returns full text + Geonames URL suitable for setting a duplicate attribute. Used in BaseModelWithAttributes::copyAttributesTo()
 	 * @return string GeoNames value
 	 */
	public function getDisplayValue($pa_options=null) {
		if(isset($pa_options['coordinates']) && $pa_options['coordinates']) {
			if (preg_match("!\[([^\]]+)!", $this->ops_text_value, $va_matches)) {
				$va_tmp = explode(',', $va_matches[1]);
				if ((sizeof($va_tmp) == 2) && (is_numeric($va_tmp[0])) && (is_numeric($va_tmp[1]))) {
					return array('latitude' => trim($va_tmp[0]), 'longitude' => trim($va_tmp[1]), 'path' => trim($va_matches[1]), 'label' => $this->ops_text_value);
				} else {
					return array('latitude' => null, 'longitude' => null, 'path' => null, 'label' => $this->ops_text_value);
				}
			} else {
				return array('latitude' => null, 'longitude' => null, 'path' => null, 'label' => $this->ops_text_value);
			}
		}
		
		if(isset($pa_options['forDuplication']) && $pa_options['forDuplication']) {
			return $this->ops_text_value.'|'.$this->ops_uri_value;
		}

		return $this->ops_text_value.' [id:'.$this->ops_uri_value.']';
	}
	# ------------------------------------------------------------------
	public function getTextValue(){
		return $this->ops_text_value;
	}
 	# ------------------------------------------------------------------
	public function getUri(){
		return $this->ops_uri_value;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 		$ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
		$vo_conf = Configuration::load();
		$vs_user = trim($vo_conf->get("geonames_user"));

		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('canBeEmpty'));
		if (!$ps_value) {
 			if(!$va_settings["canBeEmpty"]){
				$this->postError(1970, _t('Entry was blank.'), 'GeoNamesAttributeValue->parseValue()');
				return false;
			}
			return array();
 		} else {
 			$vs_text = $ps_value;
 			$vs_id = null;
			if (preg_match("! \[id:([0-9]+)\]$!", $vs_text, $va_matches)) {
				$vs_id = $va_matches[1];
				$vs_text = preg_replace("! \[id:[0-9]+\]$!", "", $ps_value);
			}
			if (!$vs_id) {
				if(!$va_settings["canBeEmpty"]){
					$this->postError(1970, _t('Entry was blank.'), 'GeoNamesAttributeValue->parseValue()');
					return false;
				}
				return array();
			}

			return array(
				'value_longtext1' => $vs_text,
				'value_longtext2' => $vs_id,
			);
		}
	}
	# ------------------------------------------------------------------
	/**
	 * @param array $pa_element_info
 	 * @param array $pa_options Supported options are 
 	 *			forSearch = if true, elenent is returned for use in a search form
 	 *	@return string HTML for element		
 	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		if (isset($pa_options['forSearch']) && $pa_options['forSearch']) {
			return caHTMLTextInput("{fieldNamePrefix}".$pa_element_info['element_id']."_{n}", array('id' => "{fieldNamePrefix}".$pa_element_info['element_id']."_{n}", 'value' => $pa_options['value']), $pa_options);
		}
 		$o_config = Configuration::load();

 		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'disableMap', 'maxResults', 'gnElements', 'gnDelimiter'));

 		$vn_max_results = (isset($va_settings['maxResults']) ? intval($va_settings['maxResults']) : 20);
 		$vs_gn_elements = $va_settings['gnElements'];
 		$vs_gn_delimiter = $va_settings['gnDelimiter'];

 		if ($pa_options['request']) {
			$vs_url = caNavUrl($pa_options['request'], 'lookup', 'GeoNames', 'Get', array('maxRows' => $vn_max_results, 'gnElements' => urlencode($vs_gn_elements), 'gnDelimiter' => urlencode($vs_gn_delimiter)));
		}

 		$vs_element = '<div id="geonames_'.$pa_element_info['element_id'].'_input{n}">'.
 			caHTMLTextInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_autocomplete{n}',
				array(
					'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
					'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'], 
					'value' => '{{'.$pa_element_info['element_id'].'}}',
					'maxlength' => 512,
					'id' => "geonames_".$pa_element_info['element_id']."_autocomplete{n}",
					'class' => 'lookupBg'
				)
			).
			caHTMLHiddenInput(
				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
				array(
					'value' => '{{'.$pa_element_info['element_id'].'}}',
					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}'
				)
			);

		$vs_element .= '</div>';

		$vs_element .= "
			<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('#geonames_".$pa_element_info['element_id']."_autocomplete{n}').autocomplete(
						{ 
							source: '{$vs_url}',
							minLength: 3, delay: 800,
							select: function(event, ui) {
								jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(ui.item.label + ' [id:' + ui.item.id + ']');
							}
						}
					).click(function() { this.select(); });
				});
			</script>
		";

		if(!$va_settings["disableMap"]){

			JavascriptLoadManager::register('maps');

			$vs_element .= "
				<div id='map_".$pa_element_info['element_id']."{n}' style='width:700px; height:160px;'>

				</div>
				<script type='text/javascript'>
					if ('{n}'.substring(0,3) == 'new') {
						jQuery('#map_".$pa_element_info['element_id']."{n}').hide();
					} else {
						jQuery(document).ready(function() {
			";

			$vs_element .= "
					var re = /\[([\d\.\-,; ]+)\]/;
					var r = re.exec('{{".$pa_element_info['element_id']."}}');
					var latlong = (r) ? r[1] : null;

					if (latlong) {
						// map vars are global
						map_".$pa_element_info['element_id']."{n} = new google.maps.Map(document.getElementById('map_".$pa_element_info['element_id']."{n}'), {
							disableDefaultUI: false,
							mapTypeId: google.maps.MapTypeId.SATELLITE
						});

						var tmp = latlong.split(',');
						var pt = new google.maps.LatLng(tmp[0], tmp[1]);
						map_".$pa_element_info['element_id']."{n}.setCenter(pt);
						map_".$pa_element_info['element_id']."{n}.setZoom(15);		// todo: make this a user preference of some sort
						var marker = new google.maps.Marker({
							position: pt,
							map: map_".$pa_element_info['element_id']."{n}
						});
					}";

			$vs_element .= "
						});
					}
				</script>";
		}

 		return $vs_element;
 	}
 	# ------------------------------------------------------------------
 	public function getAvailableSettings($pa_element_info=null) {
 		global $_ca_attribute_settings;

 		return $_ca_attribute_settings['GeoNamesAttributeValue'];
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
}