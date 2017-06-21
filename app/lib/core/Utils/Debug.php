<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Utils/Debug.php :
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	use DebugBar\StandardDebugBar;
	
	class Debug {
		# ----------------------------------------------------------------------
		/**
		 * Debug bar instance
		 */
		public static $bar;
		
		/**
		 *
		 */
		public static $debugEnabled = null;
		# ----------------------------------------------------------------------
		/**
		 * Log message to debug bar
		 *
		 * @param string $ps_message
		 */
		public static function msg($ps_message) {
			$va_trace = debug_backtrace();
			$va_line = array_shift($va_trace);
			$va_line['file'] = str_replace(__CA_BASE_DIR__, "", $va_line['file']);
			Debug::$bar['messages']->addMessage("[".$va_line['file']."@".$va_line['line']."] {$ps_message}");
		}
		# ----------------------------------------------------------------------
		/**
		 * Log stacktrace + optional message to debug bar
		 *
		 * @param string $ps_message Optional message to prefix stacktrace with
		 */
		public static function trace($ps_message=null) {
			Debug::$bar['messages']->addMessage($ps_message."\n".caPrintStackTrace(array('skip' => 1)));
		}
		# ----------------------------------------------------------------------
		/**
		 * Log stacktrace + optional message to debug bar
		 *
		 * @param string $ps_message Optional message to prefix stacktrace with
		 */
		public static function isEnabled() {
			if (is_null(Debug::$debugEnabled)) {
				Debug::$debugEnabled = (defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__);
			}
			return Debug::$debugEnabled;
		}
		# ----------------------------------------------------------------------
	}
	
	Debug::$bar = new StandardDebugBar(); 