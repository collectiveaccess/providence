<?php
/** ---------------------------------------------------------------------
 * app/lib/Exit/BaseExitFormat.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
 * @subpackage Exit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace Exit\Formats;

abstract class BaseExitFormat {
	# -------------------------------------------------------
	static $s_format_settings = [];
	# -------------------------------------------------------
	protected $name = null;
	protected $log = null;
	protected $directory = null;
	protected $file = null;
	protected $header = [];
	protected $dictionary = [];
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function __construct(string $directory, string $file, ?array $options=null){
		$this->directory = $directory;
		$this->file = $file;
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function getFormatSettings() {
		return BaseExitFormat::$s_format_settings[$this->getName()];
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function getName() {
		return $this->name;
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function setLogger(KLogger $logger) {
		$this->log = $logger;
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function setDictionary(array $dictionary) : bool {
		if(!is_array($dictionary) || !sizeof($dictionary)) { return false; }
		$this->dictionary = $dictionary;
		return true;
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function getDictionary() : ?array {
		return $this->dictionary;
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function setHeader(array $header) : bool {
		if(!is_array($header) || !sizeof($header)) { return false; }
		$this->header = $header;
		return true;
	}
	# -------------------------------------------------------
	/** 
	 *
	 */
	public function getHeader() : ?array {
		return $this->header;
	}
	# -------------------------------------------------------
	/**
	 * Log given message on level debug if logger is available (must be set via setLogger()).
	 * All export format messages are debug level because there's usually nothing interesting going on.
	 * @param string $message log message
	 */
	protected function log(string $message) {
		if($this->log && ($this->log instanceof KLogger)) {
			$this->log->logDebug($message);
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	abstract public function process(array $data,?array $options=null);
	# -------------------------------------------------------
	abstract public function getFileExtension();
	# -------------------------------------------------------
	abstract public function getContentType();
	# -------------------------------------------------------
}
