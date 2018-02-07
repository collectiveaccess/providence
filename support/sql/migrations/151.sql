/*
	Date: 9 December 2017
	Migration: 151
	Description: Extend SQL search index all for container-aware searching
*/

ALTER TABLE ca_sql_search_word_index ADD column field_container_id int unsigned null;
CREATE index i_index_field_num_container on ca_sql_search_word_index(word_id, table_num, field_table_num, field_num, field_container_id, row_id, access, boost);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (151, unix_timestamp());