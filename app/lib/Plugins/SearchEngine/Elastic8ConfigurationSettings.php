<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8ConfigurationSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2015 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage Search
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/* is ElasticSearch running?  */
define('__CA_ELASTICSEARCH_SETTING_RUNNING__', 5001);
/* does the index exist? */
define('__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__', 5002);
require_once(__CA_LIB_DIR__ . '/Datamodel.php');
require_once(__CA_LIB_DIR__ . '/Configuration.php');
require_once(__CA_LIB_DIR__ . '/Search/SearchBase.php');
require_once(__CA_LIB_DIR__ . '/Search/ASearchConfigurationSettings.php');
require_once(__CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8.php');

class Elastic8ConfigurationSettings extends ASearchConfigurationSettings {
	private Configuration $app_config;
	private Configuration $search_config;
	private Configuration $search_indexing_config;
	private array $setting_names;
	private array $setting_descriptions;
	private array $setting_hints;
	private $elasticsearch_base_url;
	private $elasticsearch_index_name;

	public function __construct() {
		$this->search_base = new SearchBase();
		$this->app_config = Configuration::load();
		$this->search_config = Configuration::load($this->app_config->get("search_config"));
		$this->search_indexing_config = Configuration::load(__CA_CONF_DIR__ . '/search_indexing.conf');
		$this->setting_descriptions = [];
		$this->setting_names = [];
		$this->setting_hints = [];
		$this->_initMessages();

		// allow overriding settings from search.conf via constant (usually defined in bootstrap file)
		// this is useful for multi-instance setups which have the same set of config files for multiple instances
		if (defined('__CA_ELASTICSEARCH_BASE_URL__') && (strlen(__CA_ELASTICSEARCH_BASE_URL__) > 0)) {
			$this->elasticsearch_base_url = __CA_ELASTICSEARCH_BASE_URL__;
		} else {
			$this->elasticsearch_base_url = $this->search_config->get('search_elasticsearch_base_url');
		}

		if (defined('__CA_ELASTICSEARCH_INDEX_NAME__') && (strlen(__CA_ELASTICSEARCH_INDEX_NAME__) > 0)) {
			$this->elasticsearch_index_name = __CA_ELASTICSEARCH_INDEX_NAME__;
		} else {
			$this->elasticsearch_index_name = $this->search_config->get('search_elasticsearch_index_name');
		}

		parent::__construct();
	}

	public function getEngineName(): string {
		return "Elastic8";
	}

	private function _initMessages() {
		$this->setting_names[__CA_ELASTICSEARCH_SETTING_RUNNING__]
			= _t("ElasticSearch up and running");
		$this->setting_names[__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__]
			= _t("ElasticSearch index exists");
		$this->setting_descriptions[__CA_ELASTICSEARCH_SETTING_RUNNING__]
			= _t("The ElasticSearch service must be running.", $this->webserver_user);
		$this->setting_descriptions[__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__]
			= _t("CollectiveAccess uses only a single index in an ElasticSearch setup. The name of that index can be set in the CollectiveAccess configuration.");
		$this->setting_hints[__CA_ELASTICSEARCH_SETTING_RUNNING__]
			= _t("Install and start the ElasticSearch service. If it is already running, check your CollectiveAccess configuration (the ElasticSearch URL and index name in particular).",
			$this->webserver_user);
		$this->setting_hints[__CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__]
			= _t("If the service is running and can be accessed by CollectiveAccess but the index is missing, let CollectiveAccess generate a fresh index and create the related indexing mappings. There is a tool in support/utils.");
	}

	public function setSettings() {
		$this->opa_possible_errors = array_keys($this->setting_names);
	}

	/**
	 * @throws Zend_Http_Client_Exception
	 */
	public function checkSetting($pn_setting_num) {
		switch ($pn_setting_num) {
			case __CA_ELASTICSEARCH_SETTING_RUNNING__:
				return $this->_checkElasticSearchRunning();
			case __CA_ELASTICSEARCH_SETTING_INDEX_EXISTS__:
				return $this->_checkElasticSearchIndexExists();
			default:
				return false;
		}
	}

	public function getSettingName($pn_setting_num) {
		return $this->setting_names[$pn_setting_num];
	}

	public function getSettingDescription($pn_setting_num) {
		return $this->setting_descriptions[$pn_setting_num];
	}

	public function getSettingHint($pn_setting_num) {
		return $this->setting_hints[$pn_setting_num];
	}

	/**
	 * @throws Zend_Http_Client_Exception
	 */
	private function _checkElasticSearchRunning(): int {
		/* check if elasticsearch alive */
		$http_client = new Zend_Http_Client();
		$http_client->setUri(
			$this->elasticsearch_base_url . "/" . /* general url */
			$this->elasticsearch_index_name . "/" . /* index name */
			"/_search"
		);
		$http_client->setParameterGet('q', '*');
		try {
			$http_response = $http_client->request();
		} catch (Zend_Http_Client_Exception $e) {
			return __CA_SEARCH_CONFIG_ERROR__;
		}

		/* everything passed */

		return __CA_SEARCH_CONFIG_OK__;
	}

	/**
	 * @throws Zend_Http_Client_Exception
	 */
	private function _checkElasticSearchIndexExists(): int {
		/* check if elasticsearch alive */
		$http_client = new Zend_Http_Client();

		$http_client->setUri(
			$this->elasticsearch_base_url . "/" . /* general url */
			$this->elasticsearch_index_name . "/" . /* index name */
			"/_search"
		);
		$http_client->setParameterGet('q', '*');
		try {
			$http_response = $http_client->request();
			$response = json_decode($http_response->getBody(), true);

			if (isset($response["status"]) && $response["status"] == 404) {
				return __CA_SEARCH_CONFIG_ERROR__;
			}
		} catch (Zend_Http_Client_Exception $e) {
			return __CA_SEARCH_CONFIG_ERROR__;
		}

		/* everything passed */

		return __CA_SEARCH_CONFIG_OK__;
	}
}
