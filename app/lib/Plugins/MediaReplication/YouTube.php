<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/MediaReplication/WLPlugMediaReplicationYouTube.php : replicates media to YouTube
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
 * @package CollectiveAccess
 * @subpackage SMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Parsers/getid3/getid3.php");
include_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMediaReplication.php");
include_once(__CA_LIB_DIR__."/core/Plugins/MediaReplication/BaseMediaReplicationPlugin.php");
include_once(__CA_LIB_DIR__."/core/Zend/Gdata.php");
include_once(__CA_LIB_DIR__."/core/Zend/Gdata/ClientLogin.php");
include_once(__CA_LIB_DIR__."/core/Zend/Gdata/YouTube.php");

class WLPlugMediaReplicationYouTube Extends BaseMediaReplicationPlugin {
	# ------------------------------------------------
	/**
	 *
	 */
	private $ops_developer_key = 'AI39si7a1BdgEdu0n6oCCKVwNs0T47OCT8AcDhOYYLduLRyuqfRtAwpCsMXuUCOJ9CzuYgMiHE3qK0OYXc8GhjbA5DI5Br1WFA';
	
	/**
	 * Google API urls
	 */
	static $s_auth_url = 'https://www.google.com/accounts/ClientLogin';
	static $s_upload_url = 'http://uploads.gdata.youtube.com/feeds/users/default/uploads';
	
	/**
	 * 
	 */
	private $opa_target_info;
	
	/**
	 * Zend_Gdata_YouTube client used to interact with YouTube
	 */
	private $opo_client = null;
	
	
	private $opa_request_list = array();
	# ------------------------------------------------
	/**
	 *
	 * @param array $pa_target_info
	 */
	public function __construct($pa_target_info=null) {
		parent::__construct();
		$this->info['NAME'] = 'YouTube';
		
		$this->description = _t('Replicates media to YouTube using the YouTube API');
		
		if ($pa_target_info) {
			$this->setTargetInfo($pa_target_info);
		}
	}
	# ------------------------------------------------
	/**
	 *
	 * @param array $pa_target_info
	 * @param array $pa_options
	 * @return bool
	 */
	public function setTargetInfo($pa_target_info, $pa_options=null) {
		$pb_connect = !caGetOption('dontConnect', $pa_options, false);
		$this->opa_target_info = $pa_target_info;
		if ($pb_connect) { 
			return $this->getClient(array('reset' => true)); 
		} else {
			return true;
		}
	}
	# ------------------------------------------------
	/**
	 *
	 * @param string $ps_filepath
	 * @param array $pa_data
	 * @param array $pa_options
	 * @return string Unique request token. The token can be used on subsequent calls to fetch information about the replication request
	 */
	public function initiateReplication($ps_filepath, $pa_data, $pa_options=null) {
		if (!($o_client = $this->getClient($pa_options))) {
			throw new Exception(_t('Could not connect to YouTube'));
		}
		$va_path_info = pathinfo($ps_filepath);
		
		$o_video_entry = new Zend_Gdata_YouTube_VideoEntry();
		
		$o_filesource = $o_client->newMediaFileSource($ps_filepath);
		
		$ID3 = new getID3();
		$ID3->option_max_2gb_check = false;
		$va_info = $ID3->analyze($ps_filepath);
		
		$o_filesource->setContentType($va_info['mime_type']);
		$o_filesource->setSlug($va_path_info['filename'].'.'.$va_path_info['extension']);

		$o_video_entry->setMediaSource($o_filesource);

		$o_video_entry->setVideoTitle(isset($pa_data['title']) ? $pa_data['title'] : $va_path_info['filename']);
		$o_video_entry->setVideoDescription(($pa_data['description']) ? $pa_data['description'] : '');
		
		// Note that category must be a valid YouTube category!
		$o_video_entry->setVideoCategory($pa_data['category'] ? $pa_data['category'] : 'Movies');

		// Set keywords, note that this must be a comma separated string
		// and that each keyword cannot contain whitespace
		$o_video_entry->SetVideoTags(is_array($pa_data['tags']) ? join(",", $pa_data['tags']) : '');
		
		if (isset($pa_options['private']) && $pa_options['private']) {
			 $o_video_entry->setVideoPrivate();
		}

		// This may throw an exception
		$o_new_entry = $o_client->insertEntry($o_video_entry,
						 WLPlugMediaReplicationYouTube::$s_upload_url,
						 'Zend_Gdata_YouTube_VideoEntry');
		
		$this->opa_request_list[$o_new_entry->getVideoID()] = array('entry' => $o_video_entry, 'errors' => array());
		return $this->info['NAME']."://".$o_new_entry->getVideoID();
	}
	# ------------------------------------------------
	/**
	 *
	 * @param string $ps_request_token
	 * @param string $pa_options
	 * @return int
	 */
	public function getReplicationStatus($ps_request_token, $pa_options=null) {
		if (!($o_client = $this->getClient($pa_options))) {
			throw new Exception(_t('Could not connect to YouTube'));
		}
		$ps_request_token = preg_replace("!^".$this->info['NAME']."://!", "", $ps_request_token); // remove plugin identifier
		
		$this->opa_request_list[$ps_request_token]['errors'] = array();
		
		$o_video_entry = $o_client->getVideoEntry($ps_request_token);

		$o_state = $o_video_entry->getVideoState();
		
		$vs_state = ($o_state instanceof Zend_Gdata_YouTube_Extension_State) ? $o_state->getName() : null;
		
		switch($vs_state) {
			case 'processing':
				return __CA_MEDIA_REPLICATION_STATUS_PROCESSING__;
				break;
			case 'rejected':
				$this->opa_request_list[$ps_request_token]['errors'][$o_state->getText()]++;
				return __CA_MEDIA_REPLICATION_STATUS_ERROR__;
				break;
				
			default:
				if ($o_video_entry->getVideoWatchPageUrl()) {
					return __CA_MEDIA_REPLICATION_STATUS_COMPLETE__;
				} else {
					$this->opa_request_list[$ps_request_token]['errors'][_t("Unknown error")]++;
					return __CA_MEDIA_REPLICATION_STATUS_ERROR__;
				}
				break;
		}
		
		return __CA_MEDIA_REPLICATION_STATUS_UNKNOWN__;
	}
	# ------------------------------------------------
	/**
	 *
	 * @param string $ps_request_token
	 * @return array
	 */
	public function getReplicationErrors($ps_request_token) {
		$ps_request_token = preg_replace("!^".$this->info['NAME']."://!", "", $ps_request_token); // remove plugin identifier
		if ($this->getReplicationStatus($ps_request_token) == __CA_MEDIA_REPLICATION_STATUS_ERROR__) {
			return is_array($va_errors = $this->opa_request_list[$ps_request_token]['errors']) ? $va_errors : array();
		}
		return array();
	}
	# ------------------------------------------------
	/**
	 *
	 * @param string $ps_request_token
	 * @param array $pa_options
	 * @return array
	 */
	public function getReplicationInfo($ps_request_token, $pa_options=null) {
		if (!($o_client = $this->getClient($pa_options))) {
			throw new Exception(_t('Could not connect to YouTube'));
		}
		$ps_request_token = preg_replace("!^".$this->info['NAME']."://!", "", $ps_request_token); // remove plugin identifier
		$this->opa_request_list[$ps_request_token]['errors'] = array();
		
		$o_video_entry = $o_client->getVideoEntry($ps_request_token);
		
		return array(
			'id' => $o_video_entry->getVideoId(),
			'title' => $o_video_entry->getVideoTitle(),
			'description' => $o_video_entry->getVideoDescription(),
			'viewCount' => $o_video_entry->getVideoViewCount(),
			'pageUrl' => $o_video_entry->getVideoWatchPageUrl(),
			'playUrl' => $o_video_entry->getFlashPlayerUrl()
		);
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function removeReplication($ps_request_token, $pa_options=null) {
		if (!($o_client = $this->getClient($pa_options))) {
			throw new Exception(_t('Could not connect to YouTube'));
		}
		
		$this->opa_request_list[$ps_request_token]['errors'] = array();
		$ps_request_token = preg_replace("!^".$this->info['NAME']."://!", "", $ps_request_token); // remove plugin identifier
		
		$o_video_entry = $o_client->getVideoEntry($ps_request_token, null, true);
		return $o_client->delete($o_video_entry);	
	}
	# ------------------------------------------------
	/**
	 * Get reference to client used for YouTube interaction
	 *
	 * @param array $pa_options Options are:
	 *			reset = force creation of new client, even if a client is already in place
	 * @return Zend_Gdata_YouTube A YouTube client
	 */
	private function getClient($pa_options=null) {
		if ($vb_reset = (bool)caGetOption('reset', $pa_options, false)) {
			$this->opo_client = null;
		}
		if ($this->opo_client) { return $this->opo_client; }
		
		$o_http_client = 
		  Zend_Gdata_ClientLogin::getHttpClient(
					  $username = $this->opa_target_info['username'],
					  $password = $this->opa_target_info['password'],
					  $service = 'youtube',
					  $client = null,
					  $source = 'CollectiveAccess', 
					  $loginToken = null,
					  $loginCaptcha = null,
					  WLPlugMediaReplicationYouTube::$s_auth_url);
					  
		$this->opo_client = new Zend_Gdata_YouTube($o_http_client,
				 'CollectiveAccess YouTube Replication',
				 null,
				 $this->ops_developer_key);
		
    	return $this->opo_client;
	}
	# ------------------------------------------------
	/**
	 * Return URL allowing access to replicated media
	 *
	 * @param array $pa_options No options defined yet
	 * @return string The URL
	 */
	public function getUrl($ps_key, $pa_options=null) {
		$va_tmp = explode("://", $ps_key);
		if((sizeof($va_tmp) == 2) && (strtolower($va_tmp[0]) == 'youtube')) {
			return "http://www.youtube.com/watch?v=".$va_tmp[1];
		}
		return null;
	}
	# ------------------------------------------------
}
?>