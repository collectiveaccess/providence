/*
	Date: 19 August 2014
	Migration: 108
	Description: add access field to allow for public displays
*/

alter table ca_bundle_displays add column access tinyint not null default 0;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (108, unix_timestamp());