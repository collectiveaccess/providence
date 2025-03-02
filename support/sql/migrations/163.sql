/*
	Date: 19 May 2020
	Migration: 163
	Description:    Expand limit for task queue "notes" field
*/

/*==========================================================================*/

ALTER TABLE ca_task_queue MODIFY COLUMN notes longtext null;


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (163, unix_timestamp());
