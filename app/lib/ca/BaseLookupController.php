<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseSearchController.php : base controller for search interface
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2014 Whirl-i-Gig
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
 	
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
 	
 	class BaseLookupController extends ActionController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = false;
 		protected $ops_table_name = '';
 		protected $ops_name_singular = '';
 		protected $ops_search_class = '';
 		protected $opo_item_instance;
 		
 		/**
 		 * @property $opa_filtera Criteria to filter list Get() return with; array keys are <tablename>.<fieldname> 
 		 * bundle specs; array values are *array* lists of values. If an item is not equal to a value in the array it will not be 
 		 * returned. Leave set to null or empty array if you don't want to filter.
 		 */
 		protected $opa_filters = array(); 
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
			if ($this->ops_search_class) { require_once(__CA_LIB_DIR__."/ca/Search/".$this->ops_search_class.".php"); }
			require_once(__CA_MODELS_DIR__."/".$this->ops_table_name.".php");
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_item_instance = new $this->ops_table_name();
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			header("Content-type: application/json");
			
			if (!$this->ops_search_class) { return null; }
			$ps_query = $this->request->getParameter('term', pString); 
			
			$pb_exact = $this->request->getParameter('exact', pInteger);
			$ps_exclude = $this->request->getParameter('exclude', pString);
			$va_excludes = explode(";", $ps_exclude);
			$ps_type = $this->request->getParameter('type', pString);
			$ps_types = $this->request->getParameter('types', pString);
			$pb_no_subtypes = (bool)$this->request->getParameter('noSubtypes', pInteger);
			$pb_quickadd = (bool)$this->request->getParameter('quickadd', pInteger);
			$pb_no_inline = (bool)$this->request->getParameter('noInline', pInteger);
			
			if (!($pn_limit = $this->request->getParameter('limit', pInteger))) { $pn_limit = 100; }
			$va_items = array();
			if (($vn_str_len = mb_strlen($ps_query)) > 0) {
				if ($vn_str_len < 3) { $pb_exact = true; }		// force short strings to be an exact match (using a very short string as a stem would perform badly and return too many matches in most cases)
				
				$o_search = new $this->ops_search_class();
				
				$pa_types = array();
				if ($ps_types) {
					$pa_types = explode(';', $ps_types);
				} else {
					if ($ps_type) {
						$pa_types = array($ps_type);
					}
				}
				
				// Get type_ids
				$vs_type_query = '';
				$va_ids = array();
				if (sizeof($pa_types)) {
					$va_types = $this->opo_item_instance->getTypeList();
					$va_types_proc = array();
					foreach($va_types as $vn_type_id => $va_type) {
						$va_types_proc[$vn_type_id] = $va_types_proc[$va_type['idno']] = $vn_type_id;
					}
					foreach($pa_types as $ps_type) {
						if (isset($va_types_proc[$ps_type])) {
							$va_ids[$va_types_proc[$ps_type]] = true;
						}
					}
					$va_ids = array_keys($va_ids);
					
					if (sizeof($va_ids) > 0) {
						$t_list = new ca_lists();
						
						if (!$pb_no_subtypes) {
							foreach($va_ids as $vn_id) {
								$va_children = $t_list->getItemsForList($this->opo_item_instance->getTypeListCode(), array('item_id' => $vn_id, 'idsOnly' => true));
								$va_ids = array_merge($va_ids, $va_children);
							}
							$va_ids = array_flip(array_flip($va_ids));
						}
						$o_search->addResultFilter($this->opo_item_instance->tableName().'.'.$this->opo_item_instance->getTypeFieldName(), 'IN', join(",", $va_ids));
					}
				}
			
				// add any additional search elements
				$vs_additional_query_params = '';
				if (is_array($pa_additional_query_params) && sizeof($pa_additional_query_params)) {
					$vs_additional_query_params = ' AND ('.join(' AND ', $pa_additional_query_params).')';
				}
				
				// get sort field
				$vs_sort = '_natural';		// always sort first on relevance...
				if ($vs_idno_fld = $this->opo_item_instance->getProperty('ID_NUMBERING_SORT_FIELD')) {
					$vs_sort .= ";".$this->opo_item_instance->tableName().".{$vs_idno_fld}";
				} else {
					if (method_exists($this->opo_item_instance, "getLabelSortField")) {
						$vs_sort .= ";".$this->opo_item_instance->getLabelTableName().'.'.$this->opo_item_instance->getLabelSortField();
					}
				}
	
				$vs_hier_parent_id_fld 		= $this->opo_item_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
				$vs_hier_fld 						= $this->opo_item_instance->getProperty('HIERARCHY_ID_FLD');
				if ($vs_hier_fld && ($vn_restrict_to_hier_id = $this->request->getParameter('currentHierarchyOnly', pInteger))) {
					$o_search->addResultFilter($this->opo_item_instance->tableName().'.'.$vs_hier_fld, '=', (int)$vn_restrict_to_hier_id);
				}
				
				// add filters
				if (isset($pa_options['filters']) && is_array($pa_options['filters']) && sizeof($pa_options['filters'])) {
					foreach($pa_options['filters'] as $va_filter) {
						$o_search->addResultFilter($va_filter[0], $va_filter[1], $va_filter[2]);
					}
				}
				
				// do search
				$qr_res = $o_search->search($ps_query.(intval($pb_exact) ? '' : '*').$vs_type_query.$vs_additional_query_params, array('search_source' => 'Lookup', 'no_cache' => false, 'sort' => $vs_sort));
		
				$qr_res->setOption('prefetch', $pn_limit);
				$qr_res->setOption('dontPrefetchAttributes', true);
				
				$va_opts = array('exclude' => $va_excludes, 'limit' => $pn_limit);
				if(!$pb_no_inline && ($pb_quickadd || ($this->request->user && $this->request->user->canDoAction('can_quickadd_'.$this->opo_item_instance->tableName())))) {
					$va_opts['inlineCreateQuery'] = $ps_query;
					$va_opts['inlineCreateMessageDoesNotExist'] = _t('<em>%1</em> does not exist. Create?', $ps_query);
					$va_opts['inlineCreateMessage'] = _t('Create <em>%1</em>?', $ps_query);
				} else {
					$va_opts['emptyResultQuery'] = $ps_query;
					$va_opts['emptyResultMessage'] = _t('No matches found for <em>%1</em>', $ps_query);
				}
				
				$va_items = caProcessRelationshipLookupLabel($qr_res, $this->opo_item_instance, $va_opts);
			}
			if (!is_array($va_items)) { $va_items = array(); }
			
			// Optional output simple list of labels instead of full data format
			if ((bool)$this->request->getParameter('simple', pInteger)) { 
				$va_items = caExtractValuesFromArrayList($va_items, 'label', array('preserveKeys' => false)); 
			}
			$this->view->setVar(str_replace(' ', '_', $this->ops_name_singular).'_list', $va_items);
 			return $this->render(str_replace(' ', '_', 'ajax_'.$this->ops_name_singular.'_list_html.php'));
		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function GetHierarchyLevel() {
 			header("Content-type: application/json");
 			
			$ps_bundle = (string)$this->request->getParameter('bundle', pString);
			$pa_ids = explode(";", $ps_ids = $this->request->getParameter('id', pString));
			if (!sizeof($pa_ids)) { $pa_ids = array(null); }
			$t_item = $this->opo_item_instance;
			if (!$t_item->isHierarchical()) { return; }
			
			$va_level_data = array();
			foreach($pa_ids as $pn_id) {
				$va_tmp = explode(":", $pn_id);
				$vn_id = $va_tmp[0];
				$vn_start = (int)$va_tmp[1];
				if($vn_start < 0) { $vn_start = 0; }
				
				$va_items_for_locale = array();
				if ((!($vn_id)) && method_exists($t_item, "getHierarchyList")) { 
					$vn_id = (int)$this->request->getParameter('root_item_id', pInteger);
					$t_item->load($vn_id);
					// no id so by default return list of available hierarchies
					$va_items_for_locale = $t_item->getHierarchyList();
				} else {
					if ($t_item->load($vn_id)) {		// id is the id of the parent for the level we're going to return
						$vs_table_name = $t_item->tableName();
						$vs_label_table_name = $this->opo_item_instance->getLabelTableName();
						$vs_label_display_field_name = $this->opo_item_instance->getLabelDisplayField();
						$vs_pk = $this->opo_item_instance->primaryKey();
						
						$va_additional_wheres = array();
						$t_label_instance = $this->opo_item_instance->getLabelTableInstance();
						if ($t_label_instance && $t_label_instance->hasField('is_preferred')) {
							$va_additional_wheres[] = "(({$vs_label_table_name}.is_preferred = 1) OR ({$vs_label_table_name}.is_preferred IS NULL))";
						}
						
						$o_config = Configuration::load();
						if (!(is_array($va_sorts = $o_config->getList($this->ops_table_name.'_hierarchy_browser_sort_values'))) || !sizeof($va_sorts)) { $va_sorts = array(); }
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
						
						if (!in_array($vs_sort_dir = strtolower($o_config->get($this->ops_table_name.'_hierarchy_browser_sort_direction')), array('asc', 'desc'))) {
							$vs_sort_dir = 'asc';
						}

						$va_items = array();
						if (is_array($va_item_ids = $t_item->getHierarchyChildren($t_item->getPrimaryKey(), array('idsOnly' => true))) && sizeof($va_item_ids)) {
							$qr_children = $t_item->makeSearchResult($t_item->tableName(), $va_item_ids);
							$va_child_counts = $t_item->getHierarchyChildCountsForIDs($va_item_ids);
						
							if (!($vs_item_template = trim($o_config->get("{$vs_table_name}_hierarchy_browser_display_settings")))) {
								$vs_item_template = "^{$vs_table_name}.preferred_labels.{$vs_label_display_field_name}";
							}
						
							if ((($vn_max_items_per_page = $this->request->getParameter('max', pInteger)) < 1) || ($vn_max_items_per_page > 1000)) {
								$vn_max_items_per_page = 100;
							}
							$vn_c = 0;
						
							while($qr_children->nextHit()) {
								$va_tmp = array(
									$vs_pk => $vn_id = $qr_children->get($this->ops_table_name.'.'.$vs_pk),
									'item_id' => $vn_id,
									'parent_id' => $qr_children->get($this->ops_table_name.'.parent_id'),
									'idno' => $qr_children->get($this->ops_table_name.'.idno'),
									$vs_label_display_field_name => $qr_children->get($this->ops_table_name.'.preferred_labels.'.$vs_label_display_field_name),
									'locale_id' => $qr_children->get($this->ops_table_name.'.'.'locale_id')
								);
								if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = $va_tmp['idno']; }
								if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = '???'; }
							
								$va_tmp['name'] = caProcessTemplateForIDs($vs_item_template, $vs_table_name, array($va_tmp[$vs_pk]), array('requireLinkTags' => true));
							
								// Child count is only valid if has_children is not null
								$va_tmp['children'] = isset($va_child_counts[$vn_id]) ? (int)$va_child_counts[$vn_id] : 0;
							
								if (is_array($va_sorts)) {
									$vs_sort_acc = array();
									foreach($va_sorts as $vs_sort) {
										$vs_sort_acc[] = $qr_children->get($vs_sort, array('sortable' => true));
									}
									$va_tmp['sort'] = join(";", $vs_sort_acc);
								}
							
								$va_items[$va_tmp[$vs_pk]][$va_tmp['locale_id']] = $va_tmp;
							
								$vn_c++;
							}
						
							$va_items_for_locale = caExtractValuesByUserLocale($va_items);
						}
						
						$vs_rank_fld = $t_item->getProperty('RANK');
						
						$va_sorted_items = array();
						foreach($va_items_for_locale as $vn_id => $va_node) {
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
						$va_items_for_locale = array();
						
						$va_sorted_items = array_slice($va_sorted_items, $vn_start, $vn_max_items_per_page);
						
						foreach($va_sorted_items as $vs_k => $va_v) {
							ksort($va_v);
							if ($vs_sort_dir == 'desc') { $va_v = array_reverse($va_v); }
							$va_items_for_locale = array_merge($va_items_for_locale, $va_v);
						}
					}
				}
				$va_items_for_locale['_primaryKey'] = $t_item->primaryKey();	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
 				$va_items_for_locale['_itemCount'] = $qr_children ? $qr_children->numHits() : 0;
 			
 				$va_level_data[$pn_id] = $va_items_for_locale;
 			}
 			
 			if (!$this->request->getParameter('init', pInteger)) {
 				// only set remember "last viewed" if the load is done interactively
 				// if the GetHierarchyLevel() call is part of the initialization of the hierarchy browser
 				// then all levels are loaded, sometimes out-of-order; if we record these initialization loads
 				// as the 'last viewed' we can end up losing the true 'last viewed' value
 				//
 				// ... so the hierbrowser passes an extra 'init' parameters set to 1 if the GetHierarchyLevel() call
 				// is part of a browser initialization
 				$this->request->session->setVar($this->ops_table_name.'_'.$ps_bundle.'_browse_last_id', array_pop($pa_ids));
 			}
 			
 			
 			$this->view->setVar(str_replace(' ', '_', $this->ops_name_singular).'_list', $va_level_data);
 			
 			return $this->render(str_replace(' ', '_', $this->ops_name_singular).'_hierarchy_level_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of ancestors for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function GetHierarchyAncestorList() {
 			header("Content-type: application/json");
 			
 			$pn_id = $this->request->getParameter('id', pInteger);
 			$t_item = new $this->ops_table_name($pn_id);
 			
 			$va_ancestors = array();
 			if ($t_item->getPrimaryKey()) { 
 				$va_ancestors = array_reverse($t_item->getHierarchyAncestors(null, array('includeSelf' => true, 'idsOnly' => true)));
 			}
 			
 			// Force ids to ints to prevent jQuery from getting confused
 			// (jQuery.getJSON() incorrectly parses arrays of numbers-as-strings)
 			$va_ancestors = array_map('intval', $va_ancestors);
 			
 			$this->view->setVar('ancestors', $va_ancestors);
 			return $this->render(str_replace(' ', '_', $this->ops_name_singular).'_hierarchy_ancestors_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
		public function IDNo() {
			header("Content-type: application/json");
			
			$va_ids = array();
			if ($vs_idno_field = $this->opo_item_instance->getProperty('ID_NUMBERING_ID_FIELD')) {
				$pn_id =  $this->request->getParameter('id', pInteger);
				
				if ($vs_idno_context_field = $this->opo_item_instance->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {		// want to set context before doing identifier lookup, if the table supports contexts (ca_list_items and ca_place do, others don't)
					if($pn_context_id =  $this->request->getParameter('_context_id', pInteger)) {
						$this->opo_item_instance->load(array($vs_idno_context_field => $pn_context_id));
					} else {
						$this->opo_item_instance->load($pn_id);
					}
				}
				if ($ps_idno = $this->request->getParameter('n', pString)) {
					$va_ids = $this->opo_item_instance->checkForDupeAdminIdnos($ps_idno, false, $pn_id);
				}
			}
			$this->view->setVar('id_list', $va_ids);
			return $this->render('idno_json.php');
		}
		# -------------------------------------------------------
 		/**
 		 * Checks value of instrinsic field and return list of primary keys that use the specified value
 		 * Can be used to determine if a value that needs to be unique is actually unique.
 		 */
		public function Intrinsic() {
			header("Content-type: application/json");
			
			$pn_table_num 	=  $this->request->getParameter('table_num', pInteger);
			$ps_field 				=  $this->request->getParameter('field', pString);
			$ps_val 				=  $this->request->getParameter('n', pString);
			$pn_id 					=  $this->request->getParameter('id', pInteger);
			$pa_within_fields	=  $this->request->getParameter('withinFields', pArray); 
			
			$vo_dm = Datamodel::load();
			if (!($t_instance = $vo_dm->getInstanceByTableNum($pn_table_num, true))) {
				return null;	// invalid table number
			}
			
			if (!$t_instance->hasField($ps_field)) {
				return null;	// invalid field
			}
			
			$o_db = new Db();
			$vs_pk = $t_instance->primaryKey();
			
			
			// If "unique within" fields are specified then we limit our query to values that have those fields
			// set similarly to the row we're checking.
			$va_unique_within = $t_instance->getFieldInfo($ps_field, 'UNIQUE_WITHIN');
			
			$va_extra_wheres = array();
			$vs_extra_wheres = '';
			$va_params = array((string)$ps_val, (int)$pn_id);
			if (sizeof($va_unique_within)) {
				foreach($va_unique_within as $vs_within_field) {
					$va_extra_wheres[] = "({$vs_within_field} = ?)";
					$va_params[] = $pa_within_fields[$vs_within_field];
				}
				$vs_extra_wheres = ' AND '.join(' AND ', $va_extra_wheres);
			}
		
			$qr_res = $o_db->query("
				SELECT {$vs_pk}
				FROM ".$t_instance->tableName()."
				WHERE
					({$ps_field} = ?) AND ({$vs_pk} <> ?)
					{$vs_extra_wheres}
			", $va_params);
			
			$va_ids = array();
			while($qr_res->nextRow()) {
				$va_ids[] = (int)$qr_res->get($vs_pk);
			}
			
			$this->view->setVar('id_list', $va_ids);
			return $this->render('intrinsic_json.php');
		}
 		# -------------------------------------------------------
 	}
?>