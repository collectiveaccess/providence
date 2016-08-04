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

require_once(__CA_LIB_DIR__.'/core/Auth/AbstractLDAPAuthAdapter.php');

class OpenLDAPAuthAdapter extends AbstractLDAPAuthAdapter {
	protected function getLDAPOptions() {
		return array(
			LDAP_OPT_PROTOCOL_VERSION => 3
		);
	}
	# --------------------------------------------------------------------------------
	protected function isUserInAnyGroup($ps_username, $pa_group_cn_list) {
		$vs_base_dn = $this->getConfigValue("ldap_base_dn");
		$vs_group_search_dn = $this->getProcessedConfigValue("ldap_group_search_dn_format", '', '', $vs_base_dn);

		foreach ($pa_group_cn_list as $vs_group_cn) {
			$vs_search_filter = $this->getProcessedConfigValue("ldap_group_search_filter_format", $vs_group_cn, '', $vs_base_dn);
			$vo_result = @ldap_search($this->getLinkIdentifier(), $vs_group_search_dn, $vs_search_filter, array("memberuid"));

			if (!$vo_result) {
				// search error
				$vs_message = _t("LDAP search error: %1", ldap_error($this->getLinkIdentifier()));
				throw new LDAPException($vs_message);
			}

			$va_entries = ldap_get_entries($this->getLinkIdentifier(), $vo_result);
			if ($va_members = $va_entries[0]["memberuid"]) {
				if (in_array($ps_username, $va_members)) {
					// found group
					return true;
				}
			}
		}
		return false;
	}
	# --------------------------------------------------------------------------------
	protected function getRolesToAddFromDirectory($ps_username) {
		$va_return = array();

		$vs_user_ou = $this->getConfigValue("ldap_user_ou");
		$vs_base_dn = $this->getConfigValue("ldap_base_dn");
		$vs_group_search_dn = $this->getProcessedConfigValue("ldap_group_search_dn_format", $ps_username, $vs_user_ou, $vs_base_dn);
		$va_roles_map = $this->getConfigValue('ldap_roles_group_map');

		if(is_array($va_roles_map) && sizeof($va_roles_map)>0) {
			foreach ($va_roles_map as $vs_ldap_group => $va_ca_roles) {
				if(is_array($va_ca_roles) && sizeof($va_ca_roles)>0) {
					$vs_search_filter = $this->getProcessedConfigValue("ldap_group_search_filter_format", $vs_ldap_group, '', $vs_base_dn);
					$vo_result = @ldap_search($this->getLinkIdentifier(), $vs_group_search_dn, $vs_search_filter, array("memberuid"));
					if (!$vo_result) {
						// search error
						$vs_message = _t("LDAP search error: %1", ldap_error($this->getLinkIdentifier()));
						throw new LDAPException($vs_message);
					}

					$va_entries = ldap_get_entries($this->getLinkIdentifier(), $vo_result);
					if($va_members = $va_entries[0]["memberuid"]) {
						if(in_array($ps_username, $va_members)) { // found group
							$va_return = array_merge($va_return, $va_ca_roles);
						}
					}
				}
			}
		}

		return $va_return;
	}
	# --------------------------------------------------------------------------------
	protected function getGroupsToAddFromDirectory($ps_username) {
		$va_return = array();

		$vs_base_dn = $this->getConfigValue("ldap_base_dn");
		$vs_group_search_dn = $this->getProcessedConfigValue("ldap_group_search_dn_format", '', '', $vs_base_dn);
		$va_groups_map = $this->getConfigValue('ldap_groups_group_map');

		if(is_array($va_groups_map) && sizeof($va_groups_map)>0) {
			foreach ($va_groups_map as $vs_ldap_group => $va_ca_groups) {
				if(is_array($va_ca_groups) && sizeof($va_ca_groups)>0) {
					$vs_search_filter = $this->getProcessedConfigValue("ldap_group_search_filter_format", $vs_ldap_group, '', $vs_base_dn);
					$vo_result = @ldap_search($this->getLinkIdentifier(), $vs_group_search_dn, $vs_search_filter, array("memberuid"));

					if (!$vo_result) {
						// search error
						$vs_message = _t("LDAP search error: %1", ldap_error($this->getLinkIdentifier()));
						throw new LDAPException($vs_message);
					}

					$va_entries = ldap_get_entries($this->getLinkIdentifier(), $vo_result);
					if($va_members = $va_entries[0]["memberuid"]) {
						if(in_array($ps_username, $va_members)) { // found group
							$va_return = array_merge($va_return, $va_ca_groups);
						}
					}
				}
			}
		}

		return $va_return;
	}
}
