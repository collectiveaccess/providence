<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/Request/Session.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2016 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/core/ApplicationError.php");
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

	/**
	 * In-memory session var storage
	 * @var array
	 */
	private $opa_session_vars = [];
	
	/**
	 *
	 *
	 */
	private $opa_changed_vars = [];
	
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
		$this->lifetime = (int) $o_config->get("session_lifetime");

		if(!$this->lifetime) {
			$this->lifetime = 3600 * 24 * 7;
		}
		
		if (!$pb_dont_create_new_session) {
			// try to get session ID from cookie. if that doesn't work, generate a new one
			if (!($vs_session_id = $this->getSessionID())) {
				$vs_cookiepath = ((__CA_URL_ROOT__== '') ? '/' : __CA_URL_ROOT__);
				if (!caIsRunFromCLI()) { setcookie($this->name, $_COOKIE[$this->name] = $vs_session_id = caGenerateGUID(), $this->lifetime ? time() + $this->lifetime : null, $vs_cookiepath); }
		 	}

			// initialize in-memory session var storage, either restored from external cache or newly initialized
			if($this->getSessionID() && ExternalCache::contains($this->getSessionID(), 'SessionVars')) {
				$this->opa_session_vars = ExternalCache::fetch($this->getSessionID(), 'SessionVars');
			} else {
				$this->opa_session_vars = array();
				if($this->getSessionID()) {
					ExternalCache::delete($this->getSessionID(), 'SessionVars');
				}
				$this->opa_changed_vars['session_end_timestamp'] = true;
				$this->opa_session_vars['session_end_timestamp'] = time() + $this->lifetime;
			}

			// kill session if it has ended or we don't have a timestamp
			if(
				!isset($this->opa_session_vars['session_end_timestamp'])
				||
				(is_numeric($this->opa_session_vars['session_end_timestamp']) && (time() > $this->opa_session_vars['session_end_timestamp']))
			) {
				$this->opa_session_vars = $this->opa_changed_vars['session_end_timestamp'] = array();
				ExternalCache::delete($this->getSessionID(), 'SessionVars');
			}
		}
	}
	# ----------------------------------------
	/**
	 * Destructor: Save session variables to permanent storage
	 */
	public function __destruct() {
		if($this->getSessionID() && is_array($this->opa_session_vars) && (sizeof($this->opa_session_vars) > 0)) {
			$this->save();	
		}
	}
	# ----------------------------------------
	/**
	 * Return service authentication token for this session (and create it, if none exists yet).
	 * These tokens usually have a much shorter lifetime than the session.
	 * @param bool $pb_dont_create_new_token dont create new auth token
	 * @return string|bool The token, false if
	 * @throws Exception
	 */
	public function getServiceAuthToken($pb_dont_create_new_token=false) {
		if(!$this->getSessionID()) { return false; }

		if(ExternalCache::contains($this->getSessionID(), 'SessionIDToServiceAuthTokens')) {
			return ExternalCache::fetch($this->getSessionID(), 'SessionIDToServiceAuthTokens');
		}

		if($pb_dont_create_new_token) { return false; }

		// generate new token
		if(function_exists('mcrypt_create_iv')) {
			$vs_token = hash('sha256', mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		} else if(function_exists('openssl_random_pseudo_bytes')) {
			$vs_token = hash('sha256', openssl_random_pseudo_bytes(32));
		} else {
			throw new Exception('mcrypt or OpenSSL is required for CollectiveAccess to run');
		}

		// save mappings in both directions for easy lookup. they are valid for 2 hrs (@todo maybe make this configurable?)
		ExternalCache::save($this->getSessionID(), $vs_token, 'SessionIDToServiceAuthTokens', 60 * 60 * 2);
		ExternalCache::save($vs_token, $this->getSessionID(), 'ServiceAuthTokensToSessionID', 60 * 60 * 2);

		return $vs_token;
	}

	/**
	 * Restore session form a temporary service auth token
	 * @param string $ps_token
	 * @param string|null $ps_name
	 * @return Session|bool The restored session, false on failure
	 */
	public static function restoreFromServiceAuthToken($ps_token, $ps_name=null) {
		$o_config = Configuration::load();
		$vs_app_name = $o_config->get("app_name");

		if(!ExternalCache::contains($ps_token, 'ServiceAuthTokensToSessionID')) {
			return false;
		}

		$vs_session_id = ExternalCache::fetch($ps_token, 'ServiceAuthTokensToSessionID');
		$_COOKIE[$vs_app_name] = $vs_session_id;

		return new Session($vs_app_name);
	}
	# ----------------------------------------
	# --- Methods
	# ----------------------------------------
	/**
	 * Returns client's session_id. 
	 */
	public function getSessionID() {
		return isset($_COOKIE[$this->name]) ? $_COOKIE[$this->name] : null;
	}
	# ----------------------------------------
	/**
	 * Removes session. Session key is no longer valid after this operation.
	 * Useful for logging out users (destroying the session destroys the login)
	 */
	public function deleteSession() {
		// nuke service token caches
		if($vs_token = $this->getServiceAuthToken(true)) {
			ExternalCache::delete($vs_token, 'ServiceAuthTokensToSessionID');
		}
		ExternalCache::delete($this->getSessionID(), 'SessionIDToServiceAuthTokens');

		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()- (24 * 60 * 60),'/');
		}
		// Delete session data
		ExternalCache::delete($this->getSessionID(), 'SessionVars');
		session_destroy();
	}
	# ----------------------------------------
	/**
	 * Set session variable
	 * @param string $ps_key variable key
	 * @param mixed $pm_val Session var may be number, string or array
	 * @param null|array $pa_options
	 * 		ENTITY_ENCODE_INPUT =
	 * 		URL_ENCODE_INPUT =
	 * @return bool
	 */
	public function setVar($ps_key, $pm_val, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		if ($ps_key && $this->getSessionID()) {
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
			$this->opa_changed_vars[$ps_key] = true;
			$this->opa_session_vars[$ps_key] = $vm_val;
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Delete session variable
	 * @param string $ps_key
	 */
	public function delete($ps_key) {
		$this->opa_changed_vars[$ps_key] = true;
		unset($this->opa_session_vars[$ps_key]);
	}
	# ----------------------------------------
	/**
	 * Get value of session variable. Var may be number, string or array.
	 */
	public function getVar($ps_key) {
		if(!$this->getSessionID()) { return null; }

		return isset($this->opa_session_vars[$ps_key]) ? $this->opa_session_vars[$ps_key] : null;
	}
	# ----------------------------------------
	/**
	 * Return names of all session vars
	 */
	public function getVarKeys() {
		return is_array($this->opa_session_vars) ? array_keys($this->opa_session_vars) : array();
	}
	# ----------------------------------------
	/**
	 * Save changes to session variables to persistent storage
	 */
	public function save() {
		if(isset($this->opa_session_vars['session_end_timestamp'])) {
			$vn_session_lifetime = abs(((int) $this->opa_session_vars['session_end_timestamp']) - time());
		} else {
			$vn_session_lifetime = 24 * 60 * 60;
		}
		
		// Get old vars
		$va_current_values = ExternalCache::fetch($this->getSessionID(), 'SessionVars');
		foreach(array_keys($this->opa_changed_vars) as $vs_key) {
			$va_current_values[$vs_key] = $this->opa_session_vars[$vs_key];
		}
		
		ExternalCache::save($this->getSessionID(), $va_current_values, 'SessionVars', $vn_session_lifetime);
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
		list($sm, $st) = explode(" ", $this->start_time);
		list($em, $et) = explode(" ",microtime());

		return sprintf("%4.{$pn_decimal_places}f", (($et+$em) - ($st+$sm)));
	}
	# ----------------------------------------
}
