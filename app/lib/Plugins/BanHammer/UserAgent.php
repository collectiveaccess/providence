<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/UserAgent.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2023 Whirl-i-Gig
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

class WLPlugBanHammerUserAgent Extends BaseBanHammerPlugin  {
	# ------------------------------------------------------
	/**
	 *
	 */
	static $priority = 100;
	
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function evaluate($request, $options=null) {
		self::init($request, $options);
		$config = self::$config->get('plugins.UserAgent');
		$banned_useragents = caGetOption('banned_useragents', $config, []);
		
		$request_useragent = $_SERVER["HTTP_USER_AGENT"];
		foreach($banned_useragents as $u) {
			if (preg_match("!".preg_quote($u, "!")."!i", $request_useragent)) {
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
		return 60 * 60 * 24;	// ban for 1 day
	}
	# ------------------------------------------------------
}
