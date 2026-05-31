<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Media/Panorama.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugMedia.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaPanorama Extends BaseMediaPlugin implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	
	var $opo_config;
	var $opo_search_config;
	var $ops_ghostscript_path;
	var $ops_pdftotext_path;
	
	var $ops_imagemagick_path;
	var $ops_graphicsmagick_path;
	
	var $metadata = [];
	
	var $info = array(
		"IMPORT" => array(
			"application/orbitvu" 				=> "zip"
		),
		
		"EXPORT" => array(
			"application/panorama" 				=> "zip"
		),
		
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing"),
			'CROP' 				=> array('width', 'height', 'x', 'y'),					// dummy
			"SHARPEN"			=> ['radius', 'sigma'], 								// dummy
			"ANNOTATE"			=> array("text", "font", "size", "color", "position", "inset"),	// dummy
			"WATERMARK"			=> array("image", "width", "height", "position", "opacity"),	// dummy
			'ROTATE' 			=> array('angle'),										// dummy
			'FLIP'				=> array('direction'),									// dummy
			'MEDIAN'			=> array('radius'),										// dummy
			'DESPECKLE'			=> array(''),											// dummy
			'UNSHARPEN_MASK'	=> array('radius', 'sigma', 'amount', 'threshold'),		// dummy
			"SET" 				=> array("property", "value")
		),
		
		"PROPERTIES" => array(
			"width" 			=> 'W', # in pixels
			"height" 			=> 'W', # in pixels
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"mimetype" 			=> 'W',
			"quality"			=> 'W',
			"colorspace"		=> 'W',
			"resolution"		=> 'W', # resolution of graphic in pixels per inch
			"filesize" 			=> 'R',
			"antialiasing"		=> 'W', # amount of antialiasing to apply to final output; 0 means none, 1 means lots; a good value is 0.5
			"crop"				=> 'W', # if set to geometry value (eg. 72x72) image will be cropped to those dimensions; set by transform() to support fill_box SCALE mode 
			"crop_from"			=> 'W', # location to calculate crop area from
			"crop_center_x"		=> 'W',
			"crop_center_y"		=> 'W',
			"target_width"		=> 'W',
			"target_height"		=> 'W',
			"colors"			=> 'W', # number of colors in output PNG-format image; default is 256
			
			"pano_filepath"		=> 'W', # path to original pano file
			"pano_mimetype"		=> 'W',	# panorama mimetype
			"pano_files"		=> 'W', # list of panorama file paths
			
			
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "Panorama",
		
		"MULTIPAGE_CONVERSION" => false, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => false
	);
	
	var $typenames = array(
		"application/orbitvu" 	=> "Orbitvu",
		"application/panorama"	=> "Panorama",
	);
	
	var $magick_names = array(
		"application/orbitvu" 		=> "Orbitvu",
		"application/panorama" 		=> "Panorama",
	);
	
	#
	# Alternative extensions for supported types
	#
	var $alternative_extensions = [];
	
	/**
	 * Per-request caching of information extracted from read files
	 */
	static $s_info_cache = [];
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Converts proprietary 360 panorama file formats into a generic panorama viewable in CollectiveAccess.');
	}
	# ------------------------------------------------
	/**
	 * What sort media does this plug-in support for import and export
	*/ 
	public function register() {
		if(!class_exists('ZipArchive')) {	// requires ZipArchive extension
			return false;
		}
		$this->opo_config = Configuration::load();
		
		$this->ops_imagemagick_path = caMediaPluginImageMagickInstalled();
		$this->ops_graphicsmagick_path = caMediaPluginGraphicsMagickInstalled();
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function checkStatus() {
		$status = parent::checkStatus();

		if ($this->register()) {
			$status['available'] = true;
		} else {
			$status['available'] = false;
		}

		return $status;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function divineFileFormat($filepath) {
		if ($filepath == '') {
			return '';
		}
		
		if(isset(self::$s_info_cache[$filepath])) { return self::$s_info_cache[$filepath]; }
		$zip = new ZipArchive();
		if ($zip->open($filepath) === true) {
			$has_content = $has_config = false;
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$p = $zip->getNameIndex($i);
				$f = pathinfo($p, PATHINFO_BASENAME);
				switch($f) {
					case 'content2.xml':
						$has_content = true;
						break;
					case 'config.xml':
						$has_config = true;
						break;
				}
			}
			$zip->close();
			if($has_content && $has_config) {
				self::$s_info_cache[$filepath] = 'application/orbitvu';
				return 'application/orbitvu';
			}
		} 
		return self::$s_info_cache[$filepath] = null;
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property] ?? null) {
				return $this->properties[$property] ?? null;
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function set($property, $value) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				switch($property) {
					case 'quality':
						if (($value < 1) || ($value > 100)) {
							$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugPanorama->set()");
							return '';
						}
						$this->properties["quality"] = $value;
						break;
					case 'antialiasing':
						if (($value < 0) || ($value > 100)) {
							$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugPanorama->set()");
							return '';
						}
						$this->properties["antialiasing"] = $value;
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
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugPanorama->set()");
				return '';
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
	# ------------------------------------------------
	/**
	 *
	 */
	public function read($filepath, $mimetype="", $options=null) {
		$zip = new ZipArchive();
		
		$mimetype = $this->divineFileFormat($filepath);
		$metadata = $pano_config = [];
		
		$this->properties['pano_mimetype'] = $this->properties['mimetype'] = $mimetype;
		$this->properties['resolution'] = 72;
		$this->properties['pano_filepath'] = $filepath;
		switch($mimetype) {
			case 'application/orbitvu':
				if ($zip->open($filepath) === true) {
					$files = [];
					for ($i = 0; $i < $zip->numFiles; $i++) {
						$p = $zip->getNameIndex($i);
						$f = pathinfo($p, PATHINFO_BASENAME);
						switch($f) {
							case 'content2.xml':
								$content = $zip->getFromName($p);
								if($c = new SimpleXMLElement($content)) {
									foreach($c->properties->property as $x) {
										$attr = $x->attributes();
										$metadata[(string)$attr['name']] = (string)$x;
									}
									
									$j = 0;
									foreach($c->sequence3d->scales->scale as $x) {
										$attr = $x->attributes();
										
										foreach(['rows', 'cols', 'width', 'height', 'tile_width', 'tile_height', 'value'] as $k) {
											$metadata['scales'][$j][$k] = (string)$attr[$k];
										}
										$j++;
									}
									
									$j=0;
									foreach($c->sequence3d->images as $x) {
										$attr = $x->attributes();
										
										foreach(['ext', 'maxWidth', 'maxHeight', 'name'] as $k) {
											$metadata['images'][$j][$k] = (string)$attr[$k];
										}
										$j++;
									}
								}
								break;
							case 'config.xml':
								$content = $zip->getFromName($p);
								if($c = new SimpleXMLElement($content)) {
									foreach($c->{'viewer-params'}->children() as $x) {
										$pano_config[(string)$x->getName()] = (string)$x;
									}
								}
								break;
							default:
								$files[] = $p;
								break;
						}
					}
					
					// Get res
					// @TODO get highest resolution images and stitch together as needed.
					$cscale = null;
					foreach($metadata['scales'] as $scale) {
						if(!$cscale && ($scale['height'] <= 1024)) { $cscale = $scale; continue; }
						
						if(($scale['height'] > $cscale['height']) && ($scale['height'] <= 1024)) {
							$cscale = $scale;
						}
					}
					
					$value = str_replace('.', '', $cscale['value']);
					
					// filter files using content settings
					$ext = preg_replace("![^\dA-Za-z]+!", "", $metadata['images'][0]['ext'] ?? 'jpg');
					$name = preg_replace("![^\dA-Za-z_\-\.]+!", "", $metadata['images'][0]['name'] ?? '');
					$files = array_values(array_filter($files, function($v) use ($ext, $value, $name) {
						return preg_match("!^{$name}[\d]+_[\d]+_{$value}_[\d]+_[\d]+\.{$ext}$!i", pathinfo($v, PATHINFO_BASENAME));
					}));
					
					$sorted_files = [];
					foreach($files as $f) {
						if(preg_match("!^{$name}[\d]+_([\d]+)_{$value}_[\d]+_[\d]+\.{$ext}$!i", pathinfo($f, PATHINFO_BASENAME), $m)) {
							$sorted_files[(int)$m[1]] = $f;
						}
					}
					ksort($sorted_files);
					
					$this->properties['pano_files'] = $sorted_files;
					$this->properties['width'] = $cscale['width'];
					$this->properties['height'] = $cscale['height'];
					
					$this->metadata = $metadata;
					
					$this->handle = $this->ohandle = [
						'zip' => $zip,
						'properties' => $this->properties,
						'transformations' => []
					];
					//$zip->close();
					return true;
				} 
				break;
			default:
				$this->handle = $this->ohandle = null;
				throw new ApplicationException(_t('Cannot read: invalid panorama type'));
				break;
		}
		return $this->handle = $this->ohandle = null;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function transform($operation, $parameters) {
		if (!$this->handle) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugPanorama->transform()");
			return false;
		}
		
		$this->handle['transformations'][] = [
			'operation' => $operation,
			'parameters' => $parameters
		];
		
		# get parameters for this operation
		$this->properties["version_width"] = $w = $parameters["width"] ?? null;
		$this->properties["version_height"] = $h = $parameters["height"] ?? null;
		$cw = $this->get("width");
		$ch = $this->get("height");
		switch($operation) {
			# -----------------------
			case "SET":
				foreach($parameters as $k => $v){
					$this->set($k, $v);
				}
				break;
			# -----------------------
			case "SCALE":
				$width_ratio = $w/$cw;
				$height_ratio = $h/$ch;
				$orig_resolution = $this->get("resolution");
				switch($parameters["mode"]) {
					# ----------------
					case "width":
						$resolution = ceil($orig_resolution * $width_ratio);
						$scaling_correction = $w/ceil($resolution * ($cw/$orig_resolution));
						break;
					# ----------------
					case "height":
						$resolution = ceil($orig_resolution * $height_ratio);
						$scaling_correction = $h/ceil($resolution * ($ch/$orig_resolution));
						break;
					# ----------------
					case "fill_box":
						$crop_from = $parameters["crop_from"] ?? null;
						if (!in_array($crop_from, array('center', 'north_east', 'north_west', 'south_east', 'south_west', 'random'))) {
							$crop_from = '';
						}
						
						if ($width_ratio < $height_ratio) {
							$resolution = ceil($orig_resolution * $width_ratio);
							$scaling_correction = $w/ceil($resolution * ($cw/$orig_resolution));
						} else {
							$resolution = ceil($orig_resolution * $height_ratio);
							$scaling_correction = $h/ceil($resolution * ($ch/$orig_resolution));
						}
						$this->set("crop",$w."x".$h);
						$this->set("crop_from",$crop_from);
						$this->set("crop_center_x", caGetOption('_centerX', $parameters, 0.5));
						$this->set("crop_center_y", caGetOption('_centerY', $parameters, 0.5));
						break;
					# ----------------
					case "bounding_box":
					default:
						if ($width_ratio > $height_ratio) {
							$resolution = ceil($orig_resolution * $height_ratio);
							$scaling_correction = $h/ceil($resolution * ($ch/$orig_resolution));
						} else {
							$resolution = ceil($orig_resolution * $width_ratio);
							$scaling_correction = $w/ceil($resolution * ($cw/$orig_resolution));
						}
						break;
					# ----------------
				}
				
				$this->properties["scaling_correction"] = $scaling_correction;
				
				$this->properties["resolution"] = $resolution;
				$this->properties["width"] = ceil($resolution * ($cw/$orig_resolution));
				$this->properties["height"] = ceil($resolution * ($ch/$orig_resolution));
				$this->properties["target_width"] = $w;
				$this->properties["target_height"] = $h;
				$this->properties["antialiasing"] = ($parameters["antialiasing"]) ? 1 : 0;
				break;
			# -----------------------
		}
		return true;
	}
	# ----------------------------------------------------------
	/**
	 * @param array $options Options include:
	 *		dontUseDefaultIcons = If set to true, write will fail rather than use default icons when preview can't be generated. Default is false – to use default icons.
	 *		writeAllPages = 
	 *		start = 
	 *		numPages = 
	 */
	public function write($filepath, $mimetype, $options=null) {
		if (!$this->handle) { return false; }
		
		$input_mimetype = $this->get('pano_mimetype');
		switch($input_mimetype) {
			case 'application/orbitvu':
				$zip = $this->handle['zip'];
				if($mimetype === 'application/panorama') {
					copy($this->properties['pano_filepath'], "{$filepath}.zip");
				} elseif(caGetMediaClass($mimetype) === 'image') {
					// @TODO: error checking
					$files = $this->get('pano_files');
					
					$target_path = caGetTempFileName('panorama');
					$zip->extractTo($target_path, $files[0]);
					$m = new Media();
					$m->read($target_path.'/'.$files[0]);
					
					foreach($this->handle['transformations'] as $t) {
						$m->transform($t['operation'], $t['parameters']);
					}
					
					$f = $m->write($filepath, $mimetype);
					return $filepath;
				} else {
					throw new ApplicationException(_t('Cannot convert Orbitvu to specified type (%1)', $mimetype));
				}
				break;
			default:
				throw new ApplicationException(_t('Cannot write: invalid panorama type'));
				break;
		}
		
		return '';
	}
	# ------------------------------------------------
	/** 
	 * Options:
	 *		width
	 *		height
	 *		numberOfPages
	 *		pageInterval
	 *		startAtPage
	 *		outputDirectory
	 *		force = ignore setting of "document_preview_generate_pages" app.conf directive and generate previews no matter what
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($filepath, $options) {
		$files = $this->get('pano_files');
		$output = [];
		
		$zip = $this->handle['zip'];
		$target_path = caGetTempFileName('panorama');
		foreach($files as $f) {
			$zip->extractTo($target_path, $f);
			$output[] = "{$target_path}/{$f}";
		}
		return $output;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getOutputFormats() {
		return $this->info["EXPORT"];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getTransformations() {
		return $this->info["TRANSFORMATIONS"];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function mimetype2extension($mimetype) {
		return $this->info["EXPORT"][$mimetype] ?? null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype] ?? null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
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
	/**
	 *
	 */
	public function reset() {
		return $this->init();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function init() {
		$this->errors = [];
		$this->handle = $this->ohandle;
		$this->properties = $this->ohandle['properties'];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function htmlTag($url, $properties, $options=null, $volume_info=null) {
		if (!is_array($options)) { $options = array(); }
		//print "url=$url\n";die;
		foreach(array(
			'name', 'url', 'viewer_width', 'viewer_height', 'idname',
			'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style',
			'embed'
		) as $vs_k) {
			if (!isset($options[$vs_k])) { $options[$vs_k] = null; }
		}
		
		if(preg_match("/\.zip\$/", $url)) {
			
			return "";
		} else {
			if (!is_array($options)) { $options = array(); }
			if (!is_array($properties)) { $properties = array(); }
			return caHTMLImage($url, array_merge($options, $properties));
		}
	}
	# ------------------------------------------------
	#
	# ------------------------------------------------
	/**
	 *
	 */
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
