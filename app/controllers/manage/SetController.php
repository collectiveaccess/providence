<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SetController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Controller/ActionController.php");
require_once(__CA_LIB_DIR__."/ResultContext.php");

class SetController extends ActionController {
	# -------------------------------------------------------
	protected $opn_list_set_type_id;
	protected $ops_set_type_singular;
	protected $ops_set_type_plural;
	protected $opn_items_per_page;
	protected $opb_criteria_has_changed;
	protected $opa_sorts;
	
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		$o_result_context = new ResultContext($this->request, 'ca_sets', 'basic_search');

		$this->opn_items_per_page = ((int) $this->request->config->get('items_per_page_default_for_ca_sets_search') ?: 20);
		$this->view->setVar('items_per_page', $this->opn_items_per_page);
		
		$this->_setType((int)$this->request->getParameter('list_set_type_id', pInteger));
		
		$this->opa_sorts = array("name", "set_content_type", "access", "lname", "item_count", "set_type", "access", "status", "rank", "created");
	}
	# -------------------------------------------------------
	private function _setType(?int $type_id) {
		$o_result_context = new ResultContext($this->request, 'ca_sets', 'basic_search');

		$this->opn_list_set_type_id = $type_id;
		if (strlen($this->opn_list_set_type_id) > 0) {
			if((int)$o_result_context->getParameter('set_type_id') != (int)$this->opn_list_set_type_id){
				$this->opb_criteria_has_changed = true;
			}
			if((int)$this->opn_list_set_type_id < 0){
				# --- pass -1 to clear the set_type_id
				$this->opn_list_set_type_id = null;
				$o_result_context->setParameter('list_set_type_id', '');
			}else{
				$o_result_context->setParameter('list_set_type_id', (int)$this->opn_list_set_type_id);
			}
			$o_result_context->saveContext();
		} else {
			$this->opn_list_set_type_id = (int)$o_result_context->getParameter('list_set_type_id');
		}
		$this->ops_set_type_singular = _t("set");
		$this->ops_set_type_plural = _t("sets");
		if($this->opn_list_set_type_id){
			$t_list = new ca_lists();
			$this->ops_set_type_singular = $t_list->getItemForDisplayByItemID($this->opn_list_set_type_id);
			$this->ops_set_type_plural = $t_list->getItemForDisplayByItemID($this->opn_list_set_type_id, ['return' => 'plural']);
		}
		$this->view->setVar('list_set_type_id', $this->opn_list_set_type_id);
		$this->view->setVar('type_name_singular', $this->ops_set_type_singular);
		$this->view->setVar('type_name_plural', $this->ops_set_type_plural);
	}
	# -------------------------------------------------------
	public function ListSets() {
		AssetLoadManager::register('tableList');
		$config = Configuration::load();
		
		$is_inventory = (bool)$this->view->getVar('is_inventory');
		
		if($is_inventory && !$this->request->user->canDoAction('can_view_inventories')) {
			throw new ApplicationException(_t('No access to inventories'));
		} elseif(!$is_inventory && !$this->request->user->canDoAction('can_view_sets')) {
			throw new ApplicationException(_t('No access to sets'));
		}	
		
		$o_result_context = new ResultContext($this->request, 'ca_sets', $is_inventory ? 'inventory' : 'basic_search');
		$t_set = new ca_sets();
		
		// get content types for sets
		if($is_inventory) {
			$table_list = [];
			if(is_array($ctables = $config->get('inventory_types'))) {
				foreach($ctables as $t) {
					$table_list[Datamodel::getTableProperty($t, 'NAME_PLURAL')] = Datamodel::getTableNum($t);	
				}
			} 
		} else {
			$table_list = caFilterTableList($t_set->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST'));
		}
		
		$this->view->setVar('table_list', $table_list);
		$this->view->setVar('t_set', $t_set);
		
		$user_id = !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null;            
   
		$o_result_context->setItemsPerPage($this->opn_items_per_page);
		$this->view->setVar('page', $page_num = $o_result_context->getCurrentResultsPageNumber());
	
		if (!$page_num || $this->opb_criteria_has_changed) {
			$page_num = 1;
			$o_result_context->setCurrentResultsPageNumber($page_num);
		}
	
		if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction($is_inventory ? 'can_administrate_inventories' : 'can_administrate_sets')) {
			$ps_mode = $this->request->getParameter('mode', pString);
			if (strlen($ps_mode) > 0) {
				$mode = (int)$ps_mode;
				$o_result_context->setParameter('set_display_mode', $mode);
				$this->opb_criteria_has_changed = true;
			} else {
				$mode = (int)$o_result_context->getParameter('set_display_mode');
			}
		
			switch($mode) {
				case 0:
				default:
					$set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $user_id, 'access' => __CA_SET_EDIT_ACCESS__, 'setType' => $this->opn_list_set_type_id)), null, null, array());
					break;
				case 1:
					$set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $user_id, 'allUsers' => true, 'setType' => $this->opn_list_set_type_id)), null, null, array());
					break;
				case 2:
					$set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $user_id, 'publicUsers' => true, 'setType' => $this->opn_list_set_type_id)), null, null, array());
					break;
			}
		} else {
			$set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $user_id, 'access' => __CA_SET_EDIT_ACCESS__, 'setType' => $this->opn_list_set_type_id)), null, null, array());
		}
		if (!($sort 	= $o_result_context->getCurrentSort()) || (!in_array($sort, $this->opa_sorts))) { 
			$sort = 'created'; 
			$sort_direction = 'desc';
		} else {
			$sort_direction = $o_result_context->getCurrentSortDirection();
		}
		if($vb_sort_has_changed = $o_result_context->sortHasChanged()){
			$this->opb_criteria_has_changed = true;
		}
		$this->view->setVar('current_sort', $sort);
		$this->view->setVar('current_sort_direction', $sort_direction);	
	
		$set_list_sorted = array();
		$set_ids = array();
		if ($set_list) {
			foreach ($set_list as $id => $set) {
				$set_list[$id]['can_delete'] = $this->UserCanDeleteSet($set);
			
				# --- order the set
				if(!in_array($sort, array("status", "access"))){
					$set_list_sorted[$set[$sort]." ".$id] = $set;
				}else{
					$set_list_sorted[$t_set->getChoiceListValue($sort, $set[$sort])." ".$id] = $set;
				}
				$set_ids[] = $id;
			}
		}
		if($sort != "name"){
			ksort($set_list_sorted);
		}
		if($sort_direction == "desc"){
			$set_list_sorted = array_reverse($set_list_sorted, true);
		}
		$this->view->setVar('mode', $mode);
	
		$this->view->setVar('num_hits', $num_hits = sizeof($set_list_sorted));
		$this->view->setVar('num_pages', $num_pages = ceil($num_hits/$this->opn_items_per_page));
		if ($page_num > $num_pages) {
			$page_num = 1;
			$o_result_context->setCurrentResultsPageNumber($page_num);
		}
	
		# --- slice array to send current page
		if($num_pages > 1){
			$start = $this->opn_items_per_page * ($page_num - 1);
			$set_list_sorted = array_slice($set_list_sorted, $start, $this->opn_items_per_page, true);
		}
		$this->view->setVar('set_list', $set_list_sorted);
		
		$o_result_context->setAsLastFind();
		$o_result_context->setResultList($set_ids);
		$o_result_context->saveContext();
		
		$this->render('set_list_html.php');
	}
	# -------------------------------------------------------
	public function ListInventories() {
		if(!(bool)$this->request->config->get('enable_inventories')) {
			throw new ApplicationException(_t('Inventories disabled'));
		}
		
		$inventory_type_id = caGetListItemID('set_types', 'inventory');
		
		$this->view->setVar('is_inventory', true);
		
		$this->_setType($inventory_type_id);
		return $this->ListSets();
	}
	# -------------------------------------------------------
	public function ListSetsByUser() {
		AssetLoadManager::register('tableList');
		$o_result_context = new ResultContext($this->request, 'ca_sets', 'by_user');
		
		$t_set = new ca_sets();
		// get content types for sets
		$this->view->setVar('table_list', caFilterTableList($t_set->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST')));
		$this->view->setVar('t_set', $t_set);
		
		$this->view->setVar('page', $vn_page_num = $o_result_context->getCurrentResultsPageNumber());
		
		$vn_user_id = !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null;            

		$o_result_context->setItemsPerPage($this->opn_items_per_page);
	
		if (!$vn_page_num || $this->opb_criteria_has_changed) {
			$vn_page_num = 1;
			$o_result_context->setCurrentResultsPageNumber($vn_page_num);
		}
	

		if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction('can_administrate_sets')) {
			$ps_mode = $this->request->getParameter('mode', pString);
			if (strlen($ps_mode) > 0) {
				$pn_mode = (int)$ps_mode;
				$o_result_context->setParameter('set_display_mode', $pn_mode);
				$this->opb_criteria_has_changed = true;
			} else {
				$pn_mode = (int)$o_result_context->getParameter('set_display_mode');
			}
		
			switch($pn_mode) {
				case 0:
				default:
					$va_set_list = $t_set->getSets(['user_id' => $vn_user_id, 'access' => __CA_SET_EDIT_ACCESS__, 'setType' => $this->opn_list_set_type_id, 'byUser' => true]);
					break;
				case 1:
					$va_set_list = $t_set->getSets(['user_id' => $vn_user_id, 'allUsers' => true, 'setType' => $this->opn_list_set_type_id, 'byUser' => true]);
					break;
				case 2:
					$va_set_list = $t_set->getSets(['user_id' => $vn_user_id, 'publicUsers' => true, 'setType' => $this->opn_list_set_type_id, 'byUser' => true]);
					break;
			}
		} else {
			$va_set_list = $t_set->getSets(['user_id' => $vn_user_id, 'access' => __CA_SET_EDIT_ACCESS__, 'setType' => $this->opn_list_set_type_id, 'byUser' => true]);
		}
	
		$va_set_ids = array_reduce($va_set_list, function($a, $v) { return array_merge($a, $z=array_map(function($x) { return $x['set_id']; }, $v['sets'])); }, []);
	
		$this->view->setVar('num_hits', $vn_num_hits = sizeof($va_set_list));
		$this->view->setVar('num_pages', $vn_num_pages = ceil($vn_num_hits/$this->opn_items_per_page));
		
		# --- slice array to send current page
		if($vn_num_pages > 1){
			$vn_start = $this->opn_items_per_page * ($vn_page_num - 1);
			if ($vn_start >= sizeof($va_set_list)) { 
				$vn_page_num = 1; $vn_start = 0; 
				$this->view->setVar('page', $vn_page_num);
				$o_result_context->setCurrentResultsPageNumber($vn_page_num);
			}
			$va_set_list = array_slice($va_set_list, $vn_start, $this->opn_items_per_page, true);
		}
		
	 
		$this->view->setVar('set_list', $va_set_list);
		$this->view->setVar('type_name_singular', _t('user'));
		$this->view->setVar('type_name_plural', _t('users'));
		
		$o_result_context->setAsLastFind();
		$o_result_context->setResultList($va_set_ids);
		$o_result_context->saveContext();
		
		$this->render('set_list_by_user_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Delete() {
		if ($vb_confirm = ($this->request->getParameter('confirm', pInteger) == 1) ? true : false) {
			if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
				$this->ListSets();
				return;
			}
			if (!is_array($pa_set_ids = $this->request->getParameter('row_id', pArray))) {
				$this->notification->addNotification(_t("At least two sets must be selected"), __NOTIFICATION_TYPE_ERROR__);
				$this->ListSets();
				return; 				
			}
			$t_set = new ca_sets();
			
			$vn_user_id = $this->request->getUserID();
			$pa_set_ids = array_filter($pa_set_ids, function($vn_set_id) use ($t_set, $vn_user_id) {
				return $t_set->haveAccessToSet($vn_user_id, __CA_SET_EDIT_ACCESS__, $vn_set_id);
			});
			
			if (sizeof($pa_set_ids) > 0) {
				$delete_count = 0;
				foreach($pa_set_ids as $set_id) {
					if ($t_set->load($set_id)) {
						if (!$t_set->delete(true)) {
							if ($delete_count > 0) {
								$this->notification->addNotification(($delete_count === 1) ? _t("Deleted %1 set", $delete_count) : _t("Deleted %1 sets", $delete_count), __NOTIFICATION_TYPE_INFO__);
							}
							$this->notification->addNotification(_t("Could not delete set %1: %2", $t_set->get('set_code'), join("; ", $t_set->getErrors())), __NOTIFICATION_TYPE_ERROR__);
							$this->ListSets();
							return;
						}
						$delete_count++;
					}
				}	
				$this->notification->addNotification(($delete_count === 1) ? _t("Deleted %1 set", $delete_count) : _t("Deleted %1 sets", $delete_count), __NOTIFICATION_TYPE_INFO__);
			} else {
				$this->notification->addNotification(_t("You do not have access to the selected sets"), __NOTIFICATION_TYPE_ERROR__);
			}
		}
		$this->ListSets();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Algebra() {
		global $g_ui_locale_id;
		
		$set_mode = $this->request->getParameter('algebra_set_mode', pString);	
		$set_name = $this->request->getParameter('algebra_set_name', pString);
		$ps_op = $this->request->getParameter('algebra_set_operation', pString);
		if (!is_array($pa_set_ids = $this->request->getParameter('algebra_set_id', pArray))) {
			$this->notification->addNotification(_t("At least two sets must be selected"), __NOTIFICATION_TYPE_ERROR__);
			$this->ListSets();
			return; 				
		}
		
		$t_set = new ca_sets();
		$vn_user_id = $this->request->getUserID();
		$pa_set_ids = array_filter($pa_set_ids, function($vn_set_id) use ($t_set, $vn_user_id) {
			return $t_set->haveAccessToSet($vn_user_id, __CA_SET_READ_ACCESS__, $vn_set_id);
		});
		
		switch($set_mode) {
			case 'DELETE':
				$this->view->setVar('sets', $pa_set_ids);
				$this->render('set_delete_html.php');
				return;
				break;
			case 'CREATE':
			default:
				if (sizeof($pa_set_ids) > 1) {
					$va_set_list = [];
					$vn_table_num = $vn_type_id = null;
					switch($ps_op) {
						case 'UNION':
							foreach($pa_set_ids as $vn_set_id) {
								$t_set->load($vn_set_id);
								if (!$vn_table_num) { $vn_table_num = $t_set->get('table_num'); $vn_type_id = $t_set->get('type_id'); }
								$va_set_list = array_merge($va_set_list, array_keys($t_set->getItems(['returnRowIdsOnly' => true, 'user_id' => $vn_user_id])));
							}
							$va_set_list = array_unique($va_set_list);
							break;
						case 'INTERSECTION':
							foreach($pa_set_ids as $i => $vn_set_id) {
								$t_set->load($vn_set_id);
								if (!$vn_table_num) { $vn_table_num = $t_set->get('table_num'); $vn_type_id = $t_set->get('type_id'); }
						
								$va_items = array_keys($t_set->getItems(['returnRowIdsOnly' => true, 'user_id' => $vn_user_id]));
								$va_set_list = ($i == 0) ? $va_items : array_intersect($va_set_list, $va_items);
							}
							$va_set_list = array_unique($va_set_list);
							break;
						case 'DIFFERENCE':
							$va_sets = [];
							foreach($pa_set_ids as $i => $vn_set_id) {
								$t_set->load($vn_set_id);
								if (!$vn_table_num) { $vn_table_num = $t_set->get('table_num'); $vn_type_id = $t_set->get('type_id'); }
								$va_set_list = array_merge($va_set_list, $va_sets[$i] = array_keys($t_set->getItems(['returnRowIdsOnly' => true, 'user_id' => $vn_user_id])));
							}
							$va_set_list = array_unique($va_set_list);
					
							$va_acc = [];
							foreach($pa_set_ids as $i => $vn_set_id) {
								$va_acc = ($i == 0) ? $va_sets[$i] : array_intersect($va_acc, $va_sets[$i]);
							}
							$va_set_list = array_diff($va_set_list, $va_acc);
							break;
						default:
							$this->notification->addNotification(_t("Invalid operation"), __NOTIFICATION_TYPE_ERROR__);
							break;
					}
		
					if (!$set_name) { 
						$t_set->load($pa_set_ids[0]);
						$set_name = _t("%1 et. al.", $t_set->get('set_code')); 
					}
			
					if (sizeof($va_set_list) > 0) {
						// create new set
						$t_set->clear();
						$t_set->setMode(ACCESS_WRITE);
						$t_set->set('set_code', $set_name);
						$t_set->set('table_num', $vn_table_num);
						$t_set->set('user_id', $vn_user_id);
						$t_set->set('type_id', $vn_type_id);
						$t_set->insert();
						if ($t_set->numErrors() > 0) {
							$this->notification->addNotification(_t("Could not create new set: %1", join("; ", $t_set->getErrors()), __NOTIFICATION_TYPE_ERROR__));
						} else {
							$t_set->addLabel(['name' => $set_name], $g_ui_locale_id, null, true);
							if ($t_set->numErrors() > 0) {
								$this->notification->addNotification(_t("Could not add label to new set: %1", join("; ", $t_set->getErrors()), __NOTIFICATION_TYPE_ERROR__));
							} elseif (!$t_set->addItems($va_set_list)) {
								$this->notification->addNotification(_t("Could not add items to new set: %1", join("; ", $t_set->getErrors()), __NOTIFICATION_TYPE_ERROR__));
							} else {
								$this->notification->addNotification(_t("Created new set <em>%1</em>", $set_name), __NOTIFICATION_TYPE_INFO__);
							}
						}
					} else {
						$this->notification->addNotification(_t("Set was not created because it has no contents"), __NOTIFICATION_TYPE_WARNING__);
					}
				} else {
					$this->notification->addNotification(_t("At least two sets must be selected"), __NOTIFICATION_TYPE_ERROR__);
				}
				break;
		}
		
		$this->ListSets();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function UserCanDeleteSet($set) {
		if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction($is_inventory ? 'can_administrate_inventories' : 'can_administrate_sets')) {
			return true;
		}
		$is_inventory = ($this->request->getAction() == 'ListInventories');
		$can_delete = false;
		// If users can delete all sets, show delete button
		if ($this->request->user->canDoAction($is_inventory ? 'can_delete_inventories' : 'can_delete_sets')) {
			$can_delete = true;
		}
		
		// If users can delete own sets, and this set belongs to them, show Delete button
		if ($this->request->user->canDoAction($is_inventory ? 'can_delete_own_inventories' : 'can_delete_own_sets')) {
			if (($set['user_id'] ?? null) == $this->request->getUserID()) {
				$can_delete = true;
			}
		}
		return $can_delete;
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function Info() {
		$t_set = new ca_sets($set_id = $this->request->getParameter('set_id', pInteger));
		
		$user_id = !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null;
		
		$is_inventory = ($this->request->getAction() == 'ListInventories');
		$this->view->setVar('is_inventory', $is_inventory);$o_result_context = new ResultContext($this->request, 'ca_sets', $is_inventory ? 'inventory' : 'basic_search');
		
		if($is_inventory) {
			$mode = '';
			$set_stats = ['mine' => [], 'user' => [], 'public' => []];
			$this->view->setVar('type_name_singular', _t('Inventory'));
			$this->view->setVar('type_name_plural', _t('Inventories'));
			
			$this->view->setVar('display_modes', [
				_t('Available to you') => 0,
				_t('By other users') => 1
			]);
		} else {
			$set_stats = array('mine' => caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__, 'setType' => $this->opn_list_set_type_id)), null, null, array()));
			if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction('can_administrate_sets')) {
				$set_stats['user'] = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $user_id, 'allUsers' => true, 'setType' => $this->opn_list_set_type_id)), null, null, array());
				$set_stats['public'] = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $user_id, 'publicUsers' => true, 'setType' => $this->opn_list_set_type_id)), null, null, array());
			}
			
			$this->view->setVar('display_modes', [
				_t('Available to you') => 0,
				_t('By other users') => 1,
				_t('By the public') => 2
			]);
		}
		$mode = (int)$o_result_context->getParameter('set_display_mode');
		
		$this->view->setVar('mode', $mode);
		$this->view->setVar('sets', $set_stats);
		
		return $this->render('widget_set_info_html.php', true);
	}
	# -------------------------------------------------------
	# Navigation (menu bar)
	# -------------------------------------------------------
	/**
	 * Returns navigation fragment for types of sets. Used to generate dynamic type menus
	 * from database by AppNavigation class.
	 *
	 * @param array $pa_params Array of parameters used to generate menu
	 * @return array List of types with subtypes ready for inclusion in a menu spec
	 */
	public function _genTypeNav($pa_params) {
		$t_subject = new ca_sets();

		$t_list = new ca_lists();
		$t_list->load(array('list_code' => $t_subject->getTypeListCode()));

		$t_list_item = new ca_list_items();
		$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
		$va_hier = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());

		$vn_sort_type = $t_list->get('default_sort');

		$va_restrict_to_types = null;
		if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
			$va_restrict_to_types = caGetTypeRestrictionsForUser('ca_sets', array('access' => __CA_BUNDLE_ACCESS_EDIT__));
		}

		$va_types = array();
		if (is_array($va_hier)) {

			$va_types_by_parent_id = array();
			$vn_root_id = $t_list->getRootItemIDForList($t_subject->getTypeListCode());

			foreach($va_hier as $vn_item_id => $va_item) {
				if($va_item['settings']) {
					$va_settings = caUnserializeForDatabase($va_item['settings']);
					if(is_array($va_settings) && isset($va_settings['render_in_new_menu']) && !((bool) $va_settings['render_in_new_menu'])) {
						unset($va_hier[$vn_item_id]);
						continue;
					}
				}
				if ($vn_item_id == $vn_root_id) { continue; } // skip root
				$va_types_by_parent_id[$va_item['parent_id']][] = $va_item;
			}
			foreach($va_hier as $vn_item_id => $va_item) {
				if (is_array($va_restrict_to_types) && !in_array($vn_item_id, $va_restrict_to_types)) { continue; }
				if ($va_item['parent_id'] != $vn_root_id) { continue; }
				
				if(isset($pa_params['options']['exclude']) && is_array($pa_params['options']['exclude']) && sizeof($pa_params['options']['exclude'])) {
					if(in_array($va_item['idno'], $pa_params['options']['exclude'], true)) { continue; }
				}
				
				// does this item have sub-items?
				$va_subtypes = array();
				if (
					!(bool)$this->getRequest()->config->get('ca_sets_navigation_new_menu_shows_top_level_types_only')
					&&
					!(bool)$this->getRequest()->config->get('ca_sets_enforce_strict_type_hierarchy')
				) {
					if (isset($va_item['item_id']) && isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
						$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id, $vn_sort_type, $va_restrict_to_types);
					}
				}

				switch($vn_sort_type) {
					case 0:			// label
					default:
						$vs_key = $va_item['name_plural'];
						break;
					case 1:			// rank
						$vs_key = sprintf("%08d", (int)$va_item['rank']);
						break;
					case 2:			// value
						$vs_key = $va_item['item_value'];
						break;
					case 3:			// identifier
						$vs_key = $va_item['idno_sort'];
						break;
				}
				$va_types[$vs_key][] = array(
					'displayName' => $va_item['name_plural'],
					'parameters' => array(
						'list_set_type_id' => $va_item['item_id']
					),
					'is_enabled' => $va_item['is_enabled'],
					'navigation' => $va_subtypes
				);
			}
			ksort($va_types);
		}
		
		$va_types_proc = array();
		if(is_array($pa_params) && $pa_params["parameters"]["parameter:showAllLink"]){
			# --- add all option to the top
			$va_types_proc[] = array(
						'displayName' => _t("All sets"),
						'parameters' => array(
							'list_set_type_id' => -1
						),
						'is_enabled' => 1
					);
		}
		foreach($va_types as $vs_sort_key => $va_items) {
			foreach($va_items as $vn_i => $va_item) {
				$va_types_proc[] = $va_item;
			}
		}
		
		// Add "sets by user" navigation
		$va_types_proc[] = array(
			"displayName" => "<div class='sf-spacer'><!-- empty --></div>"
		);
		$va_types_proc[] = array(
			'displayName' => _t("Sets by user"),
			'action' => 'ListSetsByUser',
			"default" => ['module' => 'manage', 'controller' => 'Set', 'action' => 'ListSetsByUser'],
			'parameters' => [],
			'is_enabled' => 1
		);

		return $va_types_proc;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns navigation fragment for subtypes of a given primary item type (Eg. ca_objects). Used to generate dynamic type menus
	 * from database by AppNavigation class. Called via _genTypeNav(), which is in turn called by AppNavigation.
	 *
	 * @param array $pa_subtypes Array of subtypes
	 * @param array $pa_types_by_parent_id Array of subtypes organized by parent
	 * @param int $pn_sort_type Integer code indicating how to sort types in the menu
	 * @return array List of subtypes ready for inclusion in a menu spec
	 */
	private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id, $pn_sort_type, $pa_restrict_to_types=null) {
		$va_subtypes = array();
		foreach($pa_subtypes as $vn_i => $va_type) {
			if (is_array($pa_restrict_to_types) && !in_array($va_type['item_id'], $pa_restrict_to_types)) { continue; }
			if (isset($pa_types_by_parent_id[$va_type['item_id']]) && is_array($pa_types_by_parent_id[$va_type['item_id']])) {
				$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id, $pn_sort_type, $pa_restrict_to_types);
			} else {
				$va_subsubtypes = array();
			}

			switch($pn_sort_type) {
				case 0:			// label
				default:
					$vs_key = $va_type['name_plural'];
					break;
				case 1:			// rank
					$vs_key = sprintf("%08d", (int)$va_type['rank']);
					break;
				case 2:			// value
					$vs_key = $va_type['item_value'];
					break;
				case 3:			// identifier
					$vs_key = $va_type['idno_sort'];
					break;
			}

			$va_subtypes[$vs_key][$va_type['item_id']] = array(
				'displayName' => $va_type['name_plural'],
				'parameters' => array(
					'list_set_type_id' => $va_type['item_id']
				),
				'is_enabled' => $va_type['is_enabled'],
				'navigation' => $va_subsubtypes
			);
		}

		ksort($va_subtypes);
		$va_subtypes_proc = array();

		foreach($va_subtypes as $vs_sort_key => $va_type) {
			foreach($va_type as $vn_item_id => $va_item) {
				if (is_array($pa_restrict_to_types) && !in_array($vn_item_id, $pa_restrict_to_types)) { continue; }
				$va_subtypes_proc[$vn_item_id] = $va_item;
			}
		}


		return $va_subtypes_proc;
	}
	# ------------------------------------------------------------------
}
