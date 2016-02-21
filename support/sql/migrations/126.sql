/*
	Date: 7 January 2016
	Migration: 126
	Description: Use LONGTEXT for `vars` and `field_access` columns in ca_user_roles
*/

/*==========================================================================*/

ALTER TABLE ca_user_roles MODIFY vars LONGTEXT NOT NULL, MODIFY field_access LONGTEXT NOT NULL;

/* Always add the update to ca_schema_updates at the end of the file */
# noinspection SqlNoDataSourceInspection
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (126, unix_timestamp());
