<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/NS11mmService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
  
require_once(__CA_LIB_DIR__."/ca/Service/BaseService.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");

class NS11mmService extends BaseService {
	
	const OAI_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';
	const DB_DATE_FORMAT  = 'Y-m-d H:i:s';
	
	const OAI_DATE_PCRE     = "/^\\d{4}\\-\\d{2}\\-\\d{2}$/";
	const OAI_DATETIME_PCRE = "/^\\d{4}\\-\\d{2}\\-\\d{2}T\\d{2}\\:\\d{2}\\:\\d{2}Z$/";
	
	# -------------------------------------------------------
	protected $opo_dm;
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);
		$this->opo_dm = Datamodel::load();
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
		if (!$username) { $username = $this->opo_request->getParameter('username', pString); }
		if (!$password) { $password = $this->opo_request->getParameter('password', pString); }
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
		
		if ($this->opo_request->doAuthentication($va_options)) {
			return $this->makeResponse(array('cookie' => session_name(), 'user_id' => $this->opo_request->getUserID()));
		} else {
			return $this->makeResponse(array(), 403, 'Login failed');
		}
	}
	# -------------------------------------------------------
	public function makeResponse($pa_data, $pn_code=200, $ps_error_message=null) {
		global $resp;
		$resp->setHTTPResponseCode($pn_code, $ps_error_message ? $ps_error_message : (($pn_code == 200) ? "OK" : "ERROR"));
		$resp->addHeader("Content-type", "text/json", true);
		
		if ($pn_code >= 400) {
			$pa_data['message'] = ($pa_data['message']) ? $pa_data['message']."; {$ps_error_message}" : $ps_error_message;
		}
		$va_data = array(
			'code' => $pn_code,
			'data' => $pa_data
		);
		return $va_data;
	}
	# -------------------------------------------------------
	/**
	 * Converts the given Unix timestamp to OAI-PMH's specified ISO 8601 format.
	 *
	 * @param int $ps_timestamp Unix timestamp
	 * @return string Time in ISO 8601 format
	 */
	static function unixToUtc($ps_timestamp) {
		return gmdate(self::OAI_DATE_FORMAT, $ps_timestamp);
	}
	# -------------------------------------------------------
    /**
     * Converts the given Unix timestamp to the Omeka DB's datetime format.
     *
     * @param int $ps_timestamp Unix timestamp
     * @return string Time in Omeka DB format
     */
    static function unixToDb($ps_timestamp) {
       return date(self::DB_DATE_FORMAT, $ps_timestamp);
    }
    # -------------------------------------------------------
    /**
     * Converts the given time string to MySQL database format
     *
     * @param string $databaseTime Database time string
     * @return string Time in MySQL DB format
     * @uses unixToDb()
     */
    static function utcToDb($ps_utc_datetime) {
       return self::unixToDb(strtotime($ps_utc_datetime));
    }
	# -------------------------------------------------------
}
