<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/GD.php :
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
 
/** 
 * Plugin for processing images using GD
 */

include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_LIB_DIR__."/core/Parsers/MediaMetadata/XMPParser.php");

class WLPlugMediaGD Extends BaseMediaPlugin Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	var $metadata = array();
	
	var $opo_config;
	var $opo_external_app_config;
	var $ops_imagemagick_path;
	var $ops_graphicsmagick_path;
	
	var $info = array(
		"IMPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/png" 		=> "png",
			"image/tilepic" 	=> "tpc"
		),
		"EXPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/png" 		=> "png",
			"image/tilepic" 	=> "tpc"
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing", "trim_edges", "crop_from"),					// trim_edges and crop_from are dummy and not supported by GD; they are supported by the ImageMagick-based plugins
			"ANNOTATE"	=> array("text", "font", "size", "color", "position", "inset"),	// dummy
			"WATERMARK"	=> array("image", "width", "height", "position", "opacity"),	// dummy
			"ROTATE" 			=> array("angle"),
			"SET" 				=> array("property", "value"),
			
			# --- filters
			"MEDIAN"			=> array("radius"),
			"DESPECKLE"			=> array(""),
			"SHARPEN"			=> array("radius", "sigma"),
			"UNSHARPEN_MASK"	=> array("radius", "sigma", "amount", "threshold"),
		),
		"PROPERTIES" => array(
			"width" 			=> 'W',
			"height" 			=> 'W',
			"mimetype" 			=> 'R',
			"typename" 			=> 'R',
			'tiles'				=> 'R',
			'layers'			=> 'W',
			"quality" 			=> 'W',
			'tile_width'		=> 'W',
			'tile_height'		=> 'W',
			'antialiasing'		=> 'W',
			'layer_ratio'		=> 'W',
			'tile_mimetype'		=> 'W',
			'output_layer'		=> 'W',
			'no_upsampling'		=> 'W',
			'version'			=> 'W'	// required of all plug-ins
		),
		"NAME" => "GD"
	);
	
	var $typenames = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/png" 		=> "PNG",
		"image/tilepic" 	=> "Tilepic"
	);
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides limited image processing and conversion services using libGD');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_imagemagick_path = $this->opo_external_app_config->get('imagemagick_path');
		$this->ops_graphicsmagick_path = $this->opo_external_app_config->get('graphicsmagick_app');
		$this->ops_CoreImage_path = $this->opo_external_app_config->get('coreimagetool_app');
		
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			return null;	// don't use if CoreImage executable are available
		}
		if (caMediaPluginImagickInstalled()) {	
			return null;	// don't use GD if Imagick is available
		} 
		if (caMediaPluginGmagickInstalled()) {	
			return null;	// don't use GD if Gmagick is available
		} 
		if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
			return null;	// don't use if ImageMagick executables are available
		}
		if (caMediaPluginGraphicsMagickInstalled($this->ops_graphicsmagick_path)){
			return null;	// don't use if GraphicsMagick is available
		}
		if (!caMediaPluginGDInstalled()) {
			return null;	// don't use if GD functions are not available
		}

		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		} else {
			if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because CoreImageTool is available and preferred");
			} 
			if (caMediaPluginImagickInstalled()) {	
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because Imagick/ImageMagick is available and preferred");
			} 
			if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because ImageMagick (command-line) is available and preferred");
			}
			if (caMediaPluginGmagickInstalled()) {	
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because Gmagick is available and preferred");
			}
			if (caMediaPluginGraphicsMagickInstalled($this->ops_graphicsmagick_path)) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because GraphicsMagick is available and preferred");
			}
			if (!caMediaPluginGDInstalled()) {
				$va_status['errors'][] = _t("Didn't load because your PHP install lacks GD support");
			}
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		if((!$filepath) || (!file_exists($filepath))) { return ''; }
		if($va_info = @getimagesize($filepath)) {
			switch($va_info[2]) {
				case IMAGETYPE_GIF:
					return "image/gif";
					break;
				case IMAGETYPE_JPEG:
					return "image/jpeg";
					break;
				case IMAGETYPE_PNG:
					return "image/png";
					break;
			}
			return '';
		} else {
			$tp = new TilepicParser();
			$tp->useLibrary(LIBRARY_GD);
			if ($tp->isTilepic($filepath)) {
				return "image/tilepic";
			} else {
				# file format is not supported by this plug-in
				return '';
			}
		}
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
			if ($property == "tile_size") {
				if (($value < 10) || ($value > 10000)) {
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugGD->set()");
					return '';
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugGD->set()");
								return '';
							}
							$this->properties["tile_mimetype"] = $value;
							break;
						case 'output_layer':
							$this->properties["output_layer"] = $value;
							break;
						case 'width':
							$this->properties["width"] = $value;
							break;
						case 'height':
							$this->properties["height"] = $value;
							break;
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
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugGD->set()");
					return '';
				}
			}
		} else {
			return '';
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		return $this->metadata;
	}
	# ----------------------------------------------------------
	public function read($filepath, $mimetype="") {
		if ($mimetype == 'image/tilepic') {
			#
			# Read in Tilepic format image
			#
			$this->handle = new TilepicParser($filepath);
			$tp->useLibrary(LIBRARY_GD);
			if (!$this->handle->error) {
				$this->filepath = $filepath;
				foreach($this->handle->properties as $k => $v) {
					if (isset($this->properties[$k])) {
						$this->properties[$k] = $v;
					}
				}
				$this->properties["mimetype"] = "image/tilepic";
				$this->properties["typename"] = "Tilepic";
				return true;
			} else {
				postError(1610, $this->handle->error, "WLPlugGD->read()");
				return false;
			}
		} else {
			$this->handle = "";
			$this->filepath = "";
			$this->metadata = array();
			
			$va_info = @getimagesize($filepath);
			switch($va_info[2]) {
				case IMAGETYPE_GIF:
					$this->handle = imagecreatefromgif($filepath);
					$vs_mimetype = "image/gif";
					$vs_typename = "GIF";
					break;
				case IMAGETYPE_JPEG:
					if(function_exists('exif_read_data')) {
						$this->metadata["EXIF"] = $va_exif = caSanitizeArray(@exif_read_data($filepath, 'EXIF', true, false));
						
						
						//
						// Rotate incoming image as needed
						//
						if (is_array($va_exif)) { 
							if (isset($va_exif['IFD0']['Orientation'])) {
								$vn_orientation = $va_exif['IFD0']['Orientation'];
								switch($vn_orientation) {
									case 3:
										$this->handle = imagecreatefromjpeg($filepath);
										$this->handle = $this->rotateImage($this->handle, -180);
										break;
									case 6:
										$this->handle = imagecreatefromjpeg($filepath);
										$this->handle = $this->rotateImage($this->handle, -90);
										$va_tmp = $va_info;
										$va_info[0] = $va_tmp[1];
										$va_info[1] = $va_tmp[0];
										break;
									case 8:
										$this->handle = imagecreatefromjpeg($filepath);
										$this->handle = $this->rotateImage($this->handle, 90);
										$va_tmp = $va_info;
										$va_info[0] = $va_tmp[1];
										$va_info[1] = $va_tmp[0];
										break;
								}
							}
						}
					}
					
					if (!$this->handle) {
						$this->handle = imagecreatefromjpeg($filepath);
					}
					$vs_mimetype = "image/jpeg";
					$vs_typename = "JPEG";
					
					
					$o_xmp = new XMPParser();
					if ($o_xmp->parse($ps_filepath)) {
						if (is_array($va_xmp_metadata = $o_xmp->getMetadata()) && sizeof($va_xmp_metadata)) {
							$va_metadata['XMP'] = $va_xmp_metadata;
						}
					}
					break;
				case IMAGETYPE_PNG:
					$this->handle = imagecreatefrompng($filepath);
					$vs_mimetype = "image/png";
					$vs_typename = "PNG";
					break;
				default:
					return false;
					break;
			}
			
			if ($this->handle) {
				$this->filepath = $filepath;
				
				# load image properties
				$this->properties["width"] = $va_info[0];
				$this->properties["height"] = $va_info[1];
				$this->properties["quality"] = "";
				$this->properties["mimetype"] = $vs_mimetype;
				$this->properties["typename"] = $vs_typename;
				$this->properties["filesize"] = @filesize($filepath);
				
				return true;
			} else {
				# plug-in can't handle format
				return false;
			}
		}
	}
	# ----------------------------------------------------------
	function rotateImage($img, $rotation) {
		$width = imagesx($img);
		$height = imagesy($img);
		
		if ($rotation < 0) { $rotation += 360; }
		switch($rotation) {
			case 90: 
				$newimg= @imagecreatetruecolor($height , $width );
				break;
			case 180: 
				$newimg= @imagecreatetruecolor($width , $height );
				break;
			case 270: 
			case -90:
				$newimg= @imagecreatetruecolor($height , $width );
				break;
			case 0: 
			case 360:
				return $img;
				break;
		}
		
		if($newimg) { 
			for($i = 0;$i < $width ; $i++) { 
				for($j = 0;$j < $height ; $j++) {
					$reference = imagecolorat($img,$i,$j);
					switch($rotation) {
						case 270: 
						case -90:
							if(!@imagesetpixel($newimg, ($height - 1) - $j, $i, $reference )){
								return false;
							}
							$this->set('width', $height);
							$this->set('height', $width);
							break;
						case 180: 
							if(!@imagesetpixel($newimg, $width - $i, ($height - 1) - $j, $reference )){
								return false;
							}
							break;
						case 90: 
							if(!@imagesetpixel($newimg, $j, $width - $i, $reference )){
								return false;
							}
							$this->set('width', $height);
							$this->set('height', $width);
							break;
					}
				}
			} 
			return $newimg; 
		} 
		return false;
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if ($this->properties["mimetype"] == "image/tilepic") { return false;} # no transformations for Tilepic
		if (!$this->handle) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			postError(1655, _t("Invalid transformation %1", $operation), "WLPlugGD->transform()");
			return false;
		}
		
		# get parameters for this operation
		$sparams = $this->info["TRANSFORMATIONS"][$operation];
		
		$w = $parameters["width"];
		$h = $parameters["height"];
		$cw = $this->get("width");
		$ch = $this->get("height");
		
		if((bool)$this->properties['no_upsampling']) {
			$w = min($cw, round($w)); 
			$h = min($ch, round($h));
		}
		
		$do_crop = 0;
		switch($operation) {
			# -----------------------
			case 'SCALE':
				$aa = $parameters["antialiasing"];
				if ($aa <= 0) { $aa = 0; }
				switch($parameters["mode"]) {
					# ----------------
					case "width":
						$scale_factor = $w/$cw;
						$h = $ch * $scale_factor;
						break;
					# ----------------
					case "height":
						$scale_factor = $h/$ch;
						$w = $cw * $scale_factor;
						break;
					# ----------------
					case "bounding_box":
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h); 
						$h = $ch * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h);	
						break;
					# ----------------
					case "fill_box":
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h); 
						$h = $ch * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h);	
						
						$do_crop = 1;
						break;
					# ----------------
				}
		
				$w = round($w);
				$h = round($h);
				if ($w > 0 && $h > 0) {
					$r_new_img = imagecreatetruecolor($w, $h);
					if (!imagecopyresampled($r_new_img, $this->handle, 0, 0, 0, 0, $w, $h, $cw, $ch)) {
						$this->postError(1610, _t("Couldn't resize image"), "WLPlugGD->transform()");
						return false;
					}
					imagedestroy($this->handle);
					$this->handle = $r_new_img;
					if ($do_crop) {
						$r_new_img = imagecreatetruecolor($parameters["width"], $parameters["height"]);
						imagecopy($r_new_img, $this->handle,0,0,0,0,$parameters["width"], $parameters["height"]);
						imagedestroy($this->handle);
						$this->handle = $r_new_img;
						$this->properties["width"] = $parameters["width"];
						$this->properties["height"] = $parameters["height"];
					} else {
						$this->properties["width"] = $w;
						$this->properties["height"] = $h;
					}
			}
			break;
		# -----------------------
		case "ROTATE":
			$angle = $parameters["angle"];
			if (($angle > -360) && ($angle < 360)) {
				if ( !($r_new_img = imagerotate($this->handle, $angle, 0 )) ){
					postError(1610, _t("Couldn't rotate image"), "WLPlugGD->transform()");
					return false;
				}
				imagedestroy($this->handle);
				$this->handle = $r_new_img;
			}
			break;
		# -----------------------
		case "DESPECKLE":
			# noop
			break;
		# -----------------------
		case "MEDIAN":
			# noop
			break;
		# -----------------------
		case "SHARPEN":
			# noop
			break;
		# -----------------------
		case "UNSHARP_MASK":
			# noop
			break;
		# -----------------------
		case "SET":
			while(list($k, $v) = each($parameters)) {
				$this->set($k, $v);
			}
			break;
		# -----------------------
		}
		return true;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype) {
		if (!$this->handle) { return false; }
		
		if ($mimetype == "image/tilepic") {
			if ($this->properties["mimetype"] == "image/tilepic") {
				copy($this->filepath, $filepath);
			} else {
				$tp = new TilepicParser();
				$tp->useLibrary(LIBRARY_GD);
				if (!($properties = $tp->encode($this->filepath, $filepath, 
					array(
						"tile_width" => $this->properties["tile_width"],
						"tile_height" => $this->properties["tile_height"],
						"layer_ratio" => $this->properties["layer_ratio"],
						"quality" => $this->properties["quality"],
						"antialiasing" => $this->properties["antialiasing"],
						"output_mimetype" => $this->properties["tile_mimetype"],
						"layers" => $this->properties["layers"],
					)					
				))) {
					$this->postError(1610, $tp->error, "WLPlugTilepic->write()");	
					return false;
				}
			}
			# update mimetype
			foreach($properties as $k => $v) {
				$this->properties[$k] = $v;
			}
			$this->properties["mimetype"] = "image/tilepic";
			$this->properties["typename"] = "Tilepic";
			return $filepath;
		} else {
			# is mimetype valid?
			if (!($ext = $this->info["EXPORT"][$mimetype])) {
				# this plugin can't write this mimetype
				return false;
			} 
			
			# get layer out of Tilepic
			if ($this->properties["mimetype"] == "image/tilepic") {
				if (!($h = $this->handle->getLayer($this->properties["output_layer"] ? $this->properties["output_layer"] : intval($this->properties["layers"]/2.0), $mimetype))) {
					$this->postError(1610, $this->handle->error, "WLPlugTilepic->write()");	
					return false;
				}
				$this->handle = $h;
			}
			
			$vn_res = 0;
			switch($mimetype) {
				case 'image/gif':
					$vn_res = imagegif($this->handle, $filepath.".".$ext);
					$vs_typename = "GIF";
					break;
				case 'image/jpeg':
					$vn_res = imagejpeg($this->handle, $filepath.".".$ext, $this->properties["quality"] ? $this->properties["quality"] : null);
					$vs_typename = "JPEG";
					break;
				case 'image/png':
					$vn_res = imagepng($this->handle, $filepath.".".$ext);
					$vs_typename = "PNG";
					break;
			}
			
			# write the file
			if (!$vn_res) {
				# error
				$this->postError(1610, _t("Couldn't write image"), "WLPlugGD->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = $vs_typename;
			
			return $filepath.".".$ext;
		}
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
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
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	public function reset() {
		$this->read($this->filepath);
		return true;
	}
	# ------------------------------------------------
	public function init() {
		unset($this->handle);
		unset($this->properties);
		unset($this->filepath);
		
		$this->metadata = array();
		$this->errors = array();
	}
	# ------------------------------------------------
	public function cleanup() {
		$this->destruct();
	}
	# ------------------------------------------------
	public function destruct() {
		if (is_resource($this->handle)) { imagedestroy($this->handle); };
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!is_array($pa_properties)) { $pa_properties = array(); }
		return caHTMLImage($ps_url, array_merge($pa_options, $pa_properties));
	}	
	# ------------------------------------------------
}
?>
