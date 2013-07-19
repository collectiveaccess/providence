/*
	Date: 13 June 2013
	Migration: 86
	Description: 
*/

ALTER TABLE ca_objects_x_objects CHANGE COLUMN source_notes source_info longtext not null;
ALTER TABLE ca_object_representations_x_object_representations CHANGE COLUMN source_notes source_info longtext not null;
ALTER TABLE ca_tour_stops_x_tour_stops CHANGE COLUMN source_notes source_info longtext not null;
ALTER TABLE ca_storage_locations_x_storage_locations CHANGE COLUMN source_notes source_info longtext not null;
ALTER TABLE ca_loans_x_loans CHANGE COLUMN source_notes source_info longtext not null;
ALTER TABLE ca_movements_x_movements CHANGE COLUMN source_notes source_info longtext not null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (86, unix_timestamp());