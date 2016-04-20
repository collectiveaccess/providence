/*
	Date: 5 April 2016
	Migration: 129
	Description: Remove unused table
*/

/*==========================================================================*/

DROP TABLE ca_editor_ui_bundle_placement_type_restrictions;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (129, unix_timestamp());