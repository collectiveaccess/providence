<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/File/ZFileExtension.php :
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
 * File format plugin of "last resort" that attempts to map the file's extension to a known file type. It
 * will also rename files with executable file extensions to prevent unexpected execution.
 */
 
require_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugFileFormat.php");

class WLPlugFileZFileExtension Extends WLPlug Implements IWLPlugFileFormat {
  var $errors = array();
  
  var $filepath;
  var $handle;
  var $nhandle;
  var $properties;

  var $info = array(
  			# properties from the identified file that *may*
  			# be returned; true (1) if *always* returned; false(0) if not
		    "PROPERTIES" => array(
					  # The following properties are common to all plug-ins
					  "filepath"			=>  1,
					  "filesize"			=>	1,
					  "mimetype" 			=> 	1,
					  "dangerous"			=>	1,
					  "format_name"			=>	1,	# short name of format 
					  "long_format_name"	=>	1 	# long name format 
					  ),
			"CONVERSIONS" => array(
			
					  ),
		    "NAME" => "FileExtension", # name of plug-in
		    );
		    
	# ----------------------------------------------------------------------	
	# Plug-in specific properties
	# ----------------------------------------------------------------------	    
	var $file_extensions = array(
		"txt"	=>	array("mimetype" => "text/plain", "format_name" => "Text", "long_format_name" => "Plain text"),
		"text"	=>	array("mimetype" => "text/plain", "format_name" => "Text", "long_format_name" => "Plain text"),
		"html"	=>	array("mimetype" => "text/html", "format_name" => "HTML", "long_format_name" => "HTML text", "dangerous" => 1),
		"htm"	=>	array("mimetype" => "text/html", "format_name" => "HTML", "long_format_name" => "HTML text", "dangerous" => 1),
		"php"	=>	array("mimetype" => "application/x-php4", "format_name" => "PHP", "long_format_name" => "PHP source file", "dangerous" => 1),
		"php3"	=>	array("mimetype" => "application/x-php4", "format_name" => "PHP", "long_format_name" => "PHP source file", "dangerous" => 1),
		"php4"	=>	array("mimetype" => "application/x-php4", "format_name" => "PHP", "long_format_name" => "PHP source file", "dangerous" => 1),
		"php5"	=>	array("mimetype" => "application/x-php4", "format_name" => "PHP", "long_format_name" => "PHP source file", "dangerous" => 1),
		"phps"	=>	array("mimetype" => "application/x-php4", "format_name" => "PHP", "long_format_name" => "PHP source file", "dangerous" => 1),
		"phtml"	=>	array("mimetype" => "application/x-php4", "format_name" => "PHP", "long_format_name" => "PHP source file", "dangerous" => 1),
		"pl"	=>	array("mimetype" => "text/plain", "format_name" => "Perl", "long_format_name" => "Perl source file", "dangerous" => 1),
		"cgi"	=>	array("mimetype" => "text/plain", "format_name" => "CGI", "long_format_name" => "CGI source file", "dangerous" => 1),
		"py"	=>	array("mimetype" => "text/plain", "format_name" => "Python", "long_format_name" => "Python source file", "dangerous" => 1),
		"rb"	=>	array("mimetype" => "text/plain", "format_name" => "Ruby", "long_format_name" => "Ruby source file", "dangerous" => 1),
		"shtml"	=>	array("mimetype" => "text/plain", "format_name" => "SHTML", "long_format_name" => "Server parsed HTML", "dangerous" => 1),
		"pyc"	=>	array("mimetype" => "text/plain", "format_name" => "Compiled Python", "long_format_name" => "Compiled Python", "dangerous" => 1),
		"jsp"	=>	array("mimetype" => "text/plain", "format_name" => "JSP", "long_format_name" => "Java Server Page", "dangerous" => 1),
		"asp"	=>	array("mimetype" => "text/plain", "format_name" => "ASP", "long_format_name" => "ASP", "dangerous" => 1),
		"exe"	=>	array("mimetype" => "text/plain", "format_name" => "exe", "long_format_name" => "Executable", "dangerous" => 1),
		"dll"	=>	array("mimetype" => "text/plain", "format_name" => "DLL", "long_format_name" => "DLL", "dangerous" => 1),
		"bat"	=>	array("mimetype" => "text/plain", "format_name" => "Batch file", "long_format_name" => "DOS batch file", "dangerous" => 1),
		"com"	=>	array("mimetype" => "text/plain", "format_name" => "COM", "long_format_name" => "COM file", "dangerous" => 1),
		"sh"	=>	array("mimetype" => "text/plain", "format_name" => "Shell", "long_format_name" => "Shell script", "dangerous" => 1),
		
		"jpg"	=>	array("mimetype" => "image/jpeg", "format_name" => "JPEG", "long_format_name" => "JPEG image"),
		"jpeg"	=>	array("mimetype" => "image/jpeg", "format_name" => "JPEG", "long_format_name" => "JPEG image"),
		"jpe"	=>	array("mimetype" => "image/jpeg", "format_name" => "JPEG", "long_format_name" => "JPEG image"),
		"gif"	=>	array("mimetype" => "image/gif", "format_name" => "GIF", "long_format_name" => "GIF image"),
		"png"	=>	array("mimetype" => "image/png", "format_name" => "PNG", "long_format_name" => "PNG image"),
		"tiff"	=>	array("mimetype" => "image/tiff", "format_name" => "TIFF", "long_format_name" => "TIFF image"),
		"tif"	=>	array("mimetype" => "image/tiff", "format_name" => "TIFF", "long_format_name" => "TIFF image"),
		"psd"	=>	array("mimetype" => "image/psd", "format_name" => "PSD", "long_format_name" => "Photoshop image"),
		"swf"	=>	array("mimetype" => "application/x-shockwave-flash", "format_name" => "SWF", "long_format_name" => "SWF (Shockwave Flash)"),
		"pdf"	=>	array("mimetype" => "application/pdf", "format_name" => "PDF", "long_format_name" => "Adobe Portable Document Format (PDF)"),
		"eps"	=>	array("mimetype" => "application/eps", "format_name" => "EPS", "long_format_name" => "Adobe Encapsulated PostScript (EPS)"),
		"ps"	=>	array("mimetype" => "application/ps", "format_name" => "PostScript", "long_format_name" => "Adobe PostScript"),
		"mov"	=>	array("mimetype" => "video/quicktime", "format_name" => "Quicktime", "long_format_name" => "Quicktime"),
		"doc"	=>	array("mimetype" => "application/msword", "format_name" => "Microsoft Word", "long_format_name" => "Microsoft Word"),
		"ppt"	=>	array("mimetype" => "application/vnd.ms-powerpoint", "format_name" => "Microsoft PowerPoint", "long_format_name" => "Microsoft PowerPoint"),
		"xls"	=>	array("mimetype" => "application/vnd.ms-excel", "format_name" => "Microsoft Excel", "long_format_name" => "Microsoft Excel"),
		"rm"	=>	array("mimetype" => "application/x-real-audio", "format_name" => "RealMedia", "long_format_name" => "RealMedia"),
		"wmv"	=>	array("mimetype" => "application/windows-media", "format_name" => "WindowsMedia", "long_format_name" => "WindowsMedia"),
		"tar"	=>	array("mimetype" => "application/tar", "format_name" => "TAR", "long_format_name" => "Unix Tape Archive (TAR)"),
		"tgz"	=>	array("mimetype" => "application/windows-media", "format_name" => "Tar Gzip", "long_format_name" => "Gzipped Unix Tape Archive (TAR/Gzip)"),
		"zip"	=>	array("mimetype" => "application/windows-media", "format_name" => "ZIP", "long_format_name" => "PKZIP archive"),
		"gz"	=>	array("mimetype" => "application/windows-media", "format_name" => "Gzip", "long_format_name" => "GNU Zip"),
		"Z"	=>	array("mimetype" => "application/windows-media", "format_name" => "Unix Compress", "long_format_name" => "Unix Compress"),
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
	public function test($filepath, $original_filepath="") {
		if ($original_filepath) {
			$ext = $this->getExtension($original_filepath);	
		} else {
			$ext = $this->getExtension($filepath);	
		}	
		if ((isset($this->file_extensions[strtolower($ext)])) && ($info = $this->file_extensions[strtolower($ext)])) {
			$this->properties["filepath"] = $filepath;
			$this->properties["mimetype"] = $info["mimetype"];
			$this->properties["filesize"] = filesize($filepath);
			$this->properties["format_name"] = $info["format_name"];
			$this->properties["dangerous"] = $info["dangerous"];
			$this->properties["long_format_name"] = $info["long_format_name"];
			return $info["mimetype"];
		} else {
			return "";
		}
	}
	# ----------------------------------------------------------------------	
	public function convert($ps_format, $ps_orig_filepath, $ps_dest_filepath) {
		return false;
	}
	# ----------------------------------------------------------------------	
	# Plug-in specific methods
	# ----------------------------------------------------------------------	 
	public function getExtension($filename) {
		$bits = explode(".", $filename);
		return array_pop($bits);
	}
	# ------------------------------------------------
}
?>