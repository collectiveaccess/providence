/*
	Date: 19 October 2026
	Migration: 218
	Description: Ensure ca_editor_uis settings field is present
*/
/*==========================================================================*/

/* This space left intentionally blank - changes are perfored in VersionUpdate218.php */

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, modifier, datetime, schema_name) VALUES (218, 0, unix_timestamp(), 'CORE');
