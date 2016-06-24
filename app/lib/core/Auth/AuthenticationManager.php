<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/AuthenticationManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/Auth/BaseAuthAdapter.php');

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
	 * @param $ps_adapter string Name of authentication adapter to use (CaUsers, ActiveDirectory, ExternalDB, OpenLDAP)
	 *
	 * @throws AuthClassDoesNotExistException
	 */
	public static function init($ps_adapter=null) {
		if(!is_null($ps_adapter) || (self::$g_authentication_adapter === null)) {
			AuthenticationManager::$g_authentication_conf = $o_auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");

			$vs_auth_adapter = (!is_null($ps_adapter)) ? $ps_adapter : $o_auth_config->get('auth_adapter');

			$vs_auth_adapter_file = __CA_LIB_DIR__."/core/Auth/Adapters/".$vs_auth_adapter.".php";
			if(file_exists($vs_auth_adapter_file)) {
				require_once($vs_auth_adapter_file);

				$vs_auth_class_name = $vs_auth_adapter . 'AuthAdapter';
				if(class_exists($vs_auth_class_name)) {
					self::$g_authentication_adapter = new $vs_auth_class_name();
					return;
				}
			}

			throw new AuthClassDoesNotExistException();
		}
	}

	/**
	 * Do authentication using authentication adapter from authentication.conf
	 *
	 * @param string $ps_username User name (must be unique across all users)
	 * @param string $ps_password Password
	 * @param null $pa_options Associative array of options
	 * @return bool auth successful or not?
	 */
	public static function authenticate($ps_username, $ps_password="", $pa_options=null) {
		self::init();

		if ($vn_rc = self::$g_authentication_adapter->authenticate($ps_username, $ps_password, $pa_options)) {
			return $vn_rc;
		}

		if ((AuthenticationManager::$g_authentication_conf->get('allow_fallback_to_ca_users_auth')) && !self::$g_authentication_adapter instanceof CaUsersAuthAdapter) {
			// fall back to ca_users "native" authentication
			self::init('CaUsers');
			$vn_rc = self::$g_authentication_adapter->authenticate($ps_username, $ps_password, $pa_options);
			self::$g_authentication_adapter = null;
			return $vn_rc;
		}

		return null;
	}

	/**
	 * Create user using authentication adapter from authentication.conf
	 *
	 * @param string $ps_username user name (must be unique across all users)
	 * @param string $ps_password Clear-text password
	 * @return string|null The password to store in the ca_users table. Can be left empty for
	 * back-ends where it doesn't make any sense to store a password locally (e.g. LDAP or OAuth).
	 */
	public static function createUserAndGetPassword($ps_username, $ps_password) {
		self::init();

		return self::$g_authentication_adapter->createUserAndGetPassword($ps_username, $ps_password);
	}

	/**
	 * Delete existing user using authentication adapter from authentication.conf
	 *
	 * @param string $ps_username user name (must be unique across all users)
	 * @return bool
	 */
	public static function deleteUser($ps_username) {
		self::init();

		return self::$g_authentication_adapter->deleteUser($ps_username);
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
	 * @param int $pn_feature The feature to check for
	 * @return bool
	 */
	public static function supports($pn_feature) {
		self::init();

		if ($pn_feature == __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__) {
			if (!AuthenticationManager::$g_authentication_conf->get('auth_allow_password_reset')) {
				return false;
			}
		}

		return self::$g_authentication_adapter->supports($pn_feature);
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
	 * @param string $ps_username
	 * @param string $ps_password
	 * @return bool
	 */
	public static function updatePassword($ps_username, $ps_password) {
		self::init();

		return self::$g_authentication_adapter->updatePassword($ps_username, $ps_password);
	}

	/**
	 * Get user info from back-end
	 *
	 * @param string $ps_username
	 * @param string $ps_password
	 * @return array
	 */
	public static function getUserInfo($ps_username, $ps_password) {
		self::init();

		if ($vn_rc = self::$g_authentication_adapter->getUserInfo($ps_username, $ps_password)) {
			return $vn_rc;
		}

		if ((AuthenticationManager::$g_authentication_conf->get('allow_fallback_to_ca_users_auth')) && !self::$g_authentication_adapter instanceof CaUsersAuthAdapter) {
			// fall back to ca_users "native" authentication
			self::init('CaUsers');
			$vn_rc = self::$g_authentication_adapter->getUserInfo($ps_username, $ps_password);
			self::$g_authentication_adapter = null;
			return $vn_rc;
		}

		return null;
	}
}

class AuthClassDoesNotExistException extends Exception {}
