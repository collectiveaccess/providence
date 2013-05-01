/* 
	Date: 29 December 2012
	Migration: 75
	Description:
*/

/* --------------------------- Batch edit log --------------------------- */

alter table ca_batch_log add column elapsed_time int unsigned not null default 0;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (75, unix_timestamp());