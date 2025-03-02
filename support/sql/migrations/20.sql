/* 
	Date: 5 August 2010
	Migration: 20
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_object_representation_multifiles MODIFY COLUMN media LONGBLOB not null;
ALTER TABLE ca_object_representation_multifiles MODIFY COLUMN media_metadata LONGBLOB not null;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (20, unix_timestamp());
