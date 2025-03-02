/*
	Date: 16 July 2013
	Migration: 88
	Description: 
*/

ALTER TABLE ca_data_importers ADD COLUMN rules LONGTEXT not null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (88, unix_timestamp());