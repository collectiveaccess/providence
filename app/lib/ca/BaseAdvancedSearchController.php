<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseAdvancedSearchController.php : base controller for advanced (form based) search interface
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
  
 	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
	require_once(__CA_LIB_DIR__."/ca/BaseRefineableSearchController.php");
	require_once(__CA_LIB_DIR__."/ca/Browse/ObjectBrowse.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_search_forms.php");
	require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');
 	
 	class BaseAdvancedSearchController extends BaseRefineableSearchController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = false;
 		protected $opo_datamodel;
 		protected $ops_find_type;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			$va_sortable_elements = ca_metadata_elements::getSortableElements($this->ops_tablename, $this->opn_type_restriction_id);
 			
 			$this->opa_sorts = array();
 			foreach($va_sortable_elements as $vn_element_id => $va_sortable_element) {
 				$this->opa_sorts[$this->ops_tablename.'.'.$va_sortable_element['element_code']] = $va_sortable_element['display_label'];
 			}
 		}
 		# -------------------------------------------------------
 		public function Index($pa_options=null) {
 			$po_search = (isset($pa_options['search']) && $pa_options['search']) ? $pa_options['search'] : null;
 			parent::Index($pa_options);
 			JavascriptLoadManager::register('browsable');	// need this to support browse panel when filtering/refining search results
 			
 			$t_model = $this->opo_datamodel->getTableInstance($this->ops_tablename, true);
 			
 			// Get elements of result context
 			$vn_page_num 			= $this->opo_result_context->getCurrentResultsPageNumber();
 			$vs_search 				= $this->opo_result_context->getSearchExpression();
 			if (!$vn_items_per_page = $this->opo_result_context->getItemsPerPage()) { $vn_items_per_page = $this->opa_items_per_page[0]; }
 			if (!$vs_view 			= $this->opo_result_context->getCurrentView()) { 
 				$va_tmp = array_keys($this->opa_views);
 				$vs_view = array_shift($va_tmp); 
 			}
 			if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) { 
 				$va_tmp = array_keys($this->opa_sorts);
 				$vs_sort = array_shift($va_tmp); 
 			}
			$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();
			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay();

 			if (!$this->opn_type_restriction_id) { $this->opn_type_restriction_id = ''; }
 			$this->view->setVar('type_id', $this->opn_type_restriction_id);
 			
 			MetaTagManager::setWindowTitle(_t('%1 advanced search', $this->searchName('plural')));
 			
 			$t_form = new ca_search_forms();
 			if (!($vn_form_id = (isset($pa_options['form_id'])) ? $pa_options['form_id'] : null)) {
				if (!($vn_form_id = $this->opo_result_context->getParameter('form_id'))) {
					if (sizeof($va_forms = $t_form->getForms(array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_SEARCH_FORM_READ_ACCESS__)))) {
						$va_tmp = array_keys($va_forms);
						$vn_form_id = array_shift($va_tmp);
					}
				}
			}
 			
 			$t_form->load($vn_form_id);
 			$this->view->setVar('t_form', $t_form);
 			$this->view->setVar('form_id', $vn_form_id);
 			
 			if ($pa_options['appendToSearch']) {
 				$vs_append_to_search .= " AND (".$pa_options['appendToSearch'].")";
 			}
 			
			//
			// Execute the search
			//
			if (isset($pa_options['saved_search']) && $pa_options['saved_search']) {
				// Is this a saved search? If so, reused the canned params
				$va_form_data = $pa_options['saved_search'];
 				foreach($pa_options['saved_search'] as $vs_fld => $vs_val) {
 					$vs_proc_fld = str_replace(".", "_", $vs_fld);
 					$va_proc_form_data[$vs_proc_fld] = $vs_val;
 				}
 				$vs_search = $t_form->getLuceneQueryStringForHTMLFormInput($va_proc_form_data);
 				$vb_is_new_search = true;
 			} else {
				if (!($vs_search = $t_form->getLuceneQueryStringForHTMLFormInput($_REQUEST))) { // try to get search off of request
					$vs_search = $this->opo_result_context->getSearchExpression();				// get the search out of the result context
					$va_form_data = $this->opo_result_context->getParameter('form_data');
					$vb_is_new_search = !$this->opo_result_context->cacheIsValid();
				} else {
					$va_form_data = $t_form->extractFormValuesFromArray($_REQUEST);				// ah ok, its an incoming request, so get the form values out for interpretation/processing/whatever
					$vb_is_new_search = true;
				}
			}
			
			if ($this->request->getParameter('reset', pString) == 'clear') {
 				$vs_search = '';
 				$vb_is_new_search = true;
 			}
 			
			if($vs_search && ($vs_search != "")){ /* any request? */
				$va_search_opts = array(
					'sort' => $vs_sort, 
					'sort_direction' => $vs_sort_direction, 
					'appendToSearch' => $vs_append_to_search, 
					'getCountsByField' => 'type_id',
					'checkAccess' => $va_access_values,
					'no_cache' => $vb_is_new_search
				);
				
				if ($vb_is_new_search ||isset($pa_options['saved_search']) || (is_subclass_of($po_search, "BrowseEngine") && !$po_search->numCriteria()) ) {
					$vs_browse_classname = get_class($po_search);
 					$this->opo_browse = $po_search = new $vs_browse_classname;
 					$po_search->addCriteria('_search', $vs_search);
 				}
 				
 				if ($this->opn_type_restriction_id) {
 					$po_search->setTypeRestrictions(array($this->opn_type_restriction_id));
 				}
 				
 				$vb_criteria_have_changed = false;
 				if (is_subclass_of($po_search, "BrowseEngine")) {
 					$vb_criteria_have_changed = $po_search->criteriaHaveChanged();
					$po_search->execute($va_search_opts);
					$this->opo_result_context->setParameter('browse_id', $po_search->getBrowseID());
					$vo_result = $po_search->getResults($va_search_opts);
				} else {
					$vo_result = $po_search->search($vs_search, $va_search_opts);
				}
				$this->opo_result_context->validateCache();
				
				// Only prefetch what we need
				$vo_result->setOption('prefetch', $vn_items_per_page);
 				
 				$this->opo_result_context->setParameter('form_data', $va_form_data);
 				$this->opo_result_context->setSearchExpression($vs_search);
 				
 				if($vb_is_new_search || $vb_criteria_have_changed) {
 					$this->opo_result_context->setResultList($vo_result->getPrimaryKeyValues());
					
					if ($this->opo_result_context->searchExpressionHasChanged()) { $vn_page_num = 1; }
				}
 				
 				$vo_result->seek(($vn_page_num - 1) * $vn_items_per_page);
 				
 				$this->view->setVar('num_hits', $vo_result->numHits());
 				$this->view->setVar('num_pages', $vn_num_pages = ceil($vo_result->numHits()/$vn_items_per_page));
 				if ($vn_page_num > $vn_num_pages) { $vn_page_num = 1; }
 				
 				$this->view->setVar('page', $vn_page_num);
 				$this->view->setVar('search', $vs_search);
 				$this->view->setVar('result', $vo_result);
 			}
 			
 			//
 			// Set up view for display of results
 			//
			$t_model = $this->opo_datamodel->getTableInstance($this->ops_tablename);
								
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
			$this->view->setVar('browse', $po_search);
			
 			switch($pa_options['output_format']) {
 				# ------------------------------------
 				case 'PDF':
 					$this->_genPDF($vo_result, $this->request->getParameter("label_form", pString), $vs_search);
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
					
					// Call advanced search form generator directly to set view vars in the current view
					// This lets our view in this action render Search/search_advanced_form_html.php directly
					// to avoid the annoying flicker that occurs if we load the initial search form via AJAX
					$this->getAdvancedSearchForm(false);
					
					$this->opo_result_context->setAsLastFind();
					$this->opo_result_context->saveContext();
					
					
					$this->render('Search/'.$this->ops_tablename.'_search_advanced_html.php');
					break;
				# ------------------------------------
			}				
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
		# Ajax
		# -------------------------------------------------------
		public function getAdvancedSearchForm($pb_render_view=true) {
			$t_form = new ca_search_forms();
 			if (!$vn_form_id = $this->request->getParameter('form_id', pInteger)) {
 				if ((!($vn_form_id = $this->opo_result_context->getParameter('form_id'))) || (!$t_form->haveAccessToForm($this->request->getUserID(), __CA_SEARCH_FORM_READ_ACCESS__, $vn_form_id))) {
 					if (sizeof($va_forms = $t_form->getForms(array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_SEARCH_FORM_READ_ACCESS__)))) {
 						$va_tmp = array_keys($va_forms);
 						$vn_form_id = array_shift($va_tmp);
 					}
 				}
 			}
 			
 			$t_form->load($vn_form_id);
 			
 			$this->opo_result_context->setParameter('form_id', $vn_form_id);
 			$va_form_data = $this->opo_result_context->getParameter('form_data');
 			if ($this->request->getParameter('reset', pString) == 'clear') {
 				$va_form_data = array();
 			}
 			
 			$this->view->setVar('form_data', $va_form_data);
 			$this->view->setVar('form_elements', $t_form->getHTMLFormElements($this->request, $va_form_data));
 			$this->view->setVar('t_form', $t_form);
 			$this->view->setVar('settings', $t_form->getSettings());
 			$this->view->setVar('form_id', $vn_form_id);
 			
			$this->view->setVar('table_name', $this->ops_tablename);
 			
			$this->opo_result_context->setAsLastFind();
			$this->opo_result_context->saveContext();
			
			if ($pb_render_view) { $this->render('Search/search_advanced_form_html.php'); }
		}
		# ------------------------------------------------------------------
 		/**
 		 * Returns summary of current advanced search parameters suitable for display.
 		 *
 		 * @return string Summary of current search criteria ready for display
 		 */
 		public function getCriteriaForDisplay($pn_form_id=null) {
 			$t_form = new ca_search_forms();
 			if (!($vn_form_id = $pn_form_id)) {
 				if ((!($vn_form_id = $this->opo_result_context->getParameter('form_id'))) || (!$t_form->haveAccessToForm($this->request->getUserID(), __CA_SEARCH_FORM_READ_ACCESS__, $vn_form_id))) {
 					if (sizeof($va_forms = $t_form->getForms(array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_SEARCH_FORM_READ_ACCESS__)))) {
 						$va_tmp = array_keys($va_forms);
 						$vn_form_id = array_shift($va_tmp);
 					}
 				}
 			}
 			
 			$t_form->load($vn_form_id);
 			
 			$va_form_data = $this->opo_result_context->getParameter('form_data');
 			
 			$va_buf = array();
 			if (!($t_model = $this->opo_datamodel->getTableInstance($this->ops_tablename, true))) { return '?'; }
 			foreach($va_form_data as $vs_bundle => $vs_value) {
 				if (!trim($vs_value)) { continue; }
 				$va_buf[] = $t_model->getDisplayLabel($vs_bundle).": ".$vs_value;
 			}
 			
 			return join("; ", $va_buf);
  		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Tools($pa_parameters) {
 			parent::Tools($pa_parameters, $po_search);

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