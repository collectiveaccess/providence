<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/ItemService.php
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
 * Portions of this code were inspired by and/or based upon the Omeka 
 * OaiPmhRepository plugin by John Flatness and Yu-Hsun Lin available at 
 * http://www.omeka.org and licensed under the GNU Public License version 3
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
require_once(__CA_MODELS_DIR__."/ca_lists.php");

class ItemService {
	# -------------------------------------------------------
	private $opo_request;
	private $ops_table;
	private $opo_dm;
	
	private $opa_errors;
	
	private $opn_id;
	private $opa_post;
	private $ops_method;
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table=""){
		$this->opo_request = $po_request;
		$this->ops_table = $ps_table;
		$this->opo_dm = Datamodel::load();
		$this->opa_errors = array();
		
		$this->ops_method = $this->opo_request->getRequestMethod();
		
		if(!in_array($this->ops_method, array("PUT","DELETE","GET","OPTIONS"))){
			$this->opa_errors[] = _t("Invalid HTTP request method");
		}
		
		$this->opn_id = intval($this->opo_request->getParameter("id",pInteger));

		$vs_post_data = $this->opo_request->getRawPostData();
		if(strlen(trim($vs_post_data))>0){
			$this->opa_post = json_decode($vs_post_data,true);
			if(!is_array($this->opa_post)){
				$this->opa_errors[] = _t("Data sent via POST doesn't seem to be in JSON format");
			}
		} else {
			$this->opa_post = array();
		}
		
		if(!$this->opo_dm->getTableNum($ps_table)){
			$this->opa_errors[] = _t("Table name does not exist");
		}
	}
	# -------------------------------------------------------
	public function hasErrors(){
		return (bool) sizeof($this->opa_errors);
	}
	# -------------------------------------------------------
	public function getErrors(){
		return $this->opa_errors;
	}
	# -------------------------------------------------------
	public function dispatch(){
		switch($this->ops_method){
			case "GET":
				if($this->opn_id>0){
					if(sizeof($this->opa_post)==0){
						return $this->getAllItemInfo();
					} else {
						return $this->getSpecificItemInfo();
					}
				} else {
					// do something here? (get all records!?)
					return array();
				}
				break;
			// @TODO
			case "PUT":
			case "OPTIONS":
			default: // shouldn't happen, but still ..
				$this->opa_errors[] = _t("Invalid HTTP request method");
				return false;
		}
	}
	# -------------------------------------------------------
	protected function getSpecificItemInfo(){
		if(!($t_instance = $this->_getTableInstance($this->ops_table,$this->opn_id))){
			return false;
		}

		$va_return = array();
		foreach($this->opa_post as $vs_bundle => $va_options){
			if($this->_isBadBundle($vs_bundle)){
				continue;
			}

			if(!is_array($va_options)){
				$va_options = array();
			}

			$va_return[$vs_bundle] = $t_instance->get($vs_bundle,$va_options);
		}

		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * Try to return everything useful for the specified record
	 */
	protected function getAllItemInfo(){
		if(!($t_instance = $this->_getTableInstance($this->ops_table,$this->opn_id))){
			return false;
		}
		$t_list = new ca_lists();
		$t_locales = new ca_locales();

		$va_locales = $t_locales->getLocaleList();
		
		$va_return = array();

		// labels

		$va_labels = $t_instance->get($this->ops_table.".preferred_labels",array("returnAllLocales" => true));
		$va_labels = end($va_labels);
		if(is_array($va_labels)){
			foreach($va_labels as $vn_locale_id => $va_labels_by_locale){
				foreach($va_labels_by_locale as $va_tmp){
					$va_return["preferred_labels"][$va_locales[$vn_locale_id]["code"]][] = $va_tmp[$t_instance->getLabelDisplayField()];	
				}
			}
		}

		$va_labels = $t_instance->get($this->ops_table.".nonpreferred_labels",array("returnAllLocales" => true));
		$va_labels = end($va_labels);
		if(is_array($va_labels)){
			foreach($va_labels as $vn_locale_id => $va_labels_by_locale){
				foreach($va_labels_by_locale as $va_tmp){
					$va_return["preferred_labels"][$va_locales[$vn_locale_id]["code"]][] = $va_tmp[$t_instance->getLabelDisplayField()];
				}
			}
		}

		// "intrinsic" fields
		foreach($t_instance->getFieldsArray() as $vs_field_name => $va_field_info){
			$vs_list = null;
			if(!is_null($vs_val = $t_instance->get($vs_field_name))){
				$va_return[$vs_field_name] = array(
					"value" => $vs_val,
				);
				if(isset($va_field_info["LIST"])){ // fields like "access" and "status"
					$va_tmp = end($t_list->getItemFromListByItemValue($va_field_info["LIST"],$vs_val));
					foreach($va_locales as $vn_locale_id => $va_locale){
						$va_return[$vs_field_name]["display_text"][$va_locale["code"]] = 
							$va_tmp[$vn_locale_id]["name_singular"];
					}
				}
				if(isset($va_field_info["LIST_CODE"])){ // typical example: type_id
					$va_item = $t_list->getItemFromListByItemID($va_field_info["LIST_CODE"],$vs_val);
					$t_item = new ca_list_items($va_item["item_id"]);
					$va_labels = $t_item->getLabels(null,__CA_LABEL_TYPE_PREFERRED__);
					foreach($va_locales as $vn_locale_id => $va_locale){
						if($vs_label = $va_labels[$va_item["item_id"]][$vn_locale_id][0]["name_singular"]){
							$va_return[$vs_field_name]["display_text"][$va_locale["code"]] = $vs_label;
						}
					}
				}
			}
		}

		// attributes
		$va_codes = $t_instance->getApplicableElementCodes();
		foreach($va_codes as $vs_code){
			if($va_vals = $t_instance->get($this->ops_table.".".$vs_code,
				array("convertCodesToDisplayText" => true,"returnAllLocales" => true)))
			{
				$va_vals_by_locale = end($va_vals); // i seriously have no idea what that additional level of nesting in the return format is for
				$va_attribute_values = array();
				foreach($va_vals_by_locale as $vn_locale_id => $va_locale_vals) {
					foreach($va_locale_vals as $vs_val_id => $va_actual_data){
						$vs_locale_code = isset($va_locales[$vn_locale_id]["code"]) ? $va_locales[$vn_locale_id]["code"] : "none";
						$va_attribute_values[$vs_val_id][$vs_locale_code] = $va_actual_data;
					}

					$va_return[$this->ops_table.".".$vs_code] = array_values($va_attribute_values);
				}
			}
		}

		// @TODO: related items

		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * Get BaseModel instance for given table and optionally load the record with the specified ID
	 * @param string $ps_table table name, e.g. "ca_objects"
	 * @param int $pn_id primary key value of the record to load
	 * @return BaseModel
	 */
	private function _getTableInstance($ps_table,$pn_id=null){
		if(!in_array($ps_table, array(
			"ca_objects", "ca_object_lots", "ca_entities",
			"ca_places", "ca_occurrences", "ca_collections",
			"ca_list_items", "ca_object_representations",
			"ca_storage_locations", "ca_movements",
			"ca_loans", "ca_tours", "ca_tour_stops")))
		{
			$this->opa_errors[] = _t("Accessing this table directly is not allowed");
			return false;
		}

		$t_instance = $this->opo_dm->getInstanceByTableName($ps_table);

		if($pn_id > 0){
			if(!$t_instance->load($pn_id)){
				$this->opa_errors[] = _t("ID does not exist");
				return false;
			}
		}

		return $t_instance;
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
}