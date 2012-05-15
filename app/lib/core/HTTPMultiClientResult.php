<?php
/** ---------------------------------------------------------------------
 * app/lib/core/HTTPMultiClientResult.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
	
class HTTPMultiClientResult {
	# -------------------------------------------------------------------
	/**
	 *
	 */
	private $opa_responses = array();
	
	/**
	 * The  multi-HTTP request resource
	 */
	private $opo_multi_handle = null;
	
	/**
	 * A list of requests being processed by the multi-request
	 */
	private $opa_requests = null;
	
	/**
	 * An list of requests being processed by the multi-request, indexed by string-ified resource name
	 */
	private $opa_requests_by_handle = null;
	# -------------------------------------------------------------------
	# Constructor
	# -------------------------------------------------------------------
	/**
	 * @param Resource $po_multi_handle a multi-HTTP request resource of some kind (eg. multi-cURL or HTTPRequestPool)
	 * @param array $pa_requests A list of service requests being processed by the multi-request
	 */
	public function __construct($po_multi_handle, $pa_requests) {	
		$this->opo_multi_handle = $po_multi_handle;
		$this->opa_requests = $pa_requests;
		
		foreach($this->opa_requests as $vn_i => $va_request_info) {
			$va_request_info['index'] = $vn_i;
			$this->opa_requests_by_handle[(string)$va_request_info['handle']] = $va_request_info;
		}
		
		$this->opa_responses = array();
	}
	# -------------------------------------------------------------------
	/**
	 * Get the response for a request name
	 *
	 * @param string $ps_request_name The name of the request
	 * @return string The text of the response, or null if the request name is not valid.
	 */
	public function getResponse($ps_request_name) {
		$this->updateStatus();
		if (isset($this->opa_responses[$ps_request_name])) {
			return $this->opa_responses[$ps_request_name];
		}
		return null;
	}
	# -------------------------------------------------------------------
	/**
	 * Returns request_name's for available responses
	 *
	 * @return array A list of valid request names for responses received thus far. Request names can be used with HTTPMultiClientResult::getResponse() to fetch an individual response.
	 */
	public function availableResponses() {
		$this->updateStatus();
		return array_keys($this->opa_responses);
	}
	# -------------------------------------------------------------------
	/**
	 * Get all responses
	 *
	 * @return array An array of all responses received thus far, key'ed on request id [an arbitrary string]
	 */
	public function getAllResponses() {
		return $this->opa_responses;
	}
	# -------------------------------------------------------------------
	/**
	 * Check if all requests have completed.
	 *
	 * @return bool True if all requests have returned a response.
	 */
	public function done() {
		if (!$this->updateStatus() || (sizeof($this->opa_responses) == sizeof($this->opa_requests))) {
			return true;
		}
		return false;
	}
	# -------------------------------------------------------------------
	/**
	 * Poll outstanding requests and update all statuses. Can be called repeatedly until all requests are completed.
	 *
	 * @return bool True if requests are still running after the update, false if all requests are complete (and therefore, 
	 *			calling updateStatus() again would be of little use.)
	 */
	public function updateStatus() {
		$vb_running = null;
		
		
		do {
    		curl_multi_exec($this->opo_multi_handle ,$vb_running);
		} while ($vb_running > 0);
		
		
		while(($va_message = curl_multi_info_read($this->opo_multi_handle, $vb_running))) {
			if ($va_message['msg'] == CURLMSG_DONE) {
				if ($va_message['result'] == CURLE_OK) {
					$this->opa_responses[$this->opa_requests_by_handle[(string)$va_message['handle']]['name']] = curl_multi_getcontent($va_message['handle']);
				} else {
					$this->opa_responses[$this->opa_requests_by_handle[(string)$va_message['handle']]['name']] = "ERROR";
				}
				curl_multi_remove_handle($this->opo_multi_handle, $va_message['handle']);
			}
		}
		
		if (sizeof($this->opa_responses) == sizeof($this->opa_requests)) {
			curl_multi_close($this->opo_multi_handle);
			return false;
		}
		
		return true;
	}
	# -------------------------------------------------------------------
}
?>