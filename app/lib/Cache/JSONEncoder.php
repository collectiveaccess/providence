<?php
/** ---------------------------------------------------------------------
 * app/lib/Cache/JSONEncoder.php : JSON-based Stash file cache encoder 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Cache
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace Stash\Driver\FileSystem;

class JSONEncoder implements EncoderInterface {
    public function deserialize($path) {
        if (!file_exists($path)) {
            return false;
        }

        $raw = json_decode(file_get_contents($path), true);
        if (is_null($raw) || !is_array($raw)) {
            return false;
        }

        $data = $raw['data'];
        $expiration = isset($raw['expiration']) ? $raw['expiration'] : null;

        return ['data' => $data, 'expiration' => $expiration];
    }

    public function serialize($key, $data, $expiration = null) {
        $processed = json_encode([
            'key' => $key,
            'data' => $data,
            'expiration' => $expiration
        ]);

        return $processed;
    }

    public function getExtension() {
        return '.json';
    }
}
