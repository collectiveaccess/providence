/* 
	Date: 17 July 2010
	Migration: 18
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_change_log MODIFY COLUMN snapshot LONGBLOB not null;


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (18, unix_timestamp());
