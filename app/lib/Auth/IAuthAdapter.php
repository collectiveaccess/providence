<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/IAuthAdapter.php : interface for authentication adapters
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2818 Whirl-i-Gig
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

define('__CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__', 1);
define('__CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__', 2);
define('__CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__', 3);
define('__CA_AUTH_ADAPTER_FEATURE_USE_ADAPTER_LOGIN_FORM__', 4);

interface IAuthAdapter {

	/**
	 * Authenticates user
	 *
	 * @param string $ps_username user name
	 * @param string $ps_password cleartext password
	 * @param null $pa_options Associative array of options
	 * @return boolean
	 */
	public function authenticate($ps_username, $ps_password="", $pa_options=null);

	/**
	 * Creates new user in back-end. Should throw AuthClassFeatureException if not implemented. Note that while this is
	 * called when a new user is created in CollectiveAccess, it can be used to verify that the given credentials already exist in
	 * the back-end in question. You could, for instance, use this to check group membership or other access restrictions. If
	 * you want the CollectiveAccess user record insert() process to fail, throw an exception other than AuthClassFeatureException.
	 * Otherwise the corresponding table record in ca_users will be created.
	 *
	 * @param string $ps_username user name
	 * @param string $ps_password cleartext password
	 * @return string|null The (preferrably hashed/encoded) password to store in the ca_users table. Can be left empty for
	 * back-ends where it doesn't make any sense to store a password locally (e.g. LDAP or OAuth). Can also be used to store
	 * authentication tokens. The password you store here will be passed to authenticate() as-is.
	 */
	public function createUserAndGetPassword($ps_username, $ps_password);

	/**
	 * Get array containing field_name/value pairs for newly created records in the ca_users table, e.g. email, fname, lname.
	 *
	 * @param $ps_username
	 * @param $ps_password
	 * @param $pa_options
	 * @return array
	 */
	public function getUserInfo($ps_username, $ps_password, $pa_options=null);

	/**
	 * Deletes user. Should throw AuthClassFeatureException if not implemented.
	 *
	 * @param string $ps_username user name
	 * @return bool delete successful or not?
	 */
	public function deleteUser($ps_username);

	/**
	 * Updates password for existing user and returns it. Should throw AuthClassFeatureException if not implemented.
	 *
	 * @param string $ps_username user name
	 * @param string $ps_password cleartext password
	 * @return string|null The password to store in the ca_users table. Can be left empty for
	 * back-ends where it doesn't make any sense to store a password locally (e.g. LDAP or OAuth).
	 */
	public function updatePassword($ps_username, $ps_password);


	/**
	 * Indicates whether this Adapter supports a given feature. Adapter implementations should use these constants:
	 *
	 * __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__ = reset passwords programmatically. No support means CollectiveAccess'
	 * 		own reset password feature will be disabled
	 * __CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__ = ability to automatically create CollectiveAccess users on first login
	 * 		(e.g. by authenticating against and getting the user information from an external source like a directory service)
	 *
	 * @param int $pn_feature The feature to check for
	 * @return bool Is it implemented or not?
	 */
	public function supports($pn_feature);

	/**
	 * Gives implementations an option to place an account management link on the CollectiveAccess
	 * login page. This could for instance be a link to the account management web UI of your organization.
	 * Should return false if no link is to be displayed. Should be mutually exclusive with
	 * supports(__CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__), meaning your authentication adapter should
	 * either support one or the other (or none of them) but not both.
	 *
	 * @return false|string
	 */
	public function getAccountManagementLink();

}

class AuthClassFeatureException extends Exception {}
