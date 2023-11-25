<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/ExporFrequency.php : 
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
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/Plugins/BanHammer/BaseBanHammerPlugin.php");

class WLPlugBanHammerExportFrequency Extends BaseBanHammerPlugIn  {
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
		$config = self::$config->get('plugins.ExportFrequency');
		if ((($frequency_threshold = (float)($config['frequency_threshold'] ?? 0)) < 0.1) || ($frequency_threshold > 999)) {
			$frequency_threshold = 10;
		}
		
		if ((($ban_probability = (float)($config['ban_probability'] ?? 0)) < 0) || ($ban_probability > 1.0)) {
			$ban_probability = 1.0;
		}
		
		// Frequency ban
		if (!($ip = RequestHTTP::ip())) { return 0; }
		$export_count = ExternalCache::fetch($ip, 'BanHammer_ExportCounts');
		if(!is_array($export_count)) {
			$export_count = ['s' => time(), 'c' => 1, 'total' => 1];
		} elseif((time() - $export_count['s']) > 60) {
			$export_count = ['s' => time(), 'c' => 1, 'total' => $export_count['total'] + 1];
		} else {
			$export_count['c']++;
			$export_count['total']++;
		}
		ExternalCache::save($ip, $export_count, 'BanHammer_ExportCounts');
	
		if (($interval = (time() - $export_count['s'])) > 0) {
			$freq = (float)$export_count['c']/(float)$interval;
			if ($freq > $frequency_threshold) { return $ban_probability; }
		}
		
		// Absolute count ban
		if(($exports_per_session = ($config['allowed_exports_per_session'] ?? 100)) > 0) {
			if($export_count['total'] > $exports_per_session) {
				return $ban_probability;
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
		return null;	// forever
	}
	# ------------------------------------------------------
	/**
	 * Ban is partial or global?
	 */
	static public function isPartial() {
		return true;	// only ban exports
	}
	# ------------------------------------------------------
}
