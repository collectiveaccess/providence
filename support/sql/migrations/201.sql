/*
	Date: 31 March 2025
	Migration: 201
	Description: Add settings to user and group set access
*/

/*==========================================================================*/

ALTER TABLE ca_sets_x_users ADD COLUMN settings text not null;
ALTER TABLE ca_sets_x_user_groups ADD COLUMN settings text not null;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (201, unix_timestamp());
