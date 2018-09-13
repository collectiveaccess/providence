<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/BaseAuthAdapter.php : base class for authentication adapters
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Auth/IAuthAdapter.php');

abstract class BaseAuthAdapter implements IAuthAdapter {

	/**
	 * Fallback if authentication adapter doesn't implement createUserAndGetPassword().
	 *
	 * @param $ps_username
	 * @param $ps_password
	 * @throws AuthClassFeatureException
	 * @return string
	 */
	public function createUserAndGetPassword($ps_username, $ps_password) {
		throw new AuthClassFeatureException(_t("Authentication back-end doesn't support creating new users programmatically."));
	}

	/**
	 * Fallback for getUserInfo()
	 *
	 * @param $ps_username
	 * @param $ps_password
	 * @param $pa_options
	 * @return array
	 * @throws AuthClassFeatureException
	 */
	public function getUserInfo($ps_username, $ps_password, $pa_options=null) {
		throw new AuthClassFeatureException();
	}

	/**
	 * Fallback if authentication adapter doesn't implement deleteUser(), which may be the
	 * case for some external authentication methods like OpenLDAP/slapd or ADS
	 *
	 * @param $ps_username
	 * @throws AuthClassFeatureException
	 * @return bool
	 */
	public function deleteUser($ps_username) {
		throw new AuthClassFeatureException(_t("Authentication back-end doesn't support deleting users programmatically."));
	}

	/**
	 * Fallback if authentication adapter doesn't implement updatePassword(), which may be the
	 * case for some external authentication methods like OpenLDAP/slapd or ADS
	 *
	 * @param $ps_username
	 * @param $ps_password
	 * @throws AuthClassFeatureException
	 * @return string
	 */
	public function updatePassword($ps_username, $ps_password) {
		throw new AuthClassFeatureException(_t("Authentication back-end doesn't updating existing users programmatically."));
	}

	/**
	 * Fallback if authentication adapter doesn't implement supports(). For more info @see IAuthAdapter::supports()
	 *
	 * @param int $pn_feature
	 * @return bool
	 */
	public function supports($pn_feature) {
		return false;
	}

	/**
	 * Fallback for accountManagementLink()
	 *
	 * @return false|string
	 */
	public function getAccountManagementLink() {
		return false;
	}
	

	/**
	 * Fallback for deauthenticate()
	 *
	 * @return false
	 */
	public function deauthenticate($pa_options=null) {
		return false;
	}
}
