<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseApplicationTool.php : 
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
 
require_once(__CA_LIB_DIR__.'/Utils/IApplicationTool.php');
require_once(__CA_LIB_DIR__.'/Utils/ApplicationToolSettings.php');
require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');
require_once(__CA_LIB_DIR__.'/ProgressBar.php');
 
abstract class BaseApplicationTool implements IApplicationTool {
	# -------------------------------------------------------
	
	/**
	 * Settings delegate - implements methods for setting, getting and using settings
	 */
	public $SETTINGS;
	
	/**
	 * Array of settings for this tool. Set by subclass
	 */
	protected $opa_available_settings = array();
	
	/**
	 * Tool identifier. Must be unique to tool.
	 */
	protected $ops_tool_id = null;
	
	/**
	 * Name of tool. Must be unique to tool.
	 */
	protected $ops_tool_name = null;
	
	/**
	 * Path to tool configuration file
	 */
	protected $ops_tool_config_path = null;
		
	/**
	 * Path to tool log directory
	 */
	protected $ops_log_path = null;
	
	/**
	 * Current logging level
	 */
	protected $opn_log_level = KLogger::NOTICE;
	
	/**
	 * Description of tool for display
	 */
	protected $ops_description = '';
	
	/**
	 * Application Configuration object
	 */
	protected $opo_app_config;
	
	/**
	 * Tool Configuration object
	 */
	protected $opo_config;
		
	/**
	 * Tool run mode. Either CLI (command line) or WebUI (via web-based user interface)
	 */
	protected $ops_mode;	// CLI or WebUI
	
	/**
	 * 
	 */
	protected $ops_job_id;	
	
	# -------------------------------------------------------
	/**
	 * Set up tool environment.
	 *
	 * @param array $settings Values to initialize settings with.
	 * @param string $mode Tool run mode. Either CLI (command line) or WebUI (via web-based user interface).
	 * @param $tool_config_path Path to tool configuration file.
	 * @param $log_path Path to tool log directory. If omitted log are placed in default app/log directory.
	 */
	public function __construct($settings=null, $mode='CLI', $tool_config_path=null, $log_path=null) {
		$this->SETTINGS = new ApplicationToolSettings($this->opa_available_settings, array());
		
		$this->opo_app_config = Configuration::load();
		
		if (is_array($settings)) { $this->setSettings($settings); }
		if ($tool_config_path) { $this->ops_tool_config_path = $tool_config_path; }
		
		if (!$log_path) { $log_path = __CA_APP_DIR__.'/log'; }
		if ($log_path) { $this->setLogPath($log_path); }
		if ($mode) { $this->setMode($mode); }
	}
	# -------------------------------------------------------
	/**
	 * Return list of command supported by the tool
	 *
	 * @return array
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
	 *
	 */
	public function setJobID(string $job_id=null, ?array $options=null) {
		$this->ops_job_id = ($job_id) ? $job_id : md5(caGetOption('data', $options, '').'_'.$this->getToolIdentifier().'_'.uniqid(rand(), true).'_'.microtime(true));
		
		return $this->ops_job_id;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getJobID() {
		if (!$this->ops_job_id) { $this->setJobID(); }
		
		return $this->ops_job_id;
	}
	# -------------------------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 *
	 * @param string $command The command to run
	 * @param array $options 
	 * @return bool True on success, false on failure
	 */
	public function run(string $command, ?array $options=null) {
		if (!method_exists($this, "command{$command}")) { 
			throw new Exception(_t("Command %1 does not exist", $command));
		}
		return call_user_func_array([$this, "command{$command}"], [$options]);
	}
	# -------------------------------------------------------
	# Configuration
	# -------------------------------------------------------
	/**
	 * Get application configuration instance
	 *
	 * @return Configuration
	 */
	public function getAppConfig() {
		return $this->opo_app_config;
	}
	# -------------------------------------------------------
	/**
	 * Get tool configuration instance
	 *
	 * @return Configuration
	 */
	public function getToolConfig() {
		if(!file_exists($this->ops_tool_config_path)) { return null; }
		return Configuration::load($this->ops_tool_config_path);
	}
	# -------------------------------------------------------
	# Logging
	# -------------------------------------------------------
	/**
	 * Get logger instance. Tools can use this to log activity.
	 *
	 * @return KLogger
	 */
	public function getLogger() {
		return (is_writable($this->ops_log_path)) ? new KLogger($this->ops_log_path, $this->opn_log_level) : null;
	}
	# -------------------------------------------------------
	/**
	 * Get current logging level
	 *
	 * @return int
	 */
	public function getLogLevel() {
		return $this->opn_log_level;
	}
	# -------------------------------------------------------
	/**
	 * Set current logging level 
	 *
	 * @param int $log_level KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
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
	public function setLogLevel(int $log_level) {
		$this->opn_log_level = $log_level;
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Get current log path
	 *
	 * @return string
	 */
	public function getLogPath() {
		return $this->ops_log_path;
	}
	# -------------------------------------------------------
	/**
	 * Set current log path. The path must exist and be writeable.
	 *
	 * @param string $log_path 
	 *
	 * @return bool True if path is valid and was set, false is path was not set.
	 */
	public function setLogPath(string $log_path) {
		if (is_writeable($log_path)) { 
			$this->ops_log_path = $log_path;
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	# Progress
	# -------------------------------------------------------
	/**
	 * Returns instance of progress bar for use by tool to convey current status to the user
	 *
	 * @param int $total The maximum value of the progress bar. Defaults to zero if omitted.
	 * @return ProgressBar
	 */
	public function getProgressBar(?int $total=null) {
		$o_progress = new ProgressBar($this->getMode(), $total, $this->ops_job_id);
		if ($this->getMode() == 'CLI') { $o_progress->set('outputToTerminal', true); }
		return $o_progress;
	}
	# -------------------------------------------------------
	/**
	 * Set run mode. This primarily affects how progress is conveyed to the user. Possible values
	 * are CLI (command line interface) and WebUI (web-based user interface).
	 *
	 * @param string $mode One of: CLI, WebUI
	 * @return bool True if value was valid and set, false if not.
	 */
	public function setMode(string $mode) {
		if (!in_array($mode, array('CLI', 'WebUI'))) { return false; }
		$this->ops_mode = $mode;
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Return current run mode.
	 *
	 * @return string One of: CLI, WebUI
	 */
	public function getMode() {
		return $this->ops_mode;
	}
	# -------------------------------------------------------
	# Help
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getToolName() {
		return $this->ops_tool_name;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getToolIdentifier() {
		return $this->ops_tool_id;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getToolDescription() {
		return $this->ops_description;
	}
	# -------------------------------------------------------
	/**
	 * Return short help text about a tool command
	 *
	 * @return string 
	 */
	public function getShortHelpText(string $command) {
		return _t('No help available for %1', $command);
	}
	# -------------------------------------------------------
	/**
	 * Return full help text about a tool command
	 *
	 * @return string 
	 */
	public function getHelpText(string $command) {
		return _t('No help available for %1', $command);
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
	public function __call($name, $arguments) {
		if (method_exists($this->SETTINGS, $name)) {
			return call_user_func_array(array($this->SETTINGS, $name), $arguments);
		}
		die(_t("Method {$name} is not implemented by application tool"));
	}
	# -------------------------------------------------------
}
