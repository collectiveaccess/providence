<?php
/* ----------------------------------------------------------------------
 * install/inc/SketchInstaller.php : install system from Excel-format system sketch
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
namespace Installer\Parsers;

require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');

abstract class BaseProfileParser {
	# --------------------------------------------------
	/**
	 *
	 */
	protected $log;
	
	/**
	 *
	 */
	protected $notices;
	
	/**
	 *
	 */
	protected $warnings;
	
	/**
	 *
	 */
	protected $errors;
	
	/**
	 *
	 */
	protected $debug = false;
	
	/**
	 *
	 */
	protected $debug_profile = null;
	
	# --------------------------------------------------
	/**
	 * Instantiate new parser. If $directory and $profile parameters are passed the 
	 * specified profile will be loaded and parsed.
	 *
	 * @param string $directory Directory containing profile
	 * @param string $profile Name (with or without extension) of profile to parse
	 */
	public function __construct(?string $directory=null, ?string $profile=null) {
		$this->log = new \KLogger(__CA_BASE_DIR__ . '/app/log', \KLogger::DEBUG);
		$this->notices = $this->warnings = $this->errors = [];
		$this->debug = true;
		if($profile) {
			$this->parse($directory, $profile);
		}
	}
	# --------------------------------------------------
	/**
	 * Parse a profile
	 *
	 * @param string $directory Directory containing profile
	 * @param string $profile Name (with or without extension) of profile to parse
	 *
	 * @return array Parsed profile data
	 */
	abstract public function parse(string $directory, string $profile) : array;
	# --------------------------------------------------
	/**
	 * Validate a profile
	 *
	 * @param string $directory path to a directory containing profiles
	 * @param string $profile Name of the profile, with or without file extension
	 *
	 * @return bool
	 */
	abstract public function validateProfile(string $directory, string $profile) : bool;
	# --------------------------------------------------
	/**
	 * Extract identifying information from a profile
	 *
	 * @param string $profile_path Path to profile
	 *
	 * @return array Extracted information, or null if profile cannot be read
	 */
	abstract public function profileInfo(string $profile_path) : ?array;
	# --------------------------------------------------
	/**
	 * Return parser file format (Eg. XLSX, XML, etc)
	 */
	public function format() : string {
		return $this->format;
	}
	# --------------------------------------------------
	/**
	 *  Log message during parsing
	 *
	 * @param string $message
	 */
	protected function logStatus(string $msg) : void {
		if($this->log) {
			$this->log->logInfo($msg);
		}
	}
	# --------------------------------------------------
	/**
	 * Record informational notice during parsing
	 *
	 * @param string $stage Parsing stage 
	 * @param string $message Notice text
	 */
	protected function notice(string $stage, string $message) : void {
		$this->notices[] = [
			'stage' => $stage,
			'message' => $message
		];
	}
	# --------------------------------------------------
	/**
	 * Record warning notice during parsing
	 *
	 * @param string $stage Parsing stage 
	 * @param string $message Warning text
	 */
	protected function warning(string $stage, string $message) : void {
		$this->warnings[] = [
			'stage' => $stage,
			'message' => $message
		];
	}
	# --------------------------------------------------
	/**
	 * Record error during parsing
	 *
	 * @param string $stage Parsing stage 
	 * @param string $message Error text
	 */
	protected function error(string $stage, string $message) : void {
		$this->errors[] = [
			'stage' => $stage,
			'message' => $message
		];
	}
	# --------------------------------------------------
	/**
	 * Get notices
	 *
	 * @return array List of notices
	 */
	public function getNotices() : array {
		return $this->notices;
	}
	# --------------------------------------------------
	/**
	 * Get warnings
	 *
	 * @return array List of warnings
	 */
	public function getWarnings() : array {
		return $this->warnings;
	}
	# --------------------------------------------------
	/**
	 * Get errors
	 *
	 * @return array List of errors
	 */
	public function getErrors() : array {
		return $this->errors;
	}
	# --------------------------------------------------
}
