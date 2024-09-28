/*
	Date: 06 January 2024
	Migration: 195
	Description: Add rank to ca_site_pages
*/

/*==========================================================================*/

ALTER TABLE ca_site_pages ADD COLUMN `rank` int unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (195, unix_timestamp());
