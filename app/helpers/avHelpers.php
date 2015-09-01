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
