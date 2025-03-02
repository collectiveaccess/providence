/*
	Date: 11 April 2017
	Migration: 147
	Description: Increase maximum length for data import event type code
*/

 
ALTER TABLE ca_data_import_events MODIFY COLUMN type_code varchar(50) not null default '';

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (147, unix_timestamp());