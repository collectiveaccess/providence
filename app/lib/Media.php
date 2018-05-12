<?php
/** ---------------------------------------------------------------------
 * app/lib/Media.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2016 Whirl-i-Gig
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
 
require_once (__CA_LIB_DIR__."/Configuration.php");
require_once (__CA_LIB_DIR__."/BaseObject.php");

define("__CA_MEDIA_VIDEO_DEFAULT_ICON__", 'video');
define("__CA_MEDIA_AUDIO_DEFAULT_ICON__", 'audio');
define("__CA_MEDIA_DOCUMENT_DEFAULT_ICON__", 'document');
define("__CA_MEDIA_3D_DEFAULT_ICON__", '3d');
define("__CA_MEDIA_SPIN_DEFAULT_ICON__", '3d');
define("__CA_MEDIA_BINARY_FILE_DEFAULT_ICON__", 'document');

class Media extends BaseObject {
	# ----------------------------------------------------------
	# Properties
	# ----------------------------------------------------------
	public $DEBUG = false;
	private $plugins = array();
	private $instance;
	
	# --- 
	# Cache loaded plug-ins
	# ---
	static $WLMedia_plugin_cache = array();
	static $WLMedia_unregistered_plugin_cache = array();
	static $WLMedia_plugin_names = null;
	
	static $plugin_path = null;
	
	static $s_file_extension_to_plugin_map = null; 
	
	static $s_divine_cache = [];
	
	# ----------------------------------------------------------
	# Methods
	# ----------------------------------------------------------
	public function __construct($pb_no_cache=false) { 
		if (!Media::$plugin_path) { Media::$plugin_path = __CA_LIB_DIR__.'/Plugins/Media'; }
		
		if (!(Media::$s_file_extension_to_plugin_map = CompositeCache::contains('media_file_extension_to_plugin_map'))) {
			CompositeCache::save('media_file_extension_to_plugin_map', $this->getPluginImportFileExtensionMap());
		}
		Media::$s_file_extension_to_plugin_map = CompositeCache::fetch('media_file_extension_to_plugin_map');
	}
	# ----------------------------------------------------------
	public function getPluginNames() {
		if (is_array(Media::$WLMedia_plugin_names)) { return Media::$WLMedia_plugin_names; }
		
		Media::$WLMedia_plugin_names = array();
		$dir = opendir(Media::$plugin_path);
		if (!$dir) { throw new ApplicationException(_t('Cannot open media plugin directory %1', Media::$plugin_path)); }
	
		$vb_binary_file_plugin_installed = false;
		while (($plugin = readdir($dir)) !== false) {
			if ($plugin == "BaseMediaPlugin.php") { continue; }
			if ($plugin == "BinaryFile.php") { $vb_binary_file_plugin_installed = true; continue; }
			if (preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*).php$/", $plugin, $m)) {
				Media::$WLMedia_plugin_names[] = $m[1];
			}
		}
		
		sort(Media::$WLMedia_plugin_names);
		
		if ($vb_binary_file_plugin_installed) { Media::$WLMedia_plugin_names[] = "BinaryFile"; }
		
		return Media::$WLMedia_plugin_names;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getPlugin($ps_plugin_name) {
		if (!($p = $this->getUnregisteredPlugin($ps_plugin_name))) { return null; }
		
		# register the plugin's capabilities
		if ($vo_instance = $p->register()) {
			if ($this->DEBUG) {	print "[DEBUG] LOADED $ps_plugin_name<br>\n"; }
			return Media::$WLMedia_plugin_cache[$ps_plugin_name] = $vo_instance;
		} else {
			if ($this->DEBUG) {	print "[DEBUG] DID NOT LOAD $ps_plugin_name<br>\n"; }
			Media::$WLMedia_plugin_cache[$ps_plugin_name] = false;
			return null;
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getUnregisteredPlugin($ps_plugin_name) {
		if(!in_array($ps_plugin_name, $this->getPluginNames())) { return null; }
		
		$plugin_dir = Media::$plugin_path;
		
		# load the plugin
		if (!class_exists("WLPlugMedia{$ps_plugin_name}")) { 
			require_once("{$plugin_dir}/{$ps_plugin_name}.php"); 
		}
		$ps_plugin_class = "WLPlugMedia{$ps_plugin_name}";
		$p = new $ps_plugin_class();
		
		Media::$WLMedia_unregistered_plugin_cache[$ps_plugin_name] = $p;
		
		return $p;
	}
	# ----------------------------------------------------------
	public function checkPluginStatus($ps_plugin_name) {
		
		if(!in_array($ps_plugin_name, $this->getPluginNames())) { return null; }
		if (isset(Media::$WLMedia_plugin_cache[$ps_plugin_name])) { return Media::$WLMedia_plugin_cache[$ps_plugin_name]; }
		
		$plugin_dir = Media::$plugin_path;
		
		# load the plugin
		if (!class_exists("WLPlugMedia{$ps_plugin_name}")) { 
			require_once("{$plugin_dir}/{$ps_plugin_name}.php"); 
		}
		$vs_classname = "WLPlugMedia{$ps_plugin_name}";
		$p = new $vs_classname;
		# register the plugin's capabilities
		
		return $p->checkStatus();
	}
	# ----------------------------------------------------------
	/**
	 * Determine format of a file
	 *
	 * @param string $ps_filepath
	 * @param array $pa_options Options include:
	 *		noCache = don't use cache. [Default is false]
	 *		returnPluginInstance = Return instance of media plugin rather than mimetype. [Default is false]
	 *
	 * @return mixed String Mimetype of file, or null if file is in an unknown format. If returnPluginInstance option is set then a plugin capable of handling the file is returned.
	 */
	function divineFileFormat($ps_filepath, $pa_options=null) {
		$pb_return_plugin_instance = caGetOption('returnPluginInstance', $pa_options, false);
		$pb_no_cache = caGetOption('noCache', $pa_options, false);
		
		if (!$pb_no_cache && $pb_return_plugin_instance && isset(Media::$s_divine_cache[$ps_filepath.'_plugin'])) { return Media::$s_divine_cache[$ps_filepath.'_plugin']; }
		if (!$pb_no_cache && isset(Media::$s_divine_cache[$ps_filepath])) { return Media::$s_divine_cache[$ps_filepath]; }
		
		if (sizeof(Media::$s_divine_cache) > 200) { Media::$s_divine_cache = array_slice(Media::$s_divine_cache, 100); }
		
		$vs_plugin_name = ''; $vs_mimetype = null;
		$va_plugin_names = $this->getPluginNames();

		// take an educated guess at which plugins to try, and put those at the head of the list
		if ($vs_plugin_name = Media::$s_file_extension_to_plugin_map[pathinfo($ps_filepath, PATHINFO_EXTENSION)]) {
			unset($va_plugin_names[array_search($vs_plugin_name, $va_plugin_names)]);
			array_unshift($va_plugin_names, $vs_plugin_name);
		}

		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $this->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			if ($this->DEBUG) { print "[DEBUG] TRYING ".$vs_plugin_name."<br>\n"; }
			if ($vs_mimetype = $o_plugin->divineFileFormat($ps_filepath)) {
				Media::$s_divine_cache[$ps_filepath] = $vs_mimetype;
				Media::$s_divine_cache[$ps_filepath.'_plugin'] = $o_plugin;
				if ($pb_return_plugin_instance) { return  $o_plugin; }
				break;
			}
		}
		
		if ($vs_mimetype) {
			if ($this->DEBUG) { print "[DEBUG] Plugin ".$vs_plugin_name." returned {$vs_mimetype}<br/>\n"; }
			return $vs_mimetype;
		} else {
			$this->postError(1605, _t("File type is not supported"), "Media->divineFileFormat()");
			return Media::$s_divine_cache[$ps_filepath] = "";
			Media::$s_divine_cache[$ps_filepath.'_plugin'] = null;
		}
	}
	# ----------------------------------------------------------
	public function getMimetypeTypename($ps_mimetype) {
		$va_plugin_names = $this->getPluginNames();
		foreach ($va_plugin_names as $vs_plugin_name) {
			$va_plugin_info = $this->getPlugin($vs_plugin_name);
			$o_plugin = $va_plugin_info["INSTANCE"];
			if (isset($o_plugin->typenames[$ps_mimetype]) && ($vs_typename = $o_plugin->typenames[$ps_mimetype])) {
				return $vs_typename;
			}
		}
		return "unknown";
	}
	# ----------------------------------------------------------
	public function get($property) {
		if (!$this->instance) { return ""; }
		return $this->instance->get($property);
	}
	# ----------------------------------------------------------
	public function getProperties() {
		if (!$this->instance) { return ""; }
		return $this->instance->properties;
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if (!$this->instance) { return false; }
		$this->instance->set($property, $value);
	}
	# ----------------------------------------------------------
	public function getExtractedText() {
		if (!$this->instance) { return false; }
		return $this->instance->getExtractedText();
	}
	# ----------------------------------------------------------
	public function getExtractedTextLocations() {
		if (!$this->instance) { return false; }
		return $this->instance->getExtractedTextLocations();
	}
	# ----------------------------------------------------------
	public function getExtractedMetadata() {
		if (!$this->instance) { return false; }
		return $this->instance->getExtractedMetadata();
	}
	# ----------------------------------------------------------
	public function read($filepath) {
		if ((!$this->instance) || ($filepath != $this->filepath)) {
			$this->instance = $this->divineFileFormat($filepath, ['returnPluginInstance' => true]);
		}
			
		if ($this->instance) {
			$this->instance->init();
			$vn_res = $this->instance->read($filepath, $mimetype);
		  if ($this->DEBUG) { print "USING ".$plugin_info["NAME"]."\n"; }
			if (!$vn_res) {
				$this->postError(1605, join("; ", $this->instance->getErrors()), "Media->read()");	
			} 
			
			return $vn_res;
		} else {
		  $this->postError(1605, _t("File type is not supported"), "Media->read()");
		  return false;
		}
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if (!$this->instance) { return false; }
		if (!($vb_ret = $this->instance->transform($operation, $parameters))) {
			$this->errors = $this->instance->errors;
		}
		return $vb_ret;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype, $pa_options=null) {
		if (!$this->instance) { return false; }
		
		# TODO: support for cross-plugin writes; that is, allow a file to be read in
		# by a plugin and convert to an intermediate format supported by a second plugin
		# in order to allow the second plug-in to write out the file in the desired format.
		$rc = $this->instance->write($filepath, $mimetype, $pa_options);
		$this->errors = $this->instance->errors;
		return $rc;
	}
	# ----------------------------------------------------------
	public function writePreviews($pa_options) {
		if (!$this->instance) { return false; }
	
		if (!method_exists($this->instance, 'writePreviews')) { return false; }
		$this->instance->set('version', '');
		return $this->instance->writePreviews($this->filepath, $pa_options);
	}
	# ----------------------------------------------------------
	public function joinArchiveContents($pa_files, $pa_options = array()) {
		if (!$this->instance) { return false; }
	
		if (!method_exists($this->instance, 'joinArchiveContents')) { return false; }
		$this->instance->set('version', '');
		return $this->instance->joinArchiveContents($pa_files, $pa_options);
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function writeClip($ps_filename, $ps_start_, $ps_end, $pa_options=null) {
		if (!$this->instance) { return false; }
		
		if (method_exists($this->instance, "writeClip")) {
			return $this->instance->writeClip($ps_filename, $ps_start_, $ps_end, $pa_options);
		}
		return null;
	}
	# ----------------------------------------------------------
	public function getOutputFormats() {
		if (!$this->instance) { return false; }
		return $this->instance->getOutputFormats();
	}
	# ----------------------------------------------------------
	public function getTransformations() {
		if (!$this->instance) { return false; }
		return $this->instance->getTransformations();
	}
	# ----------------------------------------------------------
	public function reset() {
		return $this->instance->reset();
	}
	# ----------------------------------------------------------
	public function cleanup() {
		if ($this->instance) {
			return $this->instance->cleanup();
		} else {
			return true;
		}
	}
	# ----------------------------------------------------------
	public function dump() {
		print_r($this->getPluginNames());
	}
	# ------------------------------------------------
	public function mimetype2extension($mimetype) {
		if (!$this->instance) {
			return "";
		}
		return $this->instance->mimetype2extension($mimetype);
	}
	# ------------------------------------------------
	public function extension2mimetype($extension) {
		if (!$this->instance) {
		  	return "";
		}
		return $this->instance->extension2mimetype($extension);
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		if (!$this->instance) {
		  return "";
		}
		return $this->instance->mimetype2typename($mimetype);
	}
	# ------------------------------------------------
	/**
	 * Return list of file extensions for media formats supported for import
	 *
	 * @return array List of file extensions
	 */
	public static function getImportFileExtensions() {
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_extensions = array();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_extensions = array_merge($va_extensions, $o_plugin->getImportExtensions());
		}
		
		return array_unique($va_extensions);
	}
	# ------------------------------------------------
	/**
	 * Return list of file extensions for media formats supported for import
	 *
	 * @return array List of file extensions
	 */
	public function getPluginImportFileExtensionMap() {
		$va_plugin_names = $this->getPluginNames();
		
		$va_map = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $this->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			foreach($va_extensions = $o_plugin->getImportExtensions() as $vs_ext) {
				$va_map[$vs_ext] = $vs_plugin_name;
			}
			
		}
		
		return $va_map;
	}
	# ------------------------------------------------
	/**
	 * Return list of mimetypes for media formats supported for import
	 *
	 * @return array List of mimetypes
	 */
	public static function getImportMimetypes() {
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_extensions = array();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_extensions = array_replace($va_extensions, $o_plugin->getImportMimetypes());
		}
		
		return array_unique($va_extensions);
	}
	# ------------------------------------------------
	private function getPluginsForMimetypes() {
		$va_plugin_names = $this->getPluginNames();

		$va_return = array();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $this->getPlugin($vs_plugin_name)) { continue; }
			/** @var BaseMediaPlugin $o_plugin */
			$o_plugin = $va_plugin_info["INSTANCE"];
			foreach($o_plugin->getImportMimeTypes() as $vs_mimetype) {
				$va_return[$vs_mimetype][] = $vs_plugin_name;
			}
		}

		return $va_return;
	}
	# ------------------------------------------------
	/**
	 * Return list of file extensions for media formats supported for export
	 *
	 * @return array List of file extensions
	 */
	public static function getExportFileExtensions() {
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_extensions = array();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_extensions = array_merge($va_extensions, $o_plugin->getExportExtensions());
		}
		
		return array_unique($va_extensions);
	}
	# ------------------------------------------------
	/**
	 * Return list of mimetypes for media formats supported for export
	 *
	 * @return array List of mimetypes
	 */
	public static function getExportMimetypes() {
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_extensions = array();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_extensions = array_merge($va_extensions, $o_plugin->getExportMimetypes());
		}
		
		return array_unique($va_extensions);
	}
	# ------------------------------------------------
	/**
	 * Return mimetype for given file extension. Only formats supported by an installed plugin for import or export are recognized.
	 *
	 * @return string Mimetype or null if extension is not recognized.
	 */
	public static function getMimetypeForExtension($ps_extension) {
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_formats = array();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_formats = array_merge($va_formats, $o_plugin->getImportFormats(), $o_plugin->getExportFormats());
		}
		$va_formats = array_flip($va_formats);
		return $va_formats[strtolower($ps_extension)];
	}
	# ------------------------------------------------
	/**
	 * Return file extension for given mimetype. Only formats supported by an installed plugin for import or export are recognized.
	 *
	 * @return string File extension or null if mimetype is not recognized.
	 */
	public static function getExtensionForMimetype($ps_mimetype) {
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_formats = array();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_formats = array_merge($va_formats, $o_plugin->getImportFormats(), $o_plugin->getExportFormats());
		}
		return $va_formats[strtolower($ps_mimetype)];
	}
	# ------------------------------------------------
	/**
	 * Return file type name for given mimetype. Only formats supported by an installed plugin for import or export are recognized.
	 *
	 * @return string Type name or null if mimetype is not recognized.
	 */
	public static function getTypenameForMimetype($ps_mimetype) {
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			if ($vs_typename = $o_plugin->mimetype2typename($ps_mimetype)) {
				return $vs_typename;
			}
		}
		return null;
	}
	# ------------------------------------------------
	# --- 
	# ------------------------------------------------
	public function htmlTag($ps_mimetype, $ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!$ps_mimetype) { return _t('No media available'); }
		$va_plugin_names = $this->getPluginNames();
		foreach($va_plugin_names as $vs_plugin_name) {
			$p = $this->getUnregisteredPlugin($vs_plugin_name);
			if ((isset($p->info['EXPORT'][$ps_mimetype])) || (isset($p->info['IMPORT'][$ps_mimetype]))) {
				$pa_properties["mimetype"] = $ps_mimetype;
				return $p->htmlTag($ps_url, $pa_properties, $pa_options, $pa_volume_info);
			}
		}
		
		return _t("Could not find plug-in for mimetype %1", $ps_mimetype);
	}
	# ------------------------------------------------
}