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
	require_once(__CA_LIB_DIR__."/ca/BaseRefineableSearchController.php");
	require_once(__CA_LIB_DIR__."/ca/Browse/ObjectBrowse.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_search_forms.php");
 	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
 	
 	class BaseSearchController extends BaseRefineableSearchController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = false;
 		protected $opo_datamodel;
 		protected $ops_find_type;
 		
 		# -------------------------------------------------------
 		#
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
	
				$va_sortable_elements = ca_metadata_elements::getSortableElements($this->ops_tablename, $this->opn_type_restriction_id);
	
				$this->opa_sorts = array();
				foreach($va_sortable_elements as $vn_element_id => $va_sortable_element) {
					$this->opa_sorts[$this->ops_tablename.'.'.$va_sortable_element['element_code']] = $va_sortable_element['display_label'];
				}
			}
 		}
 		# -------------------------------------------------------
 		/**
 		 * Options:
 		 *		appendToSearch = optional text to be AND'ed wuth current search expression
 		 *		output_format = determines format out search result output. "PDF" and "HTML" are currently supported; "HTML" is the default
 		 *		view = view with path relative to controller to use overriding default ("search/<table_name>_search_basic_html.php")
 		 *		vars = associative array with key value pairs to assign to the view
 		 *
 		 * Callbacks:
 		 * 		hookBeforeNewSearch() is called just before executing a new search. The first parameter is the BrowseEngine object containing the search.
 		 */
 		public function Index($pa_options=null) {
 			$po_search = isset($pa_options['search']) ? $pa_options['search'] : null;
 			
 			if (isset($pa_options['saved_search']) && $pa_options['saved_search']) {
 				$this->opo_result_context->setSearchExpression($pa_options['saved_search']['search']);
 				$this->opo_result_context->isNewSearch(true);
 			}
 			parent::Index($pa_options);
 			
 			JavascriptLoadManager::register('hierBrowser');
 			JavascriptLoadManager::register('browsable');	// need this to support browse panel when filtering/refining search results
 			$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$va_access_values = caGetUserAccessValues($this->request);
 			
 			// Get elements of result context
 			$vn_page_num 			= $this->opo_result_context->getCurrentResultsPageNumber();
 			$vs_search 				= $this->opo_result_context->getSearchExpression();
 			$vb_is_new_search		= $this->opo_result_context->isNewSearch();
 			
 			if ((bool)$this->request->getParameter('reset', pString) && ($this->request->getParameter('reset', pString) != 'save')) {
 				$vs_search = '';
 				$vb_is_new_search = true;
 			}
 			
			if (!($vn_items_per_page = $this->opo_result_context->getItemsPerPage())) { 
 				$vn_items_per_page = $this->opn_items_per_page_default; 
 				$this->opo_result_context->setItemsPerPage($vn_items_per_page);
 			}
 			
 			if (!($vs_view 			= $this->opo_result_context->getCurrentView())) { 
 				$va_tmp = array_keys($this->opa_views);
 				$vs_view = $this->ops_view_default ? $this->ops_view_default : array_shift($va_tmp); 
 				$this->opo_result_context->setCurrentView($vs_view);
 			}
 			if (!isset($this->opa_views[$vs_view])) { 
 				$va_tmp = array_keys($this->opa_views);
 				$vs_view = array_shift($va_tmp); 
 			}
 			
 			if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) { 
 				$va_tmp = array_keys($this->opa_sorts);
 				$vs_sort = array_shift($va_tmp); 
 			}
 			$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();
			$vn_display_id 	= $this->opo_result_context->getCurrentBundleDisplay();
 			
 			if (!$this->opn_type_restriction_id) { $this->opn_type_restriction_id = ''; }
 			$this->view->setVar('type_id', $this->opn_type_restriction_id);
 			
 			MetaTagManager::setWindowTitle(_t('%1 search', $this->searchName('plural')));
 			
 			// Get attribute sorts
 			$va_sortable_elements = ca_metadata_elements::getSortableElements($this->ops_tablename, $this->opn_type_restriction_id);
 			
 			if (!is_array($this->opa_sorts)) { $this->opa_sorts = array(); }
 			foreach($va_sortable_elements as $vn_element_id => $va_sortable_element) {
 				$this->opa_sorts[$this->ops_tablename.'.'.$va_sortable_element['element_code']] = $va_sortable_element['display_label'];
 			}
 			
 			if ($pa_options['appendToSearch']) {
 				$vs_append_to_search .= " AND (".$pa_options['appendToSearch'].")";
 			}
			//
			// Execute the search
			//
			if($vs_search && ($vs_search != "")){ /* any request? */
				$va_search_opts = array(
					'sort' => $vs_sort, 
					'sort_direction' => $vs_sort_direction, 
					'appendToSearch' => $vs_append_to_search,
					'checkAccess' => $va_access_values,
					'no_cache' => $vb_is_new_search,
					'dontCheckFacetAvailability' => true,
					'filterNonPrimaryRepresentations' => true
				);
				if ($vb_is_new_search ||isset($pa_options['saved_search']) || (is_subclass_of($po_search, "BrowseEngine") && !$po_search->numCriteria()) ) {
					$vs_browse_classname = get_class($po_search);
 					$po_search = new $vs_browse_classname;
 					if (is_subclass_of($po_search, "BrowseEngine")) {
 						$po_search->addCriteria('_search', $vs_search);
 						
 						if (method_exists($this, "hookBeforeNewSearch")) {
 							$this->hookBeforeNewSearch($po_search);
 						}
 					}
 					
 					$this->opo_result_context->setParameter('show_type_id', null);
 				}
 				if ($this->opn_type_restriction_id) {
 					$po_search->setTypeRestrictions(array($this->opn_type_restriction_id), array('includeSubtypes' => false));
 				}
 				
 				$vb_criteria_have_changed = false;
 				if (is_subclass_of($po_search, "BrowseEngine")) { 					
					//
					// Restrict facets to specific group for main browse landing page (if set in app.conf config)
					// 			
					if ($vs_facet_group = $this->request->config->get($this->ops_tablename.'_search_refine_facet_group')) {
						$po_search->setFacetGroup($vs_facet_group);
					}
					
 					$vb_criteria_have_changed = $po_search->criteriaHaveChanged();
					$po_search->execute($va_search_opts);
					
					$this->opo_result_context->setParameter('browse_id', $po_search->getBrowseID());
					
					if ($vs_group_name = $this->request->config->get('browse_facet_group_for_'.$this->ops_tablename)) {
 						$po_search->setFacetGroup($vs_group_name);
 					}
 					
					$vo_result = $po_search->getResults($va_search_opts);
				} else {
					$vo_result = $po_search->search($vs_search, $va_search_opts);
				}
				$this->opo_result_context->validateCache();
				
				// Only prefetch what we need
				$vo_result->setOption('prefetch', $vn_items_per_page);
				
				//
				// Handle details of partitioning search results by type, if required
				//
				if ((bool)$this->request->config->get('search_results_partition_by_type')) {
					$va_type_counts = $vo_result->getResultCountForFieldValues(array('ca_objects.type_id'));
					$va_type_counts_obj_type = $va_type_counts['ca_objects.type_id'];
					ksort($va_type_counts_obj_type);
					$this->view->setVar('counts_by_type', $va_type_counts_obj_type);
					
					$vn_show_type_id = $this->opo_result_context->getParameter('show_type_id');
					if (!isset($va_type_counts_obj_type[$vn_show_type_id])) {
						$va_tmp = array_keys($va_type_counts_obj_type);
						$vn_show_type_id = array_shift($va_tmp);
					}
					$this->view->setVar('show_type_id', $vn_show_type_id);
					$vo_result->filterResult('ca_objects.type_id', $vn_show_type_id);
				}
		
 				if($vb_is_new_search || $vb_criteria_have_changed) {
					$this->opo_result_context->setResultList($vo_result->getPrimaryKeyValues());
					$this->opo_result_context->setParameter('availableVisualizationChecked', 0);
					if ($vs_search) { $vn_page_num = 1; }
				}
 				$this->view->setVar('num_hits', $vo_result->numHits());
 				$this->view->setVar('num_pages', $vn_num_pages = ceil($vo_result->numHits()/$vn_items_per_page));
 				if ($vn_page_num > $vn_num_pages) { $vn_page_num = 1; }
 				
 				$vo_result->seek(($vn_page_num - 1) * $vn_items_per_page);
 				$this->view->setVar('page', $vn_page_num);
 				$this->view->setVar('search', $vs_search);
 				$this->view->setVar('result', $vo_result);
 			}
 			//
 			// Set up view for display of results
 			//
 			$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
			$this->view->setVar('views', $this->opa_views);	// pass view list to view for rendering
			$this->view->setVar('current_view', $vs_view);
			
			$this->view->setVar('sorts', $this->opa_sorts);	// pass sort list to view for rendering
			$this->view->setVar('current_sort', $vs_sort);
			$this->view->setVar('current_sort_direction', $vs_sort_direction);
			
			$this->view->setVar('current_items_per_page', $vn_items_per_page);
			$this->view->setVar('items_per_page', $this->opa_items_per_page);
			
			$this->view->setVar('t_subject', $t_model);
			
			$this->view->setVar('mode_name', _t('search'));
			$this->view->setVar('mode', 'search');
			$this->view->setVar('mode_type_singular', $this->searchName('singular'));
			$this->view->setVar('mode_type_plural', $this->searchName('plural'));
			
			$this->view->setVar('search_history', $this->opo_result_context->getSearchHistory());
	
			$this->view->setVar('result_context', $this->opo_result_context);
			$this->view->setVar('uses_hierarchy_browser', $this->usesHierarchyBrowser());
			
			$this->view->setVar('access_values', $va_access_values);
			$this->view->setVar('browse', $po_search);
			
			$t_display = $this->view->getVar('t_display');
			$va_display_list = $this->view->getVar('display_list');
			if ($vs_view == 'editable') {
				
				$va_initial_data = array();
				$va_row_headers = array();
				
 				$vn_item_count = 0;
 				
 				if ($vo_result) {
					$vs_pk = $vo_result->primaryKey();
				
					while(($vn_item_count < 100) && $vo_result->nextHit()) {
						$va_result = array('item_id' => $vn_id = $vo_result->get($vs_pk));
	
						foreach($va_display_list as $vn_placement_id => $va_bundle_info) {
							$va_result[str_replace(".", "-", $va_bundle_info['bundle_name'])] = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request));
						}
	
						$va_initial_data[] = $va_result;
	
						$vn_item_count++;
	
						$va_row_headers[] = ($vn_item_count)." ".caEditorLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), 'caResultsEditorEditLink', $vs_subject_table, $vn_id);
	
					}
				}
				
				$this->view->setVar('initialData', $va_initial_data);
				$this->view->setVar('rowHeaders', $va_row_headers);
			}
			
			//
			// Bottom line
			//
			$va_bottom_line = array();
			$vb_bottom_line_is_set = false;
			foreach($va_display_list as $vn_placement_id => $va_placement) {
				if(isset($va_placement['settings']['bottom_line']) && $va_placement['settings']['bottom_line']) {
					$va_bottom_line[$vn_placement_id] = caProcessBottomLineTemplate($this->request, $va_placement, $vo_result, array('pageStart' => ($vn_page_num - 1) * $vn_items_per_page, 'pageEnd' => (($vn_page_num - 1) * $vn_items_per_page) + $vn_items_per_page));
					$vb_bottom_line_is_set = true;
				} else {
					$va_bottom_line[$vn_placement_id] = '';
				}
			}
			$this->view->setVar('bottom_line', $vb_bottom_line_is_set ? $va_bottom_line : null);
			
			
 			switch($pa_options['output_format']) {
 				# ------------------------------------
 				case 'PDF':
 					$this->_genPDF($vo_result, $this->request->getParameter("label_form", pString), $vs_search, $vs_search);
 					break;
 				# ------------------------------------
 				case 'EXPORT':
 					$this->_genExport($vo_result, $this->request->getParameter("export_format", pString), $vs_search, $vs_search);
 					break;
 				# ------------------------------------
 				case 'HTML': 
				default:
					// generate type menu and type value list
					if (method_exists($t_model, "getTypeList")) {
						$this->view->setVar('type_list', $t_model->getTypeList());
					}
					if ($this->opb_uses_hierarchy_browser) {
						//if (sizeof($t_model->getHierarchyList()) > 0) {
							JavascriptLoadManager::register('hierBrowser');
							
							// only for interfaces that use the hierarchy browser
							$t_list = new ca_lists();
							if ($vs_type_list_code = $t_model->getTypeListCode()) {
								$this->view->setVar('num_types', $t_list->numItemsInList($vs_type_list_code));
								$this->view->setVar('type_menu',  $t_list->getListAsHTMLFormElement($vs_type_list_code, 'type_id', array('id' => 'hierTypeList')));
							}
							
							// set last browse id for hierarchy browser
							$this->view->setVar('browse_last_id', intval($this->request->session->getVar($this->ops_tablename.'_browse_last_id')));
						//} else {
						//	$this->view->setVar('no_hierarchies_defined', 1);
						//	$this->notification->addNotification(_t("No hierarchies are configured for %1", $t_model->getProperty('NAME_PLURAL')), __NOTIFICATION_TYPE_ERROR__);
						//}
					}
					
					$this->opo_result_context->setAsLastFind();
					$this->opo_result_context->saveContext();
				
					if (isset($pa_options['vars']) && is_array($pa_options['vars'])) { 
						foreach($pa_options['vars'] as $vs_key => $vs_val) {
							$this->view->setVar($vs_key, $vs_val);
						}
					}
					if (isset($pa_options['view']) && $pa_options['view']) { 
						$this->render($pa_options['view']);
					} else {
						$this->render('Search/'.$this->ops_tablename.'_search_basic_html.php');
					}
					break;
				# ------------------------------------
			}
 		}
 		# -------------------------------------------------------
		# Navigation (menu bar)
		# -------------------------------------------------------
 		public function _genTypeNav($pa_params) {
 			$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			
 			$t_list = new ca_lists();
 			$t_list->load(array('list_code' => $t_subject->getTypeListCode()));
 			
 			$t_list_item = new ca_list_items();
 			$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
 			$va_hier = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
 			
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_tablename, array('access' => __CA_BUNDLE_ACCESS_READONLY__));
 			}
 			$va_types = array();
 			if (is_array($va_hier)) {
 				
 				$va_types_by_parent_id = array();
 				$vn_root_id = $t_list->getRootItemIDForList($t_subject->getTypeListCode());

 				// organize items by parent id, exclude root
				foreach($va_hier as $vn_item_id => $va_item) {
					if ($vn_item_id == $vn_root_id) { continue; } // skip root
					if (is_array($va_restrict_to_types) && !in_array($vn_item_id, $va_restrict_to_types)) { continue; }
					$va_types_by_parent_id[$va_item['parent_id']][] = $va_item;
				}
				foreach($va_hier as $vn_item_id => $va_item) {
					if (is_array($va_restrict_to_types) && !in_array($vn_item_id, $va_restrict_to_types)) { continue; }
					if ($va_item['parent_id'] != $vn_root_id) { continue; }
					// does this item have sub-items?
					if (isset($va_item['item_id']) && isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
						$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id, $va_restrict_to_types);
					} else {
						$va_subtypes = array();
					}
					$va_types[] = array(
						'displayName' =>$va_item['name_plural'],
						'parameters' => array(
							'type_id' => $va_item['item_id']
						),
						'is_enabled' => 1,
						'navigation' => $va_subtypes
					);
				}
			}
 			return $va_types;
 		}
 		# ------------------------------------------------------------------
		private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id, $pa_restrict_to_types=null) {
			$va_subtypes = array();
			foreach($pa_subtypes as $vn_i => $va_type) {
				if (is_array($pa_restrict_to_types) && !in_array($va_type['item_id'], $pa_restrict_to_types)) { continue; }
				if (isset($pa_types_by_parent_id[$va_type['item_id']]) && is_array($pa_types_by_parent_id[$va_type['item_id']])) {
					$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id, $pa_restrict_to_types);
				} else {
					$va_subsubtypes = array();
				}
				$va_subtypes[$va_type['item_id']] = array(
					'displayName' => $va_type['name_singular'],
					'parameters' => array(
						'type_id' => $va_type['item_id']
					),
					'is_enabled' => 1,
					'navigation' => $va_subsubtypes
				);
			}
			
			return $va_subtypes;
		}
		# -------------------------------------------------------
 		# "Searchlight" autocompleting search
 		# -------------------------------------------------------
 		public function lookup() {
 			$vs_search = $this->request->getParameter('q', pString);
 			
 			$t_list = new ca_lists();
 			$va_data = array();
 			
 			$va_access_values = caGetUserAccessValues($this->request);
 			
 			#
 			# Do "quicksearches" on so-configured tables
 			#
 			if ($this->request->config->get('quicksearch_return_ca_objects')) {
				$va_results = caExtractValuesByUserLocale(SearchEngine::quickSearch($vs_search, 'ca_objects', 57, array('limit' => 3, 'checkAccess' => $va_access_values)));
				// break found objects out by type
				foreach($va_results as $vn_id => $va_match_info) {
					$vs_type = unicode_ucfirst($t_list->getItemFromListForDisplayByItemID('object_types', $va_match_info['type_id'], true));
					$va_data['ca_objects'][$vs_type][$vn_id] = $va_match_info;
				}
			}
			
			if ($this->request->config->get('quicksearch_return_ca_entities')) {
 				$va_data['ca_entities'][_t('Entities')] = caExtractValuesByUserLocale(SearchEngine::quickSearch($vs_search, 'ca_entities', 20, array('limit' => 10, 'checkAccess' => $va_access_values)));
 			}
 			
 			if ($this->request->config->get('quicksearch_return_ca_places')) {
 				$va_data['ca_places'][_t('Places')] = caExtractValuesByUserLocale(SearchEngine::quickSearch($vs_search, 'ca_places', 72, array('limit' => 10, 'checkAccess' => $va_access_values)));
 			}
 			
 			if ($this->request->config->get('quicksearch_return_ca_occurrences')) {
				$va_results = caExtractValuesByUserLocale(SearchEngine::quickSearch($vs_search, 'ca_occurrences', 67, array('limit' => 10, 'checkAccess' => $va_access_values)));
				// break found occurrences out by type
				foreach($va_results as $vn_id => $va_match_info) {
					$vs_type = unicode_ucfirst($t_list->getItemFromListForDisplayByItemID('occurrence_types', $va_match_info['type_id'], true));
					$va_data['ca_occurrences'][$vs_type][$vn_id] = $va_match_info;
				}
			}
			
			if ($this->request->config->get('quicksearch_return_ca_collections')) {
 				$va_data['ca_collections'][_t('Collections')] = caExtractValuesByUserLocale(SearchEngine::quickSearch($vs_search, 'ca_collections', 13, array('limit' => 10, 'checkAccess' => $va_access_values)));
 			}
 			
 			
 			$this->view->setVar('matches', $va_data);
 			$this->render('Search/ajax_search_lookup_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function getPartialResult($pa_options=null) {
 			$pa_options['search'] = $this->opo_browse;
 			return parent::getPartialResult($pa_options);
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			// MUST BE OVERRIDDEN 
 			return "undefined";
 		}
 		# -------------------------------------------------------
 		public function usesHierarchyBrowser() {
 			return (bool)$this->opb_uses_hierarchy_browser;
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Tools($pa_parameters) {
 			parent::Tools($pa_parameters);
 			
			$this->view->setVar('mode_name', _t('search'));
			$this->view->setVar('mode_type_singular', $this->searchName('singular'));
			$this->view->setVar('mode_type_plural', $this->searchName('plural'));
			
			$this->view->setVar('table_name', $this->ops_tablename);
			$this->view->setVar('find_type', $this->ops_find_type);
 			
			$this->view->setVar('search_history', $this->opo_result_context->getSearchHistory());
 			
 			return $this->render('Search/widget_'.$this->ops_tablename.'_search_tools.php', true);
 		}
 		# -------------------------------------------------------
 	}
?>
