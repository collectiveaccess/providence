#!/usr/local/bin/php
<?php
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
//	if (!$argv[1]) {
//		die("\nimportData.php: import files using a mapping\n");
//	}

	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/ca/ImportExport/DataImporter.php");
	
	$o_importer = new DataImporter();
	
	$va_dirs = $argv;
	array_shift($va_dirs);
	$vs_mapping = array_shift($va_dirs);
	
	foreach($va_dirs as $vs_dir) {
		print "\tReading files from {$vs_dir}...\n";
		
		if (is_dir($vs_dir)) {
			if ($r_dir = opendir($vs_dir)) {
				while (($vs_file = readdir($r_dir)) !== false) {
					if ($vs_file{0} == '.') { continue; }
					$va_files[] = $vs_dir.'/'.$vs_file;
				}
				
				closedir($r_dir);
			}
		} else {
			$va_files = array($vs_dir);
		}
		foreach($va_files as $vs_file) {
			print "\t\tImporting {$vs_file}\n";
			$o_importer->import($vs_mapping, $vs_file);
		}
	}
	
	print "\nComplete!\n";
?>