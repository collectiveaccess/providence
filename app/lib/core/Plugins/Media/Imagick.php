<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/Imagick.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 * Plugin for processing images using ImageMagick via the Imagick PECL extension
*/

include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_LIB_DIR__."/core/Parsers/MediaMetadata/XMPParser.php");

class WLPlugMediaImagick Extends BaseMediaPlugin Implements IWLPlugMedia {
	var $errors = array();
	
	var $ps_filepath;
	var $handle;
	var $ohandle;
	var $properties;
	var $metadata = array();
	
	var $opo_config;
	var $opo_external_app_config;
	
	var $opa_faces;
	
	var $info = array(
		"IMPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/tilepic" 	=> "tpc",
			"image/x-dpx"		=> "dpx",
			"image/x-exr"		=> "exr",
			"image/jp2"		=> "jp2",
			"image/x-adobe-dng"	=> "dng",
			"image/x-canon-cr2"	=> "cr2",
			"image/x-canon-crw"	=> "crw",
			"image/x-sony-arw"	=> "arw",
			"image/x-olympus-orf"	=> "orf",
			"image/x-pentax-pef"	=> "pef",
			"image/x-epson-erf"	=> "erf",
			"image/x-nikon-nef"	=> "nef",
			"image/x-sony-sr2"	=> "sr2",
			"image/x-sony-srf"	=> "srf",
			"image/x-sigma-x3f"	=> "x3f",
			"image/x-dcraw"	=> "raw",
			"application/dicom" => "dcm",
		),
		"EXPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/tilepic" 	=> "tpc",
			"image/x-dpx"		=> "dpx",
			"image/x-exr"		=> "exr",
			"image/jp2"		=> "jp2",
			"image/x-adobe-dng"	=> "dng",
			"image/x-canon-cr2"	=> "cr2",
			"image/x-canon-crw"	=> "crw",
			"image/x-sony-arw"	=> "arw",
			"image/x-olympus-orf"	=> "orf",
			"image/x-pentax-pef"	=> "pef",
			"image/x-epson-erf"	=> "erf",
			"image/x-nikon-nef"	=> "nef",
			"image/x-sony-sr2"	=> "sr2",
			"image/x-sony-srf"	=> "srf",
			"image/x-sigma-x3f"	=> "x3f",
			"image/x-dcraw"	=> "raw",
			"application/dicom" => "dcm",
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing"),
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
		
		"NAME" => "Imagick"
	);
	
	var $typenames = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "Windows Bitmap (BMP)",
		"image/x-psd" 		=> "Photoshop",
		"image/tilepic" 	=> "Tilepic",
		"image/x-dpx"		=> "DPX",
		"image/x-exr"		=> "OpenEXR",
		"image/jp2"		=> "JPEG-2000",
		"image/x-adobe-dng"	=> "Adobe DNG",
		"image/x-canon-cr2"	=> "Canon CR2 RAW Image",
		"image/x-canon-crw"	=> "Canon CRW RAW Image",
		"image/x-sony-arw"	=> "Sony ARW RAW Image",
		"image/x-olympus-orf"	=> "Olympus ORF Raw Image",
		"image/x-pentax-pef"	=> "Pentax Electronic File Image",
		"image/x-epson-erf"	=> "Epson ERF RAW Image",
		"image/x-nikon-nef"	=> "Nikon NEF RAW Image",
		"image/x-sony-sr2"	=> "Sony SR2 RAW Image",
		"image/x-sony-srf"	=> "Sony SRF RAW Image",
		"image/x-sigma-x3f"	=> "Sigma X3F RAW Image",
		"image/x-dcraw"	=> "RAW Image",
		"application/dicom" => "DICOM medical imaging data",
	);
	
	var $magick_names = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "BMP",
		"image/x-psd" 		=> "PSD",
		"image/tilepic" 	=> "TPC",
		"image/x-dpx"		=> "DPX",
		"image/x-exr"		=> "EXR",
		"image/jp2"		=> "JP2",
		"image/x-adobe-dng"	=> "DNG",
		"image/x-canon-cr2"	=> "CR2",
		"image/x-canon-crw"	=> "CRW",
		"image/x-sony-arw"	=> "ARW",
		"image/x-olympus-orf"	=> "ORF",
		"image/x-pentax-pef"	=> "PEF",
		"image/x-epson-erf"	=> "ERF",
		"image/x-nikon-nef"	=> "NEF",
		"image/x-sony-sr2"	=> "SR2",
		"image/x-sony-srf"	=> "SRF",
		"image/x-sigma-x3f"	=> "X3F",
		"image/x-dcraw"	=> "RAW",
		"application/dicom" => "DCM",
	);
	
	#
	# Some versions of ImageMagick return variants on the "normal"
	# mimetypes for certain image formats, so we convert them here
	#
	var $magick_mime_map = array(
		"image/x-jpeg" 		=> "image/jpeg",
		"image/x-gif" 		=> "image/gif",
		"image/x-tiff" 		=> "image/tiff",
		"image/x-png" 		=> "image/png",
		"image/dpx" 		=> "image/x-dpx",
		"image/exr" 		=> "image/x-exr",
		"image/jpx"		=> "image/jp2",
		"image/jpm"		=> "image/jp2",
		"image/dng"		=> "image/x-adobe-dng"
	);
	
	private $ops_CoreImage_path;
	private $ops_dcraw_path;
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides image processing and conversion services using ImageMagick via the PECL Imagick PHP extension');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_CoreImage_path = $this->opo_external_app_config->get('coreimagetool_app');
		
		$this->ops_dcraw_path = $this->opo_external_app_config->get('dcraw_app');
		
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			return null;	// don't use if CoreImage executable are available
		}
		
		if (caMediaPluginGmagickInstalled()) {
			return null;	// don't use if Gmagick extension is available
		}
		
		if (!caMediaPluginImagickInstalled()) {
			return null;	// don't use if Imagick functions are unavailable
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
			if (caMediaPluginGmagickInstalled()) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because Gmagick is available and preferred");
			} 
			if (!caMediaPluginImagickInstalled()) {	
				$va_status['errors'][] = _t("Didn't load because Imagick is not available");
			} 
		}
		
		if (!caMediaPluginDcrawInstalled($this->ops_dcraw_path)) {
			$va_status['warnings'][] = _t("RAW image support is not enabled because DCRAW cannot be found");
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($ps_filepath) {
		# is it a camera raw image?
		if (caMediaPluginDcrawInstalled($this->ops_dcraw_path)) {
			exec($this->ops_dcraw_path." -i ".caEscapeShellArg($ps_filepath)." 2> /dev/null", $va_output, $vn_return);
			if ($vn_return == 0) {
				if ((!preg_match("/^Cannot decode/", $va_output[0])) && (!preg_match("/Master/i", $va_output[0]))) {
					return 'image/x-dcraw';
				}
			}
		}
		
		$r_handle = new Imagick();
		try {
			if ($ps_filepath != '' && ($r_handle->pingImage($ps_filepath))) {
				$mimetype = $this->_getMagickImageMimeType($r_handle);
				if (($mimetype) && $this->info["IMPORT"][$mimetype]) {
					return $mimetype;
				} else {
					return '';
				}
			} 
		} catch (Exception $e) {
			return '';
		}
			
		# is it a tilepic?
		$tp = new TilepicParser();
		if ($tp->isTilepic($ps_filepath)) {
			return 'image/tilepic';
		} else {
			# file format is not supported by this plug-in
			return '';
		}
	}
	# ----------------------------------------------------------
	public function _getMagickImageMimeType($pr_handle) {
		$va_info = $pr_handle->identifyImage();
		$ps_format = $va_info['format'];
		$va_tmp = explode(' ', $ps_format);
		$ps_format = $va_tmp[0];
		foreach($this->magick_names as $vs_mimetype => $vs_format) {
			if ($ps_format == $vs_format) {
				return $vs_mimetype;
			}
		}
		return "image/x-unknown";
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				//print "Invalid property";
				return "";
			}
		} else {
			return "";
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($property == "tile_size") {
				if (($value < 10) || ($value > 10000)) {
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugImagick->set()");
					return "";
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugImagick->set()");
								return "";
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugImagick->set()");
								return "";
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugImagick->set()");
								return "";
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugImagick->set()");
								return "";
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugImagick->set()");
								return "";
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugImagick->set()");
								return "";
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugImagick->set()");
								return "";
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
								return "";
							}
							break;
					}
				} else {
					# invalid property
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugImagick->set()");
					return "";
				}
			}
		} else {
			return "";
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
	public function read($ps_filepath, $mimetype="") {
		if (!(($this->handle) && ($$ps_filepath === $this->filepath))) {
			
			if ($mimetype == 'image/tilepic') {
				#
				# Read in Tilepic format image
				#
				$this->handle = new TilepicParser($ps_filepath);
				if (!$this->handle->error) {
					$this->filepath = $ps_filepath;
					foreach($this->handle->properties as $k => $v) {
						if (isset($this->properties[$k])) {
							$this->properties[$k] = $v;
						}
					}
					$this->properties["mimetype"] = "image/tilepic";
					$this->properties["typename"] = "Tilepic";
					return 1;
				} else {
					$this->postError(1610, $this->handle->error, "WLPlugImagick->read()");
					return false;
				}
			} else {
				$this->handle = "";
				$this->filepath = "";
				$handle = new Imagick();
				
				if ($mimetype == 'image/x-dcraw') {
					if($this->filepath_conv) { @unlink($this->filepath_conv); }
					if (!caMediaPluginDcrawInstalled($this->ops_dcraw_path)) {
						$this->postError(1610, _t("Could not convert Camera RAW format file because conversion tool (dcraw) is not installed"), "WLPlugImagick->read()");
						return false;
					}
					
					$vs_tmp_name = tempnam(caGetTempDirPath(), "rawtmp");
					if (!copy($ps_filepath, $vs_tmp_name)) {
						$this->postError(1610, _t("Could not copy Camera RAW file to temporary directory"), "WLPlugImagick->read()");
						return false;
					}
					exec($this->ops_dcraw_path." -T ".caEscapeShellArg($vs_tmp_name), $va_output, $vn_return);
					if ($vn_return != 0) {
						$this->postError(1610, _t("Camera RAW file conversion failed: %1", $vn_return), "WLPlugImagick->read()");
						return false;
					}
					if (!(file_exists($vs_tmp_name.'.tiff') && (filesize($vs_tmp_name.'.tiff') > 0))) {
						$this->postError(1610, _t("Translation from Camera RAW to TIFF failed"), "WLPlugImagick->read()");
						return false;
					}
					$ps_filepath = $this->filepath_conv = $vs_tmp_name.'.tiff';

					@unlink($vs_tmp_name);
				}
				
				if ($handle->readImage($ps_filepath)) {
					$this->handle = $handle;
					$this->filepath = $ps_filepath;
					
					$va_raw_metadata = $this->handle->getImageProperties();
					$this->metadata = array();
					foreach($va_raw_metadata as $vs_tag => $vs_value) {
						if (sizeof($va_tmp = explode(':', $vs_tag)) > 1) {
							$vs_type = strtoupper($va_tmp[0]);
							$vs_tag = $va_tmp[1];
						} else {
							$vs_type = 'GENERIC';
						}
						if ($vs_type == 'EXIF') { continue; }
						
						$this->metadata[$vs_type][$vs_tag] = $vs_value;
					}
					
					// exif
					if(function_exists('exif_read_data')) {
						if (is_array($va_exif = caSanitizeArray(@exif_read_data($ps_filepath, 'EXIF', true, false)))) { 							
							//
							// Rotate incoming image as needed
							//
							if (isset($va_exif['IFD0']['Orientation'])) {
								$vn_orientation = $va_exif['IFD0']['Orientation'];
								$vs_tmp_basename = tempnam(caGetTempDirPath(), 'ca_image_tmp');
								
								$vb_is_rotated = false;
								switch($vn_orientation) {
									case 3:
										$this->handle->rotateImage("#FFFFFF", 180);
										unset($va_exif['IFD0']['Orientation']);
										$vb_is_rotated = true;
										break;
									case 6:
										$this->handle->rotateImage("#FFFFFF", 90);
										unset($va_exif['IFD0']['Orientation']);
										$vb_is_rotated = true;
										break;
									case 8:
										$this->handle->rotateImage("#FFFFFF", -90);
										unset($va_exif['IFD0']['Orientation']);
										$vb_is_rotated = true;
										break;
								}
								
								if($vb_is_rotated) {								
									if ( $this->handle->writeImage($vs_tmp_basename) ) {
										$va_tmp = $this->handle->getImageGeometry();
										$this->properties["faces"] = $this->opa_faces = caDetectFaces($vs_tmp_basename, $va_tmp['width'], $va_tmp['height']);
									}
									@unlink($vs_tmp_basename);
								}
							}
							$this->metadata['EXIF'] = $va_exif;
						}
					}
					
					// XMP					
					$o_xmp = new XMPParser();
					if ($o_xmp->parse($ps_filepath)) {
						if (is_array($va_xmp_metadata = $o_xmp->getMetadata()) && sizeof($va_xmp_metadata)) {
							$this->metadata['XMP'] = $va_xmp_metadata;
						}
					}
					
					# load image properties
					$va_tmp = $this->handle->getImageGeometry();
					$this->properties["width"] = $va_tmp['width'];
					$this->properties["height"] = $va_tmp['height'];
					$this->properties["quality"] = "";
					$this->properties["filesize"] = $this->handle->getImageLength();
					$this->properties["bitdepth"] = $this->handle->getImageDepth();
					$this->properties["resolution"] = $this->handle->getImageResolution();
					$this->properties["colorspace"] = $this->_getColorspaceAsString($this->handle->getImageColorspace());
					
					// force all images to true color (takes care of GIF transparency for one thing...)
					$this->handle->setImageType(imagick::IMGTYPE_TRUECOLOR);

					if (!$this->handle->setImageColorspace(imagick::COLORSPACE_RGB)) {
						$this->postError(1610, _t("Error during RGB colorspace transformation operation"), "WLPlugImagick->read()");
						return false;
					}
					
					
					if (!$this->properties["faces"]) {
						$this->properties["faces"] = $this->opa_faces = caDetectFaces($ps_filepath, $va_tmp['width'], $va_tmp['height']);
					}
					
					$this->properties["mimetype"] = $this->_getMagickImageMimeType($this->handle);
					$this->properties["typename"] = $this->handle->getImageFormat();
					
					$this->ohandle = $this->handle->clone();
					return 1;
				} else {
					$this->postError(1610, _t("Could not read image file"), "WLPlugImagick->read()");
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
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugImagick->transform()");
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
		
		try {
			switch($operation) {
				# -----------------------
				case 'ANNOTATE':
					$d = new ImagickDraw();
					if ($parameters['font']) { $d->setFont($parameters['font']); }
					
					$size = ($parameters['size'] > 0) ? $parameters['size']: 18;
					$d->setFontSize($size);
				
					$inset = ($parameters['inset'] > 0) ? $parameters['inset']: 0;
					$pw= new ImagickPixel();
					$pw->setColor($parameters['color'] ? $parameters['color'] : "black");
					$d->setFillColor($pw);
					
					switch($parameters['position']) {
						case 'north_east':
							$d->setGravity(imagick::GRAVITY_NORTHEAST);
							break;
						case 'north_west':
							$d->setGravity(imagick::GRAVITY_NORTHWEST);
							break;
						case 'north':
							$d->setGravity(imagick::GRAVITY_NORTH);
							break;
						case 'south_east':
							$d->setGravity(imagick::GRAVITY_SOUTHEAST);
							break;
						case 'south':
							$d->setGravity(imagick::GRAVITY_SOUTH);
							break;
						case 'center':
							$d->setGravity(imagick::GRAVITY_CENTER);
							break;
						case 'south_west':
						default:
							$d->setGravity(imagick::GRAVITY_SOUTHWEST);
							break;
					}
					$this->handle->annotateImage($d,$inset, $size + $inset, 0, $parameters['text']);
					break;
				# -----------------------
				case 'WATERMARK':
					if (!file_exists($parameters['image'])) { break; }
					$vn_opacity_setting = $parameters['opacity'];
					if (($vn_opacity_setting < 0) || ($vn_opacity_setting > 1)) {
						$vn_opacity_setting = 0.5;
					}
					$d = new ImagickDraw();
					
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
					
					$w = new Imagick();
					if (!$w->readImage($parameters['image'])) {
						$this->postError(1610, _t("Couldn't load watermark image at %1", $parameters['image']), "WLPlugImagick->transform:WATERMARK()");
						return false;
					}
					//$w->evaluateImage(imagick::COMPOSITE_MINUS, $vn_opacity, imagick::CHANNEL_OPACITY) ; [seems broken with latest imagick circa March 2010?]
					if(method_exists($w, "setImageOpacity")){ // added in ImageMagick 6.3.1
						$w->setImageOpacity($vn_opacity_setting);
					}
					$d->composite(imagick::COMPOSITE_DISSOLVE,$vn_watermark_x,$vn_watermark_y,$vn_watermark_width,$vn_watermark_height, $w);
					$this->handle->drawImage($d);
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
						if (!$this->handle->resizeImage($w + ($crop_w_edge * 2), $h + ($crop_h_edge * 2), imagick::FILTER_CUBIC, $aa)) {
								$this->postError(1610, _t("Error during resize operation"), "WLPlugImagick->transform()");
								return false;
						}
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
								if ($crop_from_offset_x < 0) { $crop_from_offset_x = 0; }
								if ($crop_from_offset_y < 0) { $crop_from_offset_y = 0; }
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
							if (!$this->handle->cropImage($parameters["width"], $parameters["height"], $crop_w_edge + $crop_from_offset_x, $crop_h_edge + $crop_from_offset_y )) {
								$this->postError(1610, _t("Error during crop operation"), "WLPlugImagick->transform()");
								return false;
							}
							$this->properties["width"] = $parameters["width"];
							$this->properties["height"] = $parameters["height"];
						} else {
							if ($crop_w_edge || $crop_h_edge) {
								if (!$this->handle->cropImage($w, $h, $crop_w_edge, $crop_h_edge )) {
									$this->postError(1610, _t("Error during crop operation"), "WLPlugImagick->transform()");
									return false;
								}
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
					if ( !$this->handle->rotateImage("#FFFFFF", $angle ) ) {
						$this->postError(1610, _t("Error during image rotate"), "WLPlugImagick->transform()");
						return false;
					}
				}
				break;
			# -----------------------
			case "DESPECKLE":
				$radius = $parameters["radius"];
				if ( !$this->handle->despeckleImage() ) {
					$this->postError(1610, _t("Error during image despeckle"), "WLPlugImagick->transform:DESPECKLE()");
					return false;
				}
				break;
			# -----------------------
			case "MEDIAN":
				$radius = $parameters["radius"];
				if ($radius < .1) { $radius = 1; }
				if ( !$this->handle->medianFilterImage($radius) ) {
					$this->postError(1610, _t("Error during image median filter"), "WLPlugImagick->transform:MEDIAN()");
					return false;
				}
				break;
			# -----------------------
			case "SHARPEN":
				$radius = $parameters["radius"];
				if ($radius < .1) { $radius = 1; }
				$sigma = $parameters["sigma"];
				if ($sigma < .1) { $sigma = 1; }
				if ( !$this->handle->sharpenImage( $radius, $sigma) ) {
					$this->postError(1610, _t("Error during image sharpen"), "WLPlugImagick->transform:SHARPEN()");
					return false;
				}
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
				if ( !$this->handle->unsharpMaskImage($radius, $sigma, $amount, $threshold) ) {
					$this->postError(1610, _t("Error during image unsharp mask"), "WLPlugImagick->transform:UNSHARPEN_MASK()");
					return false;
				}
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
		} catch (Exception $e) {
			$this->postError(1610, _t("Imagick exception"), "WLPlugImagick->transform");
			return false;
		}
	}
	# ----------------------------------------------------------
	public function write($ps_filepath, $mimetype) {
		if (!$this->handle) { return false; }
		if(strpos($ps_filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugImagick->write()");
			return false;
		}
		if ($mimetype == "image/tilepic") {
			if ($this->properties["mimetype"] == "image/tilepic") {
				copy($this->filepath, $ps_filepath);
			} else {
				$tp = new TilepicParser();
				if (!($properties = $tp->encode($this->filepath, $ps_filepath, 
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
			
			$this->handle->setImageFormat($this->magick_names[$mimetype]);
			# set quality
			if (($this->properties["quality"]) && ($this->properties["mimetype"] != "image/tiff")){ 
				$this->handle->setCompressionQuality($this->properties["quality"]);
			}
			
			$this->handle->setImageBackgroundColor(new ImagickPixel("#CC0000"));
			$this->handle->setImageMatteColor(new ImagickPixel("#CC0000"));
		
			if ($this->properties['gamma']) {
				if (!$this->properties['reference-black']) { $this->properties['reference-black'] = 0; }
				if (!$this->properties['reference-white']) { $this->properties['reference-white'] = 65535; }
				$this->handle->levelImage($this->properties['reference-black'], $this->properties['gamma'], $this->properties['reference-white']);
			}
			
			$this->handle->stripImage();	// remove all lingering metadata
			
			# write the file
			try {
				if ( !$this->handle->writeImage($ps_filepath.".".$ext ) ) {
					$this->postError(1610, _t("Error writing file"), "WLPlugImagick->write()");
					return false;
				}
			} catch (Exception $e) {
				$this->postError(1610, _t("Error writing file: %1", $e->getMessage()), "WLPlugImagick->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = $this->handle->getImageFormat();
			
			return $ps_filepath.".".$ext;
		}
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
		if(!$this->handle) { return false; }
		if($this->handle->getNumberImages() < 2) { return false; } // don't generate previews for single images

		if (!isset($pa_options['outputDirectory']) || !$pa_options['outputDirectory'] || !file_exists($pa_options['outputDirectory'])) {
			if (!($vs_tmp_dir = $this->opo_config->get("taskqueue_tmp_directory"))) {
				// no dir
				return false;
			}
		} else {
			$vs_tmp_dir = $pa_options['outputDirectory'];
		}

		$vs_output_file_prefix = tempnam($vs_tmp_dir, 'caMultipagePreview');

		$va_files = array();
		$vn_i = 0;

		foreach($this->handle as $image){
			$image->writeImage($vs_output_file_prefix.sprintf("_%05d", $vn_i).".jpg");
			$va_files[$vn_i] = $vs_output_file_prefix.sprintf("_%05d", $vn_i).'.jpg';
			$vn_i++;
		}

		@unlink($vs_output_file_prefix);
		return $va_files;
	}
	# ------------------------------------------------
	public function joinArchiveContents($pa_files, $pa_options = array()) {
		if(!is_array($pa_files)) { return false; }

		$vs_archive_original = tempnam(caGetTempDirPath(), "caArchiveOriginal");
		@rename($vs_archive_original, $vs_archive_original.".tif");
		$vs_archive_original = $vs_archive_original.".tif";

		$vo_orig = new Imagick();

		foreach($pa_files as $vs_file){
			if(file_exists($vs_file)){
				$vo_imagick = new Imagick();

				if($vo_imagick->readImage($vs_file)){
					$vo_orig->addImage($vo_imagick);
				}
			}
		}

		if($vo_orig->getNumberImages() > 0){
			if($vo_orig->writeImages($vs_archive_original,true)){
				return $vs_archive_original;
			}
		}

		return false;
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
		return "";
	}
	# ------------------------------------------------
	private function _getColorspaceAsString($pn_colorspace) {
		switch($pn_colorspace) {
			case imagick::COLORSPACE_UNDEFINED:
				$vs_colorspace = 'UNDEFINED';
				break;
			case imagick::COLORSPACE_RGB:
				$vs_colorspace = 'RGB';
				break;
			case imagick::COLORSPACE_GRAY:
				$vs_colorspace = 'GRAY';
				break;
			case imagick::COLORSPACE_TRANSPARENT:
				$vs_colorspace = 'TRANSPARENT';
				break;
			case imagick::COLORSPACE_OHTA:
				$vs_colorspace = 'OHTA';
				break;
			case imagick::COLORSPACE_LAB:
				$vs_colorspace = 'LAB';
				break;
			case imagick::COLORSPACE_XYZ:
				$vs_colorspace = 'XYZ';
				break;
			case imagick::COLORSPACE_YCBCR:
				$vs_colorspace = 'YCBCR';
				break;
			case imagick::COLORSPACE_YCC:
				$vs_colorspace = 'YCC';
				break;
			case imagick::COLORSPACE_YIQ:
				$vs_colorspace = 'YIQ';
				break;
			case imagick::COLORSPACE_YPBPR:
				$vs_colorspace = 'YPBPR';
				break;
			case imagick::COLORSPACE_YUV:
				$vs_colorspace = 'YUV';
				break;
			case imagick::COLORSPACE_CMYK:
				$vs_colorspace = 'CMYK';
				break;
			case imagick::COLORSPACE_SRGB:
				$vs_colorspace = 'SRGB';
				break;
			case imagick::COLORSPACE_HSB:
				$vs_colorspace = 'HSB';
				break;
			case imagick::COLORSPACE_HSL:
				$vs_colorspace = 'HSL';
				break;
			case imagick::COLORSPACE_HWB:
				$vs_colorspace = 'HWB';
				break;
			case imagick::COLORSPACE_REC601LUMA:
				$vs_colorspace = 'REC601LUMA';
				break;
			case imagick::COLORSPACE_REC709LUMA:
				$vs_colorspace = 'REC709LUMA';
				break;
			case imagick::COLORSPACE_LOG:
				$vs_colorspace = 'LOG';
				break;
			default:
				$vs_colorspace = 'UNKNOWN';
				break;
		}
		return $vs_colorspace;
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	public function reset() {
		if ($this->ohandle) {
			$this->handle = $this->ohandle->clone();
			# load image properties
			$va_tmp = $this->handle->getImageGeometry();
			$this->properties["width"] = $va_tmp['width'];
			$this->properties["height"] = $va_tmp['height'];
			$this->properties["quality"] = "";
			$this->properties["mimetype"] = $this->_getMagickImageMimeType($this->handle);
			$this->properties["typename"] = $this->handle->getImageFormat();
			$this->properties["faces"] = $this->opa_faces;
			return 1;
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
		$this->opa_faces = null;
	}
	# ------------------------------------------------
	public function cleanup() {
		$this->destruct();
	}
	# ------------------------------------------------
	public function destruct() {
		if(is_object($this->handle)) { $this->handle->destroy(); }
		if(is_object($this->ohandle)) { $this->ohandle->destroy(); }
		
		if ($this->filepath_conv) {
			@unlink($this->filepath_conv);
		}
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!is_array($pa_properties)) { $pa_properties = array(); }
		return caHTMLImage($ps_url, array_merge($pa_options, $pa_properties));
	}	
	# ------------------------------------------------
}
# ----------------------------------------------------------------------
?>