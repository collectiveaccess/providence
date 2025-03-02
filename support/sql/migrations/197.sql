/*
	Date: 29 May 2024
	Migration: 197
	Description: Add indexes to improve performance of created: and modified: searches
*/

/*==========================================================================*/

create index i_created_on on ca_change_log(logged_table_num, changetype, log_datetime);
create index i_modified_on on ca_change_log(logged_table_num, log_datetime);
create index i_modified_on on ca_change_log_subjects(log_id, subject_table_num);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (197, unix_timestamp());
