<?php
/** ---------------------------------------------------------------------
 * app/lib/Logging/PriorityLog.php : simple log writing to a file or the system log
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @subpackage Logging
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Thrown when a log file cannot be opened
 */
class PriorityLogException extends Exception {}

/**
 * Writes messages with a priority to a log file or the system log. Replaces Zend_Log, using the same
 * priority values and output format.
 */
class PriorityLog {
	# -------------------------------------------------------
	const ERR = 3;
	const WARN = 4;
	const INFO = 6;
	const DEBUG = 7;

	/**
	 * Priority names, as written to log
	 */
	const PRIORITY_NAMES = [
		self::ERR => 'ERR',
		self::WARN => 'WARN',
		self::INFO => 'INFO',
		self::DEBUG => 'DEBUG'
	];

	/**
	 * Map of priorities to system log priorities
	 */
	const SYSLOG_PRIORITIES = [
		self::ERR => LOG_ERR,
		self::WARN => LOG_WARNING,
		self::INFO => LOG_INFO,
		self::DEBUG => LOG_DEBUG
	];
	# -------------------------------------------------------
	/**
	 * File handle for log file; null if logging to system log
	 */
	private $file = null;

	/**
	 * Application name for system log
	 */
	private $application;

	/**
	 * Messages with a priority value greater than this are not logged
	 */
	private $max_priority = self::DEBUG;
	# -------------------------------------------------------
	/**
	 * @param string $path Path to log file. If null messages are written to the system log. [Default is null]
	 * @param array $options Options include:
	 *		application = Application name to use for system log. [Default is "CollectiveAccess"]
	 *		priority = Only log messages with this priority or more severe. [Default is PriorityLog::DEBUG]
	 * @throws PriorityLogException If log file cannot be opened
	 */
	public function __construct(?string $path=null, ?array $options=null) {
		$this->application = $options['application'] ?? 'CollectiveAccess';
		$this->setPriority($options['priority'] ?? self::DEBUG);

		if ($path) {
			if (!($this->file = @fopen($path, 'a'))) {
				throw new PriorityLogException(_t('Log file %1 cannot be opened for writing', $path));
			}
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __destruct() {
		if ($this->file) { @fclose($this->file); }
	}
	# -------------------------------------------------------
	/**
	 * Set least severe priority to log. Eg. PriorityLog::WARN will log errors and warnings.
	 *
	 * @param int $priority
	 */
	public function setPriority(int $priority) {
		$this->max_priority = $priority;
		return $this;
	}
	# -------------------------------------------------------
	/**
	 * Determine if value is a valid priority
	 *
	 * @param mixed $priority
	 * @return bool
	 */
	static public function isValidPriority($priority) : bool {
		return isset(self::PRIORITY_NAMES[$priority]);
	}
	# -------------------------------------------------------
	/**
	 * Write message to log
	 *
	 * @param string $message
	 * @param int $priority One of PriorityLog::ERR, PriorityLog::WARN, PriorityLog::INFO or PriorityLog::DEBUG
	 * @return bool True if message was written
	 */
	public function log($message, int $priority=self::INFO) : bool {
		if (!self::isValidPriority($priority) || ($priority > $this->max_priority)) { return false; }

		$name = self::PRIORITY_NAMES[$priority];
		if ($this->file) {
			// no need for timestamps in the system log, which adds its own
			return (bool)@fwrite($this->file, date('D Y-m-d H:i:s')." {$name}: {$message}".PHP_EOL);
		}
		openlog($this->application, LOG_PID, LOG_USER);
		return syslog(self::SYSLOG_PRIORITIES[$priority], "{$name}: {$message}");
	}
	# -------------------------------------------------------
}
