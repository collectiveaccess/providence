<?php
/** ---------------------------------------------------------------------
 * app/lib/GarbageCollection.php : configuration check singleton class
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2019 Whirl-i-Gig
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
	 */
	public static function gc() {
		// contains() incidentally returns false when the TTL of this item is up
		// -> time for us to run the GC
		if(!ExternalCache::contains('last_gc')) {
			self::removeStaleDiskCacheItems();

			// refresh item with new TTL
			ExternalCache::save('last_gc', 'meow');
					
			// Purge CSRF tokens that haven't been updated for at least a day from persistent cache
			PersistentCache::clean(time() - 86400, 'csrf_tokens');
		}
	}
	# -------------------------------------------------------
	private static function removeStaleDiskCacheItems() {
		if(__CA_CACHE_BACKEND__ != 'file') { return false; } // the other backends *should* honor the TTL we pass

		$vs_cache_base_dir = (defined('__CA_CACHE_FILEPATH__') ? __CA_CACHE_FILEPATH__ : __CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp');
		$vs_cache_dir = $vs_cache_base_dir.DIRECTORY_SEPARATOR.__CA_APP_NAME__.'Cache';

		$va_list = caGetDirectoryContentsAsList($vs_cache_dir);
		$va_list = array_slice($va_list, 0, 2000);
		foreach($va_list as $vs_file) {
			$r = @fopen($vs_file, "r");

			if(!is_resource($r)) { continue; } // skip if for some reason the file couldn't be opened

			if (false !== ($vs_line = fgets($r))) {
				$vn_lifetime = (integer) $vs_line;

				if ($vn_lifetime !== 0 && $vn_lifetime < time()) {
					fclose($r);
					@unlink($vs_file);
				}
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
