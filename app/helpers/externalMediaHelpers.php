<?php
/** ---------------------------------------------------------------------
 * app/helpers/externalMediaHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022-2023 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

# ---------------------------------------
/**
 * Return list of supported formats. By default a list of format codes is returned.
 * The 'full' and 'names' options allow return of additional information.
 *
 * @param array $options Options include:
 *		full = return array will all available information on formats
 *		names = return list of format names for display
 *
 * @return array
 */
function caGetExternalMediaUrlSupportedFormats(?array $options=null) : array {
	$formats = [
		'YOUTUBE' => ['name' => 'YouTube', 'url' => 'https://youtube.com'],
		'VIMEO' => ['name' => 'Vimeo', 'url' => 'https://vimeo.com'],
	];
	if(caGetOption('full', $options, false)) {
		return $formats;
	}
	if(caGetOption('names', $options, false)) {
		$names = [];
		foreach($formats as $k => $f) {
			$names[$k] = $f['name'];
		}
		return $names;
	}
	return array_keys($formats);
}
# ---------------------------------------
/**
 * 
 *
 * @param string $url
 * @param array $options
 * @return array
 */
function caGetExternalMediaUrlInfo(string $url, ?array $options=null) : ?array {
	$parts = parse_url($url);
	if(preg_match("!(youtube|youtu\.be)!i", $url)) {
		parse_str($parts['query'], $query);
		$v = $query['v'] ?? null;
		
		if($v) {
			return ["source" => "YOUTUBE", "code" => $v];
		}
	}
	if(preg_match("!(vimeo)!", $url)) {
		$v = $parts['path'] ?? null;
		if($v) { 
			$v = substr($v, 1);
			return ["source" => "VIMEO", "code" => $v];
		}
	}
	return null;
}
# ---------------------------------------
/**
 * 
 *
 * @param string $url
 * @param array $options Options include:
 *		title = 
 *		width = 
 *		height = 
 * 
 * @return string Embed HTML code or null if URL is invalid or unsupported
 */
function caGetExternalMediaEmbedCode(string $url, ?array $options=null) {
	$info = caGetExternalMediaUrlInfo($url);
	if(!$info) { return null; }
	
	$code = $info['code'];
	$width = caGetOption('width', $options, '100%');
	$height = caGetOption('height', $options, '100%');
	$title = addslashes(caGetOption('title', $options, null));
	
	switch($info['source']) {
		case 'YOUTUBE':
			return "<iframe src=\"https://www.youtube.com/embed/{$code}\" width=\"{$width}\" height=\"{$height}\" title=\"{$title}\" frameborder=\"0\" allowfullscreen></iframe>";
		case 'VIMEO':
			return "<div style=\"padding-bottom: 75%; position: relative;\"><iframe width=\"{$width}\" height=\"{$height}\" title=\"{$title}\" src=\"https://player.vimeo.com/video/{$code}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen\"  style=\"position: absolute; top: 0px; left: 0px;\"></iframe></div>";
	}
	return null;
}
# ---------------------------------------
