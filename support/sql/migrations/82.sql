/*
	Date: 30 March 2013
	Migration: 82
	Description: Compatibility with MySQL 5.6 strict
*/

ALTER TABLE ca_object_representations MODIFY COLUMN media_content longblob null;
ALTER TABLE ca_object_representations MODIFY COLUMN media_metadata longblob null;
ALTER TABLE ca_sql_search_word_index MODIFY COLUMN field_num smallint unsigned not null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (82, unix_timestamp());