/*
	Date: 13 December 2016
	Migration: 143
	Description: 
*/

/*==========================================================================*/

ALTER TABLE ca_set_items ADD COLUMN deleted tinyint not null default 0;
ALTER TABLE ca_site_templates ADD COLUMN template_code varchar(100) not null;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (143, unix_timestamp());