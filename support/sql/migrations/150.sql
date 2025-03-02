/*
	Date: 20 October 2017
	Migration: 150
	Description: Add new SQL search index to improve front-end performance
*/

DROP index i_index_field_num ON ca_sql_search_word_index;
CREATE index i_index_field_num on ca_sql_search_word_index(word_id, table_num, field_table_num, field_num, row_id, access, boost);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (150, unix_timestamp());