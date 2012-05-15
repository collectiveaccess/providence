/* 
	Date: 12 April 2012
	Migration: 59
	Description:
*/

/* Support linking a user login to an entity record */
ALTER TABLE ca_users ADD COLUMN entity_id int unsigned null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (59, unix_timestamp());
