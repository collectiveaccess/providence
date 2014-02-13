/*
	Date: 13 February 2014
	Migration: 97
	Description: Add location field to ca_item_comments
*/

ALTER TABLE ca_item_comments ADD COLUMN location varchar(255) null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (97, unix_timestamp());