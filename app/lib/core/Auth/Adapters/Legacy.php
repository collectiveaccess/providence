<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/Adapters/Legacy.php : legacy authentication backend
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
require_once(__CA_LIB_DIR__.'/core/Auth/PasswordHash.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');

class LegacyAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	# --------------------------------------------------------------------------------
	public static function authenticate($ps_username, $ps_password = '', $pa_options=null) {

		$t_user = new ca_users();

		$t_user->load($ps_username);

		if($t_user->getPrimaryKey() > 0) {

			if (md5($ps_password) == $t_user->get('password')) {
				// if the md5 hash matches, authenticate successfully and move the user over to pbkdf2
				$t_user->setMode(ACCESS_WRITE);
				$t_user->set('password', create_hash($ps_password));
				$t_user->update();
				return true;
			} else if(validate_password($ps_password, $t_user->get('password'))) {
				// user has already been moved over
				return true;
			}
		}

		return false;
	}
	# --------------------------------------------------------------------------------
	public static function createUserAndGetPassword($ps_username, $ps_password) {
		// ca_users takes care of creating the backend record for us. There's nothing else to do here
		return md5($ps_password);
	}
	# --------------------------------------------------------------------------------
	public static function supports($pn_feature) {
		switch($pn_feature){
			case __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__:
				return true;
			case __CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__:
			default:
				return false;
		}
	}
	# --------------------------------------------------------------------------------
	public static function updatePassword($ps_username, $ps_password) {
		// ca_users takes care of updating the backend record for us. There's nothing else to do here
		return md5($ps_password);
	}
	# --------------------------------------------------------------------------------
	public static function deleteUser($ps_username) {
		// ca_users takes care of deleting the db row for us. Nothing else to do here.
		return true;
	}
	# --------------------------------------------------------------------------------
}
