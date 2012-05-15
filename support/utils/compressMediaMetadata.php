#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_REPROCESSING_MEDIA__', 1);
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
	require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
	
	$o_db = new Db();
	
	$t_rep = new ca_object_representations();
	$t_rep->setMode(ACCESS_WRITE);
	
	$qr_reps = $o_db->query("SELECT * FROM ca_object_representations");
	while($qr_reps->nextRow()) {
		$va_data = caUnserializeForDatabase($qr_reps->get('media'));
		//print_r($va_data); continue;
		$vs_data = caSerializeForDatabase($va_data, true);
		
		$o_db->query("UPDATE ca_object_representations SET media = ? WHERE representation_id = ?", $vs_data, $qr_reps->get('representation_id'));
	}
?>
