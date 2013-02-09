<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/SearchJSONService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */

require_once(__CA_LIB_DIR__."/ca/Service/BaseJSONService.php"); 

class SearchJSONService extends BaseJSONService {
	private $ops_query;
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table=""){
		$this->ops_query = $po_request->getParameter("q",pString);

		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------
	public function dispatch(){
		$va_post = $this->getRequestBodyArray();


		switch($this->getRequestMethod()){
			case "GET":
				if(sizeof($va_post)==0){
					return $this->search();
				} else {
					if(is_array($va_post["bundles"])){
						return $this->search($va_post["bundles"]);
					} else {
						$this->addError(_t("Invalid request body format"));
						return false;
					}
				}
				break;
			default:
				$this->addError(_t("Invalid HTTP request method for this service"));
				return false;
		}
	}
	# -------------------------------------------------------
	private function search($pa_bundles=null){
		$ps_class = $this->mapTypeToSearchClassName($this->getTableName());
		require_once(__CA_LIB_DIR__."/ca/Search/{$ps_class}.php");
			
		$vo_search = new $ps_class();
		$t_instance = $this->_getTableInstance($this->getTableName());

		$va_return = array();
		$vo_result = $vo_search->search($this->ops_query);

		while($vo_result->nextHit()){
			$va_item = array();

			$va_item[$t_instance->primaryKey()] = $vn_id = $vo_result->get($t_instance->primaryKey());
			$va_item['id'] = $vn_id;
			if($vs_idno = $vo_result->get("idno")){
				$va_item["idno"] = $vs_idno;
			}

			if(is_array($va_display_labels = $vo_result->getDisplayLabels())){
				$va_item["display_label"] = array_pop($va_display_labels);
			}

			if(is_array($pa_bundles)){
				foreach($pa_bundles as $vs_bundle => $va_options){
					if(!is_array($va_options)){
						$va_options = array();
					}
					if($this->_isBadBundle($vs_bundle)){
						continue;
					}
					$va_item[$vs_bundle] = $vo_result->get($vs_bundle,$va_options);
				}
			}

			$va_return["results"][] = $va_item;
		}

		return $va_return;
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function mapTypeToSearchClassName($ps_type){
		switch($ps_type){
			case "ca_objects":
				return "ObjectSearch";
			case "ca_object_lots":
				return "ObjectLotSearch";
			case "ca_entities":
				return "EntitySearch";
			case "ca_places":
				return "PlaceSearch";
			case "ca_occurrences":
				return "OccurrenceSearch";
			case "ca_collections":
				return "CollectionSearch";
			case "ca_lists":
				return "ListSearch";
			case "ca_list_items":
				return "ListItemSearch";
			case "ca_object_representations":
				return "ObjectRepresentationSearch";
			case "ca_storage_locations": 
				return "StorageLocationSearch";
			case "ca_movements":
				return "MovementSearch";
			case "ca_loans":
				return "LoanSearch";
			case "ca_tours":
				return "TourSearch";
			case "ca_tour_stops":
				return "TourStopSearch";
			case "ca_sets":
				return "SetSearch";
			default:
				return false;
		}
	}
	# -------------------------------------------------------
}

?>