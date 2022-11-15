/*
	Date: 9 August 2022
	Migration: 178
	Description:  Add additional covering indexes for set/blank and count queries
*/

/*==========================================================================*/

DROP INDEX i_index_field_num_container ON ca_sql_search_word_index;
CREATE INDEX i_index_field_num_container on ca_sql_search_word_index (word_id, table_num, field_table_num, field_num, field_container_id, rel_type_id, row_id, access, boost);
CREATE INDEX i_field_word on ca_sql_search_word_index (field_num, field_table_num, table_num, word_id, row_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (178, unix_timestamp());
