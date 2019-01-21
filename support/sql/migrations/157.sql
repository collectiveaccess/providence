/*
	Date: 14 January 2019
	Migration: 157
	Description: Set null change log units
*/

/*==========================================================================*/


UPDATE ca_change_log SET unit_id = MD5(log_id) WHERE unit_id IS NULL;

CREATE INDEX i_log_plus on ca_change_log_subjects (log_id, subject_table_num, subject_row_id);
CREATE INDEX i_date_unit on ca_change_log(log_datetime, unit_id); 


/* Reset field settings that may be incorrect in older installations */
ALTER TABLE ca_search_indexing_queue MODIFY COLUMN changed_fields longtext null;
ALTER TABLE ca_set_items MODIFY COLUMN rank int unsigned not null default 0;

/*==========================================================================*/


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (157, unix_timestamp());
