<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseBrowseController.php : base controller for browse interface
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 
	require_once(__CA_LIB_DIR__."/ca/BaseFindController.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
 	require_once(__CA_APP_DIR__.'/helpers/browseHelpers.php');
 	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
 	
 	class BaseBrowseController extends BaseFindController {
 		# -------------------------------------------------------
		protected $opo_browse;
		protected $ops_tablename;
		protected $ops_context = '';
		
 		protected $ops_find_type;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if ($this->ops_tablename) {
				if ($va_items_per_page_config = $po_request->config->get('items_per_page_options_for_'.$this->ops_tablename.'_browse')) {
					$this->opa_items_per_page = $va_items_per_page_config;
				}
				if (($vn_items_per_page_default = (int)$po_request->config->get('items_per_page_default_for_'.$this->ops_tablename.'_browse')) > 0) {
					$this->opn_items_per_page_default = $vn_items_per_page_default;
				} else {
					$this->opn_items_per_page_default = $this->opa_items_per_page[0];
				}
				
				// get configured result views, if specified
				if ($va_result_views_for = $po_request->config->getAssoc('result_views_for_'.$this->ops_tablename)) {
					$this->opa_views = $va_result_views_for;
				}
				
				$this->ops_view_default = null;
				if ($vs_view_default = $po_request->config->get('view_default_for_'.$this->ops_tablename.'_browse')) {
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
 		function Index($pb_dont_render_view=false, $pa_options=null) {
 			parent::Index($po_search, $pa_options);
 			JavascriptLoadManager::register('browsable');
			JavascriptLoadManager::register('hierBrowser');
 			
 			$va_access_values = caGetUserAccessValues($this->request);
 			
 			//
 			// Restrict facets to specific group for main browse landing page (if set in app.conf config)
 			// 			
 			if ($vs_facet_group = $this->request->config->get($this->ops_tablename.'_browse_facet_group')) {
 				$this->opo_browse->setFacetGroup($vs_facet_group);
 			}
 			
 			//
 			// Set useful values we'll need later
 			//
			$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$vb_criteria_have_changed = $this->opo_browse->criteriaHaveChanged();
			
			// Get elements of result context
 			$vn_page_num 			= $this->opo_result_context->getCurrentResultsPageNumber();
 			
 			if ($this->opb_type_restriction_has_changed || ($this->request->getParameter('reset', pString) == 'clear')) { 
 				$this->opo_browse->removeAllCriteria(); 
 				$this->opo_result_context->setSearchExpression($this->opo_browse->getBrowseID());
 			}
 			
 			if (!($vn_items_per_page = $this->opo_result_context->getItemsPerPage())) { 
 				$vn_items_per_page = $this->opn_items_per_page_default; 
 				$this->opo_result_context->setItemsPerPage($vn_items_per_page);
 			}
 			
 			if (!($vs_view 			= $this->opo_result_context->getCurrentView())) { 
 				$vs_view = $this->ops_view_default ? $this->ops_view_default : array_shift(array_keys($this->opa_views)); 
 				$this->opo_result_context->setCurrentView($vs_view);
 			}
 			if (!isset($this->opa_views[$vs_view])) { $vs_view = array_shift(array_keys($this->opa_views)); }
 			
 			if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) { $vs_sort = array_shift(array_keys($this->opa_sorts)); }
			$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();
			
 			if (!$vn_page_num || $vb_criteria_have_changed) { $vn_page_num = 1; }
 			
 			// Do redirect directly to detail if configured to do so
 			if (
 				$this->opo_browse->criteriaHaveChanged() 
 				&& 
 				(sizeof($va_criteria = $this->opo_browse->getCriteria()) == 1)
 			) {
 				$va_tmp = array_keys($va_criteria);
  				
 				$va_tmp1 = array_keys($va_criteria[$va_tmp[0]]);
 				$va_facet_info = $this->opo_browse->getInfoForFacet($va_tmp[0]);
 				
 				if ($this->request->config->get('redirect_to_'.$va_facet_info['table'].'_detail_if_is_first_facet')) {
 					$t_table = $this->opo_datamodel->getInstanceByTableName($va_facet_info['table'], true);
 					
 					$va_newmuseum_hack_occurrence_type_ids = $this->request->config->getList('newmuseum_hack_browse_should_redirect_occurrence_types_to_object_details');
 					if (is_array($va_newmuseum_hack_occurrence_type_ids) && sizeof($va_newmuseum_hack_occurrence_type_ids) && ($va_facet_info['table'] == 'ca_occurrences')) {
 						if ($t_table->load($va_tmp1[0])) {
 							if (in_array($t_table->getTypeID(), $va_newmuseum_hack_occurrence_type_ids)) {
 								if (sizeof($va_objects = $t_table->getRelatedItems('ca_objects'))) {
 									$va_object = array_shift($va_objects);
 									$vn_object_id = $va_object['object_id'];
 									$this->response->setRedirect(caNavUrl($this->request, 'Detail', 'Object', 'Show', array('object_id' => $vn_object_id)));
 									return;
 								}
 							}
 						}
 					}
 					$this->response->setRedirect(caNavUrl($this->request, 'Detail', ucfirst($t_table->getProperty('NAME_SINGULAR')), 'Show', array($t_table->primaryKey() => $va_tmp1[0])));
 					return;
 				}
 			}
 			
 			//
 			// Enforce type restriction, if defined
 			// 
 			$this->opo_browse->setTypeRestrictions(array($this->opn_type_restriction_id));
 			
 			MetaTagManager::setWindowTitle(_t('%1 browse', $this->browseName('plural')));
 			
 			//
 			// Actually execute the browse - do the queries
 			//
 			if ($vs_group_name = $this->request->config->get('browse_facet_group_for_'.$this->ops_tablename)) {
				$this->opo_browse->setFacetGroup($vs_group_name);
			}
 			$this->opo_browse->execute(array('checkAccess' => $va_access_values, 'no_cache' => !$this->opo_result_context->cacheIsValid()));
 			$this->opo_result_context->validateCache();
 			
			$this->opo_result_context->setSearchExpression($this->opo_browse->getBrowseID());
 			
 			//
 			// Pass browse info (context + facets + criteria) to view
 			//
 			
 			
			$this->view->setVar('browse', $this->opo_browse);
			$this->view->setVar('target', $this->ops_tablename);
			$this->view->setVar('result_context', $this->opo_result_context);
			
 			$this->view->setVar('criteria', $va_criteria = $this->opo_browse->getCriteriaWithLabels());
 			$this->view->setVar('available_facets', $this->opo_browse->getInfoForAvailableFacets());
 			$this->view->setVar('available_facets_as_html_select', $this->opo_browse->getAvailableFacetListAsHTMLSelect('facet', array('id' => 'browseFacetSelect'), array('use_singular' => true, 'select_message' => (sizeof($va_criteria) > 0) ? _t('Refine Results By...') : _t('Start Browsing By...'))));
 			
 			$this->view->setVar('facets_with_content', $this->opo_browse->getInfoForFacetsWithContent());
 			$this->view->setVar('facet_info', $va_facet_info = $this->opo_browse->getInfoForFacets());
 			
 			$va_single_facet_values = array();
			foreach($va_facet_info as $vs_facet => $va_facet_settings) {
				$va_single_facet_values[$vs_facet] = isset($va_facet_settings['single_value']) ? $va_facet_settings['single_value'] : null;
			}
 			$this->view->setVar('single_facet_values', $va_single_facet_values);
		
		
			// browse criteria in an easy-to-display format
			$va_browse_criteria = array();
			foreach($this->opo_browse->getCriteriaWithLabels() as $vs_facet_code => $va_criteria) {
				$va_facet_info = $this->opo_browse->getInfoForFacet($vs_facet_code);
				
				$va_criteria_list = array();
				foreach($va_criteria as $vn_criteria_id => $vs_criteria_label) {
					$va_criteria_list[] = $vs_criteria_label;
				}
				
				$va_browse_criteria[$va_facet_info['label_singular']] = join('; ', $va_criteria_list);
			}
			$this->view->setVar('browse_criteria', $va_browse_criteria);
			
			//
			// Get the browse results
			//
			
			$this->view->setVar('num_hits', $vn_num_hits = $this->opo_browse->numResults());
			$this->view->setVar('num_pages', $vn_num_pages = ceil($vn_num_hits/$vn_items_per_page));
			if ($vn_page_num > $vn_num_pages) { $vn_page_num = 1; }
			
			if ($pa_options['output_format']) {
				$vo_result = $this->opo_browse->getResults(array('sort' => $vs_sort, 'sort_direction' => $vs_sort_direction));
			} else {
				$vo_result = $this->opo_browse->getResults(array('sort' => $vs_sort, 'sort_direction' => $vs_sort_direction, 'start' => ($vn_page_num - 1) * $vn_items_per_page, 'limit' => $vn_items_per_page));
			}
			
			// Only prefetch what we need
			$vo_result->setOption('prefetch', $vn_items_per_page);
			
			if ($vo_result) {
				if ($vb_criteria_have_changed) {
					// Put the results id list into the results context - we used this for previous/next navigation
					$this->opo_result_context->setResultList($vo_result->getPrimaryKeyValues());
				}
				
				$vo_result->seek(0);	
			}
			
			//
 			// Set up view for display of results
 			// 			
			$this->view->setVar('page', $vn_page_num);
			$this->view->setVar('result', $vo_result);	
			
			$this->view->setVar('views', $this->opa_views);	// pass view list to view for rendering
			$this->view->setVar('current_view', $vs_view);
			
			$this->view->setVar('sorts', $this->opa_sorts);	// pass sort list to view for rendering
			$this->view->setVar('current_sort', $vs_sort);
			$this->view->setVar('current_sort_direction', $vs_sort_direction);
			
			$this->view->setVar('items_per_page', $this->opa_items_per_page);
			$this->view->setVar('current_items_per_page', $vn_items_per_page);
			
			$this->view->setVar('t_subject', $t_model);
					
			$this->view->setVar('mode_name', _t('browse'));
			$this->view->setVar('mode', 'browse');
			$this->view->setVar('mode_type_singular', $this->browseName('singular'));
			$this->view->setVar('mode_type_plural', $this->browseName('plural'));
			
			$this->view->setVar('access_values', $va_access_values);
 			switch($pa_options['output_format']) {
 				# ------------------------------------
 				case 'PDF':
 					$this->_genPDF($vo_result, $this->request->getParameter("label_form", pString), _t('Browse'), _t('Browse'));
 					break;
 				# ------------------------------------
 				case 'EXPORT':
 					$this->_genExport($vo_result, $this->request->getParameter("export_format", pString), _t('Browse'), _t('Browse'));
 					break;
 				# ------------------------------------
 				case 'HTML': 
				default:
					// generate type menu and type value list
					if (method_exists($t_model, "getTypeList")) {
						$this->view->setVar('type_list', $t_model->getTypeList());
					}
					
					$this->opo_result_context->setAsLastFind();
					$this->opo_result_context->saveContext();
					
					if (!$pb_dont_render_view) {
						$this->render('Browse/browse_controls_html.php');
					}
					break;
			}
 		}
 		# -------------------------------------------------------
 		public function getFacet($pa_options=null) {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			
 			$this->view->setVar('only_show_group', $vs_show_group = $this->request->getParameter('show_group', pString));
 			$this->view->setVar('grouping', $vs_grouping = $this->request->getParameter('grouping', pString));
 			$this->view->setVar('id', $vm_id = $this->request->getParameter('id', pString));
 				
 			$vs_cache_key = md5(join("/", array($ps_facet_name,$vs_show_group,$vs_grouping,$vm_id)));
 			$va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name);
 			
 			if (($va_facet_info['group_mode'] != 'hierarchical') && ($vs_content = $this->opo_browse->getCachedFacetHTML($vs_cache_key))) { 
 				$this->response->addContent($vs_content);
 				return;
 			}
 			
 			// Enforce type restriction
 			$this->opo_browse->setTypeRestrictions(array($this->opn_type_restriction_id));
 			
 			if ($this->request->getParameter('clear', pInteger)) {
 				$this->opo_browse->removeAllCriteria();
 				$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 				
 				$this->opo_result_context->setSearchExpression($this->opo_browse->getBrowseID());
 				$this->opo_result_context->saveContext();
 			} else {
 				if ($this->request->getParameter('modify', pString)) {
 					$this->opo_browse->removeCriteria($ps_facet_name, array($vm_id));
 					$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 					
 					$this->view->setVar('modify', $vm_id);
 				}
 			}
 			
 			// Using the back-button can cause requests for facets that are no longer available
 			// In these cases we reset the browse.
 			if (!($va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values)))) {
 				 $this->opo_browse->removeAllCriteria();
 				 $this->opo_browse->execute();
 				 $va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
 				 $va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name);
 				 
				$this->opo_result_context->setSearchExpression($this->opo_browse->getBrowseID());
				$this->opo_result_context->saveContext();
 			}
 			
 			$this->view->setVar('browse_last_id', (int)$vm_id ? (int)$vm_id : (int)$this->opo_result_context->getParameter($ps_facet_name.'_browse_last_id'));
 			$this->view->setVar('facet', $va_facet);
 			$this->view->setVar('facet_info', $va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name));
 			$this->view->setVar('facet_name', $ps_facet_name);
 			
 			$this->view->setVar('individual_group_display', isset($va_facet_info['individual_group_display']) ? (bool)$va_facet_info['individual_group_display'] : false);
 			
 			
 			// generate type menu and type value list for related authority table facet
 			if ($va_facet_info['type'] === 'authority') {
				$t_model = $this->opo_datamodel->getTableInstance($va_facet_info['table']);
				if (method_exists($t_model, "getTypeList")) {
					$this->view->setVar('type_list', $t_model->getTypeList());
				}
				
				$t_rel_types = new ca_relationship_types();
				$this->view->setVar('relationship_type_list', $t_rel_types->getRelationshipInfo($va_facet_info['relationship_table']));
				
				$this->view->setVar('t_item', $t_model);
			}
			
			$this->view->setVar('t_subject', $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true));
				
			$this->opo_result_context->saveContext();
			if (isset($pa_options['view']) && $pa_options['view']) { 
				$vs_content = $this->render($pa_options['view']);
			} else {
				$vs_content = $this->render('Browse/ajax_browse_facet_html.php');
			}
			
			$this->opo_browse->setCachedFacetHTML($vs_cache_key, $vs_content);
 		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function getFacetHierarchyLevel() {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			if(!is_array($va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name))) { return null; }
 			
 			$va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
 			
 			$pn_id = $this->request->getParameter('id', pInteger);
 			//if ((!($pn_id = $this->request->getParameter('id', pInteger))) && method_exists($t_item, "getHierarchyList")) { 
 			//	$pn_id = $this->request->getParameter('root_item_id', pInteger);	
 			//}
 			
 			$va_json_data = array('_primaryKey' => 'id');
 			
 			switch($va_facet_info['type']) {
 				case 'attribute':
 					// is it a list attribute?
 					$t_element = new ca_metadata_elements();
 					if ($t_element->load(array('element_code' => $va_facet_info['element_code']))) {
 						if ($t_element->get('datatype') == 3) { // 3=list
							if (!$pn_id) {
								//if (!($pn_id = $this->opo_result_context->getParameter($ps_facet_name.'_browse_last_id'))) {
									$t_list = new ca_lists();
									$pn_id = $t_list->getRootListItemID($t_element->get('list_id'));
								//}
							}
							foreach($va_facet as $vn_i => $va_item) {
								if ($va_item['parent_id'] == $pn_id) {
									$va_item['name'] = $va_item['label'];
									$va_item['children'] = $va_item['child_count'];
									unset($va_item['label']);
									unset($va_item['child_count']);
									$va_json_data[$va_item['id']] = $va_item;
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
					if(!$pn_id) {
						$ps_table_name = $va_facet_info['table'];
						require_once(__CA_MODELS_DIR__.'/'.$ps_table_name.'.php');
						$t_item = new $ps_table_name($pn_id);
						$pn_id = $vn_root = $t_item->getHierarchyRootID();
						$va_hierarchy_list = $t_item->getHierarchyList(true);
						
						$vn_last_id = null;
						foreach($va_hierarchy_list as $vn_i => $va_item) {
							$va_item['id'] = $va_item[$t_item->primaryKey()];
							if (!isset($va_facet[$va_item['id']]) && ($vn_root == $va_item['id'])) { continue; }
							unset($va_item['item_id']);
							unset($va_item['parent_id']);
							unset($va_item['label']);
							$va_json_data[$va_item['id']] = $va_item;
							$vn_last_id = $va_item['id'];
						}
						if (sizeof($va_json_data) == 2) {	// if only one hierarchy root (root +  _primaryKey in array) then don't bother showing it
							$pn_id = $vn_last_id;
							unset($va_json_data[$vn_last_id]);
						}
					}
					if ($pn_id) {
						foreach($va_facet as $vn_i => $va_item) {
							if ($va_item['parent_id'] == $pn_id) {
								$va_item['name'] = $va_item['label'];
								$va_item['children'] = $va_item['child_count'];
								unset($va_item['label']);
								unset($va_item['child_count']);
								$va_json_data[$va_item['id']] = $va_item;
							}
						}
					}
					break;
			}
			
 			if (!trim($this->request->getParameter('init', pString))) {
				$this->opo_result_context->setParameter($ps_facet_name.'_browse_last_id', $pn_id);
				$this->opo_result_context->saveContext();
			}
 			
 			$this->view->setVar('facet_list', $va_json_data);
 		
 			return $this->render('Browse/facet_hierarchy_level_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of ancestors for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function getFacetHierarchyAncestorList() {
 			$pn_id = $this->request->getParameter('id', pInteger);
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			if(!is_array($va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name))) { return null; }
 			
 			$va_ancestors = array();
 			switch($va_facet_info['type']) {
 				case 'attribute':
 					// is it a list attribute?
 					$t_element = new ca_metadata_elements();
 					if ($t_element->load(array('element_code' => $va_facet_info['element_code']))) {
 						if ($t_element->get('datatype') == 3) { // 3=list
							if (!$pn_id) {
 								$t_list = new ca_lists();
								$pn_id = $t_list->getRootListItemID($t_element->get('list_id'));
							}
							$t_item = new ca_list_items($pn_id);
							
							if ($t_item->getPrimaryKey()) { 
								$va_ancestors = array_reverse($t_item->getHierarchyAncestors(null, array('includeSelf' => true, 'idsOnly' => true)));
								array_shift($va_ancestors);
							}
 						}
 					}
 					break;
 				case 'label':
 					// label facet
 					$va_facet_info['table'] = $this->ops_tablename;
 					// fall through to default case
 				default:
					$ps_table_name = $va_facet_info['table'];
					require_once(__CA_MODELS_DIR__.'/'.$ps_table_name.'.php');
					$t_item = new $ps_table_name($pn_id);
					
					if (method_exists($t_item, "getHierarchyList")) { 
						$va_access_values = caGetUserAccessValues($this->request);
						$va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
						$va_hierarchy_list = $t_item->getHierarchyList(true);
						
						$vn_hierarchies_in_use = 0;
						foreach($va_hierarchy_list as $vn_i => $va_item) {
							if (isset($va_facet[$va_item[$t_item->primaryKey()]])) { 
								$vn_hierarchies_in_use++;
								if ($vn_hierarchies_in_use > 1) { break; }
							}
						}
					}
 				
					if ($t_item->getPrimaryKey()) { 
						$va_ancestors = array_reverse($t_item->getHierarchyAncestors(null, array('includeSelf' => true, 'idsOnly' => true)));
					}
					if ($vn_hierarchies_in_use <= 1) {
						array_shift($va_ancestors);
					}
					break;
			}
			
 			$this->view->setVar('ancestors', $va_ancestors);
 			return $this->render('Browse/facet_hierarchy_ancestors_json.php');
 		}
 		# -------------------------------------------------------
 		public function addCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->addCriteria($ps_facet_name, array($pn_id = $this->request->getParameter('id', pString)));
 			$this->opo_result_context->setParameter($ps_facet_name.'_browse_last_id', $pn_id);
			$this->opo_result_context->saveContext();
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function clearAndAddCriteria() {
 			$this->opo_browse->removeAllCriteria();
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->addCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function modifyCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('mod_id', pString)));
 			$this->opo_browse->addCriteria($ps_facet_name, array($pn_id = $this->request->getParameter('id', pString)));
 			$this->opo_result_context->setParameter($ps_facet_name.'_browse_last_id', $pn_id);
			$this->opo_result_context->saveContext();
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function removeCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function clearCriteria() {
 			$this->opo_browse->removeAllCriteria();
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the browse will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function browseName($ps_mode='singular') {
 			// MUST BE OVERRIDDEN 
 			return "undefined";
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Returns summary of current browse parameters suitable for display.
 		 *
 		 * @return string Summary of current browse criteria ready for display
 		 */
 		public function getCriteriaForDisplay() {
 			$va_criteria = $this->opo_browse->getCriteriaWithLabels();
 			if (!sizeof($va_criteria)) { return ''; }
 			$va_criteria_info = $this->opo_browse->getInfoForFacets();
 			
 			$va_buf = array();
 			foreach($va_criteria as $vs_facet => $va_vals) {
 				$va_buf[] = caUcFirstUTF8Safe($va_criteria_info[$vs_facet]['label_singular']).': '.join(", ", $va_vals);
 			}
 			
 			return join("; ", $va_buf);
  		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Tools($pa_parameters) {
 			parent::Tools($pa_parameters, $po_search);
			
			$this->view->setVar('mode_type_singular', $this->browseName('singular'));
			$this->view->setVar('mode_type_plural', $this->browseName('plural'));

 			return $this->render('Browse/widget_'.$this->ops_tablename.'_browse_tools.php', true);
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generic action handler - used for any action that is not implemented
 		 * in the controller
 		 */
 		public function __call($ps_name, $pa_arguments) {
 			$ps_name = preg_replace('![^A-Za-z0-9_\-]!', '', $ps_name);
 			
			$this->view->setVar('browse', $this->opo_browse);
			$this->view->setVar('target', $this->ops_tablename);
			$this->view->setVar('result_context', $this->opo_result_context);
			
 			return $this->render('Browse/'.$ps_name.'_html.php');
 		}
 		# -------------------------------------------------------
 	}
?>