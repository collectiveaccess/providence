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
require_once(__CA_LIB_DIR__.'/Logging/PriorityLog.php');

# ---------------------------------------------------------------------
/**
 * Log message through global log facilities (usually set up in caSetupCLIScript())
 * @param string $ps_message the log message
 * @param int $pn_level log level as PriorityLog level integer:
 *        one of PriorityLog::DEBUG, PriorityLog::INFO, PriorityLog::WARN, PriorityLog::ERR
 * @return bool success state
 */
function caCLILog($ps_message, $pn_level) {
	global $g_logger;
	if(!$g_logger instanceof PriorityLog) { return false; }

	if(!PriorityLog::isValidPriority($pn_level)){
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
	caCLILog($ps_message, PriorityLog::ERR);
	exit(255);
}
# ---------------------------------------------------------------------
/**
 * Do general setup for a CLI script
 * @param array $pa_additional_parameters Additional command line parameters. You don't have to add
 * --log/-l for the log file and --log-level/-d for the log level. They're always set up automatically
 * @return CLIGetopt
 */
function caSetupCLIScript($pa_additional_parameters) {

	$va_available_cli_opts = array_merge(array(
		"log|l-s" => "Path to log file. If omitted, we log into the system log. Note that we don't log DEBUG messages into the system log, even when the log level is set to DEBUG.",
		"log-level|d-s" => "Log level"
	), $pa_additional_parameters);

	try {
		$o_opts = new CLIGetopt($va_available_cli_opts);
		$o_opts->parse();
	} catch(Exception $e) {
		die("Invalid command line options: ".$e->getMessage().PHP_EOL);
	}

	// was a loglevel set via command line? otherwise use WARN
	switch($o_opts->getOption('log-level')) {
		case 'ERR':
			$vn_priority = PriorityLog::ERR;
			break;
		case 'DEBUG':
			$vn_priority = PriorityLog::DEBUG;
			break;
		case 'INFO':
			$vn_priority = PriorityLog::INFO;
			break;
		case 'WARN':
		default:
			$vn_priority = PriorityLog::WARN;
			break;
	}

	// set up global logger. can be used by importing 'global $g_logger' anywhere, but it's recommended to use the caCLILog() helper instead
	global $g_logger;
	$g_logger = null;
	$va_log_options = ['application' => 'CollectiveAccess CLI', 'priority' => $vn_priority];
	if(($vs_log = $o_opts->getOption('log')) && is_string($vs_log)) {
		// log to file
		try {
			$g_logger = new PriorityLog($vs_log, $va_log_options);
		} catch (PriorityLogException $e) { // error while opening the file (usually permissions)
			print CLIUtils::textWithColor("Couldn't open log file. Now logging via system log.", "bold_red").PHP_EOL.PHP_EOL;
		}
	}

	// default: log everything to syslog
	if(!$g_logger) {
		$g_logger = new PriorityLog(null, $va_log_options);
	}

	return $o_opts;
}
# ---------------------------------------------------------------------

/**
 * Format a command line option for display
 *
 * @param $vs_opt_format
 * @param $vs_opt_desc
 */
function caFormatCmdOptionsForDisplay($vs_opt_format, $vs_opt_desc): string {
    if (in_array(substr($vs_opt_format, -2, 1), ['-', '='])) {
        $flagList  = substr($vs_opt_format, 0, -2);
    } else {
        $flagList = $vs_opt_format;
    }

    $va_tmp = explode("|", $flagList);
    $vs_alias = sizeof($va_tmp)>1 ? $va_tmp[1]: "";

    $vn_padding = 25;
    return "\t" . CLIUtils::textWithColor(str_pad("--" . $va_tmp[0] . " " . ($vs_alias ? "(-{$vs_alias})":""), $vn_padding), "red") . 
                    wordwrap($vs_opt_desc, 75, "\n\t" . str_repeat(" ", $vn_padding)) . "\n\n";
}
