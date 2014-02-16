<?php
/** ---------------------------------------------------------------------
 * app/helpers/accessHelpers.php : utility functions for checking user access
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2012 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */ 
 
  /**
   *
   */
   
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 
	 # --------------------------------------------------------------------------------------------
	 /**
	  * Return list of values to validate object/entity/place/etc 'access' field against
	  * when considering whether to display the item or not. This method is intended to be used
	  * in web front-ends such as Pawtucket, not Providence, and so it does not currently consider the
	  * user's login status or login-based roles. Rather, it only considers whether access settings checks 
	  * are enabled (via the 'dont_enforce_access_settings' configuration directive) and whether the user
	  * is considered privileged.
	  *
	  * @param RequestHTTP $po_request The current request
	  * @param array $pa_options Optional options. If omitted settings are taken application configuration file is used. Any array passed to this function should include the following keys: "dont_enforce_access_settings", "public_access_settings", "privileged_access_settings", "privileged_networks"
	  * @return array An array of integer values that, if present in a record, indicate that the record should be displayed to the current user
	  */
	function caGetUserAccessValues($po_request, $pa_options=null) {
		$vb_dont_enforce_access_settings = isset($pa_options['dont_enforce_access_settings']) ? (bool)$pa_options['dont_enforce_access_settings'] : $po_request->config->get('dont_enforce_access_settings');
		$va_privileged_access_settings = isset($pa_options['privileged_access_settings']) && is_array($pa_options['privileged_access_settings']) ? (bool)$pa_options['privileged_access_settings'] : (array)$po_request->config->getList('privileged_access_settings');
		$va_public_access_settings = isset($pa_options['public_access_settings']) && is_array($pa_options['public_access_settings']) ? $pa_options['public_access_settings'] : (array)$po_request->config->getList('public_access_settings');
	
		if (!$vb_dont_enforce_access_settings) {
			$vb_is_privileged = caUserIsPrivileged($po_request, $pa_options);
			if($vb_is_privileged) {
				return $va_privileged_access_settings;
			} else {
				return $va_public_access_settings;
			}
		}
		return array();
	}
	 # --------------------------------------------------------------------------------------------
	 /**
	  * Checks if current user is privileged. Currently only checks if IP address of user is on
	  * a privileged network, as defined by the 'privileged_networks' configuration directive. May 
	  * be expanded in the future to consider user's access rights and/or other parameters.
	  *
	  * @param RequestHTTP $po_request The current request
	  * @param array $pa_options Optional options. If omitted settings are taken application configuration file is used. Any array passed to this function should include "privileged_networks" as a key with a value listing all privileged networks
	  * @return boolean True if user is privileged, false if not
	  */
	function caUserIsPrivileged($po_request, $pa_options=null) {
		$va_privileged_networks = isset($pa_options['privileged_networks']) && is_array($pa_options['privileged_networks']) ? $pa_options['privileged_networks'] : (array)$po_request->config->getList('privileged_networks');
		
		if (!($va_priv_ips = $va_privileged_networks)) {
			$va_priv_ips = array();
		}
		
		$va_user_ip = explode('.', $po_request->getClientIP());
		
		if (is_array($va_priv_ips)) {
			foreach($va_priv_ips as $vs_priv_ip) {
				$va_priv_ip = explode('.', $vs_priv_ip);
				
				$vb_is_match = true;
				for($vn_i=0; $vn_i < sizeof($va_priv_ip); $vn_i++) {
					if (($va_priv_ip[$vn_i] != '*') && ($va_priv_ip[$vn_i] != $va_user_ip[$vn_i])) {
						continue(2);
					}
				}
				return true;
			}
		}
		return false;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Global containing cached type restriction values for the current user and request
	 */
	$g_access_helpers_type_restriction_cache = array();
	
	/**
	 * Return list of types to restrict activity by for given table
	 *
	 * @param mixed $pm_table_name_or_num Table name of number to fetch types for
	 * @param array $pa_options Array of options:
	 *		access = minimum access level user must have to a type for it to be returned. Values are:
	 *			__CA_BUNDLE_ACCESS_NONE__ (0)
	 *			__CA_BUNDLE_ACCESS_READONLY__ (1)
	 *			__CA_BUNDLE_ACCESS_EDIT__ (2)
	 *			If not specified types are returned for which the user has at least __CA_BUNDLE_ACCESS_READONLY__
	 *
	 * @return array List of numeric type_ids for which the user has access, or null if there are no restrictions at all
	 */
	function caGetTypeRestrictionsForUser($pm_table_name_or_num, $pa_options=null) {
		global $g_access_helpers_type_restriction_cache;
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vs_cache_key = md5($pm_table_name_or_num."/".print_r($pa_options, true));
		if (isset($g_access_helpers_type_restriction_cache[$vs_cache_key])) { return $g_access_helpers_type_restriction_cache[$vs_cache_key]; }
		$o_dm = Datamodel::load();
		$o_config = Configuration::load();
		
		$vn_min_access = isset($pa_options['access']) ? (int)$pa_options['access'] : __CA_BUNDLE_ACCESS_READONLY__;
		
		if (is_numeric($pm_table_name_or_num)) {
			$vs_table_name = $o_dm->getTableName($pm_table_name_or_num);
		} else {
			$vs_table_name = $pm_table_name_or_num;
		}
		$t_instance = $o_dm->getInstanceByTableName($vs_table_name, true);
		if (!$t_instance) { return null; }	// bad table
		
		// get types user has at least read-only access to
		global $g_request;
		$va_type_ids = null;
		if ((bool)$t_instance->getAppConfig()->get('perform_type_access_checking') && $g_request && $g_request->isLoggedIn()) {
			if (is_array($va_type_ids = $g_request->user->getTypesWithAccess($t_instance->tableName(), $vn_min_access))) {
				$va_type_ids = caMakeTypeIDList($pm_table_name_or_num, $va_type_ids, array_merge($pa_options, array('dont_include_subtypes_in_type_restriction' => true)));
			}
		} 
		// get types from config file
		if ($va_config_types = $t_instance->getAppConfig()->getList($vs_table_name.'_restrict_to_types')) {
			if ((bool)$o_config->get($vs_table_name.'_restrict_to_types_dont_include_subtypes')) {
				$pa_options['dont_include_subtypes_in_type_restriction'] = true;
			}
			$va_config_type_ids = caMakeTypeIDList($pm_table_name_or_num, $va_config_types, $pa_options);
			
			if (is_array($va_config_type_ids)) {
				if (is_array($va_type_ids) && sizeof($va_type_ids)) {
					$va_type_ids = array_intersect($va_type_ids, $va_config_type_ids);
				} else {
					$va_type_ids = $va_config_type_ids;
				}
			}
		}
		
		return $g_access_helpers_type_restriction_cache[$vs_cache_key] = $g_access_helpers_type_restriction_cache[$vs_cache_key]= $va_type_ids;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Converts the given list of type names or type_ids into an expanded list of numeric type_ids suitable for enforcing type restrictions. Processing
	 * includes expansion of types to include subtypes and conversion of any type codes to type_ids.
	 *
	 * @param mixed $pm_table_name_or_num Table name or number to which types apply
	 * @param array $pa_types List of type codes and/or type_ids that are the basis of the list
	 * @param array $pa_options Array of options:
	 * 		dont_include_subtypes_in_type_restriction = if set, returned list is not expanded to include subtypes
	 *		dontIncludeSubtypesInTypeRestriction = synonym for dont_include_subtypes_in_type_restriction
	 *
	 * @return array List of numeric type_ids
	 */
	function caMakeTypeIDList($pm_table_name_or_num, $pa_types, $pa_options=null) {
		$o_dm = Datamodel::load();
		if(isset($pa_options['dontIncludeSubtypesInTypeRestriction']) && (!isset($pa_options['dont_include_subtypes_in_type_restriction']) || !$pa_options['dont_include_subtypes_in_type_restriction'])) { $pa_options['dont_include_subtypes_in_type_restriction'] = $pa_options['dontIncludeSubtypesInTypeRestriction']; }
	 	
		if (isset($pa_options['dont_include_subtypes_in_type_restriction']) && $pa_options['dont_include_subtypes_in_type_restriction']) {
			$pa_options['noChildren'] = true;
		}
		
		if (is_numeric($pm_table_name_or_num)) {
			$vs_table_name = $o_dm->getTableName($pm_table_name_or_num);
		} else {
			$vs_table_name = $pm_table_name_or_num;
		}
		$t_instance = $o_dm->getInstanceByTableName($vs_table_name, true);
		if (!$t_instance) { return null; }	// bad table
		if (!($vs_type_list_code = $t_instance->getTypeListCode())) { return null; }	// table doesn't use types
		
		$va_type_ids = array();
		$t_list = new ca_lists();
		$t_item = new ca_list_items();
		
		$vs_list_code = $t_instance->getTypeListCode();
		foreach($pa_types as $vm_type) {
			if (!$vm_type) { continue; }
			$vn_type_id = null;
			if (is_numeric($vm_type)) { 
				$vn_type_id = (int)$vm_type; 
			} else {
				$vn_type_id = (int)$t_list->getItemIDFromList($vs_type_list_code, $vm_type);
			}
			
			if ($vn_type_id && !(isset($pa_options['noChildren']) || $pa_options['noChildren'])) {
				if ($qr_children = $t_item->getHierarchy($vn_type_id, array())) {
					while($qr_children->nextRow()) {
						$va_type_ids[$qr_children->get('item_id')] = true;
					}
				}
			} else {
				if ($vn_type_id) {
					$va_type_ids[$vn_type_id] = true;
				}
			}
		}
		return array_keys($va_type_ids);
	}
	# ------------------------------------------------------
	/**
	 * Converts the given list of relationship type names or relationship type_ids into an expanded list of numeric type_ids suitable for enforcing relationship type restrictions. Processing
	 * includes expansion of types to include subtypes and conversion of any type codes to type_ids.
	 *
	 * @param mixed $pm_table_name_or_num Table name or number to which types apply
	 * @param array $pa_types List of type codes and/or type_ids that are the basis of the list
	 * @param array $pa_options Array of options:
	 * 		dont_include_subtypes_in_type_restriction = if set, returned list is not expanded to include subtypes
	 *		dontIncludeSubtypesInTypeRestriction = synonym for dont_include_subtypes_in_type_restriction
	 *
	 * @return array List of numeric type_ids
	 */
	function caMakeRelationshipTypeIDList($pm_table_name_or_num, $pa_types, $pa_options=null) {
		$o_dm = Datamodel::load();
		if(isset($pa_options['dontIncludeSubtypesInTypeRestriction']) && (!isset($pa_options['dont_include_subtypes_in_type_restriction']) || !$pa_options['dont_include_subtypes_in_type_restriction'])) { $pa_options['dont_include_subtypes_in_type_restriction'] = $pa_options['dontIncludeSubtypesInTypeRestriction']; }
	 	
		$pa_options['includeChildren'] = (isset($pa_options['dont_include_subtypes_in_type_restriction']) && $pa_options['dont_include_subtypes_in_type_restriction']) ? false : true;
		
		$t_rel_type = new ca_relationship_types();
		return $t_rel_type->relationshipTypeListToIDs($pm_table_name_or_num, $pa_types, $pa_options);
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Merges types specified with any specified "restrict_to_types"/"restrictToTypes" option, user access settings and types configured in app.conf
	 * into a single list of type_ids suitable for enforcing type restrictions.
	 *
	 * @param BaseModel $t_instance A model instance for the table to which the types apply
	 * @param array $pa_options An array of options containing, if specified, a list of types for either the "restrict_to_types" or "restrictToTypes" keys
	 * 
	 * @return array List of numeric type_ids for which the user has access
	 */
	function caMergeTypeRestrictionLists($t_instance, $pa_options) {
		$va_restrict_to_type_ids = null;
		if (is_array($pa_options['restrict_to_types']) && sizeof($pa_options['restrict_to_types'])) {
			$pa_options['restrictToTypes'] = $pa_options['restrict_to_types'];
		}
		if (is_array($pa_options['restrictToTypes']) && sizeof($pa_options['restrictToTypes'])) {
			$va_restrict_to_type_ids = caMakeTypeIDList($t_instance->tableName(), $pa_options['restrictToTypes'], array('noChildren' => true));
		}
		
		$va_types = null;
		
		$o_config = Configuration::load();
		if ((bool)$o_config->get('perform_type_access_checking') && method_exists($t_instance, 'getTypeFieldName') && ($vs_type_field_name = $t_instance->getTypeFieldName())) {
			$va_types = caGetTypeRestrictionsForUser($t_instance->tableName());
		}
		if (is_array($va_types) && sizeof($va_types) && is_array($va_restrict_to_type_ids) && sizeof($va_restrict_to_type_ids)) {
			if (sizeof($va_tmp = array_intersect($va_restrict_to_type_ids, $va_types))) {
				$va_types = $va_tmp;
			}
		} else {
			if (!is_array($va_types) || !sizeof($va_types)) {
				$va_types = $va_restrict_to_type_ids;
			}
		}
		
		return $va_types;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Returns allowed access for the currently logged in user to the specified bundle
	 *
	 * @param string $ps_table_name Table name of bundle (Eg. ca_objects, ca_entities, ca_places)
	 * @param string $ps_bundle_name Name of bundle (Eg. preferred_labels, date)
	 * @return int Numeric constant representing access. Values are:
	 *		__CA_BUNDLE_ACCESS_NONE__ (0)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (1)
	 *		__CA_BUNDLE_ACCESS_EDIT__ (2)
	 */
	function caGetBundleAccessLevel($ps_table_name, $ps_bundle_name) {
		list($ps_table_name, $ps_bundle_name) = caTranslateBundlesForAccessChecking($ps_table_name, $ps_bundle_name);
		global $g_request;
		if ($g_request) {
			return $g_request->user->getBundleAccessLevel($ps_table_name, $ps_bundle_name);
		}
		
		$o_config = Configuration::load();
		return (int)$o_config->get('default_bundle_access_level');
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Returns allowed access for the currently logged in user to the specified type
	 *
	 * @param string $ps_table_name Table name of bundle (Eg. ca_objects, ca_entities, ca_places)
	 * @param string $pm_type_code_or_id Code or type_id for type to check
	 * @return int Numeric constant representing access. Values are:
	 *		__CA_BUNDLE_ACCESS_NONE__ (0)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (1)
	 *		__CA_BUNDLE_ACCESS_EDIT__ (2)
	 */
	function caGetTypeAccessLevel($ps_table_name, $pm_type_code_or_id) {
		global $g_request;
		if ($g_request) {
			return $g_request->user->getTypeAccessLevel($ps_table_name, $pm_type_code_or_id);
		}
		
		$o_config = Configuration::load();
		return (int)$o_config->get('default_type_access_level');
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Determines if the specified item (and optionally a specific bundle in that item) are readable by the user
	 *
	 * @param int $pn_user_id
	 * @param mixed $pm_table A table name or number
	 * @param int $pn_id The primary key value of the row
	 * @param string $ps_bundle_name An optional bundle to check access for
	 *
	 * @return True if user has read access, otherwise false if the user does not have access or null if one or more parameters are invalid
	 */
	function caCanRead($pn_user_id, $pm_table, $pn_id, $ps_bundle_name=null) {
		$o_dm = Datamodel::load();
		$ps_table_name = (is_numeric($pm_table)) ? $o_dm->getTableName($pm_table) : $pm_table;
		
		if (!($t_instance = $o_dm->getInstanceByTableName($ps_table_name, true))) { return null; }
		if (!$t_instance->load($pn_id)) { return null; }
		
		$t_user = new ca_users($pn_user_id);
		if (!$t_user->getPrimaryKey()) { return null; }
		
		list($ps_table_name, $ps_bundle_name) = caTranslateBundlesForAccessChecking($ps_table_name, $ps_bundle_name);
		
		// Check type restrictions
 		if ((bool)$t_instance->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $t_user->getTypeAccessLevel($ps_table_name, $t_instance->getTypeID());
			if ($vn_type_access < __CA_BUNDLE_ACCESS_READONLY__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if ((bool)$t_instance->getAppConfig()->get('perform_item_level_access_checking')) {
			$vn_item_access = $t_instance->checkACLAccessForUser($t_user);
			if ($vn_item_access < __CA_ACL_READONLY_ACCESS__) {
				return false;
			}
		}
		
		if ($ps_bundle_name) {
			if ($t_user->getBundleAccessLevel($ps_table_name, $ps_bundle_name) < __CA_BUNDLE_ACCESS_READONLY__) { return false; }
		}
		
		return true;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Transforms various alternative bundle expressions supported by get() into canonical bundle names suitable for determining users' access rights.
	 *
	 * @param string $ps_table_name Name of table
	 * @param string $ps_bundle_name Name of bundle
	 * @return array Array with first element set to transformed table name and the second to transformed bundle name
	 */
	function caTranslateBundlesForAccessChecking($ps_table_name, $ps_bundle_name) {
		
		$va_tmp = explode(".", $ps_bundle_name);
		if (in_array($va_tmp[1], array('hierarchy', 'parent', 'children'))) {
			unset($va_tmp[1]); 
		}
		
		$o_dm = Datamodel::load();
		if (!($t_instance = $o_dm->getInstanceByTableName($ps_table_name, true))) { return array($ps_table_name, $ps_bundle_name); }
		
			
		// Translate primary label references
		if (method_exists($t_instance, 'getLabelTableName')) {
			if ($t_instance->getLabelTableName() == $va_tmp[0]) {
				return array($ps_table_name, 'preferred_labels');
			}
		}
		
		// Translate related label references
		$t_rel = $o_dm->getInstanceByTableName($va_tmp[0], true);
		if ($t_rel) {
			if (method_exists($t_rel, 'getSubjectTableName')) {
				return array($t_rel->getSubjectTableName(), 'preferred_labels');
			}
		}
		
		// Related tables
		if($t_rel && (is_array($va_path = $o_dm->getPath($va_tmp[0], $ps_table_name))) && (sizeof($va_path) == 3)) {
			return array($ps_table_name, $va_tmp[0]);
		}
		
		// Translate subfields
		if (sizeof($va_tmp = explode('.', $ps_bundle_name)) > 2) {
			return array($va_tmp[0], $va_tmp[1]);
		}
		return array($ps_table_name, $ps_bundle_name); 
	}
	# ---------------------------------------------------------------------------------------------
 ?>