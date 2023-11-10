<?php

namespace Softonic\GraphQL\Test;

use PHPUnit\Framework\TestCase;
use Softonic\GraphQL\ResponseBuilder;

class ResponseBuilderTest extends TestCase
{
    public function testBuildMalformedResponse()
    {
        $mockHttpResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockHttpResponse->expects($this->once())
            ->method('getBody')
            ->willReturn('malformed response');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid JSON response. Response body: ');

        $builder = new ResponseBuilder();
        $builder->build($mockHttpResponse);
    }

    public function buildInvalidGraphqlJsonResponsProvider()
    {
        return [
            'Invalid structure' => [
                'body' => '["hola mundo"]',
            ],
            'No data in structure' => [
                'body' => '{"foo": "bar"}',
            ],
        ];
    }

    /**
     * @dataProvider buildInvalidGraphqlJsonResponsProvider
     */
    public function testBuildInvalidGraphqlJsonResponse(string $body)
    {
        $mockHttpResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        $mockHttpResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid GraphQL JSON response. Response body: ');

        $builder = new ResponseBuilder();
        $builder->build($mockHttpResponse);
    }

    public function testBuildValidGraphqlJsonWithoutErrors()
    {
        $mockHttpResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        $mockHttpResponse->expects($this->once())
            ->method('getBody')
            ->willReturn('{"data": {"foo": "bar"}}');

        $builder = new ResponseBuilder();
        $response = $builder->build($mockHttpResponse);

        $this->assertEquals(
            ['foo' => 'bar'],
            $response->getData()
        );
    }

    public function buildValidGraphqlJsonWithErrorsProvider()
    {
        return [
            'Response with null data' => [
                'body' => '{"data": null, "errors": [{"foo": "bar"}]}',
            ],
            'Response without data' => [
                'body' => '{"errors": [{"foo": "bar"}]}',
            ],
        ];
    }

    /**
     * @dataProvider buildValidGraphqlJsonWithErrorsProvider
     */
    public function testBuildValidGraphqlJsonWithErrors(string $body)
    {
        $mockHttpResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        $mockHttpResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $builder = new ResponseBuilder();
        $response = $builder->build($mockHttpResponse);

        $this->assertEquals(
            [],
            $response->getData()
        );
        $this->assertTrue($response->hasErrors());
        $this->assertEquals(
            [['foo' => 'bar']],
            $response->getErrors()
        );
    }
}
