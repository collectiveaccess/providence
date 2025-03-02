/*
	Date: 16 June 2016
	Migration: 134
	Description: Add support for floorpans
*/

/*==========================================================================*/

ALTER TABLE ca_places ADD  `floorplan` LONGBLOB NOT NULL;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (134, unix_timestamp());
