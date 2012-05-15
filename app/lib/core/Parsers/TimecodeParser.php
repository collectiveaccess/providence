<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/TimecodeParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
/**
 * Class to convert timecode notations. Supported notations are
 *
 *	- Simple seconds: 	ex. "8410" 			= 8410 seconds (2 hours, 20 minutes and 10 seconds)
 *	- hms notation: 	ex. 2h 20m 10s 		= 8410 seconds (2 hours, 20 minutes and 10 seconds)
 *	- colon nation: 	ex. 2:20:10 		= 8410 seconds (2 hours, 20 minutes and 10 seconds)
 *
 * You can also specify the number of frames (for video and film time code). The timebase 
 * determines how frames notation is converted to seconds. The default timebase is 29.97 frames per 
 * second, the NTSC (North American TV) timebase. This class is used by the Table class to support
 * FT_TIMECODE fields.
 *
 */
require_once(__CA_LIB_DIR__."/core/Error.php");


class TimecodeParser {
	var $opn_parsed_value_in_seconds;
	var $opn_timebase = 29.97; // NTSC fps
	# ------------------------------------------------------------------
	function TimecodeParser($ps_timecode="") {
		if ($ps_timecode) { $this->parse($ps_timecode); }
	}
	# ------------------------------------------------------------------
	function parse($ps_timecode) {
		if (preg_match("/^([\d]+[\.]{0,1}[\d]*)[s]{0,1}$/", $ps_timecode, $va_matches)) {
			// simple seconds
			return $this->opn_parsed_value_in_seconds = floatval($va_matches[1]);
		} else {
			if (preg_match("/[\d]+[hmsf]{1}/", $ps_timecode)) {
				// hms format
				$vs_timecode = preg_replace("/([hmsf].)/", "\$1 ",$ps_timecode);
				$vs_timecode = preg_replace("/[ ]+/", " ",$vs_timecode);
				$va_pieces = explode(" ", $vs_timecode);
				$vn_hours = $vn_minutes = $vn_seconds = $vn_frames = 0;
				
				foreach($va_pieces as $vs_piece) {
					$vs_piece = trim($vs_piece);
					
					if (preg_match("/^([\d]+[\.]{0,1}[\d]*)([hmsf]{1})$/", $vs_piece, $va_matches)) {
						switch($va_matches[2]) {
							case 'h':
								$vn_hours = intval($va_matches[1]);
								break;
							case 'm':
								$vn_minutes = intval($va_matches[1]);
								break;
							case 's':
								$vn_seconds = floatval($va_matches[1]);
								break;
							case 'f':
								$vn_frames = intval($va_matches[1]);
								break;
							default:
								// invalid token
								return false;
								break;
						}						
					} else {
						// invalid token
						return false;
					}
				}
				return $this->opn_parsed_value_in_seconds = ($vn_hours * 3600) + ($vn_minutes * 60) + ($vn_seconds) + ($vn_frames/$this->opn_timebase);
			} else {
				if (preg_match("/[\d]+:[\d]/", $ps_timecode)) {
					// colon format
					$va_pieces = explode(":", $ps_timecode);
					
					switch(sizeof($va_pieces)) {
						case 4:
							// with frames
							$vn_hours = intval($va_pieces[0]);
							$vn_minutes = intval($va_pieces[1]);
							$vn_seconds = intval($va_pieces[2]);
							$vn_frames = intval($va_pieces[3]);
							break;
						case 3:
							// hms
							$vn_hours = intval($va_pieces[0]);
							$vn_minutes = intval($va_pieces[1]);
							$vn_seconds = floatval($va_pieces[2]);
							$vn_frames = 0;
							break;
						case 2:
							//ms
							$vn_hours = 0;
							$vn_minutes = intval($va_pieces[0]);
							$vn_seconds = floatval($va_pieces[1]);
							$vn_frames = 0;
							break;
						default:
							return false;
							break;
					}
					
					return $this->opn_parsed_value_in_seconds = ($vn_hours * 3600) + ($vn_minutes * 60) + ($vn_seconds) + ($vn_frames/$this->opn_timebase);
				} else {
					// unrecognized format
					return false;
				}
			}
		}
	}
	# ------------------------------------------------------------------
	function getSeconds() {
		return $this->opn_parsed_value_in_seconds;
	}
	# ------------------------------------------------------------------
	function setTimebase($pn_timebase) {
		if ($pn_timebase > 0) {
			$this->opn_timebase = $pn_timebase;
			return true;
		} else {
			return false;
		}
	}
	# ------------------------------------------------------------------
	function getTimebase() {
		return $this->opn_timebase;
	}
	# ------------------------------------------------------------------
	function setParsedValueInSeconds($pn_seconds) {
		if ($pn_seconds >= 0) {
			$this->opn_parsed_value_in_seconds = $pn_seconds;
			return true;
		} else {
			return false;
		}
	}
	# ------------------------------------------------------------------
	function getParsedValueInSeconds() {
		return $this->opn_parsed_value_in_seconds;
	}
	# ------------------------------------------------------------------
	function getText($ps_format="RAW", $pa_options=null) {
		switch($ps_format) {
			case 'COLON_DELIMITED':
			case 'HOURS_MINUTES_SECONDS':
				$vn_time_in_seconds = $this->opn_parsed_value_in_seconds;
				
				if (!$vn_time_in_seconds && is_array($pa_options) && (isset($pa_options["BLANK_ON_ZERO"])) && ($pa_options["BLANK_ON_ZERO"])) {
					return "";
				} else {
					$vn_hours = intval($vn_time_in_seconds/3600);
					$vn_time_in_seconds -= ($vn_hours * 3600);
					
					$vn_minutes = intval($vn_time_in_seconds/60);
					$vn_time_in_seconds -= ($vn_minutes * 60);
					
					$vn_seconds = $vn_time_in_seconds;
					
					if ($ps_format == "COLON_DELIMITED") {
						if ((float)$vn_seconds != intval($vn_seconds)) {
							if ($pa_options["NO_FRACTIONAL_SECONDS"]) {
								$vs_seconds = sprintf("%02.0f", round($vn_seconds));
							} else {
								$vs_seconds = sprintf("%04.1f", $vn_seconds);
							}
						} else {
							$vs_seconds = sprintf("%02.0f", $vn_seconds);
						}
						return $vn_hours.":".sprintf("%02d", $vn_minutes).":".$vs_seconds;
					} else {
						return $vn_hours."h ".$vn_minutes."m ".$vn_seconds."s";
					}
				}
				break;
			case 'RAW':
				return $this->opn_parsed_value_in_seconds;
				break;
			default:
				return floatval($this->opn_parsed_value_in_seconds)."s";
				break;
		}
	}
	# ------------------------------------------------------------------
}
# ----------------------------------------------------------------------
?>