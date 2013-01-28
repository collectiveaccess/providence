/* 
	Date: 9 January 2013
	Migration: 77
	Description:
*/


/* -------------------------------------------------------------------------------- */

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (77, unix_timestamp());