<?php
/** ---------------------------------------------------------------------
 * app/lib/CookieOptionsManager.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
require_once(__CA_LIB_DIR__."/Controller/Request/Session.php");

class CookieOptionsManager {
	# -------------------------------------------------------
	/**
	 *
	 */
	static $config = null;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function init() {
		if(!self::$config) { 
			self::$config = Configuration::load(__CA_CONF_DIR__."/cookies.conf");
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function cookies() {
		return self::$config->get('cookiesByCategory');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function categoryExists(string $category) {
		$cookies_by_category = self::cookies();
		if(isset($cookies_by_category[$category])) { return true; }
		return false;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function cookieList() {
		$cookies_by_category = array_filter(self::cookies(), function($v) {
			return (isset($v['cookies']) && is_array($v['cookies']) && sizeof($v['cookies']));
		});
		
		$lifespan = caFormatInterval(Session::lifetime());
		$cookies_by_category = array_map(function($v) use ($lifespan) { 
			foreach($v['cookies'] as $c => $ci) {
				$v['cookies'][$c]['description'] = str_replace("^lifespan", $lifespan, $v['cookies'][$c]['description']);
			}
			$c = sizeof($v['cookies']);
			$v['cookieCount'] = ($c === 1) ? _t('%1 cookie', $c) : _t('%1 cookies', $c);
			
			return $v;
		}, $cookies_by_category);
		
		return $cookies_by_category;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function showBanner() {
		if(!self::cookieManagerEnabled()) { return false; }
		global $g_request;
		if($g_request && strtolower($g_request->getController()) === 'cookies') { return false; }
		return !Session::getVar("cookie_options_accepted");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function allow(string $category) {
		if(!self::cookieManagerEnabled()) { return true; }
		if(!self::categoryExists($category)) { return null; }
		
		return Session::getVar("cookie_options_allow_{$category}");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function allowAll() {
		Session::setVar("cookie_options_accepted", true);
		foreach(self::cookieList() as $category_code => $category_info) {
			Session::setVar("cookie_options_allow_{$category_code}", 1);
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function set(string $category, bool $allow) {
		if(!self::categoryExists($category)) { return null; }
		Session::setVar("cookie_options_accepted", true);
		Session::setVar("cookie_options_allow_{$category}", $allow);
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	static public function cookieManagerEnabled() {
		return self::$config->get("enable_cookie_manager");
	}
	# -------------------------------------------------------
}

CookieOptionsManager::init();
