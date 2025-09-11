<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/Debug.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2025 Whirl-i-Gig
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
class Debug {
	# ----------------------------------------------------------------------
	/**
	 *
	 */
	public static $debugEnabled = null;
	# ----------------------------------------------------------------------
	/**
	 * Log message to debug bar
	 *
	 * @param string $message
	 */
	public static function msg($message) {
		$trace = debug_backtrace();
		$line = array_shift($trace);
		$line['file'] = str_replace(__CA_BASE_DIR__, "", $line['file']);
		
		// noop 
	}
	# ----------------------------------------------------------------------
	/**
	 * Log stacktrace + optional message to debug bar
	 *
	 * @param string $message Optional message to prefix stacktrace with
	 */
	public static function trace($message=null) {
		$msg = $message."\n".caPrintStackTrace(array('skip' => 1));
		
		// noop
	}
	# ----------------------------------------------------------------------
	/**
	 * Log stacktrace + optional message to debug bar
	 *
	 * @param string $message Optional message to prefix stacktrace with
	 */
	public static function isEnabled() {
		return false;
	}
	# ----------------------------------------------------------------------
}
