<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/IIIFService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Search/Common/Stemmer/SnoballStemmer.php');

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
		if(defined('__CA_APP_TYPE__') && (__CA_APP_TYPE__ === 'PROVIDENCE') && !$request->isLoggedIn()) {
			throw new AccessException(_t('Not logged in'));
		}
		
		$response->addHeader('Cache-Control', 'max-age=3600, private', true); // Cache all responses for 1 hour.

		$va_path = array_filter(array_slice(explode("/", $request->getPathInfo()), 3), 'strlen');
		$vs_key = $identifier."/".join("/", $va_path);
		
		if ($vs_tile = CompositeCache::fetch($vs_key, 'IIIFTiles')) {
		    $response->setContentType(CompositeCache::fetch($vs_key, 'IIIFTileTypes'));
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
			$vb_cache = false;
		} else {
			$ps_size = array_shift($va_path);
			$ps_rotation = array_shift($va_path);
			list($ps_quality, $ps_format) = explode('.', array_shift($va_path));
		}
		// Load image
		$pa_identifier = explode(':', $identifier);
		
		list($ps_type, $pn_id, $page) = self::parseIdentifier($identifier);

		$vs_image_path = null;
		//$vb_cache = false;
		$highlight = $request->getParameter('highlight', pString);
		$highlight_md5 = $highlight ? md5($highlight) : '';
		
		$highlight_op = null;
		if ($vb_cache && CompositeCache::contains($identifier.$highlight_md5, 'IIIFMediaInfo')) {
			$va_cache = CompositeCache::fetch($identifier.$highlight_md5,'IIIFMediaInfo');
			$va_sizes = $va_cache['sizes'];
			$va_image_info = $va_cache['imageInfo'];
			$va_tilepic_info = $va_cache['tilepicInfo'];
			$va_versions = $va_cache['versions'];
			$va_media_paths = $va_cache['mediaPaths'];
			$vn_width = $va_cache['width'];
			$vn_height = $va_cache['height'];
		} else {
			if($highlight) {
				$tmp = explode(':', $identifier);
				$base_identifier = join(':', array_slice($tmp, 0, 2));	// trim page
				$res = self::search($base_identifier, ['q' => $highlight]);
				if(is_array($res) && is_array($res['items']) && sizeof($res['items'])) {
					// target is in the format: page-56098-5#xywh=1007,680,62,15
					$target = $res['items'][0]['target'];
					$tmp = explode('#', $target);
					$page_tmp = explode('-', $tmp[0]);
					$page = (int)$page_tmp[2];
					
					$highlight_region = str_replace("xywh=", "", $tmp[1]);
					$highlight_region_tmp = explode(',', $highlight_region);
					$ps_region = $highlight_region;
					
					$identifier = $base_identifier.':'.$page;
					
					$highlight_op = [
						'x' => $highlight_region_tmp[0], 
						'y' => $highlight_region_tmp[1], 
						'width' => $highlight_region_tmp[2],
						'height' => $highlight_region_tmp[3],
						'color' => '#eded91'	// TODO: make color configureable
					];
					
					// TODO: configurable margin?
					$highlight_region_tmp[0] -= 200;
					$highlight_region_tmp[1] -= 200;
					$highlight_region_tmp[2] += 400;
					$highlight_region_tmp[3] += 400;
					
					if($highlight_region_tmp[0] < 0) { $highlight_region_tmp[0] = 0; }
					if($highlight_region_tmp[1] < 0) { $highlight_region_tmp[1] = 0; }
					
					$ps_region = join(',', $highlight_region_tmp);
				}
			}
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
			
			CompositeCache::save($identifier.$highlight_md5, [
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
		    $response->setContentType('application/json');
			header("Access-Control-Allow-Origin: *");
			$response->addContent(caFormatJson(json_encode($va_image_info)));
			return true;
		} else {
			$va_operations = [];
			
			if(is_array($highlight_op)) {
				$va_operations[] = ['HIGHLIGHT' => $highlight_op];
			}
			
			// region
			$is_cropped = false;
			$va_region = IIIFService::calculateRegion($vn_width, $vn_height, $ps_region);
			if (($va_region['width'] != $vn_width) && ($va_region['height'] != $vn_height)) {
				$va_operations[] = ['CROP' => $va_region];
				$is_cropped = true;
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
				!$highlight
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
				if (!CompositeCache::contains($identifier.$highlight_md5, 'IIIFTileCounts')) {
					$va_tile_counts = [];
					$vn_layer_width = $vn_width;
					$vn_layer_height = $vn_height;
					for($vn_l=$va_tilepic_info['PROPERTIES']['layers']; $vn_l > 0; $vn_l--) {
						$va_tile_counts[$vn_l] = ceil($vn_layer_width/$vn_tile_width) * ceil($vn_layer_height/$vn_tile_height);
						$vn_layer_width = ceil($vn_layer_width/2);
						$vn_layer_height = ceil($vn_layer_height/2);
					}
					CompositeCache::save($identifier.$highlight_md5, $va_tile_counts, 'IIIFTileCounts');
				} else {
					$va_tile_counts = CompositeCache::fetch($identifier.$highlight_md5, 'IIIFTileCounts');
				}
				
				// calculate tile offset to required layer
				$vn_tile_offset = 0;
				for($vn_i=1; $vn_i < $vn_level; $vn_i++) {
					$vn_tile_offset += $va_tile_counts[$vn_i];
				}
				
				// tile number = offset to layer + number of tiles in rows above region + number of tiles from left side of image
				$vn_tile = ceil($y * $vn_num_tiles_per_row) + ceil($x) + 1;
				$vn_tile_num = $vn_tile_offset + $vn_tile;
				
				$response->setContentType($va_tilepic_info['PROPERTIES']['tile_mimetype']);
				
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
				$dw = $va_size['width'] - ($is_cropped ? $vn_width : $va_dimensions['width']);
				$dh = $va_size['height'] - ($is_cropped ? $vn_height : $va_dimensions['height']);
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
			$response->setContentType($vs_mimetype);
			$response->sendHeaders();
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
					case 'HIGHLIGHT':
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
		} elseif ($vn_i = array_search("service", $va_tmp)) {
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
			"maxWidth" => (int)$minfo['INPUT']['WIDTH']
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
	public static function parseIdentifier(string $identifier) {
		$pa_identifier = explode(':', $identifier);
		
		if (sizeof($pa_identifier) > 1) {
			$ps_type = $pa_identifier[0];
			$pn_id = (int)$pa_identifier[1];
			$page = isset($pa_identifier[2]) ? (int)$pa_identifier[2] : null;
		} else{
			$pn_id = (int)$pa_identifier[0];
			$page = isset($pa_identifier[1]) ? (int)$pa_identifier[1] : null;
		}
		return [$ps_type, $pn_id, $page];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getMediaInstance(string $identifier, RequestHTTP $request) {
		list($ps_type, $pn_id, $page) = self::parseIdentifier($identifier);
		
		switch($ps_type) {
			case 'attribute':
				if ($page) {
					$t_attr_val = new ca_attribute_values($pn_id);
					$t_attr_val->useBlobAsMediaField(true);
					$t_instance = new ca_attribute_value_multifiles();
					$t_instance->load(['value_id' => $pn_id, 'resource_path' => $page]);
					$t_attr = new ca_attributes($t_attr_val->get('attribute_id'));
					$vs_fldname = 'media';
				} 
				if (!$t_instance || !$t_instance->getPrimaryKey()) {
					$t_instance = new ca_attribute_values($pn_id);
					$t_instance->useBlobAsMediaField(true);
					$vs_fldname = 'value_blob';
					
					$t_attr = new ca_attributes($t_instance->get('attribute_id'));
				}
				
				if ($t_row = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true)) {
					if ($t_row->load($t_attr->get('row_id'))) {
						if (!$t_row->isReadable($request)) {
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
				if ($page) {
					$t_instance = new ca_object_representation_multifiles();
					$t_instance->load(['representation_id' => $pn_id, 'resource_path' => $page]);
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
		
		return ['instance' => $t_instance, 'field' => $vs_fldname, 'type' => $ps_type, 'id' => $pn_id, 'page' => $page];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function manifest($identifiers, ?array $options=null) : array {
		if(!$identifiers) { return null; }
		if(!is_array($identifiers)) { $identifiers = [$identifiers]; }
		
		$render = caGetOption('render', $options, 'MixedMedia');
		if(!$render) { $render = 'MixedMedia'; }
		$class = "\\CA\\Media\\IIIFManifests\\{$render}";
		if(class_exists($class))  {
			$manifest = new $class();
		} else {
			throw new IIIFAccessException(_t('Invalid render mode %1', $render), 400);	
		}
	
		return $manifest->manifest($identifiers);
	}
	# -------------------------------------------------------
	/*/
	 *
	 */
	private static function _tokenize(string $content) : array {
		$content = array_filter(array_map(function($v) {
			return preg_replace("/[^[:alnum:][:space:]]/u", '', $v);
		}, caTokenizeString($content)), function($x) { return strlen($x); });
		
		return $content;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function search($identifier, ?array $options=null) : ?array {
		global $g_request;
		if(!$identifier) { return null; }
		$media = self::getMediaInstance($identifier, $g_request);
		$target = caGetOption('target', $options, null);
		$q = caGetOption('q', $options, null);
		$exact = caGetOption('exact', $options, false, ['castAs' => 'boolean']);
		$anywhere = caGetOption('anywhere', $options, false, ['castAs' => 'boolean']);
		$anything = caGetOption('anything', $options, false, ['castAs' => 'boolean']);
		
		if($anything) { 
			$exact = $anywhere = true;
		}

		$tokens = self::_tokenize($q);
		$token_count = sizeof($tokens);
		
		$image_width = caGetOption('width', $options, null);
		$image_height = caGetOption('height', $options, null);
		
		// Do in-page search
		$page_data_files = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/newspaper_data/'.$media['instance']->getPrimaryKey());
		$data = [];
		
		$files = array_values($media['instance']->getFileList());
		
		foreach($page_data_files as $p => $page_data_file) {
			$file_info = $files[$p];
			
			$page_data = json_decode(file_get_contents($page_data_file), true);
			$locations = $page_data['locations'];
			
			if($image_width && $image_height){
				$sw  = $image_width;
				$sh = $image_height;
			} else {
				$sw = $file_info['original_width'];
				$sh = $file_info['original_height'];
			}
			
			$offset = 0;
			foreach($tokens as $tindex => $t) {
				$token_locations = self::_getLocations($t, $locations, ['exact' => $exact]);
				if($token_locations) {
					foreach($token_locations as $c) {
						$is_ok = true;
						
						if(!$anywhere){ 
							if(($tindex > 0) && (!isset($data[$p+1][$c['i']-$tindex]))) { 
								$is_ok = false;
							} elseif($tindex < ($token_count - 1)){
								$is_ok = false;
								$ft = $tokens[$tindex + 1];
								$forward_locations = self::_getLocations($ft, $locations, ['exact' => $exact]);
								if(is_array($forward_locations)) {
									foreach($forward_locations as $fl) {
										if($fl['i'] == ($c['i'] + 1)) {
											$is_ok = true;
											break;
										}
									}
								} else {
									$is_ok = false;
								}
							}
							if(!$is_ok) { continue; }
						}
						
						if(($tindex > 0) && !$anywhere) {
							if(isset($data[$p+1][$c['i']-($tindex-$offset)])) {
								if(abs(((int)($c['y'] * $sh)) - $data[$p+1][$c['i'] - ($tindex-$offset)]['y']) < 8) {
									$data[$p+1][$c['i'] - ($tindex - $offset)]['width'] = (int)(($c['x'] + $c['w']) * $sw) - $data[$p+1][$c['i'] - ($tindex - $offset)]['x'];
									$data[$p+1][$c['i'] - ($tindex - $offset)]['value'] .= ' '.$t;
									$data[$p+1][$c['i'] - ($tindex - $offset)]['c']++;
								} else {
									// new line
									$data[$p+1][$c['i']] = [
										'value' => $t,
										'x' => (int)($c['x'] * $sw),
										'y' => (int)($c['y'] * $sh),
										'width' => (int)($c['w'] * $sw),
										'height' => (int)($c['h'] * $sh),
										'c' => 0,
										'partial' => true
									];
									$data[$p+1][$c['i'] - $tindex]['partial'] = true;
									$offset = $tindex;
								}
							}
						} else {
							$data[$p+1][$c['i']] = [
								'value' => $t,
								'x' => (int)($c['x'] * $sw),
								'y' => (int)($c['y'] * $sh),
								'width' => (int)($c['w'] * $sw),
								'height' => (int)($c['h'] * $sh),
								'c' => 0,
								'partial' => false
							];
						}
					}	
				}
			}
			if(($token_count > 1) && !$anywhere) {
				foreach($data as $p => $pdata){
					$data[$p] = array_filter($pdata, function($v) use($token_count){
						return (($v['partial']) || ($v['c'] === ($token_count - 1)));
					});
				}
			}
		}
		
		if(!$exact && !sizeof($data)) {
			return self::search($identifier, array_merge($options, ['exact' => true]));
		}
		if(!$anywhere && !sizeof($data)) {
			return self::search($identifier, array_merge($options, ['exact' => false, 'anywhere' => true]));
		}
		if(!$anything && !sizeof($data)) {
			return self::search($identifier, array_merge($options, ['anything' => true]));
		}
		$search = new \CA\Media\IIIFResponses\Search();
		return $search->response($data, ['identifiers' => [$identifier], 'target' => $target]);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _getLocations($token, $locations, ?array $options=null) {
		$exact = caGetOption('exact', $options, false);
		
		if($exact) {
			return $locations[$token] ?? null;
		}
		
		$stemmer = new SnoballStemmer();
		
		$token = $stemmer->stem($token);
		$words = array_keys($locations);
		$fwords = array_filter($words, function($v) use ($token, $stemmer) {
			$v = $stemmer->stem(trim($v));
			return preg_match("!^".preg_quote($token, '!')."!ui", $v);
		});
	
		$acc = [];
		foreach($fwords as $fword) {
			$acc = array_merge($acc, $locations[$fword]);
		}
		return $acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function cliplist($identifier, RequestHTTP $request, ?array $options=null) {
		global $g_locale_id;
		if(!$request->isLoggedIn()) {
			$auth_success = $request->doAuthentication(['dont_redirect' => true, 'noPublicUsers' => false, "no_headers" => true]);
		}
		if(!is_array($media = self::getMediaInstance($identifier, $request))) {
			throw new IIIFAccessException(_t('Unknown error'), 400);
		}
		$data = json_decode($request->getRawPostData() ?? null, true);
		
		$mode = caGetOption('mode', $options, $request->getParameter('mode', pString));
		$canvas = caGetOption('canvas', $data, $request->getParameter('canvas', pString));
		$canvas_bits = explode('-', $canvas);
		$filter_to_page = $canvas_bits[2] ?? null;
		if(intval($filter_to_page) < 1) {
			$filter_to_page = null;	
		}
		
		$t_media = $media['instance'];
		$representation_id = $t_media->getPrimaryKey();
		
		$annotation_type = $t_media->getAnnotationType();
		$is_timebased = in_array($annotation_type, ['TimeBasedAudio', 'TimeBasedVideo']);
		
  		$annotations = $t_media->getAnnotations(['vtt' => $is_timebased]) ?? [];
  		$t_media->annotationMode('user');
  		$annotations = array_merge($annotations, $t_media->getAnnotations(['vtt' => $is_timebased, 'session_id' => Session::getSessionID(), 'user_id' => $request->getUserID()]) ?? []);
  		
  		$method = $request->getRequestMethod();
  		
  		$files = $t_media->getFileList(null, $page, 1, ['returnAllVersions' => true]) ?? [];
		$files = array_values($files);
		
		if(is_array($data)) {
			if(is_array($data) && sizeof($data)) {
				switch(strtoupper($method)) {
					case 'DELETE':
						if($id = ($data['annotation']['id'] ?? null)) {
							if($t_anno = ca_user_representation_annotations::findAsInstance(['representation_id' => $representation_id, 'annotation_id' => $id])) {
								// TODO: check ownership
								if($t_anno->delete(true)) {
									$annotations = array_filter($annotations, function($v) use ($id) {
										return ($id != $v['annotation_id']);
									});
								}
							}
						}
						break;
					case 'GET':
					case 'POST':
					case 'PUT':
						if(($coords = $data['annotation']['target']['selector']['value'] ?? null)) {
							$id = ($data['annotation']['id'] ?? null);
							
							$coords = preg_replace('!^xywh=!', '', $coords);
							$coords = explode(',', $coords);
							
							$tmp = explode('-', $data['annotation']['target']['source']['id'] ?? '');
							
							$page = $tmp[2] ?? 1;
							$page_info = $files[$page - 1];
							$page_width = $page_info['original_width'];
							$page_height = $page_info['original_height'];
							
							$properties = [
								'page' => $page,
								'x' => $coords[0]/$page_width,
								'y' => $coords[1]/$page_height,
								'w' => $coords[2]/$page_width,
								'h' => $coords[3]/$page_height
							];
							if(!($title = $data['annotation']['body']['value'] ?? null) && is_array($data['annotation']['body'])) {
								foreach($data['annotation']['body'] as $b) {
									if($b['type'] === 'TextualBody') {
										$title = $b['value'];
										break;
									}
								}
							}
							if(!$title) { $title = _t('Clipping'); }
							
							if($id && is_numeric($id) && 
								(
									$t_anno = $t_media->editAnnotation($id, 'en_US', $properties, 0, 0, [], ['returnAnnotation' => true])
								)
							) {
								$t_anno->replaceLabel(['name' => $title], 'en_US', null, true);
							} elseif(
								$id && ($anno_id = ca_user_representation_annotations::find(['idno' => $id], ['returnAs' => 'firstId']))
								&&
								($t_anno = $t_media->editAnnotation($anno_id, 'en_US', $properties, 0, 0, [], ['returnAnnotation' => true]))
							) {
								$t_anno->replaceLabel(['name' => $title], 'en_US', null, true);
							} else {
								$t_media->addAnnotation($title, 'en_US', $request->getUserID(), $properties, 0, 0, ['idno' => $id], ['forcePreviewGeneration' => true]);
							}
						}
						break;
				}
			}
		}

  		$clip_list = [];
  		
		if(is_array($annotations) && sizeof($annotations)) {
			foreach($annotations as $annotation) {
				if(!is_null($filter_to_page) && ($filter_to_page != (int)$annotation['page'])) { continue; }
				switch($annotation_type) {
					case 'TimeBasedAudio':
					case 'TimeBasedVideo':
						if($mode === 'vtt') {
							$clip_list[] = "{$annotation['startTimecode_vtt']} --> {$annotation['endTimecode_vtt']}\n{$annotation['label']}";
						} else {
							$clip_list[] = [
								'identifier' => $annotation['annotation_id'],
								'text' => $annotation['label'],
								'start' => $annotation['startTimecode_vtt'],
								'end' => $annotation['endTimecode_vtt']
							];
						}
						break;
					case 'Document':
						$page_info = $files[$annotation['page'] - 1];
						$page_width = $page_info['original_width'];
						$page_height = $page_info['original_height'];
						$mimetype = $page_info['original_mimetype'];
						
						$clip_list[] = [
							'label' => $annotation['label'],
							'identifier' => $annotation['annotation_id'],
							'representation_id' => $annotation['representation_id'],
							'preview' => $annotation['preview_url_thumbnail'],// TODO generalize version
							'x' => $annotation['x'] * $page_width,
							'y' => $annotation['y'] * $page_height,
							'w' => $annotation['w'] * $page_width,
							'h' => $annotation['h'] * $page_height,
							'mimetype' => $mimetype,
							'page' => $annotation['page']
						];
						break;
				}
			}
		}
		switch($mode) {
			case 'iiif':
				$clip_response = new \CA\Media\IIIFResponses\Clips();
				return $clip_response->response([], ['identifiers' => [$identifier], 'clip_list' => $clip_list]);
			case 'vtt':
				return "WEBVTT \n\n".join("\n\n", $clip_list);
			default:
				return $clip_list;
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function manifestUrl() : string {
		$config = Configuration::load();
		if(isset($_SERVER['REQUEST_URI'])) {
			return  $config->get('site_host').$_SERVER['REQUEST_URI'];
		} else {
			return $config->get('site_host').$config->get('ca_url_root')."/manifest";
		}
	}
	# -------------------------------------------------------
}
