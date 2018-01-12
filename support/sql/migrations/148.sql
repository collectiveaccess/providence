/*
	Date: 11 October 2017
	Migration: 148
	Description: Remove unique index to allow for reuse of paths
*/

 
DROP INDEX u_path on ca_site_pages;
CREATE INDEX i_path on ca_site_pages(path);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (148, unix_timestamp());