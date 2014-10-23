<?php
/* ----------------------------------------------------------------------
 * MediaReplicator.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

define("__CA_MEDIA_REPLICATION_STATE_PENDING__", 1);	// replication will start
define("__CA_MEDIA_REPLICATION_STATE_UPLOADING__", 2);	// media is being sent
define("__CA_MEDIA_REPLICATION_STATE_PROCESSING__", 3);	// replication target is processing media
define("__CA_MEDIA_REPLICATION_STATE_COMPLETE__", 4);	// replication target has accepted media
define("__CA_MEDIA_REPLICATION_STATE_ERROR__", 5);	// replication failed


	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/core/Media/MediaVolumes.php');
	require_once(__CA_LIB_DIR__.'/core/Media/MediaProcessingSettings.php');

	class MediaReplicator {
		# --------------------------------------------------------------------------------
		private $opo_config;
		static $s_plugin_names;
		# --------------------------------------------------------------------------------
		/**
		 * Initialize 
		 *
		 */
		public function __construct($ps_table=null) {
			$this->opo_config = Configuration::load();
		}
		# --------------------------------------------------------------------------------
		/**
		 * 
		 *
		 * @param string $ps_field
		 * @param 
		 */
		public function replicateMedia($ps_filepath, $pa_target_info, $pa_data, $pa_options=null) {
			if(!($o_plugin = $this->getMediaReplicationPlugin($pa_target_info['type']))) {
				throw new Exception(_t("Replication plugin %1 does not exist", $pa_target_info['type']));
			}
			$o_plugin->setTargetInfo($pa_target_info);
			$vs_replication_key = $o_plugin->initiateReplication($ps_filepath, $pa_data, $pa_options);
			
			return $vs_replication_key;
		}
		# --------------------------------------------------------------------------------
		/**
		 * 
		 *
		 * @param string $ps_field
		 * @param 
		 */
		public function removeMediaReplication($ps_replication_key, $pa_target_info, $pa_options=null) {
			if(!($o_plugin = $this->getMediaReplicationPlugin($pa_target_info['type']))) {
				throw new Exception(_t("Replication plugin %1 does not exist", $pa_target_info['type']));
			}
			
			$o_plugin->setTargetInfo($pa_target_info);
			return $o_plugin->removeReplication($ps_replication_key, $pa_options);
		}
		# --------------------------------------------------------------------------------
		/**
		 * 
		 *
		 * @param string $ps_field
		 * @param 
		 */
		public function getUrl($ps_replication_key, $pa_target_info, $pa_options=null) {
			if(!($o_plugin = $this->getMediaReplicationPlugin($pa_target_info['type']))) {
				throw new Exception(_t("Replication plugin %1 does not exist", $pa_target_info['type']));
			}
			
			return $o_plugin->getUrl($ps_replication_key, $pa_options);
		}
		# --------------------------------------------------------------------------------
		/**
		 * 
		 *
		 * @param string $ps_field
		 * @param 
		 */
		public function getReplicationStatus($pa_target_info, $ps_request_token, $pa_options=null) {
			if(!($o_plugin = $this->getMediaReplicationPlugin($pa_target_info['type']))) {
				throw new Exception(_t("Replication plugin %1 does not exist", $pa_target_info['type']));
			}
			$o_plugin->setTargetInfo($pa_target_info);
			$vn_status = $o_plugin->getReplicationStatus($ps_request_token);
			switch($vn_status) {
				case __CA_MEDIA_REPLICATION_STATUS_COMPLETE__:
					return __CA_MEDIA_REPLICATION_STATE_COMPLETE__;
					break;
				case __CA_MEDIA_REPLICATION_STATUS_ERROR__:
					return __CA_MEDIA_REPLICATION_STATE_ERROR__;
					break;
				case __CA_MEDIA_REPLICATION_STATUS_PROCESSING__:
					return __CA_MEDIA_REPLICATION_STATE_PROCESSING__;
					break;
				default:
					return __CA_MEDIA_REPLICATION_STATE_ERROR__;
					break;	
				
			}
		}
		# --------------------------------------------------------------------------------
		/**
		 * Get list of mimetypes for which replication options are configured
		 *
		 * @return array
		 */
		public static function getMediaReplicationMimeTypes() {
			$o_media_volumes = new MediaVolumes();
			$o_media_processing = new MediaProcessingSettings('ca_object_representations', 'media');
			
			$va_volumes = $o_media_volumes->getAllVolumeInformation();
			
			$va_mimetypes = array();
			foreach($va_volumes as $vs_volume => $va_volume_info) {
				if (isset($va_volume_info['replication']) && is_array($va_volume_info['replication'])) {
					foreach($va_volume_info['replication'] as $vs_target => $va_target_info) {
						if (isset($va_target_info['mimetypes']) && is_array($va_target_info['mimetypes'])) {
							$va_mimetype_list = $va_target_info['mimetypes'];
						} else {
							// get mimetypes for volume from media_processing
							$va_mimetype_list = $o_media_processing->getMimetypesForVolume($vs_volume);
						}
						foreach($va_mimetype_list as $vs_mimetype) {
							$va_mimetypes[$vs_mimetype] = true;
						}
					}
				}
			}
			
			return array_keys($va_mimetypes);
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns list of available visualization plugins
		 *
		 * @return array
		 */
		public static function getAvailableMediaReplicationPlugins() {
			if (is_array(MediaReplicator::$s_plugin_names)) { return MediaReplicator::$s_plugin_names; }
			
			$o_viz = new MediaReplicator();
			
			MediaReplicator::$s_plugin_names = array();
			$r_dir = opendir(__CA_APP_DIR__.'/core/Plugins/MediaReplication');
			while (($vs_plugin = readdir($r_dir)) !== false) {
				if ($vs_plugin == "BaseMediaReplicationPlugin.php") { continue; }
				if (preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*).php$/", $vs_plugin, $va_matches)) {
					MediaReplicator::$s_plugin_names[] = $va_matches[1];
				}
			}
		
			sort(MediaReplicator::$s_plugin_names);
			return MediaReplicator::$s_plugin_names;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns instance of specified plugin, or null if the plugin does not exist
		 *
		 * @param string $ps_plugin_name Name of plugin. The name is the same as the plugin's filename minus the .php extension.
		 *
		 * @return WLPlug BaseMediaReplicatorPlugIn Plugin instance
		 */
		public function getMediaReplicationPlugin($ps_plugin_name) {
			if (preg_match('![^A-Za-z0-9_\-]+!', $ps_plugin_name)) { return null; }
			if (!file_exists(__CA_LIB_DIR__.'/core/Plugins/MediaReplication/'.$ps_plugin_name.'.php')) { return null; }
		
			require_once(__CA_LIB_DIR__.'/core/Plugins/MediaReplication/'.$ps_plugin_name.'.php');
			$vs_plugin_classname = 'WLPlugMediaReplication'.$ps_plugin_name;
			return new $vs_plugin_classname;
		}	
		# --------------------------------------------------------------------------------
	}
?>