/*
	Date: 3 April 2015
	Migration: 118
	Description: Add default values to task queue table
*/

ALTER TABLE ca_storage_locations ADD COLUMN is_enabled TINYINT unsigned NOT NULL DEFAULT 1;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (118, unix_timestamp());
