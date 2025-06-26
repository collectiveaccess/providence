<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/UserAgent.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2025 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
use Jaybizzle\CrawlerDetect\CrawlerDetect;
require_once(__CA_LIB_DIR__."/Plugins/BanHammer/BaseBanHammerPlugin.php");

class WLPlugBanHammerUserAgent Extends BaseBanHammerPlugin  {
	# ------------------------------------------------------
	/**
	 *
	 */
	static $priority = 100;
	
	/**
	 *
	 */
	static $banned_useragents_list_filepath = __CA_TEMP_DIR__.'/userAgents.json';
	
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function evaluate($request, $options=null) {
		self::init($request, $options);
		$config = self::$config->get('plugins.UserAgent');
		$banned_useragents = caGetOption('banned_useragents', $config, []);
		
		$request_useragent = $_SERVER["HTTP_USER_AGENT"];
		$ip = RequestHTTP::ip();
		
		$ip_to_ua = ExternalCache::fetch('ip_to_ua');
		if(!is_array($ip_to_ua)) { $ip_to_ua = []; }
		
		$log = self::getLogger();
		if(isset($ip_to_ua[$ip]) && ($ip_to_ua[$ip] !== $request_useragent)) {
			if($log) { $log->logInfo(_t('[BanHammer::UserAgent] Banned ip %1 because user agent changed from %2 to %3', $ip, $ip_to_ua[$ip], $request_useragent)); }
			return 1.0;
		}
		$ip_to_ua[$ip] = $request_useragent;
		if(sizeof($ip_to_ua) > 10000) {
			$ip_to_ua = array_slice($ip_to_ua, 0, 7500);
		}
		ExternalCache::save('ip_to_ua', $ip_to_ua);
		
		if($config['use_useragent_list'] ?? false) {
			if(is_array($banned_useragents_list = self::getBannedUserAgentList())) {
				$banned_useragents = array_merge($banned_useragents, $banned_useragents_list);
			}
		}
		foreach($banned_useragents as $u) {
			if (preg_match("!{$u}!i", $request_useragent)) {
				if($log) { $log->logInfo(_t('[BanHammer::UserAgent] Banned ip %1 because user agent %2 is on ban list', $ip, $request_useragent)); }
			
				return 1.0;
			}
		}
		
		$cd = new CrawlerDetect();
		if($cd->isCrawler($_SERVER["HTTP_USER_AGENT"])) {
			if($log) { $log->logInfo(_t('[BanHammer::UserAgent] Banned ip %1 because user agent %2 is on CrawlerDetect list', $ip, $request_useragent)); }
			return 1.0;
		}
		
		return 0;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function shouldBanIP() {
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function banTTL() {
		$config = self::$config ? self::$config->get('plugins.UserAgent') : [];
		return self::getTTLFromConfig($config);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getBannedUserAgentList() {
		self::init($request, $options);
		if(file_exists(self::$banned_useragents_list_filepath)) {
			return json_decode(file_get_contents(self::$banned_useragents_list_filepath), true) ?? [];
		}
		return [];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function hookPeriodicTask(&$params) {
		self::init($request, $options);
		$config = self::$config ? self::$config->get('plugins.UserAgent') : [];
		if(!$config['use_useragent_list']) { return false; }
		if(!$config['useragent_list_url']) { return false; }
		$appvars = new ApplicationVars();
		
		$log = self::getLogger();
		
		$force = (bool)($config['useragent_list_force_reload'] ?? false);
		
		$is_loading = $appvars->getVar('banhammerUserAgentData_isLoading');
		if(!$is_loading || $force){
			$appvars->setVar('banhammerUserAgentData_isLoading', true);	
			$appvars->save();
			
			$filetime = filemtime(self::$banned_useragents_list_filepath);
			$threshold = (int)($config['useragent_list_ttl'] ?? 0);
			if($threshold <= 0) { $threshold = 21600; }
			
			if(!file_exists(self::$banned_useragents_list_filepath) || ((time() - $filetime) > $threshold) || $force) {
				$data = json_decode(file_get_contents($config['useragent_list_url']), true);
			 	if(!is_array($data)) {
			 		$log->logError(_t('[BanHammer::UserAgent] Could not load user agent list from URL "%1"', $config['useragent_list_url']));
			 		return false;
			 	}
				$user_agents = array_map(function ($v) {
					return $v['pattern'];
				}, $data);
				
				if(is_array($exclude_list = $config['exclude_useragents'] ?? []) && sizeof($exclude_list)) {
					$user_agents = array_filter($user_agents, function($v) use ($exclude_list) {
						foreach($exclude_list as $e) {
							if(preg_match("!{$e}!i", $v)) {
								return false;
							}
						}
						return true;
					});
				}
				$log->logInfo(_t('[BanHammer::UserAgent] Loaded user agent list from URL "%1"; got %2 user agents', $config['useragent_list_url'], sizeof($user_agents)));
			
				if(!file_put_contents(self::$banned_useragents_list_filepath, json_encode(array_values($user_agents)))) {
					$log->logError(_t('[BanHammer::UserAgent] Could not write user agent list to "%1"', self::$banned_useragents_list_filepath));
				}
				$appvars->setVar('banhammerUserAgentData_isLoading', false);
				$appvars->save();	
			}
		} 
		return $params;
	}
	# ------------------------------------------------------
}
