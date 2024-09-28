/*
	Date: 18 June 2024
	Migration: 198
	Description: 
*/

/*==========================================================================*/

ALTER TABLE ca_search_indexing_queue ADD COLUMN priority tinyint unsigned not null default 100;
CREATE INDEX i_priority ON ca_search_indexing_queue (priority);
DROP INDEX i_started_on ON ca_search_indexing_queue;
CREATE INDEX i_started_on ON ca_search_indexing_queue(started_on, priority);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (198, unix_timestamp());
