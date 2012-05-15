/* 
	Date: 2 January 2010
	Migration: 9
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	add field for performance and source to search log
*/
ALTER TABLE ca_search_log ADD COLUMN execution_time decimal(7,3) not null;
ALTER TABLE ca_search_log ADD COLUMN search_source varchar(40) not null;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (9, unix_timestamp());
