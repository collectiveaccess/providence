<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/Adapters/CaUsers.php : default authentication backend
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Auth/PasswordHash.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');

class CaUsersAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	# --------------------------------------------------------------------------------
	public function authenticate($ps_username, $ps_password = '', $pa_options=null) {

		$t_user = new ca_users();
		$t_user->load(['user_name' => $ps_username]);

		if(($t_user->getPrimaryKey() > 0) && ($t_user->get('active') == 1) && ($t_user->get('userclass') <> 255)) {

			$vs_hash = $t_user->get('password');
			if(preg_match('/^[a-f0-9]{32}$/', $vs_hash)) { // old-style md5 passwords
				$vb_valid = hash_equals($vs_hash, md5((string)$ps_password));
			} else {
				$vb_valid = validate_password($ps_password, $vs_hash);
			}

			// Move legacy (md5, PBKDF2) and outdated hashes over to the current scheme. The new hash is written directly
			// rather than via ca_users::update() so password policy checks, which apply when choosing a password,
			// can't block upgrading the hash of an existing one.
			if ($vb_valid && hash_needs_upgrade($vs_hash)) {
				$t_user->getDb()->query("UPDATE ca_users SET password = ? WHERE user_id = ?", [create_hash($ps_password), (int)$t_user->getPrimaryKey()]);
			}
			return $vb_valid;
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------
	public function createUserAndGetPassword($ps_username, $ps_password) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		return create_hash($ps_password);
	}
	# --------------------------------------------------------------------------------
	public function supports($pn_feature) {
		switch($pn_feature){
			case __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__:
			case __CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__:
				return true;
			case __CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__:
			default:
				return false;
		}
	}
	# --------------------------------------------------------------------------------
	public function updatePassword($ps_username, $ps_password) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		return create_hash($ps_password);
	}
	# --------------------------------------------------------------------------------
	public function deleteUser($ps_username) {
		// ca_users takes care of deleting the db row for us. Nothing else to do here.
		return true;
	}
	# --------------------------------------------------------------------------------
}

class CaUsersException extends Exception {}
