<?php
/** ---------------------------------------------------------------------
 * app/lib/ModelSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2021 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/View.php');

trait ModelSettings {
	# ------------------------------------------------------
	
	/**
	 *
	 */
	protected $SETTINGS_DEFS;
	
	/**
	 * Name of settings field in table - used by ModelSettings trait
	 */
	protected $SETTINGS_FIELD = 'settings';
	
	# ------------------------------------------------------
	# Settings
	# ------------------------------------------------------
	/**
	 * Returns an associative array with the setting values for this restriction
	 * The keys of the array are setting codes, the values are the setting values
	 */
	public function getSettings() {
		return $this->get($this->SETTINGS_FIELD);
	}
	# ------------------------------------------------------
	/**
	 * Sets and saves form element settings, taking parameters off of the request as needed. Does an update()
	 * on the ca_search_forms instance to save settings to the database
	 *
	 * @param RequestHTTP $po_request
	 * @param array|null $pa_options
	 * @return mixed
	 */
	public function setSettingsFromHTMLForm($po_request, $pa_options=null) {
		$va_locales = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true, 'available_for_cataloguing_only' => true));
		if(!is_array($va_available_settings = $this->getAvailableSettings())) { $va_available_settings = []; }

		$va_values = array();

		$vs_id_prefix = caGetOption('id', $pa_options, 'setting');
		$vs_placement_code = caGetOption('placement_code', $pa_options, '');

		foreach(array_keys($va_available_settings) as $vs_setting) {
			$va_properties = $va_available_settings[$vs_setting];
			if (isset($va_properties['takesLocale']) && $va_properties['takesLocale']) {
				foreach($va_locales as $vs_locale => $va_locale_info) {
					$va_values[$vs_setting][$va_locale_info['locale_id']] = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}_{$vs_locale}", pString);
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
					||
					(isset($va_properties['multiple']) && $va_properties['multiple'] && ($va_properties['height'] > 1))
				) {
					$va_values[$vs_setting] = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}", pArray);
				} else {
					switch($va_properties['formatType']) {
						case FT_BIT:
							// skip bits if they're not set in the form; otherwise they default to 'No' even
							// though the default might be set to 'Yes' the settings definition.
							$vs_val = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}", pString);
							if($vs_val == '') { continue(2); }

							$va_values[$vs_setting] = $vs_val;
							break;
						default:
							$va_values[$vs_setting] = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}", pString);
							break;
					}

				}
			}
		}
		foreach($va_values as $vs_setting_key => $vs_value) {
			$this->setSetting($vs_setting_key, $vs_value);
		}
		return $this->update();
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
		return $this->SETTINGS_DEFS;
	}
	# ------------------------------------------------------
	/**
	 * Sets the associative array of setting descriptions (but *not* the setting values)
	 * The keys of this array are the setting codes, the values associative arrays containing
	 * info about the setting itself (label, description type of value, how to display an entry element for the setting in a form)
	 */
	public function setAvailableSettings($pa_settings) {
		$this->SETTINGS_DEFS = $pa_settings;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function getSettingInfo($ps_setting) {
		return isset($this->SETTINGS_DEFS[$ps_setting]) ? $this->SETTINGS_DEFS[$ps_setting] : null;
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
		$va_settings = $this->getSettings();
		if (!is_array($va_settings)) { $va_settings = array(); }
		if (!$this->isValidSetting($ps_setting)) { return $va_settings; }
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
		
		if ($va_setting_info['formatType'] == FT_NUMBER) {
			$pm_value = (float)$pm_value;
		}
		
		$va_settings[$ps_setting] = $pm_value;
		
		$this->set($this->SETTINGS_FIELD, $va_settings);

		return $this->numErrors() ? false : $va_settings;
	}
	# ------------------------------------------------------
	/**
	 * Return setting value
	 *
	 * @param string $setting
	 * @param array $options Options include:
	 *		flatten = Return locale-aware settings as a string in the current user locale (or as close as possible to it). If not set, an array will be returned for locale-aware settings with keys for each supported locale. [Default is false]
	 *
	 * @return string|array
	 */
	public function getSetting(string $setting, ?array $options=null) {
		$settings = $this->getSettings();
		
		$default = isset($this->SETTINGS_DEFS[$setting]['default']) ? $this->SETTINGS_DEFS[$setting]['default'] : null;
		$v = isset($settings[$setting]) ? $settings[$setting] : $default;
		
		$i = $this->getSettingInfo($setting);
		if(!empty($i['takesLocale']) && $i['takesLocale'] && caGetOption('flatten', $options, false)) {
			$v = caExtractSettingsValueByUserLocale($setting, $settings);
		}
		return $v;
	}
	# ------------------------------------------------------
	/**
	 * Returns true if setting code exists for the current element's datatype
	 *
	 * @param string $setting
	 *
	 * @return bool
	 */ 
	public function isValidSetting(string $setting) : bool {
		$settings = $this->getAvailableSettings();
		return (isset($settings[$setting])) ? true : false;
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
		
		$po_request = 			caGetOption('request', $pa_options, null);
		$vs_id = 				caGetOption('id', $pa_options, null);
		
		$format = 				caGetOption('format', $pa_options, null);
		
		$vs_name = 				caGetOption('name', $pa_options, null);
		$vs_placement_code = 	caGetOption('placement_code', $pa_options, null);
		
		$va_options = array('request' => $po_request, 'id_prefix' => $vs_id, 'table' => caGetOption('table', $pa_options, null), 'relatedTable' => caGetOption('relatedTable', $pa_options, null));
		
		if (is_array($va_settings)) { 
			foreach($va_settings as $vs_setting => $va_setting_info) {
				if ($format) {
					$va_options['id'] = str_replace("^setting_name", $vs_setting, $format);
				} else {
					$va_options['id'] = "{$vs_id}_{$vs_setting}";
				}
				$va_options['label_id'] = $va_options['id'].'_label';
				if (!$vs_name) { $vs_name = $vs_id; }
				$va_options['name'] = $format ? $va_options['id'] : "{$vs_placement_code}{$vs_name}_{$vs_setting}";
			
				$va_options['value'] = caGetOption($vs_setting, $va_setting_values, $this->getSetting($vs_setting));
				$va_options['helpText'] = caGetOption('helpText', $va_setting_info, '');
			
				$va_form_elements[] = $this->settingHTMLFormElement($vs_setting, $va_options);
			}
		}
		return join("\n", $va_form_elements);
	}
	# ------------------------------------------------------
	/**
	 * Returns bundle HTML (using bundle view) for specifying settings for the currently loaded row
	 *
	 * @param HTTPRequest Request object
	 * @param string $ps_form_name
	 * @param string $ps_placement_code
	 * @param int $pn_table_num
	 * @param array $pa_options Optional array of options. Support options are:
	 *		id = 
	 *		name = 
	 *		settings = 
	 * @return string HTML code for bundle
	 */
	public function getHTMLSettingFormBundle($po_request, $ps_form_name, $ps_placement_code, $pn_table_num, $pa_options=null) {
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			$o_view->setVar('t_subject', $this);
			$o_view->setVar('table_num', $pn_table_num);
			$o_view->setVar('id_prefix', $ps_form_name);	
			$o_view->setVar('placement_code', $ps_placement_code);		
			$o_view->setVar('request', $po_request);	
		
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
		
		$po_request = caGetOption('request', $pa_options, null);
		
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
		
		$pb_no_container = caGetOption('noContainerDiv', $pa_options, false);
		
		$vs_return = $pb_no_container ? '' : "\n".'<div class="formLabel" id="'.$vs_input_id.'_container">'."\n";
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
			case DT_FILE_BROWSER:
				if ($po_request) {
					$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');	
					$o_view->setVar('id', $vs_input_id);	
					$o_view->setVar('defaultPath', $vs_value);
					
					$vs_return .= $o_view->render('settings_directory_browser_html.php');
				}
				break;
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
					} elseif ($vb_takes_locale) {
						$vs_input_name_suffix = '_'.$vs_locale;
					} else {
						$vs_input_name_suffix = $vs_locale_label = '';
					}
					
					if (($vs_locale != '_generic') && (is_array($vs_value))) {		// _generic means this setting doesn't take a locale
						if (!($vs_text_value = $vs_value[$va_locale_info['locale_id']])) {
							$vs_text_value = (is_array($vs_value) && isset($vs_value[$va_locale_info['code']])) ? $vs_value[$va_locale_info['code']] : '';
						}
					} else {
						$vs_text_value = $vs_value;
					}
					$vs_return .= ($vs_locale_label ? "{$vs_locale_label}<br/>" : "").caHTMLTextInput($vs_input_name.$vs_input_name_suffix, array('size' => $va_properties["width"], 'height' => $va_properties["height"], 'value' => $vs_text_value, 'id' => $vs_input_id.$vs_input_name_suffix))."<br/>\n";	
					
					if($va_properties['usewysiwygeditor']) {
						AssetLoadManager::register("ckeditor");
						
						$config = Configuration::load();
						if(!is_array($va_toolbar_config = $config->getAssoc('wysiwyg_editor_toolbar'))) { $va_toolbar_config = []; }
								
						$vs_width = $va_properties['width'];					
						if (!preg_match("!^[\d\.]+px$!i", $vs_width)) {
							$vs_width = ((int)$vs_width * 6)."px";
						}
						$vs_height = $va_properties['height'];
						if (!preg_match("!^[\d\.]+px$!i", $vs_height)) {
							$vs_height = ((int)$vs_height * 16)."px";
						}
						
						$vs_return .= "<script type='text/javascript'>jQuery(document).ready(function() {
						var ckEditor = CKEDITOR.replace( '{$vs_input_id}{$vs_input_name_suffix}',
						{
							toolbar : ".json_encode(array_values($va_toolbar_config)).",
							width: '{$vs_width}',
							height: '{$vs_height}',
							toolbarLocation: 'top',
							enterMode: CKEDITOR.ENTER_BR
						});
				
						ckEditor.on('instanceReady', function(){ 
							 ckEditor.document.on( 'keydown', function(e) {if (caUI && caUI.utils) { caUI.utils.showUnsavedChangesWarning(true); } });
						});
	});									
	</script>";
					}
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
				if (isset($va_properties['showOnSelect'])) {
					if (!is_array($va_properties['showOnSelect'])) { $va_properties['showOnSelect'] = array($va_properties['showOnSelect']); }
					
					$va_ids = array();
					foreach($va_properties['showOnSelect'] as $vs_n) {
						$va_ids[] = "#".$pa_options['id_prefix']."_{$vs_n}_container";
					}
					$va_attributes['onchange'] = 'jQuery(this).prop("checked") ? jQuery("'.join(",", $va_ids).'").slideDown(250).find("input, textarea").val("") : jQuery("'.join(",", $va_ids).'").slideUp(250);';
					
					if (!$va_attributes['checked']) {
						$vs_return .= "<script type='text/javascript'>
	jQuery(document).ready(function() {
		jQuery('".join(",", $va_ids)."').hide();
	});
	</script>\n";
					}
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
						
						AssetLoadManager::register('jquery', 'colorpicker');
						
				break;
			# --------------------------------------------
			case DT_SELECT:
				$vn_width = (isset($va_properties['width']) && (strlen($va_properties['width']) > 0)) ? $va_properties['width'] : "100px";
				$vn_height = (isset($va_properties['height']) && (strlen($va_properties['height']) > 0)) ? $va_properties['height'] : "50px";
				
				$vs_select_element = '';
				
				if($va_properties['showTypesForTable']) {
					$vs_select_element = '';
					if (!($t_show_types_for_table = Datamodel::getInstanceByTableName($va_properties['showTypesForTable'], true))) {
						break;
					}
					
					$va_type_opts = [];
					if ($t_show_types_for_table instanceof BaseRelationshipModel) { // interstitial type restriction incoming
						$va_rel_type_list = $t_show_types_for_table->getRelationshipTypes();
						if(!is_array($va_rel_type_list)) { break; }
						
						foreach($va_rel_type_list as $vn_type_id => $va_type_info) {
							if (!$va_type_info['parent_id']) { continue; }
							$va_type_opts[$va_type_info['typename'].'/'.$va_type_info['typename_reverse']] = $va_type_info['type_id'];
						}
					} elseif($t_show_types_for_table instanceof ca_representation_annotations) {
						$va_type_list = $t_show_types_for_table->getTypeList();
						foreach($va_type_list as $vn_type_id => $va_type_info) {
							$va_type_opts[$va_type_info['idno']] = $vn_type_id;
						}
					} else { // "normal" (list-based) type restriction
						$vs_select_element = $t_show_types_for_table->getTypeListAsHTMLFormElement($vs_input_name.(($vn_height > 1) ? '[]' : ''), ['multiple' => ($vn_height > 1), 'width' => $vn_width, 'height' => $vn_height, 'id' => $vs_input_id], ['values' => $vs_value, 'nullOption' => '', 'useDefaultWhenNull' => false]);
					}
					
					if($vs_select_element == '') {
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
						$vs_select_element = caHTMLSelect($vs_input_name, $va_type_opts, $va_attr, $va_opts);
					}
				} elseif (
					($vs_rel_table = $va_properties['useRelationshipTypeList']) || 
					($vb_locale_list = (bool)$va_properties['useLocaleList']) || 
					($vs_list_code = $va_properties['useList']) || 
					($vb_show_lists = ((bool)$va_properties['showLists'] || 
					(bool)$va_properties['showVocabularies'])) || 
					($vb_policy_list = (bool)$va_properties['useHistoryTrackingPolicyList']) ||
					($vb_referring_policy_list = (bool)$va_properties['useHistoryTrackingReferringPolicyList'])
				) {
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
							$va_rel_opts = array_flip(ca_locales::getLocaleList(array('return_display_values' => true)));
						} elseif($vb_policy_list) {
							$table = caGetOption('table', $pa_options, null);
							$rel_table = caGetOption('relatedTable', $pa_options, null);
							$policies = array_merge(
								ca_objects::getHistoryTrackingCurrentValuePolicies($table, ['uses' => $rel_table ? [$rel_table] : null]),
								ca_objects::getDependentHistoryTrackingCurrentValuePolicies($table)
							);
							
							$va_rel_opts = array_flip(array_map(function($v) {
								return $v['name'];
							}, $policies));
						} elseif($vb_referring_policy_list) {
							$va_rel_opts = array_flip(array_map(function($v) {
								return $v['name'];
							}, ca_objects::getDependentHistoryTrackingCurrentValuePolicies(caGetOption('table', $pa_options, null))));
						} else {
							if ($vb_show_lists) {
								$t_list = new ca_lists();
								$va_lists = caExtractValuesByUserLocale($t_list->getListOfLists());
								
								$va_rel_opts = array();
								if (isset($va_properties['allowNull']) && $va_properties['allowNull']) {
									$va_rel_opts['-'] = null;
								}
								if (isset($va_properties['allowAll']) && $va_properties['allowAll']) {
									$va_rel_opts[_t('All lists')] = '*';
								}
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
					if (is_array($va_properties['showSortableBundlesFor']) && (strlen($va_properties['showSortableBundlesFor']['table']) > 0)) {
						$va_select_opts = array_merge([
							_t('User defined sort order') => '',
							_t('Order created') => 'relation_id',
						], array_flip(caGetAvailableSortFields($va_properties['showSortableBundlesFor']['table'], null, ['includeInterstitialSortsFor' => $va_properties['showSortableBundlesFor']['relationship'], 'distinguishInterstitials' => true])));
						
						$va_select_opts = array_filter($va_select_opts, function($v) { return ($v !== '_natural'); });
						if($va_properties['showSortableBundlesFor']['table'] == 'ca_sets') {
							unset($va_select_opts[_t('User defined sort order')]);
						}
						
						if (sizeof($va_select_opts)) {	
							$va_select_attr = [];
							if($vn_height > 1) {
								$vs_input_name .= '[]'; 
								$va_select_attr = ['multiple' => 1];
							}
							$va_opts = ['id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height, 'multiple' => 1];
							if(is_array($vs_value) || (strlen($vs_value) > 0)) {
								$va_opts['value'] = is_array($vs_value) ? $vs_value[0] : $vs_value;
								$va_opts['values'] = is_array($vs_value) ? $vs_value : [$vs_value];
							}
							$vs_select_element = caHTMLSelect($vs_input_name, $va_select_opts, $va_select_attr, $va_opts);
						}
					} elseif ($va_properties['showMetadataElementsWithDataType']) {
						if (!is_array($va_properties['table'])) { $va_properties['table'] = [$va_properties['table']]; }
						
						$va_select_opts = [];
						foreach($va_properties['table'] as $vs_table) {
							$va_rep_elements = ca_metadata_elements::getElementsAsList(true, $vs_table, null, true, false, true, is_numeric($va_properties['showMetadataElementsWithDataType']) ? array($va_properties['showMetadataElementsWithDataType']) : null);
						
							if (is_array($va_rep_elements)) {
								foreach($va_rep_elements as $vs_element_code => $va_element_info) {
									$va_select_opts[$va_element_info['display_label']] = "{$vs_table}.{$vs_element_code}";
								}
							}
						
							if($va_properties['includeIntrinsics']) {
								if (!($t_rep = Datamodel::getInstance($vs_table, true))) { continue; }
						
								foreach($t_rep->getFormFields() as $vs_f => $va_field_info) {
									if (is_array($va_properties['includeIntrinsics']) && !in_array($vs_f, $va_properties['includeIntrinsics'])) { continue; }
									if(in_array($va_field_info['DT_DISPLAY'], array('DT_OMIT', 'DT_HIDDEN'))) { continue; }
									if (isset($va_field_info['IDENTITY']) && $va_field_info['IDENTITY']) { continue; }
								
									$va_select_opts[$va_field_info['LABEL']] = $vs_f;
									}	
								}
								if($va_properties['includePreferredLabels']) {
									if (!($t_rep = Datamodel::getInstance($vs_table, true))) { continue; }
									foreach($t_rep->getLabelUIFields() as $vs_f) {
										$va_select_opts[$t_rep->getDisplayLabel("{$vs_table}.preferred_labels.{$vs_f}")] = "{$vs_table}.preferred_labels.{$vs_f}";
									}	
								}
							}
							
							if (sizeof($va_select_opts)) {	
								
								$va_select_attr = [];
								if($vn_height > 1) {
									$vs_input_name .= '[]'; 
									$va_select_attr = ['multiple' => 1];
								}
								$va_opts = array('id' => $vs_input_id, 'width' => $vn_width, 'height' => $vn_height, 'value' => is_array($vs_value) ? $vs_value[0] : $vs_value, 'multiple' => 1, 'values' => is_array($vs_value) ? $vs_value : array($vs_value));
								$vs_select_element = caHTMLSelect($vs_input_name, $va_select_opts,  $va_select_attr, $va_opts);
							}
						} else {
							// Regular drop-down with configured options
							if ($vn_height > 1) { $va_attr['multiple'] = 1; $vs_input_name .= '[]'; }

							$va_opts = array('width' => $vn_width, 'height' => $vn_height, 'values' => is_array($vs_value) ? $vs_value : [$vs_value]);
							if(!isset($va_opts['value'])) { $va_opts['value'] = -1; }		// make sure default list item is never selected
							$va_attr['id'] = $vs_input_id;
							$vs_select_element = caHTMLSelect($vs_input_name, $va_properties['options'], $va_attr, $va_opts);
						}
					}
					
					
					if ($vs_select_element) { $vs_return .= $vs_select_element; } else { return ''; }
					break;
				# --------------------------------------------
				case DT_INTERVAL:
					$va_val_tmp = explode('|', $vs_value);
					if(isset($va_properties['prefix'])) { $vs_return .= $va_properties['prefix']; }
					$vs_return .= caHTMLHiddenInput($vs_input_name, ['value' => $vs_value, 'id' => $vs_input_id]);
					$vs_return .= caHTMLTextInput("{$vs_input_name}Quantity", ['size' => $va_properties["width"], 'height' => $va_properties["height"], 'value' => $va_val_tmp[0], 'id' => "{$vs_input_id}Quantity"]).' ';	
					$vs_return .= caHTMLSelect("{$vs_input_name}Units", [_t('hours') => 'HOURS', _t('days') => 'DAYS', _t('weeks') => 'WEEKS'], ['id' => "{$vs_input_id}Units"], ['value' => $va_val_tmp[1]]).' ';
					$vs_return .= caHTMLSelect("{$vs_input_name}Direction", [_t('before') => 'BEFORE', _t('after') => 'AFTER'],['id' => "{$vs_input_id}Direction"], ['value' => $va_val_tmp[2]]);
					if(isset($va_properties['suffix'])) { $vs_return .= $va_properties['suffix']; }
					$vs_return .= "
						<script type='text/javascript'>jQuery(document).ready(function() {
							jQuery('#{$vs_input_id}Quantity, #{$vs_input_id}Units, #{$vs_input_id}Direction').on('change', function() {
								var v = jQuery('#{$vs_input_id}Quantity').val() + '|' + jQuery('#{$vs_input_id}Units').val() + '|' + jQuery('#{$vs_input_id}Direction').val();
								jQuery('#{$vs_input_id}').val(v);
							});
						}); </script>
					";
					break;
				# --------------------------------------------
				default:
					break;
				# --------------------------------------------
			}
			
			
		$vs_return .= $pb_no_container ? '' : '</div>'."\n";
		TooltipManager::add('.'.$vs_label_id, "<h3>".$va_properties["label"]."</h3>".$va_properties["description"]);

		return $vs_return;
	}
	# ------------------------------------------------------
}
