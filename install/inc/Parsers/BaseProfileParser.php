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

class BaseProfileParser {
	# --------------------------------------------------
	/**
	 *
	 */
	protected $log;
	
	# --------------------------------------------------
	/**
	 *
	 */
	public function __construct(?string $directory=null, ?string $profile=null) {
		$this->log = new \KLogger(__CA_BASE_DIR__ . '/app/log', \KLogger::DEBUG);
		if($profile) {
			$this->parse($directory, $profile);
		}
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function logStatus(string $msg) {
		if($this->log) {
			$this->log->logInfo($msg);
		}
	}
	# --------------------------------------------------
	/**
	 * Return parser file format (Eg. XLSX, XML, etc)
	 */
	public function format() : string {
		return $this->format;
	}
	# --------------------------------------------------
}
