/* 
	Date: 3 September 2010
	Migration: 22
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
UPDATE ca_users SET userclass = 0 WHERE userclass IS NULL;
ALTER TABLE ca_users MODIFY COLUMN userclass TINYINT UNSIGNED NOT NULL;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (22, unix_timestamp());
