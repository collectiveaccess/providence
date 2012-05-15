#!/usr/local/bin/php
<?php
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	include('./setup.php');
	if (!$argv[1]) {
		die("You must specify a profile name\n");
	}
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	
	$o_config = new Configuration(__CA_BASE_DIR__.'/install/profiles/'.$argv[1]);
	
	$va_codes = array_keys($o_config->getAssoc('element_sets'));
	
	foreach($va_codes as $vs_code) {
		print "ca_attribute_{$vs_code}\n";
	}
?>
