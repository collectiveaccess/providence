#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_REPROCESSING_MEDIA__', 1);
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	$va_opts = getopt("h::d");
	
	$_SERVER['HTTP_HOST'] = isset($va_opts['h']) ? $va_opts['h'] : null;
	
	require_once("./setup.php");
	if (!caIsRunFromCLI()) { die("[Error] Must be run from command line\n"); }
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
	
	$o_db = new Db();
	
	$t_rep = new ca_object_representations();
	$t_rep->setMode(ACCESS_WRITE);
	
	print "[Notice] Getting valid paths...\n";
	$qr_reps = $o_db->query("SELECT * FROM ca_object_representations");
	
	$va_paths = array();
	while($qr_reps->nextRow()) {
		foreach($qr_reps->getMediaVersions('media') as $vs_version) {
			$va_paths[$qr_reps->getMediaPath('media', $vs_version)] = true;
		}
	}
	
	print "[Notice] Getting existing files...\n";
	$va_contents = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/media', true, false);
	
	$vn_delete_count = 0;
	
	print "[Notice] Finding unused files...\n";
	foreach($va_contents as $vs_path) {
		if (!preg_match('!_ca_object_representation!', $vs_path)) { continue; } // skip non object representation files
		if (!$va_paths[$vs_path]) { 
			$vn_delete_count++;
			if (isset($va_opts['d'])) {
				unlink($vs_path);
			} else {
				print "\t[Warning] Not used: {$vs_path}\n";
			}
		}
	}
	
	print "\n[Notice] There are ".sizeof($va_contents)." files total\n";
	$vs_percent = sprintf("%2.1f", ($vn_delete_count/sizeof($va_contents)) * 100)."%";
	if ($vn_delete_count == 1) {
		print (isset($va_opts['d'])) ? "[Notice] {$vn_delete_count} file ({$vs_percent}) was deleted\n" : "[Notice] {$vn_delete_count} file ({$vs_percent}) is unused\n";
	} else {
		
		print (isset($va_opts['d'])) ?  "[Notice] {$vn_delete_count} files ({$vs_percent}) were deleted\n" : "[Notice] {$vn_delete_count} files ({$vs_percent}) are unused\n";
	}
?>