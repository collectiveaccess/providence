<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/IPAddress.php : 
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
require_once(__CA_LIB_DIR__."/Plugins/BanHammer/BaseBanHammerPlugin.php");

class WLPlugBanHammerIPAddress Extends BaseBanHammerPlugin  {
	# ------------------------------------------------------
	/**
	 *
	 */
	static $priority = 10;
	
	/**
	 *
	 */
	static $banned_ips_list_filepath = __CA_APP_DIR__.'/tmp/bannedIps.json';
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function evaluate($request, $options=null) {
		self::init($request, $options);
		$config = self::$config->get('plugins.IPAddress');
		$banned_ip_addresses = caGetOption('banned_ip_addresses', $config, []);
		
		$log = self::getLogger();
		
		$request_ip = RequestHTTP::ip();
		$request_ip_long = ip2long($request_ip);
		
		foreach($banned_ip_addresses as $ip) {
			$ip_s = ip2long(str_replace("*", "0", $ip));
			$ip_e = ip2long(str_replace("*", "255", $ip));
			if (($request_ip_long >= $ip_s) && ($request_ip_long <= $ip_e)) {
				if($log) { $log->logInfo(_t('[BanHammer::IPAddress] Banned ip %1 because address is on ban list', $request_ip)); }
				return 1.0;
			}
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
		$config = self::$config ? self::$config->get('plugins.IPAddress') : [];
		return self::getTTLFromConfig($config);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getBannedIPList() {
		self::init($request, $options);
		if(file_exists(self::$banned_ips_list_filepath)) {
		
		}
		return [];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function hookPeriodicTask(&$params) {
		self::init($request, $options);
		$config = self::$config ? self::$config->get('plugins.IPAddress') : [];
		if(!$config['use_ip_ban_feed']) { return true; }
		if(!$config['ip_ban_feed_url']) { return true; }
		$appvars = new ApplicationVars();
		
		$log = self::getLogger();
		
		$force = (bool)($config['ip_ban_feed_force_reload'] ?? false);
		
		$is_loading = $appvars->getVar('banhammerIPAddressData_isLoading');
		if(!$is_loading || $force){
			$appvars->setVar('banhammerIPAddressData_isLoading', true);	
			$appvars->save();
			
			$filetime = filemtime(self::$banned_ips_list_filepath);
			$threshold = (int)($config['ip_ban_feed_ttl'] ?? 0);
			if($threshold <= 0) { $threshold = 21600; }
			
			$occurrence_threshold = isset($config['ip_ban_occurrence_threshold']) ? (int)$config['ip_ban_occurrence_threshold'] : 1;
			
			if(!file_exists(self::$banned_ips_list_filepath) || ((time() - $filetime) > $threshold) || $force) {
				$data = file_get_contents($config['ip_ban_feed_url']);
			 	if(!$data || !is_array($lines = explode("\n", $data)) || !sizeof($lines)) {
			 		$log->logError(_t('[BanHammer::IPAddress] Could not load ip ban list from URL "%1"', $config['ip_ban_feed_url']));
			 		return true;
			 	}
			 	
			 	foreach($lines as $i => $line) {
			 		if(substr($line = trim($line), 0, 1) === '#') { 
			 			unset($lines[$i]);
			 			continue; 
			 		}
			 		
			 		$tmp = explode("\t", $line);
			 		
			 		if(($occurrence_threshold > 1) && ($tmp[1] < $occurrence_threshold)) { continue; }
			 		
			 		if(!ca_ip_bans::find(['ip_addr' => $tmp[0], 'reason' => 'IPAddressAuto'])) {
			 			$b = new ca_ip_bans();
			 			$b->set([
			 				'ip_addr' => $tmp[0], 'reason' => 'IPAddressAuto', 'expires_on' => null
			 			]);
			 			$b->insert();
			 		}
			 		if($b = ca_ip_whitelist::find(['ip_addr' => $tmp[0]], ['returnAs' => 'firstModelInstance'])) {
			 			$b->delete(true);
			 		}
			 		$lines[$i] = $tmp[0];
			 	}
				
				$log->logInfo(_t('[BanHammer::IPAddress] Loadedip ban list from URL "%1"; got %2 ip addresses', $config['ip_ban_feed_url'], sizeof($lines)));
			
				if(!file_put_contents(self::$banned_ips_list_filepath, json_encode($lines))) {
					$log->logError(_t('[BanHammer::IPAddress] Could not write ip ban list to "%1"', self::$banned_ips_list_filepath));
				}
				$appvars->setVar('banhammerIPAddressData_isLoading', false);
				$appvars->save();	
			}
		} 
		return $params;
	}
	# ------------------------------------------------------
}
