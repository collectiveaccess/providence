/*
	Date: 22 October 2021
	Migration: 174
	Description:    Add metadata element soft delete
*/

/*==========================================================================*/

ALTER TABLE ca_metadata_elements ADD COLUMN deleted tinyint not null default 0;
CREATE INDEX i_deleted ON ca_metadata_elements(deleted);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (174, unix_timestamp());
