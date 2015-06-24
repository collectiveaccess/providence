/*
	Date: 3 March 2014
	Migration: 99
	Description: Add access and status fields
*/

ALTER TABLE ca_loans ADD COLUMN access tinyint unsigned not null default 0;
ALTER TABLE ca_movements ADD COLUMN access tinyint unsigned not null default 0;
ALTER TABLE ca_storage_locations ADD COLUMN access tinyint unsigned not null default 0;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (99, unix_timestamp());