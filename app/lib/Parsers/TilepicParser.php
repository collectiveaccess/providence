<?php
/** ---------------------------------------------------------------------
 * app/lib/Parsers/TilepicParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2023 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/Utils/Timer.php");
include_once(__CA_LIB_DIR__."/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

define("LIBRARY_GD", 0);
define("LIBRARY_IMAGEMAGICK",2);
define("LIBRARY_IMAGICK",3);
define("LIBRARY_GMAGICK",5);
define("LIBRARY_GRAPHICSMAGICK",6);

class TilepicParser {
	var $error = "";
	var $properties = array();
	var $fh = "";
	
	#
	# Supported tile types
	#
	var $mimetype2magick = array(
			"image/gif" 	=> "GIF",
			"image/jpeg"	=> "JPEG",
			"image/png"		=> "PNG",
			"image/tiff"	=> "TIFF"
	);
	var $mimetype2ext = array(
			"image/gif" 	=> "gif",
			"image/jpeg"	=> "jpg",
			"image/png"		=> "png",
			"image/tiff"	=> "tiff"
	);
	
	var $magick_names = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "BMP",
		"image/x-psd" 		=> "PDF",
		"image/tilepic" 	=> "TPC",
		"image/x-dcraw"		=> "RAW"
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
	
	var $debug = false;
	
	var $backend;
	
	var $opo_config;
	var $ops_imagemagick_path;
	var $ops_graphicsmagick_path;
	
	# ------------------------------------------------------------------------------------
	function __construct($filename="") {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		
		$this->ops_imagemagick_path = caMediaPluginImageMagickInstalled();
		$this->ops_graphicsmagick_path = caMediaPluginGraphicsMagickInstalled();
		
		// edit ranking of preferred backends for tilepic processing here

		// TODO: maybe put this in a config file and make it consistent with
		// what the main media processing engine "choses"?
		
		$va_backend_ranking = array(
			LIBRARY_GMAGICK => caMediaPluginGmagickInstalled(),
			LIBRARY_IMAGICK => caMediaPluginImagickInstalled(),
			LIBRARY_GRAPHICSMAGICK => (bool)$this->ops_graphicsmagick_path,
			LIBRARY_IMAGEMAGICK => (bool)$this->ops_imagemagick_path,
			LIBRARY_GD => true, // one available back-end has to be assumed
		);
		
		foreach($va_backend_ranking as $vn_backend => $vb_available){
			if($vb_available){
				$this->backend = $vn_backend;
				break;
			}
		}
		
		if ($this->debug) print "TilePic processing backend is {$this->backend}";
		
		if ($filename) { $this->load($filename); }
	}
	# ------------------------------------------------------------------------------------
	function init () {
		$this->error = "";
		$this->properties = array();
		
		if ($this->fh) { fclose($this->fh); }
		$this->fh = "";
		
		return 1;
	}
	# ------------------------------------------------------------------------------------
	function useLibrary($vn_backend) {
		switch($vn_backend) {
			case LIBRARY_GD:
				$this->backend = LIBRARY_GD;
				break;
			case LIBRARY_IMAGICK:
				$this->backend = LIBRARY_IMAGICK;
				break;
			case LIBRARY_GMAGICK:
				$this->backend = LIBRARY_GMAGICK;
				break;
			case LIBRARY_GRAPHICSMAGICK:
				$this->backend = LIBRARY_GRAPHICSMAGICK;
				break;
			default:
				$this->backend = LIBRARY_IMAGEMAGICK;
				break;
		}
	}
	# ------------------------------------------------------------------------------------
	#
	# Format check
	#
	# ------------------------------------------------------------------------------------
	function isTilepic($ps_filepath) {
		if ($fh = @fopen($ps_filepath,'r')) {
			// --------
			// -- Read header
			// --------
			$header = fread ($fh, 4);
			fclose($fh);
			if (preg_match("/TPC\n/", $header)) {
				# the first four characters of a Tilepic file are TPN\n
				return "image/tilepic";
			} else {
				return "";
			}
		} else {
			# file format is not supported by this plug-in
			return "";
		}
	}
	# ------------------------------------------------------------------------------------
	#
	# Tilepic loader (must call this before accessing file)
	#
	# ------------------------------------------------------------------------------------
	function load ($ps_filepath) {
		$this->init();
		
		if (is_array($ps_filepath)) {
			$this->properties = $ps_filepath;
			return 1;
		}
		
		//$t= new Timer();
		if ($this->fh = $fh = @fopen($ps_filepath,'r')) {
			# look for signature
			$sig = fread ($fh, 4);
			if (preg_match("/TPC\n/", $sig)) {
				$buf = fread($fh, 4);
				$x = unpack("Nheader_size", $buf);
				if ($x['header_size'] <= 8) { 
					$this->error = "Tilepic header length is invalid";
					return false;
				}
				$this->properties['filepath'] = $ps_filepath;
				
				$this->properties['header_size'] = $x['header_size'];
				
				$header = fread($fh, $x['header_size'] - 8);
				$header_values = unpack("Nwidth/Nheight/Ntile_width/Ntile_height/Ntiles/nlayers/nratio/Nattributes_size/Nattributes", $header);
				
				# --- Check header values
				if (($header_values["width"] < 1) || ($header_values["height"] < 1) || ($header_values["tile_width"] < 1) || ($header_values["tile_height"] < 1) ||
					($header_values["tiles"] < 1) || ($header_values["layers"] < 1)) {
					$this->error = "Tilepic header is invalid";
					return false;
				}
				
				foreach (array_keys($header_values) as $k) {
					$this->properties[$k] = $header_values[$k];
				}
				
				# --- get tile offsets (start of each tile)
				$tile_offsets = array();
				for ($i=0; $i < $header_values['tiles']; $i++) {
					$x = unpack("Noffset", fread($fh, 4)); 
					$tile_offsets[] = $x['offset'];
				}
				$this->properties['tile_offsets'] = $tile_offsets;
				
				# --- get attribute data
				$buf = fread($fh, 4);
				$x = unpack("Nattribute_offset", $buf);
				$this->properties['attribute_offset'] = $attribute_offset = $x['attribute_offset'];
				if (fseek($fh, $attribute_offset, 0) == -1) {
					$this->error = "Seek error while fetch attributes";
					return false;
				}
				
				$attribute_data = fread($fh, filesize($ps_filepath) - $attribute_offset);
				
				$attribute_list = explode("\0", $attribute_data);
				$attributes = array();
				foreach ($attribute_list as $attr) {
					if ($attr = trim($attr)) {
						$x = explode("=", $attr);
						$attributes[$x[0]] = $x[1];
						
						if (preg_match("/^mimetype\$/i", $x[0])) {
							$this->properties["tile_mimetype"] = $x[1];
						}
					}
				}
				$this->properties["attributes"] = $attributes;
				
				//error_log("Tilepic load took " . $t->getTime()." seconds");
				
				return 1;
			} else {
				$this->error = "File is not Tilepic format";
				return false;
			}
		} else {
			$this->error = "Couldn't open file $ps_filepath";
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	function getProperties() {
		return $this->properties;
	}
	# ------------------------------------------------------------------------------------
	#
	# Tilepic creation methods
	#
	# ------------------------------------------------------------------------------------
	function encode ($ps_filepath, $ps_output_path, $pa_options) {
		#
		# Default values for options
		#
		if (($pa_options["layer_ratio"] = (isset($pa_options["layer_ratio"])) ? $pa_options["layer_ratio"] : 2) <= 0) { $pa_options["layer_ratio"] = 2; }
		
		if (($pa_options["scale_factor"] = (isset($pa_options["scale_factor"])) ? $pa_options["scale_factor"] : 1) <= 0) { $pa_options["scale_factor"] = 1; }
		
		if (($pa_options["quality"] = (isset($pa_options["quality"])) ? $pa_options["quality"] : 75) < 1) { $pa_options["quality"] = 75; }
		if (isset($pa_options["layers"])) {
			if (($pa_options["layers"] < 1) || ($pa_options["layers"] > 100)) {
				$pa_options["layers"] = 6;
			}
		}
		
		if (($pa_options["antialiasing"] = (isset($pa_options["antialiasing"])) ? $pa_options["antialiasing"] : 1) < 0) { $pa_options["antialiasing"] = 1; }
		
		
		if (isset($pa_options["tile_size"])) {
			$pa_options["tile_width"] = $pa_options["tile_height"] = $pa_options["tile_size"];
		}
		
		if (($pa_options["tile_width"] < 10) || ($pa_options["tile_height"] < 10)) {
			$pa_options["tile_width"] = $pa_options["tile_height"] = 256;
		}
		
		if (($pa_options["layers"] < 1) || ($pa_options["layers"] > 25)) {
			$pa_options["layers"] = 0;
		}
		
		if (!$pa_options["output_mimetype"]) {
			$pa_options["output_mimetype"] = "image/jpeg";
		}
		
		switch($this->backend) {
			case LIBRARY_GD:
				return $this->encode_gd($ps_filepath, $ps_output_path, $pa_options);
				break;
			case LIBRARY_IMAGICK:
				return $this->encode_imagick($ps_filepath, $ps_output_path, $pa_options);
				break;
			case LIBRARY_GRAPHICSMAGICK:
				return $this->encode_graphicsmagick($ps_filepath, $ps_output_path, $pa_options);
				break;
			case LIBRARY_GMAGICK:
				return $this->encode_gmagick($ps_filepath, $ps_output_path, $pa_options);
				break;
			default:
				return $this->encode_imagemagick($ps_filepath, $ps_output_path, $pa_options);
				break;
		}
	}
	# ------------------------------------------------
	private function _imageMagickRead($ps_filepath) {
		if ($this->ops_imagemagick_path) {
			caExec($this->ops_imagemagick_path.' -format "%m;%w;%h\n" '.caEscapeShellArg($ps_filepath).(caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
	
			$va_tmp = explode(';', $va_output[0]);
			if (sizeof($va_tmp) != 3) {
				return null;
			}
			
			return array(
				'mimetype' => $this->magickToMimeType($va_tmp[0]),
				'magick' => $va_tmp[0],
				'width' => $va_tmp[1],
				'height' => $va_tmp[2],
				'ops' => array(),
				'filepath' => $ps_filepath
			);
		}
		return null;
	}
	# ------------------------------------------------
	private function _graphicsMagickRead($ps_filepath) {
		if ($this->ops_graphicsmagick_path) {
			caExec($this->ops_graphicsmagick_path.' identify -format "%m;%w;%h\n" '.caEscapeShellArg($ps_filepath).(caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
			$va_tmp = explode(';', $va_output[0]);
			if (sizeof($va_tmp) != 3) {
				return null;
			}
			
			return array(
				'mimetype' => $this->magickToMimeType($va_tmp[0]),
				'magick' => $va_tmp[0],
				'width' => $va_tmp[1],
				'height' => $va_tmp[2],
				'ops' => array(),
				'filepath' => $ps_filepath
			);
		}
		return null;
	}
	# ------------------------------------------------
	private function _imageMagickProcess($ps_source_filepath, $ps_dest_filepath, $pa_ops, $pn_quality=null) {
		$va_ops = [];
		if (!is_null($pn_quality)) {
			$va_ops[] = '-quality '.intval($pn_quality);
		}
		
		foreach($pa_ops as $va_op) {
			switch($va_op['op']) {
				case 'size':
					if ($va_op['width'] < 1) { break; }
					if ($va_op['height'] < 1) { break; }
					$va_ops[] = '-resize '.$va_op['width'].'x'.$va_op['height'].' -filter Cubic';
					break;
				case 'crop':
					if ($va_op['width'] < 1) { break; }
					if ($va_op['height'] < 1) { break; }
					if ($va_op['x'] < 0) { break; }
					if ($va_op['y'] < 0) { break; }
					$va_ops[] = '-crop '.$va_op['width'].'x'.$va_op['height'].'+'.$va_op['x'].'+'.$va_op['y'];
					break;
				case 'rotate':
					if (!is_numeric($va_op['angle'])) { break; }
					$va_ops[] = '-rotate '.$va_op['angle'];
					break;
				case 'filter_despeckle':
					$va_ops[] = '-despeckle';
					break;
				case 'filter_sharpen':
					if ($va_op['radius'] < 0) { break; }
					$vs_tmp = '-sharpen '.$va_op['radius'];
					if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
					$va_ops[] = $vs_tmp;
					break;
				case 'filter_median':
					if ($va_op['radius'] < 0) { break; }
					$va_ops[] = '-median '.$va_op['radius'];
					break;
				case 'filter_unsharp_mask':
					if ($va_op['radius'] < 0) { break; }
					$vs_tmp = '-unsharp '.$va_op['radius'];
					if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
					if (isset($va_op['amount'])) { $vs_tmp .= '+'.$va_op['amount'];}
					if (isset($va_op['threshold'])) { $vs_tmp .= '+'.$va_op['threshold'];}
					$va_ops[] = $vs_tmp;
					break;
				case 'strip':
					$va_ops[] = '-strip';
					break;
			}
		}
		caExec(str_replace('identify', 'convert', $this->ops_imagemagick_path).' '.caEscapeShellArg($ps_source_filepath.'[0]').' '.join(' ', $va_ops).' "'.$ps_dest_filepath.'"');
		return true;
	}
	# ------------------------------------------------
	private function _graphicsMagickProcess($ps_source_filepath, $ps_dest_filepath, $pa_ops, $pn_quality=null) {
		$va_ops = [];
		if (!is_null($pn_quality)) {
			$va_ops[] = '-quality '.intval($pn_quality);
		}
		
		foreach($pa_ops as $va_op) {
			switch($va_op['op']) {
				case 'size':
					if ($va_op['width'] < 1) { break; }
					if ($va_op['height'] < 1) { break; }
					$va_ops[] = '-resize '.$va_op['width'].'x'.$va_op['height'].' -filter Cubic';
					break;
				case 'crop':
					if ($va_op['width'] < 1) { break; }
					if ($va_op['height'] < 1) { break; }
					if ($va_op['x'] < 0) { break; }
					if ($va_op['y'] < 0) { break; }
					$va_ops[] = '-crop '.$va_op['width'].'x'.$va_op['height'].'+'.$va_op['x'].'+'.$va_op['y'];
					break;
				case 'rotate':
					if (!is_numeric($va_op['angle'])) { break; }
					$va_ops[] = '-rotate '.$va_op['angle'];
					break;
				case 'filter_despeckle':
					$va_ops[] = '-despeckle';
					break;
				case 'filter_sharpen':
					if ($va_op['radius'] < 0) { break; }
					$vs_tmp = '-sharpen '.$va_op['radius'];
					if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
					$va_ops[] = $vs_tmp;
					break;
				case 'filter_median':
					if ($va_op['radius'] < 0) { break; }
					$va_ops[] = '-median '.$va_op['radius'];
					break;
				case 'filter_unsharp_mask':
					if ($va_op['radius'] < 0) { break; }
					$vs_tmp = '-unsharp '.$va_op['radius'];
					if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
					if (isset($va_op['amount'])) { $vs_tmp .= '+'.$va_op['amount'];}
					if (isset($va_op['threshold'])) { $vs_tmp .= '+'.$va_op['threshold'];}
					$va_ops[] = $vs_tmp;
					break;
				case 'strip':
					// option
					//$va_ops[] = '-strip';
					$va_ops[] = '+profile "*"';
					break;
			}
		}
		caExec($this->ops_graphicsmagick_path.' convert '.caEscapeShellArg($ps_source_filepath.'[0]').' '.join(' ', $va_ops).' "'.$ps_dest_filepath.'"');
		return true;
	}
	# ------------------------------------------------
	private function _imageMagickImageFromTiles($ps_dest_filepath, $pa_tiles, $pn_tile_width, $pn_tile_height) {
		
		caExec(str_replace('identify', 'montage', $this->ops_imagemagick_path).' '.join(' ', $pa_tiles).' -mode Concatenate -tile '.$pn_tile_width.'x'.$pn_tile_height.' "'.$ps_dest_filepath.'"');
	
		return true;
	}
	# ------------------------------------------------
	private function _graphicsMagickImageFromTiles($ps_dest_filepath, $pa_tiles, $pn_tile_width, $pn_tile_height) {
		caExec($this->ops_graphicsmagick_path.' montage '.join(' ', $pa_tiles).' -mode Concatenate -tile '.$pn_tile_width.'x'.$pn_tile_height.' "'.$ps_dest_filepath.'"');
		return true;
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
	public function appleTypeToMimeType($ps_apple_type) {
		foreach($this->apple_type_names as $vs_mimetype => $vs_apple_type) {
			if ($ps_apple_type == $vs_apple_type) {
				return $vs_mimetype;
			}
		}
		return null;
	}
	# ------------------------------------------------------------------------------------
	function encode_imagemagick ($ps_filepath, $ps_output_path, $pa_options) {
		if (!($vs_tilepic_tmpdir = $this->opo_config->get('tilepic_tmpdir'))) {
			$vs_tilepic_tmpdir = caGetTempDirPath();
		}
		if (!($magick = $this->mimetype2magick[$pa_options["output_mimetype"]])) {
			$this->error = "Invalid output format";
			return false;
		}
		
		#
		# Open image
		#
		$h = $this->_imageMagickRead($ps_filepath);
        if (!$h) {
			$this->error = "Couldn't open image $ps_filepath";
			return false;
        }
        
        $vs_filepath = $ps_filepath;
		
        $image_width = 	$h['width'];
        $image_height = $h['height'];
        if (($image_width < 10) || ($image_height < 10)) {
        	$this->error = "Image is too small to be output as Tilepic; minimum dimensions are 10x10 pixels";
			return false;
        }
        
        if ($pa_options["scale_factor"] != 1) {
        	$image_width *= $pa_options["scale_factor"];
        	$image_height *= $pa_options["scale_factor"];
			
			$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_scale_');
			$vs_tmp_fname = $vs_tmp_basename.'.jpg';
			if (!($this->_imageMagickProcess($vs_filepath, $vs_tmp_fname, array(
					array(
						'op' => 'size',
						'width' => $image_width,
						'height' => $image_height,
					)
				)
			))) {
				$this->error = "Couldn't scale image";
				@unlink($vs_tmp_fname);
				return false;
			}
			$vs_filepath = $vs_tmp_fname;
        }
        
         if(function_exists('exif_read_data') && !($this->opo_config->get('dont_use_exif_read_data'))) {
			if (is_array($va_exif = @exif_read_data($ps_filepath, 'IDF0', true, false))) { 
				if (isset($va_exif['IFD0']['Orientation'])) {
					$vn_orientation_rotate = null;
					$vn_orientation = $va_exif['IFD0']['Orientation'];
					switch($vn_orientation) {
						case 3:
							$vn_orientation_rotate = 180;
							break;
						case 6:
							$vn_orientation_rotate = 90;
							break;
						case 8:
							$vn_orientation_rotate = -90;
							break;
					}
					
					if ($vn_orientation_rotate) {
						$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_rotate_');
						$vs_tmp_fname = $vs_tmp_basename.'.jpg';
						if (!($this->_imageMagickProcess($vs_filepath, $vs_tmp_fname, array(
								array(
									'op' => 'rotate',
									'angle' => $vn_orientation_rotate
								)
							)
						))) {
							$this->error = "Couldn't rotate image";
							@unlink($vs_tmp_fname);
							return false;
						}
						
						if (in_array($vn_orientation_rotate, array(90, -90))) {
							$vn_tmp = $image_width;
							$image_width = $h['width'] = $image_height;
							$image_height = $h['height'] = $vn_tmp;
						}
						$vs_filepath = $vs_tmp_fname;
					}
				}
			}
		}
        
		#
		# How many layers to make?
		#
		if (!$pa_options["layers"]) {
			$sw = $image_width * $pa_options["layer_ratio"];
			$sh = $image_height * $pa_options["layer_ratio"];
			$pa_options["layers"] = 1;
			while (($sw >= $pa_options["tile_width"]) || ($sh >= $pa_options["tile_height"])) {
				$sw = ceil($sw / $pa_options["layer_ratio"]);
				$sh = ceil($sh / $pa_options["layer_ratio"]);
				$pa_options["layers"] ++;
			}
		}
		
		#
		# Cut image into tiles
		#
		$tiles = 0;
		$layer_list = array();
		$base_width = $image_width;
		$base_height = $image_height;
		
		if ($this->debug) { print "BASE $base_width x $base_height \n";}
		for($l=$pa_options["layers"]; $l >= 1; $l--) {
			
			$x = $y = 0;
			$wx = $pa_options["tile_width"];
			$wy = $pa_options["tile_height"];
			
			if ($this->debug) { print "LAYER=$l\n"; };
			if ($l < $pa_options["layers"]) {
				$image_width = ceil($image_width/$pa_options["layer_ratio"]);
				$image_height = ceil($image_height/$pa_options["layer_ratio"]);
				if ($this->debug) { print "RESIZE layer $l TO $image_width x $image_height \n";}
				
				
				$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_layer_scale_');
				$vs_tmp_fname = $vs_tmp_basename.'.jpg';
				if (!($this->_imageMagickProcess($vs_filepath, $vs_tmp_fname, array(
						array(
							'op' => 'size',
							'width' => $image_width,
							'height' => $image_height,
						)
					)
				))) {
					$this->error = "Couldn't scale image";
					@unlink($vs_tmp_fname);
					return false;
				}
				if ($vs_filepath != $ps_filepath) { @unlink($vs_filepath); }
				$vs_filepath = $vs_tmp_fname;
			}
		
			$i = 0;
			
			$layer_list[] = array();
			while($y < $image_height) {
				$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_tile_');
				$vs_tmp_fname = $vs_tmp_basename.'.jpg';
				if (!($this->_imageMagickProcess($vs_filepath, $vs_tmp_fname, array(
						array(
							'op' => 'crop',
							'width' => $wx,
							'height' => $wy,
							'x' => $x,
							'y' => $y
						), 
						array(
							'op' => 'strip'
						)
					),
					$pa_options["quality"]
				))) {
					$this->error = "Couldn't tile image";
					@unlink($vs_tmp_fname);
					@unlink($vs_tmp_basename);
					return false;
				}
				
				$vs_tile = file_get_contents($vs_tmp_fname);
				@unlink($vs_tmp_fname);
				@unlink($vs_tmp_basename);
				
				$layer_list[sizeof($layer_list)-1][] = $vs_tile;
				$x += $pa_options["tile_width"];
				
				if ($x >= $image_width) {
					$y += $pa_options["tile_height"];
					$x = 0;
				}
				
				$i++;
				$tiles++;
				
			}
			if ($this->debug) { print "OUTPUT $tiles TILES FOR LAYER $l : $image_width x $image_height\n";}
		}
		if ($vs_filepath != $ps_filepath) { @unlink($vs_filepath); }
		
		#
		# Write Tilepic format file
		#
		if ($this->debug) { print "WRITING FILE..."; }
		if ($fh = fopen($ps_output_path.".tpc", "w")) {
			# --- attribute list
			$attribute_list = "";
			$attributes = 0;
			
			if ((isset($pa_options["attributes"])) && (is_array($pa_options["attributes"]))) {
				$pa_options["attributes"]["mimeType"] = $pa_options["output_mimetype"];
			} else {
				$pa_options["attributes"] = array("mimeType" => $pa_options["output_mimetype"]);
			}
			foreach ($pa_options["attributes"] as $k => $v) {
				$attribute_list .= "$k=$v\0";
				$attributes++;
			}
			
			if ($this->debug) { print "header OK;"; }
			# --- header
			if (!fwrite($fh, "TPC\n")) {
				$this->error = "Could not write Tilepic signature";
				return false;
			}
			if (!fwrite($fh, pack("NNNNNNnnNN",40, $base_width, $base_height, $pa_options["tile_width"], $pa_options["tile_height"], $tiles, $pa_options["layers"], $pa_options["layer_ratio"], strlen($attribute_list),$attributes))) {
				$this->error = "Could not write Tilepic header";
				return false;
			}
		
			# --- offset table
			$offset = 44 + ($tiles * 4);
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, pack("N",$offset))) {
						$this->error = "Could not write Tilepic offset table";
						return false;
					}
					$offset += strlen($layer_list[$i][$j]);
				}   
			}
			if ($this->debug) { print "offset table OK;"; }
			
			if (!fwrite($fh, pack("N", $offset))) {
				$this->error = "Could not finish writing Tilepic offset table";
				return false;
			}
			
			# --- tiles
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, $layer_list[$i][$j])) {
						$this->error = "Could not write Tilepic tile data";
						return false;
					}
				}   
			}
			if ($this->debug) { print "tiles OK;"; }
			unset($layer_list);
			# --- attributes
			if (!fwrite($fh, $attribute_list)) {
				$this->error = "Could not write Tilepic attributes";
				return false;
			}
			if ($this->debug) { print "attributes OK\n"; }
			fclose($fh);
			
			return $pa_options;
		} else {
			$this->error = "Couldn't open output file $ps_output_path\n";
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	function encode_graphicsmagick ($ps_filepath, $ps_output_path, $pa_options) {
		if (!($vs_tilepic_tmpdir = $this->opo_config->get('tilepic_tmpdir'))) {
			$vs_tilepic_tmpdir = caGetTempDirPath();
		}
		if (!($magick = $this->mimetype2magick[$pa_options["output_mimetype"]])) {
			$this->error = "Invalid output format";
			return false;
		}
		
		#
		# Open image
		#
		$h = $this->_graphicsMagickRead($ps_filepath);
		if (!$h) {
			$this->error = "Couldn't open image $ps_filepath";
			return false;
		}

		$vs_filepath = $ps_filepath;

		$image_width = 	$h['width'];
		$image_height = $h['height'];
		if (($image_width < 10) || ($image_height < 10)) {
			$this->error = "Image is too small to be output as Tilepic; minimum dimensions are 10x10 pixels";
				return false;
		}

		if ($pa_options["scale_factor"] != 1) {
			$image_width *= $pa_options["scale_factor"];
			$image_height *= $pa_options["scale_factor"];

				$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_scale_');
				$vs_tmp_fname = $vs_tmp_basename.'.jpg';
				if (!($this->_graphicsMagickProcess($vs_filepath, $vs_tmp_fname, array(
						array(
							'op' => 'size',
							'width' => $image_width,
							'height' => $image_height,
						)
					)
				))) {
					$this->error = "Couldn't scale image";
					@unlink($vs_tmp_fname);
					return false;
				}
				$vs_filepath = $vs_tmp_fname;
		}

		 if(function_exists('exif_read_data') && !($this->opo_config->get('dont_use_exif_read_data'))) {
			if (is_array($va_exif = @exif_read_data($ps_filepath, 'IFD0', true, false))) { 
				if (isset($va_exif['IFD0']['Orientation'])) {
					$vn_orientation_rotate = null;
					$vn_orientation = $va_exif['IFD0']['Orientation'];
					switch($vn_orientation) {
						case 3:
							$vn_orientation_rotate = 180;
							break;
						case 6:
							$vn_orientation_rotate = 90;
							break;
						case 8:
							$vn_orientation_rotate = -90;
							break;
					}
					
					if ($vn_orientation_rotate) {
						$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_rotate_');
						$vs_tmp_fname = $vs_tmp_basename.'.jpg';
						if (!($this->_graphicsMagickProcess($vs_filepath, $vs_tmp_fname, array(
								array(
									'op' => 'rotate',
									'angle' => $vn_orientation_rotate
								)
							)
						))) {
							$this->error = "Couldn't rotate image";
							@unlink($vs_tmp_fname);
							return false;
						}
						
						if (in_array($vn_orientation_rotate, array(90, -90))) {
							$vn_tmp = $image_width;
							$image_width = $h['width'] = $image_height;
							$image_height = $h['height'] = $vn_tmp;
						}
						$vs_filepath = $vs_tmp_fname;
					}
				}
			}
		}
        
		#
		# How many layers to make?
		#
		if (!$pa_options["layers"]) {
			$sw = $image_width * $pa_options["layer_ratio"];
			$sh = $image_height * $pa_options["layer_ratio"];
			$pa_options["layers"] = 1;
			while (($sw >= $pa_options["tile_width"]) || ($sh >= $pa_options["tile_height"])) {
				$sw = ceil($sw / $pa_options["layer_ratio"]);
				$sh = ceil($sh / $pa_options["layer_ratio"]);
				$pa_options["layers"] ++;
			}
		}
		
		#
		# Cut image into tiles
		#
		$tiles = 0;
		$layer_list = array();
		$base_width = $image_width;
		$base_height = $image_height;
		
		if ($this->debug) { print "BASE $base_width x $base_height \n";}
		for($l=$pa_options["layers"]; $l >= 1; $l--) {
			
			$x = $y = 0;
			$wx = $pa_options["tile_width"];
			$wy = $pa_options["tile_height"];
			
			if ($this->debug) { print "LAYER=$l\n"; };
			if ($l < $pa_options["layers"]) {
				$image_width = ceil($image_width/$pa_options["layer_ratio"]);
				$image_height = ceil($image_height/$pa_options["layer_ratio"]);
				if ($this->debug) { print "RESIZE layer $l TO $image_width x $image_height \n";}
				
				
				$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_layer_scale_');
				$vs_tmp_fname = $vs_tmp_basename.'.jpg';
				if (!($this->_graphicsMagickProcess($vs_filepath, $vs_tmp_fname, array(
						array(
							'op' => 'size',
							'width' => $image_width,
							'height' => $image_height,
						)
					)
				))) {
					$this->error = "Couldn't scale image";
					@unlink($vs_tmp_fname);
					return false;
				}
				if ($vs_filepath != $ps_filepath) { @unlink($vs_filepath); }
				$vs_filepath = $vs_tmp_fname;
			}
		
			$i = 0;
			
			$layer_list[] = array();
			while($y < $image_height) {
				$vs_tmp_basename = tempnam($vs_tilepic_tmpdir, 'tpc_tile_');
				$vs_tmp_fname = $vs_tmp_basename.'.jpg';
				if (!($this->_graphicsMagickProcess($vs_filepath, $vs_tmp_fname, array(
						array(
							'op' => 'crop',
							'width' => $wx,
							'height' => $wy,
							'x' => $x,
							'y' => $y
						), 
						array(
							'op' => 'strip'
						)
					),
					$pa_options["quality"]
				))) {
					$this->error = "Couldn't tile image";
					@unlink($vs_tmp_fname);
					@unlink($vs_tmp_basename);
					return false;
				}
				
				$vs_tile = file_get_contents($vs_tmp_fname);
				@unlink($vs_tmp_fname);
				@unlink($vs_tmp_basename);
				
				$layer_list[sizeof($layer_list)-1][] = $vs_tile;
				$x += $pa_options["tile_width"];
				
				if ($x >= $image_width) {
					$y += $pa_options["tile_height"];
					$x = 0;
				}
				
				$i++;
				$tiles++;
				
			}
			if ($this->debug) { print "OUTPUT $tiles TILES FOR LAYER $l : $image_width x $image_height\n";}
		}
		if ($vs_filepath != $ps_filepath) { @unlink($vs_filepath); }
		
		#
		# Write Tilepic format file
		#
		if ($this->debug) { print "WRITING FILE..."; }
		if ($fh = fopen($ps_output_path.".tpc", "w")) {
			# --- attribute list
			$attribute_list = "";
			$attributes = 0;
			
			if ((isset($pa_options["attributes"])) && (is_array($pa_options["attributes"]))) {
				$pa_options["attributes"]["mimeType"] = $pa_options["output_mimetype"];
			} else {
				$pa_options["attributes"] = array("mimeType" => $pa_options["output_mimetype"]);
			}
			foreach ($pa_options["attributes"] as $k => $v) {
				$attribute_list .= "$k=$v\0";
				$attributes++;
			}
			
			if ($this->debug) { print "header OK;"; }
			# --- header
			if (!fwrite($fh, "TPC\n")) {
				$this->error = "Could not write Tilepic signature";
				return false;
			}
			if (!fwrite($fh, pack("NNNNNNnnNN",40, $base_width, $base_height, $pa_options["tile_width"], $pa_options["tile_height"], $tiles, $pa_options["layers"], $pa_options["layer_ratio"], strlen($attribute_list),$attributes))) {
				$this->error = "Could not write Tilepic header";
				return false;
			}
		
			# --- offset table
			$offset = 44 + ($tiles * 4);
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, pack("N",$offset))) {
						$this->error = "Could not write Tilepic offset table";
						return false;
					}
					$offset += strlen($layer_list[$i][$j]);
				}   
			}
			if ($this->debug) { print "offset table OK;"; }
			
			if (!fwrite($fh, pack("N", $offset))) {
				$this->error = "Could not finish writing Tilepic offset table";
				return false;
			}
			
			# --- tiles
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, $layer_list[$i][$j])) {
						$this->error = "Could not write Tilepic tile data";
						return false;
					}
				}   
			}
			if ($this->debug) { print "tiles OK;"; }
			unset($layer_list);
			# --- attributes
			if (!fwrite($fh, $attribute_list)) {
				$this->error = "Could not write Tilepic attributes";
				return false;
			}
			if ($this->debug) { print "attributes OK\n"; }
			fclose($fh);
			
			return $pa_options;
		} else {
			$this->error = "Couldn't open output file $ps_output_path\n";
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	public function setResourceLimits_imagick($po_handle) {
		$po_handle->setResourceLimit(imagick::RESOURCETYPE_MEMORY, 1024*1024*1024);		// Set maximum amount of memory in bytes to allocate for the pixel cache from the heap.
		$po_handle->setResourceLimit(imagick::RESOURCETYPE_MAP, 1024*1024*1024);		// Set maximum amount of memory map in bytes to allocate for the pixel cache.
		$po_handle->setResourceLimit(imagick::RESOURCETYPE_AREA, 6144*6144);			// Set the maximum width * height of an image that can reside in the pixel cache memory.
		$po_handle->setResourceLimit(imagick::RESOURCETYPE_FILE, 1024);					// Set maximum number of open pixel cache files.
		$po_handle->setResourceLimit(imagick::RESOURCETYPE_DISK, 64*1024*1024*1024);					// Set maximum amount of disk space in bytes permitted for use by the pixel cache.	
		return true;
	}
	# ------------------------------------------------------------------------------------
	function encode_imagick ($ps_filepath, $ps_output_path, $pa_options) {
		if (!($magick = $this->mimetype2magick[$pa_options["output_mimetype"]])) {
			$this->error = "Invalid output format";
			return false;
		}
		
		#
		# Open image
		#
		$h = new Imagick();
		$this->setResourceLimits_imagick($h);
		
        if (!$h->readImage($ps_filepath)) {
			$this->error = "Couldn't open image $ps_filepath";
			return false;
        }
        $h->profileImage('*', null);
        
        if(function_exists('exif_read_data') && !($this->opo_config->get('dont_use_exif_read_data'))) {
			if (is_array($va_exif = @exif_read_data($ps_filepath, 'IFD0', true, false))) { 
				if (isset($va_exif['IFD0']['Orientation'])) {
					$vn_orientation = $va_exif['IFD0']['Orientation'];
					switch($vn_orientation) {
						case 3:
							$h->rotateImage("#FFFFFF", 180);
							break;
						case 6:
							$h->rotateImage("#FFFFFF", 90);
							break;
						case 8:
							$h->rotateImage("#FFFFFF", -90);
							break;
					}
				}
			}
		}
        
        $h->setImageType(imagick::IMGTYPE_TRUECOLOR);

		if ($h->getImageColorspace() === imagick::COLORSPACE_CMYK) {
			if (!$h->setImageColorspace(imagick::COLORSPACE_RGB)) {
				$this->error = "Error during RGB colorspace transformation operation";
				return false;
			}
		}
		
		$va_tmp = $h->getImageGeometry();
        $image_width = 	$va_tmp['width'];
        $image_height = $va_tmp['height'];
        if (($image_width < 10) || ($image_height < 10)) {
        	$this->error = "Image is too small to be output as Tilepic; minimum dimensions are 10x10 pixels";
			return false;
        }
        
        if ($pa_options["scale_factor"] != 1) {
        	$image_width *= $pa_options["scale_factor"];
        	$image_height *= $pa_options["scale_factor"];
			
			if (!$h->resizeImage($image_width, $image_height, imagick::FILTER_CUBIC, $pa_options["antialiasing"])) {
				$this->error = "Couldn't scale image";
				return false;
			}
        }
        
		#
		# How many layers to make?
		#
		if (!$pa_options["layers"]) {
			$sw = $image_width * $pa_options["layer_ratio"];
			$sh = $image_height * $pa_options["layer_ratio"];
			$pa_options["layers"] = 1;
			while (($sw >= $pa_options["tile_width"]) || ($sh >= $pa_options["tile_height"])) {
				$sw = ceil($sw / $pa_options["layer_ratio"]);
				$sh = ceil($sh / $pa_options["layer_ratio"]);
				$pa_options["layers"] ++;
			}
		}
		
		#
		# Cut image into tiles
		#
		$tiles = 0;
		$layer_list = array();
		$base_width = $image_width;
		$base_height = $image_height;
		
		if ($this->debug) { print "BASE $base_width x $base_height \n";}
		for($l=$pa_options["layers"]; $l >= 1; $l--) {
			
			$x = $y = 0;
			$wx = $pa_options["tile_width"];
			$wy = $pa_options["tile_height"];
			
			if ($this->debug) { print "LAYER=$l\n"; };
			if ($l < $pa_options["layers"]) {
				$image_width = ceil($image_width/$pa_options["layer_ratio"]);
				$image_height = ceil($image_height/$pa_options["layer_ratio"]);
				if ($this->debug) { print "RESIZE layer $l TO $image_width x $image_height \n";}
				if (!$h->resizeImage( $image_width, $image_height, imagick::FILTER_CUBIC, $pa_options["antialiasing"])) {
					$this->error = "Couldn't scale image";
					return false;
				}
			}
		
			$i = 0;
			$layer_list[] = array();
			while($y < $image_height) {
				if (!($slice = $h->getImageRegion($wx, $wy, $x, $y))) {
					$this->error = "Couldn't create tile";
					return false;
				}
				$slice->setCompressionQuality($pa_options["quality"]);
				
				if (!$slice->setImageFormat($magick)) {
					$reason      = WandGetExceptionType( $slice ) ;
					$description = WandGetExceptionDescription( $slice ) ;
					$this->error = "Tile conversion failed: $reason; $description";
					return false;
				}
				
				# --- remove color profile (saves lots of space)
				//$slice->removeImageProfile($slice);
				$layer_list[sizeof($layer_list)-1][] = $slice->getImageBlob();
				$slice->destroy();
				$x += $pa_options["tile_width"];
				
				if ($x >= $image_width) {
					$y += $pa_options["tile_height"];
					$x = 0;
				}
				
				$i++;
				$tiles++;
				
			}
			if ($this->debug) { print "OUTPUT $tiles TILES FOR LAYER $l : $image_width x $image_height\n";}
		}
		
		$h->destroy();
		#
		# Write Tilepic format file
		#
		if ($this->debug) { print "WRITING FILE..."; }
		if ($fh = fopen($ps_output_path.".tpc", "w")) {
			# --- attribute list
			$attribute_list = "";
			$attributes = 0;
			
			if ((isset($pa_options["attributes"])) && (is_array($pa_options["attributes"]))) {
				$pa_options["attributes"]["mimeType"] = $pa_options["output_mimetype"];
			} else {
				$pa_options["attributes"] = array("mimeType" => $pa_options["output_mimetype"]);
			}
			foreach ($pa_options["attributes"] as $k => $v) {
				$attribute_list .= "$k=$v\0";
				$attributes++;
			}
			
			if ($this->debug) { print "header OK;"; }
			# --- header
			if (!fwrite($fh, "TPC\n")) {
				$this->error = "Could not write Tilepic signature";
				return false;
			}
			if (!fwrite($fh, pack("NNNNNNnnNN",40, $base_width, $base_height, $pa_options["tile_width"], $pa_options["tile_height"], $tiles, $pa_options["layers"], $pa_options["layer_ratio"], strlen($attribute_list),$attributes))) {
				$this->error = "Could not write Tilepic header";
				return false;
			}
		
			# --- offset table
			$offset = 44 + ($tiles * 4);
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, pack("N",$offset))) {
						$this->error = "Could not write Tilepic offset table";
						return false;
					}
					$offset += strlen($layer_list[$i][$j]);
				}   
			}
			if ($this->debug) { print "offset table OK;"; }
			
			if (!fwrite($fh, pack("N", $offset))) {
				$this->error = "Could not finish writing Tilepic offset table";
				return false;
			}
			
			# --- tiles
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, $layer_list[$i][$j])) {
						$this->error = "Could not write Tilepic tile data";
						return false;
					}
				}   
			}
			if ($this->debug) { print "tiles OK;"; }
			unset($layer_list);
			# --- attributes
			if (!fwrite($fh, $attribute_list)) {
				$this->error = "Could not write Tilepic attributes";
				return false;
			}
			if ($this->debug) { print "attributes OK\n"; }
			fclose($fh);
			
			return $pa_options;
		} else {
			$this->error = "Couldn't open output file $ps_output_path\n";
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	public function setResourceLimits_gmagick($po_handle) {
	    // As of GraphicMagick 1.3.32 setResourceLimit is broken
		//$po_handle->setResourceLimit(Gmagick::RESOURCETYPE_MEMORY, 1024*1024*1024);		// Set maximum amount of memory in bytes to allocate for the pixel cache from the heap.
		//$po_handle->setResourceLimit(Gmagick::RESOURCETYPE_MAP, 1024*1024*1024);		// Set maximum amount of memory map in bytes to allocate for the pixel cache.
		//$po_handle->setResourceLimit(Gmagick::RESOURCETYPE_AREA, 6144*6144);			// Set the maximum width * height of an image that can reside in the pixel cache memory.
		//$po_handle->setResourceLimit(Gmagick::RESOURCETYPE_FILE, 1024);					// Set maximum number of open pixel cache files.
		//$po_handle->setResourceLimit(Gmagick::RESOURCETYPE_DISK, 64*1024*1024*1024);					// Set maximum amount of disk space in bytes permitted for use by the pixel cache.
		return true;
	}
	# ------------------------------------------------------------------------------------
	function encode_gmagick ($ps_filepath, $ps_output_path, $pa_options) {
		if (!($magick = $this->mimetype2magick[$pa_options["output_mimetype"]])) {
			$this->error = "Invalid output format";
			return false;
		}
		
		#
		# Open image
		#
		try {
			$h = new Gmagick($ps_filepath);
			$this->setResourceLimits_gmagick($h);
			$h->setimageindex(0);	// force use of first image in multi-page TIFF
		} catch (Exception $e){
			$this->error = "Couldn't open image $ps_filepath";
			return false;
		}

		$image_dimensions = $h->getimagegeometry();
		
		$tmp_path = null;
		$rotation = null;
		if(function_exists('exif_read_data') && !($this->opo_config->get('dont_use_exif_read_data'))) {
			if (is_array($va_exif = @exif_read_data($ps_filepath, 'IFD0', true, false))) { 
				if (isset($va_exif['IFD0']['Orientation'])) {
					$vn_orientation = $va_exif['IFD0']['Orientation'];
					if($vn_orientation > 0) {
						// Make copy without EXIF rotation and re-read. There's no other way
						// to get rid of the orientation tag in derived files (argh).
						$use_exif_tool_to_strip = (bool)$this->opo_config->get('dont_use_exiftool_to_strip_exif_orientation_tags');
						
						// Fall back to using stripImage() if ExifTool is not available. This is quick and easy 
						// but will strip *everything* including color profiles which will be a problem for many users.
						if (!caExifToolInstalled() || $use_exif_tool_to_strip) {	
							$h->stripImage();
						} else {
							// Stripping with EXIF tool means copying the entire file and then shelling out
							// to remove the EXIF tag. We could avoid a copy by running EXIF tool on each tile we
							// generate, but then we'd be shelling out hundreds or thousands of times per image. 
							// Either way it sucks.
							$tmp_path = caGetTempDirPath()."/".pathinfo($ps_filepath, PATHINFO_FILENAME)."_orient.".pathinfo($ps_filepath, PATHINFO_EXTENSION);
							copy($ps_filepath, $tmp_path);
							caExtractRemoveOrientationTagWithExifTool($tmp_path);
							
							try {
								$h = new Gmagick($tmp_path);
								$this->setResourceLimits_gmagick($h);
								$h->setimageindex(0);	// force use of first image in multi-page TIFF
							} catch (Exception $e){
								$this->error = "Couldn't open image {$ps_filepath}_orient";
								return false;
							}
						}
					}
					switch($vn_orientation) {
						case 3:
							$h->rotateimage("#FFFFFF", $rotation = 180);
							break;
						case 6:
							$h->rotateimage("#FFFFFF", $rotation = 90);
							break;
						case 8:
							$h->rotateimage("#FFFFFF", $rotation = -90);
							break;
					}
				}
			}
			
			if(abs($rotation) === 90) {	// flip dimensions due to EXIF rotation (Gmagick doesn't update dimensions properly)
				$d = $image_dimensions;
				$image_dimensions['width'] = $d['height'];
				$image_dimensions['height'] = $d['width'];
			}
		}
        
		$h->setimagetype(Gmagick::IMGTYPE_TRUECOLOR);

		if ($h->getimagecolorspace() === Gmagick::COLORSPACE_CMYK) {
			if (!$h->setimagecolorspace(Gmagick::COLORSPACE_RGB)) {
				$this->error = "Error during RGB colorspace transformation operation";
				return false;
			}
		}
		
		$image_width = 	$image_dimensions['width'];
		$image_height = $image_dimensions['height'];
		if (($image_width < 10) || ($image_height < 10)) {
			$this->error = "Image is too small to be output as Tilepic; minimum dimensions are 10x10 pixels";
				return false;
		}

		if ($pa_options["scale_factor"] != 1) {
			$image_width *= $pa_options["scale_factor"];
			$image_height *= $pa_options["scale_factor"];

			if (!$h->resizeimage($image_width, $image_height, Gmagick::FILTER_CUBIC, $pa_options["antialiasing"])) {
				$this->error = "Couldn't scale image";
				return false;
			}
		}
        
		#
		# How many layers to make?
		#
		if (!$pa_options["layers"]) {
			$sw = $image_width * $pa_options["layer_ratio"];
			$sh = $image_height * $pa_options["layer_ratio"];
			$pa_options["layers"] = 1;
			while (($sw >= $pa_options["tile_width"]) || ($sh >= $pa_options["tile_height"])) {
				$sw = ceil($sw / $pa_options["layer_ratio"]);
				$sh = ceil($sh / $pa_options["layer_ratio"]);
				$pa_options["layers"] ++;
			}
		}
		
		#
		# Cut image into tiles
		#
		$tiles = 0;
		$layer_list = array();
		$base_width = $image_width;
		$base_height = $image_height;
		
		if ($this->debug) { print "BASE $base_width x $base_height \n";}
		for($l=$pa_options["layers"]; $l >= 1; $l--) {
			
			$x = $y = 0;
			$wx = $pa_options["tile_width"];
			$wy = $pa_options["tile_height"];
			
			if ($this->debug) { print "LAYER=$l\n"; };
			if ($l < $pa_options["layers"]) {
				$image_width = ceil($image_width/$pa_options["layer_ratio"]);
				$image_height = ceil($image_height/$pa_options["layer_ratio"]);
				if ($this->debug) { print "RESIZE layer $l TO $image_width x $image_height \n";}
				if (!$h->resizeimage( $image_width, $image_height, Gmagick::FILTER_CUBIC, $pa_options["antialiasing"])) {
					$this->error = "Couldn't scale image";
					return false;
				}
			}
		
			$i = 0;
			$layer_list[] = array();
			while($y < $image_height) {
				$slice = clone $h;
				try {
					$slice->cropimage($wx, $wy, $x, $y);
				} catch (Exception $e){
					$this->error = "Couldn't create tile";
					return false;
				}
				
				if (!$slice->setimageformat($magick)) {
					$this->error = "Tile conversion failed";
					return false;
				}
				
				if (!$slice->setcompressionquality($pa_options["quality"])) {
					$this->error = "Tile quality set failed";
					return false;
				}
				
				$radius = 2;
				if (($wx < 100) || ($wy < 100))  {
					$radius = 1;
				}
				$sigma = 0.5;
				
				try {
					$slice->sharpenImage($radius, $sigma);
				} catch(Exception $e) {
					// noop
				}
				
				$layer_list[sizeof($layer_list)-1][] = $slice->getImageBlob();
				$slice->destroy();
				$x += $pa_options["tile_width"];
				
				if ($x >= $image_width) {
					$y += $pa_options["tile_height"];
					$x = 0;
				}
				
				$i++;
				$tiles++;
				
			}
			if ($this->debug) { print "OUTPUT $tiles TILES FOR LAYER $l : $image_width x $image_height\n";}
		}
		
		$h->destroy();
		
		if($tmp_path) { @unlink($tmp_path); }
		
		#
		# Write Tilepic format file
		#
		if ($this->debug) { print "WRITING FILE..."; }
		if ($fh = fopen($ps_output_path.".tpc", "w")) {
			# --- attribute list
			$attribute_list = "";
			$attributes = 0;
			
			if ((isset($pa_options["attributes"])) && (is_array($pa_options["attributes"]))) {
				$pa_options["attributes"]["mimeType"] = $pa_options["output_mimetype"];
			} else {
				$pa_options["attributes"] = array("mimeType" => $pa_options["output_mimetype"]);
			}
			foreach ($pa_options["attributes"] as $k => $v) {
				$attribute_list .= "$k=$v\0";
				$attributes++;
			}
			
			if ($this->debug) { print "header OK;"; }
			# --- header
			if (!fwrite($fh, "TPC\n")) {
				$this->error = "Could not write Tilepic signature";
				return false;
			}
			if (!fwrite($fh, pack("NNNNNNnnNN",40, $base_width, $base_height, $pa_options["tile_width"], $pa_options["tile_height"], $tiles, $pa_options["layers"], $pa_options["layer_ratio"], strlen($attribute_list),$attributes))) {
				$this->error = "Could not write Tilepic header";
				return false;
			}
		
			# --- offset table
			$offset = 44 + ($tiles * 4);
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, pack("N",$offset))) {
						$this->error = "Could not write Tilepic offset table";
						return false;
					}
					$offset += strlen($layer_list[$i][$j]);
				}   
			}
			if ($this->debug) { print "offset table OK;"; }
			
			if (!fwrite($fh, pack("N", $offset))) {
				$this->error = "Could not finish writing Tilepic offset table";
				return false;
			}
			
			# --- tiles
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, $layer_list[$i][$j])) {
						$this->error = "Could not write Tilepic tile data";
						return false;
					}
				}   
			}
			if ($this->debug) { print "tiles OK;"; }
			unset($layer_list);
			# --- attributes
			if (!fwrite($fh, $attribute_list)) {
				$this->error = "Could not write Tilepic attributes";
				return false;
			}
			if ($this->debug) { print "attributes OK\n"; }
			fclose($fh);
			
			return $pa_options;
		} else {
			$this->error = "Couldn't open output file $ps_output_path\n";
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	function encode_gd ($ps_filepath, $ps_output_path, $pa_options) {
		if (!($vs_tilepic_tmpdir = $this->opo_config->get('tilepic_tmpdir'))) {
			$vs_tilepic_tmpdir = caGetTempDirPath();
		}
		
		if (!($magick = $this->mimetype2magick[$pa_options["output_mimetype"]])) {
			$this->error = "Invalid output format";
			return false;
		}
		
		#
		# Open image
		#
		if($va_info = getimagesize($ps_filepath)) {
			switch($va_info[2]) {
				case IMAGETYPE_GIF:
					$r_image = imagecreatefromgif($ps_filepath);
					$vs_mimetype = "image/gif";
					$vs_typename = "GIF";
					break;
				case IMAGETYPE_JPEG:
					$r_image = imagecreatefromjpeg($ps_filepath);
					 if(function_exists('exif_read_data') && !($this->opo_config->get('dont_use_exif_read_data'))) {
						if (is_array($va_exif = @exif_read_data($ps_filepath, 'IFD0', true, false))) { 
							if (isset($va_exif['IFD0']['Orientation'])) {
								$vn_orientation = $va_exif['IFD0']['Orientation'];
								$h = new WLPlugMediaGD();
								switch($vn_orientation) {
									case 3:
										$r_image = $h->rotateImage($r_image, 180);
										break;
									case 6:
										$r_image = $h->rotateImage($r_image, -90);
										$vn_width = $va_info[0];
										$va_info[0] = $va_info[1];
										$va_info[1] = $vn_width;
										break;
									case 8:
										$r_image = $h->rotateImage($r_image, 90);
										$vn_width = $va_info[0];
										$va_info[0] = $va_info[1];
										$va_info[1] = $vn_width;
										break;
								}
							}
						}
					}
					$vs_mimetype = "image/jpeg";
					$vs_typename = "JPEG";
					break;
				case IMAGETYPE_PNG:
					$r_image = imagecreatefrompng($ps_filepath);
					$vs_mimetype = "image/png";
					$vs_typename = "PNG";
					break;
			}
			if (!$r_image) {
				$this->error = "Couldn't open image $ps_filepath: open for $vs_typename failed";
				return false;
			}
		} else {
			$this->error = "Couldn't open image $ps_filepath: unsupported file type";
			return false;
		}
        $image_width = $va_info[0];
        $image_height = $va_info[1];
        if (($image_width < 10) || ($image_height < 10)) {
        	$this->error = "Image is too small to be output as Tilepic; minimum dimensions are 10x10 pixels";
			return false;
        }
        
        if ($pa_options["scale_factor"] != 1) {
        	$image_width *= $pa_options["scale_factor"];
        	$image_height *= $pa_options["scale_factor"];
			
			$r_new_image = imagecreatetruecolor($image_width, $image_height);
			$r_color = ImageColorAllocate( $r_new_image, 255, 255, 255 );
			imagefilledrectangle($r_new_image, 0,0,$image_width-1, $image_height-1, $r_color);
			if (imagecopyresampled($r_new_image, $r_image, 0, 0, 0, 0, $image_width, $image_height, $va_info[0], $va_info[1])) {
				$this->error = "Couldn't scale image for new layer";
				return false;
			}
			imagedestroy($r_image);
			$r_image = $r_new_image;
        }
        
		#
		# How many layers to make?
		#
		if (!$pa_options["layers"]) {
			$sw = $image_width * $pa_options["layer_ratio"];
			$sh = $image_height * $pa_options["layer_ratio"];
			$pa_options["layers"] = 1;
			while (($sw >= $pa_options["tile_width"]) || ($sh >= $pa_options["tile_height"])) {
				$sw = ceil($sw / $pa_options["layer_ratio"]);
				$sh = ceil($sh / $pa_options["layer_ratio"]);
				$pa_options["layers"] ++;
			}
		}
		
		#
		# Cut image into tiles
		#
		$tiles = 0;
		$layer_list = array();
		$base_width = $image_width;
		$base_height = $image_height;
		
		for($l=$pa_options["layers"]; $l >= 1; $l--) {
			$x = $y = 0;
			
			if ($l < $pa_options["layers"]) {
				$old_image_width = $image_width;
				$old_image_height = $image_height;
				$image_width = ceil($image_width/$pa_options["layer_ratio"]);
				$image_height = ceil($image_height/$pa_options["layer_ratio"]);
				
				$r_new_image = imagecreatetruecolor($image_width, $image_height);
				$r_color = ImageColorAllocate( $r_new_image, 255, 255, 255 );
				imagefilledrectangle($r_new_image, 0,0,$image_width-1, $image_height-1, $r_color);
				if (!imagecopyresampled($r_new_image, $r_image, 0, 0, 0, 0, $image_width, $image_height, $old_image_width, $old_image_height)) {
					$this->error = "Couldn't scale image for layer $l";
					return false;
				}
				imagedestroy($r_image);
				$r_image = $r_new_image;
			}
		
			$i = 0;
			//$slices = array();
			$layer_list[] = array();
			while($y < $image_height) {
				$wx = $pa_options["tile_width"];
				$wy = $pa_options["tile_height"];
				if (($image_width - $x) < $wx)  { $wx = ($image_width - $x); }
				if (($image_height - $y) < $wy)  { $wy = ($image_height - $y); }
				
				$r_slice = imagecreatetruecolor($wx, $wy);
				$r_color = ImageColorAllocate( $r_slice, 255, 255, 255 );
				imagefilledrectangle($r_slice, 0,0,$wx-1, $wy-1, $r_color);
				if(!imagecopy($r_slice, $r_image,0,0,$x,$y,$wx, $wy)) {
					$this->error = "Couldn't create tile in level $l";
					return false;
				}
				
				$vs_gd_tmp = tempnam($vs_tilepic_tmpdir, 'tpc_gd_tmp');
				switch($pa_options["output_mimetype"]) {
					case 'image/gif':
						imagegif($r_slice, $vs_gd_tmp);
						break;
					case 'image/jpeg':
						if ($pa_options["quality"] > 0) {
							imagejpeg($r_slice, $vs_gd_tmp, $pa_options["quality"]);
						} else {
							imagejpeg($r_slice, $vs_gd_tmp);
						}
						break;
					case 'image/png':
						imagepng($r_slice, $vs_gd_tmp);
						break;
					default:
						die("Invalid output format ".$pa_options["output_mimetype"]);
				}
				$vs_image = file_get_contents($vs_gd_tmp);
				@unlink($vs_gd_tmp);
				
				$layer_list[sizeof($layer_list)-1][] = $vs_image;
				imagedestroy($r_slice);
				$x += $pa_options["tile_width"];
				
				if ($x >= $image_width) {
					$y += $pa_options["tile_height"];
					$x = 0;
				}
				$i++;
				$tiles++;
			}
		}
		imagedestroy($r_image);
		
		#
		# Write Tilepic format file
		#
		if ($fh = fopen($ps_output_path.".tpc", "w")) {
			# --- attribute list
			$attribute_list = "";
			$attributes = 0;
			
			if ((isset($pa_options["attributes"])) && (is_array($pa_options["attributes"]))) {
				$pa_options["attributes"]["mimeType"] = $pa_options["output_mimetype"];
			} else {
				$pa_options["attributes"] = array("mimeType" => $pa_options["output_mimetype"]);
			}
			foreach ($pa_options["attributes"] as $k => $v) {
				$attribute_list .= "$k=$v\0";
				$attributes++;
			}
			
			# --- header
			if (!fwrite($fh, "TPC\n")) {
				$this->error = "Could not write Tilepic signature";
				return false;
			}
			if (!fwrite($fh, pack("NNNNNNnnNN",40, $base_width, $base_height, $pa_options["tile_width"], $pa_options["tile_height"], $tiles, $pa_options["layers"], $pa_options["layer_ratio"], strlen($attribute_list),$attributes))) {
				$this->error = "Could not write Tilepic header";
				return false;
			}
		
			# --- offset table
			$offset = 44 + ($tiles * 4);
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, pack("N",$offset))) {
						$this->error = "Could not write Tilepic offset table";
						return false;
					}
					$offset += strlen($layer_list[$i][$j]);
				}   
			}
			
			if (!fwrite($fh, pack("N", $offset))) {
				$this->error = "Could not finish writing Tilepic offset table";
				return false;
			}
			
			# --- tiles
			for($i=sizeof($layer_list)-1; $i >= 0; $i--) {
				for($j=0; $j<sizeof($layer_list[$i]);$j++) {
					if (!fwrite($fh, $layer_list[$i][$j])) {
						$this->error = "Could not write Tilepic tile data";
						return false;
					}
				}   
			}
			unset($layer_list);
			# --- attributes
			if (!fwrite($fh, $attribute_list)) {
				$this->error = "Could not write Tilepic attributes";
				return false;
			}
			fclose($fh);
			
			return $pa_options;
		} else {
			$this->error = "Couldn't open output file $ps_output_path\n";
			return false;
		}
	}
	
	# ------------------------------------------------------------------------------------
	#
	# Tilepic file access methods
	#
	# ------------------------------------------------------------------------------------
	function getTile($tile_number) {
		# --- Tile numbers start at 1, *NOT* 0 in parameter!
		$tile_number--; # internally, tiles are numbered from zero, so adjust here
		
		if (!$this->properties["filepath"]) {
			$this->error = "No file loaded";
			return false;
		}
		if (($this->fh) || ($this->fh = fopen($this->properties["filepath"], "r"))) {
			if ($offset = $this->properties["tile_offsets"][$tile_number]) {
				if (!($next_offset = $this->properties["tile_offsets"][$tile_number + 1])) {
					if (!($next_offset = $this->properties["attribute_offset"])) {
						$this->error = "Couldn't find end of tile [".$this->properties["attribute_offset"]."]";
						return false;
					}
				}
				
				if (fseek($this->fh, $offset, 0) == -1) {
					$this->error = "Could not seek to requested tile";
					return false;
				}
				
				return fread($this->fh, $next_offset - $offset);
			} else {
				$this->error = "Invalid tile number '$tile_number'";
				return false;
			}
		} else {
			$this->error = "Couldn't open file ".$this->properties["filepath"];
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	function writeTiles($ps_dirpath, $ps_filestem="") {
		# --- get tile offsets (start of each tile)
		if (($this->fh) || ($this->fh = fopen($this->properties["filepath"], "r"))) {
			$vs_ext = $this->mimetype2ext[$this->properties["tile_mimetype"]];
			
			foreach($this->properties["tile_offsets"] as $vn_tile_num => $vn_offset) {
				if (fseek($this->fh, $vn_offset, 0) == -1) {
					$this->error = "Could not seek to requested tile";
					return false;
				}
				if (!($vn_next_offset = $this->properties["tile_offsets"][$vn_tile_num + 1])) {
					if (!($vn_next_offset = $this->properties["attribute_offset"])) {
						$this->error = "Couldn't find end of tile [".$this->properties["attribute_offset"]."]";
						return false;
					}
				}
				if ($r_fh = fopen($ps_dirpath."/".$ps_filestem.($vn_tile_num+1).".".$vs_ext,"w+")) {
					fwrite($r_fh, fread($this->fh, $vn_next_offset - $vn_offset));
					fclose($r_fh);
				} else {
					$this->error = "Couldn't write tile to ".$ps_dirpath;
					return false;
				}
			}
		} else {
			$this->error = "Couldn't open file ".$this->properties["filepath"];
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	function getLayer($layer_number, $output_mimetype = "image/jpeg") {
		if (!($magick = $this->mimetype2magick[$output_mimetype])) {
			$this->error = "Invalid output format";
			return false;
		}
		if (($layer_number > 0) && ($layer_number <= $this->properties['layers'])) {
			#
			# --- assemble tiles
			#
			switch($this->backend) {
				case LIBRARY_GD:
					$h = $this->getLayer_gd($layer_number, $output_mimetype);
					break;
				case LIBRARY_IMAGICK:
					$h = $this->getLayer_imagick($layer_number, $magick);
					break;
				default:
					$h = $this->getLayer_imagemagick($layer_number, $output_mimetype);
					break;
			}
			return $h;
		} else {
			# --- layer does not exist
			$this->error = "Layer $layer_number does not exist";
			return false;
		}
	}
	# ------------------------------------------------------------------------------------
	function getLayer_imagemagick($layer_number, $output_mimetype) {
		$layer_tiles = $this->getFileGeometry();
		if (!($tile_count = $layer_tiles[$layer_number]['tiles'])) {
			$this->error = "Invalid file";
			return false;
		}
		$tile_start = 1;
		
		for ($l=1; $l<$layer_number; $l++) {
			$tile_start += $layer_tiles[$l]['tiles'];
		}
		
		$tile_number = $tile_start;
		
		$tile_width = $this->properties['tile_width'];
		$tile_height = $this->properties['tile_height'];
		
		$va_tile_files = array();
		for($y=0; $y<$layer_tiles[$layer_number]['vertical_tiles']; $y++) {
			$cy = ($y*$tile_height);
			for($x=0; $x<$layer_tiles[$layer_number]['horizontal_tiles']; $x++) {
				$cx = ($x*$tile_width);
				$tile = $this->getTile($tile_number);
				if ($tile) { 
					$vs_tile_file = tempnam(caGetTempDirPath(), "tpcl_");
					file_put_contents($vs_tile_file, $tile);
					$va_tile_files[] = $vs_tile_file;
				}
				$tile_number++;
			}
		}
		
		if ($vs_ext = $this->mimetype2ext[$output_mimetype]) { $vs_ext = '.'.$vs_ext; }
		$vs_tmp_base_path = tempnam(caGetTempDirPath(), 'tcpt_');
		$vs_tmp_path = $vs_tmp_base_path.$vs_ext;
		if (!$this->_imageMagickImageFromTiles($vs_tmp_path, $va_tile_files, $tile_width, $tile_height)) {
			$this->error = "Compositing of tiles failed";
			foreach($va_tile_files as $vs_tile) {
				@unlink($vs_tile);
			}
			return false;
		}
		foreach($va_tile_files as $vs_tile) {
			@unlink($vs_tile);
		}
		$h = file_get_contents($vs_tmp_path);
		@unlink($vs_tmp_path);
		return $h;
	}
	# ------------------------------------------------------------------------------------
	function getLayer_imagick($layer_number, $magick) {
		$layer_tiles = $this->getFileGeometry();
		if (!($tile_count = $layer_tiles[$layer_number]['tiles'])) {
			$this->error = "Invalid file";
			return false;
		}
		$tile_start = 1;
		
		for ($l=1; $l<$layer_number; $l++) {
			$tile_start += $layer_tiles[$l]['tiles'];
		}
		
		$h = new Imagick();
		$this->setResourceLimits_imagick($h);
		if ($h->newImage($layer_tiles[$layer_number]["width"], $layer_tiles[$layer_number]["height"], "#ffffff")) {
			$this->error = "Couldn't create new image for layer";
			return false;
		}

		$tile_number = $tile_start;
		
		$tile_width = $this->properties['tile_width'];
		$tile_height = $this->properties['tile_height'];
		for($y=0; $y<$layer_tiles[$layer_number]['vertical_tiles']; $y++) {
			$cy = ($y*$tile_height);
			for($x=0; $x<$layer_tiles[$layer_number]['horizontal_tiles']; $x++) {
				$cx = ($x*$tile_width);
				$tile = $this->getTile($tile_number);
				if ($tile) { 
					$t = new Imagick();
					$this->setResourceLimits_imagick($t);
					$t->readImageBlob($tile);
					if (!$h->compositeImage($t, imagick::COMPOSITE_OVER,$cx,$cy)) {
						$this->error = "Couldn't add tile: composite failed";
						return false;
					}
					$t->destroy();
				}
				$tile_number++;
			}
		}
		if (!$h->setImageFormat($magick)) {
			$this->error = "Couldn't convert image to {$magick}";
			return false;
		}
		return $h;
	}
	# ------------------------------------------------------------------------------------
	function getLayer_gd($layer_number, $output_mimetype) {
		$layer_tiles = $this->getFileGeometry();
		if (!($tile_count = $layer_tiles[$layer_number]['tiles'])) {
			$this->error = "Invalid file";
			return false;
		}
		$tile_start = 1;
		
		for ($l=1; $l<$layer_number; $l++) {
			$tile_start += $layer_tiles[$l]['tiles'];
		}
		
		$r_new_image = imagecreatetruecolor($layer_tiles[$layer_number]["width"], $layer_tiles[$layer_number]["height"]);
		if (!$r_new_image) {
			$this->error = "Couldn't create new image";
			return false;
		}

		$tile_number = $tile_start;
		
		$tile_width = $this->properties['tile_width'];
		$tile_height = $this->properties['tile_height'];
		for($y=0; $y<$layer_tiles[$layer_number]['vertical_tiles']; $y++) {
			$cy = ($y*$tile_height);
			for($x=0; $x<$layer_tiles[$layer_number]['horizontal_tiles']; $x++) {
				$cx = ($x*$tile_width);
				$tile = $this->getTile($tile_number);
				if ($tile) { 
					$t = imagecreatefromstring($tile);
					if (!$t) {
						$this->error = "Invalid tile format";
						return false;
					}
					imagecopy($r_new_image, $t, $cx, $cy, 0, 0, $tile_width, $tile_height);
					imagedestroy($t);
				}
				$tile_number++;
			}
		}
		return $r_new_image;
	}
	# ------------------------------------------------------------------------------------
	function getFileGeometry() {
		# --- Layer numbers start at 1  *NOT* 0!
		$layer_tiles = array();
		
		$width_tiles = $height_tiles = $start_tile = 0;
		
		for ($l=1; $l<=$this->properties['layers']; $l++) {
			$scale_factor = pow($this->properties['ratio'], ($this->properties['layers'] - $l));
			if (!$scale_factor) { $scale_factor = 1; }
			
			$effective_tile_width = $this->properties['tile_width'] * $scale_factor;
			$effective_tile_height = $this->properties['tile_height'] * $scale_factor;
		
			$width_tiles = $this->properties['width']/$effective_tile_width;
			$height_tiles = $this->properties['height']/$effective_tile_height;
			$tiles = ceil($width_tiles) * ceil($height_tiles);
			$layer_tiles[$l] = array(
									"layer"=>$l, "tiles" => $tiles, "effective_tile_width" => $effective_tile_width, "effective_tile_height" => $effective_tile_height, 
									"scale_factor" => $scale_factor, "horizontal_tiles" => ceil($width_tiles), "vertical_tiles" => ceil($height_tiles), 
									"width" => intval($width_tiles * $this->properties['tile_width']), "height" => intval($height_tiles * $this->properties['tile_height']), 
									"start_tile" => $start_tile);
		
			$start_tile += $tiles;
		}
		
		return $layer_tiles;
	}
	# ------------------------------------------------------------------------------------
	function getTileLayout() {
		# not implemented yet
	}
	# ------------------------------------------------------------------------------------
	function getProperty($value) {
		return $this->properties[$value];
	}
	# ------------------------------------------------------------------------------------
	function close() {
		return $this->init();
	}
	# ------------------------------------------------------------------------------------
	# Static
	# ------------------------------------------------------------------------------------
	static public function getTileQuickly($ps_filepath, $pn_tile_number, $pb_print_errors=true) {
		# --- Tile numbers start at 1, *NOT* 0 in parameter!
		if ($fh = @fopen($ps_filepath,'r')) {
			# look for signature
			$sig = fread ($fh, 4);
			if (preg_match("/TPC\n/", $sig)) {
				$buf = fread($fh, 4);
				$x = unpack("Nheader_size", $buf);
				
				if ($x['header_size'] <= 8) { 
					if ($pb_print_errors) { print "Tilepic header length is invalid"; }
					fclose($fh);
					return false;
				}
				# --- get tile offsets (start of each tile)
				if (!fseek($fh, ($x['header_size']) + (($pn_tile_number - 1) * 4))) {
					$x = unpack("Noffset", fread($fh, 4)); 
					$y = unpack("Noffset", fread($fh, 4)); 
					
					$x["offset"] = TilepicParser::unpackLargeInt($x["offset"]);
					$y["offset"] = TilepicParser::unpackLargeInt($y["offset"]);
					
					$vn_len = $y["offset"] - $x["offset"];
					if (!fseek($fh, $x["offset"])) {
						$buf = fread($fh, $vn_len);
						fclose($fh);
						return $buf;
					} else {
						if ($pb_print_errors) { print "File seek error while getting tile; tried to seek to ".$x["offset"]." and read $vn_len bytes"; }
						fclose($fh);
						return false;
					}
				} else {
					if ($pb_print_errors) { print "File seek error while getting tile offset"; }
					fclose($fh);
					return false;
				}
			} else {
				if ($pb_print_errors) { print "File is not Tilepic format"; }
				fclose($fh);
				return false;
			}
		} else {
			if ($pb_print_errors) { print "Couldn't open file $ps_filepath"; }
			return false;
		}
	}
	
	# ------------------------------------------------------------------------------------
	#
	# This function gets around a bug in PHP when unpacking large ints on 64bit Opterons
	#
	static public function unpackLargeInt($pn_the_int) {
		$b = sprintf("%b", $pn_the_int); // binary representation
		if(strlen($b) == 64){
			$new = substr($b, 33);
			$pn_the_int = bindec($new);
		}
		return $pn_the_int;
	}
	# ------------------------------------------------------------------------------------
}
