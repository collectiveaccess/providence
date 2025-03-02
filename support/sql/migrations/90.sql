/*
	Date: 11 August 2013
	Migration: 90
	Description: 
*/

ALTER TABLE ca_object_representations_x_collections ADD COLUMN is_primary tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations_x_entities ADD COLUMN is_primary tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations_x_occurrences ADD COLUMN is_primary tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations_x_places ADD COLUMN is_primary tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations_x_storage_locations ADD COLUMN is_primary tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations_x_vocabulary_terms ADD COLUMN is_primary tinyint unsigned not null default 0;

DROP TABLE IF EXISTS ca_object_events_x_occurrences;
DROP TABLE IF EXISTS ca_object_events_x_places;
DROP TABLE IF EXISTS ca_object_events_x_storage_locations;
DROP TABLE IF EXISTS ca_object_events_x_vocabulary_terms;
DROP TABLE IF EXISTS ca_object_events_x_entities;
DROP TABLE IF EXISTS ca_object_event_labels;
DROP TABLE IF EXISTS ca_object_events;
DROP TABLE IF EXISTS ca_object_lot_events_x_vocabulary_terms;
DROP TABLE IF EXISTS ca_object_lot_events_x_storage_locations;
DROP TABLE IF EXISTS ca_object_lot_event_labels;
DROP TABLE IF EXISTS ca_object_lot_events;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (90, unix_timestamp());