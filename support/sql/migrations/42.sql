/* 
	Date: 5 July 2011
	Migration: 42
	Description:
*/

/*==========================================================================*/
/* Taskqueue item-level locking*/

ALTER TABLE ca_task_queue ADD COLUMN started_on int unsigned null;
CREATE INDEX i_started_on ON ca_task_queue(started_on);

/* Access-sensitive indexing */
ALTER TABLE ca_sql_search_word_index ADD COLUMN access tinyint unsigned not null default '1';


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (42, unix_timestamp());