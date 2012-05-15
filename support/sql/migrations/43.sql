/* 
	Date: 17 July 2011
	Migration: 43
	Description:
*/

/*==========================================================================*/
/* Missing indices for SQL Search index table */

drop index i_word_id on ca_sql_search_word_index;
create index i_word_id on ca_sql_search_word_index(word_id, access);
create index i_field_row_id on ca_sql_search_word_index(field_row_id, field_table_num);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (43, unix_timestamp());