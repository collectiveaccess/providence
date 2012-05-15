/* 
	Date: 13 November 2011
	Migration: 48
	Description:
*/

ALTER TABLE ca_object_representations ADD COLUMN md5 char(32) not null;
create index i_md5 on ca_object_representations(md5);

ALTER TABLE ca_object_representations ADD COLUMN original_filename varchar(1024) not null;
create index i_original_filename on ca_object_representations(original_filename);

ALTER TABLE ca_editor_uis ADD COLUMN editor_code varchar(100) null;
create unique index u_code on ca_editor_uis(editor_code);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (48, unix_timestamp());