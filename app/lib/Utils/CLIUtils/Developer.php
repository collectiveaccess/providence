<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Developer.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
trait CLIUtilsDeveloper{ 
	# -------------------------------------------------------
	/**
	 * Dump change log for a record
	 */
	public static function dump_log($opts=null) {	
		$o_db = new Db();

		$return = $opts->getOption('return');
		$table = $opts->getOption('table');
		$id = $opts->getOption('id');
		$logged_table = $opts->getOption('logged-table');
	
		if(!($t_instance = Datamodel::getInstanceByTableName($table))) {
			CLIUtils::addError(_t('A table must be specified'));
			return false;
		}
		if((int)$id <= 0) {
			CLIUtils::addError(_t('A valid row id must be specified'));
			return false;
		}
		$t_logged = $logged_table ? Datamodel::getInstanceByTableName($logged_table) : null;
	
		$guid = ca_guids::getForRow($t_instance->tableNum(), $id);
	
		$log = ca_change_log::getLog(0, null, ['forGUID' => $guid, 'forceValuesForAllAttributeSlots' => true]);

		if ($t_logged) {
			$logged_table_num = $t_logged->tableNum();
			$log = array_filter($log, function($v) use ($logged_table_num) { return $v['logged_table_num'] == $logged_table_num; });
		}
	
		$log = array_map(function($v) { $v['log_datetime_display'] = date("d-M-Y h:m:s", $v['log_datetime']); return $v; }, $log);
	
		switch($return) {
			case 'tables':
				$tables = array_unique(array_map(function($v) { return $v['logged_table_num']; }, $log));
				print_r(array_values(array_map(function($v) { return Datamodel::getTableName($v); }, $tables))); // TODO: improve formatting
				break;
			default:
				print_r($log); // TODO: improve formatting
				break;
		}
	}
	# -------------------------------------------------------
	public static function dump_logParamList() {
		return [
			"return|c=s" => _t('Data to return from log. Possible values are "tables" (return all tables referenced in the log and "log" (return log data). Default is "log".'),
			"table|t-s" => _t('Table to return log for.'),
			"id|i-s" => _t('ID of row to return log for.'),
			"logged-table|l=s" => _t('Return only log entries logged against a specific table. By default all log entries are returned.'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function dump_logUtilityClass() {
		return _t('Developer');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function dump_logShortHelp() {
		return _t('Dump change log for a record.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function dump_logHelp() {
		return _t('Dumps change log data for a row (specified by row ID) in a table.');
	}
	# -------------------------------------------------------
	/**
	 * Get GUID for a record
	 */
	public static function get_guid($opts=null) {	
		$table = $opts->getOption('table');
		$id = $opts->getOption('id');
		
		if(!($t_instance = Datamodel::getInstanceByTableName($table))) {
			CLIUtils::addError(_t('A table must be specified'));
			return false;
		}
		if((int)$id <= 0) {
			CLIUtils::addError(_t('A valid row id must be specified'));
			return false;
		}
		
		if($guid = ca_guids::getForRow($t_instance->tableNum(), $id, ['dontAdd' => true])) {
			CLIUtils::addMessage(_t('GUID: %1', $guid));
		} else {
			CLIUtils::addError(_t('Record does not exist'));
		}
	}
	# -------------------------------------------------------
	public static function get_guidParamList() {
		return [
			"table|t-s" => _t('Table to return GUID for.'),
			"id|i-s" => _t('ID of row to return GUID for.'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function get_guidUtilityClass() {
		return _t('Developer');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function get_guidShortHelp() {
		return _t('Get GUID for a record.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function get_guidHelp() {
		return _t('Returns GUID for a record specified by table and row ID.');
	}
	# -------------------------------------------------------
	/**
	 * Get table/row id pair for a GUID
	 */
	public static function get_info_for_guid($opts=null) {	
		$guid = $opts->getOption('guid');
		
		if($info = ca_guids::find(['guid' => $guid], ['returnAs' => 'arrays'])) {
			$info = array_shift($info);
			CLIUtils::addMessage(_t("[%1] => %2:%3", $guid, Datamodel::getTableName($info['table_num']), $info['row_id']));
		} else {
			CLIUtils::addError(_t('GUID does not exist'));
		}
	}
	# -------------------------------------------------------
	public static function get_info_for_guidParamList() {
		return [
			"guid|g=s" => _t('GUID to resolve.'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function get_info_for_guidUtilityClass() {
		return _t('Developer');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function get_info_for_guidShortHelp() {
		return _t('Return information for a GUID.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function guid_info_for_guidHelp() {
		return _t('Returns table and row ID for a GUID.');
	}
	# -------------------------------------------------------
	/**
	 * Extract strings from theme for translation
	 */
	public static function extract_strings_for_translation($opts=null) {	
		$theme = $opts->getOption('theme');
		if(!$theme) { $theme = __CA_THEME__; }
		if(!file_exists(__CA_THEMES_DIR__."/{$theme}")) { 
			CLIUtils::addError(_t('Theme %1 does not exist', $theme));
			return null;
		}
		$locale = $opts->getOption('locale');
		if(strlen($locale) && !preg_match("!^[a-z]{2}_[A-Z]{2,3}$!", $locale)) {
			CLIUtils::addError(_t('Locale %1 is not valid', $locale));
			return null;
		}
		$file = $opts->getOption('file');
		if(!is_writeable(pathinfo($file, PATHINFO_DIRNAME))) { 
			CLIUtils::addError(_t('Cannot write to %1', $file));
			return null;
		}
		$team = $opts->getOption('team');
		$extracted_strings = [];
		
		$directories = [__CA_THEMES_DIR__."/default", __CA_THEMES_DIR__."/{$theme}", __CA_BASE_DIR__."/app/models", __CA_BASE_DIR__."/app/lib", __CA_BASE_DIR__."/app/helpers", __CA_BASE_DIR__."/app/conf"];
		
		$file_count = 0;
		foreach($directories as $d) {
			$files = caGetDirectoryContentsAsList($d);
			print CLIProgressBar::start(sizeof($files), _t('Processing %1', pathinfo($d, PATHINFO_BASENAME)));
			
			foreach($files as $f) {
				CLIProgressBar::setMessage(_t("Processing %1: %2", pathinfo($d, PATHINFO_BASENAME), pathinfo($f, PATHINFO_BASENAME)));
				print CLIProgressBar::next();
				
				if(!file_exists($f)) { continue; }
				$ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
				if(!in_array($ext, ['php', 'conf'])) { continue; }
				$is_conf = ($ext === 'conf');
				
				$file_count++;
				$r = fopen($f, "r");

				while($line = fgets($r)) {
					// _() construction used in config files
					if($is_conf) {
						$strings = preg_match_all("!_\([\"\']{0,1}([^\"\)]+?)[\"\']{0,1}[,\)]+!s", $line, $m);
	
						$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
							return preg_match("![A-Za-z0-9]+!s", $v);
						}));
					}
					
					// _t() construction used in code
					preg_match_all("/_t\(\"(.+?)(?<!\\\\)[\"][,\)]{1}/s", $line, $m);

					$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
						return preg_replace("![\n\r]+!", " ", preg_match("![A-Za-z0-9]+!s", $v));
					}));
					
					preg_match_all("/_t\(\'(.+?)(?<!\\\\)[\'][,\)]{1}/s", $line, $m);

					$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
						return preg_replace("![\n\r]+!", " ", preg_match("![A-Za-z0-9]+!s", $v));
					}));
	
					// <t>...</t> construction used in templates and view files
					$strings = preg_match_all("!<t>(.*?)</t>!s", $line, $m);
	
					$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
						return preg_replace("![\n\r]+!", " ", preg_match("![A-Za-z0-9]+!s", $v));
					}));
				}
			}
			print CLIProgressBar::finish();
		}
		$extracted_strings = array_unique($extracted_strings);


		$out = fopen($file, "w");
		
		$headers = [
			"Project-Id-Version: ".__CA_APP_DISPLAY_NAME__."\\n",
			"POT-Creation-Date: ".date('t')."\\n",
			"PO-Revision-Date: ".date('t')."\\n",
			"Last-Translator: ".__CA_ADMIN_EMAIL__."\\n",
			"MIME-Version: 1.0\\n",
			"Content-Type: text/plain; charset=UTF-8\\n",
			"Content-Transfer-Encoding: 8bit\\n",
			"Plural-Forms: nplurals=2; plural=(n != 1);\\n",
			"X-Generator: CollectiveAccess ".__CollectiveAccess__."\\n"
		];
		if($locale) {
			$headers[] = "Language: {$locale}\\n";
		}	
		if($team) {
			$headers[] = "Language-Team: {$team}\\n";
		}
		fputs($out, "msgid \"\"\nmsgstr \"\"\n");
		foreach($headers as $h) {
			fputs($out, "\"{$h}\"\n");
		}
		fputs($out, "\n");

		foreach($extracted_strings as $s) {
			$s = stripslashes($s);
			fputs($out, "msgid \"{$s}\"\n");
			fputs($out, "msgstr \"\"\n\n");
		}
		print "\n\n";
		
		CLIUtils::addMessage(_t('Extracted %1 strings from %2 files into %3', sizeof($extracted_strings), $file_count, realpath($file)));
	}
	# -------------------------------------------------------
	public static function extract_strings_for_translationParamList() {
		return [
			"theme|g=s" => _t('Theme to extract strings from. If omitted the currently configured theme is used.'),
			"locale|l=s" => _t('Locale of translation.'),
			"file|f=s" => _t('File to write strings to.'),
			"team|t=s" => _t('Language team name. If omitted the current application name'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function extract_strings_for_translationUtilityClass() {
		return _t('Developer');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function extract_strings_for_translationShortHelp() {
		return _t('Generate gettext PO file for translation.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function extract_strings_for_translationHelp() {
		return _t('Generate gettext PO file for translation.');
	}
	# -------------------------------------------------------
}
