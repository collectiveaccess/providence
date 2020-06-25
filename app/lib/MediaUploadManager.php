<?php
/** ---------------------------------------------------------------------
 * app/lib/MediaUploadManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 * @subpackage Auth
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 require_once(__CA_MODELS_DIR__."/ca_media_upload_sessions.php");


class MediaUploadManager {
	# ------------------------------------------------------
    /**
     * Configuration instance
     */
    private $config;
    
    /**
     * Logging instance for upload log (usually set to application log)
     */
    private $log;

    /**
     *
     */
    static $s_user_cache = [];

    
    # ------------------------------------------------------
    /**
     *
     */
    public function __construct($options=null) {
        $this->config = Configuration::load();
        $this->log = caGetLogger(['logLevel' => caGetOption('logLevel', $options, null)], 'media_uploader_log_directory');;
    }	
    # ------------------------------------------------------
    /**
     *
     */
    static public function newSession($user, $num_files, $size) {
        $user_id = self::_getUserID($user);
    	if ($s = self::findSession($key, $user_id)) {
    	    return $s;
    	}
    	$s = new ca_media_upload_sessions();
    	$s->set([
    		'session_key' => caGenerateGUID(),
    		'user_id' => $user_id,
    		'cancelled' => 0,
    		'num_files' => $num_files,
    		'total_bytes' => $size
    	]);
    	if ($s->insert()) {
    		return $s;
    	}
    	throw new MediaUploadManageSessionException(_t('Could not create media upload session: '.join('; ', $s->getErrors())));
    }
    # ------------------------------------------------------
    /**
     *
     */
    static public function findSession($key) {
        if(!$key) { return null; }

        if ($s = ca_media_upload_sessions::findAsInstance(['session_key' => $key])) {
            return $s;
        }
        return null;
    }
    # ------------------------------------------------------
        /**
         * Get recent uploads for user
         *
         *
         */
        static public function getRecent(array $options) {
            $user = caGetOption('user', $options, null);
            $limit = caGetOption('limit', $options, 10);

            $sessions = [];
            if($user_id = self::_getUserID($user)) {
	            if ($sessions = ca_media_upload_sessions::find(['user_id' => $user_id], ['returnAs' => 'arrays'])) {
 		            $user_dir_path = MediaUploadManager::getMediaPathForUser($user_id);

	                $sessions = array_reverse(caSortArrayByKeyInValue($sessions, ['created_on']));
	                if ($limit > 0) {
	                    $sessions = array_slice($sessions, 0, $limit);
	                }
	                $sessions = array_map(function($s) use ($user_dir_path) {
	                    $files = caUnSerializeForDatabase($s['progress']);
	                    $files_proc = [];
	                    if(is_array($files)) {
		                    foreach($files as $p => $info) {
		                        $px = str_replace("{$user_dir_path}/", "", $p);
		                        $files_proc[$px] = $info;
		                    }
		                }
	                    $s['files'] = $files_proc;

	                    foreach(['created_on', 'completed_on', 'last_activity_on'] as $f) {
	                        $s[$f] = ($s[$f] > 0) ? caGetLocalizedDate($s[$f], ['dateFormat' => 'delimited']) : null;
	                    }

	                    unset($s['user_id']);
	                    unset($s['progress']);

	                    return $s;
	                }, $sessions);

	            }
	        }

            return array_values($sessions);
        }
    # ------------------------------------------------------
    /**
     *
     */
    static private function _getUserID($user) {
        if (array_key_exists($user, self::$s_user_cache)) {
            return self::$s_user_cache[$user];
        }
         if(is_numeric($user)) {
			if ($u = ca_users::find(['user_id' => $user], ['returnAs' => 'firstModelInstance'])) {
				return self::$s_user_cache[$user] = $u->getPrimaryKey();
			}
         }

        if (!($u = ca_users::findAsInstance(['user_name' => $user]))) {
            $u = ca_users::findAsInstance(['email' => $user]);
        }
        if($u && $u->isLoaded()) {
            return self::$s_user_cache[$user] = $u->getPrimaryKey();
        } else {
            throw new MediaUploadManageSessionException(_t('Invalid user_id'));
        }
    }
    # ------------------------------------------------------
    /**
     *
     */
    static public function getMediaPathForUser($user) {
        if(!($user_id = self::_getUserID($user))) { return null; }
        $config = Configuration::load();
        return $config->get('media_uploader_root_directory')."/userMedia".$user_id;
    }
    # ------------------------------------------------------
}

class MediaUploadManageSessionException extends Exception {}
