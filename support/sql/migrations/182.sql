/*
	Date: 27 February 2023
	Migration: 182
	Description:  Add locale field to site pages
*/

/*==========================================================================*/

ALTER TABLE ca_site_pages ADD COLUMN locale_id smallint unsigned null;
CREATE INDEX i_locale_id ON ca_site_pages(locale_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (182, unix_timestamp());
