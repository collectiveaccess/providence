<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/SqlSearchConfigurationSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
/* do the database tables exist? */
define('__CA_SQLSEARCH_RUNNING_MYSQL__',4001);
define('__CA_SQLSEARCH_TABLES_EXIST__',4002);
# ------------------------------------------------

require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Search/SearchBase.php');
require_once(__CA_LIB_DIR__.'/core/Search/ASearchConfigurationSettings.php');
# ------------------------------------------------
class SqlSearchConfigurationSettings extends ASearchConfigurationSettings {
	# ------------------------------------------------
	public function __construct(){
		parent::__construct();
	}
	# ------------------------------------------------
	public function getEngineName() {
		return "SQL Search";
	}
	# ------------------------------------------------
	public function setSettings(){
		$this->opa_possible_errors = array(
			__CA_SQLSEARCH_RUNNING_MYSQL__, __CA_SQLSEARCH_TABLES_EXIST__
		);
	}
	# ------------------------------------------------
	public function checkSetting($pn_setting_num){
		switch($pn_setting_num){
			case __CA_SQLSEARCH_RUNNING_MYSQL__:
				return $this->_checkMysqlIsRunning();
			case __CA_SQLSEARCH_TABLES_EXIST__:
				return $this->_checkSqlSearchTables();
			default:
				return false;
		}
	}
	# ------------------------------------------------
	public function getSettingName($pn_setting_num){
		switch($pn_setting_num){
			case __CA_SQLSEARCH_RUNNING_MYSQL__:
				return _t("MySQL is back-end database");
			case __CA_SQLSEARCH_TABLES_EXIST__:
				return _t("SqlSearch database tables exist");
			default:
				return null;
		}
	}
	# ------------------------------------------------
	public function getSettingDescription($pn_setting_num){
		$vo_app_config = Configuration::load();
		switch($pn_setting_num){
			case __CA_SQLSEARCH_RUNNING_MYSQL__:
				return _t("The SqlSearch search engine requires that MySQL be the back-end database for your CollectiveAccess installation.");
			case __CA_SQLSEARCH_TABLES_EXIST__:
				return _t("The SqlSearch search engine requires that certain tables be present in your database. They are installed by default and should be present, but if they are not SqlSearch will not be able to operate.");
			default:
				return null;
		}
	}
	# ------------------------------------------------
	public function getSettingHint($pn_setting_num){
		switch($pn_setting_num){
			case __CA_SQLSEARCH_RUNNING_MYSQL__:
				return _t("Try reinstalling your system with MySQL as the back-end database or try a different search engine.");
			case __CA_SQLSEARCH_TABLES_EXIST__:
				return _t("Try reloading the definitions for these tables: ca_mysql_fulltext_search, ca_mysql_fulltext_date_search");
			default:
				return null;
		}
	}
	# ------------------------------------------------
	private function _checkMysqlIsRunning(){
		$vo_app_config = Configuration::load();
		$vs_db_type = $vo_app_config->get('db_type');
		
		if ($vs_db_type === 'mysql') {
			return __CA_SEARCH_CONFIG_OK__;
		}
		return __CA_SEARCH_CONFIG_ERROR__;
	}
	# ------------------------------------------------
	private function _checkSqlSearchTables(){
		$o_db = new Db();
		$va_tables = $o_db->getTables();
		
		if (in_array('ca_mysql_fulltext_search', $va_tables) && in_array('ca_mysql_fulltext_search', $va_tables)) {
			return __CA_SEARCH_CONFIG_OK__;
		}
		return __CA_SEARCH_CONFIG_ERROR__;
	}
	# ------------------------------------------------
}
