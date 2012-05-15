<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/Request/Session.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2012 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/core/Error.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Db.php");

class Session {
	# ----------------------------------------
	# --- Properties
	# ----------------------------------------
	private $domain = ""; 	# domain session is registered to (eg. "www.whirl-i-gig.com"); blank means domain cookie was set from
	private $lifetime = 0;	# session lives for $lifetime minutes; session exists for entire browser session if 0
	private $name = "";		# application name

	private $start_time = 0;	# microtime session object was created - used for page performance measurements
	# ----------------------------------------
	# --- Constructor
	# ----------------------------------------
	public function __construct() {
 		$o_config = Configuration::load();
		# --- Init
		if (defined("__CA_MICROTIME_START_OF_REQUEST__")) {
			$this->start_time = __CA_MICROTIME_START_OF_REQUEST__;
		} else {
			$this->start_time = microtime();
		}
		
		# --- Read configuration
		$this->name = $o_config->get("app_name");
		$this->domain = $o_config->get("session_domain");
		$this->lifetime = $o_config->get("session_lifetime");
		
		session_name($this->name);
		ini_set("session.gc_maxlifetime", $this->lifetime); 
		session_set_cookie_params($this->lifetime, '/', $this->domain);
		session_start();
	}

	# ----------------------------------------
	# --- Methods
	# ----------------------------------------
	/**
	 * Returns client's session_id. 
	 */
	public function getSessionID () {
		return $this->session_id;
	}
	# ----------------------------------------
	/**
	 * Removes session. Session key is no longer valid after this operation.
	 * Useful for logging out users (destroying the session destroys the login)
	 */
	public function deleteSession() {
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()- (24 * 60 * 60),'/');
		}
		session_destroy();
	}
	# ----------------------------------------
	/**
	 * Set session variable
	 * Sesssion var may be number, string or array
	 */
	public function setVar($ps_key, $pm_val, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		if ($ps_key) {
			if (isset($pa_options["ENTITY_ENCODE_INPUT"]) && $pa_options["ENTITY_ENCODE_INPUT"]) {
				if (is_string($pm_val)) {
					$_SESSION['session_vars'][$ps_key] = htmlentities(html_entity_decode($pm_val));
				} else {
					$_SESSION['session_vars'][$ps_key] = $pm_val;
				}
			} else {
				if (isset($pa_options["URL_ENCODE_INPUT"]) && $pa_options["URL_ENCODE_INPUT"]) {
					$_SESSION['session_vars'][$ps_key] = urlencode($pm_val);
				} else {
					$_SESSION['session_vars'][$ps_key] = $pm_val;
				}
			}
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Delete session variable
	 */
	public function delete ($ps_key) {
		unset($_SESSION['session_vars'][$ps_key]);
	}
	# ----------------------------------------
	/**
	 * Get value of session variable. Var may be number, string or array.
	 */
	public function getVar($ps_key) {
		return isset($_SESSION['session_vars'][$ps_key]) ? $_SESSION['session_vars'][$ps_key] : '';
	}
	# ----------------------------------------
	/**
	 * Return names of all session vars
	 */
	public function getVarKeys() {
		return (isset($_SESSION['session_vars']) && is_array($_SESSION['session_vars'])) ? array_keys($_SESSION['session_vars']) : array();
	}
	# ----------------------------------------
	/**
	 * Close session and save vars
	 * You must call this at the end of the page or any changed session vars will not be saved!
	 */
	public function close() {
		session_write_close();
	}
	# ----------------------------------------
	# --- Page performance
	# ----------------------------------------
	# Return number of seconds since request processing began
	public function elapsedTime($pn_decimal_places=4) {
		if (!$microtime) {
			$microtime = $this->start_time;
		}
		list($sm, $st) = explode(" ",$microtime);
		list($em, $et) = explode(" ",microtime());

		return sprintf("%4.{$pn_decimal_places}f", (($et+$em) - ($st+$sm)));
	}
	# ----------------------------------------
}
?>