<?php
/** ---------------------------------------------------------------------
 * app/lib/core/HTTPMultiClient.php : 
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
  
  include_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');
  include_once(__CA_LIB_DIR__.'/core/HTTPMultiClientResult.php');
	
class HTTPMultiClient {
	# -------------------------------------------------------------------
	/**
	 *
	 */
	private $opa_requests = array();
	# -------------------------------------------------------------------
	# Constructor
	# -------------------------------------------------------------------
	public function __construct($pa_requests=null) {	
		$this->reset();
		
		if (is_array($pa_requests)) {
			$this->opa_requests = $pa_requests;
		}
	}
	# -------------------------------------------------------------------
	/**
	 * Add request to list
	 */
	public function addRequest($ps_url, $ps_method='GET', $pa_data=array(), $ps_name='') {
		if (!caIsURL($ps_url)) { return false; }
		$ps_method = strtoupper($ps_method);
		if (!in_array($ps_method, array('GET', 'POST'))) { $ps_method = 'GET'; }
		if (!is_array($pa_data)) { $pa_data = array(); }
		
		if(!$ps_name) { $ps_name = 'request_'.(sizeof($this->opa_requests) + 1); }
		$this->opa_requests[] = array(
			'url' => $ps_url,
			'method' => $ps_method,
			'data' => $pa_data,
			'handle' => null,
			'name' => $ps_name
		);
		
		return true;
	}
	# -------------------------------------------------------------------
	/**
	 * Initiate a multirequest
	 */
	public function execute($pa_options=null) {
		if ($this->_curlIsInstalled()) {
			$va_multi_request = $this->_curlRequest($this->opa_requests, $pa_options);
			return new HTTPMultiClientResult($va_multi_request['multihandle'], $va_multi_request['requests']);
		}
		
		return false;
	}
	# -------------------------------------------------------------------
	/**
	 * Reset client to initial state. All requests and responses are disgarded.
	 *
	 * @return void
	 *
	 */
	public function reset() {
		$this->opa_requests = array();
	}
	# -------------------------------------------------------------------
	/**
	 * Performs multi-request using cURL functions
	 */
	private function _curlRequest($pa_requests, $pa_options=null) {
		
		// multi handle
		$o_mh = curl_multi_init();
		
		// loop through $pa_requests and create curl handles
		// then add them to the multi-handle
		foreach ($pa_requests as $vn_i => $va_request_params) {
		
			$pa_requests[$vn_i]['handle'] = curl_init();
			
			$vs_url = (is_array($va_request_params) && !empty($va_request_params['url'])) ? $va_request_params['url'] : $va_request_params;
			
			curl_setopt($pa_requests[$vn_i]['handle'], CURLOPT_URL, $vs_url);
			curl_setopt($pa_requests[$vn_i]['handle'], CURLOPT_HEADER, 0);
			curl_setopt($pa_requests[$vn_i]['handle'], CURLOPT_RETURNTRANSFER, 1);
			
			// post?
			if (is_array($va_request_params)) {
				if ($va_request_params['method'] == 'POST') {
					curl_setopt($pa_requests[$vn_i]['handle'], CURLOPT_POST, 1);
					curl_setopt($pa_requests[$vn_i]['handle'], CURLOPT_POSTFIELDS, $va_request_params['data']);
				}
			}
		
		// extra options?
			if (!empty($pa_options)) {
				curl_setopt_array($pa_requests[$vn_i]['handle'], $pa_options);
			}
			curl_multi_add_handle($o_mh, $pa_requests[$vn_i]['handle']);
		}
		
		return array('multihandle' => $o_mh, 'requests' => $pa_requests);
	}
	# -------------------------------------------------------------------
	# Utilities
	# -------------------------------------------------------------------
	/**
	 * Checks if the PHP cURL module is available
	 *
	 * @return bool True if cURL is available, false if not
	 */
	 public function _curlIsInstalled() {
	 	return (bool)(function_exists("curl_setopt"));
	 }
	 # -------------------------------------------------------------------
}
	?>