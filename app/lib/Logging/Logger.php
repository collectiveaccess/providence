<?php
/** ---------------------------------------------------------------------
 * app/helpers/loggingHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2023 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */ 
 
  /**
   *
   */
   
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__."/Utils/CLIUtils.php");
require_once(__DIR__."/PriorityLog.php");
 
 # ----------------------------------------------------------------------
class Logger {
	# ----------------------------------------
	/**
	 *
	 */
	private $opo_replication_log = null;
	# ----------------------------------------
	/**
	 *
	 */
	public function __construct($ps_log_type, $pa_options=null) {
		$this->setLogger($ps_log_type, $pa_options);
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Log message with given log level
	 * @param string $ps_msg
	 * @param int $pn_level log level as PriorityLog level integer:
	 *        one of PriorityLog::DEBUG, PriorityLog::INFO, PriorityLog::WARN, PriorityLog::ERR
	 */
	public function log($ps_msg, $pn_level=PriorityLog::INFO) {
		if(!PriorityLog::isValidPriority($pn_level)){
			$pn_level = PriorityLog::INFO;
		}

		$this->opo_replication_log->log($ps_msg, $pn_level);

		switch($pn_level) {
			case PriorityLog::DEBUG:
				if(defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) {
					//print CLIUtils::textWithColor($ps_msg, 'purple') . PHP_EOL;
				}
				break;
			case PriorityLog::INFO:
				//print CLIUtils::textWithColor($ps_msg, 'green') . PHP_EOL;
				break;
			case PriorityLog::WARN:
				//print CLIUtils::textWithColor($ps_msg, 'yellow') . PHP_EOL;
				break;
			case PriorityLog::ERR:
				CLIUtils::addError($ps_msg);
				break;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * 
	 */
	public function setLogger($ps_log_type, $pa_options=null) {
	
		$vs_log = null;
		$vs_app = 'CollectiveAccess';
		
		switch($ps_log_type) {
			case 'replication':
				$o_replication_conf = Configuration::load('replication.conf');
				$vs_log = $o_replication_conf->get('replication_log')."_".__CA_APP_NAME__;
				$vs_app = 'CollectiveAccess Replicator';
				break;
			case 'replication_debug':
				$o_replication_conf = Configuration::load('replication.conf');
				$vs_log = $o_replication_conf->get('replication_debug_log')."_".__CA_APP_NAME__;
				$vs_app = 'CollectiveAccess Replicator';
				break;
		}
		
		$this->opo_replication_log = null;
		if($vs_log) {
			try {
				$this->opo_replication_log = new PriorityLog($vs_log, ['application' => $vs_app]);
			} catch (PriorityLogException $e) { // error while opening the file (usually permissions)
				//print CLIUtils::textWithColor("Couldn't open log file. Now logging via system log.", "bold_red") . PHP_EOL . PHP_EOL;
			}
		}

		// default: log everything to syslog
		if(!$this->opo_replication_log) {
			$this->opo_replication_log = new PriorityLog(null, ['application' => $vs_app]);
		}

		return $this->opo_replication_log;
	}
	# --------------------------------------------------------------------------------------------
}
