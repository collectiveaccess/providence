<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Media/Spin360.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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
 
/**
 * Plugin for processing 3D object files
 */
 
include_once(__CA_LIB_DIR__."/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/Configuration.php");
include_once(__CA_LIB_DIR__."/Media.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaSpin360 extends BaseMediaPlugin implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	
	var $opo_config;
	
	var $info = array(
		"IMPORT" => array(
			"application/spincar" 					=> "zip"
		),
		
		"EXPORT" => array(
			"image/jpeg"							=> "jpg",
			"image/png"								=> "png",
			"application/spincar" 					=> "zip",
			'image/tiff' 							=> 'tiff',
			'image/tilepic' 						=> 'tpc'
		),
		
		'TRANSFORMATIONS' => array(
			'SCALE' 			=> array('width', 'height', 'mode', 'antialiasing'),
			'CROP' 				=> array('width', 'height', 'x', 'y'),
			'ANNOTATE'			=> array('text', 'font', 'size', 'color', 'position', 'inset'),
			'WATERMARK'			=> array('image', 'width', 'height', 'position', 'opacity'),
			'ROTATE' 			=> array('angle'),
			'SET' 				=> array('property', 'value'),
			'FLIP'				=> array('direction'),
			
			# --- filters
			'MEDIAN'			=> array('radius'),
			'DESPECKLE'			=> array(''),
			'SHARPEN'			=> array('radius', 'sigma'),
			'UNSHARPEN_MASK'	=> array('radius', 'sigma', 'amount', 'threshold'),
		),
		
		"PROPERTIES" => array(
			"width" 			=> 'W',
			"height" 			=> 'W',
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"mimetype" 			=> 'W',
			"typename"			=> 'W',
			"filesize" 			=> 'R',
			"quality"			=> 'W',
			
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "Spin360",
		
		"MULTIPAGE_CONVERSION" => true, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => true
	);
	
	var $typenames = array(
		"application/spincar" 				=> "SpinCar"
	);
	
	var $magick_names = array(
		"application/spincar" 				=> "SpinCar"
	);
	
	#
	# Alternative extensions for supported types
	#
	var $alternative_extensions = [];
	
	
	var $filelist = [];
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts ZIP archives containing 360 spinnable images in SpinCar format (http://SpinCar.com)');

		$this->opo_config = Configuration::load();
		$this->opo_external_app_config = Configuration::load(__CA_CONF_DIR__."/external_applications.conf");
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	/**
	 * Can we handle this file?
	 */
	public function divineFileFormat($ps_filepath) {
		if ($ps_filepath == '') {
			return '';
		}

		$this->filepath = $ps_filepath;

		if ($this->_isSpinCar($ps_filepath)) {
			return "application/spincar";
		}
		$this->filepath = null;
		return '';
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _isSpinCar($ps_filepath) {
		if (caIsArchive($ps_filepath)) {
			// Ok it's a ZIP... but it is SpinCar?
			$va_archive_files = caGetDirectoryContentsAsList('phar://'.$ps_filepath);
			foreach($va_archive_files as $vs_archive_path) {
				$vs_archive_path = str_replace("phar://{$ps_filepath}/", '', $vs_archive_path);
				$va_tmp = explode('/', $vs_archive_path);
				if (($va_tmp[0] == 'img') && ($va_tmp[1] == 'ec')) {
					return true;
				}
			}
		}
		return false;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _getFileList($ps_filepath, $pa_options=null) {
		$this->filelist = [];
		
		if (caIsArchive($ps_filepath)) {
			// Ok it's a ZIP... but it is SpinCar?
			$va_archive_files = caGetDirectoryContentsAsList('phar://'.$ps_filepath);
			
			$vn_i = 0;
			foreach($va_archive_files as $vs_archive_path) {
				$vs_archive_path_proc = str_replace("phar://{$ps_filepath}/", '', $vs_archive_path);
				$va_tmp = explode('/', $vs_archive_path_proc);
				if (($va_tmp[0] == 'img') && ($va_tmp[1] == 'ec')) {
					@copy($vs_archive_path, $vs_tmp_path = tempnam(caGetTempDirPath(), "caSpin360Temp"));
					$this->filelist[] = $vs_tmp_path;
					$vn_i++;
				}
			}
		}
		
		return $this->filelist;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _cleanupFileList() {
		if (!is_array($this->filelist)) { return false; }
		foreach($this->filelist as $vs_file) {
			@unlink($vs_file);
		}
		return true;
	}
	# ------------------------------------------------
	public function read ($ps_filepath) {
		if (is_array($this->handle) && ($this->filepath == $ps_filepath)) {
			# noop
			return true;
		} else {
			if (!file_exists($ps_filepath)) {
				$this->postError(3000, _t("File %1 does not exist", $ps_filepath), "WLPlugMediaSpin360->read()");
				$this->handle = $this->filepath = "";
				return false;
			}
			if (!($vs_mimetype = $this->divineFileFormat($ps_filepath))) {
				$this->postError(3005, _t("File %1 is not a valid ZIP archive or does not contain valid images", $ps_filepath), "WLPlugMediaSpin360->read()");
				$this->handle = $this->filepath = "";
				return false;
			}
			
			if (!($va_file_list = $this->_getFileList($ps_filepath))) {
				return false;
			} 
			
			// Load first one
			$o_media = new Media(true);
			if($va_file_list[0] && file_exists($va_file_list[0]) && ($o_media->read($va_file_list[0]))) {
				$this->properties["mimetype"] = $vs_mimetype;
				if (!($this->properties["typename"] = $this->typenames[$vs_mimetype])) {
					$this->properties["typename"] = "360";
				}
				
				$this->properties["width"] = $o_media->get('width');
				$this->properties["height"] = $o_media->get('height');
				$this->properties["quality"] = "";
				$this->properties["filesize"] = filesize($ps_filepath);
			
				$this->handle = $o_media;
				return true;
			}
		}
		return false;	
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function &writePreviews($ps_filepath, $pa_options) {
		return $this->filelist;
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if (!$this->handle) { return false; }
		return $this->handle->transform($operation, $parameters);
	}
	# ----------------------------------------------------------
	public function write($ps_filepath, $ps_mimetype) {
		if (!$this->handle) { return false; }

		$this->properties["width"] = $this->properties["version_width"];
		$this->properties["height"] = $this->properties["version_height"];
		
		# is mimetype valid?
		if (!($vs_ext = $this->info["EXPORT"][$ps_mimetype])) {
			$this->postError(1610, _t("Can't convert file to %1", $ps_mimetype), "WLPlugMediaSpin360->write()");
			return false;
		}

	
		$vs_output_path = $this->handle->write($ps_filepath, $ps_mimetype, array());
		
		# use default media icons
		return $vs_output_path ? $vs_output_path : __CA_MEDIA_SPIN_DEFAULT_ICON__;
	}
	# ------------------------------------------------
	public function init() {
		$this->errors = array();
		$this->read($this->filepath);
		
		$this->metadata = array();
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!is_array($pa_properties)) { $pa_properties = array(); }
		return caHTMLImage($ps_url, array_merge($pa_options, $pa_properties));
	}
	# ------------------------------------------------
	public function __destruct() {
		$this->_cleanupFileList();
	}
	# ------------------------------------------------
}
