<?php

if(!file_exists('./setup.php')) {
	die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
}

require_once("./setup.php");
require_once(__CA_LIB_DIR__."/core/Search/Solr/SolrConfiguration.php");

SolrConfiguration::updateSolrConfiguration(true);

