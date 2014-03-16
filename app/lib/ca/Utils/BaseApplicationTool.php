<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseApplicationTool.php : 
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
 * @subpackage AppPlugin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__.'/ca/Utils/IApplicationTool.php');
require_once(__CA_LIB_DIR__.'/ca/Utils/ApplicationToolSettings.php');
require_once(__CA_LIB_DIR__.'/core/Logging/KLogger/KLogger.php');
 
	abstract class BaseApplicationTool implements IApplicationTool {
		# -------------------------------------------------------
		
		/**
		 * Settings delegate - implements methods for setting, getting and using settings
		 */
		public $SETTINGS;
		
		/**
		 *
		 */
		protected $opa_available_settings = array();
		
		/**
		 *
		 */
		protected $ops_tool_name = null;
		
		
		/**
		 *
		 */
		protected $ops_tool_config_path = null;
		
		
		/**
		 *
		 */
		protected $ops_log_path = null;
		
		/**
		 *
		 */
		protected $opn_log_level = KLogger::NOTICE;
		
		/**
		 *
		 */
		protected $ops_description = '';
		
		/**
		 *
		 */
		protected $opo_app_config;
		
		/**
		 *
		 */
		protected $opo_config;
		
		/**
		 *
		 */
		protected $opo_datamodel;
		
		# -------------------------------------------------------
		/**
		 *
		 */
		public function __construct($pa_settings=null, $ps_tool_config_path=null, $ps_log_path=null) {
			$this->SETTINGS = new ApplicationToolSettings($this->opa_available_settings, array());
			
			$this->opo_datamodel = Datamodel::load();
			$this->opo_app_config = Configuration::load();
			
			if (is_array($pa_settings)) { $this->setSettings($pa_settings); }
			if ($ps_tool_config_path) { $this->ops_tool_config_path = $ps_tool_config_path; }
			
			if (!$ps_log_path) { $ps_log_path = __CA_APP_DIR__.'/log'; }
			if ($ps_log_path) { $this->setLogPath($ps_log_path); }
		}
		# -------------------------------------------------------
		/**
		 * Reroutes calls to method implemented by settings delegate to the delegate class
		 */
		public function getCommands() {
			$va_methods = get_class_methods($this);
			
			$va_commands = array();
			foreach($va_methods as $vs_method) {
				if (preg_match("!^command([A-Z]+.*)$!", $vs_method, $va_matches)) {
					$va_commands[] = $va_matches[1];
				}
			}
			return $va_commands;
		}
		# -------------------------------------------------------
		/**
		 * Reroutes calls to method implemented by settings delegate to the delegate class
		 */
		public function run($ps_command) {
			if (!method_exists($this, "command{$ps_command}")) { 
				throw new Exception(_t("Command %1 does not exist", $ps_command));
			}
			return call_user_func_array(array($this, "command{$ps_command}"), array());
		}
		# -------------------------------------------------------
		# Configuration
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getAppConfig() {
			return $this->opo_app_config;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getAppDatamodel() {
			return $this->opo_datamodel;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getToolConfig() {
			if(!file_exists($this->ops_tool_config_path)) { return null; }
			return Configuration::load($this->ops_tool_config_path);
		}
		# -------------------------------------------------------
		# Logging
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getLogger() {
			return (is_writable($this->ops_log_path)) ? new KLogger($this->ops_log_path, $this->opn_log_level) : null;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getLogLevel() {
			return $this->opn_log_level;
		}
		# -------------------------------------------------------
		/**
		 * 
		 * @param int $pn_log_level KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
		 *			KLogger::EMERG = Emergency messages (system is unusable)
		 *			KLogger::ALERT = Alert messages (action must be taken immediately)
		 *			KLogger::CRIT = Critical conditions
		 *			KLogger::ERR = Error conditions
		 *			KLogger::WARN = Warnings
		 *			KLogger::NOTICE = Notices (normal but significant conditions)
		 *			KLogger::INFO = Informational messages
		 *			KLogger::DEBUG = Debugging messages
		 *
		 * @return bool True if log level was set
		 */
		public function setLogLevel($pn_log_level) {
			$this->opn_log_level = $pn_log_level;
			return true;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getLogPath() {
			return $this->ops_log_path;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function setLogPath($ps_log_path) {
			if (is_writeable($ps_log_path)) { 
				$this->ops_log_path = $ps_log_path;
				return true;
			}
			return false;
		}
		# -------------------------------------------------------
		# Help
		# -------------------------------------------------------
		/**
		 * Return short help text about a tool command
		 *
		 * @return string 
		 */
		public function getShortHelpText($ps_command) {
			return _t('No help available for %1', $ps_command);
		}
		# -------------------------------------------------------
		/**
		 * Return full help text about a tool command
		 *
		 * @return string 
		 */
		public function getHelpText($ps_command) {
			return _t('No help available for %1', $ps_command);
		}
		# -------------------------------------------------------
		# Settings
		# -------------------------------------------------------
		/**
		 * Return application tool settings object
		 *
		 * @return ApplicationToolSettings settings object
		 */
		public function getToolSettings() {
			return $this->SETTINGS;
		}
		# -------------------------------------------------------
		/**
		 * Reroutes calls to method implemented by settings delegate to the delegate class
		 */
		public function __call($ps_name, $pa_arguments) {
			if (method_exists($this->SETTINGS, $ps_name)) {
				return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
			}
			die(_t("Method {$ps_name} is not implemented by application tool"));
		}
		# -------------------------------------------------------
	}
?>