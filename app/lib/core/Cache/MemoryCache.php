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
	 */
	private static function init($ps_namespace='default') {
		if(self::nameSpaceExists($ps_namespace)) {
			return;
		} else {
			self::$opa_caches[(string)$ps_namespace] = new Zend\Cache\Storage\Adapter\Memory();

			$o_plugin = new Zend\Cache\Storage\Plugin\ExceptionHandler();
			$o_plugin->getOptions()->setThrowExceptions(false);
			self::getCacheObjectForNamespace($ps_namespace)->addPlugin($o_plugin);
		}
	}
	# ------------------------------------------------
	/**
	 * Does a given namespace exist?
	 * @param string $ps_namespace
	 * @return bool
	 */
	private static function nameSpaceExists($ps_namespace='default') {
		return isset(self::$opa_caches[(string)$ps_namespace]);
	}
	# ------------------------------------------------
	/**
	 * Get Zend\Cache object for a given namespace
	 * @param string $ps_namespace
	 * @return Zend\Cache\Storage\Adapter\Memory
	 */
	private static function getCacheObjectForNamespace($ps_namespace='default') {
		return self::$opa_caches[(string)$ps_namespace];
	}
	# ------------------------------------------------
	/**
	 * Get a cache item
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return mixed
	 */
	public function getItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->getItem($ps_key);
	}
	# ------------------------------------------------
	/**
	 * Get multiple cache items at once
	 * @param array $pa_keys
	 * @param string $ps_namespace
	 * @return array
	 */
	public function getItems($pa_keys, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->getItems($pa_keys);
	}
	# ------------------------------------------------
	/**
	 * Set a cache item
	 * @param string $ps_key
	 * @param mixed $pm_data
	 * @param string $ps_namespace
	 * @return bool
	 */
	public function setItem($ps_key, $pm_data, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->setItem($ps_key, $pm_data);
	}
	# ------------------------------------------------
	/**
	 * Set multiple cache objects at once
	 * @param array $pa_key_value_data key=>value map of cache keys and corresponding objects
	 * @param string $ps_namespace
	 * @return array
	 */
	public function setItems($pa_key_value_data, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->setItems($pa_key_value_data);
	}
	# ------------------------------------------------
	/**
	 * Replace an existing cache item
	 * @param string $ps_key
	 * @param mixed $pm_data
	 * @param string $ps_namespace
	 * @return bool
	 */
	public function replaceItem($ps_key, $pm_data, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->replaceItem($ps_key, $pm_data);
	}
	# ------------------------------------------------
	/**
	 * Replace list of cache items
	 * @param array $pa_key_value_data
	 * @param string $ps_namespace
	 * @return array
	 */
	public function replaceItems($pa_key_value_data, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->replaceItems($pa_key_value_data);
	}
	# ------------------------------------------------
	/**
	 * Does a given cache key exist?
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool
	 */
	public function hasItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->hasItem($ps_key);
	}
	# ------------------------------------------------
	/**
	 * Check existence for a list of keys
	 * @param array $pa_keys
	 * @param string $ps_namespace
	 * @return array
	 */
	public function hasItems($pa_keys, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->hasItems($pa_keys);
	}
	# ------------------------------------------------
}
