/* 
	Date: 13 October 2026
	Migration: 219
	Description: Update type-based privileges settings for all roles
*/
/*==========================================================================*/

/* This space left intentionally blank - changes are perfored in VersionUpdate219.php */

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, modifier, datetime, schema_name) VALUES (219, 0, unix_timestamp(), 'CORE');
