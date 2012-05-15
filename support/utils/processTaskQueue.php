#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_PROCESSING TASKQUEUE__', 1);
	$vs_basepath = dirname($_SERVER['SCRIPT_FILENAME']);
	if(!file_exists($vs_basepath.'/setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nprocessTaskQueue.php: processing pending tasks on the task queue.\n\nUSAGE: processTaskQueue.php 'instance_name'\nExample: ./processTaskQueue.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once($vs_basepath.'/setup.php');
	# Set environment variable to point at app config file
	require_once(__CA_LIB_DIR__."/core/TaskQueue.php");
	
	$vo_tq = new TaskQueue();
	
	$vo_tq->processQueue();
?>
