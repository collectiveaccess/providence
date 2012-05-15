<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/File/Image.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2011 Whirl-i-Gig
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
 * @subpackage File
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

/**
  * File format plugin that attempts to identify images and use either GD or ImageMagick (via MagickWand)
  * to create thumbnails. Note that this plug-in does not currently support use of ImageMagick without
  * the PHP MagickWand extension being installed (unlike the Media plugins)
  */

require_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugFileFormat.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");

define("LIBRARY_GD", 0);
define("LIBRARY_MAGICKWAND",1);

class WLPlugFileImage Extends WLPlug Implements IWLPlugFileFormat {
  var $errors = array();
  
  var $filepath;
  var $handle;
  var $nhandle;
  var $properties;

  var $info = array(
  			# properties from the identified file that *may*
  			# be returned; true (1) if *always* returned; false(0) if not
		    "PROPERTIES" => array(
					  "version" 			=> 	1,
					  
					  # The following properties are common to all plug-ins
					  "filepath"			=>  1,
					  "filesize"			=>	1,
					  "dangerous"			=>	1,
					  "mimetype" 			=> 	1,
					  "format_name"			=>	1,	# short name of format 
					  "long_format_name"	=>	1 	# long name format 
					  ),
			"CONVERSIONS" => array(
						"image/jpeg" => array(
												"format_name" => "JPEG",
												"long_format_name" => "JPEG preview"
											)
					  ),
		    "NAME" => "Image", # name of plug-in
		    );
	
	# ----------------------------------------------------------------------	
	# Plug-in specific properties
	# ----------------------------------------------------------------------	    
	var $backend;
	
	# ----------------------------------------------------------------------	
	# Methods all plug-ins implement
	# ----------------------------------------------------------------------
	public function __construct() {
	
	}
	# ----------------------------------------------------------------------
	# Tell WebLib about this plug-in
	public function register() {
		if (function_exists('MagickReadImage')) {
			$this->backend = LIBRARY_MAGICKWAND;
		} else {
			if (function_exists('imagecreatefromjpeg')) {
				$this->backend = LIBRARY_GD;
			} else {
				return null;
			}
		}
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->nhandle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				print "Invalid property";
				return "";
			}
		} else {
			return "";
		}
	}
	# ------------------------------------------------
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	#
	# This is where we do format-specific testing
	public function test($filepath, $original_filename="") {
		switch($this->backend) {
			case LIBRARY_GD:
				$va_info = getimagesize($filepath);
				if ($va_info[2] > 0) {
					return image_type_to_mime_type($va_info[2]);
				}
				break;
			default:
				// Try to detect PDF file by header; if we pass a PDF to MagickPingImage() 
				// it can crash Apache (on some Linux boxes, at least)
				if($r_fp = fopen($filepath, "r")) {
					$vs_header = fread($r_fp, 4);
					if ($vs_header == "%PDF") { return false; }
					
					fclose($r_fp);
				} else {
					return false;
				}
				$r_handle = NewMagickWand();
				if (MagickPingImage($r_handle, $filepath)) {
					if ($mimetype = MagickGetImageMimeType($r_handle)) {
						return $mimetype;
					} 
				}
				break;
		}
		return false;
	}	
	# ----------------------------------------------------------------------	
	public function convert($ps_format, $ps_orig_filepath, $ps_dest_filepath) {
		$vs_filepath = $vs_ext = "";
		#
		# First make sure the original file is an image
		#
		if (!$vs_mimetype = $this->test($ps_orig_filepath, "")) {
			return false;
		}
		
		if ($vs_mimetype == "application/pdf") { return false; }
		
		$va_dest_path_pieces = explode("/", $ps_dest_filepath);
		$vs_dest_filestem = array_pop($va_dest_path_pieces);
		$vs_dest_dir = join("/", $va_dest_path_pieces);

		$vn_width = $vn_height = null;
		
		switch($ps_format) {
			# ------------------------------------
			case 'image/jpeg':
				$vs_ext = "jpg";
				$vs_filepath = $vs_dest_filestem."_conv.".$vs_ext;
				switch($this->backend) {
					case LIBRARY_GD:
						if ($vs_mimetype = $this->test($ps_orig_filepath)) {
							switch($vs_mimetype) {
								case "image/jpeg":
									$rsource = imagecreatefromjpeg($ps_orig_filepath);
									break;
								case "image/png":
									$rsource = imagecreatefrompng($ps_orig_filepath);
									break;
								case "image/gif":
									$rsource = imagecreatefromgif($ps_orig_filepath);
									break;
								default:
									return false;
									break;
							}
							
							if (!$r) { return false; }
							
							list($vn_width, $vn_height) = getimagesize($ps_orig_filepath);
							
							if ($vn_width > $vn_height) {
								$vn_ratio = $vn_height/$vn_width;
								$vn_target_width = 800;
								$vn_target_height = 800 * $vn_ratio;
							} else {
								$vn_ratio = $vn_width/$vn_height;
								$vn_target_height = 800;
								$vn_target_width = 800 * $vn_ratio;
							}
							
							if (!($rdest = imagecreatetruecolor($vn_target_width, $vn_target_height))) { return false; }
							if (!imagecopyresampled($rdest, $rsource, 0, 0, 0, 0, $vn_target_width, $vn_target_height, $vn_width, $vn_height)) { return false; }
							if (!imagejpeg($rdest,$vs_dest_dir."/".$vs_filepath)) { return false; }
						} else {
							return false;
						}
						break;
					default:
						$handle = NewMagickWand();
						if (MagickReadImage($handle, $ps_orig_filepath)) {
							if (WandHasException( $handle )) {
								return false;
							}
							$vn_width = MagickGetImageWidth($handle);
							$vn_height = MagickGetImageHeight($handle);
							
							if ($vn_width > $vn_height) {
								$vn_ratio = $vn_height/$vn_width;
								$vn_target_width = 800;
								$vn_target_height = 800 * $vn_ratio;
							} else {
								$vn_ratio = $vn_width/$vn_height;
								$vn_target_height = 800;
								$vn_target_width = 800 * $vn_ratio;
							}
							
							if (!MagickResizeImage( $handle, $vn_target_width, $vn_target_height, MW_CubicFilter, 1)) {
								return false;
							}
							if ( !MagickWriteImage( $handle, $vs_dest_dir."/".$vs_filepath) ) {
								return false;
							}
							
						}
						break;
				}
				break;
			# ------------------------------------
			default:
				return false;
				break;
			# ------------------------------------
		}
		return array(
			"extension" => $vs_ext,
			"format_name" => $this->info["CONVERSIONS"][$ps_format]["format_name"],
			"dangerous" => 0,
			"width" => $vn_width,
			"height" => $vn_height,
			"long_format_name" => $this->info["CONVERSIONS"][$ps_format]["long_format_name"]
		);
	}
	# ----------------------------------------------------------------------	
	# Plug-in specific methods
	# ----------------------------------------------------------------------
	public function useLibrary($vn_backend) {
		switch($vn_backend) {
			case LIBRARY_GD:
				$this->backend = LIBRARY_GD;
				break;
			default:
				$this->backend = LIBRARY_MAGICKWAND;
				break;
		}
	}
	# ------------------------------------------------
}
?>