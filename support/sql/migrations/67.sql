/* 
	Date: 20 August 2012
	Migration: 67
	Description:
*/

alter table ca_users add column registered_on int unsigned null;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (67, unix_timestamp());