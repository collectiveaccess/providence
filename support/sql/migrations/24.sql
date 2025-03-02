/* 
	Date: 22 September 2010
	Migration: 24
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_lists ADD COLUMN default_sort tinyint unsigned not null;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (24, unix_timestamp());
