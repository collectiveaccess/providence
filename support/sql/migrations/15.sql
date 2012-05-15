/* 
	Date: 11 June 2010
	Migration: 15
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_bundle_mappings ADD COLUMN table_num tinyint unsigned not null;
ALTER TABLE ca_bundle_mapping_relationships ADD COLUMN group_code varchar(100) not null;
ALTER TABLE ca_bundle_mapping_relationships ADD COLUMN element_name varchar(100) not null;
ALTER TABLE ca_bundle_mapping_relationships ADD COLUMN type_id int unsigned null references ca_list_items(item_id);
DROP INDEX u_all ON ca_bundle_mapping_relationships;
CREATE INDEX i_type_id ON ca_bundle_mapping_relationships(type_id);


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (15, unix_timestamp());