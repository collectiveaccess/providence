/*
	Date: 15 July 2015
	Migration: 121
	Description: Make sure LONGTEXT columns in search indexing queue are NULL,
	  so that everyone who applied migration 120.sql before we changed it to work
	  with strict settings is on the same page as everyone else
*/

/*==========================================================================*/

ALTER TABLE ca_search_indexing_queue MODIFY field_data LONGTEXT null;
ALTER TABLE ca_search_indexing_queue MODIFY changed_fields TEXT null;
ALTER TABLE ca_search_indexing_queue MODIFY options LONGTEXT null;
ALTER TABLE ca_search_indexing_queue MODIFY dependencies LONGTEXT null;

/* Always add the update to ca_schema_updates at the end of the file */
# noinspection SqlNoDataSourceInspection
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (121, unix_timestamp());
