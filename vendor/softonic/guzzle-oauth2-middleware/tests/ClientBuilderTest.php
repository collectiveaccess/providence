<?php

namespace Softonic\OAuth2\Guzzle\Middleware\Test;

use PHPUnit\Framework\TestCase;
use Softonic\OAuth2\Guzzle\Middleware\ClientBuilder;

class ClientBuilderTest extends TestCase
{
    public function testClientBuilderWithoutGuzzleOptions()
    {
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockTokenOptions = [];

        $client = ClientBuilder::build($mockProvider, $mockTokenOptions, $mockCache);
        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $client);
    }

    public function testClientBuilderWithGuzzleOptions()
    {
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockTokenOptions = [];
        $baseUri = 'https://foo.bar/';
        $mockGuzzleOptions = [
            'base_uri' => $baseUri,
        ];
        $client = ClientBuilder::build(
            $mockProvider,
            $mockTokenOptions,
            $mockCache,
            $mockGuzzleOptions
        );
        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $client);

        $guzzleConfig = $client->getConfig();
        $this->assertEquals($baseUri, (string) $guzzleConfig['base_uri']);
    }
}
