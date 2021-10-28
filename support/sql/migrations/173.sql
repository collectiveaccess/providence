/*
	Date: 9 October 2021
	Migration: 173
	Description:    Add attribute source field
*/

/*==========================================================================*/

ALTER TABLE ca_attributes ADD COLUMN value_source varchar(1024) null default null;
CREATE INDEX i_value_source ON ca_attributes(value_source);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (173, unix_timestamp());
