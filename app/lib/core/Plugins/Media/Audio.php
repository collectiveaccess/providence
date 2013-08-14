<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/Audio.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2013 Whirl-i-Gig
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

include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/getid3/getid3.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
include_once(__CA_LIB_DIR__."/core/Parsers/OggParser.php");

class WLPlugMediaAudio Extends BaseMediaPlugin Implements IWLPlugMedia {

	var $errors = array();

	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	var $oproperties;
	var $metadata = array();

	var $input_bitrate;
	var $input_channels;
	var $input_sample_frequency;

	var $opo_config;
	var $opo_external_app_config;
	var $ops_path_to_ffmpeg;
	var $opb_ffmpeg_available;

	var $ops_mediainfo_path;
	var $opb_mediainfo_available;

	var $info = array(
		"IMPORT" => array(
			"audio/mpeg"						=> "mp3",
			"audio/x-aiff"						=> "aiff",
			"audio/x-wav"						=> "wav",
			"audio/x-wave"						=> "wav",
			"audio/mp4"							=> "aac",
			"audio/ogg"							=> "ogg"
		),

		"EXPORT" => array(
			"audio/mpeg"						=> "mp3",
			"audio/x-aiff"						=> "aiff",
			"audio/x-wav"						=> "wav",
			"audio/mp4"							=> "aac",
			"video/x-flv"						=> "flv",
			"image/png"							=> "png",
			"image/jpeg"						=> "jpg",
			"audio/ogg"							=> "ogg"
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
		"audio/mp4"							=> "AAC",
		"image/png"							=> "PNG",
		"image/jpeg"						=> "JPEG",
		"audio/ogg"							=> "Ogg Vorbis"
	);


	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides audio processing and conversion using ffmpeg');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_path_to_ffmpeg = $this->opo_external_app_config->get('ffmpeg_app');

		$this->ops_mediainfo_path = $this->opo_external_app_config->get('mediainfo_app');
		$this->opb_mediainfo_available = caMediaInfoInstalled($this->ops_mediainfo_path);

		$this->opb_ffmpeg_available = caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg);

		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		$this->register();
		$va_status['available'] = true;
		if (!$this->opb_ffmpeg_available) { 
			$va_status['errors'][] = _t("Incoming Audio files will not be transcoded because ffmpeg is not installed.");
		}
		
		if ($this->opb_mediainfo_available) { 
			$va_status['notices'][] = _t("MediaInfo will be used to extract metadata from video files.");
		}
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		$ID3 = new getid3();
		$info = $ID3->analyze($filepath);
		if (($info['fileformat'] == 'riff') && (!isset($info['video']))) {
			if (isset($info['audio']['dataformat']) && ($info['audio']['dataformat'] == 'wav')) {
				$info['mime_type'] = 'audio/x-wav';
			}
		}
		if (($info["mime_type"]) && isset($this->info["IMPORT"][$info["mime_type"]]) && $this->info["IMPORT"][$info["mime_type"]]) {
			if ($info["mime_type"] === 'audio/x-wave') {
				$info["mime_type"] = 'audio/x-wav';
			}
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
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				print "Invalid property '$property'";
				return "";
			}
		} else {
			return "";
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
							return "";
						}
						break;
				}
			} else {
				# invalid property
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugAudio->set()");
				return "";
			}
		} else {
			return "";
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
			
			$this->handle = $this->ohandle = $info;
			
			if($this->opb_mediainfo_available){
				$this->metadata = caExtractMetadataWithMediaInfo($this->ops_mediainfo_path, $filepath);
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

			$this->properties["mimetype"] = $this->handle["mime_type"];
			$this->properties["typename"] = $this->typenames[$this->properties["mimetype"]] ? $this->typenames[$this->properties["mimetype"]] : "Unknown";

			$this->properties["duration"] = $this->handle["playtime_seconds"];
			$this->properties["filesize"] = filesize($filepath);


			switch($this->properties["mimetype"]) {
				case 'audio/mpeg':

					if (is_array($this->handle["tags"]["id3v1"]["title"])) {
						$this->properties["title"] = 		join("; ",$this->handle["tags"]["id3v1"]["title"]);
					}
					if (is_array($this->handle["tags"]["id3v1"]["artist"])) {
						$this->properties["author"] = 		join("; ",$this->handle["tags"]["id3v1"]["artist"]);
					}
					if (is_array($this->handle["tags"]["id3v1"]["comment"])) {
						$this->properties["copyright"] = 	join("; ",$this->handle["tags"]["id3v1"]["comment"]);
					}
					if (
						(is_array($this->handle["tags"]["id3v1"]["album"])) &&
						(is_array($this->handle["tags"]["id3v1"]["year"])) &&
						(is_array($this->handle["tags"]["id3v1"]["genre"]))) {
						$this->properties["description"] = 	join("; ",$this->handle["tags"]["id3v1"]["album"])." ".join("; ",$this->handle["tags"]["id3v1"]["year"])." ".join("; ",$this->handle["tags"]["id3v1"]["genre"]);
					}
					$this->properties["type_specific"] = array("audio" => $this->handle["audio"], "tags" => $this->handle["tags"]);

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = $this->handle["tags"];

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
				case 'audio/x-aiff':

					$this->properties["type_specific"] = array("audio" => $this->handle["audio"], "riff" => $this->handle["riff"]);

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = array();

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
				case 'audio/x-wav':
					$this->properties["type_specific"] = array();

					$this->properties["audio"] = $this->handle["audio"];
					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = array();

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
				case 'audio/mp4':
					$this->properties["type_specific"] = array();

					$this->properties["audio"] = $this->handle["audio"];
					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = array();

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
				case 'audio/ogg':
					$this->properties["type_specific"] = array();

					$this->properties["audio"] = $this->handle['vorbis'];
					$this->properties["bandwidth"] = array("min" => $this->handle['vorbis']['bitrate'], "max" => $this->handle['vorbis']['bitrate']);

					$this->properties["getID3_tags"] = array();

					$this->properties["bitrate"] = $input_bitrate = $this->handle['vorbis']['bitrate'];
					$this->properties["channels"] = $input_channels = $this->handle["vorbis"]["channels"];
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["vorbis"]["samplerate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
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

		$this->properties["version_width"] = $w = $parameters["width"];
		$this->properties["version_height"] = $h = $parameters["height"];
		
		if (!$parameters["width"]) {
			$this->properties["version_width"] = $w = $parameters["height"];
		}
		if (!$parameters["height"]) {
			$this->properties["version_height"] = $h = $parameters["width"];
		}
		
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
					$this->postError(1610, _t("%1: %2 during resize operation", $reason, $description), "WLPlugAudio->transform()");
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
			case 'INTRO':
				$this->properties["intro_filepath"] = $parameters["filepath"];
				break;
			# -----------------------
			case 'OUTRO':
				$this->properties["outro_filepath"] = $parameters["filepath"];
				break;
			# -----------------------
		}
		return 1;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype) {
		if (!$this->handle) { return false; }
		if (!($ext = $this->info["EXPORT"][$mimetype])) {
			# this plugin can't write this mimetype
			$this->postError(1610, _t("Can't convert '%1' to '%2': unsupported format", $this->handle["mime_type"], $mimetype), "WLPlugAudio->write()");
			return false;
		}

		$o_config = Configuration::load();

		$va_tags = $this->get("getID3_tags");

		$vs_intro_filepath = $this->get("intro_filepath");
		$vs_outro_filepath = $this->get("outro_filepath");

		if (($vn_output_bitrate = $this->get("bitrate"))< 32) {
			$vn_output_bitrate = 64;
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

			if (($mimetype != "image/png") && ($mimetype != "image/jpeg") && ($this->opb_ffmpeg_available)) {
				#
				# Do conversion
				#
				if ($mimetype == 'audio/ogg') {
					exec($this->ops_path_to_ffmpeg." -f ".$this->info["IMPORT"][$this->properties["mimetype"]]." -i ".caEscapeShellArg($this->filepath)." -acodec libvorbis -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels."  -y ".caEscapeShellArg($filepath.".".$ext)." 2>&1", $va_output, $vn_return);
				} else {
					exec($this->ops_path_to_ffmpeg." -f ".$this->info["IMPORT"][$this->properties["mimetype"]]." -i ".caEscapeShellArg($this->filepath)." -f ".$this->info["EXPORT"][$mimetype]." -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels."  -y ".caEscapeShellArg($filepath.".".$ext)." 2>&1", $va_output, $vn_return);
				}
				if ($vn_return != 0) {
					@unlink($filepath.".".$ext);
					$this->postError(1610, _t("Error converting file to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $va_output)), "WLPlugAudio->write()");
					return false;
				}

				if ($mimetype == "audio/mpeg") {
					if ($vs_intro_filepath || $vs_outro_filepath) {
						// add intro
						$vs_tmp_filename = tempnam(caGetTempDirPath(), "audio");
						if ($vs_intro_filepath) {
							exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($vs_intro_filepath)." -f mp3 -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels." -y ".caEscapeShellArg($vs_tmp_filename), $va_output, $vn_return);
							if ($vn_return != 0) {
								@unlink($filepath.".".$ext);
								$this->postError(1610, _t("Error converting intro to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $va_output)), "WLPlugAudio->write()");
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
							exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($vs_outro_filepath)." -f mp3 -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels." -y ".caEscapeShellArg($vs_tmp_outro_filename), $va_output, $vn_return);
							if ($vn_return != 0) {
								@unlink($filepath.".".$ext);
								$this->postError(1610, _t("Error converting outro to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $va_output)), "WLPlugAudio->write()");
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
					$va_mp3_output_info = $o_getid3->analyze($filepath.".".$ext);
					$this->properties = array();
					if (is_array($va_mp3_output_info["tags"]["id3v1"]["title"])) {
						$this->properties["title"] = 		join("; ",$va_mp3_output_info["tags"]["id3v1"]["title"]);
					}
					if (is_array($va_mp3_output_info["tags"]["id3v1"]["artist"])) {
						$this->properties["author"] = 		join("; ",$va_mp3_output_info["tags"]["id3v1"]["artist"]);
					}
					if (is_array($va_mp3_output_info["tags"]["id3v1"]["comment"])) {
						$this->properties["copyright"] = 	join("; ",$va_mp3_output_info["tags"]["id3v1"]["comment"]);
					}
					if (
						(is_array($va_mp3_output_info["tags"]["id3v1"]["album"])) &&
						(is_array($va_mp3_output_info["tags"]["id3v1"]["year"])) &&
						(is_array($va_mp3_output_info["tags"]["id3v1"]["genre"]))) {
						$this->properties["description"] = 	join("; ",$va_mp3_output_info["tags"]["id3v1"]["album"])." ".join("; ",$va_mp3_output_info["tags"]["id3v1"]["year"])." ".join("; ",$va_mp3_output_info["tags"]["id3v1"]["genre"]);
					}
					$this->properties["type_specific"] = array("audio" => $va_mp3_output_info["audio"], "tags" => $va_mp3_output_info["tags"]);
	
					$this->properties["bandwidth"] = array("min" => $va_mp3_output_info["bitrate"], "max" => $va_mp3_output_info["bitrate"]);
	
					$this->properties["bitrate"] = $va_mp3_output_info["bitrate"];
					$this->properties["channels"] = $va_mp3_output_info["audio"]["channels"];
					$this->properties["sample_frequency"] = $va_mp3_output_info["audio"]["sample_rate"];
					$this->properties["duration"] = $va_mp3_output_info["playtime_seconds"];
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
			if (is_array($pa_options) && is_array($pa_options) && sizeof($pa_options) > 0) {
				require_once('parsers/getid3/getid3.php');
				require_once('parsers/getid3/write.php');
				$o_getID3 = new getID3();
				$o_tagwriter = new getid3_writetags();
				$o_tagwriter->filename   = $filepath.".".$ext;
				$o_tagwriter->tagformats = array('id3v2.3');
				$o_tagwriter->tag_data = $pa_options;

				// write them tags
				if (!$o_tagwriter->WriteTags()) {
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
	public function &writePreviews($ps_filepath, $pa_options) {
		return null;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function writeClip($ps_filepath, $ps_start, $ps_end, $pa_options=null) {
		$o_tc = new TimecodeParser();
		
		$vn_start = $vn_end = 0;
		if ($o_tc->parse($ps_start)) { $vn_start = (float)$o_tc->getSeconds(); }
		if ($o_tc->parse($ps_end)) { $vn_end = (float)$o_tc->getSeconds(); }
		
		if ($vn_end == 0) { return null; }
		if ($vn_start >= $vn_end) { return null; }
		$vn_duration = $vn_end - $vn_start;
		
		exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f mp3 -t {$vn_duration}  -y -ss {$vn_start} ".caEscapeShellArg($ps_filepath), $va_output, $vn_return);
		if ($vn_return != 0) {
			@unlink($filepath.".".$ext);
			$this->postError(1610, _t("Error extracting clip from %1 to %2: %3", $ps_start, $ps_end, join("; ", $va_output)), "WLPlugAudio->writeClip()");
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
			'data_url', 'poster_frame_url', 'viewer_parameters', 'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style', 'duration', 'pages'
		) as $vs_k) {
			if (!isset($pa_options[$vs_k])) { $pa_options[$vs_k] = null; }
		}
		
		switch($pa_properties["mimetype"]) {
		# ------------------------------------------------
			case 'audio/ogg':
				
				$vs_id = 							$pa_options["id"] ? $pa_options["id"] : "mp4_player";
				$vs_poster_frame_url =	$pa_options["poster_frame_url"];
				$vn_width =						$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =					$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				if (!$vn_width) { $vn_width = 300; }
				if (!$vn_height) { $vn_height = 32; }
				return "<div style='width: {$vn_width}px; height: {$vn_height}px;'><audio id='{$vs_id}' src='{$ps_url}' width='{$vn_width}' height='{$vn_height}' controls='1'></audio></div>";
				break;
			# ------------------------------------------------
			case 'audio/mpeg':
				$viewer_base_url 	= $pa_options["viewer_base_url"];
				$vs_id 				= $pa_options["id"] ? $pa_options["id"] : "mp3player";

				switch($pa_options["player"]) {
					case 'small':
						JavascriptLoadManager::register("swfobject");
						ob_start();
						$vn_width = ($pa_options["viewer_width"] > 0) ? $pa_options["viewer_width"] : 165;
						$vn_height = ($pa_options["viewer_height"] > 0) ? $pa_options["viewer_height"] : 38;
?>
						<div style='width: {$vn_width}px; height: {$vn_height}px;'>
							<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="<?php print $vn_viewer_width; ?>" height="<?php print $vn_viewer_height; ?>" id="<?php print $vs_id; ?>" align="">
								<param name="movie" value="<?php print $viewer_base_url; ?>/viewers/apps/niftyplayer.swf?file=<?php print $ps_url; ?>&as=0">
								<param name="quality" value="high">
								<param name="bgcolor" value="#FFFFFF">
								<embed src="<?php print $viewer_base_url; ?>/viewers/apps/niftyplayer.swf?file=<?php print $ps_url; ?>&as=0" quality="high" bgcolor="#FFFFFF" width="<?php print $vn_viewer_width; ?>" height="<?php print $vn_viewer_height; ?>" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
								</embed>
							</object>
						</div>
<?php
						return ob_get_clean();
						break;
					case 'text':
						return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to MP3")."</a>";
						break;
					default:
						JavascriptLoadManager::register("mediaelement");
						
						$vn_width = ($pa_options["viewer_width"] > 0) ? $pa_options["viewer_width"] : 400;
						$vn_height = ($pa_options["viewer_height"] > 0) ? $pa_options["viewer_height"] : 95;
						ob_start();
?>
					<div class="<?php print (isset($pa_options["class"])) ? $pa_options["class"] : "caAudioPlayer"; ?>">
						<audio id="<?php print $vs_id; ?>" src="<?php print $ps_url; ?>" type="audio/mp3" controls="controls"></audio>
					</div>	
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery('#<?php print $vs_id; ?>').mediaelementplayer({showTimecodeFrameCount: true, framesPerSecond: 100, audioWidth: <?php print (int)$vn_width; ?>, audioHeight: <?php print (int)$vn_height; ?>  });
						});
					</script>
<?php
						return ob_get_clean();
						break;
				}
				break;
				# ------------------------------------------------
			case 'audio/mp4':
				$name = $pa_options["name"] ? $pa_options["name"] : "mp3player";

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to AAC")."</a>";
				} else {
					ob_start();
					
					$vn_width = ($pa_options["viewer_width"] > 0) ? $pa_options["viewer_width"] : 400;
					$vn_height = ($pa_options["viewer_height"] > 0) ? $pa_options["viewer_height"] : 95;
?>
					<div style="width: {$vn_width}px; height: {$vn_height}px;">
						<table border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td>
									<embed width="<?php print $vn_width; ?>" height="<?php print $vn_height + 16; ?>"
										src="<?php print $ps_url; ?>" type="audio/mp4">
								</td>
							</tr>
						</table>
					</div>
<?php
					return ob_get_clean();
				}
				break;
				# ------------------------------------------------
			case 'audio/x-wav':
				$name = $pa_options["name"] ? $pa_options["name"] : "mp3player";

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to WAV")."</a>";
				} else {
					ob_start();
					
					$vn_width = ($pa_options["viewer_width"] > 0) ? $pa_options["viewer_width"] : 400;
					$vn_height = ($pa_options["viewer_height"] > 0) ? $pa_options["viewer_height"] : 95;
?>
					<div style="width: {$vn_width}px; height: {$vn_height}px;">
						<table border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td>
									<embed width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"] + 16; ?>"
										src="<?php print $ps_url; ?>" type="audio/x-wav">
								</td>
							</tr>
						</table>
					</div>
<?php
					return ob_get_clean();
				}
				break;
				# ------------------------------------------------
			case 'audio/x-aiff':
				$name = $pa_options["name"] ? $pa_options["name"] : "mp3player";

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to AIFF")."</a>";
				} else {
					ob_start();
					
					$vn_width = ($pa_options["viewer_width"] > 0) ? $pa_options["viewer_width"] : 400;
					$vn_height = ($pa_options["viewer_height"] > 0) ? $pa_options["viewer_height"] : 95;
?>
					<div style="width: {$vn_width}px; height: {$vn_height}px;">
						<table border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td>
									<embed width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"] + 16; ?>"
										src="<?php print $ps_url; ?>" type="audio/x-aiff">
								</td>
							</tr>
						</table>
					</div>
<?php
					return ob_get_clean();
				}
				break;
			# ------------------------------------------------
			case "video/x-flv":
				$vs_name = 				$pa_options["name"] ? $pa_options["name"] : "flv_player";
				$vs_id = 				$pa_options["id"] ? $pa_options["id"] : "flv_player";

				$vs_flash_vars = 		$pa_options["viewer_parameters"];
				$viewer_base_url =		$pa_options["viewer_base_url"];

				$vn_width = ($pa_options["viewer_width"] > 0) ? $pa_options["viewer_width"] : 400;
				$vn_height = ($pa_options["viewer_height"] > 0) ? $pa_options["viewer_height"] : 95;
				
				$vs_data_url =			$pa_options["data_url"];
				$vs_poster_frame_url =	$pa_options["poster_frame_url"];
				
				ob_start();
?>

			<div id="<?php print $vs_id; ?>" style="width: {$vn_width}px; height: {$vn_height}px;">
				<h1><?php print _t('You must have the Flash Plug-in version 9.0.124 or better installed to play video and audio in CollectiveAccess'); ?></h1>
				<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function() { swfobject.embedSWF("<?php print $viewer_base_url; ?>/viewers/apps/niftyplayer.swf ", "<?php print $vs_id; ?>", "<?php print $vn_width; ?>", "<?php print $vn_height; ?>", "9.0.124", "swf/expressInstall.swf", {'source' : '<?php print $ps_url; ?>', 'dataUrl':'<?php print $vs_data_url; ?>', 'posterFrameUrl': '<?php print $vs_poster_frame_url; ?>'}, {'allowscriptaccess': 'always', 'allowfullscreen' : 'true', 'allowNetworking' : 'all'}); });
			</script>
<?php
				return ob_get_clean();
				break;
				# ------------------------------------------------
			case 'image/jpeg':
			case 'image/png':
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
