<?php

namespace Srmklive\Dropbox;

trait UploadContent
{
    /**
     * The file should be uploaded in chunks if it size exceeds the 150 MB threshold
     * or if the resource size could not be determined (eg. a popen() stream).
     *
     * @param string|resource $contents
     *
     * @return bool
     */
    protected function shouldUploadChunk($contents)
    {
        $size = is_string($contents) ? strlen($contents) : fstat($contents)['size'];

        return ($this->isPipe($contents) || ($size === null)) ? true : ($size > $this->maxChunkSize);
    }

    /**
     * Check if the contents is a pipe stream (not seekable, no size defined).
     *
     * @param string|resource $contents
     *
     * @return bool
     */
    protected function isPipe($contents)
    {
        return is_resource($contents) ? (fstat($contents)['mode'] & 010000) != 0 : false;
    }

    /**
     * Upload file split in chunks. This allows uploading large files, since
     * Dropbox API v2 limits the content size to 150MB.
     *
     * The chunk size will affect directly the memory usage, so be careful.
     * Large chunks tends to speed up the upload, while smaller optimizes memory usage.
     *
     * @param string          $path
     * @param string|resource $contents
     * @param string          $mode
     * @param int             $chunkSize
     *
     * @throws \Exception
     *
     * @return array
     */
    public function uploadChunk($path, $contents, $mode = 'add', $chunkSize = null)
    {
        $chunkSize = empty($chunkSize) ? static::MAX_CHUNK_SIZE : $chunkSize;
        $stream = $contents;

        // This method relies on resources, so we need to convert strings to resource
        if (is_string($contents)) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $contents);
            rewind($stream);
        }

        $data = self::readChunk($stream, $chunkSize);
        $cursor = null;

        while (!((strlen($data) < $chunkSize) || feof($stream))) {
            // Start upload session on first iteration, then just append on subsequent iterations
            $cursor = isset($cursor) ? $this->appendContentToUploadSession($data, $cursor) : $this->startUploadSession($data);
            $data = self::readChunk($stream, $chunkSize);
        }

        // If there's no cursor here, our stream is small enough to a single request
        if (!isset($cursor)) {
            $cursor = $this->startUploadSession($data);
            $data = '';
        }

        return $this->finishUploadSession($data, $cursor, $path, $mode);
    }

    /**
     * Upload sessions allow you to upload a single file in one or more requests,
     * for example where the size of the file is greater than 150 MB.
     * This call starts a new upload session with the given data.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload_session-start
     *
     * @param string $contents
     * @param bool   $close
     *
     * @return \Srmklive\Dropbox\DropboxUploadCounter
     */
    public function startUploadSession($contents, $close = false)
    {
        $this->setupRequest(
            compact('close')
        );

        $this->apiEndpoint = 'files/upload_session/start';

        $this->content = $contents;

        $response = json_decode(
            $this->doDropboxApiContentRequest()->getBody(),
            true
        );

        return new DropboxUploadCounter($response['session_id'], strlen($contents));
    }

    /**
     * Append more data to an upload session.
     * When the parameter close is set, this call will close the session.
     * A single request should not upload more than 150 MB.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload_session-append_v2
     *
     * @param string               $contents
     * @param DropboxUploadCounter $cursor
     * @param bool                 $close
     *
     * @return \Srmklive\Dropbox\DropboxUploadCounter
     */
    public function appendContentToUploadSession($contents, DropboxUploadCounter $cursor, $close = false)
    {
        $this->setupRequest(compact('cursor', 'close'));

        $this->apiEndpoint = 'files/upload_session/append_v2';

        $this->content = $contents;

        $this->doDropboxApiContentRequest()->getBody();

        $cursor->offset += strlen($contents);

        return $cursor;
    }

    /**
     * Finish an upload session and save the uploaded data to the given file path.
     * A single request should not upload more than 150 MB.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload_session-finish
     *
     * @param string                                 $contents
     * @param \Srmklive\Dropbox\DropboxUploadCounter $cursor
     * @param string                                 $path
     * @param string|array                           $mode
     * @param bool                                   $autorename
     * @param bool                                   $mute
     *
     * @return array
     */
    public function finishUploadSession($contents, DropboxUploadCounter $cursor, $path, $mode = 'add', $autorename = false, $mute = false)
    {
        $arguments = compact('cursor');
        $arguments['commit'] = compact('path', 'mode', 'autorename', 'mute');

        $this->setupRequest($arguments);

        $this->apiEndpoint = 'files/upload_session/finish';

        $this->content = $contents;

        $response = $this->doDropboxApiContentRequest();

        $metadata = json_decode($response->getBody(), true);

        $metadata['.tag'] = 'file';

        return $metadata;
    }

    /**
     * Sometimes fread() returns less than the request number of bytes (for example, when reading
     * from network streams).  This function repeatedly calls fread until the requested number of
     * bytes have been read or we've reached EOF.
     *
     * @param resource $stream
     * @param int      $chunkSize
     *
     * @throws \Exception
     *
     * @return string
     */
    protected static function readChunk($stream, $chunkSize)
    {
        $chunk = '';
        while (!feof($stream) && $chunkSize > 0) {
            $part = fread($stream, $chunkSize);

            if ($part === false) {
                throw new \Exception('Error reading from $stream.');
            }

            $chunk .= $part;
            $chunkSize -= strlen($part);
        }

        return $chunk;
    }
}
