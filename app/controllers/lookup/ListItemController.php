<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/ListItemController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2026 Whirl-i-Gig
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
	public function Get($additional_query_params=null, $options=null) {
		if ($list = $this->request->getParameter('list', pString)) {
			if(!is_array($additional_query_params)) { $additional_query_params = array(); }
			
			$additional_query_params[] = "ca_lists.list_code:{$list}";
		} else {
			if ($lists = $this->request->getParameter('lists', pString)) {
				if(!is_array($additional_query_params)) { $additional_query_params = array(); }
				
				$lists = explode(";", $lists);
				
				$options['hier_id'] = caGetListID($lists[0]);
				
				$tmp = array();
				$options['filters'] = array();
				foreach($lists as $list) {
					if ($list = trim($list)) {
						$tmp[] = "'".preg_replace("![\"']+!", "", $list)."'";
					}
				}
				$options['filters'][] = array("ca_list_items.list_id", "IN", join(",", $tmp));
			}
		}
		return parent::Get($additional_query_params, $options);
	}
	# -------------------------------------------------------
	/**
	 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
	 * Returned data is JSON format
	 */
	public function GetHierarchyLevel() {
		$bundle = (string)$this->request->getParameter('bundle', pString);
		$ids = explode(";", $ids = $this->request->getParameter('id', pString));
		if (!sizeof($ids)) { $ids = array(null); }
		
		$t_item = $this->opo_item_instance;
		
		$template = $t_item->getAppConfig()->get('ca_list_items_hierarchy_browser_display_settings');
		
		if ($lists = $this->request->getParameter('lists', pString)) {
			$lists = explode(";", $lists);
		}
		if(!is_array($lists)) { $lists = []; }
		
		$max_items_per_page = $this->request->getParameter('max', pInteger);
		if (($max_items_per_page > 1000) || ($max_items_per_page <= 0)) {
			$max_items_per_page = 500;
		}
		
		$list_id = null;
		$level_data = array();
		
		foreach($ids as $pn_id) {
			$tmp = explode(":", $pn_id);
			$id = $tmp[0] ?? null;
			$start = (int)($tmp[1] ?? 0);
			if($start < 0) { $start = 0; }
			
			if (!$id && method_exists($t_item, "getHierarchyList")) { 
				if (!($pn_list_id = $this->request->getParameter('list_id', pInteger))) {
					// no id so by default return list of available hierarchies
					$list_items = $t_item->getHierarchyList();
					
					if (sizeof($lists)) {
						// filter out lists that weren't specified
						foreach($list_items as $item_list_id => $list) {
							if (!in_array($item_list_id, $lists) && !in_array($list['list_code'] ?? null, $lists)) {
								unset($list_items[$item_list_id]);
							}
						}
					} else {
						if ($this->request->getParameter('voc', pInteger)) {
							// Only show vocabularies
							foreach($list_items as $item_list_id => $list) {
								if (!($list['use_as_vocabulary'] ?? false)) {
									unset($list_items[$item_list_id]);
								}
							}
						}
					}
				}
			} else {
				if ($t_item->load($id)) {		// id is the id of the parent for the level we're going to return
					$list_id = $t_item->get('list_id');
					$t_list = new ca_lists($list_id);
				
					$label_table_name = $this->opo_item_instance->getLabelTableName();
					$label_display_field_name = $this->opo_item_instance->getLabelDisplayField();
					
					$list_items = $t_list->getItemsForList($list_id, array('returnHierarchyLevels' => false, 'item_id' => $id, 'extractValuesByUserLocale' => true, 'sort' => $t_list->get('sort_type'), 'directChildrenOnly' => true, 'limit' => $max_items_per_page, 'start' => $start));
		
					// output
					$display_values = caProcessTemplateForIDs($template, 'ca_list_items', array_keys($list_items ?? []), array('requireLinkTags' => true, 'returnAsArray' => true, 'indexWithIDs' => true));
					
					$c = 0;
					foreach($list_items as $item_id => $item) {
						unset($item['description']);
						unset($item['icon']);
					
						if (!trim($item[$label_display_field_name] ?? null)) { $item[$label_display_field_name] = $item['idno']; }
						if (!trim($item[$label_display_field_name] ?? null)) { $item[$label_display_field_name] = '???'; }
					
						$item['name'] = $display_values[$item_id] ?? null;
						if (!trim($item['name'])) { $item['name'] = '??? '.$item_id; }
						$item['table'] = 'ca_list_items';
					
						// Child count is only valid if has_children is not null
						$item['children'] = 0;
						$list_items[$item_id] = $item;
						$c++;
						
						if (!is_null($max_items_per_page) && ($c > ($max_items_per_page))) { break; }
					}
					
					if (is_array($list_items) && sizeof($list_items)) {
						$o_db = new Db();
						$qr_res = $o_db->query("
							SELECT count(*) c, parent_id
							FROM ca_list_items
							WHERE 
								parent_id IN (".join(",", array_keys($list_items)).") AND deleted = 0
							GROUP BY parent_id
						");	
						while($qr_res->nextRow()) {
							$list_items[$qr_res->get('parent_id')]['children'] = $qr_res->get('c');
						}
					}
				}
			}
		
			$list_items_sortable = [];
			foreach($list_items ?? [] as $item_id => $item) {
				$list_items_sortable[caSortableValue(mb_strtolower(preg_replace('![^A-Za-z0-9]!', '_', caRemoveAccents($item['name'])))).'_'.$item_id] = $item;
			}
			
			// Sort list of lists alphabetically and case insensitively
			// Items are already sorted using configured sort order
			if(!$id) { ksort($list_items_sortable); }
			
			$list_items = $list_items_sortable;
			$list_items['_sortOrder'] = array_keys($list_items);

			$list_items['_primaryKey'] = $t_item->primaryKey();	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
			$list_items['_itemCount'] = ca_list_items::find(['list_id' => $list_id, 'parent_id' => $id], ['returnAs' => 'count']); //sizeof($list_items); //$t_list ? $t_list->numItemsInList() : ($qr_res ? $qr_res->numRows() : 0);
		
			$level_data[$pn_id] = $list_items;
		}
		if (!$this->request->getParameter('init', pInteger)) {
			// only set remember "last viewed" if the load is done interactively
			// if the GetHierarchyLevel() call is part of the initialization of the hierarchy browser
			// then all levels are loaded, sometimes out-of-order; if we record these initialization loads
			// as the 'last viewed' we can end up losing the true 'last viewed' value
			//
			// ... so the hierbrowser passes an extra 'init' parameters set to 1 if the GetHierarchyLevel() call
			// is part of a browser initialization
			Session::setVar($this->ops_table_name.'_'.$bundle.'_browse_last_id', $pn_id);
		}
		
		$this->view->setVar('dontShowSymbols', (bool)$this->request->getParameter('noSymbols', pString));
		$this->view->setVar('list_item_list', $level_data);
		
		$this->response->setContentType('application/json');
		return $this->render('list_item_hierarchy_level_json.php');
	}
	# -------------------------------------------------------
}
