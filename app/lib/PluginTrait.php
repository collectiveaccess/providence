<?php
/** ---------------------------------------------------------------------
 * app/lib/PluginTrait.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 
 trait PluginTrait {
    # ------------------------------------------------------
    /**
     * The following static properties must be set in the calling class:
     *
     *      $plugin_namespace = Namespace plugins reside in
     *      $plugin_names = Array of plugin names
     *      $plugin_cache = Array of plugin instances
     *      $plugin_path = Path to plugin directory
     *      $plugin_exclude_patterns = Array of regex patterns to match against plugin directory filenames. Anything that matches will not be loaded.
     */
    # ------------------------------------------------------
    /**
     *
     */
	public static function getPluginNames() {
		if (is_array(self::$s_plugin_names)) { return self::$s_plugin_names; }
		
		self::$s_plugin_names = [];
		$dir = opendir(self::$plugin_path);
		if (!$dir) { throw new ApplicationException(_t('Cannot open plugin directory %1', self::$plugin_path)); }
	
		while (($plugin = readdir($dir)) !== false) {
		    foreach(self::$plugin_exclude_patterns as $pattern) {
		        if (preg_match("!".preg_quote($pattern, "!")."!", $plugin)) { continue(2); }
		    }
			if (preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*).php$/", $plugin, $m)) {
				self::$s_plugin_names[] = $m[1];
			}
		}
		
		sort(self::$s_plugin_names);
		
		return self::$s_plugin_names;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public static function getPlugin($plugin_name) {
		if(!in_array($plugin_name, self::getPluginNames())) { return null; }
		if (isset(self::$plugin_cache[$plugin_name])) { return self::$plugin_cache[$plugin_name]; }
		
		$plugin_classname = self::$plugin_namespace."\\".$plugin_name;
		
		if (!class_exists($plugin_classname)) { 
			require_once(self::$plugin_path."/{$plugin_name}.php"); 
		}
		
		try {
		    return self::$plugin_cache[$plugin_name] = new $plugin_classname;
		} catch (Exception $e) {
		    throw new ApplicationException(_t("Could not load plugin %1", $plugin_name));
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public static function checkPluginStatus($plugin_name) {
		if (!($p = self::getPlugin($plugin_name))) {
		    throw new ApplicationException(_t("Could not load plugin %1", $plugin_name));
		}
		
		return $p->checkStatus();
	}
    # ------------------------------------------------------
 }