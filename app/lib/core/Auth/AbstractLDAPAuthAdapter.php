<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/AbstractLDAPAuthAdapter.php : Abstract base class for LDAP adapters
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

abstract class AbstractLDAPAuthAdapter extends BaseAuthAdapter {
    private $opo_auth_config;
    private $opo_ldap;
    # --------------------------------------------------------------------------------
    public function __construct() {
        if (!function_exists("ldap_connect")){
            throw new LDAPException(_t("PHP's LDAP module is required for LDAP authentication!"));
        }

        $this->opo_auth_config = Configuration::load(Configuration::load()->get('authentication_config'));
        $this->opo_ldap = ldap_connect($this->getConfigValue("ldap_host"), $this->getConfigValue("ldap_port"));

        if (!$this->opo_ldap) {
            throw new LDAPException(_t("Could not connect to LDAP server."));
        }

        foreach ($this->getLDAPOptions() as $key => $value) {
            ldap_set_option($this->opo_ldap, $key, $value);
        }
    }
    # --------------------------------------------------------------------------------
    public function __destruct() {
        if ($this->opo_ldap) {
            ldap_close($this->opo_ldap);
        }
    }
    # --------------------------------------------------------------------------------
    public function authenticate($ps_username, $ps_password = '', $pa_options=null) {
        $vo_bind = $this->bindToDirectory($ps_username, $ps_password);
        if (!$vo_bind) {
            if (ldap_get_option($this->getLinkIdentifier(), 0x0032, $extended_error)) {
                $vs_bind_rdn = $this->getProcessedConfigValue("ldap_bind_rdn_format", $ps_username, "", "");
                caLogEvent("ERR", "LDAP ERROR (".ldap_errno($this->getLinkIdentifier()).") {$extended_error} [{$vs_bind_rdn}]", "OpenLDAP::Authenticate");
            }
            return false;
        }

        // check group membership
        if (!$this->hasRequiredGroupMembership($ps_username)) {
            return false;
        }

        // user role and group membership syncing with directory
        $this->syncWithDirectory($ps_username);

        return true;
    }
    # --------------------------------------------------------------------------------
    public function getUserInfo($ps_username, $ps_password) {
        // ldap config
        $vs_base_dn = $this->getConfigValue("ldap_base_dn");
        $vs_user_ou = $this->getConfigValue("ldap_user_ou");
        $vs_search_dn = $this->getProcessedConfigValue("ldap_user_search_dn_format", $ps_username, $vs_user_ou, $vs_base_dn);
        $vs_search_filter = $this->getProcessedConfigValue("ldap_user_search_filter_format", $ps_username, $vs_user_ou, $vs_base_dn);

        $vo_bind = $this->bindToDirectory($ps_username, $ps_password);
        if (!$vo_bind) {
            // wrong credentials
            throw new LDAPException(_t("User could not be authenticated with LDAP server."));
        }

        // check group membership
        if (!$this->hasRequiredGroupMembership($ps_username)) {
            throw new LDAPException(_t("User is not member of at least one of the required groups."));
        }

        /* query directory service for additional info on user */
        $vo_results = @ldap_search($this->getLinkIdentifier(), $vs_search_dn, $vs_search_filter);
        if (!$vo_results) {
            // search error
            $vs_message = _t("LDAP search error: %1", ldap_error($this->getLinkIdentifier()));
            throw new LDAPException($vs_message);
        }

        $vo_entry = ldap_first_entry($this->getLinkIdentifier(), $vo_results);
        if (!$vo_entry) {
            // no results returned
            throw new LDAPException(_t("User could not be found."));
        }

        $va_attrs = ldap_get_attributes($this->getLinkIdentifier(), $vo_entry);

        return array(
            'user_name' => $ps_username,
            'email' => $va_attrs[$this->getConfigValue("ldap_attribute_email")][0],
            'fname' => $va_attrs[$this->getConfigValue("ldap_attribute_fname")][0],
            'lname' => $va_attrs[$this->getConfigValue("ldap_attribute_lname")][0],
            'active' => $this->getConfigValue("ldap_users_auto_active"),
            'roles' => array_merge($this->getConfigValue("ldap_users_default_roles", array()), $this->getRolesToAddFromDirectory($ps_username)),
            'groups' => array_merge($this->getConfigValue("ldap_users_default_groups", array()), $this->getGroupsToAddFromDirectory($ps_username))
        );
    }
    # --------------------------------------------------------------------------------
    public function createUserAndGetPassword($ps_username, $ps_password) {
        // We don't create users in directories, we assume they're already there

        // TODO FIXME The following is insecure!
        // We will create a password hash that is compatible with the CaUsers authentication adapter though
        // That way users could, in theory, turn off LDAP authentication later. The hash will not be used
        // for authentication in this adapter though.
        return create_hash($ps_password);
    }
    # --------------------------------------------------------------------------------
    protected function getLinkIdentifier() {
        return $this->opo_ldap;
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
    protected function getProcessedConfigValue($ps_key, $ps_user_group_name, $ps_user_ou, $ps_base_dn) {
        $result = $this->getConfigValue($ps_key);
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
        // do something?
        return true;
    }
    # --------------------------------------------------------------------------------
    public function getAccountManagementLink() {
        if($vs_link = $this->getConfigValue('ldap_manage_account_url')) {
            return $vs_link;
        }
        return false;
    }
    # --------------------------------------------------------------------------------
    /**
     * Determine if the user has at least one required group membership.  If no required group list is configured, this
     * method should always return `true`.  By default, this method always returns `true`.
     *
     * @param $ps_username string The username
     *
     * @return boolean
     */
    protected function hasRequiredGroupMembership($ps_username){
        $va_group_cn_list = $this->getConfigValue("ldap_group_cn_list");
        if (!is_array($va_group_cn_list) || sizeof($va_group_cn_list) === 0) {
            // if no list is configured, all is good
            return true;
        }
        return $this->isUserInAnyGroup($ps_username, $va_group_cn_list);
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
     * @param $ps_username string
     * @param $pa_group_cn_list array[string]
     *
     * @return bool True if the user is in the group, otherwise false.
     */
    protected abstract function isUserInAnyGroup($ps_username, $pa_group_cn_list);
    # --------------------------------------------------------------------------------
    /**
     * Get an array of CA role names to add to the given user after logging in, based on security group assignments in
     * the directory.
     *
     * @param $ps_username string
     *
     * @return array
     */
    protected abstract function getRolesToAddFromDirectory($ps_username);
    # --------------------------------------------------------------------------------
    /**
     * Get an array of CA group names to add to the given user after logging in, based on security group assignments
     * in the directory.
     *
     * @param $ps_username string
     *
     * @return array
     */
    protected abstract function getGroupsToAddFromDirectory($ps_username);
    # --------------------------------------------------------------------------------
    private function bindToDirectory($ps_username, $ps_password) {
        if (!$ps_username) {
            return false;
        }

		if(strlen($ps_password) == 0) {
			throw new LDAPException(_t("Password for directory bind cannot be empty!"));
		}

        // ldap config
        $vs_user_ou = $this->getConfigValue("ldap_user_ou");
        $vs_base_dn = $this->getConfigValue("ldap_base_dn");
        $vs_bind_rdn = $this->getProcessedConfigValue("ldap_bind_rdn_format", $ps_username, $vs_user_ou, $vs_base_dn);
        $vs_bind_rdn_filter = $this->getProcessedConfigValue("ldap_bind_rdn_filter", $ps_username, $vs_user_ou, $vs_base_dn);

        // apply filter to bind, if there is one
        if (strlen($vs_bind_rdn_filter) > 0) {
            $vo_dn_search_results = ldap_search($this->getLinkIdentifier(), $vs_base_dn, $vs_bind_rdn_filter);
            $va_dn_search_results = ldap_get_entries($this->getLinkIdentifier(), $vo_dn_search_results);
            if (isset($va_dn_search_results[0]['dn'])) {
                $vs_bind_rdn = $va_dn_search_results[0]['dn'];
            }
        }

        // log in
        return @ldap_bind($this->getLinkIdentifier(), $vs_bind_rdn, $ps_password);
    }
    # --------------------------------------------------------------------------------
    private function syncWithDirectory($ps_username) {
        $va_default_roles = $this->getConfigValue("ldap_users_default_roles", array());
        $va_default_groups = $this->getConfigValue("ldap_users_default_groups", array());
        $t_user = new ca_users();

        // don't try to sync roles for non-existing users (the first auth call is before the user is actually created)
        if (!$t_user->load($ps_username)) {
            return;
        }

        if ($this->getConfigValue('ldap_sync_user_roles')) {
            $va_expected_roles = array_merge($va_default_roles, $this->getRolesToAddFromDirectory($ps_username));

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

        if ($this->getConfigValue('ldap_sync_user_groups')) {
            $va_expected_groups = array_merge($va_default_groups, $this->getGroupsToAddFromDirectory($ps_username));

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
}

class LDAPException extends Exception {}
