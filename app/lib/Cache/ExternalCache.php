<?php
/** ---------------------------------------------------------------------
 * app/lib/Cache/ExternalCache.php : provides caching using external facilities
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/Cache/MemoryCache.php");

class ExternalCache {
	# ------------------------------------------------
	/**
	 * 
	 */
	private static $cache;
	
	/**
	 *
	 */
	private static $invalidation_method = null;
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
			if(self::$cache = self::getCacheObject()) {
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
		return (isset(self::$cache) && (self::$cache instanceof Stash\Pool));
	}
	# ------------------------------------------------
	/**
	 * Set cache invalidation mode. See http://www.stashphp.com/Invalidation.html for a description
	 * of each method. The default is Invalidation::PRECOMPUTE which is the best choice for most.
	 * most situations. Invalidation::NONE is required for testing with short TTL's to avoid
	 * side effects from "cache stampede" protection.
	 * @return bool
	 */
	public static function setInvalidationMode($pn_method) {
		if (!in_array($pn_method, [Stash\Invalidation::NONE, Stash\Invalidation::PRECOMPUTE, Stash\Invalidation::OLD, Stash\Invalidation::VALUE, Stash\Invalidation::SLEEP])) { return false; }
		self::$invalidation_method = $pn_method;
		return true;
	}
	# ------------------------------------------------
	/**
	 * 
	 * @return string
	 */
	private static function filterKey($ps_key) {
		return str_replace("/", "_", $ps_key);
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
		if(isset(self::$cache)) {
			return self::$cache;
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
		$ps_key = self::filterKey($ps_key);
		self::checkParameters($ps_namespace, $ps_key);

		$item = self::getCache()->getItem(self::makeKey($ps_key, $ps_namespace));
		if(!is_null(self::$invalidation_method)) { $item->setInvalidationMethod(self::$invalidation_method); }
		return $item->isMiss() ? null : $item->get();
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
		$ps_key = self::filterKey($ps_key);
		self::checkParameters($ps_namespace, $ps_key);

		if(!defined('__CA_CACHE_TTL__')) {
			define('__CA_CACHE_TTL__', 3600);
		}

		$pool = self::getCache();
		$item = $pool->getItem(self::makeKey($ps_key, $ps_namespace));
		$item->expiresAfter((!is_null($pn_ttl) ? $pn_ttl : __CA_CACHE_TTL__));
		$item->set($pm_data);
		$pool->save($item);
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
		$ps_key = self::filterKey($ps_key);
		self::checkParameters($ps_namespace, $ps_key);
		
		$item = self::getCache()->getItem(self::makeKey($ps_key, $ps_namespace));
		if(!is_null(self::$invalidation_method)) { $item->setInvalidationMethod(self::$invalidation_method); }
		return !$item->isMiss();
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
		$ps_key = self::filterKey($ps_key);
		self::checkParameters($ps_namespace, $ps_key);
		
		self::getCache()->deleteItem(self::makeKey($ps_key, $ps_namespace));
		return true;
	}
	# ------------------------------------------------
	/**
	 * Flush cache
	 * @throws MemoryCacheInvalidParameterException
	 */
	public static function flush($ps_namespace=null) {
		try {
			if(!self::init()) { return false; }
			
			if ($ps_namespace) {
				self::getCache()->deleteItem($z=substr(__CA_APP_TYPE__, 0, 4).'/'.$ps_namespace);
			} else {
			    self::getCache()->clear();
			}
		} catch(UnexpectedValueException $e) {
			// happens during the installer pre tasks when we just purge everything in app/tmp without asking.
			// At that point we have existing objects in self::$cache that can't deal with that.
			// We do nothing here because the directory is re-created automatically the next time someone
			// tries to access the cache.
		}
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
			case 'sqlite':
				return self::getSqliteObject();
			case 'file':
			default:
				return self::getFileCacheObject();
		}
	}
	# ------------------------------------------------
	private static function getCacheDirectory() {
		$vs_cache_base_dir = (defined('__CA_CACHE_FILEPATH__') ? __CA_CACHE_FILEPATH__ : __CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp');
		$vs_cache_dir = $vs_cache_base_dir.DIRECTORY_SEPARATOR.__CA_APP_NAME__.'Cache';
		if(!file_exists($vs_cache_dir)) { @mkdir($vs_cache_dir); }
		return $vs_cache_dir;
	}
	# ------------------------------------------------
	private static function getFileCacheObject(){
		try {
			$driver = new Stash\Driver\FileSystem([
				'path' => ExternalCache::getCacheDirectory(),
				'dirSplit' => 2
			]);
			return new Stash\Pool($driver);
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
		$driver = new Stash\Driver\Memcache(['servers' => [__CA_MEMCACHED_HOST__, __CA_MEMCACHED_PORT__, 'prefix_key' => ExternalCache::makeCacheName(), 'serializer' => 'json']]);
		return new Stash\Pool($driver);
	}
	# ------------------------------------------------
	private static function getRedisObject(){
		if(!defined('__CA_REDIS_HOST__')) {
			define('__CA_REDIS_HOST__', 'localhost');
		}

		if(!defined('__CA_REDIS_PORT__')) {
			define('__CA_REDIS_PORT__', 6379);
		}
		if(!defined('__CA_REDIS_DB__')) {
			define('__CA_REDIS_DB__', 0);
		}
		
		$driver = new Stash\Driver\Redis(['servers' => [[__CA_REDIS_HOST__, __CA_REDIS_PORT__]], 'database' => __CA_REDIS_DB__]);
		return new Stash\Pool($driver);
	}
	# ------------------------------------------------
	private static function getSqliteObject(){
		$driver = new Stash\Driver\Sqlite([
			'path' => ExternalCache::getCacheDirectory(),
			'nesting' => 0
		]);
		return new Stash\Pool($driver);
	}
	# ------------------------------------------------
	private static function getApcObject(){
		$driver = new Stash\Driver\Apc(['ttl' => __CA_CACHE_TTL__, 'namespace' => ExternalCache::makeCacheName()]);
		return new Stash\Pool($driver);
	}
	# ------------------------------------------------
	private static function makeKey($ps_key, $ps_namespace) {
		if(!defined('__CA_APP_TYPE__')) { define('__CA_APP_TYPE__', 'PROVIDENCE'); }
		return substr(__CA_APP_TYPE__, 0, 4).'/'.$ps_namespace.'/'.$ps_key; // only use the first four chars of app type for compactness
	}
	# ------------------------------------------------
	private static function makeCacheName() {
		if(!defined('__CA_APP_TYPE__')) { define('__CA_APP_TYPE__', 'PROVIDENCE'); }
		return substr(__CA_APP_TYPE__, 0, 4).'_'.__CA_APP_NAME__;	// only use the first four chars of app type for compactness
	}
	# ------------------------------------------------
}

class ExternalCacheInvalidParameterException extends Exception {}
