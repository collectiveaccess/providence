<?php
/** ---------------------------------------------------------------------
 * app/helpers/listHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
	 * @return int list_id of list or null if no matching list was found
	 */
	function caGetListID($ps_list) {
		$t_list = new ca_lists();
		
		if (is_numeric($ps_list)) {
			if ($t_list->load((int)$ps_list)) {
				return $t_list->getPrimaryKey();
			}
		}
		
		if ($t_list->load(array('list_code' => $ps_list))) {
			return $t_list->getPrimaryKey();
		}
		
		$t_label = new ca_list_labels();
		if ($t_label->load(array('name' => $ps_list))) {
			return $t_label->get('list_id');
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * Fetch item_id for item with specified idno in list
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_idno idno of item to get item_id for
	 * @return int item_id of list item or null if no matching item was found
	 */
	function caGetListItemID($ps_list_code, $ps_idno) {
		$t_list = new ca_lists();
		
		return $t_list->getItemIDFromList($ps_list_code, $ps_idno);
	}
	# ---------------------------------------
	/**
	 * Fetch list_code for list with specified list_id
	 *
	 * @param int $pn_list_id List ID
	 * @return string The list code for the list, or null if the list_id is not valid
	 */
	$g_ca_get_list_code_cache = array();
	function caGetListCode($pn_list_id) {
		global $g_ca_get_list_code_cache;
		if(isset($g_ca_get_list_code_cache[$pn_list_id])) { return $g_ca_get_list_code_cache[$pn_list_id]; }
		$t_list = new ca_lists($pn_list_id);
		
		return $g_ca_get_list_code_cache[$pn_list_id] = $t_list->get('list_code');
	}
	# ---------------------------------------
	/**
	 * Fetch idno for item with specified item_id in list
	 *
	 * @param int $pn_item_id item_id to get idno for
	 * @return string idno of list item or null if no matching item was found
	 */
	function caGetListItemIdno($pn_item_id) {
		$t_item = new ca_list_items($pn_item_id);
		return $t_item->get('idno');
	}
	# ---------------------------------------
	/**
	 * Fetch display label in current locale for item with specified idno in list
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_idno idno of item to get label for
	 * @param bool $pb_return_plural If true, return plural version of label. Default is to return singular version of label.
	 * @return string The label of the list item, or null if no matching item was found
	 */
	function caGetListItemForDisplay($ps_list_code, $ps_idno, $pb_return_plural=false) {
		$t_list = new ca_lists();
		
		return $t_list->getItemFromListForDisplay($ps_list_code, $ps_idno, $pb_return_plural);
	}
	# ---------------------------------------
	/**
	 * Fetch item_id for item with specified label. Value must match exactly.
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_label The label value to search for
	 * @return int item_id of list item or null if no matching item was found
	 */
	function caGetListItemIDForLabel($ps_list_code, $ps_label) {
		$t_list = new ca_lists();
		
		return $t_list->getItemIDFromListByLabel($ps_list_code, $ps_label);
	}
	# ---------------------------------------
	/**
	 * Fetch item_id for default item in list
	 *
	 * @param string $ps_list_code List code
	 * @return int item_id of list item or null if no default item was found
	 */
	function caGetDefaultItemID($ps_list_code) {
		$t_list = new ca_lists();
		
		return $t_list->getDefaultItemID($ps_list_code);
	}
	# ---------------------------------------
?>