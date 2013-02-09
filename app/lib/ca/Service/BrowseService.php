<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/BrowseService.php
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
require_once(__CA_LIB_DIR__."/ca/Browse/BrowseEngine.php");

class BrowseService extends BaseJSONService {
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table=""){
		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------
	public function dispatch(){
		switch($this->getRequestMethod()){
			case "OPTIONS":
				return $this->getFacetInfo();
			case "GET":
				if(sizeof($this->getRequestBodyArray())>0){
					return $this->getBrowseResults();	
				} else {
					$this->addError(_t("Getting results for browses without criteria is not supported"));
					return false;
				}
			default:
				$this->addError(_t("Invalid HTTP request method"));
				return false;
		}
	}
	# --------------------------------------------------
	protected function getFacetInfo(){
		$o_browse = $this->initBrowseWithUserFacets();
		$o_browse->execute();
		$va_post = $this->getRequestBodyArray();

		$va_info = $o_browse->getInfoForFacetsWithContent();
		unset($va_info["_search"]);

		foreach($va_info as $vs_facet => &$va_facet_info){
			if(isset($va_post["ungrouped"]) && $va_post["ungrouped"]){
				$va_facet = $o_browse->getFacet($vs_facet);
			} else {
				$va_facet = $o_browse->getFacetWithGroups($vs_facet, $va_facet_info["group_mode"]);
			}

			if(sizeof($va_facet)==0){
				unset($va_info[$vs_facet]);
			} else {
				$va_facet_info["content"] = $va_facet;
			}
		}

		return $va_info;
	}
	# --------------------------------------------------
	protected function getBrowseResults(){
		$o_browse = $this->initBrowseWithUserFacets();
		$o_browse->execute();

		$va_post = $this->getRequestBodyArray();
		if(is_array($va_post["bundles"])){
			$pa_bundles = $va_post["bundles"];
		}
		if(!is_array($va_post["criteria"]) || sizeof($va_post["criteria"])==0){
			$this->addError(_t("Getting results for browses without criteria is not supported"));
			return false;
		}

		$va_return = array();
		$vo_result = $o_browse->getResults();
		$t_instance = $this->_getTableInstance($this->getTableName());

		while($vo_result->nextHit()){
			$va_item = array();

			$va_item[$t_instance->primaryKey()] = $vn_id = $vo_result->get($t_instance->primaryKey());
			$va_item['id'] = $vn_id;
			if($vs_idno = $vo_result->get("idno")){
				$va_item["idno"] = $vs_idno;
			}

			if($vs_label = $vo_result->get($this->getTableName().".preferred_labels")){
				$va_item["display_label"] = $vs_label;	
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
	# --------------------------------------------------
	private function initBrowseWithUserFacets(){
		$ps_class = $this->mapTypeToSearchClassName($this->getTableName());
		require_once(__CA_LIB_DIR__."/ca/Browse/{$ps_class}.php");

		$o_browse = new $ps_class();
		$va_post = $this->getRequestBodyArray();

		if(is_array($va_post) && sizeof($va_post)>0){
			$va_criteria = $va_post["criteria"];
			if(is_array($va_criteria)){
				foreach($va_criteria as $vs_facet => $va_row_ids){
					if(is_array($va_row_ids)){
						$o_browse->addCriteria($vs_facet,$va_row_ids);
					}
				}
			}
		}

		return $o_browse;
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function mapTypeToSearchClassName($ps_type){
		switch($ps_type){
			case "ca_objects":
				return "ObjectBrowse";
			case "ca_object_lots":
				return "ObjectLotBrowse";
			case "ca_entities":
				return "EntityBrowse";
			case "ca_places":
				return "PlaceBrowse";
			case "ca_occurrences":
				return "OccurrenceBrowse";
			case "ca_collections":
				return "CollectionBrowse";
			case "ca_lists":
				return "ListBrowse";
			case "ca_list_items":
				return "ListItemBrowse";
			case "ca_object_representations":
				return "ObjectRepresentationBrowse";
			case "ca_storage_locations": 
				return "StorageLocationBrowse";
			case "ca_movements":
				return "MovementBrowse";
			case "ca_loans":
				return "LoanBrowse";
			case "ca_tours":
				return "TourBrowse";
			case "ca_tour_stops":
				return "TourStopBrowse";
			default:
				return false;
		}
	}
	# -------------------------------------------------------
}

?>