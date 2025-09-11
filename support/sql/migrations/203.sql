/*
	Date: 27 May 2025
	Migration: 203
	Description: Expand indices to include field_index
*/

/*==========================================================================*/
DROP INDEX i_index_field_num on ca_sql_search_word_index;
DROP INDEX i_index_field_num_container on ca_sql_search_word_index;
CREATE INDEX i_index_field_num on ca_sql_search_word_index(word_id, table_num, field_table_num, field_num, row_id, access, boost, field_index);
CREATE INDEX i_index_field_num_container on ca_sql_search_word_index(word_id, table_num, field_table_num, field_num, field_container_id, rel_type_id, row_id, access, boost, field_index);
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (203, unix_timestamp());
