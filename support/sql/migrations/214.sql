/*
	Date: 15 September 2026
	Migration: 214
	Description: 
*/

/*==========================================================================*/

ALTER TABLE ca_editor_uis ADD COLUMN settings longtext not null;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (214, unix_timestamp());
