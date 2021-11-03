<?php
/** ---------------------------------------------------------------------
 * app/lib/MediaUploadManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2021 Whirl-i-Gig
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
 
 require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");


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
    static public function newSession($user, $num_files, $size, $source='UPLOADER') {
        $user_id = self::_getUserID($user);

    	$s = new ca_media_upload_sessions();
    	$s->set([
    		'session_key' => caGenerateGUID(),
    		'user_id' => $user_id,
    		'cancelled' => 0,
    		'num_files' => $num_files,
    		'status' => 'IN_PROGRESS',
    		'total_bytes' => $size,
    		'source' => $source
    	]);
    	$s->set('source', $source);
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

        if ($s = ca_media_upload_sessions::find(['session_key' => $key], ['noCache' => true, 'returnAs' => 'firstModelInstance'])) {
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
		$s->set('status', 'COMPLETED');
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
		$s->set('status', 'CANCELLED');
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
     */
    static public function getLog(array $options) {
        $user = caGetOption('user', $options, null);
        $status = caGetOption('status', $options, null, ['forceUppercase' => true]);
        $date = caGetOption('date', $options, null);
        $limit = caGetOption('limit', $options, 10);
        $source = caGetOption('source', $options, null, ['forceUppercase' => true]);        
        $form = caGetOption('form', $options, null);

        $user_id = $user ? self::_getUserID($user) : null;
           
        $session_key = caGetOption('sessionKey', $options, null);
        
        $sessions = [];
        $params = [];
        
        if($user_id) { $params['user_id'] = $user_id; }
        if($date && ($d = caDateToUnixTimestamps($date))) {
        	$params['created_on'] = ['BETWEEN', [$d['start'], $d['end']]]; 
        }
        
        if($source) {
        	$params['source'] = ($source === 'FORM') ? 'FORM:%' : $source ;
        } elseif($form) {
        	$params['source'] = 'FORM:'.$form;
        }
        
        if($session_key) {
        	$params['session_key'] = $session_key;
        }
        
        if (!sizeof($params)) { $params = '*'; }
        
    	$t_session = new ca_media_upload_sessions();
		if ($sessions = ca_media_upload_sessions::find($params, ['returnAs' => 'arrays', 'sort' => 'created_on', 'sortDirection' => 'desc', 'allowWildcards' => true])) {
			if (!($user_dir_path = caGetMediaUploadPathForUser($user_id))) {
				$user_dir_path = caGetUserMediaUploadPath(); 
			}
			$sessions = array_reverse(caSortArrayByKeyInValue($sessions, ['created_on']));
			if ($limit > 0) {
				$sessions = array_slice($sessions, 0, $limit);
			}
			
			$importer_config = Configuration::load(__CA_CONF_DIR__.'/importer.conf');
			$importer_forms = $importer_config->get('importerForms');
			
			$sessions = array_map(function($s) use ($user_dir_path, $importer_forms, $t_session) {
				$session = ca_media_upload_sessions::find($s['session_id']);
				$files = $session->getFileList();
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

				foreach(['created_on', 'submitted_on', 'completed_on', 'last_activity_on'] as $f) {
					$s[$f] = ($s[$f] > 0) ? caGetLocalizedDate($s[$f], ['dateFormat' => 'delimited']) : null;
				}
				$s['status_display'] = $t_session->getChoiceListValue('status', $s['status']);
				
				$received_bytes = array_reduce($files_proc, function($c, $i) { return $c + $i['bytes_received']; }, 0);
				$total_bytes = array_reduce($files_proc, function($c, $i) { return $c + $i['total_bytes']; }, 0);

				$s['received_bytes'] = $received_bytes;
				$s['total_bytes'] = $total_bytes;
				
				$s['received_display'] = caHumanFilesize($received_bytes);
				$s['total_display'] = caHumanFilesize($total_bytes);
				
				unset($s['user_id']);
				
				// display?				
				$form = null;
				if(preg_match("!^FORM:(.*)$!", $s['source'], $m)) {
					if(isset($importer_forms[$m[1]]) && is_array($form_info = $importer_forms[$m[1]]) && is_array($form_info['content'])) {
						$disp_template = $form_info['display'];
						$form_data = caUnSerializeForDatabase($s['metadata']);
						if(isset($form_data['data'])) { $form_data = $form_data['data']; }
						unset($s['metadata']);
						if(is_array($form_data) && is_array($form_info['content'])) {
							foreach($form_info['content'] as $k => $v) {
								if ($form_data[$v['bundle']]) {
									$form_data[$k] = $form_data[$v['bundle']];
								}
							}
							$s['label'] = caProcessTemplate($disp_template, $form_data);
						} 
						if(!trim($s['label'])) {
							$s['label'] = _t('[EMPTY]');
						}
					}
				}
				if(!$s['label']) { $s['label'] = _t('[EMPTY]'); }

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
				
				$fp = self::_partialToFinalPath($fileMeta['file_path']);
				
				$session->setFile($fp, [
					'bytes_received' => $fileMeta['offset'], 'total_bytes' => $fileMeta['size'],
					'completed_on' => ($fileMeta['offset'] == $fileMeta['size']) ? _t('now') : null, 'last_activity_on' => _t('now'), 
					'error_code' => null
				]);
				$session->updateStats();
				self::updateStorageStats($user_id, $session, $fileMeta);
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
				
				$session->setFile($fp, [
					'bytes_received' => $fileMeta['offset'], 'total_bytes' => $fileMeta['size'],
					'completed_on' => _t('now'), 'last_activity_on' => _t('now'), 
					'error_code' => null
				]);
				
				$session->updateStats();
				self::updateStorageStats($user_id, $session, $fileMeta);
			
				$config = Configuration::load();
				$max_num_files = (int)$config->get('media_uploader_max_files_per_session');
				if (($max_num_files > 0) && ((int)$session->get('num_files') > $max_num_files)) {
					// exceeded max files per session limit
					$session->set('error_code', 3610);
				}
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
						$rel_path_proc[] = $rel_path_dir;
						if(file_exists("{$path}/".join("/", $rel_path_proc))) { continue; }
						@mkdir("{$path}/".join("/", $rel_path_proc));
						$new_dir_count++;
					}
				}
				$name_proc = self::_partialToFinalPath($fileMeta['file_path'], ['nameOnly' => true]);
				@rename($fileMeta['file_path'], "{$path}/".join("/", $rel_path_proc)."/{$name_proc}");
			} else {
				@rename($fileMeta['file_path'], $fp);
			}
			
			if ($new_dir_count && PersistentCache::contains("userStorageStats_{$user_id}", 'mediaUploader')) {
				$stats = PersistentCache::fetch("userStorageStats_{$user_id}", 'mediaUploader');
				$stats['directoryCount'] += $new_dir_count;
				PersistentCache::save("userStorageStats_{$user_id}", $stats, 'mediaUploader');
			}
		});
		return $server;
    }
    # ------------------------------------------------------
    /**
     *
     */
    static private function updateStorageStats($user_id, $session, $fileMeta) {
    	$stats = PersistentCache::contains("userStorageStats_{$user_id}", 'mediaUploader') 
    		? 
				PersistentCache::fetch("userStorageStats_{$user_id}", 'mediaUploader', ['lock' => true]) 
				: 
				[];
		
		if((int)$fileMeta['offset'] === (int)$fileMeta['size']) {
			$stats['fileCount']++;
			$stats['storageUsage'] += $fileMeta['size'];
			$stats['storageUsageDisplay'] = caHumanFilesize($stats['storageUsage']);
		}
	
		PersistentCache::save("userStorageStats_{$user_id}", $stats, 'mediaUploader');
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
