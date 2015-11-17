/*
	Date: 16 November 2015
	Migration: 125
	Description: Add view count fields
*/

/*==========================================================================*/

ALTER TABLE ca_objects ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_entities ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_places ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_occurrences ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_collections ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_loans ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_movements ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_storage_locations ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_object_lots ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_object_representations ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_representation_annotations ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_tours ADD COLUMN view_count int unsigned not null default 0;
ALTER TABLE ca_tour_stops ADD COLUMN view_count int unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (125, unix_timestamp());
