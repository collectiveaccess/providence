<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Cache/MemoryCache.php : provides fast in-memory caching
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @subpackage Cache
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

class MemoryCache {
	# ------------------------------------------------
	private static $opa_caches = array();
	# ------------------------------------------------
	/**
	 * Initialize cache for given namespace if necessary
	 * Namespace declaration is optional
	 * @param string $ps_namespace Optional namespace
	 * @throws MemoryCacheInvalidParameterException
	 */
	private static function init($ps_namespace='default') {
		// catch invalid namespace definitions
		if(!is_string($ps_namespace)) { throw new MemoryCacheInvalidParameterException('Namespace has to be a string'); }

		if(self::nameSpaceExists($ps_namespace)) {
			return;
		} else {
			self::$opa_caches[$ps_namespace] = array();
		}
	}
	# ------------------------------------------------
	/**
	 * Is the given namespace initialized?
	 * @param string $ps_namespace
	 * @return bool
	 */
	private static function nameSpaceExists($ps_namespace='default') {
		return (isset(self::$opa_caches[$ps_namespace]) && is_array(self::$opa_caches[(string)$ps_namespace]));
	}
	# ------------------------------------------------
	/**
	 * Get a cache item
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return mixed|null null if key does not exist
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function getItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);

		if(!$ps_key) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		if(array_key_exists($ps_key, self::$opa_caches[$ps_namespace])) {
			return self::$opa_caches[$ps_namespace][$ps_key];
		} else {
			return null;
		}
	}
	# ------------------------------------------------
	/**
	 * Set a cache item. Overwrites existing items!
	 * @param string $ps_key
	 * @param mixed $pm_data
	 * @param string $ps_namespace
	 * @return bool success state
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function setItem($ps_key, $pm_data, $ps_namespace='default') {
		self::init($ps_namespace);

		if(!$ps_key) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		self::$opa_caches[$ps_namespace][$ps_key] = $pm_data;
		return true;
	}
	# ------------------------------------------------
	/**
	 * Replace an existing cache item
	 * @param string $ps_key
	 * @param mixed $pm_data
	 * @param string $ps_namespace
	 * @return bool false if item couldn't be replaced
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function replaceItem($ps_key, $pm_data, $ps_namespace='default') {
		self::init($ps_namespace);

		if(!$ps_key) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		if(array_key_exists($ps_key, self::$opa_caches[$ps_namespace])) {
			self::$opa_caches[$ps_namespace][$ps_key] = $pm_data;
			return true;
		} else {
			return false;
		}
	}
	# ------------------------------------------------
	/**
	 * Does a given cache key exist?
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function hasItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);

		if(!$ps_key) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		return array_key_exists($ps_key, self::$opa_caches[$ps_namespace]);
	}
	# ------------------------------------------------
	/**
	 * Remove a given key from cache
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool success state
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function removeItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);
		if(!$ps_key) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		if(array_key_exists($ps_key, self::$opa_caches[$ps_namespace])) {
			unset(self::$opa_caches[$ps_namespace][$ps_key]);
			return true;
		} else {
			return false;
		}
	}
	# ------------------------------------------------
	/**
	 * Flush cache
	 * @param string|null $ps_namespace Optional namespace definition. If given, only this namespace is wiped.
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function flush($ps_namespace=null) {
		if(!$ps_namespace) {
			self::$opa_caches = array();
		} else {
			if(!is_string($ps_namespace)) { throw new MemoryCacheInvalidParameterException('Namespace has to be a string'); }
			self::$opa_caches[$ps_namespace] = array();
		}
	}
	# ------------------------------------------------
}

class MemoryCacheInvalidParameterException extends Exception {}
