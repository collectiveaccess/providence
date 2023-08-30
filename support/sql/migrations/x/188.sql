/*
	Date: 16 August 2023
	Migration: 188
	Description: Raise limit on source field to allow for large constants
*/
/*==========================================================================*/

ALTER TABLE ca_data_importer_items MODIFY COLUMN source varchar(8192) NOT NULL;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (188, unix_timestamp());
