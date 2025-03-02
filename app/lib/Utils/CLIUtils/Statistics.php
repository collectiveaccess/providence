<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Statistics.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Statistics/StatisticsAggregator.php");

trait CLIUtilsStatistics { 
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function fetch_statistics($po_opts=null) {	
		$sites_list = ($sites = (string)$po_opts->getOption('sites')) ? array_filter(preg_split("![;,]+!", $sites), "strlen") : null;
		try {
			$data = StatisticsAggregator::fetch(['sites' => $sites_list]);
		} catch (Exception $e) {
			CLIUtils::addError($e->getMessage());	
			return null;
		}
		$num_sites = is_array($data) ? sizeof($data) : 0;
		$site_list = is_array($data) ? join(", ", array_keys($data)) : "";
		CLIUtils::addMessage(($num_sites === 1) ? _t("Cached statistics for %1 site: %2", $num_sites, $site_list) : _t("Cached statistics for %1 sites: %2", $num_sites, $site_list));
	}
	# -------------------------------------------------------
	public static function fetch_statisticsParamList() {
		return [
			"sites|t-s" => _t('Comma-delimited list of sites to fetch statistics for. If omitted statistics for all configured sites will be fetched.')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function fetch_statisticsUtilityClass() {
		return _t('Statistics');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function fetch_statisticsShortHelp() {
		return _t('Fetch statistics from remote systems.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function fetch_statisticsHelp() {
		return _t('Fetches data and usage statistics from local and remote CollectiveAccess instances and makes them available in the Statistics Dashboard.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_search_log($opts=null) {	
		if (!($filename = $opts->getOption('file'))) {
			CLIUtils::addError(_t('You must specify a file to write export output to.'));
			return false;
		}

		if(is_writeable($filename === false)){
			// probably a permission error
			CLIUtils::addError("Can't write to file %1. Check the permissions.",$filename);
			return false;
		}
		
		$db = new Db();

		$r = fopen($filename, "w");
		fputcsv($r, [_t('Date'), _t('User name'), _t('User email'), _t('Target'), _t('Search'), _t('Num hits'), _t('IP'), _t('Time'), _t('Source')]);
		
		$count = 0;
		$start = 0;
		
		$qr = $db->query("SELECT count(*) c FROM ca_search_log");
		$qr->nextRow();
		$total = $qr->get('c');
		print CLIProgressBar::start($total, _t('Exporting search log'));

		do {
			$qr = $db->query("
				SELECT l.search_id, l.log_datetime, l.table_num, l.search_expression, l.num_hits, l.ip_addr, 
					l.execution_time, l.search_source, u.fname, u.lname, u.email 
				FROM ca_search_log l 
				LEFT JOIN ca_users AS u ON l.user_id = u.user_id
				ORDER BY l.search_id
				LIMIT {$start}, 100
			");
			
			$n = $qr->numRows();
			while($qr->nextRow()) {
				$s = [
					date('c', $qr->get('log_datetime')),
					trim($qr->get('fname').' '.$qr->get('lname')),
					$qr->get('email'),
					Datamodel::getTableName($qr->get('table_num')),
					$qr->get('search_expression'),
					$qr->get('num_hits'),
					$qr->get('ip_addr'),
					$qr->get('execution_time').'s',
					$qr->get('search_source')
				];
				fputcsv($r, $s);
				$count++;
				
				print CLIProgressBar::next(1, _t('Exporting search log entry %1', $count));
			
			}
			$start += $n;
		} while($n > 0);
		
		CLIProgressBar::finish();
		fclose($r);
		CLIUtils::addMessage(($count === 1) ? _t("Exported %1 search log entry", $count) : _t("Exported %1 search log entries", $count));
	}
	# -------------------------------------------------------
	public static function export_search_logParamList() {
		return [
			"file|f=s" => _t('Required. File to save CSV-format log data to.')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_search_logUtilityClass() {
		return _t('Statistics');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_search_logShortHelp() {
		return _t('Export log of user searches as CSV file.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_search_logHelp() {
		return _t('Exports log with CSV-format information about user searches');
	}
	# -------------------------------------------------------
}
