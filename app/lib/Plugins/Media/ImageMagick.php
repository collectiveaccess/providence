<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Media/ImageMagick.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2021 Whirl-i-Gig
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
  * Plugin for processing images using ImageMagick command-line executables
  */

include_once(__CA_LIB_DIR__."/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_LIB_DIR__."/Parsers/MediaMetadata/XMPParser.php");

/**
 * Trait CommandConfiguration
 *
 * Manage the command configuration, application and default args.
 *
 * TODO: Refactor to be included in BaseMediaPlugin so it is available in all media classes.
 */
trait CommandConfiguration {

	static private $opo_external_app_config_static = null;
	private $ops_base_path = null;
	private $ops_command_name = null;

	/**
	 * Initialize
	 *
	 * @param $ps_command_name Set application name. Used for getting settings from configuration file.
	 * @param $ps_basepath_setting Name of the setting to the base path
	 */
	public function initCommandConfiguration( $ps_command_name, $ps_basepath_setting ) {
		$this->ops_command_name = $ps_command_name;
		if ( self::$opo_external_app_config_static == null ) {
			self::$opo_external_app_config_static = Configuration::load( __CA_CONF_DIR__ . "/external_applications.conf" );
		}
		//$this->ops_base_path = caGetExternalApplicationPath('imagemagick'); //self::$opo_external_app_config_static->get( $ps_basepath_setting );
	}

	/**
	 * Load command default arguments from configuration
	 *
	 * @param $command
	 * @param $default
	 *
	 * @return string
	 */
	public function getCommandDefaultArgs( $command, $default ) {
		$command = trim( $command );

		return self::$opo_external_app_config_static->get( $this->ops_command_name . "_${command}_args", $default );
	}

	/**
	 * Return absolute path to command and default arguments
	 *
	 * @param      $command
	 * @param null $default_args
	 *
	 * @return string
	 */
	public function commandWithDefaultArgs( $command, $default_args = null ) {
		return $this->command( $command ) . " " . $this->getCommandDefaultArgs( $command, $default_args );
	}

	/**
	 * Return absolute path to command
	 *
	 * @param $command
	 *
	 * @return string
	 */
	public function command( $command ) {
		$command = trim( $command );

		return "{$this->ops_base_path}/$command";
	}
	
	/**
	 * Sets base path. Base path is set by the constructor but can be overriden here if needed.
	 */
	public function setBasePath($base_path) {
		return $this->ops_base_path = $base_path;
	}
	
	/**
	 * Returns base path 
	 */
	abstract public function getBasePath();
}

class WLPlugMediaImageMagick Extends BaseMediaPlugin Implements IWLPlugMedia {

	use CommandConfiguration;
	
	protected $handle;
	protected $ohandle;
	var $opo_config;
	var $errors = [];
	var $filepath = null;
	var $properties = [];
	var $metadata = [];
	

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
			"application/dicom" => "dcm",
			'image/heic'		=> 'heic'
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
			"application/dicom" => "dcm",
			'image/heic'		=> 'heic'
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing", "trim_edges", "crop_from"),
			'CROP' 				=> array('width', 'height', 'x', 'y'),
			"ANNOTATE"			=> array("text", "font", "size", "color", "position", "inset"),
			"WATERMARK"			=> array("image", "width", "height", "position", "opacity"),
			"ROTATE" 			=> array("angle"),
			"SET" 				=> array("property", "value"),
			
			# --- filters
			"MEDIAN"			=> array("radius"),
			"DESPECKLE"			=> array(""),
			"SHARPEN"			=> array("radius", "sigma"),
			"UNSHARPEN_MASK"	=> array("radius", "sigma", "amount", "threshold"),
			'FLIP'				=> array('direction')
		),
		"PROPERTIES" => array(
			"width" 			=> 'R',
			"height" 			=> 'R',
			"mimetype" 			=> 'R',
			"typename" 			=> 'R',
			'tiles'				=> 'R',
			'layers'			=> 'W',
			"quality" 			=> 'W',
			'colorspace'		=> 'W',
			'background'		=> 'W',
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
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "ImageMagick"
	);
	
	var $typenames = array(
		'image/jpeg' 		=> 'JPEG',
		'image/gif' 		=> 'GIF',
		'image/tiff' 		=> 'TIFF',
		'image/png' 		=> 'PNG',
		'image/x-bmp' 		=> 'Windows Bitmap (BMP)',
		'image/x-psd' 		=> 'Photoshop',
		'image/tilepic' 	=> 'Tilepic',
		'image/x-dpx'		=> 'DPX',
		'image/x-exr'		=> 'OpenEXR',
		'image/jp2'		=> 'JPEG-2000',
		'image/x-adobe-dng'	=> 'Adobe DNG',
		'image/x-canon-cr2'	=> 'Canon CR2 RAW Image',
		'image/x-canon-crw'	=> 'Canon CRW RAW Image',
		'image/x-sony-arw'	=> 'Sony ARW RAW Image',
		'image/x-olympus-orf'	=> 'Olympus ORF Raw Image',
		'image/x-pentax-pef'	=> 'Pentax Electronic File Image',
		'image/x-epson-erf'	=> 'Epson ERF RAW Image',
		'image/x-nikon-nef'	=> 'Nikon NEF RAW Image',
		'image/x-sony-sr2'	=> 'Sony SR2 RAW Image',
		'image/x-sony-srf'	=> 'Sony SRF RAW Image',
		'image/x-sigma-x3f'	=> 'Sigma X3F RAW Image',
		'application/dicom' => 'DICOM medical imaging data',
		'image/heic' 		=> 'HEIC'
	);
	
	var $magick_names = array(
		'image/jpeg' 		=> 'JPEG',
		'image/gif' 		=> 'GIF',
		'image/tiff' 		=> 'TIFF',
		'image/png' 		=> 'PNG',
		'image/x-bmp' 		=> 'BMP',
		'image/x-psd' 		=> 'PSD',
		'image/tilepic' 	=> 'TPC',
		'image/x-dpx'		=> 'DPX',
		'image/x-exr'		=> 'EXR',
		'image/jp2'		=> 'JP2',
		'image/x-adobe-dng'	=> 'DNG',
		'image/x-canon-cr2'	=> 'CR2',
		'image/x-canon-crw'	=> 'CRW',
		'image/x-sony-arw'	=> 'ARW',
		'image/x-olympus-orf'	=> 'ORF',
		'image/x-pentax-pef'	=> 'PEF',
		'image/x-epson-erf'	=> 'ERF',
		'image/x-nikon-nef'	=> 'NEF',
		'image/x-sony-sr2'	=> 'SR2',
		'image/x-sony-srf'	=> 'SRF',
		'image/x-sigma-x3f'	=> 'X3F',
		'application/dicom' => 'DCM',
		'image/heic' 		=> 'HEIC'
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
	
	#
	# Alternative extensions for supported types
	#
	var $alternative_extensions = [
		'tif' => 'image/tiff',
		'jpeg' => 'image/jpeg'
	];	
	
	# ------------------------------------------------
	public function __construct() {
		$this->ops_base_path = $this->getBasePath();
		
		$this->handle = null;
		$this->description = _t('Provides image processing and conversion services using ImageMagick via exec() calls to ImageMagick binaries');
		$this->init();
		
		$this->initCommandConfiguration('imagemagick', 'imagemagick_path');
	}
	# ------------------------------------------------
	/**
	 * Returns base path to ImageMagick installation
	 */
	public function getBasePath() {
		return pathInfo(caMediaPluginImageMagickInstalled(), PATHINFO_DIRNAME);
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		// get config for external apps
		$this->opo_config = Configuration::load();

		if (caMediaPluginImagickInstalled()) {	
			return null;	// don't use if Imagick is available
		} 
		
		if (caMediaPluginGmagickInstalled()) {	
			return null;	// don't use if Gmagick is available
		}
		
		if (caMediaPluginGraphicsMagickInstalled()){
			return null;	// don't use if GraphicsMagick is available
		}
	
		if (!$this->ops_base_path) {
			return null;	// don't use if Imagemagick executables are unavailable
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
			if (caMediaPluginGraphicsMagickInstalled()) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because GraphicsMagick is available and preferred");
			}
			if (caMediaPluginImagickInstalled()) {	
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because Imagick is available and preferred");
			}
			if (caMediaPluginGmagickInstalled()) {	
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because Gmagick is available and preferred");
			}
			if (!$this->ops_base_path) {
				$va_status['errors'][] = _t("Didn't load because ImageMagick executables cannot be found");
			}
			
		}	
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		if(!strpos($filepath, ':') || (caGetOSFamily() == OS_WIN32)) {
			// ImageMagick bails when a colon is in the file name... catch it here
			if ($vs_mimetype = $this->_imageMagickIdentify($filepath)) { return $vs_mimetype; }
		} else {
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugImageMagick->divineFileFormat()");
			return false;
		}

		# is it a tilepic?
		$tp = new TilepicParser();
		if ($tp->isTilepic($filepath)) {
			return 'image/tilepic';
		} else {
			# file format is not supported by this plug-in
			return '';
		}
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle ?? null) {
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
		if ($this->handle ?? null) {
			if ($property == "tile_size") {
				if (($value < 10) || ($value > 10000)) {
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugImageMagick->set()");
					return '';
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugImageMagick->set()");
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
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugImageMagick->set()");
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
	public function read($filepath, $mimetype="", $options=null) {
		if (!isset($this->handle) || ($filepath !== ($this->filepath ?? null))) {
			
			if(strpos($filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
				$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugImageMagick->read()");
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
					
					return 1;
				} else {
					$this->postError(1610, $this->handle->error, "WLPlugImageMagick->read()");
					return false;
				}
			} else {
				$this->handle = "";
				$this->filepath = "";
				
				$this->metadata = array();
				
				$handle = $this->_imageMagickRead($filepath, $options);
				if ($handle) {
					$this->handle = $handle;
					$this->filepath = $filepath;
					
					# load image properties
					$this->properties["width"] = $this->handle['width'];
					$this->properties["height"] = $this->handle['height'];
					$this->properties["mimetype"] = $this->handle['mimetype'];
					$this->properties["typename"] = $this->handle['magick'];
					$this->properties["filesize"] = filesize($filepath);
					$this->properties["bitdepth"] = $this->handle['depth'];
					$this->properties["resolution"] = $this->handle['resolution'];
					$this->properties["colorspace"] = $this->handle['colorspace'];
					
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
		if (($this->properties["mimetype"] ?? null) == "image/tilepic") { return false;} # no transformations for Tilepic
		if (!($this->handle ?? null)) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation] ?? null)) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugImageMagick->transform()");
			return false;
		}

		# get parameters for this operation
		$w = $parameters["width"] ?? null;
		$h = $parameters["height"] ?? null;
		
		$cw = $this->get("width");
		$ch = $this->get("height");
		
		if((bool)($this->properties['no_upsampling'] ?? false)) {
			$w = min($cw, round($w)); 
			$h = min($ch, round($h));
		}
		
		$do_fill_box_crop = false;

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
						$crop_from = $parameters["crop_from"] ?? null;
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
					if (preg_match("/^([\d]+)%$/", $parameters["trim_edges"] ?? null, $va_matches)) {
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
								$crop_from_offset_x = $crop_from_offset_y = 0;
							
								// Get image center
								$vn_center_x = caGetOption('_centerX', $parameters, 0.5);
								$vn_center_y = caGetOption('_centerY', $parameters, 0.5);
								if ($w > $parameters["width"]) {
									$crop_from_offset_x = ceil($w * $vn_center_x) - ($parameters["width"]/2);
									if (($crop_from_offset_x + $parameters["width"]) > $w) { $crop_from_offset_x = $w - $parameters["width"]; }
									if ($crop_from_offset_x < 0) { $crop_from_offset_x = 0; }
								} else {
									if ($h > $parameters["height"]) {
										$crop_from_offset_y = ceil($h * $vn_center_y) - ($parameters["height"]/2);
										if (($crop_from_offset_y + $parameters["height"]) > $h) { $crop_from_offset_y = $h - $parameters["height"]; }
										if ($crop_from_offset_y < 0) { $crop_from_offset_y = 0; }
									}
								}
								break;
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
		case "CROP":
			$x = $parameters["x"];
			$y = $parameters["y"];
			$w = $parameters["width"];
			$h = $parameters["height"];
			
			$this->handle['ops'][] = array(
				'op' => 'crop',
				'x' => $x,
				'y' => $y,
				'width' => $w,
				'height' => $h
			);
			break;
		# -----------------------
		case "FLIP":
			$dir = strtolower($parameters["direction"]);
			
			if ($dir == 'vertical') {
				$this->handle['ops'][] = array(
					'op' => 'flip'
				);
			} else {
				$this->handle['ops'][] = array(
					'op' => 'flop'
				);
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
			foreach($parameters as $k => $v){
				$this->set($k, $v);
			}
			break;
		# -----------------------
		}
		return 1;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype) {
		if (!($this->handle ?? null)) { 
			$this->postError(1610, _t("No filepath specified for output"), "WLPlugImageMagick->write()");
			return false; 
		}
		if(strpos($filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugImageMagick->write()");
			return false;
		}
		if ($mimetype == "image/tilepic") {
			if ($this->properties["mimetype"] == "image/tilepic") {
				copy($this->filepath, $filepath);
			} else {
				$tp = new TilepicParser();
				if (!($properties = $tp->encode($this->filepath, $filepath, 
					array(
						"tile_width" => $this->properties["tile_width"] ?? null,
						"tile_height" => $this->properties["tile_height"] ?? null,
						"layer_ratio" => $this->properties["layer_ratio"] ?? null,
						"quality" => $this->properties["quality"] ?? null,
						"antialiasing" => $this->properties["antialiasing"] ?? null,
						"output_mimetype" => $this->properties["tile_mimetype"] ?? null,
						"layers" => $this->properties["layers"] ?? null,
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
					
			if (!$this->_imageMagickWrite($this->handle, $filepath.".".$ext, $mimetype, $this->properties["quality"])) {
				$this->postError(1610, _t("Could not write file %1", $filepath.".".$ext), "WLPlugImageMagick->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = $this->magick_names[$mimetype];
			
			return $filepath.".".$ext;
		}
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
		global $file_cleanup_list;
		
		if(!($this->filepath ?? null)) {
			$this->postError(1610, _t("No filepath specified for previews"), "WLPlugImageMagick->writePreviews()");
			return false;
		}
		
		if(!caMediaPluginImageMagickInstalled()) { return false; }

		if (!isset($pa_options['outputDirectory']) || !$pa_options['outputDirectory'] || !file_exists($pa_options['outputDirectory'])) {
			if (!($vs_tmp_dir = $this->opo_config->get("taskqueue_tmp_directory"))) {
				// no dir
				return false;
			}
		} else {
			$vs_tmp_dir = $pa_options['outputDirectory'];
		}

		$va_output = array();
		caExec( join( ' ', array(
			$this->commandWithDefaultArgs( 'identify' ),
			'-format "%m\n"',
			caEscapeShellArg( $this->filepath ) . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
		) ), $va_output, $vn_return );

		// don't extract previews from "normal" images (the output line count is always # of files + 1)
		if(sizeof($va_output)<=2) { return false; } 

		$vs_output_file_prefix = tempnam($vs_tmp_dir, 'caMultipagePreview');
		$vs_output_file = $vs_output_file_prefix.'_%05d.jpg';

		caExec( join( ' ', array(
			$this->commandWithDefaultArgs( 'convert' ),
			caEscapeShellArg( $this->filepath ),
			$vs_output_file . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
		) ), $va_output, $vn_return );

		$vn_i = 0;
		$va_files = array();
		while(file_exists($vs_output_file_prefix.sprintf("_%05d", $vn_i).'.jpg')) {
			// add image to list
			$file_cleanup_list[] = $va_files[$vn_i] = $vs_output_file_prefix.sprintf("_%05d", $vn_i).'.jpg';
			$vn_i++;
		}

		@unlink($vs_output_file_prefix);
		return $va_files;
	}
	# ------------------------------------------------
	public function joinArchiveContents($pa_files, $pa_options = array()) {
		global $file_cleanup_list;
		
		if(!is_array($pa_files)) { return false; }

		if (!caMediaPluginImageMagickInstalled()) { return false; }

		$vs_archive_original = tempnam(caGetTempDirPath(), "caArchiveOriginal");
		@rename($vs_archive_original, $vs_archive_original.".tif");
		$file_cleanup_list[] = $vs_archive_original = $vs_archive_original.".tif";

		$va_acceptable_files = array();
		foreach($pa_files as $vs_file){
			if(file_exists($vs_file)){
				if($this->_imageMagickIdentify($vs_file)){
					$va_acceptable_files[] = $vs_file;
				}
			}
		}

		if ( sizeof( $va_acceptable_files ) ) {
			caExec( join( ' ', array(
				$this->commandWithDefaultArgs( 'convert' ),
				join( ' ', $va_acceptable_files ),
				$vs_archive_original . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
			) ), $va_output, $vn_return );
			if ( $vn_return === 0 ) {
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
		foreach($this->info["EXPORT"] as $k => $v){
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
	public function magickToMimeType($ps_magick) {
		foreach($this->magick_names as $vs_mimetype => $vs_magick) {
			if ($ps_magick == $vs_magick) {
				return $vs_mimetype;
			}
		}
		return null;
	}
	# ------------------------------------------------
	public function reset() {
		if ($this->ohandle ?? null) {
			$this->handle = $this->ohandle;
			# load image properties
			$this->properties["width"] = $this->handle['width'];
			$this->properties["height"] = $this->handle['height'];
			$this->properties["quality"] = "";
			$this->properties["mimetype"] = $this->handle['mimetype'];
			$this->properties["typename"] = $this->handle['magick'];
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
	private function _imageMagickIdentify( $ps_filepath ) {
		if (caMediaPluginImageMagickInstalled() ) {
			caExec( join( ' ', array(
				$this->commandWithDefaultArgs( 'identify' ),
				'-format "%m"',
				caEscapeShellArg( $ps_filepath . '[0]' ) . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
			) ), $va_output, $vn_return );

			return $this->magickToMimeType( $va_output[0] );
		}

		return null;
	}
	# ------------------------------------------------
	private function _imageMagickGetMetadata($ps_filepath) {
		if (caMediaPluginImageMagickInstalled()) {
			$va_metadata = array();
			
			if(function_exists('exif_read_data') && !($this->opo_config->get('dont_use_exif_read_data'))) {
				if (is_array($va_exif = caSanitizeArray(@exif_read_data($ps_filepath, 'EXIF', true, false)))) { $va_metadata['EXIF'] = $va_exif; }
			}

			// if the builtin EXIF extraction is not used or failed for some reason, try ExifTool
			if(!isset($va_metadata['EXIF']) || !is_array($va_metadata['EXIF'])) {
				if(caExifToolInstalled()) {
					$va_metadata['EXIF'] = caExtractMetadataWithExifTool($ps_filepath, true);
				}
			}
			
			$o_xmp = new XMPParser();
			if ($o_xmp->parse($ps_filepath)) {
				if (is_array($va_xmp_metadata = $o_xmp->getMetadata()) && sizeof($va_xmp_metadata)) {
					$va_metadata['XMP'] = $va_xmp_metadata;
				}
			}

			caExec( join( ' ', array(
				$this->commandWithDefaultArgs( 'identify' ),
				"-format '%[DPX:*]'",
				caEscapeShellArg( $ps_filepath ) . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
			) ), $va_output, $vn_return );
			if ($va_output[0] ?? null) { $va_metadata['DPX'] = $va_output; }
			
			/* IPTC metadata */
			$vs_iptc_file = tempnam(caGetTempDirPath(), 'imiptc');
			@rename($vs_iptc_file, $vs_iptc_file.'.iptc'); // IM uses the file extension to figure out what we want
			$vs_iptc_file .= '.iptc';
			caExec( join( ' ', array(
				$this->commandWithDefaultArgs( 'convert' ),
				caEscapeShellArg( $ps_filepath ),
				caEscapeShellArg( $vs_iptc_file ) . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
			) ), $va_output, $vn_return );

			$vs_iptc_data = file_get_contents($vs_iptc_file);
			@unlink($vs_iptc_file);

			$va_iptc_raw = iptcparse($vs_iptc_data);

			$va_iptc_tags = array(
				'2#004'=>'Genre',
				'2#005'=>'DocumentTitle',
				'2#010'=>'Urgency',
				'2#015'=>'Category',
				'2#020'=>'Subcategories',
				'2#025'=>'Keywords',
				'2#040'=>'SpecialInstructions',
				'2#055'=>'CreationDate',
				'2#060'=>'TimeCreated',
				'2#080'=>'AuthorByline',
				'2#085'=>'AuthorTitle',
				'2#090'=>'City',
				'2#095'=>'State',
				'2#100'=>'CountryCode',
				'2#101'=>'Country',
				'2#103'=>'OTR',
				'2#105'=>'Headline',
				'2#110'=>'Credit',
				'2#115'=>'PhotoSource',
				'2#116'=>'Copyright',
				'2#120'=>'Caption',
				'2#122'=>'CaptionWriter'
			);

			$va_iptc = array();
			if (is_array($va_iptc_raw)) {
				foreach($va_iptc_raw as $vs_iptc_tag => $va_iptc_tag_data){
					if(isset($va_iptc_tags[$vs_iptc_tag])) {
						$va_iptc[$va_iptc_tags[$vs_iptc_tag]] = join('; ',$va_iptc_tag_data);
					}
				}
			}

			if (sizeof($va_iptc)) {
				$va_metadata['IPTC'] = $va_iptc;
			}
			
			if (sizeof($va_iptc)) {
				$va_metadata['IPTC'] = $va_iptc;
			}
			return $va_metadata;
		}
		return null;
	}
	# ------------------------------------------------
	private function _imageMagickRead($ps_filepath, $options=null) {
		if (caMediaPluginImageMagickInstalled()) {

			caExec( join( ' ', array(
				$this->commandWithDefaultArgs( 'identify' ),
				'-format "%m;%w;%h;%[colorspace];%[depth];%[xresolution];%[yresolution]\n" ',
				caEscapeShellArg( $ps_filepath ) . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
			) ), $va_output, $vn_return );

			$va_tmp = explode(';', $va_output[0]);
			
			if (sizeof($va_tmp) != 7) {
				return null;
			}
			
			$this->metadata = $this->_imageMagickGetMetadata($ps_filepath);
			
			//
			// Rotate incoming image as needed
			//
			if(isset($this->metadata['EXIF']) && is_array($va_exif = $this->metadata['EXIF'])) {
				if (isset($va_exif['IFD0']['Orientation'])) {
					$vn_orientation = $va_exif['IFD0']['Orientation'];
					switch($vn_orientation) {
						case 3:
							$this->properties["orientation_rotate"] = -180;
							break;
						case 6:
							$this->properties["orientation_rotate"] = 90;
							break;
						case 8:
							$this->properties["orientation_rotate"] = -90;
							break;
					}
				}
				
				// rewrite file name to use originally uploaded name
				if(array_key_exists("FILE", $this->metadata['EXIF']) && ($f = caGetOption('original_filename', $options, null))) {
					$this->metadata['EXIF']['FILE']['FileName'] = $f;
				}
			}		
			
			return array(
				'mimetype' => $this->magickToMimeType($va_tmp[0]),
				'magick' => $va_tmp[0],
				'width' => in_array($this->properties["orientation_rotate"] ?? null, array(90, -90)) ? $va_tmp[2] : $va_tmp[1],
				'height' => in_array($this->properties["orientation_rotate"] ?? null, array(90, -90)) ? $va_tmp[1] : $va_tmp[2],
				'colorspace' => $va_tmp[3],
				'depth' => $va_tmp[4],
				'resolution' => array(
					'x' => $va_tmp[5],
					'y' => $va_tmp[6]
				),
				'ops' => ($this->properties["orientation_rotate"] ?? null) ? array(0 => array('op' => 'strip')) : array(),
				'filepath' => $ps_filepath
			);
		}
		return null;
	}
	# ------------------------------------------------
	private function _imageMagickWrite($pa_handle, $ps_filepath, $ps_mimetype, $pn_quality=null) {
		if (caMediaPluginImageMagickInstalled()) {
			if (($this->properties["colorspace"]) && ($this->properties["colorspace"] != "default")){ 
				$vn_colorspace = null;
				switch($this->properties["colorspace"]) {
					case 'greyscale':
					case 'grey':
						$pa_handle['ops'][] = ['op' => 'colorspace', 'colorspace' => 'Gray'];
						break;
					case 'RGB':
					case 'color':
						$pa_handle['ops'][] = ['op' => 'colorspace', 'colorspace' => 'RGB'];
						break;
					case 'sRGB':
						$pa_handle['ops'][] = ['op' => 'colorspace', 'colorspace' => 'sRGB'];
						break;
					case 'CMYK':
						$pa_handle['ops'][] = ['op' => 'colorspace', 'colorspace' => 'CMYK'];
						break;
					case 'bitonal':
						$pa_handle['ops'][] = ['op' => 'threshold', 'threshold' => '50%'];
						break;
				}
			}
			if($background = caGetOption('background', $this->properties, null)) {
				$pa_handle['ops'][] = ['op' => 'background', 'color' => $background];
			}
			
			$va_ops = array();	
			foreach($pa_handle['ops'] as $va_op) {
				switch($va_op['op']) {
					case 'strip':
						$va_ops['convert'][] = "-strip";
						break;
					case 'annotation':
						$vs_op = '-gravity '.$va_op['position'].' -fill '.str_replace('#', '\\#', $va_op['color']).' -pointsize '.$va_op['size'].' -draw "text '.$va_op['inset'].','.$va_op['inset'].' \''.$va_op['text'].'\'"';
						
						if ($va_op['font']) {
							$vs_op .= ' -font '.$va_op['font'];
						}
						$va_ops['convert'][] = $vs_op;
						break;
					case 'watermark':
						$vs_op = "-dissolve ".($va_op['opacity'] * 100)." -gravity ".$va_op['position']." ".$va_op['watermark_image']; //"  -geometry ".$va_op['watermark_width']."x".$va_op['watermark_height']; [Seems to be interpreted as scaling the image being composited on as of at least v6.5.9; so we don't scale watermarks in ImageMagick... we just use the native size]
						$va_ops['composite'][] = $vs_op;
						break;
					case 'size':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						$va_ops['convert'][] = '-resize '.$va_op['width'].'x'.$va_op['height'].' -filter Cubic';
						break;
					case 'crop':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						if ($va_op['x'] < 0) { break; }
						if ($va_op['y'] < 0) { break; }
						$va_ops['convert'][] = '-crop '.$va_op['width'].'x'.$va_op['height'].'+'.$va_op['x'].'+'.$va_op['y'];
						break;
					case 'rotate':
						if (!is_numeric($va_op['angle'])) { break; }
						$va_ops['convert'][] = '-rotate '.$va_op['angle'];
						break;
					case 'colorspace':
						$va_ops['convert'][] = '-colorspace '.$va_op['colorspace'];
						break;
					case 'threshold':
						$va_ops['convert'][] = '-threshold '.$va_op['threshold'];
						break;
					case 'filter_despeckle':
						$va_ops['convert'][] = '-despeckle';
						break;
					case 'filter_sharpen':
						if ($va_op['radius'] < 0) { break; }
						$vs_tmp = '-sharpen '.$va_op['radius'];
						if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
						$va_ops['convert'][] = $vs_tmp;
						break;
					case 'filter_median':
						if ($va_op['radius'] < 0) { break; }
						$va_ops['convert'][] = '-median '.$va_op['radius'];
						break;
					case 'filter_unsharp_mask':
						if ($va_op['radius'] < 0) { break; }
						$vs_tmp = '-unsharp '.$va_op['radius'];
						if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
						if (isset($va_op['amount'])) { $vs_tmp .= '+'.$va_op['amount'];}
						if (isset($va_op['threshold'])) { $vs_tmp .= '+'.$va_op['threshold'];}
						$va_ops['convert'][] = $vs_tmp;
						break;
					case 'background':
						$va_ops['convert'][] = '-background "'.$va_op['color'].'"  -extent 0x0';
						break;
				}
			}
			
			if (isset($this->properties["orientation_rotate"]) && ($this->properties["orientation_rotate"] != 0)) {
				$va_ops['convert'][] = '-rotate '.$this->properties["orientation_rotate"];
			}
			
			if ($this->properties['gamma'] ?? null) {
				if (!($this->properties['reference-black'] ?? null)) { $this->properties['reference-black'] = 0; }
				if (!($this->properties['reference-white'] ?? null)) { $this->properties['reference-white'] = 65535; }
				$va_ops['convert'][] = "-set gamma ".$this->properties['gamma'];
				$va_ops['convert'][] = "-set reference-black ".$this->properties['reference-black'];
				$va_ops['convert'][] = "-set reference-white ".$this->properties['reference-white'];
			}
			
			$vs_input_file = $pa_handle['filepath'];
			if (is_array($va_ops['convert']) && sizeof($va_ops['convert'])) {
				if (!is_null($pn_quality)) {
					array_unshift($va_ops['convert'], '-quality '.intval($pn_quality));
				}
				
				if($this->properties["colorspace"] === 'CMYK') {
					array_unshift($va_ops['convert'], '-colorspace RGB');
				}
				caExec( join( ' ', array(
					$this->commandWithDefaultArgs( 'convert' ),
					caEscapeShellArg( $vs_input_file . '[0]' ),
					join( ' ', $va_ops['convert'] ),
					caEscapeShellArg( $ps_filepath ) . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
				) ) );
				$vs_input_file = $ps_filepath;
			}
			if (is_array($va_ops['composite'] ?? null) && sizeof($va_ops['composite'])) {
				caExec( join( ' ', array(
					$this->commandWithDefaultArgs( 'composite' ),
					join( ' ', $va_ops['composite'] ),
					caEscapeShellArg( $vs_input_file . '[0]' ),
					caEscapeShellArg( $ps_filepath ) . ( caIsPOSIX() ? ' 2> /dev/null' : '' )
				) ) );
			}	
			
			return true;
		}
		return null;
	}
	# ------------------------------------------------
}
