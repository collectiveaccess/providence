<?php

namespace Srmklive\Dropbox\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use GuzzleHttp\Psr7\StreamWrapper;
use Srmklive\Dropbox\Exceptions\BadRequest;
use Srmklive\Dropbox\UploadContent;

class DropboxClient
{
    use UploadContent;

    const THUMBNAIL_FORMAT_JPEG = 'jpeg';
    const THUMBNAIL_FORMAT_PNG = 'png';

    const THUMBNAIL_SIZE_XS = 'w32h32';
    const THUMBNAIL_SIZE_S = 'w64h64';
    const THUMBNAIL_SIZE_M = 'w128h128';
    const THUMBNAIL_SIZE_L = 'w640h480';
    const THUMBNAIL_SIZE_XL = 'w1024h768';

    const MAX_CHUNK_SIZE = 157286400;

    /** @var \GuzzleHttp\Client */
    protected $client;

    /**
     * Dropbox OAuth access token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * Dropbox API v2 Url.
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Dropbox content API v2 url for uploading content.
     *
     * @var string
     */
    protected $apiContentUrl;

    /**
     * Dropbox API v2 endpoint.
     *
     * @var string
     */
    protected $apiEndpoint;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * Dropbox API request data.
     *
     * @var array
     */
    protected $request;

    /**
     * @var int
     */
    protected $maxChunkSize;

    /**
     * DropboxClient constructor.
     *
     * @param string             $token
     * @param \GuzzleHttp\Client $client
     * @param int                $maxChunkSize
     */
    public function __construct($token, HttpClient $client = null, $maxChunkSize = self::MAX_CHUNK_SIZE)
    {
        $this->setAccessToken($token);

        $this->setClient($client);

        $this->apiUrl = 'https://api.dropboxapi.com/2/';
        $this->apiContentUrl = 'https://content.dropboxapi.com/2/';
        $this->maxChunkSize = ($maxChunkSize < self::MAX_CHUNK_SIZE ?
            ($maxChunkSize > 1 ? $maxChunkSize : 1) : self::MAX_CHUNK_SIZE);
    }

    /**
     * Set Http Client.
     *
     * @param \GuzzleHttp\Client $client
     */
    protected function setClient(HttpClient $client = null)
    {
        if ($client instanceof HttpClient) {
            $this->client = $client;
        } else {
            $this->client = new HttpClient([
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
            ]);
        }
    }

    /**
     * Set Dropbox OAuth access token.
     *
     * @param string $token
     */
    protected function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * Copy a file or folder to a different location in the user's Dropbox.
     *
     * If the source path is a folder all its contents will be copied.
     *
     * @param string $fromPath
     * @param string $toPath
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-copy
     */
    public function copy($fromPath, $toPath)
    {
        $this->setupRequest([
            'from_path' => $this->normalizePath($fromPath),
            'to_path'   => $this->normalizePath($toPath),
        ]);

        $this->apiEndpoint = 'files/copy';

        return $this->doDropboxApiRequest();
    }

    /**
     * Create a folder at a given path.
     *
     * @param string $path
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-create_folder
     */
    public function createFolder($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/create_folder';

        $response = $this->doDropboxApiRequest();
        $response['.tag'] = 'folder';

        return $response;
    }

    /**
     * Delete the file or folder at a given path.
     *
     * If the path is a folder, all its contents will be deleted too.
     * A successful response indicates that the file or folder was deleted.
     *
     * @param string $path
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-delete
     */
    public function delete($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/delete';

        return $this->doDropboxApiRequest();
    }

    /**
     * Download a file from a user's Dropbox.
     *
     * @param string $path
     *
     * @throws \Exception
     *
     * @return resource
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-download
     */
    public function download($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/download';

        $this->content = null;

        $response = $this->doDropboxApiContentRequest();

        return StreamWrapper::getResource($response->getBody());
    }

    /**
     * Returns the metadata for a file or folder.
     *
     * Note: Metadata for the root folder is unsupported.
     *
     * @param string $path
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_metadata
     */
    public function getMetaData($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/get_metadata';

        return $this->doDropboxApiRequest();
    }

    /**
     * Get a temporary link to stream content of a file.
     *
     * This link will expire in four hours and afterwards you will get 410 Gone.
     * Content-Type of the link is determined automatically by the file's mime type.
     *
     * @param string $path
     *
     * @throws \Exception
     *
     * @return string
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_temporary_link
     */
    public function getTemporaryLink($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/get_temporary_link';

        $response = $this->doDropboxApiRequest();

        return $response['link'];
    }

    /**
     * Get a thumbnail for an image.
     *
     * This method currently supports files with the following file extensions:
     * jpg, jpeg, png, tiff, tif, gif and bmp.
     *
     * Photos that are larger than 20MB in size won't be converted to a thumbnail.
     *
     * @param string $path
     * @param string $format
     * @param string $size
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getThumbnail($path, $format = 'jpeg', $size = 'w64h64')
    {
        $this->setupRequest([
            'path'   => $this->normalizePath($path),
            'format' => $format,
            'size'   => $size,
        ]);

        $this->apiEndpoint = 'files/get_thumbnail';

        $this->content = null;

        $response = $this->doDropboxApiContentRequest();

        return (string) $response->getBody();
    }

    /**
     * Starts returning the contents of a folder.
     *
     * If the result's ListFolderResult.has_more field is true, call
     * list_folder/continue with the returned ListFolderResult.cursor to retrieve more entries.
     *
     * Note: auth.RateLimitError may be returned if multiple list_folder or list_folder/continue calls
     * with same parameters are made simultaneously by same API app for same user. If your app implements
     * retry logic, please hold off the retry until the previous request finishes.
     *
     * @param string $path
     * @param bool   $recursive
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder
     */
    public function listFolder($path = '', $recursive = false)
    {
        $this->setupRequest([
            'path'      => $this->normalizePath($path),
            'recursive' => $recursive,
        ]);

        $this->apiEndpoint = 'files/list_folder';

        return $this->doDropboxApiRequest();
    }

    /**
     * Once a cursor has been retrieved from list_folder, use this to paginate through all files and
     * retrieve updates to the folder, following the same rules as documented for list_folder.
     *
     * @param string $cursor
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder-continue
     */
    public function listFolderContinue($cursor = '')
    {
        $this->setupRequest([
            'cursor' => $cursor,
        ]);

        $this->apiEndpoint = 'files/list_folder/continue';

        return $this->doDropboxApiRequest();
    }

    /**
     * Move a file or folder to a different location in the user's Dropbox.
     *
     * If the source path is a folder all its contents will be moved.
     *
     * @param string $fromPath
     * @param string $toPath
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-move
     */
    public function move($fromPath, $toPath)
    {
        $this->setupRequest([
            'from_path' => $this->normalizePath($fromPath),
            'to_path'   => $this->normalizePath($toPath),
        ]);

        $this->apiEndpoint = 'files/move_v2';

        return $this->doDropboxApiRequest();
    }

    /**
     * Create a new file with the contents provided in the request.
     *
     * Do not use this to upload a file larger than 150 MB. Instead, create an upload session with upload_session/start.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload
     *
     * @param string          $path
     * @param string|resource $contents
     * @param string|array    $mode
     *
     * @throws \Exception
     *
     * @return array
     */
    public function upload($path, $contents, $mode = 'add')
    {
        if ($this->shouldUploadChunk($contents)) {
            return $this->uploadChunk($path, $contents, $mode);
        }

        $this->setupRequest([
            'path' => $this->normalizePath($path),
            'mode' => $mode,
        ]);

        $this->content = $contents;

        $this->apiEndpoint = 'files/upload';

        $response = $this->doDropboxApiContentRequest();

        $metadata = json_decode($response->getBody(), true);
        $metadata['.tag'] = 'file';

        return $metadata;
    }

    /**
     * Get Account Info for current authenticated user.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#users-get_current_account
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getAccountInfo()
    {
        $this->apiEndpoint = 'users/get_current_account';

        return $this->doDropboxApiRequest();
    }

    /**
     * Revoke current access token.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#auth-token-revoke
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function revokeToken()
    {
        $this->apiEndpoint = 'auth/token/revoke';

        return $this->doDropboxApiRequest();
    }

    /**
     * Set Dropbox API request data.
     *
     * @param array $request
     */
    protected function setupRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Perform Dropbox API v2 request.
     *
     * @param $endpoint
     * @param $payload
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function performApiRequest($endpoint, $payload)
    {
        $this->setupRequest($payload);
        $this->apiEndpoint = $endpoint;

        return $this->doDropboxApiRequest();
    }

    /**
     * Perform Dropbox API v2 content request.
     *
     * @param $endpoint
     * @param $payload
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function performContentApiRequest($endpoint, $payload)
    {
        $this->setupRequest($payload);
        $this->apiEndpoint = $endpoint;

        return $this->doDropboxApiContentRequest();
    }

    /**
     * Perform Dropbox API request.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function doDropboxApiRequest()
    {
        $request = empty($this->request) ? [] : ['json' => $this->request];

        try {
            $response = $this->client->post("{$this->apiUrl}{$this->apiEndpoint}", $request);
        } catch (HttpClientException $exception) {
            throw $this->determineException($exception);
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * Setup headers for Dropbox API request.
     *
     * @return array
     */
    protected function setupDropboxHeaders()
    {
        $headers = [
            'Dropbox-API-Arg' => json_encode(
                $this->request
            ),
        ];
        if (!empty($this->content) ||
               $this->apiEndpoint == 'files/upload_session/finish') {
            // The upload_session/finish API requires a Content-Type, always
            $headers['Content-Type'] = 'application/octet-stream';
        }

        return $headers;
    }

    /**
     * Perform Dropbox API request.
     *
     * @throws \Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function doDropboxApiContentRequest()
    {
        try {
            $response = $this->client->post("{$this->apiContentUrl}{$this->apiEndpoint}", [
                'headers' => $this->setupDropboxHeaders(),
                'body'    => !empty($this->content) ? $this->content : '',
            ]);
        } catch (HttpClientException $exception) {
            throw $this->determineException($exception);
        }

        return $response;
    }

    /**
     * Normalize path.
     *
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        if (preg_match("/^id:.*|^rev:.*|^(ns:[0-9]+(\/.*)?)/", $path) === 1) {
            return $path;
        }

        $path = (trim($path, '/') === '') ? '' : '/'.$path;

        return str_replace('//', '/', $path);
    }

    /**
     * Catch Dropbox API request exception.
     *
     * @param HttpClientException $exception
     *
     * @return \Exception
     */
    protected function determineException(HttpClientException $exception)
    {
        if (!empty($exception->getResponse()) && in_array($exception->getResponse()->getStatusCode(), [400, 409])) {
            return new BadRequest($exception->getResponse());
        }

        return $exception;
    }
}
