<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrlParser/GoogleDrive.php :
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
 
class GoogleDrive Extends BaseMediaUrlPlugin {	
	# ------------------------------------------------
	/**
	 *
	 */
	private static $mimetypes = [
		'pdf' => ['application/pdf'],
		'xlsx' => ['application/msexcel', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
		'docx' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
	];
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->description = _t('Parses GoogleDrive URLs');
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
	 * Attempt to parse GoogleDrive URL. If valid, transform url for to allow download as PDF
	 * or user-specified format. Supported formats are PDF, Microsoft Excel (xlsx) and Microsoft Word (docx). 
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		format = Preferred format to grab GoogleDrive resource in, if possible. May be ignored is format is not possible for resource. Valid values are pdf, xlsx, docx. [Default is pdf]
	 *
	 * @return bool|array False if url is not valid, array with information about the url if valid.
	 */
	public function parse(string $url, array $options=null) {
		if (!is_array($parsed_url = parse_url(urldecode($url)))) { return null; }
 		
 		$format = caGetOption('format', $options, null, ['validValues' => [null, 'pdf', 'xlsx', 'docx']]);
		$tmp = explode('/', $parsed_url['path']);
		array_pop($tmp); $tmp[] = 'export';
		$path = join("/", $tmp);
		
		$url_stub = $parsed_url['scheme']."://".$parsed_url['host'].$path; 
		if (!isUrl($url_stub) || !preg_match('!^https://(docs|drive).google.com/(spreadsheets|file|document)/d/!', $url_stub, $m)) {
			return false;
		}
		if (!$format) {
			switch($m[2]) {
				case 'spreadsheets':
					$format = 'xlsx';
					break;
				case 'document':
					$format = 'docx';
					break;
				default:
					$format = 'pdf';
					break;
			}
		}
		$transformed_url = $format ? "{$url_stub}?format={$format}" : $url_stub;
		
		// Get doc title
 		$content = file_get_contents($url);
 		$filename = preg_match('!<meta property="og:title" content="([^"]+)">!', $content, $m) ? preg_replace('![^A-Za-z0-9\.\-_]+!', '_', $m[1]) : 'document';
 
		return ['url' => $transformed_url, 'originalUrl' => $url, 'format' => $format, 'plugin' => 'GoogleDrive', 'originalFilename' => "{$filename}.{$format}"];
	}
	# ------------------------------------------------
	/**
	 * Attempt to fetch content from a GoogleDrive URL, transforming GoogleDoc content to a PDF or other user-specified format (xlsx or docx).
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		filename = File name to use for fetched file. If omitted a random name is generated. [Default is null]
	 *		extension = Extension to use for fetched file. If omitted ".bin" is used as the extension. [Default is null]
	 *		format = Preferred format to grab GoogleDrive resource in, if possible. May be ignored is format is not possible for resource. Valid values are pdf, xlsx, docx. [Default is pdf]
	 *		returnAsString = Return fetched content as string rather than in a file. [Default is false]
	 *
	 * @throws UrlFetchException Thrown if fetch URL fails.
	 * @return bool|array|string False if url is not valid, array with path to file with content and format if successful, string with content if returnAsString option is set.
	 */
	public function fetch(string $url, array $options=null) {
		if ($p = $this->parse($url, $options)) {
 			$format = $p['format']; //caGetOption('format', $options, null, ['validValues' => [null, 'pdf', 'xlsx', 'docx']]);
			if($dest = caGetOption('filename', $options, null)) {
				$dest .= '.'.caGetOption('extension', $options, '.bin');
			}
			
			$tmp_file = caFetchFileFromUrl($p['url'], $dest, ['mimetype' => self::$mimetypes[$format]]);
			
			if (caGetOption('returnAsString', $options, false)) {
				$content = file_get_contents($tmp_file);
				unlink($tmp_file);
				return $content;
			}
			
			if(!$dest) { rename($tmp_file, $tmp_file .= '.'.$format); }
			
			return array_merge($p, ['file' => $tmp_file, 'format' => $format]);
		}
		return false;
	}
	# ------------------------------------------------
}
