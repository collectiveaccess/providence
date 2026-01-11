<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrlParser/_File.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2025 Whirl-i-Gig
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
 * @subpackage MediaUrl
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\MediaUrl\Plugins;
 
 /**
  *
  */
  require_once(__CA_LIB_DIR__.'/Plugins/MediaUrl/BaseMediaUrlPlugin.php');
 
class _File Extends BaseMediaUrlPlugin {	
	# ------------------------------------------------
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->description = _t('Parses file URLs');
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
	public function checkStatus() {
		$status = parent::checkStatus();
		$status['available'] = is_array($this->register()); 
		return $status;
	}
	# ------------------------------------------------
	/**
	 * Attempt to parse URL as binary file. This should only fail if the URL returns an error status.
	 *
	 * @param string $url
	 * @param array $options No options are currently supported.
	 *
	 * @return bool|array False if url is not valid, array with information about the url if valid.
	 */
	public function parse(string $url, ?array $options=null) {
		if (!isUrl($url)) { return false; }
		return ['url' => $url, 'originalUrl' => $url, 'format' => pathinfo($url, PATHINFO_EXTENSION), 'plugin' => 'File', 'originalFilename' => pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_BASENAME)];
	}
	# ------------------------------------------------
	/**
	 * Attempt to fetch content from a URL as a binary file. This should only fail if the URL returns an error status.
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
		if ($p = $this->parse($url, $options)) {
			if(caGetOption(['dont_download', 'dontDownload'], $options, false)) { 
				return array_merge($p, ['file' => null]);
			}
 			if($dest = caGetOption('filename', $options, null)) {
				$dest .= '.'.caGetOption('extension', $options, '.bin');
			}
			
			$tmp_file = caFetchFileFromUrl($p['url'], $dest);
			
			if (caGetOption('returnAsString', $options, false)) {
				$content = file_get_contents($tmp_file);
				unlink($tmp_file);
				return $content;
			}
			
			return array_merge($p, ['file' => $tmp_file, 'format' => pathinfo($url, PATHINFO_EXTENSION)]);
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
		if(!is_null($tag = $this->getConfiguredIcon('_File', '_File', $options))) {
			return $tag;
		}
		
		$size = caGetOption('size', $options, null);
		$size_css = $size ? "style='font-size: {$size}'" : '';
		
		return "<i class='fas fa-file' {$size_css}></i>";
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
				return 'URL';
			default:
				return _t('File URL');
		}
	}
	# ------------------------------------------------
}
