<?php
/* ----------------------------------------------------------------------
 * app/helpers/post-setup.php : startup file executed after setup.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

#
# __CA_BASE_DIR__ = the absolute server path to the directory containing your CollectiveAccess installation
#
#		The default value attempts to determine the path automatically. You should only change this if it's
#		failing to derive the correct value.
#
# 		If you must to set this manually, enter the correct directory but omit trailing slashes!
# 		For Windows hosts, use a notation similar to "C:/PATH/TO/COLLECTIVEACCESS"; do NOT use backslashes
#
if (!defined("__CA_BASE_DIR__")) {
	define("__CA_BASE_DIR__", pathinfo(preg_replace("!/install|/viewers/apps|/tests|support/bin/!", "", isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : __FILE__), PATHINFO_DIRNAME));
}


#
# __CA_URL_ROOT__ = the root-relative URL path to your CollectiveAccess installation
#
#		The default value attempts to determine the relative URL path automatically. You should only change 
#		this if it's failing to derive the correct value.
#
#		If you must to set this manually leave the __CA_URL_ROOT_ *BLANK* if the CollectiveAccess directory is the 
#		web server root or in the root directory of a virtual host. If CollectiveAccess is in a subdirectory or
#		an alias is used to point the web server to the correct path, set '__CA_URL_ROOT__' to
#		the relative url path to the subdirectory; start the path with a slash ('/') but omit trailing slashes.
#
# 		Example: If CollectiveAccess will be accessed via http://www.mysite.org/apps/ca then __CA_URL_ROOT__ would be set to /apps/ca
#
if (!defined("__CA_URL_ROOT__")) {
	define("__CA_URL_ROOT__", str_replace(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '', '', __CA_BASE_DIR__));
}


#
# __CA_SITE_HOSTNAME__ = the hostname for your system
#
#		The default value attempts to determine the relative URL path automatically. You should only change 
#		this if it's failing to derive the correct value.
#
#		If you must set this manually, it must be the full host name. Do not include http:// or any other prefixes.
#
if (!defined("__CA_SITE_HOSTNAME__")) {
	define("__CA_SITE_HOSTNAME__", isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
}

#
# __CA_SITE_PROTOCOL__ = the protocol your system should be accessed with
#
#		The default value is based on the URL being used to access the site.  To force a protocol, set it explicitly.
#
if (!defined("__CA_SITE_PROTOCOL__")) {
	define("__CA_SITE_PROTOCOL__", isset($_SERVER['HTTPS']) ? 'https' : ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&  ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http'));
}

# Path to CollectiveAccess 'app' directory 
if (!defined("__CA_APP_DIR__")) {
	define("__CA_APP_DIR__", __CA_BASE_DIR__."/app");
}

# Path to CollectiveAccess 'models' directory containing database table model classes
if (!defined("__CA_MODELS_DIR__")) {
	define("__CA_MODELS_DIR__", __CA_APP_DIR__."/models");
}

# Path to CollectiveAccess 'lib' directory containing software libraries CA needs to function
if (!defined("__CA_LIB_DIR__")) {
	define("__CA_LIB_DIR__", __CA_APP_DIR__."/lib");
}

# Path to CollectiveAccess 'lib' directory containing software libraries CA needs to function
if (!defined("__CA_CONF_DIR__")) {
	define("__CA_CONF_DIR__", __CA_APP_DIR__."/conf");
}

#
if(!isset($_CA_THEMES_BY_DEVICE) || !is_array($_CA_THEMES_BY_DEVICE) || !sizeof($_CA_THEMES_BY_DEVICE)) {
	$_CA_THEMES_BY_DEVICE = array(
		'_default_' 	=> 'default'
	);
}

# Set path to instance configuration file
# (If you want to run several CA distinct instances using a single install you can add additional configuration files here.)
$_CA_INSTANCE_CONFIG_FILES = array(
	'_default_'	=> __CA_CONF_DIR__.'/app.conf'	// the _default_ value must always be defined
);

if (!isset($_SERVER['HTTP_HOST']) || !isset($_CA_INSTANCE_CONFIG_FILES[$_SERVER['HTTP_HOST']]) || !($_CA_CONFIG_PATH = $_CA_INSTANCE_CONFIG_FILES[$_SERVER['HTTP_HOST']])) {
	$_CA_CONFIG_PATH = $_CA_INSTANCE_CONFIG_FILES['_default_'];
} 

if (!(file_exists($_CA_CONFIG_PATH))) {
	$opa_error_messages = array("Configuration files are missing for hostname '".(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '[unknown]')."'!<br/>Please check the <em>__CA_BASE_DIR__</em> configuration setting in your <em>setup.php</em> file.");
	if (!include_once(__CA_BASE_DIR__ . "/themes/default/views/system/configuration_error_html.php")) {
		die("Fatal error: Configuration files are missing for hostname '".$_SERVER['HTTP_HOST']."'! Please check the __CA_BASE_DIR__ configuration setting in your setup.php file.");
	}
	exit();
}

# Only MySQL databases are currently supported but there are three available 
# methods to interact with them:
#		Use 'mysql' if you need to use the old PHP "mysql" drivers. This is the default for older versions and can be used if all else fails. 
#		Use 'mysqli' to use the PHP MySQLi drivers. This is the current default.
#		Use 'pdo_mysql' to use the PHP MySQL PDO driver. This has not been fully tested yet but should generally work ok.
#
#  When in doubt, try MySQLi first and then fall back to mysql
if (!defined("__CA_DB_TYPE__")) {
	define("__CA_DB_TYPE__", 'mysqli');
}

set_include_path(__CA_LIB_DIR__.PATH_SEPARATOR.__CA_MODELS_DIR__.PATH_SEPARATOR.get_include_path());

# The path to the main instance configuration file defined as a constant
if (!defined('__CA_APP_CONFIG__')) {
	define('__CA_APP_CONFIG__', $_CA_CONFIG_PATH);
}

# Now that we have __CA_APP_DIR__ set we can load our request helpers - very basic functions we need to set up request handling
require_once(__CA_APP_DIR__.'/helpers/requestHelpers.php');

# Name of theme to use for this request
if (!defined("__CA_THEME__")) {
	define("__CA_THEME__", $g_configuration_cache_suffix = caGetPreferredThemeForCurrentDevice($_CA_THEMES_BY_DEVICE));
}

# Path to CollectiveAccess 'themes' directory containing visual presentation elements
if (!defined("__CA_THEMES_DIR__")) {
	define("__CA_THEMES_DIR__", __CA_BASE_DIR__."/themes");
}

# Now that we have __CA_APP_DIR__ set we can load our request helpers - very basic functions we need to set up request handling
require_once(__CA_APP_DIR__.'/helpers/requestHelpers.php');


# Root-relative URL path to 'themes' directory
if (!defined("__CA_THEMES_URL__")) {
	define("__CA_THEMES_URL__", __CA_URL_ROOT__."/themes");
}

# Directory and URL paths to current theme
if (!defined("__CA_THEME_DIR__")) {
	define("__CA_THEME_DIR__", __CA_THEMES_DIR__."/".__CA_THEME__);
}
if (!defined("__CA_THEME_URL__")) {
	define("__CA_THEME_URL__", __CA_THEMES_URL__."/".__CA_THEME__);
}

# Path to local config directory - configuration containing installation-specific configuration
# Note that this is not the same as the __CA_CONF_DIR__, which contains general application configuration
# Installation-specific configuration simply allows you to override selected application configuration as-needed without having to modify the stock config
# Note also that unit tests should generally ignore local configuration and use the base configuration only
if (!defined("__CA_LOCAL_CONFIG_DIRECTORY__")) {
	define("__CA_LOCAL_CONFIG_DIRECTORY__", __CA_APP_DIR__."/conf/local");
}
if (!defined("__CA_DEFAULT_THEME_CONFIG_DIRECTORY__")) {
	define("__CA_DEFAULT_THEME_CONFIG_DIRECTORY__", __CA_THEMES_DIR__."/".__CA_THEME__."/conf");
}

#
# Load the application version number
#
require_once(__CA_APP_DIR__.'/version.php');


# --------------------------------------------------------------------------------------------
# Email configuration
#

# __CA_SMTP_SERVER__ = Hostname of outgoing mail server
#
if (!defined("__CA_SMTP_SERVER__")) {
	define("__CA_SMTP_SERVER__", 'localhost');
}

# __CA_SMTP_PORT__ = port to use for outgoing mail
#
if (!defined("__CA_SMTP_PORT__")) {
	define("__CA_SMTP_PORT__", 25);
}

# If your outgoing (SMTP) mail server requires you to authenticate then authentiucation 
# details must be set in  __CA_SMTP_AUTH__, __CA_SMTP_USER__, __CA_SMTP_PASSWORD__
# and __CA_SMTP_SSL__
#

# __CA_SMTP_AUTH__ = authentication method for outgoing mail connection
#
# Leave blank if authenication is not required. Supported authentication methods are
# PLAIN, LOGIN and CRAM-MD5. LOGIN is most typically used.
#
if (!defined("__CA_SMTP_AUTH__")) {
	define("__CA_SMTP_AUTH__", '');
}

# __CA_SMTP_USER__ = User name for outgoing mail authentication
#
if (!defined("__CA_SMTP_USER__")) {
	define("__CA_SMTP_USER__", '');
}

# __CA_SMTP_PASSWORD__ = Password for outgoing mail authentication
#
if (!defined("__CA_SMTP_PASSWORD__")) {
	define("__CA_SMTP_PASSWORD__", '');
}

# __CA_SMTP_SSL__ = SSL method to use for outgoing mail connection
#
# Use either SSL or TLS. Leave blank if SMTP encryption is not used.
#
if (!defined("__CA_SMTP_SSL__")) {
	define("__CA_SMTP_SSL__", '');
}

# --------------------------------------------------------------------------------------------
# Caching configuration
# The default file-based caching should work fine in most setups
# but if you want to use memcached or php APC instead, configure them here

# Backend to use. Available options are: 'file', 'memcached', 'redis', and 'apc'
# Note that memcached, redis and apc require PHP extensions that are not
# part of the standard CollectiveAccess configuration check. If you do
# configure them here and your setup doesn't have the extension, you
# may see critical errors.
if (!defined('__CA_CACHE_BACKEND__')) { 
	define('__CA_CACHE_BACKEND__', 'file');
}

# File path for file-based caching. The default works but in some setups you may want to move this
# to the fastest available storage (in terms of random access time), like an SSD
if (!defined('__CA_CACHE_FILEPATH__')) { 
	define('__CA_CACHE_FILEPATH__', __CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp');
}

# Time-to-live for cache items (in seconds)
if (!defined('__CA_CACHE_TTL__')) { 
	define('__CA_CACHE_TTL__', 3600);
}

# Host and port for memcached
if (!defined('__CA_MEMCACHED_HOST__')) { 
	define('__CA_MEMCACHED_HOST__', 'localhost');
}
if (!defined('__CA_MEMCACHED_PORT__')) { 
	define('__CA_MEMCACHED_PORT__', 11211);
}

# Host and port for redis
if (!defined('__CA_REDIS_HOST__')) { 
	define('__CA_REDIS_HOST__', 'localhost');
}
if (!defined('__CA_REDIS_PORT__')) { 
	define('__CA_REDIS_PORT__', 6379);
}

# Redis database index. This is useful if you want to use your Redis instance for several 
# applications. Redis is usually set up with 16 databases, indexed 0 through 15. 
# CollectiveAccess will use the first (index 0) unless told otherwise.
if (!defined('__CA_REDIS_DB__')) { 
	define('__CA_REDIS_DB__', 0);
}


error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

# __CA_ALLOW_AUTOMATIC_UPDATE_OF_VENDOR_DIR__
#
# Set this to allow allow web-based loading of missing vendor libraries.
# This can be a very convenient way to install your system but could present a possible 
# security risk if your system is publicly accessible on the internet. The risk is that 
# by exposing the update control on a public url on a publicly accessible site you are 
# potentially allowing anyone to initiate the update. 
if (!defined('__CA_ALLOW_AUTOMATIC_UPDATE_OF_VENDOR_DIR__')) {
	define('__CA_ALLOW_AUTOMATIC_UPDATE_OF_VENDOR_DIR__', true);
}

# Is vendor code set up?
require_once(__CA_APP_DIR__.'/helpers/requestHelpers.php');	// provides caCheckVendorLibraries helper
caCheckVendorLibraries();

# includes commonly used classes
require_once(__CA_APP_DIR__.'/helpers/preload.php');

#
# Bail if request is a Google Cloud health check. We can to return an HTTP 200 code to 
# signify "health"
#
if (caRequestIsHealthCheck()) { print "OK"; exit; }

# __CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__
#
# Set this to enable drag-and-drop upload of profiles in the installer. This allows 
# you to avoid having to FTP new profiles or changes to existing ones. Note that this can
# be a security risk as it allows anyone to upload files to your server. You should leave 
# it set to false unless you really need it.
if (!defined('__CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__')) {
	define('__CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__', false);
}

# __CA_DISABLE_CONFIG_CACHING__
#
# Set this to force configuration settings to be loaded from the plain text
# files in __CA_CONF_DIR__ on each page refresh. This will have a significant negative 
# impact on performance. However, there are certain scenarios where want to prevent 
# caching, e.g. for debugging. DO NOT touch this unless you know what you're doing!
if (!defined('__CA_DISABLE_CONFIG_CACHING__')) {
	define('__CA_DISABLE_CONFIG_CACHING__', false);
}

# __CA_ENABLE_DEBUG_OUTPUT__
#
# Set this to have the application print debugging information in the debug 
# console. This is primarily intended for developers working on custom code. If this 
# is enabled, any variables passed to the the caDebug() function 
# (see app/helpers/utilityHelpers.php) will trigger a detailed output of the 
# variable content. Note that utilityHelpers.php has to be included to use the function, 
# but it usually is.
if (!defined('__CA_ENABLE_DEBUG_OUTPUT__')) {
	define('__CA_ENABLE_DEBUG_OUTPUT__', false);
}

# __CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__
#
# Set this to allow allow web-based database schema updates required after a code updates.
# This can be a very convenient way to update your database but could present a possible 
# security risk if your system is publicly accessible on the internet. The risk is that 
# by exposing the update control on a public url on a publicly accessible site you are 
# potentially allowing anyone to initiate the database update. 
if (!defined('__CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__')) {
	define('__CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__', true);
}

# __CA_APP_TYPE__
#
# Flag indicating application type to code libraries
if (!defined("__CA_APP_TYPE__")) {
	define("__CA_APP_TYPE__", "PROVIDENCE");
}
