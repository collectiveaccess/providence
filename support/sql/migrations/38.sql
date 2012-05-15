/* 
	Date: 3 April 2011
	Migration: 38
	Description:
*/

ALTER TABLE ca_sets_x_users ADD COLUMN sdatetime int unsigned null;
ALTER TABLE ca_sets_x_users ADD COLUMN edatetime int unsigned null;
ALTER TABLE ca_sets_x_user_groups ADD COLUMN sdatetime int unsigned null;
ALTER TABLE ca_sets_x_user_groups ADD COLUMN edatetime int unsigned null;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (38, unix_timestamp());