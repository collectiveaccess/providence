/*
	Date: Pi Day! 14 March 2015
	Migration: 117
	Description: Add default values to task queue table
*/

ALTER TABLE ca_task_queue MODIFY COLUMN priority INT unsigned NOT NULL DEFAULT 0;
ALTER TABLE ca_task_queue MODIFY COLUMN error_code INT unsigned NOT NULL DEFAULT 0;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (117, unix_timestamp());
