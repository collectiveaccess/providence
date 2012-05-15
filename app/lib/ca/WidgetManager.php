<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/WidgetManager.php : 
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
 
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/ca/BaseWidget.php');
 	require_once(__CA_LIB_DIR__.'/core/ApplicationVars.php'); 	
 
	class WidgetManager {
		# -------------------------------------------------------
		/** 
		 * @var Global flag indicating whether we've required() widgets yet
		 */
		static $s_widget_manager_did_do_widget_init = false;
		
		/** 
		 * 
		 */
		static $s_widget_instances = array();
		/**
		 *
		 */
		static $s_widget_hooks = array();
		# -------------------------------------------------------
		public function __construct() {
			WidgetManager::initWidgets();
		}
		# -------------------------------------------------------
		#
		# -------------------------------------------------------
		/**
		 * Loads widgets
		 */
		public static function initWidgets() {
			if (WidgetManager::$s_widget_manager_did_do_widget_init) { return true; }
			
			$o_config = Configuration::load();
			$vs_base_widget_dir = $o_config->get('dashboard_widgets');
			
			$va_widget_dirs = WidgetManager::getWidgetNames();
			foreach($va_widget_dirs as $vs_widget_dir) {
				if (!file_exists($vs_base_widget_dir.'/'.$vs_widget_dir.'/'.$vs_widget_dir.'Widget.php')) { continue; }
				require_once($vs_base_widget_dir.'/'.$vs_widget_dir.'/'.$vs_widget_dir.'Widget.php');
				$vs_widget_classname = $vs_widget_dir.'Widget';
				
				$o_instance = new $vs_widget_classname($vs_base_widget_dir.'/'.$vs_widget_dir, array());
				
				$va_status = $o_instance->checkStatus();
				
				if (!isset($va_status['available']) || !$va_status['available']) { continue;}

				$o_class_info = new ReflectionClass($vs_widget_classname);

				WidgetManager::$s_widget_hooks[$vs_widget_dir] = array();
				if (is_array($va_method_list = $o_class_info->getMethods())) {
					foreach($va_method_list as $o_method) {
						if (!$o_method->isPublic()) { continue; }
						$vs_method_name = $o_method->getName();
						if (!preg_match('!^hook!', $vs_method_name)) { continue; }

						WidgetManager::$s_widget_hooks[$vs_widget_dir][$vs_method_name] = true;
					}
				}
				
				WidgetManager::$s_widget_instances[$vs_widget_dir] = $o_instance;	
			}
			
			WidgetManager::$s_widget_manager_did_do_widget_init = true;
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all widgets
		 */
		public static function getWidgetNames() {
			$o_config = Configuration::load();
			if (!($vs_base_widget_dir = $o_config->get('dashboard_widgets'))) { return array(); }
			
			$va_widget_dirs = array();
			if (is_resource($r_dir = opendir($vs_base_widget_dir))) {
				while (($vs_widget_dir = readdir($r_dir)) !== false) {
					if (is_dir($vs_base_widget_dir.'/'.$vs_widget_dir) && preg_match("/^[A-Za-z_]+[A-Za-z0-9_]*$/", $vs_widget_dir)) {
						$va_widget_dirs[] = $vs_widget_dir;
					}
				}
			}
			
			sort($va_widget_dirs);
			
			return $va_widget_dirs;
		}
		# ----------------------------------------------------------
		/**
		 * Return status info for specified widget
		 */
		public static function checkWidgetStatus($ps_widget_name) {
			WidgetManager::initWidgets();
			
			if(isset(WidgetManager::$s_widget_instances[$ps_widget_name]) && is_object(WidgetManager::$s_widget_instances[$ps_widget_name])) {
				return WidgetManager::$s_widget_instances[$ps_widget_name]->checkStatus();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return display title for specified widget
		 */
		public static function getWidgetTitle($ps_widget_name) {
			WidgetManager::initWidgets();
			
			if(isset(WidgetManager::$s_widget_instances[$ps_widget_name]) && is_object(WidgetManager::$s_widget_instances[$ps_widget_name])) {
				return WidgetManager::$s_widget_instances[$ps_widget_name]->getTitle();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return description for specified widget
		 */
		public static function getWidgetDescription($ps_widget_name) {
			WidgetManager::initWidgets();
			
			if(isset(WidgetManager::$s_widget_instances[$ps_widget_name]) && is_object(WidgetManager::$s_widget_instances[$ps_widget_name])) {
				return WidgetManager::$s_widget_instances[$ps_widget_name]->getDescription();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Render a widget with the specified settings and return output
		 *
		 * @param string $ps_widget_name Name of widget
		 * @param string $ps_widget_id Unique identifer for placement of widget in dashboard used internally (is 32 character MD5 hash in case you care)
		 * @param array $pa_settings Array of settings for widget instance, as defined by widget's entry in BaseWidget::$s_widget_settings
		 *
		 * @return string Widget output on success, null on failure
		 */
		public function renderWidget($ps_widget_name, $ps_widget_id, $pa_settings=null) {
			WidgetManager::initWidgets();
			if (WidgetManager::$s_widget_instances[$ps_widget_name] && is_object(WidgetManager::$s_widget_instances[$ps_widget_name])) {
				return WidgetManager::$s_widget_instances[$ps_widget_name]->renderWidget($ps_widget_id, $pa_settings);
			}
			return null;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		public function getWidgetSettingsForm($ps_widget_name, $ps_widget_id, $pa_settings) {
			$vs_buf = '';
			WidgetManager::initWidgets();
			if (WidgetManager::$s_widget_instances[$ps_widget_name] && is_object(WidgetManager::$s_widget_instances[$ps_widget_name])) {
				$va_available_settings = WidgetManager::$s_widget_instances[$ps_widget_name]->getAvailableSettings();
				
				foreach($va_available_settings as $vs_setting_name => $va_setting_info) {
					// scope = "application" means value should be stored as an application-wide value using ApplicationVars.
					if (isset($va_setting_info['scope']) && ($va_setting_info['scope'] == 'application')) {		
						if (!$o_appvar) { $o_appvar = new ApplicationVars(); }	// get application vars
						$pa_settings[$vs_setting_name] = $o_appvar->getVar('widget_settings_'.$ps_widget_name.'_'.$vs_setting_name);
					}
					$vs_buf .= WidgetManager::$s_widget_instances[$ps_widget_name]->settingHTMLFormElement($ps_widget_id, $vs_setting_name, array('value' => $pa_settings[$vs_setting_name]));
				}
			}
			return $vs_buf;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		public function getWidgetAvailableSettings($ps_widget_name) {
			WidgetManager::initWidgets();
			if (WidgetManager::$s_widget_instances[$ps_widget_name] && is_object(WidgetManager::$s_widget_instances[$ps_widget_name])) {
				return WidgetManager::$s_widget_instances[$ps_widget_name]->getAvailableSettings();
			}
			
			return array();
		}
		# -------------------------------------------------------
		/** 
		 * Returns list of user actions defined by all widgets
		 *
		 * @return array List of user actions keyed by action code
		 */
		static public function getWidgetRoleActions() {
			$va_actions = array();
			
			$o_config = Configuration::load();
			$vs_base_widget_dir = $o_config->get('dashboard_widgets');
			
			$va_widget_dirs = WidgetManager::getWidgetNames();
			foreach($va_widget_dirs as $vs_widget_dir) {
				if (!file_exists($vs_base_widget_dir.'/'.$vs_widget_dir.'/'.$vs_widget_dir.'Widget.php')) { continue; }
				require_once($vs_base_widget_dir.'/'.$vs_widget_dir.'/'.$vs_widget_dir.'Widget.php');
				$vs_widget_classname = $vs_widget_dir.'Widget';
			
				$va_actions = array_merge($va_actions, call_user_func(array($vs_widget_classname, 'getRoleActionList')));
			}
			return $va_actions;
		}
		# -------------------------------------------------------
		/**
		 * Catches and dispatches calls to hooks
		 */
		public function __call($ps_hook_name, $pa_params) {
			$va_params = $pa_params[0];
			foreach(WidgetManager::$s_widget_hooks as $vs_plugin_name => $va_hooks) {
				if (isset(WidgetManager::$s_widget_hooks[$vs_plugin_name][$ps_hook_name]) && WidgetManager::$s_widget_hooks[$vs_plugin_name][$ps_hook_name]) {
					$va_params = WidgetManager::$s_widget_instances[$vs_plugin_name]->$ps_hook_name($va_params);
					if (!is_array($va_params)) {
						if ($va_params) {
							// if returned value is true but not an array then we reload the original params for use with the next plugin
							$va_params = $pa_params[0];
						} else {
							// if plugin returns false value then we abort processing - no further plugins are called
							return null;
						}
					}

				}
			}

			return $va_params;
		}
		# -------------------------------------------------------
	}
?>