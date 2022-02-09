<?php
/** ---------------------------------------------------------------------
 * app/helpers/logHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
   require_once(__CA_LIB_DIR__."/Logging/KLogger/KLogger.php");
	# ---------------------------------------
	/**
	 * Return KLogger instance for import log
	 *
	 * @param array $options Options include:
	 *
	 *                       logDirectory = Directory containing logs. [Default is to use app.conf
	 *                       batch_metadata_import_log_directory value]
	 *
	 *                       logLevel = KLogger numeric constant of string code for log level. Valid string codes are
	 *                       DEBUG, NOTICE, WARN, ERR, CRIT, ALERT and INFO. [Default is INFO]
	 *
	 *                       logToTempDirectoryIfLogDirectoryIsNotWritable = Log to system temporary directory if
	 *                       configured log directory is not writable. [Default is false]
	 *
	 * @return KLogger instance
	 * @throws ApplicationException
	 */
	function caGetImportLogger($options=null) {
		return caGetLogger($options, 'batch_metadata_import_log_directory');
	}
	# ---------------------------------------
	/**
	 * Return KLogger instance for log
	 *
	 * @param array $options Options include:
	 *
	 *						 logName = Filename for log
	 *
	 *                       logDirectory = Directory containing logs. [Default is to use app.conf $opt_name value]
	 *						 logName = Optional log name. [Default is to use a generic log name]
	 *
	 *                       logLevel = KLogger numeric constant of string code for log level. Valid string codes
	 *                       are DEBUG, NOTICE, WARN, ERR, CRIT, ALERT and INFO. [Default is INFO]
	 *
	 *                       logToTempDirectoryIfLogDirectoryIsNotWritable = Log to system temporary directory if
	 *                       configured log directory is not writable. [Default is false]
	 *
	 * @param string $opt_name Name of app.conf configuration entry to use for log directory. [Default is null - use current working directory]
	 *
	 * @return KLogger instance
	 * @throws ApplicationException
	 */
	function caGetLogger($options=null, $opt_name=null) {
		$log_dir = caGetLogPath($options, $opt_name);
		return new KLogger($log_dir, caLogLevelStringToNumber(caGetOption('logLevel', $options, 'INFO')), caGetOption('logName', $options, null));
	}
	# ---------------------------------------
	/**
	 *
	 */
	function caGetLogPath($options=null, $opt_name=null) {
		$config = Configuration::load();
		if(!trim($log_dir = $orig_log_dir = caGetOption('logDirectory', $options, $config->get($opt_name)))) {
			$log_dir = '.';
		}
		
		$tmp_dir = null;
		if (!is_writeable($log_dir)) {
			if (!caGetOption('logToTempDirectoryIfLogDirectoryIsNotWritable', $options, false)) {
				throw new ApplicationException(_t("Cannot write log to %1. Please check the directory's permissions and retry.", $log_dir));
			} elseif(is_writable($tmp_dir = caGetTempDirPath())) {
				$log_dir = $tmp_dir;
			} else {
				throw new ApplicationException(_t("Cannot write log to %1 or temporary directory %2. Please check directory permissions and retry.", $log_dir, $tmp_dir));
			}
		}
		return $log_dir;
	}
	# ---------------------------------------
	/**
	 * Convert text codes to KLogger constants
	 *
	 * @param string $log_level Log level string. Valid values are DEBUG, NOTICE, WARN, ERR, CRIT, ALERT and INFO.
	 *
	 * @return int
	 */
	function caLogLevelStringToNumber($log_level) {
		require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');
		
		if (is_numeric($log_level)) {
			$log_level = (int)$log_level;
		} else {
			switch($log_level) {
				case 'DEBUG':
					$log_level = KLogger::DEBUG;
					break;
				case 'NOTICE':
					$log_level = KLogger::NOTICE;
					break;
				case 'WARN':
					$log_level = KLogger::WARN;
					break;
				case 'ERR':
					$log_level = KLogger::ERR;
					break;
				case 'CRIT':
					$log_level = KLogger::CRIT;
					break;
				case 'ALERT':
					$log_level = KLogger::ALERT;
					break;
				default:
				case 'INFO':
					$log_level = KLogger::INFO;
					break;
			}
		}
		return $log_level;
	}
	# ----------------------------------------
