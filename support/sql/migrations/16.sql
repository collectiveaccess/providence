/* 
	Date: 10 July 2010
	Migration: 16
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_object_representations MODIFY COLUMN media LONGBLOB not null;
ALTER TABLE ca_object_representations MODIFY COLUMN media_metadata LONGBLOB not null;


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (16, unix_timestamp());
