<?php
/** ---------------------------------------------------------------------
 * app/lib/core/BaseObject.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2009 Whirl-i-Gig
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
 
 /**
  *
  */
 
 	require_once(__CA_LIB_DIR__.'/core/Error/IErrorSetter.php');
 
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
						array_push($va_errors,$e);
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
						array_push($error_descs,$e->getErrorDescription());
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
			return sizeof($this->errors($ps_source));
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
			$o_error = new Error();
			$o_error->setErrorOutput($this->error_output);
			$o_error->setError($pn_num,$ps_message,$ps_context, $ps_source);
			
			if (!$this->errors) { $this->errors = array(); }
			array_push($this->errors, $o_error);
			return true;
		}
		# ------------------------------------------------------------------
		public function __destruct() {
			unset($this->errors);
		}
		# ------------------------------------------------------------------
	}
?>