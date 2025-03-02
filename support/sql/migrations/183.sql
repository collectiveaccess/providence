/*
	Date: 4 April 2023
	Migration: 183
	Description:  Add checked field to entity labels
*/
/*==========================================================================*/

ALTER TABLE ca_entity_labels ADD COLUMN checked tinyint unsigned not null default 0;
CREATE INDEX i_checked ON ca_entity_labels(checked);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (183, unix_timestamp());
