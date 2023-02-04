<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/BaseBanHammerPlugin.php : 
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

class BaseBanHammerPlugin {
	# ------------------------------------------------------
	/**
	 *
	 */
	static $priority = 10;
	
	/**
	 *
	 */
	static $config;
	
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function init($request, $options=null) {
		if(!self::$config) { self::$config = Configuration::load(__CA_CONF_DIR__.'/ban_hammer.conf'); }
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function evaluate($request, $options=null) {
		return 0;	// default is pass everything (zero probability of attack)
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function shouldBanIP() {
		return true;	// default is to ban IP on failure
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function banTTL() {
		return null;	// default is to ban ip forever
	}
	# ------------------------------------------------------
}
