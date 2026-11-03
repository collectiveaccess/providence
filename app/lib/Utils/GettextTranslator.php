<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/GettextTranslator.php : user interface translations from gettext .mo files
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Translates strings using one or more gettext .mo files. Replaces Zend_Translate with the gettext adapter.
 */
class GettextTranslator {
	# -------------------------------------------------------
	/**
	 * Translations, keyed on original string
	 */
	private $translations = [];

	/**
	 *
	 */
	private $locale;

	/**
	 * Cache parsed translation files?
	 */
	private $use_cache;
	# -------------------------------------------------------
	/**
	 * @param string|array $paths Path, or list of paths, to .mo files. Translations in later files take precedence.
	 * @param string $locale Locale of translations
	 * @param array $options Options include:
	 *		cache = Cache parsed translation files. [Default is false]
	 */
	public function __construct($paths, string $locale, ?array $options=null) {
		$this->locale = $locale;
		$this->use_cache = (bool)($options['cache'] ?? false);
		foreach(is_array($paths) ? $paths : [$paths] as $path) {
			$this->addTranslation($path);
		}
	}
	# -------------------------------------------------------
	/**
	 * Add translations from a .mo file. Translations in the file take precedence over those already loaded.
	 * Files that cannot be read are skipped.
	 *
	 * @param string $path
	 * @return bool True if file was loaded
	 */
	public function addTranslation(string $path) : bool {
		if (!is_array($translations = $this->load($path))) { return false; }
		$this->translations = $translations + $this->translations;
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Return translation for string, or the string itself if no translation is available
	 *
	 * @param string $str
	 * @return string
	 */
	public function _($str) {
		return $this->translations[$str] ?? $str;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function translate($str) {
		return $this->_($str);
	}
	# -------------------------------------------------------
	/**
	 * Determine if a translation is available for string
	 *
	 * @param string $str
	 * @return bool
	 */
	public function isTranslated($str) : bool {
		return isset($this->translations[$str]);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getLocale() : string {
		return $this->locale;
	}
	# -------------------------------------------------------
	/**
	 * Load translations from .mo file, from cache if possible
	 *
	 * @param string $path
	 * @return array|null
	 */
	private function load(string $path) : ?array {
		if (!is_readable($path) || !($mtime = @filemtime($path))) { return null; }

		$key = md5(realpath($path).'/'.$mtime);
		if ($this->use_cache) {
			try {
				if (is_array($translations = ExternalCache::fetch($key, 'GettextTranslator'))) { return $translations; }
			} catch (Throwable $e) {
				$this->use_cache = false;
			}
		}
		if (!is_array($translations = self::parseMOFile($path))) { return null; }
		if ($this->use_cache) {
			try {
				ExternalCache::save($key, $translations, 'GettextTranslator', 3600 * 24);
			} catch (Throwable $e) {
				// cache is not available
			}
		}
		return $translations;
	}
	# -------------------------------------------------------
	/**
	 * Parse gettext .mo file. Untranslated strings and the file header are omitted. For plural forms the singular
	 * translation is returned. Strings with a context are keyed on context and original string, separated by \x04.
	 *
	 * @param string $path
	 * @return array|null Translations keyed on original string, or null if the file is not a valid .mo file
	 */
	static public function parseMOFile(string $path) : ?array {
		if (!is_string($data = @file_get_contents($path)) || (strlen($data) < 28)) { return null; }

		$magic = unpack('V', substr($data, 0, 4))[1];
		if ($magic === 0x950412de) {
			$fmt = 'V';
		} elseif ($magic === 0xde120495) {
			$fmt = 'N';
		} else {
			return null;
		}
		$header = unpack("{$fmt}revision/{$fmt}count/{$fmt}originals/{$fmt}translations", substr($data, 4, 16));
		$count = $header['count'];
		$size = strlen($data);
		if (($header['originals'] + ($count * 8) > $size) || ($header['translations'] + ($count * 8) > $size)) { return null; }

		$originals = array_values(unpack("{$fmt}".($count * 2), substr($data, $header['originals'], $count * 8)) ?: []);
		$translated = array_values(unpack("{$fmt}".($count * 2), substr($data, $header['translations'], $count * 8)) ?: []);

		$translations = [];
		for($i = 0; $i < $count; $i++) {
			if (!($length = $translated[$i * 2])) { continue; }			// untranslated
			$original = explode("\0", substr($data, $originals[($i * 2) + 1], $originals[$i * 2]))[0];
			if (!strlen($original)) { continue; }						// file header
			$translation = explode("\0", substr($data, $translated[($i * 2) + 1], $length))[0];
			$translations[$original] = $translation;
		}
		return $translations;
	}
	# -------------------------------------------------------
}
