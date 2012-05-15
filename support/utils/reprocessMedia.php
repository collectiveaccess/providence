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
	
	$o_db = new Db();
	
	$t_rep = new ca_object_representations();
	$t_rep->setMode(ACCESS_WRITE);
	
	$qr_reps = $o_db->query("SELECT * FROM ca_object_representations ORDER BY representation_id");
	while($qr_reps->nextRow()) {
		$vs_mimetype = $qr_reps->getMediaInfo('media', 'original', 'MIMETYPE');
		if(($argv[3]) && (!preg_match("!^".$argv[3]."!", $vs_mimetype))) {
			continue;
		}
		print "Re-processing ".$vs_mimetype." media for representation id=".$qr_reps->get('representation_id')."\n";
		$t_rep->load($qr_reps->get('representation_id'));
		$t_rep->set('media', $p =$qr_reps->getMediaPath('media', 'original'));
		print "path=$p\n";
		if ($argv[2]) {
			$t_rep->update(array('update_only_media_versions' => array($argv[2])));
		} else {
			$t_rep->update();
		}
		
		if ($t_rep->numErrors()) {
			print "\tERROR PROCESSING MEDIA: ".join('; ', $t_rep->getErrors())."\n";
		}
	}
?>
