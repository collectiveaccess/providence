/* 
	Date: 10 October 2010
	Migration: 27
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Error flag for task queue
*/
ALTER TABLE ca_task_queue ADD COLUMN error_code smallint unsigned not null;
CREATE INDEX i_error_code ON ca_task_queue(error_code);


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (27, unix_timestamp());
