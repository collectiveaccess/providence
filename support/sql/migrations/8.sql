/* 
	Date: 28 December 2009
	Migration: 8
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	form_code in ca_search_forms needs to be optional
*/
ALTER TABLE ca_search_forms MODIFY COLUMN form_code	varchar(100) null;

/* -------------------------------------------------------------------------------- */
/*
	set_code in ca_search_forms needs to be optional
*/
ALTER TABLE ca_sets MODIFY COLUMN set_code	varchar(100) null;

/* -------------------------------------------------------------------------------- */
/*
	clean up logging table (drop un-needed fields and change unit_id to 32 chars to store md5)
*/
ALTER TABLE ca_change_log DROP COLUMN unit_type;
ALTER TABLE ca_change_log DROP COLUMN remarks;
ALTER TABLE ca_change_log DROP COLUMN user_data;
ALTER TABLE ca_change_log MODIFY COLUMN unit_id char(32) null;
ALTER TABLE ca_change_log ADD COLUMN rolledback tinyint unsigned not null;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (8, unix_timestamp());
