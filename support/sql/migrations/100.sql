/*
	Date: 5 March 2014
	Migration: 100
	Description: add is_current fields for location tracking
*/

ALTER TABLE ca_movements_x_objects ADD COLUMN is_current tinyint unsigned not null default 0;
ALTER TABLE ca_objects_x_storage_locations ADD COLUMN is_current tinyint unsigned not null default 0;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (100, unix_timestamp());