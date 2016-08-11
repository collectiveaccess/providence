/*
	Date: 9 Aug 2016
	Migration: 139
	Description: Set defaults
*/

/*==========================================================================*/

ALTER TABLE ca_change_log MODIFY COLUMN rolledback tinyint unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (139, unix_timestamp());
