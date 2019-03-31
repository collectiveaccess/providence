/*
	Date: 28 March 2019
	Migration: 159
	Description:    Add fields to front-end submissions interface
	                    1. Add "public" field to groups
	                    2. Add submitted_by
*/

/*==========================================================================*/

#ALTER TABLE ca_user_groups ADD COLUMN for_public_use tinyint unsigned not null default 0;

ALTER TABLE ca_objects ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_objects ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_objects ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_entities ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_entities ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_entities ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_places ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_places ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_places ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_occurrences ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_occurrences ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_occurrences ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_collections ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_collections ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_collections ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_loans ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_loans ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_loans ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_movements ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_movements ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_movements ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_object_lots ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_object_lots ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_object_lots ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_object_representations ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_object_representations ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_object_representations ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

ALTER TABLE ca_storage_locations ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_storage_locations ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_storage_locations ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (159, unix_timestamp());
