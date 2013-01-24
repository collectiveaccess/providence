/* 
	Date: 25 October 2012
	Migration: 74
	Description:
*/

ALTER TABLE ca_data_exporters ADD COLUMN vars longtext not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (74, unix_timestamp());