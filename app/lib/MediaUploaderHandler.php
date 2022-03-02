<?php
/** ---------------------------------------------------------------------
 * app/lib/MediaUploaderHandler.php : 
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
use TusPhp\Request;
use TusPhp\Response;
use TusPhp\Middleware\TusMiddleware;

require_once(__CA_LIB_DIR__."/MediaUploadManager.php");

class MediaUploaderHandler implements TusMiddleware {
    /**
     *
     */
    public function handle(Request $request, Response $response) {
        // Check if upload is valid
        $session_key = $request->header('x-session-key');
        $session = null;
		try {
		 	$session = MediaUploadManager::findSession($session_key);
			if((int)$session->get('cancelled') == 1) {
				throw new MediaUploadManageSessionException(_t('Upload has been cancelled'));
			}
			if($error_num = $session->hasError()) {
				throw new MediaUploadManageSessionException(_t('Error: %1', caGetErrorMessage($error_num)));
			}
		} catch (MediaUploadSessionDoesNotExistException $e) {
			throw new MediaUploadManageSessionException('Invalid session key: '.$session_key);
		}
		
		// Check available storage
		$stats = caGetUserMediaStorageUsageStats(null, ['noCache' => $no_cache = (rand(0,100) === 50)]);
	
		if ($stats['storageUsage'] > $stats['storageAvailable']) {
			$stats = !$no_cache ? caGetUserMediaStorageUsageStats(null, ['noCache' => true]) : $stats;	// check one more time if decision was based upon cached statd
			if ($stats['storageUsage'] > $stats['storageAvailable']) {
				$session = MediaUploadManager::findSession($session_key);
				$session->set('error_code', 3600);
				$session->update();
				throw new MediaUploadManageSessionException('User storage quota exceeded');
			}
		}
	
		unset($stats['storageUsage']);
		$h = array_merge($response->getHeaders(), $stats);
	
		$response->setHeaders($h);
        
        // Check max server connections limit
        if (!MediaUploadManager::connectionsAvailable()) {
        	$session = MediaUploadManager::findSession($session_key);
			$session->set('error_code', 3605);
			$session->update();
			throw new MediaUploadManageSessionException('Server connection limit exceeded, please try again later');
        }       
    }
}
