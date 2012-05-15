<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Utils/ProcessStatus.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2008 Whirl-i-Gig
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

require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');

class ProcessStatus {
	# ---------------------------------------------------------------------------
	# Returns true if we have the means to detect if specific processes are running.
	# For POSIX OS', this means having the POSIX extensions installed (since this is default for PHP5 
	# the odds are very good that they're available)
	#
	# For Windows, this means having the win32ps extension installed; this is not standard and 
	# must be installed separately.
	#
	function canDetectProcesses() {
		switch(caGetOSFamily()) {
			case OS_WIN32:
				if (function_exists('win32_ps_stat_proc')) {
					return true;
				} else {
					return false;
				}
				break;
			default:
				if (function_exists('posix_kill')) {
					return true;
				} else {
					return false;
				}
				break;
		}
	}
	# ---------------------------------------------------------------------------
	# Returns ID of current process; null if it is not possible to get the ID
	#
	function getProcessID() {
		if (!$this->canDetectProcesses()) { return null; }
		
		switch(caGetOSFamily()) {
			case OS_WIN32:
				$va_proc_info = win32_ps_stat_proc();
				if (is_array($va_proc_info)) {
					return $va_proc_info['pid'];
				} else {
					return null;
				}
				break;
			default:
				return posix_getpid();
				break;
		}
	}
	# ---------------------------------------------------------------------------
	# Returns true if specified process number exists
	#
	function processExists($pn_proc_id) {
		if (!$this->canDetectProcesses()) { return null; }
		
		switch(caGetOSFamily()) {
			case OS_WIN32:
				$va_proc_info = win32_ps_stat_proc($pn_proc_id);
				return is_array($va_proc_info);
				break;
			default:
				return posix_kill($pn_proc_id, 0) ? true : false;
				break;
		}
	}
	# ---------------------------------------------------------------------------
}
?>