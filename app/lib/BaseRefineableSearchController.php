<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseRefineableSearchController.php : 
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
	require_once(__CA_LIB_DIR__."/BaseFindController.php");
	
	class BaseRefineableSearchController extends BaseFindController {
		# -------------------------------------------------------
 		/**
 		 * Browse engine used to wrap searches. The browse "wrapper" provides for "refine search" functionality
 		 */
 		protected $opo_browse;
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if ($this->ops_tablename) {
				if ($va_items_per_page_config = $po_request->config->getList('items_per_page_options_for_'.$this->ops_tablename.'_search')) {
					$this->opa_items_per_page = $va_items_per_page_config;
				}
				if (($vn_items_per_page_default = (int)$po_request->config->get('items_per_page_default_for_'.$this->ops_tablename.'_search')) > 0) {
					$this->opn_items_per_page_default = $vn_items_per_page_default;
				} else {
					$this->opn_items_per_page_default = $this->opa_items_per_page[0];
				}
				
				$this->ops_view_default = null;
				if ($vs_view_default = $po_request->config->get('view_default_for_'.$this->ops_tablename.'_search')) {
					$this->ops_view_default = $vs_view_default;
				}

				if(!is_array($this->opa_sorts)) { $this->opa_sorts = []; }
			}
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Facets() {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$this->opo_browse->loadFacetContent(array('checkAccess' => $va_access_values));
			$this->view->setVar('browse', $this->opo_browse);
			
 			$this->render("Search/ajax_refine_facets_html.php");
 		}
		# -------------------------------------------------------
 		public function getFacet() {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			
 			if ($this->request->getParameter('clear', pInteger)) {
 				$this->opo_browse->removeAllCriteria();
 				$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 				
 				$this->opo_result_context->setSearchExpression($this->opo_browse->getBrowseID());
 				$this->opo_result_context->saveContext();
 			} else {
 				if ($this->request->getParameter('modify', pString)) {
 					$vm_id = $this->request->getParameter('id', pString);
 					$this->opo_browse->removeCriteria($ps_facet_name, array($vm_id));
 					$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 					
 					$this->view->setVar('modify', $vm_id);
 				}
 			}
 			
 			$va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
 			
 			$this->view->setVar('facet', $va_facet); // leave as is for old pawtucket views
 			$this->view->setVar('facet_info', $va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name));
 			$this->view->setVar('facet_name', $ps_facet_name);
 			$this->view->setVar('grouping', $vs_grouping = $this->request->getParameter('grouping', pString));

 			// this should be 'facet' but we don't want to render all old 'ajax_refine_facet_html' views (pawtucket themes) unusable
 			$this->view->setVar('grouped_facet',$this->opo_browse->getFacetWithGroups($ps_facet_name, $va_facet_info["group_mode"], $vs_grouping, array('sort' => 'name', 'checkAccess' => $va_access_values)));
 			
 			// generate type menu and type value list for related authority table facet
 			if ($va_facet_info['type'] === 'authority') {
				$t_model = Datamodel::getInstance($va_facet_info['table']);
				if (method_exists($t_model, "getTypeList")) {
					$this->view->setVar('type_list', $t_model->getTypeList());
				}
				
				$t_rel_types = new ca_relationship_types();
				$this->view->setVar('relationship_type_list', $t_rel_types->getRelationshipInfo($va_facet_info['relationship_table']));
				
				$this->view->setVar('t_item', $t_model);
				$this->view->setVar('t_subject', Datamodel::getInstanceByTableName($this->ops_tablename, true));
			}
			
 			$this->render('Search/ajax_refine_facet_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function getFacetHierarchyLevel() {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			
 			$this->opo_browse->setTypeRestrictions(array($this->opn_type_restriction_id));
 			if(!is_array($va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name))) { return null; }
 			
 			$va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
 			
			$pa_ids = explode(";", $ps_ids = $this->request->getParameter('id', pString));
			if (!sizeof($pa_ids)) { $pa_ids = array(null); }
 			
			$va_level_data = array();
 	
 			if ((($vn_max_items_per_page = $this->request->getParameter('max', pInteger)) < 1) || ($vn_max_items_per_page > 1000)) {
				$vn_max_items_per_page = null;
			}
			
			$t_model = Datamodel::getInstanceByTableName($this->ops_tablename, true);
			
			$o_config = Configuration::load();
			if (!(is_array($va_sorts = $o_config->getList($this->ops_tablename.'_hierarchy_browser_sort_values'))) || !sizeof($va_sorts)) { $va_sorts = array(); }
			foreach($va_sorts as $vn_i => $vs_sort_fld) {
				$va_tmp = explode(".", $vs_sort_fld);
				
				if ($va_tmp[1] == 'preferred_labels') {
					$va_tmp[0] = $vs_label_table_name;
					if (!($va_tmp[1] = $va_tmp[2])) {
						$va_tmp[1] = $vs_label_display_field_name;
					}
					unset($va_tmp[2]);
					
					$va_sorts[$vn_i] = join(".", $va_tmp);
				}
			}
			
			if (!in_array($vs_sort_dir = strtolower($o_config->get($this->ops_tablename.'_hierarchy_browser_sort_direction')), array('asc', 'desc'))) {
				$vs_sort_dir = 'asc';
			}
			
			$va_expanded_facet = array();
			$t_item = new ca_list_items();
 			foreach($va_facet as $vn_id => $va_facet_item) {
 				$va_expanded_facet[$vn_id] = true;
 				$va_ancestors = $t_item->getHierarchyAncestors($vn_id, array('idsOnly' => true));
 				if (is_array($va_ancestors)) {
					foreach($va_ancestors as $vn_ancestor_id) {
						$va_expanded_facet[$vn_ancestor_id] = true;
					}
				}
 			}
 				
 			foreach($pa_ids as $pn_id) {
 				$va_json_data = array('_primaryKey' => 'item_id');
				
				$va_tmp = explode(":", $pn_id);
				$vn_id = $va_tmp[0];
				$vn_start = (int)$va_tmp[1];
				if($vn_start < 0) { $vn_start = 0; }
				
 				switch($va_facet_info['type']) {
					case 'attribute':
						// is it a list attribute?
						$t_element = new ca_metadata_elements();
						if ($t_element->load(array('element_code' => $va_facet_info['element_code']))) {
							if ($t_element->get('datatype') == 3) { // 3=list
								
								$t_list = new ca_lists();
								if (!$vn_id) { 
									$vn_id = $t_list->getRootListItemID($t_element->get('list_id'));
								}
								$t_item = new ca_list_items($vn_id);
								$va_children = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
								$va_child_counts = $t_item->getHierarchyChildCountsForIDs($va_children);
								$qr_res = caMakeSearchResult('ca_list_items', $va_children);
								
								$vs_pk = $t_model->primaryKey();
								
								if ($qr_res) {
									while($qr_res->nextHit()) {
										$vn_parent_id = $qr_res->get('ca_list_items.parent_id');
										$vn_item_id = $qr_res->get('ca_list_items.item_id');
										if (!isset($va_expanded_facet[$vn_item_id])) { continue; }
										
										$va_item = array();
										$va_item['item_id'] = $vn_item_id;
										$va_item['name'] = $qr_res->get('ca_list_items.preferred_labels');
										$va_item['children'] = (isset($va_child_counts[$vn_item_id]) && $va_child_counts[$vn_item_id]) ? $va_child_counts[$vn_item_id] : 0;
										$va_json_data[$vn_item_id] = $va_item;
									}
								}
							}
						}
						break;
					case 'label':
						// label facet
						$va_facet_info['table'] = $this->ops_tablename;
						// fall through to default case
					default:
						if(!$vn_id) {
							$va_hier_ids = $this->opo_browse->getHierarchyIDsForFacet($ps_facet_name, array('checkAccess' => $va_access_values));
							$t_item = Datamodel::getInstanceByTableName($va_facet_info['table']);
							$t_item->load($vn_id);
							$vn_id = $vn_root = $t_item->getHierarchyRootID();
							$va_hierarchy_list = $t_item->getHierarchyList(true);
							
							$vn_last_id = null;
							$vn_c = 0;
							foreach($va_hierarchy_list as $vn_i => $va_item) {
								if (!in_array($vn_i, $va_hier_ids)) { continue; }	// only show hierarchies that have items in browse result
								if ($vn_start <= $vn_c) {
									$va_item['item_id'] = $va_item[$t_item->primaryKey()];
									if (!isset($va_facet[$va_item['item_id']]) && ($vn_root == $va_item['item_id'])) { continue; }
									unset($va_item['parent_id']);
									unset($va_item['label']);
									$va_json_data[$va_item['item_id']] = $va_item;
									$vn_last_id = $va_item['item_id'];
								}
								$vn_c++;
								if (!is_null($vn_max_items_per_page) && ($vn_c >= ($vn_max_items_per_page + $vn_start))) { break; }
							}
							if (sizeof($va_json_data) == 2) {	// if only one hierarchy root (root +  _primaryKey in array) then don't bother showing it
								$vn_id = $vn_last_id;
								unset($va_json_data[$vn_last_id]);
							}
						}
						if ($vn_id) {
							$vn_c = 0;
							foreach($va_facet as $vn_i => $va_item) {
								if ($va_item['parent_id'] == $vn_id) {
									if ($vn_start <= $vn_c) {
										$va_item['item_id'] = $va_item['id'];
										$va_item['name'] = $va_item['label'];
										$va_item['children'] = $va_item['child_count'];
										unset($va_item['label']);
										unset($va_item['child_count']);
										unset($va_item['id']);
										$va_json_data[$va_item['item_id']] = $va_item;
									}									
									$vn_c++;
									if (!is_null($vn_max_items_per_page) && ($vn_c >= ($vn_max_items_per_page + $vn_start))) { break; }
								}
							}
						}
						break;
				}
				$vs_rank_fld = $t_item->getProperty('RANK');
						
				$va_sorted_items = array();
				foreach($va_json_data as $vn_id => $va_node) {
					if(!is_array($va_node)) { continue; }
					$vs_key = preg_replace('![^A-Za-z0-9]!', '_', $va_node['name']);
				
					if (isset($va_node['sort']) && $va_node['sort']) {
						$va_sorted_items[$va_node['sort']][$vs_key] = $va_node;
					} else {
						if ($vs_rank_fld && ($vs_rank = (int)sprintf("%08d", $va_node[$vs_rank_fld]))) {
							$va_sorted_items[$vs_rank][$vs_key] = $va_node;
						} else {
							$va_sorted_items[$vs_key][$vs_key] = $va_node;
						}
					}
				}
				ksort($va_sorted_items);
				if ($vs_sort_dir == 'desc') { $va_sorted_items = array_reverse($va_sorted_items); }
				$va_json_data = array();
				
				$va_sorted_items = array_slice($va_sorted_items, $vn_start, $vn_max_items_per_page);
				
				foreach($va_sorted_items as $vs_k => $va_v) {
					ksort($va_v);
					if ($vs_sort_dir == 'desc') { $va_v = array_reverse($va_v); }
					$va_json_data = array_merge($va_json_data, $va_v);
				}
				$va_json_data['_itemCount'] = sizeof($va_json_data);
				$va_json_data['_sortOrder'] = array_keys($va_json_data);
				$va_json_data['_primaryKey'] = $t_model->primaryKey();	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
 				
 				
				$va_level_data[$pn_id] = $va_json_data;
			}
 			if (!trim($this->request->getParameter('init', pString))) {
				$this->opo_result_context->setParameter($ps_facet_name.'_browse_last_id', $pn_id);
				$this->opo_result_context->saveContext();
			}
 			
 			$this->view->setVar('facet_list', $va_level_data);
 		
 			return $this->render('Browse/facet_hierarchy_level_json.php');
 		}
 		# -------------------------------------------------------
 		public function addCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->addCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			
 			//$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function modifyCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('mod_id', pString)));
 			$this->opo_browse->addCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			
 			//$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function removeCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			
 			//$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Callbacks:
 		 * 		hookAfterClearCriteria() is called after clearing criteria. The first parameter is the BrowseEngine object containing the search.
 		 */
 		public function clearCriteria() {
 			if(is_array($va_criteria = $this->opo_browse->getCriteria())) {
				foreach($va_criteria as $vs_facet_name => $va_facet_info) {
					if ($vs_facet_name === '_search') { continue; }		// never delete base search
					$this->opo_browse->removeCriteria($vs_facet_name, array_keys($va_facet_info));
				}
			}
 			if (method_exists($this, "hookAfterClearCriteria")) {
				$this->hookAfterClearCriteria($this->opo_browse);
			}
 			//$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
	}
?>