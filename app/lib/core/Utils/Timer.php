<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Utils/Timer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2011 Whirl-i-Gig
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

	class Timer {
		# ----------------------------------------------------------------------
		private $start;
		# ----------------------------------------------------------------------
		/** 
		 * @param bool $pb_start_timer If true, timer starts on instantiation. Default is true.
		 */
		public function __construct($pb_start_timer=true) {
			if ($pb_start_timer) {
				$this->startTimer();
			}
			return;
		}
		# ----------------------------------------------------------------------
		/** 
		 * Resets timer to zero and starts tracking elapsed time
		 *
		 * @return bool Always return true.
		 */
		public function startTimer()  {
			$va_start_time = explode (" ", microtime());
			$this->start = $va_start_time[1] + $va_start_time[0];
			return true;
		}
		# ----------------------------------------------------------------------
		/** 
		 * Return elapsed time
		 *
		 * @param int $pn_decimals Number of decimal places to return. Default is 2.
		 * @return float The number of seconds since the timer was started.
		 */
		public function getTime($pn_decimals=2) {
			// $decimals will set the number of decimals you want for your milliseconds.
			
			// get and format end time
			$va_end_time = explode (" ", microtime());
			return sprintf("%9.{$pn_decimals}f", (($va_end_time[1] + $va_end_time[0]) - $this->start));
		}
		# ----------------------------------------------------------------------
	}
?>