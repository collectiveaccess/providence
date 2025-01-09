/*
	Date: 8 January 2025
	Migration: 200
	Description: Add checked field to set items
*/

/*==========================================================================*/

alter table ca_set_items add column checked tinyint unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (200, unix_timestamp());
