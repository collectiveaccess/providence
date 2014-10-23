/*
	Date: 26 October 2013
	Migration: 93
	Description: Add settings field to ca_list_items
*/

alter table ca_list_items add column settings longtext not null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (93, unix_timestamp());