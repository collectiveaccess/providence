#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_REINDEXING__', 1);
	set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nupdateDidYouMeanData.php: recreates data for generating 'did you mean?' search suggestions for specified CollectiveAccess instance.\n\nUSAGE: reindex.php 'instance_name'\nExample: ./reindex.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");
	
	$o_si = new SearchIndexer(null, 'DidYouMean');
	$o_si->reindex();	
?>
