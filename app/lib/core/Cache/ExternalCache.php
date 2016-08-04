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
require_once(__CA_LIB_DIR__."/core/Cache/CAFileSystemCache.php");

class ExternalCache {
	# ------------------------------------------------
	/**
	 * @var Doctrine\Common\Cache\CacheProvider
	 */
	private static $opo_cache;
	# ------------------------------------------------
	/**
	 * Initialize cache for given namespace if necessary
	 * Namespace declaration is optionale
	 * @throws ExternalCacheInvalidParameterException
	 */
	private static function init() {
		if(self::cacheExists()) {
			return true;
		} else {
			if(self::$opo_cache = self::getCacheObject()) {
				return true;
			} else {
				return false;
			}
		}
	}
	# ------------------------------------------------
	/**
	 * Does the cache object exist?
	 * @return bool
	 */
	private static function cacheExists() {
		return (isset(self::$opo_cache) && (self::$opo_cache instanceof Doctrine\Common\Cache\CacheProvider));
	}
	# ------------------------------------------------
	private static function checkParameters($ps_namespace, $ps_key) {
		if(!is_string($ps_namespace)) {
			throw new ExternalCacheInvalidParameterException('Namespace has to be a string');
		}

		if(!preg_match("/^[A-Za-z0-9_]+$/", $ps_namespace)) {
			throw new ExternalCacheInvalidParameterException('Caching namespace must only contain alphanumeric characters, dashes and underscores');
		}

		if(!$ps_key) {
			throw new ExternalCacheInvalidParameterException('Key cannot be empty');
		}
	}
	# ------------------------------------------------
	/**
	 * Get cache object from static object property
	 * @return Doctrine\Common\Cache\CacheProvider
	 */
	private static function getCache() {
		if(isset(self::$opo_cache)) {
			return self::$opo_cache;
		} else {
			return null;
		}
	}
	# ------------------------------------------------
	/**
	 * Get a cache item
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return mixed
	 * @throws ExternalCacheInvalidParameterException
	 */
	public static function fetch($ps_key, $ps_namespace='default') {
		if(!self::init()) { return false; }
		self::checkParameters($ps_namespace, $ps_key);

		return self::getCache()->fetch(self::makeKey($ps_key, $ps_namespace));
	}
	# ------------------------------------------------
	/**
	 * Puts data into the cache. Overwrites existing items!
	 * @param string $ps_key
	 * @param mixed $pm_data
	 * @param string $ps_namespace
	 * @param int $pn_ttl 
	 * @return bool
	 * @throws ExternalCacheInvalidParameterException
	 */
	public static function save($ps_key, $pm_data, $ps_namespace='default', $pn_ttl=null) {
		if(!self::init()) { return false; }
		self::checkParameters($ps_namespace, $ps_key);

		if(!defined('__CA_CACHE_TTL__')) {
			define('__CA_CACHE_TTL__', 3600);
		}

		self::getCache()->save(self::makeKey($ps_key, $ps_namespace), $pm_data, (!is_null($pn_ttl) ? $pn_ttl : __CA_CACHE_TTL__));
		return true;
	}
	# ------------------------------------------------
	/**
	 * Does a given cache key exist?
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool
	 * @throws ExternalCacheInvalidParameterException
	 */
	public static function contains($ps_key, $ps_namespace='default') {
		if(!self::init()) { return false; }
		self::checkParameters($ps_namespace, $ps_key);

		return self::getCache()->contains(self::makeKey($ps_key, $ps_namespace));
	}
	# ------------------------------------------------
	/**
	 * Remove a given key from cache
	 * @param string $ps_key
	 * @param string $ps_namespace
	 * @return bool
	 * @throws ExternalCacheInvalidParameterException
	 */
	public static function delete($ps_key, $ps_namespace='default') {
		if(!self::init()) { return false; }
		self::checkParameters($ps_namespace, $ps_key);

		return self::getCache()->delete(self::makeKey($ps_key, $ps_namespace));
	}
	# ------------------------------------------------
	/**
	 * Flush cache
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function flush() {
		try {
			if(!self::init()) { return false; }
			self::getCache()->flushAll();
		} catch(UnexpectedValueException $e) {
			// happens during the installer pre tasks when we just purge everything in app/tmp without asking.
			// At that point we have existing objects in self::$opo_cache that can't deal with that.
			// We do nothing here because the directory is re-created automatically the next time someone
			// tries to access the cache.
		}
	}
	# ------------------------------------------------
	/**
	 * Get cache stats
	 * @return array|bool
	 */
	public static function getStats() {
		if(!self::init()) { return false; }

		return self::getCache()->getStats();
	}
	# ------------------------------------------------
	# Helpers
	# ------------------------------------------------
	private static function getCacheObject() {
		if(!defined('__CA_CACHE_BACKEND__')) {
			define('__CA_CACHE_BACKEND__', 'file');
		}

		switch(__CA_CACHE_BACKEND__) {
			case 'memcached':
				return self::getMemcachedObject();
			case 'redis':
				return self::getRedisObject();
			case 'apc':
				return self::getApcObject();
			case 'file':
			default:
				return self::getFileCacheObject();
		}
	}
	# ------------------------------------------------
	private static function getFileCacheObject(){
		$vs_cache_base_dir = (defined('__CA_CACHE_FILEPATH__') ? __CA_CACHE_FILEPATH__ : __CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp');
		$vs_cache_dir = $vs_cache_base_dir.DIRECTORY_SEPARATOR.__CA_APP_NAME__.'Cache';

		try {
			$o_cache = new \Doctrine\Common\Cache\CAFileSystemCache($vs_cache_dir, '.ca.cache');
			return $o_cache;
		} catch (InvalidArgumentException $e) {
			// carry on ... but no caching :(
			return null;
		}
	}
	# ------------------------------------------------
	private static function getMemcachedObject(){
		if(!defined('__CA_MEMCACHED_HOST__')) {
			define('__CA_MEMCACHED_HOST__', 'localhost');
		}

		if(!defined('__CA_MEMCACHED_PORT__')) {
			define('__CA_MEMCACHED_PORT__', 11211);
		}

		$o_memcached = new Memcached();
		$o_memcached->addServer(__CA_MEMCACHED_HOST__, __CA_MEMCACHED_PORT__);

		$o_cache = new \Doctrine\Common\Cache\MemcachedCache();
		$o_cache->setMemcached($o_memcached);
		return $o_cache;
	}
	# ------------------------------------------------
	private static function getRedisObject(){
		if(!defined('__CA_REDIS_HOST__')) {
			define('__CA_REDIS_HOST__', 'localhost');
		}

		if(!defined('__CA_REDIS_PORT__')) {
			define('__CA_REDIS_PORT__', 6379);
		}

		$o_redis = new Redis();
		$o_redis->connect(__CA_REDIS_HOST__, __CA_REDIS_PORT__);
		if(defined('__CA_REDIS_DB__') && is_int(__CA_REDIS_DB__)) {
			$o_redis->select(__CA_REDIS_DB__);
		}

		$o_cache = new \Doctrine\Common\Cache\RedisCache();
		$o_cache->setRedis($o_redis);
		return $o_cache;
	}
	# ------------------------------------------------
	private static function getApcObject(){
		return new \Doctrine\Common\Cache\ApcCache();
	}
	# ------------------------------------------------
	private static function makeKey($ps_key, $ps_namespace) {
		if(!defined('__CA_APP_TYPE__')) { define('__CA_APP_TYPE__', 'PROVIDENCE'); }
		return __CA_APP_TYPE__.':'.$ps_key.':'.$ps_namespace;
	}
	# ------------------------------------------------
}

class ExternalCacheInvalidParameterException extends Exception {}
