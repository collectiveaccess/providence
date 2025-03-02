/* 
	Date: 4 March 2010
	Migration: 13
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	add color and icon options for list items (really intended to support color coding of types)
	and UI screens
*/
ALTER TABLE ca_list_items ADD COLUMN color char(6) null;
ALTER TABLE ca_list_items ADD COLUMN icon longtext not null;

ALTER TABLE ca_editor_uis ADD COLUMN color char(6) null;
ALTER TABLE ca_editor_uis ADD COLUMN icon longtext not null;

ALTER TABLE ca_editor_ui_screens ADD COLUMN color char(6) null;
ALTER TABLE ca_editor_ui_screens ADD COLUMN icon longtext not null;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (13, unix_timestamp());