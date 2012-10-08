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

class ModelService extends BaseJSONService {
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table=""){
		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------
	public function dispatch(){
		switch($this->getRequestMethod()){
			case "GET":
				if(sizeof($this->getRequestBodyArray())==0){
					$this->addError(_t("Please specify the types you want model information for. Refer to the documentation if you don't know how to build a proper request."));
				} else {
					return $this->getModelInfo();
				}
				break;
			default:
				$this->addError(_t("Invalid HTTP request method for this service"));
				return false;
		}
	}
	# -------------------------------------------------------
	private function getModelInfo(){
		return array();
	}
	# -------------------------------------------------------
}