#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_VERIFYING_STRUCTURE__', 1);
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");	
	require_once(__CA_LIB_DIR__."/core/Configuration.php");	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	
	$o_dm = Datamodel::load();
	$o_config = Configuration::load();
	$o_db = new Db();
	$o_db->dieOnError(false);
	
	$vs_db_name = $o_config->get('db_database');
	
	$qr_res = $o_db->query('
		SELECT * 
		FROM information_schema.KEY_COLUMN_USAGE 
		WHERE
			CONSTRAINT_SCHEMA = ?
	', $vs_db_name);
	while($qr_res->nextRow()) {
		//print_r($qr_res->getRow());
		if ($qr_res->get('REFERENCED_TABLE_NAME')) {
			print '"'.$qr_res->get('TABLE_NAME').'.'.$qr_res->get('COLUMN_NAME').' = '.$qr_res->get('REFERENCED_TABLE_NAME').'.'.$qr_res->get('REFERENCED_COLUMN_NAME').'",'."\n";
		}
	}
?>