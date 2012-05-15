/* 
	Date: 25 August 2011
	Migration: 46
	Description:
*/

/*==========================================================================*/
/* Add fields to support sortability for mapping groups and rules */

alter table ca_bundle_mapping_groups add column rank int unsigned not null;
alter table ca_bundle_mapping_rules add column rank int unsigned not null;


alter table ca_bundle_mapping_rules change column ca_path ca_path_suffix varchar(512) not null;
alter table ca_bundle_mapping_rules change column external_path external_path_suffix varchar(512) not null;

alter table ca_bundle_mapping_groups add column ca_base_path varchar(512) not null;
alter table ca_bundle_mapping_groups add column external_base_path varchar(512) not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (46, unix_timestamp());