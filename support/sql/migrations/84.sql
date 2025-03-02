/*
	Date: 28 May 2013
	Migration: 84
	Description: 
*/

drop table if exists ca_bundle_mapping_rules;
drop table if exists ca_bundle_mapping_groups;
drop table if exists ca_bundle_mapping_group_labels;
drop table if exists ca_bundle_mappings;
drop table if exists ca_bundle_mapping_labels;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (84, unix_timestamp());