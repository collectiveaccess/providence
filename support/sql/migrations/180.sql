/*
	Date: 25 November 2022
	Migration: 180
	Description:  Add date range to history tracking
*/

/*==========================================================================*/

ALTER TABLE ca_object_representations ADD COLUMN media_class varchar(20) null;
CREATE INDEX i_media_class ON ca_object_representations(media_class);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (180, unix_timestamp());
