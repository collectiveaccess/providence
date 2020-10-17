<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrlParser/InternetArchive.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 
 /**
  *
  */
  require_once(__CA_LIB_DIR__.'/Plugins/MediaUrl/BaseMediaUrlPlugin.php');
 
class InternetArchive Extends BaseMediaUrlPlugin {	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->description = _t('Parses InternetArchive URLs');
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
	 * Attempt to parse InternetArchive URL. If valid, transform url for to allow download as PDF
	 * or user-specified format. Supported formats are PDF, Microsoft Excel (xlsx) and Microsoft Word (docx). 
	 *
	 * @param string $url
	 * @param array $options No options are currently supported.
	 *
	 * @return bool|array False if url is not valid, array with information about the url if valid.
	 */
	public function parse(string $url, array $options=null) {
		if (!is_array($parsed_url = parse_url(urldecode($url)))) { return null; }
		
		//Ex. We have an item whose url is https://archive.org/details/CTC_1988_08_17
		//We would generally pull the mp3 file directly using a url like: http://www.archive.org/download/CTC_1988_08_17/CTC_1988_08_17c_44-1.mp3
 		
 		//<meta property="og:video" content="https://archive.org/download/CTC_1988_08_17/CTC_1988_08_17a_44-1.mp3">
 		
 		if(!preg_match('!\.archive\.org$!', $parsed_url['host']) && !preg_match('!^archive\.org$!', $parsed_url['host'])) { return false; }
 		
 		// Is straight download URL?
 		if (preg_match('!^/download/[A-Za-z0-9_\-\.]+/([A-Za-z0-9_\-\.]+)!', $parsed_url['path'])) {
 			return ['url' => $url, 'originalUrl' => $url, 'plugin' => 'InternetArchive'];
 		}
 		
 		// Is detail URL?
 		if (preg_match('!^/details/[A-Za-z0-9_\-\.]+!', $parsed_url['path'], $m)) {
 			$content = file_get_contents($url);
 			if(preg_match('!<meta property="og:video" content="([^"]+)">!', $content, $m)) {
 				return ['url' => $m[1], 'originalUrl' => $url, 'plugin' => 'InternetArchive', 'originalFilename' => pathInfo($m[1], PATHINFO_BASENAME)];
 			}
 		}
 		
		return false;
	}
	# ------------------------------------------------
	/**
	 * Attempt to fetch content from a InternetArchive URL, transforming content to a PDF or other user-specified format (xlsx or docx).
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
	public function fetch(string $url, array $options=null) {
		if ($p = $this->parse($url, $options)) {
			if($dest = caGetOption('filename', $options, null)) {
				$dest .= '.'.caGetOption('extension', $options, '.bin');
			}
			
			$tmp_file = caFetchFileFromUrl($p['url'], $dest);
			
			if (caGetOption('returnAsString', $options, false)) {
				$content = file_get_contents($tmp_file);
				unlink($tmp_file);
				return $content;
			}
			
			if(!$dest) { rename($tmp_file, $tmp_file .= '.'.$format); }
			
			return array_merge($p, ['file' => $tmp_file]);
		}
		return false;
	}
	# ------------------------------------------------
}
