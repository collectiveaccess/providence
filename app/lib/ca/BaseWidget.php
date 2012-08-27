<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseWidget.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * @subpackage Dashboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
	require_once(__CA_LIB_DIR__.'/ca/IWidget.php');
 	require_once(__CA_LIB_DIR__.'/core/ApplicationVars.php'); 	
 
	abstract class BaseWidget implements IWidget {
		# -------------------------------------------------------
		protected $title = '';
		protected $description = '';
		protected $opo_view;
		protected $ops_widget_path;
		protected $opa_settings = array();
		protected $request;
		
		static $s_widget_settings;
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->ops_widget_path = $ps_widget_path;
			
			if (is_array($pa_setting)) {
				$this->opa_settings = $pa_settings;
			}
			
			if ((AppController::instanceExists()) && ($o_app = AppController::getInstance()) && ($o_req = $o_app->getRequest())) {
				$this->request = $o_req;	
			}
		}
		# -------------------------------------------------------
		/**
		 * Get request object for current request. Returns null if no request is available 
		 * (if, for example, the plugin is being run in a batch script - scripts don't use the request/response model)
		 *
		 * @return Request object or null if no request object is available
		 */
		public function getRequest() {
			return $this->request;
		}
		# -------------------------------------------------------
		/**
		 * Returns display title of the current widget. So long as your widget sets its title property
		 * you shouldn't need to override this method.
		 *
		 * @return string title of widget for display to end-users
		 */
		public function getTitle() {
			return isset($this->title) ? $this->title : '';
		}
		# -------------------------------------------------------
		/**
		 * Returns description of the current widget. So long as your widget sets its description property
		 * you shouldn't need to override this method.
		 *
		 * @return string description of widget for display to end-users
		 */
		public function getDescription() {
			return isset($this->description) ? $this->description : '';
		}
		# -------------------------------------------------------
		/**
		 * Returns current status of widget. Your widget needs to override this. The default
		 * implementation returns a status without errors but with the 'available' flag set to false (ie. widget isn't functional)
		 *
		 * @return array associative array indicating availability of widget, and any initialization errors and warnings. Also includes text description of widget for display.
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => false
			);
		}
		# -------------------------------------------------------
		/**
		 * Base class for widget renderer. Will set basic widget view variables such as DOM 'id', 'widget_id' and the 'settings' array
		 * as well as filling in default values for any unspecified widget settings
		 *
		 * @param $ps_widget_id string the unique id (actually an md5 hash) for the widget
		 * @param $pa_settings array An array of widget settings in a key-value format; this is passed by reference to this base class so it can modify the array for the caller with appropriate default values
		 * @return void
		 */
		 public function renderWidget($ps_widget_id, &$pa_settings) {
			$this->opo_view = new View($this->request, $this->ops_widget_path.'/views');
		 	$this->opo_view->setVar('widget_id', $ps_widget_id);
		 	
		 	$o_appvar = null;
		 	
		 	// Load default settings if needed
		 	if(is_array($va_settings = $this->getAvailableSettings(true))) {
				foreach($va_settings as $vs_setting_name => $va_setting_info) {
					if (!isset($pa_settings[$vs_setting_name])) {
						$pa_settings[$vs_setting_name] = $this->getSetting($vs_setting_name);
					}
					
					// scope="application" means this setting value should be coming from ApplicationVars; this means it is a value shared across all users of the application.
					if (isset($va_setting_info['scope']) && ($va_setting_info['scope'] == 'application')) {
						$vs_widget_name = preg_replace('!Widget$!', '', get_class($this));	// name is class name minus trailing "Widget"
						if (!$o_appvar) { $o_appvar = new ApplicationVars(); }
						$pa_settings[$vs_setting_name] = $o_appvar->getVar('widget_settings_'.$vs_widget_name.'_'.$vs_setting_name);
					}
				}
			}
				
			$this->opo_view->setVar('settings', $pa_settings);
		 }
		 # ------------------------------------------------------
		# Settings
		# ------------------------------------------------------
		/**
		 * Returns associative array of setting descriptions (but *not* the setting values)
		 * The keys of this array are the setting codes, the values associative arrays containing
		 * info about the setting itself (label, description type of value, how to display an entry element for the setting in a form)
		 */
		public function getAvailableSettings($pb_read_only=false) {
			$va_settings = self::$s_widget_settings[get_class($this)];
			
			$va_available_settings = array();
			if (is_array($va_settings)) {
				foreach($va_settings as $vs_setting => $va_setting_info) {
					if (!$pb_read_only && isset($va_setting_info['requires']) && $va_setting_info['requires']) {
						if ($this->request->user->canDoAction($va_setting_info['requires'])) {
							$va_available_settings[$vs_setting] = $va_setting_info;
						}
					} else {
						$va_available_settings[$vs_setting] = $va_setting_info;
					}
				}
			}
			return $va_available_settings;
		}
		# ------------------------------------------------------
		/**
		 * Returns an associative array with the setting values for this restriction
		 * The keys of the array are setting codes, the values are the setting values
		 */
		public function getSettings() {
			return $this->opa_settings;
		}
		# ------------------------------------------------------
		/**
		 * Sets widget settings using provided array
		 */
		public function setSettings($pa_settings) {
			$this->opa_settings = $pa_settings;
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Set setting value 
		 */
		public function setSetting($ps_setting, $pm_value) {
			if (!$this->isValidSetting($ps_setting)) { return null; }
			$va_settings = $this->getSettings();
			$va_settings[$ps_setting] = $pm_value;
			$this->setSettings($va_settings);
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Return setting value
		 */
		public function getSetting($ps_setting) {
			$va_settings = $this->getSettings();
			$va_setting_config = $this->getAvailableSettings();
			$vs_default = isset($va_setting_config[$ps_setting]['default']) ? $va_setting_config[$ps_setting]['default'] : null;
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
		 * Returns HTML form element for editing of setting
		 *
		 * Options:
		 *
		 * 	'name' => sets the name of the HTML form element explicitly, otherwise 'setting_<name_of_setting>' is used
		 *  'value' => sets the value of the HTML form element explicitly, otherwise the current value for the setting in the loaded row is used
		 */ 
		public function settingHTMLFormElement($ps_widget_id, $ps_setting, $pa_options=null) {
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
			if(isset($pa_options['value']) && !is_null($pa_options['value'])) {
				$vs_value = $pa_options['value'];
			} else {
				$vs_value = $this->getSetting(trim($ps_setting));
			}
			
			$vs_element = '';
			switch($va_properties['displayType']){
				# --------------------------------------------
				case DT_FIELD:
					$vb_takes_locale = false;
					if (isset($va_properties['takesLocale']) && $va_properties['takesLocale']) {
						$vb_takes_locale = true;
						$va_locales = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true)); 
					} else {
						$va_locales = array('_generic' => array());
					}
					
					foreach($va_locales as $vs_locale => $va_locale_info) {
						if ($vb_takes_locale) { 
							$vs_locale_label = " (".$va_locale_info['name'].")";
							$vs_input_name_suffix = '_'.$vs_locale;
						} else {
							$vs_input_name_suffix = $vs_locale_label = '';
						}
						
						$vs_element .= caHTMLTextInput($vs_input_name.$vs_input_name_suffix, array('size' => $va_properties["width"], 'height' => $va_properties["height"], 'value' => $vs_value, 'id' => $vs_input_name.$vs_input_name_suffix))."{$vs_locale_label}";	
						
						// focus code is needed by Firefox for some reason
						$vs_element .= "<script type='text/javascript'>jQuery('#".$vs_input_name.$vs_input_name_suffix."').click(function() { this.focus(); });</script>";
					}
					break;
				# --------------------------------------------
				case DT_CHECKBOXES:
					$va_attributes = array('value' => '1');
					if ($vs_value) {
						$va_attributes['checked'] = '1';
					}
					$vs_element .= caHTMLCheckboxInput($vs_input_name, $va_attributes);
					break;
				# --------------------------------------------
				case DT_SELECT:
					if (!is_array($va_properties['options'])) { $va_properties['options'] = array(); }
					$vs_element .= caHTMLSelect($vs_input_name, $va_properties['options'], array(), array('value' => $vs_value));
					break;
				# --------------------------------------------
				default:
					break;
				# --------------------------------------------
			}
			
			$vs_label = $va_properties['label'];
			$vb_element_is_part_of_label = false;
			if (strpos($vs_label, '^ELEMENT') !== false) {
				$vs_label = str_replace('^ELEMENT', $vs_element, $vs_label);
				$vb_element_is_part_of_label = true;
			}
			
			$vs_return = "\n".'<div class="formLabel" id="_widget_setting_'.$ps_setting.'_'.$ps_widget_id.'"><span>'.$vs_label.'</span>';
			if (!$vb_element_is_part_of_label) {
				$vs_return .= '<br />'.$vs_element;
			}
			$vs_return .= '</div>'."\n";
			TooltipManager::add('#_widget_setting_'.$ps_setting.'_'.$ps_widget_id, "<h3>".str_replace('^ELEMENT', 'X', $va_properties["label"])."</h3>".$va_properties["description"]);
	
			return $vs_return;
		}
		# -------------------------------------------------------
	}
?>