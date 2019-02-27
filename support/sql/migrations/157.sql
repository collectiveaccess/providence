/*
	Date: 25 February 2019
	Migration: 157
	Description: Extend SQL Search field_num
*/

/*==========================================================================*/

alter table ca_sql_search_word_index modify column field_num varchar(100) not null;


/*==========================================================================*/


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (157, unix_timestamp());
