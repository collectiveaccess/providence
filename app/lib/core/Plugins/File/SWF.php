<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/File/SWF.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2008 Whirl-i-Gig
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
 *
 * File format plugin that attempts to detect SWF (Flash) files
 */
 
require_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugFileFormat.php");

class WLPlugFileSWF Extends WLPlug Implements IWLPlugFileFormat {
  var $errors = array();
  
  var $filepath;
  var $handle;
  var $nhandle;
  var $properties;

  var $info = array(
  			# properties from the identified file that *may*
  			# be returned; true (1) if *always* returned; false(0) if not
		    "PROPERTIES" => array(
					  "width" 				=> 	0, # we don't get the optional ones yet
					  "height" 				=> 	0, # because we haven't written the bit-level
					  "framerate" 			=> 	0, # read functions the would be required to do so
					  "version" 			=> 	1,
					  "compressed"			=>	1,
					  
					  # The following properties are common to all plug-ins
					  "filepath"			=>  1,
					  "dangerous"			=>	1,
					  "filesize"			=>	1,
					  "mimetype" 			=> 	1,
					  "format_name"			=>	1,	# short name of format 
					  "long_format_name"	=>	1 	# long name format 
					  ),
			"CONVERSIONS" => array(
			
					  ),
		    "NAME" => "SWF", # name of plug-in
		    );
	# ----------------------------------------------------------------------	
	# Methods all plug-ins implement
	# ----------------------------------------------------------------------
	public function __construct() {
	
	}
	# ----------------------------------------------------------------------
	# Tell WebLib about this plug-in
	public function register() {
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
		if ($fp = @fopen($filepath, "r")) {
			$sig = fgets($fp, 4);
			if (($sig === 'FWS') || ($sig === 'CWS')) {
				$this->properties["filepath"] = $filepath;
				$this->properties["mimetype"] = "application/x-shockwave-flash";
				$this->properties["filesize"] = filesize($filepath);
				$this->properties["compressed"] = ($sig === 'CWS') ? 1 : 0;
				$this->properties["version"] = ord(fgets($fp, 2));
				$this->properties["format_name"] = "SWF";
				$this->properties["long_format_name"] = "SWF (Shockwave Flash)";
				$this->properties["dangerous"] = 0;
				return "application/x-shockwave-flash";
			}
		} else {
			# error: file couldn't be opened
			return false;
		}	
	}	
	# ----------------------------------------------------------------------	
	public function convert($ps_format, $ps_orig_filepath, $ps_dest_filepath) {
		return false;
	}
	# ------------------------------------------------
}
?>