<?php
/** ---------------------------------------------------------------------
 * app/lib/Controller/Request/Session.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2018 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/ApplicationError.php");
require_once(__CA_LIB_DIR__."/Configuration.php");
require_once(__CA_LIB_DIR__."/Db.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

register_shutdown_function("Session::save");

class Session {
	# ----------------------------------------
	# --- Properties
	# ----------------------------------------
	/**
	 *
	 */
	private static $domain = ""; 	# domain session is registered to (eg. "www.whirl-i-gig.com"); blank means domain cookie was set from
	
	/**
	 *
	 */
	private static $lifetime = 0;	# session lives for $lifetime minutes; session exists for entire browser session if 0
	
	/**
	 *
	 */
	private static $name = "";		# application name

	/**
	 *
	 */
	private static $start_time = 0;	# microtime session object was created - used for page performance measurements

	/**
	 * In-memory session var storage
	 * @var array
	 */
	public static $s_session_vars = [];
	
	/**
	 *
	 *
	 */
	public static $s_changed_vars = [];
	
	# ----------------------------------------
	# --- Constructor
	# ----------------------------------------
	/**
	 * @param string $ps_app_name An app name to use if no app name is configured in the application configuration file.
	 * @param bool $pb_dont_create_new_session No new session will be created if set to true. Default is false.
	 */
	public static function init($ps_app_name=null, $pb_dont_create_new_session=false) {
 		$o_config = Configuration::load();
		# --- Init
		if (defined("__CA_MICROTIME_START_OF_REQUEST__")) {
			Session::$start_time = __CA_MICROTIME_START_OF_REQUEST__;
		} else {
			Session::$start_time = microtime();
		}
		
		# --- Read configuration
		Session::$name = ($vs_app_name = $o_config->get("app_name")) ? $vs_app_name : $ps_app_name;
		Session::$domain = $o_config->get("session_domain");
		Session::$lifetime = (int) $o_config->get("session_lifetime");

		if(!Session::$lifetime) {
			Session::$lifetime = 3600 * 24 * 7;
		}
		
		if (!$pb_dont_create_new_session) {
			// try to get session ID from cookie. if that doesn't work, generate a new one
			if (!($session_id = self::getSessionID())) {
				$vs_cookiepath = ((__CA_URL_ROOT__== '') ? '/' : __CA_URL_ROOT__);
				if (!caIsRunFromCLI()) { setcookie(Session::$name, $_COOKIE[Session::$name] = $session_id = caGenerateGUID(), Session::$lifetime ? time() + Session::$lifetime : null, $vs_cookiepath); }
		 	}

			// initialize in-memory session var storage, either restored from external cache or newly initialized
			if($session_id && ExternalCache::contains($session_id, 'SessionVars')) {
				if(!is_array(Session::$s_session_vars = unserialize(gzuncompress(ExternalCache::fetch($session_id, 'SessionVars'))))) {
					Session::$s_session_vars = [];
				}
			} else {
				Session::$s_session_vars = [];
				if($session_id) {
					ExternalCache::delete($session_id, 'SessionVars');
				}
				Session::$s_changed_vars['session_end_timestamp'] = true;
				Session::$s_session_vars['session_end_timestamp'] = time() + Session::$lifetime;
			}

			// kill session if it has ended or we don't have a timestamp
			if(
				!isset(Session::$s_session_vars['session_end_timestamp'])
				||
				(is_numeric(Session::$s_session_vars['session_end_timestamp']) && (time() > Session::$s_session_vars['session_end_timestamp']))
			) {
				Session::$s_session_vars = Session::$s_changed_vars['session_end_timestamp'] = array();
				ExternalCache::delete($session_id, 'SessionVars');
			}
		}
		return $session_id;
	}
	# ----------------------------------------
	/**
	 * Return service authentication token for this session (and create it, if none exists yet).
	 * These tokens usually have a much shorter lifetime than the session.
	 * @param bool $pb_dont_create_new_token dont create new auth token
	 * @return string|bool The token, false if
	 * @throws Exception
	 */
	static public function getServiceAuthToken($pb_dont_create_new_token=false) {
		if(!($session_id = self::getSessionID())) { return false; }

		if(ExternalCache::contains($session_id, 'SessionIDToServiceAuthTokens')) {
			return ExternalCache::fetch($session_id, 'SessionIDToServiceAuthTokens');
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
		ExternalCache::save($session_id, $vs_token, 'SessionIDToServiceAuthTokens', 60 * 60 * 2);
		ExternalCache::save($vs_token, $session_id, 'ServiceAuthTokensToSessionID', 60 * 60 * 2);

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

		return Session::init($vs_app_name);
	}
	# ----------------------------------------
	# --- Methods
	# ----------------------------------------
	/**
	 * Returns client's session_id. 
	 */
	public static function getSessionID() {
		return isset($_COOKIE[Session::$name]) ? $_COOKIE[Session::$name] : null;
	}
	# ----------------------------------------
	/**
	 * Removes session. Session key is no longer valid after this operation.
	 * Useful for logging out users (destroying the session destroys the login)
	 */
	 public static function deleteSession() {
		if(!($session_id = self::getSessionID())) { return false; }
		// nuke service token caches
		if($vs_token = self::getServiceAuthToken(true)) {
			ExternalCache::delete($vs_token, 'ServiceAuthTokensToSessionID');
		}
		ExternalCache::delete($session_id, 'SessionIDToServiceAuthTokens');

		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()- (24 * 60 * 60),'/');
			@session_destroy();
		}
		// Delete session data
		unset($_COOKIE[Session::$name]);
		setCookie(Session::$name, "", time()-3600);
		ExternalCache::delete($session_id, 'SessionVars');
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
	public static function setVar($ps_key, $pm_val, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		if ($ps_key && self::getSessionID()) {
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
			Session::$s_changed_vars[$ps_key] = true;
			Session::$s_session_vars[$ps_key] = $vm_val;
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Delete session variable
	 * @param string $ps_key
	 */
	public static function delete($ps_key) {
		Session::$s_changed_vars[$ps_key] = true;
		unset(Session::$s_session_vars[$ps_key]);
	}
	# ----------------------------------------
	/**
	 * Get value of session variable. Var may be number, string or array.
	 */
	public static function getVar($ps_key) {
		if(!self::getSessionID()) { return null; }

		return isset(Session::$s_session_vars[$ps_key]) ? Session::$s_session_vars[$ps_key] : null;
	}
	# ----------------------------------------
	/**
	 * Return names of all session vars
	 */
	public static function getVarKeys() {
		return is_array(Session::$s_session_vars) ? array_keys(Session::$s_session_vars) : array();
	}
	# ----------------------------------------
	/**
	 * Save changes to session variables to persistent storage
	 */
	public static function save() {
		if(!($session_id = self::getSessionID())) { return false; }
		if(isset(Session::$s_session_vars['session_end_timestamp'])) {
			$vn_session_lifetime = abs(((int) Session::$s_session_vars['session_end_timestamp']) - time());
		} else {
			$vn_session_lifetime = 86400;	// 24 hours
		}
		if ($vn_session_lifetime > (86400 * 30)) {		// max 30 days
			$vn_session_lifetime = 86400 * 30;
		}
		
		// Get old vars
		if (!is_array($va_current_values = @unserialize(@gzuncompress(ExternalCache::fetch($session_id, 'SessionVars'))))) {
			$va_current_values = [];
		}
		
		$va_current_values = array_merge($va_current_values, Session::$s_session_vars);
		
		ExternalCache::save($session_id, @gzcompress(@serialize($va_current_values)), 'SessionVars', $vn_session_lifetime);
	}
	# ----------------------------------------
	# --- Page performance
	# ----------------------------------------
	# Return number of seconds since request processing began
	public static function elapsedTime($pn_decimal_places=4) {
		list($sm, $st) = explode(" ", Session::$start_time);
		list($em, $et) = explode(" ",microtime());

		return sprintf("%4.{$pn_decimal_places}f", (($et+$em) - ($st+$sm)));
	}
	# ----------------------------------------
}
