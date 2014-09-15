<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/AuthManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
	 * @var string contains name of authentication adapter to use
	 */
	private static $g_authentication_adapter = '';

	/**
	 * Fetches authentication adapter from authentication.conf,
	 * loads the corresponding class if it exists and sets
	 * AuthenticationManager::$g_authentication_adapter accordingly.
	 *
	 * @throws AuthClassDoesNotExistException
	 */
	public static function init() {
		if((strlen(self::$g_authentication_adapter) == 0) || !class_exists(self::$g_authentication_adapter)) {
			$o_app_conf = Configuration::load();
			$o_auth_config = Configuration::load($o_app_conf->get('authentication_config'));

			$vs_auth_adapter = $o_auth_config->get('auth_adapter');

			if(file_exists(__CA_LIB_DIR__."/core/Auth/Adapters/{$vs_auth_adapter}.php")) {
				@require_once(__CA_LIB_DIR__."/core/Auth/Adapters/{$vs_auth_adapter}.php");

				if(class_exists($vs_auth_adapter.'AuthAdapter')) {
					self::$g_authentication_adapter = $vs_auth_adapter.'AuthAdapter';
				} else {
					throw new AuthClassDoesNotExistException();
				}
			} else {
				print 'nope';
			}
		}
	}

	/**
	 * Fetches name of current authentication adapter
	 *
	 * @return string adapter name
	 * @throws AuthClassDoesNotExistException
	 */
	public static function getAdapter() {
		if((strlen(self::$g_authentication_adapter) > 0) || class_exists(self::$g_authentication_adapter)) {
			return self::$g_authentication_adapter;
		} else {
			throw new AuthClassDoesNotExistException();
		}
	}

	/**
	 * Do authentication using authentication adapter from authentication.conf
	 *
	 * @param $ps_username User name (must be unique across all users)
	 * @param string $ps_password Password
	 * @param null $pa_options Associative array of options
	 * @return bool auth successful or not?
	 */
	public static function authenticate($ps_username, $ps_password="", $pa_options=null) {
		self::init();

		return call_user_func(self::$g_authentication_adapter.'::authenticate', $ps_username, $ps_password, $pa_options);
	}
}

class AuthClassDoesNotExistException extends Exception {}
