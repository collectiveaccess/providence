/*
	Date: 11 December 2013
	Migration: 96
	Description: Add primary key to ca_sql_search_word_index entries in order to support phrase search
*/

TRUNCATE TABLE ca_sql_search_word_index;
TRUNCATE TABLE ca_sql_search_words;
TRUNCATE TABLE ca_sql_search_ngrams;

ALTER TABLE ca_sql_search_word_index ADD COLUMN index_id int unsigned not null primary key auto_increment;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (96, unix_timestamp());