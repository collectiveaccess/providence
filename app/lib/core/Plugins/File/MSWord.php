<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/File/MSWord.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2008 Whirl-i-Gig
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
   * Largely obsolete file format plug-in that attempts to detect Microsoft Word files using wvWare and 
   * optionally convert them to HTML or PDF. wvWare is quite old and increasingly difficult to compile as
   * as standalone package. Will replace this with an ABIWord-based version at some point.
   */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugFileFormat.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");

class WLPlugFileMSWord Extends WLPlug Implements IWLPlugFileFormat {
  var $errors = array();
  
  var $_wvware_path;
  
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
						"application/pdf" => array(
												"format_name" => "PDF",
												"long_format_name" => "Portable Document Format (PDF)"
											),
						"text/html" => array(
												"format_name" => "HTML text",
												"long_format_name" => "HTML text"
											),
						"text/plain" => array(
												"format_name" => "ASCII text",
												"long_format_name" => "ASCII (plain) text"
											)
					  ),
		    "NAME" => "MSWord", # name of plug-in
		    );
	
	# ----------------------------------------------------------------------	
	# Methods all plug-ins implement
	# ----------------------------------------------------------------------	
	public function  __construct() {
		$o_config = Configuration::load();
		if (!file_exists($vs_external_app_config_path = $o_config->get('external_applications'))) {
			$this->postError(1660, _t("External application configuration file could not be loaded"), "WLPlugMSWord()");
			return false;
		}
		
		$o_external_app_config = Configuration::load($vs_external_app_config_path);
		if (!file_exists($this->_wvware_path = $o_external_app_config->get('wvware_app'))) {
			$this->postError(1665, _t("Directory for external application wvWare could not be found"), "WLPlugMSWord()");
			return false;
		}
	}
	# ----------------------------------------------------------------------
	# Tell WebLib about this plug-in
	public function  register() {
	
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ----------------------------------------------------------
	public function  get($property) {
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
	public function  getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	#
	# This is where we do format-specific testing
	public function  test($filepath, $original_filename="") {
		if ($this->wvWareInstalled()) {
			
			$vs_output = exec($this->_wvware_path."/wvVersion ".escapeshellarg($filepath));
			if (preg_match("/Version: /", $vs_output) && !preg_match("/maybe/", $vs_output)) {
				$this->properties["filepath"] = $filepath;
				$this->properties["mimetype"] = "application/msword";
				$this->properties["filesize"] = filesize($filepath);
				
				$vs_version = str_replace("Version: ", "",$vs_output);
				$this->properties["version"] = $vs_version;
				$this->properties["format_name"] = "MSWord";
				$this->properties["long_format_name"] = "Microsoft Word";
				$this->properties["dangerous"] = 0;
				return "application/msword";
			} else {
				return false;
			}
		} else {
			return false;
		}
	}	
	# ----------------------------------------------------------------------	
	public function  convert($ps_format, $ps_orig_filepath, $ps_dest_filepath) {
		$this->wvWareInstalled();
		$vs_filepath = $vs_ext = "";
		#
		# First make sure the original file is a Word doc
		#
		if (!$this->test($ps_orig_filepath, "")) {
			return 0;
		}
		
		$va_dest_path_pieces = explode("/", $ps_dest_filepath);
		$vs_dest_filestem = array_pop($va_dest_path_pieces);
		$vs_dest_dir = join("/", $va_dest_path_pieces);

		switch($ps_format) {
			# ------------------------------------
			case 'application/pdf':
				$vs_filepath = $vs_dest_filestem."_conv.pdf";
				$vs_ext = "pdf";
				$vs_output = exec($this->_wvware_path."/wvPDF --targetdir=".escapeshellarg($vs_dest_dir)." ".escapeshellarg($ps_orig_filepath)." ".escapeshellarg($vs_filepath), $va_output, $vn_return_val);
				break;
			# ------------------------------------
			case 'text/html':
				$vs_filepath = $vs_dest_filestem."_conv.html";
				$vs_ext = "html";
				$vs_output = exec($this->_wvware_path."/wvHtml --targetdir=".escapeshellarg($vs_dest_dir)." ".escapeshellarg($ps_orig_filepath)." ".escapeshellarg($vs_filepath), $va_output, $vn_return_val);
				break;
			# ------------------------------------
			case 'text/plain':
				$vs_filepath = $vs_dest_filestem."_conv.txt";
				$vs_ext = "txt";
				$vs_output = exec($this->_wvware_path."/wvText ".escapeshellarg($ps_orig_filepath)." ".escapeshellarg($vs_dest_dir."/".$vs_filepath), $va_output, $vn_return_val);
				break;
			# ------------------------------------
			default:
				return 0;
				break;
			# ------------------------------------
		}
		if ($vn_return_val != 0) {
			return 0;
		}
		return array(
			"extension" => $vs_ext,
			"format_name" => $this->info["CONVERSIONS"][$ps_format]["format_name"],
			"dangerous" => 0,
			"long_format_name" => $this->info["CONVERSIONS"][$ps_format]["long_format_name"]
		);
	}
	# ----------------------------------------------------------------------	
	# Plug-in specific methods
	# ----------------------------------------------------------------------
	private function  wvWareInstalled($ps_command="wvVersion") {
		if (!file_exists($this->_wvware_path."/".$ps_command)) {
			return false;
		}
		return true;
	}
	# ------------------------------------------------
}
?>