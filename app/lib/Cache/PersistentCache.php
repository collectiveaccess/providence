<?php
/** ---------------------------------------------------------------------
 * app/lib/Cache/PersistentCache.php : provides database-backed cache for non-expiring data
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Db.php');

class PersistentCache {
	# ------------------------------------------------
	/** 
	 * Database connection for persistent cache
	 */
	static $db;
	
	/**
	 *
	 */
	static $memory_cache = [];

	/**
	 *
	 */
	static $existance_cache = [];
	# ------------------------------------------------
	/**
	 * Fetches an entry from the cache.
	 * @param string $key
	 * @param string $namespace
	 * @return mixed The cached data or NULL, if no cache entry exists for the given key.
	 */
	public static function fetch($key, $namespace='default') {
		if (!self::$db) { self::$db = new Db(); }
		$cache_key = md5("{$key}/{$namespace}");
		
		$qr = self::$db->query("SELECT cache_value FROM ca_persistent_cache WHERE cache_key = ?", [$cache_key]);
		if ($qr && $qr->nextRow()) {
			self::$existance_cache[$cache_key] = true;
			return self::$memory_cache[$cache_key] = caUnserializeForDatabase($qr->get('cache_value'));
		}
		return null;
	}
	# ------------------------------------------------
	/**
	 * Puts data into the cache. Overwrites existing items!
	 * @param string $key
	 * @param mixed $data
	 * @param string $namespace
	 * @param int $ttl Ignored as this is a persistent cache :-)
	 * @return bool success state
	 */
	public static function save($key, $data, $namespace='default', $ttl=null) {
		if (!self::$db) { self::$db = new Db(); }
		$cache_key = md5("{$key}/{$namespace}");
		
		if (self::contains($key, $namespace)) {
			$qr = self::$db->query("UPDATE ca_persistent_cache SET cache_value = ?, updated_on = ? WHERE cache_key = ?", [caSerializeForDatabase($data), time(), $cache_key]);
		} else {
			$qr = self::$db->query("INSERT INTO ca_persistent_cache (cache_key, cache_value, created_on, updated_on, namespace) VALUES (?, ?, ?, ?, ?)", [$cache_key, caSerializeForDatabase($data), time(), time(), $namespace]);
		}
		
		if ((bool)$qr) {
			self::$existance_cache[$cache_key] = true;
			self::$memory_cache[$cache_key] = $data;
		}
		
		return (bool)$qr;
	}
	# ------------------------------------------------
	/**
	 * Test if an entry exists in the cache.
	 * @param string $key
	 * @param string $namespace
	 * @return bool success state
	 */
	public static function contains($key, $namespace='default') {
		if (!self::$db) { self::$db = new Db(); }
		$cache_key = md5("{$key}/{$namespace}");
		if(isset(self::$existance_cache[$cache_key])) { return self::$existance_cache[$cache_key]; }
		
		$qr = self::$db->query("SELECT cache_key FROM ca_persistent_cache WHERE cache_key = ?", [$cache_key]);
		if ($qr && $qr->nextRow()) {
			self::$existance_cache[$cache_key] = true;
			return true;
		}
		
		self::$existance_cache[$cache_key] = false;
		return false;
	}
	# ------------------------------------------------
	/**
	 * Remove a given key from cache
	 * @param string $key
	 * @param string $namespace
	 * @return bool success state
	 */
	public static function delete($key, $namespace='default') {
		if (!self::$db) { self::$db = new Db(); }
		$cache_key = md5("{$key}/{$namespace}");
		
		$qr = self::$db->query("DELETE FROM ca_persistent_cache WHERE cache_key = ?", [$cache_key]);
		
		if ((bool)$qr) {
			unset(self::$existance_cache[$cache_key]);
			unset(self::$memory_cache[$cache_key]);
		}
		return (bool)$qr;
	}
	# ------------------------------------------------
	/**
	 * Flush whole cache. Use with caution!
	 *
	 * @param string $namespace 
	 */
	public static function flush($namespace=null) {
		if (!self::$db) { self::$db = new Db(); }
		
		if ($namespace) {
			$qr = self::$db->query("DELETE FROM ca_persistent_cache WHERE namespace = ?", [$namespace]);
		} else {
			$qr = self::$db->query("TRUNCATE TABLE ca_persistent_cache");
		}
		self::$existance_cache = [];
		self::$memory_cache = [];
	}
	# ------------------------------------------------
	/**
	 * Clean entries that have not been updated
	 *
	 * @param int $since Unix timeststamp. All entries that have not been updated since this time will be removed.
	 * @param string $namespace Restrict cleaning to specific namespace.
	 */
	public static function clean($since, $namespace=null) {
		if ($since <= 0) { return; }
		if (!self::$db) { self::$db = new Db(); }
		
		if ($namespace) {
			$qr = self::$db->query("DELETE FROM ca_persistent_cache WHERE namespace = ? AND updated_on < ?", [$namespace, (int)$since]);
		} else {
			$qr = self::$db->query("DELETE FROM ca_persistent_cache WHERE updated_on < ?", [(int)$since]);
		}
		self::$existance_cache = [];
		self::$memory_cache = [];
	}
	# ------------------------------------------------
}
