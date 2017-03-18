/*
	Date: 26 February 2017
	Migration: 144
	Description: 
*/

/*==========================================================================*/

ALTER TABLE ca_relationship_types ADD COLUMN include_subtypes_left tinyint unsigned not null default 0;
ALTER TABLE ca_relationship_types ADD COLUMN include_subtypes_right tinyint unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (144, unix_timestamp());