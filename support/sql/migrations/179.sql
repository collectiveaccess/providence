/*
	Date: 6 October 2022
	Migration: 179
	Description:  Add date range to history tracking
*/

/*==========================================================================*/

ALTER TABLE ca_history_tracking_current_values ADD COLUMN value_sdatetime DECIMAL(40,20) null;
ALTER TABLE ca_history_tracking_current_values ADD COLUMN value_edatetime DECIMAL(40,20) null;

CREATE INDEX i_datetime ON ca_history_tracking_current_values(value_sdatetime, value_edatetime, table_num, row_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (179, unix_timestamp());
