#!/usr/local/bin/php
<?php
	set_time_limit(24 * 60 * 60 * 7); /* maximum generation time: 7 days :-) */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\ngenerateSchemaDoc.php: generates HTML text description of CollectiveAccess database schema.\nUSAGE: php generateSchemaDoc.php myhostname.com");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	
	$va_datatypes = array(
		0 => "NUMBER",
		1 => "TEXT",
		2 => "TIMESTAMP",
		3 => "DATETIME",
		4 => "HISTORIC DATETIME",
		5 => "DATERANGE",
		6 => "HISTORIC DATERANGE",
		7 => "BIT",
		8 => "FILE",
		9 => "MEDIA",
		10 => "PASSWORD", 
		11 => "SERIALIZED VARIABLES",
		12 => "TIMECODE",
		13 => "DATE",
		14 => "HISTORIC DATE",
		15 => "TIME",
		16 => "TIME RANGE"
	);
	
	$va_displaytypes = array(
		0 => "DROP-DOWN MENU",
		1 => "LIST",
		2 => "LIST WITH MULTIPLE SELECTION",
		3 => "CHECKBOXES",
		4 => "RADIO BUTTONS",
		5 => "TEXT INPUT FIELD",
		6 => "HIDDEN",
		7 => "OMIT - NOT SHOWN",
		8 => "TEXT DISPLAY",
		9 => "PASSWORD",
		10 => "COLORPICKER",
		12 => "TIMECODE"
	);
	
	$o_dm = Datamodel::load();
			
	$va_tables = $o_dm->getTableNames();
?>
<html>
<head>
	<title>CollectiveAccess schema version <?php print __CollectiveAccess__;?>; revision <?php print __CollectiveAccess_Schema_Rev__; ?></title>
	 <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
</head>
<body>
<?php
	foreach($va_tables as $vs_table) {
		$t_table = $o_dm->getInstanceByTableName($vs_table);
		
		print "<h2>Table ".$t_table->tableName().": ".$t_table->getProperty('NAME_PLURAL')."</h2>\n";
		
		print "<h3>Fields</h3>\n";
		
		print "<table cellpadding='5' cellspacing='5' border='1'>\n";
		print "<th>Field</th><th>Name</th><th>Description</th><th>Datatype</th><th>Null?</th><th>Default</th><th>Validation constraints</th><th>Display settings</th><th>Relationships</th></tr>\n";
		$va_fields = $t_table->getFields();
		foreach($va_fields as $vs_field) {
			print "<tr>";
			$va_field_info = $t_table->getFieldInfo($vs_field);
			print "<td>{$vs_field}</td><td>".$va_field_info['LABEL']."</td><td>".$va_field_info['DESCRIPTION']."</td>";
			print "<td>".$va_datatypes[$va_field_info['FIELD_TYPE']]."</td>";
			print "<td>".($va_field_info['IS_NULL'] ? "NULL" : "-")."</td>";
			print "<td>".($va_field_info['DEFAULT'] ? $va_field_info['DEFAULT'] : '-')."</td>";
			
			$va_validation_constraints = array();
			if (isset($va_field_info['BOUNDS_CHOICE_LIST']) && (is_array($va_field_info['BOUNDS_CHOICE_LIST']))) {
				if (isset($va_field_info['LIST']) && $va_field_info['LIST']) {
					$va_validation_constraints[] = "<li>Choice list using ca_list with list_code = ".$va_field_info['LIST']. " and a hardcoded default list with ".sizeof($va_field_info['BOUNDS_CHOICE_LIST'])." options</li>";
				} else {
					$va_validation_constraints[] = "<li>Choice list (".sizeof($va_field_info['BOUNDS_CHOICE_LIST'])." options)</li>";
				}
			}
			
			if (isset($va_field_info['BOUNDS_VALUE']) && (is_array($va_field_info['BOUNDS_VALUE']))) {
				$va_validation_constraints[] = "<li>Minimum value is ".$va_field_info['BOUNDS_VALUE'][0]."</li>";
				$va_validation_constraints[] = "<li>Maximum value is ".$va_field_info['BOUNDS_VALUE'][1]."</li>";
			}
			
			if (isset($va_field_info['BOUNDS_LENGTH']) && (is_array($va_field_info['BOUNDS_LENGTH']))) {
				$va_validation_constraints[] = "<li>Minimum length is ".$va_field_info['BOUNDS_LENGTH'][0]."</li>";
				$va_validation_constraints[] = "<li>Maximum length is ".$va_field_info['BOUNDS_LENGTH'][1]."</li>";
			}
			
			print "<td><ul>".join("\n", $va_validation_constraints)."</ul></td>";
			
			$va_display_settings = array();
			$va_display_settings[] = "<li>Display as ".$va_displaytypes[$va_field_info['DISPLAY_TYPE']]."</li>";
			$va_display_settings[] = "<li>Display width ".$va_field_info['DISPLAY_WIDTH']." characters</li>";
			$va_display_settings[] = "<li>Display height ".$va_field_info['DISPLAY_HEIGHT']." lines</li>";
			print "<td><ul>".join("\n", $va_display_settings)."</ul></td>";
			
			$va_rel = $o_dm->getManyToOneRelations($vs_table, $vs_field);
			$va_relationships = array();
			if ($va_rel['one_table'] == $vs_table) {
				$va_relationships[] = "<li>1:many relationship with ".$va_rel['many_table'].'.'.$va_rel['many_table_field']."</li>";
			}
			if ($va_rel['many_table'] == $vs_table) {
				$va_relationships[] = "<li>many:1 relationship with ".$va_rel['one_table'].'.'.$va_rel['one_table_field']."</li>";
			}
			print "<td><ul>".join("\n", $va_relationships)."</ul></td>";
			
			print "</tr>\n";
		}
		print "</table>\n";
		
		
		print "<hr/>\n";
	}
?>
</body>
</html>