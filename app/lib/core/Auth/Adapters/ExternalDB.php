<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/Adapters/ExternalDB.php : External database authentication backend
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

class ExternalDBAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	# --------------------------------------------------------------------------------
	public function authenticate($ps_username, $ps_password = '', $pa_options=null) {
		if(!$ps_username) {
			return false;
		}

		$o_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));

		$o_log = new Eventlog();

		// external database config
		$vs_extdb_host = $o_auth_config->get("extdb_host");
		$vs_extdb_username = $o_auth_config->get("extdb_username");
		$vs_extdb_password = $o_auth_config->get("extdb_password");
		$vs_extdb_database = $o_auth_config->get("extdb_database");
		$vs_extdb_db_type = $o_auth_config->get("extdb_db_type");

		$o_ext_db = new Db(null, array(
			'host' 		=> $vs_extdb_host,
			'username' 	=> $vs_extdb_username,
			'password' 	=> $vs_extdb_password,
			'database' 	=> $vs_extdb_database,
			'type' 		=> $vs_extdb_db_type,
			'persistent_connections' => true
		), false);

		// couldn't connect to external database
		if(!$o_ext_db->connected()) {
			$o_log->log(array(
				'CODE' => 'LOGF', 'SOURCE' => 'ExternalDBAuthAdapter',
				'MESSAGE' => _t('Could not login user %1 using external database because login to external database failed [%2]', $ps_username, $_SERVER['REMOTE_ADDR'])
			));
			return false;
		}

		$vs_extdb_table = $o_auth_config->get("extdb_table");
		$vs_extdb_username_field = $o_auth_config->get("extdb_username_field");
		$vs_extdb_password_field = $o_auth_config->get("extdb_password_field");

		switch(strtolower($o_auth_config->get("extdb_password_hash_type"))) {
			case 'md5':
				$ps_password_proc = md5($ps_password);
				break;
			case 'sha1':
				$ps_password_proc = sha1($ps_password);
				break;
			default: // clear-text
				$ps_password_proc = $ps_password;
				break;
		}

		// Authenticate user against extdb
		$qr_auth = $o_ext_db->query(
			"SELECT * FROM {$vs_extdb_table} WHERE {$vs_extdb_username_field} = ? AND {$vs_extdb_password_field} = ?"
		, array($ps_username, $ps_password_proc));

		if($qr_auth && $qr_auth->nextRow()) {
			return true;
		}

		return false;
	}
	# --------------------------------------------------------------------------------
	public function createUserAndGetPassword($ps_username, $ps_password) {
		// We don't create users in external databases, we assume they're already there

		// We will create a password hash that is compatible with the CaUsers authentication adapter though
		// That way users could, in theory, turn off external db authentication later. The hash will not be used
		// for authentication in this adapter though.
		return create_hash($ps_password);
	}
	# --------------------------------------------------------------------------------
	public function getUserInfo($ps_username, $ps_password) {
		$o_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));

		// external database config
		$vs_extdb_host = $o_auth_config->get("extdb_host");
		$vs_extdb_username = $o_auth_config->get("extdb_username");
		$vs_extdb_password = $o_auth_config->get("extdb_password");
		$vs_extdb_database = $o_auth_config->get("extdb_database");
		$vs_extdb_db_type = $o_auth_config->get("extdb_db_type");

		$o_ext_db = new Db(null, array(
			'host' 		=> $vs_extdb_host,
			'username' 	=> $vs_extdb_username,
			'password' 	=> $vs_extdb_password,
			'database' 	=> $vs_extdb_database,
			'type' 		=> $vs_extdb_db_type,
			'persistent_connections' => true
		), false);

		// couldn't connect to external database
		if(!$o_ext_db->connected()) {
			throw new ExternalDBException(_t('Could not login user %1 using external database because login to external database failed [%2]', $ps_username, $_SERVER['REMOTE_ADDR']));
		}

		$vs_extdb_table = $o_auth_config->get("extdb_table");
		$vs_extdb_username_field = $o_auth_config->get("extdb_username_field");
		$vs_extdb_password_field = $o_auth_config->get("extdb_password_field");

		switch(strtolower($o_auth_config->get("extdb_password_hash_type"))) {
			case 'md5':
				$ps_password_proc = md5($ps_password);
				break;
			case 'sha1':
				$ps_password_proc = sha1($ps_password);
				break;
			default: // clear-text
				$ps_password_proc = $ps_password;
				break;
		}

		// Authenticate user against extdb
		$qr_auth = $o_ext_db->query(
			"SELECT * FROM {$vs_extdb_table} WHERE {$vs_extdb_username_field} = ? AND {$vs_extdb_password_field} = ?"
			, array($ps_username, $ps_password_proc));

		if(!$qr_auth || !$qr_auth->nextRow()) {
			throw new ExternalDBException(_t('Could not login user %1 using external database because external authentication failed [%2]', $ps_username, $_SERVER['REMOTE_ADDR']));
		}

		$va_return = array();

		$va_return['user_name'] = $ps_username;

		// Determine value for ca_users.active
		$vn_active = (int)$o_auth_config->get('extdb_default_active');

		$va_extdb_active_field_map = $o_auth_config->getAssoc('extdb_active_field_map');
		if (($vs_extdb_active_field = $o_auth_config->get('extdb_active_field')) && is_array($va_extdb_active_field_map)) {

			if (isset($va_extdb_active_field_map[$vs_active_val = $qr_auth->get($vs_extdb_active_field)])) {
				$vn_active = (int)$va_extdb_active_field_map[$vs_active_val];
			}
		}

		$va_return['active'] = $vn_active;

		// Determine value for ca_users.user_class
		$vs_extdb_access_value = strtolower($o_auth_config->get('extdb_default_access'));

		$va_extdb_access_field_map = $o_auth_config->getAssoc('extdb_access_field_map');
		if (($vs_extdb_access_field = $o_auth_config->get('extdb_access_field')) && is_array($va_extdb_access_field_map)) {

			if (isset($va_extdb_access_field_map[$vs_access_val = $qr_auth->get($vs_extdb_access_field)])) {
				$vs_extdb_access_value = strtolower($va_extdb_access_field_map[$vs_access_val]);
			}
		}

		switch($vs_extdb_access_value) {
			case 'public':
				$vn_user_class = 1;
				break;
			case 'full':
				$vn_user_class = 0;
				break;
			default:
				// Can't log in - no access
				throw new ExternalDBException(_t('Could not login user %1 after authentication from external database because user class was not set.', $ps_username));
		}

		$va_return['userclass'] = $vn_user_class;

		// map fields
		if (is_array($va_extdb_user_field_map = $o_auth_config->getAssoc('extdb_user_field_map'))) {
			foreach($va_extdb_user_field_map as $vs_extdb_field => $vs_ca_field) {
				$va_return[$vs_ca_field] = $qr_auth->get($vs_extdb_field);
			}
		}

		// map preferences
		if (is_array($va_extdb_user_pref_map = $o_auth_config->getAssoc('extdb_user_pref_map'))) {
			$va_return['preferences'] = array();
			foreach($va_extdb_user_pref_map as $vs_extdb_field => $vs_ca_pref) {
				$va_return['preferences'][$vs_ca_pref] = $qr_auth->get($vs_extdb_field);
			}
		}

		// set user roles
		$va_extdb_user_roles = $o_auth_config->getAssoc('extdb_default_roles');

		$va_extdb_roles_field_map = $o_auth_config->getAssoc('extdb_roles_field_map');
		if (($vs_extdb_roles_field = $o_auth_config->get('extdb_roles_field')) && is_array($va_extdb_roles_field_map)) {

			if (isset($va_extdb_roles_field_map[$vs_roles_val = $qr_auth->get($vs_extdb_roles_field)])) {
				$va_extdb_user_roles = $va_extdb_roles_field_map[$vs_roles_val];
			}
		}
		if(!is_array($va_extdb_user_roles)) { $va_extdb_user_roles = array(); }
		if(sizeof($va_extdb_user_roles)) { $va_return['roles'] = $va_extdb_user_roles; }

		// set user groups
		$va_extdb_user_groups = $o_auth_config->getAssoc('extdb_default_groups');

		$va_extdb_groups_field_map = $o_auth_config->getAssoc('extdb_groups_field_map');
		if (($vs_extdb_groups_field = $o_auth_config->get('extdb_groups_field')) && is_array($va_extdb_groups_field_map)) {

			if (isset($va_extdb_groups_field_map[$vs_groups_val = $qr_auth->get($vs_extdb_groups_field)])) {
				$va_extdb_user_groups = $va_extdb_groups_field_map[$vs_groups_val];
			}
		}
		if(!is_array($va_extdb_user_groups)) { $va_extdb_user_groups = array(); }
		if(sizeof($va_extdb_user_groups)) { $va_return['groups'] = $va_extdb_user_groups; }

		return $va_return;
	}
	# --------------------------------------------------------------------------------
	public function supports($pn_feature) {
		switch($pn_feature){
			case __CA_AUTH_ADAPTER_FEATURE_AUTOCREATE_USERS__:
				return true;
			case __CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__:
			case __CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__:
			default:
				return false;
		}
	}
	# --------------------------------------------------------------------------------
	public function deleteUser($ps_username) {
		// do something?
		return true;
	}
	# --------------------------------------------------------------------------------
	public function getAccountManagementLink() {
		$o_auth_cfg = Configuration::load(Configuration::load()->get('authentication_config'));

		if($vs_link = $o_auth_cfg->get('manage_account_url')) {
			return $vs_link;
		}

		return false;
	}
	# --------------------------------------------------------------------------------
}

class ExternalDBException extends Exception {}