<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/MediaInfoCoder.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2013 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/core/Media.php");
require_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

$_MEDIA_INFO_CODER_INSTANCE_CACHE = null;

class MediaInfoCoder {
	# ---------------------------------------------------------------------------
	private $opo_volume_info;
	# ---------------------------------------------------------------------------
	static public function load() {
		global $_MEDIA_INFO_CODER_INSTANCE_CACHE;
		
		if (!$_MEDIA_INFO_CODER_INSTANCE_CACHE) {
			$_MEDIA_INFO_CODER_INSTANCE_CACHE = new MediaInfoCoder();
		}
		return $_MEDIA_INFO_CODER_INSTANCE_CACHE;
	}
	# ---------------------------------------------------------------------------
	public function __construct() {
		$this->opo_volume_info = new MediaVolumes();
	}
	# ---------------------------------------------------------------------------
	# Support for field types
	# ---------------------------------------------------------------------------
	public function getMediaArray($ps_data) {
		if (!is_array($ps_data)) {
			$va_data = caUnserializeForDatabase($ps_data);
			return is_array($va_data) ? $va_data : false;
		} else {
			return $ps_data;
		}
	}
	# ---------------------------------------------------------------------------
	public function getMediaInfo($ps_data, $ps_version=null, $ps_key=null, $pa_options=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}	
		
		#
		# Use icon
		#
		if ($ps_version && (!$ps_key || (in_array($ps_key, array('WIDTH', 'HEIGHT'))))) {
			if (isset($va_media_info[$ps_version]['USE_ICON']) && ($vs_icon_code = $va_media_info[$ps_version]['USE_ICON'])) {
				if ($va_icon_size = caGetMediaIconForSize($vs_icon_code, $va_media_info[$ps_version]['WIDTH'], $va_media_info[$ps_version]['HEIGHT'])) {
					$va_media_info[$ps_version]['WIDTH'] = $va_icon_size['width'];
					$va_media_info[$ps_version]['HEIGHT'] = $va_icon_size['height'];
				}
			}
		} else {
			if (!$ps_key || (in_array($ps_key, array('WIDTH', 'HEIGHT')))) {
				foreach(array_keys($va_media_info) as $vs_version) {
					if (isset($va_media_info[$vs_version]['USE_ICON']) && ($vs_icon_code = $va_media_info[$vs_version]['USE_ICON'])) {
						if ($va_icon_size = caGetMediaIconForSize($vs_icon_code, $va_media_info[$vs_version]['WIDTH'], $va_media_info[$vs_version]['HEIGHT'])) {
							if (!$va_icon_size['size']) { continue; }
							$va_media_info[$vs_version]['WIDTH'] = $va_icon_size['width'];
							$va_media_info[$vs_version]['HEIGHT'] = $va_icon_size['height'];
						}
					}
				} 
			}
		}
		
		if ($ps_version) {
			if (!$ps_key) {
				return $va_media_info[$ps_version];
			} else { 
				return $va_media_info[$ps_version][$ps_key];
			}
		} else {
			return $va_media_info;
		}
	}
	# ---------------------------------------------------------------------------
	public function getMediaPath($ps_data, $ps_version, $pa_options=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$vn_page = 1;
		if (is_array($pa_options) && (isset($pa_options["page"])) && ($pa_options["page"] > 1)) {
			$vn_page = $pa_options["page"];
		}
		
		#
		# Use icon
		#
		if (isset($va_media_info[$ps_version]['USE_ICON']) && ($vs_icon_code = $va_media_info[$ps_version]['USE_ICON'])) {
			return caGetDefaultMediaIconPath($vs_icon_code, $va_media_info[$ps_version]['WIDTH'], $va_media_info[$ps_version]['HEIGHT']);
		}
		
		#
		# Is this version externally hosted?
		#
		if (isset($va_media_info[$ps_version]["EXTERNAL_URL"]) && ($va_media_info[$ps_version]["EXTERNAL_URL"])) {
			return '';		// no local path for externally hosted media
		}
		
		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && $va_media_info[$ps_version]["QUEUED"]) {
			return null;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		if ($va_media_info[$ps_version]["FILENAME"]) {
			if (isset($va_media_info[$ps_version]["PAGES"]) && ($va_media_info[$ps_version]["PAGES"] > 1)) {
				if ($vn_page < 1) { $vn_page = 1; }
				if ($vn_page > $va_media_info[$ps_version]["PAGES"]) { $vn_page = 1; }
				return join("/",array($va_volume_info["absolutePath"], $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILESTEM"]."_".$vn_page.".".$va_media_info[$ps_version]["EXTENSION"]));
			} else {
				return join("/",array($va_volume_info["absolutePath"], $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILENAME"]));
			}
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	/**
	 * 
	 * @param array $pa_options Supported options include:
	 *		localOnly = if true url to locally hosted media is always returned, even if an external url is available
	 *		externalOnly = if true url to externally hosted media is always returned, even if an no external url is available
	 */
	public function getMediaUrl($ps_data, $ps_version, $pa_options=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$vn_page = 1;
		if (is_array($pa_options) && (isset($pa_options["page"])) && ($pa_options["page"] > 1)) {
			$vn_page = $pa_options["page"];
		}
		
		#
		# Use icon
		#
		if (isset($va_media_info[$ps_version]['USE_ICON']) && ($vs_icon_code = $va_media_info[$ps_version]['USE_ICON'])) {
			return caGetDefaultMediaIconUrl($vs_icon_code, $va_media_info[$ps_version]['WIDTH'], $va_media_info[$ps_version]['HEIGHT']);
		}
		
		#
		# Is this version externally hosted?
		#
		if (!isset($pa_options['localOnly']) || !$pa_options['localOnly']){
			if (isset($va_media_info[$ps_version]["EXTERNAL_URL"]) && ($va_media_info[$ps_version]["EXTERNAL_URL"])) {
				return $va_media_info[$ps_version]["EXTERNAL_URL"];
			}
		}
		
		if (isset($pa_options['externalOnly']) && $pa_options['externalOnly']) {
			return $va_media_info[$ps_version]["EXTERNAL_URL"];
		}
		
		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && ($va_media_info[$ps_version]["QUEUED"])) {
			return null;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		
		# is this mirrored?
		if (isset($va_volume_info["accessUsingMirror"]) && ($va_volume_info["accessUsingMirror"]) && ($va_media_info["MIRROR_STATUS"][$va_volume_info["accessUsingMirror"]] == "SUCCESS")) {
			$vs_protocol = 	$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessProtocol"];
			$vs_host = 		$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessHostname"];
			$vs_url_path = 	$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessUrlPath"];  		
		} else {
			$vs_protocol = 	$va_volume_info["protocol"];
			$vs_host = 		$va_volume_info["hostname"];
			$vs_url_path = 	$va_volume_info["urlPath"];
		}
		
		$o_config = Configuration::load();
		if ($o_config->get('use_pdfjs_viewer')) {
			foreach($va_media_info as $vs_version => $va_info) {
				if (isset($va_info['MIMETYPE']) && ($va_info['MIMETYPE'] == 'application/pdf')) {
					JavascriptLoadManager::register("pdfjs");
				}
			}
		}
		
		
		if ($va_media_info[$ps_version]["FILENAME"]) {
			if (isset($va_media_info[$ps_version]["PAGES"]) && ($va_media_info[$ps_version]["PAGES"] > 1)) {
				if ($vn_page < 1) { $vn_page = 1; }
				if ($vn_page > $va_media_info[$ps_version]["PAGES"]) { $vn_page = 1; }
				$vs_fpath = join("/",array($vs_url_path, $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILESTEM"]."_".$vn_page.".".$va_media_info[$ps_version]["EXTENSION"]));
			} else {
				$vs_fpath = join("/",array($vs_url_path, $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILENAME"]));
			}
			return $vs_protocol."://$vs_host".$vs_fpath;
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	public function getMediaTag($ps_data, $ps_version, $pa_options=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		if (!is_array($pa_options)) {
			$pa_options = array();
		}
		if (!isset($pa_options["page"]) || ($pa_options["page"] < 1)) {
			$pa_options["page"] = 1;
		}
		
		#
		# Use icon
		#
		if (isset($va_media_info[$ps_version]['USE_ICON']) && ($vs_icon_code = $va_media_info[$ps_version]['USE_ICON'])) {
			return caGetDefaultMediaIconTag($vs_icon_code, $va_media_info[$ps_version]['WIDTH'], $va_media_info[$ps_version]['HEIGHT']);
		}
		
		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && ($va_media_info[$ps_version]["QUEUED"])) {
			return $va_media_info[$ps_version]["QUEUED_MESSAGE"];
		}
		
		$vs_url = $this->getMediaUrl($va_media_info, $ps_version, $pa_options["page"], $pa_options);
		$o_media = new Media();
		
		$o_vol = new MediaVolumes();
		$va_volume = $o_vol->getVolumeInformation($va_media_info[$ps_version]['VOLUME']);
		
		$va_properties = $va_media_info[$ps_version]["PROPERTIES"];
		if (isset($pa_options['width'])) { $va_properties['width'] = $pa_options['width']; }
		if (isset($pa_options['height'])) { $va_properties['height'] = $pa_options['height']; }
		
		$o_config = Configuration::load();
		if ($o_config->get('use_pdfjs_viewer')) {
			foreach($va_media_info as $vs_version => $va_info) {
				if (isset($va_info['MIMETYPE']) && ($va_info['MIMETYPE'] == 'application/pdf')) {
					JavascriptLoadManager::register("pdfjs");
				}
			}
		}
		
		return $o_media->htmlTag($va_media_info[$ps_version]["MIMETYPE"], $vs_url, $va_properties, $pa_options, $va_volume);
	}
	# ---------------------------------------------------------------------------
	public function getMediaVersions($ps_data) {
		if (!is_array($va_media_info)) {
			if (!($va_media_info = $this->getMediaArray($ps_data))) {
				return false;
			}
		}
		
		unset($va_media_info["ORIGINAL_FILENAME"]);
		unset($va_media_info["INPUT"]);
		unset($va_media_info["VOLUME"]);
		unset($va_media_info["_undo_"]);
		unset($va_media_info["TRANSFORMATION_HISTORY"]);
		
		return array_keys($va_media_info);		
	}
	# ---------------------------------------------------------------------------
	public function hasMedia($ps_data, $ps_field) {  
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		if (is_array($va_media_info)) {
			return true;
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	public function mediaIsMirrored($ps_data, $ps_version) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		if (is_array($va_volume_info["mirrors"])) {
			return sizeof($va_volume_info["mirrors"]);
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------
	public function getMediaMirrorStatus($ps_data, $ps_version, $ps_mirror="") {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		if ($ps_mirror) {
			return $va_media_info["MIRROR_STATUS"][$ps_mirror];
		} else {
			return $va_media_info["MIRROR_STATUS"][$va_volume_info["accessUsingMirror"]];
		}
	}
	# ---------------------------------------------------------------------------
}
?>