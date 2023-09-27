/*
	Date: 20 September 2023
	Migration: 192
	Description: Expand maximum length of history current value tracking label sort value
*/

/*==========================================================================*/

ALTER TABLE ca_history_tracking_current_value_labels MODIFY COLUMN value_sort varchar(1024) not null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (192, unix_timestamp());
