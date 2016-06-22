<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/MediaViewerManager.php :
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

/**
 *
 */
 
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 
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
			MediaViewerManager::$s_media_viewer_plugin_dir = __CA_LIB_DIR__.'/core/Media/MediaViewers';	// set here for compatibility with PHP 5.5 and earlier
			
			if (MediaViewerManager::$s_manager_did_do_init) { return true; }
			
			MediaViewerManager::$s_media_viewers = MediaViewerManager::getViewerNames();
			MediaViewerManager::$s_manager_did_do_init = true;
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public static function viewerIsAvailable($ps_viewer_name) {
			MediaViewerManager::initViewers();
			if (in_array($ps_viewer_name, MediaViewerManager::$s_media_viewers)) {
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
			
			$va_media_viewers = [];
			if (is_resource($r_dir = opendir(MediaViewerManager::$s_media_viewer_plugin_dir))) {
				while (($vs_plugin = readdir($r_dir)) !== false) {
					$vs_plugin_proc = str_replace(".php", "", $vs_plugin);
					if (preg_match("/^[A-Za-z_]+[A-Za-z0-9_]*$/", $vs_plugin_proc)) {
						require_once(MediaViewerManager::$s_media_viewer_plugin_dir."/".$vs_plugin);
						$va_media_viewers[] = $vs_plugin_proc;
					}
				}
			}
			
			sort($va_media_viewers);
			
			return $va_media_viewers;
		}
		# ----------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerForMimetype($ps_context, $ps_mimetype) {
			$va_info = caGetMediaDisplayInfo($ps_context, $ps_mimetype);
			if (!isset($va_info['viewer']) || !($vs_viewer = $va_info['viewer'])) { 
				$vs_viewer = caGetDefaultMediaViewer($ps_mimetype);
			}
			if (!$vs_viewer) { return null; }
			
			return MediaViewerManager::viewerIsAvailable($vs_viewer) ? $vs_viewer : null;
		} 
		# ----------------------------------------------------------
	}