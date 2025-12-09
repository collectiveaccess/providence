/*
	Date: 22 Novmember 2025
	Migration: 207
	Description: Add details column for bans - records details of ban
*/

/*==========================================================================*/

ALTER TABLE ca_ip_bans ADD COLUMN details text not null;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (207, unix_timestamp());
