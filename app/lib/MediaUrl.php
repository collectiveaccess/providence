<?php
/** ---------------------------------------------------------------------
 * app/lib/MediaUrl.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2025 Whirl-i-Gig
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
namespace CA;

 /**
  *
  */
require_once(__CA_LIB_DIR__.'/Plugins/PluginConsumer.php');

class MediaUrl extends \CA\Plugins\PluginConsumer {
	# ----------------------------------------------------------
	# Properties
	# ----------------------------------------------------------
	static $s_plugin_list = null;
	
	# ----------------------------------------------------------
	# Methods
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function __construct(?bool $no_cache=false) { 
		if (!self::$plugin_path) { self::$plugin_path = __CA_LIB_DIR__.'/Plugins/MediaUrl'; }
		self::$exclusion_list = ['BaseMediaUrlPlugin.php'];
		
		self::$name = 'MediaUrl';
		self::$plugin_prefix = '\\CA\\MediaUrl\\Plugins\\';
	}
	
	# ----------------------------------------------------------
	/**
	 * Validate url
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		format = Suggest preferred format to plugins that can return different formats for the same resource (Eg. GoogleDrive). The format is only a preference and may be ignored. [Default is NULL]
	 *		limit = Limit processing to a specific plugin or list of plugins. Note that the name of the default file URL handler is "_File". [Default is null; all plugins are used]
	 *
	 * @return array|bool False if no plugin can process the url, or an array of information about the URL on success.
	 */
	public function validate(string $url, ?array $options=null) {
		$plugin_names = $this->getPluginNames($options);
		foreach ($plugin_names as $plugin_name) {
			if (!($plugin_info = $this->getPlugin($plugin_name))) { continue; }
			if ($f = $plugin_info['INSTANCE']->parse($url, $options)) {
				$f['INSTANCE'] = $plugin_info['INSTANCE'];
				return $f;
			}
		}
		return false;
	}
	# ----------------------------------------------------------
	/**
	 * Fetch contents of URL
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		format = Suggest preferred format to plugins that can return different formats for the same resource (Eg. GoogleDrive). The format is only a preference and may be ignored. [Default is NULL]
	 *		limit = Limit processing to a specific plugin or list of plugins. Note that the name of the default file URL handler is "_File". [Default is null; all plugins are used]
	 *		returnAsString = Return fetched content as string rather than in a file. [Default is false]
	 *
	 * @return bool|array|string False is no plugin can process the url, an array of data including the path to a file  containing the URL contents on success, or a string with file content is the returnAsString option is set.
	 */
	public function fetch(string $url, ?array $options=null) {
		$active = $this->_getActivePlugins();
		foreach($active as $p) {
			try {
				if ($f = $p['plugin']->fetch($url, array_merge($options ?? [], $p['config']))) {
					return $f;
				}
			} catch (\UrlFetchException $e) {
				return false;
			}
		}
		return false;
	}
	# ----------------------------------------------------------
	/**
	 * Fetch preview image for URL
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		format = Suggest preferred format to plugins that can return different formats for the same resource (Eg. GoogleDrive). The format is only a preference and may be ignored. [Default is NULL]
	 *		limit = Limit processing to a specific plugin or list of plugins. Note that the name of the default file URL handler is "_File". [Default is null; all plugins are used]
	 *		returnAsString = Return fetched content as string rather than in a file. [Default is false]
	 *
	 * @return bool|array|string False is no plugin can process the url, an array of data including the path to a file  containing the URL contents on success, or a string with file content is the returnAsString option is set.
	 */
	public function fetchPreview(string $url, ?array $options=null) {
		$active = $this->_getActivePlugins();
		foreach($active as $p) {
			try {
				if ($f = $p['plugin']->fetchPreview($url, array_merge($options ?? [], $p['config']))) {
					return $f;
				}
			} catch (\UrlFetchException $e) {
				return false;
			}
		}
		return false;
	}
	# ----------------------------------------------------------
	/**
	 * Get service-specific HTML embedding tag for media
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		width = Width to apply to embedded content. [Default is 100% width]
	 *		height = Height to use for embedded content. [Default is 100% height]
	 *		title = Title to apply to embedded content. [Default is null]
	 *
	 * @return string HTML embed tag, or null if embedding is not possible
	 */
	public function embedTag(string $url, ?array $options=null) : ?string {
		$active = $this->_getActivePlugins();
		foreach($active as $p) {
			if ($f = $p['plugin']->embedTag($url, array_merge($options ?? [], $p['config']))) {
				return $f;
			}
		}
		return null;
	}
	# ----------------------------------------------------------
	/**
	 * Get service-specific icon for media
	 *
	 * @param string $url
	 * @param array $options None currently supported
	 *
	 * @return string HTML icon or null if no service-specific icon was found
	 */
	public function icon(string $url, ?array $options=null) : ?string {
		if(is_array($p = $this->validate($url, $options))) {
			if ($f = $p['INSTANCE']->icon($url, $options ?? [])) {
				return $f;
			}
		}
		return null;
	}
	# ----------------------------------------------------------
	/**
	 * Get service name for media
	 *
	 * @param string $url
	 * @param array $options None currently supported
	 *
	 * @return string Name of service from which media was fetched
	 */
	public function service(string $url, ?array $options=null) : ?string {
		if(is_array($p = $this->validate($url, $options))) {
			if ($f = $p['INSTANCE']->service($url, $options ?? [])) {
				return $f;
			}
		}
		return null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _getActivePlugins() : array {
		if(self::$s_plugin_list) { return self::$s_plugin_list; }
		$config = \Configuration::load()->getAssoc('allow_fetching_of_media_using_plugins');
		foreach($config as $k => $v) {
			$config[strtolower($k)] = $v;
		}
		$plugin_names = $this->getPluginNames();
		
		$active = [];
		foreach ($plugin_names as $plugin_name) {
			if (!($plugin_info = $this->getPlugin($plugin_name))) { continue; }
			
			$plugin_name_lc = strtolower($plugin_name);
			if(isset($config[$plugin_name_lc]) && is_array($config[$plugin_name_lc])) {
				$pconfig = $config[$plugin_name_lc];
			} elseif(isset($config['*']) && is_array($config['*'])) {
				$pconfig = $config['*'];
			} else {
				$pconfig = ['enabled' => 1];
			}
			
			if(!($pconfig['enabled'] ?? true)) { 
				continue; 
			}
			$active[] = ['plugin' => $plugin_info['INSTANCE'], 'config' => $pconfig];
		}
		self::$s_plugin_list = $active;
		return $active;
	}
	# ------------------------------------------------
}
