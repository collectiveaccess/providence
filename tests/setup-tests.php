<?php
/** ---------------------------------------------------------------------
 * tests/setup-tests.php
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
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
# Define test-specific constants.
if (!defined("__CA_DB_HOST__")) {
	define("__CA_DB_HOST__", 'localhost');
}
if (!defined("__CA_DB_USER__")) {
	define("__CA_DB_USER__", 'ca_test');
}
if (!defined("__CA_DB_PASSWORD__")) {
	define("__CA_DB_PASSWORD__", 'password');
}
if (!defined("__CA_DB_DATABASE__")) {
	define("__CA_DB_DATABASE__", 'ca_test');
}
if (!defined("__CA_APP_DISPLAY_NAME__")) {
	define("__CA_APP_DISPLAY_NAME__", 'CollectiveAccess Unit Tests');
}
if (!defined("__CA_DB_TYPE__")) {
	define("__CA_DB_TYPE__", 'mysqli');
}

// Ensure that the base dir is set correctly; this should normally be the parent of the "tests" dir.
if (!defined("__CA_BASE_DIR__")) {
	define("__CA_BASE_DIR__", dirname(__DIR__));
}

// Ensure that the local configuration directory is set to a location that does not exist; this ensures only defaults
// are used by the tests and site-specific overrides do not cause unexpected failures.
if (!defined("__CA_LOCAL_CONFIG_DIRECTORY__")) {
	define("__CA_LOCAL_CONFIG_DIRECTORY__", __DIR__ . "/conf");
}

if (!defined('__CA_CACHE_BACKEND__')) {
	define('__CA_CACHE_BACKEND__', 'file');
}

// Test instance login (for API tests)
define('__CA_USERNAME__', 'administrator');
define('__CA_PASSWORD__', 'dublincore');

// If you require any overrides in setup.php that are specific to running unit tests, put them here.

// Should GraphQL service tests run? They cannot run in some environments.
define("__CA_RUN_GRAPHQL_SERVICE_TESTS__", true);

// Must set these for GraphQL tests to run:
define("__CA_SITE_PROTOCOL__", "http");
define("__CA_SITE_HOSTNAME__", "develop");
define("__CA_URL_ROOT__", "");

// Use remaining settings from main config.
require_once(__CA_BASE_DIR__ . '/setup.php-dist');
