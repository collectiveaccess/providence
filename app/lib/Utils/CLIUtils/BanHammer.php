<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/BanHammer.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 
trait CLIUtilsBanHammer { 
	# -------------------------------------------------------
	/**
	 * Rebuild search indices
	 */
	public static function clear_bans($opts=null) {
		

		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function clear_bansParamList() {
		return [
			"to|t-s" => _t('Email address to send test message to.')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function clear_bansUtilityClass() {
		return _t('Bans');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function clear_bansHelp() {
		return _t("Use this utility to clear all banned IP addresses.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function clear_bansShortHelp() {
		return _t("Clear all bans.");
	}
	# -------------------------------------------------------
}
