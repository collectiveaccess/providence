<?php
/** ---------------------------------------------------------------------
 * app/lib/Statistics/StatisticsAggregator.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
require_once(__CA_LIB_DIR__."/Configuration.php");
use \CollectiveAccessService as CAS;


class StatisticsAggregator {
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		// noop
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	static public function fetch() {
		$sources = self::getSources();
		
		$stats_data = [];
		foreach($sources as $k => $source_info) {
			$client = new CAS\StatisticsService($source_info['url']);
			$client->setCredentials($source_info['service_user'], $source_info['service_password']);
			
			$stats_data_for_source = $client->setEndpoint('runStats')->request()->getRawData();
			$stats_data_for_source['name'] = $source_info['name'];
			$stats_data_for_source['description'] = $source_info['description'];
			$stats_data_for_source['url'] = $source_info['url'];
			$stats_data_for_source['groups'] = $source_info['groups'];
			$stats_data[$k] = $stats_data_for_source;
		}
		
		PersistentCache::save('site_statistics', $stats_data, 'statistics');
		
		self::aggregateData($stats_data);
		
		return $stats_data;
	}
	# ------------------------------------------------------------------
	/**
	 * Return fetched data for all sources
	 */
	static public function getData() {
		$data = PersistentCache::fetch('site_statistics', 'statistics');
		
		return is_array($data) ? $data : [];
	}
	# ------------------------------------------------------------------
	/**
	 * Return fetched data for all sources
	 */
	static public function getAggregatedData() {
		$data = PersistentCache::fetch('aggregated_site_statistics', 'statistics');
		
		return is_array($data) ? $data : [];
	}
	# ------------------------------------------------------------------
	/**
	 * Return data aggregated for group
	 */
	static public function getAggregatedDataForGroup($group) {
		$data = PersistentCache::fetch("aggregated_site_statistics{$group}", 'statistics');
		
		return is_array($data[$group]) ? $data[$group] : null;
	}
	# ------------------------------------------------------------------
	/**
	 * Return data for single source
	 *
	 * @param string $source
	 *
	 * @return array Data or null if source is not found
	 */
	static public function getDataForSource($source) {
		$data = PersistentCache::fetch('site_statistics', 'statistics');
		
		return is_array($data[$source]) ? $data[$source] : null;
	}
	# ------------------------------------------------------------------
	/**
	 * Return list of configured sources
	 *
	 * @return array List of sources
	 */
	static public function getSources() {
		$config = Configuration::load(__CA_CONF_DIR__."/statistics.conf");
		if (!is_array($sources = $config->getAssoc('sources'))) {
			return null;
		}
		return $sources;
	}
	# ------------------------------------------------------------------
	/**
	 * Return list of configured groups
	 *
	 * @return array List of groups
	 */
	static public function getGroups() {
		$config = Configuration::load(__CA_CONF_DIR__."/statistics.conf");
		if (!is_array($groups = $config->getAssoc('groups'))) {
			return null;
		}
		return $groups;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	static public function aggregateData($data) {
		$sources = self::getSources();
		$groups = self::getGroups();
		
		// Generate stats for all sites
		$aggregated_data = []; 
		foreach($data as $k => $v) {
			$aggregated_data = StatisticsAggregator::_addData($v, $aggregated_data);
		}
		PersistentCache::save('aggregated_site_statistics', $aggregated_data, 'statistics');
		
		// Generate stats by group
		if (is_array($groups)) {
			foreach($groups as $group => $group_info) {
				$data_for_group = [];
				
				foreach($sources as $source => $source_info) {
					if(in_array($group, caGetOption('groups', $source_info, [])) && (isset($data[$source]))) {
						$data_for_group[$source] = $data[$source];
					}
				}
				
				$aggregated_data = []; 
				foreach($data_for_group as $k => $v) {
					$aggregated_data = StatisticsAggregator::_addData($v, $aggregated_data);
				}
				
				PersistentCache::save("aggregated_site_statistics_by_group_{$group}", $aggregated_data, 'statistics');
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	static public function _addData($data, $acc) {
		foreach($data as $k => $v) {
			if(in_array($k, ['access_statuses', 'groups'])) { continue; }
			if (is_array($v)) {
				$vproc = self::_addData($v, $acc[$k]);
			} else {
				if (!is_numeric($v)) { continue; }
				if(!isset($acc[$k])) { $acc[$k] = 0; }
				$vproc = $acc[$k] + $v;
			}
			
			$acc[$k] = $vproc;
		}
		return $acc;
	}
	# ------------------------------------------------------------------
}
