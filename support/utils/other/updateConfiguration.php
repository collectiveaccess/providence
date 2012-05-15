#!/usr/local/bin/php
<?php
	set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nupdateConfiguration.php: applies updates to attribute, list and UI configuration for specified CollectiveAccess instance.\n\nUSAGE: reindex.php 'instance_name'\nExample: ./updateConfiguration.php 'www.mycollection.org' /path/to/update/file\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');
	require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	
	$vs_config_file = $argv[2];
	if (!file_exists($vs_config_file)) { die("Input file '{$vs_config_file}' does not exist\n"); }
	
	$o_config = Configuration::load($vs_config_file);
	
	$va_to_add = $o_config->getAssoc('add');
	
	caConfigAddLists($va_to_add['lists']);
	caConfigAddMetadataElementSets($va_to_add['element_sets']);
	caConfigAddUIs($va_to_add['uis']);
	caConfigAddRelationshipTypes($va_to_add['relationship_types']);
?>
