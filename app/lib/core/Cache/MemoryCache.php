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
	 * Fetches an entry from the cache.
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return mixed The cached data or FALSE, if no cache entry exists for the given key.
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function fetch($ps_key, $ps_namespace='default') {
		if(!$ps_namespace) { throw new MemoryCacheInvalidParameterException('Namespace cannot be empty'); }

		if(!strlen($ps_key)) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		if(isset(self::$opa_caches[$ps_namespace][$ps_key])) {
			return self::$opa_caches[$ps_namespace][$ps_key];
		} else {
			return false;
		}
	}
	# ------------------------------------------------
	/**
	 * Puts data into the cache. Overwrites existing items!
	 * @param string $ps_key
	 * @param mixed $pm_data
	 * @param string $ps_namespace
	 * @return bool success state
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function save($ps_key, $pm_data, $ps_namespace='default') {
		if(!$ps_namespace) { throw new MemoryCacheInvalidParameterException('Namespace cannot be empty'); }

		if(!strlen($ps_key)) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		self::$opa_caches[$ps_namespace][$ps_key] = $pm_data;
		return true;
	}
	# ------------------------------------------------
	/**
	 * Test if an entry exists in the cache.
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function contains($ps_key, $ps_namespace='default') {
		if(!$ps_namespace) { throw new MemoryCacheInvalidParameterException('Namespace cannot be empty'); }

		if(!strlen($ps_key)) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		return (isset(self::$opa_caches[$ps_namespace]) && array_key_exists($ps_key, self::$opa_caches[$ps_namespace]));
	}
	# ------------------------------------------------
	/**
	 * Remove a given key from cache
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool success state
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function delete($ps_key, $ps_namespace='default') {
		if(!$ps_namespace) { throw new MemoryCacheInvalidParameterException('Namespace cannot be empty'); }
		if(!strlen($ps_key)) { throw new MemoryCacheInvalidParameterException('Key cannot be empty'); }

		if(!isset(self::$opa_caches[$ps_namespace])) { return false; }

		if(array_key_exists($ps_key, self::$opa_caches[$ps_namespace])) {
			unset(self::$opa_caches[$ps_namespace][$ps_key]);
			return true;
		} else {
			return false;
		}
	}
	# ------------------------------------------------
	/**
	 * Get number of items for a given namespace. Compared to other cache operations this is very slow so use with caution!
	 * @param string $ps_namespace
	 * @return int
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function itemCountForNamespace($ps_namespace='default') {
		if(!$ps_namespace) { throw new MemoryCacheInvalidParameterException('Namespace cannot be empty'); }

		if(is_array(self::$opa_caches[$ps_namespace])) {
			return sizeof(self::$opa_caches[$ps_namespace]);
		} else {
			return 0;
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
