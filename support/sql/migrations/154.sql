/*
	Date: 6 March 2018
	Migration: 154
	Description: Add notification fields to track reading and uniqueness
*/

/*==========================================================================*/

ALTER TABLE ca_notifications ADD COLUMN notification_key char(32) not null default '';
CREATE INDEX i_notification_key ON ca_notifications(notification_key);

ALTER TABLE ca_notification_subjects ADD COLUMN read_on int unsigned null;
DROP INDEX i_table_num_row_id ON ca_notification_subjects;
CREATE INDEX i_table_num_row_id ON ca_notification_subjects(table_num, row_id, read_on);

/*==========================================================================*/


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (154, unix_timestamp());
