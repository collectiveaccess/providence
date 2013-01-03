<?php
/** ---------------------------------------------------------------------
 * app/lib/core/AccessRestrictions.php :
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");

/**
 *
 */
class AccessRestrictions {
	# -------------------------------------------------------
	/**
	 * Parsed version of access_restrictions.conf
	 * @var Configuration
	 */
	private $opo_acr_config;
	/**
	 * Parsed version of access restriction definition
	 * @var array 
	 */
	public $opa_acr;

	/**
	 * "Empty" ca_users variable to work with
	 * @var ca_users 
	 */
	private $opt_user;

	/**
	 * Current request
	 * @var Request
	 */
	private $opo_request = null;
	# -------------------------------------------------------
	public static function load($pb_dont_cache = false){
		global $_ACR_INSTANCE_CACHE;
		if (!isset($_ACR_INSTANCE_CACHE) || $pb_dont_cache) {
			$_ACR_INSTANCE_CACHE = new AccessRestrictions();
		}
		return $_ACR_INSTANCE_CACHE;
	}
	# -------------------------------------------------------
	public function __construct(){
		$vo_app_conf = Configuration::load();
		$this->opo_acr_config = Configuration::load($vo_app_conf->get("access_restrictions"));
		$this->opa_acr = $this->opo_acr_config->get("access_restrictions");
		$this->opt_user = new ca_users();
		
		global $req;
		if(is_object($req)){
			$this->opo_request = $req;
		}
	}
	# -------------------------------------------------------
	public function userCanAccess($pn_user_id ,$pa_module_path,$ps_controller,$ps_action,$pa_fake_parameters=array()){
		if(!$this->opo_acr_config->get("enforce_access_restrictions")){ // admin doesn't want us to enforce any restrictions
			return true;
		}
		if(!$this->opo_request){
			// there is no "real" request, i.e. we're running a CLI script or something
			// we need some context information from the request to determine if a user
			// can access something though -> always return false here!
			return false;
		}
		
		if ($this->opt_user->getPrimaryKey() != $pn_user_id) {
			$this->opt_user->load($pn_user_id);
		}
		
		if($this->opt_user->canDoAction("is_administrator")) { // almighty admin!
			return true;
		}

		$va_groups_to_check = array();

		// check module components
		if(!is_array($pa_module_path)){
			$pa_module_path = explode("/",$pa_module_path);
		}
		
		if(is_array($pa_module_path)){
			$va_modules_to_check = array();
			foreach($pa_module_path as $vs_module) {
				$va_modules_to_check[] = $vs_module;
				$vs_module_part_path = join("/",$va_modules_to_check);
				if(is_array($this->opa_acr[$vs_module_part_path])) {
					foreach($this->opa_acr[$vs_module_part_path] as $va_group){
						$va_groups_to_check[] = $va_group;
					}
				}
			}
		}

		// check controller
		$vs_controller_path = join("/",(is_array($pa_module_path) ? $pa_module_path : array()))."/".ucfirst($ps_controller).'Controller';
		if(is_array($this->opa_acr[$vs_controller_path])){
			foreach($this->opa_acr[$vs_controller_path] as $va_group){
				$va_groups_to_check[] = $va_group;
			}
		}

		// check action
		$vs_action_path = join("/",(is_array($pa_module_path) ? $pa_module_path : array()))."/".ucfirst($ps_controller)."Controller/".$ps_action;
		if(is_array($this->opa_acr[$vs_action_path])){
			foreach($this->opa_acr[$vs_action_path] as $va_group){
				$va_groups_to_check[] = $va_group;
			}
		}

		// check rules
		foreach($va_groups_to_check as $va_group){
			if(!is_array($va_group) || !is_array($va_group["actions"])) continue; // group without action restrictions
			$vb_group_passed = false;

			// check if parameter restrictions apply
			if(is_array($va_group["parameters"])){
				if(!$this->_parameterRestrictionsApply($va_group["parameters"],$ps_controller,$ps_action,$pa_fake_parameters)){
					continue; // auto-pass
				}
			}
			
			if(isset($va_group["operator"]) && ($va_group["operator"]=="OR")) { // OR
				foreach($va_group["actions"] as $vs_action) {
					if($this->opt_user->canDoAction($vs_action)){
						$vb_group_passed = true;
						break;
					}
				}
			} else { // AND
				foreach($va_group["actions"] as $vs_action) {
					if(!$this->opt_user->canDoAction($vs_action)){
						return false;
					}
				}
				$vb_group_passed = true; // passed all AND-ed conditions
			}

			if(!$vb_group_passed) { // one has to pass ALL groups!
				return false;
			}
		}
		return true; // all groups passed
	}
	# -------------------------------------------------------
	private function _parameterRestrictionsApply($pa_parameters,$ps_controller,$ps_action,$pa_fake_parameters) {
		$t_item = new ca_list_items();
		foreach($pa_parameters as $vs_key => $va_value_data){
			if(($vs_key == "type") && !is_array($va_value_data) && strlen($va_value_data)>0) {
				if(!$t_item->load(array("idno" => $va_value_data))){
					return false;
				}
				// if there is no explicit type_id parameter we need to figure out the subject with the information we have
				// (which is basically the controller name) and get the type_id by ourselves
				if($pa_fake_parameters["type_id"]){
					$vs_type_id = $pa_fake_parameters["type_id"];
				} else {
					$vs_type_id = $this->opo_request->getParameter("type_id",pInteger);
				}
				if($vs_type_id == ""){
					$vs_table = $this->_convertControllerAndActionToSubjectTable($ps_controller,$ps_action);
					if($vs_table){
						$t_instance = new $vs_table();
						if($pa_fake_parameters[$t_instance->primaryKey()]){
							$vn_id = $pa_fake_parameters[$t_instance->primaryKey()];
						} else {
							$vn_id = $this->opo_request->getParameter($t_instance->primaryKey(),pInteger);
						}
						$t_instance->load($vn_id);
						if(intval($t_instance->get("type_id")) != intval($t_item->getPrimaryKey())){
							return false;
						}
					}
				} else {
					if(intval($t_item->getPrimaryKey()) != intval($vs_type_id)){
						return false;
					}
				}
			} else {
				if(isset($pa_fake_parameters[$vs_key])){
					$vs_fake_val = $pa_fake_parameters[$vs_key];
					if(preg_match("/\{[a-z_]*\}/",$vs_fake_val)){ // fake value is placeholder for actual value inserted into forms via JS later -> set some fake actual integer value
						$vs_fake_val = 1;
					}
				} else {
					$vs_fake_val = null;
				}
				if(substr($va_value_data["value"],0,1)=="!"){
					switch($va_value_data["type"]){
						case "int":
							if(intval(substr($va_value_data["value"],1)) == intval($vs_fake_val ? $vs_fake_val : $this->opo_request->getParameter($vs_key,pInteger))){
								return false;
							}
							break;
						case "float":
							if(floatval(substr($va_value_data["value"],1)) == floatval($vs_fake_val ? $vs_fake_val : $this->opo_request->getParameter($vs_key,pFloat))){
								return false;
							}
							break;
						default:
							if(strval(substr($va_value_data["value"],1)) == strval($vs_fake_val ? $vs_fake_val : $this->opo_request->getParameter($vs_key,pString))){
								return false;
							}
							break;
					}
				} else {
					if($va_value_data["value"]=="not_set"){
						if($this->opo_request->getParameter($vs_key,pInteger)!="" || !is_null($vs_fake_val)){
							return false;
						}
					} else {
						switch($va_value_data["type"]){
							case "int":
								if(intval($va_value_data["value"]) != intval($vs_fake_val ? $vs_fake_val : $this->opo_request->getParameter($vs_key,pInteger))){
									return false;
								}
								break;
							case "float":
								if(floatval($va_value_data["value"]) != floatval($vs_fake_val ? $vs_fake_val : $this->opo_request->getParameter($vs_key,pFloat))){
									return false;
								}
								break;
							default:
								if(strval($va_value_data["value"]) != strval($vs_fake_val ? $vs_fake_val : $this->opo_request->getParameter($vs_key,pString))){
									return false;
								}
								break;
						}
					}
				}
			}
		}
		return true;
	}
	# -------------------------------------------------------
	private function _convertControllerAndActionToSubjectTable($ps_controller,$ps_action){
		switch($ps_controller){
			case "CollectionEditor":
				$vs_class = "ca_collections";
				break;
			case "EntityEditor":
				$vs_class = "ca_entities";
				break;
			case "ObjectEventEditor":
				$vs_class = "ca_object_events";
				break;
			case "ObjectLotEditor":
				$vs_class = "ca_object_lots";
				break;
			case "ObjectRepresentationEditor":
				$vs_class = "ca_object_representations";
				break;
			case "ObjectEditor":
				if($ps_action == "GetRepresentationInfo"){
					$vs_class = "ca_object_representations";
				} else {
					$vs_class = "ca_objects";
				}
				break;
			case "OccurrenceEditor":
				$vs_class = "ca_occurrences";
				break;
			case "PlaceEditor":
				$vs_class = "ca_places";
				break;
			case "RepresentationAnnotationEditor":
				$vs_class = "ca_representation_annotations";
				break;
			case "StorageLocationEditor":
				$vs_class = "ca_storage_locations";
				break;
			default:
				return false;
		}
		require_once(__CA_MODELS_DIR__."/{$vs_class}.php");
		return $vs_class;
	}
	# -------------------------------------------------------
}
?>
