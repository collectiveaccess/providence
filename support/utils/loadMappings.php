#!/usr/local/bin/php
<?php
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nloadMappings.php: load data import/export mappings from definition file\n");
	}

	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_locales.php");
	require_once(__CA_MODELS_DIR__."/ca_lists.php");
	require_once(__CA_MODELS_DIR__."/ca_bundle_mappings.php");
	
	$o_config = Configuration::load();
	$pa_locale_defaults = $o_config->getList('locale_defaults');
	$ps_locale = $pa_locale_defaults[0];
	$t_locale = new ca_locales();
	$pn_locale_id = $t_locale->localeCodeToID($ps_locale);
	
	$o_mapping_def = Configuration::load($argv[1]);
	$o_dm = Datamodel::load();
	$t_mapping = new ca_bundle_mappings();
	$t_list = new ca_lists();
	
	$_debug = true;
	
	$o_db = new Db();
	
	if ($_debug) {
		$o_db->query("truncate table ca_bundle_mapping_relationships");
		$o_db->query("truncate table ca_bundle_mapping_labels");
		$o_db->query("truncate table ca_bundle_mappings");
	}
	
	foreach($o_mapping_def->getAssocKeys() as $vs_mapping_code) {
		$va_mapping_conf = $o_mapping_def->getAssoc($vs_mapping_code);
		
		if (isset($va_mapping_conf['mapping_groups']) && is_array($va_mapping_conf['mapping_groups']) && sizeof($va_mapping_conf['mapping_groups'])) {
			if (!($vn_table_num = $o_dm->getTableNum($va_mapping_conf['table']))) { 
				print "WARNING: Invalid table specified for {$vs_mapping_code}!\n";
				continue;
			}
			
			// create mapping record
			$t_mapping->setMode(ACCESS_WRITE);
			$t_mapping->set('direction', $va_mapping_conf['direction']);
			$t_mapping->set('target', $va_mapping_conf['target']);
			$t_mapping->set('table_num', $vn_table_num);
			$t_mapping->set('mapping_code', $vs_mapping_code);
			$t_mapping->set('settings', $va_mapping_conf['settings']);
			
			$t_mapping->insert();
			
			if ($t_mapping->numErrors()) {
				print "ERROR: Couldn't insert mapping: ".join('; ', $t_mapping->getErrors())."\n";
				continue;
			}
			
			$t_mapping->addLabel(
				array('name' => ($va_mapping_conf['label'] ? $va_mapping_conf['label'] : $vs_mapping_code), 'description' => $va_mapping_conf['description']), $pn_locale_id, null, true
			);
			
			if ($t_mapping->numErrors()) {
				print "ERROR: Couldn't add label to mapping: ".join('; ', $t_mapping->getErrors())."\n";
				continue;
			}
			
			$vn_mapping_id = $t_mapping->getPrimaryKey();
			
			foreach($va_mapping_conf['mapping_groups'] as $vs_group => $va_group_info) {
				$vn_type_id = null;
				if ($va_group_info['type']) {
					$vn_type_id = $t_list->getItemIDFromList('object_types', $va_group_info['type']);
				}
				
				$va_group_settings = is_array($va_group_info['settings']) ? $va_group_info['settings'] : array();
				
				foreach($va_group_info['mappings'] as $vs_n => $va_mapping) {
					$t_mapping_rel = new ca_bundle_mapping_relationships();
					$t_mapping_rel->setMode(ACCESS_WRITE);
					$t_mapping_rel->set('mapping_id', $vn_mapping_id);
					$t_mapping_rel->set('bundle_name', $va_group_info['bundle']);
					$t_mapping_rel->set('element_name', $va_mapping['bundle']);
					$t_mapping_rel->set('destination', $va_group_info['destination'].$va_mapping['destination']);
					$t_mapping_rel->set('group_code', $vs_group);
					$t_mapping_rel->set('type_id', $vn_type_id);
					$t_mapping_rel->set('settings', array_merge($va_group_settings, $va_mapping['settings'] ? $va_mapping['settings'] : array()));
					
					$t_mapping_rel->insert();
					
					if ($t_mapping_rel->numErrors()) {
						print "ERROR: Couldn't insert mapping relationship: ".join('; ', $t_mapping_rel->getErrors())."\n";
						continue;
					}
				}
			}
		} else {
			print "WARNING: No mappings to import for {$vs_mapping_code}!\n";
		}
	}
?>
