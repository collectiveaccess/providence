/*
	Date: 8 August 2014
	Migration: 106
	Description: add current location fields to ca_objects
*/

alter table ca_objects add column current_loc_class tinyint unsigned null;
alter table ca_objects add column current_loc_subclass int unsigned null;
alter table ca_objects add column current_loc_id int unsigned null;

create index i_current_loc_class on ca_objects(current_loc_class);
create index i_current_loc_subclass on ca_objects(current_loc_subclass);
create index i_current_loc_id on ca_objects(current_loc_id);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (106, unix_timestamp());
