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
		
		$this->opn_id = $this->opo_request->getParameter("id",pInteger);
		$this->opa_post = json_decode($this->opo_request->getRawPostData(),true);
		
		if(!is_array($this->opa_post)){
			$this->opa_errors[] = _t("Data sent via POST doesn't seem to be in JSON format");
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
		return array();
	}
	# -------------------------------------------------------
}