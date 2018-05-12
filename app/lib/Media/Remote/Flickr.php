<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/Remote/Flickr.php
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

require_once(__CA_LIB_DIR__.'/core/Media/Remote/Base.php');
require_once(__CA_LIB_DIR__.'/core/Flickr/phpFlickr.php');

class Flickr extends Base {

	/**
	 * @param string $ps_url
	 * @param string $ps_target_location
	 * @throws FlickrException
	 */
	public function downloadMediaForProcessing($ps_url, $ps_target_location) {
		if(!($vs_flickr_api_key = \Configuration::load()->get('flickr_api_key'))) {
			throw new FlickrException('No Flickr API key defined in app.conf');
		}

		$va_parts = $this->parse($ps_url);
		$o_f = new \phpFlickr($vs_flickr_api_key);
		$va_sizes = $o_f->photos_getSizes($va_parts['flickr_id']);

		if(!is_array($va_sizes)) {
			throw new FlickrException(_t('Could not get image list for given Flickr ID %1', $va_parts['flickr_id']));
		}

		$va_last_part = array_pop($va_sizes);

		if(!isset($va_last_part['source'])) {
			throw new FlickrException(_t('Could not get source image for Flickr ID %1', $va_parts['flickr_id']));
		}

		$this->downloadFromVerifiedURL($va_last_part['source'], $ps_target_location);
	}

	/**
	 * Determines if this plugin can handle the given URL
	 * @param string $ps_url
	 * @return bool
	 */
	public function canHandle($ps_url) {
		if(!\Configuration::load()->get('flickr_api_key')) {
			return false;
		}

		$va_parts = $this->parse($ps_url);
		if(is_array($va_parts)) {
			return true;
		} else {
			return false;
		}
	}

	private function parse($ps_url) {
		$va_parts = parse_url($ps_url);
		$va_parts['path'] = preg_replace('!/+!u', '/', $va_parts['path']);

		if(!preg_match("/flickr\.com$/ui", $va_parts['host'])) {
			return false;
		}

		if(!preg_match("/^\/photos/u", $va_parts['path'])) {
			return false;
		}

		// extract ID
		$va_matches = [];
		if(preg_match("!/([0-9]+)/?$!u", $va_parts['path'], $va_matches)) {
			$va_parts['flickr_id'] = $va_matches[1];
		} else {
			return false;
		}

		return $va_parts;
	}

	public function getOriginalFilename($ps_url) {
		if(!($vs_flickr_api_key = \Configuration::load()->get('flickr_api_key'))) {
			throw new FlickrException('No Flickr API key defined in app.conf');
		}

		$va_parts = $this->parse($ps_url);
		$o_f = new \phpFlickr($vs_flickr_api_key);
		$va_info = $o_f->photos_getInfo($va_parts['flickr_id']);

		if(!is_array($va_info)) {
			throw new FlickrException(_t('Could not get image info for given Flickr ID %1', $va_parts['flickr_id']));
		}

		if(!isset($va_info['photo']['title']['_content'])) {
			throw new FlickrException(_t('Could not get title for given Flickr ID %1', $va_parts['flickr_id']));
		}

		return $va_info['photo']['title']['_content'];
	}
}

class FlickrException extends \Exception {}
