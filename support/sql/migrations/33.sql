/* 
	Date: 14 January 2011
	Migration: 33
	Description:
*/


ALTER TABLE ca_bundle_mappings ADD COLUMN access tinyint not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (33, unix_timestamp());
