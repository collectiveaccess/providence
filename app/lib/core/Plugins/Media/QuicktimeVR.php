<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/QuicktimeVR.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
  * Plugin for processing QuicktimeVR files
  */

include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/getid3/getid3.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimecodeParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaQuicktimeVR Extends BaseMediaPlugin Implements IWLPlugMedia {

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

	var $info = array(
		"IMPORT" => array(
			"x-world/x-qtvr" 					=> "mov",
			"video/quicktime" 				=> "mov"
		),

		"EXPORT" => array(
			"x-world/x-qtvr" 					=> "mov",
			"image/jpeg"						=> "jpg"
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
			"title" 			=> 'R',
			"author" 			=> 'R',
			"copyright" 		=> 'R',
			"description" 		=> 'R',
			"duration" 			=> 'R',
			"filesize" 			=> 'R',
			"quality"			=> 'W',
			"version"			=> 'W'		// required of all plug-ins
		),

		"NAME" => "Video",
		"NO_CONVERSION" => 0
	);

	var $typenames = array(
		"x-world/x-qtvr" 					=> "QuickTime",
		"image/jpeg"						=> "JPEG"
	);

	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides services for processing of QuicktimeVR files');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_path_to_ffmpeg = $this->opo_external_app_config->get('ffmpeg_app');

		if (!caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) { return null; }

		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		} else {
			if (!caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) { 
				$va_status['errors'][] = _t("Didn't load because ffmpeg is not installed");
			}
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		$ID3 = new getID3();
		$info = $ID3->analyze($filepath);

		if (($info["mime_type"]) && $this->info["IMPORT"][$info["mime_type"]]) {
				
			if ($info["mime_type"] === 'video/quicktime') {
				if (isset($info['video']['dataformat']) && ($info['video']['dataformat'] == 'quicktimevr')) {
					$info['mime_type'] =  'x-world/x-qtvr';
					$this->handle = $this->ohandle = $info;
					return $info['mime_type'];
				}
			}
		}
		# file format is not supported by this plug-in
		return '';
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
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugQuicktimeVR->set()");
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
			$this->postError(1650, _t("File %1 does not exist", $filepath), "WLPlugQuicktimeVR->read()");
			$this->handle = "";
			$this->filepath = "";
			return false;
		}
		if (!(($this->handle) && ($this->handle["filepath"] == $filepath))) {
			$ID3 = new getID3();
			
			$va_info = $ID3->analyze($filepath);
			$va_info['mime_type'] =  'x-world/x-qtvr';
			$this->handle = $this->ohandle = $va_info;	
		}

		$w = $h = null;
		
		if (!((isset($this->handle["error"])) && (is_array($this->handle["error"])) && (sizeof($this->handle["error"]) > 0))) {
			$this->filepath = $filepath;

			$w = $this->handle["video"]["resolution_x"];
			$h = $this->handle["video"]["resolution_y"];
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
			$this->properties["has_video"] = (isset($this->handle["video"]["bitrate"]) && sizeof($this->handle["video"]["bitrate"]) ? 1 : 0);
			$this->properties["has_audio"] = (isset($this->handle["audio"]["bitrate"]) && sizeof($this->handle["audio"]["bitrate"]) ? 1 : 0);

			$this->properties["type_specific"] = array();

			$this->properties["title"] = 		"";
			$this->properties["author"] = 		"";
			$this->properties["copyright"] = 	"";
			$this->properties["description"] = 	"";

			$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

			$this->oproperties = $this->properties;

			return 1;
		} else {
			$this->postError(1650, join("; ", $this->handle["error"]), "WLPlugQuicktimeVR->read()");
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
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugQuicktimeVR->transform()");
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
					$this->postError(1610, _t("%1: %2 during resize operation", $reason, $description), "WLPlugQuicktimeVR->transform()");
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

				if (caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) {
					if (($vn_start_secs = $this->properties["duration"]/8) > 120) { 
						$vn_start_secs = 120;		// always take a frame from the first two minutes to ensure performance (ffmpeg gets slow if it has to seek far into a movie to extract a frame)
					}
					exec($this->ops_path_to_ffmpeg." -ss ".($vn_start_secs)." -i ".caEscapeShellArg($this->filepath)." -f mjpeg -t 0.001 -y ".caEscapeShellArg($filepath.".".$ext), $va_output, $vn_return);
					if (($vn_return < 0) || ($vn_return > 1) || (!@filesize($filepath.".".$ext))) {
						@unlink($filepath.".".$ext);
						// don't throw error as ffmpeg cannot generate frame still from all files
					} else {
						// resize image to desired dimensions
						$o_media = new Media();
						$o_media->read($filepath.".".$ext);
						$o_media->transform('SCALE', array('width' => $vn_preview_width, 'height' => $vn_preview_height, 'mode' => 'bounding_box', 'antialiasing' => 0.5));
						
						$o_media->write($filepath."_tmp", 'image/jpeg', array());
						if(!$o_media->numErrors()) {
							rename($filepath."_tmp.".$ext, $filepath.".".$ext);
						} else {
							@unlink($filepath."_tmp.".$ext);
						}
					}
				}

				// if output file doesn't exist, ffmpeg failed or isn't installed
				// so use default icons
				if (!file_exists($filepath.".".$ext)) {
					return __CA_MEDIA_VIDEO_DEFAULT_ICON__;
				}
				$this->properties["mimetype"] = $mimetype;
				$this->properties["typename"] = isset($this->typenames[$mimetype]) ? $this->typenames[$mimetype] : $mimetype;

				break;
			# ------------------------------------
			default:
				if (($mimetype != $this->handle["mime_type"])) {
					# this plugin can't write this mimetype (no conversions allowed)
					$this->postError(1610, _t("Can't convert '%1' to %2", $this->handle["mime_type"], $mimetype), "WLPlugQuicktimeVR->write()");
					return false;
				}
				# write the file
				if ( !copy($this->filepath, $filepath.".".$ext) ) {
					$this->postError(1610, _t("Couldn't write file to '%1'", $filepath), "WLPlugQuicktimeVR->write()");
					return false;
				}
				break;
			# ------------------------------------
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
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
		if (!(bool)$this->opo_config->get("video_preview_generate_frames")) { return false; }
		
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
		
		if (($vn_previewed_duration = ($vn_duration - $vn_start_at - $vn_end_at)) < 0) {
			$vn_previewed_duration = $vn_duration;
			$vn_start_at = $vn_end_at = 0;
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
		
		$vs_output_file_prefix = tempnam($vs_tmp_dir, 'caQuicktimeVRPreview');
		$vs_output_file = $vs_output_file_prefix.'%05d.jpg';
		
		exec($this->ops_path_to_ffmpeg." -i ".caEscapeShellArg($this->filepath)." -f image2 -r ".$vs_freq." -ss {$vn_s} -t {$vn_previewed_duration} -s ".$vn_preview_width."x".$vn_preview_height." -y ".caEscapeShellArg($vs_output_file), $va_output, $vn_return);
		$vn_i = 1;
		
		$va_files = array();
		while(file_exists($vs_output_file_prefix.sprintf("%05d", $vn_i).'.jpg')) {
			// add frame to list
			$va_files[''.sprintf("%4.2f", ((($vn_i - 1) * $vn_frame_interval) + $vn_s)).'s'] = $vs_output_file_prefix.sprintf("%05d", $vn_i).'.jpg';
		
			$vn_i++;
		}
		
		if (!sizeof($va_files)) {
			$this->postError(1610, _t("Couldn't not write video preview frames to tmp directory (%1)", $vs_tmp_dir), "WLPlugQuicktimeVR->write()");
		}
		@unlink($vs_output_file_prefix);
		return $va_files;
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
			case 'x-world/x-qtvr':
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
			case 'image/jpeg':
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