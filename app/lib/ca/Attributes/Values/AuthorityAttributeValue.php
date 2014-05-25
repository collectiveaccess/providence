<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/AuthorityAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 	
 	require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 
	abstract class AuthorityAttributeValue extends AttributeValue {
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		protected $ops_text_value;
 		
 		/**
 		 *
 		 */
 		protected $opn_id;
 		
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 			$this->opn_id = $pa_value_array['value_integer1'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * 
 		 *
 		 * @param array Optional array of options. Support options are:
 		 *			template = 
 		 *			includeID = 
 		 *			idsOnly = 
 		 * @return string The value
 		 */
		public function getDisplayValue($pa_options=null) {
			$o_config = Configuration::load();
			if(is_array($va_lookup_template = $o_config->getList($this->ops_table_name.'_lookup_settings'))) {
				$vs_default_template = join($o_config->get($this->ops_table_name.'_lookup_delimiter'), $va_lookup_template);
			} else {
				$vs_default_template = "^".$this->ops_table_name.".preferred_labels";
			}			
			
			$ps_template = (string)caGetOption('template', $pa_options, $vs_default_template);
			$vb_include_id = (bool)caGetOption('includeID', $pa_options, false);
			$vb_ids_only = (bool)caGetOption('idsOnly', $pa_options, false);
			
			if ($vb_ids_only) { return $this->opn_id; }
			return caProcessTemplateForIDs($ps_template, $this->ops_table_name, array($this->opn_id), array()).($vb_include_id ? " [".$this->opn_id."]" : '');
		}
		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
		public function getID() {
			return $this->opn_id;
		}
		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			$vb_require_value = (is_null($pa_element_info['settings']['requireValue'])) ? true : (bool)$pa_element_info['settings']['requireValue'];
 		
 			if (preg_match('![^\d]+!', $ps_value)) {
 				// try to convert idno to id
 				//if ($vn_id = ca_entities::find(array('idno' => $ps_value), array('returnAs' => 'firstId'))) {
 				if ($vn_id = call_user_func($this->ops_table_name.'::find', arrat(array('idno' => $ps_value), array('returnAs' => 'firstId')))) { 
 					$ps_value = $vn_id;
 				}
 			}
 			if (!$vb_require_value && !(int)$ps_value) {
 				return array(
					'value_longtext1' => null,
					'value_integer1' => null
				);
 			} 
 			if (strlen($ps_value) && !is_numeric($ps_value)) { 
 				$this->postError(1970, _t('%1 id %2 is not valid for element %3', $this->ops_name_singular, $pa_element_info["element_code"], $ps_value), $this->ops_name_plural.'AttributeValue->parseValue()');
				return false;
			}
			$o_dm = Datamodel::load();
 			$t_item = $o_dm->getInstanceByTableName($this->ops_table_name, true);
 			if (!$t_item->load((int)$ps_value)) {
 				if ($ps_value) {
 					$this->postError(1970, _t('%1 id %2 is not a valid id for %3 [%4]', $this->ops_name_singular, $ps_value, $pa_element_info['displayLabel'], $pa_element_info['element_code']), $this->ops_name_plural.'AttributeValue->parseValue()');
 				} else {
 					return null;
 				}
				return false;
 			}
 			
 			return array(
 				'value_longtext1' => $ps_value,
 				'value_integer1' => (int)$ps_value
 			);
 		}
 		# ------------------------------------------------------------------
 		/**
 		  * Generates HTML form widget for attribute value
 		  * 
 		  * @param $pa_element_info array Array with information about the metadata element with which this value is associated. Keys taken to be ca_metadata_elements field names and the 'settings' field must be deserialized into an array.
 		  * @param $pa_options array Array of options. Supported options are:
 		  *
 		  *
 		  * @return string HTML code for form element
 		  */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
			$o_config = Configuration::load();

			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth'));

			$vs_element = 
				"<div id='{fieldNamePrefix}{$pa_element_info['element_id']}_display{n}' style='float: right;'> </div>".
				caHTMLTextInput(
					"{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}",
					array(
						'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
						'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1,
						'value' => '{{'.$pa_element_info['element_id'].'}}',
						'maxlength' => 512,
						'id' => "{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}",
						'class' => 'lookupBg'
					)
				).
				caHTMLHiddenInput(
					"{fieldNamePrefix}{$pa_element_info['element_id']}_{n}",
					array(
						'value' => '{{'.$pa_element_info['element_id'].'}}',
						'id' => "{fieldNamePrefix}{$pa_element_info['element_id']}_{n}"
					)
				);

			$va_params = array('max' => 50);
			if ($pa_options['request']) {
				if($vs_restrict_to_type = caGetOption('restrictTo'.$this->ops_name_singular.'TypeIdno', $pa_element_info['settings'], null)) { 
					$va_params = array("type" => $vs_restrict_to_type);
				} else {
					$va_params = null;
				}
				$vs_url = caNavUrl($pa_options['request'], 'lookup', $this->ops_name_singular, 'Get', $va_params);
			} else {
				// no lookup is possible
				return $this->getDisplayValue();
			}

			
			$vs_element .= "
				<script type='text/javascript'>
					jQuery(document).ready(function() {
						var v = jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}').val();	
						v=v.replace(/(<\/?[^>]+>)/gi, function(m, p1, offset, val) {
							jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_display{n}').html(p1);
							return '';
						});
						v=v.replace(/\[([\d]+)\]$/gi, function(m, p1, offset, val) {
							jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_{n}').val(parseInt(p1));
							return '';
						});
						jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}').val(v.trim());
						
						jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}').autocomplete( 
							{ minLength: 3, delay: 800, html: true,
								source: function( request, response ) {
									$.ajax({
										url: '{$vs_url}',
										dataType: 'json',
										data: { term: request.term, quickadd: 0, noInline: 1 },
										success: function( data ) {
											response(data);
										}
									});
								}, 
								select: function( event, ui ) {
									if(!parseInt(ui.item.id) || (ui.item.id <= 0)) {
										jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}').val('');  // no matches so clear text input
										event.preventDefault();
										return;
									}
						
									jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_{n}').val(ui.item.id);
									jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}').val(jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, '')));
									event.preventDefault();
								},
								change: function( event, ui ) {
									//If nothing has been selected remove all content from  text input
									if(!jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_{n}').val()) {
										jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_autocomplete{n}').val('');
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
 		/**
 		 *
 		 */
 		public function getAvailableSettings($pa_element_info=null) {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings[$this->ops_name_plural.'AttributeValue'];
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
		 *
		 */
		public function __call($ps_method, $pa_params) {
			if ($ps_method == 'get'.$this->ops_name_singular.'ID') {
				return $this->getID($pa_params[0]);
			}
			throw new Exception(_t('Method %1 does not exist for %2 attributes', $ps_method, $this->ops_name_singular));
		}
 		# ------------------------------------------------------------------
	}
 ?>