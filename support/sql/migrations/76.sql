/* 
	Date: 30 December 2012
	Migration: 76
	Description:
*/

/* --------------------------- SMS notifications --------------------------- */

alter table ca_users add column sms_number varchar(30) not null default '';

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (76, unix_timestamp());