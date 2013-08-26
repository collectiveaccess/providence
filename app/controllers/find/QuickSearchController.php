<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/QuickSearchController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 			if (!in_array($ps_sort, array('name', 'idno'))) {
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
			$vs_default_actions["ca_tour_stops"] = ($this->request->user->canDoAction("can_edit_ca_tour_stops") ? "Edit" : "Summary");

 			$va_searches = array(
 				'ca_objects' 					=> array('name' => 'ca_object_labels.name', 'displayidno' => 'ca_objects.idno', 'idno' => 'ca_objects.idno_sort', 'displayname' => _t('Objects'), 'primary_key' => 'object_id', 'module' => 'editor/objects', 'controller' => 'ObjectEditor', 'action' => $vs_default_actions["ca_objects"], 'searchModule' => 'find', 'searchController' => 'SearchObjects', 'searchAction' => "Index"),
 				'ca_object_lots'				=> array('name' => 'ca_object_lot_labels.name', 'displayidno' => 'ca_object_lots.idno_stub', 'idno' => 'ca_object_lots.idno_stub_sort', 'displayname' => _t('Object lots'), 'primary_key' => 'lot_id', 'module' => 'editor/object_lots', 'controller' => 'ObjectLotEditor', 'action' => $vs_default_actions["ca_object_lots"], 'searchModule' => 'find', 'searchController' => 'SearchObjectLots', 'searchAction' => "Index"),
 				'ca_entities' 					=> array('name' => 'ca_entity_labels.surname;ca_entity_labels.forename', 'displayidno' => 'ca_entities.idno', 'idno' => 'ca_entities.idno_sort', 'displayname' => _t('Entities'), 'primary_key' => 'entity_id', 'module' => 'editor/entities', 'controller' => 'EntityEditor', 'action' => $vs_default_actions["ca_entities"], 'searchModule' => 'find', 'searchController' => 'SearchEntities', 'searchAction' => "Index"),
 				'ca_places' 					=> array('name' => 'ca_place_labels.name', 'displayidno' => 'ca_places.idno', 'idno' => 'ca_places.idno_sort',  'displayname' => _t('Places'), 'primary_key' => 'place_id', 'module' => 'editor/places', 'controller' => 'PlaceEditor', 'action' => $vs_default_actions["ca_places"], 'searchModule' => 'find', 'searchController' => 'SearchPlaces', 'searchAction' => "Index"),
 				'ca_occurrences' 			=> array('name' => 'ca_occurrence_labels.name', 'displayidno' => 'ca_occurrences.idno', 'idno' => 'ca_occurrences.idno_sort', 'displayname' => _t('Occurrences'), 'primary_key' => 'occurrence_id', 'module' => 'editor/occurrences', 'controller' => 'OccurrenceEditor', 'action' => $vs_default_actions["ca_occurrences"], 'searchModule' => 'find', 'searchController' => 'SearchOccurrences', 'searchAction' => "Index"),
 				'ca_collections' 				=> array('name' => 'ca_collection_labels.name', 'displayidno' => 'ca_collections.idno', 'idno' => 'ca_collections.idno_sort', 'displayname' => _t('Collections'), 'primary_key' => 'collection_id', 'module' => 'editor/collections', 'controller' => 'CollectionEditor', 'action' => $vs_default_actions["ca_collections"], 'searchModule' => 'find', 'searchController' => 'SearchCollections', 'searchAction' => "Index"),
 				'ca_storage_locations' 	=> array('name' => 'ca_storage_location_labels.name', 'displayidno' => '', 'idno' => '', 'displayname' => _t('Storage locations'), 'primary_key' => 'location_id', 'module' => 'editor/storage_locations', 'controller' => 'StorageLocationEditor', 'action' => $vs_default_actions["ca_storage_locations"], 'searchModule' => 'find', 'searchController' => 'SearchStorageLocations', 'searchAction' => "Index"),
 				'ca_loans' 						=> array('name' => 'ca_loan_labels.name', 'displayidno' => 'ca_loans.idno', 'idno' => 'ca_loans.idno_sort', 'displayname' => _t('Loans'), 'primary_key' => 'loan_id', 'module' => 'editor/loans', 'controller' => 'LoanEditor', 'action' => $vs_default_actions["ca_loans"], 'searchModule' => 'find', 'searchController' => 'SearchLoans', 'searchAction' => "Index"),
 				'ca_movements' 			=> array('name' => 'ca_movement_labels.name', 'displayidno' => 'ca_movements.idno', 'idno' => 'ca_movements.idno_sort', 'displayname' => _t('Movements'), 'primary_key' => 'movement_id', 'module' => 'editor/movements', 'controller' => 'MovementEditor', 'action' => $vs_default_actions["ca_movements"], 'searchModule' => 'find', 'searchController' => 'SearchMovements', 'searchAction' => "Index"),
 				'ca_tours'	 					=> array('name' => 'ca_tour_labels.name', 'displayidno' => 'ca_tours.tour_code', 'idno' => 'ca_tours.tour_code', 'displayname' => _t('Tours'), 'primary_key' => 'tour_id', 'module' => 'editor/tours', 'controller' => 'TourEditor', 'action' => $vs_default_actions["ca_tours"], 'searchModule' => 'find', 'searchController' => 'SearchTours', 'searchAction' => "Index"),
 				'ca_tour_stops' 				=> array('name' => 'ca_tour_stop_labels.name', 'displayidno' => 'ca_tour_stops.idno', 'idno' => 'ca_tour_stops.idno_sort', 'displayname' => _t('Tour stops'), 'primary_key' => 'stop_id', 'module' => 'editor/tour_stops', 'controller' => 'TourStopEditor', 'action' => $vs_default_actions["ca_tour_stops"], 'searchModule' => 'find', 'searchController' => 'SearchTourStops', 'searchAction' => "Index")
 			);
 			
 			$t_list = new ca_lists();
 			$this->view->setVar('occurrence_types', caExtractValuesByUserLocale($t_list->getItemsForList('occurrence_types')));
 			
 			if(sizeof($va_aps_in_search = caSearchGetAccessPoints($ps_search))) {
 				$va_aps = caSearchGetTablesForAccessPoints($va_aps_in_search);
 				$vb_uses_aps = true;
 			} else {
 				$vb_uses_aps = false;
 			}
 			$va_single_results = array();
 			$pn_multiple_results = 0;
 			foreach($va_searches as $vs_table => $va_sorts) {
 				if (($o_config->get($vs_table.'_disable')) || (($vs_table == 'ca_tour_stops') && $o_config->get('ca_tours_disable')) || ($vb_uses_aps && (!in_array($vs_table, $va_aps)))) { 
 					unset($va_searches[$vs_table]);
 					continue;
 				}
 			 	$vo_result = $this->_doSearch($vs_table, $ps_search, $va_sorts[$ps_sort]);
 			 	$vo_result->setOption('prefetch', $this->opn_num_results_per_item_type);							// get everything we need in one pass
 			 	$vo_result->setOption('dontPrefetchAttributes', true);		// don't bother trying to prefetch attributes as we don't need them
 				$this->view->setVar($vs_table.'_results', $vo_result);
 				
 				$va_found_item_ids = array();
 				while($vo_result->nextHit()) {
					$va_found_item_ids[] = $vo_result->get($va_sorts['primary_key']);
				}
				$vo_result->seek(0);
 				$o_result_context = new ResultContext($this->request, $vs_table, 'quick_search');
 				$o_result_context->setAsLastFind();
				$o_result_context->setResultList($va_found_item_ids);
				$o_result_context->saveContext();
				if($vo_result->numHits() > 0){
					if ($vo_result->numHits() == 1) {
						$va_single_results[$vs_table] = $va_found_item_ids[0];
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
 				foreach($va_single_results as $vs_table => $vn_id) {
 					$this->response->setRedirect(caEditorUrl($this->request, $vs_table, $vn_id));
 					return;
 				}
 			}
 					
 			$this->render('Results/quick_search_results_html.php');
 		}
 		# -------------------------------------------------------
 		private function _doSearch($ps_type, $ps_search, $ps_sort) {
 			
 			$vb_no_cache = (bool)$this->request->getParameter('no_cache', pInteger);
 			
 			switch($ps_type) {
 				case 'ca_objects':
 					$o_object_search = new ObjectSearch();
 					return $o_object_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_object_lots':
					$o_object_lots_search = new ObjectLotSearch();
					return $o_object_lots_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_entities':
					$o_entity_search = new EntitySearch();
					return $o_entity_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_places':
					$o_place_search = new PlaceSearch();
					return $o_place_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_occurrences':
					$o_occurrence_search = new OccurrenceSearch();
					return $o_occurrence_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_collections':
					$o_collection_search = new CollectionSearch();
					return $o_collection_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_storage_locations':
					$o_storage_location_search = new StorageLocationSearch();
					return $o_storage_location_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_loans':
					$o_loan_search = new LoanSearch();
					return $o_loan_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_movements':
					$o_movement_search = new MovementSearch();
					return $o_movement_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_tours':
					$o_tour_search = new TourSearch();
					return $o_tour_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				case 'ca_tour_stops':
					$o_tour_stop_search = new TourStopSearch();
					return $o_tour_stop_search->search($ps_search, array('sort' => $ps_sort, 'search_source' =>'Quick', 'limit' => $this->opn_num_results_per_item_type, 'no_cache' => $vb_no_cache));
					break;
				default:
					return null;
					break;
			}
 		}
 		# -------------------------------------------------------
 	}
 ?>
