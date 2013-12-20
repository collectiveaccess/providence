<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/CoreImage.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 * Plugin for processing images using CoreImage (Mac OS X only)
 */

include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_LIB_DIR__."/core/Parsers/MediaMetadata/XMPParser.php");

class WLPlugMediaCoreImage Extends BaseMediaPlugin Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $filepath_conv;		// filepath for converted input file; 
	var $handle;
	var $ohandle;
	var $properties;
	var $metadata = array();
	
	var $opo_config;
	var $opo_external_app_config;
	var $ops_CoreImage_path;
	
	var $info = array(
		"IMPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/jp2"			=> "jp2",
			"image/x-adobe-dng"	=> "dng"
		),
		"EXPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/tilepic" 	=> "tpc",
			"image/jp2"			=> "jp2",
			"image/x-adobe-dng"	=> "dng"
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing", "trim_edges", "crop_from"),
			"ANNOTATE"			=> array("text", "font", "size", "color", "position", "inset"),
			"WATERMARK"			=> array("image", "width", "height", "position", "opacity"),
			"ROTATE" 			=> array("angle"),
			"SET" 				=> array("property", "value"),
			
			# --- filters
			"MEDIAN"			=> array("radius"),
			"DESPECKLE"			=> array(""),
			"SHARPEN"			=> array("radius", "sigma"),
			"UNSHARPEN_MASK"	=> array("radius", "sigma", "amount", "threshold"),
		),
		"PROPERTIES" => array(
			"width" 			=> 'R',
			"height" 			=> 'R',
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
			'gamma'				=> 'W',
			'reference-black'	=> 'W',
			'reference-white'	=> 'W',
			'no_upsampling'		=> 'W',
			'faces'				=> 'W',
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "CoreImage"
	);
	
	var $typenames = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "Windows Bitmap (BMP)",
		"image/x-psd" 		=> "Photoshop",
		"image/tilepic" 	=> "Tilepic",
		"image/jp2"			=> "JPEG-2000",
		"image/x-adobe-dng"	=> "Adobe DNG"
	);
	
	var $apple_type_names = array(
		"image/jpeg" 		=> "jpeg",
		"image/gif" 		=> "gif",
		"image/tiff" 		=> "tiff",
		"image/png" 		=> "png",
		"image/x-bmp" 		=> "bmp",
		"image/x-psd" 		=> "psd",
		"image/tilepic" 	=> "tpc",
		"image/jp2"			=> "jp2"
	);
	
	var $apple_UTIs = array(
		"image/jpeg" 		=> "public.jpeg",
		"image/gif" 		=> "com.compuserve.gif",
		"image/tiff" 		=> "public.tiff",
		"image/png" 		=> "public.png",
		"image/x-bmp" 		=> "com.microsoft.bmp",
		"image/x-psd" 		=> "com.adobe.photoshop.image",
		"image/tilepic" 	=> "public.tpc",
		"image/jp2"			=> "public.jpeg-2000"
	);

	
	# ------------------------------------------------
	public function __construct() {
		$this->opo_config = Configuration::load();
		$this->description = _t('Provides CoreImage-based image processing and conversion services via the command-line CoreImageTool (Mac OS X 10.4+ only). This can provide a significant performance boost when using Macintosh servers.');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		// get config for external apps
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_CoreImage_path = $this->opo_external_app_config->get('coreimagetool_app');
		
		
		if (!caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			return null;	// don't use if CoreImage executables are unavailable
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
			if (!caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
				$va_status['errors'][] = _t("Didn't load because CoreImageTool executable cannot be found");
			}
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		$vs_mimetype = $this->_CoreImageIdentify($filepath);
		return ($vs_mimetype) ? $vs_mimetype : '';
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
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugCoreImage->set()");
					return '';
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["tile_mimetype"] = $value;
							break;
						case 'output_layer':
							$this->properties["output_layer"] = $value;
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
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugCoreImage->set()");
					return '';
				}
			}
		} else {
			return '';
		}
		return 1;
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
		if (!(($this->handle) && ($filepath === $this->filepath))) {
			
			if(strpos($filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
				$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugCoreImage->read()");
				return false;
			}
			if ($mimetype == 'image/tilepic') {
				#
				# Read in Tilepic format image
				#
				$this->handle = new TilepicParser($filepath);
				if (!$this->handle->error) {
					$this->filepath = $filepath;
					foreach($this->handle->properties as $k => $v) {
						if (isset($this->properties[$k])) {
							$this->properties[$k] = $v;
						}
					}
					$this->properties["mimetype"] = "image/tilepic";
					$this->properties["typename"] = "Tilepic";
					
					if ($this->filepath_conv) { @unlink($this->filepath_conv); }
					return 1;
				} else {
					$this->postError(1610, $this->handle->error, "WLPlugCoreImage->read()");
					return false;
				}
			} else {
				$this->handle = "";
				$this->filepath = "";
				
				
				
				$handle = $this->_CoreImageRead(($this->filepath_conv) ? $this->filepath_conv : $filepath);
				if ($handle) {
					$this->handle = $handle;
					$this->filepath = $filepath;
					$this->metadata = $this->_CoreImageGetMetadata(($this->filepath_conv) ? $this->filepath_conv : $filepath);
					
					# load image properties
					$this->properties["width"] = $this->handle['width'];
					$this->properties["height"] = $this->handle['height'];
					$this->properties["quality"] = "";
					$this->properties["mimetype"] = $this->handle['mimetype'];
					$this->properties["typename"] = $this->handle['magick'];
					$this->properties["filesize"] = filesize($filepath);
					$this->properties["bitdepth"] = $this->handle['depth'];
					$this->properties["resolution"] = $this->handle['resolution'];
					$this->properties["colorspace"] = $this->handle['colorspace'];
					$this->properties["orientation_rotate"] = $this->handle['orientation_rotate'];
					$this->properties["faces"] = $this->handle['faces'];
					
					$this->ohandle = $this->handle;
					
					return 1;
				} else {
					# plug-in can't handle format
					return false;
				}
			}
		} else {
			# image already loaded by previous call (probably divineFileFormat())
			return 1;
		}
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if ($this->properties["mimetype"] == "image/tilepic") { return false;} # no transformations for Tilepic
		if (!$this->handle) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugCoreImage->transform()");
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
			case 'ANNOTATE':
				switch($parameters['position']) {
					case 'north_east':
						$position = 'NorthEast';
						break;
					case 'north_west':
						$position = 'NorthWest';
						break;
					case 'north':
						$position = 'North';
						break;
					case 'south_east':
						$position = 'SouthEast';
						break;
					case 'south':
						$position = 'South';
						break;
					case 'center':
						$position = 'Center';
						break;
					case 'south_west':
					default:
						$position = 'SouthWest';
						break;
				}
				
				$this->handle['ops'][] = array(
					'op' => 'annotation',
					'text' => $parameters['text'],
					'inset' => ($parameters['inset'] > 0) ? $parameters['inset']: 0,
					'font' => $parameters['font'],
					'size' => ($parameters['size'] > 0) ? $parameters['size']: 18,
					'color' => $parameters['color'] ? $parameters['color'] : "black",
					'position' => $position
				);
				break;
			# -----------------------
			case 'WATERMARK':
				if (!file_exists($parameters['image'])) { break; }
				$vn_opacity_setting = $parameters['opacity'];
				if (($vn_opacity_setting < 0) || ($vn_opacity_setting > 1)) {
					$vn_opacity_setting = 0.5;
				}
				
				if (($vn_watermark_width = $parameters['width']) < 10) { 
					$vn_watermark_width = $cw/2;
				}
				if (($vn_watermark_height = $parameters['height']) < 10) {
					$vn_watermark_height = $ch/2;
				}
				
				switch($parameters['position']) {
					case 'north_east':
						$vn_watermark_x = $cw - $vn_watermark_width;
						$vn_watermark_y = 0;
						break;
					case 'north_west':
						$vn_watermark_x = 0;
						$vn_watermark_y = 0;
						break;
					case 'north':
						$vn_watermark_x = ($cw - $vn_watermark_width)/2;
						$vn_watermark_y = 0;
						break;
					case 'south_east':
						$vn_watermark_x = $cw - $vn_watermark_width;
						$vn_watermark_y = $ch - $vn_watermark_height;
						break;
					case 'south':
						$vn_watermark_x = ($cw - $vn_watermark_width)/2;
						$vn_watermark_y = $ch - $vn_watermark_height;
						break;
					case 'center':
						$vn_watermark_x = ($cw - $vn_watermark_width)/2;
						$vn_watermark_y = ($ch - $vn_watermark_height)/2;
						break;
					case 'south_west':
					default:
						$vn_watermark_x = $cw - $vn_watermark_width;
						$vn_watermark_y = $ch - $vn_watermark_height;
						break;
				}
				
				$this->handle['ops'][] = array(
					'op' => 'watermark',
					'opacity' => $vn_opacity_setting,
					'watermark_width' => $vn_watermark_width,
					'watermark_height' => $vn_watermark_height,
					'position' => $parameters['position'],
					'position_x' => $vn_watermark_x,
					'position_y' => $vn_watermark_y,
					'watermark_image' => $parameters['image']
				);
				break;
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
						$crop_from = $parameters["crop_from"];
						if (!in_array($crop_from, array('center', 'north_east', 'north_west', 'south_east', 'south_west', 'random'))) {
							$crop_from = '';
						}
						
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h); 
						$h = $ch * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h);	
						
						$do_fill_box_crop = true;
						break;
					# ----------------
				}
				
				$w = round($w);
				$h = round($h);
				if ($w > 0 && $h > 0) {
					$crop_w_edge = $crop_h_edge = 0;
					if (preg_match("/^([\d]+)%$/", $parameters["trim_edges"], $va_matches)) {
						$crop_w_edge = ceil((intval($va_matches[1])/100) * $w);
						$crop_h_edge = ceil((intval($va_matches[1])/100) * $h);
					} else {
						if (isset($parameters["trim_edges"]) && (intval($parameters["trim_edges"]) > 0)) {
							$crop_w_edge = $crop_h_edge = intval($parameters["trim_edges"]);
						}
					}
					$this->handle['ops'][] = array(
						'op' => 'size',
						'width' => $w + ($crop_w_edge * 2),
						'height' => $h + ($crop_h_edge * 2),
						'antialiasing' => $aa
					);
					
					if ($do_fill_box_crop) {
						// use face detection info to intelligently crop
						if(is_array($this->properties['faces']) && sizeof($this->properties['faces'])) {
							$va_info = array_shift($this->properties['faces']);
							$crop_from_offset_x = ceil($va_info['x'] * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h));
							$crop_from_offset_x -= ceil(0.15 * $parameters["width"]);	// since face will be tightly cropped give it some room
							$crop_from_offset_y = ceil($va_info['y'] * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h));
							$crop_from_offset_y -= ceil(0.15 * $parameters["height"]);	// since face will be tightly cropped give it some room
							
							// Don't try to crop beyond image boundaries, you just end up scaling the image, often awkwardly
							if ($crop_from_offset_x > ($w - $parameters["width"])) { $crop_from_offset_x = 0; }
							if ($crop_from_offset_y > ($h - $parameters["height"])) { $crop_from_offset_y = 0; }
						} else {
							switch($crop_from) {
								case 'north_west':
									$crop_from_offset_y = 0;
									$crop_from_offset_x = $w - $parameters["width"];
									break;
								case 'south_east':
									$crop_from_offset_x = 0;
									$crop_from_offset_y = $h - $parameters["height"];
									break;
								case 'south_west':
									$crop_from_offset_x = $w - $parameters["width"];
									$crop_from_offset_y = $h - $parameters["height"];
									break;
								case 'random':
									$crop_from_offset_x = rand(0, $w - $parameters["width"]);
									$crop_from_offset_y = rand(0, $h - $parameters["height"]);
									break;
								case 'north_east':
									$crop_from_offset_x = $crop_from_offset_y = 0;
									break;
								case 'center':
								default:
									if ($w > $parameters["width"]) {
										$crop_from_offset_x = ceil(($w - $parameters["width"])/2);
									} else {
										if ($h > $parameters["height"]) {
											$crop_from_offset_y = ceil(($h - $parameters["height"])/2);
										}
									}
									break;
							}
						}
						$this->handle['ops'][] = array(
							'op' => 'crop',
							'width' => $parameters["width"],
							'height' => $parameters["height"],
							'x' => $crop_w_edge + $crop_from_offset_x,
							'y' => $crop_h_edge + $crop_from_offset_y
						);
						
						$this->properties["width"] = $parameters["width"];
						$this->properties["height"] = $parameters["height"];
					} else {
						if ($crop_w_edge || $crop_h_edge) {
							$this->handle['ops'][] = array(
								'op' => 'crop',
								'width' => $w,
								'height' => $h,
								'x' => $crop_w_edge,
								'y' => $crop_h_edge
							);
						}
						$this->properties["width"] = $w;
						$this->properties["height"] = $h;
					}
				}
			break;
		# -----------------------
		case "ROTATE":
			$angle = $parameters["angle"];
			if (($angle > -360) && ($angle < 360)) {
				$this->handle['ops'][] = array(
					'op' => 'rotate',
					'angle' => $angle
				);
			}
			break;
		# -----------------------
		case "DESPECKLE":
			$this->handle['ops'][] = array(
				'op' => 'filter_despeckle'
			);
			break;
		# -----------------------
		case "MEDIAN":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			$this->handle['ops'][] = array(
				'op' => 'filter_median',
				'radius' => $radius
			);
			break;
		# -----------------------
		case "SHARPEN":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			$sigma = $parameters["sigma"];
			if ($sigma < .1) { $sigma = 1; }
			$this->handle['ops'][] = array(
				'op' => 'filter_sharpen',
				'radius' => $radius,
				'sigma' => $sigma
			);
			break;
		# -----------------------
		case "UNSHARPEN_MASK":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			$sigma = $parameters["sigma"];
			if ($sigma < .1) { $sigma = 1; }
			$threshold = $parameters["threshold"];
			if ($threshold < .1) { $threshold = 1; }
			$amount = $parameters["amount"];
			if ($amount < .1) { $amount = 1; }
			$this->handle['ops'][] = array(
				'op' => 'filter_unsharp_mask',
				'radius' => $radius,
				'sigma' => $sigma,
				'amount' => $amount,
				'threshold' => $threshold
			);
			break;
		# -----------------------
		case "SET":
			while(list($k, $v) = each($parameters)) {
				$this->set($k, $v);
			}
			break;
		# -----------------------
		}
		return 1;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype) {
		if (!$this->handle) { return false; }
		if(strpos($filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugCoreImage->write()");
			return false;
		}
		if ($mimetype == "image/tilepic") {
			if ($this->properties["mimetype"] == "image/tilepic") {
				copy($this->filepath, $filepath);
			} else {
				$tp = new TilepicParser();
				if (!($properties = $tp->encode(($this->filepath_conv) ? $this->filepath_conv : $this->filepath, $filepath, 
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
			return 1;
		} else {
			# is mimetype valid?
			if (!($ext = $this->info["EXPORT"][$mimetype])) {
				# this plugin can't write this mimetype
				return false;
			} 
					
			if (!$this->_CoreImageWrite($this->handle, $filepath.".".$ext, $mimetype, $this->properties["quality"])) {
				$this->postError(1610, _t("%1: %2", $reason, $description), "WLPlugCoreImage->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = $this->typenames[$mimetype];
			
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
	public function appleTypeToMimeType($ps_apple_type) {
		foreach($this->apple_type_names as $vs_mimetype => $vs_apple_type) {
			if ($ps_apple_type == $vs_apple_type) {
				return $vs_mimetype;
			}
		}
		return null;
	}
	# ------------------------------------------------
	public function reset() {
		if ($this->ohandle) {
			$this->handle = $this->ohandle;
			# load image properties
			$this->properties["width"] = $this->handle['width'];
			$this->properties["height"] = $this->handle['height'];
			$this->properties["quality"] = "";
			$this->properties["mimetype"] = $this->handle['mimetype'];
			$this->properties["typename"] = $this->handle['magick'];
			$this->properties["faces"] = $this->handle['faces'];
			return true;
		}
		return false;
	}
	# ------------------------------------------------
	public function init() {
		unset($this->handle);
		unset($this->ohandle);
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
		if ($this->filepath_conv) { @unlink($this->filepath_conv); }
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!is_array($pa_properties)) { $pa_properties = array(); }
		return caHTMLImage($ps_url, array_merge($pa_options, $pa_properties));
	}	
	# ------------------------------------------------
	# Command line wrappers
	# ------------------------------------------------
	private function _CoreImageIdentify($ps_filepath) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$va_info = explode(':', shell_exec("sips --getProperty format ".caEscapeShellArg($ps_filepath)));
			return $this->appleTypeToMimeType(trim(array_pop($va_info)));
		}
		return null;
	}
	# ------------------------------------------------
	private function _CoreImageGetMetadata($ps_filepath) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$va_metadata = array();
			
			if(function_exists('exif_read_data')) {
				if (is_array($va_exif = caSanitizeArray(@exif_read_data($ps_filepath, 'EXIF', true, false)))) { $va_metadata['EXIF'] = $va_exif; }
			}
			
			$o_xmp = new XMPParser();
			if ($o_xmp->parse($ps_filepath)) {
				if (is_array($va_xmp_metadata = $o_xmp->getMetadata()) && sizeof($va_xmp_metadata)) {
					$va_metadata['XMP'] = $va_xmp_metadata;
				}
			}
			
			return $va_metadata;
		}
		return null;
	}
	# ------------------------------------------------
	private function _CoreImageRead($ps_filepath) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$vs_output = shell_exec('sips --getProperty format --getProperty space --getProperty bitsPerSample --getProperty pixelWidth --getProperty pixelHeight --getProperty dpiWidth --getProperty dpiHeight '.caEscapeShellArg($ps_filepath)." 2> /dev/null");
			
			$va_tmp = explode("\n", $vs_output);
			
			array_shift($va_tmp);
			
			$va_properties = array();
			foreach($va_tmp as $vs_line) {
				$va_line_tmp = explode(':', $vs_line);
				$va_properties[trim($va_line_tmp[0])] = trim($va_line_tmp[1]);
			}
			
			//
			// TODO: Rotate incoming image as needed
			//
			$vn_orientation_rotation = null;
			if(function_exists('exif_read_data')) {
				if (is_array($va_exif = @exif_read_data($ps_filepath, 'EXIF', true, false))) { 
					if (isset($va_exif['IFD0']['Orientation'])) {
						$vn_orientation = $va_exif['IFD0']['Orientation'];
						switch($vn_orientation) {
							case 3:
								$vn_orientation_rotation = 180;
								break;
							case 6:
								$vn_orientation_rotation = 90;
								break;
							case 8:
								$vn_orientation_rotation = -90;
								break;
						}
					}
				}
			}
			
			$va_faces = caDetectFaces($ps_filepath, $va_properties['pixelWidth'], $va_properties['pixelHeight']);			
			
			return array(
				'mimetype' => $this->appleTypeToMimeType($va_properties['format']),
				'magick' => $va_properties['format'],
				'width' => (in_array($vn_orientation_rotation, array(90, -90))) ? $va_properties['pixelHeight'] : $va_properties['pixelWidth'],
				'height' => (in_array($vn_orientation_rotation, array(90, -90))) ? $va_properties['pixelWidth'] : $va_properties['pixelHeight'],
				'colorspace' => $va_properties['space'],
				'depth' => $va_properties['bitsPerSample'],
				'orientation_rotate' => $vn_orientation_rotation,
				'resolution' => array(
					'x' => $va_properties['dpiWidth'],
					'y' => $va_properties['dpiHeight']
				),
				'ops' => array(),
				'faces' => $va_faces,
				'filepath' => $ps_filepath
			);
		}
		return null;
	}
	# ------------------------------------------------
	private function _CoreImageWrite($pa_handle, $ps_filepath, $ps_mimetype, $pn_quality=null) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$va_ops = array();	
			foreach($pa_handle['ops'] as $va_op) {
				switch($va_op['op']) {
					case 'annotation':
						// TODO: annotation is not currrently supported in this plugin
						
						//$vs_op = '-gravity '.$va_op['position'].' -fill '.str_replace('#', '\\#', $va_op['color']).' -pointsize '.$va_op['size'].' -draw "text '.$va_op['inset'].','.$va_op['inset'].' \''.$va_op['text'].'\'"';
						
						//if ($va_op['font']) {
						//	$vs_op .= ' -font '.$va_op['font'];
						//}
						//$va_ops['convert'][] = $vs_op;
						break;
					case 'watermark':
                        // TODO: watermark position is not currently handled in this plugin

						if (is_array($va_ops) && sizeof($va_ops)) {
							array_unshift($va_ops, "load watermark ".caEscapeShellArg($va_op['watermark_image']));
						}
						$va_ops[] = "filter watermark CIStretchCrop size=".$va_op['watermark_width'].",".$va_op['watermark_height'].":cropAmount=0:centerStretchAmount=0";
						$va_ops[] = "filter watermark CIColorMatrix RVector=1,0,0,0:GVector=0,1,0,0:BVector=0,0,1,0:AVector=0,0,0,".$va_op['opacity'].":BiasVector=0,0,0,0";
						$va_ops[] = "filter image CISoftLightBlendMode backgroundImage=watermark";

						break;
					case 'size':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						
						$vn_scale = $va_op['width']/$this->handle['width'];
						$va_ops[] = "filter image CILanczosScaleTransform scale={$vn_scale}:aspectRatio=1";
						break;
					case 'crop':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						if ($va_op['x'] < 0) { break; }
						if ($va_op['y'] < 0) { break; }
						
						$va_ops[] = "filter image CICrop rectangle=".join(",", array($va_op['x'], $va_op['y'], $va_op['width'], $va_op['height']));
						break;
					case 'rotate':
						if (!is_numeric($va_op['angle'])) { break; }
						$va_ops[] = "filter image CIAffineTransform transform=".cos($va_op['angle']).",".(-1*sin($va_op['angle'])).",0,".sin($va_op['angle']).",".cos($va_op['angle']).",0";
						break;
					case 'filter_despeckle':
						// TODO: see if this works nicely... just using default values
						$va_ops[] = "filter image CINoiseReduction inputNoiseLevel=0.2:inputSharpness=0.4";
						break;
					case 'filter_median':
						if ($va_op['radius'] < 0) { break; }
						// NOTE: CoreImage Median doesn't take a radius, unlike ImageMagick's
						$va_ops[] = "filter image CIMedianFilter ";
						break;
					case 'filter_unsharp_mask':
					case 'filter_sharpen':
						if ($va_op['radius'] < 0) { break; }
						
						$vn_radius = $va_op['radius'];
						if(!($vn_intensity = $va_op['amount'])) {
							$vn_intensity = 1;
						}
						
						$va_ops[] = "filter image CIUnsharpMask radius={$vn_radius}:intensity={$vn_intensity}";
						break;
				}
			}
			
			if (isset($this->properties["orientation_rotate"]) && ($this->properties["orientation_rotate"] != 0)) {
				$va_ops[] = "filter image CIAffineTransform transform=".cos($va_op['angle']).",".(-1*sin($this->properties["orientation_rotate"])).",0,".sin($this->properties["orientation_rotate"]).",".cos($this->properties["orientation_rotate"]).",0";
						
			}
			
			$vs_input_file = $pa_handle['filepath'];
			if (is_array($va_ops) && sizeof($va_ops)) {
				array_unshift($va_ops, "load image ".caEscapeShellArg($vs_input_file));
				array_push($va_ops, "store image ".caEscapeShellArg($ps_filepath)." ".$this->apple_UTIs[$ps_mimetype]);
				//print "<hr>".join(" ", $va_ops)."<hr>";
				exec($this->ops_CoreImage_path." ".join(" ", $va_ops));
				
				$vs_input_file = $ps_filepath;
			}
			
			return true;
		}
		return null;
	}
	# ------------------------------------------------
}
# ----------------------------------------------------------------------
?>
