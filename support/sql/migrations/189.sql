/*
	Date: 11 August 2023
	Migration: 189
	Description: Resolve the differences between legacy data model and current data model
*/

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (189, unix_timestamp());

