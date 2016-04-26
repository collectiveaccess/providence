<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/IIIFService.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 require_once(__CA_LIB_DIR__."/core/Media.php");
 require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

class IIIFService {
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $ps_identifier
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch($ps_identifier, $po_request) {
		$vs_cache_key = $po_request->getHash();
		$va_path = array_slice(explode("/", $po_request->getPathInfo()), 3);
		
		// INFO: 		{scheme}://{server}{/prefix}/{identifier}/info.json
		// IMAGE:		{scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
		$pb_is_info_request = false;
		if (($ps_region = array_shift($va_path)) == 'info.json') {
			$pb_is_info_request = true;
		} else {
			$ps_size = array_shift($va_path);
			$ps_rotation = array_shift($va_path);
			list($ps_quality, $ps_format) = explode('.', array_shift($va_path));
		}
		// Load image
		$pa_identifier = explode(':', $ps_identifier);
		
		$ps_type = $pa_identifier[0];
		$pn_id = (int)$pa_identifier[1];
		
		$vs_image_path = null;
		switch($ps_type) {
			case 'attribute':
				// TODO: load ca_attribute_value with value_id
				break;
			case 'representation':
			default:
				$t_rep = new ca_object_representations($pn_id);
				// TODO: is this readable by user?
				$vs_image_path = $t_rep->getMediaPath('media', 'original');
				$vn_width = $t_rep->getMediaInfo('media', 'original', 'WIDTH');
				$vn_height = $t_rep->getMediaInfo('media', 'original', 'HEIGHT');
				break;
		}
		
		if ($pb_is_info_request) {
			
		} else {
			$va_operations = [];
			
			// region
			$va_region = IIIFService::calculateRegion($vn_width, $vn_height, $ps_region);
			if (($va_region['w'] != $vn_width) && ($va_region['h'] != $vn_height)) {
				$va_operations[] = ['CROP' => $va_region];
			}
			
			// size	
			$va_dimensions = IIIFService::calculateSize($vn_width, $vn_height, $ps_size);
			$va_operations[] = ['SCALE' => $va_dimensions];
			
			// rotate
			$va_rotation = IIIFService::calculateRotation($vn_width, $vn_height, $ps_rotation);
			if ($va_rotation['angle'] != 0) {
				$va_operations[] = ['ROTATE' => $va_rotation];
			}
			if ($va_rotation['reflection']) {
				$va_operations[] = ['FLIP' => ['direction' => 'horizontal']];
			}
			
			// quality
			$vs_quality = IIIFService::calculateQuality($vn_width, $vn_height, $ps_quality);
			if ($vs_quality && ($vs_quality != 'default')) {
				$va_operations[] = ['SET' => ['colorspace' => $vs_quality]];
			}
			
			// format
			if (!($vs_mimetype = IIIFService::calculateFormat($vn_width, $vn_height, $ps_format))) {
				// TODO: throw 400 error
				die("unsupported format {$vs_mimetype}");
			}
			
			$vs_output_path = IIIFService::processImage($vs_image_path, $vs_mimetype, $va_operations, $po_request);
			header("Content-type: {$vs_mimetype}");
			
			$o_fp = @fopen($vs_output_path,"rb");
			while(is_resource($o_fp) && !feof($o_fp)) {
				print(@fread($o_fp, 1024*8));
				ob_flush();
				flush();
			}
			@unlink($vs_output_path);
		}
		
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function processImage($ps_image_path, $ps_mimetype, $pa_operations, $po_request) {
		$o_media  = new Media();
		if (!$o_media->read($ps_image_path)) { 
			throw new Exception("Cannot open file");
		}
		
		foreach($pa_operations as $vn_i => $va_operation) {
			foreach($va_operation as $vs_operation => $va_params) {
				switch($vs_operation) {
					case 'SCALE':
					case 'CROP':
					case 'ROTATE':
					case 'SET':
					case 'FLIP':
						$o_media->transform($vs_operation, $va_params);
						break;
				}
			}
		}
		
		$o_media->transform('SET', ['mimetype' => $ps_mimetype]);
		
		// TODO: proper tmp file name
		return $o_media->write($vs_output_path = "/tmp/TESTFILE", $ps_mimetype);
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image size based upon IIIF {size} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $ps_size IIIF size value 
	 *
	 * @return array Array with 'width' and 'height' keys containing calculated width and height
	 */
	private static function calculateSize($pn_image_width, $pn_image_height, $ps_size) {
		if (preg_match("!^([\d]+),$!", $ps_size, $va_matches)) {				// w,
			$vn_width = (int)$va_matches[1];
			$vn_height = (int)($pn_image_height * ($vn_width/$pn_image_width));
		} elseif (preg_match("!^,([\d]+)$!", $ps_size, $va_matches)) {			// ,h
			$vn_height = (int)$va_matches[1];
			$vn_width = (int)($pn_image_width * ($vn_height/$pn_image_height));
		} elseif (preg_match("!^([\d]+),([\d]+)$!", $ps_size, $va_matches)) {	// w,h
			$vn_width = (int)$va_matches[1];
			$vn_height = (int)$va_matches[2];
		} elseif (preg_match("!^pct:([\d]+)$!", $ps_size, $va_matches)) {		// pct:n
			$vn_pct = (int)$va_matches[1];
			
			$vn_width = (int)($pn_image_width * ($vn_pct/100));
			$vn_height = (int)($pn_image_height * ($vn_pct/100));
		} elseif (preg_match("/^!([\d]+),([\d]+)$/", $ps_size, $va_matches)) {	// !w,h
			$vn_scale_factor_w = (int)$va_matches[1]/$pn_image_width;
			$vn_scale_factor_h = (int)$va_matches[2]/$pn_image_height;
			$vn_width = (int)($pn_image_width * (($vn_scale_factor_w < $vn_scale_factor_h) ? $vn_scale_factor_w : $vn_scale_factor_h)); 
			$vn_height = (int)($pn_image_height * (($vn_scale_factor_w < $vn_scale_factor_h) ? $vn_scale_factor_w : $vn_scale_factor_h));	
		} else { 																// full
			$vn_width = $pn_image_width;
			$vn_height = $pn_image_height;
		}
		return ['width' => $vn_width, 'height' => $vn_height];
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image region based upon IIIF {region} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $ps_region IIIF region value 
	 *
	 * @return array Array with 'x', 'y', 'width' and 'height' keys containing calculated offsets, width and height
	 */
	private static function calculateRegion($pn_image_width, $pn_image_height, $ps_region) {
		if (preg_match("!^([\d]+),([\d]+),([\d]+),([\d]+)$!", $ps_region, $va_matches)) {				// x,y,w,h
			$vn_x = $va_matches[1];
			$vn_y = $va_matches[2];
			$vn_w = $va_matches[3];
			$vn_h = $va_matches[4];
		} elseif (preg_match("!^pct:([\d]+),([\d]+),([\d]+),([\d]+)$!", $ps_region, $va_matches)) {		// pct:x,y,w,h
			$vn_x = (int)(($va_matches[1]/100) * $pn_image_width);
			$vn_y = (int)(($va_matches[2]/100) * $pn_image_height);
			$vn_w = (int)(($va_matches[3]/100) * $pn_image_width);
			$vn_h = (int)(($va_matches[4]/100) * $pn_image_height);
		} else { 																						// full
			$vn_x = $vn_w = $pn_image_width;															// full
			$vn_y = $vn_h = $pn_image_height;
		}
		
		return ['x' => $vn_x, 'y' => $vn_y, 'width' => $vn_w, 'height' => $vn_h];
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image rotation and/or reflection based upon IIIF {rotation} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $ps_rotation IIIF rotation value 
	 *
	 * @return array Array with 'angle' and 'reflection' values
	 */
	private static function calculateRotation($pn_image_width, $pn_image_height, $ps_rotation) {
		if (preg_match("!^([\d]+)$!", $ps_rotation, $va_matches)) {				// n
			$vn_rotation = (float)$va_matches[1];
			$vb_reflection = false;
		} elseif (preg_match("/^!([\d]+)$/", $ps_rotation, $va_matches)) {		// !n
			$vn_rotation = (float)$va_matches[1];
			$vb_reflection = true;
		} else { 																// invalid/empty
			$vn_rotation = 0;
			$vb_reflection = false;
		}
		
		return ['angle' => (int)$vn_rotation, 'reflection' => (bool)$vb_reflection];
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image quality using IIIF {quality} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $ps_quality IIIF quality value 
	 *
	 * @return string Quality specifier; one of color, grey, bitonal, default
	 */
	private static function calculateQuality($pn_image_width, $pn_image_height, $ps_quality) {
		$ps_quality = strtolower($ps_quality);
		if (!in_array($ps_quality, ['color', 'grey', 'bitonal', 'default'])) { $ps_quality = 'default'; }
		
		return $ps_quality;
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image format using IIIF {format} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $ps_format IIIF format value 
	 *
	 * @return string mimetype for format, or null if format is unsupported
	 */
	private static function calculateFormat($pn_image_width, $pn_image_height, $ps_format) {
		$ps_format = strtolower($ps_format);
		
		$vs_mimetype = null;
		switch($ps_format) {
			case 'jpg':
				$vs_mimetype = 'image/jpeg';
				break;
			case 'tif':
				$vs_mimetype = 'image/tiff';
				break;
			case 'png':
				$vs_mimetype = 'image/png';
				break;
			case 'gif':
				$vs_mimetype = 'image/gif';
				break;
			case 'jp2':
				$vs_mimetype = 'image/jp2';
				break;
		}
		
		return $vs_mimetype;
	}
	# -------------------------------------------------------
}