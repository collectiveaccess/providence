#!/usr/local/bin/php
<?php
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
//	if (!$argv[1]) {
//		die("\ngetOAIData.php: fetch raw data from OAI provider\n");
//	}

	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_LIB_DIR__."/ca/ImportExport/DataImporter.php");
	
	$o_config = Configuration::load();
	$o_oai_config = Configuration::load($o_config->get('oai_harvester_config'));
	$o_importer = new DataImporter();
	
	
	$va_providers = $o_oai_config->getAssoc('providers');
	
	print "\nBeginning harvest from ".sizeof($va_providers)." providers\n";
	foreach($va_providers as $vs_provider_code => $va_provider_info) {
		if (isset($va_provider_info['disabled']) && (bool)$va_provider_info['disabled']) { 
			print "\tSkipping disabled provider ".$va_provider_info['name']."\n";
			continue;
		}
		print "\tHarvesting from ".$va_provider_info['name']."...\n";
		
		if ($va_provider_info['locale']) {
			$o_importer->setLocale($va_provider_info['locale']);
		}
		$o_importer->import($va_provider_info['mapping'], $va_provider_info['url'], $va_provider_info);
	}
	
	print "\nComplete!\n";
?>