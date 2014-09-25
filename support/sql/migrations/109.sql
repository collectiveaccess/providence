/*
	Date: 22 September 2014
	Migration: 109
	Description: pseudo-migration to notify users of authentication backend rewrite
*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (109, unix_timestamp());