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
	private $config;
	
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->config = Configuration::load(__CA_CONF_DIR__."/statistics.conf");
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function fetch() {
		if (!is_array($sources = $this->config->getAssoc('sources'))) {
			throw new ApplicationException(_t('No sources are configured'));
		}
		
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
		
		return $stats_data;
	}
	# ------------------------------------------------------------------
}
