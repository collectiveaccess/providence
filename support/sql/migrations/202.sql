/*
	Date: 17 April 2025
	Migration: 202
	Description: Add checked field to set items; add access inheritance fields
*/

/*==========================================================================*/

ALTER TABLE ca_collections ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_entities ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_occurrences ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_places ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_list_items ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_storage_locations ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_object_lots ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_loans ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_movements ADD COLUMN access_inherit_from_parent tinyint unsigned not null default 0;

ALTER TABLE ca_entities ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_occurrences ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_places ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_list_items ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_storage_locations ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_object_lots ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_loans ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_movements ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_set_items add column checked tinyint unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (202, unix_timestamp());
