/* 
	Date: 18 September 2009
	Migration: 3
	Description:
*/

ALTER TABLE ca_object_representations ADD COLUMN is_template tinyint unsigned not null;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (3, unix_timestamp());