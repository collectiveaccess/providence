<?php
/** ---------------------------------------------------------------------
 * app/helpers/searchHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   
require_once(__CA_MODELS_DIR__.'/ca_lists.php');


	# ---------------------------------------
	/**
	 * Get search instance for given table name
	 * @param string $pm_table_name_or_num Table name or number
	 * @return BaseSearch
	 */
	function caGetSearchInstance($pm_table_name_or_num, $pa_options=null) {
		$o_dm = Datamodel::load();
		
		$vs_table = (is_numeric($pm_table_name_or_num)) ? $o_dm->getTableName((int)$pm_table_name_or_num) : $pm_table_name_or_num;
		
		switch($vs_table) {
			case 'ca_objects':
				require_once(__CA_LIB_DIR__.'/ca/Search/ObjectSearch.php');
				return new ObjectSearch();
				break;
			case 'ca_entities':
				require_once(__CA_LIB_DIR__.'/ca/Search/EntitySearch.php');
				return new EntitySearch();
				break;
			case 'ca_places':
				require_once(__CA_LIB_DIR__.'/ca/Search/PlaceSearch.php');
				return new PlaceSearch();
				break;
			case 'ca_occurrences':
				require_once(__CA_LIB_DIR__.'/ca/Search/OccurrenceSearch.php');
				return new OccurrenceSearch();
				break;
			case 'ca_collections':
				require_once(__CA_LIB_DIR__.'/ca/Search/CollectionSearch.php');
				return new CollectionSearch();
				break;
			case 'ca_loans':
				require_once(__CA_LIB_DIR__.'/ca/Search/LoanSearch.php');
				return new LoanSearch();
				break;
			case 'ca_movements':
				require_once(__CA_LIB_DIR__.'/ca/Search/MovementSearch.php');
				return new MovementSearch();
				break;
			case 'ca_lists':
				require_once(__CA_LIB_DIR__.'/ca/Search/ListSearch.php');
				return new ListSearch();
				break;
			case 'ca_list_items':
				require_once(__CA_LIB_DIR__.'/ca/Search/ListItemSearch.php');
				return new ListItemSearch();
				break;
			case 'ca_object_lots':
				require_once(__CA_LIB_DIR__.'/ca/Search/ObjectLotSearch.php');
				return new ObjectLotSearch();
				break;
			case 'ca_object_representations':
				require_once(__CA_LIB_DIR__.'/ca/Search/ObjectRepresentationSearch.php');
				return new ObjectRepresentationSearch();
				break;
			case 'ca_representation_annotations':
				require_once(__CA_LIB_DIR__.'/ca/Search/RepresentationAnnotationSearch.php');
				return new RepresentationAnnotationSearch();
				break;
			case 'ca_item_comments':
				require_once(__CA_LIB_DIR__.'/ca/Search/ItemCommentSearch.php');
				return new ItemCommentSearch();
				break;
			case 'ca_item_tags':
				require_once(__CA_LIB_DIR__.'/ca/Search/ItemTagSearch.php');
				return new ItemTagSearch();
				break;
			case 'ca_relationship_types':
				require_once(__CA_LIB_DIR__.'/ca/Search/RelationshipTypeSearch.php');
				return new RelationshipTypeSearch();
				break;
			case 'ca_sets':
				require_once(__CA_LIB_DIR__.'/ca/Search/SetSearch.php');
				return new SetSearch();
				break;
			case 'ca_set_items':
				require_once(__CA_LIB_DIR__.'/ca/Search/SetItemSearch.php');
				return new SetItemSearch();
				break;
			case 'ca_tours':
				require_once(__CA_LIB_DIR__.'/ca/Search/TourSearch.php');
				return new TourSearch();
				break;
			case 'ca_tour_stops':
				require_once(__CA_LIB_DIR__.'/ca/Search/TourStopSearch.php');
				return new TourStopSearch();
				break;
			case 'ca_storage_locations':
				require_once(__CA_LIB_DIR__.'/ca/Search/StorageLocationSearch.php');
				return new StorageLocationSearch();
				break;
			case 'ca_users':
				require_once(__CA_LIB_DIR__.'/ca/Search/UserSearch.php');
				return new UserSearch();
				break;
			case 'ca_user_groups':
				require_once(__CA_LIB_DIR__.'/ca/Search/UserGroupSearch.php');
				return new UserGroupSearch();
				break;
			default:
				return null;
				break;
		}
	}
	 # ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caSearchLink($po_request, $ps_content, $ps_classname, $ps_table, $ps_search, $pa_other_params=null, $pa_attributes=null, $pa_options=null) {
		if (!($vs_url = caSearchUrl($po_request, $ps_table, $ps_search, false, $pa_other_params, $pa_options))) {
			return "<strong>Error: no url for search</strong>";
		}
		
		$vs_tag = "<a href='".$vs_url."'";
		
		if ($ps_classname) { $vs_tag .= " class='$ps_classname'"; }
		if (is_array($pa_attributes)) {
			$vs_tag .= _caHTMLMakeAttributeString($pa_attributes);
		}
		
		$vs_tag .= '>'.$ps_content.'</a>';
		
		return $vs_tag;
	}
	 
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return string 
	 */
	function caSearchUrl($po_request, $ps_table, $ps_search=null, $pb_return_url_as_pieces=false, $pa_additional_parameters=null, $pa_options=null) {
		$o_dm = Datamodel::load();
		
		if (is_numeric($ps_table)) {
			if (!($t_table = $o_dm->getInstanceByTableNum($ps_table, true))) { return null; }
		} else {
			if (!($t_table = $o_dm->getInstanceByTableName($ps_table, true))) { return null; }
		}
		
		$vb_return_advanced = isset($pa_options['returnAdvanced']) && $pa_options['returnAdvanced'];
		
		switch($ps_table) {
			case 'ca_objects':
			case 57:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchObjectsAdvanced' : 'SearchObjects';
				$vs_action = 'Index';
				break;
			case 'ca_object_lots':
			case 51:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchObjectLotsAdvanced' : 'SearchObjectLots';
				$vs_action = 'Index';
				break;
			case 'ca_object_events':
			case 45:
                $vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchObjectEventsAdvanced' : 'SearchObjectEvents';
				$vs_action = 'Index';
                break;
			case 'ca_entities':
			case 20:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchEntitiesAdvanced' : 'SearchEntities';
				$vs_action = 'Index';
				break;
			case 'ca_places':
			case 72:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchPlacesAdvanced' : 'SearchPlaces';
				$vs_action = 'Index';
				break;
			case 'ca_occurrences':
			case 67:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchOccurrencesAdvanced' : 'SearchOccurrences';
				$vs_action = 'Index';
				break;
			case 'ca_collections':
			case 13:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchCollectionsAdvanced' : 'SearchCollections';
				$vs_action = 'Index';
				break;
			case 'ca_storage_locations':
			case 89:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchStorageLocationsAdvanced' : 'SearchStorageLocations';
				$vs_action = 'Index';
				break;
			case 'ca_list_items':
			case 33:
				$vs_module = 'administrate/setup';
				$vs_controller = ($vb_return_advanced) ? '' : 'Lists';
				$vs_action = 'Index';
				break;
			case 'ca_object_representations':
			case 56:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchObjectRepresentationsAdvanced' : 'SearchObjectRepresentations';
				$vs_action = 'Index';
				break;
			case 'ca_representation_annotations':
			case 82:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchRepresentationAnnotationsAdvanced' : 'SearchRepresentationAnnotations';
				$vs_action = 'Index';
				break;
			case 'ca_relationship_types':
			case 79:
				$vs_module = 'administrate/setup';
				$vs_controller = ($vb_return_advanced) ? '' : 'RelationshipTypes';
				$vs_action = 'Index';
				break;
			case 'ca_loans':
			case 133:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchLoansAdvanced' : 'SearchLoans';
				$vs_action = 'Index';
				break;
			case 'ca_movements':
			case 137:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchMovementsAdvanced' : 'SearchMovements';
				$vs_action = 'Index';
				break;
			case 'ca_tours':
			case 153:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchToursAdvanced' : 'SearchTours';
				$vs_action = 'Index';
				break;
			case 'ca_tour_stops':
			case 155:
				$vs_module = 'find';
				$vs_controller = ($vb_return_advanced) ? 'SearchTourStopsAdvanced' : 'SearchTourStops';
				$vs_action = 'Index';
				break;
			default:
				return null;
				break;
		}
		if ($pb_return_url_as_pieces) {
			return array(
				'module' => $vs_module,
				'controller' => $vs_controller,
				'action' => $vs_action
			);
		} else {
			if (!is_array($pa_additional_parameters)) { $pa_additional_parameters = array(); }
			$pa_additional_parameters = array_merge(array('search' => $ps_search), $pa_additional_parameters);
			return caNavUrl($po_request, $vs_module, $vs_controller, $vs_action, $pa_additional_parameters);
		}
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return array 
	 */
	function caSearchGetAccessPoints($ps_search_expression) {
		if(preg_match("!\b([A-Za-z0-9\-\_]+):!", $ps_search_expression, $va_matches)) {
			array_shift($va_matches);
			return $va_matches;
		}
		return array();
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return array 
	 */
	function caSearchGetTablesForAccessPoints($pa_access_points) {
		$o_config = Configuration::load();
		$o_search_config = Configuration::load($o_config->get("search_config"));
		$o_search_indexing_config = Configuration::load($o_search_config->get("search_indexing_config"));	
			
		$va_tables = $o_search_indexing_config->getAssocKeys();
		
		$va_aps = array();
		foreach($va_tables as $vs_table) {
			$va_config = $o_search_indexing_config->getAssoc($vs_table);
			if(is_array($va_config) && is_array($va_config['_access_points'])) {
				if (array_intersect($pa_access_points, array_keys($va_config['_access_points']))) {
					$va_aps[$vs_table] = true;	
				}
			}
		}
		
		return array_keys($va_aps);
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return Configuration 
	 */
	function caGetSearchConfig() {
		return Configuration::load(__CA_APP_DIR__.'/conf/search.conf');
	}
	# ---------------------------------------
	/**
	 * @param Zend_Search_Lucene_Index_Term $po_term
	 * @return Zend_Search_Lucene_Index_Term
	 */
	function caRewriteElasticSearchTermFieldSpec($po_term) {
		if(strlen($po_term->field) > 0) {
			// rewrite ca_objects.dates.dates_value as ca_objects/dates/dates/value, which is
			// how we index in ElasticSsearch (they don't allow periods in field names)
			$vs_new_field = str_replace('.', '\/', str_replace('/', '|', $po_term->field));

			// rewrite ca_objects/dates/dates_value as ca_objects/dates_value, because that's
			// how the SearchIndexer indexes -- we don't care about the container the field is in
			$va_tmp = explode('\\/', $vs_new_field);
			if(sizeof($va_tmp) == 3) {
				unset($va_tmp[1]);
				$vs_new_field = join('\\/', $va_tmp);
			}
		} else {
			$vs_new_field = $po_term->field;
		}

		return new Zend_Search_Lucene_Index_Term($po_term->text, $vs_new_field);
	}
	# ---------------------------------------
	/**
	 * ElasticSearch won't accept dates where day or month is zero, so we have to
	 * rewrite certain dates, especially when dealing with "open-ended" date ranges,
	 * e.g. "before 1998", "after 2012"
	 *
	 * @param string $ps_date
	 * @param bool $pb_is_start
	 * @return string
	 */
	function caRewriteDateForElasticSearch($ps_date, $pb_is_start=true) {
		// substitute start and end of universe values with ElasticSearch's builtin boundaries
		$ps_date = str_replace(TEP_START_OF_UNIVERSE,"-292275054",$ps_date);
		$ps_date = str_replace(TEP_END_OF_UNIVERSE,"9999",$ps_date);

		if(preg_match("/(\d+)\-(\d+)\-(\d+)T(\d+)\:(\d+)\:(\d+)Z/", $ps_date, $va_date_parts)) {
			// fix large (positive) years
			if(intval($va_date_parts[1]) > 9999) { $va_date_parts[1] = "9999"; }
			// fix month-less dates
			if(intval($va_date_parts[2]) < 1) { $va_date_parts[2]  = ($pb_is_start ?  "01" : "12"); }
			// fix messed up months
			if(intval($va_date_parts[2]) > 12) { $va_date_parts[2] = "12"; }
			// fix day-less dates
			if(intval($va_date_parts[3]) < 1) { $va_date_parts[3]  = ($pb_is_start ?  "01" : "31"); }
			// fix messed up days
			$vn_days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($va_date_parts[2]), intval($va_date_parts[1]));
			if(intval($va_date_parts[3]) > $vn_days_in_month) { $va_date_parts[3] = (string) $vn_days_in_month; }

			// fix hours
			if(intval($va_date_parts[4]) > 23) { $va_date_parts[4] = "23"; }
			if(intval($va_date_parts[4]) < 0) { $va_date_parts[4]  = ($pb_is_start ?  "00" : "23"); }
			// minutes and seconds
			if(intval($va_date_parts[5]) > 59) { $va_date_parts[5] = "59"; }
			if(intval($va_date_parts[5]) < 0) { $va_date_parts[5]  = ($pb_is_start ?  "00" : "59"); }
			if(intval($va_date_parts[6]) > 59) { $va_date_parts[6] = "59"; }
			if(intval($va_date_parts[6]) < 0) { $va_date_parts[6]  = ($pb_is_start ?  "00" : "59"); }

			return "{$va_date_parts[1]}-{$va_date_parts[2]}-{$va_date_parts[3]}T{$va_date_parts[4]}:{$va_date_parts[5]}:{$va_date_parts[6]}Z";
		} else {
			return '';
		}
	}
	# ---------------------------------------
	/**
	 * @param Db $po_db
	 * @param int $pn_table_num
	 * @param int $pn_row_id
	 * @return array
	 */
	function caGetChangeLogForElasticSearch($po_db, $pn_table_num, $pn_row_id) {
		$qr_res = $po_db->query("
				SELECT ccl.log_id, ccl.log_datetime, ccl.changetype, u.user_name
				FROM ca_change_log ccl
				LEFT JOIN ca_users AS u ON ccl.user_id = u.user_id
				WHERE
					(ccl.logged_table_num = ?) AND (ccl.logged_row_id = ?)
					AND
					(ccl.changetype <> 'D')
			", $pn_table_num, $pn_row_id);

		$va_return = array();
		while($qr_res->nextRow()) {
			$vs_change_date = caGetISODates(date("c", $qr_res->get('log_datetime')))['start'];
			if ($qr_res->get('changetype') == 'I') {
				$va_return["created"][] = $vs_change_date;

				if($vs_user = $qr_res->get('user_name')) {
					$vs_user = str_replace('.', '/', $vs_user);
					$va_return["created/{$vs_user}"][] = $vs_change_date;
				}
			} else {
				$va_return["modified"][] = $vs_change_date;

				if($vs_user = $qr_res->get('user_name')) {
					$vs_user = str_replace('.', '/', $vs_user);
					$va_return["modified/{$vs_user}"][] = $vs_change_date;
				}
			}
		}

		return $va_return;
	}
