<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SMS/WLPlugMediaReplicationYouTube.php : replicates media to YouTube
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

class WLPlugMediaReplicationYouTube Extends BaseMediaReplicationPlugin Implements IWLPlugMediaReplication {
	# ------------------------------------------------
	/**
	 *
	 */
	private $ops_developer_key = 'AI39si7a1BdgEdu0n6oCCKVwNs0T47OCT8AcDhOYYLduLRyuqfRtAwpCsMXuUCOJ9CzuYgMiHE3qK0OYXc8GhjbA5DI5Br1WFA';
	
	/**
	 *
	 */
	static $s_auth_url = 'https://www.google.com/accounts/ClientLogin';
	static $s_upload_url = 'http://uploads.gdata.youtube.com/feeds/users/default/uploads';
	
	/**
	 *
	 */
	private $opa_target_info;
	
	/**
	 *
	 */
	private $opo_client = null;
	
	
	private $opa_request_list = array();
	# ------------------------------------------------
	/**
	 *
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
	 */
	public function setTargetInfo($pa_target_info, $pa_options=null) {
		$pb_connect = !caGetOption('dontConnect', $pa_options, false);
		$this->opa_target_info = $pa_target_info;
		if ($pb_connect) { 
			return $this->getClient(array('reset')); 
		} else {
			return true;
		}
	}
	# ------------------------------------------------
	/**
	 * @return string Unique request token. The token can be used on subsequent calls to fetch information about the replication request
	 */
	public function initiateReplication($ps_key, $ps_filepath, $pa_data, $pa_options=null) {
		if (!($o_client = $this->getClient())) {
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
		
		// Note that category must be a valid YouTube category !
		$o_video_entry->setVideoCategory($pa_data['category'] ? $pa_data['category'] : 'Movies');

		// Set keywords, note that this must be a comma separated string
		// and that each keyword cannot contain whitespace
		$o_video_entry->SetVideoTags(is_array($pa_data['tags']) ? join(",", $pa_data['tags']) : '');
		
		if (isset($pa_options['private']) && $pa_options['private']) {
			 $o_video_entry->setVideoPrivate();
		}

		// Optionally set some developer tags
		//$o_video_entry->setVideoDeveloperTags(array('Uploaded_by_CollectiveAccess'));

		// Optionally set the video's location
		//$yt->registerPackage('Zend_Gdata_Geo');
		//$yt->registerPackage('Zend_Gdata_Geo_Extension');
		//$where = $yt->newGeoRssWhere();
		//$position = $yt->newGmlPos('37.0 -122.0');
		//$where->point = $yt->newGmlPoint($position);
		//$myVideoEntry->setWhere($where);

		// Try to upload the video, catching a Zend_Gdata_App_HttpException
		// if availableor just a regular Zend_Gdata_App_Exception

		try {
			$o_new_entry = $o_client->insertEntry($o_video_entry,
							 WLPlugMediaReplicationYouTube::$s_upload_url,
							 'Zend_Gdata_YouTube_VideoEntry');
		} catch (Zend_Gdata_App_HttpException $o_http_exception) {
			echo $o_http_exception->getRawResponseBody();
		} catch (Zend_Gdata_App_Exception $e) {
			echo $e->getMessage();
		}
		print "id=".$o_new_entry->getVideoID()."\n\n";
		
		$this->opa_request_list[$o_new_entry->getVideoID()] = $o_video_entry;
		return $o_new_entry->getVideoID();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getReplicationStatus($ps_request_token) {
		if (!($o_client = $this->getClient())) {
			throw new Exception(_t('Could not connect to YouTube'));
		}
		//if (!isset($this->opa_request_list[$ps_request_token])) { print "skip\n";return null; }
		
		$o_video_entry = $o_client->getVideoEntry($ps_request_token);
		//$o_video_entry = $this->opa_request_list[$ps_request_token];
		
		try {
			$o_control = $o_video_entry->getControl();
		} catch (Zend_Gdata_App_Exception $e) {
			echo $e->getMessage();
			print "fail";
		}

		if ($o_control instanceof Zend_Gdata_App_Extension_Control) {
			//if ($o_control->getDraft() != null &&
			//	$o_control->getDraft()->getText() == 'yes') {
				$o_state = $o_video_entry->getVideoState();

				if ($o_state instanceof Zend_Gdata_YouTube_Extension_State) {
					print 'Upload status: '
						  . $o_state->getName()
						  .' '. $o_state->getText();
				} else {
					print 'Not able to retrieve the video status information'
						  .' yet. ' . "Please try again shortly.\n";
				}
			//} else {
			//	print "Nothing yet\n";
			//}
		} else {
			print "no control"; print_R($o_control);
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getReplicationErrors($ps_request_token) {
	
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getReplicationInfo($ps_request_token) {
	
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function removeReplication($ps_key, $pa_options=null) {
	
	}
	# ------------------------------------------------
	/**
	 *
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
		
		//$this->opo_client->setMajorProtocolVersion(2);
		
    	return $this->opo_client;
	}
	# ------------------------------------------------
}
?>