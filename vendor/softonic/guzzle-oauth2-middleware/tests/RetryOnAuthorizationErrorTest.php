<?php

namespace Softonic\OAuth2\Guzzle\Middleware\Test;

use PHPUnit\Framework\TestCase;
use Softonic\OAuth2\Guzzle\Middleware\RetryOnAuthorizationError;

class RetryOnAuthorizationErrorTest extends TestCase
{
    public function testDoNotRetryOnNot401Response()
    {
        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $mockResponse->expects($this->exactly(1))
            ->method('getStatusCode')
            ->willReturn(200);

        $decider = new RetryOnAuthorizationError($mockProvider, [], $mockCacheHandler);
        $this->assertFalse($decider(0, $mockRequest, $mockResponse));
    }

    public function testRetryOnceOn401Response()
    {
        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockProvider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $mockResponse->expects($this->exactly(1))
            ->method('getStatusCode')
            ->willReturn(401);

        $mockCacheHandler->expects($this->once())
            ->method('deleteItemByProvider')
            ->with($mockProvider, []);

        $decider = new RetryOnAuthorizationError($mockProvider, [], $mockCacheHandler);
        $this->assertTrue($decider(0, $mockRequest, $mockResponse));
        $this->assertFalse($decider(1, $mockRequest, $mockResponse));
    }

    private function matchCacheKey()
    {
        return $this->matchesRegularExpression('/^oauth2-token-[a-f0-9]{32}$/');
    }
}
