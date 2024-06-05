<?php
/** ---------------------------------------------------------------------
 * app/lib/Media.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2023 Whirl-i-Gig
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
	/**
	 *
	 */
	public $DEBUG = false;
	
	/**
	 *
	 */
	private $plugins = [];
	
	/**
	 *
	 */
	private $instance;
	
	# --- 
	# Cache loaded plug-ins
	# ---
	/**
	 *
	 */
	static $WLMedia_plugin_cache = [];
	
	/**
	 *
	 */
	static $WLMedia_unregistered_plugin_cache = [];
	
	/**
	 *
	 */
	static $WLMedia_plugin_names = null;
	
	/**
	 *
	 */
	static $plugin_path = null;
	
	/**
	 *
	 */
	static $s_file_extension_to_plugin_map = null; 
	
	/**
	 *
	 */
	static $s_divine_cache = [];
	
	/**
	 *
	 */
	 private $tmp_files = [];
	 
	 /*
	  *
	  */
	 private $filepath = null;
	# ----------------------------------------------------------
	# Methods
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function __construct($pb_no_cache=false) { 
		if (!Media::$plugin_path) { Media::$plugin_path = __CA_LIB_DIR__.'/Plugins/Media'; }
		
		if (!(Media::$s_file_extension_to_plugin_map = CompositeCache::contains('media_file_extension_to_plugin_map'))) {
			CompositeCache::save('media_file_extension_to_plugin_map', $this->getPluginImportFileExtensionMap());
		}
		Media::$s_file_extension_to_plugin_map = CompositeCache::fetch('media_file_extension_to_plugin_map');
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getPluginNames() {
		if (is_array(Media::$WLMedia_plugin_names)) { return Media::$WLMedia_plugin_names; }
		
		Media::$WLMedia_plugin_names = [];
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
	public function getPlugin($plugin_name) {
		if (!($p = $this->getUnregisteredPlugin($plugin_name))) { return null; }
		
		# register the plugin's capabilities
		if ($vo_instance = $p->register()) {
			if ($this->DEBUG) {	print "[DEBUG] LOADED $plugin_name<br>\n"; }
			return Media::$WLMedia_plugin_cache[$plugin_name] = $vo_instance;
		} else {
			if ($this->DEBUG) {	print "[DEBUG] DID NOT LOAD $plugin_name<br>\n"; }
			Media::$WLMedia_plugin_cache[$plugin_name] = false;
			return null;
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getUnregisteredPlugin($plugin_name) {
		if(!in_array($plugin_name, $this->getPluginNames())) { return null; }
		
		$plugin_dir = Media::$plugin_path;
		
		# load the plugin
		if (!class_exists("WLPlugMedia{$plugin_name}")) { 
			require_once("{$plugin_dir}/{$plugin_name}.php"); 
		}
		$ps_plugin_class = "WLPlugMedia{$plugin_name}";
		$p = new $ps_plugin_class();
		
		Media::$WLMedia_unregistered_plugin_cache[$plugin_name] = $p;
		
		return $p;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function checkPluginStatus($plugin_name) {
		
		if(!in_array($plugin_name, $this->getPluginNames())) { return null; }
		if (isset(Media::$WLMedia_plugin_cache[$plugin_name])) { return Media::$WLMedia_plugin_cache[$plugin_name]; }
		
		$plugin_dir = Media::$plugin_path;
		
		# load the plugin
		if (!class_exists("WLPlugMedia{$plugin_name}")) { 
			require_once("{$plugin_dir}/{$plugin_name}.php"); 
		}
		$vs_classname = "WLPlugMedia{$plugin_name}";
		$p = new $vs_classname;
		# register the plugin's capabilities
		
		return $p->checkStatus();
	}
	# ----------------------------------------------------------
	/**
	 * Determine format of a file
	 *
	 * @param string $ps_filepath
	 * @param array $options Options include:
	 *		noCache = don't use cache. [Default is false]
	 *		returnPluginInstance = Return instance of media plugin rather than mimetype. [Default is false]
	 *
	 * @return mixed String Mimetype of file, or null if file is in an unknown format. If returnPluginInstance option is set then a plugin capable of handling the file is returned.
	 */
	function divineFileFormat($ps_filepath, $options=null) {
		$pb_return_plugin_instance = caGetOption('returnPluginInstance', $options, false);
		$pb_no_cache = caGetOption('noCache', $options, false);
		
		if (!$pb_no_cache && $pb_return_plugin_instance && isset(Media::$s_divine_cache[$ps_filepath.'_plugin'])) { return Media::$s_divine_cache[$ps_filepath.'_plugin']; }
		if (!$pb_no_cache && isset(Media::$s_divine_cache[$ps_filepath])) { return Media::$s_divine_cache[$ps_filepath]; }
		
		if (sizeof(Media::$s_divine_cache) > 200) { Media::$s_divine_cache = array_slice(Media::$s_divine_cache, 100); }
		
		$vs_plugin_name = ''; $vs_mimetype = null;
		$va_plugin_names = $this->getPluginNames();

		// take an educated guess at which plugins to try, and put those at the head of the list
		if ($vs_plugin_name = (Media::$s_file_extension_to_plugin_map[pathinfo($ps_filepath, PATHINFO_EXTENSION)] ?? null)) {
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
	/**
	 *
	 */
	public function getMimetypeTypename($mimetype) {
		$va_plugin_names = $this->getPluginNames();
		foreach ($va_plugin_names as $vs_plugin_name) {
			$va_plugin_info = $this->getPlugin($vs_plugin_name);
			$o_plugin = $va_plugin_info["INSTANCE"];
			if (isset($o_plugin->typenames[$mimetype]) && ($vs_typename = $o_plugin->typenames[$mimetype])) {
				return $vs_typename;
			}
		}
		return "unknown";
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function get($property) {
		if (!$this->instance) { return ""; }
		return $this->instance->get($property);
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getProperties() {
		if (!$this->instance) { return null; }
		return $this->instance->properties;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function isValidProperty($property) {
		if (!$this->instance) { return null; }
		$props = $this->instance->getProperties();
		return isset($props[$property]);
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function set($property, $value) {
		if (!$this->instance) { return false; }
		$this->instance->set($property, $value);
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getExtractedText() {
		if (!$this->instance) { return false; }
		return $this->instance->getExtractedText();
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getExtractedTextLocations() {
		if (!$this->instance) { return false; }
		return $this->instance->getExtractedTextLocations();
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getExtractedMetadata() {
		if (!$this->instance) { return false; }
		return $this->instance->getExtractedMetadata();
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function read($filepath, $mimetype=null, $options=null) {
		if ((!$this->instance) || ($filepath != $this->filepath)) {
			$this->instance = $this->divineFileFormat($filepath, ['returnPluginInstance' => true]);
		}
			
		if ($this->instance) {
			$this->instance->init();
			$vn_res = $this->instance->read($filepath, $mimetype, $options);
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
	/**
	 *
	 */
	public function transform($operation, $parameters) {
		if (!$this->instance) { return false; }
		if (!($vb_ret = $this->instance->transform($operation, $parameters))) {
			$this->errors = $this->instance->errors;
		}
		return $vb_ret;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function write($filepath, $mimetype, $options=null) {
		if (!$this->instance) { return false; }
		
		# TODO: support for cross-plugin writes; that is, allow a file to be read in
		# by a plugin and convert to an intermediate format supported by a second plugin
		# in order to allow the second plug-in to write out the file in the desired format.
		$rc = $this->instance->write($filepath, $mimetype, $options);
		if(caGetOption('temporary', $options, false)) {
			$this->tmp_files[] = $rc;
		}
		$this->errors = $this->instance->errors;
		return $rc;
	}
	# ----------------------------------------------------------
	/**
	 * Write media preview files for currently loaded media
	 *
	 * @param array $options Options include:
	 *		cleanupFiles = Delete generated files when Media instance is garbage collected. [Default is true]
	 */
	public function writePreviews($options=null) {
		if (!$this->instance) { return false; }
	
		if (!method_exists($this->instance, 'writePreviews')) { return false; }
		$this->instance->set('version', '');
		$files = $this->instance->writePreviews($this->filepath, $options);
		
		if(is_array($files) && caGetOption('cleanupFiles', $options, true)) {
			$this->tmp_files = array_unique(array_merge($this->tmp_files, $files));
		}
		return $files;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function joinArchiveContents($pa_files, $options = []) {
		if (!$this->instance) { return false; }
	
		if (!method_exists($this->instance, 'joinArchiveContents')) { return false; }
		$this->instance->set('version', '');
		return $this->instance->joinArchiveContents($pa_files, $options);
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function writeClip($ps_filename, $ps_start_, $ps_end, $options=null) {
		if (!$this->instance) { return false; }
		
		if (method_exists($this->instance, "writeClip")) {
			return $this->instance->writeClip($ps_filename, $ps_start_, $ps_end, $options);
		}
		return null;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getOutputFormats() {
		if (!$this->instance) { return false; }
		return $this->instance->getOutputFormats();
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function getTransformations() {
		if (!$this->instance) { return false; }
		return $this->instance->getTransformations();
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function reset() {
		return $this->instance->reset();
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function cleanup() {
		if ($this->instance) {
			return $this->instance->cleanup();
		} else {
			return true;
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function dump() {
		print_r($this->getPluginNames());
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function mimetype2extension($mimetype) {
		if (!$this->instance) {
			return "";
		}
		return $this->instance->mimetype2extension($mimetype);
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function extension2mimetype($extension) {
		if (!$this->instance) {
		  	return "";
		}
		return $this->instance->extension2mimetype($extension);
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function mimetype2typename($mimetype) {
		if (!$this->instance) {
		  return "";
		}
		return $this->instance->mimetype2typename($mimetype);
	}
	# ----------------------------------------------------------
	/**
	 * Return list of file extensions for media formats supported for import
	 *
	 * @return array List of file extensions
	 */
	public static function getImportFileExtensions() {
		if (CompositeCache::contains('getImportFileExtensions', 'Media')) {
			return CompositeCache::fetch('getImportFileExtensions', 'Media');
		}
		
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_extensions = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_extensions = array_merge($va_extensions, $o_plugin->getImportExtensions());
		}
		
		CompositeCache::save('getImportFileExtensions', $va_extensions = array_unique($va_extensions), 'Media');
		return $va_extensions;
	}
	# ----------------------------------------------------------
	/**
	 * Return list of file extensions for media formats supported for import
	 *
	 * @return array List of file extensions
	 */
	public function getPluginImportFileExtensionMap() {
		if (CompositeCache::contains('getPluginImportFileExtensionMap', 'Media')) {
			return CompositeCache::fetch('getPluginImportFileExtensionMap', 'Media');
		}
		$va_plugin_names = $this->getPluginNames();
		
		$va_map = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $this->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			foreach($va_extensions = $o_plugin->getImportExtensions() as $vs_ext) {
				$va_map[$vs_ext] = $vs_plugin_name;
			}
			
		}
		
		CompositeCache::save('getPluginImportFileExtensionMap', $va_map, 'Media');
		return $va_map;
	}
	# ----------------------------------------------------------
	/**
	 * Return list of mimetypes for media formats supported for import
	 *
	 * @return array List of mimetypes
	 */
	public static function getImportMimetypes() {
		if (CompositeCache::contains('getImportMimetypes', 'Media')) {
			return CompositeCache::fetch('getImportMimetypes', 'Media');
		}
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_extensions = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_extensions = array_replace($va_extensions, $o_plugin->getImportMimetypes());
		}
		
		CompositeCache::save('getImportMimetypes', $va_extensions = array_unique($va_extensions), 'Media');
		return $va_extensions;
	}
	# ----------------------------------------------------------
	private function getPluginsForMimetypes() {
		if (CompositeCache::contains('getPluginsForMimetypes', 'Media')) {
			return CompositeCache::fetch('getPluginsForMimetypes', 'Media');
		}
		$va_plugin_names = $this->getPluginNames();

		$va_return = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $this->getPlugin($vs_plugin_name)) { continue; }
			/** @var BaseMediaPlugin $o_plugin */
			$o_plugin = $va_plugin_info["INSTANCE"];
			foreach($o_plugin->getImportMimeTypes() as $vs_mimetype) {
				$va_return[$vs_mimetype][] = $vs_plugin_name;
			}
		}
		CompositeCache::save('getPluginsForMimetypes', $va_return, 'Media');
		return $va_return;
	}
	# ----------------------------------------------------------
	/**
	 * Return list of file extensions for media formats supported for export
	 *
	 * @return array List of file extensions
	 */
	public static function getExportFileExtensions() {
		if (CompositeCache::contains('getExportFileExtensions', 'Media')) {
			return CompositeCache::fetch('getExportFileExtensions', 'Media');
		}
		
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_extensions = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_extensions = array_merge($va_extensions, $o_plugin->getExportExtensions());
		}
		
		CompositeCache::save('getExportFileExtensions', $va_extensions = array_unique($va_extensions), 'Media');
		return $va_extensions;
	}
	# ----------------------------------------------------------
	/**
	 * Return list of mimetypes for media formats supported for export
	 *
	 * @return array List of mimetypes
	 */
	public static function getExportMimetypes() {
		if (CompositeCache::contains('getExportMimetypes', 'Media')) {
			return CompositeCache::fetch('getExportMimetypes', 'Media');
		}
		
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$mimetypes = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$mimetypes = array_merge($mimetypes, $o_plugin->getExportMimetypes());
		}
		
		CompositeCache::save('getExportMimetypes', $mimetypes = array_unique($mimetypes), 'Media');
		return $mimetypes;
	}
	# ----------------------------------------------------------
	/**
	 * Return mimetype for given file extension. Only formats supported by an installed plugin for import or export are recognized.
	 *
	 * @return string Mimetype or null if extension is not recognized.
	 */
	public static function getMimetypeForExtension($ps_extension) {
		if(!$ps_extension) { return null; }
		if (CompositeCache::contains($ps_extension, 'Media_getMimetypeForExtension')) {
			return CompositeCache::fetch($ps_extension, 'Media_getMimetypeForExtension');
		}
		
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_formats = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_formats = array_merge($va_formats, $o_plugin->getImportFormats(), $o_plugin->getExportFormats());
		}
		$va_formats = array_flip($va_formats);
		
		CompositeCache::save($ps_extension, $ret = $va_formats[strtolower($ps_extension)], 'Media_getMimetypeForExtension');
		return $ret;
	}
	# ----------------------------------------------------------
	/**
	 * Return file extension for given mimetype. Only formats supported by an installed plugin for import or export are recognized.
	 *
	 * @return string File extension or null if mimetype is not recognized.
	 */
	public static function getExtensionForMimetype($mimetype) {
		if(!$mimetype) { return null; }
		if (CompositeCache::contains($mimetype, 'Media_getExtensionForMimetype')) {
			return CompositeCache::fetch($mimetype, 'Media_getExtensionForMimetype');
		}
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		
		$va_formats = [];
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			$va_formats = array_merge($va_formats, $o_plugin->getImportFormats(), $o_plugin->getExportFormats());
		}
		CompositeCache::save($mimetype, $ret = $va_formats[strtolower($mimetype)], 'Media_getExtensionForMimetype');
		return $ret;
	}
	# ----------------------------------------------------------
	/**
	 * Return file type name for given mimetype. Only formats supported by an installed plugin for import or export are recognized.
	 *
	 * @return string Type name or null if mimetype is not recognized.
	 */
	public static function getTypenameForMimetype($mimetype) {
		if(!$mimetype) { return null; }
		if (CompositeCache::contains($mimetype, "Media_getTypenameForMimetype")) {
			return CompositeCache::fetch($mimetype, "Media_getTypenameForMimetype");
		}
		
		$o_media = new Media();
		$va_plugin_names = $o_media->getPluginNames();
		foreach ($va_plugin_names as $vs_plugin_name) {
			if (!$va_plugin_info = $o_media->getPlugin($vs_plugin_name)) { continue; }
			$o_plugin = $va_plugin_info["INSTANCE"];
			if ($vs_typename = $o_plugin->mimetype2typename($mimetype)) {
				CompositeCache::save($mimetype, $vs_typename, "Media_getTypenameForMimetype");
				return $vs_typename;
			}
		}
		CompositeCache::save($mimetype, null, "Media_getTypenameForMimetype");
		return null;
	}
	# ----------------------------------------------------------
	# --- 
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function htmlTag($mimetype, $ps_url, $pa_properties, $options=null, $pa_volume_info=null) {
		if (!$mimetype) { return _t('No media available'); }
		
		$map = $this->getPluginsForMimetypes();
		if(is_array($map[$mimetype]) && ($vs_plugin_name = $map[$mimetype][0])) {
			$p = $this->getUnregisteredPlugin($vs_plugin_name);
			if ((isset($p->info['EXPORT'][$mimetype])) || (isset($p->info['IMPORT'][$mimetype]))) {
				$pa_properties["mimetype"] = $mimetype;
				return $p->htmlTag($ps_url, $pa_properties, $options, $pa_volume_info);
			}
		}
		
		return _t("Could not find plug-in for mimetype %1", $mimetype);
	}
	# ----------------------------------------------------------
	# --- 
	# ----------------------------------------------------------
	/**
	 * 
	 */
	public function __destruct() {
		// Clean up tmp files
		if(is_array($this->tmp_files)) {
			foreach($this->tmp_files as $f) {
				if(file_exists($f)) { @unlink($f); }
			}
		}
	}
	# ------------------------------------------------
}
