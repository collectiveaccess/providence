<?php
/** ---------------------------------------------------------------------
 * app/lib/core/File.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2009 Whirl-i-Gig
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
 
require_once (__CA_LIB_DIR__."/core/Configuration.php");
require_once (__CA_LIB_DIR__."/core/BaseObject.php");

class File extends BaseObject {
	# ----------------------------------------------------------
	# Properties
	# ----------------------------------------------------------
	var $DEBUG = false;
	var $plugins = array();
	var $errors = array();
	var $instance;
	var $error_output = "";
	
	# ----------------------------------------------------------
	# Methods
	# ----------------------------------------------------------
	function __construct() { 
		$o_config = Configuration::load();
		
		# get plugin directory from configuration
		$plugin_dir = $o_config->get("file_plugins");
		
		# read contents of plugin directory
		if($dir = dir($plugin_dir)) {
			while($plugin = $dir->read()) {
				$m = array();
				
				# all plugins must start with a alphabetical character or underscore and
				# end with the '.php' extension
				if (preg_match("/^([A-Za-z_]+).php$/", $plugin, $m)) {
					$plugin_name = $m[1];
				
					if ($this->DEBUG) {	print "LOADING $plugin_name\n"; }
				
					# load the plugin
					require_once("{$plugin_dir}/{$plugin}");
					$plugin_class = "WLPlugFile{$plugin_name}";
					$p = new $plugin_class();
				
					# register the plugin's capabilities
					if ($vo_instance =& $p->register()) {
						$this->plugins[$plugin_name] = $vo_instance;
					}
				}
			}
			return true;
		}
		return null;
	}
	# ----------------------------------------------------------
	function divineFileFormat($filepath, $original_filepath="") {
		$this->instance = "";
		foreach ($this->plugins as $plugin_info) {
			$plugin = $plugin_info["INSTANCE"];
			if ($this->DEBUG) { print "TRYING ".$plugin_info["NAME"]."\n"; }
			if ($mimetype = $plugin->test($filepath, $original_filepath)) {
				$this->instance = $plugin;
				break;
			}
		}
		if ($this->DEBUG) { print "plugin ".$plugin_info["NAME"]." returned $mimetype\n"; }
			
		if ($mimetype) {
			return $mimetype;
		} else {
			$this->postError(1605, _t("File type is not supported"), "File->divineFile()");
			return "";
		}
	}
	# ----------------------------------------------------------
	function get($property) {
		if (!is_object($this->instance)) { return ""; }
		return $this->instance->get($property);
	}
	# ----------------------------------------------------------
	function getProperties() {
		if (!is_object($this->instance)) { return ""; }
		return $this->instance->properties;
	}
	# ----------------------------------------------------------
	function convert($ps_format, $ps_orig_filepath, $ps_dest_filepath) {
		if (!is_object($this->instance)) { return ""; }
		return $this->instance->convert($ps_format, $ps_orig_filepath, $ps_dest_filepath);
	}
	# ----------------------------------------------------------
	function dump() {
		print_r($this->plugins);
	}
	# --------------------------------------------------------------------------------------------
}
?>