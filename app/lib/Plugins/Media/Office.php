<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Media/Office.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2018 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is fdistributed in the hope that it will be useful, but
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
 * Plugin for processing Microsoft Word and Excel documents
 */
 
require_once(__CA_LIB_DIR__."/Plugins/Media/BaseMediaPlugin.php");
require_once(__CA_LIB_DIR__."/Plugins/IWLPlugMedia.php");
require_once(__CA_LIB_DIR__."/Configuration.php");
require_once(__CA_LIB_DIR__."/Media.php");
require_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
require_once(__CA_LIB_DIR__."/Parsers/UnZipFile.php");

class WLPlugMediaOffice Extends BaseMediaPlugin Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	
	var $opo_config;
	var $opo_external_app_config;
	var $ops_libreoffice_path;
	
	var $opa_metadata;
	
	var $info = array(
		"IMPORT" => array(
			"text/rtf" 								=> "rtf",
			"application/msword" 					=> "doc",
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document" 					=> "docx",
			"application/vnd.ms-excel" 				=> "xls",
			"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" 						=> "xlsx",
			"application/vnd.ms-powerpoint"			=> "ppt",
			"application/vnd.openxmlformats-officedocument.presentationml.presentation" 				=> "pptx"
		),
		
		"EXPORT" => array(
			"application/msword" 					=> "doc",
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
			"application/vnd.ms-excel" 				=> "xls",
			"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" 		=> "xlsx",
			"application/vnd.ms-powerpoint"			=> "ppt",
			"application/vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
			"application/pdf"						=> "pdf",
			"text/html"								=> "html",
			"text/plain"							=> "txt",
			"image/jpeg"							=> "jpg",
			"image/png"								=> "png"
		),
		
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing"),
			"ANNOTATE"			=> array("text", "font", "size", "color", "position", "inset"),	// dummy
			"WATERMARK"			=> array("image", "width", "height", "position", "opacity"),	// dummy
			"SET" 				=> array("property", "value")
		),
		
		"PROPERTIES" => array(
			"width" 			=> 'W', # in pixels
			"height" 			=> 'W', # in pixels
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"mimetype" 			=> 'W',
			"quality"			=> 'W',
			"pages"				=> 'R',
			"page"				=> 'W', # page to output as JPEG or TIFF
			"resolution"		=> 'W', # resolution of graphic in pixels per inch
			"filesize" 			=> 'R',
			"antialiasing"		=> 'W', # amount of antialiasing to apply to final output; 0 means none, 1 means lots; a good value is 0.5
			"crop"				=> 'W', # if set to geometry value (eg. 72x72) image will be cropped to those dimensions; set by transform() to support fill_box SCALE mode 
			"scaling_correction"=> 'W',	# percent scaling required to correct sizing of image output by Ghostscript (Ghostscript does not do fractional resolutions)
			"target_width"		=> 'W',
			"target_height"		=> 'W',
			
			"colors"			=> 'W', # number of colors in output PNG-format image; default is 256
			
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "Office",
		
		"MULTIPAGE_CONVERSION" => true, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => false
	);
	
	var $typenames = array(
		"application/pdf" 				=> "PDF",
		"application/msword" 			=> "Microsoft Word",
		"application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "Microsoft Word/OpenOffice",
		"application/vnd.ms-excel" 		=> "Microsoft Excel",
		"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "Microsoft Excel/OpenOffice",
		"application/vnd.ms-powerpoint"			=> "Microsoft PowerPoint",
		"application/vnd.openxmlformats-officedocument.presentationml.presentation" 				=> "Microsoft PowerPoint/OpenOffice",
		"text/html" 					=> "HTML",
		"text/plain" 					=> "Plain text",
		"image/jpeg"					=> "JPEG",
		"image/png"						=> "PNG",
		"text/rtf"						=> "RTF"
	);
	
	var $magick_names = array(
		"application/pdf" 				=> "PDF",
		"application/msword" 			=> "DOC",
		"text/html" 					=> "HTML",
		"text/plain" 					=> "TXT"
	);
	
	#
	# Alternative extensions for supported types
	#
	var $alternative_extensions = [];
	
	/**
	 * Cache Office -> PDF file conversions across invocations in current request
	 */
	static $s_pdf_conv_cache = array();
	
	var $opa_transformations = array();
	
	var $opb_libre_office_installed = false;
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts and processes Microsoft Word, Excel and PowerPoint format documents');
		$this->opa_transformations = array();
		
		$this->opa_metadata = array();
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$this->opo_external_app_config = Configuration::load(__CA_CONF_DIR__."/external_applications.conf");
		$this->ops_libreoffice_path = $this->opo_external_app_config->get('libreoffice_app');
		$this->opb_libre_office_installed = caMediaPluginLibreOfficeInstalled($this->ops_libreoffice_path);
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		}
		
		if (!($this->opb_libre_office_installed)) { 
			$va_status['warnings'][] = _t("LibreOffice cannot be found: conversion to PDF and generation of page previews will not be performed; you can obtain LibreOffice at http://www.libreoffice.org/");
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($ps_filepath) {
		if ($ps_filepath == '') { return ''; }
		
		if ($vs_mimetype = $this->isWordExcelorPPTdoc($ps_filepath)) {
			switch($vs_mimetype) {
				case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
					$this->properties = $this->handle = $this->ohandle = array(
						"mimetype" => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
						"filesize" => filesize($ps_filepath),
						"typename" => "Microsoft Word/OpenOffice",
						"content" => ""
					);
					break;
				case 'application/msword':
					$this->properties = $this->handle = $this->ohandle = array(
						"mimetype" => 'application/msword',
						"filesize" => filesize($ps_filepath),
						"typename" => "Microsoft Word",
						"content" => ""
					);
					break;
				case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
					$this->properties = $this->handle = $this->ohandle = array(
						"mimetype" => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
						"filesize" => filesize($ps_filepath),
						"typename" => "Microsoft Excel/OpenOffice",
						"content" => ""
					);
					break;
				case 'application/vnd.ms-excel':
					$this->properties = $this->handle = $this->ohandle = array(
						"mimetype" => 'application/vnd.ms-excel',
						"filesize" => filesize($ps_filepath),
						"typename" => "Microsoft Excel",
						"content" => ""
					);
					break;
				case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
					$this->properties = $this->handle = $this->ohandle = array(
						"mimetype" => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
						"filesize" => filesize($ps_filepath),
						"typename" => "Microsoft PowerPoint/OpenOffice",
						"content" => ""
					);
					break;
				case 'application/vnd.ms-powerpoint':
					$this->properties = $this->handle = $this->ohandle = array(
						"mimetype" => 'application/vnd.ms-powerpoint',
						"filesize" => filesize($ps_filepath),
						"typename" => "Microsoft PowerPoint",
						"content" => ""
					);
					break;
				default;
					throw new ApplicationException(_t('Unsupported mimetype %1', $vs_mimetype));
					break;
			}	
			return $vs_mimetype;
		}
			
		return '';
	}
	# ----------------------------------------------------------
	/**
	 * 
	 */
	private function isWord972000doc($ps_filepath) {
		if ($r_fp = @fopen($ps_filepath, "r")) {
			$vs_sig = fgets($r_fp, 9);
			// Testing on the first 8 bytes of the file isn't great... 
			// any Microsoft Compound Document formated
			// file will be accepted by this test.
			if (
				(ord($ps_sig{0}) == 0xD0) &&
				(ord($ps_sig{1}) == 0xCF) &&
				(ord($ps_sig{2}) == 0x11) &&
				(ord($ps_sig{3}) == 0xE0) &&
				(ord($ps_sig{4}) == 0xA1) &&
				(ord($ps_sig{5}) == 0xB1) &&
				(ord($ps_sig{6}) == 0x1A) &&
				(ord($ps_sig{7}) == 0xE1)
			) {
				// Look for Word string in doc... this is hacky but seems to work well
				// If it has both the file sig above and this string it's pretty likely
				// a Word file
				while (!feof($r_fp)) {
					$buffer = fgets($r_fp, 32000);
				if (preg_match("!W.{1}o.{1}r.{1}d.{1}D.{1}o.{1}c.{1}u.{1}m.{1}e.{1}n.{1}t!", $buffer) !== false) {
						return true;
					}
				}
			}
			fclose($r_fp);
		}
		
		return false;
	}
	# ----------------------------------------------------------
	/**
	 * Detect if document pointed to by $ps_filepath is a valid Word, Excel or PowerPoint XML (OpenOffice) document.
	 *
	 * @param string $ps_filepath The path to the file to analyze
	 * @param string $ps_sig The signature (first 9 bytes) of the file
	 * @return string WORD if the document is a Word doc, EXCEL if the document is an Excel doc, PPT if it is a PowerPoint doc or boolean false if it's not a valid Word or Excel XML (OpenOffice) file
	 */
	private function isWordExcelorPPTdoc($ps_filepath) {
		// Check Powerpoint
		if (in_array(pathinfo(strtolower($ps_filepath), PATHINFO_EXTENSION), ['ppt', 'pptx'])) {
			$va_ppt_types = ['PowerPoint2007' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'PowerPoint97' => 'application/vnd.ms-powerpoint'];
		
			foreach ($va_ppt_types as $vs_type => $vs_mimetype) {
				$o_reader = \PhpOffice\PhpPresentation\IOFactory::createReader($vs_type);
				if ($o_reader->canRead($ps_filepath)) {
					return $vs_mimetype;
				}
			}
		}
		
		// 2007+ .docx files
		if (in_array(pathinfo(strtolower($ps_filepath), PATHINFO_EXTENSION), ['doc', 'docx'])) {	// PhpWord often will identify Excel docs as Word (and PHPExcel will identify Word docs as Excel...) so we test file extensions here			
			// Check Word
			if ($this->isWord972000doc($ps_filepath)) {		// old-style .doc files
				return 'application/msword';
			}
		
			$va_word_types = ['Word2007' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
		
			foreach ($va_word_types as $vs_type => $vs_mimetype) {
				$o_reader = \PhpOffice\PhpWord\IOFactory::createReader($vs_type);
				if ($o_reader->canRead($ps_filepath)) {
					return $vs_mimetype;
				}
			}
		}
		
		
		// Check Excel
		if (in_array(pathinfo(strtolower($ps_filepath), PATHINFO_EXTENSION), ['xls', 'xlsx'])) {
			$va_excel_types = ['Excel2007' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Excel5' => 'application/vnd.ms-excel', 'Excel2003XML' => 'application/vnd.ms-excel'];
			foreach ($va_excel_types as $vs_type => $vs_mimetype) {
				$o_reader = PHPExcel_IOFactory::createReader($vs_type);
				if ($o_reader->canRead($ps_filepath)) {
					return $vs_mimetype;
				}
			}
		}
	
		return false;
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				switch($property) {
					default:
						if ($this->info["PROPERTIES"][$property] == 'W') {
							$this->properties[$property] = $value;
						} else {
							# read only
							return '';
						}
						break;
				}
			} else {
				# invalid property
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugMediaOffice->set()");
				return '';
			}
		} else {
			return '';
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Returns text content for indexing, or empty string if plugin doesn't support text extraction
	 *
	 * @return String Extracted text
	 */
	public function getExtractedText() {
		return isset($this->handle['content']) ? $this->handle['content'] : '';
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		return $this->opa_metadata;
	}
	# ------------------------------------------------
	public function read ($ps_filepath) {
		if (is_array($this->handle) && ($this->handle["filepath"] == $ps_filepath)) {
			# noop
		} else {
			if (!file_exists($ps_filepath)) {
				$this->postError(1650, _t("File %1 does not exist", $ps_filepath), "WLPlugMediaOffice->read()");
				$this->handle = "";
				$this->filepath = "";
				return false;
			}
			if (!($this->divineFileFormat($ps_filepath))) {
				$this->postError(1650, _t("File %1 is not a Microsoft Word, Excel or PowerPoint document", $ps_filepath), "WLPlugMediaOffice->read()");
				$this->handle = "";
				$this->filepath = "";
				return false;
			}
		}
		$this->filepath = $ps_filepath;
		
		// Hardcode width/height since we haven't any way of calculating these short of generating a PDF
		$this->set('width', 612);
		$this->set('height', 792);
		$this->set('resolution', 72);
		
		$this->ohandle = $this->handle = $this->properties;
			
		return true;	
	}
	# ----------------------------------------------------------
	public function transform($ps_operation, $pa_parameters) {
		if (!$this->handle) { return false; }
		$this->opa_transformations[] = array('op' => $ps_operation, 'params' => $pa_parameters);
		
		if (!($this->info["TRANSFORMATIONS"][$ps_operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $ps_operation), "WLPlugOffice->transform()");
			return false;
		}
		
		# get parameters for this operation
		$sparams = $this->info["TRANSFORMATIONS"][$ps_operation];
		
		$this->properties["version_width"] = $w = $pa_parameters["width"];
		$this->properties["version_height"] = $h = $pa_parameters["height"];
		
		$cw = $this->get("width");
		$ch = $this->get("height");
		
		switch($ps_operation) {
			# -----------------------
			case "SET":
				while(list($k, $v) = each($pa_parameters)) {
					$this->set($k, $v);
				}
				break;
			# -----------------------
			case "SCALE":
				$vn_width_ratio = $w/$cw;
				$vn_height_ratio = $h/$ch;
				$vn_orig_resolution = $this->get("resolution");
				switch($pa_parameters["mode"]) {
					# ----------------
					case "width":
						$vn_resolution = ceil($vn_orig_resolution * $vn_width_ratio);
						$vn_scaling_correction = $w/ceil($vn_resolution * ($cw/$vn_orig_resolution));
						break;
					# ----------------
					case "height":
						$vn_resolution = ceil($vn_orig_resolution * $vn_height_ratio);
						$vn_scaling_correction = $h/ceil($vn_resolution * ($ch/$vn_orig_resolution));
						break;
					# ----------------
					case "fill_box":
						if ($vn_width_ratio < $vn_height_ratio) {
							$vn_resolution = ceil($vn_orig_resolution * $vn_width_ratio);
							$vn_scaling_correction = $w/ceil($vn_resolution * ($cw/$vn_orig_resolution));
						} else {
							$vn_resolution = ceil($vn_orig_resolution * $vn_height_ratio);
							$vn_scaling_correction = $h/ceil($vn_resolution * ($ch/$vn_orig_resolution));
						}
						$this->set("crop",$w."x".$h);
						break;
					# ----------------
					case "bounding_box":
					default:
						if ($vn_width_ratio > $vn_height_ratio) {
							$vn_resolution = ceil($vn_orig_resolution * $vn_height_ratio);
							$vn_scaling_correction = $h/ceil($vn_resolution * ($ch/$vn_orig_resolution));
						} else {
							$vn_resolution = ceil($vn_orig_resolution * $vn_width_ratio);
							$vn_scaling_correction = $w/ceil($vn_resolution * ($cw/$vn_orig_resolution));
						}
						break;
					# ----------------
				}
				
				$this->properties["scaling_correction"] = $vn_scaling_correction;
				
				$this->properties["resolution"] = $vn_resolution;
				$this->properties["width"] = ceil($vn_resolution * ($cw/$vn_orig_resolution));
				$this->properties["height"] = ceil($vn_resolution * ($ch/$vn_orig_resolution));
				$this->properties["target_width"] = $w;
				$this->properties["target_height"] = $h;
				$this->properties["antialiasing"] = ($pa_parameters["antialiasing"]) ? 1 : 0;
				break;
			# -----------------------
		}
		return true;
	}
	# ----------------------------------------------------------
	/**
	 * @param array $pa_options Options include:
	 *		dontUseDefaultIcons = If set to true, write will fail rather than use default icons when preview can't be generated. Default is false – to use default icons.
	 *
	 */
	public function write($ps_filepath, $ps_mimetype, $pa_options=null) {
		if (!$this->handle) { return false; }
		
		$vb_dont_allow_default_icons = (isset($pa_options['dontUseDefaultIcons']) && $pa_options['dontUseDefaultIcons']) ? true : false;
		
		# is mimetype valid?
		if (!($vs_ext = $this->info["EXPORT"][$ps_mimetype])) {
			$this->postError(1610, _t("Can't convert file to %1", $ps_mimetype), "WLPlugMediaOffice->write()");
			return false;
		} 
		
		# write the file
		if ($ps_mimetype == "application/msword") {
			if ( !copy($this->filepath, $ps_filepath.".doc") ) {
				$this->postError(1610, _t("Couldn't write file to '%1'", $ps_filepath), "WLPlugMediaOffice->write()");
				return false;
			}
		} else {
			if (!isset(WLPlugMediaOffice::$s_pdf_conv_cache[$this->filepath]) && $this->opb_libre_office_installed) {
				$vs_tmp_dir_path = caGetTempDirPath();
				$va_tmp = explode("/", $this->filepath);
				$vs_out_file = array_pop($va_tmp);
				
				putenv("HOME={$vs_tmp_dir_path}");		// libreoffice will fail silently if you don't set this environment variable to a directory it can write to. Nice way to waste a day debugging. Yay!
				exec($this->ops_libreoffice_path." --headless --convert-to pdf:writer_pdf_Export \"-env:UserInstallation=file:///tmp/LibreOffice_Conversion_${USER}\" ".caEscapeShellArg($this->filepath)."  --outdir ".caEscapeShellArg($vs_tmp_dir_path).(caIsPOSIX() ? " 2>&1" : ""), $va_output, $vn_return);
				exec($this->ops_libreoffice_path." --headless --convert-to html:HTML \"-env:UserInstallation=file:///tmp/LibreOffice_Conversion_${USER}\" ".caEscapeShellArg($this->filepath)."  --outdir ".caEscapeShellArg($vs_tmp_dir_path).(caIsPOSIX() ? " 2>&1" : ""), $va_output, $vn_return);
			
				$va_out_file = explode(".", $vs_out_file);
				if (sizeof($va_out_file) > 1) { array_pop($va_out_file); }
				$this->handle['content'] = $this->ohandle['content']  = file_exists("{$vs_tmp_dir_path}/".join(".", $va_out_file).".html") ? strip_tags(file_get_contents("{$vs_tmp_dir_path}/".join(".", $va_out_file).".html")) : '';
				$va_out_file[] = 'pdf';
				
				
				if (sizeof(WLPlugMediaOffice::$s_pdf_conv_cache) > 100) { WLPlugMediaOffice::$s_pdf_conv_cache = array_slice(WLPlugMediaOffice::$s_pdf_conv_cache, 50); }
				WLPlugMediaOffice::$s_pdf_conv_cache[$this->filepath] = "{$vs_tmp_dir_path}/".join(".", $va_out_file);
				$o_media = new Media();
				if ($o_media->read(WLPlugMediaOffice::$s_pdf_conv_cache[$this->filepath])) {
					$this->set('width', $this->ohandle["width"] = $o_media->get('width'));
					$this->set('height', $this->ohandle["height"] = $o_media->get('height'));
					$this->set('resolution', $this->ohandle["resolution"] = $o_media->get('resolution'));
				}
			}
			
			if ($vs_media = WLPlugMediaOffice::$s_pdf_conv_cache[$this->filepath]) {
				switch($ps_mimetype) {
					case 'application/pdf':
						$o_media = new Media();
						$o_media->read($vs_media);
						$o_media->set('version', $this->get('version'));
						$o_media->write($ps_filepath, $ps_mimetype, array());
						$vs_filepath_with_extension = $ps_filepath.".pdf";
						break;
					case 'image/jpeg':
						$o_media = new Media();
						$o_media->read($vs_media);
						$o_media->set('version', $this->get('version'));
						foreach($this->opa_transformations as $va_transform) {
							$o_media->transform($va_transform['op'], $va_transform['params']);
						}
						
						$o_media->write($ps_filepath, $ps_mimetype, array());
						$this->set('width', $o_media->get('width'));
						$this->set('height', $o_media->get('height'));
						$vs_filepath_with_extension = $ps_filepath.".jpg";
						break;
				}
			}
			
			# use default media icons
			if (!file_exists($vs_filepath_with_extension)) {	// always jpegs
				return $vb_dont_allow_default_icons ? null : __CA_MEDIA_DOCUMENT_DEFAULT_ICON__;
			}
		}
		
		
		$this->properties["mimetype"] = $ps_mimetype;
		$this->properties["filesize"] = filesize($ps_filepath.".".$vs_ext);
		//$this->properties["typename"] = $this->typenames[$ps_mimetype];
		
		return $ps_filepath.".".$vs_ext;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function &writePreviews($ps_filepath, $pa_options) {
		if ($vs_pdf_path = WLPlugMediaOffice::$s_pdf_conv_cache[$this->filepath]) {
			$o_media = new Media();
			if ($o_media->read($vs_pdf_path)) {
				return $o_media->writePreviews(array_merge($pa_options, array('dontUseDefaultIcons' => true)));	
			}
		
		}
		return null;
	}
	# ------------------------------------------------
	public function getOutputFormats() {
		return $this->info["EXPORT"];
	}
	# ------------------------------------------------
	public function getTransformations() {
		return $this->info["TRANSFORMATIONS"];
	}
	# ------------------------------------------------
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	public function mimetype2extension($mimetype) {
		return $this->info["EXPORT"][$mimetype];
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	public function extension2mimetype($extension) {
		reset($this->info["EXPORT"]);
		while(list($k, $v) = each($this->info["EXPORT"])) {
			if ($v === $extension) {
				return $k;
			}
		}
		return '';
	}
	# ------------------------------------------------
	public function reset() {
		return $this->init();
	}
	# ------------------------------------------------
	public function init() {
		$this->errors = array();
		$this->handle = $this->ohandle;
		$this->opa_transformations = array();
		$this->properties = array(
			"mimetype" => $this->ohandle["mimetype"],
			"filesize" => $this->ohandle["filesize"],
			"typename" => $this->ohandle["typename"],
			"width" => $this->ohandle["width"],
			"height" => $this->ohandle["height"],
			"resolution" => $this->ohandle["resolution"]
		);
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		foreach(array(
			'name', 'url', 'viewer_width', 'viewer_height', 'idname',
			'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style'
		) as $vs_k) {
			if (!isset($pa_options[$vs_k])) { $pa_options[$vs_k] = null; }
		}
		
		$vn_viewer_width = intval($pa_options['viewer_width']);
		if ($vn_viewer_width < 100) { $vn_viewer_width = 400; }
		$vn_viewer_height = intval($pa_options['viewer_height']);
		if ($vn_viewer_height < 100) { $vn_viewer_height = 400; }
		
		if (!($vs_id = isset($pa_options['id']) ? $pa_options['id'] : $pa_options['name'])) {
			$vs_id = '_msword';
		}
			
		if(preg_match("/\.doc\$/", $ps_url)) {
			return "<a href='$ps_url' target='_pdf'>"._t('Click to view Microsoft Word document')."</a>";
		} else {
			if(preg_match("/\.pdf\$/", $ps_url)) {
				if ($vs_poster_frame_url =	$pa_options["poster_frame_url"]) {
					$vs_poster_frame = "<img src='{$vs_poster_frame_url}'/ alt='"._t("Click to download document")."' title='"._t("Click to download document")."'>";
				} else {
					$vs_poster_frame = _t("View PDF document");
				}
				
				return $vs_buf;
			} else {
				if (!is_array($pa_options)) { $pa_options = array(); }
				if (!is_array($pa_properties)) { $pa_properties = array(); }
				return caHTMLImage($ps_url, array_merge($pa_options, $pa_properties));
			}
		}
	}
	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}

function WLPlugOfficeShutdown() {
	// Cleanup tmp files
	foreach(WLPlugMediaOffice::$s_pdf_conv_cache as $vs_filepath => $vs_pdf_path) {
		if(file_exists($vs_pdf_path)) {
			@unlink($vs_pdf_path);
			@unlink(preg_replace("!\.pdf$!", ".html", $vs_pdf_path));
		}
	}
}

register_shutdown_function("WLPlugOfficeShutdown");
