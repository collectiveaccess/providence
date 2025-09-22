/*
	Date: 22 July 2025
	Migration: 206
	Description: Add notes field to labels
*/

/*==========================================================================*/

ALTER TABLE ca_object_representation_labels ADD COLUMN notes text not null;
ALTER TABLE ca_occurrence_labels ADD COLUMN notes text not null;
ALTER TABLE ca_collection_labels ADD COLUMN notes text not null;
ALTER TABLE ca_place_labels ADD COLUMN notes text not null;
ALTER TABLE ca_storage_location_labels ADD COLUMN notes text not null;
ALTER TABLE ca_loan_labels ADD COLUMN notes text not null;
ALTER TABLE ca_movement_labels ADD COLUMN notes text not null;
ALTER TABLE ca_object_lot_labels ADD COLUMN notes text not null;
ALTER TABLE ca_object_labels ADD COLUMN notes text not null;
ALTER TABLE ca_entity_labels ADD COLUMN notes text not null;
ALTER TABLE ca_history_tracking_current_value_labels ADD COLUMN notes text not null;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (206, unix_timestamp());
