<?php
/** ---------------------------------------------------------------------
 * app/helpers/videoHelpers.php : miscellaneous video helpers
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @subpackage helpers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__."/core/Parsers/getid3/getid3.php");
require_once(__CA_LIB_DIR__."/core/Parsers/OggParser.php");

# ------------------------------------------------------------------------------------------------
/**
 * Divine file format with media info
 * @param string $ps_path
 * @return string mime type usable for media processing back-end, e.g. video/mp4
 */
function caMediaInfoGuessFileFormat($ps_path) {
	if(!caMediaInfoInstalled()) { return false; }

	$va_media_metadata = caExtractMetadataWithMediaInfo($ps_path);
	
	switch($va_media_metadata['VIDEO']['Format']) {
		case 'DV':
			return 'video/x-dv';
		case 'MPEG-4':
		case 'AVC':
			return 'video/mp4';
		case 'AVI':
			return 'video/avi';
		case 'Matroska':
			return 'video/x-matroska';
		// @todo add more popular formats here!
		default:
			return false;
	}
}
# ------------------------------------------------------------------------------------------------
/**
 * Divine file format with getID3
 * @param $ps_path
 * @return bool|string
 */
function caGetID3GuessFileFormat($ps_path) {
	if($va_getid3_info = caExtractMetadataWithGetID3($ps_path)) {
		if($va_getid3_info['mime_type']) {
			return $va_getid3_info["mime_type"];
		}
	}

	return false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Divine file format with OggParser
 * @param $ps_path
 * @return bool|string
 */
function caOggParserGuessFileFormat($ps_path) {
	$va_ogg_info = caExtractMediaMetadataWithOggParser($ps_path);

	if (is_array($va_ogg_info) && (sizeof($va_ogg_info) > 0)) {
		if (isset($va_ogg_info['theora'])) {
			return 'video/ogg';
		}
	}

	return false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Extract media metadata with OggParser
 * @param string $ps_filepath
 * @return array|bool
 */
function caExtractMediaMetadataWithOggParser($ps_filepath) {
	if(MemoryCache::contains($ps_filepath, 'OggParserMediaMetadata')) {
		return MemoryCache::fetch($ps_filepath, 'OggParserMediaMetadata');
	}

	$o_ogg = new OggParser($ps_filepath);
	if ($o_ogg->LastError) { return false; }

	$va_ogg_info = $o_ogg->Streams;
	$va_ogg_info['mime_type'] = 'video/ogg';
	$va_ogg_info['playtime_seconds'] = $va_ogg_info['duration'];

	MemoryCache::save($ps_filepath, $va_ogg_info, 'OggParserMediaMetadata');

	return $va_ogg_info;
}
# ------------------------------------------------------------------------------------------------
/**
 * Extract media metadata with get id3
 * @param string $ps_filepath
 * @return array|bool
 */
function caExtractMetadataWithGetID3($ps_filepath) {
	if(MemoryCache::contains($ps_filepath, 'GetID3MediaMetadata')) {
		return MemoryCache::fetch($ps_filepath, 'GetID3MediaMetadata');
	}

	$ID3 = new getID3();
	$ID3->option_max_2gb_check = false;
	$va_getid3_info = $ID3->analyze($ps_filepath);
	if (!isset($va_getid3_info["mime_type"])) { return false; }

	// force MPEG-4 files to use video/mp4 mimetype rather than the video/quicktime
	// mimetype getID3 returns. This will allow us to distinguish MPEG-4 files, which can
	// be played in HTML5 and Flash players from older Quicktime files which cannot.
	if ($va_getid3_info["mime_type"] === 'video/quicktime') {
		if (caGetID3IsMpeg4($va_getid3_info)) {
			$va_getid3_info["mime_type"] = 'video/mp4';
		}
	}

	//
	// Versions of getID3 to at least 1.7.7 throw an error that should be a warning
	// when parsing MPEG-4 files, so we supress it here, otherwise we'd never be able
	// to parse MPEG-4 files.
	//
	if ((isset($va_getid3_info["error"])) && (is_array($va_getid3_info["error"])) && (sizeof($va_getid3_info["error"]) == 1)) {
		if (preg_match("/does not fully support MPEG-4/", $va_getid3_info['error'][0])) {
			$va_getid3_info['error'] = array();
		}
		if (preg_match("/claims to go beyond end-of-file/", $va_getid3_info['error'][0])) {
			$va_getid3_info['error'] = array();
		}
		if (preg_match("/because beyond 2GB limit of PHP filesystem functions/", $va_getid3_info['error'][0])) {
			$va_getid3_info['error'] = array();
		}
	}

	MemoryCache::save($ps_filepath, $va_getid3_info, 'GetID3MediaMetadata');

	return $va_getid3_info;
}
# ------------------------------------------------------------------------------------------------
/**
 * Extracts media metadata using MediaInfo
 *
 * @param string $ps_filepath file path
 * @param string $ps_mediainfo_path optional path to MediaInfo binary. If omitted the path configured in external_applications.conf is used.
 *
 * @return array Extracted metadata
 */
function caExtractMetadataWithMediaInfo($ps_filepath, $ps_mediainfo_path=null){
	if(!$ps_mediainfo_path) { $ps_mediainfo_path = caGetExternalApplicationPath('mediainfo'); }
	if (!caIsValidFilePath($ps_mediainfo_path)) { return false; }

	if(MemoryCache::contains($ps_filepath, 'MediaInfoMetadata')) {
		return MemoryCache::fetch($ps_filepath, 'MediaInfoMetadata');
	}

	//
	// TODO: why don't we parse this from the XML output like civilized people?
	//
	exec($ps_mediainfo_path." ".caEscapeShellArg($ps_filepath), $va_output, $vn_return);
	$vs_cat = "GENERIC";
	$va_return = array();
	foreach($va_output as $vs_line){
		$va_split = explode(":",$vs_line);
		$vs_left = trim(array_shift($va_split));
		$vs_right = trim(join(":", $va_split));
		if(strlen($vs_right) == 0){ // category line
			$vs_cat = strtoupper($vs_left);
			continue;
		}
		if(strlen($vs_left) && strlen($vs_right)) {
			if($vs_left!="Complete name"){ // we probably don't want to display temporary filenames
				$va_return[$vs_cat][$vs_left] = $vs_right;
			}
		}
	}

	MemoryCache::save($ps_filepath, $va_return, 'MediaInfoMetadata');
	return $va_return;
}
# ------------------------------------------------------------------------------------------------
/**
 * Extract video duration using MediaInfo. This can be used as a fallback to getID3
 * @param string $ps_filepath
 *
 * @return float|null
 */
function caExtractVideoFileDurationWithMediaInfo($ps_filepath) {
	$ps_mediainfo_path = caGetExternalApplicationPath('mediainfo');
	if(!caMediaInfoInstalled($ps_mediainfo_path)) { return false; }

	$va_output = array();
	exec($ps_mediainfo_path.' --Inform="Video;%Duration/String3%" '.caEscapeShellArg($ps_filepath), $va_output, $vn_return);
	if(!is_array($va_output) || (sizeof($va_output) != 1)) { return null; }
	$va_tmp = explode(':', array_shift($va_output));

	if(sizeof($va_tmp)==3) { // should have hours, minutes, seconds
		return round(intval($va_tmp[0]) * 3600 + intval($va_tmp[1]) * 60 + floatval($va_tmp[2]));
	}

	return null;
}
# ------------------------------------------------------------------------------------------------
/**
 * Figure out if a file analyzed by getID3 is actually mpeg4
 *
 * @param array $pa_getid3_info
 * @return bool
 */
function caGetID3IsMpeg4($pa_getid3_info) {
	if ($pa_getid3_info['fileformat'] == 'mp4') {
		return true;
	}
	if (substr(0, 3, $pa_getid3_info['quicktime']['ftyp']['signature'] == 'mp4')) {
		return true;
	}
	if (substr(0, 3, $pa_getid3_info['quicktime']['ftyp']['fourcc'] == 'mp4')) {
		return true;
	}
	if ($pa_getid3_info['video']['dataformat'] == 'mpeg4') {
		return true;
	}
	if ($pa_getid3_info['video']['fourcc'] == 'mp4v') {
		return true;
	}
	if ($pa_getid3_info['audio']['dataformat'] == 'mpeg4') {
		return true;
	}

	if (preg_match('!H\.264!i', $pa_getid3_info['video']['codec'])) {
		return true;
	}

	return false;
}
# ------------------------------------------------------------------------------------------------