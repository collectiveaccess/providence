<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/MSWord.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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
 * Plugin for processing Microsoft Word documents
 */
 
include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_LIB_DIR__."/core/Media.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaMSWord Extends WLPlug Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	
	var $opo_config;
	var $opo_external_app_config;
	var $ops_abiword_path;
	var $ops_libreoffice_path;
	
	var $info = array(
		"IMPORT" => array(
			"text/rtf" 								=> "rtf",
			"application/msword" 					=> "doc",
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document" 					=> "docx"
		),
		
		"EXPORT" => array(
			"application/msword" 					=> "doc",
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
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
		
		"NAME" => "MSWord",
		
		"MULTIPAGE_CONVERSION" => true, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => false
	);
	
	var $typenames = array(
		"application/pdf" 				=> "PDF",
		"application/msword" 			=> "Microsoft Word",
		"text/html" 					=> "HTML",
		"text/plain" 					=> "Plain text",
		"image/jpeg"					=> "JPEG",
		"image/png"						=> "PNG",
		"text/rtf"						=> "RTF",
		"application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "Microsoft Office 2007"
	);
	
	var $magick_names = array(
		"application/pdf" 				=> "PDF",
		"application/msword" 			=> "DOC",
		"text/html" 					=> "HTML",
		"text/plain" 					=> "TXT"
	);
	
	static $s_pdf_conv_cache = array();
	
	var $opa_transformations = array();
	
	var $opb_abiword_installed = false;
	var $opb_libre_office_installed = false;
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts and processes Microsoft Word 97, 2000 and 2007 format documents');
		$this->opa_transformations = array();
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_abiword_path = $this->opo_external_app_config->get('abiword_app');
		$this->opb_abiword_installed = caMediaPluginAbiwordInstalled($this->ops_abiword_path);
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
		
		if (!($this->opb_abiword_installed)) { 
			$va_status['warnings'][] = _t("ABIWord cannot be found: indexing of text in Microsoft Word files will not be performed; you can obtain ABIWord at http://www.abisource.com/");
		}
		
		if (!($this->opb_libre_office_installed)) { 
			$va_status['warnings'][] = _t("LibreOffice cannot be found: conversion to PDF and generation of page previews will not be performed; you can obtain LibreOffice at http://www.libreoffice.org/");
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($ps_filepath) {
		if ($ps_filepath == '') {
			return '';
		}
		
		if ($r_fp = fopen($ps_filepath, "r")) {
			$vs_sig = fgets($r_fp, 9);
			if ($this->isWord2008doc($vs_sig) || $this->isWord972000doc($vs_sig, $r_fp)) {
				$this->properties = $this->handle = $this->ohandle = array(
					"mimetype" => 'application/msword',
					"filesize" => filesize($ps_filepath),
					"typename" => "Microsoft Word",
					"content" => ""
				);
				fclose($r_fp);
				return "application/msword";
			}
		}
		fclose($r_fp);
		return '';
	}
	# ----------------------------------------------------------
	private function isWord972000doc($ps_sig, $r_fp) {
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
		
		return false;
	}
	# ----------------------------------------------------------
	private function isWord2008doc($ps_sig) {
		// ehh... this'll also consider any PK zip file or text field beginning
		// with "PK" as a Word 2008 file... there has got to be a better way.
		if (
			substr($ps_sig, 0, 2) == 'PK'
		) {
			return true;
		}
		
		return false;
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				//print "Invalid property";
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
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugMediaMSWord->set()");
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
		return array();
	}
	# ------------------------------------------------
	public function read ($ps_filepath) {
		if (is_array($this->handle) && ($this->handle["filepath"] == $ps_filepath)) {
			# noop
		} else {
			if (!file_exists($ps_filepath)) {
				$this->postError(1650, _t("File %1 does not exist", $ps_filepath), "WLPlugMediaMSWord->read()");
				$this->handle = "";
				$this->filepath = "";
				return false;
			}
			if (!($this->divineFileFormat($ps_filepath))) {
				$this->postError(1650, _t("File %1 is not a Microsoft Word document", $ps_filepath), "WLPlugMediaMSWord->read()");
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
		
		//try to extract text
		if ($this->opb_abiword_installed && !$this->opb_libre_office_installed) {
			$vs_tmp_filename = tempnam('/tmp', 'CA_MSWORD_TEXT');
			exec($this->ops_abiword_path.' -t txt '.$ps_filepath.' -o '.$vs_tmp_filename);
			$vs_extracted_text = preg_replace('![^\w\d]+!u' , ' ', file_get_contents($vs_tmp_filename));	// ABIWord seems to dump Unicode...
			$this->handle['content'] = $this->ohandle['content'] = $vs_extracted_text;
			@unlink($vs_tmp_filename);
		}
			
		return true;	
	}
	# ----------------------------------------------------------
	public function transform($ps_operation, $pa_parameters) {
		if (!$this->handle) { return false; }
		$this->opa_transformations[] = array('op' => $ps_operation, 'params' => $pa_parameters);
		
		if (!($this->info["TRANSFORMATIONS"][$ps_operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $ps_operation), "WLPlugMSWord->transform()");
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
	public function write($ps_filepath, $ps_mimetype) {
		if (!$this->handle) { return false; }
		
		# is mimetype valid?
		if (!($vs_ext = $this->info["EXPORT"][$ps_mimetype])) {
			$this->postError(1610, _t("Can't convert file to %1", $ps_mimetype), "WLPlugMediaMSWord->write()");
			return false;
		} 
		
		# write the file
		if ($ps_mimetype == "application/msword") {
			if ( !copy($this->filepath, $ps_filepath.".doc") ) {
				$this->postError(1610, _t("Couldn't write file to '%1'", $ps_filepath), "WLPlugMediaMSWord->write()");
				return false;
			}
		} else {
			if (!isset(WLPlugMediaMSWord::$s_pdf_conv_cache[$this->filepath]) && $this->opb_libre_office_installed) {
				$vs_tmp_dir_path = caGetTempDirPath();
				$va_tmp = explode("/", $this->filepath);
				$vs_out_file = array_pop($va_tmp);
				
				putenv("HOME={$vs_tmp_dir_path}");		// libreoffice will fail silently if you don't set this environment variable to a directory it can write to. Nice way to waste a day debugging. Yay!
				exec($this->ops_libreoffice_path." --nologo --nofirststartwizard --headless -convert-to pdf \"".$this->filepath."\"  -outdir {$vs_tmp_dir_path} 2>&1", $va_output, $vn_return);
				exec($this->ops_libreoffice_path." --nologo --nofirststartwizard --headless -convert-to html \"".$this->filepath."\"  -outdir {$vs_tmp_dir_path} 2>&1", $va_output, $vn_return);
			
				$va_out_file = explode(".", $vs_out_file);
				if (sizeof($va_out_file) > 1) { array_pop($va_out_file); }
				$this->handle['content'] = strip_tags(file_get_contents("{$vs_tmp_dir_path}/".join(".", $va_out_file).".html"));
				$va_out_file[] = 'pdf';
				
				WLPlugMediaMSWord::$s_pdf_conv_cache[$this->filepath] = "{$vs_tmp_dir_path}/".join(".", $va_out_file);
				$o_media = new Media();
				if ($o_media->read(WLPlugMediaMSWord::$s_pdf_conv_cache[$this->filepath])) {
					$this->set('width', $this->ohandle["width"] = $o_media->get('width'));
					$this->set('height', $this->ohandle["height"] = $o_media->get('height'));
					$this->set('resolution', $this->ohandle["resolution"] = $o_media->get('resolution'));
				}
			}
			
			if ($vs_media = WLPlugMediaMSWord::$s_pdf_conv_cache[$this->filepath]) {
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
				if (file_exists($this->opo_config->get("default_media_icons"))) {
					$o_icon_info = Configuration::load($this->opo_config->get("default_media_icons"));
					if ($va_icon_info = $o_icon_info->getAssoc('application/msword')) {
						$vs_icon_path = $o_icon_info->get("icon_folder_path");
						if (!copy($vs_icon_path."/".trim($va_icon_info[$this->get("version")]),$ps_filepath.'.'.$vs_ext)) {
							$this->postError(1610, _t("Can't copy icon file from %1 to %2", $vs_icon_path."/".trim($va_icon_info[$this->get("version")]), $ps_filepath.'.'.$vs_ext), "WLPlugMediaMSWord->write()");
							return false;
						}
					} else {
						$this->postError(1610, _t("No icon available for this media type (system misconfiguration)"), "WLPlugMediaMSWord->write()");
						return false;
					}
				} else {
					$this->postError(1610, _t("No icons available (system misconfiguration)"), "WLPlugMediaMSWord->write()");
					return false;
				}
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
		if ($vs_pdf_path = WLPlugMediaMSWord::$s_pdf_conv_cache[$this->filepath]) {
			$o_media = new Media();
			if ($o_media->read($vs_pdf_path)) {
				return $o_media->writePreviews($pa_options);	
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
				
				$vs_buf = "<script type='text/javascript'>jQuery(document).ready(function() {
	new PDFObject({
		url: '{$ps_url}',
		id: '{$vs_id}',
		width: '{$vn_viewer_width}px',
		height: '{$vn_viewer_height}px',
	}).embed('{$vs_id}_div');
});</script>
	<div id='{$vs_id}_div'><a href='$ps_url' target='_pdf'>".$vs_poster_frame."</a></div>
";
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

function WLPlugMSWordShutdown() {
	// Cleanup tmp files
	foreach(WLPlugMediaMSWord::$s_pdf_conv_cache as $vs_filepath => $vs_pdf_path) {
		if(file_exists($vs_pdf_path)) {
			@unlink($vs_pdf_path);
			@unlink(preg_replace("!\.pdf$!", ".html", $vs_pdf_path));
		}
	}
}

register_shutdown_function("WLPlugMSWordShutdown");
?>