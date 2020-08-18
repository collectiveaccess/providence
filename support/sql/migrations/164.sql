/*
	Date: 18 Aug 2020
	Migration: 164
	Description:    Add index to task queue handler column.
*/

/*==========================================================================*/

create index i_handler on ca_task_queue(handler);


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (164, unix_timestamp());
