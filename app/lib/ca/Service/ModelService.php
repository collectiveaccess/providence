<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/ModelService.php
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
require_once(__CA_MODELS_DIR__."/ca_lists.php");
require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");

class ModelService extends BaseJSONService {
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table=""){
		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------
	public function dispatch(){
		$va_post = $this->getRequestBodyArray();

		switch($this->getRequestMethod()){
			case "GET":
				if(sizeof($va_post)==0){
					return $this->getModelInfoForTypes();
				} else {
					if(is_array($va_post["types"])){
						return $this->getModelInfoForTypes($va_post["types"]);
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
	private function getModelInfoForTypes($pa_types=null){
		$va_post = $this->getRequestBodyArray();
		$t_instance = $this->_getTableInstance($this->getTableName());
		$va_return = array();

		if(is_null($pa_types)){
			$va_types = $t_instance->getTypeList();
			foreach($va_types as $va_type){
				$va_return[$va_type["idno"]] = $this->getModelInfoForType($va_type["idno"]);
			}
		} else if(is_array($pa_types)){
			foreach($pa_types as $vs_type){
				$va_return[$vs_type] = $this->getModelInfoForType($vs_type);
			}
		} else {
			$this->addError(_t("Invalid request body format"));
		}

		return $va_return;
	}
	# -------------------------------------------------------
	private function getModelInfoForType($ps_type){
		$t_instance = $this->_getTableInstance($this->getTableName());
		$t_list = new ca_lists();
		$va_return = array();

		$vs_type_list_code = $t_instance->getTypeListCode();

		// type info

		$va_item = $t_list->getItemFromList($vs_type_list_code,$ps_type);
		$va_return["type_info"] = $va_item;
		$va_return["type_info"]["display_label"] = $t_list->getItemFromListForDisplay($vs_type_list_code,$ps_type);

		// applicable element codes and related info

		$va_elements = array();
		$va_codes = $t_instance->getApplicableElementCodes($va_item["item_id"]);
		$va_codes = array_flip($va_codes);

		foreach($va_codes as $vs_code => $va_junk){
			// subelements
			$t_element = $t_instance->_getElementInstance($vs_code);
			foreach($t_element->getElementsInSet() as $va_element_in_set){
				if($va_element_in_set["datatype"]==0) continue; // don't include sub-containers
				$va_element_in_set["datatype"] = ca_metadata_elements::getAttributeNameForTypeCode($va_element_in_set["datatype"]);
				$va_elements[$vs_code]["elements_in_set"][$va_element_in_set["element_code"]] = $va_element_in_set;
			}

			// element label and description
			$va_label = $t_instance->getAttributeLabelAndDescription($vs_code);
			$va_elements[$vs_code]["name"] = $va_label["name"];
			if(isset($va_label["description"])){
				$va_elements[$vs_code]["description"] = $va_label["description"];
			}
		}

		$va_return["elements"] = $va_elements;

		// possible relationships with "valid tables" (i.e. those that are accessible via services)
		$t_rel_types = new ca_relationship_types();
		foreach($this->opa_valid_tables as $vs_table){
				$vs_rel_table = $t_rel_types->getRelationshipTypeTable($this->getTableName(),$vs_table);
				$va_info = $t_rel_types->getRelationshipInfo($vs_rel_table);
				foreach($va_info as $va_tmp){
					$va_return["relationship_types"][$vs_table][$va_tmp["type_code"]] = $va_tmp;	
				}
		}


		

		return $va_return;
	}
	# -------------------------------------------------------
}