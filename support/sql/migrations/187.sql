/*
	Date: 10 August 2023
	Migration: 187
	Description: Lengthen parameters field to avoid overflows
*/
/*==========================================================================*/

ALTER TABLE ca_task_queue MODIFY COLUMN parameters longtext NOT NULL;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (187, unix_timestamp());
