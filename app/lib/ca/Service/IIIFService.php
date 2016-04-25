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
			// size	
			$va_dimensions = IIIFService::calculateSize($vn_width, $vn_height, $ps_size);
			//print_R($va_dimensions);die("XXX");
			$va_operations[] = ['SCALE' => $va_dimensions];
			
			$vs_output_path = IIIFService::processImage($vs_image_path, $va_operations, $po_request);
			
			print "gOT $vs_output_path";
		}
		
		
		//$vn_ttl = defined('__CA_SERVICE_API_CACHE_TTL__') ? __CA_SERVICE_API_CACHE_TTL__ : 60*60; // save for an hour by default
		//ExternalCache::save($vs_cache_key, $vm_return, "SimpleAPI_{$ps_endpoint}", $vn_ttl);
		return $vm_return;
	}
	
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function processImage($ps_image_path, $pa_operations, $po_request) {
		$o_media  = new Media();
		if (!$o_media->read($ps_image_path)) { 
			throw new Exception("Cannot open file");
		}
		
		foreach($pa_operations as $vn_i => $va_operation) {
			foreach($va_operation as $vs_operation => $va_params) {
				switch($vs_operation) {
					case 'SCALE':
						$o_media->transform($vs_operation, $va_params);
						break;
				}
			}
		}
		$o_media->write($vs_output_path = "/tmp/TESTFILE", "image/jpeg");
		
		return $vs_output_path;
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
		$vn_aspect_ratio = $pn_image_width/$pn_image_height;
		
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
}
