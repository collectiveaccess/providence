/*
	Date: 6 August 2014
	Migration: 105
	Description: add deleted field to ca_lists
*/

alter table ca_lists add column deleted tinyint unsigned not null default 0;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (105, unix_timestamp());