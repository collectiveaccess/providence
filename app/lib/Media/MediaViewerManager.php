<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/MediaViewerManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2023 Whirl-i-Gig
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

class MediaViewerManager {
	# -------------------------------------------------------
	/** 
	 * @var Global flag indicating whether we've required() viewer plugins yet
	 */
	static $s_manager_did_do_init = false;
	
	/** 
	 * 
	 */
	static $s_media_viewer_plugin_dir;
	
	/** 
	 * 
	 */
	static $s_media_viewers = [];
	
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	/**
	 * Loads viewers
	 */
	public static function initViewers() {
		MediaViewerManager::$s_media_viewer_plugin_dir = __CA_LIB_DIR__.'/Media/MediaViewers';	// set here for compatibility with PHP 5.5 and earlier
		
		if (MediaViewerManager::$s_manager_did_do_init) { return true; }
		
		MediaViewerManager::$s_media_viewers = array_map("strtolower", MediaViewerManager::getViewerNames());
		MediaViewerManager::$s_manager_did_do_init = true;
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public static function viewerIsAvailable(string $viewer_name) {
		MediaViewerManager::initViewers();
		if (in_array(strtolower($viewer_name), MediaViewerManager::$s_media_viewers)) {
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Returns names of all media viewers
	 */
	public static function getViewerNames() {
		if(!file_exists(MediaViewerManager::$s_media_viewer_plugin_dir)) { return array(); }
		
		$media_viewers = [];
		if (is_resource($r_dir = opendir(MediaViewerManager::$s_media_viewer_plugin_dir))) {
			while (($plugin = readdir($r_dir)) !== false) {
				$plugin_proc = str_replace(".php", "", $plugin);
				if (preg_match("/^[A-Za-z_]+[A-Za-z0-9_]*$/", $plugin_proc)) {
					require_once(MediaViewerManager::$s_media_viewer_plugin_dir."/".$plugin);
					$media_viewers[] = $plugin_proc;
				}
			}
		}
		
		sort($media_viewers);
		
		return $media_viewers;
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public static function getViewerForMimetype(string $context, ?string $mimetype, ?array $options=null) {
		if(!$mimetype) { return null; }
		$config = Configuration::load(__CA_CONF_DIR__.'/media_display.conf');
		if(caGetOption('alwaysUseCloverViewer', $options, (bool)$config->get('always_use_clover_viewer'))) {
			$viewer = 'Clover';
		} else {
			$info = caGetMediaDisplayInfo($context, $mimetype);
			if (!isset($info['viewer']) || !($viewer = $info['viewer'])) { 
				$viewer = caGetDefaultMediaViewer($mimetype);
			}
			if (!$viewer) { return null; }
		}
		return MediaViewerManager::viewerIsAvailable($viewer) ? $viewer : null;
	} 
	# ----------------------------------------------------------
}
