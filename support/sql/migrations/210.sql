/*
	Date: 11 March 2026
	Migration: 210
	Description: Support for settings on UI editors and screens
*/

/*==========================================================================*/

alter table ca_editor_uis add column settings longtext not null;
alter table ca_editor_ui_screens add column settings longtext not null;
      
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (210, unix_timestamp());
