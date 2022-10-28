<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/StatisticsService.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__."/Service/BaseJSONService.php");

class StatisticsService extends BaseJSONService {
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $endpoint
	 * @param RequestHTTP $request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch($endpoint, $request) {
		$cache_key = $request->getHash();

		if(!$request->getParameter('noCache', pInteger)) {
			if(ExternalCache::contains($cache_key, "StatisticsAPI_{$endpoint}")) {
				return ExternalCache::fetch($cache_key, "StatisticsAPI_{$endpoint}");
			}
		}

		$return = self::runStats($request);

		$ttl = defined('__CA_SERVICE_API_CACHE_TTL__') ? __CA_SERVICE_API_CACHE_TTL__ : 60*60; // save for an hour by default
		ExternalCache::save($cache_key, $return, "StatisticsAPI_{$endpoint}", $ttl);
		return $return;
	}
	# -------------------------------------------------------
	/**
	 * @param array $pa_config
	 * @param RequestHTTP $request
	 * @return array
	 * @throws Exception
	 */
	private static function runStats($request) {
		set_time_limit(3600*3);	// 3 hours should be enough?
		$app_config = Configuration::load();
		$config = Configuration::load(__CA_CONF_DIR__."/services.conf");
		$ct = time();
		$db = new Db();
		
		$time_intervals = [
			'last day' => ($ti_one_day = $ct - (24 * 60 * 60)),
			'last week' => ($ti_one_week = $ct - (24 * 60 * 60 * 7)),
			'last 30 days' => ($ti_last_30_days = $ct - (24 * 60 * 60 * 30)),
			'last 90 days' => ($ti_last_90_days = $ct - (24 * 60 * 60 * 90)),
			'last 6 months' => ($ti_last_6_months = $ct - (24 * 60 * 60 * 180)),
			'last year' => ($ti_last_6_months = $ct - (24 * 60 * 60 * 365)),
		];
	
		$primary_tables = ['ca_objects', 'ca_object_lots', 'ca_object_representations', 'ca_entities', 'ca_occurrences', 'ca_places', 'ca_collections', 'ca_storage_locations', 'ca_loans', 'ca_movements', 'ca_list_items'];
		$access_statuses = caGetListItems('access_statuses', ['index' => 'item_value']);
		$return = ['created_on' => date('c'), 'access_statuses' => array_flip($access_statuses)];
		$counts = [];
		
		foreach($primary_tables as $t) {
			if($app_config->get("{$t}_disabled")) { continue; }
			Datamodel::getInstance($t, true);
			$counts['totals'][$t] = $t::find('*', ['returnAs' => 'count']);
			foreach($access_statuses as $v => $l) {
				$counts['by_status'][$t][$l] = $t::find('*', ['checkAccess' => [$v], 'returnAs' => 'count']);
			}
			
			$t_instance = Datamodel::getInstance($t, true);
			$type_list = $t_instance->getTypeList();
			foreach($type_list as $type_id => $type_info) {
				$counts['by_type'][$t][$type_info['idno']] = $t::find(['type_id' => $type_id], ['returnAs' => 'count']);
			}
			
			if ($s = caGetSearchInstance($t)) {
				foreach($time_intervals as $ti_label => $ti) {
					$r = $s->search("created:\""._t('after %1', date('c', $ti))."\"");
					$counts['by_interval']['created'][$t][$ti_label] = $r->numHits();
					$r = $s->search("modified:\""._t('after %1', date('c', $ti))."\"");
					$counts['by_interval']['modified'][$t][$ti_label] = $r->numHits();
				}
			}
		}
		$return['records'] = [];
		$return['records']['counts'] = $counts;
		
		// last logins
		$return['logins'] = [];
		$counts = [];
		
		$users = ca_users::find('*', ['returnAs' => 'modelInstances']);
		$exclude_users = $config->getList('exclude_logins');
		
		$counts['by_class'] = [];
		$counts_by_interval = [];
		$last_login = $last_login_user = null;
		$user_count = 0;
		foreach($users as $user) {
			if (is_array($x = array_intersect([$user->get('user_name'), $user->get('email')], $exclude_users)) && sizeof($x)) { continue; }
			$t = $user->getVar('last_login');
			
			foreach($time_intervals as $ti_label => $ti) {
				if ($t > $ti) { $counts_by_interval[$ti_label]++; }
			}
			if (!$last_login || ($t > $last_login)) { $last_login = $t; $last_login_user = $user; }
			$counts['by_class'][$user->getChoiceListValue('userclass', $user->get('userclass'))]++;
			$user_count++;
		}
		
		$counts['total'] = $user_count;
		$counts['by_interval'] = $counts_by_interval;
		
		$return['logins']['counts'] = $counts;
		
		if ($last_login_user) { 
			$return['logins']['most_recent'] = [
				'last_login' => date('c', $last_login),
				'last_login_user_fname' => $last_login_user->get('fname'),
				'last_login_user_lname' => $last_login_user->get('lname'),
				'last_login_user_email' => $last_login_user->get('email')
			];
		}
		
		$return['media'] = [
			'total_size' => $size = caGetDirectoryTotalSize(__CA_BASE_DIR__.'/media'),
			'total_size_display' => caHumanFileSize($size),
			'file_count' => caGetFileCountForDirectory(__CA_BASE_DIR__.'/media'),
			'by_format' => [],
			'by_status' => []
		];
		
		$x = 0;
		if ($qr = $db->query("SELECT count(*) c, mimetype FROM ca_object_representations WHERE deleted = 0 GROUP BY mimetype")) {
			while($qr->nextRow()) {
				$mimetype = $qr->get('mimetype');
				$return['media']['by_format'][$mimetype] = ['count' => $qr->get('c')];
				
				$filesize_by_mimetype = 0;
				if($reps = ca_object_representations::findAsSearchResult(['mimetype' => $mimetype])) {
					while($reps->nextHit()) {
						$versions = $reps->getMediaVersions('ca_object_representations.media');	
						$a = $reps->get('ca_object_representations.access');
						$return['media']['by_status'][$mimetype][$access_statuses[$a]]['count']++;
						
						$used_primary_tables = [];
						foreach($primary_tables as $pt) {
							$oa = $reps->get("{$pt}.access", ['returnAsArray' => true]);
							if(is_array($oa) && sizeof($oa)) {
								foreach($oa as $x) {
									$return['media']["by_status_{$pt}"][$mimetype][$access_statuses[$x]]['count']++;
								}
								$used_primary_tables[$pt] = $oa;
							}
						}
						foreach($versions as $version) {
							$info = $reps->getMediaInfo('media', $version);
							$filesize_by_mimetype += $fs = @filesize($reps->getMediaPath('media', $version));
							
							$return['media']['by_status'][$mimetype][$access_statuses[$a]]['filesize'] += $fs;
							
							foreach($used_primary_tables as $pt => $oa) {
								foreach($oa as $x) {
									$return['media']["by_status_{$pt}"][$mimetype][$access_statuses[$x]]['filesize'] += $fs;
								}
							}
						}
						
						$return['media']['by_format'][$mimetype]['source_filesize'] += $reps->getMediaInfo('media', 'INPUT', 'FILESIZE'); // used embeddded size in case original is not stored
					}
					$return['media']['by_format'][$mimetype]['total_filesize'] = $filesize_by_mimetype;
				}
			}
		}
		

		return $return;
	}
	# -------------------------------------------------------
}
