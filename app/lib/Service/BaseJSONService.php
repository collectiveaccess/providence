<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/BaseJSONService.php
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
  
require_once(__CA_MODELS_DIR__."/ca_lists.php");

class BaseJSONService {
	# -------------------------------------------------------
	/**
	 * @var RequestHTTP
	 */
	protected $opo_request;
	protected $ops_table;
	protected $opo_dm;
	
	protected $opa_errors;
	
	protected $opn_id;
	protected $opa_post;
	protected $ops_method;

	protected $opa_valid_tables = array();
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table=""){
		$this->opo_request = $po_request;
		$this->ops_table = $ps_table;
		$this->opa_errors = array();
		
		$this->ops_method = $this->opo_request->getRequestMethod();
		
		if(!in_array($this->ops_method, array("PUT","DELETE","GET","POST","OPTIONS"))){
			$this->addError(("Invalid HTTP request method"));
		}
		
		$this->opn_id = $this->opo_request->getParameter("id",pString);	// we allow for a string to support fetching by idno; typically it's a numeric id
		if($vs_locale = $this->opo_request->getParameter('lang', pString)) {
			global $g_ui_locale, $g_ui_locale_id, $_;
			$g_ui_locale = $vs_locale;
			$t_locale = new ca_locales();
			if($g_ui_locale_id = $t_locale->localeCodeToID($vs_locale)) {
				$g_ui_locale = $vs_locale;
				if(!initializeLocale($g_ui_locale)) die("Error loading locale ".$g_ui_locale);
				$this->opo_request->reloadAppConfig();
			}
		}

		$vs_post_data = $this->opo_request->getRawPostData();
		if(strlen(trim($vs_post_data))>0){
			$this->opa_post = json_decode($vs_post_data,true);
			if(!is_array($this->opa_post)){
				$this->addError(_t("Data sent via POST doesn't seem to be in JSON format"));
			}
		} else if($vs_post_data = $this->opo_request->getParameter('source', pString)) {
			$this->opa_post = json_decode($vs_post_data, true);
			if(!is_array($this->opa_post)){
				$this->addError(_t("Data sent via 'source' parameter doesn't seem to be in JSON format"));
			}
		} else {
			$this->opa_post = array();
		}

		$va_base_tables = array(
			"ca_objects", "ca_object_lots", "ca_entities",
			"ca_places", "ca_occurrences", "ca_collections",
			"ca_list_items", "ca_lists", "ca_object_representations",
			"ca_storage_locations", "ca_movements",
			"ca_loans", "ca_tours", "ca_tour_stops", "ca_sets", "ca_item_comments"
		);

		if(is_array($this->opa_valid_tables)) {
			$this->opa_valid_tables = array_merge($va_base_tables, $this->opa_valid_tables);
		} else {
			$this->opa_valid_tables = $va_base_tables;
		}

		if(strlen($ps_table)>0) {
			if(!in_array($ps_table, $this->opa_valid_tables)){
				$this->addError(_t("Table does not exist"));
			}
		}
	}
	# -------------------------------------------------------
	public function getRequestMethod(){
		return $this->ops_method;
	}
	# -------------------------------------------------------
	public function getRequestBodyArray(){
		return $this->opa_post;
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
	public function addError($ps_error){
		$this->opa_errors[] = $ps_error;
		return true;
	}
	# -------------------------------------------------------
	public function getTableName(){
		return $this->ops_table;
	}
	# -------------------------------------------------------
	public function getIdentifier(){
		return $this->opn_id;
	}
	# -------------------------------------------------------
	/**
	 * Get BaseModel instance for given table and optionally load the record with the specified ID
	 * @param string $ps_table table name, e.g. "ca_objects"
	 * @param mixed $pn_id integer primary key value of the record to load, or string idno value for the record to load 
	 * @return BundlableLabelableBaseModelWithAttributes
	 */
	protected function _getTableInstance($ps_table,$pn_id=null){		// $pn_id might be a string if the user is fetching by idno
		if(!in_array($ps_table, $this->opa_valid_tables)){
			$this->opa_errors[] = _t("Accessing this table directly is not allowed");
			return false;
		}

		$vb_include_deleted = intval($this->opo_request->getParameter("include_deleted",pInteger));

		$t_instance = Datamodel::getInstance($ps_table);

		if ($pn_id && !is_numeric($pn_id) && ($vs_idno_fld = $t_instance->getProperty('ID_NUMBERING_ID_FIELD')) && preg_match("!^[A-Za-z0-9_\-\.,\[\]]+$!", $pn_id)) {
			// User is loading by idno
			$va_load_spec = array($vs_idno_fld => $pn_id);
			if(!$vb_include_deleted && $t_instance->hasField('deleted')){
				$va_load_spec['deleted'] = 0;
			}
			if(!$t_instance->load($va_load_spec)){
					$this->opa_errors[] = _t("idno does not exist");
					return false;
				} else if(!$vb_include_deleted && $t_instance->get("deleted")){
					$this->opa_errors[] = _t("idno does not exist");
					return false;
				}
		} else {
			if($pn_id > 0){
				if(!$t_instance->load($pn_id)){
					$this->opa_errors[] = _t("ID does not exist");
					return false;
				} else if(!$vb_include_deleted && $t_instance->get("deleted")){
					$this->opa_errors[] = _t("ID does not exist");
					return false;
				}
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
	protected function _isBadBundle($ps_bundle){
		if(stripos($ps_bundle, "ca_users")!==false){
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
}
