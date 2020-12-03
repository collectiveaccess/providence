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

        if (isset($response['server_modified'])) {
            $normalizedResponse['timestamp'] = strtotime($response['server_modified']);
        }

        if (isset($response['size'])) {
            $normalizedResponse['size'] = $response['size'];
            $normalizedResponse['bytes'] = $response['size'];
        }

        $type = ($response['.tag'] === 'folder' ? 'dir' : 'file');
        $normalizedResponse['type'] = $type;

        return $normalizedResponse;
    }
}
