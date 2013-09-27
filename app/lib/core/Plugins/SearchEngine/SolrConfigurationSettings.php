<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/SolrConfigurationSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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
/* are we able to write the solr configuration files? */
define('__CA_SOLR_SETTING_WEBSERVER_DIR_PERMISSIONS__',3001);
/* Solr and all of its cores running?  */
define('__CA_SOLR_SETTING_CORES_RUNNING__',3002);
/* configs not up-2-date, causes a warning if not */
define('__CA_SOLR_SETTING_CONFIGS_UP2DATE__',3003);
/* do cache files for backwards-compatible config changes existing and writable? */
define('__CA_SOLR_SETTING_CACHE__',3004);
# ------------------------------------------------
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Search/SearchBase.php');
require_once(__CA_LIB_DIR__.'/core/Search/ASearchConfigurationSettings.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/Solr.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Client.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Http/Response.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');
# ------------------------------------------------
class SolrConfigurationSettings extends ASearchConfigurationSettings {
	# ------------------------------------------------
	private $opo_app_config;
	private $opo_search_config;
	private $opo_search_indexing_config;
	private $ops_webserver_user;
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
		$this->ops_webserver_user = posix_getpwuid(posix_getuid());
		$this->ops_webserver_user = $this->ops_webserver_user['name'];
		$this->opa_setting_descriptions = array();
		$this->opa_setting_names = array();
		$this->opa_setting_hints = array();
		$this->_initMessages();
		parent::__construct();
	}
	# ------------------------------------------------
	public function getEngineName() {
		return "Apache SOLR";
	}
	# ------------------------------------------------
	private function _initMessages(){
		# ------------------------------------------------
		$this->opa_setting_names[__CA_SOLR_SETTING_WEBSERVER_DIR_PERMISSIONS__]=
			_t("Write permissions for Solr home directory");
		$this->opa_setting_names[__CA_SOLR_SETTING_CORES_RUNNING__] =
			_t("Solr cores up and running");
		$this->opa_setting_names[__CA_SOLR_SETTING_CONFIGS_UP2DATE__] =
			_t("Solr configuration up to date");
		$this->opa_setting_names[__CA_SOLR_SETTING_CACHE__] =
			_t("Indexing caches");
		# ------------------------------------------------
		$this->opa_setting_descriptions[__CA_SOLR_SETTING_WEBSERVER_DIR_PERMISSIONS__] =
			_t("The web server user (%1) must be able to read and write the whole Solr configuration. Please note that also your Java application server user (e.g. tomcat6) must be able to write to the Solr home and its subdirectories.",$this->ops_webserver_user);
		$this->opa_setting_descriptions[__CA_SOLR_SETTING_CORES_RUNNING__] =
			_t("CollectiveAccess uses separate Solr indexes (lightweight instances) for each table that is indexed. These so called 'cores' must be running and ready for queries.");
		$this->opa_setting_descriptions[__CA_SOLR_SETTING_CONFIGS_UP2DATE__] =
			_t("The Solr configuration has to be rebuilt and synced with the CollectiveAccess search indexing configuration and metadata attributes setup from time to time. It's not critical if they're not in sync for the moment. They should be automatically synced on each event that causes some indexing (e.g. record save)");
		$this->opa_setting_descriptions[__CA_SOLR_SETTING_CACHE__] =
			_t("CollectiveAccess uses a cache to store its search indexing configuration. The cache must exist and be writable by the web server user. You shouldn't have to care about that in normal setups.");
		# ------------------------------------------------
		$this->opa_setting_hints[__CA_SOLR_SETTING_WEBSERVER_DIR_PERMISSIONS__] =
			_t("Change the owner of the Solr home directory to the web server user (%1), the owning group to the main group of your Java application server user (e.g. tomcat6) and set the directory permissions to something like 774 (recursively).",$this->ops_webserver_user);
		$this->opa_setting_hints[__CA_SOLR_SETTING_CORES_RUNNING__] =
			_t("Start your Java application server. If it is already running, check your CollectiveAccess configuration (Solr home directory in particular), stop the application server, let CollectiveAccess generate a new Solr configuration (there is a tool in support/utils), check the permissions and start the application server again.");
		$this->opa_setting_hints[__CA_SOLR_SETTING_CONFIGS_UP2DATE__] =
			_t("Don't worry about that. If they're still out of sync after a couple of records have been saved and nothing changed in the indexing and/or metadata configuration, you should consider to stop the Java application server, let CA generate a new Solr config (with the tool provided in support/utils), start the server again and trigger a full reindex (support/utils/reindex.php.");
		$this->opa_setting_hints[__CA_SOLR_SETTING_CACHE__] =
			_t("Check if app/tmp is writable by the web server user (%1).",$this->ops_webserver_user);
		# ------------------------------------------------
	}
	# ------------------------------------------------
	public function setSettings(){
		$this->opa_possible_errors = array_keys($this->opa_setting_names);
	}
	# ------------------------------------------------
	public function checkSetting($pn_setting_num){
		switch($pn_setting_num){
			case __CA_SOLR_SETTING_WEBSERVER_DIR_PERMISSIONS__:
				return $this->_checkSolrDirPermissions();
			case __CA_SOLR_SETTING_CORES_RUNNING__:
				return $this->_checkSolrCores();
			case __CA_SOLR_SETTING_CONFIGS_UP2DATE__:
				return $this->_checkSolrConfigState();
			case __CA_SOLR_SETTING_CACHE__:
				return $this->_checkSolrCache();
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
	private function _checkSolrDirPermissions(){
		$vs_solr_home = $this->opo_search_config->get("search_solr_home_dir");
		/* try to create a new directory and delete it afterwards */
		if(!@mkdir($vs_solr_home."/tmp", 0700)){
			return __CA_SEARCH_CONFIG_ERROR__;
		}
		if(!@rmdir($vs_solr_home."/tmp")){
			return __CA_SEARCH_CONFIG_ERROR__;
		}
		return __CA_SEARCH_CONFIG_OK__;
		/* maybe we should do some checks in each core directory in addition? */
	}
	# ------------------------------------------------
	private function _checkSolrCores(){
		$vs_solr_home = $this->opo_search_config->get("search_solr_home_dir");
		/* check existence of core-specific configuration files */
		foreach($this->opo_search_base->getIndexedTables() as $vs_table){
			$vs_core_path = $vs_solr_home."/".$vs_table;
			if(!file_exists($vs_core_path)){
				return __CA_SEARCH_CONFIG_ERROR__;
			}
			if(!file_exists($vs_core_path."/conf/schema.xml")){
				return __CA_SEARCH_CONFIG_ERROR__;
			}
			if(!file_exists($vs_core_path."/conf/solrconfig.xml")){
				return __CA_SEARCH_CONFIG_ERROR__;
			}
		}

		/* check if cores are alive */
		$vo_http_client = new Zend_Http_Client();
		foreach($this->opo_search_base->getIndexedTables() as $vs_table){
			$vo_http_client->setUri(
				$this->opo_search_config->get('search_solr_url')."/". /* general url */
				$vs_table. /* core name (i.e. table name) */
				"/select" /* standard request handler */
			);
			$vo_http_client->setParameterGet('q','*:*');
			try {
				$vo_http_response = $vo_http_client->request();
			} catch (Zend_Http_Client_Exception $v_e){
				return __CA_SEARCH_CONFIG_ERROR__;
			}
		}

		/* everything passed */
		return __CA_SEARCH_CONFIG_OK__;
	}
	# ------------------------------------------------
	private function _checkSolrConfigState(){
		$vo_solr = new WLPlugSearchEngineSolr();
		if($vo_solr->_SolrConfigIsOutdated()){
			return __CA_SEARCH_CONFIG_WARNING__;
		}
		return __CA_SEARCH_CONFIG_OK__;
	}
	# ------------------------------------------------
	private function _checkSolrCache(){
		$va_frontend_options = array(
			'lifetime' => null, 				/* cache lives forever (until manual destruction) */
			'logging' => false,					/* do not use Zend_Log to log what happens */
			'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
			'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
			'automatic_serialization' => true	/* we store arrays, so we have to enable that */
		);
		$vs_cache_dir = __CA_APP_DIR__.'/tmp';

		$va_backend_options = array(
			'cache_dir' => $vs_cache_dir,		/* where to store cache data? */
			'file_locking' => true,				/* cache corruption avoidance */
			'read_control' => false,			/* no read control */
			'file_name_prefix' => 'ca_cache',	/* prefix of cache files */
			'cache_file_perm' => 0777			/* permissions of cache files */
		);

		$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);
		
		foreach($this->opo_search_base->getIndexedTables() as $vs_table){
			if (!is_array($va_cache_data = $vo_cache->load('ca_search_indexing_info_'.$vs_table))) {
				return __CA_SEARCH_CONFIG_WARNING__;
			}
		}

		return __CA_SEARCH_CONFIG_OK__;
	}
	# ------------------------------------------------
}
?>