<?php
/** ---------------------------------------------------------------------
 * app/helpers/accessHelpers.php : utility functions for checking user access
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2018 Whirl-i-Gig
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
   
 require_once(__CA_LIB_DIR__.'/Configuration.php');
 
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
		if(!caGetOption('ignoreProvidence', $pa_options, false)) {
			if (defined("__CA_APP_TYPE__") && (__CA_APP_TYPE__ == 'PROVIDENCE')) { return null; }
		}
		$vb_dont_enforce_access_settings = isset($pa_options['dont_enforce_access_settings']) ? (bool)$pa_options['dont_enforce_access_settings'] : $po_request->config->get('dont_enforce_access_settings');
		$va_privileged_access_settings = isset($pa_options['privileged_access_settings']) && is_array($pa_options['privileged_access_settings']) ? (bool)$pa_options['privileged_access_settings'] : (array)$po_request->config->getList('privileged_access_settings');
		$va_public_access_settings = isset($pa_options['public_access_settings']) && is_array($pa_options['public_access_settings']) ? $pa_options['public_access_settings'] : (array)$po_request->config->getList('public_access_settings');
	
		if (!$vb_dont_enforce_access_settings) {
			$va_access = array();
			$vb_is_privileged = caUserIsPrivileged($po_request, $pa_options);
			if($vb_is_privileged) {
				$va_access = $va_privileged_access_settings;
			} else {
				$va_access = $va_public_access_settings;
			}
			
			if ($po_request->isLoggedIn()) {
				$va_user_access = $po_request->user->getAccessStatuses(1);
				if(is_array($va_user_access)) {
					$va_access = array_unique(array_merge($va_access, $va_user_access));
				}
			}
			return $va_access;
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
	 *		exactAccess = if set only sources with access equal to the "access" value are returned. Default is false.
	 *
	 * @return array List of numeric type_ids for which the user has access, or null if there are no restrictions at all
	 */
	function caGetTypeRestrictionsForUser($pm_table_name_or_num, $pa_options=null) {
		global $g_request, $g_access_helpers_type_restriction_cache;
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		//if ($g_request && $g_request->isLoggedIn() && ($g_request->user->canDoAction('is_administrator'))) { return null; }
		
		$vs_cache_key = md5($pm_table_name_or_num."/".print_r($pa_options, true));
		if (isset($g_access_helpers_type_restriction_cache[$vs_cache_key])) { return $g_access_helpers_type_restriction_cache[$vs_cache_key]; }
		$o_config = Configuration::load();
		
		$vn_min_access = isset($pa_options['access']) ? (int)$pa_options['access'] : __CA_BUNDLE_ACCESS_READONLY__;
		
		if (is_numeric($pm_table_name_or_num)) {
			$vs_table_name = Datamodel::getTableName($pm_table_name_or_num);
		} else {
			$vs_table_name = $pm_table_name_or_num;
		}
		$t_instance = Datamodel::getInstanceByTableName($vs_table_name, true);
		if (!$t_instance) { return null; }	// bad table
		
		// get types user has at least read-only access to
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
		
		return $g_access_helpers_type_restriction_cache[$vs_cache_key] = $va_type_ids;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Global containing cached source restriction values for the current user and request
	 */
	$g_access_helpers_source_restriction_cache = array();
	
	/**
	 * Return list of sources to restrict activity by for given table
	 *
	 * @param mixed $pm_table_name_or_num Table name of number to fetch sources for
	 * @param array $pa_options Array of options:
	 *		access = minimum access level user must have to a source for it to be returned. Values are:
	 *			__CA_BUNDLE_ACCESS_NONE__ (0)
	 *			__CA_BUNDLE_ACCESS_READONLY__ (1)
	 *			__CA_BUNDLE_ACCESS_EDIT__ (2)
	 *			If not specified sources are returned for which the user has at least __CA_BUNDLE_ACCESS_READONLY__
	 *
	 *		exactAccess = if set only sources with access equal to the "access" value are returned. Default is false.
	 *
	 * @return array List of numeric source_ids for which the user has access, or null if there are no restrictions at all
	 */
	function caGetSourceRestrictionsForUser($pm_table_name_or_num, $pa_options=null) {
		global $g_request, $g_access_helpers_source_restriction_cache;
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		//if ($g_request && $g_request->isLoggedIn() && ($g_request->user->canDoAction('is_administrator'))) { return null; }
		
		$vs_cache_key = md5($pm_table_name_or_num."/".print_r($pa_options, true));
		if (isset($g_access_helpers_source_restriction_cache[$vs_cache_key])) { return $g_access_helpers_source_restriction_cache[$vs_cache_key]; }
		$o_config = Configuration::load();
		
		$vn_min_access = isset($pa_options['access']) ? (int)$pa_options['access'] : __CA_BUNDLE_ACCESS_READONLY__;
		
		if (is_numeric($pm_table_name_or_num)) {
			$vs_table_name = Datamodel::getTableName($pm_table_name_or_num);
		} else {
			$vs_table_name = $pm_table_name_or_num;
		}
		$t_instance = Datamodel::getInstanceByTableName($vs_table_name, true);
		if (!$t_instance) { return null; }	// bad table
		
		// get sources user has at least read-only access to
		$va_source_ids = null;
		if ((bool)$t_instance->getAppConfig()->get('perform_source_access_checking') && $g_request && $g_request->isLoggedIn()) {
			if (is_array($va_source_ids = $g_request->user->getSourcesWithAccess($t_instance->tableName(), $vn_min_access, $pa_options))) {
				$va_source_ids = caMakeSourceIDList($pm_table_name_or_num, $va_source_ids, array_merge($pa_options, array('dont_include_subsources_in_source_restriction' => true)));
			}
		} 
		// get sources from config file
		if ($va_config_sources = $t_instance->getAppConfig()->getList($vs_table_name.'_restrict_to_sources')) {
			if ((bool)$o_config->get($vs_table_name.'_restrict_to_sources_dont_include_subsources')) {
				$pa_options['dont_include_subsources_in_source_restriction'] = true;
			}
			$va_config_source_ids = caMakeSourceIDList($pm_table_name_or_num, $va_config_sources, $pa_options);
			
			if (is_array($va_config_source_ids)) {
				if (is_array($va_source_ids) && sizeof($va_source_ids)) {
					$va_source_ids = array_intersect($va_source_ids, $va_config_source_ids);
				} else {
					$va_source_ids = $va_config_source_ids;
				}
			}
		}
		
		return $g_access_helpers_source_restriction_cache[$vs_cache_key] = $va_source_ids;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Return list of types for which user has access
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
	function caGetTypeListForUser($pm_table_name_or_num, $pa_options=null) {
		if(is_null($va_types = caGetTypeRestrictionsForUser($pm_table_name_or_num, $pa_options))) {
			$t_instance = Datamodel::getInstanceByTableName($pm_table_name_or_num, true);
			if (!$t_instance) { return null; }	// bad table
			$va_types = array_keys($t_instance->getTypeList());
		}
		return $va_types;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Return list of sources for which user has access
	 *
	 * @param mixed $pm_table_name_or_num Table name of number to fetch sources for
	 * @param array $pa_options Array of options:
	 *		access = minimum access level user must have to a source for it to be returned. Values are:
	 *			__CA_BUNDLE_ACCESS_NONE__ (0)
	 *			__CA_BUNDLE_ACCESS_READONLY__ (1)
	 *			__CA_BUNDLE_ACCESS_EDIT__ (2)
	 *			If not specified sources are returned for which the user has at least __CA_BUNDLE_ACCESS_READONLY__
	 *
	 * @return array List of numeric source_ids for which the user has access, or null if there are no restrictions at all
	 */
	function caGetSourceListForUser($pm_table_name_or_num, $pa_options=null) {
		if(is_null($va_sources = caGetSourceRestrictionsForUser($pm_table_name_or_num, $pa_options))) {
			$t_instance = Datamodel::getInstanceByTableName($pm_table_name_or_num, true);
			if (!$t_instance) { return null; }	// bad table
			$va_sources = array_keys($t_instance->getSourceList());
		}
		return $va_sources;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Converts the given list of type codes or type_ids into an expanded list of numeric type_ids suitable for enforcing type restrictions. Processing
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
		if(!is_array($pa_options)) { $pa_options = []; }
		if (!is_array($pa_types)) { $pa_types = []; }
		$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_options, $pa_types), "caMakeTypeIDList:{$pm_table_name_or_num}");
		if (ExternalCache::contains($vs_cache_key, 'listItems')) { return ExternalCache::fetch($vs_cache_key, 'listItems'); }
		if (is_numeric($pm_table_name_or_num)) {
			$vs_table_name = Datamodel::getTableName($pm_table_name_or_num);
		} else {
			$vs_table_name = $pm_table_name_or_num;
		}
		$t_instance = Datamodel::getInstanceByTableName($vs_table_name, true);
		if (!$t_instance) { return null; }	// bad table
		if (!($vs_type_list_code = $t_instance->getTypeListCode())) { return null; }	// table doesn't use types
		
		$va_ret = caMakeItemIDList($vs_type_list_code, $pa_types, $pa_options);
		ExternalCache::save($vs_cache_key, $va_ret, 'listItems');
		return $va_ret;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Converts the given list of item idnos or item_ids into an expanded list of numeric item_ids. Processing
	 * includes expansion of types to include subitems and conversion of any item codes to item_ids. 
	 *
	 * This helper is often used to convert type lists (which are just items in a list) to ids when enforcing 
	 * type restrictions, which is why the options for expanding the list to include sub-items use "subtypes" in their names
	 * (in case you were wondering)
	 *
	 * @param mixed $pm_list_code_or_id List code or list-id
	 * @param array $pa_types List of item idnos and/or item_ids that are the basis of the list
	 * @param array $pa_options Array of options:
	 * 		dont_include_subtypes_in_type_restriction = if set, returned list is not expanded to include sub-items
	 *		dontIncludeSubtypesInTypeRestriction = synonym for dont_include_subtypes_in_type_restriction
	 *
	 * @return array List of numeric item_ids
	 */
	function caMakeItemIDList($pm_list_code_or_id, $pa_item_idnos, $pa_options=null) {
		if (!is_array($pa_item_idnos) && !strlen($pa_item_idnos)) { return []; }
		if (!is_array($pa_item_idnos)) { $pa_item_idnos = [$pa_item_idnos]; }
		if (!is_array($pa_options)) { $pa_options = []; }
		$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_options, $pa_item_idnos), "caMakeItemIDList:{$pm_list_code_or_id}");
		if (ExternalCache::contains($vs_cache_key, 'listItems')) { return ExternalCache::fetch($vs_cache_key, 'listItems'); }
		
		if(isset($pa_options['dontIncludeSubtypesInTypeRestriction']) && (!isset($pa_options['dont_include_subtypes_in_type_restriction']) || !$pa_options['dont_include_subtypes_in_type_restriction'])) { $pa_options['dont_include_subtypes_in_type_restriction'] = $pa_options['dontIncludeSubtypesInTypeRestriction']; }
	 	
		if (isset($pa_options['dont_include_subtypes_in_type_restriction']) && $pa_options['dont_include_subtypes_in_type_restriction']) {
			$pa_options['noChildren'] = true;
		}
		
		$va_item_ids = [];
		$t_list = new ca_lists();
		$t_item = new ca_list_items();
		
		if (!is_array($va_item_ids_in_list = $t_list->getItemsForList($pm_list_code_or_id, ['idsOnly' => true]))) { $va_item_ids_in_list = []; }
	
		foreach($pa_item_idnos as $vm_item) {
			if (!$vm_item) { continue; }
			$vn_type_id = null;
			if (is_numeric($vm_item)) { 
				$vn_type_id = (int)$vm_item; 
			} else {
				$vn_type_id = (int)$t_list->getItemIDFromList($pm_list_code_or_id, $vm_item);
			}
			if (!in_array($vn_type_id, $va_item_ids_in_list)) { continue; }	// skip type_id if it's not in list $pm_list_code_or_id
			
			if ($vn_type_id && (!isset($pa_options['noChildren']) || !$pa_options['noChildren'])) {
				if ($qr_children = $t_item->getHierarchy($vn_type_id, array())) {
					while($qr_children->nextRow()) {
						$va_item_ids[$qr_children->get('item_id')] = true;
					}
				}
			} else {
				if ($vn_type_id) {
					$va_item_ids[$vn_type_id] = true;
				}
			}
		}
		$va_ret = array_keys($va_item_ids);
		ExternalCache::save($vs_cache_key, $va_ret, 'listItems');
		
		return $va_ret;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Converts the given list of type codes or type_ids into an expanded list of type idnos (aka codes) 
	 * suitable for enforcing type restrictions. Processing includes expansion of types to include subtypes 
	 * and conversion of any type codes to type idnos.
	 *
	 * @param mixed $pm_table_name_or_num Table name or number to which types apply
	 * @param array $pa_types List of type codes and/or type_ids that are the basis of the list
	 * @param array $pa_options Array of options:
	 * 		dont_include_subtypes_in_type_restriction = if set, returned list is not expanded to include subtypes
	 *		dontIncludeSubtypesInTypeRestriction = synonym for dont_include_subtypes_in_type_restriction
	 *
	 * @return array List of type codes
	 */
	function caMakeTypeList($pm_table_name_or_num, $pa_type_ids, $pa_options=null) {
		if (is_array($pa_type_ids) && !sizeof($pa_type_ids)) { return array(); }
		if (!is_array($pa_type_ids)) { $pa_type_ids = [$pa_type_ids]; }
		
		if(isset($pa_options['dontIncludeSubtypesInTypeRestriction']) && (!isset($pa_options['dont_include_subtypes_in_type_restriction']) || !$pa_options['dont_include_subtypes_in_type_restriction'])) { $pa_options['dont_include_subtypes_in_type_restriction'] = $pa_options['dontIncludeSubtypesInTypeRestriction']; }
	 	
		if (isset($pa_options['dont_include_subtypes_in_type_restriction']) && $pa_options['dont_include_subtypes_in_type_restriction']) {
			$pa_options['noChildren'] = true;
		}
	
		if (is_numeric($pm_table_name_or_num)) {
			$vs_table_name = Datamodel::getTableName($pm_table_name_or_num);
		} else {
			$vs_table_name = $pm_table_name_or_num;
		}
		$t_instance = Datamodel::getInstanceByTableName($vs_table_name, true);
		if (!$t_instance) { return null; }	// bad table
		if (!($vs_type_list_code = $t_instance->getTypeListCode())) { return null; }	// table doesn't use types
		
		$t_item = new ca_list_items();
		
		$va_type_codes = [];
		
		foreach($pa_type_ids as $vm_type) {
			if (!$vm_type) { continue; }
			$vs_type_code = null;
			if (is_numeric($vm_type)) { 
				$vs_type_code = caGetListItemIdno($vm_type);
			} else {
				$vs_type_code = $vm_type;
			}
			
			if ($vs_type_code && (!isset($pa_options['noChildren']) || !$pa_options['noChildren'])) {
				if ($qr_children = $t_item->getHierarchy(caGetListItemID($vs_type_list_code, $vs_type_code), array())) {
					while($qr_children->nextRow()) {
						$va_type_codes[$qr_children->get('idno')] = true;
					}
				}
			} elseif ($vs_type_code) {
				$va_type_codes[$vs_type_code] = true;
			}
			
			$va_type_codes[$vs_type_code] = true;
		}
		return array_keys($va_type_codes);
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Converts the given list of source names or source_ids into an expanded list of numeric source_ids suitable for enforcing source restrictions. Processing
	 * includes expansion of sources to include subsources and conversion of any source codes to source_ids.
	 *
	 * @param mixed $pm_table_name_or_num Table name or number to which sources apply
	 * @param array $pa_sources List of source codes and/or source_ids that are the basis of the list
	 * @param array $pa_options Array of options:
	 * 		dont_include_subsources_in_source_restriction = if set, returned list is not expanded to include subsources
	 *		dontIncludeSubsourcesInSourceRestriction = synonym for dont_include_subsources_in_source_restriction
	 *
	 * @return array List of numeric source_ids
	 */
	function caMakeSourceIDList($pm_table_name_or_num, $pa_sources, $pa_options=null) {
		if(isset($pa_options['dontIncludeSubsourcesInSourceRestriction']) && (!isset($pa_options['dont_include_subsources_in_source_restriction']) || !$pa_options['dont_include_subsources_in_source_restriction'])) { $pa_options['dont_include_subsources_in_source_restriction'] = $pa_options['dontIncludeSubsourcesInSourceRestriction']; }
	 	
		if (isset($pa_options['dont_include_subsources_in_source_restriction']) && $pa_options['dont_include_subsources_in_source_restriction']) {
			$pa_options['noChildren'] = true;
		}
		
		if (is_numeric($pm_table_name_or_num)) {
			$vs_table_name = Datamodel::getTableName($pm_table_name_or_num);
		} else {
			$vs_table_name = $pm_table_name_or_num;
		}
		$t_instance = Datamodel::getInstanceByTableName($vs_table_name, true);
		if (!$t_instance) { return null; }	// bad table
		if (!($vs_source_list_code = $t_instance->getSourceListCode())) { return null; }	// table doesn't use sources
		
		$va_source_ids = array();
		$t_list = new ca_lists();
		$t_item = new ca_list_items();
		
		$vs_list_code = $t_instance->getSourceListCode();
		foreach($pa_sources as $vm_source) {
			if (!$vm_source) { continue; }
			$vn_source_id = null;
			if (is_numeric($vm_source)) { 
				$vn_source_id = (int)$vm_source; 
			} else {
				$vn_source_id = (int)$t_list->getItemIDFromList($vs_source_list_code, $vm_source);
			}
			
			if ($vn_source_id && !(isset($pa_options['noChildren']) || $pa_options['noChildren'])) {
				if ($qr_children = $t_item->getHierarchy($vn_source_id, array())) {
					while($qr_children->nextRow()) {
						$va_source_ids[$qr_children->get('item_id')] = true;
					}
				}
			} else {
				if ($vn_source_id) {
					$va_source_ids[$vn_source_id] = true;
				}
			}
		}
		return array_keys($va_source_ids);
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
		if (!$pa_types) { return []; }
		if (!is_array($pa_types)) { $pa_types = [$pa_types]; }
		
		if(isset($pa_options['dontIncludeSubtypesInTypeRestriction']) && (!isset($pa_options['dont_include_subtypes_in_type_restriction']) || !$pa_options['dont_include_subtypes_in_type_restriction'])) { $pa_options['dont_include_subtypes_in_type_restriction'] = $pa_options['dontIncludeSubtypesInTypeRestriction']; }
	 	
		$pa_options['includeChildren'] = (isset($pa_options['dont_include_subtypes_in_type_restriction']) && $pa_options['dont_include_subtypes_in_type_restriction']) ? false : true;
		
		$t_rel_type = new ca_relationship_types();
		return $t_rel_type->relationshipTypeListToIDs($pm_table_name_or_num, $pa_types, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	function caGetRelationshipTableName($pm_table_name_or_num_left, $pm_table_name_or_num_right, $pa_options=null) {
		
		$va_path = Datamodel::getPath($pm_table_name_or_num_left, $pm_table_name_or_num_right);
		if (!is_array($va_path)) { return null; }
		
		
		foreach(array_keys($va_path) as $vs_table) {
			if ($t_instance = Datamodel::getInstanceByTableName($vs_table, true)) {
				if (method_exists($t_instance, "isRelationship") && $t_instance->isRelationship() && $t_instance->hasField('type_id')) {
					return $vs_table;
				}
			}
		}
		return null;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Merges types specified with any specified "restrict_to_types"/"restrictToTypes" option, user access settings and types configured in app.conf
	 * into a single list of type_ids suitable for enforcing type restrictions.
	 *
	 * @param BaseModel $t_instance A model instance for the table to which the types apply
	 * @param array $pa_options An array of options containing, if specified, a list of types for either the "restrict_to_types" or "restrictToTypes" keys. Other options include:
	 *		dontIncludeSubtypesInTypeRestriction = Don't expand types to include child types. [Default is true]
	 * 
	 * @return array List of numeric type_ids for which the user has access
	 */
	function caMergeTypeRestrictionLists($t_instance, $pa_options) {
		$va_restrict_to_type_ids = null;
		if (is_array($pa_options['restrict_to_types']) && sizeof($pa_options['restrict_to_types'])) {
			$pa_options['restrictToTypes'] = $pa_options['restrict_to_types'];
		}
		if (is_array($pa_options['restrictToTypes']) && sizeof($pa_options['restrictToTypes'])) {
			$va_restrict_to_type_ids = caMakeTypeIDList($t_instance->tableName(), $pa_options['restrictToTypes'], array('noChildren' => caGetOption(['dontIncludeSubtypesInTypeRestriction', 'dont_include_subtypes_in_type_restriction'], $pa_options, true)));
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
	 * Merges sources specified with any specified "restrict_to_sources"/"restrictToSources" option, user access settings and sources configured in app.conf
	 * into a single list of source_ids suitable for enforcing source restrictions.
	 *
	 * @param BaseModel $t_instance A model instance for the table to which the sources apply
	 * @param array $pa_options An array of options containing, if specified, a list of sources for either the "restrict_to_sources" or "restrictToSources" keys
	 * 
	 * @return array List of numeric source_ids for which the user has access
	 */
	function caMergeSourceRestrictionLists($t_instance, $pa_options) {
		$va_restrict_to_source_ids = null;
		if (is_array($pa_options['restrict_to_sources']) && sizeof($pa_options['restrict_to_sources'])) {
			$pa_options['restrictToSources'] = $pa_options['restrict_to_sources'];
		}
		if (is_array($pa_options['restrictToSources']) && sizeof($pa_options['restrictToSources'])) {
			$va_restrict_to_source_ids = caMakeSourceIDList($t_instance->tableName(), $pa_options['restrictToSources'], array('noChildren' => true));
		}
		
		$va_sources = null;
		
		$o_config = Configuration::load();
		if ((bool)$o_config->get('perform_source_access_checking') && method_exists($t_instance, 'getSourceFieldName') && ($vs_source_field_name = $t_instance->getSourceFieldName())) {
			$va_sources = caGetSourceRestrictionsForUser($t_instance->tableName());
		}
		if (is_array($va_sources) && sizeof($va_sources) && is_array($va_restrict_to_source_ids) && sizeof($va_restrict_to_source_ids)) {
			if (sizeof($va_tmp = array_intersect($va_restrict_to_source_ids, $va_sources))) {
				$va_sources = $va_tmp;
			}
		} else {
			if (!is_array($va_sources) || !sizeof($va_sources)) {
				$va_sources = $va_restrict_to_source_ids;
			}
		}
		
		return $va_sources;
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
$g_bundle_access_level_cache = array();
	function caGetBundleAccessLevel($ps_table_name, $ps_bundle_name) {
		global $g_request, $g_bundle_access_level_cache;
		if (isset($g_bundle_access_level_cache[$ps_table_name][$ps_bundle_name])) { return $g_bundle_access_level_cache[$ps_table_name][$ps_bundle_name]; }
		list($ps_table_name, $ps_bundle_name) = caTranslateBundlesForAccessChecking($ps_table_name, $ps_bundle_name);

		if ($g_request) {
			return $g_bundle_access_level_cache[$ps_table_name][$ps_bundle_name] = $g_request->user->getBundleAccessLevel($ps_table_name, $ps_bundle_name);
		}
		
		$o_config = Configuration::load();
		return $g_bundle_access_level_cache[$ps_table_name][$ps_bundle_name] = (int)$o_config->get('default_bundle_access_level');
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
$g_type_access_level_cache = array();
	function caGetTypeAccessLevel($ps_table_name, $pm_type_code_or_id) {
		global $g_request, $g_type_access_level_cache;
		if (isset($g_type_access_level_cache[$ps_table_name][$pm_type_code_or_id])) { return $g_type_access_level_cache[$ps_table_name][$pm_type_code_or_id]; }
		if ($g_request) {
			return $g_type_access_level_cache[$ps_table_name][$pm_type_code_or_id] = $g_request->user->getTypeAccessLevel($ps_table_name, $pm_type_code_or_id);
		}
		
		$o_config = Configuration::load();
		return $g_type_access_level_cache[$ps_table_name][$pm_type_code_or_id] = (int)$o_config->get('default_type_access_level');
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Returns allowed access for the currently logged in user to the specified source
	 *
	 * @param string $ps_table_name Table name of bundle (Eg. ca_objects, ca_entities, ca_places)
	 * @param string $pm_source_code_or_id Code or source_id for source to check
	 * @return int Numeric constant representing access. Values are:
	 *		__CA_BUNDLE_ACCESS_NONE__ (0)
	 *		__CA_BUNDLE_ACCESS_READONLY__ (1)
	 *		__CA_BUNDLE_ACCESS_EDIT__ (2)
	 */
$g_source_access_level_cache = array();
	function caGetSourceAccessLevel($ps_table_name, $pm_source_code_or_id) {
		global $g_request, $g_source_access_level_cache;
		if (isset($g_source_access_level_cache[$ps_table_name][$pm_source_code_or_id])) { return $g_source_access_level_cache[$ps_table_name][$pm_source_code_or_id]; }
		if ($g_request) {
			return $g_source_access_level_cache[$ps_table_name][$pm_source_code_or_id] = $g_request->user->getSourceAccessLevel($ps_table_name, $pm_source_code_or_id);
		}
		
		$o_config = Configuration::load();
		return $g_source_access_level_cache[$ps_table_name][$pm_source_code_or_id] = (int)$o_config->get('default_source_access_level');
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Determines if the specified item (and optionally a specific bundle in that item) are readable by the user
	 *
	 * @param int $pn_user_id
	 * @param mixed $pm_table A table name or number
	 * @param mixed $pm_id A primary key value of the row, or an array of values to check. If a single integer value is provided then a boolean result will be returned; if an array of values is provided then an array will be returned with all ids that are readable
	 * @param string $ps_bundle_name An optional bundle to check access for
	 *
	 * @return If $pm_id is an integer return true if user has read access, otherwise false if the user does not have access; if $pm_id is an array of ids, returns an array with all ids the are readable; returns null if one or more parameters are invalid
	 */
	function caCanRead($pn_user_id, $pm_table, $pm_id, $ps_bundle_name=null, $pa_options=null) {
		$pb_return_as_array = caGetOption('returnAsArray', $pa_options, false);
		$t_user = new ca_users($pn_user_id, true);
		if (!$t_user->getPrimaryKey()) { return null; }
		
		$ps_table_name = (is_numeric($pm_table)) ? Datamodel::getTableName($pm_table) : $pm_table;		
	
		if (!is_array($pm_id)) { $pm_id = array($pm_id); }
		
		if ($ps_bundle_name) {
			if ($t_user->getBundleAccessLevel($ps_table_name, $ps_bundle_name) < __CA_BUNDLE_ACCESS_READONLY__) { 
				return ((sizeof($pm_id) == 1) && !$pb_return_as_array) ? false : array();
			}
		}
		
		if (!($t_instance = Datamodel::getInstanceByTableName($ps_table_name, true))) { return null; }
	
		$vb_do_type_access_check = (bool)$t_instance->getAppConfig()->get('perform_type_access_checking');
		$vb_do_item_access_check = (bool)$t_instance->getAppConfig()->get('perform_item_level_access_checking');
		
		list($ps_table_name, $ps_bundle_name) = caTranslateBundlesForAccessChecking($ps_table_name, $ps_bundle_name);
		
		if (!($qr_res = caMakeSearchResult($ps_table_name, $pm_id))) { return null; }
		
		$va_return_values = array();
		while($qr_res->nextHit()) {
			$vn_id = $qr_res->getPrimaryKey();
		
			// Check type restrictions
			if ($vb_do_type_access_check) {
				$vn_type_access = $t_user->getTypeAccessLevel($ps_table_name, $qr_res->get("{$ps_table_name}.type_id"));
				if ($vn_type_access < __CA_BUNDLE_ACCESS_READONLY__) {
					continue;
				}
			}
		
			// Check item level restrictions
			if ($vb_do_item_access_check) {
				$vn_item_access = $t_instance->checkACLAccessForUser($t_user, $vn_id);
				if ($vn_item_access < __CA_ACL_READONLY_ACCESS__) {
					continue;
				}
			}
		
			$va_return_values[] = $vn_id;
		}
		
		if ((sizeof($pm_id) == 1) && !$pb_return_as_array) { return (sizeof($va_return_values) > 0) ? true : false; }
		return $va_return_values;
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
		
		if (!($t_instance = Datamodel::getInstanceByTableName($ps_table_name, true))) { return array($ps_table_name, $ps_bundle_name); }
		
			
		// Translate primary label references
		if (method_exists($t_instance, 'getLabelTableName')) {
			if ($t_instance->getLabelTableName() == $va_tmp[0]) {
				return array($ps_table_name, 'preferred_labels');
			}
		}
		
		// Translate related label references
		$t_rel = Datamodel::getInstanceByTableName($va_tmp[0], true);
		if ($t_rel) {
			if (method_exists($t_rel, 'getSubjectTableName')) {
				return array($t_rel->getSubjectTableName(), 'preferred_labels');
			}
		}
		
		// Related tables
		if($t_rel && (is_array($va_path = Datamodel::getPath($va_tmp[0], $ps_table_name))) && (sizeof($va_path) == 3)) {
			return array($ps_table_name, $va_tmp[0]);
		}
		
		// Translate subfields
		if (sizeof($va_tmp = explode('.', $ps_bundle_name)) > 2) {
			return array($va_tmp[0], $va_tmp[1]);
		}
		return array($ps_table_name, $ps_bundle_name); 
	}
	# ---------------------------------------------------------------------------------------------
