#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_REPROCESSING_MEDIA__', 1);
	
	
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	$vs_base_path = $argv[2];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
	
	$o_db = new Db();
	
	$t_rep = new ca_object_representations();
	$t_rep->setMode(ACCESS_WRITE);
	
	$qr_reps = $o_db->query("SELECT representation_id, media FROM ca_object_representations");
	
	$vn_i = 0;
	$vn_c = 0;
	while($qr_reps->nextRow()) {
		$va_versions = $qr_reps->getMediaVersions('media');
		foreach($va_versions as $vs_version) {
			$vs_orig_path = $qr_reps->getMediaPath('media', $vs_version);
			$vs_path = str_replace(__CA_BASE_DIR__.'/media', '', $vs_orig_path);
			//print "got $vs_path\n";
			$va_tmp = explode('/', $vs_path);
			$vs_filename = array_pop($va_tmp);
			
			createDirs($va_tmp, $vs_base_path);
			
			print "\tCOPY {$vs_orig_path} TO ".$vs_base_path.'/'.$vs_path."\n";
			copy($vs_orig_path, $vs_base_path.'/'.$vs_path);
		}
	}
	
	
	function createDirs($pa_dirs, $ps_dir_path) {
		
		$va_cur_dir = array($ps_dir_path);
		foreach($pa_dirs as $vs_dir) {
			$va_cur_dir[] = $vs_dir;
			
			if (!file_exists(join('/', $va_cur_dir))) {
				mkdir(join('/', $va_cur_dir));
			}
		}
	}
?>
