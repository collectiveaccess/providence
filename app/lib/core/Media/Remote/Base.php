<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/Remote/Base.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/Media/Remote/Flickr.php');
require_once(__CA_LIB_DIR__.'/core/Media/Remote/File.php');

abstract class Base {

	/**
	 * Determines if this plugin can handle the given URL
	 * @param string $ps_url
	 * @return bool
	 */
	abstract public function canHandle($ps_url);

	/**
	 * From the given URL, download a media version that can be processed locally and store
	 * at the target location
	 * @param string $ps_url
	 * @param string $ps_target_location
	 */
	abstract public function downloadMediaForProcessing($ps_url, $ps_target_location);

	/**
	 * Gets original filename from given URL
	 *
	 * @param string $ps_url
	 * @return string
	 */
	abstract public function getOriginalFilename($ps_url);

	/**
	 * Determine if one of the plugins we have can handle this URL and return the plugin instance
	 * @param $ps_url
	 * @return bool|Base
	 */
	public static function getPluginInstance($ps_url) {
		if(!isURL($ps_url)) { return false; }
		if(!((bool)\Configuration::load()->get('allow_fetching_of_media_from_remote_urls'))) { return false; }

		// @todo this should probably be dynamic?

		$o_flickr = new Flickr();
		if($o_flickr->canHandle($ps_url)) {
			return $o_flickr;
		}

		$o_file = new File();
		if($o_file->canHandle($ps_url)) {
			return $o_file;
		}

		return false;
	}

	/**
	 * Downloads a given file from a verified URL. The implementation should make sure that the file is safe to load
	 * and transform the incoming url (from @see downloadMediaForProcessing) if needed
	 *
	 * @param string $ps_url
	 * @param string $ps_target
	 * @throws BaseRemoteMediaException
	 */
	protected function downloadFromVerifiedURL($ps_url, $ps_target) {
		if(!((bool)ini_get('allow_url_fopen'))) {
			throw new BaseRemoteMediaException("It looks like allow_url_fopen is set to false. This means CollectiveAccess is not able to download the given file. Please contact your system administrator.");
		}

		$ps_url = html_entity_decode($ps_url);

		if(!isURL($ps_url)) {
			throw new BaseRemoteMediaException("This does not look like a URL");
		}

		$r_incoming_fp = @fopen($ps_url, 'r');
		if(!$r_incoming_fp) {
			throw new BaseRemoteMediaException(_t('Cannot open remote URL [%1] to fetch media', $ps_url));
		}

		$r_outgoing_fp = @fopen($ps_target, 'w');
		if(!$r_outgoing_fp) {
			throw new BaseRemoteMediaException(_t('Cannot open temporary file for media fetched from URL [%1]', $ps_url));
		}

		while(($vs_content = fgets($r_incoming_fp, 4096)) !== false) {
			fwrite($r_outgoing_fp, $vs_content);
		}
		fclose($r_incoming_fp);
		fclose($r_outgoing_fp);
	}

}

class BaseRemoteMediaException extends \Exception {}
