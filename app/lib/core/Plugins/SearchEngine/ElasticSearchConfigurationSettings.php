<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearchConfigurationSettings.php :
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

# ------------------------------------------------
/* is ElasticSearch running?  */
define('__CA_ELASTICSEARCH_SETTING_RUNNING__',5001);
/* does the index exist? */
define('__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__',5002);
# ------------------------------------------------
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Search/SearchBase.php');
require_once(__CA_LIB_DIR__.'/core/Search/ASearchConfigurationSettings.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Client.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Response.php');
# ------------------------------------------------
class ElasticSearchConfigurationSettings extends ASearchConfigurationSettings {
	# ------------------------------------------------
	private $opo_app_config;
	private $opo_search_config;
	private $opo_search_indexing_config;
	# ------------------------------------------------
	private $opa_setting_names;
	private $opa_setting_descriptions;
	private $opa_setting_hints;
	# ------------------------------------------------
	public function __construct(){
		$this->opo_search_base = new SearchBase();
		$this->opo_app_config = Configuration::load();
		$this->opo_search_config = Configuration::load($this->opo_app_config->get("search_config"));
		$this->opo_search_indexing_config = Configuration::load($this->opo_search_config->get("search_indexing_config"));
		$this->opa_setting_descriptions = array();
		$this->opa_setting_names = array();
		$this->opa_setting_hints = array();
		$this->_initMessages();
		parent::__construct();
	}
	# ------------------------------------------------
	public function getEngineName() {
		return "ElasticSearch";
	}
	# ------------------------------------------------
	private function _initMessages(){
		# ------------------------------------------------
		$this->opa_setting_names[__CA_ELASTICSEARCH_SETTING_RUNNING__]=
			_t("ElasticSearch up and running");
		$this->opa_setting_names[__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__] =
			_t("ElasticSearch index exists");
		# ------------------------------------------------
		$this->opa_setting_descriptions[__CA_ELASTICSEARCH_SETTING_RUNNING__] =
			_t("The ElasticSearch service must be running.",$this->ops_webserver_user);
		$this->opa_setting_descriptions[__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__] =
			_t("CollectiveAccess uses only a single index in an ElasticSearch setup. The name of that index can be set in the CollectiveAccess configuration.");
		# ------------------------------------------------
		$this->opa_setting_hints[__CA_ELASTICSEARCH_SETTING_RUNNING__] =
			_t("Install and start the ElasticSearch service. If it is already running, check your CollectiveAccess configuration (the ElasticSearch URL and index name in particular).",$this->ops_webserver_user);
		$this->opa_setting_hints[__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__] =
			_t("If the service is running and can be accessed by CollectiveAccess but the index is missing, let CollectiveAccess generate a fresh index and create the related indexing mappings. There is a tool in support/utils.");
		# ------------------------------------------------
	}
	# ------------------------------------------------
	public function setSettings(){
		$this->opa_possible_errors = array_keys($this->opa_setting_names);
	}
	# ------------------------------------------------
	public function checkSetting($pn_setting_num){
		switch($pn_setting_num){
			case __CA_ELASTICSEARCH_SETTING_RUNNING__:
				return $this->_checkElasticSearchRunning();
			case __CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__:
				return $this->_checkElasticSearchIndexExists();
			default:
				return false;
		}
	}
	# ------------------------------------------------
	public function getSettingName($pn_setting_num){
		return $this->opa_setting_names[$pn_setting_num];
	}
	# ------------------------------------------------
	public function getSettingDescription($pn_setting_num){
		return $this->opa_setting_descriptions[$pn_setting_num];
	}
	# ------------------------------------------------
	public function getSettingHint($pn_setting_num){
		return $this->opa_setting_hints[$pn_setting_num];
	}
	# ------------------------------------------------
	private function _checkElasticSearchRunning(){
		/* check if elasticsearch alive */
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_elasticsearch_base_url')."/". /* general url */
			$this->opo_search_config->get('search_elasticsearch_index_name')."/". /* index name */
			"/_search"
		);
		$vo_http_client->setParameterGet('q','*');
		try {
			$vo_http_response = $vo_http_client->request();
		} catch (Zend_Http_Client_Exception $v_e){
			return __CA_SEARCH_CONFIG_ERROR__;
		}

		/* everything passed */
		return __CA_SEARCH_CONFIG_OK__;
	}
	# ------------------------------------------------
	private function _checkElasticSearchIndexExists(){
		/* check if elasticsearch alive */
		$vo_http_client = new Zend_Http_Client();
		
		$vo_http_client->setUri(
			$this->opo_search_config->get('search_elasticsearch_base_url')."/". /* general url */
			$this->opo_search_config->get('search_elasticsearch_index_name')."/". /* index name */
			"/_search"
		);
		$vo_http_client->setParameterGet('q','*');
		try {
			$vo_http_response = $vo_http_client->request();
			$va_response = json_decode($vo_http_response->getBody(),true);
			
			if(isset($va_response["status"]) && $va_response["status"]==404){
				return __CA_SEARCH_CONFIG_ERROR__;
			}
		} catch (Zend_Http_Client_Exception $v_e){
			return __CA_SEARCH_CONFIG_ERROR__;
		}

		/* everything passed */
		return __CA_SEARCH_CONFIG_OK__;
	}
	# ------------------------------------------------
}
?>