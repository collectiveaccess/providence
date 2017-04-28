<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/BinaryFile.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 
include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_LIB_DIR__."/core/Media.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaBinaryFile extends BaseMediaPlugin implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $properties;
	
	var $opo_config;
	
	var $info = array(
		"IMPORT" => array(
			"application/octet-stream" 					=> "bin"
		),
		
		"EXPORT" => array(
			"application/octet-stream"				=> "bin",
			"text/plain"							=> "txt",
			"image/jpeg"							=> "jpg",
			"image/png"								=> "png"
		),
		
		'TRANSFORMATIONS' => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing")
		),
		
		"PROPERTIES" => array(
			"width" 			=> 'W',
			"height" 			=> 'W',
			"mimetype" 			=> 'W',
			"typename"			=> 'W',
			"filesize" 			=> 'R',
			
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "BinaryFile",
		
		"MULTIPAGE_CONVERSION" => false, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => true
	);
	
	var $typenames = array(
		"application/octet-stream" 				=> "BinaryFile"
	);
	
	var $magick_names = array(
		"application/octet-stream" 				=> "BinaryFile"
	);
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts any file unrecognized by other media plugins and stores it as-is');

		$this->opo_config = Configuration::load();
		$this->opo_external_app_config = Configuration::load(__CA_CONF_DIR__."/external_applications.conf");
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = $this->opo_config->get('accept_all_files_as_media');
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	/**
	 * Always return binary mimetype
	 */
	public function divineFileFormat($ps_filepath) {
		if ($ps_filepath == '') { return ''; }

		$this->filepath = $this->handle = $ps_filepath;
		$this->properties['filesize'] = filesize($ps_filepath);
		$this->properties['typename'] = _t("Binary file");
		$this->properties['mimetype'] = "application/octet-stream";
		$this->properties['width'] = 1000;
		$this->properties['height'] = 1000;

		return "application/octet-stream";
	}
	# ------------------------------------------------
	public function read ($ps_filepath) {
		if ($this->filepath == $ps_filepath) {
			# noop
			return true;
		} else {
			if (!file_exists($ps_filepath)) {
				$this->postError(3000, _t("File %1 does not exist", $ps_filepath), "WLPlugMediaBinaryFile->read()");
				$this->filepath = "";
				return false;
			}
			
			$this->filepath = $this->handle = $ps_filepath;
			$this->properties['filesize'] = filesize($ps_filepath);
			$this->properties['typename'] = _t("Binary file");
			$this->properties['mimetype'] = "application/octet-stream";
			$this->properties['width'] = 1000;
			$this->properties['height'] = 1000;
			
			return true;
		}
		return false;	
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function &writePreviews($ps_filepath, $pa_options) {
		return null;
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		switch($operation) {
			case 'SCALE':
				$this->properties['width'] = $parameters['width'];
				$this->properties['height'] = $parameters['height'];
				break;
		}
		return true;
	}
	# ----------------------------------------------------------
	public function write($ps_filepath, $ps_mimetype) {
		
		# use default media icons
		return  __CA_MEDIA_BINARY_FILE_DEFAULT_ICON__;
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
}