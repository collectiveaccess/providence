/*
	Date: 4 April 2018
	Migration: 153
	Description: Add indexing queue started_on field
*/

ALTER TABLE ca_search_indexing_queue ADD COLUMN started_on int unsigned null;
CREATE INDEX i_started_on ON ca_search_indexing_queue(started_on);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (153, unix_timestamp());
