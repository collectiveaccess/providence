<?php

namespace Srmklive\Dropbox\Adapter;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use Srmklive\Dropbox\Client\DropboxClient;
use Srmklive\Dropbox\GetMimeType;
use Srmklive\Dropbox\ParseResponse;

class DropboxAdapter extends AbstractAdapter
{
    use GetMimeType;
    use NotSupportingVisibilityTrait;
    use ParseResponse;

    /** @var \Srmklive\Dropbox\Client\DropboxClient */
    protected $client;

    public function __construct(DropboxClient $client, $prefix = '')
    {
        $this->client = $client;

        $this->setPathPrefix($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, 'add');
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, 'add');
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, 'overwrite');
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, 'overwrite');
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newPath)
    {
        $path = $this->applyPathPrefix($path);
        $newPath = $this->applyPathPrefix($newPath);

        try {
            $this->client->move($path, $newPath);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->client->copy($path, $newpath);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        try {
            $this->client->delete($location);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        return $this->delete($dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $path = $this->applyPathPrefix($dirname);

        try {
            $object = $this->client->createFolder($path);

            return $this->normalizeResponse($object);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $object = $this->readStream($path);
        if ($object) {
            $object['contents'] = stream_get_contents($object['stream']);
            fclose($object['stream']);
            unset($object['stream']);
        }

        return ($object) ? $object : false;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $path = $this->applyPathPrefix($path);

        try {
            $stream = $this->client->download($path);

            return compact('stream');
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        try {
            $location = $this->applyPathPrefix($directory);

            $result = $this->client->listFolder($location, $recursive);

            return array_map(function ($entry) {
                return $this->normalizeResponse($entry);
            }, $result['entries']);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $path = $this->applyPathPrefix($path);

        try {
            $object = $this->client->getMetadata($path);

            return $this->normalizeResponse($object);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemporaryLink($path)
    {
        return $this->client->getTemporaryLink($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnail($path, $format = 'jpeg', $size = 'w64h64')
    {
        return $this->client->getThumbnail($path, $format, $size);
    }

    /**
     * {@inheritdoc}
     */
    public function applyPathPrefix($path)
    {
        $path = parent::applyPathPrefix($path);

        return '/'.trim($path, '/');
    }

    /**
     * @return DropboxClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param string          $path
     * @param resource|string $contents
     * @param string          $mode
     *
     * @throws \Exception
     *
     * @return array|false file metadata
     */
    protected function upload($path, $contents, $mode)
    {
        $path = $this->applyPathPrefix($path);

        try {
            $object = $this->client->upload($path, $contents, $mode);

            return $this->normalizeResponse($object);
        } catch (\Exception $e) {
            return false;
        }
    }
}
