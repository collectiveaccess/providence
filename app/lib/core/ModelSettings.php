<?php
/** ---------------------------------------------------------------------
 * app/lib/core/ModelSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__.'/core/View.php');
 
	class ModelSettings {
		# ------------------------------------------------------
		private $o_instance;
		private $ops_settings_field;
		private $opa_settings_defs;
		# ------------------------------------------------------
		public function __construct($t_instance, $ps_settings_field, $pa_settings_defs) {
			$this->o_instance = $t_instance;
			$this->ops_settings_field = $ps_settings_field;
			$this->opa_settings_defs = $pa_settings_defs;
		}
		# ------------------------------------------------------
		public function __destruct() {
			unset($this->o_instance);
		}
		# ------------------------------------------------------
		# Settings
		# ------------------------------------------------------
		/**
		 * Returns associative array of setting descriptions (but *not* the setting values)
		 * The keys of this array are the setting codes, the values associative arrays containing
		 * info about the setting itself (label, description type of value, how to display an entry element for the setting in a form)
		 */
		public function getAvailableSettings() {
			return $this->opa_settings_defs;
		}
		# ------------------------------------------------------
		/**
		 * Sets the associative array of setting descriptions (but *not* the setting values)
		 * The keys of this array are the setting codes, the values associative arrays containing
		 * info about the setting itself (label, description type of value, how to display an entry element for the setting in a form)
		 */
		public function setAvailableSettings($pa_settings) {
			$this->opa_settings_defs = $pa_settings;
		}
		# ------------------------------------------------------
		/**
		 * 
		 */
		public function getSettingInfo($ps_setting) {
			return isset($this->opa_settings_defs[$ps_setting]) ? $this->opa_settings_defs[$ps_setting] : null;
		}
		# ------------------------------------------------------
		/**
		 * Returns an associative array with the setting values for this restriction
		 * The keys of the array are setting codes, the values are the setting values
		 */
		public function getSettings() {
			return $this->o_instance->get($this->ops_settings_field);
		}
		# ------------------------------------------------------
		/**
		 * Set setting values
		 * (you must call insert() or update() to write the settings to the database)
		 */
		public function setSettings($pa_settings) {
			foreach($pa_settings as $vs_setting => $vm_value) {
				$this->setSetting($vs_setting, $vm_value);
			}
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Set setting value 
		 * (you must call insert() or update() to write the settings to the database)
		 */
		public function setSetting($ps_setting, $pm_value) {
			if (!$this->isValidSetting($ps_setting)) { return null; }
			$va_setting_info = $this->getSettingInfo($ps_setting);
			if ($va_setting_info['displayType'] == DT_CHECKBOXES) { $pm_value = (int)$pm_value; }
			if (
				(isset($va_setting_info['useRelationshipTypeList']) && $va_setting_info['useRelationshipTypeList'])
				||
				(isset($va_setting_info['useList']) && $va_setting_info['useList'])
				||
				(isset($va_setting_info['showLists']) && $va_setting_info['showLists'])
				||
				(isset($va_setting_info['showVocabularies']) && $va_setting_info['showVocabularies'])
			) { 
				if (!is_array($pm_value)) { $pm_value = array($pm_value); }
				
				foreach($pm_value as $vn_i => $vm_value) {
					if (trim($vm_value) && !is_numeric($vm_value)) {
						// need to convert codes to ids
						if ($vs_t = $va_setting_info['useRelationshipTypeList']) {
							$t_rel = new ca_relationship_types();
							$pm_value[$vn_i] = $t_rel->getRelationshipTypeID($vs_t, $vm_value);
						} else {
							if ($vs_l = $va_setting_info['useList']) {
								// is a list
								$t_list = new ca_lists();
								$pm_value[$vn_i] = $t_list->getItemIDFromList($vs_l, $vm_value);
							} else {
								if ($va_setting_info['showLists'] || $va_setting_info['showVocabularies']) {
									// is a list
									$t_list = new ca_lists();
									$vn_list_id = null;
									if ($t_list->load(array('list_code' => $vm_value))) {
										$vn_list_id = $t_list->getPrimaryKey();
									} else {
										if ($t_list->load((int)$vm_value)) {
											$vn_list_id = $t_list->getPrimaryKey();
										}
									}
									
									if ($vn_list_id) {
										$pm_value[$vn_i] = $vn_list_id;
									}
								} else {
									if ($va_setting_info['showSortableBundlesFor']) {
										
									}
								}
							}
						}
					}
				}
			}
			$va_settings = $this->getSettings();
			
			if ($va_setting_info['formatType'] == FT_NUMBER) {
				$pm_value = (float)$pm_value;
			}
			
			$va_settings[$ps_setting] = $pm_value;
			$this->o_instance->set($this->ops_settings_field, $va_settings);
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Return setting value
		 */
		public function getSetting($ps_setting) {
			$va_settings = $this->getSettings();
			
			$vs_default = isset($this->opa_settings_defs[$ps_setting]['default']) ? $this->opa_settings_defs[$ps_setting]['default'] : null;
			return isset($va_settings[$ps_setting]) ? $va_settings[$ps_setting] : $vs_default;
		}
		# ------------------------------------------------------
		/**
		 * Returns true if setting code exists for the current element's datatype
		 */ 
		public function isValidSetting($ps_setting) {
			$va_settings = $this->getAvailableSettings();
			return (isset($va_settings[$ps_setting])) ? true : false;
		}
		# ------------------------------------------------------
		/**
		 * Returns HTML form for specifying settings for the currently loaded row
		 *
		 * @param array $pa_options Optional array of options. Support options are:
		 *		id = 
		 *		name = 
		 *		settings = 
		 * @return string HTML form
		 */
		public function getHTMLSettingForm($pa_options=null) {
			$vs_form = '';
			$va_form_elements = array();
			$va_settings = $this->getAvailableSettings();
			$va_setting_values = is_array($pa_options['settings']) ? $pa_options['settings'] : array();
			
			$va_options = array('id_prefix' => $pa_options['id']);
			foreach($va_settings as $vs_setting => $va_setting_info) {
				$va_options['id'] = $pa_options['id']."_{$vs_setting}";
				$va_options['label_id'] = $va_options['id'].'_label';
				if (!$pa_options['name']) { $pa_options['name'] = $pa_options['id']; }
				$va_options['name'] = $pa_options['name']."_{$vs_setting}";
				
				$va_options['value'] = isset($va_setting_values[$vs_setting]) ? $va_setting_values[$vs_setting] : $this->getSetting($vs_setting);
				$va_options['helpText'] = isset($va_setting_info['helpText']) ? $va_setting_info['helpText'] : '';
				
				$va_form_elements[] = $this->settingHTMLFormElement($vs_setting, $va_options);
			}
			
			return join("\n", $va_form_elements);
		}
		# ------------------------------------------------------
		/**
		 * Returns bundle HTML (using bundle view) for specifying settings for the currently loaded row
		 *
		 * @param HTTPRequest Request object
		 * @param array $pa_options Optional array of options. Support options are:
		 *		id = 
		 *		name = 
		 *		settings = 
		 * @return string HTML code for bundle
		 */
		public function getHTMLSettingFormBundle($po_request, $pa_options=null) {
				$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
				$o_view->setVar('t_subject', $this);
				
				return $o_view->render('settings.php');
		}
		# ------------------------------------------------------
		/**
		 * Returns HTML form element for editing of setting
		 *
		 * Options:
		 *
		 * 	'name' => sets the name of the HTML form element explicitly, otherwise 'setting_<name_of_setting>' is used
		 * 	'id' => sets the id of the HTML form element explicitly, otherwise 'setting_<name_of_setting>' is used
		 *  'value' => sets the value of the HTML form element explicitly, otherwise the current value for the setting in the loaded row is used
		 *  'label_id' => sets the id of the label for the setting form element (used to link tools tips to the label); if not set then the default is to set it to  'setting_<name_of_setting>_label'
		 */ 
		public function settingHTMLFormElement($ps_setting, $pa_options=null) {
			if(!$this->isValidSetting($ps_setting)) {
				return false;
			}
			$va_available_settings = $this->getAvailableSettings();
			
			$va_properties = $va_available_settings[$ps_setting];
			
			if(isset($pa_options['name'])) {
				$vs_input_name = $pa_options['name'];
			} else {
				$vs_input_name = "setting_{$ps_setting}";
			}
			if(isset($pa_options['id'])) {
				$vs_input_id = $pa_options['id'];
			} else {
				$vs_input_id = "setting_{$ps_setting}";
			}
			if(isset($pa_options['value'])) {
				$vs_value = $pa_options['value'];
			} else {
				$vs_value = $this->getSetting(trim($ps_setting));
			}
			if(isset($pa_options['label_id'])) {
				$vs_label_id = $pa_options['label_id'];
			} else {
				$vs_label_id = "setting_{$ps_setting}_label";
			}
			
			$vs_return = "\n".'<div class="formLabel" id="'.$vs_input_id.'_container">'."\n";
			$vs_return .= '<span id="'.$vs_label_id.'"  class="'.$vs_label_id.'">'.$va_properties['label'].'</span>';
			
			
			if ($vs_help_text = $pa_options['helpText']) {
				$vs_return .= "<a href='#' onclick='jQuery(\"#".str_replace(".", "_", $vs_label_id)."_help_text\").slideToggle(250); return false;' class='settingsKeyButton'>"._t('Key')."</a>";
			}
			
			$vs_return .= '<br />'."\n";
			
			
			if ($vs_help_text) {
				$vs_return .= "\n<div id='".str_replace(".", "_", $vs_label_id)."_help_text' class='settingsKey'>{$vs_help_text}</div>\n";
			}
			
			switch($va_properties['displayType']){
				# --------------------------------------------
				case DT_FIELD:
					$vb_takes_locale = false;
					if (isset($va_properties['takesLocale']) && $va_properties['takesLocale']) {
						$vb_takes_locale = true;
						$va_locales = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true, 'available_for_cataloguing_only' => true)); 
					} else {
						$va_locales = array('_generic' => array());
					}
					
					foreach($va_locales as $vs_locale => $va_locale_info) {
						if ($vb_takes_locale && (sizeof($va_locales) > 1)) { 
							$vs_locale_label = " (".$va_locale_info['name'].")";
							$vs_input_name_suffix = '_'.$vs_locale;
						} else {
							if ($vb_takes_locale) {
								$vs_input_name_suffix = '_'.$vs_locale;
							} else {
								$vs_input_name_suffix = $vs_locale_label = '';
							}
						}
						
						if (($vs_locale != '_generic') && (is_array($vs_value))) {		// _generic means this setting doesn't take a locale
							if (!($vs_text_value = $vs_value[$va_locale_info['locale_id']])) {
								$vs_text_value = (is_array($vs_value) && isset($vs_value[$va_locale_info['code']])) ? $vs_value[$va_locale_info['code']] : '';
							}
						} else {
							$vs_text_value = $vs_value;
						}
						$vs_return .= caHTMLTextInput($vs_input_name.$vs_input_name_suffix, array('size' => $va_properties["width"], 'height' => $va_properties["height"], 'value' => $vs_text_value, 'id' => $vs_input_id))."{$vs_locale_label}<br/>\n";	
					}
					break;
				# --------------------------------------------
				case DT_CHECKBOXES:
					$va_attributes = array('value' => '1', 'id' => $vs_input_id);
					if ((int)$vs_value === 1) {
						$va_attributes['checked'] = '1';
					}
					if (isset($va_properties['hideOnSelect'])) {
						if (!is_array($va_properties['hideOnSelect'])) { $va_properties['hideOnSelect'] = array($va_properties['hideOnSelect']); }
						
						$va_ids = array();
						foreach($va_properties['hideOnSelect'] as $vs_n) {
							$va_ids[] = "#".$pa_options['id_prefix']."_{$vs_n}_container";
						}
						$va_attributes['onchange'] = 'jQuery(this).prop("checked") ? jQuery("'.join(",", $va_ids).'").slideUp(250).find("input, textarea").val("") : jQuery("'.join(",", $va_ids).'").slideDown(250);';
						
					}
					$vs_return .= caHTMLCheckboxInput($vs_input_name, $va_attributes, array());
					break;
				
				# --------------------------------------------
				case DT_COLORPICKER:
					$va_attributes = array('value' => $vs_value, 'id' => $vs_input_id);
					$vs_return .= caHTMLHiddenInput($vs_input_name, $va_attributes, array());
					$vs_return .= "<div id='{$vs_input_id}_colorchip' class='colorpicker_chip' style='background-color: #{$vs_value}'><!-- empty --></div>";
					$vs_return .= "<script type='text/javascript'>jQuery(document).ready(function() { jQuery('#{$vs_input_name}_colorchip').ColorPicker({
								onShow: function (colpkr) {
									jQuery(colpkr).fadeIn(500);
									return false;
								},
								onHide: function (colpkr) {
									jQuery(colpkr).fadeOut(500);
									return false;
								},
								onChange: function (hsb, hex, rgb) {
									jQuery('#{$vs_input_name}').val(hex);
									jQuery('#{$vs_input_name}_colorchip').css('backgroundColor', '#' + hex);
								},
								color: jQuery('#".$pa_options["name"]."').val()
							})}); </script>\n";
							
							JavascriptLoadManager::register('jquery', 'colorpicker');
							
					break;
				# --------------------------------------------
				case DT_SELECT:
 					include_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
 					
 					$vn_width = (isset($va_properties['width']) && (strlen($va_properties['width']) > 0)) ? $va_properties['width'] : "100px";
					$vn_height = (isset($va_properties['height']) && (strlen($va_properties['height']) > 0)) ? $va_properties['height'] : "50px";
					
					$vs_select_element = '';
					if (($vs_rel_table = $va_properties['useRelationshipTypeList']) || ($vb_locale_list = (bool)$va_properties['useLocaleList']) || ($vs_list_code = $va_properties['useList']) || ($vb_show_lists = ((bool)$va_properties['showLists'] || (bool)$va_properties['showVocabularies']))) {
						if ($vs_rel_table) {
							$t_rel = new ca_relationship_types();
							$va_rels = $t_rel->getRelationshipInfo($vs_rel_table);
							
							$va_rel_opts = array();
							if (isset($va_properties['allowNull']) && $va_properties['allowNull']) {
								$va_rel_opts['-'] = null;
							}
							foreach($va_rels as $vn_type_id => $va_rel_type_info) {
								if (!$va_rel_type_info['parent_id']) { continue; }
								$va_rel_opts[$va_rel_type_info['typename'].'/'.$va_rel_type_info['typename_reverse']] = $va_rel_type_info['type_id'];
							}
						} else {
							if ($vb_locale_list) {
 								include_once(__CA_MODELS_DIR__.'/ca_locales.php');
 								$va_rel_opts = array_flip(ca_locales::getLocaleList(array('return_display_values' => true)));
							} else {
								if ($vb_show_lists) {
 									include_once(__CA_MODELS_DIR__.'/ca_lists.php');
									$t_list = new ca_lists();
									$va_lists = caExtractValuesByUserLocale($t_list->getListOfLists());
									
									$va_rel_opts = array();
									foreach($va_lists as $vn_list_id => $va_list_info) {
										if ($va_properties['showVocabularies'] && !$va_list_info['use_as_vocabulary']) { continue; }
										$va_rel_opts[$va_list_info['name'].' ('.$va_list_info['list_code'].')'] = $vn_list_id;
									}
								}
							}
						}
						
						$va_attr = array();
						if ($vn_height > 1) { 
							$va_attr['multiple'] = 1; $vs_input_name .= '[]'; 
						}
						
						$va_opts = array('id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height);
						if ($vn_height > 1) {
							if ($vs_value && !is_array($vs_value)) { $vs_value = array($vs_value); }
							$va_opts['values'] = $vs_value;
						} else {
							if (is_array($vs_value)) {
								$va_opts['value'] = array_pop($vs_value);
							} else {
								if ($vs_value) {
									$va_opts['value'] = $vs_value;
								} else {
									$va_opts['value'] = null;
								}
							}
						}
						
						if ($vs_list_code) {
							$t_list = new ca_lists();
							if(!isset($va_opts['value'])) { $va_opts['value'] = -1; }		// make sure default list item is never selected
							$vs_select_element = $t_list->getListAsHTMLFormElement($vs_list_code, $vs_input_name, $va_attr, $va_opts);
						} else {
							if(!isset($va_opts['value'])) { $va_opts['value'] = -1; }		// make sure default list item is never selected
							$vs_select_element = caHTMLSelect($vs_input_name, $va_rel_opts, $va_attr, $va_opts);
						}
					} else {
						if (strlen($va_properties['showSortableBundlesFor']) > 0) {
 							require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
 							
 							$o_dm = Datamodel::load();
 							if (!($t_rel = $o_dm->getInstanceByTableName($va_properties['showSortableBundlesFor'], true))) {
 								break;
 							}
							$va_elements = ca_metadata_elements::getSortableElements($va_properties['showSortableBundlesFor']);
							
							$va_select_opts = array(
								_t('User defined sort order') => '',
								_t('Order created') => 'relation_id',		// forces sorting by relationship primary key  - aka order relationships were created
								_t('Preferred label') => $va_properties['showSortableBundlesFor'].".preferred_labels.".$t_rel->getLabelDisplayField()
							);
							if ($vs_idno_fld = $t_rel->getProperty('ID_NUMBERING_ID_FIELD')) {
								$va_select_opts[$t_rel->getFieldInfo($vs_idno_fld, 'LABEL')] = $vs_idno_fld;
							}
							
							foreach($va_elements as $vn_element_id => $va_element) {
								if(!$va_element['display_label']) { continue; }
								$va_select_opts[_t('Element: %1', $va_element['display_label'])] = $va_properties['showSortableBundlesFor'].".".$va_element['element_code'];
							}
							
							$va_opts = array('id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height, 'value' => is_array($vs_value) ? $vs_value[0] : $vs_value, 'values' => is_array($vs_value) ? $vs_value : array($vs_value));
							$vs_select_element = caHTMLSelect($vs_input_name, $va_select_opts, array(), $va_opts);
						} elseif((int)$va_properties['showSortableElementsFor'] > 0) {
							require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
							
 							$t_element = new ca_metadata_elements($va_properties['showSortableElementsFor']);
 							if (!$t_element->getPrimaryKey()) { return ''; }
 							$va_elements = $t_element->getElementsInSet();
							
							$va_select_opts = array(
								_t('Order created') => '',
							);
							foreach($va_elements as $vn_i => $va_element) {
								if ((int)$va_element['element_id'] == (int)$va_properties['showSortableElementsFor']) { continue; }
								if(!$va_element['display_label']) { continue; }
								$va_select_opts[_t('Element: %1', $va_element['display_label'])] = $va_element['element_code'];
							}
							
							$va_opts = array('id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height, 'value' => is_array($vs_value) ? $vs_value[0] : $vs_value, 'values' => is_array($vs_value) ? $vs_value : array($vs_value));
							$vs_select_element = caHTMLSelect($vs_input_name, $va_select_opts, array(), $va_opts);
						} elseif ((int)$va_properties['showMetadataElementsWithDataType'] > 0) {
							require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
							
							$va_rep_elements = ca_metadata_elements::getElementsAsList(true, $va_properties['table'], null, true, false, true, array($va_properties['showMetadataElementsWithDataType']));
							
							if (is_array($va_rep_elements)) {
								$va_select_opts = array();
								foreach($va_rep_elements as $vs_element_code => $va_element_info) {
									$va_select_opts[$va_element_info['display_label']] = $vs_element_code;
								}
								$va_opts = array('id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height, 'value' => is_array($vs_value) ? $vs_value[0] : $vs_value, 'values' => is_array($vs_value) ? $vs_value : array($vs_value));
								$vs_select_element = caHTMLSelect($vs_input_name, $va_select_opts, array(), $va_opts);
							}
						} else {
							// Regular drop-down with configured options
							if ($vn_height > 1) { $va_attr['multiple'] = 1; $vs_input_name .= '[]'; }
						
							$va_opts = array('id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height, 'value' => is_array($vs_value) ? $vs_value[0] : $vs_value, 'values' => is_array($vs_value) ? $vs_value : array($vs_value));
							if(!isset($va_opts['value'])) { $va_opts['value'] = -1; }		// make sure default list item is never selected
							$vs_select_element = caHTMLSelect($vs_input_name, $va_properties['options'], array(), $va_opts);
						}
					}
					
					
					if ($vs_select_element) { $vs_return .= $vs_select_element; } else { return ''; }
					break;
				# --------------------------------------------
				default:
					break;
				# --------------------------------------------
			}
			
			
			$vs_return .= '</div>'."\n";
			TooltipManager::add('.'.$vs_label_id, "<h3>".$va_properties["label"]."</h3>".$va_properties["description"]);
	
			return $vs_return;
		}
		# ------------------------------------------------------
		/**
		 * Sets and saves form element settings, taking parameters off of the request as needed. Does an update()
		 * on the ca_search_forms instance to save settings to the database
		 */ 
		public function setSettingsFromHTMLForm($po_request) {
			$va_locales = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true, 'available_for_cataloguing_only' => true)); 
			$va_available_settings = $this->getAvailableSettings();

			$this->o_instance->setMode(ACCESS_WRITE);
			$va_values = array();
			foreach(array_keys($va_available_settings) as $vs_setting) {
				$va_properties = $va_available_settings[$vs_setting];
				if (isset($va_properties['takesLocale']) && $va_properties['takesLocale']) {
					foreach($va_locales as $vs_locale => $va_locale_info) {
						$va_values[$vs_setting][$va_locale_info['locale_id']] = $po_request->getParameter('setting_'.$vs_setting.'_'.$vs_locale, pString);
					}
				} else {
					if (
						(isset($va_properties['useRelationshipTypeList']) && $va_properties['useRelationshipTypeList'] && ($va_properties['height'] > 1))
						||
						(isset($va_properties['useList']) && $va_properties['useList'] && ($va_properties['height'] > 1))
						||
						(isset($va_properties['showLists']) && $va_properties['showLists'] && ($va_properties['height'] > 1))
						||
						(isset($va_properties['showVocabularies']) && $va_properties['showVocabularies'] && ($va_properties['height'] > 1))
					) {
						$va_values[$vs_setting] = $po_request->getParameter('setting_'.$vs_setting, pArray);
					} else {
						$va_values = array(
							$vs_setting => $po_request->getParameter('setting_'.$vs_setting, pString)
						);
					}
				}
				
				foreach($va_values as $vs_setting_key => $vs_value) {
					$this->setSetting($vs_setting, $vs_value);
				}
			}
			return $this->o_instance->update();
		}
		# ------------------------------------------------------
	}
?>