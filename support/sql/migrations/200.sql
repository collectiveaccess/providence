/*
	Date: 11 December 2024
	Migration: 200
	Description: Add representation inheritance option to ACL
*/

/*==========================================================================*/

alter table ca_acl add column include_representations tinyint unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (200, unix_timestamp());
