#!/usr/local/bin/php
<?php
	error_reporting(E_ALL);

	set_time_limit(60 * 60); /* an hour should be sufficient */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/ca/ConfigurationExporter.php");
	
	if(!class_exists("DOMDocument")){
		die("PHP's DOM extension is required to run this script.");
	}

	/*
	* b = base
	* u = infoUrl
	* n = profileName
	*/
	$va_options = getopt("b:d:u:n:");
	
	print ConfigurationExporter::exportConfigurationAsXML($va_options["n"], $va_options["d"], $va_options["b"], $va_options["u"]);
	
	
?>
