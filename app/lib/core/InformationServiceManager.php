<?php
/** ---------------------------------------------------------------------
 * app/lib/core/InformationServiceManager.php.php : 
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
 
	class InformationServiceManager {
		# -------------------------------------------------------
		/** 
		 * @var Global flag indicating whether we've required() plugins yet
		 */
		static $s_information_service_manager_did_do_init = false;
		
		/** 
		 * 
		 */
		static $s_information_service_instances = array();
		# -------------------------------------------------------
		public function __construct() {
			InformationServiceManager::initInformationServices();
		}
		# -------------------------------------------------------
		#
		# -------------------------------------------------------
		/**
		 * Loads readers
		 */
		public static function initInformationServices() {
			if (InformationServiceManager::$s_information_service_manager_did_do_init) { return true; }
			
			$vs_base_service_dir = __CA_LIB_DIR__.'/core/Plugins/InformationService';
			
			$va_services = InformationServiceManager::getInformationServiceNames();
			foreach($va_services as $vs_service) {
				if ($vs_service == 'BaseInformationServicePlugin') { continue; }
				if (!file_exists("{$vs_base_service_dir}/{$vs_service}.php")) { continue; }
				require_once("{$vs_base_service_dir}/{$vs_service}.php");
				$vs_service_classname = "WLPlugInformationService{$vs_service}";
				$o_instance = new $vs_service_classname();
				
				$va_status = $o_instance->checkStatus();
				
				if (!isset($va_status['available']) || !$va_status['available']) { continue;}
				
				InformationServiceManager::$s_information_service_instances[$vs_service] = $o_instance;	
			}
			
			InformationServiceManager::$s_information_service_manager_did_do_init = true;
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all readers
		 */
		public static function getInformationServiceInstance($ps_service) {
			InformationServiceManager::initInformationServices();
			if (isset(InformationServiceManager::$s_information_service_instances[$ps_service]) && InformationServiceManager::$s_information_service_instances[$ps_service]) {
				return InformationServiceManager::$s_information_service_instances[$ps_service];
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all readers
		 */
		public static function getInformationServiceNames() {
			$vs_base_service_dir = __CA_LIB_DIR__.'/core/Plugins/InformationService';
			
			$va_services = array();
			if (is_resource($r_dir = opendir($vs_base_service_dir))) {
				while (($vs_service = readdir($r_dir)) !== false) {
					if ($vs_service == 'BaseInformationServicePlugin.php') { continue; }
					if (file_exists($vs_base_service_dir.'/'.$vs_service) && preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*)\.php$/", $vs_service, $va_matches)) {
						$va_services[] = $va_matches[1];
					}
				}
			}
			
			sort($va_services);
			
			return $va_services;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all readers
		 */
		public static function getInformationServiceNamesOptionList() {
			$va_names = InformationServiceManager::getInformationServiceNames();
			$va_options = array();
			foreach($va_names as $vs_service) {
				$va_options[InformationServiceManager::getInformationServiceDisplayName($vs_service)] = $vs_service;
			}
			return $va_options;
		}
		# ----------------------------------------------------------
		/**
		 * Return status info for specified reader
		 */
		public static function checkInformationServiceStatus($ps_service_name) {
			InformationServiceManager::initInformationServices();
			
			if(isset(InformationServiceManager::$s_information_service_instances[$ps_service_name]) && is_object(InformationServiceManager::$s_information_service_instances[$ps_service_name])) {
				return InformationServiceManager::$s_information_service_instances[$ps_service_name]->checkStatus();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return display display name for specified reader
		 */
		public static function getInformationServiceDisplayName($ps_service_name) {
			InformationServiceManager::initInformationServices();
			
			if(isset(InformationServiceManager::$s_information_service_instances[$ps_service_name]) && is_object(InformationServiceManager::$s_information_service_instances[$ps_service_name])) {
				return InformationServiceManager::$s_information_service_instances[$ps_service_name]->getDisplayName();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return description for specified refinery
		 */
		public static function getInformationServiceDescription($ps_service_name) {
			InformationServiceManager::initInformationServices();
			
			if(isset(InformationServiceManager::$s_information_service_instances[$ps_service_name]) && is_object(InformationServiceManager::$s_information_service_instances[$ps_service_name])) {
				return InformationServiceManager::$s_information_service_instances[$ps_service_name]->getDescription();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * 
		 * @param string $ps_format
		 * @return BaseInformationService 
		 */
		public static function getInformationServiceForFormat($ps_format) {
			InformationServiceManager::initInformationServices();
			
			$va_services = InformationServiceManager::getInformationServiceNames();
			foreach($va_services as $vs_service) {
				if ($o_reader = InformationServiceManager::getInformationServiceInstance($vs_service)) {
					if ($o_reader->canReadFormat($ps_format)) {
						return $o_reader;
					}
				}
			}
			
			return null;
		}
		# -------------------------------------------------------
	}
?>