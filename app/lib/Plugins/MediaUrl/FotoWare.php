<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrlParser/FotoWare.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2026 Whirl-i-Gig
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
 * @subpackage MediaUrlParser
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\MediaUrl\Plugins;
 
require_once(__CA_LIB_DIR__.'/Plugins/MediaUrl/BaseMediaUrlPlugin.php');
require_once(__CA_LIB_DIR__.'/Clients/FotoWare.php');
 
class FotoWare Extends BaseMediaUrlPlugin {	
	# ------------------------------------------------
	/** 
	 *
	 */
	protected $client = null;
	
	/**
	 *
	 */
	protected $metadata = [];
	
	/**
	 *
	 */
	public function __construct() {
		$this->description = _t('Parses FotoWare URLs');
		
		$this->client = $this->serviceIsConfigured() ? new \Clients\FotoWare\FotoWareClient(['url' => __FOTOWARE_URL__, 'cacheToken' => true]) : null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function register() {
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function serviceIsConfigured() : bool {
		if(defined('__FOTOWARE_URL__') && is_string(__FOTOWARE_URL__) && strlen(__FOTOWARE_URL__)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function checkStatus() {
		$status = parent::checkStatus();
		$status['available'] = $this->serviceIsConfigured(); 
		return $status;
	}
	# ------------------------------------------------
	/**
	 * Attempt to parse FotoWare URL. If valid, transform url for to allow download as PDF
	 * or user-specified format. Supported formats are PDF, Microsoft Excel (xlsx) and Microsoft Word (docx). 
	 *
	 * @param string $url
	 * @param array $options No options are currently supported.
	 *
	 * @return bool|array False if url is not valid, array with information about the url if valid.
	 */
	public function parse(string $url, ?array $options=null) {
		if(!$this->serviceIsConfigured()) { return null; }
		if (!is_array($parsed_url = parse_url(urldecode($url)))) { return null; }
		if(preg_match('!^'.__FOTOWARE_URL__.'!i', $url)) { 
			return ['url' => $url, 'originalUrl' => $url, 'plugin' => 'FotoWare'];
		}
		return false;
	}
	# ------------------------------------------------
	/**
	 * Search
	 *
	 * @param string $search
	 * @param array $options No options are currently supported.
	 *
	 * @return null|array Null if search is not supported, or array with search results
	 */
	public function search(string $search, ?array $options=null) {
		if(!$this->serviceIsConfigured()) { return null; }
		$acc = [];
		if(is_array($res = $this->client->search($search, $options))) {
			foreach($res as $r) {
				$acc[] = [
					'label' => $r['title'],
					'url' => str_replace("/__renditions/ORIGINAL", "", __FOTOWARE_URL__.$r['media']['href']),
					'preview' => __FOTOWARE_URL__.$r['preview']['href']
				];
			}
		}
		return sizeof($acc) ? $acc : null;
	}
	# ------------------------------------------------
	/**
	 * Attempt to fetch content from a FotoWare URL, transforming content to a PDF or other user-specified format (xlsx or docx).
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		filename = File name to use for fetched file. If omitted a random name is generated. [Default is null]
	 *		extension = Extension to use for fetched file. If omitted ".bin" is used as the extension. [Default is null]
	 *		returnAsString = Return fetched content as string rather than in a file. [Default is false]
	 *		dontDownload = Skip download and return file information only. [Default is false]
	 *
	 * @throws UrlFetchException Thrown if fetch URL fails.
	 * @return bool|array|string False if url is not valid, array with path to file with content and format if successful, string with content if returnAsString option is set.
	 */
	public function fetch(string $url, ?array $options=null) {
		if(!$this->serviceIsConfigured()) { return null; }
		if ($p = $this->parse($url, $options)) {
			if(caGetOption(['dont_download', 'dontDownload'], $options, false)) { 
				return array_merge($p, ['file' => null]);
			}
			
			if($dest = caGetOption('filename', $options, null)) {
				$dest .= '.'.caGetOption('extension', $options, '.bin');
			}
			
			$item = $this->client->item($p['url']);
			if(is_null($item['media'] ?? null)) { return false; }
			$format = pathinfo($item['media']['href'], PATHINFO_EXTENSION) ?? 'jpg';
			
			$renditions = \Configuration::load()->getList('fotoware_media_renditions') ?? [];
			$hrefs = [];
			foreach($renditions as $r) {
				if(!isset($item['renditions'][$r])) { continue; }
				if($item['renditions'][$r]['href'] ?? null) {
					$hrefs[] = $item['renditions'][$r]['href'];
				}
			}
			$hrefs[] = $item['media']['href'];
			foreach($hrefs as $href) {
				if($m = $this->client->fetchMedia($href, $tmp_file=__CA_APP_DIR__.'/tmp/'.$item['filename'])) {
					break;
				}
			}
			
			$metadata = [];
			if(is_array($item['fields'])) {
				foreach($item['fields'] as $f => $v) {
					$metadata[$f] = is_array($v) ? $v : [$v];
				}
			}
			if(is_array($item['metadata'])) {
				foreach($item['metadata'] as $f => $v) {
					$label = $v['label'];
					$label_proc = preg_replace("![\s]+!", "_" , preg_replace("![^\w]+!", "_", mb_strtolower($label)));
					$metadata[$label] = $metadata[mb_strtolower($label)] = $metadata[$label_proc] = $metadata[$f] = $v['values'];
				}
			}
			
			if(sizeof($this->metadata) > 255) { $this->metadata = []; }
			$this->metadata[$url] = $metadata;

			
			if (caGetOption('returnAsString', $options, false)) {
				$content = file_get_contents($tmp_file);
				unlink($tmp_file);
				return $content;
			}
			
			if(!$dest) { rename($tmp_file, $tmp_file .= '.'.$format); }
			
			return array_merge($p, ['file' => $tmp_file, 'metadata' => $metadata]);
		}
		return false;
	}
	# ------------------------------------------------
	/**
	 * Attempt to fetch preview from a URL, transforming content to specified format for source.
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		filename = File name to use for fetched file. If omitted a random name is generated. [Default is null]
	 *		extension = Extension to use for fetched file. If omitted ".bin" is used as the extension. [Default is null]
	 *		returnAsString = Return fetched content as string rather than in a file. [Default is false]
	 *
	 * @throws UrlFetchException Thrown if fetch URL fails.
	 * @return bool|array|string False if url is not valid, array with path to file with content and format if successful, string with content if returnAsString option is set.
	 */
	public function fetchPreview(string $url, ?array $options=null) {
		return false;
	}
	# ------------------------------------------------
	/**
	 * Get service-specific HTML embedding tag for media
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		width = Width to apply to embedded content. [Default is 100% width]
	 *		height = Height to use for embedded content. [Default is 100% height]
	 *		title = Title to apply to embedded content. [Default is null]
	 *
	 * @return string HTML embed tag, or null if embedding is not possible
	 */
	public function embedTag(string $url, ?array $options=null) : ?string {		
		if ($p = $this->parse($url, $options)) {
			$width = caGetOption('width', $options, '100%');
			$height = caGetOption('height', $options, '100%');
			$title = addslashes(caGetOption('title', $options, null));
			
			$tag = "<iframe src='{$url}' width='{$width}' height='{$height}' frameborder='0' webkitallowfullscreen='true' mozallowfullscreen='true' allowfullscreen></iframe>";
			return $tag;
		}
		return null;
	}
	# ------------------------------------------------
	/**
	 * Get icon for media
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		size = size of icon, including units (Eg. 64px). [Default is null]
	 *
	 * @return string HTML icon or null if no icon was found
	 */
	public function icon(string $url,  ?array $options=null) : ?string {
		if(!is_null($tag = $this->getConfiguredIcon('FotoWare', 'FotoWare', $options))) {
			return $tag;
		}
		$size = caGetOption('size', $options, null);
		$size_css = $size ? "style='font-size: {$size}'" : '';
		
		return '<i class="fas fa-archive" {$size_css}></i>';
	}
	# ------------------------------------------------
	/**
	 * Get list of formats handled. Keys are format short names, values are full names.
	 *
	 * @return array List of formats supported
	 */
	public function formats() : array {
		return ['FotoWare' => 'FotoWare'];
	}
	# ------------------------------------------------
	/**
	 * Get name of service used to fetch media
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		format = Format of name. Valid values are "full", "short". [Default is full]
	 *
	 * @return string Service name or null if not service name is available.
	 */
	public function service(string $url, ?array $options=null) : ?string {
		$format = caGetOption('format', $options, 'full', ['forceToLowercase' => true]);
		switch($format) {
			case 'short':
				return 'FotoWare';
			default:
				return _t('FotoWare');
		}
	}
	# ------------------------------------------------
	/**
	 * Return parsed metadata for url
	 *
	 * @param string $url
	 * @param array $options No options are current supported
	 *
	 * @return array Array of metadata, or null if not metadata is available for the URL.
	 */
	public function fetchMetadata(string $url, ?array $options=null) : ?array {
		return $this->metadata[$url] ?? null;
	}
	# ------------------------------------------------
}
