<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/AuthenticationManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2025 Whirl-i-Gig
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
 * @subpackage Auth
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Auth/BaseAuthAdapter.php');

class AuthenticationManager {
	/**
	 * @var object contains instance of authentication configuration
	 */
	private static $g_authentication_conf = null;

	/**
	 * @var object contains instance of authentication adapter to use
	 */
	private static $g_authentication_adapter = null;

	/**
	 * Fetches authentication adapter from authentication.conf,
	 * loads the corresponding class if it exists and sets
	 * AuthenticationManager::$g_authentication_adapter accordingly.
	 *
	 * @param $adapter string Name of authentication adapter to use (CaUsers, ActiveDirectory, ExternalDB, OpenLDAP)
	 *
	 * @throws AuthClassDoesNotExistException
	 */
	public static function init($adapter=null) {
		global $g_request;
		if(!is_null($adapter) || (self::$g_authentication_adapter === null)) {
			AuthenticationManager::$g_authentication_conf = $o_auth_config = Configuration::load('authentication.conf');

			$auth_adapter = (!is_null($adapter)) ? $adapter : $o_auth_config->get('auth_adapter');
			
			if(
				AuthenticationManager::$g_authentication_conf->get('allow_force_to_ca_users_auth')
				&&
				$g_request
				&&
				!is_null($g_request->parameterExists('forceUserAuth', pInteger))
			) {
				Session::setVar('forceUserAuth', (bool)$g_request->getParameter('forceUserAuth', pInteger));
				Session::save();
			}
		
			if($fua = (bool)Session::getVar('forceUserAuth')) {
				$auth_adapter = 'CaUsers';
			}
			if(defined("__CA_IS_SERVICE_REQUEST__") && (bool)__CA_IS_SERVICE_REQUEST__ && ($auth_adapter_for_services = $o_auth_config->get('auth_adapter_for_services'))) {
				$auth_adapter = $auth_adapter_for_services;
			}
            
		    if ($is_local = (isset($_REQUEST['local']) && $_REQUEST['local'])) { $auth_adapter = 'CaUsers'; }
		
			$auth_adapter_file = __CA_LIB_DIR__."/Auth/Adapters/".$auth_adapter.".php";
			if(file_exists($auth_adapter_file)) {
				require_once($auth_adapter_file);

				$auth_class_name = $auth_adapter . 'AuthAdapter';
				if(class_exists($auth_class_name)) {
					self::$g_authentication_adapter = new $auth_class_name();
					return;
				}
			}

			throw new AuthClassDoesNotExistException();
		}
	}

	/**
	 * Do authentication using authentication adapter from authentication.conf
	 *
	 * @param string $username User name (must be unique across all users)
	 * @param string $password Password
	 * @param null $options Associative array of options
	 * @return bool auth successful or not?
	 */
	public static function authenticate($username, $password="", $options=null) {
		global $g_request;
		
		self::init();
		if(AuthenticationManager::isFree()) { return null; }

		if(
			(get_class(self::$g_authentication_adapter) !== 'CaUsersAuthAdapter')
			&&
			AuthenticationManager::$g_authentication_conf->get('allow_force_to_ca_users_auth')
			&&
			$g_request
			&&
			(bool)Session::getVar('forceUserAuth')
		) {
			self::init('CaUsers');
		}
		
		if ($rc = self::$g_authentication_adapter->authenticate($username, $password, $options)) {
			return $rc;
		}

		if ((AuthenticationManager::$g_authentication_conf->get('allow_fallback_to_ca_users_auth')) && !self::$g_authentication_adapter instanceof CaUsersAuthAdapter) {
			// fall back to ca_users "native" authentication
			self::init('CaUsers');
			$rc = self::$g_authentication_adapter->authenticate($username, $password, $options);
			self::$g_authentication_adapter = null;
			return $rc;
		}

		return null;
	}

	/**
	 * Create user using authentication adapter from authentication.conf
	 *
	 * @param string $username user name (must be unique across all users)
	 * @param string $password Clear-text password
	 * @return string|null The password to store in the ca_users table. Can be left empty for
	 * back-ends where it doesn't make any sense to store a password locally (e.g. LDAP or OAuth).
	 */
	public static function createUserAndGetPassword($username, $password) {
		self::init();

		return self::$g_authentication_adapter->createUserAndGetPassword($username, $password);
	}

	/**
	 * Delete existing user using authentication adapter from authentication.conf
	 *
	 * @param string $username user name (must be unique across all users)
	 * @return bool
	 */
	public static function deleteUser($username) {
		self::init();

		return self::$g_authentication_adapter->deleteUser($username);
	}

	/**
	 * Indicates whether this Adapter supports a given feature. Adapter implementations should use these constants:
	 *
	 * __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__ = reset passwords programmatically. No support means
	 *      CollectiveAccess' own reset password feature will be disabled
	 * __CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__ = allow users to update their password directly (not through the
	 *      password reset function)
	 * __CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__ = ability to automatically create CollectiveAccess users on first
	 *      login (e.g. by authenticating against and getting the user information from an external source like a
	 *      directory service)
	 *
	 * @param int $feature The feature to check for
	 * @return bool
	 */
	public static function supports($feature) {
		self::init();

		if ($feature == __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__) {
			if (!AuthenticationManager::$g_authentication_conf->get('auth_allow_password_reset')) {
				return false;
			}
		}

		return self::$g_authentication_adapter->supports($feature);
	}

	/**
	 * Get account management link from authentication adapter
	 *
	 * @return bool|string
	 */
	public static function getAccountManagementLink() {
		self::init();

		return self::$g_authentication_adapter->getAccountManagementLink();
	}

	/**
	 * Update password for existing user
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	public static function updatePassword($username, $password) {
		self::init();

		return self::$g_authentication_adapter->updatePassword($username, $password);
	}

	/**
	 * Get user info from back-end
	 *
	 * @param string $username
	 * @param string $password
	 * @param array $options Options include:
	 *		minimal = Return minimal info, at least the user name. [Default is false]
	 * @return array
	 */
	public static function getUserInfo($username, $password, $options=null) {
		self::init();
		if(AuthenticationManager::isFree()) { return null; }
		
		if ($rc = self::$g_authentication_adapter->getUserInfo($username, $password, $options)) {
			return $rc;
		}

		if ((AuthenticationManager::$g_authentication_conf->get('allow_fallback_to_ca_users_auth')) && !self::$g_authentication_adapter instanceof CaUsersAuthAdapter) {
			// fall back to ca_users "native" authentication
			self::init('CaUsers');
			$rc = self::$g_authentication_adapter->getUserInfo($username, $password);
			self::$g_authentication_adapter = null;
			return $rc;
		}

		return null;
	}
	
	/**
	 * Deauthentication using authentication adapter from authentication.conf
	 *
	 * @param null $options Associative array of options
	 * @return bool auth successful or not?
	 */
	public static function deauthenticate($options=null) {
		self::init();

		if ($rc = self::$g_authentication_adapter->deauthenticate($options)) {
			return $rc;
		}

		return null;
	}
	
	/**
	 * Callback handler for adapters that require a callback for control flow (Eg. Okta)
	 */
	public static function callback($options=null) {
		self::init();

		if (method_exists(self::$g_authentication_adapter, "callback") && ($rc = self::$g_authentication_adapter->callback($options))) {
			return $rc;
		}

		return null;
	}
	
	/**
	 *
	 */
	private static function isFree() {
		global $g_request;
		if($g_request) {
			if (!is_array($free_controllers = AuthenticationManager::$g_authentication_conf->get('auth_not_required_for_controllers'))) { return false; }
			$free_controllers = array_map("strtolower", $free_controllers);
			if (in_array(strtolower($g_request->getController()), $free_controllers)) { return true; }
		}
		return false;
	}
}

class AuthClassDoesNotExistException extends Exception {}
