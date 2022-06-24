<?php

namespace Srmklive\Dropbox\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class BadRequest extends Exception
{
    /**
     * BadRequest constructor.
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        if (null !== $body && true === isset($body['error_summary'])) {
            parent::__construct($body['error_summary']);
        }
    }
}
