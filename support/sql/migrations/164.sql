/*
	Date: 30 May 2020
	Migration: 164
	Description:    Sql Search Word index with locale
*/

/*==========================================================================*/

drop index u_word on ca_sql_search_words;
create unique index u_word on ca_sql_search_words(word,locale_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (164, unix_timestamp());
