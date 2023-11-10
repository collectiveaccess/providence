<?php

namespace Srmklive\Dropbox;

trait GetMimeType
{
    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return ['mimetype' => \League\Flysystem\Util\MimeType::detectByFilename($path)];
    }
}
