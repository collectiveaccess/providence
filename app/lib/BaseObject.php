<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseObject.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2021 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Error/IErrorSetter.php');

class BaseObject implements IErrorSetter {
	# ------------------------------------------------------------------
	
	public $errors;
	private $error_output = false;
	# ------------------------------------------------------------------
	# --- Error handling
	# ------------------------------------------------------------------
	public function __construct() {
		$this->errors = array();
	}
	# ------------------------------------------------------------------
	public function &errors($ps_source=null) {
		if (is_null($ps_source)) { return $this->errors; }
		
		$va_errors = array();
		if (sizeof($this->errors)) {
			foreach ($this->errors as $e) {
				if ((is_null($ps_source)) || ((!is_null($ps_source) && ($e->getErrorSource() === $ps_source)))) {
					array_push($va_errors, $e);
				}
			}
		}
		return $va_errors;
	}
	# ------------------------------------------------------------------
	public function &getErrors($ps_source=null) {
		$error_descs = array();
		if (sizeof($this->errors)) {
			foreach ($this->errors as $e) {
				if ((is_null($ps_source)) || ((!is_null($ps_source) && ($e->getErrorSource() === $ps_source)))) {
					if(is_string($e)) {
						array_push($error_descs,$e);
					} else {
						array_push($error_descs,$e->getErrorDescription());
					}
				}
			}
		}
		return $error_descs;
	} 
	# ------------------------------------------------------------------
	public function &getErrorDescriptions($ps_source=null) {
		$va_errors = array();
		foreach($this->getErrors($ps_source) as $vs_e) {
			$va_errors[] = $vs_e;
		}
		return $va_errors;
	}
	# ------------------------------------------------------------------
	public function numErrors($ps_source=null) {
		$e = $this->errors($ps_source);
		return is_array($e) ? sizeof($e) : 0;
	}
	# ------------------------------------------------------------------
	public function clearErrors() {
		$this->errors = array();
		return true;
	}
	# ------------------------------------------------------------------
	public function setErrorOutput($error_output) {
		$this->error_output = $error_output;
		return true;
	}
	# ------------------------------------------------------------------
	public function postError($pn_num, $ps_message, $ps_context, $ps_source='') {
		$o_error = new ApplicationError();
		$o_error->setErrorOutput($this->error_output);
		$o_error->setError($pn_num,$ps_message,$ps_context, $ps_source);
		
		if (!$this->errors) { $this->errors = array(); }
		array_push($this->errors, $o_error);
		
		$dummy = null;
		if (($app = AppController::getInstance($dummy, $dummy, true)) && ($o_request = $app->getRequest()) && defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) {
			$va_trace = debug_backtrace();
			array_shift($va_trace);
			$vs_stacktrace = '';
			while($va_source = array_shift($va_trace)) {
				$vs_stacktrace .= " [{$va_source['file']}:{$va_source['line']}]";
			}
			
			$o_notification = new NotificationManager($o_request);
			$o_notification->addNotification("[{$pn_num}] {$ps_message} ({$ps_context}".($ps_source ? "; {$ps_source}" : '').$vs_stacktrace);
		}
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Check if object has experienced an error with a given numeric code.
	 *
	 * @param int $error_num
	 * @param string $source Optional source to limit errors to. [Default is null; check all errors]
	 *
	 * return bool
	 */
	public function hasErrorNum(int $error_num, ?string $source=null) : bool {
		$errors = $this->errors($source);
		
		foreach($errors as $e) {
			if($e->getErrorNumber() == $error_num) { return true; }
		}
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 * Check if object has experienced an error within a range of codes.
	 *
	 * @param int $start Start of error code range.
	 * @param int $end End of error code range.
	 * @param string $source Optional source to limit errors to. [Default is null; check all errors]
	 *
	 * return bool
	 */
	public function hasErrorNumInRange(int $start, int $end, ?string $source=null) : bool {
		$errors = $this->errors($source);
		
		foreach($errors as $e) {
			$error_num = $e->getErrorNumber();
			if(($error_num >= $start) && ($error_num <= $end)) { return true; }
		}
		return false;
	}
	# ------------------------------------------------------------------
	public function __destruct() {
		unset($this->errors);
	}
	# ------------------------------------------------------------------
}
