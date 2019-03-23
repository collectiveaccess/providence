<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/AbstractLDAPAuthAdapter.php : Abstract base class for LDAP adapters
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

require_once(__CA_LIB_DIR__.'/Auth/BaseAuthAdapter.php');
require_once(__CA_LIB_DIR__.'/Auth/PasswordHash.php');

abstract class AbstractLDAPAuthAdapter extends BaseAuthAdapter {
	/**
	 * @var array
	 */
    private $opa_auth_config_fragments = [];
	/**
	 * @var array
	 */
    private $opa_ldaps = [];
    # --------------------------------------------------------------------------------
    public function __construct() {
        if (!function_exists("ldap_connect")){
            throw new LDAPException(_t("PHP's LDAP module is required for LDAP authentication!"));
        }

        $this->opo_auth_config = Configuration::load(__CA_APP_DIR__."/conf/authentication.conf");

		// "new" config format allows defining multiple directories in an array
		if($va_directories = $this->opo_auth_config->get('directories')) {

			foreach($va_directories as $vs_dir_key => $va_dir) {
				$o_ldap = ldap_connect($va_dir["ldap_host"], $va_dir["ldap_port"]);
				if(!$o_ldap) {
					continue; // try next server
					//throw new LDAPException(_t("Could not connect to LDAP server '%1'.", $vs_dir_key));
				}

				foreach ($this->getLDAPOptions() as $key => $value) {
					ldap_set_option($o_ldap, $key, $value);
				}

				$this->opa_ldaps[$vs_dir_key] = $o_ldap;
				$this->opa_auth_config_fragments[$vs_dir_key] = $va_dir;
			}
		} else {// else @todo maybe support legacy configs?
			throw new LDAPException(_t('No directories key found in authentication config. Are you using an old configuration?'));
		}
    }
	# --------------------------------------------------------------------------------
    public function __destruct() {
        if (sizeof($this->opa_ldaps)) {
			foreach($this->opa_ldaps as $o_ldap) {
				ldap_close($o_ldap);
			}
        }
    }
    # --------------------------------------------------------------------------------
    public function authenticate($ps_username, $ps_password = '', $pa_options=null) {
		// try to bind against one of the directories
		foreach($this->getLinkIdentifiers() as $vs_key => $r_ldap) {
			$vo_bind = $this->bindToDirectory($r_ldap, $ps_username, $ps_password, $this->opa_auth_config_fragments[$vs_key]);
			if (!$vo_bind) {
				if (ldap_get_option($r_ldap, 0x0032, $extended_error)) {
					$vs_bind_rdn = $this->getProcessedConfigValue(
						$this->opa_auth_config_fragments[$vs_key],
						"ldap_bind_rdn_format",
						$ps_username, "", ""
					);
					caLogEvent("ERR", "LDAP ERROR (".ldap_errno($r_ldap).") {$extended_error} [{$vs_bind_rdn}]", "OpenLDAP::Authenticate");
				}
				continue; // try next one
			}

			// check group membership
			if (!$this->hasRequiredGroupMembership($r_ldap, $ps_username, $this->opa_auth_config_fragments[$vs_key])) {
				continue; // try next one
			}

			// user role and group membership syncing with directory
			$this->syncWithDirectory($r_ldap, $ps_username, $this->opa_auth_config_fragments[$vs_key]);

			// auth successful
			return true;
		}

		// couldn't bind to any of the directories -> bail
		return false;
    }
    # --------------------------------------------------------------------------------
    public function getUserInfo($ps_username, $ps_password, $pa_options=null) {
		foreach($this->getLinkIdentifiers() as $vs_key => $r_ldap) {
			// ldap config
			$vs_base_dn = $this->opa_auth_config_fragments[$vs_key]['ldap_base_dn'];
			$vs_user_ou = $this->opa_auth_config_fragments[$vs_key]['ldap_user_ou'];
			$va_default_roles = $this->opa_auth_config_fragments[$vs_key]["ldap_users_default_roles"];
			if(!is_array($va_default_roles)) { $va_default_roles = []; }
			$va_default_groups = $this->opa_auth_config_fragments[$vs_key]["ldap_users_default_groups"];
			if(!is_array($va_default_groups)) { $va_default_groups = []; }
			$vs_search_dn = $this->getProcessedConfigValue(
				$this->opa_auth_config_fragments[$vs_key], "ldap_user_search_dn_format",
				$ps_username, $vs_user_ou, $vs_base_dn
			);
			$vs_search_filter = $this->getProcessedConfigValue(
				$this->opa_auth_config_fragments[$vs_key], "ldap_user_search_filter_format",
				$ps_username, $vs_user_ou, $vs_base_dn
			);

			$vo_bind = $this->bindToDirectory($r_ldap, $ps_username, $ps_password, $this->opa_auth_config_fragments[$vs_key]);
			if (!$vo_bind) {
				// wrong credentials
				continue;
			}

			// check group membership
			if (!$this->hasRequiredGroupMembership($r_ldap, $ps_username, $this->opa_auth_config_fragments[$vs_key])) {
				continue;
			}

			/* query directory service for additional info on user */
			$vo_results = ldap_search($r_ldap, $vs_search_dn, $vs_search_filter);
			if (!$vo_results) {
				// search error
				//$vs_message = _t("LDAP search error: %1", ldap_error($this->getLinkIdentifier()));
				//throw new LDAPException($vs_message);
				continue;
			}

			$vo_entry = ldap_first_entry($r_ldap, $vo_results);
			if (!$vo_entry) {
				// no results returned
				continue;
			}

			$va_attrs = ldap_get_attributes($r_ldap, $vo_entry);

			return array(
				'user_name' => $ps_username,
				'email' => $va_attrs[$this->opa_auth_config_fragments[$vs_key]["ldap_attribute_email"]][0],
				'fname' => $va_attrs[$this->opa_auth_config_fragments[$vs_key]["ldap_attribute_fname"]][0],
				'lname' => $va_attrs[$this->opa_auth_config_fragments[$vs_key]["ldap_attribute_lname"]][0],
				'active' => $this->opa_auth_config_fragments[$vs_key]["ldap_users_auto_active"],
				'roles' => array_merge($va_default_roles, $this->getRolesToAddFromDirectory(
					$r_ldap, $ps_username, $this->opa_auth_config_fragments[$vs_key])
				),
				'groups' => array_merge($va_default_groups, $this->getGroupsToAddFromDirectory(
					$r_ldap, $ps_username, $this->opa_auth_config_fragments[$vs_key])
				)
			);
		}

		throw new LDAPException(_t("User could not be found."));
    }
    # --------------------------------------------------------------------------------
    public function createUserAndGetPassword($ps_username, $ps_password) {
        // We don't create users in directories, we assume they're already there

		// set random 32 byte password. as long as LDAP is enabled, this will never be used
		// and if it's ever disabled, we don't want people to be able to log in with their
		// ldap passwords
		if(function_exists('mcrypt_create_iv')) {
			$vs_password = base64_encode(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		} elseif(function_exists('openssl_random_pseudo_bytes')) {
			$vs_password = base64_encode(openssl_random_pseudo_bytes(32));
		} else {
			throw new Exception('mcrypt or OpenSSL is required for CollectiveAccess to run');
		}

        return $vs_password;
    }
    # --------------------------------------------------------------------------------
    protected function getLinkIdentifiers() {
        return $this->opa_ldaps;
    }
    # --------------------------------------------------------------------------------
    protected function getConfigValue($ps_key, $pm_default_value = null) {
        $vm_result = $this->opo_auth_config->get($ps_key);
        if ($pm_default_value && !$vm_result) {
            $vm_result = $pm_default_value;
        }
        return $vm_result;
    }
    # --------------------------------------------------------------------------------
	/**
	 * @param array $pa_config
	 * @param string $ps_key
	 * @param string $ps_user_group_name
	 * @param string $ps_user_ou
	 * @param string $ps_base_dn
	 * @return mixed
	 */
    protected function getProcessedConfigValue($pa_config, $ps_key, $ps_user_group_name, $ps_user_ou, $ps_base_dn) {
        $result = $pa_config[$ps_key];
        $result = str_replace('{username}', $ps_user_group_name, $result);
        $result = str_replace('{groupname}', $ps_user_group_name, $result);
        $result = str_replace('{user_ou}', $ps_user_ou, $result);
        $result = str_replace('{base_dn}', $ps_base_dn, $result);
        return $result;
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
        return true;
    }
    # --------------------------------------------------------------------------------
    public function getAccountManagementLink() {
        if($vs_link = $this->opo_auth_config->get('manage_account_url')) {
            return $vs_link;
        }
        return false;
    }
    # --------------------------------------------------------------------------------
    /**
     * Determine if the user has at least one required group membership.  If no required group list is configured, this
     * method should always return `true`.  By default, this method always returns `true`.
     *
	 * @param resource $pr_ldap
     * @param $ps_username string The username
	 * @param array $pa_config
     *
     * @return boolean
     */
    protected function hasRequiredGroupMembership($pr_ldap, $ps_username, $pa_config) {
        $va_group_cn_list = $pa_config["ldap_group_cn_list"];
        if (!is_array($va_group_cn_list) || sizeof($va_group_cn_list) === 0) {
            // if no list is configured, all is good
            return true;
        }
        return $this->isUserInAnyGroup($pr_ldap, $ps_username, $va_group_cn_list, $pa_config);
    }
    # --------------------------------------------------------------------------------
    /**
     * Generate a map of keys and values to pass to `ldap_set_option()`.  As a minimum, this should specify the LDAP
     * protocol version to use.  By default it specifies an LDAP protocol version of 3 and no other options.
     *
     * @return array
     */
    protected abstract function getLDAPOptions();
    # --------------------------------------------------------------------------------
    /**
     * Determine whether the given user is in any of the given groups, using the given LDAP connection.
     *
	 * @param $pr_ldap resource
     * @param $ps_username string
     * @param $pa_group_cn_list array[string]
	 * @param array $pa_config
     *
     * @return bool True if the user is in the group, otherwise false.
     */
    protected abstract function isUserInAnyGroup($pr_ldap, $ps_username, $pa_group_cn_list, $pa_config);
    # --------------------------------------------------------------------------------
    /**
     * Get an array of CA role names to add to the given user after logging in, based on security group assignments in
     * the directory.
     *
     * @param $ps_username string
	 * @param resource $pr_ldap
	 * @param array $pa_config
     *
     * @return array
     */
    protected abstract function getRolesToAddFromDirectory($pr_ldap, $ps_username, $pa_config);
    # --------------------------------------------------------------------------------
    /**
     * Get an array of CA group names to add to the given user after logging in, based on security group assignments
     * in the directory.
     *
	 * @param resource $pr_ldap
     * @param $ps_username string
	 * @param array $pa_config
     *
     * @return array
     */
    protected abstract function getGroupsToAddFromDirectory($pr_ldap, $ps_username, $pa_config);
    # --------------------------------------------------------------------------------
	/**
	 * Bind to directory
	 * @param resource $po_ldap
	 * @param string $ps_username
	 * @param string $ps_password
	 * @param array $pa_config
	 * @return bool
	 * @throws LDAPException
	 */
    private function bindToDirectory($po_ldap, $ps_username, $ps_password, $pa_config) {
        if (!$ps_username) {
            return false;
        }

		if(strlen($ps_password) == 0) {
			throw new LDAPException(_t("Password for directory bind cannot be empty!"));
		}

        // ldap config
        $vs_user_ou = $pa_config['ldap_user_ou'];
        $vs_base_dn = $pa_config['ldap_base_dn'];
        $vs_bind_rdn = $this->getProcessedConfigValue($pa_config, "ldap_bind_rdn_format", $ps_username, $vs_user_ou, $vs_base_dn);
        $vs_bind_rdn_filter = $this->getProcessedConfigValue($pa_config, "ldap_bind_rdn_filter", $ps_username, $vs_user_ou, $vs_base_dn);

        // apply filter to bind, if there is one
        if (strlen($vs_bind_rdn_filter) > 0) {
			$this->bindServiceAccount($po_ldap, $pa_config);

            if(!($vo_dn_search_results = @ldap_search($po_ldap, $vs_base_dn, $vs_bind_rdn_filter))) {
            	throw new LDAPException(_t("Couldn't apply bind RDN filter for directory. LDAP search failed"));
			}
            $va_dn_search_results = ldap_get_entries($po_ldap, $vo_dn_search_results);
            if (isset($va_dn_search_results[0]['dn'])) {
                $vs_bind_rdn = $va_dn_search_results[0]['dn'];
            }
        }

        // log in
        return @ldap_bind($po_ldap, $vs_bind_rdn, $ps_password);
    }
    # --------------------------------------------------------------------------------
	/**
	 * @param resource $pr_ldap
	 * @param string $ps_username
	 * @param array $pa_config
	 */
    private function syncWithDirectory($pr_ldap, $ps_username, $pa_config) {
        $va_default_roles = caGetOption("ldap_users_default_roles", $pa_config, array());
        $va_default_groups = caGetOption("ldap_users_default_groups", $pa_config, array());
        $t_user = new ca_users();

        // don't try to sync roles for non-existing users (the first auth call is before the user is actually created)
        if (!$t_user->load($ps_username)) {
            return;
        }

        if ($pa_config['ldap_sync_user_roles']) {
            $va_expected_roles = array_merge($va_default_roles, $this->getRolesToAddFromDirectory($pr_ldap, $ps_username, $pa_config));

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

        if ($pa_config['ldap_sync_user_groups']) {
            $va_expected_groups = array_merge($va_default_groups, $this->getGroupsToAddFromDirectory($pr_ldap, $ps_username, $pa_config));

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
	# --------------------------------------------------------------------------------	
	/**
	 * Bind to service account when server does not support search after anonymous bind
	 */
	protected function bindServiceAccount($po_ldap, $pa_config) {
		if(
			($vs_service_acct_rdn = $pa_config['ldap_service_account_rdn']) &&
			($vs_service_acct_pwd = $pa_config['ldap_service_account_password'])
		) {
			return @ldap_bind($po_ldap, $vs_service_acct_rdn, $vs_service_acct_pwd);
		}

		return false;
	}
	# --------------------------------------------------------------------------------
}

class LDAPException extends Exception {}
