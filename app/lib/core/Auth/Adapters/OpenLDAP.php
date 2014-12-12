<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/Adapters/OpenLDAP.php : OpenLDAP authentication backend
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

class OpenLDAPAuthAdapter extends BaseAuthAdapter implements IAuthAdapter {
	# --------------------------------------------------------------------------------
	public static function authenticate($ps_username, $ps_password = '', $pa_options=null) {
		$po_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));
		
		if(!function_exists("ldap_connect")){
			throw new OpenLDAPException(_t("PHP's LDAP module is required for LDAP authentication!"));
		}

		// ldap config
		$vs_ldaphost = $po_auth_config->get("ldap_host");
		$vs_ldapport = $po_auth_config->get("ldap_port");
		$vs_base_dn = $po_auth_config->get("ldap_base_dn");
		$vs_user_ou = $po_auth_config->get("ldap_user_ou");
		$vs_bind_rdn = self::postProcessLDAPConfigValue("ldap_bind_rdn_format", $ps_username, $vs_user_ou, $vs_base_dn);
		$va_default_roles = $po_auth_config->get("ldap_users_default_roles");
		if(!is_array($va_default_roles)) { $va_default_roles = array(); }
		$va_default_groups = $po_auth_config->get("ldap_users_default_groups");
		if(!is_array($va_default_groups)) { $va_default_groups = array(); }


		$vo_ldap = ldap_connect($vs_ldaphost,$vs_ldapport);
		ldap_set_option($vo_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);

		if (!$vo_ldap) {
			return false;
		}

		$vs_bind_rdn_filter = self::postProcessLDAPConfigValue("ldap_bind_rdn_filter", $ps_username, $vs_user_ou, $vs_base_dn);
		if(strlen($vs_bind_rdn_filter)>0) {
			$vo_dn_search_results = ldap_search($vo_ldap, $vs_base_dn, $vs_bind_rdn_filter);
			$va_dn_search_results = ldap_get_entries($vo_ldap, $vo_dn_search_results);
			if(isset($va_dn_search_results[0]['dn'])) {
				$vs_bind_rdn = $va_dn_search_results[0]['dn'];
			}
		}

		// log in
		$vo_bind = @ldap_bind($vo_ldap, $vs_bind_rdn, $ps_password);
		if(!$vo_bind) { // wrong credentials
			if (ldap_get_option($vo_ldap, 0x0032, $extended_error)) {
				caLogEvent("ERR", "LDAP ERROR (".ldap_errno($vo_ldap).") {$extended_error} [{$vs_bind_rdn}]", "OpenLDAP::Authenticate");
			
				print "LDAP ERROR (".ldap_errno($vo_ldap).") {$extended_error} [{$vs_bind_rdn}]\n";
			}
			ldap_unbind($vo_ldap);
			return false;
		}

		// check group membership
		if(!self::isMemberinAtLeastOneGroup($ps_username, $vo_ldap)) {
			ldap_unbind($vo_ldap);
			return false;
		}

		// user role and group membership syncing with directory
		$t_user = new ca_users();
		if($t_user->load($ps_username)) { // don't try to sync roles for non-existing users (the first auth call is before the user is actually created)

			if($po_auth_config->get('ldap_sync_user_roles')) {
				$va_expected_roles = array_merge($va_default_roles, self::getRolesToAddFromDirectory($ps_username, $vo_ldap));

				foreach($va_expected_roles as $vs_role) {
					if(!$t_user->hasUserRole($vs_role)) {
						$t_user->addRoles($vs_role);
					}
				}

				foreach($t_user->getUserRoles() as $vn_id => $va_role_info) {
					if(!in_array($va_role_info['code'], $va_expected_roles)) {
						$t_user->removeRoles($vn_id);
					}
				}
			}

			if($po_auth_config->get('ldap_sync_user_groups')) {
				$va_expected_groups = array_merge($va_default_groups, self::getGroupsToAddFromDirectory($ps_username, $vo_ldap));

				foreach($va_expected_groups as $vs_group) {
					if(!$t_user->inGroup($vs_group)) {
						$t_user->addToGroups($vs_group);
					}
				}

				foreach($t_user->getUserGroups() as $vn_id => $va_group_info) {
					if(!in_array($va_group_info['code'], $va_expected_groups)) {
						$t_user->removeFromGroups($vn_id);
					}
				}
			}

		}

		ldap_unbind($vo_ldap);
		return true;
	}
	# --------------------------------------------------------------------------------
	public static function createUserAndGetPassword($ps_username, $ps_password) {
		// We don't create users in directories, we assume they're already there

		// We will create a password hash that is compatible with the CaUsers authentication adapter though
		// That way users could, in theory, turn off LDAP authentication later. The hash will not be used
		// for authentication in this adapter though.
		return create_hash($ps_password);
	}
	# --------------------------------------------------------------------------------
	public static function getUserInfo($ps_username, $ps_password) {
		$po_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));

		if(!function_exists("ldap_connect")){
			throw new OpenLDAPException(_t("PHP's LDAP module is required for LDAP authentication!"));
		}

		// ldap config
		$vs_ldaphost = $po_auth_config->get("ldap_host");
		$vs_ldapport = $po_auth_config->get("ldap_port");
		$vs_base_dn = $po_auth_config->get("ldap_base_dn");
		$vs_user_ou = $po_auth_config->get("ldap_user_ou");
		$vs_attribute_email = $po_auth_config->get("ldap_attribute_email");
		$vs_attribute_fname = $po_auth_config->get("ldap_attribute_fname");
		$vs_attribute_lname = $po_auth_config->get("ldap_attribute_lname");
		$vs_bind_rdn = self::postProcessLDAPConfigValue("ldap_bind_rdn_format", $ps_username, $vs_user_ou, $vs_base_dn);
		$vs_search_dn = self::postProcessLDAPConfigValue("ldap_user_search_dn_format", $ps_username, $vs_user_ou, $vs_base_dn);
		$vs_search_filter = self::postProcessLDAPConfigValue("ldap_user_search_filter_format", $ps_username, $vs_user_ou, $vs_base_dn);
		$va_default_roles = $po_auth_config->get("ldap_users_default_roles");
		if(!is_array($va_default_roles)) { $va_default_roles = array(); }
		$va_default_groups = $po_auth_config->get("ldap_users_default_groups");
		if(!is_array($va_default_groups)) { $va_default_groups = array(); }

		$vo_ldap = ldap_connect($vs_ldaphost,$vs_ldapport);
		if (!$vo_ldap) {
			throw new OpenLDAPException(_t("Could not connect to LDAP server."));
		}
		ldap_set_option($vo_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);

		$vs_bind_rdn_filter = self::postProcessLDAPConfigValue("ldap_bind_rdn_filter", $ps_username, $vs_user_ou, $vs_base_dn);
		if(strlen($vs_bind_rdn_filter)>0) {
			$vo_dn_search_results = ldap_search($vo_ldap, $vs_base_dn, $vs_bind_rdn_filter);
			$va_dn_search_results = ldap_get_entries($vo_ldap, $vo_dn_search_results);
			if(isset($va_dn_search_results[0]['dn'])) {
				$vs_bind_rdn = $va_dn_search_results[0]['dn'];
			}
		}

		$vo_bind = @ldap_bind($vo_ldap, $vs_bind_rdn, $ps_password);
		if (!$vo_bind) {
			// wrong credentials
			ldap_unbind($vo_ldap);
			throw new OpenLDAPException(_t("User could not be authenticated with LDAP server."));
		}

		// check group membership
		if(!self::isMemberinAtLeastOneGroup($ps_username, $vo_ldap)) {
			ldap_unbind($vo_ldap);
			throw new OpenLDAPException(_t("User is not member of at least one of the required groups."));
		}

		/* query directory service for additional info on user */
		$vo_results = @ldap_search($vo_ldap, $vs_search_dn, $vs_search_filter);
		if (!$vo_results) {
			// search error
			$vs_message = _t("LDAP search error: %1", ldap_error($vo_ldap));
			ldap_unbind($vo_ldap);
			throw new OpenLDAPException($vs_message);
		}

		$vo_entry = ldap_first_entry($vo_ldap, $vo_results);
		if (!$vo_entry) {
			// no results returned
			ldap_unbind($vo_ldap);
			throw new OpenLDAPException(_t("User could not be found."));
		}

		$va_attrs = ldap_get_attributes($vo_ldap, $vo_entry);

		$va_return = array();

		$va_return['email'] = $va_attrs[$vs_attribute_email][0];
		$va_return['fname'] = $va_attrs[$vs_attribute_fname][0];
		$va_return['lname'] = $va_attrs[$vs_attribute_lname][0];
		$va_return['user_name'] = $ps_username;
		$va_return['active'] = $po_auth_config->get("ldap_users_auto_active");

		$va_return['roles'] = array_merge($va_default_roles, self::getRolesToAddFromDirectory($ps_username, $vo_ldap));
		$va_return['groups'] = array_merge($va_default_groups, self::getGroupsToAddFromDirectory($ps_username, $vo_ldap));

		ldap_unbind($vo_ldap);

		return $va_return;
	}
	# --------------------------------------------------------------------------------
	private static function postProcessLDAPConfigValue($key, $ps_user_group_name, $ps_user_ou, $ps_base_dn) {
		$o_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));

		$result = $o_auth_config->get($key);
		$result = str_replace('{username}', $ps_user_group_name, $result);
		$result = str_replace('{groupname}', $ps_user_group_name, $result);
		$result = str_replace('{user_ou}', $ps_user_ou, $result);
		$result = str_replace('{base_dn}', $ps_base_dn, $result);
		return $result;
	}
	# --------------------------------------------------------------------------------
	private static function isMemberinAtLeastOneGroup($ps_user, $po_ldap){
		$o_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));
		$vs_base_dn = $o_auth_config->get("ldap_base_dn");

		$vs_group_search_dn = self::postProcessLDAPConfigValue("ldap_group_search_dn_format", '', '', $vs_base_dn);
		$va_group_cns = $o_auth_config->get('ldap_group_cn_list');

		if(is_array($va_group_cns) && sizeof($va_group_cns)>0){
			foreach($va_group_cns as $vs_group_cn) {
				$vs_search_filter = self::postProcessLDAPConfigValue("ldap_group_search_filter_format", $vs_group_cn, '', $vs_base_dn);
				$vo_result = @ldap_search($po_ldap, $vs_group_search_dn, $vs_search_filter, array("memberuid"));

				if (!$vo_result) {
					// search error
					$vs_message = _t("LDAP search error: %1", ldap_error($po_ldap));
					ldap_unbind($po_ldap);
					throw new OpenLDAPException($vs_message);
				}

				$va_entries = ldap_get_entries($po_ldap, $vo_result);
				if($va_members = $va_entries[0]["memberuid"]){
					if(in_array($ps_user, $va_members)){ // found group
						return true;
					}
				}
			}
		} else { // if no list is configured, all is good
			return true;
		}

		return false;
	}
	# --------------------------------------------------------------------------------
	private static function getRolesToAddFromDirectory($ps_user, $po_ldap) {
		$va_return = array();

		$o_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));
		$vs_base_dn = $o_auth_config->get("ldap_base_dn");

		$vs_group_search_dn = self::postProcessLDAPConfigValue("ldap_group_search_dn_format", '', '', $vs_base_dn);
		$va_roles_map = $o_auth_config->get('ldap_roles_group_map');

		if(is_array($va_roles_map) && sizeof($va_roles_map)>0) {
			foreach ($va_roles_map as $vs_ldap_group => $va_ca_roles) {
				if(is_array($va_ca_roles) && sizeof($va_ca_roles)>0) {

					$vs_search_filter = self::postProcessLDAPConfigValue("ldap_group_search_filter_format", $vs_ldap_group, '', $vs_base_dn);
					$vo_result = @ldap_search($po_ldap, $vs_group_search_dn, $vs_search_filter, array("memberuid"));

					if (!$vo_result) {
						// search error
						$vs_message = _t("LDAP search error: %1", ldap_error($po_ldap));
						ldap_unbind($po_ldap);
						throw new OpenLDAPException($vs_message);
					}

					$va_entries = ldap_get_entries($po_ldap, $vo_result);
					if($va_members = $va_entries[0]["memberuid"]){
						if(in_array($ps_user, $va_members)){ // found group
							$va_return = array_merge($va_return, $va_ca_roles);
						}
					}
				}
			}
		}

		return $va_return;
	}
	# --------------------------------------------------------------------------------
	private static function getGroupsToAddFromDirectory($ps_user, $po_ldap) {
		$va_return = array();

		$o_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));
		$vs_base_dn = $o_auth_config->get("ldap_base_dn");

		$vs_group_search_dn = self::postProcessLDAPConfigValue("ldap_group_search_dn_format", '', '', $vs_base_dn);
		$va_groups_map = $o_auth_config->get('ldap_groups_group_map');

		if(is_array($va_groups_map) && sizeof($va_groups_map)>0) {
			foreach ($va_groups_map as $vs_ldap_group => $va_ca_groups) {
				if(is_array($va_ca_groups) && sizeof($va_ca_groups)>0) {

					$vs_search_filter = self::postProcessLDAPConfigValue("ldap_group_search_filter_format", $vs_ldap_group, '', $vs_base_dn);
					$vo_result = @ldap_search($po_ldap, $vs_group_search_dn, $vs_search_filter, array("memberuid"));

					if (!$vo_result) {
						// search error
						$vs_message = _t("LDAP search error: %1", ldap_error($po_ldap));
						ldap_unbind($po_ldap);
						throw new OpenLDAPException($vs_message);
					}

					$va_entries = ldap_get_entries($po_ldap, $vo_result);
					if($va_members = $va_entries[0]["memberuid"]){
						if(in_array($ps_user, $va_members)){ // found group
							$va_return = array_merge($va_return, $va_ca_groups);
						}
					}
				}
			}
		}

		return $va_return;
	}
	# --------------------------------------------------------------------------------
	public static function supports($pn_feature) {
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
	public static function deleteUser($ps_username) {
		// do something?
		return true;
	}
	# --------------------------------------------------------------------------------
	public static function getAccountManagementLink() {
		$po_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));

		if($vs_link = $po_auth_config->get('ldap_manage_account_url')) {
			return $vs_link;
		}

		return false;
	}
	# --------------------------------------------------------------------------------
}

class OpenLDAPException extends Exception {}