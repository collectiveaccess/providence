<?php
/** ---------------------------------------------------------------------
 * app/lib/GarbageCollection.php : configuration check singleton class
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2021 Whirl-i-Gig
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
 * @subpackage Configuration
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */


final class GarbageCollection {
	# -------------------------------------------------------
	/**
	 * Do all jobs needed for GC
	 *
	 * @params array $options Options include:
	 *		showCLIProgress = Print progress messages. [Default is false]
	 *		force = Perform garbage collection even if last run was less than five minutes ago. [Default is false]
	 *		limit = Maximum number of file cache files to process. Set to zero or null for no limit. [Default is 1000]
	 */
	public static function gc(array $options=null) {
		// contains() incidentally returns false when the TTL of this item is up
		// -> time for us to run the GC
		$show_cli = caGetOption('showCLIProgress', $options, false);
		$force = (bool)caGetOption('force', $options, false);
		
		if($force || !ExternalCache::contains('last_gc', 'gc') || (((int)ExternalCache::fetch('last_gc', 'gc') + 300) < time())) {
			if($show_cli) { CLIUtils::addMessage(_t('Removing stale disk cache items...')); }
			self::removeStaleDiskCacheItems($options);

			// refresh item with new TTL
			ExternalCache::save('last_gc', time(), 'gc', 300);
			
			// remove old user media files
			if($show_cli) { CLIUtils::addMessage(_t('Clearing old user media...')); }
			caCleanUserMediaDirectories();
					
			// Purge CSRF tokens that haven't been updated for at least a day from persistent cache
			if($show_cli) { CLIUtils::addMessage(_t('Removing old CSRF tokens...')); }
			PersistentCache::clean(time() - 86400, 'csrf_tokens');
		}
	}
	# -------------------------------------------------------
	/**
	 * Check and remove expired file cache entries. We assume use of the Stash JSON-based file cache encoder. 
	 * Each JSON file is parsed and its expiration date extracted. By default only 1000 files at a time are processed.
	 *
	 * @param array $options Options include:
	 *		limit = Maximum number of files to process. Set to 0 or null for no limit. [Default is 1000]
	 */
	private static function removeStaleDiskCacheItems(array $options=null) {
		if(__CA_CACHE_BACKEND__ != 'file') { return false; } // the other backends *should* honor the TTL we pass

		$vs_cache_base_dir = (defined('__CA_CACHE_FILEPATH__') ? __CA_CACHE_FILEPATH__ : __CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp');
		$vs_cache_dir = $vs_cache_base_dir.DIRECTORY_SEPARATOR.__CA_APP_NAME__.'Cache';

		$va_list = caGetDirectoryContentsAsList($vs_cache_dir, true, false, false, false, ['limit' => caGetOption('limit', $options, 1000)]);	// max 1000 files to check 
		foreach($va_list as $vs_file) {
			// NOTE: this assumes use of the Stash JSON-based cache encoder
			$d = @json_decode(@file_get_contents($vs_file), true);
			$exp = $d['expiration'] ?? null;
			
			if(!is_null($exp) && ($exp < time())) {
				@unlink($vs_file);
			}
		}

		$va_dir_list = caGetSubDirectoryList($vs_cache_dir);
		// note we're explicitly reversing the array here so that
		// the order is /foo/bar/foobar, then /foo/bar and then /foo
		// that way we don't need recursion because we just work our way up the directory tree
		foreach(array_reverse($va_dir_list) as $vs_dir => $vn_c) {
			if(caDirectoryIsEmpty($vs_dir)) {
				@rmdir($vs_dir);
			}
		}

		return true;
	}
	# -------------------------------------------------------
}
