<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Cache/CompositeCache.php : provides simple combination of on-disk and in-memory caching
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

require_once(__CA_LIB_DIR__.'/core/Cache/MemoryCache.php');
require_once(__CA_LIB_DIR__.'/core/Cache/ExternalCache.php');

class CompositeCache {
	# ------------------------------------------------
	/**
	 * Fetches an entry from the cache.
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return mixed The cached data or FALSE, if no cache entry exists for the given key.
	 */
	public static function fetch($ps_key, $ps_namespace='default') {
		if(MemoryCache::contains($ps_key, $ps_namespace)) {
			return MemoryCache::fetch($ps_key, $ps_namespace);
		}

		if(ExternalCache::contains($ps_key, $ps_namespace)) {
			//Debug::msg("[CompositeCache] got {$ps_namespace}:{$ps_key} from external cache");
			// copy data into 'L1' cache so that subsequent fetch() and contain() calls are fast
			$vm_data = ExternalCache::fetch($ps_key, $ps_namespace);
			MemoryCache::save($ps_key, $vm_data, $ps_namespace);
			return $vm_data;
		}

		return false;
	}
	# ------------------------------------------------
	/**
	 * Puts data into the cache. Overwrites existing items!
	 * @param string $ps_key
	 * @param mixed $pm_data
	 * @param string $ps_namespace
	 * @param int $pn_ttl
	 * @return bool success state
	 */
	public static function save($ps_key, $pm_data, $ps_namespace='default', $pn_ttl=null) {
		MemoryCache::save($ps_key, $pm_data, $ps_namespace);
		ExternalCache::save($ps_key, $pm_data, $ps_namespace, $pn_ttl);

		return true;
	}
	# ------------------------------------------------
	/**
	 * Test if an entry exists in the cache.
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool
	 */
	public static function contains($ps_key, $ps_namespace='default') {
		if(MemoryCache::contains($ps_key, $ps_namespace)) {
			return true;
		}

		if(ExternalCache::contains($ps_key, $ps_namespace)) {
			return true;
		}

		return false;
	}
	# ------------------------------------------------
	/**
	 * Remove a given key from cache
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool success state
	 */
	public static function delete($ps_key, $ps_namespace='default') {
		//Debug::msg("[CompositeCache] delete {$ps_namespace}:{$ps_key}");
		MemoryCache::delete($ps_key, $ps_namespace);
		ExternalCache::delete($ps_key, $ps_namespace);

		return true;
	}
	# ------------------------------------------------
	/**
	 * Flush whole cache, both in-memory and on-disk. Use with caution!
	 */
	public static function flush() {
		MemoryCache::flush();
		ExternalCache::flush();
	}
	# ------------------------------------------------
}

