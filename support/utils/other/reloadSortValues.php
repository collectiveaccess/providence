#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_REINDEXING__', 1);
	set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nreloadSortValues.php: Regenerates content is name_sort and idno_sort fields for objects, object lots, entities, places, occurrences, collections, storage locations, object representations, representation annotations and list items. Primarily intended for repairing data created before we started populating sort fields. If you don't know why you're running this, you shouldn't be running it.\n\nUSAGE: reloadSortValues.php 'instance_name'\nExample: ./reloadSortValues.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	
	$o_db = new Db();
	
	foreach(array(
		'ca_objects', 'ca_object_lots', 'ca_places', 'ca_entities',
		'ca_occurrences', 'ca_collections', 'ca_storage_locations',
		'ca_object_representations', 'ca_representation_annotations',
		'ca_list_items'
	) as $vs_table) {
		
		require_once(__CA_MODELS_DIR__."/".$vs_table.".php");
		$t_table = new $vs_table;
		$vs_pk = $t_table->primaryKey();
		$qr_res = $o_db->query('SELECT '.$vs_pk.' FROM '.$vs_table);
		
		if ($vs_label_table_name = $t_table->getLabelTableName()) {
			require_once(__CA_MODELS_DIR__."/".$vs_label_table_name.".php");
			$t_label = new $vs_label_table_name;
			$vs_label_pk = $t_label->primaryKey();
			$qr_labels = $o_db->query('SELECT '.$vs_label_pk.' FROM '.$vs_label_table_name);
			
			print "PROCESSING {$vs_label_table_name}\n";
			while($qr_labels->nextRow()) {
				$vn_label_pk_val = $qr_labels->get($vs_label_pk);
				print "\tUPDATING LABEL [{$vn_label_pk_val}]\n";
				if ($t_label->load($vn_label_pk_val)) {
					$t_label->setMode(ACCESS_WRITE);
					$t_label->update();
				}
			}
		}
		
		print "PROCESSING {$vs_table}\n";
		while($qr_res->nextRow()) {
			$vn_pk_val = $qr_res->get($vs_pk);
			print "\tUPDATING [{$vn_pk_val}]\n";
			if ($t_table->load($vn_pk_val)) {
				$t_table->setMode(ACCESS_WRITE);
				$t_table->update();
			}
		}
	}
?>
