<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Media/Audio.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2023 Whirl-i-Gig
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
 * Plugin for processing audio media using ffmpeg
 */
include_once(__CA_LIB_DIR__."/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugMedia.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_APP_DIR__."/helpers/avHelpers.php");
include_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
include_once(__CA_LIB_DIR__."/Parsers/OggParser.php");

class WLPlugMediaAudio Extends BaseMediaPlugin Implements IWLPlugMedia {

	var $errors = [];

	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	var $oproperties;
	var $metadata = [];

	var $input_bitrate;
	var $input_channels;
	var $input_sample_frequency;

	var $opo_config;
	var $ops_path_to_ffmpeg;

	var $ops_mediainfo_path;

	var $info = array(
		"IMPORT" => array(
			"audio/mpeg"						=> "mp3",
			"audio/x-aiff"						=> "aiff",
			"audio/wav"							=> "wav",
			"audio/x-wav"						=> "wav",
			"audio/x-wave"						=> "wav",
			"audio/mp4"							=> "mp4",
			"audio/ogg"							=> "ogg",
			"audio/x-flac"						=> "flac"
		),

		"EXPORT" => array(
			"audio/mpeg"						=> "mp3",
			"audio/x-aiff"						=> "aiff",
			"audio/wav"							=> "wav",
			"audio/x-wav"						=> "wav",
			"audio/x-wave"						=> "wav",
			"audio/mp4"							=> "mp4",
			"video/x-flv"						=> "flv",
			"image/png"							=> "png",
			"image/jpeg"						=> "jpg",
			"audio/ogg"							=> "ogg",
			"audio/x-flac"						=> "flac"
		),

		"TRANSFORMATIONS" => array(
			"SET" 		=> array("property", "value"),
			"SCALE" 	=> array("width", "height", "mode", "antialiasing"),
			"ANNOTATE"	=> array("text", "font", "size", "color", "position", "inset"),	// dummy
			"WATERMARK"	=> array("image", "width", "height", "position", "opacity"),	// dummy
			"INTRO"		=> array("filepath"),
			"OUTRO"		=> array("filepath")
		),

		"PROPERTIES" => array(
			"width"				=> 'W',
			"height"			=> 'W',
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"intro_filepath"	=> 'R',
			"outro_filepath"	=> 'R',
			"mimetype" 			=> 'R',
			"typename"			=> 'R',
			"bandwidth"			=> 'R',
			"title" 			=> 'R',
			"author" 			=> 'R',
			"copyright" 		=> 'R',
			"description" 		=> 'R',
			"duration" 			=> 'R',
			"filesize" 			=> 'R',
			"getID3_tags"		=> 'W',
			'colorspace'		=> 'W',
			"quality"			=> "W",		// required for JPEG compatibility
			"bitrate"			=> 'W', 	// in kbps (ex. 64)
			"channels"			=> 'W',		// 1 or 2, typically
			"sample_frequency"	=> 'W',		// in khz (ex. 44100)
			"version"			=> 'W'		// required of all plug-ins
		),

		"NAME" => "Audio",
		"NO_CONVERSION" => 0
	);

	var $typenames = array(
		"audio/mpeg"						=> "MPEG-3",
		"audio/x-aiff"						=> "AIFF",
		"audio/x-wav"						=> "WAV",
		"audio/x-wave"						=> "WAV",
		"audio/wav"							=> "WAV",
		"audio/mp4"							=> "AAC",
		"image/png"							=> "PNG",
		"image/jpeg"						=> "JPEG",
		"audio/ogg"							=> "Ogg Vorbis",
		"audio/x-flac"						=> "FLAC"
	);
	
	#
	# Alternative extensions for supported types
	#
	var $alternative_extensions = [
		'aif' => 'audio/x-aiff',
		'wave' => "audio/x-wave"
	];


	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides audio processing and conversion using ffmpeg');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$this->ops_path_to_ffmpeg = caMediaPluginFFmpegInstalled();
		$this->ops_mediainfo_path = caMediaInfoInstalled();

		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$status = parent::checkStatus();
		
		$this->register();
		$status['available'] = true;
		if (!$this->ops_path_to_ffmpeg) { 
			$status['errors'][] = _t("Incoming audio files will not be transcoded because ffmpeg is not installed.");
		}
		
		if ($this->ops_mediainfo_path) { 
			$status['notices'][] = _t("MediaInfo will be used to extract metadata from audio files.");
		}
		return $status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		$ID3 = new getID3();
		$info = $ID3->analyze($filepath);
		if ((($info['fileformat'] ?? null) == 'riff') && (!isset($info['video']))) {
			if (isset($info['audio']['dataformat']) && ($info['audio']['dataformat'] == 'wav')) {
				$info['mime_type'] = 'audio/x-wav';
			}
		}
		if ( 
		    (($info['fileformat'] ?? null) == 'quicktime') && 
		    ($info['audio']['codec'] == 'Fraunhofer MPEG Layer-III alias') &&
		    ($info['video']['resolution_x'] == 0) && 
		    ($info['video']['resolution_y'] == 0)
		) {
		    // Quicktime-wrapped MP3
			$info['mime_type'] = 'audio/mpeg';
		}
		if (in_array(strtolower(trim($info["mime_type"] ?? null)), ['audio/wave', 'audio/wav', 'audio/x-wave'], true)) {
			$info["mime_type"] = 'audio/x-wav';
		}
		if (($info["mime_type"] ?? null) && isset($this->info["IMPORT"][$info["mime_type"]]) && $this->info["IMPORT"][$info["mime_type"]]) {
			$this->handle = $this->ohandle = $info;
			$this->metadata = $info;	// populate with getID3 data because it's handy
			return $info["mime_type"];
		} else {
			// is it Ogg?
			$info = new OggParser($filepath);
			if (!$info->LastError && is_array($info->Streams) && (sizeof($info->Streams) > 0)) {
				if (!isset($info->Streams['theora'])) {
					$this->handle = $this->ohandle = $info->Streams;
					return $this->handle['mime_type'] = 'audio/ogg';
				}
			}
			# file format is not supported by this plug-in
			return "";
		}
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property] ?? null) {
				return $this->properties[$property] ?? null;
			} else {
				return null;
			}
		} else {
			return null;
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
							return true;
						} else {
							# read only
							return null;
						}
						break;
				}
			} else {
				# invalid property
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugAudio->set()");
				return null;
			}
		} else {
			return null;
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
	public function read ($filepath, $mimetype="", $options=null) {
		if (!file_exists($filepath)) {
			$this->postError(1650, _t("File %1 does not exist", $filepath), "WLPlugAudio->read()");
			$this->handle = "";
			$this->filepath = "";
			return false;
		}
		if (!(($this->handle) && ($this->handle["filepath"] == $filepath))) {
			$ID3 = new getid3();
			$info = $ID3->analyze($filepath);
			
			if ($info["mime_type"] === 'audio/x-wave') {
				$info["mime_type"] = 'audio/x-wav';
			}
			if (
                ($info['fileformat'] == 'quicktime') && 
                ($info['audio']['codec'] == 'Fraunhofer MPEG Layer-III alias') &&
                ($info['video']['resolution_x'] == 0) && 
                ($info['video']['resolution_y'] == 0)
            ) {
                // Quicktime-wrapped MP3
                $info['mime_type'] = 'audio/mpeg';
            }
			
			$this->handle = $this->ohandle = $info;
			
			if($this->ops_mediainfo_path){
				$this->metadata = caExtractMetadataWithMediaInfo($filepath);
			} else {
				$this->metadata = $this->handle;
			}
			
			if (!$this->handle['mime_type']) {
				// is it Ogg?
				$info = new OggParser($filepath);
				if (!$info->LastError) {
					if (!isset($info->Streams['theora'])) {
						$this->handle = $this->ohandle = $info->Streams;
						$this->handle['mime_type'] = 'audio/ogg';
						$this->handle['playtime_seconds'] = $this->handle['duration'];
					}
				}
			}
		}
		if (!((isset($this->handle["error"])) && (is_array($this->handle["error"])) && (sizeof($this->handle["error"]) > 0))) {
			$this->filepath = $filepath;
			//$this->properties  = $this->handle;
			$this->properties = [];

			$this->properties["mimetype"] = $this->handle["mime_type"];
			$this->properties["typename"] = $this->typenames[$this->properties["mimetype"]] ? $this->typenames[$this->properties["mimetype"]] : "Unknown";

			$this->properties["duration"] = $this->handle["playtime_seconds"];
			$this->properties["filesize"] = filesize($filepath);


			switch($this->properties["mimetype"]) {
				case 'audio/mpeg':

					if (is_array($this->handle["tags"]["id3v1"]["title"] ?? null)) {
						$this->properties["title"] = 		join("; ",$this->handle["tags"]["id3v1"]["title"]);
					}
					if (is_array($this->handle["tags"]["id3v1"]["artist"] ?? null)) {
						$this->properties["author"] = 		join("; ",$this->handle["tags"]["id3v1"]["artist"]);
					}
					if (is_array($this->handle["tags"]["id3v1"]["comment"] ?? null)) {
						$this->properties["copyright"] = 	join("; ",$this->handle["tags"]["id3v1"]["comment"]);
					}
					if (
						(is_array($this->handle["tags"]["id3v1"]["album"] ?? null)) &&
						(is_array($this->handle["tags"]["id3v1"]["year"] ?? null)) &&
						(is_array($this->handle["tags"]["id3v1"]["genre"] ?? null))) {
						$this->properties["description"] = 	join("; ",$this->handle["tags"]["id3v1"]["album"])." ".join("; ",$this->handle["tags"]["id3v1"]["year"])." ".join("; ",$this->handle["tags"]["id3v1"]["genre"]);
					}
					$this->properties["type_specific"] = array("audio" => $this->handle["audio"] ?? null, "tags" => $this->handle["tags"] ?? null);

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = $this->handle["tags"] ?? null;

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"] ?? null;
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"] ?? null;
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"] ?? null;
					$this->properties["duration"] = $this->handle["playtime_seconds"] ?? null;
					break;
				case 'audio/x-aiff':

					$this->properties["type_specific"] = array("audio" => $this->handle["audio"] ?? null, "riff" => $this->handle["riff"] ?? null);

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"] ?? null, "max" => $this->handle["bitrate"] ?? null);

					$this->properties["getID3_tags"] = [];

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"] ?? null;
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"] ?? null;
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"] ?? null;
					$this->properties["duration"] = $this->handle["playtime_seconds"] ?? null;
					break;
				case 'audio/x-flac':
					$this->properties["type_specific"] = [];

					$this->properties["audio"] = $this->handle["audio"] ?? null;
					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"] ?? null, "max" => $this->handle["bitrate"] ?? null);
					
					$this->properties["getID3_tags"] = [];

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"] ?? null;
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"] ?? null;
					$this->properties["sample_frequency"] = $this->handle["audio"]["sample_rate"] ?? null;
					$this->properties["duration"] = $this->handle["playtime_seconds"] ?? null;
					break;
				case 'audio/x-wav':
					$this->properties["type_specific"] = [];

					$this->properties["audio"] = $this->handle["audio"] ?? null;
					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"] ?? null, "max" => $this->handle["bitrate"] ?? null);

					$this->properties["getID3_tags"] = [];

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"] ?? null;
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"] ?? null;
					$this->properties["sample_frequency"] = $this->handle["audio"]["sample_rate"] ?? null;
					$this->properties["duration"] = $this->handle["playtime_seconds"] ?? null;
					break;
				case 'audio/mp4':
					$this->properties["type_specific"] = [];

					$this->properties["audio"] = $this->handle["audio"] ?? null;
					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"] ?? null, "max" => $this->handle["bitrate"] ?? null);

					$this->properties["getID3_tags"] = [];

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"] ?? null;
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"] ?? null;
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"] ?? null;
					$this->properties["duration"] = $this->handle["playtime_seconds"] ?? null;
					break;
				case 'audio/ogg':
					$this->properties["type_specific"] = [];

					$this->properties["audio"] = $this->handle['vorbis'] ?? null;
					$this->properties["bandwidth"] = array("min" => $this->handle['vorbis']['bitrate'] ?? null, "max" => $this->handle['vorbis']['bitrate'] ?? null);

					$this->properties["getID3_tags"] = [];

					$this->properties["bitrate"] = $input_bitrate = $this->handle['vorbis']['bitrate'] ?? null;
					$this->properties["channels"] = $input_channels = $this->handle["vorbis"]["channels"] ?? null;
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["vorbis"]["samplerate"] ?? null;
					$this->properties["duration"] = $this->handle["playtime_seconds"] ?? null;
					break;
			}

			$this->oproperties = $this->properties;

			return 1;
		} else {
			$this->postError(1650, join("; ", $this->handle["error"]), "WLPlugAudio->read()");
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
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugAudio->transform()");
			return false;
		}

		# get parameters for this operation
		$sparams = $this->info["TRANSFORMATIONS"][$operation];

		$this->properties["version_width"] = $w = $parameters["width"] ?? null;
		$this->properties["version_height"] = $h = $parameters["height"] ?? null;
		
		if (!($parameters["width"] ?? null)) {
			$this->properties["version_width"] = $w = $parameters["height"] ?? null;
		}
		if (!($parameters["height"] ?? null)) {
			$this->properties["version_height"] = $h = $parameters["width"] ?? null;
		}
		
		$do_crop = false; 
		
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
					$this->postError(1610, _t("Width or height was zero"), "WLPlugAudio->transform()");
					return false;
				}
				if ($do_crop) {
					$this->properties["width"] = $parameters["width"] ?? null;
					$this->properties["height"] = $parameters["height"] ?? null;
				} else {
					$this->properties["width"] = $w;
					$this->properties["height"] = $h;
				}
				break;
			# -----------------------
			case 'INTRO':
				$this->properties["intro_filepath"] = $parameters["filepath"] ?? null;
				break;
			# -----------------------
			case 'OUTRO':
				$this->properties["outro_filepath"] = $parameters["filepath"] ?? null;
				break;
			# -----------------------
		}
		return 1;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype, $options=null) {
		if (!$this->handle) { return false; }
		if (!($ext = $this->info["EXPORT"][$mimetype])) {
			# this plugin can't write this mimetype
			$this->postError(1610, _t("Can't convert '%1' to '%2': unsupported format", $this->handle["mime_type"] ?? null, $mimetype), "WLPlugAudio->write()");
			return false;
		}

		$o_config = Configuration::load();

		$tags = $this->get("getID3_tags");

		$vs_intro_filepath = $this->get("intro_filepath");
		$vs_outro_filepath = $this->get("outro_filepath");

		if (($vn_output_bitrate = $this->get("bitrate")) < 32000) {
			$vn_output_bitrate = 320000;
		}
		if (($vn_sample_frequency = $this->get("sample_frequency")) < 4096) {
			$vn_sample_frequency = 44100;
		}
		if (($vn_channels = $this->get("channels")) < 1) {
			$vn_channels = 1;
		}
		if (
			($this->properties["mimetype"] == $mimetype)
			&&
			(!(($this->properties["mimetype"] == "audio/mpeg") && ($vs_intro_filepath || $vs_outro_filepath)))
			&&
			(($vn_output_bitrate == $this->input_bitrate) && ($vn_sample_frequency == $this->input_sample_frequency) && ($vn_channels == $this->input_channels))
		) {
			# write the file
			if ( !copy($this->filepath, $filepath.".".$ext) ) {
				$this->postError(1610, _t("Couldn't write file to '%1'", $filepath), "WLPlugAudio->write()");
				return false;
			}
		} else {

			if (($mimetype != "image/png") && ($mimetype != "image/jpeg") && ($this->ops_path_to_ffmpeg)) {
				#
				# Do conversion
				#
				if ($mimetype == 'audio/ogg') {
					caExec($this->ops_path_to_ffmpeg." -f ".$this->info["IMPORT"][$this->properties["mimetype"]]." -i ".caEscapeShellArg($this->filepath)." -acodec libvorbis -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels."  -y ".caEscapeShellArg($filepath.".".$ext).(caIsPOSIX() ? " 2>&1" : ""), $output, $vn_return);
				} else {
					caExec($this->ops_path_to_ffmpeg." -f ".$this->info["IMPORT"][$this->properties["mimetype"]]." -i ".caEscapeShellArg($this->filepath)." -f ".$this->info["EXPORT"][$mimetype]." -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels." -map a -y ".caEscapeShellArg($filepath.".".$ext).(caIsPOSIX() ? " 2>&1" : ""), $output, $vn_return);
				}
				if ($vn_return != 0) {
					@unlink($filepath.".".$ext);
					$this->postError(1610, _t("Error converting file to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $output)), "WLPlugAudio->write()");
					return false;
				}

				if ($mimetype == "audio/mpeg") {
					if ($vs_intro_filepath || $vs_outro_filepath) {
						// add intro
						$vs_tmp_filename = tempnam(caGetTempDirPath(), "audio");
						if ($vs_intro_filepath) {
							caExec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($vs_intro_filepath)." -f mp3 -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels." -y ".caEscapeShellArg($vs_tmp_filename).(caIsPOSIX() ? " 2>&1" : ""), $output, $vn_return);
							if ($vn_return != 0) {
								@unlink($filepath.".".$ext);
								$this->postError(1610, _t("Error converting intro to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $output)), "WLPlugAudio->write()");
								return false;
							}
						}

						$r_fp = fopen($vs_tmp_filename, "a");
						$r_mp3fp = fopen($filepath.".".$ext, "r");
						while (!feof($r_mp3fp)) {
							fwrite($r_fp, fread($r_mp3fp, 8192));
						}
						fclose($r_mp3fp);
						if ($vs_outro_filepath) {
							$vs_tmp_outro_filename = tempnam(caGetTempDirPath(), "audio");
							caExec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($vs_outro_filepath)." -f mp3 -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels." -y ".caEscapeShellArg($vs_tmp_outro_filename).(caIsPOSIX() ? " 2>&1" : ""), $output, $vn_return);
							if ($vn_return != 0) {
								@unlink($filepath.".".$ext);
								$this->postError(1610, _t("Error converting outro to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $output)), "WLPlugAudio->write()");
								return false;
							}
							$r_mp3fp = fopen($vs_tmp_outro_filename, "r");
							while (!feof($r_mp3fp)) {
								fwrite($r_fp, fread($r_mp3fp, 8192));
							}
							unlink($vs_tmp_outro_filename);
						}
						fclose($r_fp);
						copy($vs_tmp_filename, $filepath.".".$ext);
						unlink($vs_tmp_filename);
					}
				
					$o_getid3 = new getid3();
					$mp3_output_info = $o_getid3->analyze($filepath.".".$ext);
					$this->properties = array();
					if (is_array($mp3_output_info["tags"]["id3v1"]["title"] ?? null)) {
						$this->properties["title"] = 		join("; ",$mp3_output_info["tags"]["id3v1"]["title"]);
					}
					if (is_array($mp3_output_info["tags"]["id3v1"]["artist"] ?? null)) {
						$this->properties["author"] = 		join("; ",$mp3_output_info["tags"]["id3v1"]["artist"]);
					}
					if (is_array($mp3_output_info["tags"]["id3v1"]["comment"] ?? null)) {
						$this->properties["copyright"] = 	join("; ",$mp3_output_info["tags"]["id3v1"]["comment"]);
					}
					if (
						(is_array($mp3_output_info["tags"]["id3v1"]["album"] ?? null)) &&
						(is_array($mp3_output_info["tags"]["id3v1"]["year"] ?? null)) &&
						(is_array($mp3_output_info["tags"]["id3v1"]["genre"] ?? null))) {
						$this->properties["description"] = 	join("; ",$mp3_output_info["tags"]["id3v1"]["album"])." ".join("; ",$mp3_output_info["tags"]["id3v1"]["year"])." ".join("; ",$mp3_output_info["tags"]["id3v1"]["genre"]);
					}
					$this->properties["type_specific"] = array("audio" => $mp3_output_info["audio"], "tags" => $mp3_output_info["tags"]);
	
					$this->properties["bandwidth"] = array("min" => $mp3_output_info["bitrate"], "max" => $mp3_output_info["bitrate"]);
	
					$this->properties["bitrate"] = $mp3_output_info["bitrate"] ?? null;
					$this->properties["channels"] = $mp3_output_info["audio"]["channels"] ?? null;
					$this->properties["sample_frequency"] = $mp3_output_info["audio"]["sample_rate"] ?? null;
					$this->properties["duration"] = $mp3_output_info["playtime_seconds"] ?? null;
				}
			} else {
				# use default media icons if ffmpeg is not present or the current version is an image
				if(!$this->get("width") && !$this->get("height")){
					$this->set("width",580);
					$this->set("height",200);
				}
				return __CA_MEDIA_AUDIO_DEFAULT_ICON__;
			}
		}

		if ($mimetype == "audio/mpeg") {
			// try to write getID3 tags (if set)
			if (is_array($options) && is_array($options) && sizeof($options) > 0) {
				$o_tagwriter = new getid3_writetags();
				$o_tagwriter->filename   = $filepath.".".$ext;
				$o_tagwriter->tagformats = array('id3v2.3');
				$o_tagwriter->tag_data = $options;
				// write them tags
				if (!@$o_tagwriter->WriteTags()) {
					// failed to write tags
				}
			}
		}

		$this->properties["mimetype"] = $mimetype;
		$this->properties["typename"] = $this->typenames[$mimetype];

		return $filepath.".".$ext;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $options) {
		return null;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function writeClip($ps_filepath, $ps_start, $ps_end, $options=null) {
		$o_tc = new TimecodeParser();
		
		$vn_start = $vn_end = 0;
		if ($o_tc->parse($ps_start)) { $vn_start = (float)$o_tc->getSeconds(); }
		if ($o_tc->parse($ps_end)) { $vn_end = (float)$o_tc->getSeconds(); }
		
		if ($vn_end == 0) { return null; }
		if ($vn_start >= $vn_end) { return null; }
		$vn_duration = $vn_end - $vn_start;
		
		caExec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f mp3 -t {$vn_duration}  -y -ss {$vn_start} ".caEscapeShellArg($ps_filepath).(caIsPOSIX() ? " 2>&1" : ""), $output, $vn_return);
		if ($vn_return != 0) {
			@unlink($ps_filepath);
			$this->postError(1610, _t("Error extracting clip from %1 to %2: %3", $ps_start, $ps_end, join("; ", $output)), "WLPlugAudio->writeClip()");
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
		return "";
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype] ?? null;
	}
	# ------------------------------------------------
	public function reset() {
		$this->errors = [];
		$this->properties = $this->oproperties;
		return $this->handle = $this->ohandle;
	}
	# ------------------------------------------------
	public function init() {
		$this->errors = [];
		$this->filepath = "";
		$this->handle = "";
		$this->properties = "";
		
		$this->metadata = [];
	}
	# ------------------------------------------------
	public function htmlTag($url, $properties, $options=null, $volume_info=null) {
		if (!is_array($options)) { $options = []; }
		
		foreach(array(
			'name', 'show_controls', 'url', 'text_only', 'viewer_width', 'viewer_height', 'id',
			'data_url', 'poster_frame_url', 'viewer_parameters', 'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style', 'duration', 'pages'
		) as $k) {
			if (!isset($options[$k])) { $options[$k] = null; }
		}
		
		$width 	=	caParseFormElementDimension($options["viewer_width"] ?? ($properties["width"] ?? null), ['returnAs' => 'pixels', 'assumePixels' => true]);
		$height =	caParseFormElementDimension($options["viewer_height"] ?? ($properties["height"] ?? null), ['returnAs' => 'pixels', 'assumePixels' => true]);
		
		$id 	= 	$options["id"] ?? "audio_player";
		$name 	= 	$options["name"] ?? $id;
		$class 	= 	$options["class"] ?? "caAudioPlayer";
		
		switch($properties["mimetype"]) {
			# ------------------------------------------------
			case 'audio/ogg':
				$poster_frame_url =	$options["poster_frame_url"];
				return "<div class='{$class}' style='width: {$width}px; height: {$height}px;'><audio id='{$id}' src='{$url}' width='{$width}' height='{$height}' controls='1'></audio></div>";
				break;
			# ------------------------------------------------
			case 'audio/mpeg':
			case 'audio/mp4':
				$poster_frame_url =	$options["poster_frame_url"] ?? null;
				$captions = 		caGetOption("captions", $options, [], ['castTo' => 'array']);
				$controls = 		caGetOption("controls", $options, ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'fullscreen'], ['castTo' => 'array']);
				ob_start();

				if(caGetOption('user_interface', $options, false, ['forceLowercase' => true]) !== 'mediaelement') {
?>
					<div class="<?= $class; ?> audio-responsive">
						<video id="<?= $id; ?>" playsinline controls data-poster="<?= $poster_frame_url; ?>" width="<?= $width; ?>" height="<?= $height; ?>" >
						  <source src="<?= $url; ?>" type="<?= $properties["mimetype"]; ?>" />
<?php
								if(is_array($captions)) {
									foreach($captions as $locale_id => $caption_track) {
										print '<track kind="captions" src="'.$caption_track['url'].'" srclang="'.substr($caption_track["locale_code"] ?? null, 0, 2).'" label="'.$caption_track['locale'].'" default>';	
									}
								}
?>
						</video>
					</div>
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
				} else {
?>
					<div class="<?= $class; ?>">
						<audio id="<?= $id; ?>" src="<?= $url; ?>" <?= ($poster_url = caGetOption('posterURL', $options, null) ? "poster='{$poster_url}'" : ''); ?> type="<?= $properties["mimetype"]; ?>" controls="controls"></audio>
					</div>	
					<script type="text/javascript">
						jQuery(document).ready(function() {
							var m = jQuery('#<?= $id; ?>').mediaelementplayer({
								showTimecodeFrameCount: true, framesPerSecond: 100, 
								audioWidth: '<?= $width; ?>', audioHeight: '<?= $height; ?>',
								success:  function (mediaElement, domObject) {
									var m = mediaElement; 
									m.addEventListener("play", function(e){ 
										// Force poster image to remain visible during playback
										var $thisMediaElement = (mediaElement.id) ? jQuery("#"+mediaElement.id) : jQuery(mediaElement);
										$thisMediaElement.parents(".mejs-inner").find(".mejs-poster").show();
									});
									m.addEventListener("canplay", function(e){ 
										var $thisMediaElement = (mediaElement.id) ? jQuery("#"+mediaElement.id) : jQuery(mediaElement);
										$thisMediaElement.parents(".mejs-inner").find(".mejs-poster").on('click', function() {
											caUI.mediaPlayerManager.isPlaying("<?= $id; ?>") ? caUI.mediaPlayerManager.stop("<?= $id; ?>") : caUI.mediaPlayerManager.play("<?= $id; ?>");
										});
									});
								}
							});
							if (caUI.mediaPlayerManager) { caUI.mediaPlayerManager.register("<?= $id; ?>", m, 'MediaElement'); }
						});
			</script>
<?php
				}
				return ob_get_clean();
				break;
				# ------------------------------------------------
			case 'audio/x-aiff':
			case 'audio/x-flac':
			case 'audio/x-wav':
				ob_start();
?>
				<div class="<?= $class; ?>" style="width: {$width}px; height: {$height}px;">
					<table>
						<tr>
							<td>
								<embed width="<?= $properties["width"]; ?>" height="<?= $properties["height"] + 16; ?>"
									src="<?= $url; ?>" type="audio/x-wav">
							</td>
						</tr>
					</table>
				</div>
<?php
				return ob_get_clean();
				break;
			# ------------------------------------------------
			case 'image/jpeg':
			case 'image/png':
				if (!is_array($options)) { $options = []; }
				if (!is_array($properties)) { $properties = []; }
				return caHTMLImage($url, array_merge($options, $properties));
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
