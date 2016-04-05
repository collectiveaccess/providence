/*
	Date: 11 March 2016
	Migration: 128
	Description: Add new sqlsearch indices
*/

/*==========================================================================*/

CREATE INDEX i_index_delete ON ca_sql_search_word_index(table_num, row_id, field_table_num, field_num);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (128, unix_timestamp());