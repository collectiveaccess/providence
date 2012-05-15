#!/usr/local/bin/php
<?php
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nupdateDatabaseSchema.php: applies all database migrations required to bring the system database up-to-date with the currently installed codebase. You will typically run this after installing a software update.\n\nUSAGE: updateDatabaseSchema.php 'instance_name'\nExample: ./updateDatabaseSchema.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	require_once(__CA_LIB_DIR__."/ca/ConfigurationCheck.php");
	
	$o_config_check = new ConfigurationCheck();
	 if (($vn_current_revision = ConfigurationCheck::getSchemaVersion()) < __CollectiveAccess_Schema_Rev__) {
		print "Are you sure you want to update your CollectiveAccess database from revision {$vn_current_revision} to ".__CollectiveAccess_Schema_Rev__."?\nNOTE: you should backup your database before applying updates!\n\nType 'y' to proceed or 'N' to cancel, then hit return ";
		flush();
		ob_flush();
		$confirmation  =  trim( fgets( STDIN ) );
		if ( $confirmation !== 'y' ) {
   			// The user did not say 'y'.
   			exit (0);
		}
		$va_messages = ConfigurationCheck::performDatabaseSchemaUpdate();

		print "\n\n";
		print "Applying database migrations...\n";
		foreach($va_messages as $vs_message) {
			print "\t{$vs_message}\n";
		}
	} else {
		print "Database already at revision ".__CollectiveAccess_Schema_Rev__.". No update is required.\n";
	}
?>
