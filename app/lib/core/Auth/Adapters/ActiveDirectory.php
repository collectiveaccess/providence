<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Auth/Adapters/ActiveDirectoryAdapter.php : Microsoft AD authentication backend
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

require_once(__CA_LIB_DIR__.'/core/Auth/AbstractLDAPAuthAdapter.php');

class ActiveDirectoryAuthAdapter extends AbstractLDAPAuthAdapter {
	protected function getLDAPOptions() {
		return array(
			LDAP_OPT_PROTOCOL_VERSION => 3,
			LDAP_OPT_REFERRALS => 0
		);
	}
	# --------------------------------------------------------------------------------
	protected function isUserInAnyGroup($ps_username, $pa_group_cn_list){
		$vs_base_dn = $this->getConfigValue("ldap_base_dn");
		$vs_user_ou = $this->getConfigValue("ldap_user_ou");
		$vs_user_search_dn = $this->getProcessedConfigValue("ldap_user_search_dn_format", $ps_username, $vs_user_ou, $vs_base_dn);
		$vs_user_search_filter = $this->getProcessedConfigValue("ldap_user_search_filter_format", $ps_username, $vs_user_ou, $vs_base_dn);

		$vo_results = ldap_search($this->getLinkIdentifier(), $vs_user_search_dn, $vs_user_search_filter);
		if (!$vo_results) {
			// search error
			return false;
		}

		$vo_entry = ldap_first_entry($this->getLinkIdentifier(), $vo_results);
		if (!$vo_entry) {
			// no results returned
			return false;
		}

		$va_attrs = ldap_get_attributes($this->getLinkIdentifier(), $vo_entry);
		$vs_member_of_attr = $this->getConfigValue("ldap_attribute_member_of");
		return sizeof(array_intersect(array_map('strtolower', $pa_group_cn_list), array_map('strtolower', $va_attrs[$vs_member_of_attr]))) > 0;
	}
	# --------------------------------------------------------------------------------
	protected function getRolesToAddFromDirectory($ps_username) {
		$va_return = array();
		$va_roles_map = $this->getConfigValue('ldap_roles_group_map');
		if (is_array($va_roles_map) && sizeof($va_roles_map) > 0) {
			foreach ($va_roles_map as $vs_ldap_group => $va_ca_roles) {
				if (is_array($va_ca_roles) && sizeof($va_ca_roles) > 0) {
					if ($this->isUserInAnyGroup($ps_username, array( $vs_ldap_group ))) {
						$va_return = array_merge($va_return, $va_ca_roles);
					}
				}
			}
		}
		return $va_return;
	}
	# --------------------------------------------------------------------------------
	protected function getGroupsToAddFromDirectory($ps_username) {
		$va_return = array();
		$va_groups_map = $this->getConfigValue('ldap_groups_group_map');
		if (is_array($va_groups_map) && sizeof($va_groups_map) > 0) {
			foreach ($va_groups_map as $vs_ldap_group => $va_ca_groups) {
				if (is_array($va_ca_groups) && sizeof($va_ca_groups) > 0) {
					if ($this->isUserInAnyGroup($ps_username, array( $vs_ldap_group ))) {
						$va_return = array_merge($va_return, $va_ca_groups);
					}
				}
			}
		}
		return $va_return;
	}
}
