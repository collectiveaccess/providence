<?php
use TusPhp\Request;
use TusPhp\Response;
use TusPhp\Middleware\TusMiddleware;

class MediaUploaderHandler implements TusMiddleware {

    /**
     *
     */
    public function handle(Request $request, Response $response) {
        // TODO: Add checks here?
        
        $stats = caGetUserMediaStorageUsageStats();
        unset($stats['storageUsage']);
        $h = array_merge($response->getHeaders(), $stats);
        $response->setHeaders($h);
    }
}
