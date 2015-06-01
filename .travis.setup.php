<?php

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
	define("__CA_DB_TYPE__", 'pdo_mysql');
}

# Include the defaults.
require_once('setup.php-dist');
