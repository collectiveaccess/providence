<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Cache/CAFileSystemCache.php : extension of doctrine fs cache
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

namespace Doctrine\Common\Cache;

class CAFileSystemCache extends FilesystemCache {

	/**
	 * Override file name logic in Doctrine\Common\Cache\FileCache
	 * @param string $ps_key cache key
	 * @return string
	 */
	protected function getFilename($ps_key) {
		$vs_hash = md5($ps_key);
		$vs_path = implode(str_split($vs_hash, 12), DIRECTORY_SEPARATOR);
		$vs_path = $this->directory . DIRECTORY_SEPARATOR . $vs_path;

		return $vs_path . DIRECTORY_SEPARATOR . $vs_hash . $this->getExtension();
	}
}
