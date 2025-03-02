/* 
	Date: 4 August 2010
	Migration: 19
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_list_items MODIFY COLUMN icon LONGBLOB not null;
ALTER TABLE ca_editor_uis MODIFY COLUMN icon LONGBLOB not null;
ALTER TABLE ca_editor_ui_screens MODIFY COLUMN icon LONGBLOB not null;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (19, unix_timestamp());
