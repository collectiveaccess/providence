/*
	Date: 7 May 2014
	Migration: 102
	Description: deaccession tracking
*/

/* Add deaccession fields for objects */
ALTER TABLE ca_objects ADD COLUMN is_deaccessioned tinyint not null default 0;
ALTER TABLE ca_objects ADD COLUMN deaccession_notes text not null;
ALTER TABLE ca_objects ADD COLUMN deaccession_type_id int unsigned null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (102, unix_timestamp());