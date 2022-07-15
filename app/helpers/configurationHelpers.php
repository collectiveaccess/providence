<?php
/** ---------------------------------------------------------------------
 * app/helpers/configurationHelpers.php : utility functions for setting database-stored configuration values
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2021 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

# ----------------------------------------------------------------
/**
  * Returns a sorted list of profiles. Keys are display names and values are profile codes (filename without .xml extension).
  *
  * @param string $install_dir_prefix optional prefix for install dir
  * @return array List of available profiles
  */
function caGetAvailableProfiles(string $install_dir_prefix='.') {
	$files = caGetDirectoryContentsAsList($install_dir_prefix.'/profiles', true);
	$profiles = array();
	
	foreach($files as $filepath) {
		if (preg_match("!\.(xml|xlsx)$!", $filepath)) {
			$tmp = explode('/', $filepath);
			$tmp2 = explode('.', array_pop($tmp));
			$file = array_shift($tmp2);
			$profile_info = \Installer\Installer::getProfileInfo($install_dir_prefix.'/profiles', $file);
			if (!$profile_info['useForConfiguration']) { continue; }
			$profiles[strip_tags($profile_info['display'])] = $file; 
		}
	}
	
	ksort($profiles, SORT_NATURAL);
	return $profiles;
}
# --------------------------------------------------
/**
 * Return path to profile based upon profile name and root directory
 *
 * @param string $directory path to a directory containing profiles and XML schema
 * @param string $profile of the profile, as in <$directory>/xml/<$profile>.xml
 *
 * @return string Return path to profile, or null if profile was not found
 */
function caGetProfilePath(string $directory, string $profile) : ?string {
	$files = caGetDirectoryContentsAsList($directory);
	
	$possible_match = null;
	foreach($files as $f) {
		$filename = pathinfo($f, PATHINFO_FILENAME);
		$basename = pathinfo($f, PATHINFO_BASENAME);
		
		if($filename === $profile) {
			return $f;
		}
		if($basename === $profile) {
			$possible_match = $f;
		}
	}
	return $possible_match;
}
# ----------------------------------------------------------------
/**
 *
 */
function caFlushOutput() {
	echo str_pad('',4096)."\n";
	@ob_flush();
	flush();
}
# ----------------------------------------------------------------
