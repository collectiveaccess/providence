/*
	Date: 4 March 2026
	Migration: 210
	Description: Add ACL representation extension field
*/

/*==========================================================================*/

ALTER TABLE ca_acl ADD COLUMN include_representations tinyint unsigned not null default 0;
      
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (210, unix_timestamp());
