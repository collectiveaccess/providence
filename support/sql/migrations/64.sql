/* 
	Date: 25 May 2012
	Migration: 64
	Description:
*/

/* Fix typo in schema */
ALTER TABLE ca_acl CHANGE COLUMN aci_id acl_id int unsigned not null auto_increment;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (64, unix_timestamp());