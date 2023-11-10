<?php

namespace Phpoaipmh\HttpAdapter;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Phpoaipmh\Exception\HttpException;

/**
 * GuzzleAdapter HttpAdapter HttpAdapterInterface Adapter (works with v5 or v6/7)
 *
 * @package Phpoaipmh\HttpAdapter
 */
class GuzzleAdapter implements HttpAdapterInterface
{
    /**
     * @var GuzzleClient
     */
    private $guzzle;

    /**
     * Constructor
     *
     * @param GuzzleClient $guzzle
     */
    public function __construct(GuzzleClient $guzzle = null)
    {
        $this->guzzle = $guzzle ?: new GuzzleClient();
    }

    /**
     * Get the Guzzle Client
     *
     * @return GuzzleClient
     */
    public function getGuzzleClient()
    {
        return $this->guzzle;
    }

    /**
     * Do the request with GuzzleAdapter
     *
     * @param  string        $url
     * @return string
     * @throws HttpException
     */
    public function request($url)
    {
        try {
            $resp = $this->guzzle->get($url);
            return (string) $resp->getBody();
        } catch (RequestException $e) {
            $response = $e->getResponse();
            throw new HttpException($response ? $response->getBody() : null, $e->getMessage(), $e->getCode(), $e);
        } catch (TransferException $e) {
            throw new HttpException('', $e->getMessage(), $e->getCode(), $e);
        }
    }
}
