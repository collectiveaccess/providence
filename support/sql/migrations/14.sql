/* 
	Date: 8 March 2010
	Migration: 14
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	display code should be nullable (optional) for bundle displays and mappings
*/
ALTER TABLE ca_bundle_displays MODIFY COLUMN display_code varchar(100) null;
ALTER TABLE ca_bundle_mappings MODIFY COLUMN mapping_code varchar(100) null;


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (14, unix_timestamp());