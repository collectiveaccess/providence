<?php
/* ----------------------------------------------------------------------
 * twitterPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Service/Twitter.php');
 

	
	class twitterPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $opn_last_update_timestamp;
		private $opn_old_access_value;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Publishes newly published records to twitter.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/twitter.conf');
			require_once($ps_plugin_path.'/bitly.php');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the twitterPlugin plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Insert Twitter configuration option into "manage" menu
		 */
		public function hookRenderMenuBar($pa_menu_bar) {
			if ($o_req = $this->getRequest()) {
				//if (!$o_req->user->canDoAction('can_use_media_import_plugin')) { return null; }
				
				if (isset($pa_menu_bar['manage'])) {
					$va_menu_items = $pa_menu_bar['manage']['navigation'];
					if (!is_array($va_menu_items)) { $va_menu_items = array(); }
				} else {
					$va_menu_items = array();
				}
				$va_menu_items['twitter_auth'] = array(
					'displayName' => _t('Twitter integration'),
					"default" => array(
						'module' => 'twitter', 
						'controller' => 'Auth', 
						'action' => 'Index'
					)
				);
				
				$pa_menu_bar['manage']['navigation'] = $va_menu_items;
			} 
			return $pa_menu_bar;
		}
		# -------------------------------------------------------
		/**
		 * Tweet on save of item
		 */
		public function hookBeforeSaveItem(&$pa_params) {
			$t_subject = $pa_params['instance'];	// get instance from params
			$va_tmp = $t_subject->getLastChangeTimestamp();
			$this->opn_last_update_timestamp = (int)$va_tmp['timestamp'];
			
			if (!is_array($va_access_values = $this->opo_config->getList($t_subject->tableName().'_tweet_when_access'))) {
				$va_access_values = array();
			}
			
			$this->opn_old_access_value = $t_subject->get('access');
		}
		# -------------------------------------------------------
		/**
		 * Tweet on save of item
		 */
		public function hookSaveItem(&$pa_params) {
			$t_subject = $pa_params['instance'];	// get instance from params
			$bitly = new bitly('collectiveaccess', 'R_8a0ecd6ea746c58f787d6769329b9976');
			
			// check access
			$va_access_values = $this->opo_config->getList($t_subject->tableName().'_tweet_when_access');
			if (is_array($va_access_values) && sizeof($va_access_values)) {
				if (!in_array($t_subject->get('access'), $va_access_values)) { 
					// Skip record because it is not publicly accessible
					return $pa_params;
				}
			}
			
			// check update threshold (prevents repeated tweeting when a record is repeatedly saved over a short period of time)
			if (($vn_threshold = $this->opo_config->get($t_subject->tableName()."_tweet_update_threshold")) <= 0) {		// seconds
				$vn_threshold = 3600;	// default to one hour if no threshold is specified
			}
			
			$vb_access_changed = ($t_subject->get('access') != $this->opn_old_access_value);
			$this->opn_old_access_value = null;
			
			if (!$vb_access_changed && ((time() - $this->opn_last_update_timestamp) < $vn_threshold)) { return $pa_params; }
			
			// Get Twitter token. If it doesn't exist silently skip posting.
			if ($o_token = @unserialize(file_get_contents(__CA_APP_DIR__.'/tmp/twitter.token'))) { 
				
				try {
					$o_twitter = new Zend_Service_Twitter(array(
						'consumerKey' => $this->opo_config->get('consumer_key'),
						'consumerSecret' => $this->opo_config->get('consumer_secret'),
						'username' => $this->opo_config->get('twitter_username'),
						'accessToken' => $o_token
					));
					 
					// Post to Twitter
					
					$vn_id = $t_subject->getPrimaryKey();
					$vs_url = $this->opo_config->get($t_subject->tableName().'_public_url').$vn_id;
					
					$vs_url_bitly = $bitly->shorten($vs_url);
					$vs_tweet = $this->opo_config->get(($pa_params['is_insert']) ? $t_subject->tableName().'_new_message' : $t_subject->tableName().'_updated_message');
					
					if ($vs_tweet) {
						// substitute tags
						$vs_tweet = caProcessTemplate($vs_tweet, array('BITLY_URL' => $vs_url_bitly, 'URL' => $vs_url, 'ID' => $vn_id), array('getFrom' => $t_subject, 'delimiter' => ', '));
						
						if (mb_strlen($vs_tweet) > 140) { $vs_tweet = mb_substr($vs_tweet, 0, 140); }
						$o_response = $o_twitter->status->update($vs_tweet);
					}
				} catch (Exception $e) {
					// Don't post error to user - Twitter failing is not a critical error
					// But let's put it in the event log so you have some chance of knowing what's going on
					//print "Post to Twitter failed: ".$e->getMessage();
					$o_log = new Eventlog();
					$o_log->log(array(
						'SOURCE' => 'twitter plugin',
						'MESSAGE' => _t('Post to Twitter failed: %1', $e->getMessage()),
						'CODE' => 'ERR')
					);
				}
			}
			return $pa_params;
		}
		# -------------------------------------------------------
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------


	}

?>