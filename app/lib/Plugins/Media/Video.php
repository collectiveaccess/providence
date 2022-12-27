<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Media/Video.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2022 Whirl-i-Gig
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

include_once(__CA_LIB_DIR__."/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/Parsers/TimecodeParser.php");
include_once(__CA_LIB_DIR__."/Configuration.php");
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
	var $media_metadata = array();
	
	var $path_to_ffmeg = null;

	var $info = array(
		"IMPORT" => array(
			"video/x-ms-asf" 					=> "asf",
			"video/x-ms-wmv"					=> "wmv",
			"video/quicktime" 					=> "mov",
			"video/avi" 						=> "avi",
			"video/x-flv"						=> "flv",
			"video/mpeg" 						=> "mpeg",
			"video/mp4" 						=> "m4v",
			"video/MP2T"						=> "mts",
			"video/ogg"							=> "ogg",
			"video/x-matroska"					=> "mkv",
			"video/x-dv"						=> "dv",
		),

		"EXPORT" => array(
			"video/x-ms-asf" 					=> "asf",
			"video/x-ms-wmv"					=> "wmv",
			"video/quicktime" 					=> "mov",
			"video/avi" 						=> "avi",
			"video/x-flv"						=> "flv",
			"video/mpeg" 						=> "mp4",
			"video/MP2T"						=> "mts",
			"audio/mpeg"						=> "mp3",
			"image/jpeg"						=> "jpg",
			"image/png"							=> "png",
			"video/mp4" 						=> "m4v",
			"video/ogg"							=> "ogg",
			"video/x-matroska"					=> "mkv",
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
		"video/MP2T"						=> "MTS",
		"video/ogg"							=> "Ogg Theora",
		"video/x-matroska"					=> "Matroska",
		"video/x-dv"						=> "DIF (DV)"
	);
	
	#
	# Alternative extensions for supported types
	#
	var $alternative_extensions = [
		"mp4" => "video/mp4",
		"qt" => "video/quicktime"
	];

	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->description = _t('Provides ffmpeg-based video processing');
		
		$this->ops_path_to_ffmeg = caMediaPluginFFmpegInstalled();
		$this->ops_path_to_mediainfo = caMediaInfoInstalled();
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
		$status = parent::checkStatus();
		
		$this->register();
		$status['available'] = true;
		
		if(!$this->ops_path_to_ffmeg){
			$status['errors'][] = _t("Incoming video files will not be transcoded because ffmpeg is not installed.");
		}
		
		if ($this->ops_path_to_mediainfo) {
			$status['notices'][] = _t("MediaInfo will be used to extract metadata from video files.");
		}
		return $status;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function divineFileFormat($filepath) {

		// first try mediainfo
		if($mimetype = caMediaInfoGuessFileFormat($filepath)) {
			if($this->info["IMPORT"][$mimetype] ?? null) {
				return $mimetype;
			}
		}

		// then getID3
		if($mimetype = caGetID3GuessFileFormat($filepath)) {
			if($this->info["IMPORT"][$mimetype] ?? null) {
				return $mimetype;
			}
		}

		// lastly, OggParser
		if($mimetype = caOggParserGuessFileFormat($filepath)) {
			if($this->info["IMPORT"][$mimetype] ?? null) {
				return $mimetype;
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
		if ($this->media_metadata) {
			if ($this->info["PROPERTIES"][$property] ?? null) {
				return $this->properties[$property] ?? null;
			} else {
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
		if ($this->media_metadata ?? null) {
			if ($this->info["PROPERTIES"][$property] ?? null) {
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
		} 
		return '';
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		// $this->media_metadata might be extracted by mediainfo at this point or it might not
		// so we do it again. all calls are cached anyway so this should be too bad as far as performance
		if($this->ops_path_to_mediainfo) {
			return caExtractMetadataWithMediaInfo($this->filepath);
		} else {
			return $this->media_metadata;
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function read ($filepath, $mimetype="", $options=null) {
		if (!file_exists($filepath)) {
			$this->postError(1650, _t("File %1 does not exist", $filepath), "WLPlugVideo->read()");
			$this->media_metadata = array();
			$this->filepath = null;
			return false;
		}
		if (!(($this->media_metadata) && ($this->media_metadata["filepath"] == $filepath))) {

			// first try mediainfo
			if($mimetype = caMediaInfoGuessFileFormat($filepath)) {
				$media_metadata = caExtractMetadataWithMediaInfo($filepath);

				$media_metadata['filepath'] = $filepath;
				$media_metadata['mime_type'] = $mimetype;
				
				// Set properties for framerate and starting timecode
				$o_tc = new TimecodeParser();
				$o_tc->setTimebase($framerate = trim(str_replace("fps", "", $media_metadata['VIDEO']['Frame rate']?? null)));
				$o_tc->parse($media_metadata['OTHER']['Time code of first frame']?? null);
				$this->properties['timecode_offset'] = (int)$o_tc->getSeconds();
				$this->properties['framerate'] = $framerate;

				$this->media_metadata = $media_metadata;
			} elseif($mimetype = caGetID3GuessFileFormat($filepath)) {
				// then try getid3
				$this->media_metadata = caExtractMetadataWithGetID3($filepath);
				$this->properties['timecode_offset'] = 0; // getID3 doesn't return offsets 
				$this->properties['framerate'] = (float)$this->media_metadata['video']['frame_rate'];
			} else {
				// lastly, try ogg/ogv
				$this->media_metadata = caExtractMediaMetadataWithOggParser($filepath);
				$this->properties['timecode_offset'] = 0; // OggParser doesn't return offsets 
				$this->properties['framerate'] = ((float)$this->media_metadata['duration'] > 0) ? (float)$this->media_metadata['framecount']/(float)$this->media_metadata['duration'] : 0;
			}
		}

		if(!$this->media_metadata['mime_type']) { return false; } // divineFileFormat() should prevent that, but you never know

		$w = $h = null;
		
		if (!((isset($this->media_metadata["error"])) && (is_array($this->media_metadata["error"])) && (sizeof($this->media_metadata["error"]) > 0))) {
			$this->filepath = $filepath;

			// getID3 sometimes reports the wrong width and height in the resolution_x and resolution_y indices for FLV files, but does
			// report correct values in the 'meta' block. So for FLV only we try to take values from the 'meta' block first.
			if (($this->media_metadata["mime_type"] == 'video/x-flv') && is_array($this->media_metadata['meta']) && is_array($this->media_metadata['meta']['onMetaData'])) {
				$w = $this->media_metadata['meta']['onMetaData']['width'] ?? null;
				$h = $this->media_metadata['meta']['onMetaData']['height'] ?? null;
			} else {
				if ($this->media_metadata['mime_type'] == 'video/ogg') {
					$w = $this->media_metadata['theora']['width'] ?? null;
					$h = $this->media_metadata['theora']['height'] ?? null;
				}
			}
			if (!$w || !$h) {
				$w = $this->media_metadata["video"]["resolution_x"] ?? null;
				$h = $this->media_metadata["video"]["resolution_y"] ?? null;
			}
			if (!$w || !$h) {
				// maybe it's stuck in a stream?
				if (is_array($this->media_metadata["video"]["streams"] ?? null)) {
					foreach($this->media_metadata["video"]["streams"] as $key => $stream_info) {
						$w = $this->media_metadata["video"]["streams"][$key]["resolution_x"] ?? null;
						$h = $this->media_metadata["video"]["streams"][$key]["resolution_y"] ?? null;

						if ($w > 0 && $h > 0) {
							break;
						}
					}
				}
			}
			// maybe it came from mediainfo?
			if (!$w || !$h) {
				if(isset($this->media_metadata['VIDEO']['Width'])) {
					$w = preg_replace("/[\D]/", '', $this->media_metadata['VIDEO']['Width']);
				}
				if(isset($this->media_metadata['VIDEO']['Height'])) {
					$h = preg_replace("/[\D]/", '', $this->media_metadata['VIDEO']['Height']);
				}
			}

			$this->properties["width"] = $w;
			$this->properties["height"] = $h;

			$this->properties["mimetype"] = $this->media_metadata["mime_type"];
			$this->properties["typename"] = isset($this->typenames[$this->properties["mimetype"]]) ? $this->typenames[$this->properties["mimetype"]] : _t("Unknown");

			$this->properties["duration"] = $this->media_metadata["playtime_seconds"];

			// getID3 sometimes messes up the duration. mediainfo seems a little more reliable so use it if it's available
			if($this->ops_path_to_mediainfo && ($mediainfo_duration = caExtractVideoFileDurationWithMediaInfo($filepath))) {
				$this->properties['duration'] = $this->media_metadata["playtime_seconds"] = $mediainfo_duration;
			}

			$this->properties["filesize"] = filesize($filepath);

			# -- get bandwidth
			switch($this->properties["mimetype"]) {
				// in this case $this->media_metadata definitely came from mediainfo
				// so the array structure is a little different than getID3
				case 'video/x-dv':
					$this->properties["has_video"] = isset($this->media_metadata["VIDEO"]["Duration"]) ? 1 : 0;
					$this->properties["has_audio"] = isset($this->media_metadata["AUDIO"]["Duration"]) ? 1 : 0;

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		$this->media_metadata["GENERAL"]["Complete name"] ?? null;
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$bitrate = preg_replace("/[\D]/", '', $this->media_metadata['VIDEO']['Bit rate'] ?? null);
					$this->properties["bandwidth"] = array("min" => 0, "max" => $bitrate);
					break;
				case 'video/x-ms-asf':
				case 'video/x-ms-wmv':
					$this->properties["has_video"] = (($this->media_metadata["asf"]["video_media"]) ? 1 : 0);
					$this->properties["has_audio"] = (($this->media_metadata["asf"]["audio_media"]) ? 1 : 0);

					$this->properties["type_specific"] = array("asf" => $this->media_metadata["asf"]);

					$this->properties["title"] = 		$this->media_metadata["asf"]["comments"]["title"] ?? null;
					$this->properties["author"] = 		$this->media_metadata["asf"]["comments"]["artist"] ?? null;
					$this->properties["copyright"] = 	$this->media_metadata["asf"]["comments"]["copyright"] ?? null;
					$this->properties["description"] = 	$this->media_metadata["asf"]["comments"]["comment"] ?? null;

					$this->properties["bandwidth"] = array("min" => 0, "max" => $this->media_metadata["bitrate"] ?? null);
					break;
				case 'video/quicktime':
				case 'video/mp4':
				case 'video/MP2T':
					$this->properties["has_video"] = (isset($this->media_metadata["video"]["bitrate"]) && ($this->media_metadata["video"]["bitrate"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->media_metadata["audio"]["bitrate"]) && ($this->media_metadata["audio"]["bitrate"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->media_metadata["bitrate"] ?? null, "max" => $this->media_metadata["bitrate"] ?? null);
					break;
				case 'video/ogg':
					$this->properties["has_video"] = (isset($this->media_metadata["theora"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->media_metadata["vorbis"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->media_metadata["bitrate"] ?? null, "max" => $this->media_metadata["bitrate"] ?? null);
					break;
				case 'application/x-shockwave-flash':
					$this->properties["has_video"] = (($this->media_metadata["header"]["frame_width"] > 0) ? 1 : 0);
					$this->properties["has_audio"] = 1;

					$this->properties["type_specific"] = array("header" => $this->media_metadata["header"] ?? null);

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->media_metadata["filesize"]/$this->media_metadata["playtime_seconds"], "max" => $this->media_metadata["filesize"]/$this->media_metadata["playtime_seconds"]);
					break;
				case 'video/mpeg':
					$this->properties["has_video"] = (isset($this->media_metadata["video"]["bitrate"]) && ($this->media_metadata["video"]["bitrate"]) ? 1 : 0);
					$this->properties["has_audio"] = (isset($this->media_metadata["audio"]["bitrate"]) && ($this->media_metadata["audio"]["bitrate"]) ? 1 : 0);

					$this->properties["type_specific"] = array();

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$this->properties["bandwidth"] = array("min" => $this->media_metadata["bitrate"], "max" => $this->media_metadata["bitrate"]);
					break;
				case 'video/x-flv':
					$this->properties["has_video"] = (($this->media_metadata["header"]["hasVideo"]) ? 1 : 0);
					$this->properties["has_audio"] = (($this->media_metadata["header"]["hasAudio"]) ? 1 : 0);

					$this->properties["type_specific"] = array("header" => $this->media_metadata["header"]);

					$this->properties["title"] = 		"";
					$this->properties["author"] = 		"";
					$this->properties["copyright"] = 	"";
					$this->properties["description"] = 	"";

					$bitrate = $this->media_metadata["filesize"]/$this->media_metadata["playtime_seconds"];

					$this->properties["bandwidth"] = array("min" => $bitrate, "max" => $bitrate);
					break;
			}

			$this->oproperties = $this->properties;

			return 1;
		} else {
			$this->postError(1650, join("; ", $this->media_metadata["error"]), "WLPlugVideo->read()");
			$this->media_metadata = "";
			$this->filepath = "";
			return false;
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function transform($operation, $parameters) {
		if (!$this->media_metadata) { return false; }
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugVideo->transform()");
			return false;
		}
		
		$do_crop = 0;
		
		# get parameters for this operation
		$this->properties["version_width"] = $w = $parameters["width"] ?? null;
		$this->properties["version_height"] = $h = $parameters["height"] ?? null;
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
	public function write($filepath, $mimetype, $options=null) {
		if (!$this->media_metadata) { return false; }
		if (!($ext = $this->info["EXPORT"][$mimetype])) {
			# this plugin can't write this mimetype
			return false;
		}
		$o_tc = new TimecodeParser();
		
		$grab_at = $this->opo_app_config->get('video_poster_frame_grab_at');
        if(preg_match("!([\d]+)%!", $grab_at, $matches)) {
            $start_secs = ceil((float)$this->get('duration') * ($matches[1]/100));
        } elseif (!($start_secs = ($o_tc->parse($this->opo_app_config->get('video_poster_frame_grab_at'))) ? $o_tc->getSeconds() : 0)) {
            $start_secs = 0;
        }
        
        // If start is past end of video force to beginning
        if($start_secs > (float)$this->get('duration')) { $start_secs = 0; }
		# is mimetype valid?
		switch($mimetype) {
			# ------------------------------------
			case 'image/jpeg':
				$preview_width = $this->properties["width"];
				$preview_height = $this->properties["height"];

				if (caMediaPluginFFmpegInstalled() && ($this->media_metadata["mime_type"] != 'application/x-shockwave-flash')) {
					caExec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f image2 -ss ".($start_secs)." -t 0.04 -vf \"scale=w={$preview_width}:h={$preview_height}:force_original_aspect_ratio=decrease,pad={$preview_width}:{$preview_height}:(ow-iw)/2:(oh-ih)/2\"  -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
					$exists = file_exists("{$filepath}.{$ext}");
					if (($return < 0) || ($return > 1) || (!$exists || !@filesize("{$filepath}.{$ext}"))) {
						if($exists) { @unlink("{$filepath}.{$ext}"); }
						// try again, without attempting to force aspect ratio, as this seems to cause ffmpeg to barf at specific frame sizes and input aspect ratios
						caExec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f image2 -ss ".($start_secs)." -t 0.04 -vf \"scale=w={$preview_width}:h={$preview_height}\"  -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
					}

					if (($return < 0) || ($return > 1) || (!$exists || !@filesize("{$filepath}.{$ext}"))) {
						if($exists) { @unlink("{$filepath}.{$ext}"); }
						// don't throw error as ffmpeg cannot generate frame still from all file
					}
				}

				$this->properties["mimetype"] = $mimetype;
				$this->properties["typename"] = isset($this->typenames[$mimetype]) ? $this->typenames[$mimetype] : $mimetype;

				break;
			# ------------------------------------
			case 'image/png':
				$preview_width = $this->properties["width"];
				$preview_height = $this->properties["height"];

				if (caMediaPluginFFmpegInstalled() && ($this->media_metadata["mime_type"] != "application/x-shockwave-flash")) {
					caExec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -vcodec png -ss ".($start_secs)." -t 0.04 -s {$preview_width}x{$preview_height} -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
					$exists = file_exists("{$filepath}.{$ext}");
					if (($return < 0) || ($return > 1) || (!$exists || !@filesize("{$filepath}.{$ext}"))) {
						if($exists) { @unlink("{$filepath}.{$ext}"); }
						// try again, with -ss 1 (seems to work consistently on some files where other -ss values won't work)
						caExec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -vcodec png -ss ".($start_secs)." -t 0.04  -vf \"scale=w={$preview_width}:h={$preview_height}:force_original_aspect_ratio=decrease,pad={$preview_width}:{$preview_height}:(ow-iw)/2:(oh-ih)/2\"   -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
					}

					if (($return < 0) || ($return > 1) || (!file_exists("{$filepath}.{$ext}") || !@filesize("{$filepath}.{$ext}"))) {
						@unlink("{$filepath}.{$ext}");
						// don't throw error as ffmpeg cannot generate frame still from all file
					}
				}

				$this->properties["mimetype"] = $mimetype;
				$this->properties["typename"] = isset($this->typenames[$mimetype]) ? $this->typenames[$mimetype] : $mimetype;

				break;
			# ------------------------------------
			case 'video/x-flv':
				if (caMediaPluginFFmpegInstalled()) {
					$video_bitrate = $this->get('video_bitrate');
					if ($video_bitrate < 20000) { $video_bitrate = 256000; }
					$audio_bitrate = $this->get('audio_bitrate');
					if ($audio_bitrate < 8000) { $audio_bitrate = 32000; }
					$audio_sample_freq = $this->get('audio_sample_freq');
					if (($audio_sample_freq != 44100) && ($audio_sample_freq != 22050) && ($audio_sample_freq != 11025)) {
						$audio_sample_freq = 44100;
					}
					caExec($cmd = caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f flv -b ".intval($video_bitrate)." -ab ".intval($audio_bitrate)." -ar ".intval($audio_sample_freq)." -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
					if (($return < 0) || ($return > 1) || (filesize("{$filepath}.{$ext}") == 0)) {
						@unlink("{$filepath}.{$ext}");
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
					$ffmpeg_params = array();

					if (!($ffmpeg_command = $this->get('command'))) {
						// Video bitrate
						$video_bitrate = $this->get('video_bitrate');
						if($video_bitrate!='') {
							if ($video_bitrate < 20000) {
								$video_bitrate = 256000;
							}
							$ffmpeg_params["video_bitrate"] = "-b ".intval($video_bitrate);
						}
						
						// Audio bitrate
						$audio_bitrate = $this->get('audio_bitrate');
						if ($audio_bitrate < 8000) { $audio_bitrate = 32000; }
						$ffmpeg_params["audio_bitrate"] = "-ab ".intval($audio_bitrate);
	
						// Audio sample frequency
						$audio_sample_freq = $this->get('audio_sample_freq');
						if (($audio_sample_freq != 44100) && ($audio_sample_freq != 22050) && ($audio_sample_freq != 11025)) {
							$audio_sample_freq = 44100;
						}
						$ffmpeg_params["audio_sample_freq"] = "-ar ".intval($audio_sample_freq);
	
						// Multithreading
						$threads = $this->get('threads');
						if ($threads < 1 || $threads == '') {
							$threads = 1;
						}
						$ffmpeg_params["threads"] = "-threads ".$threads;
	
						// Quantitizers
						$qmin = $this->get('qmin');
						if ($qmin != '') {
							$ffmpeg_params["qmin"] = "-qmin ".$qmin;
						}
						$qmax = $this->get('qmax');
						if ($qmax != '') {
							$ffmpeg_params["qmax"] = "-qmax ".$qmax;
						}
	
						// Flags
						if(($flags = $this->get('flags'))!=''){
							$ffmpeg_params["flags"] = "-flags ".$flags;
						}
	
						// Resolution
						if(($res = $this->get('resolution'))!=''){
							$ffmpeg_params["resolution"] = "-s ".$res;
						}
	
						// Coder
						if(($coder = $this->get('coder'))!=''){
							$ffmpeg_params["coder"] = "-coder ".$coder;
						}
	
						// 2-pass encoding
						if($this->get('twopass')){
							$vb_twopass = true;
						} else {
							$vb_twopass =false;
						}
						
						// qdiff
						if(($qdiff = $this->get('qdiff'))!=''){
							$ffmpeg_params["qdiff"] = "-qdiff ".$qdiff;
						}
						
						// partitions
						if(($partitions = $this->get('partitions'))!=''){
							$ffmpeg_params["partitions"] = "-partitions ".$partitions;
						}
						
						// cmp
						if(($cmp = $this->get('cmp'))!=''){
							$ffmpeg_params["cmp"] = "-cmp ".$cmp;
						}
						
						// qdiff
						if(($sc_threshold = $this->get('sc_threshold'))!=''){
							$ffmpeg_params["sc_threshold"] = "-sc_threshold ".$sc_threshold;
						}
						
						// vpre
						if(!($vpreset = $this->get('vpre'))!=''){
							$vpreset = null;
						}
					}

					// put it all together

					// we need to be in a directory where we can write (libx264 logfiles)
					$cwd = getcwd();
					chdir(__CA_APP_DIR__."/tmp/");
					
					$cmd = '';
					if ($ffmpeg_command) {
						caExec($cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." {$ffmpeg_command} ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
					} else {
						if ($vpreset) {
							$other_params = "";
							if($audio_bitrate){
								$other_params.="-ab {$audio_bitrate} ";
							}
							if($audio_sample_freq){
								$other_params.="-ar {$audio_sample_freq} ";
							}
							if($res && $res!=''){
								$other_params.="-s ".$res;
							}
							caExec($cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 {$other_params} -vpre {$vpreset} -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
						} else {
							if(!$vb_twopass) {
								caExec($cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 ".join(" ",$ffmpeg_params)." -y ".caEscapeShellArg("{$filepath}.{$ext}"). ((caGetOSFamily() == OS_POSIX) ? " 2> /dev/null" : ""), $output, $return);
							} else {
								caExec($cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -pass 1 ".join(" ",$ffmpeg_params)." -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
								caExec($cmd .= caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -pass 2 ".join(" ",$ffmpeg_params)." -y ".caEscapeShellArg("{$filepath}.{$ext}"). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
								// probably cleanup logfiles here
							}
						}
					}
					
					chdir($cwd); // avoid fun side-effects
					if (@filesize("{$filepath}.{$ext}") == 0) {
						@unlink("{$filepath}.{$ext}");
						if ($vpreset) {
							$this->postError(1610, _t("Couldn't convert file to MPEG4 format [%1]; does the ffmpeg preset '%2' exist? (command was %3)", $return, $vpreset, $cmd), "WLPlugVideo->write()");
						} else {
							$this->postError(1610, _t("Couldn't convert file to MPEG4 format [%1] (command was %2)", $return, $cmd), "WLPlugVideo->write()");
						}
						return false;
					}
					# ------------------------------------
					$this->properties["mimetype"] = $mimetype;
					$this->properties["typename"] = $this->typenames[$mimetype];
				}
				break;
			# ------------------------------------
			default:
				if (($mimetype != $this->media_metadata["mime_type"])) {
					# this plugin can't write this mimetype (no conversions allowed)
					$this->postError(1610, _t("Can't convert '%1' to %2", $this->media_metadata["mime_type"], $mimetype), "WLPlugVideo->write()");
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
		if (!file_exists("{$filepath}.{$ext}")) {
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
	public function &writePreviews($filepath, $options) {
		if (!(bool)$this->getAppConfig()->get("video_preview_generate_frames") && (!isset($options['force']) || !$options['force'])) { return false; }
		if (!caMediaPluginFFmpegInstalled()) return false;
		
		if (!isset($options['outputDirectory']) || !$options['outputDirectory'] || !file_exists($options['outputDirectory'])) {
			if (!($tmp_dir = $this->getAppConfig()->get("taskqueue_tmp_directory"))) {
				// no dir
				return false;
			}
		} else {
			$tmp_dir = $options['outputDirectory'];
		}
		
		$o_tc = new TimecodeParser();
		
		if (($min_number_of_frames = $options['minNumberOfFrames']) < 1) {
			$min_number_of_frames = 0;
		}
		
		if (($max_number_of_frames = $options['maxNumberOfFrames']) < 1) {
			$max_number_of_frames = 100;
		}
		
		$duration = $this->properties["duration"];
		if (!($frame_interval = ($o_tc->parse($options['frameInterval'])) ? $o_tc->getSeconds() : 0)) {
			$frame_interval = 30;
		}
		if (!($start_at = ($o_tc->parse($options['startAtTime'])) ? $o_tc->getSeconds() : 0)) {
			$start_at = 0;
		}
		if (!($end_at = ($o_tc->parse($options['endAtTime'])) ? $o_tc->getSeconds() : 0)) {
			$end_at = 0;
		}
		
		if (($previewed_duration = ($start_at - $end_at)) < 0) {
			$previewed_duration = $duration;
			$start_at = $end_at = 0;
		} else {
			// if start and end times are the same assume single frame mode and set duration to a sliver of time
			if ($previewed_duration == 0) { $previewed_duration = 0.1; }
		}
			
		if ($frame_interval > $previewed_duration) {
			$frame_interval = $previewed_duration;
		}
		
		$preview_width = (isset($options['width']) && ((int)$options['width'] > 0)) ? (int)$options['width'] : 320;
		$preview_height= (isset($options['height']) && ((int)$options['height'] > 0)) ? (int)$options['height'] : 320;
		
		$s = $start_at;
		$num_frames = ($frame_interval > 0) ? ceil($previewed_duration/$frame_interval) : 1;
		
		if ($num_frames < $min_number_of_frames) {
			$frame_interval = ($previewed_duration)/$min_number_of_frames;
			$num_frames = $min_number_of_frames;
			$previewed_duration = ($num_frames * $frame_interval);
		}
		if ($num_frames > $max_number_of_frames) {
			$frame_interval = ($previewed_duration)/$max_number_of_frames;
			$num_frames = $max_number_of_frames;
			$previewed_duration = ($num_frames * $frame_interval);
		}
		$freq = 1/$frame_interval;
		
		$output_file_prefix = tempnam($tmp_dir, 'caVideoPreview');
		$output_file = $output_file_prefix.'%05d.jpg';
		
		caExec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f image2 -r ".$freq." -ss {$s} -t {$previewed_duration} -vf \"scale=w={$preview_width}:h={$preview_height}:force_original_aspect_ratio=decrease,pad={$preview_width}:{$preview_height}:(ow-iw)/2:(oh-ih)/2\"  -y ".caEscapeShellArg($output_file). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
		$i = 1;
		$files = array();
		while(file_exists($output_file_prefix.sprintf("%05d", $i).'.jpg')) {
			// add frame to list
			$files[''.sprintf("%4.2f", ((($i - 1) * $frame_interval) + $s)).'s'] = $output_file_prefix.sprintf("%05d", $i).'.jpg';
		
			$i++;
		}
		
		if (!sizeof($files)) {
			$this->postError(1610, _t("Couldn't not write video preview frames to tmp directory (%1)", $tmp_dir), "WLPlugVideo->write()");
		}
		@unlink($output_file_prefix);
		return $files;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function writeClip($filepath, $start, $end, $options=null) {
		if (!caMediaPluginFFmpegInstalled()) return false;
		$o_tc = new TimecodeParser();
		
		$start = $end = null;
		if ($o_tc->parse($start)) { $start = $o_tc->getSeconds(); }
		if ($o_tc->parse($end)) { $end = $o_tc->getSeconds(); }
		
		if (!$start || !$end) { return null; }
		if ($start >= $end) { return null; }
		
		$duration = $end - $start;
		
		caExec(caGetExternalApplicationPath('ffmpeg')." -i ".caEscapeShellArg($this->filepath)." -f mp4 -vcodec libx264 -acodec mp3 -t {$duration}  -y -ss {$start} ".caEscapeShellArg($filepath). (caIsPOSIX() ? " 2> /dev/null" : ""), $output, $return);
		if ($return != 0) {
			@unlink($filepath);
			$this->postError(1610, _t("Error extracting clip from %1 to %2: %3", $start, $end, join("; ", $output)), "WLPlugVideo->writeClip()");
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
		return $this->media_metadata;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function init() {
		$this->errors = array();
		$this->filepath = null;
		$this->properties = array();
		$this->media_metadata = array();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function htmlTag($url, $properties, $options=null, $volume_info=null) {
		if (!is_array($options)) { $options = array(); }
		
		foreach(array(
			'name', 'show_controls', 'url', 'text_only', 'viewer_width', 'viewer_height', 'id',
			'poster_frame_url', 'viewer_parameters', 'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style'
		) as $k) {
			if (!isset($options[$k])) { $options[$k] = null; }
		}
		
		switch($properties["mimetype"]) {
			# ------------------------------------------------
			case 'video/quicktime':
				$name = $options["name"] ? $options["name"] : "qplayer";

				$width =				$options["viewer_width"] ? $options["viewer_width"] : $properties["width"];
				$height =			$options["viewer_height"] ? $options["viewer_height"] : $properties["height"];
				ob_start();

				if ($options["text_only"]) {
					return "<a href='$url'>".(($options["text_only"]) ? $options["text_only"] : "View QuickTime")."</a>";
				} else {
?>
					<table>
						<tr>
							<td>
								<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
									width="<?php print $width; ?>" height="<?php print $height + 16; ?>"
 									codebase="http://www.apple.com/qtactivex/qtplugin.cab">
									<param name="src" VALUE="<?php print $url; ?>">
									<param name="autoplay" VALUE="true">
									<param name="controller" VALUE="true">

									<embed  src="<?php print $url; ?>"
										name="id_<?php print $name; ?>"
										width="<?php print $width; ?>" height="<?php print $height + 16; ?>"
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
			case 'video/MP2T':
				$id = 				$options["id"] ? $options["id"] : "mp4_player";

				$poster_frame_url =	$options["poster_frame_url"];

				$width =			$options["viewer_width"] ? $options["viewer_width"] : $properties["width"];
				$height =			$options["viewer_height"] ? $options["viewer_height"] : $properties["height"];
				
				$captions = 		caGetOption("captions", $options, array(), array('castTo' => 'array'));
				
				$controls = 		caGetOption("controls", $options, ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'fullscreen'], ['castTo' => 'array']);
				ob_start();
?>
				<video id="<?= $id; ?>" playsinline controls data-poster="<?= $poster_frame_url; ?>" width="<?= $width; ?>" height="<?= $height; ?>" >
				  <source src="<?= $url; ?>" type="video/mp4" />
<?php
						if(is_array($captions)) {
							foreach($captions as $locale_id => $caption_track) {
								print '<track kind="captions" src="'.$caption_track['url'].'" srclang="'.substr($caption_track["locale_code"], 0, 2).'" label="'.$caption_track['locale'].'" default>';	
							}
						}
?>
				</video>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						options = {
							debug: false,
							iconUrl: '<?= __CA_URL_ROOT__; ?>/assets/plyr/plyr.svg',
							controls: [<?= join(',', array_map(function($v) { return "'".addslashes(preg_replace("![\"']+!", '', $v))."'"; }, $controls)); ?>],
						};
						const player = new Plyr('#<?= $id; ?>', options);
						jQuery('#<?= $id; ?>').data('player', player);
						if (caUI.mediaPlayerManager) { caUI.mediaPlayerManager.register("<?= $id; ?>", player, 'Plyr'); }
					});
				</script>
<?php
				return ob_get_clean();
				break;
			# ------------------------------------------------
			case 'video/ogg':
			case 'video/x-matroska':
			case "video/x-ms-asf":
			case "video/x-ms-wmv":
				$id = 						$options["id"] ? $options["id"] : "mp4_player";
				$width =					$options["viewer_width"] ? $options["viewer_width"] : $properties["width"];
				$height =					$options["viewer_height"] ? $options["viewer_height"] : $properties["height"];
				
				return "<video id='{$id}' src='{$url}' width='{$width}' height='{$height}' controls='1'></video>";
				break;
			# ------------------------------------------------
			case 'image/jpeg':
			case 'image/gif':
				if (!is_array($options)) { $options = array(); }
				if (!is_array($properties)) { $properties = array(); }
				return caHTMLImage($url, array_merge($options, $properties));
				break;
			# ------------------------------------------------
		}
		return null;
	}

	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
