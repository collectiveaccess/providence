<?php
/** ---------------------------------------------------------------------
 * app/lib/TusConfig.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
 * @subpackage MediaUploader
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
return [
	// Tus server cache connection parameters
    'redis' => [
        'host' => getenv('REDIS_HOST') !== false ? getenv('REDIS_HOST') : (defined('__CA_REDIS_HOST__') ? __CA_REDIS_HOST__ : '127.0.0.1'),
        'port' => getenv('REDIS_PORT') !== false ? getenv('REDIS_PORT') : (defined('__CA_REDIS_PORT__') ? __CA_REDIS_PORT__ : '6379'),
        'database' => getenv('REDIS_DB') !== false ? getenv('REDIS_DB') : (defined('__CA_REDIS_DB__') ? __CA_REDIS_DB__ : 0),
    ],
    'file' => [
        'dir' => __CA_APP_DIR__ .'/tmp/tuscache/',
        'name' => 'tus_php.server.cache',
    ],
];
