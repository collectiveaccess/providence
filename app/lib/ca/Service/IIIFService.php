<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/IIIFService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2017 Whirl-i-Gig
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
 require_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
 require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
 require_once(__CA_MODELS_DIR__."/ca_attribute_values.php");

class IIIFService {
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $ps_identifier
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch($ps_identifier, $po_request, $po_response) {
		$va_path = array_slice(explode("/", $po_request->getPathInfo()), 3);
		
		// BASEURL:		{scheme}://{server}{/prefix}/{identifier}
		// INFO: 		{scheme}://{server}{/prefix}/{identifier}/info.json
		// IMAGE:		{scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
		
		if (sizeof($va_path) == 0) { 
			$po_response->setRedirect($po_request->getFullUrlPath()."/info.json");
			return;
		}
		
		$vb_cache = true;
		$pb_is_info_request = false;
		if (($ps_region = array_shift($va_path)) == 'info.json') {
			$pb_is_info_request = true;
			//$vb_cache = false;
		} else {
			$ps_size = array_shift($va_path);
			$ps_rotation = array_shift($va_path);
			list($ps_quality, $ps_format) = explode('.', array_shift($va_path));
		}
		// Load image
		$pa_identifier = explode(':', $ps_identifier);
		
		if (sizeof($pa_identifier) > 1) {
			$ps_type = $pa_identifier[0];
			$pn_id = (int)$pa_identifier[1];
			$pn_page = isset($pa_identifier[2]) ? (int)$pa_identifier[2] : null;
		} else{
			$pn_id = (int)$pa_identifier[0];
			$pn_page = isset($pa_identifier[1]) ? (int)$pa_identifier[1] : null;
		}
		
		$vs_image_path = null;
		
		if ($vb_cache && CompositeCache::contains($ps_identifier, 'IIIFMediaInfo')) {
			$va_cache = CompositeCache::fetch($ps_identifier,'IIIFMediaInfo');
			$va_sizes = $va_cache['sizes'];
			$va_image_info = $va_cache['imageInfo'];
			$va_tilepic_info = $va_cache['tilepicInfo'];
			$va_versions = $va_cache['versions'];
			$va_media_paths = $va_cache['mediaPaths'];
			$vn_width = $va_cache['width'];
			$vn_height = $va_cache['height'];
		} else {
			switch($ps_type) {
				case 'attribute':
					if ($pn_page) {
						$t_attr_val = new ca_attribute_values($pn_id);
						$t_attr_val->useBlobAsMediaField(true);
						$t_media = new ca_attribute_value_multifiles();
						$t_media->load(['value_id' => $pn_id, 'resource_path' => $pn_page]);
						$t_attr = new ca_attributes($t_attr_val->get('attribute_id'));
						$vs_fldname = 'media';
					} 
					if (!$t_media || !$t_media->getPrimaryKey()) {
						$t_media = new ca_attribute_values($pn_id);
						$t_media->useBlobAsMediaField(true);
						$vs_fldname = 'value_blob';
						
						$t_attr = new ca_attributes($t_media->get('attribute_id'));
					}
					
					$o_dm = Datamodel::load();
					if ($t_instance = $o_dm->getInstanceByTableNum($t_attr->get('table_num'), true)) {
						if ($t_instance->load($t_attr->get('row_id'))) {
							if (!$t_instance->isReadable($po_request)) {
								// not readable
								$po_response->setHTTPResponseCode(403, _t('Access denied'));
								return false;
							}
						} else {
							// doesn't exist
							$po_response->setHTTPResponseCode(400, _t('Invalid identifier'));
							return false;
						}
					} else {
						// doesn't exist
						$po_response->setHTTPResponseCode(400, _t('Invalid identifier'));
						return false;
					}
				
					break;
				case 'representation':
				default:
					if ($pn_page) {
						$t_media = new ca_object_representation_multifiles();
						$t_media->load(['representation_id' => $pn_id, 'resource_path' => $pn_page]);
					}
					if (!$t_media || !$t_media->getPrimaryKey()) {
						$t_media = new ca_object_representations($pn_id);
					}
					$vs_fldname = 'media';
				
					if (!$t_media->getPrimaryKey()) {
						// doesn't exist
						$po_response->setHTTPResponseCode(400, _t('Invalid identifier'));
						return false;
					}
					if (!$t_media->isReadable($po_request)) {
						// not readable
						$po_response->setHTTPResponseCode(403, _t('Access denied'));
						return false;
					} 
					break;
			}
			 
			$vn_width = $t_media->getMediaInfo($vs_fldname, 'original', 'WIDTH');
			$vn_height = $t_media->getMediaInfo($vs_fldname, 'original', 'HEIGHT');
			
			$va_sizes = IIIFService::getAvailableSizes($t_media, $vs_fldname, ['indexByVersion' => true]);
			$va_image_info = IIIFService::imageInfo($t_media, $vs_fldname, $po_request);
			$va_tilepic_info = $t_media->getMediaInfo($vs_fldname, 'tilepic');
			$va_versions = $t_media->getMediaVersions($vs_fldname);
			
			$va_media_paths = [];
			foreach($va_versions as $vs_version) {
				$va_media_paths[$vs_version] = $t_media->getMediaPath($vs_fldname, $vs_version);
			}
			
			CompositeCache::save($ps_identifier, [
				'sizes' => $va_sizes,
				'imageInfo' => $va_image_info,
				'tilepicInfo' => $va_tilepic_info,
				'versions' => $va_versions,
				'mediaPaths' => $va_media_paths,
				'width' => $vn_width,
				'height' => $vn_height
			],'IIIFMediaInfo');
		}
		
		if ($pb_is_info_request) {
			// Return JSON-format IIIF metadata
			header("Content-type: text/json");
			header("Access-Control-Allow-Origin: *");
			$po_response->addContent(caFormatJson(json_encode($va_image_info)));
			return true;
		} else {
			$va_operations = [];
			
			// region
			$va_region = IIIFService::calculateRegion($vn_width, $vn_height, $ps_region);
			if (($va_region['width'] != $vn_width) && ($va_region['height'] != $vn_height)) {
				$va_operations[] = ['CROP' => $va_region];
			}
			
			// size	
			$va_dimensions = IIIFService::calculateSize($vn_width, $vn_height, $ps_size);
			$va_operations[] = ['SCALE' => $va_dimensions];
			
			// Can we use a pre-generated tilepic tile for this request?
			$vn_tile_width = $va_tilepic_info['PROPERTIES']['tile_width'];
			$vn_tile_height = $va_tilepic_info['PROPERTIES']['tile_height'];
			
			if (
				in_array('tilepic', $va_versions)
				&&
				(
					(($va_dimensions['width'] == $vn_tile_width) && ($va_dimensions['height'] == $vn_tile_height))
					||
					((($va_dimensions['width'] <= $vn_tile_width) || ($va_dimensions['height'] <= $vn_tile_height)) && ($va_dimensions['mode'] == 'incomplete'))
				)
			) {
				$vn_scale_factor = ceil($va_region['width']/$va_dimensions['width']);						// magnification = width of region requested/width of returned tile
				$vn_level = floor($va_tilepic_info['PROPERTIES']['layers'] - log($vn_scale_factor,2));		// tilepic layer # = total # layers  - num of layer with relevant magnification (layers are stored from smallest to largest)
		
				$x = floor(($va_region['x'])/($vn_scale_factor * $vn_tile_width)); 							// scaled x-origin of tile
				$y = floor(($va_region['y'])/($vn_scale_factor * $vn_tile_height));							// scaled y-origin of tile
				
				$vn_num_tiles_per_row = ceil(($vn_width/$vn_scale_factor)/$vn_tile_width);					// number of tiles per row for this layer/magnification
				
				// calculate # of tiles in each layer of the image
				if (!CompositeCache::contains($ps_identifier, 'IIIFTileCounts')) {
					$va_tile_counts = [];
					$vn_layer_width = $vn_width;
					$vn_layer_height = $vn_height;
					for($vn_l=$va_tilepic_info['PROPERTIES']['layers']; $vn_l > 0; $vn_l--) {
						$va_tile_counts[$vn_l] = ceil($vn_layer_width/$vn_tile_width) * ceil($vn_layer_height/$vn_tile_height);
						$vn_layer_width = ceil($vn_layer_width/2);
						$vn_layer_height = ceil($vn_layer_height/2);
					}
					CompositeCache::save($ps_identifier, $va_tile_counts, 'IIIFTileCounts');
				} else {
					$va_tile_counts = CompositeCache::fetch($ps_identifier, 'IIIFTileCounts');
				}
				
				// calculate tile offset to required layer
				$vn_tile_offset = 0;
				for($vn_i=1; $vn_i < $vn_level; $vn_i++) {
					$vn_tile_offset += $va_tile_counts[$vn_i];
				}
				
				// tile number = offset to layer + number of tiles in rows above region + number of tiles from left side of image
				$vn_tile = ceil($y * $vn_num_tiles_per_row) + ceil($x) + 1;
				$vn_tile_num = $vn_tile_offset + $vn_tile;
				
				header("Content-type: ".$va_tilepic_info['PROPERTIES']['tile_mimetype']);
				$po_response->addContent(TilepicParser::getTileQuickly($va_media_paths['tilepic'], $vn_tile_num, true));
				return true;
			}
			
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
				$po_response->setHTTPResponseCode(400, _t('Unsupported format %1', $ps_format));
				return false;
			}
			
			
			// find smallest size that is larger than the target width/height
			// smaller file = less processing time
			$vs_target_version = null;
			$vn_d = null;
			foreach($va_sizes as $vs_version => $va_size) {
				$dw = $va_size['width'] - $va_dimensions['width'];
				$dh = $va_size['height'] - $va_dimensions['height'];
				if (($dw < 0) || ($dh < 0)) { continue; }
				$d = sqrt(pow($dw, 2) + pow($dh,2));
				
				if (is_null($vn_d) || ($d < $vn_d)) { $vn_d = $d; $vs_target_version = $vs_version; }
			}
			
			if ($vs_target_version) {
				$vs_image_path = $va_media_paths[$vs_target_version];
			} else {
				$vs_image_path = $va_media_paths['original'];
			}
			
			$vs_output_path = IIIFService::processImage($vs_image_path, $vs_mimetype, $va_operations, $po_request);
			
			// TODO: should we be caching output?
		
			header("Content-type: {$vs_mimetype}");
			header("Content-length: ".filesize($vs_output_path));
			header("Access-Control-Allow-Origin: *");
			
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
		
		return $o_media->write(caGetTempFileName("caIIIF"), $ps_mimetype);
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
			$vs_mode = 'incomplete';
		} elseif (preg_match("!^,([\d]+)$!", $ps_size, $va_matches)) {			// ,h
			$vn_height = (int)$va_matches[1];
			$vn_width = (int)($pn_image_width * ($vn_height/$pn_image_height));
			$vs_mode = 'incomplete';
		} elseif (preg_match("!^([\d]+),([\d]+)$!", $ps_size, $va_matches)) {	// w,h
			$vn_width = (int)$va_matches[1];
			$vn_height = (int)$va_matches[2];
			$vs_mode = 'full';
		} elseif (preg_match("!^pct:([\d]+)$!", $ps_size, $va_matches)) {		// pct:n
			$vn_pct = (int)$va_matches[1];
			
			$vn_width = (int)($pn_image_width * ($vn_pct/100));
			$vn_height = (int)($pn_image_height * ($vn_pct/100));
			$vs_mode = 'percent';
		} elseif (preg_match("/^!([\d]+),([\d]+)$/", $ps_size, $va_matches)) {	// !w,h
			$vn_scale_factor_w = (int)$va_matches[1]/$pn_image_width;
			$vn_scale_factor_h = (int)$va_matches[2]/$pn_image_height;
			$vn_width = (int)($pn_image_width * (($vn_scale_factor_w < $vn_scale_factor_h) ? $vn_scale_factor_w : $vn_scale_factor_h)); 
			$vn_height = (int)($pn_image_height * (($vn_scale_factor_w < $vn_scale_factor_h) ? $vn_scale_factor_w : $vn_scale_factor_h));	
			$vs_mode = 'fit';
		} else { 																// full
			$vn_width = $pn_image_width;
			$vn_height = $pn_image_height;
			$vs_mode = 'full';
		}
		return ['width' => $vn_width, 'height' => $vn_height, 'mode' => $vs_mode];
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
			$vn_x = 0; $vn_w = $pn_image_width;															// full
			$vn_y = 0; $vn_h = $pn_image_height;
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
		}
		
		return $vs_mimetype;
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image format using IIIF {format} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $ps_format IIIF format value 
	 *
	 * @return array IIIF image information response
	 */
	private static function imageInfo($pt_media, $ps_fldname, $po_request) {
		$va_sizes = IIIFService::getAvailableSizes($pt_media, $ps_fldname);
		$va_tilepic_info = $pt_media->getMediaInfo($ps_fldname, 'tilepic');
		
		$va_scales = [];
		for($i=0; $i < $va_tilepic_info['PROPERTIES']['layers']; $i++) {
			$va_scales[] = pow(2,$i);
		}
		$va_tiles = ['width' => $va_tilepic_info['PROPERTIES']['tile_width'], 'height' => $va_tilepic_info['PROPERTIES']['tile_height'], 'scaleFactors' => $va_scales];

		$vs_base_url = $po_request->config->get('site_host').$po_request->getFullUrlPath();
		
		$va_tmp = explode("/", $vs_base_url);
		if ($vn_i = array_search("service.php", $va_tmp)) {
			$va_tmp = array_slice($va_tmp, 0, $vn_i + 3);
		}
		
		$vs_base_url = join('/', $va_tmp);
		
		$va_possible_formats = ['jpg', 'tif', 'tiff', 'png', 'gif'];
		$o_media  = new Media();
		if (!$o_media->read($pt_media->getMediaPath($ps_fldname, 'original')) && !$o_media->read($pt_media->getMediaPath($ps_fldname, 'large'))) { 
			throw new Exception("Cannot open file");
		}
		
		$va_formats = [];
		foreach($o_media->getOutputFormats() as $vs_mimetype => $vs_ext) {
			if (in_array($vs_ext, $va_possible_formats)) { 
				$va_formats[] = ($vs_ext === 'tiff') ? 'tif' : $vs_ext; 
			}
		}
		
		$va_resp = [
			'@context' => 'http://iiif.io/api/image/2/context.json',
			'@id' => $vs_base_url,
			'protocol' => 'http://iiif.io/api/image',
			'width' => $vn_width = (int)$pt_media->getMediaInfo($ps_fldname, 'original', 'WIDTH'),
			'height' => (int)$pt_media->getMediaInfo($ps_fldname, 'original', 'HEIGHT'),
			'sizes' => $va_sizes,
			'tiles' => [$va_tiles],
			'profile' => [
				"http://iiif.io/api/image/2/level2.json",
				[
					'formats' => $va_formats,
					'qualities' =>  ['color', 'grey', 'bitonal'],
					'supports' => [
						'mirroring', 'rotationArbitrary', 'regionByPct', 'regionByPx', 'rotationBy90s',
      					'sizeAboveFull', 'sizeByForcedWh', 'sizeByH', 'sizeByPct', 'sizeByW', 'sizeByWh',
      					'baseUriRedirect'
					]
				]
			],
			"maxWidth" => (int)$vn_width
		];
		
		return $va_resp;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function getAvailableSizes($pt_media, $ps_fldname, $pa_options=null) {
		$va_sizes = [];
		foreach($pt_media->getMediaVersions($ps_fldname) as $vs_version) {
			if ($vs_version == 'tilepic') { continue; }
			$va_sizes[$vs_version] = ['width' => (int)$pt_media->getMediaInfo($ps_fldname, $vs_version, 'WIDTH'), 'height' => (int)$pt_media->getMediaInfo($ps_fldname, $vs_version, 'HEIGHT')];
		}
		return caGetOption('indexByVersion', $pa_options, false) ? $va_sizes : array_values($va_sizes);
	}
	# -------------------------------------------------------
}