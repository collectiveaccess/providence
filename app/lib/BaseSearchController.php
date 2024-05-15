<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseSearchController.php : base controller for search interface
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
 * @package CollectiveAccess
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/BaseRefineableSearchController.php");
require_once(__CA_LIB_DIR__."/Browse/ObjectBrowse.php");
require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
require_once(__CA_LIB_DIR__.'/Media/MediaViewerManager.php');

class BaseSearchController extends BaseRefineableSearchController {
	# -------------------------------------------------------
	protected $opb_uses_hierarchy_browser = false;
	protected $ops_find_type;
	
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	
		if ($po_request->config->get($this->ops_tablename.'_disable_basic_search')) {
			throw new ApplicationException(_t('Basic search interface is disabled'));
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
		
		AssetLoadManager::register('hierBrowser');
		AssetLoadManager::register('browsable');	// need this to support browse panel when filtering/refining search results
		$t_model = Datamodel::getInstanceByTableName($this->ops_tablename, true);
		$va_access_values = caGetUserAccessValues($this->request);
		
		// Get elements of result context
		$vn_page_num 			= $this->opo_result_context->getCurrentResultsPageNumber();
		$vs_search 				= html_entity_decode($this->opo_result_context->getSearchExpression());	// decode entities encoded to avoid Apache request parsing issues (Eg. forward slashes [/] in searches) 
		$vb_is_new_search		= $this->opo_result_context->isNewSearch();
		
		$vo_result = null;
		
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
		
		if($vb_is_new_search && ($default_sort = $this->request->config->get($this->ops_tablename.'_reset_sort_on_new_search'))) {
			$this->opo_result_context->setCurrentSort($default_sort);
			$this->opo_result_context->setCurrentSortDirection('ASC');
		}
		
		if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) { 
			$va_tmp = array_keys($this->opa_sorts);
			$vs_sort = array_shift($va_tmp);
		}
		
		$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();

		$vb_sort_has_changed = $this->opo_result_context->sortHasChanged();
		
		if (!$this->opn_type_restriction_id) { $this->opn_type_restriction_id = ''; }
		$this->view->setVar('type_id', $this->opn_type_restriction_id);
		
		MetaTagManager::setWindowTitle(_t('%1 search', $this->searchName('plural')));
		
		$vs_append_to_search = '';
		if ($pa_options['appendToSearch'] ?? false) {
			$vs_append_to_search .= " AND (".$pa_options['appendToSearch'].")";
		}
		//
		// Execute the search
		//
		$vs_search_with_suffix = caMatchOnStem($vs_search);
		
		if($vs_search){ /* any request? */
			if(is_array($va_set_ids = caSearchIsForSets($vs_search))) {
				// When search includes sets we add sort options for the references sets...
				foreach($va_set_ids as $vn_set_id => $vs_set_name) {
					$this->opa_sorts["ca_sets.set_id:{$vn_set_id}"] = _t("Set order: %1", $vs_set_name);
				}
				
				// ... and default the sort to the set
				//if ($vb_is_new_search) {
				//	$this->opo_result_context->setCurrentSort($vs_sort = "ca_sets.set_id:{$vn_set_id}");
				//}
			}
			
			$va_search_opts = array(
				'sort' => $vs_sort, 
				'sort_direction' => $vs_sort_direction, 
				'appendToSearch' => $vs_append_to_search,
				'checkAccess' => $va_access_values,
				'no_cache' => $vb_is_new_search || !$this->opo_result_context->cacheIsValid(),
				'dontCheckFacetAvailability' => true,
				'filterNonPrimaryRepresentations' => true,
				'rootRecordsOnly' => $this->view->getVar('hide_children'),
				'filterDeaccessionedRecords' => $this->view->getVar('hide_deaccession'),
				'throwExceptions' => !caGetOption('error', $pa_options, false)
			);
			
			if ($vb_is_new_search ||isset($pa_options['saved_search']) || (is_subclass_of($po_search, "BrowseEngine") && !$po_search->numCriteria()) ) {
				$vs_browse_classname = get_class($po_search);
				$po_search = new $vs_browse_classname;
				if (is_subclass_of($po_search, "BrowseEngine")) {
					$po_search->addCriteria('_search', $vs_search_with_suffix);
					
					if (method_exists($this, "hookBeforeNewSearch")) {
						$this->hookBeforeNewSearch($po_search);
					}
				}
				
				$this->opo_result_context->setParameter('show_type_id', null);
			}
			if ($this->opn_type_restriction_id > 0) {
				$exclude_type_ids = caMakeTypeIDList($this->ops_tablename, $this->request->config->getList($this->ops_tablename.'_find_dont_expand_hierarchically'), ['dontIncludeSubtypesInTypeRestriction' => true]);
				$po_search->setTypeRestrictions([$this->opn_type_restriction_id], ['dontExpandHierarchically' => in_array($this->opn_type_restriction_id, $exclude_type_ids)]);
			}
		
	try {	
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
				
				if((!$vo_result || !$vo_result->numHits()) && $po_search->numCriteria() > 1) {
					$po_search->removeAllCriteria();
					$po_search->addCriteria('_search', $vs_search_with_suffix);
					$po_search->execute($va_search_opts);
					$vo_result = $po_search->getResults($va_search_opts);
				}
		
				$n = (isset($pa_options['result']) && is_a($pa_options['result'], 'SearchResult')) ? $pa_options['result']->numHits() : $vo_result->numHits();
			
				if ($vn_page_num > ceil($n / $vn_items_per_page)) { 
					$this->opo_result_context->setCurrentResultsPageNumber($vn_page_num = 1);	// reset page count if out of bounds
				}
				
				if (!is_array($va_facets_with_info = $po_search->getInfoForAvailableFacets()) || !sizeof($va_facets_with_info)) {
					$this->view->setVar('open_refine_controls', false);
					$this->view->setVar('noRefineControls', false); 
				}
				
			} elseif($po_search) {
				$vo_result = $po_search->search($vs_search_with_suffix, $va_search_opts);
			}
	} catch (SearchException $e) {
		$this->notification->addNotification($e->getMessage(), __NOTIFICATION_TYPE_ERROR__);
		return $this->Index(['error' => true]);
	}
	
			$vo_result = isset($pa_options['result']) ? $pa_options['result'] : $vo_result;

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
	
			if($vb_is_new_search || $vb_criteria_have_changed || $vb_sort_has_changed || $this->type_restriction_has_changed) {
				$this->opo_result_context->setResultList($vo_result->getPrimaryKeyValues());
				$this->opo_result_context->setParameter('availableVisualizationChecked', 0);
				//if ($this->opo_result_context->searchExpressionHasChanged()) { $vn_page_num = 1; }
				$vn_page_num = 1; 
			}
			$this->view->setVar('num_hits', $vo_result->numHits());
			$this->view->setVar('num_pages', $vn_num_pages = ceil($vo_result->numHits()/$vn_items_per_page));
			if ($vn_page_num > $vn_num_pages) { $vn_page_num = 1; }
			
			$this->view->setVar('start', $start = ($vn_page_num - 1) * $vn_items_per_page);
			$vo_result->seek($start);
			$this->view->setVar('page', $vn_page_num);
			$this->view->setVar('search', $vs_search);
			$this->view->setVar('result', $vo_result);
			
			$result_desc = [];
			if($this->request->user->getPreference('show_search_result_desc') === 'show') {
				$page_hits = caGetHitsForPage($vo_result, $start, $vn_items_per_page);
				$result_desc = $po_search->getResultDesc($page_hits);
			}
			$this->view->setVar('result_desc', $result_desc);
			$this->opo_result_context->setResultDesc($result_desc);
			
		}
		//
		// Set up view for display of results
		//
		$t_model = Datamodel::getInstanceByTableName($this->ops_tablename, true);
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
		if (!is_array($va_display_list = $this->view->getVar('display_list'))) { $va_display_list = array(); }
		
		$this->_setBottomLineValues($vo_result, $va_display_list, $t_display);
		
		switch($pa_options['output_format'] ?? null) {
			# ------------------------------------
			case 'LABELS':
				caExportAsLabels($this->request, $vo_result, $this->request->getParameter("label_form", pString), $vs_search, $vs_search, ['output' => 'STREAM', 'checkAccess' => $va_access_values, 'display' => $t_display]);
				break;
			# ------------------------------------
			case 'EXPORT':
				caExportResult($this->request, $vo_result, $this->request->getParameter("export_format", pString), $vs_search, ['output' => 'STREAM', 'checkAccess' => $va_access_values, 'display' => $t_display, 'criteriaSummary' => $vs_search, 'browseCriteria' => $this->opo_browse->getCriteriaAsStrings(null, ['sense' => 'singular', 'returnAs' => 'array'])]);
				break;
			# ------------------------------------
			case 'HTML': 
			default:
				// generate type menu and type value list
				if (method_exists($t_model, "getTypeList")) {
					$this->view->setVar('type_list', $t_model->getTypeList());
				}
				if ($this->opb_uses_hierarchy_browser) {
					AssetLoadManager::register('hierBrowser');
					
					// only for interfaces that use the hierarchy browser
					$t_list = new ca_lists();
					if ($vs_type_list_code = $t_model->getTypeListCode()) {
						$this->view->setVar('num_types', $t_list->numItemsInList($vs_type_list_code));
						$this->view->setVar('type_menu',  $t_list->getListAsHTMLFormElement($vs_type_list_code, 'type_id', array('id' => 'hierTypeList')));
					}
					
					// set last browse id for hierarchy browser
					$vn_id = ($this->request->config->get($this->ops_tablename.'_dont_remember_last_browse_item')) ? null : intval(Session::getVar($this->ops_tablename.'_browse_last_id'));
					if (!$t_model->load($vn_id)) { 
						$vn_id = null;
					} elseif ($t_model->get('deleted')) {
						$vn_id = $t_model->get('parent_id');
					}
					
					$this->view->setVar('browse_last_id', $vn_id);
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
		$t_subject = Datamodel::getInstanceByTableName($this->ops_tablename, true);
		
		$t_list = new ca_lists();
		$va_hier = caExtractValuesByUserLocale($t_list->getItemsForList($t_subject->getTypeListCode()));
		
		$va_restrict_to_types = null;
		if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
			$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_tablename, array('access' => __CA_BUNDLE_ACCESS_READONLY__));
		}
		
		$limit_to_types = $this->getRequest()->config->getList($this->ops_tablename.'_navigation_find_menu_limit_types_to');
		$exclude_types = $this->getRequest()->config->getList($this->ops_tablename.'_navigation_find_menu_exclude_types');

		$va_types = [];
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
				
				if(is_array($limit_to_types) && sizeof($limit_to_types) && !in_array($va_item['idno'], $limit_to_types)) { continue; }
				if(is_array($exclude_types) && sizeof($exclude_types) && in_array($va_item['idno'], $exclude_types)) { continue; }
				
				// does this item have sub-items?
				if (
					!$this->getRequest()->config->get($this->ops_tablename.'_navigation_find_menu_shows_top_level_types_only')
					&&
					(isset($va_item['item_id']) && isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']]))
				) {
					$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id, $va_restrict_to_types);
				} else {
					$va_subtypes = method_exists($this, "_getSubTypeActionNav") ? $this->_getSubTypeActionNav($va_item) : [];
				}
				$va_types[] = array(
					'displayName' => $va_item['name_plural'],
					'parameters' => array(
						'type_id' => $va_item['item_id']
					),
					'is_enabled' => $va_item['is_enabled'],
					'navigation' => $va_subtypes
				);
			}
		}
		return $va_types;
	}
	# ------------------------------------------------------------------
	private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id, $pa_restrict_to_types=null) {
		$va_subtypes = array();
		
		$limit_to_types = $this->getRequest()->config->getList($this->ops_tablename.'_navigation_find_menu_limit_types_to');
		$exclude_types = $this->getRequest()->config->getList($z=$this->ops_tablename.'_navigation_find_menu_exclude_types');
		
		foreach($pa_subtypes as $vn_i => $va_type) {
			if (is_array($pa_restrict_to_types) && !in_array($va_type['item_id'], $pa_restrict_to_types)) { continue; }
			if (isset($pa_types_by_parent_id[$va_type['item_id']]) && is_array($pa_types_by_parent_id[$va_type['item_id']])) {
				$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id, $pa_restrict_to_types);
			} else {
				$va_subsubtypes = array();
			}
			
			if(is_array($limit_to_types) && sizeof($limit_to_types) && !in_array($va_type['idno'], $limit_to_types)) { continue; }
			if(is_array($exclude_types) && sizeof($exclude_types) && in_array($va_type['idno'], $exclude_types)) { continue; }
			
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
	/**
	 * Returns string representing the name of the item the search will return
	 *
	 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
	 */
	public function searchName($ps_mode='singular') {
		return $this->getResultsDisplayName($ps_mode);
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
	/**
	 * QuickLook
	 */
	public function QuickLook() {
		$t_subject = Datamodel::getInstanceByTableName($this->ops_tablename, true);
		$vn_id = (int)$this->request->getParameter($t_subject->primaryKey(), pInteger);
		$t_subject->load($vn_id);
		if (!($vn_representation_id = (int)$this->request->getParameter('representation_id', pInteger))) {
			$vn_representation_id = $t_subject->getPrimaryRepresentationID();
		}
		$t_rep = new ca_object_representations($vn_representation_id);
		
		if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_rep->getMediaInfo('media', 'original', 'MIMETYPE')))) {
			// error: no viewer available
			die("Invalid viewer");
		}
		
		if(!$vn_id) {
			$this->postError(1100, _t('Invalid object/representation'), 'SearchObjectsController->QuickLook');
			return;
		}

		$this->response->addContent($vs_viewer_name::getViewerHTML(
			$this->request, 
			"representation:{$vn_representation_id}", 
			['context' => 'media_overlay', 't_instance' => $t_rep, 't_subject' => $t_subject, 'display' => caGetMediaDisplayInfo('media_overlay', $vs_mimetype)])
		);
	}
	# -------------------------------------------------------
}
