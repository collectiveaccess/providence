/*
	Date: 30 November 2024
	Migration: 199
	Description: Add word position indices to search index
*/

/*==========================================================================*/

alter table ca_sql_search_word_index add column word_index int unsigned not null default 0;
alter table ca_sql_search_word_index add column word_count int unsigned not null default 0;
alter table ca_sql_search_word_index add column field_index int unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (199, unix_timestamp());
