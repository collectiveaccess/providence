<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ApplicationPluginManager.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 * @subpackage AppPlugin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 require_once(__CA_LIB_DIR__.'/ca/BaseApplicationPlugin.php');
 
	class ApplicationPluginManager {
		# -------------------------------------------------------
		 /** 
		  * @var Global flag indicating whether we've required() plugins yet
		  */
		static $s_application_plugin_manager_did_do_plugin_init = false;
		
		/** 
		  * 
		  */
		 static $s_application_plugin_instances = array();
		  
		  /** 
		  * 
		  */
		 static $s_application_plugin_hooks = array();

		# -------------------------------------------------------
		public function __construct() {
			if (ApplicationPluginManager::$s_application_plugin_manager_did_do_plugin_init) { return true; }
			ApplicationPluginManager::initPlugins();
		}
		# -------------------------------------------------------
		/**
		 * Catches and dispatches calls to hooks 
		 */
		public function __call($ps_hook_name, $pa_params) {
			$va_params = $va_old_params = $pa_params[0];
			foreach(ApplicationPluginManager::$s_application_plugin_hooks as $vs_plugin_name => $va_hooks) {
				if (isset(ApplicationPluginManager::$s_application_plugin_hooks[$vs_plugin_name][$ps_hook_name]) && ApplicationPluginManager::$s_application_plugin_hooks[$vs_plugin_name][$ps_hook_name]) {
					$va_params = ApplicationPluginManager::$s_application_plugin_instances[$vs_plugin_name]->$ps_hook_name($va_params);
			
					if (!is_array($va_params)) {
						if ($va_params) {
							// if returned value is true but not an array then we reload the original params for use with the next plugin
							$va_params = $va_old_params;
						} else {
							// if plugin returns false value then we abort processing - no further plugins are called
							return null;
						}
					}
					$va_old_params = $va_params;
				}
			}
			
			return $va_params;
		}
		# -------------------------------------------------------
		#
		# -------------------------------------------------------
		/**
		 * Loads plugins
		 */
		public static function initPlugins() {
			if (ApplicationPluginManager::$s_application_plugin_manager_did_do_plugin_init) { return true; }
			
			$o_config = Configuration::load();
			$vs_app_plugin_dir = $o_config->get('application_plugins');
			
			$va_app_plugin_dirs = ApplicationPluginManager::getPluginNames();
			foreach($va_app_plugin_dirs as $vs_plugin_dir) {
				if (!file_exists($vs_app_plugin_dir.'/'.$vs_plugin_dir.'/'.$vs_plugin_dir.'Plugin.php')) { continue; }
				require_once($vs_app_plugin_dir.'/'.$vs_plugin_dir.'/'.$vs_plugin_dir.'Plugin.php');
				$vs_plugin_classname = $vs_plugin_dir.'Plugin';
				
				$o_instance = new $vs_plugin_classname($vs_app_plugin_dir.'/'.$vs_plugin_dir);
				
				$va_status = $o_instance->checkStatus();
				
				if (!isset($va_status['available']) || !$va_status['available']) { continue;}
				
				$o_class_info = new ReflectionClass($vs_plugin_classname);
				
				ApplicationPluginManager::$s_application_plugin_hooks[$vs_plugin_dir] = array();
				if (is_array($va_method_list = $o_class_info->getMethods())) {
					foreach($va_method_list as $o_method) {
						if (!$o_method->isPublic()) { continue; }
						$vs_method_name = $o_method->getName();
						if (!preg_match('!^hook!', $vs_method_name)) { continue; }
						
						ApplicationPluginManager::$s_application_plugin_hooks[$vs_plugin_dir][$vs_method_name] = true;
					}
				}
				ApplicationPluginManager::$s_application_plugin_instances[$vs_plugin_dir] = $o_instance;	
			}
			
			ApplicationPluginManager::$s_application_plugin_manager_did_do_plugin_init = true;
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all application plugins
		 */
		public static function getPluginNames() {
			$o_config = Configuration::load();
			if (!($vs_app_plugin_dir = $o_config->get('application_plugins'))) { return array(); }
			
			$va_app_plugin_dirs = array();
			if (is_resource($r_dir = opendir($vs_app_plugin_dir))) {
				while (($vs_plugin_dir = readdir($r_dir)) !== false) {
					if (is_dir($vs_app_plugin_dir.'/'.$vs_plugin_dir) && preg_match("/^[A-Za-z_]+[A-Za-z0-9_]*$/", $vs_plugin_dir)) {
						$va_app_plugin_dirs[] = $vs_plugin_dir;
					}
				}
			}
			
			sort($va_app_plugin_dirs);
			
			return $va_app_plugin_dirs;
		}
		# ----------------------------------------------------------
		/**
		 * Return plugin status info for specified plugin
		 */
		public static function checkPluginStatus($ps_plugin_name) {
			ApplicationPluginManager::initPlugins();
			
			if(isset(ApplicationPluginManager::$s_application_plugin_instances[$ps_plugin_name]) && is_object(ApplicationPluginManager::$s_application_plugin_instances[$ps_plugin_name])) {
				return ApplicationPluginManager::$s_application_plugin_instances[$ps_plugin_name]->checkStatus();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/** 
		 * Returns list of user actions defined by all plugins
		 *
		 * @return array List of user actions keyed by action code
		 */
		static public function getPluginRoleActions() {
			$va_actions = array();
			
			$o_config = Configuration::load();
			$vs_app_plugin_dir = $o_config->get('application_plugins');
			
			$va_app_plugin_dirs = ApplicationPluginManager::getPluginNames();
			
			foreach($va_app_plugin_dirs as $vs_plugin_dir) {
				if (!file_exists($vs_app_plugin_dir.'/'.$vs_plugin_dir.'/'.$vs_plugin_dir.'Plugin.php')) { continue; }
				require_once($vs_app_plugin_dir.'/'.$vs_plugin_dir.'/'.$vs_plugin_dir.'Plugin.php');
				$vs_plugin_classname = $vs_plugin_dir.'Plugin';
			
				$va_actions = array_merge($va_actions, call_user_func(array($vs_plugin_classname, 'getRoleActionList')));
			}
			return $va_actions;
		}
		# -------------------------------------------------------
	}
?>