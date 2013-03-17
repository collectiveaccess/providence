<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Export/ExportRefineryManager.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/Export/BaseExportRefinery.php');
 
	class ExportRefineryManager {
		# -------------------------------------------------------
		/** 
		 * @var Global flag indicating whether we've required() widgets yet
		 */
		static $s_did_do_refinery_init = false;
		
		static $s_refinery_instances = array();
		# -------------------------------------------------------
		public function __construct() {
			ExportRefineryManager::initRefineries();
		}
		# -------------------------------------------------------
		#
		# -------------------------------------------------------
		/**
		 * Loads refineries
		 */
		public static function initRefineries() {
			if (ExportRefineryManager::$s_did_do_refinery_init) { return true; }
			
			$o_config = Configuration::load();
			$vs_base_refinery_dir = __CA_LIB_DIR__.'/ca/Export/ExportRefineries';
			
			$va_refinery_dirs = ExportRefineryManager::getRefineryNames();
			foreach($va_refinery_dirs as $vs_refinery_dir) {
				if (!file_exists($vs_base_refinery_dir.'/'.$vs_refinery_dir.'/'.$vs_refinery_dir.'Refinery.php')) { continue; }
				require_once($vs_base_refinery_dir.'/'.$vs_refinery_dir.'/'.$vs_refinery_dir.'Refinery.php');
				$vs_refinery_classname = $vs_refinery_dir.'Refinery';
				
				$o_instance = new $vs_refinery_classname($vs_base_refinery_dir.'/'.$vs_refinery_dir, array());
				
				$va_status = $o_instance->checkStatus();
				
				if (!isset($va_status['available']) || !$va_status['available']) { continue;}

				
				ExportRefineryManager::$s_refinery_instances[$vs_refinery_dir] = $o_instance;	
			}
			
			ExportRefineryManager::$s_did_do_refinery_init = true;
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public static function getRefineryInstance($ps_refinery_name) {
			ExportRefineryManager::initRefineries();
			if (isset(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]) && ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]) {
				return ExportRefineryManager::$s_refinery_instances[$ps_refinery_name];
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all refineries
		 */
		public static function getRefineryNames() {
			$vs_base_refinery_dir = __CA_LIB_DIR__.'/ca/Export/ExportRefineries';
			
			$va_refinery_dirs = array();
			if (is_resource($r_dir = opendir($vs_base_refinery_dir))) {
				while (($vs_refinery = readdir($r_dir)) !== false) {
					if (is_dir($vs_base_refinery_dir.'/'.$vs_refinery) && preg_match("/^[A-Za-z_]+[A-Za-z0-9_]*$/", $vs_refinery)) {
						$va_refinery_dirs[] = $vs_refinery;
					}
				}
			}
			
			sort($va_refinery_dirs);
			
			return $va_refinery_dirs;
		}
		# ----------------------------------------------------------
		/**
		 * Return status info for specified refinery
		 */
		public static function checkRefineryStatus($ps_refinery_name) {
			ExportRefineryManager::initRefineries();
			
			if(isset(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]) && is_object(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name])) {
				return ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]->checkStatus();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return display title for specified refinery
		 */
		public static function getRefineryTitle($ps_refinery_name) {
			ExportRefineryManager::initRefineries();
			
			if(isset(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]) && is_object(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name])) {
				return ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]->getTitle();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return description for specified refinery
		 */
		public static function getRefineryDescription($ps_refinery_name) {
			ExportRefineryManager::initRefineries();
			
			if(isset(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]) && is_object(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name])) {
				return ExportRefineryManager::$s_refinery_instances[$ps_widget_name]->getDescription();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		public function getRefinerySettingsForm($ps_refinery_name, $ps_refinery_id, $pa_settings) {
			$vs_buf = '';
			ExportRefineryManager::initRefineries();
			if (ExportRefineryManager::$s_refinery_instances[$ps_refinery_name] && is_object(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name])) {
				$va_available_settings = ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]->getAvailableSettings();
				
				foreach($va_available_settings as $vs_setting_name => $va_setting_info) {
					// scope = "application" means value should be stored as an application-wide value using ApplicationVars.
					if (isset($va_setting_info['scope']) && ($va_setting_info['scope'] == 'application')) {		
						if (!$o_appvar) { $o_appvar = new ApplicationVars(); }	// get application vars
						$pa_settings[$vs_setting_name] = $o_appvar->getVar('export_refinery_settings_'.$ps_refinery_name.'_'.$vs_setting_name);
					}
					$vs_buf .= ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]->settingHTMLFormElement($ps_refinery_id, $vs_setting_name, array('value' => $pa_settings[$vs_setting_name]));
				}
			}
			return $vs_buf;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		public function getRefineryAvailableSettings($ps_refinery_name) {
			ExportRefineryManager::initRefineries();
			if (ExportRefineryManager::$s_refinery_instances[$ps_refinery_name] && is_object(ExportRefineryManager::$s_refinery_instances[$ps_refinery_name])) {
				return ExportRefineryManager::$s_refinery_instances[$ps_refinery_name]->getAvailableSettings();
			}
			
			return array();
		}
		# -------------------------------------------------------
	}
?>