#!/usr/local/bin/php
<?php
if(!file_exists('./setup.php')) {
	die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
}

$_SERVER['HTTP_HOST'] = $argv[1];

require_once("./setup.php");
require_once(__CA_LIB_DIR__."/ConfigurationCheck.php");

if ($argv[1]) {
	$o_config_check = new ConfigurationCheck();
	if (($current_revision = ConfigurationCheck::getSchemaVersion()) < __CollectiveAccess_Schema_Rev__) {
		$messages = \System\Updater::performDatabaseSchemaUpdate();

		print "\n\n";
		print "[".$argv[1]."] Applying database migrations...\n";
		foreach($messages as $message) {
			print "\t{$message}\n";
		}
	} else {
		print "[".$argv[1]."] Database already at revision ".__CollectiveAccess_Schema_Rev__.". No update is required.\n";
	}
} else {
	foreach($g_systems as $host => $system_info) {
		passthru('php ./updateDatabaseSchema.php '.$host, $ret_);
	}	
}
