<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/Video.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2017 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/core/Parsers/TimecodeParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_APP_DIR__."/helpers/avHelpers.php");

class WLPlugMediaVideo Extends BaseMediaPlugin Implements IWLPlugMedia {

	var $errors = array();

	var $filepath;
	/**
	 * @var
	 */
	var $properties = array();
	var $oproperties = array();
	var $opa_media_metadata = array();

	var $info = array(
		"IMPORT" => array(
			"video/x-ms-asf" 					=> "asf",
			"video/x-ms-wmv"					=> "wmv",
			"video/quicktime" 					=> "mov",
			"video/avi" 						=> "avi",
			"video/x-flv"						=> "flv",
			"video/mpeg" 						=> "mpeg",
			"video/mp4" 						=> "m4v",
			"video/ogg"							=> "ogg",
			"video/x-matroska"					=> "webm",
			"video/x-dv"						=> "dv",
		),

		"EXPORT" => array(
			"video/x-ms-asf" 					=> "asf",
			"video/x-ms-wmv"					=> "wmv",
			"video/quicktime" 					=> "mov",
			"video/avi" 						=> "avi",
			"video/x-flv"						=> "flv",
			"video/mpeg" 						=> "mp4",
			"audio/mpeg"						=> "mp3",
			"image/jpeg"						=> "jpg",
			"image/png"							=> "png",
			"video/mp4" 						=> "m4v",
			"video/ogg"							=> "ogg",
			"video/x-matroska"					=> "webm",
			"video/x-dv"						=> "dv",
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
			"framerate"			=> 'R',
			"timecode_offset"	=> 'R',
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
		"video/x-ms-asf" 					=> "WindowsMedia",
		"video/x-ms-wmv"					=> "WindowsMedia",
		"video/quicktime" 					=> "QuickTime",
		"video/x-flv"						=> "FlashVideo (flv)",
		"video/mpeg" 						=> "MPEG",
		"audio/mpeg"						=> "MP3 audio",
		"image/jpeg"						=> "JPEG",
		"image/png"							=> "PNG",
		"video/mp4" 						=> "MPEG-4",
		"video/ogg"							=> "Ogg Theora",
		"video/x-matroska"					=> "WebM",
		"video/x-dv"						=> "DIF (DV)"
	);

	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->description = _t('Provides ffmpeg-based video processing');
	}
	# ------------------------------------------------
	/**
	 * What kinds of media does this plug-in support for import and export
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
		$va_status = parent::checkStatus();
		
		$this->register();
		$va_status['available'] = true;
		
		if(!caMediaPluginFFmpegInstalled()){
			$va_status['errors'][] = _t("Incoming video files will not be transcoded because ffmpeg is not installed.");
		}
		
		if (caMediaInfoInstalled()) {
			$va_status['notices'][] = _t("MediaInfo will be used to extract metadata from video files.");
		}
		return $va_status;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function divineFileFormat($filepath) {

		// first try mediainfo
		if($vs_mimetype = caMediaInfoGuessFileFormat($filepath)) {
			if($this->info["IMPORT"][$vs_mimetype]) {
				return $vs_mimetype;
			}
		}

		// then getID3
		if($vs_mimetype = caGetID3GuessFileFormat($filepath)) {
			if($this->info["IMPORT"][$vs_mimetype]) {
				return $vs_mimetype;
			}
		}

		// lastly, OggParser
		if($vs_mimetype = caOggParserGuessFileFormat($filepath)) {
			if($this->info["IMPORT"][$vs_mimetype]) {
				return $vs_mimetype;
			}
		}

		# file format is not supported by this plug-in
		return false;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function get($property) {
		if ($this->opa_media_metadata) {
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
	/**
	 *
	 */
	public function set($property, $value) {
		if ($this->opa_media_metadata) {
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
		// $this->opa_media_metadata might be extracted by mediainfo at this point or it might not
		// so we do it again. all calls are cached anyway so this should be too bad as far as performance
		if(caMediaInfoInstalled()) {
			return caExtractMetadataWithMediaInfo($this->filepath);
		} else {
			return $this->opa_media_metadata;
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function read ($filepath) {
		if (!file_exists($filepath)) {
			$this->postError(1650, _t("File %1 does not exist", $filepath), "WLPlugVideo->read()");
			$this->opa_media_metadata = array();
			$this->filepath = null;
			return false;
		}
		if (!(($this->opa_media_metadata) && ($this->opa_media_metadata["filepath"] == $filepath))) {

			// first try mediainfo
			if($vs_mimetype = caMediaInfoGuessFileFormat($filepath)) {
				$va_media_metadata = caExtractMetadataWithMediaInfo($filepath);

				$va_media_metadata['filepath'] = $filepath;
				$va_media_metadata['mime_type'] = $vs_mimetype;
				
				// Set properties for framerate and starting timecode
				$o_tc = new TimecodeParser();
				$o_tc->setTimebase($vn_framerate = trim(str_replace("fps", "", $va_media_metadata['VIDEO']['Frame rate'])));
				$o_tc->parse($va_media_metadata['OTHER']['Time code of first frame']);
				$this->properties['timecode_offset'] = (int)$o_tc->getSeconds();
				$this->properties['framerate'] = $vn_framerate;

				$this->opa_media_metadata = $va_media_metadata;
			} elseif($vs_mimetype = caGetID3GuessFileFormat($filepath)) {
				// then try getid3
				$this->opa_media_metadata = caExtractMetadataWithGetID3($filepath);
				$this->properties['timecode_offset'] = 0; // getID3 doesn't return offsets 
				$this->properties['framerate'] = (float)$this->opa_media_metadata['video']['frame_rate'];
			} else {
				// lastly, try ogg/ogv
				$this->opa_media_metadata = caExtractMediaMetadataWithOggParser($filepath);
				$this->properties['timecode_offset'] = 0; // OggParser doesn't return offsets 
				$this->properties['framerate'] = ((float)$this->opa_media_metadata['duration'] > 0) ? (float)$this->opa_media_metadata['framecount']/(float)$this->opa_media_metadata['duration'] : 0;
			}
		}

		if(!$this->opa_media_metadata['mime_type']) { return false; } // divineFileFormat() should prevent that, but you never know

		$w = $h = null;
		
		if (!((isset($this->opa_media_metadata["error"])) && (is_array($this->opa_media_metadata["error"])) && (sizeof($this->opa_media_metadata["error"]) > 0))) {
			$this->filepath = $filepath;

			// getID3 sometimes reports the wrong width and height in the resolution_x and resolution_y indices for FLV files, but does
			// report correct values in the 'meta' block. So for FLV only we try to take values from the 'meta' block first.
			if (($this->opa_media_metadata["mime_type"] == 'video/x-flv') && is_array($this->opa_media_metadata['meta']) && is_array($this->opa_media_metadata['meta']['onMetaData'])) {
				$w = $this->opa_media_metadata['meta']['onMetaData']['width'];
				$h = $this->opa_media_metadata['meta']['onMetaData']['height'];
			} else {
				if ($this->opa_media_metadata['mime_type'] == 'video/ogg') {
					$w = $this->opa_media_metadata['theora']['width'];
					$h = $this->opa_media_metadata['theora']['height'];
				}
			}
			if (!$w || !$h) {
				$w = $this->opa_media_metadata["video"]["resolution_x"];
				$h = $this->opa_media_metadata["video"]["resolution_y"];
			}
			if (!$w || !$h) {
				// maybe it's stuck in a stream?
				if (is_array($this->opa_media_metadata["video"]["streams"])) {
					foreach($this->opa_media_metadata["video"]["streams"] as $vs_key => $va_stream_info) {
						$w = $this->opa_media_metadata["video"]["streams"][$vs_key]["resolution_x"];
						$h = $this->opa_media_metadata["video"]["streams"][$vs_key]["resolution_y"];

						if ($w > 0 && $h > 0) {
							break;
						}
					}
				}
			}
			// maybe it came from mediainfo?
			if (!$w || !$h) {
				if(isset($this->opa_media_metadata['VIDEO']['Width'])) {
					$w = preg_replace("/[\D]/", '', $this->opa_media_metadata['VIDEO']['Width']);
				}
				if(isset($this->opa_media_metadata['VIDEO']['Height'])) {
					$h = preg_replace("/[\D]/", '', $this->opa_media_metadata['VIDEO']['Height']);
				}
			}

			$this->properties["width"] = $w;
			$this->properties["height"] = $h;

			$this->properties["mimetype"] = $this->opa_media_metadata["mime_type"];
			$this->properties["typename"] = $this->typenames[$this->properties["mimetype"]] ? $this->typenames[$this->properties["mimetype"]] : "Unknown";

			$this->properties["duration"] = $this->opa_media_metadata["playtime_seconds"];

			// getID3 sometimes messes up the duration. mediainfo seems a little more reliable so use it if it's available
			if($this->opb_mediainfo_available && ($vn_mediainfo_duration = caExtractVideoFileDurationWithMediaInfo($filepath))) {
				$this->properties['duration'] = $this->opa_media_metadata["playtime_seconds"] = $vn_mediainfo_duration;
			}

			$this->properties["filesize"] = filesize($filepath);

			# -- get bandwidth
			switch($this->properties["mimetype"]) {
				// in this case $this->opa_media_metadata definitely came from mediainfo
				// so the array structure is a little different than getID3
				case 'video/x-dv':
					$this->properties["has_video"] = isset($this->opa_media_metadata["VIDEO"]["Duration"]) ? 1 : 0;
					$this->properties["has_audio"] = isset($this->opa_media_metadata["AUDIO"]["Duration"]) ? 1 : 0;

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		$this->opa_media_metadata["GENERAL"]["Complete name"];
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$vn_bitrate = preg_replace("/[\D]/", '', $this->opa_media_metadata['VIDEO']['Bit rate']);
					$this->properties["bandwidth"] = array("min" => 0, "max" => $vn_bitrate);
					break;
				case 'video/x-ms-asf':
				case 'video/x-ms-wmv':
					$this->properties["has_video"] = (sizeof($this->opa_media_metadata["asf"]["video_media"]) ? 1 : 0);
					$this->properties["has_audio"] = (sizeof($this->opa_media_metadata["asf"]["audio_media"]) ? 1 : 0);

					$this->properties["type_specific"] = array("asf" => $this->opa_media_metadata["asf"]);

					$this->properties["title"] = 		$this->opa_media_metadata["asf"]["comments"]["title"];
					$this->properties["author"] = 		$this->opa_media_metadata["asf"]["comments"]["artist"];
					$this->properties["copyright"] = 	$this->opa_media_metadata["asf"]["comments"]["copyright"];
					$this->properties["description"] = 	$this->opa_media_metadata["asf"]["comments"]["comment"];

					$this->properties["bandwidth"] = array("min" => 0, "max" => $this->opa_media_metadata["bitrate"]);
					break;
				case 'video/quicktime':
				case 'video/mp4':
					$this->properties["has_video"] = (isset($this->opa_media_metadata["video"]["bitrate"]) && sizeof($this->opa_media_metadata["video"]["bitrate"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->opa_media_metadata["audio"]["bitrate"]) && sizeof($this->opa_media_metadata["audio"]["bitrate"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => (int)$this->opa_media_metadata["theora"]['nombitrate'] + (int)$this->opa_media_metadata["vorbis"]['bitrate'], "max" => (int)$this->opa_media_metadata["theora"]['nombitrate'] + (int)$this->opa_media_metadata["vorbis"]['bitrate']);
					break;
				case 'video/ogg':
					$this->properties["has_video"] = (isset($this->opa_media_metadata["theora"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->opa_media_metadata["vorbis"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->opa_media_metadata["bitrate"], "max" => $this->opa_media_metadata["bitrate"]);
					break;
				case 'application/x-shockwave-flash':
					$this->properties["has_video"] = (($this->opa_media_metadata["header"]["frame_width"] > 0) ? 1 : 0);
					$this->properties["has_audio"] = 1;

					$this->properties["type_specific"] = array("header" => $this->opa_media_metadata["header"]);

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->opa_media_metadata["filesize"]/$this->opa_media_metadata["playtime_seconds"], "max" => $this->opa_media_metadata["filesize"]/$this->opa_media_metadata["playtime_seconds"]);
					break;
				case 'video/mpeg':
					$this->properties["has_video"] = (isset($this->opa_media_metadata["video"]["bitrate"]) && sizeof($this->opa_media_metadata["video"]["bitrate"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->opa_media_metadata["audio"]["bitrate"]) && sizeof($this->opa_media_metadata["audio"]["bitrate"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->opa_media_metadata["bitrate"], "max" => $this->opa_media_metadata["bitrate"]);
					break;
				case 'video/x-flv':
					$this->properties["has_video"] = (sizeof($this->opa_media_metadata["header"]["hasVideo"]) ? 1 : 0);
					$this->properties["has_audio"] = (sizeof($this->opa_media_metadata["header"]["hasAudio"]) ? 1 : 0);

					$this->properties["type_specific"] = array("header" => $this->opa_media_metadata["header"]);

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$vn_bitrate = $this->opa_media_metadata["filesize"]/$this->opa_media_metadata["playtime_seconds"];

					$this->properties["bandwidth"] = array("min" => $vn_bitrate, "max" => $vn_bitrate);
					break;
			}

			$this->oproperties = $this->properties;

			return 1;
		} else {
			$this->postError(1650, join("; ", $this->opa_media_metadata["error"]), "WLPlugVideo->read()");
			$this->opa_media_metadata = "";
			$this->filepath = "";
			return false;
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function transform($operation, $parameters) {
		if (!$this->opa_media_metadata) { return false; }
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
					$this->postError(1610, _t("Width or height was zero"), "WLPlugVideo->transform()");
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
	/**
	 *
	 */
	public function write($filepath, $mimetype, $pa_options=null) {
		if (!$this->opa_media_metadata) { return false; }
		if (!($ext = $this->info["EXPORT"][$mimetype])) {
			# this plugin can't write this mimetype
			return false;
		}
		$o_tc = new TimecodeParser();

		# is mimetype valid?
		switch($mimetype) {
			# ------------------------------------
			case 'image/jpeg':
				$vn_preview_width = $this->properties["width"];
				$vn_preview_height = $this->properties["height"];

				if (caMediaPluginFFmpegInstalled() && ($this->opa_media_metadata["mime_type"] != 'application/x-shockwave-flash')) {
					if (!($vn_start_secs = ($o_tc->parse($this->opo_app_config->get('video_poster_frame_grab_at'))) ? $o_tc->getSeconds() : 5)) {
						$vn_start_secs = 5;
					}

					exec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f image2 -ss ".($vn_start_secs)." -t 0.04 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
					if (($vn_return < 0) || ($vn_return > 1) || (!@filesize($filepath.".".$ext))) {
						@unlink($filepath.".".$ext);
						// try again, with -ss 1 (seems to work consistently on some files where other -ss values won't work)
						exec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f image2 -ss ".($vn_start_secs)." -t 0.04 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
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

				if (caMediaPluginFFmpegInstalled() && ($this->opa_media_metadata["mime_type"] != "application/x-shockwave-flash")) {
					if (!($vn_start_secs = ($o_tc->parse($this->opo_app_config->get('video_poster_frame_grab_at'))) ? $o_tc->getSeconds() : 5)) {
						$vn_start_secs = 5;
					}
					
					exec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -vcodec png -ss ".($vn_start_secs)." -t 0.04 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
					if (($vn_return < 0) || ($vn_return > 1) || (!@filesize($filepath.".".$ext))) {
						@unlink($filepath.".".$ext);
						// try again, with -ss 1 (seems to work consistently on some files where other -ss values won't work)
						exec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -vcodec png -ss ".($vn_start_secs)." -t 0.04 -s {$vn_preview_width}x{$vn_preview_height} -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
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
				if (caMediaPluginFFmpegInstalled()) {
					$vn_video_bitrate = $this->get('video_bitrate');
					if ($vn_video_bitrate < 20000) { $vn_video_bitrate = 256000; }
					$vn_audio_bitrate = $this->get('audio_bitrate');
					if ($vn_audio_bitrate < 8000) { $vn_audio_bitrate = 32000; }
					$vn_audio_sample_freq = $this->get('audio_sample_freq');
					if (($vn_audio_sample_freq != 44100) && ($vn_audio_sample_freq != 22050) && ($vn_audio_sample_freq != 11025)) {
						$vn_audio_sample_freq = 44100;
					}
					exec($vs_cmd = caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f flv -b ".intval($vn_video_bitrate)." -ab ".intval($vn_audio_bitrate)." -ar ".intval($vn_audio_sample_freq)." -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
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
			// only support "command" option...
			case 'video/mpeg':
			case 'video/ogg':
			case 'video/x-dv':
				if (caMediaPluginFFmpegInstalled()) {
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
						exec($vs_cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." {$vs_ffmpeg_command} ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
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
							exec($vs_cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -acodec libfaac {$vs_other_params} -vpre {$vs_vpreset} -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
						} else {
							if(!$vb_twopass) {
								exec($vs_cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -acodec libfaac ".join(" ",$va_ffmpeg_params)." -y ".caEscapeShellArg($filepath.".".$ext). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $va_output, $vn_return);
							} else {
								exec($vs_cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -pass 1 -acodec libfaac ".join(" ",$va_ffmpeg_params)." -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
								exec($vs_cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -pass 2 -acodec libfaac ".join(" ",$va_ffmpeg_params)." -y ".caEscapeShellArg($filepath.".".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
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
						exec($this->ops_path_to_qt_faststart." ".caEscapeShellArg($filepath.".".$ext)." ".caEscapeShellArg($filepath."_tmp.".$ext). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
						rename("{$filepath}_tmp.{$ext}", "{$filepath}.{$ext}");
					}
					# ------------------------------------
					$this->properties["mimetype"] = $mimetype;
					$this->properties["typename"] = $this->typenames[$mimetype];
				}
				break;
			# ------------------------------------
			default:
				if (($mimetype != $this->opa_media_metadata["mime_type"])) {
					# this plugin can't write this mimetype (no conversions allowed)
					$this->postError(1610, _t("Can't convert '%1' to %2", $this->opa_media_metadata["mime_type"], $mimetype), "WLPlugVideo->write()");
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
		if (!(bool)$this->getAppConfig()->get("video_preview_generate_frames") && (!isset($pa_options['force']) || !$pa_options['force'])) { return false; }
		if (!caMediaPluginFFmpegInstalled()) return false;
		
		if (!isset($pa_options['outputDirectory']) || !$pa_options['outputDirectory'] || !file_exists($pa_options['outputDirectory'])) {
			if (!($vs_tmp_dir = $this->getAppConfig()->get("taskqueue_tmp_directory"))) {
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
		
		exec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f image2 -r ".$vs_freq." -ss {$vn_s} -t {$vn_previewed_duration} -s ".$vn_preview_width."x".$vn_preview_height." -y ".caEscapeShellArg($vs_output_file). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
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
		if (!caMediaPluginFFmpegInstalled()) return false;
		$o_tc = new TimecodeParser();
		
		$vn_start = $vn_end = null;
		if ($o_tc->parse($ps_start)) { $vn_start = $o_tc->getSeconds(); }
		if ($o_tc->parse($ps_end)) { $vn_end = $o_tc->getSeconds(); }
		
		if (!$vn_start || !$vn_end) { return null; }
		if ($vn_start >= $vn_end) { return null; }
		
		$vn_duration = $vn_end - $vn_start;
		
		exec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -acodec mp3 -t {$vn_duration}  -y -ss {$vn_start} ".caEscapeShellArg($ps_filepath). (caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
		if ($vn_return != 0) {
			@unlink($ps_filepath);
			$this->postError(1610, _t("Error extracting clip from %1 to %2: %3", $ps_start, $ps_end, join("; ", $va_output)), "WLPlugVideo->writeClip()");
			return false;
		}
		
		return true;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getOutputFormats() {
		return $this->info["EXPORT"];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getTransformations() {
		return $this->info["TRANSFORMATIONS"];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function mimetype2extension($mimetype) {
		return $this->info["EXPORT"][$mimetype];
	}
	# ------------------------------------------------
	/**
	 *
	 */
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
	/**
	 *
	 */
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function reset() {
		$this->errors = array();
		$this->properties = $this->oproperties;
		return $this->opa_media_metadata;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function init() {
		$this->errors = array();
		$this->filepath = null;
		$this->properties = array();
		$this->opa_media_metadata = array();
	}
	# ------------------------------------------------
	/**
	 *
	 */
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
			case 'video/quicktime':
				$vs_name = $pa_options["name"] ? $pa_options["name"] : "qplayer";

				$vn_width =				$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =			$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				ob_start();

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "View QuickTime")."</a>";
				} else {
?>
					<table>
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
?>
			<!-- Begin VideoJS -->
			 <video id="<?php print $vs_id; ?>" class="video-js vjs-default-skin"  
				  controls preload="auto" width="<?php print $vn_width; ?>" height="<?php print $vn_height; ?>"  
				  poster="<?php print $vs_poster_frame_url; ?>"  
				  data-setup='{}'>  
				 <source src="<?php print $ps_url; ?>" type="video/mp4" />
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
				
				w = jQuery('#<?php print $vs_id; ?>:parent').width();
				if ((h = jQuery('#<?php print $vs_id; ?>:parent').height()) < 100) {
					h = Math.ceil(w * .7);
				}
				jQuery("#<?php print $vs_id; ?>").attr('width', w).attr('height', h);
				jQuery("#<?php print $vs_id; ?>").attr('style', 'width:' + w + 'px; height: ' + h + 'px;');
				_V_("<?php print $vs_id; ?>", {}, function() {});
				
				if (caUI.mediaPlayerManager) { caUI.mediaPlayerManager.register("<?php print $vs_id; ?>", _V_.players["<?php print $vs_id; ?>"], 'VideoJS'); }
			</script>
			<!-- End VideoJS -->
<?php
				return ob_get_clean();
				break;
			# ------------------------------------------------
			case 'video/ogg':
			case 'video/x-matroska':
			case "video/x-ms-asf":
			case "video/x-ms-wmv":
				$vs_id = 						$pa_options["id"] ? $pa_options["id"] : "mp4_player";
				$vs_poster_frame_url =			$pa_options["poster_frame_url"];
				$vn_width =						$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =					$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				
				return "<video id='{$vs_id}' src='{$ps_url}' width='{$vn_width}' height='{$vn_height}' controls='1'></video>";
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