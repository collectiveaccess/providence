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
	# -------------------------------------------------------
	private $ops_query;
	private $opb_deleted_only = false;
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table=""){
		$this->ops_query = $po_request->getParameter("q",pString);
		$this->opb_deleted_only = (bool)$po_request->getParameter("deleted",pInteger);
		
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
	/**
	 *
	 */
	private function search($pa_bundles=null){
		if (!($vo_search = caGetSearchInstance($this->getTableName()))) { 
			$this->addError(_t("Invalid table"));
			return false; 
		}
		$t_instance = $this->_getTableInstance($vs_table_name = $this->getTableName());

		$va_return = array();
		$vo_result = $vo_search->search($this->ops_query, array(
			'deletedOnly' => $this->opb_deleted_only, 
			'sort' => $this->opo_request->getParameter('sort', pString))		// user-specified sort
		);

		$vs_template = $this->opo_request->getParameter('template', pString);		// allow user-defined template to be passed; allows flexible formatting of returned label
		while($vo_result->nextHit()){
			$va_item = array();

			$va_item[$t_instance->primaryKey()] = $vn_id = $vo_result->get($t_instance->primaryKey());
			$va_item['id'] = $vn_id;
			if($vs_idno = $vo_result->get("idno")){
				$va_item["idno"] = $vs_idno;
			}

			if ($vs_template) {
				$va_item["display_label"] = caProcessTemplateForIDs($vs_template, $vs_table_name, array($vn_id), array('convertCodesToDisplayText' => true));
			} else {
				if(is_array($va_display_labels = $vo_result->getDisplayLabels())){
					$va_item["display_label"] = array_pop($va_display_labels);
				}
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
}

?>