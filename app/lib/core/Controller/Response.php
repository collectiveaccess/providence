<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/Response.php :
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
class Response {
	# -------------------------------------------------------
	protected $opa_errors;
	protected $opa_exceptions;
	
	protected $opa_content;
	# -------------------------------------------------------
	public function __construct() {
		$this->opa_content = array();
	}
	# -------------------------------------------------------
	# Content
	# -------------------------------------------------------
	protected function prependSegment($ps_content, $ps_content_segment, $ps_parent=null) {
		$this->opa_content = array($ps_content_segment => $ps_content) + $this->opa_content;
	}
	# -------------------------------------------------------
	protected function appendSegment($ps_content, $ps_content_segment) {
		if (isset($this->opa_content[$ps_content_segment])) { unset($this->opa_content[$ps_content_segment]); }
		$this->opa_content[$ps_content_segment] = $ps_content;
	}
	# -------------------------------------------------------
	protected function addToSegment($ps_content, $ps_content_segment='_default') {
		$this->opa_content[$ps_content_segment] .= $ps_content;
	}
	# -------------------------------------------------------
	protected function removeSegment($ps_content_segment) {
		unset($this->opa_content[$ps_content_segment]);
	}
	# -------------------------------------------------------
	# Output
	# -------------------------------------------------------
	public function sendResponse() {
		print_r($this->opa_content);
	}
	# -------------------------------------------------------
	# Errors
	# -------------------------------------------------------
	public function postError($pn_num, $ps_message, $ps_context, $pn_severity=null, $ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	public function setError($po_error, $ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	public function setErrors($pa_errors, $ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	# Return array of error messages
	public function getErrorMessages($ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	# Return array of error objects
	public function getErrors($ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	# Return array of error objects
	public function errors($ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	# Return list of defined error namespaces
	public function getErrorNamespaces() {
	
	}
	# -------------------------------------------------------
	# return number of errors
	public function numErrors($ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	# return error message formatted
	public function renderErrors($ps_namespace=null) {
	
	}
	# -------------------------------------------------------
	# Exceptions
	# -------------------------------------------------------
	public function setException($e_exception) {
	
	}
	# -------------------------------------------------------
	public function numExceptions() {
	
	}
	# -------------------------------------------------------
	public function getExceptions() {
	
	}
	# -------------------------------------------------------
	public function hasExceptionOfType($ps_type) {
	
	}
	# -------------------------------------------------------
	public function hasExceptionOfMessage($ps_message) {
	
	}
	# -------------------------------------------------------
	public function hasExceptionOfCode($ps_code) {
	
	}
	# -------------------------------------------------------
	public function getExceptionByType($ps_type) {
	
	}
	# -------------------------------------------------------
	public function getExceptionByMessage($ps_message) {
	
	}
	# -------------------------------------------------------
	public function getExceptionByCode($ps_code) {
	
	}
	# -------------------------------------------------------
	public function renderExceptions() {
	
	}
	# -------------------------------------------------------
	public function throwExceptions($pb_throw_it) {
	
	}
	# -------------------------------------------------------
}
 ?>