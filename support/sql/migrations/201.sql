/*
	Date: 17 February 2025
	Migration: 201
	Description: Add access inheritance fields; add settings to user and group set access
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

ALTER TABLE ca_sets_x_users ADD COLUMN settings text not null;
ALTER TABLE ca_sets_x_user_groups ADD COLUMN settings text not null;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (201, unix_timestamp());
