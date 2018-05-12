<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/Remote/File.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2017 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\Media\Remote;

require_once(__CA_LIB_DIR__.'/core/Media/Remote/Base.php');

class File extends Base {

	/**
	 * @param string $ps_url
	 * @param string $ps_target_location
	 * @throws FileException
	 */
	public function downloadMediaForProcessing($ps_url, $ps_target_location) {
		if(!$this->canHandle($ps_url)) { throw new FileException('Invalid URL'); }

		$this->downloadFromVerifiedURL($ps_url, $ps_target_location);
	}

	/**
	 * Determines if this plugin can handle the given URL
	 * @param string $ps_url
	 * @return bool
	 */
	public function canHandle($ps_url) {
		$ps_url = html_entity_decode($ps_url);
		if(!isURL($ps_url)) { return false; }

		$va_parts = parse_url($ps_url);
		if(!is_array($va_parts)) {
			return false;
		}

	// REMOVED BECAUSE NOT ALL FILE URLS END WITH WHAT LOOKS LIKE A FILE
		// needs to have a file name at the end
		//if(!preg_match("/(.+)\.[A-Za-z0-9]{1,5}$/u", $va_parts['path']) && !preg_match("/(.+)\.[A-Za-z0-9]{1,5}$/u", $va_parts['query'])) {
		//	return false;
		//}

		return true;
	}

	public function getOriginalFilename($ps_url) {
		if(!$this->canHandle($ps_url)) { throw new FileException('Invalid URL'); }

		$va_matches = [];
		if(preg_match("/(([^\/]+)\.[A-Za-z0-9]{1,5})$/u", $ps_url, $va_matches[2])) {
			return $va_matches[1];
		}

		return $ps_url;
	}
}

class FileException extends \Exception {}
