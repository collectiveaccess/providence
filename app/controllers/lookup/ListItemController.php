<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/ListItemController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/BaseLookupController.php");

class ListItemController extends BaseLookupController {
	# -------------------------------------------------------
	protected $opb_uses_hierarchy_browser = true;
	protected $ops_table_name = 'ca_list_items';		// name of "subject" table (what we're editing)
	protected $ops_name_singular = 'list_item';
	protected $ops_search_class = 'ListItemSearch';
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Get($pa_additional_query_params=null, $pa_options=null) {
		if ($ps_list = $this->request->getParameter('list', pString)) {
			if(!is_array($pa_additional_query_params)) { $pa_additional_query_params = array(); }
			
			$pa_additional_query_params[] = "ca_lists.list_code:{$ps_list}";
		} else {
			if ($ps_lists = $this->request->getParameter('lists', pString)) {
				if(!is_array($pa_additional_query_params)) { $pa_additional_query_params = array(); }
				
				$va_lists = explode(";", $ps_lists);
				
				$pa_options['hier_id'] = caGetListID($va_lists[0]);
				
				$va_tmp = array();
				$pa_options['filters'] = array();
				foreach($va_lists as $vs_list) {
					if ($vs_list = trim($vs_list)) {
						$va_tmp[] = "'".preg_replace("![\"']+!", "", $vs_list)."'";
					}
				}
				$pa_options['filters'][] = array("ca_list_items.list_id", "IN", join(",", $va_tmp));
			}
		}
		return parent::Get($pa_additional_query_params, $pa_options);
	}
	# -------------------------------------------------------
	/**
	 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
	 * Returned data is JSON format
	 */
	public function GetHierarchyLevel() {
		$ps_bundle = (string)$this->request->getParameter('bundle', pString);
		$pa_ids = explode(";", $ps_ids = $this->request->getParameter('id', pString));
		if (!sizeof($pa_ids)) { $pa_ids = array(null); }
		
		$t_item = $this->opo_item_instance;
		
		$vs_template = $t_item->getAppConfig()->get('ca_list_items_hierarchy_browser_display_settings');
		
		$va_lists = array();
		if ($ps_lists = $this->request->getParameter('lists', pString)) {
			$va_lists = explode(";", $ps_lists);
		}
		
		$vn_max_items_per_page = $this->request->getParameter('max', pInteger);
		if (($vn_max_items_per_page > 1000) || ($vn_max_items_per_page <= 0)) {
			$vn_max_items_per_page = 500;
		}
		
		$vn_list_id = null;
		$va_level_data = array();
		
		foreach($pa_ids as $pn_id) {
			$va_tmp = explode(":", $pn_id);
			$vn_id = $va_tmp[0] ?? null;
			$vn_start = (int)($va_tmp[1] ?? 0);
			if($vn_start < 0) { $vn_start = 0; }
			
			if (!$vn_id && method_exists($t_item, "getHierarchyList")) { 
				if (!($pn_list_id = $this->request->getParameter('list_id', pInteger))) {
					// no id so by default return list of available hierarchies
					$va_list_items = $t_item->getHierarchyList();
					
					if (sizeof($va_lists)) {
						// filter out lists that weren't specified
						foreach($va_list_items as $item_list_id => $va_list) {
							if (!in_array($item_list_id, $va_lists) && !in_array($va_list['list_code'] ?? null, $va_lists)) {
								unset($va_list_items[$item_list_id]);
							}
						}
					} else {
						if ($this->request->getParameter('voc', pInteger)) {
							// Only show vocabularies
							foreach($va_list_items as $item_list_id => $va_list) {
								if (!($va_list['use_as_vocabulary'] ?? false)) {
									unset($va_list_items[$item_list_id]);
								}
							}
						}
					}
				}
			} else {
				if ($t_item->load($vn_id)) {		// id is the id of the parent for the level we're going to return
					$vn_list_id = $t_item->get('list_id');
					$t_list = new ca_lists($vn_list_id);
				
					$vs_label_table_name = $this->opo_item_instance->getLabelTableName();
					$vs_label_display_field_name = $this->opo_item_instance->getLabelDisplayField();
					
					$va_list_items = $t_list->getItemsForList($vn_list_id, array('returnHierarchyLevels' => false, 'item_id' => $vn_id, 'extractValuesByUserLocale' => true, 'sort' => $t_list->get('sort_type'), 'directChildrenOnly' => true, 'limit' => $vn_max_items_per_page, 'start' => $vn_start));
		
					// output
					$va_display_values = caProcessTemplateForIDs($vs_template, 'ca_list_items', array_keys($va_list_items), array('requireLinkTags' => true, 'returnAsArray' => true));
					
					$vn_c = 0;
					foreach($va_list_items as $vn_item_id => $va_item) {
						unset($va_item['description']);
						unset($va_item['icon']);
					
						if (!trim($va_item[$vs_label_display_field_name] ?? null)) { $va_item[$vs_label_display_field_name] = $va_item['idno']; }
						if (!trim($va_item[$vs_label_display_field_name] ?? null)) { $va_item[$vs_label_display_field_name] = '???'; }
					
						$va_item['name'] = $va_display_values[$vn_c] ?? null;
						if (!trim($va_item['name'])) { $va_item['name'] = '??? '.$vn_item_id; }
						$va_item['table'] = 'ca_list_items';
					
						// Child count is only valid if has_children is not null
						$va_item['children'] = 0;
						$va_list_items[$vn_item_id] = $va_item;
						$vn_c++;
						
						if (!is_null($vn_max_items_per_page) && ($vn_c > ($vn_max_items_per_page))) { break; }
					}
					
					if (sizeof($va_list_items)) {
						$o_db = new Db();
						$qr_res = $o_db->query("
							SELECT count(*) c, parent_id
							FROM ca_list_items
							WHERE 
								parent_id IN (".join(",", array_keys($va_list_items)).") AND deleted = 0
							GROUP BY parent_id
						");	
						while($qr_res->nextRow()) {
							$va_list_items[$qr_res->get('parent_id')]['children'] = $qr_res->get('c');
						}
					}
				}
			}
		
			$va_list_items_sortable = [];
			foreach($va_list_items as $vn_item_id => $va_item) {
				$va_list_items_sortable[caSortableValue(mb_strtolower(preg_replace('![^A-Za-z0-9]!', '_', caRemoveAccents($va_item['name'])))).'_'.$vn_item_id] = $va_item;
			}
			
			// Sort list of lists alphabetically and case insensitively
			// Items are already sorted using configured sort order
			if(!$vn_id) { ksort($va_list_items_sortable); }
			
			$va_list_items = $va_list_items_sortable;
			$va_list_items['_sortOrder'] = array_keys($va_list_items);

			$va_list_items['_primaryKey'] = $t_item->primaryKey();	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
			$va_list_items['_itemCount'] = ca_list_items::find(['list_id' => $vn_list_id, 'parent_id' => $vn_id], ['returnAs' => 'count']); //sizeof($va_list_items); //$t_list ? $t_list->numItemsInList() : ($qr_res ? $qr_res->numRows() : 0);
		
			$va_level_data[$pn_id] = $va_list_items;
		}
		if (!$this->request->getParameter('init', pInteger)) {
			// only set remember "last viewed" if the load is done interactively
			// if the GetHierarchyLevel() call is part of the initialization of the hierarchy browser
			// then all levels are loaded, sometimes out-of-order; if we record these initialization loads
			// as the 'last viewed' we can end up losing the true 'last viewed' value
			//
			// ... so the hierbrowser passes an extra 'init' parameters set to 1 if the GetHierarchyLevel() call
			// is part of a browser initialization
			Session::setVar($this->ops_table_name.'_'.$ps_bundle.'_browse_last_id', $pn_id);
		}
		
		$this->view->setVar('dontShowSymbols', (bool)$this->request->getParameter('noSymbols', pString));
		$this->view->setVar('list_item_list', $va_level_data);
		
		$this->response->setContentType('application/json');
		return $this->render('list_item_hierarchy_level_json.php');
	}
	# -------------------------------------------------------
}
