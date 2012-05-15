/* 
	Date: 9 March 2012
	Migration: 57
	Description:
*/

/* Support for "soft" delete of representations */
ALTER TABLE ca_object_representations ADD COLUMN deleted tinyint unsigned not null default 0;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (57, unix_timestamp());