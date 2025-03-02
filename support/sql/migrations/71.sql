/* 
	Date: 25 October 2012
	Migration: 71
	Description:
*/

DROP INDEX i_table_num ON ca_data_importer_groups;

ALTER TABLE ca_data_importer_groups DROP COLUMN table_num;
ALTER TABLE ca_data_importer_groups ADD COLUMN destination varchar(1024) not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (71, unix_timestamp());