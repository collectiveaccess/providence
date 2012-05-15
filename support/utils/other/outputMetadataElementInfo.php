#!/usr/local/bin/php
<?php
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\noutputMetadataElementInfo.php: Outputs information about configured metadata elements in tab-delimited format suitable for viewing in an application such as Excel.\n\nUSAGE: outputMetadataElementInfo.php 'instance_name'\nExample: ./outputMetadataElementInfo.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
	
	$t_element = new ca_metadata_elements();
	$o_dm = Datamodel::load();
	$o_config = Configuration::load();
	$o_attribute_types = Configuration::load($o_config->get('attribute_type_config'));
	$va_attribute_types = $o_attribute_types->getList('types');
	
	
	$va_elements = $t_element->getRootElementsAsList();
	
	$va_elements_by_table = array();
	foreach($va_elements as $va_element) {
		$va_line = array('', $va_element['element_code'], $va_attribute_types[$va_element['datatype']]);
		if ($t_element->load($va_element['element_id'])) {
			$va_labels = caExtractValuesByUserLocale($t_element->getPreferredLabels());
			
			foreach($va_labels as $vn_id => $va_labels) {
				foreach($va_labels as $va_label) {
					$va_line[] = $va_label['name'];
					$va_line[] = $va_label['description'];
				}
				break;
			}
			
			foreach($va_restrictions = $t_element->getTypeRestrictions() as $va_restriction) {
				$va_elements_by_table[$va_restriction['table_num']][$va_element['element_code']] = $va_line;
			}
		}
	}
	
	print join("\t", array('Code', 'Datatype', 'name', 'description'))."\n";
	foreach($va_elements_by_table as $vn_table_num => $va_elements) {
		$t_table = $o_dm->getInstanceByTableNum($vn_table_num);
		print $t_table->getProperty('NAME_PLURAL')."\n";
		
		foreach($va_elements as $vs_element_code => $va_element_info) {
			print join("\t", $va_element_info)."\n";
		}
	}
?>
