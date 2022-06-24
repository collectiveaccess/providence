<?php

namespace Srmklive\Dropbox;

trait ParseResponse
{
    /**
     * Parse response from Dropbox.
     *
     * @param array|\Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function normalizeResponse($response)
    {
        $normalizedPath = ltrim($this->removePathPrefix($response['path_display']), '/');

        $normalizedResponse = ['path' => $normalizedPath];
        $normalizedResponse['timestamp'] = isset($response['server_modified']) ?
            strtotime($response['server_modified']) : null;
        $normalizedResponse['size'] = isset($response['size']) ? $response['size'] : null;
        $normalizedResponse['bytes'] = isset($response['size']) ? $response['size'] : null;

        $type = ($response['.tag'] === 'folder' ? 'dir' : 'file');
        $normalizedResponse['type'] = $type;

        return array_filter($normalizedResponse);
    }
}
