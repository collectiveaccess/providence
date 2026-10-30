<?php
/** ---------------------------------------------------------------------
 * app/lib/TranscriptionManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @subpackage Transcription
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA;

/**
 * Loads AV media transcription plugins from app/lib/Plugins/Transcription
 */
class TranscriptionManager {
	# ----------------------------------------------------------
	/**
	 *
	 */
	const PLUGIN_PREFIX = '\\CA\\Transcription\\Plugins\\';

	/**
	 *
	 */
	private static $plugin_names = null;

	/**
	 *
	 */
	private static $plugin_cache = [];

	# ----------------------------------------------------------
	/**
	 * Return list of installed transcription plugin names
	 *
	 * @return array
	 */
	public static function getServiceNames() : array {
		if(is_array(self::$plugin_names)) { return self::$plugin_names; }

		self::$plugin_names = [];
		foreach(glob(__CA_LIB_DIR__.'/Plugins/Transcription/*.php') as $path) {
			$name = pathinfo($path, PATHINFO_FILENAME);
			if(preg_match('!^Base!', $name)) { continue; }
			self::$plugin_names[] = $name;
		}
		sort(self::$plugin_names);
		return self::$plugin_names;
	}
	# ----------------------------------------------------------
	/**
	 * Name of transcription service set in app.conf transcription_service directive
	 *
	 * @return string
	 */
	public static function getDefaultServiceName() : string {
		return \Configuration::load()->get('transcription_service') ?: 'Whisper';
	}
	# ----------------------------------------------------------
	/**
	 * Get instance of transcription service plugin. Names are case-insensitive.
	 *
	 * @param string $name Name of service. If omitted the service configured in app.conf transcription_service is used. [Default is null]
	 *
	 * @return BaseTranscriptionPlugin Plugin instance, or null if the service does not exist or is not available (Eg. not installed or not configured)
	 */
	public static function getService(?string $name=null) {
		if(!$name) { $name = self::getDefaultServiceName(); }
		$key = strtolower($name);
		if(array_key_exists($key, self::$plugin_cache)) { return self::$plugin_cache[$key]; }

		foreach(self::getServiceNames() as $plugin_name) {
			if(strtolower($plugin_name) !== $key) { continue; }

			require_once(__CA_LIB_DIR__."/Plugins/Transcription/{$plugin_name}.php");
			$class = self::PLUGIN_PREFIX.$plugin_name;
			$p = new $class();
			return self::$plugin_cache[$key] = (is_array($info = $p->register()) ? $info['INSTANCE'] : null);
		}
		caGetLogger()->logError(_t('[TranscriptionManager] Transcription service %1 is not installed. Available services are: %2', $name, join(', ', self::getServiceNames())));
		return self::$plugin_cache[$key] = null;
	}
	# ----------------------------------------------------------
	/**
	 * Check if transcription service is installed and configured
	 *
	 * @param string $name Name of service. If omitted the service configured in app.conf transcription_service is used. [Default is null]
	 *
	 * @return bool
	 */
	public static function isAvailable(?string $name=null) : bool {
		return (bool)self::getService($name);
	}
	# ----------------------------------------------------------
}
