<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/DbBase.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2008 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
/**
 * Provides common error handling methods
 */
class DbBase {
	/**
	 * Should we die on error?
	 *
	 * @access private
	 */
	var $opb_die_on_error = true;

	/**
	 * List of errors
	 *
	 * @access private
	 */
	var $errors = array();

	/**
	 * Defines if the application dies if an error occurrs. Default is true.
	 *
	 * @param bool $pb_die_on_error
	 */
	function dieOnError($pb_die_on_error=true) {
		$this->opb_die_on_error = $pb_die_on_error;
	}

	/**
	 * Do we die on error?
	 *
	 * @return bool
	 */
	function getDieOnError() {
		return $this->opb_die_on_error;
	}

	/**
	 * How many errors occurred?
	 *
	 * @return int
	 */
	function numErrors() {
		return sizeof($this->errors);
	}

	/**
	 * Fetches the errors. Returns an array of Error objects.
	 *
	 * @return array
	 */
	function errors() {
		return $this->errors;
	}

	/**
	 * Fetches the error. Returns an array containing the error descriptions.
	 *
	 * @return array
	 */
	function getErrors() {
		$va_error_descs = array();
		if (sizeof($this->errors)) {
			foreach ($this->errors as $o_e) {
				array_push($va_error_descs,$o_e->getErrorDescription());
			}
		}
		return $va_error_descs;
	}

	/**
	 * Clears all errors.
	 *
	 * @return bool
	 */
	function clearErrors() {
		$this->errors = array();
		return true;
	}

	/**
	 * Adds a new error
	 *
	 * @param int $pn_num
	 * @param string $ps_message
	 * @param string $ps_context
	 */
	function postError($pn_num, $ps_message, $ps_context, $ps_source='') {
		$o_error = new Error();
		$o_error->setErrorOutput($this->opb_die_on_error);
		$o_error->setHaltOnError($this->opb_die_on_error);
		$o_error->setError($pn_num, $ps_message, $ps_context, $ps_source);
		array_push($this->errors, $o_error);
		return true;
	}
	# ---------------------------------------------------------------------------
}