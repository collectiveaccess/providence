<?php
/** ---------------------------------------------------------------------
 * app/lib/ConfigurationCheck.php : configuration check singleton class
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 * @subpackage Configuration
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Db/Transaction.php");
require_once(__CA_LIB_DIR__.'/ca/GenericVersionUpdater.php');


 	define('__CA_SCHEMA_UPDATE_ERROR__', 0);
 	define('__CA_SCHEMA_UPDATE_WARNING__', 1);
 	define('__CA_SCHEMA_UPDATE_INFO__', 2);

final class ConfigurationCheck {
	# -------------------------------------------------------
	private static $opa_error_messages;
	private static $opo_config;
	private static $opo_db;
	# -------------------------------------------------------
	/**
	 * Invokes all "QuickCheck" methods. Note that this is usually invoked
	 * in index.php and that any errors set here cause the application
	 * to die and display a nasty configuration error screen.
	 */
	public static function performQuick() {
		self::$opa_error_messages = array();
		self::$opo_db = new Db();
		self::$opo_config = ConfigurationCheck::$opo_db->getConfig();

		/* execute checks */
		$vo_reflection = new ReflectionClass("ConfigurationCheck");
		$va_methods = $vo_reflection->getMethods();
		foreach($va_methods as $vo_method){
			if(strpos($vo_method->name,"QuickCheck")!==false){
				if (!$vo_method->invoke("ConfigurationCheck")) {
					return;
				}
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Performs all "ExpensiveCheck" methods. Note that this is usually
	 * invoked in the Configuration check screen and therefore any
	 * errors set here are "non-lethal", i.e. the application still works
	 * although certain features may not function properly.
	 */
	public static function performExpensive() {
		self::$opa_error_messages = array();
		self::$opo_db = new Db();
		self::$opo_config = ConfigurationCheck::$opo_db->getConfig();

		/* execute checks */
		$vo_reflection = new ReflectionClass("ConfigurationCheck");
		$va_methods = $vo_reflection->getMethods();
		foreach($va_methods as $vo_method){
			if(strpos($vo_method->name,"ExpensiveCheck")!==false){
				if (!$vo_method->invoke("ConfigurationCheck")) {	// true means keep on doing checks; false means stop performing checks
					return;
				}
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Invokes an explicit list of tests to be executed before 
	 * CollectiveAccess installation
	 */
	public static function performInstall() {
		self::$opa_error_messages = array();
		self::$opo_db = new Db();
		self::$opo_config = ConfigurationCheck::$opo_db->getConfig();

		self::PHPVersionQuickCheck();
		self::PHPModuleRequirementQuickCheck();
		self::memoryLimitExpensiveCheck();
		self::DBInnoDBQuickCheck();
		self::permissionInstallCheck();
		self::DBLoginQuickCheck();
		self::tmpDirQuickCheck();
	}
	# -------------------------------------------------------
	private static function addError($ps_error) {
		self::$opa_error_messages[] = $ps_error;
	}
	# -------------------------------------------------------
	public static function foundErrors(){
		return (sizeof(self::$opa_error_messages) > 0);
	}
	# -------------------------------------------------------
	public static function getErrors() {
		return self::$opa_error_messages;
	}
	# -------------------------------------------------------
	public static function updateDatabaseSchema() {
		require_once(self::$opo_config->get("views_directory")."/system/configuration_error_schema_update_html.php");
	}
	# -------------------------------------------------------
	public static function renderErrorsAsHTMLOutput(){
		require_once(self::$opo_config->get("views_directory")."/system/configuration_error_html.php");
	}
	# -------------------------------------------------------
	public static function renderInstallErrorsAsHTMLOutput(){
		require_once(self::$opo_config->get("views_directory")."/system/configuration_error_install_html.php");
	}
	# -------------------------------------------------------
	# "special" install time check functions
	# -------------------------------------------------------
	public static function permissionInstallCheck(){
		//
		// Check app/tmp
		//
		if (!is_writeable(__CA_APP_DIR__.'/tmp')) {
			self::addError(_t('The CollectiveAccess <i>app/tmp</i> directory is NOT writeable by the installer. This may result in installation errors. It is recommended that you change permissions on this directory (<i>%1</i>) to allow write access prior to installation. You can reload the installer to verify that the changed permissions are correct.', __CA_APP_DIR__.'/tmp'));
		}

		//
		// Check app/lucene
		//
		if ((self::$opo_config->get('search_engine_plugin') == 'Lucene') && (!is_writeable(self::$opo_config->get('search_lucene_index_dir')))) {
			self::addError(_t('The CollectiveAccess <i>Lucene search index</i> directory is NOT writeable by the installer. This may result in installation errors. It is recommended that you change permissions on this directory (<i>%1</i>) to allow write access prior to installation. You can reload the installer to verify that the changed permissions are correct.',self::$opo_config->get('search_lucene_index_dir')));
		}

		//
		// Check media
		//
		$vs_media_root = self::$opo_config->get('ca_media_root_dir');
                $vs_base_dir = self::$opo_config->get('ca_base_dir');
		$va_tmp = explode('/', $vs_media_root);
		$vb_perm_media_error = false;
		$vs_perm_media_path = null;
		$vb_at_least_one_part_of_the_media_path_exists = false;
		while(sizeof($va_tmp)) {
			if (!file_exists(join('/', $va_tmp))) {
				array_pop($va_tmp);
				continue;
			}
			if (!is_writeable(join('/', $va_tmp))) {
				$vb_perm_media_error = true;
				$vs_perm_media_path = join('/', $va_tmp);
				break;
			}
			$vb_at_least_one_part_of_the_media_path_exists = true;
			break;
		}

		// check web root for write-ability
		if (!$vb_perm_media_error && !$vb_at_least_one_part_of_the_media_path_exists && !is_writeable($vs_web_root)) {
			$vb_perm_media_error = true;
			$vs_perm_media_path = $vs_base_dir;
		}

		if ($vb_perm_media_error) {
			self::addError(_t('The CollectiveAccess media directory is NOT writeable by the installer. This will prevent proper creation of the media directory structure and result in installation errors. It is recommended that you change permissions on this directory (<i>%1</i>) to allow write access prior to installation. You can reload the installer to verify that the changed permissions are correct.',$vs_perm_media_path));
		}

		return true;
	}
	# -------------------------------------------------------
	# Quick configuration check functions
	# -------------------------------------------------------
	/**
	 * Check for innodb availabiliy
	 */
	public static function DBInnoDBQuickCheck() {
		$va_mysql_errors = array();
		$qr_engines = self::$opo_db->query("SHOW ENGINES");
		$vb_innodb_available = false;
		while($qr_engines->nextRow()){
			if (!in_array($qr_engines->get('Support'), array('YES', 'DEFAULT'))) { continue; }
			if(strtolower($qr_engines->get("Engine"))=="innodb"){
				$vb_innodb_available = true;
			}
		}
		if(!$vb_innodb_available){
			self::addError(_t("Your MySQL installation doesn't support the InnoDB storage engine which is required by CollectiveAccess. For more information also see %1.","<a href='http://dev.mysql.com/doc/refman/5.1/en/innodb.html' target='_blank'>http://dev.mysql.com/doc/refman/5.1/en/innodb.html</a>"));
		}

		return true;
	}
	# -------------------------------------------------------
	/**
	 * Check if database login works okay
	 */
	public static function DBLoginQuickCheck() {
		if (!self::$opo_config->get('db_type')) {
			self::addError(_t("No database login information found. Have you specified it in your setup.php file?"));
		}
		if (!self::$opo_db->connected()) {
			self::addError(_t("Could not connect to database. Did you enter your database login information into setup.php?"));
		}
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Does database have any tables in it?
	 */
	public static function DBTableQuickCheck() {
		$va_tmp = self::$opo_db->getTables();
		if (!is_array($va_tmp) || !in_array('ca_users', $va_tmp)) {
			self::addError(_t("It looks like you have not installed your database yet. Check your configuration or run the <a href=\"%1/install/\">installer</a>.", __CA_URL_ROOT__));
			return false;
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Is the DB schema up-to-date?
	 */
	public static function DBOutOfDateQuickCheck() {
		if (!in_array('ca_schema_updates', self::$opo_db->getTables())) {
			self::addError(_t("Your database is extremely out-of-date. Please install all database migrations starting with migration #1 or contact support@collectiveaccess.org for assistance. See the <a href=\"http://wiki.collectiveaccess.org/index.php?title=Applying_Database_Updates\">update HOW-TO</a> for instructions on applying database updates manually."));
		} else if (($vn_schema_revision = self::getSchemaVersion()) < __CollectiveAccess_Schema_Rev__) {
			if (defined('__CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__') && __CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__) {
				self::addError(_t("Your database is out-of-date. Please install all schema migrations starting with migration #%1. <a href='index.php?updateSchema=1'><strong>Click here</strong></a> to automatically apply the required updates, or see the <a href=\"http://wiki.collectiveaccess.org/index.php?title=Applying_Database_Updates\">update HOW-TO</a> for instructions on applying database updates manually.<br/><br/><div align='center'><strong>NOTE: you should back-up your database before applying updates!</strong></div>",($vn_schema_revision + 1)));
			} else {
				self::addError(_t("Your database is out-of-date. Please install all schema migrations starting with migration #%1. See the <a href=\"http://wiki.collectiveaccess.org/index.php?title=Applying_Database_Updates\">update HOW-TO</a> for instructions on applying database updates manually.<br/><br/><div align='center'><strong>NOTE: you should back-up your database before applying updates!</strong></div>",($vn_schema_revision + 1)));
			}
			for($vn_i = ($vn_schema_revision + 1); $vn_i <= __CollectiveAccess_Schema_Rev__; $vn_i++) {
				if ($o_instance = ConfigurationCheck::getVersionUpdateInstance($vn_i)) {
					if ($vs_preupdate_message = $o_instance->getPreupdateMessage()) {
						self::addError(_t("For migration %1", $vn_i).": {$vs_preupdate_message}");		
					}
				}
			}
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Is the PHP version too old?
	 */
	public static function PHPVersionQuickCheck() {
		$va_php_version = caGetPHPVersion();
		if($va_php_version["versionInt"]<50106){
			self::addError(_t("CollectiveAccess requires PHP version 5.1.6 or higher to function properly. You're running %1. Please upgrade.",$va_php_version["version"]));
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Does the app/tmp dir exist and is it writable?
	 */
	public static function tmpDirQuickCheck() {
		if(!file_exists(__CA_APP_DIR__."/tmp") || !is_writable(__CA_APP_DIR__."/tmp")){
			self::addError(_t("It looks like the directory for temporary files is not writable by the webserver. Please change the permissions of %1 and enable the user which runs the webserver to write this directory.",__CA_APP_DIR__."/tmp"));
		}
		return true;
	}
	# -------------------------------------------------------
	public static function mediaDirQuickCheck() {
		$vs_media_root = self::$opo_config->get("ca_media_root_dir");
		if(!file_exists($vs_media_root) || !is_writable($vs_media_root)){
			self::addError(_t("It looks like the media directory is not writable by the webserver. Please change the permissions of %1 (or create it if it doesn't exist already) and enable the user which runs the webserver to write this directory.",$vs_media_root));
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Does the HTMLPurifier DefinitionCache dir exist and is it writable?
	 */
	public static function htmlPurifierDirQuickCheck() {
		if(!file_exists(__CA_LIB_DIR__."/core/Parsers/htmlpurifier/standalone/HTMLPurifier/DefinitionCache") || !is_writable(__CA_LIB_DIR__."/core/Parsers/htmlpurifier/standalone/HTMLPurifier/DefinitionCache")){
			self::addError(_t("It looks like the directory for HTML filtering caches is not writable by the webserver. Please change the permissions of %1 and enable the user which runs the webserver to write this directory.",__CA_LIB_DIR__."/core/Parsers/htmlpurifier/standalone/HTMLPurifier/DefinitionCache"));
		}
		return true;
	}
	# -------------------------------------------------------
	public static function caUrlRootQuickCheck() {
		$vs_script_name = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"]);
		$vs_probably_correct_urlroot = str_replace("/index.php", "", $vs_script_name);
		
		if (caGetOSFamily() === OS_WIN32) {	// Windows paths are case insensitive
			if(strcasecmp($vs_probably_correct_urlroot, __CA_URL_ROOT__) != 0) {
				self::addError(_t("It looks like the __CA_URL_ROOT__ variable in your setup.php is not set correctly. Please try to set it to &quot;%1&quot;. We came up with this suggestion because you accessed this script via &quot;&lt;your_hostname&gt;%2&quot;.",$vs_probably_correct_urlroot,$vs_script_name));
			}
		} else {
			if(!($vs_probably_correct_urlroot == __CA_URL_ROOT__)) {
				self::addError(_t("It looks like the __CA_URL_ROOT__ variable in your setup.php is not set correctly. Please try to set it to &quot;%1&quot;. We came up with this suggestion because you accessed this script via &quot;&lt;your_hostname&gt;%2&quot;. Note that paths are case sensitive.",$vs_probably_correct_urlroot,$vs_script_name));
			}
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * I suspect that the application would die before we even reach this check if the base dir is messed up?
	 */
	public static function caBaseDirQuickCheck() {
		$vs_script_filename = str_replace("\\", "/", $_SERVER["SCRIPT_FILENAME"]);
		$vs_probably_correct_base = str_replace("/index.php", "", $vs_script_filename);

		if (caGetOSFamily() === OS_WIN32) {	// Windows paths are case insensitive
			if(strcasecmp($vs_probably_correct_base, __CA_BASE_DIR__) != 0) {
				self::addError(_t("It looks like the __CA_BASE_DIR__ variable in your setup.php is not set correctly. Please try to set it to &quot;%1&quot;. We came up with this suggestion because the location of this script is &quot;%2&quot;.",$vs_probably_correct_base,$vs_script_filename));
			}
		} else {
			if(!($vs_probably_correct_base == __CA_BASE_DIR__)) {
				self::addError(_t("It looks like the __CA_BASE_DIR__ variable in your setup.php is not set correctly. Please try to set it to &quot;%1&quot;. We came up with this suggestion because the location of this script is &quot;%2&quot;. Note that paths are case sensitive.",$vs_probably_correct_base,$vs_script_filename));
			}
		}
		return true;
	}
	# -------------------------------------------------------
	public static function PHPModuleRequirementQuickCheck() {
		//mbstring, JSON, mysql, iconv, zlib, PCRE are required
		if (!function_exists("json_encode")) {
			self::addError(_t("PHP JSON module is required for CollectiveAccess to run. Please install it."));
		}
		if (!function_exists("mb_strlen")) {
			self::addError(_t("PHP mbstring module is required for CollectiveAccess to run. Please install it."));
		}
		if (!function_exists("iconv")) {
			self::addError(_t("PHP iconv module is required for CollectiveAccess to run. Please install it."));
		}
		if (!function_exists("mysql_query")) {
			self::addError(_t("PHP mysql module is required for CollectiveAccess to run. Please install it."));
		}
		if (!function_exists("gzcompress")){
			self::addError(_t("PHP zlib module is required for CollectiveAccess to run. Please install it."));
		}
		if (!function_exists("preg_match")){
			self::addError(_t("PHP PCRE module is required for CollectiveAccess to run. Please install it."));
		}
		if (!class_exists("DOMDocument")){
			self::addError(_t("PHP Document Object Model (DOM) module is required for CollectiveAccess to run. Please install it."));
		}
		
		if (@preg_match('/\p{L}/u', 'a') != 1) {
			self::addError(_t("Your version of the PHP PCRE module lacks unicode features. Please install a module version with UTF-8 support."));
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Check if app_name is valid
	 */
	public static function appNameValidityQuickCheck() {
		if(!preg_match("/^[[:alnum:]|_]+$/", __CA_APP_NAME__)){
			self::addError(_t('It looks like the __CA_APP_NAME__ setting in your setup.php is invalid. It may only consist of alphanumeric ASCII characters and underscores (&quot;_&quot;)'));
		}
		return true;
	}
	# -------------------------------------------------------
	# Expensive configuration check functions
	# These are not executed on every page refresh and should be used for more "expensive" checks.
	# They appear in the "configuration check" screen under manage -> system configuration.
	# -------------------------------------------------------
	public static function mediaDirRecursiveExpensiveCheck() {
		$va_dirs = self::getSubdirectoriesAsArray(self::$opo_config->get("ca_media_root_dir"));
		$i = 0;
		foreach($va_dirs as $vs_dir){
			if(!file_exists($vs_dir) || !is_writable($vs_dir)){
				if($i++==10){ // we don't want to spam houndreds of directories. I guess the admin will get the pattern after a few.
					return;
				}
				self::addError(_t("It looks like a subdirectory in the media directory is not writable by the webserver. Please change the permissions for %1 and enable the user which runs the webserver to write this directory.",$vs_dir));
			}
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Now this check clearly isn't expensive but we don't want it to have the
	 * application die in case it fails as it only breaks display of media
	 * and 99% of the other features work just fine. Also the check may
	 * fail if everything is just fine because users tend to to crazy things
	 * with their /etc/hosts files.
	 */
	public static function hostNameExpensiveCheck() {
		if(__CA_SITE_HOSTNAME__ != $_SERVER["HTTP_HOST"]){
			self::addError(_t(
				"It looks like the __CA_SITE_HOSTNAME__ setting in your setup.php may be set incorrectly. ".
				"If you experience any troubles with image display try setting this to &quot;%1&quot;. ".
				"We came up with this suggestion because this is the hostname you used to access this script. ".
				"It may only be valid for you (and not for other users of the system) though (e.g. if you use ".
				"'localhost' or a feature like /etc/hosts on UNIX-based operating systems) so you have to be ".
				"very careful when editing this.",$_SERVER["HTTP_HOST"]
			));
		}
		return true;
	}
	# -------------------------------------------------------
	public static function memoryLimitExpensiveCheck() {
		$vs_memory_limit = ini_get("memory_limit");
		if($vs_memory_limit == "-1"){ // unlimited memory for php processes -> everything's fine
			return true;
		}
		$vn_memory_limit = self::returnValueInBytes($vs_memory_limit);
		if($vn_memory_limit < 134217728){
			self::addError(_t(
				'The memory limit for your PHP installation may not be sufficient to run CollectiveAccess correctly. '.
				'Please consider adjusting the "memory_limit" variable in your PHP configuration (usually a file named) '.
				'&quot;php.ini&quot;. See <a href="http://us.php.net/manual/en/ini.core.php#ini.memory-limit" target="_blank">http://us.php.net/manual/en/ini.core.php</a> '.
				'for more details. The value in your config is &quot;%1&quot;, the recommended value is &quot;128M&quot; or higher.'
			,$vs_memory_limit));
		}
		return true;
	}
	# -------------------------------------------------------
	public static function uploadLimitExpensiveCheck() {
		$vn_post_max_size = self::returnValueInBytes(ini_get("post_max_size"));
		$vn_upload_max_filesize = self::returnValueInBytes(ini_get("upload_max_filesize"));

		if($vn_post_max_size != $vn_upload_max_filesize){
			self::addError(_t(
				'It looks like the PHP configuration variables "post_max_size" and "upload_max_filesize" '.
				'are set to different values. Note that the lowest of both values limits the size of the '.
				'files you can upload to CollectiveAccess. Your values: upload_max_filesize=%1 and post_max_size=%2.'
			,ini_get("upload_max_filesize"),ini_get("post_max_size")));
		}

		if($vn_post_max_size < 5242880 || $vn_upload_max_filesize < 5242880){
			self::addError(_t(
				'It looks like at least one of the PHP configuration variables "post_max_size" and "upload_max_filesize" '.
				'is set to a very low value. Note that the lowest of both values limits the size of the '.
				'files you can upload to CollectiveAccess. We recommend values greater than "5M" but in general you should set '.
				'them to values greater than the largest file you will upload to CollectiveAccess. '.
				'Your values: upload_max_filesize=%1 and post_max_size=%2.'
			,ini_get("upload_max_filesize"),ini_get("post_max_size")));
		}
		return true;
	}
	# -------------------------------------------------------
	public static function suhoshinExpensiveCheck(){
		$vs_post_max_name_length = ini_get("suhosin.post.max_name_length");
		if($vs_post_max_name_length && intval($vs_post_max_name_length)<256){
			self::addError(
				_t('It looks like you have the PHP Suhoshin extension installed which introduces some default constraints than can prevent CA from saving information entered into forms.').
				_t('In particular, you have to set the configuration value "suhosin.post.max_name_length" to 256 or higher to ensure that CA works correctly. Your value is %1',$vs_post_max_name_length)
			);
		}
		$vs_request_max_varname_length = ini_get("suhosin.request.max_varname_length");
		if($vs_request_max_varname_length && intval($vs_request_max_varname_length)<256){
			self::addError(
				_t('It looks like you have the PHP Suhoshin extension installed which introduces some default constraints than can prevent CA from saving information entered into forms.').
				_t('In particular, you have to set the configuration value "suhosin.request.max_varname_length" to 256 or higher to ensure that CA works correctly. Your value is %1',$vs_request_max_varname_length)
			);
		}
		$vs_post_max_totalname_length = ini_get("suhosin.post.max_totalname_length");
		if($vs_post_max_totalname_length && intval($vs_post_max_totalname_length)<5012){
			self::addError(
				_t('It looks like you have the PHP Suhoshin extension installed which introduces some default constraints than can prevent CA from saving information entered into forms.').
				_t('In particular, you have to set the configuration value "suhosin.post.max_totalname_length" to 5012 or higher to ensure that CA works correctly. Your value is %1',$vs_post_max_totalname_length)
			);
		}
		$vs_request_max_totalname_length = ini_get("suhosin.request.max_totalname_length");
		if($vs_request_max_totalname_length && intval($vs_request_max_totalname_length)<5012){
			self::addError(
				_t('It looks like you have the PHP Suhoshin extension installed which introduces some default constraints than can prevent CA from saving information entered into forms.').
				_t('In particular, you have to set the configuration value "suhosin.request.max_totalname_length" to 5012 or higher to ensure that CA works correctly. Your value is %1',$vs_request_max_totalname_length)
			);
		}
		return true;
	}
	# -------------------------------------------------------
	# UTILITIES
	# -------------------------------------------------------
	private static function getSubdirectoriesAsArray($vs_dir) {
		$va_items = array();
		if ($vr_handle = opendir($vs_dir)) {
			while (false !== ($vs_file = readdir($vr_handle))) {
				if ($vs_file != "." && $vs_file != "..") {
					if (is_dir($vs_dir. "/" . $vs_file)) {
						$va_items = array_merge($va_items, self::getSubdirectoriesAsArray($vs_dir. "/" . $vs_file));
						$vs_file = $vs_dir . "/" . $vs_file;
						$va_items[] = preg_replace("/\/\//si", "/", $vs_file);
					}
				}
			}
			closedir($vr_handle);
		}
		return $va_items;
	}
	# -------------------------------------------------------
	/**
	 * Returns number of currently loaded schema version
	 */
	public static function getSchemaVersion() {
		if(!self::$opo_db) { self::$opo_db = new Db(); }
		$qr_res = self::$opo_db->query('
			SELECT max(version_num) n
			FROM ca_schema_updates
		');
		if ($qr_res->nextRow()) {
			return $qr_res->get('n');
		}
		return null;
	}
	# -------------------------------------------------------
	private static function returnValueInBytes($vs_val) {
		$vs_val = trim($vs_val);
		$vs_last = strtolower($vs_val[strlen($vs_val)-1]);
		switch($vs_last) {
			case 'g':
				$vs_val *= 1024;
			case 'm':
				$vs_val *= 1024;
			case 'k':
				$vs_val *= 1024;
		}
		return $vs_val;
	}
	# -------------------------------------------------------
	public static function performDatabaseSchemaUpdate() {
		$va_messages = array();
		if (($vn_schema_revision = self::getSchemaVersion()) < __CollectiveAccess_Schema_Rev__) {			
			
			for($vn_i = ($vn_schema_revision + 1); $vn_i <= __CollectiveAccess_Schema_Rev__; $vn_i++) {
				if (!($o_updater = ConfigurationCheck::getVersionUpdateInstance($vn_i))) {
					$o_updater = new GenericVersionUpdater($vn_i);
				}
				
				
				$va_methods_that_errored = array();
				
				// pre-update tasks
				foreach($o_updater->getPreupdateTasks() as $vs_preupdate_method) {
					if (!$o_updater->$vs_preupdate_method()) {
						//$va_messages["error_{$vn_i}_{$vs_preupdate_method}_preupdate"] = _t("Pre-update task '{$vs_preupdate_method}' failed");
						$va_methods_that_errored[] = $vs_preupdate_method;
					}
				}
				
				if (is_array($va_new_messages = $o_updater->applyDatabaseUpdate())) {
					$va_messages = $va_messages + $va_new_messages;
				} else {
					$va_messages["error_{$vn_i}_sql_fail"] = _t('Could not apply database update for migration %1', $vn_i);
				}
				// post-update tasks
				foreach($o_updater->getPostupdateTasks() as $vs_postupdate_method) {
					if (!$o_updater->$vs_postupdate_method()) {
						//$va_messages["error_{$vn_i}_{$vs_postupdate_method}_postupdate"] = _t("Post-update task '{$vs_postupdate_method}' failed");
						$va_methods_that_errored[] = $vs_postupdate_method;
					}
				}
				
				if ($vs_message = $o_updater->getPostupdateMessage()) {
					$va_messages[(sizeof($va_methods_that_errored) ? "error" : "info")."_{$vn_i}_{$vs_postupdate_method}_postupdate_message"] = _t("For migration %1", $vn_i).": {$vs_message}";
				} else {
					if (sizeof($va_methods_that_errored)) {
						$va_messages["error_{$vn_i}_{$vs_postupdate_method}_postupdate_message"] = _t("For migration %1", $vn_i).": "._t("The following tasks did not complete: %1", join(', ', $va_methods_that_errored));
					} else {
						$va_messages["info_{$vn_i}_postupdate"] = _t("Applied migration %1", $vn_i);
					}
				}
			}
		}
		
		// Clean cache
		caRemoveDirectory(__CA_APP_DIR__.'/tmp', false);
		
		return $va_messages;
	}
	# -------------------------------------------------------
	private static function getVersionUpdateInstance($pn_version) {
		$vs_classname = "VersionUpdate{$pn_version}";
		if (file_exists(__CA_BASE_DIR__."/support/sql/migrations/{$vs_classname}.php")) {
			require_once(__CA_BASE_DIR__."/support/sql/migrations/{$vs_classname}.php");
			return new $vs_classname();
		}
		return null;
	}
	# -------------------------------------------------------
}