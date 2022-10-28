<?php
/** ---------------------------------------------------------------------
 * app/lib/Statistics/StatisticsAggregator.php : 
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
	static public function fetch() {
		$sites = self::getSites();
		
		$stats_data = [];
		foreach($sites as $k => $site_info) {
			$client = new CAS\StatisticsService($site_info['url']);
			$client->setCredentials($site_info['service_user'], $site_info['service_password']);
			$stats_data_for_site = $client->setEndpoint('runStats')->request()->getRawData();
			$stats_data_for_site['name'] = $site_info['name'];
			$stats_data_for_site['description'] = $site_info['description'];
			$stats_data_for_site['url'] = $site_info['url'];
			$stats_data_for_site['groups'] = $site_info['groups'];
			$stats_data_for_site['fetched_on'] = time();
			$stats_data[$k] = $stats_data_for_site;
		}
		
		PersistentCache::save('site_statistics_last_fetch', time(), 'statistics');
		PersistentCache::save('site_statistics', $stats_data, 'statistics');
		
		self::aggregateData($stats_data);
		
		return $stats_data;
	}
	# ------------------------------------------------------------------
	/**
	 * Return fetched data for all sites
	 */
	static public function getData() {
		$data = PersistentCache::fetch('site_statistics', 'statistics');
		
		return is_array($data) ? $data : [];
	}
	# ------------------------------------------------------------------
	/**
	 * Return fetched data for all sites
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
		$data = PersistentCache::fetch("aggregated_site_statistics_by_group_{$group}", 'statistics');
		return is_array($data) ? $data : null;
	}
	# ------------------------------------------------------------------
	/**
	 * Return data for single site
	 *
	 * @param string $site
	 *
	 * @return array Data or null if site is not found
	 */
	static public function getDataForsite($site) {
		$data = PersistentCache::fetch('site_statistics', 'statistics');
		
		return is_array($data[$site]) ? $data[$site] : null;
	}
	# ------------------------------------------------------------------
	/**
	 * Return list of configured sites
	 *
	 * @return array List of sites
	 */
	static public function getSites() {
		$config = Configuration::load(__CA_CONF_DIR__."/statistics.conf");
		if (!is_array($sites = $config->getAssoc('sites'))) {
			return null;
		}
		return $sites;
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
	static public function getSitesForGroup($group) {
		$sites = self::getSites();
		
		$site_list = [];
		foreach($sites as $site => $site_info) {
			if (in_array($group, $site_info['groups'], true)) {
				$site_list[$site] = $site_info;
			}
		}
		return $site_list;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	static public function localSite() {
		$config = Configuration::load(__CA_CONF_DIR__."/statistics.conf");
		$local_site = $config->get('local_site');
		$sites = self::getSites();
		return isset($sites[$local_site]) ? $sites[$local_site] : null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	static public function aggregateData($data) {
		$sites = self::getSites();
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
				
				foreach($sites as $site => $site_info) {
					if(in_array($group, caGetOption('groups', $site_info, [])) && (isset($data[$site]))) {
						$data_for_group[$site] = $data[$site];
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
