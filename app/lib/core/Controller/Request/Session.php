<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/Request/Session.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2014 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

class Session {
	# ----------------------------------------
	# --- Properties
	# ----------------------------------------
	private $domain = ""; 	# domain session is registered to (eg. "www.whirl-i-gig.com"); blank means domain cookie was set from
	private $lifetime = 0;	# session lives for $lifetime minutes; session exists for entire browser session if 0
	private $name = "";		# application name

	private $start_time = 0;	# microtime session object was created - used for page performance measurements
	private $sessionData = null;
	
	# ----------------------------------------
	# --- Constructor
	# ----------------------------------------
	/**
	 * @param string $ps_app_name An app name to use if no app name is configured in the application configuration file.
	 * @param bool $pb_dont_create_new_session No new session will be created if set to true. Default is false.
	 */
	public function __construct($ps_app_name=null, $pb_dont_create_new_session=false) {
 		$o_config = Configuration::load();
		# --- Init
		if (defined("__CA_MICROTIME_START_OF_REQUEST__")) {
			$this->start_time = __CA_MICROTIME_START_OF_REQUEST__;
		} else {
			$this->start_time = microtime();
		}
		
		# --- Read configuration
		$this->name = ($vs_app_name = $o_config->get("app_name")) ? $vs_app_name : $ps_app_name;
		$this->domain = $o_config->get("session_domain");
		$this->lifetime = $o_config->get("session_lifetime");
		
		if (!$pb_dont_create_new_session) {
			session_save_path(__CA_APP_DIR__."/tmp");
			session_name($this->name);
			ini_set("session.gc_maxlifetime", $this->lifetime); 
			session_set_cookie_params($this->lifetime, '/', $this->domain);
			session_start();
			$_SESSION['last_activity'] = $this->start_time;
			session_write_close();
			
			$this->sessionData = caGetCacheObject("ca_session_".md5(session_id()), ($this->lifetime > 0) ? $this->lifetime : 7 * 24 * 60 * 60);
		}
	}

	# ----------------------------------------
	# --- Methods
	# ----------------------------------------
	/**
	 * Returns client's session_id. 
	 */
	public function getSessionID () {
		return md5(session_id());
	}
	# ----------------------------------------
	/**
	 * Removes session. Session key is no longer valid after this operation.
	 * Useful for logging out users (destroying the session destroys the login)
	 */
	public function deleteSession() {
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()- (24 * 60 * 60),'/');
		}
		// Delete session data
		$this->sessionData->remove(Zend_Cache::CLEANING_MODE_ALL);
		session_destroy();
	}
	# ----------------------------------------
	/**
	 * Set session variable
	 * Sesssion var may be number, string or array
	 */
	public function setVar($ps_key, $pm_val, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		if ($ps_key && $this->sessionData) {
			$va_vars = $this->sessionData->load('vars');
			if (isset($pa_options["ENTITY_ENCODE_INPUT"]) && $pa_options["ENTITY_ENCODE_INPUT"]) {
				if (is_string($pm_val)) {
					$vm_val = html_entity_decode($pm_val);
				} else {
					$vm_val = $pm_val;
				}
			} else {
				if (isset($pa_options["URL_ENCODE_INPUT"]) && $pa_options["URL_ENCODE_INPUT"]) {
					$vm_val = urlencode($pm_val);
				} else {
					$vm_val = $pm_val;
				}
			}
			$va_vars[$ps_key] = $vm_val;
			$this->sessionData->save($va_vars, 'vars');
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Delete session variable
	 */
	public function delete ($ps_key) {
		$va_vars = $this->sessionData->load('vars');
		unset($va_vars[$ps_key]);
		$this->sessionData->save($va_vars, 'vars');
	}
	# ----------------------------------------
	/**
	 * Get value of session variable. Var may be number, string or array.
	 */
	public function getVar($ps_key) {
		if (!$this->sessionData) { return null; }
		$va_vars = $this->sessionData->load('vars');
		return isset($va_vars[$ps_key]) ? $va_vars[$ps_key] : null;
	}
	# ----------------------------------------
	/**
	 * Return names of all session vars
	 */
	public function getVarKeys() {
		if (!$this->sessionData) { return null; }
		$va_vars = $this->sessionData->load('vars');
		return array_keys($va_vars);
	}
	# ----------------------------------------
	/**
	 * Close session
	 */
	public function close() {
		// NOOP
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