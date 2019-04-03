/*
	Date: 28 March 2019
	Migration: 159
	Description:    Add fields to front-end submissions interface
	                    1. Add "public" field to groups
	                    2. Add submitted by user/group, submission status, submission form fields
*/

/*==========================================================================*/

ALTER TABLE ca_user_groups ADD COLUMN for_public_use tinyint unsigned not null default 0;

ALTER TABLE ca_objects ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_objects ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_objects ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_objects ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_objects(submission_user_id);
create index i_submission_group_id on ca_objects(submission_group_id);
create index i_submission_status_id on ca_objects(submission_status_id);
create index i_submission_via_form on ca_objects(submission_via_form);

ALTER TABLE ca_entities ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_entities ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_entities ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_entities ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_entities(submission_user_id);
create index i_submission_group_id on ca_entities(submission_group_id);
create index i_submission_status_id on ca_entities(submission_status_id);
create index i_submission_via_form on ca_entities(submission_via_form);

ALTER TABLE ca_places ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_places ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_places ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_places ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_places(submission_user_id);
create index i_submission_group_id on ca_places(submission_group_id);
create index i_submission_status_id on ca_places(submission_status_id);
create index i_submission_via_form on ca_places(submission_via_form);

ALTER TABLE ca_occurrences ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_occurrences ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_occurrences ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_occurrences ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_occurrences(submission_user_id);
create index i_submission_group_id on ca_occurrences(submission_group_id);
create index i_submission_status_id on ca_occurrences(submission_status_id);
create index i_submission_via_form on ca_occurrences(submission_via_form);

ALTER TABLE ca_collections ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_collections ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_collections ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_collections ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_collections(submission_user_id);
create index i_submission_group_id on ca_collections(submission_group_id);
create index i_submission_status_id on ca_collections(submission_status_id);
create index i_submission_via_form on ca_collections(submission_via_form);

ALTER TABLE ca_loans ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_loans ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_loans ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_loans ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_loans(submission_user_id);
create index i_submission_group_id on ca_loans(submission_group_id);
create index i_submission_status_id on ca_loans(submission_status_id);
create index i_submission_via_form on ca_loans(submission_via_form);

ALTER TABLE ca_movements ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_movements ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_movements ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_movements ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_movements(submission_user_id);
create index i_submission_group_id on ca_movements(submission_group_id);
create index i_submission_status_id on ca_movements(submission_status_id);
create index i_submission_via_form on ca_movements(submission_via_form);

ALTER TABLE ca_object_lots ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_object_lots ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_object_lots ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_object_lots ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_object_lots(submission_user_id);
create index i_submission_group_id on ca_object_lots(submission_group_id);
create index i_submission_status_id on ca_object_lots(submission_status_id);
create index i_submission_via_form on ca_object_lots(submission_via_form);

ALTER TABLE ca_object_representations ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_object_representations ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_object_representations ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_object_representations ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_object_representations(submission_user_id);
create index i_submission_group_id on ca_object_representations(submission_group_id);
create index i_submission_status_id on ca_object_representations(submission_status_id);
create index i_submission_via_form on ca_object_representations(submission_via_form);

ALTER TABLE ca_storage_locations ADD COLUMN submission_user_id int unsigned null references ca_users(user_id);
ALTER TABLE ca_storage_locations ADD COLUMN submission_group_id int unsigned null references ca_user_groups(group_id);
ALTER TABLE ca_storage_locations ADD COLUMN submission_status_id int unsigned null references ca_list_items(item_id);
ALTER TABLE ca_storage_locations ADD COLUMN submission_via_form varchar(100) null;

create index i_submission_user_id on ca_storage_locations(submission_user_id);
create index i_submission_group_id on ca_storage_locations(submission_group_id);
create index i_submission_status_id on ca_storage_locations(submission_status_id);
create index i_submission_via_form on ca_storage_locations(submission_via_form);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (159, unix_timestamp());
