<?php
/** ---------------------------------------------------------------------
 * app/helpers/cliHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2017 Whirl-i-Gig
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
 * @subpackage helpers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

# ---------------------------------------
/**
 * Is ncurses library available?
 *
 * @return bool True if CLI should use ncurses
 */
function caCLIUseNcurses() {
	if (function_exists("ncurses_init")) { return true; }

	return false;
}
# ---------------------------------------------------------------------
/**
 * Log message through global Zend_Log facilities (usually set up in caSetupCLIScript())
 * @param string $ps_message the log message
 * @param int $pn_level log level as Zend_Log level integer:
 *        one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
 * @return bool success state
 */
function caCLILog($ps_message, $pn_level) {
	global $g_logger;
	if(!$g_logger instanceof Zend_Log) { return false; }

	if(!in_array($pn_level, array(Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR))){
		return false;
	}
	$g_logger->log($ps_message,$pn_level);
	return true;
}
# ---------------------------------------------------------------------
/**
 * Log error to console and log facilities and exit
 * @param string $ps_message the error message
 */
function caCLILogCritError($ps_message) {
	CLIUtils::addError("\t".$ps_message.PHP_EOL);
	caCLILog($ps_message, Zend_Log::ERR);
	exit(255);
}
# ---------------------------------------------------------------------
/**
 * Do general setup for a CLI script
 * @param array $pa_additional_parameters Additional command line parameters. You don't have to add
 * --log/-l for the log file and --log-level/-d for the Zend_Log log level. They're always set up automatically
 * @return Zend_Console_Getopt
 */
function caSetupCLIScript($pa_additional_parameters) {
	require_once(__CA_LIB_DIR__."/Zend/Console/Getopt.php");
	require_once(__CA_LIB_DIR__."/Zend/Log.php");
	require_once(__CA_LIB_DIR__."/Zend/Log/Writer/Stream.php");
	require_once(__CA_LIB_DIR__."/Zend/Log/Writer/Syslog.php");
	require_once(__CA_LIB_DIR__."/Zend/Log/Formatter/Simple.php");

	$va_available_cli_opts = array_merge(array(
		"log|l-s" => "Path to log file. If omitted, we log into the system log. Note that we don't log DEBUG messages into the system log, even when the log level is set to DEBUG.",
		"log-level|d-s" => "Log level"
	), $pa_additional_parameters);

	try {
		$o_opts = new Zend_Console_Getopt($va_available_cli_opts);
		$o_opts->parse();
	} catch(Exception $e) {
		die("Invalid command line options: ".$e->getMessage().PHP_EOL);
	}

	// set up logging
	$o_writer = null;
	if($vs_log = $o_opts->getOption('log')) {
		// log to file
		try {
			$o_writer = new Zend_Log_Writer_Stream($vs_log);
			$o_writer->setFormatter(new Zend_Log_Formatter_Simple('%timestamp% %priorityName%: %message%'.PHP_EOL));
		} catch (Zend_Log_Exception $e) { // error while opening the file (usually permissions)
			$o_writer = null;
			print CLIUtils::textWithColor("Couldn't open log file. Now logging via system log.", "bold_red").PHP_EOL.PHP_EOL;
		}
	}

	// default: log everything to syslog
	if(!$o_writer) {
		$o_writer = new Zend_Log_Writer_Syslog(array('application' => 'CollectiveAccess CLI', 'facility' => LOG_USER));
		// no need for timespamps in syslog ... the syslog itsself provides that
		$o_writer->setFormatter(new Zend_Log_Formatter_Simple('%priorityName%: %message%'.PHP_EOL));
	}

	// was a loglevel set via command line? -> add filter to Zend logger, otherwise use WARN
	$vs_level = $o_opts->getOption('log-level');
	switch($vs_level) {
		case 'ERR':
			$o_filter = new Zend_Log_Filter_Priority(Zend_Log::ERR);
			break;
		case 'DEBUG':
			$o_filter = new Zend_Log_Filter_Priority(Zend_Log::DEBUG);
			break;
		case 'INFO':
			$o_filter = new Zend_Log_Filter_Priority(Zend_Log::INFO);
			break;
		case 'WARN':
		default:
			$o_filter = new Zend_Log_Filter_Priority(Zend_Log::WARN);
			break;
	}

	// set up global logger. can be used by importing 'global $g_logger' anywhere, but it's recommended to use the caCLILog() helper instead
	global $g_logger;
	$g_logger = new Zend_Log($o_writer);
	$g_logger->setTimestampFormat('D Y-m-d H:i:s');
	$g_logger->addFilter($o_filter);

	return $o_opts;
}
# ---------------------------------------------------------------------
