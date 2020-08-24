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
    static public function findSession($key, $user_id=null) {
        if(!$key) { throw new MediaUploadSessionDoesNotExistException(_t('Empty session')); }

        if ($s = ca_media_upload_sessions::findAsInstance(['session_key' => $key])) {
        	if ($user_id && ($s->get('user_id') != $user_id)) { 
        		throw new MediaUploadSessionDoesNotExistException(_t('Session not found'));
        	}
            return $s;
        }
        throw new MediaUploadSessionDoesNotExistException(_t('Session not found'));
    }
    # ------------------------------------------------------
    /**
     *
     */
    static public function completeSession($key, $user_id=null){
		$s = MediaUploadManager::findSession($key, $user_id);
		
		$s->set('completed_on', _t('now'));
		$s->update();
		if ($s->numErrors() > 0) {
			throw new MediaUploadManageSessionException(_t('Could not complete media upload session: '.join('; ', $s->getErrors())));
		}
        return $s;
    }
    # ------------------------------------------------------
    /**
     *
     */
    static public function cancelSession($key, $user_id=null){
		$s = MediaUploadManager::findSession($key, $user_id);
		
		$s->set('completed_on', _t('now'));
		$s->set('cancelled', 1);
		$s->update();
		if ($s->numErrors() > 0) {
			throw new MediaUploadManageSessionException(_t('Could not cancel media upload session: '.join('; ', $s->getErrors())));
		}
        return $s;
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

       	return self::getLog(['user' => $user, 'limit' => $limit]);
    }
    # ------------------------------------------------------
    /**
     * Get upload log
     *
     *
     */
    static public function getLog(array $options) {
        $user = caGetOption('user', $options, null);
        $status = caGetOption('status', $options, null, ['forceUppercase' => true]);
        $date = caGetOption('date', $options, null);
        $limit = caGetOption('limit', $options, 10);

        $user_id = $user ? self::_getUserID($user) : null;
        
        $sessions = [];
        $params = [];
        
        if($user_id) { $params['user_id'] = $user_id; }
        if($date && ($d = caDateToUnixTimestamps($date))) {
        	$params['created_on'] = ['BETWEEN', [$d['start'], $d['end']]]; 
        }
        if (!sizeof($params)) { $params = '*'; }
        
		if ($sessions = ca_media_upload_sessions::find($params, ['returnAs' => 'arrays'], ['sort' => 'created_on', 'sortDirection' => 'desc'])) {
			if (!($user_dir_path = caGetMediaUploadPathForUser($user_id))) {
				$user_dir_path = caGetUserMediaUploadPath(); 
			}
			$sessions = array_reverse(caSortArrayByKeyInValue($sessions, ['created_on']));
			if ($limit > 0) {
				$sessions = array_slice($sessions, 0, $limit);
			}
			$sessions = array_map(function($s) use ($user_dir_path) {
				$files = caUnSerializeForDatabase($s['progress']);
				$files_proc = [];
				$s['user'] = $u = ca_users::userInfoFor($s['user_id']);
				
				if(is_array($files)) {
					foreach($files as $p => $info) {
						$px = str_replace("{$user_dir_path}/~{$u['user_name']}/", "", $p);
						$px = str_replace("{$user_dir_path}/", "", $px);
						$files_proc[$px] = $info;
					}
				}
				
				$s['files'] = $files_proc;

				foreach(['created_on', 'completed_on', 'last_activity_on'] as $f) {
					$s[$f] = ($s[$f] > 0) ? caGetLocalizedDate($s[$f], ['dateFormat' => 'delimited']) : null;
				}
				if ($s['cancelled']) {
					$s['status'] = 'CANCELLED';
					$s['status_display'] = _t('Cancelled');
				} elseif ($s['completed_on']) {
					$s['status'] = 'COMPLETED';
					$s['status_display'] = _t('Completed');
				} elseif ($s['last_activity_on']) {
					$s['status'] = 'IN_PROGRESS';
					$s['status_display'] = _t('In progress');
				} elseif ($s['last_activity_on']) {
					$s['status'] = 'UNKNOWN';
					$s['status_display'] = _t('Unknown');
				}

				unset($s['user_id']);
				unset($s['progress']);

				return $s;
			}, $sessions);
			
			if($status) {
				$sessions = array_filter($sessions, function($v) use ($status) {
					return $v['status'] === $status;
				});
			}

		}

        return array_values($sessions);
    }
    # ------------------------------------------------------
    /**
     * Get list of users who have performed uploads
     *
     */
    static public function getUserList(array $options=null) {
       $db = new Db();
       $qr = $db->query("
			SELECT DISTINCT u.user_id, u.user_name, u.fname, u.lname, u.email
			FROM ca_media_upload_sessions s
			INNER JOIN ca_users AS u ON u.user_id = s.user_id
			ORDER BY u.lname, u.fname
		");
		$users = [];
		while($qr->nextRow()) {
			$users[$qr->get('user_id')] = $qr->getRow();
		}
        return $users;
    }
    # ------------------------------------------------------
    /**
     *
     */
    static private function _getUserID($user) {
    	if ($user_id = ca_users::userIDFor($user)) {
    		return $user_id;
	    }
	    throw new MediaUploadManageSessionException(_t('Invalid user_id'));
    }
    # ------------------------------------------------------
}

class MediaUploadManageSessionException extends Exception {}
class MediaUploadSessionDoesNotExistException extends Exception {}
