<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/SqlSearch2ConfigurationSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2023 Whirl-i-Gig
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
define('__CA_SQLSEARCH_RUNNING_MYSQL__',4001);
define('__CA_SQLSEARCH_TABLES_EXIST__',4002);
define('__CA_SQLSEARCH_CHINESE_WORD_SEGMENTER_AVAILABLE__', 4003);
# ------------------------------------------------

require_once(__CA_LIB_DIR__.'/Datamodel.php');
require_once(__CA_LIB_DIR__.'/Search/SearchBase.php');
require_once(__CA_LIB_DIR__.'/Search/ASearchConfigurationSettings.php');
# ------------------------------------------------
class SqlSearch2ConfigurationSettings extends ASearchConfigurationSettings {
	# ------------------------------------------------
	public function __construct(){
		parent::__construct();
	}
	# ------------------------------------------------
	public function getEngineName() {
		return "SQL Search v2";
	}
	# ------------------------------------------------
	public function setSettings(){
		$this->opa_possible_errors = array(
			__CA_SQLSEARCH_RUNNING_MYSQL__, __CA_SQLSEARCH_TABLES_EXIST__, __CA_SQLSEARCH_CHINESE_WORD_SEGMENTER_AVAILABLE__
		);
	}
	# ------------------------------------------------
	public function checkSetting($pn_setting_num){
		switch($pn_setting_num){
			case __CA_SQLSEARCH_RUNNING_MYSQL__:
				return $this->_checkMysqlIsRunning();
			case __CA_SQLSEARCH_TABLES_EXIST__:
				return $this->_checkSqlSearchTables();
			case __CA_SQLSEARCH_CHINESE_WORD_SEGMENTER_AVAILABLE__:
				return $this->_checkJiebaPHPAvailability();
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
			case __CA_SQLSEARCH_CHINESE_WORD_SEGMENTER_AVAILABLE__:
				return _t('Chinese word segmenter is available');
			default:
				return null;
		}
	}
	# ------------------------------------------------
	public function getSettingDescription($pn_setting_num){
		switch($pn_setting_num){
			case __CA_SQLSEARCH_RUNNING_MYSQL__:
				return _t("The SqlSearch2 search engine requires that MySQL be the back-end database for your CollectiveAccess installation.");
			case __CA_SQLSEARCH_TABLES_EXIST__:
				return _t("The SqlSearch2 search engine requires that certain tables be present in your database. They are installed by default and should be present, but if they are not SqlSearch will not be able to operate.");
			case __CA_SQLSEARCH_CHINESE_WORD_SEGMENTER_AVAILABLE__:
				return _t("To fully index Chinese-language content the Jieba PHP word segmenter (https://github.com/binaryoung/jieba-php) should be installed.");
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
				return _t("Try reloading the definitions for these tables: ca_sql_search_words, ca_sql_search_word_index");
			case __CA_SQLSEARCH_CHINESE_WORD_SEGMENTER_AVAILABLE__:
				return _t("Try installing Jieba PHP. See See https://github.com/binaryoung/jieba-php");
			default:
				return null;
		}
	}
	# ------------------------------------------------
	private function _checkMysqlIsRunning(){
		$vo_app_config = Configuration::load();
		$vs_db_type = $vo_app_config->get('db_type');
		
		if (in_array($vs_db_type, array('mysqli', 'pdo_mysql'))) {
			return __CA_SEARCH_CONFIG_OK__;
		}
		return __CA_SEARCH_CONFIG_ERROR__;
	}
	# ------------------------------------------------
	private function _checkSqlSearchTables(){
		$o_db = new Db();
		$va_tables = $o_db->getTables();
		
		if (
			in_array('ca_sql_search_words', $va_tables) &&
			in_array('ca_sql_search_word_index', $va_tables) 
		) {
			return __CA_SEARCH_CONFIG_OK__;
		}
		return __CA_SEARCH_CONFIG_ERROR__;
	}
	# ------------------------------------------------
	private function _checkJiebaPHPAvailability(){
		if (class_exists("\Binaryoung\Jieba\Jieba")) {
			return __CA_SEARCH_CONFIG_OK__;
		}
		return __CA_SEARCH_CONFIG_ERROR__;
	}
	# ------------------------------------------------
}
