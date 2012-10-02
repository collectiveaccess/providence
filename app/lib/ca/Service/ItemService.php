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
		if(($this->opn_id>0) && ($this->ops_method=="GET")){
			if(sizeof($this->opa_post)==0){
				
			} else {
				return $this->getSpecificItemInfo();
			}
		} else {
			
		}
		return array();
	}
	# -------------------------------------------------------
	protected function getSpecificItemInfo(){
		$t_instance = $this->opo_dm->getInstanceByTableName($this->ops_table);
		if(!$t_instance->load($this->opn_id)){
			$this->opa_errors[] = _t("ID does not exist");
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