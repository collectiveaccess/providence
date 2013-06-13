<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/TaxonomyAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

 	global $_ca_attribute_settings;

 	$_ca_attribute_settings['TaxonomyAttributeValue'] = array(		// global
		'fieldWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 60,
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
			'description' => _t('Check this option if you don\'t want your Taxonomy values to be locale-specific. (The default is to not be.)')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is not to be.)')
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

	class TaxonomyAttributeValue extends AttributeValue implements IAttributeValue {
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
		public function getDisplayValue($pa_options=null) {
			return $this->ops_text_value;
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
 		public function parseValue($ps_value, $pa_element_info) {
			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('uBioKeyCode'));
 			$ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
 			$va_return = "";

			if (trim($ps_value)) {
				$va_matches = array();
				if(preg_match("/^.+\[uBio\:([0-9]+)\]/",$ps_value,$va_matches)){
					$vs_id = trim($va_matches[1]);
					$vs_text = trim($ps_value);
					$vo_conf = new Configuration();
					$vs_ubio_keycode = trim($vo_conf->get("ubio_keycode"));
					$vs_detail_uri = "http://www.ubio.org/webservices/service.php?function=namebank_object&namebankID={$vs_id}&keyCode={$vs_ubio_keycode}";

					$va_return = array(
						'value_longtext1' => $vs_text,	// text
						'value_longtext2' => $vs_detail_uri,
						'value_decimal1' => intval($vs_id),	// id
					);
				}
			}

			return $va_return;
 		}
 		# ------------------------------------------------------------------
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight'));

 			$vs_element = '<div id="taxonomy_'.$pa_element_info['element_id'].'_input{n}">'.
 				caHTMLTextInput(
 					'{fieldNamePrefix}'.$pa_element_info['element_id'].'_autocomplete{n}',
					array(
						'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
						'height' => 1,
						'value' => '{{'.$pa_element_info['element_id'].'}}',
						'maxlength' => 512,
						'id' => "taxonomy_".$pa_element_info['element_id']."_autocomplete{n}",
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

			if ($pa_options['request']) {
				$vs_url = caNavUrl($pa_options['request'], 'lookup', 'Taxonomy', 'Get', array('max' => 100));
			} else {
				// hardcoded default for testing.
				$vs_url = '/index.php/lookup/Taxonomy/Get';
			}

			$vs_element .= " <a href='#' style='display: none;' id='{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}' target='_taxonomy_details'>"._t("More")."</a>";

			$vs_element .= '</div>';
			$vs_element .= "
				<script type='text/javascript'>
					jQuery(document).ready(function() {
						jQuery('#taxonomy_".$pa_element_info['element_id']."_autocomplete{n}').autocomplete(
							{ 
								source: '{$vs_url}', minLength: 3, delay: 800,
								select: function(event, ui) {
									if (ui.item.id) {
										jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(ui.item.label);
									} else {
										event.preventDefault();
									}
								}
							}	
						).click(function() { this.select(); });
					});
				</script>
			";

 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings() {
 			global $_ca_attribute_settings;

 			return $_ca_attribute_settings['TaxonomyAttributeValue'];
 		}
 		# ------------------------------------------------------------------
	}
 ?>