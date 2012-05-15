#!/usr/local/bin/php
<?php
	define('__CollectiveAccess_IS_VERIFYING_STRUCTURE__', 1);
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	
	$o_dm = Datamodel::load();
	$o_db = new Db();
	$o_db->dieOnError(false);
	
	$va_class_tables = $o_dm->getTableNames();
	$va_db_tables = $o_db->getTables();
	
	// Check tables
	//print "Checking database tables...\n";
	foreach($va_class_tables as $vs_table) {
		if (!in_array($vs_table, $va_db_tables)) {
			print "ERROR: table '$vs_table' does not exist in database\n";
		}
	}
	
	foreach($va_db_tables as $vs_table) {
		if (!in_array($vs_table, $va_class_tables) && (!preg_match('!^ca_!', $vs_table)))  {
			print "WARNING: table '$vs_table' exists in database but is not part of CollectiveAccess\n";
		}
	}
	
	foreach($va_class_tables as $vs_table) {
		$t_table = $o_dm->getInstanceByTableName($vs_table);
		
		$va_class_fields = $t_table->getFields();
		if (!($va_db_fields = $o_db->getFieldsFromTable($vs_table))) {
			print "WARNING: skipping table '$vs_table' because it does not exist in database\n";
			continue;
		}
						
		$vs_class_primary_key = $t_table->primaryKey();
		
		if ($vb_class_is_primary_key != $vb_db_is_primary_key) {
			print "ERROR: field '$vs_class_field' in table '$vs_table' class primary key status does not match database primary key status: [class primary key=".($vb_class_is_primary_key ? "yes" : "no")."; db primary key=".($vb_db_is_primary_key ? "yes" : "no")."]\n";
		}
		
		$va_db_field_hash = array();
		foreach($va_db_fields as $va_field_info) {
			$va_db_field_hash[$va_field_info['fieldname']] = $va_field_info;
		}
		
		foreach($va_class_fields as $vs_class_field) {
			$va_class_field_info = $t_table->getFieldInfo($vs_class_field);
			$va_db_field_info = $va_db_field_hash[$vs_class_field];
		
			if (in_array($va_class_field_info['FIELD_TYPE'], array(FT_DATERANGE, FT_HISTORIC_DATERANGE))) {
				//
				// date range fields
				//
				
					
				// check field existance
				if (!$va_db_field_hash[$va_class_field_info['START']]) {
					print "ERROR: date range start field '".$va_class_field_info['START']."' does not exist in table '$vs_table'\n";
				}
				if (!$va_db_field_hash[$va_class_field_info['END']]) {
					print "ERROR: date range end field '".$va_class_field_info['END']."' does not exist in table '$vs_table'\n";
				}
				
			} else {
				//
				// Non-date fields
				//
				
				// check field existance
				if (!$va_db_field_hash[$vs_class_field]) {
					print "ERROR: field '$vs_class_field' does not exist in table '$vs_table'\n";
				}
			
				// check field type
				switch($va_db_field_info['type']) {
					case 'int':
					case 'float':
						if (!in_array($va_class_field_info['FIELD_TYPE'], array(FT_NUMBER, FT_BIT, FT_DATETIME, FT_TIME, FT_HISTORIC_DATE, FT_HISTORIC_DATETIME, FT_TIMESTAMP, FT_TIMECODE))) {
							print "ERROR: field '$vs_class_field' in table '$vs_table' is not compatible with database field type '".$va_db_field_info['type']."'\n";
						}
						break;
					case 'varchar':
					case 'char':
					case 'text':
						if (!in_array($va_class_field_info['FIELD_TYPE'], array(FT_TEXT, FT_PASSWORD, FT_VARS, FT_MEDIA, FT_FILE))) {
							print "ERROR: field '$vs_class_field' in table '$vs_table' is not compatible with database field type '".$va_db_field_info['type']."'\n";
						}
						break;
					case 'blob':
						if (!in_array($va_class_field_info['FIELD_TYPE'], array(FT_VARS, FT_MEDIA, FT_FILE))) {
							print "ERROR: field '$vs_class_field' in table '$vs_table' is not compatible with database field type '".$va_db_field_info['type']."'\n";
						}
						break;
					default:
						print "WARNING: Unknown database field type '".$va_db_field_info['type']."' for field '$vs_class_field'\n";
						break;
				}
				
				// check field bounds
				
				
				
				// check nullability
				$vb_class_is_nullable = ($va_class_field_info['IS_NULL']) ? true : false;
				$vb_db_is_nullable = $va_db_field_info['null'] ? true : false;
				
				if ($vb_class_is_nullable != $vb_db_is_nullable) {
					print "ERROR: field '$vs_class_field' in table '$vs_table' class null status does not match database null status: [class null=".($vb_class_is_nullable ? "yes" : "no")."; db null=".($vb_db_is_nullable ? "yes" : "no")."]\n";
				}
				
				// check identity
				$vb_class_is_identity = ($va_class_field_info['IDENTITY']) ? true : false;
				
				$vb_db_is_identity = false;
				if (is_array($va_db_field_info['options'])) {
					foreach($va_db_field_info['options'] as $vs_opt) {
						if ($vs_opt == 'identity') { $vb_db_is_identity = true; }
					}
				}
				
				if ($vb_class_is_identity != $vb_db_is_identity) {
					print "ERROR: field '$vs_class_field' in table '$vs_table' class identity status does not match database identity status: [class identity=".($vb_class_is_identity ? "yes" : "no")."; db identity=".($vb_db_is_identity ? "yes" : "no")."]\n";
				}
								
				// check primary key
				$vb_db_is_primary_key = ($va_db_field_info['index'] == 'primary') ? true : false;
				if ($vb_db_is_primary_key) {
					if ($vs_class_field !== $vs_class_primary_key) {
						print "ERROR: field '$vs_class_field' in table '$vs_table' should be, but is not, primary key in database\n";	
					}
				}
			}
		}
	}
?>
