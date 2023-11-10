<?php

namespace Softonic\OAuth2\Guzzle\Middleware\Test;

use PHPUnit\Framework\TestCase;
use Softonic\OAuth2\Guzzle\Middleware\AddAuthorizationHeader;

class AddAuthorizationHeaderTest extends TestCase
{
    private $mockOauth2Provider;
    private $mockAccessToken;

    public function setUp()
    {
        $this->mockOauth2Provider = $this->createMock(\League\OAuth2\Client\Provider\AbstractProvider::class);
        $this->mockAccessToken = $this->createMock(\League\OAuth2\Client\Token\AccessToken::class);
    }

    public function testMiddlewareWhenGrantTypeNotSpecified()
    {
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $mockCacheHandler->expects($this->once())
            ->method('getTokenByProvider')
            ->with($this->mockOauth2Provider, [])
            ->willReturn(false);

        $addAuthorizationHeader = new AddAuthorizationHeader(
            $this->mockOauth2Provider,
            [],
            $mockCacheHandler
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config value `grant_type` needs to be specified.');

        
        $addAuthorizationHeader($mockRequest);
    }

    public function testMiddlewareWhenProviderThrowsIdentityProviderException()
    {
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $mockCacheHandler->expects($this->once())
            ->method('getTokenByProvider')
            ->with($this->mockOauth2Provider)
            ->willReturn(false);

        $this->mockOauth2Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                'client_credentials',
                ['scope' => 'myscope']
            )
            ->willThrowException(new \League\OAuth2\Client\Provider\Exception\IdentityProviderException('custom message', 500, []));

        $config = [
            'grant_type' => 'client_credentials',
            'scope' => 'myscope',
        ];
        $this->expectException(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class);
        $this->expectExceptionMessage('custom message');

        $addAuthorizationHeader = new AddAuthorizationHeader(
            $this->mockOauth2Provider,
            $config,
            $mockCacheHandler
        );
        $addAuthorizationHeader($mockRequest);
    }

    public function testMiddlewareAddingAuthorizationHeader()
    {
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $config = [
            'grant_type' => 'client_credentials',
            'scope' => 'myscope',
        ];

        $this->mockAccessToken->expects($this->once())
            ->method('getToken')
            ->willReturn('mytoken');

        $mockCacheHandler->expects($this->once())
            ->method('getTokenByProvider')
            ->with($this->mockOauth2Provider)
            ->willReturn(false);
        $mockCacheHandler->expects($this->once())
            ->method('saveTokenByProvider')
            ->with(
                $this->mockAccessToken,
                $this->mockOauth2Provider,
                $config
            );

        $this->mockOauth2Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                'client_credentials',
                ['scope' => 'myscope']
            )
            ->willReturn($this->mockAccessToken);

        $mockRequest->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer mytoken')
            ->willReturnSelf();

        $addAuthorizationHeader = new AddAuthorizationHeader(
            $this->mockOauth2Provider,
            $config,
            $mockCacheHandler
        );
        $request = $addAuthorizationHeader($mockRequest);

        $this->assertSame($mockRequest, $request);
    }

    public function testMiddlewareAddingAuthorizationHeaderWithoutScope()
    {
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);
        $config = [
            'grant_type' => 'client_credentials',
        ];

        $this->mockAccessToken->expects($this->once())
            ->method('getToken')
            ->willReturn('mytoken');

        $mockCacheHandler->expects($this->once())
            ->method('getTokenByProvider')
            ->with(
                $this->mockOauth2Provider,
                $config
            )
            ->willReturn(false);
        $mockCacheHandler->expects($this->once())
            ->method('saveTokenByProvider')
            ->with(
                $this->mockAccessToken,
                $this->mockOauth2Provider,
                $config
            );

        $mockRequest->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer mytoken')
            ->willReturnSelf();

        $this->mockOauth2Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                'client_credentials',
                []
            )
            ->willReturn($this->mockAccessToken);

        $addAuthorizationHeader = new AddAuthorizationHeader(
            $this->mockOauth2Provider,
            $config,
            $mockCacheHandler
        );
        $request = $addAuthorizationHeader($mockRequest);

        $this->assertSame($mockRequest, $request);
    }

    public function testMiddlewareAddingAuthorizationHeaderWithTokenOptions()
    {
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $config = [
            'grant_type' => 'client_credentials',
            'scope' => 'myscope',
            'token_options' => ['audience' => 'test_audience'],
        ];

        $this->mockAccessToken->expects($this->once())
            ->method('getToken')
            ->willReturn('mytoken');

        $mockCacheHandler->expects($this->once())
            ->method('getTokenByProvider')
            ->with($this->mockOauth2Provider)
            ->willReturn(false);
        $mockCacheHandler->expects($this->once())
            ->method('saveTokenByProvider')
            ->with(
                $this->mockAccessToken,
                $this->mockOauth2Provider,
                $config
            );

        $this->mockOauth2Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                'client_credentials',
                ['scope' => 'myscope', 'audience' => 'test_audience']
            )
            ->willReturn($this->mockAccessToken);

        $mockRequest->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer mytoken')
            ->willReturnSelf();

        $addAuthorizationHeader = new AddAuthorizationHeader(
            $this->mockOauth2Provider,
            $config,
            $mockCacheHandler
        );
        $request = $addAuthorizationHeader($mockRequest);

        $this->assertSame($mockRequest, $request);
    }

    public function testMiddlewareAddingAuthorizationHeaderWithTokenOptionsDeclaringScope()
    {
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $config = [
            'grant_type' => 'client_credentials',
            'scope' => 'ignoredscope',
            'token_options' => ['audience' => 'test_audience', 'scope' => 'myscope'],
        ];

        $this->mockAccessToken->expects($this->once())
            ->method('getToken')
            ->willReturn('mytoken');

        $mockCacheHandler->expects($this->once())
            ->method('getTokenByProvider')
            ->with($this->mockOauth2Provider)
            ->willReturn(false);
        $mockCacheHandler->expects($this->once())
            ->method('saveTokenByProvider')
            ->with(
                $this->mockAccessToken,
                $this->mockOauth2Provider,
                $config
            );

        $this->mockOauth2Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                'client_credentials',
                ['scope' => 'myscope', 'audience' => 'test_audience']
            )
            ->willReturn($this->mockAccessToken);

        $mockRequest->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer mytoken')
            ->willReturnSelf();

        $addAuthorizationHeader = new AddAuthorizationHeader(
            $this->mockOauth2Provider,
            $config,
            $mockCacheHandler
        );
        $request = $addAuthorizationHeader($mockRequest);

        $this->assertSame($mockRequest, $request);
    }

    public function testMiddlewareNotNegotiatingTokenWhenIsCached()
    {
        $mockRequest = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $mockCacheHandler = $this->createMock(\Softonic\OAuth2\Guzzle\Middleware\AccessTokenCacheHandler::class);

        $config = [
            'grant_type' => 'client_credentials',
        ];

        $mockCacheHandler->expects($this->once())
            ->method('getTokenByProvider')
            ->with(
                $this->mockOauth2Provider,
                $config
            )
            ->willReturn('mytoken');
        $mockCacheHandler->expects($this->never())
            ->method('saveTokenByProvider');

        $mockRequest->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer mytoken')
            ->willReturnSelf();

        $this->mockOauth2Provider->expects($this->never())
            ->method('getAccessToken');

        $addAuthorizationHeader = new AddAuthorizationHeader(
            $this->mockOauth2Provider,
            $config,
            $mockCacheHandler
        );
        $request = $addAuthorizationHeader($mockRequest);

        $this->assertSame($mockRequest, $request);
    }
}
