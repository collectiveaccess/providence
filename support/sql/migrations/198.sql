/*
	Date: 29 November 2024
	Migration: 198
	Description: Add home location for storage locations
*/

/*==========================================================================*/

alter table ca_storage_locations add column home_location_id int unsigned null;
alter table ca_storage_locations add constraint fk_ca_storage_locations_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict;
      
create index i_home_location_id on ca_storage_locations(home_location_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (198, unix_timestamp());
