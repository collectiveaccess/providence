<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Utils/CLIBaseUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2015 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__.'/core/Utils/CLIProgressBar.php');
require_once(__CA_APP_DIR__.'/helpers/CLIHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/mediaPluginHelpers.php');
require_once(__CA_LIB_DIR__."/core/Zend/Console/Getopt.php");
require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
require_once(__CA_LIB_DIR__."/ca/MediaContentLocationIndexer.php");

class CLIBaseUtils {
	# -------------------------------------------------------
	/**
	 * Errors
	 */
	private static $errors = array();

	/**
	 * Process messages
	 */
	private static $messages = array();

	/**
	 * ANSI foreground colors
	 */
	private static $ansiForegroundColors = array(
		'black' => '0;30',
		'dark_gray' => '1;30',
		'red' => '0;31',
		'bold_red' => '1;31',
		'green' => '0;32',
		'bold_green' => '1;32',
		'brown' => '0;33',
		'yellow' => '1;33',
		'blue' => '0;34',
		'bold_blue' => '1;34',
		'purple' => '0;35',
		'bold_purple' => '1;35',
		'cyan' => '0;36',
		'bold_cyan' => '1;36',
		'white' => '1;37',
		'bold_gray' => '0;37',
	);

	/**
	 * ANSI background colors
	 */
	private static $ansiBackgroundColors = array(
		'black' => '40',
		'red' => '41',
		'magenta' => '45',
		'yellow' => '43',
		'green' => '42',
		'blue' => '44',
		'cyan' => '46',
		'light_gray' => '47',
	);
	# -------------------------------------------------------
	/**
	 * Determines if CLIBaseUtils function should be presented as a command within caUtils
	 *
	 * @param string $ps_function_name The function to check
	 * @return bool True if the function is a caUtils command
	 */
	public static function isCommand($ps_function_name) {
		return (!in_array($ps_function_name, array(
			'isCommand', 'textWithColor', 'textWithBackgroundColor',
			'clearErrors', 'numErrors', 'getErrors', 'addError',
			'clearMessages', 'numMessages', 'getMessages', 'addMessage'
		)));
	}
	# -------------------------------------------------------
	/**
	 *  Clear list of current messages
	 *
	 * @return bool Always returns true
	 */
	public static function clearMessages() {
		CLIBaseUtils::$messages = array();
		return true;
	}
	# -------------------------------------------------------
	/**
	 *  Get count of current messages
	 *
	 * @return int Number of messages currently posted
	 */
	public static function numMessages() {
		return sizeof(CLIBaseUtils::$messages);
	}
	# -------------------------------------------------------
	/**
	 *  Get list of posted messages
	 *
	 * @return array List of messages
	 */
	public static function getMessages() {
		return CLIBaseUtils::$messages;
	}
	# -------------------------------------------------------
	/**
	 *  Add message to message list
	 *
	 * @param string $ps_message Message to post
	 * @param array $pa_options Options are:
	 *		dontOutput = if set message is not output to screen. Default is false.
	 *		color = color to render message in. Default is none.
	 * @return bool Always returns true
	 */
	public static function addMessage($ps_message, $pa_options=null) {
		$pb_dont_output = caGetOption('dontOutput', $pa_options, false);
		$ps_color = caGetOption('color', $pa_options, null);
		if (!$pb_dont_output) {
			if ($ps_color && (strtolower($ps_color) !== 'none')) {
				print CLIBaseUtils::textWithColor($ps_message, $ps_color)."\n";
			} else {
				print "{$ps_message}\n";
			}
		}
		array_push(CLIBaseUtils::$messages, $ps_message);
		return true;
	}
	# -------------------------------------------------------
	/**
	 *  Clear list of current errors
	 *
	 * @return bool Always returns true
	 */
	public static function clearErrors() {
		CLIBaseUtils::$errors = array();
		return true;
	}
	# -------------------------------------------------------
	/**
	 *  Get count of current errors
	 *
	 * @return int Number of errors currently posted
	 */
	public static function numErrors() {
		return sizeof(CLIBaseUtils::$errors);
	}
	# -------------------------------------------------------
	/**
	 *  Get list of error messages
	 *
	 * @return array List of messages for current errors
	 */
	public static function getErrors() {
		return CLIBaseUtils::$errors;
	}
	# -------------------------------------------------------
	/**
	 *  Add error to current error list
	 *
	 * @param string $ps_error Error message to post
	 * @param array $pa_options Options are:
	 *		dontOutput = if set error is not output to screen. Default is false.
	 * @return bool Always returns true
	 */
	public static function addError($ps_error, $pa_options=null) {
		if (!is_array($pa_options) || !isset($pa_options['dontOutput']) || !$pa_options['dontOutput']) {
			print CLIBaseUtils::textWithColor($ps_error, "red")."\n";
		}
		array_push(CLIBaseUtils::$errors, $ps_error);
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Return text in ANSI color
	 *
	 * @param string $ps_string The string to output
	 * @param string $ps_color The color to output $ps_string in. Colors are defined in CLIBaseUtils::ansiForegroundColors
	 * @return string The string with ANSI color codes. If $ps_color is invalid the original string will be returned without ANSI codes.
	 */
	public static function textWithColor($ps_string, $ps_color) {
		if (!isset(self::$ansiForegroundColors[$ps_color])) {
			return $ps_string;
		}
		// Disabling color printing under Windows
		if (caGetOSFamily() == OS_WIN32) {
			return $ps_string;
		}
		return "\033[".self::$ansiForegroundColors[$ps_color]."m".$ps_string."\033[0m";
	}
	# -------------------------------------------------------
	/**
	 * Return text in ANSI color
	 *
	 * @param string $ps_string The string to output
	 * @param string $ps_color The background color to output $ps_string with. Colors are defined in CLIBaseUtils::ansiBackgroundColors
	 * @return string The string with ANSI color codes. If $ps_color is invalid the original string will be returned without ANSI codes.
	 */
	public static function textWithBackgroundColor($ps_string, $ps_color) {
		if (!isset(self::$background[$color])) {
			return $ps_string;
		}
		// Disabling color printing under Windows
		if (caGetOSFamily() == OS_WIN32) {
			return $ps_string;
		}
		return "\033[".self::$ansiBackgroundColors[$ps_color].'m'.$ps_string."\033[0m";
	}
	# -------------------------------------------------------
}
