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
        // TODO: Add checks here?
        
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
