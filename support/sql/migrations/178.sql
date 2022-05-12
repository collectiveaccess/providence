/*
	Date: 9 May 2022
	Migration: 178
	Description:  Add importer rank fields
*/

/*==========================================================================*/


ALTER TABLE ca_data_importer_groups ADD COLUMN `rank` int unsigned not null default 0;
UPDATE ca_data_importer_groups SET `rank` = group_id;
ALTER TABLE ca_data_importer_items ADD COLUMN `rank` int unsigned not null default 0;
UPDATE ca_data_importer_items SET `rank` = item_id;

ALTER TABLE ca_data_importer_items ADD COLUMN mapping_type char(1) not null default 'M';
UPDATE ca_data_importer_items SET mapping_type = 'M';
UPDATE ca_data_importer_items SET mapping_type = 'C' WHERE source LIKE '_CONSTANT_:%';
UPDATE ca_data_importer_items SET source = REGEXP_REPLACE(source, '_CONSTANT_:[0-9]+:', '');

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (178, unix_timestamp());
