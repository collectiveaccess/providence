<?php
/** ---------------------------------------------------------------------
 * app/helpers/videoHelpers.php : miscellaneous video helpers
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2026 Whirl-i-Gig
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
# ------------------------------------------------------------------------------------------------
/**
 * Divine file format with media info
 *
 * @param string $path
 * @return string mime type usable for media processing back-end, e.g. video/mp4
 */
function caMediaInfoGuessFileFormat($path) {
	if(!caMediaInfoInstalled()) { return false; }

	$media_metadata = caExtractMetadataWithMediaInfo($path);
	switch($media_metadata['VIDEO']['Format'] ?? null) {
		case 'DV':
			return 'video/x-dv';
		case 'MPEG-4':
		case 'MPEG-4 Visual':
			return 'video/mp4';
		case 'AVC':
			switch($media_metadata['GENERAL']['Format'] ?? null) {
				case 'MPEG-4':
					return 'video/mp4';
					break;
				case 'MXF':
					return 'video/mxf';
					break;
				default:
					return 'video/mp2t';
					break;
			}
		case 'AVI':
			return 'video/avi';
		case 'Matroska':
			return 'video/x-matroska';
		// @todo add more popular formats here!
		default:
			switch($media_metadata['GENERAL']['Format'] ?? null) {
				case 'MPEG-4':
					return 'video/mp4';
					break;
				case 'MXF':
					return 'video/mxf';
					break;
			}
			return false;
	}
}
# ------------------------------------------------------------------------------------------------
/**
 * Determine if file is a web-playable H.264 MPEG-4 video. Will return true if it a playable H,264-compressed
 * MPEG-4 video, false if it is a non-web-playable MPEG-4 video and null for all other files.
 *
 * Requires presence of MediaInfo. If MediaInfo is not available null will be returned.
 *
 * @param string $path
 * @return bool or null
 */
function caIsPlayableH264($path) : ?bool {
	if(!caMediaInfoInstalled()) { return null; }

	$media_metadata = caExtractMetadataWithMediaInfo($path);

	if(!preg_match('!MPEG\-4!i', $media_metadata['GENERAL']['Format'])) { return null; }
	
	if(preg_match("!H\.263!i", ($media_metadata['VIDEO']['Format settings, Matrix'] ?? null))) { return false; }
	if(($media_metadata['VIDEO']['Chroma subsampling'] ?? null) === '4:2:2') { return false; }

	return true;
}
# ------------------------------------------------------------------------------------------------
/**
 * Divine file format with getID3
 *
 * @param $path
 * @return bool|string
 */
function caGetID3GuessFileFormat($path) {
	if(!$path) { return null; }
	if($getid3_info = caExtractMetadataWithGetID3($path)) {
	
		if(
			isset($getid3_info['quicktime']) && 
			isset($getid3_info['quicktime']['ftyp']) && 
			isset($getid3_info['quicktime']['ftyp']['signature']) && 
			($getid3_info['quicktime']['ftyp']['signature'] === 'heic')
		) {
			return false;
		}
	
	
		if($getid3_info['mime_type']) {
			return $getid3_info["mime_type"];
		}
	}

	return false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Extract media metadata with get id3
 *
 * @param string $filepath
 * @return array|bool
 */
function caExtractMetadataWithGetID3($filepath) {
	if(!$filepath) { return null; }
	if(MemoryCache::contains($filepath, 'GetID3MediaMetadata')) {
		return MemoryCache::fetch($filepath, 'GetID3MediaMetadata');
	}

	$ID3 = new getID3();
	$ID3->option_max_2gb_check = false;
	$getid3_info = $ID3->analyze($filepath);
	if (!isset($getid3_info["mime_type"])) { return false; }

	// force MPEG-4 files to use video/mp4 mimetype rather than the video/quicktime
	// mimetype getID3 returns. This will allow us to distinguish MPEG-4 files, which can
	// be played in HTML5 and Flash players from older Quicktime files which cannot.
	if ($getid3_info["mime_type"] === 'video/quicktime') {
		if (caGetID3IsMpeg4($getid3_info)) {
			$getid3_info["mime_type"] = 'video/mp4';
		}
	}

	//
	// Versions of getID3 to at least 1.7.7 throw an error that should be a warning
	// when parsing MPEG-4 files, so we supress it here, otherwise we'd never be able
	// to parse MPEG-4 files.
	//
	if ((isset($getid3_info["error"])) && (is_array($getid3_info["error"])) && (sizeof($getid3_info["error"]) == 1)) {
		if (preg_match("/does not fully support MPEG-4/", $getid3_info['error'][0])) {
			$getid3_info['error'] = array();
		}
		if (preg_match("/claims to go beyond end-of-file/", $getid3_info['error'][0])) {
			$getid3_info['error'] = array();
		}
		if (preg_match("/because beyond 2GB limit of PHP filesystem functions/", $getid3_info['error'][0])) {
			$getid3_info['error'] = array();
		}
	}

	MemoryCache::save($filepath, $getid3_info, 'GetID3MediaMetadata');

	return $getid3_info;
}
# ------------------------------------------------------------------------------------------------
/**
 * Extracts media metadata using MediaInfo
 *
 * @param string $ps_filepath file path
 * @param string $ps_mediainfo_path optional path to MediaInfo binary. If omitted the path configured in external_applications.conf is used.
 *
 * @return array|bool Array with extracted metadata or false if file cannot be read.
 */
function caExtractMetadataWithMediaInfo($ps_filepath, $ps_mediainfo_path=null){
	if(!$ps_mediainfo_path) { $ps_mediainfo_path = caGetExternalApplicationPath('mediainfo'); }
	if (!$ps_filepath || !caIsValidFilePath($ps_mediainfo_path)) { return false; }

	if(MemoryCache::contains($ps_filepath, 'MediaInfoMetadata')) {
		return MemoryCache::fetch($ps_filepath, 'MediaInfoMetadata');
	}

	//
	// TODO: why don't we parse this from the XML output like civilized people?
	//
	caExec($ps_mediainfo_path." ".caEscapeShellArg($ps_filepath), $va_output, $vn_return);
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
	if(!$ps_filepath) { return null; }
	$ps_mediainfo_path = caGetExternalApplicationPath('mediainfo');
	if(!caMediaInfoInstalled($ps_mediainfo_path)) { return false; }

	$va_output = array();
	caExec($ps_mediainfo_path.' --Inform="Video;%Duration/String3%" '.caEscapeShellArg($ps_filepath), $va_output, $vn_return);
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
/**
 * Convert Whisper JSON transcription output to VTT format
 *
 * @param string $filepath Path to Whisper JSON file
 * @param string $vttpath Path to file containing VTT output
 * @param array $options Options include:
 *		returnContent = return decoded JSON sdata as array
 *
 * @return mixed path to VTT output, array of data (if returnContent option is set), or null on error
 */
function caWhisperTranscriptionToVTT(string $filepath, string $vttpath, ?array $options=null) {
	if(!($json_str = file_get_contents($filepath))) { return null; }
	if (!($json = json_decode($json_str, true))) { return null; }
	if(!is_array($segments = $json['segments'] ?? null)) { return null; }
	
	if(!($r = fopen($vttpath, "w"))) { return null; }
	$tc = new TimecodeParser();
	fputs($r, "WEBVTT\n\n");
	foreach($segments as $s) {
		$tc->parse((float)$s['start']);
		$start = $tc->getText();
		$tc->parse((float)$s['end']);
		$end = $tc->getText();
		$text = trim($s['text']);
		$id = $s['id'] + 1;
		
		$line = "{$id}\n{$start} --> {$end}\n{$text}\n\n";
		fputs($r, $line);
	}
	fclose($r);
	return !caGetOption('returnContent', $options, false) ? $vttpath : $json;
}
# ------------------------------------------------------------------------------------------------
/**
 * Convert Whisper JSON transcription output to VTT format
 *
 * @param array $data 
 * @param array $options
 *
 * @return array
 */
function caWhisperTranscriptionSegmentsToWords(array $data, ?array $options=null) : ?array {
	if(!is_array($data['segments'] ?? null)) { return null; }
	
	$words = [];
	foreach($data['segments'] as $s) {
		if(is_array($s['words'])) {
			foreach($s['words'] as $w) {
				$words[] = [
					'word' => $w['word'],
					'start' => $w['start'],
					'end' => $w['end']
				];
			}
		}
	}
	return $words;
}
# ------------------------------------------------------------------------------------------------

