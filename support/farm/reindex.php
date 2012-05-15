#!/usr/local/bin/php
<?php
	ini_set('memory_limit', '4000m');
	set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	//if (!$argv[1]) {
	//	die("\nreindex.php: recreates search indices for specified CollectiveAccess instance.\n\nUSAGE: reindex.php 'instance_name'\nExample: ./reindex.php 'www.mycollection.org'\n");
	//}
	
	
	if ($argv[1]) {
		$_SERVER['HTTP_HOST'] = $argv[1];
		require_once("./setup.php");
		require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");
		
		$o_si = new SearchIndexer();
		print "[REINDEXING] ".__CA_APP_DISPLAY_NAME__."\n";
		$o_si->reindex(null, array('showProgress' => true, 'interactiveProgressDisplay' => true));
	} else {
		require_once("./setup.php");
		foreach($va_systems as $vs_host => $va_system_info) {
			passthru('php ./reindex.php '.$vs_host, $vn_ret_);
		}
	}
?>