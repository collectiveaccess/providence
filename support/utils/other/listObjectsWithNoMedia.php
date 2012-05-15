#!/usr/local/bin/php
<?php
	#
	# Simple utility lists objects with no linked representations
	#
	
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nlistObjectsWithNoMedia.php: Lists all objects that do not have representations attached.'\nExample: ./listObjectsWithNoMedia.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	
	$o_db = new Db();
	
	$qr_res = $o_db->query("
		SELECT DISTINCT o.object_id
		FROM ca_objects o
		INNER JOIN ca_objects_x_object_representations AS coxor ON o.object_id = coxor.object_id
	");
	
	$va_objects_that_have_representations = array();
	while($qr_res->nextRow()) {
		$va_objects_that_have_representations[$qr_res->get('object_id')] = true;
	}
	
	$qr_res = $o_db->query("
		SELECT object_id, idno
		FROM ca_objects
	");
	
	while($qr_res->nextRow()) {
		if (!$va_objects_that_have_representations[$qr_res->get('object_id')]) {
			print $qr_res->get('idno')." has no representations attached\n";
		}
	}
?>