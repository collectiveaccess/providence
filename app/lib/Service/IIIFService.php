<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/IIIFService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2026 Whirl-i-Gig
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
		if(defined('__CA_APP_TYPE__') && (__CA_APP_TYPE__ === 'PAWTUCKET') && Configuration::load('authentication.conf')->get('iiif_use_authentication') && !$request->isLoggedIn()) {
			if(!$request->doAuthentication(['noPublicUsers' => true, "dont_redirect" => true, "no_headers" => true])) {
				throw new AccessException(_t('Not logged in'));
			}
		}
		$response->addHeader('Cache-Control', 'max-age=3600, private', true); // Cache all responses for 1 hour.

		$path = array_filter(array_slice(explode("/", $request->getPathInfo()), 3), 'strlen');
		$user_id = $request->getUserID();
		$key = "{$identifier}/{$user_id}/".join("/", $path);
		$ukey = "{$identifier}/{$user_id}";
		
		if (CompositeCache::contains($ukey, 'IIIFUserKeys') && ($tile = CompositeCache::fetch($key, 'IIIFTiles'))) {
		    $response->setContentType(CompositeCache::fetch($key, 'IIIFTileTypes'));
		    $response->addContent($tile);
		    return true;
		}
		
		// BASEURL:		{scheme}://{server}{/prefix}/{identifier}
		// INFO: 		{scheme}://{server}{/prefix}/{identifier}/info.json
		// IMAGE:		{scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
		
		if (sizeof($path) == 0) { 
			$response->setRedirect($request->getFullUrlPath()."/info.json");
			return;
		}
		
		$cache = true;
		$is_info_request = false;
		if (($region = array_shift($path)) == 'info.json') {
			$is_info_request = true;
			$cache = false;
		} else {
			$size = array_shift($path);
			$rotation = array_shift($path);
			list($quality, $format) = explode('.', array_shift($path));
		}
		// Load image
		list($type, $pn_id, $page) = self::parseIdentifier($identifier);

		$vs_image_path = null;
		$highlight = $request->getParameter('highlight', pString);
		$highlight_md5 = $highlight ? md5($highlight) : '';
		
		$highlight_op = null;
		if ($cache && CompositeCache::contains($identifier.$highlight_md5, 'IIIFMediaInfo')) {
			$cache = CompositeCache::fetch($identifier.$highlight_md5,'IIIFMediaInfo');
			$sizes = $cache['sizes'];
			$image_info = $cache['imageInfo'];
			$tilepic_info = $cache['tilepicInfo'];
			$versions = $cache['versions'];
			$media_paths = $cache['mediaPaths'];
			$width = $cache['width'];
			$height = $cache['height'];
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
					$region = $highlight_region;
					
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
					
					$region = join(',', $highlight_region_tmp);
				}
			}
			$media = self::getMediaInstance($identifier, $request);
			
			$t_media = $media['instance'];
			$vs_fldname = $media['field'];
			
			if ($t_media->hasField('access') && $t_media->getAppConfig()->get('iiif_enforce_access')) {
				$access = (int)$t_media->get('access');
				$public_values = caGetUserAccessValues($request);
				if(!in_array($access, $public_values, true)) {
					throw new AccessException(_t('Access denied'));
				}
			}
			
			$minfo = $t_media->getMediaInfo($vs_fldname);
			$width = (int)$minfo['INPUT']['WIDTH'];
			$height = (int)$minfo['INPUT']['HEIGHT'];
			
			$sizes = IIIFService::getAvailableSizes($t_media, $vs_fldname, ['indexByVersion' => true]);
			$image_info = IIIFService::imageInfo($t_media, $vs_fldname, $request);
			$tilepic_info = $t_media->getMediaInfo($vs_fldname, 'tilepic');
			$versions = $t_media->getMediaVersions($vs_fldname);
			
			$media_paths = [];
			foreach($versions as $vs_version) {
				$media_paths[$vs_version] = $t_media->getMediaPath($vs_fldname, $vs_version);
			}
			
			CompositeCache::save($identifier.$highlight_md5, [
				'sizes' => $sizes,
				'imageInfo' => $image_info,
				'tilepicInfo' => $tilepic_info,
				'versions' => $versions,
				'mediaPaths' => $media_paths,
				'width' => $width,
				'height' => $height
			],'IIIFMediaInfo');
		}
	
		if ($is_info_request) {
			// Return JSON-format IIIF metadata
		    $response->setContentType('application/json');
			header("Access-Control-Allow-Origin: *");
			$response->addContent(caFormatJson(json_encode($image_info)));
			return true;
		} else {
			$operations = [];
			
			if(is_array($highlight_op)) {
				$operations[] = ['HIGHLIGHT' => $highlight_op];
			}
			
			// region
			$is_cropped = false;
			$region = IIIFService::calculateRegion($width, $height, $region);
			if (($region['width'] != $width) && ($region['height'] != $height)) {
				$operations[] = ['CROP' => $region];
				$is_cropped = true;
			}
			
			// size	
			$dimensions = IIIFService::calculateSize($width, $height, $size);
			$operations[] = ['SCALE' => $dimensions];
			
			// Can we use a pre-generated tilepic tile for this request?
			$tile_width = $tilepic_info['PROPERTIES']['tile_width'];
			$tile_height = $tilepic_info['PROPERTIES']['tile_height'];
		
			if (
				in_array('tilepic', $versions)
				&&
				!$highlight
				&&
				(
					(($dimensions['width'] == $tile_width) && ($dimensions['height'] == $tile_height))
					||
					((($dimensions['width'] <= $tile_width) || ($dimensions['height'] <= $tile_height))) // && ($dimensions['mode'] == 'incomplete'))
				)
			) {
				$scale_factor = ceil($region['width']/$dimensions['width']);						// magnification = width of region requested/width of returned tile
				$level = floor($tilepic_info['PROPERTIES']['layers'] - log($scale_factor,2));		// tilepic layer # = total # layers  - num of layer with relevant magnification (layers are stored from smallest to largest)
		
				$x = floor(($region['x'])/($scale_factor * $tile_width)); 							// scaled x-origin of tile
				$y = floor(($region['y'])/($scale_factor * $tile_height));							// scaled y-origin of tile
				
				$num_tiles_per_row = ceil(($width/$scale_factor)/$tile_width);					// number of tiles per row for this layer/magnification
				
				// calculate # of tiles in each layer of the image
				if (!CompositeCache::contains($identifier.$highlight_md5, 'IIIFTileCounts')) {
					$tile_counts = [];
					$layer_width = $width;
					$layer_height = $height;
					for($l=$tilepic_info['PROPERTIES']['layers']; $l > 0; $l--) {
						$tile_counts[$l] = ceil($layer_width/$tile_width) * ceil($layer_height/$tile_height);
						$layer_width = ceil($layer_width/2);
						$layer_height = ceil($layer_height/2);
					}
					CompositeCache::save($identifier.$highlight_md5, $tile_counts, 'IIIFTileCounts');
				} else {
					$tile_counts = CompositeCache::fetch($identifier.$highlight_md5, 'IIIFTileCounts');
				}
				
				// calculate tile offset to required layer
				$tile_offset = 0;
				for($i=1; $i < $level; $i++) {
					$tile_offset += $tile_counts[$i];
				}
				
				// tile number = offset to layer + number of tiles in rows above region + number of tiles from left side of image
				$tile = ceil($y * $num_tiles_per_row) + ceil($x) + 1;
				$tile_num = $tile_offset + $tile;
				
				$response->setContentType($tilepic_info['PROPERTIES']['tile_mimetype']);
				
				$tile = TilepicParser::getTileQuickly($media_paths['tilepic'], $tile_num, true);
				CompositeCache::save($key, $tile, 'IIIFTiles');
				CompositeCache::save($ukey, 1, 'IIIFUserKeys');
				CompositeCache::save($key, $tilepic_info['PROPERTIES']['tile_mimetype'], 'IIIFTileTypes');
				$response->addContent($tile);
				return true;
			}
			
			// rotate
			$rotation = IIIFService::calculateRotation($width, $height, $rotation);
			if ($rotation['angle'] != 0) {
				$operations[] = ['ROTATE' => $rotation];
			}
			if ($rotation['reflection']) {
				$operations[] = ['FLIP' => ['direction' => 'horizontal']];
			}
			
			// quality
			$vs_quality = IIIFService::calculateQuality($width, $height, $quality);
			if ($vs_quality && ($vs_quality != 'default')) {
				$operations[] = ['SET' => ['colorspace' => $vs_quality]];
			}
			
			// format
			if (!($vs_mimetype = IIIFService::calculateFormat($width, $height, $format))) {
				$response->setHTTPResponseCode(400, _t('Unsupported format %1', $format));
				return false;
			}
			
			
			// find smallest size that is larger than the target width/height
			// smaller file = less processing time
			$vs_target_version = null;
			$d = null;
			foreach($sizes as $vs_version => $size) {
				$dw = $size['width'] - ($is_cropped ? $width : $dimensions['width']);
				$dh = $size['height'] - ($is_cropped ? $height : $dimensions['height']);
				if (($dw < 0) || ($dh < 0)) { continue; }
				$d = sqrt(pow($dw, 2) + pow($dh,2));
				
				if (is_null($d) || ($d < $d)) { $d = $d; $vs_target_version = $vs_version; }
			}
			
			if ($vs_target_version) {
				$vs_image_path = $media_paths[$vs_target_version];
			} else {
				$vs_image_path = caGetOption(['original', 'large', 'page_preview', 'large_preview'], $media_paths, null);
			}
			
			$vs_output_path = IIIFService::processImage($vs_image_path, $vs_mimetype, $operations, $request);
			
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
	private static function processImage(string $image_path, string $mimetype, array $operations, RequestHTTP $request) {
		$o_media  = new Media();
		if (!$o_media->read($image_path)) { 
			throw new Exception("Cannot open file");
		}
		
		foreach($operations as $i => $operation) {
			foreach($operation as $vs_operation => $params) {
				switch($vs_operation) {
					case 'SCALE':
					case 'CROP':
					case 'ROTATE':
					case 'SET':
					case 'FLIP':
					case 'HIGHLIGHT':
						$o_media->transform($vs_operation, $params);
						break;
				}
			}
		}
		
		$o_media->transform('SET', ['mimetype' => $mimetype]);
		
		return $o_media->write(caGetTempFileName("caIIIF"), $mimetype);
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image size based upon IIIF {size} value
	 *
	 * @param int $image_width Width of source image
	 * @param int $image_height Height of source image
	 * @param $size IIIF size value 
	 *
	 * @return array Array with 'width' and 'height' keys containing calculated width and height
	 */
	private static function calculateSize(int $image_width, int $image_height, string $size) {
		if (preg_match("!^([\d]+),$!", $size, $matches)) {				// w,
			$width = (int)$matches[1];
			$height = (int)($image_height * ($width/$image_width));
			$vs_mode = 'incomplete';
		} elseif (preg_match("!^,([\d]+)$!", $size, $matches)) {			// ,h
			$height = (int)$matches[1];
			$width = (int)($image_width * ($height/$image_height));
			$vs_mode = 'incomplete';
		} elseif (preg_match("!^([\d]+),([\d]+)$!", $size, $matches)) {	// w,h
			$width = (int)$matches[1];
			$height = (int)$matches[2];
			$vs_mode = 'full';
		} elseif (preg_match("!^pct:([\d]+)$!", $size, $matches)) {		// pct:n
			$pct = (int)$matches[1];
			
			$width = (int)($image_width * ($pct/100));
			$height = (int)($image_height * ($pct/100));
			$vs_mode = 'percent';
		} elseif (preg_match("/^!([\d]+),([\d]+)$/", $size, $matches)) {	// !w,h
			$scale_factor_w = (int)$matches[1]/$image_width;
			$scale_factor_h = (int)$matches[2]/$image_height;
			$width = (int)($image_width * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h)); 
			$height = (int)($image_height * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h));	
			$vs_mode = 'fit';
		} else { 																// full
			$width = $image_width;
			$height = $image_height;
			$vs_mode = 'full';
		}
		return ['width' => $width, 'height' => $height, 'mode' => $vs_mode];
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image region based upon IIIF {region} value
	 *
	 * @param int $image_width Width of source image
	 * @param int $image_height Height of source image
	 * @param $region IIIF region value 
	 *
	 * @return array Array with 'x', 'y', 'width' and 'height' keys containing calculated offsets, width and height
	 */
	private static function calculateRegion(int $image_width, int $image_height, string $region) {
		if (preg_match("!^([\d]+),([\d]+),([\d]+),([\d]+)$!", $region, $matches)) {				// x,y,w,h
			$x = $matches[1];
			$y = $matches[2];
			$w = $matches[3];
			$h = $matches[4];
		} elseif (preg_match("!^pct:([\d]+),([\d]+),([\d]+),([\d]+)$!", $region, $matches)) {		// pct:x,y,w,h
			$x = (int)(($matches[1]/100) * $image_width);
			$y = (int)(($matches[2]/100) * $image_height);
			$w = (int)(($matches[3]/100) * $image_width);
			$h = (int)(($matches[4]/100) * $image_height);
		} else { 																						// full
			$x = 0; $w = $image_width;															// full
			$y = 0; $h = $image_height;
		}
		
		return ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h];
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image rotation and/or reflection based upon IIIF {rotation} value
	 *
	 * @param int $image_width Width of source image
	 * @param int $image_height Height of source image
	 * @param $rotation IIIF rotation value 
	 *
	 * @return array Array with 'angle' and 'reflection' values
	 */
	private static function calculateRotation(int $image_width, int $image_height, ?string $rotation) {
		if (preg_match("!^([\d]+)$!", $rotation, $matches)) {				// n
			$rotation = (float)$matches[1];
			$reflection = false;
		} elseif (preg_match("/^!([\d]+)$/", $rotation, $matches)) {		// !n
			$rotation = (float)$matches[1];
			$reflection = true;
		} else { 																// invalid/empty
			$rotation = 0;
			$reflection = false;
		}
		
		return ['angle' => (int)$rotation, 'reflection' => (bool)$reflection];
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image quality using IIIF {quality} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $quality IIIF quality value 
	 *
	 * @return string Quality specifier; one of color, grey, bitonal, default
	 */
	private static function calculateQuality(int $pn_image_width, int $pn_image_height, string $quality) {
		$quality = strtolower($quality);
		if (!in_array($quality, ['color', 'grey', 'bitonal', 'default'])) { $quality = 'default'; }
		
		return $quality;
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image format using IIIF {format} value
	 *
	 * @param int $image_width Width of source image
	 * @param int $image_height Height of source image
	 * @param $format IIIF format value 
	 *
	 * @return string mimetype for format, or null if format is unsupported
	 */
	private static function calculateFormat(int $image_width, int $image_height, ?string $format) {
		$format = strtolower($format);
		
		$mimetype = null;
		switch($format) {
			case 'jpg':
				$mimetype = 'image/jpeg';
				break;
			case 'tif':
				$mimetype = 'image/tiff';
				break;
			case 'png':
				$mimetype = 'image/png';
				break;
			case 'gif':
				$mimetype = 'image/gif';
				break;
		}
		
		return $mimetype;
	}
	# -------------------------------------------------------
	/**
	 * Calculate target image format using IIIF {format} value
	 *
	 * @param int $pn_image_width Width of source image
	 * @param int $pn_image_height Height of source image
	 * @param $format IIIF format value 
	 *
	 * @return array IIIF image information response
	 */
	private static function imageInfo($pt_media, string $fldname, RequestHTTP $request) {
		$sizes = IIIFService::getAvailableSizes($pt_media, $fldname);
		$tilepic_info = $pt_media->getMediaInfo($fldname, 'tilepic');
		
		$scales = [];
		for($i=0; $i < $tilepic_info['PROPERTIES']['layers']; $i++) {
			$scales[] = pow(2,$i);
		}
		$tiles = ['width' => $tilepic_info['PROPERTIES']['tile_width'], 'height' => $tilepic_info['PROPERTIES']['tile_height'], 'scaleFactors' => $scales];

		$vs_base_url = $request->config->get('site_host').$request->getFullUrlPath();
		
		$tmp = explode("/", $vs_base_url);
		if ($i = array_search("service.php", $tmp)) {
			$tmp = array_slice($tmp, 0, $i + 3);
		} elseif ($i = array_search("service", $tmp)) {
			$tmp = array_slice($tmp, 0, $i + 3);
		}
		
		$vs_base_url = join('/', $tmp);
		
		$possible_formats = ['jpg', 'tif', 'tiff', 'png', 'gif'];
		$o_media  = new Media();
		
		$path = null;
		foreach(['original', 'large', 'page_preview', 'large_preview'] as $version) {
			if(($path = $pt_media->getMediaPath($fldname, $version)) && file_exists($path)) { break; }
		}
		
		if(!$path) { throw new ApplicationException(_t('No media path')); }
		
		if (!$o_media->read($path)) { 
			throw new Exception("Cannot open file");
		}
		
		$formats = [];
		foreach($o_media->getOutputFormats() as $vs_mimetype => $vs_ext) {
			if (in_array($vs_ext, $possible_formats)) { 
				$formats[] = ($vs_ext === 'tiff') ? 'tif' : $vs_ext; 
			}
		}
		$minfo = $pt_media->getMediaInfo($fldname);
		$resp = [
			'@context' => 'http://iiif.io/api/image/2/context.json',
			'@id' => $vs_base_url,
			'protocol' => 'http://iiif.io/api/image',
			'width' => (int)$minfo['INPUT']['WIDTH'],
			'height' => (int)$minfo['INPUT']['HEIGHT'],
			'sizes' => $sizes,
			'tiles' => [$tiles],
			'profile' => [
				"http://iiif.io/api/image/2/level2.json",
				[
					'formats' => $formats,
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
		return $resp;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function getAvailableSizes($media, string $fldname, ?array $options=null) {
		$sizes = [];
		foreach($media->getMediaVersions($fldname) as $version) {
			if ($version == 'tilepic') { continue; }
			$w = (int)$media->getMediaInfo($fldname, $version, 'WIDTH');
			$h = (int)$media->getMediaInfo($fldname, $version, 'HEIGHT');
			if(($w <= 0) || ($h <= 0)) { continue; }
			
			$sizes[$version] = ['width' => $w, 'height' => $h];
		}
		return caGetOption('indexByVersion', $options, false) ? $sizes : array_values($sizes);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function parseIdentifier(string $identifier) {
		$identifier_bits = explode(':', $identifier);
		
		if (sizeof($identifier_bits) > 1) {
			$type = $identifier_bits[0];
			$id = (int)$identifier_bits[1];
			$page = isset($identifier_bits[2]) ? (int)$identifier_bits[2] : null;
		} else{
			$id = (int)$identifier_bits[0];
			$page = isset($identifier_bits[1]) ? (int)$identifier_bits[1] : null;
		}
		return [$type, $id, $page];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getMediaInstance(string $identifier, RequestHTTP $request) {
		list($type, $id, $page) = self::parseIdentifier($identifier);
		
		switch($type) {
			case 'attribute':
				if ($page) {
					$t_attr_val = new ca_attribute_values($id);
					$t_attr_val->useBlobAsMediaField(true);
					$t_instance = new ca_attribute_value_multifiles();
					$t_instance->load(['value_id' => $id, 'resource_path' => $page]);
					$t_attr = new ca_attributes($t_attr_val->get('attribute_id'));
					$fldname = 'media';
				} 
				if (!$t_instance || !$t_instance->getPrimaryKey()) {
					$t_instance = new ca_attribute_values($id);
					$t_instance->useBlobAsMediaField(true);
					$fldname = 'value_blob';
					
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
					$t_instance->load(['representation_id' => $id, 'resource_path' => $page]);
				}
				if (!$t_instance || !$t_instance->getPrimaryKey()) {
					$t_instance = new ca_object_representations($id);
				}
				$fldname = 'media';
			
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
				if($t_instance = Datamodel::getInstance($type, true)) {
					$t_instance->load($id);
					if (!$t_instance->getPrimaryKey()) {
						// doesn't exist
						throw new IIIFAccessException(_t('Invalid identifier'), 400);
					}
					if (!$t_instance->isReadable($request)) {
						// not readable
						throw new IIIFAccessException(_t('Access denied'), 403);
					} 
					
					$fldname = null;
				} else {
					throw new IIIFAccessException(_t('Invalid identifier type'), 400);
				}
				break;
		}
		
		return ['instance' => $t_instance, 'field' => $fldname, 'type' => $type, 'id' => $id, 'page' => $page];
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
