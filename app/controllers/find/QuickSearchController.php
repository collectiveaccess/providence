<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/QuickSearchController.php :
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
 * ----------------------------------------------------------------------
 */
 	
 	require_once(__CA_LIB_DIR__."/core/Configuration.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseFindController.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/ObjectSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/ObjectLotSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/EntitySearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/PlaceSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/OccurrenceSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/CollectionSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/StorageLocationSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/LoanSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/MovementSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/TourSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/TourStopSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/QuickSearch.php");
 	
 	require_once(__CA_MODELS_DIR__."/ca_lists.php");
 	
 	class QuickSearchController extends BaseFindController {
 		# -------------------------------------------------------
 		private $opn_num_results_per_item_type = 100;
 		
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function Index($pa_options=null) {
 			$ps_search 		= $this->request->getParameter('search', pString);
 			$ps_sort 		= $this->request->getParameter('sort', pString);
 			
 			if (!$ps_search) { $ps_search = $this->request->session->getVar('quick_search_last_search'); }
 			if (!in_array($ps_sort, array('name', 'idno', 'relevance'))) {
 				if (!$ps_sort = $this->request->session->getVar('quick_search_last_sort')) {
 					$ps_sort = 'name';
 				}
 			}
 			
 			MetaTagManager::setWindowTitle(_t('Quick search'));
 			
 			$o_config = Configuration::load();

			$vs_default_actions["ca_objects"] = ($this->request->user->canDoAction("can_edit_ca_objects") ? "Edit" : "Summary");
			$vs_default_actions["ca_object_lots"] = ($this->request->user->canDoAction("can_edit_ca_object_lots") ? "Edit" : "Summary");
			$vs_default_actions["ca_entities"] = ($this->request->user->canDoAction("can_edit_ca_entities") ? "Edit" : "Summary");
			$vs_default_actions["ca_places"] = ($this->request->user->canDoAction("can_edit_ca_places") ? "Edit" : "Summary");
			$vs_default_actions["ca_occurrences"] = ($this->request->user->canDoAction("can_edit_ca_occurrences") ? "Edit" : "Summary");
			$vs_default_actions["ca_collections"] = ($this->request->user->canDoAction("can_edit_ca_collections") ? "Edit" : "Summary");
			$vs_default_actions["ca_storage_locations"] = ($this->request->user->canDoAction("can_edit_ca_storage_locations") ? "Edit" : "Summary");
			$vs_default_actions["ca_loans"] = ($this->request->user->canDoAction("can_edit_ca_loans") ? "Edit" : "Summary");
			$vs_default_actions["ca_movements"] = ($this->request->user->canDoAction("can_edit_ca_movements") ? "Edit" : "Summary");
			$vs_default_actions["ca_tours"] = ($this->request->user->canDoAction("can_edit_ca_tours") ? "Edit" : "Summary");
			$vs_default_actions["ca_tour_stops"] = ($this->request->user->canDoAction("can_edit_ca_tours") ? "Edit" : "Summary");

 			$va_searches = QuickSearch::getSearches($this->request->user);
 			
 			$t_list = new ca_lists();
 			$this->view->setVar('occurrence_types', caExtractValuesByUserLocale($t_list->getItemsForList('occurrence_types')));
 			
 			$va_single_results = [];
 			$pn_multiple_results = 0;
 			foreach($va_searches as $vs_target => $va_sorts) {
 				$va_table = explode('/', $vs_target);
 				$vs_table = $va_table[0]; $vs_type = (isset($va_table[1])) ? $va_table[1] : null;
 				
 				if (($o_config->get($vs_table.'_disable')) || (($vs_table == 'ca_tour_stops') && $o_config->get('ca_tours_disable'))) {
 					unset($va_searches[$vs_target]);
 					continue;
 				}
 			 	if (!($vo_result = $this->_doSearch($vs_table, $ps_search, $va_sorts[$ps_sort], $vs_type))) { unset($va_searches[$vs_target]); continue; }
 			 	$vo_result->setOption('prefetch', $this->opn_num_results_per_item_type);	// get everything we need in one pass
 			 	$vo_result->setOption('dontPrefetchAttributes', true);						// don't bother trying to prefetch attributes as we don't need them
 				$this->view->setVar("{$vs_target}_results", $vo_result);
 				
 				$va_found_item_ids = [];
 				while($vo_result->nextHit()) {
					$va_found_item_ids[] = $vo_result->get($va_sorts['primary_key']);
				}
				$vo_result->seek(0);
 				$o_result_context = new ResultContext($this->request, $vs_table, 'quick_search', $vs_type);
 				$o_result_context->setAsLastFind();
				$o_result_context->setResultList($va_found_item_ids);
				$o_result_context->saveContext();
				if($vo_result->numHits() > 0){
					if ($vo_result->numHits() == 1) {
						$va_single_results[$vs_target] = $va_found_item_ids[0];
					}else{
						$pn_multiple_results = 1;
					}
				}
 			}
 			$this->view->setVar('searches', $va_searches);
 			
 			// note last quick search
 			if ($ps_search) {
 				$this->request->session->setVar('quick_search_last_search', $ps_search);
 			}
 			if($ps_sort) {
 				$this->request->session->setVar('quick_search_last_sort', $ps_sort);
 			}
 			$this->view->setVar('search', $ps_search);
 			$this->view->setVar('sort', $this->request->session->getVar('quick_search_last_sort'));
 					
 			$this->view->setVar('maxNumberResults', $this->opn_num_results_per_item_type);
 			
 			// did we find only a single result in a single table? If so, then redirect to that record instead of showing results
 			if ((!$pn_multiple_results) && (sizeof($va_single_results) == 1)) {
 				foreach($va_single_results as $vs_target => $vn_id) {
 					$va_table = explode("/", $vs_target);
 					$this->response->setRedirect(caEditorUrl($this->request, $va_table[0], $vn_id));
 					return;
 				}
 			}
 					
 			$this->render('Results/quick_search_results_html.php');
 		}
 		# -------------------------------------------------------
 		private function _doSearch($ps_target, $ps_search, $ps_sort, $ps_type=null) {
 			
 			$va_access_values = caGetUserAccessValues($this->request);
 			$vb_no_cache = (bool)$this->request->getParameter('no_cache', pInteger);
 			if (!$this->request->user->canDoAction('can_search_'.(($ps_target == 'ca_tour_stops') ? 'ca_tours' : $ps_target))) { return ''; }
 			switch($ps_target) {
 				case 'ca_objects':
 					$o_object_search = new ObjectSearch();
 					if ($ps_type) { $o_object_search->setTypeRestrictions($ps_type); }
 					return $o_object_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_object_lots':
					$o_object_lots_search = new ObjectLotSearch();
 					if ($ps_type) { $o_object_lots_search->setTypeRestrictions([$ps_type]); }
					return $o_object_lots_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_entities':
					$o_entity_search = new EntitySearch();
 					if ($ps_type) { $o_entity_search->setTypeRestrictions([$ps_type]); }
					return $o_entity_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_places':
					$o_place_search = new PlaceSearch();
 					if ($ps_type) { $o_place_search->setTypeRestrictions([$ps_type]); }
					return $o_place_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_occurrences':
					$o_occurrence_search = new OccurrenceSearch();
 					if ($ps_type) { $o_occurrence_search->setTypeRestrictions([$ps_type]); }
					return $o_occurrence_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_collections':
					$o_collection_search = new CollectionSearch();
 					if ($ps_type) { $o_collection_search->setTypeRestrictions([$ps_type]); }
					return $o_collection_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_storage_locations':
					$o_storage_location_search = new StorageLocationSearch();
 					if ($ps_type) { $o_storage_location_search->setTypeRestrictions([$ps_type]); }
					return $o_storage_location_search->search(($ps_search == '*') ? '(ca_storage_locations.is_enabled:1)' : '('.$ps_search.') AND (ca_storage_locations.is_enabled:1)', array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_loans':
					$o_loan_search = new LoanSearch();
 					if ($ps_type) { $o_loan_search->setTypeRestrictions([$ps_type]); }
					return $o_loan_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_movements':
					$o_movement_search = new MovementSearch();
 					if ($ps_type) { $o_movement_search->setTypeRestrictions([$ps_type]); }
					return $o_movement_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_tours':
					$o_tour_search = new TourSearch();
 					if ($ps_type) { $o_tour_search->setTypeRestrictions([$ps_type]); }
					return $o_tour_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				case 'ca_tour_stops':
					$o_tour_stop_search = new TourStopSearch();
 					if ($ps_type) { $o_tour_stop_search->setTypeRestrictions([$ps_type]); }
					return $o_tour_stop_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache, 'checkAccess' => $va_access_values));
					break;
				default:
					return null;
					break;
			}
 		}
 		# -------------------------------------------------------
 	}