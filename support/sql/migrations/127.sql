/*
	Date: 16 January 2016
	Migration: 127
	Description: Add new sqlsearch indices
*/

/*==========================================================================*/

CREATE INDEX i_index_table_num ON ca_sql_search_word_index(word_id, table_num, row_id);
CREATE INDEX i_index_field_table_num ON ca_sql_search_word_index(word_id, table_num, field_table_num, row_id);
CREATE INDEX i_index_field_num ON ca_sql_search_word_index(word_id, table_num, field_table_num, field_num, row_id);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (127, unix_timestamp());
