<?php
/** ---------------------------------------------------------------------
 * app/helpers/loggingHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
   
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__."/core/Zend/Log/Writer/Stream.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log/Writer/Syslog.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log/Formatter/Simple.php");
require_once(__CA_LIB_DIR__."/ca/Utils/CLIUtils.php");
 
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
	 * @param int $pn_level log level as Zend_Log level integer:
	 *        one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
	 */
	public function log($ps_msg, $pn_level=Zend_Log::INFO) {
		if(!in_array($pn_level, array(Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR))){
			$pn_level = Zend_Log::INFO;
		}

		$this->opo_replication_log->log($ps_msg, $pn_level);

		switch($pn_level) {
			case Zend_Log::DEBUG:
				if(defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) {
					//print CLIUtils::textWithColor($ps_msg, 'purple') . PHP_EOL;
				}
				break;
			case Zend_Log::INFO:
				//print CLIUtils::textWithColor($ps_msg, 'green') . PHP_EOL;
				break;
			case Zend_Log::WARN:
				//print CLIUtils::textWithColor($ps_msg, 'yellow') . PHP_EOL;
				break;
			case Zend_Log::ERR:
				CLIUtils::addError($ps_msg);
				break;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * 
	 */
	public function setLogger($ps_log_type, $pa_options=null) {
	
		$o_writer = $vs_log = null;
		
		switch($ps_log_type) {
			case 'replication':
				$o_replication_conf = Configuration::load(__CA_CONF_DIR__.'/replication.conf');
				$vs_log = $o_replication_conf->get('replication_log');
				$vs_app = 'CollectiveAccess Replicator';
				break;
				break;
		}
		
		if($vs_log) {
			try {
				$o_writer = new Zend_Log_Writer_Stream($vs_log);
				$o_writer->setFormatter(new Zend_Log_Formatter_Simple('%timestamp% %priorityName%: %message%' . PHP_EOL));
			} catch (Zend_Log_Exception $e) { // error while opening the file (usually permissions)
				$o_writer = null;
				//print CLIUtils::textWithColor("Couldn't open log file. Now logging via system log.", "bold_red") . PHP_EOL . PHP_EOL;
			}
		}

		// default: log everything to syslog
		if(!$o_writer) {
			$o_writer = new Zend_Log_Writer_Syslog(array('application' => $vs_app, 'facility' => LOG_USER));
			// no need for timestamps in syslog ... the syslog itsself provides that
			$o_writer->setFormatter(new Zend_Log_Formatter_Simple('%priorityName%: %message%'.PHP_EOL));
		}

		$this->opo_replication_log = new Zend_Log($o_writer);
		$this->opo_replication_log->setTimestampFormat('D Y-m-d H:i:s');
		
		return $this->opo_replication_log;
	}
	# --------------------------------------------------------------------------------------------
}