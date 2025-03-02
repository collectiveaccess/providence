/* 
	Date: 19 January 2010
	Migration: 11
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	add support for multiple placements of a bundle on the same screen
*/
ALTER TABLE ca_editor_ui_bundle_placements ADD COLUMN placement_code varchar(255) not null;

DROP INDEX u_bundle_name ON ca_editor_ui_bundle_placements;
CREATE UNIQUE INDEX u_bundle_name ON ca_editor_ui_bundle_placements(bundle_name, screen_id, placement_code);

UPDATE ca_editor_ui_bundle_placements SET placement_code = bundle_name;


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (11, unix_timestamp());