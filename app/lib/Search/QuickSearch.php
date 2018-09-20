<?php
/* ----------------------------------------------------------------------
 * app/lib/Search/QuickSearch.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_lists.php");
 
	class QuickSearch {
		# -------------------------------------------------------
		/**
		 * Return array with information about quick-search supported searches
		 *
		 * @param array $pa_options No options are supported
		 * @return array
		 */ 
		public static function availableSearches($pa_options=null) {
			$o_config = Configuration::load();
			
			# default order is 
			$va_default_order = $o_config->getList("quicksearch_default_results");
			
			# list of tables to display by table
			$va_breakout_by_type = $o_config->getList("quicksearch_breakout_by_type");
			
			$va_searches = [
 				'ca_collections' 				=> ['relevance' => '_natural', 'name' => 'ca_collection_labels.name', 'displayidno' => 'ca_collections.idno', 'idno' => 'ca_collections.idno_sort', 'displayname' => _t('Collections'), 'primary_key' => 'collection_id', 'module' => 'editor/collections', 'controller' => 'CollectionEditor', 'action' => $vs_default_actions["ca_collections"], 'searchModule' => 'find', 'searchController' => 'SearchCollections', 'searchAction' => "Index"],
 				'ca_objects' 					=> ['relevance' => '_natural', 'name' => 'ca_object_labels.name', 'displayidno' => 'ca_objects.idno', 'idno' => 'ca_objects.idno_sort', 'displayname' => _t('Objects'), 'primary_key' => 'object_id', 'module' => 'editor/objects', 'controller' => 'ObjectEditor', 'action' => $vs_default_actions["ca_objects"], 'searchModule' => 'find', 'searchController' => 'SearchObjects', 'searchAction' => "Index"],
 				'ca_object_lots'				=> ['relevance' => '_natural', 'name' => 'ca_object_lot_labels.name', 'displayidno' => 'ca_object_lots.idno_stub', 'idno' => 'ca_object_lots.idno_stub_sort', 'displayname' => _t('Object lots'), 'primary_key' => 'lot_id', 'module' => 'editor/object_lots', 'controller' => 'ObjectLotEditor', 'action' => $vs_default_actions["ca_object_lots"], 'searchModule' => 'find', 'searchController' => 'SearchObjectLots', 'searchAction' => "Index"],
 				'ca_entities' 					=> ['relevance' => '_natural', 'name' => 'ca_entity_labels.surname;ca_entity_labels.forename', 'displayidno' => 'ca_entities.idno', 'idno' => 'ca_entities.idno_sort', 'displayname' => _t('Entities'), 'primary_key' => 'entity_id', 'module' => 'editor/entities', 'controller' => 'EntityEditor', 'action' => $vs_default_actions["ca_entities"], 'searchModule' => 'find', 'searchController' => 'SearchEntities', 'searchAction' => "Index"],
 				'ca_places' 					=> ['relevance' => '_natural', 'name' => 'ca_place_labels.name', 'displayidno' => 'ca_places.idno', 'idno' => 'ca_places.idno_sort',  'displayname' => _t('Places'), 'primary_key' => 'place_id', 'module' => 'editor/places', 'controller' => 'PlaceEditor', 'action' => $vs_default_actions["ca_places"], 'searchModule' => 'find', 'searchController' => 'SearchPlaces', 'searchAction' => "Index"],
 				'ca_occurrences' 				=> ['relevance' => '_natural', 'name' => 'ca_occurrence_labels.name', 'displayidno' => 'ca_occurrences.idno', 'idno' => 'ca_occurrences.idno_sort', 'displayname' => _t('Occurrences'), 'primary_key' => 'occurrence_id', 'module' => 'editor/occurrences', 'controller' => 'OccurrenceEditor', 'action' => $vs_default_actions["ca_occurrences"], 'searchModule' => 'find', 'searchController' => 'SearchOccurrences', 'searchAction' => "Index"],
 				'ca_storage_locations' 			=> ['relevance' => '_natural', 'name' => 'ca_storage_location_labels.name', 'displayidno' => '', 'idno' => '', 'displayname' => _t('Storage locations'), 'primary_key' => 'location_id', 'module' => 'editor/storage_locations', 'controller' => 'StorageLocationEditor', 'action' => $vs_default_actions["ca_storage_locations"], 'searchModule' => 'find', 'searchController' => 'SearchStorageLocations', 'searchAction' => "Index"],
 				'ca_loans' 						=> ['relevance' => '_natural', 'name' => 'ca_loan_labels.name', 'displayidno' => 'ca_loans.idno', 'idno' => 'ca_loans.idno_sort', 'displayname' => _t('Loans'), 'primary_key' => 'loan_id', 'module' => 'editor/loans', 'controller' => 'LoanEditor', 'action' => $vs_default_actions["ca_loans"], 'searchModule' => 'find', 'searchController' => 'SearchLoans', 'searchAction' => "Index"],
 				'ca_movements' 					=> ['relevance' => '_natural', 'name' => 'ca_movement_labels.name', 'displayidno' => 'ca_movements.idno', 'idno' => 'ca_movements.idno_sort', 'displayname' => _t('Movements'), 'primary_key' => 'movement_id', 'module' => 'editor/movements', 'controller' => 'MovementEditor', 'action' => $vs_default_actions["ca_movements"], 'searchModule' => 'find', 'searchController' => 'SearchMovements', 'searchAction' => "Index"],
 				'ca_tours'	 					=> ['relevance' => '_natural', 'name' => 'ca_tour_labels.name', 'displayidno' => 'ca_tours.tour_code', 'idno' => 'ca_tours.tour_code', 'displayname' => _t('Tours'), 'primary_key' => 'tour_id', 'module' => 'editor/tours', 'controller' => 'TourEditor', 'action' => $vs_default_actions["ca_tours"], 'searchModule' => 'find', 'searchController' => 'SearchTours', 'searchAction' => "Index"],
 				'ca_tour_stops' 				=> ['relevance' => '_natural', 'name' => 'ca_tour_stop_labels.name', 'displayidno' => 'ca_tour_stops.idno', 'idno' => 'ca_tour_stops.idno_sort', 'displayname' => _t('Tour stops'), 'primary_key' => 'stop_id', 'module' => 'editor/tour_stops', 'controller' => 'TourStopEditor', 'action' => $vs_default_actions["ca_tour_stops"], 'searchModule' => 'find', 'searchController' => 'SearchTourStops', 'searchAction' => "Index"]
 			];
 				
			$t_list = new ca_lists();
			
 			$va_searches_sorted = [];
 			foreach($va_default_order as $vs_spec) {
 			    list($vs_table, $vs_type) = explode('/', $vs_spec);
 				if((bool)$o_config->get("{$vs_table}_disable")) { continue; }
 				$va_searches_sorted[$vs_table] = $va_searches[$vs_table];
 				
 				if (!($t_instance = Datamodel::getInstanceByTableName($vs_table, true))) { continue; }
 					
 				if ($vs_type) {
 				    if ($t_type = $t_instance->getTypeInstance($t_instance->getTypeIDForCode($vs_type))) {
						$va_proto_type = $va_searches_sorted[$vs_table];
						unset($va_searches_sorted[$vs_table]);
						$va_searches_sorted["{$vs_table}/".$t_type->get('idno')] = $va_proto_type;
						$va_searches_sorted["{$vs_table}/".$t_type->get('idno')]['displayname'] = caUcFirstUTF8Safe($t_type->get('ca_list_items.preferred_labels.name_plural'));
						
					}
 				} elseif (is_array($va_breakout_by_type) && in_array($vs_table, $va_breakout_by_type)) {
 					if (is_array($va_types = caExtractValuesByUserLocale($t_list->getItemsForList($t_instance->getTypeListCode())))) {
						$va_proto_type = $va_searches_sorted[$vs_table];
						unset($va_searches_sorted[$vs_table]);
						foreach($va_types as $vn_i => $va_type) {
							$va_searches_sorted["{$vs_table}/".$va_type['idno']] = $va_proto_type;
							$va_searches_sorted["{$vs_table}/".$va_type['idno']]['displayname'] = caUcFirstUTF8Safe($va_type['name_plural']);
						}
					}
 				}
 			}
 			
 			return $va_searches_sorted;
 		}
 		# -------------------------------------------------------
 		/**
 		 * Return sorted array with information about quick-search searches configured for display by the current user. 
 		 *
 		 * @param ca_users $pt_user The current user
 		 * @return array
 		 */
 		public static function getSearches($pt_user) {
 			if (!is_array($va_search_list = $pt_user->getPreference("quicksearch_search_list"))) { $va_search_list = []; }
 			$va_selected_searches = array_filter($va_search_list, "strlen");
 			if (!is_array($va_selected_searches) || !sizeof($va_selected_searches)) { $va_selected_searches = array_keys(QuickSearch::availableSearches(['expandByType' => true])); }
 		
 			$va_available_searches = QuickSearch::availableSearches();
 			$va_searches = [];
 			foreach($va_selected_searches as $vs_table) {
 				$va_searches[$vs_table] = $va_available_searches[$vs_table];
 			}
 		
 			return $va_searches;
 		}
 		# -------------------------------------------------------
	}
