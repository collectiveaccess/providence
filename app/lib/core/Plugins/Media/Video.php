<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/Video.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2013 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
/** 
  * Plugin for processing video media using ffmpeg
  */

include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/getid3/getid3.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimecodeParser.php");
include_once(__CA_LIB_DIR__."/core/Parsers/OggParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaVideo Extends BaseMediaPlugin Implements IWLPlugMedia {

	var $errors = array();

	var $filepath;
	var $handle;
	var $ohandle;
	var $pa_properties;
	var $oproperties;
	var $metadata = array();

	var $opo_config;
	var $opo_external_app_config;
	var $ops_path_to_ffmpeg;
	var $ops_path_to_qt_faststart;
	var $opb_ffmpeg_available;

	var $ops_mediainfo_path;
	var $opb_mediainfo_available;

	var $info = array(
		"IMPORT" => array(
			"audio/x-realaudio" 				=> "rm",
			"video/x-ms-asf" 					=> "asf",
			"video/x-ms-wmv"					=> "wmv",
			"video/quicktime" 					=> "mov",
			"video/avi" 						=> "avi",
			"video/x-flv"						=> "flv",
			"application/x-shockwave-flash" 	=> "swf",
			"video/mpeg" 						=> "mpeg",
			"video/mp4" 						=> "m4v",
			"video/ogg"							=> "ogg",
			"video/x-matroska"					=> "webm"
		),

		"EXPORT" => array(
			"audio/x-realaudio" 				=> "rm",
			"video/x-ms-asf" 					=> "asf",
			"video/x-ms-wmv"					=> "wmv",
			"video/quicktime" 					=> "mov",
			"video/avi" 						=> "avi",
			"video/x-flv"						=> "flv",
			"application/x-shockwave-flash" 	=> "swf",
			"video/mpeg" 						=> "mp4",
			"audio/mpeg"						=> "mp3",
			"image/jpeg"						=> "jpg",
			"image/png"							=> "png",
			"video/mp4" 						=> "m4v",
			"video/ogg"							=> "ogg",
			"video/x-matroska"					=> "webm"
		),

		"TRANSFORMATIONS" => array(
			"SET" 		=> array("property", "value"),
			"ANNOTATE"	=> array("text", "font", "size", "color", "position", "inset"),	// dummy
			"WATERMARK"	=> array("image", "width", "height", "position", "opacity"),	// dummy
			"SCALE" 	=> array("width", "height", "mode", "antialiasing")
		),

		"PROPERTIES" => array(
			"width" 			=> 'R',
			"height" 			=> 'R',
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"mimetype" 			=> 'R',
			"typename"			=> 'R',
			"bandwidth"			=> 'R',
			"video_bitrate"		=> 'W',
			"audio_bitrate"		=> 'W',
			"audio_sample_freq"	=> 'W',
			"title" 			=> 'R',
			"author" 			=> 'R',
			"copyright" 		=> 'R',
			"description" 		=> 'R',
			"duration" 			=> 'R',
			"filesize" 			=> 'R',
			"has_video"		 	=> 'R',
			"has_audio" 		=> 'R',
			"quality"			=> 'W',
			"version"			=> 'W',		// required of all plug-ins
			"threads"			=> 'W',
			"qmin"				=> 'W',
			"qmax"				=> 'W',
			"flags"				=> 'W',
			"resolution"		=> 'W',
			"coder"				=> 'W',
			"twopass"			=> 'W',
			"qdiff"				=> 'W',
			"cmp"				=> 'W',
			"sc_threshold"		=> 'W',
			"partitions"		=> 'W',
			"vpre"				=> 'W',
			"command"			=> 'W'
		),

		"NAME" => "Video",
		"NO_CONVERSION" => 0
	);

	var $typenames = array(
		"audio/x-realaudio" 				=> "RealMedia",
		"video/x-ms-asf" 					=> "WindowsMedia",
		"video/x-ms-wmv"					=> "WindowsMedia",
		"video/quicktime" 					=> "QuickTime",
		"video/x-flv"						=> "FlashVideo (flv)",
		"application/x-shockwave-flash" 	=> "Flash (swf)",
		"video/mpeg" 						=> "MPEG",
		"audio/mpeg"						=> "MP3 audio",
		"image/jpeg"						=> "JPEG",
		"image/png"							=> "PNG",
		"video/mp4" 						=> "MPEG-4",
		"video/ogg"							=> "Ogg Theora",
		"video/x-matroska"					=> "WebM"
	);

	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides ffmpeg-based audio and video processing services');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_path_to_ffmpeg = $this->opo_external_app_config->get('ffmpeg_app');
		$this->ops_path_to_qt_faststart = $this->opo_external_app_config->get('qt-faststart_app');
		$this->opb_ffmpeg_available = caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg);

		$this->ops_mediainfo_path = $this->opo_external_app_config->get('mediainfo_app');
		$this->opb_mediainfo_available = caMediaInfoInstalled($this->ops_mediainfo_path);

		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		$this->register();
		$va_status['available'] = true;
		
		if(!$this->opb_ffmpeg_available){
			$va_status['errors'][] = _t("Incoming Audio files will not be transcoded because ffmpeg is not installed.");
		}
		
		if ($this->opb_mediainfo_available) { 
			$va_status['notices'][] = _t("MediaInfo will be used to extract metadata from video files.");
		}
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		$ID3 = new getID3();
		$ID3->option_max_2gb_check = false;
		$info = $ID3->analyze($filepath);
		if (($info["mime_type"]) && $this->info["IMPORT"][$info["mime_type"]]) {
			$this->handle = $this->ohandle = $info;
				
			// force MPEG-4 files to use video/mp4 mimetype rather than the video/quicktime
			// mimetype getID3 returns. This will allow us to distinguish MPEG-4 files, which can
			// be played in HTML5 and Flash players from older Quicktime files which cannot.
			if ($info["mime_type"] === 'video/quicktime') {
				if (isset($info['video']['dataformat']) && ($info['video']['dataformat'] == 'quicktimevr')) {
					// don't attempt to handle QuicktimeVR - it's not video!
					return '';
				}
				if ($this->_isMPEG4($info)) {
					$info["mime_type"] = 'video/mp4';
				}
			}
			
			unset($info['quicktime']['moov']);	// remove voluminous parse of Quicktime files from metadata
			$this->metadata = $info;	// populate with getID3 data because it's handy
			
			return $info["mime_type"];
		} else {
			// is it Ogg?
			$info = new OggParser($filepath);
			if (!$info->LastError && is_array($info->Streams) && (sizeof($info->Streams) > 0)) {
				if (isset($info->Streams['theora'])) {
					$this->handle = $this->ohandle = $info->Streams;
					return $this->handle['mime_type'] = 'video/ogg';
				}
			}
			
			# file format is not supported by this plug-in
			return '';
		}
	}
	# ----------------------------------------------------------
	private function _isMPEG4($pa_info) {
		if ($pa_info['fileformat'] == 'mp4') {
			return true;
		}
		if (substr(0, 3, $pa_info['quicktime']['ftyp']['signature'] == 'mp4')) {
			return true;
		}
		if (substr(0, 3, $pa_info['quicktime']['ftyp']['fourcc'] == 'mp4')) {
			return true;
		}
		if ($pa_info['video']['dataformat'] == 'mpeg4') {
			return true;
		}
		if ($pa_info['video']['fourcc'] == 'mp4v') {
			return true;
		}
		if ($pa_info['audio']['dataformat'] == 'mpeg4') {
			return true;
		}
		
		if (preg_match('!H\.264!i', $pa_info['video']['codec'])) {
			return true;
		}
		

		return false;
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				//print "Invalid property";
				return '';
			}
		} else {
			return '';
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				switch($property) {
					default:
						if ($this->info["PROPERTIES"][$property] == 'W') {
							$this->properties[$property] = $value;
						} else {
							# read only
							return '';
						}
						break;
				}
			} else {
				# invalid property
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugVideo->set()");
				return '';
			}
		} else {
			return '';
		}
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		return $this->metadata;
	}
	# ------------------------------------------------
	public function read ($filepath) {
		if (!file_exists($filepath)) {
			$this->postError(1650, _t("File %1 does not exist", $filepath), "WLPlugVideo->read()");
			$this->handle = "";
			$this->filepath = "";
			return false;
		}
		if (!(($this->handle) && ($this->handle["filepath"] == $filepath))) {
			$ID3 = new getID3();
			$ID3->option_max_2gb_check = false;
			$this->handle = $this->ohandle = $ID3->analyze($filepath);
			
			if($this->opb_mediainfo_available){
				$this->metadata = caExtractMetadataWithMediaInfo($this->ops_mediainfo_path, $filepath);
			} else {
				$this->metadata = $this->handle;
			}
			if (!$this->handle['mime_type']) {
				// is it Ogg?
				$info = new OggParser($filepath);
				if (!$info->LastError) {
					$this->handle = $this->ohandle = $info->Streams;
					$this->handle['mime_type'] = 'video/ogg';
					$this->handle['playtime_seconds'] = $this->handle['duration'];
				}
			}
			
			// force MPEG-4 files to use video/mp4 mimetype rather than the video/quicktime
			// mimetype getID3 returns. This will allow us to distinguish MPEG-4 files, which can
			// be played in HTML5 and Flash players from older Quicktime files which cannot.
			if ($this->handle["mime_type"] === 'video/quicktime') {
				if ($this->_isMPEG4($this->handle)) {
					$this->handle["mime_type"] = 'video/mp4';
				}
			}
		}

		//
		// Versions of getID3 to at least 1.7.7 throw an error that should be a warning
		// when parsing MPEG-4 files, so we supress it here, otherwise we'd never be able
		// to parse MPEG-4 files.
		//
		if ((isset($this->handle["error"])) && (is_array($this->handle["error"])) && (sizeof($this->handle["error"]) == 1)) {
			if (preg_match("/does not fully support MPEG-4/", $this->handle['error'][0])) {
				$this->handle['error'] = array();
			}
			if (preg_match("/claims to go beyond end-of-file/", $this->handle['error'][0])) {
				$this->handle['error'] = array();
			}
			if (preg_match("/because beyond 2GB limit of PHP filesystem functions/", $this->handle['error'][0])) {
				$this->handle['error'] = array();
			}
		}

		$w = $h = null;
		
		if (!((isset($this->handle["error"])) && (is_array($this->handle["error"])) && (sizeof($this->handle["error"]) > 0))) {
			$this->filepath = $filepath;

			// getID3 sometimes reports the wrong width and height in the resolution_x and resolution_y indices for FLV files, but does
			// report correct values in the 'meta' block. So for FLV only we try to take values from the 'meta' block first.
			if (($this->handle["mime_type"] == 'video/x-flv') && is_array($this->handle['meta']) && is_array($this->handle['meta']['onMetaData'])) {
				$w = $this->handle['meta']['onMetaData']['width'];
				$h = $this->handle['meta']['onMetaData']['height'];
			} else {
				if ($this->handle['mime_type'] == 'video/ogg') {
					$w = $this->handle['theora']['width'];
					$h = $this->handle['theora']['height'];
				}
			}
			if (!$w || !$h) {
				$w = $this->handle["video"]["resolution_x"];
				$h = $this->handle["video"]["resolution_y"];
			}
			if (!$w || !$h) {
				// maybe it's stuck in a stream?
				if (is_array($this->handle["video"]["streams"])) {
					foreach($this->handle["video"]["streams"] as $vs_key => $va_stream_info) {
						$w = $this->handle["video"]["streams"][$vs_key]["resolution_x"];
						$h = $this->handle["video"]["streams"][$vs_key]["resolution_y"];

						if ($w > 0 && $h > 0) {
							break;
						}
					}
				}
			}

			$this->properties["width"] = $w;
			$this->properties["height"] = $h;

			$this->properties["mimetype"] = $this->handle["mime_type"];
			$this->properties["typename"] = $this->typenames[$this->properties["mimetype"]] ? $this->typenames[$this->properties["mimetype"]] : "Unknown";

			$this->properties["duration"] = $this->handle["playtime_seconds"];
			$this->properties["filesize"] = filesize($filepath);

			# -- get bandwidth
			switch($this->properties["mimetype"]) {
				case 'audio/x-realaudio':
					$video_streams = array();
					$audio_streams = array();
					if (is_array($this->handle["real"]["chunks"])) {
						foreach($this->handle["real"]["chunks"] as $chunk) {
							if ($chunk["name"] == "MDPR") {
								if (in_array($chunk["mime_type"], array("video/x-pn-realvideo", "video/x-pn-multirate-realvideo"))) {
									$video_streams[] = $chunk["max_bit_rate"];
								} else {
									if (in_array($chunk["mime_type"], array("audio/x-pn-realaudio", "audio/x-pn-multirate-realaudio"))) {
										$audio_streams[] = $chunk["max_bit_rate"];
									}
								}
							}
						}

						sort($video_streams);
						sort($audio_streams);

						$this->properties["has_video"] = (sizeof($video_streams) ? 1 : 0);
						$this->properties["has_audio"] = (sizeof($audio_streams) ? 1 : 0);
					} else {
						// old real format
						if (is_array($this->handle["real"]["old_ra_header"])) {
							if (($this->properties["filesize"] - $this->handle["real"]["old_ra_header"]["audio_bytes"]) > 0) {
								$this->properties["has_video"] = 1;
								$video_streams[] = (($this->properties["filesize"] - $this->handle["real"]["old_ra_header"]["audio_bytes"]) * 8) / $this->properties["duration"];
							} else {
								$this->properties["has_video"] = 0;
							}
							if ($this->handle["real"]["old_ra_header"]["audio_bytes"] > 0) {
								$this->properties["has_audio"] = 1;
								$audio_streams[] = ($this->handle["real"]["old_ra_header"]["audio_bytes"] * 8) / $this->properties["duration"];
							} else {
								$this->properties["has_audio"] = 0;
							}
						} else {
							$this->properties["has_video"] = 0;
							$this->properties["has_audio"] = 0;
						}
					}

					$this->properties["type_specific"] = array("real" => $this->handle["real"]);

					$this->properties["title"] = 		$this->handle["real"]["comments"]["title"];
					$this->properties["author"] = 		$this->handle["real"]["comments"]["artist"];
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	$this->handle["real"]["comments"]["comment"];

					$this->properties["bandwidth"] = array(
						"min" => (sizeof($video_streams) ? $video_streams[0] : 0) + (sizeof($audio_streams) ? $audio_streams[0] : 0),
						"max" => (sizeof($video_streams) ? $video_streams[sizeof($video_streams) - 1] : 0)+ (sizeof($audio_streams) ? $audio_streams[sizeof($audio_streams) - 1] : 0)
					);
					break;
				case 'video/x-ms-asf':
				case 'video/x-ms-wmv':
					$this->properties["has_video"] = (sizeof($this->handle["asf"]["video_media"]) ? 1 : 0);
					$this->properties["has_audio"] = (sizeof($this->handle["asf"]["audio_media"]) ? 1 : 0);

					$this->properties["type_specific"] = array("asf" => $this->handle["asf"]);

					$this->properties["title"] = 		$this->handle["asf"]["comments"]["title"];
					$this->properties["author"] = 		$this->handle["asf"]["comments"]["artist"];
					$this->properties["copyright"] = 	$this->handle["asf"]["comments"]["copyright"];
					$this->properties["description"] = 	$this->handle["asf"]["comments"]["comment"];

					$this->properties["bandwidth"] = array("min" => 0, "max" => $this->handle["bitrate"]);
					break;
				case 'video/quicktime':
				case 'video/mp4':
					$this->properties["has_video"] = (isset($this->handle["video"]["bitrate"]) && sizeof($this->handle["video"]["bitrate"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->handle["audio"]["bitrate"]) && sizeof($this->handle["audio"]["bitrate"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => (int)$this->handle["theora"]['nombitrate'] + (int)$this->handle["vorbis"]['bitrate'], "max" => (int)$this->handle["theora"]['nombitrate'] + (int)$this->handle["vorbis"]['bitrate']);
					break;
				case 'video/ogg':
					$this->properties["has_video"] = (isset($this->handle["theora"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->handle["vorbis"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);
					break;
				case 'application/x-shockwave-flash':
					$this->properties["has_video"] = (($this->handle["header"]["frame_width"] > 0) ? 1 : 0);
					$this->properties["has_audio"] = 1;

					$this->properties["type_specific"] = array("header" => $this->handle["header"]);

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->handle["filesize"]/$this->handle["playtime_seconds"], "max" => $this->handle["filesize"]/$this->handle["playtime_seconds"]);
					break;
				case 'video/mpeg':
					$this->properties["has_video"] = (isset($this->handle["video"]["bitrate"]) && sizeof($this->handle["video"]["bitrate"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->handle["audio"]["bitrate"]) && sizeof($this->handle["audio"]["bitrate"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);
					break;
				case 'video/x-flv':
					$this->properties["has_video"] = (sizeof($this->handle["header"]["hasVideo"]) ? 1 : 0);
					$this->properties["has_audio"] = (sizeof($this->handle["header"]["hasAudio"]) ? 1 : 0);

					$this->properties["type_specific"] = array("header" => $this->handle["header"]);

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$vn_bitrate = $this->handle["filesize"]/$this->handle["playtime_seconds"];

					$this->properties["bandwidth"] = array("min" => $vn_bitrate, "max" => $vn_bitrate);
					break;
			}

			$this->oproperties = $this->properties;

			return 1;
		} else {
			$this->postError(1650, join("; ", $this->handle["error"]), "WLPlugVideo->read()");
			$this->handle = "";
			$this->filepath = "";
			return false;
		}
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if (!$this->handle) { return false; }
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugVideo->transform()");
			return false;
		}
		
		$do_crop = 0;
		
		# get parameters for this operation
		$sparams = $this->info["TRANSFORMATIONS"][$operation];

		$this->properties["version_width"] = $w = $parameters["width"];
		$this->properties["version_height"] = $h = $parameters["height"];
		$cw = $this->get("width");
		$ch = $this->get("height");
		if (!$cw) { $cw = $w; }
		if (!$ch) { $ch = $h; }
		switch($operation) {
			# -----------------------
			case "SET":
				while(list($k, $v) = each($parameters)) {
					$this->set($k, $v);
				}
				break;
			# -----------------------
			case 'SCALE':
				switch($parameters["mode"]) {
					# ----------------
					case "width":
						$scale_factor = $w/$cw;
						$h = $ch * $scale_factor;
						break;
					# ----------------
					case "height":
						$scale_factor = $h/$ch;
						$w = $cw * $scale_factor;
						break;
					# ----------------
					case "bounding_box":
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h);
						$h = $ch * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h);
						break;
					# ----------------
					case "fill_box":
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h);
						$h = $ch * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h);

						$do_crop = 1;
						break;
					# ----------------
				}

				$w = round($w);
				$h = round($h);

				if (!($w > 0 && $h > 0)) {
					$this->postError(1610, _t("%1: %2 during resize operation", $reason, $description), "WLPlugVideo->transform()");
					return false;
				}
				if ($do_crop) {
					$this->properties["width"] = $parameters["width"];
					$this->properties["height"] = $parameters["height"];
				} else {
					$this->properties["width"] = $w;
					$this->properties["height"] = $h;
				}
				break;
			# -----------------------
		}
		return 1;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype, $pa_options=null) {
		if (!$this->handle) { return false; }
		if (!($ext = $this->info["EXPORT"][$mimetype])) {
			# this plugin can't write this mimetype
			return false;
		}

		# is mimetype valid?
		switch($mimetype) {
			# ------------------------------------
			case 'image/jpeg':
				$vn_preview_width = $this->properties["width"];
				$vn_preview_height = $this->properties["height"];

				if ((caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) && ($this->handle["mime_type"] != "application/x-shockwave-flash")) {
					if (($vn_start_secs = $this->properties["duration"]/8) > 120) { 
						$vn_start_secs = 120;		// always take a frame from the first two minutes to ensure performance (ffmpeg gets slow if it has to seek far into a movie to extract a frame)
					}
					
					
					exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f image2 -ss ".($vn_start_secs)." -t 0.04 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
					if (($vn_return < 0) || ($vn_return > 1) || (!@filesize($filepath.".".$ext))) {
						@unlink($filepath.".".$ext);
						// try again, with -ss 1 (seems to work consistently on some files where other -ss values won't work)
						exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f image2 -ss ".($vn_start_secs)." -t 1 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
					}

					if (($vn_return < 0) || ($vn_return > 1) || (!@filesize($filepath.".".$ext))) {
						@unlink($filepath.".".$ext);
						// don't throw error as ffmpeg cannot generate frame still from all file
					}
				}

				$this->properties["mimetype"] = $mimetype;
				$this->properties["typename"] = isset($this->typenames[$mimetype]) ? $this->typenames[$mimetype] : $mimetype;

				break;
			# ------------------------------------
			case 'image/png':
				$vn_preview_width = $this->properties["width"];
				$vn_preview_height = $this->properties["height"];

				if ((caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) && ($this->handle["mime_type"] != "application/x-shockwave-flash")) {
					if (($vn_start_secs = $this->properties["duration"]/8) > 120) { 
						$vn_start_secs = 120;		// always take a frame from the first two minutes to ensure performance (ffmpeg gets slow if it has to seek far into a movie to extract a frame)
					}
					
					
					exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -vcodec png -ss ".($vn_start_secs)." -t 0.04 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
					if (($vn_return < 0) || ($vn_return > 1) || (!@filesize($filepath.".".$ext))) {
						@unlink($filepath.".".$ext);
						// try again, with -ss 1 (seems to work consistently on some files where other -ss values won't work)
						exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -vcodec png -ss ".($vn_start_secs)." -t 1 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
					}

					if (($vn_return < 0) || ($vn_return > 1) || (!@filesize($filepath.".".$ext))) {
						@unlink($filepath.".".$ext);
						// don't throw error as ffmpeg cannot generate frame still from all file
					}
				}

				$this->properties["mimetype"] = $mimetype;
				$this->properties["typename"] = isset($this->typenames[$mimetype]) ? $this->typenames[$mimetype] : $mimetype;

				break;
			# ------------------------------------
			case 'video/x-flv':
				if (caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) {
					$vn_video_bitrate = $this->get('video_bitrate');
					if ($vn_video_bitrate < 20000) { $vn_video_bitrate = 256000; }
					$vn_audio_bitrate = $this->get('audio_bitrate');
					if ($vn_audio_bitrate < 8000) { $vn_audio_bitrate = 32000; }
					$vn_audio_sample_freq = $this->get('audio_sample_freq');
					if (($vn_audio_sample_freq != 44100) && ($vn_audio_sample_freq != 22050) && ($vn_audio_sample_freq != 11025)) {
						$vn_audio_sample_freq = 44100;
					}
					exec($vs_cmd = $this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f flv -b ".intval($vn_video_bitrate)." -ab ".intval($vn_audio_bitrate)." -ar ".intval($vn_audio_sample_freq)." -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
					if (($vn_return < 0) || ($vn_return > 1) || (filesize($filepath.".".$ext) == 0)) {
						@unlink($filepath.".".$ext);
						$this->postError(1610, _t("Couldn't convert file to FLV format"), "WLPlugVideo->write()");
						return false;
					}
					$this->properties["mimetype"] = $mimetype;
					$this->properties["typename"] = $this->typenames[$mimetype];
				}
				break;
			# ------------------------------------
			case 'video/mpeg':
			case 'video/ogg':		// only support "command" option...
				if (caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) {
					$va_ffmpeg_params = array();

					if (!($vs_ffmpeg_command = $this->get('command'))) {
						// Video bitrate
						$vn_video_bitrate = $this->get('video_bitrate');
						if($vn_video_bitrate!='') {
							if ($vn_video_bitrate < 20000) {
								$vn_video_bitrate = 256000;
							}
							$va_ffmpeg_params["video_bitrate"] = "-b ".intval($vn_video_bitrate);
						}
						
						// Audio bitrate
						$vn_audio_bitrate = $this->get('audio_bitrate');
						if ($vn_audio_bitrate < 8000) { $vn_audio_bitrate = 32000; }
						$va_ffmpeg_params["audio_bitrate"] = "-ab ".intval($vn_audio_bitrate);
	
						// Audio sample frequency
						$vn_audio_sample_freq = $this->get('audio_sample_freq');
						if (($vn_audio_sample_freq != 44100) && ($vn_audio_sample_freq != 22050) && ($vn_audio_sample_freq != 11025)) {
							$vn_audio_sample_freq = 44100;
						}
						$va_ffmpeg_params["audio_sample_freq"] = "-ar ".intval($vn_audio_sample_freq);
	
						// Multithreading
						$vn_threads = $this->get('threads');
						if ($vn_threads < 1 || $vn_threads == '') {
							$vn_threads = 1;
						}
						$va_ffmpeg_params["threads"] = "-threads ".$vn_threads;
	
						// Quantitizers
						$vn_qmin = $this->get('qmin');
						if ($vn_qmin != '') {
							$va_ffmpeg_params["qmin"] = "-qmin ".$vn_qmin;
						}
						$vn_qmax = $this->get('qmax');
						if ($vn_qmax != '') {
							$va_ffmpeg_params["qmax"] = "-qmax ".$vn_qmax;
						}
	
						// Flags
						if(($vs_flags = $this->get('flags'))!=''){
							$va_ffmpeg_params["flags"] = "-flags ".$vs_flags;
						}
	
						// Resolution
						if(($vs_res = $this->get('resolution'))!=''){
							$va_ffmpeg_params["resolution"] = "-s ".$vs_res;
						}
	
						// Coder
						if(($vn_coder = $this->get('coder'))!=''){
							$va_ffmpeg_params["coder"] = "-coder ".$vn_coder;
						}
	
						// 2-pass encoding
						if($this->get('twopass')){
							$vb_twopass = true;
						} else {
							$vb_twopass =false;
						}
						
						// qdiff
						if(($vs_qdiff = $this->get('qdiff'))!=''){
							$va_ffmpeg_params["qdiff"] = "-qdiff ".$vs_qdiff;
						}
						
						// partitions
						if(($vs_partitions = $this->get('partitions'))!=''){
							$va_ffmpeg_params["partitions"] = "-partitions ".$vs_partitions;
						}
						
						// cmp
						if(($vs_cmp = $this->get('cmp'))!=''){
							$va_ffmpeg_params["cmp"] = "-cmp ".$vs_cmp;
						}
						
						// qdiff
						if(($vs_sc_threshold = $this->get('sc_threshold'))!=''){
							$va_ffmpeg_params["sc_threshold"] = "-sc_threshold ".$vs_sc_threshold;
						}
						
						// vpre
						if(!($vs_vpreset = $this->get('vpre'))!=''){
							$vs_vpreset = null;
						}
					}

					// put it all together

					// we need to be in a directory where we can write (libx264 logfiles)
					$vs_cwd = getcwd();
					chdir(__CA_APP_DIR__."/tmp/");
					
					$vs_cmd = '';
					if ($vs_ffmpeg_command) {
						exec($vs_cmd .= $this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." {$vs_ffmpeg_command} ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
					} else {
						if ($vs_vpreset) {
							$vs_other_params = "";
							if($vn_audio_bitrate){
								$vs_other_params.="-ab {$vn_audio_bitrate} ";
							}
							if($vn_audio_sample_freq){
								$vs_other_params.="-ar {$vn_audio_sample_freq} ";
							}
							if($vs_res && $vs_res!=''){
								$vs_other_params.="-s ".$vs_res;
							}
							exec($vs_cmd .= $this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -acodec libfaac {$vs_other_params} -vpre {$vs_vpreset} -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
						} else {
							if(!$vb_twopass) {
								exec($vs_cmd .= $this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -acodec libfaac ".join(" ",$va_ffmpeg_params)." -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
							} else {
								exec($vs_cmd .= $this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -pass 1 -acodec libfaac ".join(" ",$va_ffmpeg_params)." -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
								exec($vs_cmd .= $this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -pass 2 -acodec libfaac ".join(" ",$va_ffmpeg_params)." -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
								// probably cleanup logfiles here
							}
						}
					}
					
					chdir($vs_cwd); // avoid fun side-effects
					if (@filesize($filepath.".".$ext) == 0) {
						@unlink($filepath.".".$ext);
						if ($vs_vpreset) {
							$this->postError(1610, _t("Couldn't convert file to MPEG4 format [%1]; does the ffmpeg preset '%2' exist? (command was %3)", $vn_return, $vs_vpreset, $vs_cmd), "WLPlugVideo->write()");
						} else {
							$this->postError(1610, _t("Couldn't convert file to MPEG4 format [%1] (command was %2)", $vn_return, $vs_cmd), "WLPlugVideo->write()");
						}
						return false;
					}
					
					// try to hint for streaming
					if (file_exists($this->ops_path_to_qt_faststart)) {
						exec($this->ops_path_to_qt_faststart." ".caEscapeShellArg($filepath.".".$ext)." ".caEscapeShellArg($filepath."_tmp.".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
						rename("{$filepath}_tmp.{$ext}", "{$filepath}.{$ext}");
					}
					# ------------------------------------
					$this->properties["mimetype"] = $mimetype;
					$this->properties["typename"] = $this->typenames[$mimetype];
				}
				break;
			# ------------------------------------
			default:
				if (($mimetype != $this->handle["mime_type"])) {
					# this plugin can't write this mimetype (no conversions allowed)
					$this->postError(1610, _t("Can't convert '%1' to %2", $this->handle["mime_type"], $mimetype), "WLPlugVideo->write()");
					return false;
				}
				# write the file
				if ( !copy($this->filepath, $filepath.".".$ext) ) {
					$this->postError(1610, _t("Couldn't write file to '%1'", $filepath), "WLPlugVideo->write()");
					return false;
				}
				break;
			# ------------------------------------
		}
		
		// if output file doesn't exist, ffmpeg failed or isn't installed
		// so use default icons
		if (!file_exists($filepath.".".$ext)) {
			# use default media icons
			return __CA_MEDIA_VIDEO_DEFAULT_ICON__;
		}
		
		return $filepath.".".$ext;
	}
	# ------------------------------------------------
	/** 
	 * Options:
	 *		width
	 *		height
	 *		minNumberOfFrames
	 *		maxNumberOfFrames
	 *		frameInterval
	 *		startAtTime
	 *		endAtTime
	 *		outputDirectory
	 *		force = ignore setting of "video_preview_generate_frames" app.conf directive and generate previews no matter what
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
		if (!(bool)$this->opo_config->get("video_preview_generate_frames") && (!isset($pa_options['force']) || !$pa_options['force'])) { return false; }
		if (!$this->opb_ffmpeg_available) return false;
		
		if (!isset($pa_options['outputDirectory']) || !$pa_options['outputDirectory'] || !file_exists($pa_options['outputDirectory'])) {
			if (!($vs_tmp_dir = $this->opo_config->get("taskqueue_tmp_directory"))) {
				// no dir
				return false;
			}
		} else {
			$vs_tmp_dir = $pa_options['outputDirectory'];
		}
		
		$o_tc = new TimecodeParser();
		
		if (($vn_min_number_of_frames = $pa_options['minNumberOfFrames']) < 1) {
			$vn_min_number_of_frames = 0;
		}
		
		if (($vn_max_number_of_frames = $pa_options['maxNumberOfFrames']) < 1) {
			$vn_max_number_of_frames = 100;
		}
		
		$vn_duration = $this->properties["duration"];
		if (!($vn_frame_interval = ($o_tc->parse($pa_options['frameInterval'])) ? $o_tc->getSeconds() : 0)) {
			$vn_frame_interval = 30;
		}
		if (!($vn_start_at = ($o_tc->parse($pa_options['startAtTime'])) ? $o_tc->getSeconds() : 0)) {
			$vn_start_at = 0;
		}
		if (!($vn_end_at = ($o_tc->parse($pa_options['endAtTime'])) ? $o_tc->getSeconds() : 0)) {
			$vn_end_at = 0;
		}
		
		if (($vn_previewed_duration = ($vn_start_at - $vn_end_at)) < 0) {
			$vn_previewed_duration = $vn_duration;
			$vn_start_at = $vn_end_at = 0;
		} else {
			// if start and end times are the same assume single frame mode and set duration to a sliver of time
			if ($vn_previewed_duration == 0) { $vn_previewed_duration = 0.1; }
		}
			
		if ($vn_frame_interval > $vn_previewed_duration) {
			$vn_frame_interval = $vn_previewed_duration;
		}
		
		$vn_preview_width = (isset($pa_options['width']) && ((int)$pa_options['width'] > 0)) ? (int)$pa_options['width'] : 320;
		$vn_preview_height= (isset($pa_options['height']) && ((int)$pa_options['height'] > 0)) ? (int)$pa_options['height'] : 320;
		
		$vn_s = $vn_start_at;
		$vn_e = $vn_duration - $vn_end_at;
		$vn_num_frames = ($vn_previewed_duration)/$vn_frame_interval;
		
		if ($vn_num_frames < $vn_min_number_of_frames) {
			$vn_frame_interval = ($vn_previewed_duration)/$vn_min_number_of_frames;
			$vn_num_frames = $vn_min_number_of_frames;
			$vn_previewed_duration = ($vn_num_frames * $vn_frame_interval);
		}
		if ($vn_num_frames > $vn_max_number_of_frames) {
			$vn_frame_interval = ($vn_previewed_duration)/$vn_max_number_of_frames;
			$vn_num_frames = $vn_max_number_of_frames;
			$vn_previewed_duration = ($vn_num_frames * $vn_frame_interval);
		}
		$vs_freq = 1/$vn_frame_interval;
		
		$vs_output_file_prefix = tempnam($vs_tmp_dir, 'caVideoPreview');
		$vs_output_file = $vs_output_file_prefix.'%05d.jpg';
		
		exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f image2 -r ".$vs_freq." -ss {$vn_s} -t {$vn_previewed_duration} -s ".$vn_preview_width."x".$vn_preview_height." -y ".caEscapeShellArg($vs_output_file). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
		$vn_i = 1;
		$va_files = array();
		while(file_exists($vs_output_file_prefix.sprintf("%05d", $vn_i).'.jpg')) {
			// add frame to list
			$va_files[''.sprintf("%4.2f", ((($vn_i - 1) * $vn_frame_interval) + $vn_s)).'s'] = $vs_output_file_prefix.sprintf("%05d", $vn_i).'.jpg';
		
			$vn_i++;
		}
		
		if (!sizeof($va_files)) {
			$this->postError(1610, _t("Couldn't not write video preview frames to tmp directory (%1)", $vs_tmp_dir), "WLPlugVideo->write()");
		}
		@unlink($vs_output_file_prefix);
		return $va_files;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function writeClip($ps_filepath, $ps_start, $ps_end, $pa_options=null) {
		if (!$this->opb_ffmpeg_available) return false;
		$o_tc = new TimecodeParser();
		
		$vn_start = $vn_end = null;
		if ($o_tc->parse($ps_start)) { $vn_start = $o_tc->getSeconds(); }
		if ($o_tc->parse($ps_end)) { $vn_end = $o_tc->getSeconds(); }
		
		if (!$vn_start || !$vn_end) { return null; }
		if ($vn_start >= $vn_end) { return null; }
		
		$vn_duration = $vn_end - $vn_start;
		
		exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -acodec mp3 -t {$vn_duration}  -y -ss {$vn_start} ".caEscapeShellArg($ps_filepath). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
		if ($vn_return != 0) {
			@unlink($filepath.".".$ext);
			$this->postError(1610, _t("Error extracting clip from %1 to %2: %3", $ps_start, $ps_end, join("; ", $va_output)), "WLPlugVideo->writeClip()");
			return false;
		}
		
		return true;
	}
	# ------------------------------------------------
	public function getOutputFormats() {
		return $this->info["EXPORT"];
	}
	# ------------------------------------------------
	public function getTransformations() {
		return $this->info["TRANSFORMATIONS"];
	}
	# ------------------------------------------------
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	public function mimetype2extension($mimetype) {
		return $this->info["EXPORT"][$mimetype];
	}
	# ------------------------------------------------
	public function extension2mimetype($extension) {
		reset($this->info["EXPORT"]);
		while(list($k, $v) = each($this->info["EXPORT"])) {
			if ($v === $extension) {
				return $k;
			}
		}
		return '';
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	public function reset() {
		$this->errors = array();
		$this->properties = $this->oproperties;
		return $this->handle = $this->ohandle;
	}
	# ------------------------------------------------
	public function init() {
		$this->errors = array();
		$this->filepath = "";
		$this->handle = "";
		$this->properties = "";
		
		$this->metadata = array();
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		foreach(array(
			'name', 'show_controls', 'url', 'text_only', 'viewer_width', 'viewer_height', 'id',
			'poster_frame_url', 'viewer_parameters', 'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style'
		) as $vs_k) {
			if (!isset($pa_options[$vs_k])) { $pa_options[$vs_k] = null; }
		}
		
		switch($pa_properties["mimetype"]) {
			# ------------------------------------------------
			case 'audio/x-realaudio':
				$vs_name = $pa_options["name"] ? $pa_options["name"] : "prm";

				$vb_show_controls = (isset($pa_options["show_controls"]) && $pa_options["show_controls"]) ? 1 : 0;
				if ($pa_options["text_only"]) {
					return "<a href='".(isset($pa_options["url"]) ? $pa_options["url"] : $ps_url)."'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "View Realmedia")."</a>";
				} else {
					ob_start();
		?>
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<object id="<?php print $vs_name; ?>" width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"]; ?>" classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA">
									<param name="controls" value="ImageWindow">
		<?php
			if ($vb_show_controls) {
		?>
									<param name="console" value="<?php print $vs_name; ?>_controls">
		<?php
			}
		?>
									<param name="autostart" value="true">
									<param name="type" value="audio/x-pn-realaudio-plugin">
									<param name="autogotourl" value="false">
									<param name="src" value="<?php print isset($pa_options["url"]) ? $pa_options["url"] : $ps_url; ?>">

									<embed name="<?php print $vs_name; ?>" src="<?php print isset($pa_options["url"]) ? $pa_options["url"] : $ps_url; ?>" width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"]; ?>"
										controls="ImageWindow" nojava="false"
										showdisplay="0" showstatusbar="1" autostart="true" type="audio/x-pn-realaudio-plugin">
									</embed>
								</object>
							</td>
						</tr>
<?php
					if ($vb_show_controls) {
?>
						<tr>
							<td>
								<object id="<?php print $vs_name; ?>_controls" width="<?php print $pa_properties["width"]; ?>" height="32" classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA">
									<param name="controls" value="ControlPanel">
									<param name="type" value="audio/x-pn-realaudio-plugin">
									<param name="src" value="<?php print isset($pa_options["url"]) ? $pa_options["url"] : $ps_url; ?>">

									<embed name="id_<?php print $vs_name; ?>_controls" src="<?php print isset($pa_options["url"]) ? $pa_options["url"] : $ps_url; ?>" width="<?php print $pa_properties["width"]; ?>" height="32"
										console="Clip1" controls="ControlPanel" type="audio/x-pn-realaudio-plugin">
									</embed>
								</object>
							</td>
						</tr>
<?php
					}
?>
					</table>
<?php
					return ob_get_clean();
				}
				break;
			# ------------------------------------------------
			case 'video/x-ms-asf':
			case 'video/x-ms-wmv':
				$vs_name = $pa_options["name"] ? $pa_options["name"] : "pwmv";

				$vb_show_controls = (isset($pa_options["show_controls"]) && $pa_options["show_controls"]) ? "1" : "0";

				ob_start();

				if (isset($pa_options["text_only"]) && $pa_options["text_only"]) {
					return "<a href='".(isset($pa_options["url"]) ? $pa_options["url"] : $ps_url)."'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "View WindowsMedia")."</a>";
				} else {
?>
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<object id="<?php print $vs_name; ?>"
									standby="Loading Microsoft Windows Media Player components..."
									type="application/x-oleobject"
									width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"] + ($vb_show_controls == 'true' ? 45 : 0); ?>"
									codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715"
									classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95">
									<param name="ShowControls" value="<?php print $vb_show_controls; ?>">
									<param name="ShowAudioControls" value="<?php print $vb_show_controls; ?>">
									<param name="ShowPositionControls" value="<?php print $vb_show_controls; ?>">
									<param name="ShowTracker" value="<?php print $vb_show_controls; ?>">
									<param name="ShowStatusBar" value="<?php print $vb_show_controls; ?>">
									<param name="ShowDisplay" value="<?php print $vb_show_controls; ?>">
									<param name="AnimationatStart" value="0">
									<param name="AutoStart" value="1">
									<param name="FileName" value="<?php print isset($pa_options["url"]) ? $pa_options["url"] : $ps_url; ?>">
									<param name="AllowChangeDisplaySize" value="1">
									<param name="DisplaySize" value="0">

									<embed  src="<?php print isset($pa_options["url"]) ? $pa_options["url"] : $ps_url; ?>"
										name="<?php print $vs_name; ?>"
										id="<?php print $vs_name; ?>"
										width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"] + ($vb_show_controls == 'true' ? 45 : 0); ?>"
										AutoStart="1"
										AnimationatStart="0"
										ShowControls="<?php print $vb_show_controls; ?>"
										ShowAudioControls="<?php print $vb_show_controls; ?>"
										ShowPositionControls="<?php print $vb_show_controls; ?>"
										ShowStatusBar="<?php print $vb_show_controls; ?>"
										ShowTracker="<?php print $vb_show_controls; ?>"
										ShowDisplay="<?php print $vb_show_controls; ?>"
										AllowChangeDisplaySize="1"
										DisplaySize="0"
										TYPE="application/x-mplayer2"
										PLUGINSPAGE="http://www.microsoft.com/isapi/redir.dll?prd=windows&sbp=mediaplayer&ar=Media&sba=Plugin&">
									</embed>
								</object>
							</td>
						</tr>
					</table>
<?php
					return ob_get_clean();
				}
			# ------------------------------------------------
			case 'video/quicktime':
				$vs_name = $pa_options["name"] ? $pa_options["name"] : "qplayer";

				$vn_width =				$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =			$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				ob_start();

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "View QuickTime")."</a>";
				} else {
?>
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
									width="<?php print $vn_width; ?>" height="<?php print $vn_height + 16; ?>"
 									codebase="http://www.apple.com/qtactivex/qtplugin.cab">
									<param name="src" VALUE="<?php print $ps_url; ?>">
									<param name="autoplay" VALUE="true">
									<param name="controller" VALUE="true">

									<embed  src="<?php print $ps_url; ?>"
										name="id_<?php print $vs_name; ?>"
										width="<?php print $vn_width; ?>" height="<?php print $vn_height + 16; ?>"
										autoplay="true" controller="true" kioskmode="true"
										pluginspage="http://www.apple.com/quicktime/download/"
										type="video/quicktime"
									>
									</embed>
								</object>
							</td>
						</tr>
					</table>
<?php
					return ob_get_clean();
				}
				break;
			# ------------------------------------------------
			case "video/x-flv":
			case 'video/mpeg':
			case 'audio/mpeg':
			case 'video/mp4':
				$vs_id = 				$pa_options["id"] ? $pa_options["id"] : "mp4_player";

				$vs_poster_frame_url =	$pa_options["poster_frame_url"];
				
				$vs_flash_vars = 		$pa_options["viewer_parameters"];
				$viewer_base_url =		$pa_options["viewer_base_url"];

				$vn_width =				$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =			$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				
				$va_captions = 			caGetOption("captions", $pa_options, array(), array('castTo' => 'array'));
				
				ob_start();
	
			$vs_config = 'config={"playlist":[{"url":"'.$vs_poster_frame_url.'", "scaling": "fit"}, {"url": "'.$ps_url.'","autoPlay":false,"autoBuffering":true, "scaling": "fit"}]};';
			$vb_is_rtmp = false;
			
			if(($vb_is_rtmp = (substr($ps_url, 0, 7) === 'rtmp://'))) { //  && (isset($pa_options['always_use_flash']) && $pa_options['always_use_flash'])) {
				$pa_options['always_use_flash'] = true;
				
				switch($pa_properties["mimetype"]) {
					case "video/x-flv":
						$vs_type = 'flv';
						break;
					case 'audio/mpeg':
						$vs_type = 'mp3';
						break;
					case 'video/mpeg':
					case 'video/mp4':
					default:
						$vs_type = 'mp4';
						break;
				}
				
				if ($vb_is_rtmp) {
					$va_volume_info =  (isset($pa_volume_info['accessUsingMirror']) && (bool)$pa_volume_info['accessUsingMirror']) ? $pa_volume_info['mirrors'][$pa_volume_info['accessUsingMirror']] : $pa_volume_info;
				
					$va_tmp = explode('/', $ps_url);
					$va_filename = explode('.', array_pop($va_tmp));
					$vs_ext = $va_filename[1];
					
					$vs_stub = ((isset($va_volume_info['accessProtocol']) ? $va_volume_info['accessProtocol'] : $va_volume_info['protocol'])).'://'.((isset($va_volume_info['accessHostname']) ? $va_volume_info['accessHostname'] : $va_volume_info['hostname'])).((isset($va_volume_info['accessUrlPath']) ? $va_volume_info['accessUrlPath'] : $va_volume_info['urlPath']));

					$vs_file_path = str_replace($vs_stub, '', $ps_url);
					$vs_file_path = preg_replace('!\.'.$vs_ext.'$!', '', $vs_file_path);
					
					
					$vs_config = '{ "playlist":[ {"url": "'.$va_volume_info['rtmpContentPath'].$vs_file_path.'", "provider": "streaming_server"} ], "plugins" : { "streaming_server" : { "url": "flowplayer.rtmp-3.2.3.swf", "netConnectionUrl": "'.((isset($va_volume_info['accessProtocol']) ? $va_volume_info['accessProtocol'] : $va_volume_info['protocol'])).'://'.((isset($va_volume_info['accessHostname']) ? $va_volume_info['accessHostname'] : $va_volume_info['hostname'])).$va_volume_info['rtmpMediaPrefix'].'" } } }';
				}
?>
				<div id="<?php print $vs_id; ?>" style="width: <?php print $vn_width; ?>px; height: <?php print $vn_height; ?>px;"> </div>
				<script type="text/javascript">
					flowplayer("<?php print $vs_id; ?>", "<?php print $viewer_base_url; ?>/viewers/apps/flowplayer-3.2.7.swf", <?php print $vs_config; ?>);
				</script>
<?php
			} else {
?>
			<!-- Begin VideoJS -->
			 <video id="<?php print $vs_id; ?>" class="video-js vjs-default-skin"  
				  controls preload="auto" width="<?php print $vn_width; ?>" height="<?php print $vn_height; ?>"  
				  poster="<?php print $vs_poster_frame_url; ?>"  
				  data-setup='{}'>  
				 <source src="<?php print $ps_url; ?>" type='video/mp4' />  
<?php
	if(is_array($va_captions)) {
		foreach($va_captions as $vn_locale_id => $va_caption_track) {
			print "<track kind=\"captions\" src=\"".$va_caption_track['url']."\" srclang=\"".$va_caption_track["locale_code"]."\" label=\"".$va_caption_track['locale']."\">\n";	
		}
	}
?>
				</video>
			<script type="text/javascript">
				_V_.players["<?php print $vs_id; ?>"] = undefined;	// make sure VideoJS doesn't think it has already loaded the viewer
				jQuery("#<?php print $vs_id; ?>").attr('width', jQuery('#<?php print $vs_id; ?>:parent').width()).attr('height', jQuery('#<?php print $vs_id; ?>:parent').height());
				_V_("<?php print $vs_id; ?>", {}, function() {});
			</script>
			<!-- End VideoJS -->
<?php
		}
?>

<?php
				return ob_get_clean();
				break;
			
			# ------------------------------------------------
			case 'video/ogg':
				
				$vs_id = 							$pa_options["id"] ? $pa_options["id"] : "mp4_player";
				$vs_poster_frame_url =	$pa_options["poster_frame_url"];
				$vn_width =						$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =					$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				
				return "<video id='{$vs_id}' src='{$ps_url}' width='{$vn_width}' height='{$vn_height}' controls='1'></video>";
				break;
			# ------------------------------------------------
			case 'video/x-matroska':
				
				$vs_id = 							$pa_options["id"] ? $pa_options["id"] : "mp4_player";
				$vs_poster_frame_url =	$pa_options["poster_frame_url"];
				$vn_width =						$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =					$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				
				return "<video id='{$vs_id}' src='{$ps_url}' width='{$vn_width}' height='{$vn_height}' controls='1'></video>";
				break;
			# ------------------------------------------------
			case 'application/x-shockwave-flash':
				$vs_name = $pa_options["name"] ? $pa_options["name"] : "swfplayer";

				#
				# We allow forcing of width and height for Flash media
				#
				# If you set a width or height, the Flash media will be scaled so it is as large as it
				# can be without exceeding either dimension
				if (isset($pa_options["width"]) || isset($pa_options["height"])) {
					$vn_ratio = 1;
					$vn_w_ratio = 0;
					if ($pa_options["width"] > 0) {
						$vn_ratio = $vn_w_ratio = $pa_options["width"]/$pa_properties["width"];
					}
					if ($pa_options["height"] > 0) {
						$vn_h_ratio = $pa_options["height"]/$pa_properties["height"];
						if (($vn_h_ratio < $vn_w_ratio) || (!$vn_w_ratio)) {
							$vn_ratio = $vn_h_ratio;
						}
					}

					$pa_options["width"] = intval($pa_properties["width"] * $vn_ratio);
					$pa_options["height"] = intval($pa_properties["height"] * $vn_ratio);
				}
				ob_start();

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "View Flash")."</a>";
				} else {
?>

			<div id="<?php print $vs_name; ?>">
				<h1><?php print _t('You must have the Flash Plug-in version 9.0.124 or better installed to play video and audio in CollectiveAccess'); ?></h1>
				<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function() { swfobject.embedSWF("<?php print $ps_url; ?>", "<?php print $vs_name; ?>", "<?php print isset($pa_options["width"]) ? $pa_options["width"] : $pa_properties["width"]; ?>", "<?php print isset($pa_options["height"]) ? $pa_options["height"] : $pa_properties["height"]; ?>", "9.0.124", "swf/expressInstall.swf", {}, {'allowscriptaccess': 'always', 'allowfullscreen' : 'true', 'allowNetworking' : 'all'}); });
			</script>
<?php
					return ob_get_clean();
				}
				break;
			# ------------------------------------------------
			case 'image/jpeg':
			case 'image/gif':
				if (!is_array($pa_options)) { $pa_options = array(); }
				if (!is_array($pa_properties)) { $pa_properties = array(); }
				return caHTMLImage($ps_url, array_merge($pa_options, $pa_properties));
				break;
				# ------------------------------------------------
		}
	}

	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
?>
