<?php

namespace Phpoaipmh\HttpAdapter;

use Phpoaipmh\Exception\HttpException;

/**
 * CurlAdapter HttpAdapter HttpAdapterInterface Adapter
 *
 * @package Phpoaipmh\HttpAdapter
 */
class CurlAdapter implements HttpAdapterInterface
{
    /**
     * @var array  CURL Options
     */
    private $curlOpts = [
        CURLOPT_RETURNTRANSFER    => true,
        CURLOPT_CONNECTTIMEOUT    => 10,
        CURLOPT_DNS_CACHE_TIMEOUT => 10,
        CURLOPT_TIMEOUT           => 60,
        CURLOPT_FOLLOWLOCATION    => true,
        CURLOPT_MAXREDIRS         => 3,
        CURLOPT_USERAGENT         => 'PHP OAI-PMH Library',
    ];

    /**
     * Constructor
     *
     * Checks for CURL libraries
     *
     * @param array $curlOpts  Array of CURL directives and values (e.g. [CURLOPT_TIMEOUT => 120])
     * @throws \Exception  If CURL not installed.
     */
    public function __construct(array $curlOpts = [])
    {
        if (! is_callable('curl_exec')) {
            throw new \Exception("OAI-PMH CurlAdapter HTTP HttpAdapterInterface requires the CURL PHP Extension");
        }

        $this->setCurlOpts($curlOpts);
    }

    /**
     * Set cURL Options at runtime
     *
     * Sets cURL options.  If $merge is true, then merges desired params with existing.
     * If $merge is false, then clobbers the existing cURL options
     *
     * @param array $opts
     * @param bool  $merge
     */
    public function setCurlOpts(array $opts, $merge = true)
    {
        $this->curlOpts = ($merge)
            ? array_replace($this->curlOpts, $opts)
            : $opts;
    }

    /**
     * Do CURL Request
     *
     * @param  string $url The full URL
     * @return string The response body
     */
    public function request($url)
    {
        $curlOpts = array_replace($this->curlOpts, [CURLOPT_URL => $url]);

        $ch = curl_init();
        foreach ($curlOpts as $opt => $optVal) {
            curl_setopt($ch, $opt, $optVal);
        }

        $resp = curl_exec($ch);
        $info = (object) curl_getinfo($ch);
        curl_close($ch);

        //Check response
        $httpCode = (string) $info->http_code;
        if ($httpCode[0] != '2') {
            $msg = sprintf('HTTP Request Failed (code %s): %s', $info->http_code, $resp);
            throw new HttpException($resp, $msg, $httpCode);
        } elseif (strlen(trim($resp)) == 0) {
            throw new HttpException($resp, 'HTTP Response Empty');
        }

        return $resp;
    }
}
