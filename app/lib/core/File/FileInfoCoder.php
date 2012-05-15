<?php
/** ---------------------------------------------------------------------
 * app/lib/core/File/FileInfoCoder.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2008 Whirl-i-Gig
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
 * @subpackage File
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__."/core/File/FileVolumes.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

$_FILE_INFO_CODER_INSTANCE_CACHE = null;

class FileInfoCoder {
	# ---------------------------------------------------------------------------
	private $opo_volume_info;
	# ---------------------------------------------------------------------------
	static public function load() {
		global $_FILE_INFO_CODER_INSTANCE_CACHE;
		
		if (!$_FILE_INFO_CODER_INSTANCE_CACHE) {
			$_FILE_INFO_CODER_INSTANCE_CACHE = new FileInfoCoder();
		}
		return $_FILE_INFO_CODER_INSTANCE_CACHE;
	}
	# ---------------------------------------------------------------------------
	public function __construct() {
		$this->opo_volume_info = new FileVolumes();
	}
	# ---------------------------------------------------------------------------
	# Support for Weblib field types
	# ---------------------------------------------------------------------------
	public function &getFileArray($ps_data) {
		if (!is_array($ps_data)) {
			$va_data = caUnserializeForDatabase($ps_data);
			return is_array($va_data) ? $va_data : false;
		} else {
			return $ps_data;
		}
	}
	# ---------------------------------------------------------------------------
	public function getFileInfo($ps_data) {
		if (!($va_file_info = $this->getFileArray($ps_data))) {
			return false;
		}
		return $va_file_info;
	}
	# ---------------------------------------------------------------------------
	public function getFilePath($ps_data) {
		if (!($va_file_info = $this->getFileArray($ps_data))) {
			return false;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_file_info["VOLUME"]);
    
		if (!is_array($va_volume_info)) {
		  return false;
	
		}    
		return join("/",array($va_volume_info["absolutePath"], $va_file_info["HASH"], $va_file_info["MAGIC"]."_".$va_file_info["FILENAME"]));
	}
	# ---------------------------------------------------------------------------
	public function getFileUrl($ps_data) {
		if (!($va_file_info = $this->getFileArray($ps_data))) {
			return false;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_file_info["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		
		$vs_protocol = 		$va_volume_info["protocol"];
		$vs_host = 			$va_volume_info["hostname"];
		$vs_url_path = 		$va_volume_info["urlPath"];
		$vs_fpath = 		join("/",array($vs_url_path, $va_file_info["HASH"], $va_file_info["MAGIC"]."_".$va_file_info["FILENAME"]));
		return $va_file_info["FILENAME"] ? $vs_protocol."://".$vs_host.$vs_fpath : "";
	}
	# ---------------------------------------------------------------------------
	public function hasFile($ps_data) {
		if (!($va_file_info = $this->getFileArray($ps_data))) {
			return false;
		}
		
		if (is_array($va_file_info)) {
			return true;
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	public function getFileConversions($ps_data) {
		if (!($va_file_info = $this->getFileArray($ps_data))) {
			return false;
		}
		
		if (!is_array($va_file_info) || !isset($va_file_info["CONVERSIONS"]) || !is_array($va_file_info["CONVERSIONS"])) {
			return array();
		}
		return $va_file_info["CONVERSIONS"];
	}
	# ---------------------------------------------------------------------------
	public function getFileConversionPath($ps_data, $ps_mimetype) {
		if (!($va_file_info = $this->getFileArray($ps_data))) {
			return false;
		}
	
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_file_info["VOLUME"]);
		
		if (!is_array($va_volume_info)) {
			return false;
		} 
		
		$va_conversions = $this->getFileConversions($va_file_info);
		
		if ($va_conversions[$ps_mimetype]) {
			return join("/",array($va_volume_info["absolutePath"], $va_file_info["HASH"], $va_file_info["MAGIC"]."_".$va_conversions[$ps_mimetype]["FILENAME"]));
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	public function getFileConversionUrl($ps_data, $ps_mimetype) {
		if (!($va_file_info = $this->getFileArray($ps_data))) {
			return false;
		}
	
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_file_info["VOLUME"]);
		
		if (!is_array($va_volume_info)) {
			return false;
		}   
		$va_conversions = $this->getFileConversions($va_file_info);
		
		
		if ($va_conversions[$ps_mimetype]) {
			return $va_volume_info["protocol"]."://".join("/", array($va_volume_info["hostname"], $va_volume_info["urlPath"], $va_file_info["HASH"], $va_file_info["MAGIC"]."_".$va_conversions[$ps_mimetype]["FILENAME"]));
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
}
?>