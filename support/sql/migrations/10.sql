/* 
	Date: 5 January 2010
	Migration: 10
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	add description field to bundle mapping labels
*/
ALTER TABLE ca_bundle_mapping_labels ADD COLUMN description text not null;

/* -------------------------------------------------------------------------------- */
/*
	remove un-needed table (holdover from CA 0.5x)
*/
DROP TABLE ca_user_workspace_items;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (10, unix_timestamp());