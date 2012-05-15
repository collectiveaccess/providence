#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_REINDEXING__', 1);
	set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nupdateDimensionAttributeValues.php: Sets metric value for length and width attributes that is used for searching. Prior to 12 March 2010 code revisions, this value was not generated. This script populates those values to support range searches on dimensions .\n\nUSAGE: updateDimensionAttributeValues.php 'instance_name'\nExample: ./updateDimensionAttributeValues.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_LIB_DIR__."/ca/Attributes/Values/LengthAttributeValue.php");
	require_once(__CA_LIB_DIR__."/ca/Attributes/Values/WeightAttributeValue.php");
	
	$o_db = new Db();
	
	print "CONVERTING LENGTHS...\n";
	$t_len = new LengthAttributeValue();
	$qr_values = $o_db->query("
		SELECT cav.*, cme.*
		FROM ca_attribute_values cav
		INNER JOIN ca_metadata_elements AS cme ON cme.element_id = cav.element_id
		WHERE
			cme.datatype = 8
	");
	
	while($qr_values->nextRow()) {
		$va_row = $qr_values->getRow();
		$va_parsed_value = $t_len->parseValue($qr_values->get('value_longtext1'), $va_row);
		$o_db->query("
			UPDATE ca_attribute_values SET value_decimal1 = ? WHERE value_id = ?
		", $va_parsed_value['value_decimal1'], $qr_values->get('value_id'));
	}
	
	print "CONVERTING WEIGHTS...\n";
	$t_weight = new WeightAttributeValue();
	$qr_values = $o_db->query("
		SELECT cav.*, cme.*
		FROM ca_attribute_values cav
		INNER JOIN ca_metadata_elements AS cme ON cme.element_id = cav.element_id
		WHERE
			cme.datatype = 9
	");
	
	while($qr_values->nextRow()) {
		$va_row = $qr_values->getRow();
		$va_parsed_value = $t_weight->parseValue($qr_values->get('value_longtext1'), $va_row);
		$o_db->query("
			UPDATE ca_attribute_values SET value_decimal1 = ? WHERE value_id = ?
		", $va_parsed_value['value_decimal1'], $qr_values->get('value_id'));
	}
	
	print "DONE!\n";
?>
