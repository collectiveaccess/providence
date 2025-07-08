/*
	Date: 4 July 2025
	Migration: 204
	Description: Add fields to support auto-deletion of sets
*/

/*==========================================================================*/

ALTER TABLE ca_sets ADD COLUMN last_used int unsigned null;
ALTER TABLE ca_sets ADD COLUMN autodelete tinyint unsigned not null default 0;
UPDATE ca_sets SET last_used = unix_timestamp();

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (204, unix_timestamp());
