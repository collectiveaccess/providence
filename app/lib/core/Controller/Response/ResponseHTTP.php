<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/Response/ResponseHTTP.php :
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
 
require_once(__CA_LIB_DIR__.'/core/Controller/Response.php');

class ResponseHTTP extends Response {
	# -------------------------------------------------------

	private $opa_headers;
	private $opn_http_response_code = 200;
	private $ops_http_response_message = 'OK';
	private $opb_headers_were_sent = false;
	private $opb_content_was_sent = false;
	private $opb_is_redirect = false;
	
	# -------------------------------------------------------
	public function __construct() {
		parent::__construct();
	}
	# -------------------------------------------------------
	# Headers
	# -------------------------------------------------------
	public function addHeader($ps_name, $ps_value, $ps_replace=false) {
		if (!isset($this->opa_headers[$ps_name]) || !is_array($this->opa_headers[$ps_name]) || $ps_replace) { $this->opa_headers[$ps_name] = array(); }
		$this->opa_headers[$ps_name][] = $ps_value;
	}
	# -------------------------------------------------------
	public function addHeaders($pa_headers) {
		foreach($pa_headers as $vs_name => $vs_value) {
			$this->addHeader($vs_name, $vs_value);
		}
	}
	# -------------------------------------------------------
	public function getHeaders($ps_name=null) {
		if ($ps_name) {
			return $this->opa_headers[$ps_name];
		} 
		return $this->opa_headers;
	}
	# -------------------------------------------------------
	public function clearHeader($ps_name) {
		unset($this->opa_headers[$ps_name]);
	}
	# -------------------------------------------------------
	public function clearHeaders() {
		$this->opa_headers = array();
	}
	# -------------------------------------------------------
	public function headersAreSet() {
		return (sizeof($this->opa_headers) ? true : false);
	}
	# -------------------------------------------------------
	public function setRedirect($ps_url, $pn_code = 302) {
		$this->addHeader("Location", $ps_url);
		$this->setHTTPResponseCode($pn_code, 'Moved');
		$this->opb_is_redirect = true;
	}
	# -------------------------------------------------------
	public function isRedirect() {
		return $this->opb_is_redirect;
	}
	# -------------------------------------------------------
	public function setHTTPResponseCode($pn_code, $ps_message) {
		$this->opn_http_response_code = $pn_code;
		$this->ops_http_response_message = $ps_message;
	}
	# -------------------------------------------------------
	public function getHTTPResponseCode() {
		return $this->opn_http_response_code;
	}
	# -------------------------------------------------------
	public function getHTTPResponseMessage() {
		return $this->ops_http_response_message;
	}
	# -------------------------------------------------------
	public function headersWereSent() {
		return $this->opb_headers_were_sent;
	}
	# -------------------------------------------------------
	# Content
	# -------------------------------------------------------
	public function setContent($ps_content, $ps_segment='default') {
		$this->opa_content[$ps_segment] = $ps_content;
	}
	# -------------------------------------------------------
	public function addContent($ps_content, $ps_segment='default') {
		if (!isset($this->opa_content[$ps_segment])) { $this->opa_content[$ps_segment] = ''; }
		$this->opa_content[$ps_segment] .= $ps_content;
	}
	# -------------------------------------------------------
	public function appendContent($ps_content, $ps_segment) {
		$this->appendSegment($ps_content, $ps_segment);
	}
	# -------------------------------------------------------
	public function prependContent($ps_content, $ps_segment) {
		$this->prependSegment($ps_content, $ps_segment);
	}
	# -------------------------------------------------------
	public function insertIntoBody($ps_content, $ps_segment, $ps_after_segment) {
	
	}
	# -------------------------------------------------------
	public function removeContent($ps_segment) {
		$this->removeSegment($ps_segment);
	}
	# -------------------------------------------------------
	public function clearContent() {
		$this->opa_content = array();
	}
	# -------------------------------------------------------
	public function getContentSegments($ps_content, $ps_segment=null) {
		return $this->opa_content;
	}
	# -------------------------------------------------------
	public function getContent() {
		return join('',$this->opa_content);
	}
	# -------------------------------------------------------
	# Send it
	# -------------------------------------------------------
	public function sendResponse() {
		$this->sendHeaders();
		$this->sendContent();
	}
	# -------------------------------------------------------
	public function sendHeaders() {
		if ($this->opb_content_was_sent) { return; }
		foreach($this->getHeaders() as $vs_name => $va_values) {
			foreach ($va_values as $vs_value) {
				header($vs_name.': '.$vs_value);
			}
		}
		if($this->opn_http_response_code != 200){
			header("HTTP/1.1 {$this->opn_http_response_code} {$this->ops_http_response_message}");
		}
		$this->opb_headers_were_sent = true;
	}
	# -------------------------------------------------------
	public function sendContent() {
		print $this->getContent();
		$this->opb_content_was_sent = true;
	}
	# -------------------------------------------------------
}
 ?>