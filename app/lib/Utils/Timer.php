<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/Timer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2015 Whirl-i-Gig
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
		/**
		 * @float microtime the timer started
		 */
		private $start;
		
		/**
		 * @float microtime the timer has run before the current start. This allows additive timer runs. Each time the timer is started and stoped the time run is added to $elapsed and $start is reset to 0.
		 */
		private $elapsed = 0;
		
		/**
		 * @array List of all current timers. Timers are defined by a name, used as keys here. Values are Timer() instances.
		 */
		static $s_timers = array();
		
		/**
		 * @array List tracking enabled status for each timer. Keys are timer names, values are booleans.
		 */
		static $s_timers_enabled = array();
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
			$this->elapsed = 0;
			$this->start = microtime(true);
			return true;
		}
		# ----------------------------------------------------------------------
		/** 
		 * Return elapsed time
		 *
		 * @param int $pn_decimals Number of decimal places to return. [Default is 2.]
		 * @return float The number of seconds since the timer was started.
		 */
		public function getTime($pn_decimals=2) {
			// get and format end time
			return sprintf("%9.{$pn_decimals}f", (microtime(true) - (float)$this->start + (float)$this->elapsed));
		}
		# ----------------------------------------------------------------------
		/**
		 * Start a timer with the specified name. If timer does not already exist it will be created.
		 *
		 * @param string $ps_name Name of timer
		 * @param array $pa_option No options are currently supported
		 *
		 * @return Timer
		 */
		static function start($ps_name, $pa_options=null) {
			Timer::$s_timers_enabled[$ps_name] = true;
			if (Timer::$s_timers[$ps_name]) { Timer::$s_timers[$ps_name]->start = microtime(true); return Timer::$s_timers[$ps_name]; }
			return Timer::$s_timers[$ps_name] = new Timer();
		}
		# ----------------------------------------------------------------------
		/**
		 * Stop the timer with the specified name. The timer may be restarted later with Timer::start().
		 *
		 * @param string $ps_name Name of timer
		 * @param array $pa_option No options are currently supported
		 *
		 * @return bool True on success, null if named timer does not exist.
		 */
		static function stop($ps_name, $pa_options=null) {
			if(Timer::$s_timers[$ps_name]) {
				Timer::$s_timers_enabled[$ps_name] = false;
				Timer::$s_timers[$ps_name]->elapsed += (microtime(true) - Timer::$s_timers[$ps_name]->start);
				Timer::$s_timers[$ps_name]->start = null;
				return true;
			}
			return null;
		}
		# ----------------------------------------------------------------------
		/**
		 * Reset a timer with the specified name to zero. If timer does not already exist it will be created.
		 *
		 * @param string $ps_name Name of timer
		 * @param array $pa_option No options are currently supported
		 *
		 * @return Timer
		 */
		static function reset($ps_name, $pa_options=null) {
			Timer::$s_timers_enabled[$ps_name] = true;
			if (Timer::$s_timers[$ps_name]) { Timer::$s_timers[$ps_name]->elapsed = 0; Timer::$s_timers[$ps_name]->start = microtime(true); return Timer::$s_timers[$ps_name]; }
			return Timer::$s_timers[$ps_name] = new Timer();
		}
		# ----------------------------------------------------------------------
		/**
		 * Remove the timer with the specified name. The timer will no longer be available.
		 *
		 * @param string $ps_name Name of timer
		 * @param array $pa_option No options are currently supported
		 *
		 * @return bool True on success, null if named timer does not exist.
		 */
		static function destroy($ps_name, $pa_options=null) {
			if(Timer::$s_timers[$ps_name]) {
				Timer::$s_timers_enabled[$ps_name] = false;
				unset(Timer::$s_timers[$ps_name]);
				return true;
			}
			return null;
		}
		# ----------------------------------------------------------------------
		/**
		 * Prints the current timer value.
		 *
		 * @param string $ps_name Name of timer
		 * @param string $ps_message Formatting string for display. String may contain placeholders for time (%time) and timer name (%name). [Default is "[%name] %time seconds<br/>\n"]
		 * @param array $pa_option Options include:
		 *		decimals = Number of decimal places to display [Default is 6]
		 *
		 * @return void
		 */
		static function p($ps_name, $ps_message="[%name] %time seconds<br/>\n", $pa_options=null) {
			print Timer::get($ps_name, $ps_message, $pa_options);
		}
		# ----------------------------------------------------------------------
		/**
		 * Prints the current timer value and stops the timer.
		 *
		 * @param string $ps_name Name of timer
		 * @param string $ps_message Formatting string for display. String may contain placeholders for time (%time) and timer name (%name). [Default is "[%name] %time seconds<br/>\n"]
		 * @param array $pa_option Options include:
		 *		decimals = Number of decimal places to display [Default is 6]
		 *
		 * @return void
		 */
		static function printAndStop($ps_name, $ps_message="[%name] %time seconds<br/>\n", $pa_options=null) {
			print Timer::get($ps_name, $ps_message, $pa_options);
			return Timer::stop($ps_name);
		}
		# ----------------------------------------------------------------------
		/**
		 * Get the current timer value for display
		 *
		 * @param string $ps_name Name of timer
		 * @param string $ps_message Formatting string for display. String may contain placeholders for time (%time) and timer name (%name). [Default is "[%name] %time seconds<br/>\n"]
		 * @param array $pa_option Options include:
		 *		decimals = Number of decimal places to display [Default is 6]
		 *
		 * @return string
		 */
		static function get($ps_name, $ps_message="[%name] %time seconds<br/>\n", $pa_options=null) {
			if(Timer::$s_timers[$ps_name] instanceof Timer) {
				if (!Timer::$s_timers_enabled[$ps_name]) { return false; }
				
				$vn_time = Timer::$s_timers[$ps_name]->getTime(caGetOption('decimals', $pa_options, 6));
				return $ps_message ? str_replace("%time", $vn_time, str_replace("%name", $ps_name, $ps_message)) : $vn_time;
			}
			return null;
		}
		# ----------------------------------------------------------------------
		/**
		 * Toggle enabled status of timer. An enabled timer will be printable via Timer::p() and Timer::printAndStop() 
		 * and be capable of running. Disabled timers cannot print.
		 *
		 * @param string $ps_name Name of timer
		 * @param bool $pb_enabled Enabled status to set
		 *
		 * @return bool True on success, null if named timer does not exist.
		 */
		static function enable($ps_name, $pb_enabled=true) {
			if(Timer::$s_timers[$ps_name]) {
				Timer::$s_timers_enabled[$ps_name] = $pb_enabled;
				return true;
			}
			return null;
		}
		# ----------------------------------------------------------------------
		/**
		 * Disable a timer. A disabled timer will not be printable via Timer::p() and Timer::printAndStop().
		 *
		 * @param string $ps_name Name of timer
		 *
		 * @return bool True on success, null if named timer does not exist.
		 */
		static function disable($ps_name) {
			if(Timer::$s_timers[$ps_name]) {
				Timer::$s_timers_enabled[$ps_name] = false;
				return true;
			}
			return null;
		}
		# ----------------------------------------------------------------------
		/**
		 * Enable a timer. An enabled timer will be printable via Timer::p() and Timer::printAndStop() 
		 * and be capable of running. Disabled timers cannot print.
		 *
		 * @param string $ps_name Name of timer
		 * @param bool $pb_enabled Enabled status to set
		 *
		 * @return bool True on success, null if named timer does not exist.
		 */
		static function enabled($ps_name) {
			if(Timer::$s_timers[$ps_name]) {
				return Timer::$s_timers_enabled[$ps_name];
			}
			return null;
		}
		# ----------------------------------------------------------------------
	}