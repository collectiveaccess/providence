/*
	Date: 29 January 2022
	Migration: 175
	Description:    Add metadata element soft delete
*/

/*==========================================================================*/

ALTER TABLE ca_sets_x_users ADD COLUMN pending_access tinyint null;
ALTER TABLE ca_sets_x_users ADD COLUMN activation_key char(36) null;
CREATE UNIQUE INDEX u_activation_key ON ca_sets_x_users(activation_key);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (175, unix_timestamp());
