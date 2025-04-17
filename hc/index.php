<?php
/* ----------------------------------------------------------------------
 * index.php : health check
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2025 Whirl-i-Gig
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
define("__CA_APP_TYPE__", "PROVIDENCE");
define("__CA_MICROTIME_START_OF_REQUEST__", microtime());
define("__CA_BASE_MEMORY_USAGE__", memory_get_usage(true));
require("../app/helpers/errorHelpers.php");

$s = explode("/", isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : __FILE__);
array_pop($s); array_pop($s);
define("__CA_BASE_DIR__", join("/", $s));

if (!file_exists('../setup.php')) {
	print "No setup.php found";
	http_response_code(500);
	exit; 
}
if (!@require('../setup.php')) { 
	print "Loading setup.php failed";
	http_response_code(500);
	exit; 
}
if (!@require_once('../app/helpers/post-setup.php')) {
	print "Loading post-setup.php failed";
	http_response_code(500);
	exit; 
}

require_once(__CA_APP_DIR__.'/lib/ConfigurationCheck.php');
ConfigurationCheck::performQuick(['skipPathChecks' => true]);
if(ConfigurationCheck::foundErrors()){
	print "Configuration check failed";
	ConfigurationCheck::renderErrorsAsHTMLOutput();
	http_response_code(500);
	exit; 
}

if(isset($_REQUEST['key']) && defined('__CA_HC_INFO_KEY__') && strlen(__CA_HC_INFO_KEY__) && (__CA_HC_INFO_KEY__ === $_REQUEST['key'])) {
	header("Content-type: application/json");
	$ret = [
		'status' => 'happy',
		'php_version' => caGetPHPVersion(),
		'ca_version' => __CollectiveAccess_Version__,
		'schema_revision' => __CollectiveAccess_Schema_Rev__,
		
		'server_time' => date('c'),
		'timezone' => date_default_timezone_get(),
		'cpu_load' => sys_getloadavg(),
		'os' => php_uname()
	];
	
	foreach([
		'CA_PROVIDENCE_BRANCH' => 'providence_branch',
		'CA_PAWTUCKET_BRANCH' => 'pawtucket_branch',
		'WORDPRESS_BRANCH' => 'wordpress_branch',
		'CA_PROVIDENCE_ROOT' => 'providence_path',
		'CA_PAWTUCKET_ROOT' => 'pawtucket_path',
		'WORDPRESS_ROOT' => 'wordpress_path'
	] as $env => $k) {
		if(strlen($e = getenv($env))) {
			$ret[$k] = $e;
		} 
	}
	
	print caFormatJson(json_encode($ret));
} else {
	header("Content-type: text/plain");
	print "status=happy";
}
