<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/MediaVolumes.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2008 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__."/core/Configuration.php");

$_MEDIA_VOLUME_INSTANCE_CACHE = null;

class MediaVolumes {
	var $o_info;  # contains loaded volume information
	
	# ------------------------------------------------
	static public function load() {
		global $_MEDIA_VOLUME_INSTANCE_CACHE;
		
		if (!$_MEDIA_VOLUME_INSTANCE_CACHE) {
			$_MEDIA_VOLUME_INSTANCE_CACHE = new MediaVolumes();
		}
		return $_MEDIA_VOLUME_INSTANCE_CACHE;
	}
	# ------------------------------------------------
	function __construct() {
		# -- Load volumes configuration file
		$o_config = Configuration::load();
		if (file_exists($o_config->get("media_volumes"))) {
			$this->o_info = Configuration::load($o_config->get("media_volumes"));
		}
	}
	# ------------------------------------------------
	# returns block of information as associative array for given volume
	function getVolumeInformation($ps_volume) {
		if (!$this->o_info) { return null; }
		if ($va_volume_info = $this->o_info->getAssoc($ps_volume)) {
			return $va_volume_info;
		} else {
			return null;
		}
	}
	# ------------------------------------------------
	function getAllVolumeInformation() {
		if (!$this->o_info) { return null; }
		$va_keys = $this->o_info->getAssocKeys();
		
		$va_info = array();
		foreach($va_keys as $vs_key) {
			$va_info[$vs_key] = $this->getVolumeInformation($vs_key);
		}
		
		return $va_info;
	}
	# ------------------------------------------------
}
?>