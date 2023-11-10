<?php

namespace Softonic\OAuth2\Guzzle\Middleware\Test;

use PHPUnit\Framework\TestCase;
use Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler;

class AccessTokenCacheHandlerTest extends TestCase
{
    public function testGetCacheKeyIsDifferentBetweenOauthClients()
    {
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);

        $options = [];
        $providerA = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $providerA->expects($this->any())
            ->method('getAuthorizationUrl')
            ->willReturn('http://example.com?client_id=a');

        $providerB = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $providerB->expects($this->any())
            ->method('getAccessToken')
            ->willReturn('http://example.com?client_id=b');

        $cacheHandler = new AccessTokenCacheHandler($mockCache);
        $this->assertNotEquals(
            $cacheHandler->getCacheKey($providerA, $options),
            $cacheHandler->getCacheKey($providerB, $options)
        );
    }

    public function testGetCacheKeyIsEqualForSameProvider()
    {
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);

        $options = [];
        $providerA = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $providerB = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);

        $cacheHandler = new AccessTokenCacheHandler($mockCache);
        $this->assertEquals(
            $cacheHandler->getCacheKey($providerA, $options),
            $cacheHandler->getCacheKey($providerB, $options)
        );
    }

    public function testGetCacheKeyIsDifferentBetweenSameProviderButDifferentOptions()
    {
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);

        $optionsA = [
            'grant_type' => 'client_credentials',
            'scope' => 'myscopeA',
        ];
        $optionsB = [
            'grant_type' => 'client_credentials',
            'scope' => 'myscopeB',
        ];

        $provider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);

        $cacheHandler = new AccessTokenCacheHandler($mockCache);
        $this->assertNotEquals(
            $cacheHandler->getCacheKey($provider, $optionsA),
            $cacheHandler->getCacheKey($provider, $optionsB)
        );
    }

    public function testGetTokenByProviderWhenNotSet()
    {
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);
        $mockCacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);

        $mockCache->expects($this->once())
            ->method('getItem')
            ->with($this->matchCacheKey())
            ->willReturn($mockCacheItem);

        $mockCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $cacheHandler = new AccessTokenCacheHandler($mockCache);
        $this->assertFalse($cacheHandler->getTokenByprovider($mockProvider, []));
    }

    public function testGetTokenByProviderWhenSet()
    {
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);
        $mockCacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);

        $mockCache->expects($this->once())
            ->method('getItem')
            ->with($this->matchCacheKey())
            ->willReturn($mockCacheItem);

        $mockCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $mockCacheItem->expects($this->once())
            ->method('get')
            ->willReturn('mytoken');

        $cacheHandler = new AccessTokenCacheHandler($mockCache);
        $this->assertSame('mytoken', $cacheHandler->getTokenByprovider($mockProvider, []));
    }

    public function testSaveTokenByProvider()
    {
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);
        $mockCacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
        $mockAccessToken = $this->createMock(\League\OAuth2\Client\Token\AccessToken::class);

        $expiryTimestamp = 1498146237;
        $mockAccessToken->expects($this->once())
            ->method('getToken')
            ->willReturn('mytoken');
        $mockAccessToken->expects($this->once())
            ->method('getExpires')
            ->willReturn($expiryTimestamp);

        $mockCache->expects($this->once())
            ->method('getItem')
            ->with($this->matchCacheKey())
            ->willReturn($mockCacheItem);

        $mockCache->expects($this->once())
            ->method('save')
            ->with($mockCacheItem)
            ->willReturn(true);

        $mockCacheItem->expects($this->once())
            ->method('set')
            ->with('mytoken');

        $mockCacheItem->expects($this->once())
            ->method('expiresAt')
            ->with(
                $this->isInstanceOf(\DateTime::class)
            );

        $cacheHandler = new AccessTokenCacheHandler($mockCache);
        $this->assertTrue($cacheHandler->saveTokenByProvider($mockAccessToken, $mockProvider, []));
    }

    public function testDeleteItemByProvider()
    {
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockCache = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);

        $mockCache->expects($this->once())
            ->method('deleteItem')
            ->with($this->matchCacheKey())
            ->willReturn(true);

        $cacheHandler = new AccessTokenCacheHandler($mockCache);
        $this->assertTrue($cacheHandler->deleteItemByprovider($mockProvider, []));
    }

    private function matchCacheKey()
    {
        return $this->matchesRegularExpression('/^oauth2-token-[a-f0-9]{32}$/');
    }
}
