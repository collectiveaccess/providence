<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/IIIFService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2023 Whirl-i-Gig
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
 
 require_once(__CA_LIB_DIR__."/Media.php");
 require_once(__CA_LIB_DIR__."/Parsers/TilepicParser.php");

class IIIFService {
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $identifier
	 * @param RequestHTTP $request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch(string $identifier, RequestHTTP $request, ResponseHTTP $response) {
		$va_path = array_filter(array_slice(explode("/", $request->getPathInfo()), 3), 'strlen');
		$vs_key = $identifier."/".join("/", $va_path);
		
		if ($vs_tile = CompositeCache::fetch($vs_key, 'IIIFTiles')) {
		    header("Content-type: ".CompositeCache::fetch($vs_key, 'IIIFTileTypes'));
		    $response->addContent($vs_tile);
		    return true;
		}
		
		// BASEURL:		{scheme}://{server}{/prefix}/{identifier}
		// INFO: 		{scheme}://{server}{/prefix}/{identifier}/info.json
		// IMAGE:		{scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
		
		if (sizeof($va_path) == 0) { 
			$response->setRedirect($request->getFullUrlPath()."/info.json");
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
		$pa_identifier = explode(':', $identifier);
		
		list($ps_type, $pn_id, $pn_page) = self::parseIdentifier($identifier);
		
		$vs_image_path = null;
		
		if ($vb_cache && CompositeCache::contains($identifier, 'IIIFMediaInfo')) {
			$va_cache = CompositeCache::fetch($identifier,'IIIFMediaInfo');
			$va_sizes = $va_cache['sizes'];
			$va_image_info = $va_cache['imageInfo'];
			$va_tilepic_info = $va_cache['tilepicInfo'];
			$va_versions = $va_cache['versions'];
			$va_media_paths = $va_cache['mediaPaths'];
			$vn_width = $va_cache['width'];
			$vn_height = $va_cache['height'];
		} else {
			$media = self::getMediaInstance($identifier, $request);
			
			$t_media = $media['instance'];
			$vs_fldname = $media['field'];
			
			$minfo = $t_media->getMediaInfo($vs_fldname);
			$vn_width = (int)$minfo['INPUT']['WIDTH'];
			$vn_height = (int)$minfo['INPUT']['HEIGHT'];
			
			$va_sizes = IIIFService::getAvailableSizes($t_media, $vs_fldname, ['indexByVersion' => true]);
			$va_image_info = IIIFService::imageInfo($t_media, $vs_fldname, $request);
			$va_tilepic_info = $t_media->getMediaInfo($vs_fldname, 'tilepic');
			$va_versions = $t_media->getMediaVersions($vs_fldname);
			
			$va_media_paths = [];
			foreach($va_versions as $vs_version) {
				$va_media_paths[$vs_version] = $t_media->getMediaPath($vs_fldname, $vs_version);
			}
			
			CompositeCache::save($identifier, [
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
			$response->addContent(caFormatJson(json_encode($va_image_info)));
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
					((($va_dimensions['width'] <= $vn_tile_width) || ($va_dimensions['height'] <= $vn_tile_height))) // && ($va_dimensions['mode'] == 'incomplete'))
				)
			) {
				$vn_scale_factor = ceil($va_region['width']/$va_dimensions['width']);						// magnification = width of region requested/width of returned tile
				$vn_level = floor($va_tilepic_info['PROPERTIES']['layers'] - log($vn_scale_factor,2));		// tilepic layer # = total # layers  - num of layer with relevant magnification (layers are stored from smallest to largest)
		
				$x = floor(($va_region['x'])/($vn_scale_factor * $vn_tile_width)); 							// scaled x-origin of tile
				$y = floor(($va_region['y'])/($vn_scale_factor * $vn_tile_height));							// scaled y-origin of tile
				
				$vn_num_tiles_per_row = ceil(($vn_width/$vn_scale_factor)/$vn_tile_width);					// number of tiles per row for this layer/magnification
				
				// calculate # of tiles in each layer of the image
				if (!CompositeCache::contains($identifier, 'IIIFTileCounts')) {
					$va_tile_counts = [];
					$vn_layer_width = $vn_width;
					$vn_layer_height = $vn_height;
					for($vn_l=$va_tilepic_info['PROPERTIES']['layers']; $vn_l > 0; $vn_l--) {
						$va_tile_counts[$vn_l] = ceil($vn_layer_width/$vn_tile_width) * ceil($vn_layer_height/$vn_tile_height);
						$vn_layer_width = ceil($vn_layer_width/2);
						$vn_layer_height = ceil($vn_layer_height/2);
					}
					CompositeCache::save($identifier, $va_tile_counts, 'IIIFTileCounts');
				} else {
					$va_tile_counts = CompositeCache::fetch($identifier, 'IIIFTileCounts');
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
				
				$vs_tile = TilepicParser::getTileQuickly($va_media_paths['tilepic'], $vn_tile_num, true);
				CompositeCache::save($vs_key, $vs_tile, 'IIIFTiles');
				CompositeCache::save($vs_key, $va_tilepic_info['PROPERTIES']['tile_mimetype'], 'IIIFTileTypes');
				$response->addContent($vs_tile);
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
				$response->setHTTPResponseCode(400, _t('Unsupported format %1', $ps_format));
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
				$vs_image_path = caGetOption(['original', 'large', 'page_preview', 'large_preview'], $va_media_paths, null);
			}
			
			$vs_output_path = IIIFService::processImage($vs_image_path, $vs_mimetype, $va_operations, $request);
			
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
	private static function processImage(string $ps_image_path, string $ps_mimetype, array $pa_operations, RequestHTTP $request) {
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
	private static function calculateSize(int $pn_image_width, int $pn_image_height, string $ps_size) {
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
	private static function calculateRegion(int $pn_image_width, int $pn_image_height, string $ps_region) {
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
	private static function calculateRotation(int $pn_image_width, int $pn_image_height, ?string $ps_rotation) {
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
	private static function calculateQuality(int $pn_image_width, int $pn_image_height, string $ps_quality) {
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
	private static function calculateFormat(int $pn_image_width, int $pn_image_height, ?string $ps_format) {
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
	private static function imageInfo($pt_media, string $ps_fldname, RequestHTTP $request) {
		$va_sizes = IIIFService::getAvailableSizes($pt_media, $ps_fldname);
		$va_tilepic_info = $pt_media->getMediaInfo($ps_fldname, 'tilepic');
		
		$va_scales = [];
		for($i=0; $i < $va_tilepic_info['PROPERTIES']['layers']; $i++) {
			$va_scales[] = pow(2,$i);
		}
		$va_tiles = ['width' => $va_tilepic_info['PROPERTIES']['tile_width'], 'height' => $va_tilepic_info['PROPERTIES']['tile_height'], 'scaleFactors' => $va_scales];

		$vs_base_url = $request->config->get('site_host').$request->getFullUrlPath();
		
		$va_tmp = explode("/", $vs_base_url);
		if ($vn_i = array_search("service.php", $va_tmp)) {
			$va_tmp = array_slice($va_tmp, 0, $vn_i + 3);
		}
		
		$vs_base_url = join('/', $va_tmp);
		
		$va_possible_formats = ['jpg', 'tif', 'tiff', 'png', 'gif'];
		$o_media  = new Media();
		
		$path = null;
		foreach(['original', 'large', 'page_preview', 'large_preview'] as $version) {
			if($path = $pt_media->getMediaPath($ps_fldname, $version)) { break; }
		}
		
		if(!$path) { throw new ApplicationException(_t('No media path')); }
		
		if (!$o_media->read($path)) { 
			throw new Exception("Cannot open file");
		}
		
		$va_formats = [];
		foreach($o_media->getOutputFormats() as $vs_mimetype => $vs_ext) {
			if (in_array($vs_ext, $va_possible_formats)) { 
				$va_formats[] = ($vs_ext === 'tiff') ? 'tif' : $vs_ext; 
			}
		}
		$minfo = $pt_media->getMediaInfo($ps_fldname);
		$va_resp = [
			'@context' => 'http://iiif.io/api/image/2/context.json',
			'@id' => $vs_base_url,
			'protocol' => 'http://iiif.io/api/image',
			'width' => (int)$minfo['INPUT']['WIDTH'],
			'height' => (int)$minfo['INPUT']['HEIGHT'],
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
	private static function getAvailableSizes($pt_media, string $ps_fldname, ?array $pa_options=null) {
		$va_sizes = [];
		foreach($pt_media->getMediaVersions($ps_fldname) as $vs_version) {
			if ($vs_version == 'tilepic') { continue; }
			$w = (int)$pt_media->getMediaInfo($ps_fldname, $vs_version, 'WIDTH');
			$h = (int)$pt_media->getMediaInfo($ps_fldname, $vs_version, 'HEIGHT');
			if(($w <= 0) || ($h <= 0)) { continue; }
			
			$va_sizes[$vs_version] = ['width' => $w, 'height' => $h];
		}
		return caGetOption('indexByVersion', $pa_options, false) ? $va_sizes : array_values($va_sizes);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function parseIdentifier(string $identifier) {
		$pa_identifier = explode(':', $identifier);
		
		if (sizeof($pa_identifier) > 1) {
			$ps_type = $pa_identifier[0];
			$pn_id = (int)$pa_identifier[1];
			$pn_page = isset($pa_identifier[2]) ? (int)$pa_identifier[2] : null;
		} else{
			$pn_id = (int)$pa_identifier[0];
			$pn_page = isset($pa_identifier[1]) ? (int)$pa_identifier[1] : null;
		}
		return [$ps_type, $pn_id, $pn_page];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function getMediaInstance(string $identifier, RequestHTTP $request) {
		list($ps_type, $pn_id, $pn_page) = self::parseIdentifier($identifier);
		
		switch($ps_type) {
			case 'attribute':
				if ($pn_page) {
					$t_attr_val = new ca_attribute_values($pn_id);
					$t_attr_val->useBlobAsMediaField(true);
					$t_instance = new ca_attribute_value_multifiles();
					$t_instance->load(['value_id' => $pn_id, 'resource_path' => $pn_page]);
					$t_attr = new ca_attributes($t_attr_val->get('attribute_id'));
					$vs_fldname = 'media';
				} 
				if (!$t_instance || !$t_instance->getPrimaryKey()) {
					$t_instance = new ca_attribute_values($pn_id);
					$t_instance->useBlobAsMediaField(true);
					$vs_fldname = 'value_blob';
					
					$t_attr = new ca_attributes($t_instance->get('attribute_id'));
				}
				
				if ($t_instance = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true)) {
					if ($t_instance->load($t_attr->get('row_id'))) {
						if (!$t_instance->isReadable($request)) {
							// not readable
							throw new IIIFAccessException(_t('Access denied'), 403);
						}
					} else {
						// doesn't exist
						throw new IIIFAccessException(_t('Invalid identifier'), 400);
					}
				} else {
					// doesn't exist
					throw new IIIFAccessException(_t('Invalid identifier'), 400);
				}
			
				break;
			case 'representation':
				if ($pn_page) {
					$t_instance = new ca_object_representation_multifiles();
					$t_instance->load(['representation_id' => $pn_id, 'resource_path' => $pn_page]);
				}
				if (!$t_instance || !$t_instance->getPrimaryKey()) {
					$t_instance = new ca_object_representations($pn_id);
				}
				$vs_fldname = 'media';
			
				if (!$t_instance->getPrimaryKey()) {
					// doesn't exist
					throw new IIIFAccessException(_t('Invalid identifier'), 400);
				}
				if (!$t_instance->isReadable($request)) {
					// not readable
					throw new IIIFAccessException(_t('Access denied'), 403);
				} 
				break;
			default:
				if($t_instance = Datamodel::getInstance($ps_type, true)) {
					$t_instance->load($pn_id);
					if (!$t_instance->getPrimaryKey()) {
						// doesn't exist
						throw new IIIFAccessException(_t('Invalid identifier'), 400);
					}
					if (!$t_instance->isReadable($request)) {
						// not readable
						throw new IIIFAccessException(_t('Access denied'), 403);
					} 
					
					$vs_fldname = null;
				} else {
					throw new IIIFAccessException(_t('Invalid identifier type'), 400);
				}
				break;
		}
		
		return ['instance' => $t_instance, 'field' => $vs_fldname, 'type' => $ps_type, 'id' => $ps_id, 'page' => $pn_page];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function manifest($identifiers, RequestHTTP $request) : array {
		$o_config = Configuration::load();
		$base_url = $o_config->get('site_host').$o_config->get('ca_url_root'); //.$request->getBaseUrlPath();
		
		$manifest_url = '';
		if(isset($_SERVER['REQUEST_URI'])) {
			$manifest_url = $o_config->get('site_host').$_SERVER['REQUEST_URI'];
		} else {
			$manifest_url = $base_url.join(":", $identifiers)."/manifest";
		}
		
		if(!is_array($identifiers)) { $identifiers = [$identifiers]; }
		$json = [
			'@context' => 'http://iiif.io/api/presentation/3/context.json',
			'id' => $manifest_url,
			'type' => 'Manifest',
			'label' => ['none' => []],
			'metadata' => [],
			//'requiredStatement' => ['label' => ['none' => ['TODO']]],
			//'rights' => 'TODO',
			//'thumbnail' => null,
			//'seeAlso' => null,
			//'homepage' => null,
			//'partOf' => null,
			'items' => []
		];
	
		foreach($identifiers as $identifier) {
			if(!is_array($media = self::getMediaInstance($identifier, $request))) {
				throw new IIIFAccessException(_t('Unknown error'), 400);
			}
			
			// $item = [
// 				'id' => $base_url.$identifier,
// 				'type' => 'Canvas',
// 				'label' => ['none' => [$media['instance']->get('preferred_labels')]],
// 				'width' => null,
// 				'height' => null
// 			];
			$mwidth = $mheight = null;
			
			switch($media['type']) {
				case 'representation':
				
					break;
				case 'attribute':
				
					break;
				default:
					$reps = $media['instance']->getRepresentations(['original', 'thumbnail', 'preview170', 'medium', 'h264_hi', 'mp3'], null, ['includeAnnotations' => true]);
					
					$replist = [];
					
					foreach($reps as $rep) {
						$w = $rep['info']['original']['WIDTH'];
						$h = $rep['info']['original']['HEIGHT'];
						
						if(is_null($mwidth) || ($w > $mwidth)) { $mwidth = $w; }
						if(is_null($mheight) || ($h > $mheight)) { $mheight = $h; }
						
						$page = 1; // @TODO: fix
						
						$service_url = "{$base_url}/service.php/IIIF/representation:{$rep['representation_id']}:{$page}";
						
						$thumb_width = $rep['info']['thumbnail']['WIDTH'];
						$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
						$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
						
						$base_iiif_id = $manifest_url.'-'.$rep['representation_id'];
						
						$rep_mimetype = $rep['info']['original']['MIMETYPE'];
						
						$rep_media_class = caGetMediaClass($rep_mimetype, ['forIIIF' => true]);
						
						$services = null;
						$media_url = null;
						$placeholder_url = $placeholder_width = $placeholder_height = $placeholder_mimetype = null;
						$thumb_url = $thumb_width = $thumb_height = $thumb_mimetype = null;
						$d = null;
						
						$annotations = [];
						switch($rep_media_class) {
							case 'Image':
								$services = [
									[
										'id' => $service_url,
										'type' => 'ImageService2',
										'profile' => 'http://iiif.io/api/image/2/level2.json"'
									]
								];
								$media_url = $service_url.'/full/max/0/default.jpg';
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
												
								$thumb_url = $rep['urls']['thumbnail'];				
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								break;
							case 'Video':
								if(!($media_url = ($rep['urls']['h264_hi'] ?? null))) {
									$media_url = $rep['urls']['original'];
								}
								$d = $rep['info']['original']['PROPERTIES']['duration'];
								
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
								
								$thumb_url = $rep['urls']['thumbnail'];
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								if(is_array($rep['captions']) && sizeof($rep['captions'])) {
									foreach($rep['captions'] as $ci => $caption_info) {
										$annotations[] =
											[
												'id' => $base_iiif_id.'-annotation-subtitles-'.$ci,
												'type' => 'AnnotationPage',
												'items' => [
													[
													  'id' => $base_iiif_id.'-annotation-subtitles-vtt-'.$ci,
													  'type' => 'Annotation',
													  'motivation' => 'supplementing',
													  'body' => [
														'id' => $caption_info['url'],
														'type' => 'Text',
														'format' => 'text/vtt',
														'label' => [
														  'en' => ['Subtitles']
														],
														'language' => substr($caption_info['locale_code'], 0, 2)
													  ],
													  'target' => $base_iiif_id
													],
												],
											];
									}
								}
								if($rep['num_annotations'] > 0) {
									$annotations[] =
											[
												'id' => $base_iiif_id.'-annotation-clips-'.$ci,
												'type' => 'AnnotationPage',
												'items' => [
													[
													  'id' => $base_iiif_id.'-annotation-clips-json-'.$ci,
													  'type' => 'Annotation',
													  'motivation' => 'supplementing',
													  'body' => [
														'id' => preg_replace("!/IIIF/manifest/.*$!", "/IIIF/cliplist/", $manifest_url)."representation:".$rep['representation_id'],
														'type' => 'Text',
														'format' => 'text/vtt',
														'label' => [
														  'en' => ['Clips']
														],
														'language' => substr($caption_info['locale_code'], 0, 2)
													  ],
													  'target' => $base_iiif_id
													],
												],
											];
								}
								break;
							case 'Sound':
								if(!($media_url = ($rep['urls']['mp3'] ?? null))) {
									$media_url = $rep['urls']['original'];
								}
								$d = $rep['info']['original']['PROPERTIES']['duration'];
								
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
								
								$thumb_url = $rep['urls']['thumbnail'];
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								break;
							case 'Text':
								if(!($media_url = $rep['urls']['compressed'] ?? null)) {
									$media_url = $rep['urls']['original'];
								}
								
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
								
								$thumb_url = $rep['urls']['thumbnail'];
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								break;
						}
						
						if($rep['label'] === '[BLANK]') { $rep['label'] = ''; }
						$repinfo = [
							'id' => $base_iiif_id,
							'type' => 'Canvas',
							'label' => ['none' => [$rep['label']]],
							'width' => $w,
							'height' => $h,
							'duration' => $d,
							'thumbnail' => [[
								'id' => $thumb_url,
								'type' => 'Image',
								'format' => $thumb_mimetype,
								'width' => $thumb_width,
								'height' => $thumb_height
							]],
							'items' => [
								[
									'id' => $base_iiif_id.'-item-page',
									'type' => 'AnnotationPage',
									'items' => [
										[
											'id' => $base_iiif_id.'-annotation',
											'type' => 'Annotation',
											'motivation' => 'painting',
											'target' => $base_iiif_id,
											'body' => [
												'id' => $media_url,
												'type' => $rep_media_class,
												'format' => $rep_mimetype,
												'width' => $w,
												'height' => $h,
												'duration' => $d,
												'service' => $services
											],
										]
									],
								]
							],
							'annotations' => $annotations,
							'placeholderCanvas' => [
								'id' => $base_iiif_id.'-placeholder',
								'type' => 'Canvas',
								'width' => $placeholder_width,
								'height' => $placeholder_height,
								'items' => [
									[
										'id' => $base_iiif_id.'-placeholder-annotation-page',
										'type' => 'AnnotationPage',
										'items' => [
											[
												'id' => $base_iiif_id.'-placeholder-annotation',
												'type' => 'Annotation',
												'motivation' => 'painting',
												'body' => [
												  'id' => $placeholder_url,
												  'type' => 'Image',
												  'format' => $placeholder_mimetype,
												  'width' => $placeholder_width,
												  'height' => $placeholder_height
												],
												'target' => $base_iiif_id.'-placeholder'
											]
										]
									]
								]
							]
						];
						
						if(!$services) {
							unset($repinfo['items'][0]['items'][0]['body']['service']);
						}
						if(!$d) {
							unset($repinfo['duration']);
							unset($repinfo['items'][0]['items'][0]['body']['duration']);
						}
						
						if(!$w) {
							unset($repinfo['items'][0]['items'][0]['body']['width']);
							unset($repinfo['items'][0]['items'][0]['body']['height']);
						}
						
						$replist[] = $repinfo;
					}
					break;
			}
			
			$json['items'] = $replist;
		}
		return $json;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function cliplist($identifier, RequestHTTP $request, ?array $options=null) {
		if(!is_array($media = self::getMediaInstance($identifier, $request))) {
			throw new IIIFAccessException(_t('Unknown error'), 400);
		}
		
		$vtt = caGetOption('vtt', $options, false);
		
		$t_media = $media['instance'];

  		$clip_list = [];
		if(is_array($annotations = $t_media->getAnnotations(['vtt' => true]))) {
			foreach($annotations as $annotation) {
				if($vtt) {
					$clip_list[] = "{$annotation['startTimecode_vtt']} --> {$annotation['endTimecode_vtt']}\n{$annotation['label']}";
				} else {
					$clip_list[] = [
						'identifier' => $annotation['annotation_id'],
						'text' => $annotation['label'],
						'start' => $annotation['startTimecode_vtt'],
						'end' => $annotation['endTimecode_vtt']
					];
				}
			}
		}
		
		if($vtt) {
			return "WEBVTT \n\n".join("\n\n", $clip_list);
		}
		return $clip_list;
	}
	# -------------------------------------------------------
}
