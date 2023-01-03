<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Statistics.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2022 Whirl-i-Gig
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
		try {
			$data = StatisticsAggregator::fetch();
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
		return [];
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
}
