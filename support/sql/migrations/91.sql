/*
	Date: 26 August 2013
	Migration: 91
	Description: 
*/

ALTER TABLE ca_object_lots_x_object_representations ADD COLUMN is_primary tinyint unsigned not null default 0;
ALTER TABLE ca_loans_x_object_representations ADD COLUMN is_primary tinyint unsigned not null default 0;
ALTER TABLE ca_movements_x_object_representations ADD COLUMN is_primary tinyint unsigned not null default 0;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (91, unix_timestamp());