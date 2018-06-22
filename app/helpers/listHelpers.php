<?php
/** ---------------------------------------------------------------------
 * app/helpers/listHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2018 Whirl-i-Gig
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
   
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_list_labels.php');
require_once(__CA_MODELS_DIR__.'/ca_list_items.php');

	
	# ---------------------------------------
	/**
	 * Fetch item_id for item with specified idno in list
	 *
	 * @param string $ps_list List code or list label
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 * @return int list_id of list or null if no matching list was found
	 */
	$g_list_id_cache = [];
	function caGetListID($ps_list, $pa_options=null) {
		global $g_list_id_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $ps_list);
		
		if(isset($g_list_id_cache[$vs_cache_key])) { return $g_list_id_cache[$vs_cache_key]; }
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		if (is_numeric($ps_list)) {
			if ($t_list->load((int)$ps_list)) {
				return $g_list_id_cache[$vs_cache_key] = $t_list->getPrimaryKey();
			}
		}
		
		if ($t_list->load(array('list_code' => $ps_list))) {
			return $g_list_id_cache[$vs_cache_key] = $t_list->getPrimaryKey();
		}
		
		$t_label = new ca_list_labels();
		if ($t_label->load(array('name' => $ps_list))) {
			return $g_list_id_cache[$vs_cache_key] = $t_label->get('list_id');
		}
		return $g_list_id_cache[$vs_cache_key] = null;
	}
	# ---------------------------------------
	/**
	 * Convert array of list codes into array of list ids
	 *
	 * @param array $pa_lists Array of list codes or list_ids
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 * @return array A list of list_ids
	 */
	function caMakeListIDList($pa_lists, $pa_options=null) {
		global $g_list_id_cache;
		
		$va_list_codes = array_flip($va_list_ids = ca_lists::getListCodes($pa_options));
		
		$va_ids = [];
		foreach($pa_lists as $pm_list) {
			if (is_numeric($pm_list) && isset($va_list_ids[$pm_list])) {
				$va_ids[] = (int)$pm_list;
			} elseif (isset($va_list_codes[$pm_list])) { 
				$va_ids[] = (int)$va_list_codes[$pm_list];
			}
			$g_list_id_cache[$pm_list] = $va_list_codes[$pm_list] ? (int)$va_list_codes[$pm_list] : null;
		}
		
		return $va_ids;
	}
	# ---------------------------------------
	/**
	 * Fetch item_id for item with specified idno in list
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_idno idno of item to get item_id for
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
     *
	 * @return int item_id of list item or null if no matching item was found
	 */
	$g_list_item_id_cache = [];
	function caGetListItemID($ps_list_code, $ps_idno, $pa_options=null) {
		global $g_list_item_id_cache;
	    $vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$ps_list_code}/{$ps_idno}");
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
			if(isset($g_list_item_id_cache[$vs_cache_key])) { return $g_list_item_id_cache[$vs_cache_key]; }
		}
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		return $g_list_item_id_cache[$vs_cache_key] = $t_list->getItemIDFromList($ps_list_code, $ps_idno, $pa_options);
	}
	# ---------------------------------------
	/**
	 * Fetch list_code for list with specified list_id
	 *
	 * @param int $pn_list_id List ID
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
     *
	 * @return string The list code for the list, or null if the list_id is not valid
	 */
	$g_ca_get_list_code_cache = [];
	function caGetListCode($pn_list_id, $pa_options=null) {
		global $g_ca_get_list_code_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $pn_list_id);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_ca_get_list_code_cache[$vs_cache_key])) { return $g_ca_get_list_code_cache[$vs_cache_key]; }
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		if (!$t_list->load($pn_list_id)) { return null; }
		
		return $g_ca_get_list_code_cache[$vs_cache_key] = $t_list->get('list_code');
	}
	# ---------------------------------------
	/**
	 * Fetch list name for list with specified list_id
	 *
	 * @param mixed $pm_list_code_or_id List code or numeric list_id
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
     *
	 * @return string The preferred label for the list, or null if no list exists
	 */
	$g_ca_get_list_name_cache = [];
	function caGetListName($pm_list_code_or_id, $pa_options=null) {
		global $g_ca_get_list_name_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $pm_list_code_or_id);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_ca_get_list_name_cache[$vs_cache_key])) { return $g_ca_get_list_name_cache[$vs_cache_key]; }
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		if (is_numeric($pm_list_code_or_id)) {
		    if (!$t_list->load($pm_list_code_or_id)) { return null; }
		} else {
		    if (!($t_list = ca_lists::find(['list_code' => $pm_list_code_or_id], ['returnAs' => 'firstModelInstance']))) { return null; }
		}
		
		return $g_ca_get_list_name_cache[$vs_cache_key] = $t_list->get('ca_lists.preferred_labels.name');
	}
	# ---------------------------------------
	/**
	 * Fetch idno for item with specified item_id in list
	 *
	 * @param int $pn_item_id item_id to get idno for
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return string idno of list item or null if no matching item was found
	 */
	$g_list_item_idno_cache = [];
	function caGetListItemIdno($pn_item_id, $pa_options=null) {
		global $g_list_item_idno_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $pn_item_id);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_item_idno_cache[$vs_cache_key])) { return $g_list_item_idno_cache[$vs_cache_key]; }
		}
		
		$t_item = new ca_list_items();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_item->setTransaction($o_trans); }
		if (!$t_item->load($pn_item_id)) { return null; }
		if (is_array($pa_check_access = caGetOption('checkAccess', $pa_options, null)) && (sizeof($pa_check_access) > 0) && !in_array($t_item->get('ca_list_items.access'), $pa_check_access)) { return null; }
		
		return $g_list_item_idno_cache[$vs_cache_key] = $t_item->get('idno');
	}
	# ---------------------------------------
	/**
	 * Fetch display label in current locale for item with specified idno in list
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_idno idno of item to get label for
	 * @param bool $pb_return_plural If true, return plural version of label. Default is to return singular version of label.
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return string The label of the list item, or null if no matching item was found
	 */
	$g_list_item_label_cache = [];
	function caGetListItemForDisplay($ps_list_code, $ps_idno, $pa_options=null) {
		global $g_list_item_label_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$ps_list_code}/{$ps_idno}");
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_item_label_cache[$vs_cache_key])) { return $g_list_item_label_cache[$vs_cache_key]; }
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		return $g_list_item_label_cache[$vs_cache_key] = $t_list->getItemFromListForDisplay($ps_list_code, $ps_idno, $pa_options);
	}
	# ---------------------------------------
	/**
	 * Fetch display label in current locale for item with specified item_id
	 *
	 * @param int $pn_item_id item_id of item to get label for
	 * @param bool $pb_return_plural If true, return plural version of label. Default is to return singular version of label.
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return string The label of the list item, or null if no matching item was found
	 */
	$g_list_item_label_cache = [];
	function caGetListItemByIDForDisplay($pn_item_id, $pa_options=null) {
		global $g_list_item_label_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $pn_item_id);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_item_label_cache[$vs_cache_key])) { return $g_list_item_label_cache[$vs_cache_key]; }
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		return $g_list_item_label_cache[$vs_cache_key] = $t_list->getItemForDisplayByItemID($pn_item_id, $pa_options);
	}
	# ---------------------------------------
	/**
	 * Get list item id for value. Can be useful when handling access/status values
	 * @param string $ps_list_code Code of the list
	 * @param string $ps_value item_value of the list item in question
	 * @param array $pa_options Options for ca_lists::getItemFromListByItemValue() plus:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return string|null
	 */
	$g_list_item_ids_for_values = [];
	function caGetListItemIDForValue($ps_list_code, $ps_value, $pa_options=null) {
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$ps_list_code}/{$ps_value}");
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_item_ids_for_values[$vs_cache_key])) { return $g_list_item_ids_for_values[$vs_cache_key]; }
        }
        
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		if ($va_item = $t_list->getItemFromListByItemValue($ps_list_code, $ps_value, $pa_options)) {
			return $g_list_item_ids_for_values[$vs_cache_key] = array_shift(array_keys($va_item));
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * Get list item value for a given item_id
	 * @param int $pn_id
	 * @param null|array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return string|null
	 */
	$g_list_item_values_for_ids = [];
	function caGetListItemValueForID($pn_id, $pa_options=null) {
		if(!$pn_id || !is_numeric($pn_id)) { return null; }
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $pn_id);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_item_values_for_ids[$vs_cache_key])) { return $g_list_item_values_for_ids[$vs_cache_key]; }
        }
        
		$t_item = new ca_list_items();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_item->setTransaction($o_trans); }

		if ($t_item->load($pn_id)) {
		    if (is_array($pa_check_access = caGetOption('checkAccess', $pa_options, null)) && (sizeof($pa_check_access) > 0) && !in_array($t_item->get('ca_list_items.access'), $pa_check_access)) { return null; }
			return $g_list_item_values_for_ids[$vs_cache_key] = $t_item->get('item_value');
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * Get List item value for a given list code and idno
	 *
	 * @param string $ps_list_code
	 * @param string $ps_idno
	 * @return string
	 */
	function caGetListItemValueForIdno($ps_list_code, $ps_idno) {
		return caGetListItemValueForID(caGetListItemID($ps_list_code, $ps_idno));
	}
	# ---------------------------------------
	/**
	 * Fetch item_id for item with specified label. Value must match exactly.
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_label The label value to search for
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
     *
	 * @return int item_id of list item or null if no matching item was found
	 */
	$g_list_item_id_for_label_cache = [];
	function caGetListItemIDForLabel($ps_list_code, $ps_label, $pa_options=null) {
		global $g_list_item_id_for_label_cache;
		
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, "{$ps_list_code}/{$ps_label}");
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_item_id_for_label_cache[$vs_cache_key])) { return $g_list_item_id_for_label_cache[$vs_cache_key]; }
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		return $g_list_item_id_for_label_cache[$vs_cache_key] = $t_list->getItemIDFromListByLabel($ps_list_code, $ps_label, $pa_options);
	}
	# ---------------------------------------
	/**
	 * Return simple array with items from a list. Array keys and values may be any list item field.
	 *
	 * @param string $ps_list_code List code
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *		index = name of field to index returned list on. [Default is item_id]
	 *		value = value to return in list. [Default is name_plural]
	 *		defaultOnly = return only default item in list. [Default is false]	 
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
	 *      checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
     *
	 * @return array
	 */
	$g_list_items_cache = [];
	function caGetListItems($ps_list_code, $pa_options=null) {
		global $g_list_items_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $ps_list_code);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_items_cache[$vs_cache_key])) { return $g_list_items_cache[$vs_cache_key]; }
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		$key = caGetOption('index', $pa_options, 'id');
		$value = caGetOption('value', $pa_options, 'name_plural');
		$default_only = caGetOption('defaultOnly', $pa_options, false);
		
		$list_items = caExtractValuesByUserLocale($t_list->getItemsForList($ps_list_code, $pa_options));
		
		$list = [];
		foreach($list_items as $id => $item) {
			if ($default_only && !$item['is_default']) { continue; }
			$list[isset($item[$key]) ? $item[$key] : $id] = isset($item[$value]) ? $item[$value] : $item['name_plural'];
		}
		ksort($list);
		return $g_list_items_cache[$vs_cache_key] = $list;
	}
	# ---------------------------------------
	/**
	 * Fetch item_id for default item in list
	 *
	 * @param string $ps_list_code List code
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
     *
	 * @return int item_id of list item or null if no default item was found
	 */
	$g_default_list_item_id_cache = [];
	function caGetDefaultItemID($ps_list_code, $pa_options=null) {
		global $g_default_list_item_id_cache;
		if (!is_array($pa_options)) { $pa_options = []; }
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $ps_list_code);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_default_list_item_id_cache[$vs_cache_key])) { return $g_default_list_item_id_cache[$vs_cache_key]; }
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		
		return $g_default_list_item_id_cache[$vs_cache_key] = $t_list->getDefaultItemID($ps_list_code, array_merge($pa_options, ['useFirstElementAsDefaultDefault' => true]));
	}
	# ---------------------------------------
	/**
	 * Converts the given list of list idnos or item_ids into a list of numeric item_ids
	 *
	 * @param mixed $pm_list List code or list_id
	 * @param array $pa_list_items List of item idnos and/or item_ids 
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 *
	 * @return array List of numeric item_ids
	 */
	function caMakeListItemIDList($pm_list, $pa_list_items, $pa_options=null) {
		if (!($vn_list_id = caGetListID($pm_list))) return [];
		$t_item = new ca_list_items();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_item->setTransaction($o_trans); }
		
		$va_ids = [];
		
		foreach($pa_list_items as $vm_item) {
			if (is_numeric($vm_item) && ((int)$vm_item > 0)) {
				$va_ids[(int)$vm_item] = true;
			} else {
				if ($vn_id = caGetListItemID($vn_list_id, $vm_item, $pa_options)) {
					$va_ids[(int)$vn_id] = true;
				}
			}
		}
		
		return array_keys($va_ids);
	}
	# ---------------------------------------
	/**
	 * Return item_ids for ancestors of an item. 
	 *
	 * Note: does NOT support checkAccess option.
	 *
	 * @param mixed $pm_item_id 
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default is null]
	 *		includeSelf = include $pn_item_id in returned list. [Default is false]	 
	 *      noCache = Don't use cache. [Default is false]
	 *      dontCache = Synonym for noCache
     *
	 * @return array An array of item_ids for items that are ancestors of the specified item. The specified item_id is only included in the returned list if the includeSelf option is set.
	 */
	$g_list_item_id_ancestors_cache = [];
	function caGetAncestorsForItemID($pm_item_id, $pa_options=null) {
		if(!$pm_item_id) { return null; }
		global $g_list_item_id_ancestors_cache;
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $pm_item_id);
		
		if(!caGetOption(['noCache', 'dontCache'], $pa_options, false)) {
		    if(isset($g_list_item_id_ancestors_cache[$vs_cache_key])) { return $g_list_item_id_ancestors_cache[$vs_cache_key]; }
		}
		
		$t_item = new ca_list_items();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_item->setTransaction($o_trans); }
		
		if (!is_array($pm_item_id)) { $pm_item_id = [$pm_item_id]; }
		
		$va_acc = caGetOption('includeSelf', $pa_options, false) ? [$pn_item_id] : [];
		foreach($pm_item_id as $pn_item_id) {
			if (is_array($va_ancestors = $t_item->getHierarchyAncestors($pn_item_id, ['idsOnly' => true, 'includeSelf' => caGetOption('includeSelf', $pa_options, false)]))) {
				$va_acc = array_merge($va_acc, $va_ancestors);
			}
		}
		
		return $g_list_item_id_ancestors_cache[$vs_cache_key] = array_unique($va_acc);
	}
	# ---------------------------------------
	/**
	 * Fetch the list of defined types for a table 
	 *
	 * @param mixed $pm_table_name_or_num 
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 * @return array Returns an array keys by type_id with type information, or null if the table is invalid.
	 */
	function caGetTypeList($pm_table_name_or_num, $pa_options=null) {
		if (($t_instance = Datamodel::getInstance($pm_table_name_or_num)) && (method_exists($t_instance, "getTypeList"))) {
			if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_instance->setTransaction($o_trans); }
			return $t_instance->getTypeList($pa_options);
		}

		return null;
	}
	# ---------------------------------------
	/**
	 * Get label type list
	 * @param string|int $pm_table_name_or_num
	 * @param bool $pb_preferred
	 * @return string|null
	 */
	function caGetLabelTypeList($pm_table_name_or_num, $pb_preferred = true) {
		$vs_table_name = Datamodel::getTableName($pm_table_name_or_num);

		$o_conf = Configuration::load();
		return $o_conf->get($pb_preferred ? "{$vs_table_name}_preferred_label_type_list" : "{$vs_table_name}_nonpreferred_label_type_list");
	}
	# ---------------------------------------
	/**
	 * Fetch the id of the root item in list
	 *
	 * @param string $ps_list_code List code
	 * @param array $pa_options Options include:
	 *		transaction = transaction to execute queries within. [Default=null]
	 * @return int item_id of the root list item or null if no default item was found
	 */
	function caGetListRootID($ps_list_code, $pa_options=null) {
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }

		return $t_list->getRootListItemID($ps_list_code);
	}
	# ---------------------------------------
	/**
	 * Fetch the type code for a given relationship type id (primary key value)
	 * @param $pn_type_id
	 * @return Array|bool|mixed|null|string
	 */
	function caGetRelationshipTypeCode($pn_type_id) {
		if(CompositeCache::contains($pn_type_id, 'RelationshipIDsToCodes')) {
			return CompositeCache::fetch($pn_type_id, 'RelationshipIDsToCodes');
		}

		$t_rel_types = new ca_relationship_types($pn_type_id);
		if(!$t_rel_types->getPrimaryKey()) { return false; }

		$vs_code = $t_rel_types->get('type_code');
		CompositeCache::save($pn_type_id, $vs_code, 'RelationshipIDsToCodes');
		return $vs_code;
	}
	# ---------------------------------------
	/**
	 * Fetch the type id (primary key) for a given relationship type code
	 *
	 * @param int|string $pm_table_name_or_num
	 * @param int|string $pm_type_code_or_id
	 * @return int|bool
	 */
	function caGetRelationshipTypeID($pm_table_name_or_num, $pm_type_code_or_id) {
		if(CompositeCache::contains($pm_table_name_or_num.'/'.$pm_type_code_or_id, 'RelationshipTypeCodesToIDs')) {
			return CompositeCache::fetch($pm_table_name_or_num.'/'.$pm_type_code_or_id, 'RelationshipTypeCodesToIDs');
		}

		$t_rel_types = new ca_relationship_types();
		$vn_id = $t_rel_types->getRelationshipTypeID($pm_table_name_or_num, $pm_type_code_or_id);
		if(!$vn_id) { return false; }

		CompositeCache::save($pm_table_name_or_num.'/'.$pm_type_code_or_id, $vn_id, 'RelationshipTypeCodesToIDs');
		return $vn_id;
	}
	# ---------------------------------------
