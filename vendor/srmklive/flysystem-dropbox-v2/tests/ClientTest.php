<?php

namespace Srmklive\Dropbox\Test;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Srmklive\Dropbox\Client\DropboxClient as Client;
use Srmklive\Dropbox\DropboxUploadCounter as UploadSessionCursor;
use Srmklive\Dropbox\Exceptions\BadRequest;

class ClientTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $client = new Client('test_token');

        $this->assertInstanceOf(Client::class, $client);
    }

    /** @test */
    public function it_can_copy_a_file()
    {
        $expectedResponse = [
            '.tag' => 'file',
            'name' => 'Prime_Numbers.txt',
        ];

        $mockHttpClient = $this->mock_http_request(
            json_encode($expectedResponse),
            'https://api.dropboxapi.com/2/files/copy',
            [
                'json' => [
                    'from_path' => '/from/path/file.txt',
                    'to_path'   => '/to/path/file.txt',
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals($expectedResponse, $client->copy('from/path/file.txt', 'to/path/file.txt'));
    }

    /** @test */
    public function it_can_create_a_folder()
    {
        $mockHttpClient = $this->mock_http_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/create_folder',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals(['.tag' => 'folder', 'name' => 'math'], $client->createFolder('Homework/math'));
    }

    /** @test */
    public function it_can_delete_a_folder()
    {
        $mockHttpClient = $this->mock_http_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/delete',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals(['name' => 'math'], $client->delete('Homework/math'));
    }

    /** @test */
    public function it_can_download_a_file()
    {
        $expectedResponse = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $expectedResponse->expects($this->once())
            ->method('isReadable')
            ->willReturn(true);

        $mockHttpClient = $this->mock_http_request(
            $expectedResponse,
            'https://content.dropboxapi.com/2/files/download',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(['path' => '/Homework/math/answers.txt']),
                ],
                'body'    => '',
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertTrue(is_resource($client->download('Homework/math/answers.txt')));
    }

    /** @test */
    public function it_can_retrieve_metadata()
    {
        $mockHttpClient = $this->mock_http_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/get_metadata',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals(['name' => 'math'], $client->getMetaData('Homework/math'));
    }

    /** @test */
    public function it_can_get_a_temporary_link()
    {
        $mockHttpClient = $this->mock_http_request(
            json_encode([
                'name' => 'math',
                'link' => 'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2',
            ]),
            'https://api.dropboxapi.com/2/files/get_temporary_link',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals(
            'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2',
            $client->getTemporaryLink('Homework/math')
        );
    }

    /** @test */
    public function it_can_get_a_thumbnail()
    {
        $expectedResponse = $this->getMockBuilder(StreamInterface::class)
            ->getMock();

        $mockHttpClient = $this->mock_http_request(
            $expectedResponse,
            'https://content.dropboxapi.com/2/files/get_thumbnail',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'path'   => '/Homework/math/answers.jpg',
                            'format' => 'jpeg',
                            'size'   => 'w64h64',
                        ]
                    ),
                ],
                'body'    => '',
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertTrue(is_string($client->getThumbnail('Homework/math/answers.jpg')));
    }

    /** @test */
    public function it_can_list_a_folder()
    {
        $mockHttpClient = $this->mock_http_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/list_folder',
            [
                'json' => [
                    'path'      => '/Homework/math',
                    'recursive' => true,
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals(['name' => 'math'], $client->listFolder('Homework/math', true));
    }

    /** @test */
    public function it_can_continue_to_list_a_folder()
    {
        $mockHttpClient = $this->mock_http_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/list_folder/continue',
            [
                'json' => [
                    'cursor' => 'ZtkX9_EHj3x7PMkVuFIhwKYXEpwpLwyxp9vMKomUhllil9q7eWiAu',
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals(
            ['name' => 'math'],
            $client->listFolderContinue('ZtkX9_EHj3x7PMkVuFIhwKYXEpwpLwyxp9vMKomUhllil9q7eWiAu')
        );
    }

    /** @test */
    public function it_can_move_a_file()
    {
        $expectedResponse = [
            '.tag' => 'file',
            'name' => 'Prime_Numbers.txt',
        ];

        $mockHttpClient = $this->mock_http_request(
            json_encode($expectedResponse),
            'https://api.dropboxapi.com/2/files/move_v2',
            [
                'json' => [
                    'from_path' => '/from/path/file.txt',
                    'to_path'   => '',
                ],
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals($expectedResponse, $client->move('/from/path/file.txt', ''));
    }

    /** @test */
    public function it_can_upload_a_file()
    {
        $mockHttpClient = $this->mock_http_request(
            json_encode(['name' => 'answers.txt']),
            'https://content.dropboxapi.com/2/files/upload',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'path' => '/Homework/math/answers.txt',
                            'mode' => 'add',
                        ]
                    ),
                    'Content-Type'    => 'application/octet-stream',
                ],
                'body'    => 'testing text upload',
            ]
        );

        $client = new Client('test_token', $mockHttpClient);

        $this->assertEquals(
            ['.tag' => 'file', 'name' => 'answers.txt'],
            $client->upload('Homework/math/answers.txt', 'testing text upload')
        );
    }

    /** @test */
    public function it_can_start_upload_session()
    {
        $mockGuzzle = $this->mock_http_request(
            json_encode(['session_id' => 'mockedUploadSessionId']),
            'https://content.dropboxapi.com/2/files/upload_session/start',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'close' => false,
                        ]
                    ),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => 'this text have 23 bytes',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $uploadSessionCursor = $client->startUploadSession('this text have 23 bytes');

        $this->assertInstanceOf(UploadSessionCursor::class, $uploadSessionCursor);
        $this->assertEquals('mockedUploadSessionId', $uploadSessionCursor->session_id);
        $this->assertEquals(23, $uploadSessionCursor->offset);
    }

    /** @test */
    public function it_can_append_to_upload_session()
    {
        $mockGuzzle = $this->mock_http_request(
            null,
            'https://content.dropboxapi.com/2/files/upload_session/append_v2',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'cursor' => [
                                'session_id'    => 'mockedUploadSessionId',
                                'offset'        => 10,
                            ],
                            'close' => false,
                        ]
                    ),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => 'this text has 32 bytes',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $oldUploadSessionCursor = new UploadSessionCursor('mockedUploadSessionId', 10);

        $uploadSessionCursor = $client->appendContentToUploadSession('this text has 32 bytes', $oldUploadSessionCursor);

        $this->assertInstanceOf(UploadSessionCursor::class, $uploadSessionCursor);
        $this->assertEquals('mockedUploadSessionId', $uploadSessionCursor->session_id);
        $this->assertEquals(32, $uploadSessionCursor->offset);
    }

    /** @test */
    public function it_can_upload_a_file_string_chunk()
    {
        $content = 'chunk0chunk1chunk2rest';
        $mockClient = $this->mock_chunk_upload_client($content, 6);

        $this->assertEquals(
            ['name' => 'answers.txt'],
            $mockClient->uploadChunk('Homework/math/answers.txt', $content, 'add', 6)
        );
    }

    /** @test */
    public function it_can_upload_a_file_resource_chunk()
    {
        $content = 'chunk0chunk1chunk2rest';
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        $mockClient = $this->mock_chunk_upload_client($content, 6);

        $this->assertEquals(
            ['name' => 'answers.txt'],
            $mockClient->uploadChunk('Homework/math/answers.txt', $resource, 'add', 6)
        );
    }

    /** @test */
    public function it_can_upload_a_tiny_file_chunk()
    {
        $content = 'smallerThenChunkSize';
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        $mockClient = $this->mock_chunk_upload_client($content, 21);

        $this->assertEquals(
            ['name' => 'answers.txt'],
            $mockClient->uploadChunk('Homework/math/answers.txt', $resource, 'add', 21)
        );
    }

    /** @test */
    public function it_can_finish_an_upload_session()
    {
        $mockGuzzle = $this->mock_http_request(
            json_encode([
                'name' => 'answers.txt',
            ]),
            'https://content.dropboxapi.com/2/files/upload_session/finish',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode([
                        'cursor' => [
                            'session_id'    => 'mockedUploadSessionId',
                            'offset'        => 10,
                        ],
                        'commit' => [
                            'path'          => 'Homework/math/answers.txt',
                            'mode'          => 'add',
                            'autorename'    => false,
                            'mute'          => false,
                        ],
                    ]),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => 'this text has 32 bytes',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $oldUploadSessionCursor = new UploadSessionCursor('mockedUploadSessionId', 10);

        $response = $client->finishUploadSession(
            'this text has 32 bytes',
            $oldUploadSessionCursor,
            'Homework/math/answers.txt'
        );

        $this->assertEquals([
            '.tag' => 'file',
            'name' => 'answers.txt',
        ], $response);
    }

    /** @test */
    public function it_can_get_account_info()
    {
        $expectedResponse = [
            'account_id'    => 'dbid:AAH4f99T0taONIb-OurWxbNQ6ywGRopQngc',
            'name'          => [
                'given_name'        => 'Franz',
                'surname'           => 'Ferdinand',
                'familiar_name'     => 'Franz',
                'display_name'      => 'Franz Ferdinand (Personal)',
                'abbreviated_name'  => 'FF',
            ],
            'email'             => 'franz@gmail.com',
            'email_verified'    => false,
            'disabled'          => false,
            'locale'            => 'en',
            'referral_link'     => 'https://db.tt/ZITNuhtI',
            'is_paired'         => false,
            'account_type'      => [
                '.tag' => 'basic',
            ],
            'profile_photo_url' => 'https://dl-web.dropbox.com/account_photo/get/dbid%3AAAH4f99T0taONIb-OurWxbNQ6ywGRopQngc?vers=1453416673259&size=128x128',
            'country'           => 'US',
        ];

        $mockGuzzle = $this->mock_http_request(
            json_encode($expectedResponse),
            'https://api.dropboxapi.com/2/users/get_current_account',
            []
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals($expectedResponse, $client->getAccountInfo());
    }

    /** @test */
    public function it_can_revoke_token()
    {
        $mockGuzzle = $this->mock_http_request(
            null,
            'https://api.dropboxapi.com/2/auth/token/revoke',
            []
        );

        $client = new Client('test_token', $mockGuzzle);

        $client->revokeToken();
    }

    /** @test */
    public function content_endpoint_request_can_throw_exception()
    {
        $mockGuzzle = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['post'])
            ->getMock();
        $mockGuzzle->expects($this->once())
            ->method('post')
            ->willThrowException(
                new ClientException(
                    'there was an error',
                    $this->getMockBuilder(RequestInterface::class)->getMock(),
                    $this->getMockBuilder(ResponseInterface::class)->getMock()
                )
            );

        $client = new Client('test_token', $mockGuzzle);
        $this->expectException(ClientException::class);
        $client->performContentApiRequest('testing/endpoint', []);
    }

    /** @test */
    public function rpc_endpoint_request_can_throw_exception_with_400_status_code()
    {
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $mockResponse->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(400);
        $mockGuzzle = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['post'])
            ->getMock();
        $mockGuzzle->expects($this->once())
            ->method('post')
            ->willThrowException(
                new ClientException(
                    'there was an error',
                    $this->getMockBuilder(RequestInterface::class)->getMock(),
                    $mockResponse
                )
            );
        $client = new Client('test_token', $mockGuzzle);
        $this->expectException(BadRequest::class);
        $client->performApiRequest('testing/endpoint', []);
    }

    /** @test */
    public function rpc_endpoint_request_can_throw_exception_with_409_status_code()
    {
        $body = [
            'error' => [
                '.tag' => 'machine_readable_error_code',
            ],
            'error_summary' => 'Human readable error code',
        ];
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $mockResponse->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(409);
        $mockResponse->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($body));
        $mockGuzzle = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['post'])
            ->getMock();
        $mockGuzzle->expects($this->once())
            ->method('post')
            ->willThrowException(
                new ClientException(
                    'there was an error',
                    $this->getMockBuilder(RequestInterface::class)->getMock(),
                    $mockResponse
                )
            );

        $client = new Client('test_token', $mockGuzzle);
        $this->expectException(BadRequest::class);
        $client->performApiRequest('testing/endpoint', []);
    }

    /** @test */
    public function it_can_normalize_paths()
    {
        $normalizeFunction = self::getMethod('normalizePath');

        $client = new Client('test_token');

        //Default functionality of client to prepend slash for file paths requested
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['/test/file/path']), '/test/file/path');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['testurl']), '/testurl');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['']), '');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['file:1234567890']), '/file:1234567890');

        //If supplied with a direct id/ns/rev normalization should not prepend slash
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['id:1234567890']), 'id:1234567890');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['ns:1234567890']), 'ns:1234567890');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['rev:1234567890']), 'rev:1234567890');
    }

    private function mock_http_request($expectedResponse, $expectedEndpoint, $expectedParams)
    {
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($expectedResponse);

        $mockHttpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['post'])
            ->getMock();
        $mockHttpClient->expects($this->once())
            ->method('post')
            ->with($expectedEndpoint, $expectedParams)
            ->willReturn($mockResponse);

        return $mockHttpClient;
    }

    private function mock_chunk_upload_client($content, $chunkSize)
    {
        $chunks = str_split($content, $chunkSize);

        $mockClient = $this->getMockBuilder(Client::class)
            ->setConstructorArgs(['test_token'])
            ->setMethodsExcept(['uploadChunk', 'upload'])
            ->getMock();

        $mockClient->expects($this->once())
            ->method('startUploadSession')
            ->with(array_shift($chunks))
            ->willReturn(new UploadSessionCursor('mockedSessionId', $chunkSize));

        $mockClient->expects($this->once())
            ->method('finishUploadSession')
            ->with(array_pop($chunks), $this->anything(), 'Homework/math/answers.txt', 'add')
            ->willReturn(['name' => 'answers.txt']);

        $remainingChunks = count($chunks);
        $offset = $chunkSize;

        if ($remainingChunks) {
            $withs = [];
            $returns = [];

            foreach ($chunks as $chunk) {
                $offset += $chunkSize;
                $withs[] = [$chunk, $this->anything()];
                $returns[] = new UploadSessionCursor('mockedSessionId', $offset);
            }

            $mockClient->expects($this->exactly($remainingChunks))
                ->method('appendContentToUploadSession')
                ->withConsecutive(...$withs)
                ->willReturn(...$returns);
        }

        return $mockClient;
    }

    protected static function getMethod($name)
    {
        $class = new \ReflectionClass(Client::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
