/*
	Date: 2 April 2017
	Migration: 146
	Description: Increase maximum length for entity surnames
*/

ALTER TABLE ca_entity_labels MODIFY COLUMN surname varchar(512) not null default '';

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (146, unix_timestamp());