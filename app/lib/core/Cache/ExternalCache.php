<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Cache/ExternalCache.php : provides caching using external facilities
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

require_once(__CA_LIB_DIR__."/core/Cache/MemoryCache.php");

class ExternalCache {
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
			self::$opa_caches[(string)$ps_namespace] = self::getCacheObject($ps_namespace);
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
	 * @return Zend\Cache\Storage\StorageInterface
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
	public static function getItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);

		return unserialize(self::getCacheObjectForNamespace($ps_namespace)->getItem($ps_key));
	}
	# ------------------------------------------------
	/**
	 * Get multiple cache items at once
	 * @param array $pa_keys
	 * @param string $ps_namespace
	 * @return array
	 */
	public static function getItems($pa_keys, $ps_namespace='default') {
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
	public static function setItem($ps_key, $pm_data, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->setItem($ps_key, serialize($pm_data));
	}
	# ------------------------------------------------
	/**
	 * Set multiple cache objects at once
	 * @param array $pa_key_value_data key=>value map of cache keys and corresponding objects
	 * @param string $ps_namespace
	 * @return array array of not stored keys
	 */
	public static function setItems($pa_key_value_data, $ps_namespace='default') {
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
	public static function replaceItem($ps_key, $pm_data, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->replaceItem($ps_key, serialize($pm_data));
	}
	# ------------------------------------------------
	/**
	 * Replace list of cache items
	 * @param array $pa_key_value_data
	 * @param string $ps_namespace
	 * @return array Array of not stored keys
	 */
	public static function replaceItems($pa_key_value_data, $ps_namespace='default') {
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
	public static function hasItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->hasItem($ps_key);
	}
	# ------------------------------------------------
	/**
	 * Check existence for a list of keys
	 * @param array $pa_keys
	 * @param string $ps_namespace
	 * @return array list of found keys
	 */
	public static function hasItems($pa_keys, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->hasItems($pa_keys);
	}
	# ------------------------------------------------
	/**
	 * Remove a given key from cache
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool
	 */
	public static function removeItem($ps_key, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->removeItem($ps_key);
	}
	# ------------------------------------------------
	/**
	 * Remove a list of keys from cache
	 * @param array $pa_keys
	 * @param string $ps_namespace
	 * @return array
	 */
	public static function removeItems($pa_keys, $ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->removeItems($pa_keys);
	}
	# ------------------------------------------------
	/**
	 * Flush the whole cache
	 * @param string $ps_namespace
	 * @return bool
	 */
	public static function flush($ps_namespace='default') {
		self::init($ps_namespace);

		return self::getCacheObjectForNamespace($ps_namespace)->flush();
	}
	# ------------------------------------------------
	# Helpers
	# ------------------------------------------------
	private static function getCacheObject($ps_namespace) {

		if(MemoryCache::hasItem('cache_backend') && MemoryCache::getItem('cache_configuration')) {
			$vs_cache_backend = MemoryCache::getItem('cache_backend');
		} else {
			$o_app_conf = Configuration::load();
			$o_cache_conf = Configuration::load($o_app_conf->get('cache_config'));
			$vs_cache_backend = $o_cache_conf->get('cache_backend');
			MemoryCache::setItem('cache_backend', $vs_cache_backend);
			MemoryCache::setItem('cache_configuration', $o_cache_conf);
		}

		switch($vs_cache_backend) {
			case 'file':
				return self::getFileCacheObject($ps_namespace);
			default:

		}
	}
	# ------------------------------------------------
	private static function getFileCacheObject($ps_namespace){
		$o_cache_conf = MemoryCache::getItem('cache_configuration');

		$o_cache = \Zend\Cache\StorageFactory::factory(array(
			'adapter' => array(
				'name' => 'Filesystem',
				'options' => array (
					'ttl' => ($o_cache_conf->get('cache_ttl') ? (int) $o_cache_conf->get('cache_ttl') : 3600),
					'cache_dir' => ($o_cache_conf->get('cache_file_path') ? $o_cache_conf->get('cache_file_path') : __CA_APP_DIR__.'/tmp'),
					'dir_permission' => 0755,
					'file_permission' => 0644,
					'namespace' => $ps_namespace,
				),
			),
			'plugins' => array (
				'exception_handler' => array(
					'throw_exceptions' => false,
				)
			)
		));

		return $o_cache;
	}
	# ------------------------------------------------
}
