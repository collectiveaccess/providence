/*
	Date: 21 January 2015
	Migration: 115
	Description: Add access inherit flags for ca_objects
*/

ALTER TABLE ca_objects ADD COLUMN access_inherit_from_parent tinyint unsigned not null;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (115, unix_timestamp());
