<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/BaseService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
class BaseService {
	# -------------------------------------------------------
	protected $opo_request;
	# -------------------------------------------------------
	public function  __construct($po_request) {
		$this->opo_request = $po_request;
	}
	# -------------------------------------------------------
	/**
	 * Handles authentification
	 *
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	public function auth($username="",$password=""){
		if(($username != "") && ($password != "")){
			$va_options = array(
				'noPublicUsers' => true,
				"no_headers" => true,
				"dont_redirect" => true,
				"options" => array(),
				"user_name" => $username,
				"password" => $password,
			);
		} else {
			$va_options = array(
				'noPublicUsers' => true,
				"no_headers" => true,
				"dont_redirect" => true,
				"options" => array()
			);
		}
		
		$this->opo_request->doAuthentication($va_options);
		return $this->opo_request->getUserID();
	}
	# -------------------------------------------------------
	/**
	 * Log out
	 * 
	 * @return boolean
	 */
	public function deauthenticate(){
		$this->opo_request->deauthenticate();
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Fetches the user ID of the current user (neq zero if logged in)
	 *
	 * @return int
	 */
	public function getUserID(){
		return $this->opo_request->getUserID();
	}
	# -------------------------------------------------------
	/**
	 * Fetches current date/time on the server and returns as Unix timestamp
	 *
	 * @return int
	 */
	public function getServerTime(){
		return time();
	}
	# -------------------------------------------------------
}

?>