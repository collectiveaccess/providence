<?php
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
		try {
			$session = MediaUploadManager::findSession($session_key);
			if((int)$session->get('cancelled') == 1) {
				throw new MediaUploadManageSessionException('Upload has been cancelled');
			}
			if(strlen($session->get('completed_on')) > 0) {
				throw new MediaUploadManageSessionException('Upload is complete. No further data can be accepted.');
			}
		} catch (MediaUploadSessionDoesNotExistException $e) {
			throw new MediaUploadManageSessionException('Invalid session key: '.$session_key);
		}
		
		// Check available storage
        if (rand(0,100) === 50) {
			$stats = caGetUserMediaStorageUsageStats();
		
			if ($stats['storageUsage'] > $stats['storageAvailable']) {
				throw new MediaUploadManageSessionException('User storage quota exceeded');
			}
		
			unset($stats['storageUsage']);
			$h = array_merge($response->getHeaders(), $stats);
		
        	$response->setHeaders($h);
        }
        
        
    }
}