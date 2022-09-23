/*
	Date: 23 September 2022
	Migration: 179
	Description:  Add todo list "checked" field for set items
*/

/*==========================================================================*/

ALTER TABLE ca_set_items ADD COLUMN checked tinyint unsigned not null default 0;
CREATE INDEX i_checked_id ON ca_set_items(set_id, checked);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (179, unix_timestamp());
