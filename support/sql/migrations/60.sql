/* 
	Date: 26 April 2012
	Migration: 60
	Description:
*/

/* Fix typo in schema and make mimetype NULL */
ALTER TABLE ca_object_representations MODIFY COLUMN mimetype varchar(255) null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (60, unix_timestamp());