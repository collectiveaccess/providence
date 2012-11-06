<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/SearchService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
  
require_once(__CA_LIB_DIR__."/ca/Service/BaseService.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_MODELS_DIR__."/ca_list_items.php");

class SearchService extends BaseService {
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);
	}
	# -------------------------------------------------------
	/**
	 * Performs a search (for use with REST-style services)
	 * NOTE: This method cannot be used via the SOAP services since
	 * Zend_Service can't put DOMDocument objects in SOAP responses
	 *
	 * @param string $type can be one of: "ca_objects", "ca_entities", "ca_places", "ca_occurrences", "ca_collections", "ca_list_items", "ca_object_representations", "ca_storage_locations", "ca_movements", "ca_loans", "ca_tours", "ca_tour_stops"
	 * @param string $query the search query
	 * @param array $additional_bundles associated array which defines the additional data to get. It must be an array of
	 *	"bundle name" => "option array" mappings, e.g.
	 *
	 *	array(
	 *		"ca_objects.status" => array("convertCodesToDisplayText" => 1)
	 *	)
	 *
	 *	For a list of available options @see ItemInfo::get().
	 * @return DOMDocument
	 * @throws SoapFault
	 */
	public function queryRest($type,$query,$additional_bundles){
		if(!$ps_class = $this->mapTypeToSearchClassName($type)){
			throw new SoapFault("Server","Invalid type [{$type}]");
		}

		require_once(__CA_LIB_DIR__."/ca/Search/{$ps_class}.php");
		require_once(__CA_MODELS_DIR__."/{$type}.php");
		$vo_search = new $ps_class();
		$t_instance = new $type();

		$vo_result = $vo_search->search($query);

		$vo_dom = new DOMDocument('1.0', 'utf-8');
		$vo_root = $vo_dom->createElement('CaSearchResult');
		$vo_dom->appendChild($vo_root);
		while($vo_result->nextHit()){

			// create element representing row
			$vo_item = $vo_dom->createElement($type);
			$vo_item->setAttribute($t_instance->primaryKey(),$vo_result->get($t_instance->primaryKey()));
			$vo_root->appendChild($vo_item);

			// add display label
			if(is_array($va_display_labels = $vo_result->getDisplayLabels())){
				$vo_display_label = $vo_dom->createElement("displayLabel",caEscapeForXML(array_pop($va_display_labels)));
				$vo_item->appendChild($vo_display_label);
			}
			
			// add idno
			if(($vs_idno = $vo_result->get("idno"))){
				$vo_idno = $vo_dom->createElement("idno",htmlspecialchars($vs_idno));
				$vo_item->appendChild($vo_idno);
			}

			if(is_array($additional_bundles)){
				foreach($additional_bundles as $vs_bundle => $va_options){
					if($this->_isBadBundle($vs_bundle)){
						continue;
					}
					$vo_bundle = $vo_dom->createElement($vs_bundle,htmlspecialchars($vo_result->get($vs_bundle,$va_options)));
					$vo_item->appendChild($vo_bundle);
				}
			}
		}
		return $vo_dom;
	}
	# -------------------------------------------------------
	/**
	 * Filter fields which should not be available for every service user
	 * @param string $ps_bundle field name
	 * @return boolean true if bundle should not be returned to user
	 */
	private function _isBadBundle($ps_bundle){
		if(stripos($ps_bundle, "ca_users")!==false){
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Performs a search (for SOAP services)
	 * @param string $type can be one of: "ca_objects", "ca_entities", "ca_places", "ca_occurrences", "ca_collections", "ca_list_items", "ca_object_representations", "ca_storage_locations", "ca_movements", "ca_loans", "ca_tours", "ca_tour_stops"
	 * @param string $query the search query
	 * @return array
	 * @throws SoapFault
	 */
	public function querySoap($type,$query){
		if(!$ps_class = $this->mapTypeToSearchClassName($type)){
			throw new SoapFault("Server","Invalid type [{$type}]");
		}

		require_once(__CA_LIB_DIR__."/ca/Search/{$ps_class}.php");
		require_once(__CA_MODELS_DIR__."/{$type}.php");
		$vo_search = new $ps_class();
		$t_instance = new $type();

		$vo_result = $vo_search->search($query);

		$va_return = array();
		while($vo_result->nextHit()){
			if(is_array($va_display_labels = $vo_result->getDisplayLabels())){
				$vs_display_label = array_pop($va_display_labels);
			}

			$va_return[$vo_result->get($t_instance->primaryKey())] = array(
				"display_label" => $vs_display_label,
				"idno" => ($vo_result->get("idno") ? $vo_result->get("idno") : null),
				$t_instance->primaryKey() => $vo_result->get($t_instance->primaryKey())
			);
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
			default:
				return false;
		}
	}
}
