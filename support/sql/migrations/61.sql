/* 
	Date: 28 April 2012
	Migration: 61
	Description:
*/

/* Modify existing unused "preview" field for use as storage for clip media */
ALTER TABLE ca_representation_annotations MODIFY COLUMN preview longblob not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (61, unix_timestamp());