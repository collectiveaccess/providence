<?php

// Ensure that the base dir is set correctly; this should normally be the parent of the "tests" dir.
if (!defined("__CA_BASE_DIR__")) {
	define("__CA_BASE_DIR__", dirname(__DIR__));
}

// Ensure that the local configuration directory is set to a location that does not exist; this ensures only defaults
// are used by the tests and site-specific overrides do not cause unexpected failures.
if (!defined("__CA_LOCAL_CONFIG_DIRECTORY__")) {
	define("__CA_LOCAL_CONFIG_DIRECTORY__", __DIR__ . "/conf");
}

// If you require any overrides in setup.php that are specific to running unit tests, put them here.

if (!defined("__CA_DB_TYPE__")) {
	define("__CA_DB_TYPE__", 'pdo_mysql');
}

// Use remaining settings from main config.
require_once(__CA_BASE_DIR__ . '/setup.php');
