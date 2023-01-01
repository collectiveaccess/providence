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
		if($reasons = $opts->getOption('reason')) {
			if(!is_array($reasons)) { $reasons = preg_split('/[;,]/', $reasons); }
			$valid_reasons = array_map('strtolower', BanHammer::getPluginNames());
			$reasons = array_filter($reasons, function($v) use ($valid_reasons) {
				return in_array(strtolower($v), $valid_reasons, true);
			});
			if(!sizeof($reasons)) { 
				CLIUtils::addError(_t('Invalid reasons specified'));
				return false;
			}
		}
		if($from = $opts->getOption('from')) {
			if(!($dt = caDateToUnixTimestamp($from))) { 
				CLIUtils::addError(_t('Invalid from date specified'));	
				return false;
			}
		}
		
		if(!is_null($count = ca_ip_bans::removeBans(['reasons' => $reasons, 'from' => $from]))) {
			CLIUtils::addMessage(($count == 1) ? _t('Removed %1 ban', $count) : _t('Removed %1 bans', $count));	
		} else {
			CLIUtils::addError(_t('Could not remove bans'));	
		}

		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function clear_bansParamList() {
		return [
			"reason|r-s" => _t('Comma separated list of ban reasons to clear. If omitted all bans will be removed.'),
			"from|f-s" => _t('Remove bans created on or before a date. If omitted all bans will be removed.')
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
