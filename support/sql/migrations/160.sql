/*
	Date: 19 April 2019
	Migration: 160
	Description:    Make u_all index more selective
*/

/*==========================================================================*/

DROP INDEX u_all ON ca_history_tracking_current_values;
CREATE INDEX u_all ON ca_history_tracking_current_values(row_id, table_num, policy, type_id, is_future);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (160, unix_timestamp());
