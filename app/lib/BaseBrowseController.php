<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseBrowseController.php : base controller for browse interface
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2017 Whirl-i-Gig
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
	require_once(__CA_LIB_DIR__."/Datamodel.php");
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
			}
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Index($pa_options=null) {
 			$po_search = isset($pa_options['search']) ? $pa_options['search'] : null;
 			$pb_dont_render_view = (isset($pa_options['dontRenderView']) && (bool)$pa_options['dontRenderView']) ? true : false;
 			
 			parent::Index($pa_options);
 			AssetLoadManager::register('browsable');
			AssetLoadManager::register('hierBrowser');
 			
 			$va_access_values = caGetUserAccessValues($this->request);
 			
 			
 			
 			//
 			// Enforce type restriction, if defined
 			// 
 			if ($this->opn_type_restriction_id > 0) {
 				$this->opo_browse->setTypeRestrictions(array($this->opn_type_restriction_id));
 			}
 			
 			//
 			// Restrict facets to specific group for main browse landing page (if set in app.conf config)
 			// 			
 			if ($vs_facet_group = $this->request->config->get($this->ops_tablename.'_browse_facet_group')) {
 				$this->opo_browse->setFacetGroup($vs_facet_group);
 			}
 			
 			//
 			// Set useful values we'll need later
 			//
			$t_model = Datamodel::getInstanceByTableName($this->ops_tablename, true);
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

			$vb_sort_has_changed = $this->opo_result_context->sortHasChanged();
			
 			if (!$vn_page_num || $vb_criteria_have_changed) { $vn_page_num = 1; }
 			
 			// Do redirect directly to detail if configured to do so
 			if (
 				$this->opo_browse->criteriaHaveChanged() 
 				&& 
 				(is_array($va_criteria = $this->opo_browse->getCriteria()) && (sizeof($va_criteria) == 1))
 			) {
 				$va_tmp = array_keys($va_criteria);
  				
 				$va_tmp1 = array_keys($va_criteria[$va_tmp[0]]);
 				$va_facet_info = $this->opo_browse->getInfoForFacet($va_tmp[0]);
 				
 				if ($this->request->config->get('redirect_to_'.$va_facet_info['table'].'_detail_if_is_first_facet')) {
 					$t_table = Datamodel::getInstanceByTableName($va_facet_info['table'], true);
 					
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
 			
 			MetaTagManager::setWindowTitle(_t('%1 browse', $this->browseName('plural')));
 			
 			//
 			// Actually execute the browse - do the queries
 			//
 			//if ($vs_group_name = $this->request->config->get('browse_facet_group_for_'.$this->ops_tablename)) {
			//	$this->opo_browse->setFacetGroup($vs_group_name);
			//}
			//
			// Restrict facets to specific group (if set in app.conf config)
			// 			
			if ($vs_facet_group = $this->request->config->get($this->ops_tablename.(($this->opo_browse->numCriteria() < 1) ? '_browse_facet_group' : '_browse_refine_facet_group'))) {
				$this->opo_browse->setFacetGroup($vs_facet_group);
			}
 			$this->opo_browse->execute(array('checkAccess' => $va_access_values, 'no_cache' => !$this->opo_result_context->cacheIsValid(), 'rootRecordsOnly' => $this->view->getVar('hide_children')));
 			$this->opo_result_context->validateCache();
 			
			$this->opo_result_context->setSearchExpression($this->opo_browse->getBrowseID());
			
	
			if (!is_array($va_facets_with_info = $this->opo_browse->getInfoForAvailableFacets()) || !sizeof($va_facets_with_info)) {
				$this->view->setVar('open_refine_controls', false);
				$this->view->setVar('noRefineControls', true);
			}
 			
 			//
 			// Pass browse info (context + facets + criteria) to view
 			//
 			
 			
			$this->view->setVar('browse', $this->opo_browse);
			$this->view->setVar('target', $this->ops_tablename);
			$this->view->setVar('result_context', $this->opo_result_context);
			
 			$this->view->setVar('criteria', $va_criteria = $this->opo_browse->getCriteriaWithLabels());
 			$this->view->setVar('available_facets', $this->opo_browse->getInfoForAvailableFacets());
 			
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
				if ($vb_criteria_have_changed || $vb_sort_has_changed) {
					// Put the results id list into the results context - we used this for previous/next navigation
					$vo_full_result = $this->opo_browse->getResults(array('sort' => $vs_sort, 'sort_direction' => $vs_sort_direction));
					$this->opo_result_context->setResultList($vo_full_result->getPrimaryKeyValues());
					unset($vo_full_result);
					$this->opo_result_context->setParameter('availableVisualizationChecked', 0);
				}
				
				$vo_result->seek(0);	
			}
			
			//
 			// Set up view for display of results
 			// 			
 			
 			$this->view->setVar('start', ($vn_page_num - 1) * $vn_items_per_page);
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
	
						$va_row_headers[] = ($vn_item_count)." ".caEditorLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 2), 'caResultsEditorEditLink', $this->ops_tablename, $vn_id);
	
					}
				}
				
				$this->view->setVar('initialData', $va_initial_data);
				$this->view->setVar('rowHeaders', $va_row_headers);
			}
			
			$this->_setBottomLineValues($vo_result, $va_display_list, $t_display);
			
 			switch($pa_options['output_format']) {
 				# ------------------------------------
 				case 'LABELS':
 					$this->_genLabels($vo_result, $this->request->getParameter("label_form", pString), _t('Browse'), _t('Browse'));
 					break;
 				# ------------------------------------
 				case 'EXPORT':
 					$this->_genExport($vo_result, $this->request->getParameter("export_format", pString), _t('Browse'), _t('Browse'));
 					break;
				# ------------------------------------
				case 'EXPORTWITHMAPPING':
					$this->_genExportWithMapping($vo_result, $this->request->getParameter("exporter_id", pInteger));
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
 			
 			//if (($va_facet_info['group_mode'] != 'hierarchical') && ($vs_content = $this->opo_browse->getCachedFacetHTML($vs_cache_key))) { 
 			//	$this->response->addContent($vs_content);
 			//	return;
 			//}
 			
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
 			
 			$va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name);
 			if ($va_facet_info['type'] == 'attribute') {
 				// set table for authority element so browse can use table-specific services (Eg. entity lookups for attributes of type Entity)
 				if ($t_element = ca_metadata_elements::getInstance($va_facet_info['element_code'])) {
 					if ($t_authority = AuthorityAttributeValue::elementTypeToInstance($t_element->get('datatype'))) {
 						$va_facet_info['table'] = $t_authority->tableName();
 					}
 				}
 			}
 			$this->view->setVar('facet_info', $va_facet_info);
 			$this->view->setVar('facet_name', $ps_facet_name);
 			
 			$this->view->setVar('individual_group_display', isset($va_facet_info['individual_group_display']) ? (bool)$va_facet_info['individual_group_display'] : false);

 			// this should be 'facet' but we don't want to render all old 'ajax_browse_facet_html' views (pawtucket themes) unusable
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
			}
			
			$this->view->setVar('t_subject', Datamodel::getInstanceByTableName($this->ops_tablename, true));
				
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
 		 * Given a semicolon delimited list of item_id's (request parameter 'id') returns a list of direct 
 		 * children for use in the hierarchy browser. Returned data is JSON format.
 		 */
 		public function getFacetHierarchyLevel() {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			
 			$this->opo_browse->setTypeRestrictions(array($this->opn_type_restriction_id));
 			if(!is_array($va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name))) { return null; }
 			
 			$va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
 			
 			if ($va_facet_info['type'] == 'location') {
 				//
 				// Hierarchical display of current location facets is only available when pure storage location tracking (ie. only 
 				// locations, not loans, occurrences etc.) is configured. The keys of the location facet array are in the 
 				// form <table num>:<type id>:<row id> but the hierarchy level code below expects keys to be pure row_ids. Since 
 				// can assume the last value in the colon-delimited key is the storage location_id we rewrite the facet keys here 
 				// to use these values.
 				//
				$va_facet_proc = [];
				foreach($va_facet as $k => $v) {
					$id = array_pop(explode(':', $k));
					$va_facet_proc[$id] = $v;
				}
 				$va_facet = $va_facet_proc;
 			}
 			
 			$t_item = Datamodel::getInstanceByTableName($va_facet_info['table']);
 			
 			// Get list of level parents to generate lists for 
			$pa_ids = explode(";", $ps_ids = $this->request->getParameter('id', pString));
			
			if (!sizeof($pa_ids)) { $pa_ids = array(null); }
 			
			$va_level_data = array();
 	
 			if ((($vn_max_items_per_page = $this->request->getParameter('max', pInteger)) < 1) || ($vn_max_items_per_page > 1000)) {
				$vn_max_items_per_page = null;
			}
			
			$t_model = Datamodel::getInstanceByTableName($this->ops_tablename, true);	// browse target
			
			$o_config = Configuration::load();
			
			// Get level sort criteria
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
			
			// Get level sort direction
			if (!in_array($vs_sort_dir = strtolower($o_config->get($this->ops_tablename.'_hierarchy_browser_sort_direction')), array('asc', 'desc'))) { $vs_sort_dir = 'asc'; }
			
			$vn_count = 0;
			
 			// Generate list for each ID
 			foreach($pa_ids as $pn_id) {
 				$va_json_data = array('_primaryKey' => 'item_id');
				
				$va_tmp = explode(":", $pn_id);		// list id may have a starting index (to support paging)
				$vn_id = $va_tmp[0];
				$vn_start = (int)$va_tmp[1];
				if($vn_start < 0) { $vn_start = 0; }
				
 				switch($va_facet_info['type']) {
					case 'attribute':
						// is it a list attribute?
						$t_element = new ca_metadata_elements();
						if ($t_element->load(array('element_code' => $va_facet_info['element_code']))) {
							switch($t_element->get('datatype')) { 
								case __CA_ATTRIBUTE_VALUE_LIST__:
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
										// expand facet
										$va_ancestors = $t_item->getHierarchyAncestorsForIDs(array_keys($va_facet), array('returnAs' => 'ids', 'includeSelf' => true));
										while($qr_res->nextHit()) {
											$vn_parent_id = $qr_res->get('ca_list_items.parent_id');
											$vn_item_id = $qr_res->get('ca_list_items.item_id');
											$vn_access = $qr_res->get('ca_list_items.access');
											if (is_array($va_access_values) && !in_array($vn_access, $va_access_values)) { continue; }
											if (!is_array($va_ancestors) || !in_array($vn_item_id, $va_ancestors)) { continue; }
								
											$va_item = array();
											$va_item['item_id'] = $vn_item_id;
											$va_item['name'] = $qr_res->get('ca_list_items.preferred_labels');
											$va_item['children'] = (isset($va_child_counts[$vn_item_id]) && $va_child_counts[$vn_item_id]) ? $va_child_counts[$vn_item_id] : 0;
											$va_item['access'] = $vn_access;
											$va_item['is_enabled'] = $qr_res->get('ca_list_items.is_enabled');
											$va_json_data[$vn_item_id] = $va_item;
											$vn_count++;
										}
									}
									break;
							}
						}
						break;
					case 'label':
						// label facet
						$va_facet_info['table'] = $this->ops_tablename;
						// fall through to default case
					default:
						if (!$vn_id && ($t_item->getHierarchyType() == __CA_HIER_TYPE_SIMPLE_MONO__)) {
							// Force top level to be root for simple mono hierarchies
							$vn_id = $t_item->getHierarchyRootID();
						}
						if(!$vn_id) {
							$va_hier_ids = $this->opo_browse->getHierarchyIDsForFacet($ps_facet_name, array('checkAccess' => $va_access_values));
							
							if (is_array($va_hier_ids) && (sizeof($va_hier_ids) > 0) && $t_item) {
								$vn_root = $t_item->getHierarchyRootID();
								$va_hierarchy_list = $t_item->getHierarchyList(true);
							
								$vn_last_id = null;
								foreach($va_hierarchy_list as $vn_i => $va_item) {
									if (!in_array($vn_i, $va_hier_ids)) { continue; }	// only show hierarchies that have items in browse result
				
                                    $va_item['item_id'] = $va_item[$t_item->primaryKey()];
                                    if (!isset($va_facet[$va_item['item_id']]) && ($vn_root == $va_item['item_id'])) { continue; }
                                    if(is_array($va_access_values) && isset($va_item['access']) && (!in_array($va_item['access'], $va_access_values))) { continue; }
                                    unset($va_item['parent_id']);
                                    unset($va_item['label']);
                                    $va_json_data[$va_item['item_id']] = $va_item;
                                    $vn_last_id = $va_item['item_id'];
                                    
									if (!is_null($vn_max_items_per_page) && ($vn_c >= ($vn_max_items_per_page + $vn_start))) { break; }
								}
								if (sizeof($va_json_data) == 2) {	// if only one hierarchy root (root +  _primaryKey in array) then don't bother showing it
									$vn_id = $vn_last_id;
									unset($va_json_data[$vn_last_id]);
								}
							}
						}
						if ($vn_id) {
							$t_item = Datamodel::getInstanceByTableName($va_facet_info['table']);
							$t_item->load($vn_id);
							
							$vn_c = 0;
							$va_children = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
							$va_child_counts = $t_item->getHierarchyChildCountsForIDs($va_children);
							$qr_res = caMakeSearchResult($vs_rel_table = $t_item->tableName(), $va_children);
		                    $vn_count += $qr_res->numHits();
						
							$vs_pk = $t_item->primaryKey();
							if ($qr_res) {
								// expand facet
								$va_ancestors = $t_item->getHierarchyAncestorsForIDs(array_keys($va_facet), array('returnAs' => 'ids'));
								
								$vn_c = 0;
								while($qr_res->nextHit()) {
								    if ($vn_start > $vn_c) { $vn_c++; continue; }
									$vn_parent_id = $qr_res->get("{$vs_rel_table}.parent_id");
									$vn_item_id = $qr_res->get("{$vs_rel_table}.{$vs_pk}");
									
									if (!isset($va_facet[$vn_item_id]) && !in_array($vn_item_id, $va_ancestors)) { continue; } 
									
									$va_item = array();
									$va_item['item_id'] = $vn_item_id;
									$va_item['name'] = $qr_res->get("{$vs_rel_table}.preferred_labels");
									$va_item['children'] = (isset($va_child_counts[$vn_item_id]) && $va_child_counts[$vn_item_id]) ? $va_child_counts[$vn_item_id] : 0;
									$va_json_data[$vn_item_id] = $va_item;
									
									$vn_c++;
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
				$va_json_data['_itemCount'] = $vn_count;
				$va_json_data['_sortOrder'] = array_keys($va_json_data);
				$va_json_data['_primaryKey'] = $t_item->primaryKey();	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
 				
 				
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
					$t_item = Datamodel::getInstanceByTableName($va_facet_info['table']);
					$t_item->load($pn_id);
					
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
					    $va_ancestors = $t_item->getHierarchyAncestors(null, array('includeSelf' => true, 'idsOnly' => true));
						if (!is_array($va_ancestors)) { $va_ancestors = array(); }
						$va_ancestors = array_reverse($va_ancestors);
					}
					if (($vn_hierarchies_in_use <= 1) && ($t_item->getProperty('HIERARCHY_TYPE') !== __CA_HIER_TYPE_ADHOC_MONO__)) {
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
 			$this->opo_browse->addCriteria($ps_facet_name, $pa_ids = explode('|', $this->request->getParameter('id', pString)));
 			$this->opo_result_context->setParameter($ps_facet_name.'_browse_last_id', array_shift($pa_ids));
			$this->opo_result_context->saveContext();
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function clearAndAddCriteria() {
 			$this->opo_browse->removeAllCriteria();
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->addCriteria($ps_facet_name, explode('|', $this->request->getParameter('id', pString)));
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function modifyCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('mod_id', pString)));
 			$this->opo_browse->addCriteria($ps_facet_name, $pa_ids = explode('|', $this->request->getParameter('id', pString)));
 			$this->opo_result_context->setParameter($ps_facet_name.'_browse_last_id', array_shift($pa_ids));
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
 			return $this->getResultsDisplayName($ps_mode);
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
 		/**
 		 *
 		 */ 
 		public function getPartialResult($pa_options=null) {
 			$pa_options['search'] = $this->opo_browse;
 			return parent::getPartialResult($pa_options);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Tools($pa_parameters) {
 			parent::Tools($pa_parameters);
			
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
