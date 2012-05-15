/* 
	Date: 11 April 2012
	Migration: 58
	Description:
*/

/* Record original mimetype in field for easy access */
ALTER TABLE ca_object_representations ADD COLUMN mimetype varchar(255) null;
create index i_mimetype on ca_object_representations(mimetype);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (58, unix_timestamp());