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

        if ($s = ca_media_upload_sessions::findAsInstance(['session_key' => $key], ['noCache' => true])) {
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
				if ($s['cancelled'] > 0) {
					$s['status'] = 'CANCELLED';
					$s['status_display'] = _t('Cancelled');
				} elseif ($s['error_code'] > 0) {
					$s['status'] = 'ERROR';
					$s['status_display'] = _t('Error');
					$s['error_display'] = caGetErrorMessage($s['error_code']);	
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
	static public function getCurrentUploadSessionCount(array $options=null) {
		$params = [
			'completed_on' => null,
			'last_activity_on' => ['>', time() - 15],
			'cancelled' => 0,
		];
		return $c = ca_media_upload_sessions::find($params, ['returnAs' => 'count']) ? $c : 0;
	}
	# ------------------------------------------------------
    /**
     *
     */
	static public function connectionsAvailable(array $options=null) {
		$max_concurrent_uploads = Configuration::load()->get('media_uploader_max_concurrent_uploads');
		
		return (self::getCurrentUploadSessionCount() <= $max_concurrent_uploads);
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
    /**
     * Set up and return TUS server instance for file upload
     *
     * @param int $user_id
     * @return \TusPhp\Tus\Server Server instance
     */
    static public function getTUSServer(int $user_id) {
    	 // Create user directory if it doesn't already exist
		$user_dir_path = caGetMediaUploadPathForUser($user_id);

		// Start up server
		$server = new \TusPhp\Tus\Server('redis');  // TODO: make cache type configurable

		$server->middleware()->add(MediaUploaderHandler::class);
		$server->setApiPath('/batch/MediaUploader/tus')->setUploadDir($user_dir_path);

		$server->event()->addListener('tus-server.upload.progress', function (\TusPhp\Events\TusEvent $event) use ($user_id) {
			$fileMeta = $event->getFile()->details();
			$request  = $event->getRequest();
			$response = $event->getResponse();
			$key = $request->header('x-session-key');

			// ...
			if ($session = MediaUploadManager::findSession($key, $user_id)) {
				$session->set('last_activity_on', _t('now'));
				$progress_data = $session->get('progress');
				
				$fp = self::_partialToFinalPath($fileMeta['file_path']);
			
				$progress_data[$fp]['totalSizeInBytes'] = $fileMeta['size'];
				$progress_data[$fp]['progressInBytes'] = $fileMeta['offset'];
				$progress_data[$fp]['complete'] = false;
				$session->set('progress', $progress_data);
				
				$config = Configuration::load();
				$max_file_size = caParseHumanFilesize($config->get('media_uploader_max_file_size'));
				if(($max_file_size > 0) && (($max_file_size < $fileMeta['size']) || ($max_file_size < $fileMeta['offset']))) {
					$session->set('error_code', 3615); // limit for size of a single file exceeded
				}
				
				$session->update();
			}
		});
		$server->event()->addListener('tus-server.upload.complete', function (\TusPhp\Events\TusEvent $event) use ($user_id) {
			$fileMeta = $event->getFile()->details();
			$request  = $event->getRequest();
			$response = $event->getResponse();
			$key = $request->header('x-session-key');

			$fp = self::_partialToFinalPath($fileMeta['file_path']);
				
			// ...
			if ($session = MediaUploadManager::findSession($key, $user_id)) {
				$session->set('last_activity_on', _t('now'));
				$session->set('progress_files', (int)$session->set('progress_files') + 1);
				$progress_data = $session->get('progress');
				
				
				$progress_data[$fp]['totalSizeInBytes'] = $fileMeta['size'];
				$progress_data[$fp]['progressInBytes'] = $fileMeta['size'];
				$progress_data[$fp]['complete'] = true;
				$session->set('progress', $progress_data);
				
				if (PersistentCache::contains('userStorageStats_'.$user_id, 'mediaUploader')) {
					$stats = PersistentCache::fetch('userStorageStats_'.$user_id, 'mediaUploader');
					$stats['fileCount']++;
				
					$stats['storageUsage'] += $fileMeta['size'];
					$stats['storageUsageDisplay'] = caHumanFilesize($stats['storageUsage']);
				
					PersistentCache::save('userStorageStats_'.$user_id, $stats, 'mediaUploader');
				
					$config = Configuration::load();
					$max_num_files = (int)$config->get('media_uploader_max_files_per_session');
					if (($max_num_files > 0) && (sizeof($progress_data) > $max_num_files)) {
						// exceeded max files per session limit
						$session->set('error_code', 3610);
					}
				}
				$session->update();
			}

			// If subdirectory
			$new_dir_count = 0;
			$rel_path = $fileMeta['metadata']['relativePath'];
			if(isset($rel_path) && strlen($rel_path) && file_exists($fileMeta['file_path'])) {
				$path = pathinfo($fileMeta['file_path'], PATHINFO_DIRNAME);
				$name = pathinfo($fileMeta['file_path'], PATHINFO_BASENAME);

				$rel_path_proc = [];
				foreach(preg_split('!/!', $rel_path) as $rel_path_dir) {
					if(strlen($rel_path_dir = preg_replace('![^A-Za-z0-9\-_]+!', '_', $rel_path_dir))) {
						if(file_exists($rel_path_dir)) { continue; }
						$rel_path_proc[] = $rel_path_dir;
						@mkdir("{$path}/".join("/", $rel_path_proc));
						$new_dir_count++;
					}
				}
				$name_proc = self::_partialToFinalPath($fileMeta['file_path'], ['nameOnly' => true]);
				error_log("copy ".$fileMeta['file_path']);
				error_log("to "."{$path}/".join("/", $rel_path_proc)."/{$name_proc}");
				@rename($fileMeta['file_path'], "{$path}/".join("/", $rel_path_proc)."/{$name_proc}");
			} else {
				@rename($fileMeta['file_path'], $fp);
			}
			
			if ($new_dir_count && PersistentCache::contains('userStorageStats_'.$user_id, 'mediaUploader')) {
				$stats = PersistentCache::fetch('userStorageStats_'.$user_id, 'mediaUploader');
				$stats['directoryCount'] += $new_dir_count;
				PersistentCache::save('userStorageStats_'.$user_id, $stats, 'mediaUploader');
			}
		});
		return $server;
    }
    # ------------------------------------------------------
    /**
     *
     */
    static private function _partialToFinalPath(string $filepath, array $options=null) : string {
    	$basepath = pathinfo($filepath, PATHINFO_DIRNAME);
    	$basename = pathinfo($filepath, PATHINFO_BASENAME);
    	
    	$basename = preg_replace('!^\.{1}!', '', $basename);
    	$basename = preg_replace('!\.part$!', '', $basename);
    	
    	return caGetOption('nameOnly', $options, false) ? $basename : $basepath.'/'.$basename;
    }
    # ------------------------------------------------------
}

class MediaUploadManageSessionException extends Exception {}
class MediaUploadSessionDoesNotExistException extends Exception {}
