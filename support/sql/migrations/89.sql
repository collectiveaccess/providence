/*
	Date: 20 July 2013
	Migration: 89
	Description: 
*/

ALTER TABLE ca_object_representations ADD COLUMN media_content_locations LONGBLOB null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (89, unix_timestamp());